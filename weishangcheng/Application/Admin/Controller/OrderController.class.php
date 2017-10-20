<?php
namespace Admin\Controller;
use Common\Common\CommonController;
use Common\Model\OrderStatus;
use Common\Model\RefundStatus;
use Org\IdWork;
use Common\Model\OrderModel;
use Common\Common\Auth;
use Common\Model\TradeEndType;

/**
 * 订单管理
 * @author lanxuebao
 *
 */
class OrderController extends CommonController
{
    private $authAllShop = false;
    private $shopId;
    
    function __construct(){
        parent::__construct();
        
        $login = $this->user();
        $this->projectId = $login['project_id'];
        $this->shopId = $login['shop_id'];
        $this->authAllShop = Auth::get()->validated('admin','shop','all');
    }
    
    /**
     * 我的订单
     */
    public function index(){
        $search = array(
            'shop_id'      => is_numeric($_GET['shop_id']) ? $_GET['shop_id'] : $this->shopId,
        	'kw'           => trim($_GET['kw']),
            'start_time'   => '',
            'end_time'     => '',
        	'status'       => !isset($_GET['status']) ? null : $_GET['status'],
            'buyer_id'     => is_numeric($_GET['buyer_id']) ? $_GET['buyer_id'] : '',
            'buyer_mobile' => is_numeric($_GET['buyer_mobile']) ? $_GET['buyer_mobile'] : '',
        	'type'         => is_numeric($_GET['type']) ? $_GET['type'] : 0
        );
        
        if($_GET['date_range']){
            $search['start_time'] = substr($_GET['date_range'], 0, 16).':00';
            $search['end_time']   = substr($_GET['date_range'], 19).':59';
        }else{
            $search['start_time'] = date('Y-m-d 00:00:00', strtotime('-3 day'));
            $search['end_time']   = date('Y-m-d').' 23:59:59';
        }

        // 显示数据
        if(IS_AJAX){
            return $this->getOrderList($search);
        }
        
        $orderStatus = OrderStatus::getAll();
        $this->assign(array(
            'order_status'       => $orderStatus,
            'show_out_stock_btn' => Auth::get()->validated('admin','order','out_stock'),
            "search"             => $search
        ));
        $this->display('index');
    }
    
