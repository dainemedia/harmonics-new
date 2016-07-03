<?php

class TM_FireCheckout_Model_Observer
{
    public function addToCartComplete(Varien_Event_Observer $observer)
    {
        $generalConfig = Mage::getStoreConfig('firecheckout/general');
        if (($generalConfig['enabled'] && $generalConfig['redirect_to_checkout'])
            || $observer->getRequest()->getParam('firecheckout')) {

            $observer->getResponse()
                ->setRedirect(
                    Mage::helper('firecheckout/url')->getCheckoutUrl()
                );
            Mage::getSingleton('checkout/session')->setNoCartRedirect(true);
        }
    }

    public function addAdditionalFieldsToResponseFrontend(Varien_Event_Observer $observer)
    {
        if (Mage::getStoreConfig('payment/authorizenet_directpost/active')) {
            return Mage::getSingleton('authorizenet/directpost_observer')->addAdditionalFieldsToResponseFrontend($observer);
        }
        return $this;
    }

    /**
     * Called before captcha check
     */
    public function setCheckoutMethod($observer)
    {
        $data  = $observer->getControllerAction()->getRequest()->getPost('billing', array());
        $checkout = Mage::getSingleton('firecheckout/type_standard');
        $quote = $checkout->getQuote();
        if (isset($data['register_account']) && $data['register_account']) {
            $quote->setCheckoutMethod(TM_FireCheckout_Model_Type_Standard::METHOD_REGISTER);
        } else if ($checkout->getCustomerSession()->isLoggedIn()) {
            $quote->setCheckoutMethod(TM_FireCheckout_Model_Type_Standard::METHOD_CUSTOMER);
        } else {
            $quote->setCheckoutMethod(TM_FireCheckout_Model_Type_Standard::METHOD_GUEST);
        }
        return $this;
    }

/* See Mage_Captcha_Model_Observer for the source of the next methods */

    /**
     * Check Captcha On Forgot Password Page
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Captcha_Model_Observer
     */
    public function checkForgotpassword($observer)
    {
        if (!Mage::helper('firecheckout')->canUseCaptchaModule()) {
            return $this;
        }
        $formId = 'user_forgotpassword';
        $captchaModel = Mage::helper('captcha')->getCaptcha($formId);
        if ($captchaModel->isRequired()) {
            $controller = $observer->getControllerAction();
            if (!$captchaModel->isCorrect($this->_getCaptchaString($controller->getRequest(), $formId))) {
                $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                $result = array(
                    'success' => false,
                    'error'   => Mage::helper('captcha')->__('Incorrect CAPTCHA.'),
                    'captcha' => 'user_forgotpassword'
                );
                $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            }
        }
        return $this;
    }

    /**
     * Check Captcha On User Login Page
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Captcha_Model_Observer
     */
    public function checkUserLogin($observer)
    {
        if (!Mage::helper('firecheckout')->canUseCaptchaModule()) {
            return $this;
        }
        $formId = 'user_login';
        $captchaModel = Mage::helper('captcha')->getCaptcha($formId);
        $controller = $observer->getControllerAction();
        $loginParams = $controller->getRequest()->getPost('login');
        $login = array_key_exists('username', $loginParams) ? $loginParams['username'] : null;
        if ($captchaModel->isRequired($login)) {
            $word = $this->_getCaptchaString($controller->getRequest(), $formId);
            if (!$captchaModel->isCorrect($word)) {
                $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                Mage::getSingleton('customer/session')->setUsername($login);
                $result = array(
                    'success' => false,
                    'error'   => Mage::helper('captcha')->__('Incorrect CAPTCHA.'),
                    'captcha' => 'user_login'
                );
                $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            }
        }
        $captchaModel->logAttempt($login);
        return $this;
    }

    /**
     * Check Captcha On Checkout as Guest Page
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Captcha_Model_Observer
     */
    public function checkGuestCheckout($observer)
    {
        if (!Mage::helper('firecheckout')->canUseCaptchaModule()) {
            return $this;
        }
        $formId = 'guest_checkout';
        $captchaModel = Mage::helper('captcha')->getCaptcha($formId);
        $checkoutMethod = Mage::getSingleton('checkout/type_onepage')->getQuote()->getCheckoutMethod();
        if ($checkoutMethod == Mage_Checkout_Model_Type_Onepage::METHOD_GUEST) {
            if ($captchaModel->isRequired()) {
                $controller = $observer->getControllerAction();
                if (!$captchaModel->isCorrect($this->_getCaptchaString($controller->getRequest(), $formId))) {
                    $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                    $result = array(
                        'error'   => 1,
                        'message' => Mage::helper('captcha')->__('Incorrect CAPTCHA.'),
                        'captcha' => 'guest_checkout'
                    );
                    $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                }
            }
        }
        return $this;
    }

