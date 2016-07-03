<?php
class Microbiz_Connector_Model_Customer_Observer
{

    private $customerstatus;

    public function __construct()
    {
    }
       
    public function onBeforeSave($observer)
    {
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
        }
    }
    
    
    public function onAfterSave($observer)
    {
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
		}
    }
	public function onAfterDelete($observer)
    {
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
		
		return $this;
	}
    
    public function onCreate($object)
    {
        /*
        $customerData = $customer->getDefaultBilling();
        */
        
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

        return $this;
    }
        
    public function onUpdate($object)
    {
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

		/* if($SyncStatus) {
		$data    = json_encode($customer['object']->getData());		
		}else{
		$data    = json_encode(array('email'=>$customer['object']->getEmail(),'sync_status'=>0));
		} */

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

        return $this;
    }
	public function onFrontendBeforeSave($observer)
    {
		if(Mage::registry('customer_save_observer_executed')){
				return $this; //this method has already been executed once in this request (see comment below)
			}       
	   $object = $observer->getEvent()->getCustomer();
		
		Mage::log('Before Save');
		
         if ($object->getId()) {
            $object->setIsNewlyCreated(false);    
	    
	} //$object->getId()
        else {
            $object->setIsNewlyCreated(true);
	    }
		Mage::log($object);
		 Mage::register('customer_save_observer_executed',true); 
    }
	
	public function onFrontendAfterSave($observer)
    {
        $object = $observer->getEvent()->getCustomer();
        if ($object->getIsNewlyCreated()) {
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
      public function onFrontendCreate($object)
    {
        /*
        $customerData = $customer->getDefaultBilling();
        */
        if(Mage::getStoreConfig('connector/frontendsettings/customer_create')){
        $customer = Mage::getSingleton('customer/session');
        $apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
		$url    =$apiInformation['api_server']; // get microbiz server details fron configuration settings. 
		$api_user = $apiInformation['api_user'];
		$api_key = $apiInformation['api_key'];
		$display_name = $apiInformation['display_name'];
		
			$url    = $url.'/index.php/api/customer';
            $method = 'POST';
            
            // headers and data (this is API dependent, some uses XML)
            $headers = array(
                'Accept: application/json',
                'Content-Type: application/json',
				'X_MBIZPOS_USERNAME: '.$api_user,
				'X_MBIZPOS_PASSWORD: '.$api_key
            );
            $data    = json_encode(Mage::getModel('customer/customer')->load($customer->getId())->getData());
            Mage::log("Test");
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
            } 

		$response = curl_exec($handle);
		$code = curl_getinfo($handle);
		if($code['http_code'] == 200 ) Mage::getSingleton('core/session')->addSuccess($display_name . ': '. $response); 
		else
		Mage::getSingleton('core/session')->addError($display_name . ': '. $response);
		
		}
		return $this;
    }
	
	public function onFrontendUpdate($object)
    {
		if(Mage::getStoreConfig('connector/frontendsettings/customer_update')){
		
        $customer =  Mage::getSingleton('customer/session');
        
		$apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
		$url    =$apiInformation['api_server']; // get microbiz server details fron configuration settings. 
		$api_user = $apiInformation['api_user'];
		$api_key = $apiInformation['api_key'];
		$display_name = $apiInformation['display_name'];	
		$url    = $url.'/index.php/api/customer';
		$method = 'POST';
		
		// headers and data (this is API dependent, some uses XML)
		$headers = array(
		    'Accept: application/json',
		    'Content-Type: application/json',
			'X_MBIZPOS_USERNAME: '.$api_user,
			'X_MBIZPOS_PASSWORD: '.$api_key
		);

		$data    = json_encode(Mage::getModel('customer/customer')->load($customer->getId())->getData());
		// Mage::log("Test update");
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
		
		$response = curl_exec($handle);
		$code = curl_getinfo($handle);
		if($code['http_code'] == 200 ) Mage::getSingleton('core/session')->addSuccess($display_name . ': '. $response); 
		else
		Mage::getSingleton('core/session')->addError($display_name . ': '. $response);
		
	}
	        return $this;

    }
}