    /**
     * 显示订单列表数据
     * @param array $search
     */
    private function getOrderList($search){
        if(is_numeric($search['shop_id'])){
            if(IdWork::getProjectId($search['shop_id']) != $this->projectId || (!$this->authAllShop && $search['shop_id'] != $this->shopId)){
                $this->error('您无权限查看其它店铺订单');
            }
        }

        $Model = new OrderModel();
        $where = array();
        if($search['shop_id'] == 'all'){
            $where[] = "trade_seller.seller_id BETWEEN {$this->projectId}000 AND {$this->projectId}999";
        }else{
            $where[] = "trade_seller.seller_id=".$search['shop_id'];
        }
        
        // 搜索订单号
        if(is_numeric($search['kw']) && IdWork::isSystemTid($search['kw'])){
        	$where[] = "trade_seller.tid=".$search['kw'];
        }else{ // 根据下单时间获取订单号范围
        	if(strlen($search['kw']) > 1){
        		$where[] = "MATCH(trade_search.kw, trade_search.other) AGAINST ('".addslashes($search['kw'])."' IN BOOLEAN MODE)";
        	}
        	
            $startTime = strtotime($search['start_time']);
            $endTime   = strtotime($search['end_time']);
            $subTime   = $endTime - $startTime;
            if($subTime < 0){
                $this->error('日期格式错误');
            }else if($subTime > 2678400){
                $this->error('查询时间段不能超过31天');
            }
            $range     = IdWork::getTidRange($startTime, $endTime);
            $where[]   = "trade_seller.tid BETWEEN {$range[0]} AND {$range[1]}";
        }
        
        // 订单状态
        if(is_numeric($search['status'])){
            $where[] = "trade_seller.`status`=".$search['status'];
        }else if($search['status'] == 'refunding'){ // 退款中
            $where[] = "trade_seller.refund_status IN (1,4)";
        }
        
        // 买家
        if($search['buyer_id'] != ''){
            $where[] = "trade_seller.buyer_id='{$search['buyer_id']}'";
        }else if($search['buyer_mobile'] != ''){
            $mids = $Model->query("SELECT GROUP_CONCAT(id) AS mid FROM member WHERE mobile='{$search['buyer_mobile']}'");
            $mids = $mids[0]['mid'];
            if(!$mids){
                $this->error('会员手机号不存在');
            }else if(is_numeric($mids)){
                $where[] = "trade_seller.buyer_id='{$mids}'";
            }else{
                $where[] = "trade_seller.buyer_id IN ({$mids})";
            }
        }
        
        // 订单类型
        if($search['type'] > 0){
        	$where[] = "FIND_IN_SET({$search['type']}, trade_seller.type)";
        }
        
        $rows = $Model->getTradeBySellerCount($where);
        if($rows > 0){
            $page = intval($_GET['page']);
            $page = $page > 0 ? $page : 1;
            $size = 10;
            
            $this->assign(array('rows' => $rows, 'page' => $page, 'size' => $size));
            
            $list = $Model->getTradeBySeller($where, ($page - 1 ) * $size, $size);
            $this->assign('list', $list);
            
            $adjust = Auth::get()->validated('admin','order','adjust');
            $this->assign('auth_adjust', $adjust);
        }
        $projectId = substr($this->shopId, 0, -3);
        $project = get_project($projectId);
        $this->assign('p_url', $project);
        $this->assign('total_rows', $rows);
        $this->display('list');
    }
    
    /**
     * 根据订单号获取订单信息
     */
    private function getTradeByTid($Model, $tid){
        $trade = $Model->getTradeByTid($tid);
        if(!$trade){
            $this->error('订单不存在');
        }/*else if(!$this->authAllShop && $trade['shop_id'] != $this->shopId){
            $this->error('您无权操作此订单');
        }*/else if(IdWork::getProjectId($trade['seller_id']) != $this->projectId){
            $this->error('您无权操作此订单');
        }
        
        return $trade;
    }
    
