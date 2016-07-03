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

class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Configurable_Apparel extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Simple_Apparel {

	protected $_is_variants = false;
	protected $_variants_rows = false;
	protected $_original_variants_rows = false;
	protected $_assoc_ids;
	protected $_assocs;

    public function initialize() {
		parent::initialize();
		$this->setApparelCategories();
	}

    /**
     * Moved away from _beforeMap and _map to eliminate memory consumed for passing
     * assocMaps object and $assoc product objects as $this properties. This has a huge impact on
     * memory used by configurable products with large number of associated items
     *
     * @return array
     */
    public function map() {

        $rows = array();
        $parentRow = null;
        $this->_assocs = array();

        $stockStatusFlag = false; $stockStatus = false;
        $assocIds = $this->getAssocIds();

        foreach ($assocIds as $assocId) {

            $assoc = Mage::getModel('catalog/product');
            $assoc->setStoreId($this->getStoreId());
            $assoc->getResource()->load($assoc, $assocId);

            $this->_assocs[] = $assoc;
            if ($this->getGenerator()->getData('verbose')) {
                echo $this->getGenerator()->formatMemory(memory_get_usage(true)). " - Apparel Associated: ". $assoc->getId(). "\n";
            }

            $stockItem = Mage::getModel('cataloginventory/stock_item');
            $stockItem->setStoreId($this->getStoreId());
            $stockItem->getResource()->loadByProductId($stockItem, $assoc->getId());
            $stockItem->setOrigData();

            if ($stockItem->getId() && $stockItem->getIsInStock()) {
                $assoc->setData('quantity', $stockItem->getQty());
                $stock = $this->getConfig()->getInStockStatus();
            } else {
                $assoc->setData('quantity', 0);
                $stock = $this->getConfig()->getOutOfStockStatus();
            }

            // Set stock status of the current item and check if the status has changed
            if ($stockStatus != false && $stock != $stockStatus) {
                $stockStatusFlag = true;
            }
            $stockStatus = $stock;
        }

        if ($this->getConfig()->isAllowApparelConfigurableMode($this->getStoreId())) {
            if (!$this->isSkip()) {
                $data = $this->getData();
                $data['is_apparel'] = false; $data['is_apparel_clothing'] = false; $data['is_apparel_shoes'] = false;
                $pMap = $this->getGenerator()->getProductMapModel($this->getProduct()->getTypeId())
                        ->setData($data)
                        ->setSkipAssocs(true)
                        ->setIsApparel(true)
                        ->setColumnsMap($this->_columns_map)
                        ->setEmptyColumnsReplaceMap($this->_empty_columns_replace_map);

                // Set configurable stock status if all assocs have the same stock status
                if ($stockStatus && !$stockStatusFlag) {
                    $pMap->setAssociatedStockStatus($stockStatus);
                    if ($stockStatus == $this->getConfig()->getOutOfStockStatus() && !$this->getConfigVar('add_out_of_stock')) {
                        $this->setSkip(sprintf("product id %d sku %s, configurable apparel, skipped - out of stock.", $this->getProduct()->getId(), $this->getProduct()->getSku()));
                    }
                }

                if (!$this->isSkip()) {
                    $row = $pMap->map();
                    if (count($row)) {
                        reset($row);
                        if (!$this->getConfigVar('allow_empty_color_size', 'apparel')) {
                            $row = $this->formUsConfigurableNonVariant($row); // As stand alone apparel product - no variants.
                        } else {
                            $row = $this->formOtherConfigurableNonVariant($row);
                        }
                        $parentRow = current($row);
                    }
                }
            }
        }

        // Start BeforeMap
        $this->_variants_rows = array();
        $this->flushCacheAssociatedPrice();

        foreach ($this->_assocs as $assoc) {
            $assocId  = $assoc->getId();
            $assocSku = $assoc->getSku();

            if (!$this->getConfigVar('add_out_of_stock_configurable_assoc') && !$assoc->getData('quantity')) {
                if ($this->getConfigVar('log_skip')) {
                    $this->log(sprintf("product id %d sku %s, configurable apparel item, skipped - out of stock", $assocId, $assocSku));
                }
                unset($assoc);
                continue;
            }

            if (!($this->setCacheAssociatedPricesByProduct($assoc) === true)) {
                if ($this->getConfigVar('log_skip')) {
                    $this->log(sprintf("product id %d sku %s, configurable apparel item, skipped - could not set price cache", $assocId, $assocSku));
                }
                unset($assoc);
                continue;
            }

            $assocMap = $this->getAssocMapModel($assoc);
            if ($assocMap->checkSkipSubmission()->isSkip()) {
                if ($this->getConfigVar('log_skip')) {
                    $this->log(sprintf("product id %d product sku %s, configurable apparel item, skipped - product has 'Skip from Being Submitted' = 'Yes'.", $assocId, $assocSku));
                }
                continue;
            }

            // Start Map
            $row = $assocMap->map();

            if (!$assocMap->isSkip()) {
                reset($row);
                $row = current($row);

                // Overwrite price with configurable price if no option price set
                if ($assocMap->getProduct()->hasOptionPrice() &&
                    !$assocMap->getProduct()->getOptionPrice() && $parentRow) {
                    $row['price'] = $parentRow['price'];
                }

                if (!$assocMap->isSkip()) {
                    $this->_variants_rows[] = $row;
                }
                $rows[] = $row;
            }
        }
        // End BeforeMap

        // Finish Map
        $this->_original_variants_rows = $this->_variants_rows;
        $this->_is_variants = count($this->_variants_rows) ? $this->validateVariants($this->_variants_rows) : false;

        if ($this->_is_variants) {
            $rows = $this->_variants_rows;
        }

        // Fill in parent columns specified in $inherit_columns with values list from associated items
        if ($parentRow && count($parentRow)) {
            $this->mergeVariantValuesToParent($parentRow, $rows);
            array_unshift($rows, $parentRow);
        }

        return $this->_afterMap($rows);
    }
    
