<?php
namespace Pay\Controller;

use Pay\Model\OrderModel;
use Common\Model\StaticModel;
use Common\Model\BalanceModel;
use Pay\Model\OrderPreviewModel;
use Common\Model\OrderStatus;
use Common\Model\OrderType;
use Common\Model\PayType;
use Admin\Model\TradeSubscribe;

class OrderController extends PayController{
    
    /**
     * 订单确认
     */
    public function confirm(){
        $id = $_GET['book_key'];
        if(!is_numeric($id)){
            $this->error('book_key无效');
        }
        
        $Model = new OrderPreviewModel();
        $book = $Model->getBook($id);
        if(!$book){
            $this->error($Model->getError());
        }

        $login = $this->checkLogin();
        
        if($login['id'] != $book['buyer_id']){
            $this->error('book_key与下单人不一致', $book['redirect']);
        }
        
        $this->assign('book_key', $book['id']);
        $this->assign('PAY_URL', C('PAY_URL'));
        
        // 底部导航URL处理
        $host = $this->getRedirectHost($book['redirect']);
        $parse = C('TMPL_PARSE_STRING');
        $parse['__MODULE__'] = '<?php echo "'.$host.'" ?>';
        C('TMPL_PARSE_STRING', $parse);

        $this->display();
    }

    private function getRedirectHost($url){
        $data = parse_url($url);
        $project = explode('/', $data['path']);
        return $data['scheme'].'://'.$data['host'].'/'.$project[1];
    }

    /**
     * 点击确定或好跳转的地址
     */
    private function getOrderUrl($url, $result){
        $data = parse_url($url);
        $project = explode('/', $data['path']);
        $url = $data['scheme'].'://'.$data['host'].'/'.$project[1].'/order';
    
        $paidTid = count($result['paid_tid']);
        $needPayTid = count($result['need_pay']);
    
        if($paidTid + $needPayTid == 1){
            $url .= '/detail?tid='.($paidTid == 1 ? $result['paid_tid'][0] : $result['need_pay'][0]);
        }
    
        return $url;
    }
    
    /**
     * 重新组合订单
     */
    public function buildorder(){
        if(!is_numeric($_POST['book_key'])){
            $this->error('book key无效');
        }
        $Model = new OrderPreviewModel();
        $book = $Model->getBook($_POST['book_key']);
        if(!$book){
            $this->error($Model->getError());
        }

        $login = $this->checkLogin();
    
        // 创建订单基础数据校验
        if(isset($_POST['need_pay'])){
            if(mb_strlen($_POST['address']['receiver_name'], 'UTF8') < 2
                || !is_numeric($_POST['address']['receiver_mobile'])
                || mb_strlen($_POST['address']['receiver_detail'], 'UTF8') < 5){
                    $this->error('收货地址无效');
            }
        }
        
        $data = $Model->createPreview($book, $login, $_POST);
        //判断小B
        $isLB = $this->isLittleB(substr($data['groups'][0]['shop_id'],0,-3));
        if($isLB == true){
            $id = $this->user('id');
            $wx = M('wx_user')->where(array('mid'=>$id))->find();

            if($wx['coupon']>1){
                if(count($data['groups'][0]['trades'])==1 && $data['groups'][0]['payment']>5){
                    $data['groups'][0]['discount_fee'] = $_POST['discount_fee']/100;
                    $data['groups'][0]['payment'] = sprintf('%.2f',$data['groups'][0]['payment'] - $data['groups'][0]['discount_fee']);
                    // $data['groups'][0]['total_fee'] = $data['groups'][0]['total_fee'] - $data['groups'][0]['discount_fee'];
                    $data['groups'][0]['trades'][0]['discount_fee'] = sprintf('%.2f',$data['groups'][0]['discount_fee']);
                    $data['groups'][0]['trades'][0]['payment'] = sprintf('%.2f',$data['groups'][0]['payment']);
                    $data['need_pay'] = sprintf('%.2f',$data['need_pay'] - $data['groups'][0]['discount_fee']);
                }
            }else{
                if(count($data['groups'][0]['trades'])==1 && $data['groups'][0]['payment']>5){
                    $data['groups'][0]['discount_fee'] = $wx['coupon'];
                    $data['groups'][0]['payment'] = sprintf('%.2f',$data['groups'][0]['payment'] - $data['groups'][0]['discount_fee']);
                    // $data['groups'][0]['total_fee'] = $data['groups'][0]['total_fee'] - $data['groups'][0]['discount_fee'];
                    $data['groups'][0]['trades'][0]['discount_fee'] = sprintf('%.2f',$data['groups'][0]['discount_fee']);
                    $data['groups'][0]['trades'][0]['payment'] = sprintf('%.2f',$data['groups'][0]['payment']);
                    $data['need_pay'] = sprintf('%.2f',$data['need_pay'] - $data['groups'][0]['discount_fee']);
                }
            }
            $data['isLB'] = 1;
        }
        if(!$data['has_error'] && isset($_POST['need_pay'])){
            if(floatval($data['need_pay']) != floatval($_POST['need_pay']) || floatval($data['need_score']) != floatval($_POST['need_score'])){
                $data['has_error'] = 1;
                $data['error'] = '支付总额与服务端计算不一致，请尝试从新结算';
            }else{
                $result = $Model->createOrder($data['groups'], $data['address']);
                if(!$result){
                    $data['has_error'] = 1;
                    $data['error'] = $Model->getError();
                }else{
                    $result['submit'] = 1;
                    $result['payment'] = floatval($data['need_pay']);
                    $result['redirect'] = $book['redirect'];
                    $result['order_url'] = $this->getOrderUrl($book['redirect'], $result);
                    $result['appid'] = $book['mch_appid'];
                    //判断小B
                    if($isLB==true){
                        M('trade_seller')->addAll($result['seller'],array(),true);
                        M('trade_buyer')->addAll($result['buyer'],array(),true);
                        $result['isLB'] = '1';
                        $result['shop_id'] = $result['seller'][0]['seller_id'];
                        $shop = M('shop_info')->where(array('id'=>$result['shop_id']))->find();
                        $result['pay_qr'] = $shop['pay_qr'];
                    }
                    $data = $result;
                }
            }
        }

        $this->ajaxReturn($data);
    }
    