    /**
     * 订单详细
     */
    public function detail(){
        $order_no = $_GET['tid'];//订单号
        $Model = new \Common\Model\OrderModel();
        $trade = $Model->getTradeByTid($order_no);
        if ( $trade['type'] )
        {
            $ali_trade = M('alibaba_trade')->where(array('tid'=>$trade['tid']))->select();
            $this->assign('ali_trade', $ali_trade);
        }
        $refunded_desc = '无退款';
//        $refund = array('no_refund' ,'partial_refunding','partial_refunded','partial_failed','full_refunding','full_refunded','full_failed');
//        $trade['refund_status'] = $refund[$trade['refund_status']];
        if($trade['refund_status'] != 'no_refund'){
            switch ($trade['refund_status']){
                case RefundStatus::PARTIAL_REFUNDED:
                    $refunded_desc = '已部分退款<br>'.sprintf('%.2f', $trade['refunded_fee']);
                    break;
                case RefundStatus::PARTIAL_REFUNDING:
                    $refunded_desc = '部分退款中'.($trade['refunded_fee'] > 0 ? '<br>已退款'.$trade['refunded_fee'].'元' : '');
                    break;
                case RefundStatus::FULL_REFUNDED:
                    $refunded_desc = '已全额退款&nbsp;'.$trade['refunded_fee'];
                    break;
                case RefundStatus::FULL_REFUNDING:
                    $refunded_desc = '全额退款中'.($trade['refunded_fee'] > 0 ? '<br>已退款'.$trade['refunded_fee'].'元' : '');
                    break;
                case RefundStatus::PARTIAL_FAILED:
                    $refunded_desc = '部分退款失败'.($trade['refunded_fee'] > 0 ? '<br>已退款'.$trade['refunded_fee'].'元' : '');
                    break;
                case RefundStatus::FULL_FAILED:
                    $refunded_desc = '全额退款失败'.($trade['refunded_fee'] > 0 ? '<br>已退款'.$trade['refunded_fee'].'元' : '');
                    break;
            }
        }
        $trade['refunded_desc'] = $refunded_desc;
//        $status = array('tosend','topay','send','success','cancel');
//        $trade['status'] = $status[$trade['status']];
        if($trade['status'] == OrderStatus::WAIT_SEND_GOODS && $trade['pay_time']){
            $trade['status_str'] = '买家已付款，等待商家发货';
            $trade['status_desc'] = '买家已付款，请尽快发货，否则买家有权申请退款';
        }else if($trade['status'] == OrderStatus::WAIT_PAY){
            $trade['status_str'] = '商品已拍下，等待买家付款';
            $trade['status_desc'] = '如买家未在规定时间内付款，订单将按照设置逾期自动关闭';
        }else if($trade['status'] == OrderStatus::WAIT_CONFIRM_GOODS){
            $trade['status_str'] = '商家已发货';
            $trade['status_desc'] = '商家已发货,等待买家签收,确认快递';
        }else if($trade['status'] == OrderStatus::SUCCESS){
            $trade['status_str'] = '交易完成';
            $trade['status_desc'] = '交易完成';
        }else if($trade['status'] == OrderStatus::BUYER_CANCEL){
            $trade['status_str'] = '订单关闭';
            $trade['status_desc'] = '订单关闭';
        }

        if(empty($trade['buyer_remark']))
            $trade['buyer_remark'] = '无留言内容';
        if(empty($trade['seller_remark']))
            $trade['seller_remark'] = '无备注内容';
        
        if($trade['shipping_type'] == 'virtual'){
            $trade['shipping_type_str'] = '无需物流';
        }else if($trade['shipping_type'] == 'selffetch'){
            $trade['shipping_type_str'] = '上门自提';
        }else{
            $trade['shipping_type_str'] = '快递配送';
        }
        
        // 已付款
        $payment = array();
        if($trade['paid_fee'] > 0)
            $payment[] = '¥'.sprintf('%.2f', $trade['paid_fee']);
        $payments = count($payment) > 0 ? implode(' + ', $payment) : '¥0.00';
        $this->assign("payment",$payments);


        // 应付款
        $payment_desc = '';
        $add = array();
        $reduce = array();
        if($trade['total_fee'] > 0)
            $add[] = '商品'.sprintf('%.2f', $trade['total_fee']).'元';
        if($trade['post_fee'] > 0)
            $add[] = '运费'.sprintf('%.2f', $trade['post_fee']).'元';
        if($trade['discount_fee'] > 0)
            $reduce[] = '优惠'.sprintf('%.2f', $trade['discount_fee']).'元';
        if($trade['paid_balance'] > 0)
            $reduce[] = '零钱'.sprintf('%.2f', $trade['paid_balance']).'元';
        if($trade['paid_no_balance'] > 0)
            $reduce[] = '积分'.sprintf('%.2f', $trade['paid_no_balance']).'元';
        if($trade['adjust_fee'] > 0)
            $add[] = '调价'.sprintf('%.2f', abs($trade['adjust_fee'])).'元';
        else if($trade['adjust_fee'] < 0)
            $reduce[] = '调价'.sprintf('%.2f', abs($trade['adjust_fee'])).'元';
        if(count($add) > 0)
            $payment_desc .= implode(' + ', $add);
        if(count($reduce) > 0)
            $payment_desc .= ' - '.implode(' - ', $reduce);
        $this->assign("payment_desc",$payment_desc.'='.$trade['payment'].'元');

        //判断主订单是否可以取消 $can_cancel = 1可以取消
//        $can_cancel = $Model->can_cancel($trade);
        $trade['pay_time'] = $trade['pay_time'] > 0 ? date('Y-m-d H:i:s',$trade['pay_time']) : '未支付';
        $trade['consign_time'] = $trade['consign_time'] > 0 ? date('Y-m-d H:i:s',$trade['consign_time']) : '未发货';
        $trade['sign_time'] = $trade['sign_time'] > 0 ? date('Y-m-d H:i:s',$trade['sign_time']) : '未签收';
        $this->assign(array(
            'data'         =>  $trade,
//            'can_cancel'   => $can_cancel
        ));
        
        $this->display();
    }
    
