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

class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Groupedassocprodsmode extends Varien_Object
{
	const ONLY_GROUPED = 0;
	const ONLY_ASSOCIATED = 1;
	const BOTH_GROUPED_ASSOCIATED = 2;
	
    public function toOptionArray()
    {
        $vals = array(
    		self::ONLY_GROUPED => Mage::helper('googlebasefeedgenerator')->__('Only parent grouped product / No childs associated products'),
    		self::ONLY_ASSOCIATED => Mage::helper('googlebasefeedgenerator')->__('No parent grouped product / Only childs associated products'),
    		self::BOTH_GROUPED_ASSOCIATED => Mage::helper('googlebasefeedgenerator')->__('Both types - parent grouped product and childs associated products'),
        );
        $options = array();
        foreach ($vals as $k => $v)
            $options[] = array('value' => $k, 'label' => $v);

        return $options;
    }
}