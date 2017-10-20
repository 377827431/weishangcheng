<?php

/**
 * 活动说明
 * 2016.09.11中秋节50元优惠券
 * 
 * 作者：lanxuebao
 * code：57d50c6c6c799
 */

$member = $this->user('id, agent_level, mobile, subscribe');
$Model = new \Common\Model\CouponModel();
$coupon = $Model->getCoupon(4, $member);
   
if(empty($coupon)){
    $this->error('优惠券异常，请联系客服处理');
}

if(IS_GET){
    // 二维码
    if(!$member['subscribe']){
        $qrModel = new \Common\Model\QrcodeModel();
        $qrcode = $qrModel->getTicket(\Common\Model\QrcodeModel::COUPON, $coupon['code']);
        $this->assign('qrcode', $qrcode['url']);
    }
    
    // 4款热卖商品
    $hotList = $Model->query("SELECT id, title, pic_url, price, agent3_price FROM mall_goods WHERE id IN(6,318,23,128)");
    $this->assign('hotList', $hotList);
    $this->assign('member', $member);
    $this->assign('coupon', $coupon);
    return;
}else{
    if($member['subscribe'] == 0){
        $this->error('请先关注公众号再领取');
    }
    
    // 一次性全部领取
    $sql = "INSERT INTO member_coupon(mid, coupon_id, created, value, `status`) VALUES";
    $updateSql = [];
    if($coupon['errcode'] != 0){
        $this->error($coupon['errmsg']);
    }
    
    if($coupon['quota'] < 1){
        $this->error('有人开小差了，请稍后再试');
    }
    
    for($i=0; $i<$coupon['quota']; $i++){
        if(is_numeric($coupon['value'])){
            $value = $coupon['value'];
        }else{
            $_temp = explode(',', $coupon['value']);
            $value = mt_rand($_temp[0] * 100, $_temp[1] * 100) * 0.01;
        }
        $sql .= "({$member['id']}, {$coupon['id']}, ".NOW_TIME.", {$value}, 0, {$coupon['end_time']}),";
    }
    
    $updateSql[] = "UPDATE mall_coupon SET pv=pv+{$coupon['quota']}".($coupon['haved'] == 0 ? ', uv=uv+1' : '')." WHERE id=".$coupon['id'];
    
    $sql = rtrim($sql, ',');
    $result = $Model->execute($sql);
    if($result <= 0){
        $this->error('领取失败，请稍后再试');
    }
    
    // 累加领取数量
    foreach ($updateSql as $sql){
        $Model->execute($sql);
    }
    $this->success('领取成功');
}

?>