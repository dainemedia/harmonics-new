<?php

class TM_CheckoutFields_Model_Observer
{
    public function setCheckoutFields(Varien_Event_Observer $observer)
    {
        /**
         * @var TM_CheckoutFields_Helper_Data
         */
        $helper     = Mage::helper('checkoutfields');
        $quote      = Mage::getSingleton('checkout/session')->getQuote();
        $controller = $observer->getEvent()->getData('controller_action');
        $request    = $controller->getRequest();
        $result     = array();
        foreach ($helper->getEnabledFields() as $fieldName => $fieldConfig) {
            $value = (string)$request->getPost($fieldName);
            if (strlen($value)) {
                $quote->setData($fieldName, $value);
            } elseif ('req' === $fieldConfig['status']) {
                $result = array();
                $result['success'] = false;
                $result['error']   = true;
                $result['error_messages'] = 'Fill all required fields please';
            }
        }

        if ($result) {
            $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }

        return $this;
    }

    public function adminhtmlAddCheckoutFields($observer)
    {
        /**
         * @var TM_CheckoutFields_Helper_Data
         */
        $helper  = Mage::helper('checkoutfields');
        $quote   = $observer->getOrderCreateModel()->getQuote();
        $request = $observer->getRequest();
        foreach ($helper->getEnabledFields() as $fieldName => $fieldConfig) {
            if (!isset($request[$fieldName])) {
                $value = '';
            } else {
                $value = (string)$request[$fieldName];
            }
            if (strlen($value)) {
                $quote->setData($fieldName, $value);
            } elseif ('req' === $fieldConfig['status']) {
                throw new Exception('Fill all required fields please');
            }
        }
    }
}
