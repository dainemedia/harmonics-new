<?php
class Microbiz_Connector_Model_Mysql4_Mbizcustomeraddr_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('mbizcustomeraddr/mbizcustomeraddr');
    }

}
