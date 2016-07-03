<?php
class CJM_CustomStockStatus_Model_Style
{
    public function toOptionArray()
    {
        return array(
            array('value'=>1, 'label'=>Mage::helper('customstockstatus')->__('Countdown')),
            array('value'=>2, 'label'=>Mage::helper('customstockstatus')->__('Custom Text And Ship Date')),     
        );
    }

}