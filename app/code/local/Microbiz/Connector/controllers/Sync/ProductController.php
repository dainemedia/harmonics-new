<?php
//Version 108
/**
 * Extended product Controller of Catalog product controller
 *
 * @category   Ktree
 * @package    Ktree_ExtendedMbizConnector
 * @author      KT097
 */
// require_once("../../Helper/ERunActions.php");


class Microbiz_Connector_Sync_ProductController extends Mage_Core_Controller_Front_Action
{

    const MAX_QTY_VALUE = 99999999.9999;

    public function saveSyncRecordsAction()
    {
        $productId = $this->getRequest()->getParam('id');
        $response = array();
        $productUpdatedData = Mage::getModel('syncmbizstatus/syncmbizstatus')->getCollection()->addFieldToFilter('sync_status', 'PRD_BG')->addFieldToFilter('sync_header_id', 'PrdUpdate'.$productId)->getFirstItem()->getData();
        $response = json_decode($productUpdatedData['status_desc'],true);

        $productData = $response['data'];
        $configurableSimpleProductsData = $response['configurable_products_data'];

        $syncStatus = $response['sync_status'];

        $overAllSyncStatus = Mage::getStoreConfig('connector/settings/syncstatus');

        if ($overAllSyncStatus) {

            if ($syncStatus) {

                try {
                    // Save Magento Version Numbers into the Relation Tables
                    Mage::helper('microbiz_connector')->saveProductVerRel($productId, $productData, null, $response['user']);

                    if ($response['product_has_changes'] && is_array($response['old_attributes']) && !empty($response['old_attributes'])) {
                        $newValues = array_diff_assoc($response['new_attributes'], $response['old_attributes']);
                    } else {
                        $newValues = $response['new_attributes'];
                    }

                    $productRelation = Mage::helper('microbiz_connector')->checkObjectRelation($productId, 'Product');

                    // $productRelation will return an array with magento_id ,
                    // Mbiz_id if the relation is exists.

                    if (isset($productData['configurable_products_data'])) {
                        foreach ($productData['configurable_attributes_data'] as $productAttributeOption) {
                            $configAttributes[] = $productAttributeOption['attribute_code'];
                        }
                        foreach ($configurableSimpleProductsData as $key => $child) {
                            $simpleProduct = Mage::getModel('catalog/product')->load($key);
                            $attributes = $simpleProduct->getAttributes();

                            $simpleConfigDetails = array();

                            foreach ($configAttributes as $configAttribute) {
                                $value = null;

                                if (array_key_exists($configAttribute, $attributes)) {
                                    $attribute = $attributes["{$configAttribute}"];
                                    $value = $attribute->getFrontend()->getValue($simpleProduct);
                                }

                                $attributeDetails = Mage::getSingleton("eav/config")->getAttribute("catalog_product", $configAttribute);
                                $options = $attributeDetails->getSource()->getAllOptions(false);
                                $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
                                $code = $eavAttribute->getIdByCode('catalog_product', $configAttribute);

                                foreach ($options as $option) {
                                    if ($option["label"] == $value) {
                                        $attributeValueIndex = $option["value"];
                                    }
                                }

                                $simpleConfigDetails[] = array(
                                    'label' => $value,
                                    'attribute_id' => $code,
                                    'value_index' => $attributeValueIndex
                                );
                            }
                            $newChildProductIds[] = $key;
                            $productData['configurable_products_data'][$key] = $simpleConfigDetails;
                        }

                        $productInfo['configurable_attributes_data'] = $productData['configurable_attributes_data'];
                        $simplePrdIds =  $response['simple_product_ids'];
                        $productInfo['configurable_products_data'] = $productData['configurable_products_data'];
                        $removedIds = array_diff($simplePrdIds, $newChildProductIds);


                    }

                    $categoryIds = $response['categoryIds'];
                        $categoryIds = array_unique($categoryIds);
                    $productData['category_ids'] = serialize($categoryIds);

                    $updateFullInfo = Mage::registry('update_full_product_info');

                    if (!($productRelation) || $updateFullInfo) {
                        if ($productData['type_id'] == 'simple') {
                            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($productId);
                            $productData['parentIds'] = $parentIds;
                        }

                        $store_price = $productData['store_price'];

                        if (!$store_price || $store_price <= 0) {
                            $productData['store_price'] = $productData['price'];
                        }

                        if (!$response['is_newly_created']) {
                            $productInfo = Mage::getModel('Microbiz_Connector_Model_Observer')->getProductInfo($productId);
                        } else {
                            $productInfo['is_newly_created'] = 1;
                        }

                        if (isset($productData['media_gallery'])) {
                            foreach ($productData['media_gallery'] as $key => $media) {
                                $productData['media_gallery'][$key] = json_decode($media);
                            }
                        }

                        $i = 0;

                        if (isset($productData['media_gallery']['images'])) {
                            foreach ($productData['media_gallery']['images'] as $images) {
                                $productData['media_gallery']['images'][$i] = (array)$images;
                                $i++;
                            }
                        }

                        //converting stock item array into stock data array
                        if (isset($productData['stock_item'])) {
                            foreach ($productData['stock_item'] as $key => $value) {
                                $productData['stock_data'][$key] = $value;
                            }
                        }

                        foreach ($productData as $k1 => $v1) {
                            if (!is_array($v1)) {
                                if (!isset($productInfo[$k1]) || $productData[$k1] != $productInfo[$k1]) {
                                    $update_items[$k1] = $productData[$k1];
                                }
                            }
                        }

                        if (isset($productData['group_price'])) {
                            foreach ($productData['group_price'] as $k1 => $v1) {
                                if (is_array($v1)) {
                                    foreach ($v1 as $groupkey => $groupval) {
                                        if ($productData['group_price'][$k1][$groupkey] != $productInfo['group_price'][$k1][$groupkey]) {
                                            $update_items['group_price'][$k1] = $productData['group_price'][$k1];
                                        }
                                    }
                                }
                            }
                        }

                        if (isset($update_items['group_price']) && count($update_items['group_price'])) {
                            $update_items['group_price'] = serialize($update_items['group_price']);
                        }

                        if (isset($productData['tier_price'])) {
                            foreach ($productData['tier_price'] as $k1 => $v1) {
                                if (is_array($v1)) {
                                    foreach ($v1 as $groupkey => $groupval) {
                                        if ($productData['tier_price'][$k1][$groupkey] != $productInfo['tier_price'][$k1][$groupkey]) {
                                            $update_items['tier_price'][$k1] = $productData['tier_price'][$k1];
                                        }
                                    }
                                }
                            }
                        }

                        if (isset($update_items['tier_price']) && count($update_items['tier_price'])) {
                            $update_items['tier_price'] = serialize($update_items['tier_price']);
                        }

                        foreach ($productData['stock_item'] as $k1 => $v1) {
                            if (!is_array($v1)) {
                                if (!isset($productInfo['stock_item'][$k1]) || $productData['stock_item'][$k1] != $productInfo['stock_item'][$k1]) {
                                    $update_items[$k1] = $productData['stock_item'][$k1];
                                }
                            }
                        }

                        if (isset($productData['media_gallery']['images'])) {
                            foreach ($productData['media_gallery']['images'] as $k1 => $v1) {
                                $update_items['media_gallery']['images'][$k1] = array_diff($productData['media_gallery']['images'][$k1], $productInfo['media_gallery']['images'][$k1]);
                                unset($update_items['media_gallery']['images'][$k1]['url']);
                                if (!count($update_items['media_gallery']['images'][$k1]))
                                    unset($update_items['media_gallery']['images']);
                            }
                        }

                        if (isset($update_items['media_gallery']['images']) && count($update_items['media_gallery']['images'])) {
                            $update_items['media_gallery']['images'] = serialize($update_items['media_gallery']['images']);
                        }

                        if (isset($update_items['original_inventory_qty'])) {
                            unset($update_items['original_inventory_qty']);
                        }

                        if (isset($update_items['use_config_gift_message_available'])) {
                            unset($update_items['use_config_gift_message_available']);
                        }

                        $stockData = $productData['stock_item'];

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

                        unset($productData['stock_item']);
                        //for calculating price Excluding tax

                        $tax_helper = Mage::getSingleton('tax/calculation');
                        $tax_request = $tax_helper->getRateOriginRequest();
                        $tax_request->setProductClassId($productData['tax_class_id']);
                        $tax = $tax_helper->getRate($tax_request);
                        $calculator = Mage::getSingleton('tax/calculation');
                        $price = $productData['price'];
                        $storePrice = $productData['store_price'];

                        if ((!$storePrice || $storePrice <= 0) && isset($price)) {
                            if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                $tax_amount = $calculator->calcTaxAmount($price, $tax, true, true);
                                $productData['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($productId, $price);
                                $productData['tax_amount'] = $tax_amount;
                            } else {
                                $productData['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($productId, $price);
                            }
                        } else {
                            if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                $tax_amount = $calculator->calcTaxAmount($storePrice, $tax, true, true);
                                $productData['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($productId, $storePrice);
                                $productData['tax_amount'] = $tax_amount;
                            } else {
                                $productData['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($productId, $storePrice);
                            }
                        }

                        if (isset($productData['price'])) {
                            if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                $tax_amount = $calculator->calcTaxAmount($price, $tax, true, true);
                                $productData['price'] = Mage::helper('microbiz_connector')->getIncExlPrice($productId, $price);
                                $productData['price_including_tax'] = $price;
                                $productData['tax_amount'] = $tax_amount;
                            } else {
                                $productData['price'] = Mage::helper('microbiz_connector')->getIncExlPrice($productId, $price);
                            }
                        }

                        $productData['attribute_set_id'] = $productData['attribute_set_id'];
                        $productData['api_web_price_tax_setting'] = Mage::getStoreConfig('tax/calculation/price_includes_tax');
                        Mage::getModel('Microbiz_Connector_Model_Observer')->setProductSyncInfo($productId, $productData, $response['user']);

                    } else {

                        $tax_helper = Mage::getSingleton('tax/calculation');
                        $tax_request = $tax_helper->getRateOriginRequest();
                        $tax_request->setProductClassId($productData['tax_class_id']);
                        $tax = $tax_helper->getRate($tax_request);
                        $calculator = Mage::getSingleton('tax/calculation');
                        $price = $newValues['price'];
                        $storePrice = $newValues['store_price'];

                        if ((!$storePrice || $storePrice <= 0) && isset($price)) {
                            if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                $tax_amount = $calculator->calcTaxAmount($price, $tax, true, true);
                                $newValues['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($productId, $price);
                                $newValues['tax_amount'] = $tax_amount;
                            } else {
                                $newValues['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($productId, $price);
                            }
                        } else {
                            if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                $tax_amount = $calculator->calcTaxAmount($storePrice, $tax, true, true);
                                $newValues['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($productId, $storePrice);
                                $newValues['tax_amount'] = $tax_amount;
                            } else {
                                $newValues['store_price'] = Mage::helper('microbiz_connector')->getIncExlPrice($productId, $storePrice);
                            }
                        }

                        if (isset($newValues['price'])) {
                            if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                                $tax_amount = $calculator->calcTaxAmount($price, $tax, true, true);
                                //$newValues['price'] = $price_excluding_tax;
                                $newValues['price'] = Mage::helper('microbiz_connector')->getIncExlPrice($productId, $price);
                                $newValues['price_including_tax'] = $price;
                                $newValues['tax_amount'] = $tax_amount;
                            } else {
                                $newValues['price'] = Mage::helper('microbiz_connector')->getIncExlPrice($productId, $price);
                            }
                        }


                        $updated_info = $newValues;
                        $updated_info['type_id'] = $productData['type_id'];
                        $parentIds = array();
                        $updated_info['category_ids'] = $productData['category_ids'];
                        if ($productData['type_id'] == 'simple') {

                            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($productId);
                            $updated_info['parentIds'] = $parentIds;
                        }

                        $updated_info['attribute_set_id'] = $productData['attribute_set_id'];
                        $updated_info['sku'] = $productData['sku'];
                        $updated_info['api_web_price_tax_setting'] = Mage::getStoreConfig('tax/calculation/price_includes_tax');


                        Mage::getModel('Microbiz_Connector_Model_Observer')->setProductSyncInfo($productId, $updated_info, $response['user']);
                    }
                    // for configurable child products sync

                    if ($productData['type_id'] == 'configurable') {

                        $configurable_products_data = $productData['configurable_products_data'];
                        if (count($removedIds)) {
                            foreach ($removedIds as $configremoveproduct) {
                                $configurable_products_data[$configremoveproduct] = $configremoveproduct;
                            }
                        }

                        foreach ($configurable_products_data as $key => $childProduct) {

                            $simplePrdInfo = Mage::getModel('Microbiz_Connector_Model_Observer')->getProductInfo($key);
                            $child_product_id = $simplePrdInfo['entity_id'];
                            $childproductinfo1 = array();
                            $relation_childproduct_data = Mage::helper('microbiz_connector')->checkObjectRelation($child_product_id, 'Product');

                            if (!($relation_childproduct_data)) {
                                unset($simplePrdInfo['_cache_instance_product_ids']);
                                foreach ($simplePrdInfo as $key => $update) {
                                    if (is_array($update)) {
                                        $childproductinfo1[$key] = serialize($update);
                                    } else {
                                        $childproductinfo1[$key] = $update;
                                    }
                                }
                            }
                            $childPrdParentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($child_product_id);
                            (!in_array($child_product_id,$removedIds)) ? $childPrdParentIds[] = $productId : $childPrdParentIds = array_diff( $childPrdParentIds, $productId );
                            $childPrdParentIds = array_unique($childPrdParentIds);
                            Mage::Log($child_product_id,null,'parentIds.log');
                            Mage::Log($childPrdParentIds,null,'parentIds.log');
                            $childproductinfo1['parentIds'] = $childPrdParentIds;

                            //for calculating price Excluding tax
                            $childProductObject = Mage::getModel('catalog/product')->load($child_product_id);
                            $tax_helper = Mage::getSingleton('tax/calculation');
                            $tax_request = $tax_helper->getRateOriginRequest();
                            $tax_request->setProductClassId($childProductObject->getTaxClassId());
                            $tax = $tax_helper->getRate($tax_request);
                            $calculator = Mage::getSingleton('tax/calculation');
                            $price = $childProductObject->getPrice();
                            $storePrice = $childProductObject->getStorePrice();
                            if ((!$storePrice || $storePrice <= 0) && isset($price)) {
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
                            //for calculating price Excluding tax End
                            $cats = $childProductObject->getCategoryIds();
                            $childproductinfo1['category_ids'] = array_unique($cats);
                            $childproductinfo1['type_id'] = $childProductObject->getTypeId();
                            $childproductinfo1['attribute_set_id'] = $childProductObject->getAttributeSetId();
                            $childproductinfo1['sku'] = $childProductObject->getSku();
                            $childproductinfo1['api_web_price_tax_setting'] = Mage::getStoreConfig('tax/calculation/price_includes_tax');

                            Mage::getModel('Microbiz_Connector_Model_Observer')->setProductSyncInfo($child_product_id, $childproductinfo1, $response['user']);

                        }
                    }
                } catch (Mage_Core_Exception $e) {
                    Mage::log($e->getMessage() . '<br/>' . $e->getTraceAsString(), null, 'runcaction.log');
                }
            }
        }
        try{
            Mage::getModel('syncmbizstatus/syncmbizstatus')->load($productUpdatedData['id'])->delete();
        }
        catch(Exception $e) {
            Mage::log($e->getMessage(),null,'productBg.log');
        }
    }
}
