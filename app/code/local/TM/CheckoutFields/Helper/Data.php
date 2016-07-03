<?php

class TM_CheckoutFields_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Retrieve enabled fields with their configuration
     *
     * @return array
     */
    public function getFields()
    {
        $fields = array();
        foreach (Mage::getStoreConfig('checkoutfields') as $field => $config) {
            if (!strstr($field, 'tm_field')) {
                continue;
            }
            if (isset($config['options']) && !empty($config['options'])) {
                $options = array();
                foreach (unserialize($config['options']) as $optionArr) {
                    $options[] = current($optionArr);
                }
                $config['options'] = $options;
            } else {
                $config['options'] = array();
            }
            $fields[$field] = $config;
        }
        return $fields;
    }

    /**
     * Retrieve enabled fields with their configuration
     *
     * @return array
     */
    public function getEnabledFields()
    {
        $fields = array();
        foreach ($this->getFields() as $field => $config) {
            if (!$config['status']) {
                continue;
            }
            $fields[$field] = $config;
        }
        return $fields;
    }
}
