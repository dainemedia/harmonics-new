<?php
//version 103
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Product attribute add/edit form main tab
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Microbiz_Connector_Block_Product_Attribute_Main extends Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tab_Main
{
    /**
     * Adding product form elements for editing attribute
     *
     * @return Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tab_Main
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();
        $attributeObject = $this->getAttributeObject();
        $form = $this->getForm();
        $attributeId = $this->getRequest()->getParam('attribute_id');
        $relationData = Mage::helper('microbiz_connector')->checkIsObjectExists($attributeId, 'Attributes');
        if($attributeObject->getIsUserDefined() || !$attributeId) {
            $fieldset = $form->addFieldset('new_fieldset',
                array('legend'=>Mage::helper('catalog')->__('Microbiz  Options'))
            );
            $yesNoSource = Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray();
            $isReadOnly = false;
            if($relationData) {
                $mbizId = $relationData['mbiz_id'];
                $mappableMBizFields = array('10031','10049','10054','10056','10058','10061','10062','10063','10076','10049');
                $isReadOnly = (!in_array($mbizId,$mappableMBizFields)) ? false : true;
            }
            $fieldset->addField('is_used_to_create_mapping', 'select', array(
                'name' => 'is_used_to_create_mapping',
                'label' => Mage::helper('catalog')->__('Is Used to Create Field Mapping in MicroBiz'),
                'title' => Mage::helper('catalog')->__('Is Used to Create Field Mapping in MicroBiz'),
                'values' => $yesNoSource,
                'disabled' => $isReadOnly
            ));
        }

        $versionFieldSet = $form->addFieldset('mbiz_version_details',
            array('legend'=> Mage::helper('catalog')->__('MicroBiz Version Details')));


        if(!empty($relationData)) {
            $versionFieldSet->addField('mbiz_attr_id','label',array(
                'label' => Mage::helper('catalog')->__('Attribute ID'),
                'name' => 'mbiz_attr_id',
                'required' => false,
                'value' => $relationData['mbiz_id']
            ));
            $isSecure = Mage::app()->getStore()->isCurrentlySecure();
            $magBaseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, $isSecure);

            $versionElem = $versionFieldSet->addField('version_info','label',array(
                'label' => Mage::helper('catalog')->__('Version Details'),
                'name' => 'version_info',
                'required'=> false,
                'class' => 'mbiz_version_info',
                'value' => ''
            ));
            $jsUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS,$isSecure);

            $versionElem->setAfterElementHtml('
                        <script src="'.$jsUrl.'jquerylib/jquery-1.7.2.min.js"></script>
                        <script src="'.$jsUrl.'jquerylib/noconflict.js"></script>
                        <script src="'.$jsUrl.'jquerylib/js/jquery-ui-1.10.4.custom.js"></script>
                        <link rel="stylesheet" type="text/css" href="'.$jsUrl.'jquerylib/css/ui-lightness/jquery-ui-1.10.4.custom.css" />
                        <span onclick="showMbizAttrVersInfo('.$attributeId.')" class="get_version_info" style="background: none repeat scroll 0 0 #2F82BA !important;
                        border-radius: 5px;color: #FFFFFF;cursor:pointer;font-family: icon !important;font-size: 15px;
                        font-style: italic;font-weight: bold;line-height: 20px;padding: 2px 5px;text-align: center;text-decoration: none;">i</span>

                        </tr><input type="hidden" id="mag_base_url" value="'.$magBaseUrl.'" />

                        <span id="dialog"></span>

                        <script type="text/javascript">
                            function showMbizAttrVersInfo(magAttrId) {
                                //alert(magAttrSetId);
                                baseUrl = jQuery("#mag_base_url").val();
                                jQuery("#loading-mask").show();
                                postUrl = baseUrl+"connector/index/getAttrVerInfo";

                                jQuery.ajax({
                                    url: postUrl,
                                    dataType: "json",
                                    type: "post",
                                    data: {mag_attr_id: magAttrId},
                                    success: function(data) {
                                        if(data.status=="SUCCESS") {
                                            console.log(data);
                                            jQuery("#loading-mask").hide();
                                            popupContent = data.popup_content;
                                            if(popupContent!="") {
                                                        jQuery("#dialog").html(popupContent);
                                                        jQuery("#dialog").dialog({
                                                        title: "MicroBiz Version Details",
                                                        modal: true,
                                                        width: 400,
                                                        height: 200,
                                                        closeOnEscape: true,
                                                        draggable: false,
                                                        resize : false,
                                                        scroll: true

                                                    });
                                                    }
                                        }
                                        else {
                                            jQuery("#loading-mask").hide();
                                            alert(data.status_msg);
                                        }
                                    },
                                    error: function() {
                                        alert("Error Occurred while getting the MicroBiz Version Details");
                                        jQuery("#loading-mask").hide();
                                    }
                                });
                            }

                            function resyncAttribute(magAttrId,syncDirec) {
                                baseUrl = jQuery("#mag_base_url").val();
                                jQuery("#dialog").dialog("close");
                                jQuery("#loading-mask").show();
                                postUrl = baseUrl+"connector/index/resyncAttribute";

                                jQuery.ajax({
                                            url: postUrl,
                                            dataType: "json",
                                            type: "post",
                                            data: {mag_attr_id:magAttrId,sync_direction:syncDirec },
                                            success: function(data) {
                                                if(data.status=="SUCCESS") {
                                                    alert(data.status_msg);
                                                    location.reload();
                                                }
                                                else {
                                                    alert(data.status_msg);
                                                }
                                                jQuery("#loading-mask").hide();
                                            },
                                            error: function($e) {
                                                alert("Error Occured");
                                                jQuery("#loading-mask").hide();
                                            }
                                        });
                            }
                        </script>

                    ');
        }
        else {
            $versionFieldSet->addField('mbiz_attr_id','label',array(
                'label' => Mage::helper('catalog')->__('Attribute ID'),
                'name' => 'mbiz_attr_id',
                'required' => false,
                'value' => 'Attribute Relation Not Exists'
            ));
        }
        return $this;
        //parent::_prepareForm();
    }

    /**
     * Retrieve additional element types for product attributes
     *
     * @return array
     */
    protected function _getAdditionalElementTypes()
    {
        return array(
            'apply'         => Mage::getConfig()->getBlockClassName('adminhtml/catalog_product_helper_form_apply'),
        );
    }
}
