<?php
namespace Admin\Model;

use Think\Model;
use Common\Model\OrderStatus;
use Common\Model\BaseModel;
use Common\Model\RefundStatus;
use Common\Model\ShopBalanceModel;
use Common\Model\BalanceType;
use Common\Model\MessageType;
use Common\Model\StaticModel;
use Common\Model\BalanceModel;

class CommisionSubscribe extends BaseModel{
    protected $tableName = 'trade_commision';
    private $memberCards = null;
    
    private function mergeMemberCard($member, $cards){
        $card = $cards[$member['card_id']];
        $member['settlement_type'] = $card['settlement_type'];
        $member['agent_rate'] = $card['agent_rate'];
        $member['agent_same'] = $card['agent_same'];
        $member['agent_rate2'] = $card['agent_rate2'];
        $member['agents'] = explode_string(',', $member['agents']);
        return $member;
    }
    
    /**
     * 添加佣金(p1必传，p2和p3请忽略)
     */
    public function add($tid = '', $p2 = null, $p3 = null){
        if(!is_numeric($tid)){
            E('订单号格式错误');
        }
        
        $sql = "SELECT tid, `status`, buyer_id, buyer_card_id, seller_id, paid_balance, paid_wallet, paid_fee, paid_score
                FROM trade WHERE tid='{$tid}'";
        $trade = $this->query($sql);
        if(empty($trade)){
            E('订单号不存在');
        }
        $trade = $trade[0];
        
        // 获取项目配置
        $project = get_project($trade['seller_id'], true);
        $trade['project_id'] = $project['id'];
        
        // 如果已存在则尝试结算
        $commision = $this->query("SELECT 1 FROM trade_commision WHERE seller_id='{$trade['seller_id']}' AND tid='{$trade['tid']}' LIMIT 1");
        if($commision){
            return;
        }
        
        // 下单人
        $xiadanren = $this->getProjectMember($trade['buyer_id'], $project['id']);
        if(!$xiadanren){
            E('下单人丢失:'.$trade['tid']);
        }
        
        // 获取会员卡
        $cards = $this->memberCards = get_member_card($project['id']);
        $xiadanren = $this->mergeMemberCard($xiadanren, $cards);
        
        $default = array('id' => 0, 'card_id' => 0, 'agents' => array(), 'pid' => 0);
        // 上一级
        $parent1 = $default;
        if($xiadanren['pid'] > 0){
            $parent1 = $this->getProjectMember($xiadanren['pid'], $project['id']);
            $parent1 = $this->mergeMemberCard($parent1, $cards);
        }
        
        // 上二级
        $parent2 = $default;
        if($parent1['pid'] > 0){
            $parent2 = $this->getProjectMember($parent1['pid'], $project['id']);
            $parent2 = $this->mergeMemberCard($parent2, $cards);
        }
        
        $timestamp = time();
        
        // 查找订单
        $sql = "SELECT `order`.goods_id, `order`.payment, `order`.payscore, `order`.oid,
                    `order`.type AS order_type, order.quantity, `order`.ext_params,
                    IF(ISNULL(agent.id), 0, 1) AS is_custom,
                    agent.reward_type, agent.reward_value, agent.settlement_type
                FROM trade_order AS `order`
                LEFT JOIN agent_goods AS agent ON agent.id=`order`.goods_id
                WHERE `order`.tid='{$trade['tid']}'";
        $list = $this->query($sql);
        
        $agentGroup = array();
        foreach ($list as $order){
            // 单独代理权佣金
            $isAgent = $this->singleAgent($trade, $order, $xiadanren, $parent1, $parent2);
            if(!$isAgent){
                $this->doAdd($trade, $order, $xiadanren, $parent1, $parent2);
            }
        }
        
        // 通知订单结算
        if($trade['status'] == OrderStatus::WAIT_SEND_GOODS || $trade['status'] == OrderStatus::WAIT_CONFIRM_GOODS || $trade['status'] == OrderStatus::SUCCESS){
            $this->lPublish('CommisionSettlement', $trade['tid']);
        }
    }
    
