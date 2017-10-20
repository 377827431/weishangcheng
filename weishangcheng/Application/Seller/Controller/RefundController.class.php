<?php
namespace Seller\Controller;

use Common\Model\OrderStatus;
use Common\Model\RefundStatus;
use Common\Model\RefundReason;
use Common\Model\OrderType;
use Common\Model\BaseModel;
use Org\IdWork;
use Common\Model\MessageType;
use Common\Model\OrderModel;
use Common\Model\BalanceType;
use Org\WxPay\WxPayRefund;
use Org\WxPay\WxPayApi;

/**
 * 订单退款
 * @author 兰学宝
 *
 */
class RefundController extends ManagerController
{
    private function getRefundById($oid){
        if(!is_numeric($oid)){
            $this->error('订单号不能为空');
        }

        $Model = M();
        $sql = "SELECT `order`.oid, `order`.title, `order`.type, `order`.quantity, `order`.payment, `order`.ext_params, trade.`status` AS trade_status,
                    refund.refund_status, refund.refund_fee, refund.refund_post, refund.refund_quantity, refund.refund_remark, trade.seller_name,
                    refund.refund_reason, refund.refund_type, refund.refund_express, refund.refund_images, refund.refund_sremark, trade.buyer_id,
                    refund.receiver_name, refund.receiver_mobile, refund.receiver_address, is_received, refund_id, `order`.sku_json,
                    shop_refund.message_mid, shop_refund.special, `order`.tid, trade.paid_wallet, trade.paid_balance, trade.paid_fee, trade.seller_id,
                    refund.receiver_address, refund.receiver_mobile, refund.receiver_name
                FROM trade_order AS `order`
                LEFT JOIN trade ON trade.tid=`order`.tid
                LEFT JOIN trade_refund AS refund ON refund.refund_id=`order`.oid
                LEFT JOIN shop_refund ON shop_refund.id=trade.seller_id
                WHERE `order`.oid='{$oid}'";
        $data = $Model->query($sql);
        $data = $data[0];
        if(!$data){
            $this->error('订单不存在');
        }if($data['type'] == OrderType::GIFT){
            $this->error('赠品无法退款');
        }if($data['trade_status'] == OrderStatus::WAIT_PAY){
            $this->error('待付款订单无法申请退款');
        }if($data['refund_status'] == 0){
            if($data['trade_status'] == OrderStatus::SUCCESS || $data['trade_status'] == OrderStatus::BUYER_CANCEL){
                $this->error('当前状态无法申请退款');
            }
            
            $param = decode_json($data['ext_params']);
            if(!isset($param['returns'])){
                $this->error('此产品不支持退换货');
            }
        }
        
        $data['spec']       = get_spec_name($data['sku_json']);
        $data['paid_total'] = bcadd($data['paid_wallet'], $data['paid_balance'], 2);
        $data['paid_total'] = bcadd($data['paid_total'], $data['paid_fee'], 2);
        $data['special']    = $data['special'] ? explode(',', $data['special']) : array();
        return $data;
    }

