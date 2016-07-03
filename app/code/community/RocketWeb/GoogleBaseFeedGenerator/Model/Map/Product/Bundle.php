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

class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Bundle extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract {

    protected $_assoc_ids;
    protected $_assocs;

    public function _beforeMap() {

        parent::_beforeMap();
        if ($this->isSkip()) {
            return $this;
        }
        $this->_assocs = array();

        $bundleType = $this->getProduct()->getTypeInstance(true);
        $assocCollection = $bundleType->getSelectionsCollection($bundleType->getOptionsIds($this->getProduct()), $this->getProduct());

        $stockStatusFlag = false; $stockStatus = false;

        foreach($assocCollection as $option) {

            $assocId = $option->product_id;

            $assoc = Mage::getModel('catalog/product');
            $assoc->setStoreId($this->getStoreId());
            $assoc->getResource()->load($assoc, $assocId);

            if ($this->getGenerator()->getData('verbose')) {
                echo $this->getGenerator()->formatMemory(memory_get_usage(true)). " - Bundle Associated: ". $assoc->getId(). "\n";
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
            if ($this->getConfigVar('add_out_of_stock')) {
                $this->_assocs[$assocId] = $assoc;
                $this->_assoc_ids[] = $assocId;
            } elseif ($stock == $this->getConfig()->getInStockStatus()) {
                $this->_assocs[$assocId] = $assoc;
                $this->_assoc_ids[] = $assocId;
            } else {
                // Set skip messages
                if ($this->getConfigVar('log_skip')) {
                    $this->log(sprintf("product id %d sku %s, configurable item, skipped - out of stock", $assocId, $assoc->getSku()));
                }
            }

            // Set stock status of the current item and check if the status has changed
            if ($stockStatus != false && $stock != $stockStatus) {
                $stockStatusFlag = true;
            }
            $stockStatus = $stock;
        }

        // Set bundle stock status if all assocs have the same stock status, only for default stocks
        if ($this->getConfigVar('use_default_stock', 'columns') && $stockStatus && !$stockStatusFlag) {
            $this->setAssociatedStockStatus($stockStatus);
            if ($stockStatus == $this->getConfig()->getOutOfStockStatus() && !$this->getConfigVar('add_out_of_stock')) {
                $this->setSkip(sprintf("product id %d sku %s, bundle, skipped - out of stock.", $this->getProduct()->getId(), $this->getProduct()->getSku()));
            }
        }

        // Set associated prices
        $this->setCacheAssociatedPrices();

// TODO: convert this piece to Bundle settings when we decide to add it in configs.
//        $assocMapArr = array();
//        if ($this->getConfig()->isAllowConfigurableAssociatedMode($this->getStoreId()) && !$this->getIsApparel()) {
//            foreach ($this->_assocs as $assoc) {
//                $assocMap = $this->getAssocMapModel($assoc);
//                if ($assocMap->checkSkipSubmission()->isSkip()) {
//                    if ($this->getConfigVar('log_skip')) {
//                        $this->log(sprintf("product id %d sku %s, configurable associated, skipped - product has 'Skip from Being Submitted' = 'Yes'.", $assoc->getId(), $assoc->getSku()));
//                    }
//                    continue;
//                }
//                $assocMapArr[$assoc->getId()] = $assocMap;
//            }
//        }
//
//        $this->setAssocMaps($assocMapArr);

        return $this;
    }

    public function getPrice($product = null) {

    	if (is_null($product)) {
    		$product = $this->getProduct();
    	}
    	
   		$price = $this->calcMinimalPrice($product);
        if ($price <= 0) {
            $price = $product->getPrice();
        }

        if ($price <=0) {
            $this->setSkip(sprintf("product id %d, sku %s - bundle, can't determine price.", $product->getId(), $product->getSku()));
        }
    	
    	return $price;
    }

    /**
     * @param $product
     * @return float|mixed
     */
    public function calcMinimalPrice($product) {

		if ($this->getConfig()->compareMagentoVersion(array('major' => 1, 'minor' => 6, 'revision' => 0, 'patch' => 0)) &&
            $this->getConfig()->compareMagentoVersion(array('major' => 1, 'minor' => 10, 'revision' => 1, 'patch' => 1), '!=')) {
			$_prices = $product->getPriceModel()->getTotalPrices($product);
		} else {
			$_prices = $product->getPriceModel()->getPrices($product);
		}
		if (is_array($_prices)) {
			$price = min($_prices);
		} else {
			$price = $_prices;
		}

		return $price;
    }

    /**
     * @param null $product
     * @return float|int
     */
    public function getSpecialPrice($product = null) {

    	if (is_null($product)) {
    		$product = $this->getProduct();
    	}
    	
    	$price = $this->calcMinimalPrice($product);
    	$special_price_percent = $product->getSpecialPrice();
    	if ($special_price_percent <= 0 || $special_price_percent > 100)
    		return 0;
    	$special_price = (($special_price = (100 - $special_price_percent) * $price / 100) > 0 ? $special_price : 0);
    	return $special_price;
    }


    /**
     * @param array $params
     * @return string
     */
    protected function mapAttributeWeight($params = array()) {

        $map = $params['map'];
        /** @var $product Mage_Catalog_Model_Product */
        $product = $this->getProduct();

        if ($product->getWeightType()) {
            $weight_attribute = $this->getGenerator()->getAttribute($map['attribute']);
            if ($weight_attribute === false) {
                Mage::throwException(sprintf('Couldn\'t find attribute \'%s\'.', $map['attribute']));
            }
            $weight = $this->getAttributeValue($product, $weight_attribute);
        } else {
            $weight = '';
            if (is_array($this->_assocs)) {
                $weight = 0;
                $bundleType = $this->getProduct()->getTypeInstance(true);
                $optionsCollection = $bundleType->getOptionsCollection($product);
                foreach ($optionsCollection as $option) {
                    if ($selections = $option->getSelections()) {
                        foreach ($selections as $selection) {
                            $minQty = $selection->getSelectionQty();
                            if ($minQty && array_key_exists($selection->getId(), $this->_assocs)) {
                                $weight += $minQty * $this->_assocs[$selection->getId()]->getWeight();
                                break;
                            }
                        }
                    }
                }
            }
        }

        if ($weight != ""){
            if (strpos($weight, $this->getConfigVar('weight_unit_measure', 'columns')) === false) {
                $weight .= ' '.$this->getConfigVar('weight_unit_measure', 'columns');
            }
            return $this->cleanField($weight, $params);
        }

        // Get Default Value
        $weight = $map['default_value'];
        $weight .= ' '.$this->getConfigVar('weight_unit_measure', 'columns');
        return $this->cleanField($weight, $params);
    }
}