<?php

namespace Seller\Controller;
use Common\Model\OrderStatus;
use Common\Model\TradeEndType;
use Org\IdWork;
use Admin\Model\TradeSubscribe;

/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/4/7
 * Time: 11:29
 */

class OrderController extends ManagerController{

    public function index(){
        $title = I('get.title');
        $param = I('get.param');
        //判断小B
        $isLB = $this->isLittleB(substr($this->shopId, 0,-3));
        if(IS_AJAX){
            $where = array("trade_seller.seller_id={$this->shopId}");
            if($isLB == true){
                switch ($_GET['status']){
                    case 'topay':
                        $where[]  = 'trade_seller.`status`='.OrderStatus::WAIT_PAY;
                        break;
                    case 'topurchase':
                        $where[]  = 'trade_seller.`status`='.OrderStatus::ALI_WAIT_PAY;
                        break;
                    case 'tosend':
                        $where[]  = 'trade_seller.`status`='.OrderStatus::WAIT_SEND_GOODS;
                        break;
                    case 'send':
                        $where[]  = 'trade_seller.`status`='.OrderStatus::WAIT_CONFIRM_GOODS;
                        break;
                    case 'success':
                        $where[] = "trade_seller.`status`=".OrderStatus::SUCCESS;
                        break;
                }
            }else{
                switch ($_GET['status']){
                    case 'topay':
                        $where[]  = 'trade_seller.`status`='.OrderStatus::WAIT_PAY;
                        break;
                    case 'tosend':
                        $where[]  = 'trade_seller.`status`='.OrderStatus::WAIT_SEND_GOODS;
                        break;
                    case 'send':
                        $where[]  = 'trade_seller.`status`='.OrderStatus::WAIT_CONFIRM_GOODS;
                        break;
                    case 'success':
                        $where[] = "trade_seller.`status`=".OrderStatus::SUCCESS;
                        break;
                    case 'refund':
                        $where[]  = 'trade_seller.refund_status>0';
                        break;
                }
            }
            if(!empty($title)){
                $title = addslashes($title);
                $where[] = "(trade.tid = '{$title}' OR trade.receiver_mobile = '{$title}' OR trade.receiver_name like '%{$title}%' OR trade.buyer_nick like '%{$title}%')";
            }
            if(!empty($param)){
                if($param == 'tosend'){
                    $where[]  = 'trade_seller.`status`='.OrderStatus::WAIT_SEND_GOODS;
                }
                if($param == 'send'){
                    $where[]  = 'trade_seller.`status`='.OrderStatus::WAIT_CONFIRM_GOODS;
                }
                if($param == 'topay'){
                    $where[]  = 'trade_seller.`status`='.OrderStatus::WAIT_PAY;
                }
                if($param == 'today'){
                    $startdate = date('Y-m-d').' 00:00:00';
                    $enddate = date('Y-m-d').' 23:59:59';
                    $starttime = strtotime($startdate);
                    $endtime = strtotime($enddate);
                    $where[] = 'trade.created BETWEEN '.$starttime.' AND '.$endtime;
                }
            }
            $Model = new \Common\Model\OrderModel();
            $where[] = 'trade.seller_del = 0';
            $list = $Model->getTradeBySeller($where, $_GET['offset'], $_GET['size']);
            if($isLB == true){
                $extend_param = array();
                $goods = M('mall_goods')->field("id,extend_param")->where('shop_id = %s',$this->shopId)->select();
                foreach ($goods as $ky => $val) {
                    $param = json_decode($val['extend_param'],true);
                    $extend_param[$val['id']] = $param['is_suyuan'];
                }
                foreach ($list as $key => $value) {
                    //判断回流
                    foreach ($value['orders'] as $k => $v) {
                        if(!empty($extend_param[$v['goods_id']]) && $extend_param[$v['goods_id']] == 1){
                            $is_suyuan = 1;
                        }else{
                            if($is_suyuan != 1){
                                $is_suyuan = 0;
                            }
                        }
                        if($v['sku_json']!=''){
                            $sku = decode_json($v['sku_json']);
                            foreach ($sku as $ks => $vs) {
                                $list[$key]['orders'][$k]['sku_desc'] .= $vs['k'].':'.$vs['v'].';';
                            }
                        }else{
                            $list[$key]['orders'][$k]['sku_desc'] = '';
                        }
                    }
                    //显示订单号
                    if(!empty($list[$key]['express_no'])){
                        foreach ($list[$key]['express_no'] as $expresskey => $expressvalue) {
                            $list[$key]['express_no'] = $expresskey;
                        }
                    }
                    $list[$key]['is_suyuan'] = $is_suyuan;
                    $list[$key]['isLB'] = 1;
                }
            }else{
                foreach ($list as $key => $value) {
                    if(!empty($list[$key]['express_no'])){
                        foreach ($list[$key]['express_no'] as $expresskey => $expressvalue) {
                            $list[$key]['express_no'] = $expresskey;
                        }
                    }
                    $list[$key]['isLB'] = 0;
                    $list[$key]['is_suyuan'] = 0;
                    $list[$key]['paid_ali'] = 0;
                }
            }
            $this->ajaxReturn($list);
        }
        if($isLB == true){
            //更新订单状态
            // $this->synOrderShop();
            $this->assign('isLB','1');
        }else{
            $this->assign('isLB','0');
        }
        $this->assign('search_title', $title);
        $this->assign('param',$param);
        $this->display();
    }