    public function index(){
        //$userId = $this->user("id");
        $oid = $_GET['refund_id'];
        $data = $this->getRefundById($oid);
        $label = array(
            'is_received' => array(0 => '我还没有发货', 1 => '我已发货'),
            'refund_type' => array(0 => '我要退货退款', 1 => '仅退款', 2 => '我要换货')
        );
        $view = array('refund_id' => $data['refund_id'], 'refund_remark' => $data['refund_remark'], 'refund_images' => decode_json($data['refund_images']));
        
        // 是否收到物品
        $is_received = $data['refund_status'] == 11 ? $data['is_received'] :
            (OrderStatus::WAIT_SEND_GOODS || $data['trade_status'] == OrderStatus::OUT_STOCK ? 0 : 1);
        $view['is_received'] = array('value' => $is_received, 'text' => $label['is_received'][$is_received]);

        // 希望卖家做什么操作
        $refund_type = $data['refund_status'] == 11 ? $data['refund_type'] : 
            (OrderStatus::WAIT_SEND_GOODS || $data['trade_status'] == OrderStatus::OUT_STOCK ? 1 : 0);
        $view['refund_type'] = array('value' => $refund_type, 'text' => $label['refund_type'][$refund_type]);
        
        $view['max'] = floatval($data['payment']);
        $view['refund_fee'] = $data['refund_status'] == 0 ? '' : floatval(bcadd($data['refund_fee'], $data['refund_post'], 2));

        // 退款原因
        $wsdwp = RefundReason::getAll($data['trade_status'] != OrderStatus::WAIT_SEND_GOODS);
        $ysdwp = RefundReason::getAll(true);

        $reason = $data['refund_reason'];
        if(isset($wsdwp[$reason])){
            $view['reason'] = $wsdwp[$reason]['title'];
        }else if(isset($ysdwp[$reason])){
            $view['reason'] = $ysdwp[$reason]['title'];
        }else{
            $view['reason'] = '请选择';
            $reason = 0;
        }
        $view['tid'] = $data['tid'];
        if($data['refund_id']){
            $view['refund_quantity'] = $data['refund_quantity'];
        }else{
            $view['refund_quantity'] = $data['quantity'];
        }
        // 删除七天无理由
        /*if(!in_array(10, $data['special'])){
            unset($wsdwp[10]);
            unset($ysdwp[10]);
        }*/
        $this->assign('wsdwp', json_encode($wsdwp));
        $this->assign('ysdwp', json_encode($ysdwp));
        $this->assign('data', $view);
        //print_data($data);
        if($data['refund_status'] == 0 || $data['refund_status'] == 11){
            $post = array(
                'refund_id'     => $data['oid'],
                'is_received'   => $is_received,
                'refund_fee'    => bcadd($data['refund_fee'], $data['refund_post'], 2),
                'refund_remark' => $data['refund_remark'],
                'refund_reason' => $reason,
                'refund_type'   => $data['refund_type'],
                'refund_images' => decode_json($data['refund_images'])
            );
            $this->assign('post', json_encode($post));
            $this->showAdd($data, $view);
        }else{
            $post = array(
                'refund_id'     => $data['oid'],
                'is_received'   => $is_received,
                'refund_fee'    => bcadd($data['refund_fee'], $data['refund_post'], 2),
                'refund_remark' => $data['refund_remark'],
                'refund_reason' => $reason,
                'refund_type'   => $data['refund_type'],
                'refund_images' => decode_json($data['refund_images'])
            );
            $this->assign('post', json_encode($post));
            $this->showDetail($data, $view);
        }
    }

    /**
     * 显示添加 和 修改视图
     */
    private function showAdd($data, $view){
        $this->display('add');
    }
    
    /**
     * 保存退款记录
     */
    public function add(){
        $userId = session("manager.shop_id");
        $refund = array(
            'refund_id'     => $_POST['refund_id'],
            'is_received'   => $_POST['is_received'],
            'refund_fee'    => floatval($_POST['refund_fee']),
            'refund_remark' => trim($_POST['refund_remark']),
            'refund_reason' => $_POST['refund_reason'],
            'refund_type'   => $_POST['refund_type'],
            'refund_images' => encode_json($_POST['refund_images'])?encode_json($_POST['refund_images']):"",
            'refund_modify' => NOW_TIME,
            'refund_status' => RefundStatus::APPLYING,
            'refund_quantity' => $_POST['refund_quantity']
        );
        $data = $this->getRefundById($refund['refund_id']);
        if($userId != $data['seller_id']){
            $this->error('操作无效：下单人与登陆人不符');
        }if($data['refund_status'] && $data['refund_status'] != RefundStatus::APPLYING){
            $this->error('操作无效：退款状态已变更');
        }if($refund['refund_fee'] > floatval($data['payment'])){
            $this->error('最多可申请'.$data['payment'].'元');
        }
        
        // 计算退款数量
        /*$onePayment = bcdiv($data['payment'], $data['quantity'], 2);
        $refundNum  = bcdiv($data['refund_fee'], $onePayment, 2);
        $refundNum  = ceil($refundNum);
        $refund['refund_quantity'] = $refundNum > $data['quantity'] ? $data['quantity'] : $refundNum;*/
        
        $Model = M('trade_refund');
        if(!$data['refund_id']){ // 添加
            $refund['refund_sid'] = $data['seller_id'];
            $refund['refund_tid'] = $data['tid'];
            $Model->add($refund);
        }else{ // 修改
            $Model->where("refund_id='{$data['refund_id']}'")->save($refund);
        }
        
        // 重新计算订单的退款状态
        $this->updateTradeRefundStatus($Model, $data);
        
        // 提醒店铺售后客服
        $this->sendMsgToShop($data['message_mid'], $data['seller_id'], array(
            'title'      => '尊敬的'.$data['seller_name'].'售后客服',
            'tid'        => $data['tid'],
            'progress'   => '待审核',
            'goods'      => $data['title'].$data['spec'],
            'refund_fee' => sprintf('%.2f', $refund['refund_fee']),
            'remark'     => '售后原因：'.RefundReason::getById($refund['refund_reason']).'。'.$refund['refund_remark']
        ));
        $this->success('已提交申请');
    }
    