    /**
     * 单品代理
     */
    private function singleAgent($trade, $order, &$xiadanren, $parent1,  $parent2){
        $extparams = decode_json($order['ext_params']);
        if(!isset($extparams['agent']) || $extparams['agent']['target'] < 1){
            return false;
        }
        
        $result1 = $result2 = array();
        $agent = $extparams['agent'];
        
        // 查找单品代理设置
        static $agentGroup = array();
        if(!isset($agentGroup[$agent['id']])){
            $group = $this->query("SELECT id, relation, card_id, reward_first, reward_second, reward_type, settlement_type FROM agent_group WHERE id=".$agent['id']);
            $group = $group[0];
            if(!$group){
                return false;
            }
            
            $group['items']           = decode_json($group['items']);
            $group['reward_first']    = decode_json($group['reward_first']);
            $group['reward_second']   = decode_json($group['reward_second']);
            $agentGroup[$agent['id']] = $group;
        }
        
        $group = $agentGroup[$agent['id']];
        $group['target'] = $agent['target'];
        $group['current'] = $agent['current'];
        
        $isLevelUp = $group['target'] > $group['current'];
        $targetId = $group['id'].$group['target'];
        
        // 升级
        if($isLevelUp && !in_array($targetId, $xiadanren['agents'])){
            $levelup = explode(',', $targetId.($group['relation'] ? ','.$group['relation'] : ''));
            foreach($levelup as $id){
                if(!in_array($id, $xiadanren['agents'])){
                    $xiadanren['agents'][] = $id;
                }
            }
            
            // 执行升级
            $this->levelup($trade, $xiadanren, $group, $targetId);
        }
        
        // 如果没有上级或未开启结算则不做处理
        if($parent1['id'] < 1 || $group['settlement_type'] == 0){
            return false;
        }
        
        // 计算上一级是哪个级别
        $p1gid = 0;
        if($parent1['id'] > 0){
            foreach ($parent1['agents'] as $groupId){
                if(substr($groupId, 0, -1) == $group['id'].''){
                    $p1gid = $groupId;
                    break;
                }
            }
        }
        
        // 计算上二级是哪个级别
        $p2gid = 0;
        if($parent2['id'] > 0){
            foreach ($parent2['agents'] as $groupId){
                if(substr($groupId, 0, -1) == $group['id'].''){
                    $p2gid = $groupId;
                    break;
                }
            }
        }
        
        // 上两级相对此商品都无代理权则不处理
        if($p1gid == 0 && $p2gid == 0){
            return false;
        }
        
        // 奖励配置
        $reward_value = $isLevelUp? $group['reward_first'] : $group['reward_second'];
        
        // 计算上一级奖励，上二级奖励
        $p1Value = $p2Value = 0;
        $items = $group['items'];
        if(!empty($reward_value)){
            // 当前下单人相对于单品代理等级
            $glid = $group['current'] == 0 ? 0 :  $group['id'].$group['current'];
            // 获取上一上二奖励值(方法中已忽略为0的游客)
            $values = $this->getRewardValue($reward_value, $glid, $p1gid, $p2gid);
            $p1Value = $values[0];
            $p2Value = $values[1];
        }
        
        // 上级符合结算或未开启结算(使用默认结算)
        if($p1Value == 0 && $p2Value == 0){
            return false;
        }
        
        $title = $group['title'].$group['items'][$targetId]['title'];
        $data1 = $data2 = array(
            'oid'                 => $order['oid'],
            'mid'                 => 0,
            'seller_id'           => $trade['seller_id'],
            'tid'                 => $trade['tid'],
            'card_id'             => 0,
            'reward_type'         => $group['reward_type'],
            'reward_value'        => 0,
            'settlement_describe' => $isLevelUp ? '升级为'.$title : $title.'补货',
            'settlement_type'     => $group['settlement_type'],
            'settlement_created'  => time()
        );
        
        // 保存上一级奖励
        if($p1gid > 0 && $p1Value > 0){
            $data1['mid']          = $parent1['id'];
            $data1['card_id']      = $p1gid;
            $data1['reward_value'] = $p1Value;
            
            $money = 0;
            if($group['reward_type'] == 0){ // 按成交百分比
                $rate  = bcmul($p1Value, 0.01, 4);
                $money = bcmul($order['payment'], $rate, 2);
            }else{
                $money = bcmul($order['quantity'], $p1Value, 2);
            }
            $data1['settlement_describe'] .= ',预计收益'.$money.'元';
            parent::add($data1);
        }
        
        // 保存上二级奖励
        if($p2gid > 0 && $p2Value > 0){
            $data2['mid']          = $parent2['id'];
            $data2['card_id']      = $p2gid;
            $data2['reward_value'] = $p2Value;
            
            $money = 0;
            if($group['reward_type'] == 0){ // 按成交百分比
                $rate  = bcmul($p2Value, 0.01, 4);
                $money = bcmul($order['payment'], $rate, 2);
            }else{
                $money = bcmul($order['quantity'], $p2Value, 2);
            }
            $data2['settlement_describe'] .= ',预计收益'.$money.'元';
            parent::add($data2);
        }
        return true;
    }
    
