<?php
//version 120
class Microbiz_Connector_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {

        $this->loadLayout();
        $this->renderLayout();
    }
    /*
     * @author KT097
     * Function for update shipping prices based on store selection in mbiz delivery and Instore pickup shipping methods
     */

    public function getEstimateShippingAction()
    {
        $postdata=$this->getRequest()->getPost();
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        $quoteData = $quote->getData();
        $quote_id = $quote->getId();
        Mage::getSingleton('checkout/session')->setCustomQuoteId($quote_id);

        if($postdata['type'] == 2) {
            $pickupData = Mage::getSingleton('checkout/session')->getPickup();
            $pickupData[$quote_id]['store'] =  $postdata['store'];
            $pickupData[$quote_id]['window_type'] = 2;
            $pickupData[$quote_id]['method'] = 'instorepickup_instorepickup';
            $pickupData[$quote_id]['date'] =  $postdata['date'];
            $pickupData[$quote_id]['deliveryWindow'] =  $postdata['deliveryWindow'];
            $pickupData[$quote_id]['shippingZone'] =  $postdata['shippingZone'];
            $pickupData[$quote_id]['note'] =  $postdata['note'];
            if(isset($quoteData['is_multi_shipping']) && $quoteData['is_multi_shipping']) {
                $addressId = $postdata['addressId'];
                $pickupData[$quote_id][$addressId] = $postdata;
            }
            Mage::getSingleton('checkout/session')->setInStoreShipping($postdata['shippingPrice']);
            if($pickupData){
                Mage::getSingleton('checkout/session')->setPickup($pickupData);
            }
        }
        else if($postdata['type'] == 3) {
            $deliveryData = Mage::getSingleton('checkout/session')->getDelivery();
            $deliveryData[$quote_id]['store'] =  $postdata['store'];
            $deliveryData[$quote_id]['date'] =  $postdata['date'];
            $deliveryData[$quote_id]['window_type'] = 3;
            $deliveryData[$quote_id]['method'] = 'mbizdelevery_mbizdelevery';
            $deliveryData[$quote_id]['shippingZone'] =  $postdata['shippingZone'];
            $deliveryData[$quote_id]['deliveryWindow'] =  $postdata['deliveryWindow'];
            $deliveryData[$quote_id]['note'] =  $postdata['note'];
            $deliveryData[$quote_id]['shippingPrice'] =  $postdata['shippingPrice'];
            if(isset($quoteData['is_multi_shipping'])  && $quoteData['is_multi_shipping']) {
                $addressId = $postdata['addressId'];
                $deliveryData[$quote_id][$addressId] = $postdata;
            }
            Mage::getSingleton('checkout/session')->setLocalDeliveryShipping($postdata['shippingPrice']);
            if($deliveryData){
                Mage::getSingleton('checkout/session')->setDelivery($deliveryData);
            }
        }
        $jsonresponse=array();
        if(isset($quoteData['is_multi_shipping']) && $quoteData['is_multi_shipping']) {
            foreach($quote->getAllShippingAddresses() as $address){
                if($address->getId() == $postdata['addressId']){

                    $address->setCollectShippingRates(true);
                    $address->collectShippingRates();
                    $address->collectTotals();
                    $rates = $address->collectShippingRates()
                        ->getGroupedAllShippingRates();

                    if($postdata['type'] == 3) {
                        $block = Mage::app()->getLayout()->createBlock('checkout/multishipping_shipping');
                        foreach ($rates['mbizdelevery'] as $rate) {

                            $excludingTax = $block->getShippingPrice($address,$rate->getPrice(),Mage::helper('tax')->displayShippingPriceIncludingTax());
                            $includingTax = $block->getShippingPrice($address,$rate->getPrice(), true);
                            if (Mage::helper('tax')->displayShippingBothPrices() && $includingTax != $excludingTax) {
                                $shippingText = $excludingTax.'(Incl. Tax'.$includingTax.')';
                            }
                            else {
                                $shippingText = $excludingTax;
                            }
                            $jsonresponse['mbizdelivery'] = $rate->getMethodTitle().'  '.$shippingText;

                        }
                    }
                    $quote->save();
                }
            }
            $jsonresponse['status'] = $quoteData['is_multi_shipping'];
        }
        else
        {
            $cart = Mage::getSingleton('checkout/cart');
            $address = $cart->getQuote()->getShippingAddress();
            $address->setCollectShippingrates(true);
            $cart->save();

            // Find if our shipping has been included.
            $rates = $address->collectShippingRates()
                ->getGroupedAllShippingRates();
            if($postdata['type'] == 3) {
                $block = Mage::app()->getLayout()->createBlock('checkout/onepage_shipping_method_available');
                foreach ($rates['mbizdelevery'] as $rate) {

                    $excludingTax = $block->getShippingPrice($rate->getPrice(),Mage::helper('tax')->displayShippingPriceIncludingTax());
                    $includingTax = $block->getShippingPrice($rate->getPrice(), true);
                    if (Mage::helper('tax')->displayShippingBothPrices() && $includingTax != $excludingTax) {
                        $shippingText = $excludingTax.'(Incl. Tax'.$includingTax.')';
                    }
                    else {
                        $shippingText = $excludingTax;
                    }
                    $jsonresponse['mbizdelivery'] = $rate->getMethodTitle().'  '.$shippingText;
                }
            }
            $quote->save();
            $jsonresponse['status'] = 0;

        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonresponse));
        return;
    }
    public function checkGiftCardExists($price,$type,$sku)
    {
        //Mage::log('came to checkgiftcardexists',null,'multiorder.log');
        //Mage::log('price'.$price,null,'multiorder.log');
        //Mage::log('type'.$type,null,'multiorder.log');
        if($price>0 && $type>0)
        {
            $quote = Mage::getSingleton('checkout/cart')->getQuote();
            $items = $quote->getAllItems();
            $status=1;
            //Mage::log('status before'.$status,null,'multiorder.log');
            foreach($items as $item)
            {
                $itemType = $item->getProductType();
                if($itemType=='mbizgiftcard') {
                    //Mage::log('giftcard product exists',null,'multiorder.log');
                    $customOptions = $item->getProduct()
                        ->getTypeInstance(true)
                        ->getOrderOptions($item->getProduct());
                    if($type==2) {
                        if($customOptions['options'][0]['value']=='Any Amount')
                        {
                            $status=0;
                            //Mage::log('giftcard exists anyamount'.$status,null,'multiorder.log');
                        }
                    }
                    else {
                        if($customOptions['options'][0]['value']=='Fixed Amount '.$price)
                        {
                            $status=0;
                            //Mage::log('giftcard exists fixed '.$status,null,'multiorder.log');
                        }
                    }

                }


            }
        }
        else {
            $status=0;
        }
        //Mage::log('status after'.$status,null,'multiorder.log');
        //$giftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
        //$giftBuyData = unserialize($giftBuyData);
        //Mage::log("after all udpate in controller",null,'multiorder.log');
        //Mage::log($giftBuyData,null,'multiorder.log');
        return $status;
    }

    /**
     * @author KT-174
     * @description This method is used to get the gift card price and find it in custom options and add to cart.
     */
    public function mbizBuyGiftCardAction()
    {
        //Mage::getSingleton('checkout/session')->unsGiftBuyData();
        $postData = $this->getRequest()->getPost();
        $product = Mage::getModel('catalog/product');
        $sku = Mage::getStoreConfig('connector/settings/giftcardsku');
        $productId = $product->getIdBySku($sku);
        $price = $postData['gift_amount'];
        $type = $postData['card_type'];
        if($price>0 && $type>0)
        {
            //check the giftcard item with the price is already exists or not if exists stop further process.

            $isNotExists = $this->checkGiftCardExists($price,$type,$sku);

            if($isNotExists==1) {
                $apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
                $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
                $queryString = '?gcd_amt='.$price.'&gcd_type='.$type;

                $url = $url.'/index.php/api/validateGiftcardRange'.$queryString; // prepare url for the rest call
                $api_user = $apiInformation['api_user'];
                $api_key = $apiInformation['api_key'];

                $method ='GET';
                // headers and data (this is API dependent, some uses XML)
                $headers = array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'X-MBIZPOS-USERNAME: '.$api_user,
                    'X-MBIZPOS-PASSWORD: '.$api_key
                );

                $handle = curl_init();		//curl request to create the product
                curl_setopt($handle, CURLOPT_URL, $url);
                curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

                /*$data = array();
                $data['gcd_unique_num'] = $postData['gift_no'];
                $data = json_encode($data);*/

                switch ($method) {
                    case 'GET':
                        break;

                }

                $response = curl_exec($handle);	// send curl request to microbiz

                $response=json_decode($response,true);


                $code = curl_getinfo($handle);
                if($code['http_code']==200)
                {
                    if($response['gcd_unique_num']!='' && $response['status_msg']=='')
                    {
                        $product->load($productId);
                        $optionId = '';
                        $optionValId = '';
                        $status=0;

                        $cart = Mage::getSingleton('checkout/cart');
                        $productOptions = $product->getOptions();

                        if($type==1)
                        {
                            foreach($productOptions as $Options)
                            {
                                foreach($Options->getValues() as $OptionsVal)
                                {
                                    if($OptionsVal->getTitle()=='Fixed Amount '.$price)
                                    {
                                        $optionId = $Options->getId();
                                        $optionValId = $OptionsVal->getId();
                                        $status=1;
                                    }
                                }
                            }
                        }
                        else
                        {
                            foreach($productOptions as $Options)
                            {
                                foreach($Options->getValues() as $OptionsVal)
                                {
                                    if($OptionsVal->getTitle()=='Any Amount')
                                    {
                                        $optionId = $Options->getId();
                                        $optionValId = $OptionsVal->getId();
                                        $status=2;
                                    }
                                }
                            }
                        }


                        //if the gift card is not pre-defined.
                        if($status>0)
                        {
                            $giftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                            $giftBuyData = unserialize($giftBuyData);

                            if(!empty($giftBuyData))
                            {
                                $key = count($giftBuyData);
                                if($key>0)
                                {
                                    /*$isExists=0;
                                    foreach($giftBuyData as $k=>$data)
                                    {
                                        if($data['amount']==$price)
                                        {
                                            $giftBuyData[$k]['qty'] = $data['qty']+1;
                                            $isExists=1;
                                        }
                                    }
                                    if($isExists==0)
                                    {*/
                                    $giftBuyData[$key]['gcd_amt']=$price;
                                    $giftBuyData[$key]['gcd_type']=$status;
                                    $giftBuyData[$key]['gcd_unique_num']=$response['gcd_unique_num'];
                                    //}
                                }

                                Mage::getSingleton('checkout/session')->unsGiftBuyData();
                                Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($giftBuyData));
                            }
                            else
                            {
                                $newGiftBuyData = array();
                                $newGiftBuyData[0]['gcd_amt'] = $price;
                                $newGiftBuyData[0]['gcd_type']=$status;
                                $newGiftBuyData[0]['gcd_unique_num']=$response['gcd_unique_num'];
                                Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($newGiftBuyData));
                            }
                        }

                        $arrGiftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                        $arrGiftBuyData = unserialize($arrGiftBuyData);


                        $cart->init();
                        $params = array(
                            'product' => $productId,
                            'qty' => 1,
                            'options' => array(
                                $optionId => $optionValId
                            )
                        );

                        $result =array();
                        try {
                            $cart->addProduct($product,$params);
                            $cart->save();
                            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);


                            $quote = Mage::getSingleton('checkout/cart')->getQuote();
                            $items = $quote->getAllItems();
                            foreach($items as $item)
                            {
                                $customOptions = $item->getProduct()
                                    ->getTypeInstance(true)
                                    ->getOrderOptions($item->getProduct());
                                if($customOptions['options'][0]['value']=='Any Amount')
                                {

                                    $newAmount = 0;
                                    foreach($arrGiftBuyData as $data)
                                    {
                                        if($data['gcd_type']==2)
                                        {
                                            $newAmount = $newAmount + $data['gcd_amt'];
                                        }
                                    }
                                    $item->setCustomPrice($newAmount);
                                    $item->setOriginalCustomPrice($newAmount);
                                    $item->setQty(1);
                                    $item->getProduct()->setIsSuperMode(true);
                                    $item->save();
                                }
                            }

                            $quote->save();
                            $quote->setTotalsCollectedFlag(false)->collectTotals();
                            $result['status'] ='SUCCESS';
                        }
                        catch (Exception $ex) {

                            $result['status'] ='FAIL';
                            $result['status_msg'] = $ex->getMessage();
                        }

                        $this->getResponse()->setHeader('Content-type', 'application/json');
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                        return $this;
                    }
                    else{
                        $result['status'] = 'FAIL';
                        if($response['status_msg'])
                        {
                            $result['status_msg'] = $response['status_msg'];
                        }
                        else {
                            $result['status_msg'] = "No GiftCards available with this range.";
                        }
                        $this->getResponse()->setHeader('Content-type', 'application/json');
                        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                        return $this;
                    }
                }
                else{
                    $result['status'] = 'FAIL';
                    $result['status_msg'] = "Unable to add this gift card since there was occurred an HTTP Error".$code['http_code'];
                    $this->getResponse()->setHeader('Content-type', 'application/json');
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return $this;

                }

            }
            else {
                $result['status'] = 'FAIL';
                $result['status_msg'] = "Maximum qty allowed for purchase is one";
                $this->getResponse()->setHeader('Content-type', 'application/json');
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return $this;
            }


        }
    }

    /**
     * @author KT-174
     * @description - This method is used to check or Validate the Gift Card Range when a Gift Card is used to buy. Here
     * in this method we will send the ranges array to microbiz and check in the microbiz whether the ranges are active
     * or not and gift cards available or not in the response we will get the gift card numbers for each and every
     * range.
     */
    public function mbizValidateGiftCardRangeAction()
    {
        $postData = $this->getRequest()->getPost();
        $arrGiftCardRanges= $postData['gift_ranges'];
        Mage::log($arrGiftCardRanges,null,'customersync.log');
        $arrGiftCardRanges = json_encode($arrGiftCardRanges);

        $apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
        $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
        $url = $url . '/index.php/api/processGiftcardSale'; // prepare url for the rest call
        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];
        Mage::log($url,null,'customersync.log');
        $method ='POST';
        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: '.$api_user,
            'X-MBIZPOS-PASSWORD: '.$api_key
        );

        $handle = curl_init();		//curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        switch ($method) {
            case 'GET':
                break;

            case 'POST':
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $arrGiftCardRanges);
                break;

            case 'PUT':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');	// create product request.
                curl_setopt($handle, CURLOPT_POSTFIELDS, $arrGiftCardRanges);
                break;

            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($handle);	// send curl request to microbiz
        $response=json_decode($response,true);
        $code = curl_getinfo($handle);
        Mage::log($response,null,'customersync.log');
        $jsonResponse=array();
        if($code['http_code'] == 200 ) {
            if($response['status'] == 'SUCCESS') {
                unset($response['status']);
                $jsonResponse['status'] = 'SUCCESS';
                $buyGiftData = serialize($response);
                Mage::getSingleton('checkout/session')->unsGiftBuyData();
                Mage::getSingleton('checkout/session')->setGiftBuyData($buyGiftData);

            }
            else {
                unset($response['status']);
                $jsonResponse['status_msg']= '';
                $jsonTemparr = array();

                unset($response[0]['gcd_unique_no']);

                if(count($response)>0)
                {
                    foreach($response as $key=>$giftInfo)
                    {
                        if(array_key_exists('status_msg',$giftInfo))
                        {
                            if($jsonTemparr[$giftInfo['gcd_amt']])
                            {
                                $jsonTemparr[$giftInfo['gcd_amt']] = $jsonTemparr[$giftInfo['gcd_amt']]+1;
                            }
                            else {
                                $jsonTemparr[$giftInfo['gcd_amt']] = 1;
                            }
                            unset($response[$key]['status_msg']);
                        }

                    }
                    if(count($jsonTemparr)>0)
                    {
                        foreach($jsonTemparr as $key=>$count)
                            if($jsonResponse['status_msg']=='')
                            {
                                $jsonResponse['status_msg'] = 'Gift Cards Not Available with this Range '.$key.' of Qty '.$count;
                            }
                            else{
                                $jsonResponse['status_msg'] = $jsonResponse['status_msg'].','.$key.' of Qty '.$count;
                            }
                    }
                }

                $jsonResponse['status'] = 'ERROR';
                $buyGiftData = serialize($response);
                Mage::getSingleton('checkout/session')->unsGiftBuyData();
                Mage::getSingleton('checkout/session')->setGiftBuyData($buyGiftData);
            }

        }
        else
        {
            $jsonResponse['status'] = 'ERROR';
            $jsonResponse['status_msg'] = 'Http Code '.$code['http_code'].' From the Server';
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonResponse));
        return $this;
    }
    /**
     * @author KT-174
     * @description This method is used to add the updated amount to the Discount Object
     */

    public function mbizUpdateDiscountAmountAction()
    {
        $postData = $this->getRequest()->getPost();
        $creditNo = $postData['credit_no'];
        $creditType = $postData['credit_type'];
        $creditAmt = $postData['credit_amt'];
        $creditCurrentBal = $postData['current_bal'];
        if($creditType==2)
        {
            $creditPin = $postData['credit_pin'];
        }
        $isPayment = 0;
        $isPayment = $postData['is_payment'];
        $applyGiftPaymentResponse = array();
        if($creditAmt!=0)
        {
            $quote = Mage::getSingleton('checkout/cart')->getQuote();
            $quote->collectTotals()->save();
            $cartTotal = $quote->getGrandTotal();
            $creditAmt= number_format((float)$creditAmt,2,'.', '');
            $cartTotal=number_format((float)$cartTotal,2,'.', '');
            $creditCurrentBal=number_format((float)$creditCurrentBal,2,'.', '');
            //Mage::log("came to update discount amoutn");
            //Mage::log($creditAmt."++".$cartTotal."++".$creditCurrentBal);
            if($creditAmt<=$cartTotal && $creditAmt<=$creditCurrentBal)
            {
                $creditFinalData = Mage::getSingleton('checkout/session')->getCreditFinalData();
                $creditFinalData = unserialize($creditFinalData);

                if(!empty($creditFinalData))
                {
                    $key = count($creditFinalData);
                    $creditFinalData[$key]['credit_no'] = $creditNo;
                    $creditFinalData[$key]['credit_amt'] = $creditAmt;
                    $creditFinalData[$key]['credit_type'] = $creditType;
                    if($creditType==2)
                    {
                        $creditFinalData[$key]['credit_pin'] = $creditPin;
                    }
                    Mage::getSingleton('checkout/session')->setCreditFinalData(serialize($creditFinalData));
                    if($creditType==1)
                    {
                        $message = $this->__('Store Credit with no '.$creditNo.' is Applied Successfully');
                        $applyGiftPaymentResponse['status'] = 'SUCCESS';
                        $applyGiftPaymentResponse['message'] = 'Store Credit with no '.$creditNo.' is Applied Successfully';
                        $applyGiftPaymentResponse['credit_id'] = $creditNo;
                    }
                    else{
                        $message = $this->__('Gift Card with no '.$creditNo.' is Applied Successfully');
                        $applyGiftPaymentResponse['status'] = 'SUCCESS';
                        $applyGiftPaymentResponse['message'] = 'Gift Card with no '.$creditNo.' is Applied Successfully';
                        $applyGiftPaymentResponse['credit_id'] = $creditNo;
                    }
                    if($isPayment==0)
                    {
                        Mage::getSingleton('core/session')->addSuccess($message);
                    }

                }
                else
                {
                    $creditNewData = array();
                    $creditNewData[0]['credit_no'] = $creditNo;
                    $creditNewData[0]['credit_amt'] = $creditAmt;
                    $creditNewData[0]['credit_type'] = $creditType;
                    if($creditType==2)
                    {
                        $creditNewData[0]['credit_pin'] = $creditPin;
                    }
                    Mage::getSingleton('checkout/session')->setCreditFinalData(serialize($creditNewData));
                    if($creditType==1)
                    {
                        $message = $this->__('Store Credit with no '.$creditNo.' is Applied Successfully');
                        $applyGiftPaymentResponse['status'] = 'SUCCESS';
                        $applyGiftPaymentResponse['message'] = 'Store Credit with no '.$creditNo.' is Applied Successfully';
                        $applyGiftPaymentResponse['credit_id'] = $creditNo;
                    }
                    else{
                        $message = $this->__('Gift Card with no '.$creditNo.' is Applied Successfully');
                        $applyGiftPaymentResponse['status'] = 'SUCCESS';
                        $applyGiftPaymentResponse['message'] = 'Gift Card with no '.$creditNo.' is Applied Successfully';
                        $applyGiftPaymentResponse['credit_id'] = $creditNo;
                    }
                    if($isPayment==0)
                    {
                        Mage::getSingleton('core/session')->addSuccess($message);
                    }
                }

                $creditData = Mage::getSingleton('checkout/session')->getCreditData();
                $creditData = unserialize($creditData);
                Mage::log("from updategiftcardaction",null,'giftcards.log');
                Mage::log($creditData,null,'giftcards.log');
                $creditFidDataMage = Mage::getSingleton('checkout/session')->getCreditFinalData();
                Mage::log("from udpategiftcardaction",null,'giftcards.log');
                Mage::log(unserialize($creditFidDataMage),null,'giftcards.log');
                $arrNewCreditData = array();
                $i=0;
                foreach($creditData as $data)
                {
                    if($data['credit_no']!=$creditNo)
                    {
                        $arrNewCreditData[$i]['credit_no'] = $data['credit_no'];
                        $arrNewCreditData[$i]['credit_amt'] = $data['credit_amt'];
                        $arrNewCreditData[$i]['credit_type'] = $data['credit_type'];
                        if($data['credit_type']==2)
                        {
                            $arrNewCreditData[$i]['credit_pin'] = $creditPin;
                        }
                        $i++;
                    }
                }
                Mage::log("after update");
                Mage::log($arrNewCreditData);
                Mage::getSingleton('checkout/session')->setCreditData(serialize($arrNewCreditData));
            }
            else
            {
                $message = $this->__('The amount you entered is either greater than cart total or current bal.');
                $applyGiftPaymentResponse['status'] = 'FAIL';
                $applyGiftPaymentResponse['message'] = 'The amount you entered is either greater than cart total or current bal.';
                $applyGiftPaymentResponse['credit_id'] = $creditNo;
                if($isPayment==0)
                {
                    Mage::getSingleton('core/session')->addError($message);
                }

            }

        }
        else{
            $message = $this->__('Please enter amount to redeem');
            $applyGiftPaymentResponse['status'] = 'FAIL';
            $applyGiftPaymentResponse['message'] = 'Please enter amount to redeem';
            $applyGiftPaymentResponse['credit_id'] = $creditNo;
            if($isPayment==0)
            {
                Mage::getSingleton('core/session')->addError($message);
            }
        }
        if($isPayment==0)
        {
            $this->_redirect('checkout/cart');
        }
        else {
            $quote = Mage::getSingleton('checkout/cart')->getQuote();

            Mage::log("total recollected while applying",null,'payment.log');
            $quote->setTotalsCollectedFlag(false)->collectTotals();
            $quote->save();
            $quote = Mage::getModel('checkout/session')->getQuote();
            $Total = $quote->getGrandTotal();
            $applyGiftPaymentResponse['total'] = $Total;
            Mage::log("total recollected while applying resp",null,'payment.log');
            Mage::log($applyGiftPaymentResponse,null,'payment.log');
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($applyGiftPaymentResponse));
            return $this;
        }


    }

    /**
     * @author KT-174
     * @description function for removing the already applied store credit or gift card from the session variable
     */
    public function mbizRemoveCreditPostAction()
    {
        $creditRemoveId = $this->getRequest()->getParam('id');
        $isPayment =0;
        $isPayment = $this->getRequest()->getParam('is_payment');
        $arrCreditFinalData = Mage::getSingleton('checkout/session')->getCreditFinalData();
        $arrCreditFinalData = unserialize($arrCreditFinalData);
        if(count($arrCreditFinalData)>0)
        {
            foreach($arrCreditFinalData as $key=>$data)
            {
                if($creditRemoveId==$data['credit_no'])
                {
                    unset($arrCreditFinalData[$key]);
                    $credit_type = $data['credit_type'];
                }
            }
            $arrNewCreditData = array();
            $x=0;
            foreach($arrCreditFinalData as $data2)
            {
                $arrNewCreditData[$x]['credit_no'] = $data2['credit_no'];
                $arrNewCreditData[$x]['credit_amt'] = $data2['credit_amt'];
                $arrNewCreditData[$x]['credit_type'] = $data2['credit_type'];
                if($data2['credit_type']==2)
                {
                    $arrNewCreditData[$x]['credit_pin'] = $data2['credit_pin'];
                }
                $x++;
            }
            Mage::getSingleton('checkout/session')->unsCreditFinalData();
            Mage::getSingleton('checkout/session')->setCreditFinalData(serialize($arrNewCreditData));
            Mage::getModel('checkout/session')->unsCreditDiscountData();
            Mage::getModel('checkout/session')->setCreditDiscountData(serialize($arrNewCreditData));
            if($credit_type==1)
            {
                $message = $this->__('Store Credit with no '.$creditRemoveId.' is Removed Successfully');
                $removeGiftPaymentResponse['status'] = 'SUCCESS';
                $removeGiftPaymentResponse['message'] = 'Store Credit with no '.$creditRemoveId.' is Removed Successfully';
                $removeGiftPaymentResponse['credit_id'] = $creditRemoveId;

            }
            else{
                $message = $this->__('Gift Card with no '.$creditRemoveId.' is Removed Successfully');
                $removeGiftPaymentResponse['status'] = 'SUCCESS';
                $removeGiftPaymentResponse['message'] = 'Gift Card with no '.$creditRemoveId.' is Removed Successfully';
                $removeGiftPaymentResponse['credit_id'] = $creditRemoveId;
            }
            $quote = Mage::getSingleton('checkout/cart')->getQuote();

            Mage::log("total recollected",null,'payment.log');
            $quote->setTotalsCollectedFlag(false)->collectTotals();
            $quote->save();
            $quote = Mage::getModel('checkout/session')->getQuote();
            $Total = $quote->getGrandTotal();
            $removeGiftPaymentResponse['total'] = $Total;
            Mage::log($removeGiftPaymentResponse,null,'payment.log');



            if($isPayment==0)
            {
                Mage::getSingleton('core/session')->addSuccess($message);
            }
        }
        else
        {
            $message = $this->__('No Store Credit/GiftCards Exists');
            $removeGiftPaymentResponse['status'] = 'FAIL';
            $removeGiftPaymentResponse['message'] = 'No Store Credit/GiftCards Exists';
            if($isPayment==0)
            {
                Mage::getSingleton('core/session')->addError($message);
            }


        }
        $creditData = Mage::getSingleton('checkout/session')->getCreditData();
        $creditData = unserialize($creditData);
        Mage::log("from removegiftcardaction",null,'giftcards.log');
        Mage::log($creditData,null,'giftcards.log');
        $creditFidDataMage = Mage::getSingleton('checkout/session')->getCreditFinalData();
        Mage::log("from udpategiftcardaction",null,'giftcards.log');
        Mage::log(unserialize($creditFidDataMage),null,'giftcards.log');
        if($isPayment==0)
        {
            $this->_redirect('checkout/cart');
        }
        else {
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($removeGiftPaymentResponse));
            return $this;
        }

    }
    /**
     * @author KT-174
     * @description - This method is used to Cancel the Validated Store Credit or Gift Card in Cart and Payment Methods.
     */
    public function mbizCancelCreditPostAction()
    {
        $creditCancelId = $this->getRequest()->getParam('id');
        $isPayment =0;
        $isPayment=$this->getRequest()->getParam('is_payment');

        $creditData = Mage::getSingleton('checkout/session')->getCreditData();
        $arrCreditData = unserialize($creditData);
        Mage::log("from cancelgiftcardaction",null,'giftcards.log');
        Mage::log($creditCancelId,null,'giftcards.log');
        Mage::log($isPayment,null,'giftcards.log');


        if($creditCancelId)
        {
            if(count($arrCreditData)>0)
            {
                $cancelGiftPaymentResponse = array();
                foreach($arrCreditData as $key=>$data)
                {
                    if($creditCancelId==$data['credit_no'])
                    {
                        unset($arrCreditData[$key]);
                        $credit_type = $data['credit_type'];
                    }
                }
                $arrNewCreditData = array();
                $x=0;
                foreach($arrCreditData as $data2)
                {
                    $arrNewCreditData[$x]['credit_no'] = $data2['credit_no'];
                    $arrNewCreditData[$x]['credit_amt'] = $data2['credit_amt'];
                    $arrNewCreditData[$x]['credit_type'] = $data2['credit_type'];
                    if($data2['credit_type']==2)
                    {
                        $arrNewCreditData[$x]['credit_pin'] = $data2['credit_pin'];
                    }
                    $x++;
                }

                Mage::getSingleton('checkout/session')->unsCreditData();
                Mage::getSingleton('checkout/session')->setCreditData(serialize($arrNewCreditData));
                if($credit_type==1)
                {
                    $message = $this->__('Store Credit with no '.$creditCancelId.' is Cancelled Successfully');
                    $cancelGiftPaymentResponse['status'] = 'SUCCESS';
                    $cancelGiftPaymentResponse['message'] = 'Store Credit with no '.$creditCancelId.' is Cancelled Successfully';
                    $cancelGiftPaymentResponse['credit_id'] = $creditCancelId;
                }
                else{
                    $message = $this->__('Gift Card with no '.$creditCancelId.' is Cancelled Successfully');
                    $cancelGiftPaymentResponse['status'] = 'SUCCESS';
                    $cancelGiftPaymentResponse['message'] = 'Gift Card with no '.$creditCancelId.' is Cancelled Successfully';
                    $cancelGiftPaymentResponse['credit_id'] = $creditCancelId;
                }
                if($isPayment==0)
                {
                    Mage::getSingleton('core/session')->addSuccess($message);
                }

            }
            else{
                $message = $this->__('No Store Credit/GiftCards Exists');
                $cancelGiftPaymentResponse['status'] = 'FAIL';
                $cancelGiftPaymentResponse['message'] = 'No Store Credit/GiftCards Exists';
                if($isPayment==0)
                {
                    Mage::getSingleton('core/session')->addError($message);
                }

            }
        }
        if($isPayment==0)
        {
            $this->_redirect('checkout/cart');
        }
        else{
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($cancelGiftPaymentResponse));
            return $this;
        }

    }
    /**
     * @author KT-174
     * @description - This method is used to get the payment process data and send it to microbiz and get the status
     * messages and send the response the the requested ajax call. this method is called at the time of order placing.
     */
    public function mbizProcessPaymentAction()
    {
        $postData = $this->getRequest()->getPost();
        $paymentData = $postData['payment_data'];

        $paymentData = json_encode($paymentData);

        $apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
        $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
        $url = $url . '/index.php/api/processPayments'; // prepare url for the rest call
        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];

        $method ='POST';
        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: '.$api_user,
            'X-MBIZPOS-PASSWORD: '.$api_key
        );

        $handle = curl_init();		//curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        switch ($method) {
            case 'GET':
                break;

            case 'POST':
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $paymentData);
                break;

            case 'PUT':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');	// create product request.
                curl_setopt($handle, CURLOPT_POSTFIELDS, $paymentData);
                break;

            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($handle);	// send curl request to microbiz

        $response=json_decode($response,true);

        $code = curl_getinfo($handle);


        $jsonresponse=array();
        if($code['http_code'] == 200 ) {
            if($response['status'] == 'SUCCESS') {
                $jsonresponse['status'] = 'SUCCESS';

            }
            else {
                $jsonresponse['status_msg'] = $response['status_msg'];
                $jsonresponse['status'] = 'ERROR';
            }

        }
        else
        {
            $jsonresponse['status'] = 'ERROttR';
            $jsonresponse['status_msg'] = 'Http Code '.$code['http_code'].' From the Server';
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonresponse));
        return $this;


    }

    /**
     * @return $this
     * @author KT174
     * @description This method is used to check the Test Connection with the given details in Installation Wizard.
     */
    public function mbizInstanceTestConnectionAction(){
        $postdata=$this->getRequest()->getPost();
        $jsonresponse =  Mage::helper('microbiz_connector')->testConnection($postdata);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonresponse));
        return $this;

    }

    /*
        * function for Testing MBiz APi Connection
        * return success if connected in json format
    */
    public function checkAppConnectionAction()
    {
        $postdata=$this->getRequest()->getPost();
        $url=$postdata['apiserver'];
        $api_user=$postdata['apipath'];
        $api_key=$postdata['apipassword'];
        $mage_url=$postdata['magentourl'];

        $url    = $url.'/index.php/api/test';			// prepare url for the rest call
        $method = 'POST';

        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: '.$api_user,
            'X-MBIZPOS-PASSWORD: '.$api_key
        );

        $data=array('mage_url'=>$mage_url);
        $data    = json_encode($data);


        $handle = curl_init();		//curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        switch ($method) {
            case 'GET':
                break;

            case 'POST':
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'PUT':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');	// create product request.
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($handle);	// send curl request to microbiz
        $response=json_decode($response,true);




        $code = curl_getinfo($handle);


        $jsonresponse=array();
        if($code['http_code'] == 200 ) {
            $jsonresponse['status'] = 'SUCCESS';
            $jsonresponse['instance_id'] = $response['instance_id'];
            $jsonresponse['syncstatus'] = $response['syncstatus'];
            $jsonresponse['message'] = $this->__('Connected to API Server');
        }
        else if($code['http_code'] == 500) {
            $jsonresponse['status'] = 'ERROR';
            $jsonresponse['message'] = $code['http_code'].' - Internal Server Error'.$response['message'];
        }
        else if($code['http_code'] == 0) {
            $jsonresponse['status'] = 'ERROR';
            $jsonresponse['message'] = $code['http_code'].' - Please Check the API Server URL'.$response['message'];
        }
        else
        {
            $jsonresponse['status'] = 'ERROR';
            $jsonresponse['message'] = $code['http_code'].' - '.$response['message'];
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonresponse));
        return $this;

    }

    /**
     * @author: KT-174
     * @description: This method is used to add the gift card details with amount to the already existing store credits or
     * Gift cards session data.
     */
    public function mbizValidateGiftCardAction()
    {
        $postData=$this->getRequest()->getPost();
        Mage::log($postData,null,'giftcards.log');
        $isPayment=0;
        $apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
        $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
        Mage::log($url,null,'giftcards.log');
        $queryString = '?gcd_unique_num='.$postData['gift_no'].'&gcd_pin='.$postData['gift_pin'];

        $url = $url.'/index.php/api/validateGiftcard'.$queryString; // prepare url for the rest call
        Mage::log($url,null,'giftcards.log');
        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];

        $method ='GET';
        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: '.$api_user,
            'X-MBIZPOS-PASSWORD: '.$api_key
        );




        $handle = curl_init();		//curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $data = array();
        $data['gcd_unique_num'] = $postData['gift_no'];
        $isPayment = $postData['is_payment'];
        $data = json_encode($data);

        switch ($method) {
            case 'GET':
                break;

            case 'POST':
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'PUT':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');	// create product request.
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($handle);	// send curl request to microbiz

        $response=json_decode($response,true);


        $code = curl_getinfo($handle);


        $arrPostData = $this->getRequest()->getPost();
        $creditId = $arrPostData['gift_no'];
        $validateGiftPaymentResponse = array();
        if($code['http_code'] == 200 ) {

            /*This code is added by KT-174 for Gift Card Redemption starts here.*/
            if($response['status'] == 1)
            {

                $creditAmt = $response['present_card_value'];
                $giftCardPin = $arrPostData['gift_pin'];
                $quoteModel = Mage::getModel('checkout/cart')->getQuote();
                $cartTotal = $quoteModel->getGrandTotal();

                if($creditAmt>0)
                {
                    if($cartTotal>0)
                    {
                        $creditFinalData = Mage::getSingleton('checkout/session')->getCreditFinalData();
                        $creditFinalData = unserialize($creditFinalData);

                        if(!empty($creditFinalData))
                        {
                            $isExists=0;
                            foreach($creditFinalData as $data)
                            {
                                if($creditId==$data['credit_no']) {
                                    $isExists=1;
                                }
                            }
                            if($isExists==0)
                            {
                                $prevCreditData = Mage::getSingleton('checkout/session')->getCreditData();
                                $prevCreditData = unserialize($prevCreditData);
                                if(!empty($prevCreditData))
                                {
                                    $key = count($prevCreditData);
                                    $prevCreditData[$key]['credit_no'] = $creditId;
                                    $prevCreditData[$key]['credit_amt'] = $creditAmt;
                                    $prevCreditData[$key]['credit_type'] = 2;
                                    $prevCreditData[$key]['credit_pin'] = $giftCardPin;
                                    Mage::getSingleton('checkout/session')->setCreditData(serialize($prevCreditData));

                                }
                                else
                                {
                                    $creditData = array();
                                    $creditData[0]['credit_no'] = $creditId;
                                    $creditData[0]['credit_amt'] = $creditAmt;
                                    $creditData[0]['credit_type'] = 2;
                                    $creditData[0]['credit_pin'] = $giftCardPin;
                                    Mage::getSingleton('checkout/session')->setCreditData(serialize($creditData));
                                }

                                $message = $this->__('Gift Card with no '.$creditId.' is Added enter amount');
                                $validateGiftPaymentResponse['status']='SUCCESS';
                                $validateGiftPaymentResponse['message']='Gift Card with no '.$creditId.' is Added enter amount';
                                $validateGiftPaymentResponse['available_amt'] = $creditAmt;
                                if($isPayment==0)
                                {
                                    Mage::getSingleton('core/session')->addSuccess($message);
                                }

                            }
                            else
                            {
                                $message = $this->__('Gift Card with no '.$creditId.' is already applied.');
                                $validateGiftPaymentResponse['status']='FAIL';
                                $validateGiftPaymentResponse['message']='Gift Card with no '.$creditId.' is already applied.';
                                if($isPayment==0)
                                {
                                    Mage::getSingleton('core/session')->addError($message);
                                }

                            }

                        }
                        else
                        {
                            $prevCreditData = Mage::getSingleton('checkout/session')->getCreditData();
                            $prevCreditData = unserialize($prevCreditData);
                            if(!empty($prevCreditData))
                            {
                                $key = count($prevCreditData);
                                $prevCreditData[$key]['credit_no'] = $creditId;
                                $prevCreditData[$key]['credit_amt'] = $creditAmt;
                                $prevCreditData[$key]['credit_type'] = 2;
                                $prevCreditData[$key]['credit_pin'] = $giftCardPin;
                                Mage::getSingleton('checkout/session')->setCreditData(serialize($prevCreditData));

                            }
                            else
                            {
                                $creditData = array();
                                $creditData[0]['credit_no'] = $creditId;
                                $creditData[0]['credit_amt'] = $creditAmt;
                                $creditData[0]['credit_type'] = 2;
                                $creditData[0]['credit_pin'] = $giftCardPin;
                                Mage::getSingleton('checkout/session')->setCreditData(serialize($creditData));
                            }

                            $message = $this->__('Gift Card with no '.$creditId.' is Added enter amount');
                            $validateGiftPaymentResponse['status']='SUCCESS';
                            $validateGiftPaymentResponse['message']='Gift Card with no '.$creditId.' is Added enter amount';
                            $validateGiftPaymentResponse['available_amt'] = $creditAmt;
                            if($isPayment==0)
                            {
                                Mage::getSingleton('core/session')->addSuccess($message);
                            }

                        }


                    }
                    else{
                        $message = $this->__('Unable to apply gift card since the cart total is already zero.');
                        $validateGiftPaymentResponse['status']='FAIL';
                        $validateGiftPaymentResponse['message']='Unable to apply gift card since the cart total is already zero.';
                        if($isPayment==0)
                        {
                            Mage::getSingleton('core/session')->addError($message);
                        }

                    }
                }
                else{
                    $message = $this->__('Unable to apply gift card since the card contains zero balance.');
                    $validateGiftPaymentResponse['status']='FAIL';
                    $validateGiftPaymentResponse['message']='Unable to apply gift card since the card contains zero balance.';
                    if($isPayment==0)
                    {
                        Mage::getSingleton('core/session')->addError($message);
                    }
                }
            }
            else{
                $message = $this->__($response['status_msg']);
                $validateGiftPaymentResponse['status']='FAIL';
                $validateGiftPaymentResponse['message']=$response['status_msg'];
                if($isPayment==0)
                {
                    Mage::getSingleton('core/session')->addError($message);
                }
            }

        }
        else
        {
            $message = $this->__('Gift Card with no '.$creditId.' is unable to apply due to Http Error '.
                $code['http_code'].' From the Server');
            $validateGiftPaymentResponse['status']='FAIL';
            $validateGiftPaymentResponse['message']='Gift Card with no '.$creditId.' is unable to apply due to Http Error '.
                $code['http_code'].' From the Server';
            if($isPayment==0)
            {
                Mage::getSingleton('core/session')->addError($message);
            }
        }
        $creditData = Mage::getSingleton('checkout/session')->getCreditData();
        $creditData = unserialize($creditData);
        Mage::log("from validategiftcardaction",null,'giftcards.log');
        Mage::log($validateGiftPaymentResponse,null,'giftcards.log');
        Mage::log($isPayment,null,'giftcards.log');

        if($isPayment==0)
        {
            $this->_redirect('checkout/cart');
        }
        else{
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($validateGiftPaymentResponse));
            return $this;
        }


    }

    /**
     * @author KT-174
     * @description This method will be called when any store credit no entered and used to redeem from front-end. In
     * this method we are going to get the credit amount based the posted credit id by sending curl request to mbiz
     * and if the credit amount exists and the current cart total is greater than zero then we are going to form one
     * array with the credit no , credit amount, credit type and then we are going to set the array to session as a
     * variable by serializing it .
     */
    public function mbizValidateStoreCreditAction()
    {
        $postData=$this->getRequest()->getPost();
        $isPayment=0;
        $isPayment = $postData['is_payment'];
        $checkout = Mage::getSingleton('checkout/session')->getQuote()->getData();

        $apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
        $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.

        $relationData = Mage::getModel('mbizcustomer/mbizcustomer')
            ->getCollection()
            ->addFieldToFilter('magento_id', $checkout['customer_id'])
            ->setOrder('id', 'asc')
            ->getFirstItem();
        $queryString = '?scr_unique_num='.$postData['credit_no'].'&customer_id='.$relationData['mbiz_id'];

        $url = $url.'/index.php/api/validateStoreCredit'.$queryString; // prepare url for the rest call
        $api_user = $apiInformation['api_user'];
        $api_key = $apiInformation['api_key'];

        $method ='GET';
        // headers and data (this is API dependent, some uses XML)
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: '.$api_user,
            'X-MBIZPOS-PASSWORD: '.$api_key
        );



        $data = array();
        $data['scr_unique_num']=$postData['credit_no'];
        $data['customer_id']=$relationData['mbiz_id'];
        $data = json_encode($data);

        $handle = curl_init();		//curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        switch ($method) {
            case 'GET':
                break;

            case 'POST':
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'PUT':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');	// create product request.
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                break;

            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($handle);	// send curl request to microbiz

        $response=json_decode($response,true);

        $code = curl_getinfo($handle);


        $arrPostData = $postData;
        $creditId = $arrPostData['credit_no'];
        $validateStoreCreditPaymentResponse = array();
        if($code['http_code'] == 200 ) {

            /*code added by KT-174 for store credit redemption start here*/
            if($response['status'] == 1)
            {
                $quote = Mage::getModel('checkout/cart')->getQuote();
                $cartTotal = $quote->getGrandTotal();
                $creditAmt = $response['available_balance'];
                if($creditAmt>0)
                {
                    if($cartTotal>0)
                    {
                        $creditFinalData = Mage::getSingleton('checkout/session')->getCreditFinalData();
                        $creditFinalData = unserialize($creditFinalData);

                        if(!empty($creditFinalData))
                        {
                            $isExists=0;
                            foreach($creditFinalData as $data)
                            {
                                if($creditId==$data['credit_no']) {
                                    $isExists=1;
                                }
                            }
                            if($isExists==0)
                            {
                                $prevCreditData = Mage::getSingleton('checkout/session')->getCreditData();
                                $prevCreditData = unserialize($prevCreditData);
                                if(!empty($prevCreditData))
                                {
                                    $key = count($prevCreditData);
                                    $prevCreditData[$key]['credit_no'] = $creditId;
                                    $prevCreditData[$key]['credit_amt'] = $creditAmt;
                                    $prevCreditData[$key]['credit_type'] = 1;
                                    Mage::getSingleton('checkout/session')->setCreditData(serialize($prevCreditData));

                                }
                                else
                                {
                                    $creditData = array();
                                    $creditData[0]['credit_no'] = $creditId;
                                    $creditData[0]['credit_amt'] = $creditAmt;
                                    $creditData[0]['credit_type'] = 1;
                                    Mage::getSingleton('checkout/session')->setCreditData(serialize($creditData));
                                }

                                $message = $this->__('Store Credit with no '.$creditId.' is Added enter amount');
                                $validateStoreCreditPaymentResponse['status']='SUCCESS';
                                $validateStoreCreditPaymentResponse['message']='Store Credit with no '.$creditId.' is Added enter amount';
                                $validateStoreCreditPaymentResponse['available_amt'] = $creditAmt;
                                if($isPayment==0)
                                {
                                    Mage::getSingleton('core/session')->addSuccess($message);
                                }
                            }
                            else
                            {
                                $message = $this->__('Store Credit with no '.$creditId.' is already applied.');
                                $validateStoreCreditPaymentResponse['status']='FAIL';
                                $validateStoreCreditPaymentResponse['message']='Store Credit with no '.$creditId.' is already applied.';
                                if($isPayment==0)
                                {
                                    Mage::getSingleton('core/session')->addError($message);
                                }
                            }

                        }
                        else
                        {
                            $prevCreditData = Mage::getSingleton('checkout/session')->getCreditData();
                            $prevCreditData = unserialize($prevCreditData);
                            if(!empty($prevCreditData))
                            {
                                $key = count($prevCreditData);
                                $prevCreditData[$key]['credit_no'] = $creditId;
                                $prevCreditData[$key]['credit_amt'] = $creditAmt;
                                $prevCreditData[$key]['credit_type'] = 1;
                                Mage::getSingleton('checkout/session')->setCreditData(serialize($prevCreditData));

                            }
                            else
                            {
                                $creditData = array();
                                $creditData[0]['credit_no'] = $creditId;
                                $creditData[0]['credit_amt'] = $creditAmt;
                                $creditData[0]['credit_type'] = 1;
                                Mage::getSingleton('checkout/session')->setCreditData(serialize($creditData));
                            }

                            $message = $this->__('Store Credit with no '.$creditId.' is Added enter amount');
                            $validateStoreCreditPaymentResponse['status']='SUCCESS';
                            $validateStoreCreditPaymentResponse['message']='Store Credit with no '.$creditId.' is Added enter amount';
                            $validateStoreCreditPaymentResponse['available_amt'] = $creditAmt;
                            if($isPayment==0)
                            {
                                Mage::getSingleton('core/session')->addSuccess($message);
                            }
                        }




                    }
                    else{
                        $message = $this->__('Unable to apply store credit since the cart total is already zero.');
                        $validateStoreCreditPaymentResponse['status']='FAIL';
                        $validateStoreCreditPaymentResponse['message']='Unable to apply store credit since the cart total is already zero.';
                        if($isPayment==0)
                        {
                            Mage::getSingleton('core/session')->addError($message);
                        }
                    }
                }
                else{
                    $message = $this->__('Unable to apply store credit since the card contains zero balance.');
                    $validateStoreCreditPaymentResponse['status']='FAIL';
                    $validateStoreCreditPaymentResponse['message']='Unable to apply store credit since the card contains zero balance.';
                    if($isPayment==0)
                    {
                        Mage::getSingleton('core/session')->addError($message);
                    }
                }

            }
            else
            {
                $message = $this->__($response['status_msg']);
                $validateStoreCreditPaymentResponse['status']='FAIL';
                $validateStoreCreditPaymentResponse['message']=$response['status_msg'];
                if($isPayment==0)
                {
                    Mage::getSingleton('core/session')->addError($message);
                }
            }



        }
        else
        {

            $message = $this->__('Store Credit with no '.$creditId.' is unable to apply due to Http Error '.
                $code['http_code'].' From the Server');
            $validateStoreCreditPaymentResponse['status']='FAIL';
            $validateStoreCreditPaymentResponse['message']='Store Credit with no '.$creditId.' is unable to apply due to Http Error '.
                $code['http_code'].' From the Server';
            if($isPayment==0)
            {
                Mage::getSingleton('core/session')->addError($message);
            }
        }

        if($isPayment==0)
        {
            $this->_redirect('checkout/cart');
        }
        else{
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($validateStoreCreditPaymentResponse));
            return $this;
        }
        /*code added by KT-174 for store credit redemption end here*/
    }

    /**
     *
     */
    public function getPaymentMethodsListAction()
    {
        $response = array();
        Mage::log("came to get paymetnnddd",null,'payment.log');

        $block = Mage::app()->getLayout()->createBlock('checkout/onepage_payment_methods');
        $_methods = $block->getMethods();
        $_methodsCount  = count($_methods);
        Mage::log($_methodsCount,null,'payment.log');
        $methodsHtml = '';
        foreach ($_methods as $_method):
            $_code = $_method->getCode();
            $methodsHtml.='<dt>';
            if ($_methodsCount > 1):
                $methodsHtml.='<input type="radio" id="p_method_'.$_code.'" value="'.$_code.'" name="payment[method]" title="'.$block->escapeHtml($_method->getTitle()) .'" onclick="payment.switchMethod('.$_code.')"';
                if($block->getSelectedMethodCode()==$_code):
                    $methodsHtml.='checked="checked"';
                endif;
                $methodsHtml.='class="radio" />';
            else:
                $methodsHtml.='<span class="no-display"><input type="radio" id="p_method_'.$_code.'" value="'.$_code.'" name="payment[method]" checked="checked" class="radio" /></span>';
            endif;
            $methodsHtml.='<label for="p_method_'.$_code.'">'.$block->escapeHtml($_method->getTitle()).'</label>';
            $methodsHtml.='</dt>';
            if($html = $block->getChildHtml('payment.method.'.$_code)) :
                $methodsHtml.='<dd>'.$html.'</dd>';
            endif;
        endforeach;
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $quote->collectTotals();
        $cartTotal = $quote->getGrandTotal();
        Mage::log("car total",null,'payment.log');
        Mage::log($cartTotal,null,'payment.log');
        if($cartTotal==0)
        {
            Mage::log($cartTotal."is ",null,'payment.log');
            $methodsHtml.='<dt><input type="radio" class="radio" onclick="payment.switchMethod(free)" title="No Payment Information Required" name="payment[method]" value="free" id="p_method_free"><label for="p_method_free">No Payment Information Required</label></dt>';
        }
        Mage::log($methodsHtml);

        /*This Code is to check the quote total and microbiz store credits and gift cards are applied or not.*/
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $quote->collectTotals();
        $quoteTotal = $quote->getGrandTotal();
        $arrQuoteCreditDiscountData = Mage::getSingleton('checkout/session')->getCreditDiscountData();
        $arrQuoteCreditDiscountData = unserialize($arrQuoteCreditDiscountData);
        $isDiscountApplied=0;
        if(count($arrQuoteCreditDiscountData)>0)
        {
            foreach($arrQuoteCreditDiscountData as $data)
            {
                if(array_key_exists('credit_no',$data))
                {
                    $isDiscountApplied=1;
                }
            }
        }


        $response['status'] = 'SUCCESS';
        $response['methods'] = $methodsHtml;
        $response['quote_total'] = $quoteTotal;
        $response['is_discount_applied'] = $isDiscountApplied;
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        return $this;

    }

    public function getVersionNumbersAction()
    {
        $postData = $this->getRequest()->getPost();

        Mage::log($postData,null,'rel.log');
        $mbizProductId = $postData['mbiz_product_id'];
        $magProductId = $postData['mag_product_id'];

        $mbizVersionData = Mage::helper('microbiz_connector')->getMbizVersionNumbers($magProductId,'Product');

        Mage::log($mbizVersionData,null,'version.log');
        $relationdata = Mage::getModel('mbizproduct/mbizproduct')->getCollection()
            ->addFieldToFilter('mbiz_id', $mbizProductId)->setOrder('id', 'asc')
            ->getFirstItem()->getData();

        $productVerMisMatch =0;
        $html='';
        $html.='<table border="2px solid" cellspacing="3" cellpadding="5"><thead>
                        <tr>
                        <th>MicroBiz</th>
                        <th>Magento</th>
                        </tr></thead><tbody>';

        if($mbizVersionData['status']=='SUCCESS') {

            $mbizresponse['status'] = 'SUCCESS';
            $mbizresponse['response_data']['mbiz_versions'] =$mbizVersionData;
            $mbizresponse['response_data']['mag_versions'] =$relationdata;


            $html.='<tr><td>'.$mbizVersionData['mbiz_version_number'].$mbizVersionData['mage_version_number'].'</td>
                            <td>'.$relationdata['mbiz_version_number'].$relationdata['mage_version_number'].'</td></tr>';


            $html.='</tbody></table>';


            //Version Comparing Code Starts Here.

            if(!empty($mbizVersionData) && !empty($relationdata)) {
                $magMagVerNo = $relationdata['mage_version_number'];
                $mbizMagVerNo = $mbizVersionData['mage_version_number'];

                $magMbizVerNo = $relationdata['mbiz_version_number'];
                $mbizMbizVerNo = $mbizVersionData['mbiz_version_number'];

                $isSecure = Mage::app()->getStore()->isCurrentlySecure();
                $magBaseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, $isSecure);
                $newhtml = '';

                //checking mage versions in both magento and microbiz.
                if($mbizMagVerNo<$magMagVerNo) {

                    $mbizresponse['response_data']['sync_direction'] = 1;
                    $syncUrl = $magBaseUrl."connector/index/resyncproduct?sync_direc=1&mag_prod_id=".$magProductId;
                    //$newhtml = '<p>Product Version Missmatch Found in MicroBiz please <a href="'.$syncUrl.'">click here </a> to Sync Product to MicroBiz. </p>';
                    $newhtml = '<p>Product Version Missmatch Found in MicroBiz please <a href="javascript:void(1)" onclick="resyncProduct('.$magProductId.',1)">click here </a> to Sync Product to MicroBiz. </p>';
                }
                elseif($magMbizVerNo<$mbizMbizVerNo) {

                    $mbizresponse['response_data']['sync_direction'] = 2;
                    $syncUrl = $magBaseUrl."connector/index/resyncproduct?sync_direc=2&mag_prod_id=".$magProductId;
                    $newhtml = '<p>Product Version Miss-match Found in Magento please <a href="javascript:void(1)" onclick="resyncProduct('.$magProductId.',2)">click here </a> to Sync Product From MicroBiz. </p>';
                }
                else {
                    $mbizresponse['response_data']['sync_direction'] = 0;
                }

            }
            else {
                $mbizresponse['response_data']['sync_direction'] = 1;
                $newhtml = '<p>Product Relation is not Exists in MicroBiz please <a href="javascript:void(1)" onclick="resyncProduct('.$magProductId.',1)">click here </a> to Sync Product to MicroBiz. </p>';
            }


        }
        else {
            $mbizresponse['status'] = 'FAIL';
            $html.='<tr><td> '.$mbizVersionData['status_msg'].'</td>
            <td>'.$relationdata['mbiz_version_number'].$relationdata['mage_version_number'].'</td></tr>';

            $html.='</tbody></table>';

        }



        $mbizAttrSetResp = $mbizVersionData['attribute_set_rel'];
        $attributeSetResponse = $this->getAttrSetVersions($magProductId,$mbizAttrSetResp);

        Mage::log("came to attrisetresp",null,'version.log');
        Mage::log($attributeSetResponse,null,'version.log');

        if($attributeSetResponse['missmatch']==1) {
            $mbizresponse['response_data']['popup_content'] = '<p><h5>Product Version Details</h5></p><br/>'.$html.
                '<br/><br/><p><h5>AttributeSets Version Details </h5></p>'.$attributeSetResponse['html_content'];
        }
        else {
            $newhtml='<p><h5>Product Version Details</h5></p><br/>'.$newhtml.$html;
            $mbizresponse['response_data']['popup_content'] = $newhtml.
                '<br/><br/><p><h5>AttributeSets Version Details </h5></p>'.$attributeSetResponse['html_content'];
        }





        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($mbizresponse));
        return $this;
    }

    public function getAttrSetVersions($magProductId,$mbizAttrSetResp=array())
    {
        Mage::log("came to attrisetresp",null,'version.log');
        Mage::log($magProductId,null,'version.log');
        //getting AttributeSet Id from Magento Product.
        if($magProductId) {
            $product = Mage::getModel('catalog/product')->load($magProductId);

            $attrSetId = $product->getAttributeSetId();
            Mage::log($attrSetId,null,'version.log');

            $mbizVersionData = $mbizAttrSetResp;


            $relationdata = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()
                ->addFieldToFilter('magento_id', $attrSetId)->setOrder('id', 'asc')
                ->getFirstItem()->getData();
            Mage::log("magento ver det",null,'version.log');
            Mage::log($relationdata,null,'version.log');
            Mage::log($mbizVersionData,null,'version.log');
            $html='';
            $html='<table border="2px solid" cellspacing="3" cellpadding="5"><thead>
                        <tr>
                        <th>MicroBiz</th>
                        <th>Magento</th>
                        </tr></thead><tbody>';




            $html.='<tr><td>'.$mbizVersionData['mbiz_version_number'].$mbizVersionData['mage_version_number'].'</td>
            <td>'.$relationdata['mbiz_version_number'].$relationdata['mage_version_number'].'</td></tr>';

            $html.='</tbody></table>';

            //Version Comparing Code Starts Here.

            if(!empty($mbizVersionData) && !empty($relationdata)) {
                $magMagVerNo = $relationdata['mage_version_number'];
                $mbizMagVerNo = $mbizVersionData['mage_version_number'];

                $magMbizVerNo = $relationdata['mbiz_version_number'];
                $mbizMbizVerNo = $mbizVersionData['mbiz_version_number'];

                $isSecure = Mage::app()->getStore()->isCurrentlySecure();
                $magBaseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, $isSecure);
                $newhtml = '';

                //checking mage versions in both magento and microbiz.
                if($mbizMagVerNo<$magMagVerNo) {
                    $attrSetResponse['missmatch'] = 1;
                    $mbizresponse['response_data']['sync_direction'] = 1;
                    $syncUrl = $magBaseUrl."connector/index/resyncattribueset?sync_direc=1&mag_attrset_id=".$attrSetId;
                    //$newhtml.= '<p>AttributeSet Version Missmatch Found in MicroBiz please <a href="'.$syncUrl.'">click here </a> to Sync AttributeSet to MicroBiz. </p>';
                    $newhtml.= '<p>AttributeSet Version Missmatch Found in MicroBiz please <a href="javascript:void(1)" onclick="resyncAttributeset('.$attrSetId.',1)">click here </a> to Sync AttributeSet to MicroBiz. </p>';
                }
                elseif($magMbizVerNo<$mbizMbizVerNo) {
                    $attrSetResponse['missmatch'] = 1;
                    $mbizresponse['response_data']['sync_direction'] = 2;
                    $syncUrl = $magBaseUrl."connector/index/resyncattribueset?sync_direc=2&mag_attrset_id=".$attrSetId;
                    $newhtml.= '<p>AttributeSet Version Miss-match Found in Magento please <a href="javascript:void(1)" onclick="resyncAttributeset('.$attrSetId.',2)" >click here </a> to Sync AttributeSet From MicroBiz. </p>';
                }
                else {
                    $attrSetResponse['missmatch'] = 0;
                    $mbizresponse['response_data']['sync_direction'] = 0;
                }

            }
            else {
                $attrSetResponse['missmatch'] = 1;
                $newhtml= '<p>AttributeSet Relation Not Exists in Magento or MicroBiz please <a href="javascript:void(1)" onclick="resyncAttributeset('.$attrSetId.',1)">click here </a> to Sync AttributeSet. </p>';
                $mbizresponse['response_data']['sync_direction'] = 1;
            }


            Mage::log($html,null,'version.log');
            Mage::log($newhtml,null,'version.log');
            $newhtml.=$html;
            $attrSetResponse['html_content'] = $newhtml;

            return $attrSetResponse;
        }


    }


    public function resyncproductAction()
    {
        $params = $this->getRequest()->getParams();

        $magProductId = $params['mag_product_id'];
        $syncDirection = $params['sync_direction'];


        if($syncDirection==1){ //sync product from magento to microbiz.
            $reSyncResponse = Mage::helper('microbiz_connector')->mbizStartReSync($magProductId,'mage','Product');
            if($reSyncResponse['status'] == 'SUCCESS') {
                $response = $reSyncResponse;
            }
            else {
                $response['status'] = 'FAIL';
                $response['status_msg'] = $reSyncResponse['status_msg'];
            }
        }
        else if($syncDirection==2){   //sync product from microbiz to magento
            $reSyncResponse = Mage::helper('microbiz_connector')->mbizStartReSync($magProductId,'mbiz','Product');
            if($reSyncResponse['status'] == 'SUCCESS') {
                $response = $reSyncResponse;
            }
            else {
                $response['status'] = 'FAIL';
                $response['status_msg'] = $reSyncResponse['status_msg'];
            }
        }
        else {
            $response['status'] = 'FAIL';
            $response['status_msg'] = 'No Sync Direction Found';
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        return $this;
    }

    public function reSyncAttributesetAction()
    {
        $params = $this->getRequest()->getParams();

        $magAttrSetId = $params['mag_attr_set_id'];
        $syncDirection = $params['sync_direction'];
        Mage::log("came to resyncAttributeSet",null,'version.log');
        Mage::log($syncDirection,null,'version.log');


        if($syncDirection==1){ //sync product from magento to microbiz.
            $reSyncResponse = Mage::helper('microbiz_connector')->mbizStartReSync($magAttrSetId,'mage','AttributeSets');
            if($reSyncResponse['status'] == 'SUCCESS') {
                $response = $reSyncResponse;
            }
            else {
                $response['status'] = 'FAIL';
                $response['status_msg'] = $reSyncResponse['status_msg'];
            }

        }
        else if($syncDirection==2){   //sync product from microbiz to magento
            $reSyncResponse = Mage::helper('microbiz_connector')->mbizStartReSync($magAttrSetId,'mbiz','AttributeSets');
            if($reSyncResponse['status'] == 'SUCCESS') {
                $response = $reSyncResponse;
            }
            else {
                $response['status'] = 'FAIL';
                $response['status_msg'] = $reSyncResponse['status_msg'];
            }
        }
        else {
            $response['status'] = 'FAIL';
            $response['status_msg'] = 'No Sync Direction Found';
        }
        Mage::log($response,null,'version.log');

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        return $this;
    }

    public function getAttrSetVerInfoAction()
    {
        $magAttrSetId = $this->getRequest()->getParam('mag_attrset_id');

        $response['status'] = 'SUCCESS';
        $response['response_data'] = $magAttrSetId;

        //get MicroBiz Version Details .
        $mbizVersionData = Mage::helper('microbiz_connector')->getMbizVersionNumbers($magAttrSetId,'AttributeSets');

        Mage::log("came to getAttrsetverInfo",null,'version.log');
        Mage::log($mbizVersionData,null,'version.log');
        $relationdata = Mage::helper('microbiz_connector')->checkIsObjectExists($magAttrSetId,'AttributeSets');
        $mbizAttrSetId = $relationdata['mbiz_id'];
        $attrSetHtml = '<table cellspacing="3" cellpadding="5" border="2px solid"><thead>
                        <tr>
                        <th>MicroBiz</th>
                        <th>Magento</th>
                        </tr></thead><tbody>';
        if($mbizVersionData['status']=='SUCCESS') {

            $attrSetHtml.='<tr>
                                <td>'.$mbizVersionData['mbiz_version_number'].$mbizVersionData['mage_version_number'].'</td>
                                <td>'.$relationdata['mbiz_version_number'].$relationdata['mage_version_number'].'</td>
                           </tr>';

            //code to find missmatch exists or not.
            if($mbizVersionData['mage_version_number'] && $mbizVersionData['mbiz_version_number']) {
                $magMagVerNo = $relationdata['mage_version_number'];
                $mbizMagVerNo = $mbizVersionData['mage_version_number'];

                $magMbizVerNo = $relationdata['mbiz_version_number'];
                $mbizMbizVerNo = $mbizVersionData['mbiz_version_number'];

                $attributeSetMissmatch='';

                if($mbizMagVerNo<$magMagVerNo) {
                    //missmatch found in Versions please click here to sync Attributeset to MicroBiz.
                    $attributeSetMissmatch= '<p>Missmatch Found in Versions please <a href="javascript:void(1)" onclick="resyncAttributeset('.$magAttrSetId.',1)">click here </a> to Sync AttributeSet to MicroBiz. </p>';

                }
                elseif($magMbizVerNo<$mbizMbizVerNo) {
                    //missmatch found in Versions please click here to sync Attributeset From MicroBiz.
                    $attributeSetMissmatch= '<p>Missmatch Found in Versions please <a href="javascript:void(1)" onclick="resyncAttributeset('.$magAttrSetId.',1)">click here </a> to Sync AttributeSet From MicroBiz. </p>';
                }
                else {
                    $attributeSetMissmatch='';
                }
            }
            else {
                $attributeSetMissmatch= '<p>No Relation is Exists in MicroBiz please <a href="javascript:void(1)" onclick="resyncAttributeset('.$magAttrSetId.',1)">click here </a> to Sync AttributeSet to MicroBiz. </p>';
            }

        }
        else {
            $attrSetHtml.='<tr>
                                <td>'.$mbizVersionData['status_msg'].'</td>
                                <td>'.$relationdata['mbiz_version_number'].$relationdata['mage_version_number'].'</td>
                           </tr>';
        }
        /*$attrSetHtml.='<tr>
                            <td>'.$relationdata['mbiz_version_number'].$relationdata['mage_version_number'].'</td>
                        </tr>';*/
        $attrSetHtml.='</tbody></table>';


        /*get Attributes html content starts here*/
        $fullVersionInfo = $mbizVersionData['full_version_info'];

        $arrAttributeResponse = $this->getAllAttrVerInfo($magAttrSetId,$fullVersionInfo,$mbizAttrSetId);
        $attrSetHtml.= $arrAttributeResponse['html_content'];
        /*get Attributes html content ends here*/
        $attributeMissmatch = $arrAttributeResponse['attribute_missmatch'];
        $attributeOptionMissmatch = $arrAttributeResponse['attribute_option_missmatch'];

        if($attributeSetMissmatch!='') {
            $attrSetHtml = $attributeSetMissmatch.$attrSetHtml;
        }
        elseif($attributeMissmatch!='') {
            $attrSetHtml = $attributeMissmatch.$attrSetHtml;
        }
        else {
            if($attributeOptionMissmatch!='') {
                $attrSetHtml = $attributeOptionMissmatch.$attrSetHtml;
            }
        }

        $response['popup_content'] = $attrSetHtml;
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        return $this;
    }

    public function getAllAttrVerInfo($attrSetId,$mbizFullVerInfo,$mbizAttrSetId) {

        //get attribute version data using mbiz attributeset id.

        if($mbizAttrSetId) {
            $attrMissmatch = '';
            $attrOptMissmatch = '';
            $attributeInfo = Mage::getModel('mbizattribute/mbizattribute')->getCollection()
                ->addFieldToFilter('mbiz_attr_set_id', $mbizAttrSetId)->setOrder('id', 'asc')->getData();
            //Mage::log("came to get AttrVerInfo",null,'version.log');
            //Mage::log($attributeInfo,null,'version.log');
            if(!empty($attributeInfo)) {

                //Mage::log("mbizfullinfo",null,'version.log');
                //Mage::log($mbizFullVerInfo,null,'version.log');

                $attrHtml='';
                $attrHtml.='<br/><br/><p><h5>Attribute Version Details</h5><br/>';
                $attrHtml.='<table cellspacing="3" cellpadding="5" border="2px solid"><thead>
                                <tr class="header-title">
                                    <th>Attribute Code</th>
                                    <th>AttributeId</th>
                                    <th>MicroBiz</th>
                                    <th>Magento</th>
                                </tr><tbody>';
                $attrOptsHtml ='';
                $attrOptsHtml.='<br/><br/><p><h5>Attribute Options Version Details</h5><br/>';
                $attrOptsHtml.='<table cellspacing="3" cellpadding="5" border="2px solid"><thead>
                                <tr class="header-title">
                                    <th>Attribute Id</th>
                                    <th>Option Value</th>
                                    <th>Attribute Option Id</th>
                                    <th>MicroBiz</th>
                                    <th>Magento</th>
                                </tr><tbody>';
                foreach($attributeInfo as $attribute) {

                    $attrHtml.='<tr>
                                    <td>'.$attribute['magento_attr_code'].'</td>
                                    <td>'.$attribute['magento_id'].'(MicroBiz Id-'.$mbizFullVerInfo['attributes'][$attribute['magento_id']]['mbiz_attribute_id'].') </td>
                                    <td>'.$mbizFullVerInfo['attributes'][$attribute['magento_id']]['mbiz_version_number'].$mbizFullVerInfo['attributes'][$attribute['magento_id']]['mage_version_number'].'</td>
                                    <td>'.$attribute['mbiz_version_number'].$attribute['mage_version_number'].'</td>
                                </tr>';

                    /*$attrHtml.='<tr>


                                    <td>'.$attribute['magento_id'].'</td>
                                    <td>'.$attribute['mage_version_number'].'</td>
                                    <td>'.$attribute['mbiz_version_number'].'</td>
                                </tr>';*/

                    /*code to check Attribute miss match starts here*/

                    if($mbizFullVerInfo['attributes'][$attribute['magento_id']]['mage_version_number']<$attribute['mage_version_number']) {
                        //if magento version number in mbiz is less than the magento version number in magento sync from magento to microbiz
                        $attrMissmatch='Missmatch Occurred in Attributes Versions Please <a href="javascript:void(1)" onclick="resyncAttributeset('.$attrSetId.',1)">click here</a> to Sync AttributeSet to MicroBiz';
                    }
                    elseif($attribute['mbiz_version_number']<$mbizFullVerInfo['attributes'][$attribute['magento_id']]['mbiz_version_number']) {
                        //if mbiz version number in magento is less than the mbiz version number in microbiz sync from microbiz to magento
                        $attrMissmatch='Missmatch Occurred in Attributes Versions Please <a href="javascript:void(1)" onclick="resyncAttributeset('.$attrSetId.',2)">click here</a> to Sync AttributeSet to Magento';
                    }
                    else {
                    }

                    /*code to check Attribute miss match ends here*/


                    //options Html Code
                    $mbizAttrId = $attribute['mbiz_id'];
                    $magAttrId = $attribute['magento_id'];
                    $optsrelationdata = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()
                        ->addFieldToFilter('mbiz_attr_id', $mbizAttrId)->setOrder('id', 'asc')->getData();

                    if(!empty($optsrelationdata)) {
                        foreach($optsrelationdata as $options) {
                            $optionLabel = '';
                            if($magAttrId) {
                                $attr = Mage::getModel('catalog/resource_eav_attribute')->load($magAttrId);
                                $optionLabel = $attr->getSource()->getOptionText($options['magento_id']);
                            }
                            $attrOptsHtml.='<tr>
                                        <td>'.$magAttrId.'</td>
                                        <td>'.$optionLabel.'</td>
                                        <td>'.$options['magento_id'].'(MicroBiz Id:-'.$mbizFullVerInfo['attribute_options'][$options['magento_id']]['mbiz_option_id'].'</td>
                                        <td>'.$mbizFullVerInfo['attribute_options'][$options['magento_id']]['mbiz_version_number'].
                                $mbizFullVerInfo['attribute_options'][$options['magento_id']]['mage_version_number'].'</td>
                                        <td>'.$options['mbiz_version_number'].$options['mage_version_number'].'</td>
                                    </tr>';

                            /*$attrOptsHtml.='<tr>
                                                <td>MicroBiz</td>
                                                <td>'.$mbizFullVerInfo['attribute_options'][$options['magento_id']]['option_id'].'</td>
                                                <td>'.$mbizFullVerInfo['attribute_options'][$options['magento_id']]['mage_version_number'].'</td>
                                                <td>'.$mbizFullVerInfo['attribute_options'][$options['magento_id']]['mbiz_version_number'].'</td>
                                            </tr>';*/


                            /*Code to check any Missmatch Occurred in Versions Starts Here*/
                            if($attrMissmatch=='') {
                                if($mbizFullVerInfo['attribute_options'][$options['magento_id']]['mage_version_number']<$options['mage_version_number']) {
                                    //if magento version number in mbiz is less than the magento version number in magento sync from magento to microbiz
                                    $attrOptMissmatch='Missmatch Occurred in Attribute Option Versions Please <a href="javascript:void(1)" onclick="resyncAttributeset('.$attrSetId.',1)">click here</a> to Sync AttributeSet to MicroBiz';
                                }
                                elseif($options['mbiz_version_number']<$mbizFullVerInfo['attribute_options'][$options['magento_id']]['mbiz_version_number']) {
                                    //if mbiz version number in magento is less than the mbiz version number in microbiz sync from microbiz to magento
                                    $attrOptMissmatch='Missmatch Occurred in Attribute Option Versions Please <a href="javascript:void(1)" onclick="resyncAttributeset('.$attrSetId.',2)">click here</a> to Sync AttributeSet to Magento';
                                }
                                else {
                                }
                            }

                            /*Code to check any Missmatch Occurred in Versions Ends Here*/
                        }


                    }

                }

                $attrHtml.='</tbody></table>';
                $attrOptsHtml.='</tbody></table>';

                //code to form attributeoption html starts here.




            }
        }
        else {
            $attrHtml='';
            $attrHtml.='<br/><br/><p><h5>Attribute Version Details</h5><br/>';
            $attrHtml.='<p>No MicroBiz AttributeSet ID Found.</p>';
        }
        Mage::log($attrHtml,null,'version.log');
        Mage::log("attribute options html code",null,'version.log');
        Mage::log($attrOptsHtml,null,'version.log');
        if($attrOptsHtml!='') {
            $attrHtml = $attrHtml.$attrOptsHtml;
        }

        $response['html_content'] = $attrHtml;
        $response['attribute_missmatch'] = $attrMissmatch;
        $response['attribute_option_missmatch'] = $attrOptMissmatch;
        return $response;
    }

    public function getAttrVerInfoAction() {
        $magAttrId = $this->getRequest()->getParam('mag_attr_id');
        $attrMissmatch = '';
        $attrOptMissmatch = '';
        if($magAttrId) {
            $relationData = Mage::helper('microbiz_connector')->checkIsObjectExists($magAttrId, 'Attributes');
            $mbizAttributeId = $relationData['mbiz_id'];
            $mbizAttrSetId = $relationData['mbiz_attr_set_id'];

            $attrSetRelationData = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->
                addFieldToFilter('mbiz_id', $mbizAttrSetId)->setOrder('id', 'asc')->getFirstItem()->getData();
            $magAttrSetId = $attrSetRelationData['magento_id'];
            $mbizVersionData = Mage::helper('microbiz_connector')->getMbizVersionNumbers($magAttrId,'Attributes');
            $attrHtml = '';
            $attrHtml.= '<table cellspacing="3" cellpadding="5" border="2px solid"><thead>
                        <tr>
                        <th>MicroBiz</th>
                        <th>Magento</th>
                        </tr></thead><tbody>';
            $attrOptsHtml ='';
            $attrOptsHtml.='<br/><br/><p><h5>Attribute Options Version Details</h5><br/>';
            $attrOptsHtml.='<table cellspacing="3" cellpadding="5" border="2px solid"><thead>
                                <tr class="header-title">
                                    <th>Attribute Id</th>
                                    <th>Option Value</th>
                                    <th>Attribute Option Id</th>
                                    <th>MicroBiz</th>
                                    <th>Magento</th>
                                </tr><tbody>';
            $attrOptionRel = array();
            if($mbizVersionData['status']=='SUCCESS') {
                $attrOptionRel = $mbizVersionData['attribute_option_rel'];
                $attrHtml.='<tr>
                                <td>'.$mbizVersionData['mbiz_version_number'].$mbizVersionData['mage_version_number'].'</td>
                                <td>'.$relationData['mbiz_version_number'].$relationData['mage_version_number'].'</td>
                            </tr>';
            }
            else {
                $attrHtml.='<tr>
                                <td>'.$mbizVersionData['status_msg'].'</td>
                                <td>'.$relationData['mbiz_version_number'].$relationData['mage_version_number'].'</td>
                            </tr>';
            }
            /*$attrHtml.='<tr>
                                <td>Magento</td>
                                <td>'.$relationData['mage_version_number'].'</td>
                                <td>'.$relationData['mbiz_version_number'].'</td>
                            </tr>';*/


            $attrHtml.='</tbody></table>';

            /*Code to check attribute version missmatch starts here*/
            if($mbizVersionData['mage_version_number']<$relationData['mage_version_number']) {
                //if mage version in mbiz is less than the mage version in mag then sync attribute to microbiz
                $attrMissmatch='Missmatch Occurred in Attribute Versions Please <a href="javascript:void(1)"
                    onclick="resyncAttribute('.$magAttrId.',1)">click here</a> to Sync Attribute to MicroBiz';
            }
            elseif($relationData['mbiz_version_number']<$mbizVersionData['mbiz_version_number']) {
                //if mbiz version in mage is less than the mbiz version in mbiz then sync attribute to magento
                $attrMissmatch='Missmatch Occurred in Attribute Versions Please <a href="javascript:void(1)"
                    onclick="resyncAttribute('.$magAttrId.',2)">click here</a> to Sync Attribute to MicroBiz';
            }
            else {

            }
            /*Code to check attribute version missmatch ends here*/

            //options Html Code
            $mbizAttrId = $relationData['mbiz_id'];
            $magAttrId = $relationData['magento_id'];
            $optsrelationdata = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()
                ->addFieldToFilter('mbiz_attr_id', $mbizAttrId)->setOrder('id', 'asc')->getData();

            if(!empty($optsrelationdata)) {
                foreach($optsrelationdata as $options) {
                    $optionLabel = '';
                    if($magAttrId) {
                        $attr = Mage::getModel('catalog/resource_eav_attribute')->load($magAttrId);
                        $optionLabel = $attr->getSource()->getOptionText($options['magento_id']);
                    }
                    $attrOptsHtml.='<tr>
                                        <td>'.$magAttrId.'</td>
                                        <td>'.$optionLabel.'</td>
                                        <td>'.$options['magento_id'].'( MicroBiz Id:-'.$attrOptionRel[$options['magento_id']]['mbiz_option_id'].') </td>
                                        <td>'.$attrOptionRel[$options['magento_id']]['mbiz_version_number'].$attrOptionRel[$options['magento_id']]['mage_version_number'].'</td>
                                        <td>'.$options['mbiz_version_number'].$options['mage_version_number'].'</td>
                                    </tr>';

                    /*$attrOptsHtml.='<tr>
                                                <td>MicroBiz</td>
                                                <td>'.$attrOptionRel[$options['magento_id']]['option_id'].'</td>
                                                <td>'.$attrOptionRel[$options['magento_id']]['mage_version_number'].'</td>
                                                <td>'.$attrOptionRel[$options['magento_id']]['mbiz_version_number'].'</td>
                                            </tr>';*/


                    //code to check options missmatch
                    if($attrMissmatch=='') {
                        if($attrOptionRel[$options['magento_id']]['mage_version_number']<$options['mage_version_number']) {
                            //if mage version in mbiz is less than the mage version in magento then sync to microbiz.
                            $attrOptMissmatch='Missmatch Occurred in Attribute Option Versions Please <a href="javascript:void(1)"
                    onclick="resyncAttribute('.$magAttrId.',1)">click here</a> to Sync Attribute to MicroBiz';
                        }
                        elseif($options['mbiz_version_number']<$attrOptionRel[$options['magento_id']]['mbiz_version_number']) {
                            $attrOptMissmatch='Missmatch Occurred in Attribute Option Versions Please <a href="javascript:void(1)"
                    onclick="resyncAttribute('.$magAttrId.',2)">click here</a> to Sync Attribute to MicroBiz';
                        }
                        else {

                        }
                    }
                }

            }
            else {

                $attrOptsHtml.='<tr>
                                        <td>'.$magAttrId.'</td>
                                        <td colspan="5">NO Option Relations Exists with this Attribute</td>
                                    </tr>';
            }

            $attrOptsHtml.='</tbody></table>';

            if($attrOptsHtml!='') {
                $attrHtml = $attrHtml.$attrOptsHtml;
            }

            if($attrMissmatch!='') {
                $attrHtml = $attrMissmatch.$attrHtml;
            }
            elseif($attrOptMissmatch!='') {
                $attrHtml = $attrOptMissmatch.$attrHtml;
            }
            else {

            }

            $response['status']='SUCCESS';
            $response['popup_content'] = $attrHtml;


        }
        else {
            $response['status'] = 'FAIL';
            $response['status_msg'] = 'Unable to Get Version Details No Attribute Id Passed';
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }

    public function resyncAttributeAction()
    {
        $params = $this->getRequest()->getParams();

        $magAttrId = $params['mag_attr_id'];
        $syncDirection = $params['sync_direction'];

        Mage::log("came to resyncAttribute",null,'resync.log');
        if($syncDirection==1){ //sync Attribute from magento to microbiz.
            $reSyncResponse = Mage::helper('microbiz_connector')->mbizStartReSync($magAttrId,'mage','Attributes');
            Mage::log($reSyncResponse,null,'resync.log');
            if($reSyncResponse['status'] == 'SUCCESS') {
                $response = $reSyncResponse;
            }
            else {
                $response['status'] = 'FAIL';
                $response['status_msg'] = $reSyncResponse['status_msg'];
            }
        }
        else if($syncDirection==2){   //sync Attribute from microbiz to magento
            $reSyncResponse = Mage::helper('microbiz_connector')->mbizStartReSync($magAttrId,'mbiz','Attributes');
            Mage::log($reSyncResponse,null,'resync.log');
            if($reSyncResponse['status'] == 'SUCCESS') {
                $response = $reSyncResponse;
            }
            else {
                $response['status'] = 'FAIL';
                $response['status_msg'] = $reSyncResponse['status_msg'];
            }
        }
        else {
            $response['status'] = 'FAIL';
            $response['status_msg'] = 'No Sync Direction Found';
        }
        Mage::log($response,null,'resync.log');
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        return $this;
    }

    /**
     * @author KT 174
     * @description This method is used to get the post data and create magento api user and send it to microbiz and
     * update the microbiz api user in magento config values.
     */
    public function mbizLinkApiUsersAction()
    {
        $postdata = $this->getRequest()->getPost();
        Mage::log("came to mbizlinkapiusers action",null,'linking.log');
        Mage::log($postdata,null,'linking.log');

        $magentoUrl = $postdata['mage_url'];
        $mbizSiteName = $postdata['mbiz_sitename'];
        $siteType = $postdata['mbiz_sitetype'];
        if($siteType==1)
        {
            $apiServerUrl=$postdata['mbiz_sitename'];
            $apiServerUrl = 'https://'.$apiServerUrl.'.microbiz.com';
        }
        else {
            $apiServerUrl=$postdata['mbiz_sitename'];
        }
        $mbizUname = $postdata['mbiz_username'];
        $mbizPwd = $postdata['mbiz_password'];
        $configData = new Mage_Core_Model_Config();

        $arrResponse = array();

        /*creating api user and role and relating both of them starts here.*/

        if(!empty($postdata)) {
            /*Validate the Api User with the Post Users Starts here.*/
            Mage::helper('microbiz_connector')->RemoveCaching();
            $apiUserEmail = $postdata['mag_api_email'];
            $apiuserName = $postdata['mag_api_username'];


            $usernameApiModel = Mage::getModel('api/user')->getCollection()
                ->addFieldToFilter('username',$apiuserName)
                ->getFirstItem()->getData();

            if(!empty($usernameApiModel)) { //username is exists in the existing users

                $magApiUname = Mage::getStoreConfig('connector/installation/mag_api_username');
                $isLinked = Mage::getStoreConfig('connector/installation/is_linked');

                Mage::log("came to username exists.",null,'linking.log');

                if($isLinked==1) {  //username is exists in the list but linked to microbiz.

                    if($apiuserName!=$magApiUname) {  //username changed then again creating new one in both mag and mbiz.
                        Mage::log("came to username mismatch.",null,'linking.log');
                        $userApiModel = Mage::getModel('api/user')->getCollection()
                            ->addFieldToFilter('email',$apiUserEmail)
                            ->getFirstItem()->getData();

                        Mage::log($userApiModel,null,'linking.log');
                        $apiPassword = Mage::helper('microbiz_connector')->mbizGenerateApiPassword(rand(8,12));
                        if(empty($userApiModel)) {

                            $magApiUserResponse = $this->magCreateApiUser($apiuserName,$apiUserEmail,$apiPassword);

                        }
                        else {
                            $userId = $usernameApiModel['user_id'];
                            $roleName = Mage::helper('microbiz_connector')->mbizGenerateUniqueString(4);
                            $roleName = 'MicroBiz_'.$roleName;
                            $roleModel = Mage::getModel('api/roles');
                            $roleModel =  $roleModel->setName($roleName)
                                ->setPid(0)
                                ->setRoleType('G')
                                ->save();
                            $roleId = $roleModel->getId();
                            $resource = array("all");
                            Mage::getModel("api/rules")->setRoleId($roleId)
                                ->setResources($resource)
                                ->saveRel();

                            $userApiModel = Mage::getModel('api/user')->load($userId);

                            $userApiModel->setEmail($apiUserEmail)->setApiKey($apiPassword)->setIsActive(1)->setId($userId)->save();

                            $userApiModel->setRoleIds(array($roleId))  // your created custom role
                                ->setRoleUserId($userId)
                                ->saveRelations();
                            $magApiUserResponse['status']='Success';

                        }





                        if($magApiUserResponse['status']=='Success') {
                            $configData ->saveConfig('connector/installation/mag_api_username', $apiuserName, 'default', 0);

                            $configData ->saveConfig('connector/installation/mag_api_email', $apiUserEmail, 'default', 0);
                            $configData ->saveConfig('connector/settings/mag_api_key', $apiPassword, 'default', 0);

                            /*Sending Curl request to microbiz to create api users */

                            Mage::helper('microbiz_connector')->RemoveCaching();

                            $arrApiUserDetails = array();
                            $ver = new Mage;
                            $edition = $ver->getEdition();
                            $version = $ver->getVersion();
                            $version = str_split($version,3);
                            $arrApiUserDetails['app_api_version']=$version[0];
                            $arrApiUserDetails['app_api_edition']=$edition;
                            $arrApiUserDetails['app_api_type']='SOAP';
                            $arrApiUserDetails['app_api_display_name']=$apiServerUrl;
                            $arrApiUserDetails['app_api_user_name']=$apiuserName;
                            $arrApiUserDetails['app_api_key']=$apiPassword;
                            $arrApiUserDetails['app_api_user_email']=$apiUserEmail;
                            $arrApiUserDetails['app_api_mage_url']=$magentoUrl;
                            Mage::log($arrApiUserDetails,null,'linking.log');

                            $mbizApiServer = $apiServerUrl;

                            $url = $mbizApiServer.'/index.php/api/mbizInstanceCreateApiUser';

                            $response = $this->mbizCreateApiUsers($arrApiUserDetails,$url,$mbizUname,$mbizPwd);

                            if($response['http_code']==200) {
                                $mbizApiUserName = $response['api_info']['api_usr_name'];
                                $mbizApiPwd = $response['api_info']['api_key'];
                                $mbizApiServer = $mbizApiServer;
                                $configData ->saveConfig('connector/settings/api_server', $mbizApiServer, 'default', 0);
                                $configData ->saveConfig('connector/settings/instance_id', $response['api_settings']['app_instance_id'], 'default', 0);
                                $configData ->saveConfig('connector/settings/syncstatus', "1", 'default', 0);
                                $configData ->saveConfig('connector/settings/api_user', $mbizApiUserName, 'default', 0);
                                $configData ->saveConfig('connector/settings/api_key', $mbizApiPwd, 'default', 0);

                                $configData ->saveConfig('connector/settings/display_name', $mbizSiteName, 'default', 0);

                                $configData ->saveConfig('connector/installation/is_linked', 1, 'default', 0);

                                Mage::helper('microbiz_connector')->RemoveCaching();


                                $arrResponse['status'] = 'SUCCESS';
                                $arrResponse['instance_id'] = $response['api_settings']['app_instance_id'];
                                $arrResponse['syncstatus'] = $response['syncstatus'];
                                $arrResponse['mag_api_username'] = $apiuserName;
                                $arrResponse['mag_api_password'] = $apiPassword;
                                $arrResponse['mbiz_api_username'] = $mbizApiUserName;
                                $arrResponse['mbiz_api_password'] = $mbizApiPwd;
                                $magentoUrl = explode('://',$magentoUrl);
                                $arrResponse['status_msg'] = $apiServerUrl.'  is now linked to '.$magentoUrl[1];
                                $arrResponse['is_linked_status'] = 1;
                            }
                            else {
                                $arrResponse['status'] = 'ERROR';
                                $arrResponse['status_msg'] = $response['status_msg'];
                                $arrResponse['is_linked_status'] = 0;
                                $configData ->saveConfig('connector/installation/is_linked', 0, 'default', 0);
                            }


                        }
                        else {
                            $arrResponse['status'] = 'ERROR';
                            $arrResponse['status_msg'] = $magApiUserResponse['status_msg'];
                        }



                    }
                    else {  //username is not changed and linked to mbiz already
                        Mage::helper('microbiz_connector')->RemoveCaching();
                        Mage::helper('microbiz_connector')->RemoveCaching();
                        $arrResponse['status'] = 'SUCCESS';
                        $arrResponse['instance_id'] = Mage::getStoreConfig('connector/settings/instance_id');
                        $arrResponse['syncstatus'] = Mage::getStoreConfig('connector/settings/syncstatus');
                        $arrResponse['mag_api_username'] = Mage::getStoreConfig('connector/installation/mag_api_username');
                        $arrResponse['mag_api_email'] = Mage::getStoreConfig('connector/installation/mag_api_email');
                        $arrResponse['mag_api_password'] = Mage::getStoreConfig('connector/settings/mag_api_key');
                        $arrResponse['mbiz_api_username'] = Mage::getStoreConfig('connector/settings/api_user');
                        $arrResponse['mbiz_api_password'] = Mage::getStoreConfig('connector/settings/api_key');
                        $magentoUrl = explode('://',$magentoUrl);
                        $arrResponse['status_msg'] = $apiServerUrl.'  is now linked to '.$magentoUrl[1];
                        $arrResponse['is_linked_status'] = 1;
                        $configData ->saveConfig('connector/installation/is_linked', 1, 'default', 0);
                    }


                }
                else {  //if username is not linked to microbiz but exists in magento

                    /*Creating New Roles with all Permissions and Updating the User*/

                    $userId = $usernameApiModel['user_id'];

                    try {
                        $roleName = Mage::helper('microbiz_connector')->mbizGenerateUniqueString(4);
                        $roleName = 'MicroBiz_'.$roleName;
                        $roleModel = Mage::getModel('api/roles');
                        $roleModel =  $roleModel->setName($roleName)
                            ->setPid(0)
                            ->setRoleType('G')
                            ->save();
                        $roleId = $roleModel->getId();
                        $resource = array("all");
                        Mage::getModel("api/rules")->setRoleId($roleId)
                            ->setResources($resource)
                            ->saveRel();

                        $userApiModel = Mage::getModel('api/user')->load($userId);

                        $apiPassword = Mage::helper('microbiz_connector')->mbizGenerateApiPassword(rand(8,12));

                        $userApiModel->setEmail($apiUserEmail)->setApiKey($apiPassword)->setIsActive(1)->setId($userId)->save();

                        $userApiModel->setRoleIds(array($roleId))  // your created custom role
                            ->setRoleUserId($userId)
                            ->saveRelations();

                        $configData ->saveConfig('connector/installation/mag_api_username', $apiuserName, 'default', 0);

                        $configData ->saveConfig('connector/installation/mag_api_email', $apiUserEmail, 'default', 0);
                        $configData ->saveConfig('connector/settings/mag_api_key', $apiPassword, 'default', 0);


                        /*Creating New Roles with all Permissions and Updating the User Ends*/


                        /*Sending Curl Request to MicroBiz to Create Api User in Microbiz Starts Here.*/

                        Mage::helper('microbiz_connector')->RemoveCaching();

                        $arrApiUserDetails = array();
                        $ver = new Mage;
                        $edition = $ver->getEdition();
                        $version = $ver->getVersion();
                        $version = str_split($version,3);
                        $arrApiUserDetails['app_api_version']=$version[0];
                        $arrApiUserDetails['app_api_edition']=$edition;
                        $arrApiUserDetails['app_api_type']='SOAP';
                        $arrApiUserDetails['app_api_display_name']=$apiServerUrl;
                        $arrApiUserDetails['app_api_user_name']=$apiuserName;
                        $arrApiUserDetails['app_api_key']=$apiPassword;
                        $arrApiUserDetails['app_api_user_email']=$apiUserEmail;
                        $arrApiUserDetails['app_api_mage_url']=$magentoUrl;
                        Mage::log($arrApiUserDetails,null,'linking.log');

                        $mbizApiServer = $apiServerUrl;

                        $url = $mbizApiServer.'/index.php/api/mbizInstanceCreateApiUser';

                        $response = $this->mbizCreateApiUsers($arrApiUserDetails,$url,$mbizUname,$mbizPwd);

                        if($response['http_code']==200) {
                            $mbizApiUserName = $response['api_info']['api_usr_name'];
                            $mbizApiPwd = $response['api_info']['api_key'];
                            $mbizApiServer = $mbizApiServer;
                            $configData ->saveConfig('connector/settings/api_server', $mbizApiServer, 'default', 0);
                            $configData ->saveConfig('connector/settings/instance_id', $response['api_settings']['app_instance_id'], 'default', 0);
                            $configData ->saveConfig('connector/settings/syncstatus', "1", 'default', 0);
                            $configData ->saveConfig('connector/settings/api_user', $mbizApiUserName, 'default', 0);
                            $configData ->saveConfig('connector/settings/api_key', $mbizApiPwd, 'default', 0);

                            $configData ->saveConfig('connector/settings/display_name', $mbizSiteName, 'default', 0);

                            $configData ->saveConfig('connector/installation/is_linked', 1, 'default', 0);

                            Mage::helper('microbiz_connector')->RemoveCaching();


                            $arrResponse['status'] = 'SUCCESS';
                            $arrResponse['instance_id'] = $response['api_settings']['app_instance_id'];
                            $arrResponse['syncstatus'] = $response['syncstatus'];
                            $arrResponse['mag_api_username'] = $apiuserName;
                            $arrResponse['mag_api_password'] = $apiPassword;
                            $arrResponse['mbiz_api_username'] = $mbizApiUserName;
                            $arrResponse['mbiz_api_password'] = $mbizApiPwd;
                            $magentoUrl = explode('://',$magentoUrl);
                            $arrResponse['status_msg'] = $apiServerUrl.'  is now linked to '.$magentoUrl[1];
                            $arrResponse['is_linked_status'] = 1;
                        }
                        else {
                            $arrResponse['status'] = 'ERROR';
                            $arrResponse['status_msg'] = $response['status_msg'];
                            $arrResponse['is_linked_status'] = 0;
                            $configData ->saveConfig('connector/installation/is_linked', 0, 'default', 0);
                        }



                        /*Sending Curl Request to MicroBiz to Create Api User in Microbiz Ends Here.*/
                    }
                    catch(Exception $ex) {

                        $arrResponse['status'] = 'ERROR';
                        $arrResponse['status_msg'] = 'Exception Occurred while Creating api Users in Magento and sending to MicroBiz '.$ex->getMessage();
                        $arrResponse['is_linked_status'] = 0;
                        $configData ->saveConfig('connector/installation/is_linked', 0, 'default', 0);

                    }

                }
            }
            else {


                Mage::log("came to username mismatch.",null,'linking.log');
                $userApiModel = Mage::getModel('api/user')->getCollection()
                    ->addFieldToFilter('email',$apiUserEmail)
                    ->getFirstItem()->getData();

                Mage::log($userApiModel,null,'linking.log');
                $apiPassword = Mage::helper('microbiz_connector')->mbizGenerateApiPassword(rand(8,12));
                if(empty($userApiModel)) {

                    $magApiUserResponse = $this->magCreateApiUser($apiuserName,$apiUserEmail,$apiPassword);

                }
                else {
                    $userId = $userApiModel['user_id'];
                    $roleName = Mage::helper('microbiz_connector')->mbizGenerateUniqueString(4);
                    $roleName = 'MicroBiz_'.$roleName;
                    $roleModel = Mage::getModel('api/roles');
                    $roleModel =  $roleModel->setName($roleName)
                        ->setPid(0)
                        ->setRoleType('G')
                        ->save();
                    $roleId = $roleModel->getId();
                    $resource = array("all");
                    Mage::getModel("api/rules")->setRoleId($roleId)
                        ->setResources($resource)
                        ->saveRel();

                    $userApiModel = Mage::getModel('api/user')->load($userId);

                    $userApiModel->setUsername($apiuserName)->setEmail($apiUserEmail)->setApiKey($apiPassword)->setIsActive(1)->setId($userId)->save();

                    $userApiModel->setRoleIds(array($roleId))  // your created custom role
                        ->setRoleUserId($userId)
                        ->saveRelations();
                    $magApiUserResponse['status']='Success';

                }

                if($magApiUserResponse['status']=='Success') {
                    $configData ->saveConfig('connector/installation/mag_api_username', $apiuserName, 'default', 0);

                    $configData ->saveConfig('connector/installation/mag_api_email', $apiUserEmail, 'default', 0);
                    $configData ->saveConfig('connector/settings/mag_api_key', $apiPassword, 'default', 0);

                    /*Sending Curl request to microbiz to create api users */

                    Mage::helper('microbiz_connector')->RemoveCaching();

                    $arrApiUserDetails = array();
                    $ver = new Mage;
                    $edition = $ver->getEdition();
                    $version = $ver->getVersion();
                    $version = str_split($version,3);
                    $arrApiUserDetails['app_api_version']=$version[0];
                    $arrApiUserDetails['app_api_edition']=$edition;
                    $arrApiUserDetails['app_api_type']='SOAP';
                    $arrApiUserDetails['app_api_display_name']=$apiServerUrl;
                    $arrApiUserDetails['app_api_user_name']=$apiuserName;
                    $arrApiUserDetails['app_api_key']=$apiPassword;
                    $arrApiUserDetails['app_api_user_email']=$apiUserEmail;
                    $arrApiUserDetails['app_api_mage_url']=$magentoUrl;
                    Mage::log($arrApiUserDetails,null,'linking.log');

                    $mbizApiServer = $apiServerUrl;

                    $url = $mbizApiServer.'/index.php/api/mbizInstanceCreateApiUser';

                    $response = $this->mbizCreateApiUsers($arrApiUserDetails,$url,$mbizUname,$mbizPwd);

                    if($response['http_code']==200) {
                        $mbizApiUserName = $response['api_info']['api_usr_name'];
                        $mbizApiPwd = $response['api_info']['api_key'];
                        $mbizApiServer = $mbizApiServer;
                        $configData ->saveConfig('connector/settings/api_server', $mbizApiServer, 'default', 0);
                        $configData ->saveConfig('connector/settings/instance_id', $response['api_settings']['app_instance_id'], 'default', 0);
                        $configData ->saveConfig('connector/settings/syncstatus', "1", 'default', 0);
                        $configData ->saveConfig('connector/settings/api_user', $mbizApiUserName, 'default', 0);
                        $configData ->saveConfig('connector/settings/api_key', $mbizApiPwd, 'default', 0);

                        $configData ->saveConfig('connector/settings/display_name', $mbizSiteName, 'default', 0);

                        $configData ->saveConfig('connector/installation/is_linked', 1, 'default', 0);

                        Mage::helper('microbiz_connector')->RemoveCaching();


                        $arrResponse['status'] = 'SUCCESS';
                        $arrResponse['instance_id'] = $response['api_settings']['app_instance_id'];
                        $arrResponse['syncstatus'] = $response['syncstatus'];
                        $arrResponse['mag_api_username'] = $apiuserName;
                        $arrResponse['mag_api_password'] = $apiPassword;
                        $arrResponse['mbiz_api_username'] = $mbizApiUserName;
                        $arrResponse['mbiz_api_password'] = $mbizApiPwd;
                        $magentoUrl = explode('://',$magentoUrl);
                        $arrResponse['status_msg'] = $apiServerUrl.'  is now linked to '.$magentoUrl[1];
                        $arrResponse['is_linked_status'] = 1;
                    }
                    else {
                        $arrResponse['status'] = 'ERROR';
                        $arrResponse['status_msg'] = $response['status_msg'];
                        $arrResponse['is_linked_status'] = 0;
                        $configData ->saveConfig('connector/installation/is_linked', 0, 'default', 0);
                    }


                }
                else {
                    $arrResponse['status'] = 'ERROR';
                    $arrResponse['status_msg'] = $magApiUserResponse['status_msg'];
                }
            }



            /*Validate the Api User with the Post Users Ends here.*/
        }
        else {
            $arrResponse['status'] = 'ERROR';
            $arrResponse['status_msg'] = 'Unable to Establish the Connection no data posted for Creating Api users ';
            $arrResponse['is_linked_status'] = 0;
            $configData ->saveConfig('connector/installation/is_linked', 0, 'default', 0);
        }
        /*creating api user and role and relating both of them ends here.*/

        Mage::log($arrResponse,null,'linking.log');
        Mage::helper('microbiz_connector')->RemoveCaching();

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($arrResponse));
        return $this;

    }

    /**
     * @return $this
     * @author KT174
     * @description This methdo is used to send Email to cloudsupport that magento and microbiz connection needs ssl.
     */
    public function mbizGetSslConnAction()
    {
        $postData = $this->getRequest()->getPost();
        $jsonResponse = array();
        if(!empty($postData))
        {
            $mbizSite = $postData['mbiz_site_name'];
            $magSite = $postData['mag_site_name'];

            try {
                $emailTemplateId = 'initialload_email_template';
                $emailTemplate = Mage::getModel('core/email_template')->loadDefault($emailTemplateId);
                $storeEmailId = Mage::getStoreConfig('trans_email/ident_general/email');
                if($storeEmailId!='')
                {
                    $emailTemplate->setSenderEmail($storeEmailId);
                }
                else {
                    $emailTemplate->setSenderEmail('info@Ktree.com');
                }
//Mage::log($emailTemplate->getData());
                $emailTempVars = array();
                $emailTempVars['magento_site_url'] = $magSite;
                $emailTempVars['mbiz_site_url']	= $mbizSite;
                $processedTemplate = $emailTemplate->getProcessedTemplate($emailTempVars);
//Mage::log($processedTemplate);
                //Mage::log($emailTemplateId);
                //Mage::log($emailTempVars);


                $emailTemplate->send('cloudsupport@microbiz.com','Cloud Support',$emailTempVars);

                $jsonResponse['status'] = 'SUCCESS';
                $jsonResponse['status_msg'] = 'Email has been sent to cloud support for ssl .';
            }
            catch (Exception $e)
            {
                $jsonResponse['status'] = 'ERROR';
                $jsonResponse['status_msg'] = 'Unable to send Email to cloud support due to '.$e->getMessage();
            }


        }
        else {
            $jsonResponse['status'] = 'ERROR';
            $jsonResponse['status_msg'] = 'Invalid Magento and MicroBiz Urls';
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonResponse));
        return $this;

    }

    public function mbizCreateApiUsers($arrApiUserDetails,$url,$mbizUname,$mbizPwd)
    {
        Mage::log("came to mbiz Create Api Users ",null,'linking.log');
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: '.$mbizUname,
            'X-MBIZPOS-PASSWORD: '.$mbizPwd
        );


        $arrApiUserDetails['app_plugin_version'] = (string) Mage::getConfig()->getNode()->modules->Microbiz_Connector->version;
        $data = json_encode($arrApiUserDetails);
        Mage::log($data,null,'linking.log');


        $handle = curl_init();		//curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($handle);	// send curl request to microbiz

        $response=json_decode($response,true);

        $code = curl_getinfo($handle);
        Mage::log($response,null,'linking.log');
        Mage::log($code,null,'linking.log');
        $configData = new Mage_Core_Model_Config();

        if($code['http_code'] == 200 ) {
            $arrResponse = $response;
        }
        else if($code['http_code'] == 500) {
            $arrResponse['status'] = 'ERROR';
            $arrResponse['status_msg'] = $code['http_code'].' - Internal Server Error'.$response['message'];
            $arrResponse['is_linked_status'] = 0;
            $configData ->saveConfig('connector/installation/is_linked', 0, 'default', 0);
        }
        else if($code['http_code'] == 0) {
            $arrResponse['status'] = 'ERROR';
            $arrResponse['status_msg'] = $code['http_code'].' - Please Check the API Server URL'.$response['message'];
            $arrResponse['is_linked_status'] = 0;
            $configData ->saveConfig('connector/installation/is_linked', 0, 'default', 0);
        }
        else
        {
            $arrResponse['status'] = 'ERROR';
            $arrResponse['status_msg'] = $code['http_code'].' - '.$response['message'];
            $arrResponse['is_linked_status'] = 0;
            $configData ->saveConfig('connector/installation/is_linked', 0, 'default', 0);
        }

        $arrResponse['http_code'] = $code['http_code'];

        return $arrResponse;
    }

    public function magCreateApiUser($apiuserName,$apiUserEmail,$apiPassword) {
        $magApiUserResponse = array();
        $roleName = Mage::helper('microbiz_connector')->mbizGenerateUniqueString(4);
        try {
            $roleName = 'MicroBiz_'.$roleName;
            $roleModel = Mage::getModel('api/roles');
            $roleModel =  $roleModel->setName($roleName)
                ->setPid(0)
                ->setRoleType('G')
                ->save();
            //echo $roleModel->getRoleId();
            $roleId = $roleModel->getId();
            $resource = array("all");
            Mage::getModel("api/rules")->setRoleId($roleId)
                ->setResources($resource)
                ->saveRel();
            $apiUserName = $apiuserName;


            $userapi = Mage::getModel('api/user')
                ->setData(array(
                    'username' => $apiUserName,
                    'firstname' => 'microbiz',
                    'lastname' => 'microbiz',
                    'email' => $apiUserEmail,
                    'api_key' => $apiPassword,
                    'api_key_confirmation' => $apiPassword,
                    'is_active' => 1,
                    // 'user_roles' => '',
                    //'assigned_user_role' => '',
                    // 'role_name' => '',
                    'roles' => array($roleId) // your created custom role
                ));
            $userapi->save();
            $userapi->setRoleIds(array($roleId))  // your created custom role
                ->setRoleUserId($userapi->getUserId())
                ->saveRelations();

            $magApiUserResponse['status'] = 'Success';


        }
        catch(Exception $ex) {
            $magApiUserResponse['status'] = 'Error';
            $magApiUserResponse['status_msg'] = 'Error Occurred while Creating Api Users in Magento '.$ex->getMessage();
        }

        return $magApiUserResponse;


    }

    public function mbizSaveInstanceDetailsAction()
    {
        $postData = $this->getRequest()->getPost();
        Mage::Log($postData,null,'saveinstance.log');
        if(!empty($postData)) {
            $siteUrl = $postData['mbiz_instance_url'];
            $siteUname = $postData['mbiz_username'];
            $sitePwd = $postData['mbiz_password'];
            $siteType = 2;



            $configData = new Mage_Core_Model_Config();
            $configData->saveConfig('connector/installation/mbiz_sitetype',$siteType, 'default', 0);
            $configData->saveConfig('connector/installation/mbiz_sitename',$siteUrl, 'default', 0);
            $configData->saveConfig('connector/installation/mbiz_username',$siteUname, 'default', 0);
            $configData->saveConfig('connector/installation/mbiz_password',$sitePwd, 'default', 0);
            // $configData->saveConfig('connector/installation/mage_url',$siteUrl, 'default', 0);

            Mage::helper('microbiz_connector')->RemoveCaching();

            //$adminUrl = Mage::helper('adminhtml')->getUrl('connector/adminhtml_connector/mbizinstallsignin/');
            $baseUrl = Mage::getBaseUrl();

            $redirectUrl = $baseUrl.'connector/adminhtml_connector/mbizinstallsignin/';

            //Mage::app()->getResponse()->setRedirect($redirectUrl);
            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("connector/adminhtml_connector/mbizinstallsignin/"));
        }

    }

}
