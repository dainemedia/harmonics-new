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

class RocketWeb_GoogleBaseFeedGenerator_Model_Generator extends Varien_Object {

	const PRODUCT_TYPE_ASSOC = 'simple_associated';
	
	protected $_handle = null;

	protected $_count_products_exported = 0;
	protected $_count_products_skipped = 0;

	protected $_alternate_feed_dir = null;

	protected $_columns_map = null;
	protected $_empty_columns_replace_map = null;

	protected $_collection = null;

	protected $_total_items = null;
	protected $_current_iter = 0;

	protected $_currencyObject;
	protected $_currencyRate;
	
	/**
	 * @var RocketWeb_GoogleBaseFeedGenerator_Model_Batch
	 */
	protected $batch;

    protected $_storeLockFile;


    protected function _construct() {

    	parent::_construct();
    	
    	if (!$this->hasData('store_code'))
    		$this->setData('store_code', Mage_Core_Model_Store::DEFAULT_CODE);
    	try {
    		Mage::app()->getStore($this->getData('store_code'));
    	} catch (Exception $e) {
    		Mage::throwException(sprintf('Store with code \'%s\' doesn\'t exists.', $this->getData('store_code')));
    	}

    	$this->setData('store_id', Mage::app()->getStore($this->getData('store_code'))->getStoreId());
    	$this->setData('website_id', Mage::app()->getStore($this->getData('store_code'))->getWebsiteId());
        $this->setData('store_currency_code', Mage::app()->getStore($this->getData('store_code'))->getCurrentCurrencyCode());

        // Initialize locks
        $this->initSavePath();
        $this->_storeLockFile = @fopen($this->getLockPath(), "w");
        if (!file_exists($this->getLockPath())) {
            Mage::throwException(sprintf('Can\'t create file %s', $this->getLockPath()));
        }

        // If the location is not writable, flock() does not work and it doesn't mean another script instance is running
        if (!is_writable($this->getLockPath())) {
            Mage::throwException(sprintf('Not enough permissions. Location [%s] must be writable', $this->getLockPath()));
        }
    }
    
    protected function initialize() {

    	$this->getColumnsMap();
    	$this->getEmptyColumnsReplaceMap();
    	$this->loadAdditionalAttributes();

        return $this;
    }
    
    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Batch
     */
    public function getBatch() {

    	if ($this->getBatchMode() && is_null($this->batch)) {
    		if (!$this->getScheduleId())
    			Mage::throwException(sprintf('Invalid schedule_id %s', $this->getScheduleId()));
    		$this->batch = Mage::getModel('googlebasefeedgenerator/batch', array(
	    		'store_code' => $this->getStoreCode(),
	    		'store_id'	 => $this->getStoreId(),
	    		'website_id' => $this->getWebsiteId(),
	    		'config'	 => $this->getConfig(),
	    		'schedule_id' => $this->getScheduleId(),
	    	));
    	}
    	
    	return $this->batch;
    }

    /**
     * @return $this
     */
    public function run() {

        $memory = memory_get_usage(true);
    	if (!$this->getConfigVar('is_turned_on')) {
            return $this;
        }

        // Another instance is writing to the feed
        if (!$this->acquireLock()) {
            Mage::throwException(sprintf('Another generator instance is writing the feed for store [%s]. Try again later.', $this->getStoreCode()));
        }

        // Attempt to run a full feed when batch not finished
        if (!$this->getBatchMode() && $this->batchInProgress()) {
            Mage::throwException(sprintf('Batch generation is in progress. Wait for the batch to finish or force this action by removing [%s]', $this->getBatchLockPath()));
        }

    	$this->log('START');
        if ($this->getData('verbose')) {
            session_start(); // fix for magento 1.4 complaining abut headers. Not sure why 1.4 initiates the session
            echo "START\n";
        }
    	
		if ($this->getBatchMode() && !$this->getConfigVar('use_batch_segmentation'))
    		$this->setBatchMode(false);
    	
    	$this->initialize();
    	
    	$this->_total_items = null;
    	if($this->getBatchMode()) {
    		$count_coll = clone $this->_getCollection();
    		$this->_total_items = $count_coll->getSize();
    		$this->getBatch()->setTotalItems($this->_total_items);
    		unset($count_coll);
    		$batch_limit = ($this->getConfigVar('batch_limit') == 0 ? 1000 : $this->getConfigVar('batch_limit'));
    		$batch_limit = ($batch_limit <= $this->_total_items ? $batch_limit : $this->_total_items);
    		$this->getBatch()->setLimit($batch_limit);
    		// Can't get lock, another script is running.
    		if (!$this->getBatch()->aquireLock()) {
    			return $this;
    		}
    	}
    	
    	$collection = $this->getCollection();
    	if (is_null($this->_total_items))
    		$this->_total_items = $collection->getSize();
    	if(!$this->getBatchMode() || ($this->getBatchMode() && $this->getBatch()->getIsNew())) {
    		$this->writeFeed($this->getHeader(), false);
    	}
    	
    	$product_types = $this->getConfig()->getMultipleSelectVar('product_types', $this->getData('store_id'));
    	$this->_current_iter = 0;

    	Mage::getSingleton('core/resource_iterator')->walk($collection->getSelect(), array(array($this, 'processProductCallback')), array(
    		'product_types' => $product_types,
    	));

    	$this->closeHandle();
    	$this->log(sprintf('products (items added, skipped)(%3d, %3d) to file %s', $this->getCountProductsExported(), $this->getCountProductsSkipped(), $this->getFeedPath()));
    	
    	if($this->getBatchMode()) {
    		$this->getBatch()->releaseLock();
    	}

        $this->releaseLock();
    	$this->log('END / MEMORY USED: '. $this->formatMemory(memory_get_usage(true) - $memory));

        if ($this->getData('verbose')) {
            echo "=====================================================\n";
            echo sprintf('products (items added, skipped)(%3d, %3d) to file %s', $this->getCountProductsExported(), $this->getCountProductsSkipped(), $this->getFeedPath()). "\n";
            echo 'MEMORY USED: '. $this->formatMemory(memory_get_usage(true) - $memory). "\n";
        }

    	return $this;
    }
    