    /**
     * 获取奖励值
     */
    private function getRewardValue($config, $card0, $card1, $card2){
        $result = array(0, 0);
        $key = isset($config[$card0]) ? $card0 : 'o';
        foreach ($config[$key] as $i=>$item){
            $key1 = isset($item['1_'.$card1]) ? '1_'.$card1: '1_o';
            $key2 = isset($item['2_'.$card2]) ? '2_'.$card2: '2_o';
            if(isset($item[$key1]) && isset($item[$key2])){
                if($card1 > 0){
                    $result[0] = floatval($item[$key1]);
                }if($card2 > 0){
                    $result[1] = floatval($item[$key2]);
                }
                break;
            }
        }
        return $result;
    }
    
    /**
     * 保存代理佣金
     */
    private function doAdd($trade, $order, $xiadanren, $parent1, $parent2){
        $reward_type     = 0;   // 按成交额百分比计算佣金
        $settlement_type = $parent1['settlement_type']; // 以上一级会员卡佣金结算方式为准
        $p1Value         = $parent1['agent_rate']; // 上一级佣金百分比
        $p2Value         = $parent1['card_id'] == $parent2['card_id'] ? $parent1['agent_same'] : $parent1['agent_rate2']; // 上二级佣金百分比
        
        // 设置了自定义佣金比例
        if($order['is_custom']){
            $reward_type     = $order['reward_type'];
            $settlement_type = $order['settlement_type'];
            $reward_value    = decode_json($order['reward_value']);
            
            $values  = $this->getRewardValue($reward_value, $xiadanren['card_id'], $parent1['card_id'], $parent2['card_id']);
            $p1Value = $values[0];
            $p2Value = $values[1];
        }
        
        $timestamp = time();
        $data1 = $data2 = array(
            'oid'                 => $order['oid'],
            'mid'                 => 0,
            'seller_id'           => $trade['seller_id'],
            'tid'                 => $trade['tid'],
            'card_id'             => 0,
            'reward_type'         => $reward_type,
            'reward_value'        => 0,
            'settlement_describe' => '',
            'settlement_type'     => $settlement_type,
            'settlement_created'  => $timestamp
        );
        
        if(($p1Value == 0 && $p2Value == 0) || $settlement_type == 0){
            if($settlement_type == 0){
                $data1['settlement_describe'] = '不参与结算';
                $data1['settlement_time']     = $timestamp;
            }else{
                $data1['settlement_describe'] = '不符合结算条件';
            }
            
            $data1['mid']          = $parent1['id'];
            $data1['card_id']      = $parent1['card_id'];
            if(empty($data1['settlement_type']) || !isset($data1['settlement_type'])){
                $data1['settlement_type'] = 0;
            }
            parent::add($data1);
            return;
        }
        
        // 保存上一级奖励
        if($parent1['id'] > 0 && $p1Value > 0){
            $data1['mid']          = $parent1['id'];
            $data1['card_id']      = $parent1['card_id'];
            $data1['reward_value'] = $p1Value;
            
            $money = 0;
            if($reward_type== 0){ // 按成交百分比
                $rate  = bcmul($p1Value, 0.01, 4);
                $money = bcmul($order['payment'], $rate, 2);
            }else{
                $money = bcmul($order['quantity'], $p1Value, 2);
            }
            $data1['settlement_describe'] = '预计收益'.$money.'元';
            if(empty($data1['settlement_type']) || !isset($data1['settlement_type'])){
                $data1['settlement_type'] = 0;
            }
            parent::add($data1);
        }
        
        // 保存上二级奖励
        if($parent2['id'] > 0 && $p2Value > 0){
            $data2['mid']          = $parent2['id'];
            $data2['card_id']      = $parent2['card_id'];
            $data2['reward_value'] = $p2Value;
            
            $money = 0;
            if($reward_type== 0){ // 按成交百分比
                $rate  = bcmul($p2Value, 0.01, 4);
                $money = bcmul($order['payment'], $rate, 2);
            }else{
                $money = bcmul($order['quantity'], $p2Value, 2);
            }
            $data2['settlement_describe'] = '预计收益'.$money.'元';
            if(empty($data2['settlement_type']) || !isset($data2['settlement_type'])){
                $data2['settlement_type'] = 0;
            }
            parent::add($data2);
        }
    }
    
