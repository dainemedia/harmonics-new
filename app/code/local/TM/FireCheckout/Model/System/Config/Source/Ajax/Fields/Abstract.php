<?php

class TM_FireCheckout_Model_System_Config_Source_Ajax_Fields_Abstract
{
    protected $_configPath = null;

    public function toArray()
    {
        $result = $this->_toArray();
        $label = Mage::helper('firecheckout')->__('Custom fields');
        if ($custom = $this->getSavedFields()) {
            $custom = array_combine($custom, $custom);
            $custom = array_diff_key($custom, $result);
            foreach ($custom as $field) {
                $result[$label][$field] = $field;
            }
        }
        return $result;
    }

    public function toOptionArray()
    {
        $result = array();
        foreach ($this->toArray() as $value => $label) {
            if (is_array($label)) {
                $options = array();
                foreach ($label as $_value => $_label) {
                    $options[] = array(
                        'label' => $_label,
                        'value' => $_value
                    );
                }
                $result[] = array(
                    'value' => $options,
                    'label' => $value
                );
            } else {
                $result[] = array(
                    'label' => $label,
                    'value' => $value
                );
            }
        }
        return $result;
    }

    public function getSavedFields()
    {
        if (!$this->_configPath) {
            return array();
        }

        $fields = Mage::getStoreConfig($this->_configPath);
        $fields = explode(",", $fields);
        $fields = array_filter($fields);
        natcasesort($fields);
        return $fields;
    }
}
