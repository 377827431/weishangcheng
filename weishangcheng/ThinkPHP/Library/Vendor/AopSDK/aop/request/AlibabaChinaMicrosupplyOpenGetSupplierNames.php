<?php
/**
 * AOP API: alibaba.china.microsupply.open.getSupplierNames request
 * 获取微供供应商列表
 */
class AlibabaChinaMicrosupplyOpenGetSupplierNames
{
	private $apiParas = array();
	
	public function __construct ($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}

	public function getUrl()
	{
		return "com.alibaba.commissionSale.microsupply/alibaba.china.microsupply.open.getSupplierNames";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
?>
