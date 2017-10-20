<?php
namespace Admin\Model;

use Think\Model;
use Org\IdWork;
use Common\Model\BaseModel;

class AlibabaTradeSubscribe extends BaseModel{
    protected $tableName = 'alibaba_trade';
    private $orderErrors;
    
    // 获取接口权限
    private function getAlibabaAuth($tokenId,$appkey=null,$secret=null){
        static $InterfaceList = array();
        if(!isset($InterfaceList[$tokenId])){
            $InterfaceList[$tokenId] = new \Org\Alibaba\AlibabaAuth($tokenId,$appkey,$secret);
        }
        return $InterfaceList[$tokenId];
    }
    
    /**
     * 创建1688订单
     */
    public function add1688($trade, $orders,$params){
    	//E('项目上线后请删除此行代码，或请使用非正式的1688账号进行测试！');
    	$shop = $this->query("SELECT `name`, aliid AS token_id FROM shop WHERE id='{$trade['seller_id']}'");
    	$shop = $shop[0];
    	if(!$shop['token_id']){if(empty($params)){E('店铺token_id丢失');}else{return '店铺token_id丢失';}}
    	$this->orderErrors = array();
        
    	// 从数据库读取1688商品信息
        $taoIds = '';
        foreach ($orders as $oid=>$item){
        	$taoIds .= $item['tao_id'].',';
        }
        $taoIds = rtrim($taoIds, ',');
        $sql = "SELECT ar.id, ar.relation, goods.cat_id, goods.login_id,
		    		goods.login_id, IF(ar.relation=2, goods.daixiao_price, goods.price) AS price,
		    		goods.unit, goods.freight_id, goods.products, shop.mix_amount, shop.mix_number
	    		FROM alibaba_relation AS ar
	    		INNER JOIN alibaba_goods AS goods ON goods.id=ar.id
				LEFT JOIN alibaba_shop AS shop ON shop.token_id=ar.token_id AND shop.login_id=goods.login_id
	    		WHERE ar.token_id='{$shop['token_id']}' AND ar.id IN ({$taoIds})";
        $list = $this->query($sql);
        if(count($list) == 0){
        	return;
        }

        // 解析1688商品SKU，方便选取数据
        $taoList = $shopMix = array();
        foreach ($list as $i=>$item){
        	$loginId = $shopMix[$item['login_id']];
        	$shopMix[$loginId] = array('mix_amount' => $item['mix_amount'], 'mix_number' => $item['mix_number']);
        	
        	$products = json_decode($item['products'], true);
        	if(!$products){
        		$key = $item['id'].'_';
        		$taoList[$key]   =  array(
        			'id'         => $item['id'],
        			'unit'       => $item['unit'],
        			'price'      => $item['price'],
        			'freight_id' => $item['freight_id'],
        			'sku_id'     => null,
        			'spec_id'    => null,
        			'daixiao'    => $item['price'],
        			'relation'   => $item['relation'],
        			'cat_id'     => $item['cat_id'],
        			'login_id'   => $item['login_id'],
        		);
        		continue;
        	}
        	
        	foreach ($products as $product){
        		$key = $item['id'].'_'.IdWork::convertSKU($product['sku_json']);
        		$taoList[$key]   =  array(
        			'id'         => $item['id'],
        			'unit'       => $item['unit'],
        			'price'      => $product['price'],
        			'freight_id' => $item['freight_id'],
        			'sku_id'     => $product['sku_id'],
        			'spec_id'    => $product['spec_id'],
        			'daixiao'    => $product['daixiao'],
        			'relation'   => $item['relation'],
        			'cat_id'     => $item['cat_id'],
        			'login_id'   => $item['login_id']
        		);
        	}
        }
        
        $trade['receiver_mobile'] = "".$trade['receiver_mobile'];
        // 根据type分组下单(调用接口不同)
        $weigong = $dashichang = $daixiao = array();
        
        foreach($orders as $oid=>$item){
        	$key = $item['tao_id'].'_'.IdWork::convertSKU($item['sku_json']);
        	if(!isset($taoList[$key])){
                if(empty($params)){
            		E('商品SKU丢失');
                }else{
                    return '商品SKU丢失';
                }
        	}
        	$tao = $taoList[$key];

            switch ($tao['relation']){
            	case 1: // 大市场
            		$dashichang[] = array(
            			'cartId'    => $tao['cat_id'],
            			'id'        => $tao['id'],
            			'offerId'   => $tao['id'],
            			'quantity'  => $item['quantity'],
            			'specId'    => $tao['spec_id'],
            			'skuId'     => $tao['sku_id'],
            			'oid'       => $oid
            		);
            		break;
            	case 2: // 一件代发
            		$daixiao[] = array(
            			'cartId'    => $tao['cat_id'],
            			'id'        => $tao['id'],
            			'offerId'   => $tao['id'],
            			'quantity'  => $item['quantity'],
            			'specId'    => $tao['spec_id'],
            			'skuId'     => $tao['sku_id'],
            			'oid'       => $oid
            		);
            		break;
            	case 3: // 微供（一个买家、一个卖家进行分组下单）
            		$wg = $weigong[$tao['login_id']];
            		if(!$wg){
            			if(!isset($trade['city_code'])){
            				$trade = $this->getCityCode($trade);
            			}
            			
            			$wg = array(
            				'supplierLoginId'  => $tao['login_id'],
                            'senderInfo'       => $shop['name'],//代理信息
            				'totalFreightFee'  => 0,
            				'totalProductPrice'=> 0,
            				'products'         => array(),
            				'addressInfoModel' => array(
            					'personalName' => $trade['receiver_name'],
            					'mobileNO'     => $trade['receiver_mobile'],
            					'province'     => $trade['receiver_province'],
            					'city'         => $trade['receiver_city'],
            					'district'     => $trade['receiver_county'],
            					'cityCode'     => $trade['city_code'],
            					'districtCode' => $trade['county_code'],
            					'areaCode'     => $trade['county_code'],
            					'addressDetail'=> $trade['receiver_detail'],
            				),
            				'offerViewItems'   => array()
            			);
            		}
            		
            		// 商品总额
            		$money = bcmul($tao['price'], $item['quantity'], 2);
            		$wg['totalProductPrice'] = bcadd($wg['totalProductPrice'], $money, 2);
            		
            		$wg['offerViewItems'][] = array(
            			'offerId'    => $tao['id'],
            			'freightId'  => $tao['freight_id'],
            			'buyDetails' => array(
            				'type'   => $tao['sku_id'] ? 'SKU' : 'OFFER',
            				'price'  => $tao['price'],
            				'amount' => $item['quantity'],
            				'unit'   => $tao['unit'],
            				'skuId'  => $tao['sku_id'],
            				'specId' => $tao['spec_id']
            			)
            		);
            		
            		$wg['products'][$oid] = array('tao_id' => $tao['id'], 'spec_id' => $tao['spec_id'], 'sku_id' => $tao['sku_id']);
                    $weigong[$tao['login_id']] = $wg;
            		break;
            	default:
            		E('未知商品业务类型:'.$tao['relation']);
            }
        }
        
        $trade['created']   = time();
        if(empty($params)){
            // 微供下单
            $this->orderToWeiGong($shop['token_id'], $trade, $weigong);
        }else{
            // 微供下单
            $result = $this->orderToWeiGong($shop['token_id'], $trade, $weigong,$params);
            return $result;
        }
        // 大市场下单
        $this->orderToDaShiChang($shop['token_id'], $trade, $dashichang, $shopMix);
        // 一件代发下单
        $this->orderToDaiXiao($shop['token_id'], $trade, $daixiao, $shopMix);
    }
    
