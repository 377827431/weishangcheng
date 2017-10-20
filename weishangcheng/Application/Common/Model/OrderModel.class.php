<?php 
namespace Common\Model;

use Org\Wechat\WechatAuth;
use Org\IdWork;
use Common\Model\RefundStatus;

/**
 * 订单处理modal
 * @author lanxuebao
 *
 */
class OrderModel extends BaseModel{
    protected $tableName = 'trade';
    protected $pk = 'tid';
    
    public function buyNum($goods_id, $buyer_id){
        $sql = "SELECT SUM(num) FROM mall_order_product WHERE buyer_id='{$buyer_id}' AND goods_id='{$goods_id}'";
        $result = $this->query($sql);
        $total = current($result[0]);
        return $total ? $total : 0;
    }
    
    /**
     * 根据订单号获取订单
     */
    public function getTradeByTid($tid){
        $sql = "SELECT tid, `status`, buyer_id, buyer_nick, seller_id, seller_name,seller_remark,  kind, total_quantity, discount_fee,
                       total_fee, total_postage, paid_wallet, paid_balance, payment, adjust_fee, paid_fee,
                       total_score, discount_score, paid_score, payscore, pay_timeout, pay_time, pay_type, transaction_id,
                       buyer_rate, refund_status, refunded_fee, refunded_score, created,
                       express_id, consign_time, express_no, sign_time, receive_time, end_time, buyer_remark, anonymous,
                       receiver_name, receiver_mobile, receiver_province, receiver_city, receiver_county, receiver_detail
                FROM trade WHERE tid='{$tid}'";
        $trade = $this->query($sql);
        if(empty($trade)){
            $this->error = '订单不存在';
            return;
        }
        $trade = $trade[0];
        
        // 订单详情
        $sql = "SELECT oid, `status`, type, title, goods_id, product_id, sku_json, cost, quantity, price, score,quantity,
                       total_fee, discount_fee, adjust_fee, payment, discount_score, payscore, original_price, sub_stock,
                       pic_url, shipping_type, tao_id, outer_id, goods_type, buyer_message, main_tag, ext_params
                FROM trade_order AS `order`
                WHERE tid='{$tid}'";
        $orders = $this->query($sql);
        
        // 读取子订单号，用于查找其他数据
        $oids = '';
        foreach ($orders as $i=>$order){
            $oids .= $order['oid'].',';
        }
        $oids = rtrim($oids, ',');
        
        // 退款信息
        $sql = "SELECT refund_id, refund_status, refund_fee, refund_post, refund_quantity, is_received, refund_remark, refund_created,
                       refund_reason, refund_type, refund_express, refund_images, receiver_name, receiver_mobile,
                       receiver_address, reset_times, refund_sremark, refund_express
                FROM trade_refund
                WHERE refund_id IN ({$oids})";
        $refunds = $this->query($sql);
        $list = array();
        foreach ($refunds as $item){
            $item['status_str'] = RefundStatus::getOrderStatus($item['refund_status']);
            $item['refund_images'] = decode_json($item['refund_images']);
            $list[$item['refund_id']] = $item;
        }
        $refunds = $list;
        
        // 推广佣金
        $sql = "SELECT commision.oid, commision.mid, member.`name`, commision.card_id, commision.settlement_balance, commision.settlement_created,
                       commision.reward_type, commision.reward_value, commision.settlement_describe, commision.settlement_type, settlement_time,
                       deducted_balance, deducted_wallet, deducted_score, deducted_time
                FROM trade_commision AS commision
                LEFT JOIN member ON member.id=commision.mid
                WHERE commision.oid IN ($oids)";
        $commisions = $this->query($sql);
        $list = array();
        foreach ($commisions as $item){
            $list[$item['oid']][] = $item;
        }
        $commisions = $list;
        
        // 获取项目信息
        $project = get_project($trade['seller_id'], true);
        $trade['project'] = $project;
        
        // 获取买家信息
        $buyer = $this->getProjectMember($trade['buyer_id'], $project['id']);
        $trade['buyer'] = $buyer;
        
        // 组合订单
        $canRefund = $trade['status'] != OrderStatus::WAIT_PAY && $trade['status'] != OrderStatus::BUYER_CANCEL && $trade['status'] != OrderStatus::SUCCESS;
        foreach ($orders as $i=>$order){
            $order['ext_params'] = decode_json($order['ext_params']);
            $order['spec'] = get_spec_name($order['sku_json'], 1);
            $order['commision'] = $commisions[$order['oid']];
            $order['refund'] = $refunds[$order['oid']];
            if(!$order['refund']){
                $order['refund'] = array('refund_status' => -1, 'refund_quantity' => 0, 'status_str' => '');
                if(isset($order['ext_params']['returns']) && $canRefund && $order['status'] != OrderStatus::BUYER_CANCEL){
                    $order['refund']['refund_status'] = 0;

                    if($order['refund']['refund_status'] == 0){
                        $order['refund']['status_str'] = '联系商家';
                    }
                }
            }
            
            $order['view_price'] = $this->viewPrice($order);
            $order['detail_url'] = $buyer['url'].'/goods?id='.$order['goods_id'];
            $orders[$i] = $order;
        }
        $trade['orders'] = $orders;
        
        // 统一处理
        $trade = $this->handle($trade);
        
        // 售后电话
        $seller = $this->query("SELECT service_hotline FROM shop WHERE id=".$trade['seller_id']);
        if(!$seller || !$seller[0]['service_hotline']){
            $trade['service_hotline'] = $project['service_hotline'];
        }else{
            $trade['service_hotline'] = $seller[0]['service_hotline'];
        }
        
        return $trade;
    }

