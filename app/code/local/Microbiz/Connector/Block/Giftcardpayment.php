<?php
//version 100
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 9/6/14
 * Time: 12:38 PM
 */
class Microbiz_Connector_Block_Giftcardpayment extends Mage_Core_Block_Template
{
    /**
     * @author - KT-174
     * @description - This is a default constructor will be called on load of this block, and in the constructor we are
     * calling the template file.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('connector/checkout/onepage/payment/giftcardpayment.phtml');
    }
}