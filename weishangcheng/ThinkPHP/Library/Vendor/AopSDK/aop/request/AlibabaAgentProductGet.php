<?php
/**
 * AOP API: alibaba.product.get request
 * 由商品ID获取商品详细信息，接口能获取非授权用户的商品信息。该接口需要向阿里巴巴申请权限才能访问。注意：该接口不能查询零售通商品。
 */
class AlibabaAgentProductGet
{
	private $apiParas = array('webSite' => '1688');
	
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
		return "com.alibaba.product/alibaba.agent.product.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
}
?>
