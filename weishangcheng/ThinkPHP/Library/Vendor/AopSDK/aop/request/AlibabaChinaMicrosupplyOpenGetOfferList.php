<?php
/**
 * AOP API: alibaba.china.microsupply.open.getOfferList request
 * 根据指定供应商获取产品信息
 */
class AlibabaChinaMicrosupplyOpenGetOfferList
{
	private $apiParas = array();
	
	public function __construct ($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}
	
	public function setMarketSupplierLoginId($marketSupplierLoginId){
		$this->apiParas["marketSupplierLoginId"] = $marketSupplierLoginId;
	}
	
	public function setOffset($offset){
		$this->apiParas["offset"] = $offset;
	}
	
	public function setLimit($limit){
		$this->apiParas["limit"] = $limit;
	}
	
	public function getUrl()
	{
		return "com.alibaba.commissionSale.microsupply/alibaba.china.microsupply.open.getOfferList";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
}
?>
