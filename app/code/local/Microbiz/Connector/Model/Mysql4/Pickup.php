<?php

class Microbiz_Connector_Model_Mysql4_Pickup extends Mage_Core_Model_Mysql4_Abstract
{
	/**
     * Resource initialization
     */
	public function _construct()
    {    
        
        $this->_init('pickup/pickup', 'id');
    }
}