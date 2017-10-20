<?php
/**
 * AOP API: alibaba.china.microsupply.open.order request
 * 微供开放平台下单接口
 */
class AlibabaChinaMicrosupplyOpenOrder
{
	private $apiParas = array();
	
	public function __construct ($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}
	
	public function setMicroSupplyMakeOrderInputModels($microSupplyMakeOrderInputModels){
		$this->apiParas["microSupplyMakeOrderInputModels"] = $microSupplyMakeOrderInputModels;
	}
	
	public function getUrl()
	{
		return "com.alibaba.commissionSale.microsupply/alibaba.china.microsupply.open.order";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
}
?>