    /**
     * 取消订单
     */
    public function cancel(){
        $tid    = $_POST['tid'];
        $reason = $_POST['reason'];
        if(!is_numeric($reason) || !IdWork::isSystemTid($tid)){
            $this->error('请求参数错误');
        }
        
        $Model = new \Common\Model\OrderModel();
        $trade = $this->getTradeByTid($Model, $tid);
        if($trade['status'] != OrderStatus::WAIT_PAY){
            $this->error('非“待付款”订单无法取消，请尝试退款操作');
        }
        
        $tradeEndType = TradeEndType::getById($reason, false);
        if(!$tradeEndType){
            $this->error('关闭原因不在已知范围内');
        }
        $Model->cancelTrade($trade, $tradeEndType);
        $this->success('已取消');
    }
    
    /**
     * 订单备注
     */
    public function remark(){
        $tid = $_POST['tid'];
        $remark   = $_POST['remark'];
        if(!IdWork::isSystemTid($tid)){
            $this->error('订单号格式错误');
        }
        
        M("trade")->where("tid='{$tid}'")->save(array(
            'seller_remark'    => $remark,
            'modified'         => NOW_TIME
        ));
        $this->success();
    }
    
    /**
     * 订单发货
     */
    public function send(){
        $tid = I('request.tid');
        $Model  = D('Order');
        $trade = $Model->getTradeByTid($tid);
        $express_list = D('Common/Static')->express();
        
        $orderCount = count( $trade["orders"]);
        $sendCount = 0; // 已发送订单数量
        $sended = array();
        $nosended = array();
        
        foreach($trade["orders"] as $k=>$v){
            if($v['shipping_type'] == 'selffetch'){
                $trade["orders"][$k]['express_name'] = '上门自提';
            }else if($v['shipping_type'] == 'virtual'){
                $trade["orders"][$k]['express_name'] = '无需物流';
            }
            
            if($v['status'] == 'send'){
                $sendCount++;
            }
        
            foreach($trade["logistics"] as $key=>$val){
                if($v["product_id"] == $val["product_id"]){
                    $v["num"] = $val["num"];
                    $v["express_id"] = $val["express_id"];
                    $v["express_no"] = $val["express_no"];
                    $v["express_name"] = $express_list[$val["express_id"]]['name'];
                    
                    $v["status"] = "send";
                    $v["status_str"] = "已发货";
                    $sended[] = $v;
        
                    $trade["orders"][$k]["num"] -= $val["num"];
                    if($trade["orders"][$k]["num"] <= 0){
                        unset($trade["orders"][$k]);
                    }
                }
            }
        }
        
        $nosended = $trade["orders"];
        if($_POST){
            $now = date("Y-m-d H:i:s");
            $logisticsList = array();
            $update = array();
            
            $add = array(
                "tid" => $tid,
                "product_id" => 0,
                "num" => 0,
                "express_id" => '',
                "express_no" => '',
                "consign_time" => $now,
            );
            
            if($_POST["shipping_type"] == 'express'){
                $add['express_id'] = $_POST["express_id"];
                $add['express_no'] = $_POST["express_no"];
            }else if($_POST["shipping_type"] == 'selffetch'){
                $add['express_id'] = 1;
                $add['express_no'] = 'selffetch';
            }else if($_POST["shipping_type"] == 'virtual'){
                $add['express_id'] = 2;
                $add['express_no'] = 'virtual';
            }
            
            foreach($nosended as $item){
                if(!isset($_POST['products'][$item['oid']]) || $item['send']){
                    continue;
                }
                
                $num = $_POST['products'][$item['oid']];
                if($item['num'] < $num){
                    $this->error('发货产品数量充裕');
                }

                $add['product_id'] = $item["product_id"];
                $add['num'] = $num;
                $logisticsList[] = $add;
                $update[] = "UPDATE mall_order SET shipping_type='{$_POST["shipping_type"]}'".($item['num'] == $num ? ",status='send'" : "")." WHERE oid='{$item['oid']}'";
                if($item['num'] == $num){
                    $sendCount++;
                }
            }
            
            M("mall_logistics")->addAll($logisticsList);
            $status = '';
            if($orderCount == $sendCount){
                $status = 'send';
            }else if($trade['status'] == 'tosend'){
                $status = 'sendpart';
            }
            
            if($status != ''){
                $sql = "UPDATE trade SET status='{$status}'";
                if(!$trade['consign_time']){
                    $sql .= ",consign_time='{$now}'";
                }
                $update[] = $sql." WHERE tid='{$tid}'";
            }else{
                $status = $trade['status'];
            }
            
            foreach ($update as $sql){
                $Model->execute($sql);
            }
            
            // 计算代理收益
            if($status == 'send'){
                
            }
            $this->success(array('status' => $status));
        }

        $this->assign(array(
            'express_list' => $express_list,
            'trade'        => $trade,
            'orders'   => array_merge($sended, $nosended)
        ));
        $this->display();
    }
    
