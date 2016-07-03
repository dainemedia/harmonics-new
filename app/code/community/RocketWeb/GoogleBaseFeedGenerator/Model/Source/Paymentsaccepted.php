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

class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Paymentsaccepted extends Varien_Object
{
    public function toOptionArray()
    {
        $vals = array(
    		'AmericanExpress'	=> 'AmericanExpress',
    		'Cash'				=> 'Cash',
    		'Check'				=> 'Check',
    		'Discover'			=> 'Discover',
    		'MasterCard'		=> 'MasterCard',
    		'Visa'				=> 'Visa',
    		'WireTransfer'		=> 'WireTransfer',
    		
    		//'GoogleCheckout'	=> 'GoogleCheckout', wrong http://www.google.com/support/forum/p/base/thread?tid=4d255763bf3f6b12&hl=en
        );
        $options = array();
        foreach ($vals as $k => $v)
            $options[] = array('value' => $k, 'label' => $v);

        return $options;
    }
}