<?php
//version 101
class Microbiz_Connector_Model_Mysql4_Mbizcategory extends Mage_Core_Model_Mysql4_Abstract
{

    /**
     * Resource initialization
     */
    public function _construct()
    {
        $this->_init('mbizcategory/mbizcategory', 'id');
    }

}
