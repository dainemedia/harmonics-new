<?php

class TM_FireCheckout_Block_Address_Validator extends Mage_Core_Block_Template
{
    /**
     * Get relevant path to template
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->_template) {
            $this->_template = 'tm/firecheckout/address/validator.phtml';
        }
        return $this->_template;
    }

    public function getTitle($type)
    {
        if ('billing' === $type) {
            return $this->__('Billing Address');
        } else {
            return $this->__('Shipping Address');
        }
    }

    public function renderVerifiedAddressFields(array $address, $type = 'billing')
    {
        $nameToId = array(
            'Address1' => 'street1',
            'Address2' => 'street2',
            'City'     => 'city',
            'State'    => 'region_id',
            'Zip5'     => 'postcode'
        );

        $output = '';
        foreach ($address as $key => $value) {
            if (!isset($nameToId[$key])) {
                continue;
            }
            if ('State' === $key) {
                $value = Mage::getModel('directory/region')
                    ->loadByCode($value, 'US')
                    ->getId();
            }
            $output .= '<input class="input-verified" type="hidden" '
                . 'name="' . $type . ':' . $nameToId[$key] . '" '
                . 'value="' . $value . '" />';
        }
        return $output;
    }
}
