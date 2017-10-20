<?php
/**
 * AOP API: alibaba.china.microsupply.open.getChinaAddress request
 * 获取中文站地址库接口
 */
class AlibabaChinaMicrosupplyOpenGetChinaAddress
{
	private $apiParas = array();
	
	public function __construct ($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}

	public function getUrl()
	{
		return "com.alibaba.commissionSale.microsupply/alibaba.china.microsupply.open.getChinaAddress";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
?>
