<?php

class TM_CheckoutSuccess_Model_Observer
{
    public function registerCurrentOrder($observer)
    {
        if (!Mage::helper('checkoutsuccess')->isEnabled()
            || Mage::registry('current_order')) {

            return;
        }

        $session = Mage::getSingleton('checkout/session');
        if (!$session->getLastSuccessQuoteId()) {
            return;
        }

        $lastQuoteId = $session->getLastQuoteId();
        $lastOrderId = $session->getLastOrderId();
        $lastRecurringProfiles = $session->getLastRecurringProfileIds();
        if (!$lastQuoteId || (!$lastOrderId && empty($lastRecurringProfiles))) {
            return;
        }

        Mage::register('current_order', Mage::getModel('sales/order')->load($lastOrderId));
    }

    /**
     * @todo move to TM_Core module: <layout_tm>...</layout_tm>
     */
    public function addLayoutUpdate($observer)
    {
        $area = Mage_Core_Model_App_Area::AREA_FRONTEND;
        $updates = $observer->getUpdates();
        $extraNodes = Mage::app()->getConfig()->getNode($area.'/layout_checkoutsuccess/updates');
        foreach ($extraNodes->children() as $node) {
            if ($node->getAttribute('condition')) {
                $parts = explode('/', $node->getAttribute('condition'));
                $helper = array_shift($parts);
                $method = array_shift($parts);
                if (count($parts)) {
                    $helper .= '/' . $method;
                    $method = array_shift($parts);
                }
                if (!Mage::helper($helper)->{$method}()) {
                    continue;
                }
            }
            $updates->appendChild($node);
        }
    }
}