    /**
     * 订单修改价格--获取要修该的订单信息
     */
    public function get_change_order(){
        $this->Model = M("mall_order");
        $order_no = I('get.order_no');
        if($order_no == ""){
            $this->error("订单号不能为空！");
        }
        $order = $this->Model
                 ->where("order_no='%s'",$order_no)
                 ->field("order_no,status,total_price,total_fee,total_postage,adjust_fee,address_user_name,address_province,address_city,address_county,address_detail")
                 ->find();
        
        if(empty($order)){
            $this->error("订单不存在！");
        }
        if($order['status'] != "topay"){
            $this->error("订单状态已更新，无法改价！");
        }
        
        $order['products'] = $this->Model->query("SELECT * FROM mall_order_product WHERE order_id='{$order['order_no']}'");
        if(empty($order['products'])){
            $this->error("订单不存在！");
        }
        
        //数据处理
        foreach($order['products'] as $k=>$v){
            $sku_json = "";
            $v['sku_json'] = json_decode($v['sku_json']);
            foreach($v['sku_json'] as $key=>$val){
                $sku_json .= $val->sku_text.":".$val->text."&nbsp;&nbsp;";
            }
            $order['products'][$k]['sku_json'] = $sku_json;
        }
        
        $this->ajaxReturn($order);
    }
    
    /**
     * 订单修改价格
     */
    public function change_price(){
        $this->Model = M("mall_order");
        $order = array();
        $pay_time_out = C('ORDER_TIME_OUT');
        $order = I("post.order");
        
        if($order['order_no'] == ""){
            $this->error("订单号不能为空！");
        }
        
        $data = $this->Model->where("order_no='%s'",$order['order_no'])->find();
        
        //验证订单是否可以改价
        if(empty($data)){
            $this->error("订单不存在！");
        }
        if($data['status'] != "topay"){
            $this->error("订单状态已更新，无法改价！");
        }
        if($pay_time_out > 0 && strtotime($data['create_time']) + $pay_time_out <= NOW_TIME){
            $order['status'] = 'cancel';
            $order['end_time'] = NOW_TIME;
            $order['end_type'] = 'timeout';
            M()->execute("UPDATE mall_order SET status='{$order['status']}', end_time='{$order['end_time']}', end_type='{$order['end_type']}' WHERE order_no='{$data['order_no']}'");
            $this->error("订单已超时未付款，无法改价！");
        }

        //计算总金额的值
        $order['total_fee'] = bcsub($data['total_fee'], (bcsub($data['adjust_fee'], $order['adjust_fee'], 2) + bcsub($data['total_postage'], $order['total_postage'], 2)), 2);
        
        if($order['total_fee'] <= 0){
            $this->error("修改价格不能小于总价格！");
        }
        
        $this->Model->where("order_no='{$data["order_no"]}'")->save($order);
        $this->ajaxReturn($order);
    }
    
