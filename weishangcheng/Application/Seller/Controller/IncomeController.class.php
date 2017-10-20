<?php

namespace Seller\Controller;
use Common\Model\BalanceType;
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/4/6
 * Time: 18:18
 */
class IncomeController extends ManagerController{

    public function index(){
        $method = I('get.method','');
        $data = M('shop')->field('balance, wait_settlement, frozen_balance,tixian_method')->where("id = {$this->shopId}")->find();
        $time = date("Y-m", time());
        $time = strtotime($time);
        //判断小B
        $isLB = $this->isLittleB(substr($this->shopId,0,-3));
        if($isLB == true){
            //本月成交额
            $res = M('trade')
                ->field("sum(paid_fee) as `sab`")
                ->where("seller_id = {$this->shopId} AND pay_time > {$time}")
                ->find();
            $data['sab'] = number_format(abs($res['sab']),2);
            //今日成交额
            $time = date("Y-m-d", time());
            $time = strtotime($time);
            $res = M('trade')
                ->field("sum(paid_fee) as `sab`")
                ->where("seller_id = {$this->shopId} AND pay_time > {$time}")
                ->find();
            $data['sab2'] = number_format(abs($res['sab']),2);
        }else{
            $res = M('shop_balance')
                ->field("sum(case when type = '13' then add_balance else 0 end) as `tab`")
                ->where("shop_id = {$this->shopId} AND `type` = '13'")
                ->find();
            $data['tab'] = number_format(abs($res['tab']),2);
            $res = M('shop_balance')
                ->field("sum(case when type = '13' then add_balance else 0 end) as `sab`")
                ->where("shop_id = {$this->shopId} AND created > {$time} AND `type` = '13'")
                ->find();
            $data['sab'] = number_format(abs($res['sab']),2);
            $res = M('shop_balance')
                ->field("sum(ABS(add_balance)) as `sab2`")
                ->where("shop_id = {$this->shopId}")
                ->find();
            $data['sab2'] = number_format(abs($res['sab2']),2);
            $data['restore'] = number_format(abs($res['restore']), 2);
        }
        if($method != ''){
            $data['method'] = $method;
        }else{
            if($data['tixian_method'] == ''){
                $data['method'] = 'nohave';
            }else{
                $data['method'] = $data['tixian_method'];
            }
        }
        
        $this->assign('isLB',$isLB);
        $this->assign('data', $data);
        $this->display();
    }
    /*
     * 选择提现方式页面
     */
    public function selectMethod(){
        $projectId = substr($this->shopId, 0, -3);
        $data = array();
        $alipay = M("alipay_info")->where("project_id = {$projectId}")->find();
        if(empty($alipay)){
            //未绑定
            $data['ali_msg'] = '未设置';
        }else{
            //已绑定
            $data['ali_msg'] = '已绑定';
        }
        $data['ali_url'] = "/seller/income/alipay_edit";
        $bank = M('bank_info')->field("1")->where("project_id = {$projectId}")->find();
        if(empty($bank)){
            //未绑定
            $data['bank_msg'] = '未设置'; 
        }else{
            //已绑定
            $data['bank_msg'] = '已绑定';
        }
        $data['bank_url'] = "/seller/income/bank_edit";
        $this->assign('data',$data);
        $this->display('tixian_ways');
    }

    public function bill(){
        if (IS_AJAX){
            $limit = I('get.size/d', 0);
            $offset = I('get.offset/d', 20);
            $type = I('get.status', 'income');
            $where = "shop_id = {$this->shopId}";
            if ($type == 'income'){
                $where .= " AND add_balance>= 0";
            }else{
                $where .= " AND add_balance< 0";
            }
            $data = $list = M('shop_balance')->field("id, add_balance as money, balance, reason, type, created")->where($where)->limit($offset, $limit)->order("id desc")->select();
            $icon = BalanceType::getAll();
            foreach ($data as $k => $v){
                if ($v['money'] >= 0){
                    $data[$k]['type'] = 0;
                }else{
                    $data[$k]['type'] = -1;
                }
                $data[$k]['money'] = number_format(abs($v['money']), 2);
                $data[$k]['created'] = date("Y-m-d H:i:s", $v['created']);
                $data[$k]['short'] = $icon[$v['type']]['short'];
                $data[$k]['color'] = $icon[$v['type']]['color'];
            }
            $this->ajaxReturn($data);
        }
        $this->display('my_bill');
    }

    public function bind(){
        $projectId = substr($this->shopId, 0, -3);
        $data = M('bank_info')->where("project_id = {$projectId}")->find();
        if ($data['bc_no'] == ''){
            $data['show_name'] = '绑定银行卡';
        }
        $this->assign('data', $data);
        $this->display('bind_bank_3');
    }

