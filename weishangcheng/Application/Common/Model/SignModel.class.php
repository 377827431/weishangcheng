<?php 
namespace Common\Model;

use Think\Model;
class SignModel extends Model{
	protected $tableName = 'active_sign';

	public function getData(){
	    $data = $this->find();
		if(empty($data) || empty($data['rules'])){
			$data['rules'] = '[]';
		}
	    return $data;
	}
	
	/**
	 * 签到
	 * @param unknown $by
	 */
	public function sign($by){
	    $sql = "SELECT id, nickname, balance, no_balance FROM member ";
	    if(is_numeric($by)){
	        $sql .= "WHERE id={$by}";
	    }else{
	        $sql .= "WHERE id=(SELECT mid FROM wx_user WHERE openid='{$by}')";
	    }
	    
	    $user = $this->query($sql);
	    if(empty($user)){
	        $this->error = '账户不存在';
	        return -1;
	    }
	    $user = $user[0];
	    
	    $continued = 1;
	    $today = strtotime(date('Y-m-d').' 00:00:00');
	    $prev = $this->query("SELECT * FROM active_sign_record WHERE mid={$user['id']} ORDER BY id DESC LIMIT 1");
	    if(count($prev) > 0){
	        $prev = $prev[0];
	        if($prev['created'] >= $today){
	            $this->error = '今日已签到';
	            return -1;
	        }
	        
	        if(date('Y-m-d', $prev['created']) == date('Y-m-d', strtotime('-1 day'))){
	            $continued += $prev['continued'];
	        }
	    }
	    
	    // 查找规则
	    $sign = $this->getData();
	    if(empty($sign) || $sign['enabled'] != 1){
	        $this->error = '签到活动已结束';
	        return -1;
	    }
	    
	    $rules = json_decode($sign['rules'], true);
	    $money = isset($rules[$continued]) ? $rules[$continued] : $sign['money'];
	    $this->addRecord($user['id'], $money, $continued, $prev['created'], $sign['id']);
	    
	    return array('money' => $money, 'continued' => $continued, 'balance' => $user['balance'], 'no_balance' => $user['no_balance'] + $money);
	}
	
	private function addRecord($mid, $money, $continued, $ptime, $signId){
	    // 保存签到记录
	    $sql = "INSERT INTO active_sign_record(sign_id, mid, created, money, prev_time, continued)
	            VALUES({$signId}, {$mid}, ".NOW_TIME.", '{$money}', '".(empty($ptime) ? NOW_TIME : $ptime)."', '{$continued}')";
	    $this->execute($sql);
	    
	    $sql = "UPDATE {$this->tableName} SET sended_fee=sended_fee+{$money}
			   ".(empty($ptime) ? ", played_uv=played_uv+1" : "")."
				, played_pv=played_pv+1 WHERE id=".$signId;
	    $this->execute($sql);
	    
	    if($money > 0){
	        D('Balance')->add(array(
	            'mid'        => $mid,
	            'no_balance' => $money,
				'reason'	 => '每日签到',
	            'type'       => 'sign'
	        ));
	    }
	}
}
?>