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

/**
 * Cache by time for product shipping per store.
 *
 */

class RocketWeb_GoogleBaseFeedGenerator_Model_Shipping_Cache extends Varien_Object
{
	/**
	 * @var RocketWeb_GoogleBaseFeedGenerator_Model_Shipping
	 */
	protected $_Shipping;
	
	static public $_now;
	
	public function hit($product_id, $store_id)
	{
		self::_setNow();
		$Shipping = Mage::getModel('googlebasefeedgenerator/shipping');
		$this->_setShipping($Shipping);
		$collection = Mage::getModel('googlebasefeedgenerator/shipping')->getCollection()
			->addProductIdFilters($product_id, $store_id);
		if (count($collection) > 0) {
			$Shipping = $collection->getFirstItem();
			$this->_setShipping($Shipping);
			if (!$this->isExpired($Shipping->getUpdatedAt())) {
				return $Shipping->getValue();
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public function miss($product_id, $store_id, $value)
	{
		self::_setNow();
		$Shipping = $this->_getShipping();
		$Shipping->setProductId($product_id);
		$Shipping->setStoreId($store_id);
		//$Shipping->setUpdatedAt(null);
		$Shipping->setUpdatedAt(self::$_now);
		$Shipping->setValue($value);
		$Shipping->save();
		return $this;
	}
	
	public function isExpired($created_at)
	{
		self::_setNow();
		
		if (empty($created_at))
			return true;
		
		$created_time = mktime(
			substr($created_at, 11, 2),
			substr($created_at, 14, 2),
			substr($created_at, 17, 2),
			substr($created_at, 5, 2),
			substr($created_at, 8, 2),
			substr($created_at, 0, 4)
		);
		
		$ttl = $this->getConfig()->getConfigVar('ttl', $this->getStoreId(), 'shipping');
		if (empty($ttl))
			$ttl = 168;
		$e = 15 * 60; // a numerical error
		if ($created_time + 3600 * $ttl <= self::$_now - $e) {
			$ret = true;
		} else {
			$ret = false;
		}
		return $ret;
	}
	
	/**
	 * @param RocketWeb_GoogleBaseFeedGenerator_Model_Shipping $Shipping
	 */
	protected function _setShipping($Shipping)
	{
		$this->_Shipping = $Shipping;
		return $this;
	}
	
	/**
	 * @return RocketWeb_GoogleBaseFeedGenerator_Model_Shipping
	 */
	protected function _getShipping()
	{
		return $this->_Shipping;
	}
	
	static public function _setNow()
	{
		if (is_null(self::$_now)) {
			self::$_now = Mage::getModel('core/date')->timestamp(time());
		}
	}
}