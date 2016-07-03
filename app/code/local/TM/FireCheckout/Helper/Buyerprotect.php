<?php

class TM_FireCheckout_Helper_Buyerprotect
    extends Symmetrics_Buyerprotect_Helper_Data
{
    public function getTsProductsInCart()
    {
        $tsProductIds = array();

        /* @var $cart Mage_Checkout_Model_Cart */
        $cart = Mage::getSingleton('checkout/cart')
            ->setStore(Mage::app()->getStore());

        /* @var $cartItems Mage_Sales_Model_Mysql4_Quote_Item_Collection */
        // firecheckout fix
        // $cartItems = $cart->getItems();
        $cartItems = Mage::getModel('sales/quote_item')->getCollection();
        $cartItems->setQuote($cart->getQuote());
        // fix

        /* @var $tsIdsSelect Varien_Db_Select */
        $tsIdsSelect = clone $cartItems->getSelect();
        $tsIdsSelect->where('product_type = ?', Symmetrics_Buyerprotect_Model_Type_Buyerprotect::TYPE_BUYERPROTECT);

        $items = $cartItems->getConnection()->fetchCol($tsIdsSelect);
        $customerSession = Mage::getSingleton('customer/session');
        $customerSession->setTsSoap(false);
        if ($items) {
            $customerSession->setTsSoap(true);
            foreach ($items as $item) {
                $tsProductIds[$item] = $cartItems->getItemById($item)->getProductId();
            }
        }

        return $tsProductIds;
    }
}
