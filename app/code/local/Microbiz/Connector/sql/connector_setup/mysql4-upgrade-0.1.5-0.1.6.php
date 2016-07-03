<?php
//version 100

$installer = $this;

$installer->startSetup();

if (version_compare(Mage::getVersion(), '1.6.0', '<='))
{
    $installer->getConnection()->addColumn(
        $installer->getTable('sync_records_header_tran'),
        'is_initial_sync',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 0,
            'comment'   => 'Is Initial Sync Record'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('sync_records_header_tran_history'),
        'is_initial_sync',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 11,
            'nullable'  => false,
            'default' => 0,
            'comment'   => 'Is Initial Sync Record'
        )
    );
}
else {
    $installer->getConnection()->addColumn(
        $installer->getTable('sync_records_header_tran'),
        'is_initial_sync',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,null,
            'length'    => 11,
            'nullable'  => false,
            'default' => 0,
            'comment'   => 'Is Initial Sync Record'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('sync_records_header_tran_history'),
        'is_initial_sync',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,null,
            'length'    => 11,
            'nullable'  => false,
            'default' => 0,
            'comment'   => 'Is Initial Sync Record'
        )
    );
}
$installer->run("ALTER TABLE {$this->getTable('sync_mbiz_status_tran')} CHANGE sync_header_id sync_header_id VARCHAR(255) NOT NULL");



$installer->endSetup();

$frontname = Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
/*if($frontname != 'admin') {
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
}*/
if($frontname != 'admin') {
    $configFile = Mage::getBaseDir().DS.'app/code/local/Microbiz/Connector/etc/config.xml';
    $content=simplexml_load_file($configFile);
    foreach($content->global->rewrite as $child) {
        foreach($child as $key=>$childkey) {
            if (strpos($content->global->rewrite->$key->from,'^/admin/') !== false) {
                $cDataText =  str_replace("^/admin/","^/".$frontname."/",$content->global->rewrite->$key->from);
                $cDataText = '<![CDATA['.$cDataText.']]>';
                $content->global->rewrite->$key->from = $cDataText;
            }
        }
    }
    $content->asXML($configFile);
    //read the entire string
    $str=file_get_contents($configFile);
    //replace something in the file string - this is a VERY simple example
    $str=str_replace("&lt;","<",$str);
    $str=str_replace("&gt;",">",$str);
    //write the entire string
    file_put_contents($configFile, $str);

}
