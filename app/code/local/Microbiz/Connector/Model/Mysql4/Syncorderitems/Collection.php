<?php
//version 101
class Microbiz_Connector_Model_Mysql4_Syncorderitems_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     *description: This is used to get all the collection from the mbz_sales_flat_order_item table
     * author: KT174
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('syncorderitems/syncorderitems');
    }

}
