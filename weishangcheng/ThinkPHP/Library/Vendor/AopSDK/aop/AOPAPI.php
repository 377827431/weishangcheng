<?php
class AOPAPI
{
    protected $http = 'http';
    protected $https = 'https';
    protected $gatewayUrl = 'gw.open.1688.com/openapi';
    public $version = 1;
    private $protocol = 'param2';
    public $aop_oauth;
    public $readTimeout;
    public $connectTimeout;

    public function __construct (){
        $this->aop_oauth = new \AOPOAuth();
    }
    
    public function setAopOauth(){
        $this->aop_oauth = new \AOPOAuth();
    }

    public function getToken ($code, $format = true)
    {
        $aop_oauth = $this->aop_oauth;
        
        $api = 'http/1/system.oauth2/getToken';
        $param = array(
            'grant_type'    => 'authorization_code',
            'need_refresh_token'    => 'true',
            'client_id' => $aop_oauth->client_id,
            'client_secret' => $aop_oauth->secret,
            'redirect_uri'  => $_SERVER['HTTP_HOST'],
            'code'  =>  $code   
            );
        
        $url = $this->https.'://';
        $urls[] = $this->gatewayUrl;
        $urls[] = $api;
        $urls[] = $aop_oauth->client_id;
        $url.= implode('/',$urls);
        
        foreach( $param as $k => $v )
        {
            $params[] = $k.'='.$v;
        }
        
        $result = $this->curl($url.'?'.implode('&', $params));
        $this->setAopOauth();
        return $format ? json_decode($result) : $result ;
    }
    
    // 刷新授权
    public function refreshToken ($refreshToken, $format = true)
    {
        $aop_oauth = $this->aop_oauth;
        
        $api = 'param2/1/system.oauth2/getToken';
        $param = array(
            'grant_type'    => 'refresh_token',
            'client_id' => $aop_oauth->client_id,
            'client_secret' => $aop_oauth->secret,
            'refresh_token' => $refreshToken
            );
        
        $url = $this->https.'://';
        $urls[] = $this->gatewayUrl;
        $urls[] = $api;
        $urls[] = $aop_oauth->client_id;
        $url.= implode('/',$urls);
        
        foreach( $param as $k => $v )
        {
            $params[] = $k.'='.$v;
        }
        
        $result = $this->curl($url.'?'.implode('&', $params));
        $this->setAopOauth();
        return $format ? json_decode($result) : $result ;
    }
    
    public function api($request, $assoc = true)
    {
        $aop_oauth = $this->aop_oauth;
        
        $api = $this->protocol.'/'.$this->version.'/'.$request->getUrl();
        $param = $request->getApiParas();
        $aop_oauth->setParam($param);
        $api_info = $api.'/'.$aop_oauth->client_id;
        $aop_oauth->generateSign($api_info);
        
        $url = $this->http.'://';
        $urls[] = $this->gatewayUrl;
        $urls[] = $api_info;
        $url.= implode('/',$urls);
        
        foreach( $aop_oauth->param as $k => $v )
        {
            $params[] = $k.'='.urlencode($v);
        }

        $result = $this->curl($url.'?'.implode('&', $params));
        $this->setAopOauth();
        return json_decode($result, $assoc, 10240, JSON_BIGINT_AS_STRING);
    }
    
    /*
    * 发送请求
    */
    private function curl($url, $postFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }else{
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        }
        
        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }
        curl_setopt ( $ch, CURLOPT_USERAGENT, "top-sdk-php" );
        //https 请求
        if(strlen($url) > 5 && strtolower(substr($url,0,5)) == "https" ) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (is_array($postFields) && 0 < count($postFields))
        {
            $postBodyString = "";
            $postMultipart = false;
            foreach ($postFields as $k => $v)
            {
                if(!is_string($v))
                    continue ;

                if("@" != substr($v, 0, 1))//判断是不是文件上传
                {
                    $postBodyString .= "$k=" . urlencode($v) . "&"; 
                }
                else//文件上传用multipart/form-data，否则用www-form-urlencoded
                {
                    $postMultipart = true;
                    if(class_exists('\CURLFile')){
                        $postFields[$k] = new \CURLFile(substr($v, 1));
                    }
                }
            }
            unset($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart)
            {
                if (class_exists('\CURLFile')) {
                    curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
                } else {
                    if (defined('CURLOPT_SAFE_UPLOAD')) {
                        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            }
            else
            {
                $header = array("content-type: application/x-www-form-urlencoded; charset=UTF-8");
                curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString,0,-1));
            }
        }
        $reponse = curl_exec($ch);
        $errno = curl_errno($ch);
        $errmsg = curl_error($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($errno){
            throw new Exception($errmsg, 0);
        }else if (200 !== $httpStatusCode){
            throw new Exception($errmsg, $httpStatusCode);
        }
        return $reponse;
    }
}