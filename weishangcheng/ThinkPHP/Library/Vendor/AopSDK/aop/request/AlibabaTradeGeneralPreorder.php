<?php
/**
 * 多订单的预先浏览展示，在创建订单前可以调用该API计算价格，物流费用等。ISV使用该API可以当作订单展示功能使用
 */
class AlibabaTradeGeneralPreorder
{
	private $apiParas = array();
	
	public function __construct($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}
	
	public function setGoods($list){
		$this->apiParas["goods"] = json_encode($list);
	}
	
	public function setReceiveAddress($address){
		$this->apiParas["receiveAddress"] = json_encode($address);
	}
	
	public function getUrl()
	{
		return "com.alibaba.trade/alibaba.trade.general.preorder";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
?>
