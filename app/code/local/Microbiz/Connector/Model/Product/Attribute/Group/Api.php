<?php
//Version 108
class Microbiz_Connector_Model_Product_Attribute_Group_Api extends Mage_Catalog_Model_Api_Resource
{	
	/**
     * Retrieve list of products attribute group info.
     *
     * @param string|int $setId
     * @return array
     */

	public function items($setId = null)
	{
		$groups = Mage::getModel('eav/entity_attribute_group')->getResourceCollection();
        $attrSetRel = '';
		if(!is_null($setId) && !empty($setId) && is_numeric($setId))
		{
			$groups->setAttributeSetFilter($setId); // ->setSortOrder('ASC');

            $attrSetRel = Mage::helper('microbiz_connector')->checkIsObjectExists($setId, 'AttributeSets');

        }
		$groups->load();

		$arrGroups = array();
		
		foreach($groups as $group)
		{
            $checkAttributeGroupRelationExists = Mage::getModel('mbizattributegroup/mbizattributegroup')->getCollection()->addFieldToFilter('magento_id', $group->getAttributeGroupId())->getFirstItem()->getData();
            $mbizAttributeGroupId = '';
            if ($checkAttributeGroupRelationExists) {
                $mbizAttributeGroupId = $checkAttributeGroupRelationExists['mbiz_id'];
            }
					$arrGroup = array(
						'attribute_group_id' 	=> $group->getAttributeGroupId(),
						'attribute_set_id' 		=> $group->getAttributeSetId(),
						'attribute_group_name' 	=> $group->getAttributeGroupName(),
                        'mbiz_attribute_group_id' => $mbizAttributeGroupId,
						'sort_order' 			=> $group->getSortOrder(),
						'default_id' 			=> $group->getDefaultId(),
						'id'				=> $group->getId()
						);

		$nodeChildren = Mage::getResourceModel('catalog/product_attribute_collection')->setAttributeGroupFilter($group->getAttributeGroupId())->addFieldToFilter('is_mappable', 1)->checkConfigurableProducts()->load();

		if ($nodeChildren->getSize() > 0) {
                $arrGroup['attributes'] = array();
                foreach ($nodeChildren->getItems() as $child) {
                    $attr = array(
			'attribute_id'                => $child->getAttributeId(),
                        'attribute_code'              => $child->getAttributeCode(),
                        'entity_id'         => $child->getEntityAttributeId(),
			'apply_to' => $child->getApplyTo()
                    );
			$applyTo = $child->getApplyTo();
			$isVisible = $child->getIsVisible();
            $checkAttributeRelationExists = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $child->getAttributeId())->getFirstItem()->getData();
            $mbizAttributeId = '';
            $mbizAttributeCode = '';
            if (!empty($attrSetRel) && $checkAttributeRelationExists) {
                $mbizAttributeId = $checkAttributeRelationExists['mbiz_id'];
                $mbizAttributeCode = $checkAttributeRelationExists['mbiz_attr_code'];
            }
            else {
                $checkAttributeRelationExists = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('mbiz_attr_set_id', array('null' => true))->addFieldToFilter('magento_id', $child->getAttributeId())->getFirstItem()->getData();
                $mbizAttributeId = $checkAttributeRelationExists['mbiz_id'];
                $mbizAttributeCode = $checkAttributeRelationExists['mbiz_attr_code'];
            }
            if(is_array($applyTo) && (empty($applyTo) || in_array("simple", $applyTo) || in_array("configurable", $applyTo)))
                if($isVisible == 1 || ($isVisible == 0 && $child->getAttributeCode() == 'sync_update_msg')) {
                    $arrGroup['attributes'][$child->getAttributeCode()] = $child->getData();
                    $arrGroup['attributes'][$child->getAttributeCode()]['mbiz_attribute_id'] = $mbizAttributeId;
                    $arrGroup['attributes'][$child->getAttributeCode()]['mbiz_attribute_code'] = $mbizAttributeCode;
                    $attributeId = $child->getAttributeId();
                    if($child->getFrontendInput() == 'select' || $child->getFrontendInput() == 'multiselect') {
                        $arrGroup['attributes'][$child->getAttributeCode()]['attribute_options'] = Mage::getModel('Microbiz_Connector_Model_Entity_Attribute_Option_Api')->items($attributeId);
                    }
                    /*Code to add version numbers starts here*/
                    $attrId = $child->getAttributeId();
                    //$attributeRel = Mage::helper('microbiz_connector')->checkIsObjectExists($attrId, 'Attributes');
                    if(!is_null($setId) && !empty($setId) && is_numeric($setId))
                    {
                        $attributeRel = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $attrId)->addFieldToFilter('mbiz_attr_set_id', $attrSetRel['mbiz_id'])->setOrder('id', 'asc')->getFirstItem()->getData();;
                        if(!empty($attributeRel)) {
                            $arrGroup['attributes'][$child->getAttributeCode()]['mage_version_number'] = $attributeRel['mage_version_number'];
                            $arrGroup['attributes'][$child->getAttributeCode()]['mbiz_version_number'] = $attributeRel['mbiz_version_number'];
                            $arrGroup['attributes'][$child->getAttributeCode()]['mbiz_id'] = $attributeRel['mbiz_id'];
                        }
                        else {
                            $arrGroup['attributes'][$child->getAttributeCode()]['mage_version_number'] = 100;
                            $arrGroup['attributes'][$child->getAttributeCode()]['mbiz_version_number'] = 100;
                        }
                    }
                }


                }
		}

