<?php

class TM_FireCheckout_Model_System_Config_Source_Ajax_Fields_Address_Billing
    extends TM_FireCheckout_Model_System_Config_Source_Ajax_Fields_Address
{
    protected $_configPath = 'firecheckout/ajax_save/billing';
    protected $_keyPrefix  = 'billing:';

    public function _toArray()
    {
        $result = parent::_toArray();
        $customerFields = Mage::getModel('firecheckout/system_config_source_ajax_fields_customer')->toArray();
        foreach ($customerFields as $key => $value) {
            $key = $this->_addPrefix($key);
            if (!isset($result[$key])) {
                $result[$key] = $value;
            }
        }
        natcasesort($result);
        return $result;
    }
}
