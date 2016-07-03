<?php
// Version 119
class Microbiz_Connector_Adminhtml_ConnectorController extends Mage_Adminhtml_Controller_Action
{
    protected $_publicActions = array('mbizgeteditlink','mbizinstallsignin','index');

    protected function _initAction()
    {
        Mage::helper('microbiz_connector')->RemoveCaching();
        $this->loadLayout()->_setActiveMenu('microbiz_connector/connector');
        if(Mage::getStoreConfig('connector/settings/installation'))
        {
            $this->_addContent($this->getLayout()->createBlock('adminhtml/template')->setTemplate('connector/configuration.phtml'));
        }else {
            //$this->_addContent($this->getLayout()->createBlock('adminhtml/template')->setTemplate('connector/configuration.phtml'));
            $this->_addContent($this->getLayout()->createBlock('adminhtml/template')->setTemplate('connector/initialstep.phtml'));
        }
        return $this;
    }


    public function indexAction() {
        $this->_initAction()->renderLayout();
    }
    public function saveConfigAction()
    {
        $postdata=$this->getRequest()->getPost();
        /*
        * Getting the post data into variables
        */
        $apiserver=$postdata['api_server'];
        $apipath=$postdata['api_path'];
        $apipassword=$postdata['api_password'];
        $mage_url=$postdata['mage_url'];
        $batchsize=$postdata['batchsize'];
        $connectordebug=$postdata['connectordebug'];
        $displayname=$postdata['display_name'];
        $defaultwebsite_customer=$postdata['defaultwebsite_customer'];
        $defaultwebsite_product=$postdata['defaultwebsite_product'];
        $customer_update=$postdata['customer_update'];
        $syncstatus=$postdata['syncstatus'];
        $allownegativeinv=$postdata['allownegativeinv'];
        $syncorders=$postdata['syncorders'];
        $customer_create=$postdata['customer_create'];
        $defaultattributeset=$postdata['defaultattributeset'];
        $allowstoreselection=$postdata['allowstoreselection'];
        $storeCredit = $postdata['storecredit'];
        $giftCard = $postdata['giftcard'];
        $storeCreditPayment = $postdata['storecreditpayment'];
        $giftCardPayment = $postdata['giftcardpayment'];
        $sellGiftCard = $postdata['sellgiftcard'];
        $giftCardHeader = $postdata['giftcardheader'];
        $giftCardText = $postdata['giftcardtext'];
        $syncGiftRanges = $postdata['syncgiftranges'];
        $showgGftCard = $postdata['showgiftcard'];
        $giftCardDisplayName = $postdata['giftcard_displayname'];
        //$manageStock = $postdata['configproduct_managestock'];
        $left=$right=$home=0;

        foreach($showgGftCard as $position)
        {
            if($position=='left') { $left=1; }
            if($position=='right') { $right=1; }
            if($position=='home') { $home=1; }
        }

        $syncGiftStatus = 0;

        $url    = $apiserver.'/index.php/api/test';			// prepare url for the rest call
        $method = 'POST';

        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: '.$apipath,
            'X-MBIZPOS-PASSWORD: '.$apipassword
        );

        $data=array('mage_url'=>$mage_url,'syncstatus'=>$syncstatus);
        $data    = json_encode($data);


        $handle = curl_init();		//curl request to create the product
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
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');	// create product request.
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($handle);	// send curl request to microbiz





        $code = curl_getinfo($handle);