    /**
     * 提示升级
     */
    private function levelup($trade, $member, $group, $targetId){
        $sql = "UPDATE project_member SET agents='".implode(',', $member['agents'])."'";
        if($group['card_id'] > $member['card_id']){
            $expire = $this->memberCards[$group['card_id']]['expire_time'];
            if($expire > 0){
                $expire = strtotime('+'.$expire.' day');
            }
            $sql .= ", card_id={$group['card_id']}, expire_time={$expire}";
        }
        
        // 更改代理等级
        $sql .= " WHERE project_id={$trade['project_id']} AND mid={$member['id']} AND FIND_IN_SET({$targetId}, agents)=0";
        $result = $this->execute($sql);
        if($result < 1){
            // 默认已存在此等级
            return false;
        }
        
        // 保存级别变更记录
        $oldTitle = $group['current'] == 0 ? '非代理' : $group['items'][$group['current']]['title'];
        $newTitle = $group['items'][$group['target']]['title'];
        $sql = "INSERT INTO member_change SET
                    mid={$member['id']},
                    old_level='".$group['id'].$group['current']."',
                    old_title='{$oldTitle}',
                    new_level='".$group['id'].$group['target']."',
                    new_title='{$newTitle}',
                    reason='升级为".$group['title'].'代理['.$trade['tid'].']'.($group['relation'] ? '，关联权限'.$group['relation'] : '')."',
                    created='".date('Y-m-d H:i:s')."',
                    username='system'";
        $this->execute($sql);
        
        // 放入消息队列
        $this->lPublish('MessageNotify', array(
            'type'         => MessageType::MEMBER_GRADE_CHANGE,
            'openid'       => $member['openid'],
            'appid'        => $member['appid'],
            'data'         => array(
                'url'      => $member['url'].'/personal',
                'title'    => '恭喜您已成为'.$group['title'].'“'.$newTitle.'”',
                'old_grade'=> $oldTitle,
                'new_grade'=> $newTitle,
                'time'     => date('Y-m-d H:i'),
                'remark'   => '感谢您的支持，祝您生活愉快！'
            )
        ));
        return true;
    }

