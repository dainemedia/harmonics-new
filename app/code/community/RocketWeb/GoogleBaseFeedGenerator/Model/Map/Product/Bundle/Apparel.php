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

class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Bundle_Apparel extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Simple_Apparel {

    // Redundant code from RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Bundle
	public function getPrice($product = null) {

    	if (is_null($product)) {
    		$product = $this->getProduct();
    	}
    	
   		$price = $this->calcMinimalPrice($product);
        if ($price <= 0) {
            $price = $product->getPrice();
        }

        if ($price <=0) {
            $this->setSkip(sprintf("product id %d, sku %s - bundle apparel, can't determine price.", $product->getId(), $product->getSku()));
        }

    	return $price;
    }

    /**
     * @param $product
     * @return mixed
     */
    public function calcMinimalPrice($product) {

		if ($this->getConfig()->compareMagentoVersion(array('major' => 1, 'minor' => 6, 'revision' => 0, 'patch' => 0))) {
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
}