<?php
class Microbiz_Core_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getConnectorVersion() { 
		Mage::log("came to connector version");
		if(Mage::helper('core')->isModuleEnabled('Microbiz_Connector')) {
		Mage::log(Mage::getConfig()->getNode()->modules->Microbiz_Connector->version);
		return (string)Mage::getConfig()->getNode()->modules->Microbiz_Connector->version; 
}
else {
		$configFilePath = Mage::getBaseDir().DS.'app/code/local/Microbiz/Connector/etc/config.xml';
		$content=simplexml_load_file($configFilePath);
		$version = $content->config->modules->Microbiz_Connector->version;
		return (string)$version;
		
}
	} 
	public function getCoreExtVersion() {
		Mage::log("came to connector version");
		Mage::log(Mage::getConfig()->getNode()->modules->Microbiz_Core->version);
		return Mage::getConfig()->getNode()->modules->Microbiz_Core->version; 
	}
	public function rrmdir($dir)
	{
	
		if (is_dir($dir)) { 
     $objects = scandir($dir); 
    $response[] = $objects;
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
        	if (is_dir($dir."/".$object)) {
		
		Mage::helper('microbiz_core')->rrmdir($dir."/".$object);
		rmdir($$dir."/".$object); 
		}
		else {
		 unlink($dir."/".$object);
		}
       } 
     } 
     reset($objects); 
     rmdir($dir); 