    public function detail(){
        $tid = I('get.id');
        if(!is_numeric($tid)){
            $this->error('参数错误');
        }
        $Model = new \Common\Model\OrderModel();
        $data = $Model->getTradeByTid($tid);
        foreach ($data['orders'] as $k => $v){
            if ($v['original_price'] > $v['price']){
                $data['orders'][$k]['is_original'] = 1;
            }else{
                $data['orders'][$k]['is_original'] = 0;
            }
        }
        $data['is_sys'] = IdWork::isSystemTid($data['tid']);
        $extend_param = array();
        $goods = M('mall_goods')->field("id,extend_param")->where('shop_id = %s',$this->shopId)->select();
        foreach ($goods as $ky => $val) {
            $param = json_decode($val['extend_param'],true);
            $extend_param[$val['id']] = $param['is_suyuan'];
        }
        foreach ($data['orders'] as $k => $v) {
            if(!empty($extend_param[$v['goods_id']]) && $extend_param[$v['goods_id']] == 1){
                $is_suyuan = 1;
            }else{
                if($is_suyuan != 1){
                    $is_suyuan = 0;
                }
            }
        }
        $data['is_suyuan'] = $is_suyuan;
        $isLB = $this->isLittleB(substr($this->shopId, 0,-3));
        $data['isLB'] = $isLB;
        if($data['pay_time']!=0){
            $data['pay_time'] = date('Y-m-d H:i:s',$data['pay_time']);
        }else{
            $data['pay_time'] = '未付款';
        }
        foreach ($data['orders'] as $key => $value) {
            $data['orders'][$key]['sku_arr'] = json_decode($value['sku_json'],true);
            foreach ($data['orders'][$key]['sku_arr'] as $k => $v) {
                $data['orders'][$key]['sku'] .= $v['k'].':'.$v['v'].' ';
            }
        }
        $this->assign('data', $data);
        $this->display();
    }

    public function send(){
        $data = I('get.');
        if (IS_AJAX){
            $post = I('post.data');
            if (!is_numeric($post['id'])){
                return;
            }
            $model = M('trade');
            $up = array(
                "status" => '6',
                "express_no" => '{"'.$post['kuaidi_num'].'":"'.$post['kuaidi'].'"}',
                "receiver_detail" => $post['send_addr'],
                "receiver_mobile" => $post['tel'],
                "receiver_name" => $post['sender_nick'],
                "buyer_remark" => $post['custom_ps'],
            );
            $trade = $model->where("tid = {$post['id']} AND seller_id = {$this->shopId}")->find();
            $model->where("tid = {$post['id']} AND seller_id = {$this->shopId}")->save($up);
            if (empty($trade)){
                return;
            }
            $up_other = array(
                "status" => '6'
            );
            M("trade_seller")->where("tid = {$post['id']} AND seller_id = {$this->shopId}")->save($up_other);
            M("trade_buyer")->where("tid = {$post['id']} AND seller_id = {$this->shopId}")->save($up_other);
            M("trade_order")->where("tid = {$post['id']}")->save($up_other);
            /*$keys = array();
            $before = array();
            $after = array();
            foreach ($up as $k => $v){
                if ($v != $trade[$k]){
                    $keys[] = $k;
                    $before[$k] = $trade[$k];
                    $after[$k] = $v;
                }
            }
            $add = array(
                'tid' => $post['id'],
                'created' => NOW_TIME,
                'type' => '1',
                'keys' => implode(',', $keys),
                'before' => encode_json($before),
                'after' => encode_json($after),
            );
            $model = M('trade_record');
            $model->add($add);*/
            $this->ajaxReturn(1);
        }
        $this->assign('data', $data);
        $this->display();
    }