    /**
     * 查询订单(买家视角)
     */
    public function getTradeByBuyer($where, $limit = 0, $offset = 20){
        $where = implode(' AND ', $where);
        $sql = "SELECT trade.tid, trade.`status`, trade.buyer_id, trade.seller_id, trade.seller_name, trade.kind, trade.total_quantity,
                    trade.total_fee, trade.total_postage, trade.paid_wallet, trade.paid_balance, trade.payment, trade.adjust_fee, trade.paid_fee,
                    trade.total_score, trade.discount_score, trade.paid_score, trade.payscore, trade.pay_timeout, trade.pay_time, trade.pay_type, trade.transaction_id,
                    trade.buyer_rate, trade.refund_status, trade.refunded_fee, trade.refunded_score, trade.created, trade.end_type,
                    trade.express_id, trade.consign_time, trade.express_no, trade.sign_time, trade.receive_time, trade.end_time, trade.buyer_remark, trade.anonymous,
                    trade.receiver_name, trade.receiver_mobile, trade.receiver_province, trade.receiver_city, trade.receiver_county, trade.receiver_detail
                FROM trade_buyer
                INNER JOIN trade ON trade.tid=trade_buyer.tid
                WHERE {$where}
                ORDER BY trade.tid DESC
                LIMIT {$limit}, {$offset}";
        $tradeList = $this->query($sql);
        return $this->tradeListHandle($tradeList);
    }
    
    /**
     * 查询订单(卖家视角)
     */
    public function getTradeBySeller($where, $limit = 0, $offset = 20){
        $where = implode(' AND ', $where);
        $join = '';
        if(strpos($where, 'trade_search.kw') !== false){
        	$join = "INNER JOIN trade_search ON trade_search.tid=trade_seller.tid";
        }

        $sql = "SELECT trade.tid, trade.`status`, trade.buyer_id, trade.seller_id, trade.seller_name, trade.kind, trade.total_quantity, trade.discount_fee,
                    trade.total_fee, trade.total_postage, trade.paid_wallet, trade.paid_balance, trade.payment, trade.adjust_fee, trade.paid_fee, trade.end_type,
                    trade.total_score, trade.discount_score, trade.paid_score, trade.payscore, trade.pay_timeout, trade.pay_time, trade.pay_type, trade.transaction_id,
                    trade.buyer_rate, trade.refund_status, trade.refunded_fee, trade.refunded_score, trade.created, trade.buyer_nick, trade.seller_remark,
                    trade.express_id, trade.consign_time, trade.express_no, trade.sign_time, trade.receive_time, trade.end_time, trade.buyer_remark, trade.anonymous,
                    trade.receiver_name, trade.receiver_mobile, trade.receiver_province, trade.receiver_city, trade.receiver_county, trade.receiver_detail,trade.errmsg 
                FROM trade_seller
                INNER JOIN trade ON trade.tid=trade_seller.tid
        		{$join}
                WHERE {$where}
                ORDER BY trade.tid DESC
                LIMIT {$limit}, {$offset}";
        $tradeList = $this->query($sql);
        return $this->tradeListHandle($tradeList);
    }
    
    /**
     * 查询订单总数(卖家视角)
     */
    public function getTradeBySellerCount($where){
        $where = implode(' AND ', $where);
        
        $join = '';
        if(strpos($where, 'trade_search.kw') !== false){
        	$join = "INNER JOIN trade_search ON trade_search.tid=trade_seller.tid";
        }
        
        $sql = "SELECT COUNT(*) AS rows
                FROM trade_seller
				{$join}
                WHERE {$where}";
        $result = $this->query($sql);
        return $result[0]['rows'];
    }
    