return $response;

   } 
	}



	public function checkConflictExists()
    {

        //sending curl request and check whether the conflict file is exists in the upgrade list or not. If not Continue
        //the Upgrade process else stop and through the error.
        Mage::log("Came to Check Conflicts Exists action",null,'upgrade.log');

        $conflictResponse = array();
        $apiServerUrl = Mage::getStoreConfig('connector/settings/api_server');
        Mage::log($apiServerUrl,null,'upgrade.log');
        if($apiServerUrl) {

            $apiServerUrl = $url = preg_replace("(^https?://)", "", $apiServerUrl );

            $serverArray = explode('.',$apiServerUrl);

            $clientName = $serverArray[0];
            $pluginId = Mage::getStoreConfig('mbizcoreplugininstall/upgrade_available/version_id');



           // $url = "http://162.243.82.240/initialsync/Installupgrade/upgrade.php?client_name=".$clientName;
            $apiUrlData = Mage::helper('microbiz_core')->getApiServerUrl();
            $ApiUrl = $apiUrlData.'/index.php/api/MbizConnectionUpgrade?client_name='.$clientName.'&plugin_id='.$pluginId;

            Mage::log("client name ".$clientName,null,'upgrade.log');
            Mage::log($ApiUrl,null,'upgrade.log');
            $headers = '';
            $handle = curl_init();		//curl request to create the product
            curl_setopt($handle, CURLOPT_URL, $ApiUrl);
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($handle);	// send curl request to microbiz
            $response=json_decode($response,true);
            Mage::log($response,null,'upgrade.log');
            $code = curl_getinfo($handle);
            Mage::log($code,null,'upgrade.log');
            if($code['http_code']==200) {

                if($response['errorCode']==0) {
                    $conflictResponse = $response;
                    $conflictResponse['status'] = 'SUCCESS';

                }
                else {
                    $conflictResponse['status'] = 'FAIL';
                    $conflictResponse['status_msg'] = $response['status_msg'];
                    $conflictResponse['errorCode'] = $response['errorCode'];

                }

            }
            else {
                $conflictResponse['status'] = 'FAIL';
                $conflictResponse['status_msg'] = 'Unbale to Check for the Conflict due to Http Error '.$code['http_code'];
            }
        }
        else {
            $conflictResponse['status'] = 'FAIL';
            $conflictResponse['status_msg'] = 'Your Magento Store is not Connected to any MicroBiz Cloud Instance. Please Configure and try again.';
        }

        Mage::log($conflictResponse,null,'upgrade.log');
        Mage::log("end of check conflicts exits action in helper",null,'upgrade.log');

        return $conflictResponse;

    }

    public function RemoveCaching()
    {
        try {
            $allTypes = Mage::app()->useCache();
            foreach($allTypes as $type => $blah) {
                //Mage::log($blah);
                Mage::app()->getCacheInstance()->cleanType($type);
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage());
        }
    }

    /**
     * @author KT174
     * @description This method is used to check whether the current magento store or server has soap and curl modules
     * installed or not.
     * @response Success or Fail.
     */
    public function checkDependencies()
    {

        if (version_compare(Mage::getVersion(), '1.6.0.0', '>=')) {
            //$soap = class_exists("SOAPClient");
//            Mage::Log(get_loaded_extensions(),null,'upgrade.log');
            if(in_array('soap',get_loaded_extensions())) {
                //check Curl is Installed or Enabled.
                if(in_array('curl',get_loaded_extensions())) {
                    $response['status'] = 'SUCCESS';
                    $response['status_msg'] = 'All Dependency Modules have been Installed.';
                }
                else {
                    $response['status'] = 'FAIL';
                    $response['status_msg'] = 'CURL module is not Installed / Enabled . Please Install/Enable it and try again.';
                }
            }
            else {
                $response['status'] = 'FAIL';
                $response['status_msg'] = 'SOAP module is not Installed / Enabled . Please Install/Enable it and try again.';
            }
        }
        else {
            $response['status'] = 'FAIL';
            $response['status_msg'] = 'MicorBiz Connector Plugin is not Supported for this Magento Version. Please Upgrade your Magento and try again.';
        }


        return $response;
    }

    public function testConnection($mbizSiteName,$mbizUname,$mbizPwd)
    {
        $url = $mbizSiteName.'/index.php/api/test';
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: '.$mbizUname,
            'X-MBIZPOS-PASSWORD: '.$mbizPwd
        );

        $data = array();

        $handle = curl_init();		//curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($handle);	// send curl request to microbiz
        $response=json_decode($response,true);

        $code = curl_getinfo($handle);
        $code['message'] = $response['message'];

        return $code;
    }

    public function getInstallFiles()
    {
        $apiUrlData = Mage::helper('microbiz_core')->getApiServerUrl();
        $ApiUrl = $apiUrlData.'/index.php/api/InstallPlugin';

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: admin',
            'X-MBIZPOS-PASSWORD: admin321'
        );

        $handle = curl_init();		//curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $ApiUrl);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_POST, true);

        $response = curl_exec($handle);	// send curl request to microbiz
        $response=json_decode($response,true);

        $code = curl_getinfo($handle);

        if($code['http_code']==200) {
            if(!empty($response)) {
                $InstallResponse['status']='SUCCESS';
                $InstallResponse =$response;
            }
            else {
                $InstallResponse['status']='FAIL';
                $InstallResponse['status_msg']= 'Unable to Install the Plugin. No Response is returned by the server.';
            }

        }
        else {
            $InstallResponse['status']='FAIL';
            $InstallResponse['status_msg']= 'Unable to Install the Plugin error Occurred while getting the files list due to Http Error '.$code['http_code'];
        }

        return $InstallResponse;
    }

    public function CheckInstallPermissions()
    {
        $permissionsResponse = array();
        $isDirNotWriteable = 0;

        $baseDir = Mage::getBaseDir();
        $ConnectorInstallList = array();
        $ConnectorInstallList[] = $baseDir.'/app/code';
        $ConnectorInstallList[] = $baseDir.'/app/locale/en_US/template/email';
        $ConnectorInstallList[] = $baseDir.'/app/etc/modules';
        $ConnectorInstallList[] = $baseDir.'/app/design/adminhtml/default/default/layout/';
        $ConnectorInstallList[] = $baseDir.'/app/design/adminhtml/default/default/template';
        $ConnectorInstallList[] = $baseDir.'/app/design/frontend/base/default/layout';
        $ConnectorInstallList[] = $baseDir.'/app/design/frontend/base/default/template';
        $ConnectorInstallList[] = $baseDir.'/js';
        $ConnectorInstallList[] = $baseDir.'/skin/adminhtml/default/default/images';

        foreach($ConnectorInstallList as $directory) {
             if(!is_dir_writeable($directory)) {
                 $isDirNotWriteable =1;
                 $nonWritableDir = $directory;
                 break;

             }
        }

        if(!$isDirNotWriteable) {
            $permissionsResponse['status'] = 'SUCCESS';
            $permissionsResponse['status_msg'] = 'All the Required Folders have Correct Write Permissions.';


        }
        else {
            $permissionsResponse['status'] = 'FAIL';
            $permissionsResponse['status_msg'] = 'Unable to Install the MicroBiz Connector Plugin due to Permissions
            Issue. This Folder '.$nonWritableDir.' does not have writeable Permissions. please change the permissions and try again.';
        }
        return $permissionsResponse;


    }

    public function getInstallLink()
    {
        $ApiUrl = 'http://162.243.82.240/initialsync/Installupgrade/install.php';
        $apiUrlData = Mage::helper('microbiz_core')->getApiServerUrl();
        $ApiUrl = $apiUrlData.'/index.php/api/MbizConnectionInstall';

        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-MBIZPOS-USERNAME: admin',
            'X-MBIZPOS-PASSWORD: admin321'
        );

        $handle = curl_init();		//curl request to create the product
        curl_setopt($handle, CURLOPT_URL, $ApiUrl);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($handle, CURLOPT_POST, true);

        $response = curl_exec($handle);	// send curl request to microbiz

        $response = json_decode($response,true);
     //   print_r($response);
        $code = curl_getinfo($handle);

        if($code['http_code']==200) {
            if($response!='') {
                $InstallResponse['status']='SUCCESS';
                $InstallResponse['install_link'] =$response['zipFilePath'];
            }
            else {
                $InstallResponse['status']='FAIL';
                $InstallResponse['status_msg']= 'Unable to Install the Plugin. No Response is returned by the server.';
            }

        }
        else {
            $InstallResponse['status']='FAIL';
            $InstallResponse['status_msg']= 'Unable to Install the Plugin error Occurred while getting the files list due to Http Error '.$code['http_code'];
        }

        return $InstallResponse;
    }

    public function getUpgradeAvailFiles($upgradeXmlFilePath)
    {
        Mage::log("came to get upgrade avail files action helper",null,'upgrade.log');
        Mage::log($upgradeXmlFilePath,null,'upgrade.log');
        $response = array();
        if($upgradeXmlFilePath) {
            try {
                $response['status'] = 'SUCCESS';

                $filesList = simplexml_load_file($upgradeXmlFilePath);
                $jsonData = json_encode($filesList);
                $xmlArray = json_decode($jsonData, TRUE);
                Mage::log($xmlArray,null,'upgrade.log');
                $response['upgrade_files_list'] = $xmlArray['file'];
            }
            catch(Exception $e) {
                $response['status']='FAIL';
                $response['status_msg'] = 'Unable to Upgrade the Connector Plugin Error Occurred while Xml Reading. Please contact site Administrator.';

            }
        }
        else {
            $response['status'] = 'FAIL';
            $response['status_msg'] = 'No Upgrade File Path Specified from the Automation Server.';
        }

        return $response;
    }

    public function CheckUpgradePermissions($UpgradeFilesList)
    {
        Mage::log("came to check upgrade permissions",null,'upgrade.log');
        Mage::log($UpgradeFilesList,null,'upgrade.log');
        $isFileNotWriteable =0;
        $permissionsResponse = array();
        $baseDir = Mage::getBaseDir().'/';
        if(!empty($UpgradeFilesList)) {
            foreach($UpgradeFilesList as $file) {
                if(file_exists($baseDir.trim($file))) {
                    if(!is_writable($file)) {
                        $isFileNotWriteable =1;
                        $nonWritableFile = $baseDir.$file;
                        break;

                    }
                }
                else {
                    $fileArray = explode('/',trim($file));
                    unset($fileArray[count($fileArray)-1]);
                    $newFolder = implode($fileArray,'/');

                    if(!is_dir_writeable($newFolder)) {
                        $isFileNotWriteable =1;
                        $nonWritableFile = $baseDir.$newFolder;
                        break;
                    }

                }

            }

            if(!$isFileNotWriteable) {
                $permissionsResponse['status'] = 'SUCCESS';
                $permissionsResponse['status_msg'] = 'All the Required Files have Correct Write Permissions.';


            }
            else {
                $permissionsResponse['status'] = 'FAIL';
                $permissionsResponse['status_msg'] = 'Unable to Upgrade the MicroBiz Connector Plugin due to Permissions
            Issue. This File '.$nonWritableFile.' does not have writeable Permissions. please change the permissions and try again.';
            }
        }
        else {
            $permissionsResponse['status'] = 'FAIL';
            $permissionsResponse['status_msg'] = 'Unable to Upgrade the MicroBiz Connector Plugin No Files List Returned by the Server to Check the writeable Permissions.';

        }
        Mage::log($permissionsResponse,null,'upgrade.log');
        Mage::log("end of permissions Response",null,'upgrade.log');
        return $permissionsResponse;
    }

    public function getApiServerUrl()
    {
       // $apiUrl = 'http://ktc41.ktree.org/sujana.autometion';
        //$apiUrl = 'http://104.131.65.5/automation';
        $apiUrl = 'http://104.131.65.5/productionauto';

        return $apiUrl;
    }

    public function getErrorDesc($errorCode=null) {
        switch($errorCode)
        {
        case '1501' :
            $message = 'Instance Magento Api details Empty';
            break;

        case '1502' :
            $message = 'Instance Magento Api Not responding';
            break;

        case '1503' :
            $message = 'Plugin is customized version';
            break;
        case '1510' :
            $message = 'Magento File permissions issue';
            break;
        case '1511' :
            $message = 'Magento plugin version not supporting';
            break;
        case '1512' :
            $message = 'At least 1 file should have for proceeding upgrade.';
            break;
        case '1513' :
            $message = 'Post Data Not Valid.';
            break;
        case '1514' :
            $message = 'Package file not exists';
            break;
        case '1515' :
            $message = 'Soap fault';
            break;
        default :
                $message = 'No Error Code Returned by the Server.';

        }

        return $message;
    }

}
?>
