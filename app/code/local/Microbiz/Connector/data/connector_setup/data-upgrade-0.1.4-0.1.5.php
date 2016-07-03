<?php
//version 100
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 20/11/14
 * Time: 4:38 PM
 */



$apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
$instanceId = ($apiInformation['instance_id']) ? $apiInformation['instance_id'] : 1;
$url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
$versionUrl = $url . '/index.php/api/updatePluginVersion'; // prepare url for the rest call
$api_user = $apiInformation['api_user'];
$api_key = $apiInformation['api_key'];


// headers and data (this is API dependent, some uses XML)
$headers = array(
    'Accept: application/json',
    'Content-Type: application/json',
    'X-MBIZPOS-USERNAME: '.$api_user,
    'X-MBIZPOS-PASSWORD: '.$api_key
);
$versionData = array('mage_plugin_version' => '0.1.5');
Mage::log($versionData,null,'vesionUpdate.log');
$handle = curl_init();		//curl request to create the product
curl_setopt($handle, CURLOPT_URL, $versionUrl);
curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($handle, CURLOPT_POST, true);
curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($versionData));

$response = curl_exec($handle);	// send curl request to microbiz

$code = curl_getinfo($handle);
Mage::log($response,null,'vesionUpdate.log');
curl_close($handle);

