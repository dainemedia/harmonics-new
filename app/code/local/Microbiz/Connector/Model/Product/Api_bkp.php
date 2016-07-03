<?php

class Microbiz_Connector_Model_Product_Api extends Mage_Catalog_Model_Product_Api
{
    /**
     * Retrieve list of products with partial info (id, sku, type, set, name, price, ...)
     *
     * @param array $filters
     * @param string|int $store
     * @return array
     */
    public function listPartial($filters = null, $store = null)
    {
        $collection = Mage::getModel('catalog/product')->getCollection()->setStoreId($this->_getStoreId($store))->setFlag('require_stock_items', true)->addAttributeToSelect('name')->addAttributeToSelect('price')->addAttributeToSelect('status');
        
        if (is_array($filters)) {
            try {
                foreach ($filters as $field => $value) {
                    if (isset($this->_filtersMap[$field])) {
                        $field = $this->_filtersMap[$field];
                    }
                    if ($field == 'created_at' || $field == 'updated_at')
                        $attributeFilter[] = array(
                            'attribute' => $field,
                            'from' => $value
                        );
                    else
                        $collection->addFieldToFilter($field, $value);
                }
                $collection->addFieldToFilter($attributeFilter);
                //$collection->addFieldToFilter('type_id', array( '=' => 'simple' ));
            }
            catch (Mage_Core_Exception $e) {
                $this->_fault('filters_invalid', $e->getMessage());
            }
        }
        
        $result = array();
        
        foreach ($collection as $product) {
            $arrProduct = array( // Basic product data
                // 'id'	=>  "product_".$product->getId(),
                'magentoId' => $product->getId(),
                'product_id' => $product->getId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'set' => $product->getAttributeSetId(),
                'mageItem' => $product->getName(),
                'mbizItem' => $product->getName(),
                'iconCls' => 'task',
                'leaf' => true,
                // 'type' => 'attribute',
                'type' => $product->getTypeId(),
                'sys_required' => 0,
                // 'status' => $product->getStatus(),
                'qty' => $product->getStockItem()->data
            );
            
            $result[] = $arrProduct;
        }
        
        return $result;
    }
    
    /**
     * Retrieve list of products with FULL info (id, sku, type, set, name, price, ...).
     * VERY SLOW!
     *
     * @param array $filters
     * @param string|int $store
     * @return array
     */
    public function listFull($filters = null, $store = null)
    {
        $collection = Mage::getModel('catalog/product')->getCollection()->setStoreId($this->_getStoreId($store))->addAttributeToSelect('*');
        
        $result = array();
        
        if (is_array($filters)) {
            try {
                foreach ($filters as $field => $value) {
                    if (isset($this->_filtersMap[$field])) {
                        $field = $this->_filtersMap[$field];
                    }
                    if ($field == 'created_at' || $field == 'updated_at')
                        $attributeFilter[] = array(
                            'attribute' => $field,
                            'from' => $value
                        );
                    else
                        $collection->addFieldToFilter($field, $value);
                }
                $collection->addFieldToFilter($attributeFilter);
                //$collection->addFieldToFilter('type_id', array( '=' => 'simple' ));
            }
            catch (Mage_Core_Exception $e) {
                $this->_fault('filters_invalid', $e->getMessage());
            }
        }
        
        $result = array();
        
        foreach ($collection as $product) {
            $arrProduct = array( // Basic product data
                'product_id' => $product->getId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'set' => $product->getAttributeSetId(),
                'type' => $product->getTypeId(),
                'categories' => $product->getCategoryIds(),
                'websites' => $product->getWebsiteIds(),
                'qty' => $product->getStockItem()->getQty(),
                'is_in_stock' => $product->getStockItem()->getIsInStock()
            );
            
            $arrProduct['attributes'] = $product->getData();
            $result[]                 = $arrProduct;
        }
        
        return $result;
    }
    /**
     * Create new product.
     *
     * @param string $type
     * @param int $set
     * @param string $sku
     * @param array $productData
     * @param string $store
     * @return int
     */
    public function create($type, $set, $sku, $productData, $store = null)
    {
        if (!$type || !$set || !$sku) {
            $this->_fault('data_invalid');
        }

        if (!in_array($type, array_keys(Mage::getModel('catalog/product_type')->getOptionArray()))) {
            $this->_fault('product_type_not_exists');
        }
       $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($set);
        if (is_null($attributeSet->getId())) {
            $this->_fault('product_attribute_set_not_exists');
        }
        if (Mage::getModel('catalog/product')->getResource()->getTypeId() != $attributeSet->getEntityTypeId()) {
            $this->_fault('product_attribute_set_not_valid');
        }

        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product');
        $product->setStoreId($this->_getStoreId($store))
            ->setAttributeSetId($set)
            ->setTypeId($type)
            ->setSku($sku);

        if (!isset($productData['stock_data']) || !is_array($productData['stock_data'])) {
            //Set default stock_data if not exist in product data
            $product->setStockData(array('use_config_manage_stock' => 0));
        }

        foreach ($product->getMediaAttributes() as $mediaAttribute) {
            $mediaAttrCode = $mediaAttribute->getAttributeCode();
            $product->setData($mediaAttrCode, 'no_selection');
        }

       $this->_prepareDataForSave($product, $productData);

        try {
            /**
             * @todo implement full validation process with errors returning which are ignoring now
             * @todo see Mage_Catalog_Model_Product::validate()
             */
            if (is_array($errors = $product->validate())) {
                $strErrors = array();
                foreach($errors as $code => $error) {
                    if ($error === true) {
                        $error = Mage::helper('catalog')->__('Attribute "%s" is invalid.', $code);
                    }
                    $strErrors[] = $error;
                }
                $this->_fault('data_invalid', implode("\n", $strErrors));
            }

            $product->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }

        return $product->getId();
    }
	
