<?php

class TM_FireCheckout_Model_System_Config_Source_Ajax_Sections_Abstract
{
    public function toArray()
    {
        return array(
            'shipping'        => Mage::helper('checkout')->__('Shipping Address'),
            'billing'         => Mage::helper('checkout')->__('Billing Address'),
            'payment-method'  => Mage::helper('checkout')->__('Payment Method'),
            'shipping-method' => Mage::helper('checkout')->__('Shipping Method'),
            'total'           => Mage::helper('customer')->__('Order Total'),
            'cart'            => Mage::helper('firecheckout')->__('Cart contents (qty, weight)'),
            'coupon'          => Mage::helper('salesrule')->__('Coupon Code')
        );
    }

    public function toOptionArray()
    {
        $result = array();
        foreach ($this->toArray() as $value => $label) {
            $result[] = array(
                'label' => $label,
                'value' => $value
            );
        }
        return $result;
    }
}
