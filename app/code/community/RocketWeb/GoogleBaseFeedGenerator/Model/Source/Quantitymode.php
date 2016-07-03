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

class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Quantitymode extends Varien_Object {

    const ITEM_QTY = 0;
	const ITEM_SUM_DEFAULT_QTY = 1;

    public function toOptionArray() {

        return array(
            array('value' => self::ITEM_QTY, 'label' => Mage::helper('googlebasefeedgenerator')->__('Item\'s qty')),
            array('value' => self::ITEM_SUM_DEFAULT_QTY, 'label' => Mage::helper('googlebasefeedgenerator')->__('Sum of associated products qty'))
        );
    }
}