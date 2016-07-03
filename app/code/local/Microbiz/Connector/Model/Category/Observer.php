<?php
/**
* Product_Observer.php
* 
*/
//Version 101
class Microbiz_Connector_Model_Category_Observer
{
    

    public function __construct()
    {
    }
       
    
	public function onCategoryDelete($observer)
    {
	
		/*$object = $observer->getEvent()->getCategory();
		$category = $object->getData();
        
		$category_model = Mage::getModel('catalog/category'); //get category model
        $_category = $category_model->load($object->getId()); //$categoryid for which the child categories to be found       
        $all_child_categories = $category_model->getResource()->getAllChildren($_category);
		// Mage::getSingleton('core/session')->addError(json_encode($all_child_categories));				   
		// return;
		


		$categoryid = $object->getId();
		
		$apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
		$url    =$apiInformation['api_server']; // get microbiz server details fron configuration settings. 
		$api_user = $apiInformation['api_user'];
		$api_key = $apiInformation['api_key'];
		$display_name = $apiInformation['display_name'];
        $url    = $url.'/index.php/api/category/'.$categoryid;
        $method = 'DELETE';
            
        // headers and data (this is API dependent, some uses XML)
		
        $headers = array(
                'Accept: application/json',
                'Content-Type: application/json',
				'X_MBIZPOS_USERNAME: '.$api_user,
				'X_MBIZPOS_PASSWORD: '.$api_key
        );
		
        $handle = curl_init();
		
            curl_setopt($handle, CURLOPT_URL, $url);
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
            
            switch ($method) {                
                case 'GET':
                    break;
                
                case 'POST':
                    curl_setopt($handle, CURLOPT_POST, true);
                    curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                    break;
                
                case 'PUT':
                    curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
                    break;
                
                case 'DELETE':
                    curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    break;
            } //$method
        
        $response = curl_exec($handle);	// send curl request to microbiz 
        $code = curl_getinfo($handle);

		if($code['http_code'] == 200 ) Mage::getSingleton('core/session')->addSuccess($display_name . ': '. $response); 
		else if($code['http_code'] == 100)
		return $this;
		else
		Mage::getSingleton('core/session')->addError($display_name . ': '. $response);
		
		return $this;
		// Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Category Deleted Successfully (Category Delete event observer)'));
	*/
	}
    /**
     * @author KT174
     * @description This method is used to get the shuffled category information and update it in the sync tables
     */
    public function mbizOnCategoryShuffle($observer)
    {
        $adminsession = Mage::getSingleton('admin/session', array('name'=>'adminhtml'));
        if($adminsession->isLoggedIn()) {
            $category = $observer->getEvent()->getCategory();
            $categoryId = $category->getId();

            if(Mage::getSingleton('admin/session')->isLoggedIn()) {

                $user = Mage::getSingleton('admin/session');
                $user = $user->getUser()->getFirstname();

            }

            else if(Mage::getSingleton('api/session'))
            {
                $userApi = Mage::getSingleton('api/session');
                $user = $userApi->getUser()->getUsername();

            }
            else
            {
                $user = 'Guest';
            }
            $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
            $categoryRelationModel = Mage::getModel('mbizcategory/mbizcategory')
                ->getCollection()
                ->addFieldToFilter('magento_id', $categoryId)
                ->setOrder('id','asc')
                ->getFirstItem()->getData();
            $categoryData =$category->getData();
            $parentId = $categoryData['parent_id'];
            Mage::log($category->getData(),null,'giftcardsale.log');
            Mage::log("category modelaa".$parentId,null,'giftcardsale.log');
            if($parentId>1) //curent category is a sub category
            {
                Mage::log("category modelddd",null,'giftcardsale.log');
                $arrCategoryPathIds = explode('/',$category->getPath());
                $rootCategoryId = $arrCategoryPathIds[1];
                $rootCategorySyncStatus = Mage::getModel('catalog/category')->load($rootCategoryId)->getData('sync_cat_create');
                Mage::log("category modelbb".count($categoryRelationModel),null,'giftcardsale.log');
                Mage::log("category modeldd".$rootCategorySyncStatus,null,'giftcardsale.log');
                if(count($categoryRelationModel)>0 || $rootCategorySyncStatus==1)
                {
                    $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                        ->addFieldToFilter('obj_id', $categoryId)
                        ->addFieldToFilter('model_name', 'ProductCategories')
                        ->addFieldToFilter('status', 'Pending')
                        ->setOrder('header_id','desc')
                        ->getData();

                    if($isObjectExists)
                    {
                        $header_id=$isObjectExists[0]['header_id'];

                    }
                    else
                    {

                        $date = date("Y/m/d H:i:s", Mage::getModel('core/date')->timestamp(time()));
                        $arrCategoryData = array();
                        $arrCategoryData['model_name']='ProductCategories';
                        $arrCategoryData['instance_id']=Mage::helper('microbiz_connector')->getAppInstanceId();
                        $arrCategoryData['obj_id']=$categoryId;
                        $arrCategoryData['created_by']=$user;
                        $arrCategoryData['created_time']= $date;
                        $model = Mage::getModel('extendedmbizconnector/extendedmbizconnector')
                            ->setData($arrCategoryData)
                            ->save();
                        $header_id=$model['header_id'];

                    }
                    $arrCategory = $category->getData();

                    foreach($arrCategory as $key=>$data) {
                        if(is_array($data)) {
                            $arrUpdatedItems[$key]=serialize($data);

                        } else {
                            $arrUpdatedItems[$key]=$data;
                        }
                    }
                    // Mage::log($arrUpdatedItems,null,'syncproduct.log');

                    foreach($arrUpdatedItems as $k=>$updateditem) {
                        if(!is_array($updateditem)) {
                            $arrCategoryInfoData = array();
                            $arrCategoryInfoData['header_id']=$header_id;
                            $isItemExists = Mage::getModel('syncitems/syncitems')
                                ->getCollection()
                                ->addFieldToFilter('header_id', $header_id)
                                ->addFieldToFilter('attribute_name', $k)
                                ->getFirstItem();
                            $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
                            $code = $eavAttribute->getIdByCode('catalog_category', $k);
                            $arrCategoryInfoData['attribute_id']=$code;
                            $arrCategoryInfoData['attribute_name']=$k;
                            $arrCategoryInfoData['attribute_value']= $updateditem;
                            $arrCategoryInfoData['created_by']=$user;
                            $arrCategoryInfoData['created_time']= $date;
                            if($isItemExists->getId()) {
                                $model = Mage::getModel('syncitems/syncitems')->load($isItemExists->getId());
                            }
                            else {
                                $model = Mage::getModel('syncitems/syncitems');
                            }
                            $model->setData($arrCategoryInfoData)->setId($isItemExists->getId())->save();
                        }
                    }

                }
                else {
                    $isObjectExists = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection()
                        ->addFieldToFilter('obj_id', $categoryId)
                        ->addFieldToFilter('model_name', 'ProductCategories')
                        ->addFieldToFilter('status', 'Pending')
                        ->setOrder('header_id','desc')
                        ->getData();
                    if($isObjectExists)
                    {
                        $header_id=$isObjectExists[0]['header_id'];
                        Mage::getModel('extendedmbizconnector/extendedmbizconnector')->load($header_id)->delete();

                    }

                }
            }
        }
    }

}
