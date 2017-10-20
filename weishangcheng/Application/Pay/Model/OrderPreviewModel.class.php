<?php 
namespace Pay\Model;

use Think\Cache\Driver\Redis;
use Common\Model\BalanceModel;
use Org\IdWork;
use Common\Model\GoodsModel;
use Common\Model\OrderStatus;
use Common\Model\TradeType;
use Common\Model\PayType;
use Common\Model\BalanceType;
use Common\Model\OrderType;
use Common\Model\ProjectConfig;

class OrderPreviewModel extends GoodsModel{
    private $hasError = 0;
    private $buyer = null;
    private $activityStock = array();
    private $book = null;
    
    public function getBook($id){
        $book = $this->query("SELECT * FROM trade_book WHERE id='{$id}'");
        if(!$book){
            $this->error = 'book key无效，请重新下单';
            if(IS_GET && !IS_AJAX){
                exit('<script>location.href=document.referrer</script>');
            }
            return;
        }
        
        $book = $book[0];
        if($book['created'] + 1800 < NOW_TIME){
            $this->execute("DELETE FROM trade_book WHERE id='{$id}'");
            $this->error = '操作超过30分钟请重新下单';
            return;
        }
        if(is_null($book['buyer_openid'])){
            $book['buyer_openid'] = '';
        }
        if(is_null($book['buyer_appid'])){
            $book['buyer_appid'] = $book['mch_appid'];
        }
        if(is_null($book['buyer_subscribe'])){
            $book['buyer_subscribe'] = '0';
        }
        
        $book['address'] = json_decode($book['address'], true);
        $address = $this->query("SELECT * FROM member_address WHERE mid = {$book['buyer_id']} AND receiver_id = '{$book['address']['receiver_id']}'");
        if(empty($address)){
            //已删除地址，获取最近添加的地址
            $ads = $this->query("SELECT * FROM member_address WHERE mid = {$book['buyer_id']} ORDER BY receiver_id DESC LIMIT 0,1");
            $addr = $ads[0];
            $province = $this->query("SELECT name FROM city WHERE id='{$addr['province_code']}'");
            $city = $this->query("SELECT name FROM city WHERE id='{$addr['city_code']}'");
            $county = $this->query("SELECT name FROM city WHERE id='{$addr['county_code']}'");
            $addr['receiver_province'] = $province[0]['name'];
            $addr['receiver_city'] = $city[0]['name'];
            $addr['receiver_county'] = $county[0]['name'];
            $this->execute("UPDATE trade_book SET address = '".encode_json($addr)."' WHERE id = '{$id}'");
            $book['address'] = $addr;
        }else{
            //已修改地址，更新trade_book，显示更新的地址
            $addr = $address[0];
            if($addr['receiver_name'] != $book['address']['receiver_name']
             || $addr['receiver_mobile'] != $book['address']['receiver_mobile']
             || $addr['province_code'] != $book['address']['province_code']
             || $addr['city_code'] != $book['address']['city_code']
             || $addr['county_code'] != $book['address']['county_code'])
            {
                $province = $this->query("SELECT name FROM city WHERE id='{$addr['province_code']}'");
                $city = $this->query("SELECT name FROM city WHERE id='{$addr['city_code']}'");
                $county = $this->query("SELECT name FROM city WHERE id='{$addr['county_code']}'");
                $addr['receiver_province'] = $province[0]['name'];
                $addr['receiver_city'] = $city[0]['name'];
                $addr['receiver_county'] = $county[0]['name'];
                $this->execute("UPDATE trade_book SET address = '".encode_json($addr)."' WHERE id = '{$id}'");
                $book['address'] = $addr;
            }
        }
        $book['products'] = json_decode($book['products'], true);
        $this->book = $book;
        return $book;
    }
    
    private function delBook(){
        $book = $this->book;
        $this->clearCart($book['id'], $book['carts']);
    }

    // 清空购物车
    private function clearCart($postId, $postCarts){
        $Model = M();
        if(!is_numeric($postId)){
            $this->error('id不能为空');
        }
        $Model->execute("DELETE FROM trade_book WHERE id='{$postId}'");

        $carts = json_decode($postCarts, true);
        foreach ($carts as $id=>$quantity){
            if(!is_numeric($id) || !is_numeric($quantity)){
                break;
            }

            $result = $Model->execute("DELETE FROM mall_cart WHERE id='{$id}' AND quantity<={$quantity}");
            if($result < 1){
                $Model->execute("UPDATE mall_cart SET quantity=quantity-{$quantity} WHERE id='{$id}'");
            }
        }
    }
    
    /**
     * 下单预览
     */
    public function createPreview($book, $login, $adjustParams){
        if(isset($adjustParams['address'])){
            $book['address'] = $adjustParams['address'];
        }
        if(!isset($adjustParams['allow_paid_balance'])){
            $adjustParams['allow_paid_balance'] = 1;
        }
        if(!isset($adjustParams['allow_paid_wallet'])){
            $adjustParams['allow_paid_wallet'] = 1;
        }
        if(!isset($adjustParams['allow_paid_score'])){
            $adjustParams['allow_paid_score'] = 1;
        }
        if(!isset($adjustParams['allow_discount_fee'])){
            $adjustParams['allow_discount_fee'] = 1;
        }
        $adjustParams['anonymous'] = $adjustParams['anonymous'] ? 1 : 0;
        
        $result = $this->getDBProducts($book['products'], $login, $book['address']);
        if($this->error != ''){
            return;
        }
        $buyer = $login;
        
        // 有效的产品
        $productList = $result['list'];
        // 无效的产品
        $invalidList = $result['invalidList'];
        // 将产品分组
        $groups = $this->getTradeGroup($productList, $buyer, $adjustParams);
        // 满减
        $groups = $this->tradeManjian($groups, $buyer, $adjustParams);
        // 优惠券
        $groups = $this->tradeCoupon($groups, $buyer, $adjustParams);
        // 运费
        $groups = $this->tradeFreight($groups, $book['address'], $adjustParams);
        // 汇总
        $result = $this->tradeSum($groups, $buyer, $adjustParams);

        $result['invalidList'] = $invalidList;
        $result['address'] = $book['address'];
        
        $this->buyer = $buyer;
        if(count($invalidList) > 0){
            $result['has_error'] = 1;
            if(count($productList) > 0){
                $result['error'] = '清空失效宝贝可继续结算';
            }else{
                $result['error'] = '商品校验失败，请重新下单';
            }
        }else if(!$book['address']){
            $result['has_error'] = 1;
        }
        return $result;
    }
    
