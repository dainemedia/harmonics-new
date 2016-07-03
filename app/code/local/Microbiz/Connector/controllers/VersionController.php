<?php
//version 100

class Microbiz_Connector_VersionController extends Mage_Core_Controller_Front_Action
{
    /**
     * @author KT174
     * @description This method is used to read the Upgrade Files Version numbers and Sum and Output the Results.
     */
    public function checkUpgradeFileVersionsAction()
    {
        $folderToCheck = Mage::getBaseDir().'/';
        $modifiedFilesList = array('app/design/adminhtml/default/default/template/connector/configuration.phtml',
            'app/code/local/Microbiz/Connector/controllers/Adminhtml/ConnectorController.php',
            'app/code/local/Microbiz/Connector/Model/Observer.php',
            'app/code/local/Microbiz/Connector/etc/system.xml',
            'app/code/local/Microbiz/Connector/etc/api.xml',
            'app/code/local/Microbiz/Connector/etc/config.xml',
            'app/design/adminhtml/default/default/layout/connector.xml',
            'app/code/local/Microbiz/Connector/Model/Api.php',
            'app/code/local/Microbiz/Connector/Model/Category/Api.php',
            'app/code/local/Microbiz/Connector/controllers/IndexController.php',
            'app/code/local/Microbiz/Connector/controllers/Sync/ProductController.php',
            'app/code/local/Microbiz/Connector/Helper/Data.php',
            'app/locale/en_US/template/email/initialsync_ssl.html',
            'app/design/adminhtml/default/default/template/connector/choosesettings.phtml',
            'app/design/adminhtml/default/default/template/connector/initialstep.phtml',
            'app/design/adminhtml/default/default/template/connector/installcomplete.phtml',
            'app/design/adminhtml/default/default/template/connector/linking.phtml',
            'app/design/adminhtml/default/default/template/connector/magtombiz.phtml',
            'app/design/adminhtml/default/default/template/connector/mbiztomag.phtml',
            'app/design/adminhtml/default/default/template/connector/pluginapidetails.phtml',
            'app/design/adminhtml/default/default/template/connector/pluginsignin.phtml',
            'app/code/local/Microbiz/Connector/sql/connector_setup/mysql4-upgrade-0.1.5-0.1.6.php',
            'app/code/local/Microbiz/Connector/data/connector_setup/data-upgrade-0.1.5-0.1.6.php',
            'app/code/local/Microbiz/Connector/Model/Product/Observer.php',
            'app/code/local/Microbiz/Connector/Model/ERunActionsHttpClient.php',
            'skin/adminhtml/default/default/images/connector');
        $searchfor = 'VERSION';
        $counter = 0;
        $fileCount= 0;
        $pattern = preg_quote($searchfor, '/');

        $pattern = "/^.*$pattern.* \d{1,4}\$/m";

        foreach ($modifiedFilesList as $file) {
            $fileFullPath = $folderToCheck.$file;
            $info = pathinfo($fileFullPath,PATHINFO_EXTENSION);

            if ($info == 'php' || $info == 'js' || $info == 'phtml' || $info== 'html') {
                $fileCount++;
                echo $path = $fileFullPath;
                $Stringfull = file_get_contents($path);
                if(preg_match_all($pattern, strtoupper($Stringfull), $matches)){
                    $versionString = $matches[0][0];
                    $versionArray = explode(' ',$versionString);
                    echo   "   ".$versionValue = (is_numeric($versionArray[count($versionArray)-1])) ? $versionArray[count($versionArray)-1] : 100;

                    $counter += $versionArray[count($versionArray)-1];
                }
                echo "<br>";
            }
        }

        echo "<br>Total Counter Value = ".$counter;
        echo "<br>Total Files Verified = ".$fileCount;
    }
}