    /**
     * 批量处理订单
     */
    private function tradeListHandle($tradeList){
        if(empty($tradeList)){
            return $tradeList;
        }
        
        ignore_user_abort(true);
        set_time_limit(0);
    
        // 取得订单号
        $tids = '';
        $list = array();
        foreach ($tradeList as $i=>$trade){
            $tids .= ($i == 0 ? '' : ',').$trade['tid'];
    
            $trade['detail_url'] = __MODULE__.'/order/detail?tid='.$trade['tid'];
            $trade['support_refund'] = $trade['status'] != OrderStatus::WAIT_PAY && $trade['status'] != OrderStatus::BUYER_CANCEL;
            $trade['orders'] = array();
            $list[$trade['tid']] = $trade;
        }
        $tradeList = $list;
    
        // 订单详情
        $sql = "SELECT tid, oid, `status`, type, title, goods_id, product_id, sku_json, cost, quantity, price, score,
                    total_fee, discount_fee, adjust_fee, payment, discount_score, payscore, original_price, sub_stock,
                    pic_url, shipping_type, tao_id, outer_id, goods_type, buyer_message, main_tag, ext_params
                FROM trade_order AS `order`
                WHERE tid IN({$tids})";
        $orders = $this->query($sql);
    
        // 读取子订单号，用于查找其他数据
        $oids = '';
        foreach ($orders as $i=>$order){
            $oids .= $order['oid'].',';
        }
        $oids = rtrim($oids, ',');
    
        // 退款信息
        $sql = "SELECT refund_id, refund_status, refund_fee, refund_post, refund_quantity, refund_remark, refund_created,
                    refund_reason, refund_type, refund_express, refund_images, receiver_name, receiver_mobile,
                    receiver_address, reset_times
                FROM trade_refund
                WHERE refund_id IN ({$oids})";
        $refunds = $this->query($sql);
        $list = array();
        foreach ($refunds as $item){
            $list[$item['refund_id']] = $item;
        }
        $refunds = $list;
    
        // 推广佣金
        $sql = "SELECT commision.oid, commision.mid, member.`name`, commision.card_id, commision.settlement_balance, commision.settlement_created,
                    reward_type, commision.reward_value, commision.settlement_describe, commision.settlement_type, settlement_time
                FROM trade_commision AS commision
                LEFT JOIN member ON member.id=commision.mid
                WHERE commision.oid IN ($oids)";
        $commisions = $this->query($sql);
        $list = array();
        foreach ($commisions as $item){
            $list[$item['oid']][] = $item;
        }
        $commisions = $list;
    
        // 组合订单详情
        foreach ($orders as $i=>$order){
            $order['ext_params'] = decode_json($order['ext_params']);
            $order['spec'] = get_spec_name($order['sku_json'], 1);
            $order['commisions'] = $commisions[$order['oid']];
            $order['refund'] = $refunds[$order['oid']];
            if(!$order['refund']){
                if(isset($order['ext_params']['returns']) && $order['status'] != OrderStatus::BUYER_CANCEL && $tradeList[$order['tid']]['support_refund']){
                    $order['refund'] = array('refund_status' => 0, 'status_str' => '申请退款');
                }else{
                    $order['refund'] = false;
                }
            }
    
            $order['view_price'] = $this->viewPrice($order);
            $tradeList[$order['tid']]['orders'][] = $order;
        }
    
        // 统一处理
        $list = array();
        foreach ($tradeList as $trade){
            $list[] = $this->handle($trade);
        }
        return $list;
    }
    
    /**
     * 订单支付超时 - 取消订单
     * @param Trade $trade
     */
    public function isPayTimeout(&$trade){
        return  $trade['status'] == OrderStatus::WAIT_PAY && NOW_TIME >= $trade['pay_timeout'];
    }
    
    /**
     * 是否自动确认收货
     */
    public function canConfirmGoods($trade, $auto = false){
        return 
        $trade['status'] == OrderStatus::WAIT_CONFIRM_GOODS &&
        (!$auto ||
            (
                $trade['refund_status'] != RefundStatus::NO_REFUND &&
                $trade['refund_status'] != RefundStatus::FULL_REFUNDING &&
                $trade['refund_status'] != RefundStatus::PARTIAL_REFUNDING &&
                NOW_TIME >= strtotime('+15 day', $trade['pay_time'])
            )
        );
    }
    