    /**
     * 清空失效商品
     */
    public function clear(){
        $id = $_GET['book_key'];
        $products = $_GET['products'];
        if(!is_numeric($id)){
            $this->error('book key无效');
        }else if(empty($products)){
            $this->error('清空产品不能为空');
        }
    
        $Model = M('trade_book');
        $book = $Model->field("products")->find($id);
        if(empty($book)){
            $this->error('book key已失效');
        }
    
        $products = explode(',', $products);
        $book['products'] = json_decode($book['products'], true);
        foreach ($book['products'] as $i=>$item){
            if(in_array($item['product_id'], $products)){
                array_splice($book['products'], $i, 1);
            }
        }
    
        $book['products'] = json_encode($book['products']);
        $Model->where("id='{$id}'")->save($book);
        $this->success('已清空，您可继续下单了');
    }
    
    private function checkeStatus($trade, $buyer){
        $redirect = $buyer['url'].'/order/detail?tid='.$trade['tid'];
        if($trade['status'] != OrderStatus::WAIT_PAY){
            $this->error('订单状态已变更：'.OrderStatus::getById($trade['status']), $redirect);
        }else if($trade['pay_timeout'] <= NOW_TIME){
            $this->error('订单付款超时', $redirect);
        }
    }
    
    /**
     * 订单详情
     */
    public function detail(){
        $tid = $_GET['tid'];
        if(!is_numeric($tid)){
            $this->error('订单号不能为空');
        }
        
        // 检测登录状态
        $login = $this->checkLogin();
        
        $Model = new OrderModel();
        $trade = $this->getTrade($Model, $tid);
        
        $project = get_project($trade['seller_id'], true);
        $buyer = $this->getBuyer($Model, $login, $project['id']);

        $parse = C('TMPL_PARSE_STRING');
        $parse['__MODULE__'] = '<?php echo "'.$buyer['url'].'" ?>';
        C('TMPL_PARSE_STRING', $parse);

        
        $this->checkeStatus($trade, $buyer);
        $orders = $this->getOrder($Model, $trade, $buyer['url']);
        if($trade['buyer_id']== $login['id']){
            $btn_text = '立即支付';
        }else{
            $btn_text = '帮他代付';
        }
        $this->assign('btn_text', $btn_text);
        
        // 可抵用项目
        $switch = array();
        if($trade['payment'] > 0 && $buyer['wallet'] > 0){
            $switch[] = array('message' => '可用'.$project['wallet_alias'].'抵用', 'field' => 'wallet');
        }
        if($trade['payment'] > 0 && $buyer['balance'] > 0){
            $switch[] = array('message' => '可用'.$project['balance_alias'].'抵用', 'field' => 'balance');
        }
        if($trade['payscore'] > 0){
            if($trade['payscore'] > $buyer['score']){
                $this->assign('hasError', true);
                $this->assign('btn_text', $project['score_alias'].'不足');
            }else{
                $switch[] = array('message' => '可用'.$project['score_alias'].'支付', 'field' => 'score');
            }
        }
        $this->assign('switch', $switch);
        $isLB = $this->isLittleB($project['id']);
        $shop_info = M('shop_info')->where(array('id'=>$project['id'].'001'))->find();
        //如果有支付宝二维码
        if(!empty($shop_info['pay_zfb']) && !is_null($shop_info['pay_zfb'])){
            $this->assign('has_zfb','1');
        }
        $this->assign('isLB',$isLB);
        $this->assign('project', $project);
        $this->display();
    }
    
