<?php
//Version 107
/**
 * Extended product Controller of Catalog product controller
 *
 * @category   Ktree
 * @package    Ktree_ExtendedMbizConnector
 * @author      KT097
 */
include_once("Mage/Adminhtml/controllers/Catalog/ProductController.php");

// require_once("../../Helper/ERunActions.php");


class Microbiz_Connector_Catalog_ProductController extends Mage_Adminhtml_Catalog_ProductController
{
    protected $_publicActions = array('saveSyncRecords');

    const MAX_QTY_VALUE = 99999999.9999;

    /*protected function _construct()
    {
        Mage::getSingleton('core/session', array('name' => 'adminhtml'));
        if (!(Mage::getSingleton('admin/session') && Mage::getSingleton('admin/session')->isLoggedIn())) {
            header('Location: ' . Mage::helper('adminhtml')->getUrl('adminhtml/index/login'));
            exit;
            $this->_forward('adminhtml/index/login');
            return;
        } else {
            parent::_construct();
        }
    }*/

    /**
     * Save product action
     * Extended product controller for saving the modified information into sync tables
     * after product save it will dispatch an custom event ktree_product_save
     * we can get the array of updated attributes
     * @author KT097
     */
    public function saveAction()
    {

        $storeId = $this->getRequest()->getParam('store');
        $redirectBack = $this->getRequest()->getParam('back', false);
        $productId = $this->getRequest()->getParam('id');
        $isEdit = (int)($this->getRequest()->getParam('id') != null);

        $data = $this->getRequest()->getPost();
        /*if(isset($data['product']['sync_prd_create'])) {
        $data['product']['sync_status']=$data['product']['sync_prd_create'];
        } */ //echo "<pre>";
        //print_r($data); exit;
        if (isset($data['configurable_products_data'])) {
            $data['product']['configurable_products_data'] = json_decode($data['configurable_products_data'], true);
        }
        if (isset($data['configurable_attributes_data'])) {
            $data['product']['configurable_attributes_data'] = json_decode($data['configurable_attributes_data'], true);
        }
        $store_price = $data['product']['store_price'];
        if (!$store_price) {
            $data['product']['store_price'] = $data['product']['price'];
        }
        $productpostdata = $data['product'];
        if (isset($data['category_ids'])) {
            $productpostdata['category_ids'] = $data['category_ids'];
            $categoryids = explode(',', $data['category_ids']);
        }

        $product = Mage::getModel('catalog/product')->load($productId);
        if ($productId) {
            $productinfo = Mage::getModel('Microbiz_Connector_Model_Observer')->getProductInfo($productId);
        } else {
            $productinfo['is_newly_created'] = 1;
        }
        if ($product->getTypeId() == 'configurable' || isset($productpostdata['configurable_products_data'])) {
            if ($productId) {
                $confAttributes = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
            }
            $productinfo['configurable_attributes_data'] = $productpostdata['configurable_attributes_data'];
            unset($productpostdata['configurable_attributes_data'][0]['html_id']);
            /*$productpostdata['configurable_attributes_data'] = array_diff($productpostdata['configurable_attributes_data'], $confAttributes);
            if (!$productpostdata['configurable_attributes_data']) {
            unset($productpostdata['configurable_attributes_data']);
            }*/
            //for featching configurable product child ids and updated info
            if ($productId) {
                $childProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null, $product);
                $childproductids = array();
                foreach ($childProducts as $childProduct) {
                    $childproductinfo = $childProduct->getData();
                    $childproductids[] = $childproductinfo['entity_id'];
                }
            }
            $childcount = 0;
            $newChildProductIds = array();
            foreach ($productpostdata['configurable_products_data'] as $key => $value) {
                $newChildProductIds[] = $key;
                if (!in_array($key, $childproductids)) {
                    $childcount++;
                }
            }
            $productinfo['configurable_products_data'] = $productpostdata['configurable_products_data'];
            $removedIds = array_diff($childproductids, $newChildProductIds);
            if (count($removedIds)) {
                $productpostdata['configurable_productremoved_data'] = $removedIds;
            }
            $addedIds = array_diff($newChildProductIds, $childproductids);
            if (!count($addedIds) && !count($removedIds)) {
                unset($productpostdata['configurable_products_data']);
            }


        }
        // for finding category modifications for the product
        $_catCollection = $product->getCategoryCollection();
        $catids = array();
        foreach ($_catCollection as $_category) {
            $catids[] = $_category->getId();
        }
        if (isset($productpostdata['category_ids']) && $productpostdata['category_ids']) {
            $catids = array_unique($catids);
            $categoryids = array_unique($categoryids);
            $removedCategories = array_diff($catids, $categoryids);
            $addedCategories = array_diff($categoryids, $catids);
            //$categoryids=explode(',',$productpostdata['category_ids']);
            $productpostdata['category_ids'] = serialize($categoryids);
        }

