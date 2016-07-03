<?php
class Microbiz_Connector_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{
    public function  __construct() {

    parent::__construct();
$isResync = Mage::getModel('saleorderheader/saleorderheader')->getCollection()->addFieldToFilter('order_id',$this->getOrderId())->addFieldToFilter('overall_sync_status','Failed')->getData();
   if(count($isResync))
    $this->_addButton('resync_order', array(
    'label'     => Mage::helper('microbiz_connector')->__('Sync Order to MicroBiz'),
    'onclick'   => 'setLocation(\'' . $this->getSyncOrderMicroBizUrl() . '\')',
    'class'     => 'go'
    ), 0, 100, 'header', 'header');
    }

    public function getSyncOrderMicroBizUrl() {
        return $this->getUrl('*/sales_order/syncOrderToMicroBiz');
    }
}

?>