    /**
     * 店铺结算
     * @param String $tid 
     */
    private function shopSettlement($trade){
        // 计算可结算金额
        $paidList = array('wallet' => $trade['paid_wallet'], 'weixin' => $trade['paid_fee'], 'balance' => $trade['paid_balance']);
        $prev = StaticModel::getNeedRefund($trade['refunded_fee'], $paidList);
        $now = array(
            'wallet'  => floatval(bcsub($paidList['wallet'], $prev['wallet'], 2)),
            'weixin'  => floatval(bcsub($paidList['weixin'], $prev['weixin'], 2)),
            'balance' => floatval(bcsub($paidList['balance'], $prev['balance'], 2))
        );
        $settlement = bcadd($now['weixin'], $now['balance'], 2);
        
        // 标记结算，如果已结算则不做处理
        $timestamp = time();
        $result = $this->execute("UPDATE shop_settlement SET `status`=1, balance={$settlement}, failed=0, modified={$timestamp} WHERE tid='{$trade['tid']}' AND `status`=0");
        if($result < 1){
        	return $trade['shop_balance'];
        }
        		
        // 减去店铺待结算
        $this->execute("UPDATE shop SET wait_settlement=wait_settlement-(SELECT ss.wait_settlement FROM shop_settlement AS ss WHERE ss.tid='{$trade['tid']}') WHERE id=".$trade['seller_id']);
        
        // 无资金变动不处理
        if(floatval($settlement) < 0.01){
            return $trade['shop_balance'];
        }
        
        // 增加店铺可用余额
        $ShopBalanceM = new ShopBalanceModel();
        $shop = $ShopBalanceM->add(array(
        	'shop_id'  => $trade['seller_id'],
        	'balance'  => $settlement,
        	'type'     => BalanceType::TRADE,
        	'reason'   => '订单结算-'.$trade['tid']
        ), true);
        
        // 扣除微信手续费
        $poundage = bcmul($now['weixin'], 0.006, 4);
        if(floatval($poundage) > 0){
        	$shop = $ShopBalanceM->add(array(
        		'shop_id'  => $trade['seller_id'],
        		'balance'  => -sprintf("%.2f", $poundage),
        		'type'     => BalanceType::POUNDAGE,
        		'reason'   => '微信手续费-'.$trade['tid'],
        		'username' => 'system'
        	));
        }
        return $shop['balance'];
    }
    