    public function bank_edit(){
        $projectId = substr($this->shopId, 0, -3);
        if (IS_AJAX){
            $post = I('post.');
            $res = M('bank_info')->field("1")->where("project_id = {$projectId}")->find();
            $change = array(
                'bc_name' => $post['bank_name'],
                'bc_no' => $post['bank_num'],
                'address' => $post['prov_name'].','.$post['city_name'],
                'card_name' => $post['owner_name']
            );
            if (empty($res)){
                $change['project_id'] = $projectId;
                M('bank_info')->add($change);
            }else{
                M('bank_info')->where("project_id = {$projectId}")->save($change);
            }
            $this->ajaxReturn('1');
        }
        $data = M('bank_info')->where("project_id = {$projectId}")->find();
        $this->assign('data', $data);
        $this->display('bind_bank_2');
    }

    /*
     * 支付宝信息编辑
     */
    public function alipay_edit(){
        $projectId = substr($this->shopId, 0, -3);
        if(IS_AJAX){
            $post = I('post.');
            $res = M("alipay_info")->where("project_id = {$projectId}")->find();
            $change = array(
                'alipay_accounts' => $post['account'],
                );
            if(empty($res)){
                $change['project_id'] = $projectId;
                M("alipay_info")->add($change);
            }else{
                M("alipay_info")->where("project_id = {$projectId}")->save($change);
            }
            $this->ajaxReturn("1");
        }
        $data = M("alipay_info")->where("project_id = {$projectId}")->find();
        $this->assign('data',$data);
        $this->display('ali');
    }

    public function bank_info(){
        if (IS_AJAX){
            $projectId = substr($this->shopId, 0, -3);
            $res = M('bank_info')->field("")->where("project_id = {$projectId}")->find();
            $res['code'] = 1;
            if (empty($res) || $res['bc_name'] == '') {
                $res['bc_no'] = '没有绑定银行卡';
                $res['bc_name'] = '';
            }else{
                $long = strlen($res['bc_no']);
                if ($long > 8){
                    $p1 = substr($res['bc_no'], 0, 4);
                    $p2 = substr($res['bc_no'], $long - 4, 4);
                    $p3 = '';
                    while ($long > 8){
                        $p3 .= '*';
                        $long--;
                    }
                    $res['bc_no'] = $p1.$p3.$p2;
                }
            }
            $this->ajaxReturn($res);
        }
    }
    /*
     * 提现操作，银行卡和支付宝
     */
    public function withdrawals_info(){
        if(IS_AJAX){
            $projectId = substr($this->shopId, 0, -3);
            $last_request = M('transfers_request')->field('type')->where("shop_id = {$this->shopId}")->order("created desc")->find();
            $res_bank = M("bank_info")->where("project_id = {$projectId}")->find();
            $res_alipay = M("alipay_info")->where("project_id = {$projectId}")->find();
            $res['code'] = 1;
            if(empty($res_bank) || $res_bank['bc_name'] == ''){
                $res['bc_no'] = '没有绑定银行卡';
                $res['bc_name'] = '';
            }else{
                $long = strlen($res_bank['bc_no']);
                if ($long > 8){
                    $p1 = substr($res_bank['bc_no'], 0, 4);
                    $p2 = substr($res_bank['bc_no'], $long - 4, 4);
                    $p3 = '';
                    while ($long > 8){
                        $p3 .= '*';
                        $long--;
                    }
                    $res['bc_no'] = $p1.$p3.$p2;
                    $res['bc_name'] = $res_bank['bc_name'];
                }
            }
            if(empty($res_alipay) || $res_alipay['alipay_accounts'] == ''){
                $res['ali_no'] = '没有绑定支付宝';
                $res['ali_name'] = '收款方支付宝帐号';
            }else{
                $res['ali_no'] = $res_alipay['alipay_accounts'];
                $res['ali_name'] = '收款方支付宝帐号';
            }
            $this->ajaxReturn($res);
        }
    }
    /*
     * 支付宝信息（提现操作）
     */
    public function alipay_info(){
        if(IS_AJAX){
            $projectId = substr($this->shopId,0,-3);
            $res = M('alipay_info')->where("project_id = {$projectId}")->find();
            $res['code'] = 1;
            if(empty($res) || $res['alipay_accounts'] == ''){
                $res['alipay_accounts'] = '没有绑定支付宝';
                $res['bc_name'] = '';
            }else{
                $res['alipay_accounts'] = $res['alipay_accounts'];
            }
            $this->ajaxReturn($res);
        }
    }

    public function bind_card(){
        $projectId = substr($this->shopId, 0, -3);
        if (IS_AJAX){
            $post = I('post.');
            $res = M('bank_info')->field("1")->where("project_id = {$projectId}")->find();
            $change = array(
                'card_name' => $post['user_name'],
                'card_no' => $post['card_code'],
            );
            if (empty($res)){
                $change['project_id'] = $projectId;
                M('bank_info')->add($change);
            }else{
                M('bank_info')->where("project_id = {$projectId}")->save($change);
            }
            $this->ajaxReturn('1');
        }
        $data = M('bank_info')->where("project_id = {$projectId}")->find();
        $this->assign('data', $data);
        $this->display('bind_bank');
    }

