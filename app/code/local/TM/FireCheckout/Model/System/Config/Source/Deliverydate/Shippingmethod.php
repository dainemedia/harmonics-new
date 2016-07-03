<?php

class TM_FireCheckout_Model_System_Config_Source_Deliverydate_Shippingmethod
{
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label' => Mage::helper('firecheckout')->__('Show for All Shipping Methods')),
            array('value' => 1, 'label' => Mage::helper('firecheckout')->__('Show for Specific Shipping Methods'))
        );
    }
}
