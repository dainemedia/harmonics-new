<?php

class TM_FireCheckout_Block_Paypal_Hosted_Pro_Iframe extends Mage_Paypal_Block_Hosted_Pro_Iframe
{
    /**
     * Set path to template used for generating block's output.
     *
     * @param string $template
     * @return TM_FireCheckout_Block_Paypal_Iframe
     */
    public function setTemplate($template)
    {
        // @see Mage_Paypal_HostedproController::cancelAction
        if ('paypal/hss/redirect.phtml' === $template
            && Mage::helper('firecheckout')->canFireCheckout()) {

            $template = 'tm/firecheckout/paypal/hostedpro/redirect.phtml';
        }
        return parent::setTemplate($template);
    }
}
