<?php

class TM_FireCheckout_Block_Captcha extends Mage_Core_Block_Abstract
{
    protected function _prepareLayout()
    {
        if (!Mage::helper('firecheckout')->canUseCaptchaModule()) {
            return parent::_prepareLayout();
        }

        /**
         * @var Mage_Core_Model_Layout
         */
        $layout = $this->getLayout();

        $login = $layout->getBlock('customer.login');
        if ($login) {
            $list = $layout->createBlock('core/text_list', 'form.additional.info');
            $captcha = $layout->createBlock('captcha/captcha', 'captcha.login.checkout');
            $captcha->setFormId('user_login')
                ->setImgWidth(230)
                ->setImgHeight(50);
            $list->append($captcha);
            $login->append($list);
        }

        $forgot = $layout->getBlock('customer.forgot');
        if ($forgot) {
            $list = $layout->createBlock('core/text_list', 'form.additional.info');
            $captcha = $layout->createBlock('captcha/captcha', 'captcha.forgot.checkout');
            $captcha->setFormId('user_forgotpassword')
                ->setImgWidth(230)
                ->setImgHeight(50);
            $list->append($captcha);
            $forgot->append($list);
        }

        $billing = $layout->getBlock('checkout.onepage.billing');
        if ($billing) {
            $list = $layout->createBlock('core/text_list', 'form.additional.info');
            $captcha = $layout->createBlock('captcha/captcha', 'captcha.guest.checkout');
            $captcha->setFormId('guest_checkout')
                ->setImgWidth(230)
                ->setImgHeight(50);
            $list->append($captcha);

            $captcha = $layout->createBlock('captcha/captcha', 'captcha.register.during.checkout');
            $captcha->setFormId('register_during_checkout')
                ->setImgWidth(230)
                ->setImgHeight(50);
            $list->append($captcha);

            $billing->append($list);
        }

        return parent::_prepareLayout();
    }
}