    /**
     * @param int $type_id
     * @param array $args
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract
     */
    public function getProductMapModel($type_id, $args = array()) {

        $productMap = null;
    	$params = array(
    		'store_code' => $this->getData('store_code'),
    		'store_id' => $this->getData('store_id'),
    		'website_id' => $this->getData('website_id'),
    	);
		
    	$is_assoc_configurable = isset($args['is_assoc_configurable']) && $args['is_assoc_configurable'] ? true : false;
    	$is_assoc_grouped = isset($args['is_assoc_grouped']) && $args['is_assoc_grouped'] ? true : false;
    	$is_apparel = isset($args['is_apparel']) && $args['is_apparel'] ? true : false;

    	switch ($type_id) {

    		case 'simple':
    			if ($is_assoc_configurable && $is_apparel)
    				Mage::throwException(sprintf('Product type %s is not allowed here.', $type_id));
    			if ($is_assoc_configurable && $this->isProductTypeEnabled(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)) {
    				$productMap = Mage::getModel('googlebasefeedgenerator/map_product_associated', $params);
    			} else {
    				if ($is_apparel)
    					$productMap = Mage::getModel('googlebasefeedgenerator/map_product_simple_apparel', $params);
    				else
    					$productMap = Mage::getModel('googlebasefeedgenerator/map_product_simple', $params);
    			}
    			break;

    		case 'configurable':
    			if ($is_apparel)
    				$productMap = Mage::getModel('googlebasefeedgenerator/map_product_configurable_apparel', $params);
    			else
    				$productMap = Mage::getModel('googlebasefeedgenerator/map_product_configurable', $params);
    			break;

    		case 'bundle':
    			if ($is_apparel)
    				$productMap = Mage::getModel('googlebasefeedgenerator/map_product_bundle_apparel', $params);
    			else
    				$productMap = Mage::getModel('googlebasefeedgenerator/map_product_bundle', $params);
    			break;
    	}

        if (is_null($productMap) && is_readable(dirname(__FILE__). DS. 'Map'. DS. 'Product'. DS. ucfirst($type_id). '.php')) {
            $productMap = Mage::getModel('googlebasefeedgenerator/map_product_'.$type_id, $params);
        } elseif (is_null($productMap)) {
            $productMap = Mage::getModel('googlebasefeedgenerator/map_product_abstract', $params);
        }

        unset($type_id, $args, $params, $is_apparel, $is_assoc_configurable, $is_assoc_grouped);
    	return $productMap;
    }

