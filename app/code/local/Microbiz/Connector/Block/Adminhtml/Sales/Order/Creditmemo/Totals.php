<?php
//version 102
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 14/4/14
 * Time: 7:40 PM
 */
class Microbiz_Connector_Block_Adminhtml_Sales_Order_Creditmemo_Totals extends Mage_Adminhtml_Block_Sales_Order_Creditmemo_Totals
{
    /**
     * Initialize order totals array
     * @author KT-174
     * @description - In this method we will get the store credits or gift card information based on the order id
     * from the sync table mbiz_order_credit_usage_history and based on the collection we are going to display the
     * store credit information and gift card information fields separately. And also we are updating the grand total
     * while displaying.
     */
    protected function _initTotals()
    {
        parent::_initTotals();
        $orderId = $this->getOrder()->getId();
        $creditData = Mage::getModel('mbizcreditusage/mbizcreditusage')->getCollection();
        $arrCreditData = $creditData->addFieldToFilter('order_id',$orderId)->setOrder('id','desc')->getData();
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

        $this->_totals['grand_total'] = new Varien_Object(array(
            'code'      => 'grand_total',
            'strong'    => true,
            'value'     => $this->getSource()->getGrandTotal()-$discountAmount,
            'base_value'=> $this->getSource()->getBaseGrandTotal()-$discountAmount,
            'label'     => $this->helper('sales')->__('Grand Total'),
            'area'      => 'footer'
        ));

    }
}
