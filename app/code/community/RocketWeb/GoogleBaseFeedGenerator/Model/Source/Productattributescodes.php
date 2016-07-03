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

class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Productattributescodes extends Varien_Object
{
	static public $attributes;
	
    public function toOptionArray()
    {
    	if (is_null(self::$attributes)) {
    		$this->loadAttributes();
    	}

    	$attrs = array();
    	foreach (self::$attributes as $code => $arr) {
    		$attrs[$code] = $arr['value'];
    	}
    	asort($attrs);
    	
        $options = array(array('value' => '', 'label' => ''));
        foreach ($attrs as $k => $v)
            $options[] = array('value' => $k, 'label' => $v);

        return $options;
    }
    
    public function loadAttributes()
    {
    	$config = Mage::getModel('eav/config');
        $attributes_codes = $config->getEntityAttributeCodes('catalog_product');
        self::$attributes = array();
        foreach($attributes_codes as $attribute_code) {
            $attribute = $config->getAttribute('catalog_product', $attribute_code);
			if ($attribute !== false && $attribute->getAttributeId() > 0) {
				self::$attributes[$attribute->getAttributeCode()] = array(
					'is_configurable' => ($attribute->getIsConfigurable() &&
										  $attribute->getFrontendInput() == 'select' &&
										  (class_exists("Mage_Catalog_Model_Resource_Eav_Attribute") && constant("Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL") !== null && $attribute->getIsGlobal() == Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL) &&
										  ($attribute->getBackendType() == 'int'? true : false) &&
										  (strpos($attribute->getBackendModel(), 'boolean') === false && strpos($attribute->getSourceModel(), 'boolean') === false)),
					'value' => $attribute->getFrontend()->getLabel().' ('.$attribute->getAttributeCode().')'
				);
			}
        }
    }
}