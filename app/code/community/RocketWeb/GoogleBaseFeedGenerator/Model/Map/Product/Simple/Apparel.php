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

class RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Simple_Apparel extends RocketWeb_GoogleBaseFeedGenerator_Model_Map_Product_Simple {

    /**
     * @return $this|void
     */
    public function initialize() {
		parent::initialize();
		$this->setApparelCategories();
	}

    /**
     * @return array
     */
    protected function _map() {

		$rows = parent::_map();
		reset($rows);
		return $rows;
	}

    /**
     * @param $rows
     * @return $this
     */
    public function _afterMap($rows) {

		reset($rows);
		$fields = current($rows);
		
		if (!$this->checkSkipImages($fields)) {
            $this->setSkip(sprintf("product id %d product sku %s, skipped - apparel product non variant, no image: '%s'.", $this->getProduct()->getId(), $this->getProduct()->getSku(), trim($fields['image_link'])));
			return $this;
		}
		
		if (!$this->getConfigVar('allow_empty_color_size', 'apparel')) {
            if (!$this->checkColorSizeRequired($fields)) {
                $this->setSkip(sprintf("product id %d product sku %s, skipped - apparel product non variant, color column is empty.", $this->getProduct()->getId(), $this->getProduct()->getSku()));
                return $this;
            }
		}

		return $rows;
	}

    /**
     * @param $fields
     * @return bool
     */
    public function checkSkipImages($fields) {

		if (!$this->getConfigVar('submit_no_img', 'apparel') && (!isset($fields['image_link']) || (isset($fields['image_link']) && trim($fields['image_link']) == ""))) {
			return false;
		}
		return true;
	}

    /**
     * @param array $params
     * @return mixed|string
     */
    public function mapDirectiveApparelColor($params = array()) {

		$map = $params['map'];
    	$cell = "";
    	
    	$default_value = isset($map['default_value']) ? $map['default_value'] : "";
    	if ($default_value != "") {
			$cell = $default_value;
    		$cell = $this->cleanField($cell, $params);
			$cell = str_replace(",", "/", $cell);
    		return $cell;
    	}
		
		$attributes_codes = $this->getConfig()->getMultipleSelectVar('color_attribute_code', $this->getStoreId(), 'apparel');
		if (count($attributes_codes) == 0) {
			return $cell;
		}

		foreach ($attributes_codes as $attr_code) {
			$attribute = $this->getGenerator()->getAttribute($attr_code);
            if ($attribute) {
                $cell = $this->cleanField($this->getAttributeValue($this->getProduct(), $attribute), $params);
                if ($cell != "") {
                    break;
                }
            }
		}
		
		// multiselect attributes - comma replaced with /
		return str_replace(",", "/", $cell);
	}

    /**
     * @param array $params
     * @return string
     */
    public function mapDirectiveApparelSize($params = array()) {

		$map = $params['map'];

    	if (!($this->getIsApparelClothing() || $this->getIsApparelShoes())) {
			return '';
        }
    	
    	$default_value = isset($map['default_value']) ? $map['default_value'] : "";
    	if ($default_value != "") {
    		return $this->cleanField($default_value, $params);
    	}
		
		$attributes_codes = $this->getConfig()->getMultipleSelectVar('size_attribute_code', $this->getStoreId(), 'apparel');
		if (count($attributes_codes) == 0) {
			return '';
		}
		
		foreach ($attributes_codes as $attr_code) {
			$attribute = $this->getGenerator()->getAttribute($attr_code);
            $cell = $this->cleanField($this->getAttributeValue($this->getProduct(), $attribute), $params);
			if ($cell != "") {
				break;
			}
		}
        $this->_findAndReplace($cell, $params['map']['column']);
		return $cell;
	}

