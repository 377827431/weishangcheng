<?php

namespace Seller\Controller;
use Common\Model\OrderStatus;

/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/4/7
 * Time: 11:29
 */

class IndexController extends ManagerController{

    public function index(){
        $re = M('shop')
            ->field("shop.id, shop.logo, shop.name, si.pay_qr, si.wx_no")
            ->join("shop_info as si on shop.id = si.id")
            ->where(array("shop.id" => $this->shopId))
            ->find();
        $id = $this->user('id');
//        $t_auth = M('transfers_auth')->field('id, status, created')->where("id = '{$id}'")->find();
//        if (empty($t_auth)){
//            $t_auth['status'] = -1;
//        }
//        switch ($t_auth['status']){
//            case -1:
//                $auth_status = 'unvalidate';
//                break;
//            case 0:
//                $auth_status = 'waiting';
//                break;
//            case 1:
//                $auth_status = 'validated';
//                break;
//            default:
//                $auth_status = 'rejected';
//                break;
//        }
//        if (empty($re) || empty($re['pay_qr']) || empty($re['name']) || empty($re['logo']) || empty($re['wx_no'])){
//            $this->redirect("/shop");
//        }

        $t_auth = M('transfers_auth')
            ->field('id, status, created')
            ->where(array("id" => $id, "status" => 1))
            ->find();
        if (!empty($t_auth)){
            $this->assign('t_auth', 1);
        }else{
            $this->assign('t_auth', 0);
        }

        $code = I('get.code');
        if ($code == '0'){
            $isshow = array(
                "result" => "success",
            );
        }elseif ($code == '-1') {
            $isshow = array(
                "result" => "failed",
                "reason" => I('get.reason')
            );
        }else{
            $isshow = 0;
        }

        if(IS_AJAX){
            $today = strtotime('today');
            $Idw = new \Org\IdWork();
            $bet = $Idw->getTidRange($today);
            $tosend = OrderStatus::WAIT_SEND_GOODS;
            $send = OrderStatus::WAIT_CONFIRM_GOODS;
            $wait_pay = OrderStatus::WAIT_PAY;
            $model = M('trade_seller');
            $trade1 = $model
                ->alias('ts')
                ->field("count(*) as `count`, sum(trade.paid_fee) as `turn`")
                ->join('trade on ts.tid = trade.tid')
                ->where("ts.seller_id = '{$this->shopId}' and ts.tid > {$bet[0]} and ts.tid < {$bet[1]}")
                ->find();
            $trade2 = $model
                ->alias('ts')
                ->field("count(case when trade.status = '{$tosend}' then 1 else null end) as `tosend`, count(case when trade.status = '{$send}' then 1 else null end) as `send`, count(case when trade.status = '{$wait_pay}' then 1 else null end) as `topay`")
                ->join('trade on ts.tid = trade.tid')
                ->where("ts.seller_id = {$this->shopId}")
                ->find();
            $trade = array_merge($trade1, $trade2);
            if ($trade['turn'] == ''){
                $trade['turn'] = 0;
            }
            $trade['turn'] = round($trade['turn']);
            $this->ajaxReturn($trade);
        }

        $shop = M('shop')->where("id = {$this->shopId}")->find();
        if ($shop['logo'] == ''){
            $shop['logo'] = C('CDN').'/img/logo_108.png';
        }
        $trade = array(
            "turn" => '&nbsp',
            "tosend" => '&nbsp',
            "send" => '&nbsp',
            "topay" => '&nbsp',
            "count" => '&nbsp'
        );
        $data = array(
            "name" => $shop['name'],
            "head_img" => $shop['logo'],
            "trade" => $trade,
        );
        $projectId = substr($this->shopId, 0, -3);
        $project = M('project')->field('alias')->where("id = {$projectId}")->find();
        $url = '/'.$project['alias'].'/'.'transfer?url='.$project['host'].'/'.$project['alias'].'/';
        $this->assign('url', $url);
        //判断是测试者
        $manager = $this->user();
        $shop = M('shop')->find($manager['shop_id']);
        $ali = M('alibaba_token')->find($shop['aliid']);
        if($ali['login_id'] == "兴业宝科技有限公司"){
            $this->assign('is_tester','1');
        }
        $this->assign('data', $data);
        $this->assign('isshow', $isshow);
        $this->assign('shopid', $this->shopId);
        $this->display();
    }
}