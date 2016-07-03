<?php
class IWD_Productvideo_Helper_Zoom extends Mage_Core_Helper_Abstract
{
    const XML_PATH_STATUS = 'iwd_productvideo/zoom/enabled';
    const XML_PATH_WIDTH_IMAGE = 'iwd_productvideo/zoom/width';
    const XML_PATH_HEIGHT_IMAGE = 'iwd_productvideo/zoom/height';
    const XML_PATH_WIDTH_ZOOM = 'iwd_productvideo/zoom/width_zoom';
    const XML_PATH_HEIGHT_ZOOM = 'iwd_productvideo/zoom/height_zoom';
    const XML_PATH_ZOOM_RATIO = 'iwd_productvideo/zoom/ratio';
    const XML_PATH_ZOOM_POSITION = 'iwd_productvideo/zoom/position';

    const XML_PATH_THUMBNAILS_SLIDER_ENABLED = 'iwd_productvideo/slider/enabled';
    const XML_PATH_THUMBNAILS_POSITION = 'iwd_productvideo/slider/position';
    const XML_PATH_THUMBNAILS_WIDTH = 'iwd_productvideo/slider/width';
    const XML_PATH_THUMBNAILS_HEIGHT = 'iwd_productvideo/slider/height';

    const XML_PATH_BLOCK_TITLE = 'iwd_productvideo/slider/title';

    public function getBlockTitle()
    {
        return $this->__(Mage::getStoreConfig(self::XML_PATH_BLOCK_TITLE));
    }

    public function getTemplateConfig()
    {
        $config = new Varien_Object();

        //image size
        $config->setImageWidth(Mage::getStoreConfig(self::XML_PATH_WIDTH_IMAGE));
        $config->setImageHeight(Mage::getStoreConfig(self::XML_PATH_HEIGHT_IMAGE));

        //thumnails position
        $config->setThumbnailsPosition(Mage::getStoreConfig(self::XML_PATH_THUMBNAILS_POSITION));

        //zoombox
        $config->setZoomBoxWidth(Mage::getStoreConfig(self::XML_PATH_WIDTH_ZOOM));
        $config->setZoomBoxHeight(Mage::getStoreConfig(self::XML_PATH_HEIGHT_ZOOM));
        $config->setPositionZoom(Mage::getStoreConfig(self::XML_PATH_ZOOM_POSITION));

        //zoom ratio
        $config->setZoomRatio(Mage::getStoreConfig(self::XML_PATH_ZOOM_RATIO));


        //thumbnails sizes
        $config->setThumbnailWidth(Mage::getStoreConfig(self::XML_PATH_THUMBNAILS_WIDTH));
        $config->setThumbnailHeight(Mage::getStoreConfig(self::XML_PATH_THUMBNAILS_HEIGHT));

        $config->setThumbnailSlider(Mage::getStoreConfig(self::XML_PATH_THUMBNAILS_SLIDER_ENABLED));

        return $config;
    }


    public function checkPositionMainImage($config)
    {
        $thumbnailPosition = $config->getThumbnailsPosition();
        $classImageWrapper = 'right';

        switch ($thumbnailPosition) {
            case '1': //left
                $classImageWrapper = 'right';
                break;
            case '2': //right
                $classImageWrapper = 'left';
                break;
            case '3': //top
                $classImageWrapper = 'top';
                break;
            case '4': //bottom
                $classImageWrapper = 'bottom';
                break;
        }

        return $classImageWrapper;
    }

    public function checkPositionThumbnails($config)
    {
        $thumbnailPosition = $config->getThumbnailsPosition();
        $classImageWrapper = 'bottom';

        switch ($thumbnailPosition) {
            case '1': //left
                $classImageWrapper = 'left';
                break;
            case '2': //right
                $classImageWrapper = 'right';
                break;
            case '3': //top
                $classImageWrapper = 'top';
                break;
            case '4': //bottom
                $classImageWrapper = 'bottom';
                break;
        }
        return $classImageWrapper;
    }

    public function getJsonConfig()
    {
        $config = $this->getTemplateConfig();
        return $config->toJson();
    }
}