    /**
     * 结算
     */
    public function settlement($tid){
        if(!is_numeric($tid)){
            E('订单号格式错误');
        }
        
        $sql = "SELECT commision.oid, commision.mid, trade.tid, trade.buyer_id, trade.seller_id, trade.seller_name,
                    commision.reward_type, commision.reward_value, trade.status, commision.settlement_type,
                    `order`.quantity, `order`.payment, `order`.payscore, shop.mid AS shop_mid, commision.settlement_time,
                    IF(ISNULL(refund.refund_id), 0, refund.refund_status) AS refund_status,
                    refund.refund_fee, refund.refund_post, refund.refund_quantity, shop.balance AS shop_balance,
					trade.paid_balance, trade.paid_wallet, trade.paid_fee, trade.refunded_fee
                FROM trade
                INNER JOIN shop ON shop.id=trade.seller_id
                INNER JOIN trade_order AS `order` ON `order`.tid=trade.tid
                INNER JOIN trade_commision AS commision ON commision.oid=`order`.oid
                LEFT JOIN trade_refund AS refund ON refund.refund_id=commision.oid
                WHERE trade.tid='{$tid}' AND trade.`status`!=".OrderStatus::BUYER_CANCEL;
        
        $list = $this->query($sql);
        if(count($list) == 0){
            return;
        }

        // 本次处理的结算
        $trade = $list[0];
        $shopBalance = $trade['shop_balance'];
        $tradeStatus = $trade['status'];
        $allowType = array();
        switch ($tradeStatus){
            case OrderStatus::WAIT_SEND_GOODS:
                $allowType[] = 2;
                break;
            case OrderStatus::ALI_WAIT_PAY:
                $allowType[] = 2;
                break;
            case OrderStatus::WAIT_CONFIRM_GOODS:
                $allowType[] = array(2, 3);
                break;
            case OrderStatus::SUCCESS:
                $allowType = array(2, 3, 4);
                $shopBalance = $this->shopSettlement(array(
                    'tid'          => $trade['tid'],
                    'seller_id'    => $trade['seller_id'],
                    'paid_balance' => $trade['paid_balance'],
                    'paid_wallet'  => $trade['paid_wallet'],
                    'paid_fee'     => $trade['paid_fee'],
                    'refunded_fee' => $trade['refunded_fee'],
                    'shop_balance' => $trade['shop_balance']
                ));
                break;
            default:
                E('不在预期内通知订单结算');
        }
        
        
        $project = get_project($trade['seller_id'], true);

        $commisions = array();
        $totalSettlement = 0;
        $timestamp = time();
        foreach ($list as $item){
        	// 结算时间不在本次或已结算则忽略
        	if($item['settlement_time'] > 0 || !in_array($item['settlement_type'], $allowType)){
                continue;
            }
            
            $quantity = $item['quantity'];
            $payment  = $item['payment'];

            $refundStatus = $item['refund_status'];
            if($refundStatus > 0){
                if($refundStatus == RefundStatus::REFUNDED){
                    $quantity -= $item['refund_quantity'];
                    $payment = bcsub($payment, $item['refund_fee'], 2);
                }else if($refundStatus != RefundStatus::CANCEL_REFUND && $refundStatus != RefundStatus::REFUSED_REFUND){
                    // 退款中整个订单不做佣金处理
                    return;
                }
            }
            
            if($item['reward_type'] == 0){// 百分比
                $rate = bcdiv($item['reward_value'], 100, 4);
                $payment = bcmul($payment, $rate, 2);
            }else{
                $payment = bcmul($item['reward_value'], $quantity, 2);
            }

            // 放入数组中等待更新数据
            $commisions[] = array('oid' => $item['oid'], 'mid' => $item['mid'], 'balance' => $payment);
            $totalSettlement = bcadd($totalSettlement, $payment, 2);
        }

        // 判断店铺可用余额是否充足(佣金要从店铺可用余额扣除，避免旁氏骗局)
        $totalSettlement = floatval($totalSettlement);
        $shopBalance     = floatval($shopBalance);

        // 本次结算佣金超过店铺可用余额则不做佣金处理
        if($totalSettlement > $shopBalance){
            $member = $this->getProjectMember($trade['shop_mid'], $project['id']);
            if($member['subscribe']){
                $this->lPublish('MessageNotify', array(
                    'type'         => MessageType::BALANCE_CHANGE,
                    'openid'       => $member['openid'],
                    'appid'        => $member['appid'],
                    'data'         => array(
                        'url'      => '',
                        'title'    => '尊贵的'.$project['name'].'，您旗下的店铺可用余额不足',
                        'username' => $trade['seller_name'], // 用户名
                    	'time'     => date('Y年m月d日 H:i'), // 变动时间
                    	'value'    => '需'.sprintf('%2.f', $totalSettlement).'元', // 金额变动
                        'balance'  => $shopBalance, // 可用余额
                        'reason'   => '销售员推广佣金结算', // 变动原因
                        'remark'   => '请您及时充值，以免影响销售员的推广，相关订单号'.$tid.'。祝您生活愉快！'
                    )
                ));
            }
            
            // 标记佣金结算失败，等待充值后重新结算
            $this->execute("UPDATE shop_settlement SET failed=1, modified='{$timestamp}' WHERE tid='{$tid}'");
            return;
        }
        
        // 更改佣金结算状态
        $list = array();
        foreach($commisions as $item){
        	$mid     = $item['mid'];
        	$balance = floatval($item['balance']);
        	$sql = "UPDATE trade_commision SET settlement_time={$timestamp}, settlement_balance={$balance} WHERE oid={$item['oid']} AND mid={$mid} AND settlement_time=0";
            $result = $this->execute($sql);
            if($result > 0 && $balance > 0){
            	if(isset($list[$mid])){
            		$list[$mid] = bcadd($list[$mid], $balance, 2);
            	}else{
            		$list[$mid] = $balance;
            	}
            }
        }
        
        // 无需处理店铺和个人
        if(count($list) == 0){
            return;
        }
        
        $ShopBalanceM = new ShopBalanceModel();
        $BalanceModel = new BalanceModel();
        foreach ($list as $mid=>$balance){
        	// 扣除店铺的金额
        	$ShopBalanceM->add(array(
        		'shop_id'  => $trade['seller_id'],
        		'balance'  => -$balance,
        		'type'     => BalanceType::COMMISION,
        		'reason'   => '销售员['.$mid.']的推广佣金-'.$tid,
        	));
        	
        	// 增加个人资金
            $BalanceModel->add(array(
                'mid'        => $mid,
                'project_id' => $project['id'],
                'type'       => BalanceType::COMMISION,
            	'reason'     => '订单推广佣金-'.$tid,
                'balance'    => $balance
            ), true);
        }
        
        // 取消失败标记
        $this->execute("UPDATE shop_settlement SET failed=0, modified='{$timestamp}' WHERE tid='{$tid}'");
    }
}
?>