<?php
//version 118
class Microbiz_Connector_Model_Order_Observer
{

    /**
     * Event after save shipping method
     * Setting the shipping form data into quote object
     * @author KT097
     *
     */
    public function saveShippingMethod($evt){

        $request = $evt->getRequest();
        $quote = $evt->getQuote();
        $shippingMethod = $request->getParam('shipping_method',false);
        $pickup = $request->getParam($shippingMethod.'_shipping_pickup',false);
        $pickup['method'] = $shippingMethod;
        $quote_id = $quote->getId();
        $data = array($quote_id => $pickup);
        if($pickup){
            if($shippingMethod == 'mbizdelevery_mbizdelevery') {
                $deliveryData = Mage::getSingleton('checkout/session')->getDelivery();
                Mage::getSingleton('checkout/session')->setDelivery($deliveryData);
                Mage::getSingleton('checkout/session')->setShippingWindow(3);
            }
            if($shippingMethod == 'instorepickup_instorepickup') {
                Mage::getSingleton('checkout/session')->setPickup($data);
                Mage::getSingleton('checkout/session')->setShippingWindow(2);
            }
        }

    }
    /**
     * Event after save order after
     * saving the custom shipping form details into database using model
     * @author KT097
     *
     */
    public function saveOrderAfter($evt){
        $order = $evt->getOrder();
        $quote = $evt->getQuote();
        $quote_id = $quote->getId();
        $orderType = Mage::getSingleton('checkout/session')->getShippingWindow();

        if($orderType == 2) {

            $pickup = Mage::getSingleton('checkout/session')->getPickup();

            if(isset($pickup[$quote_id])){
                $data['store'] = $pickup[$quote_id]['store'];
                $data['window_type'] = $pickup[$quote_id]['window_type'];
                $data['delivery_window'] = $pickup[$quote_id]['deliveryWindow'];
                // $data['zone_id'] = $pickup[$quote_id]['shippingZone'];
                $data['zone_id'] = '';
                $data['delivery_date'] = $pickup[$quote_id]['date'];
                $data['note'] = $pickup[$quote_id]['note'];
                $data['method'] = $pickup[$quote_id]['method'];
                $data['order_id'] = $order->getId();
                $pickupModel = Mage::getModel('pickup/pickup');
                $pickupModel->setData($data);
                $pickupModel->save();
            }
        }
        if($orderType == 3) {
            $delivery = Mage::getSingleton('checkout/session')->getDelivery();

            if(isset($delivery[$quote_id])){
                $data['store'] = $delivery[$quote_id]['store'];
                $data['window_type'] = $delivery[$quote_id]['window_type'];
                $data['delivery_window'] = $delivery[$quote_id]['deliveryWindow'];
                $data['delivery_date'] = $delivery[$quote_id]['date'];
                $data['zone_id'] = $delivery[$quote_id]['shippingZone'];
                $data['note'] = $delivery[$quote_id]['note'];
                $data['method'] = $delivery[$quote_id]['method'];
                $data['order_id'] = $order->getId();
                $pickupModel = Mage::getModel('pickup/pickup');
                $pickupModel->setData($data);
                $pickupModel->save();
            }
        }
        //unset instorepickupdata
        Mage::getSingleton('checkout/session')->unsPickupData();
        Mage::getSingleton('checkout/session')->unsShippingWindow();
        Mage::getSingleton('checkout/session')->unsCustomQuoteId();
        Mage::getSingleton('checkout/session')->unsPickup();
        //unset delivery related data
        Mage::getSingleton('core/session')->unsDeleveryData();
        Mage::getSingleton('checkout/session')->unsDelevery();
        Mage::getSingleton('checkout/session')->unsLocalDeliveryShipping();
        Mage::getModel('Microbiz_Connector_Model_Observer')->onOrderSave($order);

        Mage::getSingleton('core/session')->unsCreditData();
        Mage::getSingleton('checkout/session')->unsCreditData();
        Mage::getSingleton('checkout/session')->unsCreditFinalData();
        Mage::getSingleton('checkout/session')->unsCreditDiscountData();
        Mage::getSingleton('checkout/session')->unsGiftBuyData();
    }



