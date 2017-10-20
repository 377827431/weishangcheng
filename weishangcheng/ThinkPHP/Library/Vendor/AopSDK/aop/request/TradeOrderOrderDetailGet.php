<?php
/**
 * AOP API: trade.order.orderDetail.get request
 * 交易订单详情
 */
class TradeOrderOrderDetailGet
{
	private $apiParas = array();
	
	public function __construct ($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}
	
	public function setOrderId($orderId){
		$this->apiParas["orderId"] = $orderId;
	}
	
	public function getUrl()
	{
		return "cn.alibaba.open/trade.order.orderDetail.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
}
?>
