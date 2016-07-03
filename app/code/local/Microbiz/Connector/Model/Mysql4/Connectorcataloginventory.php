<?php
// Version 100
class Microbiz_Connector_Model_Mysql4_Connectorcataloginventory extends Mage_Core_Model_Mysql4_Abstract
{

    /**
     * Resource initialization
     */
    public function _construct()
    {
        $this->_init('connectorcataloginventory/connectorcataloginventory', 'item_id');
    }

}