        if($code['http_code'] == 200 ) {
            /*If Sell GiftCards are disabled then disable all the Products of GiftCard Product Type starts here*/
            if(!$sellGiftCard) {

                $GiftProductCollection = Mage::getModel('catalog/product')->getCollection()->addFieldToFilter('type_id','mbizgiftcard')->getData();


                if(!empty($GiftProductCollection)) {
                    foreach($GiftProductCollection as $giftProduct) {
                        $prdId =  $giftProduct['entity_id'];

                        $prdModel = Mage::getModel('catalog/product')->load($prdId);
                        $prdModel->setStatus(2)->save();
                    }
                }
            }
            else {
                $GiftProductCollection = Mage::getModel('catalog/product')->getCollection()->addFieldToFilter('type_id','mbizgiftcard')->getData();

                if(!empty($GiftProductCollection)) {
                    foreach($GiftProductCollection as $giftProduct) {
                        $prdId =  $giftProduct['entity_id'];

                        $prdModel = Mage::getModel('catalog/product')->load($prdId);
                        $prdModel->setStatus(1)->save();
                    }
                }
            }
            /*If Sell GiftCards are disabled then disable all the Products of GiftCard Product Type ends here*/

            /*IF GiftCard Custom Display Name is Provided then Updating the MBIZ GiftCard Product with this Custom Name*/
            $giftCardSku = Mage::getStoreConfig('connector/settings/giftcardsku');
            if($giftCardDisplayName && $giftCardSku) {
                $productId = Mage::getModel('catalog/product')->getIdBySku($giftCardSku);

                if($productId) {
                    $productModel = Mage::getModel('catalog/product')->load($productId);

                    $productModel->setName($giftCardDisplayName)->save();

                    $allStores = Mage::app()->getStores();

                    foreach($allStores as $eachstoreid => $store) {
                        $storeId  = Mage::app()->getStore($eachstoreid)->getId();
                        $productModel->setStoreId($storeId)->setName($giftCardDisplayName)->save();
                    }


                }

            }
            /*IF GiftCard Custom Display Name is Provided then Updating the MBIZ GiftCard Product with this Custom Name*/
            /*Sending Magento Version Number to MicroBiz Code Starts here*/

            $instanceId = 1;
            $version_url = $apiserver; // get microbiz server details fron configuration settings.
            $versionUrl = $version_url.'/index.php/api/updatePluginVersion'; // prepare url for the rest call
            $api_user = $apipath;
            $api_key = $apipassword;


            // headers and data (this is API dependent, some uses XML)
            $headers = array(
                'Accept: application/json',
                'Content-Type: application/json',
                'X-MBIZPOS-USERNAME: '.$api_user,
                'X-MBIZPOS-PASSWORD: '.$api_key
            );
            $versionData = array('mage_plugin_version' => (string) Mage::getConfig()->getNode()->modules->Microbiz_Connector->version);

            $versionhandle = curl_init();		//curl request to create the product
            curl_setopt($versionhandle, CURLOPT_URL, $versionUrl);
            curl_setopt($versionhandle, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($versionhandle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($versionhandle, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($versionhandle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($versionhandle, CURLOPT_POST, true);
            curl_setopt($versionhandle, CURLOPT_POSTFIELDS, json_encode($versionData));

            $versionresponse = curl_exec($versionhandle);	// send curl request to microbiz

            $versioncode = curl_getinfo($versionhandle);
            Mage::log($versionresponse,null,'vesionUpdate.log');
            Mage::log($versioncode,null,'vesionUpdate.log');
            curl_close($versionhandle);
            /*Sending Magento Version Number to MicroBiz Code Ends Here.*/

            /*Sending the Price Includes Tax Setting to MicroBiz starts here */
            $priceIncludesTax = Mage::getStoreConfig('tax/calculation/price_includes_tax');
            $postAttributeData=array('price_includes_tax'=>$priceIncludesTax);
            $taxUrl = $apiserver.'/index.php/api/updateWebTaxPriceSetting';
            $settingPostData = json_encode($postAttributeData);
            $SettingHandle = curl_init();		//curl request to create the product
            curl_setopt($SettingHandle, CURLOPT_URL, $taxUrl);
            curl_setopt($SettingHandle, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($SettingHandle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($SettingHandle, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($SettingHandle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($SettingHandle, CURLOPT_POST, true);
            curl_setopt($SettingHandle, CURLOPT_POSTFIELDS, $settingPostData);

            $SettingResponse = curl_exec($SettingHandle);	// send curl request to microbiz

            $SettingResponse=json_decode($SettingResponse,true);

            $SettingCode = curl_getinfo($SettingHandle);

            if($SettingCode['http_code']!=200) {
                Mage::getSingleton('core/session')->addNotice('Unable to Save Catalog Price Includes Tax Setting in MicroBiz due to Http Error '.$SettingCode['http_code']);

                /*If the Setting is not Saved on the Fly we are Syncing the Setting into the Sync.*/
                $overallsyncStatus = Mage::getStoreConfig('connector/settings/syncstatus');
                if($overallsyncStatus) {
                    Mage::helper('microbiz_connector')->saveTaxSettingToSync($priceIncludesTax);
                }
            }
            /*Sending the Price Includes Tax Setting to MicroBiz ends here */
            Mage::getSingleton('core/session')->addNotice('Connected to API Server');
            if($syncGiftRanges==1)
            {
                $ApiUrl = $apiserver.'/index.php/api/getGiftCardRanges';
                $data=array('mage_url'=>$mage_url,'syncstatus'=>$syncstatus);
                $data    = json_encode($data);

                $RangeHandler = curl_init();		//curl request to create the product
                curl_setopt($RangeHandler, CURLOPT_URL, $ApiUrl);
                curl_setopt($RangeHandler, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($RangeHandler, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($RangeHandler, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($RangeHandler, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($RangeHandler, CURLOPT_POST, true);
                curl_setopt($RangeHandler, CURLOPT_POSTFIELDS, $data);

                $giftRanges = curl_exec($RangeHandler);
                $HttpCode = curl_getinfo($RangeHandler);
                if($HttpCode['http_code']=='200')
                {
                    $RangeCount = count(json_decode($giftRanges,true));
                    if($RangeCount>0)
                    {
                        Mage::getModel('Microbiz_Connector_Model_Api')->mbizSaveGiftCardRanges($giftRanges,1);
                    }
                    $syncGiftStatus = 1;

                }


            }
        } else {
            Mage::getSingleton('core/session')->addError('Not Connected to API Server.');
            if($code['http_code'] == 500) {
                Mage::getSingleton('core/session')->addError($code['http_code'].' - Internal Server Error'.$response['message']);
            }
            else if($code['http_code'] == 0) {
                Mage::getSingleton('core/session')->addError($code['http_code'].' - Please Check the API Server URL'.$response['message']);
            }
            else
            {
                Mage::getSingleton('core/session')->addError($code['http_code'].'  - '.$response['message']);

            }
            Mage::getSingleton('adminhtml/session')->setData($this->getRequest()->getPost());
            $this->_redirect('*/*/');
            return;
        }
        try {
            $configdata = new Mage_Core_Model_Config();
            /*
            * Saving the data into core_config_data table
            */
            $response=json_decode($response,true);

            $instance_id=$response['instance_id'];
            $configdata ->saveConfig('connector/settings/api_server', $apiserver, 'default', 0);
            $configdata ->saveConfig('connector/settings/instance_id', $instance_id, 'default', 0);
            $configdata ->saveConfig('connector/settings/syncstatus', $syncstatus, 'default', 0);
            $configdata ->saveConfig('connector/settings/allownegativeinv', $allownegativeinv, 'default', 0);
            $configdata ->saveConfig('connector/settings/defaultattributeset', $defaultattributeset, 'default', 0);
            $configdata ->saveConfig('connector/settings/api_user', $apipath, 'default', 0);
            $configdata ->saveConfig('connector/settings/api_key', $apipassword, 'default', 0);
            $configdata ->saveConfig('connector/settings/display_name', $displayname, 'default', 0);
            $configdata ->saveConfig('connector/frontendsettings/customer_update', $customer_update, 'default', 0);
            $configdata ->saveConfig('connector/frontendsettings/customer_create', $customer_create, 'default', 0);
            $configdata ->saveConfig('connector/batchsizesettings/batchsize', $batchsize, 'default', 0);
            $configdata ->saveConfig('connector/settings/connectordebug', $connectordebug, 'default', 0);
            $configdata ->saveConfig('connector/defaultwebsite/customer', $defaultwebsite_customer, 'default', 0);
            $configdata ->saveConfig('connector/defaultwebsite/product', $defaultwebsite_product, 'default', 0);
            $configdata ->saveConfig('connector/settings/allowstoreselection', $allowstoreselection, 'default', 0);
            $configdata ->saveConfig('connector/settings/syncorders', $syncorders, 'default', 0);
            $configdata ->saveConfig('connector/settings/storecredit', $storeCredit, 'default', 0);
            $configdata ->saveConfig('connector/settings/giftcard', $giftCard, 'default', 0);
            $configdata ->saveConfig('connector/settings/storecreditpayment', $storeCreditPayment, 'default', 0);
            $configdata ->saveConfig('connector/settings/giftcardpayment', $giftCardPayment, 'default', 0);
            $configdata ->saveConfig('connector/settings/sellgiftcard', $sellGiftCard, 'default', 0);
            // $configdata ->saveConfig('connector/configproduct/managestock',$manageStock, 'default', 0);
            $configdata ->saveConfig('connector/settings/giftcard_displayname', $giftCardDisplayName, 'default', 0);


            if($syncGiftStatus == 1)
            {
                $configdata->saveConfig('connector/settings/syncgiftranges',1,'default',0);
            }
            else
            {
                $configdata->saveConfig('connector/settings/syncgiftranges',0,'default',0);
            }



            if($_FILES['giftcardimage']['tmp_name']!='')
            {
                $giftImageName = $_FILES['giftcardimage']['name'];
                $tmpName = $_FILES['giftcardimage']['tmp_name'];
                $destination = Mage::getBaseDir('media').DS.'configuration';
                if(!file_exists($destination) && !is_dir($destination))
                {
                    mkdir(Mage::getBaseDir().'/media/configuration',0777);
                }
                Move_uploaded_file($tmpName,"$destination/$giftImageName");
            }
            else
            {
                $giftImageName = Mage::getStoreConfig('connector/settings/giftcardimage');
            }
            $configdata ->saveConfig('connector/settings/giftcardimage', $giftImageName, 'default', 0);
            $configdata ->saveConfig('connector/settings/giftcardtext', $giftCardText, 'default', 0);
            $configdata ->saveConfig('connector/settings/giftcardheader', $giftCardHeader, 'default', 0);

            //code for gift card display settings saving
            if($left==1)
            {
                $configdata ->saveConfig('connector/settings/showleft', $left, 'default', 0);
            }
            else{
                $configdata ->saveConfig('connector/settings/showleft', $left, 'default', 0);
            }
            if($right==1)
            {
                $configdata ->saveConfig('connector/settings/showright', $right, 'default', 0);
            }
            else{
                $configdata ->saveConfig('connector/settings/showright', $right, 'default', 0);
            }
            if($home==1)
            {
                $configdata ->saveConfig('connector/settings/showhome', $home, 'default', 0);
            }
            else{
                $configdata ->saveConfig('connector/settings/showhome', $home, 'default', 0);
            }

            /*
            * Cleaning the configuration cache programatically
            */
            try {
                $allTypes = Mage::app()->useCache();
                foreach($allTypes as $type => $blah) {
                    Mage::log($blah);
                    Mage::app()->getCacheInstance()->cleanType($type);
                }
            } catch (Exception $e) {
                // do something
                error_log($e->getMessage());
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Configuration was successfully saved'));
            $this->_redirect('*/*/');
            return;
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->setData($this->getRequest()->getPost());
            $this->_redirect('*/*/');
            return;
        }
    }

    /**
     * @author: KT174
     * @description This method is used to get the edit link id and redirect to Model Edit Page.
     */
    public function mbizgeteditlinkAction() {

        $objId = $this->getRequest()->getParam('obj_id');
        $objModel = $this->getRequest()->getParam('obj_model');
        Mage::log("came to mbizgeteditlinks admin",null,'links.log');

        switch($objModel) {
            case 'product' :
                Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/catalog_product/edit/", array("id"=>$objId)));

                break;
            case 'customer' :

                Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/customer/edit/", array("id"=>$objId)));

                break;
            case 'order' :

                Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/sales_order/view/", array("order_id"=>$objId)));

                break;

            default :

                Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/dashboard/index/"));
                break;
        }


    }

    /**
     * @author KT174
     * @description This function is used to redirect to plugin sign in page.
     */
    public function mbizinstallsigninAction()
    {
        Mage::helper('microbiz_connector')->RemoveCaching();
        $this->loadLayout();
        $this->_title($this->__("Microbiz POS Plugin Install"));
        $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/pluginsignin.phtml");
        $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }

    /**
     * @author KT174
     * @description This Function is used to Sign Up for New MicroBiz Cloud Instance.
     */
    public function mbizinstallsignupAction()
    {
        Mage::helper('microbiz_connector')->RemoveCaching();
        $this->loadLayout();
        $this->_title($this->__("Setup Wizard"));
        $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/pluginsignup.phtml");
        $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }

    /**
     * @return $this
     * @author - KT174
     * @description - This method is used to get the post data submitted from the Installation Wizard Sign In Form.
     * And check the Test connection process for further steps
     */
    public function mbizvalidateinstallposAction()
    {
        Mage::helper('microbiz_connector')->RemoveCaching();
        $postdata=$this->getRequest()->getPost();

        if($postdata['mbiz_post_signin']==1) {
            $arrResponse =  Mage::helper('microbiz_connector')->testConnection($postdata);

            if($arrResponse['status']=='SUCCESS') {

                $configData = new Mage_Core_Model_Config();
                if($postdata['mbiz_sitetype']==1)
                {
                    $mbizSiteUrl = 'https://'.$postdata['mbiz_sitename'].'.microbiz.com';

                    $configData->saveConfig('connector/installation/mbiz_siteurl',$mbizSiteUrl, 'default', 0);
                    $configData->saveConfig('connector/installation/mbiz_sitename',$postdata['mbiz_sitename'], 'default', 0);
                }
                else {
                    $mbizSiteUrl = $postdata['mbiz_sitename'];
                    $configData->saveConfig('connector/installation/mbiz_siteurl',$mbizSiteUrl, 'default', 0);
                    $configData->saveConfig('connector/installation/mbiz_sitename',$postdata['mbiz_sitename'], 'default', 0);
                }

                $message = $this->__('Test Connection with MicroBiz Instance is Successful.');
                Mage::getSingleton('core/session')->addSuccess($message);
                $this->loadLayout();
                $this->_title($this->__("Microbiz POS Plugin Install"));
                $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/pluginapidetails.phtml");
                $block->setInstallSignInData($postdata);
                $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();


            }
            else {
                $message = $this->__($arrResponse['message']);
                Mage::getSingleton('core/session')->addError($message);
                $this->loadLayout();
                $this->_title($this->__("Microbiz POS Plugin Install"));
                $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/pluginsignin.phtml");
                $block->setInstallSignInData($postdata);
                $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();
            }
        }
        else {
            $this->loadLayout();
            $this->_title($this->__("Microbiz POS Plugin Install"));
            $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/pluginapidetails.phtml");
            $block->setInstallSignInData($postdata);
            $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
            $this->getLayout()->getBlock('content')->append($block);
            $this->renderLayout();
        }
    }

    /**
     * @author - KT174
     * @description This method is used to get the api username and api email from the post data and create and send it
     *to MicroBiz.
     */
    public function mbizValidateApiUserDetailsAction()
    {
        Mage::helper('microbiz_connector')->RemoveCaching();
        $isLinked = Mage::getStoreConfig('connector/installation/is_linked');
        if($isLinked!=1) {
            $postdata=$this->getRequest()->getPost();

            $apiuserName = Mage::getStoreConfig('connector/installation/mag_api_username');
            $apiUserEmail = Mage::getStoreConfig('connector/installation/mag_api_email');
            $apiPassword = Mage::getStoreConfig('connector/settings/mag_api_key');
            if($apiuserName=='') {
                $apiUserEmail = $postdata['mag_api_email'];
                $apiuserName = $postdata['mag_api_username'];

                $usernameApiModel = Mage::getModel('api/user')->getCollection()
                    ->addFieldToFilter('username',$apiuserName)
                    ->getFirstItem()->getData();


                if(empty($usernameApiModel))
                {
                    $userApiModel = Mage::getModel('api/user')->getCollection()
                        ->addFieldToFilter('email',$apiUserEmail)
                        ->getFirstItem()->getData();

                    if(count($userApiModel)==0)
                    {


                        $message = $this->__("Magento Api User and Roles are Creating in both Magento and Microbiz Instances. Please wait...");
                        Mage::getSingleton('core/session')->addSuccess($message);
                        $this->loadLayout();
                        $this->_title($this->__("Microbiz POS Plugin Install"));
                        $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/linking.phtml");
                        $block->setLinkStatus('Linking');
                        $block->setLinkData($postdata);
                        $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                        $this->getLayout()->getBlock('content')->append($block);
                        $this->renderLayout();
                    }
                    else {


                        $message = $this->__("Email Id ".$apiUserEmail." Already Exists, Please add Other Email Id to Process Further");
                        Mage::getSingleton('core/session')->addError($message);
                        $this->loadLayout();
                        $this->_title($this->__("Microbiz POS Plugin Install"));
                        $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/pluginapidetails.phtml");
                        $block->setInstallSignInData($postdata);
                        $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                        $this->getLayout()->getBlock('content')->append($block);
                        $this->renderLayout();

                    }
                }
                else
                {

                    $message = $this->__("Magento Api User and Roles are Creating in both Magento and Microbiz Instances. Please wait...");
                    Mage::getSingleton('core/session')->addSuccess($message);
                    $this->loadLayout();
                    $this->_title($this->__("Microbiz POS Plugin Install"));
                    $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/linking.phtml");
                    $block->setLinkStatus('Linking');
                    $block->setLinkData($postdata);
                    $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                    $this->getLayout()->getBlock('content')->append($block);
                    $this->renderLayout();
                }
            }
            else {
                $message = $this->__("Magento Api User and Roles are Creating in both Magento and Microbiz Instances. Please wait...");
                Mage::getSingleton('core/session')->addSuccess($message);
                $this->loadLayout();
                $this->_title($this->__("Microbiz POS Plugin Install"));
                $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/linking.phtml");
                $block->setLinkStatus('Linking');
                $block->setLinkData($postdata);
                $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();
            }
        }
        else {
            $postdata=$this->getRequest()->getPost();
            $this->loadLayout();
            $this->_title($this->__("Microbiz POS Plugin Install"));
            $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/linking.phtml");
            $block->setLinkStatus('Success');
            $block->setLinkData($postdata);
            $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
            $this->getLayout()->getBlock('content')->append($block);
            $this->renderLayout();
        }
    }

    /**
     * @author KT174
     * @description This method is used to set choosesettings template.
     */
    public function mbizsettingwizardAction()
    {
        Mage::helper('microbiz_connector')->RemoveCaching();
        $postData = $this->getRequest()->getPost();
        $this->loadLayout();
        $this->_title($this->__("Microbiz POS Plugin Install"));
        $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/choosesettings.phtml");
        $block->setInstallationData($postData);
        $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }

    /**
     * @author KT174
     * @descrption This method is used to set the settings page template based on the selected direction.
     */
    public function mbizsavedirectionAction()
    {
        Mage::helper('microbiz_connector')->RemoveCaching();
        $postData = $this->getRequest()->getPost();
        $syncDirection = $postData['sync_direction'];
        $configdata = new Mage_Core_Model_Config();
        if($syncDirection==1)
        {


            $configdata ->saveConfig('connector/installation/sync_direction', $syncDirection, 'default', 0);
            Mage::helper('microbiz_connector')->RemoveCaching();
            $this->loadLayout();
            $this->_title($this->__("Microbiz POS Plugin Install"));
            $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/mbiztomag.phtml");
            $block->setSyncDirectionData($postData);
            $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
            $this->getLayout()->getBlock('content')->append($block);
            $this->renderLayout();
        }
        else if($syncDirection==2)
        {
            $configdata ->saveConfig('connector/installation/sync_direction', $syncDirection, 'default', 0);
            Mage::helper('microbiz_connector')->RemoveCaching();
            $this->loadLayout();
            $this->_title($this->__("Microbiz POS Plugin Install"));
            $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/magtombiz.phtml");
            $block->setSyncDirectionData($postData);
            $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
            $this->getLayout()->getBlock('content')->append($block);
            $this->renderLayout();
        }
        else {

            $configdata ->saveConfig('connector/settings/installation', 1, 'default', 0);
            $configdata ->saveConfig('connector/installation/sync_direction', $syncDirection, 'default', 0);
            $configdata ->saveConfig('connector/mbiz/initialsync/status', 0, 'default', 0);
            $configdata ->saveConfig('connector/mbiz/noinitialsync/status', 1, 'default', 0);
            $configdata ->saveConfig('connector/batchsizesettings/batchsize', 20, 'default', 0);
            Mage::helper('microbiz_connector')->RemoveCaching();

            /*Sending Empty Settings to MicroBiz for No Initial Sync*/
            $sitename = Mage::getStoreConfig('connector/installation/mbiz_sitename');
            $apiUsername = Mage::getStoreConfig('connector/settings/api_user');
            $apiPassword = Mage::getStoreConfig('connector/settings/api_key');


            $mbizSaveSettings = array();

            $store = Mage::getModel('core/store')->load(Mage_Core_Model_App::DISTRO_STORE_ID);
            $rootId = $store->getRootCategoryId();

            $instanceId = Mage::getStoreConfig('connector/settings/instance_id');
            $mbizSaveSettings['product_tax_class'] = $mbizSaveSettings['mage_product_tax_class'] = $mbizSaveSettings['root_category'] = $mbizSaveSettings['customer_tax_class'] = $mbizSaveSettings['mage_customer_tax_class'] = $mbizSaveSettings['customer_type'] = $mbizSaveSettings['inventory'] = $mbizSaveSettings['stock_balance_to'] =
            $mbizSaveSettings['stock_balance_from'] = $mbizSaveSettings['sync_attributesets']= $mbizSaveSettings['sync_categories']= $mbizSaveSettings['sync_customers']= $mbizSaveSettings['sync_products']=
            $mbizSaveSettings['sync_inventory']= null;
            $mbizSaveSettings['magento_instance_id']=$instanceId;
            $mbizSaveSettings['root_category'] = $rootId;
            $mbizSaveSettings['web_price'] = Mage::getStoreConfig('tax/calculation/price_includes_tax');
            $mbizSaveSettings['attr_supp_manuf'] = $mbizSaveSettings['attr_brand'] = $mbizSaveSettings['attr_manuf_upc'] = $mbizSaveSettings['attr_cost'] = null;
            $mbizSaveSettings['app_plugin_version'] = (string) Mage::getConfig()->getNode()->modules->Microbiz_Connector->version;

            $response = Mage::helper('microbiz_connector')->mbizSendSettings($sitename,$apiUsername,$apiPassword,$mbizSaveSettings);

            if($response['http_code']==200) {
                $message = 'Your Installation is Completed Successfully and empty Settings Sent to MicroBiz.';
                Mage::getSingleton('core/session')->addSuccess($message);
            }
            else {
                $message = 'Your Installation is Completed Successfully and Http Error.'.$response['http_code'].' Occurred while Sending Settings to MicroBiz.';
                Mage::getSingleton('core/session')->addError($message);
            }

            $this->_redirect('*/*/index');
            /*$this->loadLayout();
            $this->_title($this->__("Configuration"));
            $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/configuration.phtml");
            $block->setCompleteInstallData($postData);
            $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
            $this->getLayout()->getBlock('content')->append($block);
            $this->renderLayout();*/
        }

    }

    /**
     * @author - KT174
     * @description This method is used to get the Magento to MicroBiz Settings Values and save them in store config
     * data.
     */
    public function savemagtombizsettingsAction()
    {
        $postData = $this->getRequest()->getPost();
        //echo'<pre>';print_r($postData);exit;
        $prodSyncSett = $postData['product_sync_setting'];
        $prodTaxClass = $postData['product_tax_class'];
        $magRootCate = $postData['root_category'];
        $attrSupplManf = explode(",",$postData['attr_supp_manuf']);
        $attrSupplManfId = $attrSupplManf[0];
        $attrManufUpcCode = $attrSupplManf[1];
        $attrBrand = explode(",",$postData['attr_brand']);
        $attrBrandId = $attrBrand[0];
        $attrBrandCode = $attrBrand[1];
        $attrManufUpc = explode(",",$postData['attr_manuf_upc']);
        $attrManufUpcId = $attrManufUpc[0];
        $attrManufUpccCode = $attrManufUpc[1];
        $attrCost = explode(",",$postData['attr_cost']);
        $attrCostId = $attrCost[0];
        $attrCostCode = $attrCost[1];
        $customersSync = $postData['customers'];
        $customerTaxClass = $postData['customer_tax_class'];
        $customerType = $postData['customer_type'];
        $inventorySync = $postData['inventory'];
        $stockBalTo = $postData['stock_balance_to'];
        $stockBalFrom = $postData['stock_balance_from'];

        try {
            $configdata = new Mage_Core_Model_Config();

            $configdata ->saveConfig('connector/magtombiz_settings/product_sync_setting', $prodSyncSett, 'default', 0);
            $configdata ->saveConfig('connector/magtombiz_settings/product_tax_class', $prodTaxClass, 'default', 0);
            $configdata ->saveConfig('connector/magtombiz_settings/root_category', $magRootCate, 'default', 0);
            $configdata ->saveConfig('connector/magtombiz_settings/attr_supp_manuf', $attrSupplManfId, 'default', 0);
            $configdata ->saveConfig('connector/magtombiz_settings/attr_brand', $attrBrandId, 'default', 0);
            $configdata ->saveConfig('connector/magtombiz_settings/attr_manuf_upc', $attrManufUpcId, 'default', 0);
            $configdata ->saveConfig('connector/magtombiz_settings/attr_cost', $attrCostId, 'default', 0);
            $configdata ->saveConfig('connector/magtombiz_settings/customers', $customersSync, 'default', 0);
            $configdata ->saveConfig('connector/magtombiz_settings/customer_tax_class', $customerTaxClass, 'default', 0);
            $configdata ->saveConfig('connector/magtombiz_settings/customer_type', $customerType, 'default', 0);
            $configdata ->saveConfig('connector/magtombiz_settings/inventory', $inventorySync, 'default', 0);
            $configdata ->saveConfig('connector/magtombiz_settings/stock_balance_to', $stockBalTo, 'default', 0);
            $configdata ->saveConfig('connector/magtombiz_settings/stock_balance_from', $stockBalFrom, 'default', 0);
            $configdata ->saveConfig('connector/batchsizesettings/batchsize', 20, 'default', 0);

            Mage::helper('microbiz_connector')->RemoveCaching();
            Mage::helper('microbiz_connector')->RemoveCaching();

            $prodSyncStatus = Mage::getStoreConfig('connector/magtombiz_settings/product_sync_setting');  // 0-do not sync,1 - sync all products, 2- sync enabled products
            $custSyncStatus = Mage::getStoreConfig('connector/magtombiz_settings/customers');   //0 - Do not sync customers,1- sync all customers
            $productInventory = Mage::getStoreConfig('connector/magtombiz_settings/inventory');

            $mbizSaveSettings = array();
            $mbizSaveSettings['product_tax_class'] = $prodTaxClass;
            $mbizSaveSettings['root_category'] = $magRootCate;
            $mbizSaveSettings['customer_tax_class'] = $customerTaxClass;
            $mbizSaveSettings['customer_type'] = $customerType;
            $mbizSaveSettings['inventory'] = $inventorySync;
            $mbizSaveSettings['stock_balance_to'] = $stockBalTo;
            $mbizSaveSettings['stock_balance_from'] = $stockBalFrom;

            $mbizSaveSettings['sync_attributesets']=1;
            $mbizSaveSettings['sync_categories']=1;
            $mbizSaveSettings['sync_customers']=$customersSync;
            $mbizSaveSettings['sync_products']=$prodSyncSett;
            $mbizSaveSettings['sync_inventory']=$inventorySync;
            $instanceId = Mage::getStoreConfig('connector/settings/instance_id');
            $mbizSaveSettings['magento_instance_id']=$instanceId;


            $mbizSaveSettings['attr_supp_manuf'] = array('attr_supp_manuf_id'=>$attrSupplManfId,'attr_supp_manuf_code'=>$attrManufUpcCode);
            $mbizSaveSettings['attr_brand'] = array('attr_brand_id'=>$attrBrandId,'attr_brand_code'=>$attrBrandCode);
            $mbizSaveSettings['attr_manuf_upc'] = array('attr_manuf_upc_id'=>$attrManufUpcId,'attr_manuf_upc_code'=>$attrManufUpcCode);
            $mbizSaveSettings['attr_cost'] = array('attr_cost_id'=>$attrCostId,'attr_cost_code'=>$attrCostCode);
            $mbizSaveSettings['web_price'] = Mage::getStoreConfig('tax/calculation/price_includes_tax');
            $mbizSaveSettings['app_plugin_version'] = (string) Mage::getConfig()->getNode()->modules->Microbiz_Connector->version;

            $mbizSaveSettings['mage_product_tax_class'] = $mbizSaveSettings['mage_customer_tax_class'] = null;

            $sitename = $postData['mbiz_sitename'];
            $apiUsername = $postData['mbiz_api_username'];
            $apiPassword = $postData['mbiz_api_password'];
            Mage::log("came to savemethod",null,'linking.log');
            Mage::log($mbizSaveSettings,null,'linking.log');
            $response = Mage::helper('microbiz_connector')->mbizSendSettings($sitename,$apiUsername,$apiPassword,$mbizSaveSettings);



            if($response['http_code']==200)
            {
                /*Saving Inventory Root Category Relation*/

                Mage::helper('microbiz_connector')->mbizSaveInventoryCategory($magRootCate);
                $message  = $this->__('Your Installation is Completed Successfully and Settings Sent to MicroBiz.');
                Mage::getSingleton('core/session')->addSuccess($message);
                $this->loadLayout();
                $this->_title($this->__("Microbiz POS Plugin Install"));
                $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/installcomplete.phtml");
                $block->setSyncDirectionData($postData);
                $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();
            }
            else {
                $message  = $this->__('Your Installation is Completed Successfully and Settings Not Sent to MicroBiz.');
                Mage::getSingleton('core/session')->addError($message);
                $this->loadLayout();
                $this->_title($this->__("Microbiz POS Plugin Install"));
                $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/magtombiz.phtml");
                $block->setSyncDirectionData($postData);
                $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();
            }

        }
        catch (Exception $e)
        {
            $message = $e->getMessage();
            Mage::getSingleton('core/session')->addError($message);
            $this->loadLayout();
            $this->_title($this->__("Microbiz POS Plugin Install"));
            $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/magtombiz.phtml");
            $block->setSyncDirectionData($postData);
            $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
            $this->getLayout()->getBlock('content')->append($block);
            $this->renderLayout();


        }

        Mage::helper('microbiz_connector')->RemoveCaching();
    }

    /**
     * @author - KT174
     * @description This method is used to get the MicroBiz to Magento Settings Values and save them in store config
     * data.
     */
    public function savembiztomagsettingsAction()
    {
        $postData = $this->getRequest()->getPost();
        //echo '<pre>';print_r($postData);exit;
        $prodSyncSett = $postData['product_sync_setting'];
        $prodTaxClass = $postData['mage_product_tax_class'];
        $magRootCate = $postData['root_category'];

        $attrSupplManf = explode(",",$postData['attr_supp_manuf']);
        $attrSupplManfId = $attrSupplManf[0];
        $attrManufUpcCode = $attrSupplManf[1];
        $attrBrand = explode(",",$postData['attr_brand']);
        $attrBrandId = $attrBrand[0];
        $attrBrandCode = $attrBrand[1];
        $attrManufUpc = explode(",",$postData['attr_manuf_upc']);
        $attrManufUpcId = $attrManufUpc[0];
        $attrManufUpccCode = $attrManufUpc[1];
        $attrCost = explode(",",$postData['attr_cost']);
        $attrCostId = $attrCost[0];
        $attrCostCode = $attrCost[1];

        $customersSync = $postData['customers'];
        $customerTaxClass = $postData['mage_customer_tax_class'];
        $customerType = $postData['customer_type'];
        $inventorySync = $postData['inventory'];
        $stockBalTo = $postData['stock_balance_to'];
        $stockBalFrom = $postData['stock_balance_from'];

        try {
            $configdata = new Mage_Core_Model_Config();

            $configdata ->saveConfig('connector/mbiztomag_settings/product_sync_setting', $prodSyncSett, 'default', 0);
            $configdata ->saveConfig('connector/mbiztomag_settings/mage_product_tax_class', $prodTaxClass, 'default', 0);
            $configdata ->saveConfig('connector/mbiztomag_settings/root_category', $magRootCate, 'default', 0);
            $configdata ->saveConfig('connector/mbiztomag_settings/attr_supp_manuf', $attrSupplManfId, 'default', 0);
            $configdata ->saveConfig('connector/mbiztomag_settings/attr_brand', $attrBrandId, 'default', 0);
            $configdata ->saveConfig('connector/mbiztomag_settings/attr_manuf_upc', $attrManufUpcId, 'default', 0);
            $configdata ->saveConfig('connector/mbiztomag_settings/attr_cost', $attrCostId, 'default', 0);
            $configdata ->saveConfig('connector/mbiztomag_settings/customers', $customersSync, 'default', 0);
            $configdata ->saveConfig('connector/mbiztomag_settings/mage_customer_tax_class', $customerTaxClass, 'default', 0);
            $configdata ->saveConfig('connector/mbiztomag_settings/inventory', $inventorySync, 'default', 0);
            $configdata ->saveConfig('connector/mbiztomag_settings/stock_balance_to', $stockBalTo, 'default', 0);
            $configdata ->saveConfig('connector/mbiztomag_settings/stock_balance_from', $stockBalFrom, 'default', 0);
            $configdata ->saveConfig('connector/batchsizesettings/batchsize', 20, 'default', 0);

            Mage::helper('microbiz_connector')->RemoveCaching();
            Mage::helper('microbiz_connector')->RemoveCaching();

            $prodSyncStatus = Mage::getStoreConfig('connector/mbiztomag_settings/product_sync_setting');  // 0-do not sync,1 - sync all products, 2- sync enabled products
            $custSyncStatus = Mage::getStoreConfig('connector/mbiztomag_settings/customers');   //0 - Do not sync customers,1- sync all customers
            $productInventory = Mage::getStoreConfig('connector/mbiztomag_settings/inventory');

            $mbizSaveSettings = array();
            $mbizSaveSettings['mage_product_tax_class'] = $prodTaxClass;
            $mbizSaveSettings['root_category'] = $magRootCate;
            $mbizSaveSettings['mage_customer_tax_class'] = $customerTaxClass;
            $mbizSaveSettings['customer_type'] = $customerType;
            $mbizSaveSettings['inventory'] = $inventorySync;
            $mbizSaveSettings['stock_balance_to'] = $stockBalTo;
            $mbizSaveSettings['stock_balance_from'] = $stockBalFrom;

            $mbizSaveSettings['sync_attributesets']=1;
            $mbizSaveSettings['sync_categories']=1;
            $mbizSaveSettings['sync_customers']=$customersSync;
            $mbizSaveSettings['sync_products']=$prodSyncSett;
            $mbizSaveSettings['sync_inventory']=$inventorySync;

            $instanceId = Mage::getStoreConfig('connector/settings/instance_id');
            $mbizSaveSettings['magento_instance_id']=$instanceId;

            $mbizSaveSettings['attr_supp_manuf'] = array('attr_supp_manuf_id'=>$attrSupplManfId,'attr_supp_manuf_code'=>$attrManufUpcCode);
            $mbizSaveSettings['attr_brand'] = array('attr_brand_id'=>$attrBrandId,'attr_brand_code'=>$attrBrandCode);
            $mbizSaveSettings['attr_manuf_upc'] = array('attr_manuf_upc_id'=>$attrManufUpcId,'attr_manuf_upc_code'=>$attrManufUpcCode);
            $mbizSaveSettings['attr_cost'] = array('attr_cost_id'=>$attrCostId,'attr_cost_code'=>$attrCostCode);

            $mbizSaveSettings['web_price'] = Mage::getStoreConfig('tax/calculation/price_includes_tax');
            $mbizSaveSettings['app_plugin_version'] = (string) Mage::getConfig()->getNode()->modules->Microbiz_Connector->version;

            $mbizSaveSettings['product_tax_class'] = $mbizSaveSettings['customer_tax_class'] = null;

            $sitename = $postData['mbiz_sitename'];
            $apiUsername = $postData['mbiz_api_username'];
            $apiPassword = $postData['mbiz_api_password'];
            Mage::log("came to savemethod",null,'linking.log');
            Mage::log($mbizSaveSettings,null,'linking.log');
            $response = Mage::helper('microbiz_connector')->mbizSendSettings($sitename,$apiUsername,$apiPassword,$mbizSaveSettings);


            if($response['http_code']==200)
            {
                /*Saving Inventory Root Category Relation*/

                Mage::helper('microbiz_connector')->mbizSaveInventoryCategory($magRootCate);

                $message  = $this->__('Your Installation is Completed Successfully and Settings Sent to MicroBiz.');
                Mage::getSingleton('core/session')->addSuccess($message);
                $this->loadLayout();
                $this->_title($this->__("Microbiz POS Plugin Install"));
                $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/installcomplete.phtml");
                $block->setSyncDirectionData($postData);
                $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();
            }
            else {
                $message  = $this->__('Your Installation is Completed Successfully and Settings Not Sent to MicroBiz.');
                Mage::getSingleton('core/session')->addError($message);
                $this->loadLayout();
                $this->_title($this->__("Microbiz POS Plugin Install"));
                $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/magtombiz.phtml");
                $block->setSyncDirectionData($postData);
                $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();
            }

        }
        catch (Exception $e)
        {
            $message = $e->getMessage();
            Mage::getSingleton('core/session')->addError($message);
            $this->loadLayout();
            $this->_title($this->__("Microbiz POS Plugin Install"));
            $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/magtombiz.phtml");
            $block->setSyncDirectionData($postData);
            $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
            $this->getLayout()->getBlock('content')->append($block);
            $this->renderLayout();


        }
        Mage::helper('microbiz_connector')->RemoveCaching();
    }

    /**
     * @author KT174
     * @description This method is used to begin the initial sync based on the post data and selected settings.
     */
    public function mbizbegininitialsyncAction()
    {
        $postData = $this->getRequest()->getPost();
        //echo "<pre>";
        //$configData = new Mage_Core_Model_Config();
        //$configData ->saveConfig('connector/mbiz/noinitialsync/status', 0, 'default', 0);
        $configData = new Mage_Core_Model_Config();
        $configData ->saveConfig('connector/settings/installation', 1, 'default', 0);
        Mage::helper('microbiz_connector')->RemoveCaching();

        Mage::log("came to mbiz begininitial sync action",null,"beginsync.log");
        Mage::log($postData,null,"beginsync.log");
        if(!empty($postData))
        {
            //print_r($postData);
            Mage::helper('microbiz_connector')->RemoveCaching();
            $mbizSiteType = Mage::getStoreConfig('connector/installation/mbiz_sitetype');
            if($mbizSiteType==1)
            {
                $clientName = $postData['mbiz_sitename'];
            }
            else {

                $mbizSiteUrl = Mage::getStoreConfig('connector/installation/mbiz_siteurl');
                $urlarr = explode('//',$mbizSiteUrl);

                $urlNoHttp = $urlarr[1];
                $displayarr = explode('.',$urlNoHttp);
                $clientName = $displayarr[0];
            }
            $SyncStatus = 'Pending';
            $syncDirection = Mage::getStoreConfig('connector/installation/sync_direction');
            $apiData = array();
            $apiData['client_name'] = $clientName;
            $apiData['initial_sync_status'] = $SyncStatus;
            if($syncDirection==1) {
                $initiateFrom = 'MBIZ';
            }
            else if($syncDirection==2) {
                $initiateFrom = 'MAGE';
            }
            else {
                $initiateFrom = '';
            }
            $apiData['initiate_from'] = $initiateFrom;
            $data = json_encode($apiData);
            Mage::log($data,null,"beginsync.log");
            /*Sending Api call to MicroBiz to Start the Sync.*/
            //$loadJobUrl = $clientName.'.microbiz.com/index.php/api/setInitialSyncClientsStatus';
            $mbizSiteUrl = Mage::getStoreConfig('connector/installation/mbiz_siteurl');
            $loadJobUrl = $mbizSiteUrl.'/index.php/api/setInitialSyncClientsStatus';

            Mage::log($loadJobUrl,null,"beginsync.log");
            $method = 'POST';

            $headers = '';
            $apipath = Mage::getStoreConfig('connector/settings/api_user');
            $apipassword = Mage::getStoreConfig('connector/settings/api_key');
            $headers = array(
                'Accept: application/json',
                'Content-Type: application/json',
                'X-MBIZPOS-USERNAME: '.$apipath,
                'X-MBIZPOS-PASSWORD: '.$apipassword
            );

            $handle = curl_init();		//curl request to create the product
            curl_setopt($handle, CURLOPT_URL, $loadJobUrl);
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $data);


            $response = curl_exec($handle);	// send curl request to microbiz

            $response=json_decode($response,true);
            Mage::log($response,null,"beginsync.log");

            $code = curl_getinfo($handle);
            Mage::log($code,null,"beginsync.log");

            if($code['http_code']=='200')
            {

                $configData ->saveConfig('connector/mbiz/initialsync/status', 1, 'default', 0);
                $configData ->saveConfig('connector/mbiz/noinitialsync/status', 0, 'default', 0);
                $configData ->saveConfig('connector/mbiz/initialsync/model', 'AttributeSets', 'default', 0);
                $configData ->saveConfig('connector/mbiz/initialsync/lastInsertId', 0, 'default', 0);
                $configData ->saveConfig('connector/mbiz/initialsync/state', 1, 'default', 0);
                Mage::helper('microbiz_connector')->RemoveCaching();

                $message = $this->__('Your Installation is Completed Successfully and Initial Sync Started.');
                Mage::getSingleton('core/session')->addSuccess($message);
                $this->_redirect('*/*/index');
                /*$this->loadLayout();
                $this->_title($this->__("Configuration"));
                $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/configuration.phtml");
                $block->setCompleteInstallData($postData);
                $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();*/

                Mage::helper('microbiz_connector')->RemoveCaching();
            }
            else if($code['http_code'] == 500) {
                $message  = $this->__('Your Installation is Completed Successfully and Initial Sync Not Started due to '.$code['http_code'].' - Internal Server Error');
                Mage::getSingleton('core/session')->addError($message);
                $this->_redirect('*/*');
            }
            else if($code['http_code'] == 0) {
                $message  = $this->__('Your Installation is Completed Successfully and Initial Sync Not Started due to '.$code['http_code'].' - Please Check the API Server URL');
                Mage::getSingleton('core/session')->addError($message);
                $this->_redirect('*/*');
            }
            else
            {
                $message  = $this->__('Your Installation is Completed Successfully and Initial Sync Not Started due to '.$code['http_code']);
                Mage::getSingleton('core/session')->addError($message);
                $this->_redirect('*/*');
            }
        }

    }

    /**
     * @author KT174
     * @description This method is used to cancel and initial sync and set some config values and redirect to config page
     */
    public function cancelintialsyncAction()
    {
        $configData = new Mage_Core_Model_Config();
        $configData ->saveConfig('connector/settings/installation', 1, 'default', 0);
        $configData ->saveConfig('connector/mbiz/initialsync/status', 0, 'default', 0);
        $configData ->saveConfig('connector/mbiz/noinitialsync/status', 1, 'default', 0);


        Mage::helper('microbiz_connector')->RemoveCaching();
        Mage::helper('microbiz_connector')->RemoveCaching();

        $siteType = Mage::getStoreConfig('connector/installation/mbiz_sitetype');
        $message = $this->__('Your Installation is Completed Successfully.');
        Mage::getSingleton('core/session')->addSuccess($message);
        $this->_redirect('*/*/index');
        /*$postData = $this->getRequest()->getPost();

        $this->loadLayout();
        $this->_title($this->__("Configuration"));
        $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/configuration.phtml");
        $block->setCompleteInstallData($postData);
        $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();*/
        Mage::helper('microbiz_connector')->RemoveCaching();
    }

    /**
     * @author KT174
     * @description This method is used to start the initial load setup wizard from where the user has cancelled.
     */
    public function startinitialsyncAction()
    {
        Mage::helper('microbiz_connector')->RemoveCaching();
        Mage::helper('microbiz_connector')->RemoveCaching();
        $installComplete = Mage::getStoreConfig('connector/settings/installation');

        $initialSyncStatus = Mage::getStoreConfig('connector/mbiz/initialsync/status');

        $syncDirection = Mage::getStoreConfig('connector/installation/sync_direction');

        if(!$initialSyncStatus)
        {
            if($syncDirection==1) //mbiztomag
            {
                $postData = array();
                $this->loadLayout();
                $this->_title($this->__("Microbiz POS Plugin Install"));
                $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/choosesettings.phtml");
                $block->setSyncDirectionData($postData);
                $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();
            }
            else if($syncDirection==2)  //magtombiz
            {
                $postData = array();
                $this->loadLayout();
                $this->_title($this->__("Microbiz POS Plugin Install"));
                $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/choosesettings.phtml");
                $block->setSyncDirectionData($postData);
                $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();
            }
            else {  //chose direction
                $postData = array();
                $this->loadLayout();
                $this->_title($this->__("Microbiz POS Plugin Install"));
                $block = $this->getLayout()->createBlock('microbiz_connector/connector')->setTemplate("connector/choosesettings.phtml");
                $block->setSyncDirectionData($postData);
                $this->_setActiveMenu('microbiz_connector/microbiz_connector'); // to set the menu item active
                $this->getLayout()->getBlock('content')->append($block);
                $this->renderLayout();
            }
        }

    }

}