    /**
     * Event for loading order in Admin
     * retriving the custom shipping form details based on orderid and save  into order object
     * @author KT097
     *
     */
    public function loadOrderAfter($evt){
        $order = $evt->getOrder();
        if($order->getId()){
            $order_id = $order->getId();
            $pickupCollection = Mage::getModel('pickup/pickup')->getCollection();
            $pickupCollection->addFieldToFilter('order_id',$order_id);
            $pickup = $pickupCollection->getFirstItem();
            $order->setPickupObject($pickup);
        }
    }
    /**
     * Event for loading quote after in frontend
     * retriving the custom shipping form details based on quote info previously saved and set to quote object
     * @author KT097
     *
     */
    public function loadQuoteAfter($evt)
    {
        $quote = $evt->getQuote();
        if($quote->getId()){
            $quote_id = $quote->getId();
            $pickup = Mage::getSingleton('checkout/session')->getPickup();

            if(isset($pickup[$quote_id])){
                $data = $pickup[$quote_id];
                $quote->setPickupData($data);
            }
            $delivery = Mage::getSingleton('checkout/session')->getDelivery();
            if(isset($delivery[$quote_id])){
                $deliverydata = $delivery[$quote_id];
                $quote->setDeliveryData($deliverydata);
            }
        }
    }
    public function mbizOnMultiShipShippingSave($observer){

        $shippingData = $observer->getRequest()->getPost();
        Mage::getSingleton('checkout/session')->setUsedShippingMethods($shippingData['shipping_method']);

    }
    /**
     *
     * @author - KT-174
     * @description - This method is used to get all the orderIds and also get the MultiShip data from the session
     * and used to find the order id from the order increment id and sync to sync table mbiz_order_credit_usage_history
     * with order id, credit no, credit amount, credit type and credit status.
     */
    public function mbizOnMultiShipOrderSave()
    {

        $orderIncrementIds = array();
        $pickup = Mage::getSingleton('checkout/session')->getPickup();
        $delivery = Mage::getSingleton('checkout/session')->getDelivery();
        $quote_id = Mage::getSingleton('checkout/session')->getCustomQuoteId();
        $usedShippingMethodsInOrder = Mage::getSingleton('checkout/session')->getUsedShippingMethods();
        foreach($pickup[$quote_id] as $addressKey => $pickupdata)
        {
            if(is_array($pickupdata) && $usedShippingMethodsInOrder[$addressKey] == $pickupdata['method'])
            {

                $orderIncrementId = $pickupdata['order_id'];
                $orderIncrementIds[] = $orderIncrementId;
                $orderModel = Mage::getModel('sales/order')->load($orderIncrementId, 'increment_id');
                $pickupdata['order_id'] = $orderModel->getId();
                $pickupModel = Mage::getModel('pickup/pickup');
                $pickupModel->setData($pickupdata);
                $pickupModel->save();
            }
        }

        foreach($delivery[$quote_id] as $addressKey => $deliverydata)
        {
            if(is_array($deliverydata)  && $usedShippingMethodsInOrder[$addressKey] == $deliverydata['method'])
            {

                $orderIncrementId = $deliverydata['order_id'];
                $orderIncrementIds[] = $orderIncrementId;
                $orderModel = Mage::getModel('sales/order')->load($orderIncrementId, 'increment_id');
                $deliverydata['order_id'] = $orderModel->getId();
                $pickupModel = Mage::getModel('pickup/pickup');
                $pickupModel->setData($deliverydata);
                $pickupModel->save();
            }
        }
        Mage::getSingleton('checkout/session')->unsUsedShippingMethods();
        //KT174 Code
        /**
         *
         * @author - KT-174
         * @description - This method is used to get all the orderIds and also get the MultiShip data from the session
         * and used to find the order id from the order increment id and sync to sync table mbiz_order_credit_usage_history
         * with order id, credit no, credit amount, credit type and credit status.
         */
        $arrMultiShipCreditData = Mage::getSingleton('checkout/session')->getNewMultiData();
        $arrMultiShipCreditData = unserialize($arrMultiShipCreditData);
        $isDiscountAvailable=0;
        if(count($arrMultiShipCreditData)>0)
        {

            foreach($arrMultiShipCreditData as $data)
            {
                if(array_key_exists('credit_id',$data))
                {
                    $isDiscountAvailable=1;
                }
            }
            if($isDiscountAvailable==1)
            {
                foreach($arrMultiShipCreditData as $data)
                {
                    foreach($data as $childData)
                    {

                        $orderIncrementId = $childData['order_increment_id'];
                        $orderModel = Mage::getModel('sales/order')->load($orderIncrementId, 'increment_id');
                        $orderId = $orderModel->getId();

                        $arrCreditHistoryData = array();
                        $arrCreditHistoryData['credit_id'] = $childData['credit_no'];
                        $arrCreditHistoryData['credit_amt'] = $childData['credit_amt'];
                        $arrCreditHistoryData['order_id'] = $orderId;
                        $arrCreditHistoryData['type'] = $childData['credit_type'];
                        $arrCreditHistoryData['status']=1;

                        Mage::getModel('mbizcreditusage/mbizcreditusage')
                            ->addData($arrCreditHistoryData)
                            ->save();

                    }
                }
            }

        }


        /*This code is added by KT174 to save the sale gift card data.*/

        $arrMultiShipBuyGiftData = Mage::getSingleton('checkout/session')->getNewGiftBuyData();
        $arrMultiShipBuyGiftData = unserialize($arrMultiShipBuyGiftData);
        Mage::log("updateing the item price if the order is from multishipping",null,'multiorder.log');
        Mage::log($arrMultiShipBuyGiftData,null,'multiorder.log');
        $isGiftSaleExists=0;
        Mage::log("came to on multi ship order sve",null,'ordersync.log');
        Mage::log($arrMultiShipBuyGiftData,null,'ordersync.log');
        if(!empty($arrMultiShipBuyGiftData))
        {
            foreach($arrMultiShipBuyGiftData as $key=>$data)
            {
                $orderModel = Mage::getModel('sales/order')->load($data['increment_id'],'increment_id');
                $orderId = $orderModel->getId();
                Mage::log($orderId,null,'multiorder.log');

                /*Updating the any amount giftcard item price in order items table to show correctly in admin order view*/

                $arrGiftBuyData = Mage::getSingleton('checkout/session')->getNewGiftBuyData();
                $arrGiftBuyData = unserialize($arrGiftBuyData);

                Mage::log($arrGiftBuyData,null,'multiorder.log');


                if(count($arrGiftBuyData)>0)
                {
                    $items = $orderModel->getAllItems();
                    foreach($items as $item)
                    {
                        $opts=$item->getData('product_options');
                        $opts=unserialize($opts);
                        $arrAppliedOptions = $opts['options'][0];

                        Mage::log($arrAppliedOptions,null,'multiorder.log');
                        if($arrAppliedOptions['label']=='Gift Card' && $arrAppliedOptions['value']=='Any Amount')
                        {
                            $customPrice =0;
                            foreach($arrGiftBuyData as $giftdata)
                            {
                                if($giftdata['gcd_type']==2)
                                {
                                    $customPrice = $customPrice + $giftdata['gcd_amt'];
                                }
                            }
                            Mage::log("custom price".$customPrice,null,'multiorder.log');
                            $itemModel = Mage::getModel('sales/order_item')->load($item->getId());
                            $itemModel->setPrice($customPrice);
                            $itemModel->setBasePrice($customPrice);
                            $itemModel->setOriginalPrice($customPrice);
                            $itemModel->setBaseOriginalPrice($customPrice);
                            $itemModel->setPriceInclTax($customPrice);
                            $itemModel->setBasePriceInclTax($customPrice);
                            $itemModel->setRowTotal($customPrice*$item->getQtyOrdered());
                            $itemModel->setBaseRowTotal($customPrice*$item->getQtyOrdered());

                            $itemModel->save();

                        }

                        /*Updating the session object with item ids starts here.*/

                        Mage::log("order item id updating starts here",null,'multiorder.log');
                        $quoteItemId = $item->getQuoteItemId();
                        $quoteOrderItemId = $item->getItemId();
                        $quoteOrderId = $item->getOrderId();

                        Mage::log('qutoe item id=='.$quoteItemId,null,'multiorder.log');
                        Mage::log('qutoe order item id=='.$quoteOrderItemId,null,'multiorder.log');
                        Mage::log('data[quote_item_id]d=='.$data['quote_item_id'],null,'multiorder.log');

                        if($data['quote_item_id']==$quoteItemId){
                            Mage::log('if satisfied.',null,'multiorder.log');
                            $arrMultiShipBuyGiftData[$key]['order_item_id'] = $quoteOrderItemId;
                            $arrMultiShipBuyGiftData[$key]['order_id'] = $quoteOrderId;
                        }

                        /*Updating the session object with item ids ends here.*/

                    }

                }
                $orderModel->save();
                /*end of updating the custom price value in multishipping order*/

            }

            Mage::getSingleton('checkout/session')->unsNewGiftBuyData();
            Mage::getSingleton('checkout/session')->setNewGiftBuyData(serialize($arrMultiShipBuyGiftData));

            Mage::log("after updating with Item Id",null,'multiorder.log');
            $arrGiftBuyData = Mage::getSingleton('checkout/session')->getNewGiftBuyData();
            $arrGiftBuyData = unserialize($arrGiftBuyData);
            Mage::log($arrGiftBuyData,null,'multiorder.log');


            foreach($arrGiftBuyData as $data)
            {


                /*for($i=1;$i<=$data['qty'];$i++)
                {*/
                $quote_item_id = $data['quote_item_id'];
                $saveGiftInfo = array();
                $saveGiftInfo['order_id'] = $data['order_id'];
                $saveGiftInfo['gcd_amt'] = $data['gcd_amt'];
                $saveGiftInfo['gcd_type'] = $data['gcd_type'];
                $saveGiftInfo['gcd_unique_num'] = $data['gcd_unique_num'];
                $saveGiftInfo['gcd_pin'] = $data['gcd_pin'];
                $saveGiftInfo['order_item_id'] = $data['order_item_id'];


                Mage::getModel('mbizgiftcardsale/mbizgiftcardsale')->setData($saveGiftInfo)->save();
                //}


            }


        }




        Mage::getSingleton('checkout/session')->unsMultiData();
        Mage::getSingleton('checkout/session')->unsNewMultiData();
        Mage::getSingleton('core/session')->unsCreditData();
        Mage::getSingleton('checkout/session')->unsCreditData();
        Mage::getSingleton('checkout/session')->unsCreditFinalData();
        Mage::getSingleton('checkout/session')->unsCreditDiscountData();
        Mage::getSingleton('checkout/session')->unsGiftBuyData();
        Mage::getSingleton('checkout/session')->unsNewGiftBuyData();

        //KT174 End
        $orderIncrementIds = array_unique($orderIncrementIds);
        foreach($orderIncrementIds as $orderIncrementId) {
            $orderModel = Mage::getModel('sales/order')->load($orderIncrementId, 'increment_id');
            Mage::getModel('Microbiz_Connector_Model_Observer')->onOrderSave($orderModel);
        }

        //unset instorepickupdata
        Mage::getSingleton('checkout/session')->unsPickupData();
        Mage::getSingleton('checkout/session')->unsCustomQuoteId();
        Mage::getSingleton('checkout/session')->unsPickup();
        //unset delivery related data
        Mage::getSingleton('core/session')->unsDeleveryData();
        Mage::getSingleton('checkout/session')->unsDelevery();
        Mage::getSingleton('checkout/session')->unsLocalDeliveryShipping();


    }

    /**
     * @$observer - contains order details and address details.
     * @author KT-174
     * @description - This method will be rendered for multiShipping checkout and in this method we will get the
     * order increment id and order address id and also we will get the multiShip data from the session and based on
     * the address id we will append the order increment id to multiShip data.
     */
    public function mbizOnMultiShipOrderCreate($observer)
    {

        Mage::log("came to on multi ship order create",null,'ordersync.log');

        $orderIncrementId = $observer->getEvent()->getOrder()->getIncrementId();
        $orderAddressId = $observer->getEvent()->getAddress()->getId();

        $isVirtual = 0;
        $isVirtual = $observer->getEvent()->getOrder()->getIsVirtual();

        $arrMultiShipCreditData = Mage::getSingleton('checkout/session')->getMultiData();
        $arrMultiShipCreditData = unserialize($arrMultiShipCreditData);

        $arrMultiShipBuyGiftData = Mage::getSingleton('checkout/session')->getGiftBuyData();
        $arrMultiShipBuyGiftData = unserialize($arrMultiShipBuyGiftData);

        if(empty($arrMultiShipBuyGiftData))
        {
            $arrMultiShipBuyGiftData = Mage::getSingleton('checkout/session')->getNewGiftBuyData();
            $arrMultiShipBuyGiftData = unserialize($arrMultiShipBuyGiftData);
        }
        Mage::log("came to multishipordercreate",null,'multiorder.log');
        Mage::log($orderIncrementId,null,'multiorder.log');


        Mage::log("came to on multi ship order create",null,'ordersync.log');
        Mage::log($arrMultiShipBuyGiftData,null,'ordersync.log');
        if(count($arrMultiShipBuyGiftData)>0)
        {
            Mage::log("came to inside ",null,'multiorder.log');
            foreach($arrMultiShipBuyGiftData as $key=>$data)
            {
                if($data['address_id']==$orderAddressId){
                    $arrMultiShipBuyGiftData[$key]['increment_id'] = $orderIncrementId;
                }
            }
            Mage::getSingleton('checkout/session')->unsGiftBuyData();
            //Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($arrMultiShipBuyGiftData));
            Mage::getSingleton('checkout/session')->setNewGiftBuyData(serialize($arrMultiShipBuyGiftData));
        }

        Mage::log("came to multishipordercreate after update",null,'multiorder.log');
        Mage::log($arrMultiShipBuyGiftData,null,'multiorder.log');



        /**
         * @$observer - contains order details and address details.
         * @author KT-174
         * @description - This method will be rendered for multiShipping checkout and in this method we will get the
         * order increment id and order address id and also we will get the multiShip data from the session and based on
         * the address id we will append the order increment id to multiShip data.
         */




        if(count($arrMultiShipCreditData))
        {
            foreach($arrMultiShipCreditData as $key=>$data)
            {
                if($key==$orderAddressId)
                {
                    foreach($data as $k=>$childData)
                    {
                        $arrMultiShipCreditData[$key][$k]['order_increment_id'] = $orderIncrementId;

                    }
                }
            }
            Mage::getSingleton('checkout/session')->unsMultiData();
            Mage::getSingleton('checkout/session')->setNewMultiData(serialize($arrMultiShipCreditData));




        }

        $quote_id = Mage::getSingleton('checkout/session')->getCustomQuoteId();


        $pickup = Mage::getSingleton('checkout/session')->getPickup();

        if(isset($pickup[$quote_id][$orderAddressId])){
            $pickupAddressData = $pickup[$quote_id][$orderAddressId];
            $data = array();
            $data['store'] = $pickupAddressData['store'];
            $data['window_type'] = 2;
            $data['delivery_window'] = $pickupAddressData['deliveryWindow'];
            //$data['zone_id'] = $pickupAddressData['shippingZone'];
            $data['zone_id'] ='';
            $data['delivery_date'] = $pickupAddressData['date'];
            $data['note'] =$pickupAddressData['note'];
            $data['method'] ='instorepickup_instorepickup';
            $data['order_id'] = $orderIncrementId;
            $pickup[$quote_id][$orderAddressId] = $data;
            Mage::getSingleton('checkout/session')->setPickup($pickup);
            /* $pickupModel = Mage::getModel('pickup/pickup');
             $pickupModel->setData($data);
             $pickupModel->save();*/
        }
        $delivery = Mage::getSingleton('checkout/session')->getDelivery();
        if(isset($delivery[$quote_id][$orderAddressId])){
            $deliverydata = $delivery[$quote_id][$orderAddressId];
            $data = array();
            $data['store'] = $deliverydata['store'];
            $data['window_type'] = 3;
            $data['delivery_window'] = $deliverydata['deliveryWindow'];
            $data['zone_id'] = $deliverydata['shippingZone'];

            $data['delivery_date'] = $deliverydata['date'];
            $data['note'] =$deliverydata['note'];
            $data['order_id'] = $orderIncrementId;
            $data['method'] ='mbizdelevery_mbizdelevery';
            $delivery[$quote_id][$orderAddressId] = $data;
            Mage::getSingleton('checkout/session')->setDelivery($delivery);
            /*$pickupModel = Mage::getModel('pickup/pickup');
            $pickupModel->setData($data);
            $pickupModel->save();*/
        }


    }

