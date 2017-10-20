<?php
/**
 * AOP API: alibaba.logistics.freightTemplate.getList request
 * 获取运费模板列表
 */
class AlibabaLogisticsFreightTemplateGetList
{
	private $apiParas = array();
	
	public function __construct ($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}
	
	public function setWebSite($webSite){
		$this->apiParas["webSite"] = $webSite;
	}
	
	public function getUrl()
	{
		return "com.alibaba.product/alibaba.logistics.freightTemplate.getList";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
}
?>
