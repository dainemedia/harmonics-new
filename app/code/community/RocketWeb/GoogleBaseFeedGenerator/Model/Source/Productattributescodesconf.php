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

class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Productattributescodesconf extends RocketWeb_GoogleBaseFeedGenerator_Model_Source_Productattributescodes
{
    public function toOptionArray()
    {
    	if (is_null(self::$attributes)) {
    		$this->loadAttributes();
    	}

    	$attrs = array();
    	foreach (self::$attributes as $code => $arr) {
    		if ($arr['is_configurable'])
    			$attrs[$code] = $arr['value'];
    	}
    	asort($attrs);
    	
    	$options = array(array('value' => '', 'label' => ''));
        foreach ($attrs as $k => $v)
            $options[] = array('value' => $k, 'label' => $v);

        return $options;
    }
}