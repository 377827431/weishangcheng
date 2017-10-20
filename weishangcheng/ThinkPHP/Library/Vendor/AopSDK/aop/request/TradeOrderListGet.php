<?php
/**
 * AOP API: trade.order.orderDetail.get request
 * 交易订单详情
 */
class TradeOrderListGet
{
	private $apiParas = array();
	
	public function __construct ($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}
	
	public function orderIdSet($orderId){
		$this->apiParas["orderIdSet"] = $orderId;
	}

	public function buyerMemberId($buyerMemberId){
		$this->apiParas["buyerMemberId"] = $buyerMemberId;
	}
	
	public function getUrl()
	{
		return "cn.alibaba.open/trade.order.list.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
}
?>
