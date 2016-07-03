<?php
class IWD_Productvideo_Block_Frontend_Media extends Mage_Catalog_Block_Product_View_Media
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if (Mage::helper('iwd_productvideo')->IsEnabled()) {
            /*** IWD_ProductVideo template ***/
            $this->setTemplate('iwd/productvideo/media.phtml');

            //$this->getLayout()->getBlock('head')->addJs('iwd/all/jquery-1.10.2.min.js');
            //$this->getLayout()->getBlock('head')->addJs('iwd/productvideo/video.js');
            //$this->getLayout()->getBlock('head')->addJs('iwd/productvideo/view.js');
            //$this->getLayout()->getBlock('head')->addJs('iwd/productvideo/zoom.js');

            //$this->getLayout()->getBlock('head')->addItem('iwd/all/jquery-1.10.2.min.js');
            $this->getLayout()->getBlock('head')->addItem('skin_js', 'js/iwd/productvideo/video.js');
            $this->getLayout()->getBlock('head')->addItem('skin_js', 'js/iwd/productvideo/view.js');
            $this->getLayout()->getBlock('head')->addItem('skin_js', 'js/iwd/productvideo/zoom.js');

            $this->getLayout()->getBlock('head')->addCss('css/iwd/productvideo/video-js.css');
            $this->getLayout()->getBlock('head')->addCss('css/iwd/productvideo/zoom.css');
        } else {
            /*** Standard template ***/
            $this->setTemplate('catalog/product/view/media.phtml');
        }

        return $this;
    }

    public function getUrlController()
    {
        $url = Mage::helper('adminhtml')->getUrl('iwd_productvideo/player/getvideo');
        return str_replace("https://", "http://", $url);
    }
}