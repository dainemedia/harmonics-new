<?php
class Microbiz_Connector_Model_Mbizcustomer extends Mage_Core_Model_Abstract
{

    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('mbizcustomer/mbizcustomer');
    }
}
