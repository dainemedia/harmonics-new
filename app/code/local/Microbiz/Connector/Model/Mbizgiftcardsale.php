<?php
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 23/5/14
 * Time: 3:04 PM
 */
//version 100
class Microbiz_Connector_Model_Mbizgiftcardsale extends Mage_Core_Model_Abstract
{

    /**
     * Internal constructor not depended on params. Can be used for object initialization
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('mbizgiftcardsale/mbizgiftcardsale');
    }
}