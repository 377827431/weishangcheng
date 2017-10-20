<?php
namespace Admin\Model;

use Think\Model;
use Common\Model\BalanceModel;
use Common\Model\BalanceType;
use Common\Model\BaseModel;
use Common\Model\MessageType;
use Common\Model\OrderStatus;

/**
 * 会员订阅通知
 * @author Administrator
 *
 */
class MemberSubscribe extends BaseModel{
    
    public function balance($record){
        static $BalanceModel = null;
        if(is_null($BalanceModel)){
            $BalanceModel = new \Common\Model\BalanceModel();
        }
        $BalanceModel->add($record, true);
    }
    
    public function checkLevelup($memberId, $projectId){
        if(!is_numeric($memberId) || !is_numeric($projectId)){
            E('参数错误');
        }
        $member = $this->getProjectMember($memberId, $projectId);
        $project = $member['project'];
        
        $cards = get_member_card($projectId);
        $nowCard = $targetCard = $cards[$member['card_id']];
        foreach ($cards as $cardId=>$target){
            if($target['level'] <= $targetCard['level']){
                continue;
            }else if(($target['auto_trade'] > 0 && $member['sum_trade'] >= $target['auto_trade'])
                || ($target['auto_payment'] > 0 && $member['sum_paid'] >= $target['auto_payment'])
                || ($target['auto_score'] > 0 && $member['sum_score'] >= $target['auto_score'])){
                $targetCard = $target;
            }
        }
        
        // 未达到升级条件
        if($targetCard['level'] <= $nowCard['level']){
            return;
        }
        
        // 升级
        $result = $this->execute("UPDATE project_member SET card_id={$target['id']}, card_expire='".($target['expire_time'] == 0 ? 0 : strtotime('+'.$target['expire_time'].' day'))."' WHERE project_id='{$projectId}' AND mid='{$memberId}'");
        if($result != 1){
            E('自动升级失败');
        }

        $discription = array();
        // 达到一定等级赠送会员积分和不可提现资金
        if($targetCard['give_wallet'] > 0 || $targetCard['give_score'] > 0){
            $this->balance(array(
                'mid'        => $member['id'],
                'project_id' => $projectId,
                'type'       => BalanceType::LEVEUP,
                'reason'     => '自动升级，平台赠送',
                'wallet'     => $targetCard['give_wallet'],
                'score'      => $targetCard['give_score']
            ));

            if($targetCard['give_wallet'] > 0){
                $discription[] = $project['wallet_alias'].floatval($targetCard['give_wallet']).'元';
            }
            if($targetCard['give_score'] > 0){
                $discription[] = floatval($targetCard['give_score']).$project['score_alias'];
            }
        }
        
        // 赠送优惠券
        if($targetCard['give_coupon'] > 0){
            $this->lPublish('CouponGive', $targetCard['give_coupon'], $member['id'], '自动升级为“'.$targetCard['title'].'”，平台赠送');
        }

        // 发送模板消息
        if(!$member['subscribe']){
            return;
        }
        $discription = count($discription) > 0 ? '，同时平台赠送您'.implode('+', discription) : '';
        
        $this->lPublish('MessageNotify', array(
            'type'   => MessageType::MEMBER_GRADE_CHANGE,
            'openid' => $member['openid'],
            'appid'  => $member['appid'],
            'data'   => array(
                'url'       => $member['url'].'/personal',
                'title'     => '亲爱的'.$member['name'].'您已成自动升级为'.$targetCard['title'],
                'old_grade' => $nowCard['title'],
                'new_grade' => $targetCard['title'],
                'time'      => date('Y-m-d H:i'),
                'remark'    => '恭喜您已自动升级'.$discription.'！感谢您的支持，祝您生活愉快！'
            )
        ));
    }
    
    /**
     * 购买了会员卡
     */
    public function buyCard($tid, $mid, $newCardId){
        if(!is_numeric($tid) || !is_numeric($mid) || !is_numeric($newCardId)){
            E('参数不为数字');
        }
        
        $projectId = substr($newCardId, 0, -1);
        $member = $this->getProjectMember($mid, $projectId);
        if(!$member['binded']){
            E('会员未与店铺绑定关系');
        }
        
        // 更新用户会员卡
        $cards = get_member_card($projectId);
        $newCard = $cards[$newCardId];
        $oldCard = $cards[$member['card_id']];
        $this->execute("UPDATE project_member SET card_id={$newCardId} WHERE project_id={$projectId} AND mid={$mid}");

        // 标记订单发货
        $tradeStatus = OrderStatus::SUCCESS;
        $timestamp = time();
        $this->execute("UPDATE trade SET `status`={$tradeStatus}, consign_time={$timestamp},receive_time={$timestamp},
            shipping_type='virtual', modified={$timestamp} WHERE tid='{$tid}'");

        // 通知计算佣金
        $this->lPublish('CommisionSettlement', $tid);
        // 复制订单数据
        $this->lPublish('TradeCopy', $tid);
        
        // 保存级别变更日志
        $sql = "INSERT INTO member_change SET
		        mid={$member['id']},
		        old_level='{$oldCard['id']}',
		        old_title='{$oldCard['title']}',
		        new_level='{$newCard['id']}',
		        new_title='{$newCard['title']}',
		        reason='购买会员卡{$tid}',
		        created='".date('Y-m-d H:i:s')."',
                username='system'";
        $this->execute($sql);
        
        // 升级赠送
        $zengsong = '';
        if($oldCard['id'] < $newCard['id']){
            if($newCard['give_score'] > 0 || $newCard['give_wallet'] > 0){
                $this->balance(array(
                    'project_id' => $projectId,
                    'mid'        => $mid,
                    'type'       => BalanceType::LEVEUP,
                    'reason'     => '升级为“'.$newCard['title'].'”系统赠送',
                    'wallet'     => $newCard['give_wallet'],
                    'score'      => $newCard['give_score']
                ));
            }
        
            // 赠送优惠券
            if($newCard['give_coupon'] > 0){
                $this->lPublish('CouponGive', $newCard['give_coupon'], $member['id'], '升级为“'.$newCard['title'].'”，平台赠送');
            }
        }
        
        if(!$member['subscribe']){
            return;
        }
        
        $this->lPublish('MessageNotify', array(
            'type'   => MessageType::MEMBER_GRADE_CHANGE,
            'openid' => $member['openid'],
            'appid'  => $member['appid'],
            'data'   => array(
                'url'       => $member['url'].'/personal',
                'title'     => $oldCard['id'] < $newCard['id'] ? '恭喜您已成为'.$newCard['title'] : '会员等级变更提醒',
                'old_grade' => $oldCard['title'],
                'new_grade' => $newCard['title'],
                'time'      => date('Y-m-d H:i')
            )
        ));
    }
}
?>