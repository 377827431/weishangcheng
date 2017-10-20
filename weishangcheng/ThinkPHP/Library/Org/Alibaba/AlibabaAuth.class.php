<?php

namespace Org\Alibaba;

class AlibabaAuth {
    private $apiURL = 'https://gw.open.1688.com';
    // private $clientId = 4157308;
    // private $secret = 'kVhHHhfWu1';
    private $clientId = 9264641;
    private $secret = 'PFOFA7NYWhPP';
    private $aliid = '3027680123';
    
    function __construct($aliid = null,$clientId = null,$secret = null){
        if(is_numeric($aliid) && $aliid>0){
            $this->aliid = $aliid;
        }
        if(is_numeric($clientId) && $clientId>0){
            $this->clientId = $clientId;
        }
        if($secret!=null && $secret!=''){
            $this->secret = $secret;
        }
    }
    
    public function getSign($redirect){
        $param = array(
            'redirect_uri' => $redirect,
            'state'        => 'wslm',
            'client_id'    => $this->clientId,
            'site'         => 'china'
        );
        $sign = $this->createSign($param);
        return $sign;
    }
    /**
     * 跳转到alibaba授权
     */
    public function redirectAuth($redirect){
        $param = array(
        //     'redirect_uri' => $redirect,
        //     'state'        => 'wslm',
        //     'client_id'    => $this->clientId,
        //     'site'         => 'china'
            'appKey'    => $this->clientId,
            '_aop_timestamp'    => time().'000',
        );
        
        $s = 'param2/1/system.oauth2/cybLogin/'.$this->clientId;

        $param['_aop_signature'] = $this->createSign($param,$s);
        // $redirect = 'https://gw.open.1688.com/auth/authorize.htm?'.http_build_query($param);
        $redirect = 'https://gw.open.1688.com/openapi/'.$s.'?'.http_build_query($param);
        return $redirect; 
    }
    
    /**
     * 授权拿到code后，覆盖授权信息
     * @param string $code
     * @param string $state
     */
    public function setAuth($code, $state = ''){
        $param = array(
            'code'               =>  $code,
            'grant_type'         => 'authorization_code',
            'need_refresh_token' => 'true',
            'client_id'          => $this->clientId,
            'client_secret'      => $this->secret,
            'redirect_uri'       => $_SERVER['HTTP_HOST']
        );
    
        $url = $this->apiURL.'/openapi/http/1/system.oauth2/getToken/'.$this->clientId;
        $response = self::http($url, $param);
        $response = json_decode($response);
        
        $data = array(
            'id'             => isset($response->aliId)?$response->aliId:$response->userId,
            'member_id'      => $response->memberId,
            'login_id'       => $response->resource_owner,
            'access_token'   => $response->access_token,
            'access_timeout' => NOW_TIME + $response->expires_in,
            'refresh_token'  => $response->refresh_token,
            'refresh_timeout'=> strtotime(substr($response->refresh_token_timeout, 0, 14)),
            'userId'         => $response->aliId,
        );
        
        $access_timeout = NOW_TIME + $response->expires_in - 300;
        $refresh_timeout= strtotime(substr($response->refresh_token_timeout, 0, 14));
        $sql = "INSERT INTO alibaba_token SET
				  id='{$data['id']}',
				  member_id='{$response->memberId}',
				  login_id='{$response->resource_owner}',
				  access_token='{$response->access_token}',
				  access_timeout='{$access_timeout}',
				  refresh_token='{$response->refresh_token}',
				  refresh_timeout='{$refresh_timeout}',
                  userId='{$response->aliId}'
				ON DUPLICATE KEY UPDATE
				  member_id=VALUES(member_id),
				  login_id=VALUES(login_id),
				  access_token=VALUES(access_token),
				  access_timeout=VALUES(access_timeout),
				  refresh_timeout=VALUES(refresh_timeout),
                  userId=VALUES(userId)";

        M()->execute($sql);
        S('ali_token_'.$data['id'], $data['access_token'], $response->expires_in - 300);
        return $data;
    }
    
