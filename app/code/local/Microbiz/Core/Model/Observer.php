<?php
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 24/2/15
 * Time: 7:50 PM
 */
class Microbiz_Core_Model_Observer
{

    protected function _disablePlugin($moduleName,$status='true') {
        $connectorConfig = Mage::getBaseDir().DS.'app/etc/modules/'.$moduleName.'.xml';
        $connectorContent=simplexml_load_file($connectorConfig);

        Mage::log($connectorContent,null,'massupdate.log');
        if($connectorContent->modules->$moduleName->active==true) {
            Mage::log("plugin is disabled but file active status is enabled",null,'massupdate.log');
            $connectorContent->modules->$moduleName->active = $status;
            $connectorContent->asXML($connectorConfig);
            Mage::helper('microbiz_core')->RemoveCaching();
            return true;
        }

    }

    public function onModuleStatusChange() {
        Mage::log("came to observer onModuleStatusChange",null,'massupdate.log');
        $microbizConnector = Mage::getStoreConfig('advanced/modules_disable_output/Microbiz_Connector');
        $microbizModulesConflictDetector = Mage::getStoreConfig('advanced/modules_disable_output/Microbiz_ModulesConflictDetector');
        Mage::log($microbizConnector,null,'massupdate.log');
        Mage::log($microbizModulesConflictDetector,null,'massupdate.log');


        /*Code for Microbiz_Connector Plugin*/
        if($microbizConnector) { //module is disabled
            $this->_disablePlugin('Microbiz_Connector','false');
        }
        else {  //module is enabled
            $this->_disablePlugin('Microbiz_Connector','true');
            }



        /*Code for Microbiz_ModulesConflictDetector Plugin*/
        if($microbizModulesConflictDetector) { //module is disabled

            $this->_disablePlugin('Microbiz_ModulesConflictDetector','false');
        }
        else {  //module is enabled
            $this->_disablePlugin('Microbiz_ModulesConflictDetector','true');
            }



        Mage::helper('microbiz_core')->RemoveCaching();


    }
}