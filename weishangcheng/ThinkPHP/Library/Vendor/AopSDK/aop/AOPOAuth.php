<?php
class AOPOAuth
{
    public $param = array();
    public $client_id;
    public $secret;
    public $_aop_signature;
    
    public function __construct ()
    {
        # appkey appsecret 未来放到配置里去
        $client_id = 4157308;
        $secret = 'kVhHHhfWu1';
        
        # 初始化赋值
        $this->client_id = $client_id;
        $this->secret = $secret;
    }
    
    public function setParam ($param)
    {
        $this->param = array_merge($this->param,$param);
    }
    
    public function generateSign ($apiInfo = '')
    {
        ksort($this->param);
        foreach ($this->param as $k => $v)
        {
            $factor .= "$k$v";
        }
        $this->param['_aop_signature'] = strtoupper(bin2hex(hash_hmac("sha1", $apiInfo.$factor, $this->secret, true)));
    }
    
    public function doOAuth ()
    {
        $url = 'http://gw.open.1688.com/auth/authorize.htm';
        $this->setParam(array(
            'client_id' => $this->client_id,
            'site'  => 'china',
            'redirect_uri' => $_SERVER['HTTP_HOST'].'/admin/index/setToken'
        ));
        $this->generateSign();
        foreach( $this->param as $k => $v )
        {
            $params[] = $k.'='.$v;
        }
        header("Location: ".$url.'?'.implode('&', $params));
    }
}