    /**
     * 订单确认收货
     */
    public function confirmGoods($trade, $isAuto = false){
        $timestamp             = time();
        $trade['status']       = OrderStatus::SUCCESS;
        $trade['receive_time'] = $timestamp;
        $trade['modified']     = $timestamp;
        $trade['end_time']     = $timestamp;
        $trade['end_type']     = TradeEndType::SUCESS;
        
        // 退款状态
        if($trade['refund_status'] == RefundStatus::PARTIAL_REFUNDING){
            $trade['refund_status'] = RefundStatus::PARTIAL_FAILED;
        }else if($trade['refund_status'] == RefundStatus::FULL_REFUNDING){
            $trade['refund_status'] = RefundStatus::FULL_FAILED;
        }
        
        $sql = "UPDATE trade SET
                  `status`={$trade['status']},
                   receive_time={$trade['receive_time']},
                   refund_status={$trade['refund_status']},
                   end_time={$trade['end_time']},
                   end_type={$trade['end_type']},
                   modified={$trade['modified']}
                WHERE tid='{$trade['tid']}' AND `status`=".OrderStatus::WAIT_CONFIRM_GOODS;
        $result = $this->execute($sql);
        if($result < 1){
            $this->error = '确认收货失败';
            return $isAuto ? $this->getNewestTradeInfo($trade) : -1;
        }
        
        $this->lPublish('TradeCopy', $trade['tid']);
        
        // 退款状态
        foreach ($trade['orders'] as $i=>$order){
            $refund = $order['refund'];
            if($refund && $refund['refund_status'] > 0 && 
                ($refund['refund_status'] == RefundStatus::APPLYING || $refund['refund_status'] == RefundStatus::WAIT_EXPRESS_NO || $refund['refund_status'] == RefundStatus::WAIT_REFUND)){
                
                $refund['refund_status'] = RefundStatus::CANCEL_REFUND;
                $refund['status_str']    = RefundStatus::getOrderStatus($refund['refund_status']);
                $refund['refund_endtime'] = $timestamp;
                $trade['orders'][$i]['refund'] = $refund;
                
                $this->execute("UPDATE trade_refund SET refund_status={$refund['refund_status']}, refund_endtime={$timestamp} WHERE refund_id={$order['oid']}");
            }
        }

        // 通知佣金结算
        $this->lPublish('CommisionSettlement', $trade['tid']);
        // 获取促销信息
        if(!isset($trade['promotion_details'])){
            $discounts = $this->query("SELECT promotion_details FROM trade_discount WHERE tid='{$trade['tid']}'");
            $trade['promotion_details'] = decode_json($discounts[0]['promotion_details']);
        }
        
        // 通知团购返现
        static $grouponSold = array();
        foreach ($trade['promotion_details'] as $activity){
            if($activity['type'] == ActivityType::GROUPON){
                if(!isset($grouponSold[$activity['id']])){
                    $sold = $this->query("SELECT sold FROM mall_groupon WHERE id={$activity['id']}");
                    $grouponSold[$activity['id']] = $sold[0]['sold'];
                }
                
                $sold = $grouponSold[$activity['id']];
                foreach ($activity['back'] as $quantity=>$back){
                    if($sold < $quantity){
                        break;
                    }
                }
                
                $this->lPublish('TradeBackCash', array(
                    'tid'         => $trade['tid'],
                    'mid'         => $trade['buyer_id'],
                    'project_id'  => IdWork::getProjectId($trade['seller_id']),
                    'score'       => $back['score'],
                    'balance'     => $back['balance'],
                    'wallet'      => $back['wallet'],
                    'coupon_id'   => $back['coupon_id'],
                    'activity_id' => $activity['type'].$activity['id'],
                    'reason'      => $activity['name']
                ));
                break;
            }
        }
        return $trade;
    }
    
    /**
     * 是否可以评价
     */
    public function canRate($trade, $auto = false){
        return $trade['receive_time'] > 0 && $trade['buyer_rate'] == 0 && (!$auto || NOW_TIME >= strtotime('+3 day', $trade['receive_time']));
    }
    
    /**
     * 评价订单
     */
    public function rateTrade($trade, $list = null, $rewardScore = 0){
        $timestamp = time();
        $trade['modified'] = $timestamp;
        $trade['buyer_rate'] = 1;
        
        $result = $this->execute("UPDATE trade SET buyer_rate=1, modified={$timestamp} WHERE tid={$trade['tid']} AND buyer_rate=0");
        if(!$result){
            $this->error = '评价失败';
            return $this->getNewestTradeInfo($trade);
        }
        
        // 通知复制订单
        $this->lPublish('TradeCopy', $trade['tid']);
        
        $feedback = array('非常好，棒棒哒！', '感觉还不错！', '嗯，还可以吧。', '东西很好，下次还来这家买！', '老板人很好，以后就认这家了！');
        $feedmax  = count($feedback)-1;
        if(empty($list)){
            foreach ($trade['orders'] as $order){
                $list[] = array(
                    'seller_id'   => $trade['seller_id'],
                	'buyer_id'    => $trade['buyer_id'],
                	'buyer_nick'  => $trade['buyer_nick'],
                    'tid'         => $trade['tid'],
                    'oid'         => $order['oid'],
                    'goods_id'    => $order['goods_id'],
                    'goods_spec'  => $order['spec'],
                	'feedback'    => '',
                    'score'       => 5,
                    'logistics'   => 5,
                    'service'     => 5,
                    'anonymous'   => $trade['anonymous'],
                    'created'     => $timestamp
                );
            }
        }

        // 统计数据
        $logistics = $list[0]['logistics'];
        $service   = $list[0]['service'];
        $rate_good = $rate_middle = $rate_bad = 0;
        $goods = array();
        foreach ($list as $i=>$rate){
            $data = isset($goods[$rate['goods_id']]) ? $goods[$rate['goods_id']] : array('times' => 0, 'score' => 0, 'good' => 0, 'middle' => 0, 'bad' => 0);
            
            $data['times'] += 1;
            $data['score'] += $rate['score'];
            switch ($rate['score']){
                case 1:
                case 2:
                    $data['bad']++;
                    $rate_bad++;
                    break;
                case 3:
                case 4:
                    $data['middle']++;
                    $rate_middle++;
                    break;
                default:
                    $data['good']++;
                    $rate_good++;
                    break;
            }
            
            $goods[$rate['goods_id']] = $data;
            
            // 默认好评内容，防止用户每次看到的信息都不一致
            if($rate['feedback'] == ''){
            	$list[$i]['feedback'] = $feedback[mt_rand(0, $feedmax)];
            }
        }
        
        $this->startTrans();
        // 批量插入
        M('trade_rate')->addAll($list);
        
        // 更新店铺评分
        $sql = "UPDATE shop SET
                  rate_times=rate_times+1,
                  sum_logistics=sum_logistics+{$logistics},
                  sum_service=sum_service+{$service},
                  logistics_score=TRUNCATE(sum_logistics/rate_times, 8),
                  service_score=TRUNCATE(sum_service/rate_times, 8),
                  rate_good=rate_good+{$rate_good},
                  rate_middle=rate_middle+{$rate_middle},
                  rate_bad=rate_bad+{$rate_bad}
                WHERE id={$trade['seller_id']}";
        $this->execute($sql);
        // 更新商品评分
        foreach ($goods as $goodsId=>$rate){
            $sql = "UPDATE mall_goods_sort SET
                      rate_times=rate_times+{$rate['times']},
                      rate_sum_score=rate_sum_score+{$rate['score']},
                      rate_score=TRUNCATE(rate_sum_score/rate_times, 8),
                      rate_good=rate_good+{$rate['good']},
                      rate_middle=rate_middle+{$rate['middle']},
                      rate_bad=rate_bad+{$rate['bad']}
                    WHERE id={$goodsId}";
            $this->execute($sql);
        }
        $this->commit();

        return $trade;
    }
    
