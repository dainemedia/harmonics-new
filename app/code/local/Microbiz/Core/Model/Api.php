<?php
class Microbiz_Core_Model_Api extends Mage_Api_Model_Resource_Abstract
{
	public function mbizInstallConnPlugin($installData)
	{
        $arrInstallData = json_decode($installData,true);
        $installResponse = array();
        Mage::log($arrInstallData,null,'upgrade.log');
        if(!empty($arrInstallData))
        {
            $installUrl = $arrInstallData['zipFilePath'];

            $DependencyResponse = Mage::helper('microbiz_core')->checkDependencies();
            Mage::log("Dependency Response ",null,'upgrade.log');
            Mage::log($DependencyResponse,null,'upgrade.log');

            if($DependencyResponse['status']=='SUCCESS') {

                //check the files and folders permisssions before installing.

                $FolderPermissionsResponse = Mage::helper('microbiz_core')->CheckInstallPermissions();
                Mage::log("FolderPermissionsResponse ",null,'upgrade.log');
                Mage::log($FolderPermissionsResponse,null,'upgrade.log');

                if($FolderPermissionsResponse['status']=='SUCCESS') {

                    if(!is_dir(Mage::getBaseDir().'/MicrobizAutomation')) {
                        mkdir(Mage::getBaseDir().'/MicrobizAutomation',0777,true);
                    }

                    $installArray = explode('/',$installUrl);

                    $fileId = count($installArray)-1;
                    $fileName = $installArray[$fileId];

                    $file2 = fopen(Mage::getBaseDir().'/MicrobizAutomation/'.$fileName,'w+');
                    $zipFilePath = Mage::getBaseDir().'/MicrobizAutomation/'.$fileName;
                    chmod($zipFilePath, 0777);

                    $headers = '';
                    $handle = curl_init();		//curl request to create the product
                    curl_setopt($handle, CURLOPT_URL, $installUrl);
                    curl_setopt($handle, CURLOPT_FAILONERROR, true);
                    curl_setopt($handle, CURLOPT_HEADER, 0);
                    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($handle, CURLOPT_AUTOREFERER, true);
                    curl_setopt($handle, CURLOPT_BINARYTRANSFER,true);
                    curl_setopt($handle, CURLOPT_TIMEOUT, 10);
                    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($handle, CURLOPT_FILE, $file2);
                    $response = curl_exec($handle);

                    $DownloadZipResponse = curl_getinfo($handle);
                    curl_close($handle);
                    Mage::log("DownloadZipResponse ",null,'upgrade.log');
                    Mage::log($DownloadZipResponse,null,'upgrade.log');
                    if($DownloadZipResponse['http_code']==200) {



                        try{
                            $phar = new PharData(Mage::getBaseDir().'/MicrobizAutomation/'.$fileName);
                            if ($phar) {
                                $phar->extractTo(Mage::getBaseDir(),null,true);
                                Mage::helper('microbiz_core')->RemoveCaching();
                                $message = 'Microbiz Connector plugin Installed Successfully';
                                $installResponse['status'] = true;
                                $installResponse['errorCode'] = 0;

                            } else {
                                $message = 'Unable to Install MicroBiz Connector plugin since no file is downloaded from the server.';
                                //$installResponse['status'] = 'Fail';
                                $installResponse['status_msg'] = $message;
                                $installResponse['status'] = false;
                                $installResponse['errorCode'] = 1502;
                            }
                        }
                        catch(Exception $e) {
                            $message = $e->getMessage();
                            //$installResponse['status'] = 'Fail';
                            $installResponse['status_msg'] = 'Unable to Install MicroBiz Connector Plugin due to '.$message;
                            $installResponse['status'] = false;
                            $installResponse['errorCode'] = 1502;
                        }
                    }
                    else {
                        $installResponse['status'] = false;
                        $installResponse['errorCode'] = 1502;
                        $installResponse['status_msg'] = 'Unable to Install MicroBiz Connector Plugin. Http Error
                        '.$DownloadZipResponse['http_code'].' Occurred while Download the MicroBiz Connector Plugin.';

                    }

                }
                else {
                    $installResponse['status'] = false;
                    $installResponse['errorCode'] = 1510;
                    $installResponse['status_msg'] = $FolderPermissionsResponse['status_msg'];
                }
            }
            else {
                $installResponse['status'] = false;
                $installResponse['errorCode'] = 1511;
                $installResponse['status_msg'] = $DependencyResponse['status_msg'];
            }
        }
        else {
            $installResponse['status'] = false;
            $installResponse['errorCode'] = 1513;
            $installResponse['status_msg'] = 'No Data has been Posted.';
        }
        Mage::log("DownloadZipResponse ",null,'upgrade.log');
        Mage::log($DownloadZipResponse,null,'upgrade.log');

        return json_encode($installResponse);

	}