    /**
     * 读取买家信息
     */
    private function getBuyer($Model, $login, $projectId){
        // 读取买家信息
        $buyer = $Model->getProjectMember($login, $projectId);
        // 如果用户的可提现余额变成负数，则限制钱包货款，保护商家利益
        if($buyer['balance'] < 0 && $buyer['wallet'] > 0){
            $buyer['wallet'] = bcadd($buyer['wallet'], $buyer['balance'], 2);
        }

        $buyer['wallet'] = floatval($buyer['wallet']);
        $buyer['balance'] = floatval($buyer['balance']);
        
        if(IS_GET){
            $this->assign('buyer', json_encode($buyer));
        }
        return $buyer;
    }
    
    /**
     * 获取订单信息
     */
    private function getTrade($Model, $tid){
        $trade = $Model->find($tid);
        
        if(empty($trade)){
            $this->error('订单不存在');
        }
        
        if(IS_GET){
            // 合计 - 购买商品
            $trade['sum_fee'] = bcadd($trade['total_fee'], $trade['total_freight'], 2);
            $trade['sum_fee'] = bcsub($trade['sum_fee'], $trade['discount_fee'], 2);
            $trade['sum_score'] = bcadd($trade['total_score'], $trade['discount_score'], 2);
            $trade['sum_score'] = floatval($trade['sum_score']);
            
            $express = StaticModel::express($trade['express_id']);
            if($express['id'] == 0 || $express['id'] == 1){
                $trade['express'] = $express['name'];
            }else{
                $trade['express'] = $express['name'].($trade['total_freight'] > 0 ? $trade['total_freight'].'元' : '(包邮)');
            }
            
            $this->assign('message', '距离订单自动关闭还剩'.second_to_time($trade['pay_timeout'] - NOW_TIME, true));
            $this->assign('trade', $trade);
        }
        
        return $trade;
    }
    
