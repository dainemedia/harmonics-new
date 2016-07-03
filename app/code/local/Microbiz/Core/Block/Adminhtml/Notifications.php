<?php
//version 100
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 23/2/15
 * Time: 1:30 PM
 */
class Microbiz_Core_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
    public function checkUpgradeAvail() {
        $mbizExtVer = Mage::helper('microbiz_core')->getConnectorVersion();
        $currentVer = Mage::helper('microbiz_core')->getConCurrVer();

        if(trim($mbizExtVer) && trim($mbizExtVer)< trim($currentVer))  {
            return true;
        }
        else {
            return false;
        }
    }

}