    /**
     * @param array $params
     * @return string
     */
    public function mapDirectiveApparelGender($params = array()) {

		$map = $params['map'];
    	$cell = "";
    	$allowed_genders = $this->getConfig()->getAllowedGender();
    	
    	$default_value = isset($map['default_value']) ? $map['default_value'] : "";
    	if ($default_value != "") {
    		if (count(array_diff($this->getTools()->explodeMultiselectValue(strtolower($default_value)), $allowed_genders)) == 0) {
				$cell = $default_value;
    			$cell = $this->cleanField($cell, $params);
    			return $cell;
    		}
    	}
		
		$attributes_codes = $this->getConfig()->getMultipleSelectVar('gender_attribute_code', $this->getStoreId(), 'apparel');
		if (count($attributes_codes) == 0 ) {
			$cell = "";
			return $cell;
		}
		
		foreach ($attributes_codes as $attr_code) {
			$attribute = $this->getGenerator()->getAttribute($attr_code);
            $cell = $this->cleanField($this->getAttributeValue($this->getProduct(), $attribute), $params);
			if ($cell != "") {
				if (count(array_diff($this->getTools()->explodeMultiselectValue(strtolower($cell)), $allowed_genders)) == 0) {
					break;
				} else {
					$cell = "";
				}
			}
		}
		
		if (count(array_diff($this->getTools()->explodeMultiselectValue(strtolower($cell)), $allowed_genders)) != 0) {
			$cell = "";
        }

        $this->_findAndReplace($cell, $params['map']['column']);
		return $cell;
	}

    /**
     * @param array $params
     * @return string
     */
    public function mapDirectiveApparelAgeGroup($params = array()) {

		$map = $params['map'];
    	$cell = "";
    	$allowed_age_groups = $this->getConfig()->getAllowedAgeGroup();
    	
    	$default_value = isset($map['default_value']) ? $map['default_value'] : "";
    	if ($default_value != "") {
    		if (count(array_diff($this->getTools()->explodeMultiselectValue(strtolower($default_value)), $allowed_age_groups)) == 0) {
				$cell = $default_value;
    			$cell = $this->cleanField($cell, $params);
    			return $cell;
    		}
    	}
		
		$attributes_codes = $this->getConfig()->getMultipleSelectVar('age_group_attribute_code', $this->getStoreId(), 'apparel');
		if (count($attributes_codes) == 0 ) {
			$cell = "";
			return $cell;
		}
		
		foreach ($attributes_codes as $attr_code) {
			$attribute = $this->getGenerator()->getAttribute($attr_code);
            $cell = $this->cleanField($this->getAttributeValue($this->getProduct(), $attribute), $params);
			if ($cell != "") {
				if (count(array_diff($this->getTools()->explodeMultiselectValue(strtolower($cell)), $allowed_age_groups)) == 0) {
					break;
				} else {
					$cell = "";
				}
			}
		}
		
		if (count(array_diff($this->getTools()->explodeMultiselectValue(strtolower($cell)), $allowed_age_groups)) != 0)
			$cell = "";

        $this->_findAndReplace($cell, $params['map']['column']);
		return $cell;
	}

    /**
     * This function has been converted from 'US Feed' to 'Allow Apparel without Color or Size'
     *
     * @param $fields
     * @return bool
     */
    protected function checkColorSizeRequired($fields) {

		foreach ($fields as $k => $v) $fields[$k] = trim($v);
		$gb_category = $this->mapColumn('google_product_category');
		
		$ret = true;
		$empties = array();
		$columns = array($this->_color_column_name, 'gender', 'age_group');
		if ($this->getIsApparelClothing() || $this->getIsApparelShoes()) {
			$columns[] = 'size';
        }

		foreach ($columns as $column) {
			if (!isset($fields[$column]) || (isset($fields[$column]) && $fields[$column] == "")) {
				if ($column == 'gender' || $column == 'age_group') {
					// not required for some subcategories
					$f = false;
					foreach ($this->getConfig()->getMultipleSelectVar($column.'_not_req_categories', $this->getStoreId(), 'apparel') as $categ) {
						if ($this->matchGoogleCategory($gb_category, $categ))
							$f = true;
					}
					if (!$f) {
						$empties[] = $column;
						$ret = false;
					}
				} else {
					$empties[] = $column;
					$ret = false;
				}
			}
		}
		
		return $ret;
	}
}