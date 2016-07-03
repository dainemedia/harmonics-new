<?php
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 10/4/14
 * Time: 12:48 PM
 */
class Microbiz_Connector_Block_Storecredits extends Mage_Checkout_Block_Cart_Abstract
{
    /**
     * @author - KT-174
     * @description - This is a default constructor will be called on load of this block, and in the constructor we are
     * calling the template file.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('connector/checkout/cart/storecredits.phtml');
    }


}