        foreach ($productpostdata['media_gallery'] as $key => $media) {
            $productpostdata['media_gallery'][$key] = json_decode($media);
        }

        $i = 0;

        foreach ($productpostdata['media_gallery']['images'] as $k => $images) {
            $productpostdata['media_gallery']['images'][$i] = (array)$images;
            $i++;
        }

        foreach ($productpostdata as $k1 => $v1) {
            if (!is_array($v1)) {
                if (!isset($productinfo[$k1]) || $productpostdata[$k1] != $productinfo[$k1]) {
                    $update_items[$k1] = $productpostdata[$k1];
                }
            }
        }

        // $update_items['configurable_attributes_data'] = $productinfo['configurable_attributes_data'];

        if (isset($data['links']) && $data['links']) {
            $update_items['links'] = serialize($data['links']);
        }

        if (isset($productpostdata['group_price'])) {
            foreach ($productpostdata['group_price'] as $k1 => $v1) {
                if (is_array($v1)) {
                    foreach ($v1 as $groupkey => $groupval) {
                        if ($productpostdata['group_price'][$k1][$groupkey] != $productinfo['group_price'][$k1][$groupkey]) {
                            $update_items['group_price'][$k1] = $productpostdata['group_price'][$k1];
                        }
                    }
                }
            }
        }
        if (isset($update_items['group_price']) && count($update_items['group_price'])) {
            $update_items['group_price'] = serialize($update_items['group_price']);
        }
        if (isset($productpostdata['tier_price'])) {
            foreach ($productpostdata['tier_price'] as $k1 => $v1) {
                if (is_array($v1)) {
                    foreach ($v1 as $groupkey => $groupval) {
                        if ($productpostdata['tier_price'][$k1][$groupkey] != $productinfo['tier_price'][$k1][$groupkey]) {
                            $update_items['tier_price'][$k1] = $productpostdata['tier_price'][$k1];
                        }
                    }
                }
            }
        }
        if (isset($update_items['tier_price']) && count($update_items['tier_price'])) {
            $update_items['tier_price'] = serialize($update_items['tier_price']);
        }

        foreach ($productpostdata['stock_data'] as $k1 => $v1) {
            if (!is_array($v1)) {
                if (!isset($productinfo['stock_item'][$k1]) || $productpostdata['stock_data'][$k1] != $productinfo['stock_item'][$k1]) {
                    $update_items[$k1] = $productpostdata['stock_data'][$k1];
                }
            }
        }

        if (isset($productpostdata['media_gallery']['images'])) {
            foreach ($productpostdata['media_gallery']['images'] as $k1 => $v1) {
                $update_items['media_gallery']['images'][$k1] = array_diff($productpostdata['media_gallery']['images'][$k1], $productinfo['media_gallery']['images'][$k1]);
                unset($update_items['media_gallery']['images'][$k1]['url']);
                if (!count($update_items['media_gallery']['images'][$k1]))
                    unset($update_items['media_gallery']['images']);
            }
        }
        if (isset($update_items['media_gallery']['images']) && count($update_items['media_gallery']['images'])) {
            $update_items['media_gallery']['images'] = serialize($update_items['media_gallery']['images']);
        }

