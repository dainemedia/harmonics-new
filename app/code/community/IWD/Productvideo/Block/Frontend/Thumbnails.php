<?php
class IWD_Productvideo_Block_Frontend_Thumbnails extends Mage_Catalog_Block_Product_View_Media
{
    const SORT_IMAGE_VIDEO = "image_video";
    const SORT_VIDEO_IMAGE = "video_image";
    const SORT_IMAGE = "image";
    const SORT_VIDEO = "video";

    const XML_PATH_THUMBNAILS_SLIDER_SORT = 'iwd_productvideo/slider/sort_order';

    private $media_collection = array();

    public function getSliderSort()
    {
        return Mage::getStoreConfig(self::XML_PATH_THUMBNAILS_SLIDER_SORT);
    }

    public function getImageSrc($image_file, $width, $height, $zoom = 1)
    {
        return Mage::helper('catalog/image')
            ->init($this->getProduct(), 'thumbnail', $image_file)
            ->resize($width * $zoom, $height * $zoom);
    }

    public function getGalleryVideos()
    {
        $productId = $this->getProduct()->getId();
        return Mage::getModel('iwd_productvideo/productvideo')->getVideoCollectionByProduct($productId);
    }

    public function getGalleryMedia()
    {
        switch ($this->getSliderSort()) {
            case self::SORT_IMAGE_VIDEO:
                $this->_getImagesCollection();
                $this->_getVideoCollection();
                break;
            case self::SORT_VIDEO_IMAGE:
                $this->_getVideoCollection();
                $this->_getImagesCollection();
                break;
            case self::SORT_IMAGE:
                $this->_getImagesCollection();
                break;
            case self::SORT_VIDEO:
                $this->_getVideoCollection();
                break;
        }

        return $this->media_collection;
    }

    private function _getImagesCollection()
    {
        $images_collection = $this->getGalleryImages();
        if (!empty($images_collection)) {
            foreach ($images_collection as $image) {
                $image->setMediaType('image');
                $this->media_collection[] = $image;
            }
        }
    }

    private function _getVideoCollection()
    {
        $videos_collection = $this->getGalleryVideos();
        if (!empty($videos_collection)) {
            foreach ($videos_collection as $video) {
                $video->setMediaType('video');
                $this->media_collection[] = $video;
            }
        }
    }
}