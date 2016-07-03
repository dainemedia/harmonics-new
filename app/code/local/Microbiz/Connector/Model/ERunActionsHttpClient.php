<?php
//Version 102
/**
 * ERunActionsHttpClient.php
 */
class Microbiz_Connector_Model_ERunActionsHttpClient extends Mage_Core_Model_Abstract
{

    public $userAgent = 'Mozilla/5.0 Firefox/3.6.12';

    private $_touchOnly;

    public function _construct($touchOnly = false)
    {

        $this->_touchOnly = $touchOnly;
    }

    /**
     * Socked based http request
     * Based on code from: http://www.php.net/manual/de/function.fsockopen.php#101872
     * Added touchOnly feature, changed headers
     *
     * @param mixed $ip
     * @param integer $port
     * @param string $uri
     * @param string $verb
     * @param array $getData
     * @param array $postData
     * @param array $cookie
     * @param array $custom_headers
     * @param integer $timeout
     * @param mixed $req_hdr
     * @param mixed $res_hdr
     * @return
     */
    public function request
    (
        $ip, /* Target IP/Hostname */
        $port = 80, /* Target TCP port */
        $uri = '/', /* Target URI */
        $verb = 'GET', /* HTTP Request Method (GET and POST supported) */
        $getData = array(), /* HTTP GET Data ie. array('var1' => 'val1', 'var2' => 'val2') */
        $postData = null, /* HTTP POST Data ie. array('var1' => 'val1', 'var2' => 'val2') */
        $contentType = null,
        $cookie = array(), /* HTTP Cookie Data ie. array('var1' => 'val1', 'var2' => 'val2') */
        $custom_headers = array(), /* Custom HTTP headers ie. array('Referer: http://localhost/ */
        $timeout = 2000, /* Socket timeout in milliseconds */
        $req_hdr = false, /* Include HTTP request headers */
        $res_hdr = false /* Include HTTP response headers */
    )
    {

        $isSSL = $port == 443;

        $ret = '';
        $verb = strtoupper($verb);
        $cookie_str = '';
        $getData_str = count($getData) ? '?' : '';
        $postData_str = '';

        if (!empty($getData)) {
            foreach ($getData as $k => $v)
                $getData_str .= urlencode($k) . '=' . urlencode($v) . '&';
            $getData_str = substr($getData_str, 0, -1);
        }

        if (isset($postData)) {
            if (is_array($postData)) {
                foreach ($postData as $k => $v)
                    $postData_str .= urlencode($k) . '=' . urlencode($v) . '&';

                $postData_str = substr($postData_str, 0, -1);
            } else
                $postData_str = is_string($postData) ? $postData : serialize($postData);
        }

        foreach ($cookie as $k => $v)
            $cookie_str .= urlencode($k) . '=' . urlencode($v) . '; ';

        $crlf = "\r\n";
        $req = $verb . ' ' . $uri . $getData_str . ' HTTP/1.1' . $crlf;
        $req .= 'Host: ' . $ip . $crlf;
        $req .= 'User-Agent: ' . $this->userAgent . $crlf;
        $req .= "Cache-Control: no-store, no-cache, must-revalidate" . $crlf;
        $req .= "Cache-Control: post-check=0, pre-check=0" . $crlf;
        $req .= "Pragma: no-cache" . $crlf;

        foreach ($custom_headers as $k => $v)
            $req .= $k . ': ' . $v . $crlf;

        if (!empty($cookie_str))
            $req .= 'Cookie: ' . substr($cookie_str, 0, -2) . $crlf;

        if ($verb == 'POST' && !empty($postData_str)) {
            if (is_array($postData))
                $req .= 'Content-Type: application/x-www-form-urlencoded' . $crlf;
            else {
                if (empty($contentType))
                    $contentType = 'text/plain';

                $req .= 'Content-Type: ' . $contentType . $crlf;
            }

            $req .= 'Content-Length: ' . strlen($postData_str) . $crlf;
            $req .= 'Connection: close' . $crlf . $crlf;
            $req .= $postData_str;
        } else
            $req .= 'Connection: close' . $crlf . $crlf;

        if ($req_hdr)
            $ret .= $req;

        $ip = $isSSL ? 'ssl://' . $ip : $ip;

        // Mage::log($ip, null, 'runActions.log');
$hasError = false;
        if (($fp = @fsockopen($ip, $port, $errno, $errstr)) == false) {
            $message = "Error $errno: $errstr";
            $hasError = true;
        }


        stream_set_timeout($fp, 0, $timeout * 1000);

        fputs($fp, $req);
        while ($line = fgets($fp)) {
            $ret .= $line;
        }
        fclose($fp);
         $status = substr($ret,9,3);

        if((!empty($status) && $status != 200) || $hasError) {
            try{
                $createSynchandle = curl_init();		//curl request to create the product

                curl_setopt($createSynchandle, CURLOPT_URL, $ip.$uri);

                curl_setopt($createSynchandle, CURLOPT_RETURNTRANSFER, true);
                if($verb == 'POST') {
                    $headers = array(
                        'Accept:
                        application/x-www-form-urlencoded',
                        'Content-Type: application/x-www-form-urlencoded',
                    );
                    curl_setopt($createSynchandle, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($createSynchandle, CURLOPT_POST, true);
                    curl_setopt($createSynchandle, CURLOPT_POSTFIELDS, $postData_str);
                }
                else {
                    $headers = array(
                        'Accept: text/html',
                        'Content-Type: text/html',
                    );
                    curl_setopt($createSynchandle, CURLOPT_HTTPHEADER, $headers);
                }


                $versionresponse = curl_exec($createSynchandle);	// send curl request to microbiz
                $versioncode = curl_getinfo($createSynchandle);
            }
            catch(Exception $e){
                Mage::log($e->getMessage(), null, 'runActions.log');
            }

        }

        if (!$res_hdr)
            $ret = substr($ret, strpos($ret, "\r\n\r\n") + 4);


        return $ret;
    }
}

?>