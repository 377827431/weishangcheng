<?php
namespace Common\Model;

class CouponModel extends BaseModel
{
    protected $tableName = 'mall_coupon';
    private $couponType = array(1 => 'coupon_card', 2 => 'coupon_code', 3 => 'cash_coupon');
    
    /**
     * 获取优惠券信息(type < 4)
     */
    public function getCoupon($id, $member){
        $where = is_numeric($id) ? "id=".$id : "id IN ({$id})";
        //$where .= " AND type < 4";
        $list = $this->field("id, `code`, title, total, quota, pv, uv, `status`, start_time, end_time, type, meet, value, member_level, single, notice")->where($where)->select();
        
        if(empty($list)){
            $this->error = '优惠券不存在';
            return;
        }
        
        $agents = $this->agentLevel();
        foreach($list as $i=>&$coupon){
            $coupon['errcode'] = 0;
            $coupon['errmsg']  = '';
            
            // 使用条件
            $coupon['condition'] = $coupon['meet'] > 0 ? '商品满'.$coupon['meet'].'元可用' : '下单即可用';
            
            if(NOW_TIME < $coupon['start_time']){
                $coupon['errcode'] = 1;
                $coupon['errmsg']  = '未开始';
                continue;
            }else if(NOW_TIME >= $coupon['end_time']){
                $coupon['errcode'] = 1;
                $coupon['errmsg']  = '已过期';
                continue;
            }else if($coupon['status'] != 1){
                $coupon['errcode'] = 1;
                $coupon['errmsg']  = '已失效';
                continue;
            }
            
            // 会员级别限制
            if($coupon['member_level'] !== ''){
                $allow = explode(',', $coupon['member_level']);
                if(!in_array($member['agent_level'], $allow)){
                    $coupon['errcode'] = 1;
                    $coupon['errmsg']  = $agents[$member['agent_level']]['title'].'不能领取';
                    continue;
                }
            }
            
            // 整体数量限制
            if($coupon['total'] > 0){
                if($coupon['pv'] >= $coupon['total']){
                    $coupon['stock'] = 0;
                }else{
                    $coupon['stock'] = $coupon['total'] - $coupon['pv'];
                }
            }else{
                $coupon['stock'] = 99999;
            }
            
            if($coupon['stock'] == 0){
                $coupon['errcode'] = 1;
                $coupon['errmsg']  = '您来晚了，优惠券已发放完毕';
                continue;
            }
            
            $coupon['haved'] = 0;   // 用于标记是否增加uv
            // 限制领取张数
            if($coupon['quota'] > 0){
                $sql = "SELECT COUNT(*) AS total FROM member_coupon WHERE mid={$member['id']} AND coupon_id={$coupon['id']} LIMIT ".$coupon['quota'];

                $total = $this->query($sql);
                if(!empty($total)){
                    $coupon['haved'] = $total[0]['total'] > 0 ? 1 : 0;
                    if($total[0]['total'] >= $coupon['quota']){
                        $coupon['errcode'] = 1;
                        $coupon['errmsg']  = '每人最多领取'.$coupon['quota'].'张' ;
                        continue;
                    }
                }
            }else{  // 不限制数量时可用优惠券最多20张
                $sql = "SELECT id, `status` FROM member_coupon WHERE mid={$member['id']} AND coupon_id={$coupon['id']} ORDER BY `status` LIMIT 20";
                $valueList = $this->query($sql);
                if(!empty($valueList)){
                    $coupon['haved'] = 1;
                    $total = 0;
                    foreach ($total as $info){
                        if($info['status'] == 0){
                            $total++;
                        }
                    }
                    
                    if($total >= 20){
                        $coupon['errcode'] = 1;
                        $coupon['errmsg']  = '已达上限，请先使用几张后再来领取';
                        continue;
                    }
                }
            }
        }
        
        if(is_numeric($id)){
            return $list[0];
        }
        return $list;
    }
    
