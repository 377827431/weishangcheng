<?php
namespace Service\Controller;

use Common\Common\CommonController;
use Org\Alibaba\AlibabaAuth;
use Think\Cache\Driver\Redis;
use Common\Model\AlibabaModel;
use Common\Model\MessageType;

/**
 * 阿里接口
 */
class AliController extends CommonController{
    
    /**
     * 更新1688店铺(微供)
     */
    public function syncShopGoods(){
        $sign = create_sign($_GET);
        if($sign != $_GET['sign']){
            $this->error('签名错误');
        }
        
        // 设置15分钟超时，但是程序到10分钟后自动重启
        set_time_limit(900);
        
        $hours = date('H');
        $maxRows = $hours > 23 || $hours < 8 ? 499 : 999;
        
        $tokenId = $_POST['token_id'];
        $loginList = json_decode($_POST['logins'], true);
        $AlibabaAuth = new AlibabaAuth($tokenId);
        
        $redis = new Redis();
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        
        $limit   = 100;
        $channel = 'SyncAliGoods';
        $rows    = 0;
        foreach ($loginList as $i=>$loginId){
            $offset = 0;
            do{
                // 读取商品id
                $list = $AlibabaAuth->getShopGoods($loginId, $offset, $limit);
                // 放入消息队列等待更新(防止数据量过大)
                $redis->lPush($channel, $tokenId, $list);
                
                $count     = count($list);
                $offset   += $count;
                $rows     += $count;
            }while($count == $limit);
            unset($loginList[$i]);
            
            if($rows > $maxRows){
                $rows = 0;
                $this->restart('syncGoods');
            }
            
            if($this->isTimeout() && count($loginList) > 0){
                $post = array('token_id' => $tokenId, 'logins' => json_encode($loginList, JSON_UNESCAPED_UNICODE));
                $this->restart(ACTION_NAME, $post);
                exit('超时结束');
            }
        }
        
        if($rows > 0){
            $this->restart('syncGoods');
        }
    }
    
    /**
     * 同步商品信息
     */
    public function syncGoods(){
        $sign = create_sign($_GET);
        if($sign != $_GET['sign']){
            $this->error('签名错误');
        }
        
        // 设置15分钟超时，但是程序到10分钟后自动重启
        set_time_limit(900);
        $redis = new Redis();
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        
        $channel = 'SyncAliGoods';
        $AlibabaModel= new AlibabaModel();
        
        while ($redis->lSize($channel)){
            $data = $redis->rPop($channel);
            $data = json_decode($data, true);
            print_data($data);
            
            $tokenId = $data[0];
            $list    = $data[1];
            foreach ($list as $offerId){
                $AlibabaModel->syncGoods($offerId, $tokenId);
            }
            
            // 如果已经超过10分钟则重新创建请求
            if($this->isTimeout()){
                $this->restart(ACTION_NAME);
                exit('超时结束');
            }
        }
    }
    
    /**
     * 检测是否超时(如果超时则自动重新发起请求)
     */
    private function isTimeout(){
        return time() - NOW_TIME > 600;
    }
    
    /**
     * 重新请求
     */
    private function restart($action, $post = null){
        $url = (is_ssl() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].__CONTROLLER__.'/'.$action;
        $param = array(
            'timestamp' => time(),
            'noncestr'  => \Org\Util\String2::randString(16)
        );
        $param['sign'] = create_sign($param);
        sync_notify($url, $param, $post);
    }
    
    /**
     * 同步商品类目
     */
    public function category($id = 0, $pids = '', $level = 1){
        static $AlibabaAuth = null;
        if(is_null($AlibabaAuth)){
            $sign = create_sign($_GET);
            if($sign != $_GET['sign']){
                $this->error('签名错误');
            }
            
            set_time_limit(0);
            $AlibabaAuth = new \Org\Alibaba\AlibabaAuth();
            
            $id = $_GET['id'];
            $pids = '';
            $level = 1;
        }
        
        $dataList = array();
        $Model = M('alibaba_category');
        $list = $AlibabaAuth->category($id);
        foreach ($list as $i=>$item){
            $data = array(
                'id'         => $item['categoryID'],
                'name'       => $item['name'],
                'level'      => $level,
                'parent_ids' => $pids,
                'child_ids'  => ''
            );
            
            if(isset($item['childIDs'])){
                $data['child_ids'] = implode(',', $item['childIDs']);
                foreach ($item['childIDs'] as $id){
                    $this->category($id, $pids.($pids ? ',' : '').$id, $level+1);
                }
            }
            $dataList[] = $data;
            
            if(($i+1) % 50 == 0){
                $Model->addAll($dataList);
                $dataList = array();
            }
        }
        
        if(count($dataList) > 0){
            $Model->addAll($dataList);
        }
    }
    
