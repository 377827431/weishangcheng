<?php
namespace Org\WxPay;

/**
 * 微信转账
 * @author lanxuebao
 *
 */
class WXTransfers{
    /**
     * 请求Url
     */
    private $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
    private $config;
    protected $values = array();
    const CHECK_NAME_NO_CHECK    = 'NO_CHECK';
    const CHECK_NAME_FORCE_CHECK    = 'FORCE_CHECK';
    const CHECK_NAME_OPTION_CHECK    = 'OPTION_CHECK';
    
    /**
     * 微信转账类
     */
    function __construct($config){
        if(!$config['appid']){
            E('微信转账配置文件不存在');
        }
        $this->config = $config;

        $this->setMchAppid($config['appid']);
        $this->setMchId($config['mch_id']);
        $this->setCheckName(self::CHECK_NAME_NO_CHECK);
    }
    
    /**
     * 商户订单号
     * 商户订单号（每个订单号必须唯一）
     * 组成： mch_id+yyyymmdd+10位一天内不能重复的数字。
     * 接口根据商户订单号支持重入， 如出现超时可再调用。
     * @param unknown $value
     */
    public function setTradeNo($value){
        $this->values['partner_trade_no'] = $value;
    }
    
    /**
     * 生成随机商户订单号
     * YmdHis + 4位随机数字
     */
    public function createRandTradeNo(){
        return date('YmdHis').rand(1000, 9999);
    }
    
    /**
     * 商户号
     * 微信支付分配的商户号
     */
    public function setMchId($value){
        $this->values['mchid'] = $value;
    }
    
    /**
     * 公众账号appid
     * 微信分配的公众账号ID（企业号corpid即为此appId），接口传入的所有appid应该为公众号的appid（在mp.weixin.qq.com申请的），不能为APP的appid（在open.weixin.qq.com申请的）。
     */
    public function setMchAppid($value){
        $this->values['mch_appid'] = $value;
    }
    
    /**
     * 校验用户姓名选项
     * NO_CHECK：不校验真实姓名 
     * FORCE_CHECK：强校验真实姓名（未实名认证的用户会校验失败，无法转账） 
     * OPTION_CHECK：针对已实名认证的用户才校验真实姓名（未实名认证用户不校验，可以转账成功）
     * @param unknown $type
     */
    public function setCheckName($type){
        $this->values['check_name'] = $type;
    }
    
    /**
     * 收款用户真实姓名(可选)
     * 如果check_name设置为FORCE_CHECK或OPTION_CHECK，则必填用户真实姓名
     */
    public function setReUserName($name){
        $this->values['re_user_name'] = $name;
    }
    
    /**
     * 企业付款金额，单位为分(必填)
     */
    public function setAmount($value){
        $this->values['amount'] = $value;
    }
    
    /**
     * 商户appid下，某用户的openid(必填)
     */
    public function setOpenid($value){
        $this->values['openid'] = $value;
    }
    
    /**
     * 企业付款操作说明信息(必填)
     */
    public function setDesc($value){
        $this->values['desc'] = $value;
    }
    
    /**
     * 生成签名
     */
    private function createSign($map, $urlencode = false){
        $stringA = "";
        ksort($map);
        foreach ($map as $k => $v){
            if (null == $v || "null" == $v || "sign" == $k || "mch_key" == $k) {
                continue;
            }
        
            if($urlencode){
                $v = urlencode($v);
            }
            $stringA .= $k . "=" . $v . "&";
        }
        
        $sign = md5($stringA."key=".$this->config['mch_key']);
        $sign = strtoupper($sign);
        return $sign;
    }
    
    /**
     * 生成xml红包数据
     * @return mixed
     */
    private function createXML($data){
        $xml = new \SimpleXMLElement('<xml></xml>');
        data_xml($xml, $data);
        return $xml->asXML();
    }
    
    /**
     * SSL curl请求
     * @param unknown $url
     * @param unknown $vars
     * @param number $second
     * @param unknown $aHeader
     * @return mixed|boolean
     */
    private function doSend($url, $vars, $second=30, $aHeader=array()){
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
    
        //以下两种方式需选择一种
        //第一种方法，cert 与 key 分别属于两个.pem文件
        $dir = __DIR__.'\\Apiclient\\'.$this->config['appid'].'\\';
        curl_setopt($ch,CURLOPT_SSLCERT,$dir.'apiclient_cert.pem');
        curl_setopt($ch,CURLOPT_SSLKEY,$dir.'apiclient_key.pem');
        //curl_setopt($ch,CURLOPT_CAINFO,$dir.'rootca.pem');
    
        //第二种方式，两个文件合成一个.pem文件
        //curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');
    
        if( count($aHeader) >= 1 ){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }
    
        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
        $data = curl_exec($ch);
    
        if($data){
            curl_close($ch);
            return $data;
        }
        else {
            $error = curl_error($ch);
            //$error = curl_errno($ch);
            curl_close($ch);
            return array('return_code' => 'FAIL', 'return_msg' => $error, 'result_code' => 'FAIL');
        }
    }
    
    /**
     * 执行转账
     * @param string $openid
     * @param decimal $amount 单位为元
     * @return Ambigous <mixed, boolean>|array
     */
    public function transfers($openid, $amount){
        $this->setOpenid($openid);
        $this->setAmount($amount * 100);
        
        if(empty($this->values['spbill_create_ip'])){
            $this->values['spbill_create_ip'] = get_client_ip();
        }
        
        $this->values['nonce_str'] = \Org\Util\String2::randString(16);
        if(!isset($this->values['partner_trade_no'])) {
            $this->values['partner_trade_no'] = $this->createRandTradeNo();
        }
        // 数据校验
        
        // 生成签名
        $this->values['sign'] = $this->createSign($this->values);
        
        $xml = $this->createXML($this->values);
        $responseXml = $this->doSend($this->url, $xml);
        
        if(is_array($responseXml)){
            return $responseXml;
        }else{
            return (array)simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
    }
}
?>