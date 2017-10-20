<?php
/**
 * AOP API: alibaba.product.get request
 * 获取产品信息
 */
class OffGet
{
	private $apiParas = array();
	
	public function __construct ($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}
	
	public function setOfferId($offerId){
		$this->apiParas["offerId"] = $offerId;
	}
	
	public function setReturnFields($returnFields = 'skuArray'){
		$this->apiParas["returnFields"] = $returnFields;
	}
	
	public function getUrl()
	{
		return "cn.alibaba.open/offer.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
}
?>
