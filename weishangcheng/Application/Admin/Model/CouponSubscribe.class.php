<?php
namespace Admin\Model;

use Think\Model;
use Org\Wechat\WechatAuth;
use Common\Model\BaseModel;

/**
 * 优惠券
 * @author Administrator
 *
 */
class CouponSubscribe extends BaseModel{
    /**
     * 优惠券核销
     * @param array $coupon
     */
    public function used($coupon){
        if(!is_numeric($coupon['id']) || !is_numeric($coupon['coupon_id'])){
            E('优惠券ID格式错误');
        }
        
        $this->execute("UPDATE member_coupon SET `status`='1', tid='{$coupon['tid']}', used_time='{$coupon['used_time']}' WHERE id='{$coupon['id']}'");
        $this->execute("UPDATE mall_coupon SET used=used+1 WHERE id={$coupon['coupon_id']}");
    }
    
    /**
     * 优惠券撤回(暂无用)
     * @param array $coupon
     */
    public function unuse($coupon){
        if(!is_numeric($coupon['id']) || !is_numeric($coupon['coupon_id'])){
            E('优惠券ID格式错误');
        }
        
        $mcoupon = $this->query("SELECT * FROM member_coupon WHERE id='{$coupon['id']}'");
        if(empty($mcoupon)){
            E('优惠券不存在');
        }
        $mcoupon = $mcoupon[0];
        
        // 判断是否过期
        if($mcoupon['expire_time'] >= NOW_TIME){
            E('优惠券已过期');
        }
        
        $this->execute("UPDATE member_coupon SET `status`='0', tid='0', used_time='0' WHERE id='{$coupon['id']}'");
        $this->execute("UPDATE mall_coupon SET used=used-1 WHERE id={$coupon['coupon_id']}");
    }
    
    /**
     * 赠送优惠券(带消息推送)
     */
    public function give($couponId, $mids, $discription = '卖家赠送您一张优惠券，记得在过期前使用哦'){
        $coupon = $this->query("SELECT id, name, meet, project_id, member_level, start_time, end_time, expire_day, quota, `value`, range_type, coupon_code FROM mall_coupon WHERE id=".$couponId);
        $coupon = $coupon[0];
        if(!$coupon){
            E('优惠券不存在');
        }else if($coupon['end_time'] > NOW_TIME){
            E('优惠券已过期');
        }
        
        // 过期时间
        $timestamp = time();
        $coupon['expire_time'] = $coupon['expire_day'] > 0 ? strtotime('+'.$coupon['expire_day'].' day', $timestamp) : $coupon['end_time'];
        
        // 适用范围
        $coupon['discription'] = '适用范围：';
        switch ($coupon['range_type']){
            case 0:
                $coupon['discription'] .= '平台通用';
                break;
            case 1:
                $coupon['discription'] .= '指定商品可用';
                break;
            case 2:
                $coupon['discription'] .= '指定分组可用';
                break;
            case 3:
                $coupon['discription'] .= '指定类目可用';
                break;
        }
        $coupon['discription'] .= '; 记得在'.date('Y年m月d日 H:i', $coupon['expire_time']).'前使用哦！感谢您的支持，祝您生活愉快！';
        
        // 批量获取会员
        $list = $this->getProjectMembers($coupon['project_id'], $mids);
        if(count($list) == 0){
            E('指定项目会员不存在');
        }
        $mids = array_keys($list);
        $mids = implode(',', $mids);
        
        // 领取数量限制
        $quotaList = array();
        if($coupon['quota'] > 0){
            $quotaList = $this->query("SELECT mid, count(*) AS total FROM member_coupon WHERE mid IN {$mids} AND coupon_id={$couponId} GROUP BY mid");
            $quotaList = array_kv($quotaList, 'mid');
        }
        $coupon['member_level'] = explode_string(',', $coupon['member_level']);
        
        $sql = "INSERT INTO member_coupon(mid, coupon_id, coupon_code, `value`, created, expire_time) VALUES";
        $sended = array();
        foreach ($list as $mid=>$member){
            // 指定会员卡可领
            if($coupon['member_level'] && !in_array($member['card_id'], $coupon['member_level'])){
                continue;
            }
            
            // 领取数量限购
            if(isset($quotaList[$member['id']]) && $quotaList[$member['id']] >= $coupon['quota']){
                continue;
            }
            
            // 面值
            $value = 0.01;
            if(is_numeric($coupon['value'])){
                $value = $coupon['value'];
            }else{
                $range = explode(',', $coupon['value']);
                $value = rand($range[0]*100, $range[1]*100);
                $value = bcdiv($value, 100, 2);
            }
            
            $sql .= "({$mid},{$couponId},'{$coupon['coupon_code']}',{$value},{$timestamp},{$coupon['expire_time']}),";
            
            $member['coupon_value'] = floatval($value);
            $sended[$member['id']] = $member;
        }
        $this->execute(rtrim($sql, ','));

        // 发送消息
        $wechats = array();
        foreach ($sended as $member){
            if($member['subscribe'] == 0){
                continue;
            }
            
            if(!isset($wechats[$member['appid']])){
                $config = get_wx_config($member['appid']);
                $wechat = new WechatAuth($config);
                $wechats[$member['appid']] = array('config' => $config, 'wechat' => $wechat);
            }
            $config = $wechats[$member['appid']]['config'];
            $wechat = $wechats[$member['appid']]['wechat'];
            
            // 资金变动通知
            $template = array(
                'template_id'  => $config['template']['OPENTM207453441'],
                'url'          => $member['url'].'/coupon/detail?id='.$coupon['coupon_code'],
                'data'         => array(
                    'first'    => array('value' => '您有新优惠券到账如下', 'color' => '#173177'),
                    'keyword1' => array('value' => $member['name']), // 用户名
                    'keyword2' => array('value' => date('Y年m月d日 H:i')), // 变动时间
                    'keyword3' => array('value' => $member['coupon_value']), // 金额变动
                    'keyword4' => array('value' => $member['coupon_value']), // 可用余额
                    'keyword5' => array('value' => $discription), // 变动原因
                    'remark'   => array('value' => $coupon['discription'])
                )
            );
            
            $wechat->sendTemplate($member['openid'], $template);
        }
        
        return $sended;
    }
}
?>