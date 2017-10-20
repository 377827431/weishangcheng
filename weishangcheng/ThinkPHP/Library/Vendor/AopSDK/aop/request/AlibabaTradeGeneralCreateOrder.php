<?php
/**
 * 创建大市场订单，支持支付宝担保交易，担保交易、账期交易和交易4.0
 */
class AlibabaTradeGeneralCreateOrder
{
	private $apiParas = array();
	
	public function __construct($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}
	
	public function setCargoGroups($groups){
		$this->apiParas["cargoGroups"] = json_encode($groups);
	}
	
	public function setOtherInfoGroup($info){
	    $this->apiParas["otherInfoGroup"] = json_encode($info);
	}
	
	public function setReceiveAddress($address){
		$this->apiParas["receiveAddressGroup"] = json_encode($address);
	}
	
	public function getUrl()
	{
		return "com.alibaba.trade/alibaba.trade.general.CreateOrder";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
?>