    /**
     * 获取省市区code
     */
    private function getCityCode($trade){
    	$city = get_city_by_name($trade['receiver_province']);
    	$trade['province_code'] = ''.$city['code'];
    	$city = get_city_by_name($trade['receiver_city'], $city['code']);
    	$trade['city_code'] = ''.$city['code'];
    	$city = get_city_by_name($trade['receiver_county'], $city['code']);
    	$trade['county_code'] = ''.$city['code'];
    	
    	return $trade;
    }
    /**
     * 微供下单new
     */
    private function orderToWeiGong($tokenId, $trade, $list,$params=''){
        if(count($list) == 0){
            return;
        }

        // 0待下单;1待付款;2待发货;3成功;4交易关闭
        $Interface = $this->getAlibabaAuth($tokenId);
        foreach ($list as $loginId=>$model){
            $products = $model['products'];
            unset($model['products']);
            $result = $Interface->MakeOrderInputModels($model, true);
            if($result['id']){
                parent::add(array(
                    'id'        => $result['id'],
                    'created'   => $trade['created'],
                    'type'      => '1',
                    'tid'       => $trade['tid'],
                    'status'    => $result['id'] ? 1 : 0,
                    'buyer_id'  => $tokenId,
                    'seller_id' => $model['supplierLoginId'],
                    'errcode'   => $result['errcode'],
                    'errmsg'    => $result['errmsg'],
                    'interface' => 3,
                    'products'  => encode_json($products)
                ));
                if(!empty($params)){
                    return 'success';
                }
            }else{
                if(empty($params)){
                    E($result['errmsg']);
                }else{
                    $sync_time = time();
                    $this->execute("INSERT INTO alibaba_trade_sync(tid,status,err_msg,sync_time,source) VALUES('{$trade['tid']}','7','{$result['errmsg']}',{$sync_time},'订单回流')");
                    return $result['errmsg'];
                }
            }
        }
    }
    /**
     * 微供下单
     */
    private function orderToWeiGongOld($tokenId, $trade, $list){
    	if(count($list) == 0){
            return;
        }
        
        // 0待下单;1待付款;2待发货;3成功;4交易关闭
        $Interface = $this->getAlibabaAuth($tokenId);
        foreach ($list as $loginId=>$model){
        	$products = $model['products'];
        	unset($model['products']);
        	$result = $Interface->orderToWeiGong($model, true);
        	parent::add(array(
        		'id'        => $result['id'],
        		'created'   => $trade['created'],
        		'tid'       => $trade['tid'],
        		'status'    => $result['id'] ? 1 : 0,
        		'buyer_id'  => $tokenId,
        		'seller_id' => $supplierLoginId,
        		'errcode'   => $result['errcode'],
        		'errmsg'    => $result['errmsg'],
        		'interface' => 3,
        		'products'  => encode_json($products)
        	));
        	
        	if($result['errcode']){
        		$this->orderErrors[] = $result['errmsg'];
        	}
        }
    }
    
