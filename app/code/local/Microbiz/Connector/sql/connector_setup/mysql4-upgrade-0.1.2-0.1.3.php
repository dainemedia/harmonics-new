<?php
//version 102
$installer = $this;
//ALTER TABLE  `mbiz_options_rel_mas` ADD  `is_deleted` TINYINT( 1 ) NOT NULL DEFAULT  '0'
$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS {$this->getTable('connector_eav_attribute')} LIKE {$this->getTable('eav_attribute')};
	INSERT {$this->getTable('connector_eav_attribute')} SELECT * FROM {$this->getTable('eav_attribute')};

CREATE TABLE IF NOT EXISTS {$this->getTable('connector_eav_attribute_group')} LIKE {$this->getTable('eav_attribute_group')};
	INSERT {$this->getTable('connector_eav_attribute_group')} SELECT * FROM {$this->getTable('eav_attribute_group')};
CREATE TABLE IF NOT EXISTS {$this->getTable('connector_eav_attribute_label')} LIKE {$this->getTable('eav_attribute_label')};
	INSERT {$this->getTable('connector_eav_attribute_label')} SELECT * FROM {$this->getTable('eav_attribute_label')};
CREATE TABLE IF NOT EXISTS {$this->getTable('connector_eav_attribute_option')} LIKE {$this->getTable('eav_attribute_option')};
	INSERT {$this->getTable('connector_eav_attribute_option')} SELECT * FROM {$this->getTable('eav_attribute_option')};
CREATE TABLE IF NOT EXISTS {$this->getTable('connector_eav_attribute_option_value')} LIKE {$this->getTable('eav_attribute_option_value')};
	INSERT {$this->getTable('connector_eav_attribute_option_value')} SELECT * FROM {$this->getTable('eav_attribute_option_value')};
CREATE TABLE IF NOT EXISTS {$this->getTable('connector_eav_attribute_set')} LIKE {$this->getTable('eav_attribute_set')};
	INSERT {$this->getTable('connector_eav_attribute_set')} SELECT * FROM {$this->getTable('eav_attribute_set')};
CREATE TABLE IF NOT EXISTS {$this->getTable('connector_catalog_category_entity')} LIKE {$this->getTable('catalog_category_entity')};
	INSERT {$this->getTable('connector_catalog_category_entity')} SELECT * FROM {$this->getTable('catalog_category_entity')};

");

