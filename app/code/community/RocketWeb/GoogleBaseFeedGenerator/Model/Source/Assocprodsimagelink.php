<?php

/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @copyright  Copyright (c) 2012 RocketWeb (http://rocketweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     RocketWeb
 */

class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsimagelink extends Varien_Object
{
	const FROM_CONFIGURABLE = 0;
	const FROM_ASSOCIATED = 1;
	const FROM_ASSOCIATED_CONFIGURABLE = 2;
	const FROM_CONFIGURABLE_ASSOCIATED = 3;
	
    public function toOptionArray()
    {
        $vals = array(
    		self::FROM_CONFIGURABLE => Mage::helper('googlebasefeedgenerator')->__('Parent configurable product only'),
    		self::FROM_ASSOCIATED => Mage::helper('googlebasefeedgenerator')->__('Associated product only'),
    		self::FROM_ASSOCIATED_CONFIGURABLE => Mage::helper('googlebasefeedgenerator')->__('Associated product if exists, otherwise from configurable'),
    		self::FROM_CONFIGURABLE_ASSOCIATED => Mage::helper('googlebasefeedgenerator')->__('Configurable product if exists, otherwise from associated product'),
        );
        $options = array();
        foreach ($vals as $k => $v)
            $options[] = array('value' => $k, 'label' => $v);

        return $options;
    }
}