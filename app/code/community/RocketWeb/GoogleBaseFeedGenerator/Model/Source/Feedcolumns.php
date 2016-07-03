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

class RocketWeb_GoogleBaseFeedGenerator_Model_Source_Feedcolumns extends Varien_Object
{
	static public $columns;
	
    public function toOptionArray()
    {
    	if (is_null(self::$columns)) {
    		self::$columns = array();
    		$Stores = Mage::app()->getStores();
    		$config = Mage::getSingleton('googlebasefeedgenerator/config');
    		foreach ($Stores as $Store) {
    			$cfg_map_product_columns = $config->getConfigVar('map_product_columns', $Store->getStoreId(), 'columns');
    			if (is_array($cfg_map_product_columns))
	    			foreach ($cfg_map_product_columns as $arr) {
	    				if (isset($arr['column']) && !isset(self::$columns[$arr['column']])) {
	    					self::$columns[$arr['column']] = $arr['column'];
	    				}
	    			}
    		}
	        asort(self::$columns);
    	}

    	$options = array(array('value' => '', 'label' => ''));
        foreach (self::$columns as $k => $v)
            $options[] = array('value' => $k, 'label' => $v);

        return $options;
    }
}