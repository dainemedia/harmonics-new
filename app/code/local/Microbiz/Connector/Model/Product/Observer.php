<?php
/**
 * Product_Observer.php
 *
 */
//Version 130
class Microbiz_Connector_Model_Product_Observer
{
    const MAX_QTY_VALUE = 99999999.9999;

    public function __construct()
    {
    }

    public function onBeforeSave($observer)
    {
        $adminSession = Mage::getSingleton('admin/session', array(
            'name' => 'adminhtml'
        ));

        if ($adminSession->isLoggedIn()) {
            /**
             * Product before save
             *
             * @param array $productData
             */
            $object = $observer->getEvent()->getProduct();

            /*Setting the Child Products ids for configurable product starts here*/
            Mage::log("came to on before save", null, 'assoc.log');

            Mage::log($object->getId(), null, 'assoc.log');

            if ($object->getId()) {
                $typeId = $object->getTypeId();

                if ($typeId == 'configurable') {
                    $childIds = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($object->getId());

                    Mage::log("child ids before", null, 'assoc.log');
                    Mage::log($childIds, null, 'assoc.log');
                    $object->setAssociateChildProducts($childIds[0]);
                }
            }

            /*Setting the Child Products ids for configurable product ends here*/

            if (!is_array(Mage::app()->getRequest()->getParam('product')) && is_numeric(Mage::app()->getRequest()->getParam('product'))) {
                $object = $observer->getEvent()->getProduct();

                $price = $object->getData('price');
                $configurableProduct = Mage::getModel('catalog/product')->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)->load(Mage::app()->getRequest()->getParam('product'));

                if ($configurableProduct->isConfigurable()) {
                    $object->setData('store_price', $price);
                }
            }
            else {
                $price = $object->getData('price');
                $storePrice = $object->getData('store_price');
                (empty($storePrice)) ?  $object->setData('store_price', $price) : null;
            }

            $user = Mage::getSingleton('admin/session')->getUser()->getUsername(); // get current user first name
            $date = date("d/m/Y", Mage::getModel('core/date')->timestamp(time())); // current time stamp
            $attributeSetId = $object->getData('attribute_set_id');
            $syncStatus = $object->getData('sync_status');
            $checkAttributeSetRelation = Mage::helper('microbiz_connector')->checkObjectRelation($attributeSetId, 'AttributeSets');

            if (!$checkAttributeSetRelation && $syncStatus) {
                $attributeSetInfo = Mage::helper('microbiz_connector')->saveAttributeSetSyncInfo($attributeSetId);
            }

            if ($object->getId()) { //on product create
                $object->setIsNewlyCreated(false);
                $object->setSyncUpdateMsg("Modified in Magento by {$user} on {$date}"); // set update message for the Product
            } else { //on product update
                $object->setIsNewlyCreated(true);
                $object->setSyncUpdateMsg("Created in Magento by {$user} on {$date}");
            }
        }
    }

    public function onAfterDelete($observer)
    {
        $object = $observer->getEvent()->getProduct();
        /*$product = $object->getData();*/

        $productid = $object->getId();

        $apiInformation = Mage::helper('microbiz_connector')->getApiDetails();
        $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];
        $display_name = $apiInformation['display_name'];

        $url = $url . '/index.php/api/product/' . $productid;
        $method = 'DELETE';

        // headers and data (this is API dependent, some uses XML)

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: ' . $api_user,
            'X-MBIZPOS-PASSWORD: ' . $api_key
        );
        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        $data = array();
        switch ($method) {
            case 'GET':
                break;

            case 'POST':
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'PUT':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($handle); // send curl request to microbiz
        $code = curl_getinfo($handle);

        if ($code['http_code'] == 200) {
            Mage::getSingleton('core/session')->addSuccess($display_name . ': ' . $response);
        } else if ($code['http_code'] == 100) {
            return $this;
        } else {
            Mage::getSingleton('core/session')->addError($display_name . ': ' . $response);
            return $this;
        }
    }

    /**
     * @param $observer
     */
    public function onAttributeSetDelete($observer)
    {
        $attributesetid = $observer->getEvent()->getSetId();
        $relationattributesetdata = $relationdata = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->addFieldToFilter('magento_id', $attributesetid)->setOrder('id', 'asc')->getData();
        if ($relationattributesetdata) {
            if (Mage::getSingleton('admin/session')->isLoggedIn()) {

                $user = Mage::getSingleton('admin/session');
                $user = $user->getUser()->getFirstname();

            } else if (Mage::getSingleton('api/session')) {
                $userApi = Mage::getSingleton('api/session');
                $user = $userApi->getUser()->getUsername();

            } else {
                $user = 'Guest';
            }
            $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
            $attributeSetData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $attributeSetData['model_name'] = 'AttributeSets';
            $attributeSetData['obj_id'] = $attributesetid;
            $attributeSetData['obj_status'] = 2;
            $attributeSetData['mbiz_obj_id'] = $relationattributesetdata[0]['mbiz_id'];
            $attributeSetData['created_by'] = $user;
            $attributeSetData['created_time'] = $date;
            Mage::getModel('extendedmbizconnector/extendedmbizconnector')->setData($attributeSetData)->save();
        }


    }

    public function onAttributeGroupDelete()
    {
        //Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Product Attribute Group Deleted Successfully (Product Attribute Group Delete event observer)'));
    }


    public function onAfterSave($observer)
    {
        /**
         * Prodct after save
         *
         * @param array $productData
         */

        $object = $observer->getEvent()->getProduct();
        $user = Mage::getSingleton('admin/session')->getUser();

        //  generate events for product create/update.
        if ($user) {
            if ($object->getIsNewlyCreated()) {
                Mage::dispatchEvent('on_product_create', array(
                    'object' => $object
                ));
            } else {
                Mage::dispatchEvent('on_product_update', array(
                    'object' => $object
                ));
            }
        }
    }

    public function onCreate($object)
    {

        /**
         * Product create event listener.
         * create product from magento to microbiz.
         */

        $product = $object->getData(); //get product information

        $productSync = $product['object']->getSyncPrdCreate(); //get product sync status
        $apiInformation = Mage::helper('microbiz_connector')->getApiDetails();
        $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];
        $display_name = $apiInformation['display_name'];
        if ($productSync) {
            $url = $url . '/index.php/api/product'; // prepare url for the rest call
            $method = 'POST';

            // headers and data (this is API dependent, some uses XML)
            $headers = array(
                'Accept: application/json',
                'Content-Type: application/json',
                'X-MBIZPOS-USERNAME: ' . $api_user,
                'X-MBIZPOS-PASSWORD: ' . $api_key
            );

            $result = $product['object']->getData();
            $data = json_encode($result);


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


            // Mage::getSingleton('core/session')->addError($code . "  : " . $response);

            $code = curl_getinfo($handle);

            // if($code['http_code'] == 500 ) Mage::getSingleton('core/session')->addError($response);	// display error msg if response got errors
            if ($code['http_code'] == 200) {
                Mage::getSingleton('core/session')->addSuccess($display_name . ': ' . $response);
            } else {
                Mage::getSingleton('core/session')->addError($display_name . ': ' . $response);
            }
        }
        return $this;
    }

    public function onUpdate($object)
    {

        /**
         * product update event listener.
         * create product from magento to microbiz.
         */

        $product = $object->getData();

        $SyncStatus = $product['object']->getSyncStatus(); //get product sync status
        $Synproduct = $product['object']->getSyncPrdCreate();
        $apiInformation = Mage::helper('microbiz_connector')->getApiDetails();
        $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];
        $display_name = $apiInformation['display_name'];
        if ($Synproduct && $SyncStatus) {
            $url = $url . '/index.php/api/product';
            $method = 'POST';
            // headers and data (this is API dependent, some uses XML)
            $headers = array(
                'Accept: application/json',
                'Content-Type: application/json',
                'X-MBIZPOS-USERNAME: ' . $api_user,
                'X-MBIZPOS-PASSWORD: ' . $api_key
            );


            $result = $product['object']->getData();

            $data = json_encode($result);


            $handle = curl_init();
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
                    curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                    break;

                case 'DELETE':
                    curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    break;
            }

            $response = curl_exec($handle);

            $code = curl_getinfo($handle);


            if ($code['http_code'] == 200) {
                Mage::getSingleton('core/session')->addSuccess($display_name . ': ' . $response);
            } else {
                Mage::getSingleton('core/session')->addError($display_name . ': ' . $response);
            }
        }
        return $this;
    }

    /**
     * @param $observer --contains newly created product information.
     * @Description: This method is used to sync product information into sync tables when any product is created using
     * API or Admin
     * @author: KT174
     */

    public function mbizOnProductSave($observer)
    {
        $adminSession = Mage::getSingleton('admin/session', array(
            'name' => 'adminhtml'
        ));

        $setApiSession = $observer->getEvent()->getIsApiSession();

        $updateFullInfo = Mage::registry('update_full_product_info');

        try {
            if ($adminSession->isLoggedIn() || $setApiSession || $updateFullInfo) {

                if (Mage::getSingleton('admin/session')->isLoggedIn()) {
                    $user = Mage::getSingleton('admin/session');
                    $user = $user->getUser()->getFirstname();

                } else if (Mage::getSingleton('api/session')) {
                    $userApi = Mage::getSingleton('api/session');
                    $user = $userApi->getUser()->getUsername();
                } else {
                    $user = 'Guest';
                }

                $product = $observer->getEvent()->getProduct();

                $syncStatus = $product->getSyncStatus();

                if ($syncStatus == 0)
                    return;

                /*Converting the object inside the array to array by json encoding and decoding*/

                $origData = json_decode(json_encode($product->getOrigData()), true);
                $prodData = json_decode(json_encode($product->getData()), true);

                $is_newly_created = $product->getIsNewlyCreated();

                //$prodCollection = Mage::getModel('catalog/product')->load($product->getId());

                $productPostData = Mage::app()->getRequest()->getPost();
                if (isset($productPostData['configurable_products_data'])) {
                    $configurableSimpleProductsData = json_decode($productPostData['configurable_products_data'], true);
                }

                unset($prodData['_cache_editable_attributes']);

                $product_has_changes = $product->hasDataChanges();

                $_catCollection = $product->getCategoryCollection();

                $categoryIds = array();

                foreach ($_catCollection as $_category) {
                    $categoryIds[] = $_category->getId();
                }
                $simplePrdIds = array();
                if($product->getTypeId() == 'configurable') {
                    $simpleProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null, $product);

                    foreach ($simpleProducts as $childProduct) {
                        $simplePrdInfo = $childProduct->getData();
                        $simplePrdIds[] = $simplePrdInfo['entity_id'];
                    }
                }

                //$productBySku = Mage::getModel('catalog/product')->loadByAttribute('sku', $product->getSku());
                $url = Mage::getUrl("connector/sync_product/saveSyncRecords/", array("id" => $product->getId()));

                $updatePrdData = array('data' => $product->toArray(),'new_attributes' => $prodData, 'old_attributes' => $origData, 'product_has_changes' => $product_has_changes, 'categoryIds' => $categoryIds, 'user' => $user, 'sync_status' => $syncStatus, 'is_newly_created' => $is_newly_created,'configurable_products_data'=>$configurableSimpleProductsData,'simple_product_ids'=>$simplePrdIds);
                Mage::helper('microbiz_connector')->createMbizSyncStatus('PrdUpdate'.$product->getId(), 'PRD_BG',json_encode($updatePrdData));
                Mage::helper('microbiz_connector/ERunActions')->touchUrl($url);

            } else {
                /* Code to Save Magento Version Numbers
                   into the Relation Tables Starts Here.
                */

                $product = $observer->getEvent()->getProduct();
                $prdId = $product->getId();
                $objectType = 'Product';

                Mage::log("came to Product save after event using Api", null, 'relations.log');
                Mage::log($prdId, null, 'relations.log');

                $prdRel = Mage::helper('microbiz_connector')->checkIsObjectExists($prdId, $objectType);

                if (!empty($prdRel)) {
                    Mage::log($prdRel, null, 'relations.log');

                    $Id = $prdRel['id'];
                    $magVerNo = $prdRel['mage_version_number'];
                    Mage::getModel('mbizproduct/mbizproduct')->load($Id)->setMageVersionNumber($magVerNo + 1)->setId($Id)->save();
                }
                /*Code to Save Magento Version Numbers into the Relation Tables Ends Here.*/
            }
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage() . '<br/>' . $e->getTraceAsString(), null, 'runcaction.log');
        }
    }

    /**
     * @param $observer
     * @author KT174
     * @description This function is used to check the layout block name and change the custom options block for
     * GiftCard Product
     */
    public function updateGiftOptions($observer)
    {
        Mage::log("came to update gift options method", null, 'links.log');
        $controller = $observer->getAction();
        if ($controller->getFullActionName() == 'catalog_product_view') {
            $currentProduct = Mage::registry('current_product');
            if (!empty($currentProduct)) {
                $currentProductSku = $currentProduct->getSku();
                $giftCardSku = Mage::getStoreConfig('connector/settings/giftcardsku');
                if ($currentProductSku == $giftCardSku) {

                    //$actonName = $controller->getFullActionName();
                    //Mage::log($controller,null,'giftcardissue.log');
                    //Mage::log($actonName,null,'giftcardissue.log');
                    if ($controller->getFullActionName() != 'catalog_product_view') {
                        return;
                    }

                    $layout = $controller->getLayout();
                    $product_info = $layout->getBlock('product.info.options');
                    $product_info->setTemplate('connector/options.phtml');

                    //Mage::log($currentProduct->getName(),null,'giftcardissue.log');
                    //Mage::log($currentProductSku,null,'giftcardissue.log');
                    //$controller->getParams();

                    //echo "<pre>";
                    //print_r($product_info);exit;

                } else {
                    return;
                }
            }
        } else {
            $controller = $observer->getAction();
            Mage::log("came before full action name", null, 'links.log');
            //Mage::log($controller,null,'links.log');
            Mage::log($controller->getFullActionName(), null, 'links.log');
            Mage::log("came after full action name", null, 'links.log');
            if ($controller->getFullActionName() == 'adminhtml_customer_edit' || $controller->getFullActionName() == 'adminhtml_catalog_product_edit' || $controller->getFullActionName() == 'adminhtml_sales_order_view') {
                $adminSession = Mage::getSingleton('admin/session', array(
                    'name' => 'adminhtml'
                ));

                if ($adminSession->isLoggedIn()) {
                    Mage::log("came inside loop", null, 'links.log');
                    Mage::helper('microbiz_connector')->RemoveCaching();
                    $defaultUrlValue = Mage::getStoreConfig('connector/default/use_form_key');
                    Mage::log($defaultUrlValue, null, 'customerurl.log');


                    //$configData = new Mage_Core_Model_Config();

                    //$configData->saveConfig('admin/security/use_form_key',1,'default',0);
                    Mage::helper('microbiz_connector')->RemoveCaching();
                    Mage::helper('microbiz_connector')->RemoveCaching();
                    $keyval = Mage::getStoreConfig('admin/security/use_form_key');
                    Mage::log("after update" . $keyval, null, 'links.log');
                }

            } else {
                return;
            }
        }


    }

    public function mbizOnProductDelete($observer)
    {
        $product = $observer->getEvent()->getProduct();
        $productId = $product->getId();
        $productSku = $product->getSku();
        $sync_status = $product->getSyncStatus();
        $deletedproduct = array(
            'sku' => $productSku,
            'id' => $productId
        );

        $overAllSyncStatus = Mage::getStoreConfig('connector/settings/syncstatus');
        $checkObjectRelation = Mage::helper('microbiz_connector')->checkObjectRelation($productId, 'Product');

        if ($overAllSyncStatus || $checkObjectRelation) {
            if (($sync_status) || $checkObjectRelation) {
                Mage::dispatchEvent('ktree_product_delete', array(
                    'sku' => $deletedproduct
                ));
            }
        }
        Mage::helper('microbiz_connector')->deleteAppRelation($productId, 'Product');

    }

    public function onCatalogRuleChange($observer)
    {
        Mage::log("came to observer onCatalogRuleChange", null, 'massupdate.log');
        $section = $observer->getEvent()->getSection();

        if ($section == 'tax') {
            Mage::log("before data", null, 'massupdate.log');
            $priceIncludesTax = Mage::getStoreConfig('tax/calculation/price_includes_tax');
            Mage::log("before value" . $priceIncludesTax, null, 'massupdate.log');

            $apiInformation = Mage::helper('microbiz_connector')->getApiDetails();
            $url = $apiInformation['api_server'];


            $url = $url . '/index.php/api/updateWebTaxPriceSetting'; // prepare url for the rest call

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
            $postAttributeData['price_includes_tax'] = $priceIncludesTax;
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
                Mage::getSingleton('core/session')->addSuccess('Catalog Prices Tax Include/Exclude Setting Saved in MicroBiz.');
            } else {
                Mage::getSingleton('core/session')->addNotice('Unable to Save Price Includes Taxes Setting in MicroBiz due to Http Error ' . $code['http_code']);

                /*If the Setting is not Saved on the Fly we are Syncing the Setting into the Sync.*/

                $overAllSyncStatus = Mage::getStoreConfig('connector/settings/syncstatus');
                if ($overAllSyncStatus) {
                    Mage::helper('microbiz_connector')->saveTaxSettingToSync($priceIncludesTax);
                }
            }

        }

    }
}