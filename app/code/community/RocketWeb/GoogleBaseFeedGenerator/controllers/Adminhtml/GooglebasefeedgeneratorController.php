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

class RocketWeb_GoogleBaseFeedGenerator_Adminhtml_GooglebasefeedgeneratorController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
		
	    $this->_forward('noroute');
	}
	
	public function generateAction()
	{
		$this->loadLayout('popup');
		$this->_addContent(
			$this->getLayout()->createBlock('googlebasefeedgenerator/adminhtml_system_process', 'google_base_gen_feed')
		);
		$this->renderLayout();
	}
	
	public function downloadFeedAction()
	{
		try {
			
			$website = $this->getRequest()->getParam('website');
        	$store   = $this->getRequest()->getParam('store');
        	
        	$testmode = $this->getRequest()->getParam('testmode', 0);
	        $sku = $this->getRequest()->getParam('sku');
	    	$limit   = (int) $this->getRequest()->getParam('limit', 0);
	    	$offset   = (int) $this->getRequest()->getParam('offset', 0);
	    	
        	$store_code = Mage_Core_Model_Store::DEFAULT_CODE;
        	if ($store != "")
        		$store_code = $store;

            try {
                $store_id = Mage::app()->getStore($store_code)->getStoreId();
            } catch (Exception $e) {
                Mage::throwException(sprintf('Store with code \'%s\' doesn\'t exists.', $store_code));
            }
            $Generator = Mage::getSingleton('googlebasefeedgenerator/tools')->addData(array('store_code' => $store_code))->getGenerator($store_id);
			
			if ($testmode) {
	    		$Generator->setTestMode(true);
		    	if ($sku) {
		    		$Generator->setTestSku($sku);
		    	} elseif($offset >= 0 && $limit > 0) {
		    		$Generator->setTestOffset($offset);
		    		$Generator->setTestLimit($limit);
		    	} else {
		    		Mage::throwException(sprintf("Invalid parameters for test mode: sku %s or offset %s and limit %s", $sku, $offset, $limit));
		    	}
	    	}
			
			$filePath = $Generator->getFeedPath();
            if (!is_file($filePath) || !is_readable($filePath)) {
				throw new Exception('File %s doesn\'t exists.', $$filePath);
            }
            
            return $this->_prepareDownloadResponse(
				basename($filePath),
				array('value' => $filePath,
					  'type'  => 'filename'),
				//"text/tab-separated-values",
				"text/plain",
				filesize($filePath));
        } catch (Exception $e) {
            $this->_forward('noRoute');
        }
	}
}