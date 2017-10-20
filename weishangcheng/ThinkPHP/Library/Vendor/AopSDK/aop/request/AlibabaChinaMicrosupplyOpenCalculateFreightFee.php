<?php
/**
 * AOP API: alibaba.china.microsupply.open.calculateFreightFee request
 * 微供开放平台运费计算接口
 */
class AlibabaChinaMicrosupplyOpenCalculateFreightFee
{
	private $apiParas = array();
	
	public function __construct ($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}
	
	public function setOrderInputModel($orderInputModel){
		$this->apiParas["orderInputModel"] = $orderInputModel;
	}
	
	public function setCityCode($cityCode){
		$this->apiParas["cityCode"] = $cityCode;
	}
	
	public function setDistrictCode($districtCode){
		$this->apiParas["districtCode"] = $districtCode;
	}
	
	public function getUrl()
	{
		return "com.alibaba.commissionSale.microsupply/alibaba.china.microsupply.open.calculateFreightFee";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
}
?>
