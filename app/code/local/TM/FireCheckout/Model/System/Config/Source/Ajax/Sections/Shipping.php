<?php

class TM_FireCheckout_Model_System_Config_Source_Ajax_Sections_Shipping
    extends TM_FireCheckout_Model_System_Config_Source_Ajax_Sections_Abstract
{
    public function toArray()
    {
        $result = parent::toArray();
        unset($result['payment-method']);
        unset($result['shipping-method']);
        unset($result['billing']);
        return $result;
    }
}
