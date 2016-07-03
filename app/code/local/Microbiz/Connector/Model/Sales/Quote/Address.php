<?php
//version 100
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 27/11/14
 * Time: 1:40 PM
 */
class Microbiz_Connector_Model_Sales_Quote_Address extends Mage_Sales_Model_Quote_Address
{
    /**
     * Retrieve all visible items
     *
     * @return array
     */
    public function getAllVisibleItems()
    {
        Mage::log("came to getallvisibleitems");
        Mage::log("came to getallvisibleitems of microbiz/sales/model/quote/address.hp",null,'multiorder.log');
        Mage::log($this->getAddressType(),null,'multiorder.log');
        Mage::log($this->getId(),null,'multiorder.log');

        $items = array();
        foreach ($this->getAllItems() as $item) {
            if (!$item->getParentItemId()) {
                $productType = $item->getProduct()->getTypeId();
                if($productType=='mbizgiftcard') {
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
                    //Mage::log($customPrice);
                    $productOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());


                    $arrAppliedOptions = $productOptions['options'];
                    //Mage::log($arrAppliedOptions);

                    if(count($arrAppliedOptions)>0)
                    {
                        //code to get the option title starts here
                        $OptionValues = Mage::getModel('sales/quote_item_option')->getCollection()->addFieldToFilter('item_id',$item->getQuoteItemId())
                            ->addFieldToFilter('code','info_buyRequest')->getFirstItem()->getData();

                        $arrValues = unserialize($OptionValues['value']);
                        //Mage::log("arrvalues");
                        //Mage::log($arrValues);

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

                        Mage::log($optionLabel,null,'multiorder.log');


                        //code to get the option title ends here


                        //code to update the giftcard object with address id starts here.
                        if($optionLabel!='')
                        {
                            if($optionLabel=='Any Amount')
                            {
                                $itemPrice = $customPrice;
                                $addressId = $this->getId();
                                $arrGiftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                                $arrGiftBuyData = unserialize($arrGiftBuyData);

                                if(!empty($arrGiftBuyData))
                                {
                                    foreach($arrGiftBuyData as $key=>$giftData)
                                    {
                                        if($giftData['gcd_type']==2 && $giftData['gcd_amt']==$itemPrice)
                                        {
                                            $arrGiftBuyData[$key]['address_id'] = $addressId;
                                            $arrGiftBuyData[$key]['quote_item_id'] = $item->getId();
                                        }
                                    }
                                    Mage::getSingleton('checkout/session')->unsGiftBuyData();
                                    Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($arrGiftBuyData));

                                }


                            }
                            else {

                                $itemPrice = $item->getPrice();
                                $addressId = $this->getId();
                                $arrGiftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                                $arrGiftBuyData = unserialize($arrGiftBuyData);

                                if(!empty($arrGiftBuyData))
                                {
                                    foreach($arrGiftBuyData as $key=>$giftData)
                                    {
                                        if($giftData['gcd_type']==1 && $giftData['gcd_amt']==$itemPrice)
                                        {
                                            $arrGiftBuyData[$key]['address_id'] = $addressId;
                                            $arrGiftBuyData[$key]['quote_item_id'] = $item->getId();
                                        }
                                    }
                                    Mage::getSingleton('checkout/session')->unsGiftBuyData();
                                    Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($arrGiftBuyData));

                                }

                            }
                        }

                        $arrGiftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                        $arrGiftBuyData = unserialize($arrGiftBuyData);
                        Mage::log("after update session object with address ids",null,'multiorder.log');
                        Mage::log($arrGiftBuyData,null,'multiorder.log');

                        //code to update the giftcard object with address id ends here.

                        foreach($arrAppliedOptions as $option)
                        {
                            if($option['label']=='Gift Card' && $optionLabel=='Any Amount' && $item->getPrice()==0)
                            {



                                $item->setPrice($customPrice);
                                $item->setBasePrice($customPrice);
                                $item->setPriceInclTax($customPrice);
                                $item->setBasePriceInclTax($customPrice);

                                $item->calcRowTotal();
                                $item->save();
                            }
                        }
                    }
                }

                $items[] = $item;
            }
        }
        //Mage::log(count($items));


        return $items;
    }
}