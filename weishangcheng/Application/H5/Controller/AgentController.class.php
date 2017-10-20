<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/7/3
 * Time: 11:11
 */

namespace H5\Controller;


use Common\Common\CommonController;

class AgentController extends CommonController
{
    public function index(){
        $mid = $this->user('id');
        $shopId = $this->projectId.'001';
        $agent = M('agent')->where(array("mid" => $mid, "shop_id" => $shopId, "status" => 1))->find();
        if (empty($agent)){
            $this->error('不是推广员');
        }
        $Model = new \Common\Model\Agent();
        $data = $Model->getDetail($mid, $shopId);
        $shop = M("shop")
            ->alias('s')
            ->field('s.id, s.name, s.logo, si.desc,si.shop_sign')
            ->join('shop_info as si on s.id = si.id')
            ->where("s.id = {$shopId}")
            ->cache(true, 600)
            ->find();
        $Model = new \Common\Model\BalanceModel();
        $member = $Model->getProjectMember($mid, $this->projectId);
        $this->assign('balance', $member['balance']);
        $this->assign('data', $data);
        $this->assign('shop', $shop);
        $this->assign('mid', $mid);
        $this->display();
    }
}