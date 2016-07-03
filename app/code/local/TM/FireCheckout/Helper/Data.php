<?php

class TM_FireCheckout_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_agreements = null;

    /**
     * Get fire checkout availability
     *
     * @return bool
     */
    public function canFireCheckout()
    {
        return (bool)Mage::getStoreConfig('firecheckout/general/enabled');
    }

    public function isAllowedGuestCheckout()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->isAllowedGuestCheckout()) {
            return false;
        }
        return ('optional' == Mage::getStoreConfig('firecheckout/general/registration_mode')
            || 'optional-checked' == Mage::getStoreConfig('firecheckout/general/registration_mode'));
    }

    public function getIsSubscribed()
    {
        $customerSession = Mage::getSingleton('customer/session');
        if (!$customerSession->isLoggedIn()) {
            return false;
        }
        $ids = Mage::getResourceModel('newsletter/subscriber_collection')
            ->useOnlySubscribed()
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->addFieldToFilter('subscriber_email', $customerSession->getCustomer()->getEmail())
            ->getAllIds();

        return count($ids) > 0;
    }

    public function canShowNewsletter()
    {
        if (!Mage::getStoreConfig('firecheckout/general/newsletter_checkbox')) {
            return false;
        }

        if (!Mage::helper('core')->isModuleOutputEnabled('Mage_Newsletter')) {
            return false;
        }

        $customerSession = Mage::getSingleton('customer/session');
        if (!$customerSession->isLoggedIn()
            && !Mage::getStoreConfig('newsletter/subscription/allow_guest_subscribe')) {

            return false;
        }

        return !Mage::helper('firecheckout')->getIsSubscribed();
    }

    public function canUseCaptchaModule()
    {
        return Mage::helper('core')->isModuleOutputEnabled('Mage_Captcha');
    }

    /**
     * Retrieve the magento version converted to community edition
     */
    public function getMagentoVersion()
    {
        $version = Mage::getVersion();
        if (!Mage::getConfig()->getModuleConfig('Enterprise_Enterprise')) {
            return $version;
        }

        // $mapping = array(
            // '1.13.0.0' => '1.8.0.0',
            // '1.12.0.2' => '1.7.0.2',
            // '1.12.0.0' => '1.7.0.0',
            // '1.11.2.0' => '1.6.2.0',
            // '1.11.1.0' => '1.6.1.0',
            // '1.11.0.0' => '1.6.0.0',
            // '1.10.0.0' => '1.5.0.0'
        // );
        $info = explode('.', $version);
        $info[1] -= 5;
        $version = implode('.', $info);

        return $version;
    }

    public function canUseMageWorxMultifees()
    {
        return Mage::helper('core')->isModuleOutputEnabled('MageWorx_MultiFees');
    }

    public function canUseMageWorxCustomerCredit()
    {
        return Mage::helper('core')->isModuleOutputEnabled('MageWorx_CustomerCredit');
    }
}