        if (isset($update_items['original_inventory_qty']))
            unset($update_items['original_inventory_qty']);
        if (isset($update_items['use_config_gift_message_available']))
            unset($update_items['use_config_gift_message_available']);

        if ($data) {

            if (!isset($stockData['use_config_manage_stock'])) {
                $stockData['use_config_manage_stock'] = 0;
            }
            if (isset($stockData['qty']) && (float)$stockData['qty'] > self::MAX_QTY_VALUE) {
                $stockData['qty'] = self::MAX_QTY_VALUE;
            }
            if (isset($stockData['min_qty']) && (int)$stockData['min_qty'] < 0) {
                $stockData['min_qty'] = 0;
            }
            if (!isset($stockData['is_decimal_divided']) || $stockData['is_qty_decimal'] == 0) {
                $stockData['is_decimal_divided'] = 0;
            }

            $product = $this->_initProductSave();
            if (!$store_price) {
                //$data['product']['store_price']= $data['product']['price'];
                $product->setStorePrice($data['product']['price']);
            }
            if (isset($data['product']['sync_status'])) {
                $data['product']['sync_status'] = $data['product']['sync_status'];
                $product->setSyncStatus($data['product']['sync_status']);
            }
            try {
                $product->save();
                $productId = $product->getId();

                /**
                 * Do copying data to stores
                 */
                if (isset($data['copy_to_stores'])) {
                    foreach ($data['copy_to_stores'] as $storeTo => $storeFrom) {
                        $newProduct = Mage::getModel('catalog/product')->setStoreId($storeFrom)->load($productId)->setStoreId($storeTo)->save();
                    }
                }

                Mage::getModel('catalogrule/rule')->applyAllRulesToProduct($productId);

                $this->_getSession()->addSuccess($this->__('The product has been saved.'));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage())->setProductData($data);
                $redirectBack = true;
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage());
                $redirectBack = true;
            }
        }

        if ($redirectBack) {
            $this->_redirect('*/*/edit', array(
                'id' => $productId,
                '_current' => true
            ));
        } elseif ($this->getRequest()->getParam('popup')) {
            $this->_redirect('*/*/created', array(
                '_current' => true,
                'id' => $productId,
                'edit' => $isEdit
            ));
        } else {
            $this->_redirect('*/*/', array(
                'store' => $storeId
            ));
        }
    }

    /**
     * Delete product action
     * Extended delete functionality from core for custom event for syncing information into sync tables
     * @author KT097
     */
    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            $product = Mage::getModel('catalog/product')->load($id);
            $sku = $product->getSku();
            //$sync_prd_create = $product->getSyncPrdCreate();
            $sync_status = $product->getSyncStatus();
            $productid = $id;
            $deletedproduct = array(
                'sku' => $sku,
                'id' => $productid
            );
            try {
                $product->delete();
                $overallsyncStatus = Mage::getStoreConfig('connector/settings/syncstatus');
                $checkObjectRelation = Mage::helper('microbiz_connector')->checkObjectRelation($productid, 'Product');
                if ($overallsyncStatus || $checkObjectRelation) {
                    if (($sync_status) || $checkObjectRelation) {
                        Mage::dispatchEvent('ktree_product_delete', array(
                            'sku' => $deletedproduct
                        ));
                    }
                }
                Mage::helper('microbiz_connector')->deleteAppRelation($productid, 'Product');
                $this->_getSession()->addSuccess($this->__('The product has been deleted.'));
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }

        $this->getResponse()->setRedirect($this->getUrl('*/*/', array(
            'store' => $this->getRequest()->getParam('store')
        )));
    }

    public function saveSyncRecordsAction()
    {
        $id = $this->getRequest()->getParam('id');
        $product = Mage::getModel('catalog/product')->load($id);
        Mage::log('productInfo');
    }

}