    /**
     * 大市场下单
     */
    private function orderToDaShiChang($tokenId, $trade, $list, $shopMix){
    	if(count($list) == 0){
            return;
        }
        
        $Interface = $this->getAlibabaAuth($tokenId);
        // 下单预览
        $preview = $Interface->orderPreview($list, $trade);
        // 预览失败则不做处理
        if($preview['errcode']){
        	$products = array();
        	foreach ($list as $item){
        		$products[$item['oid']] = array('tao_id' => $item['offerId'], 'spec_id' => $item['specId'], 'sku_id' => $item['skuId']);
        	}
        	
        	parent::add(array(
        		'created'   => $trade['created'],
        		'tid'       => $trade['tid'],
        		'status'    => 0,
        		'buyer_id'  => $tokenId,
        		'seller_id' => '',
        		'errcode'   => $preview['errcode'],
        		'errmsg'    => $preview['errmsg'],
        		'interface' => 1,
        		'products'  => encode_json($products)
        	));
        	return;
        }
        
        // 组合数据创建订单
        foreach ($preview['trades'] as $item){
        	$cargoGroups    = $item['cargoGroups'];
        	$otherInfoGroup = array(
        		'is_daixiao'        => false,
        		'additionalFee'     => 0, // 附加费,单位，元
        		'chooseFreeFreight' => '0',   // '0'：用户没有选择免用费 '1':用户选择免运费
        		'discountFee'       => 0,   // 计算完货品金额后再次进行的减免金额. 单位: 元
        		'filledCarriage'    => 0, // 用户填写的运费 单位:元
        		'guaranteeFee'      => 0, //页面传过来的阿里信用凭证担保费. 单位：元
        		'message'           => $trade['buyer_remark'],
        		'needCheckCode'     => false,  // 是否需要验证码
        		'needCheckInstant'  => -1, // 是否使用协议提额极速到账，用来接收checkbox的状态 。 * -1：页面上未出现checkbox，走老的极速到账逻辑 * 0：页面上出现了checkbox，但未被买家选中，表示不走极速到账交易，走支付宝担保交易 * 1：页面上出现了checkbox，且被买家选中，表示走提额极速到账
        		'needInstallment'   => false, // 是否需要分期付款
        		'needRegist'        => false, // 判断前台是否需要登录注册
        		'orderCodFee'       => 0, // cod服务费
        		'payEntry'          => $item['tradeMode'], // 选择的支付入口
        		'payWay'            => $item['tradeWay'], // 支付4.0
        		'sumCarriage'       => $item['sumCarriage'], // 总运费。除非为0，否则必填
        		'supportInvoice'    => false, // 是否支持发票标识
        		'toleranceFreight'  => '0', //  1：运费被容错。 0:正常运费.
        		'totalAmount'       => $item['sumPayment'], //  货品总金额 + 运费，单位: 元
        		'umpSysAvailble'    => '1', // 1：ump 系统可用 0:ump系统不可用
        		'mixAmount'         => $shopMix[$item['loginId']]['mix_amount'], // 混批设置
        		'mixNumber'         => $shopMix[$item['loginId']]['mix_number'] // 混批设置
        	);
        	
        	$result = $Interface->createOrder($cargoGroups, $otherInfoGroup, $preview['address']);
        	parent::add(array(
        		'id'        => $result['id'],
        		'created'   => $trade['created'],
        		'tid'       => $trade['tid'],
        		'status'    => $result['out_tid'] ? 1 : 0,
        		'buyer_id'  => $tokenId,
        		'seller_id' => $item['loginId'],
        		'errcode'   => $result['errcode'],
        		'errmsg'    => $result['errmsg'],
        		'interface' => 1,
        		'products'  => encode_json($item['products'])
        	));
        }
    }
    
