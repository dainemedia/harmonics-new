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

/**
 * Keeps attribute code. Test if exists.
 *
 */
class RocketWeb_GoogleBaseFeedGenerator_Model_System_Config_Backend_Attributecode extends Mage_Core_Model_Config_Data
{
    protected function _beforeSave()
    {
        $value = $this->getValue();

        if (!empty($value)) {
        	$value = trim($value);
	        $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $value);
			if ($attribute->getId() == null) {
				Mage::throwException('Attribute \'' . $value . ' \'does not exists');
			}
        }
        
        $result = parent::_beforeSave();
        
        return $result;
    }
}