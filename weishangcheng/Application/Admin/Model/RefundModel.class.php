<?php
namespace Admin\Model;

use Org\Wechat\WechatAuth;
use Common\Model\RefundReason;
use Common\Model\RefundStatus;
use Common\Model\StaticModel;
use Common\Common\CommonController;

/**
 * 退款modal
 * 
 * @author 兰学宝
 *        
 */
class RefundModel extends \Common\Model\BaseModel
{
    protected $tableName = 'trade_refund';
    private $sellerId = 0;
    function __construct($sellerId){
        parent::__construct();
        $this->sellerId = $sellerId;
    }
    
    /**
     * 获取单笔退款及交易信息
     * @param unknown $refundId
     */
    public function getRefundById($refundId){
        if(!is_numeric($refundId)){
            $this->error = '退款编号不能为空';
            return;
        }
        
        $sql = "SELECT refund.*,`order`.price, truncate(`order`.payment / `order`.num, 2) AS discount_price, `order`.num AS buy_num,trade.paid_no_balance, trade.refunded_fee, trade.total_num, 
                    trade.tid, trade.buyer_id, trade.buyer_nick, trade.buyer_openid, trade.seller_id, trade.post_fee, trade.paid_fee, 
                    trade.refund_state AS trade_refund_state, trade.pay_time, trade.`status` AS trade_status
                FROM {$this->tableName} AS refund
                INNER JOIN trade_order AS `order` ON `order`.oid=refund.refund_id
                INNER JOIN trade AS trade ON trade.tid=`order`.tid
                WHERE refund_id={$refundId}";
        $refund = $this->query($sql);
        if(empty($refund)){
            $this->error = '退款编号不存在';
            return;
        }else if($this->sellerId !== true && $refund[0]['seller_id'] != $this->sellerId){
           $this->error = '您无权限查看其他店铺退款';
           return;
        }else{
            $refund = $refund[0];
        }
        
        $refund['can_reset'] = $this->canReset($refund);
        return $refund;
    }
    
    /**
     * 是否可重置
     * @param unknown $refund
     * @return boolean
     */
    private function canReset($refund){
        // 只可操作一次
        if($refund['reset_times'] > 0 || $refund['trade_status'] == 'topay' || $refund['trade_status'] == 'cancel'){
            return false;
        }
        
        // 支付时间戳
        $payTimeStamp = strtotime($refund['pay_time']);
        
        // 最基础的30天
        $day = session('user.is_admin') ? 90 : 30;
        $expirationTimeStamp = strtotime('+'.$day.' day', $payTimeStamp);
        if($expirationTimeStamp  < NOW_TIME){
            return false;
        }
        
        // 未申请、取消、拒绝
        if(!is_numeric($refund['refund_id']) || $refund['refund_state'] == 0 || $refund['refund_state'] == 4 || $refund['refund_state'] == 5){
            return true;
        }
        
        return false;
    }
    
