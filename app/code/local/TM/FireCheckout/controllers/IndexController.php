<?php

require_once 'Mage/Checkout/controllers/OnepageController.php';

class TM_FireCheckout_IndexController extends Mage_Checkout_OnepageController
{
    protected $_updateCheckoutLayout = null;

    public function getUpdateCheckoutLayout()
    {
        if (null === $this->_updateCheckoutLayout) {
            $layout = $this->getLayout();
            $update = $layout->getUpdate();
            $update->load('firecheckout_index_updatecheckout');
            $this->_initLayoutMessages('checkout/session');
            $layout->generateXml();
            $layout->generateBlocks();
            $this->_updateCheckoutLayout = $layout;
        }
        return $this->_updateCheckoutLayout;
    }

    /**
     * Get shipping method html
     *
     * @return string
     */
    protected function _getShippingMethodHtml()
    {
        return $this->getUpdateCheckoutLayout()->getBlock('checkout.shipping.method')->toHtml();
    }

    /**
     * Get payment method html
     *
     * @return string
     */
    protected function _getPaymentMethodHtml()
    {
        return $this->getUpdateCheckoutLayout()->getBlock('checkout.payment.method')->toHtml();
    }

    /**
     * Get coupon code html
     *
     * @return string
     */
    protected function _getCouponDiscountHtml()
    {
        $layout = $this->getUpdateCheckoutLayout();
        if (!$block = $layout->getBlock('checkout.coupon')) {
            $block = $layout->getBlock('checkout_cart_coupon_normal'); // @see layout/firecheckout/rewardpoints.xml
        }
        return $block ? $block->toHtml() : '';
    }

    /**
     * Get giftcard code html
     *
     * @return string
     */
    protected function _getGiftcardHtml()
    {
        return $this->getUpdateCheckoutLayout()->getBlock('checkout.giftcard')->toHtml();
    }

    /**
     * Get j2t Rewardpoints block html
     *
     * @return string
     */
    protected function _getRewardpointsHtml()
    {
        $block = $this->getUpdateCheckoutLayout()->getBlock('checkout_cart_coupon_normal');
        if (!$block) {
            return '';
        }
        return $block->toHtml();
    }

    /**
     * Get order review html
     *
     * @return string
     */
    protected function _getReviewHtml()
    {
        return $this->getUpdateCheckoutLayout()->getBlock('checkout.review')->toHtml();
    }

    /**
     * @return TM_FireCheckout_Model_Type_Standard
     */
    public function getOnepage()
    {
        return $this->getCheckout();
    }

    /**
     * @return TM_FireCheckout_Model_Type_Standard
     */
    public function getCheckout()
    {
        return Mage::getSingleton('firecheckout/type_standard');
    }

