<?php
namespace H5\Controller;

use Common\Common\CommonController;
use Common\Model\OrderStatus;
use Org\IdWork;
use Common\Model\TradeEndType;
use Common\Model\OrderModel;
use Common\Model\ProjectConfig;

/**
 * 订单
 * @author lanxuebao
 *
 */
class OrderController extends CommonController
{
    /**
     * 列表视图
     */
	public function index(){
        $mid = $this->user('id');
        $projectId = PROJECT_ID;
    	$isLB = $this->isLittleB($projectId);
    	if($isLB == true){
    		$this->assign('isLB',1);
    	}else{
    		$this->assign('isLB',0);
    	}
		$this->display();
	}
	
	/**
	 * 查询订单列表
	 */
	public function search(){
	    $buyerId = $this->user('id');
	    $projectId = PROJECT_ID;
	    $where = array("trade_buyer.buyer_id={$buyerId}", "trade_buyer.seller_id BETWEEN {$projectId}000 AND {$projectId}999");
	    if(is_numeric($_GET['tid'])){
	        $where[] = "trade_buyer.tid='{$_GET['tid']}'";
	    }
	    $isLB = $this->isLittleB($projectId);
	    switch ($_GET['status']){
	        case 'topay':
	            $where[]  = 'trade_buyer.`status`='.OrderStatus::WAIT_PAY;
	            break;
	        case 'tosend':
	            $where[]  = '(trade_buyer.`status`='.OrderStatus::WAIT_SEND_GOODS.' OR trade_buyer.`status`='.OrderStatus::ALI_WAIT_PAY.')';
	            break;
	        case 'send':
	            $where[]  = 'trade_buyer.`status`='.OrderStatus::WAIT_CONFIRM_GOODS;
	            break;
	        case 'torefund':
	            $where[]  = 'trade_buyer.refund_status>0';
	            break;
	        case 'torate':
	            $where[] = "trade_buyer.`status`=".OrderStatus::SUCCESS." AND trade_buyer.buyer_rate=0";
	            break;
	    }
        
        $Model = new \Common\Model\OrderModel();
        $list = $Model->getTradeByBuyer($where, $_GET['offset'], $_GET['size']);
 		$shop_info = M('shop_info')->where(array('id'=>$projectId.'001'))->find();
        if($isLB == true){
        	foreach ($list as $key => $value) {
        		if($value['status'] == OrderStatus::WAIT_PAY){
        			$seller = array(
        				'tid' => $value['tid']
        				);
        			$shop_id = $value['seller_id'];
        			$arr = array('seller'=>array($seller),'shop_id'=>$shop_id);
        			$param = json_encode($arr);
        			$list[$key]['buttons'] = array(
        				array('text' => '取消订单', 'class' => 'js-cancel-trade', 'url' => __MODULE__.'/order/cancel'),
        				array('text'=>'微信付款','class'=>'wxzf','url'=>"javascript:;")
        			);
        			if(!empty($shop_info['pay_zfb']) && !is_null($shop_info['pay_zfb'])){
        				$list[$key]['buttons'] = array(
	        				array('text' => '取消订单', 'class' => 'js-cancel-trade', 'url' => __MODULE__.'/order/cancel'),
	        				array('text'=>'支付宝付款','class'=>'zfbzf','url'=>"javascript:;"),
	        				array('text'=>'微信付款','class'=>'wxzf','url'=>"javascript:;")
	        			);
        				// $list[$key]['buttons'][] = array('text'=>'支付宝付款','class'=>'zfbzf','url'=>"javascript:;");
        			}
        		}
        		if($value['status'] == OrderStatus::WAIT_CONFIRM_GOODS){
        			$list[$key]['buttons'] = array(
        				array('text' => '查看物流', 'class' => 'js-search-express', 'url' => __MODULE__.'/order/checkLogistics?tid='.$value['tid']),
        				array('text' => '确认收货', 'class' => 'js-confirm-goods', 'url'=>__MODULE__.'/order/confirm')
        			);
        		}
        	}
        }
        $this->ajaxReturn($list);
	}
	
	/**
	 * 获取订单号
	 */
	private function getTid(){
	    $tid = IS_POST ? $_POST['tid'] : $_GET['tid'];
	    if(!is_numeric($tid)){
	        $this->error('订单号不能为空');
	    }
	    return $tid;
	}
	
