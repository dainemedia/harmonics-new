<?php

class TM_FireCheckout_Block_Paypal_Payflow_Advanced_Iframe extends Mage_Paypal_Block_Payflow_Advanced_Iframe
{
    /**
     * Set path to template used for generating block's output.
     *
     * @param string $template
     * @return TM_FireCheckout_Block_Paypal_Iframe
     */
    public function setTemplate($template)
    {
        // @see Mage_Paypal_PayflowadvancedController::cancelPaymentAction
        if ('paypal/payflowadvanced/redirect.phtml' === $template
            && Mage::helper('firecheckout')->canFireCheckout()) {

            $template = 'tm/firecheckout/' . $template;
        }
        return parent::setTemplate($template);
    }
}