    /**
     * 订单统一处理入口
     * @param unknown $trade
     */
    public function handle($trade){
        static $projectIds = array();
        if(!isset($trade['project'])){
            $projectId = IdWork::getProjectId($trade['seller_id']);
            if(!in_array($projectId, $projectIds)){
                $trade['project'] = get_project($projectId);
            }
        }
        
        // 已付总额
        $trade['paid_total']   = bcadd($trade['paid_wallet'], $trade['paid_balance'], 2);
        $trade['paid_total']   = bcadd($trade['paid_total'], $trade['paid_fee'], 2);
        // 应付总额
        $trade['need_payment'] = bcadd($trade['paid_total'], $trade['payment'], 2);
        $trade['need_score']   = floatval(bcadd($trade['paid_score'], $trade['payscore'],2));
        
        
        // 是否付款超时
        if($this->isPayTimeout($trade)){
            $endType = TradeEndType::getById(TradeEndType::PAY_TIMEOUT, false);
            $this->cancelTrade($trade, $endType);
        }
        
        // 是否自动确认收货
        if($this->canConfirmGoods($trade, true)){
            $this->confirmGoods($trade);
        }
        
        // 是否自动评价
        if($this->canRate($trade, true)){
            $trade = $this->rateTrade($trade);
        }
        
        // 查找物流信息 [{'快递单号':'快递公司'}]
        $trade['express_no'] = decode_json($trade['express_no']);
        $express = StaticModel::express($trade['express_id']);
        $trade['express_name'] = $express['name'];
        if($trade['express_no']){
            $i = 0;
            $baoguo = array('一', '二', '三', '四', '五', '六', '七', '八', '九', '十');
            foreach ($trade['express_no'] as $no=>$name){
                $trade['express_no'][$no] = $name.'(包裹'.$baoguo[$i].')';
                $i++;
            }
        }
        
        // 汉化状态
        $status_str = OrderStatus::getById($trade['status'],false);
        $trade['status_str'] = $status_str['title'];
        $trade['status_desc'] = $status_str['describe'];
        $trade['refund_status_str'] = RefundStatus::getTradeStatus($trade['refund_status']);
        $trade['pay_type_str'] = PayType::getById($trade['pay_type']);

        // 订单按钮
        if(MODULE_NAME != 'Admin'){
            if (APP_NAME != P_USER){
                $trade['buttons'] = array();
                if($trade['status'] == OrderStatus::WAIT_PAY){
                    $trade['buttons'][] = array('text' => '取消', 'class' => 'js-cancel-trade', 'url' => __MODULE__.'/order/cancel');
                    $trade['buttons'][] = array('text' => '付款', 'class' => '', 'url' => C('PAY_URL').'/order/detail?tid='.$trade['tid'].'&ticket='.session_id());
                }else if($trade['status'] == OrderStatus::WAIT_SEND_GOODS || $trade['status'] == OrderStatus::OUT_STOCK || $trade['status'] == OrderStatus::PART_SEND_GOODS){
                    $trade['buttons'][] = array('text' => '查看我的订单', 'class' => '', 'url' => __MODULE__.'/order');
                }else if($trade['status'] == OrderStatus::WAIT_CONFIRM_GOODS){
                    if(!empty($trade['express_no'])){
                        $trade['buttons'][] = array('text' => '查看物流', 'class' => 'js-search-express', 'url' => 'https://m.kuaidi100.com/result.jsp?nu='.key($trade['express_no']));
                    }
                    $trade['buttons'][] = array('text' => '确认收货', 'class' => 'js-confirm-goods', 'url' => __MODULE__.'/order/confirm');
                }else if($trade['status'] == OrderStatus::SUCCESS){
                    $trade['buttons'][] = array('text' => '删除', 'class' => 'js-delete-trade', 'url' => __MODULE__.'/order/delete');

                    if($trade['buyer_rate'] == 0){
                        $trade['buttons'][] = array('text' => '评价', 'class' => '', 'url' => __MODULE__.'/order/rate?tid='.$trade['tid']);
                    }
                }else if($trade['status'] == OrderStatus::BUYER_CANCEL){
                    $trade['buttons'][] = array('text' => '删除', 'class' => 'js-delete-trade', 'url' => __MODULE__.'/order/delete');
                }
            }else{
                $trade['buttons'] = array();
                if($trade['status'] == OrderStatus::WAIT_PAY){
                    $trade['buttons'][] = array('text' => '取消', 'class' => 'js-cancel-trade', 'url' => __MODULE__.'/ordernew/cancel');
                    $trade['buttons'][] = array('text' => '付款', 'class' => '', 'url' => C('PAY_URL').'/order/detail?tid='.$trade['tid'].'&ticket='.session_id());
                }else if($trade['status'] == OrderStatus::WAIT_SEND_GOODS || $trade['status'] == OrderStatus::OUT_STOCK || $trade['status'] == OrderStatus::PART_SEND_GOODS){
                    $trade['buttons'][] = array('text' => '查看我的订单', 'class' => '', 'url' => __MODULE__.'/ordernew');
                }else if($trade['status'] == OrderStatus::WAIT_CONFIRM_GOODS){
                    if(!empty($trade['express_no'])){
                        $trade['buttons'][] = array('text' => '查看物流', 'class' => 'js-search-express', 'url' => 'https://m.kuaidi100.com/result.jsp?nu='.key($trade['express_no']));
                    }
                    $trade['buttons'][] = array('text' => '确认收货', 'class' => 'js-confirm-goods', 'url' => __MODULE__.'/ordernew/confirm');
                }else if($trade['status'] == OrderStatus::SUCCESS){
                    $trade['buttons'][] = array('text' => '删除', 'class' => 'js-delete-trade', 'url' => __MODULE__.'/ordernew/delete');

                    if($trade['buyer_rate'] == 0){
                        $trade['buttons'][] = array('text' => '评价', 'class' => '', 'url' => __MODULE__.'/ordernew/rate?tid='.$trade['tid']);
                    }
                }else if($trade['status'] == OrderStatus::BUYER_CANCEL){
                    $trade['buttons'][] = array('text' => '删除', 'class' => 'js-delete-trade', 'url' => __MODULE__.'/ordernew/delete');
                }
            }
        }
        
        $trade['created'] = date('Y-m-d H:i:s',$trade['created']);
        $trade['end_type_str'] = TradeEndType::getById($trade['end_type']);
        return $trade;
    }
    
