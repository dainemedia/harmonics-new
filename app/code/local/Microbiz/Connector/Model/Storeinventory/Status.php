<?php
class Microbiz_Connector_Model_Storeinventory_Status extends Varien_Object
{
	/**#@+
	* Constants
	*/
    const STATUS_ENABLED	= 1;
    const STATUS_DISABLED	= 2;
	/**
	*Global Function to get options for enabled and disabled 
	*@return array containing enabled and disabled
	*@aurhor KT097
	*/
    static public function getOptionArray()
    {
        return array(
            self::STATUS_ENABLED    => Mage::helper('connector')->__('Enabled'),
            self::STATUS_DISABLED   => Mage::helper('connector')->__('Disabled')
        );
    }
}
