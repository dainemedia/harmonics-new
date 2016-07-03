<?php

/**
 * @var Mage_Core_Model_Resource_Setup
 */
$installer = $this;

$installer->startSetup();

$collection = Mage::getResourceModel('core/config_data_collection');
$collection->addFieldToFilter('path', array('like' => 'firecheckout/%'))->load();
if (!$collection->count()) {
    $installer->endSetup();
    return;
}

// move old ajax_update config to the new one
$mapping = array(
    'firecheckout/ajax_save/billing' => array(
        'firecheckout/ajax_update/payment_method_on_country' => 'billing:country_id',
        'firecheckout/ajax_update/total_on_taxvat'           => array('billing:vat_id', 'billing:taxvat')
    ),
    'firecheckout/ajax_save/shipping' => array(
        'firecheckout/ajax_update/shipping_method_on_country' => 'shipping:country_id',
        'firecheckout/ajax_update/shipping_method_on_zip'     => 'shipping:postcode',
        'firecheckout/ajax_update/shipping_method_on_region'  => 'shipping:region_id',
        'firecheckout/ajax_update/total_on_shipping_country'  => 'shipping:country_id',
        'firecheckout/ajax_update/total_on_shipping_zip'      => 'shipping:postcode',
        'firecheckout/ajax_update/total_on_shipping_region'   => 'shipping:region_id',
        'firecheckout/ajax_update/total_on_taxvat'            => 'shipping:vat_id'
    ),
    'firecheckout/ajax_reload/payment-method' => array(
        'firecheckout/ajax_update/payment_method_on_country' => 'billing',
        'firecheckout/ajax_update/payment_method_on_total'   => 'total',
        'firecheckout/ajax_update/payment_method_on_cart'    => 'cart'
    ),
    'firecheckout/ajax_reload/shipping-method' => array(
        'firecheckout/ajax_update/shipping_method_on_country' => 'shipping',
        'firecheckout/ajax_update/shipping_method_on_zip'     => 'shipping',
        'firecheckout/ajax_update/shipping_method_on_region'  => 'shipping',
        'firecheckout/ajax_update/shipping_method_on_total'   => 'total',
        'firecheckout/ajax_update/shipping_method_on_cart'    => 'cart',
        'firecheckout/ajax_update/shipping_method_on_coupon'  => 'coupon'
    ),
    'firecheckout/ajax_reload/review' => array(
        'firecheckout/ajax_update/total_on_payment_method'   => 'payment-method',
        'firecheckout/ajax_update/total_on_shipping_method'  => 'shipping-method',
        'firecheckout/ajax_update/total_on_shipping_country' => 'shipping',
        'firecheckout/ajax_update/total_on_shipping_zip'     => 'shipping',
        'firecheckout/ajax_update/total_on_shipping_region'  => 'shipping',
        'firecheckout/ajax_update/total_on_taxvat'           => array('billing', 'shiping')
    )
);
$newConfig = array();
foreach ($mapping as $newPath => $rules) {
    if ($collection->getItemByColumnValue('path', $newPath)) {
        continue;
    }
    foreach ($rules as $oldPath => $newValue) {
        $oldItems = $collection->getItemsByColumnValue('path', $oldPath);
        if (!count($oldItems)) {
            continue;
        }
        foreach ($oldItems as $oldItem) {
            if (!$oldItem->getValue()) {
                continue;
            }
            if (!is_array($newValue)) {
                $newValue = array($newValue);
            }
            foreach ($newValue as $value) {
                $newConfig[$newPath][$oldItem->getScope()][$oldItem->getScopeId()][$value] = $value;
            }
        }
    }
}

$table = Mage::getResourceModel('core/config_data')->getMainTable();
foreach ($newConfig as $path => $scopes) {
    foreach ($scopes as $scope => $scopeIds) {
        foreach ($scopeIds as $scopeId => $values) {
            $value = implode(',', $values);
            $installer->run("
                INSERT INTO `$table` (scope, scope_id, path, value)
                    VALUES ('{$scope}', {$scopeId}, '{$path}', '{$value}');
            ");
        }
    }
}

$installer->endSetup();