    /**
     * 覆盖最新订单信息
     */
    private function getNewestTradeInfo($trade){
        $new = $this->field("`status`, receive_time, modified, refund_status, end_type, end_time, modified, buyer_rate, refunded_fee, refunded_score")->find($trade['tid']);
        return array_merge($trade, $new);
    }
    
    /**
     * 取消交易
     * @param $trade
     * @param $reason
     */
    public function cancelTrade($trade, $tradeEndType){
        if(!isset($trade['orders'])){
            E('trade.orders必须');
        }
        
        if($trade['status'] != OrderStatus::WAIT_PAY){
            $this->error = '仅待付款订单可取消';
            return;
        }
        
        // 原订单状态
        $tradeOrginalStatus = $trade['status'];
        $timestamp = time();
        $trade['status'] = OrderStatus::BUYER_CANCEL;
        $trade['refund_status'] = RefundStatus::NO_REFUND;
        $trade['end_type'] = $tradeEndType['id'];
        $trade['end_time'] = $timestamp;
        $trade['modified'] = $timestamp;
        
        // 返还金额
        $refundedFee = $trade['paid_total'];
        $refundScore = $trade['paid_score'];
        
        $trade['refunded_fee'] = bcadd($trade['refunded_fee'], $refundedFee, 2);
        $trade['refunded_score'] = bcadd($trade['refunded_score'], $refundScore, 2);
        $trade['refunded_score'] = floatval($trade['refunded_score']);
        
        // 更新订单信息
        $sql = "UPDATE trade SET
                    status='{$trade['status']}', end_time='{$trade['end_time']}', end_type='{$trade['end_type']}',
                    refund_status='{$trade['refund_status']}',
                    refunded_fee=refunded_fee+{$refundedFee}, refunded_score=refunded_score+{$refundScore},errmsg='手动关闭订单' 
                WHERE tid='{$trade['tid']}' AND `status` =".OrderStatus::WAIT_PAY;
        $result = $this->execute($sql);
        if($result < 1){
            //$this->error = '取消失败：状态已变更为'.OrderStatus::getById($trade['status']);
            return $this->getNewestTradeInfo($trade);
        }

        // 通知拷贝订单
        $this->lPublish('TradeCopy', $trade['tid']);
        
        $this->startTrans();
        // 退还支付的金额
        $projectId = IdWork::getProjectId($trade['seller_id']);
        static $BalanceModel = null;
        if(is_null($BalanceModel)){
            $BalanceModel = new \Common\Model\BalanceModel();
        }
        $BalanceModel->add(array(
            'mid'        => $trade['buyer_id'],
            'project_id' => $projectId,
            'type'       => BalanceType::TRADE_CANCEL,
            'reason'     => '订单['.$trade['tid'].']'.$tradeEndType['title'].'，支付金额退还',
            'balance'    => $trade['paid_balance'],
            'wallet'     => bcadd($trade['paid_wallet'], $trade['paid_fee'], 2),
            'score'      => $trade['paid_score'],
        ), true);

        // 关闭佣金
        foreach ($trade['orders'] as $i=>$order){
            $order['status'] = OrderStatus::BUYER_CANCEL;
            foreach ($order['commision'] as $ci=>$commision){
                $commision['settlement_time'] = $timestamp;
                $commision['settlement_type'] = 0;
                $order['commision'][$ci] = $commision;
            }
            $trade['orders'][$i] = $order;
        }
        
        $this->commit();
        
        // 通知订单已被取消
        $this->lPublish('TradeCancelled', $trade['tid']);
        return $trade;
    }
    