    /**
     * 获取订单详情
     */
    private function getOrder($Model, $trade, $detailUrl = ''){
        $hasError = false;
        $errmsg = '';
        
        // 查找子订单
        $sql = "SELECT `order`.oid, `order`.main_tag, `order`.type, `order`.goods_id, `order`.title, `order`.price, `order`.score,
                goods.is_display, goods.is_del, `order`.ext_params, `order`.`status`, `order`.sku_json, `order`.original_price,
                product.id AS product_id, product.stock, `order`.sub_stock, `order`.pic_url, `order`.quantity
                FROM trade_order AS `order`
                LEFT JOIN mall_goods AS goods ON goods.id=`order`.goods_id
                LEFT JOIN mall_product AS product ON product.id=`order`.product_id AND `order`.type != ".OrderType::GIFT."
                WHERE `order`.tid='{$trade['tid']}'";
        $orders = $Model->query($sql);
        
        // 校验子订单库存
        foreach ($orders as $i=>$order){
            $order['spec'] = get_spec_name($order['sku_json']);
        
            // 检测赠品是否充足
            if($order['type'] == OrderType::GIFT){
                $order['price'] = '赠品';
                // 查找赠品
                $giftId = $order['product_id'];
                $gift = $Model->query("SELECT buy_quota, goods_id, start_time, end_time FROM mall_gift WHERE id='{$giftId}'");
                $gift = $gift[0];
                if(empty($gift) || $order['is_del']){
                    $order['errmsg'] = '赠品已被删除';
                }else if($gift['end_time'] > NOW_TIME){
                    $order['errmsg'] = '赠品已过期(超时)';
                }else if($gift['buy_quota'] > 0){
                    $sql = "SELECT SUM(quantity) AS total FROM trade_gift WHERE gift_id='{$giftId}' AND mid='{$trade['buyer_id']}' AND `status`<2";
                    $times = $Model->query($sql);
                    $times = $times[0]['total'] ? $times[0]['total'] : 0;
                    if($times > $gift['buy_quota']){
                        $order['errmsg'] = '赠品无效(超领)';
                    }
                }
        
                if($order['errmsg'] != ''){
                    $errmsg = '赠品无效，继续支付后其他产品正常发货，赠品将自动删除';
                }
            }else if($order['is_del']){
                $order['errmsg'] = '抱歉，商品已被删除';
                $hasError = true;
            }else if($order['is_display'] != 1){
                $order['errmsg'] = '抱歉，商品已下架';
                $hasError = true;
            }else if($order['sub_stock'] == 0 && $order['stock'] < $order['quantity']){
                if($order['stock'] < 1){
                    $order['errmsg'] = '抱歉，商品已售罄';;
                }else{
                    $order['errmsg'] = '抱歉，库存不足！仅剩'.$order['stock'].'件';
                }
            }
        
            // 页面价格
            if(IS_GET){
                $order['view_price'] = array();
                if($order['score'] > 0){
                    $order["view_price"][] = array('price' => $order['score'], 'prefix' => '', 'suffix' => '积分');
                    if($order['price'] > 0){
                        $order["view_price"][] = array('price' => sprintf('%.2f', $order['price']), 'prefix' => '+', 'suffix' => '元');
                    }else{
                        $order["view_price"][] = array('price' => sprintf('%.2f', $order['original_price']), 'prefix' => '¥', 'suffix' => '');
                    }
                }else{
                    $order["view_price"][] = array('price' => sprintf('%.2f', $order['price']), 'prefix' => '¥', 'suffix' => '');
                    if($order['original_price'] > 0){
                        $order["view_price"][] = array('price' => sprintf('%.2f', $order['original_price']), 'prefix' => '¥', 'suffix' => '');
                    }else{
                        $order["view_price"][] = array('price' => '&nbsp;', 'prefix' => '', 'suffix' => '');
                    }
                }
                
                $order['detail_url'] = $detailUrl.'/goods?id='.$order['goods_id'];
            }else if($hasError){
                $this->error($order['errmsg'], '/order?tid='.$trade['tid'].'&modify='.NOW_TIME);
            }
            $orders[$i] = $order;
        }
        
        if(IS_GET){
            $this->assign('orders', $orders);
            $this->assign('hasError', $hasError);
            $this->assign('errmsg', $errmsg);
        }
        return $orders;
    }
    
