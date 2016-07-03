<?php

class TM_FireCheckout_Model_System_Config_Source_Ajax_Sections_Review
    extends TM_FireCheckout_Model_System_Config_Source_Ajax_Sections_Abstract
{
    public function toArray()
    {
        $result = parent::toArray();
        unset($result['total']);
        unset($result['cart']);
        unset($result['coupon']);
        return $result;
    }
}