    /**
     * 删除订单
     */
    public function delete($tid, $buyer_id){
        $this->execute("UPDATE trade SET buyer_del=1 WHERE tid='{$tid}' AND buyer_id='{$buyer_id}'");
        $this->execute("DELETE FROM trade_buyer WHERE buyer_id='{$buyer_id}' AND tid='{$tid}'");
    }

    /**
     * 卖家端删除订单
     */
    public function delete2($tid, $seller_id){

        $this->execute("UPDATE trade SET seller_del=1 WHERE tid='{$tid}' AND seller_id='{$seller_id}'");
        $this->execute("DELETE FROM trade_seller WHERE seller_id='{$seller_id}' AND tid='{$tid}'");
    }
    
    /**
     * 订单商品显示的价格
     */
    private function viewPrice($order){
        $view = array();
        if($order['score'] > 0){
            $view[] = array('price' => $order['score'], 'prefix' => '', 'suffix' => '积分');
            if($order['price'] > 0){
                $view[] = array('price' => sprintf('%.2f', $order['price']), 'prefix' => '+', 'suffix' => '元');
            }else{
                $view[] = array('price' => sprintf('%.2f', $order['original_price']), 'prefix' => '¥', 'suffix' => '');
            }
        }else{
            $view[] = array('price' => sprintf('%.2f', $order['price']), 'prefix' => '¥', 'suffix' => '');
            if($order['original_price'] > 0){
                $view[] = array('price' => sprintf('%.2f', $order['original_price']), 'prefix' => '¥', 'suffix' => '');
            }else{
                $view[] = array('price' => '&nbsp;', 'prefix' => '', 'suffix' => '');
            }
        }
        
        return $view;
    }
    
    /**
     * 修改订单价格
     */
    public function adjustFee($trade, $change){
        if($trade['status'] != OrderStatus::WAIT_PAY){
            $this->error = '非“待付款”订单无法修改价格';
            return;
        }else if($change['payment'] == $trade['payment']){
            $this->error = '需付金额未变更，无需修改';
            return;
        }
        
        // 固定金额
        $fixedFee = bcsub($trade['total_fee'], $trade['paid_total'], 2);
        $fixedFee = bcsub($fixedFee, $trade['discount_fee'], 2);
        
        $payment = bcadd($fixedFee, $change['postage'], 2);
        $payment = bcadd($payment, $change['adjust_fee'], 2);
        
        $minValue = APP_DEBUG ? 0.01 : 1;
        if(floatval($change['postage']) < 0){
            $this->error = '邮费不能小于0元，请重新改价';
            return;
        }else if(floatval($change['payment']) < $minValue || floatval($payment) < $minValue){
            $this->error = '支付总金额不能低于'.$minValue.'元，请重新改价';
            return;
        }else if(sprintf('%.2f', $change['payment']) != $payment){
            $this->error = $change['payment'].'改价后金额与服务器计算不一致'.$payment;
            return;
        }
        
        $orders = array();
        $maxValue = 0; // 最多可调价金额
        foreach($trade['orders'] as $i=>$order){
            $payment = bcsub($order['payment'], $order['adjust_fee'], 2);
            if(floatval($payment) > 0){
                $orders[$order['oid']] = $payment;
                $maxValue = bcadd($maxValue, $payment);
            }
        }
        $maxValue = floatval($maxValue);
        if(floatval($change['adjust_fee']) < -$maxValue){
            $this->error = '最多可减'.$maxValue.'元';
            return;
        }
        
        // 单品分摊改价
        $avgs = avg_dsicount($change['adjust_fee'], $orders);
        
        $this->startTrans();
        // 订单订单需付金额
        $sql = "UPDATE trade SET
        payment={$change['payment']},
        total_postage={$change['postage']},
        adjust_fee={$change['adjust_fee']},
        modified=".NOW_TIME."
        WHERE tid='{$trade['tid']}'";
        $this->execute($sql);
        
        // 子订单需付
        foreach ($avgs as $oid=>$discount){
            // 未改价时需付的金额
            $payment = $orders[$oid];
            $payment = bcadd($payment, $discount, 2);
            $this->execute("UPDATE trade_order SET adjust_fee={$discount}, payment={$payment} WHERE oid='{$oid}'");
        }
        $this->commit();
        
        if(!$trade['buyer']['subscribe']){
            return true;
        }
        
        // 发送消息通知
        $buyer  = $trade['buyer'];
        $config = get_wx_config($buyer['appid']);
        $text   = '卖家已为您的订单修改价格';
        $text  .= '\r\n订单编号：'.$trade['tid'];
        $text  .= '\r\n原需支付：'.sprintf('%.2f', $trade['payment']);
        $text  .= '\r\n现需支付：'.sprintf('%.2f', $change['payment']);
        $text  .= '\r\n系统提示：请'.date('d号H点i分', $trade['pay_timeout']).'前完成支付，逾期订单将自动关闭。<a href=\"'.$buyer['url'].'/order/detail?tid='.$trade['tid'].'\">立即支付</a>';
        
        $wechatAuth = new WechatAuth($config);
        $wechatAuth->sendText($buyer['openid'], $text);
        return true;
    }
    
