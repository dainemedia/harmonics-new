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

/** @var $installer RocketWeb_GoogleBaseFeedGenerator_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;

$installer->startSetup();

$installer->addAttribute('catalog_product', 'rw_google_base_adw_redirect', array(
    'type' => 'varchar',
    'input' => 'text',
    'label'    => 'Adwords Redirect',
    'global'    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'note'      => 'Allows advertisers to specify a separate URL that can be used to track traffic coming from Google Shopping.',
    'visible'    => true,
    'required' => 0,
    'user_defined' => false,
    'visible_on_front' => false,
    'used_for_price_rules' => false,
    'position' => 70,
    'group' => 'Google Shopping Feed'
));

$installer->endSetup();