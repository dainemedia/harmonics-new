<?php

class Microbiz_Connector_Model_Storeinventory_Mysql4_Storeinventory extends Mage_Core_Model_Mysql4_Abstract
{
    
	/**
     * Constructor 
     */
	public function _construct()
    {    
        // Note that the storeinventory_id refers to the key field in your database table.
        $this->_init('storeinventory/storeinventory', 'storeinventory_id');
    }
}
