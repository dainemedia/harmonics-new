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

class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Grouped extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract {

    protected $_assoc_ids;
	protected $_assocs;

    /**
     * @return $this
     */
    public function _beforeMap() {

		$this->_assocs = array();
		foreach ($this->getAssocIds() as $assocId) {

            $assoc = Mage::getModel('catalog/product');
            $assoc->setStoreId($this->getStoreId());
            $assoc->getResource()->load($assoc, $assocId);
            $assoc->setData('quantity', 0);

            if ($this->getGenerator()->getData('verbose')) {
                echo $this->getGenerator()->formatMemory(memory_get_usage(true)). " - Grouped Associated: ". $assoc->getEntityId(). "\n";
            }

            $stock = $this->getConfig()->getOutOfStockStatus();

            if (!$this->getConfigVar('use_default_stock', 'columns')) {
                $stock_attribute = $this->getGenerator()->getAttribute($this->getConfigVar('stock_attribute_code', 'columns'));
                if ($stock_attribute === false)
                    Mage::throwException(sprintf('Couldn\'t find attribute \'%s\'.', $this->getConfigVar('stock_attribute_code', 'columns')));

                $stock = trim(strtolower($this->getAttributeValue($assoc, $stock_attribute)));
                if (array_search($stock, $this->getConfig()->getAllowedStockStatuses()) === false) {
                    $stock = $this->getConfig()->getOutOfStockStatus();
                }
            } else {
                $stockItem = Mage::getModel('cataloginventory/stock_item');
                $stockItem->setStoreId($this->getStoreId());
                $stockItem->getResource()->loadByProductId($stockItem, $assoc->getId());
                $stockItem->setOrigData();

                if ($stockItem->getId() && $stockItem->getIsInStock()) {
                    $assoc->setData('quantity', $stockItem->getQty());
                    $stock = $this->getConfig()->getInStockStatus();
                }

                // Clear stockItem memory
                unset($stockItem->_data);
                $this->getTools()->clearNestedObject($stockItem);
            }

            // Append assoc considering the appropriate stock status
            if ($this->getConfigVar('add_out_of_stock_grouped_assoc')) {
                $this->_assocs[$assocId] = $assoc;
            } elseif ($stock == $this->getConfig()->getInStockStatus()) {
                $this->_assocs[$assocId] = $assoc;
            } else {
                // Set skip messages
                if ($this->getConfigVar('log_skip')) {
                    $this->log(sprintf("product id %d sku %s, grouped associated, skipped - out of stock", $assocId, $assoc->getSku()));
                }
            }
        }

        $assocMapArr = array();
		foreach ($this->_assocs as $assoc) {
			$assocMap = $this->getAssocMapModel($assoc);
			if ($assocMap->checkSkipSubmission()->isSkip()) {
				unset($this->_assocs[$assoc->getEntityId()]);
	    		continue;
			}
			$assocMapArr[$assoc->getEntityId()] = $assocMap;
		}
		$this->setAssocMaps($assocMapArr);

		if (count($assocMapArr) <= 0) {
            $this->setSkip(sprintf("product id %d product sku %s, skipped - All associated products of the grouped product are disabled or out of stock.", $this->getProduct()->getId(), $this->getProduct()->getSku()));
		}

    	return parent::_beforeMap();
    }

    /**
     * @return array
     */
    public function _map() {

		$rows = array();
		
		if ($this->getConfig()->isAllowGroupedMode($this->getStoreId())) {
			if (!$this->isSkip()) {
				$row = parent::_map();
				reset($row);
				$row = current($row);
				$rows[] = $row;
			}
		}
		
		if ($this->getConfig()->isAllowGroupedAssociatedMode($this->getStoreId())) {
			foreach ($this->getAssocMaps() as $assocId => $assocMap) {
				$row = $assocMap->map();
				reset($row);
				$row = current($row);
				if (!$assocMap->isSkip())
					$rows[] = $row;
			}
		}
		
		return $rows;
	}

