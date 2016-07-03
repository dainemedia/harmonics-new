<?php
//version 104
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 6/2/15
 * Time: 12:06 PM
 */
$installer = $this;
$installer->startSetup();
if (version_compare(Mage::getVersion(), '1.6.0', '<='))
{
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_rel_mas'),
        'status',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 1,
            'default'  => 1,
            'nullable' => false,
            'comment'   => 'Attribute Mappigs Status '
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog_eav_attribute'),
        'is_mappable',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 1,
            'unsigned'  => true,
            'default' => 1,
            'nullable'  => true,
            'comment'   => 'Is Allowed for Mappings'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog_eav_attribute'),
        'is_used_to_create_mapping',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 1,
            'unsigned'  => true,
            'default' => 0,
            'nullable'  => true,
            'comment'   => 'Is Used to Create  Mappings  in MicroBiz'
        )
    );

}
else{
    $installer->getConnection()->addColumn(
        $installer->getTable('mbiz_attr_rel_mas'),
        'status',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 1,
            'nullable'  => false,
            'default' => 1,
            'comment'   => 'Attribute Mappigs Status '
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog_eav_attribute'),
        'is_used_to_create_mapping',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 1,
            'unsigned'  => true,
            'default' => 0,
            'nullable'  => true,
            'comment'   => 'Is Used to Create  Mappings  in MicroBiz'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog_eav_attribute'),
        'is_mappable',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 1,
            'unsigned'  => true,
            'default' => 1,
            'nullable'  => true,
            'comment'   => 'Is Allowed for Mappings'
        )
    );
}
$installer->run("
INSERT IGNORE INTO {$this->getTable('mbiz_attr_rel_mas')} (mbiz_id, magento_attr_code,mbiz_attr_code) VALUES(10078, 'visibility', 'visibility');
UPDATE {$this->getTable('mbiz_attr_rel_mas')}, {$this->getTable('eav_attribute')}
    SET {$this->getTable('mbiz_attr_rel_mas')}.magento_id = {$this->getTable('eav_attribute')}.attribute_id
    WHERE {$this->getTable('mbiz_attr_rel_mas')}.magento_attr_code = {$this->getTable('eav_attribute')}.attribute_code and {$this->getTable('eav_attribute')}.entity_type_id = 4 and {$this->getTable('mbiz_attr_rel_mas')}.magento_attr_code='visibility';

update {$this->getTable('catalog_eav_attribute')}  set is_mappable = 0 where attribute_id IN(select attribute_id from {$this->getTable('eav_attribute')} where attribute_code NOT IN('country_of_manufacture','description','image','meta_description','meta_keyword','meta_title','name','news_from_date','news_to_date','pos_product_status','price','short_description','sku','small_image','status','store_price','sync_status','tax_class_id','thumbnail','visibility','weight') and entity_type_id =4 and is_user_defined = 0 and attribute_id <(SELECT attribute_id  FROM {$this->getTable('eav_attribute')} WHERE entity_type_id = 4 and attribute_code = 'store_price'));
delete  FROM {$this->getTable('mbiz_options_rel_mas')} WHERE mbiz_attr_id IN ('10056','10076');
");
$installer->run("
INSERT IGNORE INTO {$this->getTable('mbiz_attr_rel_mas')}( magento_id,mbiz_id,magento_attr_code, mbiz_attr_code )
SELECT magento_id,mbiz_id,magento_attr_code, mbiz_attr_code
FROM {$this->getTable('mbiz_attr_rel_mas')}
WHERE mbiz_attr_set_id IS NOT NULL and magento_attr_code not IN (SELECT magento_attr_code from {$this->getTable('mbiz_attr_rel_mas')} where mbiz_attr_set_id IS  NULL group by magento_attr_code)
AND id > 19
GROUP BY magento_id
");
$installer->run("DELETE FROM {$this->getTable('mbiz_options_rel_mas')} WHERE magento_id IN (SELECT attribute_id FROM {$this->getTable('eav_attribute')} WHERE attribute_code IN ('cost_price','prd_supplier_id','do_not_discount','prd_return','is_qty_decimal','qty_increments','prd_brand_id','volume','alternate_sku','manufacturer_upc','item_no','style','mfr_sugg_price','prd_id','simple_prd_name','prd_type','prd_created_by','accept_qty_decimals','accept_returns','include_in_qp','min_qty_for_out_of_stock','minimum_supplier_order','min_qty_to_sale'));


DELETE  FROM {$this->getTable('mbiz_attr_rel_mas')} WHERE `magento_id` IN (SELECT attribute_id FROM {$this->getTable('eav_attribute')} WHERE attribute_code IN ('cost_price','prd_supplier_id','do_not_discount','prd_return','is_qty_decimal','qty_increments','prd_brand_id','volume','alternate_sku','manufacturer_upc','item_no','style','mfr_sugg_price','prd_id','simple_prd_name','prd_type','prd_created_by','accept_qty_decimals','accept_returns','include_in_qp','min_qty_for_out_of_stock','minimum_supplier_order','min_qty_to_sale'));


DELETE FROM {$this->getTable('eav_attribute')} WHERE attribute_code IN ('cost_price','prd_supplier_id','do_not_discount','prd_return','is_qty_decimal','qty_increments','prd_brand_id','volume','alternate_sku','manufacturer_upc','item_no','style','mfr_sugg_price','prd_id','simple_prd_name','prd_type','prd_created_by','accept_qty_decimals','accept_returns','include_in_qp','min_qty_for_out_of_stock','minimum_supplier_order','min_qty_to_sale');");
//$installer->run("DELETE FROM  {$this->getTable('mbiz_options_rel_mas')} WHERE  `mbiz_attr_id` IN(SELECT Distinct(mbiz_id)  FROM {$this->getTable('mbiz_attr_rel_mas')} WHERE `mbiz_attr_code` IN('tax_class_id','prd_brand_id','prd_supplier_id'));");
//$installer->run("DELETE FROM {$this->getTable('mbiz_attr_rel_mas')} WHERE `mbiz_attr_code` IN('prd_brand_id','prd_supplier_id')");
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