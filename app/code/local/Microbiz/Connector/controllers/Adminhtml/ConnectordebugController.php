<?php
// Version 104
class Microbiz_Connector_Adminhtml_ConnectordebugController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
	{
		$this->loadLayout()->_setActiveMenu('microbiz_connector/connectordebug')->_addBreadcrumb(Mage::helper('adminhtml')->__('Debug Information'),Mage::helper('adminhtml')->__('Debug Information'));
		return $this;
	}
    /*
     * Sync Customers Info
     * from Id and To Id
     */
    public function customerSyncAction() {
        $this->loadLayout()->_setActiveMenu('microbiz_connector/connector');
        $this->_addContent($this->getLayout()->createBlock('adminhtml/template')->setTemplate('connector/syncCustomers.phtml'));
        $this->renderLayout();
    }


    /*
     * Sync Products Info
     * from Id and To Id
     */
    public function productSyncAction() {
        $this->loadLayout()->_setActiveMenu('microbiz_connector/connector');
        $this->_addContent($this->getLayout()->createBlock('adminhtml/template')->setTemplate('connector/syncProducts.phtml'));
        $this->renderLayout();
    }

    // Grid ajax Actions Start
    /*
     * AjaxGrid Action for  Sync Header Information
     * @author KT097
    */
    public function syncHeaderGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('microbiz_connector/adminhtml_syncheader_grid')->toHtml()
        );
    }
    /*
     * AjaxGrid Action for  Sync History Information
     * @author KT097
    */
    public function syncHistoryGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('microbiz_connector/adminhtml_synchistory_grid')->toHtml()
        );
    }
    /*
     * AjaxGrid Action for  MicroBiz StoreInventory Information
     * @author KT097
    */
    public function storeinventoryGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('microbiz_connector/adminhtml_storeinventory_grid')->toHtml()
        );
    }

    /*
     * AjaxGrid Action for  MicroBiz Product Relations Information
     * @author KT097
    */
    public function mbizproductGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('microbiz_connector/adminhtml_mbizproduct_grid')->toHtml()
        );
    }

    /*
     * AjaxGrid Action for  MicroBiz Customer Relations Information
     * @author KT097
    */
    public function mbizcustomerGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('microbiz_connector/adminhtml_mbizcustomer_grid')->toHtml()
        );
    }

    /*
     * AjaxGrid Action for  MicroBiz Customer Address Relations Information
     * @author KT097
    */
    public function mbizcustomeraddrGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('microbiz_connector/adminhtml_mbizcustomeraddr_grid')->toHtml()
        );
    }
    /*
     * AjaxGrid Action for  MicroBiz AttributeSet Relations Information
     * @author KT097
    */
    public function mbizattributesetGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('microbiz_connector/adminhtml_mbizattributeset_grid')->toHtml()
        );
    }
    /*
     * AjaxGrid Action for  MicroBiz Attribute Relations Information
     * @author KT097
    */
    public function mbizattributeGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('microbiz_connector/adminhtml_mbizattribute_grid')->toHtml()
        );
    }
    /*
     * AjaxGrid Action for  MicroBiz Attribute Group Relations Information
     * @author KT097
    */
    public function mbizattributegroupGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('microbiz_connector/adminhtml_mbizattributegroup_grid')->toHtml()
        );
    }
    /*
     * AjaxGrid Action for  MicroBiz Attribute Options Relations Information
     * @author KT097
    */
    public function mbizattributeoptionGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('microbiz_connector/adminhtml_mbizattributeoption_grid')->toHtml()
        );
    }
    /*
     * AjaxGrid Action for  MicroBiz Connector Debug  Information
     * @author KT097
    */
    public function connectordebugGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('microbiz_connector/adminhtml_connectordebug_grid')->toHtml()
        );
    }
    /*
     * AjaxGrid Action for  MicroBiz Connector Catalog Inventory Information
     * @author KT097
    */
    public function connectorcataloginventoryGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('microbiz_connector/adminhtml_connectorcataloginventory_grid')->toHtml()
        );
    }
    // Grid ajax Actions End

	public function indexAction() {
		$this->loadLayout()->_setActiveMenu('microbiz_connector/connectordebug')->renderLayout();
	}
	public function productAction() {
		$this->loadLayout()->_setActiveMenu('microbiz_connector/connectordebug')->renderLayout();
	}
	public function customerAction() {
		$this->loadLayout()->_setActiveMenu('microbiz_connector/connectordebug')->renderLayout();
	}
	public function customerAddressAction() {
		$this->loadLayout()->_setActiveMenu('microbiz_connector/connectordebug')->renderLayout();
	}
	public function attributesetAction() {
		$this->loadLayout()->_setActiveMenu('microbiz_connector/connectordebug')->renderLayout();
	}

    public function attributeAction() {
        $this->loadLayout()->_setActiveMenu('microbiz_connector/connectordebug')->renderLayout();
    }
    public function attributeoptionAction() {
        $this->loadLayout()->_setActiveMenu('microbiz_connector/connectordebug')->renderLayout();
    }
    public function attributegroupAction() {
        $this->loadLayout()->_setActiveMenu('microbiz_connector/connectordebug')->renderLayout();
    }
	
	public function syncheaderAction() {
		$this->loadLayout()->_setActiveMenu('microbiz_connector/connectordebug')->renderLayout();
	}
	
	public function synchistoryAction() {
		$this->loadLayout()->_setActiveMenu('microbiz_connector/connectordebug')->renderLayout();
	}
	
	public function storeinventoryAction() {
		$this->loadLayout()->_setActiveMenu('microbiz_connector/connectordebug')->renderLayout();
	}
	
	public function connectorcataloginventoryAction() {
		$this->loadLayout()->_setActiveMenu('microbiz_connector/connectordebug')->renderLayout();
	}
	
	public function viewAction() {
		$this->loadLayout()->_setActiveMenu('microbiz_connector/connector');
		$this->_addContent($this->getLayout()->createBlock('adminhtml/template')->setTemplate('connector/syncInformation.phtml'));
		$this->renderLayout();
	}
	
	public function viewsynchistoryAction() {
		$this->loadLayout()->_setActiveMenu('microbiz_connector/connector');
		$this->_addContent($this->getLayout()->createBlock('adminhtml/template')->setTemplate('connector/syncHistoryInformation.phtml'));
		$this->renderLayout();
	}
    public function processRecordAction(){
        $this->loadLayout()->_setActiveMenu('microbiz_connector/connector');
        $id = $this->getRequest()->getParam('id');
        $headerdatacollection = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('header_id', $id)->getFirstItem()->getData();
        if(!count($headerdatacollection)) {
            $message = "Header record Not Exits with ".$id." in Sync Data Information";
            Mage::getSingleton('core/session')->addError($message);
            $this->_redirect('*/*/syncheader/');
        }
        $this->_addContent($this->getLayout()->createBlock('adminhtml/template')->setTemplate('connector/processSyncInformation.phtml'));
        $this->renderLayout();
    }

    public function massProcessAction(){

       $headerIds = $this->getRequest()->getParam('mass_sync_id');
        $apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
        $instanceId = ($apiInformation['instance_id']) ? $apiInformation['instance_id'] : 1;
        $collection = array();
        foreach($headerIds as $headerId) {
            $headerdatacollection = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('header_id', $headerId)->getFirstItem()->getData();
            $collection[$instanceId][$headerId]['HeaderDetails'] = array(
                'model' => $headerdatacollection['model_name'],
                'obj_id' => $headerdatacollection['obj_id'],
                'mbiz_obj_id' => $headerdatacollection['mbiz_obj_id'],
                'mbiz_ref_obj_id' => $headerdatacollection['mbiz_ref_obj_id'],
                'ref_obj_id' => $headerdatacollection['ref_obj_id'],
                'obj_status' => $headerdatacollection['obj_status']
            );
            if ($headerdatacollection['associated_configurable_products']) {
                $collection[$instanceId][$headerId]['HeaderDetails']['associated_configurable_products'] = unserialize($headerdatacollection['associated_configurable_products']);
            }
            if($headerdatacollection['model_name'] == 'Orders') {
                $collection[$instanceId][$headerId]['ItemDetails'] = Mage::getModel('Microbiz_Connector_Model_Api')->getOrderinformation($headerdatacollection['obj_id']);
            }
            else {
                $itemdatacollection = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $headerId)->getData();
                $modifieddata       = array();
                foreach ($itemdatacollection as $itemdata) {
                    $attribute_name                = $itemdata['attribute_name'];
                    $attribute_value               = (unserialize($itemdata['attribute_value'])) ? unserialize($itemdata['attribute_value']) : $itemdata['attribute_value'];
                    $modifieddata[$attribute_name] = $attribute_value;
                }

                $collection[$instanceId][$headerId]['ItemDetails'] = $modifieddata;
            }
        }

        $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
        $url = $url . '/index.php/api/syncDetails'; // prepare url for the rest call
        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];

        $method ='POST';
        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: '.$api_user,
            'X-MBIZPOS-PASSWORD: '.$api_key
        );

        $handle = curl_init();		//curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($collection));

       $response = curl_exec($handle);	// send curl request to microbiz

        $code = curl_getinfo($handle);

        if($code['content_type'] != 'application/json' || $code['http_code'] != 200) {
            if(count($headerIds) == 1) {
                $origData = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('header_id', $headerId)->getData();
                $origData[0]['status']         = 'Failed';
                $headerdatacollection['status']         = 'Failed';
                $origData[0]['exception_desc'] = $response;
                Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($headerId)->setData($origData[0])->save();

            }
            Mage::getSingleton('core/session')->addError($response);
        }
        else {
            Mage::getModel('Microbiz_Connector_Model_Api')->extendedmbizupdateApi($response);
            Mage::getSingleton('core/session')->addSuccess('Header Records Processed Successfully');
        }
        $this->_redirect('*/*/syncheader/');

    }

    public function clearsynchistoryAction() {
        $collection = Mage::getModel('syncheaderhistory/syncheaderhistory')->getCollection();
        /*foreach($collection as $item) {
            $itemCollection = Mage::getModel('syncheaderhistory/syncheaderhistory')->load($item->getId());
            $itemCollection->delete();
        }*/

        $collection->walk('delete');
        $itemsCollection = Mage::getModel('syncitemhistory/syncitemhistory')->getCollection();
        /*foreach($itemsCollection as $syncitem) {
            $syncitemCollection = Mage::getModel('syncitemhistory/syncitemhistory')->load($syncitem->getId());
            $syncitemCollection->delete();
        }*/

        $itemsCollection->walk('delete');
        $this->_redirect('*/*/');
        return;
    }

    public function cleardebuginfoAction() {
        $collection = Mage::getModel('connectordebug/connectordebug')->getCollection();
        /*foreach($collection as $item) {
            $itemCollection = Mage::getModel('connectordebug/connectordebug')->load($item->getId());
            $itemCollection->delete();
        }*/
        $collection->walk('delete');

        $this->_redirect('*/*/');
        return;
    }

    /**
     * @author KT174
     * @description This method is used to get the Duplicate Attribute Relation Data and Update the COrrect one with the
     * Original and send the request to microbiz to delete the Duplicate IDs.
     *
     */
    public function getDuplicateAttrOptionRelAction()
    {
        $apiServer = Mage::getStoreConfig('connector/settings/api_server');
        $apiUser = Mage::getStoreConfig('connector/settings/api_user');
        $apiKey = Mage::getStoreConfig('connector/settings/api_key');

        $url = $apiServer."/index.php/api/duplicatedAttributeOptions";
        $method = 'GET';

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: '.$apiUser,
            'X-MBIZPOS-PASSWORD: '.$apiKey
        );

        $handle = curl_init();		//curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($handle);	// send curl request to microbiz
        $response=json_decode($response,true);

        $code = curl_getinfo($handle);

        if($code['http_code'] == 200 ) {



            $DuplicateAttrOptionsData = $response;
            $successMessage = '';
            if(!empty($DuplicateAttrOptionsData)) {
                $DuplicateAttrOptionIDs = array();
                foreach($DuplicateAttrOptionsData as $DuplicateAttrOptionData)
                {
                    $isOptionExists=0;
                    $isOptionNotExists=array();
                    $x=0;
                    foreach($DuplicateAttrOptionData as $attrOptionData) {
                        $magAttrId = $attrOptionData['magento_attribute_id'];
                        $magOptionId = $attrOptionData['mag_option_id'];
                        $mbizOptionId = $attrOptionData['option_id'];
                        $sysOptionId = $attrOptionData['sys_field_option_mag_id'];
                        $attributeModel = Mage::getModel('eav/entity_attribute')->load($magAttrId);

                        if($attributeModel->getId()) {
                            $attributeOptionModel = Mage::getModel('eav/entity_attribute_source_table') ;
                            $options = $attributeOptionModel->setAttribute($attributeModel)->getAllOptions(false);

                            if(!empty($options))
                            {
                                $optionIds = array();
                                foreach($options as $option)
                                {
                                    $optionIds[] = $option['value'];
                                }

                                if(in_array($magOptionId,$optionIds,true)) {
                                    $isOptionExists = $magOptionId;
                                }
                                else {
                                    $isOptionNotExists[$x]['mag_option_id'] = $magOptionId;
                                    $isOptionNotExists[$x]['sys_option_id'] = $sysOptionId;
                                    $isOptionNotExists[$x]['mag_attr_id'] = $magAttrId;
                                    $x++;
                                }

                            }
                        }
                    }

                    if(!empty($isOptionNotExists) && $isOptionExists) {

                        foreach($isOptionNotExists as $optionNot)
                        {
                            $sysoptid = $optionNot['sys_option_id'];
                            $magoptid = $optionNot['mag_option_id'];
                            $magattrid = $optionNot['mag_attr_id'];
                            $DuplicateAttrOptionIDs[] = $sysoptid;

                            if($successMessage=='') {
                                $successMessage = $mbizOptionId;
                            }
                            else {
                                $successMessage = $successMessage.",".$mbizOptionId;
                            }

                            $table = "catalog_product_entity_int";
                            $resource = Mage::getSingleton("core/resource");
                            $tableName = $resource->getTableName($table);

                            $writeConnection = $resource->getConnection('core_write');

                            $query = "Update ".$tableName." set value=".$isOptionExists." Where attribute_id=".$magattrid." And value=".$magoptid;
                            $writeConnection->query($query);
                        }


                    }
                }

                if(!empty($DuplicateAttrOptionIDs)) {

                    $duplicateUrl = $apiServer."/index.php/api/deleteAttributeOptionsRelation";

                    $postData = array('deleted_sys_field_option_mag_id'=>$DuplicateAttrOptionIDs);

                    $postData = json_encode($postData);

                    $headers = array(
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'X-MBIZPOS-USERNAME: '.$apiUser,
                        'X-MBIZPOS-PASSWORD: '.$apiKey
                    );

                    $delAttrHandle = curl_init();		//curl request to create the product
                    curl_setopt($delAttrHandle, CURLOPT_URL, $duplicateUrl);
                    curl_setopt($delAttrHandle, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($delAttrHandle, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($delAttrHandle, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($delAttrHandle, CURLOPT_SSL_VERIFYPEER, false);

                    curl_setopt($delAttrHandle, CURLOPT_POST, true);
                    curl_setopt($delAttrHandle, CURLOPT_POSTFIELDS, $postData);

                    $delAttrResp = curl_exec($delAttrHandle);	// send curl request to microbiz
                    $delAttrResp=json_decode($delAttrResp,true);

                    $delCode = curl_getinfo($delAttrHandle);


                    if($delCode['http_code']==200) {
                        $message = $this->__('Duplicate Attribute Relations with Attribute Option Ids '.$successMessage.' are deleted and updated in Magento');
                        Mage::getSingleton('core/session')->addSuccess($message);
                    }
                    else {
                        $message = $this->__('Unable to Remove the Duplicate Attribute Relations with Attribute Option Ids '.$successMessage.' in MicroBiz');
                        Mage::getSingleton('core/session')->addError($message);
                    }
                }
                else {
                    $message = $this->__('No Duplicate Attribute Options Found');
                    Mage::getSingleton('core/session')->addSuccess($message);
                }
            }
            else {
                $message = $this->__('No Duplicate Attribute Options Found');
                Mage::getSingleton('core/session')->addSuccess($message);
            }
        }
        else if($code['http_code'] == 500) {
            $message = $code['http_code'].' - Internal Server Error'.$response['message'];
            Mage::getSingleton('core/session')->addError($message);
        }
        else if($code['http_code'] == 0) {
            $message = $code['http_code'].' - Please Check the API Server URL'.$response['message'];
            Mage::getSingleton('core/session')->addError($message);
        }
        else
        {
            $message = $code['http_code'].' - '.$response['message'];
            Mage::getSingleton('core/session')->addError($message);
        }


        $this->_redirect('*/*/');
        return;

        /*$responseData = '{"1035": [{"sys_field_option_mag_id": "958","option_id": "1035","magento_instance_id": null,"magento_attribute_set_id": null,
        "magento_attribute_id": "92","mag_option_id": "209"},
        {"sys_field_option_mag_id": "98","option_id": "103","magento_instance_id": null,"magento_attribute_set_id": null,"magento_attribute_id": "92","mag_option_id": "200"},
        {"sys_field_option_mag_id": "1101","option_id": "10","magento_instance_id": null,"magento_attribute_set_id": null,"magento_attribute_id": "92","mag_option_id": "3"}]}';*/


    }

    public function syncOrderToMicroBizAction() {
        $data = $this->getRequest()->getParams();
        $orderId =  $data['order_id'];
        $order = Mage::getModel('sales/order')->load($orderId);
        Mage::getModel('Microbiz_Connector_Model_Observer')->onOrderSave($order);
        Mage::getSingleton('core/session')->addSuccess('Order Successfully added to the MicroBiz Sync Process');
        Mage::app()->getResponse()->setRedirect(Mage::helper('adminhtml')->getUrl("adminhtml/sales_order/view", array('order_id'=>$order->getId())));
    }
}