	/**
     * Update product data
     * 
     * @param int|string $productId
     * @param array $productData
     * @param string|int $store
     * @return boolean
     */
    public function update($productId, $productData, $store = null, $identifierType = null)
    {
        $product = parent::_getProduct($productId, $store, $identifierType);

        $this->_prepareDataForSave($product, $productData);

        try {
            /**
             * @todo implement full validation process with errors returning which are ignoring now
             * @todo see Mage_Catalog_Model_Product::validate()
             */
            if (is_array($errors = $product->validate())) {
                $strErrors = array();
                foreach($errors as $code => $error) {
                    if ($error === true) {
                        $error = Mage::helper('catalog')->__('Value for "%s" is invalid.', $code);
                    } else {
                        $error = Mage::helper('catalog')->__('Value for "%s" is invalid: %s', $code, $error);
                    }
                    $strErrors[] = $error;
                }
                $this->_fault('data_invalid', implode("\n", $strErrors));
            }

            $product->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }

        return true;
    }
	/**
     *  Set additional data before product saved
     *
     *  @param    Mage_Catalog_Model_Product $product
     *  @param    array $productData
     *  @return	  object
     */
	protected function _prepareDataForSave($product, $productData)
    {
        
		if (isset($productData['website_ids']) && is_array($productData['website_ids'])) {
            $product->setWebsiteIds($productData['website_ids']);
        }

        foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
            //Unset data if object attribute has no value in current store
            if (Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID !== (int) $product->getStoreId()
                && !$product->getExistsStoreValueFlag($attribute->getAttributeCode())
                && !$attribute->isScopeGlobal()
            ) {
                $product->setData($attribute->getAttributeCode(), false);
            }

            if ($this->_isAllowedAttribute($attribute)) {
                if (isset($productData[$attribute->getAttributeCode()])) {
                    $product->setData(
                        $attribute->getAttributeCode(),
                        $productData[$attribute->getAttributeCode()]
                    );
                } elseif (isset($productData['additional_attributes']['single_data'][$attribute->getAttributeCode()])) {
                    $product->setData(
                        $attribute->getAttributeCode(),
                        $productData['additional_attributes']['single_data'][$attribute->getAttributeCode()]
                    );
                } elseif (isset($productData['additional_attributes']['multi_data'][$attribute->getAttributeCode()])) {
                    $product->setData(
                        $attribute->getAttributeCode(),
                        $productData['additional_attributes']['multi_data'][$attribute->getAttributeCode()]
                    );
                }
            }
        }

