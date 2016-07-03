<?php
//version 104
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 15/4/14
 * Time: 11:08 AM
 */
class Microbiz_Connector_Block_Multishipping_Overview extends Mage_Checkout_Block_Multishipping_Overview
{
    /*public function getVirtualItems()
    {
        $items = array();
        foreach ($this->getBillingAddress()->getItemsCollection() as $_item) {
            if ($_item->isDeleted()) {
                continue;
            }
            if ($_item->getProduct()->getIsVirtual() && !$_item->getParentItemId()) {

                $arrGiftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                $arrGiftBuyData = unserialize($arrGiftBuyData);
                //print_r($arrGiftBuyData);
                if(count($arrGiftBuyData)>0)
                {
                    $customPrice =0;
                    foreach($arrGiftBuyData as $data)
                    {
                        if($data['gcd_type']==2)
                        {
                            $customPrice = $customPrice + $data['gcd_amt'];
                        }
                    }

                }

                $productOptions = $_item->getProduct()->getTypeInstance(true)->getOrderOptions($_item->getProduct());

                $arrAppliedOptions = $productOptions['options'];

                if(count($arrAppliedOptions)>0)
                {
                    //code to get the option title starts here
                    $OptionValues = Mage::getModel('sales/quote_item_option')->getCollection()->addFieldToFilter('item_id',$_item->getQuoteItemId())
                        ->addFieldToFilter('code','info_buyRequest')->getFirstItem()->getData();

                    $arrValues = unserialize($OptionValues['value']);
                    $productId = $arrValues['product'];
                    $arrOptions = $arrValues['options'];
                    foreach($arrOptions as $key=>$value)
                    {
                        $optionId = $key;
                        $optionValue = $value;
                    }
                    $arrProductOptions = Mage::getModel('catalog/product')->load($productId)->getOptions();
                    foreach($arrProductOptions as $Options)
                    {
                        foreach($Options->getValues() as $OptionsVal)
                        {
                            if( $Options->getId()==$optionId && $OptionsVal->getId()==$optionValue)
                            {
                                $optionLabel = $OptionsVal->getTitle();
                            }
                        }
                    }
                    //code to get the option title ends here

                    foreach($arrAppliedOptions as $option)
                    {
                        if($option['label']=='Gift Card' && $optionLabel=='Any Amount' && $_item->getPrice()==0)
                          //  if($_item->getId()=='771')
                        {



                            $_item->setPrice($customPrice);
                            $_item->setBasePrice($customPrice);
                            $_item->setPriceInclTax($customPrice);
                            $_item->setBasePriceInclTax($customPrice);

                            $_item->calcRowTotal();
                            $_item->save();
                        }
                    }

                }
                $items[] = $_item;
            }
        }

        return $items;
    }*/
    /**
     * @author - KT-174
     * @description - this method is called for Multishipping checkout on Overview page,in this method we are
     * adding the store credit information and gift card information details.
     *
     */
    public function renderTotals($totals, $colspan=null,$addressId,$arrMultiShipCreditInfo)
    {
        if ($colspan === null) {
            $colspan = $this->helper('tax')->displayCartBothPrices() ? 5 : 3;
        }
        //get the credit data from session if any store credits or giftcards applied.

        $quote = Mage::getModel('checkout/cart')->getQuote();
        $quoteCreditDiscountData = $quote->getCreditDiscountData();
        $quoteCreditDiscountData = unserialize($quoteCreditDiscountData);

        if(count($quoteCreditDiscountData)>0)
        {
            $totalCreditAmount=0;
            $arrMultiCreditData = Mage::getSingleton('checkout/session')->getMultiCreditData();
            $arrMultiCreditData = unserialize($arrMultiCreditData);
            Mage::log("multishipping credit data");
            Mage::log($arrMultiCreditData);
            foreach($arrMultiCreditData as $key=>$data)
            {
                if($key==$addressId)
                {
                    $totalCreditAmount = $data['credit_usage'];
                }
            }

            $storeCreditTotals='';

            if(is_array($quoteCreditDiscountData)):
                $i=0;
                foreach($quoteCreditDiscountData as $key=>$data)
                {
                    if($totalCreditAmount>0)
                    {

                        if($totalCreditAmount>=$data['credit_amt'])
                        {
                            $totalCreditAmount = $totalCreditAmount-$data['credit_amt'];
                            if($data['credit_type']==1)
                            {
                                $storeCreditTotals.='<tr>
                                        <td style="" class="a-right" colspan="3">
                                            Store Credit ('.$data["credit_no"].')
                                        </td>
                                        <td style="" class="a-right">
                                            <span class="price">-'.$this->helper('checkout')->formatPrice($data["credit_amt"]).'</span>
                                         </td>
                                    </tr>';
                            }
                            else {
                                $storeCreditTotals.='<tr>
                                        <td style="" class="a-right" colspan="3">
                                            Gift card ('.$data["credit_no"].')
                                        </td>
                                        <td style="" class="a-right">
                                            <span class="price">-'.$this->helper('checkout')->formatPrice($data["credit_amt"]).'</span>
                                         </td>
                                    </tr>';
                            }

                            $arrMultiShipCreditInfo[$addressId][$i]['credit_no'] = $data["credit_no"];
                            $arrMultiShipCreditInfo[$addressId][$i]['credit_amt'] = $data["credit_amt"];
                            $arrMultiShipCreditInfo[$addressId][$i]['credit_type'] = $data["credit_type"];
                            unset($quoteCreditDiscountData[$key]);
                        }
                        else{
                            if($data['credit_type']==1)
                            {
                                $storeCreditTotals.='<tr>
                                        <td style="" class="a-right" colspan="3">
                                            Store Credit ('.$data["credit_no"].')
                                        </td>
                                        <td style="" class="a-right">
                                            <span class="price">-'.$this->helper('checkout')->formatPrice($totalCreditAmount).'</span>
                                         </td>
                                    </tr>';
                            }
                            else {
                                $storeCreditTotals.='<tr>
                                        <td style="" class="a-right" colspan="3">
                                            Gift Card ('.$data["credit_no"].')
                                        </td>
                                        <td style="" class="a-right">
                                            <span class="price">-'.$this->helper('checkout')->formatPrice($totalCreditAmount).'</span>
                                         </td>
                                    </tr>';
                            }

                            $arrMultiShipCreditInfo[$addressId][$i]['credit_no'] = $data["credit_no"];
                            $arrMultiShipCreditInfo[$addressId][$i]['credit_amt'] = $totalCreditAmount;
                            $arrMultiShipCreditInfo[$addressId][$i]['credit_type'] = $data["credit_type"];
                            $quoteCreditDiscountData[$key]['credit_amt']= $data['credit_amt']-$totalCreditAmount;
                            $totalCreditAmount=0;
                        }
                        $i++;
                    }


                }

                if(count($arrMultiShipCreditInfo)>0)
                {
                    $quote->setMultiShipCreditInfo(serialize($arrMultiShipCreditInfo));

                    $arrMultiShippingCreditData = Mage::getSingleton('checkout/session')->getMultiData();
                    $arrMultiShippingCreditData = unserialize($arrMultiShippingCreditData);

                    if(count($arrMultiShippingCreditData)>0)
                    {
                        foreach($arrMultiShippingCreditData as $key=>$data)
                        {
                            foreach($data as $k=>$dat)
                            {
                                $arrMultiShipCreditInfo[$key][$k]['credit_no'] = $dat['credit_no'];
                                $arrMultiShipCreditInfo[$key][$k]['credit_amt'] = $dat['credit_amt'];
                                $arrMultiShipCreditInfo[$key][$k]['credit_type'] = $dat['credit_type'];

                            }

                        }
                    }

                    Mage::getSingleton('checkout/session')->unsMultiData();
                    Mage::getSingleton('checkout/session')->setMultiData(serialize($arrMultiShipCreditInfo));

                }
                $quote->setCreditDiscountData(serialize($quoteCreditDiscountData));
                $quote->save();

            endif;
        }



        $totals = $this->getChild('totals')->setTotals($totals)->renderTotals('', $colspan,$addressId,$arrMultiShipCreditInfo).$storeCreditTotals
            .$this->getChild('totals')->setTotals($totals)->renderTotals('footer', $colspan,$addressId,$arrMultiShipCreditInfo);
        return $totals;
    }
}