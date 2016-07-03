<?php

class TM_FireCheckout_Model_Quote_Address extends Mage_Sales_Model_Quote_Address
// Unirgy_DropshipSplit_Model_Quote_Address
{
    /**
     * Validate address attribute values
     *
     * @return bool
     */
    public function validate()
    {
        $errors = array();
        $helper = Mage::helper('customer');
        $this->implodeStreetAddress();
        $formConfig = Mage::getStoreConfig('firecheckout/address_form_status');

        if (!Zend_Validate::is($this->getFirstname(), 'NotEmpty')) {
            $errors[] = $helper->__('Please enter the first name.');
        }
        if (!Zend_Validate::is($this->getLastname(), 'NotEmpty')) {
            $errors[] = $helper->__('Please enter the last name.');
        }

        if ('required' === $formConfig['company']
            && !Zend_Validate::is($this->getCompany(), 'NotEmpty')) {

            $errors[] = $helper->__('Please enter the company.'); // translate
        }

        if ('required' === $formConfig['street1']
            && !Zend_Validate::is($this->getStreet(1), 'NotEmpty')) {

            $errors[] = $helper->__('Please enter the street.');
        }

        if ('required' === $formConfig['city']
            && !Zend_Validate::is($this->getCity(), 'NotEmpty')) {

            $errors[] = $helper->__('Please enter the city.');
        }

        if ('required' === $formConfig['telephone']
            && !Zend_Validate::is($this->getTelephone(), 'NotEmpty')) {

            $errors[] = $helper->__('Please enter the telephone number.');
        }

        if ('required' === $formConfig['fax']
            && !Zend_Validate::is($this->getFax(), 'NotEmpty')) {

            $errors[] = $helper->__('Please enter the fax.'); // translate
        }

        $_havingOptionalZip = Mage::helper('directory')->getCountriesWithOptionalZip();
        if ('required' === $formConfig['postcode']
            && !in_array($this->getCountryId(), $_havingOptionalZip)
            && !Zend_Validate::is($this->getPostcode(), 'NotEmpty')) {

            $errors[] = $helper->__('Please enter the zip/postal code.');
        }

        if ('required' === $formConfig['country_id']
            && !Zend_Validate::is($this->getCountryId(), 'NotEmpty')) {

            $errors[] = $helper->__('Please enter the country.');
        }

        if ('required' === $formConfig['region']
            && $this->getCountryModel()->getRegionCollection()->getSize()
            && !Zend_Validate::is($this->getRegionId(), 'NotEmpty')) {

            $errors[] = $helper->__('Please enter the state/province.');
        }

        if (empty($errors) || $this->getShouldIgnoreValidation()) {
            return true;
        }
        return $errors;
    }
}
