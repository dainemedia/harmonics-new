<?php
class Microbiz_Connector_Model_Mysql4_Mbizcreditusage_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     *description: This is used to get all the collection from the mbiz_category_rel_mas  table
     * author: mahesh
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('mbizcreditusage/mbizcreditusage');
    }

}
