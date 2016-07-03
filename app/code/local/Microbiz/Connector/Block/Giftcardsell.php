<?php
//version 102
class Microbiz_Connector_Block_Giftcardsell extends Mage_Core_Block_Template
{
     /**
     * @author - KT-174
     * @description - This method is used to get the gift card amount ranges from mbiz and gift configuration information
     * from the magento config
     */
    public function mbizGetGiftCardSaleInfo()
    {
        $product = Mage::getModel('catalog/product');
        $sku = Mage::getStoreConfig('connector/settings/giftcardsku');
        $productId = $product->getIdBySku($sku);
        $product->load($productId);

        $productOptions = $product->getOptions();
        $AvailableAmounts = '';
        if(count($productOptions)>0)
        {

            foreach($productOptions as $option)
            {
                if($option->getTitle()=='Gift Card')
                {
                    $AvailableAmounts = '<ul>';
                    foreach($option->getValues() as $value)
                    {
                        $price = round($value->getPrice(),2);
                        if($price!=0.00 || $price!='' || $price!=0)
                        {
                            $AvailableAmounts.='<li><a class="mbiz_gift_range" href="javascript:void(0);" mbizgiftcard_amt ='.$price.' mbizgiftcard_type=1 >$ '.$price.'</a> </li>';
                        }
                        else{
                            $AvailableAmounts.='<ul><li><a class="mbiz_gift_range" href="javascript:void(0);" mbizgiftcard_amt ='.$price.' mbizgiftcard_type=2 >Any Amount</a> </li>';
                        }
                    }
                }

            }
            if($AvailableAmounts!='')
            {
                $AvailableAmounts.= '</ul>';
            }
        }
        else
        {
            $AvailableAmounts = 'No Gift Card Ranges Available Currently.';
        }


        return $AvailableAmounts;
    }
}