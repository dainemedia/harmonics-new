<?php
//Version 100
class Microbiz_Connector_Model_Sales_Order extends Mage_Sales_Model_Order
{
    
	/**
	   * set custom Shipping Description
	   * overriding the function from core class
	   * @author KT097
	   * returns Custom description
	*/
	public function getShippingDescription(){
        $desc = parent::getShippingDescription();
        $pickupObject = $this->getPickupObject();
        if($pickupObject){
            $desc .= 'Pickup Store: '.$pickupObject->getStore();

        }
        return $desc;
    }
}
