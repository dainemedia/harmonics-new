<?php
class Microbiz_Connector_Model_Storeinventoryproduct_Storeinventoryproduct extends Mage_Core_Model_Abstract
{
	/**
     * Constructor 
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('storeinventoryproduct/storeinventoryproduct');
    }
}