    /**
     * 创建订单
     */
    public function createOrder($groups, $address){
        ignore_user_abort(true);
        set_time_limit(180);
        // 买家ip定位
        $ipLocation = new \Org\Net\IpLocation();
        $location = $ipLocation->getlocation();
        
        $tidList = array('paid_tid' => array(), 'need_pay' => array(),'buyer'=>array(),'seller'=>array());
        $tradeList = $tradeSellerList = $tradeBuyerList = $orderList = $discountList = $memberBalance = $substockList = $paidList = $couponList = array();
        $payType = 0;
        foreach ($groups as $group){
            $buyer = $this->buyer[$group['project_id']];
            foreach ($group['trades'] AS $trade){
                $tid = IdWork::nextTId();
        
                // 已支付
                if(floatval($trade['payscore']) < 1){
                    if($trade['type'] === 'cod'){ // 货到付款
                        $payType = PayType::CODPAY;
                    }else if(floatval($trade['payment']) < 0.01){
                        if($trade['paid_score'] > 0){ // 积分兑换
                            $payType = PayType::SCORE;
                        }else if($trade['paid_wallet'] > 0 && $trade['paid_wallet'] > $trade['paid_balance']){ // 货款抵用
                            $payType = PayType::WALLET;
                        }else if($trade['paid_balance'] > 0){ // 余额抵用
                            $payType = PayType::BALANCE;
                        }else{
                            $payType = PayType::GIFT;
                        }
                    }
                }
                
                if($payType > 0){
                    $paidList[] = array('tid' => $tid, 'pay_time' => NOW_TIME, 'pay_type' => $payType, 'paid_fee' => 0, 'paid_score' => 0, 'transaction_id' => $tid);
                    $tidList['paid_tid'][] = $tid;
                }else{
                    $tidList['need_pay'][] = $tid;
                }
                
                $tradeList[] = array(
                    'tid'            => $tid,
                    'status'         => $payType>0?OrderStatus::WAIT_SEND_GOODS:OrderStatus::WAIT_PAY,
                    'created'        => NOW_TIME,
                    'type'           => $trade['type'],
                    'kind'           => $trade['kind'],
                    'total_quantity' => $trade['total_quantity'],
                    'shipping_type'  => 'express',
                    'express_id'     => $trade['express_id'],
                    'total_weight'   => $trade['total_weight'],
                    'pay_timeout'    => $trade['pay_timeout'],
                    'modified'       => NOW_TIME,
                    'pay_type'       => $trade['type'] === 'cod'?PayType::CODPAY:'',
        
                    // 收货地址
                    //'receiver_id'       => $address['receiver_id'],
                    'receiver_name'     => $address['receiver_name'],
                    'receiver_mobile'   => $address['receiver_mobile'],
                    'receiver_province' => $address['receiver_province'],
                    'receiver_city'     => $address['receiver_city'],
                    'receiver_county'   => $address['receiver_county'],
                    'receiver_detail'   => $address['receiver_detail'],
                    'receiver_zip'      => $address['receiver_zip'],
        
                    // 支付信息
                    'total_fee'      => $trade['total_fee'],
                    'total_postage'  => $trade['total_postage'],
                    'discount_fee'   => $trade['discount_fee'],
                    'paid_balance'   => $trade['paid_balance'],
                    'paid_wallet'    => $trade['paid_wallet'],
                    'payment'        => $trade['payment'],
                    'total_score'    => $trade['total_score'],
                    'discount_score' => $trade['discount_score'],
                    'paid_score'     => $trade['paid_score'],
                    'payscore'       => $trade['payscore'],
                    'pay_type'       => 0,
        
                    // 卖家信息
                    'seller_id'   => $group['shop_id'],
                    'seller_name' => $group['shop_name'],
        
                    // 买家信息
                    'buyer_id'        => $buyer['id'],
                    'buyer_nick'      => $buyer['name'],
                	'buyer_openid'    => $this->book['buyer_openid'], // 可以发起微信支付的openid
                	'buyer_appid'     => $this->book['buyer_appid'],  // 发起结算的公众号
                	'buyer_subscribe' => $this->book['buyer_subscribe'],
                    'buyer_type'      => $this->book['login_type'],
                    'buyer_card_id'   => $buyer['card_id'],
                    'buyer_remark'    => $trade['buyer_remark'],
                    'buyer_ip'        => $location['ip'],
                    'buyer_area'      => $location['country'],
                    'anonymous'       => $trade['anonymous'],
                    
                );
                $tradeSellerList[] = array(
                    'tid'            => $tid,
                    'status'         => $payType>0?OrderStatus::WAIT_SEND_GOODS:OrderStatus::WAIT_PAY,
                    'type'           => $trade['type'],
                    'shipping_type'  => 'express',
                    'receiver_name'     => $address['receiver_name'],
                    'receiver_mobile'   => $address['receiver_mobile'],
                    'seller_id'   => $group['shop_id'],
                    'buyer_id'        => $buyer['id'],
                    'buyer_rate'      => $buyer['name'],
                );
                $tradeBuyerList[] = array(
                    'tid'            => $tid,
                    'status'         => $payType>0?OrderStatus::WAIT_SEND_GOODS:OrderStatus::WAIT_PAY,
                    'seller_id'   => $group['shop_id'],
                    'buyer_id'        => $buyer['id'],
                    'buyer_rate'      => $buyer['name'],
                    );
                // 扣除会员余额
                $balance = array('mid' => $buyer['id'], 'project_id' => $group['project_id'], 'type' => BalanceType::PAY_ORDER, 'reason' => '支付订单['.$tid.']', 'score' => '0.00', 'balance' => '0.00', 'wallet' => '0.00');
                if($trade['paid_balance'] > 0){
                    $balance['balance'] = bcsub($balance['balance'], $trade['paid_balance'], 2);
                }
                if($trade['paid_wallet'] > 0){
                    $balance['wallet'] = bcsub($balance['wallet'], $trade['paid_wallet'], 2);
                }
                if($trade['paid_score'] > 0){
                    $balance['score'] = bcsub($balance['score'], $trade['score'], 2);
                }
                
                // 优惠和活动
                $discount_details = array();
                foreach ($trade['discount_details'] as $discount){
                    if(!$discount['checked']){
                        continue;
                    }
        
                    unset($discount['checked']);
                    $discount_details[] = $discount;
        
                    switch ($discount['type']){
                        case 'score':
                            $balance['score'] = bcsub($balance['score'], $discount['score'], 2);
                            break;
                        case 'coupon':
                        case 'coupon_code':
                            $couponList[] = array(
                                'tid'       => $tid,
                                'mid'       => $trade['buyer_id'],
                                'shop_id'   => $trade['seller_id'],
                                'used_time' => NOW_TIME,
                                'type'      => $discount['type'],
                                'id'        => $discount['id'],
                                'coupon_id' => $discount['coupon_id']
                            );
                            break;
                    }
                }
                if(count($discount_details) > 0 || count($trade['promotion_details']) > 0){
                    $discountList[] = array(
                        'tid'               => $tid,
                        'promotion_details' => encode_json($trade['promotion_details']),
                        'discount_details'  => encode_json($discount_details)
                    );
                }
                
                // 标记会员需要减余额
                if($balance['score'] != '0.00' || $balance['balance'] != '0.00' || $balance['wallet'] != '0.00'){
                    $memberBalance[] = $balance;
                }
                
                // 货到付款立即减库存
                foreach ($trade['orders'] AS $i=>$order){
                    $oid = $i == 0 ? $tid : IdWork::nextTId();
                    $orderList[] = array(
                        'oid'            => $oid,
                        'tid'            => $tid,
                        'status'         => $payType>0?OrderStatus::WAIT_SEND_GOODS:OrderStatus::WAIT_PAY,
                        'type'           => $order['type'],
                        'title'          => $order['title'],
                        'goods_id'       => $order['goods_id'],
                        'product_id'     => $order['product_id'],
                        'quantity'       => $order['quantity'],
                        'price'          => $order['price'],
                        'score'          => $order['score'],
                        'total_fee'      => $order['total_fee'],
                        'discount_fee'   => $order['discount_fee'],
                        'payment'        => $order['payment'],
                        'total_score'    => $order['total_score'],
                        'discount_score' => $order['discount_score'],
                        'payscore'       => $order['payscore'],
                        //'sub_stock'      => $order['sub_stock'],
                        'sub_stock'      => 0, // 利用通知修改此字段，防止程序异常
                        'cost'           => $order['cost'],
                        'weight'         => $order['weight'],
                        'pic_url'        => $order['pic_url'],
                        'shipping_type'  => $order['shipping_type'],
                        'sku_json'       => encode_json($order['sku_json']),
                        'cat_id'         => $order['cat_id'],
                        'tao_id'         => $order['tao_id'],
                        'outer_id'       => $order['outer_id'],
                        'goods_type'     => $order['goods_type'],
                        'promotion_details'=> encode_json($order['promotion_details']),
                        'discount_details' => encode_json($order['discount_details']),
                        'ext_params'     => $order['ext_params'],
                        'main_tag'       => $order['main_tag'],
                        'buyer_message'  => $order['buyer_message'],
                        'original_price' => $order['original_price'],
                    );

                    // 立即减库存
                    if($order['sub_stock']){
                        $temp = array('goods_id' => $order['goods_id'], 'product_id' => $order['product_id'], 'quantity' => -$order['quantity'], 'type' => $order['type'], 'mid' => $buyer['id'], 'tid' => $tid, 'oid' => $oid, 'created' => NOW_TIME);
                        if($order['type'] == OrderType::GIFT){
                            $temp['status'] = 0;
                        }
                        $substockList[] = $temp;
                    }
                }
            }
        }

        $redis = new Redis();
        // 立即更改活动库存
        $decrList = $this->decrStockBy($redis);
        if(is_null($decrList)){
            $this->error = '活动库存不足';
            return;
        }

        // 保存数据
        $this->startTrans();
        try{
            // 保存订单
            $result = M('trade')->addAll($tradeList);
            if($result < 1){
                E('trade创建失败');
            }
            
            if(count($discountList) > 0){
                $result = M('trade_discount')->addAll($discountList);
                if($result < 1){
                    E('discount创建失败');
                }
            }
            $result = M('trade_order')->addAll($orderList);
            if($result < 1){
                E('order创建失败');
            }
            if($payType == PayType::CODPAY){
                $result = M('trade_buyer')->addAll($tradeBuyerList);
                $result = M('trade_seller')->addAll($tradeSellerList);
            }
            if(!empty($tradeList[0]['discount_fee'])){
                M()->execute("UPDATE wx_user SET coupon=coupon-{$tradeList[0]['discount_fee']} WHERE mid={$tradeList[0]['buyer_id']}");
            }
            // 会员资金流水
            if(count($memberBalance) > 0){
                $MBalance = new BalanceModel();
                foreach ($memberBalance as $balance){
                    $MBalance->add($balance);
                }
            }
            
            $this->commit();
        }catch (\Exception $e){
            $this->rollbackStock($redis, $decrList);
            $this->rollback();
            $this->error = $e->getMessage();
            return;
        }
        
        // 立即减库存(通常为活动商品和赠品)
        if(count($substockList) > 0){
            $key = 'TradeMinusStock';
            foreach ($substockList as $item){
                $redis->lPush($key, json_encode($item, JSON_UNESCAPED_UNICODE));
            }
            $redis->publish($key, NOW_TIME);
        }
        
        // 优惠券核销
        if(count($couponList) > 0){
            $key = 'CouponUsed';
            foreach ($couponList as $item){
                $redis->lPush($key, json_encode($item, JSON_UNESCAPED_UNICODE));
            }
            $redis->publish($key, NOW_TIME);
        }
        
        // 已支付
        if(count($paidList) > 0){
            $key = 'TradePaid';
            foreach ($paidList as $item){
            	$redis->lPush($key, $item);
            }
            $redis->publish($key, NOW_TIME);
        }
        
        // 通知订单已创建
        $key = 'TradeCreated';
        foreach ($tradeList as $trade){
            $redis->lPush($key, $trade['tid']);
        }
        $redis->publish($key, NOW_TIME);

        // 删除book
        $this->delBook();
        $tidList['buyer'] = $tradeBuyerList;
        $tidList['seller'] = $tradeSellerList;
        return $tidList;
    }
    
    /**
     * 定量加库存(用于下单失败)
     */
    private function rollbackStock($redis, $decrList){
        foreach ($decrList as $key=>$stock){
            $redis->incrBy($key, $stock);
        }
    }
    
    /**
     * 定量减库存
     */
    private function decrStockBy($redis){
        $decrList = array();
        foreach ($this->activityStock as $item){
            // 产品库存
            $key = 'stock_p'.$item['product_id'];
            $result = $redis->decrBy($key, $item['quantity']);
            $decrList[$key] = $item['quantity'];
            if($result < 0){
                $this->rollbackStock($redis, $decrList);
                return;
            }
    
            // 商品库存
            $key = 'stock_g'.$item['goods_id'];
            $redis->decrBy($key, $item['quantity']);
            $decrList[$key] = $item['quantity'];
        }
        return $decrList;
    }
    
    private function tradeSave($bookKey, $groups){
        $groups = json_encode($groups, JSON_UNESCAPED_UNICODE);
        $groups = addslashes($groups);
        $this->execute("UPDATE trade_book_content SET groups='{$groups}' WHERE id='{$bookKey}'");
    }
    
