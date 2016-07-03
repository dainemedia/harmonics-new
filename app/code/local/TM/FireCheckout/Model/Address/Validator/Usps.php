<?php

class TM_FireCheckout_Model_Address_Validator_Usps extends Mage_Usa_Model_Shipping_Carrier_Usps
{
    protected $_result = null;

    protected $_addresses = array();

    protected $_isVerified = array();

    public function isValid()
    {
        if (!$this->getAddresses()) {
            return true;
        }

        $userId  = $this->getConfigData('userid');
        if (empty($userId)) {
            $this->_result['error'] = Mage::helper('firecheckout')->__(
                'USPS user id is not correct. See the configuration at System > Configuration > Shipping Methods > USPS'
            );
            return false;
        }
        $request = "<AddressValidateRequest USERID='{$userId}'>";
        foreach ($this->getAddresses() as $id => $address) {
            $regionCode = Mage::getModel('directory/region')
                ->load($address['region_id'])
                ->getCode();

            $request .= '<Address ID="' . $id . '">';
            if (isset($address['street'][1])) {
                $address1 = $address['street'][0];
                $address2 = $address['street'][1];
            } else {
                $address1 = '';
                $address2 = $address['street'][0];
            }
            $request .= '<Address1>' . $address1 . '</Address1>';
            $request .= '<Address2>' . $address2 . '</Address2>';
            $request .= '<City>' . $address['city'] . '</City>';
            $request .= '<State>' . $regionCode . '</State>';
            $request .= '<Zip5>' . $address['postcode'] . '</Zip5>';
            $request .= '<Zip4></Zip4>';
            $request .= '</Address>';
        }
        $request .= "</AddressValidateRequest>";

        $responseBody = $this->_getCachedQuotes($request);
        if ($responseBody === null) {
            $debugData = array('request' => $request);
            try {
                $url = $this->getConfigData('gateway_url');
                if (!$url) {
                    $url = $this->_defaultGatewayUrl;
                }
                $client = new Zend_Http_Client();
                $client->setUri($url);
                $client->setConfig(array('maxredirects'=>0, 'timeout'=>30));
                $client->setParameterGet('API', 'Verify');
                $client->setParameterGet('XML', $request);
                $response = $client->request();
                $responseBody = $response->getBody();

                $debugData['result'] = $responseBody;
                $this->_setCachedQuotes($request, $responseBody);
            } catch (Exception $e) {
                $debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
                $responseBody = '';
            }
            $this->_debug($debugData);
        }
        $this->_result = $this->_parseXmlResponse($responseBody);

        return !$this->getError() && $this->isVerified();
    }

    public function getError()
    {
        if (isset($this->_result['error'])) {
            return $this->_result['error'];
        }
        return false;
    }

    public function getAddressError($id)
    {
        foreach ($this->_result[$id] as $verifiedAddress) {
            if (isset($verifiedAddress['error'])) {
                return $verifiedAddress['error'];
            }
        }
        return false;
    }

    public function isVerified($id = null)
    {
        if (!count($this->_isVerified)) {
            $mapping = array(
                'Address1' => 'street',
                'Address2' => 'street',
                'City'     => 'city',
                'State'    => 'region',
                'Zip5'     => 'postcode'
            );
            foreach ($this->_result as $id => $verifiedAddresses) {
                $this->_isVerified[$id] = 0;

                foreach ($verifiedAddresses as $verifiedAddress) {
                    if (isset($verifiedAddress['error'])) {
                        break;
                    }

                    $verified = true;
                    foreach ($verifiedAddress as $name => $value) {
                        if (!isset($mapping[$name])) {
                            continue;
                        }

                        if ('Address1' === $name) {
                            $originalValue = $this->_addresses[$id][$mapping[$name]][0];
                        } elseif ('Address2' === $name) {
                            $originalValue = $this->_addresses[$id][$mapping[$name]][1];
                        } else {
                            $originalValue = $this->_addresses[$id][$mapping[$name]];
                        }

                        if (0 !== strcasecmp($value, $originalValue)) {
                            $verified = false;
                            break;
                        }
                    }

                    if ($verified) {
                        // if address ruturned by usps is same as user entered,
                        // then we should not show verification window for this address
                        $this->_isVerified[$id] = 1;
                        break;
                    }
                }
            }
        }

        if ($id) {
            return $this->_isVerified[$id];
        }
        return min($this->_isVerified);
    }

    public function addAddress($address, $id)
    {
        $this->_addresses[$id] = $address;
    }

    public function getAddresses()
    {
        return $this->_addresses;
    }

    public function getVerifiedAddresses($id)
    {
        if (isset($this->_result[$id])) {
            return $this->_result[$id];
        }
        return null;
    }

    /**
     * Parse calculated rates
     *
     * @link http://www.usps.com/webtools/htm/Rate-Calculators-v2-3.htm
     * @param string $response
     * @return Mage_Shipping_Model_Rate_Result
     */
    protected function _parseXmlResponse($response)
    {
        $result = array();
        $xml    = simplexml_load_string($response);
        if (!is_object($xml)) {
            return array(
                'error' => 'Unable to parse usps response'
            );
        }

        if (is_object($xml->Number) && is_object($xml->Description) && (string)$xml->Description!='') {
            return array(
                'error' => (string)$xml->Description
            );
        }

        $result = array();
        if (is_object($xml->Address)) {
            foreach ($xml->Address as $address) {
                if (is_object($address->Error) && (string)$address->Error->Description!='') {
                    $result[(string)$address->attributes()->ID][] = array(
                        'error' => (string)$address->Error->Description
                    );
                    continue;
                }

                $id = (string)$address->attributes()->ID;
                $result[$id][] = array(
                    'Address1' => is_object($address->Address1) ? (string)$address->Address1 : '',
                    'Address2' => is_object($address->Address2) ? (string)$address->Address2 : '',
                    'City'     => is_object($address->City)     ? (string)$address->City     : '',
                    'State'    => is_object($address->State)    ? (string)$address->State    : '',
                    'Zip5'     => is_object($address->Zip5)     ? (string)$address->Zip5     : ''//,
                    // 'Zip4'     => is_object($address->Zip4)     ? (string)$address->Zip4     : ''
                );
            }
        }

        return $result;
    }

    /**
     * Log debug data to file
     *
     * @param mixed $debugData
     */
    protected function _debug($debugData)
    {
        if ($this->getDebugFlag()) {
            Mage::getModel('core/log_adapter', 'firecheckout_' . $this->getCarrierCode() . '.log')
               ->setFilterDataKeys($this->_debugReplacePrivateDataKeys)
               ->log($debugData);
        }
    }
}
