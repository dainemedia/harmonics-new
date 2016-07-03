<?php

class Microbiz_Connector_Model_Pickup extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('pickup/pickup');
    }
}