    /**
     * 更新订单退款状态
     */
    private function updateTradeRefundStatus($Model, $trade){
        // 此单用户支付总额
        $paidTotal = floatval($trade['paid_total']);
        
        // 查找最新的退款信息
        $list = $Model->query("SELECT refund_status, (refund_fee + refund_post) AS refunded_fee FROM trade_refund WHERE refund_sid={$trade['seller_id']} AND refund_tid={$trade['tid']} GROUP BY refund_status");
        $refundArray = array('doing' => '0.00', 'refunded' => '0.00', 'faild' => '0.00');
        foreach ($list as $refund){
            $status = $refund['refund_status'];
            $key = '';
            if($status == RefundStatus::CANCEL_REFUND){
                continue;
            }else if($status == RefundStatus::APPLYING || $status == RefundStatus::WAIT_EXPRESS_NO || $status == RefundStatus::WAIT_REFUND){
                $key = 'doing';
            }else if($status == RefundStatus::REFUSED_REFUND){
                $key = 'faild';
            }else if($status == RefundStatus::REFUNDED){
                $key = 'refunded';
            }else{
                continue;
            }
            
            $refundArray[$key] = bcadd($refundArray[$key], $refund['refunded_fee'], 2);
        }
        
        // 计算订单最新的退款状态
        $amount = 0;
        if($refundArray['doing'] != '0.00'){
            $amount = floatval($refundArray['doing']);
            $refundStatus = $amount >= $paidTotal ? RefundStatus::FULL_REFUNDING : RefundStatus::PARTIAL_REFUNDING;
        }else if($refundArray['refunded'] != '0.00'){
            $amount = floatval($refundArray['refunded']);
            if($amount > $paidTotal){
                $refundStatus = RefundStatus::EXCESS_REFUND;
            }else{
                $refundStatus = $amount == $paidTotal ? RefundStatus::FULL_REFUNDED : RefundStatus::PARTIAL_REFUNDED;
            }
        }else if($refundArray['faild'] != '0.00'){
            $amount = floatval($refundArray['faild']);
            $refundStatus = $amount >= $paidTotal ? RefundStatus::FULL_FAILED : RefundStatus::PARTIAL_FAILED;
        }else{
            $refundStatus = RefundStatus::NO_REFUND;
        }
        
        // 保存订单退款信息
        $timestamp = NOW_TIME;
        $sql = "UPDATE trade SET refund_status={$refundStatus}, refunded_fee={$refundArray['refunded']}, modified={$timestamp} WHERE tid='{$trade['tid']}'";
        $Model->execute($sql);
    }
    
    /**
     * 取消申请
     */
    /*public function cancel(){
        $userId = $this->user("id");
        $data = $this->getRefundById($_POST['refund_id']);
        if($userId != $data['buyer_id']){
            $this->error('操作无效：下单人与登陆人不符');
        }if($data['refund_status'] != RefundStatus::APPLYING){
            $this->error('操作无效：退款状态已变更');
        }
        
        $Model = M('trade_refund');
        $Model->where("refund_id=".$data['refund_id'])->save(array(
            'refund_status'  => RefundStatus::CANCEL_REFUND,
            'refund_modify'  => NOW_TIME,
            'refund_endtime' => NOW_TIME,
            'reset_times'    => 0
        ));
        
        // 重新计算订单的退款状态
        $this->updateTradeRefundStatus($Model, $data);
        $this->success('已取消申请');
    }*/