    /**
     * 计算运费
     */
    public function tradeFreight($groups, $address, $adjustParams){
        if(!$address['city_code']){
            return $groups;
        }
        
        $postageList = array();
        $taoList = array(
        	'tao_id' => array(), // 用于组合SQL
        	'items' => array() // 用于计算运费
        );
        foreach ($groups as $shopId=>$group){
            foreach ($group['trades'] as $freightId=>$trade){
                if($freightId == '0'){
                    $groups[$shopId]['trades'][$freightId]['express_id'] = 0;
                    $groups[$shopId]['trades'][$freightId]['express'][] = array('id' => 0, 'name' => '卖家承担运费', 'money' => 0, 'checked' => 1);
                    continue;
                }
                
                $isAliFreight = substr($freightId, 0, 1) == 'T';
                if($isAliFreight){
                    $groups[$shopId]['trades'][$freightId]['express'][] = array('id' => 10, 'name' => '商家自配', 'money' => 0, 'checked' => 1);
                }
                
                foreach ($trade['orders'] as $order){
                    // 淘系模板
                    if($isAliFreight){
                        // 忽略包邮标记的商品
                        if($order['free_postage']){
                            continue;
                        }
                        
                        if(!isset($taoList['tao_id'][$order['token_id']])){
                        	$taoList['tao_id'][$order['token_id']] = array($order['tao_id']);
                        }else if(!in_array($order['tao_id'], $taoList['tao_id'][$order['token_id']])){
                        	$taoList['tao_id'][$order['token_id']][] = $order['tao_id'];
                        }
                        if($order['sku_json'][0]['kid']==0){
                            $order['sku_json'] = '';
                        }
                        $taoList['items'][$shopId.'_'.$freightId][$order['token_id']][] = array('tao_id' => $order['tao_id'], 'quantity' => $order['quantity'], 'sku_json' => $order['sku_json']);
                    }else{
                        $weight = bcadd($order['quantity'], $order['weight'], 2);
                        if(!isset($postageList[$freightId])){
                            $data = array('quantity' => 0, 'weight' => 0, 'payment' => 0);
                            $postageList[$freightId]['shop_id'] = $shopId;
                            $postageList[$freightId]['default'] = $data;
                            $postageList[$freightId]['other'] = $data;
                        }
                        
                        $data = $postageList[$freightId]['other'];
                        $data['quantity'] += $order['quantity'];
                        $data['weight'] = bcadd($data['weight'], $weight, 2);
                        $data['payment'] = bcadd($data['payment'], $order['payment'], 2);
                        $postageList[$freightId]['other'] = $data;
                        
                        // 忽略包邮标记的商品
                        if($order['free_postage']){
                            continue;
                        }
                        
                        $data = $postageList[$freightId]['default'];
                        $data['quantity'] += $order['quantity'];
                        $data['weight'] = bcadd($data['weight'], $weight, 2);
                        $data['payment'] = bcadd($data['payment'], $order['payment'], 2);
                        $postageList[$freightId]['default'] = $data;
                    }
                }
            }
        }
        
        // 系统模板
        if(count($postageList) > 0){
            $groups = $this->getSystemFreight($groups, $postageList, $address, $adjustParams);
        }
        
        // 淘宝模板
        if(count($taoList['items']) > 0 && $address['city_code']){
            $list = $this->getAlibabaFreight($taoList, $address);
            foreach ($list as $gk=>$result){
                $group = explode('_', $gk);
                $express = $groups[$group[0]]['trades'][$group[1]]['express'][0];
                $express = array_merge($express, $result);
                $groups[$group[0]]['trades'][$group[1]]['express'][0] = $express;
                $groups[$group[0]]['trades'][$group[1]]['total_postage'] = $express['money'];
            }
        }
        
        return $groups;
    }
    
    private function getSystemFreight($groups, $postageList, $address, $adjustParams){
        $freightIds = array_keys($postageList);
        $freightIds = implode(',', $freightIds);
        $templates = $this->query("SELECT id, checked, config FROM template_freight WHERE id IN ({$freightIds})");
        
        // 将运费模板解析成键值对，方便遍历数据
        $_template = array();
        foreach ($templates as $item){
            $item['config'] = json_decode($item['config'], true);
            $_template[$item['id']] = $item;
        }
        $templates = $_template;
        
        $expressList = include COMMON_PATH.'\Conf\express.php';
        foreach ($postageList as $freightId=>$data){
            $shopId = $data['shop_id'];
            $checked = $templates[$freightId]['checked'];
            
            // 不同快递
            $checkedIndex = 0;
            foreach ($templates[$freightId]['config'] as $i=>$template){
                $money = 0;
                
                // 默认快递享受优惠信息，其他快递不享受
                if(in_array($checked, $template['express'])){
                    if($data['default']['quantity'] > 0){
                        $num = $template['type'] == 0 ? $data['default']['weight'] : $data['default']['quantity'];
                        $money = $this->getFreightFee($template, $address, $num, $data['default']['payment']);
                    }
                }else{
                    $num = $template['type'] == 0 ? $data['other']['weight'] : $data['other']['quantity'];
                    $money = $this->getFreightFee($template, $address, $num, $data['other']['payment']);
                }
                
                $errcode = 0;
                $errmsg = '';
                if(!is_numeric($money) || $money < 0){
                    $errcode = 1;
                    $errmsg = $money;
                    $money = 99999999;
                }
        
                foreach ($template['express'] as $expressId){
                    if(!isset($expressList[$expressId])){
                        E('未知快递公司');
                    }
                    $express = $expressList[$expressId];
                    $groups[$shopId]['trades'][$freightId]['express'][] =  array('id' => $express['id'], 'name' => $express['name'], 'money' => $money, 'checked' => 0, 'errcode' => $errcode, 'errmsg' => $errmsg);
        
                    $isChecked = (is_null($adjustParams['address']) ? $checked : $adjustParams[$shopId.'_'.$freightId.'_express']) == $express['id'];
                    if($isChecked){
                        $checkedIndex = count($groups[$shopId]['trades'][$freightId]['express']) - 1;
                    }
                }
            }
        
            $checked = $groups[$shopId]['trades'][$freightId]['express'][$checkedIndex];
            $groups[$shopId]['trades'][$freightId]['express'][$checkedIndex]['checked'] = 1;
            $groups[$shopId]['trades'][$freightId]['total_postage'] = $checked['money'];
            $groups[$shopId]['trades'][$freightId]['express_id'] = $checked['id'];
            
            // 货到付款
            if($checked['id'] === 1){
                $groups[$shopId]['trades'][$freightId]['type'] = OrderType::COD;
                $groups[$shopId]['trades'][$freightId]['pay_type'] = PayType::CODPAY;
            }
        }
        
        return $groups;
    }
    
    private function getFreightFee($template, $address, $num, $payment){
        $money = -1;
        $isSpecial = false;
        $disabled = false;
        $errmsg = '';
        foreach ($template['specials'] as $special){
            if(!in_array($address['receiver_province'], $special['areas'])){
                continue;
            }
            
            // 所选地区禁止下单
            if(!$special['order']){
                if($special['payment'] == 0){
                    $errmsg = '此地区不支持配送';
                    $disabled = true;
                    continue;
                }
                if($payment < $special['payment']){
                    $errmsg = '未满'.$special['payment'].'元不支持配送';
                    $disabled = true;
                    continue;
                }
            }
            
            // 最低金额不足
            if($special['payment'] > 0 && $payment < $special['payment']){
                $errmsg = '未满'.$special['payment'].'元不支持配送';
                continue;
            }
            
            $postage = $special['postage'];
            if($num > $special['start']){
                $sub = bcsub($num, $special['start'], 2);
                $temp = bcdiv($sub, $special['plus'], 2);
                $temp = ceil($temp);
                $temp = bcmul($special['postage_plus'], $temp, 2);
                $postage = bcadd($postage, $temp, 2);
            }
            
            // 取最小的金额作为运费
            $postage = floatval($postage);
            if($postage < $money || $money == -1){
                $isSpecial = true;
                $disabled = false;
                $money = $postage;
            }
        }
        
        // 在指定地区，并且禁止下单
        if($isSpecial){
            if($disabled){
                return $errmsg;
            }else{
                return $money;
            }
        }
        
        // 默认地区
        $default = $template['default'];
        if(!$default['order']){
            if($errmsg != ''){
                return $errmsg;
            }
            
            return '此地区不支持发货';
        }
        
        $money = $default['postage'];
        if($num > $template['start']){
            $sub = bcsub($num, $default['start'], 2);
            $temp = bcdiv($sub, $default['plus'], 2);
            $temp = ceil($temp);
            $temp = bcmul($default['postage_plus'], $temp, 2);
            $money = bcadd($money, $temp, 2);
        }
        
        return floatval($money);
    }
    
