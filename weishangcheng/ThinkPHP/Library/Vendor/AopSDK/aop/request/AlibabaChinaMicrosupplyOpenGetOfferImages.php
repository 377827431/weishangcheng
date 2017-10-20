<?php
/**
 * AOP API: alibaba.product.get request
 * 获取产品信息
 */
class AlibabaChinaMicrosupplyOpenGetOfferImages
{
	private $apiParas = array();
	
	public function __construct ($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}
	
	public function setOfferId($offerId){
		$this->apiParas["offerId"] = $offerId;
	}
	
	public function getUrl()
	{
		return "com.alibaba.commissionSale.microsupply/alibaba.china.microsupply.open.getOfferImages";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
}
?>
