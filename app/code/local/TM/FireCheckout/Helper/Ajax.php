<?php

class TM_FireCheckout_Helper_Ajax// extends Mage_Core_Helper_Abstract
{
    protected $_dependencies = false;

    public function getRules()
    {
        $rules = array(
            'save'   => 'firecheckout/ajax_save',
            'reload' => 'firecheckout/ajax_reload'
        );
        $result = array();
        foreach ($rules as $key => $configPath) {
            $config = Mage::getStoreConfig($configPath);
            foreach ($config as $_key => $_value) {
                $result[$key][$_key] = array_filter(explode(',', $_value));
            }
        }
        return $result;
    }

    /**
     * Retrieve dependency between section and dependsOnSection
     *
     * @param  string $section
     *  payment-method
     *  shipping-method
     *  review
     * @param  mixed $dependsOnSection
     *  shipping
     *  billing
     *  payment-method
     *  shipping-method
     *  total
     *  cart
     *  coupon
     * @return boolean
     */
    public function getIsSectionDependsOn($section, $dependsOnSection)
    {
        if (!$this->_dependencies) {
            $config = Mage::getStoreConfig('firecheckout/ajax_reload');
            foreach ($config as $_section => $_dependencies) {
                $this->_dependencies[$_section] = explode(',', $_dependencies);
                $this->_dependencies[$_section] = array_filter($this->_dependencies[$_section]);
            }
        }
        if (empty($this->_dependencies[$section])) {
            return false;
        }
        if (is_array($dependsOnSection)) {
            $intersection = array_intersect(
                $dependsOnSection,
                $this->_dependencies[$section]
            );
            return count($intersection) > 0;
        } else {
            return in_array($dependsOnSection, $this->_dependencies[$section]);
        }
    }

    public function getIsPaymentMethodDependsOn($section)
    {
        return $this->getIsSectionDependsOn('payment-method', $section);
    }

    public function getIsShippingMethodDependsOn($section)
    {
        return $this->getIsSectionDependsOn('shipping-method', $section);
    }

    public function getIsTotalDependsOn($section)
    {
        return $this->getIsSectionDependsOn('review', $section);
    }
}
