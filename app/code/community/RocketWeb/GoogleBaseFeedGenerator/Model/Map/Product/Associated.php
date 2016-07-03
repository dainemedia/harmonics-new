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

class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Associated extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Abstract {

    /**
     * Skip checks for images since this is an non-apparel configurable,
     * because the image is mapped later using criteria to grab from configurable
     *
     * @return $this
     */
    public function _beforeMap() {

        if ($parentMap = $this->getParentMap()) {
            if ($parentMap->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
                || $parentMap->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED
                || $parentMap->getProduct()->getTypeId() == RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Subscription_Configurable::PRODUCT_TYPE_SUBSCTIPTION_CONFIGURABLE
                || $parentMap->getProduct()->getTypeId() == RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Subscription_Grouped::PRODUCT_TYPE_SUBSCTIPTION_GROUPED
               ) {
                return $this;
            }
        }
        // do the regular image check
        return parent::_beforeMap();
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function mapColumnDescription($params = array()) {

		$args = array('map' => $params['map']);

    	switch ($this->getConfigVar('associated_products_description', 'columns')) {
    		case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsdesc::FROM_ASSOCIATED:
    			$value = $this->getCellValue($args);
    			break;
    		case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsdesc::FROM_CONFIGURABLE:
    			$value = $this->getParentMap()->mapColumn('description');
    			break;
    		case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsdesc::FROM_CONFIGURABLE_ASSOCIATED:
    			$value = $this->getParentMap()->mapColumn('description');
    			if ($value == "")
    				$value = $this->getCellValue($args);
    			break;
    		case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsdesc::FROM_ASSOCIATED_CONFIGURABLE:
    			$value = $this->getCellValue($args);
    			if ($value == "")
    				$value = $this->getParentMap()->mapColumn('description');
    			break;
    		
    		default:
    			$value = $this->getCellValue($args);
    			if ($value == "")
    				$value = $this->getParentMap()->mapColumn('description');
    		
    	}
		
		return $value;
	}

    /**
     * @param array $params
     * @return mixed|string
     */
    public function mapDirectiveUrl($params = array()) {

		$args = array('map' => $params['map']);
		$product = $this->getProduct();
    	
    	switch ($this->getConfigVar('associated_products_link', 'columns')) {
    		case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodslink::FROM_CONFIGURABLE:
    			$value = $this->getParentMap()->mapColumn($args['map']['column']);
    			if ($this->getConfigVar('associated_products_link_add_unique', 'columns'))
    				$value = $this->addUrlUniqueParams($value, $product, $this->getParentMap()->getConfigurableAttributeCodes());
    			break;
    		case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodslink::FROM_ASSOCIATED_CONFIGURABLE:
    			if ($product->isVisibleInSiteVisibility()) {
		    		$value = $this->getCellValue($args);
		    	} else {
    				$value = $this->getParentMap()->mapColumn($args['map']['column']);
    				if ($this->getConfigVar('associated_products_link_add_unique', 'columns'))
    					$value = $this->addUrlUniqueParams($value, $product, $this->getParentMap()->getConfigurableAttributeCodes());
		    	}
    			break;
    		
    		default:
    			$value = $this->getParentMap()->mapColumn($args['map']['column']);
    			if ($this->getConfigVar('associated_products_link_add_unique', 'columns'))
    				$value = $this->addUrlUniqueParams($value, $product, $this->getParentMap()->getConfigurableAttributeCodes());
    	}
    	
		return $value;
	}

    /**
     * @param array $params
     * @return mixed
     */
    public function mapColumnImageLink($params = array()) {

		$args = array('map' => $params['map']);
    	
    	switch ($this->getConfigVar('associated_products_image_link_configurable', 'columns')) {
    		case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsimagelink::FROM_CONFIGURABLE:
    			$value = $this->getParentMap()->mapColumn('image_link');
    			break;
    		case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsimagelink::FROM_ASSOCIATED:
    			$value = $this->getCellValue($args);
    			break;
    		case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsimagelink::FROM_ASSOCIATED_CONFIGURABLE:
		    	$value = $this->getCellValue($args);
		    	if ($value == "" && $pMap = $this->getParentMap()) {
		    		$value = $pMap->mapColumn('image_link');
		    	}
    			break;
    		case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsimagelink::FROM_CONFIGURABLE_ASSOCIATED:
                if ($pMap = $this->getParentMap()) {
		    	    $value = $pMap->mapColumn('image_link');
                }
		    	if ($value == "") {
		    		$value = $this->getCellValue($args);
		    	}
    			break;
    		
    		default:
    			$value = $this->getCellValue($args);
		    	if ($value == "" && $pMap = $this->getParentMap()) {
		    		$value = $pMap->mapColumn('image_link');
		    	}
    	}
    	
		return $value;
	}
	
	/**
	 * @param array $params
	 * @return string
	 */
	public function mapColumnAdditionalImageLink($params = array()) {

        $args = array('map' => $params['map']);

        switch ($this->getConfigVar('associated_products_image_link_configurable', 'columns')) {
            case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsimagelink::FROM_CONFIGURABLE:
                $value = $this->getParentMap()->mapColumn('additional_image_link');
                break;
            case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsimagelink::FROM_ASSOCIATED:
                $value = $this->getCellValue($args);
                break;
            case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsimagelink::FROM_ASSOCIATED_CONFIGURABLE:
                $value = $this->getCellValue($args);
                if ($value == "" && $pMap = $this->getParentMap()) {
                    $value = $pMap->mapColumn('additional_image_link');
                }
                break;
            case RocketWeb_GoogleBaseFeedGenerator_Model_Source_Assocprodsimagelink::FROM_CONFIGURABLE_ASSOCIATED:
                if ($pMap = $this->getParentMap()) {
                    $value = $pMap->mapColumn('additional_image_link');
                }
                if ($value == "") {
                    $value = $this->getCellValue($args);
                }
                break;

            default:
                $value = $this->getCellValue($args);
                if ($value == "" && $pMap = $this->getParentMap()) {
                    $value = $pMap->mapColumn('additional_image_link');
                }
        }

		return $value;
	}
	
	public function getPrice($product = null) {

		if (is_null($product)) {
			$product = $this->getProduct();
		}
        if ($pMap = $this->getParentMap()) {
            return $pMap->getCacheAssociatedPrice($product->getId());
        }
		return $product->getPrice();
	}

    /**
     * Note: Magento takes the sale price from parent if the assoc does not have it.
     *
     * @param array $params
     * @return string
     */
    public function mapColumnSalePrice($params = array()) {

        /** @var $product Mage_Catalog_Model_Product */
    	$product    = $this->getProduct();
        /** @var $helper RocketWeb_GoogleBaseFeedGenerator_Helper_Tax */
        $helper     = Mage::helper('googlebasefeedgenerator/tax');

        /* 0 - excluding tax
           1 - including tax */
        $priceIncludesTax   = ($helper->priceIncludesTax($this->getStoreId()) ? true : false);
        $includingTax       = ($this->getConfigVar('add_tax_to_price', 'columns') ? true : false);

        $cell = '';

        // Get it from the parent
        $special_price   = $this->getParentMap()->getSpecialPrice($this->getParentMap()->getProduct());
        if ($this->getParentMap()->hasSpecialPrice($this->getParentMap()->getProduct(), $special_price)) {
            $cell = $helper->getPrice($product, $special_price, $includingTax, false, false, null, $this->getStoreId(), $priceIncludesTax);
        }

        // Get it from the assoc itself if parent does not have a special price.
        if (empty($cell)) {
            $special_price = $this->getSpecialPrice($this->getProduct());
            if ($this->hasSpecialPrice($product, $special_price)) {
                $cell = $helper->getPrice($product, $special_price, $includingTax, false, false, null, $this->getStoreId(), $priceIncludesTax);
            }
        }

    	$this->_cache_sale_price_excluding_tax = $helper->getPrice($product, $special_price, false, false, false, null, $this->getStoreId(), $priceIncludesTax);
    	$this->_cache_sale_price_including_tax = $helper->getPrice($product, $special_price, true, false, false, null, $this->getStoreId(), $priceIncludesTax);
    	
    	if (is_numeric($cell) && $cell <= 0) {
            $cell = '';
            $this->log(sprintf("product id %d product sku %s, associated product - product has special price 0.", $product->getId(), $product->getSku(), $cell), Zend_Log::WARN);
    	}

    	return $this->cleanField($cell, $params);
	}

    /**
     * @param array $params
     * @return mixed
     */
    public function mapColumnSalePriceEffectiveDate($params = array()) {
    	return $this->getParentMap()->mapColumn('sale_price_effective_date');
	}

    /**
     * @param array $params
     * @return mixed
     */
    public function mapColumnAvailability($params = array()) {

		$args = array('map' => $params['map']);
    	$value = $this->getParentMap()->mapColumn('availability');

    	// gets out of stock if parent is out of stock
    	if (strcasecmp($this->getConfig()->getOutOfStockStatus(), $value) == 0) {
    		return $value;
        }
    	
    	return $this->getCellValue($args);
	}

    /**
     * @param array $params
     * @return mixed
     */
    public function mapColumnBrand($params = array()) {

		$args = array('map' => $params['map']);

    	// get value from parent first
    	$value = $this->getParentMap()->mapColumn('brand');
    	if ($value != "") {
    		return $value;
        }
    	
    	return $this->getCellValue($args);
	}

    /**
     * @param array $params
     * @return string
     */
    public function mapColumnGoogleProductCategory($params = array()) {

		$args = array('map' => $params['map']);

    	// get value from parent first
        $value = $this->getParentMap()->mapColumn('google_product_category');


        // Grab it from attribute
        if (empty($value)) {
            $value = $this->mapAttribute($args);
        }

        // Grab it from by category map
        if (empty($value)) {
            $map_by_category = $this->getConfig()->getMapCategorySorted('google_product_category_by_category', $this->getStoreId());
            $category_ids = $this->getProduct()->getCategoryIds();
            if (empty($category_ids)) {
                $category_ids = $this->getParentMap()->getProduct()->getCategoryIds();
            }
            $value = $this->matchByCategory($map_by_category, $category_ids);
        }

        // Grab it from directive
        if (empty($value)) {
            $value = $this->getCellValue($args);
        }

        $this->_findAndReplace($value, $params['map']['column']);
		return html_entity_decode($value);
	}

    /**
     * @param array $params
     * @return string
     */
    public function mapColumnProductType($params = array()) {

		$args = array('map' => $params['map']);

    	// get value from parent first
    	$value = $this->getParentMap()->mapColumn('product_type');
    	if ($value != "")
    		return html_entity_decode($value);
    	
    	if ($value == "") {
    		$value = $this->getCellValue($args);
    		if ($value != "") {
    			return html_entity_decode($value);
    		}
    	}
    	
    	$map_by_category = $this->getConfig()->getMapCategorySorted('product_type_by_category', $this->getStoreId());
    	$category_ids = $this->getProduct()->getCategoryIds();
    	if (empty($category_ids))
    		$category_ids = $this->getParentMap()->getProduct()->getCategoryIds();
    	if (!empty($category_ids) && count($map_by_category) > 0) {
    		foreach ($map_by_category as $arr) {
    			if (array_search($arr['category'], $category_ids) !== false) {
    				$value = $arr['value'];
                    $this->_findAndReplace($value, $params['map']['column']);
    				break;
    			}
    		}
    	}

		return html_entity_decode($value);
	}

    /**
     * @param array $params
     * @return string
     */
    public function mapColumnAdwordsGrouping($params = array()) {

        $args = array('map' => $params['map']);

        // get value from parent first
        $value = $this->getParentMap()->mapColumn('adwords_grouping');
        if ($value != "")
                return html_entity_decode($value);

        if ($value == "") {
            $value = $this->getCellValue($args);
            if ($value != "") {
                return html_entity_decode($value);
            }
        }

        $map_by_category = $this->getConfig()->getMapCategorySorted('adwords_grouping_by_category', $this->getStoreId());
        $category_ids = $this->getProduct()->getCategoryIds();
        if (empty($category_ids))
            $category_ids = $this->getParentMap()->getProduct()->getCategoryIds();
        if (!empty($category_ids) && count($map_by_category) > 0) {
            foreach ($map_by_category as $arr) {
                if (array_search($arr['category'], $category_ids) !== false) {
                    $value = $arr['value'];
                    $this->_findAndReplace($value, $params['map']['column']);
                    break;
                }
            }
        }

        return html_entity_decode($value);
    }

    /**
     * @param array $params
     * @return string
     */
    public function mapColumnAdwordsLabels($params = array()) {

        $args = array('map' => $params['map']);

        // get value from parent first
        $value = $this->getParentMap()->mapColumn('adwords_labels');
        if ($value != "")
                return html_entity_decode($value);

        if ($value == "") {
            $value = $this->getCellValue($args);
            if ($value != "") {
                return html_entity_decode($value);
            }
        }

        $map_by_category = $this->getConfig()->getMapCategorySorted('adwords_labels_by_category', $this->getStoreId());
        $category_ids = $this->getProduct()->getCategoryIds();
        if (empty($category_ids))
            $category_ids = $this->getParentMap()->getProduct()->getCategoryIds();
        if (!empty($category_ids) && count($map_by_category) > 0) {
            foreach ($map_by_category as $arr) {
                if (array_search($arr['category'], $category_ids) !== false) {
                    $value = $arr['value'];
                    $this->_findAndReplace($value, $params['map']['column']);
                    break;
                }
            }
        }

        return html_entity_decode($value);
    }

    /**
     * @param array $params
     * @return string
     */
    public function mapDirectiveApparelSize($params = array()) {

        $attributes_codes = $this->getConfig()->getMultipleSelectVar('size_attribute_code', $this->getStoreId(), 'apparel');
        return $this->_mapDirectiveApparel($params, $attributes_codes);
    }

    /**
     * @param array $params
     * @return mixed|string
     */
    public function mapDirectiveApparelColor($params = array()) {

        $attributes_codes = $this->getConfig()->getMultipleSelectVar('color_attribute_code', $this->getStoreId(), 'apparel');
        return $this->_mapDirectiveApparel($params, $attributes_codes);
    }

    /**
     * @param array $params
     * @return string
     */
    public function mapDirectiveApparelMaterial($params = array()) {

        $attributes_codes = $this->getConfig()->getMultipleSelectVar('variant_material_attribute_code', $this->getStoreId(), 'apparel');
        return $this->_mapDirectiveApparel($params, $attributes_codes);
    }

    /**
     * @param array $params
     * @return string
     */
    public function mapDirectiveApparelPattern($params = array()) {

        $attributes_codes = $this->getConfig()->getMultipleSelectVar('variant_pattern_attribute_code', $this->getStoreId(), 'apparel');
        return $this->_mapDirectiveApparel($params, $attributes_codes);
    }
}