    /**
     * Array with associated products ids in current store.
     *
     * @return array
     */
    public function getAssocIds() {

    	if (is_null($this->_assoc_ids))
			$this->_assoc_ids = $this->loadAssocIds($this->getProduct(), $this->getStoreId());
		return $this->_assoc_ids;
    }

    /**
     * @param $rows
     * @return $this
     */
    public function _afterMap($rows) {

        // Free some memory
        foreach ($this->_assocs as $assoc) {
            if ($assoc->getEntityid()) {
                $this->getTools()->clearNestedObject($assoc);
            }
        }
        $this->flushCacheAssociatedPrice();

		if (!$this->_is_variants && $rows) {
			return parent::_afterMap($rows);
		}
		return $rows;
	}
	
	/**
     * @param Mage_Catalog_Model_Product $product
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract
     */
    protected function getAssocMapModel($product) {

    	$params = array(
    		'store_code' => $this->getData('store_code'),
    		'store_id' => $this->getData('store_id'),
    		'website_id' => $this->getData('website_id'),
    	);
    	
    	$productMap = Mage::getModel('googlebasefeedgenerator/map_product_associated_apparel', $params);
    	$productMap->setProduct($product)
			->setColumnsMap($this->_columns_map)
			->setEmptyColumnsReplaceMap($this->_empty_columns_replace_map)
			->setParentMap($this)
            ->setCacheAssociatedPrices($this->getCacheAssociatedPrices())
			->initialize();
    	
    	return $productMap;
    }
    
    /**
     * @param array $params
     * @return string
     */
    protected function mapAttributeWeight($params = array()) {

    	$map = $params['map'];
    	$product = $this->getProduct();
    	/** @var $product Mage_Catalog_Model_Product */
    	

    	// Get attribute value
    	$weight_attribute = $this->getGenerator()->getAttribute($map['attribute']);
		if ($weight_attribute === false)
			Mage::throwException(sprintf('Couldn\'t find attribute \'%s\'.', $map['attribute']));
		
		$weight = $this->getAttributeValue($product, $weight_attribute);
		if ($weight != "")
			$weight .= ' '.$this->getConfigVar('weight_unit_measure', 'columns');
		
		// Configurable doesn't have weight of it's own.
		if ($weight == "") {
			$min_price = PHP_INT_MAX;
			foreach ($this->_assocs as $assoc) {
				if ($this->getCacheAssociatedPrice($assoc->getId()) !== false && $min_price > $this->getCacheAssociatedPrice($assoc->getId())) {
					$min_price = $this->getCacheAssociatedPrice($assoc->getId());
					$weight = $this->getAttributeValue($assoc, $weight_attribute);
					break;
				}
			}
		}

        if ($weight != "") {
            if (strpos($weight, $this->getConfigVar('weight_unit_measure', 'columns')) === false) {
                $weight .= ' '.$this->getConfigVar('weight_unit_measure', 'columns');
            }
            return $this->cleanField($weight, $params);
        }

        // Get Static Value
        $weight = $map['default_value'];
        $weight .= ' '.$this->getConfigVar('weight_unit_measure', 'columns');
        return $this->cleanField($weight, $params);
    }

