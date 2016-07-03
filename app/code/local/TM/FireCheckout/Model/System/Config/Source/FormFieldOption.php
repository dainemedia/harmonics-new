<?php

class TM_FireCheckout_Model_System_Config_Source_FormFieldOption
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'optional', 'label' => Mage::helper('firecheckout')->__('Optional')),
            array('value' => 'required', 'label' => Mage::helper('firecheckout')->__('Required')),
            array('value' => 'hidden', 'label' => Mage::helper('firecheckout')->__('Hidden'))
        );
    }
}