    /**
     * Check Captcha On Checkout Register Page
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Captcha_Model_Observer
     */
    public function checkRegisterCheckout($observer)
    {
        if (!Mage::helper('firecheckout')->canUseCaptchaModule()) {
            return $this;
        }
        $formId = 'register_during_checkout';
        $captchaModel = Mage::helper('captcha')->getCaptcha($formId);
        $checkoutMethod = Mage::getSingleton('checkout/type_onepage')->getQuote()->getCheckoutMethod();
        if ($checkoutMethod == Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER) {
            if ($captchaModel->isRequired()) {
                $controller = $observer->getControllerAction();
                if (!$captchaModel->isCorrect($this->_getCaptchaString($controller->getRequest(), $formId))) {
                    $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                    $result = array(
                        'error'   => 1,
                        'message' => Mage::helper('captcha')->__('Incorrect CAPTCHA.'),
                        'captcha' => 'register_during_checkout'
                    );
                    $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                }
            }
        }
        return $this;
    }

    /**
     * Get Captcha String
     *
     * @param Varien_Object $request
     * @param string $formId
     * @return string
     */
    protected function _getCaptchaString($request, $formId)
    {
        $captchaParams = $request->getPost(Mage_Captcha_Helper_Data::INPUT_NAME_FIELD_VALUE);
        return $captchaParams[$formId];
    }

    /**
     * Saves customer comment and delivery date to quote
     */
    public function adminhtmlAddAdditionalFields($observer)
    {
        $quote   = $observer->getOrderCreateModel()->getQuote();
        $request = $observer->getRequest();

        if (isset($request['firecheckout_customer_comment'])) {
            $quote->setFirecheckoutCustomerComment($request['firecheckout_customer_comment']);
        }

        if (!isset($request['delivery_date']) || !is_array($request['delivery_date'])) {
            return;
        }

        $firecheckout = Mage::getModel('firecheckout/type_standard')->setQuote($quote);
        $result = $firecheckout->saveDeliveryDate($request['delivery_date'], false);

        if (is_array($result)) {
            throw new Exception($result['message']);
        }
    }

    public function validateAddressInformation($observer)
    {
        if (!Mage::getStoreConfigFlag('firecheckout/address_verification/enabled')) {
            return $this;
        }

        $controller       = $observer->getControllerAction();
        $request          = $controller->getRequest();
        $skipVerification = $request->getQuery('skip-address-verification');
        $block            = $controller->getLayout()
            ->createBlock('firecheckout/address_validator')
            ->setValidator(Mage::getModel('firecheckout/address_validator_usps'));

        $billing = $request->getPost('billing', array());
        if (!$request->getPost('billing_address_id')
            && !$this->_canSkipAddressVerification($billing, $skipVerification)) {

            $block->getValidator()->addAddress($billing, 'billing');
        }

        if (!isset($billing['use_for_shipping']) || !$billing['use_for_shipping']) {
            $shipping = $request->getPost('shipping', array());
            if (!$request->getPost('shipping_address_id')
                && !$this->_canSkipAddressVerification($shipping, $skipVerification)) {

                $block->getValidator()->addAddress($shipping, 'shipping');
            }
        }

        if (!$block->getValidator()->isValid()) {
            $result = array();
            $result['body']['content'] = $block->toHtml();
            $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
            $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            return;
        }

        return $this;
    }

    protected function _canSkipAddressVerification($address, $skipVerification)
    {
        if (!isset($address['country_id'])
            || 'US' != $address['country_id']
            || !isset($address['region_id'])) {

            return true;
        }

        $session = Mage::getSingleton('checkout/session');
        $key = md5(implode('_', array(
            'FIRECHECKOUT_ADDRESS_VERIFICATION_SKIP_',
            $address['street'][0],
            isset($address['street'][1]) ? $address['street'][1] : '',
            $address['city'],
            $address['region_id'],
            $address['postcode']
        )));

        if ($session->hasData($key)) { // previously marked as verified
            return true;
        }

        if ($skipVerification) {
            $session->setData($key, 1);
            return true;
        }

        return false;
    }

