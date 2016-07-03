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

class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Category extends Varien_Object {

    public function getOptionText($value) {

		$ret = array();
		
		if(is_string($value))
			$value = explode(',', $value);

		$options = $this->getAllOptions();
		
		if(is_array($value)) {
		    foreach($value as $key) {
		        $ret[] = @$options[$key];
		    }
		}
		
		return implode(",", $ret);
    }
    
    public function getAllOptions($store_id = null) {

		$options = array(array('value' => '', 'label' => '', 'style' => ''));
		$_categories = $this->getConfig()->getAllCategories($store_id);
		foreach ($_categories as $id => $categ) {
			if (isset($categ['name']) && isset($categ['level'])) {
				if ($categ['level'] < 1)
					$categ['level'] = 1;

				$options[] = array(
					'value' => $id,
					'label' =>  $categ['name'],
					'style' => 'padding-left:' . (($categ['level'] - 1) * 7) . 'px;',
				);
			}
		}
		
        return $options;
    }
    
    public function toOptionArray() {

        $code = Mage::app()->getRequest()->getParam('store', false);
        $store_id = $code ? Mage::getModel('core/store')->load($code)->getId() : null;
        return $this->getAllOptions($store_id);
    }
    
    public function getConfig() {
    	return Mage::getSingleton('googlebasefeedgenerator/config');
    }
}