    /**
     * Note: Fix image map in configurable product, it should really not replicate the "Associated products - Fetch main image from" logic.
     * We keep the FROM_ASSOCIATED_CONFIGURABLE logic here to make sure there is an image in case the associate has no image,
     * but all other case should just be $this->getCellValue since this is not an associated product but the configurable itself.
     *
     * @param array $params
     * @return mixed
     */
    public function mapColumnImageLink($params = array()) {

        $args = array('map' => $params['map']);

        switch ($this->getConfigVar('associated_products_image_link_configurable', 'columns')) {
            case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsimagelink::FROM_ASSOCIATED_CONFIGURABLE:
                $value = $this->getCellValue($args);
                if ($value == '' && $this->getParentMap()) {
                    $value = $this->getParentMap()->mapColumn('image_link');
                }
                break;
            default:
                $value = $this->getCellValue($args);
        }

        return $value;
    }

    /**
     * Check the value of apparel rows that should vary to have different values.
     * Returns: false, if no combination varies meaning the product is a non-variant apparel
     *
     * @param $rows
     * @return bool
     */
    public function validateVariants(&$rows) {

    	$is_variants = true;
    	$only_varies = array();
    	$only_varies_all = array('material', 'pattern');
    	foreach ($only_varies_all as $column) {
    		if (isset($this->_columns_map[$column]))
    			$only_varies[] = $column;
    	}
    	unset($only_varies_all);
    	
    	$changed = false;
    	foreach ($only_varies as $column) {
    		$tt = array();
	    	foreach ($rows as $line => $row){
	    		if (isset($row[$column]) && $row[$column] != "") {
	    			$tt[strtolower($row[$column])] = true;
                }
	    	}
	    	
	    	// Doesn't vary, show warning.
	    	if (count($tt) <= 1) {
                $this->log(sprintf("product id %d, sku %s - configurable apparel, values of '%s' column should vary throughout associated items.", $this->getProduct()->getId(), $this->getProduct()->getSku(), $column), Zend_Log::WARN);
	    	}
    	}
    	
    	reset($rows);
    	$row = current($rows);
    	$gb_category = (isset($row['google_product_category']) ? $row['google_product_category'] : "");
    	$must_vary = array($this->_color_column_name);
    	if (isset($this->_columns_map['material'])) {
			$must_vary[] = 'material';
        }
		if (isset($this->_columns_map['pattern'])) {
			$must_vary[] = 'pattern';
        }
		
    	if ($this->matchApparelClothingCategory($gb_category) || $this->matchApparelShoesCategory($gb_category)) {
    		$must_vary[] = 'size';
        }

        // Add attributes_codes optional to a may vary logic, so that rows that vary on other attributes are not skipped
        $may_vary = array();
        $may_vary_columns = explode(",", $this->getConfigVar('variant_additional_columns', 'apparel'));
        foreach($may_vary_columns as $column) {
            $column = trim($column);
            if (array_key_exists($column, $this->_columns_map)) {
                $may_vary[] = $column;
            }
        }

    	// More than 1 line for a combination of variants values => choose by image and minimal price.
    	$variants_values = array();
    	$configurable_image = $this->mapColumn('image_link');
    	foreach ($rows as $line => $row) {
    		$tt = "";
            $missing_columns = '';
			foreach ($must_vary as $column) {
				if (array_key_exists($column, $row)) {
                    $tt .= $row[$column];
                } else {
                    $missing_columns .= $column. ',';
                }
            }
            foreach ($may_vary as $column) {
                if (array_key_exists($column, $row)) {
                    $tt .= " | ". $row[$column];
                }
            }
            if ($missing_columns) {
                $this->log('Product id:'. $row['id']. ' is missing the apparel variant columns "'. $missing_columns. '" Please add those columns to the feed.');
            }
			if (!isset($variants_values[$tt])) {
				$variants_values[$tt] = array();
            }
			$variants_values[$tt][] = $line;
    	}
    	
    	foreach ($variants_values as $v) {
    		if (count($v) > 0) {
    			$keep = false;
    			$minimal_price = PHP_INT_MAX;

                // It should have image and minimal price.
    			foreach ($v as $line) {
    				if ((!$this->getConfigVar('variant_submit_no_img', 'apparel') && isset($rows[$line]['image_link']) && $rows[$line]['image_link'] != "") && $rows[$line]['price'] < $minimal_price) {
    					$keep = $line;
    					$minimal_price = $rows[$line]['price'];
    				}
    			}
    			
    			if ($keep !== false) {
    				foreach ($v as $line) {
    					if ($keep !== $line) {
                            if ($this->getConfigVar('auto_skip')) {
                                unset($rows[$line]);
                                $changed = true;
                                $this->log("Skipped Variant - ". $row['id']. " is missing image_link\n");
                                if ($this->getGenerator()->getData('verbose')) {
                                    echo "Skipped Variant - ". $row['id']. " is missing image_link\n";
                                }
                            }
    					}
    				}
    			}
    		}
    	}

        // No image -> get configurable image.
    	if ($this->getConfigVar('submit_no_img', 'apparel') && $this->getConfigVar('variant_submit_no_img', 'apparel')) {
	    	foreach ($rows as $line => $row) {
	    		if (isset($rows[$line]['image_link']) && $rows[$line]['image_link'] == "") {
	    			$rows[$line]['image_link'] = $configurable_image;
	    		}
	    	}
    	}

        // Should have at least configurable image.
    	if (!$this->getConfigVar('submit_no_img', 'apparel')) {
    		$crows = $rows;
    		foreach ($crows as $line => $row) {
    			if (!isset($row['image_link']) || (isset($row['image_link']) && $row['image_link'] == "")) {
                    if ($this->getGenerator()->getData('verbose')) {
                        echo "Skipped Variant - ". $row['id']. " is missing image_link\n";
                    }
	    			unset($rows[$line]);
	    			$changed = true;
	    		}
    		}
    	}

        // no variants, clear item_group_id
    	if (count($rows) <= 1) {
    		$is_variants = false;
    		foreach ($rows as $line => $row) {
    			if (isset($row['item_group_id']))
    				$rows[$line]['item_group_id'] = "";
    		}
    	} else {
    		if ($changed) {
    			// Change title and description with configurable data
    			$varies = array($this->_color_column_name);
    			if ($this->matchApparelClothingCategory($gb_category) || $this->matchApparelShoesCategory($gb_category))
    				$varies[] = 'size';
    			
	    		if (isset($this->_columns_map['material']))
					$varies[] = 'material';
				if (isset($this->_columns_map['pattern']))
					$varies[] = 'pattern';
    			
    			$parent_title = $this->mapColumn('title');
    			$parent_description = $this->mapColumn('description');
    			foreach ($rows as $line => $row) {
    				if (isset($row['description']) && $row['description'] == "")
    					$rows[$line]['description'] = $parent_description;
    				
    				if (isset($row['title']))
    					$rows[$line]['title'] = $parent_title;
    			}
    		}
    	}
    	
    	return $is_variants;
    }