    /**
     * 领取随机金额优惠券
     * @param unknown $rand
     * @param unknown $mid
     */
    public function getRandCouponValue($coupon, $rand, $member){
        if(empty($rand) || empty($member)){
            E('CouponModel.getRandCouponValue.Exception');
        }
        
        $remainSize   = $rand['num'] - $rand['send_num']; // 剩余数量
        if($remainSize <= 0){
            $this->error = '您来晚了，优惠券已被抢空！';
            return -1;
        }

        $remainMoney = bcsub($rand['total'], $rand['send_total'], 2); // 剩余金额
        // 剩余一张
        $fee = 0;
        if ($remainSize == 1) {
            $fee = $remainMoney;
        }else{
            $remainMoney *= 100;
            $min   = 1;
            $max   = bcdiv($remainMoney, $remainSize, 2) * 2;
            $money = bcmul(mt_rand(1, 99) * 0.01, $max, 2);
        
            $money = $money < $min ? $min : $money;

            $range = implode(',', $coupon['value']);
            $setMin = isset($range[1]) ? $range[0] : 0.01;
            $setMax = isset($range[1]) ? $range[1] : $range[0];
            if($money > $setMax){
                $money = $setMax;
            }
            $fee = bcdiv($money, 100, 2);
        }

        // 马上更新数据，占据位置
        $result = $this->execute("UPDATE mall_coupon_rand SET send_num=send_num+1, send_total=send_total+{$fee} WHERE id='{$rand['id']}' AND send_num+1<=num");
        if($result == 0){
            $this->error = '您手慢了，优惠券已被抢空了！';
            return -1;
        }
        
        $exists = $this->query("SELECT 1 FROM member_coupon WHERE mid={$member['id']} AND coupon_id={$coupon['id']} AND coupon_code='{$rand['id']}' LIMIT 1");
        $uv = count($exists) > 0 ? 0 : 1;
        $this->execute("UPDATE mall_coupon SET pv=pv+1, uv=uv+{$uv} WHERE id=".$coupon['id']);
        
        // 优惠券过期时间
        $expireTime = $rand['expire_day'] > 0 ? strtotime('+'.$rand['expire_day'].' day', NOW_TIME) : $coupon['end_time'];
        if($expireTime > $coupon['end_time']){
            $expireTime = $coupon['end_time'];
        }
        $this->execute("INSERT INTO member_coupon SET mid={$member['id']}, coupon_id={$coupon['id']}, coupon_code='{$rand['id']}', value={$fee}, created='".NOW_TIME."', status=0, expire_time={$expireTime}");
        return $fee;
    }
    
    public function existsMemberCoupon($mid, $couponId, $couponCode){
        $data = $this->query("SELECT * FROM member_coupon WHERE mid={$mid} AND coupon_id='{$couponId}' AND coupon_code='{$couponCode}' LIMIT 1");
        return $data[0];
    }
    
    /**
     * 给会员发优惠券
     */
    public function send($mid, $cardId, $couponId){
        $coupon = $this->query("SELECT id, member_level, end_time, expire_day, quota, `value` FROM mall_coupon WHERE id=".$couponId);
        if(!$coupon){
            return;
        }
        $coupon = $coupon[0];
        
        // 指定会员卡可领
        if($coupon['member_level']){
            $coupon['member_level'] = explode(',', $coupon['member_level']);
            if(!in_array($cardId, $coupon['member_level'])){
                return;
            }
        }
        
        // 领取数量限制
        if($coupon['quota'] > 0){
            $exists = $this->query("SELECT 1 FROM member_coupon WHERE mid={$mid} AND coupon_id={$couponId} LIMIT {$coupon['quota']}");
            if(count($exists) >= $coupon['quota']){
                return;
            }
        }
        
        $created = time();
        
        // 面值
        $value = 0.01;
        if(is_numeric($coupon['value'])){
            $value = $coupon['value'];
        }else{
            $range = explode(',', $coupon['value']);
            $value = rand($range[0]*100, $range[1]*100);
            $value = bcdiv($value, 100, 2);
        }
        
        // 过期时间
        $expire_time = $coupon['end_time'];
        if($coupon['expire_day'] > 0){
            $expire_time = strtotime('+'.$coupon['expire_day'].' day', $created);
        }
        
        // 发优惠券
        return $this->execute("INSERT INTO member_coupon SET mid={$mid}, coupon_id={$couponId}, `value`={$value}, created='{$created}', expire_time='{$expire_time}'");
    }
}
?>