    protected function api($name, $param = '', $method = 'GET'){
        $s = 'param2'.'/'.$name.'/'.$this->clientId;
        $token = $this->getAccessToken();
        $params = array('access_token' => $token, 'webSite' => '1688');
        if(!empty($param) && is_array($param)){
            $params = array_merge($param, $params);
        }
        $params['_aop_signature'] = $this->createSign($params, $s);
    
        $url  = $this->apiURL.'/openapi/'.$s;
    
        $response = self::http($url, $params, '', $method);
        $response = json_decode($response, true, 512, JSON_BIGINT_AS_STRING);
        
        if($response['error_code']){
            return array(
                'errcode' => $response['error_code'],
                'errmsg'  => $response['error_message']
            );
        }else if($response['errorCode']){
            return array(
                'errcode' => $response['errorCode'],
                'errmsg'  => $response['errorMessage']
            );
        }else if($response['msgCode']){
            return array(
                'errcode' => $response['msgCode'],
                'errmsg'  => $response['msgInfo']
            );
        }else{
            return $response;
        }
    }
    
    protected static function http($url, $param, $data = '', $method = 'GET'){
        $opts = array(
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_URL            => $url
        );
    
        /* 根据请求类型设置特定参数 */
        if(!empty($param)){
            $opts[CURLOPT_URL] .= '?' . http_build_query($param);
        }
    
        if(strtoupper($method) == 'POST'){
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $data;
    
            if(is_string($data)){ //发送JSON数据
                $opts[CURLOPT_HTTPHEADER] = array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($data),
                );
            }
        }
    
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
    
        //发生错误，抛出异常
        if($error) throw new \Exception('请求发生错误：' . $error);
    