    /**
     * If empty color or size try replace configurable with valid associated product color/size/price that has minimal price.
     *
     * @param $rows
     * @return array
     */
    protected function formUsConfigurableNonVariant($rows) {

    	reset($rows);
    	$fields = current($rows);
    	$gb_category = (isset($fields['google_product_category']) ? $fields['google_product_category'] : "");

    	$must_have = array($this->_color_column_name);
    	if ($this->matchApparelClothingCategory($gb_category) || $this->matchApparelShoesCategory($gb_category)) {
            $must_have[] = 'size';
        }
    	
    	// If empty color or size try replace configurable with valid associated product color/size/price that has minimal price.
    	$minimal_price = PHP_INT_MAX;
    	$keep = false;
    	if (is_array($this->_original_variants_rows) && ((isset($fields[$this->_color_column_name]) && $fields[$this->_color_column_name] == "")
                                                          || (array_search("size", $must_have) !== false && isset($fields['size']) && $fields['size'] == ""))) {
    		foreach ($this->_original_variants_rows as $line => $row) {
    			$all = true;
    			foreach ($must_have as $column) {
    				if (!isset($row[$column]) || (isset($row[$column]) && $row[$column] == ""))
    				$all = false;
    			}
    			
    			if ($all && $row['price'] < $minimal_price) {
    				$keep = $line;
    				$minimal_price = $row['price'];
    			}
    		}
    	}
    	
    	if ($keep !== false && $this->_original_variants_rows[$keep]['image_link'] == "") {
    		// Get configurable image.
    		$configurable_image = $this->mapColumn('image_link');
    		if ($configurable_image != "") {
    			$this->_original_variants_rows[$keep]['image_link'] = $configurable_image;
    		} else {
    			$keep = false;
    		}
    	}
    	
    	if ($keep !== false) {
    		if (isset($fields[$this->_color_column_name]) && $fields[$this->_color_column_name] == "")
    			$fields[$this->_color_column_name] = $this->_original_variants_rows[$keep][$this->_color_column_name];
    		
    		if (isset($fields['size']) && $fields['size'] == "")
    			$fields['size'] = $this->_original_variants_rows[$keep]['size'];
    		
    		if ($this->_original_variants_rows[$keep]['price'] > 0)
    			$fields['price'] = $this->_original_variants_rows[$keep]['price'];
    		
    		if ($this->_original_variants_rows[$keep]['sale_price'] > 0) {
    			$fields['sale_price'] = $this->_original_variants_rows[$keep]['sale_price'];
    			if ($this->_original_variants_rows[$keep]['sale_price_effective_date'] != "")
    				$fields['sale_price_effective_date'] = $this->_original_variants_rows[$keep]['sale_price_effective_date'];
    		}
    		
    		// Configurable does not have weight of it's own, fill with child's weight.
    		if (isset($fields['shipping_weight'])) {
    			$fields['shipping_weight'] = $this->_original_variants_rows[$keep]['shipping_weight'];
    		}
    	} else {
    		// Pass intact configurable values.
    	}
    	
    	return array($fields);
    }

