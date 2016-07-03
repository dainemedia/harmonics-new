<?php

class TM_FireCheckout_Model_System_Config_Source_AgreementOutput
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'standard', 'label' => Mage::helper('firecheckout')->__('Standard')),
            array('value' => 'minimal', 'label' => Mage::helper('firecheckout')->__('Minimal'))
        );
    }
}
