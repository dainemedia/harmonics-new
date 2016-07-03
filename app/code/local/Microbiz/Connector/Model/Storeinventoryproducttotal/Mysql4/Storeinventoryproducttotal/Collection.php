<?php

class Microbiz_Connector_Model_Storeinventoryproducttotal_Mysql4_Storeinventoryproducttotal_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Constructor 
	 * It collects the data related to this model
     */
	public function _construct()
    {
        parent::_construct();
        $this->_init('storeinventoryproducttotal/storeinventoryproducttotal');
    }
}
