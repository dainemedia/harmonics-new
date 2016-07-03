<?php
/* Version 100 */
class Microbiz_Connector_Model_Product_Attribute_Set_Api extends Mage_Catalog_Model_Product_Attribute_Set_Api
{
	/*
	<param><value><string>40phfuctek2kf85n8ioha14al2</string></value></param>
	<param><value><string>catalog_product_attribute_set.list</string></value></param>
	*/
 
     /**
     * Retrieve list of attribute sets
     *
     * @param array $filters
     * @param string|int $store
     * @return array
     */

	public function items()
    {
        $entityType = Mage::getModel('catalog/product')->getResource()->getEntityType();
        $collection = Mage::getResourceModel('eav/entity_attribute_set_collection')->setEntityTypeFilter($entityType->getId());

        $attributeset = array();
		$attributesets = array();
        
        foreach ($collection as $attributeSet) {
            $attributeset = $arrGroup = array(
						'id'	=>  "set_".$attributeSet->getAttributeSetId(),
						'magentoId'	=>  $attributeSet->getAttributeSetId(),
						// 'parentId'	=>  0,
						'attribute_set_id'	=> $attributeSet->getAttributeSetId(),
						'attribute_set_name' 	=> $attributeSet->getAttributeSetName(),
						'mageItem' 	=> $attributeSet->getAttributeSetName(),
						'mbizItem' 	=> $attributeSet->getAttributeSetName(),
        				'iconCls' => 'task-folder',
						'type' => 'set',
						'input' => 0,
						'sys_required' => 0,
						);
						
						// $attributeSet->getData();
			
			$groups = Mage::getModel('eav/entity_attribute_group')->getResourceCollection()->setAttributeSetFilter($attributeSet->getAttributeSetId())->load();
		
		$arrGroups = array();
		
		foreach($groups as $group)
		{		
		$arrGroup = array(
						'id'	=>  "group_".$group->getId(),
						'magentoId'	=> $group->getId(),
						// 'parentId'	=> $attributeSet->getAttributeSetId(),
						'mageItem' 	=> $group->getAttributeGroupName(),
						'mbizItem' 	=> $group->getAttributeGroupName(),
        				'iconCls' => 'task-folder',
						'type' => 'group',
						'input' => 0,
						'sys_required' => 0,
						);

		$nodeChildren = Mage::getResourceModel('catalog/product_attribute_collection')
		->setAttributeGroupFilter($group->getAttributeGroupId())
		->checkConfigurableProducts()
		->load();

		if ($nodeChildren->getSize() > 0) {
                $arrGroup['children'] = array();
                foreach ($nodeChildren->getItems() as $child) {
                    $attr = array(
						'magentoId'    => $child->getAttributeId(),
					    // 'parentId'	=> $group->getId(),
						'mageItem'        => $child->getAttributeCode(),
						//'mbiz_attribute_id'           => '',
						'mbizItem'        => $child->getAttributeCode(),
				        'leaf' => true,
						'iconCls' => 'task',
					    'type' => 'attribute',
						'input' => $child->getFrontendInput(),
						'sys_required' => 0,
                    );
			$applyTo = $child->getApplyTo();
			$isVisible = $child->getIsVisible();
			if(is_array($applyTo) && (empty($applyTo) || in_array("simple", $applyTo) || in_array("configurable", $applyTo)))
				if($isVisible == 1 || ($isVisible == 0 && $child->getAttributeCode() == 'sync_update_msg'))
					$arrGroup['children'][] = $attr;	   
			}
			
		}
		
					$group_clean = preg_replace('/\s*/', '', $group->getAttributeGroupName());
					$group_clean = strtolower($group_clean);

					$arrGroups[] = $arrGroup;
	    }
		$attributeset['children'] = $arrGroups;
		$attributesets[] = $attributeset;
        }

        return $attributesets;
    }
    
