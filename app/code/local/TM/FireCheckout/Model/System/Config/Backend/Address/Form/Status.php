<?php

class TM_FireCheckout_Model_System_Config_Backend_Address_Form_Status
    extends Mage_Core_Model_Config_Data
{
    /**
     * Change field eav attribute config before saving the value
     *
     * @return TM_FireCheckout_Model_System_Config_Backend_Address_Form_Status
     */
    protected function _beforeSave()
    {
        $value = $this->getValue();
        $field = $this->getField();

        // if option was not changed - don't need to update the attribute
        if ($value == Mage::getStoreConfig('firecheckout/address_form_status/' . $field)) {
            return $this;
        }

        if ('street1' === $field) {
            $field = 'street';
        } elseif ('region' === $field) {
            $field = 'region_id';

            /* fix for the previous firecheckout bug. Region should not be required */
            /* @var $collection Mage_Customer_Model_Resource_Address_Attribute_Collection */
            $collection = Mage::getResourceModel('customer/address_attribute_collection')
                ->setCodeFilter('region');
            $attribute = $collection->getFirstItem();
            if ($attribute) {
                $attribute->setValidateRules(null)
                    ->setIsRequired(0);
                $attribute->save();
            }
            /* end of fix */
        }

        /* @var $collection Mage_Customer_Model_Resource_Address_Attribute_Collection */
        $collection = Mage::getResourceModel('customer/address_attribute_collection')
            ->setCodeFilter($field);
        $attribute = $collection->getFirstItem();

        if (!$attribute) {
            return $this;
        }

        // $entities = Mage::getResourceModel('customer/setup');
        $attributes = array();
        $filename = Mage::getBaseDir('app') . "/code/core/Mage/Customer/Model/Resource/Setup.php";
        if (is_readable($filename)) {
            $setup      = new Mage_Customer_Model_Resource_Setup('customer_setup');
            $entities   = $setup->getDefaultEntities();
            $attributes = $entities['customer_address']['attributes'];
        } else {
            $attributes = 'a:2:{s:15:"max_text_length";i:255;s:15:"min_text_length";i:1;}';
        }
        switch ($value) {
            case 'required':
                $rules = null;
                if (is_string($attributes)) {
                    $rules = $attributes;
                } elseif (!empty($attributes[$field]['validate_rules'])) {
                    $rules = $attributes[$field]['validate_rules'];
                }
                $attribute->setValidateRules($rules)
                    ->setIsRequired(1);

                if ('region_id' === $field || 'postcode' === $field) { // Magento options should be used
                    $attribute->setValidateRules(null)
                        ->setIsRequired(0);
                }

                break;
            case 'optional':
            case 'hidden':
                $attribute->setValidateRules(null)
                    ->setIsRequired(0);
                break;
        }
        $attribute->save();

        return $this;
    }
}