if (version_compare(Mage::getVersion(), '1.6.0', '<='))
{
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'str_unq_id',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 255,
            'nullable'  => true,
            'comment'   => 'Store Unique ID '
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_options_rel_mas'),
        'is_deleted',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 255,
            'nullable'  => false,
            'default' => 0,
            'comment'   => 'Deleted In Magento'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'include_inventory',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 1,
            'nullable'  => false,
            'default' => 0,
            'comment'   => 'Include Store Inventory to product'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_country',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 255,
            'nullable'  => true,
            'comment'   => 'Store Country '
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_state',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 255,
            'nullable'  => true,
            'comment'   => 'Store State'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_city',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 255,
            'nullable'  => true,
            'comment'   => 'Store City'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_county',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 255,
            'nullable'  => true,
            'comment'   => 'Store County'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_phone',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 50,
            'nullable'  => true,
            'comment'   => 'Store Country '
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_email',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 50,
            'nullable'  => true,
            'comment'   => 'Store Email'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_time_zone',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 100,
            'nullable'  => true,
            'comment'   => 'Store Time Zone'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_currency',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 5,
            'default' => 'USD',
            'nullable'  => false,
            'comment'   => 'Store Currency'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'company_currency',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 5,
            'default' => 'USD',
            'nullable'  => false,
            'comment'   => 'Company Currency'
        )
    );

    /*Version Related Queries*/

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_set_rel_mas'),
        'mage_version_number',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'Magento Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_set_rel_mas'),
        'mbiz_version_number',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'MicroBiz Version Number'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_set_rel_mas'),
        'last_updated_from',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 10,
            'nullable'  => false,
            'comment'   => 'Last Updated From Mag Or Mbiz'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_set_rel_mas'),
        'modified_by',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 50,
            'nullable'  => false,
            'comment'   => 'Modified by User Name'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_set_rel_mas'),
        'modified_at',Varien_Db_Ddl_Table::TYPE_DATETIME,null,
        array(
            'nullable'  => false,
            'comment'   => 'Modified Date Time'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_rel_mas'),
        'mage_version_number',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'Magento Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_rel_mas'),
        'mbiz_version_number',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'MicroBiz Version Number'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_rel_mas'),
        'last_updated_from',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 10,
            'nullable'  => false,
            'comment'   => 'Last Updated From Mag Or Mbiz'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_rel_mas'),
        'modified_by',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 50,
            'nullable'  => false,
            'comment'   => 'Modified by User Name'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_rel_mas'),
        'modified_at',Varien_Db_Ddl_Table::TYPE_DATETIME,null,
        array(
            'nullable'  => false,
            'comment'   => 'Modified Date Time'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_options_rel_mas'),
        'mage_version_number',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'Magento Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_options_rel_mas'),
        'mbiz_version_number',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'MicroBiz Version Number'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_options_rel_mas'),
        'last_updated_from',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 10,
            'nullable'  => false,
            'comment'   => 'Last Updated From Mag Or Mbiz'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_options_rel_mas'),
        'modified_by',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 50,
            'nullable'  => false,
            'comment'   => 'Modified by User Name'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_options_rel_mas'),
        'modified_at',Varien_Db_Ddl_Table::TYPE_DATETIME,null,
        array(
            'nullable'  => false,
            'comment'   => 'Modified Date Time'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_prd_rel_mas'),
        'mage_version_number',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'Magento Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_prd_rel_mas'),
        'mbiz_version_number',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'MicroBiz Version Number'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_prd_rel_mas'),
        'last_updated_from',Varien_Db_Ddl_Table::TYPE_VARCHAR,null,
        array(
            'length'    => 10,
            'nullable'  => false,
            'comment'   => 'Last Updated From Mag Or Mbiz'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_prd_rel_mas'),
        'modified_by',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 50,
            'nullable'  => false,
            'comment'   => 'Modified by User Name'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_prd_rel_mas'),
        'modified_at',Varien_Db_Ddl_Table::TYPE_DATETIME,null,
        array(
            'nullable'  => false,
            'comment'   => 'Modified Date Time'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_prd_rel_mas'),
        'mbiz_sku',Varien_Db_Ddl_Table::TYPE_TEXT,null,
        array(
            'length'    => 50,
            'nullable'  => false,
            'comment'   => 'MicroBiz Product Sku'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('sync_records_header_tran'),
        'mage_version_number',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'Magento Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('sync_records_header_tran'),
        'mbiz_version_number',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'MicroBiz Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('sync_records_header_tran_history'),
        'mage_version_number',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'Magento Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('sync_records_header_tran_history'),
        'mbiz_version_number',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'MicroBiz Version Number'
        )
    );
}
else
{
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_options_rel_mas'),
        'is_deleted',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 255,
            'nullable'  => false,
            'default' => 0,
            'comment'   => 'Deleted In Magento'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'str_unq_id',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 255,
            'nullable'  => true,
            'comment'   => 'Store Unique Id'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'include_inventory',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,null,
            'length'    => 1,
            'nullable'  => false,
            'default' => 0,
            'comment'   => 'Include Store Inventory to product'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_country',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 255,
            'nullable'  => true,
            'comment'   => 'Store Country'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_state',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 255,
            'nullable'  => true,
            'comment'   => 'Store State'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_city',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 255,
            'nullable'  => true,
            'comment'   => 'Store City'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_county',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 255,
            'nullable'  => true,
            'comment'   => 'Store County'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_phone',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 50,
            'nullable'  => true,
            'comment'   => 'Store Phone Number'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_email',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 50,
            'nullable'  => true,
            'comment'   => 'Store Email'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_time_zone',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 100,
            'nullable'  => true,
            'comment'   => 'Store Time Zone'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'store_currency',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 5,
            'default' => 'USD',
            'nullable'  => false,
            'comment'   => 'Store Currency'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('storeinventory_header'),
        'company_currency',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 5,
            'default' => 'USD',
            'nullable'  => false,
            'comment'   => 'Company Currency'
        )
    );

    /*Version Related Queries*/

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_set_rel_mas'),
        'mage_version_number',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'Magento Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_set_rel_mas'),
        'mbiz_version_number',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'MicroBiz Version Number'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_set_rel_mas'),
        'last_updated_from',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 10,
            'nullable'  => false,
            'comment'   => 'Last Updated From Mag Or Mbiz'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_set_rel_mas'),
        'modified_by',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 50,
            'nullable'  => false,
            'comment'   => 'Modified by User Name'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_set_rel_mas'),
        'modified_at',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable'  => false,
            'comment'   => 'Modified Date Time'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_rel_mas'),
        'mage_version_number',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'Magento Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_rel_mas'),
        'mbiz_version_number',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'MicroBiz Version Number'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_rel_mas'),
        'last_updated_from',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 10,
            'nullable'  => false,
            'comment'   => 'Last Updated From Mag Or Mbiz'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_rel_mas'),
        'modified_by',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 50,
            'nullable'  => false,
            'comment'   => 'Modified by User Name'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_rel_mas'),
        'modified_at',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable'  => false,
            'comment'   => 'Modified Date Time'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_options_rel_mas'),
        'mage_version_number',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'Magento Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_options_rel_mas'),
        'mbiz_version_number',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'MicroBiz Version Number'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_options_rel_mas'),
        'last_updated_from',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 10,
            'nullable'  => false,
            'comment'   => 'Last Updated From Mag Or Mbiz'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_options_rel_mas'),
        'modified_by',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 50,
            'nullable'  => false,
            'comment'   => 'Modified by User Name'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_options_rel_mas'),
        'modified_at',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable'  => false,
            'comment'   => 'Modified Date Time'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_prd_rel_mas'),
        'mage_version_number',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'Magento Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_prd_rel_mas'),
        'mbiz_version_number',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'MicroBiz Version Number'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_prd_rel_mas'),
        'last_updated_from',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 10,
            'nullable'  => false,
            'comment'   => 'Last Updated From Mag Or Mbiz'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_prd_rel_mas'),
        'modified_by',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 50,
            'nullable'  => false,
            'comment'   => 'Modified by User Name'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_prd_rel_mas'),
        'modified_at',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_DATETIME,
            'nullable'  => false,
            'comment'   => 'Modified Date Time'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_prd_rel_mas'),
        'mbiz_sku',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_TEXT,
            'length'    => 50,
            'nullable'  => false,
            'comment'   => 'MicroBiz Product Sku'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('sync_records_header_tran'),
        'mage_version_number',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'Magento Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('sync_records_header_tran'),
        'mbiz_version_number',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'MicroBiz Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('sync_records_header_tran_history'),
        'mage_version_number',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'Magento Version Number'
        )
    );

    $installer->getConnection()->addColumn(
        $installer->getTable('sync_records_header_tran_history'),
        'mbiz_version_number',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 11,
            'nullable'  => false,
            'default' => 100,
            'comment'   => 'MicroBiz Version Number'
        )
    );
}
$installer->endSetup();
$stores = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->distinct(true)
    ->addFieldToSelect('store_id')->getColumnValues('store_id');
