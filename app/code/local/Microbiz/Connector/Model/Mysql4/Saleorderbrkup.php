<?php
class Microbiz_Connector_Model_Mysql4_Saleorderbrkup extends Mage_Core_Model_Mysql4_Abstract
{

    /**
     * Resource initialization
     */
    public function _construct()
    {
        $this->_init('saleorderbrkup/saleorderbrkup', 'id');
    }

}
