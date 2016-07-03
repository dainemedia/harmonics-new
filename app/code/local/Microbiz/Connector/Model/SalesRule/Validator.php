<?php
//Version 100
class Microbiz_Connector_Model_SalesRule_Validator extends Mage_SalesRule_Model_Validator
{

    /**
     * @param Mage_Sales_Model_Quote_Item_Abstract $item
     * @return Mage_SalesRule_Model_Validator|void
     * @author KT174
     * @description This method is used to apply discount to cart items. In this method we are restricting the applying
     * of discount to giftCard product.
     */
    public function process(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        //Mage::log("came to my method");
        //Mage::log($item->getSku());
        $itemSku = $item->getSku();
        $giftCardSku = Mage::getStoreConfig('connector/settings/giftcardsku');
        if($itemSku!=$giftCardSku)
        {
            parent::process($item);
        }

    }
}