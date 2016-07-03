<?php
class CJM_All_Model_Observer
{
    public function preDispatch(Varien_Event_Observer $observer)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            $feedModel  = Mage::getModel('cjm_all/feed');
            $feedModel->checkUpdate();
        }

    }
}