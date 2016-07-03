<?php
//version 101
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 3/2/15
 * Time: 4:21 PM
 */
class Microbiz_Connector_Model_Product_Action extends Mage_Catalog_Model_Product_Action
{
    /**
     * Update attribute values for entity list per store
     *
     * @param array $productIds
     * @param array $attrData
     * @param int $storeId
     * @return Mage_Catalog_Model_Product_Action
     */
    public function updateAttributes($productIds, $attrData, $storeId)
    {
        //Mage::log("came to update Attributes Microbiz",null,'massupdate.log');
        //Mage::log($attrData,null,'massupdate.log');
        //Mage::log($productIds,null,'massupdate.log');
        Mage::dispatchEvent('catalog_product_attribute_update_before', array(
            'attributes_data' => &$attrData,
            'product_ids'   => &$productIds,
            'store_id'      => &$storeId
        ));

        $this->_getResource()->updateAttributes($productIds, $attrData, $storeId);
        $this->setData(array(
            'product_ids'       => array_unique($productIds),
            'attributes_data'   => $attrData,
            'store_id'          => $storeId
        ));

        // register mass action indexer event
        Mage::getSingleton('index/indexer')->processEntityAction(
            $this, Mage_Catalog_Model_Product::ENTITY, Mage_Index_Model_Event::TYPE_MASS_ACTION
        );

        Mage::dispatchEvent('catalog_product_attribute_update_after', array(
            'product_ids'   => $productIds,
        ));

        //Dispatching the MicroBiz onProductMassUpate event to sync the updated attributes into sync.
        $attrData['is_version_update'] = true;
        Mage::dispatchEvent('ktree_product_massupdate_save', array(
            'updatedattributes' => &$attrData,
            'productids'   => &$productIds,
            'store_id'      => &$storeId
        ));

        return $this;
    }
}