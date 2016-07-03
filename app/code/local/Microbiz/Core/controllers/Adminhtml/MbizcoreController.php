<?php
class Microbiz_Core_Adminhtml_MbizcoreController extends Mage_Adminhtml_Controller_Action
{
    protected $_publicActions = array('index');

    protected function _initAction()
	{
        $this->loadLayout()->_setActiveMenu('microbiz_core/microbiz_core');
		$this->_addContent($this->getLayout()->createBlock('adminhtml/template')->setTemplate('core/installplugin.phtml'));
        
		return $this;
	}


    public function indexAction() {

		$this->_initAction()->renderLayout();
	}
	
	public function installpluginAction()
	{	
        ///echo "success<pre>";
        Mage::helper('microbiz_core')->RemoveCaching();
        //$postData = $this->getRequest()->getPost();
        //print_r($postData);
        $DependencyResponse = Mage::helper('microbiz_core')->checkDependencies();

        if($DependencyResponse['status']=='SUCCESS') {

            //check the files and folders permisssions before installing.

            $FolderPermissionsResponse = Mage::helper('microbiz_core')->CheckInstallPermissions();

            //print_r($FolderPermissionsResponse);exit;

            if($FolderPermissionsResponse['status']=='SUCCESS') {

                //Send the Api Request to MicroBiz Instance Automatio and Get the list of Files and Install Url.

                $InstallPluginResponse = Mage::helper('microbiz_core')->getInstallLink();
            //    print_r($InstallPluginResponse);
                $InstallLink = $InstallPluginResponse['install_link'];

                if($InstallLink) {
                    if(!is_dir(Mage::getBaseDir().'/MicrobizAutomation')) {
                        mkdir(Mage::getBaseDir().'/MicrobizAutomation',0777,true);
                    }


                    $installArray = explode('/',$InstallLink);

                    $fileId = count($installArray)-1;
                    $fileName = $installArray[$fileId];
                    $file2 = fopen(Mage::getBaseDir().'/MicrobizAutomation/'.$fileName,'w+');
                    $zipFilePath = Mage::getBaseDir().'/MicrobizAutomation/'.$fileName;
                    chmod($zipFilePath, 0777);
                    $headers = '';
                    $handle = curl_init();		//curl request to create the product
                    curl_setopt($handle, CURLOPT_URL, $InstallLink);
                    curl_setopt($handle, CURLOPT_FAILONERROR, true);
                    curl_setopt($handle, CURLOPT_HEADER, 0);
                    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($handle, CURLOPT_AUTOREFERER, true);
                    curl_setopt($handle, CURLOPT_BINARYTRANSFER,true);
                    curl_setopt($handle, CURLOPT_TIMEOUT, 10);
                    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($handle, CURLOPT_FILE, $file2);
                    $response = curl_exec($handle);	// send curl request to microbiz

                    $response=json_decode($response,true);

                    $DownloadZipResponse = curl_getinfo($handle);
                    curl_close($handle);

                    if($DownloadZipResponse['http_code']==200) {
                        //echo "File downloaded from the server successs";exit;


                        try{
                            $phar = new PharData(Mage::getBaseDir().'/MicrobizAutomation/'.$fileName);
                            //echo $phar;exit;
                            if ($phar) {
                                $phar->extractTo(Mage::getBaseDir(),null,true);
                                Mage::helper('microbiz_core')->RemoveCaching();
                                $message = 'Microbiz Connector plugin Installed Successfully';
                                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__($message));
                                $this->_redirect('*/*/');
                                return;
                                /*$adminSession = Mage::getSingleton('admin/session');
                                $adminSession->unsetAll();
                                $adminSession->getCookie()->delete($adminSession->getSessionName());*/

                            } else {
                                $message = 'Unable to Install Microbiz Connector plugin since no file is downloaded from the server.';
                                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__($message));$this->_redirect('*/*/');
                                return;
                            }

                        }
                        catch(Exception $e){
                            $message = $e->getMessage();
                            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Unable to Install Connector Plugin due to '.$message));
                            $this->_redirect('*/*/');
                            return;
                            //$this->_redirect('core/index/index');
                        }

                    }
                    else {
                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Unable to
                            Install MicroBiz Connector Plugin. Http Error '.$DownloadZipResponse['http_code'].' Occurred while Download the MicroBiz Connector Plugin.'));
                        $this->_redirect('*/*/');
                        return;
                    }

                }
                else {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Unable to
                            Install MicroBiz Connector Plugin. No Install Url is Returned by the Api Server.'));
                    $this->_redirect('*/*/');
                    return;
                }
            }
            else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__($FolderPermissionsResponse['status_msg']));
                $this->_redirect('*/*/');
                return;
            }


        }
        else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__($DependencyResponse['status_msg']));
            $this->_redirect('*/*/');
            return;
        }
				
	}
	
	public function upgradepluginAction()
	{
        Mage::log("came to upgrade Plugin Action",null,'upgrade.log');
        Mage::helper('microbiz_core')->RemoveCaching();
        $mbizSiteName = Mage::getStoreConfig('connector/settings/api_server');
        $mbizUname = Mage::getStoreConfig('connector/settings/api_user');
        $mbizPwd = Mage::getStoreConfig('connector/settings/api_key');
		$ApiConnectStatus = Mage::helper('microbiz_core')->testConnection($mbizSiteName,$mbizUname,$mbizPwd);  //check for test connection with microbiz
        Mage::log("Api Connection Response",null,'upgrade.log');
        Mage::log($ApiConnectStatus,null,'upgrade.log');
        if($ApiConnectStatus['http_code']==200) {
            $checkConflict = Mage::helper('microbiz_core')->checkConflictExists();   //check for conflicts available in upgrade list
            //echo "<pre>";print_r($checkConflict);
            Mage::log("Conflict Response",null,'upgrade.log');
            Mage::log($checkConflict,null,'upgrade.log');
            if($checkConflict['status']=='SUCCESS') {

                $configdata = new Mage_Core_Model_Config();
                $configdata->saveConfig('mbizcoreplugininstall/upgrade_available/status',0,'default',0);
                Mage::helper('microbiz_core')->RemoveCaching();
                $message = 'Microbiz Connector plugin Upgraded Successfully';
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__($message));


            }
            else {
                $codeMessage = Mage::helper('microbiz_core')->getErrorDesc($checkConflict['errorCode']);
                $message = 'Unable to Upgrade the MicroBiz Connector Plugin due to Error  '.$codeMessage;
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__($message));
            }
        }
        else {
            $message = 'Unable to Start the Upgrade Process. Error Occurred while Establishing the Api Connection.';
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__($message));
        }

			/*
			* Cleaning the configuration cache programatically
			*/
			Mage::helper('microbiz_core')->RemoveCaching();

			$this->_redirect('*/*/');
			return;
		
	}
    
	
	public function uninstallpluginAction()
	{
		$mbizCoreVer = Mage::helper('microbiz_core')->getCoreExtVersion();
		$mbizExtVer = Mage::helper('microbiz_core')->getConnectorVersion();
		
		if($mbizCoreVer && $mbizExtVer)
		{
            $giftCardProductStatus = $this->RemoveGiftCardProducts();
            if($giftCardProductStatus==1) {
                $tableStatus = $this->performTableOperations();
//$tableStatus = 1;
                if($tableStatus==1)
                {
                    $attrStatus = $this->performAttributeOperations();
                    if($attrStatus==1)
                    {
                        $fileStatus = $this->performFileOperations();


                        if($fileStatus==1)
                        {
                            $configStatus = $this->performConfigOperations();
                            if($configStatus==1)
                            {
                                $configdata = new Mage_Core_Model_Config();
                                $configdata->saveConfig('mbizcoreplugininstall/upgrade_available/status',0,'default',0);
                                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('MicroBiz Connector Plugin Un-Installed Successfully'));
                            }
                            else {

                                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Unable to Un-Install MicroBiz Connector Plugin , Error Occurred while deleting the Saved Configuration Data.'));
                            }
                        }else {

                            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Unable to Un-Install MicroBiz Connector Plugin , Error Occurred while deleting the Files.'));
                        }
                    }
                    else {

                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Unable to Un-Install MicroBiz Connector Plugin , Error Occurred while deleting the Attributes.'));
                    }

                }
                else {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Unable to Un-Install MicroBiz Connector Plugin , Error Occurred while deleting the tables.'));
                }
            }
            else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Unable to Un-Install MicroBiz Connector Plugin , Error Occurred while deleting the GiftCard Products.'));
            }
			

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

			$this->_redirect('*/*/');
			return;
