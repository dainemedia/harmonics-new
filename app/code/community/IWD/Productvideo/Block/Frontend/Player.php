<?php
class IWD_Productvideo_Block_Frontend_Player extends Mage_Catalog_Block_Product_Abstract
{

    public function isAutoplayVideo()
    {
        return Mage::helper("iwd_productvideo")->isAutoplayVideo();
    }
}