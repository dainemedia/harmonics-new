<?php
//Version 185

/** @noinspection PhpDocSignatureInspection */

/** @noinspection PhpDocSignatureInspection */

/** @noinspection PhpDocSignatureInspection */
class Microbiz_Connector_Model_Api extends Mage_Api_Model_Resource_Abstract
{
    const INITIAL_SYNC_LIMIT = 100;
    const SYNC_ALL_PRODUCTS = 1;
    const SYNC_ENAB_PRODUCTS = 2;
    /**
     * for Magento Change records customers/product/categories bulk products
     *
     * @return array of change records
     * @author KT097
     */
    public function extendedgetSyncDetails($isInitialSync=null)
    {
        $collection = array();
        $batchsize = Mage::helper('microbiz_connector')->getBatchSize();
        //$headerdatacollection = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->setOrder('header_id', 'asc')->addFieldToFilter('status',array('in' => array('Pending','Processing')))->setPageSize($batchsize)->getData();
        register_shutdown_function(array($this, 'mbizUpdateFatalErrorHandler'));
        if($isInitialSync) {
            $attributeCollection = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                ->setOrder('header_id', 'asc')
                ->addFieldToFilter('model_name', 'AttributeSets')
                ->addFieldToFilter('status',array('in' => array('Pending','Processing')));
            $attributeSetPendingCollection = $attributeCollection->setPageSize(1)->getData();
            if($attributeSetPendingCollection) {
                $headerdatacollection = $attributeSetPendingCollection;
            }
            else {
                $attriuteSetRelations = Mage::helper('microbiz_connector')->getAllAttributeSetsRelation();
                $headerdatacollection = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                    ->setOrder('header_id', 'asc')
                    ->addFieldToFilter(array('model_name', 'ref_obj_id'),
                        array(
                            array('nin'=>array('Product')),
                            array('in'=>$attriuteSetRelations)
                        ))
                    ->addFieldToFilter('status',array('in' => array('Pending','Processing')));
                $headerdatacollection->addFieldToFilter('is_initial_sync',1);
                $headerdatacollection->setPageSize($batchsize)->getData();
            }

        }
        else{
            $attriuteSetRelations = Mage::helper('microbiz_connector')->getAllAttributeSetsRelation();
            $headerdatacollection = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                ->setOrder('header_id', 'asc')
                ->addFieldToFilter(array('model_name', 'ref_obj_id'),
                    array(
                        array('nin'=>array('Product')),
                        array('in'=>$attriuteSetRelations)
                    ))
                ->addFieldToFilter('status',array('in' => array('Pending','Processing')));
            $headerdatacollection->addFieldToFilter('is_initial_sync',0);
            $headerdatacollection->setPageSize($batchsize)->getData();
        }

        foreach ($headerdatacollection as $headerdata) {

            $modelname = $headerdata['model_name'];
            $header_id = $headerdata['header_id'];
            Mage::unregister('sync_magento_status_header_id');
            Mage::register('sync_magento_status_header_id', $header_id);
            /*Adding Version Numbers code starts here */
            $attrRel = Mage::helper('microbiz_connector')->checkIsObjectExists($headerdata['obj_id'], $modelname);
            if (!empty($attrRel)) {
                $headerdata['mage_version_number'] = $attrRel['mage_version_number'];
                $headerdata['mbiz_version_number'] = $attrRel['mbiz_version_number'];

            } else {
                $headerdata['mage_version_number'] = 100;
                $headerdata['mbiz_version_number'] = 100;
            }
            /*Adding Version Numbers code starts here */
            $collection[$header_id]['HeaderDetails'] = array(
                'model' => $modelname,
                'obj_id' => $headerdata['obj_id'],
                'mbiz_obj_id' => $headerdata['mbiz_obj_id'],
                'mbiz_ref_obj_id' => $headerdata['mbiz_ref_obj_id'],
                'ref_obj_id' => $headerdata['ref_obj_id'],
                'is_initial_sync' => $headerdata['is_initial_sync'],
                'obj_status' => $headerdata['obj_status'],
                'mage_version_number' => $headerdata['mage_version_number'],
                'mbiz_version_number' => $headerdata['mbiz_version_number']
            );

            switch ($modelname) {
                case 'Orders':
                    $collection[$header_id]['ItemDetails'] = $this->getOrderinformation($headerdata['obj_id']);
                    break;

                case 'Product' :
                    if(!$isInitialSync) {
                        if ($headerdata['associated_configurable_products']) {
                            $collection[$header_id]['HeaderDetails']['associated_configurable_products'] = unserialize($headerdata['associated_configurable_products']);

                            $associatedParents = unserialize($headerdata['associated_configurable_products']);

                            if (!empty($associatedParents)) {
                                $objectType = 'Product';
                                foreach ($associatedParents as $parent) {
                                    $prdRel = Mage::helper('microbiz_connector')->checkIsObjectExists($parent, $objectType);

                                    if (!empty($prdRel)) {
                                        $collection[$header_id]['HeaderDetails']['associated_configurable_products_versions'][$parent]['mage_version_number'] = $prdRel['mage_version_number'];
                                        $collection[$header_id]['HeaderDetails']['associated_configurable_products_versions'][$parent]['mbiz_version_number'] = $prdRel['mbiz_version_number'];
                                    }
                                }
                            }
                        }

                        $itemdatacollection = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->getData();
                        $modifieddata       = array();
                        foreach ($itemdatacollection as $itemdata) {
                            $attribute_name                = $itemdata['attribute_name'];
                            $attribute_value               = (unserialize($itemdata['attribute_value'])) ? unserialize($itemdata['attribute_value']) : $itemdata['attribute_value'];
                            $modifieddata[$attribute_name] = $attribute_value;
                        }

                        $collection[$header_id]['ItemDetails'] = $modifieddata;
                    }
                    else {
                        Mage::log("came to product ",null,'linking.log');
                        $product = Mage::getSingleton('catalog/product')->load($headerdata['obj_id']);
                        $productId = $product->getId();
                        Mage::log($productId,null,'linking.log');
                        Mage::log($product->getTypeId(),null,'linking.log');
                        if ($product->getTypeId() == 'simple') {

                            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($productId);
                            Mage::log($parentIds,null,'linking.log');
                            if(count($parentIds)>0)
                            {
                                $collection[$header_id]['HeaderDetails']['associated_configurable_products'] = $parentIds;
                                Mage::getModel('extendedmbizconnector/extendedmbizconnector')->setAssociatedConfigurableProducts(serialize($parentIds))->setId($header_id)->save();
                                //$headerdata['associated_configurable_products'] = serialize($parentIds);

                                $objectType = 'Product';
                                foreach($parentIds as $parent) {
                                    $prdRel = Mage::helper('microbiz_connector')->checkIsObjectExists($parent, $objectType);

                                    if(!empty($prdRel)) {
                                        $collection[$header_id]['HeaderDetails']['associated_configurable_products_versions'][$parent]['mage_version_number'] = $prdRel['mage_version_number'];
                                        $collection[$header_id]['HeaderDetails']['associated_configurable_products_versions'][$parent]['mbiz_version_number'] = $prdRel['mbiz_version_number'];
                                    }
                                }
                            }
                        }


                        $status = $this->getInitialSyncData($modelname,$headerdata['obj_id'],$header_id);

                        if($status)
                        {
                            $itemdatacollection = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->getData();
                            $modifieddata = array();
                            foreach ($itemdatacollection as $itemdata) {
                                $attribute_name = $itemdata['attribute_name'];
                                $attribute_value = (unserialize($itemdata['attribute_value'])) ? unserialize($itemdata['attribute_value']) : $itemdata['attribute_value'];
                                $modifieddata[$attribute_name] = $attribute_value;
                            }

                            $collection[$header_id]['ItemDetails'] = $modifieddata;


                        }


                    }
                    break;


                default:
                    if(!$isInitialSync) {
                        $itemdatacollection = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->getData();
                        $modifieddata = array();
                        foreach ($itemdatacollection as $itemdata) {
                            $attribute_name = $itemdata['attribute_name'];
                            $attribute_value = (unserialize($itemdata['attribute_value'])) ? unserialize($itemdata['attribute_value']) : $itemdata['attribute_value'];
                            $modifieddata[$attribute_name] = $attribute_value;
                        }

                        $collection[$header_id]['ItemDetails'] = $modifieddata;
                    }
                    else {
                        $status = $this->getInitialSyncData($modelname,$headerdata['obj_id'],$header_id);

                        if($status)
                        {
                            $itemdatacollection = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->getData();
                            $modifieddata       = array();
                            foreach ($itemdatacollection as $itemdata) {
                                $attribute_name                = $itemdata['attribute_name'];
                                $attribute_value               = (unserialize($itemdata['attribute_value'])) ? unserialize($itemdata['attribute_value']) : $itemdata['attribute_value'];
                                $modifieddata[$attribute_name] = $attribute_value;
                            }

                            $collection[$header_id]['ItemDetails'] = $modifieddata;

                        }
                    }
                    break;
            }

            $headerdata['status'] = 'Processing';
            Mage::Log("came to sync fetch records",null,'syncrecords.log');
            Mage::Log($header_id,null,'syncrecords.log');
            Mage::Log($headerdata,null,'syncrecords.log');
            $headerModel = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($header_id);
            $headerModel->setMageVersionNumber($headerdata['mage_version_number'])
                ->setMbizVersionNumber($headerdata['mbiz_version_number'])
                ->setStatus('Processing')
                ->setId($header_id)->save();

        }
        $count = count($collection);
        $modifiedData = array();
        $modifiedData['recordsCount'] = $count;
        $modifiedData['syncDetails'] = $collection;

        return json_encode($modifiedData);
    }

