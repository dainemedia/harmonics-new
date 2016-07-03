<?php
//Version 107
class Microbiz_Connector_Model_Customer_Observer
{

    private $customerstatus;

    public function __construct()
    {
    }
       
    public function onBeforeSave($observer)
    {
        /*Mage::log("came to onBeforeSave event",null,'customersync.log');
        $object = $observer->getEvent()->getCustomer();
		$user = Mage::getSingleton('admin/session')->getUser()->getUsername();
		$date = date("d/m/Y", Mage::getModel('core/date')->timestamp(time()));

        if ($object->getId()) {
            $object->setIsNewlyCreated(false);
			$object->setSyncUpdateMsg("Modified in Magento by {$user} on {$date}");
		} //$object->getId()
        else {
            $object->setIsNewlyCreated(true);
            $object->setSyncUpdateMsg("Created in Magento by {$user} on {$date}");
        }*/
    }
    
    
    public function onAfterSave($observer)
    {
        /*Mage::log("came to onAfterSave event",null,'customersync.log');
        $object = $observer->getEvent()->getCustomer();
	
		//  generate events for customer create/update.
		$user = Mage::getSingleton('admin/session')->getUser();

		if($user)
		{ 
        if ($object->getIsNewlyCreated()) {
            Mage::dispatchEvent('on_create', array(
                'object' => $object
            ));
        } 	//$object->getIsNewlyCreated()
        else {
		
			// $status = $object->getPosCusStatus();	
            Mage::dispatchEvent('on_update', array(
                'object' => $object
            ));
        }
		}*/
    }
	public function onAfterDelete($observer)
    {
        /*Mage::log("came to onAfterDelete event",null,'customersync.log');
        $object = $observer->getEvent()->getCustomer();
		$customer = $object->getData();
        
		$customerid = $object->getId();
		$apiInformation	=	Mage::helper('microbiz_connector')->getApiDetails();
		$url    =	$apiInformation['api_server']; // get microbiz server details fron configuration settings. 
		$api_user = $apiInformation['api_user'];
		$api_key = $apiInformation['api_key'];
		$display_name = $apiInformation['display_name'];

        $url    = $url.'/index.php/api/customer/'.$customerid;
        $method = 'DELETE';
            
        // headers and data (this is API dependent, some uses XML)
		
        $headers = array(
                'Accept: application/json',
                'Content-Type: application/json',
				'X_MBIZPOS_USERNAME: '.$api_user,
				'X_MBIZPOS_PASSWORD: '.$api_key
        );
		
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
            } //$method
        
        $response = curl_exec($handle);	// send curl request to microbiz 
        $code = curl_getinfo($handle);

		if($code['http_code'] == 200 ) Mage::getSingleton('core/session')->addSuccess($display_name . ': '. $response); 
		else if($code['http_code'] == 100)
		return $this;
		else
		Mage::getSingleton('core/session')->addError($display_name . ': '. $response);
		
		return $this;*/
	}
    
    public function onCreate($object)
    {
        /*

        Mage::log("came to onCreate event",null,'customersync.log');
        $customer = $object->getData();
        
        $customerSync = $customer['object']->getSyncCusCreate();
		$apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
		$url    =$apiInformation['api_server']; // get microbiz server details fron configuration settings. 
		$api_user = $apiInformation['api_user'];
		$api_key = $apiInformation['api_key'];
		$display_name = $apiInformation['display_name'];
        if ($customerSync) {
            $url    = $url.'/index.php/api/customer';
            $method = 'POST';
            
            // headers and data (this is API dependent, some uses XML)
            $headers = array(
                'Accept: application/json',
                'Content-Type: application/json',
				'X_MBIZPOS_USERNAME: '.$api_user,
				'X_MBIZPOS_PASSWORD: '.$api_key
            );

	    $result = $customer['object']->getData();
		
				$billingData = $customer['object']->getDefaultBilling();
				$shippingData = $customer['object']->getDefaultShipping();
				      if ($shippingData) {
					   $address = Mage::getModel('customer/address')->load($shippingData);
					   $result['shipping'] = $address->getData();
				       }
				      if ($billingData) {
					   $address = Mage::getModel('customer/address')->load($billingData);
					   $result['billing'] = $address->getData();
				       }
					   
					   if (!$billingData && !$shippingData) {
						// $result['addresses'] = $customer['object']->getAddresses();
				
						foreach ($customer['object']->getAddresses() as $address) {
							$data = $address->toArray();
							$adresses = $data;
							break;
						}
						$result['addresses'] = $adresses;
						}

            // $data    = json_encode($result);	
            
			// Mage::log($data);
            
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
            } //$method
            
            	$response = curl_exec($handle);	// send curl request to microbiz 
                $code = curl_getinfo($handle);

		if($code['http_code'] == 200 ) Mage::getSingleton('core/session')->addSuccess($display_name . ': '. $response); 
		else
		Mage::getSingleton('core/session')->addError($display_name . ': '. $response);

            // $code = curl_getinfo($handle);

			// print_r($response);	die('OnUpdate2');
        } //$customerSync

        return $this;*/
    }
        
