<?php
namespace Org\WxPay;

use Org\Util\String2;

/**
 * 微信红包类库
 * @author lanxuebao
 *
 */
class WxRedPack{
    /**
     * 请求Url
     */
    private $url = 'https://api.mch.weixin.qq.com';
    private $config;
    protected $values = array();
    
    /**
     * 红包类
     * @param unknown $mch
     */
    function __construct($config){
        if(!$config['mch_id']){
            E('微信红包配置文件不存在');
        }
        $this->config = $config;

        $this->setMchId($config['mch_id']);
        $this->setWxAppid($config['appid']);
        $this->setSendName($config['mch_name']);
        $this->setMchKey($config['mch_key']);
    }
    
    public function setMchKey($value){
        $this->values['mch_key'] = $value;
    }
    
    /**
     * 商户订单号
     * 商户订单号（每个订单号必须唯一）
     * 组成： mch_id+yyyymmdd+10位一天内不能重复的数字。
     * 接口根据商户订单号支持重入， 如出现超时可再调用。
     * @param unknown $value
     */
    public function setMchBillno($value){
        $this->values['mch_billno'] = $value;
    }
    
    /**
     * 生成随机商户订单号
     * YmdHis + 4位随机数字
     */
    public function getRandBillNo(){
        return $this->config['mch_id'].date('YmdHis').rand(1000, 9999);
    }
    
    /**
     * 商户号
     * 微信支付分配的商户号
     */
    public function setMchId($value){
        $this->values['mch_id'] = $value;
    }
    
    /**
     * 公众账号appid
     * 微信分配的公众账号ID（企业号corpid即为此appId），接口传入的所有appid应该为公众号的appid（在mp.weixin.qq.com申请的），不能为APP的appid（在open.weixin.qq.com申请的）。
     */
    public function setWxAppid($value){
        $this->values['wxappid'] = $value;
    }
    
    /**
     * 商户名称
     * 红包发送者名称
     */
    public function setSendName($value){
        $this->values['send_name'] = $value;
    }
    
    /**
     * 接收红包的种子用户（首个用户）
     * 用户在wxappid下的openid
     */
    public function setOpenid($value){
        $this->values['re_openid'] = $value;
    }
    
    /**
     * 付款金额，单位分
     */
    public function setTotalAmount($value){
        $this->values['total_amount'] = $value;
    }
    
    /**
     * 红包发放总人数
     */
    public function setTotalNum($value){
        $this->values['total_num'] = $value;
    }
    
    /**
     * 红包祝福语
     */
    public function setWishing($value){
        $this->values['wishing'] = $value;
    }
    
    /**
     * 调用接口的机器Ip地址
     */
    public function setClientIp($value){
        $this->values['client_ip'] = $value;
    }
    
    /**
     * 活动名称
     */
    public function setActName($value){
        $this->values['act_name'] = $value;
    }
    
    /**
     * 备注信息
     */
    public function setRemark($value){
        $this->values['remark'] = $value;
    }
    
    /**
     *  红包金额设置方式
     * ALL_RAND—全部随机,商户指定总金额和红包发放总人数，由微信支付随机计算出各红包金额
     */
    public function setAmtType($value){
        $this->values['amt_type'] = $value;
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
        
        $sign = md5($stringA."key=".$map['mch_key']);
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
            return array('return_code' => 'FAIL', 'return_msg' => $error);
        }
    }
    
    /**
     * 发送现金红包
     */
    public function sendRedPack($openid = null){
        $this->setTotalNum(1);
        if(!is_null($openid)){
            $this->setOpenid($openid);
        }
        
        if(empty($this->values['client_ip'])){
            $this->values['client_ip'] = get_client_ip();
        }
        
        $this->values['nonce_str'] = String2::randString(8);
        if(!isset($this->values['mch_billno'])) {
            $this->values['mch_billno'] = $this->getRandBillNo();
        }
        
        // 数据校验
        
        // 生成签名
        $this->values['sign'] = $this->createSign($this->values);
        
        $xml = $this->createXML($this->values);
        $responseXml = $this->doSend($this->url.'/mmpaymkttransfers/sendredpack', $xml);
        
        if(is_array($responseXml)){
            return $responseXml;
        }else{
            return (array)simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
    }
    
    /**
     * 发送裂变红包
     */
    public function sendGroupRedPack($openid = null, $num = null){
        if(!is_null($openid)){
            $this->setOpenid($openid);
        }
        
        if(!is_null($num)){
            $this->setTotalNum($num);
        }
        
        $this->values['amt_type'] = 'ALL_RAND';
        if(!isset($this->values['mch_billno'])) {
            $this->values['mch_billno'] = $this->getRandBillNo();
        }
        
        // 数据校验
        
        // 生成签名
        $this->values['nonce_str'] = String2::randString(8);
        $this->values['sign'] = $this->createSign($this->values);
        
        $xml = $this->createXML($this->values);
        $responseXml = $this->doSend($this->url.'/mmpaymkttransfers/sendgroupredpack', $xml);
        
        if(is_array($responseXml)){
            return $responseXml;
        }else{
            return (array)simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
    }
    
    public function send($openid = null, $num = null){
        if(!is_null($openid)){
            $this->setOpenid($openid);
        }
        
        if(!is_null($num)){
            $this->setTotalNum($num);
        }
        
        // 发送裂变红包
        if($this->values['total_num'] > 1){
            return $this->sendGroupRedPack();
        }
        
        // 发送现金红包
        return $this->sendRedPack();
    }
}
?>