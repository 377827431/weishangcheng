<?php
namespace H5\Controller;
use Common\Common\CommonController;
use Org\Wechat\WechatAuth;
use Common\Model\BalanceModel;
use Org\IdWork;
use Common\Model\BalanceType;

/**
 * 资金流水记录
 * @author 兰学宝
 *
 */
class BalanceController extends CommonController
{
    private  $stepMin = 3600; // 提现间隔秒数
    private  $oneMax  = 200; // 每次最多提现金额
    private  $dayTimes = 3; // 每日最多提现次数
    
    public function index(){
        $userId = $this->user('id');
        $project = get_project(APP_NAME);
        
        $Model = new BalanceModel();
        $member = $Model->getProjectMember($userId, $project['id']);
        $btn = $this->canTransfers($Model, $member);
        
        if(IS_AJAX){
            $limit  = I('get.size/d', 0);
            $offset = I('get.offset/d', 50);
            $where  = array('mid' => $userId, 'project_id' => $project['id']);
            $list   = $Model->getMyRecord($where, $offset, $limit);
            $result = array(
                'user'          => $member,
                'rows'          => $list,
                'btn'           => $btn,
                'score_alias'   => $project['score_alias'],
                'balance_alias' => $project['balance_alias'],
                'wallet_alias'  => $project['wallet_alias']
            );
            $this->ajaxReturn($result);
        }

        $this->assign(array(
            'user'     => $member,
            'btn'      => $btn,
            'step_min' => $this->stepMin,
            'oneMax'   => $this->oneMax,
            'dayTimes' => $this->dayTimes,
            'score_alias'   => $project['score_alias'],
            'balance_alias' => $project['balance_alias'],
            'wallet_alias'  => $project['wallet_alias']
        ));
        $this->display();
    }
    
    /**
     * 是否可提现
     */
    private function canTransfers($Model, &$member){
        // 账户总余额
        $member['total_balance'] = bcadd($member['balance'], $member['wallet'], 2);
        // 立即提现按钮是否可用
        $btn = array('disabled' => floatval($member['balance']) <= 0, 'seconds' => 0, 'errmsg' => '');
        
        $hours = date('H');
        if($hours < 8 || $hours > 19){
            $btn['disabled'] = 1;
            $timestamp = $hours < 8 ? NOW_TIME : strtotime('+1 day');
            $btn['seconds'] = strtotime(date('Y-m-d 08:00:00', $timestamp)) - NOW_TIME;
            $btn['errmsg'] = '每日8点到18点可申请提现';
        }


        if(floatval($member['balance']) < 0.01){
            $btn['seconds'] = 0;
        }else{
            // 每日最多可提现
            $today = date('Y-m-d');
            $sql = "SELECT COUNT(*) AS times, MAX(payment_time) AS prev_time
                    FROM wx_transfers
                    WHERE payment_time BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59'
                    AND mid = {$member['id']}
                    AND result_code='SUCCESS'";
            $today = $Model->query($sql);
            $today = $today[0];
            if($today['times'] > 0){
                if($today['times'] >= $this->dayTimes){
                    $member['can_transfers'] = 0;
                    $btn['disabled'] = 1;
                    $btn['errmsg'] = '每日最多可申请'.$this->dayTimes.'次提现';
                }else{
                    $interval = $this->stepMin - (NOW_TIME - strtotime($today['prev_time']));
                    if($interval > 0){
                        $btn['disabled'] = 1;
                        $btn['seconds']  = $interval;
                        $btn['errmsg'] = '操作频繁，请'.(ceil($interval/60)).'分钟后再试';
                    }
                }
            }
        }
        
        // 今日可提现金额
        $max = $this->dayTimes * $this->oneMax;
        $member['can_transfers'] = $max > floatval($member['balance']) ? $member['balance'] : sprintf('%.2f', $max);
        // 本次可提现
        $member['this_times']    = $this->oneMax > floatval($member['can_transfers']) ? $member['can_transfers'] : $this->oneMax;
        
        if(floatval($member['this_times']) < 1){
            $btn['disabled'] = 1;
            $btn['errmsg'] = '账户余额不足1元，无法提现';
        }
        return $btn;
    }

    /**
     * 转账-提现
     */
    public function transfers(){
        $user = $this->user();
        
        // 数据校验
        $amount = floatval($_POST['amount']);
        if($amount < 1 || $amount > $this->oneMax){
            $this->error('单笔提现应在1~'.$this->oneMax.'元之间');
        }
        
        $project = get_project(APP_NAME);
        $Model = new BalanceModel();
        $member = $Model->getProjectMember($user['id'], $project['id']);
        $btn = $this->canTransfers($Model, $member);
        if($btn['disabled']){
            $this->error($btn['errmsg']);
        }if(floatval($member['balance']) < $amount){
            $this->error('您的余额不足，最多可提现：'.$member['balance'].'元');
        }

        // session标记，防止频繁提交
        $sessionTime = session('transfers');
        if(is_numeric($sessionTime) && $sessionTime + 60 > NOW_TIME){
            $this->error('操作频繁，请稍后再试');
        }
        session('transfers', NOW_TIME);
        
        // 开始提现
        $appid  = $project['third_mpid'];
        $openid = $user[$appid]['openid'];
        $config = get_wx_config($appid);
        
        $tid = IdWork::nextOutTid();
        $wxTransfers = new \Org\WxPay\WXTransfers($config);
        $wxTransfers->setReUserName($member['name']);
        $wxTransfers->setDesc($member['id']."余额提现");
        $wxTransfers->setTradeNo($tid);
        $result = $wxTransfers->transfers($openid, $amount);
        
        // 保存微信提现结果
        $result['mid'] = $member['id'];
        $result['openid'] = $openid;
        $result['amount'] = $amount;
        $result['balance'] = $member['balance'];
        if(empty($result['payment_time'])){
            $result['payment_time'] = date('Y-m-d H:i:s');
        }
        $Model->add($result);
        
        if($result['result_code'] != 'SUCCESS'){
            if($result['err_code'] == 'NOTENOUGH'){
                return $this->msgToAdmini($Model, $project);
            }
            $this->error(empty($result['return_msg']) ? '系统繁忙，请稍后重试！' : $result['return_msg']);
        }
        
        // 扣除金额
        $Model->add(array(
            'mid'        => $member['id'],
            'project_id' => $project['id'],
            'balance' => -$amount,
            'reason'  => '提现扣款',
            'type'    => BalanceType::TRANSFERS
        ));
        $this->success('已提现到您的微信零钱中，注意查收！');
    }
    
    /**
     * 发送代办事情
     */
    public function msgToAdmini(BalanceModel $Model, $project){
        $appid = C('DEFAULT_APPID');
        $wxUser = $Model->query("SELECT appid, openid FROM wx_user WHERE mid=1 AND appid='{$appid}'");
        $wxUser = $wxUser[0];
        $config = get_wx_config($wxUser['appid']);
        
        $WechatAuth = new WechatAuth($config);
        $message = array(
            'template_id' => $config['template']['OPENTM401202033'],
            'url' => '',
            'data' => array(
                'first'    => array('value' => "可提现余额不足，请及时补给", 'color' => '#173177'),
                'keyword1' => array('value' => '待充值'),
                'keyword2' => array('value' => date('Y-m-d H:i:s')),
                'remark'   => array('value' => '微信商户平台['.$config['name'].']余额不足，请及时充值')
            )
        );
        $WechatAuth->sendTemplate($wxUser['openid'], $message);
    }
}
?>