    /**
    * 显示退款详情
    */
    private function showDetail($data){
        $this->assign('can_change_express', $data['refund_status'] == RefundStatus::WAIT_EXPRESS_NO || $data['refund_status'] == RefundStatus::WAIT_REFUND);
        $this->assign('refund', $data);
        $this->display('detail');
    }
    
    /**
     * 保存快递单号
     */
    public function express(){
        $refund_id = $_POST['refund_id'];
        $refund_express = $_POST['refund_express'];
        if(strlen($refund_express) < 10){
            $this->error('返回快递单号过短');
        }
        
        $userId = $this->user("id");
        $data = $this->getRefundById($refund_id);
        if($userId != $data['buyer_id']){
            $this->error('操作无效：下单人与登陆人不符');
        }if($data['refund_status'] != RefundStatus::WAIT_EXPRESS_NO){
            $this->error('操作无效：退款状态已变更');
        }
        
        $Model = M('trade_refund');
        $Model->where("refund_id=".$data['refund_id'])->save(array(
            'refund_status' => RefundStatus::WAIT_REFUND,
            'refund_modify' => NOW_TIME,
            'refund_express'=> $refund_express
        ));
        
        $this->sendMsgToShop($data['message_mid'], $data['seller_id'], array(
            'title'      => '尊敬的'.$data['seller_name'].'售后客服',
            'tid'        => $data['tid'],
            'progress'   => '买家已上传退货快递单号',
            'goods'      => $data['title'].$data['spec'],
            'refund_fee' => bcadd($data['refund_fee'], $data['refund_post'], 2),
            'remark'     => '退货运单：'.$refund_express.'。签收后记得给买家退款哦！祝您生活愉快！',
        ));
        $this->success('我们已通知商家会尽快为您退款！');
        
    }
    
    /*
     * 发送推送*/
    private function sendMsgToShop($mid, $shopId, $message){
        if(!$mid){
            return;
        }
        
        $Model = new BaseModel();
        $member = $Model->getProjectMember($mid, IdWork::getProjectId($shopId));
        if(!$member['subscribe']){
            return;
        }
        
        // 放入消息队列
        $message['url'] = $member['url'].'/order/detail?tid='.$message['tid'];
        $Model->lPublish('MessageNotify', array(
            'type'   => MessageType::REFUND,
            'openid' => $member['openid'],
            'appid'  => $member['appid'],
            'data'   => $message
        ));
    }