        if (isset($productData['categories']) && is_array($productData['categories'])) {
            $product->setCategoryIds($productData['categories']);
        }

        if (isset($productData['websites']) && is_array($productData['websites'])) {
            foreach ($productData['websites'] as &$website) {
                if (is_string($website)) {
                    try {
                        $website = Mage::app()->getWebsite($website)->getId();
                    } catch (Exception $e) { }
                }
            }
            $product->setWebsiteIds($productData['websites']);
        }

        if (Mage::app()->isSingleStoreMode()) {
            $product->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
        }

        if (isset($productData['stock_data']) && is_array($productData['stock_data'])) {
            $product->setStockData($productData['stock_data']);
        }

        if (isset($productData['tier_price']) && is_array($productData['tier_price'])) {
             $tierPrices = Mage::getModel('catalog/product_attribute_tierprice_api')
                 ->prepareTierPrices($product, $productData['tier_price']);
             $product->setData(Mage_Catalog_Model_Product_Attribute_Tierprice_Api::ATTRIBUTE_CODE, $tierPrices);
        }

        /*
         * Check for configurable products array passed through API Call
        */
        if(isset($productData['configurable_products_data']) && is_array($productData['configurable_products_data'])) {
		
			/*
			
						$simpleProductsId=$productData['configurable_products_data'];
						$config_product = Mage::getModel('catalog/product')->load($product->getId());
						$productAttributeOptions = $config_product->getTypeInstance(true)->getConfigurableAttributesAsArray($config_product);
						foreach($productAttributeOptions as $productAttributeOption) {
							$configAttributes[]=$productAttributeOption['attribute_code'];
						}
						foreach($simpleProductsId as $simpleProductId) {
							$simpleProduct = Mage::getModel('catalog/product')->load($simpleProductId);
							$attributes = $simpleProduct->getAttributes();
							$simpleconfiginfo=array();
							foreach($configAttributes as $configAttribute) {
								$attributeValue = null;		
								if(array_key_exists($configAttribute , $attributes)){
									$attributesobj = $attributes["{$configAttribute}"];
									$attributeValue = $attributesobj->getFrontend()->getValue($simpleProduct);
								}
								$attribute_details = Mage::getSingleton("eav/config")->getAttribute("catalog_product", $configAttribute);
								$options = $attribute_details->getSource()->getAllOptions(false);
								$eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
							$code = $eavAttribute->getIdByCode('catalog_product', $configAttribute);
								foreach($options as $option){
									if($option["label"] == $attributeValue) {
										$attributeValueIndex=$option["value"];
									}
								}
								
								$simpleconfiginfo[]=array (
									'label' => $attributeValue,
									'attribute_id' => code,
									'value_index' => $attributeValueIndex,
								);
							}
							$configurableProductData[$simpleProductId]=$simpleconfiginfo;
						}
						$productData['configurable_products_data']=$configurableProductData;
			
			*/
            $product->setConfigurableProductsData($productData['configurable_products_data']);          
        }
          