    public function onAdminOrderSave($observer) {
        $order = $observer->getEvent()->getOrder();
        Mage::getModel('Microbiz_Connector_Model_Observer')->onOrderSave($order);
    }



    /**
     * @$observer - contains the creditMemo details
     * @author KT-174
     * @description This method is used the capture the CreditMemo details and get the order id from the CreditMemo
     * and check whether any store credit or gift card applied to the order, if applied update the sync tables.
     */
    public function mbizOnOrderRefunded($observer)
    {
        Mage::log("came to mbizOnOrderRefunded",null,'refundorder.log');
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $orderId = $creditMemo->getOrder()->getId();
        $customerId = $creditMemo->getOrder()->getCustomerId();

        $creditUsageModel = Mage::getModel('mbizcreditusage/mbizcreditusage')
            ->getCollection()
            ->addFieldToFilter('order_id',$orderId)->getData();
        if(count($creditUsageModel)>0)
        {
            $x=0;
            $y=0;
            $arrRefundCreditAmt = array();
            $checkout = Mage::getSingleton('checkout/session')->getQuote()->getData();
            $relationData = Mage::getModel('mbizcustomer/mbizcustomer')
                ->getCollection()
                ->addFieldToFilter('magento_id', $customerId)
                ->setOrder('id', 'asc')
                ->getFirstItem();
            foreach($creditUsageModel as $data)
            {
                try {
                    $creditModel = Mage::getModel('mbizcreditusage/mbizcreditusage')->load($data['id']);
                    $creditModel->setStatus(2);
                    $creditModel->setId($data['id'])->save();
                    if($data['type']==1) // 1 means store credit
                    {
                        $arrRefundCreditAmt['storecredits'][$x]['scr_unique_num'] = $data['credit_id'];
                        $arrRefundCreditAmt['storecredits'][$x]['customer_id'] = $relationData['mbiz_id'];
                        $arrRefundCreditAmt['storecredits'][$x]['applied_amount'] = $data['credit_amt'];
                        $arrRefundCreditAmt['storecredits'][$x]['movement_indigator'] = '+';
                        $x++;
                    }
                    else{
                        $arrRefundCreditAmt['giftcards'][$y]['gcd_unique_num'] = $data['credit_id'];
                        $arrRefundCreditAmt['giftcards'][$y]['applied_amount'] = $data['credit_amt'];
                        $arrRefundCreditAmt['giftcards'][$y]['movement_indigator'] = '+';
                        $y++;
                    }
                }
                catch(Exception $e)
                {
                    Mage::logException($e->getMessage());
                }
            }
            Mage::log($arrRefundCreditAmt);
            if(count($arrRefundCreditAmt)>0)
            {
                $apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
                $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
                $url = $url . '/index.php/api/processPayments'; // prepare url for the rest call
                $api_user = $apiInformation['api_user'];
                $api_key = $apiInformation['api_key'];
                $postData = json_encode($arrRefundCreditAmt);

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
                        curl_setopt($handle, CURLOPT_POSTFIELDS, $postData);
                        break;

                    case 'PUT':
                        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');	// create product request.
                        curl_setopt($handle, CURLOPT_POSTFIELDS, $postData);
                        break;

                    case 'DELETE':
                        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                        break;
                }

                $response = curl_exec($handle);	// send curl request to microbiz

                $response=json_decode($response,true);
                Mage::log($response);



            }
        }