    /**
     * @param $args
     */
    public function processProductCallback($args) {

        $row = $args['row'];
    	$parentEntityId = null;

    	if (++$this->_current_iter % $this->getLogCountStep($this->_total_items) == 0) {
            $this->log(sprintf("(%3d, %3d) products (processed, max)", $this->_current_iter, $this->_total_items));
        }

        if (($category_ids = $this->getTools()->getCategoriesById($row['entity_id'])) !== false) {
        	if (count(array_intersect($this->getConfig()->getMultipleSelectVar('skip_category', $this->getStoreId(), 'columns'), $category_ids)) > 0) {
        		if ($this->getConfigVar('log_skip')) {
	    			$this->log(sprintf("product id %d product sku %s, skipped - by category.", $row['entity_id'], $row['sku']));
	    		}
	    		$this->_count_products_skipped++;
	    		return;
        	}
        }

    	$is_assoc_configurable = $this->getTools()->isChildOfProductType($row['type_id'], $row['sku'], Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE, $this->getStoreId());
        if (!$is_assoc_configurable) {
            $is_assoc_configurable = $this->getTools()->isChildOfProductType($row['type_id'], $row['sku'], RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Subscription_Configurable::PRODUCT_TYPE_SUBSCTIPTION_CONFIGURABLE, $this->getStoreId());
        }

    	if ($is_assoc_configurable !== false && $this->isProductTypeEnabled(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)) {
            $parentEntityId = $is_assoc_configurable['parent_entity_id'];

    	    // Skip any associated products of configurable.
            if ($this->getConfigVar('log_skip')) {
                $this->log(sprintf("product id %d product sku %s, initially skipped as simple product, will be added as configurable associated product of id %d.", $row['entity_id'], $row['sku'], $parentEntityId));
            }
    		return;
    	}

    	$is_assoc_grouped = $this->getTools()->isChildOfProductType($row['type_id'], $row['sku'], Mage_Catalog_Model_Product_Type::TYPE_GROUPED, $this->getStoreId());
        if (!$is_assoc_grouped) {
            $is_assoc_grouped = $this->getTools()->isChildOfProductType($row['type_id'], $row['sku'], RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Subscription_Grouped::PRODUCT_TYPE_SUBSCTIPTION_GROUPED, $this->getStoreId());
        }
		if ($is_assoc_grouped !== false) {
			$parentEntityId = $is_assoc_grouped[0]['parent_entity_id'];
		}

    	// Skip any associated products of grouped when grouped_associated_products_mode is not RocketWeb_GoogleBaseFeedGenerator_Model_Source_Groupedassocprodsmode::ONLY_GROUPED.
    	if ($is_assoc_grouped !== false && $this->getConfig()->isAllowGroupedAssociatedMode($this->getStoreId())
            && $this->getConfig()->getConfigVar('auto_skip')) {
            if ($this->getConfigVar('log_skip')) {
                $this->log('Stand alone item processed SKU: '. $row['sku']. ' is been omitted because is part of a configurable product.');
            }
    		return;
    	}

    	$pMap = $this->getProductMapModel('abstract')
			->setColumnsMap($this->getColumnsMap())
			->setEmptyColumnsReplaceMap($this->getEmptyColumnsReplaceMap())
			->initialize();
    	$is_apparel = $pMap->isApparelBySql($row['entity_id'], $parentEntityId, $category_ids);

    	// Skip apparels if disabled.
    	if ($is_apparel && !$this->getConfigVar('is_turned_on', 'apparel')) {
    		if ($this->getConfigVar('log_skip')) {
    			$this->log(sprintf("product id %d product sku %s, skipped - apparel products are disabled.", $row['entity_id'], $row['sku']));
    		}
    		$this->_count_products_skipped++;
    		return;
    	}

    	$productMap = $this->getProductMapModel($row['type_id'], array(
    		'is_assoc_configurable' => (@$is_assoc_configurable !== false ? true : false),
    		'is_assoc_grouped' => (@$is_assoc_grouped !== false ? true : false),
    		'is_apparel'  => (@$is_apparel == 1 ? true : false),
    		/*'is_clothing' => ($pMap->isClothingBySql($row['entity_id'], $parentEntityId) == 1 ? true : false),
    		'is_shoes'	  => ($pMap->isShoesBySql($row['entity_id'], $parentEntityId) == 1? true : false),*/
    	));

        //Skip product if non provided
        if($productMap == false){
            return;
        }

    	$product = Mage::getModel('catalog/product');
    	$product->setStoreId($this->getStoreId());
    	$product->getResource()->load($product, $row['entity_id']);

    	$productMap->setProduct($product)
			->setColumnsMap($this->getColumnsMap())
			->setEmptyColumnsReplaceMap($this->getEmptyColumnsReplaceMap())
			->initialize();

		if ($productMap->checkSkipSubmission()->isSkip()) {
			if ($this->getConfigVar('log_skip')) {
    			$this->log(sprintf("product id %d product sku %s, skipped - product has 'Skip from Being Submitted' = 'Yes'.", $row['entity_id'], $row['sku']));
    		}
			$this->_count_products_skipped++;
    		return;
		}

        $this->addProductToFeed($productMap);

        // Free up memory
        $this->getTools()->clearNestedObject($product);

        $this->getTools()->unsConfigurableAttributesAsArray($product);
        unset($product, $productMap, $pMap, $parentEntityId, $category_ids, $is_assoc_configurable, $is_apparel, $is_assoc_grouped, $row);

        if ($this->getData('verbose')) {
            echo $this->formatMemory(memory_get_usage(true)). " - ". $args['row']['sku']. "\n";
        }
    }

