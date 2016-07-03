<?php

class TM_FireCheckout_Model_Service_Quote extends Mage_Sales_Model_Service_Quote
{
    // removed address validation
    protected function _validate()
    {
        $helper = Mage::helper('sales');
        if (!$this->getQuote()->isVirtual()) {
            $address = $this->getQuote()->getShippingAddress();
            $addressValidation = Mage::getSingleton('firecheckout/type_standard')->validateAddress($address);
            if ($addressValidation !== true) {
                Mage::throwException(
                    $helper->__('Please check shipping address information. %s', implode(' ', $addressValidation))
                );
            }
            $method = $address->getShippingMethod();
            $rate = $address->getShippingRateByCode($method);
            if (!$this->getQuote()->isVirtual() && (!$method || !$rate)) {
                Mage::throwException($helper->__('Please specify a shipping method.'));
            }
        }

        $addressValidation = Mage::getSingleton('firecheckout/type_standard')
            ->validateAddress($this->getQuote()->getBillingAddress());

        if ($addressValidation !== true) {
            Mage::throwException(
                $helper->__('Please check billing address information. %s', implode(' ', $addressValidation))
            );
        }

        if (!($this->getQuote()->getPayment()->getMethod())) {
            Mage::throwException($helper->__('Please select a valid payment method.'));
        }

        return $this;
    }
}
