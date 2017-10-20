<?php
namespace Admin\Model;

use Think\Model;
use Common\Model\OrderStatus;
use Common\Model\OrderType;
use Common\Model\BalanceType;
use Common\Model\PayType;
use Common\Model\BaseModel;
use Common\Model\BalanceModel;
use Org\IdWork;
use Common\Model\ActivityType;
use Common\Model\ShopBalanceModel;

class TradeSubscribe extends BaseModel{
    protected $tableName = 'trade';
    protected $pk = 'tid';
    
    /**
     * 订单创建
     */
    public function created($tid){
        if(!is_numeric($tid)){
            E('订单号格式错误');
        }
        
        $trade = $this->field("tid, buyer_id, seller_id, buyer_nick, receiver_name, receiver_mobile")->find($tid);
        if(!$trade){
           E('订单号不存在');
        }
        
        // 通知复制订单
        $this->lPublish('TradeCopy', $tid);
        
        // 搜索关键字
        $search = array('kw' => '', 'other' => array($trade['receiver_name'], $trade['receiver_mobile']));
        
        // 子订单
        $goodsIds = '';
        $orders = $this->query("SELECT oid, goods_id, quantity, ext_params, sku_json, outer_id, main_tag, type, kw
								FROM trade_order
								LEFT JOIN mall_key_word ON mall_key_word.id=trade_order.goods_id
								WHERE tid='{$tid}'");
        $today = date('Ymd');
        foreach ($orders as $order){
            if($orders['ext_params']){
                $params = json_decode($orders['ext_params'], true);
                foreach ($params as $key=>$val){
                    switch ($key){
                        case 'day_quota':   // 商品 - 日限售
                            $sql = "INSERT INTO trade_day_quota
                                    SET goods_id={$order['goods_id']}, today='{$today}', quantity={$order['quantity']}
                                    ON DUPLICATE KEY UPDATE
                                    today=VALUES(today), quantity=IF(today={$today}, quantity+VALUES(quantity), VALUES(quantity))";
                            $this->execute($sql);
                            break;
                        case 'every_quota': // 商品 - 每人每日限购
                            $sql = "INSERT INTO trade_every_quota
                                    SET buyer_id={$trade['buyer_id']} goods_id={$order['goods_id']}, today='{$today}', quantity={$order['quantity']}
                                    ON DUPLICATE KEY UPDATE
                                    today=VALUES(today), quantity=IF(today={$today}, quantity+VALUES(quantity), VALUES(quantity))";
                            $this->execute($sql);
                            break;
                    }
                }
            }
            
            $goodsIds .= $order['goods_id'].',';
            
            // 搜索
            $search['kw']     .= $order['kw'].' ';
            //$search['other'][] = 'ot'.$order['type'];
            $search['other'][] = $order['outer_id'];
            $search['other'][] = $order['main_tag'];
            $specName = get_spec_name($order['sku_json']);
            if($specName !== ''){
            	$search['other'][] = $specName;
            }
        }
        
        // 累加下单次数
        $goodsIds = rtrim($goodsIds, ',');
        $this->execute("UPDATE mall_goods_sort SET order_times=order_times+1 WHERE id IN ({$goodsIds})");
        
        // 处理trade_search
        $search['kw']    = addslashes(rtrim($search['kw']));
        $search['other'] = array_unique($search['other']);
        $search['other'] = addslashes(implode(' ', $search['other']));
        $this->execute("INSERT INTO trade_search SET tid='{$tid}', kw='{$search['kw']}', other='{$search['other']}'");

        // 后台订单消息提醒
        $add = array(
            'shop_id' => $trade['seller_id'],
            'name' => $trade['buyer_nick'],
            'tid' => $tid,
            'type' => \Common\Model\OrderReminderType::NEW_ORDER,
            'created' => time(),
            'status' => 2,
        );
        M('trade_reminder')->add($add);

        return true;
    }
    
    /**
     * 订单已付款
     */
    public function paid($data,$params=''){
        if(!is_numeric($data['tid']) ||
            !is_numeric($data['paid_fee']) || $data['paid_fee'] < 0 ||
            !is_numeric($data['paid_score']) || $data['paid_score'] < 0 ||
            !is_numeric($data['pay_time']) || !is_numeric($data['pay_type'])){
            if(empty($params)){
                E('参数异常');
            }else{
                return '参数异常';
            }
        }
        
        $timespan = time();
        $toPay = OrderStatus::WAIT_PAY;
        $toSend = OrderStatus::WAIT_SEND_GOODS;
        $isCodPay = PayType::CODPAY == $data['pay_type'];
        $statusSql = $isCodPay ? $toSend : "IF(payment<0.01 AND payscore<1, {$toSend}, `status`)";
        
        // 先更改数据
        $sql = "UPDATE trade SET
                    paid_fee=paid_fee+IF({$data['paid_fee']}>payment, payment, {$data['paid_fee']}),
                    payment=payment-IF({$data['paid_fee']}>payment, payment, {$data['paid_fee']}),
                    paid_score=paid_score+{$data['paid_score']},
                    payscore=payscore-{$data['paid_score']},
                    pay_time={$data['pay_time']},
                    pay_type={$data['pay_type']},
                    transaction_id=IF(transaction_id='', '{$data['transaction_id']}', transaction_id),
                    modified={$timespan},
                    `status`={$statusSql}
                WHERE tid='{$data['tid']}' AND `status`=".$toPay;
        $result = $this->execute($sql);
        if(!$result){
            if(empty($params)){
                E('支付状态变更，不二次处理');
            }else{
                return '支付状态变更，不二次处理';
            }
        }
        
        // 再把数据查出来
        $sql = "SELECT tid, `status`, buyer_id, buyer_card_id, seller_id,
                    paid_balance, paid_wallet, paid_fee, payment, payscore, paid_score, buyer_remark,
                    receiver_name, receiver_mobile, receiver_province, receiver_city, receiver_county, receiver_detail, receiver_zip,
                    (SELECT discount_details FROM trade_discount WHERE trade_discount.tid=trade.tid) AS discount_details
                FROM trade
                WHERE tid='{$data['tid']}' AND `status`={$toSend}";
        $trade = $this->query($sql);
        if(empty($trade)){
            return;
        }
        $trade = $trade[0];
        if(empty($params)){
            // 计算销售员推广佣金
            $this->lPublish('CommisionAdd', $trade['tid']);
            // 通知同步数据
            $this->lPublish('TradeCopy', $trade['tid']);
        }else{
            $CA = new \Admin\Model\CommisionSubscribe();
            $CA->add($trade['tid']);
            $TC = new \Admin\Model\TradeSubscribe();
            $TC->copy($trade['tid']);
        }
        // 待结算总额(可提现+微信支付)
        $settlement = bcadd($trade['paid_balance'], $trade['paid_fee'], 2);
        $ShopBalance = new ShopBalanceModel();
        $ShopBalance->addWaitSettlement($trade['tid'], $trade['seller_id'], $settlement);
        
        /*******************子订单处理*******************/
        // 查找子订单
        $this->execute("UPDATE trade_order SET `status`={$toSend} WHERE tid='{$trade['tid']}' AND `status`={$toPay}");
        $orders = $this->query("SELECT oid, type, `status`, goods_id, product_id, quantity, payment, payscore, shipping_type, sub_stock, tao_id, sku_json, promotion_details, goods_type FROM trade_order WHERE tid='{$data['tid']}'");
        $project = get_project($trade['seller_id'], true);
        
        $substock = null;
        $taoList = array();
        foreach ($orders as $i=>$order){
            // 减库存
            if(!$order['sub_stock'] || $order['type'] == OrderType::GIFT){
                if($order['goods_type'] != 1){
                    $substock = array('tid' => $trade['tid'], 'oid' => $order['oid'], 'goods_id' => $order['goods_id'], 'product_id' => $order['product_id'], 'type' => $order['type'], 'quantity' => -$order['quantity'], 'mid' => $trade['buyer_id'], 'created' => $timespan);
                    if($order['type'] == OrderType::GIFT){
                        $substock['status'] = 1;
                    }
                    if(empty($params)){
                        $this->lPush('TradeMinusStock', $substock);
                    }else{
                        $TM = new \Admin\Model\TradeSubscribe();
                        $TM->minusStock($substock);
                    }
                }
            }

            $sku_json = $orders[$i]['sku_json'] = decode_json($order['sku_json'], true);
        
            // 1688订单
            if($order['tao_id'] > 0){
                $taoList[$order['oid']] = array('tao_id' => $order['tao_id'], 'quantity' => $order['quantity'], 'sku_json' => $sku_json);
            }
            
            // 购买会员卡
            if($order['goods_type'] == 1){
                $this->lPublish('MemberCardBuy', $trade['tid'], $trade['buyer_id'], $sku_json[0]['vid']);
            }
        }
        
        // 减库存
        if(!is_null($substock)){
            $this->publish('TradeMinusStock'); 
        }

        // 1688下单
        if(count($taoList) > 0){
            if(empty($params)){
                $this->lPublish('AlibabaTradeAdd', array(
                    'tid'               => $trade['tid'],
                    'receiver_name'     => $trade['receiver_name'],
                    'receiver_mobile'   => $trade['receiver_mobile'],
                    'receiver_province' => $trade['receiver_province'],
                    'receiver_city'     => $trade['receiver_city'],
                    'receiver_county'   => $trade['receiver_county'],
                    'receiver_detail'   => $trade['receiver_detail'],
                    'receiver_zip'      => $trade['receiver_zip'],
                	'seller_id'         => $trade['seller_id'],
                    'buyer_remark'      => $trade['buyer_remark']
                ),$taoList);
            }else{
                $ATS = new \Admin\Model\AlibabaTradeSubscribe();
                $result = $ATS->add1688(array(
                    'tid'               => $trade['tid'],
                    'receiver_name'     => $trade['receiver_name'],
                    'receiver_mobile'   => $trade['receiver_mobile'],
                    'receiver_province' => $trade['receiver_province'],
                    'receiver_city'     => $trade['receiver_city'],
                    'receiver_county'   => $trade['receiver_county'],
                    'receiver_detail'   => $trade['receiver_detail'],
                    'receiver_zip'      => $trade['receiver_zip'],
                    'seller_id'         => $trade['seller_id'],
                    'buyer_remark'      => $trade['buyer_remark']
                ),$taoList,$params);
                if($result == 'success'){
                    foreach ($orders as $order){
                        // 累加产品销售
                        $this->execute("UPDATE mall_goods_sort SET sold=sold+{$order['quantity']}, pay_times=pay_times+1 WHERE id='{$order['goods_id']}'");
                    }
                    // 累加会员支付总额和消费次数
                    $totalPaidFee = bcadd($trade['paid_balance'], $trade['paid_wallet'], 2);
                    $totalPaidFee = bcadd($totalPaidFee, $trade['paid_fee'], 2);
                    $this->execute("UPDATE project_member SET sum_paid=sum_paid+{$totalPaidFee}, sum_trade=sum_trade+1 WHERE project_id='{$project['id']}' AND mid='{$trade['buyer_id']}'");
                }
                return $result;
            }
        }
        /*******************子订单处理(结束)*******************/
        
        
        // 同步活动库存和销量
        $allowActivity = ActivityType::getAll();
        foreach ($orders as $order){
            // 增加促销活动销量
            if($order['promotion_details']){
                $promotions = json_decode($order['promotion_details'], true);
                foreach ($promotions as $activity){
                    if(!array_key_exists($activity['type'], $allowActivity)){
                        continue;
                    }
        
                    $class = $allowActivity[$activity['type']]['model'];
                    $class = is_string($class) ? new $class() : $class;
                    $class->syncStock(array('id' => $activity['id'], 'product_id' => $order['product_id'], 'quantity' => -$order['quantity']));
                }
            }
        
            // 累加产品销售
            $this->execute("UPDATE mall_goods_sort SET sold=sold+{$order['quantity']}, pay_times=pay_times+1 WHERE id='{$order['goods_id']}'");
        }
        
        // 累加会员支付总额和消费次数
        $totalPaidFee = bcadd($trade['paid_balance'], $trade['paid_wallet'], 2);
        $totalPaidFee = bcadd($totalPaidFee, $trade['paid_fee'], 2);
        $this->execute("UPDATE project_member SET sum_paid=sum_paid+{$totalPaidFee}, sum_trade=sum_trade+1 WHERE project_id='{$project['id']}' AND mid='{$trade['buyer_id']}'");
        
        if($trade['discount_details']){
            $list = json_decode($trade['discount_details'], true);
            foreach ($list as $item){
                // 满减返现
                if($item['type'] == 'manjian' && ($item['score'] > 0 || $item['coupon_id'] > 0)){
                    $this->lPublish('TradeBackCash', array(
                        'project_id'  => $project['id'],
                        'mid'         => $trade['buyer_id'],
                        'tid'         => $trade['tid'],
                        'activity_id' => $item['id'],
                        'score'       => $item['score'],
                        'coupon_id'   => $item['coupon_id'],
                        'reason'      => '订单'.$trade['tid'].'满减活动赠送'
                    ));
                    break;
                }
            }
        }
        
        // 会员自动升级/下单送积分
        $this->lPublish('MemberCheckLevelup', $trade['buyer_id'], $project['id']);
        // 提醒店铺管理员有人付款记得发货
        $this->lPublish('SellerOrderPaid', $trade['tid']);
        
        // 搜索
        if($data['transaction_id'] != ''){
        	$this->execute("UPDATE trade_search SET other=CONCAT(other, ' ', '{$data['transaction_id']}') WHERE tid='{$trade['tid']}'");
        }
    }
    
    /**
     * 拷贝订单
     */
    public function copy($tid){
        if(!is_numeric($tid)){
            E('订单格式错误');
        }
        
        $sql = "SELECT tid, created, type, `status`, seller_id, seller_remark, receiver_name, buyer_del,
                receiver_mobile, buyer_id, buyer_rate, pay_time, transaction_id, shipping_type, refund_status
                FROM trade WHERE tid='{$tid}'";
        $trade = $this->query($sql);
        if(!$trade){
            E('订单号不存在');
        }
        $trade = $trade[0];
        
        // 同步买家订单
        if(!$trade['buyer_del']){
            $this->execute("INSERT INTO trade_buyer SET
                            buyer_id='{$trade['buyer_id']}',
                            tid='{$trade['tid']}',
                            `status`='{$trade['status']}',
                            seller_id='{$trade['seller_id']}',
                            buyer_rate='{$trade['buyer_rate']}',
                            refund_status='{$trade['refund_status']}'
                            ON DUPLICATE KEY UPDATE
                            `status`=VALUES(`status`),
                            seller_id=VALUES(seller_id),
                            buyer_rate=VALUES(buyer_rate),
                            refund_status=VALUES(refund_status)");
        }
        
        // 同步卖家订单
        $trade['receiver_name'] = addslashes($trade['receiver_name']);
        $this->execute("INSERT INTO trade_seller SET
                        tid='{$trade['tid']}',
                        seller_id='{$trade['seller_id']}',
                        type=(SELECT GROUP_CONCAT(DISTINCT type) FROM trade_order WHERE tid='{$trade['tid']}'),
                        `status`='{$trade['status']}',
                        buyer_id='{$trade['buyer_id']}',
                        buyer_rate='{$trade['buyer_rate']}',
                        pay_time='{$trade['pay_time']}',
                        refund_status='{$trade['refund_status']}'
                        ON DUPLICATE KEY UPDATE
                        `status`=VALUES(status),
                        buyer_rate=VALUES(buyer_rate),
                        pay_time=VALUES(pay_time),
                        refund_status=VALUES(refund_status)");
    }
    
    /**
     * 减库存
     */
    public function minusStock($data){
        if($data['type'] == OrderType::GIFT){
            $giftId = $data['product_id'];
            $sql = "INSERT INTO trade_gift SET
                    tid='{$data['tid']}',
                    gift_id='{$giftId}',
                    mid='{$data['mid']}',
                    created='{$data['created']}',
                    goods_id='{$data['goods_id']}',
                    title=(SELECT title FROM mall_goods WHERE id='{$data['goods_id']}'),
                    quantity='".abs($data['quantity'])."',
                    `status`={$data['status']}
                    ON DUPLICATE KEY UPDATE `status`=VALUES(`status`)";
            $result = $this->execute($sql);
            if($data['status'] == 2){// 0下单未付款，1下单已付款，2取消订单
                $this->execute("UPDATE mall_gift SET sold_num=sold_num-{$data['quantity']} WHERE id='{$giftId}'");
                $this->execute("UPDATE mall_product SET stock=stock+{$data['quantity']} WHERE goods_id='{$data['goods_id']}'");
            }
        }
        
        if($data['quantity'] < 0){
            $result = $this->execute("UPDATE trade_order SET sub_stock=1 WHERE oid='{$data['oid']}' AND sub_stock=0");
            if(!$result){
                return;
            }
        }
        
        $this->execute("UPDATE mall_goods SET stock=stock+{$data['quantity']} WHERE id='{$data['goods_id']}'");
        if($data['type'] == 'gift'){
            $this->execute("UPDATE mall_product SET stock=stock+{$data['quantity']} WHERE goods_id='{$data['goods_id']}'");
        }else{
            $this->execute("UPDATE mall_product SET stock=stock+{$data['quantity']} WHERE id='{$data['product_id']}'");
        }
    }
    
    /**
     * 订单返现
     * $record: tid, project_id, mid, activity_id活动id, score反积分, wallet反货款, balance反可提现, coupon_id反优惠券id, reason原因简要说明
     */
    public function backCash($record){
        if(!is_numeric($record['tid']) || !is_numeric($record['project_id']) || !is_numeric($record['mid']) || !is_numeric($record['activity_id'])){
            E('参数错误');
        }
        
        $record['score'] = is_numeric($record['score']) ? $record['score'] : 0;
        $record['wallet'] = is_numeric($record['wallet']) ? $record['wallet'] : 0;
        $record['balance'] = is_numeric($record['balance']) ? $record['balance'] : 0;
        $record['coupon_id'] = is_numeric($record['coupon_id']) ? $record['coupon_id'] : 0;
        $record['coupon_value'] = 0;
        
        $this->startTrans();
        // 送积分、可提现、不可提现余额
        if($record['score'] > 0 || $record['wallet'] > 0 || $record['balance'] > 0){
            $BalanceModel = new \Common\Model\BalanceModel();
            $BalanceModel->add(array(
                'mid'        => $record['mid'],
                'project_id' => $record['project_id'],
                'type'       => BalanceType::BACK_CASH,
                'reason'     => $record['reason'],
                'balance'    => $record['balance'],
                'wallet'     => $record['wallet'],
                'score'      => $record['score']
            ), true);
        }
        
        // 送优惠券
        if($record['coupon_id'] > 0){
            $CouponSubscribe = new CouponSubscribe($this->redis);
            try{
                $result = $CouponSubscribe->give($record['coupon_id'], $record['mid'], $record['reason']);
                if(isset($result[$record['mid']])){
                    $record['coupon_value'] = $result[$record['mid']]['coupon_value'];
                }
            }catch (\Exception $e){
                $record['reason'] .= '[优惠券]:'.$e->getMessage();
            }
        }
        
        $timestamp = time();
        $sql = "INSERT INTO trade_rebate SET
                    tid='{$record['tid']}'
                    activity_id='{$record['activity_id']}'
                    project_id='{$record['project_id']}'
                    balance='{$record['balance']}'
                    wallet='{$record['wallet']}'
                    score='{$record['score']}'
                    coupon_id='{$record['coupon_id']}'
                    coupon_value='{$record['coupon_fee']}'
                    created={$timestamp}
                    reason='".addslashes($record['reason'])."'";
        $this->execute($sql);
        $this->commit();
    }
    
    /**
     * 订单取消了
     */
    public function cancelled($tid){
        if(!is_numeric($tid)){
            E('订单号不为数字');
        }
        
        // 查找订单
        $trade = $this->query("SELECT tid, `status`, seller_id, buyer_id, created FROM trade WHERE tid='{$tid}'");
        $trade = $trade[0];
        if(!$trade || $trade['status'] != OrderStatus::BUYER_CANCEL){
            return;
        }

        $projectId = IdWork::getProjectId($trade['seller_id']);
        $timestamp = time();
        
        // 关闭子订单
        $this->execute("UPDATE trade_order SET `status`=".OrderStatus::BUYER_CANCEL." WHERE tid='{$tid}'");
        // 查找子订单
        $orders = $this->query("SELECT * FROM trade_order WHERE tid='{$tid}'");
        
        // 取消限购
        $quotaDate = date('Ymd', is_numeric($trade['created']) ? $trade['created'] : strtotime($trade['created']));
        if($quotaDate != date('Ymd')){
            $quotaDate = null;
        }
        
        $redis = $this->Redis();
        
        // 子订单处理
        foreach($orders as $order){
            $ext_params = decode_json($order['ext_params']);
            
            // 把库存加回来
            if($order['sub_stock'] == 1){
                // 取消限购
                if($quotaDate){
                    if(isset($ext_params['every_quota'])){
                        $this->execute("UPDATE trade_every_quota SET quantity=quantity-{$order['quantity']} WHERE buyer_id={$trade['buyer_id']} AND goods_id={$order['goods_id']} AND today={$quotaDate}");
                    }
            
                    if(isset($ext_params['day_quota'])){
                        $this->execute("UPDATE trade_day_quota SET quantity=quantity-{$order['quantity']} WHERE goods_id={$order['goods_id']} AND today={$quotaDate}");
                    }
                }
            
                // 会员卡不恢复库存
                if($order['goods_type'] != 1){
                    $substock = array('tid' => $trade['tid'], 'oid' => $order['oid'], 'goods_id' => $order['goods_id'], 'product_id' => $order['product_id'], 'type' => $order['type'], 'quantity' => $order['quantity'], 'mid' => $trade['buyer_id'], 'created' => $timestamp);
                    if($order['type'] == OrderType::GIFT){
                        $substock['status'] = 2;
                    }
                    
                    $this->minusStock($substock);
                }
            }
            
            // 恢复活动库存
            if($order['type'] != OrderType::GIFT){
                $promotions = decode_json($order['promotion_details']);
                foreach ($promotions as $p){
                    if($p['end_time'] > $timestamp){
                        // 产品库存
                        $key = 'stock_p'.$order['product_id'];
                        if($redis->exists($key)){
                            $result = $redis->decrBy($key, $order['quantity']);
                        }
                    
                        // 商品库存
                        $key = 'stock_g'.$order['goods_id'];
                        if($redis->exists($key)){
                            $result = $redis->decrBy($key, $order['quantity']);
                        }
                    }
                }
            }
        }

        // 关闭佣金
        $Balance = null;
        $list = $this->query("SELECT * from trade_commision WHERE seller_id={$trade['seller_id']} AND tid='{$trade['tid']}'");
        foreach ($list as $item){
            if($item['deducted_time'] > 0){
                continue;
            }
            
            // 扣回佣金
            if(floatval($item['settlement_balance']) > 0 || floatval($item['settlement_wallet']) > 0 || floatval($item['settlement_score']) > 0){
                if(is_null($Balance)){
                    $Balance = new BalanceModel();
                }
                
                $Balance->add(array(
                    'project_id' => $projectId,
                    'mid'        => $item['mid'],
                    'type'       => BalanceType::COMMISION_DEDUCTED,
                    'reason'     => '推广订单'.$trade['tid'].'交易关闭，佣金扣回',
                    'balance'    => -$item['settlement_balance'],
                    'wallet'     => -$item['settlement_wallet'],
                    'score'      => -$item['settlement_score']
                ), true);
            }

            $sql = "UPDATE trade_commision SET
                    settlement_time=IF(settlement_time=0, {$timestamp}, settlement_time),
                    deducted_time={$timestamp},
                    deducted_balance=-settlement_balance,
                    deducted_wallet=-settlement_wallet,
                    deducted_score=-settlement_score
                    WHERE oid='{$item['oid']}' AND mid='{$item['mid']}'";
            $this->execute($sql);
        }
    }
}
?>