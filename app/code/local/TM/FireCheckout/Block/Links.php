<?php

class TM_FireCheckout_Block_Links extends Mage_Checkout_Block_Links
{
    /**
     * Add link on checkout page to parent block
     *
     * @return TM_FireCheckout_Block_Links
     */
    public function addCheckoutLink()
    {
        if (!$this->helper('firecheckout')->canFireCheckout()) {
            return parent::addCheckoutLink();
        }
        if ($parentBlock = $this->getParentBlock()) {
            $text = $this->helper('checkout')->__('Checkout');
            $parentBlock->addLink($text, 'firecheckout', $text, true, array('_secure'=> true), 60, null, 'class="top-link-checkout"');
        }
        return $this;
    }
}