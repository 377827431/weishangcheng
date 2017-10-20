<?php
namespace Org\WxPay;
use Org\WxPay\WxPayApi;
use Org\IdWork;

/**
 * 
 * JSAPI支付实现类
 * 该类实现了从微信公众平台获取code、通过code获取openid和access_token、
 * 生成jsapi支付js接口所需的参数、生成获取共享收货地址所需的参数
 * 
 * 该类是微信支付提供的样例程序，商户可根据自己的需求修改，或者使用lib中的api自行开发
 * 
 * @author widy
 *
 */
class JsApiPay
{
	/**
	 * 
	 * 网页授权接口微信服务器返回的数据，返回样例如下
	 * {
	 *  "access_token":"ACCESS_TOKEN",
	 *  "expires_in":7200,
	 *  "refresh_token":"REFRESH_TOKEN",
	 *  "openid":"OPENID",
	 *  "scope":"SCOPE",
	 *  "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
	 * }
	 * 其中access_token可用于获取共享收货地址
	 * openid是微信支付jsapi支付接口必须的参数
	 * @var array
	 */
	public $data = null;
	
	public function __construct(){
	    ini_set('date.timezone','Asia/Shanghai');
	}
	
	/**
	 * 
	 * 获取jsapi支付的参数
	 * @param array $UnifiedOrderResult 统一支付接口返回的数据
	 * @throws WxPayException
	 * 
	 * @return json数据，可直接填入js函数作为参数
	 */
	public function GetParameters($UnifiedOrderResult)
	{
		if(!array_key_exists("appid", $UnifiedOrderResult)
		|| !array_key_exists("prepay_id", $UnifiedOrderResult)
		|| $UnifiedOrderResult['prepay_id'] == "")
		{
			throw new WxPayException("参数错误");
		}
		
		$jsapi = $UnifiedOrderResult['trade_type'] == 'APP' ? new WxPayAppApiPay() : new WxPayJsApiPay();
		
		$jsapi->SetAppid($UnifiedOrderResult["appid"]);
		$timeStamp = time();
		$jsapi->SetTimeStamp("$timeStamp");
		$jsapi->SetNonceStr(WxPayApi::getNonceStr());
		if($UnifiedOrderResult['trade_type'] == 'APP'){
		    $jsapi->SetPrepayId($UnifiedOrderResult['prepay_id']);
		    $jsapi->SetPackage("Sign=WXPay");
		    $jsapi->SetMchId($UnifiedOrderResult['mch_id']);
		}else{
		    $jsapi->SetSignType('MD5');
		    $jsapi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);
		}
		
		$jsapi->SetPaySign($jsapi->MakeSign());
		return $jsapi->GetValues();
	}
	
	public function createOrder(\Org\WxPay\WxPayUnifiedOrder $model, $retry = false){
	    // 生成外部订单号
	    if(!$model->IsOut_trade_noSet()){
	        $outTradeNo = IdWork::nextOutTid();
	        $model->SetOut_trade_no($outTradeNo);
	    }
	    
	    $order = \Org\WxPay\WxPayApi::unifiedOrder($model);
	    if($order['result_code'] == 'SUCCESS' && $order['return_code'] == 'SUCCESS'){
	        return $this->GetParameters($order);
	    }else if(!$retry){
            return $this->createOrder($model, true);
	    }
	    
	    return array('errcode' => $order['result_code'], 'errmsg' => $order['err_code_des']);
	}
}
?>