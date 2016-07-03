<?php
//Version 101
class Microbiz_Connector_Model_Carrier_Instorepickup extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code = 'instorepickup';
	
	public function getFormBlock(){
        return 'microbiz_connector/instorepickup';
    }
	/**
	   * Collect rates for this shipping method based on information in $request
	   *
	   * @param Mage_Shipping_Model_Rate_Request $data
	   * @return Mage_Shipping_Model_Rate_Result
	*/
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        if(Mage::getSingleton('admin/session')->isLoggedIn()){
            return false;
        }
        $result = Mage::getModel('shipping/rate_result');
		/*
        if ($this->getConfigData('type') == 'O') { // per order
            //$shippingPrice = $this->getConfigData('price');
            $shippingPrice = 0;
		  // $shippingPrice = Mage::getSingleton('checkout/session')->getInStoreShipping();
        } elseif ($this->getConfigData('type') == 'I') { // per item
           // $shippingPrice = ($request->getPackageQty() * Mage::getSingleton('checkout/session')->getInStoreShipping());
			// $shippingPrice = ($request->getPackageQty() *  $this->getConfigData('price'));
            $shippingPrice = 0;
        } else {
            $shippingPrice = false;
        }*/
        $shippingPrice = 0;
        $shippingPrice = $this->getFinalPriceWithHandlingFee($shippingPrice);
        if ($shippingPrice !== false) {
            $method = Mage::getModel('shipping/rate_result_method');
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethod($this->_code);
            $method->setMethodTitle($this->getConfigData('name'));
            if ($request->getFreeShipping() === true) {
                $shippingPrice = '0.00';
            }
            $method->setPrice($shippingPrice);
            $method->setCost($shippingPrice);
            $result->append($method);
        }
        return $result;
    }
	/**
		* This method is used when viewing / listing Shipping Methods with Codes programmatically
	*/
    public function getAllowedMethods()
    {
        return array('instorepickup'=>$this->getConfigData('name'));
    }
}