    /**
     * 获取1688运费
     */
    private function getAlibabaFreight($dataList, $address){
    	$sql = array();
    	foreach ($dataList['tao_id'] as $tokenId=>$taoIdList){
    		$sql[] = "SELECT ar.id, ar.token_id, ar.relation, goods.cat_id,
			    		goods.login_id, IF(ar.relation=2, goods.daixiao_price, goods.price) AS price,
			    		goods.unit, goods.freight_id, goods.products
		    		FROM alibaba_relation AS ar
		    		INNER JOIN alibaba_goods AS goods ON goods.id=ar.id
		    		WHERE ar.token_id='{$tokenId}' AND ar.id IN (".implode(',', $taoIdList).")";
    	}
    	$sql  = implode(' UNION ALL ', $sql);
        $list = $this->query($sql);
        
        $taoList = $weigong = $dashichang = $daixiao = array();
        foreach ($list as $i=>$item){
        	$products = json_decode($item['products'], true);
        	$tokenId  = $item['token_id'];
        	if(!$products){
        		$key = $item['id'].'_';
        		$taoList[$tokenId][$key] = array(
        			'id'     => $item['id'],
        			'unit'       => $item['unit'],
        			'price'      => $item['price'],
        			'freight_id' => $item['freight_id'],
        			'sku_id'     => null,
        			'spec_id'    => null,
        			'daixiao'    => $item['price'],
        			'relation'   => $item['relation'],
        			'cat_id'     => $item['cat_id']
        		);
        		continue;
        	}
        	
        	foreach ($products as $product){
        		$key = $item['id'].'_'.IdWork::convertSKU($product['sku_json']);
        		$taoList[$tokenId][$key] = array(
        			'id'     => $item['id'],
        			'unit'       => $item['unit'],
        			'price'      => $product['price'],
        			'freight_id' => $item['freight_id'],
        			'sku_id'     => $product['sku_id'],
        			'spec_id'    => $product['spec_id'],
        			'daixiao'    => $product['daixiao'],
        			'relation'   => $item['relation'],
        			'cat_id'     => $item['cat_id']
        		);
        	}
        }
        
        $returns = array();
        foreach($dataList['items'] as $gk=>$groups){
        	$returns[$gk] = array('errcode' => 0, 'errmsg' => '', 'money' => 0);
        	foreach ($groups as $tokenId=>$items){
        		foreach ($items as $item){
        			$list = $taoList[$tokenId];
        			$key  = $item['tao_id'].'_'.IdWork::convertSKU($item['sku_json']);
        			if(!isset($list[$key])){
        				E('商品SKU丢失');
        			}
        			
        			$tao = $list[$key];
        			switch ($tao['relation']){
        				case 1: // 大市场
        					$dashichang[$gk][$tokenId][] = array(
        						'cartId'  => $tao['cat_id'],
        						'id'      => $tao['id'],
        						'offerId' => $tao['id'],
        						'quantity'=> $item['quantity'],
        						'specId'  => $tao['spec_id']
        					);
        				case 2: // 代销
        					$daixiao[$gk][$tokenId][] = array(
        						'cartId'  => $tao['cat_id'],
        						'id'      => $tao['id'],
        						'offerId' => $tao['id'],
        						'quantity'=> $item['quantity'],
        						'specId'  => $tao['spec_id']
        					);
        				case 3: // 微供
        					$model = array(
        						'offerId'   => $tao['id'],
        						'quantity'  => $item['quantity'],
        						'unitPrice' => $tao['price'],
        						'freightId' => $tao['freight_id']
        					);
        					if($tao['sku_id']){
        						$model['specId'] = $tao['spec_id'];
        						$model['skuId']  = $tao['sku_id'];
        					}
        					$weigong[$gk][$tokenId][] = $model;
        					break;
        				default:
        					E('未知商品业务类型');
        			}
        		}
        	}
        }
        
        // 微供运费
        foreach ($weigong as $gk=>$items){
            $data = array('errcode' => 0, 'errmsg' => '', 'money' => 0);
            foreach ($items as $tokenId=>$model){ // 按常理来说数组中的tokenid只有一个，但是为了方便调用数据，所以数组就这么个结构了
                $Interface = $this->getAlibabaAuth($tokenId);
                $result = $Interface->getWGExpressFee($model, $address['city_code'], $address['county_code']);
                // print_r($address);
                if($result['errcode']){
                    $data['errcode'] = $result['errcode'];
                    $data['errmsg']  = $result['errmsg'];
                }else{
                    $data['money']   = bcadd($data['money'], $result['freight_fee'], 2);
                }
                
            }
            
            if($data['errcode']){
            	$returns[$gk]['errcode'] = $data['errcode'];
            	$returns[$gk]['errmsg']  = $data['errmsg'];
            }else{
            	$returns[$gk]['money']   = bcadd($returns[$gk]['money'], $data['money'], 2);
            }
        }
        // die;
        // 大市场运费
        foreach ($dashichang as $gk=>$items){
            $data = array('errcode' => 0, 'errmsg' => '', 'money' => 0);
            foreach ($items as $tokenId=>$model){
                $Interface = $this->getAlibabaAuth($tokenId);

                $result = $Interface->orderPreview($model, $address);
                if($result['errcode']){
                    $data['errcode'] = $result['errcode'];
                    $data['errmsg']  = $result['errmsg'];
                }else{
                    $data['money']   = bcadd($data['money'], $result['sumCarriage'], 2);
                }
            }
            
            if($data['errcode']){
            	$returns[$gk]['errcode'] = $data['errcode'];
            	$returns[$gk]['errmsg']  = $data['errmsg'];
            }else{
            	$returns[$gk]['money']   = bcadd($returns[$gk]['money'], $data['money'], 2);
            }
        }
        
        // 代销运费
        foreach ($daixiao as $gk=>$items){
            $data = array('errcode' => 0, 'errmsg' => '', 'money' => 0);
            foreach ($items as $tokenId=>$model){
                $Interface = $this->getAlibabaAuth($tokenId);
        
                $result = $Interface->orderPreview($model, $address, true);
                if($result['errcode']){
                    $data['errcode'] = $result['errcode'];
                    $data['errmsg']  = $result['errmsg'];
                }else{
                    $data['money']   = bcadd($data['money'], $result['sumCarriage'], 2);
                }
            }
            
            if($data['errcode']){
            	$returns[$gk]['errcode'] = $data['errcode'];
            	$returns[$gk]['errmsg']  = $data['errmsg'];
            }else{
            	$returns[$gk]['money']   = bcadd($returns[$gk]['money'], $data['money'], 2);
            }
        }
        
        return $returns;
    }
    
    /**
     * 获取1688接口
     */
    private function getAlibabaAuth($tokenId){
        static $InterfaceList = array();
        if(!isset($InterfaceList[$tokenId])){
            $InterfaceList[$tokenId] = new \Org\Alibaba\AlibabaAuth($tokenId);
        }
        return $InterfaceList[$tokenId];
    }
    
    /**
     * 去数据库查找产品信息
     */
    public function getDBProducts($bookProducts, &$login, $address = ''){
        $goodsIds = $productIds = array();
        foreach ($bookProducts as $item){
            if(!in_array($item['product_id'], $productIds)){
                $productIds[] = $item['product_id'];
            }
            if(!in_array($item['goods_id'], $goodsIds)){
                $goodsIds[] = $item['goods_id'];
            }
        }
        $goodsIds = implode(',', $goodsIds);
        $productIds = implode(',', $productIds);

        $sql = "SELECT
                    goods.id AS goods_id, product.id AS product_id, goods.title, goods.cat_id, goods.tag_id,
                    IF(product.pic_url='', goods.pic_url, product.pic_url) AS pic_url,
                    product.original_price, product.price, product.score, product.custom_price, goods.member_discount,
                    goods.goods_type, goods.is_virtual, goods.tao_id, goods.invoice, goods.warranty, goods.returns,
                    goods.buy_quota, goods.day_quota, goods.every_quota, goods.level_quota, goods.min_order_quantity,
                    product.stock, product.weight, product.cost, product.sku_json, shop.aliid,
                    goods.is_display, goods.sold_time, goods.is_del, content.remote_area,
                    goods.shop_id, shop.name AS shop_name, goods.freight_id, product.outer_id
                FROM mall_goods AS goods
                LEFT JOIN mall_goods_content AS content ON content.goods_id=goods.id
                LEFT JOIN shop ON shop.id = goods.shop_id
                LEFT JOIN mall_product AS product ON product.goods_id=goods.id AND product.id IN ({$productIds})
                WHERE goods.id IN({$goodsIds})";
        $list = $this->query($sql);
        $productList = $goodsList = $invalidList = array();
        foreach ($list as $item){
            if(is_numeric($item['product_id'])){
                $productList[$item['product_id']] = $item;
            }else{
                $goodsList[$item['goods_id']] = $item;
            }
        }

        // 覆盖活动标记（如果没有传活动标记则不参加活动）
        $list = array();
        foreach ($bookProducts as $item){
            $product                  = isset($productList[$item['product_id']]) ? $productList[$item['product_id']] : $goodsList[$item['goods_id']];
            $product['activity_id']   = $item['activity_id'];
            $product['settlement']    = array(
                'quantity'  => $item['quantity'],
                'errmsg'    => '',
                'can_buy'   => 1,
                'invalid'   => 0
            );
            $product['quantity'] = $item['quantity'];
            $list[] = $product;
        }

        $list = $this->goodsListHandler($list, $login);
    
        foreach ($list as $i=>$item){
            $item['view_price'] = $item['view_price'][0];
            if($item['settlement']['can_buy']){
                if($item['remote_area']){
                    $remote_area = explode(',', $item['remote_area']);
                    if(in_array($address['province_code'], $remote_area)){
                        unset($list[$i]);
                        $item['settlement']['can_buy'] = 0;
                        $item['settlement']['errmsg'] = '快递无法到达'.$address['receiver_province'];
                        $invalidList[] = $item;
                        continue;
                    }
                }
                $list[$i] = $item;
            }else{
                unset($list[$i]);
                $invalidList[] = $item;
            }
        }

        return array('list' => $list, 'invalidList' => $invalidList);
    }
    