    /*
     * 支付宝帐号绑定身份证
     */
    public function bind_alipay(){
        $projectId = substr($this->shopId, 0, -3);
        if(IS_AJAX){
            $post = I('post.');
            $res = M("alipay_info")->where("project_id = {$projectId}")->find();
            $change = array(
                'alipay_name' => $post['alipay_name'],
                'card_no'     =>$post['card_no']
                );
            if(empty($res)){
                $change['project_id'] = $projectId;
                M("alipay_info")->add($change);
            }else{
                M("alipay_info")->where("project_id = {$projectId}")->save($change);
            }
            $this->ajaxReturn("1");
        }
        $data = M("alipay_info")->where("project_id = {$projectId}")->find();
        $this->assign('data',$data);
        $this->display();
    }

    public function transfers(){
        // 数据校验
        $post = I('post.');
        $amount = $post['cash_num'];
        $type = $post['method'];
        if ($type != 'weixin' && $type != 'bank' && $type != 'alipay'){
            $this->error('请选择提现方式！');
        }

        if(!is_numeric($amount) || $amount < 1){
            $this->error('单笔提现应大于1元');
        }

        $shop = M('shop')->field('balance')->where("id = {$this->shopId}")->find();
        if($shop['balance'] < $amount){
            $this->error('您的余额不足，可提现余额：'.$shop['balance']);
        }

        $certify = M('transfers_auth')->where(array('shop_id'=>$this->shopId))->find();
        if(empty($certify) || $certify['status'] != 1 || $certify['card_name'] == '' || $certify['card_no'] == ''){
            $this->error('您还没有实名认证哦，将无法使用提现等功能');
        }
        // 事务开始
        $transaction = M('shop');
        $transaction->startTrans();

        // 扣除金额
        $transaction->query("update `shop` set `balance` = `balance` - {$amount}, `tixian_method` = '{$type}', `frozen_balance` = `frozen_balance` + {$amount} WHERE id = {$this->shopId}");
        $shop_get = $transaction->field('balance, frozen_balance, wait_settlement')->where("id = {$this->shopId}")->find();
        $transfers_balance = $shop_get['balance'];
        if ($transfers_balance < 0){
            $transaction->rollback();
            $this->error( '您的余额不足！');
        }

        // 插入记录 待处理
        if ($type == 'weixin'){
            $seller = array(
                "shop_id" => $this->shopId,
                "amount" => $amount,
                "type" => 'weixin',
                "created" => time(),
                "wxid" => '未填'
            );
        }
        $projectId = substr($this->shopId, 0, -3);
        if ($type == 'bank'){
            $res = M('bank_info')->field("")->where("project_id = {$projectId}")->find();
            if ($res['bc_name'] == '' || $res['bc_no'] == '' || $res['card_name'] == ''){
                $project_info['is_ok'] = 2;
                $this->ajaxReturn($project_info);
            }
            $seller = array(
                "shop_id" => $this->shopId,
                "amount" => $amount,
                "type" => 'bank',
                "created" => time(),
                "bc_name" => $res['bc_name'],
                "bc_no" => $res['bc_no'],
                "card_name" => $res['card_name'],
                "card_no" => $res['card_no'],
                "address" => $res['address'],
            );
        }
        if($type == 'alipay'){
            $res = M('alipay_info')->where("project_id = {$projectId}")->find();
            if($res['alipay_accounts'] == ''){
                $project_info['is_ok'] = 3;
                $this->ajaxReturn($project_info);
            }
            $seller = array(
                "shop_id" => $this->shopId,
                "amount" => $amount,
                "type" => 'alipay',
                "created" => time(),
                "bc_name" => '',
                "bc_no" => $res['alipay_accounts'],
                "card_name" => '',
                "card_no" => '',
                "address" => '',
                );
        }
        $Model = M('transfers_request');
        $Model->add($seller);

        //事务结束
        $transaction->commit();
        $project_info['is_ok'] = 1;
        $this->ajaxReturn($project_info);
    }

    public function certNotice(){
        //实名认证检验
        $cert = M('transfers_auth')->where(array('shop_id'=>$this->shopId,'status'=>1,'project_id'=>substr($this->shopId, 0,-3)))->find();
        if(empty($cert) || empty($cert['card_name']) || empty($cert['card_no'])){
            //未实名认证
            $this->assign('is_cert','0');
        }else{
            $this->assign('is_cert','1');
        }
        //微信登录检验
        $admin = M("admin_user")->where(array('shop_id'=>$this->shopId))->find();
        if(!empty($admin['openid'])){
            //已微信登录
            $this->assign('is_wx_login','1');
        }else{
            $model = new \Common\Model\QrcodeModel();
            $url = '/seller/wxlogin/index?shop_id='.$this->shopId;
            $result = $model->create_new_code2($this->shopId, $url);
            $qr_url = $result['link'];
            $this->assign('qr_url',$qr_url);
            $this->assign('is_wx_login','0');
        }
        $this->display('notice');
    }
}