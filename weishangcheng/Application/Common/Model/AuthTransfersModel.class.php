<?php 
namespace Common\Model;
use Org\IdWork;

/**
 * 店铺资金流水
 * @author lanxuebao
 *
 */
class AuthTransfersModel extends BaseModel{
    protected $tableName = 'shop_balance';
    
    /**
     * 店铺资金自动提现
     */
    public function shopTransfers($record = array()){
        if($record['type'] == '12' || $record['type']=='10'){
            //判断是否为小B
            $projectId = substr($record['shop_id'],0,-3);
            $appid = $this->query("SELECT mch_appid FROM project_appid WHERE id={$projectId}");
            if($appid[0]['mch_appid'] == C('DEFAULT_WEIXIN') && $record['balance']>0){
                //小B
                $admin = $this->query("SELECT openid FROM admin_user WHERE shop_id = {$record['shop_id']}");
                if(empty($admin[0]['openid'])){
                    //没有openid 不能转账
                    return false;
                }else{
                    //计算转账金额(扣除手续费)
                    $shb = $this->query("SELECT balance FROM shop WHERE id={$record['shop_id']}");
                    $re_tixian = $shb[0]['balance']-ceil(($shb[0]['balance']*6/1000)*100)/100;
                    //判断是否符合微信转账的条件
                    $re = $this->transfers_rule($re_tixian,$record['shop_id'],$admin[0]['openid']);
                    if($re == false){
                        //不符合条件,跳出提现流程；
                        return false;
                    }
                    //提现
                    $user = array(
                        'appid'      => $appid[0]['mch_appid'],
                        'project_id' => $projectId,
                        'openid'     => $admin[0]['openid'],
                        'desc'       => $record['reason'].';自动转账',
                        'balance'    => $shb['balance'],
                        'no_balance' => 0,
                    );
                    $result = $this->transfers($user,$re_tixian,$record,'shop');
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    /**
     * 会员资金自动提现
     */
    public function memberTransfers($record=array()){
        if($record['type'] == '12' || $record['type']=='10'){
            //判断是否为小B
            $projectId = $record['project_id'];
            $appid = $this->query("SELECT mch_appid FROM project_appid WHERE id={$projectId}");
            if($appid[0]['mch_appid'] == C('DEFAULT_WEIXIN') && $record['add_balance']>0){
                //小B
                $shopId = $projectId.'001';
                $admin = $this->query("SELECT openid FROM admin_user WHERE shop_id = {$shopId}");
                if(empty($admin[0]['openid'])){
                    //没有openid 不能转账
                    return false;
                }else{
                    $pmb = $this->query("SELECT balance FROM project_member WHERE project_id={$record['project_id']} AND mid='{$record['mid']}'");
                    $re_tixian = $pmb[0]['balance']-ceil(($pmb[0]['balance']*6/1000)*100)/100;
                    //判断是否符合微信转账的条件
                    $re = $this->transfers_rule($re_tixian,$shopId,$admin[0]['openid']);
                    if($re == false){
                        //不符合条件,跳出提现流程；
                        return false;
                    }
                    //提现
                    $user = array(
                        'appid'      => $appid[0]['mch_appid'],
                        'project_id' => $projectId,
                        'openid'     => $admin[0]['openid'],
                        'desc'       => $record['reason'].';自动转账',
                        'balance'    => $pmb['balance'],
                        'no_balance' => 0,
                        'mid'        => $record['mid'],
                        );
                    $trResult = $this->transfers($user,$re_tixian,$record,'member');
                }
            }
            if($trResult != true){
                
                return false;
            }else{
                return true;
            }
        }
    }

    /**
     * 转账-提现
     */
    public function transfers($user,$amount,$record=array(),$type='shop'){
        $amount = $amount;    // 转账金额
        // 开始提现
        $config = get_wx_config($user['appid']);
        $wxTransfers = new \Org\WxPay\WXTransfers($config);
        $wxTransfers->setDesc($user['desc']);
        $result = $wxTransfers->transfers($user['openid'], $amount);
        
        // 保存微信提现结果
        $result['project_id'] = $user['project_id'];
        $result['mid'] = 0;
        $result['openid'] = $user['openid'];
        $result['amount'] = $amount;
        $result['balance'] = $user['balance'];
        $result['no_balance'] = $user['no_balance'];
        if(empty($result['payment_time'])){
            $result['payment_time'] = date('Y-m-d H:i:s');
        }
        foreach ($result as $key => $value) {
            $k[] = $key;
            $v[] = '"'.$value.'"';
        }
        $sql = "INSERT IGNORE INTO wx_transfers(".implode(',', $k).") VALUES(".implode(',',$v).")";
        $this->execute($sql);
        //如果提现成功，生成流水
        if($result['result_code'] == 'SUCCESS' && $result['return_code']== 'SUCCESS'){
            //如果为店铺提现
            if($type == 'shop'){
                //生成提现记录
                $this->execute("UPDATE shop SET balance=balance-{$record['balance']} WHERE id={$record['shop_id']}");
                $shop = $this->query("SELECT balance, mid, name FROM shop WHERE id={$record['shop_id']}");
                $shop = $shop[0];
                //计算提现金额,手续费
                $service_charge = ceil(($record['balance']*6/1000)*100)/100;
                $tixian = $record['balance'] - $service_charge;
                $this->execute("INSERT INTO shop_balance SET
                    created=".time().",
                    shop_id='{$record['shop_id']}',
                    add_balance='-{$tixian}',
                    balance='{$shop['balance']}',
                    type='13',
                    reason='{$record['reason']},实际提现',
                    username='{$record['username']}'");
                $this->execute("INSERT INTO shop_balance SET
                    created=".time().",
                    shop_id='{$record['shop_id']}',
                    add_balance='-{$service_charge}',
                    balance='{$shop['balance']}',
                    type='11',
                    reason='{$record['reason']},手续费',
                    username='{$record['username']}'");
                //判断是否有可提现余额
                $shb = $this->query("SELECT balance FROM shop WHERE id={$record['shop_id']}");
                if($shb[0]['balance']>0){
                    //有余额，添加到自动提现金额内,记录流水
                    $this->execute("UPDATE shop SET balance=balance-{$shb[0]['balance']} WHERE id={$record['shop_id']}");
                    $shop = $this->query("SELECT balance, mid, name FROM shop WHERE id={$record['shop_id']}");
                    $shop = $shop[0];
                    $service_chargeb = ceil(($shb[0]['balance']*6/1000)*100)/100;
                    $tixianb = $shb[0]['balance'] - $service_chargeb;
                    $this->execute("INSERT INTO shop_balance SET
                        created=".time().",
                        shop_id='{$record['shop_id']}',
                        add_balance='-{$tixianb}',
                        balance='{$shop['balance']}',
                        type='13',
                        reason='自动提现可结算金额,实际提现',
                        username='{$record['username']}'");
                    $this->execute("INSERT INTO shop_balance SET
                        created=".time().",
                        shop_id='{$record['shop_id']}',
                        add_balance='-{$service_chargeb}',
                        balance='{$shop['balance']}',
                        type='11',
                        reason='自动提现可结算金额,手续费',
                        username='{$record['username']}'");
                }
            }
            //如果为销售员提现
            if($type == 'member'){
                //生成提现记录
                $where = "WHERE project_id={$record['project_id']} AND mid={$record['mid']}";
                $sumScore = $record['add_score'] > 0 && $record['type'] != 'trade_refund' ? ", sum_score=sum_score+{$record['add_score']}" : "";
                $this->execute("UPDATE project_member SET balance=balance-{$record['add_balance']} {$sumScore} {$where}");
                $member = $this->getProjectMember(array('id' => $record['mid']), $record['project_id']);
                //计算提现金额,手续费
                $service_charge = ceil(($record['add_balance']*6/1000)*100)/100;
                $tixian = $record['add_balance'] - $service_charge;
                $result = $this->execute("INSERT INTO member_balance SET
                    add_balance='-{$tixian}',
                    add_wallet='0',
                    add_score='0',
                    project_id='{$record['project_id']}',
                    mid='{$record['mid']}',
                    balance='{$member['balance']}',
                    wallet='{$member['wallet']}',
                    score='{$member['score']}',
                    created=".time().",
                    `type`='13',
                    reason='{$record['reason']},实际提现'");
                $result = $this->execute("INSERT INTO member_balance SET
                    add_balance='-{$service_charge}',
                    add_wallet='0',
                    add_score='0',
                    project_id='{$record['project_id']}',
                    mid='{$record['mid']}',
                    balance='{$member['balance']}',
                    wallet='{$member['wallet']}',
                    score='{$member['score']}',
                    created=".time().",
                    `type`='11',
                    reason='{$record['reason']},手续费'");
                //判断是否有可提现余额
                $tixianb = 0;
                $pmb = $this->query("SELECT balance FROM project_member WHERE project_id={$record['project_id']} AND mid='{$record['mid']}'");
                if($pmb[0]['balance']>0){
                    //有余额,记录流水
                    $this->execute("UPDATE project_member SET balance=balance-{$pmb[0]['balance']} {$sumScore} {$where}");
                    $member = $this->getProjectMember(array('id' => $record['mid']), $record['project_id']);
                    $service_chargeb = ceil(($pmb[0]['balance']*6/1000)*100)/100;
                    $tixianb = $pmb[0]['balance'] - $service_chargeb;
                    $result = $this->execute("INSERT INTO member_balance SET
                        add_balance='-{$tixianb}',
                        add_wallet='0',
                        add_score='0',
                        project_id='{$record['project_id']}',
                        mid='{$record['mid']}',
                        balance='{$member['balance']}',
                        wallet='{$member['wallet']}',
                        score='{$member['score']}',
                        created=".time().",
                        `type`='13',
                        reason='{$record['reason']},实际提现'");
                    $result = $this->execute("INSERT INTO member_balance SET
                        add_balance='-{$service_chargeb}',
                        add_wallet='0',
                        add_score='0',
                        project_id='{$record['project_id']}',
                        mid='{$record['mid']}',
                        balance='{$member['balance']}',
                        wallet='{$member['wallet']}',
                        score='{$member['score']}',
                        created=".time().",
                        `type`='11',
                        reason='{$record['reason']},手续费'");
                }
            }
        }
        return true;
    }
    /*
     * 提现规则
     */
    public function transfers_rule($amount,$shopId,$openid){
        if(!is_numeric($amount)){
            return false;
        }
        //单笔最小金额为1元,单笔最大金额2W
        if($amount<1 || $amount>20000){
            return false;
        }
        //给同一个用户付款时间间隔不得低于15秒
        $projectId = substr($shopId,0,-3);
        $tranMsg = M('wx_transfers')->where(array("return_code"=>'SUCCESS','result_code'=>'SUCCESS','project_id'=>$projectId,'openid'=>$openid))->order('payment_time desc')->limit('0,1')->find();
        if(strtotime($tranMsg['payment_time'])+15>=time()){
            return false;
        }
        //给同一个用户付款，每日限额2W;
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        $transfer_count = M()->query("SELECT SUM(amount) AS total,COUNT(*) AS con FROM wx_transfers WHERE openid = '{$openid}' AND result_code = 'SUCCESS' AND payment_time BETWEEN '{$today_start}' AND '{$today_end}'");
        if(!is_numeric($transfer_count[0]['total'])){
            $transfer_count[0]['total'] = 0;
        }
        $total = $transfer_count[0]['total']+$amount;
        if($total>2000000){
            return false;
        }
        //一个商户同一日付款总额限额100W
        $transfer_count = M()->query("SELECT SUM(amount) AS total FROM wx_transfers WHERE project_id = '{$projectId}' AND result_code = 'SUCCESS' AND payment_time BETWEEN '{$today_start}' AND '{$today_end}'");
        if($transfer_count[0]['total']>=100000000){
            return false;
        }
        //需要先绑定实名认证
        $transfers_auth = M()->query("SELECT * FROM transfers_auth WHERE project_id = '{$projectId}'");
        if(empty($transfers_auth) || $transfers_auth[0]['status']!=1 || empty($transfers_auth[0]['card_name']) || empty($transfers_auth[0]['card_no'])){
            return false;
        }
        return true;
    }
}
?>