<?php

class TM_FireCheckout_Block_Paypal_Iframe extends Mage_Paypal_Block_Iframe
{
    /**
     * Internal constructor
     * Set payment method code
     *
     */
    protected function _construct()
    {
        parent::_construct();

        $paymentCode = $this->_getCheckout()
            ->getQuote()
            ->getPayment()
            ->getMethod();
        if (in_array($paymentCode, $this->helper('paypal/hss')->getHssMethods())) {
            $this->_paymentMethodCode = $paymentCode;
            $templatePath = str_replace('_', '', $paymentCode);
            $templateFile = "tm/firecheckout/paypal/{$templatePath}/iframe.phtml";
            if (file_exists(Mage::getDesign()->getTemplateFilename($templateFile))) {
                $this->setTemplate($templateFile);
            } else {
                $this->setTemplate('tm/firecheckout/paypal/hostedpro/iframe.phtml');
            }
        }
    }
}
