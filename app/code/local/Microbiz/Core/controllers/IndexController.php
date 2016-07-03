<?php
class Microbiz_Core_IndexController extends Mage_Core_Controller_Front_Action
{
	public function checkconnectionAction()
	{
		$postData = $this->getRequest()->getPost();
		$response = array();
		Mage::log("came to check connection action");
		Mage::log($postData);
		if(!empty($postData))
		{
			
			$url=$postData['mbiz_install_siteurl'];
       $api_user=$postData['mbiz_install_siteuname'];
       $api_key=$postData['mbiz_install_sitepwd'];
       //$url= 'http://ktc13.ktree.org/syncattributeset';
       $url    = $url.'/index.php/api/test';			// prepare url for the rest call
       $method = 'POST';
       $headers = array(
           'Accept: application/json',
           'Content-Type: application/json',
           'X_MBIZPOS_USERNAME: '.$api_user,
           'X_MBIZPOS_PASSWORD: '.$api_key
       );

       $data=array();
       $handle = curl_init();		//curl request to create the product
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
               curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');	// create product request.
               curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
               break;

           case 'DELETE':
               curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
               break;
       }

       $response = curl_exec($handle);	// send curl request to microbiz
       $response=json_decode($response,true);
       $code = curl_getinfo($handle);
       $jsonresponse=array();
       if($code['http_code'] == 200 ) {
           $jsonresponse['status'] = 'SUCCESS';
           $jsonresponse['message'] = $this->__('Test Connection Success With Microbiz Instance');
       }
       else if($code['http_code'] == 500) {
           $jsonresponse['status'] = 'ERROR';
           $jsonresponse['message'] = $code['http_code'].' - Internal Server Error'.$response['message'];
       }
       else if($code['http_code'] == 0) {
           $jsonresponse['status'] = 'ERROR';
           $jsonresponse['message'] = $code['http_code'].' - Please Check the API Server URL'.$response['message'];
       }
       else
       {
           $jsonresponse['status'] = 'ERROR';
           $jsonresponse['message'] = $code['http_code'].' - '.$response['message'];
       }
      }
		
		
		$this->getResponse()->setHeader('Content-type', 'application/json');
               $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonresponse));
		$this->_redirect('*/*');
                return json_encode($this);
	}
}