    public function remark(){
        if (IS_AJAX){
            $order_num = I('post.order_num');
            $seller_remark = I('post.data');
            if (!is_numeric($order_num)){
                return;
            }
            $up = array(
                "seller_remark" => $seller_remark,
            );
            $data = M('trade')->where("tid = {$order_num} AND seller_id = {$this->shopId}")->save($up);
            $this->ajaxReturn($data);
        }
    }
    /*
     * 取消订单
     */
    public function cancel(){
        if($_GET){
            $tid = I('get.id');
            if(!IdWork::isSystemTid($tid)){
                $this->error('请求参数错误');
            }
            $projectId = $this->user('project_id');
            $Model = new \Common\Model\OrderModel();
            $trade = $Model->getTradeByTid($tid);
            //print_data($projectId);
            if(!$trade){
                $this->error('订单不存在');
            }else if($trade['seller_id'] != $this->shopId){
                $this->error('您无权操作此订单');
            }else if(IdWork::getProjectId($trade['seller_id']) != $projectId){
                $this->error('您无权操作此订单');
            }
            if($trade['status'] != OrderStatus::WAIT_PAY){
                $this->error('非“待付款”订单无法取消，请尝试退款操作');
            }
            $tradeEndType = TradeEndType::getById(TradeEndType::SELLER_CANCEL, false);
            if(!$tradeEndType){
                $this->error('关闭原因不在已知范围内');
            }
            $Model->cancelTrade($trade, $tradeEndType);
            $this->success('已取消');
        }
    }
    /*
     * 同步1688订单状态
     */
    public function synOrder1688(){
        $tid = I('post.tid','');
        if(!empty($tid)){
            if(!is_numeric($tid)){
                $this->ajaxReturn('参数非法');
            }else{
                $shop = M('shop')->find($this->shopId);
                if(empty($shop) || empty($shop['aliid'])){
                    $this->ajaxReturn('未绑定1688店铺，无法更新订单状态');
                }
                $tokenId = $shop['aliid'];
                $Model = D("Alibaba");
                $result = $Model->getAliTrade($tid,$tokenId);
                if($result[$tid]['success'] == 1){
                    $this->ajaxReturn('同步1688订单状态成功');
                }else{
                    $this->ajaxReturn('同步1688订单状态失败');
                }
            }
        }
    }
    //
    public function payTo1688(){
        if(IS_AJAX){
            $tid = I('post.tid','');
            if(!is_numeric($tid)){
                $this->ajaxReturn('error');
            }
            $synOrder = new \Common\Model\AlibabaModel();
            $shop = M('shop')->field('aliid')->find($this->shopId);
            $result = $synOrder->getAliTrade($tid,$shop['aliid']);

            if($result[$tid]['success'] == 1 && $result[$tid]['pay'] == 1){
                //1688已支付
                $this->ajaxReturn('paid');
            }else if($result[$tid]['pay'] == 0 && $result[$tid]['success'] == 1){
                //1688未支付
                $this->ajaxReturn('nopay');
            }else if($result[$tid]['pay'] == 3 && $result[$tid]['success'] == 1){
                //完成订单，或变为确认收货，或取消订单
                $this->ajaxReturn('complete');
            }else{
                //同步订单失败
                $this->ajaxReturn($result[$tid]['fail_reason']);
            }
        }
    }
    //
    public function tradePaid(){
        $tid = I('post.tid');
        $is_suyuan = I('post.is_suyuan');
        if(!is_numeric($tid)){
            $this->error('参数错误');
        }
        $trade = M('trade')->where(array('tid'=>$tid))->find();
        if($trade['status']!=1){
            $this->ajaxReturn('已生成采购单');
        }
        $paid_fee = $trade['paid_fee']+$trade['payment'];
        $payment = $trade['payment']-$trade['payment'];
        M('trade')->where(array('tid'=>$tid))->save(array('payment'=>$payment,'paid_fee'=>$paid_fee));
        $trade = M('trade')->where(array('tid'=>$tid))->find();
        $Subscribe = new TradeSubscribe();
        $postData = array('tid' => $tid, 'pay_time' => NOW_TIME, 'pay_type' => $trade['pay_type'], 'paid_fee' => $trade['paid_fee'], 'paid_score' => 0);
        $result = $Subscribe->paid($postData,'1');
        if($is_suyuan == 1){
            //校验下单是否成功
            if($result == 'success'){
                M('trade_seller')->where('tid = %s',$tid)->save(array('status'=>OrderStatus::ALI_WAIT_PAY));
                M('trade')->where('tid = %s',$tid)->save(array('status'=>OrderStatus::ALI_WAIT_PAY));
                M('trade_order')->where('tid = %s',$tid)->save(array('status'=>OrderStatus::ALI_WAIT_PAY));
                M('trade_buyer')->where('tid = %s',$tid)->save(array('status'=>OrderStatus::ALI_WAIT_PAY));
                $this->ajaxReturn('1');
            }else{
                M('trade_seller')->where('tid = %s',$tid)->save(array('status'=>OrderStatus::BUYER_CANCEL));
                M('trade')->where('tid = %s',$tid)->save(array('status'=>OrderStatus::BUYER_CANCEL,'end_time'=>time(),'end_type'=>'1','errmsg'=>$result));
                M('trade_order')->where('tid = %s',$tid)->save(array('status'=>OrderStatus::BUYER_CANCEL));
                M('trade_buyer')->where('tid = %s',$tid)->save(array('status'=>OrderStatus::BUYER_CANCEL));
                $this->ajaxReturn($result.'，系统将自动关闭订单');
            }
        }else{
            M('trade_seller')->where('tid = %s',$tid)->save(array('status'=>OrderStatus::WAIT_SEND_GOODS));
            M('trade')->where('tid = %s',$tid)->save(array('status'=>OrderStatus::WAIT_SEND_GOODS));
            M('trade_order')->where('tid = %s',$tid)->save(array('status'=>OrderStatus::WAIT_SEND_GOODS));
            M('trade_buyer')->where('tid = %s',$tid)->save(array('status'=>OrderStatus::WAIT_SEND_GOODS));
            $this->ajaxReturn('0');
        }
    }
    //更新本店铺订单状态
    public function synOrderShop(){
        ignore_user_abort(true);
        $trade = M()->query("SELECT MIN(tid) AS min_tid, MAX(tid) AS max_tid FROM trade WHERE status IN(1,3,4,6,25) AND seller_id = '{$this->shopId}'");
        $min_tid = $trade[0]['min_tid'];
        $max_tid = $trade[0]['max_tid'];
        $ali = new \Common\Model\AlibabaModel();
        $shop = M()->query("SELECT aliid FROM shop WHERE id={$this->shopId}");
        $result = $ali->getAliTrade($min_tid,$shop[0]['aliid'], $max_tid);
        // return $result;
    }