    /**
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function getCollection() {

    	if (is_null($this->_collection)) {
    		$this->_collection = clone $this->_getCollection();
    		if ($this->getBatchMode()) {
	        	$this->_collection->getSelect()->limit($this->getBatch()->getLimit(), $this->getBatch()->getOffset() - $this->getBatch()->getLimit());
	        } elseif ($this->getTestMode()) {
	        	if ($this->getTestSku()) {
	        		$this->_collection->addAttributeToFilter('sku', $this->getTestSku());
	        	} elseif ($this->getTestOffset() >= 0 && $this->getTestLimit() > 0) {
	        		$this->_collection->getSelect()->limit(($this->getTestLimit() > 0 ? $this->getTestLimit() : 0), ($this->getTestOffset() > 0 ? $this->getTestOffset() : 0));
	        	} else {
	        		Mage::throwException(sprintf("Invalid parameters for test mode: sku %s or offset %s and limit %s", $this->getTestSku(), $this->getTestOffset(), $this->getTestLimit()));
	        	}
	        }
    	}

    	return $this->_collection;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    protected function _getCollection() {

        /** @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection*/
    	$collection = Mage::getModel('catalog/product')->getCollection()
                      ->setStore($this->getData('store_code'))
                      ->addStoreFilter($this->getData('store_code'));
        //$collection->addAttributeToSelect('name', 'image', 'media_gallery', 'gallery');

        $this->addProductTypeToFilter($collection);

