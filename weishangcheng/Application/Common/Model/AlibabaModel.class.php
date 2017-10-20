<?php
namespace Common\Model;

use Org\Alibaba\AlibabaAuth;
use Common\Model\OrderStatus;

class AlibabaModel extends BaseModel
{
    protected $tableName = 'alibaba_goods';
    protected $pk = 'id';
    
    public function getTokenId($shopId){
        $shop = $this->query("SELECT aliid FROM shop WHERE id='{$shopId}'");
        return $shop[0]['aliid'];
    }
    
    public function syncGoods($offerId, $tokenId, $return = false ,$shopAliid){
        $Interface = new AlibabaAuth($tokenId,'4157308','kVhHHhfWu1');
        $response = $Interface->getGoods($offerId);
        $response['productInfo']['description'] = str_replace('{', '', $response['productInfo']['description']);
        $productInfo = $response['productInfo'];
        if(!$productInfo){
            $this->execute("UPDATE alibaba_relation SET error_times=error_times+1 WHERE id={$offerId} AND token_id='{$shopAliid}'");
            $this->error = '未获取到商品信息';
            return;
        }
        
        $timestamp = time();
        $goods = array(
            'id'                => $productInfo['productID'],
            'cat_id'             => $productInfo['categoryID'],
            'subject'            => $productInfo['subject'],
            'price'              => $productInfo['saleInfo']['priceRanges'][0]['price'],
            'retailprice'        => $productInfo['saleInfo']['retailprice'] ? $productInfo['saleInfo']['retailprice'] : 0,
            'daixiao_price'      => 0,
            'price_range'        => array(),
            'min_order_quantity' => $productInfo['saleInfo']['minOrderQuantity'],
            'unit'               => isset($productInfo['saleInfo']['unit']) ? $productInfo['saleInfo']['unit'] : '',
            'stock'              => $productInfo['saleInfo']['amountOnSale'],
            'weight'             => isset($productInfo['shippingInfo']['unitWeight']) ? $productInfo['shippingInfo']['unitWeight'] : 0,
            'freight_id'         => $productInfo['shippingInfo']['freightTemplateID'],
            'images'             => array(),
            'sku_json'           => array(),
            'products'           => array(),
            'attributes'         => array(),
            'status'             => $productInfo['status'],
            'send_address_id'    => $productInfo['shippingInfo']['sendGoodsAddressId'],
            'detail'             => $productInfo['description'],
            'last_update'        => substr($response['lastUpdateTime'], 0, 14),
            'expire_time'        => substr($response['expireTime'], 0, 14),
            'login_id'           => isset($response['productOwnerId']) ? $response['productOwnerId'] : ''
        );
         
        // 起批量价格区间
        foreach ($productInfo['saleInfo']['priceRanges'] as $item){
            $goods['price_range'][$item['startQuantity']] = floatval($item['price']);
        }
        $goods['price_range'] = json_encode($goods['price_range']);
         
        // 商品主图
        $picHost = 'https://cbu01.alicdn.com/';
        foreach($productInfo['image']['images'] as $src){
            $goods['images'][] = $picHost.$src;
        }
         
        // 属性参数
        $attributes = array();
        foreach($productInfo['attributes'] as $item){
            if(!isset($attributes[$item['attributeID']])){
                $attributes[$item['attributeID']] = array('name' => $item['attributeName'], 'value' => array($item['value']));
            }else{
                $attributes[$item['attributeID']]['value'][] = $item['value'];
            }
        }
        $goods['attributes'] = $attributes;
        
        // 判断此商品支持的业务
        $support = array();
        foreach ($response['bizGroupInfos'] as $item){
            $support[$item['code']] = $item['support'];
        }
        // 1大市场；2代销isConsignMarketOffer；3微供isMicroSupply
        $relation = $support['isMicroSupply'] ? 3 : 1;
        
        // 解析代销价
        $daixiaoPrice = 0;
        if($support['isConsignMarketOffer']){
            foreach($productInfo['extendInfos'] AS $item){
                if($item['key'] == 'consign_price'){
                    $value = rtrim($item['value'], ';');
                    $explode = explode(':', $value);
                    if($explode[0] == -1){
                        $daixiaoPrice = $goods['daixiao_price'] = $explode[1];
                    }else{
                        $value = preg_replace('(;)', ',', $value);
                        $value = preg_replace('(:)', '":', $value);
                        $value = preg_replace('(,)', ',"', $value);
                        $value = '{"'.$value.'}';
                        $daixiaoPrice = json_decode($value, true);
                        $goods['daixiao_price'] = current($daixiaoPrice);
                    }
                    break;
                }
            }
        }
        
        // 解析组合系统SKU
        $skuItemValues = array();
        foreach ($productInfo['skuInfos'] as $item){
            // 单品SKU
            $skuJosn = array();
             
            foreach ($item['attributes'] as $attr){
                // 商品SKU集合
                $sku = $goods['sku_json'][$attr['attributeID']];
                if(!$sku){
                    $sku = array(
                        'id'   => $attr['attributeID'],
                        'text' => $attributes[$attr['attributeID']]['name'],
                        'items'=> array()
                    );
                }
                 
                $vid = array_search($attr['attributeValue'], $attributes[$sku['id']]['value']);
                $sku['items'][$vid] = array(
                    'id' => $vid + 1,
                    'text' => $attr['attributeValue']
                );
                if($attr['skuImageUrl']){
                    $sku['items'][$vid]['img'] = $picHost.$attr['skuImageUrl'];
                }
                $goods['sku_json'][$attr['attributeID']] = $sku;
                 
                // 单品SKU
                $skuJosn[] = array(
                    'kid' => $sku['id'],
                    'vid' => $vid + 1,
                    'k'   => $sku['text'],
                    'v'   => $attr['attributeValue']
                );
            }
            
            $daixiao = is_numeric($daixiaoPrice) ? $daixiaoPrice : $daixiaoPrice[$item['skuId']];
            $goods['products'][] = array(
                'sku_id'   => $item['skuId'],
                'spec_id'  => $item['specId'],
                'price'    => !is_numeric($item['price']) ? floatval($goods['price']) : floatval($item['price']),
                'stock'    => $item['amountOnSale'],
                'sku_json' => $skuJosn,
                'daixiao'  => $daixiao,
                'retail_price' => $item['retailPrice']
            );
            
            if($goods['daixiao_price'] == 0 || $daixiao < $goods['daixiao_price']){
                $goods['daixiao_price'] = $daixiao;
            }
        }
        
        // 利用代销接口获取卖家信息；如果商品支持代销，则判断是否为代销关系
        $memberId = '';
        if(1==1 || $support['isConsignMarketOffer']){
            $daixiao = $Interface->isDaiXiao($goods['id'], $goods['products'][0]['spec_id']);
            $memberId= $daixiao['seller']['member_id'];
            $goods['login_id'] = $daixiao['seller']['login_id'];
            $mobile = $daixiao['seller']['mobile'];
            if($mobile){
                $goods['mobile'] = $mobile;
            }
            if($daixiao['success']){
                $relation = 2;
            }
        }
        
        if($relation == 3){ // 获取微供九宫图
        	$array = $Interface->getWXNineImage($offerId);
        	$array = explode('|', $array);
        	$goods['images'] = array();
        	foreach ($array as $src){
        		$goods['images'][] = $src;
        		$goods['detail'] .= '<img src="'.$src.'"/>';
        	}
        }else if($relation != 3){// 如果不是微供产品，则获取卖家混批设置
            static $existsMix = array();
            if(!in_array($memberId, $existsMix)){
                $mixConfig = $Interface->getMixConfig($memberId);
                if(!isset($mixConfig['errcode'])){
                    $sql = "REPLACE INTO alibaba_shop SET
                            token_id='{$shopAliid}',
                            login_id='{$goods['login_id']}',
                            general_hunpi='{$mixConfig['generalHunpi']}',
                            mix_amount='{$mixConfig['mixAmount']}',
                            mix_number='{$mixConfig['mixNumber']}',
                            last_sync='{$timestamp}',
                            errcode='',
                            errmsg=''";
                    $this->execute($sql);
                    $existsMix[] = $memberId;
                }
            }
        }
        
        // 格式化数据
        $goods['sku_json'] = array_values($goods['sku_json']);
        
        // 全文索引分词
        $pscws = new \Org\PSCWS\PSCWS4();
        $text  = $pscws->getText($goods['subject']);
        $goods['key_word'] = addslashes($text);
        
        // 保存到数据库中
        $save = $goods;
        $save['sku_json'] = json_encode($goods['sku_json']);
        $save['images'] = json_encode($goods['images']);
        $save['products'] = json_encode($goods['products']);
        $save['attributes'] = json_encode($goods['attributes']);
        is_null($save['daixiao_price']) && $save['daixiao_price'] = 0;
        $this->add($save, null, true);
        
        // 保存关系
        $sql = "INSERT INTO alibaba_relation SET 
                id='{$goods['id']}',
                token_id='{$shopAliid}',
                relation='{$relation}',
                last_sync='{$timestamp}',
                error_times=0
				ON DUPLICATE KEY UPDATE
				relation=VALUES(relation),
				last_sync=VALUES(last_sync),
				error_times=VALUES(error_times)";
        $this->execute($sql);
        if($return === false){
        }else if($return === true){
            $goods = $this->find($goods['id']);
        }else{
            $goods = $this->field($return)->find($goods['id']);
        }
        $goods['relation'] = $relation;
        $goods['token_id'] = $tokenId;
        $goods['last_sync'] = $timestamp;
        return $goods;
    }
    