    public function onUpdate($object)
    {
        /*Mage::log("came to onUpdate event",null,'customersync.log');
        $customer = $object->getData();
        
        $SyncStatus = $customer['object']->getSyncStatus();
		$Syncustomer = $customer['object']->getSyncCusCreate();
		
		$apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
		$url    =$apiInformation['api_server']; // get microbiz server details fron configuration settings. 
		$api_user = $apiInformation['api_user'];
		$api_key = $apiInformation['api_key'];
		$display_name = $apiInformation['display_name'];        
		if ($Syncustomer && $SyncStatus) 
		{		
		$url    = $url.'/index.php/api/customer';
		$method = 'POST';
		
		// headers and data (this is API dependent, some uses XML)
		$headers = array(
		    'Accept: application/json',
		    'Content-Type: application/json',
			'X_MBIZPOS_USERNAME: '.$api_user,
			'X_MBIZPOS_PASSWORD: '.$api_key
		);



		$result = $customer['object']->getData();

		foreach ($customer['object']->getAddresses() as $address) {
            // $data = $address->toArray();
            // $row  = array();
            // $result[] = $row;
        }
		
		$billingData = $customer['object']->getDefaultBilling();
		$shippingData = $customer['object']->getDefaultShipping();
		
		if ($shippingData) {
			   $address = Mage::getModel('customer/address')->load($shippingData);
			   $result['shipping'] = $address->getData();
		       }
			   
		if ($billingData) {
			   $address = Mage::getModel('customer/address')->load($billingData);
			   $result['billing'] = $address->getData();
		       }

		if (!$billingData && !$shippingData) {
		// $result['addresses'] = $customer['object']->getAddresses();
				
				foreach ($customer['object']->getAddresses() as $address) {
					$data = $address->toArray();
					$adresses = $data;
					break;
				}
				$result['addresses'] = $adresses;
		}
		
		$data    = json_encode($result);

		Mage::log($data);

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
		} //$method
		
		$response = curl_exec($handle);
	
		$code = curl_getinfo($handle);

		if($code['http_code'] == 200 ) Mage::getSingleton('core/session')->addSuccess($display_name . ': '. $response); 
		else
		Mage::getSingleton('core/session')->addError($display_name . ': '. $response);
	}

        return $this;*/
    }
	public function onFrontendBeforeSave($observer)
    {

        $user='';
        /*if(Mage::getSingleton('api/session'))
        {
            $userApi = Mage::getSingleton('api/session');
            //$user = $userApi->getUser()->getUsername();
        }*/
        $apiData = Mage::getSingleton('api/session')->getData();

        if(count($apiData)==0) {
            Mage::log("came to onFrontendBeforeSave event",null,'customersync.log');
            if(Mage::registry('customer_save_observer_executed')){
                return $this; //this method has already been executed once in this request (see comment below)
            }
            $object = $observer->getEvent()->getCustomer();

            Mage::log('Before Save');
            Mage::unregister('customer_new_order');
            if ($object->getId()) {
                $object->setIsNewlyCreated(false);

            } //$object->getId()
            else {
                $object->setIsNewlyCreated(true);
                Mage::register('customer_new_order',true);
            }
            //Mage::log($object);
            Mage::register('customer_save_observer_executed',true);
        }

    }
	