    /**
     * 导出并发货
     */
    public function print_and_send(){
        $Model = D('ExportSendOrder');
        $shopId = $this->user('shop_id');
        if(is_numeric($_GET['shop_id']) && $shopId != $_GET['shop_id']){
            if(!$this->authAllShop){
                $this->error('您无权导出其他店铺订单');
            }
            $shopId = $_GET['shop_id'];
        }

        $uid = $this->user('id');
        $data = $Model->sendAndExport($shopId, $uid);
        if($data === false){
            $this->error($Model->getError());
        }
        exit();
    }
    
    /**
     * 导出订单
     */
    public function printOrder(){
        $Model = D('ExportSendOrder');
        $shopId = $this->authAllShop && is_numeric($_GET['shop_id']) ? $_GET['shop_id'] : $this->user('shop_id');
        $data = $Model->printOrder($shopId);
        if($data === false){
            $this->error($Model->getError());
        }
        die;
    }
    
    public function import(){
        $sellerId = $this->user('shop_id');
        if(IS_GET){
            $static = D('Static');
            $list = $static->express(true);
            $this->assign('express_list', $list);
            $this->display();
        }
        
        if(empty($_FILES) || !preg_match('/\.xl(s|sx)$/', $_FILES['file_stu']['name'], $match)){
            $this->error("请上传excel文件！");
        }
         
        if($_FILES["file_stu"]['error'] > 0){
            $this->error("文件格式错误！");
        }
         
        $folder = '../Upload/import/'.date('Y-m');
        if(!@is_dir($folder)){
            mkdir ($folder, 0777, true);
        }
        
        $filename = $folder.'/'.date("Y-m-d His").'_'.$sellerId.$match[0];
        move_uploaded_file($_FILES["file_stu"]["tmp_name"], $filename);
        $Model = D('ImportOrderExpress');
        $Model->import($filename, $sellerId);
    }
    
    /**
     * 订单发货
     */
    public function sendGoods(){
        if(!IdWork::isSystemTid($_POST['tid'])){
            $this->error('订单号错误');
        }
        
        $Model = new OrderModel();
        $trade = $this->getTradeByTid($Model, $_POST['tid']);
        $success = $Model->sendGoods($trade, $_POST['express']);
        if(!$success){
            $this->error($Model->getError());
        }
        $this->success();
    }
    