    /**
     * 交易订单分组(店铺id+运费模板id)
     */
    protected function getTradeGroup($list, &$buyer, $adjustParams){
        $payTimeout = NOW_TIME + C('ORDER_TIMEOUT');
        $groupList = array();

        foreach ($list as $item){
            $tradeGroup = null;
            if(!isset($groupList[$item['shop_id']])){
                $tradeGroup = array(
                    'shop_id'     => $item['shop_id'],
                    'shop_name'   => $item['shop_name'],
                    'project_id'  => IdWork::getProjectId($item['shop_id']),
                    'total_fee'   => 0, // 商品总额
                    'total_postage' => 0,
                    'discount_fee'=>0,
                    'payment'     => 0, // 微信支付
                    'total_score' => 0, // 总积分
                    'discount_score' => 0,
                    'payscore'    => 0, // 应付积分
                    'trades'      => array()
                );
            }else{
                $tradeGroup = $groupList[$item['shop_id']];
            }
            
            $trade = null;
            if(!isset($tradeGroup['trades'][$item['freight_id']])){
                $trade = array(
                    'type'          => OrderType::NORMAL,
                    'total_fee'     => 0, // 商品总额
                    'total_postage' => 0, // 运费
                    'discount_fee'  => 0, // 折扣总额
                    'payment'       => 0, // 应付(微信)
                    'total_score'   => 0, // 总积分
                    'discount_score'=> 0, // 积分优惠
                    'payscore'      => 0, // 应付(积分)
                    'paid_balance'  => 0, // 可提现抵用
                    'paid_wallet'   => 0, // 不可提现抵用
                    'paid_score'    => 0, // 支付积分金额
                    'freight_id'    => $item['freight_id'],  // 运费模板
                    'kind'          => 0, // 商品种类(orders的数量)
                    'total_quantity'=> 0, // 总件数
                    'total_weight'  => 0,
                    'orders'        => array(), // 订单商品
                    'express'       => array(),
                    'express_id'    => -1,
                    'anonymous'     => $adjustParams['anonymous'],
                    'buyer_remark'  => htmlspecialchars($adjustParams[$item['shop_id'].'_'.$item['freight_id'].'_remark']),
                    'promotion_details' => array(), // 活动信息(discount_fee=0)
                    'discount_details'  => array(), // 优惠信息(discount_fee>=0)
                    'pay_timeout'   => $payTimeout,
                );
            }else{
                $trade = $tradeGroup['trades'][$item['freight_id']];
            }
            
            $quantity = $item['settlement']['quantity'];
            $totalFee = bcmul($item['price'], $quantity, 2);
            $order = array(
                'goods_id'        => $item['goods_id'],
                'product_id'      => $item['product_id'],
                'type'            => OrderType::NORMAL,
                'price_type'      => $item['price_type'],
                'title'           => $item['title'],
                'tag_id'          => $item['tag_id'],
                'spec'            => $item['spec'],
                'errmsg'          => $item['settlement']['errmsg'],
                'cat_id'          => $item['cat_id'],
                'tag_id'          => $item['tag_id'],
                'quantity'        => $quantity,
                'price'           => $item['price'],
                'total_fee'       => $totalFee,
                'discount_fee'    => 0,
                'payment'         => $totalFee,
                'score'           => $item['score'],
                'total_score'     => 0,
                'discount_score'  => 0,
                'payscore'        => 0,
                'goods_type'      => $item['goods_type'],
                'weight'          => $item['weight'],
                'outer_id'        => $item['outer_id'],
                'main_tag'        => $item['main_tag'],
                'view_price'      => $item['view_price'],
                'activity'        => $item['activity'],
                'is_virtual'      => $item['is_virtual'],
                'pic_url'         => $item['pic_url'],
                'cost'            => $item['cost'],
                'original_price'  => $item['original_price'],
                'other_discount'  => $item['other_discount'],
                'shipping_type'   => $item['is_virtual'] ? 'virtual' : 'express',
                'promotion_details'=> array(),
                'discount_details'=> array(),
                'free_postage'    => 0,
                'remote_area'     => explode(',', $item['remote_area']),
                'sub_stock'       => $item['sub_stock'] ? 1 : 0,
                'ext_params'      => array(),
                'buyer_message'   => htmlspecialchars($adjustParams[$item['product_id'].'_message']),
                'sku_json'        => $item['sku_json'],
                'tao_id'          => $item['tao_id'],
                'token_id'        => $item['aliid']
            );
            
            // 扩展参数
            if($item['day_quota'] > 0){// 日限售
                $order['ext_params']['day_quota'] = $item['day_quota'];
            }
            if($item['every_quota'] > 0){// 每人每日限购
                $order['ext_params']['every_quota'] = $item['every_quota'];
            }
            if($item['invoice']){// 有发票
                $order['ext_params']['invoice'] = 1;
            }
            if($item['warranty']){// 有保修
                $order['ext_params']['warranty'] = 1;
            }
            if($item['returns']){// 可退换
                $order['ext_params']['returns'] = 1;
            }
            if($item['agent']){ // 单品代理
                $order['ext_params']['agent'] = array('id' => $item['agent']['id'], 'target' => $item['agent']['target'], 'curren' => $item['agent']['level']);
            }
            
            // 会员卡
            if($order['goods_type'] == 1){
                $trade['type'] = OrderType::MEMBER_CARD;
                $order['type'] = OrderType::MEMBER_CARD;
            }else if(!empty($item['activity'])){// 合并显示优惠信息
                $trade['promotion_details'][] = $item['activity'];
                $order['promotion_details'][] = $item['activity'];
                $order['type'] = $item['activity']['type'];
                
                // 活动立即减库存
                $trade['pay_timeout'] = NOW_TIME + 15 * 60;
                $order['sub_stock'] = 1;
                $this->activityStock[] = array('quantity' => $order['quantity'], 'goods_id' => $order['goods_id'], 'product_id' => $order['product_id']);
            }else if($item['member_discount']){// 会员折扣
                $member = $buyer[$tradeGroup['project_id']];
                if($member['discount'] > 0 && $member['discount'] < 1){
                    $orginal = bcdiv($item['price'], $member['discount'], 2);
                    $sheng = bcsub($orginal, $item['price'], 2);
                    $sheng = floatval($sheng);
                    if($sheng > 0){
                        $order['type'] = OrderType::MEMBER_DISCOUNT;
                        $order['promotion_details'][] = array(
                            'id'    => $member['card_id'],
                            'type'  => 'member_discount',
                            'name'  => '会员折扣',
                            'discount_fee' => $sheng,
                            'description' => $member['agent_title'].'打'.($member['discount']*10).'折'
                        );
                    }
                }
            }
            // 积分兑换
            if($order['price_type'] == 3 && $order['type'] == OrderType::NORMAL){
                $order['type'] = OrderType::SCORE;
            }

            $trade['kind']++;
            $trade['total_quantity'] += $quantity;
            $totalWeight = bcmul($item['weight'], $quantity);
            $trade['total_weight'] = bcadd($trade['total_weight'], $totalWeight, 2);

            // 如果是积分商品
            if($order['price_type'] == 3){
                $order['total_score'] = bcmul($item['score'], $quantity, 2);
                $order['payscore'] = $order['total_score'];
            }

            $trade['orders'][] = $order;
            $tradeGroup['trades'][$item['freight_id']] = $trade;
            $groupList[$item['shop_id']] = $tradeGroup;
        }
        
        return $groupList;
    }
    
    /**
     * 获取可以参加优惠的商品
     */
    private function getDiscountOrders($groupList){
        $params = array();
        foreach($groupList as $shopId=>$group){
            foreach ($group['trades'] as $freightId=>$trade){
                $coupon = $params[$shopId][$freightId];
                if(empty($coupon)){
                    $coupon = array('payment' => 0, 'goods' => array(), 'products' => array(), 'cat_ids' => array(), 'tag_ids' => array());
                }
        
                foreach ($trade['orders'] as $i=>$order){
                    if(!$order['other_discount'] || $order['payment'] < 0.01){
                        continue;
                    }
        
                    //$field = $order['type'] == 'score' ? 'payscore' : 'payment';
                    $field = 'payment';
                    $goods = $coupon['goods'][$order['goods_id']];
                    if(!$goods){
                        $goods = array('payment' => 0, 'products' => array());
                    }
                    $goods['products'][$i] = $order[$field];
                    $goods['payment'] = bcadd($goods['payment'], $order[$field], 2);
        
                    $coupon['payment'] = bcadd($coupon['payment'], $order[$field], 2);
                    $coupon['goods'][$order['goods_id']] = $goods;
                    $coupon['products'][$i] = $order[$field];

                    $coupon['cat_ids'][$order['cat_id']][] = $order['goods_id'];
                    foreach ($order['tag_id'] as $tagId){
                        if($tagId > 1000){
                            $coupon['tag_ids'][$tagId][] = $order['goods_id'];
                        }
                    }
                    
                    $params[$shopId][$freightId] = $coupon;
                }
            }
        }
        
        return $params;
    }
    
    /**
     * 订单满减
     */
    private function tradeManjian($groupList, &$buyer, $adjustParams){
        $params = $this->getDiscountOrders($groupList);
        if(count($params) == 0){
            return $groupList;
        }
        
        // 查找满减活动
        $manjianList = $this->getManJian($buyer, $params);
        foreach($manjianList as $manjian){
            $shopId = $manjian['shop_id'];
            $freightId = $manjian['freight_id'];
            $trade = $groupList[$shopId]['trades'][$freightId];
            
            // 是否选中
            $checked = false;
            $key = $shopId.'_'.$freightId.'_manjian';
            if($adjustParams['allow_discount_fee'] && (!isset($adjustParams[$key]) || $adjustParams[$key] == $manjian['id'])){
                $checked = true;
            }
            
            $trade['discount_details'][] = array(
                'id'   => $manjian['id'],
                'type' => 'manjian',
                'name' => $manjian['name'],
                'checked' => $checked,
                'discount_fee' => sprintf('%.2f', $manjian['discount_fee']),
                'free_postage' => $manjian['free_postage'],
                'coupon_id' => $manjian['coupon_id'],
                'score' => $manjian['score'],
                'description' => $manjian['description'],
                'gift_id'   => $manjian['gift']['gift_id'],
            );
        
            if($checked && ($manjian['discount_fee'] > 0 || $manjian['free_postage'])){
                foreach ($manjian['orders'] as $i=>$discountFee){
                    $order = $trade['orders'][$i];
                
                    // 减现金/积分
                    if($discountFee > 0){
                        $order['discount_fee'] = bcadd($order['discount_fee'], $discountFee, 2);
                        $order['payment'] = bcsub($order['total_fee'], $order['discount_fee'], 2);
                    }
                
                    // 包邮
                    if($manjian['free_postage']){
                        $order['free_postage'] = 1;
                    }

                    $order['discount_details'][] = array(
                        'id'   => $manjian['id'],
                        'type' => 'manjian',
                        'title' => $manjian['name'],
                        'discount_fee' => $discountFee,
                        'free_postage' => $manjian['free_postage']
                    );
                    $trade['orders'][$i] = $order;
                }
            }
        
            // 送赠品
            if($checked && $manjian['gift']){
                $trade['orders'][] = $manjian['gift'];
            }

            $groupList[$manjian['shop_id']]['trades'][$manjian['freight_id']] = $trade;
        }
        
        return $groupList;
    }
    