	public function onFrontendAfterSave($observer)
    {

        $user='';
        /*if(Mage::getSingleton('api/session'))
        {
            $userApi = Mage::getSingleton('api/session');
            $user = $userApi->getUser()->getUsername();
        }*/
        $apiData = Mage::getSingleton('api/session')->getData();

        if(count($apiData)==0)  {
            Mage::log("came to onFrontendAfterSave event",null,'customersync.log');
            $object = $observer->getEvent()->getCustomer();
            if ($object->getIsNewlyCreated() || Mage::registry('customer_new_order')) {
                Mage::dispatchEvent('onfrontend_create', array(
                    'object' => $object
                ));
            } //$object->getIsNewlyCreated()
            else {
                Mage::dispatchEvent('onfrontend_update', array(
                    'object' => $object
                ));
            }
        }

    }
      public function onFrontendCreate($object)
    {
        Mage::log("came to onFrontendCreate event",null,'customersync.log');
        /*
        $customerData = $customer->getDefaultBilling();
        */
        if(Mage::getStoreConfig('connector/frontendsettings/customer_create') && Mage::getStoreConfig('connector/settings/syncstatus')){
        $customer = Mage::getSingleton('customer/session');
        $customerinfo=$object->getEvent()->getObject()->getData();

            //$customerinfo['sync_status']=1;
		$update_items=$customerinfo;
		if(count($update_items)) {
				$relationcustomerdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter('magento_id', $customerinfo['entity_id'])
									 ->setOrder('id','asc')->getData();
            $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('obj_id', $customerinfo['entity_id'])->addFieldToFilter('model_name', 'Customer')->addFieldToFilter('status', 'Pending')->setOrder('header_id','desc')->getData();
            if($isObjectExists) {
                Mage::log("came to object exists event",null,'customersync.log');
                Mage::log($isObjectExists,null,'customersync.log');

                $header_id=$isObjectExists[0]['header_id'];
                $origitemsData = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->getData();
                foreach($origitemsData as $origitemData) {
                    $itemid=$origitemData['id'];
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

            }
            else {
                Mage::log("came to object exists event",null,'customersync.log');
                //$user = Mage::getSingleton('customer/session')->getCustomer()->getFirstname();
                $user = $customerinfo['firstname'];
                Mage::log($customerinfo['firstname'],null,'customersync.log');

					$date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
					$customerData['model_name']='Customer';
					$customerData['instance_id']=Mage::helper('microbiz_connector')->getAppInstanceId();
					$customerData['obj_id']=$customerinfo['entity_id'];
					$customerData['obj_status']=1;
					$customerData['mbiz_obj_id']=$relationcustomerdata[0]['mbiz_id'];
					$customerData['created_by']=$user;
					$customerData['created_time']= $date;
					$model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
							->setData($customerData)
							->save();
					$header_id=$model['header_id'];
        }
				
				}
				
				foreach($update_items as $key=>$updateditem) {
					if(is_array($updateditem)) {
					}
					else {
					$attribute_details = Mage::getSingleton("eav/config")->getAttribute('customer', $key);
						$attributeid=$attribute_details['attribute_id'];
						$customerinfoData['header_id']=$header_id;
						$customerinfoData['attribute_id']=$attributeid;
						$customerinfoData['attribute_name']=$key;
						$customerinfoData['attribute_value']= $updateditem;
						$customerinfoData['created_by']=$user;
						$customerinfoData['created_time']= $date;
						$model = Mage::getModel('syncitems/syncitems')
							->setData($customerinfoData)
							->save();
					}
				}

            /*Saving the Newly Created Customer Address into the Sync Tables Starts Here*/
            $customerId = $customerinfo['entity_id'];

            $customer = Mage::getModel('customer/customer')->load($customerId);

            $isDefaultBilling = ($customer->getDefaultBillingAddress()) ? $customer->getDefaultBillingAddress()->getData('entity_id') : null;
            $isDefaultShipping = ($customer->getDefaultShippingAddress()) ? $customer->getDefaultShippingAddress()->getData('entity_id'): null;

            foreach($customer->getAddresses() as $key=>$addressdata) {
                $addressId = $addressdata->getId();
                //Mage::log("customer addressId".$addressId,null,'sync.log');
                $customerAddressData = Mage::getModel('customer/address')->load($addressId)->getData();
                //Mage::log($customerAddressData,null,'sync.log');
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
                if($addressId==$isDefaultBilling)
                {
                    $customerAddressData['is_default_billing']=1;
                }
                if($addressId==$isDefaultShipping)
                {
                    $customerAddressData['is_default_shipping']=1;
                }
                //Mage::log("custome address data",null,'sync.log');
                //Mage::log($customerAddressData,null,'sync.log');

                /*Inserting into sync tables*/
                $user = Mage::getSingleton('customer/session')->getCustomer()->getFirstname();
                $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                foreach($customerAddressData as $k=>$updateditem) {

                    $customerinfoData = array();
                    $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('obj_id', $addressId)->addFieldToFilter('model_name', 'CustomerAddressMaster')->addFieldToFilter('status', 'Pending')->setOrder('header_id','desc')->getData();
                    if($isObjectExists) {
                        $headerId=$isObjectExists[0]['header_id'];
                    }
                    else {

                        $user = Mage::getSingleton('customer/session')->getCustomer()->getFirstname();
                        $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                        $customerData['model_name']='CustomerAddressMaster';
                        $customerData['instance_id']=Mage::helper('microbiz_connector')->getAppInstanceId();
                        $customerData['obj_id']=$addressId;
                        $customerData['obj_status']=1;
                        $customerData['ref_obj_id']=$customerId;
                        $customerData['mbiz_obj_id']='';
                        $customerData['created_by']=$user;
                        $customerData['created_time']= $date;
                        $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                            ->setData($customerData)
                            ->save();
                        $headerId=$model['header_id'];
                    }
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
            }



            /*Saving the Newly Created Customer Address into the Sync Tables Ends Here*/

		}
		return $this;
    }
	
	public function onFrontendUpdate($object)
    {
        Mage::log("came to onFrontendUpdate event",null,'customersync.log');
        if(Mage::getStoreConfig('connector/frontendsettings/customer_update') && Mage::getStoreConfig('connector/settings/syncstatus')){
			 $customer =  Mage::getSingleton('customer/session');
				$customerinfo=$object->getEvent()->getObject()->getData(); 
			   $customerOriginfo=$object->getEvent()->getObject()->getOrigData();
			   $update_items=array();
			   /*$relationcustomerdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter('magento_id', $customerinfo['entity_id'])
									 ->setOrder('id','asc')->getData();*/

            $relationcustomerdata = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToFilter('magento_id', $customerinfo['entity_id'])
                ->setOrder('id','asc')->getFirstItem()->getData();
            if(!count($relationcustomerdata)) {
                if(count($relationcustomerdata)) {
                    $update_items=array_diff($customerinfo, $customerOriginfo);
                } else {
                    $update_items=$customerinfo;
                }
                unset($update_items['updated_at']);
                if(count($update_items)) {

                    $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('obj_id', $customerinfo['entity_id'])->addFieldToFilter('model_name', 'Customer')->addFieldToFilter('status', 'Pending')->setOrder('header_id','desc')->getData();
                    if($isObjectExists) {
                        $header_id=$isObjectExists[0]['header_id'];
                        $origitemsData = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->getData();
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
                        }

                    }
                    else {
                        $user = Mage::getSingleton('customer/session')->getCustomer()->getFirstname();
                        $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                        $customerData['model_name']='Customer';
                        $customerData['instance_id']=Mage::helper('microbiz_connector')->getAppInstanceId();
                        $customerData['obj_id']=$customerinfo['entity_id'];
                        $customerData['obj_status']=1;
                        $customerData['mbiz_obj_id']=$relationcustomerdata[0]['mbiz_id'];
                        $customerData['created_by']=$user;
                        $customerData['created_time']= $date;
                        $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                            ->setData($customerData)
                            ->save();
                        $header_id=$model['header_id'];
                    }

                }

                foreach($update_items as $key=>$updateditem) {
                    if(is_array($updateditem)) {
                    }
                    else {
                        $attribute_details = Mage::getSingleton("eav/config")->getAttribute('customer', $key);
                        $attributeid=$attribute_details['attribute_id'];
                        $customerinfoData['header_id']=$header_id;
                        $customerinfoData['attribute_id']=$attributeid;
                        $customerinfoData['attribute_name']=$key;
                        $customerinfoData['attribute_value']= $updateditem;
                        $customerinfoData['created_by']=$user;
                        $customerinfoData['created_time']= $date;
                        $model = Mage::getModel('syncitems/syncitems')
                            ->setData($customerinfoData)
                            ->save();
                    }
                }


            }
           // else {
                /*if the customer has relation we are syncing any newly added address at the time of order place starts here..*/
                $customerId = $customerinfo['entity_id'];

                $customer = Mage::getModel('customer/customer')->load($customerId);
            $isDefaultBilling = ($customer->getDefaultBillingAddress()) ? $customer->getDefaultBillingAddress()->getData('entity_id') : null;
            $isDefaultShipping = ($customer->getDefaultShippingAddress()) ? $customer->getDefaultShippingAddress()->getData('entity_id'): null;

            foreach($customer->getAddresses() as $addressdata) {
                    $addressId = $addressdata->getId();
                    //Mage::log("customer addressId".$addressId,null,'sync.log');
                    $relationCustomerAddrData = Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->getCollection()->addFieldToFilter('magento_id', $addressId)
                        ->setOrder('id','asc')->getFirstItem()->getData();
                    if(!count($relationCustomerAddrData)) {
                        $customerAddressData = Mage::getModel('customer/address')->load($addressId)->getData();
                        //Mage::log($customerAddressData,null,'sync.log');
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
                        if($addressId==$isDefaultBilling)
                        {
                            $customerAddressData['is_default_billing']=1;
                        }
                        if($addressId==$isDefaultShipping)
                        {
                            $customerAddressData['is_default_shipping']=1;
                        }
                        //Mage::log("custome address data",null,'sync.log');
                        //Mage::log($customerAddressData,null,'sync.log');

                        /*Inserting into sync tables*/
                        $user = Mage::getSingleton('customer/session')->getCustomer()->getFirstname();
                        $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                        foreach($customerAddressData as $k=>$updateditem) {

                            $customerinfoData = array();
                            $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('obj_id', $addressId)->addFieldToFilter('model_name', 'CustomerAddressMaster')->addFieldToFilter('status', 'Pending')->setOrder('header_id','desc')->getData();
                            if($isObjectExists) {
                                $headerId=$isObjectExists[0]['header_id'];
                            }
                            else {

                                $user = Mage::getSingleton('customer/session')->getCustomer()->getFirstname();
                                $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                                $customerData['model_name']='CustomerAddressMaster';
                                $customerData['instance_id']=Mage::helper('microbiz_connector')->getAppInstanceId();
                                $customerData['obj_id']=$addressId;
                                $customerData['obj_status']=1;
                                $customerData['ref_obj_id']=$customerId;
                                $customerData['mbiz_obj_id']='';
                                $customerData['created_by']=$user;
                                $customerData['created_time']= $date;
                                $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                                    ->setData($customerData)
                                    ->save();
                                $headerId=$model['header_id'];
                            }
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

                    }
                }

                /*if the customer has relation we are syncing any newly added address at the time of order place ends here..*/
          //  }

		}
	        return $this;

    }
	
	public function onCustomerAddressSave($observer)
    {
        Mage::log("came to onCustomerAddressSave event",null,'customersync.log');
        $originfo = $observer->getEvent()->getOriginfo();
		$newinfo = $observer->getEvent()->getNewinfo(); 
		
		if(Mage::getStoreConfig('connector/frontendsettings/customer_update')){
			 $customer =  Mage::getSingleton('customer/session');
				$relationcustomerdata = Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->getCollection()->addFieldToFilter('magento_id', $newinfo['entity_id'])
										 ->setOrder('id','asc')->getData();
			      $update_items=array();
				  if(count($relationcustomerdata)) {
				   $update_items=array_diff($newinfo, $originfo);
				   }
				   else {
				    $update_items=$newinfo;
				   }
				 
			if(count($update_items)) {
					
				$isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('obj_id', $newinfo['entity_id'])->addFieldToFilter('model_name', 'CustomerAddressMaster')->addFieldToFilter('status', 'Pending')->setOrder('header_id','desc')->getData();
				if($isObjectExists) {
					$header_id=$isObjectExists[0]['header_id'];
					$origitemsData = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->getData();
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
					}
                    $addrId = $isObjectExists[0]['obj_id'];
			
				}
				else {
                        $addrId = $newinfo['entity_id'];
						$user = Mage::getSingleton('customer/session')->getCustomer()->getFirstname();
                        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
						$date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
						$customerData['model_name']='CustomerAddressMaster';
						$customerData['instance_id']=Mage::helper('microbiz_connector')->getAppInstanceId();
						$customerData['obj_id']=$newinfo['entity_id'];
						$customerData['ref_obj_id']=$customerId;
						$customerData['obj_status']=1;
						$customerData['mbiz_obj_id']=$relationcustomerdata[0]['mbiz_id'];
						$customerData['created_by']=$user;
						$customerData['created_time']= $date;
						$model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
								->setData($customerData)
								->save();
						$header_id=$model['header_id'];
					}
			}
					/*Updating the Customer Address Data and converting the street to street1 and street2 starts here*/
                        $customerId = Mage::getSingleton('customer/session')->getCustomer()->getId();
                        $customerModel = Mage::getModel('customer/customer')->load($customerId);
                        foreach($customerModel->getAddresses() as $addressdata) {
                            $addressId = $addressdata->getId();
                                $customerAddressData = Mage::getModel('customer/address')->load($addressId)->getData();
                                //Mage::log($customerAddressData,null,'sync.log');
                                if(array_key_exists('street',$customerAddressData) && $addressId==$addrId)
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
                                                $update_items[$streetId] = $street;
                                            }
                                        }
                                    }
                                    unset($update_items['street']);
                                }

                        }
					/*Updating the Customer Address Data and converting the street to street1 and street2 end here*/


					foreach($update_items as $key=>$updateditem) {
						if(is_array($updateditem)) {
						}
						else {
						$attribute_details = Mage::getSingleton("eav/config")->getAttribute('customer', $key);
							$attributeid=$attribute_details['attribute_id'];
							$customerinfoData['header_id']=$header_id;
							$customerinfoData['attribute_id']=$attributeid;
							$customerinfoData['attribute_name']=$key;
							$customerinfoData['attribute_value']= $updateditem;
							$customerinfoData['created_by']=$user;
							$customerinfoData['created_time']= $date;
							$model = Mage::getModel('syncitems/syncitems')
								->setData($customerinfoData)
								->save();
						}
					}
				//}
		}
	        return $this;

    }
	
	public function onCustomerAddressDelete($observer) 
	{
        Mage::log("came to onCustomerAddressDelete event",null,'customersync.log');
        $addressid = $observer->getEvent()->getAddressid();
		$relationcustomerdata = Mage::getModel('mbizcustomeraddr/mbizcustomeraddr')->getCollection()->addFieldToFilter('magento_id', $addressid)->setOrder('id','asc')->getData();
		$user = Mage::getSingleton('customer/session')->getFirstname();
						$date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
						$customerData['model_name']='CustomerAddressMaster';
						$customerData['instance_id']=Mage::helper('microbiz_connector')->getAppInstanceId();
						$customerData['obj_id']=$addressid;
						$customerData['obj_status']=2;
						Mage::helper('microbiz_connector')->deleteAppRelation($addressid,'CustomerAddressMaster');
						$customerData['mbiz_obj_id']=$relationcustomerdata[0]['mbiz_id'];
						$customerData['created_by']=$user;
						$customerData['created_time']= $date;
						$model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
								->setData($customerData)
								->save();
		
	}
}
