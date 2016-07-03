<?php
class Microbiz_Connector_Block_Instorepickup extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{
    public function __construct(){
        $this->setTemplate('connector/instorepickup.phtml');       
    }
}
