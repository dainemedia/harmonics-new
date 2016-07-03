<?php

class TM_CheckoutFields_Model_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Invoice
{
    /**
     * Draw additional checkout fields
     *
     * @param Zend_Pdf_Page $page
     * @return void
     */
    protected function _drawCheckoutFields(Zend_Pdf_Page $page, $order)
    {
        if ($order instanceof Mage_Sales_Model_Order_Shipment) {
            $order = $order->getOrder();
        }

        $fields = Mage::helper('checkoutfields')->getFields();
        $lines  = array();
        $i      = 0;
        foreach ($fields as $field => $config) {
            $value = (string)$order->getData($field);

            if (!strlen($value)) {
                continue;
            }

            $lines[$i][] = array(
                'text' => $config['label'],
                'feed' => 35
            );
            $lines[$i][] = array(
                'text' => $value,
                'feed' => 200
            );

            $i++;
        }

        if ($lines) {
            $this->_setFontRegular($page, 10);
            $page->setFillColor(new Zend_Pdf_Color_RGB(0, 0, 0));
            $this->y -= 5;
            $lineBlock = array(
                'lines'  => $lines,
                'height' => 14
            );

            $this->drawLineBlocks($page, array($lineBlock));
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        }
    }

    /**
     * Overriden to add call for _drawCheckoutFields method
     *
     * @param  array $invoices
     * @return Zend_Pdf
     */
    public function getPdf($invoices = array())
    {
        if (!Mage::getStoreConfigFlag('checkoutfields/print/pdf_invoice')) {
            return parent::getPdf($invoices);
        }

        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->emulate($invoice->getStoreId());
                Mage::app()->setCurrentStore($invoice->getStoreId());
            }

            if (method_exists($this, 'insertDocumentNumber')) {
                $page  = $this->newPage();
                $order = $invoice->getOrder();
                /* Add image */
                $this->insertLogo($page, $invoice->getStore());
                /* Add address */
                $this->insertAddress($page, $invoice->getStore());
                /* Add head */
                $this->insertOrder(
                    $page,
                    $order,
                    Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID, $order->getStoreId())
                );
                /* Add document text and number */
                $this->insertDocumentNumber(
                    $page,
                    Mage::helper('sales')->__('Invoice # ') . $invoice->getIncrementId()
                );

                // override start
                $this->_drawCheckoutFields($page, $order);
                // override end

                /* Add table */
                $this->_drawHeader($page);
                /* Add body */
                foreach ($invoice->getAllItems() as $item){
                    if ($item->getOrderItem()->getParentItem()) {
                        continue;
                    }
                    /* Draw item */
                    $this->_drawItem($item, $page, $order);
                    $page = end($pdf->pages);
                }
                /* Add totals */
                $this->insertTotals($page, $invoice);
                if ($invoice->getStoreId()) {
                    Mage::app()->getLocale()->revert();
                }
            } else {
                $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
                $pdf->pages[] = $page;

                $order = $invoice->getOrder();

                /* Add image */
                $this->insertLogo($page, $invoice->getStore());

                /* Add address */
                $this->insertAddress($page, $invoice->getStore());

                /* Add head */
                $this->insertOrder($page, $order, Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID, $order->getStoreId()));

                // override start
                $this->_drawCheckoutFields($page, $order);
                // override end

                $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
                $this->_setFontRegular($page);
                $page->drawText(Mage::helper('sales')->__('Invoice # ') . $invoice->getIncrementId(), 35, 780, 'UTF-8');

                /* Add table */
                $page->setFillColor(new Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
                $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
                $page->setLineWidth(0.5);

                $page->drawRectangle(25, $this->y, 570, $this->y -15);
                $this->y -=10;

                /* Add table head */
                $page->setFillColor(new Zend_Pdf_Color_RGB(0.4, 0.4, 0.4));
                $page->drawText(Mage::helper('sales')->__('Products'), 35, $this->y, 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('SKU'), 255, $this->y, 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Price'), 380, $this->y, 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Qty'), 430, $this->y, 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Tax'), 480, $this->y, 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Subtotal'), 535, $this->y, 'UTF-8');

                $this->y -=15;

                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

                /* Add body */
                foreach ($invoice->getAllItems() as $item){
                    if ($item->getOrderItem()->getParentItem()) {
                        continue;
                    }

                    if ($this->y < 15) {
                        $page = $this->newPage(array('table_header' => true));
                    }

                    /* Draw item */
                    $page = $this->_drawItem($item, $page, $order);
                }

                /* Add totals */
                $page = $this->insertTotals($page, $invoice);

                if ($invoice->getStoreId()) {
                    Mage::app()->getLocale()->revert();
                }
            }
        }
        $this->_afterGetPdf();
        return $pdf;
    }
}