    /**
     * 同步微供店铺
     */
    public function syncShop($shopId){
        $shop    = $this->query("SELECT id, mid, `name`, aliid FROM shop WHERE id=".$shopId);
        $shop    = $shop[0];
        $tokenId = $shop['aliid'];
        if(!$tokenId){
            $this->error = '店铺未授权绑定1688账号，无法更新';
            return false;
        }
        
        // 分发通知更新商品
        $url = C('SERVICE_URL').'/ali/syncOtherGoods';
        $param = array(
            'timestamp' => time(),
            'noncestr'  => \Org\Util\String2::randString(16)
        );
        $param['sign'] = create_sign($param);
        sync_notify($url, $param, array('token_id' => $tokenId));
        
        $auth = new AlibabaAuth($tokenId);
        $list = $auth->getSupplier();
        if(isset($list['errcode'])){
            $this->error = '接口异常：'.$list['errmsg'];
            return false;
        }
        
        $this->execute("UPDATE alibaba_sync SET times=times-1, prev_time=".NOW_TIME);
        
        $count = count($list);
        set_time_limit(900); // 本地5分钟更新900+
        $seconds = bcdiv($count, 4, 4);
        $seconds = bcdiv($seconds, 60, 4);
        finish_request(array('status' => 1, 'info' => '数据同步已开始'));
        
        $timestamp = time();
        $values = '';
        $items  = array();
        foreach ($list as $i=>$loginId){
            $values.= "('{$tokenId}', '{$loginId}', '{$timestamp}', '', ''),";
            $items[] = $loginId;
            if(($i+1) % 100 == 0){
                $this->addShopAndSyncGoods($values, $items);
                $values = "";
                $items = array();
            }
        }
        
        if(count($items) > 0){
            $this->addShopAndSyncGoods($values, $items);
        }
        exit('更新结束：共用时'.(time() - NOW_TIME));
    }
    