    /**
     * 设置外部订单号
     */
    public function setOutTradeNo(){
        $tid = $_REQUEST['tid'];
        if(!preg_match('/^\d{13}$/', $tid)){
            $this->error('订单号不能为空');
        }
        
        $Model = M('trade');
        $trade = $Model->field("seller_id, `status`, receiver_name, receiver_mobile")->find($tid);
        if(empty($trade)){
            $this->error('订单号不存在');
        }else if(!$this->authAllShop && $trade['seller_id'] != $this->user('shop_id')){
            $this->error('您无权查看其它店铺订单');
        }else if($trade['status'] != 'toout'){
            $this->error('不是出库中不能更改外部订单号');
        }
        
        $loginIdList = $Model->query("SELECT login_id FROM alibaba_token WHERE expires_in>".NOW_TIME);
        foreach ($loginIdList as $i=>$item){
            $loginIdList[$i] = $item['login_id'];
        }
        
        // 查看已存在的订单号
        $existsList = $Model->query("SELECT id, tid, out_tid, `status`, buyer_login_id, error_msg, type FROM alibaba_trade WHERE tid={$tid} AND is_del=0");

        if(IS_GET){
            $list = null;
            if(empty($existsList)){
                $list = array(array('login_id' => '','out_tid'  => '', 'status' => 'error'));
            }else{
                $list = $existsList;
            }

            $this->assign('list', $list);
            $this->assign('loginIdList', $loginIdList);
            $this->display();
        }
        
        if(empty($_POST['out_trade_no'])){
            $this->error('外部订单号不能为空');
        }
        
        //校验本地系统数据
        $outTidList = $_POST['out_trade_no'];
        $_temp = array_keys($outTidList);
        $_temp = implode(',', $_temp);
        
        $sql = "SELECT alibaba_trade.id, alibaba_trade.tid, alibaba_trade.buyer_login_id, alibaba_trade.out_tid, alibaba_trade.`status`,
                   trade.receiver_name, trade.receiver_mobile, alibaba_trade.type
                FROM alibaba_trade
                INNER JOIN trade ON trade.tid=alibaba_trade.tid
                WHERE alibaba_trade.out_tid IN({$_temp}) AND alibaba_trade.is_del=0";
        $exists = $Model->query($sql);
        $noDoCostList = array();   // 不二次计算成本
        foreach ($exists as $item){
            if($item['tid'] != $tid){
                if($item['buyer_login_id'] != $outTidList[$item['out_tid']]){
                    $this->error('订单'.$item['out_tid'].'已存在本系统，且与下单账号不匹配');
                }
                
                if($trade['receiver_name'] != $item['receiver_name'] || $trade['receiver_mobile'] != $item['receiver_mobile']){
                    $this->error('订单'.$item['out_tid'].'已存在本系统，且与'.$tid.'收货人信息不匹配');
                }
                $noDoCostList[] = $item['out_tid'];
            }
        }
        
        $parameters = array();
        foreach ($_POST['out_trade_no'] as $no=>$loginId){
            if(!is_numeric($no) || strlen($no) != 16){
                $this->error('订单号'.$no.'格式错误');
            }
            $parameters[$loginId][] = $no;
        }
        
        $updateList = array();   // 更新
        $delList = '';     // 删除
        foreach ($existsList as $item){
            if(!empty($item['out_tid'])){
                $updateList[$item['out_tid']] = $item['id'];
            }else{
                $delList .= ($delList == '' ? '' : ',').$item['id'];
            }
        }

        $totalCost = 0;
        $aopModelList = array();
        $aop = null;
        $created = date('Y-m-d H:i:s');
        $sqlList = array();
        
        // 1688订单
        foreach ($parameters as $loginId=>$noList){
            if(!isset($aopModelList[$loginId])){
                $aop = new \Common\Model\AopModel($loginId);
                $aopModelList[$loginId] = $aop;
            }else{
                $aop = $aopModelList[$loginId];
            }
            
            // 1688订单
            $list = $aop->getTradeListByTid($noList);
            $yaoliubaba = array();
            $loginId = addslashes($loginId);
            foreach ($list as $item){
                $outId = number_format($item['id'],0,'','');
                unset($_POST['out_trade_no'][$outId]);
                
                $yaoliubaba[] = $outId;
                $orderTime = substr($item['gmtCreate'], 0, 14);
                $payment = ($item['sumPayment'] + $item['codFee']) / 100;
                
                $doCost = 0;
                if(!in_array($outId, $noDoCostList)){
                    $doCost = 1;
                    $totalCost = bcadd($totalCost, $payment, 2);
                }
                
                if(array_key_exists($outId, $updateList)){
                    $sqlList[] = "UPDATE alibaba_trade SET `status`='success', seller_nick='{$item['sellerLoginId']}',buyer_login_id='{$loginId}', order_time='{$orderTime}', payment='{$payment}', do_cost={$doCost}, type='1' WHERE id=".$updateList[$outId];
                    unset($updateList[$outId]);
                }else{
                    $sqlList[] = "INSERT INTO alibaba_trade SET tid={$tid}, out_tid='{$outId}', created='{$created}', `status`='success', seller_nick='{$item['sellerLoginId']}', buyer_login_id='{$loginId}', order_time='{$orderTime}', payment='{$payment}', do_cost={$doCost}, type='1'";
                }
            }
            
            // 淘宝订单
            $diffList = array_diff($noList, $yaoliubaba);
            foreach ($diffList as $outId){
                $doCost = in_array($outId, $noDoCostList) ? 0 : 1;
                if(array_key_exists($outId, $updateList)){
                    $sqlList[] = "UPDATE alibaba_trade SET `status`='end', seller_nick='淘宝卖家',buyer_login_id='{$loginId}', order_time='{$orderTime}', payment='0', do_cost={$doCost}, type='2' WHERE id=".$updateList[$outId];
                    unset($updateList[$outId]);
                }else{
                    $sqlList[] = "INSERT INTO alibaba_trade SET tid={$tid}, out_tid='{$outId}', created='{$created}', `status`='end', seller_nick='淘宝卖家', buyer_login_id='{$loginId}', order_time='{$orderTime}', payment='0', do_cost={$doCost}, type='2'";
                }
                unset($_POST['out_trade_no'][$outId]);
            }
        }
        
        if(count($_POST['out_trade_no']) > 0){
            $this->error('订单号无效:'.implode(',', array_keys($_POST['out_trade_no'])));
        }
        
        // 更新成本
        //$sqlList[] = "UPDATE trade SET total_cost={$totalCost} WHERE tid=".$tid;
        
        // 删除无用的
        if(count($updateList) > 0 || $delList != ''){
            foreach ($updateList as $id){
                $delList .= ($delList == '' ? '' : ',').$id;
            }
            $sqlList[] = "UPDATE alibaba_trade SET is_del=1 WHERE id IN ({$delList})";
        }
        
        // 执行修改
        $Model->startTrans();
        foreach($sqlList as $sql){
            $Model->execute($sql);
        }
        $Model->commit();
        $this->success();
    }
    
