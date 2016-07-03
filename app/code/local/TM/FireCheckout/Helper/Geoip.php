<?php

include_once("MaxMind/GeoIP/geoip.php");
include_once('MaxMind/GeoIP/geoipcity.php');
include_once("MaxMind/GeoIP/geoipregionvars.php");

class TM_FireCheckout_Helper_Geoip extends Mage_Core_Helper_Abstract
{
    public function detect($remoteAddr)
    {
        if (!function_exists('geoip_open')) {
            throw new Exception(
                Mage::helper('firecheckout')->__(
                    "GeoIP is enabled but not included. geoip_open function doesn't found"
                )
            );
        }

        $result = array();
        if (Mage::getStoreConfig('firecheckout/geo_ip/city')) {
            $filename = Mage::getBaseDir('lib')
                . DS
                . "MaxMind/GeoIP/data/"
                . Mage::getStoreConfig('firecheckout/geo_ip/city_file');

            if (is_readable($filename)) {
                $gi = geoip_open($filename, GEOIP_STANDARD);
                $record = geoip_record_by_addr($gi, $remoteAddr);
                if ($record) {
                    $region = Mage::getModel('directory/region')->loadByCode(
                        $record->region, $record->country_code
                    );
                    $result['region_id']  = $region->getId();
                    $result['city']       = $record->city;
                    $result['postcode']   = $record->postal_code;
                    $result['country_id'] = $record->country_code;
                }
                geoip_close($gi);
            } else {
                throw new Exception(
                    Mage::helper('firecheckout')->__(
                        "City detection is enabled but %s not found",
                        Mage::getStoreConfig('firecheckout/geo_ip/city_file')
                    )
                );
            }
        } elseif (Mage::getStoreConfig('firecheckout/geo_ip/country')) {
            $filename = Mage::getBaseDir('lib')
                . DS
                . "MaxMind/GeoIP/data/"
                . Mage::getStoreConfig('firecheckout/geo_ip/country_file');

            if (is_readable($filename)) {
                $gi = geoip_open($filename, GEOIP_STANDARD);
                $result['country_id'] = geoip_country_code_by_addr($gi, $remoteAddr);
                geoip_close($gi);
            } else {
                throw new Exception(
                    Mage::helper('firecheckout')->__(
                        "Country detection is enabled but %s not found",
                        Mage::getStoreConfig('firecheckout/geo_ip/country_file')
                    )
                );
            }
        }
        return $result;
    }
}
