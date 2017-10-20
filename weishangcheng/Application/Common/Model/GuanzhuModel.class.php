<?php 
namespace Common\Model;

use Org\Wechat\WechatAuth;
/**
 * 关注
 * @author lanxuebao
 *
 */
class GuanzhuModel extends BaseModel{
    protected $tableName = 'member';
    
    /**
     * 绑定 推荐人 和 被推荐人,如果被推荐人是第一次进入平台，则给推荐人1毛钱
     * 本方法返回的值都用于wechatController
     * @param unknown $user
     * @param unknown $shareMember
     */
    public function shareGuanzhu($user, $share_mid){
        if(empty($user['id'])){
            $this->error = '数据不存在！';
            return -1;
        }else if($user['agent_level'] > 0){
            $this->error = '您已成为代理，无需绑定上级好友';
            return -1;
        }else if($user['id'] == $share_mid){
            $this->error = '您不能绑定您自己！';
            return -1;
        }else if($user['pid'] == $share_mid){
            $this->error = '您已与推荐人为好友关系!';
            return -1;
        }else if($user['pid'] > 0){
            $this->error = '不可重新绑定推荐人！';
            return -1;
        }
        
        //查询分享人信息
        $shareMember = $this->getWXUserConfig($share_mid);
        if(empty($shareMember)){
            $this->error = '二维码无效：推荐人不存在';
            return -1;
        }else if($shareMember['pid'] == $user['id']){
            $this->error = '绑定失败：推荐人不能为您的下级好友';
            return -1;
        }
        
        //验证分享人是否是登录人的下线
        $is_my_child = $this->query("select isMyChild({$shareMember['id']},{$user['id']}) AS is_my_child");
        if($is_my_child[0]["is_my_child"] != 0){
            $this->error = '绑定失败：上下级关系为死循环';
            return -1;
        }
    
        $this->execute("UPDATE member SET pid=".$shareMember['id']." WHERE id=".$user['id']);
        
        $content = '【'.$user['nickname'].'】已成为您的好友！';
        if($user['is_new'] == 1 && $user['subscribe'] == 1){
            $maxMoney = 40;     // 每天最多n元货款
            $onceMoney = 0.2;   // 每次对多n元
            if(NOW_TIME >= 1480557600 && NOW_TIME <= 1481731199){
                $maxMoney = 100;
                $onceMoney = 0.5;
            }
            
            $today = date('Y-m-d');
            $sql = "SELECT SUM(money) AS total_balance
                    FROM member_balance
                    WHERE mid={$share_mid} AND create_time BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59' AND type='tjhy'
                    LIMIT 200";
            $record = $this->query($sql);
            $sendedMoney = is_numeric($record[0]['total_balance']) ? $record[0]['total_balance'] : 0;
            if($sendedMoney < $maxMoney){
                $content .= "\r\n系统奖励您{$onceMoney}个积分\r\n(仅可用于购买商品，不可提现)";
                
                $balanceModel = new BalanceModel();
                $balanceModel->add(array(
                    'mid'          => $shareMember['id'],
                    'no_balance'   => $onceMoney,
                    'type'         => 'tjhy',
                    'reason'       => '邀请的好友【'.mb_substr($user['nickname'], 0, 4, 'utf8').'】第一次关注公众号'
                ));
            }else{
                $content .= "\r\n每日推荐好友最多奖励{$maxMoney}个积分，已达上限系统不再派送货款积分，感谢您的支持！";
            }
        }else{
            $content .= "\r\n此好友不是第一次进入本公众号！";
        }
        
        if(!empty($shareMember['config'])){
            $wechatAuth = new WechatAuth($shareMember['config']['WEIXIN']);
            $wechatAuth->messageCustomSend($shareMember['openid'], $content);
        }
        
        return $shareMember;
    }
}