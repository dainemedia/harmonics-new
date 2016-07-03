<?php
//version 101
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 23/4/14
 * Time: 3:17 PM
 */
class Microbiz_Connector_Block_Giftcards extends Mage_Checkout_Block_Cart_Abstract
{
    /**
     * @author - KT-174
     * @description - This is a default constructor will be called on load of this block, and in the constructor we are
     * calling the template file.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('connector/checkout/cart/giftcards.phtml');
    }

}