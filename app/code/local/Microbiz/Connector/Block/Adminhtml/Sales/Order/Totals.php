<?php
//version 101
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 14/4/14
 * Time: 7:40 PM
 */
class Microbiz_Connector_Block_Adminhtml_Sales_Order_Totals extends Microbiz_Connector_Block_Adminhtml_Sales_Totals
{
    /**
     * Initialize order totals array
     * @author KT-174
     * @description - In this method we will get the store credits or gift card information based on the order id
     * from the sync table mbiz_order_credit_usage_history and based on the collection we are going to display the
     * store credit information and gift card information fields separately.
     */
    protected function _initTotals()
    {
        parent::_initTotals();

        $orderId = $this->getSource()->getId();
        $creditData = Mage::getModel('mbizcreditusage/mbizcreditusage')->getCollection();
        $arrCreditData = $creditData->addFieldToFilter('order_id',$orderId)->setOrder('id','desc')->getData();

        if(count($arrCreditData)>0)
        {
            if(is_array($arrCreditData))
            {
                $discountAmount =0;
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