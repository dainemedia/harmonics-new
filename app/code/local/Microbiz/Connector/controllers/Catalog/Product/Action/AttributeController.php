<?php
//version 103
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 27/3/14
 * Time: 6:32 PM
 */
include_once("Mage/Adminhtml/controllers/Catalog/Product/Action/AttributeController.php");
class Microbiz_Connector_Catalog_Product_Action_AttributeController extends Mage_Adminhtml_Catalog_Product_Action_AttributeController
{
    /*protected function _construct() {
      //  Mage::getSingleton('core/session', array('name'=>'adminhtml'));
        if (!(Mage::getSingleton('admin/session') && Mage::getSingleton('admin/session')->isLoggedIn())) {
            header('Location: '.Mage::helper('adminhtml')->getUrl('adminhtml/index/login'));
            exit;
            $this->_forward('adminhtml/index/login');
            return;
        } else {
            parent::_construct();
        }
    }*/
    public function saveAction()
    {
        if (!$this->_validateProducts()) {
            return;
        }

        /* Collect Data */
        $inventoryData      = $this->getRequest()->getParam('inventory', array());
        $attributesData     = $this->getRequest()->getParam('attributes', array());
        $websiteRemoveData  = $this->getRequest()->getParam('remove_website_ids', array());
        $websiteAddData     = $this->getRequest()->getParam('add_website_ids', array());
        $updatedProductIds = $this->_getHelper()->getProductIds();
        $arrUpdatedAttributes = array();
        /*if(isset($attributesData))
        {
            foreach($attributesData as $k=>$data)
            {
                $arrUpdatedAttributes[$k] = $data;
            }
        }*/
        if(!empty($inventoryData))
        {
            $arrUpdatedAttributes['is_version_update'] = true;
            foreach($inventoryData as $k=>$data)
            {
                $arrUpdatedAttributes[$k] = $data;
            }
        }
        if(!empty($websiteRemoveData))
        {
            $arrUpdatedAttributes['is_version_update'] = true;
            foreach($websiteRemoveData as $k=>$data)
            {
                $arrUpdatedAttributes[$k] = $data;
            }
        }
        if(!empty($websiteAddData))
        {
            $arrUpdatedAttributes['is_version_update'] = true;
            foreach($websiteAddData as $k=>$data)
            {
                $arrUpdatedAttributes[$k] = $data;
            }
        }

        /* Prepare inventory data item options (use config settings) */
        foreach (Mage::helper('cataloginventory')->getConfigItemOptions() as $option) {
            if (isset($inventoryData[$option]) && !isset($inventoryData['use_config_' . $option])) {
                $inventoryData['use_config_' . $option] = 0;
            }
        }

        try {
            if ($attributesData) {
                $dateFormat = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
                $storeId    = $this->_getHelper()->getSelectedStoreId();

                foreach ($attributesData as $attributeCode => $value) {
                    $attribute = Mage::getSingleton('eav/config')
                        ->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeCode);
                    if (!$attribute->getAttributeId()) {
                        unset($attributesData[$attributeCode]);
                        continue;
                    }
                    if ($attribute->getBackendType() == 'datetime') {
                        if (!empty($value)) {
                            $filterInput    = new Zend_Filter_LocalizedToNormalized(array(
                                'date_format' => $dateFormat
                            ));
                            $filterInternal = new Zend_Filter_NormalizedToLocalized(array(
                                'date_format' => Varien_Date::DATE_INTERNAL_FORMAT
                            ));
                            $value = $filterInternal->filter($filterInput->filter($value));
                        } else {
                            $value = null;
                        }
                        $attributesData[$attributeCode] = $value;
                    } elseif ($attribute->getFrontendInput() == 'multiselect') {
                        // Check if 'Change' checkbox has been checked by admin for this attribute
                        $isChanged = (bool)$this->getRequest()->getPost($attributeCode . '_checkbox');
                        if (!$isChanged) {
                            unset($attributesData[$attributeCode]);
                            continue;
                        }
                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }
                        $attributesData[$attributeCode] = $value;
                    }
                }

                Mage::getSingleton('catalog/product_action')
                    ->updateAttributes($this->_getHelper()->getProductIds(), $attributesData, $storeId);
            }
            if ($inventoryData) {
                /** @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
                $stockItem = Mage::getModel('cataloginventory/stock_item');
                $stockItem->setProcessIndexEvents(false);
                $stockItemSaved = false;

                foreach ($this->_getHelper()->getProductIds() as $productId) {
                    $stockItem->setData(array());
                    $stockItem->loadByProduct($productId)
                        ->setProductId($productId);

                    $stockDataChanged = false;
                    foreach ($inventoryData as $k => $v) {
                        $stockItem->setDataUsingMethod($k, $v);
                        if ($stockItem->dataHasChangedFor($k)) {
                            $stockDataChanged = true;
                        }
                    }
                    if ($stockDataChanged) {
                        $stockItem->save();
                        $stockItemSaved = true;
                    }
                }

                if ($stockItemSaved) {
                    Mage::getSingleton('index/indexer')->indexEvents(
                        Mage_CatalogInventory_Model_Stock_Item::ENTITY,
                        Mage_Index_Model_Event::TYPE_SAVE
                    );
                }
            }

            if ($websiteAddData || $websiteRemoveData) {
                /* @var $actionModel Mage_Catalog_Model_Product_Action */
                $actionModel = Mage::getSingleton('catalog/product_action');
                $productIds  = $this->_getHelper()->getProductIds();

                if ($websiteRemoveData) {
                    $actionModel->updateWebsites($productIds, $websiteRemoveData, 'remove');
                }
                if ($websiteAddData) {
                    $actionModel->updateWebsites($productIds, $websiteAddData, 'add');
                }

                /**
                 * @deprecated since 1.3.2.2
                 */
                Mage::dispatchEvent('catalog_product_to_website_change', array(
                    'products' => $productIds
                ));

                $notice = Mage::getConfig()->getNode('adminhtml/messages/website_chnaged_indexers/label');
                if ($notice) {
                    $this->_getSession()->addNotice($this->__((string)$notice, $this->getUrl('adminhtml/process/list')));
                }
            }

            Mage::dispatchEvent('ktree_product_massupdate_save', array(
                'productids' => $updatedProductIds,
                'updatedattributes' => $arrUpdatedAttributes,

            ));
            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) were updated', count($this->_getHelper()->getProductIds()))
            );
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('An error occurred while updating the product(s) attributes.'));
        }

        $this->_redirect('*/catalog_product/', array('store'=>$this->_getHelper()->getSelectedStoreId()));
    }
}