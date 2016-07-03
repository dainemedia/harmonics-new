<?php

class TM_FireCheckout_Model_Taxvat_Validator
{
    protected $_message = '';
    protected $_helper = null;

    protected $_patterns = array(
        'AT' => '/^U[0-9]{8}$/',
        'BE' => '/^0?[0-9]{*}$/',
        'CZ' => '/^[0-9]{8,10}$/',
        'DE' => '/^[0-9]{9}$/',
        'CY' => '/^[0-9]{8}[A-Z]$/',
        'DK' => '/^[0-9]{8}$/',
        'EE' => '/^[0-9]{9}$/',
        'EL' => '/^[0-9]{9}$/',
        'ES' => '/^[0-9A-Z][0-9]{7}[0-9A-Z]$/',
        'FI' => '/^[0-9]{8}$/',
        'FR' => '/^[0-9A-Z]{2}[0-9]{9}$/',
        'GB' => '/^([0-9]{9}|[0-9]{12})~(GD|HA)[0-9]{3}$/',
        'UK' => '/^([0-9]{9}|[0-9]{12})~(GD|HA)[0-9]{3}$/',
        'HU' => '/^[0-9]{8}$/',
        'IE' => '/^[0-9][A-Z0-9\\+\\*][0-9]{5}[A-Z]$/',
        'IT' => '/^[0-9]{11}$/',
        'LT' => '/^([0-9]{9}|[0-9]{12})$/',
        'LU' => '/^[0-9]{8}$/',
        'LV' => '/^[0-9]{11}$/',
        'MT' => '/^[0-9]{8}$/',
        'NL' => '/^[0-9]{9}B[0-9]{2}$/',
        'PL' => '/^[0-9]{10}$/',
        'PT' => '/^[0-9]{9}$/',
        'SE' => '/^[0-9]{12}$/',
        'SI' => '/^[0-9]{8}$/',
        'SK' => '/^[0-9]{10}$/'
    );

    public function isValid($taxvat, $countryCode)
    {
        $this->_message = '';
        $this->_helper  = Mage::helper('firecheckout');
        $taxvatCountry = substr($taxvat, 0, 2);
        if (in_array($taxvatCountry, array_keys($this->_patterns))) {
            $taxvat = str_replace($taxvatCountry, '', $taxvat);
            $countryCode = $taxvatCountry;
        }

        if (Mage::getStoreConfig('firecheckout/taxvat/vies')) {
            return $this->isValidVies($taxvat, $countryCode);
        }
        return $this->isValidRegexp($taxvat, $countryCode);
    }

    public function isValidVies($taxvat, $countryCode)
    {
        $countryCodeMapping = array(
            'UK' => 'GB',
            'GR' => 'EL'
        );
        if (array_key_exists($countryCode, $countryCodeMapping)) {
            $countryCode = $countryCodeMapping[$countryCode];
        }

        if (!isset($this->_patterns[$countryCode])) {
            $this->_message = 'The provided CountryCode is invalid for the VAT number';
            return false;
        }

//        $vies  = new SoapClient('http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl');
//        $check = new firecheckoutCheckVat($countryCode, $taxvat);
//        try {
//            $ret = $vies->checkVat($check);
//        } catch (SoapFault $e) {
//            $ret = $e->faultstring;
//            $pattern = '/\{ \'([A-Z_]*)\' \}/';
//            $n = preg_match($pattern, $ret, $matches);
//            $ret = $matches[1];
//            $faults = array(
//              'INVALID_INPUT'       => 'The provided CountryCode is invalid or the VAT number is empty',
//              'SERVICE_UNAVAILABLE' => 'The SOAP service is unavailable, try again later',
//              'MS_UNAVAILABLE'      => 'The Member State service is unavailable, try again later or with another Member State',
//              'TIMEOUT'             => 'The Member State service could not be reached in time, try again later or with another Member State',
//              'SERVER_BUSY'         => 'The service cannot process your request. Try again later.'
//            );
//            $this->_message = $faults[$ret];
//            return false;
//        }
//        return true;

        try {
            $http = new Varien_Http_Adapter_Curl();
            $http->setConfig(array(
                'timeout' => 30
            ));
            $http->write(
                Zend_Http_Client::POST,
                'http://ec.europa.eu/taxation_customs/vies/viesquer.do',
                '1.1',
                array(),
                http_build_query(array(
                    'ms'    => $countryCode,
                    'iso'   => $countryCode,
                    'vat'   => $taxvat
                ))
            );
            $response = $http->read();
            $http->close();
        } catch (Exception $e) {
            throw $e;
        }

        $response = str_replace(array("\n", "\r", "\t"), '', /*strip_tags(*/$response/*)*/);
        if (empty($response) || strstr($response, 'Yes, valid VAT number')) { // if service is not available of correct vat number
            return true;
        } elseif (strstr($response, 'No, invalid VAT number')) {
            $this->_message = 'Invalid VAT number';
        } elseif (strstr($response, 'Service unavailable')) {
            $this->_message = 'The VAT validation service unavailable. Please re-submit your request later.';
        } elseif (strstr($response, 'Member State service unavailable')) {
            $this->_message = 'The VAT validation service unavailable. Please re-submit your request later.';
        } elseif (strstr($response, 'Error: Incomplete')) {
            $this->_message = 'The provided CountryCode is invalid or the VAT number is empty';
        } elseif (strstr($response, 'Request time-out')) {
            $this->_message = 'The VAT validation service cannot process your request. Try again later.';
        } elseif (strstr($response, 'System busy')) {
            $this->_message = 'The VAT validation service cannot process your request. Try again later.';
        } else {
            $this->_message = 'Unknown VAT validation service message. Try again later.';
        }

        return false;
    }

    public function isValidRegexp($taxvat, $countryCode)
    {
        if (!isset($this->_patterns[$countryCode])) {
            $this->_message = 'The provided CountryCode is invalid for the VAT number';
            return false;
        }

        $taxvat = str_replace($countryCode, '', $taxvat);
        if (!preg_match($this->_patterns[$countryCode], trim($taxvat))) {
            $this->_message = 'Invalid VAT number';
            return false;
        }

        return true;
    }

    public function getMessage()
    {
        return $this->_helper->__($this->_message);
    }
}

/**** SoapValidation ****/
class firecheckoutCheckVat
{
    /**
     * @var string
     */
    var $countryCode;

    /**
     * @var string
     */
    var $vatNumber;

    function __construct($cc, $vat)
    {
        $this->countryCode = $cc;
        $this->vatNumber = $vat;
    }
}
