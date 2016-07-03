<?php
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 23/5/14
 * Time: 3:07 PM
 */
//version 100
class Microbiz_Connector_Model_Mysql4_Mbizgiftcardsale extends Mage_Core_Model_Mysql4_Abstract
{

    /**
     * Resource initialization
     */
    public function _construct()
    {
        $this->_init('mbizgiftcardsale/mbizgiftcardsale', 'id');
    }

}
