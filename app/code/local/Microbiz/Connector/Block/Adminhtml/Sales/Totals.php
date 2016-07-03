<?php
//version 101
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 15/4/14
 * Time: 3:16 PM
 */

class Microbiz_Connector_Block_Adminhtml_Sales_Totals extends Mage_Adminhtml_Block_Sales_Totals
{
    /**
     * Initialize order totals array
     * @author KT-174
     * @description - In this method we will get the store credits or gift card information based on the order id
     * from the sync table mbiz_order_credit_usage_history and based on the collection we are going to calculate the
     * total discount applied by using store credits and gift cards and then we are updating the total paid and
     * total refunded fields by subtracting the discount amount from them.
     */
    protected function _initTotals()
    {
        $this->_totals = array();
        $this->_totals['subtotal'] = new Varien_Object(array(
            'code'      => 'subtotal',
            'value'     => $this->getSource()->getSubtotal(),
            'base_value'=> $this->getSource()->getBaseSubtotal(),
            'label'     => $this->helper('sales')->__('Subtotal')
        ));

        /**
         * Add shipping
         */
        if (!$this->getSource()->getIsVirtual() && ((float) $this->getSource()->getShippingAmount() || $this->getSource()->getShippingDescription()))
        {
            $this->_totals['shipping'] = new Varien_Object(array(
                'code'      => 'shipping',
                'value'     => $this->getSource()->getShippingAmount(),
                'base_value'=> $this->getSource()->getBaseShippingAmount(),
                'label' => $this->helper('sales')->__('Shipping & Handling')
            ));
        }


        $orderId = $this->getSource()->getId();
        $creditData = Mage::getModel('mbizcreditusage/mbizcreditusage')->getCollection();
        $arrCreditData = $creditData->addFieldToFilter('order_id',$orderId)->setOrder('id','asc')->getData();

        if(count($arrCreditData)>0)
        {
            $discountAmount =0;
                foreach($arrCreditData as $data)
                {
                    $discountAmount = $discountAmount + $data['credit_amt'];

                }


        }
        if (((float)$this->getSource()->getDiscountAmount()) != 0) {
            if ($this->getSource()->getDiscountDescription()) {
                $discountLabel = $this->helper('sales')->__('Discount (%s)', $this->getSource()->getDiscountDescription());
            } else {
                $discountLabel = $this->helper('sales')->__('Discount');
            }
            $this->_totals['discount'] = new Varien_Object(array(
                'code'      => 'discount',
                'value'     => $this->getSource()->getDiscountAmount(),
                'base_value'=> $this->getSource()->getBaseDiscountAmount(),
                'label'     => $discountLabel
            ));
        }

        $this->_totals['grand_total'] = new Varien_Object(array(
            'code'      => 'grand_total',
            'strong'    => true,
            'value'     => $this->getSource()->getGrandTotal(),
            'base_value'=> $this->getSource()->getBaseGrandTotal(),
            'label'     => $this->helper('sales')->__('Grand Total'),
            'area'      => 'footer'
        ));

        $totalPaid = $this->getSource()->getTotalPaid();
        if($totalPaid>0)
        {
            $totalPaid = $totalPaid-$discountAmount;
        }
        $this->_totals['paid'] = new Varien_Object(array(
            'code'      => 'paid',
            'strong'    => true,
            'value'     => $totalPaid,
            'base_value'=> $totalPaid,
            'label'     => $this->helper('sales')->__('Total Paid'),
            'area'      => 'footer'
        ));
        $totalRefunded = $this->getSource()->getTotalRefunded();
        if($totalRefunded>0)
        {
            $totalRefunded = $totalRefunded-$discountAmount;
        }
        $this->_totals['refunded'] = new Varien_Object(array(
            'code'      => 'refunded',
            'strong'    => true,
            'value'     => $totalRefunded,
            'base_value'=> $totalRefunded,
            'label'     => $this->helper('sales')->__('Total Refunded'),
            'area'      => 'footer'
        ));
        $this->_totals['due'] = new Varien_Object(array(
            'code'      => 'due',
            'strong'    => true,
            'value'     => $this->getSource()->getTotalDue(),
            'base_value'=> $this->getSource()->getBaseTotalDue(),
            'label'     => $this->helper('sales')->__('Total Due'),
            'area'      => 'footer'
        ));
        return $this;
    }
}