<?php
/**
 * AOP API: alibaba.product.get request
 * 获取产品信息
 */
class AlibabaProductGet
{
	private $apiParas = array();
	
	public function __construct ($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}
	
	public function setProductID($productID){
		$this->apiParas["productID"] = $productID;
	}
	
	public function setWebSite($webSite){
		$this->apiParas["webSite"] = $webSite;
	}
	
	public function getUrl()
	{
		return "com.alibaba.commissionSale/alibaba.product.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
}
?>