    /**
     * 批量同步某个1688账号的订单信息
     */
    public function syncOrder(){
    	$sign = create_sign($_GET);
    	if($sign != $_GET['sign'] || !is_numeric($_GET['token_id'])){
    		$this->error('签名错误');
    	}
    	
    	$tokenId   = $_GET['token_id'];
    	$page      = !is_numeric($_GET['page']) || $_GET['page'] < 1 ? 1 : $_GET['page'];
    	$startTime = $_GET['start'];
    	$endTime   = $_GET['end'];
    	
    	$Model = M();
    	if($page == 1){
    		$token = $Model->query("SELECT last_sync_order FROM alibaba_token WHERE id='{$tokenId}'");
    		if(!$token){
    			$this->error('token_id不存在');
    		}
    		$token = $token[0];
    		$Model->execute("UPDATE alibaba_token SET last_sync_order='".NOW_TIME."' WHERE id='{$tokenId}'");
    		
    		// 超过3天未更新则只更新最近三天的，防止长时间用户不适用本系统，会大量更新无用数据
    		$startTime = $token['last_sync_order'];
    		if(NOW_TIME - $token['last_sync_order'] > 24 * 3600 * 3){
    			$startTime = strtotime('-3 day');
    		}
    		$endTime = NOW_TIME;
    	}
    	
    	// 设置15分钟超时，但是程序到10分钟后自动重启
    	set_time_limit(900);
    	ignore_user_abort(true);
    	$redis = new Redis();
    	$redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
    	
    	// 订单状态  0待下单; 1.waitbuyerpay(等待卖家付款); 2.waitsellersend(等待卖家发货); 3.waitbuyerreceive(等待买家收货 ); 4.success; 5.cancel(交易取消，违约金等交割完毕)
    	$orderStatus= array('waitbuyerpay' => 1, 'waitsellersend' => 2, 'waitbuyerreceive' => 3, 'success' => 4, 'cancel' => 5);
    	
    	$interface = new AlibabaAuth($tokenId);
    	do{
    		$orders = $interface->getOrderList(array('page' => $page, 'size' => 50, 'modify_start' => $startTime, 'modify_end' => $endTime));
	    	
	    	$list = array();
	    	$ids  = '';
	    	foreach ($orders['result'] as $item){
	    		$order = $item['baseInfo'];
	    		$list[$order['id']] = array(
	    				'status'        => $order['status'],
	    				'refund'        => $order['refund'],
	    				'pay_time'      => strtotime(substr($order['payTime'], 0, 14)),
	    				'payment'       => sprintf('%.2f', $order['totalAmount']),
	    				'shipping_fee'  => sprintf('%.2f', $order['shippingFee']),
	    				'delivered'     => $order['allDeliveredTime'] ? strtotime(substr($order['allDeliveredTime'], 0, 14)) : 0
	    		);
	    		
	    		$ids .= $order['id'].',';
	    	}
	    	$ids = rtrim($ids, ',');
	    	
	    	// 与数据库数据进行对比，判断是否需要更新本地数据
	    	$sql = "SELECT alitrade.id, alitrade.`status`, alitrade.refund, alitrade.pay_time, alitrade.payment,
						alitrade.shipping_fee, alitrade.delivered, trade.express_no, trade.tid,
						trade.buyer_id, trade.seller_id, wx_user.appid, wx_user.openid, wx_user.subscribe, project.host, project_appid.alias
					FROM alibaba_trade AS alitrade
					INNER JOIN trade ON trade.tid=alitrade.tid
					LEFT JOIN wx_user ON wx_user.appid=trade.buyer_appid AND wx_user.mid=trade.buyer_id
					LEFT JOIN project_appid ON project_appid.id=SUBSTR(trade.seller_id, 1, LENGTH(trade.seller_id) - 3) AND project_appid.appid=trade.buyer_appid
					LEFT JOIN project ON project.id=project_appid.id
					WHERE alitrade.id IN({$ids})";
	    	$dbList = $Model->query($sql);
	    	foreach ($dbList as $sys){
	    		$id  = $sys['id'];
	    		$ali = $list[$id];
	    		if(!isset($orderStatus[$ali['status']])){continue;}
	    		$status = $orderStatus[$ali['status']];
	    		
	    		if( $sys['status']       != $status ||
	    		    $sys['refund']       != $ali['refund'] ||
	    			$sys['pay_time']     != $ali['pay_time'] ||
	    			$sys['payment']      != $ali['payment'] ||
	    			$sys['shipping_fee'] != $ali['shipping_fee'] ||
	    			$sys['delivered']    != $ali['delivered']
	    		){
	    			$sql = "UPDATE alibaba_trade SET
							`status`='{$status}',
							 refund='{$ali['refund']}',
							 pay_time='{$ali['pay_time']}',
							 payment='{$ali['payment']}',
							 shipping_fee='{$ali['shipping_fee']}'
							WHERE id='{$id}'";
	    			$Model->execute($sql);
	    			
	    			// 如果已发货，本系统还是待付款和待发货状态(获取物流信息)
	    			if($sys['delivered'] > 0 && ($sys['status'] == 1 || $sys['status'] == 2)){
	    				$logistics = $interface->getLogisticsInfos($id);
	    				if(isset($logistics['errcode'])){
	    					sleep(1);
	    					$logistics = $interface->getLogisticsInfos($id);
	    					if(isset($logistics['errcode'])){
	    						continue;
	    					}
	    				}
	    				
	    				$address = '';
	    				$express = decode_json($sys['express_no']);
	    				foreach ($logistics as $logistic){
	    					$express[$logistic['logisticsBillNo']] = $logistic['logisticsCompanyName'];
	    					
	    					$receiver = $logistic['receiver'];
	    					$address = '收货信息：'.$receiver['receiverName'].' '.$receiver['receiverMobile'].' '.$receiver['receiverProvince'].' '.$receiver['receiverCity'].' '.$receiver['receiverCounty'].' '.$receiver['receiverAddress'];
	    				}
	    				$Model->execute("UPDATE trade SET express_no='".encode_json($express)."', `status`=IF(`status`=3 OR `status`=4, 6, `status`), consign_time=IF(consign_time>0,consign_time,{$sys['delivered']}), modified=".time()." WHERE tid='{$sys['tid']}'");
	    				$redis->lPush('TradeCopy', $sys['tid']);
	    				
	    				// 发货消息提醒
	    				$this->lPublish('MessageNotify', array(
	    					'type'         => MessageType::ORDER_SEND_GOODS,
	    					'openid'       => $sys['openid'],
	    					'appid'        => $sys['appid'],
	    					'data'         => array(
	    						'url'      => $sys['host'].'/'.$sys['alias'].'/order/detail?tid='.$sys['tid'],
	    						'title'    => '您的订单已发货，请保持收货人手机号畅通！',
	    						'tid'      => $sys['tid'],
	    						'name'     => current($express),
	    						'no'       => key($express),
	    						'remark'   => $address
	    					)
	    				));
	    			}
	    		}
	    	}
	    	$page++;
	    	
	    	// 检测运行时间，如果已经超过10分钟则重新创建请求
	    	if($this->isTimeout()){
	    		$url = (is_ssl() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].__CONTROLLER__.'/syncOrder';
	    		$param = array(
	    			'timestamp' => time(),
	    			'noncestr'  => \Org\Util\String2::randString(16),
	    			'token_id'  => $tokenId,
	    			'page'      => $page,
	    			'start'     => $startTime,
	    			'end'       => $endTime
	    		);
	    		$param['sign'] = create_sign($param);
	    		sync_notify($url, $param);
	    		break;
	    	}
    	}while (count($orders) == 50);
    	
    	$redis->publish('TradeCopy');
    	$redis->publish('MessageNotify');
    	exit();
    }
}
?>