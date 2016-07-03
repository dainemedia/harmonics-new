<?php
//Version 142

class Microbiz_Connector_Model_Observer
{

    /**
     * For retriving Product info
     * @param product_id
     * @return array of product information
     * @author KT097
     **/
    public function getProductInfo($productId)
    {

        $cProduct = Mage::getModel('Mage_Catalog_Model_Product_Api')->info($productId);
        $product = Mage::getModel('catalog/product')->load($productId);
        $productinfo = $product->toArray();
        if (isset($cProduct['category_ids']) && count($cProduct['category_ids'])) {
            $productinfo['category_ids'] = serialize($cProduct['category_ids']);
        }
        foreach ($productinfo['stock_item'] as $k => $stockdata) {
            $productinfo[$k] = $stockdata;
        }
        if (empty($productinfo['store_price']) || !isset($productinfo['store_price'])) {
            $productinfo['store_price'] = $productinfo['price'];
        }
        return $productinfo;
    }

    /*
     * for Saving product modified info into sync tables
     * @param productId - Product Id which we are saving modified information
     * @param updated_info updated information of the product
     * @author KT097
    */
    public function setProductSyncInfo($productId, $updated_info, $user = null)
    {
        if ($user == null) {
            if (Mage::getSingleton('admin/session')->isLoggedIn()) {
                $user = Mage::getSingleton('admin/session');
                $user = $user->getUser()->getFirstname();

            } else if (Mage::getSingleton('api/session')) {
                $userApi = Mage::getSingleton('api/session');
                $user = $userApi->getUser()->getUsername();
            } else {
                $user = 'Guest';
            }
        }

        $parentIds = array();

        if (isset($updated_info['parentIds'])) {
            $parentIds = $updated_info['parentIds'];
            unset($updated_info['parentIds']);
        }


        $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));


        $productRelation = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter('magento_id', $productId)
            ->setOrder('id', 'asc')->getData();

        $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('obj_id', $productId)->addFieldToFilter('model_name', 'Product')->addFieldToFilter('status', array('in' => array('Pending', 'Failed')))->setOrder('header_id', 'desc')->getData();

        if ($isObjectExists) {

            $header_id = $isObjectExists[0]['header_id'];

            /*Adding Version Numbers code starts here */

            $objectType = 'Product';
            $prdRel = Mage::helper('microbiz_connector')->checkIsObjectExists($productId, $objectType);

            if (!empty($prdRel)) {
                Mage::log("came to updating the version number in product sync", null, 'relations.log');

                $mageVersionNo = $prdRel['mage_version_number'];
                $mbizVersionNo = $prdRel['mbiz_version_number'];

                Mage::log($header_id, null, 'relations.log');
                Mage::log($mageVersionNo, null, 'relations.log');
                Mage::log($mbizVersionNo, null, 'relations.log');
                $isObjectExists[0]['mage_version_number'] = $mageVersionNo;
                $isObjectExists[0]['mbiz_version_number'] = $mbizVersionNo;
            }

            /*Adding Version Numbers code ends here */

            $isObjectExists[0]['status'] = 'Pending';
            $isObjectExists[0]['ref_obj_id'] = $updated_info['attribute_set_id'];
            Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($header_id)->setData($isObjectExists[0])->save();

            if (count($parentIds)) {
                $isObjectExists[0]['associated_configurable_products'] = serialize($parentIds);

                $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                    ->setData($isObjectExists[0])
                    ->save();
            } else {
                $isObjectExists[0]['associated_configurable_products'] = '';

                $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                    ->setData($isObjectExists[0])
                    ->save();
            }

            /*$origitemsData = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->getData();
            foreach($origitemsData as $origitemData) {
                $itemid=$origitemData['id'];
                $model1 = Mage::getModel('syncitems/syncitems')->load($itemid);
                // deleting the records form item table
                try {
                    $model1->delete();
                } catch (Mage_Core_Exception $e) {
                    $this->_fault('not_deleted', $e->getMessage());
                    // Some errors while deleting.
                }
            }*/

        } else {

            $productData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $productData['model_name'] = 'Product';
            $productData['obj_id'] = $productId;
            $productData['ref_obj_id'] = $updated_info['attribute_set_id'];
            $productData['mbiz_obj_id'] = count($productRelation) ? $productRelation[0]['mbiz_id'] : '';
            $productData['created_by'] = $user;
            if (count($parentIds)) {
                $productData['associated_configurable_products'] = serialize($parentIds);
            }
            $productData['created_time'] = $date;
            /*Adding Version Numbers code starts here */
            $objectType = 'Product';

            $prdRel = Mage::helper('microbiz_connector')->checkIsObjectExists($productId, $objectType);

            if (!empty($attrRel)) {
                $productData['mage_version_number'] = $prdRel['mage_version_number'];
                $productData['mbiz_version_number'] = $prdRel['mbiz_version_number'];
            }

            $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                ->setData($productData)
                ->save();
            $header_id = $model['header_id'];
        }

        foreach ($updated_info as $key => $update) {
            if (is_array($update)) {
                $updated_info[$key] = serialize($update);
            } else {
                $updated_info[$key] = $update;
            }
        }

        foreach ($updated_info as $k => $productinfo) {
            if (is_array($productinfo)) {

                foreach ($productinfo as $key => $value) {
                    if ($k == 'media_gallery' && is_array($value)) {
                        foreach ($value as $valuekey => $value1) {
                            $removeItemifExists = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->addFieldToFilter('attribute_name', $valuekey)->getFirstItem();
                            if ($removeItemifExists->getId()) {
                                $model1 = Mage::getModel('syncitems/syncitems')->load($removeItemifExists->getId());
                                // deleting the records form item table
                                try {
                                    $model1->delete();
                                } catch (Mage_Core_Exception $e) {
                                    $this->_fault('not_deleted', $e->getMessage());
                                    // Some errors while deleting.
                                }
                            }
                            $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
                            $code = $eavAttribute->getIdByCode('catalog_product', $valuekey);
                            $productinfoData['header_id'] = $header_id;
                            $productinfoData['attribute_id'] = $code;
                            $productinfoData['attribute_name'] = $valuekey;
                            $productinfoData['attribute_value'] = $value1;
                            $productinfoData['created_by'] = $user;
                            $productinfoData['created_time'] = $date;
                            $model = Mage::getModel('syncitems/syncitems')
                                ->setData($productinfoData)
                                ->save();
                        }
                    }
                    $removeItemifExists = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->addFieldToFilter('attribute_name', $key)->getFirstItem();
                    if ($removeItemifExists->getId()) {
                        $model1 = Mage::getModel('syncitems/syncitems')->load($removeItemifExists->getId());
                        // deleting the records form item table
                        try {
                            $model1->delete();
                        } catch (Mage_Core_Exception $e) {
                            $this->_fault('not_deleted', $e->getMessage());
                            // Some errors while deleting.
                        }
                    }
                    $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
                    $code = $eavAttribute->getIdByCode('catalog_product', $key);
                    $productinfoData['header_id'] = $header_id;
                    $productinfoData['attribute_id'] = $code;
                    $productinfoData['attribute_name'] = $key;
                    $productinfoData['attribute_value'] = $value;
                    $productinfoData['created_by'] = $user;
                    $productinfoData['created_time'] = $date;
                    Mage::getModel('syncitems/syncitems')
                        ->setData($productinfoData)
                        ->save();
                }
            } else {

                $removeItemifExists = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->addFieldToFilter('attribute_name', $k)->getFirstItem();
                if ($removeItemifExists->getId()) {
                    $model1 = Mage::getModel('syncitems/syncitems')->load($removeItemifExists->getId());
                    // deleting the records form item table
                    try {
                        $model1->delete();
                    } catch (Mage_Core_Exception $e) {
                        $this->_fault('not_deleted', $e->getMessage());
                        // Some errors while deleting.
                    }
                }
                $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
                $code = $eavAttribute->getIdByCode('catalog_product', $k);
                $productinfoData['header_id'] = $header_id;
                $productinfoData['attribute_id'] = $code;
                $productinfoData['attribute_name'] = $k;
                $productinfoData['attribute_value'] = $productinfo;
                $productinfoData['created_by'] = $user;
                $productinfoData['created_time'] = $date;
                Mage::getModel('syncitems/syncitems')
                    ->setData($productinfoData)
                    ->save();
            }
        }
    }

    /*
     * Product save afer function for saving the changes in Sync Tables
     * @param observer object which has information of current Product data and Updated Data
     * @author KT097
    */
    public function ktree_product_save($observer)
    {

        $product = $observer->getEvent()->getProduct();
        $updated_info = $observer->getEvent()->getUpdateditems();
        $productpostdata = $observer->getEvent()->getPostinfo();
        $configurable_productremoved_data = array();
        if (isset($productpostdata['configurable_productremoved_data'])) {

            $configurable_productremoved_data = $productpostdata['configurable_productremoved_data'];
            unset($productpostdata['configurable_productremoved_data']);
        }

        $productinfo = $product->toArray();
        $productId = $productinfo['entity_id'];
        $refproductinfo = $observer->getEvent()->getRefproductinfo();
        if (isset($updated_info['media_gallery'])) {
            $mediacount = count($updated_info['media_gallery']);
            if ($mediacount == 0) {
                unset($updated_info['media_gallery']);
            }
        }
        $parentIds = array();
        if ($product->getTypeId() == 'simple') {

            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($productId);
        }
        if ($product->getTypeId() == 'configurable') {
            $configurable_products_data = $refproductinfo['configurable_products_data'];
        }
        $productRelation = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter('magento_id', $productId)
            ->setOrder('id', 'asc')->getData();
        if (!count($productRelation)) {

            foreach ($productpostdata['stock_data'] as $k1 => $v1) {
                if (!is_array($v1)) {

                    $productpostdata[$k1] = $productpostdata['stock_data'][$k1];

                }
            }
            if (isset($productpostdata['stock_data'])) {
                unset($productpostdata['stock_data']);
            }

            if (isset($productpostdata['media_gallery'])) {
                unset($productpostdata['media_gallery']);
            }
            $cProduct = Mage::getModel('Mage_Catalog_Model_Product_Api')->info($productId);
            if (isset($cProduct['category_ids']) && count($cProduct['category_ids'])) {
                $productpostdata['category_ids'] = serialize($cProduct['category_ids']);
            }
            if ($refproductinfo['is_newly_created']) {
                $getproductinfo = $this->getProductInfo($productId);
                foreach ($getproductinfo as $k => $v) {
                    $refproductinfo[$k] = $v;
                }
            }
            foreach ($refproductinfo as $k => $v) {
                if (is_array($v)) {
                    $productpostdata[$k] = serialize($v);
                } else {
                    $productpostdata[$k] = $v;
                }
            }
            if (isset($refproductinfo['stock_data'])) {
                foreach ($refproductinfo['stock_data'] as $k => $stockdata) {
                    $productpostdata[$k] = $stockdata;
                }
                unset($productpostdata['stock_data']);
            }
            unset($productpostdata['media_gallery']);
            unset($productpostdata['is_newly_created']);
            $updated_info = $productpostdata;
            $updated_info['store_id'] = $productinfo['store_id'];
            $updated_info['attribute_set_id'] = $productinfo['attribute_set_id'];
            $updated_info['type_id'] = $productinfo['type_id'];

        }
        $cProduct = Mage::getModel('Mage_Catalog_Model_Product_Api')->info($productId);

        $productpostdata['category_ids'] = serialize($cProduct['categories']);
        $updated_info['category_ids'] = serialize($cProduct['categories']);

        foreach ($updated_info as $key => $update) {
            if (is_array($update)) {
                $updated_info[$key] = serialize($update);
            } else {
                $updated_info[$key] = $update;
            }
        }
        if (count($updated_info)) {
            if (count($parentIds)) {
                $updated_info['parentIds'] = $parentIds;
            }
            if (isset($updated_info['sku'])) {
                $updated_info['sku'] = $product->getSku();
            }
            //for calculating price Excluding tax

            /* KT097 Code for Store price excluding tax*/
            if (isset($updated_info['store_price'])) {
                $tax_helper = Mage::getSingleton('tax/calculation');
                $tax_request = $tax_helper->getRateOriginRequest();
                $tax_request->setProductClassId($product->getTaxClassId());
                $tax = $tax_helper->getRate($tax_request);
                $calculator = Mage::getSingleton('tax/calculation');
                if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                    $price_excluding_tax = Mage::helper('tax')->getPrice($product, $product->getStorePrice(), false);
                    $tax_amount = $calculator->calcTaxAmount($product->getStorePrice(), $tax, true, true);
                    $updated_info['store_price'] = $price_excluding_tax;
                    $updated_info['tax_amount'] = $tax_amount;
                } else {
                    $updated_info['store_price'] = $product->getStorePrice();
                }
            }
            if (isset($updated_info['price'])) {
                if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                    $price_excluding_tax = Mage::helper('tax')->getPrice($product, $product->getPrice(), false);
                    $tax_amount = $calculator->calcTaxAmount($product->getPrice(), $tax, true, true);
                    $updated_info['price'] = $price_excluding_tax;
                    $updated_info['price_including_tax'] = $product->getPrice();
                    $updated_info['tax_amount'] = $tax_amount;
                } else {
                    $updated_info['price'] = $product->getPrice();
                }
            }
            //for calculating price Excluding tax End
            $updated_info['type_id'] = $product->getTypeId();
            $updated_info['attribute_set_id'] = $product->getAttributeSetId();
            $this->setProductSyncInfo($productId, $updated_info);
        }
        // for configurable child products sync
        if ($product->getTypeId() == 'configurable') {

            if (count($configurable_productremoved_data)) {
                foreach ($configurable_productremoved_data as $configremoveproduct) {
                    $configurable_products_data[$configremoveproduct] = $configremoveproduct;
                }
            }

            foreach ($configurable_products_data as $key => $childProduct) {
                $childproductinfo = $this->getProductInfo($key);
                $child_product_id = $childproductinfo['entity_id'];
                $childproductinfo1 = array();
                $relation_childproduct_data = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter('magento_id', $child_product_id)
                    ->setOrder('id', 'asc')->getData();
                if (!count($relation_childproduct_data)) {
                    unset($childproductinfo['_cache_instance_product_ids']);
                    foreach ($childproductinfo as $key => $update) {
                        if (is_array($update)) {
                            $childproductinfo1[$key] = serialize($update);
                        } else {
                            $childproductinfo1[$key] = $update;
                        }
                    }
                }
                $childPrdParentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($child_product_id);
                $childproductinfo1['parentIds'] = $childPrdParentIds;

                //for calculating price Excluding tax
                $childProductObject = Mage::getModel('catalog/product')->load($child_product_id);
                $tax_helper = Mage::getSingleton('tax/calculation');
                $tax_request = $tax_helper->getRateOriginRequest();
                $tax_request->setProductClassId($childProductObject->getTaxClassId());
                $tax = $tax_helper->getRate($tax_request);
                $calculator = Mage::getSingleton('tax/calculation');
                if (!$childproductinfo1['store_price'] && isset($childproductinfo1['price'])) {
                    if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                        $price_excluding_tax = Mage::helper('tax')->getPrice($childProductObject, $childProductObject->getPrice(), false);
                        $tax_amount = $calculator->calcTaxAmount($childProductObject->getPrice(), $tax, true, true);
                        $childproductinfo1['store_price'] = $price_excluding_tax;
                        $childproductinfo1['tax_amount'] = $tax_amount;
                    } else {
                        $childproductinfo1['store_price'] = $childProductObject->getPrice();
                    }
                } else {
                    if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                        $price_excluding_tax = Mage::helper('tax')->getPrice($childProductObject, $childProductObject->getStorePrice(), false);
                        $tax_amount = $calculator->calcTaxAmount($childProductObject->getStorePrice(), $tax, true, true);
                        $childproductinfo1['store_price'] = $price_excluding_tax;
                        $childproductinfo1['tax_amount'] = $tax_amount;
                    } else {
                        $childproductinfo1['store_price'] = $childProductObject->getStorePrice();
                    }
                }
                if (isset($childproductinfo1['price'])) {
                    if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                        $price_excluding_tax = Mage::helper('tax')->getPrice($childProductObject, $childProductObject->getPrice(), false);
                        $tax_amount = $calculator->calcTaxAmount($childProductObject->getPrice(), $tax, true, true);
                        $childproductinfo1['price'] = $price_excluding_tax;
                        $childproductinfo1['price_including_tax'] = $childProductObject->getPrice();
                        $childproductinfo1['price_tax_amount'] = $tax_amount;
                    } else {
                        $childproductinfo1['price'] = $childProductObject->getPrice();
                    }
                }

                //for calculating price Excluding tax End
                $cats = $childProductObject->getCategoryIds();
                $childproductinfo1['category_ids'] = array_unique($cats);
                $childproductinfo1['type_id'] = $childProductObject->getTypeId();
                $childproductinfo1['attribute_set_id'] = $childProductObject->getAttributeSetId();
                $this->setProductSyncInfo($child_product_id, $childproductinfo1);

            }


        }

        // for configurable child products sync End
    }

    /*
     * Customer save afer function for saving the changes in Sync Tables
     * @param observer object which has information of current customer data and Post data and Previous data
     * @author KT097
    */
    public function customer_save_after($observer)
    {
        Mage::log("came to customer_save_after", null, 'customersync.log');
        $customer = $observer->getEvent()->getCustomer();
        $customersavedinfo = $customer->toArray();
        $updatedcustomerdata = $customer->getData();
        Mage::log($updatedcustomerdata);
        $originalcustomerdata = $customer->getOrigData();
        $relationdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter('magento_id', $customersavedinfo['entity_id'])
            ->setOrder('id', 'asc')->getData();
        if (!count($relationdata)) {
            $update_items = $updatedcustomerdata;
        } else {
            foreach ($updatedcustomerdata as $k => $v) {
                if (!isset($originalcustomerdata[$k]) || $updatedcustomerdata[$k] != $originalcustomerdata[$k]) {
                    $update_items[$k] = $updatedcustomerdata[$k];
                }
            }
        }

        $isDefaultBilling = ($customer->getDefaultBillingAddress()) ? $customer->getDefaultBillingAddress()->getData('entity_id') : null;
        $isDefaultShipping = ($customer->getDefaultShippingAddress()) ? $customer->getDefaultShippingAddress()->getData('entity_id') : null;

        foreach ($customer->getAddresses() as $key => $addressdata) {
            $addressId = $addressdata->getId();
            $addressnew = $addressdata->getData();
            ($addressId == $isDefaultBilling) ? $addressnew['is_default_billing'] = 1 : null;

            ($addressId == $isDefaultShipping) ? $addressnew['is_default_shipping'] = 1 : null;

            $addressold = $addressdata->getOrigData();
            if (!count($addressnew)) {
                $addressupdate_items[$key] = array('entity_id' => $key, 'is_deleted' => 2);
            } else if (!count($relationdata)) {
                $addressupdate_items[$key] = $addressnew;
            } else {
                $relationaddressdata = Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->getCollection()->addFieldToFilter('magento_id', $key)
                    ->setOrder('id', 'asc')->getData();
                if (!count($relationaddressdata)) {
                    $addressupdate_items[$key] = $addressnew;
                } else {
                    foreach ($addressnew as $k => $v) {
                        if (!isset($addressold[$k]) || $addressnew[$k] != $addressold[$k]) {
                            $addressupdate_items[$key][$k] = $addressnew[$k];
                        }
                    }

                    unset($addressupdate_items[$key]['updated_at']);
                    //unset($addressupdate_items[$key]['is_default_shipping']);
                    //unset($addressupdate_items[$key]['is_default_billing']);
                    unset($addressupdate_items[$key]['post_index']);
                    unset($addressupdate_items[$key]['store_id']);
                    unset($addressupdate_items[$key]['store_id']);
                    unset($addressupdate_items[$key]['customer_id']);
                    unset($addressupdate_items[$key]['is_customer_save_transaction']);
                    $addressupdate_items[$key]['entity_id'] = $key;
                }

            }
            if (array_key_exists('street', $addressupdate_items[$key])) {
                $arrStreetData = $addressdata->getStreet();
                if (count($arrStreetData) > 0) {
                    foreach ($arrStreetData as $sid => $street) {
                        if ($sid == 0) {
                            $streetId = 'street1';
                        } else {
                            $streetId = 'street2';
                        }
                        if ($street != '') {
                            $addressupdate_items[$key][$streetId] = $street;
                        }
                    }
                }
                unset($addressupdate_items[$key]['street']);
            }

        }

        unset($update_items['dob_is_formated']);
        unset($update_items['updated_at']);
        unset($update_items['sync_update_msg']);
        if (count($update_items)) {
            $relationcustomerdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter('magento_id', $customersavedinfo['entity_id'])
                ->setOrder('id', 'asc')->getData();
            $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('obj_id', $customersavedinfo['entity_id'])->addFieldToFilter('model_name', 'Customer')->addFieldToFilter('status', array('in' => array('Pending', 'Failed')))->setOrder('header_id', 'desc')->getData();
            if ($isObjectExists) {
                $header_id = $isObjectExists[0]['header_id'];
                $isObjectExists[0]['status'] = 'Pending';
                Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($header_id)->setData($isObjectExists[0])->save();

                $origitemsData = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->getData();
                foreach ($origitemsData as $origitemData) {
                    $itemid = $origitemData['id'];
                    $model1 = Mage::getModel('syncitems/syncitems')->load($itemid);
                    // deleting the records form item table
                    try {
                        $model1->delete();
                    } catch (Mage_Core_Exception $e) {
                        Mage::LogException($e->getMessage());
                        //$this->_fault('not_deleted', $e->getMessage());
                        // Some errors while deleting.
                    }
                }

            } else {
                $user = Mage::getSingleton('admin/session')->getUser()->getFirstname();
                $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                $customerData['model_name'] = 'Customer';
                $customerData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                $customerData['obj_id'] = $customersavedinfo['entity_id'];
                $customerData['mbiz_obj_id'] = $relationcustomerdata[0]['mbiz_id'];
                $customerData['created_by'] = $user;
                $customerData['created_time'] = $date;
                $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                    ->setData($customerData)
                    ->save();
                $header_id = $model['header_id'];
            }

        }

        foreach ($update_items as $key => $updateditem) {
            if (is_array($updateditem)) {
            } else {

                $attribute_details = Mage::getSingleton("eav/config")->getAttribute('customer', $key);
                $attributeid = $attribute_details['attribute_id'];
                $customerinfoData['header_id'] = $header_id;
                $customerinfoData['attribute_id'] = $attributeid;
                $customerinfoData['attribute_name'] = $key;
                $customerinfoData['attribute_value'] = $updateditem;
                $customerinfoData['created_by'] = $user;
                $customerinfoData['created_time'] = $date;
                Mage::getModel('syncitems/syncitems')
                    ->setData($customerinfoData)
                    ->save();
            }
        }
        foreach ($addressupdate_items as $k => $v) {

            if (!count($v)) {
                unset($addressupdate_items[$k]);
            }

        }
        if (count($addressupdate_items)) {
            foreach ($addressupdate_items as $updateditem1) {
                $customerentityid = $updateditem1['entity_id'];
                unset($updateditem1['entity_id']);
                if (count($updateditem1)) {
                    $relationcustomerdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter('magento_id', $customersavedinfo['entity_id'])
                        ->setOrder('id', 'asc')->getData();
                    $relationcustomeraddrdata = Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->getCollection()->addFieldToFilter('magento_id', $customerentityid)
                        ->setOrder('id', 'asc')->getData();
                    $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('obj_id', $customerentityid)->addFieldToFilter('model_name', 'CustomerAddressMaster')->addFieldToFilter('status', array('in' => array('Pending', 'Failed')))->setOrder('header_id', 'desc')->getData();
                    if ($isObjectExists && !isset($updateditem1['is_deleted'])) {
                        $addheader_id = $isObjectExists[0]['header_id'];
                        $isObjectExists[0]['status'] = 'Pending';
                        Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($addheader_id)->setData($isObjectExists[0])->save();

                        $origitemsData = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $addheader_id)->getData();
                        foreach ($origitemsData as $origitemData) {
                            $itemid = $origitemData['id'];
                            $model1 = Mage::getModel('syncitems/syncitems')->load($itemid);
                            // deleting the records form item table
                            try {
                                $model1->delete();
                            } catch (Mage_Core_Exception $e) {
                                $this->_fault('not_deleted', $e->getMessage());
                                // Some errors while deleting.
                            }
                        }

                    } else {
                        $user = Mage::getSingleton('admin/session')->getUser()->getFirstname();
                        $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                        $customerData['model_name'] = 'CustomerAddressMaster';
                        $customerData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                        $customerData['obj_id'] = $customerentityid;
                        if (isset($updateditem1['is_deleted'])) {
                            $customerData['obj_status'] = 2;
                            Mage::helper('microbiz_connector')->deleteAppRelation($customerentityid, 'CustomerAddressMaster');
                        } else {
                            $customerData['obj_status'] = 1;
                        }
                        $customerData['ref_obj_id'] = $customersavedinfo['entity_id'];
                        $customerData['mbiz_obj_id'] = $relationcustomeraddrdata[0]['mbiz_id'];
                        $customerData['mbiz_ref_obj_id'] = $relationcustomerdata[0]['mbiz_id'];
                        $customerData['created_by'] = $user;
                        $customerData['created_time'] = $date;
                        $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                            ->setData($customerData)
                            ->save();
                        $addheader_id = $model['header_id'];
                    }
                }
                if (is_array($updateditem1)) {
                    foreach ($updateditem1 as $updatedkey2 => $updateditem2) {

                        $attribute_details = Mage::getSingleton("eav/config")->getAttribute('customer_address', $updatedkey2);
                        $attributeid = $attribute_details['attribute_id'];
                        $customerinfoData['header_id'] = $addheader_id;
                        $customerinfoData['attribute_id'] = $attributeid;
                        $customerinfoData['attribute_name'] = $updatedkey2;
                        $customerinfoData['attribute_value'] = $updateditem2;
                        $customerinfoData['created_by'] = $user;
                        $customerinfoData['created_time'] = $date;
                        Mage::getModel('syncitems/syncitems')
                            ->setData($customerinfoData)
                            ->save();
                    }
                } else {
                    $attribute_details = Mage::getSingleton("eav/config")->getAttribute('customer', $key);


                    $attributeid = $attribute_details['attribute_id'];
                    $customerinfoData['header_id'] = $addheader_id;
                    $customerinfoData['attribute_id'] = $attributeid;
                    $customerinfoData['attribute_name'] = $key;
                    $customerinfoData['attribute_value'] = $updateditem1;
                    $customerinfoData['created_by'] = $user;
                    $customerinfoData['created_time'] = $date;
                    Mage::getModel('syncitems/syncitems')
                        ->setData($customerinfoData)
                        ->save();
                }
            }
        }
    }

    /**
     * observer for product after delte event
     * @param product object
     * save the product id in sync tables with object status 2
     * @author KT097
     */
    public function ktree_product_delete($observer)
    {
        $product = $observer->getEvent()->getSku();
        $productRelation = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter('magento_id', $product['id'])
            ->setOrder('id', 'asc')->getData();
        $user = Mage::getSingleton('admin/session')->getUser()->getFirstname();
        $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
        $productData['model_name'] = 'Product';
        $productData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
        $productData['obj_id'] = $product['id'];
        $productData['obj_status'] = 2;
        Mage::helper('microbiz_connector')->deleteAppRelation($product['id'], 'Product');
        $productData['mbiz_obj_id'] = $productRelation[0]['mbiz_id'];
        $productData['created_by'] = $user;
        $productData['created_time'] = $date;
        Mage::getModel('extendedmbizconnector/extendedmbizconnector')
            ->setData($productData)
            ->save();
    }

    /**
     * observer for customer after delte
     * @param customer object
     * save the customer id in sync tables with object status 2
     * @author KT097
     */
    public function ktree_customer_delete($observer)
    {

        $customer = $observer->getEvent()->getCustomerdelete();
        $relationcustomerdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter('magento_id', $customer['id'])
            ->setOrder('id', 'asc')->getData();

        $user = Mage::getSingleton('admin/session')->getUser()->getFirstname();
        $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
        $customerData['model_name'] = 'Customer';
        $customerData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
        $customerData['obj_id'] = $customer['id'];
        $customerData['obj_status'] = 2;
        Mage::helper('microbiz_connector')->deleteAppRelation($customer['id'], 'Customer');
        $customerData['mbiz_obj_id'] = $relationcustomerdata[0]['mbiz_id'];
        $customerData['created_by'] = $user;
        $customerData['created_time'] = $date;
        Mage::getModel('extendedmbizconnector/extendedmbizconnector')
            ->setData($customerData)
            ->save();


    }

    /**
     * observer for after delte category
     * @param category object
     * save the category id in sync tables with object status 2
     * @author KT097
     */
    public function ktree_category_delete($observer)
    {

        $data = $observer->getEvent()->getCategory()->getData();
        $user = Mage::getSingleton('admin/session')->getUser()->getFirstname();
        $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
        $categoryRelationModel = Mage::getModel('mbizcategory/mbizcategory')
            ->getCollection()
            ->addFieldToFilter('magento_id', $data['entity_id'])
            ->setOrder('id', 'asc')
            ->getFirstItem()->getData();
        $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
            ->addFieldToFilter('obj_id', $data['entity_id'])
            ->addFieldToFilter('model_name', 'ProductCategories')
            ->addFieldToFilter('status', array('in' => array('Pending', 'Failed')))
            ->setOrder('header_id', 'desc')->getFirstItem()->getData();
        if (count($categoryRelationModel) > 0) {
            $relationId = $categoryRelationModel['id'];
            $relationModel = Mage::getModel('mbizcategory/mbizcategory');
            $relationModel->setId($relationId)->delete();
            $categoryData['model_name'] = 'ProductCategories';
            $categoryData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $categoryData['obj_id'] = $data['entity_id'];
            $categoryData['obj_status'] = 2;
            $categoryData['created_by'] = $user;
            $categoryData['created_time'] = $date;
            if (!$isObjectExists) {
                Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                    ->setData($categoryData)
                    ->save();
            } else {
                $headerId = $isObjectExists['header_id'];
                $isObjectExists['status'] = 'Pending';
                Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($headerId)->setData($isObjectExists)->save();

                Mage::getModel('extendedmbizconnector/extendedmbizconnector')->setId($headerId)->setObjStatus(2)->save();
            }

        } else {
            if ($isObjectExists) {
                $headerId = $isObjectExists['header_id'];
                Mage::getModel('extendedmbizconnector/extendedmbizconnector')->setId($headerId)->setObjStatus(2)->save();
            }
        }

        /*Getting Child Category Ids */

        /*$category = Mage::getModel('catalog/category')->load($data['entity_id']);
        $childCategoryIds = Mage::helper('microbiz_connector')->getChildrenIds($category, true);

        if (!empty($childCategoryIds)) {
            foreach ($childCategoryIds as $categoryId) {

                $isCatObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                    ->addFieldToFilter('obj_id', $categoryId)
                    ->addFieldToFilter('model_name', 'ProductCategories')
                    ->addFieldToFilter('status', array('in' => array('Pending', 'Failed')))
                    ->setOrder('header_id', 'desc')->getFirstItem()->getData();

                $checkCategoryExists = Mage::getModel('mbizcategory/mbizcategory')
                    ->getCollection()
                    ->addFieldToFilter('magento_id', $categoryId)
                    ->setOrder('id', 'asc')
                    ->getFirstItem()->getData();

                if (!empty($checkCategoryExists)) {
                    $categoryRelModel = Mage::getModel('mbizcategory/mbizcategory');
                    $categoryRelModel->setId($checkCategoryExists['id'])->delete();

                    if (!empty($isCatObjectExists)) {
                        $existHeaderId = $isCatObjectExists['header_id'];
                        $headerModel = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($existHeaderId);
                        $headerModel->setObjStatus(2)->setStatus('Pending')->setId($existHeaderId)->save();
                    } else {
                        $headerModel = Mage::getModel('extendedmbizconnector/extendedmbizconnector');

                        $headCateData = array();
                        $headCateData['model_name'] = 'ProductCategories';
                        $headCateData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                        $headCateData['obj_id'] = $categoryId;
                        $headCateData['obj_status'] = 2;
                        $headCateData['created_by'] = $user;
                        $headCateData['created_time'] = $date;

                        $headerModel->setData($headCateData)->save();
                    }
                } else {
                    if (!empty($isCatObjectExists)) {
                        $existHeaderId = $isCatObjectExists['header_id'];
                        $headerModel = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($existHeaderId);
                        $headerModel->setObjStatus(2)->setStatus('Pending')->setId($existHeaderId)->save();
                    }
                }
            }
        }*/
        }


    /**
     * observer for after Save Order
     * @param order object
     * save the order information in sync tables
     * @author KT097
     */
    public function onOrderSave($order)
    {
        //$order = $observer->getEvent()->getOrder();

        $orderinfo = Mage::getModel('sales/order')->loadByIncrementId($order->getIncrementId());
        $adminsession = Mage::getSingleton('admin/session', array('name' => 'adminhtml'));
        $syncOrderToMbiz = Mage::getStoreConfig('connector/settings/syncorders');

        if ($order->getStoreId()) {
            $user = $order->getCustomerFirstname();
        } else if ($adminsession->isLoggedIn()) {
            $user = Mage::getSingleton('admin/session')->getUser()->getUserId();
        } else {
            $user = 'Guest';
        }
        //$user = Mage::getSingleton('admin/session')->getUser()->getFirstname();
        $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('obj_id', $order->getId())->addFieldToFilter('model_name', 'Orders')->addFieldToFilter('status', array('in' => array('Pending', 'Failed')))->setOrder('header_id', 'desc')->getData();
        if (!$isObjectExists && $syncOrderToMbiz) {
            $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
            $orderData['model_name'] = 'Orders';
            $orderData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $orderData['obj_id'] = $order->getId();

            $orderData['mbiz_obj_id'] = '';
            $orderData['created_by'] = $user;
            $orderData['created_time'] = $date;
            Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                ->setData($orderData)
                ->save();
        } else if ($isObjectExists) {
            $header_id = $isObjectExists[0]['header_id'];
            $isObjectExists[0]['status'] = 'Pending';
            Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($header_id)->setData($isObjectExists[0])->save();

        }

        $items = $orderinfo->getAllItems();

        $orderInformation = array();
        $orderHeaderModel = Mage::getModel('saleorderheader/saleorderheader')->getCollection()->addFieldToFilter('order_id', $order->getId())->getData();
        if ($orderHeaderModel) {
            $orderHeaderModelId = $orderHeaderModel[0]['id'];
            $baseCurrencyRate = $orderHeaderModel[0]['base_amount'];
        }

        $orderInformation['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
        $orderInformation['order_id'] = $order->getId();

        $shipmentInfo = Mage::getModel('pickup/pickup')->getCollection()->addFieldToFilter('order_id', $order->getId())->setOrder('id', 'asc')->getData();
        $orderInformation['customer_id'] = $order->getCustomerId();
        $orderInformation['customer_name'] = $order->getCustomerFirstname();
        $orderInformation['customer_phone'] = Mage::getModel('sales/order_address')->getCollection()->addFieldToFilter('parent_id', $order->getId())->addFieldToFilter('address_type', 'shipping')->getFirstItem()->getTelephone();
        $orderType = '';
        if ($shipmentInfo) {
            $orderType = $shipmentInfo[0]['window_type'];
            $orderInformation['order_ship_from_store'] = $shipmentInfo[0]['store'];
            $orderInformation['order_service_window'] = $shipmentInfo[0]['delivery_window'];
            $orderInformation['schedule_date'] = $shipmentInfo[0]['delivery_date'];
            $orderInformation['due_date'] = $shipmentInfo[0]['delivery_date'];

        } else {
            $response = $this->getDefaulrStoreIdFromMbiz($order->getStoreId());
            $defaultStoreFromMbiz = $response['store_id'];
            $orderInformation['order_ship_from_store'] = $defaultStoreFromMbiz;
        }
        if (is_null($orderInformation['order_ship_from_store']) || $orderInformation['order_ship_from_store'] == 0) {
            $response = $this->getDefaulrStoreIdFromMbiz($order->getStoreId());
            $defaultStoreFromMbiz = $response['store_id'];
            $orderInformation['order_ship_from_store'] = $defaultStoreFromMbiz;
        }
        if ($orderType == '' || $orderType == 0) {

            $orderType = 4;
        }
        $orderInformation['order_type'] = $orderType;
        $storeId = $order->getStoreId();

        //$orderInformation['website_id']= Mage::getModel('core/store')->load($storeId)->getWebsiteId();
        if (!$defaultStoreFromMbiz) {
            $response = $this->getDefaulrStoreIdFromMbiz($order->getStoreId());
            $defaultStoreFromMbiz = $response['store_id'];
        }

        $mbizStoreCurrency = Mage::helper('microbiz_connector')->getMbizStoreCurrency($defaultStoreFromMbiz);
        $baseCurrencyCode = $order->getBaseCurrencyCode();
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        $mbizStoreCurrency = (!$mbizStoreCurrency) ? $baseCurrencyCode : $mbizStoreCurrency;
        if ($mbizStoreCurrency && $mbizStoreCurrency == $orderCurrencyCode) {
            $currencyRate = $order->getBaseToOrderRate();
            $orderInformation['base_amount'] = $currencyRate;
            $orderInformation['total_amount'] = $order->getGrandTotal();
            $orderInformation['total_tax'] = $order->getTaxAmount();
            $orderInformation['total_prm_discount'] = $order->getDiscountAmount();
        } else if ($mbizStoreCurrency == $baseCurrencyCode) {
            $currencyRate = $order->getBaseToGlobalRate();
            $orderInformation['base_amount'] = $currencyRate;
            $orderInformation['total_amount'] = $order->getBaseGrandTotal();
            $orderInformation['total_tax'] = $order->getBaseTaxAmount();
            $orderInformation['total_prm_discount'] = $order->getBaseDiscountAmount();
        } else {
            //if order currency and base currency both are not same asStore currency
            if ($orderHeaderModel) {
                $currencyRate = $baseCurrencyRate;
            } else {
                $currencyRate = Mage::helper('microbiz_connector')->getCurrencyRate($baseCurrencyCode, $mbizStoreCurrency);
            }
            //$orderInformation['base_amount'] = Mage::helper('directory')->currencyConvert($order->getBaseSubTotal(), $baseCurrencyCode, $mbizStoreCurrency);
            $orderInformation['base_amount'] = $currencyRate;
            $orderInformation['total_amount'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($order->getBaseGrandTotal(), $currencyRate);
            $orderInformation['total_tax'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($order->getBaseTaxAmount(), $currencyRate);
            $orderInformation['total_prm_discount'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($order->getBaseDiscountAmount(), $currencyRate);
        }

        $orderInformation['magento_store_id'] = $storeId;
        $orderInformation['status_id'] = $order->getStatus();
        $orderInformation['order_placed_via'] = '';
        $orderInformation['priority'] = 1;
        $orderInformation['status'] = 1;

        $orderInformation['overall_shipment_status'] = '';
        $orderInformation['reason_for_cancel'] = $order->getCustomerNote();
        if ($order->getStoreId()) {
            $orderInformation['created_by'] = $order->getCustomerFirstname();
        } else {
            $orderInformation['created_by'] = Mage::getSingleton('admin/session')->getUser()->getUserId();
        }
        $orderInformation['created_time'] = $order->created_at;
        if ($order->getStoreId()) {
            $orderInformation['last_modified_by'] = $order->getCustomerFirstname();
        } else {
            $orderInformation['last_modified_by'] = Mage::getSingleton('admin/session')->getUser()->getUserId();
        }
        if ($orderHeaderModelId) {
            $orderHeaderModelUpdate = Mage::getModel('saleorderheader/saleorderheader')->load($orderHeaderModelId);
            $orderHeaderModelUpdate->setData($orderInformation)->setId($orderHeaderModelId)->save();
        } else {
            if ($syncOrderToMbiz) {
                Mage::getModel('saleorderheader/saleorderheader')
                    ->setData($orderInformation)
                    ->save();
            }

        }
        $arrGiftCardOrderItems = array();
        foreach ($items as $item) {

            $itemInfo = $item->getData();
            $productId = $itemInfo['product_id'];
            $product = Mage::getModel('catalog/product')->load($productId);

            if ($itemInfo['product_type'] == 'configurable') {

                $arrSimpleData = Mage::helper('microbiz_connector')->convertItemPrices($itemInfo, $mbizStoreCurrency, $orderCurrencyCode, $baseCurrencyCode, $currencyRate);
                $arrSimpleData['item_id'] = $itemInfo['item_id'];
                $arrSimpleData['discount_percent'] = $itemInfo['discount_percent'];
                $arrSimpleData['tax_percent'] = $itemInfo['tax_percent'];

                $taxClassId = $product->getData("tax_class_id");
                $taxClass = Mage::getModel('tax/class')->load($taxClassId);
                $taxClassName = $taxClass->getClassName();
                $arrSimpleData['tax_rule_id'] = $taxClassId;
                $arrSimpleData['tax_rule_name'] = $taxClassName;


            }
            if ($itemInfo['product_type'] != 'configurable') {

                if ($itemInfo['parent_item_id']) {
                    if ($itemInfo['parent_item_id'] == $arrSimpleData['item_id']) {
                        $itemInformation['unit_price'] = $arrSimpleData['unit_price'];
                        $itemInformation['item_selling_price'] = $arrSimpleData['item_selling_price'];
                        $itemInformation['total_amount'] = $arrSimpleData['total_amount'];
                        $itemInformation['tax_amount'] = $arrSimpleData['tax_amount'];
                        $itemInformation['total_tax_amount'] = $arrSimpleData['total_tax_amount'];
                        $itemInformation['total_discount_amount'] = $arrSimpleData['total_discount_amount'];
                        $itemInformation['discount_percent'] = $arrSimpleData['discount_percent'];
                        $itemInformation['tax_percent'] = $arrSimpleData['tax_percent'];

                        $itemInformation['tax_rule_id'] = $arrSimpleData['tax_rule_id'];
                        $itemInformation['tax_rule_name'] = $arrSimpleData['tax_rule_name'];
                    } else {
                        $itemInformation = Mage::helper('microbiz_connector')->convertItemPrices($itemInfo, $mbizStoreCurrency, $orderCurrencyCode, $baseCurrencyCode, $currencyRate);
                        $itemInformation['discount_percent'] = $itemInfo['discount_percent'];
                        $itemInformation['tax_percent'] = $itemInfo['tax_percent'];
                        $taxClassId = $product->getData("tax_class_id");
                        $taxClass = Mage::getModel('tax/class')->load($taxClassId);
                        $taxClassName = $taxClass->getClassName();
                        $itemInformation['tax_rule_id'] = $taxClassId;
                        $itemInformation['tax_rule_name'] = $taxClassName;
                    }
                } else {
                    $itemInformation = Mage::helper('microbiz_connector')->convertItemPrices($itemInfo, $mbizStoreCurrency, $orderCurrencyCode, $baseCurrencyCode, $currencyRate);
                    $itemInformation['discount_percent'] = $itemInfo['discount_percent'];
                    $itemInformation['tax_percent'] = $itemInfo['tax_percent'];
                    $taxClassId = $product->getData("tax_class_id");
                    $taxClass = Mage::getModel('tax/class')->load($taxClassId);
                    $taxClassName = $taxClass->getClassName();
                    $itemInformation['tax_rule_id'] = $taxClassId;
                    $itemInformation['tax_rule_name'] = $taxClassName;
                }

                $giftCardSku = Mage::getStoreConfig('connector/settings/giftcardsku');

                if ($itemInfo['product_type'] == 'mbizgiftcard' && $itemInfo['sku'] == $giftCardSku) {
                    $itemInformation['order_line_item_type'] = 2;
                } else {
                    $itemInformation['order_line_item_type'] = 1;
                }
                $itemInformation['order_id'] = $itemInfo['order_id'];
                $itemInformation['order_line_item_id'] = $itemInfo['item_id'];
                $itemInformation['product_id'] = $itemInfo['product_id'];
                $itemInformation['sku'] = $itemInfo['sku'];
                $itemInformation['name'] = $itemInfo['name'];
                $itemInformation['order_ship_from_store'] = $shipmentInfo[0]['store'];
                $itemInformation['line_prd_cat_id'] = '';
                $itemInformation['order_quantity'] = $itemInfo['qty_ordered'];
                $itemInformation['shipped_quantity'] = $itemInfo['qty_shipped'];
                $itemInformation['taxable'] = '';
                $itemInformation['prd_tax_class'] = '';
                $itemInformation['UOM'] = '';

                if ($mbizStoreCurrency && $mbizStoreCurrency == $orderCurrencyCode) {
                    $itemInformation['cost_price'] = $itemInfo['cost'];
                } else if ($mbizStoreCurrency == $baseCurrencyCode) {
                    $itemInformation['cost_price'] = $itemInfo['base_cost'];
                } else {
                    $itemInformation['cost_price'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($itemInfo['base_cost'], $currencyRate);
                }
                //$itemInformation['unit_price_currency'] = $order->getOrderCurrencyCode();
                $itemInformation['unit_price_currency'] = $mbizStoreCurrency;


                $itemInformation['status'] = 1;
                $itemInformation['shipment_status'] = '';

                if ($order->getStoreId()) {
                    $itemInformation['created_by'] = $order->getCustomerFirstname();
                } else {
                    $itemInformation['created_by'] = Mage::getSingleton('admin/session')->getUser()->getUserId();
                }
                $itemInformation['created_time'] = $order->created_at;
                if ($order->getStoreId()) {
                    $itemInformation['last_modified_by'] = $order->getCustomerFirstname();
                } else {
                    $itemInformation['last_modified_by'] = Mage::getSingleton('admin/session')->getUser()->getUserId();
                }
                $orderItemsModel = Mage::getModel('saleorderitem/saleorderitem')->getCollection()->addFieldToFilter('order_id', $itemInformation['order_id'])->addFieldToFilter('sku', $itemInformation['sku'])->addFieldToFilter('order_line_item_id', $itemInfo['item_id'])->getData();
                $orderItemsModelId = $orderItemsModel[0]['id'];
                if ($orderItemsModelId) {
                    $orderItemsModelUpdate = Mage::getModel('saleorderitem/saleorderitem')->load($orderItemsModelId);
                    $orderItemsModelUpdate->setData($itemInformation)->setId($orderItemsModelId)->save();
                } else {
                    if ($syncOrderToMbiz) {
                        Mage::getModel('saleorderitem/saleorderitem')
                            ->setData($itemInformation)
                            ->save();
                    }
                }
                if (!$orderHeaderModel) {
                    $brkupInformation = array();
                    $brkupInformation['order_id'] = $itemInfo['order_id'];
                    $brkupInformation['order_line_itm_num'] = $itemInfo['item_id'];
                    $brkupInformation['selling_price'] = $itemInformation['total_amount'];
                    $brkupInformation['order_line_itm_num'] = $itemInfo['item_id'];
                    //$brkupInformation['document_currency'] = $order->getOrderCurrencyCode();
                    $brkupInformation['document_currency'] = $mbizStoreCurrency;


                    $brkupInformation['brkup_type_id'] = 3;
                    $brkupInformation['discount_amount'] = $itemInformation['total_discount_amount'];
                    $brkupInformation['discount_percent'] = $itemInformation['discount_percent'];
                    if ($syncOrderToMbiz) {
                        Mage::getModel('saleorderbrkup/saleorderbrkup')
                            ->setData($brkupInformation)
                            ->save();

                    }

                    $brkupInformation['discount_amount'] = '';
                    $brkupInformation['discount_percent'] = '';
                    $brkupInformation['tax_percent'] = $itemInformation['tax_percent'];
                    $brkupInformation['tax_amount'] = $itemInformation['tax_amount'];
                    $brkupInformation['tax_rule_id'] = $itemInformation['tax_rule_id'];
                    $brkupInformation['tax_rule_name'] = $itemInformation['tax_rule_name'];
                    $brkupInformation['brkup_type_id'] = 1;
                    $brkupInformation['discount_amount'] = '';
                    $brkupInformation['discount_percent'] = '';
                    if ($syncOrderToMbiz) {
                        Mage::getModel('saleorderbrkup/saleorderbrkup')
                            ->setData($brkupInformation)
                            ->save();
                    }

                }


            }
            /*Saving Item Id in the Array for Gift Card Sale Starts Here.*/
            $giftCardSku = Mage::getStoreConfig('connector/settings/giftcardsku');
            $itemSku = $itemInfo['sku'];
            //Mage::log("came from calling fnc");
            //Mage::log($itemSku);
            if ($itemSku == $giftCardSku) {
                $opts = $item->getData('product_options');
                $itemOpts = unserialize($opts);
                $arrAppliedOptions = $itemOpts['options'][0];
                // Mage::log($arrAppliedOptions);
                if ($arrAppliedOptions['label'] == 'Gift Card' && $arrAppliedOptions['value'] == 'Any Amount') {
                    $arrGiftCardOrderItems['any_amount'] = $itemInfo['item_id'];
                } else {

                    $itemPrice = round($itemInfo['price'], 2);
                    //$itemPrice = number_format((float)$itemPrice, 2, '.', '');
                    //Mage::log("item price".$itemPrice);
                    //Mage::log("item id".$itemInfo['item_id']);
                    $arrGiftCardOrderItems[$itemPrice] = $itemInfo['item_id'];
                }
                //Mage::log($arrGiftCardOrderItems);
            }


            /*Saving Item Id in the Array for Gift Card Sale Ends Here.*/

        }
        /*This code is added by KT174 on 26th march 2014*/
        $this->saveOrderSimpleItemIds($order);
        /*This code is added by KT174 on 26th march 2014*/

        $creditDiscountData = Mage::getSingleton('checkout/session')->getCreditDiscountData();
        $creditDiscountData = unserialize($creditDiscountData);
        if (count($creditDiscountData) > 0) {
            if (is_array($creditDiscountData)) {

                $orderId = $order->getId();

                if ($orderId != 0 || $orderId != '') {
                    foreach ($creditDiscountData as $data) {

                        $arrCreditHistoryData = array();
                        $arrCreditHistoryData['credit_id'] = $data['credit_no'];
                        $arrCreditHistoryData['credit_amt'] = $data['credit_amt'];
                        $arrCreditHistoryData['order_id'] = $orderId;
                        $arrCreditHistoryData['type'] = $data['credit_type'];
                        $arrCreditHistoryData['status'] = 1;
                        Mage::getModel('mbizcreditusage/mbizcreditusage')
                            ->addData($arrCreditHistoryData)
                            ->save();
                    }
                }
            }
        }

        /*This code is added by KT174 to save the Sale Gift Card Data into the Table Starts Here. */
        $buyGiftData = Mage::getSingleton('checkout/session')->getGiftBuyData();
        $buyGiftData = unserialize($buyGiftData);
        if (count($buyGiftData) > 0) {
            // Mage::log("came from calling fnc");
            //Mage::log($arrGiftCardOrderItems);

            $this->saveGiftCardInfo($orderinfo->getId(), $buyGiftData, $arrGiftCardOrderItems);
        }
        /*This code is added by KT174 to save the Sale Gift Card Data into the Table Ends Here. */
        Mage::getSingleton('checkout/session')->unsMultiData();
        Mage::getSingleton('core/session')->unsCreditData();
        Mage::getSingleton('checkout/session')->unsCreditData();
        Mage::getSingleton('checkout/session')->unsCreditFinalData();
        Mage::getSingleton('checkout/session')->unsCreditDiscountData();
        Mage::getSingleton('checkout/session')->unsGiftBuyData();

    }

    /*
     * function for getting default store id from Mbiz
     * return array with storeId
     * @author KT097
     */
    public function getDefaulrStoreIdFromMbiz($storeId)
    {
        $apiInformation = Mage::helper('microbiz_connector')->getApiDetails();
        $instanceId = Mage::helper('microbiz_connector')->getAppInstanceId();
        $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.

        $url = $url . '/index.php/api/getDefaultStoreId?store_id=' . $storeId . '&instance_id=' . $instanceId; // prepare url for the rest call
        $method = 'GET';
        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];

        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: ' . $api_user,
            'X-MBIZPOS-PASSWORD: ' . $api_key
        );

        $data = array();

        $handle = curl_init(); //curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        switch ($method) {
            case 'GET':
                break;

            case 'POST':
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'PUT':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT'); // create product request.
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($handle); // send curl request to microbiz
        $response = json_decode($response, true);
        return $response[0];
    }

    /**
     * observer for after Save Attribute
     * @param attribute object
     * save the attribute information in sync tables
     * @author KT097
     */
    public function onAttributeSave($observer)
    {
        $locale = 'en_US';

// changing locale works!

        Mage::app()->getLocale()->setLocaleCode($locale);

// needed to add this
        Mage::app()->getTranslator()->setLocale($locale);
        Mage::app()->getTranslator()->init('frontend', true);
        Mage::app()->getTranslator()->init('adminhtml', true);
        $afterSaveData = $observer->getEvent()->getAttribute()->getData();

        $beforesaveData = $observer->getEvent()->getAttribute()->getOrigData();
        $attributeId = $afterSaveData['attribute_id'];

        /*Code to Save Magento Version Numbers into the Relation Tables Starts Here.*/
        $attrId = $attributeId;
        $objectType = 'Attributes';
        Mage::log("came to attr  controller", null, 'relations.log');
        Mage::log($attrId, null, 'relations.log');

        $attrRel = Mage::helper('microbiz_connector')->checkIsObjectExists($attrId, $objectType);

        if (!empty($attrRel)) {
            Mage::log($attrRel, null, 'relations.log');

            $Id = $attrRel['id'];
            $magVerNo = $attrRel['mage_version_number'];
            $mbizVerNo = $attrRel['mbiz_version_number'];

            $saveVersion = Mage::helper('microbiz_connector')->saveMageVersions($objectType, $Id, $magVerNo, $mbizVerNo);
        }
        /*Code to Save Magento Version Numbers into the Relation Tables Ends Here.*/

        //Attribute sets with specific attribute
        $setInfo = Mage::getResourceModel('eav/entity_attribute_set')->getSetInfo(array($attributeId));
        $attributeSetIds = array_keys($setInfo[$attributeId]);
        // $modifiedinformation = array_diff($beforesaveData, $afterSaveData);
        $modifiedinformation = $afterSaveData;
        unset($modifiedinformation['form_key']);
        unset($modifiedinformation['modulePrefix']);
        unset($modifiedinformation['store_labels']);
        unset($modifiedinformation['option']);
        if ($afterSaveData['frontend_input'] == 'select' || $afterSaveData['frontend_input'] == 'multiselect') {
            $optionsInformation = Mage::getModel('Microbiz_Connector_Model_Entity_Attribute_Option_Api')->items($attributeId);
            $modifiedinformation['attribute_options'] = serialize($optionsInformation);
            $deletedAttributeOptionIds = array_keys($afterSaveData['option']['delete'], "1");
            unset($modifiedinformation['option']);
            $modifiedinformation['deleted_attribute_options'] = serialize($deletedAttributeOptionIds);
            $mbizAttributeRel = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $attributeId)->getFirstItem()->getData();
            $attributeOptionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')->setAttributeFilter($attributeId)->setStoreFilter(0)->load();
            $allOptionIds = array();
            foreach ($attributeOptionCollection as $attributeOption) {
                $allOptionIds[$attributeOption->getOptionId()] = $attributeOption->getOptionId();
            }
            if ($mbizAttributeRel) {
                if (count($allOptionIds)) {
                    $checkAttributeOptionRelation = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('magento_id', array('nin' => $allOptionIds))->addFieldToFilter('mbiz_attr_id', $mbizAttributeRel['mbiz_id'])->getData();

                } else {
                    $checkAttributeOptionRelation = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('mbiz_attr_id', $mbizAttributeRel['mbiz_id'])->getData();

                }
                foreach ($checkAttributeOptionRelation as $attributeOptionRelation) {
                    Mage::getModel('mbizattributeoption/mbizattributeoption')->load($attributeOptionRelation['id'])->setIsDeleted(1)->setId($attributeOptionRelation['id'])->save();
                }
            }

        }
        $relationAttributeSetdata = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->addFieldToFilter('magento_id', array('in' => $attributeSetIds))
            ->setOrder('id', 'asc')->getData();
        if ($modifiedinformation && count($relationAttributeSetdata)) {

            $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('obj_id', $attributeId)->addFieldToFilter('model_name', 'Attributes')->addFieldToFilter('status', array('in' => array('Pending', 'Failed')))->setOrder('header_id', 'desc')->getData();
            if ($isObjectExists) {
                $header_id = $isObjectExists[0]['header_id'];

                /*Adding Version Numbers code starts here */
                $attrRel = Mage::helper('microbiz_connector')->checkIsObjectExists($attrId, $objectType);
                if (!empty($attrRel)) {
                    Mage::log("came to updating the version number in sync", null, 'relations.log');
                    $mageVersionNo = $attrRel['mage_version_number'];
                    $mbizVersionNo = $attrRel['mbiz_version_number'];

                    Mage::log($header_id, null, 'relations.log');
                    Mage::log($mageVersionNo, null, 'relations.log');
                    Mage::log($mbizVersionNo, null, 'relations.log');
                    $extendedModel = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($header_id);
                    $extendedModel->setMageVersionNumber($mageVersionNo);
                    $extendedModel->setMbizVersionNumber($mbizVersionNo);
                    $extendedModel->save();


                }
                /*Adding Version Numbers code ends here */

                $isObjectExists[0]['status'] = 'Pending';
                Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($header_id)->setData($isObjectExists[0])->save();

                $origitemsData = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->getData();
                foreach ($origitemsData as $origitemData) {
                    $itemid = $origitemData['id'];
                    $model1 = Mage::getModel('syncitems/syncitems')->load($itemid);
                    // deleting the records form item table
                    try {
                        if ($model1->getAttributeName() == 'deleted_attribute_options') {
                            $oldOptionDeletedValues = unserialize($model1->getAttributeValue());
                            $newOptionDeletedValues = unserialize($modifiedinformation['deleted_attribute_options']);
                            $totalDeletedOptions = array_merge($oldOptionDeletedValues, $newOptionDeletedValues);
                            $modifiedinformation['deleted_attribute_options'] = serialize($totalDeletedOptions);
                        }
                        $model1->delete();
                    } catch (Mage_Core_Exception $e) {
                        $this->_fault('not_deleted', $e->getMessage());
                        // Some errors while deleting.
                    }
                }

            } else {
                $user = Mage::getSingleton('admin/session')->getUser()->getFirstname();
                $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                $attributeData['model_name'] = 'Attributes';
                $attributeData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                $attributeData['obj_id'] = $attributeId;
                $attributeData['mbiz_obj_id'] = '';
                $attributeData['created_by'] = $user;
                $attributeData['created_time'] = $date;
                /*Adding Version Numbers code starts here */
                $attrRel = Mage::helper('microbiz_connector')->checkIsObjectExists($attrId, $objectType);
                if (!empty($attrRel)) {
                    $attributeSetData['mage_version_number'] = $attrRel['mage_version_number'];
                    $attributeSetData['mbiz_version_number'] = $attrRel['mbiz_version_number'];
                }
                /*Adding Version Numbers code ends here */
                $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                    ->setData($attributeData)
                    ->save();
                $header_id = $model['header_id'];
            }

            foreach ($modifiedinformation as $key => $updateditem) {
                if (!is_array($updateditem)) {
                    //$attribute_details = Mage::getSingleton("eav/config")->getAttribute('attribute', $key);
                    $attributeid = '';
                    $customerinfoData['header_id'] = $header_id;
                    $customerinfoData['attribute_id'] = $attributeid;
                    $customerinfoData['attribute_name'] = $key;
                    $customerinfoData['attribute_value'] = $updateditem;
                    $customerinfoData['created_by'] = $user;
                    $customerinfoData['created_time'] = $date;
                    Mage::getModel('syncitems/syncitems')
                        ->setData($customerinfoData)
                        ->save();
                }
            }

        }


    }

    /**
     * observer for after Save Attribute
     * @param attribute object
     * save the attribute information in sync tables
     * @author KT097
     */
    public function onAttributeSetSave($observer)
    {
        /*Code to Save Magento Version Numbers into the Relation Tables Starts Here.*/
        $attrSetId = $observer->getEvent()->getAttributeSetId();
        $objectType = 'AttributeSets';

        Mage::log("came to attr set controller", null, 'relations.log');
        Mage::log($attrSetId, null, 'relations.log');

        $attrRel = Mage::helper('microbiz_connector')->checkIsObjectExists($attrSetId, $objectType);

        if (!empty($attrRel)) {
            Mage::log($attrRel, null, 'relations.log');

            $Id = $attrRel['id'];
            $magVerNo = $attrRel['mage_version_number'];
            $mbizVerNo = $attrRel['mbiz_version_number'];

            $saveVersion = Mage::helper('microbiz_connector')->saveMageVersions($objectType, $Id, $magVerNo, $mbizVerNo);
        }
        /*Code to Save Magento Version Numbers into the Relation Tables Ends Here.*/
        $user = Mage::getSingleton('admin/session')->getUser()->getFirstname();

        $attributesetId = $observer->getEvent()->getAttributeSetId();
        $attributesetName = $observer->getEvent()->getAttributeSetName();

        $url = Mage::getUrl("connector/sync_attributeset/saveSyncRecords/", array("id" => $attributesetId));

        Mage::helper('microbiz_connector/ERunActions')->touchUrl($url, array('attribute_set_id' => $attributesetId, 'attribute_set_name' => $attributesetName, 'user' => $user));
    }

    /**
     * observer for after save Attributeset
     * @param attributeSetId
     * save the attributeset info in sync tables
     * @author KT097
     */
    public function saveAttributeSetSyncInfo($attributesetId)
    {
        $locale = 'en_US';

        // changing locale works!

        Mage::app()->getLocale()->setLocaleCode($locale);

        // needed to add this
        Mage::app()->getTranslator()->setLocale($locale);
        Mage::app()->getTranslator()->init('frontend', true);
        Mage::app()->getTranslator()->init('adminhtml', true);
        $attributeSetInformation = Mage::getModel('Microbiz_Connector_Model_Product_Attribute_Group_Api')->items($attributesetId);

        $attributeSetModel = Mage::getModel("eav/entity_attribute_set");
        $attributeSetModel->load($attributesetId);
        $attributesetName = $attributeSetModel->getAttributeSetName();
        $attributeSetInformation['attribute_set_name'] = $attributesetName;
        return $attributeSetInformation;
    }

    /**
     * @param $observer contains all the event information of an order.
     * @description: This method is used to get the order items and save the item information like orderId,productId,
     * orderItemId,sku into the sync table mbz_sales_flat_order_item. If the order has other than simple products then
     * we are saving its simple product Id as productId and its parent_item_id as orderItemId into the sync table.
     * @author: KT174
     */

    public function saveOrderSimpleItemIds($order)
    {

        $orderInfo = Mage::getModel('sales/order')->loadByIncrementId($order->getIncrementId());
        $items = $orderInfo->getAllItems();


        foreach ($items as $item) {

            $itemInfo = $item->getData();

            $arrOrderInfo = array();
            $arrOrderInfo['order_id'] = $itemInfo['order_id'];
            if (empty($itemInfo['parent_item_id'])) {
                $arrOrderInfo['order_item_id'] = $itemInfo['item_id'];
            } else {
                $arrOrderInfo['order_item_id'] = $itemInfo['parent_item_id'];
            }

            $arrOrderInfo['product_id'] = $itemInfo['product_id'];
            $arrOrderInfo['sku'] = $itemInfo['sku'];
            if ($itemInfo['product_type'] != 'bundle' && $itemInfo['product_type'] != 'configurable') {
                try {
                    Mage::getModel('syncorderitems/syncorderitems')
                        ->addData($arrOrderInfo)
                        ->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }

            }


        }

    }

    /**
     * @param $observer -- get all the event information
     * @Description: This method is used to sync product information into sync tables when any updations are done by
     * product massupdate.
     * @author: KT174
     */
    public function onProductMassUpdate($observer)
    {
        $arrProductIds = $observer->getEvent()->getProductids();
        $arrAttributesUpdatedData = $observer->getEvent()->getUpdatedattributes();
        asort($arrProductIds);
        foreach ($arrProductIds as $Id) {
            $prdAttributesUpdatedData = array();
            $productInformation = array();
            $arrProduct = array();
            $cProduct = Mage::getModel('Mage_Catalog_Model_Product_Api')->info($Id);
            $product = Mage::getModel('catalog/product')->load($Id);
            $productInformation = $product->toArray();
            if (isset($cProduct['category_ids']) && count($cProduct['category_ids'])) {
                $productInformation['category_ids'] = serialize($cProduct['category_ids']);
            }
            foreach ($productInformation['stock_item'] as $k => $stockdata) {
                $productInformation[$k] = $stockdata;
            }
            if (empty($productinfo['store_price']) || !isset($productinfo['store_price'])) {
                $productInformation['store_price'] = $productInformation['price'];
            }
            $arrProduct = $productInformation;

            $parentIds = array();
            if ($arrProduct['type_id'] == 'simple') {

                $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($Id);
            }

            $syncStatus = $arrProduct['sync_status'];
            $attributeSetId = $arrProduct['attribute_set_id'];
            $checkAttributeSetRelation = Mage::helper('microbiz_connector')->checkObjectRelation($attributeSetId, 'AttributeSets');
            $overallsyncStatus = Mage::getStoreConfig('connector/settings/syncstatus');


            if ($overallsyncStatus) {

                if ($syncStatus && $checkAttributeSetRelation) {

                    /*Code to Save Magento Version Numbers into the Relation Tables Starts Here.*/
                    if ($arrAttributesUpdatedData['is_version_update']) {

                        Mage::helper('microbiz_connector')->saveProductVerRel($Id, $arrProduct, true);

                    }
                    $prdAttributesUpdatedData = $arrAttributesUpdatedData;

                    /*Code to Save Magento Version Numbers into the Relation Tables Ends Here.*/

                    $arrRelationProductData = Mage::helper('microbiz_connector')->checkObjectRelation($Id, 'Product');

                    if (!($arrRelationProductData)) {

                        //Mage::log("Relation Not Exists",null,'massupdate.log');
                        //Mage::log($arrProduct['type_id'],null,'massupdate.log');
                        $arrRefProductInfo = array();
                        foreach ($arrProduct as $k => $v) {
                            $arrRefProductInfo[$k] = $v;
                        }

                        $arrProductPostData = array();
                        foreach ($arrRefProductInfo as $k => $v) {
                            if (is_array($v)) {
                                $arrProductPostData[$k] = serialize($v);
                            } else {
                                $arrProductPostData[$k] = $v;
                            }
                        }

                        if (isset($arrRefProductInfo['stock_data'])) {
                            foreach ($arrRefProductInfo['stock_data'] as $k => $stockdata) {
                                $arrProductPostData[$k] = $stockdata;
                            }
                            unset($arrProductPostData['stock_data']);
                        }
                        unset($arrProductPostData['media_gallery']);
                        unset($arrProductPostData['is_newly_created']);
                        $arrUpdatedInfo = array();
                        $arrUpdatedInfo = $arrProductPostData;

                        //if the product is configurable and having relation then updating the configurable attr data and product data.
                        if ($arrProduct['type_id'] == 'configurable') {
                            $configProduct = Mage::getModel('catalog/product')->load($Id);
                            $productAttributeOptions = $configProduct->getTypeInstance(true)->getConfigurableAttributesAsArray($configProduct);

                            foreach ($productAttributeOptions as $productAttributeOption) {
                                $configAttributes[] = $productAttributeOption['attribute_code'];
                            }

                            $childProducts = Mage::getModel('catalog/product_type_configurable')
                                ->getUsedProducts(null, $configProduct);
                            $arrUpdatedInfo['configurable_attributes_data'] = $productAttributeOptions;

                            if (!empty($childProducts)) {
                                foreach ($childProducts as $child) {
                                    $simpleProduct = Mage::getModel('catalog/product')->load($child->getId());
                                    $attributes = $simpleProduct->getAttributes();
                                    $simpleconfiginfo = array();
                                    foreach ($configAttributes as $configAttribute) {
                                        $attributeValue = null;
                                        if (array_key_exists($configAttribute, $attributes)) {
                                            $attributesobj = $attributes["{$configAttribute}"];
                                            $attributeValue = $attributesobj->getFrontend()->getValue($simpleProduct);
                                        }
                                        $attribute_details = Mage::getSingleton("eav/config")->getAttribute("catalog_product", $configAttribute);
                                        $options = $attribute_details->getSource()->getAllOptions(false);
                                        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
                                        $code = $eavAttribute->getIdByCode('catalog_product', $configAttribute);

                                        foreach ($options as $option) {
                                            if ($option["label"] == $attributeValue) {
                                                $attributeValueIndex = $option["value"];
                                            }
                                        }

                                        $simpleconfiginfo[] = array(
                                            'label' => $attributeValue,
                                            'attribute_id' => $code,
                                            'value_index' => $attributeValueIndex,
                                        );
                                    }
                                    $arrUpdatedInfo['configurable_products_data'][$child->getId()] = $simpleconfiginfo;
                                }
                            } else {
                                $arrUpdatedInfo['configurable_products_data'] = array();
                            }

                        }

                        foreach ($arrUpdatedInfo as $key => $update) {
                            if (is_array($update)) {
                                $arrUpdatedInfo[$key] = serialize($update);
                            } else {
                                $arrUpdatedInfo[$key] = $update;
                            }
                        }

                        if (count($arrUpdatedInfo)) {
                            if (count($parentIds)) {
                                $arrUpdatedInfo['parentIds'] = $parentIds;
                            }
                            if (isset($arrUpdatedInfo['sku'])) {
                                $arrUpdatedInfo['sku'] = $arrProduct['sku'];
                            }

                            //for calculating price Excluding tax
                            /* KT097 Code for Store price excluding tax*/
                            /*if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                $tax_helper = Mage::getSingleton('tax/calculation');
                                $tax_request = $tax_helper->getRateOriginRequest();
                                $tax_request->setProductClassId($arrProduct['tax_class_id']);

                                $tax = $tax_helper->getRate($tax_request);
                                $calculator = Mage::getSingleton('tax/calculation');

                                $price_excluding_tax_price = Mage::helper('tax')->getPrice($product, $arrProduct['price'], false);
                                $tax_amount_price = $calculator->calcTaxAmount($arrProduct['price'], $tax, true, true);
                                $arrUpdatedInfo['price'] = $price_excluding_tax_price;
                                $arrUpdatedInfo['price_tax_amount'] = $tax_amount_price;


                                $price_excluding_tax = Mage::helper('tax')->getPrice($product, $arrProduct['store_price'], false);
                                $tax_amount = $calculator->calcTaxAmount($arrProduct['store_price'], $tax, true, true);
                                $arrUpdatedInfo['store_price'] = $price_excluding_tax;
                                $arrUpdatedInfo['tax_amount'] = $tax_amount;
                            } else {
                                $arrUpdatedInfo['store_price'] = $arrProduct['store_price'];

                                $arrUpdatedInfo['price'] = $arrProduct['price'];
                            }*/


                            $tax_helper = Mage::getSingleton('tax/calculation');
                            $tax_request = $tax_helper->getRateOriginRequest();
                            $tax_request->setProductClassId($product->getTaxClassId());
                            $tax = $tax_helper->getRate($tax_request);
                            $calculator = Mage::getSingleton('tax/calculation');
                            $price = $arrUpdatedInfo['price'];
                            $storePrice = $product->getStorePrice();
                            if ((!$storePrice || $storePrice <= 0) && isset($price)) {
                                if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                    $price_excluding_tax = Mage::helper('tax')->getPrice($product, $price, false);
                                    $tax_amount = $calculator->calcTaxAmount($price, $tax, true, true);
                                    //$arrUpdatedInfo['store_price'] = $price_excluding_tax;
                                    $arrUpdatedInfo['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($product->getId(), $price);
                                    $arrUpdatedInfo['tax_amount'] = $tax_amount;
                                } else {
                                    //$productData['store_price'] = $product->getPrice();
                                    $arrUpdatedInfo['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($product->getId(), $price);
                                }
                            } else {
                                if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                    $price_excluding_tax = Mage::helper('tax')->getPrice($product, $product->getStorePrice(), false);
                                    $tax_amount = $calculator->calcTaxAmount($product->getStorePrice(), $tax, true, true);
                                    //$arrUpdatedInfo['store_price'] = $price_excluding_tax;
                                    $arrUpdatedInfo['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($product->getId(), $product->getStorePrice());
                                    $arrUpdatedInfo['tax_amount'] = $tax_amount;
                                } else {
                                    $arrUpdatedInfo['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($product->getId(), $product->getStorePrice());
                                }
                            }
                            if (isset($price)) {
                                if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                    $price_excluding_tax = Mage::helper('tax')->getPrice($product, $price, false);
                                    $tax_amount = $calculator->calcTaxAmount($price, $tax, true, true);
                                    //$arrUpdatedInfo['price'] = $price_excluding_tax;
                                    $arrUpdatedInfo['price'] = Mage::helper('microbiz_connector')->getIncExlPrice($product->getId(), $price);
                                    $arrUpdatedInfo['price_including_tax'] = $price;
                                    $arrUpdatedInfo['tax_amount'] = $tax_amount;
                                } else {
                                    //$arrUpdatedInfo['price'] = $product->getPrice();
                                    $arrUpdatedInfo['price'] = Mage::helper('microbiz_connector')->getIncExlPrice($product->getId(), $product->getPrice());
                                }
                            }


                            //for calculating price Excluding tax End

                        }
                        $arrUpdatedInfo['attribute_set_id'] = $arrProduct['attribute_set_id'];
                        $arrUpdatedInfo['type_id'] = $arrProduct['type_id'];
                        $arrUpdatedInfo['category_ids'] = $arrProduct['category_ids'];
                        $arrUpdatedInfo['sku'] = $arrProduct['sku'];

                        if ($arrUpdatedInfo['type_id'] == 'configurable' && array_key_exists('parentIds', $arrUpdatedInfo)) {
                            unset($arrUpdatedInfo['parentIds']);
                        }
                        $arrUpdatedInfo['api_web_price_tax_setting'] = Mage::getStoreConfig('tax/calculation/price_includes_tax');

                        Mage::log($arrUpdatedInfo, null, 'massupdate.log');
                        $this->setProductSyncInfo($Id, $arrUpdatedInfo);


                    } else {
                        $prdAttributesUpdatedData['type_id'] = $arrProduct['type_id'];
                        $prdAttributesUpdatedData['attribute_set_id'] = $arrProduct['attribute_set_id'];
                        $prdAttributesUpdatedData['sku'] = $arrProduct['sku'];
                        $prdAttributesUpdatedData['category_ids'] = $arrProduct['category_ids'];

                        $parentIds = array();
                        if ($prdAttributesUpdatedData['type_id'] == 'simple') {

                            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($arrProduct['entity_id']);
                            $prdAttributesUpdatedData['parentIds'] = $parentIds;
                        }

                        if ($prdAttributesUpdatedData['type_id'] == 'configurable' && array_key_exists('parentIds', $prdAttributesUpdatedData)) {
                            unset($prdAttributesUpdatedData['parentIds']);
                        }


                        //if the product is configurable and having relation then updating the configurable attr data and product data.
                        if ($prdAttributesUpdatedData['type_id'] == 'configurable') {
                            $configProduct = Mage::getModel('catalog/product')->load($Id);
                            $productAttributeOptions = $configProduct->getTypeInstance(true)->getConfigurableAttributesAsArray($configProduct);

                            foreach ($productAttributeOptions as $productAttributeOption) {
                                $configAttributes[] = $productAttributeOption['attribute_code'];
                            }

                            $childProducts = Mage::getModel('catalog/product_type_configurable')
                                ->getUsedProducts(null, $configProduct);
                            $prdAttributesUpdatedData['configurable_attributes_data'] = $productAttributeOptions;

                            if (!empty($childProducts)) {
                                foreach ($childProducts as $child) {
                                    $simpleProduct = Mage::getModel('catalog/product')->load($child->getId());
                                    $attributes = $simpleProduct->getAttributes();
                                    $simpleconfiginfo = array();
                                    foreach ($configAttributes as $configAttribute) {
                                        $attributeValue = null;
                                        if (array_key_exists($configAttribute, $attributes)) {
                                            $attributesobj = $attributes["{$configAttribute}"];
                                            $attributeValue = $attributesobj->getFrontend()->getValue($simpleProduct);
                                        }
                                        $attribute_details = Mage::getSingleton("eav/config")->getAttribute("catalog_product", $configAttribute);
                                        $options = $attribute_details->getSource()->getAllOptions(false);
                                        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
                                        $code = $eavAttribute->getIdByCode('catalog_product', $configAttribute);

                                        foreach ($options as $option) {
                                            if ($option["label"] == $attributeValue) {
                                                $attributeValueIndex = $option["value"];
                                            }
                                        }

                                        $simpleconfiginfo[] = array(
                                            'label' => $attributeValue,
                                            'attribute_id' => $code,
                                            'value_index' => $attributeValueIndex,
                                        );
                                    }
                                    $prdAttributesUpdatedData['configurable_products_data'][$child->getId()] = $simpleconfiginfo;
                                }
                            } else {
                                $prdAttributesUpdatedData['configurable_products_data'] = array();
                            }

                        }
                        if (isset($prdAttributesUpdatedData['price'])) {
                            if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                $productTaxClassId = isset($prdAttributesUpdatedData['tax_class_id']) ? $prdAttributesUpdatedData['tax_class_id'] : $arrProduct['tax_class_id'];
                                $tax_helper = Mage::getSingleton('tax/calculation');
                                $tax_request = $tax_helper->getRateOriginRequest();
                                $tax_request->setProductClassId($productTaxClassId);

                                $tax = $tax_helper->getRate($tax_request);
                                $calculator = Mage::getSingleton('tax/calculation');
                                $price_excluding_tax = Mage::helper('tax')->getPrice($product, $prdAttributesUpdatedData['price'], false);
                                $tax_amount = $calculator->calcTaxAmount($prdAttributesUpdatedData['price'], $tax, true, true);
                                //$prdAttributesUpdatedData['price'] = $price_excluding_tax;
                                $prdAttributesUpdatedData['price'] = Mage::helper('microbiz_connector')->getIncExlPrice($arrProduct['entity_id'], $prdAttributesUpdatedData['price']);
                                $prdAttributesUpdatedData['price_tax_amount'] = $tax_amount;
                            } else {
                                $prdAttributesUpdatedData['price'] = Mage::helper('microbiz_connector')->getIncExlPrice($arrProduct['entity_id'], $prdAttributesUpdatedData['price']);
                            }

                        }

                        //if store price is updated
                        if (isset($prdAttributesUpdatedData['store_price']) && $prdAttributesUpdatedData['store_price'] > 0) {
                            if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                $productTaxClassId = isset($prdAttributesUpdatedData['tax_class_id']) ? $prdAttributesUpdatedData['tax_class_id'] : $arrProduct['tax_class_id'];
                                $tax_helper = Mage::getSingleton('tax/calculation');
                                $tax_request = $tax_helper->getRateOriginRequest();
                                $tax_request->setProductClassId($productTaxClassId);

                                $tax = $tax_helper->getRate($tax_request);
                                $calculator = Mage::getSingleton('tax/calculation');
                                $price_excluding_tax = Mage::helper('tax')->getPrice($product, $prdAttributesUpdatedData['store_price'], false);
                                $tax_amount = $calculator->calcTaxAmount($prdAttributesUpdatedData['store_price'], $tax, true, true);
                                //$prdAttributesUpdatedData['price'] = $price_excluding_tax;
                                $prdAttributesUpdatedData['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($arrProduct['entity_id'], $prdAttributesUpdatedData['store_price']);
                                $prdAttributesUpdatedData['store_price_tax_amount'] = $tax_amount;
                            } else {
                                $prdAttributesUpdatedData['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($arrProduct['entity_id'], $prdAttributesUpdatedData['store_price']);
                            }

                        }
                        $prdAttributesUpdatedData['api_web_price_tax_setting'] = Mage::getStoreConfig('tax/calculation/price_includes_tax');
                        Mage::log($prdAttributesUpdatedData, null, 'massupdate.log');
                        $this->setProductSyncInfo($Id, $prdAttributesUpdatedData);


                    }

                    // for configurable child products syncing
                    if ($arrProduct['type_id'] == 'configurable') {

                        $arrProductConfig = Mage::getModel('catalog/product')->load($Id);
                        $arrChildProducts = Mage::getModel('catalog/product_type_configurable')
                            ->getUsedProducts(null, $arrProductConfig);

                        foreach ($arrChildProducts as $child) {
                            $childproductinfo = $this->getProductInfo($child->getId());

                            $child_product_id = $child->getId();
                            $childproductinfo1 = array();
                            $relation_childproduct_data = Mage::helper('microbiz_connector')->checkObjectRelation($child_product_id, 'Product');
                            if (!($relation_childproduct_data)) {
                                unset($childproductinfo['_cache_instance_product_ids']);
                                foreach ($childproductinfo as $key => $update) {
                                    if (is_array($update)) {
                                        $childproductinfo1[$key] = serialize($update);
                                    } else {
                                        $childproductinfo1[$key] = $update;
                                    }
                                }
                            }
                            $childPrdParentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($child_product_id);
                            $childproductinfo1['parentIds'] = $childPrdParentIds;

                            $childProductObject = Mage::getModel('catalog/product')->load($child_product_id);
                            $tax_helper = Mage::getSingleton('tax/calculation');
                            $tax_request = $tax_helper->getRateOriginRequest();
                            $tax_request->setProductClassId($childProductObject->getTaxClassId());

                            $tax = $tax_helper->getRate($tax_request);
                            $calculator = Mage::getSingleton('tax/calculation');

                            if ((!$childproductinfo1['store_price'] || $childproductinfo1['store_price'] <= 0) && isset($childproductinfo1['price'])) {
                                if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                    $price_excluding_tax = Mage::helper('tax')->getPrice($childProductObject, $childProductObject->getPrice(), false);
                                    $tax_amount = $calculator->calcTaxAmount($childProductObject->getPrice(), $tax, true, true);
                                    //$childproductinfo1['store_price'] = $price_excluding_tax;
                                    $childproductinfo1['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($childProductObject->getId(), $childProductObject->getPrice());
                                    $childproductinfo1['tax_amount'] = $tax_amount;
                                } else {
                                    //$childproductinfo1['store_price'] = $childProductObject->getPrice();
                                    $childproductinfo1['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($childProductObject->getId(), $childProductObject->getPrice());
                                }
                            } else {
                                if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                    $price_excluding_tax = Mage::helper('tax')->getPrice($childProductObject, $childProductObject->getStorePrice(), false);
                                    $tax_amount = $calculator->calcTaxAmount($childProductObject->getStorePrice(), $tax, true, true);
                                    //$childproductinfo1['store_price'] = $price_excluding_tax;
                                    $childproductinfo1['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($childProductObject->getId(), $childProductObject->getStorePrice());
                                    $childproductinfo1['tax_amount'] = $tax_amount;
                                } else {
                                    //$childproductinfo1['store_price'] = $childProductObject->getStorePrice();
                                    $childproductinfo1['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($childProductObject->getId(), $childProductObject->getStorePrice());
                                }

                            }

                            if (isset($childproductinfo1['price'])) {
                                if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                    $price_excluding_tax = Mage::helper('tax')->getPrice($childProductObject, $childProductObject->getPrice(), false);
                                    $tax_amount = $calculator->calcTaxAmount($childProductObject->getPrice(), $tax, true, true);
                                    //$childproductinfo1['price'] = $price_excluding_tax;
                                    $childproductinfo1['price'] = Mage::helper('microbiz_connector')->getIncExlPrice($childProductObject->getId(), $childProductObject->getPrice());
                                    $childproductinfo1['price_including_tax'] = $childProductObject->getPrice();
                                    $childproductinfo1['tax_amount'] = $tax_amount;
                                } else {
                                    //$childproductinfo1['price'] = $childProductObject->getPrice();
                                    $childproductinfo1['price'] = Mage::helper('microbiz_connector')->getIncExlPrice($childProductObject->getId(), $childProductObject->getPrice());
                                }
                            }
                            $childproductinfo1['attribute_set_id'] = $childProductObject->getAttributeSetId();
                            $childproductinfo1['type_id'] = $childProductObject->getTypeId();
                            $childproductinfo1['sku'] = $childProductObject->getSku();

                            //check if sync to microbiz field is set to yes or not if not set to yes
                            if (!empty($childproductinfo)) {
                                if (array_key_exists('sync_status', $childproductinfo)) {
                                    if ($childproductinfo['sync_status']) {
                                        $childproductinfo1['sync_status'] = $childproductinfo['sync_status'];
                                    } else {
                                        $prodModel = Mage::getSingleton('catalog/product')->load($child_product_id);
                                        $prodModel->setSyncStatus(1)->setId($child_product_id)->save();
                                        $childproductinfo1['sync_status'] = 1;
                                    }
                                } else {
                                    $prodModel = Mage::getSingleton('catalog/product')->load($child_product_id);
                                    $prodModel->setSyncStatus(1)->setId($child_product_id)->save();
                                    $childproductinfo1['sync_status'] = 1;
                                }
                            }
                            $childproductinfo1['api_web_price_tax_setting'] = Mage::getStoreConfig('tax/calculation/price_includes_tax');
                            $this->setProductSyncInfo($child_product_id, $childproductinfo1);

                        }

                    }
                }

            }
        }

    }

    /**
     * @param $categoryId
     * @param $categoryModel
     * @param $allChildCategories
     * @param $user
     * @author KT174
     * @description This method is used to sync the child categories of a category.
     */
    public function syncChildCategories($categoryId, $categoryModel, $allChildCategories, $user)
    {
        foreach ($allChildCategories as $key => $value) {
            if ($value != $categoryId) {
                $childCategoryData = $categoryModel->load($value)->getData();
                $arrUpdatedChildItems = array();
                foreach ($childCategoryData as $key => $data) {
                    if (!is_array($data)) {
                        $arrUpdatedChildItems[$key] = $data;
                    } else {
                        $arrArrayItems[$key] = $data;
                    }
                }

                if ($arrUpdatedChildItems['is_changed_product_list']) {
                    $arrUpdatedChildItems['affected_product_ids'] = serialize($arrArrayItems['affected_product_ids']);
                    $arrUpdatedChildItems['posted_products'] = serialize($arrArrayItems['posted_products']);
                }
                unset($arrUpdatedChildItems['id']);
                unset($arrUpdatedChildItems['is_changed_product_list']);
                unset($arrUpdatedChildItems['custom_design_from_is_formated']);
                unset($arrUpdatedChildItems['custom_design_to_is_formated']);
                unset($arrUpdatedChildItems['updated_at']);

                $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                    ->addFieldToFilter('obj_id', $value)
                    ->addFieldToFilter('model_name', 'ProductCategories')
                    ->addFieldToFilter('status', array('in' => array('Pending', 'Failed')))
                    ->setOrder('header_id', 'desc')
                    ->getData();
                if ($isObjectExists) {
                    $header_id = $isObjectExists[0]['header_id'];
                    $isObjectExists[0]['status'] = 'Pending';
                    Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($header_id)->setData($isObjectExists[0])->save();

                } else {
                    $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                    $arrCategoryData = array();
                    $arrCategoryData['model_name'] = 'ProductCategories';
                    $arrCategoryData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                    $arrCategoryData['obj_id'] = $value;
                    $arrCategoryData['created_by'] = $user;
                    $arrCategoryData['created_time'] = $date;
                    $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                        ->setData($arrCategoryData)
                        ->save();
                    $header_id = $model['header_id'];

                }

                //saving the category data to sync items tables.

                foreach ($arrUpdatedChildItems as $k => $updateditem) {
                    if (!is_array($updateditem)) {
                        $arrCategoryInfoData = array();
                        $arrCategoryInfoData['header_id'] = $header_id;
                        $isItemExists = Mage::getModel('syncitems/syncitems')
                            ->getCollection()
                            ->addFieldToFilter('header_id', $header_id)
                            ->addFieldToFilter('attribute_name', $k)
                            ->getFirstItem();
                        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
                        $code = $eavAttribute->getIdByCode('catalog_category', $k);
                        $arrCategoryInfoData['attribute_id'] = $code;
                        $arrCategoryInfoData['attribute_name'] = $k;
                        $arrCategoryInfoData['attribute_value'] = $updateditem;
                        $arrCategoryInfoData['created_by'] = $user;
                        $arrCategoryInfoData['created_time'] = $date;
                        if ($isItemExists->getId()) {
                            $model = Mage::getModel('syncitems/syncitems')->load($isItemExists->getId());
                        } else {
                            $model = Mage::getModel('syncitems/syncitems');
                        }
                        $model->setData($arrCategoryInfoData)->setId($isItemExists->getId())->save();
                    }
                } //end foreach of sync child items
            }

        } //end foreach of childcategories
    }

    /**
     * $observer contains the category object with new or existing category details.
     * @description: This method is used the capture the new/updated category information and update them in the sync
     * tables.
     * @author: KT174
     */

    public function mbizOnCategorySave($observer)
    {
        $adminsession = Mage::getSingleton('admin/session', array('name' => 'adminhtml'));
        if ($adminsession->isLoggedIn()) {
            $arrCategory = $observer->getEvent()->getCategory()->getData();
            $arrOrigCategory = $observer->getEvent()->getCategory()->getorigData();

            $storeId = $arrCategory['store_id'];

            if ($storeId) {
                return;
            }
            $parentId = $arrCategory['parent_id'];
            $categoryId = $arrCategory['entity_id'];
            if (Mage::getSingleton('admin/session')->isLoggedIn()) {

                $user = Mage::getSingleton('admin/session');
                $user = $user->getUser()->getFirstname();

            } else if (Mage::getSingleton('api/session')) {
                $userApi = Mage::getSingleton('api/session');
                $user = $userApi->getUser()->getUsername();

            } else {
                $user = 'Guest';
            }

            $categoryRelationModel = Mage::getModel('mbizcategory/mbizcategory')
                ->getCollection()
                ->addFieldToFilter('magento_id', $categoryId)
                ->setOrder('id', 'asc')
                ->getData();
            if ($parentId > 1) // if parent id greater than 1 means current category is a sub category and contains parent.
            {
                $RootPathCategory = explode('/', $arrCategory['path']);
                $rootCategoryId = $RootPathCategory[1];


                $rootCategorySyncStatus = Mage::getModel('catalog/category')->load($rootCategoryId)->getData('sync_cat_create');
                if ($rootCategorySyncStatus == 1) {
                    if (count($categoryRelationModel) > 0) {
                        foreach ($arrCategory as $key => $data) {
                            if (!is_array($data)) {
                                if ($arrCategory[$key] != $arrOrigCategory[$key]) {
                                    $arrUpdatedItems[$key] = $data;
                                }
                            } else {
                                $arrArrayItems[$key] = $data;
                            }
                        }
                    } else {
                        foreach ($arrCategory as $key => $data) {
                            if (!is_array($data)) {
                                $arrUpdatedItems[$key] = $data;
                            } else {
                                $arrArrayItems[$key] = $data;
                            }
                        }
                    }


                    if ($arrUpdatedItems['is_changed_product_list']) {
                        $arrUpdatedItems['affected_product_ids'] = serialize($arrArrayItems['affected_product_ids']);
                        $arrUpdatedItems['posted_products'] = serialize($arrArrayItems['posted_products']);
                    }
                    unset($arrUpdatedItems['id']);
                    unset($arrUpdatedItems['is_changed_product_list']);
                    unset($arrUpdatedItems['custom_design_from_is_formated']);
                    unset($arrUpdatedItems['custom_design_to_is_formated']);
                    //unset($arrUpdatedItems['updated_at']);

                    if (count($arrUpdatedItems) > 0) {

                        $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                            ->addFieldToFilter('obj_id', $categoryId)
                            ->addFieldToFilter('model_name', 'ProductCategories')
                            ->addFieldToFilter('status', array('in' => array('Pending', 'Failed')))
                            ->setOrder('header_id', 'desc')
                            ->getData();
                        if ($isObjectExists) {
                            $header_id = $isObjectExists[0]['header_id'];
                            $isObjectExists[0]['status'] = 'Pending';
                            Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($header_id)->setData($isObjectExists[0])->save();

                        } else {


                            $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                            $arrCategoryData = array();
                            $arrCategoryData['model_name'] = 'ProductCategories';
                            $arrCategoryData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                            $arrCategoryData['obj_id'] = $arrCategory['entity_id'];
                            $arrCategoryData['created_by'] = $user;
                            $arrCategoryData['created_time'] = $date;
                            $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                                ->setData($arrCategoryData)
                                ->save();
                            $header_id = $model['header_id'];

                        }

                        foreach ($arrUpdatedItems as $k => $updateditem) {
                            if (!is_array($updateditem)) {
                                $arrCategoryInfoData = array();
                                $arrCategoryInfoData['header_id'] = $header_id;
                                $isItemExists = Mage::getModel('syncitems/syncitems')
                                    ->getCollection()
                                    ->addFieldToFilter('header_id', $header_id)
                                    ->addFieldToFilter('attribute_name', $k)
                                    ->getFirstItem();
                                $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
                                $code = $eavAttribute->getIdByCode('catalog_category', $k);
                                $arrCategoryInfoData['attribute_id'] = $code;
                                $arrCategoryInfoData['attribute_name'] = $k;
                                $arrCategoryInfoData['attribute_value'] = $updateditem;
                                $arrCategoryInfoData['created_by'] = $user;
                                $arrCategoryInfoData['created_time'] = $date;
                                if ($isItemExists->getId()) {
                                    $model = Mage::getModel('syncitems/syncitems')->load($isItemExists->getId());
                                } else {
                                    $model = Mage::getModel('syncitems/syncitems');
                                }
                                $model->setData($arrCategoryInfoData)->setId($isItemExists->getId())->save();
                            }
                        }

                    }

                    //Sync all the child Categories of a Root Category Starts Here.
                    $categoryModel = Mage::getModel('catalog/category');
                    $category = Mage::getModel('catalog/category')->load($categoryId);
                    //$allChildCategories = $categoryModel->getResource()->getChildrenIds($category,true);
                    $allChildCategories = Mage::helper('microbiz_connector')->getChildrenIds($category, true);
                    //Mage::log("came to sub categories on category save",null,'catesync.log');
                    //Mage::log($allChildCategories,null,'catesync.log');
                    if (count($allChildCategories) > 0) {
                        $this->syncChildCategories($categoryId, $categoryModel, $allChildCategories, $user);
                    } //end if count of childcategories
                }

            } else {
                $synStatus = $arrCategory['sync_cat_create'];
                if ($synStatus) {
                    if (count($categoryRelationModel) > 0) {
                        foreach ($arrCategory as $key => $data) {
                            if (!is_array($data)) {
                                if ($arrCategory[$key] != $arrOrigCategory[$key]) {
                                    $arrUpdatedItems[$key] = $data;
                                }
                            } else {
                                $arrArrayItems[$key] = $data;
                            }
                        }
                    } else {
                        foreach ($arrCategory as $key => $data) {
                            if (!is_array($data)) {
                                $arrUpdatedItems[$key] = $data;
                            } else {
                                $arrArrayItems[$key] = $data;
                            }
                        }
                    }

                    if ($arrUpdatedItems['is_changed_product_list']) {
                        $arrUpdatedItems['affected_product_ids'] = serialize($arrArrayItems['affected_product_ids']);
                        $arrUpdatedItems['posted_products'] = serialize($arrArrayItems['posted_products']);
                    }
                    unset($arrUpdatedItems['id']);
                    unset($arrUpdatedItems['is_changed_product_list']);
                    unset($arrUpdatedItems['custom_design_from_is_formated']);
                    unset($arrUpdatedItems['custom_design_to_is_formated']);
                    //unset($arrUpdatedItems['updated_at']);

                    if (count($arrUpdatedItems) > 0) {

                        $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                            ->addFieldToFilter('obj_id', $categoryId)
                            ->addFieldToFilter('model_name', 'ProductCategories')
                            ->addFieldToFilter('status', array('in' => array('Pending', 'Failed')))
                            ->setOrder('header_id', 'desc')
                            ->getData();
                        if ($isObjectExists) {
                            $header_id = $isObjectExists[0]['header_id'];
                            $isObjectExists[0]['status'] = 'Pending';
                            Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($header_id)->setData($isObjectExists[0])->save();

                        } else {
                            $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                            $arrCategoryData = array();
                            $arrCategoryData['model_name'] = 'ProductCategories';
                            $arrCategoryData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                            $arrCategoryData['obj_id'] = $arrCategory['entity_id'];
                            $arrCategoryData['created_by'] = $user;
                            $arrCategoryData['created_time'] = $date;
                            $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                                ->setData($arrCategoryData)
                                ->save();
                            $header_id = $model['header_id'];

                        }

                        foreach ($arrUpdatedItems as $k => $updateditem) {
                            if (!is_array($updateditem)) {
                                $arrCategoryInfoData = array();
                                $arrCategoryInfoData['header_id'] = $header_id;
                                $isItemExists = Mage::getModel('syncitems/syncitems')
                                    ->getCollection()
                                    ->addFieldToFilter('header_id', $header_id)
                                    ->addFieldToFilter('attribute_name', $k)
                                    ->getFirstItem();
                                $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
                                $code = $eavAttribute->getIdByCode('catalog_category', $k);
                                $arrCategoryInfoData['attribute_id'] = $code;
                                $arrCategoryInfoData['attribute_name'] = $k;
                                $arrCategoryInfoData['attribute_value'] = $updateditem;
                                $arrCategoryInfoData['created_by'] = $user;
                                $arrCategoryInfoData['created_time'] = $date;
                                if ($isItemExists->getId()) {
                                    $model = Mage::getModel('syncitems/syncitems')->load($isItemExists->getId());
                                } else {
                                    $model = Mage::getModel('syncitems/syncitems');
                                }
                                $model->setData($arrCategoryInfoData)->setId($isItemExists->getId())->save();
                            }
                        }

                    }

                    //Sync all the child Categories of a Root Category Starts Here.
                    $categoryModel = Mage::getModel('catalog/category');
                    $category = Mage::getModel('catalog/category')->load($categoryId);
                    //Mage::log("came to cateogry root ",null,'catesync.log');
                    //$allChildCategories = $categoryModel->getResource()->getChildrenIds($category,true);
                    $allChildCategories = Mage::helper('microbiz_connector')->getChildrenIds($category, true);
                    //Mage::log($allChildCategories,null,'catesync.log');
                    if (count($allChildCategories) > 0) {
                        $this->syncChildCategories($categoryId, $categoryModel, $allChildCategories, $user);
                    } //end if count of childcategories

                }
            }
        }
    }


    /**
     * @$observer contains the quote details
     * @descrption This method is used to add discount to the cart,checkout.
     * @author KT174
     */
    public function mbizSetDiscountAmount($observer)
    {

        Mage::log("came to setdiscount");

        $quote = $observer->getEvent()->getQuote();
        $arrCreditData = Mage::getSingleton('checkout/session')->getCreditFinalData();
        $quote->setCreditData($arrCreditData);
        $quoteCreditData = unserialize($quote->getCreditData());
        $quote->getCreditDiscountData();

        /*giftcardsale price and total updations only if the quote has variable giftcard sale product and it is a multishipping. starts here.*/
        $arrGiftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
        $arrGiftBuyData = unserialize($arrGiftBuyData);

        $isGiftCardSaleExists = 0;
        if (!empty($arrGiftBuyData)) {
            foreach ($arrGiftBuyData as $data) {
                if (array_key_exists('gcd_type', $data)) {
                    $isGiftCardSaleExists = 1;
                }
            }
        }

        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        if (strpos($currentUrl, "multishipping") !== false) {
            $isMultishipping = 1;
        } else {
            $isMultishipping = 0;
        }

        if ($isGiftCardSaleExists == 1 && $quote->getIsMultiShipping() == 1) {
            $customPrice = 0;
            foreach ($arrGiftBuyData as $data) {
                if ($data['gcd_type'] == 2) {
                    $customPrice = $customPrice + $data['gcd_amt'];
                }
            }

            if ($customPrice > 0) {
                //echo "test";
                //echo $quote->getGrandTotal();
                $arrTotals = array();
                foreach ($quote->getAllAddresses() as $address) {
                    $arrTotals[$address->getId()]['sub_total'] = $address->getSubtotal();
                    $arrTotals[$address->getId()]['grand_total'] = $address->getGrandTotal();
                    $address->setSubtotal(0);
                    $address->setBaseSubtotal(0);

                    $address->setGrandTotal(0);
                    $address->setBaseGrandTotal(0);

                    $address->collectTotals();

                    $quote->setSubtotal((float)$quote->getSubtotal()
                        + $address->getSubtotal());
                    $quote->setBaseSubtotal((float)$quote->getBaseSubtotal()
                        + $address->getBaseSubtotal());

                    $quote->setSubtotalWithDiscount(
                        (float)$quote->getSubtotalWithDiscount()
                        + $address->getSubtotalWithDiscount()
                    );
                    $quote->setBaseSubtotalWithDiscount(
                        (float)$quote->getBaseSubtotalWithDiscount()
                        + $address->getBaseSubtotalWithDiscount()
                    );
                    /*
                    $quote->setGrandTotal((float) $quote->getGrandTotal()
                        + $address->getGrandTotal());
                    $quote->setBaseGrandTotal((float) $quote->getBaseGrandTotal()
                        + $address->getBaseGrandTotal());*/
                }
                $quote->setGrandTotal((float)$quote->getGrandTotal()
                    + $customPrice);
                $quote->setBaseGrandTotal((float)$quote->getBaseGrandTotal()
                    + $customPrice);
                if ($quote->getIsMultiShipping() == 1) {

                    Mage::log("came to observer");
                    Mage::log($arrGiftBuyData);
                    Mage::log($arrTotals);
                    foreach ($quote->getAllAddresses() as $address) {
                        Mage::log("address id" . $address->getId());

                        $subtotal = 0;
                        $discountAmt = 0;
                        Mage::log("subtotal" . $subtotal);
                        Mage::log($customPrice);
                        $addressId = $address->getId();
                        $isVariable = 0;

                        if ($address->getAddressType() == 'shipping') {
                            //$subtotal = $address->getSubTotal();
                            foreach ($address->getAllItems() as $_item) {

                                $productType = $_item->getProduct()->getTypeId();
                                if ($productType == 'mbizgiftcard') {
                                    Mage::log("item id" . $_item->getId());
                                    Mage::log("item price" . $_item->getPrice());

                                    $discountAmt = $_item->getDiscountAmount();

                                    $productOptions = $_item->getProduct()->getTypeInstance(true)->getOrderOptions($_item->getProduct());
                                    $arrAppliedOptions = $productOptions['options'];

                                    //Mage::log("applied options");
                                    //Mage::log($arrAppliedOptions);
                                    if (count($arrAppliedOptions) > 0) {
                                        $OptionValues = Mage::getModel('sales/quote_item_option')->getCollection()->addFieldToFilter('item_id', $_item->getQuoteItemId())
                                            ->addFieldToFilter('code', 'info_buyRequest')->getFirstItem()->getData();

                                        $arrValues = unserialize($OptionValues['value']);
                                        $productId = $arrValues['product'];
                                        $arrOptions = $arrValues['options'];
                                        foreach ($arrOptions as $key => $value) {
                                            $optionId = $key;
                                            $optionValue = $value;
                                        }
                                        $arrProductOptions = Mage::getModel('catalog/product')->load($productId)->getOptions();
                                        foreach ($arrProductOptions as $Options) {
                                            foreach ($Options->getValues() as $OptionsVal) {
                                                if ($Options->getId() == $optionId && $OptionsVal->getId() == $optionValue) {
                                                    $optionLabel = $OptionsVal->getTitle();
                                                }
                                            }
                                        }
                                        //code to get the option title ends here
                                        foreach ($arrAppliedOptions as $option) {
                                            if ($option['label'] == 'Gift Card' && $optionLabel == 'Any Amount' && $_item->getPrice() == 0) {

                                                $subtotal += $customPrice;
                                                $isVariable = 1;


                                            }
                                        }

                                    }
                                    $subtotal += $_item->getPrice();

                                    Mage::log("subtotal after update is");
                                    Mage::log($subtotal);


                                }


                            }

                            //updating the Subtotal and Grand Total of the current address and adding custom price if exists.

                            if ($isVariable == 1) {
                                foreach ($arrTotals as $addrId => $totalsData) {
                                    if ($addrId == $addressId) {
                                        $address->setSubtotal($totalsData['sub_total'] + $customPrice);
                                        $address->setBaseSubtotal($totalsData['sub_total'] + $customPrice);

                                        $address->setGrandTotal($totalsData['grand_total'] + $customPrice);
                                        $address->setBaseGrandTotal($totalsData['grand_total'] + $customPrice);
                                        $address->save();

                                    }

                                }
                            }


                        }


                    }


                }
                $quote->setTotalsCollectedFlag(true);
            }
        }
        /*giftcardsale price and total updations only if the quote has variable giftcard sale product and it is a multishipping. starts here.*/

        if (count($quoteCreditData) > 0) {
            $discountAmount = 0;
            if (is_array($quoteCreditData)) {
                foreach ($quoteCreditData as $data) {
                    $discountAmount = $discountAmount + $data['credit_amt'];
                    //calculating all the discount amt of both store credit and gift card.
                }
            }

            $quoteId = $quote->getId();
            $multiShippingDiscount = array();
            if ($quoteId) {
                if ($discountAmount > 0) {

                    //this is the total amount after defaut discount deduction and including all other shipping and tax charges

                    //Here we will check whether the grand total is >= to discount amount if greater or equal then we will
                    //update the grand total and assign the applied discount amount into $newDiscountAmount variable.
                    if ($quote->getGrandTotal() >= $discountAmount) {
                        $newDiscountAmount = $discountAmount;
                        $quote->setGrandTotal($quote->getGrandTotal() - $discountAmount)
                            ->setBaseGrandTotal($quote->getBaseGrandTotal() - $discountAmount);

                    } //if grand total is less than the discount amount then updating the total and discount amount variable.
                    else {
                        $newDiscountAmount = $quote->getGrandTotal();
                        $quote->setGrandTotal(0)
                            ->setBaseGrandTotal(0);
                    }
                    $quote->save();
                    $discountAmount = $newDiscountAmount;

                    if ($discountAmount > 0) {

                        $arrNewCreditData = array();
                        $TempDiscountAmt = $discountAmount;
                        /**
                         * Used to calculate the actual discount amount what we are utilizing
                         * for the cart for the credit id separately.
                         */
                        foreach ($quoteCreditData as $key => $data) {
                            $amount = $data['credit_amt'];
                            if ($amount >= $TempDiscountAmt) {
                                if ($TempDiscountAmt > 0) {
                                    $arrNewCreditData[$key]['credit_no'] = $data['credit_no'];
                                    $arrNewCreditData[$key]['credit_type'] = $data['credit_type'];
                                    if ($data['credit_type'] == 2) {
                                        $arrNewCreditData[$key]['credit_pin'] = $data['credit_pin'];
                                    }
                                    $arrNewCreditData[$key]['credit_amt'] = $TempDiscountAmt;
                                    $TempDiscountAmt = 0;
                                }

                            } else {
                                $arrNewCreditData[$key]['credit_no'] = $data['credit_no'];
                                $arrNewCreditData[$key]['credit_type'] = $data['credit_type'];
                                if ($data['credit_type'] == 2) {
                                    $arrNewCreditData[$key]['credit_pin'] = $data['credit_pin'];
                                }
                                $arrNewCreditData[$key]['credit_amt'] = $amount;
                                $TempDiscountAmt = $TempDiscountAmt - $amount;
                            }

                        }
                        $quote->setCreditDiscountData(serialize($arrNewCreditData));
                        $quote->save();
                        Mage::getSingleton('checkout/session')->unsCreditDiscountData();
                        Mage::getSingleton('checkout/session')->setCreditDiscountData(serialize($arrNewCreditData));

                    }


                    foreach ($quote->getAllAddresses() as $address) {
                        //$canAddItems = $quote->isVirtual()? ('billing') : ('shipping');

                        // if($address->getAddressType()==$canAddItems)
                        // {

                        if ($address->getGrandTotal() >= $discountAmount) {
                            $multiShippingDiscount[$address->getId()]['default_discount'] = $address->getDiscountAmount();
                            $multiShippingDiscount[$address->getId()]['credit_usage'] = $discountAmount;
                            $address->setGrandTotal((float)$address->getGrandTotal() - $discountAmount);
                            $address->setBaseGrandTotal((float)$address->getBaseGrandTotal() - $discountAmount);

                            $discountAmount = 0;
                        } else {
                            $multiShippingDiscount[$address->getId()]['default_discount'] = $address->getDiscountAmount();
                            $multiShippingDiscount[$address->getId()]['credit_usage'] = $address->getGrandTotal();
                            $discountAmount = $discountAmount - $address->getGrandTotal();

                            $address->setGrandTotal(0);
                            $address->setBaseGrandTotal(0);

                        }


                        $address->save();

                        //}
                    } //end address foreach


                    Mage::getSingleton('checkout/session')->unsMultiCreditData();
                    if (count($multiShippingDiscount) > 0 && is_array($multiShippingDiscount)) {
                        Mage::getSingleton('checkout/session')->setMultiCreditData(serialize($multiShippingDiscount));
                    }

                }


            }
        }

    }

    /**
     * @author KT174
     * @$order - Contains the complete order information
     * @description - This method is used to check any giftcards are added to the order.
     * If added it will capture those details from order and save into giftcard_info model.
     */
    public function saveGiftCardInfo($orderId, $buyGiftData, $orderItemIds)
    {
        Mage::log($orderItemIds);
        Mage::log($buyGiftData);
        if (count($buyGiftData) > 0) {
            foreach ($buyGiftData as $giftInfo) {
                /* for($i=1;$i<=$giftInfo['qty'];$i++)
                 {*/
                $saveGiftInfo = array();
                $saveGiftInfo['order_id'] = $orderId;
                $saveGiftInfo['gcd_amt'] = $giftInfo['gcd_amt'];
                $saveGiftInfo['gcd_type'] = $giftInfo['gcd_type'];
                $saveGiftInfo['gcd_pin'] = $giftInfo['gcd_pin'];
                if ($giftInfo['gcd_type'] == 2) {
                    $saveGiftInfo['order_item_id'] = $orderItemIds['any_amount'];

                } else {
                    $itemPrice = $giftInfo['gcd_amt'];
                    $orderItemID = $orderItemIds[$itemPrice];
                    Mage::log("order item id first" . $orderItemID);
                    if ($orderItemID == '') {
                        $itemPrice = round($giftInfo['gcd_amt'], 2);
                        $orderItemID = $orderItemIds[$itemPrice];
                    }
                    Mage::log("order item id after" . $orderItemID);
                    $saveGiftInfo['order_item_id'] = $orderItemID;

                }
                $saveGiftInfo['gcd_unique_num'] = $giftInfo['gcd_unique_num'];
                //Mage::log($saveGiftInfo);
                Mage::getModel('mbizgiftcardsale/mbizgiftcardsale')->setData($saveGiftInfo)->save();


                // }

            }
        }
    }

    public function mbizOnCartUpdate($cartObserver)
    {

        $updatedGiftData = array();
        foreach ($cartObserver->getCart()->getQuote()->getAllVisibleItems() as $item) {
            $giftCardSku = Mage::getStoreConfig('connector/settings/giftcardsku');

            $giftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
            $giftBuyData = unserialize($giftBuyData);
            Mage::log("before update ");
            Mage::log($updatedGiftData);


            if ($giftCardSku == $item['sku']) {
                $productOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                $arrAppliedOptions = $productOptions['options'];


                //finding gcd type using options
                if ($arrAppliedOptions[0]['label'] == 'Gift Card' && $arrAppliedOptions[0]['value'] == 'Any Amount') {

                    $gcdType = 2;
                    $gcdAnyAmts = array();

                    foreach ($giftBuyData as $data) {
                        if ($data['gcd_type'] == 2) {
                            $gcdAmt = $data['gcd_amt'];

                            $isAmtExists = $this->checkGcdAmtExists($gcdAmt, $gcdType, $gcdAnyAmts);

                            if (!$isAmtExists) {
                                if (!empty($gcdAnyAmts)) {
                                    $key = count($gcdAnyAmts);
                                    $gcdAnyAmts[$key]['gcd_amt'] = $data['gcd_amt'];
                                    $gcdAnyAmts[$key]['gcd_type'] = $data['gcd_type'];
                                    $gcdAnyAmts[$key]['gcd_unique_num'] = $data['gcd_unique_num'];
                                } else {
                                    $gcdAnyAmts[0]['gcd_amt'] = $data['gcd_amt'];
                                    $gcdAnyAmts[0]['gcd_type'] = $data['gcd_type'];
                                    $gcdAnyAmts[0]['gcd_unique_num'] = $data['gcd_unique_num'];
                                }
                            }
                        }

                    }

                } else {
                    $gcdType = 1;
                    $itemPrice = $item->getPrice();
                    $gcdAmount = round($itemPrice, 2);
                }
                for ($i = 0; $i < $item->getQty(); $i++) {
                    if ($arrAppliedOptions[0]['label'] == 'Gift Card' && $arrAppliedOptions[0]['value'] == 'Any Amount') {
                        foreach ($gcdAnyAmts as $anyData) {
                            if (!empty($updatedGiftData)) {
                                $key = count($updatedGiftData);
                                $updatedGiftData[$key]['gcd_amt'] = $anyData['gcd_amt'];
                                $updatedGiftData[$key]['gcd_type'] = $anyData['gcd_type'];
                                $updatedGiftData[$key]['gcd_unique_num'] = $anyData['gcd_unique_num'];
                            } else {
                                $updatedGiftData[0]['gcd_amt'] = $anyData['gcd_amt'];
                                $updatedGiftData[0]['gcd_type'] = $anyData['gcd_type'];
                                $updatedGiftData[0]['gcd_unique_num'] = $anyData['gcd_unique_num'];
                            }
                        }

                    } else {
                        $gcdUniqueNum = $this->getFixedAmtGcdNum($gcdAmount, $giftBuyData);
                        if (!empty($updatedGiftData)) {
                            $key = count($updatedGiftData);
                            $updatedGiftData[$key]['gcd_amt'] = $gcdAmount;
                            $updatedGiftData[$key]['gcd_type'] = 1;
                            $updatedGiftData[$key]['gcd_unique_num'] = $gcdUniqueNum;
                        } else {
                            $updatedGiftData[0]['gcd_amt'] = $gcdAmount;
                            $updatedGiftData[0]['gcd_type'] = 1;
                            $updatedGiftData[0]['gcd_unique_num'] = $gcdUniqueNum;
                        }

                    }
                }
            } //end if condition
        } //endforeach
        Mage::log("after update ");
        Mage::log($updatedGiftData);
        if (count($updatedGiftData) > 0) {
            Mage::getSingleton('checkout/session')->unsGiftBuyData();
            Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($updatedGiftData));
        }
    }

    /**
     * @param $observer contains the cart remove item details
     * @author - KT174
     * @description - This method is used to remove the Gift Card Sale Information from the Session Variable when
     * any Gift Card Product is Removed from the Cart.
     */
    public function mbizOnCartItemRemove($observer)
    {

        $arrGiftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
        $arrGiftBuyData = unserialize($arrGiftBuyData);
        $item = $observer->getQuoteItem();
        $productOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
        $arrAppliedOptions = $productOptions['options'];
        $itemPrice = $item->getPrice();
        if (count($arrAppliedOptions) > 0) {
            foreach ($arrAppliedOptions as $option) {
                if ($option['label'] == 'Gift Card') {
                    $type = 0;
                    if ($option['value'] == 'Any Amount') {
                        $type = 2;
                    } else {
                        $type = 1;
                    }

                    foreach ($arrGiftBuyData as $key => $data) {

                        if ($data['gcd_amt'] == $itemPrice && $data['gcd_type'] == $type) {
                            unset($arrGiftBuyData[$key]);
                        } else {
                            if ($data['gcd_type'] == $type) {
                                unset($arrGiftBuyData[$key]);
                            }
                        }

                    }

                    Mage::getSingleton('checkout/session')->unsGiftBuyData();
                    Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($arrGiftBuyData));
                }
            }

        }

    }

    /**
     * @author KT174
     * @description This method is used to check whether the any amount giftcard is already exists in the array
     * or not.
     */
    public function checkGcdAmtExists($gcdAmt, $gcdType, $gcdAnyAmts)
    {
        $found = false;
        foreach ($gcdAnyAmts as $data) {
            if (is_array($data)) {
                if ($data['gcd_amt'] == $gcdAmt && $data['gcd_type'] == $gcdType) {
                    $found = true;
                    break; // no need to loop anymore, as we have found the item => exit the loop
                }
            }

        }
        return $found;
    }

    /**
     * @param $gcdAmount
     * @param $giftBuyData
     * @return int
     * @author KT174
     * @description This method is used to check the gift card no with the amount from Fixed types
     */
    public function getFixedAmtGcdNum($gcdAmount, $giftBuyData)
    {
        if ($gcdAmount > 0 && count($giftBuyData) > 0) {
            $gcdUniqueNum = 0;
            foreach ($giftBuyData as $data) {
                if ($data['gcd_amt'] == $gcdAmount && $data['gcd_type'] == 1) {
                    $gcdUniqueNum = $data['gcd_unique_num'];
                    break;
                }
            }
            return $gcdUniqueNum;
        } else {
            return 0;
        }
    }

    /**
     * @param $observer
     * @description This method is used to redirect the user to redirect the admin user to referrer url after login
     * @author KT097
     */
    public function adminLoginSuccess($observer)
    {

        header('Location:' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}
