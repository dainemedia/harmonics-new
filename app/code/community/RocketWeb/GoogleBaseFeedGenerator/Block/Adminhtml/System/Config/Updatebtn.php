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
 * Custom renderer
 */
class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_System_Config_Updatebtn extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('googlebasefeedgenerator/system/config/updatebtn.phtml');
        }
        return $this;
    }
    
    /**
     * Unset some non-related element parameters
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        
        $website = $this->getRequest()->getParam('website');
        $store   = $this->getRequest()->getParam('store');
        
        $uri = $originalData['button_url'];
        if ($store != "")
        	$uri .= '/website/'.$website;
        if ($store != "")
        	$uri .= '/store/'.$store;
        
        $uri = Mage::helper('adminhtml')->getUrl($uri);
        $this->addData(array(
            'button_label'	=> Mage::helper('googlebasefeedgenerator')->__($originalData['button_label']),
            'button_url'	=> $uri,
            'html_id'		=> $element->getHtmlId(),
        ));
        return $this->_toHtml();
    }

	public function getButtonLabel()
	{
		return Mage::helper('googlebasefeedgenerator')->__('Generate Feed Now');
	}

	public function getButtonUrl()
	{
		$website = $this->getRequest()->getParam('website');
		$store = $this->getRequest()->getParam('store');
		$uri = 'googlebasefeedgenerator_admin/adminhtml_googlebasefeedgenerator/generate';
		if ($store != "") $uri .= '/website/'.$website;
		if ($store != "") $uri .= '/store/'.$store;
		$uri = Mage::helper('adminhtml')->getUrl($uri);
		return $uri;
	}
	
	public function isButtonAllowedProducts()
	{
		$store   = $this->getRequest()->getParam('store');
		$config = Mage::getModel('googlebasefeedgenerator/config');
		
		try {
			Mage::app()->getStore(Mage_Core_Model_Store::DEFAULT_CODE);
			$is_default_store = true;
		} catch (Exception $e) {
			$is_default_store = false;
		}
		
		$count = 999999999;
		if ($this->isButtonAllowed())
		{
			$collection = Mage::getModel('catalog/product')->getCollection();
			if ($store != "")
			{
				
				$collection->setStoreId(Mage::app()->getStore($store)->getStoreId());
			} else {
				$collection->setStoreId(Mage::app()->getStore(Mage_Core_Model_Store::DEFAULT_CODE));
			}
			
			$count = $collection->getSize();
		}
		
		if ($count > $config->getConfigVar('button_max_products'))
			return false;
		else
			return true;
	}
	
	public function isButtonAllowed()
	{
		$is_default_store = true;
		try {
			Mage::app()->getStore(Mage_Core_Model_Store::DEFAULT_CODE);
			$is_default_store = true;
		} catch (Exception $e) {
			$is_default_store = false;
		}
		
        $store   = $this->getRequest()->getParam('store');
		if ($store == "" && Mage::app()->isSingleStoreMode() && $is_default_store)
		{
			return true;
		} elseif ($store != "") {
			return true;
		}
		
		return false;
	}
}
