<?php
//Version 103
class Microbiz_Connector_Model_Customer_Api extends Mage_Customer_Model_Customer_Api
{
    
	/**
     * Retrieve customer data based on email
     *
     * @param int $customerEmail
     * @param array $attributes
     * @return array
     */

	 public function infoByEmail($customerEmail, $attributes = null)
    {
        $customer = Mage::getModel('customer/customer');
		$customer->setWebsiteId(Mage::app()->getWebsite()->getId());
		$customer->loadByEmail($customerEmail);

        if (!$customer->getId()) {
            $this->_fault('not_exists');
        }

        if (!is_null($attributes) && !is_array($attributes)) {
            $attributes = array($attributes);
        }

        $result = array();

        foreach ($this->_mapAttributes as $attributeAlias=>$attributeCode) {
            $result[$attributeAlias] = $customer->getData($attributeCode);
        }

        foreach ($this->getAllowedAttributes($customer, $attributes) as $attributeCode=>$attribute) {
            $result[$attributeCode] = $customer->getData($attributeCode);
        }
		
        return $result;
    }
	
	
	/**
     * Retrieve customer data
     *
     * @param int $customerId
     * @param array $attributes
     * @return array
     */
    public function info($customerId, $attributes = null)
    {
        $customer = Mage::getModel('customer/customer')->load($customerId);

        if (!$customer->getId()) {
            $this->_fault('not_exists');
        }

        if (!is_null($attributes) && !is_array($attributes)) {
            $attributes = array($attributes);
        }

        $result = array();

        foreach ($this->_mapAttributes as $attributeAlias=>$attributeCode) {
            $result[$attributeAlias] = $customer->getData($attributeCode);
        }

        foreach ($this->getAllowedAttributes($customer, $attributes) as $attributeCode=>$attribute) {
            $result[$attributeCode] = $customer->getData($attributeCode);
        }

		$billingData = $customer->getDefaultBilling();
		$shippingData = $customer->getDefaultShipping();

		       if ($shippingData) {
			   $address = Mage::getModel('customer/address')->load($shippingData);
			   $result['shipping'] = $address->getData();
		       }
		      if ($billingData) {
			   $address = Mage::getModel('customer/address')->load($billingData);
			   $result['billing'] = $address->getData();
		       }
			   
			  //if (!$billingData && !$shippingData) {
        foreach ($customer->getAddresses() as $address) {
            $addressId = $address->getId();
            if($addressId)
            {
                if($addressId!=$billingData && $addressId!=$shippingData) {
                    $data = $address->toArray();
                    $adresses = $data;
                    $result['customer_addresses'][$addressId] = $adresses;
                }

            }


        }
				//$result['addresses'] = $adresses;
				//}

        /*Updating the street in the address field to street1 and stree2*/

        if(!empty($result['shipping']))
        {
            $result['shipping'] = $this->updateCustomerAddress($customerId,$result['shipping']);
        }
        if(!empty($result['billing']))
        {
            $result['billing'] = $this->updateCustomerAddress($customerId,$result['billing']);
        }
        if(!empty($result['customer_addresses']))
        {
            //$result['addresses'] = $this->updateCustomerAddress($customerId,$result['addresses']);
            $addresses = $result['customer_addresses'];
            foreach($addresses as $addressId=>$addressData) {
                $result['customer_addresses'][$addressId] = $this->updateCustomerAddress($customerId,$addressData);
            }
        }

        return $result;
    }
	/**
     * Create new customer
     *
     * @param array $customerData
     * @return int
     */
    public function create($customerData)
    {
        $customerData = $this->_prepareData($customerData);
        try {
            if($customerData['auto_generate_password']){
                $customer = Mage::getModel('customer/customer')
                    ->setData($customerData);
                $pwd_length=7;
                $customer->setPassword($customer->generatePassword($pwd_length));
                $customer->save();

                $customer->setConfirmation(null); //confirmation needed to register?
                $customer->save(); //yes, this is also needed
                // $customer->sendNewAccountEmail(); //send confirmation email to customer?
            }
            else {
                $customer = Mage::getModel('customer/customer')
                    ->setData($customerData)
                    ->save();
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        return $customer->getId();
    }
    /**
     * Create new customer
     *
     * @param array $customerData
     * @return int
     */
    public function customersCount($filters = null,$store = null,$exclude = false)
    {
        //$collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');
        $collection = Mage::getResourceModel('customer/customer_collection')
            ->addNameToSelect()
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('created_at')
            ->addAttributeToSelect('group_id')
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname');
        /** @var $apiHelper Mage_Api_Helper_Data */
        $magentoVesion = Mage::getVersion();
        if(version_compare($magentoVesion,'1.7.1') > 0){
            $apiHelper = Mage::helper('api');
            $filters = $apiHelper->parseFilters($filters, $this->_mapAttributes);
        }
        else {
            if (is_array($filters)) {
                try {
                    foreach ($filters as $field => $value) {
                        if (isset($this->_mapAttributes[$field])) {
                            $field = $this->_mapAttributes[$field];
                        }

                        $collection->addFieldToFilter($field, $value);
                    }
                } catch (Mage_Core_Exception $e) {
                    $this->_fault('filters_invalid', $e->getMessage());
                }
            }
        }

        if($exclude) {
            $existsIds = array();
            $existsCollection = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToSelect('magento_id')->getData();
            foreach($existsCollection as $relationCustomerIds) {
                $existsIds[] = $relationCustomerIds['magento_id'];
            }
        }
        try {
            foreach ($filters as $field => $value) {
                if($field == 'name') {
                    $collection->addFieldToFilter(array(
                        array('attribute' => 'firstname', 'like' => $value),
                        array('attribute' => 'lastname', 'like' => $value)
                    ));

                }
                else if($field=='entity_id')
                {
                    if($exclude) {
                        $value['in'] = array_diff($value['in'], $existsIds);
                    }
                    $collection->addFieldToFilter($field, $value);
                }
                else
                {
                    $collection->addFieldToFilter($field, $value);

                }
            }
            if($exclude) {
                $collection->addFieldToFilter('entity_id', array('nin'=>$existsIds));
            }

        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }
        $collection->load();

        //return  $collection->getSelect()->__toString();
        $collection->getSelect()->group('e.entity_id')->distinct(true);
        return $collection->count();

    }

    public function listPartial($filters = null,$store = null,$exclude = false,$pageNumber = null,$pageSize = null,$importAll = false)
    {
       // $collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');
        /** @var $apiHelper Mage_Api_Helper_Data */
        $collection = Mage::getResourceModel('customer/customer_collection')
            ->addNameToSelect()
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('created_at')
            ->addAttributeToSelect('group_id')
            ->addAttributeToSelect('firstname')
            ->addAttributeToSelect('lastname');
        $magentoVesion = Mage::getVersion();
        if(version_compare($magentoVesion,'1.7.1') > 0){
            $apiHelper = Mage::helper('api');
            $filters = $apiHelper->parseFilters($filters, $this->_mapAttributes);
        }
        else {
            if (is_array($filters)) {
                try {
                    foreach ($filters as $field => $value) {
                        if (isset($this->_mapAttributes[$field])) {
                            $field = $this->_mapAttributes[$field];
                        }

                        $collection->addFieldToFilter($field, $value);
                    }
                } catch (Mage_Core_Exception $e) {
                    $this->_fault('filters_invalid', $e->getMessage());
                }
            }
        }
        if($exclude) {
            $existsIds = array();
            $existsCollection = Mage::getModel('mbizcustomer/mbizcustomer')->getCollection()->addFieldToSelect('magento_id')->getData();
            foreach($existsCollection as $relationCustomerIds) {
                $existsIds[] = $relationCustomerIds['magento_id'];
            }
        }
        try {
            foreach ($filters as $field => $value) {
                if($field == 'name') {
                    $collection->addFieldToFilter(array(
                        array('attribute' => 'firstname', 'like' => $value),
                        array('attribute' => 'lastname', 'like' => $value)
                    ));

                }
                else if($field=='entity_id')
                {
                    if($exclude) {
                        $value['in'] = array_diff($value['in'], $existsIds);
                    }
                    $collection->addFieldToFilter($field, $value);
                }
                else
                {
                    $collection->addFieldToFilter($field, $value);

                }
            }
            if($exclude) {
                $collection->addFieldToFilter('entity_id', array('nin'=>$existsIds));
            }

        } catch (Mage_Core_Exception $e) {
            $this->_fault('filters_invalid', $e->getMessage());
        }



        if($pageNumber) {
            $collection->getSelect()->limit($pageSize,$pageSize * ($pageNumber-1));
        }
        //return  $collection->getSelect()->__toString();
        $collection->getSelect()->group('e.entity_id')->distinct(true);
        $result = array();
        $collection->load();
        if($importAll) {
            foreach ($collection as $customer) {
                $result[] = $customer->getId();

            }
        } else {
            foreach ($collection as $customer) {
                $data = $customer->toArray();
                $row  = array();
              //  $row = $data;
                $row['customer_id'] = $data['entity_id'];
                $row['firstname'] = $data['firstname'];
                $row['lastname'] = $data['lastname'];
                $row['email'] = $data['email'];
               // $row['status'] = $data['status'];
                $result[] = $row;
            }
        }
        $totalPages = ceil($this->customersCount($filters,$store, $exclude) / $pageSize) ;
        $totalResult=array('totalPages'=>$totalPages,'customers'=>$result);
        return $totalResult;
    }

    /**
     * Retrieve customer data
     *
     * @param int $customerId
     * @param array $attributes
     * @return array
     */
    public function infoFull($customerIds, $attributes = null)
    {
        $finalResult = array();
        foreach($customerIds as $customerId) {
            try{
                $customer = Mage::getModel('customer/customer')->load($customerId);

                if (!$customer->getId()) {
                    $this->_fault('not_exists');
                }

                if (!is_null($attributes) && !is_array($attributes)) {
                    $attributes = array($attributes);
                }

                $result = array();

                foreach ($this->_mapAttributes as $attributeAlias=>$attributeCode) {
                    $result[$attributeAlias] = $customer->getData($attributeCode);
                }

                foreach ($this->getAllowedAttributes($customer, $attributes) as $attributeCode=>$attribute) {
                    $result[$attributeCode] = $customer->getData($attributeCode);
                }

                $billingData = $customer->getDefaultBilling();
                $shippingData = $customer->getDefaultShipping();

                if ($shippingData) {
                    $address = Mage::getModel('customer/address')->load($shippingData);
                    $result['shipping'] = $address->getData();
                }
                if ($billingData) {
                    $address = Mage::getModel('customer/address')->load($billingData);
                    $result['billing'] = $address->getData();
                }

                //if (!$billingData && !$shippingData) {
                foreach ($customer->getAddresses() as $address) {
                    $addressId = $address->getId();
                    if($addressId)
                    {
                        if($addressId!=$billingData && $addressId!=$shippingData) {
                            $data = $address->toArray();
                            $adresses = $data;
                            $result['customer_addresses'][$addressId] = $adresses;
                        }

                    }


                }
                //}

                /*Updating the street in the address field to street1 and stree2*/

                if(!empty($result['shipping']))
                {
                    $result['shipping'] = $this->updateCustomerAddress($customerId,$result['shipping']);
                }
                if(!empty($result['billing']))
                {
                    $result['billing'] = $this->updateCustomerAddress($customerId,$result['billing']);
                }
                if(!empty($result['customer_addresses']))
                {
                    //$result['addresses'] = $this->updateCustomerAddress($customerId,$result['addresses']);
                    $addresses = $result['customer_addresses'];
                    foreach($addresses as $addressId=>$addressData) {
                        $result['customer_addresses'][$addressId] = $this->updateCustomerAddress($customerId,$addressData);
                    }
                }
            }
            catch(Exception $e) {
                $result['Exception'] = $e->getMessage();
            }
            $finalResult[] = $result;
        }


        return $finalResult;
    }

    /**
     * @param $customerId
     * @param $customerAddress
     * @return $customerAddressData
     * @author KT174
     * @description This method is to change the street to street1 and stree2 in the passed addressdata.
     */
    public function updateCustomerAddress($customerId,$customerAddress)
    {
        $customer = Mage::getModel('customer/customer')->load($customerId);

        $addressId = $customerAddress['entity_id'];
        //Mage::log($customerId,null,'syncproduct.log');
        //Mage::log($customerAddress,null,'syncproduct.log');
        $customerAddressData = Mage::getModel('customer/address')->load($addressId)->getData();
        foreach($customer->getAddresses() as $addressdata) {
            $currentAddressId = $addressdata->getId();


            if($addressId==$currentAddressId)
            {
                $customerAddressData = Mage::getModel('customer/address')->load($addressId)->getData();
                if(array_key_exists('street',$customerAddressData))
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
        }

        return $customerAddressData;
    }

} // Class Mage_Customer_Model_Customer_Api End
