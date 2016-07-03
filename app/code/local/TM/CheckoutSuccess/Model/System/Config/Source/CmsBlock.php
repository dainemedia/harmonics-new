<?php

class TM_CheckoutSuccess_Model_System_Config_Source_CmsBlock
{
    public function toOptionArray()
    {
        $collection = Mage::getResourceModel('cms/block_collection')
            ->addFieldToFilter('is_active', 1)
            ->addOrder('title', 'ASC');
        $blocks = $collection->toOptionArray();
        array_unshift($blocks, array(
            'value' => 0,
            'label' => Mage::helper('adminhtml')->__('No')
        ));
        return $blocks;
    }
}