    /**
     * 生成微信支付
     */
    public function wxpay(){
        $tid = $_POST['tid'];
        $login = $this->checkLogin();
        
        $Model = new OrderModel();
        if(is_array($tid)){
            $result = $Model->createPay($_POST['tid'], $_POST['appid'], $login[$_POST['appid']]['openid']);
            if(!$result){
                $this->error($Model->getError());
            }
            $this->ajaxReturn($result);
        }
        
        $trade = $this->getTrade($Model, $tid);
        $project = get_project($trade['seller_id'], true);
        $buyer = $this->getBuyer($Model, $login, $project['id']);
        
        $this->checkeStatus($trade, $buyer);
        $orders = $this->getOrder($Model, $trade, $buyer['url']);
        $redirect = $buyer['url'].'/order/detail?tid='.$trade['tid'];
        
        // 重新推送消息
        if($trade['payment'] < 0.01 && $trade['payscore'] < 1){
            $this->tradePaid($tid, $trade['pay_type'], $redirect);
            $result = array('payment' => 0, 'payscore' => 0, 'url' => $redirect);
            $this->ajaxReturn($result);
        }
        
        // 货款抵用
        $trade['payment'] = floatval($trade['payment']);
        $useWallet = floatval($_POST['use_wallet']);
        if($useWallet > 0 && $trade['payment'] > 0 && $buyer['wallet'] > 0){
            if($useWallet > $buyer['wallet']){
                $this->error('账户'.$project['wallet_alias'].'不足', '/order?tid='.$trade['tid'].'&modify='.NOW_TIME);
            }else if($useWallet > $trade['payment']){
                $this->error('应付金额已变更，请重新支付', '/order?tid='.$trade['tid'].'&modify='.NOW_TIME);
            }
            
            $trade['payment'] = bcsub($trade['payment'], $useWallet, 2);
            $trade['paid_wallet'] = bcadd($trade['paid_wallet'], $useWallet, 2);
        }else{
            $useWallet = 0;
        }

        // 零钱抵用
        $trade['payment'] = floatval($trade['payment']);
        $useBalance = floatval($_POST['use_balance']);
        if($useBalance > 0 && $trade['payment'] > 0 && $buyer['balance'] > 0){
            if($useBalance > $buyer['balance']){
                $this->error('账户'.$project['balance_alias'].'不足', '/order?tid='.$trade['tid'].'&modify='.NOW_TIME);
            }else if($useBalance > $trade['payment']){
                $this->error('应付金额已变更，请重新支付', '/order?tid='.$trade['tid'].'&modify='.NOW_TIME);
            }
        
            $trade['payment'] = bcsub($trade['payment'], $useBalance, 2);
            $trade['paid_balance'] = bcadd($trade['paid_balance'], $useBalance, 2);
        }else{
            $useBalance = 0;
        }
        
        // 积分抵用
        $trade['payscore'] = floatval($trade['payscore']);
        $useScore = floatval($_POST['use_score']);
        if($useScore > 0 && $trade['payscore'] > 0 && $buyer['score'] > 0){
            if($useScore > $buyer['score']){
                $this->error('账户'.$project['score_alias'].'不足', '/order?tid='.$trade['tid'].'&modify='.NOW_TIME);
            }else if($useScore > $trade['payscore']){
                $this->error('应付'.$project['score_alias'].'已变更，请重新支付', '/order?tid='.$trade['tid'].'&modify='.NOW_TIME);
            }
        
            $trade['payscore'] = bcsub($trade['payscore'], $useScore, 2);
            $trade['paid_score'] = bcadd($trade['paid_score'], $useScore, 2);
        }else{
            $useScore = 0;
        }

        $trade['payment'] = floatval($trade['payment']);
        $trade['payscore'] = floatval($trade['payscore']);
        
        // 保存数据
        $anonymous = $_POST['anonymous'] ? 1 : 0;
        $sql = "UPDATE trade SET 
                  paid_wallet=paid_wallet+{$useWallet},
                  paid_balance=paid_balance+{$useBalance},
                  paid_score=paid_score+{$useScore},
                  payment=payment-{$useWallet}-{$useBalance},
                  payscore=payscore-{$useScore},
                  anonymous={$anonymous},
                  buyer_remark='".addslashes($_POST['buyer_remark'])."',
                  modified=".NOW_TIME."
                WHERE tid='{$trade['tid']}' AND `status`=".OrderStatus::WAIT_PAY;
        $result = $Model->execute($sql);
        if($result < 1){
            $this->error('保存失败');
        }
        
        if($useBalance > 0 || $useWallet > 0 || $useScore > 0){
            $BalanceModel = new BalanceModel();
            $BalanceModel->add(array(
                'project_id' => $project['id'],
                'mid'        => $login['id'],
                'balance'    => -$useBalance,
                'wallet'     => -$useWallet,
                'score'      => -$useScore,
                'reason'     => $login['id'] == $trade['buyer_id'] ? '支付订单['.$trade['tid'].']' : '代付订单['.$trade['tid'].']',
                'type'       => 'order'
            ));
        }
        
        if($trade['payment'] > 0){  // 创建微信支付
            $count = count($orders);
            $data = array(
                'body'      => '订单结算中心-'.$orders[0]['title'].'('.($count > 0 ? '等'.($count-1).'种商品' : $trade['total_quantity'].'件').')',
                'total_fee' => floatval($trade['payment']),
                'appid'     => $project['third_mpid'],
                'openid'    => $login[$project['third_mpid']]['openid'],
                'payscore'  => $trade['payscore']
            );
            
            $result = $Model->createWxPayOrder($data, $trade['tid']);
            $result['payscore'] = $trade['payscore'];
            $result['redirect'] = $trade['payscore'] > 0 ? '/order?tid='.$trade['tid'].'&modify='.NOW_TIME : $redirect;
            $this->ajaxReturn($result);
        }else if($trade['payscore'] < 1){ // 通知订单已支付
            $payType = $trade['pay_type'];
            if($trade['pay_type'] == PayType::CODPAY){
                
            }else if($trade['paid_score'] > 0){ // 积分兑换
                $payType = PayType::SCORE;
            }else if($trade['paid_wallet'] > 0 && $trade['paid_wallet'] > $trade['paid_balance']){ // 货款抵用
                $payType = PayType::WALLET;
            }else if($trade['paid_balance'] > 0){ // 余额抵用
                $payType = PayType::BALANCE;
            }
            
            $this->tradePaid($tid, $payType, $redirect);
        }
        
        $result = array('payment' => $trade['payment'], 'payscore' => $trade['payscore'], 'url' => $redirect);
        $this->ajaxReturn($result);
    }
    