    /**
     * 退款详情
     * @param unknown $refundId
     */
    public function getDetail($tid){
        if(!is_numeric($tid)){
            $this->error = '订单号不能为空';
            return;
        }
        
        $trade = $this->query("SELECT tid, pay_time, total_num, buyer_id, buyer_nick, buyer_openid, seller_id, `status`,
                                refund_state, refunded_fee, paid_fee, post_fee, paid_no_balance
                               FROM trade WHERE tid=".$tid);
        if(empty($trade)){
            $this->error = '订单不存在';
            return;
        }
        $trade = $trade[0];
        $trade['total_refund_fee'] = 0; // 累计退款总额
        $trade['total_refund_num'] = 0; // 累计退款件数
        
        if($this->sellerId !== true && $trade['seller_id'] != $this->sellerId){
            $this->error = '您无权限查看其它店铺订单';
            return;
        }
        
        $sql = "SELECT `order`.oid, `order`.title, `order`.num, `order`.price, truncate(`order`.payment / `order`.num, 2) AS discount_price, `order`.sku_json, refund.*
                FROM trade_order AS `order`
                LEFT JOIN {$this->tableName} AS refund ON refund_id=`order`.oid
                WHERE `order`.tid={$trade['tid']}";
        $orders = $this->query($sql);
        
        // 退货地址
        //$address = array( 'receiver_name' => '', 'receiver_mobile' => '', 'receiver_province' => '', 'receiver_city' => '', 'receiver_county' => '', 'receiver_detail' => '');
        $address = null;
        if($trade['refund_state'] == 'partial_refunding' || $trade['refund_state'] == 'full_refunding'){
            $rule = $this->query("SELECT * FROM shop_refund WHERE id=".$trade['seller_id']);
            if(!empty($rule)){
                $address = $rule[0];
            }
        }
        
        // 退款状态、原因
        $Model = new RefundReason();
        $allReason = $Model->getAll();
        $Model = new RefundStatus();
        $allState = $Model->getTradeStatus();
        
        $list = array();
        foreach($orders as $i=>$item){
            if($item['refund_state'] == 1 || $item['refund_state'] == 2 || $item['refund_state'] == 3){
                $trade['total_refund_fee'] += $item['refund_fee'] + $item['refund_post'];
                $trade['total_refund_num'] += $item['refund_num'];
                
                if($item['refund_state'] == 1 && $item['refund_type'] == 0 && !empty($address)){
                    $item['receiver_name']    = $address['receiver_name'];
                    $item['receiver_mobile']  = $address['receiver_mobile'];
                    $item['receiver_address'] = $address['receiver_province'].' '.$address['receiver_city'].' '.$address['receiver_county'].' '.$address['receiver_detail'];
                }
            }else if($item['refund_state'] == ''){
                $item['refund_state'] = 0;
            }
            $item['refund_num']  = $item['refund_state'] == 0 ? 0 : $item['refund_num'];
            $item['refund_fee']  = is_numeric($item['refund_fee'])  ? $item['refund_fee']  : 0;
            $item['refund_post'] = is_numeric($item['refund_post']) ? $item['refund_post'] : 0;
            $item['refund_state_str'] = $allState[$item['refund_state']];
            $item['spec']   = get_spec_name($item['sku_json']);
            
            // 是否可再次退款 / 是否可强制申请退款
            $item['pay_time'] = $trade['pay_time'];
            $item['trade_status'] = $trade['status'];
            
            $item['can_reset'] = $this->canReset($item);
            
            $list[$item['oid']] = $item;
        }

        $trade['status_str'] = $Static->orderStatus($trade['status']); // 订单状态
        $trade['orders'] = $list;
        
        return array('trade' => $trade, 'reason' => $allReason);
    }
    
    /**
     * 更新订单退款状态
     * @param unknown $trade
     */
    private function updateStatus($trade){
        // 计算是否还有退款，以便更改订单退款状态
        $all = array('doing' => 0, 'success' => 0, 'fail' => 0);
        $list = $this->query("SELECT refund_state, SUM(refund_fee) + SUM(refund_post) AS total_fee, SUM(refund_num) AS total_num FROM {$this->tableName} WHERE refund_id IN (SELECT oid FROM mall_order WHERE tid='{$trade['tid']}') GROUP BY refund_state");
        $refundNum = 0;
        $refunding = 0;
        $refunded = 0;
        $refundFail = 0;
        foreach($list as $item){
            if($item['refund_state'] == 1 || $item['refund_state'] == 2 || $item['refund_state'] == 2.1){ // 申请中和退款中
                $all['doing'] += $item['total_fee'];
                $refunding++;
            }else if($item['refund_state'] == 3){
                $all['success'] += $item['total_fee'];
                $refundNum += $item['total_num'];
                $refunded++;
            }else if($item['refund_state'] == 4){
                $all['fail'] += $item['total_fee'];
                $refundFail++;
            }
        }
    
        $tradeRefundState = 'no_refund';
        if($all['doing'] > 0 || $refunding > 0){
            $tradeRefundState = $all['doing'] >= $trade['paid_fee'] ? 'full_refunding' : 'partial_refunding';
        }else if($all['success'] > 0 || $refunded > 0){
            $tradeRefundState = $all['success'] >= $trade['paid_fee'] ? 'full_refunded' : 'partial_refunded';
        }else if($all['fail'] > 0 || $refundFail > 0){
            $tradeRefundState = $all['fail'] >= $trade['paid_fee'] ? 'full_failed' : 'partial_failed';
        }
    
        $sql = "UPDATE mall_trade SET refund_state='{$tradeRefundState}', refunded_fee={$all['success']}";
        if($refundNum == $trade['total_num']){
            $sql .= ", `status`='cancel', end_time='".date('Y-m-d H:i:s')."', end_type='refund'";
        }
        $sql .= " WHERE tid='{$trade['tid']}'";
        $this->execute($sql);
    }
    
    /**
     * 获取退款列表
     */
    public function getAll($paging,$myProjectId){
        $data = array('total' => 0, 'rows' => array());
        $where = array();
        if($this->sellerId === true){
            if(is_numeric($_GET['seller_id'])){
                $where[] = "trade.seller_id=".$_GET['seller_id'];
            }
            else {
                $where[] = "trade.seller_id BETWEEN '".$myProjectId.'001'."' AND '".$myProjectId.'999'."'";
            }
        }else{
            $where[] = "trade.seller_id=".$this->sellerId;
        }
        if(is_numeric($_GET['refund_state'])){
            $where[] = "refund.refund_status='{$_GET['refund_state']}'";
        }
        if(!empty($_GET['date_range'])){
            $search['start_time'] = strtotime(substr($_GET['date_range'], 0, 16).':00');
            $search['end_time']   = strtotime(substr($_GET['date_range'], 19).':59');
            $where[] = "refund.refund_created between '".$search['start_time']."' and '".$search['end_time']."'";
        }
        if(!empty($_GET['tid'])){
            if(is_numeric($_GET['tid'])){
                $time   = substr($_GET['tid'], 0, 8);
                $format = 'Ymd';
                $d = \DateTime::createFromFormat($format, $time);
                if($d && $d->format($format) == $time){
                    $where[] = "trade.tid=".$_GET['tid'];
                }else if(preg_match('/^(1458\d{6})$/', $_GET['tid'])){
                    $where[] = "trade.tid=".$_GET['tid'];
                }else{
                    $where[] = "refund.refund_express='".addslashes($_GET['tid'])."'";
                }
            }else{
                $where[] = "refund.refund_express='".addslashes($_GET['tid'])."'";
            }
        } 
        $where = count($where) > 0 ? "WHERE ".implode(" AND ", $where) : "";
        $offset = is_numeric($_GET['offset']) ? $_GET['offset'] : 0;
        $limit = is_numeric($_GET['limit']) ? $_GET['limit'] : 50;
        if($paging==0){
            $sql = "SELECT COUNT(*)
            FROM {$this->tableName} AS refund
            INNER JOIN trade_order AS `order` ON `order`.oid=refund.refund_id
            INNER JOIN trade AS trade ON trade.tid=`order`.tid
            {$where}";
            $total = $this->query($sql);
            $data['total'] = current($total[0]);
            if($data['total'] > 0){
                $sql = "SELECT `order`.tid, `order`.goods_id, `order`.sku_json, `order`.title, `order`.quantity AS buy_num, `order`.price, `order`.pic_url, 
                        refund.refund_created, refund.refund_reason, refund.refund_fee, refund.refund_quantity, refund.refund_post, refund.refund_express, refund.refund_status
                FROM {$this->tableName} AS refund
                INNER JOIN trade_order AS `order` ON `order`.oid=refund.refund_id
                INNER JOIN trade AS trade ON trade.tid=`order`.tid
                {$where}
                ORDER BY refund.refund_created DESC
                LIMIT {$offset}, {$limit}";
                $list = $this->query($sql);
            }
        }else{
            $sql = "SELECT `order`.tid, `order`.goods_id, `order`.sku_json, `order`.title, `order`.quantity AS buy_num, `order`.price, `order`.pic_url,
            refund.refund_created, refund.refund_reason, refund.refund_fee, refund.refund_quantity, refund.refund_post, refund.refund_express, refund.refund_status,trade.seller_name,buyer_id
            FROM {$this->tableName} AS refund
            INNER JOIN trade_order AS `order` ON `order`.oid=refund.refund_id
            INNER JOIN trade AS trade ON trade.tid=`order`.tid
            {$where}
            ORDER BY refund.refund_created DESC
            ";
            $list = $this->query($sql);
        }
        
        // 退款状态、原因
        $Model = new RefundReason;
        $allReason = $Model->getAll();
        foreach($list as $item){
            $item['spec'] = get_spec_name($item['sku_json']);
            $item['refund_reason_str'] = $allReason[$item['refund_reason']];
            $item['refund_created'] = date('Y-m-d H:i:s',$item['refund_created']);
            unset($item['sku_json']);
            $data['rows'][] = $item;
        }
        return $data;
    }
    
    /**
    * 保存数据
    * @access public
    * @param mixed $data 数据
    * @param array $options 表达式
    * @return boolean
    */
    public function save($data= array(),$options=array()) {
        $data['refund_uid']    = session('user.id');
        $data['refund_modify'] = date('Y-m-d H:i:s');
        return parent::save($data, $options);
    }
    
    /**
     * 拒绝退款申请
     * @param unknown $data
     */
    public function refuse($data){
        $refund = $this->getRefundById($data['refund_id']);
        if(empty($refund)){
            return -1;
        }else if(strlen($data['refund_sremark']) < 5){
            $this->error = '拒绝原因不能少于5个字符';
            return -1;
        }else if($refund['refund_state'] != 1){
            $Static = new \Common\Model\StaticModel();
            $allState = $Static->refundedState();
            $this->error = '当前状态无法拒绝：'.$allState[$refund['refund_state_str']];
            return -1;
        }

        $data['refund_state']  = 4;
        $this->where("refund_id=".$data['refund_id'])->save($data);
        $refund = array_merge($refund, $data);
        
        // 更新订单退款状态
        $this->updateStatus($refund);
        
        // 消息通知
        $this->sendAgreeOrRefuseMsg($refund);
        return 1;
    }

    /**
     * 修改
     */
    public function agree($data, $address = null){
        if(empty($data) || !is_numeric($data['refund_id'])){
            $this->error = '退款编号不能为空';
            return -1;
        }else if($data['refund_fee'] + $data['refund_post'] <= 0){
            $this->error = '退款总额不能小于0';
            return -1;
        }else if($data['refund_num'] < 1){
            $this->error = '退换数量不能小于1';
            return -1;
        }else if(empty($data['refund_reason'])){
            $this->error = '请选择退款原因';
            return -1;
        }
    
        $refund = $this->getRefundById($data['refund_id']);
        if(empty($refund)){
            return -1;
        }

        $maxRefundPost = $refund['post_fee'] == 0 ? 10 : $refund['post_fee'] * 2;
        if($refund['refund_state'] != 1 && !$refund['can_reset']){
            $this->error = '当前状态禁止此操作，请刷新后再试！';
            return -1;
        }else if($data['refund_num'] > $refund['buy_num']){
            $this->error = '退换数量应在1~'.$refund['buy_num'].'之间';
            return -1;
        }else if($data['refund_post'] < 0 || $data['refund_post'] > $maxRefundPost){
            $this->error = '邮费补偿应在0~'.$maxRefundPost.'之间';
            return -1;
        }else if($data['refund_fee'] < 0 || $data['refund_fee'] > $refund['discount_price'] * $data['refund_num']){
            $this->error = '退款金额应在0~'.($refund['discount_price'] * $data['refund_num']).'之间';
            return -1;
        }
        
        // 再次确认退款数量
        /*
        if($refund['discount_price'] <= 0){
            $this->error = '此商品不支持退换货';
            return -1;
        }
        */
        $sysRefundNum = ceil(($data['refund_fee'] + $data['post_fee']) / $refund['discount_price']);
        if($data['refund_num'] < $sysRefundNum){
            $this->error = '系统建议将退换数量调整为：'.$sysRefundNum;
            return -1;
        }
    
        if(empty($address)){
            $data['refund_state'] = 2.1;
        }else{
            $data['refund_state'] = 2;
            $data = array_merge($data, $address);
        }

        $result = $this->where("refund_id=".$refund['refund_id'])->save($data);
        if($result <= 0){
            $this->error = '保存失败';
            return -1;
        }
        $refund = array_merge($refund, $data);
        
        // 更新订单退款状态
        $this->updateStatus($refund);
    
        // 立即退款
        if($refund['refund_state'] == 2.1){
            $this->refundMoney($refund);
        }else{
            $this->sendAgreeOrRefuseMsg($refund);
        }
    
        return 1;
    }
    
    /**
     * 发送同意或拒绝消息
     * @param unknown $refund
     */
    public function sendAgreeOrRefuseMsg($refund){
        $wxUser = $this->getWXUserConfig($refund['buyer_id'], $refund['buyer_openid']);
        if(empty($wxUser['config'])){
            return;
        }
        
        $agree = $refund['refund_state'] != 4;
        
        $config = $wxUser['config'];
        $url = $config['HOST'].'/h5/order/detail?tid='.$refund['tid'];
        $wechatAuth = new WechatAuth($config['WEIXIN']);
        $wechatAuth->sendTemplate($wxUser['openid'], array(
            "template_id"  => $config['WX_TEMPLATE']['OPENTM202735558'],
            "url"          => $url,
            "data" => array(
                "first"    => array("value" => '你好，您的退款申请被'.($agree ? '通过' : '驳回').'。'),
                "keyword1" => array("value" => '已被'.($agree ? '通过' : '驳回')),
                "keyword2" => array("value" => sprintf('%.2f', $refund['refund_fee'] + $refund['refund_post'])),
                "keyword3" => array("value" => $refund['refund_state'] == 2 ? '请填写退货快递单号' : $refund['refund_sremark']),
                "remark"   => array("value" => '点击查看详情')
            )
        ));
    }
    
    /**
     * 退款 - 退款
     * @param unknown $trade
     * @param unknown $data
     */
    public function refundMoney($refund, $data){
        if(empty($refund)){
            E('退款数据异常');
        }

        $maxRefundPost = $refund['post_fee'] == 0 ? 10 : $refund['post_fee'] * 2;
        if($refund['refund_state'] != 2.1 && $refund['refund_state'] != 2){ // 只有退款中才允许退款
            $this->error = '当前状态不可立即退款，请刷新后再试！';
            return -1;
        }else if($data['refund_post'] < 0 || $data['refund_post'] > $maxRefundPost){
            $this->error = '邮费补偿应在0~'.$maxRefundPost.'之间';
            return -1;
        }else if($data['refund_fee'] < 0 || $data['refund_fee'] > $refund['discount_price'] * $refund['refund_num']){
            $this->error = '退款金额应在0~'.($refund['discount_price'] * $refund['refund_num']).'之间';
            return -1;
        }

        $data['refund_state'] = 3;
        $this->startTrans();
        $this->where("refund_id=".$refund['refund_id'])->save($data);
        $refund = array_merge($refund, $data);
        
        $balanceModel = D('Balance');
        $url = '/h5/order/detail?tid='.$refund['tid'];
    
        // 扣回差价
        $profitList = $this->query("SELECT mid, diff_price FROM mall_trade_difference WHERE tid='{$refund['tid']}' AND oid='{$refund['refund_id']}' AND checkout=1");
        if(!empty($profitList)){
            foreach ($profitList as $i=>$profit){
                $profit['money'] = sprintf('%.2f', $profit['diff_price'] * $refund['refund_num']);
                $balanceModel->add(array(
                    'mid'       => $profit['mid'],
                    'reason'    => '好友售后退款 - '.$refund['tid'],
                    'balance'   => -$profit['money'],
                    'link'      => $url,
                    'type'      => 'lower_cancel'
                ));
                $profitList[$i] = $profit;
            }
        }
    
        //3.给下单人返款金额。
        $backMoney = bcadd($refund['refund_fee'], $refund['refund_post'], 2);
        $backBalance = array(
            'mid'       => $refund['buyer_id'],
            'reason'    => '订单售后退款 - '.$refund['tid'],
            'balance'   => $backMoney,
            'link'      => $url,
            'type'      => 'order_refunded'
        );
        //计算需要返回的不可提现金额
        if($refund['paid_no_balance'] > 0){
            $noBalance = $refund['paid_no_balance'] - $refund['refunded_fee'];
            if($noBalance > 0){
                $backBalance['no_balance'] = ($noBalance - $backMoney) > 0 ? $backMoney : $noBalance;
                $backBalance['balance'] = bcsub($backMoney, $backBalance['no_balance'], 2);
            }
        }
        $balanceModel->add($backBalance);
        
        // 更新订单状态
        $this->updateStatus($refund);
        $this->commit();
    
        // 通知断开连接
        header('Content-Type:application/json; charset=utf-8');
        echo json_encode(array('status' => 1, 'info' => '退款成功'));
        ignore_user_abort(true);
        header('X-Accel-Buffering: no');
        header('Content-Length: '. strlen(ob_get_contents()));
        header("Connection: close");
        header("HTTP/1.1 200 OK");
        ob_end_flush();
        flush();
        set_time_limit(0);
    
        $msgDate = date('Y年m月d日 H:i');
        // 下单人零钱入账通知
        $myWX = $this->getWXUserConfig($refund['buyer_id'], $refund['buyer_openid']);
        if(!empty($myWX['config'])){
            $WechatAuth = new WechatAuth($myWX['config']['WEIXIN']);
            $WechatAuth->sendTemplate($myWX['openid'], array(
                'template_id' => $myWX['config']['WX_TEMPLATE']['TM00335'],
                'url' => $myWX['config']['HOST'].$url,
                'data' => array(
                    'first'    => array('value' => '您有新积分到账，详情如下。', 'color' => '#173177'),
                    'account'  => array('value' => $refund['buyer_nick']),
                    'time'     => array('value' => $msgDate),
                    'type'     => array('value' => '订单售后退款'),
                    'creditChange' => array('value' => '退回'),
                    'number'       => array('value' => $backMoney),
                    'creditName'   => array('value' => '积分'),
                    'amount'       => array('value' => '***'),
                    'remark'       => array('value' => '订单'.$refund['tid'].'售后退款')
                )
            ));
        }
    
        // 上级扣款通知
        if(!empty($profitList)){
            foreach ($profitList as $profit){
                $parentWX = $this->getWXUserConfig($profit['mid']);
                if(empty($parentWX['config'])){
                    continue; 
                }
                
                $WechatAuth = new WechatAuth($parentWX['config']['WEIXIN']);
                $WechatAuth->sendTemplate($parentWX['openid'], array(
                    'template_id' => $parentWX['config']['WX_TEMPLATE']['TM00335'],
                    'url' => $parentWX['config']['HOST'].$url,
                    'data' => array(
                        'first'    => array('value' => '您有新积分扣除，详情如下。', 'color' => '#173177'),
                        'account'  => array('value' => '当前账户'),
                        'time'     => array('value' => $msgDate),
                        'type'     => array('value' => '好友订单售后退款'),
                        'creditChange' => array('value' => '扣除'),
                        'number'       => array('value' => $profit['money']),
                        'creditName'   => array('value' => '积分'),
                        'amount'       => array('value' => '***'),
                        'remark'       => array('value' => '好友订单'.$refund['tid'].'已售后退款，故积分应返还给好友')
                    )
                ));
            }
        }
    
        exit();
    }
    
    /**
     * 添加退款
     * @param unknown $data
     */
    public function addByAdmin($data){
        if(empty($data) || !is_numeric($data['refund_id'])){
            $this->error = '退款编号不能为空';
            return -1;
        }else if($data['refund_fee'] + $data['refund_post'] <= 0){
            $this->error = '退款总额不能小于0';
            return -1;
        }else if($data['refund_num'] < 1){
            $this->error = '退换数量不能小于1';
            return -1;
        }else if(empty($data['refund_reason'])){
            $this->error = '请选择退款原因';
            return -1;
        }
        
        // 获取订单信息
        $sql = "SELECT `order`.price, truncate(`order`.payment / `order`.num, 2) AS discount_price, `order`.num AS buy_num,trade.paid_no_balance, trade.refunded_fee, trade.total_num,
                    trade.tid, trade.buyer_id, trade.buyer_nick, trade.buyer_openid, trade.seller_id, trade.post_fee, trade.paid_fee,
                    trade.refund_state AS trade_refund_state, trade.pay_time, trade.`status` AS trade_status
                FROM trade_order AS `order`
                INNER JOIN trade AS trade ON trade.tid=`order`.tid
                WHERE `order`.oid={$data['refund_id']}";
        $trade = $this->query($sql);
        $trade = $trade[0];
        if(empty($trade)){
            $this->error = '订单不存在';
            return -1;
        }else if($this->sellerId !== true && $this->sellerId != $trade['seller_id']){
            $this->error = '您无权查看其它店铺订单';
            return -1;
        }

        $maxRefundPost = $trade['post_fee'] == 0 ? 10 : $trade['post_fee'] * 2;
        if(!$this->canReset($trade)){
            $this->error = '已超过可退款期限';
            return -1;
        }else if($data['refund_num'] > $trade['buy_num']){
            $this->error = '退换数量应在1~'.$trade['buy_num'].'之间';
            return -1;
        }else if($data['refund_post'] < 0 || $data['refund_post'] > $maxRefundPost){
            $this->error = '邮费补偿应在0~'.$maxRefundPost.'之间';
            return -1;
        }else if($data['refund_fee'] < 0 || $data['refund_fee'] > $trade['discount_price'] * $data['refund_num']){
            $this->error = '退款金额应在0~'.($trade['discount_price'] * $data['refund_num']).'之间';
            return -1;
        }
        
        // 再次确认退款数量
        /*
        if($trade['discount_price'] <= 0){
            $this->error = '此商品不支持退换货';
            return -1;
        }
        */
        $sysRefundNum = ceil(($data['refund_fee'] + $data['post_fee']) / $trade['discount_price']);
        if($data['refund_num'] < $sysRefundNum){
            $this->error = '系统建议将退换数量调整为：'.$sysRefundNum;
            return -1;
        }
        
        // 其它数据
        $now = date('Y-m-d H:i:s');
        $data['refund_state'] = empty($data['receiver_address']) ? 2.1 : 2;
        $data['refund_type'] = $trade['trade_status'] == 'tosend' || $trade['trade_status'] == 'toout' ? 1 : 0;
        
        $data['refund_created'] = $now;
        $data['refund_uid']    = session('user.id');
        $data['refund_modify'] = $now;
        
        $result = $this->add($data);
        if($result < 1){
            $this->error = '添加退款失败，未知原因';
            return -1;
        }
        
        $refund = array_merge($trade, $data);

        // 更新订单退款状态
        $this->updateStatus($refund);
        
        // 立即退款
        if($refund['refund_state'] == 2.1){
            $this->refundMoney($refund);
        }else{
            $this->sendAgreeOrRefuseMsg($refund);
        }
        return 1;
    }
}
?>