//$this->_redirect('core/adminhtml_core/index');			
		
	}

    public function RemoveGiftCardProducts()
    {
        $collectionGiftcard = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToFilter('type_id', array('eq' => 'mbizgiftcard'))->getData();
        $status =1;
        //echo "<pre>";print_r($collectionGiftcard);
        if(!empty($collectionGiftcard)) {
            foreach($collectionGiftcard as $product) {
                $prdSku = $product['sku'];

                try {
                    $productId = Mage::getModel('catalog/product')->getIdBySku($prdSku);

                    if($productId) {
                        Mage::getModel('catalog/product')->load($productId)->delete();
                    }
                }
                catch(Exception $ex) {
                    $status=0;
                }



            }
        }
        else {
            $status =1;
        }

        return $status;
    }
	
	public function performTableOperations()
	{
		$resource = Mage::getSingleton('core/resource');
		$read = $resource->getConnection('core_read'); 
$write = $resource->getConnection('core_write'); 
		$arrTableList = array();
		
		

		/*$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('connector/storeinventorytotal'): $resource->getTableName('connector/storeinventorytotal');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('connector/storeinventory'): $resource->getTableName('connector/storeinventory');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('connector/storeinventoryproduct'): $resource->getTableName('connector/storeinventoryproduct');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('connector/storeinventoryproducttotal'): $resource->getTableName('connector/storeinventoryproducttotal');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('extendedmbizconnector/extendedmbizconnector'): $resource->getTableName('extendedmbizconnector/extendedmbizconnector');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('syncitems/syncitems'): $resource->getTableName('syncitems/syncitems');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('syncheaderhistory/syncheaderhistory'): $resource->getTableName('syncheaderhistory/syncheaderhistory');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('syncitemhistory/syncitemhistory'): $resource->getTableName('syncitemhistory/syncitemhistory');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('mbizcustomer/mbizcustomer'): $resource->getTableName('mbizcustomer/mbizcustomer');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('mbizproduct/mbizproduct'): $resource->getTableName('mbizproduct/mbizproduct');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('mbizattributeset/mbizattributeset'): $resource->getTableName('mbizattributeset/mbizattributeset');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('mbizcustomeraddr/mbizcustomeraddr'): $resource->getTableName('mbizcustomeraddr/mbizcustomeraddr');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('connectordebug/connectordebug'): $resource->getTableName('connectordebug/connectordebug');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('connectorcataloginventory/connectorcataloginventory'): $resource->getTableName('connectorcataloginventory/connectorcataloginventory');
		$arrTableList[] = ($prefix!='') ? $prefix.'_connector_cataloginventory_stock_status' : 'connector_cataloginventory_stock_status';

		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('saleorderheader/saleorderheader'): $resource->getTableName('saleorderheader/saleorderheader');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('saleorderitem/saleorderitem'): $resource->getTableName('saleorderitem/saleorderitem');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('saleorderbrkup/saleorderbrkup'): $resource->getTableName('saleorderbrkup/saleorderbrkup');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('mbizattributegroup/mbizattributegroup'): $resource->getTableName('mbizattributegroup/mbizattributegroup');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('mbizattribute/mbizattribute'): $resource->getTableName('mbizattribute/mbizattribute');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('mbizattributeoption/mbizattributeoption'): $resource->getTableName('mbizattributeoption/mbizattributeoption');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('pickup/pickup'): $resource->getTableName('pickup/pickup');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('syncorderitems/syncorderitems'): $resource->getTableName('syncorderitems/syncorderitems');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('mbizcategory/mbizcategory'): $resource->getTableName('mbizcategory/mbizcategory');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('mbizcreditusage/mbizcreditusage'): $resource->getTableName('mbizcreditusage/mbizcreditusage');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('mbizgiftcardsale/mbizgiftcardsale'): $resource->getTableName('mbizgiftcardsale/mbizgiftcardsale');
		$arrTableList[] = ($prefix!='') ? $prefix."_".$resource->getTableName('syncmbizstatus/syncmbizstatus'): $resource->getTableName('syncmbizstatus/syncmbizstatus');*/
		$arrTableList[] = 'storeinventory_header';
		$arrTableList[] = 'storeinventory';
		$arrTableList[] = 'prd_shipment_item_tran';
		$arrTableList[] = 'prd_shipment_header_tran';
		$arrTableList[] = 'sync_records_header_tran';
		$arrTableList[] = 'sync_records_item_tran';
		$arrTableList[] = 'sync_records_header_tran_history';
		$arrTableList[] = 'sync_records_item_tran_history';
		$arrTableList[] = 'mbiz_cust_rel_mas';
		$arrTableList[] = 'mbiz_prd_rel_mas';
		$arrTableList[] = 'mbiz_attr_set_rel_mas';
		$arrTableList[] = 'mbiz_cust_addr_rel_mas';
		$arrTableList[] = 'connector_debug';
		$arrTableList[] = 'connector_cataloginventory_stock_item';
		$arrTableList[] = 'connector_cataloginventory_stock_status';
		$arrTableList[] = 'sal_order_header_tran';
		$arrTableList[] = 'sal_order_items_tran';
		$arrTableList[] = 'sal_order_brkup_item_tran';
		$arrTableList[] = 'mbiz_attr_grp_rel_mas';
		$arrTableList[] = 'mbiz_attr_rel_mas';
		$arrTableList[] = 'mbiz_options_rel_mas';
		$arrTableList[] = 'mbiz_order_used_shipping_methods';
		$arrTableList[] = 'mbiz_sales_flat_order_item';
		$arrTableList[] = 'mbiz_category_rel_mas';
		$arrTableList[] = 'mbiz_order_credit_usage_history';
		$arrTableList[] = 'mbiz_giftcard_sale_info';
		$arrTableList[] = 'sync_mbiz_status_tran';

        //0.1.3 Tables List
        $arrTableList[] = 'connector_eav_attribute';
        $arrTableList[] = 'connector_eav_attribute_group';
        $arrTableList[] = 'connector_eav_attribute_label';
        $arrTableList[] = 'connector_eav_attribute_option';
        $arrTableList[] = 'connector_eav_attribute_option_value';
        $arrTableList[] = 'connector_eav_attribute_set';
        $arrTableList[] = 'connector_catalog_category_entity';
		//echo count($arrTableList);
		//print_r($arrTableList);exit;
		$status=1;
		if(!empty($arrTableList)) {
			$prefix = Mage::getConfig()->getTablePrefix();
			foreach($arrTableList as $tableName)
			{
				try {
				if($prefix!='') {				
					$read->query("DROP TABLE IF EXISTS ".$prefix."_".$tableName);
				}
				else {
					$read->query("DROP TABLE IF EXISTS ".$tableName);
				}
					
				}
				catch(Exception $e)
				{
					$status=0;
				}
			}
		}
		
		$write->query("Delete From core_resource WHERE code = 'connector_setup'");
		$write->query("Delete From core_resource WHERE code = 'product_setup'");
		$installer = new Mage_Core_Model_Resource_Setup();
	        $installer->startSetup();
		//$installer->run("Delete From core_resource WHERE code = 'connector_setup'");
		//$installer->run("Delete From core_resource WHERE code = 'product_setup'");
		$installer->getConnection()->dropColumn($installer->getTable('eav_attribute_set'),'sync_attr_set_create',null);
		$installer->getConnection()->dropColumn($installer->getTable('catalog_eav_attribute'),'sync_attr_create',null);
		$installer->getConnection()->dropColumn($installer->getTable('eav_attribute'),'sync_attr_create',null);
		$installer->endSetup();
		//$read->query("ALTER TABLE eav_attribute_set DROP COLUMN sync_attr_set_create");
		//$read->query("ALTER TABLE catalog_eav_attribute DROP COLUMN sync_attr_create");
		//$read->query("ALTER TABLE eav_attribute DROP COLUMN sync_attr_create");
        //$resource->close();
		return $status;
	}
	
	public function performAttributeOperations()
	{
		$eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
		//$mbizAttrs['sync_cus_create'] = $eavAttribute->getIdByCode('customer', 'sync_cus_create');
		$mbizAttrs['cust_sync_status'] = $eavAttribute->getIdByCode('customer', 'sync_status');
		$mbizAttrs['pos_cus_status'] = $eavAttribute->getIdByCode('customer', 'pos_cus_status');
		$mbizAttrs['cust_sync_update_msg'] = $eavAttribute->getIdByCode('customer', 'sync_update_msg');

		$mbizAttrs['sync_prd_create'] = $eavAttribute->getIdByCode('catalog_product', 'sync_prd_create');
		$mbizAttrs['prd_sync_status'] = $eavAttribute->getIdByCode('catalog_product', 'sync_status');
		$mbizAttrs['pos_product_status'] = $eavAttribute->getIdByCode('catalog_product', 'pos_product_status');
		$mbizAttrs['prd_sync_update_msg'] = $eavAttribute->getIdByCode('catalog_product', 'sync_update_msg');
		$mbizAttrs['store_price'] = $eavAttribute->getIdByCode('catalog_product', 'store_price');
		
		$mbizAttrs['sync_cat_create'] = $eavAttribute->getIdByCode('catalog_category', 'sync_cat_create');	
		$status=1;
        Mage::log("came to perform attribute operations",null,'attrdel.log');
        Mage::log($mbizAttrs,null,'attrdel.log');
		if(!empty($mbizAttrs))
		{
			foreach($mbizAttrs as $attrId)
			{
				if($attrId!='') {
					try {				
						$attrModel = Mage::getModel('eav/entity_attribute')->load($attrId)->delete();
					}
					catch(Exception $e){
						$status=0;
					}
				}
				
			}
		}
		return $status;
	}

	public function performFileOperations()
	{
		$status=1;
		$basePath = Mage::getBaseDir();
			
		$arrUninstall = array();
		$arrUninstall['global_conn_config']	 = Mage::getBaseDir('etc').'/modules/Microbiz_Connector.xml';
		$arrUninstall['global_confl_config']	 = Mage::getBaseDir('etc').'/modules/Microbiz_ModulesConflictDetector.xml';
		$arrUninstall['local_conn_files'] = Mage::getBaseDir('code').'/local/Microbiz/Connector';
		$arrUninstall['local_confl_files'] = Mage::getBaseDir('code').'/local/Microbiz/ModulesConflictDetector';
		$arrUninstall['admin_layout'] = Mage::getBaseDir('design').'/adminhtml/default/default/layout/connector.xml';
		$arrUninstall['admin_conn_template'] = Mage::getBaseDir('design').'/adminhtml/default/default/template/connector';
		$arrUninstall['admin_confl_template'] = Mage::getBaseDir('design').'/adminhtml/default/default/template/microbizModulesConflictDetector';
		$arrUninstall['frontend_layout'] = Mage::getBaseDir('design').'/frontend/base/default/layout/connector.xml';
		$arrUninstall['frontend_template'] = Mage::getBaseDir('design').'/frontend/base/default/template/connector';
		$arrUninstall['js_files'] = Mage::getBaseDir('base').'/js/jquerylib';
		
		if(!empty($arrUninstall)) {
			foreach($arrUninstall as $uninstallData)
			{
				try {
					if(file_exists($uninstallData))
					{
						if(is_dir($uninstallData))
						{
							$response = Mage::helper('microbiz_core')->rrmdir($uninstallData);			
						}
						else {
							unlink($uninstallData);
						}
							
					}
				}
				catch(Exception $e)
				{
					$status=0;
				}				
					
				
			}

		}
		return $status;
	}
	public function performConfigOperations()
	{
		$resource = Mage::getSingleton('core/resource');
		$read = $resource->getConnection('core_read'); 
		$read->query('DELETE FROM core_config_data WHERE path LIKE "connector/%"');
$status=1;
		/*$config = new Mage_Core_Model_Config();
		$configValues = array('connector/settings/api_server','connector/settings/instance_id','connector/settings/syncstatus','connector/settings/allownegativeinv','connector/settings/api_user','connector/settings/api_key','connector/settings/display_name','connector/frontendsettings/customer_update','connector/frontendsettings/customer_create','connector/batchsizesettings/batchsize','connector/settings/connectordebug','connector/defaultwebsite/customer','connector/defaultwebsite/product','connector/settings/allowstoreselection','connector/settings/syncorders','connector/settings/storecredit','connector/settings/giftcard','connector/settings/storecreditpayment','connector/settings/giftcardpayment','connector/settings/sellgiftcard','connector/settings/syncgiftranges','connector/settings/giftcardimage','connector/settings/giftcardtext','connector/settings/giftcardheader','connector/settings/showleft','connector/settings/showright','connector/settings/showhome','connector/magtombiz_settings/product_sync_setting','connector/magtombiz_settings/product_tax_class','connector/magtombiz_settings/root_category','connector/magtombiz_settings/attr_supp_manuf','connector/magtombiz_settings/attr_brand','connector/magtombiz_settings/attr_manuf_upc','connector/magtombiz_settings/attr_cost','connector/magtombiz_settings/customers','connector/magtombiz_settings/customer_tax_class','connector/magtombiz_settings/inventory','connector/magtombiz_settings/stock_balance_to','connector/mbiztomag_settings/product_sync_setting','connector/mbiztomag_settings/product_tax_class','connector/mbiztomag_settings/root_category','connector/mbiztomag_settings/attr_supp_manuf','connector/mbiztomag_settings/attr_brand','connector/mbiztomag_settings/attr_manuf_upc','connector/mbiztomag_settings/attr_cost','connector/mbiztomag_settings/customers','connector/mbiztomag_settings/customer_tax_class','connector/mbiztomag_settings/inventory','connector/mbiztomag_settings/stock_balance_to','connector/settings/installation','connector/mbiz/initialsync/model','connector/mbiz/initialsync/lastInsertId','connector/mbiz/initialsync/state','connector/settings/giftcardsku','connector/settings/defaultattributeset','connector/magtombiz_settings/customer_type');
		$status=1;
		if(!empty($configValues))
		{
			foreach($configValues as $configPath)
			{
				try {
					$config->deleteConfig($configPath);					
				}
				catch(Exception $e)
				{
					$status=0;
				}				

			}
		}           */
	return $status;
            
	}	
}
?>