    /**
     * 执行订单已支付代码
     * @param unknown $tid
     * @param unknown $payType
     * @param unknown $redirect
     */
    private function tradePaid($tid, $payType, $redirect){
        $Subscribe = new TradeSubscribe();
        $postData = array('tid' => $tid, 'pay_time' => NOW_TIME, 'pay_type' => $payType, 'paid_fee' => 0, 'paid_score' => 0);
        $Subscribe->paid($postData);
        $result = array('payment' => 0, 'payscore' => 0, 'url' => $redirect);
        $this->ajaxReturn($result);
    }

    /**
     * 联系商家付款页面
     */
    public function buyerPay(){
        $data = I('get.data');
        $shop_id = I('get.shop_id');
        $data = json_decode($data,true);
        // print_data($data);
        $Model = new \Common\Model\OrderModel();
        foreach ($data as $key => $value) {
            $order[] = $Model->getTradeByTid($value['tid']);
            $tid = $value['tid'];
        }
        $receiver_msg = array(
            'name' => $order[0]['receiver_name'],
            'mobile' => $order[0]['receiver_mobile'],
            'province' => $order[0]['receiver_province'],
            'city'=> $order[0]['receiver_city'],
            'county'=> $order[0]['receiver_county'],
            'detail'=> $order[0]['receiver_detail'],
            );

        $this->assign('receiver_msg',$receiver_msg);
        $this->assign('order',$order);
        $this->assign('tid',$tid);
        //计算订单数量
        $order_num = count($data);
        if($order_num == 1){
            $this->assign('only','1');
        }
        //计算商品总价，运费
        $total = 0;
        $post = 0;
        foreach ($order as $k => $val) {
            $total += $val['payment'];
            $post += $val['total_postage'];
        }
        $this->assign('total',$total);
        $this->assign('post',$post);
        $this->assign('shop_id',$shop_id);
        //获取二维码
        $shop_info = M('shop_info')->find($shop_id);
        $this->assign('pay_qr',$shop_info['pay_qr']);
        //获取微信号
        $this->assign('wx_no',$shop_info['wx_no']);
        //如果有支付宝二维码
        if(!empty($shop_info['pay_zfb']) && !is_null($shop_info['pay_zfb'])){
            $this->assign('has_zfb','1');
        }
        $this->display('paytoseller');
    }

    public function getPayQr(){
        if(IS_AJAX){
            $tid = I("post.tid");
            $paymode = I("post.wx_or_zfb",'wx');
            if(!is_numeric($tid)){
                $this->error("参数错误");
            }
            $trade = M('trade')->where(array('tid'=>$tid))->find();
            if(empty($trade)){
                $this->error("订单不存在");
            }else{
                $shop = M('shop_info')->where(array('id'=>$trade['seller_id']))->find();
                if(empty($shop)){
                    $this->error("订单不存在");
                }
                if($paymode == 'wx'){
                    $data=array('payment'=>$trade['payment'],'pay_qr'=>$shop['pay_qr'],'explain'=>'/img/wx_bz.png');
                }else if($paymode == 'zfb'){
                    $data=array('payment'=>$trade['payment'],'pay_qr'=>$shop['pay_zfb'],'explain'=>'/img/zfb_bz.png');
                }
                $this->ajaxReturn($data);
            }
        }
    }
}
?>