    private function getDiscountProduct($db, $trade){
        $rangeType = $db['range_type'];
        $rangeList = $db['range_value'];
        $rangeExclude = $db['range_exclude'];
        $activityGoods = array();
        $payment = 0;
        if($rangeType == 1){ // 指定商品满减
            foreach ($trade['goods'] as $goodsId=>$data){
                if(!in_array($goodsId, $rangeList)){
                    continue;
                }
                $payment = bcadd($payment, $data['payment'], 2);
                $activityGoods += $data['products'];
            }
        }else if($rangeType == 2){ // 指定分组满减
            foreach ($trade['tag_ids'] as $tagId=>$goodsIds){
                if(!in_array($tagId, $rangeList)){
                    continue;
                }
        
                foreach($goodsIds as $goodsId){
                    // 排除指定商品
                    if(in_array($goodsId, $rangeExclude)){
                        continue;
                    }
        
                    $data = $trade['goods'][$goodsId];
                    $payment = bcadd($payment, $data['payment'], 2);
                    $activityGoods += $data['products'];
                }
            }
        }else if($rangeType == 3){ // 指定类目满减
            foreach ($trade['cat_ids'] as $catId=>$goodsIds){
                if(!in_array($catId, $rangeList)){
                    continue;
                }
        
                foreach($goodsIds as $goodsId){
                    // 排除指定商品
                    if(in_array($goodsId, $rangeExclude)){
                        continue;
                    }
        
                    $data = $trade['goods'][$goodsId];
                    $payment = bcadd($payment, $data['payment'], 2);
                    $activityGoods += $data['products'];
                }
            }
        }else if(count($rangeExclude) > 0){// 排除指定商品
            foreach ($trade['goods'] as $goodsId=>$data){
                if(!in_array($goodsId, $rangeExclude)){
                    $payment = bcadd($payment, $data['payment'], 2);
                    $activityGoods += $data['products'];
                }
            }
        }else{
            $payment = $trade['payment'];
            $activityGoods = $trade['products'];
        }
        
        return array('payment' => floatval($payment), 'products' => $activityGoods);
    }
    
    private function getManJian($buyer, $params){
        $result = array();
        $shops = array_keys($params);
        $shops = implode(',', $shops);

        $sql = "SELECT id, activity_name, shop_id, meet, range_type, range_value, range_exclude, config, start_time, end_time
                FROM mall_manjian
                WHERE shop_id IN ({$shops}) AND ".NOW_TIME." BETWEEN start_time AND end_time";
        $list = $this->query($sql);
        $giftList = array();
        foreach ($list as $manjian){
            $configList = null;
            $manjian['range_value'] = $manjian['range_type'] > 0 ? explode(',', $manjian['range_value']) : null;
            $manjian['range_exclude'] = $manjian['range_exclude'] ? explode(',', $manjian['range_exclude']) : array();
            
            foreach($params[$manjian['shop_id']] as $freightId=>$trade){
                $payment = $trade['payment'];
                if($payment < $manjian['meet']){
                    continue;
                }

                // 满足最低满减条件
                $temp = $this->getDiscountProduct($manjian, $trade);
                $payment = $temp['payment'];
                if($payment < $manjian['meet']){
                    continue;
                }
                
                $activityGoods = $temp['products'];
                if(is_null($configList)){
                    $configList = json_decode($manjian['config'], true);
                }
                
                $config = null;
                foreach ($configList as $meet=>$_config){
                    if($payment >= $meet){
                        $config = $_config;
                    }else{
                        break;
                    }
                }

                $description = array();
                if($config['cash']){
                    $description[] = '减'.$config['cash'];
                }
                if($config['free_postage']){
                    $description[]= '包邮';
                }
                if($config['coupon']){
                    $description[]= '送优惠券';
                }
                if($config['score']){
                    $description[]= '送'.$config['score'].'积分';
                }
                if($config['gift']){
                    if(!isset($giftList[$config['gift']])){
                        $giftList[$config['gift']] = 1;
                    }else{
                        $giftList[$config['gift']]++;
                    }
                }

                $activity = array(
                    'id'           => $manjian['id'],
                    'name'         => $manjian['activity_name'],
                    'type'         => 'manjian',
                    'checked'      => 1,
                    'discount_fee' => sprintf('%.2f', $config['cash']),
                    'free_postage' => $config['free_postage'],
                    'coupon_id'    => $config['coupon_id'],
                    'score'        => $config['score'],
                    'start_time'   => $manjian['start_time'],
                    'end_time'     => $manjian['end_time'],
                    'description'  => '满'.$meet.implode('、', $description),
                    'orders'       => avg_dsicount($config['cash'], $activityGoods),
                    'gift_id'      => $config['gift'],
                    'shop_id'      => $manjian['shop_id'],
                    'freight_id'  => $freightId
                );
                $result[] = $activity;
                
                unset($params[$manjian['shop_id']][$freightId]);
            }
        }
        
        // 查找赠品
        if(count($giftList) == 0){
            return $result;
        }
        
        if(count($giftList) > 0){
            $giftList = $this->getGift($buyer, $giftList);
            foreach ($result as $i=>$activity){
                if($activity['gift_id']){
                    $gift = $giftList[$activity['gift_id']];
                    if(!empty($gift) && $gift['quantity'] > 0){
                        $gift['quantity'] = 1;
                        $activity['gift'] = $gift;
                        $giftList[$activity['gift_id']]['quantity']--;
                        $activity['description'] .= '、送赠品';
                    }
                }
                unset($activity['gift_id']);
                $result[$i] = $activity;
            }
        }
        return $result;
    }
    
    /**
     * 获取赠品
     */
    private function getGift($buyer, $giftList){
        $idstr = array_keys($giftList);
        $idstr = implode(',', $idstr);
        $sql = "SELECT gift.id AS gift_id, goods.id AS goods_id, goods.title,
                    goods.cost, goods.weight, goods.cat_id, gift.end_time, goods.stock,
                    goods.pic_url, gift.buy_quota, gift.free_postage
                FROM mall_gift AS gift
                INNER JOIN mall_goods AS goods ON goods.id=gift.goods_id
                WHERE gift.id IN ({$idstr})
                    AND ".NOW_TIME." BETWEEN gift.start_time AND gift.end_time
                    AND goods.is_del=0 AND goods.stock>0";
        $list = $this->query($sql);
        
        if(empty($list)){
            return;
        }
        
        $buyerId = current($buyer)['id'];
        $result = array();
        foreach ($list as $i=>$gift){
            if($gift['stock'] < 1){
                continue;
            }
            
            $gift['quantity'] = $giftList[$gift['gift_id']];
            if($gift['quantity'] > $gift['stock']){
                $gift['quantity'] = $gift['stock'];
            }
            
            // 如果赠品限购
            if($gift['buy_quota'] > 0){
                if($gift['quantity'] > $gift['buy_quota']){
                    $gift['quantity'] = $gift['buy_quota'];
                }
                
                $quota = $this->query("SELECT SUM(quantity) AS total FROM trade_gift WHERE gift_id='{$gift['gift_id']}' AND mid='{$buyerId}' AND `status`<2");
                $canLingQu = $gift['buy_quota'] - ($quota[0]['total'] ? $quota[0]['total'] : 0);
                if($canLingQu < 1){
                    continue;
                }else if($gift['quantity'] > $canLingQu){
                    $gift['quantity'] = $canLingQu;
                }
            }
            
            $gift['type'] = OrderType::GIFT;
            $gift['product_id'] = $gift['gift_id'];
            $gift['price'] = 0;
            $gift['score'] = 0;
            $gift['other_discount'] = 0;
            $gift['payment'] = 0;
            $gift['sub_stock'] = 1;
            $gift['view_price'] = array('title' => '', 'price' => '赠品', 'prefix' => '', 'suffix' => '');
            $gift['errmsg'] = '数量有限，以实际到货为准';
            $gift['main_tag'] = '';
            $gift['spec'] = '随机发货，备注无效';
            $result[$gift['gift_id']] = $gift;
        }
        
        return $result;
    }
    
