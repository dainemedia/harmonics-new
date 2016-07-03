<?php

class TM_FireCheckout_Helper_Url extends Mage_Checkout_Helper_Url
{
    /**
     * Retrieve checkout url
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        if (Mage::helper('firecheckout')->canFireCheckout()) {
            return $this->_getUrl('firecheckout', array('_secure'=>true));
        }
        return parent::getCheckoutUrl();
    }
}
