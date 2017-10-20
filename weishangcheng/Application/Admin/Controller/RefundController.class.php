<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Common\Model\OrderModel;
use Org\IdWork;
use Common\Model\RefundReason;
use Common\Model\OrderStatus;
use Common\Common\Auth;
use Common\Model\RefundStatus;
use Org\WxPay\WxPayRefund;
use Org\WxPay\WxPayApi;
use Common\Model\BalanceModel;
use Common\Model\BalanceType;
use Common\Model\TradeEndType;
use Common\Model\MessageType;
use Common\Model\ShopBalanceModel;

/**
 * 退款
 * @author 兰学宝
 */
class RefundController extends CommonController
{
    private $model = null;
    private $authAllShop = false;
    private $shopId;
    
    public function __construct(){
        parent::__construct();

        $login = $this->user();
        $this->shopId = $login['shop_id'];
        $this->authAllShop = Auth::get()->validated('admin','shop','all');
    }
    
    private function refundModel(){
        if(is_null($this->model)){
            // 判断是否具有全部店铺的权限
            $sellerId = \Common\Common\Auth::get()->validated('admin','shop','all');
            if($sellerId === false){
                $sellerId = $this->user('shop_id');
            }
            $this->model = new \Admin\Model\RefundModel($sellerId);
        }
        return $this->model;
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
        }else if(!$this->authAllShop && $trade['shop_id'] != $this->shopId){
            $this->error('您无权操作此订单');
        }else if(IdWork::getProjectId($trade['seller_id']) != $this->projectId){
            $this->error('您无权操作此订单');
        }else if($trade['status'] == OrderStatus::WAIT_PAY){
            $this->error('待付款订单请直接取消订单');
        }
        return $trade;
    }
    
    /**
     * 列表
     */
    public function index(){
        $Model = $this->refundModel();
        $date1 = date_create($_GET['start_date']);
        $date2 = date_create($_GET['end_date']);
        $diff = date_diff($date1,$date2);
        $date = str_replace('+','',$diff->format("%R%a"));
        if($date>31){
            $this->error('只能查询一个月以内的');
        }
        if($_GET['date_range']){
            $search['start_time'] = substr($_GET['date_range'], 0, 16).':00';
            $search['end_time']   = substr($_GET['date_range'], 19).':59';
        }else{
            $search['start_time'] = date('Y-m-d 00:00:00', strtotime('-3 day'));
            $search['end_time']   = date('Y-m-d H:i:s');
        }
        if(!IS_AJAX){
            // 判断是否具有全部店铺的权限
            $accessAllShop = \Common\Common\Auth::get()->validated('admin','shop','all');
            $allShop = $this->shops();
            $this->assign(array(
                'start_date' => date('Y-m-d 00:00:00', strtotime('-30 day')),
                'end_date'   => date('Y-m-d 23:59:59'),
                'allShop'    => $allShop,
                'search'     => $search
            ));
            $this->display();
        }
        $myProjectId = $this->user('project_id');
        $data = $Model->getAll($paging=0,$myProjectId);
        $this->ajaxReturn($data);
    }
    
    /**
     * 导出
     */
    public function export(){
        $Model = $this->refundModel();
        $data = $Model->getAll($paging=1);
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        // 读取工作表
        $worksheet = $objPHPExcel->setActiveSheetIndex(0);
        $worksheet->setTitle('售后退款');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
        // 退款状态、原因
        $RefundStatus = new RefundStatus();
        $allState = $RefundStatus->getTradeStatus();
        foreach($data['rows'] as $key => $item){
           $data['rows'][$key]['refund_status'] = $allState[$item['refund_status']];
        }
        $searchStr = "查询时间段：".$_GET["date_range"];
        if(is_numeric($_GET["tid"])){
            $searchStr.= "\r\n订单号：".$_GET["tid"];
        }
        if(is_numeric($_GET["refund_state"])){
            $searchStr.= "\r\n退款状态：".$data['rows'][$key]['refund_status'];
        }
        $worksheet->setCellValueExplicit('A1', $searchStr);
        $worksheet->getRowDimension(1)->setRowHeight(40);
         
        $i=2;  
        $worksheet
        ->setCellValue('A'.$i, '订单号')
        ->setCellValue('B'.$i, '申请时间')
        ->setCellValue('C'.$i, '卖家')
        ->setCellValue('D'.$i, '商品ID')
        ->setCellValue('E'.$i, '商品名称')
        ->setCellValue('F'.$i, '退款数量')
        ->setCellValue('G'.$i, '退款金额')
        ->setCellValue('H'.$i, '运费补偿')
        ->setCellValue('I'.$i, '快递单号')
        ->setCellValue('J'.$i, '退款原因')
        ->setCellValue('K'.$i, '退款状态')
        ->setCellValue('L'.$i, '买家ID');
    
        
        $sum = 0;
        foreach($data['rows'] as $k=>$v){
            $i++;
            $worksheet
            ->setCellValueExplicit('A'.$i, $v['tid'])
            ->setCellValueExplicit('B'.$i, $v['refund_created'])
            ->setCellValueExplicit('C'.$i, $v['seller_name'])
            ->setCellValueExplicit('D'.$i, $v['goods_id'])
            ->setCellValueExplicit('E'.$i, $v['title']."  ".$v['spec'])
            ->setCellValueExplicit('F'.$i, $v['refund_quantity'])
            ->setCellValueExplicit('G'.$i, $v['refund_fee'])
            ->setCellValueExplicit('H'.$i, $v['refund_post'])
            ->setCellValueExplicit('I'.$i, $v['refund_express'])
            ->setCellValueExplicit('J'.$i, $v['refund_reason_str']['title'])
            ->setCellValueExplicit('K'.$i, $v['refund_status']['title'])
            ->setCellValueExplicit('L'.$i, $v['buyer_id']);
           
        }
        
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $text = iconv('UTF-8', 'GB2312', '售后退款');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
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
     * 退款详情
     */
    public function detail(){
        $tid = $_GET['tid'];
        if(!IdWork::isSystemTid($tid)){
            $this->error('订单号错误');
        }
        
        $Model = null;
        $trade = $this->getTradeByTid($Model, $tid);
        
        $shopRefund = $this->getShopRefund($Model, $trade['seller_id']);

        $status = $trade['status'];
        $refundType = ($status == OrderStatus::WAIT_SEND_GOODS || $status == OrderStatus::OUT_STOCK) ? 1 : 0;
        foreach ($trade['orders'] as $i=>$order){
            $refund = $order['refund'];
            if($refund['refund_status'] < 12){
                // 0退货退款；1退款；2退货；3换货
                $refund['refund_type'] = $refundType;
                $trade['orders'][$i]['refund'] = array_merge($refund, $shopRefund);
            }
        }
        
        $maxPostage = bcadd($trade['total_postage'], 15, 2);
        $this->assign(array(
            'trade'       => $trade,
            'reason'      => RefundReason::getAll($trade['status']),
            'max_postage' => $maxPostage
        ));
        $this->display();
    }
    
    /**
     * 处理退款
     */
    public function handle(){
        $prevTime = session('prev_refund_time');
        if(!APP_DEBUG && $prevTime && NOW_TIME - $prevTime < 60){
            $this->error('操作频繁，请'.(60 - (NOW_TIME - $prevTime)).'秒后再试');
        }
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
        }else if($_POST['refund_type'] == 0 && empty($_POST['receiver_name']) || strlen($_POST['receiver_mobile']) != 11 || strlen($_POST['receiver_address']) < 10){
            $this->error('请补全退货地址');
        }
        
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
            'refund_uid'      => $this->user('username'),
            'refund_modify'   => NOW_TIME
        );

        // 运费金额
        $maxPostage = floatval(bcadd($trade['total_postage'], 15, 2));
        if($refund['refund_post'] < 0 || $refund['refund_post'] > $maxPostage){
            $this->error('邮费应在 0 - '.$maxPostage.'元之间');
        }
        // 退款数量
        else if($refund['refund_quantity'] < 1 || $refund['refund_quantity'] > $order['quantity']){
            $this->error('退款数量应在 1 - '.$order['quantity'].'之间');
        }
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
        $Model->commit();
        
        // 更新订单退款状态
        $this->updateTradeRefundStatus($Model, $trade);
        
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
                    $data['remark'] = '退款提现：资金会按原支付途径返还。如果您使用了微信支付请留意微信退款通知，如果您使用了'.$project['balance_alias'].'或'.$project['wallet_alias'].'请到本公众号个人中心查看或提现('.$project['score_alias'].'不退)';
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
     * 更新订单退款状态
     */
    private function updateTradeRefundStatus($Model, $trade){
        // 此单用户支付总额
        $paidTotal = floatval($trade['paid_total']);
        
        $oids = '';
        foreach ($trade['orders'] as $order){
            $oids .= $order['oid'].',';
        }
        $oids = rtrim($oids, ',');
        
        // 查找最新的退款信息
        $list = $Model->query("SELECT refund_status, (refund_fee + refund_post) AS refunded_fee FROM trade_refund WHERE refund_id IN ($oids) GROUP BY refund_status");
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
        $sql = "UPDATE trade SET refund_status={$refundStatus}, refunded_fee={$refundArray['refunded']}, modified=".$timestamp;
        if($refundArray['doing'] == '0.00' && $amount >= $paidTotal){
            $sql .= ", `status`=".OrderStatus::BUYER_CANCEL.", end_time={$timestamp}, end_type=".TradeEndType::REFUND;
            
            // 关闭佣金结算
            $Model->execute("UPDATE trade_commision SET settlement_time={$timestamp} WHERE oid IN ({$oids}) AND settlement_time=0");
        }
        $sql .= " WHERE tid='{$trade['tid']}'";
        $Model->execute($sql);
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
     * 添加退款
     */
    private function add($Model, $trade, $refund){
        if($refund['refund_status'] > 1){
            $this->error('操作失败：退款状态已变更为'.RefundStatus::getOrderStatus($refund['refund_status']));
        }
        
        $refund['refund_created'] = NOW_TIME;
        if($refund['refund_type'] != 1){
            $refund['refund_status'] = RefundStatus::WAIT_EXPRESS_NO;
        }
        
        $sql = "INSERT INTO trade_refund SET ";
        foreach ($refund as $field=>$val){
            $sql .= "`{$field}`='".addslashes($val)."',";
        }
        $Model->execute(rtrim($sql, ','));
        
        // 立即退款
        if($refund['refund_type'] == 1){
            $refund['refund_status'] = RefundStatus::WAIT_REFUND;
            return $this->refundNow($Model, $trade, $refund);
        }
        return $refund;
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

    /**
     * 提前退款
     */
    private function advance($Model, $trade, $refund){
        if($refund['refund_status'] != RefundStatus::WAIT_EXPRESS_NO && RefundStatus::WAIT_REFUND){
            $this->error('操作失败：退款状态已变更为'.RefundStatus::getOrderStatus($refund['refund_status']));
        }
        
        $refund['refund_status'] = RefundStatus::WAIT_REFUND;
        return $this->refundNow($Model, $trade, $refund);
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
        $ShopBalance = new ShopBalanceModel();
        
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
                $Model->execute($sql);

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
            $Balance = new BalanceModel();
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
        $Balance = new BalanceModel();
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
}
?>