        // Filter visible / enabled products
        $collection->addAttributeToFilter('status', array('neq' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED))
                    ->addFieldToFilter('visibility', array('neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE));


		if (!$this->getConfigVar('add_out_of_stock')) {
			$collection->addPriceData(null, $this->getData('website_id'));
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
		}

        if (!$this->getTestMode() && $this->getConfigVar('sku', 'debug') != "") {
        	$collection->addAttributeToFilter('sku', $this->getConfigVar('sku', 'debug'));
        }
        
        return $collection;
    }

    /**
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    protected function addProductTypeToFilter($collection) {

    	$default_product_types = array(
    		Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
    		Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
    		Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE,
    		Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
    		Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
    		Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
        );
		$product_types = $this->_getProductTypes();

		$not_in_product_types = array_diff($default_product_types, $product_types);
        $in_product_types = array_diff($product_types, $default_product_types);

        if (count($in_product_types)) {
            $collection->addAttributeToFilter('type_id', array('in' => $product_types));
        }

		if (count($not_in_product_types) > 0) {
			$collection->addAttributeToFilter('type_id', array('nin' => $not_in_product_types));
        }

    	return $collection;
    }
    
    /**
     * Returns columns map in asc order.
     * Skips columns with attributes that doesn't exist.
     * Caches eav attributes model used.
     * 
     *  [column] =>
     * 			[column]
     * 			[attribute code or directive code]
     * 			[default_value]
     * 			[order]
     *
     * @return array
     */
    protected function getColumnsMap() {

    	if (!is_null($this->_columns_map))
    		return $this->_columns_map;

    	$tmp = $cfg_map = $this->getConfigVar('map_product_columns', 'columns');
    	foreach ($tmp as $k => $arr) {
    		if (!$this->getConfig()->isDirective($arr['attribute'], $this->getData('store_id'))) {
                $attribute = $this->getAttribute($arr['attribute']);
    			if ($attribute == false) {
    				$this->log(sprintf("Column '%s' ignored, can't find attribute with code '%s'.", $arr['column'], $arr['attribute']), Zend_Log::WARN);
    				unset($cfg_map[$k]);
    				continue;
    			}
			    $attribute->setStoreId($this->getData('store_id'));
                $this->setAttribute($attribute);
    		}
    	}
    	$this->_columns_map = array();
    	foreach ($cfg_map as $arr)
    		$this->_columns_map[$arr['column']] = $arr;
    	
    	// Check shipping enabled and if set shipping column.
    	if (isset($this->_columns_map['shipping']) && $this->_columns_map['shipping']['attribute'] == "rw_gbase_directive_shipping" && !$this->getConfigVar('enabled', 'shipping')) {
    		unset($this->_columns_map['shipping']);
    	}

    	$names = array(($this->getConfigVar('locale') == 'en_GB') ? 'colour': 'color', 'size', 'gender', 'age_group', 'material', 'pattern');
    	foreach ($names as $n) {
	    	// Check and load apparel attributes.
	    	if (isset($this->_columns_map[$n]) && isset($this->_columns_map[$n]['attribute']) && $this->_columns_map[$n]['attribute'] == 'rw_gbase_directive_apparel_'.$n) {
	    		if (!$this->loadApparelAttributes($n) && (isset($this->_columns_map[$n]['defailt_value']) && $this->_columns_map[$n]['default_value'] == "")) {
	    			$this->log(sprintf("Column '%s' ignored, can't find any attributes assigned.", $this->_columns_map[$n]['column']), Zend_Log::WARN);
					unset($this->_columns_map[$n]);
	    		}
	    	}
    	}
		
    	// Check attribute assigned to availability column (stock status).
    	if (!$this->getConfigVar('use_default_stock', 'columns') && isset($this->_columns_map['availability']) && $this->getConfigVar('stock_attribute_code', 'columns') !== "") {
            $attribute = $this->getAttribute($this->getConfigVar('stock_attribute_code', 'columns'));
    		if ($attribute !== false) {
    			$attribute->setStoreId($this->getData('store_id'));
                $this->setAttribute($attribute);
    		} else {
    			$this->log(sprintf("Column '%s' ignored, can't find attribute with code '%s'.", $this->_columns_map['availability']['column'], $this->getConfigVar('stock_attribute_code', 'columns')), Zend_Log::WARN);
				unset($this->_columns_map['availability']);
    		}
    	}
    	
    	$s = array();
    	foreach ($this->_columns_map as $column => $arr) {
    		$s[$column] = $arr['order'];
        }
    	array_multisort($s, $this->_columns_map);
		
    	return $this->_columns_map;
    }
    
    /**
     * @param string $name
     * @return bool
     */
    protected function loadApparelAttributes($name) {

    	$one = false;
    	
    	if ($name != "material" && $name != "pattern") {
	    	$attributes_codes = $this->getConfig()->getMultipleSelectVar($name.'_attribute_code', $this->getData('store_id'), 'apparel');
	    	if (count($attributes_codes) > 0) {
				foreach ($attributes_codes as $attr_code) {
                    $attribute = $this->getAttribute($attr_code);
					if ($attribute !== false) {
						$attribute->setStoreId($this->getData('store_id'));
                        $this->setAttribute($attribute);
						$one = true;
					}
				}
			}
    	}
		
    	if ($name != "gender" && $name != "age_group") {
			$attributes_codes = $this->getConfig()->getMultipleSelectVar('variant_'.$name.'_attribute_code', $this->getData('store_id'), 'apparel');
	    	if (count($attributes_codes) > 0) {
				foreach ($attributes_codes as $attr_code) {
                    $attribute = $this->getAttribute($attr_code);
					if ($attribute !== false) {
						$attribute->setStoreId($this->getData('store_id'));
                        $this->setAttribute($attribute);
						$one = true;
					}
				}
			}
    	}
    	
		return $one;
    }
    
    /**
     * Returns columns map replaced by other attributes when it's value is empty for a product.
     * Sorts result asc by rule order.
     * Caches eav attributes model used.
     * Skips rules with attributes that doesn't exist.
     * 
     * @return array
     */
    protected function getEmptyColumnsReplaceMap() {

    	$_columns_map = $this->getColumnsMap();
    	if (!is_null($this->_empty_columns_replace_map))
    		return $this->_empty_columns_replace_map;

    	$tmp = $cfg_map = $this->getConfigVar('map_replace_empty_columns', 'columns');

    	if (empty($cfg_map))
    		$tmp = $cfg_map = array();
    	foreach ($tmp as $k => $arr) {
    		if (!isset($_columns_map[$arr['column']])) {
    			unset($cfg_map[$k]);
    			continue;
    		}

            $attribute = $this->getAttribute($arr['attribute']);
			if ($attribute == false && empty($arr['static'])) {
				$this->log(sprintf("Rule ('%s', '%s', '%d') is ignored, can't find attribute with code '%s'.", $arr['column'], $arr['attribute'], @$arr['rule_order'], $arr['attribute']), Zend_Log::WARN);
				unset($cfg_map[$k]);
				continue;
			} elseif ($attribute){
                $attribute->setStoreId($this->getData('store_id'));
                $this->setAttribute($attribute);
            }
    	}
    	
    	$this->_empty_columns_replace_map = $cfg_map;
    	
    	$s = array();
    	// Move rules without order to the bottom.
    	foreach ($this->_empty_columns_replace_map as $k => $arr) {
    		if (!isset($arr['rule_order']) || (isset($arr['rule_order']) && $arr['rule_order'] == ""))
				$this->_empty_columns_replace_map[$k]['rule_order'] = 99999;

			$s[$k] = $arr['rule_order'];
    	}
    	array_multisort($s, $this->_empty_columns_replace_map);
    	
    	return $this->_empty_columns_replace_map;
    }
    
    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Generator
     */
    protected function loadAdditionalAttributes() {

    	$codes = array('status');
    	foreach ($codes as $attribute_code) {
            $this->setAttribute($this->getAttribute($attribute_code));
    	}
    	
    	return $this;
    }
    
    public function getHeader() {
        return array_combine(array_keys($this->_columns_map), array_keys($this->_columns_map));
    }
    
    protected function writeFeed($fields, $add_new_line = true) {

    	// google error: "Too many column delimiters"
    	foreach ($this->_columns_map as $column => $arr) {
    		if (isset($fields[$column]) && $fields[$column] == "")
    			$fields[$column] = " ";
    	}
    	fwrite($this->getHandle(), ($add_new_line ? PHP_EOL : '').implode("\t", $fields));
    }
    
    /**
    * @param  RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract $productMap
    */
    protected function addProductToFeed($productMap) {

    	try {
	        $rows = $productMap->map();
            // if (get_class($productMap) != 'RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Configurable_Apparel') { $rows = $productMap->map(); }

			if ($productMap->isSkip()) {
				$this->_count_products_skipped++;
				return $this;
			}

			if ( ($productMap->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
                    || $productMap->getProduct()->getTypeId() == RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Subscription_Configurable::PRODUCT_TYPE_SUBSCTIPTION_CONFIGURABLE
                 ) && $productMap->getIsApparel() && $productMap->hasAssocMaps() && $productMap->getIsVariants()
               ) {
        		foreach ($productMap->getAssocMaps() as $assocMap) {
        			if ($assocMap->isSkip())
        				$this->_count_products_skipped++;
        		}
        	}

	        foreach ($rows as $row) {
	        	// format prices
	        	foreach ($row as $column => $value) {
	        		if (($column == "price" || $column == "sale_price") && trim($value) != "") {
	        			$row[$column] = $this->formatPrice($value);
	        		}
	        	}

				$this->writeFeed($row);
	        	$this->_count_products_exported++;
	        }
    	} catch (Exception $e) {
    		$this->log($e->getMessage(), Zend_Log::ERR);
    		if ($this->getTestMode()) {
    			if ($productMap instanceof RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract && $productMap->getProduct() instanceof Mage_Catalog_Model_Product) {
    				Mage::throwException(sprintf("product id %d product sku %s: ".$e->getMessage(), $productMap->getProduct()->getId(), $productMap->getProduct()->getSku()));
    			} else {
    				Mage::throwException($e->getMessage());
    			}
    		}
    	}

        return $this;
    }

    /**
     * @param $value
     * @param null $format_prices_locale
     * @return string
     */
    public function formatPrice($value, $format_prices_locale = null) {

    	if (trim($value) == "")
    		return $value;
    	
    	if (is_null($format_prices_locale)) {
    		$format_prices_locale = $this->getConfigVar('format_prices_locale', 'columns');
    	}

		if (!is_numeric($value)) {
			// no vars or objects references in function getNumber
		    $value = Mage::app()->getLocale()->getNumber($value);
		} elseif (is_string($value)) {
			$value = floatval($value);
		}
		
		$base_currency_code = Mage::getStoreConfig('currency/options/base', $this->getStoreId());
		$to_currency_code = Mage::app()->getStore($this->getStoreId())->getCurrentCurrencyCode();
		if ($base_currency_code != $to_currency_code) {
			if (is_null($this->_currencyRate)) {
				$this->_currencyRate = Mage::app()->getStore()->getCurrentCurrency()->
					getCurrencyRates(
						$base_currency_code,
						$to_currency_code
					);
				
				if (!(is_array($this->_currencyRate) && isset($this->_currencyRate[$to_currency_code]))) {
					Mage::throwException(sprintf('Can\'t find currency rate %s to %s', $base_currency_code, $to_currency_code));
				}
			}
			$value = $this->_currencyRate[$to_currency_code] * $value;
		}
		
		if (is_null($this->_currencyObject)) {
			$locale = Mage::getStoreConfig('general/locale/code', $this->getStoreId());
			if (!$format_prices_locale) {
				$locale = "en_US";
			}
			$this->_currencyObject = new Zend_Currency($to_currency_code, $locale);
		}
		
		$options = array(
			'display' => Zend_Currency::NO_SYMBOL,
		);
		$value = sprintf("%.2F", $value);
		$value = $this->_currencyObject->toCurrency($value, $options);

    	return $value. ' '. $this->getData('store_currency_code');
    }

    /**
     * @return bool
     */
    public function isAlternateFeedPath() {

    	$path = trim($this->getConfigVar('alternate_feed_dir'));
    	if (!empty($path))
    		return true;
    	return false;
    }

    /**
     * Gets feed's filepath.
     *
     * @return string
     */
    public function getFeedPath() {

    	if (!$this->isAlternateFeedPath()) {
	    	$filepath = rtrim(Mage::getBaseDir(), DS) . DS . rtrim($this->getConfigVar('feed_dir'), DS) . DS;
    	} else {
    		if (is_null($this->_alternate_feed_dir)) {
    			$this->_setAlternateRealPath();
    		}
    		$filepath = $this->_alternate_feed_dir;
    	}
    	
    	if (!$this->getTestMode())
    		$name = sprintf($this->getConfigVar('feed_filename'), $this->getData('store_code'));
    	else
    		$name = sprintf($this->getConfigVar('test_feed_filename'), $this->getData('store_code'));
    	
    	return $filepath. $name;
    }

    /**
     * @return bool|string
     */
    protected function _setAlternateRealPath() {

    	$path = rtrim(Mage::getConfig()->getOptions()->getDir('base'), DS). DS. rtrim(trim($this->getConfigVar('alternate_feed_dir')), DS);

    	if (is_dir($path . DS))
    		$this->_alternate_feed_dir = $path . DS;
    	else
    		$this->_alternate_feed_dir = false;
    	
    	return $this->_alternate_feed_dir;
    }

    protected function initSavePath() {

    	if (!$this->isAlternateFeedPath()) {
	    	$path = dirname($this->getFeedPath());
	    	$ioAdapter = new Varien_Io_File();
			if (!is_dir($path)) {
				$ioAdapter->mkdir($path);
				if (!is_dir($path)) {
					Mage::throwException(sprintf('Not enough permissions, can\'t create dir [%s].', $path));
				}
			}
        } else {
        	if ($this->_setAlternateRealPath() === false) {
        		Mage::throwException(sprintf('Can\'t save feed. Dir [%s] doesn\'t exist.', rtrim(Mage::getConfig()->getOptions()->getDir('base'), DS). DS. rtrim(trim($this->getConfigVar('alternate_feed_dir')), DS)));
        	} else {
        		if (!is_writable(dirname($this->getFeedPath()))) {
        			Mage::throwException(sprintf('Can\'t save feed. Dir [%s] hasn\'t enough permissions.', dirname($this->getFeedPath())));
        		}
        	}
        }
    }

    /**
     * @return bool|null|resource
     */
    protected function getHandle() {

    	if ($this->_handle === null) {
    		$mode = "a";
    		if(!$this->getBatchMode() || ($this->getBatchMode() && $this->getBatch()->getIsNew()))
	    		$mode = "w";

    		$this->_handle = @fopen($this->getFeedPath(), $mode);
    		if ($this->_handle === false) {
				Mage::throwException(sprintf('Not enough permissions to write to file %s.', $this->getFeedPath()));
    		}
    	}
    	
    	return $this->_handle;
    }

    protected function closeHandle() {
    	@fclose($this->_handle);
    }
    
    public function getCountProductsExported() {
    	return $this->_count_products_exported;
    }
    
    public function getCountProductsSkipped() {
    	return $this->_count_products_skipped;
    }

    protected function getLogCountStep($total) {

    	$step = 1000;
    	if ($total <= 0) return $step;
    	if ($total >= 50000) $step = 2500;
    	elseif ($total >= 10000) $step = 1000;
    	elseif ($total >= 1000) $step = 100;
    	elseif ($total >= 500) $step = 50;
    	elseif ($total <= 500 && $step > 10) $step = 10;
    	else $step = 1;

    	return $step;
    }
    
    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Config
     */
    public function getConfig() {
    	return Mage::getSingleton('googlebasefeedgenerator/config');
    }
    
    /**
     * @param string $key
     * @param string $section
     * @return mixed
     */
    public function getConfigVar($key, $section = 'settings') {
    	return $this->getConfig()->getConfigVar($key, $this->getData('store_id'), $section);
    }
    
    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Tools
     */
    public function getTools() {
    	return Mage::getSingleton('googlebasefeedgenerator/tools')->setConfig($this->getConfig());
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    public function _getLog() {
    	return Mage::getSingleton('googlebasefeedgenerator/log');
    }

    /**
     * @param $msg
     * @param null $level
     * @param null $writer
     */
    public function log($msg, $level = null, $writer = null) {

    	if (is_null($level))
    		$level = Zend_Log::INFO;
    	if (!$this->hasData('force_log')) {
    		$this->setData('force_log', false);
    		if ($this->getConfigVar('force_log'))
    			$this->setData('force_log', true);
    	}
    	
    	if (!$this->hasData('log_filename'))
    		$this->setData('log_filename', sprintf($this->getConfigVar('log_filename'), $this->getData('store_code')));
		
    	if ($this->getBatchMode()) {
    		$msg = sprintf('[%s] '.$msg, $this->getBatch()->getScheduleId());
		}
		
		$m = memory_get_usage();
    	$msg = sprintf('(mem %s) ', $this->formatMemory($m)). $msg;
		
    	$options = array(
    		'file' => $this->getData('log_filename'),
    		'force' => $this->getData('force_log'),
    	);
    	$this->_getLog()->log($msg, $level, $writer, $options);
    	
    	if (!$this->getBatchMode()) {
    		$this->_getLog()->log($msg, $level, RocketWeb_GoogleBaseFeedGenerator_Model_Log::WRITER_MEMORY);
    	}

        unset($msg, $level, $writer, $m, $mem, $options);
    }

    /**
     * @param $memory
     * @return string
     */
    public function formatMemory($memory) {

        $units = array('b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb');
        $m = @round($memory / pow(1024, ($i = floor(log($memory, 1024)))), 2).' '.$units[$i];
        return sprintf('%4.2f %s', $m, $units[$i]);
    }

    /**
     * Wrapper for attribute cache in Tools object.
     *
     * @param $code
     * @return mixed|null
     */
    public function getAttribute($attributeCode) {
        return $this->getTools()->getAttribute($attributeCode);
    }

    /**
     * Wrapper for set attribute cache in Tools object
     *
     * @param $attribute
     */
    public function setAttribute($attribute) {
        return $this->getTools()->setAttribute($attribute);
    }


    public function __destruct() {
        @fclose($this->_storeLockFile);
    }

    /**
     * @return string
     */
    public function getLockPath() {
        return rtrim(dirname($this->getFeedPath()), DS). DS. sprintf($this->getConfigVar('store_lock_filename'), $this->getStoreCode());
    }

    /**
     * @return string
     */
    public function getBatchLockPath() {
        return rtrim(dirname($this->getFeedPath()), DS). DS. sprintf($this->getConfigVar('batch_lock_filename'), $this->getStoreCode());
    }

    /**
     * Implements the lock feed generation by store using the file system lock mechanism.
     * @return bool
     */
    public function acquireLock() {

        // Acquire an exclusive lock on file without blocking the script
        if (!flock($this->_storeLockFile, LOCK_EX | LOCK_NB)) {
            $this->log(sprintf('Can\'t acquire feed lock for store [%s]', $this->getStoreCode()). ($this->hasScheduleId() ? sprintf('script [%s]', $this->getScheduleId()) : ''), Zend_Log::ERR);
            $this->log(sprintf('Ensure write proper write permissions to [%s]', $this->getLockPath()));
            return false;
        }

        ftruncate($this->_storeLockFile, 0); // truncate file
        fwrite($this->_storeLockFile, date("Y-m-d H:i:s\n"));
        fflush($this->_storeLockFile);       // flush output before releasing the lock

        return true;
    }

    /**
     * Release the file lock
     * @return $this
     */
    public function releaseLock() {

        // Releasing the lock will also be done automatically when php runtime ends
        flock($this->_storeLockFile, LOCK_UN);
        return $this;
    }

    public function batchInProgress() {

        if ($mixed = @file_get_contents($this->getBatchLockPath())){
            $mixed = @unserialize($mixed);
            if (is_array($mixed) && (int)$mixed['offset'] < (int)$mixed['total'] - (int)$mixed['limit']) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    protected function _getProductTypes() {

        $product_types = $this->getConfigVar('product_types');
        return explode(",", $product_types);
    }

    /**
     * @param $type
     * @return bool
     */
    public function isProductTypeEnabled($type) {
        return in_array($type, $this->_getProductTypes());
    }
}