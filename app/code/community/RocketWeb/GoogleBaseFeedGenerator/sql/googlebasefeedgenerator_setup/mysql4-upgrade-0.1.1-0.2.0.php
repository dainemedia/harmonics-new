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
 */

/**
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @author     RocketWeb
 */

/** @var $installer RocketWeb_GoogleBaseFeedGenerator_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

$installer->updateAttribute('catalog_product', 'rw_google_base_product_categ', 'frontend_label', "Google Category of the Item");
$installer->updateAttribute('catalog_product', 'rw_google_base_product_categ', 'note', "e.g. Apparel & Accessories > Clothing > Dresses");
$installer->updateAttribute('catalog_product', 'rw_google_base_product_type', 'frontend_label', "Google Shopping Product Type");
$installer->updateAttribute('catalog_product', 'rw_google_base_product_type', 'note', "e.g. Home & Garden > Kitchen & Dining > Appliances > Refrigerators or Home & Garden ");

$installer->endSetup();