    private function getCoupon($buyer, $params){
        $result = array();
        $shops = array_keys($params);
        $shops = implode(',', $shops);
    
        $sql = "SELECT id, activity_name, shop_id, meet, range_type, range_value, range_exclude, config, start_time, end_time
        FROM mall_manjian
        WHERE shop_id IN ({$shops}) AND ".NOW_TIME." BETWEEN start_time AND end_time";
        $list = $this->query($sql);
        $giftList = array();
        foreach ($list as $manjian){
            $configList = null;
            $rangeList = $manjian['range_type'] > 0 ? explode(',', $manjian['range_value']) : null;
            $rangeExclude = $manjian['range_type'] > 0 ? explode(',', $manjian['exclude']) : array();
    
            foreach($params[$manjian['shop_id']] as $freightId=>$trade){
                $payment = $trade['payment'];
                if($payment < $manjian['meet']){
                    continue;
                }
    
                $activityGoods = $trade['products'];
                if($manjian['range_type'] == 1){ // 指定商品满减
                    $activityGoods = array();
                    $payment = 0;
                    foreach ($trade['goods'] as $goodsId=>$data){
                        if(!in_array($goodsId, $rangeList)){
                            continue;
                        }
                        $payment = bcadd($payment, $data['payment'], 2);
                        $activityGoods += $data['products'];
                    }
    
                    if($payment < $manjian['meet']){
                        continue;
                    }
                }else if($manjian['range_type'] == 2){ // 指定分组满减
                    $activityGoods = array();
                    $payment = 0;
                    foreach ($trade['tag_ids'] as $tagId=>$goodsIds){
                        if(!in_array($tagId, $rangeList)){
                            continue;
                        }
    
                        foreach($goodsIds as $goodsId){
                            // 排除指定商品
                            if(in_array($goodsId, $rangeExclude)){
                                continue;
                            }
    
                            $data = $trade['goods'][$goodsId];
                            $payment = bcadd($payment, $data['payment'], 2);
                            $activityGoods += $data['products'];
                        }
                    }
    
                    if($payment < $manjian['meet']){
                        continue;
                    }
                }else if($manjian['range_type'] == 3){ // 指定类目满减
                    $activityGoods = array();
                    $payment = 0;
                    foreach ($trade['cat_ids'] as $catId=>$goodsIds){
                        if(!in_array($catId, $rangeList)){
                            continue;
                        }
    
                        foreach($goodsIds as $goodsId){
                            // 排除指定商品
                            if(in_array($goodsId, $rangeExclude)){
                                continue;
                            }
    
                            $data = $trade['goods'][$goodsId];
                            $payment = bcadd($payment, $data['payment'], 2);
                            $activityGoods += $data['products'];
                        }
                    }
    
                    if($payment < $manjian['meet']){
                        continue;
                    }
                }
    
                if(is_null($configList)){
                    $configList = json_decode($manjian['config'], true);
                }
    
                $config = null;
                foreach ($configList as $meet=>$_config){
                    if($payment >= $meet){
                        $config = $_config;
                    }else{
                        break;
                    }
                }
    
                $description = array();
                if($config['cash']){
                    $description[] = '减'.$config['cash'];
                }
                if($config['free_postage']){
                    $description[]= '包邮';
                }
                if($config['coupon']){
                    $description[]= '送优惠券';
                }
                if($config['score']){
                    $description[]= '送'.$config['score'].'积分';
                }
                if($config['gift']){
                    if(!isset($giftList[$config['gift']])){
                        $giftList[$config['gift']] = 1;
                    }else{
                        $giftList[$config['gift']]++;
                    }
                }
    
                $activity = array(
                    'id'           => $manjian['id'],
                    'name'         => $manjian['activity_name'],
                    'type'         => 'manjian',
                    'checked'      => 1,
                    'discount_fee' => sprintf('%.2f', $config['cash']),
                    'free_postage' => $config['free_postage'],
                    'coupon_id'    => $config['coupon_id'],
                    'score'        => $config['score'],
                    'start_time'   => $manjian['start_time'],
                    'end_time'     => $manjian['end_time'],
                    'description'  => '满'.$meet.implode('、', $description),
                    'orders'       => avg_dsicount($config['cash'], $activityGoods),
                    'gift_id'      => $config['gift'],
                    'shop_id'      => $manjian['shop_id'],
                    'freight_id'  => $freightId
                );
                $result[] = $activity;
    
                unset($params[$manjian['shop_id']][$freightId]);
            }
        }
    
        // 查找赠品
        if(count($giftList) == 0){
            return $result;
        }
    
        if(count($giftList) > 0){
            $giftList = $this->getGift($buyer, $giftList);
            foreach ($result as $i=>$activity){
                if($activity['gift_id']){
                    $gift = $giftList[$activity['gift_id']];
                    if(!empty($gift) && $gift['quantity'] > 0){
                        $gift['quantity'] = 1;
                        $activity['gift'] = $gift;
                        $giftList[$activity['gift_id']]['quantity']--;
                        $activity['description'] .= '、送赠品';
                    }
                }
                unset($activity['gift_id']);
                $result[$i] = $activity;
            }
        }
        return $result;
    }
    
    /**
     * 优惠券
     */
    private function tradeCoupon($groupList, &$buyer, $adjustParams = null){
        $params = $this->getDiscountOrders($groupList);
        if(count($params) == 0){
            return $groupList;
        }
        
        $buyerId = current($buyer)['id'];
        
        // 项目通用
        $projectIds = array();
        foreach($params as $shopId=>$item){
            $projectIds[] = IdWork::getProjectId($shopId);
        }
        $projectIds = array_unique($projectIds);
        $projectIds = implode(',', $projectIds);
        $sql = "SELECT member_coupon.id, mall_coupon.id AS coupon_id, mall_coupon.meet, member_coupon.`value`, mall_coupon.name,
                    mall_coupon.type, mall_coupon.range_type, mall_coupon.range_value, mall_coupon.range_exclude, mall_coupon.shop_ids
                FROM member_coupon
                INNER JOIN mall_coupon ON mall_coupon.id=member_coupon.coupon_id
                WHERE member_coupon.mid={$buyerId} AND ".NOW_TIME." < member_coupon.expire_time AND member_coupon.`status`=0
                    AND mall_coupon.project_id IN ({$projectIds}) AND ".NOW_TIME." > mall_coupon.start_time";
        $list = $this->query($sql);
        
        // 一个订单只能用一张优惠券
        $used = $resultList = array();
        foreach($params as $shopId=>$group){
            $projectId = IdWork::getProjectId($shopId);
            foreach ($group as $freightId=>$trade){
                $couponList = array();
                $maxIndex = 0;
                $maxValue = 0;
                $checkedIndex = -1;
                $checkedKey = $shopId.'_'.$freightId.'_coupon';
                
                foreach ($list as $i=>$coupon){
                    // 不满条件最低条件
                    if($coupon['meet'] > 0 && floatval($trade['payment']) < floatval($coupon['meet'])){
                        continue;
                    }
                    
                    if(!isset($coupon['inited'])){
                        $coupon['range_value'] = $coupon['range_value'] ? explode(',', $coupon['range_value']) : array();
                        $coupon['range_exclude'] = $coupon['range_exclude'] ? explode(',', $coupon['range_exclude']) : array();
                        $coupon['shop_ids'] = $coupon['shop_ids'] ? explode(',', $coupon['shop_ids']) : array();
                        $coupon['inited'] = 1;
                        $list[$i] = $coupon;
                    }
                    
                    // 不在指定的店铺内
                    if($coupon['shop_ids'] && !in_array($shopId, $coupon['shop_ids'])){
                        continue;
                    }
                    
                    $temp = $this->getDiscountProduct($coupon, $trade);
                    // 不满条件最低条件
                    if($coupon['meet'] > 0 && floatval($temp['payment']) < floatval($coupon['meet'])){
                        continue;
                    }
                    $orders = $temp['products'];
                    $discountFee = floatval($coupon['value']);

                    // 是否选中
                    $checked = 0;
                    if($adjustParams[$checkedKey] == $coupon['id']){
                        $checked = 1;
                        $checkedIndex = $i;
                    }
                    
                    $couponList[$i] = array(
                        'id'           => $coupon['id'],
                        'name'         => $coupon['name'],
                        'type'         => 'coupon',
                        'checked'      => $checked,
                        'meet'         => $coupon['meet'],
                        'discount_fee' => sprintf('%.2f', $discountFee),
                        'description'  => ($coupon['meet'] > 0 ? '满'.$coupon['meet'] : '').'优惠'.$discountFee,
                        'coupon_id'    => $coupon['coupon_id'],
                        'orders'       => $orders
                    );
                    
                    if($discountFee > $maxValue){
                        $maxIndex = $i;
                        $maxValue = $discountFee;
                    }
                }
                
                if(count($couponList) == 0){
                    continue;
                }
                
                if($adjustParams['allow_discount_fee'] && $checkedIndex == -1 && !isset($adjustParams[$checkedKey])){
                    $couponList[$maxIndex]['checked'] = 1;
                    $checkedIndex = $maxIndex;
                }
                $couponList = array_values($couponList);
                
                $resultList[] = array('shop_id' => $shopId, 'freight_id' => $freightId, 'coupons' => $couponList);

                if($checkedIndex > -1){
                    $used[] = $list[$checkedIndex]['id'];
                    unset($list[$checkedIndex]);
                }
            }
        }
        
        foreach($resultList as $i=>$result){
            $trade = $groupList[$result['shop_id']]['trades'][$result['freight_id']];
            
            $checked = null;
            foreach ($result['coupons'] as $ci=>$coupon){
                if($coupon['checked']){
                    $checked = $coupon;
                }else if(in_array($coupon['id'], $used)){
                    array_splice($result['coupons'], $ci, 1);
                }
                
                unset($coupon['orders']);
                $trade['discount_details'][] = $coupon;
            }
            
            if(!is_null($checked)){
                $coupon = $checked;
                $avg = avg_dsicount($coupon['discount_fee'], $coupon['orders']);
                
                foreach ($avg as $ai=>$discountFee){
                    $order = $trade['orders'][$ai];
                    $order['discount_fee'] = bcadd($order['discount_fee'], $discountFee, 2);
                    $order['payment'] = bcsub($order['total_fee'], $order['discount_fee'], 2);
                
                    $coupon['discount_fee'] = $discountFee;
                    unset($coupon['orders']);
                    $order['discount_details'][] = $coupon;
                    $trade['orders'][$ai] = $order;
                }
            }
            
            $groupList[$result['shop_id']]['trades'][$result['freight_id']] = $trade;
        }
        
        return $groupList;
    }
    
