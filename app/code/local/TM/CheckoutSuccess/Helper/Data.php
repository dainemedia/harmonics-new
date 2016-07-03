<?php

class TM_CheckoutSuccess_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function isEnabled()
    {
        return Mage::getStoreConfigFlag('checkoutsuccess/general/enabled');
    }
}
