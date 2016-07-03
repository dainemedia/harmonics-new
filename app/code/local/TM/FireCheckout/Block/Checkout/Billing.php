<?php

class TM_FireCheckout_Block_Checkout_Billing extends Mage_Checkout_Block_Onepage_Billing
{
    /**
     * Added to get shipping address from quote for guests too.
     *
     * Return Sales Quote Address model
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getAddress()
    {
        if (is_null($this->_address)) {
            $this->_address = $this->getQuote()->getBillingAddress();
            if ($this->isCustomerLoggedIn()) {
                if(!$this->_address->getFirstname()) {
                    $this->_address->setFirstname($this->getQuote()->getCustomer()->getFirstname());
                }
                if(!$this->_address->getLastname()) {
                    $this->_address->setLastname($this->getQuote()->getCustomer()->getLastname());
                }
            }
        }

        return $this->_address;
    }

    public function getRegisterAccount()
    {
        return $this->getAddress()->getRegisterAccount()
            || $this->getRequest()->has('register');
    }
}
