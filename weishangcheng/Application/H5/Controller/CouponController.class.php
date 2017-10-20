<?php
namespace H5\Controller;

use Common\Common\CommonController;
use Common\Model\QrcodeModel;
use Org\Wechat\WechatAuth;

/**
 * 优惠卡券
 * @author lanxuebao
 *
 */
class CouponController extends CommonController{
    /**
     * 我的优惠卷
     */
    public function index(){
        // 投放视图
        if(!IS_AJAX){
            $this->display();
        }
        //投放数据
        $offset = is_numeric($_GET['offset']) ? $_GET['offset'] : 0;
        $size = is_numeric($_GET['size']) ? $_GET['size'] : 19;
        $enabled = $_GET['status'];
        $mid = $this->user('id');
        $sql = "SELECT coupon.name, coupon.type, coupon.start_time, coupon.end_time, coupon.meet,
                    mc.id, mc.coupon_id, mc.`value`, mc.`status`, mc.expire_time
                FROM member_coupon AS mc
                INNER JOIN mall_coupon AS coupon ON coupon.id=mc.coupon_id
                WHERE mc.mid={$mid} AND mc.`status` != 3";
        if($enabled){
            $sql .= " AND mc.`status` = 0";
        }else{
            $sql .= " AND mc.`status` in(1,2,3)";
        }
        $sql .= " ORDER BY mc.id DESC";
        $sql .= " LIMIT {$offset}, {$size}";
        
        $Model = M();
        $list = $Model->query($sql);
        foreach($list as &$coupon){
            if($coupon['meet']>0){
                $coupon['condition']='满'.$coupon['meet'].'使用';
            }elseif($coupon['range_type']==1){
                $coupon['condition']='指定商品可用';
            }else{
                $coupon['condition']='无使用限制';
            }

            // 使优惠券过期
            if(NOW_TIME >= $coupon['expire_time'] && $coupon['status'] == 0){
                $Model->execute("UPDATE member_coupon SET `status`=2 WHERE mid={$mid} AND coupon_id={$coupon['coupon_id']} AND `status`=0");
                $coupon['status'] = 2;
            }
            
            $coupon['start_time']=date("Y.m.d",$coupon['start_time']);
            $coupon['end_time']=date("Y.m.d",$coupon['expire_time']);
        }
        $this->ajaxReturn($list);
    }
    
    public function uniqid(){
        echo uniqid();
    }
    
    public function _empty($code){
        if(!preg_match('/^[0-9a-zA-Z]{13}$/', $code)){
            $this->error('优惠券不存在');
        }
        
        $filename = MODULE_PATH.'Model/Coupon/'.$code.'.php';
        if(!@file_exists($filename)){
            // 判断优惠券
            # some code
            
            // 判断随机优惠券
            $rand = M('mall_coupon_rand')->find($code);
            if(!empty($rand)){
                $this->rand($rand);
            }
            
            $this->error('优惠券不存在');
        }
        include_once $filename;
        $this->display();
    }
    
    /**
     * 优惠券微信二维码(临时)
     */
    public function qrcode(){
        $outerId = $_GET['couter_id'];
        $Model = new QrcodeModel();
        $reslt = $Model->getTicket(QrcodeModel::COUPON, $outerId);
        print_data($reslt);
    }
    
    /**
     * 随机金额优惠券
     * @param mall_coupon_rand $rand
     */
    private function rand($rand){
        $member = $this->user('id, agent_level, mobile, subscribe, wx.headimgurl');
        $Model = new \Common\Model\CouponModel();
        
        // 获取优惠券信息
        $coupon = $Model->getCoupon($rand['coupon_id'], $member);
        if(empty($coupon)){
            $this->error($Model->getError());
        }
        
        // 判断此人是否已抢过
        $exists = $Model->existsMemberCoupon($member['id'], $rand['coupon_id'], $rand['id']);
        if(!empty($exists)){
            if(IS_POST){
                $this->success($exists['value']);
            }
            $rand['value'] = sprintf('%.2f', $exists['value']);
        }
        
        if(IS_POST){
            if($rand['send_num'] >= $rand['num'] || $rand['send_total'] >= $rand['total']){
                $this->error('您来晚了，优惠券已被抢空！');
            }
            
            $rand['value'] = $Model->getRandCouponValue($coupon, $rand, $member);
            
            if($rand['value'] == -1){
                $this->error($Model->getError());
            }else{
                $this->success($rand['value']);
            }
        }

        $this->assign('member', $member);
        $this->assign('rand', $rand);
        $this->assign('coupon', $coupon);
        
        // 微信分享
        if(IS_WEIXIN){
            // 分享文案
            $shareData = array(
                "title" => '抢优惠券啦',
                "desc" => $coupon['condition'].'。数量有限，先抢先得！',
                "link" =>  C('PROTOCOL').$_SERVER['HTTP_HOST'].'/h5/coupon/'.$rand['id'],
                "imgUrl" => C('CDN').'/img/rand_coupon/share.jpg'
            );
            
            //获取签名
            $WechatAuth = new \Org\Wechat\WechatAuth();
            $sign = $WechatAuth->getSignPackage();
            $this->assign(array('sign' => json_encode($sign), 'share_data' => json_encode($shareData)));
        }
        $this->display('rand');
    }
}
?>