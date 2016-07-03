<?php
/**
 * @author    Marcin Frymark
 * @email     contact@microbiz.com
 * @company   Microbiz
 * @website   www.microbiz.com
 */
class Microbiz_ModulesConflictDetector_Model_MicrobizAdminNotification_Observer
{
    public function preDispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            $feedModel  = Mage::getModel('microbiz_modulesConflictDetector/microbizAdminNotification_feed');
            $feedModel->checkUpdate();
        }

    }
}