    /**
     * 处理退款
     */
    public function handle(){
        $prevTime = session('prev_refund_time');
        /*if($prevTime && NOW_TIME - $prevTime < 60){
            $this->error('操作频繁，请'.(60 - (NOW_TIME - $prevTime)).'秒后再试');
        }*/
        session('prev_refund_time', NOW_TIME);
        
        $Model = null;
        $trade = $this->getTradeByTid($Model, $_POST['tid']);
        
        if($trade['status'] == OrderStatus::BUYER_CANCEL){
            $this->error('订单已被取消，无法处理退款');
        }
        
        $shopRefund = $this->getShopRefund($Model, $trade['seller_id']);
        if($_POST['action'] != 'refuse' && $_POST['action'] != 'cancel' && NOW_TIME - $trade['pay_time'] > $shopRefund['admin_max_day'] * 86400){
            $this->error('订单已超过'.$shopRefund['admin_max_day'].'天，请直接拒绝或取消退款');
        }
        
        $exists = false;
        foreach ($trade['orders'] as $order){
            if($_POST['refund_id'] == $order['oid']){
                $exists = true;
                break;
            }
        }
        
        if(!$exists){
            $this->error('子订单不存在');
        }/*else if($_POST['refund_type'] == 0 && empty($_POST['receiver_name']) || strlen($_POST['receiver_mobile']) != 11 || strlen($_POST['receiver_address']) < 10){
            $this->error('请补全退货地址');
        }*/
        $refund = array(
            'refund_id'       => $_POST['refund_id'],
            'refund_status'   => $order['refund']['refund_status'],
            'refund_sid'      => $trade['seller_id'],
            'refund_tid'      => $trade['tid'],
            'refund_reason'   => $_POST['refund_reason'],
            'refund_quantity' => intval($_POST['refund_quantity']),
            'refund_fee'      => floatval($_POST['refund_fee']),
            'refund_post'     => floatval($_POST['refund_post']),
            'refund_sremark'  => $_POST['refund_sremark'],
            'receiver_name'   => $_POST['receiver_name'],
            'receiver_mobile' => $_POST['receiver_mobile'],
            'receiver_address'=> $_POST['receiver_address'],
            'refund_type'     => $_POST['refund_type'],
            'refund_uid'      => session("manager.username"),
            'refund_modify'   => NOW_TIME
        );

        // 运费金额
        $maxPostage = floatval(bcadd($trade['total_postage'], 15, 2));
        if($refund['refund_post'] < 0 || $refund['refund_post'] > $maxPostage){
            $this->error('邮费应在 0 - '.$maxPostage.'元之间');
        }
        // 退款数量
        // else if($refund['refund_quantity'] < 1 || $refund['refund_quantity'] > $order['quantity']){
        //     $this->error('退款数量应在 1 - '.$order['quantity'].'之间');
        // }
        // 退款金额
        $maxRefundFee = floatval($order['payment']);
        if($refund['refund_fee'] < 0 || $refund['refund_fee'] > $maxRefundFee){
            $this->error('退款金额应在 0 - '.$maxRefundFee.'元之间');
        }

        ignore_user_abort(true);
        set_time_limit(180);
        
        $Model->startTrans();
        switch ($_POST['action']){
            case 'agree':
                $refund = $this->agree($Model, $trade, $refund);
                break;
            case 'refuse':
                $refund = $this->refuse($Model, $trade, $refund);
                break;
            case 'advance':
                $refund = $this->advance($Model, $trade, $refund);
                break;
            case 'refundNow':
                $refund = $this->refundNow($Model, $trade, $refund);
                break;
            case 'add':
                $refund = $this->add($Model, $trade, $refund);
                break;
            case 'cancel':
                $refund = $this->cancel($Model, $order, $refund);
                break;
            default:
                $this->error('未知处理操作');
                break;
        }
        
        // 更新订单退款状态
        $this->updateTradeRefundStatus($Model, $trade);
        $Model->commit();
        
        // 给客户发送消息通知
        $buyer = $trade['buyer'];
        if($buyer['subscribe']){
            $data = array(
                'url'       => $buyer['url'].'/order/detail?tid='.$trade['tid'].'&refund_id='.$refund['refund_id'],
                'title'     => '您好，您的退款有了新的变化！',
                'tid'       => $trade['tid'],
                'progress'  => RefundStatus::getOrderStatus($refund['refund_status']), // 状态
                'goods'     => $order['title'].$order['spec'],
                'refund_fee'=> bcadd($refund['refund_fee'], $refund['refund_post'], 2), // 退款金额
                'remark'    => '点击查看详情。'
            );
            
            $project = $trade['project'];
            switch ($refund['refund_status']){
                case RefundStatus::WAIT_EXPRESS_NO:
                    $data['title']  = '卖家已同意您的退款申请！';
                    $data['remark'] = '退货地址：'.$refund['receiver_name'].' '.$refund['receiver_mobile'].' '.$refund['receiver_address'].'。'.$refund['refund_sremark'];
                    break;
                case RefundStatus::REFUSED_REFUND:
                    $data['title']  = '您好，卖家拒绝了您的退款申请！';
                    $data['remark'] = '拒绝原因：'.$refund['refund_sremark'];
                    break;
                case RefundStatus::REFUNDED:
                    $data['title']  = '退款已完成！';
                    $data['remark'] = '退款提现：资金会按原支付途径返还。如果您使用了微信支付请留意微信退款通知，如果您使用了'.$project['balance_alias'].' 或 '.$project['wallet_alias'].'请到本公众号个人中心查看或提现(“'.$project['score_alias'].'”不在退款范围内)';
                    break;
                case RefundStatus::CANCEL_REFUND:
                    $data['title']   = '您好，卖家已替您取消退款申请！';
                    $data['remark']  = '系统提醒：如有异议请点击详情咨询卖家。';
                    $data['remark'] .= $refund['refund_sremark'];
                    break;
            }
            
            $Model->lPublish('MessageNotify', array(
                'type'   => MessageType::REFUND,
                'openid' => $buyer['openid'],
                'appid'  => $buyer['appid'],
                'data'   => $data
            ));
        }
        
        $Model->lPublish('TradeCopy', $trade['tid']);
        $this->success();
    }
    