	/**
	 * 根据订单号查订单
	 */
	private function getTrade($Model, $buyer_id = 0){
	    $tid = $this->getTid();
	    $trade = $Model->getTradeByTid($tid);
	    if(!$trade){
	        $this->error('订单号不存在');
	    }else if($buyer_id > 0 && $buyer_id != $trade['buyer_id']){
	        $this->error('您无权操作他人订单');
	    }
	    return $trade;
	}

	/**
	 * 取消订单
	 */
	public function cancel(){
	    $buyer_id = $this->user('id');

	    $Model = new \Common\Model\OrderModel();
	    $trade = $this->getTrade($Model, $buyer_id);
	    
	    if($trade['status'] == OrderStatus::WAIT_PAY){
	        $tradeEndType = TradeEndType::getById(TradeEndType::BUYER_CANCEL, false);
	        $Model->cancelTrade($trade, $tradeEndType);
	    }
	    $this->success('订单已取消');
	}
	
	/**
	 * 删除订单
	 */
	public function delete(){
	    $buyer_id = $this->user('id');
	    $tid = $this->getTid();
	    
	    $Model = new \Common\Model\OrderModel();
	    $Model->delete($tid, $buyer_id);
	    $this->success('订单已删除');
	}
	
	/**
	 * 确认收货
	 */
	public function confirm(){
	    $buyer_id = $this->user('id');
	    
	    $Model = new \Common\Model\OrderModel();
	    $trade = $this->getTrade($Model, $buyer_id);
	    
	    if($trade['status'] == OrderStatus::WAIT_CONFIRM_GOODS){
	        $Model->confirmGoods($trade);
	    }
	    
	    $this->success();
	}

	/**
	* 崔单
	*/
	public function reminder(){
		$this->error('已提醒卖家发货！');
	}
	
	/**
	 * 订单详情
	 */
	public function detail(){
	    $tid = $_GET['tid'];
	    if(!is_numeric($tid)){
	        $this->error('订单号不能为空');
	    }
        $userId = $this->user("id");
	    $Model = new \Common\Model\OrderModel();
	    $trade = $Model->getTradeByTid($tid);
	    
	    if(!$trade){
	        $this->error('订单号不存在');
	    }
	    
	    if($userId != $trade['buyer_id']){
	        $this->error('您无权查看此订单');
	    }
	    
	    
		//判断小B
		$projectId = substr($trade['seller_id'], 0, -3);
        $isLB = $this->isLittleB($projectId);
        //小B更新订单
        if($isLB == true){
        	$shop = M('shop')->find($trade['seller_id']);
        	$Ali = new \Common\Model\AlibabaModel();
        	$Ali->getAliTrade($tid,$shop['aliid']);
        	$trade = $Model->getTradeByTid($tid);
        }
	    if($trade['status'] == OrderStatus::WAIT_PAY){
	        $url = C('PAY_URL').'/order/detail?tid='.$tid.'&ticket='.session_id();
	        redirect($url);
	    }else if($trade['buyer_id'] != $userId){
	        $trade['receiver_mobile'] = IdWork::hideMobile($trade['receiver_mobile']);
	    }

	    
	    // 显示倒计时
	    $countDown = array('visible' => false);
	    if($trade['status'] == OrderStatus::WAIT_PAY){// 等待付款
	        $countDown['visible'] = true;
	        $countDown['message'] = '距离订单自动关闭还剩';
	        $countDown['time'] = $trade['pay_timeout'];
	    }else if($trade['status'] == OrderStatus::WAIT_CONFIRM_GOODS){// 等待确认收货
	        $countDown['visible'] = true;
	        if($isLB != 1){
	        	$countDown['message'] = '请确认已收到产品并无问题再确认收货，如有问题及时联系商家解决！距离自动确认收货还剩';
	        }else{
	        	$countDown['message'] = '请确认已收到产品并无问题再确认收货，如有问题及时联系商家解决！';
	        	$countDown['unable'] = true;
	        }
	        $countDown['time'] = strtotime('+15 day', $trade['consign_time']);
	    }else if($trade['receive_time'] > 0 && $trade['buyer_rate'] == 0){
	        $countDown['visible'] = true;
	        $countDown['message'] = '距离自动评价还剩';
	        $countDown['time'] = strtotime('+3 day', $trade['receive_time']);
	        $countDown['show_rate'] = true;
	    }
		
        $this->assign('isLB',$isLB);
        
	    $this->assign(array(
	        'trade'     => $trade,
            'countDown' => $countDown
	    ));
	    $this->display();
	}
	
