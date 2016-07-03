<?php
class AffinityCloud_Catalog_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{
    protected $_disableFlat = false;

    public function isEnabledFlat()
    {
        if ($this->_disableFlat)
        return false;
        return parent::isEnabledFlat();
    }

    public function setDisableFlat($value)
    {
        $this->_disableFlat = (boolean)$value;
        $type = $value ? 'catalog/product' : 'catalog/product_flat';
        $this->setEntity(Mage::getResourceSingleton($type));
        return $this;
    }

}