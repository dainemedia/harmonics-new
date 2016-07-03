<?php

class TM_FireCheckout_Model_System_Config_Source_Ajax_Fields_Address
    extends TM_FireCheckout_Model_System_Config_Source_Ajax_Fields_Abstract
{
    protected $_keyPrefix = null;

    public function _toArray()
    {
        $collection = Mage::getResourceModel('customer/address_attribute_collection');
        $collection->addOrder('frontend_label', 'asc');
        $result = array();
        foreach ($collection as $item) {
            if (!$this->canUse($item)) {
                continue;
            }
            $key = $this->_addPrefix($item->getAttributeCode());
            $result[$key] = $item->getFrontendLabel();
        }
        return $result;
    }

    protected function _addPrefix($key)
    {
        if ($this->_keyPrefix) {
            $key = $this->_keyPrefix . $key;
        }
        return $key;
    }

    public function canUse($item)
    {
        if (!$item->getFrontendLabel()) {
            return false;
        }
        return !in_array($item->getAttributeCode(), array(
            'vat_is_valid',
            'vat_request_id',
            'vat_request_date',
            'vat_request_success',
            'region' // region_id is used
        ));
    }
}
