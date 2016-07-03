<?php
// Version 101
/**
 * Extended Attributeset Controller of Front Action
 *
 * @category   Ktree
 * @package    Ktree_ExtendedMbizConnector
 * @author      KT026
 */
// require_once("../../Helper/ERunActions.php");


class Microbiz_Connector_Sync_AttributesetController extends Mage_Core_Controller_Front_Action
{
    const MAX_QTY_VALUE = 99999999.9999;

    public function saveSyncRecordsAction()
    {
        $attributesetId = $this->getRequest()->getParam('id');

        $locale = 'en_US';

        // changing locale works!
        Mage::app()->getLocale()->setLocaleCode($locale);

        // needed to add this
        Mage::app()->getTranslator()->setLocale($locale);
        Mage::app()->getTranslator()->init('frontend', true);
        Mage::app()->getTranslator()->init('adminhtml', true);

        $response = array();

        foreach ($this->getRequest()->getPost() as $key => $data) {
            if (unserialize($data)) {
                $response[$key] = unserialize($data);
            } else {
                $response[$key] = $data;
            }
        }

        $attributeSetInformation = Mage::getModel('Microbiz_Connector_Model_Product_Attribute_Group_Api')->items($attributesetId);

        $attributeSetInformation['attribute_set_name'] = $response['attribute_set_name'];

        $relationAttributeSetdata = Mage::getModel('mbizattributeset/mbizattributeset')->getCollection()->addFieldToFilter('magento_id', $attributesetId)->setOrder('id', 'asc')->getData();

        $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()->addFieldToFilter('obj_id', $attributesetId)->addFieldToFilter('model_name', 'AttributeSets')->addFieldToFilter('status', array('in' => array('Pending', 'Failed')))->setOrder('header_id', 'desc')->getData();

        if ($isObjectExists) {

            $header_id = $isObjectExists[0]['header_id'];

            /*Adding Version Numbers code starts here */

            $attrRel = Mage::helper('microbiz_connector')->checkIsObjectExists($attributesetId, 'AttributeSets');

            if (!empty($attrRel)) {
                /*Mage::log("came to updating the version number in sync", null, 'relations.log');*/
                $mageVersionNo = $attrRel['mage_version_number'];
                $mbizVersionNo = $attrRel['mbiz_version_number'];

                /*Mage::log($header_id, null, 'relations.log');
                Mage::log($mageVersionNo, null, 'relations.log');
                Mage::log($mbizVersionNo, null, 'relations.log');*/
                $extendedModel = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($header_id);
                $extendedModel->setMageVersionNumber($mageVersionNo);
                $extendedModel->setMbizVersionNumber($mbizVersionNo);
                $extendedModel->save();
            }

            /*Adding Version Numbers code ends here */

            $isObjectExists[0]['status'] = 'Pending';
            Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($header_id)->setData($isObjectExists[0])->save();
            $origitemsData = Mage::getModel('syncitems/syncitems')->getCollection()->addFieldToFilter('header_id', $header_id)->getData();
            foreach ($origitemsData as $origitemData) {
                $itemid = $origitemData['id'];
                $model1 = Mage::getModel('syncitems/syncitems')->load($itemid);
                // deleting the records form item table
                try {
                    $model1->delete();
                } catch (Mage_Core_Exception $e) {
                    $this->_fault('not_deleted', $e->getMessage());
                    // Some errors while deleting.
                }
            }
        } else {

            $user = $response['user']; // Mage::getSingleton('admin/session')->getUser()->getFirstname();

            $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
            $attributeSetData['model_name'] = 'AttributeSets';
            $attributeSetData['instance_id'] = Mage::helper('microbiz_connector')->getAppInstanceId();
            $attributeSetData['obj_id'] = $attributesetId;
            $attributeSetData['mbiz_obj_id'] = $relationAttributeSetdata[0]['mbiz_id'];
            $attributeSetData['created_by'] = $user;
            $attributeSetData['created_time'] = $date;
            /*Adding Version Numbers code starts here */
            $attrRel = Mage::helper('microbiz_connector')->checkIsObjectExists($attrSetId, $objectType);
            if (!empty($attrRel)) {
                $attributeSetData['mage_version_number'] = $attrRel['mage_version_number'];
                $attributeSetData['mbiz_version_number'] = $attrRel['mbiz_version_number'];
            }
            $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                ->setData($attributeSetData)
                ->save();
            $header_id = $model['header_id'];
        }

        foreach ($attributeSetInformation as $key => $updateditem) {
            $attributeSetInfo['header_id'] = $header_id;
            $attributeSetInfo['attribute_id'] = '';
            $attributeSetInfo['attribute_name'] = $key;
            if (!is_array($updateditem)) {
                $attributeSetInfo['attribute_value'] = $updateditem;
            } else {
                $attributeSetInfo['attribute_value'] = serialize($updateditem);
            }
            $attributeSetInfo['created_by'] = $user;
            $attributeSetInfo['created_time'] = $date;
            $model = Mage::getModel('syncitems/syncitems')
                ->setData($attributeSetInfo)
                ->save();
        }
    }
}