        if(isset($productData['configurable_attributes_data']) && is_array($productData['configurable_attributes_data'])) {
			/*
				$configAttributes=$productData['configurable_attributes_data'];
						$i=0;
						$requiredFormat=array();
						foreach($configAttributes as $configAttribute)	 {
							$eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
							$code = $eavAttribute->getIdByCode('catalog_product', $configAttribute);
							$configArray= Mage::getModel('eav/entity_attribute')->load($code)->getData();
							$requiredFormat[$i]=array (
							  'id' => NULL,
							  'label' => $configArray['frontend_label'],
							  'use_default' => NULL,
							  'position' => NULL,
							  'values' => array (),
							  'attribute_id' => $configArray['attribute_id'],
							  'attribute_code' => $configArray['attribute_code'],
							  'frontend_label' => $configArray['frontend_label'],
							  'store_label' => $configArray['frontend_label'],
							  'html_id' => 'configurable__attribute_'.$i,
							);
						$i++;
						}
						$productData['configurable_attributes_data']=$requiredFormat;
			*/
            foreach($productData['configurable_attributes_data'] as $key => $data) {
                //Check to see if these values exist, otherwise try and populate from existing values
                $data['label']          =   (!empty($data['label']))            ? $data['label']            : $product->getResource()->getAttribute($data['attribute_code'])->getStoreLabel();
                $data['frontend_label'] =   (!empty($data['frontend_label']))   ? $data['frontend_label']   : $product->getResource()->getAttribute($data['attribute_code'])->getFrontendLabel();
                $productData['configurable_attributes_data'][$key] = $data;
            }
            $product->setConfigurableAttributesData($productData['configurable_attributes_data']);
            $product->setCanSaveConfigurableAttributes(1);
        }
    }
    /**
     * Retrieve product info
     *
     * @param int|string $productId
     * @param string|int $store
     * @param array $attributes
     * @return array
     */
    public function infoFull($productIds, $store = null, $attributes = null)
    {
        if (!is_array($productIds)) {
            $productIds = array(
                $productIds
            );
        }
        
        $product = Mage::getModel('catalog/product');
        
        foreach ($productIds as &$productId) {
            if ($newId = $product->getIdBySku($productId)) {
                $productId = $newId;
            }
        }
        
        $collection = Mage::getModel('catalog/product')->getCollection()->setStoreId($this->_getStoreId($store))->setFlag('require_stock_items', true)->addFieldToFilter('entity_id', array(
            'in' => $productIds
        ))->addAttributeToSelect('*');
        
        $result = array();
        
        foreach ($collection as $product) {
            
            
			$result = array( // Basic product data
                'product_id' => $product->getId(),
                'sku' => $product->getSku(),
                'set' => $product->getAttributeSetId(),
                'type' => $product->getTypeId(),
                'categories' => $product->getCategoryIds(),
                'websites' => $product->getWebsiteIds(),
                'qty' => (int) $product->getStockItem()->getQty(),
                'is_in_stock' => $product->getStockItem()->getIsInStock(),
                'media' => Mage::getModel('Mage_Catalog_Model_Product_Attribute_Media_Api')->items($product->getId()),
				
            );
           
			if($product->getTypeId() == "configurable"){  
				$AssociatedProducts = $product->getTypeInstance()->getUsedProducts();
				$configcount=0;
				foreach($AssociatedProducts as $AssociatedProduct) {
					$associatedProducts[$configcount]=(array)$AssociatedProduct->getData();
					$configcount++;
				}
				$result['associated_products']=$associatedProducts;
			} 
            foreach ($product->getTypeInstance()->getEditableAttributes() as $attribute) {
                if ($this->_isAllowedAttribute($attribute, $attributes)) {
                    $result[$attribute->getAttributeCode()] = $product->getData($attribute->getAttributeCode());
                }
            }
            
        }
        
        return $result;
    }
	
	
		
    public function productCreate($type, $set, $sku, $productData, $store = null)
    {  
        // Mage::log($productData['storeinventory']);
		try{
        $productId = $this->create($type, $set, $sku, $productData, $store);
        
        foreach ($productData['storeinventory'] as &$inventoryData) {
            $inventoryData['material_id'] = $productId;
        }
		Mage::getModel('Microbiz_Connector_Model_Storeinventory_Api')->createMbizInventory($productData['storeinventory'], $productId);
		}
         catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        return $productId;
    }
    
    public function productUpdate($productId, $productData, $store = null, $identifierType = null)
    {
        $product = $this->_getProduct($productId, $store, $identifierType);
		// $this->_fault('data_invalid', $productId.' '. $identifierType);
        try {
            parent::update($productId, $productData);
			foreach ($productData['storeinventory'] as &$inventoryData){
                $inventoryData['material_id'] = $product->getId();
            }
			Mage::getModel('Microbiz_Connector_Model_Storeinventory_Api')->createMbizInventory($productData['storeinventory'], $product->getId());
        }
        catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        return true;
    }
}

?>