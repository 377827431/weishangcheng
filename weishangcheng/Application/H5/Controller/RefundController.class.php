<?php
namespace H5\Controller;

use Common\Common\CommonController;
use Common\Model\OrderStatus;
use Common\Model\RefundStatus;
use Common\Model\RefundReason;
use Common\Model\OrderType;
use Common\Model\BaseModel;
use Org\IdWork;
use Common\Model\MessageType;
use Think\Cache\Driver\Redis;

/**
 * 订单退款
 * @author 兰学宝
 *
 */
class RefundController extends CommonController
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
                    refund.receiver_address, refund.receiver_mobile, refund.receiver_name, trade.buyer_nick
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
        $userId = $this->user("id");
        $oid = $_GET['refund_id'];
        $data = $this->getRefundById($oid);

        $label = array(
            'is_received' => array(0 => '我还没有收到货物', 1 => '我已收到货物'),
            'refund_type' => array(0 => '我要退货退款', 1 => '我希望仅退款', 2 => '我要换货')
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
        
        // 删除七天无理由
        if(!in_array(10, $data['special'])){
            unset($wsdwp[10]);
            unset($ysdwp[10]);
        }
        $this->assign('wsdwp', json_encode($wsdwp));
        $this->assign('ysdwp', json_encode($ysdwp));
        $this->assign('data', $view);

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
        $userId = $this->user("id");
        $refund = array(
            'refund_id'     => $_POST['refund_id'],
            'is_received'   => $_POST['is_received'],
            'refund_fee'    => floatval($_POST['refund_fee']),
            'refund_remark' => trim($_POST['refund_remark']),
            'refund_reason' => $_POST['refund_reason'],
            'refund_type'   => $_POST['refund_type'],
            'refund_images' => encode_json($_POST['refund_images'])?encode_json($_POST['refund_images']):"",
            'refund_modify' => NOW_TIME,
            'refund_status' => RefundStatus::APPLYING
        );
        
        $data = $this->getRefundById($refund['refund_id']);
        if($userId != $data['buyer_id']){
            $this->error('操作无效：下单人与登陆人不符');
        }if($data['refund_status'] && $data['refund_status'] != RefundStatus::APPLYING){
            $this->error('操作无效：退款状态已变更');
        }if($refund['refund_fee'] > floatval($data['payment'])){
            $this->error('最多可申请'.$data['payment'].'元');
        }
        
        // 计算退款数量
        $refundNum      = 1;
        $onePayment     = bcdiv($data['payment'], $data['quantity'], 4);
        if(floatval($onePayment) > 0){
            $refundNum  = bcdiv($data['refund_fee'], $onePayment, 2);
            $refundNum  = ceil($refundNum);
        }else{
            $refundNum  = $data['quantity'];
        }
        $refund['refund_quantity'] = $refundNum > $data['quantity'] ? $data['quantity'] : $refundNum;
        
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

        // 后台退款提醒
        $this->add_refund_reminder($data);
        
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
        $sql = "UPDATE trade SET refund_status={$refundStatus}, modified={$timestamp} WHERE tid='{$trade['tid']}'";
        $Model->execute($sql);
        
        // 通知拷贝订单
        $redis = new Redis();
        $redis->lPublish('TradeCopy', $trade['tid']);
        $redis->close();
    }
    
    /**
     * 取消申请
     */
    public function cancel(){
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
    }

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
        }if($data['refund_status'] != RefundStatus::WAIT_EXPRESS_NO && $data['refund_status'] != RefundStatus::WAIT_REFUND){
            $this->error('操作无效：退款状态已变更');
        }
        
        $Model = M('trade_refund');
        $Model->where("refund_id=".$data['refund_id'])->save(array(
            'refund_status' => RefundStatus::WAIT_REFUND,
            'refund_modify' => NOW_TIME,
            'refund_express'=> $refund_express
        ));

        // 后台退款提醒
        $this->add_refund_reminder($data);
        
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

    /**
     * 添加后台退款提醒
     */
    public function add_refund_reminder($data){
        $add_refund = array(
            'shop_id' => $data['seller_id'],
            'name' => $data['buyer_nick'],
            'tid' => $data['tid'],
            'type' => \Common\Model\OrderReminderType::REFUND,
            'created' => time(),
            'status' => 2,
        );
        M('trade_reminder')->add($add_refund);
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
}
?>