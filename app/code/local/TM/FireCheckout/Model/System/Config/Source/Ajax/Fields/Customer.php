<?php

class TM_FireCheckout_Model_System_Config_Source_Ajax_Fields_Customer
    extends TM_FireCheckout_Model_System_Config_Source_Ajax_Fields_Abstract
{
    public function _toArray()
    {
        $collection = Mage::getResourceModel('customer/attribute_collection');
        $collection->addOrder('frontend_label', 'asc');
        $result = array();
        foreach ($collection as $item) {
            if (!$this->canUse($item)) {
                continue;
            }
            $result[$item->getAttributeCode()] = $item->getFrontendLabel();
        }
        return $result;
    }

    public function canUse($item)
    {
        if (!$item->getFrontendLabel()) {
            return false;
        }
        return !in_array($item->getAttributeCode(), array(
            'password_hash',
            'rp_token',
            'rp_token_created_at',
            'reward_update_notification',
            'regreward_warning_notificationion',
            'website_id',
            'store_id',
            'created_at',
            'created_in',
            'default_billing',
            'default_shipping',
            'disable_auto_group_change',
            'group_id',
            'confirmation'
        ));
    }
}