    /**
     * 根据商品ID获取商品某段时间的销量
     * @param unknown $goodsId
     * @param unknown $startTime
     * @param unknown $endTime
     * @param number $buyerId
     * @return number|mixed
     */
    public function getSoldNumByGoods($goodsId, $buyerId = 0, $startTime = '', $endTime = NOW_TIME){
        if(empty($startTime)){
            $startTime = strtotime('-3 month');
            $endTime = NOW_TIME;
        }
        
        if(is_numeric($startTime)){
            $payStart = date('Y-m-d H:i:s', $startTime);
            $startTid = date('Ymd', $startTime).'00000';
        }else{
            $payStart = $startTime;
            $startTid = date('Ymd', strtotime($startTime)).'00000';
        }
        
        if(is_numeric($endTime)){
            $payEnd = date('Y-m-d H:i:s', $endTime);
            $endTid = date('Ymd', $endTime).'99999';
        }else{
            $payEnd = $endTime;
            $endTid = date('Ymd', strtotime($endTime)).'99999';
        }
        
        $where = "WHERE `order`.oid BETWEEN '{$startTid}' AND '{$endTid}'";
        if($buyerId > 0){ $where .= " AND trade.buyer_id='{$buyerId}'"; }
        $where .= " AND `order`.goods_id='{$goodsId}'";
        
        $sql = "SELECT SUM(`order`.stock_num) AS sold
                FROM mall_order AS `order`
                INNER JOIN mall_trade AS trade ON trade.tid=`order`.tid
                {$where}";
        $result = $this->query($sql);
        return is_numeric($result[0]['sold']) ? $result[0]['sold'] : 0;
    }
    
    /**
     * 订单发货
     */
    public function sendGoods($trade, $noList){
        if($trade['status'] != OrderStatus::WAIT_SEND_GOODS && $trade['status'] != OrderStatus::OUT_STOCK && $trade['status'] != OrderStatus::WAIT_CONFIRM_GOODS){
            $this->error = '订单状态为[待发货、出库中、待确认收货]时方可上传单号';
            return;
        }
        
        $newNo = $express = array();
        foreach ($noList as $item){
            if(!$item['no'] || !$item['name']){
                $this->error = '运单号格式错误';
                return;
            }
            
            if(!isset($trade['express_no'][$item['no']])){
                $newNo[$item['no']] = $item['name'];
            }
            $express[$item['no']] = $item['name'];
        }
        
        if(empty($express)){
            $this->error = '请至少上传一个运单号';
            return;
        }
        
        $timestamp = NOW_TIME;
        $this->execute("UPDATE trade SET express_no='".encode_json($express)."', `status`=IF(`status`=3 OR `status`=4, 6, `status`), consign_time=IF(consign_time>0,consign_time,{$timestamp}), modified={$timestamp} WHERE tid='{$trade['tid']}'");
        $this->lPublish('TradeCopy',$trade['tid']);

        $buyer = $trade['buyer'];
        if(!$buyer['subscribe'] || empty($newNo)){
            return true;
        }
        
        $baoguo = count($express);
        $config = get_wx_config($buyer['appid']);
        $WechatAuth = new WechatAuth($config);
        $template = array(
            'template_id' => $config['template']['OPENTM200565259'],
            'url' => $buyer['url'].'/order/detail?tid='.$trade['tid'],
            'data' => array(
                "first"    => array("value" => '您的订单已发货，请保持收货人手机号畅通！'),
                "keyword1" => array("value" => $trade['tid']),
                "keyword2" => array("value" => current($newNo).($baoguo > 1 ? '(共'.$baoguo.'个包裹)' : '')),
                "keyword3" => array("value" => implode(';', array_keys($newNo))),
                "remark"   => array("value" => '收货信息：'.$trade["receiver_name"] ." ".$trade["receiver_mobile"]." ".$trade["receiver_province"]." ".$trade["receiver_city"]." ".$trade["receiver_county"]." ".$trade["receiver_detail"])
            )
        );
        $result = $WechatAuth->sendTemplate($buyer['openid'], $template);
        return true;
    }
}
?>