    /**
     * @return array
     * @author KT174
     * @description This method is used to push the Upgrade Available Notification in the Magento Store.
     */
    public function mbizUpgradeNotification($notificationData)
    {
        $arrNotificationData = json_decode($notificationData,true);
        $response = array();
        if(!empty($arrNotificationData)) {
            $versionId = $arrNotificationData['version_id'];
            $configdata = new Mage_Core_Model_Config();
            $configdata->saveConfig('mbizcoreplugininstall/upgrade_available/status',1,'default',0);
            $configdata->saveConfig('mbizcoreplugininstall/upgrade_available/version_id',$versionId,'default',0);


            $response['status'] = true;
            $response['errorCode'] = 0;
            $response['status_msg'] = 'MicroBiz Plugin Upgrade Notification is Pushed Successfully to the Magento Store.';

            Mage::helper('microbiz_core')->RemoveCaching();
        }
        else {
            $response['status'] = false;
            $response['errorCode'] = 1513;
            $response['status_msg'] = 'No Upgrade Notification data posted.';
        }


        return json_encode($response);

    }

    public function mbizUpgradeConnPlugin($upgradeData)
    {
        $arrUpgradeData = json_decode($upgradeData,true);
        $upgradeResponse = array();
        Mage::log(" came to mbizUpgrade Conn Plugin Api Method",null,'upgrade.log');
        Mage::log($arrUpgradeData,null,'upgrade.log');
        if(!empty($arrUpgradeData)) {

            $mbizSiteName = Mage::getStoreConfig('connector/settings/api_server');
            $mbizUname = Mage::getStoreConfig('connector/settings/api_user');
            $mbizPwd = Mage::getStoreConfig('connector/settings/api_key');
            $ApiConnectStatus = Mage::helper('microbiz_core')->testConnection($mbizSiteName,$mbizUname,$mbizPwd);  //check for test connection with microbiz
            Mage::log("api test connection response",null,'upgrade.log');
            Mage::log($ApiConnectStatus,null,'upgrade.log');
            if($ApiConnectStatus['http_code']==200) {
                $upgradeVersion = $arrUpgradeData['version'];
                $currentVersion = Mage::getConfig()->getNode()->modules->Microbiz_Connector->version;
                Mage::log("current ver ".$currentVersion,null,'upgrade.log');
                Mage::log("upgrade ver ".$upgradeVersion,null,'upgrade.log');

                if($currentVersion && $upgradeVersion) {
                    $arrUpgrade = explode('.',$upgradeVersion);
                    $arrCurrent = explode('.',$currentVersion);

                    if(($arrUpgrade[1]-$arrCurrent[1])==0 && ($arrUpgrade[2]-$arrCurrent[2]==1)) {
                        Mage::log("there is only one version diff in upgrade",null,'upgrade.log');
                        $xmlFilePath = $arrUpgradeData['xmlFilePath'];
                        $zipFileUrl = $arrUpgradeData['zipFilePath'];

                        Mage::log("xmlFilePat ".$xmlFilePath,null,'upgrade.log');
                        Mage::log("uzipFileUrl ".$zipFileUrl,null,'upgrade.log');
                        $UpgradeFilesList = Mage::helper('microbiz_core')->getUpgradeAvailFiles($xmlFilePath);
                        Mage::log("upgrade files list ",null,'upgrade.log');
                        Mage::log($UpgradeFilesList,null,'upgrade.log');

                        if($UpgradeFilesList['status']=='SUCCESS' && !empty($UpgradeFilesList['upgrade_files_list'])) {

                            $checkUpgradePermResponse = Mage::helper('microbiz_core')->CheckUpgradePermissions($UpgradeFilesList['upgrade_files_list']);
                            Mage::log("checkUpgradePermResponse ",null,'upgrade.log');
                            Mage::log($checkUpgradePermResponse,null,'upgrade.log');

                            if($checkUpgradePermResponse['status']=='SUCCESS') {
                                $upgradeResponse = $checkUpgradePermResponse;
                                if(!is_dir(Mage::getBaseDir().'/MicrobizAutomation')) {
                                    mkdir(Mage::getBaseDir().'/MicrobizAutomation',0777,true);
                                }

                                $upgradeArray = explode('/',$zipFileUrl);

                                $fileId = count($upgradeArray)-1;
                                $fileName = $upgradeArray[$fileId];
                                $file2 = fopen(Mage::getBaseDir().'/MicrobizAutomation/'.$fileName,'w+');
                                $zipFilePath = Mage::getBaseDir().'/MicrobizAutomation/'.$fileName;
                                chmod($zipFilePath, 0777);
                                $headers = '';
                                $handle = curl_init();		//curl request to create the product
                                $headers = '';
                                $handle = curl_init();		//curl request to create the product
                                curl_setopt($handle, CURLOPT_URL, $zipFileUrl);
                                curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
                                curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
                                curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
                                curl_setopt($handle, CURLOPT_FILE, $file2);
                                $response = curl_exec($handle);	// send curl request to microbiz


                                $DownloadZipResponse = curl_getinfo($handle);
                                curl_close($handle);

                                Mage::log("DownloadZipResponse",null,'upgrade.log');
                                Mage::log($DownloadZipResponse,null,'upgrade.log');
                                if($DownloadZipResponse['http_code']==200) {
                                    //$phar = new PharData(Mage::getBaseDir().'/MicrobizAutomation/'.$fileName);

                                    $zip = new ZipArchive;
                                    $ZipPath = Mage::getBaseDir().'/MicrobizAutomation/'.$fileName;

                                    try{

                                        //echo $phar;exit;
                                        if ($zip->open($ZipPath) === TRUE) {
                                            $zip->extractTo(Mage::getBaseDir());
                                            $zip->close();

                                            $configdata = new Mage_Core_Model_Config();
                                            $configdata->saveConfig('mbizcoreplugininstall/upgrade_available/status',0,'default',0);
                                            Mage::helper('microbiz_core')->RemoveCaching();
                                            $message = 'Microbiz Connector plugin Upgraded Successfully';
                                            $upgradeResponse['status'] = true;
                                            $upgradeResponse['errorCode'] = 0;
                                            $upgradeResponse['status_msg'] = $message;


                                        } else {
                                            $message = 'Unable to Upgrade Microbiz Connector plugin since cannot  open the Zip file which downloaded from the server.';
                                            //$upgradeResponse['status'] = 'Fail';
                                            $upgradeResponse['status_msg'] =$message;
                                            $upgradeResponse['status'] = false;
                                            $upgradeResponse['errorCode'] = 1514;

                                        }

                                    }
                                    catch(Exception $e){
                                        $message = $e->getMessage();
                                        //$upgradeResponse['status'] = 'Fail';
                                        $upgradeResponse['status_msg'] ='Unable to Uprade MicroBiz Connector Plugin due to error.'.$message;
                                        $upgradeResponse['status'] = false;
                                        $upgradeResponse['errorCode'] = 1514;

                                    }
                                }
                                else {
                                    /*$upgradeResponse['status'] = 'Fail';*/
                                    $upgradeResponse['status_msg'] = 'Unable to Upgrade MicroBiz Connector Plugin. Http Error '.$DownloadZipResponse['http_code'].' Occurred while Download the MicroBiz Connector Plugin.';
                                    $upgradeResponse['status'] = false;
                                    $upgradeResponse['errorCode'] = 1514;
                                }
                            }
                            else {
                                /*$upgradeResponse['status'] = 'Fail';*/
                                $upgradeResponse['status_msg'] = $checkUpgradePermResponse['status_msg'];
                                $upgradeResponse['status'] = false;
                                $upgradeResponse['errorCode'] = 1510;
                            }
                        }
                        else {
                            /*$upgradeResponse['status'] = 'Fail';*/
                            $upgradeResponse['status_msg'] = 'Unable to Upgrade the MicroBiz Connector Plugin Error Occurred while getting the Upgrade Files List';
                            $upgradeResponse['status'] = false;
                            $upgradeResponse['errorCode'] = 1514;
                        }
                    }
                    else {
                        /*$upgradeResponse['status'] = 'Fail';*/
                        $upgradeResponse['status_msg'] = 'Unable to Upgrade the MicroBiz Connector Plugin there is a Version MissMatch. Previous Upgrade version is '.$currentVersion.' Current Upgraded Version is '.$upgradeVersion;
                        $upgradeResponse['status'] = false;
                        $upgradeResponse['errorCode'] = 1511;
                    }
                }
                else {
                    /*$upgradeResponse['status'] = 'Fail';*/
                    $upgradeResponse['status_msg'] = 'Unable to Upgrade the MicroBiz Connector Plugin empty Versions return by the Server and not able to Compare. Please contact cloud support.';
                    $upgradeResponse['status'] = false;
                    $upgradeResponse['errorCode'] = 1511;
                }
            }
            else {
                $upgradeResponse['status'] = false;
                $upgradeResponse['errorCode'] = 1502;
                $upgradeResponse['status_msg'] = 'Unable to Upgrade the MicroBiz Connector Plugin Api Connection not Established.';
            }

        }
        else {
            $upgradeResponse['status'] = false;
            $upgradeResponse['errorCode'] = 1513;
            $upgradeResponse['status_msg'] = 'No Data has been Posted.';
        }

        Mage::log("Upgrade api response ",null,'upgrade.log');
        Mage::log($upgradeResponse,null,'upgrade.log');
        return json_encode($upgradeResponse);


    }
}
?>
