<?php

class TM_FireCheckout_Block_Cart_Sidebar extends Mage_Checkout_Block_Cart_Sidebar
{
    /**
     * Check if firecheckout is available
     *
     * @return bool
     */
    public function isPossibleOnepageCheckout()
    {
        if (!$this->helper('firecheckout')->canFireCheckout()) {
            return parent::isPossibleOnepageCheckout();
        }
        return !$this->getQuote()->getHasError();
    }
}
