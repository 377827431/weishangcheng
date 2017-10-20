<?php 
namespace Common\Model;

class PayRedPackModel extends OrderModel{
    
    public function exchange($buyer, $seller, $productId){
        $buyNum = 1;
         
        // 数据校验
        $sql = "SELECT
                	product.id, product.goods_id,
                    goods.title, goods.pic_url AS goods_pic_url, product.pic_url, product.price, product.score, goods.points, goods.original_price,
                    goods.pay_type, goods.is_virtual, goods.cat_id, goods.buy_quota, goods.day_quota, goods.every_quota,
                	product.stock, product.outer_id, product.sku_json, product.total_num,
                	goods.post_fee, goods.sold_time, goods.is_del, goods.is_display
                FROM
                	mall_product AS product
                INNER JOIN mall_goods AS goods ON goods.id = product.goods_id
	            WHERE product.id=".intval($productId);
         
        // 提交的产品信息列表
        $products = $this->query($sql);
        
        if(empty($products)){
            $this->error = '红包不存在，请刷新页面后重试';
            return null;
        }
        $product = $products[0];
        
        // 商品信息校验
        if($product['is_del']){
            $this->error = '产品已被删除';
        }else if($product['is_display'] == 0){
            $this->error = '产品已下架';
        }else if($product['sold_time'] > 0 && $product['sold_time'] > time()){
            $this->error = date('Y-m-d H:i:s', $product['sold_time']).'开售';
        }else if($product['stock'] == 0){
            $this->error = '库存不足';
        }else if($product['pay_type'] != 4 && $product['pay_type'] != 5){
            $this->error = '商品信息已变更，请刷新页面';
        }else if($buyer['score'] < $product['score']){
            $this->error = '积分不足，剩余'.$buyer['score'].'积分';
        }
        
        if($this->error != ''){
            return null;
        }
        
        $startTime = date('Y-m-d').' 00:00:00';
        $endTime = date('Y-m-d').' 23:59:59';
        $goodsId = $product['goods_id'];
        
        if(!isset($quotaList[$goodsId])){
            //$quotaList['商品id'] = array('今日卖出数量' => 0, '今日购买数量' => 0, '累计购买数量' => 0 );
            $quotaList[$goodsId] = array('today_sold' => -1, 'today_buy' => -1, 'buy_num' => -1);
        }
        
        // 每日最多可售卖数量限制
        if($product['day_quota'] > 0){
            // 获取今日此商品卖出数量
            if($quotaList[$goodsId]['today_sold'] == -1){
                $soldNum = $this->getSoldNumByTime($goodsId, $startTime, $endTime);
                $quotaList[$goodsId]['today_sold'] = $soldNum;
            }
        
            // 今日已售罄
            $num = $product['day_quota'] - $quotaList[$goodsId]['today_sold'];
            if($num > 0){
                $product['stock'] = $num;
            }else{
                $product['stock'] = 0;
                $this->error = '今日已售罄';
                return;
            }
        
            if($buyNum + $quotaList[$goodsId]['today_sold'] > $product['stock']){
                $this->error = '今日已售罄';
                return;
            }
        }
        
        // 每人最多可购买数量
        if($product['buy_quota'] > 0){
            if($quotaList[$goodsId]['buy_num'] == -1){
                $quotaList[$goodsId]['buy_num'] = $this->getSoldNumByBuyer($product['goods_id'], $buyer['id']);
            }
        
            // 要买的数量 + 已买的数量 > 每人限购数量
            if($buyNum + $quotaList[$goodsId]['buy_num'] > $product['buy_quota']){
                $this->error = '每人限购'.$product['buy_quota'].'件';
                return;
            }
        }
        
        // 每人每日最多可购买
        if($product['every_quota'] > 0){
            if($quotaList[$goodsId]['today_buy'] == -1){
                $quotaList[$goodsId]['today_buy'] = $this->getSoldNumByBuyerTime($goodsId, $buyer['id'], $startTime, $endTime);
            }
        
            // 要买的数量 + 已买的数量 > 每人限购数量
            if($buyNum + $quotaList[$goodsId]['today_buy'] > $product['every_quota']){
                $this->error = '每人每日限购'.$product['every_quota'].'件';
                return;
            }
        }
        
        $today = date('Y-m-d H:i:s');
        // 买家ip定位
        $ipLocation = new \Org\Net\IpLocation();
        $location = $ipLocation->getlocation();
        // 生成订单号
        $idwork = new \Org\IdWork();

        // 红包金额
        $amount = $product['pay_type'] == 4 ? rand(100, $product['price'] * 100) : $product['price'] * 100;
        
        // 交易
        $trade = array(
            'tid'              => $idwork->nextId(),
            'type'             => 'wxredpack',
            'created'          => $today,
            'status'           => 'send',
            'seller_id'        => $seller['id'],
            'seller_nick'      => $seller['name'],
            'receiver_name'    => $buyer['nickname'],
            'receiver_mobile'  => $buyer['mobile'],
            'buyer_id'         => $buyer['id'],
            'buyer_nick'       => $buyer['nickname'],
            'buyer_openid'     => $buyer['openid'],
            'buyer_subscribe'  => $buyer['subscribe'],
            'buyer_location'   => $location['country'],
            'buyer_ip'         => $location['ip'],
            'kind'             => 1,   // 购买了几种商品
            'total_num'        => 1,   // 商品数量的和
            'total_fee'        => $amount / 100,   // 商品数量*商品售价的和
            'total_score'      => $product['score'],   // 商品数量*商品积分价的和
            'total_points'     => 0,   // 交易成功赠送积分
            'post_fee'         => 0,   // 商品数量*商品邮费的和
            'discount_fee'     => 0,   // 优惠总额
            'payment'          => 0,   // 需付金额
            'pay_type'         => 'score',
            'trade_no'         => '',
            'pay_time'         => $today,
            'paid_fee'         => 0,   // 已付金额
            'paid_score'       => $product['score'],   // 已付积分
            'shipping_type'    => 'virtual', // 物流方式
            'consign_time'     => $today
        );
        
        // 订单
        $order = array(
            'oid'          => $trade['tid'],
            'tid'          => $trade['tid'],
            'buyer_id'     => $buyer['id'],
            'goods_id'     => $product['goods_id'],
            'product_id'   => $product['id'],
            'outer_id'     => $product['outer_id'],
            'cat_id'       => $product['cat_id'],
            'title'        => $product['title'],
            'num'          => $buyNum,
            'price'        => $product['price'],
            'score'        => $product['score'],
            'original_price'=>$product['original_price'],
            'pic_url'      => $product['pic_url'] ? $product['pic_url'] : $product['goods_pic_url'],
            'pay_type'     => $product['pay_type'],
            'points'       => $product['points'],
            'total_fee'    => $trade['total_fee'],
            'total_score'  => $product['score'],
            'payment'      => 0,
            'sku_json'     => $product['sku_json'],
            'post_fee'     => 0,
            'is_virtual'   => 1,
            'shipping_type'=> 'virtual',
            'send_time'    => $today
        );
        
        // 执行发红包
        $data = array(
            'tid' => $trade['tid'], 
            'openid' => $trade['buyer_openid'],
            'total_amount' => $amount, 
            'total_num' => $product['pay_type'] == 4 ? 1 : $product['total_num'], 
            'client_ip' => $trade['buyer_ip'], 
            'wishing' => '恭喜发财', 
            'act_name' => '积分兑换微信红包', 
            'remark' => $product['title'].get_sku_str($product['sku_json']));
        $result = $this->send($data);
        if(empty($result)){
            return;
        }
        $trade['trade_no'] = $result['send_listid'];
        
        $this->add($trade);
        D('mall_order')->add($order);
         
        //减少库存、增加销售量
        $this->execute("UPDATE mall_goods SET stock=stock-{$order['num']}, sold_num=sold_num+{$order['num']} WHERE id={$order['goods_id']}");
        $this->execute("UPDATE mall_product SET stock=stock-{$order['num']}, sold_num=sold_num+{$order['num']} WHERE id={$order['product_id']}");
        
        $score = array(
            'mid'       => $trade['buyer_id'], 
            'reason'    => $product['title'], 
            'score'     => -$trade['total_score'], 
            'link'      => '/h5/order/detail?tid='.$trade['tid'], 
            'img'       => $order['pic_url'], 
            'type'      => 'order'
        );
        D('Score')->add($score);
        
        $trade['orders'] = $order;
        return $trade;
    }
    
