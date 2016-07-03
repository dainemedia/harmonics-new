<?php

$installer = $this;
$installer->startSetup();
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$setup->addAttribute('catalog_product', 'cjm_stocktext', array(

    'group'         				=> 'Custom Stock Status',
    'input'         				=> 'text',
    'type'          				=> 'varchar',
    'label'         				=> 'Custom Stock Message',
    'backend'       				=> '',
	'frontend'						=> '',
    'visible'       				=> true,
    'required'      				=> false,
    'user_defined' 					=> true,
    'searchable' 					=> false,
    'filterable' 					=> false,
    'comparable'    				=> false,
    'visible_on_front' 				=> false,
    'visible_in_advanced_search'  	=> false,
    'is_html_allowed_on_front' 		=> false,
    'global'        				=> Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'note'							=> 'Enter custom availability text here if wanted.'
));

$setup->addAttribute('catalog_product', 'cjm_stockmessage', array(

    'group'         				=> 'Custom Stock Status',
    'input'         				=> 'select',
    'type'          				=> 'text',
    'label'         				=> 'Stock Message',
    'backend'       				=> '',
	'frontend'						=> '',
    'visible'       				=> true,
    'required'      				=> false,
    'user_defined' 					=> true,
    'searchable' 					=> false,
    'filterable' 					=> false,
    'comparable'    				=> false,
    'visible_on_front' 				=> false,
    'visible_in_advanced_search'  	=> false,
    'is_html_allowed_on_front' 		=> false,
    'global'        				=> Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE
));

$setup->addAttribute('catalog_product', 'cjm_ships_in', array(

    'group'         				=> 'Custom Stock Status',
    'input'         				=> 'text',
    'type'          				=> 'varchar',
    'label'         				=> 'Order Processing Time',
	'frontend_class'				=> 'validate-digits',
    'backend'       				=> '',
    'visible'       				=> true,
    'required'      				=> false,
    'user_defined' 					=> true,
    'searchable' 					=> false,
    'filterable' 					=> false,
    'comparable'    				=> false,
    'visible_on_front' 				=> false,
    'visible_in_advanced_search'  	=> false,
    'is_html_allowed_on_front' 		=> false,
    'global'        				=> Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'note'							=> 'Days to process order. Leave blank for default. To show in stock status, use variable <b>%days%</b>.'
));

$setup->addAttribute('catalog_product', 'cjm_expecdate', array(

    'group'         				=> 'Custom Stock Status',
    'input'         				=> 'date',
    'type'          				=> 'datetime',
    'label'         				=> 'Expected In-Stock Date',
	'frontend_class'				=> '',
    'backend'       				=> 'eav/entity_attribute_backend_datetime',
	'frontend'						=> 'eav/entity_attribute_frontend_datetime',
    'visible'       				=> true,
    'required'      				=> false,
    'user_defined' 					=> true,
    'searchable' 					=> false,
    'filterable' 					=> false,
    'comparable'    				=> false,
    'visible_on_front' 				=> false,
    'visible_in_advanced_search'  	=> false,
    'is_html_allowed_on_front' 		=> false,
    'global'        				=> Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'note'							=> 'Date the product is expected to be back in-stock. To show in stock status, use variable <b>%expec%</b>.'
));

$setup->addAttribute('catalog_product', 'cjm_preorderdate', array(

    'group'         				=> 'Custom Stock Status',
    'input'         				=> 'date',
    'type'          				=> 'datetime',
    'label'         				=> 'On Pre-Order Until',
	'frontend_class'				=> '',
    'backend'       				=> 'eav/entity_attribute_backend_datetime',
	'frontend'						=> 'eav/entity_attribute_frontend_datetime',
    'visible'       				=> true,
    'required'      				=> false,
    'user_defined' 					=> true,
    'searchable' 					=> false,
    'filterable' 					=> false,
    'comparable'    				=> false,
    'visible_on_front' 				=> false,
    'visible_in_advanced_search'  	=> false,
    'is_html_allowed_on_front' 		=> false,
    'global'        				=> Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'note'							=> 'Date the product will no longer show as a pre-order. To show in pre-order text, use variable <b>%pod%</b>.'
));

$setup->addAttribute('catalog_product', 'cjm_preordertext', array(

    'group'         				=> 'Custom Stock Status',
    'input'         				=> 'text',
    'type'          				=> 'varchar',
    'label'         				=> 'Pre-Order Text',
    'backend'       				=> '',
	'frontend'						=> '',
    'visible'       				=> true,
    'required'      				=> false,
    'user_defined' 					=> true,
    'searchable' 					=> false,
    'filterable' 					=> false,
    'comparable'    				=> false,
    'visible_on_front' 				=> false,
    'visible_in_advanced_search'  	=> false,
    'is_html_allowed_on_front' 		=> false,
    'global'        				=> Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'note'							=> 'Enter pre-order text here if wanted, otherwise pre-order date will be shown.'
));

$setup->addAttribute('catalog_product', 'cjm_hideshipdate', array(

    'group'         				=> 'Custom Stock Status',
    'input'         				=> 'select',
    'type'          				=> 'int',
    'label'         				=> 'Hide Ship Date?',
	'source'            			=> 'eav/entity_attribute_source_boolean',
	'frontend_class'				=> '',
    'backend'       				=> '',
	'frontend'						=> '',
	'default_value'					=> 0, 
    'visible'       				=> true,
    'required'      				=> false,
    'user_defined' 					=> true,
    'searchable' 					=> false,
    'filterable' 					=> false,
    'comparable'    				=> false,
    'visible_on_front' 				=> true,
    'visible_in_advanced_search'  	=> false,
    'is_html_allowed_on_front' 		=> false,
    'global'        				=> Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'note'							=> 'Do you want to hide the ship date info for this product?'
));

$installer->endSetup();

$setup->updateAttribute('catalog_product', 'cjm_stockmessage', 'note', 'Select a stock message here if wanted. If custom availability text is entered above, it will override this message. Add more messages by going to <b>Catalog->Attributes->Manage Attributes</b> and clicking on the \'cjm_stockmessage\' attribute.');

$setup->updateAttribute('catalog_product', 'cjm_stocktext', 'used_in_product_listing', 1);
$setup->updateAttribute('catalog_product', 'cjm_preordertext', 'used_in_product_listing', 1);
$setup->updateAttribute('catalog_product', 'cjm_preorderdate', 'used_in_product_listing', 1);
$setup->updateAttribute('catalog_product', 'cjm_expecdate', 'used_in_product_listing', 1);
$setup->updateAttribute('catalog_product', 'cjm_stockmessage', 'used_in_product_listing', 1);