        return  $data;
    }
    
    /**
     * 获取accesstoken
     * @param unknown $memberId
     * @return mixed|object|unknown
     */
    public function getAccessToken($aliid= null){
        if(!is_numeric($aliid)){
            $aliid= $this->aliid;
        }
        $token = S('ali_token_'.$aliid);
        if($token){
            return $token;
        }
        
        $Model = M('alibaba_token');
        $data = $Model->find($aliid);
        if(empty($data)){
            E('店铺TOKEN不存在：请重新授权');
        }else if(NOW_TIME >= $data['refresh_timeout']){
            E('店铺TOKEN已过期：请重新授权');
        }

        $param = array(
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->clientId,
            'client_secret' => $this->secret,
            'refresh_token' => $data['refresh_token']
        );
        $url = $this->apiURL.'/openapi/param2/1/system.oauth2/getToken/'.$this->clientId;
        $response = self::http($url, $param);
        $response = json_decode($response, true);
        
        $Model->where("id='{$aliid}'")->save(array(
            'access_token' => $response['access_token'],
            'access_timeout' => NOW_TIME + $response['expires_in']
        ));
            
        S('ali_token_'.$aliid, $response['access_token'], $response['expires_in'] - 600);
        return $response['access_token'];
    }
    
    private function createSign($param, $s = ''){
        ksort($param);
        $factor = '';
        foreach ($param as $k=>$v){
            $factor .= $k.$v;
        }
        return strtoupper(bin2hex(hash_hmac("sha1", $s.$factor, $this->secret, true)));
    }
    
    /**
     * 根据offerId获取商品信息
     * @param long $offerId
     */
    public function getGoods($id, $type = 2){
        $version = 1;
        $namespace = 'com.alibaba.product';
        $function = 'alibaba.agent.product.get';
        $param = array(
            'productID' => $id
        );
        
        // 微供
        if($type == 3){
            $namespace = 'com.alibaba.commissionSale';
            $function = 'alibaba.product.get';
        }

        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url, $param);
        
        if($type == 3){
            return $response['result'];
        }
        return $response;
    }
    
    /**
     * 批量转换loginId到memberId
     * @param array/string $loginIds
     * @return array/string
     */
    public function convertToMemberId($loginIds){
        $version = 1;
        $namespace = 'cn.alibaba.open';
        $function = 'convertMemberIdsByLoginIds';
        
        $isArray = false;
        if(is_array($loginIds)){
            $isArray = true;
            $loginIds = json_encode($loginIds, JSON_UNESCAPED_UNICODE);
        }else{
            $loginIds = '["'.$loginIds.'"]';
        }
        
        $param = array('loginIds' => $loginIds);
    
        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url, $param);
        return $isArray ? $response : current($response);
    }

    /**
     * 批量转换memberId到loginId
     * @param array/string $loginIds
     * @return array/string
     */
    public function convertToLoginId($memberIds){
        $version = 1;
        $namespace = 'cn.alibaba.open';
        $function = 'convertLoginIdsByMemberIds';
        $param = array(
            'memberIds' => is_array($memberIds) ? $memberIds : array($memberIds)
        );
    
        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url, $param);
        return $response;
    }
    
    /**
     * 查询卖家混批设置
     * @param string $memberId
     */
    public function getMixConfig($memberId){
        $version = 1;
        $namespace = 'com.alibaba.trade';
        $function = 'alibaba.trade.OpQueryMarketingMixConfig';
        $param = array(
            'sellerMemberId' => $memberId
        );
        
        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url, $param);
        if(isset($response['errcode'])){
            return $response;
        }
        
        $data = array('generalHunpi' => 0, 'mixAmount' => 0, 'mixNumber' => 0);
        if(isset($response['result'])){
            return array_merge($data, $response['result']);
        }
        return $response;
    }
    
    /**
     * 创建订单(大市场)
     * @param unknown $cargoGroups
     * @param unknown $otherInfoGroup
     * @param unknown $receiveAddressGroup
     * @param array $invoiceGroup
     */
    public function createOrder($cargoGroups, $otherInfoGroup, $receiveAddressGroup, $invoiceGroup = null){
        $version = 1;
        $namespace = 'com.alibaba.trade';
        $function = 'alibaba.trade.general.CreateOrder';
        
        $isDaiXiao = $otherInfoGroup['is_daixiao'];
        unset($otherInfoGroup['is_daixiao']);
        if($isDaiXiao){
            $function = 'alibaba.trade.saleproxy.CreateOrder';
        }
        
        $param = array(
            'cargoGroups' => json_encode($cargoGroups),
            'otherInfoGroup' => json_encode($otherInfoGroup),
            'receiveAddressGroup' => json_encode($receiveAddressGroup)
        );
        
        if($invoiceGroup){
            $param['invoiceGroup'] = json_encode($invoiceGroup);
        }
        
        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url, $param, 'POST');
        
        if(isset($response['errcode'])){
            $response['id'] = null;
            return $response;
        }
        
        $tid = $response['result']['commitResults'][0]['orderId'];
        return array(
            'errcode' => $tid ? '' : 'error',
            'errmsg'  => $tid ? '' : '下单失败',
            'id'      => $tid
        );
    }
    
    /**
     * 获取供应商
     */
    public function getSupplier(){
        $version = 1;
        $namespace = 'com.alibaba.commissionSale.microsupply';
        $function = 'alibaba.china.microsupply.open.getSupplierNames';
        
        $param = array();
        
        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url, $param);
        if(isset($response['model'])){
            return $response['model'];
        }
        return $response;
    }
    
    /**
     * 获取类目
     */
    public function category($id = 0){
        $version = 1;
        $namespace = 'com.alibaba.product';
        $function = 'alibaba.category.get';
        
        $param = array('categoryID' => $id, 'webSite' => '1688');
        
        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url, $param);
        
        if(isset($response['categoryInfo'])){
            return $response['categoryInfo'];
        }
        return $response;
    }
    
    /**
     * 是否为1688代销产品
     */
    public function isDaiXiao($id, $specId = null){
        $version = 1;
        $namespace = 'com.alibaba.trade';
        $function = 'alibaba.trade.saleproxy.preorder';
        
        $param = array(
            'goods' => json_encode(array(array(
                'id'      => ''.$id,
                'offerId' => ''.$id,
                'quantity'=> 1,
                'specId'  => $specId
            ))),
            'receiveAddress'  => json_encode(array(
                'address'     => '江陵路567号新东方国际科技中心1508室',
                'addressCode' => "330108",
                'fullName'    => '技术测试是否为代销关系',
                'mobile'      => "18968001693",
            ))
        );
        
        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url, $param);
        if(isset($response['errcode'])){
            return $response;
        }
        
        $seller = $response['result']['orderModels'][0]['orderModel']['seller'];
        return array(
            'success' => isset($response['result']['resultCode']) ? $response['result']['resultCode']['success'] : 1,
            'result_code' => isset($response['result']['resultCode']) ? $response['result']['resultCode']['resultCode'] : 0,
            'message' => isset($response['result']['resultCode']) ? $response['result']['resultCode']['message'] : '已代销此产品',
            'buyer'  => array(
                'member_id' => $response['result']['buyer']['memberId'],
                'name' => $response['result']['buyer']['name'],
                'user_id' => $response['result']['buyer']['userId'],
            ),
            'seller' => array(
                'login_id'  => $seller['loginId'],
                'member_id' => $seller['memberId'],
                'mobile'    => $seller['mobile'],
                'company_name' => $seller['companyName'],
            ),
        );
    }
    
    /**
     * 获取微供商品运费
     */
    public function getWGExpressFee($model, $cityCode, $districtCode){
        $version = 1;
        $namespace = 'com.alibaba.commissionSale.microsupply';
        $function = 'alibaba.china.microsupply.open.calculateFreightFee';

        $param = array(
            'cityCode' => $cityCode,
            'districtCode' => $districtCode,
            'orderInputModel' => json_encode($model)
        );
        
        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url, $param);
        if(isset($response['success'])){
            return array(
                'errcode' => '',
                'freight_fee' => $response['model']['totalFreightFee'],
                'total_fee' => $response['model']['totalFee']
            );
        }
        
        $response['errmsg'] = '[运费]'.$response['errmsg'];
        return $response;
    }
    
    /**
     * 微供下单
     * @param unknown $model
     * @param string $express
     * @return string[]|string[]|unknown[]|mixed[]|string[]|unknown[]|mixed[]|string[]|unknown[]|mixed[]
     */
    public function orderToWeiGong($model, $express = true){
        $return = array('errcode' => '', 'errmsg' => '', 'id' => null);
        
        // 需要计算运费
        if($express){
            $expressModel = array();
            foreach ($model['offerViewItems'] as $item){
                $_model = array(
                    'offerId'   => $item['offerId'],
                    'quantity'  => $item['buyDetails']['amount'],
                    'unitPrice' => $item['buyDetails']['price'],
                    'freightId' => $item['freightId'],
                );
                if($item['buyDetails']['specId']){
                    $_model['specId'] = $item['buyDetails']['specId'];
                    $_model['skuId'] = $item['buyDetails']['skuId'];
                }
                $expressModel[] = $_model;
            }
            
            $result = $this->getWGExpressFee($expressModel, $model['addressInfoModel']['cityCode'], $model['addressInfoModel']['districtCode']);
            if($result['errcode']){
                $return['errcode'] = $result['errcode'];
                $return['errmsg'] = $result['errmsg'];
                return $return;
            }
            $model['totalFreightFee'] = $result['freight_fee'];
        }
        
        // 下单
        $version = 1;
        $namespace = 'com.alibaba.commissionSale.microsupply';
        $function = 'alibaba.china.microsupply.shop.order';
        $param = array(
            'microSupplyMakeOrderInputModels' => json_encode($model),
        );
        
        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url, $param);
        
        if(isset($response['model'])){
            $result = json_decode($response['model'][0], true, 512, JSON_BIGINT_AS_STRING);
            if($result['success']){
                $return['id'] = $result['orderId'];
                return $return;
            }
        }

        $response['errmsg'] = '[下单失败]'.$response['errmsg'];
        return $response;
    }
    
    /**
     * 订单下单预览
     */
    public function orderPreview($list, $address, $isDaiXiao = false){
        // 下单
        $version = 1;
        $namespace = 'com.alibaba.trade';
        $function = 'alibaba.trade.general.preorder';
        
        if($isDaiXiao){
            $namespace = 'com.alibaba.trade';
            $function = 'alibaba.trade.saleproxy.preorder';
        }
        
        $param = array(
            'goods'          => json_encode($list),
            'receiveAddress' => json_encode(array(
                'address'    => $address['receiver_detail'],
                'addressCode'=> $address['county_code'],
                'addressCodeText'=> $address['receiver_county'],
                'fullName'   => $address['receiver_name'],
                'mobile'     => $address['receiver_mobile'],
                'postCode'   => $address['receiver_zip']
            ))
        );
        
        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url, $param);
        if($response['errcode']){
            return $response;
        }
        
        // 按offerId分组，方便下面组合订单
        $result = array('address' => array(
            'address'  => $address['receiver_detail'],
            'areaCode' => $address['county_code'],
            'areaText' => $address['receiver_county'],
            'cityCode' => $address['city_code'],
            'cityText' => $address['receiver_city'],
            'fullName' => $address['receiver_name'],
            'mobile'   => $address['receiver_mobile'],
            'postCode' => $address['receiver_zip'],
            'provinceCode' => $address['province_code'],
            'provinceText' => $address['receiver_province']
        ), 'trades' => array());
        
        foreach ($response['result']['orderModels'] as $trade){
        	$cargos = $offers = $products = array();
        	foreach ($trade['orderModel']['cargos'] as $cargo){
        		$offerId = $cargo['offerId'];
        		if(!in_array($offerId, $offers)){
        			$offers[] = $offerId;
        			foreach ($list as $item){
        				if($item['offerId'] != $offerId){
        					continue;
        				}
        				
        				$products[$item['oid']] = array('tao_id' => $offerId, 'spec_id' => $item['specId'], 'sku_id' => $item['skuId']);
        				
        				unset($item['oid']);
        				$item['unitPrice'] = $cargo['unitPrice'];
        				$cargos[] = $item;
        			}
            	}
            }
            
            $sumCarriage = bcdiv($trade['orderModel']['sumCarriage'], 100, 2);
            $result['trades'][] = array(
                'group'      => $trade['orderModel']['group'],
                'loginId'    => $trade['orderModel']['seller']['loginId'],
                'memberId'   => $trade['orderModel']['seller']['memberId'],
                'tradeWay'   => $trade['orderModel']['tradeModeModel']['curSelectedTradeMode']['tradeWay'],
                'tradeMode'  => $trade['orderModel']['tradeModeModel']['curSelectedTradeMode']['tradeMode'],
                'sumPayment' => bcdiv($trade['orderModel']['sumPayment'], 100, 2),
                'sumCarriage'=> $sumCarriage,
                'sumPaymentNoCarriage' => bcdiv($trade['orderModel']['sumPaymentNoCarriage'], 100, 2),
            	'cargoGroups'=> $cargos,
            	'products'   => $products
            );
            
            $result['sumCarriage'] = bcadd($result['sumCarriage'], $sumCarriage, 2);
        }
        
        return $result;
    }
    
    /**
     * 根据标题搜索1688商品
     * @param string q 搜索关键词
     * @param int page 分页页码
     * @return mixed
     */
    public function searchGoods($q, $page = 0, $size=10){
        $version = 1;
        $namespace = 'cn.alibaba.open';
        $function = 'offer.search';
        $param = array(
            'pageNo' => $page,
            'pageSize' => $size,
        );
        if (!empty($q)) {
            $param['q'] = $q;
        } else {
            $param['quantityBegin'] = '1~1';
        }

        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url, $param);
        return $response['result'];
    }
    
    /**
     * 获取店铺下的商品
     */
    public function getShopGoods($loginId, $offset=0, $limit = 100){
        $version = 1;
        $namespace = 'com.alibaba.commissionSale.microsupply';
        $function = 'alibaba.china.microsupply.open.getOfferList';
        
        $param = array(
            'marketSupplierLoginId' => $loginId,
            'offset'                => $offset,
            'limit'                 => $limit
        );
        
        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url, $param);
        
        if($response['success'] == 1){
            return $response['model'];
        }
        return $response;
    }
    
    /**
     * 获取微供九宫图
     */
    public function getWXNineImage($offerId){
    	$version = 1;
    	$namespace = 'com.alibaba.commissionSale.microsupply';
    	$function = 'alibaba.china.microsupply.open.getOfferImages';
    	
    	$param = array('offerId' => $offerId);
    	
    	$url = $version.'/'.$namespace.'/'.$function;
    	$response = $this->api($url, $param);
    	
    	if($response['success'] == 1){
    		return $response['model']['__MAIN_IMAGES'];
    	}
    	return $response;
    }
    
    /**
     * 获取订单列表
     */
    public function getOrderList($where){
    	$version   = 1;
    	$namespace = 'com.alibaba.trade';
    	$function  = 'alibaba.trade.getBuyerOrderList';
    	
    	$param = array('page' => $where['page']);
    	if(isset($where['size'])){ // 最多50条
    		$param['pageSize'] = $where['size'];
    	}if(isset($where['status'])){
    		//订单状态，值有 success, cancel(交易取消，违约金等交割完毕), waitbuyerpay(等待卖家付款)， waitsellersend(等待卖家发货), waitbuyerreceive(等待买家收货 )
    		$param['orderStatus'] = $where['status'];
    	}if(isset($where['modify_end'])){
    		$param['modifyEndTime'] = $this->toJavaUtilDate($where['modify_end']);
    	}if(isset($where['modify_start'])){
    		$param['modifyStartTime'] = $this->toJavaUtilDate($where['modify_start']);
    	}if(isset($where['create_end'])){
    		$param['createEndTime'] = $this->toJavaUtilDate($where['create_end']);
    	}if(isset($where['create_start'])){
    		$param['createStartTime'] = $this->toJavaUtilDate($where['create_start']);
    	}
    	
    	$url = $version.'/'.$namespace.'/'.$function;
    	$response = $this->api($url, $param);
    	print_data($response);
    	
    	if($response['success'] == 1){
    		return $response['model']['__MAIN_IMAGES'];
    	}
    	return $response;
    }
    
    private function toJavaUtilDate($time){
    	if(is_numeric($time)){
    		$time = date('YmdHis', $time).'000+0800';
    	}else if(is_string($time)){
    		$time = date('YmdHis', strtotime($time)).'000+0800';
    	}
    	return $time;
    }
    
    /**
     * 获取物流信息
     */
    public function getLogisticsInfos($orderId, $fields = 'company.name,receiver'){
    	$version   = 1;
    	$namespace = 'com.alibaba.logistics';
    	$function  = 'alibaba.trade.getLogisticsInfos.buyerView';
    	
    	$param = array('orderId' => $orderId, 'fields' => $fields, 'webSite' => '1688');
    	
    	$url = $version.'/'.$namespace.'/'.$function;
    	$response = $this->api($url, $param);
    	
    	if($response['result']){
    		return $response['result'];
    	}
    	return $response;
    }

    /*
     * 获取已经建立代销关系的供应商名称
     */
    public function getSupplierNames(){
        $version = 1;
        $namespace = 'com.alibaba.commissionSale.microsupply';
        $function = 'alibaba.china.microsupply.open.getSupplierNames';
        $url = $version.'/'.$namespace.'/'.$function;
        $response = $this->api($url);
        return $response;
    }
    /*
     * 根据供应商的名称获取该供应商的offerId列表
     * 这是一个分页接口，需要提供起始位置和获取的数量。一次最多获取100条
     */
    public function getOfferList($LoginId,$offset=0,$limit=100){
        $version = 1;
        $namespace = 'com.alibaba.commissionSale.microsupply';
        $function = 'alibaba.china.microsupply.open.getOfferList';
        $url = $version.'/'.$namespace.'/'.$function;
        $param = array(
            'marketSupplierLoginId' => $LoginId,
            'offset'                => $offset,
            'limit'                 => $limit
            );
        $response = $this->api($url,$param);
        return $response;
    }
    /*
     * 获取运费模板列表
     * 1688有两类特殊运费模板，不在此接口返回：不传运费模板表示使用运费说明；传入1表示卖家承担运费
     */
    public function getFreightTemplateList(){
        $version = 1;
        $namespace = 'com.alibaba.product';
        $function = 'alibaba.logistics.freightTemplate.getList';
        $url = $version.'/'.$namespace.'/'.$function;
        $param = array('webSite'=>'1688');
        $response = $this->api($url,$param);
        return $response;
    }
    /*
     * 代销场景下获取产品信息的通用API, 可支持用户A获取用户B的产品信息。
     */
    public function getProduct($productID){
        $version = 1;
        $namespace = 'com.alibaba.commissionSale';
        $function = 'alibaba.product.get';
        $url = $version.'/'.$namespace.'/'.$function;
        $param = array('productID'=>$productID,'webSite'=>'1688');
        $response = $this->api($url,$param);
        return $response;
    }
    /*
     * 查询当前会话会员的交易订单详情
     * 获取的物流信息中，logisticsBillNo为物流运单号即快递单单号、logisticsOrderNo为阿里内部的物流订单编号、logisticsId为阿里内部的物流ID
     */
    public function getOrderDetail($orderId){
        $version = 1;
        $namespace = 'cn.alibaba.open';
        $function = 'trade.order.orderDetail.get';
        $url = $version.'/'.$namespace.'/'.$function;
        $param = array('orderId'=>$orderId);
        $response = $this->api($url,$param);
        return $response;
    }
    /*
     * ISV对用户下的订单按照一个买家和一个卖家为一组进行拆单，将订单批量提交
     */
    public function MakeOrderInputModels($model, $express = true){
        $return = array('errcode' => '', 'errmsg' => '', 'id' => null);
        
        // 需要计算运费
        if($express){
            $expressModel = array();
            foreach ($model['offerViewItems'] as $item){
                $_model = array(
                    'offerId'   => $item['offerId'],
                    'quantity'  => $item['buyDetails']['amount'],
                    'unitPrice' => $item['buyDetails']['price'],
                    'freightId' => $item['freightId'],
                );
                if($item['buyDetails']['specId']){
                    $_model['specId'] = $item['buyDetails']['specId'];
                    $_model['skuId'] = $item['buyDetails']['skuId'];
                }
                $expressModel[] = $_model;
            }
            
            $result = $this->getWGExpressFee($expressModel, $model['addressInfoModel']['cityCode'], $model['addressInfoModel']['districtCode']);
            if($result['errcode']){
                $return['errcode'] = $result['errcode'];
                $return['errmsg'] = $result['errmsg'];
                return $return;
            }
            $model['totalFreightFee'] = $result['freight_fee'];
        }
        
        $version = 1;
        $namespace = 'com.alibaba.commissionSale.microsupply';
        $function = 'alibaba.china.microsupply.shop.order';
        $url = $version.'/'.$namespace.'/'.$function;
        $param = array('microSupplyMakeOrderInputModels'=>json_encode($model,JSON_UNESCAPED_UNICODE));
        $response = $this->api($url,$param);
        if(isset($response['model'])){
            $result = json_decode($response['model'][0], true, 512, JSON_BIGINT_AS_STRING);
            if($result['success']){
                $return['id'] = $result['orderId'];
                return $return;
            }
        }

        $response['errmsg'] = '[下单失败]'.$response['errmsg'];
        return $response;
    }
    /*
     * 同步1688用户在下游电商平台的支持情况(包括电商平台和店铺级别的标识)
     */
    public function syncUserPlatform($userPlatformDetails){
        $userPlatformDetails['partnerId'] = $this->clientId;//合作伙伴ID,比如APPKEY
        $version = 1;
        $namespace = 'com.alibaba.product.push';
        $function = 'alibaba.product.push.microSupply.syncUserPlatform';
        $url = $version.'/'.$namespace.'/'.$function;
        $param = array('userPlatformDetails' => json_encode($userPlatformDetails));
        $response = $this->api($url,$param);
        return $response;
    }
    /*
     * 同步铺货结果给1688
     */
    public function syncPushProductResult($pushProductResults){
        $version = 1;
        $namespace = 'com.alibaba.product.push';
        $function = 'alibaba.product.push.microSupply.syncPushProductResult';
        $url = $version.'/'.$namespace.'/'.$function;
        $param = array('pushProductResults' => json_encode($pushProductResults));
        $response = $this->api($url,$param);
        return $response;
    }
    /*
     * 获取中文站地址库。该API需要申请成为微供解决方案提供者才能使用
     * 外部用户下单时需要使用1688的地址库来填写自己的收货地址，第三方可以通过地址库接口获取1688地址库
     */
    public function getChinaAddress(){
        $version = 1;
        $namespace = 'com.alibaba.commissionSale.microsupply';
        $function = 'alibaba.china.microsupply.open.getChinaAddress';
        $url = $version.'/'.$namespace.'/'.$function;
        $param = array();
        $response = $this->api($url,$param);
        return $response;
    }
    /*
     * 查询单个订单详情，新版的查询详情接口，不含隐私数据，给支付宝调用，开放收货人地址、联系方式、姓名。
     * ISV通过接口获取订单状态，如物流信息等
     */
    public function getTradeDetail($orderId){
        $version = 1;
        $namespace = 'cn.alibaba.open';
        $function = 'trade.order.detail.get';
        $url = $version.'/'.$namespace.'/'.$function;
        $param = array(
            'id'                     => $orderId,//订单号
            // 'needOrderEntries'       => true,//是否需要订单明细true/false
            // 'needInvoiceInfo'        => true,//是否需要发票信息true/false
            // 'needOrderMemoList'      => true,//是否需要订单备注true/false
            // 'needLogisticsOrderList' => true,//是否需要物流单信息true/false
            );
        $response = $this->api($url,$param);
        if(isset($response['orderModel'])){
            $response = $response['orderModel'];
        }
        return $response;
    }

    /*
     * 查询订单物流信息（1688买家看）
     */
    public function getLogisticsTraceInfo($orderId){
        $version = 1;
        $namespace = 'com.alibaba.logistics';
        $function = 'alibaba.trade.getLogisticsTraceInfo.buyerView';
        $url = $version.'/'.$namespace.'/'.$function;
        $param = array(
            'orderId' => $orderId,
            'webSite' => '1688'
        );
        $response = $this->api($url,$param);
        return $response;
    }
}
?>