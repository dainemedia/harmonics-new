<?php
class IWD_Productvideo_Model_System_Config_Sort
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'image_video',    'label' => 'Image - Video'),
            array('value' => 'video_image',    'label' => 'Video - Image'),
            array('value' => 'image',    'label' => 'Only image'),
            array('value' => 'video',    'label' => 'Only video'),
            //array('value' => 'sort_order',    'label' => 'Parameter sort order'),
        );
    }
}