					$group_clean = preg_replace('/\s*/', '', $group->getAttributeGroupName());
					$group_clean = strtolower($group_clean);

			$arrGroups[$group_clean] = $arrGroup;
               
  		// sort alphabetically by name
		// $arrGroups['children'] = $nodeChildren->getItems();
		}
        if(!empty($attrSetRel)) {
            $arrGroups['mage_version_number'] = $attrSetRel['mage_version_number'];
            $arrGroups['mbiz_version_number'] = $attrSetRel['mbiz_version_number'];

        }
        else {
            $arrGroups['mage_version_number'] = 100;
            $arrGroups['mbiz_version_number'] = 100;
        }
		return $arrGroups;
	}



	public function create($setId, array $data)
	{
		try
		{
			// $attrOption = Mage_Eav_Model_Entity_Attribute_Group
			$attrOption = Mage::getModel("eav/entity_attribute_group");
			
			$attrOption->addData($data);
			
			// check if there already exists a group with the give groupname
			if($attrOption->itemExists())
			{
				$this->_fault("group_already_exists");
			}
			
			$attrOption->save();
			
			return (int)$attrOption->getAttributeGroupId();
		} 
		catch(Exception $ex)
		{
			return false;
		}
	}

	public function update(array $data)
	{
		try
		{
			// $attrOption = Mage_Eav_Model_Entity_Attribute_Group
			$attrOption = Mage::getModel("eav/entity_attribute_group");
			
			$attrOption->load($data["attribute_group_id"]);
			
			// check if the requested group exists...
			if(!$attrOption->getAttributeGroupId())
			{
				$this->_fault("group_not_exists");
			}
			
			$attrOption->addData($data);
			
			$attrOption->save();
			
			return true;
		} 
		catch(Exception $ex)
		{
			return false;
		}
	}
	


	public function delete($groupId)
	{
		try
		{
			// $attrOption = Mage_Eav_Model_Entity_Attribute_Group
			$attrOption = Mage::getModel("eav/entity_attribute_group");
			
			$attrOption->load($groupId);
			
			// check if the requested group exists...
			if(!$attrOption->getAttributeGroupId())
			{
				$this->_fault("group_not_exists");
			}
			
			// save data
			$attrOption->delete();
			
			return true;
		} 
		catch(Exception $ex)
		{
			return false;
		}
	}
	
	/*public function addAttribute($groupID, $attributeID)
	{
		$linkAttrSetGroup = Mage::getResourceSingleton("core/resource")->getConnection("core_write");
		
		
		if ($setId && $groupId && $object->getEntityTypeId()) {
            $write = $this->_getWriteAdapter();
            $table = $this->getTable('entity_attribute');


            $data = array(
                'entity_type_id' => $object->getEntityTypeId(),
                'attribute_set_id' => $setId,
                'attribute_group_id' => $groupId,
                'attribute_id' => $attrId,
                'sort_order' => (($object->getSortOrder()) ? $object->getSortOrder() : $this->_getMaxSortOrder($object) + 1),
            );

            $condition = "$table.attribute_id = '$attrId'
                AND $table.attribute_set_id = '$setId'";
            $write->delete($table, $condition);
            $write->insert($table, $data);

        }
	}*/
	
	
	/*private function getSetup() 
	{
		if(!isset($this->_setup))
		{
			$this->_setup = new Mage_Eav_Model_Entity_Setup('core_setup');
		}
		
		return $this->_setup;
	}*/
}

?>