    /**
     * 保存店铺并通知更新商品
     */
    private function addShopAndSyncGoods($values, $items){
        $sql  = "INSERT INTO alibaba_shop (token_id,login_id,last_sync,errcode,errmsg) VALUES".rtrim($values, ',');
        $sql .= " ON DUPLICATE KEY UPDATE last_sync=VALUES(last_sync),errcode=VALUES(errcode),errmsg=VALUES(errmsg)";
        $this->execute($sql);
        
        // 分发通知更新商品
        $url = C('SERVICE_URL').'/ali/syncShopGoods';
        $param = array(
            'timestamp' => time(),
            'noncestr'  => \Org\Util\String2::randString(16)
        );
        $param['sign'] = create_sign($param);
        sync_notify($url, $param, array('token_id' => $tokenId, 'logins' => json_encode($items, JSON_UNESCAPED_UNICODE)));
    }

    /*
     * 同步1688订单状态
     */
    public function getAliTrade($tids,$tokenId, $endTids = null){
        $returnMsg = array();
        if(empty($tids)){
            $returnMsg[$tids]['fail_reason'] = '参数错误';
            return $returnMsg;
        }
        //拼凑条件，查询alibaba_trade订单，
        $where = "WHERE alibaba_trade.is_del=0 AND alibaba_trade.status<>'7' AND alibaba_trade.status<>'8' AND ";
        if(is_numeric($tids)){
            if(is_numeric($endTids)){
                $where .= "alibaba_trade.tid BETWEEN {$tids} AND {$endTids}";
            }else{
                $where .= "alibaba_trade.tid=".$tids;
            }
        }else if(is_array($tids)){
            $where .= "alibaba_trade.tid IN(".implode(',', $tids).")";
        }else{
            $where .= "alibaba_trade.tid IN(".addslashes($tids).")";
        }

        $sql = "SELECT alibaba_trade.id, mall_trade.tid, alibaba_trade.out_tid, alibaba_trade.`status`, alibaba_trade.seller_id, alibaba_trade.do_cost,
                    mall_trade.consign_time, mall_trade.express_no, alibaba_trade.payment, mall_trade.total_cost, mall_trade.express_no, alibaba_trade.type,
                    mall_trade.`status` AS trade_status, mall_trade.buyer_openid, wx_user.appid, mall_trade.receiver_name, mall_trade.receiver_mobile,
                    mall_trade.receiver_city, mall_trade.receiver_province, mall_trade.receiver_county, 
                    mall_trade.receiver_detail, wx_user.subscribe AS buyer_subscribe, alibaba_trade.pay_time
                FROM alibaba_trade
                INNER JOIN trade AS mall_trade ON alibaba_trade.tid=mall_trade.tid
                LEFT JOIN wx_user ON wx_user.openid=mall_trade.buyer_openid
                {$where}
                ORDER BY mall_trade.tid";

        $ali_trades = $this->query($sql);
        if(empty($ali_trades)){
            $returnMsg[$tids]['fail_reason'] = '获取订单信息失败';
            return $returnMsg;
        }
        
        $this->startTrans();
        $Aop = new \Org\Alibaba\AlibabaAuth($tokenId);
        //遍历订单信息
        foreach($ali_trades as $i=>$v){
            if($v['status'] != OrderStatus::BUYER_CANCEL && $v['status'] != OrderStatus::SUCCESS){
                $aorder = $Aop->getTradeDetail($v['id']);
                if(empty($aorder)){
                    continue;
                }
                if(!empty($aorder['errcode'])){
                    $time = time();
                    $this->execute("INSERT INTO alibaba_trade_sync(tid,tid_tao,err_code,err_msg,sync_time,source) VALUES('{$v['tid']}','{$v['id']}','{$aorder['errcode']}','{$aorder['errmsg']}','{$time}','订单状态同步')");
                    $returnMsg[$v['tid']]['fail_reason'] = 'API错误';
                    $returnMsg[$v['tid']]['success'] = 0;
                }else{
                    //判断订单状态
                    if($aorder['status'] == 'WAIT_SELLER_SEND'){
                        //等待卖家发货
                        $status = OrderStatus::WAIT_SEND_GOODS;
                    }else if($aorder['status'] == 'CANCEL'){
                        //交易关闭
                        $status = OrderStatus::BUYER_CANCEL;
                    }else if($aorder['status'] == 'SUCCESS'){
                        //交易成功
                        $status = OrderStatus::SUCCESS;
                    }else if($aorder['status'] == 'WAIT_BUYER_PAY'){
                        //等待买家付款
                        $status = OrderStatus::ALI_WAIT_PAY;
                    }else if($aorder['status'] == 'WAIT_BUYER_RECEIVE'){
                        //等待买家确认收货
                        $status = OrderStatus::WAIT_CONFIRM_GOODS;
                    }else if($aorder['status'] == 'SIGN_IN_SUCCESS'){
                        //买家已签收货到付款
                        $status = OrderStatus::SUCCESS;
                    }else{
                        //等待买家确认收货
                        $status = OrderStatus::WAIT_CONFIRM_GOODS;
                    }
                    //
                    $update = array(0=>"`status`='{$status}'");
                    $aliUpdate = array(0=>"`status`='{$status}'");
                    if($status == OrderStatus::ALI_WAIT_PAY){
                        //如果买家未付款
                        $returnMsg[$v['tid']]['pay'] = 0;
                        // $status = OrderStatus::WAIT_SEND_GOODS;
                    }else if($status == OrderStatus::WAIT_SEND_GOODS){
                        //如果买家已已付款，卖家未发货
                        if(!empty($aorder['gmtPayment']) && $aorder['payStatus']!=1){
                            $returnMsg[$v['tid']]['pay'] = '1';
                        }
                    }else{
                        $returnMsg[$v['tid']]['pay'] = '3';
                    }
                    //如果买家已已付款，获得支付信息；存储更改订单信息
                    if(!empty($aorder['gmtPayment']) && $aorder['payStatus']!=1){
                        $paytime = substr($aorder['gmtPayment'],0,-8);
                        $paytime  = strtotime($paytime);
                        $payment = $aorder['sumPayment']/100;
                        // $aliUpdate .= ", payment = '{$payment}',pay_time = '{$paytime}' ";
                        $aliUpdate[] = "payment = '{$payment}'";
                        $aliUpdate[] = "pay_time = '{$paytime}'";
                    }
                    //如果卖家已发货，获得运单号，物流信息；存储更改订单信息
                    if(!empty($aorder['logisticsOrderList'])){
                        $consign_time = substr($aorder['gmtGoodsSend'],0,-8);
                        $consign_time  = strtotime($consign_time);
                        // $consign_time = $aorder['gmtGoodsSend'];
                        $logisticsBillNo = $aorder['logisticsOrderList'][0]['logisticsBillNo'];
                        $express_id = $aorder['logisticsOrderList'][0]['logisticsCompany']['id'];
                        $logisticsCompanyName = $aorder['logisticsOrderList'][0]['logisticsCompany']['companyName'];
                        $express_no = array($logisticsBillNo => $logisticsCompanyName);
                        $express_no = encode_json($express_no);
                        // $update .= ", consign_time='{$consign_time}', express_id='{$express_id}', express_no='{$express_no}' ";
                        $update[] = "consign_time='{$consign_time}'";
                        $update[] = "express_id='{$express_id}'";
                        $update[] = "express_no='{$express_no}'";
                    }else{
                        if($aorder['status'] == "WAIT_BUYER_RECEIVE"){
                            $status = OrderStatus::WAIT_SEND_GOODS;
                            $update = array(0=>"`status`='{$status}'");
                            $aliUpdate = array(0=>"`status`='{$status}'");
                        }
                    }
                    $this->execute("UPDATE alibaba_trade SET ".implode(',',$aliUpdate)." WHERE id=".$v['id']);
                    $this->execute("UPDATE trade SET ".implode(',',$update)." WHERE tid=".$v['tid']);
                    $this->execute("UPDATE trade_order SET status='{$status}' WHERE tid='{$v['tid']}'");
                    $this->execute("UPDATE trade_seller SET status='{$status}' WHERE tid = '{$v['tid']}'");
                    $this->execute("UPDATE trade_buyer SET status='{$status}' WHERE tid = '{$v['tid']}'");
                    $result = encode_json($aorder);
                    $time = time();
                    $this->execute("INSERT INTO alibaba_trade_sync(tid,tid_tao,status,result,sync_time,source) VALUES('{$v['tid']}','{$v['id']}','{$status}','{$result}','{$time}','订单状态同步')");
                    $returnMsg[$v['tid']]['success'] = 1;
                }
            }
        }
        $this->commit();
        return $returnMsg;
    }

    /**
     * 获取订单物流信息
     */
    public function getLogistics($tid,$tokenId){
        $ali_trade = $this->query("SELECT * FROM alibaba_trade WHERE tid = %d",$tid);
        $ali_trade = $ali_trade[0];
        $Ali = new \Org\Alibaba\AlibabaAuth($tokenId);
        $logisticsInfo = $Ali->getLogisticsTraceInfo($ali_trade['id']);
       
        if(!empty($logisticsInfo['errorCode'])){
            //获取物流信息失败，返回失败原因
            $logistics['error'] = $logisticsInfo['errorMessage'];
            return $logistics;
        }else{
            $logisticsInfo = $logisticsInfo['logisticsTrace'];
            if(empty($logisticsInfo)){
                //未获取到物流信息，返回未查找到数据
                $logistics['error'] = "未获取到物流信息";
                return $logistics;
            }else{
                foreach ($logisticsInfo as $key => $value) {
                    $logistics['orderId'] = $value['orderId'];
                    $logistics['logisticsBillNo'] = $value['logisticsBillNo'];
                    $logistics['logisticsSteps'] = $value['logisticsSteps'];
                }
                return $logistics;
            }
        }
    }
}
?>