    /**
     * 更新退款信息
     */
    private function update($Model, $refund){
        $sql = "UPDATE trade_refund SET ";
        foreach ($refund as $field=>$val){
            $sql .= "`{$field}`='".addslashes($val)."',";
        }
        $sql = rtrim($sql, ',')." WHERE refund_id=".$refund['refund_id'];
        $Model->execute($sql);
        
        return $refund;
    }
    
    /**
     * 同意退款
     */
    private function agree($Model, $trade, $refund){
        if($refund['refund_status'] != RefundStatus::APPLYING){
            $this->error('操作失败：退款状态已变更为'.RefundStatus::getOrderStatus($refund['refund_status']));
        }
        
        // 立即退款
        if($refund['refund_type'] == 1){
            $refund['refund_status'] = RefundStatus::WAIT_REFUND;
            return $this->refundNow($Model, $trade, $refund);
        }
        
        $refund['refund_status'] = RefundStatus::WAIT_EXPRESS_NO;
        return $this->update($Model, $refund);
    }
    
    /**
     * 拒绝退款
     */
    private function refuse($Model, $trade, $refund){
        if($refund['refund_status'] != RefundStatus::APPLYING){
            $this->error('操作失败：退款状态已变更为'.RefundStatus::getOrderStatus($refund['refund_status']));
        }
        
        $refund['refund_status'] = RefundStatus::REFUSED_REFUND;
        return $this->update($Model, $refund);
    }

    /**
     * 立即退款
     */
    private function refundNow($Model, $trade, $refund){
        if($refund['refund_status'] != RefundStatus::WAIT_REFUND){
            $this->error('操作失败：退款状态已变更为'.RefundStatus::getOrderStatus($refund['refund_status']));
        }

        // 计算上次退款
        $paidList = array('wallet' => floatval($trade['paid_wallet']), 'weixin' => floatval($trade['paid_fee']), 'balance' => floatval($trade['balance']));
        $prev = $this->getNeedRefund($trade['refunded_fee'], $paidList);

        // 上次退款后，还剩多少可退
        $now = array(
            'wallet'  => floatval(bcsub($paidList['wallet'], $prev['wallet'], 2)),
            'weixin'  => floatval(bcsub($paidList['weixin'], $prev['weixin'], 2)),
            'balance' => floatval(bcsub($paidList['balance'], $prev['balance'], 2))
        );

        // 本次需要退款明细
        $needRefund = bcadd($refund['refund_fee'], $refund['refund_post'], 2);
        $needRefund =  $this->getNeedRefund($needRefund, $now);

        // 判断此店铺账户余额是否充足
        $ketixian = bcadd($needRefund['balance'], $needRefund['weixin'], 2);
        $ketixian = floatval($ketixian);
        $surplus  = floatval($needRefund['surplus']); // 超退
        if($surplus > 0 || ($trade['status'] == OrderStatus::SUCCESS && $ketixian > 0)){
            $shop = $Model->query("SELECT balance FROM shop WHERE id='{$trade['seller_id']}'");
            $shopBalance = floatval($shop[0]['balance']);

            $money = $trade['status'] == OrderStatus::SUCCESS && $ketixian > 0 ? $ketixian : $surplus;
            if($shopBalance < $money){
                $this->error("店铺可用余额不足，请充值后再退款");
            }
        }
        
        $timestamp = time();
        $clientIp = get_client_ip();
        $ShopBalance = new \Common\Model\ShopBalanceModel();
        
        // 退第三方支付
        if($needRefund['weixin'] > 0){
            $minOuterTid = IdWork::getMinOutTid($trade['created']);
            $pay = M()->query("SELECT tid, type, transaction_id, total_fee, settlement_fee,  openid, appid, mch_id, device_info FROM trade_pay_create WHERE tid>'{$minOuterTid}' AND transaction_id='{$trade['transaction_id']}' LIMIT 1");
            if(empty($pay)){
                $this->error('微信支付数据丢失，无法发起退款');
            }
            $pay = $pay[0];

            // 调用微信接口
            $wxPayRefund = new WxPayRefund();
            $wxPayRefund->SetAppid($pay['appid']);
            $wxPayRefund->SetMch_id($pay['mch_id']);
            $wxPayRefund->SetTransaction_id($pay['transaction_id']);
            $wxPayRefund->SetOut_refund_no('RD'.$refund['refund_id']);
            $wxPayRefund->SetDevice_info($pay['device_info']);
            $wxPayRefund->SetTotal_fee(bcmul($pay['total_fee'], 100));
            $wxPayRefund->SetRefund_fee(bcmul($needRefund['weixin'], 100));
            $wxPayRefund->SetOp_user_id($pay['mch_id']);
            $result = WxPayApi::refund($wxPayRefund);
            if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
                $sql = "INSERT INTO trade_pay_refund SET
                        refund_id='{$refund['refund_id']}',
                        type={$pay['type']},
                        created={$timestamp},
                        transaction_id='{$result['transaction_id']}',
                        pay_create_id='{$result['out_trade_no']}',
                        appid='{$result['appid']}',
                        mch_id='{$result['mch_id']}',
                        client_ip='{$clientIp}',
                        out_refund_id='{$result['refund_id']}',
                        refund_fee={$needRefund['weixin']},
                        settlement_refund_fee=".bcdiv($result['settlement_refund_fee'], 100, 2);
                $Model->excute($sql);

                // 已结算订单退回手续费
                if($trade['status'] == OrderStatus::SUCCESS){
                    $poundage = bcmul($needRefund['weixin'], 0.006, 4);
                    $ShopBalance->add(array(
                        'shop_id'  => $trade['seller_id'],
                        'balance'  => sprintf("%.2f", $poundage),
                        'type'     => BalanceType::POUNDAGE,
                        'reason'   => '微信手续费退回-'.$trade['tid'],
                        'username' => $refund['refund_uid']
                    ));
                }
            }else{
                $this->error('微信退款失败：'.($result['err_code_des'] ? $result['err_code_des'] : $result['return_msg']));
            }
        }