    /**
     * @param $rows
     * @return array
     */
    protected function formOtherConfigurableNonVariant($rows) {

    	reset($rows);
    	$fields = current($rows);
    	
    	// compact apparel fields
    	$varies = array($this->_color_column_name, 'size', 'material', 'pattern', 'gender', 'age_group');
    	foreach ($varies as $column) {
    		if (isset($fields[$column])) {
    			$values = array();
    			if ($fields[$column] != "") {
    				$arr = explode(",", $fields[$column]);
    				foreach ($arr as $k => $v)
    					$values[trim($v)] = trim($v);
    			}
    			if ($this->_is_variants && $this->_variants_rows) {
                    foreach ($this->_variants_rows as $line => $row) {
                        if (isset($row[$column]) && $row[$column] != "") {
                            $arr = explode(",", $row[$column]);
                            foreach ($arr as $k => $v)
                                $values[trim($v)] = trim($v);
                        }
                    }
                }
				
				$fields[$column] = implode(",", $values);
    		}
    	}

    	return array($fields);
    }
    
    /**
     * Redundant code with RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Configurable
     *
     * @return float
     */
    public function getPrice($product = null) {

    	if (is_null($product)) {
    		$product = $this->getProduct();
    	}

        $price = 0;
    	if (!$this->hasSpecialPrice($product, $this->getSpecialPrice($product))) {
    		$price = $this->calcMinimalPrice($product);
    	}

    	if ($price <= 0) {
            $price = $product->getPrice();
		}

        if ($price <=0) {
            $this->setSkip(sprintf("product id %d, sku %s - configurable apparel, can't determine price.", $product->getId(), $product->getSku()));
        }
		
		return $price;
    }
    
    /**
     * Redundant code with RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Configurable
     *
     * @return float
     */
    public function calcMinimalPrice($product) {

    	$price = 0.0;
    	$minimal_price = PHP_INT_MAX;
		foreach ($this->_assocs as $assoc) {
			if ($minimal_price > $this->getCacheAssociatedPrice($assoc->getId())) {
				$minimal_price = $this->getCacheAssociatedPrice($assoc->getId());
			}
		}
		if ($minimal_price < PHP_INT_MAX) {
			$price = $minimal_price;
		}
		
		return $price;
    }


    public function getIsVariants() {
    	$this->_is_variants;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function mapDirectiveAvailability($params = array()) {

        $map = $params['map'];

        // Set the Static Value / overwrite
        $default_value = isset($map['default_value']) ? $map['default_value'] : "";
        if ($default_value != "") {
            $stock_status = trim(strtolower($default_value));
            if (array_search($stock_status, $this->getConfig()->getAllowedStockStatuses()) === false) {
                $stock_status = $this->getConfig()->getOutOfStockStatus();
            }
            return $this->cleanField($stock_status, $params);
        }

        // Set the computed configurable stock status
        if ($this->hasAssociatedStockStatus() && $this->getAssociatedStockStatus() == $this->getConfig()->getOutOfStockStatus()) {
            return $this->cleanField($this->getAssociatedStockStatus(), $params);
        }

        return parent::mapDirectiveAvailability($params);
    }
}