    /**
     * for Magento Change records Update information customers/product/categories bulk products
     * @param updateinfo updateinformation array from Mbiz
     * @return array of change records
     * @author KT097
     */
    public function extendedmbizupdateApi($updatedinfo)
    {

        register_shutdown_function(array($this, 'mbizUpdateFatalErrorHandler'));
        $updatedinfo = json_decode($updatedinfo, true);
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {

            $user = Mage::getSingleton('admin/session');
            $user = $user->getUser()->getFirstname();

        } else if (Mage::getSingleton('api/session')) {
            $userApi = Mage::getSingleton('api/session');
            $user = $userApi->getUser()->getUsername();

        } else {
            $user = 'Guest';
        }
        foreach ($updatedinfo as $k => $updateitem) {
            $status = $updateitem['sync_status'];
            Mage::unregister('sync_magento_status_header_id');
            Mage::register('sync_magento_status_header_id', $k);
            $origData = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('header_id', $k)->getData();

            if ($status == 'Completed') {

                try {
                    $mbiz_id = $updateitem['mbiz_obj_id'];
                    $magento_id = $updateitem['obj_id'];
                    $origData[0]['status'] = 'Completed';
                    $origData[0]['mbiz_obj_id'] = $updateitem['mbiz_obj_id'];
                    $origData[0]['mbiz_ref_obj_id'] = $updateitem['mbiz_ref_obj_id'];
                    $modelname = $origData[0]['model_name'];
                    // for saving the relation in relation tables
                    switch ($modelname) {
                        case 'Orders':
                            $updateitem['OrderDetails']['OrderHeaderDetails']['order_id'] = $magento_id;
                            $updateitem['OrderDetails']['OrderHeaderDetails']['mbiz_order_id'] = $mbiz_id;
                            $ordersData = $updateitem['OrderDetails'];
                            $this->updateOrderData($ordersData);

                            break;
                        case 'AttributeSets':
                            $checkObjectRelation = Mage::helper('microbiz_connector')->checkIsObjectExists($magento_id, $modelname);

                            $relationinfo['magento_id'] = $magento_id;
                            $relationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                            $relationinfo['mbiz_id'] = $mbiz_id;
                            $relationinfo['mbiz_version_number'] = $updateitem['mbiz_version_number'];
                            if (!$checkObjectRelation && $origData[0]['obj_status'] != 2) {
                                $relationinfo['last_updated_from'] = 'MAG';
                                $relationinfo['modified_by'] = $user;
                                $relationinfo['modified_at'] = Now();
                                $model = Mage::getModel('mbizattributeset/mbizattributeset')->setData($relationinfo)->save();

                            }
                            if ($checkObjectRelation && $origData[0]['obj_status'] != 2) {
                                $relationId = $checkObjectRelation['id'];
                                $model = Mage::getModel('mbizattributeset/mbizattributeset')->load($relationId);
                                $model->setMbizVersionNumber($updateitem['mbiz_version_number'])->setId($relationId)->save();
                            }
                            Mage::Log($updateitem);
                            if (isset($updateitem['attribute_set_info']) && count($updateitem['attribute_set_info'])) {
                                $attributeSetData['HeaderDetails']['mbiz_obj_id'] = $mbiz_id;
                                $attributeSetData['HeaderDetails']['obj_id'] = $magento_id;
                                $attributeSetData['HeaderDetails']['instanceId'] = Mage::helper('microbiz_connector')->getAppInstanceId();;
                                $attributeSetData['ItemDetails'] = $updateitem['attribute_set_info'];
                                $this->saveAttributeSetSync($attributeSetData, false);
                            }
                            break;
                        case 'Attributes':
                            $checkObjectRelation = Mage::helper('microbiz_connector')->checkIsObjectExists($magento_id, $modelname);

                            $relationinfo['magento_id'] = $magento_id;
                            $relationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                            $relationinfo['mbiz_id'] = $mbiz_id;
                            $relationinfo['mbiz_version_number'] = $updateitem['mbiz_version_number'];
                            if (!$checkObjectRelation && $origData[0]['obj_status'] != 2) {
                                $relationinfo['last_updated_from'] = 'MAG';
                                $relationinfo['modified_by'] = $user;
                                $relationinfo['modified_at'] = Now();
                                $model = Mage::getModel('mbizattribute/mbizattribute')->setData($relationinfo)->save();

                            }
                            if ($checkObjectRelation && $origData[0]['obj_status'] != 2) {
                                $relationId = $checkObjectRelation['id'];
                                $model = Mage::getModel('mbizattribute/mbizattribute')->load($relationId);
                                $model->setMbizVersionNumber($updateitem['mbiz_version_number'])
                                    ->setId($relationId)->save();
                            }
                            if (isset($updateitem['attribute_info']) && is_array($updateitem['attribute_info'])) {
                                try {
                                    $attributeId = $magento_id;
                                    $attributeOptions = array();
                                    if (isset($updateitem['attribute_info']['attribute_options'])) {
                                        $attributeOptions = $updateitem['attribute_info']['attribute_options'];
                                        unset($updateitem['attribute_info']['attribute_options']);
                                    }


                                    $attributeUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);

                                    if (count($attributeOptions)) {

                                        $attributeCode = $attributeUpdate->getAttributeCode();
                                        $attributeOptions['safeOptionIds'] = $updateitem['attribute_info']['safeOptionIds'];
                                        $arrtrResponse = $this->updateAttributeOptions($attributeCode, $attributeOptions, $mbiz_id, false);
                                    }
                                } catch (Mage_Api_Exception $e) {
                                    $exceptions[] = $e->getCustomMessage();

                                }


                            }
                            break;
                        case 'Product':
                            $checkObjectRelation = Mage::helper('microbiz_connector')->checkIsObjectExists($magento_id, $modelname);

                            $relationinfo['magento_id'] = $magento_id;
                            $relationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                            $relationinfo['mbiz_id'] = $mbiz_id;
                            $relationinfo['mbiz_version_number'] = $updateitem['mbiz_version_number'];
                            Mage::log($origData);
                            if (!$checkObjectRelation && $origData[0]['obj_status'] != 2) {

                                $relationinfo['last_updated_from'] = 'MAG';
                                $relationinfo['modified_by'] = $user;
                                $relationinfo['modified_at'] = Now();

                                /*$skuData = Mage::getModel('catalog/product')->load($magento_id);

                                if($skuData->getId()) {
                                    $relationinfo['mbiz_sku'] = $skuData->getSku();
                                }*/
                                $mbizSku = $updateitem['mbiz_sku'];
                                if ($mbizSku) {
                                    $relationinfo['mbiz_sku'] = $mbizSku;
                                }

                                Mage::getModel('mbizproduct/mbizproduct')->setData($relationinfo)->save();

                            }
                            if ($checkObjectRelation && $origData[0]['obj_status'] != 2) {
                                $relationId = $checkObjectRelation['id'];
                                $prdModel = Mage::getModel('mbizproduct/mbizproduct')->load($relationId);
                                $prdModel->setMbizId($mbiz_id);
                                $prdModel->setMbizVersionNumber($updateitem['mbiz_version_number']);
                                $prdModel->setId($relationId)->save();
                            }


                            break;
                        case 'Customer':
                            $checkObjectRelation = Mage::helper('microbiz_connector')->checkObjectRelation($magento_id, $modelname);
                            $relationinfo['magento_id'] = $magento_id;
                            $relationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                            $relationinfo['mbiz_id'] = $mbiz_id;

                            if (!$checkObjectRelation && $origData[0]['obj_status'] != 2) {
                                Mage::getModel('mbizcustomer/mbizcustomer')->setData($relationinfo)->save();

                            }
                            break;
                        case 'CustomerAddressMaster':
                            $checkObjectRelation = Mage::helper('microbiz_connector')->checkObjectRelation($magento_id, $modelname);

                            $relationinfo['magento_id'] = $magento_id;
                            $relationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                            $relationinfo['mbiz_id'] = $mbiz_id;
                            if (!$checkObjectRelation && $origData[0]['obj_status'] != 2) {
                                Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->setData($relationinfo)->save();

                            }
                            break;
                        case 'ProductCategories':
                            $checkObjectRelation = Mage::helper('microbiz_connector')->checkObjectRelation($magento_id, $modelname);
                            $relationinfo['magento_id'] = $magento_id;
                            $relationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                            $relationinfo['mbiz_id'] = $mbiz_id;
                            $relationinfo['is_inventory_category'] = 0;

                            if (!$checkObjectRelation && $origData[0]['obj_status'] != 2) {
                                Mage::getModel('mbizcategory/mbizcategory')->setData($relationinfo)->save();
                            }

                            /*Code to make the root category as sync to microbiz as Yes Starts here*/
                            $categoryId = $magento_id;
                            $category = Mage::getModel('catalog/category')->load($categoryId);
                            if($category->getParentId()==1) {
                                $category->setSyncCatCreate(1)->save();
                            }
                            /*Code to make the root category as sync to microbiz as Yes Ends here*/

                            break;
                    }
                    $historyData = $origData[0];

                    // moving success header data into history table header
                    Mage::getModel('syncheaderhistory/syncheaderhistory')->setData($historyData)->save();

                    $model1 = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($k);
                    // removing the success header data from header table
                    try {
                        $model1->delete();
                    } catch (Mage_Core_Exception $e) {
                        //$this->_fault('not_deleted', $e->getMessage());
                        // Some errors while deleting.
                    }
                    $origitemsData = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $k)->getData();
                    foreach ($origitemsData as $origitemData) {
                        $itemid = $origitemData['id'];
                        unset($origitemData['id']);
                        //moving the items information into history tables which is successfully updated in mbiz
                        Mage::getModel('syncitemhistory/syncitemhistory')->setData($origitemData)->save();
                        $model1 = Mage::getModel('syncitems/syncitems')->load($itemid);
                        // deleting the records form item table
                        try {
                            $model1->delete();
                        } catch (Mage_Core_Exception $e) {
                            //$this->_fault('not_deleted', $e->getMessage());
                            // Some errors while deleting.
                        }
                    }
                } catch (Exception $e) {
                    Mage::Log($e->getMessage());
                }

            } else {
                $origData[0]['status'] = $status;
                $exception_desc = $updateitem['exception_desc'];
                $origData[0]['exception_desc'] = $exception_desc;
                Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($origData[0]['header_id'])->setData($origData[0])->save();
            }
        }

        return "success";
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    public function compare_sortorder(array $a, array $b)
    {
        return strnatcmp($a['index'], $b['index']);
    }

    /**
     * For creating/updating Mbiz records in Magento
     * @param $data
     * @param bool $debug
     * @internal param object $json containg multiple records of customers/products/categoris
     * @return status for each record in json format
     * @author KT097
     */
    public function extendedMbizApi($data, $debug = false)
    {
        register_shutdown_function(array($this, 'mbizFatalErrorHandler'));
        $locale = 'en_US';
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {

            $user = Mage::getSingleton('admin/session');
            $user = $user->getUser()->getFirstname();

        } else if (Mage::getSingleton('api/session')) {
            $userApi = Mage::getSingleton('api/session');
            $user = $userApi->getUser()->getUsername();

        } else {
            $user = 'Guest';
        }
        // changing locale works!

        Mage::app()->getLocale()->setLocaleCode($locale);
        Mage::app()->getTranslator()->init('frontend', true);
        Mage::app()->getTranslator()->init('adminhtml', true);
        // needed to add this
        Mage::app()->getTranslator()->setLocale($locale);
        $finalresult = array();
//return ($debug) ? $debug : $data;
        $data = json_decode($data, true);
        //ksort($data);
        uasort($data, array(
            $this,
            'compare_sortorder'
        ));
        foreach ($data as $k => $singledata) {
            $syncMbizStatusData = Mage::helper('microbiz_connector')->checkMbizSyncHeaderStatus($k);
            $modelname = $singledata['HeaderDetails']['model'];
            $result = array();
            ($debug) ? $result['recieved_microbiz_data'] = $singledata : null;
            Mage::unregister('sync_microbiz_status_header_id');
            Mage::register('sync_microbiz_status_header_id', $k);
            if (!$syncMbizStatusData) {

                Mage::helper('microbiz_connector')->createMbizSyncStatus($k, 'Pending');
                switch ($modelname) {


                    case 'APISettings':
                        $apiSettingsData = $singledata['ItemDetails'];
                        $storesInformation = (isset($apiSettingsData['stores'])) ? $apiSettingsData['stores'] : array();
                        $instanceId = Mage::helper('microbiz_connector')->getAppInstanceId();
                        $exceptions = array();
                        foreach ($storesInformation as $storeInformation) {
                            $storeInformation['instance_id'] = $instanceId;
                            try {
                                $storemodel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->getCollection()->addFieldToFilter('company_id', $storeInformation['company_id'])->addFieldToFilter('store_id', $storeInformation['store_id'])->getFirstItem()->getData();
                                if (!count($storemodel)) {
                                    $storesModel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->setData($storeInformation)->save();
                                    $id = $storesModel->getId();
                                } else {
                                    $id = $storemodel['id'];
                                    Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->load($id)->setData($storeInformation)->setId($id)->save();
                                }
                            } catch (Exception $e) {
                                $exceptions[] = $e->getMessage();
                            }

                        }
                        $stores = $apiSettingsData['include_inventory'];
                        foreach ($stores as $store => $value) {
                            try {
                                $storemodel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->getCollection()->addFieldToFilter('store_id', $store)->getFirstItem()->getData();
                                $id = $storemodel['id'];
                                if ($id) {
                                    $inventoryData = $storemodel;
                                    $inventoryData['include_inventory'] = $value;
                                    Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->load($id)->setData($inventoryData)->setId($id)->save();

                                }
                            } catch (Exception $e) {
                                $exceptions[] = $e->getMessage();
                            }
                        }
                        $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                        $result['obj_id'] = $id;
                        $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                        $result['sync_status'] = 'Completed';
                        $result['exception_desc'] = implode(', ', $exceptions);

                        break;
                    case 'Stores':
                        $inventoryData = $singledata['ItemDetails'];
                        $inventoryData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                        $storemodel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->getCollection()->addFieldToFilter('company_id', $inventoryData['company_id'])->addFieldToFilter('store_id', $inventoryData['store_id'])->getFirstItem()->getData();
                        if (!count($storemodel)) {
                            $storesModel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->setData($inventoryData)->save();
                            $id = $storesModel->getId();
                        } else {
                            $id = $storemodel['id'];
                            Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->load($id)->setData($inventoryData)->setId($id)->save();
                        }
                        if ($id) {
                            $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                            $result['obj_id'] = $id;
                            $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                            $result['sync_status'] = 'Completed';
                        } else {
                            $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                            $result['obj_id'] = '';
                            $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                            $result['sync_status'] = 'Failed';
                            $result['exception_desc'] = "Exception while saving Store Information";
                        }
                        $finalresult[$k] = $result;
                        break;
                    case 'AttributeSets':

                        if ($singledata['HeaderDetails']['obj_status'] == 2) {
                            $attributeSetId = $singledata['HeaderDetails']['obj_id'];
                            if (empty($attributeSetId)) {
                                $relationdata = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->addFieldToFilter('mbiz_id', $singledata['HeaderDetails']['mbiz_obj_id'])->setOrder('id', 'asc')->getData();
                                $attributeSetId = $relationdata[0]['magento_id'];
                            }
                            if ($attributeSetId) {
                                //Load product model collecttion filtered by attribute set id
                                $products = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*')->addFieldToFilter('attribute_set_id', $attributeSetId);

                                //process your product collection for removing product relation from relation table
                                foreach ($products as $p) {
                                    $productinfo = $p->getData();
                                    Mage::helper('microbiz_connector')->deleteAppRelation($productinfo['entity_id'], 'Product');
                                }
                                Mage::helper('microbiz_connector')->deleteAppRelation($attributeSetId, 'AttributeSets');
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $attributeSetId;
                                $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $result['sync_status'] = 'Completed';
                            } else {
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $attributeSetId;
                                $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $result['sync_status'] = 'Failed';
                                $result['exception_desc'] = "Not exists";
                                $result['exception_id'] = 101;
                            }
                            $finalresult[$k] = $result;
                        } else {
                            try {
                                $finalresult[$k] = $this->saveAttributeSetSync($singledata, true);

                            } catch (Mage_Api_Exception $ex) {
                                $finalresult[$k]['exception_full_desc'] = $ex->getCustomMessage();
                            }
                        }

                        /*Code to send on fly request to update magento version number starts here.*/
                        if ($result['sync_status'] == 'Completed' && $singledata['HeaderDetails']['obj_status'] != 2) {
                            $verionUpdateResponse = Mage::helper('microbiz_connector')->updateAttributeSetRelations($attributeSetId, $singledata['HeaderDetails']['mbiz_obj_id']);

                            $finalresult[$k]['version_update_status'] = $verionUpdateResponse['status'];

                            if ($verionUpdateResponse['status'] == 'FAIL') {
                                $finalresult[$k]['version_update_status_msg'] = $verionUpdateResponse['status_msg'];
                            }
                        }
                        /*Code to send on fly request to update magento version number ends here.*/
                        break;

                    case 'Attributes':
                        if ($singledata['HeaderDetails']['obj_status'] == 2) {
                            $attributeId = $singledata['HeaderDetails']['obj_id'];
                            if (empty($attributeId)) {
                                $relationdata = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('mbiz_id', $singledata['HeaderDetails']['mbiz_obj_id'])->setOrder('id', 'asc')->getData();
                                $attributeId = $relationdata[0]['magento_id'];
                            }
                            if ($attributeId) {
                                //Load product model collecttion filtered by attribute set id
                                Mage::helper('microbiz_connector')->deleteAppRelation($attributeId, 'Attributes');
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $attributeId;
                                $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $result['sync_status'] = 'Completed';
                            } else {
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $attributeId;
                                $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $result['sync_status'] = 'Failed';
                                $result['exception_desc'] = "Not exists";
                                $result['exception_id'] = 101;
                            }
                            $finalresult[$k] = $result;
                        } else {
                            $attributeId = '';
                            $attributeSetResponse = '';
                            $mbizAttributeId = $singledata['HeaderDetails']['mbiz_obj_id'];
                            $attributeOptions = array();
                            if (isset($singledata['ItemDetails']['attribute_options'])) {
                                $attributeOptions = $singledata['ItemDetails']['attribute_options'];
                                unset($singledata['ItemDetails']['attribute_options']);
                            }
                            $exceptions = array();
                            if (empty($singledata['HeaderDetails']['obj_id'])) {
                                $attributeData = $singledata['ItemDetails'];
                                $attributeCode = $attributeData['attribute_code'];
                                $mbizAttributeCode = $attributeCode;
                                $checkMbizAttributeCode = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('mbiz_attr_code', $attributeCode)->setOrder('id', 'asc')->getData();
                                if ($checkMbizAttributeCode) {
                                    $attributeCode = $checkMbizAttributeCode[0]['magento_attr_code'];
                                }
                                $isAttributeExists = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', $attributeCode);
                                if ($isAttributeExists->getId()) {

                                    $attributeId = $isAttributeExists->getId();
                                    //Mage::getModel('Mage_Catalog_Model_Product_Attribute_Api')->update($isAttributeExists,$attribute);
                                    $attributeUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
                                    // set frontend labels array with store_id as keys
                                    try {

                                        $attributeUpdate->setAttributeId($attributeId);
                                        $attributeUpdate->setSourceModel($attributeUpdate->getSourceModel());
                                        $attributeUpdate->setIsGlobal($attributeUpdate->getIsGlobal());
                                        if (isset($attributeData['is_configurable'])) {
                                            $attributeUpdate->setIsConfigurable($attributeData['is_configurable']);
                                        }

                                        if (isset($attributeData['is_required'])) {
                                            $attributeUpdate->setIsRequired($attributeData['is_required']);
                                        }
                                        $attributeUpdate->setIsUserDefined($attributeUpdate->getIsUserDefined());
                                        if (isset($attributeData['is_used_for_promo_rules'])) {
                                            $attributeUpdate->setIsUsedForPromoRules($attributeData['is_used_for_promo_rules']);
                                        }
                                        if (isset($attributeData['is_unique'])) {
                                            $attributeUpdate->setIsUnique($attributeData['is_unique']);
                                        }
                                        if (isset($attributeData['frontend_label'])) {
                                            $attributeUpdate->setFrontendLabel($attributeData['frontend_label']);
                                        }
                                        $attributeUpdate->save();
                                    } catch (Mage_Api_Exception $e) {
                                        $exceptions[] = $e->getCustomMessage();

                                    }

                                }


                            } else {
                                try {
                                    $attributeId = $singledata['HeaderDetails']['obj_id'];
                                    $attributeUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
                                    if ($attributeUpdate->getIsUserDefined()) {
                                        $singledata['ItemDetails']['is_configurable'] = (isset($singledata['ItemDetails']['is_configurable']) && !empty($singledata['ItemDetails']['is_configurable'])) ? $singledata['ItemDetails']['is_configurable'] : $attributeUpdate->getIsConfigurable();
                                        $singledata['ItemDetails']['is_used_for_promo_rules'] = (isset($singledata['ItemDetails']['is_used_for_promo_rules']) && !empty($singledata['ItemDetails']['is_used_for_promo_rules'])) ? $singledata['ItemDetails']['is_used_for_promo_rules'] : $attributeUpdate->getIsUsedForPromoRules();
                                        $singledata['ItemDetails']['is_required'] = (isset($singledata['ItemDetails']['is_required']) && !empty($singledata['ItemDetails']['is_required'])) ? $singledata['ItemDetails']['is_required'] : $attributeUpdate->getIsRequired();
                                        $singledata['ItemDetails']['is_unique'] = (isset($singledata['ItemDetails']['is_unique']) && !empty($singledata['ItemDetails']['is_unique'])) ? $singledata['ItemDetails']['is_unique'] : $attributeUpdate->getIsUnique();
                                        $singledata['ItemDetails']['source_model'] = $attributeUpdate->getSourceModel();
                                        //$singledata['ItemDetails']['scope'] =  $attributeUpdate->getScope();
                                        if (isset($singledata['ItemDetails']['apply_to'])) {
                                            $isApplyTo = $singledata['ItemDetails']['apply_to'];
                                            $isApplyToArray = array();
                                            (is_array($isApplyTo) && ($key = array_search(1, $isApplyTo)) !== FALSE) ? $isApplyToArray[$key] = 'simple' : null;
                                            (is_array($isApplyTo) && ($key = array_search(2, $isApplyTo)) !== FALSE) ? $isApplyToArray[$key] = 'configurable' : null;
                                            $applyTo = $attributeUpdate->getApplyTo();
                                            if (count($isApplyToArray) == 2 && is_array($applyTo) && count($applyTo)) {
                                                $isApplyToArray = array_merge($applyTo, $isApplyToArray);
                                            } else if (count($isApplyToArray) == 2) {
                                                $isApplyToArray = array();
                                            }

                                        } else if (array_key_exists('apply_to', $singledata['ItemDetails'])) {
                                            $isApplyToArray = array();
                                        } else {
                                            $isApplyToArray = $attributeUpdate->getApplyTo();
                                        }
                                        $singledata['ItemDetails']['apply_to'] = $isApplyToArray;
                                        switch ($attributeUpdate->getIsGlobal()) {
                                            case 0:
                                                $singledata['ItemDetails']['scope'] = 'store';
                                                break;
                                            case 1:
                                                $singledata['ItemDetails']['scope'] = 'global';
                                                break;
                                            case 2:
                                                $singledata['ItemDetails']['scope'] = 'website';
                                                break;
                                            default:
                                                $singledata['ItemDetails']['scope'] = 'global';
                                                break;
                                        }
                                        $attributeData = $singledata['ItemDetails'];
                                        Mage::getModel('Mage_Catalog_Model_Product_Attribute_Api')->update($attributeId, $attributeData);

                                    }
                                } catch (Mage_Api_Exception $e) {
                                    $exceptions[] = $e->getCustomMessage();

                                }

                            }
                            if ($attributeId) {
                                try {
                                    $attributeUpdate = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attributeId);
                                    if (($attributeUpdate->getSourceModel() == 'eav/entity_attribute_source_table' || is_null($attributeUpdate->getSourceModel()))) {
                                        Mage::Log($attributeOptions);
                                        $attributeCode = $attributeUpdate->getAttributeCode();
                                        $arrtrResponse = $this->updateAttributeOptions($attributeCode, $attributeOptions, $mbizAttributeId, true);
                                    }
                                    $attributeRelation['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                                    $attributeRelation['magento_id'] = $attributeId;
                                    $attributeRelation['mbiz_id'] = $mbizAttributeId;
                                    $attributeRelation['magento_attr_code'] = $attributeCode;
                                    $attributeRelation['mbiz_attr_code'] = $mbizAttributeCode;
                                    $attributeRelation['mbiz_attr_set_id'] = '';
                                    $checkAttributeRelation = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $attributeId)->setOrder('id', 'asc')->getData();

                                    if (!$checkAttributeRelation) {
                                        $attributeRelation['mbiz_version_number'] = $singledata['HeaderDetails']['mbiz_version_number'];
                                        $attributeRelation['last_updated_from'] = 'MBIZ';
                                        $attributeRelation['modified_by'] = $user;
                                        $attributeRelation['modified_at'] = Now();
                                        $model = Mage::getModel('mbizattribute/mbizattribute')->setData($attributeRelation)->save();
                                    } else {
                                        $mbizVerNo = $singledata['HeaderDetails']['mbiz_version_number'];

                                        $attrRelData = Mage::helper('microbiz_connector')->checkIsObjectExists($attributeId, 'Attributes');
                                        if (!empty($attrRelData)) {
                                            $Id = $attrRelData['id'];
                                            $attrModel = Mage::getModel('mbizattribute/mbizattribute')->load($Id);

                                            $attrModel->setMbizVersionNumber($mbizVerNo);
                                            $attrModel->setMageVersionNumber($attrRelData['mage_version_number'] + 1);
                                            $attrModel->setLastUpdateFrom('MBIZ');
                                            $attrModel->setModifiedBy($user);
                                            $attrModel->setModifiedAt(Now());
                                            $attrModel->setId($Id)->save();
                                        }
                                    }
                                    $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                    $result['obj_id'] = $attributeId;
                                    $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                                    $result['exception_desc'] = 'Attribute Option value not defined';
                                    $result['sync_status'] = (count($arrtrResponse)) ? 'Completed' : 'Failed';
                                    if ($result['sync_status'] == 'Completed') {
                                        $attrRelData = Mage::helper('microbiz_connector')->checkIsObjectExists($attributeId, 'Attributes');
                                        if (!empty($attrRelData)) {
                                            $result['mage_version_number'] = $attrRelData['mage_version_number'];
                                            $result['mbiz_version_number'] = $attrRelData['mbiz_version_number'];
                                        }
                                    }
                                    (isset($arrtrResponse['attribute_info'])) ? $result['attribute_info'] = $arrtrResponse['attribute_info'] : null;

                                } catch (Mage_Api_Exception $e) {
                                    $exceptions[] = $e->getCustomMessage();
                                    $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                    $result['obj_id'] = $attributeId;
                                    $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = implode("\n", $exceptions);
                                    $result['exception_id'] = '';
                                }

                            } else {
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $attributeId;
                                $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $result['sync_status'] = 'Failed';
                                $result['exception_desc'] = implode("\n", $exceptions);
                                $result['exception_id'] = '';
                            }
                            $finalresult[$k] = $result;

                            Mage::log("came to attribute Response", null, 'prdrel.log');
                            Mage::log($result, null, 'prdrel.log');
                        }

                        /*Code to send on fly request to update magento version number starts here.*/
                        if ($result['sync_status'] == 'Completed' && $singledata['HeaderDetails']['obj_status'] != 2) {
                            $verionUpdateResponse = Mage::helper('microbiz_connector')->updateAttributeRelations($attributeId, $singledata['HeaderDetails']['mbiz_obj_id']);
                            $finalresult[$k]['version_update_status'] = $verionUpdateResponse['status'];

                            if ($verionUpdateResponse['status'] == 'FAIL') {
                                $finalresult[$k]['version_update_status_msg'] = $verionUpdateResponse['status_msg'];
                            }
                        }
                        /*Code to send on fly request to update magento version number ends here.*/
                        break;
                    case 'Product':
                        if ($singledata['HeaderDetails']['obj_status'] == 2) {
                            $productId = $singledata['HeaderDetails']['obj_id'];
                            if (empty($productId)) {
                                $relationdata = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter('mbiz_id', $singledata['HeaderDetails']['mbiz_obj_id'])->setOrder('id', 'asc')->getData();
                                $productId = $relationdata[0]['magento_id'];
                            }
                            if ($productId) {
                                Mage::helper('microbiz_connector')->deleteAppRelation($productId, 'Product');
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $productId;
                                $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $result['sync_status'] = 'Completed';
                            } else {
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $productId;
                                $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $result['sync_status'] = 'Failed';
                                $result['exception_desc'] = "Not exists";
                                $result['exception_id'] = 101;
                            }
                            $connectorDebug = array();
                            $connectorDebug['instance_id'] = $result['instanceId'];
                            $connectorDebug['status'] = $result['sync_status'];
                            $connectorDebug['status_msg'] = "Product with " . $productId . "  " . $result['exception_desc'];
                            Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
                            $finalresult[$k] = $result;

                        } else {
                            $productid = '';
                            $prdSyncStatus = 0;
                            if (empty($singledata['HeaderDetails']['obj_id'])) {
                                $sku = $singledata['ItemDetails']['sku'];
                                $productid = '';
                                $typeid = $singledata['ItemDetails']['product_type_id'];
                                switch ($typeid) {
                                    case "1":
                                        $type = "simple";
                                        break;
                                    case "2":
                                        $type = "configurable";
                                        break;
                                }
                                $set = $singledata['HeaderDetails']['ref_obj_id'];
                                if (!$set) {
                                    $set = Mage::helper('microbiz_connector')->getObjectRelation($singledata['HeaderDetails']['mbiz_ref_obj_id'], 'AttributeSets', 'Mbiz');
                                }
                                $mbiz_id = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $productData = $singledata['ItemDetails'];
                                $storeInventory = $productData['store_inventory'];
                                unset($productData['store_inventory']);
                                unset($productData['attribute_set_id']);
                                unset($productData['product_id']);
                                try {
                                    $mbizPrdRelation = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter('mbiz_id', $mbiz_id)->setOrder('id', 'asc')->getFirstItem()->getData();

                                    if (!empty($mbizPrdRelation)) {
                                        $productexists = Mage::getModel('catalog/product')->load($mbizPrdRelation['magento_id']);
                                    } else {
                                        $productexists = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);

                                    }

                                    if (array_key_exists('price', $productData)) {
                                        $price_includes_tax = Mage::getStoreConfig('tax/calculation/price_includes_tax');
                                        if ($productData['api_web_price_tax_setting'] != $price_includes_tax) {
                                            unset($productData['price']);
                                            if (array_key_exists('store_price', $productData)) {
                                                unset($productData['store_price']);
                                            }
                                            $prdSyncStatus = 1;
                                        }
                                    }


                                    if ($productexists) {
                                        $productexistsdata = $productexists->getData();

                                        // unset($productData['tax_class_id']);

                                        if ($type == $productexists->getTypeId() && $productexists->getAttributeSetId() == $set) {
                                            $productid = $productexistsdata['entity_id'];
                                            $productupdated = Mage::getModel('Microbiz_Connector_Model_Product_Api')->update($productid, $productData, $store = null, $identifierType = null);

                                        } else {
                                            $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                            $result['obj_id'] = '';
                                            $result['mbiz_obj_id'] = $mbiz_id;
                                            $result['sync_status'] = 'Failed';
                                            $result['exception_desc'] = 'Type or AttributeSet not matching with Existing product';
                                            $result['exception_full_desc'] = 'Product Already Exists with Same Sku but Product Type or AttributeSet is not maatching with The Magento product';
                                            $result['exception_id'] = '';

                                        }
                                    } else {

                                        $productData['websites'] = array(
                                            Mage::getStoreConfig('connector/defaultwebsite/product')
                                        );
                                        $productid = Mage::getModel('Microbiz_Connector_Model_Product_Api')->create($type, $set, $sku, $productData);

                                    }
                                    if ($productid) {
                                        ($storeInventory) ? Mage::getModel('Microbiz_Connector_Model_Storeinventory_Api')->createMbizInventory($storeInventory, $productid) : null;
                                        $relationinfo['magento_id'] = $productid;
                                        $relationinfo['mbiz_id'] = $mbiz_id;
                                        $relationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                                        $checkObjectRelation = Mage::helper('microbiz_connector')->checkObjectRelation($productid, $modelname);

                                        if (!$checkObjectRelation) {
                                            $relationinfo['mbiz_version_number'] = $singledata['HeaderDetails']['mbiz_version_number'];
                                            $relationinfo['last_updated_from'] = 'MBIZ';
                                            $relationinfo['modified_by'] = $user;
                                            $relationinfo['modified_at'] = Now();
                                            if (array_key_exists('sku', $singledata['ItemDetails'])) {
                                                $mbizSku = $singledata['ItemDetails']['sku'];
                                                $relationinfo['mbiz_sku'] = $mbizSku;
                                            }

                                            Mage::getModel('mbizproduct/mbizproduct')->setData($relationinfo)->save();
                                        } else {
                                            $mbizVerNo = $singledata['HeaderDetails']['mbiz_version_number'];

                                            $prdRelData = Mage::helper('microbiz_connector')->checkIsObjectExists($productid, $modelname);
                                            if (!empty($prdRelData)) {
                                                $Id = $prdRelData['id'];
                                                $prdModel = Mage::getModel('mbizproduct/mbizproduct')->load($Id);

                                                $prdModel->setMbizVersionNumber($mbizVerNo);
                                                //$prdModel->setMageVersionNumber($prdRelData['mage_version_number']+1);
                                                $prdModel->setLastUpdateFrom('MBIZ');
                                                $prdModel->setModifiedBy($user);
                                                $prdModel->setModifiedAt(Now());
                                                if (array_key_exists('sku', $singledata['ItemDetails'])) {
                                                    $mbizSku = $singledata['ItemDetails']['sku'];
                                                    $prdModel->setMbizSku($mbizSku);
                                                }
                                                $prdModel->setId($Id)->save();
                                            }
                                        }

                                        //associated Configurable product versions updating code
                                        $associatedConfigurableProductVersions = $singledata['HeaderDetails']['associated_configurable_products_versions'];
                                        if (!empty($associatedConfigurableProductVersions)) {
                                            foreach ($associatedConfigurableProductVersions as $key => $configProductRel) {
                                                $prdRelData = Mage::getModel('mbizproduct/mbizproduct')->getCollection()
                                                    ->addFieldToFilter('mbiz_id', $key)->setOrder('id', 'asc')->getFirstItem()->getData();

                                                if (!empty($prdRelData)) {
                                                    $Id = $prdRelData['id'];
                                                    $prdModel = Mage::getModel('mbizproduct/mbizproduct')->load($Id);

                                                    $prdModel->setMbizVersionNumber($configProductRel['mbiz_version_number']);

                                                    $prdModel->setId($Id)->save();
                                                }
                                            }
                                        }
                                        // if (isset($singledata['HeaderDetails']['associated_configurable_products'])) {
                                        $associated_configurable_products = $singledata['HeaderDetails']['associated_configurable_products'];
                                        $configMsg = Mage::helper('microbiz_connector')->saveSimpleConfig($productid, $associated_configurable_products);

                                        // }
                                        $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                        $result['obj_id'] = $productid;
                                        $result['mbiz_obj_id'] = $mbiz_id;
                                        if (!$prdSyncStatus) {
                                            $result['sync_status'] = 'Completed';
                                        } else {
                                            $result['sync_status'] = 'Failed';
                                            $result['exception_des'] = 'Product Created but Price is not Updated due to Missmatch in Catalog Price Includes Tax Setting ';
                                        }


                                        $prdRelData = Mage::helper('microbiz_connector')->checkIsObjectExists($productid, $modelname);

                                        if (!empty($prdRelData)) {
                                            $result['mage_version_number'] = $prdRelData['mage_version_number'];
                                            $result['mbiz_version_number'] = $prdRelData['mbiz_version_number'];

                                        }

                                        if (is_array($configMsg)) {
                                            $result['sync_status'] = 'Failed';
                                            $result['exception_desc'] = 'Product Created but unable to save configurable product Associations. ' . $configMsg['exception_desc'];
                                            $result['exception_id'] = '';
                                        }
                                    }

                                } catch (Zend_Db_Statement_Exception $ex) {
                                    $result['sync_status'] = 'Pending';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_full_desc'] = $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (PDOException $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (Mage_Api_Exception $ex) {
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_full_desc'] = $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (Exception $ex) {
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage();
                                    $result['exception_full_desc'] = $ex->getMessage();
                                    $result['exception_id'] = $ex->getCode();
                                }
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['mbiz_obj_id'] = $mbiz_id;


                                $connectorDebug = array();
                                $connectorDebug['instance_id'] = $result['instanceId'];
                                $connectorDebug['status'] = $result['sync_status'];
                                $connectorDebug['status_msg'] = "Product " . $productid . "  " . $result['exception_full_desc'];
                                Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
                                $finalresult[$k] = $result;
                            } else {
                                $productId = $singledata['HeaderDetails']['obj_id'];
                                $productData = $singledata['ItemDetails'];
                                $mbiz_id = $singledata['HeaderDetails']['mbiz_obj_id'];

                                unset($productData['attribute_set_id']);
                                unset($productData['product_id']);

                                if (array_key_exists('price', $productData)) {
                                    $price_includes_tax = Mage::getStoreConfig('tax/calculation/price_includes_tax');
                                    if ($productData['api_web_price_tax_setting'] != $price_includes_tax) {
                                        unset($productData['price']);
                                        if (array_key_exists('store_price', $productData)) {
                                            unset($productData['store_price']);
                                        }
                                        $prdSyncStatus = 1;
                                    }

                                }
                                try {
                                    //unset($productData['tax_class_id']);
                                    $skuFlag = false;
                                    $isProductExists = Mage::getModel('catalog/product')->load($productId);

                                    if (isset($productData['sku']) && $productData['sku']) {
                                        $sku = $productData['sku'];
                                        $idBySku = Mage::getModel('catalog/product')->getIdBySku($sku);
                                        $skuFlag = ($idBySku && ($idBySku != $productId)) ? true : false;
                                        $exceptionDescription = 'Product already exists(Product Id:' . $idBySku . ') with this SKU';
                                    }
                                    $productDeletedFlag = false;
                                    if (!(is_object($isProductExists) && $isProductExists->getId())) {
                                        $productDeletedFlag = true;
                                        $exceptionDescription = 'Product Already Deleted In Magento';
                                    }


                                    if ($skuFlag || $productDeletedFlag) {
                                        $result['sync_status'] = 'Failed';
                                        $result['exception_desc'] = $exceptionDescription;
                                        $result['exception_full_desc'] = $exceptionDescription;
                                        $result['exception_id'] = '';
                                    } else {
                                        $productupdated = Mage::getModel('Microbiz_Connector_Model_Product_Api')->update($productId, $productData, $store = null, $identifierType = null);
                                        $relationinfo['magento_id'] = $productId;
                                        $relationinfo['mbiz_id'] = $mbiz_id;
                                        $relationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                                        $checkObjectRelation = Mage::helper('microbiz_connector')->checkObjectRelation($productId, $modelname);

                                        if (!$checkObjectRelation) {
                                            $relationinfo['mbiz_version_number'] = $singledata['HeaderDetails']['mbiz_version_number'];
                                            $relationinfo['last_updated_from'] = 'MBIZ';
                                            $relationinfo['modified_by'] = $user;
                                            $relationinfo['modified_at'] = Now();
                                            if (array_key_exists('sku', $singledata['ItemDetails'])) {
                                                $mbizSku = $singledata['ItemDetails']['sku'];
                                                $relationinfo['mbiz_sku'] = $mbizSku;
                                            }
                                            Mage::getModel('mbizproduct/mbizproduct')->setData($relationinfo)->save();

                                        } else {
                                            $mbizVerNo = $singledata['HeaderDetails']['mbiz_version_number'];
                                            Mage::log("came to update prd rel", null, 'prdrel.log');
                                            Mage::log($mbizVerNo, null, 'prdrel.log');
                                            Mage::log($modelname, null, 'prdrel.log');

                                            $prdRelData = Mage::helper('microbiz_connector')->checkIsObjectExists($productId, $modelname);
                                            Mage::log($prdRelData, null, 'prdrel.log');
                                            if (!empty($prdRelData)) {
                                                $Id = $prdRelData['id'];
                                                $prdModel = Mage::getModel('mbizproduct/mbizproduct')->load($Id);

                                                $prdModel->setMbizVersionNumber($mbizVerNo);
                                                //$prdModel->setMageVersionNumber($prdRelData['mage_version_number']+1);
                                                $prdModel->setLastUpdateFrom('MBIZ');
                                                $prdModel->setModifiedBy($user);
                                                $prdModel->setModifiedAt(Now());
                                                if (array_key_exists('sku', $singledata['ItemDetails'])) {
                                                    $mbizSku = $singledata['ItemDetails']['sku'];
                                                    $prdModel->setMbizSku($mbizSku);
                                                }
                                                $prdModel->setId($Id)->save();
                                            }
                                        }

                                        //associated Configurable product versions updating code
                                        $associatedConfigurableProductVersions = $singledata['HeaderDetails']['associated_configurable_products_versions'];
                                        if (!empty($associatedConfigurableProductVersions)) {
                                            foreach ($associatedConfigurableProductVersions as $key => $configProductRel) {
                                                $prdRelData = Mage::getModel('mbizproduct/mbizproduct')->getCollection()
                                                    ->addFieldToFilter('mbiz_id', $key)->setOrder('id', 'asc')->getFirstItem()->getData();

                                                if (!empty($prdRelData)) {
                                                    $Id = $prdRelData['id'];
                                                    $prdModel = Mage::getModel('mbizproduct/mbizproduct')->load($Id);

                                                    $prdModel->setMbizVersionNumber($configProductRel['mbiz_version_number']);

                                                    $prdModel->setId($Id)->save();
                                                }
                                            }
                                        }
                                        // if (isset($singledata['HeaderDetails']['associated_configurable_products'])) {

                                        $associated_configurable_products = $singledata['HeaderDetails']['associated_configurable_products'];
                                        $configMsg = Mage::helper('microbiz_connector')->saveSimpleConfig($productId, $associated_configurable_products);

                                        // }
                                        $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                        $result['obj_id'] = $productId;
                                        $result['mbiz_obj_id'] = $mbiz_id;
                                        if (!$prdSyncStatus) {
                                            $result['sync_status'] = 'Completed';
                                        } else {
                                            $result['sync_status'] = 'Failed';
                                            $result['exception_des'] = 'Product Updated but Price is not Updated due to Missmatch in Catalog Price Includes Tax Setting ';
                                        }

                                        $prdRelData = Mage::helper('microbiz_connector')->checkIsObjectExists($productId, $modelname);

                                        if (!empty($prdRelData)) {
                                            $result['mage_version_number'] = $prdRelData['mage_version_number'];
                                            $result['mbiz_version_number'] = $prdRelData['mbiz_version_number'];

                                        }

                                        if (is_array($configMsg)) {
                                            $result['sync_status'] = 'Failed';
                                            $result['exception_desc'] = 'Product Created but unable to save configurable product Associations. ' . $configMsg['exception_desc'];
                                            $result['exception_id'] = '';
                                        }
                                    }

                                } catch (Zend_Db_Statement_Exception $ex) {
                                    $result['sync_status'] = 'Pending';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_full_desc'] = $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (PDOException $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (Mage_Api_Exception $ex) {
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage() . '  ' . $ex->getCustomMessage();
                                    $result['exception_full_desc'] = $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (Exception $ex) {
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage();
                                    $result['exception_full_desc'] = $ex->getMessage();
                                    $result['exception_id'] = $ex->getCode();
                                }
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $productId;
                                $result['mbiz_obj_id'] = $mbiz_id;


                                $connectorDebug = array();
                                $connectorDebug['instance_id'] = $result['instanceId'];
                                $connectorDebug['status'] = $result['sync_status'];
                                $connectorDebug['status_msg'] = "Product " . $productId . "  " . $result['exception_full_desc'];
                                Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
                                $finalresult[$k] = $result;
                            }
                        }
                        break;
                    case 'Customer':
                        $customerid = '';
                        if ($singledata['HeaderDetails']['obj_status'] == 2) {
                            $customerId = $singledata['HeaderDetails']['obj_id'];
                            if (empty($customerId)) {
                                $relationdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter('mbiz_id', $singledata['HeaderDetails']['mbiz_obj_id'])->setOrder('id', 'asc')->getData();
                                $customerId = $relationdata[0]['magento_id'];
                            }
                            if ($customerId) {
                                Mage::helper('microbiz_connector')->deleteAppRelation($customerId, 'Customer');
                                $re_customer = Mage::getModel('customer/customer')->load($customerId);
                                $addressarray = array();
                                foreach ($re_customer->getAddresses() as $address) {
                                    $data = $address->toArray();
                                    $addressarray[] = $data['entity_id'];
                                }
                                foreach ($addressarray as $addressid) {
                                    Mage::helper('microbiz_connector')->deleteAppRelation($addressid, 'CustomerAddressMaster');
                                }
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $customerId;
                                $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $result['sync_status'] = 'Completed';
                            } else {
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $customerId;
                                $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $result['sync_status'] = 'Failed';
                                $result['exception_desc'] = "Not exists";
                                $result['exception_id'] = 101;
                            }
                            $connectorDebug = array();
                            $connectorDebug['instance_id'] = $result['instanceId'];
                            $connectorDebug['status'] = $result['sync_status'];
                            $connectorDebug['status_msg'] = "Customer " . $customerId . "  " . $result['exception_desc'];
                            Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
                            $finalresult[$k] = $result;
                        } else {
                            if (empty($singledata['HeaderDetails']['obj_id'])) {
                                $customerid = '';
                                $mbiz_id = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $relationmbizcustomerdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter('mbiz_id', $mbiz_id)->setOrder('id', 'asc')->getData();
                                if (count($relationmbizcustomerdata)) {
                                    $customerid = $relationmbizcustomerdata[0]['magento_id'];
                                }
                                $customerinfo = $singledata['ItemDetails'];

                                try {
                                    if (!$customerid) {
                                        $customer_email = $customerinfo['email'];
                                        $collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*')->addAttributeToFilter('email', $customer_email);
                                        foreach ($collection as $customer) {
                                            $customerarray = $customer->toArray();
                                        }
                                        $customerid = $customerarray['entity_id'];
                                    }
                                    if ($customerid) {
                                        Mage::getModel('Mage_Customer_Model_Customer_Api')->update($customerid, $customerinfo);
                                    } else {
                                        $singledata['ItemDetails']['created_at'] = date("Y-m-d H:m:s");
                                        $singledata['ItemDetails']['website_id'] = Mage::getStoreConfig('connector/defaultwebsite/customer');
                                        $singledata['ItemDetails']['sync_cus_create'] = 1;
                                        $singledata['ItemDetails']['sync_status'] = 1;
                                        $customerid = Mage::getModel('Microbiz_Connector_Model_Customer_Api')->create($singledata['ItemDetails']);
                                    }
                                    $relationinfo['magento_id'] = $customerid;
                                    $relationinfo['mbiz_id'] = $mbiz_id;
                                    $result['obj_id'] = $customerid;

                                    $relationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                                    $checkObjectRelation = Mage::helper('microbiz_connector')->checkObjectRelation($customerid, $modelname);

                                    if (!$checkObjectRelation) {
                                        Mage::getModel('mbizcustomer/mbizcustomer')->setData($relationinfo)->save();

                                    }
                                    $result['sync_status'] = 'Completed';
                                } catch (Zend_Db_Statement_Exception $ex) {
                                    $result['status'] = 'Pending';
                                    $result['sync_status'] = 'Pending';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (PDOException $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (Mage_Api_Exception $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (Exception $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage();
                                    $result['exception_id'] = $ex->getCode();
                                }

                                $result['mbiz_obj_id'] = $mbiz_id;
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];

                                $connectorDebug = array();
                                $connectorDebug['instance_id'] = $result['instanceId'];
                                $connectorDebug['status'] = $result['sync_status'];
                                $connectorDebug['status_msg'] = "Customer with Mbiz id" . $mbiz_id . "  " . $result['exception_desc'];
                                Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
                                $finalresult[$k] = $result;
                            } else {
                                $customerId = $singledata['HeaderDetails']['obj_id'];
                                $customerinfo = $singledata['ItemDetails'];
                                $mbiz_id = $singledata['HeaderDetails']['mbiz_obj_id'];
                                try {
                                    $customerid = Mage::getModel('Mage_Customer_Model_Customer_Api')->update($customerId, $customerinfo);
                                    $relationinfo['magento_id'] = $customerId;
                                    $relationinfo['mbiz_id'] = $mbiz_id;
                                    $relationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                                    $checkObjectRelation = Mage::helper('microbiz_connector')->checkObjectRelation($customerId, $modelname);
                                    $result['sync_status'] = 'Completed';

                                    if (!$checkObjectRelation) {
                                        Mage::getModel('mbizcustomer/mbizcustomer')->setData($relationinfo)->save();

                                    }
                                } catch (Zend_Db_Statement_Exception $ex) {
                                    $result['status'] = 'Pending';
                                    $result['sync_status'] = 'Pending';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (PDOException $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (Mage_Api_Exception $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (Exception $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage();
                                    $result['exception_id'] = $ex->getCode();
                                }


                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $customerId;
                                $result['mbiz_obj_id'] = $mbiz_id;
                                $connectorDebug = array();
                                $connectorDebug['instance_id'] = $result['instanceId'];
                                $connectorDebug['status'] = $result['sync_status'];
                                $connectorDebug['status_msg'] = "Customer with Mbiz id" . $mbiz_id . "  " . $result['exception_desc'];
                                Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
                                $finalresult[$k] = $result;
                            }
                        }
                        break;
                    case 'CustomerAddressMaster':
                        $customeraddressidid = '';

                        if ($singledata['HeaderDetails']['obj_status'] == 2) {
                            $customeraddressId = $singledata['HeaderDetails']['obj_id'];
                            if (empty($customeraddressId)) {
                                $relationdata = Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->getCollection()->addFieldToFilter('mbiz_id', $singledata['HeaderDetails']['mbiz_obj_id'])->setOrder('id', 'asc')->getData();
                                $customeraddressId = $relationdata[0]['magento_id'];
                            }
                            if ($customeraddressId) {
                                Mage::helper('microbiz_connector')->deleteAppRelation($customeraddressId, 'CustomerAddressMaster');
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $customeraddressId;
                                $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $result['sync_status'] = 'Completed';
                            } else {
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $customeraddressId;
                                $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $result['sync_status'] = 'Failed';
                                $result['exception_desc'] = "Customer Not exists";
                                $result['exception_id'] = 101;
                            }
                            $connectorDebug = array();
                            $connectorDebug['instance_id'] = $result['instanceId'];
                            $connectorDebug['status'] = $result['sync_status'];
                            $connectorDebug['status_msg'] = "Customer with " . $customeraddressId . "  " . $result['exception_desc'];
                            Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
                            $finalresult[$k] = $result;
                        } else {
                            if (empty($singledata['HeaderDetails']['obj_id'])) {
                                $customerId = $singledata['HeaderDetails']['ref_obj_id'];
                                $mbiz_ref_obj_id = $singledata['HeaderDetails']['mbiz_ref_obj_id'];
                                if (!$customerId) {
                                    $relationcustomerdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter('mbiz_id', $mbiz_ref_obj_id)->setOrder('id', 'asc')->getData();
                                    $customerId = $relationcustomerdata[0]['magento_id'];
                                }
                                $customerAddressData = $singledata['ItemDetails'];
                                $region = Mage::getModel('directory/region')->getCollection()->addFieldToFilter('country_id', $customerAddressData['country'])->addFieldToFilter('code', $customerAddressData['state'])->getData();
                                if (count($region)) {
                                    $customerAddressData['region_id'] = $region[0]['region_id'];
                                } else {
                                    $customerAddressData['region'] = $customerAddressData['state'];
                                }
                                $mbiz_id = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $customerAddressData['street'] = array(
                                    $customerAddressData['address'],
                                    $customerAddressData['address2']
                                );
                                $customerAddressData['country_id'] = $customerAddressData['country'];
                                $customerAddressData['company'] = $customerAddressData['company_name'];
                                $customerAddressData['postcode'] = $customerAddressData['zipcode'];
                                $customerAddressData['telephone'] = $customerAddressData['phone'];
                                try {

                                    $customeraddressidid = Mage::getModel('Mage_Customer_Model_Address_Api')->create($customerId, $customerAddressData);


                                    $relationinfo['magento_id'] = $customeraddressidid;
                                    $relationinfo['mbiz_id'] = $mbiz_id;
                                    $relationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                                    $checkObjectRelation = Mage::helper('microbiz_connector')->checkObjectRelation($customeraddressidid, $modelname);

                                    if (!$checkObjectRelation) {
                                        $model = Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->setData($relationinfo)->save();

                                    }
                                    $result['obj_id'] = $customeraddressidid;
                                    $result['sync_status'] = 'Completed';
                                } catch (Zend_Db_Statement_Exception $ex) {
                                    $result['status'] = 'Pending';
                                    $result['sync_status'] = 'Pending';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (PDOException $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (Mage_Api_Exception $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (Exception $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage();
                                    $result['exception_id'] = $ex->getCode();
                                }

                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];

                                $result['mbiz_obj_id'] = $mbiz_id;
                                $result['ref_obj_id'] = $customerId;
                                $result['mbiz_ref_obj_id'] = $mbiz_ref_obj_id;

                                $connectorDebug = array();
                                $connectorDebug['instance_id'] = $result['instanceId'];
                                $connectorDebug['status'] = $result['sync_status'];
                                $connectorDebug['status_msg'] = "Customer with Mbiz Id " . $mbiz_ref_obj_id . " and Address Id " . $mbiz_id . "  " . $result['exception_desc'];
                                Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
                                $finalresult[$k] = $result;
                            } else {
                                $mbiz_id = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $mbiz_ref_obj_id = $singledata['HeaderDetails']['mbiz_ref_obj_id'];
                                $addressId = $singledata['HeaderDetails']['obj_id'];
                                $addressData = $singledata['ItemDetails'];
                                $addressData['street'] = array(
                                    $addressData['address'],
                                    $addressData['address2']
                                );
                                $addressData['country_id'] = $addressData['country'];

                                $addressData['postcode'] = $addressData['zipcode'];
                                $addressData['telephone'] = $addressData['phone'];
                                $region = Mage::getModel('directory/region')->getCollection()->addFieldToFilter('country_id', $addressData['country'])->addFieldToFilter('code', $addressData['state'])->getData();
                                if (count($region)) {
                                    $addressData['region_id'] = $region[0]['region_id'];
                                } else {
                                    $addressData['region'] = $addressData['state'];
                                }
                                try {
                                    $customeraddressidid = Mage::getModel('Mage_Customer_Model_Address_Api')->update($addressId, $addressData);
                                    $relationinfo['magento_id'] = $addressId;
                                    $relationinfo['mbiz_id'] = $mbiz_id;
                                    $relationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                                    $checkObjectRelation = Mage::helper('microbiz_connector')->checkObjectRelation($addressId, $modelname);
                                    if (!$checkObjectRelation) {
                                        Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->setData($relationinfo)->save();

                                    }
                                    $result['sync_status'] = 'Completed';

                                } catch (Zend_Db_Statement_Exception $ex) {
                                    $result['status'] = 'Pending';
                                    $result['sync_status'] = 'Pending';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (PDOException $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (Mage_Api_Exception $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                    $result['exception_id'] = $ex->getCode();
                                } catch (Exception $ex) {
                                    $result['status'] = 'Failed';
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = $ex->getMessage();
                                    $result['exception_id'] = $ex->getCode();
                                }

                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $addressId;
                                $result['mbiz_obj_id'] = $mbiz_id;
                                $result['mbiz_ref_obj_id'] = $mbiz_ref_obj_id;

                                $connectorDebug = array();
                                $connectorDebug['instance_id'] = $result['instanceId'];
                                $connectorDebug['status'] = $result['sync_status'];
                                $connectorDebug['status_msg'] = "Customer with Mbiz Id " . $mbiz_ref_obj_id . " and Address Id " . $mbiz_id . "  " . $result['exception_desc'];
                                Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
                                $finalresult[$k] = $result;
                            }
                        }

                        break;
                    case 'ProductCategories':

                        if ($singledata['HeaderDetails']['obj_status'] == 2) {

                            $mbizCattId = $singledata['HeaderDetails']['mbiz_obj_id'];
                            $categoryRelationModel = Mage::getModel('mbizcategory/mbizcategory')
                                ->getCollection()
                                ->addFieldToFilter('mbiz_id', $mbizCattId)
                                ->setOrder('id', 'asc')
                                ->getFirstItem()->getData();
                            if (count($categoryRelationModel) > 0) {
                                $relationId = $categoryRelationModel['id'];
                                $magCatId = $categoryRelationModel['magento_id'];
                                $relationModel = Mage::getModel('mbizcategory/mbizcategory');
                                $relationModel->setId($relationId)->delete();
                                $category = Mage::getModel('catalog/category')->load($magCatId);
                                $category->delete();
                                //$result = array();
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = $magCatId;
                                $result['mbiz_obj_id'] = $mbizCattId;
                                $result['sync_status'] = 'Completed';
                                $result['exception_desc'] = "Category and Relation Removed Successfully";

                            } else {
                                //$result = array();
                                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                $result['obj_id'] = '';
                                $result['mbiz_obj_id'] = $mbizCattId;
                                $result['sync_status'] = 'Failed';
                                $result['exception_desc'] = "Category Relation not exists";
                            }

                            $finalresult[$k] = $result;

                        }

                        else {
                            if (empty($singledata['HeaderDetails']['obj_id'])) {
                                $mbizCattId = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $categoryData = $singledata['ItemDetails'];
                                $categoryRelationModel = Mage::getModel('mbizcategory/mbizcategory')
                                    ->getCollection()
                                    ->addFieldToFilter('mbiz_id', $mbizCattId)
                                    ->setOrder('id', 'asc')
                                    ->getData();

                                if (count($categoryRelationModel) > 0) {
                                    $categoryId = $categoryRelationModel[0]['magento_id'];
                                    try {
                                        $category = Mage::getModel('catalog/category')->load($categoryId);

                                        $MbizParentId = $categoryData['parent_id'];
                                        $magCurrentParentId = $category->getParentId();
                                        Mage::log($MbizParentId, null, 'syncproduct.log');
                                        $categoryParentRelationModel = Mage::getModel('mbizcategory/mbizcategory')
                                            ->getCollection()
                                            ->addFieldToFilter('mbiz_id', $MbizParentId)
                                            ->setOrder('id', 'asc')
                                            ->getFirstItem()->getData();
                                        Mage::log("category parent rel", null, 'syncproduct.log');
                                        Mage::log($categoryParentRelationModel, null, 'syncproduct.log');
                                        if (!empty($categoryParentRelationModel)) {
                                            $magParentId = $categoryParentRelationModel['magento_id'];
                                            if ($magCurrentParentId != $magParentId && $magParentId != $categoryId) {
                                                Mage::log("category is moved...", null, 'syncproduct.log');
                                                $categoryData['parent_id'] = $magParentId;
                                                $category = Mage::getModel('catalog/category')->load($categoryId);
                                                $category->move($magParentId, null);
                                            } else {
                                                Mage::log("category is updated", null, 'syncproduct.log');
                                                $categoryData['parent_id'] = $magCurrentParentId;
                                            }

                                        } else {
                                            Mage::log("category parent rel not exits", null, 'syncproduct.log');
                                            $categoryData['parent_id'] = $magCurrentParentId;
                                        }

                                        $categoryRelExists = Mage::getModel('mbizcategory/mbizcategory')
                                            ->getCollection()
                                            ->addFieldToFilter('magento_id', $categoryId)
                                            ->setOrder('id', 'asc')
                                            ->getFirstItem()->getData();
                                        if ($singledata['ItemDetails']['parent_id'] == 1 && !empty($categoryRelExists) && $magCurrentParentId != $MbizParentId) {
                                            Mage::log("sub category changed to root in mbiz ", null, 'syncproduct.log');
                                            $categoryData['sync_cat_create'] = '1';
                                            $category = Mage::getModel('catalog/category')->load($categoryId);
                                            $category->move($MbizParentId, null);
                                        }
                                        if ($singledata['ItemDetails']['parent_id'] == 1) {
                                            Mage::log("Root category Imported from mbiz", null, 'syncproduct.log');
                                            $categoryData['sync_cat_create'] = '1';
                                            $categoryData['parent_id'] = '1';
                                        }

                                        $OrigCatData = $category->getData();
                                        $updatedData = array_merge($OrigCatData, $categoryData);

                                        $postDataConfig = array();

                                        /*Code to Check the default avail_sort and default_sort for Cateogry and Update it accordngly starts here */

                                        $defaultSortCollection = Mage::getModel('Mage_Catalog_Model_Config')->getAttributeUsedForSortByArray();
                                        $defaultSortCollection = array_keys($defaultSortCollection);

                                        $defaultSortBy = $OrigCatData['default_sort_by'];
                                        $availableSortBy = $OrigCatData['available_sort_by'];

                                        //$updatedData['default_sort_by'] = $defaultSortBy;
                                        //$updatedData['available_sort_by'] = $availableSortBy;

                                        Mage::log("default sort collection ",null,'syncproduct.log');
                                        Mage::log($defaultSortCollection,null,'syncproduct.log');
                                        Mage::log($OrigCatData,null,'syncproduct.log');


                                        if($defaultSortBy) {
                                            if(!in_array($defaultSortBy,$defaultSortCollection)) {
                                                $updatedData['default_sort_by'] = false;
                                                $postDataConfig[] = 'default_sort_by';

                                            }
                                        }
                                        else {
                                            $updatedData['default_sort_by'] = false;
                                            $postDataConfig[] = 'default_sort_by';
                                        }


                                        if(!empty($availableSortBy)) {

                                            foreach($availableSortBy as $key=>$sortValue) {
                                                if(!in_array($sortValue,$defaultSortCollection)) {
                                                    unset($availableSortBy[$key]);
                                                }
                                            }

                                            if(empty($availableSortBy)) {
                                                $updatedData['available_sort_by'] = false;
                                                $postDataConfig[] = 'available_sort_by';
                                            }
                                            else {
                                                $updatedData['available_sort_by'] = $availableSortBy;
                                            }

                                        }
                                        else {
                                            $updatedData['available_sort_by'] = false;
                                            $postDataConfig[] = 'available_sort_by';
                                        }

                                        /*Code to Check the default avail_sort and default_sort for Cateogry and Update it accordngly ends here */


                                        if (!empty($postDataConfig)) {
                                            $updatedData['use_post_data_config'] = $updatedData['use_config'] = $postDataConfig;
                                        }

                                        if(array_key_exists('status',$updatedData)) {
                                            $updatedData['is_active'] = $updatedData['status'];
                                            unset($updatedData['status']);
                                        }

                                        $categoryid = Mage::getModel('Microbiz_Connector_Model_Category_Api')->update($categoryId, $updatedData);
                                        $mbizCattId = $singledata['HeaderDetails']['mbiz_obj_id'];
                                        $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                        $result['obj_id'] = $categoryId;
                                        $result['mbiz_obj_id'] = $mbizCattId;
                                        $result['sync_status'] = 'Completed';
                                    } catch (Zend_Db_Statement_Exception $ex) {
                                        $mbizCattId = $singledata['HeaderDetails']['mbiz_obj_id'];
                                        $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                        $categoryid = $categoryId;
                                        $result['obj_id'] = $categoryId;
                                        $result['mbiz_obj_id'] = $mbizCattId;
                                        $result['status'] = 'Pending';
                                        $result['sync_status'] = 'Pending';
                                        $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                        $result['exception_id'] = $ex->getCode();
                                    } catch (PDOException $ex) {
                                        $mbizCattId = $singledata['HeaderDetails']['mbiz_obj_id'];
                                        $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                        $categoryid = $categoryId;
                                        $result['obj_id'] = $categoryId;
                                        $result['mbiz_obj_id'] = $mbizCattId;
                                        $result['status'] = 'Failed';
                                        $result['sync_status'] = 'Failed';
                                        $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                        $result['exception_id'] = $ex->getCode();
                                    } catch (Mage_Api_Exception $ex) {
                                        $mbizCattId = $singledata['HeaderDetails']['mbiz_obj_id'];
                                        $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                        $categoryid = $categoryId;
                                        $result['obj_id'] = $categoryId;
                                        $result['mbiz_obj_id'] = $mbizCattId;
                                        $result['status'] = 'Failed';
                                        $result['sync_status'] = 'Failed';
                                        $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                        $result['exception_id'] = $ex->getCode();
                                    } catch (Exception $ex) {
                                        $mbizCattId = $singledata['HeaderDetails']['mbiz_obj_id'];
                                        $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                        $categoryid = $categoryId;
                                        $result['obj_id'] = $categoryId;
                                        $result['mbiz_obj_id'] = $mbizCattId;
                                        $result['status'] = 'Failed';
                                        $result['sync_status'] = 'Failed';
                                        $result['exception_desc'] = $ex->getMessage();
                                        $result['exception_id'] = $ex->getCode();
                                    }

                                    $connectorDebug = array();
                                    $connectorDebug['instance_id'] = $result['instanceId'];
                                    $connectorDebug['status'] = $result['sync_status'];
                                    $connectorDebug['status_msg'] = "Category with " . $categoryId . " " . $result['exception_desc'];
                                    Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
                                    $finalresult[$k] = $result;

                                } else {
                                    $mbizParentId = $singledata['ItemDetails']['parent_id'];
                                    if ($mbizParentId > 1) {
                                        $categoryRelationModel = Mage::getModel('mbizcategory/mbizcategory')
                                            ->getCollection()
                                            ->addFieldToFilter('mbiz_id', $mbizParentId)
                                            ->setOrder('id', 'asc')
                                            ->getData();
                                        if (count($categoryRelationModel) > 0) {
                                            $parentId = $categoryRelationModel[0]['magento_id'];
                                        } else {
                                            $parentId = 0;
                                        }
                                    } else {
                                        $parentId = $mbizParentId;
                                    }

                                    if ($parentId >= 1) {
                                        try {
                                            $categoryData['is_active'] = 1;
                                            $categoryData['include_in_menu'] = 1;
                                            $categoryData['available_sort_by'] = Mage::getStoreConfig('catalog/frontend/default_sort_by');
                                            $categoryData['default_sort_by'] = Mage::getStoreConfig('catalog/frontend/default_sort_by');
                                            if ($parentId == 1) {
                                                $categoryData['sync_cat_create'] = '1';
                                            }

                                            if(array_key_exists('status',$categoryData)) {
                                                $categoryData['is_active'] = $categoryData['status'];
                                                unset($categoryData['status']);
                                            }
                                            $categoryid = Mage::getModel('Microbiz_Connector_Model_Category_Api')->createCategory($parentId, $categoryData);
                                            $categoryRelationData['instance_id'] = 1;
                                            $categoryRelationData['magento_id'] = $categoryid;
                                            $categoryRelationData['mbiz_id'] = $mbizCattId;
                                            $categoryRelationData['is_inventory_category'] = 0;

                                            Mage::getModel('mbizcategory/mbizcategory')->setData($categoryRelationData)->save();
                                            $result['obj_id'] = $categoryid;
                                            $result['mbiz_obj_id'] = $mbizCattId;
                                            $result['sync_status'] = 'Completed';

                                        } catch (Zend_Db_Statement_Exception $ex) {
                                            $result['status'] = 'Pending';
                                            $result['sync_status'] = 'Pending';
                                            $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                            $result['exception_id'] = $ex->getCode();
                                        } catch (PDOException $ex) {
                                            $result['status'] = 'Failed';
                                            $result['sync_status'] = 'Failed';
                                            $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                            $result['exception_id'] = $ex->getCode();
                                        } catch (Mage_Api_Exception $ex) {
                                            $result['status'] = 'Failed';
                                            $result['sync_status'] = 'Failed';
                                            $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                            $result['exception_id'] = $ex->getCode();
                                        } catch (Exception $ex) {
                                            $result['status'] = 'Failed';
                                            $result['sync_status'] = 'Failed';
                                            $result['exception_desc'] = $ex->getMessage();
                                            $result['exception_id'] = $ex->getCode();
                                        }

                                        $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                        $result['obj_id'] = $categoryid;
                                    } else {
                                        $mbizCattId = $singledata['HeaderDetails']['mbiz_obj_id'];
                                        $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                        $result['obj_id'] = '';
                                        $result['mbiz_obj_id'] = $mbizCattId;
                                        $result['sync_status'] = 'Failed';
                                        $result['exception_desc'] = "Unable to Create Category in Magento no Parent Relation Exists.";
                                        $result['exception_id'] = 0;
                                    }
                                    $connectorDebug = array();
                                    $connectorDebug['instance_id'] = $result['instanceId'];
                                    $connectorDebug['status'] = $result['sync_status'];
                                    $connectorDebug['status_msg'] = "Category with " . $categoryid . " " . $result['exception_desc'];
                                    Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
                                    $finalresult[$k] = $result;
                                }

                            } else {
                                $categoryId = $singledata['HeaderDetails']['obj_id'];
                                $categoryData = $singledata['ItemDetails'];
                                $mbizCattId = $singledata['HeaderDetails']['mbiz_obj_id'];
                                $category = Mage::getModel('catalog/category')->load($categoryId);
                                $isCatExists = $category->getId();
                                if ($isCatExists != '') {
                                    try {


                                        $MbizParentId = $categoryData['parent_id'];
                                        $magCurrentParentId = $category->getParentId();
                                        Mage::log("came to category api edit", null, 'syncproduct.log');
                                        Mage::log("category id " . $categoryId, null, 'syncproduct.log');
                                        Mage::log("category mag parent id " . $magCurrentParentId, null, 'syncproduct.log');
                                        Mage::log("category mbiz parent id " . $MbizParentId, null, 'syncproduct.log');
                                        $categoryParentRelationModel = Mage::getModel('mbizcategory/mbizcategory')
                                            ->getCollection()
                                            ->addFieldToFilter('mbiz_id', $MbizParentId)
                                            ->setOrder('id', 'asc')
                                            ->getFirstItem()->getData();
                                        Mage::log("category parent rel", null, 'syncproduct.log');
                                        Mage::log($categoryParentRelationModel, null, 'syncproduct.log');
                                        if (!empty($categoryParentRelationModel)) {
                                            $magParentId = $categoryParentRelationModel['magento_id'];
                                            if ($magCurrentParentId != $magParentId && $magParentId != $categoryId) {
                                                Mage::log("category is moved...", null, 'syncproduct.log');
                                                $categoryData['parent_id'] = $magParentId;
                                                $category = Mage::getModel('catalog/category')->load($categoryId);
                                                $category->move($magParentId, null);
                                                //Mage::getModel('Mage_Catalog_Model_Category_Api')->move($categoryId, $magParentId);
                                            } else {
                                                Mage::log("category is updated", null, 'syncproduct.log');
                                                $categoryData['parent_id'] = $magCurrentParentId;
                                            }

                                        } else {
                                            Mage::log("category parent rel not exits", null, 'syncproduct.log');
                                            $categoryData['parent_id'] = $magCurrentParentId;
                                        }

                                        $categoryRelExists = Mage::getModel('mbizcategory/mbizcategory')
                                            ->getCollection()
                                            ->addFieldToFilter('magento_id', $categoryId)
                                            ->setOrder('id', 'asc')
                                            ->getFirstItem()->getData();
                                        if ($singledata['ItemDetails']['parent_id'] == 1 && !empty($categoryRelExists) && $magCurrentParentId != $MbizParentId) {
                                            Mage::log("sub category changed to root in mbiz ", null, 'syncproduct.log');
                                            $categoryData['sync_cat_create'] = '1';
                                            $category = Mage::getModel('catalog/category')->load($categoryId);
                                            $category->move($MbizParentId, null);
                                            //Mage::getModel('Mage_Catalog_Model_Category_Api')->move($categoryId, $MbizParentId);
                                        }
                                        if ($singledata['ItemDetails']['parent_id'] == 1) {
                                            Mage::log("Root category Imported from mbiz", null, 'syncproduct.log');
                                            $categoryData['sync_cat_create'] = '1';
                                            $categoryData['parent_id'] = '1';
                                        }
                                        $OrigCatData = $category->getData();
                                        $updatedData = array_merge($OrigCatData, $categoryData);

                                        $postDataConfig = array();


                                        /*Code to Check the default avail_sort and default_sort for Cateogry and Update it accordngly starts here */

                                        $defaultSortCollection = Mage::getModel('Mage_Catalog_Model_Config')->getAttributeUsedForSortByArray();
                                        $defaultSortCollection = array_keys($defaultSortCollection);

                                        $defaultSortBy = $OrigCatData['default_sort_by'];
                                        $availableSortBy = $OrigCatData['available_sort_by'];

                                        Mage::log("default sort collection ",null,'syncproduct.log');
                                        Mage::log($defaultSortCollection,null,'syncproduct.log');
                                        Mage::log($OrigCatData,null,'syncproduct.log');


                                        //$updatedData['default_sort_by'] = $defaultSortBy;
                                        //$updatedData['available_sort_by'] = $availableSortBy;

                                        if($defaultSortBy) {
                                            if(!in_array($defaultSortBy,$defaultSortCollection)) {
                                                $updatedData['default_sort_by'] = false;
                                                $postDataConfig[] = 'default_sort_by';

                                            }
                                        }
                                        else {
                                            $updatedData['default_sort_by'] = false;
                                            $postDataConfig[] = 'default_sort_by';
                                        }

                                        if(!empty($availableSortBy)) {

                                            foreach($availableSortBy as $key=>$sortValue) {
                                                if(!in_array($sortValue,$defaultSortCollection)) {
                                                    unset($availableSortBy[$key]);
                                                }
                                            }

                                            if(empty($availableSortBy)) {
                                                $updatedData['available_sort_by'] = false;
                                                $postDataConfig[] = 'available_sort_by';
                                            }
                                            else {
                                                $updatedData['available_sort_by'] = $availableSortBy;
                                            }

                                        }
                                        else {
                                            $updatedData['available_sort_by'] = false;
                                            $postDataConfig[] = 'available_sort_by';
                                        }

                                        /*Code to Check the default avail_sort and default_sort for Cateogry and Update it accordngly ends here */

                                        if (!empty($postDataConfig)) {
                                            $updatedData['use_post_data_config'] = $updatedData['use_config'] = $postDataConfig;
                                        }
                                        if(array_key_exists('status',$updatedData)) {
                                            $updatedData['is_active'] = $updatedData['status'];
                                            unset($updatedData['status']);
                                        }
                                        Mage::log($updatedData, null, 'syncproduct.log');
                                        Mage::log($categoryId, null, 'syncproduct.log');
                                        $categoryid = Mage::getModel('Microbiz_Connector_Model_Category_Api')->update($categoryId, $updatedData);
                                        if ($categoryid) {
                                            $cateRelModel = Mage::getModel('mbizcategory/mbizcategory')->getCollection()
                                                ->addFieldToFilter('magento_id', $categoryId)
                                                ->setOrder('id', 'asc')->getFirstItem()->getData();
                                            $newrelationinfo['magento_id'] = $categoryId;
                                            $newrelationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                                            $newrelationinfo['mbiz_id'] = $mbizCattId;
                                            $newrelationinfo['is_inventory_category'] = 0;

                                            if (!$cateRelModel) {
                                                Mage::getModel('mbizcategory/mbizcategory')->setData($newrelationinfo)->save();
                                            } else {
                                                if ($cateRelModel['id']) {
                                                    Mage::getModel('mbizcategory/mbizcategory')->setId($cateRelModel['id'])->setMbizId($mbizCattId)->save();
                                                }

                                            }
                                        }
                                        $result['obj_id'] = $categoryId;
                                        $result['mbiz_obj_id'] = $mbizCattId;
                                        $result['sync_status'] = 'Completed';
                                    } catch (Zend_Db_Statement_Exception $ex) {
                                        $result['status'] = 'Pending';
                                        $result['sync_status'] = 'Pending';
                                        $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                        $result['exception_id'] = $ex->getCode();
                                    } catch (PDOException $ex) {
                                        $result['status'] = 'Failed';
                                        $result['sync_status'] = 'Failed';
                                        $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                        $result['exception_id'] = $ex->getCode();
                                    } catch (Mage_Api_Exception $ex) {
                                        $result['status'] = 'Failed';
                                        $result['sync_status'] = 'Failed';
                                        $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                                        $result['exception_id'] = $ex->getCode();
                                    } catch (Exception $ex) {
                                        $result['status'] = 'Failed';
                                        $result['sync_status'] = 'Failed';
                                        $result['exception_desc'] = $ex->getMessage();
                                        $result['exception_id'] = $ex->getCode();
                                    }


                                    $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                                    $connectorDebug = array();
                                    $connectorDebug['instance_id'] = $result['instanceId'];
                                    $connectorDebug['status'] = $result['sync_status'];
                                    $connectorDebug['status_msg'] = "Category with " . $categoryId . " " . $result['exception_desc'];
                                    Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
                                    $finalresult[$k] = $result;

                                } else {
                                    $result['sync_status'] = 'Failed';
                                    $result['exception_desc'] = 'Category No Longer Exists';
                                    $result['obj_id'] = $categoryId;
                                    $result['mbiz_obj_id'] = $mbizCattId;
                                    $finalresult[$k] = $result;

                                }
                            }
                        }

                        break;
                    case 'ProductInventory' :
                        Mage::helper('microbiz_connector')->createMbizSyncStatus($k, 'Pending');
                        $result = $this->mbizCreateProductInventory($singledata);
                        $finalresult[$k] = $result;
                        break;
                }
                Mage::helper('microbiz_connector')->createMbizSyncStatus($k, 'Completed');
            } else {
                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                $result['obj_id'] = ($singledata['HeaderDetails']['obj_id']) ? $singledata['HeaderDetails']['obj_id'] : Mage::helper('microbiz_connector')->getObjectRelation($singledata['HeaderDetails']['mbiz_obj_id'], $modelname, 'MicroBiz');
                $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                $result['sync_status'] = $syncMbizStatusData['sync_status'];
                $result['exception_desc'] = $syncMbizStatusData['status_desc'];
                $result['exception_id'] = '';
                $finalresult[$k] = $result;
            }
        }
        $headerIds = array_keys($finalresult);
        $mbizSyncUpdateModel = Mage::getModel('syncmbizstatus/syncmbizstatus')->getCollection()->addFieldToFilter('sync_header_id', array('in' => $headerIds));
        $mbizSyncUpdateModel->walk('delete');
        return json_encode($finalresult);

    }

    public function mbizCreateProductInventory($inventory)
    {
        $materialId = $inventory['HeaderDetails']['ref_obj_id'];
        $mbizmaterialId = $inventory['HeaderDetails']['mbiz_ref_obj_id'];
        if (empty($materialId)) {
            $magentorelation = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter('mbiz_id', $mbizmaterialId)->getData();
            $materialId = $magentorelation[0]['magento_id'];
        }
        $data = $inventory['ItemDetails'];
        $data['material_id'] = $materialId;
        $data['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
        $isProductExists = Mage::getModel('catalog/product')->load($materialId);
        if (!count($inventory['ItemDetails']) || !(is_object($isProductExists) && $isProductExists->getId())) {
            $result['instanceId'] = $inventory['HeaderDetails']['instanceId'];
            $result['ref_obj_id'] = $materialId;
            $result['mbiz_obj_id'] = $inventory['HeaderDetails']['mbiz_obj_id'];
            $result['sync_status'] = 'Failed';
            $result['status'] = 'Failed';
            $result['exception_desc'] = (!count($inventory['ItemDetails'])) ? "Item Details are Empty" : "Product Not Exits With " . $materialId;
            $result['exception_id'] = '';

        } else {
            try {
                $data['stock_type'] = (isset($data['stock_type']) && $data['stock_type']) ? $data['stock_type'] : 1;
                $productInventory = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $data['material_id'])->addFieldToFilter('company_id', $data['company_id'])->addFieldToFilter('store_id', $data['store_id'])->addFieldToFilter('stock_type', $data['stock_type'])->getData();
                // if Not Create Store Inventory else update the inventory.
                if ($materialId) {
                    if (empty($productInventory)) {
                        $model = Mage::getModel('connector/storeinventory_storeinventory')->setData($data)->save();
                        $inventoryId = $model->getId();
                    } else {
                        $inventoryId = $productInventory[0]['storeinventory_id']; //assinging id into inventoryId
                        $model = Mage::getModel('connector/storeinventory_storeinventory')->load($inventoryId)->setData($data); //setting the inventory data based on stock inventory ID
                        $model->setId($inventoryId)->save(); //saving the model
                    }
                    // Check if store exists, create/update store in the magento
                    $storemodel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->getCollection()->addFieldToFilter('company_id', $data['company_id'])->addFieldToFilter('store_id', $data['store_id'])->getData();

                    if (!count($storemodel)) {
                        $storeinformation = array();
                        $storeinformation['store_id'] = $data['store_id'];
                        $storeinformation['company_id'] = $data['company_id'];
                        $storeinformation['store_name'] = $data['store_name'];
                        $storeinformation['company_name'] = $data['company_name'];
                        $storeinformation['store_short_name'] = $data['store_short_name'];
                        $storeinformation['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                        Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->setData($storeinformation)->save();
                    }
                    $materialid = $data['material_id'];

                    /*$productTotalInventory = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $data['material_id'])->getData();
                    $qtyval                = 0;
                    foreach ($productTotalInventory as $pinventory) {
                        if ($pinventory['stock_type'] == 1) {
                            $qtyval = $qtyval + $pinventory['quantity'];
                        }
                    }
                    $stockItem   = Mage::getModel('cataloginventory/stock_item')->loadByProduct($materialid);


                    $stockItem->setData('qty', $qtyval);
                    if ($qtyval > 0) {
                        $stockItem->setData('is_in_stock', '1');
                    }

                    $stockItem->save();*/
                    $storemodel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->
                        getCollection()->addFieldToFilter('include_inventory', 1)->getColumnValues('store_id');


                    $batchsize = Mage::helper('microbiz_connector')->getBatchSize();
                    $productTotalInventory = Mage::getModel('connector/storeinventory_storeinventory')
                        ->getCollection()->addFieldToFilter('store_id', array('in' => $storemodel))
                        ->addFieldToFilter('material_id', $materialid)
                        ->setPageSize($batchsize);
                    $productTotalInventory->getSelect()->group('material_id');

                    if (count($productTotalInventory) > 0) {
                        foreach ($productTotalInventory as $inventory) {
                            $materialId = $inventory->getMaterialId();
                            $productTotalInventoryData = Mage::getModel('connector/storeinventory_storeinventory')
                                ->getCollection()->addFieldToFilter('store_id', array('in' => $storemodel))
                                ->addFieldToFilter('material_id', $materialId)->getColumnValues('quantity');

                            $qty = array_sum($productTotalInventoryData);
                            $isProductExists = Mage::getModel('catalog/product')->load($materialId);
                            if ((is_object($isProductExists) && $isProductExists->getId())) {
                                //$qty = $inventory->getQuantity();

                                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($materialId);

                                $stockItem->setData('qty', $qty);
                                if ($qty > 0) {
                                    $stockItem->setData('is_in_stock', '1');
                                }

                                $stockItem->save();
                            }

                        }
                    }
                    $result['status'] = 'Completed';
                    $result['sync_status'] = 'Completed';
                } else {
                    $result['status'] = 'Failed';
                    $result['sync_status'] = 'Failed';
                    $result['exception_desc'] = "Product Not Exists. No Product Relation With MIcroBiz ID " . $mbizmaterialId;
                    $result['exception_id'] = 0;
                }

            } catch (Zend_Db_Statement_Exception $ex) {
                $result['status'] = 'Pending';
                $result['sync_status'] = 'Pending';
                $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                $result['exception_id'] = $ex->getCode();
            } catch (PDOException $ex) {
                $result['status'] = 'Pending';
                $result['sync_status'] = 'Pending';
                $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                $result['exception_id'] = $ex->getCode();
            } catch (Mage_Api_Exception $ex) {
                $result['status'] = 'Failed';
                $result['sync_status'] = 'Failed';
                $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                $result['exception_id'] = $ex->getCode();
            } catch (Exception $ex) {
                $result['status'] = 'Failed';
                $result['sync_status'] = 'Failed';
                $result['exception_desc'] = $ex->getMessage();
                $result['exception_id'] = $ex->getCode();
            }
            $result['instanceId'] = $inventory['HeaderDetails']['instanceId'];
            $result['ref_obj_id'] = $materialId;
            $result['mbiz_obj_id'] = $inventory['HeaderDetails']['mbiz_obj_id'];
            $result['mbiz_ref_obj_id'] = $inventory['HeaderDetails']['mbiz_ref_obj_id'];

            $connectorDebug = array();
            $connectorDebug['instance_id'] = $result['instanceId'];
            $connectorDebug['status'] = $result['status'];
            $connectorDebug['status_msg'] = "Inventory for  " . $materialId . " with Mbiz Object Id " . $mbizmaterialId . "  " . $result['exception_desc'];
            Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
        }

        return $result;
    }

    /**
     * Update Product Stock for bulk products
     *
     * @param array of products Inventory information including stores
     * @return array of product ids which are Updated
     * @author KT097
     */
    public function extendedUpdateInventory($inventoryArray)
    {
        register_shutdown_function(array($this, 'mbizFatalErrorHandler'));
        $finalresult = array();
        $inventoryArray = json_decode($inventoryArray, true);
        ksort($inventoryArray);
        foreach ($inventoryArray as $k => $inventory) {
            $syncMbizStatusData = Mage::helper('microbiz_connector')->checkMbizSyncHeaderStatus($k);
            $inventoryId = "";
            $result = array();

            Mage::unregister('sync_microbiz_status_header_id');
            Mage::register('sync_microbiz_status_header_id', $k);
            $modelname = $inventory['HeaderDetails']['model'];
            if (!$syncMbizStatusData || $modelname == 'UpdateMagentoInventory') {
                Mage::helper('microbiz_connector')->createMbizSyncStatus($k, 'Pending');
                switch ($modelname) {
                    case 'ProductInventory':
                        $result = $this->mbizCreateProductInventory($inventory);
                        break;
                    case 'UpdateMagentoInventory':
                        $prevMaterialId = (isset($inventory['HeaderDetails']['ref_obj_id'])) ? $inventory['HeaderDetails']['ref_obj_id'] : 0;
                        try {
                            $storemodel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->getCollection()->addFieldToFilter('include_inventory', 1)->getColumnValues('store_id');
                            $batchsize = Mage::helper('microbiz_connector')->getBatchSize();
                            $productTotalInventory = Mage::getModel('connector/storeinventory_storeinventory')
                                ->getCollection()->addFieldToFilter('store_id', array('in' => $storemodel))
                                ->addFieldToFilter('material_id', array('gt' => $prevMaterialId))
                                ->setPageSize($batchsize);
                            $productTotalInventory->getSelect()->group('material_id');


                            if (count($productTotalInventory) > 0) {
                                foreach ($productTotalInventory as $inventory) {
                                    $materialId = $inventory->getMaterialId();
                                    $productTotalInventoryData = Mage::getModel('connector/storeinventory_storeinventory')
                                        ->getCollection()->addFieldToFilter('store_id', array('in' => $storemodel))
                                        ->addFieldToFilter('material_id', $materialId)->getColumnValues('quantity');

                                    $qty = array_sum($productTotalInventoryData);
                                    $isProductExists = Mage::getModel('catalog/product')->load($materialId);
                                    if ((is_object($isProductExists) && $isProductExists->getId())) {
                                        //$qty = $inventory->getQuantity();

                                        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($materialId);

                                        $stockItem->setData('qty', $qty);
                                        if ($qty > 0) {
                                            $stockItem->setData('is_in_stock', '1');
                                        }

                                        $stockItem->save();
                                    }

                                }
                                $result['instanceId'] = $inventory['HeaderDetails']['instanceId'];
                                $result['ref_obj_id'] = $materialId;
                                $result['mbiz_obj_id'] = $inventory['HeaderDetails']['mbiz_obj_id'];
                                $result['sync_status'] = 'Pending';
                                $result['status'] = 'Pending';
                                $result['exception_desc'] = 'All products Quantity Updated';
                                $result['exception_id'] = '';
                            } else {
                                $result['instanceId'] = $inventory['HeaderDetails']['instanceId'];
                                $result['ref_obj_id'] = '';
                                $result['mbiz_obj_id'] = $inventory['HeaderDetails']['mbiz_obj_id'];
                                $result['sync_status'] = 'Completed';
                                $result['status'] = 'Completed';
                                $result['exception_desc'] = 'All products Quantity Updated';
                                $result['exception_id'] = '';
                            }
                        } catch (Zend_Db_Statement_Exception $ex) {
                            $result['status'] = 'Pending';
                            $result['sync_status'] = 'Pending';
                            $result['ref_obj_id'] = $materialId;
                            $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                            $result['exception_id'] = $ex->getCode();
                        } catch (PDOException $ex) {
                            $result['status'] = 'Pending';
                            $result['sync_status'] = 'Pending';
                            $result['ref_obj_id'] = $materialId;
                            $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                            $result['exception_id'] = $ex->getCode();
                        } catch (Mage_Api_Exception $ex) {
                            $result['status'] = 'Pending';
                            $result['sync_status'] = 'Pending';
                            $result['ref_obj_id'] = $materialId;
                            $result['exception_desc'] = $ex->getMessage() . ' ' . $ex->getCustomMessage();
                            $result['exception_id'] = $ex->getCode();
                        } catch (Exception $ex) {
                            $result['status'] = 'Pending';
                            $result['sync_status'] = 'Pending';
                            $result['ref_obj_id'] = $materialId;
                            $result['exception_desc'] = $ex->getMessage();
                            $result['exception_id'] = $ex->getCode();
                        }

                        break;

                }
                Mage::helper('microbiz_connector')->createMbizSyncStatus($k, 'Completed');

            } else {
                $result['instanceId'] = $inventory['HeaderDetails']['instanceId'];
                $result['ref_obj_id'] = $inventory['HeaderDetails']['ref_obj_id'];
                $result['mbiz_obj_id'] = $inventory['HeaderDetails']['mbiz_obj_id'];
                $result['sync_status'] = ($syncMbizStatusData['sync_status'] == 'Completed') ? 'Completed' : 'Failed';
                $result['status'] = ($syncMbizStatusData['sync_status'] == 'Completed') ? 'Completed' : 'Failed';
                $result['exception_desc'] = $syncMbizStatusData['status_desc'];
                $result['exception_id'] = '';
            }
            $finalresult[$k] = $result;
        }
        $headerIds = array_keys($finalresult);
        $mbizSyncUpdateModel = Mage::getModel('syncmbizstatus/syncmbizstatus')->getCollection()->addFieldToFilter('sync_header_id', array('in' => $headerIds));
        $mbizSyncUpdateModel->walk('delete');
        return json_encode($finalresult);
    }

    /**
     * Save attribute set relation in relation tables
     *
     * @param array of attributeset relation information
     * @return true on succeess
     * @author KT097
     */
    public function saveAttributesetRelation($attributesetinfo)
    {

        $attributesetinfo = array(
            $attributesetinfo
        );
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {

            $user = Mage::getSingleton('admin/session');
            $user = $user->getUser()->getFirstname();

        } else if (Mage::getSingleton('api/session')) {
            $userApi = Mage::getSingleton('api/session');
            $user = $userApi->getUser()->getUsername();

        } else {
            $user = 'Guest';
        }
        foreach ($attributesetinfo as $attributeset) {
            $attributeset['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $attributeSetId = $attributeset['magento_id'];
            $checkAttributeSetRelation = Mage::helper('microbiz_connector')->checkObjectRelation($attributeSetId, 'AttributeSets');
            $attributeset['last_updated_from'] = 'MBIZ';
            $attributeset['modified_by'] = $user;
            $attributeset['modified_at'] = Now();
            $attributeset['mbiz_version_number'] = $attributeset['mbiz_version_number'];
            $currentAttributes = $attributeset['attributes'];
            $currentAttributeOptions = $attributeset['attribute_options'];
            unset($attributeset['attributes']);
            unset($attributeset['attribute_options']);

            if (!$checkAttributeSetRelation) {

                $model = Mage::getModel('mbizattributeset/mbizattributeset')->setData($attributeset)->save();
                $attributeset['status'] = "Attribute Set Relation Saved Successfully" . $attributeSetId;
            } else {
                $relationdata = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->addFieldToFilter('magento_id', $attributeSetId)->setOrder('id', 'asc')->getFirstItem();
                $id = $relationdata['id'];
                Mage::getModel('mbizattributeset/mbizattributeset')->load($id)->setMbizVersionNumber($attributeset['mbiz_version_number'])
                    ->setLastUpdatedFrom('MBIZ')->setModifiedBy($user)->setModifiedAt(Now())->setId($id)->save();
                $attributeset['status'] = "Attribute Set Relation Updated Successfully" . $attributeSetId;
            }
            $connectorDebug = array();
            $connectorDebug['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();

            $connectorDebug['status'] = "Completed";
            $connectorDebug['status_msg'] = $attributeset['status'];
            Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();

            /*Code to update the Attribute and Attirbute Options MicroBiz Version Number Starts Here*/
            if (!empty($currentAttributes)) {

                foreach ($currentAttributes as $Attribute) {
                    $attrModel = Mage::getModel('mbizattribute/mbizattribute');
                    $magAttrId = $Attribute['magento_id'];
                    if ($magAttrId) {
                        $attrData = $attrModel->getCollection()->addFieldToFilter('magento_id', $magAttrId)->setOrder('id', 'asc')->getFirstItem()->getData();
                    } else {
                        $mbizAttrid = $Attribute['mbiz_id'];
                        $attrData = $attrModel->getCollection()->addFieldToFilter('mbiz_id', $mbizAttrid)->setOrder('id', 'asc')->getFirstItem()->getData();
                    }

                    if (!empty($attrData)) {
                        $id = $attrData['id'];
                        $attrModel->load($id)->setMbizVersionNumber($Attribute['mbiz_version_number'])->setId($id)->save();
                    }


                }
            }

            if (!empty($currentAttributeOptions)) {
                foreach ($currentAttributeOptions as $AttributeOption) {
                    $attrOptModel = Mage::getModel('mbizattributeoption/mbizattributeoption');

                    $magAttrOptId = $AttributeOption['magento_id'];
                    if ($magAttrOptId) {
                        $attrOptData = $attrOptModel->getCollection()->addFieldToFilter('magento_id', $magAttrOptId)->setOrder('id', 'asc')->getFirstItem()->getData();
                    } else {
                        $mbizAttrOptId = $AttributeOption['mbiz_id'];
                        $attrOptData = $attrOptModel->getCollection()->addFieldToFilter('mbiz_id', $mbizAttrOptId)->setOrder('id', 'asc')->getFirstItem()->getData();
                    }

                    if (!empty($attrOptData)) {
                        $id = $attrOptData['id'];
                        $attrOptModel->load($id)->setMbizVersionNumber($AttributeOption['mbiz_version_number'])->setId($id)->save();
                    }
                }

            }
            /*Code to update the Attribute and Attirbute Options MicroBiz Version Number Ends Here*/
        }
        return json_encode($attributeset);
    }

    /**
     * @param $attributeInfo
     * @return string
     * @author KT174
     * @description This method is used to save the Attribute Relation and also updating the Magento Version Number for
     * Attributeoptions.
     */
    public function saveAttributeRelation($attributeInfo)
    {
        $attributeInfo = array($attributeInfo);

        if (Mage::getSingleton('admin/session')->isLoggedIn()) {

            $user = Mage::getSingleton('admin/session');
            $user = $user->getUser()->getFirstname();

        } else if (Mage::getSingleton('api/session')) {
            $userApi = Mage::getSingleton('api/session');
            $user = $userApi->getUser()->getUsername();

        } else {
            $user = 'Guest';
        }
        if (!empty($attributeInfo)) {
            foreach ($attributeInfo as $attribute) {
                $attribute['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                $attributeId = $attribute['magento_id'];
                $checkAttributeRelation = Mage::helper('microbiz_connector')->checkObjectRelation($attributeId, 'Attributes');
                $attribute['last_updated_from'] = 'MBIZ';
                $attribute['modified_by'] = $user;
                $attribute['modified_at'] = Now();
                $attribute['mbiz_version_number'] = $attribute['mbiz_version_number'];

                $currentAttributeOptions = $attribute['attribute_options'];
                unset($attribute['attribute_options']);

                if (!$checkAttributeRelation) {
                    $model = Mage::getModel('mbizattribute/mbizattribute')->setData($attribute)->save();
                    $attribute['status'] = "Attribute Relation Saved Successfully" . $attributeId;
                } else {
                    $relationdata = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $attributeId)->setOrder('id', 'asc')->getFirstItem();
                    $id = $relationdata['id'];
                    Mage::getModel('mbizattribute/mbizattribute')->load($id)->setMbizVersionNumber($attribute['mbiz_version_number'])
                        ->setLastUpdatedFrom('MBIZ')->setModifiedBy($user)->setModifiedAt(Now())->setId($id)->save();
                    $attribute['status'] = "Attribute Relation Updated Successfully" . $attributeId;
                }

                $connectorDebug = array();
                $connectorDebug['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();

                $connectorDebug['status'] = "Completed";
                $connectorDebug['status_msg'] = $attribute['status'];
                Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();


                /*Code to update MicroBiz Version number for Attribute Options Starts Here*/
                if (!empty($currentAttributeOptions)) {
                    foreach ($currentAttributeOptions as $AttributeOption) {
                        $attrOptModel = Mage::getModel('mbizattributeoption/mbizattributeoption');

                        $magAttrOptId = $AttributeOption['magento_id'];
                        if ($magAttrOptId) {
                            $attrOptData = $attrOptModel->getCollection()->addFieldToFilter('magento_id', $magAttrOptId)->setOrder('id', 'asc')->getFirstItem()->getData();
                        } else {
                            $mbizAttrOptId = $AttributeOption['mbiz_id'];
                            $attrOptData = $attrOptModel->getCollection()->addFieldToFilter('mbiz_id', $mbizAttrOptId)->setOrder('id', 'asc')->getFirstItem()->getData();
                        }

                        if (!empty($attrOptData)) {
                            $id = $attrOptData['id'];
                            $attrOptModel->load($id)->setMbizVersionNumber($AttributeOption['mbiz_version_number'])->setId($id)->save();
                        }
                    }

                }
                /*Code to update MicroBiz Version number for Attribute Options Ends Here*/

            }
        }

        return json_encode($attribute);
    }

    /**
     * Save category relation in relation tables
     *
     * @param array of category relation information
     * @return true on succeess
     * @author KT174
     */
    public function mbizSaveCategoryRelation($categoryInfo)
    {
        $categoryInfo = array(
            $categoryInfo
        );

        $arrcategory = array();
        foreach ($categoryInfo as $arrcategory) {
            $arrResult = array();
            $arrcategory['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $categoryId = $arrcategory['magento_id'];
            $checkCategoryRelation = Mage::helper('microbiz_connector')->checkObjectRelation($categoryId, 'ProductCategories');
            if (!$checkCategoryRelation) {
                Mage::getModel('mbizcategory/mbizcategory')->setData($arrcategory)->save();
                $arrcategory['status'] = "Category Relation Saved Successfully" . $categoryId;
            } else {
                $relationData = Mage::getModel('mbizcategory/mbizcategory')->getCollection()->addFieldToFilter('magento_id', $categoryId)->setOrder('id', 'asc')->getFirstItem();
                $id = $relationData['id'];
                Mage::getModel('mbizcategory/mbizcategory')->setData($arrcategory)->setId($id)->save();
                $arrcategory['status'] = "Category Relation Updated Successfully" . $categoryId;
            }
            $arrResult['instanceId'] = $arrcategory['instance_id'];
            $connectorDebug = array();
            $connectorDebug['instance_id'] = $arrResult['instanceId'];
            $connectorDebug['status'] = "Completed";
            $connectorDebug['status_msg'] = $arrcategory['status'];
            Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();

        }

        return json_encode($arrcategory);
    }

    /**
     * @params $amount = card amount, $ranges = group of ranges.
     * @param $amount
     * @param $ranges
     * @return true if value exists in the ranges false if not.
     * @author KT174
     * @description This method is used to check whether the card amount is already present in the group of ranges or nt
     */
    public function mbizCheckRangeExists($amount, $ranges)
    {
        $found = false;
        foreach ($ranges as $data) {
            if (is_array($data)) {
                if ($data['price'] == $amount) {
                    $found = true;
                    break; // no need to loop anymore, as we have found the item => exit the loop
                }
            }

        }
        return $found;

    }

    /**
     * @param $giftRanges
     * @param int $syncAll
     * @internal param \of $array gift card ranges.
     * @return true on success
     * @author KT174
     */
    public function mbizSaveGiftCardRanges($giftRanges, $syncAll = 0)
    {
        $sku = Mage::getStoreConfig('connector/settings/giftcardsku');
        $product = Mage::getModel('catalog/product');
        $productId = $product->getIdBySku($sku);
        if ($productId) //check whether product exists with that sku or not
        {
            $giftRanges = json_decode($giftRanges, true);

            $connectorDebug = array();
            $product->load($productId);
            if (!empty($giftRanges)) // if gift card ranges are exists
            {
                $productOptions = $product->getOptions();

                $values = array();
                $i = 0;

                foreach ($giftRanges as $giftCardData) // prepare values and title and amount
                {
                    if ($giftCardData['card_type'] == 1) // fixed
                    {
                        $amount = $giftCardData['card_amount'];
                        $title = 'Fixed Amount ' . $amount;
                    } else //any amount
                    {
                        $title = 'Any Amount';
                        $amount = 0;
                    }

                    if ($giftCardData['status'] == 1) //if the giftcard range is active
                    {

                        $rangeExists = $this->mbizCheckRangeExists($amount, $values);
                        if (!$rangeExists) {
                            $values[$i]['title'] = $title;
                            $values[$i]['price'] = $amount;
                            $values[$i]['price_type'] = 'fixed';
                            $values[$i]['sku'] = '';
                            $values[$i]['sort_order'] = '1';
                            $i++;
                        }

                    }
                }

                if (count($productOptions) > 0) // product have any custom options
                {
                    //get option id
                    foreach ($productOptions as $option) {
                        if ($option->getTitle() == 'Gift Card') {
                            $optionId = $option->getId();
                        }
                    }

                    if ($optionId) //if GiftCard  custom option exists
                    {

                        if (!empty($values)) {
                            //Mage::log("respnse values",null,'reorder.log');
                            //Mage::log($values,null,'reorder.log');
                            $newRanges = $values;
                            $existingRanges = array();

                            foreach ($productOptions as $option) {
                                if ($option->getTitle() == 'Gift Card') {
                                    $x = 0;
                                    $values = $option->getValues();
                                    foreach ($values as $value) {
                                        $existingRanges[$x]['title'] = $value->getTitle();
                                        $existingRanges[$x]['price'] = $value->getPrice();
                                        $x++;


                                    }
                                }
                            }
                            //Mage::log($existingRanges,null,'reorder.log');
                            foreach ($newRanges as $range) {
                                //check the range exists or not.
                                $rangeExists = $this->mbizCheckRangeExists($range['price'], $existingRanges);
                                if (!$rangeExists) //if the range is not exists in the available options create new value
                                {
                                    //Mage::log($range['title'],null,'reorder.log');
                                    $valueModel = Mage::getModel('catalog/product_option_value');
                                    $valueModel->setTitle($range['title'])
                                        ->setPriceType($range['price_type'])
                                        ->setSortOrder($range['sort_order'])
                                        ->setPrice($range['price'])
                                        ->setSku("")
                                        ->setOptionId($optionId);
                                    $valueModel->save();
                                }
                            }

                            $product->save();


                            //Now delete the options which are exists already that are not synced currently.

                            foreach ($productOptions as $option) {
                                if ($option->getTitle() == 'Gift Card') {
                                    foreach ($values as $key => $value) {
                                        $price = $value->getPrice();
                                        $rangeExists = $this->mbizCheckRangeExists($price, $newRanges);
                                        if (!$rangeExists) //range is not present in the currently synced range.
                                        {
                                            //Mage::log("ranges removed".$price,null,'reorder.log');
                                            $optionValueModel = Mage::getModel('catalog/product_option_value')->load($key);
                                            $optionValueModel->delete();
                                        }


                                    }
                                }
                            }
                            $product->save();

                            $connectorDebug['status'] = $connectorDebug['status'] . ' Updated the GiftCard Ranges.';
                        }

                    } else {
                        if (!empty($values)) {
                            $option = array(
                                'title' => 'Gift Card',
                                'type' => 'radio', // could be drop_down ,checkbox , multiple
                                'is_require' => 1,
                                'sort_order' => 0,
                                'values' => $values,
                            );

                            $product->setProductOptions(array($option));
                            $product->setCanSaveCustomOptions(true);
                            $product->save();

                            $connectorDebug['status'] = $connectorDebug['status'] . ' created new ranges.';
                        }
                    }
                } else { // if the product does not have options and range data is greater than 1
                    if (count($values) > 0) {
                        $option = array(
                            'title' => 'Gift Card',
                            'type' => 'radio', // could be drop_down ,checkbox , multiple
                            'is_require' => 1,
                            'sort_order' => 0,
                            'values' => $values,
                        );

                        $product->setProductOptions(array($option));
                        $product->setCanSaveCustomOptions(true);
                        $product->save();

                        $connectorDebug['status'] = $connectorDebug['status'] . ' created new ranges.';
                    }
                }
            } else // if the gifCard has no active ranges.
            {
                $connectorDebug['status'] .= 'No Active Gift Cards available ';
            }

        } else {
            $connectorDebug['status'] = 'Product Not Available';
        }


        $connectorDebug['instance_id'] = 1;
        Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();

        return json_encode($connectorDebug);

    }

    /**
     * Save product relation in relation tables
     *
     * @param array of product relation information
     * @return true on succeess
     * @author KT097
     */
    public function saveProductRelation($productinfo)
    {

        /* $productinfo = array(
             $productinfo
         );*/
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {

            $user = Mage::getSingleton('admin/session');
            $user = $user->getUser()->getFirstname();

        } else if (Mage::getSingleton('api/session')) {
            $userApi = Mage::getSingleton('api/session');
            $user = $userApi->getUser()->getUsername();

        } else {
            $user = 'Guest';
        }
        foreach ($productinfo as $product) {
            $product['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $productid = $product['magento_id'];
            /* $productModel = Mage::getModel("catalog/product")->load($productid);
             $productModel->setSyncPrdCreate(1);
             $productModel->setSyncStatus(1);
             if(isset($product['prd_status'])) {
                 $productModel->setPosProductStatus($product['prd_status']);
             }
             $productModel->save();*/
            unset($product['prd_status']);
            $checkProductRelation = Mage::helper('microbiz_connector')->checkObjectRelation($productid, 'Product');
            $product['last_updated_from'] = 'MBIZ';
            $product['modified_by'] = $user;
            $product['modified_at'] = Now();
            $product['mbiz_version_number'] = $product['mbiz_version_number'];

            if (!$checkProductRelation) {
                $model = Mage::getModel('mbizproduct/mbizproduct')->setData($product)->save();
            } else {
                $relationdata = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter('magento_id', $productid)->setOrder('id', 'asc')->getFirstItem();
                $id = $relationdata['id'];
                $product['mage_version_number'] = $relationdata['mage_version_number'];
                Mage::getModel('mbizproduct/mbizproduct')->load($id)->setMbizVersionNumber($product['mbiz_version_number'])
                    ->setLastUpdatedFrom('MBIZ')->setModifiedBy($user)->setModifiedAt(Now())->setId($id)->save();
                $attributeset['status'] = "Product Relation Updated Successfully";
            }
            $connectorDebug = array();
            $result = array();
            $connectorDebug['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $connectorDebug['status'] = "Completed";
            $connectorDebug['status_msg'] = "product relation Saved " . $productid;
            Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();
        }
        return true;
    }

    /**
     * Save Customer relation in relation tables
     *
     * @param array of Customer relation information
     * @return true on succeess
     * @author KT097
     */
    public function saveCustomerRelation($customerinfo, $includingAdressRelations = 0)
    {

        /* $customerinfo = array(
             $customerinfo
         );*/

        if ($includingAdressRelations) {
            $relationData = $customerinfo['customer'];
            $addressRelations = $customerinfo['customer_address'];
            $this->saveCustomerAddressRelation($addressRelations);
        } else {
            $relationData = $customerinfo;
        }
        foreach ($relationData as $customer) {
            $customer['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $customerid = $customer['magento_id'];
            $checkCustomerRelation = Mage::helper('microbiz_connector')->checkObjectRelation($customerid, 'Customer');
            /*  $customerModel = Mage::getModel('customer/customer')->load($customerid);
              $customerModel->setSyncCusCreate(1);
              $customerModel->setSyncStatus(1);
              $customerModel->setPosCusStatus(1);
              $customerModel->save();
            */
            if (!$checkCustomerRelation) {
                Mage::getModel('mbizcustomer/mbizcustomer')->setData($customer)->save();
            } else {
                $relationdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter('magento_id', $customerid)->setOrder('id', 'asc')->getFirstItem();
                $id = $relationdata['id'];
                Mage::getModel('mbizcustomer/mbizcustomer')->setData($customer)->setId($id)->save();
                $attributeset['status'] = "Customer Relation Updated Successfully";
            }
            $connectorDebug = array();
            $result = array();
            $connectorDebug['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $connectorDebug['status'] = "Completed";
            $connectorDebug['status_msg'] = "Customer relation Saved " . $customerid;
            Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();

        }
        return true;
    }

    /**
     * Save CustomerAddress relation in relation tables
     *
     * @param array of CustomerAddress relation information
     * @return true on succeess
     * @author KT097
     */
    public function saveCustomerAddressRelation($customerAddressInfo)
    {

        /*  $customerAddressInfo = array(
              $customerAddressInfo
          ); */

        foreach ($customerAddressInfo as $customerAddress) {
            $customerAddress['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $customerAddressId = $customerAddress['magento_id'];
            $checkCustomerAddressRelation = Mage::helper('microbiz_connector')->checkObjectRelation($customerAddressId, 'CustomerAddressMaster');
            if (!$checkCustomerAddressRelation) {
                Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->setData($customerAddress)->save();
            } else {
                $relationdata = Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->getCollection()->addFieldToFilter('magento_id', $customerAddressId)->setOrder('id', 'asc')->getFirstItem();
                $id = $relationdata['id'];
                Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->setData($customerAddress)->setId($id)->save();


            }
            $connectorDebug = array();
            $result = array();
            $connectorDebug['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $connectorDebug['status'] = "Completed";
            $connectorDebug['status_msg'] = "Customer Address relation Saved " . $customerAddressId;
            Mage::getModel('connectordebug/connectordebug')->setData($connectorDebug)->save();

        }
        return true;
    }

    /**
     * Save App Sync Status
     *
     * @param Sync information array (sync status and InstanceId)
     * @return true on succeess
     * @author KT097
     */
    public function setAppSyncStatus($appSyncStatus)
    {
        $instance_id = $appSyncStatus['instance_id'];
        $syncstatus = $appSyncStatus['syncstatus'];
        $configdata = new Mage_Core_Model_Config();
        $configdata->saveConfig('connector/settings/instance_id', $instance_id, 'default', 0);
        $configdata->saveConfig('connector/settings/syncstatus', $syncstatus, 'default', 0);
        /*
         * Cleaning the configuration cache programatically
         */
        try {
            $allTypes = Mage::app()->useCache();
            foreach ($allTypes as $type => $blah) {
                Mage::app()->getCacheInstance()->cleanType($type);
            }
        } catch (Exception $e) {
            // do something
            error_log($e->getMessage());
        }
        $result = array();
        $result['app_plugin_version'] = (string)Mage::getConfig()->getNode()->modules->Microbiz_Connector->version;
        $result['web_price'] = Mage::getStoreConfig('tax/calculation/price_includes_tax');
        return $result;
    }

    /**
     * Get Tax Classes
     *
     *
     * @return tax class array
     * @author KT097
     */
    public function getTaxClasses()
    {
        $taxClasses = Mage::getModel('tax/class')->getCollection()->getData();
        $allTaxclasses = array();
        foreach ($taxClasses as $taxClass) {
            $taxclasstype = $taxClass['class_type'];
            $allTaxclasses[$taxclasstype][] = $taxClass;
        }
        return json_encode($allTaxclasses);
    }

    /**
     * Get Tax Rules
     *
     *
     * @return tax class array
     * @author KT097
     */
    public function getTaxRules()
    {
        $taxRules = Mage::getModel('tax/calculation_rule')->getCollection()->getData();
        return json_encode($taxRules);
    }

    /**
     * for Magento orders
     *
     * @return array of Created orders
     * @author KT097
     */
    public function getOrderDetails()
    {
        return true;
    }

    /**
     * for Magento orders
     *
     * @param $order_id
     * @return details of an order
     * @author KT097
     */
    public function getOrderinformation($order_id)
    {

        $collection = array();
        $headerdatacollection = Mage::getModel('saleorderheader/saleorderheader')->getCollection()->setOrder('id', 'asc')->addFieldToFilter('order_id', $order_id)->getFirstItem()->getData();
        $headerdata = $headerdatacollection;
        //foreach ($headerdatacollection as $headerdata) {
        $currencyRate = $headerdata['base_amount'];
        //$order_id                               = $headerdata['sal_order_mag_id'];
        $headerdata['mage_order_number'] = Mage::getModel('sales/order')->load($order_id)->getIncrementId();
        $collection[$order_id]['OrderHeaderDetails'] = $headerdata;
        $itemdatacollection = Mage::getModel('saleorderitem/saleorderitem')->getCollection()->addFieldToFilter('order_id', $order_id)->getData();
        $itemsData = array();
        foreach ($itemdatacollection as $itemdata) {

            $giftCardProductSku = Mage::getStoreConfig('connector/settings/giftcardsku');
            if ($giftCardProductSku == $itemdata['sku']) {
                $itemdata['item_type'] = 2;
                $gcdDetails = Mage::getModel('mbizgiftcardsale/mbizgiftcardsale')->getCollection()->addFieldToFilter('order_id', $order_id)->addFieldToFilter('order_item_id', $itemdata['order_line_item_id'])->getData();
                $itemdata['gcd_info'] = $gcdDetails;
            }
            $brkupdataPromotioncollection = Mage::getModel('saleorderbrkup/saleorderbrkup')->getCollection()->addFieldToFilter('order_id', $order_id)->addFieldToFilter('order_line_itm_num', $itemdata['order_line_item_id'])->addFieldToFilter('brkup_type_id', 3)->getData();
            $itemdata['promotions'] = $brkupdataPromotioncollection;
            $brkupdataTaxcollection = Mage::getModel('saleorderbrkup/saleorderbrkup')->getCollection()->addFieldToFilter('order_id', $order_id)->addFieldToFilter('brkup_type_id', 1)->addFieldToFilter('order_line_itm_num', $itemdata['order_line_item_id'])->getData();
            $itemdata['tax'] = $brkupdataTaxcollection;
            $itemsData[] = $itemdata;
        }

        $collection[$order_id]['OrderItemDetails'] = $itemsData;
        /*$brkupdatacollection = Mage::getModel('saleorderbrkup/saleorderbrkup')->getCollection()->addFieldToFilter('order_id', $order_id)->getData();
        $brkupData          = array();
        foreach ($brkupdatacollection as $brkup) {
            $brkupData[] = $brkup;
        }

        $collection[$order_id]['BrkupDetails'] = $brkupData;
        */
        $shipmentInfo = Mage::getModel('pickup/pickup')->getCollection()->addFieldToFilter('order_id', $order_id)->setOrder('id', 'asc')->getData();
        $collection[$order_id]['OrderHeaderDetails']['zone_id'] = $shipmentInfo[0]['zone_id'];

        $addressData = Mage::getModel('sales/order_address')->getCollection()->addFieldToFilter('parent_id', $order_id)->addFieldToFilter('address_type', 'shipping')->getFirstItem()->getData();

        if (count($addressData) == 0) {
            $addressData = Mage::getModel('sales/order_address')->getCollection()->addFieldToFilter('parent_id', $order_id)->addFieldToFilter('address_type', 'billing')->getFirstItem()->getData();
        }

        $collection[$order_id]['AddressDetails'] = $addressData;
        $order = Mage::getModel("sales/order")->load($order_id); //load order by order id
        $payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
        $creditData = Mage::getModel('mbizcreditusage/mbizcreditusage')->getCollection();
        $arrCreditData = $creditData->addFieldToFilter('order_id', $order_id)->setOrder('id', 'asc')->getData();
        $discountAmount = 0;
        if (count($arrCreditData) > 0) {
            if (is_array($arrCreditData)) {

                foreach ($arrCreditData as $key => $data) {
                    $discountAmount = $discountAmount + $data['credit_amt'];
                }
            }
        }
        /*$collection[$order_id]['OrderHeaderDetails']['payment']['total_due'] = $order->getTotalDue();
        $collection[$order_id]['OrderHeaderDetails']['payment']['total_paid'] = $order->getTotalPaid()-$discountAmount;
        $collection[$order_id]['OrderHeaderDetails']['payment']['total_amount'] = $order->getGrandTotal();*/
        $response = Mage::getModel('Microbiz_Connector_Model_Observer')->getDefaulrStoreIdFromMbiz($order->getStoreId());
        $defaultStoreFromMbiz = $response['store_id'];
        $mbizStoreCurrency = Mage::helper('microbiz_connector')->getMbizStoreCurrency($defaultStoreFromMbiz);
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        $baseCurrencyCode = $order->getBaseCurrencyCode();
        $mbizStoreCurrency = (!$mbizStoreCurrency) ? $baseCurrencyCode : $mbizStoreCurrency;
        if ($mbizStoreCurrency && $mbizStoreCurrency == $orderCurrencyCode) {
            $collection[$order_id]['OrderHeaderDetails']['payment']['total_due'] = $order->getTotalDue();
            $collection[$order_id]['OrderHeaderDetails']['payment']['total_paid'] = $order->getTotalPaid() - $discountAmount;
            $collection[$order_id]['OrderHeaderDetails']['payment']['total_amount'] = $order->getGrandTotal();
        } else if ($mbizStoreCurrency == $baseCurrencyCode) {
            $collection[$order_id]['OrderHeaderDetails']['payment']['total_due'] = $order->getBaseTotalDue();
            $collection[$order_id]['OrderHeaderDetails']['payment']['total_paid'] = $order->getBaseTotalPaid() - $discountAmount;
            $collection[$order_id]['OrderHeaderDetails']['payment']['total_amount'] = $order->getBaseGrandTotal();
        } else {
            $collection[$order_id]['OrderHeaderDetails']['payment']['total_due'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($order->getBaseTotalDue(), $currencyRate);
            $collection[$order_id]['OrderHeaderDetails']['payment']['total_paid'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($order->getBaseTotalPaid(), $currencyRate) - $discountAmount;
            $collection[$order_id]['OrderHeaderDetails']['payment']['total_amount'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($order->getBaseGrandTotal(), $currencyRate);
        }
        $orderPaymentItemsInformation = array();
        $paymentItemsInformation = Mage::getModel('mbizcreditusage/mbizcreditusage')->getCollection()->addFieldToFilter('order_id', $order_id)->getData();
        foreach ($paymentItemsInformation as $paymentItemInformation) {

            $paymentItemInfo = array();
            if ($paymentItemInformation['type'] == 1) {
                $paymentItemInfo['method'] = 'mbiz_storecredit';
            }
            if ($paymentItemInformation['type'] == 2) {
                $paymentItemInfo['method'] = 'mbiz_giftcard';
            }
            $paymentItemInfo['paid_amount'] = $paymentItemInformation['credit_amt'];
            $paymentItemInfo['credit_id'] = $paymentItemInformation['credit_id'];
            $orderPaymentItemsInformation[] = $paymentItemInfo;
        }
        $origpaymentItemInfo['method'] = $payment_method_code;
        $origpaymentItemInfo['paid_amount'] = $order->getTotalPaid();
        if ($mbizStoreCurrency && $mbizStoreCurrency == $orderCurrencyCode) {
            $origpaymentItemInfo['paid_amount'] = $order->getTotalPaid();
        } else if ($mbizStoreCurrency == $baseCurrencyCode) {
            $origpaymentItemInfo['paid_amount'] = $order->getBaseTotalPaid();
        } else {
            $origpaymentItemInfo['paid_amount'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($order->getBaseTotalPaid(), $currencyRate);
        }

        $origpaymentItemInfo['method'] = $payment_method_code;
        $orderPaymentItemsInformation[] = $origpaymentItemInfo;
        $collection[$order_id]['OrderHeaderDetails']['payment']['items'] = $orderPaymentItemsInformation;
        $shipmentInfo = Mage::getModel('pickup/pickup')->getCollection()->addFieldToFilter('order_id', $order->getId())->setOrder('id', 'asc')->getData();
        $collection[$order_id]['OrderHeaderDetails']['note'] = $shipmentInfo[0]['note'];
        $shippingMethodDetails = array();
        $shippingMethodDetails['method'] = $order->getShippingMethod();
        //$shippingMethodDetails['amount'] = $order->getShippingInclTax();
        if ($mbizStoreCurrency && $mbizStoreCurrency == $orderCurrencyCode) {
            $shippingMethodDetails['amount'] = $order->getShippingInclTax();
        } else if ($mbizStoreCurrency == $baseCurrencyCode) {
            $shippingMethodDetails['amount'] = $order->getBaseShippingInclTax();
        } else {
            $shippingMethodDetails['amount'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($order->getBaseShippingInclTax(), $currencyRate);
        }
        $collection[$order_id]['OrderHeaderDetails']['shippingDetails'] = $shippingMethodDetails;
        //}
        return $collection;
    }

    public function updateOrderData($ordersData)
    {

        $orderHeaderModel = Mage::getModel('saleorderheader/saleorderheader')->getCollection()->addFieldToFilter('order_id', $ordersData['OrderHeaderDetails']['order_id'])->getData();
        $orderHeaderModelId = $orderHeaderModel[0]['id'];
        $ordersData['OrderHeaderDetails']['base_amount'] = $orderHeaderModel[0]['base_amount'];
        $ordersData['OrderHeaderDetails']['status_id'] = $orderHeaderModel[0]['status_id'];
        $orderHeaderModelUpdate = Mage::getModel('saleorderheader/saleorderheader')->load($orderHeaderModelId);
        $orderHeaderModelUpdate->setData($ordersData['OrderHeaderDetails'])->setId($orderHeaderModelId)->save();
        /*foreach($ordersData['OrderItemDetails'] as $itemInformation) {
            $itemInformation['order_id'] = $ordersData['OrderHeaderDetails']['order_id'];
            $orderItemsModel = Mage::getModel('saleorderitem/saleorderitem')->getCollection()->addFieldToFilter('order_id',$ordersData['OrderHeaderDetails']['order_id'])->addFieldToFilter('sku',$itemInformation['sku'])->getData();
            $orderItemsModelId = $orderItemsModel[0]['id'];
            $itemInformation['mbiz_order_line_item_id'] = $itemInformation['order_line_item_id'];
            $itemInformation['order_line_item_id'] = $orderItemsModel[0]['order_line_item_id'];
            $itemInformation['order_id'] = $ordersData['OrderHeaderDetails']['order_id'];
            $orderItemsModelUpdate = Mage::getModel('saleorderitem/saleorderitem')->load($orderItemsModelId);
            $orderItemsModelUpdate->setData($itemInformation)->setId($orderItemsModelId)->save();
        }*/

    }

    /**
     * Update attributesets
     * @param Id $attributeSetId
     * @param $attributeSetData
     * @param $mbizAttributeSetId
     * @param bool $isNewlyCreated
     * @internal param \Id $attributeSetId of an attributeSet needs to update
     * @internal param array $attributesetData of attributeset information with groups and Attributes
     * @return array containing status and object id
     * @author KT097
     */
    public function updateAttributeSet($attributeSetId, $attributeSetData, $mbizAttributeSetId, $isUpdate = false)
    {
        $locale = 'en_US';
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {

            $user = Mage::getSingleton('admin/session');
            $user = $user->getUser()->getFirstname();

        } else if (Mage::getSingleton('api/session')) {
            $userApi = Mage::getSingleton('api/session');
            $user = $userApi->getUser()->getUsername();

        } else {
            $user = 'Guest';
        }

// changing locale works!

        Mage::app()->getLocale()->setLocaleCode($locale);
        Mage::app()->getTranslator()->init('frontend', true);
        Mage::app()->getTranslator()->init('adminhtml', true);
// needed to add this
        Mage::app()->getTranslator()->setLocale($locale);
        $exceptions = array();
        $isNewItemsExists = false;
        unset($attributeSetData['attribute_set_name']);
        $groups = Mage::getModel('eav/entity_attribute_group')->getResourceCollection()->setAttributeSetFilter($attributeSetId)->load();
        $arrGroups = array();
        foreach ($groups as $group) {
            $arrGroups[] = $group->getAttributeGroupId();
        }

        $newarrGroups = array();
        $mbizGroups = $attributeSetData;
        //Mage::Log($mbizGroups);
        //return $mbizGroups;
        foreach ($mbizGroups as $value) {
            //$attributeGroup = $key;
            $mbizAttributeGroupId = $value['attribute_group_id'];
            $attributeGroupName = trim($value['attribute_group_name']);
            Mage::Log($attributeGroupName, null, 'attributemapiingsSync.log');
            $checkAttributeGroupExists = Mage::getModel('mbizattributegroup/mbizattributegroup')->getCollection()->addFieldToFilter('mbiz_id', $mbizAttributeGroupId)->getFirstItem()->getData();
            if ($checkAttributeGroupExists) {
                $attributeGroupId = $checkAttributeGroupExists['magento_id'];
            } else {
                $groupData = Mage::getModel('eav/entity_attribute_group')->getResourceCollection()->setAttributeSetFilter($attributeSetId)->addFieldToFilter('attribute_group_name', $attributeGroupName)->getFirstItem()->getData();
                $attributeGroupId = $groupData['attribute_group_id'];
            }
            if (!$attributeGroupId) {
                $modelGroup = Mage::getModel('eav/entity_attribute_group');
                //set the group name
                $modelGroup->setAttributeGroupName($attributeGroupName)->setAttributeSetId($attributeSetId);
                //save the new group
                $modelGroup->save();
                $attributeGroupId = (int)$modelGroup->getId();
            }
            $newarrGroups[] = $attributeGroupId;
            Mage::Log('Attribute Group Id ');
            Mage::Log($attributeGroupId);
            $attributeGroupRelation['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $attributeGroupRelation['magento_id'] = $attributeGroupId;
            $attributeGroupRelation['mbiz_id'] = $mbizAttributeGroupId;
            $checkAttributeGroupRelation = Mage::getModel('mbizattributegroup/mbizattributegroup')->getCollection()->addFieldToFilter('magento_id', $attributeGroupId)->setOrder('id', 'asc')->getData();

            if (!$checkAttributeGroupRelation) {
                $model = Mage::getModel('mbizattributegroup/mbizattributegroup')->setData($attributeGroupRelation)->save();
                $isNewItemsExists = true;
            }
            $attributes = $value['attributes'];
            $attributesCollection = Mage::getResourceModel('catalog/product_attribute_collection');
            $attributesCollection->setAttributeGroupFilter($attributeGroupId);

            $oldAttributeIds = array();
            $userDefinedAttributeIds = array();
            foreach ($attributesCollection as $attributeinformation) {
                $oldAttributeIds[] = $attributeinformation->getAttributeId();
                if ($attributeinformation->getIsUserDefined()) {
                    $userDefinedAttributeIds[] = $attributeinformation->getAttributeId();
                }
            }
            $attributesSortOrder = array();
            $attributesRelatedToAttributeSet = Mage::getResourceModel('catalog/product_attribute_collection')
                ->setAttributeSetFilter($attributeSetId)
                ->addVisibleFilter()
                ->load();

            foreach ($attributesRelatedToAttributeSet->getItems() as $attributeSort) {
                $attributesSortOrder[$attributeSort->getAttributeCode()] = $attributeSort->getSortOrder();
            }
            //return $oldAttributeIds;
            $newAttributeIds = array();
            foreach ($attributes as $code => $attribute) {
                Mage::Log('Attribute Code Execution for Mbiz Attribute ' . $code);
                Mage::Log($attribute, null, 'attrtype.log');
                $attributeId = '';
                $mbizAttributeId = $attribute['attribute_id'];
                $magentoAttributeCode = $attribute['magento_attribute_code'];
                unset($attribute['attribute_id']);
                if (isset($attribute['attribute_options'])) {
                    $attributeOptions = $attribute['attribute_options'];
                    unset($attribute['attribute_options']);
                }
                unset($attribute['attribute_set_id']);
                unset($attribute['attribute_group_id']);

                $attribute['frontend_label'] = $attribute['attribute_label'];
                $mbizAttributeCode = $code;

                $checkMbizAttributeCode = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('mbiz_attr_set_id', $mbizAttributeSetId)->addFieldToFilter('mbiz_attr_code', $code)->setOrder('id', 'asc')->getData();
                if (!$checkMbizAttributeCode) {
                    $checkMbizAttributeCode = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('mbiz_attr_set_id', array('null' => true))->addFieldToFilter('status', '1')->addFieldToFilter('mbiz_attr_code', $code)->setOrder('id', 'asc')->getData();

                }
                if ($checkMbizAttributeCode) {
                    $code = $checkMbizAttributeCode[0]['magento_attr_code'];
                    $attributeId = $checkMbizAttributeCode['magento_id'];
                }


                if (!$attributeId) {
                    $code = ($magentoAttributeCode) ? $magentoAttributeCode : $code;
                    $isAttributeExists = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', $code);

                    if (!$isAttributeExists->getId()) {
                        Mage::Log('attribute if');
                        try {
                            $attribute['attribute_name'] = $code;
                            $attributeId = $this->createAttribute($attribute);
                            if (is_array($attributeId)) {
                                $exceptions = $attributeId;
                                $attributeId = '';
                            }

                        } catch (Exception $e) {
                            $exceptions[] = $e->getMessage();
                        }
                    } else if (!$magentoAttributeCode && $isAttributeExists->getFrontendInput() != $attribute['frontend_input']) {
                        try {
                            $attribute['attribute_name'] = $this->getUpdatedAttributeCode($code);
                            $code = $attribute['attribute_name'];
                            $attributeId = $this->createAttribute($attribute);
                            if (is_array($attributeId)) {
                                $exceptions = $attributeId;
                                $attributeId = '';
                            }

                        } catch (Exception $e) {
                            $exceptions[] = $e->getMessage();
                        }
                    } else {
                        Mage::Log('attribute else');
                        $attributeId = $isAttributeExists->getId();
                        $attributeUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
                        try {
                            if ($attributeUpdate->getIsUserDefined() && $attribute['is_user_defined']) {
                                $attribute['is_configurable'] = (isset($attribute['is_configurable']) && !empty($attribute['is_configurable'])) ? $attribute['is_configurable'] : $attributeUpdate->getIsConfigurable();
                                $attribute['is_used_for_promo_rules'] = (isset($attribute['is_used_for_promo_rules']) && !empty($attribute['is_used_for_promo_rules'])) ? $attribute['is_used_for_promo_rules'] : $attributeUpdate->getIsUsedForPromoRules();
                                $attribute['is_required'] = (isset($attribute['is_required']) && !empty($attribute['is_required'])) ? $attribute['is_required'] : $attributeUpdate->getIsRequired();
                                $attribute['is_unique'] = (isset($attribute['is_unique']) && !empty($attribute['is_unique'])) ? $attribute['is_unique'] : $attributeUpdate->getIsUnique();
                                $attribute['source_model'] = $attributeUpdate->getSourceModel();
                                $applyTo = $attributeUpdate->getApplyTo();
                                $isApplyToArray = $attribute['apply_to'];
                                if (is_array($isApplyToArray)&& is_array($applyTo) && count($applyTo)) {
                                    $attribute['apply_to'] = array_merge($applyTo, $isApplyToArray);
                                }
                                switch ($attributeUpdate->getIsGlobal()) {
                                    case 0:
                                        $attribute['scope'] = 'store';
                                        break;
                                    case 1:
                                        $attribute['scope'] = 'global';
                                        break;
                                    case 2:
                                        $attribute['scope'] = 'website';
                                        break;
                                    default:
                                        $attribute['scope'] = 'global';
                                        break;
                                }
                                $attribute['scope'] = (isset($attribute['is_configurable'])) ? 'global' : $attribute['scope'];
                                Mage::getModel('Mage_Catalog_Model_Product_Attribute_Api')->update($attributeId, $attribute);
                            }
                        } catch (Exception $e) {
                            $exceptions[] = "Exception While updating";
                            $exceptions[] = $e->getMessage();
                        }

                    }
                } else {
                    $attributeUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
                    try {
                        if ($attributeUpdate->getIsUserDefined() && $attribute['is_user_defined']) {
                            $attribute['is_configurable'] = (isset($attribute['is_configurable']) && !empty($attribute['is_configurable'])) ? $attribute['is_configurable'] : $attributeUpdate->getIsConfigurable();
                            $attribute['is_used_for_promo_rules'] = (isset($attribute['is_used_for_promo_rules']) && !empty($attribute['is_used_for_promo_rules'])) ? $attribute['is_used_for_promo_rules'] : $attributeUpdate->getIsUsedForPromoRules();
                            $attribute['is_required'] = (isset($attribute['is_required']) && !empty($attribute['is_required'])) ? $attribute['is_required'] : $attributeUpdate->getIsRequired();
                            $attribute['is_unique'] = (isset($attribute['is_unique']) && !empty($attribute['is_unique'])) ? $attribute['is_unique'] : $attributeUpdate->getIsUnique();
                            $attribute['source_model'] = $attributeUpdate->getSourceModel();
                            //$singledata['ItemDetails']['scope'] =  $attributeUpdate->getScope();
                            $applyTo = $attributeUpdate->getApplyTo();
                            $isApplyToArray = $attribute['apply_to'];
                            if (is_array($isApplyToArray) && is_array($applyTo) && count($applyTo)) {
                                $attribute['apply_to'] = array_merge($applyTo, $isApplyToArray);
                            }
                            switch ($attributeUpdate->getIsGlobal()) {
                                case 0:
                                    $attribute['scope'] = 'store';
                                    break;
                                case 1:
                                    $attribute['scope'] = 'global';
                                    break;
                                case 2:
                                    $attribute['scope'] = 'website';
                                    break;
                                default:
                                    $attribute['scope'] = 'global';
                                    break;
                            }
                            $attribute['scope'] = (isset($attribute['is_configurable'])) ? 'global' : $attribute['scope'];
                            Mage::getModel('Mage_Catalog_Model_Product_Attribute_Api')->update($attributeId, $attribute);


                            //  $attributeUpdate->setAttributeId($attributeId)->setSourceModel($attributeUpdate->getSourceModel())->setIsConfigurable($attribute['is_configurable'])->setIsGlobal($attributeUpdate->getIsGlobal())->setIsRequired($attribute['is_required'])->setIsUsedForPromoRules($attribute['is_used_for_promo_rules'])->setIsUnique($attribute['is_unique'])->setApplyTo($attribute['apply_to'])->setFrontendLabel($attribute['frontend_label'])->save();
                            //$attributeUpdateId = Mage::getModel('Mage_Catalog_Model_Product_Attribute_Api')->update($attributeId,$attribute);
                        }
                    } catch (Exception $e) {
                        $exceptions[] = $e->getMessage();
                    }
                }
                if ($attributeId) {
                    Mage::Log('test name');
                    $newAttributeIds[] = $attributeId;
                    (!$attribute['is_user_defined']) ? $mbizSystemDefinedAttributes[] = $attributeId : null;
                    $attributeRelation['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                    $attributeRelation['magento_id'] = $attributeId;
                    $attributeRelation['mbiz_id'] = $mbizAttributeId;
                    $attributeRelation['magento_attr_code'] = $code;
                    $attributeRelation['mbiz_attr_code'] = $mbizAttributeCode;
                    $attributeRelation['mbiz_attr_set_id'] = $mbizAttributeSetId;

                    $checkAttributeRelation = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $attributeId)->addFieldToFilter('mbiz_attr_set_id', $mbizAttributeSetId)->setOrder('id', 'asc')->getData();

                    if (!$checkAttributeRelation) {

                        $attributeRelation['mbiz_version_number'] = $attribute['mbiz_version_number'];
                        $attributeRelation['last_updated_from'] = ($isUpdate == true) ? 'MBIZ' : 'MAG';
                        $attributeRelation['modified_by'] = $user;
                        $attributeRelation['modified_at'] = Now();
                        $checkDefaultAttributeRelation = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $attributeId)->addFieldToFilter('mbiz_attr_set_id', array('null' => true))->setOrder('id', 'asc')->getData();
                        if(!$checkDefaultAttributeRelation) {
                            $attributeDefaultRelation = $attributeRelation;
                            unset($attributeDefaultRelation['instance_id']);
                            unset($attributeDefaultRelation['mbiz_attr_set_id']);
                            Mage::getModel('mbizattribute/mbizattribute')->setData($attributeDefaultRelation)->save();

                        }

                        $model = Mage::getModel('mbizattribute/mbizattribute')->setData($attributeRelation)->save();
                        $isNewItemsExists = true;
                    } else {
                        $mbizVerNo = $attribute['mbiz_version_number'];

                        $attrRelData = Mage::helper('microbiz_connector')->checkIsObjectExists($attributeSetId, 'Attributes');
                        if (!empty($attrRelData)) {
                            $Id = $attrRelData['id'];
                            $attrModel = Mage::getModel('mbizattribute/mbizattribute')->load($Id);

                            $attrModel->setMbizVersionNumber($mbizVerNo);
                            if ($isUpdate == true) {
                                $attrModel->setLastUpdateFrom('MBIZ');
                                $attrModel->setMageVersionNumber($attrRelData['mage_version_number'] + 1);
                            } else {
                                $attrModel->setLastUpdateFrom('MAG');
                            }

                            $attrModel->setModifiedBy($user);
                            $attrModel->setModifiedAt(Now());
                            $attrModel->setId($Id)->save();
                        }
                    }

                    $attributeModel = Mage::getModel('eav/entity_attribute')->load($attributeId);
                    $isUserDefined = $attributeModel->getIsUserDefined();
                    $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($attributeSetId);
                    if (!$attributeGroupId) {
                        // define default attribute group id for current attribute set
                        $attributeGroupId = $attributeSet->getDefaultGroupId();
                    }
                    $attributeModel->setAttributeSetId($attributeSet->getId())->loadEntityAttributeIdBySet();
                    $entityAttributeId = $attributeModel->getEntityAttributeId();
                    //$exceptions[] = $entityAttributeId."test";
                    //return $exceptions;
                    try {
                        $sortOrder = 100;
                        //$attributeModel = Mage::getModel('eav/entity_attribute')->load($entityAttributeId);
                        //  $attributeModel->setEntityTypeId($attributeSet->getEntityTypeId())->setAttributeSetId($attributeSetId)->setAttributeGroupId($attributeGroupId)->setSortOrder($sortOrder)->setEntityAttributeId($entityAttributeId)->save();

                        $attSet = Mage::getModel('eav/entity_type')->getCollection()->addFieldToFilter('entity_type_code', 'catalog_product')->getFirstItem(); // This is because the you adding the attribute to catalog_products entity ( there is different entities in magento ex : catalog_category, order,invoice... etc )
                        $set = Mage::getModel('eav/entity_attribute_set')->load($attributeSet->getId());
                        $setId = $set->getId();

                        //if(($isUserDefined || (!$isUserDefined && !$entityAttributeId)) && $attributeId) {

                        if ($isUserDefined && $attributeId && ($attribute['is_user_defined'] || (!$attribute['is_user_defined'] && !$entityAttributeId))) {
                            $newItem = Mage::getModel('eav/entity_attribute');
                            $newItem->setEntityTypeId($attSet->getId())
                                ->setAttributeSetId($attributeSetId)
                                ->setAttributeGroupId($attributeGroupId)
                                ->setAttributeId($attributeId);
                            if(!$entityAttributeId) {
                                $newItem->setSortOrder($sortOrder);
                            }
                            else {
                                Mage::log($attributesSortOrder[$attributeModel->getAttributeCode()],null,'sortOrder.log');
                                $newItem->setSortOrder($attributesSortOrder[$attributeModel->getAttributeCode()]);
                                $newItem->setEntityAttributeId($entityAttributeId);
                            }

                            $newItem->save();
                            $attributeModelUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
                            $attributeSourceModel = Mage::helper('catalog/product')->getAttributeSourceModelByInputType($attributeModelUpdate->getFrontendInput());
                            $attributeSourceModel = ($attributeModelUpdate->getSourceModel()) ? $attributeModelUpdate->getSourceModel() : $attributeSourceModel;
                            $attributeModelUpdate->setAttributeId($attributeId)->setSourceModel($attributeSourceModel)->save();

                        }
                        Mage::Log("Attribute " . $entityAttributeId . " Added to Attribute Set " . $attributeSetId . " in Attribute Group " . $attributeGroupId);
                    } catch (Exception $e) {
                        $exceptions[] = $e->getMessage();
                    }
                    Mage::Log('attr source Model');
                    if (!$checkAttributeRelation && ($attributeModel->getSourceModel() == 'eav/entity_attribute_source_table' || is_null($attributeModel->getSourceModel()))) {
                        Mage::Log($attributeOptions);
                        $attributeCode = $attributeModel->getAttributeCode();
                        $this->updateAttributeOptions($attributeCode, $attributeOptions, $mbizAttributeId, $isUpdate);

                    }

                }

            }
            foreach ($newAttributeIds as $newAttributeId) {
                $attributeModelUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($newAttributeId);
                $attributeSourceModel = Mage::helper('catalog/product')->getAttributeSourceModelByInputType($attributeModelUpdate->getFrontendInput());
                if ($attributeModelUpdate->getSourceModel() == 'eav/entity_attribute_source_table') {
                    $attributeModelUpdate->setAttributeId($newAttributeId)->setSourceModel($attributeSourceModel)->save();
                }

            }
            $removeAttributes = array_diff($userDefinedAttributeIds, $newAttributeIds);
            foreach ($removeAttributes as $removeAttribute) {
                $checkAttributeRelation = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $removeAttribute)->addFieldToFilter('mbiz_attr_set_id', $mbizAttributeSetId)->setOrder('id', 'asc')->getData();
                if ($checkAttributeRelation) {

                    $attribute = Mage::getModel('eav/entity_attribute')->load($removeAttribute);
                    $attribute->setAttributeSetId($attributeSetId)->setAttributeGroupId($attributeGroupId)->loadEntityAttributeIdBySet();
                    if ($attribute->getEntityAttributeId() && !in_array($removeAttribute,$mbizSystemDefinedAttributes) && $checkAttributeRelation['0']['mbiz_id'] >= 30000) {
                        try {
                            Mage::getmodel('Mage_Catalog_Model_Product_Attribute_Set_Api')->attributeRemove($removeAttribute, $attributeSetId);
                            $attributerelationId = $checkAttributeRelation[0]['id'];
                            $attributeRelModel = Mage::getModel('mbizattribute/mbizattribute')->load($attributerelationId);
                            $attributeRelModel->delete();
                        } catch (Exception $e) {
                            $exceptions[] = $e->getMessage();
                        }
                    }
                }
                else {
                    $checkAttributeRelation = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $removeAttribute)->addFieldToFilter('mbiz_attr_set_id', array('null' => true))->addFieldToFilter('status',1)->setOrder('id', 'asc')->getData();
                    if ($checkAttributeRelation) {

                        $attribute = Mage::getModel('eav/entity_attribute')->load($removeAttribute);
                        $attribute->setAttributeSetId($attributeSetId)->setAttributeGroupId($attributeGroupId)->loadEntityAttributeIdBySet();
                        if ($attribute->getEntityAttributeId() && !in_array($removeAttribute,$mbizSystemDefinedAttributes)  && $checkAttributeRelation['0']['mbiz_id'] >= 30000) {
                            try {
                                Mage::getmodel('Mage_Catalog_Model_Product_Attribute_Set_Api')->attributeRemove($removeAttribute, $attributeSetId);
                                $attributerelationId = $checkAttributeRelation[0]['id'];
                                $attributeRelModel = Mage::getModel('mbizattribute/mbizattribute')->load($attributerelationId);
                                $attributeRelModel->delete();
                            } catch (Exception $e) {
                                $exceptions[] = $e->getMessage();
                            }
                        }
                    }
                }
            }
            Mage::Log('Removed attributes End');
        }
        $removeAttributegroups = array_diff($arrGroups, $newarrGroups);
        foreach ($removeAttributegroups as $removeAttributegroup) {
            $checkAttributeGroupRelation = Mage::getModel('mbizattributegroup/mbizattributegroup')->getCollection()->addFieldToFilter('magento_id', $removeAttributegroup)->setOrder('id', 'asc')->getData();
            if ($checkAttributeGroupRelation) {
                try {
                    $attributeGrouprelationId = $checkAttributeGroupRelation[0]['id'];
                    $attributegroupRelModel = Mage::getModel('mbizattributegroup/mbizattributegroup')->load($attributeGrouprelationId);
                    $attributegroupRelModel->delete();
                    Mage::getmodel('Mage_Catalog_Model_Product_Attribute_Set_Api')->groupRemove($removeAttributegroup);

                } catch (Exception $e) {
                    $exceptionsMessage = $e->getMessage() . $removeAttributegroup;
                    Mage::Log($exceptionsMessage, null, 'attributeSetSync.log');
                }

            }
        }
        $attributesetResponseArray = array();
        $attributesetResponseArray['isNewItemsExists'] = $isNewItemsExists;
        $attributesetResponseArray['exceptions'] = $exceptions;
        return $attributesetResponseArray;
    }


    /**
     * Add/Update attribute options
     * @param Id $attributeId
     * @param Attibute $newValue
     * @param null $optionId
     * @param $order
     * @param null $is_default
     * @internal param \Id $attributeId of an attribute needs to update with options
     * @internal param \Attibute $newValue option Vallue
     * @return new option Id
     * @author KT097
     */
    public function addAttributeOption($attributeId, $newValue, $optionId = null, $order, $is_default = null)
    {
        $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
        try {
            //get all the possible attribute values and put them into array
            $mageAttrOptions = $attribute->getSource()->getAllOptions(false);
            $attrOptions = array();
            $attrOptionsUpper = array();
            foreach ($mageAttrOptions as $option) {
                $labelVal = strtoupper($option['label']);
                $attrOptionsUpper[$labelVal] = $option['value'];
                $origLabelVal = $option['label'];
                $attrOptions[$origLabelVal] = $option['value'];
            }

            //if we do not have the attribute value set, then we need to add
            //the new value to the attribute and return the id of the newly created
            //attribute value
            if ($optionId != '') {
                $_optionArr = array(
                    'value' => array(),
                    'order' => array(),
                    'delete' => array()
                );
                foreach ($attrOptions as $label => $value) {
                    //iterate thru old ones
                    if ($optionId == $value) {
                        $label = $newValue;
                    }
                    $_optionArr['value'][$value] = array(
                        $label
                    );
                    if (!isset($_optionArr['order'][$value])) {
                        $optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                            ->setAttributeFilter($attributeId)
                            ->setPositionOrder('desc')
                            ->load();
                        $optionTotalData = $optionCollection->addFieldToFilter('option_id', $value)->getData();
                        $_optionArr['order'][$value] = $optionTotalData[0]['sort_order'];
                    }
                }
                /* $_optionArr['value'][$optionId] = array(
                     $newValue
                 );*/
                $_optionArr['order'][$optionId] = (int)$order;
                if ($is_default) {
                    $modelData[] = $optionId;
                } else {
                    $modelData[] = $attribute->getDefaultValue();
                }
            } else if (!isset($attrOptionsUpper[strtoupper($newValue)])) {
                //create that option and retrieve the id
                $_optionArr = array(
                    'value' => array(),
                    'order' => array(),
                    'delete' => array()
                );
                foreach ($attrOptions as $label => $value) {
                    //iterate thru old ones
                    $_optionArr['value'][$value] = array(
                        $label
                    );
                    if (!isset($_optionArr['order'][$value])) {
                        $optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                            ->setAttributeFilter($attributeId)
                            ->setPositionOrder('desc')
                            ->load();
                        $optionTotalData = $optionCollection->addFieldToFilter('option_id', $value)->getData();
                        $_optionArr['order'][$value] = $optionTotalData[0]['sort_order'];
                    }
                }
                //add the new one
                $_optionArr['value']['option_1'] = array(
                    $newValue
                );
                $_optionArr['order']['option_1'] = (int)$order;
                if ($is_default) {
                    $modelData[] = 'option_1';
                } else {
                    $modelData[] = $attribute->getDefaultValue();
                }
            }
            //set them to the attribute
            Mage::Log($_optionArr);
            $attribute->setOption($_optionArr);
            $attribute->setDefault($modelData);
            //save the attribute
            $attribute->save();

            //get the new id for the value
            $entityType = Mage::getModel('catalog/product')->getResource()->getEntityType();
            $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeId);
            $mageAttrOptions = $attribute->getSource()->getAllOptions(false);
            $attrOptions = array();
            foreach ($mageAttrOptions as $option) {
                $attrOptions[$option['label']] = $option['value'];
            }
            //we have the new attribute value added, new ID fetched, now we need to return it
            return $attrOptions[$newValue];


        } catch (Exception $ex) {

            $exceptions[] = $ex->getMessage();
            return $exceptions;
        }

    }

    /**
     * Retrieve stores list
     *
     * @return array
     */
    public function storesList()
    {
        // Retrieve stores

        $stores = Mage::app()->getStores();
        // return Mage::getSingleton('api/session')->getUser()->getUsername();
        // Make result array
        $result = array();
        foreach ($stores as $store) {
            $result[] = array(
                'store_id' => $store->getId(),
                'code' => $store->getCode(),
                'website_id' => $store->getWebsiteId(),
                'website_name' => $store->getWebsite()->getName(),
                'group_id' => $store->getGroupId(),
                'group_name' => $store->getGroup()->getName(),
                'store_name' => $store->getName(),
                'sort_order' => $store->getSortOrder(),
                'is_active' => $store->getIsActive()
            );
        }

        return $result;
    }

    /**
     * Create an attribute.
     *
     * For reference, see Mage_Adminhtml_Catalog_Product_AttributeController::saveAction().
     *
     * @param $attributedataInfo
     * @param $values
     * @param $productTypes
     * @param $setInfo
     * @return int|false
     */
    public function createAttribute($attributedataInfo)
    {

        $labelText = trim($attributedataInfo['attribute_label']);
        $attributeCode = trim($attributedataInfo['attribute_name']);
        $productTypes = array();
        if (isset($attributedataInfo['apply_to'])) {
            $productTypes = $attributedataInfo['apply_to'];
        }
        Mage::Log("Creating attribute [$labelText] with code [$attributeCode].");


        switch ($attributedataInfo['frontend_input']) {
            case 'text':
                $backendModel = 'varchar';
                break;
            case 'textarea':
                $backendModel = 'text';
                break;
            case 'date':
                $backendModel = 'datetime';
                break;
            case 'price':
                $backendModel = 'decimal';
                break;
            case 'weee':
                $backendModel = 'text';
                break;
            case 'media_image':
                $backendModel = 'varchar';
                break;
            case 'boolean':
                $backendModel = 'int';
                break;
            case 'multiselect':
                $backendModel = 'text';
                break;
            case 'select':
                $backendModel = 'int';
                break;
            default:
                $backendModel = 'static';
                break;
        }
        if ($attributedataInfo['is_configurable']) {
            $isGlobal = 1;
        } else {
            $isGlobal = 0;
        }
        $data = array(
            'is_global' => $isGlobal,
            'frontend_input' => $attributedataInfo['frontend_input'],
            'default_value_text' => '',
            'default_value_yesno' => '0',
            'default_value_date' => '',
            'default_value_textarea' => '',
            'is_unique' => $attributedataInfo['frontend_input'],
            'is_required' => $attributedataInfo['is_required'],
            'frontend_class' => '',
            'is_searchable' => '1',
            'is_visible_in_advanced_search' => '1',
            'is_comparable' => '1',
            'is_used_for_promo_rules' => $attributedataInfo['is_used_for_promo_rules'],
            'is_html_allowed_on_front' => '1',
            'is_visible_on_front' => '0',
            'used_in_product_listing' => '0',
            'used_for_sort_by' => '0',
            'is_configurable' => $attributedataInfo['is_configurable'],
            'is_filterable' => '0',
            'is_filterable_in_search' => '0',
            'backend_type' => $backendModel,
            'default_value' => '',
            'is_user_defined' => 1
        );
        $helper = Mage::helper('catalog/product');
        $data['source_model'] = $helper->getAttributeSourceModelByInputType($attributedataInfo['frontend_input']);
        $data['backend_model'] = $helper->getAttributeBackendModelByInputType($attributedataInfo['frontend_input']);


        $data['apply_to'] = $productTypes;
        $data['attribute_code'] = $attributeCode;
        $data['frontend_label'] = array(
            0 => $attributedataInfo['frontend_label']
        );


        $model = Mage::getModel('catalog/resource_eav_attribute');

        $model->addData($data);

        /*if ($setInfo !== -1) {
            $model->setAttributeSetId($setInfo['SetID']);
            $model->setAttributeGroupId($setInfo['GroupID']);
        }*/

        $entityTypeID = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
        $model->setEntityTypeId($entityTypeID);

        $model->setIsUserDefined(1);


        try {
            $model->save();
        } catch (Exception $ex) {
            Mage::Log("Attribute [$labelText] could not be saved: " . $ex->getMessage());
            $exceptions[] = "Attribute [$labelText] could not be saved: " . $ex->getMessage();
            return $exceptions;
        }

        $id = $model->getId();

        Mage::Log("Attribute [$labelText] has been saved as ID ($id).");


        return $id;
    }

    public function saveAttributeSetSync($singledata, $isUpdate = false)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {

            $user = Mage::getSingleton('admin/session');
            $user = $user->getUser()->getFirstname();

        } else if (Mage::getSingleton('api/session')) {
            $userApi = Mage::getSingleton('api/session');
            $user = $userApi->getUser()->getUsername();

        } else {
            $user = 'Guest';
        }
        $attributeSetId = '';
        $isNewItemsExists = false;
        $attributeSetResponse = '';
        $mbizAttributeSetId = $singledata['HeaderDetails']['mbiz_obj_id'];
        $attributeSetData = $singledata['ItemDetails'];
        //Mage::Log($singledata['ItemDetails']);
        $entityTypeId = Mage::getModel('catalog/product')->getResource()->getEntityType()->getId(); //product entity type
        if (empty($singledata['HeaderDetails']['obj_id'])) {
            $isNewlyCreated = false;
            $attributeSetName = $singledata['ItemDetails']['attribute_set_name'];
            if (!$attributeSetName) {
                $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                $result['obj_id'] = $attributeSetId;
                $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                $result['sync_status'] = 'Failed';
                $result['exception_desc'] = 'Data Invalid: Attribute Set name Could not be Empty';
                $result['exception_id'] = '';
                return $result;
            }
            $checkAttributeSetExists = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->addFieldToFilter('mbiz_id', $mbizAttributeSetId)->getFirstItem()->getData();
            if ($checkAttributeSetExists) {
                $attributeSetId = $checkAttributeSetExists['magento_id'];
            } else {
                $attributeSet = Mage::getResourceModel('eav/entity_attribute_set_collection')->setEntityTypeFilter($entityTypeId)->addFilter('attribute_set_name', $attributeSetName)->getFirstItem()->getData();
                $attributeSetId = $attributeSet['attribute_set_id'];
                $isNewlyCreated = true;
            }
            $attributeSetModel = Mage::getModel("eav/entity_attribute_set");
            $attributeSetModel->load($attributeSetId);
            if(!$attributeSetModel->getAttributeSetName() && !$isNewlyCreated) {
                unset($attributeSetId);
            }
            if ($attributeSetId) {

                $attributeSetData = $singledata['ItemDetails'];
                if ($singledata['ItemDetails']['attribute_set_name']) {
                    Mage::getModel('eav/entity_attribute_set')
                        ->setEntityTypeId($entityTypeId)->load($attributeSetId)->setAttributeSetName($singledata['ItemDetails']['attribute_set_name'])->setSyncAttrSetCreate(1)->save();
                    $attributeSetResponse = $this->updateAttributeSet($attributeSetId, $attributeSetData, $mbizAttributeSetId, true);

                }
                $statusmsg = "Attributeset updated" . $attributeSet['attribute_set_id'];
            } else {
                try {
                    $defaultattributeset = Mage::getStoreConfig('connector/settings/defaultattributeset');
                    if (!$defaultattributeset) {
                        $defaultattributeset = Mage::getModel('catalog/product')->getDefaultAttributeSetId();
                    }
                    $skeletonID = $defaultattributeset;
                    $attributeSetData = $singledata['ItemDetails'];
                    $newAttributeSet = Mage::getModel('eav/entity_attribute_set')->setEntityTypeId($entityTypeId)->setAttributeSetName($attributeSetName)->setSyncAttrSetCreate(1);
                    if ($attributeSetName && $newAttributeSet->validate()) {
                        $newAttributeSet->save();
                        $isNewlyCreated = true;
                        $newAttributeSet->initFromSkeleton($skeletonID)->save();
                        $attributeSetId = $newAttributeSet->getId();
                        $attributeSetResponse = $this->updateAttributeSet($attributeSetId, $attributeSetData, $mbizAttributeSetId, $isUpdate);
                        $statusmsg = "Attributeset Created" . $attributeSetId;
                    } else {
                        $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                        $result['obj_id'] = $attributeSetId;
                        $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                        $result['sync_status'] = 'Failed';
                        $result['exception_desc'] = 'Data Invalid: Attribute Set name Could not be Empty';
                        $result['exception_id'] = '';
                        return $result;
                    }

                } catch (Exception $ex) {
                    $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
                    $result['obj_id'] = $attributeSetId;
                    $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
                    $result['sync_status'] = 'Failed';
                    $result['exception_desc'] = $ex->getMessage();
                    $result['exception_id'] = '';
                    return $result;
                }
            }
        } else {

            $attributeSetId = $singledata['HeaderDetails']['obj_id'];
            $attributeSetModel = Mage::getModel("eav/entity_attribute_set");
            $attributeSetModel->load($attributeSetId);
            if(!$attributeSetModel->getAttributeSetName()) {
                $exceptionDesc = "AttributeSet with Id ".$attributeSetId." already Deleted in Magento";
                unset($attributeSetId);
            }
            else {
                $attributeSetData = $singledata['ItemDetails'];
                $attributeSetResponse = $this->updateAttributeSet($attributeSetId, $attributeSetData, $mbizAttributeSetId, $isUpdate);
                $statusmsg = "Attributeset updated" . $attributeSetResponse['attribute_set_id'];
            }

        }
        if ($attributeSetId) {
            if ($singledata['ItemDetails']['attribute_set_name']) {
                Mage::getModel('eav/entity_attribute_set')
                    ->setEntityTypeId($entityTypeId)->load($attributeSetId)->setAttributeSetName($singledata['ItemDetails']['attribute_set_name'])->setSyncAttrSetCreate(1)->save();

            }
            $checkAttibuteSetObjectRelation = Mage::helper('microbiz_connector')->checkObjectRelation($attributeSetId, 'AttributeSets');
            $attributeSetRelationinfo = array();
            $attributeSetRelationinfo['magento_id'] = $attributeSetId;
            $attributeSetRelationinfo['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $attributeSetRelationinfo['mbiz_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
            if (!$checkAttibuteSetObjectRelation) {
                $attributeSetRelationinfo['mbiz_version_number'] = $singledata['HeaderDetails']['mbiz_version_number'];
                if ($isUpdate == 'true') {
                    $attributeSetRelationinfo['last_updated_from'] = 'MBIZ';
                } else {
                    $attributeSetRelationinfo['last_updated_from'] = 'MAG';
                }

                $attributeSetRelationinfo['modified_by'] = $user;
                $attributeSetRelationinfo['modified_at'] = Now();
                Mage::getModel('mbizattributeset/mbizattributeset')->setData($attributeSetRelationinfo)->save();
                $isNewItemsExists = true;

            } else {
                $mbizVerNo = $singledata['HeaderDetails']['mbiz_version_number'];

                $attrRelData = Mage::helper('microbiz_connector')->checkIsObjectExists($attributeSetId, 'AttributeSets');
                if (!empty($attrRelData)) {
                    $Id = $attrRelData['id'];
                    $attrModel = Mage::getModel('mbizattributeset/mbizattributeset')->load($Id);

                    $attrModel->setMbizVersionNumber($mbizVerNo);
                    if ($isUpdate == 'true') {
                        $attrModel->setLastUpdateFrom('MBIZ');
                        $attrModel->setMageVersionNumber($attrRelData['mage_version_number'] + 1);
                    } else {
                        $attrModel->setLastUpdateFrom('MAG');
                    }

                    $attrModel->setModifiedBy($user);
                    $attrModel->setModifiedAt(Now());
                    $attrModel->setId($Id)->save();
                }
            }
            $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
            $result['obj_id'] = $attributeSetId;
            $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
            $exceptionsInfo = $attributeSetResponse['exceptions'];


            // if ($isNewItemsExists) {
            $result['attribute_set_info'] = Mage::getModel('Microbiz_Connector_Model_Observer')->saveAttributeSetSyncInfo($attributeSetId);
            //  }

            $result['exception_desc'] = implode(';', $exceptionsInfo);
            $result['sync_status'] = 'Completed';

            $attrSetRelData = Mage::helper('microbiz_connector')->checkIsObjectExists($attributeSetId, 'AttributeSets');
            if (!empty($attrSetRelData)) {
                $result['mage_version_number'] = $attrSetRelData['mage_version_number'];
                $result['mbiz_version_number'] = $attrSetRelData['mbiz_version_number'];
            }

        } else {
            $result['instanceId'] = $singledata['HeaderDetails']['instanceId'];
            $result['obj_id'] = $attributeSetId;
            $result['mbiz_obj_id'] = $singledata['HeaderDetails']['mbiz_obj_id'];
            $result['sync_status'] = 'Failed';
            $result['exception_desc'] = ($exceptionDesc) ? $exceptionDesc : 'Attribute Set Id Not Exists';
            $result['exception_id'] = '';
        }
        return $result;
    }

    public function updateAttributeData($attributeCode, $attributeInformation, $mbizAttributeId, $isUpdate = false)
    {
        try {


            $entityTypeId = Mage::getModel('catalog/product')->getResource()->getEntityType()->getId();
            $loadAttributeByCode = Mage::getModel('eav/entity_attribute')->getCollection()->addFieldToFilter('entity_type_id', $entityTypeId)->addFieldToFilter('attribute_code', $attributeCode)->getFirstItem();
            $attributeModel = Mage::getModel('catalog/resource_eav_attribute');

            $attributeModel->load($loadAttributeByCode->getId());
            $attributeId = $loadAttributeByCode->getId();
            $attributeUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
            $attributeOptions = (isset($attributeInformation['attribute_options'])) ? $attributeInformation['attribute_options'] : null;
            unset($attributeInformation['attribute_options']);

            if ($attributeUpdate->getIsUserDefined()) {
                $attributeInformation['is_configurable'] = (isset($attributeInformation['is_configurable']) && !empty($attributeInformation['is_configurable'])) ? $attributeInformation['is_configurable'] : $attributeUpdate->getIsConfigurable();
                $attributeInformation['is_used_for_promo_rules'] = (isset($attributeInformation['is_used_for_promo_rules']) && !empty($attributeInformation['is_used_for_promo_rules'])) ? $attributeInformation['is_used_for_promo_rules'] : $attributeUpdate->getIsUsedForPromoRules();
                $attributeInformation['is_required'] = (isset($attributeInformation['is_required']) && !empty($attributeInformation['is_required'])) ? $attributeInformation['is_required'] : $attributeUpdate->getIsRequired();
                $attributeInformation['is_unique'] = (isset($attributeInformation['is_unique']) && !empty($attributeInformation['is_unique'])) ? $attributeInformation['is_unique'] : $attributeUpdate->getIsUnique();
                $attributeInformation['source_model'] = $attributeUpdate->getSourceModel();
                //$singledata['ItemDetails']['scope'] =  $attributeUpdate->getScope();
                if (isset($singledata['ItemDetails']['apply_to'])) {
                    $isApplyTo = $singledata['ItemDetails']['apply_to'];
                    $isApplyToArray = array();
                    (is_array($isApplyTo) && ($key = array_search(1, $isApplyTo)) !== FALSE) ? $isApplyToArray[$key] = 'simple' : null;
                    (is_array($isApplyTo) && ($key = array_search(2, $isApplyTo)) !== FALSE) ? $isApplyToArray[$key] = 'configurable' : null;
                    (count($isApplyToArray) == 2) ? $isApplyToArray = array() : null;
                } else {
                    $isApplyToArray = $attributeUpdate->getApplyTo();
                }
                $singledata['ItemDetails']['apply_to'] = $isApplyToArray;
                $singledata['ItemDetails']['apply_to'] = (isset($attributeInformation['apply_to'])) ? $attributeInformation['apply_to'] : $attributeUpdate->getApplyTo();
                switch ($attributeUpdate->getIsGlobal()) {
                    case 0:
                        $attributeInformation['scope'] = 'store';
                        break;
                    case 1:
                        $attributeInformation['scope'] = 'global';
                        break;
                    case 2:
                        $attributeInformation['scope'] = 'website';
                        break;
                    default:
                        $attributeInformation['scope'] = 'global';
                        break;
                }
                $attributeOptions = (isset($attributeInformation['attribute_options'])) ? $attributeInformation['attribute_options'] : null;
                unset($attributeInformation['attribute_options']);
                Mage::getModel('Mage_Catalog_Model_Product_Attribute_Api')->update($attributeId, $attributeInformation);
            }
            $attributeInfo = array();
            if ($attributeOptions) {
                $attributeInfo = $this->updateAttributeOptions($attributeCode, $attributeOptions, $mbizAttributeId, $isUpdate);
            } else {
                $optionsInformation = Mage::getModel('Microbiz_Connector_Model_Entity_Attribute_Option_Api')->items($loadAttributeByCode->getId());
                $attributeInfo = $attributeModel->getData();
                $attributeInfo['attribute_options'] = $optionsInformation;

            }

            /*code to add version starts here.*/
            if ($attributeId) {
                $isObjectExists = Mage::helper('microbiz_connector')->checkIsObjectExists($attributeId, 'Attributes');
                if (!empty($isObjectExists)) {
                    $attributeInfo['mage_version_number'] = $isObjectExists['mage_version_number'];
                    $attributeInfo['mbiz_version_number'] = $isObjectExists['mbiz_version_number'];
                } else {
                    $attributeInfo['mage_version_number'] = '';
                    $attributeInfo['mbiz_version_number'] = '';
                }
            } else {
                $attributeInfo['mage_version_number'] = '';
                $attributeInfo['mbiz_version_number'] = '';
            }
            /*code to add version ends here.*/

        } catch (Mage_Api_Exception $e) {
            $exceptions[] = $e->getCustomMessage();
        }
        return $attributeInfo;
    }

    public function updateAttributeOptions($attributeCode, $attributeOptions, $mbizAttributeId, $isUpdate = false)
    {

        if (Mage::getSingleton('admin/session')->isLoggedIn()) {

            $user = Mage::getSingleton('admin/session');
            $user = $user->getUser()->getFirstname();

        } else if (Mage::getSingleton('api/session')) {
            $userApi = Mage::getSingleton('api/session');
            $user = $userApi->getUser()->getUsername();

        } else {
            $user = 'Guest';
        }
        try {

            $removedAttributeoptions = array();
            $oldAttributeoptions = array();
            $newAttributeoptions = array();
            $storeViewOptions = array();
            $newstoreViewOptions = array();
            $attributeOptionsSorted = array();
            if (isset($attributeOptions['safeOptionIds'])) {
                $safeOptionIds = $attributeOptions['safeOptionIds'];
                unset($attributeOptions['safeOptionIds']);
            } else {
                $safeOptionIds = array();
            }
            foreach ($attributeOptions as $attributeOption) {

                $attributeOptionRelation = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('mbiz_attr_id', $mbizAttributeId)->addFieldToFilter('mbiz_id', $attributeOption['option_id'])->setOrder('id', 'asc')->getFirstItem()->getData();
                if (($attributeOption['status'] == 0 || $attributeOption['status'] == 2) && $attributeOptionRelation['magento_id']) {
                    $removedAttributeoptions[$attributeOptionRelation['magento_id']] = $attributeOptionRelation['magento_id'];
                } else if ($attributeOption['status'] == 1 && $attributeOptionRelation['magento_id']) {
                    $oldAttributeoptions[$attributeOptionRelation['magento_id']] = $attributeOption['value'];
                    $storeViewOptions[$attributeOptionRelation['magento_id']] = $attributeOption['front_end_label'];
                    $sortOrder[$attributeOption['value']] = $attributeOption['sort_order'];
                    $isDefault[$attributeOption['value']] = isset($attributeOption['is_default']) ? $attributeOption['is_default'] : '';

                } else if ($attributeOption['status'] == 1) {
                    $newAttributeoptions[$attributeOption['option_id']] = $attributeOption['value'];
                    $newstoreViewOptions[$attributeOption['option_id']] = $attributeOption['front_end_label'];
                    $sortOrder[$attributeOption['value']] = $attributeOption['sort_order'];
                    $isDefault[$attributeOption['value']] = isset($attributeOption['is_default']) ? $attributeOption['is_default'] : '';
                }


                $attributeOptionsSorted[$attributeOption['option_id']] = $attributeOption;
            }
            ksort($attributeOptionsSorted);

            $allStores = Mage::app()->getStores();
            $optionValues = array();
            $optionOrder = array();
            $entityTypeId = Mage::getModel('catalog/product')->getResource()->getEntityType()->getId();
            $loadAttributeByCode = Mage::getModel('eav/entity_attribute')->getCollection()->addFieldToFilter('entity_type_id', $entityTypeId)->addFieldToFilter('attribute_code', $attributeCode)->getFirstItem();
            if ($isUpdate) {
                foreach ($allStores as $_eachStoreId => $val) {
                    $_storeId = Mage::app()->getStore($_eachStoreId)->getId();
                    $attributeOptionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')->setAttributeFilter($loadAttributeByCode->getId())->setStoreFilter($_storeId)->load();
                    foreach ($attributeOptionCollection as $mageAttrOptions) {
                        if ($mageAttrOptions->getStoreDefaultValue()) {
                            $optionValues[$mageAttrOptions->getOptionId()][$_storeId] = $mageAttrOptions->getValue();
                        }
                        /*
                         if($_storeId == 1) {
                            $magentoStoreViewVal = isset( $storeViewOptions[$mageAttrOptions->getOptionId()]) ? $storeViewOptions[$mageAttrOptions->getOptionId()] : $mageAttrOptions->getValue();
                            $optionValues[$mageAttrOptions->getOptionId()][$_storeId] = $magentoStoreViewVal;
                        }
                        else  {
                            $optionValues[$mageAttrOptions->getOptionId()][$_storeId] = $mageAttrOptions->getValue();
                        }
                         */
                    }
                }
                $attributeOptionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')->setAttributeFilter($loadAttributeByCode->getId())->setStoreFilter(0)->load();
                $existingAttributeOptions = array();
                foreach ($attributeOptionCollection as $mageAttrOptions) {
                    $existingAttributeOptions[$mageAttrOptions->getOptionId()] = $mageAttrOptions->getValue();
                    $attributeOptionNewValue = (isset($oldAttributeoptions[$mageAttrOptions->getOptionId()])) ? $oldAttributeoptions[$mageAttrOptions->getOptionId()] : $mageAttrOptions->getValue();
                    $optionValues[$mageAttrOptions->getOptionId()][0] = $attributeOptionNewValue;
                    $optionOrder[$mageAttrOptions->getOptionId()] = $sortOrder[$attributeOptionNewValue];
                    if ($isDefault[$attributeOptionNewValue]) {
                        $data['default'][0] = $mageAttrOptions->getOptionId();
                    }
                }
                $data['option']['value'] = $optionValues;
                $data['option']['order'] = $optionOrder;
                $newAttributeOptionCount = 1;
                foreach ($newAttributeoptions as $mbizOptionId => $addedAttributeOption) {
                    if (!in_array($addedAttributeOption, $existingAttributeOptions)) {
                        $optionValIndex = 'option_' . $newAttributeOptionCount;
                        //$data['option']['value'][$optionValIndex] = array('0'=>$addedAttributeOption,'1'=>$newstoreViewOptions[$mbizOptionId]);
                        $data['option']['value'][$optionValIndex] = array('0' => $addedAttributeOption);
                        $data['option']['order'][$optionValIndex] = $sortOrder[$addedAttributeOption];
                        if ($isDefault[$addedAttributeOption]) {
                            $data['default'][0] = $optionValIndex;
                        }
                        $newAttributeOptionCount++;
                    } else {
                        // $attributeOptionIdFromArray = array_search($addedAttributeOption,$existingAttributeOptions);
                        $ExistingAttributeOptionRelation = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('mbiz_attr_id', $mbizAttributeId)->getColumnValues('mbiz_id');
                        $ExistingMagentoAttributeOptionRelation = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('mbiz_attr_id', $mbizAttributeId)->getColumnValues('magento_id');
                        $newexistingAttributeOptions = $existingAttributeOptions;
                        foreach ($ExistingMagentoAttributeOptionRelation as $mageRelation) {
                            unset($newexistingAttributeOptions[$mageRelation]);
                        }
                        $attributeOptionIdFromArray = array_search($addedAttributeOption, $newexistingAttributeOptions);
                        if (!in_array($mbizOptionId, $ExistingAttributeOptionRelation) && !$attributeOptionIdFromArray) {
//if($isUpdate && !in_array($mbizOptionId,$ExistingAttributeOptionRelation)) {
                            $optionValIndex = 'option_' . $newAttributeOptionCount;
                            $data['option']['value'][$optionValIndex] = array('0' => $addedAttributeOption, '1' => $newstoreViewOptions[$mbizOptionId]);
                            $data['option']['order'][$optionValIndex] = $sortOrder[$addedAttributeOption];
                            if ($isDefault[$addedAttributeOption]) {
                                $data['default'][0] = $optionValIndex;
                            }
                            $newAttributeOptionCount++;
                        } else {
                            unset($removedAttributeoptions[$attributeOptionIdFromArray]);
                            $data['option']['value'][$attributeOptionIdFromArray] = array('0' => $addedAttributeOption, '1' => $newstoreViewOptions[$mbizOptionId]);
                            $data['option']['order'][$attributeOptionIdFromArray] = $sortOrder[$addedAttributeOption];
                            if ($isDefault[$addedAttributeOption]) {
                                $data['default'][0] = $attributeOptionIdFromArray;
                            }
                        }
                        unset($existingAttributeOptions[$attributeOptionIdFromArray]);


                    }

                }
                $attributeModel = Mage::getModel('catalog/resource_eav_attribute');

                $attributeModel->load($loadAttributeByCode->getId());

                if (!isset($data['default'][0])) {
                    $data['default'][0] = $attributeModel->getDefaultValue();
                }
                $attributeModel->addData($data);
                $attributeModel->save();

                // if($isUpdate) {
                foreach ($removedAttributeoptions as $removedAttributeoption) {
                    Mage::getModel('Mage_Catalog_Model_Product_Attribute_Api')->removeOption($attributeCode, $removedAttributeoption);
                }
            }
            $attributeOptionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')->setAttributeFilter($loadAttributeByCode->getId())->setStoreFilter(0)->load();
            $allOptions = array();
            foreach ($attributeOptionCollection as $attributeOption) {
                $allOptions[$attributeOption->getOptionId()] = $attributeOption->getValue();
            }
            foreach ($attributeOptionsSorted as $attributeOption) {
                if ($attributeOption['status'] == 1) {
                    $optionValue = $attributeOption['value'];
                    $magentoOptionId = array_search($optionValue, $allOptions);
                    $checkAttributeOptionRelation = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('mbiz_id', $attributeOption['option_id'])->addFieldToFilter('mbiz_attr_id', $mbizAttributeId)->setOrder('id', 'asc')->getFirstItem()->getData();

                    if (!$checkAttributeOptionRelation) {
                        if ($magentoOptionId) {
                            $magentoOptionId = $this->checkMagentoOptionRel($magentoOptionId, $optionValue, $allOptions);
                            $attributeOptionRelation = array();
                            $attributeOptionRelation['magento_id'] = $magentoOptionId;
                            $attributeOptionRelation['mbiz_id'] = $attributeOption['option_id'];
                            $attributeOptionRelation['mbiz_attr_id'] = $mbizAttributeId;

                            //version code starts
                            $attrData = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('mbiz_id', $mbizAttributeId)->setOrder('id', 'asc')->getFirstItem()->getData();
                            if (!empty($attrData)) {
                                $attributeOptionRelation['mbiz_version_number'] = $attrData['mbiz_version_number'];
                                $attributeOptionRelation['mage_version_number'] = $attrData['mage_version_number'];
                                if ($isUpdate == 'true') {
                                    $attributeOptionRelation['last_updated_from'] = 'MBIZ';
                                } else {
                                    $attributeOptionRelation['last_updated_from'] = 'MAG';
                                }
                                $attributeOptionRelation['modified_by'] = $user;
                                $attributeOptionRelation['modified_at'] = Now();
                            }
                            //version code ends.

                            Mage::getModel('mbizattributeoption/mbizattributeoption')->setData($attributeOptionRelation)->save();
                        }
                    } else if ($checkAttributeOptionRelation && in_array($attributeOption['option_id'], $safeOptionIds)) {
                        Mage::getModel('mbizattributeoption/mbizattributeoption')->load($checkAttributeOptionRelation['id'])->delete();
                    } else {
                        $attributeOptionRelation = array();
                        $magentoOptionId = $checkAttributeOptionRelation['magento_id'];
                        if ($magentoOptionId) {
                            $attributeOptionRelation['magento_id'] = $magentoOptionId;
                            $attributeOptionRelation['mbiz_id'] = $attributeOption['option_id'];
                            $attributeOptionRelation['mbiz_attr_id'] = $mbizAttributeId;

                            //version code starts
                            $attrData = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('mbiz_id', $mbizAttributeId)->setOrder('id', 'asc')->getFirstItem()->getData();
                            if (!empty($attrData)) {
                                $attributeOptionRelation['mbiz_version_number'] = $attrData['mbiz_version_number'];
                                $attributeOptionRelation['mage_version_number'] = $attrData['mage_version_number'];
                                if ($isUpdate == 'true') {
                                    $attributeOptionRelation['last_updated_from'] = 'MBIZ';
                                } else {
                                    $attributeOptionRelation['last_updated_from'] = 'MAG';
                                }
                                $attributeOptionRelation['modified_by'] = $user;
                                $attributeOptionRelation['modified_at'] = Now();
                            }
                            //version code ends.

                            Mage::getModel('mbizattributeoption/mbizattributeoption')->load($checkAttributeOptionRelation['id'])->setData($attributeOptionRelation)->setId($checkAttributeOptionRelation['id'])->save();
                        }
                    }
                    unset($allOptions[$magentoOptionId]);
                }
                /*else {
                    $checkAttributeOptionRelation = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('mbiz_id', $attributeOption['option_id'])->addFieldToFilter('mbiz_attr_id', $mbizAttributeId)->getData();
                    foreach($checkAttributeOptionRelation as $attributeOptionRelation) {
                        Mage::getModel('mbizattributeoption/mbizattributeoption')->load($attributeOptionRelation['id'])->delete();
                    }
                }*/

            }


            /*
             * Old Attribute options Code
             */
            /*$sortOrder = array();
            $isDefault = array();
            $newAttributeOptions = array();
            foreach ($attributeOptions as $attributeOption) {
                if(is_array($attributeOption) && $attributeOption['value'] != '') {
                    $newAttributeOptions[]           = $attributeOption['value'];
                    $sortOrder[$attributeOption['value']] =  $attributeOption['sort_order'];
                    $isDefault[$attributeOption['value']] = isset($attributeOption['is_default']) ? $attributeOption['is_default'] : '';
                }
                else {
                   // return false;
                }

            }
            $allStores = Mage::app()->getStores();
            $optionValues = array();
            $optionOrder = array();
                $entityTypeId =Mage::getModel('catalog/product')->getResource()->getEntityType()->getId();
               // $entityTypeId = 4;
                $loadAttributeByCode =  Mage::getModel('eav/entity_attribute')->getCollection()->addFieldToFilter('entity_type_id',$entityTypeId)->addFieldToFilter('attribute_code', $attributeCode)->getFirstItem();
            // $loadAttributeByCode       = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeCode);
            foreach ($allStores as $_eachStoreId => $val)
            {
                $_storeId = Mage::app()->getStore($_eachStoreId)->getId();
                $attributeOptionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')->setAttributeFilter($loadAttributeByCode->getId())->setStoreFilter($_storeId)->load();
                foreach($attributeOptionCollection as $mageAttrOptions) {
                    if($mageAttrOptions->getStoreDefaultValue()) {
                        $optionValues[$mageAttrOptions->getOptionId()][$_storeId] = $mageAttrOptions->getValue();
                    }
                }
            }
            $attributeOptionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')->setAttributeFilter($loadAttributeByCode->getId())->setStoreFilter(0)->load();
            $oldAttributeOptions = array();
            foreach($attributeOptionCollection as $mageAttrOptions) {
                //if (in_array($mageAttrOptions->getValue(), $newAttributeOptions)) {

                $optionValues[$mageAttrOptions->getOptionId()][0] = $mageAttrOptions->getValue();
                $optionOrder[$mageAttrOptions->getOptionId()] = $sortOrder[$mageAttrOptions->getValue()];
                $oldAttributeOptions[] = $mageAttrOptions->getValue();
                if($isDefault[$mageAttrOptions->getValue()]) {
                    $data['default'][0] = $mageAttrOptions->getOptionId();
                }
                //}

            }
            $data['option']['value'] = $optionValues;
            $data['option']['order'] = $optionOrder;
            $addedAttributeOptions = array_diff($newAttributeOptions,$oldAttributeOptions);
            $newAttributeOptioncount  = 1;
            foreach($addedAttributeOptions as $addedAttributeOption) {
                $optionValIndex = 'option_'.$newAttributeOptioncount;
                $data['option']['value'][$optionValIndex] = array('0'=>$addedAttributeOption);
                $data['option']['order'][$optionValIndex] = $sortOrder[$addedAttributeOption];
                if($isDefault[$addedAttributeOption]) {
                    $data['default'][0] = $optionValIndex;
                }
                $newAttributeOptioncount++;
            }



            $attributeModel = Mage::getModel('catalog/resource_eav_attribute');

            $attributeModel->load($loadAttributeByCode->getId());

            if(!isset($data['default'][0])) {
                $data['default'][0] = $attributeModel->getDefaultValue();
            }
            $attributeModel->addData($data);
            Mage::Log($data);
            //$attributeModel->setDefault($defaultValue);
            $attributeModel->save();
            $attributeOptionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')->setAttributeFilter($loadAttributeByCode->getId())->setStoreFilter($loadAttributeByCode->getStoreId())->load();

            foreach ($attributeOptionCollection as $attributeExistsOption) {
                //print_r($attributeExistsOption);
                if (!in_array($attributeExistsOption->getValue(), $newAttributeOptions)) {
                    $attributeExistsOption->delete();
                }
            }
            foreach ($attributeOptions as $attributeOption) {
                $optionValue    = $attributeOption['value'];
                $productModel = Mage::getModel('catalog/product');
                $attr = $productModel->getResource()->getAttribute($attributeCode);
                $magentoOptionId = '';
                if ($attr->usesSource() && $optionValue != '') {
                    $magentoOptionId = $attr->getSource()->getOptionId($optionValue);
                }
                $checkAttributeOptionRelation = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('magento_id', $magentoOptionId)->addFieldToFilter('mbiz_attr_id', $mbizAttributeId)->setOrder('id', 'asc')->getData();

                if (!$checkAttributeOptionRelation && $magentoOptionId) {
                    $attributeOptionRelation                 = array();
                    $attributeOptionRelation['magento_id']   = $magentoOptionId;
                    $attributeOptionRelation['mbiz_id']      = $attributeOption['option_id'];
                    $attributeOptionRelation['mbiz_attr_id'] = $mbizAttributeId;
                    Mage::getModel('mbizattributeoption/mbizattributeoption')->setData($attributeOptionRelation)->save();

                }
            }*/
        } catch (Mage_Core_Exception $e) {
            return false;
        }

        $result = array();
        $optionsInformation = Mage::getModel('Microbiz_Connector_Model_Entity_Attribute_Option_Api')->items($loadAttributeByCode->getId());
        $attributeModel = Mage::getModel('catalog/resource_eav_attribute');

        $attributeModel->load($loadAttributeByCode->getId());

        $attributeInformation = $attributeModel->getData();

        /*code to add version starts here.*/
        if ($loadAttributeByCode->getId()) {
            $attributeId = $loadAttributeByCode->getId();
            $isObjectExists = Mage::helper('microbiz_connector')->checkIsObjectExists($attributeId, 'Attributes');
            if (!empty($isObjectExists)) {
                $attributeInformation['mage_version_number'] = $isObjectExists['mage_version_number'];
                $attributeInformation['mbiz_version_number'] = $isObjectExists['mbiz_version_number'];
            } else {
                $attributeInformation['mage_version_number'] = '';
                $attributeInformation['mbiz_version_number'] = '';
            }
        } else {
            $attributeInformation['mage_version_number'] = '';
            $attributeInformation['mbiz_version_number'] = '';
        }
        /*code to add version ends here.*/

        $attributeInformation['attribute_options'] = $optionsInformation;
        $result['attribute_info'] = $attributeInformation;
        return $result;
    }


    public function checkMagentoOptionRel($magentoOptionId, $optionValue, $allAttributeOptions)
    {
        $attributeOptionRelation = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('magento_id', $magentoOptionId)->setOrder('id', 'asc')->getFirstItem()->getData();
        if ($attributeOptionRelation['magento_id']) {
            unset($allAttributeOptions[$attributeOptionRelation['magento_id']]);
            $magentoOptionId = array_search($optionValue, $allAttributeOptions);
            $magentoOptionId = $this->checkMagentoOptionRel($magentoOptionId, $optionValue, $allAttributeOptions);
        }
        return $magentoOptionId;

    }


    /*
    * function for handling fatal errors on Create or update Mbiz data
     */
    public function mbizFatalErrorHandler()
    {

        $error = error_get_last();
        $headerId = Mage::registry('sync_microbiz_status_header_id');
        if ($error['type'] == 1) {


            $errorDesc = "FatalError : " . $error['message'] . "  in file  " . $error['file'] . "  on line number" . $error['line'];

            Mage::helper('microbiz_connector')->createMbizSyncStatus($headerId, 'Failed', $errorDesc);
        }

        return true;
    }

    /*
       * function for handling fatal errors on sending header records or update header status in Magento
    */
    public function mbizUpdateFatalErrorHandler()
    {

        $error = error_get_last();
        if ($error['type'] == 1) {

            $headerId = Mage::registry('sync_magento_status_header_id');
            $errorDesc = "FatalError : " . $error['message'] . "  in file  " . $error['file'] . "  on line number" . $error['line'];
            $origData = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('header_id', $headerId)->getFirstItem()->getData();

            $origData['status'] = "Failed";

            $origData['exception_desc'] = $errorDesc;
            Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($origData['header_id'])->setData($origData)->save();

            Mage::helper('microbiz_connector')->createMbizSyncStatus($headerId, 'Failed', $errorDesc);
        }

        return true;
    }

    public function updateAttributeSetOnImport($attributeSetInfo, $magentoId, $mbizId)
    {
        $attributeSetData['HeaderDetails']['mbiz_obj_id'] = $mbizId;
        $attributeSetData['HeaderDetails']['obj_id'] = $magentoId;
        $attributeSetData['HeaderDetails']['instanceId'] = Mage::helper('microbiz_connector')->getAppInstanceId();;
        $attributeSetData['ItemDetails'] = $attributeSetInfo;
        $this->saveAttributeSetSync($attributeSetData, false);
        return true;
    }

    /**
     * @param $customerId
     * @return string
     * @author KT174
     * @description This method is used to get the customer edit url of admin
     */
    public function getCustomerUrl($customerId)
    {
        if ($customerId) {

            $customer = Mage::getModel('customer/customer')->load($customerId);
            if ($customer->getId()) {
                $customerEditUrl = Mage::helper("adminhtml")->getUrl("connector/adminhtml_connector/mbizgeteditlink/obj_id/" . $customerId . "/obj_model/customer");

                $response = array();
                $response['customer_edit_status'] = 'SUCCESS';
                $response['customer_edit_url'] = $customerEditUrl;
            } else {
                $response = array();
                $response['customer_edit_status'] = 'FAIL';
                $response['customer_edit_url'] = 'Customer No Longer Exists in Magento';
            }

        } else {
            $response = array();
            $response['customer_edit_status'] = 'FAIL';
            $response['customer_edit_url'] = 'No Customer Id Found ';
        }
        return json_encode($response);
    }

    /**
     * @param $productId
     * @return string
     * @author KT174
     * @description This method is used to get the product edit url and product view url.
     */
    public function getProductUrl($productId)
    {
        if ($productId) {


            $product = Mage::getModel('catalog/product')->load($productId);
            //$productViewUrl = $product->getProductUrl();

            if ($product->getId()) {

                $productEditUrl = Mage::helper("adminhtml")->getUrl("connector/adminhtml_connector/mbizgeteditlink/obj_id/" . $productId . "/obj_model/product");


                $product_collection = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToFilter('entity_id', $productId)
                    ->addUrlRewrite();
                $productViewUrl = $product_collection->getFirstItem()->getProductUrl();

                $response = array();
                $response['product_edit_url'] = $productEditUrl;
                $response['product_edit_status'] = 'SUCCESS';
                $visibility = $product->getVisibility();
                if ($visibility == 2 || $visibility == 3 || $visibility == 4) {
                    $response['product_view_status'] = 'SUCCESS';
                    $response['product_view_url'] = $productViewUrl;
                } else {
                    $response['product_view_status'] = 'ERROR';
                    $response['product_view_url'] = 'This Product Cannot be Viewed on Frontend';
                }
            } else {
                $response = array();
                $response['product_edit_url'] = 'Product Not Exists ';
                $response['product_view_url'] = 'Product Not Exists ';
                $response['product_edit_status'] = 'ERROR';
                $response['product_view_status'] = 'ERROR';
            }

        } else {
            $response = array();
            $response['product_edit_url'] = 'No Product Id Found ';
            $response['product_view_url'] = 'No Product Id Found ';
            $response['product_edit_status'] = 'ERROR';
            $response['product_view_status'] = 'ERROR';

        }
        return json_encode($response);

    }

    /**
     * @return string
     * @author KT174
     * @description This method is used to get the frontend base url.
     */
    public function getFrontendBaseUrl()
    {
        $baseUrl = Mage::getBaseUrl();

        return $baseUrl;
    }

    /**
     * @return string
     * @author KT174
     * @description This method is used to get the Admin Base Url with the frontName.
     */
    public function getAdminBaseUrl()
    {

        $frontname = Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
        if ($frontname) {
            $baseUrl = Mage::getBaseUrl();

            $adminUrl = $baseUrl . $frontname;
        }

        return $adminUrl;
    }

    /**
     * @param $orderId
     * @return string
     * @author KT174
     * @description This method is used to get the Order Edit url with the given order id.
     */
    public function getOrderUrl($orderId)
    {
        if ($orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            if ($order->getId()) {
                $orderEditUrl = Mage::helper("adminhtml")->getUrl("connector/adminhtml_connector/mbizgeteditlink/obj_id/" . $orderId . "/obj_model/order");

                $response = array();
                $response['order_edit_status'] = 'SUCCESS';
                $response['order_edit_url'] = $orderEditUrl;
            } else {
                $response = array();
                $response['order_edit_status'] = 'FAIL';
                $response['order_edit_url'] = "Order No Longer Exists in Magento";
            }


        } else {
            $response = array();
            $response['order_edit_status'] = 'SUCCESS';
            $response['order_edit_url'] = 'No Order Id Found ';

        }

        return json_encode($response);

    }

    /*
 * Update Company Currency of Stores Base on Company Id
 * @param $companyId
 * @param $currency currency of the Company
 * @author KT097
 */
    public function updateCompanyCurrency($companyId, $currency)
    {
        $collection = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->getCollection()->addFieldToFilter('company_id', $companyId);
        try {
            foreach ($collection as $store) {
                $store->setCompanyCurrency($currency);
                $store->save();
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return true;
    }

    public function getObjectVersionDetails($objId, $objModel)
    {
        $result = array();

        switch ($objModel) {
            case 'AttributeSets':
                $result = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->addFieldToFilter('magento_id', $objId)->getFirstItem()->getData();
                $attributeInformation = Mage::getModel('Microbiz_Connector_Model_Observer')->saveAttributeSetSyncInfo($objId);
                $mbizVersionInformation = array();
                foreach ($attributeInformation as $attributeGroup) {
                    foreach ($attributeGroup['attributes'] as $attribute) {
                        $mbizVersionInformation['attributes'][$attribute['mbiz_id']]['attribute_id'] = $attribute['attribute_id'];
                        $mbizVersionInformation['attributes'][$attribute['mbiz_id']]['mage_version_number'] = $attribute['mage_version_number'];
                        $mbizVersionInformation['attributes'][$attribute['mbiz_id']]['mbiz_version_number'] = $attribute['mbiz_version_number'];
                        if (isset($attribute['attribute_options'])) {
                            foreach ($attribute['attribute_options'] as $attributeOption) {
                                $mbizVersionInformation['attribute_options'][$attributeOption['mbiz_id']]['option_id'] = $attributeOption['option_id'];
                                $mbizVersionInformation['attribute_options'][$attributeOption['mbiz_id']]['mage_version_number'] = $attributeOption['mage_version_number'];
                                $mbizVersionInformation['attribute_options'][$attributeOption['mbiz_id']]['mbiz_version_number'] = $attributeOption['mbiz_version_number'];
                            }
                        }
                    }
                }
                $result['full_version_info'] = $mbizVersionInformation;
                break;
            case 'Product':
                $product = Mage::getModel('catalog/product')->load($objId);
                if ($product) {
                    $result = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter('magento_id', $objId)->getFirstItem()->getData();
                    $attributeSetId = $product->getAttributeSetId();
                    $result['attribute_set_rel'] = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->addFieldToFilter('magento_id', $attributeSetId)->getFirstItem()->getData();

                }

                break;
            case 'Attributes':
                $result = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $objId)->getFirstItem()->getData();
                $attributeOptions = Mage::getModel('Microbiz_Connector_Model_Entity_Attribute_Option_Api')->items($objId);
                foreach ($attributeOptions as $attributeOption) {
                    $mbizVersionInformation[$attributeOption['mbiz_id']]['option_id'] = $attributeOption['option_id'];
                    $mbizVersionInformation[$attributeOption['mbiz_id']]['mage_version_number'] = $attributeOption['mage_version_number'];
                    $mbizVersionInformation[$attributeOption['mbiz_id']]['mbiz_version_number'] = $attributeOption['mbiz_version_number'];
                }
                $result['attribute_option_rel'] = $mbizVersionInformation;
                break;
        }
        $result['objId'] = $objId;
        $result['objModel'] = $objModel;
        return json_encode($result);
    }

    public function getSkuById($productId)
    {
        $productInfo = Mage::getModel('catalog/product')->load($productId);
        return $productInfo->getSku();
    }

    public function getAttributeSetName($attributeSetId)
    {
        $entityTypeId = Mage::getModel('catalog/product')->getResource()->getEntityType()->getId();
        $attributeSet = Mage::getModel('eav/entity_attribute_set')->setEntityTypeId($entityTypeId)->load($attributeSetId);
        return $attributeSet->getAttributeSetName();
    }

    public function getAttributeInformation($attributeId)
    {
        $attributeInformation = array();
        $attributeModel = Mage::getModel('catalog/resource_eav_attribute');

        $attributeModel->load($attributeId);
        $optionsInformation = Mage::getModel('Microbiz_Connector_Model_Entity_Attribute_Option_Api')->items($attributeId);
        $attributeInformation = $attributeModel->getData();
        if (count($optionsInformation))
            $attributeInformation['attribute_options'] = $optionsInformation;
        return $attributeInformation;
    }

    public function getExtensionVersion()
    {
        return (string)Mage::getConfig()->getNode()->modules->Microbiz_Connector->version;
    }

    /*
     * For getting New Attribute Code for already Existing attribute
     * @param code - attribute Code
     * @return - updated Attribute Code
     */
    public function getUpdatedAttributeCode($code)
    {
        $ascii = NULL;
        $attributeLength = strlen($code);
        for ($i = 0; $i < $attributeLength; $i++) {
            $ascii += ord($code[$i]);
        }
        $myStr = 'mbiz_' . $ascii;
        $appendedString = substr($myStr, 0, 29 - $attributeLength);
        $updatedAttributeCode = $appendedString . '_' . $code;
        $isAttributeExists = Mage::getModel('catalog/resource_eav_attribute')->loadByCode('catalog_product', $updatedAttributeCode);
        if (!$isAttributeExists->getId()) {
            return $updatedAttributeCode;
        } else {
            return $this->getUpdatedAttributeCode($updatedAttributeCode);
        }
    }

    public function getAttributesList()
    {
        $mappedAttributes = array_unique(Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('mbiz_attr_set_id', array('null' => true))->getColumnValues('magento_attr_code'));
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')->addFieldToFilter('is_used_to_create_mapping',1)->addFieldToFilter(array('is_mappable','is_user_defined'), array(1,1))->getItems();
        $attributesData = array();
        foreach ($attributes as $attribute) {
            $attributeData = array();
            $applyTo = $attribute->getApplyTo();
            $isVisible = $attribute->getIsVisible();
            if (!in_array($attribute->getAttributeCode(), $mappedAttributes) && is_array($applyTo) && (empty($applyTo) || in_array("simple", $applyTo) || in_array("configurable", $applyTo))) {
                if ($isVisible == 1 || ($isVisible == 0 && $attribute->getAttributeCode() == 'sync_update_msg')) {
                    $attributeData['code'] = $attribute->getAttributecode();
                    $attributeData['attribute_id'] = $attribute->getAttributeId();
                    $attributeData['label'] = $attribute->getFrontendLabel();
                    $attributeData['frontend_input'] = $attribute->getFrontendInput();
                    $attributeData['source_model'] = $attribute->getSourceModel();
                    $attributesData[$attribute->getAttributeId()] = $attributeData;
                }
            }
        }
        return $attributesData;
    }

    public function getNotMappableAttributes()
    {
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')->addFieldToFilter('is_used_to_create_mapping', 0)->addFieldToFilter('is_mappable', 0)->getItems();
        $attributesData = array();
        foreach ($attributes as $attribute) {
            $attributeRelationData = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $attribute->getAttributeId())->getFirstItem()->getData();
            (count($attributeRelationData) && $attributeRelationData['status'] == 0) ? $attributesData[] = $attribute->getAttributecode() : null;
        }
        return $attributesData;
    }

    public function deleteAttributeMapping($attributeId, $deleteAll = false)
    {
        try {
            $attributeRelationData = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $attributeId);
            //(!$deleteAll) ? $attributeRelationData->addFieldToFilter('mbiz_attr_set_id', array('null' => true)) : null;
            $result = array();
            if ($attributeRelationData) {
                $attributeRelationOptionsData = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $attributeId)->getFirstItem()->getData();
                $attributeRelationData->walk('delete');
                $result['message'] = 'Attribute Mapping deleted in Magento';
                $result['status'] = 'SUCCESS';
            } else {
                $result['message'] = 'Attribute Mapping Not exists in Magento';
                $result['status'] = 'SUCCESS';
            }
            $attributeUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
            $attributeUpdate->setAttributeId($attributeId);
            $attributeUpdate->setIsMappable(0);
            $attributeUpdate->save();
            $attributeOptionsRelationData = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('mbiz_attr_id', $attributeRelationOptionsData['mbiz_id']);
            if ($attributeOptionsRelationData) {
                $attributeOptionsRelationData->walk('delete');
            }
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            $result['status'] = 'FAIL';
        }
        return $result;
    }

    public function updateAttributeMapping($attributeId, $status,$attributeData = array())
    {
        try {
            $attributesRelationData = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('mbiz_attr_set_id', array('null' => true))->addFieldToFilter('magento_id', $attributeId);
            $result = array();
            if ($attributesRelationData) {
                foreach ($attributesRelationData as $attributeRelData) {
                    $attributeRelationData = $attributeRelData->getData();
                    $id = $attributeRelationData['id'];
                    $attributeRelationData['status'] = $status;
                    Mage::getModel('mbizattribute/mbizattribute')->load($id)->setData($attributeRelationData)->setId($id)->save();
                }

                if($status) {
                    /*$resource = Mage::getSingleton('core/resource');
                    $writeConnection = $resource->getConnection('core_write');
                    $updateSql = "update ".$resource->getTableName('catalog_eav_attribute')."  set is_mappable = 1 where attribute_id = ".$attributeId;
                    $writeConnection->query($updateSql);*/
                    $attributeUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
                    $attributeUpdate->setAttributeId($attributeId);
                    $attributeUpdate->setIsMappable(1);
                    $attributeUpdate->save();
                    if(isset($attributeData['attribute_options'])) {
                        $attributeInformation = $this->updateAttributeOptions($attributeRelationData['magento_attr_code'], $attributeData['attribute_options'], $attributeRelationData['mbiz_id'],true);
                        $result['attribute_options'] = $attributeInformation['attribute_info']['attribute_options'];
                    }
                }
                else {
                    $attributesRelationsData = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('mbiz_attr_set_id', array('null' => false))->addFieldToFilter('magento_id', $attributeId);
                    foreach ($attributesRelationsData as $attributeRelDeleteData) {
                        if($attributeRelDeleteData['mbiz_attr_set_id']) {
                            $mbizId = $attributeRelDeleteData['mbiz_id'];
                            $attributeRelDeleteData->delete();
                        }

                    }

                    $attributeOptionsRelationData = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('mbiz_attr_id', $mbizId);
                    if ($attributeOptionsRelationData) {
                        $attributeOptionsRelationData->walk('delete');
                    }
                    $attributeUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
                    $attributeUpdate->setAttributeId($attributeId);
                    $attributeUpdate->setIsMappable(0);
                    $attributeUpdate->save();
                }
                $result['message'] = 'Attribute Mapping Updated in Magento';
                $result['status'] = 'SUCCESS';
            } else {
                $result['message'] = 'Attribute Mapping Not exists in Magento';
                $result['status'] = 'SUCCESS';
            }
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            $result['status'] = 'FAIL';
        }
        return $result;
    }

    public function createAttributeMapping($attributeData)
    {
        try {
            $attributeRelationData = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('mbiz_id', $attributeData['field_id'])->getFirstItem()->getData();
            if (!$attributeRelationData) {
                $attributeRelation = array();
                $attributeRelation['magento_id'] = $attributeData['magento_attribute_id'];
                $attributeRelation['mbiz_id'] = $attributeData['field_id'];
                $attributeRelation['magento_attr_code'] = $attributeData['magento_attribute_code'];
                $attributeRelation['mbiz_attr_code'] = $attributeData['mbiz_attribute_code'];
                Mage::getModel('mbizattribute/mbizattribute')->setData($attributeRelation)->save();
                $result['message'] = 'Attribute Mappings Created in Magento';
                // $result['data'] = Mage::getModel('mbizattribute/mbizattribute')->load($modelId)->getData();
                $attributeUpdate = Mage::getModel('catalog/resource_eav_attribute')->load($attributeRelation['magento_id']);
                $attributeUpdate->setAttributeId($attributeRelation['magento_id']);
                $attributeUpdate->setIsMappable(1);
                $attributeUpdate->save();
                if(isset($attributeData['attribute_options'])) {
                    $attributeInformation = $this->updateAttributeOptions($attributeData['magento_attribute_code'], $attributeData['attribute_options'], $attributeData['field_id'],true);
                    $result['attribute_options'] = $attributeInformation['attribute_info']['attribute_options'];
                }
                /*$resource = Mage::getSingleton('core/resource');
                $writeConnection = $resource->getConnection('core_write');
                $updateSql = "update ".$resource->getTableName('catalog_eav_attribute')."  set is_mappable = 1 where attribute_id = ".$attributeRelation['magento_id'];
               Mage::Log($updateSql,null,'mappable.log');
                $writeConnection->query($updateSql);*/
                /* $attributeData = array();
                 $attributeData['is_mappable'] = 1;
                 Mage::getModel('Mage_Catalog_Model_Product_Attribute_Api')->update($attributeRelation['magento_id'], $attributeData);*/
            } else {
                $result['message'] = 'Attribute Relation Already Exists';
                $result['data'] = $attributeRelationData;
                $result['status'] = 'SUCCESS';
                Mage::Log('else',null,'mappable.log');
            }

        } catch (Exception $e) {
            Mage::Log($e->getMessage(),null,'mappable.log');
            $result['message'] = $e->getMessage();
            $result['status'] = 'FAIL';
        }
        return $result;
    }

    public function getMagentoMappings()
    {
        $magentoMappings = array();
        $magentoMappings['getAttributesList'] = Mage::getModel('Microbiz_Connector_Model_Api')->getAttributesList();
        $magentoMappings['notMappableAttributes'] = Mage::getModel('Microbiz_Connector_Model_Api')->getNotMappableAttributes();
        $magentoMappings['getTaxClasses'] = json_decode(Mage::getModel('Microbiz_Connector_Model_Api')->getTaxClasses(), true);
        $magentoMappings['getTaxRules'] = json_decode(Mage::getModel('Microbiz_Connector_Model_Api')->getTaxRules(), true);
        $magentoMappings['customer_group_list'] = Mage::getModel('Mage_Customer_Model_Group_Api')->items();
        $magentoMappings['category_microbiz_tree'] = Mage::getModel('Microbiz_Connector_Model_Category_Api')->tree();
        return $magentoMappings;
    }
    public function mbizBeginInitialSync()
    {
        Mage::log("came to begin initial sync",null,'beginsync.log');
        Mage::helper('microbiz_connector')->RemoveCaching();
        $configModel = Mage::getStoreConfig('connector/mbiz/initialsync/model');
        $lastInsertId = Mage::getStoreConfig('connector/mbiz/initialsync/lastInsertId');
        $state = Mage::getStoreConfig('connector/mbiz/initialsync/state');
        Mage::log($configModel,null,'beginsync.log');
        Mage::log($lastInsertId,null,'beginsync.log');
        $model = ($configModel) ? $configModel:'AttributeSets';
        $lastInsertId = ($lastInsertId) ? $lastInsertId :0;

        Mage::log("after condition check",null,'beginsync.log');
        Mage::log($lastInsertId,null,'beginsync.log');
        Mage::log($lastInsertId,null,'beginsync.log');
        $response = array();
        $response['model'] = $model;
        $response['last_insert_id'] = $lastInsertId;

        $prodSyncStatus = Mage::getStoreConfig('connector/magtombiz_settings/product_sync_setting');  // 0-do not sync,1 - sync all products, 2- sync enabled products
        $custSyncStatus = Mage::getStoreConfig('connector/magtombiz_settings/customers');   //0 - Do not sync customers,1- sync all customers
        $rootCategory = Mage::getStoreConfig('connector/magtombiz_settings/root_category');
        $syncRules = array();
        $syncRules['product_sync_setting'] = $prodSyncStatus;
        $syncRules['customers'] = $custSyncStatus;


        switch($model)
        {
            case 'AttributeSets' :
                /*Sync all the attributesets to Header Table Starts here.*/
                $entityType = Mage::getModel('catalog/product')->getResource()->getEntityType();
                $attributeSets = Mage::getResourceModel('eav/entity_attribute_set_collection')
                    ->setEntityTypeFilter($entityType->getId());
                if($lastInsertId)
                {
                    $attributeSets->addFieldToFilter('attribute_set_id',array('gt'=>$lastInsertId));
                }

                if(!count($attributeSets))
                {
                    Mage::log("no attributesets found",null,'beginsync.log');
                    $model = 'ProductCategories';
                    $lastInsertId ='1,0';
                    $configData = new Mage_Core_Model_Config();
                    $configData ->saveConfig('connector/mbiz/initialsync/model', $model, 'default', 0);
                    $configData ->saveConfig('connector/mbiz/initialsync/lastInsertId',$lastInsertId, 'default', 0);
                    $configData ->saveConfig('connector/mbiz/initialsync/state', 1, 'default', 0);
                    Mage::log($lastInsertId,null,'beginsync.log');
                }
                break;
            case 'ProductCategories' :
                Mage::log("came to product categories case",null,'beginsync.log');
                Mage::log($lastInsertId,null,'beginsync.log');
                /*Get Selected Category and its Child Categories*/
                $categories = Mage::getModel('catalog/category')
                    ->getCollection()
                    ->addAttributeToSelect('*');
                $lastInsertArray = explode(',',$lastInsertId);
                $level = $lastInsertArray[0];
                $lastInsertCatId = $lastInsertArray[1];
                $categories->addFieldToFilter('level',array('eq'=>$level));
                $categories->addFieldToFilter('parent_id',array('gt'=>0));
                if($lastInsertCatId)
                {
                    $categories->addFieldToFilter('entity_id',array('gt'=>$lastInsertCatId));
                }
                Mage::log("count of categories".count($categories),null,'beginsync.log');
                if(!count($categories))
                {
                    Mage::log("count of categories is nulll".count($categories),null,'beginsync.log');
                    $level = $level + 1;
                    $lastInsertCatId =0;
                    $lastInsertId = $level.','.$lastInsertCatId;
                    Mage::log("level".$level,null,'beginsync.log');
                    Mage::log("lst insert id".$lastInsertId,null,'beginsync.log');
                    $categories = Mage::getModel('catalog/category')
                        ->getCollection()
                        ->addAttributeToSelect('*');
                    $categories->addFieldToFilter('level',array('eq'=>$level))->addFieldToFilter('parent_id',array('gt'=>0))->addFieldToFilter('entity_id',array('gt'=>$lastInsertCatId));

                    Mage::log("count of categories is after level update".count($categories),null,'beginsync.log');

                    if(!count($categories))
                    {
                        if($custSyncStatus >0) {
                            $model = 'Customer';
                            $lastInsertId = 0;
                            $configData = new Mage_Core_Model_Config();
                            $configData ->saveConfig('connector/mbiz/initialsync/model', $model, 'default', 0);
                            $configData ->saveConfig('connector/mbiz/initialsync/lastInsertId',$lastInsertId, 'default', 0);
                            $configData ->saveConfig('connector/mbiz/initialsync/state', 1, 'default', 0);
                        }
                        else {
                            if($prodSyncStatus>0)
                            {
                                $model = 'Product';
                                $lastInsertId = 0;
                                $configData = new Mage_Core_Model_Config();
                                $configData ->saveConfig('connector/mbiz/initialsync/model', $model, 'default', 0);
                                $configData ->saveConfig('connector/mbiz/initialsync/lastInsertId',$lastInsertId, 'default', 0);
                                $configData ->saveConfig('connector/mbiz/initialsync/state', 1, 'default', 0);
                            }
                            else {
                                $model = 'getSyncDetails';
                                $lastInsertId = -1;
                                $configData = new Mage_Core_Model_Config();
                                $configData ->saveConfig('connector/mbiz/initialsync/model', $model, 'default', 0);
                                $configData ->saveConfig('connector/mbiz/initialsync/lastInsertId',$lastInsertId, 'default', 0);
                                $configData ->saveConfig('connector/mbiz/initialsync/state', 2, 'default', 0);
                            }
                        }
                    }

                }
                break;
            case 'Customer' :
                if($custSyncStatus >0)
                {
                    $customers = Mage::getModel('customer/customer')->getCollection();
                    if($lastInsertId)
                    {
                        $customers->addFieldToFilter('entity_id',array('gt'=>$lastInsertId));
                    }

                    if(!count($customers)) {
                        if($prodSyncStatus>0)
                        {
                            $model = 'Product';
                            $lastInsertId = 0;
                            $configData = new Mage_Core_Model_Config();
                            $configData ->saveConfig('connector/mbiz/initialsync/model', $model, 'default', 0);
                            $configData ->saveConfig('connector/mbiz/initialsync/lastInsertId',$lastInsertId, 'default', 0);
                            $configData ->saveConfig('connector/mbiz/initialsync/state', 1, 'default', 0);
                        }
                        else {
                            $model = 'getSyncDetails';
                            $lastInsertId = -1;
                            $configData = new Mage_Core_Model_Config();
                            $configData ->saveConfig('connector/mbiz/initialsync/model', $model, 'default', 0);
                            $configData ->saveConfig('connector/mbiz/initialsync/lastInsertId',$lastInsertId, 'default', 0);
                            $configData ->saveConfig('connector/mbiz/initialsync/state', 2, 'default', 0);
                        }
                    }

                } else {
                    if($prodSyncStatus>0)
                    {
                        $model = 'Product';
                        $lastInsertId = 0;
                        $configData = new Mage_Core_Model_Config();
                        $configData ->saveConfig('connector/mbiz/initialsync/model', $model, 'default', 0);
                        $configData ->saveConfig('connector/mbiz/initialsync/lastInsertId',$lastInsertId, 'default', 0);
                        $configData ->saveConfig('connector/mbiz/initialsync/state', 1, 'default', 0);
                    }
                    else {
                        $model = 'getSyncDetails';
                        $lastInsertId = -1;
                        $configData = new Mage_Core_Model_Config();
                        $configData ->saveConfig('connector/mbiz/initialsync/model', $model, 'default', 0);
                        $configData ->saveConfig('connector/mbiz/initialsync/lastInsertId',$lastInsertId, 'default', 0);
                        $configData ->saveConfig('connector/mbiz/initialsync/state', 2, 'default', 0);
                    }
                }
                break;
            case 'Product' :
                if($prodSyncStatus >0) {
                    $products = Mage::getModel('catalog/product')->getCollection();
                    $products->addAttributeToFilter('type_id',array('in'=>array('simple','configurable')));

                    if($lastInsertId)
                    {
                        $products->addFieldToFilter('entity_id',array('gt'=>$lastInsertId));
                    }

                    if(!count($products))
                    {
                        $model = 'getSyncDetails';
                        $lastInsertId = -1;
                        $configData = new Mage_Core_Model_Config();
                        $configData ->saveConfig('connector/mbiz/initialsync/model', $model, 'default', 0);
                        $configData ->saveConfig('connector/mbiz/initialsync/lastInsertId',$lastInsertId, 'default', 0);
                        $configData ->saveConfig('connector/mbiz/initialsync/state', 2, 'default', 0);
                    }

                }
                else {
                    $model = 'getSyncDetails';
                    $lastInsertId = -1;
                    $configData = new Mage_Core_Model_Config();
                    $configData ->saveConfig('connector/mbiz/initialsync/model', $model, 'default', 0);
                    $configData ->saveConfig('connector/mbiz/initialsync/lastInsertId',$lastInsertId, 'default', 0);
                    $configData ->saveConfig('connector/mbiz/initialsync/state', 2, 'default', 0);
                }
                break;
            case 'getSyncDetails' :
                $lastInsertId = -1;
                break;


        }

        Mage::helper('microbiz_connector')->RemoveCaching();



        Mage::log("before create initial records".$lastInsertId,null,'beginsync.log');
        $response = ($lastInsertId>=0) ? $this->createInitialHeaderRecords($syncRules,$model,$lastInsertId) : $this->extendedgetSyncDetails(1);

        $response = json_decode($response,true);
        if(!empty($response))
        {
            if(array_key_exists('recordsCount',$response))
            {
                $recordsCount = $response['recordsCount'];
                if($recordsCount>0)
                {
                    $response['state']=2;
                    $configData = new Mage_Core_Model_Config();
                    $configData ->saveConfig('connector/mbiz/initialsync/state',$response['state'], 'default', 0);
                }
                else {
                    $response['state']=3;
                    $configData = new Mage_Core_Model_Config();
                    $configData ->saveConfig('connector/mbiz/initialsync/state',$response['state'], 'default', 0);
                }

            }
            Mage::helper('microbiz_connector')->RemoveCaching();
            $response = json_encode($response);
        }
        return $response;

    }

    public function createInitialHeaderRecords($syncRules,$model,$lastInsertId)
    {
        Mage::log("came to createInitialHeaderRecords".$lastInsertId,null,'beginsync.log');
        switch($model)
        {
            case 'AttributeSets' :
                $entityType = Mage::getModel('catalog/product')->getResource()->getEntityType();
                $collection = Mage::getResourceModel('eav/entity_attribute_set_collection')
                    ->setEntityTypeFilter($entityType->getId());
                if($lastInsertId)
                {
                    $collection->addFieldToFilter('attribute_set_id',array('gt'=>$lastInsertId));
                }
                $collection->getSelect()->Order('attribute_set_id ASC')->limit(self::INITIAL_SYNC_LIMIT);
                break;
            case 'ProductCategories' :
                $collection = Mage::getModel('catalog/category')
                    ->getCollection();
                $lastInserArray = explode(',',$lastInsertId);
                $level = $lastInserArray[0];
                $level = ($level) ? $level : '1';
                $lastInserCatId = $lastInserArray[1];
                $collection->addFieldToFilter('level',array('eq'=>$level));
                $collection->addFieldToFilter('parent_id',array('gt'=>0));
                if($lastInserCatId)
                {
                    $collection->addFieldToFilter('entity_id',array('gt'=>$lastInserCatId));
                }
                $collection->getSelect()->limit(self::INITIAL_SYNC_LIMIT);

                Mage::log("came to createInitialHeaderRecords count ".count($collection),null,'beginsync.log');
                if(!count($collection)) {
                    $level = $level + 1;
                    $collection = Mage::getModel('catalog/category')
                        ->getCollection();
                    $collection->addFieldToFilter('level',array('eq'=>$level))->addFieldToFilter('parent_id',array('gt'=>0))->addFieldToFilter('entity_id',array('gt'=>0));
                }
                break;
            case 'Customer' :
                $collection = Mage::getModel('customer/customer')->getCollection();
                if($lastInsertId)
                {
                    $collection->addFieldToFilter('entity_id',array('gt'=>$lastInsertId));
                }
                $collection->getSelect()->limit(self::INITIAL_SYNC_LIMIT);
                break;
            case 'Product' :
                $collection = Mage::getModel('catalog/product')->getCollection();
                $collection->addAttributeToFilter('type_id',array('in'=>array('simple','configurable')));
                if($lastInsertId)
                {
                    $collection->addFieldToFilter('entity_id',array('gt'=>$lastInsertId));
                }
                if(!empty($syncRules)) {
                    $productSyncSetting = $syncRules['product_sync_setting'];
                    if($productSyncSetting==self::SYNC_ENAB_PRODUCTS) //sync enabled products
                    {
                        $collection->addFieldToFilter('status',1);
                    }
                }
                $collection->getSelect()->limit(self::INITIAL_SYNC_LIMIT);
                break;

        }
        Mage::log("came to createInitialHeaderRecords before save initia records ".$lastInsertId,null,'beginsync.log');
        $response = $this->saveInitialSyncRecords($model,$collection,$lastInsertId);

        Mage::log("came to createInitialHeaderRecords after save initia records ",null,'beginsync.log');
        Mage::log($response,null,'beginsync.log');

        return $response;
    }

    public function saveInitialSyncRecords($model,$collection,$lastInsertId)
    {
        $response = array();
        switch($model)
        {
            case 'AttributeSets' :
                if(!empty($collection))
                {
                    foreach($collection as $attribute)
                    {
                        $attributeSetId = $attribute->getAttributeSetId();
                        $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                            ->addFieldToFilter('obj_id',$attributeSetId)
                            ->addFieldToFilter('model_name', 'AttributeSets')
                            ->addFieldToFilter('status', 'Pending')
                            ->addFieldToFilter('is_initial_sync', '1')
                            ->setOrder('header_id','desc')->getData();
                        if(!$isObjectExists)
                        {
                            Mage::helper('microbiz_connector')->mbizInitialSyncHeaderDetails($attributeSetId,'AttributeSets');
                        }
                        $lastInsertId = $attributeSetId;
                    }
                    $response['model'] = $model;
                    $response['last_insert_id'] = $lastInsertId;
                    $response['state'] = 1;
                }
                else {
                    $response['model'] = $model;
                    $response['last_insert_id'] = 0;
                    $response['state'] = 1;
                }

                break;
            case 'ProductCategories' :
                if(!empty($collection))
                {
                    foreach($collection as $category)
                    {
                        $categoryId = $category->getId();
                        $level = $category->getLevel();
                        $parentId = $category->getParentId();
                        if($categoryId>1) {
                            $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                                ->addFieldToFilter('obj_id', $categoryId)
                                ->addFieldToFilter('model_name', 'ProductCategories')
                                ->addFieldToFilter('status', 'Pending')
                                ->addFieldToFilter('is_initial_sync', '1')
                                ->setOrder('header_id','desc')->getData();

                            if(!$isObjectExists)
                            {
                                Mage::helper('microbiz_connector')->mbizInitialSyncHeaderDetails($categoryId,'ProductCategories',$parentId);
                            }

                            $lastInsertId = $level.','.$categoryId;
                        }

                    }
                    $response['model'] = $model;
                    $response['last_insert_id'] = $lastInsertId;
                    $response['state'] = 1;
                }
                else {
                    $response['model'] = $model;
                    $response['last_insert_id'] = 0;
                    $response['state'] = 1;
                }
                break;
            case 'Customer' :
                if(!empty($collection))
                {
                    foreach($collection as $customer)
                    {
                        $customerId = $customer->getId();
                        $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                            ->addFieldToFilter('obj_id', $customerId)
                            ->addFieldToFilter('model_name', 'Customer')
                            ->addFieldToFilter('status', 'Pending')
                            ->addFieldToFilter('is_initial_sync', '1')
                            ->setOrder('header_id','desc')->getData();

                        if(!$isObjectExists)
                        {
                            Mage::helper('microbiz_connector')->mbizInitialSyncHeaderDetails($customerId,'Customer');
                        }
                        $lastInsertId = $customerId;
                    }
                    $response['model'] = $model;
                    $response['last_insert_id'] = $lastInsertId;
                    $response['state'] = 1;
                }
                else {
                    $response['model'] = $model;
                    $response['last_insert_id'] = 0;
                    $response['state'] = 1;
                }
                break;
            case 'Product' :
                if(!empty($collection))
                {

                    foreach($collection as $product)
                    {
                        $productId = $product->getId();
                        $refObjId = $product->getAttributeSetId();


                        $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                            ->addFieldToFilter('obj_id', $productId)
                            ->addFieldToFilter('model_name', 'Product')
                            ->addFieldToFilter('status', 'Pending')
                            ->addFieldToFilter('is_initial_sync', '1')
                            ->setOrder('header_id','desc')->getData();

                        if(!$isObjectExists)
                        {
                            Mage::helper('microbiz_connector')->mbizInitialSyncHeaderDetails($productId,'Product',$refObjId);
                        }
                        $lastInsertId = $productId;


                    }
                    $response['model'] = $model;
                    $response['last_insert_id'] = $lastInsertId;
                    $response['state'] = 1;
                }
                else {
                    $response['model'] = $model;
                    $response['last_insert_id'] = 0;
                    $response['state'] = 1;
                }
                break;


        }
        if(!empty($response))
        {
            $configData = new Mage_Core_Model_Config();
            $configData ->saveConfig('connector/mbiz/initialsync/model', $response['model'], 'default', 0);
            $configData ->saveConfig('connector/mbiz/initialsync/lastInsertId',$response['last_insert_id'], 'default', 0);
            $configData ->saveConfig('connector/mbiz/initialsync/state',$response['state'], 'default', 0);
            Mage::helper('microbiz_connector')->RemoveCaching();
        }

        return json_encode($response);
    }

    /**
     * @param $objectId
     * @author KT174
     * @description This method is to get the attributeSet Information on the Sync Record.
     */
    public function getAttributeSetInfo($objectId,$headerId)
    {
        $AttributeSetInfo = Mage::getModel('Microbiz_Connector_Model_Observer')->saveAttributeSetSyncInfo($objectId);


        /*Inserting the Item Details */

        if(Mage::getSingleton('admin/session')->isLoggedIn()) {

            $user = Mage::getSingleton('admin/session');
            $user = $user->getUser()->getFirstname();

        }

        else if(Mage::getSingleton('api/session'))
        {
            $userApi = Mage::getSingleton('api/session');
            $user = $userApi->getUser()->getUsername();

        }
        else
        {
            $user = 'Guest';
        }
        $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
        foreach($AttributeSetInfo as $key=>$updateditem) {
            $attributeSetInfo['header_id']=$headerId;
            $attributeSetInfo['attribute_id']='';
            $attributeSetInfo['attribute_name']=$key;
            $isItemExists = Mage::getModel('syncitems/syncitems')
                ->getCollection()
                ->addFieldToFilter('header_id', $headerId)
                ->addFieldToFilter('attribute_name', $key)
                ->getFirstItem();

            if(!is_array($updateditem)) {
                $attributeSetInfo['attribute_value']= $updateditem;
            }
            else {
                $attributeSetInfo['attribute_value']= serialize($updateditem);
            }
            $attributeSetInfo['created_by']=$user;
            $attributeSetInfo['created_time']= $date;
            if($isItemExists->getId()) {
                $model = Mage::getModel('syncitems/syncitems')->load($isItemExists->getId());
            }
            else {
                $model = Mage::getModel('syncitems/syncitems');
            }
            $model->setData($attributeSetInfo)->setId($isItemExists->getId())->save();

        }
        return true;
    }

    /**
     * @param $objectId
     * @return mixed
     * @author KT174
     * @description This method is used to get the category info using category id and return the data.
     */
    public function getCategoriesInfo($objectId,$headerId)
    {
        $categoryData = array();
        if($objectId)
        {
            $categoryData = Mage::getModel('catalog/category')->load($objectId)->getData();

            /*Creating Sync Item Records */
            if(Mage::getSingleton('admin/session')->isLoggedIn()) {

                $user = Mage::getSingleton('admin/session');
                $user = $user->getUser()->getFirstname();

            }

            else if(Mage::getSingleton('api/session'))
            {
                $userApi = Mage::getSingleton('api/session');
                $user = $userApi->getUser()->getUsername();

            }
            else
            {
                $user = 'Guest';
            }
            $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));


            foreach($categoryData as $k=>$updateditem) {

                $arrCategoryInfoData = array();
                $arrCategoryInfoData['header_id']=$headerId;
                $isItemExists = Mage::getModel('syncitems/syncitems')
                    ->getCollection()
                    ->addFieldToFilter('header_id', $headerId)
                    ->addFieldToFilter('attribute_name', $k)
                    ->getFirstItem();
                $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
                $code = $eavAttribute->getIdByCode('catalog_category', $k);
                $arrCategoryInfoData['attribute_id']=$code;
                $arrCategoryInfoData['attribute_name']=$k;
                if(!is_array($updateditem)) {
                    $arrCategoryInfoData['attribute_value']= $updateditem;
                }
                else {
                    $arrCategoryInfoData['attribute_value']= serialize($updateditem);
                }
                $arrCategoryInfoData['created_by']=$user;
                $arrCategoryInfoData['created_time']= $date;
                if($isItemExists->getId()) {
                    $model = Mage::getModel('syncitems/syncitems')->load($isItemExists->getId());
                }
                else {
                    $model = Mage::getModel('syncitems/syncitems');
                }
                $model->setData($arrCategoryInfoData)->setId($isItemExists->getId())->save();


            }

        }
        return $categoryData;
    }

    /**
     * @param $objectId
     * @return array|mixed
     * @author KT174
     * @description This method is used to get the customer info using customer id and return the data.
     */
    public function getCustomerInfo($objectId,$headerId)
    {
        $customerData = array();
        if($objectId)
        {
            $customerData = Mage::getModel('customer/customer')->load($objectId)->getData();

            /*Creating Sync Item Records */
            if(Mage::getSingleton('admin/session')->isLoggedIn()) {

                $user = Mage::getSingleton('admin/session');
                $user = $user->getUser()->getFirstname();

            }

            else if(Mage::getSingleton('api/session'))
            {
                $userApi = Mage::getSingleton('api/session');
                $user = $userApi->getUser()->getUsername();

            }
            else
            {
                $user = 'Guest';
            }
            $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));


            foreach($customerData as $key=>$updateditem) {

                $customerinfoData = array();
                $isItemExists = Mage::getModel('syncitems/syncitems')
                    ->getCollection()
                    ->addFieldToFilter('header_id', $headerId)
                    ->addFieldToFilter('attribute_name', $key)
                    ->getFirstItem();
                $attribute_details = Mage::getSingleton("eav/config")->getAttribute('customer', $key);
                $attributeid=$attribute_details['attribute_id'];
                $customerinfoData['header_id']=$headerId;
                $customerinfoData['attribute_id']=$attributeid;
                $customerinfoData['attribute_name']=$key;
                if(!is_array($updateditem))
                {
                    $customerinfoData['attribute_value']= $updateditem;
                }
                else {
                    $customerinfoData['attribute_value']= serialize($updateditem);
                }
                $customerinfoData['created_by']=$user;
                $customerinfoData['created_time']= $date;

                if($isItemExists->getId()) {
                    $model = Mage::getModel('syncitems/syncitems')->load($isItemExists->getId());
                }
                else {
                    $model = Mage::getModel('syncitems/syncitems');
                }
                $model->setData($customerinfoData)->setId($isItemExists->getId())->save();
            }
        }

        return true;
    }

    /**
     * @param $objectId
     * @return array|mixed
     * @author KT174
     * @description This method is used to get the customeraddress info using customer address id and return the data.
     */
    public function getCustomerAddressInfo($objectId,$headerId)
    {
        $customerAddressData = array();
        if($objectId)
        {
            $customerAddressData = Mage::getModel('customer/address')->load($objectId)->getData();
        }
        $customerId = $customerAddressData['parent_id'];
        $customer = Mage::getModel('customer/customer')->load($customerId);

        $isDefaultBilling = ($customer->getDefaultBillingAddress()) ? $customer->getDefaultBillingAddress()->getData('entity_id') : null;
        $isDefaultShipping = ($customer->getDefaultShippingAddress()) ? $customer->getDefaultShippingAddress()->getData('entity_id'): null;

        if($objectId==$isDefaultBilling)
        {
            $customerAddressData['is_default_billing']=1;
        }

        if($objectId==$isDefaultShipping)
        {
            $customerAddressData['is_default_shipping']=1;
        }
        foreach($customer->getAddresses() as $key=>$addressdata) {
            $addressId = $addressdata->getId();
            if(array_key_exists('street',$customerAddressData) && $addressId==$customerAddressData['entity_id'])
            {
                $arrStreetData = $addressdata->getStreet();
                if(count($arrStreetData)>0)
                {
                    foreach($arrStreetData as $sid=>$street)
                    {
                        if($sid==0)
                        {
                            $streetId = 'street1';
                        }
                        else{
                            $streetId = 'street2';
                        }
                        if($street!='')
                        {
                            $customerAddressData[$streetId] = $street;
                        }
                    }
                }
                unset($customerAddressData['street']);
            }

        }
        Mage::log($customerAddressData);
        /*Creating Sync Item Records */
        if(Mage::getSingleton('admin/session')->isLoggedIn()) {

            $user = Mage::getSingleton('admin/session');
            $user = $user->getUser()->getFirstname();

        }

        else if(Mage::getSingleton('api/session'))
        {
            $userApi = Mage::getSingleton('api/session');
            $user = $userApi->getUser()->getUsername();

        }
        else
        {
            $user = 'Guest';
        }
        $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
        foreach($customerAddressData as $k=>$updateditem) {

            $customerinfoData = array();
            $isItemExists = Mage::getModel('syncitems/syncitems')
                ->getCollection()
                ->addFieldToFilter('header_id', $headerId)
                ->addFieldToFilter('attribute_name', $k)
                ->getFirstItem();
            $attribute_details = Mage::getSingleton("eav/config")->getAttribute('customer_address', $k);
            $attributeid=$attribute_details['attribute_id'];
            $customerinfoData['header_id']=$headerId;
            $customerinfoData['attribute_id']=$attributeid;
            $customerinfoData['attribute_name']=$k;
            if(!is_array($updateditem))
            {
                $customerinfoData['attribute_value']= $updateditem;
            }
            else {
                $customerinfoData['attribute_value']= serialize($updateditem);
            }
            $customerinfoData['created_by']=$user;
            $customerinfoData['created_time']= $date;
            if($isItemExists->getId()) {
                $model = Mage::getModel('syncitems/syncitems')->load($isItemExists->getId());
            }
            else {
                $model = Mage::getModel('syncitems/syncitems');
            }
            $model->setData($customerinfoData)->setId($isItemExists->getId())->save();
        }

        return true;
    }

    /**
     * @param $objectId
     * @return array|mixed
     */
    const MAX_QTY_VALUE = 99999999.9999;
    public function getProductInfo($objectId,$headerId)
    {
        $productData = array();
        Mage::log("came to get product info",null,'syncissue.log');
        Mage::log($objectId.'=='.$headerId,null,'syncissue.log');

        if($objectId)
        {
            $product = Mage::getModel('catalog/product')->load($objectId);
            Mage::log("after load product id is ".$product->getId(),null,'syncissue.log');

            /*Updating the Sync to Microbiz fields and store price before syncing into the sync tables starts here.*/

            $product->setSyncPrdCreate(1);
            $product->setSyncStatus(1);
            $product->setPosProductStatus(1);

            $store_price = $product->getStorePrice();
            if (!$store_price) {
                $price = $product->getPrice();
                $product->setStorePrice($price);
            }
            $product->save();
            Mage::log("after save ",null,'syncissue.log');
            /*Updating the Sync to Microbiz fields and store price before syncing into the sync tables ends here.*/
            $product =  Mage::getModel('catalog/product')->load($objectId);
            $productData = $product->toArray();
            Mage::log("after save pdata",null,'syncissue.log');
            Mage::log($productData,null,'syncissue.log');
            $productId = $product->getId();
            Mage::log("after save product id is ".$product->getId(),null,'syncissue.log');

            //form configurable product data and configurable attributes data
            if ($product->getTypeId() == 'configurable' || isset($productData['configurable_products_data']))
            {
                //get configurable product data
                $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                foreach($productAttributeOptions as $productAttributeOption) {
                    $configAttributes[]=$productAttributeOption['attribute_code'];
                }

                $childProducts = Mage::getModel('catalog/product_type_configurable')
                    ->getUsedProducts(null,$product);
                $productData['configurable_attributes_data'] = $productAttributeOptions;

                foreach($childProducts as $child) {
                    $simpleProduct = Mage::getModel('catalog/product')->load($child->getId());
                    $attributes = $simpleProduct->getAttributes();
                    $simpleconfiginfo=array();
                    foreach($configAttributes as $configAttribute) {
                        $attributeValue = null;
                        if(array_key_exists($configAttribute , $attributes)){
                            $attributesobj = $attributes["{$configAttribute}"];
                            $attributeValue = $attributesobj->getFrontend()->getValue($simpleProduct);
                        }
                        $attribute_details = Mage::getSingleton("eav/config")->getAttribute("catalog_product", $configAttribute);
                        $options = $attribute_details->getSource()->getAllOptions(false);
                        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
                        $code = $eavAttribute->getIdByCode('catalog_product', $configAttribute);

                        foreach($options as $option){
                            if($option["label"] == $attributeValue) {
                                $attributeValueIndex=$option["value"];
                            }
                        }

                        $simpleconfiginfo[]=array (
                            'label' => $attributeValue,
                            'attribute_id' => $code,
                            'value_index' => $attributeValueIndex,
                        );
                    }
                    $productData['configurable_products_data'][$child->getId()]=$simpleconfiginfo;
                    $productData['associated_simple_products'][] = $child->getId();
                }



            }

            $cats = $product->getCategoryIds();
            $productData['category_ids'] = $cats;

            $parentIds = array();
            if ($product->getTypeId() == 'simple') {

                $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($productId);
                $productData['parentIds'] = $parentIds;
                $productData['associated_configurable_products'] = $parentIds;
            }

            $store_price = $productData['store_price'];
            if (!$store_price) {
                $productData['store_price'] = $productData['price'];
            }



            //converting stock item array into stock data array

            if (isset($productData['stock_item'])) {
                foreach ($productData['stock_item'] as $key => $value) {
                    $productData['stock_data'][$key] = $value;
                }
            }




            $stockData = $productData['stock_item'];

            if (!isset($stockData['use_config_manage_stock'])) {
                $stockData['use_config_manage_stock'] = 0;
            }
            if (isset($stockData['qty']) && (float) $stockData['qty'] > self::MAX_QTY_VALUE) {
                $stockData['qty'] = self::MAX_QTY_VALUE;
            }
            if (isset($stockData['min_qty']) && (int) $stockData['min_qty'] < 0) {
                $stockData['min_qty'] = 0;
            }
            if (!isset($stockData['is_decimal_divided']) || $stockData['is_qty_decimal'] == 0) {
                $stockData['is_decimal_divided'] = 0;
            }

            unset($productData['stock_item']);
            unset($productData['_cache_editable_attributes']);
            unset($productData['_cache_instance_products']);
            unset($productData['media_attributes']);


            /*Updating Price and Store Prices with tax values starts here*/
            $tax_helper = Mage::getSingleton('tax/calculation');
            $tax_request = $tax_helper->getRateOriginRequest();
            $tax_request->setProductClassId($product->getTaxClassId());
            $tax = $tax_helper->getRate($tax_request);
            $calculator = Mage::getSingleton('tax/calculation');

            $price = $productData['price'];
            $storePrice = $product->getStorePrice();

            if ((!$storePrice || $storePrice<=0) && isset($price)) {
                if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                    $price_excluding_tax = Mage::helper('tax')->getPrice($product, $$price, false);
                    $tax_amount = $calculator->calcTaxAmount($price, $tax, true, true);
                    //$productData['store_price'] = $price_excluding_tax;
                    $productData['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($product->getId(),$price);
                    $productData['tax_amount'] = $tax_amount;
                } else {
                    //$productData['store_price'] = $product->getPrice();
                    $productData['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($product->getId(),$price);
                }
            } else {
                if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                    $price_excluding_tax = Mage::helper('tax')->getPrice($product, $product->getStorePrice(), false);
                    $tax_amount = $calculator->calcTaxAmount($product->getStorePrice(), $tax, true, true);
                    //$productData['store_price'] = $price_excluding_tax;
                    $productData['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($product->getId(),$product->getStorePrice());
                    $productData['tax_amount'] = $tax_amount;
                } else {
                    $productData['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($product->getId(),$product->getStorePrice());
                }
            }
            if(isset($productData['price'])) {
                if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                    $price_excluding_tax = Mage::helper('tax')->getPrice($product, $price, false);
                    $tax_amount = $calculator->calcTaxAmount($price, $tax, true, true);
                    //$productData['price'] = $price_excluding_tax;
                    $productData['price'] = Mage::helper('microbiz_connector')->getIncExlPrice($product->getId(),$price);
                    $productData['price_including_tax'] = $price;
                    $productData['tax_amount'] = $tax_amount;
                } else {
                    //$productData['price'] = $product->getPrice();
                    $productData['price'] = Mage::helper('microbiz_connector')->getIncExlPrice($product->getId(),$price);
                }
            }
            $productData['api_web_price_tax_setting'] = Mage::getStoreConfig('tax/calculation/price_includes_tax');
            /*Updating Price and Store Prices with tax values ends here*/

            /*Creating Sync Item Records */
            if(Mage::getSingleton('admin/session')->isLoggedIn()) {

                $user = Mage::getSingleton('admin/session');
                $user = $user->getUser()->getFirstname();

            }

            else if(Mage::getSingleton('api/session'))
            {
                $userApi = Mage::getSingleton('api/session');
                $user = $userApi->getUser()->getUsername();

            }
            else
            {
                $user = 'Guest';
            }
            $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));

            if(!empty($productData)) {
                foreach($productData as $k=>$value)
                {
                    $productinfoData = array();
                    $isItemExists = Mage::getModel('syncitems/syncitems')
                        ->getCollection()
                        ->addFieldToFilter('header_id', $headerId)
                        ->addFieldToFilter('attribute_name', $k)
                        ->getFirstItem();
                    $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
                    $code = $eavAttribute->getIdByCode('catalog_product', $k);
                    $productinfoData['header_id']=$headerId;
                    $productinfoData['attribute_id']=$code;
                    $productinfoData['attribute_name']=$k;
                    if(!is_array($value))
                    {
                        $productinfoData['attribute_value']= $value;
                    }
                    else {
                        $productinfoData['attribute_value']= serialize($value);
                    }
                    $productinfoData['created_by']=$user;
                    $productinfoData['created_time']= $date;

                    if($isItemExists->getId()) {
                        $model = Mage::getModel('syncitems/syncitems')->load($isItemExists->getId());
                    }
                    else {
                        $model = Mage::getModel('syncitems/syncitems');
                    }
                    $model->setData($productinfoData)->setId($isItemExists->getId())->save();
                }
            }


        }
        return true;
    }

    public function getInitialSyncData($modelname,$objectId,$headerId)
    {
        $itemDetails = false;
        switch($modelname) {
            case 'AttributeSets' :
                $itemDetails = $this->getAttributeSetInfo($objectId,$headerId);
                break;
            case 'ProductCategories' :
                $itemDetails = $this->getCategoriesInfo($objectId,$headerId);
                break;
            case 'Customer' :
                $itemDetails = $this->getCustomerInfo($objectId,$headerId);
                break;
            case 'CustomerAddressMaster' :
                $itemDetails = $this->getCustomerAddressInfo($objectId,$headerId);
                break;
            case 'Product' :
                $itemDetails = $this->getProductInfo($objectId,$headerId);
                break;
        }
        return $itemDetails;

    }

    /**
     * @param null $productId
     * @param array $syncProductAttributes
     * @return array
     * @author KT174
     * @descirption This method is used to get the product attributes and their values and form a validation array for
     * validating the data in microbiz while syncing.
     */
    public function getProductValidateData($productId=null,$syncProductAttributes = array())
    {
        $validateData = array();
        if($productId) {
            $product = Mage::getModel('catalog/product')->load($productId);
            $eavConfig = Mage::getModel('eav/config');
            $defaultProductAttributes = $eavConfig->getEntityAttributeCodes(
                Mage_Catalog_Model_Product::ENTITY,
                $product
            );

            $productAttributes = array_intersect($syncProductAttributes,$defaultProductAttributes);
            if(!empty($productAttributes)) {
                $validateData = Mage::getModel('Microbiz_Connector_Model_Product_Api')->getProductFrontendData($product,$productAttributes);

            }
            else {
                $validateData = array();
            }

        }
        else {
            $validateData = array();
        }

        return $validateData;
    }
}