    private function tradeSum($groupList, &$buyer, $adjustParams){
        $error = '';
        $totalNum = 0;
        $hasError = 0;
        $tipUseDiscount = false;
        $tipUseBalance = false;
        $tipUseWallet = false;
        $tipUseScore = false;
        
        $minTimeout = 0;
        $list = array();
        $sumInfo = array('total_fee' => 0, 'total_score' => 0, 'total_postage' => 0, 'total_discount' => 0, 'paid_balance' => 0, 'paid_wallet' => 0, 'paid_score' => 0);
        foreach($groupList as $shopId=>$group){
            // 抵用
            $member = $buyer[$group['project_id']];
            $project = $member['project'];
            // 如果用户的可提现余额变成负数，则限制钱包货款，保护商家利益
            if($member['balance'] < 0 && $member['wallet'] > 0){
                $member['wallet'] = bcadd($member['wallet'], $member['balance'], 2);
            } 
            foreach ($group['trades'] as $freightId=>$trade){
                // 货到付款
                if($trade['express_id'] == 1){
                    $trade['type'] = 'cod';
                }
                
                $scoreDiscount = array(
                    'id'           => 1,
                    'name'         => $project['score_alias'].'抵用',
                    'type'         => 'score',
                    'score'        => 0,
                    'checked'      => $adjustParams['allow_discount_fee'] || isset($adjustParams[$shopId.'_'.$freightId.'_score']),
                    'discount_fee' => 0,
                    'description'  => ''
                );
                
                foreach ($trade['orders'] as $i=>$order){
                    if($order['price_type'] == 3){ // 积分商品
                        $trade['total_score'] = bcadd($trade['total_score'], $order['total_score'], 2);
                        $trade['discount_score'] = bcadd($trade['discount_score'], $order['discount_score'], 2);
                    }else if($order['score'] > 0){ // 积分抵用
                        if($member['score'] > 0){
                            $rate = bcmul($order['score'], 0.01, 2);
                            $maxMoney = bcmul($order['payment'], $rate, 2);
                            $result = money_to_score($maxMoney, $member['score'], $project['min_score'], $project['min_money']);
                            if($result['score'] > 0){
                                if($scoreDiscount['checked']){
                                    $member['score'] = bcsub($member['score'], $result['score'], 2);
                                    $order['discount_details'][] = array(
                                        'id'    => '',
                                        'type'  => 'score',
                                        'title' => $project['score_alias'].'抵用',
                                        'discount_fee' => $result['money'],
                                        'score' => $result['score'],
                                        'description' => $scoreDiscount['description']
                                    );
                                    $order['discount_fee'] = bcadd($order['discount_fee'], $result['money'], 2);
                                    $order['payment'] = bcadd($order['total_fee'], $order['discount_fee'], 2);
                                }
                                
                                $scoreDiscount['description'] = $result['min_score'].$project['score_alias'].'抵'.$result['min_money'].'元；'.$result['score'].$project['score_alias'].'抵'.$result['money'].'元';
                                $scoreDiscount['discount_fee'] = bcadd($scoreDiscount['discount_fee'], $result['money'], 2);
                                $scoreDiscount['score'] = bcadd($scoreDiscount['score'], $result['score'], 2) * 1;
                            }
                        }

                        $order['score'] = 0;
                    }
                    
                    if($order['free_postage']){
                        $order['main_tag'] = '包邮';
                        $order['ext_params']['free_postage'] = 1;
                    }
                    
                    $trade['total_fee'] = bcadd($trade['total_fee'], $order['total_fee'], 2);
                    $trade['discount_fee'] = bcadd($trade['discount_fee'], $order['discount_fee'], 2);
                    $trade['orders'][$i] = $order;
                    foreach ($order['sku_json'] as $sk => $sv) {
                        $sku_str .= $sv['k'].":".$sv['v']." ";
                    }
                    $trade['orders'][$i]['sku_str'] = $sku_str;
                    $totalNum += $order['quantity'];
                }
                
                $trade['payment'] = bcadd($trade['total_fee'], $trade['total_postage'], 2);
                $trade['payment'] = bcsub($trade['payment'], $trade['discount_fee'], 2);
                $trade['payscore'] = bcsub($trade['total_score'], $trade['discount_score'], 2);
                if($scoreDiscount['discount_fee'] > 0){
                    $scoreDiscount['discount_fee'] = sprintf('%.2f', $scoreDiscount['discount_fee']);
                    $trade['discount_details'][] = $scoreDiscount;
                }
                $trade['kind'] = count($trade['orders']);
                
                // 店铺汇总
                $group['total_fee'] = bcadd($group['total_fee'], $trade['total_fee'], 2);
                $group['total_postage'] = bcadd($group['total_postage'], $trade['total_postage'], 2);
                $group['discount_fee'] = bcadd($group['discount_fee'], $trade['discount_fee'], 2);
                $group['payment'] = bcadd($group['payment'], $trade['payment'], 2);
                
                $group['total_score'] = bcadd($group['total_score'], $trade['total_score'], 2);
                $group['discount_score'] = bcadd($group['discount_score'], $trade['discount_score'], 2);
                $group['payscore'] = bcadd($group['payscore'], $trade['payscore'], 2);
                
                // wallet
                if($trade['payment'] > 0 && $member['wallet'] > 0){
                    if($adjustParams['allow_paid_wallet']){
                        $trade['paid_wallet'] = $member['wallet'] > $trade['payment'] ? $trade['payment'] : $member['wallet'];
                        $trade['payment'] = bcsub($trade['payment'], $trade['paid_wallet'], 2);
                        $member['wallet'] = bcsub($member['wallet'], $trade['paid_wallet'], 2);
                    }else{
                        $tipUseWallet = true;
                    }
                }
                // balance
                if($trade['payment'] > 0 && $member['balance'] > 0){
                    if($adjustParams['allow_paid_balance']){
                        $trade['paid_balance'] = $member['balance'] > $trade['payment'] ? $trade['payment'] : $member['balance'];
                        $trade['payment'] = bcsub($trade['payment'], $trade['paid_balance'], 2);
                        $member['balance'] = bcsub($member['balance'], $trade['paid_balance'], 2);
                    }else{
                        $tipUseBalance = true;
                    }
                }
                // score
                if($trade['payscore'] > 0 && $member['score'] > 0){
                    if($adjustParams['allow_paid_score']){
                        $trade['paid_score'] = $member['score'] > $trade['payscore'] ? $trade['payscore'] : $member['score'];
                        $trade['payscore'] = bcsub($trade['payscore'], $trade['paid_score'], 2);
                        $member['score'] = bcsub($member['score'], $trade['paid_score'], 2);
                    }else{
                        $tipUseScore = true;
                    }
                }
                
                // 判断积分不足是否允许下单
                if($trade['payscore'] > 0 ){
                    $allow = project_config($group['project_id'], ProjectConfig::ALLOW_FORCE_ORDER);
                    if(!$allow){
                        $hasError = 1;
                    }
                }

                $sumInfo['paid_balance'] = bcadd($sumInfo['paid_balance'], $trade['paid_balance'], 2);
                $sumInfo['paid_wallet'] = bcadd($sumInfo['paid_wallet'], $trade['paid_wallet'], 2);
                $sumInfo['paid_score'] = bcadd($sumInfo['paid_score'], $trade['paid_score'], 2);
                
                $group['trades'][$freightId] = $trade;
                if($trade['discount_details']){
                    $tipUseDiscount = true;
                }
                
                if($minTimeout == 0 || $trade['pay_timeout'] < $minTimeout){
                    $minTimeout = $trade['pay_timeout'];
                }
            }
            $buyer[$group['project_id']] = $member;

            $sumInfo['total_fee'] = bcadd($sumInfo['total_fee'], $group['total_fee'], 2);
            $sumInfo['total_score'] = bcadd($sumInfo['total_score'], $group['total_score'], 2);
            $sumInfo['total_postage'] = bcadd($sumInfo['total_postage'], $group['total_postage'], 2);
            $sumInfo['discount_fee'] = bcadd($sumInfo['discount_fee'], $group['discount_fee'], 2);
            $sumInfo['discount_score'] = bcadd($sumInfo['discount_score'], $group['discount_score'], 2);
            
            $group['trades'] = array_values($group['trades']);
            $list[] = $group;
        }

        // 最终需要支付的总额
        $needPay = bcadd($sumInfo['total_fee'], $sumInfo['total_postage'], 2);
        $needPay = bcsub($needPay, $sumInfo['discount_fee'], 2);
        $needPay = bcsub($needPay, $sumInfo['paid_balance'], 2);
        $needPay = bcsub($needPay, $sumInfo['paid_wallet'], 2);
        // 最终需要支付的积分
        $needScore = bcsub($sumInfo['total_score'], $sumInfo['discount_score'], 2);
        $needScore = bcsub($needScore, $sumInfo['paid_score'], 2);
        $needScore = floatval($needScore);
        
        // 汇总描述
        $describe = '';
        if($sumInfo['discount_fee'] > 0){
            $describe .= '<div class="block-item"><span class="title-info">可用优惠抵用'.$sumInfo['discount_fee'].'元</span><div class="switch mini switch-on" data-type="allow_discount_fee"></div></div>';
        }else if($tipUseDiscount){
            $describe .= '<div class="block-item"><span class="title-info">建议您使用优惠</span><div class="switch mini" id="allow_discount_fee" data-type="allow_discount_fee"></div></div>';
        }
        
        if($sumInfo['paid_wallet'] > 0){
            $describe .= '<div class="block-item"><span class="title-info">'.$project['wallet_alias'].'抵'.$sumInfo['paid_wallet'].'元</span><div class="switch mini switch-on" data-type="allow_paid_wallet"></div></div>';
        }else if($tipUseWallet){
            $describe .= '<div class="block-item"><span class="title-info">建议您用'.$project['wallet_alias'].'抵用</span><div class="switch mini" data-type="allow_paid_wallet"></div></div>';
        }
        if($sumInfo['paid_balance'] > 0){
            $describe .= '<div class="block-item"><span class="title-info">'.$project['balance_alias'].'抵'.$sumInfo['paid_balance'].'元</span><div class="switch mini switch-on" data-type="allow_paid_balance"></div></div>';
        }else if($tipUseBalance){
            $describe .= '<div class="block-item"><span class="title-info">建议您用'.$project['balance_alias'].'抵用</span><div class="switch mini" data-type="allow_paid_balance"></div></div>';
        }
        if($sumInfo['paid_score'] > 0){
            $describe .= '<div class="block-item"><span class="title-info">支付'.floatval($sumInfo['paid_score']).$project['score_alias'].'</span><div class="switch mini switch-on" data-type="allow_paid_score"></div></div>';
        }else if($tipUseScore){
            $describe .= '<div class="block-item"><span class="title-info">建议您用'.$project['score_alias'].'支付</span><div class="switch mini" data-type="allow_paid_score"></div></div>';
        }
        
        if($needScore > 0){
            if($hasError){
                $error = $project['score_alias'].'不足，还需'.$needScore;
            }else{
                $error = $project['score_alias'].'不足，但仍可先下单';
            }
        }
        
        $message = '下单后请于';
        $timeoutDate = second_to_time($minTimeout - NOW_TIME);
        $hours = $timeoutDate['hours'] + $timeoutDate['days'] * 24;
        if($hours > 0){
            $message .= $hours.'小时';
        }
        if($timeoutDate['minutes'] > 0){
            $message .= $timeoutDate['minutes'].'分钟';
        }
        $message .= '内付款，逾期订单将自动关闭';
        
        return array(
            'describe'   => $describe,
            'groups'     => $list,
            'message'    => $message,
            'error'      => $error,
            'has_error'  => $hasError,
            'total_quantity'  => $totalNum,
            'need_pay'   => $needPay,
            'need_score' => $needScore,
            'hide_sum_need_pay' => 1
        );
    }
}
?>