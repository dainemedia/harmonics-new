<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @copyright  Copyright (c) 2012 RocketWeb (http://rocketweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     RocketWeb
 */

class RocketWeb_GoogleBaseFeedGenerator_Model_System_Config_Backend_Serialized_Mapproductcolumns extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array
{
	protected function _beforeSave()
    {
        $value = $this->_rwCleanValue($this->getValue());
        $this->setValue($value);
        parent::_beforeSave();
    }
    
    protected function _afterLoad()
    {
        parent::_afterLoad();
        if ($this->getValue() === false)
        	$this->setDefaultMapProductColumns();
    }
    
    protected function setDefaultMapProductColumns()
    {
    	$this->setValue(Mage::getSingleton('googlebasefeedgenerator/config')->convertDefaultMapProductColumns());
    }
    
    protected function _rwCleanValue($value)
    {
    	if (is_array($value)) {
    		foreach ($value as $k => $v) {
    			if (is_array($v) && isset($v['column'])) {
    				$value[$k]['column'] = str_replace(" ", "_", trim(strtolower($v['column'])));
    				$value[$k]['column'] = preg_replace('/__*/', '_', $value[$k]['column']);
    				$value[$k]['column'] = trim($value[$k]['column'], "_");
    			} elseif (is_array($v) && isset($v['default_value'])) {
    				$value[$k]['default_value'] = trim($v['default_value']);
    			}
    		}
    	}
    	return $value;
    }
}