    public function indexAction()
    {
        if (!Mage::helper('firecheckout')->canFireCheckout()) {
            Mage::getSingleton('checkout/session')->addError($this->__('The fire checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }

        if (!Mage::getStoreConfig('firecheckout/mobile/enabled')
            && $this->_isMobile()) {

            $this->_redirect('checkout/onepage');
            return;
        }

        /**
         * @var Mage_Sales_Model_Quote
         */
        $quote = $this->getCheckout()->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message');
            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }
        Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
        Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_secure' => true)));

        // sage server fix
//        $sagepayModel = Mage::getModel('sagepayserver2/sagePayServer_session');
//        if ($sagepayModel) {
//            $sessId = Mage::getModel('core/session')->getSessionId();
//            $_s = $sagepayModel->loadBySessionId($sessId);
//            if ($_s->getId()) {
//                $_s->delete();
//            }
//        }
        // sage server fix

        $this->getCheckout()->applyDefaults()->initCheckout();
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('checkout/session');
        $this->getLayout()->getBlock('head')->setTitle(Mage::getStoreConfig('firecheckout/general/title'));
        $this->renderLayout();
    }

    public function saveRewardpointsAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if (!$this->getRequest()->isPost()) {
            return;
        }

        /**
         * @var Mage_Sales_Model_Quote
         */
        $quote    = $this->getCheckout()->getQuote();
        $oldTotal = $quote->getBaseGrandTotal();
        $sections = array();
        $ajaxHelper = Mage::helper('firecheckout/ajax');
        $cancelRewardPoints = $this->getRequest()->getPost('cancel_rewardpoints', false);
        if (!$cancelRewardPoints) {
            $session = Mage::getSingleton('core/session');
            $points_value = $this->getRequest()->getPost('points_to_be_used', 0);
            if (Mage::getStoreConfig('rewardpoints/default/max_point_used_order', Mage::app()->getStore()->getId())){
                if ((int)Mage::getStoreConfig('rewardpoints/default/max_point_used_order', Mage::app()->getStore()->getId()) < $points_value){
                    $points_max = (int)Mage::getStoreConfig('rewardpoints/default/max_point_used_order', Mage::app()->getStore()->getId());
                    $session->addError($this->__('You tried to use %s loyalty points, but you can use a maximum of %s points per shopping cart.', $points_value, $points_max));
                    $points_value = $points_max;
                }
            }
            $quote_id = Mage::helper('checkout/cart')->getCart()->getQuote()->getId();

            Mage::getSingleton('rewardpoints/session')->setProductChecked(0);
            Mage::getSingleton('rewardpoints/session')->setShippingChecked(0);
            Mage::helper('rewardpoints/event')->setCreditPoints($points_value);
            Mage::helper('checkout/cart')->getCart()->getQuote()
                ->setRewardpointsQuantity($points_value)
                // ->save()
                ;
        } else {
            Mage::getSingleton('rewardpoints/session')->setProductChecked(0);
            Mage::helper('rewardpoints/event')->setCreditPoints(0);
            Mage::helper('checkout/cart')->getCart()->getQuote()
                ->setRewardpointsQuantity(NULL)
                ->setRewardpointsDescription(NULL)
                ->setBaseRewardpoints(NULL)
                ->setRewardpoints(NULL)
                // ->save()
                ;
        }

        $quote->collectTotals();
        $sections[] = 'review';
        $sections[] = 'coupon-discount';

        if ($ajaxHelper->getIsShippingMethodDependsOn('total')) {
            $sections[] = 'shipping-method';
            // changing total by shipping price may affect the shipping prices theoretically
            // (free shipping may be canceled or added)
            $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();

            // Shipping methods always left in the place. Only price changes. (No max/min price rules that hides shipping methods)
            // So we don't need to apply shipping method again

            // if shipping price was changed, we need to recalculate totals again.
            // Example: SELECTED SHIPPING METHOD NOW BECOMES FREE
            // previous method added a discount and selected shipping method wasn't free
            // but removing the previous shipping method removes the discount also
            // and selected shipping method is now free
            $quote->setTotalsCollectedFlag(false)->collectTotals();
        }

        if ($ajaxHelper->getIsPaymentMethodDependsOn('total')
            || $quote->getBaseGrandTotal() <= 0 || $oldTotal <= 0) {

            $sections[] = 'payment-method';

            // if only one method is available it should be chosen automatically
            // because some discount may appear on this method
            // and user is not required to click on single payment method,
            // and we should display the updated grand total

            // total was changed [in both sides], so some payment methods
            // now can be removed or added (min/max order total configuration)
            $this->getOnepage()->applyPaymentMethod();

            if ($ajaxHelper->getIsTotalDependsOn('payment-method')) {
                // recollect totals again because adding/removing payment
                // method may add/remove some discounts in the order

                // to recollect discount rules need to clear previous discount
                // descriptions and mark address as modified
                // see _canProcessRule in Mage_SalesRule_Model_Validator
                $quote->getShippingAddress()->setDiscountDescriptionArray(array())->isObjectNew(true);

                $quote->setTotalsCollectedFlag(false)->collectTotals();
            }
        }

        $quote->save();
        $result['update_section'] = $this->_renderSections($sections);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function awpointsAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if (!$this->getRequest()->isPost()) {
            return;
        }

        /**
         * @var Mage_Sales_Model_Quote
         */
        $quote    = $this->getCheckout()->getQuote();
        $oldTotal = $quote->getBaseGrandTotal();
        $sections = array();
        $ajaxHelper = Mage::helper('firecheckout/ajax');
        $session = Mage::getSingleton('checkout/session');
        $payment = $this->getRequest()->getPost('payment');
        $session->setData('use_points', isset($payment['use_points']));
        $session->setData('points_amount', isset($payment['points_amount']) ? $payment['points_amount'] : '');

        $quote->collectTotals();
        $sections[] = 'review';
        if ($ajaxHelper->getIsShippingMethodDependsOn('total')) {
            $sections[] = 'shipping-method';
            $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();
            $quote->setTotalsCollectedFlag(false)->collectTotals();
        }

        if ($ajaxHelper->getIsPaymentMethodDependsOn('total')
            || $quote->getBaseGrandTotal() <= 0 || $oldTotal <= 0) {

            $sections[] = 'payment-method';
            $this->getOnepage()->applyPaymentMethod();

            if ($ajaxHelper->getIsTotalDependsOn('payment-method')) {
                $quote->getShippingAddress()->setDiscountDescriptionArray(array())->isObjectNew(true);
                $quote->setTotalsCollectedFlag(false)->collectTotals();
            }
        }

        $quote->save();
        $result['update_section'] = $this->_renderSections($sections);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function saveGiftcardAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if (!$this->getRequest()->isPost()) {
            return;
        }

        $quote          = $this->getOnepage()->getQuote();
        $oldTotal       = $quote->getBaseGrandTotal();
        $sections       = array();
        $ajaxHelper     = Mage::helper('firecheckout/ajax');
        $removeGiftcard = $this->getRequest()->getPost('remove_giftcard', false);
        $giftcardCode   = $this->getRequest()->getPost('giftcard_code');
        if (!$giftcardCode) {
            return;
        }

        $sections[] = 'giftcard';
        if (!$removeGiftcard) {
            try {
                Mage::getModel('enterprise_giftcardaccount/giftcardaccount')
                    ->loadByCode($giftcardCode)
                    ->addToCart();
                Mage::getSingleton('checkout/session')->addSuccess(
                    $this->__('Gift Card "%s" was added.', Mage::helper('core')->htmlEscape($giftcardCode))
                );
            } catch (Mage_Core_Exception $e) {
                Mage::dispatchEvent('enterprise_giftcardaccount_add', array('status' => 'fail', 'code' => $giftcardCode));
                Mage::getSingleton('checkout/session')->addError(
                    $e->getMessage()
                );
            } catch (Exception $e) {
                Mage::getSingleton('checkout/session')->addException($e, $this->__('Cannot apply gift card.'));
            }
        } else {
            try {
                Mage::getModel('enterprise_giftcardaccount/giftcardaccount')
                    ->loadByCode($giftcardCode)
                    ->removeFromCart();
            } catch (Mage_Core_Exception $e) {
            } catch (Exception $e) {
            }
        }

        $quote->collectTotals();
        $sections[] = 'review';

        if ($ajaxHelper->getIsShippingMethodDependsOn('total')) {
            $sections[] = 'shipping-method';
            // changing total by shipping price may affect the shipping prices theoretically
            // (free shipping may be canceled or added)
            $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();

            // Shipping methods always left in the place. Only price changes. (No max/min price rules that hides shipping methods)
            // So we don't need to apply shipping method again

            // if shipping price was changed, we need to recalculate totals again.
            // Example: SELECTED SHIPPING METHOD NOW BECOMES FREE
            // previous method added a discount and selected shipping method wasn't free
            // but removing the previous shipping method removes the discount also
            // and selected shipping method is now free
            $quote->setTotalsCollectedFlag(false)->collectTotals();
        }

        if ($ajaxHelper->getIsPaymentMethodDependsOn('total')
            || $quote->getBaseGrandTotal() <= 0 || $oldTotal <= 0) {

            $sections[] = 'payment-method';

            // if only one method is available it should be chosen automatically
            // because some discount may appear on this method
            // and user is not required to click on single payment method,
            // and we should display the updated grand total

            // total was changed [in both sides], so some payment methods
            // now can be removed or added (min/max order total configuration)
            $this->getOnepage()->applyPaymentMethod();

            if ($ajaxHelper->getIsTotalDependsOn('payment-method')) {
                // recollect totals again because adding/removing payment
                // method may add/remove some discounts in the order

                // to recollect discount rules need to clear previous discount
                // descriptions and mark address as modified
                // see _canProcessRule in Mage_SalesRule_Model_Validator
                $quote->getShippingAddress()->setDiscountDescriptionArray(array())->isObjectNew(true);

                $quote->setTotalsCollectedFlag(false)->collectTotals();
            }
        }

        $quote->save();
        $result['update_section'] = $this->_renderSections($sections);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function savePaymentDataAction()
    {
        $sections    = array();
        $paymentData = $this->getRequest()->getPost('payment', array());
        $quote       = $this->getOnepage()->getQuote();

        if ($this->getRequest()->getPost('remove_storecredit', false)) {
            if ($quote->getUseCustomerBalance()) {
                $quote->setUseCustomerBalance(false);
            }
        } elseif ($this->getRequest()->getPost('remove_rewardpoints', false)) {
            if ($quote->getUseRewardPoints()) {
                $quote->setUseRewardPoints(false);
            }
        } elseif (!empty($paymentData['use_customer_balance'])
            || !empty($paymentData['use_reward_points'])) {

            try {
                $this->getCheckout()->savePayment($paymentData);
            } catch (Exception $e) {
                // skip this message. form can be filled with invalid data at this step
            }
        }

        $quote->collectTotals()->save();
        $sections['review']         = 'review';
        $sections['payment-method'] = 'payment-method';
        $result['update_section']   = $this->_renderSections($sections);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function saveBillingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if (!$this->getRequest()->isPost()) {
            return;
        }

        $data              = $this->getRequest()->getPost('billing', array());
        $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
        $result            = $this->getOnepage()->saveBilling($data, $customerAddressId, false);

        if (isset($result['error'])) {
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }

        /**
         * @var Mage_Sales_Model_Quote
         */
        $quote           = $this->getOnepage()->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $sections        = array();
        $ajaxHelper      = Mage::helper('firecheckout/ajax');
        if (!$quote->isVirtual()
            && isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1
            && $ajaxHelper->getIsShippingMethodDependsOn('shipping')) {

            $sections['shipping-method'] = 'shipping-method';

            // recollect avaialable shipping methods
            $oldMethod = $shippingAddress->getShippingMethod();
            $shippingAddress->collectTotals()->collectShippingRates()->save();
            // apply or cancel shipping method
            $this->getOnepage()->applyShippingMethod();

            if (($ajaxHelper->getIsTotalDependsOn('shipping-method')
                    && $oldMethod != $shippingAddress->getShippingMethod())
                || $ajaxHelper->getIsTotalDependsOn('shipping')) {

                $sections['review'] = 'review';
                // shipping method may affect the total in both sides (discount on using shipping address)
                $quote->collectTotals();

                if ($ajaxHelper->getIsShippingMethodDependsOn('total')) {
                    // changing total by shipping price may affect the shipping prices theoretically
                    // (free shipping may be canceled or added)
                    $shippingAddress->setCollectShippingRates(true)->collectShippingRates();
                    // if shipping price was changed, we need to recalculate totals again.
                    // Example: SELECTED SHIPPING METHOD NOW BECOMES FREE
                    // previous method added a discount and selected shipping method wasn't free
                    // but removing the previous shipping method removes the discount also
                    // and selected shipping method is now free
                    $quote->setTotalsCollectedFlag(false)->collectTotals();
                }

                if ($ajaxHelper->getIsPaymentMethodDependsOn('total')) {
                    $sections['payment-method'] = 'payment-method';

                    // if only one method is available it should be chosen automatically
                    // because some discount may appear on this method
                    // and user is not required to click on single payment method,
                    // and we should display the updated grand total

                    // total was changed [in both sides], so some payment methods
                    // now can be removed or added (min/max order total configuration)
                    $this->getOnepage()->applyPaymentMethod();

                    if ($ajaxHelper->getIsTotalDependsOn('payment-method')) { // @todo && method is changed
                        // recollect totals again because adding/removing payment
                        // method may add/remove some discounts in the order

                        // to recollect discount rules need to clear previous discount
                        // descriptions and mark address as modified
                        // see _canProcessRule in Mage_SalesRule_Model_Validator
                        $shippingAddress->setDiscountDescriptionArray(array())->isObjectNew(true);

                        $quote->setTotalsCollectedFlag(false)->collectTotals();
                    }
                }
            }
        } elseif ($ajaxHelper->getIsTotalDependsOn('shipping')) {
            $sections['review'] = 'review';

            // shipping method may affect the total in both sides (discount on using shipping address)
            $quote->collectTotals();

            if (!$quote->isVirtual() && $ajaxHelper->getIsShippingMethodDependsOn('total')) {
                $sections[] = 'shipping-method';
                // changing total by shipping price may affect the shipping prices theoretically
                // (free shipping may be canceled or added)
                $shippingAddress->setCollectShippingRates(true)->collectShippingRates();
                // if shipping price was changed, we need to recalculate totals again.
                // Example: SELECTED SHIPPING METHOD NOW BECOMES FREE
                // previous method added a discount and selected shipping method wasn't free
                // but removing the previous shipping method removes the discount also
                // and selected shipping method is now free
                $quote->setTotalsCollectedFlag(false)->collectTotals();
            }

            if ($ajaxHelper->getIsPaymentMethodDependsOn('total')) {
                $sections[] = 'payment-method';

                // if only one method is available it should be chosen automatically
                // because some discount may appear on this method
                // and user is not required to click on single payment method,
                // and we should display the updated grand total

                // total was changed [in both sides], so some payment methods
                // now can be removed or added (min/max order total configuration)
                $this->getOnepage()->applyPaymentMethod();

                if ($ajaxHelper->getIsTotalDependsOn('payment-method')) {
                    // recollect totals again because adding/removing payment
                    // method may add/remove some discounts in the order

                    // to recollect discount rules need to clear previous discount
                    // descriptions and mark address as modified
                    // see _canProcessRule in Mage_SalesRule_Model_Validator
                    $shippingAddress->setDiscountDescriptionArray(array())->isObjectNew(true);

                    $quote->setTotalsCollectedFlag(false)->collectTotals();
                }
            }
        }

        if (!isset($sections['payment-method']) && $ajaxHelper->getIsPaymentMethodDependsOn('billing')) {
            $sections['payment-method'] = 'payment-method';

            // if only one method is available it should be chosen automatically
            // because some discount may appear on this method
            // and user is not required to click on single payment method,
            // and we should display the updated grand total

            // total was changed [in both sides], so some payment methods
            // now can be removed or added (min/max order total configuration)
            $this->getOnepage()->applyPaymentMethod();

            if ($ajaxHelper->getIsTotalDependsOn('payment-method')) { // @todo && method is changed
                $sections['review'] = 'review';

                // recollect totals again because adding/removing payment
                // method may add/remove some discounts in the order

                // to recollect discount rules need to clear previous discount
                // descriptions and mark address as modified
                // see _canProcessRule in Mage_SalesRule_Model_Validator
                $shippingAddress->setDiscountDescriptionArray(array())->isObjectNew(true);

                $quote->setTotalsCollectedFlag(false)->collectTotals();
            }
        }
        $quote->save();

        $result['update_section'] = $this->_renderSections($sections);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function saveShippingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if (!$this->getRequest()->isPost()) {
            return;
        }

        $sections          = array();
        $data              = $this->getRequest()->getPost('shipping', array());
        $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
        $result            = $this->getOnepage()->saveShipping($data, $customerAddressId, false);

        if (isset($result['error'])) {
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
        /**
         * @var Mage_Sales_Model_Quote
         */
        $quote = $this->getOnepage()->getQuote();
        $ajaxHelper = Mage::helper('firecheckout/ajax');
        if ($ajaxHelper->getIsShippingMethodDependsOn('shipping')) {
            $sections[] = 'shipping-method';

            // recollect avaialable shipping methods
            $quote->getShippingAddress()->collectTotals()->collectShippingRates()->save();
            // apply or cancel shipping method
            $this->getOnepage()->applyShippingMethod();

            if ($ajaxHelper->getIsTotalDependsOn('shipping-method') // @todo: && method was changed
                || $ajaxHelper->getIsTotalDependsOn('shipping')) {

                $sections[] = 'review';

                // shipping method may affect the total in both sides (discount on using shipping address)
                $quote->collectTotals();

                if ($ajaxHelper->getIsShippingMethodDependsOn('total')) {
//                    $sections[] = 'shipping-method';
                    // changing total by shipping price may affect the shipping prices theoretically
                    // (free shipping may be canceled or added)
                    $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();
                    // if shipping price was changed, we need to recalculate totals again.
                    // Example: SELECTED SHIPPING METHOD NOW BECOMES FREE
                    // previous method added a discount and selected shipping method wasn't free
                    // but removing the previous shipping method removes the discount also
                    // and selected shipping method is now free
                    $quote->setTotalsCollectedFlag(false)->collectTotals();
                }

                if ($ajaxHelper->getIsPaymentMethodDependsOn('total')) {
                    $sections[] = 'payment-method';

                    // if only one method is available it should be chosen automatically
                    // because some discount may appear on this method
                    // and user is not required to click on single payment method,
                    // and we should display the updated grand total

                    // total was changed [in both sides], so some payment methods
                    // now can be removed or added (min/max order total configuration)
                    $this->getOnepage()->applyPaymentMethod();

                    if ($ajaxHelper->getIsTotalDependsOn('payment-method')) { // @todo && method is changed
                        // recollect totals again because adding/removing payment
                        // method may add/remove some discounts in the order

                        // to recollect discount rules need to clear previous discount
                        // descriptions and mark address as modified
                        // see _canProcessRule in Mage_SalesRule_Model_Validator
                        $quote->getShippingAddress()->setDiscountDescriptionArray(array())->isObjectNew(true);

                        $quote->setTotalsCollectedFlag(false)->collectTotals();
                    }
                }
            }
            $quote->save();
        } else if ($ajaxHelper->getIsTotalDependsOn('shipping')) {
            $sections[] = 'review';

            // shipping method may affect the total in both sides (discount on using shipping address)
            $quote->collectTotals();

            if ($ajaxHelper->getIsShippingMethodDependsOn('total')) {
                $sections[] = 'shipping-method';
                // changing total by shipping price may affect the shipping prices theoretically
                // (free shipping may be canceled or added)
                $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();
                // if shipping price was changed, we need to recalculate totals again.
                // Example: SELECTED SHIPPING METHOD NOW BECOMES FREE
                // previous method added a discount and selected shipping method wasn't free
                // but removing the previous shipping method removes the discount also
                // and selected shipping method is now free
                $quote->setTotalsCollectedFlag(false)->collectTotals();
            }

            if ($ajaxHelper->getIsPaymentMethodDependsOn('total')) {
                $sections[] = 'payment-method';

                // if only one method is available it should be chosen automatically
                // because some discount may appear on this method
                // and user is not required to click on single payment method,
                // and we should display the updated grand total

                // total was changed [in both sides], so some payment methods
                // now can be removed or added (min/max order total configuration)
                $this->getOnepage()->applyPaymentMethod();

                if ($ajaxHelper->getIsTotalDependsOn('payment-method')) {
                    // recollect totals again because adding/removing payment
                    // method may add/remove some discounts in the order

                    // to recollect discount rules need to clear previous discount
                    // descriptions and mark address as modified
                    // see _canProcessRule in Mage_SalesRule_Model_Validator
                    $quote->getShippingAddress()->setDiscountDescriptionArray(array())->isObjectNew(true);

                    $quote->setTotalsCollectedFlag(false)->collectTotals();
                }
            }
            $quote->save();
        }

        $result['update_section'] = $this->_renderSections($sections);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function saveShippingMethodAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        $method = $this->getRequest()->getPost('shipping_method', false);
        if ($this->getRequest()->getPost('remove-shipping', false)) {
            $method = false;
        }
        $quote           = $this->getOnepage()->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $oldMethod       = $shippingAddress->getShippingMethod();

        $this->getCheckout()->applyShippingMethod($method);
        if (Mage::helper('firecheckout')->canUseMageWorxMultifees()) {
            $shippingAddress->save();
        }
        $newMethod = $shippingAddress->getShippingMethod();

        $sections = array();
        $ajaxHelper = Mage::helper('firecheckout/ajax');
        if ($ajaxHelper->getIsTotalDependsOn('shipping-method')) {
            $sections[] = 'review';
            /**
             * @var Mage_Sales_Model_Quote
             */
            $quote->collectTotals();

            if ($ajaxHelper->getIsShippingMethodDependsOn('total')
                || (!$newMethod && $oldMethod != $newMethod)) { // reset method fix

                $sections[] = 'shipping-method';
                // changing total by shipping price may affect the shipping prices theoretically
                // (free shipping may be canceled or added)
                $shippingAddress->setCollectShippingRates(true)->collectShippingRates();

                // Shipping methods always left in the place. Only price changes. (No max/min price rules that hides shipping methods)
                // So we don't need to apply shipping method again

                // if shipping price was changed, we need to recalculate totals again.
                // Example: SELECTED SHIPPING METHOD NOW BECOMES FREE
                // previous method added a discount and selected shipping method wasn't free
                // but removing the previous shipping method removes the discount also
                // and selected shipping method is now free
                $quote->setTotalsCollectedFlag(false)->collectTotals();
            }

            if ($ajaxHelper->getIsPaymentMethodDependsOn('total')) {
                $sections[] = 'payment-method';

                // if only one method is available it should be chosen automatically
                // because some discount may appear on this method
                // and user is not required to click on single payment method,
                // and we should display the updated grand total

                // total was changed [in both sides], so some payment methods
                // now can be removed or added (min/max order total configuration)
                $this->getOnepage()->applyPaymentMethod();

                if ($ajaxHelper->getIsTotalDependsOn('payment-method')) {
                    // recollect totals again because adding/removing payment
                    // method may add/remove some discounts in the order

                    // to recollect discount rules need to clear previous discount
                    // descriptions and mark address as modified
                    // see _canProcessRule in Mage_SalesRule_Model_Validator
                    $shippingAddress->setDiscountDescriptionArray(array())->isObjectNew(true);

                    $quote->setTotalsCollectedFlag(false)->collectTotals();
                }
            }

            $quote->save();
        }

        $result['update_section'] = $this->_renderSections($sections);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function savePaymentAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if (!$this->getRequest()->isPost()) {
            $this->_ajaxRedirectResponse();
            return;
        }

        $data = $this->getRequest()->getPost('payment', array());
        if (isset($data['remove'])) {
            $data['method'] = false;
        }
        $this->getCheckout()->applyPaymentMethod(isset($data['method']) ? $data['method'] : null);

        $sections = array();
        $ajaxHelper = Mage::helper('firecheckout/ajax');
        if ($ajaxHelper->getIsTotalDependsOn('payment-method')
            || Mage::helper('firecheckout')->canUseMageWorxCustomerCredit()) {

            $sections[] = 'review';

            if (!empty($data['use_internal_credit']) || $data['method']=='customercredit') {
                Mage::getSingleton('checkout/session')->setUseInternalCredit(true);
            } else {
                Mage::getModel('checkout/session')->setUseInternalCredit(false);
            }

            /**
             * @var Mage_Sales_Model_Quote
             */
            $quote = $this->getOnepage()->getQuote();
            $quote->collectTotals();

            if ($ajaxHelper->getIsShippingMethodDependsOn('total')) {
                $sections[] = 'shipping-method';
                // changing total by payment may affect the shipping prices theoretically
                // (free shipping may be canceled or added)
                $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();
                // if shipping price was changed, we need to recalculate totals again.
                // Example: SELECTED SHIPPING METHOD NOW BECOMES FREE
                // previous method added a discount and selected shipping method wasn't free
                // but removing the previous shipping method removes the discount also
                // and selected shipping method is now free
                $quote->setTotalsCollectedFlag(false)->collectTotals();
            }

            if ($ajaxHelper->getIsPaymentMethodDependsOn('total')) {
                $sections[] = 'payment-method';
                if (!isset($data['remove'])) { // if not canceled method
                    // total was changed [in both sides], so some payment methods
                    // now can be removed or added (min/max order total configuration)
                    $this->getOnepage()->applyPaymentMethod(isset($data['method']) ? $data['method'] : null);

                    if ($ajaxHelper->getIsTotalDependsOn('payment-method')) {
                        // recollect totals again because adding/removing payment
                        // method may add/remove some discounts in the order

                        // to recollect discount rules need to clear previous discount
                        // descriptions and mark address as modified
                        // see _canProcessRule in Mage_SalesRule_Model_Validator
                        $quote->getShippingAddress()->setDiscountDescriptionArray(array())->isObjectNew(true);

                        $quote->setTotalsCollectedFlag(false)->collectTotals();
                    }
                }
            }

            $quote->save();
        }

        $result['update_section'] = $this->_renderSections($sections);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function saveCouponAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if (!$this->getRequest()->isPost()) {
            $this->_ajaxRedirectResponse();
            return;
        }

        /**
         * @var Mage_Sales_Model_Quote
         */
        $quote         = $this->getCheckout()->getQuote();
        $oldTotal      = $quote->getBaseGrandTotal();
        $sections      = array();
        $data          = $this->getRequest()->getPost('coupon', array());
        $couponChanged = false;

        if ($this->getRequest()->getPost('amcoupon_code_cancel', false)) {
            $codeToCancel = $this->getRequest()->getParam('amcoupon_code_cancel');
            $appliedCoupons = $quote->getAppliedCoupons();
            $session = Mage::getSingleton('checkout/session');
            foreach ($appliedCoupons as $i => $coupon) {
                if ($coupon == $codeToCancel) {
                    unset($appliedCoupons[$i]);
                    try {
                        if ($quote->setCouponCode($appliedCoupons)->save()) {
                            $session->addSuccess($this->__('Coupon code %s was canceled.', $codeToCancel));
                        }
                    } catch (Mage_Core_Exception $e) {
                        $session->addError($e->getMessage());
                    } catch (Exception $e) {
                        $session->addError($this->__('Cannot canel the coupon code.'));
                    }
                }
            }
            $sections[] = 'coupon-discount';
        } elseif ($this->getRequest()->getPost('remove_ugiftcert', false)) {
            $gc  = $this->getRequest()->getPost('gc');
            $gcs = $quote->getGiftcertCode();
            if ($gc && $gcs && strpos($gcs, $gc) !== false) {
                $gcsArr = array();
                foreach (explode(',', $gcs) as $gc1) {
                    if (trim($gc1) !== $gc) {
                        $gcsArr[] = $gc1;
                    }
                }
                $quote->setGiftcertCode(join(',', $gcsArr));
                $sections[] = 'giftcard';
            }
        } elseif ($code = trim($this->getRequest()->getParam('cert_code'))) {
            $session = Mage::getSingleton('checkout/session');
            $hlp = Mage::helper('ugiftcert');
            try {
                if ($hlp->addCertificate($code, $quote)) {
                    $session->addSuccess(
                        Mage::helper('ugiftcert')->__("Gift certificate '%s' was applied to your order.", $code)
                    );
                } else {
                    $session->addError($hlp->__("'%s' is not valid certificate code.", $code));
                }
            } catch (Unirgy_Giftcert_Exception_Coupon $gce) {
                $session->addError($gce->getMessage());
            } catch (Exception $e) {
                $session->addError($hlp->__("Gift certificate '%s' could not be applied to your order.", $code));
                $session->addError($e->getMessage());
            }
            $sections[] = 'giftcard';
        } else {
            if (!empty($data['remove'])) {
                $data['code'] = '';
            }
            $oldCouponCode = $quote->getCouponCode();
            if ($oldCouponCode != $data['code']) {
                try {
                    $quote->setCouponCode(
                        strlen($data['code']) ? $data['code'] : ''
                    );
                    if ($data['code']) {
                        $couponChanged = true;
                    } else {
                        Mage::getSingleton('checkout/session')->addSuccess($this->__('Coupon code was canceled.'));
                    }
                } catch (Mage_Core_Exception $e) {
                    Mage::getSingleton('checkout/session')->addError($e->getMessage());
                } catch (Exception $e) {
                    Mage::getSingleton('checkout/session')->addError($this->__('Cannot apply the coupon code.'));
                }
                $sections[] = 'coupon-discount';
            }
        }

        // coupon may affect the total in both sides (apply or cancel)
        $quote->collectTotals(); // coupon validation is inside collectTotals method
        $sections[] = 'review';
        $ajaxHelper = Mage::helper('firecheckout/ajax');

        if ($ajaxHelper->getIsShippingMethodDependsOn(array('total', 'coupon'))) {
            $sections[] = 'shipping-method';
            // changing total by shipping price may affect the shipping prices theoretically
            // (free shipping may be canceled or added)
            $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();

            // Shipping methods always left in the place. Only price changes. (No max/min price rules that hides shipping methods)
            // So we don't need to apply shipping method again

            // if shipping price was changed, we need to recalculate totals again.
            // Example: SELECTED SHIPPING METHOD NOW BECOMES FREE
            // previous method added a discount and selected shipping method wasn't free
            // but removing the previous shipping method removes the discount also
            // and selected shipping method is now free
            $quote->setTotalsCollectedFlag(false)->collectTotals();
        }

        if ($ajaxHelper->getIsPaymentMethodDependsOn('total')
            || $quote->getBaseGrandTotal() <= 0 || $oldTotal <= 0) { // hide and show payment methods

            $sections[] = 'payment-method';

            // if only one method is available it should be chosen automatically
            // because some discount may appear on this method
            // and user is not required to click on single payment method,
            // and we should display the updated grand total

            // total was changed [in both sides], so some payment methods
            // now can be removed or added (min/max order total configuration)
            $this->getOnepage()->applyPaymentMethod();

            if ($ajaxHelper->getIsTotalDependsOn('payment-method')) {
                // recollect totals again because adding/removing payment
                // method may add/remove some discounts in the order

                // to recollect discount rules need to clear previous discount
                // descriptions and mark address as modified
                // see _canProcessRule in Mage_SalesRule_Model_Validator
                $quote->getShippingAddress()->setDiscountDescriptionArray(array())->isObjectNew(true);

                $quote->setTotalsCollectedFlag(false)->collectTotals();
            }
        }
        $quote->save();

        if ($couponChanged) {
            $couponToValidate = $quote->getCouponCode();
            if ($quote instanceof Amasty_Coupons_Model_Sales_Quote) {
                $coupons = explode(',', $couponToValidate);
                if (count($coupons)){
                    $couponToValidate = $coupons[count($coupons) - 1];
                }
            }
            if ($data['code'] == $couponToValidate) {
                Mage::getSingleton('checkout/session')->addSuccess(
                    $this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($data['code']))
                );
            } else {
                Mage::getSingleton('checkout/session')->addError(
                    $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($data['code']))
                );
            }
        }

        $result['update_section'] = $this->_renderSections($sections);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    protected function _validateUnirgyGiftCertificateConditions(Unirgy_Giftcert_Model_Cert $cert, $quote)
    {
        if($cert->getConditions()) {
            if($quote->isVirtual()) {
                $address = $quote->getBillingAddress();
            } else {
                $address = $quote->getShippingAddress();
            }
            return $cert->getConditions()->validate($address);
        }
        return false;
    }

    // Copy of the updatePost action of Magento CartController
    protected function _updateCart($cartData)
    {
        try {
            /**
             * @var Mage_Checkout_Model_Session
             */
            $session = $this->getCheckout()->getCheckout();
            if (is_array($cartData)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                foreach ($cartData as $index => $data) {
                    if (isset($data['qty'])) {
                        $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                    }
                }
                /**
                 * @var Mage_Checkout_Model_Cart
                 */
                $cart = Mage::getSingleton('checkout/cart');
                if (!$cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                    $cart->getQuote()->setCustomerId(null);
                }

                $oldItems = $cart->getItems();
                $oldCartData = array();
                foreach ($oldItems as $item) {
                    $oldCartData[$item->getId()]['qty'] = $item->getQty();
                }

                $cartData = $cart->suggestItemsQty($cartData);
                $cart->updateItems($cartData)
                    ->save();

                if (!$this->getCheckout()->getQuote()->validateMinimumAmount()) {
                    $this->getCheckout()->getQuote()->setTotalsCollectedFlag(false);

                    // $oldCartData = $cart->suggestItemsQty($oldCartData);
                    $cart->updateItems($oldCartData)
                        ->save();

                    $error = Mage::getStoreConfig('sales/minimum_order/error_message');
                    $session->addError($error);

                    $minimumAmount = Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())
                        ->toCurrency(Mage::getStoreConfig('sales/minimum_order/amount'));

                    $warning = Mage::getStoreConfig('sales/minimum_order/description')
                        ? Mage::getStoreConfig('sales/minimum_order/description')
                        : Mage::helper('checkout')->__('Minimum order amount is %s', $minimumAmount);

                    $session->addNotice($warning);
                }
            }
            // false to prevent _expireAjax to return redirect
            $session->setCartWasUpdated(false);
            return true;
        } catch (Mage_Core_Exception $e) {
            $session->addError($e->getMessage());
        } catch (Exception $e) {
            $session->addException($e, $this->__('Cannot update shopping cart.'));
            Mage::logException($e);
        }
        return false;
    }

    public function saveCartAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if (!$this->getRequest()->isPost()) {
            $this->_ajaxRedirectResponse();
            return;
        }

        /**
         * @var Mage_Sales_Model_Quote
         */
        $quote    = $this->getOnepage()->getQuote();
        $sections = array('review');
        $sections[] = 'coupon-discount';
        $ajaxHelper = Mage::helper('firecheckout/ajax');

        if (!$this->_updateCart($this->getRequest()->getParam('updated_cart'))
            || $quote->getHasError()) {

            if ($quote->getHasError()) {
                foreach ($quote->getErrors() as $message) {
                    $this->getCheckout()->getCheckout()->addError($message->getText());
                }
            }

            // if unavailable product qty was received - revert to the original values
            $this->_updateCart($this->getRequest()->getParam('updated_cart_safe'));

            $quote->collectTotals();
            $result['update_section'] = $this->_renderSections($sections);
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }

        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message');
            Mage::getSingleton('checkout/session')->addError($error);
            return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                'redirect' => Mage::getUrl('checkout/cart', array('_secure'=>true)),
                'success'  => true
            )));
        }

        if ($this->_expireAjax()) { // if all products were removed from the cart
            return;
        }

        $quote->collectTotals();
        // @todo on cart contents?
        if ($ajaxHelper->getIsShippingMethodDependsOn(array('cart', 'total'))) {
            $sections[] = 'shipping-method';
            // changing total by payment may affect the shipping prices theoretically
            // (free shipping may be canceled or added)
            $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();

            $this->getCheckout()->applyShippingMethod();
            // if shipping price was changed, we need to recalculate totals again.
            // Example: SELECTED SHIPPING METHOD NOW BECOMES FREE
            // previous method added a discount and selected shipping method wasn't free
            // but removing the previous shipping method removes the discount also
            // and selected shipping method is now free
            $quote->setTotalsCollectedFlag(false)->collectTotals();
        }

        if ($ajaxHelper->getIsPaymentMethodDependsOn(array('cart', 'total'))) {
            $sections[] = 'payment-method';
            // total was changed [in both sides], so some payment methods
            // now can be removed or added (min/max order total configuration)
            $this->getOnepage()->applyPaymentMethod();

            if ($ajaxHelper->getIsTotalDependsOn('payment-method')) {
                // recollect totals again because adding/removing payment
                // method may add/remove some discounts in the order

                // to recollect discount rules need to clear previous discount
                // descriptions and mark address as modified
                // see _canProcessRule in Mage_SalesRule_Model_Validator
                $quote->getShippingAddress()->setDiscountDescriptionArray(array())->isObjectNew(true);

                $quote->setTotalsCollectedFlag(false)->collectTotals();
            }
        }
        $quote->save();

        $result['update_section'] = $this->_renderSections($sections);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function saveOrderAction()
    {
        if (version_compare(Mage::helper('firecheckout')->getMagentoVersion(), '1.8.0.0') >= 0) {
            if (!$this->_validateFormKey()) {
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                    'success' => false,
                    'error'   => true,
                    'error_messages' => $this->__('Invalid Form Key. Please refresh the page.')
                )));
                return;
            }
        }

        if ($this->_expireAjax()) {
            return;
        }

        // sage server fix