    /**
     * 代销下单
     */
    private function orderToDaiXiao($tokenId, $trade, $list, $shopMix){
    	if(count($list) == 0){
    		return;
    	}
    	
    	$Interface = $this->getAlibabaAuth($tokenId);
    	// 下单预览
    	$preview = $Interface->orderPreview($list, $trade, true);
    	// 预览失败则不做处理
    	if($preview['errcode']){
    		$products = array();
    		foreach ($list as $item){
    			$products[$item['oid']] = array('tao_id' => $item['offerId'], 'spec_id' => $item['specId'], 'sku_id' => $item['skuId']);
    		}
    		
    		parent::add(array(
    			'created'   => $trade['created'],
    			'tid'       => $trade['tid'],
    			'status'    => 0,
    			'buyer_id'  => $tokenId,
    			'seller_id' => '',
    			'errcode'   => $preview['errcode'],
    			'errmsg'    => $preview['errmsg'],
    			'interface' => 1,
    			'products'  => encode_json($products)
    		));
    		return;
    	}
    	
    	// 组合数据创建订单
    	foreach ($preview['trades'] as $item){
    		$cargoGroups    = $item['cargoGroups'];
    		$otherInfoGroup = array(
    				'is_daixiao'        => true,
    				'additionalFee'     => 0, // 附加费,单位，元
    				'chooseFreeFreight' => '0',   // '0'：用户没有选择免用费 '1':用户选择免运费
    				'discountFee'       => 0,   // 计算完货品金额后再次进行的减免金额. 单位: 元
    				'filledCarriage'    => 0, // 用户填写的运费 单位:元
    				'guaranteeFee'      => 0, //页面传过来的阿里信用凭证担保费. 单位：元
    				'message'           => $trade['buyer_remark'],
    				'needCheckCode'     => false,  // 是否需要验证码
    				'needCheckInstant'  => -1, // 是否使用协议提额极速到账，用来接收checkbox的状态 。 * -1：页面上未出现checkbox，走老的极速到账逻辑 * 0：页面上出现了checkbox，但未被买家选中，表示不走极速到账交易，走支付宝担保交易 * 1：页面上出现了checkbox，且被买家选中，表示走提额极速到账
    				'needInstallment'   => false, // 是否需要分期付款
    				'needRegist'        => false, // 判断前台是否需要登录注册
    				'orderCodFee'       => 0, // cod服务费
    				'payEntry'          => $item['tradeMode'], // 选择的支付入口
    				'payWay'            => $item['tradeWay'], // 支付4.0
    				'sumCarriage'       => $item['sumCarriage'], // 总运费。除非为0，否则必填
    				'supportInvoice'    => false, // 是否支持发票标识
    				'toleranceFreight'  => '0', //  1：运费被容错。 0:正常运费.
    				'totalAmount'       => $item['sumPayment'], //  货品总金额 + 运费，单位: 元
    				'umpSysAvailble'    => '1', // 1：ump 系统可用 0:ump系统不可用
    				'mixAmount'         => $shopMix[$item['loginId']]['mix_amount'], // 混批设置
    				'mixNumber'         => $shopMix[$item['loginId']]['mix_number'] // 混批设置
    		);
    		
    		$result = $Interface->createOrder($cargoGroups, $otherInfoGroup, $preview['address']);
    		parent::add(array(
    				'id'        => $result['id'],
    				'created'   => $trade['created'],
    				'tid'       => $trade['tid'],
    				'out_tid'   => $result['out_tid'],
    				'status'    => $result['out_tid'] ? 1 : 0,
    				'buyer_id'  => $tokenId,
    				'seller_id' => $item['loginId'],
    				'errcode'   => $result['errcode'],
    				'errmsg'    => $result['errmsg'],
    				'interface' => 1,
    				'products'  => encode_json($item['products'])
    		));
    	}
    }
}
?>