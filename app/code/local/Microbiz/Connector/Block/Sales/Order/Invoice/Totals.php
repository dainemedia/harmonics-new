<?php
//version 101
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 23/4/14
 * Time: 1:21 PM
 */
class Microbiz_Connector_Block_Sales_Order_Invoice_Totals extends Microbiz_Connector_Block_Sales_Order_Totals
{
    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     * @author KT-174
     * @description - In this method we will get the store credits or gift card information based on the order id
     * from the sync table mbiz_order_credit_usage_history and based on the collection we are going to display the
     * credits information based on the type. Here if credit_type is 1 then it is store credit and if credit_type is 2
     * then it is gift card.
     */
    protected function _initTotals()
    {
        parent::_initTotals();
        $orderId = $this->getSource()->getRealOrderId();
        $creditData = Mage::getModel('mbizcreditusage/mbizcreditusage')->getCollection();
        $arrCreditData = $creditData->addFieldToFilter('order_id',$orderId)->getData();
        $discountAmount =0;
        if(count($arrCreditData)>0)
        {
            if(is_array($arrCreditData))
            {

                foreach($arrCreditData as $key=>$data)
                {
                    $discountAmount = $discountAmount + $data['credit_amt'];
                    if($data['type']==1)
                    {
                        $this->addTotal(new Varien_Object(array(
                            'code' => 'Discount'.$key,
                            'value' => -$data['credit_amt'],
                            'base_value' => -$data['credit_amt'],
                            'label' => 'Store Credit ('.$data['credit_id'].')',
                        )), 'discount');
                    }
                    else {
                        $this->addTotal(new Varien_Object(array(
                            'code' => 'Discount'.$key,
                            'value' => -$data['credit_amt'],
                            'base_value' => -$data['credit_amt'],
                            'label' => 'Gift Card ('.$data['credit_id'].')',
                        )), 'discount');
                    }

                }

            }
        }
    }
}