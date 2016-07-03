<?php

//version 129
class Microbiz_Connector_Helper_Data extends Mage_Core_Helper_Abstract
{


    public function testConnection($postdata){

        /*$jsonresponse['status'] = 'SUCCESS';
            $jsonresponse['message'] = $this->__('Test Connection Success With Microbiz Instance');
         return $jsonresponse;*/
        $siteType = $postdata['mbiz_sitetype'];
        if($siteType==1)
    {
            $url=$postdata['mbiz_sitename'];
            $url = 'https://'.$url.'.microbiz.com';
        }
        else {
            $url=$postdata['mbiz_sitename'];
        }
        $api_user = $postdata['mbiz_username'];
        $api_key = $postdata['mbiz_password'];
        //$url = $url.'.microbiz.com';
        //$url= 'http://pos.ktree.org/branches/initialsync';
        $url    = $url.'/index.php/api/mbizInstanceTestConnection';
        Mage::log("came to testConnection");		// prepare url for the rest call;
        Mage::log($url);
        $method = 'POST';
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
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);


        $response = curl_exec($handle); // send curl request to microbiz
        $response = json_decode($response, true);
        $code = curl_getinfo($handle);
        $jsonresponse = array();
        Mage::log($response);
        Mage::log($code);
        if ($code['http_code'] == 200) {
            $jsonresponse['status'] = 'SUCCESS';
            $jsonresponse['message'] = $this->__('Test Connection Success With Microbiz Instance');
        } else if ($code['http_code'] == 500) {
            $jsonresponse['status'] = 'ERROR';
            $jsonresponse['message'] = $code['http_code'] . ' - Internal Server Error' . $response['message'];
        } else if ($code['http_code'] == 0) {
            $jsonresponse['status'] = 'ERROR';
            $jsonresponse['message'] = $code['http_code'] . ' - Please Check the API Server URL' . $response['message'];
        } else {
            $jsonresponse['status'] = 'ERROR';
            $jsonresponse['message'] = $code['http_code'] . ' - ' . $response['message'];
        }
        return $jsonresponse;
    }

    /*
     * helper function for getting Api information from Plugin configuration
     * @return array of config information
     * @author KT097
     **/
    public function getApiDetails()
    {

        $apiInformation = array();
        $apiInformation['api_server'] = Mage::getStoreConfig('connector/settings/api_server');
        $apiInformation['instance_id'] = Mage::getStoreConfig('connector/settings/instance_id');
        $apiInformation['api_user'] = Mage::getStoreConfig('connector/settings/api_user');
        $apiInformation['api_key'] = Mage::getStoreConfig('connector/settings/api_key');
        $apiInformation['display_name'] = Mage::getStoreConfig('connector/settings/display_name');
        $apiInformation['syncstatus'] = Mage::getStoreConfig('connector/settings/syncstatus');
        return $apiInformation;
    }

    /*
     * helper function for getting Batch Size information from configuration
     * @return batch size of config information
     * @author KT097
     **/
    public function getBatchSize()
    {

        $batchSize = Mage::getStoreConfig('connector/batchsizesettings/batchsize');
        return $batchSize;
    }

    /*
     * helper function for Check the object relation  exists in mbiz rel tables
     * @param objectId it will hold the respective id of product/customer/customer address/attributeset
     * @param objectType. It is the value which model we are finding the  relation
     * @return true if relation exists
     * @author KT097
     **/
    public function checkObjectRelation($objectId, $objectType)
    {
        switch ($objectType) {
            case 'Product':
                $relationdata = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter('magento_id', $objectId)->addFieldToFilter('mbiz_id', array('nin' => array('', '0')))->setOrder('id', 'asc')->getData();
                break;
            case 'Customer':
                $relationdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getData();
                break;
            case 'CustomerAddressMaster':
                $relationdata = Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getData();
                break;
            case 'AttributeSets':
                $relationdata = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getData();
                break;
            case 'Attributes':
                $relationdata = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getData();
                break;
            case 'ProductCategories':
                $relationdata = Mage::getModel('mbizcategory/mbizcategory')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getData();
                break;
        }
        if (!count($relationdata)) {
            return false;
        }
        return true;
    }

    /*
     * helper function for getting App instance id from configuration
     * @return instance id
     * @author KT097
     **/
    public function getAppInstanceId()
    {

        $instance_id = Mage::getStoreConfig('connector/settings/instance_id');
        return $instance_id;
    }

    /*
     * helper function for Deleting App relations and store inventory
     * @param objectId it will hold the respective id of product/customer/customer address/attributeset
     * @param objectType. It is the value which model we are deleting relation
     * @return true if success
     * @author KT097
     **/
    public function deleteAppRelation($objectId, $objectType)
    {
        $relation = $this->checkObjectRelation($objectId, $objectType);
        switch ($objectType) {

            case 'Product':

                if ($relation) {

                    $relationdata = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getData();
                    $id = $relationdata[0]['id'];
                    $model = Mage::getModel('mbizproduct/mbizproduct')->load($id);
                    try {
                        $model->delete();
                    } catch (Mage_Core_Exception $e) {
                        $this->_fault('not_deleted', $e->getMessage());
                        // Some errors while deleting.
                    }

                    $productInventorys = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $objectId)->getData();
                    foreach ($productInventorys as $productInventory) {
                        $inventoryId = $productInventory['storeinventory_id'];
                        Mage::getModel('Microbiz_Connector_Model_Storeinventory_Api')->deleteMbizInventory($inventoryId);
                    }

                }
                break;
            case 'Customer':
                if ($relation) {
                    $relationdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getData();
                    $id = $relationdata[0]['id'];
                    $model = Mage::getModel('mbizcustomer/mbizcustomer')->load($id);
                    try {
                        $model->delete();
                    } catch (Mage_Core_Exception $e) {
                        $this->_fault('not_deleted', $e->getMessage());
                        // Some errors while deleting.
                    }
                }
                break;
            case 'CustomerAddressMaster':
                if ($relation) {
                    $relationdata = Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getData();
                    $id = $relationdata[0]['id'];
                    $model = Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->load($id);
                    try {
                        $model->delete();
                    } catch (Mage_Core_Exception $e) {
                        $this->_fault('not_deleted', $e->getMessage());
                        // Some errors while deleting.
                    }
                }
                break;
            case 'AttributeSets':
                if ($relation) {
                    $relationdata = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getData();
                    $id = $relationdata[0]['id'];
                    $model = Mage::getModel('mbizattributeset/mbizattributeset')->load($id);
                    try {
                        $model->delete();
                    } catch (Mage_Core_Exception $e) {
                        $this->_fault('not_deleted', $e->getMessage());
                        // Some errors while deleting.
                    }
                }
                break;
            case 'Attributes':
                if ($relation) {
                    $relationdata = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getData();
                    $id = $relationdata[0]['id'];
                    $model = Mage::getModel('mbizattribute/mbizattribute')->load($id);
                    try {
                        $model->delete();
                    } catch (Mage_Core_Exception $e) {
                        $this->_fault('not_deleted', $e->getMessage());
                        // Some errors while deleting.
                    }
                }
                break;
        }
        return true;
    }

    /**
     * For adding Simple Product to configurable Products
     * @param productId it will hold the simple product Id
     * @param associated_configurable_products array contaning the Magento configurable product ids
     * @return true on success
     * @author KT097
     */
    public function assignSimpleProductToConfigurable($productId, $configurableProducts)
    {
        try {
            foreach ($configurableProducts as $configurableProduct) {
                $config_product = Mage::getModel('catalog/product')->load($configurableProduct);
                if ($config_product->getTypeId() == 'configurable') {
                    $productAttributeOptions = $config_product->getTypeInstance(true)->getConfigurableAttributesAsArray($config_product);
                    foreach ($productAttributeOptions as $productAttributeOption) {
                        $configAttributes[] = $productAttributeOption['attribute_code'];
                    }
                    $simpleProductsId = Mage::getModel('catalog/product_type_configurable')->setProduct($config_product)->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions()->getAllIds();
                    $simpleProductsId[] = $productId;
                    $simpleProductsId = array_unique($simpleProductsId);
                    foreach ($simpleProductsId as $simpleProductId) {
                        $simpleProduct = Mage::getModel('catalog/product')->load($simpleProductId);
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
                                'value_index' => $attributeValueIndex
                            );
                        }
                        $configurableProductData[$simpleProductId] = $simpleconfiginfo;
                    }
                    $productData['configurable_products_data'] = $configurableProductData;
                    $config_product->setConfigurableProductsData($productData['configurable_products_data']);
                    $config_product->save();
                }
            }
        } catch (Exception $ex) {
            $exceptionArray = array();
            $exceptionArray['exception_desc'] = $ex->getMessage();
            return $exceptionArray;
        }
        return true;
    }

    /**
     * For removing Simple Products from configurable Products
     * @param productId it will hold the simple product Id
     * @param associated_configurable_products array contaning the Magento configurable product ids
     * @return true on success
     * @author KT097
     */
    public function removeSimpleProductFromConfigurable($productId, $configurableProducts)
    {

        try {
            foreach ($configurableProducts as $configurableProduct) {
                $config_product = Mage::getModel('catalog/product')->load($configurableProduct);
                if ($config_product->getTypeId() == 'configurable') {
                    $productAttributeOptions = $config_product->getTypeInstance(true)->getConfigurableAttributesAsArray($config_product);
                    foreach ($productAttributeOptions as $productAttributeOption) {
                        $configAttributes[] = $productAttributeOption['attribute_code'];
                    }
                    $simpleProductsId = Mage::getModel('catalog/product_type_configurable')->setProduct($config_product)->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions()->getAllIds();

                    foreach ($simpleProductsId as $simpleProductId) {
                        if ($productId != $simpleProductId) {
                            $simpleProduct = Mage::getModel('catalog/product')->load($simpleProductId);
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
                                    'value_index' => $attributeValueIndex
                                );
                            }
                            $configurableProductData[$simpleProductId] = $simpleconfiginfo;
                        }
                    }
                    $productData['configurable_products_data'] = $configurableProductData;
                    $config_product->setConfigurableProductsData($productData['configurable_products_data']);
                    $config_product->save();
                    $resource = Mage::getSingleton('core/resource');
                    $table = $resource->getTableName('catalog_product_super_link');
                    $sql = 'DELETE FROM  ' . $table . ' WHERE product_id ="' . $productId . '"  and  parent_id = ' . $config_product->getId();

                    $writeConnection = $resource->getConnection('core_write');
                    $writeConnection->query($sql);
                }

            }
        } catch (Exception $ex) {
            $exceptionArray = array();
            $exceptionArray['exception_desc'] = $ex->getMessage();
            return $exceptionArray;
        }
        return true;
    }

    /**
     * For adding/removing Simple Products into configurable Products
     * @param productId it will hold the simple product Id
     * @param associated_configurable_products array contaning the MBiz configurable product ids
     * @return true on success
     * @author KT097
     */
    public function saveSimpleConfig($productId, $associated_configurable_products)
    {

        $productModel = Mage::getModel('catalog/product')->load($productId);
        if ($productModel->getTypeId() == 'simple') {
            $oldParentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($productId);
            $newParentIds = array();
            foreach ($associated_configurable_products as $associated_configurable_product) {
                $relationproductdata = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter('mbiz_id', $associated_configurable_product)->setOrder('id', 'asc')->getData();
                if ($relationproductdata) {
                    $newParentIds[] = $relationproductdata[0]['magento_id'];
                }
            }
            $addedIds = array_diff($newParentIds, $oldParentIds);
            if (count($addedIds)) {
                $saveConfigReturn = Mage::helper('microbiz_connector')->assignSimpleProductToConfigurable($productId, $newParentIds);
                if (is_array($saveConfigReturn)) {
                    return $saveConfigReturn;
                }
            }
            $removedIds = array_diff($oldParentIds, $newParentIds);
            if (count($removedIds)) {
                $removeConfigReturn = Mage::helper('microbiz_connector')->removeSimpleProductFromConfigurable($productId, $removedIds);
                if (is_array($removeConfigReturn)) {
                    return $removeConfigReturn;
                }
            }
        }

        return true;

    }

    /**
     * @author KT174
     * @description This method is used to generate  alphanumeric string based on the length passed
     * @return-  alphanumeric string.
     */
    public function mbizGenerateUniqueString($length)
    {
        Mage::log("came to setup file");
        $key = '';
        $keys = array_merge(range(0, 9), range('A', 'Z'));

        for ($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }

        return $key;
    }

    /**
     * @author KT174
     * @description This method is used to generate  alphanumeric username for creating Api User from the plugin
     * installation wizard
     * @return-  alphanumeric string.
     */
    public function mbizGenerateApiUserName($length)
    {
        // Set allowed chars
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

        // Create username
        $username = "";
        for ($i = 0; $i < $length; $i++) {
            $username .= $chars[mt_rand(0, strlen($chars))];
        }
        return $username;
    }

    /**
     * @author KT174
     * @description This method is used to generate  alphanumeric password for Api User from the plugin
     * installation wizard
     * @return-  alphanumeric string.
     */
    public function mbizGenerateApiPassword($length)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
        $password = substr(str_shuffle($chars), 0, $length);
        return $password;
    }



    /**
     * @param $itemId
     * @param $orderId
     * @return array
     * @author KT174
     * @description This method is used to get the itemid,orderid and return the giftcard sale information.
     */
    public function getGcdDetails($itemId, $orderId)
    {
        $gcdDetails = array();
        $gcdDetails = Mage::getModel('mbizgiftcardsale/mbizgiftcardsale')->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('order_item_id', $itemId)
            ->getData();
        return $gcdDetails;
    }

    /*
     * Get Currency Based on Microbiz Store from Storeinventory Header table
     * @param StoreId id of Microbiz store selected during order
     * @return return store currency
     */
    public function getMbizStoreCurrency($storeId)
    {

        if (!$storeId) {
            $storeInfo = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->getCollection()->addFieldToFilter('company_id', 1)->getFirstItem();
        } else {
            $storeInfo = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->getCollection()->addFieldToFilter('store_id', $storeId)->getFirstItem();
        }
        return ($storeInfo) ? $storeInfo['company_currency'] : false;
    }

    /*
         * Get converted prices Currency Based on Microbiz Store from Storeinventory Header table
         * @param $itemInfo order item information
         * @return converted prices for item
    */
    public function convertItemPrices($itemInfo, $mbizStoreCurrency, $orderCurrencyCode, $baseCurrencyCode, $currencyRate)
    {
        $simpleIntemData = array();
        if ($mbizStoreCurrency && $mbizStoreCurrency == $orderCurrencyCode) {
            $simpleIntemData['unit_price'] = $itemInfo['price'];
            $simpleIntemData['item_selling_price'] = $itemInfo['price'];
            $simpleIntemData['total_amount'] = $itemInfo['row_total'];
            $simpleIntemData['tax_amount'] = $itemInfo['tax_amount'];
            $simpleIntemData['total_tax_amount'] = $itemInfo['tax_amount'];
            $simpleIntemData['total_discount_amount'] = $itemInfo['discount_amount'];
        } else if ($mbizStoreCurrency == $baseCurrencyCode) {
            $simpleIntemData['unit_price'] = $itemInfo['base_price'];
            $simpleIntemData['item_selling_price'] = $itemInfo['base_price'];
            $simpleIntemData['total_amount'] = $itemInfo['base_row_total'];
            $simpleIntemData['tax_amount'] = $itemInfo['base_tax_amount'];
            $simpleIntemData['total_tax_amount'] = $itemInfo['base_tax_amount'];
            $simpleIntemData['total_discount_amount'] = $itemInfo['base_discount_amount'];
        } else {
            $simpleIntemData['unit_price'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($itemInfo['base_price'], $currencyRate);
            $simpleIntemData['item_selling_price'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($itemInfo['base_price'], $currencyRate);
            $simpleIntemData['total_amount'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($itemInfo['base_row_total'], $currencyRate);
            $simpleIntemData['tax_amount'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($itemInfo['base_tax_amount'], $currencyRate);
            $simpleIntemData['total_tax_amount'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($itemInfo['base_tax_amount'], $currencyRate);
            $simpleIntemData['total_discount_amount'] = Mage::helper('microbiz_connector')->convertPriceBasedOnRate($itemInfo['base_discount_amount'], $currencyRate);
        }
        return $simpleIntemData;
    }

    public function convertPriceBasedOnRate($price, $rate)
    {
        return number_format(($price * $rate), 4);
    }

    public function getCurrencyRate($from, $to)
    {
        $url = 'http://www.webservicex.net/CurrencyConvertor.asmx/ConversionRate?FromCurrency=' . $from . '&ToCurrency=' . $to;
        $httpClient = new Varien_Http_Client();
        $response = $httpClient
            ->setUri($url)
            ->setConfig(array('timeout' => Mage::getStoreConfig('currency/webservicex/timeout')))
            ->request('GET')
            ->getBody();

        $xml = simplexml_load_string($response, null, LIBXML_NOERROR);
        if (!$xml) {
            return false;
        }
        return (float)$xml;
    }

    /*
     * function to get Object relation
     */
    public function getObjectRelation($objectId, $objectType, $instance = 'Magento')
    {
        $fieldValue = ($instance == 'Magento') ? 'magento_id' : 'mbiz_id';
        $relationdata = '';
        switch ($objectType) {
            case 'Product':
                $relationdata = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter($fieldValue, $objectId)->setOrder('id', 'asc')->getFirstItem()->getData();
                break;
            case 'Customer':
                $relationdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter($fieldValue, $objectId)->setOrder('id', 'asc')->getFirstItem()->getData();
                break;
            case 'CustomerAddressMaster':
                $relationdata = Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->getCollection()->addFieldToFilter($fieldValue, $objectId)->setOrder('id', 'asc')->getFirstItem()->getData();
                break;
            case 'AttributeSets':
                $relationdata = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->addFieldToFilter($fieldValue, $objectId)->setOrder('id', 'asc')->getFirstItem()->getData();
                break;
            case 'Attributes':
                $relationdata = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter($fieldValue, $objectId)->setOrder('id', 'asc')->getFirstItem()->getData();
                break;
            case 'ProductCategories':
                $relationdata = Mage::getModel('mbizcategory/mbizcategory')->getCollection()->addFieldToFilter($fieldValue, $objectId)->setOrder('id', 'asc')->getFirstItem()->getData();
                break;
        }
        if (!count($relationdata)) {
            return false;
        }
        return $relationdata['magento_id'];
    }

    /*
     * function to check any record exists in mbiz status tran
     */
    public function checkMbizSyncHeaderStatus($headerId)
    {
        $syncMbizStatusData = Mage::getModel('syncmbizstatus/syncmbizstatus')->getCollection()->addFieldToFilter('sync_header_id', $headerId)->getFirstItem()->getData();
        return $syncMbizStatusData;
    }

    /*
     * function to create Sync record in mbiz status table on fatal error
     */
    public function createMbizSyncStatus($syncHeaderId, $syncStatus, $exception = null)
    {

        $syncMbizStatus = array();
        $syncMbizStatus['sync_header_id'] = $syncHeaderId;
        $syncMbizStatus['sync_status'] = $syncStatus;
        $syncMbizStatus['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
        $syncMbizStatus['status_desc'] = $exception;
        $syncMbizStatusData = Mage::helper('microbiz_connector')->checkMbizSyncHeaderStatus($syncHeaderId);
        $id = $syncMbizStatusData['id'];
        ($id) ? Mage::getModel('syncmbizstatus/syncmbizstatus')->load($id)->setData($syncMbizStatus)->setId($id)->save() : Mage::getModel('syncmbizstatus/syncmbizstatus')->setData($syncMbizStatus)->save();
        //Mage::getModel('syncmbizstatus/syncmbizstatus')->setSyncHeaderId($syncHeaderId)->setData($syncMbizStatus)->save();
        return true;
    }

    /**
     * @param $category
     * @param bool $recursive
     * @return array
     * @author KT174
     * @description This method is used to find out the child categories of a given category recursive both active and inactive
     */
    public function getChildrenIds($category, $recursive = true)
    {
        //Mage::log("came to helper",null,'catsync.log');
        $categoryId = $category->getId();
        //Mage::log($categoryId,null,'catsync.log');
        $allCategories = Mage::getModel('Microbiz_Connector_Model_Category_Api')->tree($categoryId);
        $childrenIds = array();
        //Mage::log($allCategories,null,'catsync.log');
        if (!empty($allCategories)) {
            $allChildCategories = $allCategories['children'];
            $childrenCount = $allCategories['children_count'];
            if (!empty($allChildCategories) && $childrenCount > 0) {


                $allChildIds = Mage::helper('microbiz_connector')->getAllChildIds($allChildCategories, $childrenIds);
                //Mage::log($allChildIds,null,'catsync.log');

            } else {
                return $childrenIds;
            }

        } else {
            return $childrenIds;
        }
        return $allChildIds;
    }

    /**
     * @param $allChildCategories
     * @param array $chilrenIds
     * @return array
     * @author KT174
     * @description This Method is used to find the category ids recursively
     */
    public function getAllChildIds($allChildCategories, &$chilrenIds = array())
    {
        foreach ($allChildCategories as $chilren) {
            $chilrenIds[] = $chilren['entity_id'];
            $childCount = $chilren['children_count'];

            if ($childCount > 0) {
                Mage::helper('microbiz_connector')->getAllChildIds($chilren['children'], $chilrenIds);
            }
        }

        return $chilrenIds;
    }

    /**
     * Helper function for Saving Attributeset Sync Info
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
        $relationAttributeSetdata = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->addFieldToFilter('magento_id', $attributesetId)->setOrder('id', 'asc')->getData();

        $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('obj_id', $attributesetId)->addFieldToFilter('model_name', 'AttributeSets')->addFieldToFilter('status', array('in' => array('Pending', 'Failed')))->setOrder('header_id', 'desc')->getData();
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
                    $this->_fault('not_deleted', $e->getMessage());
                    // Some errors while deleting.
                }
            }

        } else {
            $user = Mage::getSingleton('admin/session')->getUser()->getFirstname();
            $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
            $attributeSetData['model_name'] = 'AttributeSets';
            $attributeSetData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $attributeSetData['obj_id'] = $attributesetId;
            $attributeSetData['mbiz_obj_id'] = $relationAttributeSetdata[0]['mbiz_id'];
            $attributeSetData['created_by'] = $user;
            $attributeSetData['created_time'] = $date;
            $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                ->setData($attributeSetData)
                ->save();
            $header_id = $model['header_id'];
        }

        foreach ($attributeSetInformation as $key => $updateditem) {
            $attributeSetInfo['header_id'] = $header_id;
            $attributeSetInfo['attribute_id'] = '';
            $attributeSetInfo['attribute_name'] = $key;
            if (!is_array($updateditem)) {
                $attributeSetInfo['attribute_value'] = $updateditem;
            } else {
                $attributeSetInfo['attribute_value'] = serialize($updateditem);
            }
            $attributeSetInfo['created_by'] = $user;
            $attributeSetInfo['created_time'] = $date;
            $model = Mage::getModel('syncitems/syncitems')
                ->setData($attributeSetInfo)
                ->save();
        }
        return true;
    }

    /**
     * Helper function for get All Attributeset relations
     * @author KT097
     */
    public function getAllAttributeSetsRelation()
    {
        $syncAttributeSetIds = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('model_name','AttributeSets')
            ->addFieldToFilter('status',array('in' => array('Pending','Processing')))->getColumnValues('obj_id');
        $collection = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection();

        if(!empty($syncAttributeSetIds)) {
            $collection->addFieldToFilter('magento_id',array('nin'=>$syncAttributeSetIds));
        }
        $collection->getData();
        $attributeSetRelationResult = array();

        foreach ($collection as $attributeSet) {
            ($attributeSet['magento_id']) ? $attributeSetRelationResult[] = $attributeSet['magento_id'] : null;
        }
        return $attributeSetRelationResult;
    }

    /**
     * @param $objectType
     * @param $Id
     * @param $magVer
     * @param $mbizVer
     * @return bool
     * @author KT174
     * @description This method is used to Save Mage Version Numbers into the Relation Tables whenever any Ojbect is
     * Updated in Magento.
     */
    public function saveMageVersions($objectType, $Id, $magVer, $mbizVer)
    {
        Mage::log("came to save mage versions", null, 'saveversion.log');
        switch ($objectType) {
            case 'AttributeSets' :
                $attrSetModel = Mage::getModel('mbizattributeset/mbizattributeset')->load($Id);

                $attrSetsData = $attrSetModel->getData();
                Mage::log($attrSetsData, null, 'saveversion.log');

                $attrSetModel->setMageVersionNumber($magVer + 1);
                $attrSetModel->setLastUpdatedFrom('MAG');
                if (Mage::getSingleton('admin/session')->isLoggedIn()) {

                    $user = Mage::getSingleton('admin/session');
                    $user = $user->getUser()->getFirstname();
                    $attrSetModel->setModifiedBy($user);

                }
                $attrSetModel->setModifiedAt(Now());
                $attrSetModel->save();

                /*Updating all Attributes and AttributeOptions Version Numbers Incrementing by 1 for Attributes and Updating
                Attribute Version Number for Options*/

                $currentAttributes = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('mbiz_attr_set_id', $attrSetsData['mbiz_id'])->getData();

                if (!empty($currentAttributes)) {
                    foreach ($currentAttributes as $attributeRel) {
                        $attrModel = Mage::getModel('mbizattribute/mbizattribute')->load($attributeRel['id']);
                        $attrModel->setMageVersionNumber($attributeRel['mage_version_number'] + 1);
                        $attrModel->setLastUpdatedFrom('MAG');
                        $attrModel->setModifiedBy($user);
                        $attrModel->setModifiedAt(Now());
                        $attrModel->setId($attributeRel['id'])->save();

                        $mbizAttributeId = $attributeRel['mbiz_id'];

                        if ($mbizAttributeId) {
                            $mageAttrVerNo = $attributeRel['mage_version_number'] + 1;
                            $optionModel = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('mbiz_attr_id', $mbizAttributeId)->getData();

                            if (!empty($optionModel)) {
                                foreach ($optionModel as $optionRel) {
                                    $optModel = Mage::getModel('mbizattributeoption/mbizattributeoption')->load($optionRel['id']);
                                    $optModel->setMageVersionNumber($mageAttrVerNo);
                                    $optModel->setLastUpdatedFrom('MAG');
                                    $optModel->setModifiedBy($user);
                                    $optModel->setModifiedAt(Now());
                                    $optModel->setId($optionRel['id'])->save();
                                }
                            }


                        }
                    }
                }

                /*Updating all Attributes and AttributeOptions Version Numbers Incrementing by 1 for Attributes and Updating
                Attribute Version Number for Options*/


                break;

            case 'Attributes' :
                $attrModel = Mage::getModel('mbizattribute/mbizattribute')->load($Id);

                $attrData = $attrModel->getData();
                $attrModel->setMageVersionNumber($magVer + 1);
                $attrModel->setLastUpdatedFrom('MAG');
                if (Mage::getSingleton('admin/session')->isLoggedIn()) {

                    $user = Mage::getSingleton('admin/session');
                    $user = $user->getUser()->getFirstname();
                    $attrModel->setModifiedBy($user);

                }
                $attrModel->setModifiedAt(Now());
                $attrModel->save();

                /*Updating Options Version Numbers when attribute is saved*/

                $optionModel = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('mbiz_attr_id', $attrData['mbiz_id'])->getData();

                if (!empty($optionModel)) {
                    foreach ($optionModel as $optionRel) {
                        $optModel = Mage::getModel('mbizattributeoption/mbizattributeoption')->load($optionRel['id']);
                        $optModel->setMageVersionNumber($optionRel['mage_version_number'] + 1);
                        $optModel->setLastUpdatedFrom('MAG');
                        $optModel->setModifiedBy($user);
                        $optModel->setModifiedAt(Now());
                        $optModel->setId($optionRel['id'])->save();
                    }
                }

                /*Updating Options Version Numbers when attribute is saved*/
                break;

            case 'Product' :
                $prdModel = Mage::getModel('mbizproduct/mbizproduct')->load($Id);

                $prdModel->setMageVersionNumber($magVer + 1);
                $prdModel->setLastUpdatedFrom('MAG');
                if (Mage::getSingleton('admin/session')->isLoggedIn()) {

                    $user = Mage::getSingleton('admin/session');
                    $user = $user->getUser()->getFirstname();
                    $prdModel->setModifiedBy($user);

                }
                $prdModel->setModifiedAt(Now());
                $prdModel->save();
                break;

        }
        return true;
    }

    /**
     * @param $objectId
     * @param $objectType
     * @return array
     * @author KT174
     * @description This method is used to check the Relation and return the relation data if exists.
     */
    public function checkIsObjectExists($objectId, $objectType)
    {
        $relationdata = array();
        switch ($objectType) {
            case 'Product':
                $relationdata = Mage::getModel('mbizproduct/mbizproduct')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getFirstItem()->getData();
                break;
            case 'AttributeSets':
                $relationdata = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getFirstItem()->getData();
                break;
            case 'Attributes':
                $relationdata = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getFirstItem()->getData();
                break;
            case 'AttributeOptions':
                $relationdata = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('magento_id', $objectId)->setOrder('id', 'asc')->getFirstItem()->getData();
                break;
        }

        return $relationdata;
    }

    public function getMbizVersionNumbers($objId, $objModel)
    {
        Mage::log("came to heler", null, 'version.log');
        Mage::log($objId, null, 'version.log');
        Mage::log($objModel, null, 'version.log');
        /*get versions from microbiz code starts here*/
        $apiInformation = Mage::helper('microbiz_connector')->getApiDetails();
        $url = $apiInformation['api_server'];
        $queryString = '?objId=' . $objId . '&objModel=' . $objModel;

        $url = $url . '/index.php/api/getObjectVersionNumber' . $queryString; // prepare url for the rest call
        //$url = 'http://ktc1.ktree.org/index.php/connector/index/getMbizVersionNumbers'.$queryString;
        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];

        //Mage::log($url,null,'rel.log');

        $method = 'GET';
        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: ' . $api_user,
            'X-MBIZPOS-PASSWORD: ' . $api_key
        );

        $handle = curl_init(); //curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($handle); // send curl request to microbiz
        /*if($objModel=='Product') {
            $response = '{"prd_mage_rel_id":"187","product_id":"21055","instance_id":"1","mage_prd_id":"190",
        "mage_sku":"ks_plum_cake","entity_type_id":"4","mbiz_version_number":"100","mage_version_number":"100",
        "last_updated_from":"Magento","modified_by":"1","modified_time":"2015-01-05 23:41:58"}';
        }
    else {
        $response = '{"prd_mage_rel_id":"187","product_id":"21055","instance_id":"1","mage_prd_id":"190",
        "mage_sku":"ks_plum_cake","entity_type_id":"4","mbiz_version_number":"100","mage_version_number":"100",
        "last_updated_from":"Magento","modified_by":"1","modified_time":"2015-01-05 23:41:58"}';
    }*/


        $response = json_decode($response, true);
        //Mage::log("came to helper",null,'version.log');
        //Mage::log($response,null,'version.log');

        $code = curl_getinfo($handle);
        /*get versions from microbiz code ends here*/

        if ($code['http_code'] == 200) {
            $response['status'] = 'SUCCESS';

        } else {
            $response['status'] = 'FAIL';
            $response['status_msg'] = 'Unable to get MicroBiz Version Details due to Http Error ' . $code['http_code'];
        }

        return $response;
    }

    public function mbizStartReSync($objId, $updateFrom, $objModel)
    {

        Mage::log("came to mbizStartReSync", null, 'version.log');
        Mage::log("objId" . $objId . "==updateFrom" . $updateFrom . "&&objModel=" . $objModel, null, 'version.log');
        $apiInformation = Mage::helper('microbiz_connector')->getApiDetails();
        $url = $apiInformation['api_server'];
        $queryString = '?objId=' . $objId . '&objModel=' . $objModel . '&updateFrom=' . $updateFrom;

        $url = $url . '/index.php/api/updateObjectData' . $queryString; // prepare url for the rest call
        //$url = 'http://ktc1.ktree.org/index.php/connector/index/getMbizVersionNumbers'.$queryString;
        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];

        //Mage::log($url,null,'rel.log');

        $method = 'GET';
        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: ' . $api_user,
            'X-MBIZPOS-PASSWORD: ' . $api_key
        );

        $handle = curl_init(); //curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($handle); // send curl request to microbiz

        $response = json_decode($response, true);

        $code = curl_getinfo($handle);
        Mage::log("came to response", null, 'version.log');
        Mage::log($response, null, 'version.log');
        Mage::log($code, null, 'version.log');
        if ($code['http_code'] == 200) {
            $response['status'] = 'SUCCESS';
            $response['status_msg'] = $objModel . ' Sync is Started in Background';

        } else {
            $response['status'] = 'FAIL';
            $response['status_msg'] = 'Unable to get MicroBiz Version Details due to Http Error ' . $code['http_code'];
        }

        return $response;

    }

    /**
     * @param $attributeSetId
     * @param $mbizAttrSetId
     * @return mixed
     */
    public function updateAttributeSetRelations($attributeSetId, $mbizAttrSetId)
    {
        $postAttributeSetData = array();
        $postAttributeSetData['magento_id'] = $attributeSetId;
        $postAttributeSetData['mbiz_id'] = $mbizAttrSetId;
        $postAttributeSetData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();

        $attrRelData = Mage::helper('microbiz_connector')->checkIsObjectExists($attributeSetId, 'AttributeSets');

        if (!empty($attrRelData)) {
            $postAttributeSetData['mage_version_number'] = $attrRelData['mage_version_number'];
        }

        //get AttributesVersions Info

        $attrData = Mage::getModel('mbizattribute/mbizattribute')->getCollection()
            ->addFieldToFilter('mbiz_attr_set_id', $mbizAttrSetId)->setOrder('id', 'asc')->getData();

        $attributesInfo = array();
        $attributeOptions = array();
        if (!empty($attrData)) {

            foreach ($attrData as $attribute) {
                $attributesInfo[$attribute['mbiz_id']]['mbiz_id'] = $attribute['mbiz_id'];
                $attributesInfo[$attribute['mbiz_id']]['magento_id'] = $attribute['magento_id'];
                $attributesInfo[$attribute['mbiz_id']]['mage_version_number'] = $attribute['mage_version_number'];
                $attributesInfo[$attribute['mbiz_id']]['mbiz_version_number'] = $attribute['mbiz_version_number'];

                if ($attribute['mbiz_id']) {
                    $attrOptData = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()
                        ->addFieldToFilter('mbiz_attr_id', $attribute['mbiz_id'])->setOrder('id', 'asc')->getData();
                    if (!empty($attrOptData)) {
                        foreach ($attrOptData as $attributeOption) {
                            $attributeOptions[$attributeOption['mbiz_id']]['mbiz_id'] = $attributeOption['mbiz_id'];
                            $attributeOptions[$attributeOption['mbiz_id']]['magento_id'] = $attributeOption['magento_id'];
                            $attributeOptions[$attributeOption['mbiz_id']]['mage_version_number'] = $attributeOption['mage_version_number'];
                            $attributeOptions[$attributeOption['mbiz_id']]['mbiz_version_number'] = $attributeOption['mbiz_version_number'];
                        }
                    }
                }
            }
        }
        $postAttributeSetData['attributes'] = $attributesInfo;
        $postAttributeSetData['attribute_options'] = $attributeOptions;


        //sending curl request to microbiz to update magento versions .

        $apiInformation = Mage::helper('microbiz_connector')->getApiDetails();
        $url = $apiInformation['api_server'];


        $url = $url . '/index.php/api/updateAttributeSetRelations'; // prepare url for the rest call

        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];

        //Mage::log($url,null,'rel.log');

        $method = 'POST';
        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: ' . $api_user,
            'X-MBIZPOS-PASSWORD: ' . $api_key
        );
        $postData = json_encode($postAttributeSetData);
        $handle = curl_init(); //curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $postData);

        $response = curl_exec($handle); // send curl request to microbiz

        $response = json_decode($response, true);

        $code = curl_getinfo($handle);

        if ($code['http_code'] == 200) {
            $response['status'] = 'SUCCESS';
        } else {
            $response['status'] = 'FAIL';
            $response['status_msg'] = 'Unable to update MicroBiz Version Number in MicroBiz due to Http Error ' . $code['http_code'];
        }

        return $response;

    }


    public function updateAttributeRelations($attributeId, $mbizAttrId)
    {
        $postAttributeData = array();
        $postAttributeData['magento_id'] = $attributeId;
        $postAttributeData['mbiz_id'] = $mbizAttrId;
        $postAttributeData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();

        $attrRelData = Mage::helper('microbiz_connector')->checkIsObjectExists($attributeId, 'Attributes');

        if (!empty($attrRelData)) {
            $postAttributeData['mage_version_number'] = $attrRelData['mage_version_number'];
            $postAttributeData['mbiz_version_number'] = $attrRelData['mbiz_version_number'];
        } else {
            $postAttributeData['mage_version_number'] = '';
            $postAttributeData['mbiz_version_number'] = '';
        }

        //get AttributesVersions Info

        $attrOptData = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()
            ->addFieldToFilter('mbiz_attr_id', $mbizAttrId)->setOrder('id', 'asc')->getData();


        $attributeOptions = array();

        if (!empty($attrOptData)) {
            foreach ($attrOptData as $attributeOption) {
                $attributeOptions[$attributeOption['mbiz_id']]['mbiz_id'] = $attributeOption['mbiz_id'];
                $attributeOptions[$attributeOption['mbiz_id']]['magento_id'] = $attributeOption['magento_id'];
                $attributeOptions[$attributeOption['mbiz_id']]['mage_version_number'] = $attributeOption['mage_version_number'];
                $attributeOptions[$attributeOption['mbiz_id']]['mbiz_version_number'] = $attributeOption['mbiz_version_number'];
            }
        }


        $postAttributeData['attribute_options'] = $attributeOptions;


        //sending curl request to microbiz to update magento versions .

        $apiInformation = Mage::helper('microbiz_connector')->getApiDetails();
        $url = $apiInformation['api_server'];


        $url = $url . '/index.php/api/updateAttributeRelations'; // prepare url for the rest call

        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];

        //Mage::log($url,null,'rel.log');

        $method = 'POST';
        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: ' . $api_user,
            'X-MBIZPOS-PASSWORD: ' . $api_key
        );
        $postData = json_encode($postAttributeData);
        $handle = curl_init(); //curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $postData);

        $response = curl_exec($handle); // send curl request to microbiz

        $response = json_decode($response, true);

        $code = curl_getinfo($handle);

        if ($code['http_code'] == 200) {
            $response['status'] = 'SUCCESS';
        } else {
            $response['status'] = 'FAIL';
            $response['status_msg'] = 'Unable to update MicroBiz Version Number in MicroBiz due to Http Error ' . $code['http_code'];
        }

        return $response;

    }

    public function RemoveCaching()
    {
        try {
            $allTypes = Mage::app()->useCache();
            foreach ($allTypes as $type => $blah) {
                //Mage::log($blah);
                Mage::app()->getCacheInstance()->cleanType($type);
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage());
        }
    }

    /**
     * @param $objId
     * @param $productData
     * @param bool $isMassUpdate
     * @author KT174
     * @description This method id used to check the product has relation or not if not create relation, if exists
     * update mageversionnumbe with one increment.
     */
    public function saveProductVerRel($objId, $productData, $isMassUpdate = false, $user = null)
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

        $prdId = $objId;
        $objectType = 'Product';

        Mage::log("came to Product controller", null, 'relations.log');
        Mage::log($prdId, null, 'relations.log');

        $prdRel = Mage::helper('microbiz_connector')->checkIsObjectExists($prdId, $objectType);

        if (!empty($prdRel)) {
            Mage::log("rel exists", null, 'assoc.log');
            Mage::log($prdRel, null, 'assoc.log');
            $Id = $prdRel['id'];
            $magVerNo = $prdRel['mage_version_number'];
            $mbizVerNo = $prdRel['mbiz_version_number'];

            $saveVersion = Mage::helper('microbiz_connector')->saveMageVersions($objectType, $Id, $magVerNo, $mbizVerNo);

        } else {

            $instanceId = Mage::helper('microbiz_connector')->getAppInstanceId();
            $prdRelModel = Mage::getModel('mbizproduct/mbizproduct');

            $prdRelModel->setInstanceId($instanceId)
                ->setMagentoId($prdId)->setMageVersionNumber('100')->setMbizVersionNumber(0)->setLastUpdatedFrom('MAG')
                ->setModifiedBy($user)->setModifiedAt(Now())->save();


        }

        if (!$isMassUpdate && !empty($productData)) {
            if ($productData['type_id'] == 'configurable') {
                $childIds = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($productData['entity_id']);

                Mage::log("child ids after", null, 'assoc.log');
                Mage::log($childIds, null, 'assoc.log');
                Mage::log($productData['associate_child_products'], null, 'assoc.log');

                $currentChilds = $childIds[0];

                $beforeChildIds = $productData['associate_child_products'];

                if (count($currentChilds) > count($beforeChildIds)) {
                    $difference = array_diff($currentChilds, $beforeChildIds);

                    $count = count($difference);

                    if ($count > 0) {
                        $prdRel = Mage::helper('microbiz_connector')->checkIsObjectExists($prdId, $objectType);

                        if (!empty($prdRel)) {
                            $Id = $prdRel['id'];

                            $prdRelModel = Mage::getModel('mbizproduct/mbizproduct')->load($Id);
                            $prdRelModel->setMageVersionNumber($prdRel['mage_version_number'] + $count)->setId($Id)->save();
                        }
                    }


                }


            }
        }

    }

    public function getIncExlPrice($productId = null, $price = null)
    {
        $priceData = array();

        if ($price && $productId) {

            $product = Mage::getSingleton('catalog/product')->load($productId);

            //calculating Excluding Tax Price Value


            //calculating Including Tax Price Value
            $productTaxClassId = $product->getTaxClassId();
            $tax_helper = Mage::getSingleton('tax/calculation');
            $tax_request = $tax_helper->getRateOriginRequest();
            $tax_request->setProductClassId($productTaxClassId);
            $tax = $tax_helper->getRate($tax_request);
            $calculator = Mage::getSingleton('tax/calculation');
            $tax_amount = $calculator->calcTaxAmount($price, $tax, false, true);


            //$price_including_tax = Mage::helper('tax')->getPrice($product, $price, true);

            if (Mage::getStoreConfig('tax/calculation/price_includes_tax')) {
                $price_including_tax = $price;
                $price_excluding_tax = Mage::helper('tax')->getPrice($product, $price, false);
            } else {
                $price_excluding_tax = $price;
                $price_including_tax = $price + $tax_amount;

            }


            $priceData['including_tax'] = $price_including_tax;
            $priceData['excluding_tax'] = $price_excluding_tax;


        }

        return $priceData;
    }

    public function saveTaxSettingToSync($priceIncludesTax = 0)
    {
        $overallsyncStatus = Mage::getStoreConfig('connector/settings/syncstatus');
        if ($overallsyncStatus) {
            $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                ->addFieldToFilter('obj_id', 1)
                ->addFieldToFilter('model_name', 'UpdateMagentoTaxSettings')
                ->addFieldToFilter('status', array('in' => array('Pending', 'Failed')))
                ->setOrder('header_id', 'desc')->getData();

            $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
            $user = 'admin';


            if (!empty($isObjectExists)) {
                $headerId = $isObjectExists[0]['header_id'];
                Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($headerId)->setStatus('Pending')->setId($headerId)->save();

                $removeItemifExists = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $headerId)->addFieldToFilter('attribute_name', 'price_includes_tax')->getFirstItem();

                if ($removeItemifExists->getId()) {
                    $ItemId = $removeItemifExists->getId();
                    Mage::getModel('syncitems/syncitems')->load($ItemId)->setAttributeValue($priceIncludesTax)->setId($ItemId)->save();
                } else {
                    $ItemData = array();
                    $ItemData['header_id'] = $headerId;
                    $ItemData['attribute_id'] = '0';
                    $ItemData['attribute_name'] = 'price_includes_tax';
                    $ItemData['attribute_value'] = $priceIncludesTax;
                    $ItemData['created_by'] = $user;
                    $ItemData['created_time'] = $date;

                    Mage::getModel('syncitems/syncitems')
                        ->setData($ItemData)
                        ->save();
                }
            } else {
                $TaxHeaderData = array();
                $TaxHeaderData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
                $TaxHeaderData['model_name'] = 'UpdateMagentoTaxSettings';
                $TaxHeaderData['obj_id'] = 1;
                $TaxHeaderData['obj_status'] = 1;
                $TaxHeaderData['ref_obj_id'] = '';
                $TaxHeaderData['mbiz_obj_id'] = '';
                $TaxHeaderData['status'] = 'Pending';
                $TaxHeaderData['created_by'] = $user;
                $TaxHeaderData['created_time'] = $date;

                $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                    ->setData($TaxHeaderData)
                    ->save();
                $headerId = $model['header_id'];


                $ItemData = array();
                $ItemData['header_id'] = $headerId;
                $ItemData['attribute_id'] = '0';
                $ItemData['attribute_name'] = 'price_includes_tax';
                $ItemData['attribute_value'] = $priceIncludesTax;
                $ItemData['created_by'] = $user;
                $ItemData['created_time'] = $date;

                Mage::getModel('syncitems/syncitems')
                    ->setData($ItemData)
                    ->save();

            }
        }

    }

    public function deleteAttributeMappings($id) {
        $apiInformation = Mage::helper('microbiz_connector')->getApiDetails();
        $url = $apiInformation['api_server'];


        $url = $url . '/index.php/api/deleteAttributeRelations'; // prepare url for the rest call

        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];

        //Mage::log($url,null,'rel.log');

        $method = 'POST';
        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: ' . $api_user,
            'X-MBIZPOS-PASSWORD: ' . $api_key
        );
        $postData = json_encode(array('magento_attribute_id'=>$id));
        $handle = curl_init(); //curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $postData);

        $response = curl_exec($handle); // send curl request to microbiz

        $response = json_decode($response, true);

        $code = curl_getinfo($handle);

        if ($code['http_code'] == 200) {
            $response['status'] = 'SUCCESS';
        } else {
            $response['status'] = 'FAIL';
            $response['status_msg'] = 'Unable to update MicroBiz Version Number in MicroBiz due to Http Error ' . $code['http_code'];
        }
        Mage::log($response,null,'attributeMappings.log');
        return $response;
    }
    /**
     * @author KT174
     * @description This method is used to get the Initial Sync Details from MicroBiz.
     * @params mbizSitename,mbizApiUserName, mbizApiPassword
     * @return an json Object with the Initial Sync Details.
     */
    public function mbizGetInitialSyncData($sitename,$apiUsername,$apiPassword)
    {
        Mage::log("came to mbiz get ini",null,'settings.log');
        $apiserver = $sitename;
        //$apiserver = $sitename.'.microbiz.com';
        //$apiserver = 'http://pos.ktree.org/branches/initialsync';
        $url    = $apiserver.'/index.php/api/mbizInitialSyncDetails';			// prepare url for the rest call
        $method = 'GET';
        $apipath = $apiUsername;
        $apipassword = $apiPassword;

        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: '.$apipath,
            'X-MBIZPOS-PASSWORD: '.$apipassword
        );
        $data = array();
        $data['instance_id']=1;
        $data['syncstatus']=1;
        $handle = curl_init();		//curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);

        switch ($method) {
            case 'GET':
                break;

            case 'POST':
                curl_setopt($handle, CURLOPT_POST, true);
                //curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'PUT':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');	// create product request.
                //curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($handle);	// send curl request to microbiz
        $code = curl_getinfo($handle);
        Mage::log($code,null,'settings.log');
        return $response;
    }

    /**
     * @author KT174
     * @description This method is used to send the Initial Sync Settings Details to MicroBiz.
     * @params settings array
     * @return an http code.
     */
    public function mbizSendSettings($sitename,$apiUsername,$apiPassword,$mbizSaveSettings)
    {
        Mage::log("came to mbizsendsettings",null,'linking.log');
        Mage::log($mbizSaveSettings,null,'linking.log');
        Mage::log(json_encode($mbizSaveSettings),null,'linking.log');
        $siteType = Mage::getStoreConfig('connector/installation/mbiz_sitetype');
        if($siteType==1)
        {
            $apiserver = 'https://'.$sitename.'.microbiz.com';
        }
        else {
            $apiserver = $sitename;
        }
        //$apiserver = $sitename.'.microbiz.com';
        //$apiserver = 'http://pos.ktree.org/branches/initialsync';
        $url    = $apiserver.'/index.php/api/mbizInitialSyncDetails';			// prepare url for the rest call
        $method = 'POST';
        $apipath = $apiUsername;
        $apipassword = $apiPassword;

        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: '.$apipath,
            'X-MBIZPOS-PASSWORD: '.$apipassword
        );
        $data = array();
        $data = json_encode($mbizSaveSettings);
        $handle = curl_init();		//curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);

        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);


        $response = curl_exec($handle);	// send curl request to microbiz
        $code = curl_getinfo($handle);
        Mage::log($response,null,'linking.log');
        Mage::log($code,null,'linking.log');

        $response = json_decode($response,true);
        /*Code to add the Store Include Inventory when the Settings has been saved both magetombiz and mbiztomage starts here.*/
        if($code['http_code']==200 && !empty($response)) {

            $instanceId = $response['magento_instance_id'];
            $storesInfo = $response['store_info'];

            $includeInventoryTo = $storesInfo['include_inventory'];
            $storesInformation = $storesInfo['stores'];

            if(!empty($storesInformation)) {
                foreach($storesInformation as $store) {
                    $store['instance_id'] = $instanceId;

                    try {
                        $storemodel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->getCollection()->addFieldToFilter('company_id', $store['company_id'])->addFieldToFilter('store_id', $store['store_id'])->getFirstItem()->getData();
                        if (!count($storemodel)) {
                            $storesModel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->setData($store)->save();
                            $id = $storesModel->getId();
                        } else {
                            $id = $storemodel['id'];
                            Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->load($id)->setData($store)->setId($id)->save();
                        }
                    } catch (Exception $e) {
                        $exceptions[] = $e->getMessage();
                    }
                }
            }

            if(!empty($includeInventoryTo)) {
                foreach($includeInventoryTo as $store=>$value) {
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
            }

        }
        /*Code to add the Store Include Inventory when the Settings has been saved both magetombiz and mbiztomage ends here.*/
        Mage::log("end of mbizsendsettings helper",null,'linking.log');
        return $code;
    }

    /**
     * @author KT174
     * @description This method is used to sync the sync records to Header Table while the Initial Sync.
     * @params $obj_id,$model_name
     */
    public function mbizInitialSyncHeaderDetails($objId,$modelName,$refObjId=null)
    {
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
        $attributeSetData['model_name']=$modelName;
        $attributeSetData['instance_id']=Mage::helper('microbiz_connector')->getAppInstanceId();
        $attributeSetData['obj_id']=$objId;
        $attributeSetData['ref_obj_id']=$refObjId;
        $attributeSetData['created_by']=$user;
        $attributeSetData['created_time']= $date;
        $attributeSetData['is_initial_sync']= '1';
        $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
            ->setData($attributeSetData)
            ->save();
        $headerId=$model['header_id'];

        //if the model is customer sync all the addresses of that customer also
        if($modelName=='Customer') {
            $model = 'CustomerAddressMaster';
            $customer = Mage::getModel('customer/customer')->load($objId);

            foreach($customer->getAddresses() as $Address)
            {
                $isCustAddressObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                    ->addFieldToFilter('obj_id', $Address->getId())
                    ->addFieldToFilter('model_name', 'CustomerAddressMaster')
                    ->addFieldToFilter('status', 'Pending')
                    ->addFieldToFilter('is_initial_sync', '1')
                    ->setOrder('header_id','desc')->getData();

                if(!$isCustAddressObjectExists)
                {
                    $HeaderData['model_name']=$model;
                    $HeaderData['instance_id']=Mage::helper('microbiz_connector')->getAppInstanceId();
                    $HeaderData['obj_id']=$Address->getId();
                    $HeaderData['ref_obj_id']=$objId;
                    $HeaderData['created_by']=$user;
                    $HeaderData['created_time']= $date;
                    $HeaderData['is_initial_sync']= '1';
                    Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                        ->setData($HeaderData)
                        ->save();
                }
            }
        }

        return $headerId;
    }
    public function mbizSaveInventoryCategory($magRootCate = 2)
    {
        /*Saving the Inventory Root Category Relation in Magento Starts Here.*/
        $categoryModel = Mage::getModel('mbizcategory/mbizcategory');
        $isRelationExists = $categoryModel->getCollection()->addFieldToFilter('magento_id',2)->getData();

        if(!empty($isRelationExists)) {
            $categoryModel->load($isRelationExists[0]['magento_id']);

            $categoryModel->setMagentoId($magRootCate)->setMbizId(2)->setId($isRelationExists[0]['magento_id'])->save();
        }
        else {
            $categoryInfo = array();
            $categoryInfo['is_inventory_category'] = 1;
            $categoryInfo['magento_id'] = $magRootCate;
            $categoryInfo['mbiz_id'] = 2;

            $categoryModel->setData($categoryInfo)->save();
        }

        /*Saving the Inventory Root Category Relation in Magento Ends Here.*/
    }


}