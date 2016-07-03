<?php
class IWD_Productvideo_Model_System_Config_Position
{
    public function toOptionArray()
    {
        return array(
            array('value' => '1',    'label' => Mage::helper('iwd_productvideo')->__('Left')),
            array('value' => '3',    'label' => Mage::helper('iwd_productvideo')->__('Top')),
            array('value' => '2',    'label' => Mage::helper('iwd_productvideo')->__('Right')),
            array('value' => '4',    'label' => Mage::helper('iwd_productvideo')->__('Bottom')),
        );
    }
}