    /**
     * 调价
     */
    public function adjust(){
        $tid = $_REQUEST['tid'];//订单号
        if(!IdWork::isSystemTid($tid)){
            $this->error('订单号错误');
        }
        
        $Model = new \Common\Model\OrderModel();
        $trade = $this->getTradeByTid($Model, $tid);
        if($trade['status'] != OrderStatus::WAIT_PAY){
            $this->error('非“待付款”订单无法修改价格');
        }
        
        $fixedFee = bcsub($trade['total_fee'], $trade['paid_total'], 2);
        $fixedFee = bcsub($fixedFee, $trade['discount_fee'], 2);
        
        if(IS_POST){
            $change = array(
                'adjust_fee' => floatval($_POST['adjust_fee']),
                'postage'    => floatval($_POST['postage']),
                'payment'    => floatval($_POST['payment'])
            );
            
            $result = $Model->adjustFee($trade, $change);
            if(!$result){
                $this->error($Model->getError());
            }
            
            $this->success('改价成功，系统已自动通知买家及时付款！');
        }
        
        $this->assign('fixedFee', $fixedFee);
        $this->assign('trade', $trade);
        $this->display();
    }

    public function out_stock(){
        $Model = D('ExportSendOrder');
        $shopId = $this->user('shop_id');
//        if(is_numeric($_GET['shop_id']) && $shopId != $_GET['shop_id']){
//            if(!$this->allShop){
//                $this->error('您无权导出其他店铺订单');
//            }
//            $shopId = $_GET['shop_id'];
//        }

        $uid = $this->user('id');
        $data = $Model->sendAndExport($shopId, $uid);
        if($data === false){
            $this->error($Model->getError());
        }
        exit();
    }
}
?>