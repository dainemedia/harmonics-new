<?php

class TM_FireCheckout_Model_System_Config_Source_VatNumber
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'vat_id', 'label' => Mage::helper('customer')->__('VAT Number')),
            array('value' => 'taxvat', 'label' => Mage::helper('customer')->__('Tax/VAT number'))
        );
    }
}