//        $sagepayModel = Mage::getModel('sagepayserver2/sagePayServer_session');
//        if ($sagepayModel) {
//            $sessId = Mage::getModel('core/session')->getSessionId();
//            $_s = $sagepayModel->loadBySessionId($sessId);
//            if ($_s->getId()) {
//                $_s->delete();
//            }
//        }
        // sage server fix

        $result = array();
        /* @var TM_FireCheckout_Model_Type_Standard */
        $checkout = $this->getCheckout();
        /* @var Mage_Sales_Model_Quote */
        $quote = $checkout->getQuote();

        try {
            $checkout->applyShippingMethod($this->getRequest()->getPost('shipping_method', false));

            $deliveryDate = $this->getRequest()->getPost('delivery_date');
            if ($deliveryDate) {
                $result = $checkout->saveDeliveryDate($deliveryDate);
                if (is_array($result)) {
                    $result['success'] = false;
                    $result['error']   = true;
                    $result['error_messages'] = $result['message'];
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }
            }

            $quote->setFirecheckoutCustomerComment($this->getRequest()->getPost('order-comment'));

            $billing = $this->getRequest()->getPost('billing', array());
            $result = $checkout->saveBilling(
                $billing,
                $this->getRequest()->getPost('billing_address_id', false)
            );
            if ($result) {
                $result['success'] = false;
                $result['error']   = true;
                if ($result['message'] === $checkout->getCustomerEmailExistsMessage()) {
                    unset($result['message']);
                    $result['body'] = array(
                        'id'      => 'emailexists',
                        'modal'   => 1,
                        'window'  => array(
                            'triggers' => array(),
                            'destroy'  => 1,
                            'size'     => array(
                                'maxWidth' => 400
                            )
                        ),
                        'content' => $this->getLayout()->createBlock('core/template')
                            ->setTemplate('tm/firecheckout/emailexists.phtml')
                            ->toHtml()
                    );
                } else {
                    $result['error_messages'] = $result['message'];
                }

                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }

            if ((!isset($billing['use_for_shipping']) || !$billing['use_for_shipping'])
                && !$quote->isVirtual()) {

                $result = $checkout->saveShipping(
                    $this->getRequest()->getPost('shipping', array()),
                    $this->getRequest()->getPost('shipping_address_id', false)
                );
                if ($result) {
                    $result['success'] = false;
                    $result['error']   = true;
                    $result['error_messages'] = $result['message'];
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }
            }

            if ('relaypoint_relaypoint' == $this->getRequest()->getPost('shipping_method', false)) {
                $this->relaypointChangeAddress();
            } elseif ('storepickup_storepickup' == $this->getRequest()->getPost('shipping_method', false)) {
                // Magestore_Storepickup
                $storepickup = Mage::getSingleton('checkout/session')->getData('storepickup_session');
                if ($storepickup && isset($storepickup['store_id']) && $storepickup['store_id']) {
                    $this->storepickupChangeAddress();
                }
            }

            $requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
            if ($requiredAgreements) {
                $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
                $diff = array_diff($requiredAgreements, $postedAgreements);
                if ($diff) {
                    $result['success'] = false;
                    $result['error']   = true;
                    $result['error_messages'] = Mage::helper('checkout')->__('Please agree to all the terms and conditions before placing the order.');
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }
            }

            $result = $this->_savePayment();
            if ($result && !isset($result['redirect'])) {
                $result['error_messages'] = $result['error'];
            }

            $quote->collectTotals();

            if (!isset($result['error'])) {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request'=>$this->getRequest(), 'quote'=>$quote));
                if ($quote->getCheckoutMethod() == TM_FireCheckout_Model_Type_Standard::METHOD_GUEST) {
                    $this->_subscribeToNewsletter();
                } elseif ($this->getRequest()->getPost('newsletter')) {
                    $quote->getCustomer()->setIsSubscribed(1);
                }
            }

            // Sales representative integration
            if (Mage::getStoreConfig('salesrep/setup/enabled')
                && $salesRep = $this->getRequest()->getPost('getvoice')) {

                Mage::getSingleton('core/session')->setSalesrep($salesRep);
            }
            // End of Sales representative integration

            // 3D Secure
            $method = $quote->getPayment()->getMethodInstance();
            if ($method->getIsCentinelValidationEnabled()) {
                $centinel = $method->getCentinelValidator();
                if ($centinel && $centinel->shouldAuthenticate()) {
                    $layout = $this->getLayout();
                    $update = $layout->getUpdate();
                    $update->load('firecheckout_index_saveorder');
                    $this->_initLayoutMessages('checkout/session');
                    $layout->generateXml();
                    $layout->generateBlocks();
                    return $this->getResponse()->setBody(Zend_Json::encode(array(
                        'method'            => 'centinel',
                        'update_section'    => array(
                            'centinel-iframe' => $layout->getBlock('centinel.frame')->toHtml()
                        )
                    )));
                }
            }
            // 3D Secure

            $paymentData = $this->getRequest()->getPost('payment', array());
            if ($paymentData && @defined('Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT')) {
                $paymentData['checks'] = Mage_Payment_Model_Method_Abstract::CHECK_USE_CHECKOUT
                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_COUNTRY
                    | Mage_Payment_Model_Method_Abstract::CHECK_USE_FOR_CURRENCY
                    | Mage_Payment_Model_Method_Abstract::CHECK_ORDER_TOTAL_MIN_MAX
                    | Mage_Payment_Model_Method_Abstract::CHECK_ZERO_TOTAL;
            }

            // SagePay Server
//            $sagePaySuiteMethods = array(
//                'sagepayserver',
//                'sagepayform',
//                'sagepaydirectpro'
//            );
//            if (in_array($paymentData['method'], $sagePaySuiteMethods)) {
//                return $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
//                    'method' => 'sagepayserver',
//                    'update_section' => array(
//                        'sagepay-iframe' => $this->getLayout()
//                            ->createBlock('sagepayserver/checkout_onepage_review_info')
//                            ->setTemplate('tm/firecheckout/sagepay/iframe.phtml')
//                            ->toHtml()
//                    )
//                )));
//            }
            // SagePay Server

            // Sage Pay Suite
            $sagePaySuiteMethods = array(
                'sagepayserver',
                'sagepayform',
                'sagepaypaypal',
                'sagepaydirectpro'
            );
            if (in_array($paymentData['method'], $sagePaySuiteMethods)) {
                $quote->save();
                return $this->getResponse()
                    ->setBody(Mage::helper('core')->jsonEncode(array(
                        'method' => $paymentData['method']
                    )));
            }
            // Sage Pay Suite

            // Authorize.Net
            if (!$this->getRequest()->getBeforeForwardInfo() // if forwarded, then we already did the translaction request to authorize.net
                && 'authorizenet_directpost' === $paymentData['method']) {

                $quote->save();
                $layout = $this->getLayout();
                $update = $layout->getUpdate();
                $update->load('firecheckout_index_saveorder');
                $this->_initLayoutMessages('checkout/session');
                $layout->generateXml();
                $layout->generateBlocks();
                return $this->getResponse()
                    ->setBody(Mage::helper('core')->jsonEncode(array(
                        'method' => $paymentData['method'],
                        'popup' => array(
                            'id'      => $paymentData['method'],
                            'content' => $layout->getBlock('payment.form.directpost')->toHtml()
                        )
                    ))
                );
            }
            // Authorize.Net

            if (!isset($result['redirect']) && !isset($result['error'])) {
                if ($paymentData) {
                    $quote->getPayment()->importData($paymentData);
                }

                $checkout->saveOrder();

                $paymentHelper = Mage::helper("payment");
                if (method_exists($paymentHelper, 'getZeroSubTotalPaymentAutomaticInvoice')) {
                    $storeId = Mage::app()->getStore()->getId();
                    $zeroSubTotalPaymentAction = $paymentHelper->getZeroSubTotalPaymentAutomaticInvoice($storeId);
                    if ($paymentHelper->isZeroSubTotal($storeId)
                            && $this->_getOrder()->getGrandTotal() == 0
                            && $zeroSubTotalPaymentAction == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE
                            && $paymentHelper->getZeroSubTotalOrderStatus($storeId) == 'pending') {
                        $invoice = $this->_initInvoice();
                        $invoice->getOrder()->setIsInProcess(true);
                        $invoice->save();
                    }
                }

                $redirectUrl = $checkout->getCheckout()->getRedirectUrl();
                $result['success'] = true;
                $result['order_created'] = true;
                $result['error']   = false;
            } elseif (isset($result['redirect'])) {
                // paypal express register customer fix
                if ('paypal_express' == $paymentData['method']
                    && version_compare(Mage::helper('firecheckout')->getMagentoVersion(), '1.6.1.0') < 0 // 1.6.1 can register customer during express checkout
                    && Mage::getStoreConfig('firecheckout/general/paypalexpress_register')) {

                    $checkout->registerCustomerIfRequested();
                }
            }
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($quote, $e->getMessage());
            $result['success'] = false;
            $result['error'] = true;
            $result['error_messages'] = $e->getMessage();

            if ($gotoSection = $checkout->getCheckout()->getGotoSection()) {
                $result['goto_section'] = $gotoSection;
                $checkout->getCheckout()->setGotoSection(null);
            }

            if ($updateSection = $checkout->getCheckout()->getUpdateSection()) {
                if (isset($this->_sectionUpdateFunctions[$updateSection])) {

                    $layout = $this->getUpdateCheckoutLayout();

                    $updateSectionFunction = $this->_sectionUpdateFunctions[$updateSection];
                    $result['update_section'] = array(
                        'name' => $updateSection,
                        'html' => $this->$updateSectionFunction()
                    );
                }
                $checkout->getCheckout()->setUpdateSection(null);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($quote, $e->getMessage());
            $result['success']  = false;
            $result['error']    = true;
            $result['error_messages'] = Mage::helper('checkout')->__('There was an error processing your order. Please contact us or try again later.');
        }
        $quote->save();
        /**
         * when there is redirect to third party, we don't want to save order yet.
         * we will save the order in return action.
         */
        if (isset($redirectUrl)) {
            $result['redirect'] = $redirectUrl;
        }

        // paypal hss
        if (empty($result['error']) && file_exists(BP . DS . 'app/code/core/Mage/Paypal/Helper/Hss.php')) {
            $payment = $quote->getPayment();
            if ($payment && in_array($payment->getMethod(), Mage::helper('paypal/hss')->getHssMethods())) {
                $layout = $this->getLayout();
                $update = $layout->getUpdate();
                $update->load('firecheckout_index_saveorder');
                $this->_initLayoutMessages('checkout/session');
                $layout->generateXml();
                $layout->generateBlocks();
                $result = array(
                    'method' => 'paypalhss',
                    'popup' => array(
                        'id'      => $payment->getMethod(),
                        'modal'   => 1,
                        'content' => $layout->getBlock('paypal.iframe')->toHtml()
                    )
                );
                $result['redirect'] = false;
                $result['success'] = false;
            }
        }
        // paypal hss

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * Save payment with validation of all fields
     */
    protected function _savePayment()
    {
        // controller_action_predispatch_checkout_onepage_savePayment
        if (Mage::getStoreConfig('payment/ops_alias/active')) {
            Mage::getModel('ops/observer')->checkoutTypeOnepageSavePaymentAfter();
        }

        try {
            $result = array();
            $data = $this->getRequest()->getPost('payment', array());
            $result = $this->getCheckout()->savePayment($data);

            $redirectUrl = $this->getCheckout()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if ($redirectUrl) {
                $result['redirect'] = $redirectUrl;
            }
        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = Mage::helper('checkout')->__('Unable to set Payment Method.');
        }
        return $result;
    }

    protected function _renderSections($sections)
    {
        $result = array();
        foreach ($sections as $id) {
            $method = str_replace(' ', '', ucwords(str_replace('-', ' ', $id)));
            $result[$id] = $this->{'_get' . $method . 'Html'}();
        }
        return $result;
    }

    // https://github.com/mrlynn/MobileBrowserDetectionExample
    private function _isMobile()
    {
        $isMobile = false;
        if(isset($_SERVER['HTTP_USER_AGENT'])
            && preg_match('/(android|up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {

            $isMobile = true;
        }
        if((isset($_SERVER['HTTP_ACCEPT']) && (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml')>0))
            || ((isset($_SERVER['HTTP_X_WAP_PROFILE'])
            || isset($_SERVER['HTTP_PROFILE'])))) {

            $isMobile = true;
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $mobileUserAgent = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
            $mobileAgents = array(
                'w3c ','acs-','alav','alca','amoi','andr','audi','avan','benq',
                'bird','blac','blaz','brew','cell','cldc','cmd-','dang','doco',
                'eric','hipt','inno','ipaq','java','jigs','kddi','keji','leno',
                'lg-c','lg-d','lg-g','lge-','maui','maxo','midp','mits','mmef',
                'mobi','mot-','moto','mwbp','nec-','newt','noki','oper','palm',
                'pana','pant','phil','play','port','prox','qwap','sage','sams',
                'sany','sch-','sec-','send','seri','sgh-','shar','sie-','siem',
                'smal','smar','sony','sph-','symb','t-mo','teli','tim-','tosh',
                'tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
                'wapr','webc','winw','winw','xda','xda-'
            );
            if(in_array($mobileUserAgent, $mobileAgents)) {
                $isMobile = true;
            }
        }

        if (isset($_SERVER['ALL_HTTP'])) {
            if (strpos(strtolower($_SERVER['ALL_HTTP']), 'OperaMini') > 0) {
                $isMobile = true;
            }
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])
            && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') > 0) {

            $isMobile = false;
        }
        return $isMobile;
    }

    /**
     * Subsribe payer to newsletterr.
     * All notices and error messages are not shown,
     * to not confuse payer during checkout (Only checkout messages can be showed).
     *
     * @return void
     */
    protected function _subscribeToNewsletter()
    {
        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('newsletter')) {
            //$session       = Mage::getSingleton('core/session');
            $customerSession = Mage::getSingleton('customer/session');
            $billingData     = $this->getRequest()->getPost('billing');
            $email           = $customerSession->isLoggedIn() ?
                $customerSession->getCustomer()->getEmail() : $billingData['email'];

            try {
                if (Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG) != 1 &&
                    !$customerSession->isLoggedIn()) {
                    Mage::throwException(Mage::helper('newsletter')->__('Sorry, but administrator denied subscription for guests. Please <a href="%s">register</a>.', Mage::getUrl('customer/account/create/')));
                }

                $ownerId = Mage::getModel('customer/customer')
                        ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                        ->loadByEmail($email)
                        ->getId();
                if ($ownerId !== null && $ownerId != $customerSession->getId()) {
                    return;
                    Mage::throwException(Mage::helper('newsletter')->__('Sorry, but your can not subscribe email adress assigned to another user.'));
                }

                $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
            } catch (Mage_Core_Exception $e) {
            } catch (Exception $e) {
            }
        }
    }

    public function forgotpasswordAction()
    {
        $session = Mage::getSingleton('customer/session');

        if ($this->_expireAjax() || $session->isLoggedIn()) {
            return;
        }

        $email = $this->getRequest()->getPost('email');
        $result = array(
            'success' => false
        );
        if ($email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                $session->setForgottenEmail($email);
                $result['error'] = Mage::helper('checkout')->__('Invalid email address.');
            } else {
                $customer = Mage::getModel('customer/customer')
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                    ->loadByEmail($email);

                if ($customer->getId()) {
                    try {
                        $customerHelper = Mage::helper('customer');
                        if (method_exists($customerHelper, 'generateResetPasswordLinkToken')) {
                            $newResetPasswordLinkToken = Mage::helper('customer')->generateResetPasswordLinkToken();
                            $customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
                            $customer->sendPasswordResetConfirmationEmail();
                        } else {
                            // 1.6.0.x and earlier
                            $newPassword = $customer->generatePassword();
                            $customer->changePassword($newPassword, false);
                            $customer->sendPasswordReminderEmail();
                            $result['message'] = Mage::helper('customer')->__('A new password has been sent.');
                        }
                        $result['success'] = true;
                    } catch (Exception $e){
                        $result['error'] = $e->getMessage();
                    }
                }
                if (!isset($result['message']) && ($result['success'] || !$customer->getId())) {
                    $result['message'] = Mage::helper('customer')->__('If there is an account associated with %s you will receive an email with a link to reset your password.', Mage::helper('customer')->htmlEscape($email));
                }
            }
        } else {
            $result['error'] = Mage::helper('customer')->__('Please enter your email.');
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function loginAction()
    {
        $session = Mage::getSingleton('customer/session');

        if ($this->_expireAjax() || $session->isLoggedIn()) {
            return;
        }

        $result = array(
            'success' => false
        );

        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $session->login($login['username'], $login['password']);
                    $result['redirect'] = Mage::getUrl('*/*/index', array('_secure'=>true));
                    $result['success'] = true;
                } catch (Mage_Core_Exception $e) {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', Mage::helper('customer')->getEmailConfirmationUrl($login['username']));
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $result['error'] = $message;
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
                $result['error'] = Mage::helper('customer')->__('Login and password are required.');
            }
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function storepickupChangeAddress()
    {
        $data = Mage::getSingleton('checkout/session')->getData('storepickup_session');
        if (isset($data['store_id']) && $data['store_id']) {
            $store = Mage::getModel('storepickup/store')->load($data['store_id']);

            $data['firstname'] = Mage::helper('storepickup')->__('Store');
            $data['lastname'] = $store->getData('store_name');
            $data['street'][0] = $store->getData('address');
            $data['city'] = $store->getCity();
            $data['region'] = $store->getState();
            $data['region_id'] = $store->getData('state_id');
            $data['postcode'] = $store->getData('zipcode');
            $data['country_id'] = $store->getData('country');

            $data['company'] = '';
            if($store->getStoreFax())
                $data['fax'] = $store->getStoreFax();
            else
                unset($data['fax']);
            if($store->getStorePhone())
                $data['telephone'] = $store->getStorePhone();
            else
                unset($data['telephone']);

            $data['save_in_address_book'] = 1;
        }

        try {
            $address = $this->getCheckout()->getQuote()->getShippingAddress();
            unset($data['address_id']);
            $address->addData($data);
            $address->implodeStreetAddress();
            $address->setCollectShippingRates(true);
        } catch(Exception $e) {
            //
        }
    }

    public function relaypointChangeAddress()
    {
        if ($relaypoint = $this->getRequest()->getParam('relay-point')) {
            list($street, $description, $postcode, $city) = explode("&&&", $relaypoint);
            $shipping = array(
                'street'      => $street,
                'description' => $description,
                'postcode'    => $postcode,
                'city'        => $city
            );

            $current = $this->getCheckout()->getQuote();
            Mage::register ( 'current_quote', $current );
            $address = $current->getShippingAddress ();

            ( string ) $postcode = $shipping ['postcode'];
            if (substr ( $postcode, 0, 2 ) == 20) {
                $regioncode = substr ( $postcode, 0, 3 );
                switch ($regioncode) {
                    case 201 :
                        $regioncode = '2A';
                        break;
                    case 202 :
                        $regioncode = '2B';
                        break;
                }
            } else {
                $regioncode = substr ( $postcode, 0, 2 );
            }
            Mage::app ()->getLocale ()->setLocaleCode ( 'en_US' );
            $region = Mage::getModel ( 'directory/region' )->loadByCode ( $regioncode, $address->getCountryId () );
            $regionname = $region->getDefaultName ();
            $regionid = $region->getRegionId ();
            $address->setRegion ( $regionname );
            $address->setRegionId ( $regionid );
            $address->setPostcode ( $postcode );
            $address->setStreet ( urldecode ( $shipping ['street'] ) );
            $address->setCity ( urldecode ( $shipping ['city'] ) );
            $address->setCompany ( urldecode ( $shipping ['description'] ) );
            $address->save ();
            $current->setShippingAddress ( $address );
//            $current->save ();
        }
    }

    /**
     * Check can page show for unregistered users
     *
     * @return boolean
     */
    protected function _canShowForUnregisteredUsers()
    {
        return true;
    }

    public function buyerprotectAction()
    {
        // false to prevent _expireAjax to return redirect
        $this->getCheckout()->getCheckout()->setCartWasUpdated(false);

        if ($this->_expireAjax()) {
            return;
        }
        if (!$this->getRequest()->isPost()) {
            $this->_ajaxRedirectResponse();
            return;
        }

        /**
         * @var Mage_Sales_Model_Quote
         */
        $quote    = $this->getOnepage()->getQuote();
        $sections = array('review');
        $ajaxHelper = Mage::helper('firecheckout/ajax');
        $quote->collectTotals();

        if ($ajaxHelper->getIsShippingMethodDependsOn(array('cart', 'total'))) {
            $sections[] = 'shipping-method';
            // changing total by payment may affect the shipping prices theoretically
            // (free shipping may be canceled or added)
            $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();

            $this->getCheckout()->applyShippingMethod();
            // if shipping price was changed, we need to recalculate totals again.
            // Example: SELECTED SHIPPING METHOD NOW BECOMES FREE
            // previous method added a discount and selected shipping method wasn't free
            // but removing the previous shipping method removes the discount also
            // and selected shipping method is now free
            $quote->setTotalsCollectedFlag(false)->collectTotals();
        }

        if ($ajaxHelper->getIsPaymentMethodDependsOn(array('cart', 'total'))) {
            $sections[] = 'payment-method';
            // total was changed [in both sides], so some payment methods
            // now can be removed or added (min/max order total configuration)
            $this->getOnepage()->applyPaymentMethod();

            if ($ajaxHelper->getIsTotalDependsOn('payment-method')) {
                // recollect totals again because adding/removing payment
                // method may add/remove some discounts in the order

                // to recollect discount rules need to clear previous discount
                // descriptions and mark address as modified
                // see _canProcessRule in Mage_SalesRule_Model_Validator
                $quote->getShippingAddress()->setDiscountDescriptionArray(array())->isObjectNew(true);

                $quote->setTotalsCollectedFlag(false)->collectTotals();
            }
        }
        $quote->save();

        $result['update_section'] = $this->_renderSections($sections);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * GiveChangeMakeChange integration
     *
     * Modified version of GCMC_GiveChange_CartController::addAction
     */
    public function givechangeaddAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if (!$this->getRequest()->isPost()) {
            $this->_ajaxRedirectResponse();
            return;
        }

        /**
         * @var Mage_Sales_Model_Quote
         */
        $quote    = $this->getOnepage()->getQuote();
        $cart     = Mage::getSingleton('checkout/cart');
        $session  = $this->getCheckout()->getCheckout();
        $sections = array('review');
        $ajaxHelper = Mage::helper('firecheckout/ajax');

        try {
            foreach ($quote->getAllVisibleItems() as $item) {
                if ($item->getProductType() == GCMC_GiveChange_Model_Product_Type_Donation::TYPE_CODE) {
                    $cart->removeItem($item->getId());
                    $quote->removeItem($item->getId());
                }
            }

            // Recollect the totals after deleting any items
            // $quote->collectTotals();
            $product = $this->_givechnageloadProduct();

            if (!$product) {
                throw new Mage_Core_Exception($this->__('Cannot add the Give Change donation to your shopping cart.'));
            }

            // Set donation value only if custom selected
            if ($this->getRequest()->getParam('roundup', null) == GCMC_GiveChange_Helper_Data::DONATION_TYPE_CUSTOM) {
                $product->setDonationValue($this->getRequest()->getParam('custom', null));
            }

            $params = array(
                'qty'     => 1,
                'giftaid' => $this->getRequest()->getParam('giftaid', false),
                'roundup' => $this->getRequest()->getParam('roundup', GCMC_GiveChange_Helper_Data::DONATION_TYPE_CUSTOM),
                'value'   => $product->getDonationValue()
            );

            $cart->addProduct($product, $params);
            $cart->save();

            // false to prevent _expireAjax to return redirect
            $session->setCartWasUpdated(false);

            Mage::dispatchEvent('checkout_cart_add_product_complete',
                array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch(Exception $e) {
            $result['error'] = $this->__('Cannot add the Give Change donation to your shopping cart.');
        }

        $quote->collectTotals();
        if ($ajaxHelper->getIsShippingMethodDependsOn(array('cart', 'total'))) {
            $sections[] = 'shipping-method';
            // changing total by payment may affect the shipping prices theoretically
            // (free shipping may be canceled or added)
            $quote->getShippingAddress()->setCollectShippingRates(true)->collectShippingRates();

            $this->getCheckout()->applyShippingMethod();
            // if shipping price was changed, we need to recalculate totals again.
            // Example: SELECTED SHIPPING METHOD NOW BECOMES FREE
            // previous method added a discount and selected shipping method wasn't free
            // but removing the previous shipping method removes the discount also
            // and selected shipping method is now free
            $quote->setTotalsCollectedFlag(false)->collectTotals();
        }

        if ($ajaxHelper->getIsPaymentMethodDependsOn(array('cart', 'total'))) {
            $sections[] = 'payment-method';
            // total was changed [in both sides], so some payment methods
            // now can be removed or added (min/max order total configuration)
            $this->getOnepage()->applyPaymentMethod();

            if ($ajaxHelper->getIsTotalDependsOn('payment-method')) {
                // recollect totals again because adding/removing payment
                // method may add/remove some discounts in the order

                // to recollect discount rules need to clear previous discount
                // descriptions and mark address as modified
                // see _canProcessRule in Mage_SalesRule_Model_Validator
                $quote->getShippingAddress()->setDiscountDescriptionArray(array())->isObjectNew(true);

                $quote->setTotalsCollectedFlag(false)->collectTotals();
            }
        }
        $quote->save();

        $result['update_section'] = $this->_renderSections($sections);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    protected function _givechnageloadProduct()
    {
        $product = Mage::getModel('catalog/product');
        $id      = $product->getIdBySku(Mage::helper('givechange')->getProductSku());
        if ($id) {
            $product->setStoreId(Mage::app()->getStore()->getId())->load($id);
            if ($product->getId()) {
                return $product;
            }
        }
        return $this->_givechnagecreateProduct();
    }

    protected function _givechnagecreateProduct()
    {
        $product = Mage::getModel('catalog/product');
        Mage::getSingleton('givechange/product_type_donation')->addDefaultData($product);
        $product->save()->load();
        return $product;
    }
}