        // 增加用户账户余额
        if($needRefund['balance'] > 0 || $needRefund['wallet'] > 0){
            $Balance = new \Common\Model\BalanceModel();
            $Balance->add(array(
                'project_id' => $trade['project']['id'],
                'mid'        => $trade['buyer_id'],
                'type'       => BalanceType::ORDER_REFUND,
                'reason'     => '订单退款-'.$trade['tid'],
                'balance'    => $needRefund['balance'],
                'wallet'     => $needRefund['wallet']
            ), true);
        }

        if($ketixian > 0){ // 扣除店铺账户余额
            if($trade['status'] == OrderStatus::SUCCESS){
                $ShopBalance->add(array(
                    'shop_id'  => $trade['seller_id'],
                    'balance'  => -$ketixian,
                    'type'     => BalanceType::ORDER_REFUND,
                    'reason'   => '退款订单已结算资金扣回-'.$trade['tid'],
                    'username' => $refund['refund_uid']
                ));
            }else if($surplus > 0){// 多退部分扣除
                $ShopBalance->add(array(
                    'shop_id'  => $trade['seller_id'],
                    'balance'  => -$surplus,
                    'type'     => BalanceType::ORDER_REFUND,
                    'reason'   => '订单多退款-'.$trade['tid'],
                    'username' => $refund['refund_uid']
                ));
            }
        }

