<?php
class Microbiz_Connector_Model_Mysql4_Syncitemhistory extends Mage_Core_Model_Mysql4_Abstract
{

    /**
     * Resource initialization
     */
    public function _construct()
    {
        $this->_init('syncitemhistory/syncitemhistory', 'id');
    }

}
