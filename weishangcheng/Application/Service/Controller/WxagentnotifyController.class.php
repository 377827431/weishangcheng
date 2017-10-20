<?php
namespace Service\Controller;
use Org\WxPay\WxPayNotify;
use Org\WxPay\WxPayOrderQuery;
use Org\WxPay\WxPayApi;
use Org\Wechat\WechatAuth;
use Common\Model\BalanceModel;

class WxagentnotifyController extends WxPayNotify
{
    public function index(){
        $this->Handle(false);
    }

    //查询订单
    public function Queryorder($transaction_id)
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input);
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {
        // 保存通知记录
        M('wx_pay_notify')->add($data);
        $notfiyOutput = array();
        
        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            return false;
        }
        
        //查询订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"])){
            $msg = "订单查询失败";
            return false;
        }
        
        $result = $this->save($data);
        if($result === true){
            return true;
        }else{
            $msg = $result;
            return false;
        }
    }
    
    /**
     * 保存数据
     * @param unknown $data
     * @return string|boolean
     */
    private function save($data){
        $Model = M('member_recharge');
        $Model->startTrans();
        // 更新用户关注状态
        $subscrib = $data['is_subscribe'] == 'Y' ? 1 : 0;
        $Model->execute("UPDATE wx_user SET subscribe={$subscrib} WHERE openid='{$data['openid']}'");
        
        // 查询订单
        $tid = $data['out_trade_no'];
        $recharge = $Model->find($tid);
        
        if(empty($recharge)){
            return '订单不存在';
        }else if($recharge['status'] != 'topay'){
            return '订单状态：'.$recharge['status'];
        }
        
        //修改订单状态
        $Model->execute("UPDATE member_recharge SET `status`='success' WHERE tid='{$tid}'");
        //修改代理等级
        $Model->execute("UPDATE member SET agent_level={$recharge['agent_level']} WHERE id={$recharge['buyer_id']}");
        
        // 查找上级是否有全返金额名额
        $allToParent = false;
        $parent = $Model->query("SELECT id, agent_level FROM member WHERE id=(SELECT pid FROM member WHERE id={$recharge['buyer_id']})");
        if(!empty($parent) && $parent[0]['agent_level'] > 0){
            $parent = $parent[0];
            $agent_total_field = 'agent_'.$recharge['agent_level'].'_total';
            $agent_used_field = 'agent_'.$recharge['agent_level'].'_used';
            $inviteRecharge = $Model->query("SELECT * FROM member_invite_recharge WHERE mid='{$parent['id']}' AND {$agent_total_field}>{$agent_used_field} LIMIT 1");
            if(!empty($inviteRecharge)){
                $inviteRecharge = $inviteRecharge[0];
                $result = $Model->execute("UPDATE member_invite_recharge SET `{$agent_used_field}`={$agent_used_field}+1 WHERE mid='{$parent['id']}' and $agent_used_field<{$agent_total_field}");
                $allToParent = $result > 0;
            }
        }
        
        if(!$allToParent){
            $Model->execute("UPDATE member SET agent_level={$recharge['agent_level']} WHERE id={$recharge['buyer_id']}");
            $list = json_decode($recharge['detail'], true);
            $BalanceModel = D('Balance');
            foreach($list as $level=>$item){
                if($item['money'] <= 0){
                    continue;
                }
            
                $balance = $item['id'] == $recharge['buyer_id'] ?
                array(
                    'mid'        => $item['id'],
                    'reason'     => '升级系统赠送',
                    'type'       => 'agent_up',
                    'no_balance' => $item['money']
                ) :
                array(
                    'mid'        => $item['id'],
                    'reason'     => $level.'级好友冲值升级',
                    'balance'    => $item['money'],
                    'type'       => 'agent_tj'
                );
            
                $BalanceModel->add($balance);
            }
        }
        $Model->commit();
        
        if($allToParent){
            $this->sendMssage($tid, $inviteRecharge['agent_'.$recharge['agent_level'].'_money'], $parent['id']);
        }
        return true;
    }
    
    /**
     * 发送消息提醒
     * @param unknown $tid
     * @param unknown $balance
     * @param unknown $buyer_id
     */
    private function sendMssage($tid, $balance ,$buyer_id){
        if($balance == 0){
            return;
        }
        
        $Model = new BalanceModel();
        $Model->add(array(
            'mid'       => $buyer_id,
            'reason'    => '好友充值升级',
            'balance'   => $balance,
            'type'      => 'agent_tj'
        ));
    
        $wxUser = $Model->getWXUserConfig($buyer_id);
        if(empty($wxUser['config'])){
            return;
        }
        $sql = "SELECT (agent_4_total-agent_4_used) as leave_4,(agent_3_total-agent_3_used) as leave_3,(agent_2_total-agent_2_used) as leave_2 
                        from member_invite_recharge where mid=(SELECT pid FROM member WHERE id={$buyer_id})";
        $leave =  $Model->query($sql);
        $leave = $leave['0'];
        $wechatAuth = new WechatAuth($wxUser['config']['WEIXIN']);
        $config = $wxUser['config'];
        $message = array(
            'template_id' => $config['WX_TEMPLATE']['TM00335'],
            'url' => $config['HOST'].'/h5/balance',
            'data' => array(
                'first'        => array('value' => '您有新积分到账，详情如下。', 'color' => '#173177'),
                'account'      => array('value' => '当前账户'),
                'time'         => array('value' => date('Y年m月d日 H:i')),
                'type'         => array('value' => '一级好友充值升级'),
                'creditChange' => array('value' => '到账'),
                'number'       => array('value' => $balance.'积分'),
                'creditName'   => array('value' => '积分'),
                'amount'       => array('value' => '***'),
                'remark'       => array('value' => '积分已到账，下级贵宾名额还剩'.$leave['leave_4'].'个，下级会员名额还剩'.$leave['leave_3'].'个，下级员工名额还剩'.$leave['leave_2'].'个，点击查看详情。')
            )
        );
        $wechatAuth->sendTemplate($wxUser['openid'], $message);
    }
}
?>