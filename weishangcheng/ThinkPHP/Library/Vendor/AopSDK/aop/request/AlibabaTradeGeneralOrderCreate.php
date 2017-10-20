<?php
/**
 * AOP API: alibaba.product.get request
 * 获取产品信息
 */
class AlibabaTradeGeneralOrderCreate
{
	private $apiParas = array();
	
	public function __construct ($access_token)
	{
		$this->apiParas["access_token"] = $access_token;
	}
	
	public function setInvoiceGroup($invoiceGroup){
		$this->apiParas["invoiceGroup"] = $invoiceGroup;
	}

	public function setReceiveAddressGroup($receiveAddressGroup){
		$this->apiParas["receiveAddressGroup"] = $receiveAddressGroup;
	}
	
	public function setOtherInfoGroup($otherInfoGroup){
		$this->apiParas["otherInfoGroup"] = $otherInfoGroup;
	}
	
	public function setCargoGroups($cargoGroups){
		$this->apiParas["cargoGroups"] = $cargoGroups;
	}
	
	public function getUrl()
	{
		return "com.alibaba.trade/alibaba.trade.generalOrder.create";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
}
?>