        return true;


    }

    /**
     * $observer contains the quote item details
     *@author KT174
     * @description This method is used to check whether any Gift Card Product is added to the cart and check whether
     * it has any Variable amount and update the price with the customprice as we did in the quote_item.
     */
    public function mbizUpdateGiftCardPrice($observer)
    {
        /*
        foreach($observer->getQuote()->getAllItems() as $item)
        {

            if($item->getisVirtual())
            {

                $this->updateVirtualItem($item);

            }

        }*/
    }

    public function updateVirtualItem($item) {

        $productOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
        $arrAppliedOptions = $productOptions['options'];
        if(count($arrAppliedOptions)>0)
        {
            foreach($arrAppliedOptions as $option)
            {
                if($option['label']=='Gift Card' && $option['value']=='Any Amount')
                {


                    $customPrice = $item->getPrice();
                    $itemId = $item->getId();
                    $arrAddressItemData = Mage::getModel('sales/quote_address_item')->getCollection()->addFieldToFilter('quote_item_id',$itemId)->getFirstItem()->getData();
                    $addressItemId = $arrAddressItemData['address_item_id'];
                    $addressItemModel = Mage::getModel('sales/quote_address_item');
                    $addressItemModel->setPrice($customPrice);
                    $addressItemModel->setBasePrice($customPrice);
                    $addressItemModel->setId($addressItemId);
                    $addressItemModel->save();

                    $afterUpdate = Mage::getModel('sales/quote_address_item')->getCollection()->addFieldToFilter('quote_item_id',$itemId)->getFirstItem()->getData();

                }
            }
        }
    }

    /**
     * $observer contains the order details.
     * @author KT174
     * @description This method is used to update the order status to the sync records whenever the order status changed
     */
    public function mbizOnOrderStatusChange($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $state = $order->getState();

        $adminsession = Mage::getSingleton('admin/session', array('name'=>'adminhtml'));

        //Mage::log("came to onorderstatuschange",null,'ordersync.log');
        $orderHeaderModel = Mage::getModel('saleorderheader/saleorderheader')->getCollection()->addFieldToFilter('order_id',$order->getId())->getData();
        if($state!='new' && $orderHeaderModel) {
            //Mage::log("came to from admin",null,'ordersync.log');
            Mage::getModel('Microbiz_Connector_Model_Observer')->onOrderSave($order);
        }
        else if($state == 'new') {
            Mage::getModel('Microbiz_Connector_Model_Observer')->onOrderSave($order);
        }

        /*$state = $order->getState();
        $status = $order->getStatus();
        Mage::log("came to on order status change",null,'ordersync.log');
        $orderId = $order->getId();
        if($orderId)
        {
            $orderHeaderModel = Mage::getModel('saleorderheader/saleorderheader')->getCollection()
                                    ->addFieldToFilter('order_id',$orderId)->getFirstItem()->getData();

            if(count($orderHeaderModel)>0)
            {
                $id = $orderHeaderModel['id'];

                $orderHeaderModel = Mage::getModel('saleorderheader/saleorderheader');
                $orderHeaderModel->setStatusId($status);
                $orderHeaderModel->setId($id);
                $orderHeaderModel->save();

            }
        }*/


    }

    /**
     * @param Varien_Event_Observer $observer
     * @author KT174
     * @description This method is used to check whether the order contains any giftcard product. if exists it will send
     * availability request to microbiz and if available it will update the session object of giftcard sale if not exists
     * throws an error but still the product will be added to cart.-
     */
    public function mbizReorderToQuote(Varien_Event_Observer $observer)
    {

        $action = Mage::app()->getFrontController()->getAction();
        $actionName = $action->getFullActionName();
        //Mage::log("mbiz full action name ",null,'reorder.log');
        //Mage::log($actionName);
        if ($actionName == 'sales_order_reorder')
        {
            $order = Mage::registry('current_order');
            $items = $order->getItemsCollection();
            $orderId = $order->getId();
            //echo $orderId;
            //Mage::log("mbiz full order id name ".$orderId,null,'reorder.log');
            $sku = Mage::getStoreConfig('connector/settings/giftcardsku');

            //echo "<pre>";
            $reorderItems = array();
            $i=0;
            foreach($items as $item) {
                if($item->getSku()==$sku){
                    $registeredItems = Mage::registry('reordered_items_data');
                    if(empty($registeredItems)) {
                        $opts=$item->getData('product_options');
                        $opts=unserialize($opts);
                        $productOptions = $opts['options'][0];
                        $optionId = $productOptions['option_id'];
                        $optionValId = $productOptions['option_value'];
                        if($productOptions['label']=='Gift Card' && $productOptions['value']=='Any Amount')
                        {
                            $cardType=2;
                        }
                        else {
                            $cardType=1;
                        }
                        $reorderItems[$i]['card_type'] = $cardType;
                        $reorderItems[$i]['card_amount'] = $item->getPrice();
                        $i++;
                    }

                }

            }
            if(count($reorderItems)>0)
            {
                Mage::register('reordered_items_data',$reorderItems);
            }
            //print_r(Mage::registry('reordered_items_data'));


            //Mage::log("mbiz mbizReorderToQuote");
            $quoteItem = $observer->getEvent()->getQuoteItem();
            $quoteData = $quoteItem->getData();
            $quoteId = $quoteData['quote_id'];
            $product = $quoteItem->getProduct();

            //Mage::log($quoteId);
            //Mage::log($quoteData);



            $sku = Mage::getStoreConfig('connector/settings/giftcardsku');
            $productSku = $product->getSku();
            //echo $productSku."<br>";
            //echo $product->getPrice();
            //print_r(Mage::registry('reordered_items_data'));

            if($sku==$productSku)
            {
                $customOptions = $product->getTypeInstance(true)->getOrderOptions($product);
                //print_r($customOptions);
                $orderedQty = $customOptions['info_buyRequest']['qty'];
                //echo $orderedQty;exit;
                //print_r(Mage::registry('reordered_items_data'));
                $orderOptionId = $customOptions['options'][0]['option_id'];
                $orderOptionValId = $customOptions['options'][0]['option_value'];
                $product = Mage::getModel('catalog/product');
                $productId = $product->getIdBySku($sku);
                $product->load($productId);

                $productAvailOptions = $product->getOptions();
                if (!empty($productAvailOptions)) {

                    foreach ($productAvailOptions as $opt) {
                        $currentOptionId = $opt->getId();

                        if($orderOptionId==$currentOptionId)
                        {
                            $values = $opt->getValues();
                            foreach ($values as $v) {
                                $curOptValId = $v->getId();
                                if($curOptValId==$orderOptionValId)
                                {
                                    $customPriceFixed = $v->getPrice();
                                    $isGiftCardAvailable =1;
                                }
                            }
                        }


                    }
                }


                if($customOptions['options'][0]['value']=='Any Amount') //Any amount
                {
                    $reorderedItems = Mage::registry('reordered_items_data');
                    if(!empty($reorderedItems))
                    {
                        foreach($reorderedItems as $reItems)
                        {
                            if($reItems['card_type']==2)
                            {
                                $customPrice = $reItems['card_amount'];
                                $giftCardType = 2;
                            }

                        }
                    }

                }
                else { //Fixed amount
                    $reorderedItems = Mage::registry('reordered_items_data');
                    if(!empty($reorderedItems))
                    {
                        foreach($reorderedItems as $reItems)
                        {
                            if($reItems['card_type']==1)
                            {
                                $customPrice = $customPriceFixed;
                                $giftCardType = 1;
                            }

                        }
                    }
                }
                //echo "produc price ".$product->getPrice();
                //echo $customPrice;echo $giftCardType;
                //Mage::log($customPrice."custom price",null,'reorder.log');
                //Mage::log($giftCardType."giftcard Type",null,'reorder.log');

                if($customPrice>0)
                {
                    $apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
                    $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
                    $queryString = '?gcd_amt='.$customPrice.'&gcd_type='.$giftCardType;

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

                    switch ($method) {
                        case 'GET':
                            break;
                    }

                    $response = curl_exec($handle);	// send curl request to microbiz

                    $response=json_decode($response,true);
                    //Mage::log($response,null,'reorder.log');

                    $code = curl_getinfo($handle);
                    if($code['http_code']==200)
                    {
                        //$response['gcd_unique_num']='';
                        if($response['gcd_unique_num']!='' && $response['status_msg']=='')
                        {

                            if($giftCardType==1)
                            {
                                for($i=1;$i<=$orderedQty;$i++)
                                {
                                    $giftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                                    $giftBuyData = unserialize($giftBuyData);
                                    //Mage::log("previous session object data");
                                    //Mage::log($giftBuyData);
                                    if(!empty($giftBuyData))
                                    {
                                        $key = count($giftBuyData);
                                        if($key>0)
                                        {
                                            $giftBuyData[$key]['gcd_amt']=$customPrice;
                                            $giftBuyData[$key]['gcd_type']=$giftCardType;
                                            $giftBuyData[$key]['gcd_unique_num']=$response['gcd_unique_num'];

                                        }

                                        Mage::getSingleton('checkout/session')->unsGiftBuyData();
                                        Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($giftBuyData));
                                    }
                                    else
                                    {
                                        $newGiftBuyData = array();
                                        $newGiftBuyData[0]['gcd_amt'] = $customPrice;
                                        $newGiftBuyData[0]['gcd_type']=$giftCardType;
                                        $newGiftBuyData[0]['gcd_unique_num']=$response['gcd_unique_num'];
                                        Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($newGiftBuyData));
                                    }
                                }
                            }
                            else {
                                //get the any amount giftcard details.
                                $oldAmounts =  Mage::getModel('mbizgiftcardsale/mbizgiftcardsale')->getCollection();
                                $oldAmounts = $oldAmounts->addFieldToFilter('order_id',$orderId)->addFieldToFilter('gcd_type',2)->setOrder('id','asc')->getData();
                                //Mage::log("old amounts");
                                //Mage::log($oldAmounts);
                                //echo $orderId."---".$orderedQty;

                                if(!empty($oldAmounts))
                                {
                                    foreach($oldAmounts as $amount)
                                    {
                                        $giftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                                        $giftBuyData = unserialize($giftBuyData);
                                        if(!empty($giftBuyData))
                                        {
                                            $key = count($giftBuyData);
                                            if($key>0)
                                            {
                                                $giftBuyData[$key]['gcd_amt']=$amount['gcd_amt'];
                                                $giftBuyData[$key]['gcd_type']=$giftCardType;
                                                $giftBuyData[$key]['gcd_unique_num']=$response['gcd_unique_num'];

                                            }

                                            Mage::getSingleton('checkout/session')->unsGiftBuyData();
                                            Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($giftBuyData));
                                        }
                                        else
                                        {
                                            $newGiftBuyData = array();
                                            $newGiftBuyData[0]['gcd_amt'] = $amount['gcd_amt'];
                                            $newGiftBuyData[0]['gcd_type']=$giftCardType;
                                            $newGiftBuyData[0]['gcd_unique_num']=$response['gcd_unique_num'];
                                            Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($newGiftBuyData));
                                        }
                                    }
                                }
                            }

                            // }
                            $arrGiftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                            $arrGiftBuyData = unserialize($arrGiftBuyData);

                            //print_r($arrGiftBuyData);

                        }
                        else {
                            $message = 'No GiftCards Available with the Range '.$customPrice;
                            Mage::getSingleton('core/session')->addError($message);
                        }
                    }
                    else {
                        $message = 'Unable to Validate GiftCard Ranges due to Http Error '.$code['http_code'];
                        Mage::getSingleton('core/session')->addError($message);
                    }

                    //updating the price of quote item
                    if($customPrice>0 && $giftCardType==2)
                    {
                        $arrGiftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                        $arrGiftBuyData = unserialize($arrGiftBuyData);

                        //print_r($arrGiftBuyData);
                        $newAmount = 0;
                        if(!empty($arrGiftBuyData))
                        {
                            foreach($arrGiftBuyData as $data)
                            {
                                if($data['gcd_type']==2)
                                {
                                    $newAmount = $newAmount + $data['gcd_amt'];
                                }
                            }
                            //echo $newAmount;exit;
                            $quoteItem->setCustomPrice($newAmount);
                            $quoteItem->setOriginalCustomPrice($newAmount);
                            $quoteItem->setQty(1);
                            $quoteItem->getProduct()->setIsSuperMode(true);
                        }

                    }
                }
                $arrGiftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                $arrGiftBuyData = unserialize($arrGiftBuyData);
                //Mage::log($arrGiftBuyData,null,'reorder.log');

            }

        }

    }

    public function mbizCartLoadAfter()
    {
        //Mage::log("came to cart save after event",null,'prdsync.log');


        $sku = Mage::getStoreConfig('connector/settings/giftcardsku');
        $errorMsg = array();
        $cart = Mage::getModel('checkout/cart')->getQuote();
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        foreach ($cart->getAllItems() as $item) {

            $giftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
            $giftBuyData = unserialize($giftBuyData);
            //Mage::log($giftBuyData,null,'prdsync.log');
            $productSku = $item->getProduct()->getSku();
            //Mage::log($productSku,null,'prdsync.log');
            $itemQty = $item->getQty();
            //Mage::log($itemQty,null,'prdsync.log');
            if($sku==$productSku)
            {
                $customOptions = $item->getProduct()
                    ->getTypeInstance(true)
                    ->getOrderOptions($item->getProduct());
                $productPrice = $item->getPrice();
                //Mage::log($productPrice,null,'prdsync.log');
                if($customOptions['options'][0]['value']=='Any Amount')
                {
                    //Mage::log("Any amount",null,'prdsync.log');
                    $giftCardType=2;
                    $giftCardNum = $this->checkGcdExists($productPrice,$giftBuyData,$giftCardType);
                    if($giftCardNum)
                    {
                        $existsQty = $this->getExistsQty($productPrice,$giftBuyData,$giftCardType);
                        $newQty = $itemQty-$existsQty;
                        $newQty = 1;
                        $existsAmts = 0;
                        //price updation for any amount.
                        if(!empty($giftBuyData))
                        {
                            foreach($giftBuyData as $data)
                            {
                                if($data['gcd_type']==2)
                                {
                                    $existsAmts = $existsAmts + $data['gcd_amt'];
                                }

                            }

                        }

                        $updProdPrice = $productPrice-$existsAmts;

                        if($updProdPrice>0)
                        {
                            for($i=1;$i<=$newQty;$i++)
                            {
                                $giftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                                $giftBuyData = unserialize($giftBuyData);
                                //Mage::log("previous session object data",null,'prdsync.log');
                                //Mage::log($giftBuyData,null,'prdsync.log');
                                if(!empty($giftBuyData))
                                {
                                    $key = count($giftBuyData);
                                    if($key>0)
                                    {
                                        $giftBuyData[$key]['gcd_amt']=$productPrice;
                                        $giftBuyData[$key]['gcd_type']=$giftCardType;
                                        $giftBuyData[$key]['gcd_unique_num']=$giftCardNum;

                                    }

                                    Mage::getSingleton('checkout/session')->unsGiftBuyData();
                                    Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($giftBuyData));
                                }
                                else
                                {
                                    $newGiftBuyData = array();
                                    $newGiftBuyData[0]['gcd_amt'] = $productPrice;
                                    $newGiftBuyData[0]['gcd_type']=$giftCardType;
                                    $newGiftBuyData[0]['gcd_unique_num']=$giftCardNum;
                                    Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($newGiftBuyData));
                                }
                            }
                        }
                    }
                    else {
                        //Mage::log("Any amount newly adding",null,'prdsync.log');
                        $itemQty = 1;
                        $response = $this->addNewGiftData($productPrice,$giftCardType,$itemQty);
                        if($response['status']=='FAIL')
                        {
                            $errorMsg['error'] = $errorMsg['error'].' '.$response['status_message'];
                        }
                    }

                    $arrGiftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                    $arrGiftBuyData = unserialize($arrGiftBuyData);
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
                else {
                    //Mage::log("Fixed amount",null,'prdsync.log');
                    $giftCardType=1;
                    $giftCardNum = $this->checkGcdExists($productPrice,$giftBuyData,$giftCardType);
                    if($giftCardNum)
                    {
                        $existsQty = $this->getExistsQty($productPrice,$giftBuyData,$giftCardType);
                        $newQty = $itemQty-$existsQty;
                        if($newQty>0)
                        {
                            for($i=1;$i<=$newQty;$i++)
                            {
                                $giftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                                $giftBuyData = unserialize($giftBuyData);
                                //Mage::log("previous session object data",null,'prdsync.log');
                                //Mage::log($giftBuyData,null,'prdsync.log');
                                if(!empty($giftBuyData))
                                {
                                    $key = count($giftBuyData);
                                    if($key>0)
                                    {
                                        $giftBuyData[$key]['gcd_amt']=$productPrice;
                                        $giftBuyData[$key]['gcd_type']=$giftCardType;
                                        $giftBuyData[$key]['gcd_unique_num']=$giftCardNum;

                                    }

                                    Mage::getSingleton('checkout/session')->unsGiftBuyData();
                                    Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($giftBuyData));
                                }
                                else
                                {
                                    $newGiftBuyData = array();
                                    $newGiftBuyData[0]['gcd_amt'] = $productPrice;
                                    $newGiftBuyData[0]['gcd_type']=$giftCardType;
                                    $newGiftBuyData[0]['gcd_unique_num']=$giftCardNum;
                                    Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($newGiftBuyData));
                                }
                            }
                        }
                    }
                    else {
                        //Mage::log("Fixed amount newly added",null,'prdsync.log');
                        $response = $this->addNewGiftData($productPrice,$giftCardType,$itemQty);
                        if($response['status']=='FAIL')
                        {
                            $errorMsg['error'] = $errorMsg['error'].' '.$response['status_message'];
                        }


                    }

                }

            }

        }

        if($errorMsg['error'])
        {
            Mage::getSingleton('core/session')->addError($errorMsg['error']);
        }
        $quote->save();
        $quote->setTotalsCollectedFlag(false)->collectTotals();

        $giftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
        $giftBuyData = unserialize($giftBuyData);
        //Mage::log("Came to end of the function",null,'prdsync.log');
        //Mage::log($giftBuyData,null,'prdsync.log');

    }

    public function checkGcdExists($value,$ranges,$type)
    {
        //Mage::log("came to checkGcdExists",null,'prdsync.log');
        $found = false;
        foreach ($ranges as $data) {
            if(is_array($data))
            {
                if($type==1)
                {
                    if ($data['gcd_amt'] == $value && $data['gcd_type']==$type) {
                        $found = $data['gcd_unique_num'];
                        break; // no need to loop anymore, as we have found the item => exit the loop
                    }
                }
                else {
                    if ($data['gcd_type']==$type) {
                        $found = $data['gcd_unique_num'];
                        break; // no need to loop anymore, as we have found the item => exit the loop
                    }
                }

            }

        }
        return $found;
    }

    public function getExistsQty($value,$ranges,$type)
    {
        //Mage::log("came to getExistsQty",null,'prdsync.log');
        $counter=0;
        foreach ($ranges as $data) {
            if(is_array($data))
            {
                if ($data['gcd_amt'] == $value && $data['gcd_type']==$type) {
                    $counter++;
                }
            }

        }
        return $counter;

    }

    public function addNewGiftData($productPrice,$giftCardType,$itemQty)
    {
        //Mage::log("came to addNewGiftData",null,'prdsync.log');
        $result = array();
        $apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
        $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
        $queryString = '?gcd_amt='.$productPrice.'&gcd_type='.$giftCardType;

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

        switch ($method) {
            case 'GET':
                break;
        }

        $response = curl_exec($handle);	// send curl request to microbiz

        $response=json_decode($response,true);
        //Mage::log($response,null,'reorder.log');

        $code = curl_getinfo($handle);

        if($code['http_code']==200)
        {
            //$response['gcd_unique_num']='';
            if($response['gcd_unique_num']!='' && $response['status_msg']=='')
            {
                $giftCardNum = $response['gcd_unique_num'];
                for($i=1;$i<=$itemQty;$i++)
                {
                    $giftBuyData = Mage::getSingleton('checkout/session')->getGiftBuyData();
                    $giftBuyData = unserialize($giftBuyData);
                    //Mage::log("previous session object data");
                    //Mage::log($giftBuyData);
                    if(!empty($giftBuyData))
                    {
                        $key = count($giftBuyData);
                        if($key>0)
                        {
                            $giftBuyData[$key]['gcd_amt']=$productPrice;
                            $giftBuyData[$key]['gcd_type']=$giftCardType;
                            $giftBuyData[$key]['gcd_unique_num']=$giftCardNum;

                        }

                        Mage::getSingleton('checkout/session')->unsGiftBuyData();
                        Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($giftBuyData));
                    }
                    else
                    {
                        $newGiftBuyData = array();
                        $newGiftBuyData[0]['gcd_amt'] = $productPrice;
                        $newGiftBuyData[0]['gcd_type']=$giftCardType;
                        $newGiftBuyData[0]['gcd_unique_num']=$giftCardNum;
                        Mage::getSingleton('checkout/session')->setGiftBuyData(serialize($newGiftBuyData));
                    }
                }
                $result['status']=='SUCCESS';
            }
            else {
                $result['status']=='FAIL';
                if($response['status_msg'])
                {
                    $result['status_message']=$response['status_msg'];
                }
                else {
                    $result['status_message']='No GiftCards Available with the Range '.$productPrice;
                }


            }
        }
        else {
            $result['status']=='FAIL';
            $result['status_message']=='Unable to Validate GiftCard Product due to HTTP Error '.$code['http_code'];
        }

        return $result;

    }

    /**
     * @param $observer
     * @return $this
     * @author KT174
     * @description This method is used to pass discount amount variable to paypal variables.
     */
    public function mbizSetDiscountToPaypal($observer)
    {
        Mage::log("came to mbizsetdiscount to paypal");
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $quoteCreditDiscountData = $quote->getCreditDiscountData();
        $quoteCreditDiscountData = unserialize($quoteCreditDiscountData);
        $totalDiscount =0;
        if(count($quoteCreditDiscountData)>0)
        {
            if(is_array($quoteCreditDiscountData)) {
                foreach($quoteCreditDiscountData as $data)
                {
                    $totalDiscount+= $data['credit_amt'];

                }

                if($totalDiscount>0) {
                    $cart = $observer->getEvent()->getPaypalCart();
                    $newAmount = $totalDiscount;
                    $cart->updateTotal(Mage_Paypal_Model_Cart::TOTAL_DISCOUNT,$newAmount);
                    return $this;
                }

            }
        }
    }

    public function addResyncButton($event) {
        $block = $event->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View) {
            $orderId = $block->getRequest()->getParam('order_id');
            $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('obj_id', $orderId)->addFieldToFilter('model_name', 'Orders')->addFieldToFilter('status', array('in' => array('Pending', 'Processing')))->setOrder('header_id', 'desc')->getData();
            $orderHeaderModel = Mage::getModel('saleorderheader/saleorderheader')->getCollection()->addFieldToFilter('order_id',$orderId)->getData();

            if(!$isObjectExists && $orderHeaderModel) {
                $block->addButton('order_resync_mbiz', array(
                    'label'     => Mage::helper('sales')->__('ReSync Order To MicroBiz'),
                    'onclick'   => 'setLocation(\'' . $block->getUrl('connector/adminhtml_connectordebug/syncOrderToMicroBiz') . '\')',
                ));
            }

        }
    }

    public function mbizOrderInformation($observer){
        $template = $observer->getBlock()->getTemplate();
        if($template == 'sales/order/view/info.phtml'): // make sure its the template you want
            $event = $observer->getEvent();
            $eblock = $event->getBlock();
            $order = $eblock->getOrder();
            $orderHeaderModel = Mage::getModel('saleorderheader/saleorderheader')->getCollection()->addFieldToFilter('order_id',$order->getId())->getFirstItem()->getData();
            if($orderHeaderModel['mbiz_order_id']) {
                $transport = $observer->getTransport();
                $oldHtml = $transport->getHtml();
                $newHtml = '<div class="entry-edit">
                        <!--MicroBiz Order Details-->
                                <div class="entry-edit">
                                    <div class="entry-edit-head">
                                        <h4 class="icon-head head-microbiz-method">MicroBiz Order Information</h4>
                                    </div>
                                    <div class="fieldset">
                                        <div class="hor-scroll">
                <table cellspacing="0" class="form-list data order-tables">
                                                <tr>
                                                    <td class="label"><label>Order Id</label></td>
                                                    <td class="value">    '.$orderHeaderModel['mbiz_order_id'].'
                                                    </td>
                                                </tr>';
                $gcdModel = Mage::getModel('mbizgiftcardsale/mbizgiftcardsale')->getCollection()->addFieldToFilter('order_id',$order->getId())->getData();
                if($gcdModel) {
                    $newHtml .= '<tr><td><strong>GiftCard Details</strong></td></tr>';
                    $newHtml .= '<tr class="headings"><th>Sl.No.</th><th>Giftcard Number</th><th>Giftcard Amount</th></tr>';
                    $gcdCount = 1;
                    foreach($gcdModel as $giftCard) {
                        $newHtml .= '<tr><td>'.$gcdCount++.'</td><td>'.$giftCard['gcd_unique_num'].'</td><td>'.$giftCard['gcd_amt'].'</td></tr>';
                    }
                }
                $newHtml .= '
                </table>
                    </div>
                </div></div>
                </div><div class="clear"></div>'.$oldHtml;
                $transport->setHtml($newHtml);
            }

        endif;

        /*Code to Display MicroBiz Info in Magento Product Edit Page Starts Here.*/
        if($template == 'catalog/form/renderer/fieldset/element.phtml'):
            $elemBlock = $observer->getEvent()->getBlock();

            $productId = $elemBlock->getDataObject()->getId();
            $productModel = Mage::getSingleton('catalog/product')->load($productId);
            $currentPrdSku = $productModel->getSku();
            $giftcardSku = Mage::getStoreConfig('connector/settings/giftcardsku');
            if($currentPrdSku!=$giftcardSku) {
                $this->showMbizProductInfo($observer);
            }


        endif;
        /*Code to Display MicroBiz Info in Magento Product Edit Page Ends Here.*/

    }

    /**
     * @param $observer
     * @author KT174
     * @description This method is used to add the MicroBiz Product Info in Edit Page.
     */
    public function showMbizProductInfo($observer)
    {
        //Mage::log("came to mbizorderinfor",null,'rel.log');
        $event = $observer->getEvent();
        $eblock = $event->getBlock();

        $element = $eblock->getElement();

        $attrCode = $element->getHtmlId();
        $attrValue = $element->getValue();

        if($attrCode=='sync_status' && $attrValue==1) {

            $productId = $eblock->getDataObject()->getId();

            //check mbiz sku in product relation table.

            $relationdata = Mage::getModel('mbizproduct/mbizproduct')->getCollection()
                ->addFieldToFilter('magento_id', $productId)->setOrder('id', 'asc')
                ->getFirstItem()->getData();

            if(!empty($relationdata)) {  //product relation exists.
                $isSecure = Mage::app()->getStore()->isCurrentlySecure();
                $magBaseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, $isSecure);

                $mbizSku = $relationdata['mbiz_sku'];
                $mbizProductId = $relationdata['mbiz_id'];
                $relationId = $relationdata['id'];

                if($mbizProductId) {
                    if($mbizSku) { //microbiz sku is present in relation table
                        $transport = $observer->getTransport();
                        $oldHtml = $transport->getHtml();
                        $isSecure = Mage::app()->getStore()->isCurrentlySecure();
                        $jsUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS,$isSecure);
                        $newHtml ='<script src="'.$jsUrl.'jquerylib/jquery-1.7.2.min.js"></script>
                               <script src="'.$jsUrl.'jquerylib/noconflict.js"></script>
                               <script src="'.$jsUrl.'jquerylib/js/jquery-ui-1.10.4.custom.js"></script>
                                <link rel="stylesheet" type="text/css" href="'.$jsUrl.'jquerylib/css/ui-lightness/jquery-ui-1.10.4.custom.css" />';
                        $newHtml.= '<tr>
                                            <td class="label"><label for="mbiz_product_id">MicroBiz Product ID</label></td>
                                            <td class="value">'.$mbizProductId.'</td>
                                            <td class="scope-label"><span class="nobr"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="label"><label for="mbiz_product_sku">MicroBiz Product Sku</label></td>
                                            <td class="value">'.$mbizSku.'</td>
                                            <td class="scope-label"><span class="nobr"></span></td>
                                        </tr>';

                        $infoImgUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS).'jquerylib/css/ui-lightness/images/info.png';


                        $newHtml.= '<tr>
                                    <td class="label"><label for="mbiz_version_details">MicroBiz Version Details</label></td>
                                    <td class="value">
                                    <span onclick="showMbizVersion('.$mbizProductId.','.$productId.')" class="get_version_info" style="background: none repeat scroll 0 0 #2F82BA !important;
                        border-radius: 5px;color: #FFFFFF;cursor:pointer;font-family: icon !important;font-size: 15px;
                        font-style: italic;font-weight: bold;line-height: 20px;padding: 2px 5px;text-align: center;text-decoration: none;">i</span>
                                    <td class="scope-label"><span class="nobr"></span></td>
                                </tr><input type="hidden" id="mag_base_url" value="'.$magBaseUrl.'" />
                                <div id="dialog"></div>';

                        $newHtml.='<script type="text/javascript">
                                    function showMbizVersion(mbizProductId,magProductId) {
                                        //alert("from show mbiz verson");
                                        var magBaseUrl = jQuery("#mag_base_url").val();
                                        var postUrl = magBaseUrl+"connector/index/getVersionNumbers";
                                        jQuery("#loading-mask").show();
                                        //jQuery(".scalable").attr("disabled","disabled")

                                        jQuery.ajax({
                                            url: postUrl,
                                            dataType: "json",
                                            type: "post",
                                            data: {mbiz_product_id:mbizProductId,mag_product_id:magProductId },
                                            success: function(data) {
                                                if(data.status=="SUCCESS") {
                                                    versionData = data.response_data;
                                                    magVerData = versionData.mag_versions;
                                                    mbizVerData = versionData.mbiz_versions;
                                                    popupContent = versionData.popup_content;
                                                    console.log(magVerData);
                                                    console.log(mbizVerData);
                                                    console.log(popupContent);
                                                    if(popupContent!="") {
                                                        jQuery("#dialog").html(popupContent);
                                                        jQuery("#dialog").dialog({
                                                        title: "MicroBiz Version Details",
                                                        modal: true,
                                                        width: 445,
                                                        height: 220,
                                                        closeOnEscape: true,
                                                        draggable: false,
                                                        resize : false,
                                                        scroll: true

                                                    });
                                                    }


                                                }
                                                else {
                                                versionData = data.response_data;
                                                popupContent = versionData.popup_content;
                                                console.log(popupContent);
                                                    if(popupContent!="") {
                                                        jQuery("#dialog").html(popupContent);
                                                        jQuery("#dialog").dialog({
                                                        title: "MicroBiz Version Details",
                                                        modal: true,
                                                        width: 500,
                                                        height: 350,
                                                        closeOnEscape: true,
                                                        draggable: false,
                                                        resize : false,
                                                        scroll: true

                                                    });
                                                    }
                                                }
                                                //console.log(data)
                                                jQuery("#loading-mask").hide();
                                            },
                                            error: function() {
                                                console.log("error occurerd")
                                                jQuery("#loading-mask").hide();
                                            }
                                        })



                                    }
                                    function resyncProduct(objId,syncDire) {
                                        //alert("came to resyncProduct");
                                        jQuery("#dialog").dialog("close");

                                        var magBaseUrl = jQuery("#mag_base_url").val();
                                        var postUrl = magBaseUrl+"connector/index/reSyncProduct";

                                        jQuery("#loading-mask").show();

                                        jQuery.ajax({
                                            url: postUrl,
                                            dataType: "json",
                                            type: "post",
                                            data: {mag_product_id:objId,sync_direction:syncDire },
                                            success: function(data) {
                                                if(data.status=="SUCCESS") {
                                                    alert(data.status_msg);
                                                    location.reload();
                                                }
                                                else {
                                                    alert(data.status_msg);
                                                }
                                                jQuery("#loading-mask").hide();
                                            },
                                            error: function($e) {
                                                alert("Error Occured");
                                                jQuery("#loading-mask").hide();
                                            }
                                        });

                                    }
                                    function resyncAttributeset(objId,syncDire) {
                                        //alert("came to resyncattributeset");
                                        jQuery("#dialog").dialog("close");

                                        var magBaseUrl = jQuery("#mag_base_url").val();
                                        var postUrl = magBaseUrl+"connector/index/reSyncAttributeset";

                                        jQuery("#loading-mask").show();

                                        jQuery.ajax({
                                            url: postUrl,
                                            dataType: "json",
                                            type: "post",
                                            data: {mag_attrset_id:objId,sync_direction:syncDire },
                                            success: function(data) {
                                                if(data.status=="SUCCESS") {
                                                    alert(data.status_msg);
                                                    location.reload();
                                                }
                                                else {
                                                    alert(data.status_msg);
                                                }
                                                jQuery("#loading-mask").hide();
                                            },
                                            error: function($e) {
                                                alert("Error Occured");
                                                jQuery("#loading-mask").hide();
                                            }
                                        });

                                    }
                                </script>';
                        $oldHtml = $oldHtml.$newHtml;
                        $transport->setHtml($oldHtml);
                    }
                    else { //send request to get microbiz sku and update in the relation table.

                        $transport = $observer->getTransport();
                        $oldHtml = $transport->getHtml();
                        $isSecure = Mage::app()->getStore()->isCurrentlySecure();
                        $jsUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS,$isSecure);
                        $newHtml ='<script src="'.$jsUrl.'jquerylib/jquery-1.7.2.min.js"></script>
                               <script src="'.$jsUrl.'jquerylib/noconflict.js"></script>
                               <script src="'.$jsUrl.'jquerylib/js/jquery-ui-1.10.4.custom.js"></script>
                                <link rel="stylesheet" type="text/css" href="'.$jsUrl.'jquerylib/css/ui-lightness/jquery-ui-1.10.4.custom.css" />';
                        $newHtml.= '<tr>
                                            <td class="label"><label for="mbiz_product_id">MicroBiz Product ID</label></td>
                                            <td class="value">'.$mbizProductId.'</td>
                                            <td class="scope-label"><span class="nobr"></span></td>
                                        </tr>';

                        $apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
                        $url = $apiInformation['api_server'];
                        $queryString = '?id='.$mbizProductId;

                        $queryString = '?id='.$mbizProductId;
                        $url = $url.'/index.php/api/getSkuById'.$queryString; // prepare url for the rest call

                        //$url = 'http://ktc1.ktree.org/index.php/connector/index/getMbizProductSku'.$queryString;
                        $api_user = $apiInformation['api_user'];
                        $api_key = $apiInformation['api_key'];

                        //Mage::log($url,null,'rel.log');

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

                        $response = curl_exec($handle);	// send curl request to microbiz

                        //$response=json_decode($response,true);


                        $code = curl_getinfo($handle);

                        //Mage::log($response,null,'rel.log');
                        //Mage::log($code,null,'rel.log');

                        if($code['http_code']==200) {
                            $mbizSku = $response;
                            $relUptModel = Mage::getModel('mbizproduct/mbizproduct')->load($relationId);

                            $relUptModel->setMbizSku($mbizSku);
                            $relUptModel->setId($relationId)->save();

                            $newHtml.='<tr>
                                            <td class="label"><label for="mbiz_product_sku">MicroBiz Product Sku</label></td>
                                            <td class="value">'.$mbizSku.'</td>
                                            <td class="scope-label"><span class="nobr"></span></td>
                                        </tr>';


                        }
                        else {
                            $newHtml.='<tr>
                                            <td class="label"><label for="mbiz_product_sku">MicroBiz Product Sku</label></td>
                                            <td class="value">Unable to get MicroBiz Product Sku due to Http Error'.$code['http_code'].'</td>
                                            <td class="scope-label"><span class="nobr"></span></td>
                                        </tr>';
                        }

                        $newHtml.= '<tr>
                                    <td class="label"><label for="mbiz_version_details">MicroBiz Version Details</label></td>
                                    <td class="value">
                                    <span onclick="showMbizVersion('.$mbizProductId.','.$productId.')" class="get_version_info" style="background: none repeat scroll 0 0 #2F82BA !important;
                        border-radius: 5px;color: #FFFFFF;cursor:pointer;font-family: icon !important;font-size: 15px;
                        font-style: italic;font-weight: bold;line-height: 20px;padding: 2px 5px;text-align: center;text-decoration: none;">i</span>
                                    <td class="scope-label"><span class="nobr"></span></td>
                                </tr><input type="hidden" id="mag_base_url" value="'.$magBaseUrl.'" />
                                <div id="dialog"></div>';

                        $newHtml.='<script type="text/javascript">
                                    function showMbizVersion(mbizProductId,magProductId) {
                                        //alert("from show mbiz verson");
                                        var magBaseUrl = jQuery("#mag_base_url").val();
                                        var postUrl = magBaseUrl+"connector/index/getVersionNumbers";
                                        jQuery("#loading-mask").show();
                                        //jQuery(".scalable").attr("disabled","disabled")

                                        jQuery.ajax({
                                            url: postUrl,
                                            dataType: "json",
                                            type: "post",
                                            data: {mbiz_product_id:mbizProductId,mag_product_id:magProductId },
                                            success: function(data) {
                                                if(data.status=="SUCCESS") {
                                                    versionData = data.response_data;
                                                    magVerData = versionData.mag_versions;
                                                    mbizVerData = versionData.mbiz_versions;
                                                    popupContent = versionData.popup_content;
                                                    console.log(magVerData);
                                                    console.log(mbizVerData);
                                                    console.log(popupContent);
                                                    if(popupContent!="") {
                                                        jQuery("#dialog").html(popupContent);
                                                        jQuery("#dialog").dialog({
                                                        title: "MicroBiz Version Details",
                                                        modal: true,
                                                        width: 445,
                                                        height: 220,
                                                        closeOnEscape: true,
                                                        draggable: false,
                                                        resize : false,
                                                        scroll: true

                                                    });
                                                    }


                                                }
                                                else {
                                                versionData = data.response_data;
                                                popupContent = versionData.popup_content;
                                                console.log(popupContent);
                                                    if(popupContent!="") {
                                                        jQuery("#dialog").html(popupContent);
                                                        jQuery("#dialog").dialog({
                                                        title: "MicroBiz Version Details",
                                                        modal: true,
                                                        width: 500,
                                                        height: 350,
                                                        closeOnEscape: true,
                                                        draggable: false,
                                                        resize : false,
                                                        scroll: true

                                                    });
                                                    }
                                                }
                                                //console.log(data)
                                                jQuery("#loading-mask").hide();
                                            },
                                            error: function() {
                                                console.log("error occurerd")
                                                jQuery("#loading-mask").hide();
                                            }
                                        })



                                    }
                                    function resyncProduct(objId,syncDire) {
                                        alert("came to resyncProduct");
                                        jQuery("#dialog").dialog("close");

                                        var magBaseUrl = jQuery("#mag_base_url").val();
                                        var postUrl = magBaseUrl+"connector/index/reSyncProduct";

                                        jQuery("#loading-mask").show();

                                        jQuery.ajax({
                                            url: postUrl,
                                            dataType: "json",
                                            type: "post",
                                            data: {mag_product_id:objId,sync_direction:syncDire },
                                            success: function(data) {
                                                if(data.status=="SUCCESS") {
                                                    alert(data.status_msg);
                                                    location.reload();
                                                }
                                                else {
                                                    alert(data.status_msg);
                                                }
                                                jQuery("#loading-mask").hide();
                                            },
                                            error: function($e) {
                                                alert("Error Occured");
                                                jQuery("#loading-mask").hide();
                                            }
                                        });

                                    }
                                    function resyncAttributeset(objId,syncDire) {
                                        //alert("came to resyncattributeset");
                                        jQuery("#dialog").dialog("close");

                                        var magBaseUrl = jQuery("#mag_base_url").val();
                                        var postUrl = magBaseUrl+"connector/index/reSyncAttributeset";

                                        jQuery("#loading-mask").show();

                                        jQuery.ajax({
                                            url: postUrl,
                                            dataType: "json",
                                            type: "post",
                                            data: {mag_attrset_id:objId,sync_direction:syncDire },
                                            success: function(data) {
                                                console.log("came to success");
                                                console.log(data);
                                                if(data.status=="SUCCESS") {
                                                    alert(data.status_msg);
                                                    location.reload();
                                                }
                                                else {
                                                    alert(data.status_msg);
                                                }
                                                jQuery("#loading-mask").hide();
                                            },
                                            error: function($e) {
                                                alert("Error Occured");
                                                jQuery("#loading-mask").hide();
                                            }
                                        });

                                    }
                                </script>';
                        $oldHtml = $oldHtml.$newHtml;
                        $transport->setHtml($oldHtml);

                    }
                }
                else {
                    $newHtml= '<tr>
                                            <td class="label"><label for="mbiz_product_id">MicroBiz Product ID</label></td>
                                            <td class="value"> Product Not Synced to MicroBiz </td>
                                            <td class="scope-label"><span class="nobr"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="label"><label for="mbiz_product_sku">MicroBiz Product Sku</label></td>
                                            <td class="value"> Product Not Synced to MicroBiz </td>
                                            <td class="scope-label"><span class="nobr"></span></td>
                                        </tr>
                                        <tr>
                                    <td class="label"><label for="mbiz_version_details">MicroBiz Version Details</label></td>
                                    <td class="value"> Product Not Synced to MicroBiz</td>
                                    <td class="scope-label"><span class="nobr"</td> ';

                    $transport = $observer->getTransport();
                    $oldHtml = $transport->getHtml();

                    $oldHtml = $oldHtml.$newHtml;
                    $transport->setHtml($oldHtml);

                }


            }
            else { //product relation is not exists and need to sync on the fly

                $transport = $observer->getTransport();
                $oldHtml = $transport->getHtml();
                $newHtml = '<tr>
                                            <td class="label"><label for="mbiz_product_id">MicroBiz Product ID</label></td>
                                            <td class="value">This Product is not Synced to MicroBiz</td>
                                            <td class="scope-label"><span class="nobr"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="label"><label for="mbiz_product_sku">MicroBiz Product Sku</label></td>
                                            <td class="value">This Product is not Synced to MicroBiz</td>
                                            <td class="scope-label"><span class="nobr"></span></td>
                                        </tr>';

                $newHtml.= '<tr>
                                            <td class="label"><label for="mbiz_version_details">MicroBiz Version Details</label></td>
                                            <td class="value">This Product is not Synced to MicroBiz</td>
                                            <td class="scope-label"><span class="nobr"></span></td>
                                        </tr>';
                $oldHtml = $oldHtml.$newHtml;
                $transport->setHtml($oldHtml);

            }

        }
    }

    /**
     * @param $observer
     * @author KT174
     * @description This method is used to Check If the CreditItems have GiftCard, If Exists we are checking the
     * present giftcard value by sending curl request to microbiz if giftcard value and present giftcard value
     * both are same means giftcard is not yet used, then we are allowing to refund otherwise we are throwing Error
     * Message and stopping the Creation of Credit Memo.
     */
    public function mbizOnCreditMemoBefore($observer) {
        Mage::log("came to mbizOnCreditMemoBefore",null,'refundorder.log');
        //echo "came to mbizoncreditmemobefore";
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $creditMemo->getOrder();
        $orderId = $order->getId();

        $creditItems = $creditMemo->getAllItems();
        $giftCardSku = Mage::getStoreConfig('connector/settings/giftcardsku');




        //$giftCardSku = 'product_sku12940';
        foreach($creditItems as $item) {

            if($giftCardSku) {
                if($item->getSku()==$giftCardSku) {

                    /*Sending Curl Request to Find the Usage of GiftCard is Done or not */
                    $giftCardPrice = $item->getPrice();
                    $giftSaleInfo = Mage::getModel('mbizgiftcardsale/mbizgiftcardsale')->getCollection()->addFieldToFilter('order_id',$orderId)->addFieldToFilter('gcd_amt',$giftCardPrice)->getData();

                    //print_r($giftSaleInfo);
                    if(!empty($giftSaleInfo)) {
                        $apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
                        $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
                        Mage::log($url,null,'giftcards.log');
                        $queryString = '?gcd_unique_num='.$giftSaleInfo[0]['gcd_unique_num'].'&gcd_pin='.$giftSaleInfo[0]['gcd_pin'];

                        $url = $url.'/index.php/api/validateGiftcard'.$queryString; // prepare url for the rest call

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

                        $response = curl_exec($handle);	// send curl request to microbiz
                        $response=json_decode($response,true);

                        $code = curl_getinfo($handle);
                        //print_r($code);exit;
                        Mage::log($response,null,'refundorder.log');
                        if($code['http_code']==200) {
                            if($response['status']==1) {
                                $presentCardValue = $response['present_card_value'];
                                Mage::log($giftCardPrice,null,'refundorder.log');

                                if($presentCardValue<$giftCardPrice) {
                                    Mage::getSingleton('core/session')->addError('Unable to Create Credit Memo.As the GiftCard has been Used. Please Remove Giftcard Product from Items to create Credit Memo for Other Items.');
                                    Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/sales_order_creditmemo/new/", array("order_id"=>$orderId)))->sendResponse();
                                    exit;
                                }
                                else {

                                    Mage::log("came to calling mbizhold",null,'refundorder.log');
                                    Mage::log($orderId,null,'refundorder.log');
                                    Mage::log($giftSaleInfo[0]['gcd_unique_num'],null,'refundorder.log');
                                    $this->mbizHoldGiftCard($giftSaleInfo[0]['gcd_unique_num']);
                                }

                            }
                            else {
                                Mage::getSingleton('core/session')->addError('Unable to Create Credit Memo. Error while validating Gift Card Product. Please Remove Giftcard Product from Items to create Credit Memo for Other Items.');
                                Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/sales_order_creditmemo/new/", array("order_id"=>$orderId)))->sendResponse();
                                exit;
                            }
                        }
                        else {
                            Mage::getSingleton('core/session')->addError('Unable to Create Credit Memo due to Http Error '.$code['http_code'].' while validating Gift Card Product. Please Remove Giftcard Product from Items to create Credit Memo for Other Items.');
                            Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/sales_order_creditmemo/new/", array("order_id"=>$orderId)))->sendResponse();
                            exit;
                        }
                    }
                    else {
                        Mage::getSingleton('core/session')->addError('Unable to Create Credit Memo due to some error while validating Gift Card Product. Please Remove Giftcard Product from Items to create Credit Memo for Other Items.');
                        Mage::app()->getResponse()->setRedirect(Mage::helper("adminhtml")->getUrl("adminhtml/sales_order_creditmemo/new/", array("order_id"=>$orderId)))->sendResponse();
                        exit;
                    }



                }
            }

        }

    }

        public function mbizHoldGiftCard($gcdUniqueNum=null)
    {
        Mage::log("came to mbizHoldGiftCard",null,'refundorder.log');
        Mage::log($gcdUniqueNum,null,'refundorder.log');

        /*Code to check whether this order contains  any giftcards and send request to microbiz to hold the giftcard until the giftcard is synced*/
        if($gcdUniqueNum) {
            $giftCardSaleData = Mage::getModel('mbizgiftcardsale/mbizgiftcardsale')->getCollection()->addFieldToFilter('order_id',$orderId)->addFieldToFilter('order_item_id',$itemId)->getData();

                    $postGiftData[] = $gcdUniqueNum;

                $apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
                $url = $apiInformation['api_server']; // get microbiz server details fron configuration settings.
                $url = $url . '/index.php/api/holdGiftCardOnCancelOrder'; // prepare url for the rest call
                $api_user = $apiInformation['api_user'];
                $api_key = $apiInformation['api_key'];
                $postData = json_encode($postGiftData);

                Mage::log($postData,null,'refundorder.log');


                // headers and data (this is API dependent, some uses XML)
                $headers = array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'X-MBIZPOS-USERNAME: '.$api_user,
                    'X-MBIZPOS-PASSWORD: '.$api_key
                );

                $GiftHandle = curl_init();		//curl request to create the product
                curl_setopt($GiftHandle, CURLOPT_URL, $url);
                curl_setopt($GiftHandle, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($GiftHandle, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($GiftHandle, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($GiftHandle, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($GiftHandle, CURLOPT_POST, true);
                curl_setopt($GiftHandle, CURLOPT_POSTFIELDS, $postData);


                $giftResponse = curl_exec($GiftHandle);

                $code = curl_getinfo($GiftHandle);

                $giftResponse=json_decode($giftResponse,true);
                Mage::log($code,null,'refundorder.log');
                Mage::log($giftResponse,null,'refundorder.log');


        }

        return true;

    }

}
