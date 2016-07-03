<?php

class TM_FireCheckout_Helper_Address extends Mage_Core_Helper_Abstract
{
    /**
     * Retrive sorted and grouped fields by rows
     * @return array
     * <code>
     *  array(
     *      array(
     *          'name'
     *      ),
     *      array(
     *          'email',
     *          'company'
     *      ),
     *      ...
     *  )
     * </code>
     */
    public function getSortedFields()
    {
        $result = array();
        $fields = Mage::getStoreConfig('firecheckout/address_form_order');
        asort($fields);

        $i = 0;
        $prevOrder = 0;
        foreach ($fields as $field => $order) {
            if ($order - $prevOrder > 1) {
                $i++;
            }
            $prevOrder = $order;
            $result[$i][] = $field;
        }
        return $result;
    }
}
