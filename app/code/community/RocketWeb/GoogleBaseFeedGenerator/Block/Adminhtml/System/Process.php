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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_System_Process extends Mage_Adminhtml_Block_Abstract {

    /**
     * @return $this|Mage_Core_Block_Abstract
     */
    protected function _prepareLayout() {

    	$this->runUpdate();
    	$this->setTemplate('googlebasefeedgenerator/system/process.phtml');
        parent::_prepareLayout();
        return $this;
    }

    /**
     * Run the generators
     */
    protected function runUpdate() {

    	$this->setData('script_started_at', Mage::app()->getLocale()->date(null, null, Mage::app()->getLocale()->getDefaultLocale()));
        $this->setData('script_finished_at', Mage::app()->getLocale()->date(null, null, Mage::app()->getLocale()->getDefaultLocale()));
        	
    	$messages = array();
    	$this->setData('messages', $messages);

        $store   = $this->getRequest()->getParam('store');
        $testmode = $this->getRequest()->getParam('testmode', 0);
        $sku = $this->getRequest()->getParam('sku');
    	$limit   = (int) $this->getRequest()->getParam('limit', 0);
    	$offset   = (int) $this->getRequest()->getParam('offset', 0);
		if ($limit > 100) $limit = 100;
		
        $store_code = Mage_Core_Model_Store::DEFAULT_CODE;
        if ($store != "")
        	$store_code = $store;
        
        $this->setData('is_feed', false);
        
        $this->setData('log_messages', array());
        try {

            try {
                $store_id = Mage::app()->getStore($store_code)->getStoreId();
            } catch (Exception $e) {
                Mage::throwException(sprintf('Store with code \'%s\' doesn\'t exists.', $store_code));
            }
            $Generator = Mage::getSingleton('googlebasefeedgenerator/tools')->addData(array('store_code' => $store_code))->getGenerator($store_id);
	    	$messages[] = array('msg' => 'Store '.Mage::app()->getStore($store_code)->getName(), 'type' => 'info');
	    	
	    	/** @var $Generator RocketWeb_GoogleBaseFeedGenerator_Model_Generator*/

	    	if ($testmode) {
	    		if ($limit > $Generator->getConfigVar('button_max_products'))
	    			$limit = $Generator->getConfigVar('button_max_products');
	    		
	    		$this->setTestMode(true);
	    		$Generator->setTestMode(true);
		    	if ($sku) {
		    		$Generator->setTestSku($sku);
		    		$this->setTestSku($sku);
		    	} elseif($offset >= 0 && $limit > 0) {
		    		$Generator->setTestOffset($offset);
		    		$this->setTestOffset($offset);
		    		$Generator->setTestLimit($limit);
		    		$this->setTestLimit($limit);
		    	} else {
		    		Mage::throwException(sprintf("Invalid parameters for test mode: sku %s or offset %s and limit %s", $sku, $offset, $limit));
		    	}
		    	
		    	$messages[] = array('msg' => 'Test mode.', 'type' => 'info');
	    	} else {
	    		$this->setTestMode(false);
	    	}
			
	    	if (!$Generator->getConfigVar('is_turned_on')) {
				$messages[] = array('msg' => 'Can\'t generate feed. It\'s disabled from config option: RocketWeb Extensions > Google Shopping Feed > Settings > Enabled.', 'type' => 'error');
				$this->setData('messages', $messages);
				return;
			}
			
			if (!$this->getTestMode()) {

				$collection = Mage::getModel('catalog/product')->getCollection();
				$collection->setStoreId(Mage::app()->getStore($store_code)->getStoreId());
				$count = $collection->getSize();
				if ($count > $Generator->getConfigVar('button_max_products'))
				{
					Mage::throwException(sprintf("Too many products. Detected %d products more than the limit allowed of %d.", $count, $Generator->getConfigVar('button_max_products')));
				}
			}
			
			// Generate feed - costly process.
			$Generator->run();
			if ($Generator->getCountProductsExported() > 0) {
				$this->setData('is_feed', true);
			}
			
		} catch (Exception $e) {
			$messages[] = array('msg' => 'Error:<br />' . $e->getMessage(), 'type' => 'error');
		}
    	
		$count_products = 0;
		$count_skipped = 0;
		if (isset($Generator) && is_object($Generator) && $Generator instanceof RocketWeb_GoogleBaseFeedGenerator_Model_Generator) {
			$count_products = $Generator->getCountProductsExported();
			$count_skipped = $Generator->getCountProductsSkipped();
		}
		
		$feed_data = array();
		if ($this->getIsFeed() && $sku != "" && $count_products > 0 && file_exists($Generator->getFeedPath())) {
			/* tsv file */
			$csv = new Varien_File_Csv();
			$csv->setDelimiter("\t");
			$csv->setEnclosure('~'); // dummy enclosure
			$rows = $csv->getData($Generator->getFeedPath());
			$i = 0;
			foreach ($rows as $row) {
				if ($i == 0) {
					$i++;
					continue;
				}
				$feed_data[] = array_combine($rows[0], $row);
				$i++;
			}
		}
		$this->setFeedData($feed_data);
		$messages[] = array('msg' => sprintf("The feed was generated.<br />%d items were added %d products were skipped.", $count_products, $count_skipped), 'type' => 'info');
    	
    	$this->setData('messages', $messages);
        if (isset($Generator) && $Generator) {
    	    $this->setData('log_messages', $Generator->_getLog()->getMemoryStorage());
        }
    	$this->setData('script_finished_at', Mage::app()->getLocale()->date(null, null, Mage::app()->getLocale()->getDefaultLocale()));
    }

    /**
     * @return string
     */
    public function getDownloadUrl() {

    	$website = $this->getRequest()->getParam('website');
        $store   = $this->getRequest()->getParam('store');
        $uri = 'googlebasefeedgenerator_admin/adminhtml_googlebasefeedgenerator/downloadFeed';

    	if ($website != "") {
            $uri .= '/website/'.$website;
        }

        if ($store != "") {
        	$uri .= '/store/'.$store;
        }
        
        $add_uri = "";
        if ($this->getTestMode()) {
        	if ($this->getTestSku()) {
        		$add_uri .= "/testmode/1/sku/".$this->getTestSku();
        	} else if ($this->getTestOffset() >= 0 && $this->getTestLimit() > 0) {
        		$add_uri .= "/testmode/1/offset/".$this->getTestOffset()."/limit/".$this->getTestLimit();
        	}
        }
        
        return rtrim(Mage::helper('adminhtml')->getUrl($uri), "/").$add_uri;
    }
}