    /**
     * @param $rows
     * @return array
     */
    public function _afterMap($rows) {

        // Free some memory
        foreach ($this->_assocs as $assoc) {
            $this->getTools()->clearNestedObject($assoc);
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
    	
    	$productMap = Mage::getModel('googlebasefeedgenerator/map_product_grouped_associated', $params);
    	
    	$productMap->setProduct($product)
			->setColumnsMap($this->_columns_map)
			->setEmptyColumnsReplaceMap($this->_empty_columns_replace_map)
			->setParentMap($this)
            ->setCacheAssociatedPrices($this->getCacheAssociatedPrices())
			->initialize();
    	
    	return $productMap;
    }
    
	/**
	 * @return float
	 */
	public function getPrice($product = null) {

    	if (is_null($product)) {
    		$product = $this->getProduct();
    	}

		$price = $this->calcMinimalPrice($product);
		if ($price <= 0) {
            $price = $product->getPrice();
		}

        if ($price <=0) {
            $this->setSkip(sprintf("product id %d, sku %s - grouped, can't determine price.", $product->getId(), $product->getSku()));
        }

		return $price;
    }

    /**
     * @param $product
     * @return float|mixed
     */
    public function calcMinimalPrice($product) {

    	$assocMapArr = $this->getAssocMaps();
    	$price = 0.0;
    	$has_default_qty = $this->_hasDefaultQty($assocMapArr);

    	$display_min = $this->getConfigVar('grouped_price_display_mode', 'columns');
    	if ($has_default_qty) {
    		if ($display_min == RocketWeb_GoogleBaseFeedGenerator_Model_Source_Pricegroupedmode::PRICE_SUM_DEFAULT_QTY) {
    			$price = 0.0;
    			foreach ($assocMapArr as $assocMap) {
    				$associatedProduct = $assocMap->getProduct();
    				if ($associatedProduct->getQty() > 0) {
    					$assoc_price = $assocMap->getPrice($associatedProduct) * $associatedProduct->getQty();
    					$price += $assoc_price;
    				}
    			}
    		} else {
    			// RocketWeb_GoogleBaseFeedGenerator_Model_Source_Pricegroupedmode::PRICE_START_AT
    			$minAssocMap = $this->findMinimalPriceProduct($assocMapArr);
    			if ($minAssocMap === false) {
    				return $price;
    			}
    			$price = $minAssocMap->getPrice($minAssocMap->getProduct());

    		}
    	} else {
    		// RocketWeb_GoogleBaseFeedGenerator_Model_Source_Pricegroupedmode::PRICE_START_AT
    		$minAssocMap = $this->findMinimalPriceProduct($assocMapArr);
    		if ($minAssocMap === false) {
				return $price;
			}
    		$price = $minAssocMap->getPrice($minAssocMap->getProduct());
    	}

		return $price;
    }
    
    /**
	 * @return float
	 */
	public function getSpecialPrice($product = null) {

    	if (is_null($product)) {
    		$product = $this->getProduct();
    	}
    	
		$price = $this->calcMinimalSpecialPrice($product);
		if ($price <= 0) {
			$price = 0.0;
		}
		
		return $price;
    }
    
    public function calcMinimalSpecialPrice($product) {

    	$assocMapArr = $this->getAssocMaps();
    	$price = 0.0;
    	$has_default_qty = $this->_hasDefaultQty($assocMapArr);

    	$display_min = $this->getConfigVar('grouped_price_display_mode', 'columns');
    	if ($has_default_qty) {
    		if ($display_min == RocketWeb_GoogleBaseFeedGenerator_Model_Source_Pricegroupedmode::PRICE_SUM_DEFAULT_QTY) {
    			$price = 0.0;
    			foreach ($assocMapArr as $assocMap) {
    				$associatedProduct = $assocMap->getProduct();
    				if ($associatedProduct->getQty() > 0) {
    					if ($assocMap->hasSpecialPrice($associatedProduct, $assocMap->getSpecialPrice($associatedProduct))) {
		    				$assoc_price = $assocMap->getSpecialPrice($associatedProduct);
		    			} else {
		    				$assoc_price = $assocMap->getPrice($associatedProduct);
		    			}
    					$price += $assoc_price * $associatedProduct->getQty();
    				}
    			}
    		} else {
    			// RocketWeb_GoogleBaseFeedGenerator_Model_Source_Pricegroupedmode::PRICE_START_AT
    			$minAssocMap = $this->findMinimalPriceProduct($assocMapArr);
    			if ($minAssocMap === false) {
    				return $price;
    			}
    			$associatedProduct = $minAssocMap->getProduct();
    			if ($minAssocMap->hasSpecialPrice($associatedProduct, $minAssocMap->getSpecialPrice($associatedProduct))) {
    				$price = $minAssocMap->getSpecialPrice($associatedProduct);
    			} else {
    				$price = 0.0;
    			}
    		}
    	} else {
    		// RocketWeb_GoogleBaseFeedGenerator_Model_Source_Pricegroupedmode::PRICE_START_AT
    		$minAssocMap = $this->findMinimalPriceProduct($assocMapArr);
    		if ($minAssocMap === false) {
				return $price;
			}
			$associatedProduct = $minAssocMap->getProduct();
			if ($minAssocMap->hasSpecialPrice($associatedProduct, $minAssocMap->getSpecialPrice($associatedProduct))) {
				$price = $minAssocMap->getSpecialPrice($associatedProduct);
			} else {
				$price = 0.0;
			}
    	}
		
		return $price;
    }
    
    public function hasSpecialPrice($product, $special_price) {

    	$has = false;
    	$product = $this->getProduct();
    	$assocMapArr = $this->getAssocMaps();
    	$special_price = $this->calcMinimalPrice($product);
    	if ($special_price <= 0)
    		return $has;
    	
    	$has_default_qty = $this->_hasDefaultQty($assocMapArr);
    	$display_min = $this->getConfigVar('grouped_price_display_mode', 'columns');
    	
    	if ($has_default_qty) {
    		if ($display_min == RocketWeb_GoogleBaseFeedGenerator_Model_Source_Pricegroupedmode::PRICE_SUM_DEFAULT_QTY) {
    			foreach ($assocMapArr as $assocMap) {
    				$associatedProduct = $assocMap->getProduct();
    				if ($associatedProduct->getQty() > 0) {
    					if ($assocMap->hasSpecialPrice($associatedProduct, $assocMap->getSpecialPrice($associatedProduct))) {
		    				$has = true;
		    				break;
		    			}
    				}
    			}
    		} else {
    			// RocketWeb_GoogleBaseFeedGenerator_Model_Source_Pricegroupedmode::PRICE_START_AT
    			$minAssocMap = $this->findMinimalPriceProduct($assocMapArr);
    			if ($minAssocMap === false) {
    				return false;
    			}
				$associatedProduct = $minAssocMap->getProduct();
				if ($minAssocMap->hasSpecialPrice($associatedProduct, $minAssocMap->getSpecialPrice($associatedProduct))) {
					$has = true;
				}
    		}
    	} else {
    		// RocketWeb_GoogleBaseFeedGenerator_Model_Source_Pricegroupedmode::PRICE_START_AT
    		$minAssocMap = $this->findMinimalPriceProduct($assocMapArr);
    		if ($minAssocMap === false) {
				return false;
			}
			$associatedProduct = $minAssocMap->getProduct();
			if ($minAssocMap->hasSpecialPrice($associatedProduct, $minAssocMap->getSpecialPrice($associatedProduct))) {
				$has = true;
			}
    	}

    	return $has;
    }
    
    protected function mapDirectiveSalePriceEffectiveDate($params = array()) {

        /** @var $product Mage_Catalog_Model_Product */
    	$product = $this->getProduct();
    	$assocMapArr = $this->getAssocMaps();
    	
    	$cDate = Mage::app()->getLocale()->date(null, null, Mage::app()->getLocale()->getDefaultLocale());
		$timezone = Mage::app()->getStore($this->getStoreId())->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
    	
    	$cell = "";
		if (!$this->hasSpecialPrice($product, $this->getSpecialPrice($product)))
			return $cell;
		
		$has_default_qty = $this->_hasDefaultQty($assocMapArr);
    	$display_min = $this->getConfigVar('grouped_price_display_mode', 'columns');
    	
    	if ($has_default_qty) {
    		if ($display_min == RocketWeb_GoogleBaseFeedGenerator_Model_Source_Pricegroupedmode::PRICE_SUM_DEFAULT_QTY) {
    			$product_from_date = $product_to_date = null;
    			$from_time = -1; $to_time = PHP_INT_MAX;
    			foreach ($assocMapArr as $assocMap) {
    				$associatedProduct = $assocMap->getProduct();
    				if ($associatedProduct->getQty() > 0) {
    					if ($assocMap->hasSpecialPrice($associatedProduct, $assocMap->getSpecialPrice($associatedProduct))) {
    						if ($assocMap->hasPriceByCatalogRules($associatedProduct)) {
					    		// Current date to cdate + ttl
					    		$fromDate = clone $cDate;
    							$fromDate->setTime('00:00:00', 'HH:mm:ss');
								$toDate = new Zend_Date(null, null, Mage::app()->getLocale()->getDefaultLocale());
								if ($timezone) $toDate->setTimezone($timezone);
								$toDate->setDate($cDate->toString('yyyy-MM-dd'), 'yyyy-MM-dd');
								$toDate->setTime('23:59:59', 'HH:mm:ss');
								$toDate->add((int) $this->getConfigVar('ttl', 'columns'), Zend_Date::DAY);
								
								$compare_from_date = $fromDate->toString('yyyy-MM-dd 00:00:00');
								$compare_to_date = $toDate->toString('yyyy-MM-dd 23:59:59');
					    	} else {
					    		$compare_from_date = $associatedProduct->getSpecialFromDate();
					    		$compare_to_date = $associatedProduct->getSpecialToDate();
					    	}
					    	
		    				if ($from_time < $this->dateToTime($compare_from_date)) {
		    					$product_from_date = $compare_from_date;
		    					$from_time = $this->dateToTime($product_from_date);
		    				}
		    				
		    				if (!is_empty_date($compare_to_date) && $to_time > $this->dateToTime($compare_to_date)) {
		    					$product_to_date = $compare_to_date;
		    					$to_time = $this->dateToTime($product_to_date);
		    				}
		    			}
    				}
    			}
    		} else {
    			// RocketWeb_GoogleBaseFeedGenerator_Model_Source_Pricegroupedmode::PRICE_START_AT
    			$minAssocMap = $this->findMinimalPriceProduct($assocMapArr);
    			if ($minAssocMap === false) {
    				return $cell;
    			}
				$associatedProduct = $minAssocMap->getProduct();
				if ($minAssocMap->hasPriceByCatalogRules($associatedProduct)) {
		    		// Current date to cdate + ttl
		    		$fromDate = clone $cDate;
    				$fromDate->setTime('00:00:00', 'HH:mm:ss');
					$toDate = new Zend_Date(null, null, Mage::app()->getLocale()->getDefaultLocale());
					if ($timezone) $toDate->setTimezone($timezone);
					$toDate->setDate($cDate->toString('yyyy-MM-dd'), 'yyyy-MM-dd');
					$toDate->setTime('23:59:59', 'HH:mm:ss');
					$toDate->add((int) $this->getConfigVar('ttl', 'columns'), Zend_Date::DAY);
					
					$compare_from_date = $fromDate->toString('yyyy-MM-dd 00:00:00');
					$compare_to_date = $toDate->toString('yyyy-MM-dd 23:59:59');
		    	} else {
		    		$compare_from_date = $associatedProduct->getSpecialFromDate();
		    		$compare_to_date = $associatedProduct->getSpecialToDate();
		    	}
					    	
				$product_from_date = $compare_from_date;
				$product_to_date = $compare_to_date;
				
    		}
    	} else {
    		// RocketWeb_GoogleBaseFeedGenerator_Model_Source_Pricegroupedmode::PRICE_START_AT
			$minAssocMap = $this->findMinimalPriceProduct($assocMapArr);
			if ($minAssocMap === false) {
				return $cell;
			}
			$associatedProduct = $minAssocMap->getProduct();
			$product_from_date = $associatedProduct->getSpecialFromDate();
			$product_to_date = $associatedProduct->getSpecialToDate();
    	}
    	
		$fromDate = new Zend_Date(null, null, Mage::app()->getLocale()->getDefaultLocale());
    	if ($timezone) $fromDate->setTimezone($timezone);
        if ($product_from_date) {
            $fromDate->setDate(substr($product_from_date, 0, 10), 'yyyy-MM-dd');
            $fromDate->setTime(substr($product_from_date, 11, 8), 'HH:mm:ss');
        }
    	
    	$toDate = new Zend_Date(null, null, Mage::app()->getLocale()->getDefaultLocale());
    	if (!is_empty_date($product_to_date)) {
			if ($timezone) $toDate->setTimezone($timezone);
	    	$toDate->setDate(substr($product_to_date, 0, 10), 'yyyy-MM-dd');
	    	$toDate->setTime('23:59:59', 'HH:mm:ss');
		} else {
			if ($timezone) $toDate->setTimezone($timezone);
			$toDate->setDate($cDate->toString('yyyy-MM-dd'), 'yyyy-MM-dd');
			$toDate->setTime('23:59:59', 'HH:mm:ss');
			$toDate->add((int) $this->getConfigVar('ttl', 'columns'), Zend_Date::DAY);
		}
		
		$cell = $fromDate->toString(Zend_Date::ISO_8601)."/".$toDate->toString(Zend_Date::ISO_8601);
    	return $cell;
    }
    
    public function findMinimalPriceProduct($assocMapArr) {

    	$minAssocMap = false;
    	$min_price = PHP_INT_MAX;
    	foreach ($assocMapArr as $assocMap) {
			$associatedProduct = $assocMap->getProduct();
			$price = $assocMap->getPrice($associatedProduct);
			if ($assocMap->hasSpecialPrice($associatedProduct, $assocMap->getSpecialPrice($associatedProduct))) {
				$price = $assocMap->getSpecialPrice($associatedProduct);
			}
			if ($min_price > $price) {
				$min_price = $price;
				$minAssocMap = $assocMap;
			}
		}

		return $minAssocMap;
    }
    
    protected function _hasDefaultQty($assocMapArr) {

    	$has = false;
    	foreach ($assocMapArr as $assocMap) {
    		if ($assocMap->getProduct()->getQty() > 0) {
    			$has = true;
    			break;
    		}
    	}
    	return $has;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function mapDirectiveQuantity($params = array()) {

        $cell = $this->getInventoryCount();

        // If Qty not set at parent item, summarize it from associated items
        if ($this->getConfigVar('qty_mode') == RocketWeb_GoogleBaseFeedGenerator_Model_Source_Quantitymode::ITEM_SUM_DEFAULT_QTY) {
            $qty = 0;
            foreach ($this->_assocs as $assocId => $assoc) {
                $qty += $assoc->getData('quantity');
            }
            $cell = $qty ? $qty : $cell;
        }

        $cell = sprintf('%d', $cell);
        $this->_findAndReplace($cell, $params['map']['column']);
        return $cell;
    }

    /**
     * Array with associated products ids in current store.
     *
     * @return array
     */
    public function getAssocIds() {

        if (is_null($this->_assoc_ids)) {
            $this->_assoc_ids = $this->loadAssocIds($this->getProduct(), $this->getStoreId());
        }
        return $this->_assoc_ids;
    }
}