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

class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Apparel_Assocprodsmode extends Varien_Object {

    const ONLY_ASSOCIATED = 1;
    const BOTH_CONFIGURABLE_ASSOCIATED = 2;

    public function toOptionArray() {

        return array(
            array('value' => self::ONLY_ASSOCIATED, 'label' => Mage::helper('googlebasefeedgenerator')->__('No parent configurable product / Only childs associated products')),
            array('value' => self::BOTH_CONFIGURABLE_ASSOCIATED, 'label' => Mage::helper('googlebasefeedgenerator')->__('Both types - parent configurable product and childs associated products')),
        );
    }
}