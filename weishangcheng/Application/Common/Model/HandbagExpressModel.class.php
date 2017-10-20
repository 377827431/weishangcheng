<?php
namespace Common\Model;

class HandbagExpressModel extends BaseModel
{
    protected $tableName = 'active_handbag_express';
    
    public function scan($openid, $code){
        if(empty($openid)){
            return '扫码OPENID不能为空';
        }else if(empty($code)){
            return '条码不能为空';
        }else if(!in_array($openid, array('o6Q9Iw85tWE7xLDz9O2Gwvz-OrAQ', 'o6Q9Iw-sIo77v_PBPWvwJIPbUGwA', 'o6Q9IwzEHMurqvS61mhP-qN4wsYY', 'o6Q9IwwN-xPU17R-aEqERd5NR9WI'))){
            return '此功能仅对快递员开放，扫码结果:'.$code;
        }
        
        $member = $this->query("SELECT id, nickname, mobile, agent_level, balance, no_balance FROM member WHERE id=(SELECT mid FROM wx_user WHERE openid='{$openid}')");
        if(empty($member)){
            return '账户不存在或已被注销，请重新关注后再试！';
        }else{
            $member = $member[0];
            $member['openid'] = $openid;
        }

        // 查找code信息
        $handbag = $this->field("id, mid, sign_time, end_time, amount")->find($code);
        if(empty($handbag)){
            return '运单号不存在，多次扫码无效后您将被屏蔽使用此功能';
        }else if($handbag['mid'] > 0){
            if($handbag['amount'] == 0){
                return "无法领取奖励：退件签收";
            }
            return date('m月d日 H:i', $handbag['sign_time'])."【签收】\r\n".date('m月d日 H:i', $handbag['end_time'])."【发放】\r\n<a href=".C('PROTOCOL')."{$_SERVER['HTTP_HOST']}/h5/balance>红包已被领取！</a>";
        }
        
        // 记录扫码信息
        $record = $member['id'].':'.NOW_TIME;
        $this->execute("UPDATE {$this->tableName} SET times=times+1, record=IF(record='', '{$record}', CONCAT(record, ',{$record}')) WHERE id='{$code}'");
    
        $result = $this->getInfo($code);
        if($result['status'] == 1){ // 已签收
            return $this->transfers($member, $code, $result['time']);
        }else if($result['status'] == 2){ // 退件签收
            $this->execute("UPDATE {$this->tableName} SET sign_time=".strtotime($result['time']).",end_time=".NOW_TIME.", mid={$member['id']}, amount=0 WHERE id='{$code}'");
        }
        return $result['msg'];
    }
    
    /**
     * 获取物流信息
     * @param unknown $code
     */
    private function getInfo($mailno){
        $url = 'http://japi.zto.cn/zto/api_utf8/traceInterface';
        $json = '["'.$mailno.'"]';
        $map = array(
            "data" => $json,
            "msg_type" => "TRACES",
            "data_digest" => md5(($json."18C50AC881FFEB172D84EA4A89E8BF2E")),
            "company_id" => "006078cec56349f8af4a496b79c18118"
        );
        
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $strPOST = http_build_query($map);
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        
        $result = array('status' => 0, 'msg' => '', 'time' => '');
        $data = json_decode($sContent, true);
        if($data['status'] != 1){
            $result['msg'] = '未获取到运单信息,请稍后重试';
        }else if(empty($data['data'])){
            $result['msg'] = $data['msg'];
        }else{
            $list  = $data['data'][0]['traces'];
            $first = $list[0];
            $last  = end($list);
            if($last['scanType'] == '签收'){
                if($last['remark'] == '退件签收' || $first['scanSiteCode'] == $last['scanSiteCode']){
                    $result['status'] = 2;
                    $result['msg'] = '无法领取奖励：退件签收';
                }else{
                    $result['status'] = 1;
                    $result['msg']    = '已签收';
                }
            }else{
                $result['msg'] = "无法领取奖励\r\n运输途中：".$last['desc'];
            }
            $result['time'] = $last['scanDate'];
        }
        
        return $result;
    }
    
    /**
     * 转账-提现
     */
    private function transfers($user, $code, $signTime){
        $amount = 1;    // 转账金额
        $stepMin = 60;  // 转账间隔
        $dayMax = 100;  // 每日最多金额
        
        $Model = M('wx_transfers');
        // 提现间隔分钟
        $prevTime = $Model->where("mid=".$user['id'])->max("payment_time");
        $prevTimespan = strtotime($prevTime);
        if($prevTimespan + $stepMin >= NOW_TIME){
            return '兑换操作频繁，请于'.($stepMin - (NOW_TIME - $prevTimespan)).'秒后再试';
        }
        
        // 每日最多可提现
        $today = date('Y-m-d');
        $todayAmount = $Model->where("mid='{$user['id']}' AND payment_time BETWEEN '{$today} 00:00:00' AND '{$today} 23:59:59' AND result_code='SUCCESS'")->sum('amount');
        if(is_numeric($todayAmount)){
            if($todayAmount >= $dayMax){
                return '每日最多兑换'.$dayMax.'积分';
            }else if($todayAmount + $amount > $dayMax){
                return '您今日还可兑换'.sprintf('%.2f', $dayMax - $todayAmount).'积分';
            }
        }
        
        // 开始提现
        $wxTransfers = new \Org\WxPay\WXTransfers();
        $wxTransfers->setReUserName($user['nickname']);
        $wxTransfers->setDesc("手包派件活动");
        $result = $wxTransfers->transfers($user['openid'], $amount);

        // 保存微信提现结果
        $result['mid'] = $user['id'];
        $result['openid'] = $user['openid'];
        $result['amount'] = $amount;
        $result['balance'] = $user['balance'];
        $result['no_balance'] = $user['no_balance'];
        if(empty($result['payment_time'])){
            $result['payment_time'] = date('Y-m-d H:i:s');
        }
        $Model->add($result);
        
        if($result['result_code'] != 'SUCCESS'){
            if($result['error_code'] == 'NOTENOUGH'){
                return '平台余额不足，请稍后重试或联系管理员！';
            }
            return empty($result['return_msg']) ? '系统繁忙，请稍后重试！' : $result['return_msg'];
        }
        
        // 扣除金额
        $this->execute("INSERT INTO member_balance
                        SET
                        mid={$user['id']},
                        reason='手包派件签收',
                        balance='{$user['balance']}',
                        no_balance='{$user['no_balance']}',
                        money={$amount},
                        create_time='".date('Y-m-d H:i:s')."',
                        type='handbag_express'");
        
        // 直接变成会员
        if($user['agent_level'] == 0){
            $this->execute("UPDATE member SET agent_level=3 WHERE id=".$user['id']);
        }
        
        $this->execute("UPDATE {$this->tableName} SET sign_time=".strtotime($signTime).",end_time=".NOW_TIME.", mid={$user['id']}, amount={$amount} WHERE id='{$code}'");
        
        $msg = "快递小哥您辛苦了！\r\n".$amount."元现金已转至您的微信钱包，请笑纳！";
        if($user['agent_level'] == 0){
            $msg .= "\r\n同时本平台所有商品您均享受会员价！";
        }
        return $msg;
    }
}
?>