foreach($stores as $store) {
    $storeModel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->getCollection()->distinct(true)
        ->addFieldToFilter('store_id',$store)->getFirstItem()->getData();
    if($storeModel['id']) {
        $storeModel['include_inventory'] = 1;
        Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->load($storeModel['id'])->setData($storeModel)->save();
    }
}
$frontname = Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
if($frontname != 'admin') {
    $configFile = Mage::getBaseDir().DS.'app/code/local/Microbiz/Connector/etc/config.xml';
    $content=simplexml_load_file($configFile);
    $content->global->rewrite->connector_sales_order_cancel->from = '<![CDATA[#^/'.$frontname.'/sales_order/cancel/#]]>';
    $content->global->rewrite->connector_sales_order_shipment_save->from = '<![CDATA[#^/'.$frontname.'/sales_order_shipment/save/#]]>';
    $content->global->rewrite->connector_catalog_product_save->from = '<![CDATA[#^/'.$frontname.'/catalog_product/save/#]]>';
    $content->global->rewrite->connector_catalog_product_delete->from = '<![CDATA[#^/'.$frontname.'/catalog_product/delete/#]]>';
    $content->global->rewrite->connector_customer_save->from = '<![CDATA[#^/'.$frontname.'/customer/save/#]]>';
    $content->global->rewrite->connector_customer_delete->from = '<![CDATA[#^/'.$frontname.'/customer/delete/#]]>';
    $content->global->rewrite->connector_catalog_category_save->from = '<![CDATA[#^/'.$frontname.'/catalog_category/save/#]]>';
    $content->global->rewrite->connector_catalog_product_action_attribute_save->from = '<![CDATA[#^/'.$frontname.'/catalog_product_action_attribute/save/#]]>';
    $content->global->rewrite->connector_catalog_product_set_save->from = '<![CDATA[#^/'.$frontname.'/catalog_product_set/save/#]]>';
    $content->global->rewrite->connector_catalog_product_attribute_save->from = '<![CDATA[#^/'.$frontname.'/catalog_product_attribute/save/#]]>';
    $content->asXML($configFile);
}