        // 扣回佣金(只减用户资金，不增加店铺可用余额)
        foreach ($trade['orders'] as $order){
            if($order['oid'] == $refund['refund_id']){
                $this->deductedCommision($trade, $order['commision'], floatval($ketixian) >= floatval($order));
                break;
            }
        }
        $refund['refund_status'] = RefundStatus::REFUNDED;
        return $this->update($Model, $refund);
    }

    /**
     * 扣回佣金
     */
    private function deductedCommision($trade, $commisions, $closed){
        if(!$commisions){
            return;
        }

        $projectId = IdWork::getProjectId($trade['seller_id']);
        $Balance = new \Common\Model\BalanceModel();
        $timestamp = time();
        foreach ($commisions as $item){
            // 扣回佣金
            if(floatval($item['settlement_balance']) > 0 || floatval($item['settlement_wallet']) > 0 || floatval($item['settlement_score']) > 0){
                $Balance->add(array(
                    'project_id' => $projectId,
                    'mid'        => $item['mid'],
                    'type'       => BalanceType::COMMISION_DEDUCTED,
                    'reason'     => '推广订单已退款佣金扣回-'.$trade['tid'],
                    'balance'    => -$item['settlement_balance'],
                    'wallet'     => -$item['settlement_wallet'],
                    'score'      => -$item['settlement_score']
                ), true);

                $sql = "UPDATE trade_commision SET
                        settlement_time=IF(settlement_time=0, {$timestamp}, settlement_time),
                        deducted_time={$timestamp},
                        deducted_balance=-settlement_balance,
                        deducted_wallet=-settlement_wallet,
                        deducted_score=-settlement_score
                        WHERE oid='{$item['oid']}' AND mid='{$item['mid']}'";
                $Balance->execute($sql);
            }else if($closed && $item['settlement_time'] == 0){
                $sql = "UPDATE trade_commision SET
                        settlement_time={$timestamp}
                        WHERE oid='{$item['oid']}' AND mid='{$item['mid']}' AND settlement_time=0";
                $Balance->execute($sql);
            }
        }
    }


    /**
     * 根据订单号获取订单信息
     */
    private function getTradeByTid(&$Model, $tid){
        if(!IdWork::isSystemTid($tid)){
            $this->error('订单号错误');
        }
        
        $Model = new OrderModel();
        $trade = $Model->getTradeByTid($tid);
        if(!$trade){
            $this->error('订单不存在');
        }else if($trade['status'] == OrderStatus::WAIT_PAY){
            $this->error('待付款订单请直接取消订单');
        }
        return $trade;
    }

    /**
     * 获取店铺退款的配置文件
     */
    private function getShopRefund($Model, $shopId){
        // 查找店铺退货地址
        $data = $Model->query("SELECT * FROM shop_refund WHERE id=".$shopId);
        if($data){
            $data = $data[0];
            $data = array(
                'receiver_name'    => $data['receiver_name'],
                'receiver_mobile'  => $data['receiver_mobile'],
                'receiver_address' => $data['receiver_province'].' '.$data['receiver_city'].' '.$data['receiver_county'].' '.$data['receiver_detail'],
                'admin_max_day'    => $data['admin_max_day'],
                'message_mid'      => $data['message_mid']
            );
        }else{
            $data = array('receiver_name' => '', 'receiver_mobile' => '', 'receiver_address' => '', 'admin_max_day' => 60, 'message_mid' => '');
        }
        
        return $data;
    }

    /**
     * 根据支付部分计算应退款多少钱(请勿修改此公式！否则商家金额可能出现混乱)
     * 优先级别wallet、weixin、balance
     */
    private function getNeedRefund($refundedFee, $paidList){
        $refundedFee = floatval($refundedFee);

        $sort = array('wallet', 'weixin', 'balance');
        $prev = array('wallet' => 0, 'weixin' => 0, 'balance' => 0, 'surplus' => 0);
        if($refundedFee > 0){
            $field = $sort[0];
            $prev[$field] = $refundedFee >= $paidList[$field] ? $paidList[$field] : $refundedFee;
            $refundedFee = floatval(bcsub($refundedFee, $prev[$field], 2));

            if($refundedFee > 0){
                $field = $sort[1];
                $prev[$field] = $refundedFee >= $paidList[$field] ? $paidList[$field] : $refundedFee;
                $refundedFee = floatval(bcsub($refundedFee, $prev[$field], 2));

                if($refundedFee > 0){
                    $field = $sort[2];
                    $prev[$field] = $refundedFee >= $paidList[$field] ? $paidList[$field] : $refundedFee;
                    $refundedFee = floatval(bcsub($refundedFee, $prev[$field], 2));

                    if($refundedFee > 0){
                        $surplus = floatval(bcadd($prev['balance'], $refundedFee, 2));
                        $prev['balance'] = $surplus;
                        $prev['surplus'] = $surplus;
                    }
                }
            }
        }

        return $prev;
    }

    /**
     * 取消退款
     */
    private function cancel($Model, $trade, $refund){
        if($refund['refund_status'] != RefundStatus::WAIT_EXPRESS_NO && RefundStatus::WAIT_REFUND){
            $this->error('操作失败：退款状态已变更为'.RefundStatus::getOrderStatus($refund['refund_status']));
        }
        
        $refund['reset_times'] = 1;
        $refund['refund_status'] = RefundStatus::CANCEL_REFUND;
        return $this->update($Model, $refund);
    }
}
?>