    public function addThirdPartyModulesLayoutUpdate($observer)
    {
        $helper  = Mage::helper('core');
        $updates = $observer->getUpdates();
        $mapping = array(
            'Aicod_Italy'                => 'tm/firecheckout/aicod_italy.xml',
            'Aitoc_Aitgiftwrap'          => 'tm/firecheckout/aitoc_aitgiftwrap.xml',
            'Amasty_Deliverydate'        => 'tm/firecheckout/amasty_deliverydate.xml',
            'AW_Advancednewsletter'      => 'tm/firecheckout/aw_newsletter.xml',
            'AW_Newsletter'              => 'tm/firecheckout/aw_newsletter.xml',
            'Billpay'                    => 'tm/firecheckout/billpay.xml',
            'Bitpay_Bitcoins'            => 'tm/firecheckout/bitpay_bitcoins.xml',
            'Bpost_ShippingManager'      => 'tm/firecheckout/bpost_shippingmanager.xml',
            'Braintree'                  => 'tm/firecheckout/braintree.xml',
            'Bysoft_Relaypoint'          => 'tm/firecheckout/bysoft_relaypoint.xml', // not confirmed module code
            'CraftyClicks'               => 'tm/firecheckout/craftyclicks.xml',
            'Customweb_PayUnityCw'       => 'tm/firecheckout/customweb_payunitycw.xml',
            'Ebizmarts_MageMonkey'       => 'tm/firecheckout/ebizmarts_magemonkey.xml',
            'Ebizmarts_SagePaySuite'     => 'tm/firecheckout/ebizmarts_sagepaysuite.xml',
            'Emja_TaxRelief'             => 'tm/firecheckout/emja_taxrelief.xml',
            'Enterprise_Enterprise'      => 'tm/firecheckout/mage_enterprise.xml',
            'GCMC_GiveChange'            => 'tm/firecheckout/gcmc_givechange.xml',
            'Geissweb_Euvatgrouper'      => 'tm/firecheckout/geissweb_euvatgrouper.xml',
            'Inchoo_SocialConnect'       => 'tm/firecheckout/inchoo_socialconnect.xml',
            'IntellectLabs_Stripe'       => 'tm/firecheckout/intellectlabs_stripe.xml',
            'IrvineSystems_Deliverydate' => 'tm/firecheckout/irvinesystems_deliverydate.xml',
            'IrvineSystems_JapanPost'    => 'tm/firecheckout/irvinesystems_japanpost.xml',
            'IrvineSystems_Sagawa'       => 'tm/firecheckout/irvinesystems_sagawa.xml',
            'IrvineSystems_Seino'        => 'tm/firecheckout/irvinesystems_seino.xml',
            'IrvineSystems_Yamato'       => 'tm/firecheckout/irvinesystems_yamato.xml',
            'IWD_OnepageCheckoutSignature' => 'tm/firecheckout/iwd_opc_signature.xml',
            'Kiala_LocateAndSelect'      => 'tm/firecheckout/kiala_locateandselect.xml',
            'Klarna_KlarnaPaymentModule' => 'tm/firecheckout/klarna_klarnapaymentmodule.xml',
            'Mage_Captcha'               => 'tm/firecheckout/mage_captcha.xml',
            'Magestore_Storepickup'      => 'tm/firecheckout/magestore_storepickup.xml',
            'MageWorx_MultiFees'         => 'tm/firecheckout/mageworx_multifees.xml',
            'Netresearch_OPS'            => 'tm/firecheckout/netresearch_ops.xml',
            'Payone_Core'                => 'tm/firecheckout/payone_core.xml',
            'Phoenix_Ipayment'           => 'tm/firecheckout/phoenix_ipayment.xml',
            'PostcodeNl_Api'             => 'tm/firecheckout/postcodenl_api.xml',
            'Rewardpoints'               => 'tm/firecheckout/rewardpoints.xml',
            'Symmetrics_Buyerprotect'    => 'tm/firecheckout/symmetrics_buyerprotect.xml',
            'Tig_MyParcel'               => 'tm/firecheckout/tig_myparcel.xml',
            'TIG_Postcode'               => 'tm/firecheckout/tig_postcode.xml',
            'TIG_PostNL'                 => 'tm/firecheckout/tig_postnl.xml',
            'Unirgy_Giftcert'            => 'tm/firecheckout/unirgy_giftcert.xml',
            'Vaimo_Klarna'               => 'tm/firecheckout/vaimo_klarna.xml',
            'Webshopapps_Desttype'       => 'tm/firecheckout/webshopapps_desttype.xml',
            'Webshopapps_Wsafreightcommon' => 'tm/firecheckout/webshopapps_wsafreightcommon.xml',
            'Webtex_Giftcards'           => 'tm/firecheckout/webtex_gitcards.xml'
        );
        foreach ($mapping as $module => $layoutXml) {
            if (!$helper->isModuleOutputEnabled($module)) {
                continue;
            }
            $tag = strtolower("firecheckout_{$module}");
            $xml = "<{$tag}><file>{$layoutXml}</file></{$tag}>";
            $node = new Varien_Simplexml_Element($xml);
            $updates->appendChild($node);
        }
    }
}
