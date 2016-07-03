<?php

class TM_FireCheckout_Block_Paypal_Payflow_Link_Iframe extends Mage_Paypal_Block_Payflow_Link_Iframe
{
    /**
     * Set path to template used for generating block's output.
     *
     * @param string $template
     * @return TM_FireCheckout_Block_Paypal_Iframe
     */
    public function setTemplate($template)
    {
        // @see Mage_Paypal_PayflowController::returnUrl
        if ('paypal/payflowlink/redirect.phtml' === $template
            && Mage::helper('firecheckout')->canFireCheckout()) {

            $template = 'tm/firecheckout/' . $template;
        }
        return parent::setTemplate($template);
    }
}