	/**
	 * 最近下单记录
	 */
	public function nearest(){
	    $id = date('s');
	    $data = M('trade_nearest')->find($id);
	    
	    $hour = date('H');
	    $interval = 5;
	    if($hour > 0 && $hour < 6){
	        $interval = 300;
	    }else{
	        $interval = random_int(5, 8);
	    }
	    $data['interval'] = $interval;
	    $this->ajaxReturn($data, 'JSONP');
	}
	
	/**
	 * 评价订单
	 */
	public function rate(){
	    $buyerId = $this->user('id');
	    $Model = new OrderModel();
	    $trade = $this->getTrade($Model, $buyerId);
	    if($trade['buyer_rate']){
	        $this->error('您已评价过了');
	    }
	    
	    // 默认数据
	    $postData = array('tid' => $trade['tid'], 'service' => 5, 'logistics' => 5, 'items' => array());
	    foreach ($trade['orders'] as $order){
	        $postData['items'][$order['oid']] = array(
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
	            'created'     => NOW_TIME
	        );
	    }
	    
	    // 好评/五星奖励积分
	    $rewardScore = project_config($trade['project']['id'], ProjectConfig::GOOD_RATE_SCORE);
	    if(!is_numeric($rewardScore)){
	        $rewardScore = 0;
	    }
	    
	    // 投放页面
	    if(IS_GET){
	        $this->assign('trade', $trade);
	         
	        $rateTip = $rewardScore == 0 ? '你的评价能帮助其他小伙伴哟' : '亲亲，好评可获得'.$rewardScore.$trade['project']['score_alias'].'奖励哦~';
	        $this->assign('rateTip', $rateTip);
	        
	        $this->assign('postData', json_encode($postData));
	        return $this->display();
	    }
	    
	    // 添加评价
	    $postData['service'] = intval($_POST['service']);
	    $postData['logistics'] = intval($_POST['logistics']);
	    if($postData['service'] < 1 || $postData['service'] > 5 || $postData['logistics'] < 1 || $postData['logistics'] > 5){
	        $this->error('店铺评分不在预期范围');
	    }
	    
	    $list = array();
	    foreach ($_POST['items'] as $oid=>$rate){
	        if(!isset($postData['items'][$oid])){
	            $this->error('子订单号不存在');
	        }
	        
	        $data                = $postData['items'][$oid];
	        $data['score']       = intval($rate['score']);
	        $data['feedback']    = $rate['feedback'];
	        $data['logistics']   = $postData['logistics'];
	        $data['service']     = $postData['service'];
	        $data['anonymous']   = $rate['anonymous'] ? 1 : 0;
	        
	        if($data['score'] < 1 || $data['score'] > 5){
	            $this->error('好中差评不在预期范围内');
	        }
	        $list[] = $data;
	    }
	    $Model->rateTrade($trade, $list, $rewardScore);
	    $this->success('评价成功！');
	}

	/**
     * 联系商家付款页面
     */
    public function buyerPay(){
        $shop_id = I('get.sid',0,'int');
        $payment = I('get.pay',0,'float');
        if($shop_id==0 || $payment==0){
            $this->error('参数错误');
        }
        $shop = M('shop_info')->where("id=".$shop_id)->find();
        $sh = M('shop')->where("id=".$shop_id)->find();
        $this->assign(array('shop_id'=>$shop_id,'wx_nick'=>$shop['wx_nick'],'wx_no'=>$shop['wx_no'],'payment'=>$payment,'mobile'=>$sh['service_hotline']));
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
    /**
     * 查看物流信息
     */
    public function checkLogistics(){
        $tid = I('get.tid');
        $trade = M('trade')->where(array('tid'=>$tid))->find();
        $shop = M('shop')->where(array('id'=>$trade['seller_id']))->find();
        $Model = new \Common\Model\AlibabaModel();
        $logistics = $Model->getLogistics($tid,$shop['aliid']);
        if(!empty($logistics['error'])){
            $this->error($logistics['error']);
        }
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
}
?>