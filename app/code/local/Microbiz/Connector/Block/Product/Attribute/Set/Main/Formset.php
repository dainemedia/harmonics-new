<?php
//Version 103
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
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Microbiz_Connector_Block_Product_Attribute_Set_Main_Formset extends Mage_Adminhtml_Block_Catalog_Product_Attribute_Set_Main_Formset
{
   
    /**
     * Prepares attribute set form
     *
     */
    protected function _prepareForm()
    {
        $data = Mage::getModel('eav/entity_attribute_set')
            ->load($this->getRequest()->getParam('id'));

        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('set_name', array('legend'=> Mage::helper('catalog')->__('Edit Set Name')));
        $fieldset->addField('attribute_set_name', 'text', array(
            'label' => Mage::helper('catalog')->__('Name'),
            'note' => Mage::helper('catalog')->__('For internal use.'),
            'name' => 'attribute_set_name',
            'required' => true,
            'class' => 'required-entry validate-no-html-tags',
            'value' => $data->getAttributeSetName()
        ));
		$yesnoSource = Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray();
		$eventElem=$fieldset->addField('sync_attr_set_create', 'select', array(
            'label' => Mage::helper('catalog')->__('Create/Update Attribute Set in MicroBiz POS'),
            'name' => 'sync_attr_set_create',
            'required' => true,
			'id'=>'sync_attr_set_create',
            'class' => 'required-entry',
            'values' => $yesnoSource,
			'value' =>  $data->getSyncAttrSetCreate()
        ));
        if( !$this->getRequest()->getParam('id', false) ) {
            $fieldset->addField('gotoEdit', 'hidden', array(
                'name' => 'gotoEdit',
                'value' => '1'
            ));

            $sets = Mage::getModel('eav/entity_attribute_set')
                ->getResourceCollection()
                ->setEntityTypeFilter(Mage::registry('entityType'))
                ->load()
                ->toOptionArray();

            $fieldset->addField('skeleton_set', 'select', array(
                'label' => Mage::helper('catalog')->__('Based On'),
                'name' => 'skeleton_set',
                'required' => true,
                'class' => 'required-entry',
                'values' => $sets,
            ));
        }
		else  {
            $jsUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS);
			$eventElem->setAfterElementHtml("
<script src='".$jsUrl."jquerylib/jquery-1.7.2.min.js'></script>
<script src='".$jsUrl."jquerylib/noconflict.js'></script>
<script>
                jQuery(document).ready(function(){
					editSet.req.sync_attr_set_create = jQuery('#sync_attr_set_create').val();
					jQuery('#sync_attr_set_create').change(function(){
						var thisvalue = jQuery(this).val();
						editSet.req.sync_attr_set_create = thisvalue;
					});
					//jQuery('button.save').hide();
				});
			</script>");
		}

        /*Code to Display MicroBiz Version Details Fieldset starts here. */
            Mage::log("came to attributesets edit",null,'version.log');
            Mage::log($data->getSyncAttrSetCreate(),null,'version.log');
            $syncAttrSetVal = $data->getSyncAttrSetCreate();
            if($syncAttrSetVal==1) {
                $versionFieldSet = $form->addFieldset('mbiz_version_details',array('legend'=> Mage::helper('catalog')->__('MicroBiz Version Details')));
                $magAttrSetId = $this->getRequest()->getParam('id');

                $relationData = Mage::helper('microbiz_connector')->checkIsObjectExists($magAttrSetId, 'AttributeSets');

                if(!empty($relationData)) {
                    $versionFieldSet->addField('mbiz_attrset_id','label',array(
                        'label' => Mage::helper('catalog')->__('AttributeSet ID'),
                        'name' => 'mbiz_attrset_id',
                        'required' => false,
                        'value' => $relationData['mbiz_id']
                    ));
                    $isSecure = Mage::app()->getStore()->isCurrentlySecure();
                    $magBaseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, $isSecure);
                    $infoImgUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS,$isSecure).'jquerylib/css/ui-lightness/images/info.png';

                    $versionElem = $versionFieldSet->addField('version_info','label',array(
                        'label' => Mage::helper('catalog')->__('Version Details'),
                        'name' => 'version_info',
                        'required'=> false,
                        'class' => 'mbiz_version_info',
                        //'value' => '<a href="javascript:void(1)" onclick="showMbizVersion('.$relationData['mbiz_id'].','.$magAttrSetId.')" ><img src="'.$infoImgUrl.'" width="20px" alt="Show Info"/></a>'
                        'value' => ''
                    ));
                    $jsUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS,$isSecure);

                    $versionElem->setAfterElementHtml('
                        <script src="'.$jsUrl.'jquerylib/js/jquery-ui-1.10.4.custom.js"></script>
                        <link rel="stylesheet" type="text/css" href="'.$jsUrl.'jquerylib/css/ui-lightness/jquery-ui-1.10.4.custom.css" />
                        <span onclick="showMbizAttrSetVersInfo('.$magAttrSetId.')" class="get_version_info" style="background: none repeat scroll 0 0 #2F82BA !important;
                        border-radius: 5px;color: #FFFFFF;cursor:pointer;font-family: icon !important;font-size: 15px;
                        font-style: italic;font-weight: bold;line-height: 20px;padding: 2px 5px;text-align: center;text-decoration: none;">i</span>

                        </tr><input type="hidden" id="mag_base_url" value="'.$magBaseUrl.'" />

                        <span id="dialog"></span>

                        <script type="text/javascript">
                            function showMbizAttrSetVersInfo(magAttrSetId) {
                                //alert(magAttrSetId);
                                baseUrl = jQuery("#mag_base_url").val();
                                jQuery("#loading-mask").show();
                                postUrl = baseUrl+"connector/index/getAttrSetVerInfo";

                                jQuery.ajax({
                                    url: postUrl,
                                    dataType: "json",
                                    type: "post",
                                    data: {mag_attrset_id: magAttrSetId},
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
                                                        height: 400,
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
                            function resyncAttributeset(magAttrSetId,syncDirec) {
                                baseUrl = jQuery("#mag_base_url").val();
                                jQuery("#dialog").dialog("close");
                                jQuery("#loading-mask").show();
                                postUrl = baseUrl+"connector/index/resyncAttributeSet";

                                jQuery.ajax({
                                            url: postUrl,
                                            dataType: "json",
                                            type: "post",
                                            data: {mag_attr_set_id:magAttrSetId,sync_direction:syncDirec },
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
                    $versionFieldSet->addField('mbiz_attrset_id','label',array(
                        'label' => Mage::helper('catalog')->__('AttributeSet ID'),
                        'name' => 'mbiz_attrset_id',
                        'required' => false,
                        'value' => 'NO AttributeSet Relation Exists'
                    ));
                }

            }

        /*Code to Display MicroBiz Version Details Fieldset ends here. */

        $form->setMethod('post');
        $form->setUseContainer(true);
        $form->setId('set_prop_form');
        $form->setAction($this->getUrl('*/*/save'));
        $form->setOnsubmit('return false;');
        $this->setForm($form);
    }
}