    //批量同步订单状态
    public function syncOrderAll()
    {
        $seller = array();
        $trade = M()->query("SELECT DISTINCT seller_id FROM trade GROUP BY seller_id");
        foreach ($trade as $key => $value) {
            $seller[$key]['shop_id'] = $value['seller_id'];
            $shop = M()->query("SELECT aliid FROM shop WHERE id={$value['seller_id']}");
            $seller[$key]['aliid'] = $shop[0]['aliid'];
        }
        foreach ($seller as $val) {
            if($val['aliid']==0){
                continue;
            }else{
                $trade = M()->query("SELECT MIN(tid) AS min_tid, MAX(tid) AS max_tid FROM trade WHERE status IN(1,3,4,6) AND seller_id = '{$val['shop_id']}'");
                $min_tid = $trade[0]['min_tid'];
                $max_tid = $trade[0]['max_tid'];
                $ali = new \Common\Model\AlibabaModel();
                $result = $ali->getAliTrade($min_tid,$val['aliid'], $max_tid);
            }    
        }
    }
    /**
     * 查看物流信息
     */
    public function checkLogistics(){
        $tid = I('get.tid');
        if(!is_numeric($tid)){
            $this->error('参数错误');
        }
        $shop = M('shop')->where(array('id'=>$this->shopId))->find();
        $Model = new \Common\Model\AlibabaModel();
        $logistics = $Model->getLogistics($tid,$shop['aliid']);
        if(!empty($logistics['error'])){
            $this->error($logistics['error']);
        }

        $trade = M('trade')->where(array('tid'=>$tid))->find();
        if($trade['consign_time']!=0){
            $logistics['consign_time'] = $trade['consign_time'];
            $logistics['consign_date'] = date("Y-m-d H:i:s",$trade['consign_time']);
        }
        if(!empty($trade['express_no'])){
            $express_no = json_decode($trade['express_no'],true);
            foreach ($express_no as $key => $value) {
                $logistics['companyName'] = $value;
            }
        }
        //获取最近的物流节点高亮
        $maxKey = count($logistics['logisticsSteps'])-1;
        $dateTime = $logistics['logisticsSteps'][$maxKey]['acceptTime'];
        $remark = $logistics['logisticsSteps'][$maxKey]['remark'];
        $nowStep = array('acceptTime'=>$dateTime,'remark'=>$remark);
        //获取剩余节点
        unset($logistics['logisticsSteps'][$maxKey]);
        foreach ($logistics['logisticsSteps'] as $key => $value) {
            $date_time[] = strtotime($value['acceptTime']);
            $logistics['logisticsSteps'][$key]['date_time'] = strtotime($value['acceptTime']);
        }
        array_multisort($date_time, SORT_DESC, $logistics['logisticsSteps']);
        $this->assign('beforeSteps',$logistics['logisticsSteps']);
        $this->assign('nowStep',$nowStep);
        $this->assign('logistics',$logistics);
        $this->display('logistical');
    }

    /**
     * 卖家端删除订单
     */
    public function delete(){
        if (IS_AJAX){
            $tid = I('get.id/d', 0);
            if ($tid == 0){
                return -1;
            }
            $Model = new \Common\Model\OrderModel();
            $Model->delete2($tid, $this->shopId);
            $this->ajaxReturn(1);
        }
    }
}