	public function products($filters = null, $store = null)
    {
        $entityType = Mage::getModel('catalog/product')->getResource()->getEntityType();
        $collection = Mage::getResourceModel('eav/entity_attribute_set_collection')->setEntityTypeFilter($entityType->getId());

        $attributeset = array();
		$productsList = array();
        
        foreach ($collection as $attributeSet) {
            $attributeset = $arrProduct = array(
						'id'	=>  "set_".$attributeSet->getAttributeSetId(),
						'magentoId'	=>  $attributeSet->getAttributeSetId(),
						// 'parentId'	=>  0,
						'attribute_set_id'	=> $attributeSet->getAttributeSetId(),
						'attribute_set_name' 	=> $attributeSet->getAttributeSetName(),
						'mageItem' 	=> $attributeSet->getAttributeSetName(),
						'mbizItem' 	=> $attributeSet->getAttributeSetName(),
        				'iconCls' => 'task-folder',
						'type' => 'set',
						'sys_required' => 0,
						);
						
						// $attributeSet->getData();
				$filters['attribute_set_id'] = $attributeSet->getAttributeSetId();
				// $products = Mage::getModel('catalog/product')->getCollection()->setAttributeSetFilter($attributeSet->getAttributeSetId())->load();
				// $products = Mage::getResourceModel('catalog/product')->getResource()->setAttributeSetFilter($attributeSet->getAttributeSetId())->setFlag('require_stock_items', true)->addAttributeToSelect('name')->addAttributeToSelect('price')->addAttributeToSelect('status');
       	        $products = Mage::getModel('Microbiz_Connector_Model_Product_Api')->listPartial($filters);

		$arrProducts = array();
		
		foreach($products as $product)
		{		
					$product_clean = preg_replace('/\s*/', '', $product['sku']);
					$product_clean = strtolower($product_clean);
					$arrProducts[] = $product;
	    }
		// $attributeset['children'] = $arrProducts;
		// $products[] = $attributeset;
		$productsList = array_merge($productsList, $arrProducts);
        }

        return $productsList;
    }
    /**
     * Return the EAV Entity Type ID for Product.
     * Usually this will return 4.
     */
    private function getProductEntityTypeId()
    {
        return Mage::getModel('catalog/product')->getResource()->getEntityType()->getId();
    }
    
    /**
     * Based on basedOnSetID is usually 4 (Default).
	<param><value><string>cl19t0dqhmheafqc0ccdeejc76</string></value></param>
	<param><value><string>catalog_product_attribute_set.create</string></value></param>
	<param>
		<value>
			<array>
				<data>
					<value><i4>26</i4></value>
					<value>
						<struct>
							<member>
								<name>attribute_set_name</name>
								<value><string>Groepsnaam 1</string></value>
							</member>
							<member>
								<name>sort_order</name>
								<value><i4>0</i4></value>
							</member>
						</struct>
					</value>
				</data>
			</array>
		</value>
	</param>
	*/
	public function create($basedOnSetID, array $data)
	{
		Mage::log(__CLASS__ . '::create '. $basedOnSetID .', '. var_export($data, true));
		//try
		//{
			// $attrOption = Mage_Eav_Model_Entity_Attribute_Set
			$attrSet = Mage::getModel("eav/entity_attribute_set");
			
			try
			{
				$attrSet->setData($data);
				$attrSet->setEntityTypeId($this->getProductEntityTypeId());
				$attrSet->save();
			} 
			catch(Exception $ex)
			{
				Mage::log("Error when attrSet->setData(): ". $ex->getMessage());
			}
			
			try
			{
				$attrSet->initFromSkeleton($basedOnSetID);
				$attrSet->save();
			}
			catch(Exception $ex)
			{
				Mage::log("Error when initFromSkeleton(): ". $ex->getMessage());	
			}
			
			return (int)$attrSet->getAttributeSetId();
		//} 
		//catch(Exception $ex)
		//{
		//	return $ex;
		//}
	}
	
	/*
	<param><value><string>cl19t0dqhmheafqc0ccdeejc76</string></value></param>
	<param><value><string>catalog_product_attribute_set.update</string></value></param>
	<param>
		<value>
			<array>
				<data>
					<value>
						<struct>
							<member>
								<name>attribute_set_id</name>
								<value><i4>381</i4></value>
							</member>
							<member>
								<name>attribute_set_name</name>
								<value><string>Groepsnaam 1 (gewijzigd)</string></value>
							</member>
							<member>
								<name>sort_order</name>
								<value><i4>8</i4></value>
							</member>
						</struct>
					</value>
				</data>
			</array>
		</value>
	</param>
	*/
	public function update(array $data)
	{
		try
		{
			// $attrOption = Mage_Eav_Model_Entity_Attribute_Set
			$attrSet = Mage::getModel("eav/entity_attribute_set");
			
			$attrSet->load($data["attribute_set_id"]);
			
			if(!$attrSet->getAttributeSetId())
			{
				$this->_fault("set_not_exists");
			}
			
			$attrSet->addData($data);
			
			$attrSet->save();
			
			return true;
		} 
		catch(Exception $ex)
		{
			return false;
		}
	}
	
	public function delete($attributeSetId)
	{
		try
		{
			// $attrOption = Mage_Eav_Model_Entity_Attribute_Set
			$attrSet = Mage::getModel("eav/entity_attribute_set");
			
			$attrSet->load($attributeSetId);
			
			$attrSet->delete();
			
			return true;
		} 
		catch(Exception $ex)
		{
			return false;
		}
	}
}

?>