    private function send($data){
        $config = C('WEIXIN');
        $mchBillno = $config['mch_id'].date('Ymd').$data['tid'];
         
        // 发红包
        $redpack = new \Org\WxPay\WxRedPack($config);
        $redpack->setMchBillno($mchBillno);
        $redpack->setTotalAmount($data['total_amount']);
        $redpack->setClientIp($data['client_ip']);
        $redpack->setWishing($data['wishing']);
        $redpack->setActName($data['act_name']);
        $redpack->setRemark($data['remark']);
         
        $result = $redpack->send($data['openid'], $data['total_num']);
        
        if($result['return_code'] == "SUCCESS" && $result['result_code'] == "SUCCESS"){
            return $result;
        }else if($result['err_code'] == 'FREQ_LIMIT'){
            $this->error = '您接收红包过于频繁,请稍候重试.';
        }else if($result['err_code'] == 'NOTENOUGH'){
            $this->error ='商家账户余额不足,请稍候重试.';
        }else if($result['err_code'] == 'NO_AUTH'){
            $this->error = '您的账号存在异常,已被微信拦截.请确认您的微信已绑定银行卡.';
        }else if($result['err_code'] == 'SENDNUM_LIMIT'){
            $this->error = '您今日领取微信红包次数已达上线,请明日再试.';
        }else{
            $this->error = '下单失败：'.$result['err_code_des'];
        }
    }
}
?>