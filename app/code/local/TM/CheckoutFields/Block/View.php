<?php

class TM_CheckoutFields_Block_View extends Mage_Core_Block_Template
{
    /**
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }
}
