<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/5/18
 * Time: 10:21
 */

namespace Seller\Controller;


class ShopController extends ManagerController
{
    public function index(){
        if (IS_AJAX){
            $post = I('post.');
            $shopUp = array('id' => $this->shopId);
            if (!empty($post['name'])){
                $shopUp['name'] = $post['name'];
            }
            if (!empty($post['logo'])){
                $shopUp['logo'] = $post['logo'];
            }
            $shopInfo = array();
            if (!empty($post['wlcm'])){
                $shopInfo['shop_sign'] = $post['wlcm'];
            }
            if (!empty($post['manager_qr'])){
                $shopInfo['owners_wx'] = $post['manager_qr'];
            }
            if (!empty($post['server_qr'])){
                $shopInfo['kefu_wx'] = $post['server_qr'];
            }
            if (!empty($post['mobile'])){
                $shopInfo['mobile'] = $post['mobile'];
            }
            if (!empty($post['desc'])){
                $shopInfo['desc'] = $post['desc'];
            }

            $Model = new \Common\Model\ShopModel();
            if ($Model->update($shopUp, $shopInfo)){
                $this->ajaxReturn('1');
            }else{
                $this->ajaxReturn($Model->getError());
            }
        }
        $id = $this->user('id');
        $t_auth = M('transfers_auth')->field('id, status, created')->where("id = '{$id}'")->find();
        if (empty($t_auth)){
            $t_auth['status'] = -1;
        }
        switch ($t_auth['status']){
            case -1:
                $auth_status = 'unvalidate';
                break;
            case 0:
                $auth_status = 'waiting';
                break;
            case 1:
                $auth_status = 'validated';
                break;
            case 2:
                $auth_status = 'rejected';
                break;
        }
        $this->assign('auth_status', $auth_status);
        $data = M('shop')
            ->alias('s')
            ->field('s.id, s.name, s.logo, si.mobile, si.desc, si.shop_sign, si.owners_wx, si.kefu_wx, si.wx_nick, si.wx_no, si.pay_qr,si.pay_zfb')
            ->join('shop_info as si on si.id = s.id')
            ->where("s.id = {$this->shopId}")
            ->find();
        if ($data['logo'] == ''){
            $data['logo'] = C('CDN').'/img/logo_108.png';
        }
        if ($data['shop_sign'] == ''){
            $data['is_shop_sign'] = '默认';
        }elseif ($data['shop_sign'] != str_replace('/img/seller/default_shopsign1.jpg', '', $data['shop_sign'])){
            $data['is_shop_sign'] = '默认一';
        }elseif ($data['shop_sign'] != str_replace('/img/seller/default_shopsign2.jpg', '', $data['shop_sign'])){
            $data['is_shop_sign'] = '默认二';
        }elseif ($data['shop_sign'] != str_replace('/img/seller/default_shopsign3.jpg', '', $data['shop_sign'])){
            $data['is_shop_sign'] = '默认三';
        }else{
            $data['is_shop_sign'] = '自定义';
        }

        if ($data['pay_zfb'] == '' && $data['pay_qr'] == ''){
            $pay_code = 1;
        }
        if ($data['pay_zfb'] != '' && $data['pay_qr'] != ''){
            $pay_code = 2;
        }
        if ($data['pay_zfb'] == '' && $data['pay_qr'] != ''){
            $pay_code = 3;
        }
        if ($data['pay_zfb'] != '' && $data['pay_qr'] == ''){
            $pay_code = 4;
        }
        $this->assign('pay_code', !empty($pay_code) ? $pay_code : 1);

        if (!empty($data['desc'])){
            $data['is_desc'] = '已填写';
        }else{
            $data['is_desc'] = '未填写';
        }
        if (I('get.from') == 'index'){
            $this->delcookie();
        }else{
            //取回cookie
            if (isset($_COOKIE['shop_name'])){
                $data['name'] = urldecode($_COOKIE['shop_name']);
                setcookie("shop_name", "", time()-3600);
            }

            if (isset($_COOKIE['shop_logo'])){
                $data['logo'] = $_COOKIE['shop_logo'];
                setcookie("shop_logo", "", time()-3600);
            }

            if (isset($_COOKIE['shop_mobile'])){
                $data['mobile'] = $_COOKIE['shop_mobile'];
                setcookie("shop_mobile", "", time()-3600);
            }

            if (isset($_COOKIE['wx_nick'])){
                $data['wx_nick'] = $_COOKIE['wx_nick'];
                setcookie("wx_nick", "", time()-3600);
            }

            if (isset($_COOKIE['wx_id'])){
                $data['wx_no'] = $_COOKIE['wx_id'];
                setcookie("wx_id", "", time()-3600);
            }

            if (isset($_COOKIE['shop_owner_wx'])){
                $data['owners_wx'] = $_COOKIE['shop_owner_wx'];
                setcookie("shop_owner_wx", "", time()-3600);
            }

            if (isset($_COOKIE['shop_kefu_wx'])){
                $data['kefu_wx'] = $_COOKIE['shop_kefu_wx'];
                setcookie("shop_kefu_wx", "", time()-3600);
            }

            if (isset($_COOKIE['shop_owner_pay_wx'])){
                $data['pay_qr'] = $_COOKIE['shop_owner_pay_wx'];
                setcookie("shop_owner_pay_wx", "", time()-3600);
            }
            if (isset($_COOKIE['shop_owner_pay_zfb'])){
                $data['pay_zfb'] = $_COOKIE['shop_owner_pay_zfb'];
                setcookie("shop_owner_pay_wx", "", time()-3600);
            }
        }
        
        $this->assign('username',$this->user('username'));
        $this->assign('data', $data);
        $this->display();
    }

    /*
     * 店招设置
     */
    public function set_custom_picture(){
        if (IS_AJAX){
            $custom_pic = I('post.custom_pic');
            $add = array(
                "id" => $this->shopId,
                "custom_pic" => $custom_pic
            );
            M('shop_info')->save($add);
            $this->ajaxReturn('1');
        }
    }

    /*
     * 店招设置
     */
    public function shop_sign(){
        if (IS_AJAX){
            $shop_sign = I('post.shop_sign');
            if ($shop_sign != str_replace("/img/seller/add_shopsign.jpg", "", $shop_sign)){
                $shop_sign = '';
            }
            if ($shop_sign != str_replace("/img/seller/none_shop.jpg", "", $shop_sign)){
                $shop_sign = '';
            }
            $add = array(
                "id" => $this->shopId,
                "shop_sign" => $shop_sign
            );
            M('shop_info')->save($add);
            $this->ajaxReturn('1');
        }

        $select = array(
            1 => '',
            2 => '',
            3 => '',
            4 => '',
        );

        $data = M('shop_info')
            ->field("shop_sign, custom_pic")
            ->where(array("id" => $this->shopId))
            ->find();

        if (!empty($data['shop_sign'])){
            $shop_sign_big = $data['shop_sign'];
        }else{
            $shop_sign_big = C('CDN').'/img/seller/none_shop.jpg';
        }
        $haspic = '';
        if (!empty($data['custom_pic'])){
            $custom_pic = $data['custom_pic'];
            $haspic = 'default_sign';
        }else{
            $custom_pic = C('CDN').'/img/seller/add_shopsign.jpg';
        }

        if ($shop_sign_big != str_replace("/img/seller/default_shopsign1.jpg", "", $shop_sign_big)){
            $select[1] = 'on';
            $select[4] = '';
            $select[2] = '';
            $select[3] = '';
        }
        if ($shop_sign_big != str_replace("/img/seller/default_shopsign2.jpg", "", $shop_sign_big)){
            $select[2] = 'on';
            $select[4] = '';
            $select[1] = '';
            $select[3] = '';
        }
        if ($shop_sign_big != str_replace("/img/seller/default_shopsign3.jpg", "", $shop_sign_big)){
            $select[3] = 'on';
            $select[4] = '';
            $select[1] = '';
            $select[2] = '';
        }
        if ($shop_sign_big == $custom_pic){
            $select[4] = 'on';
            $select[1] = '';
            $select[2] = '';
            $select[3] = '';
        }

        $this->assign('haspic' , $haspic);
        $this->assign('select' , $select);
        $this->assign('shop_sign_big' , $shop_sign_big);
        $this->assign('custom_pic' , $custom_pic);
        $this->display();
    }

    /*
     * 店铺简介
     */
    public function profile(){
        if (IS_AJAX){
            $add = array(
                "id" => $this->shopId,
                "desc" => I('post.desc')
            );
            M('shop_info')->save($add);
            $this->ajaxReturn('1');
        }
        $data = M('shop_info')
            ->field("desc")
            ->where(array("id" => $this->shopId))
            ->find();
        if (!empty($data)){
            $desc = $data['desc'];
        }else{
            $desc = '';
        }
        $n1 = mb_strlen($desc);
        $n2 = strlen($desc);
        $b1 = ($n2-$n1)/2;
        $b2 = $n1 - $b1;
        $desc_num = $b1 * 2 + $b2;

        $this->assign('desc_num',$desc_num);
        $this->assign('desc' , $desc);
        $this->display();
    }


    public function update_auth(){
        if (IS_AJAX){
            $id = $this->user('id');
            $data = M('transfers_auth')
                ->field('status')
                ->where("id = '{$id}'")
                ->find();
            if (empty($data)){
                $add = array(
                    'id' => $id,
                    'created' => time(),
                    'status' => 0,
                    'project_id' => substr($this->shopId, 0, -3),
                    'shop_id' => $this->shopId,
                    'card_pic' => I('post.card_img'),
                    'card_name' => I('post.true_name'),
                    'card_no' => I('post.card_num')
                );
                M('transfers_auth')->add($add);
                $msg = '1';
            }else{
                if ($data['status'] == 0 || $data['status'] == 1){
                    $msg = '1';
                }else{
                    $up = array(
                        'created' => time(),
                        'status' => 0,
                        'project_id' => substr($this->shopId, 0, -3),
                        'shop_id' => $this->shopId,
                        'card_pic' => I('post.card_img'),
                        'card_name' => I('post.true_name'),
                        'card_no' => I('post.card_num')
                    );
                    M('transfers_auth')->where("id = '{$id}' AND status = 2")->save($up);
                    $msg = '1';
                }
            }
            $this->ajaxReturn($msg);
        }
    }

    public function ajax_save(){
        $post = I('post.');
        $shopUp = M('shop')->where(array("id" => $this->shopId))->find();
        if (!empty($post['name'])){
            $shopUp['name'] = addslashes($post['name']);
        }
        if (!empty($post['logo'])){
            $shopUp['logo'] = $post['logo'];
        }

        $shopInfo = array();
        if (!empty($post['wlcm'])){
            $shopInfo['shop_sign'] = $post['wlcm'];
        }
        if (!empty($post['manager_qr'])){
            $shopInfo['owners_wx'] = $post['manager_qr'];
        }else{
            $shopInfo['owners_wx'] = '';
        }
        if (!empty($post['server_qr'])){
            $shopInfo['kefu_wx'] = $post['server_qr'];
        }else{
            $shopInfo['kefu_wx'] = '';
        }
        if (!empty($post['mobile'])){
            $shopInfo['mobile'] = $post['mobile'];
        }else{
            $shopInfo['mobile'] = '';
        }
        if (!empty($post['desc'])){
            $shopInfo['desc'] = $post['desc'];
        }
        if(!empty($post['wxnick'])){
            $shopInfo['wx_nick'] = $post['wxnick'];
        }else{
            $shopInfo['wx_nick'] = '';
        }
        if(!empty($post['wxid'])){
            $shopInfo['wx_no'] = $post['wxid'];
        }else{
            $shopInfo['wx_no'] = '';
        }
//        if(!empty($post['dianzhu_pay_qr'])){
//            $shopInfo['pay_qr'] = $post['dianzhu_pay_qr'];
//        }else{
//            $shopInfo['pay_qr'] = '';
//        }
//        if(!empty($post['dianzhu_pay_zfb'])){
//            $shopInfo['pay_zfb'] = $post['dianzhu_pay_zfb'];
//        }else{
//            $shopInfo['pay_zfb'] = '';
//        }
        $Model = new \Common\Model\ShopModel();
        $exists = $Model->existsName($shopUp['name'], $shopUp['id']);
        if($exists){
            $this->error('店铺名称已存在，请更改!');
        }
        M('shop')->where('id='.$this->shopId)->save($shopUp);

        M('shop_info')->where('id='.$this->shopId)->save($shopInfo);
        $this->ajaxReturn('1');
    }

    /**
     * 手机号短信验证
     */
    public function check(){
        $phone = I('phone','','/^\d+$/');
        #号码非数字报错
        if (strlen($phone) == 0) {
            $this->error('请输入手机号');
        }
        // 判断上次验证码是否未过60秒
        $check = session("check");
        $now = time();
        if (is_array($check) && $now < $check['time']) {
            $this->success('验证码已发送');
        }
        $data = array();
        $data['id'] = $this->user('id');
        $data['project_id'] = substr($this->shopId, 0, -3);
        $data['sign'] = '修改手机号';
        $data['mobile'] = $phone;
        $res = send_sms($data);
        if ($res['code'] > 0) {
            session("check",array('phone'=>$phone, "num" =>$res['code'],"time" =>$now + 60));
            $this->success('验证码已发送'.(APP_DEBUG ? $res['code'] : ''));
        } else {
            if ( $res['errcode'] == 15 )
                $msg = '发送验证码过于频繁';
            else
                $msg = '发送失败';
            $this->error($msg);
        }
    }

    /**
     * 保存个人资料 手机号
     */
    public function save(){
        $post = I('post.data');
        $data = array(
            "id"            => $post['id'],
            "mobile"        => $post['mobile'],
            "checknum"      => $post['checknum'],
        );
        #判断如果手机号没改，就不走验证码流程
        $id = $this->user('id');
        if ($data['id'] != $id){
            $this->error('保存失败');
        }
        $user = M('admin_user')->field('username as mobile')->where("id = {$id}")->find();
        if ($user['mobile'] != $data['mobile']) {
            $check = session('check');
            if(!is_array($check) || $check['num'] != $data['checknum'] || $check['phone'] != $data['mobile']){
                $this->error('验证码错误');
            }
        }
        $res = $this->save_mobile($data);
        if($res > -1){
            session('check', null);
            $this->success("保存成功");
        }
        $this->error('保存失败');
    }

    public function save_mobile($data){
        if(! preg_match('/^1[3|4|5|7|8]\d{9}$/', $data['mobile'])){
            $this->success("手机号格式错误");
            return -1;
        }
        $up = array('username' => $data['mobile']);
        M('admin_user')->where("id = ".addslashes($data['id']))->save($up);
        return 1;
    }

    private function delcookie(){
        setcookie("shop_name", "", time()-3600);
        setcookie("shop_logo", "", time()-3600);
        setcookie("shop_mobile", "", time()-3600);
        setcookie("shop_owner_wx", "", time()-3600);
        setcookie("shop_kefu_wx", "", time()-3600);
        setcookie("wx_nick", "", time()-3600);
        setcookie("wx_id", "", time()-3600);
        setcookie("shop_owner_pay_wx", "", time()-3600);
        setcookie("shop_owner_pay_zfb", "", time()-3600);
    }
    /*
     * 帐号设置
     */
    public function setAccounts(){
        $username = $this->user('username');
        if(!empty($username)){
            $this->assign('username',$username);
        }else{
            $this->error("参数错误");
        }
        $this->display('account');
    }
    /*
     * 修改密码
     */
    public function changePwd(){
        if(IS_AJAX){
            $yuanPwd = I('post.yuan_pwd','');
            if($yuanPwd == ''){
                $this->ajaxReturn('emptyYuan');
            }
            $newPwd = I('post.new_pwd','');
            if($newPwd == ''){
                $this->ajaxReturn('emptyNew');
            }
            $surePwd = I('post.sure_pwd','');
            if($surePwd == ''){
                $this->ajaxReturn('emptySure');
            }
            $admin = M('admin_user')->find($this->user('id'));
            if($admin['password'] != md5($yuanPwd)){
                $this->ajaxReturn('errorYuan');
            }else if($newPwd!=$surePwd){
                $this->ajaxReturn('pwdDiff');
            }else{
                M('admin_user')->where(array('id'=>$this->user('id')))->save(array('password'=>md5($newPwd)));
                $this->ajaxReturn('success');
            }
        }
        $this->display('changePwd');
    }

    /*
     * 收款码
     */
    public function pay_code(){
        if (IS_AJAX){
            $post = I('post.');
            if (!empty($post['zfb']) || !empty($post['wx'])){
                $up = array(
                    "pay_zfb" => !empty($post['zfb']) ? $post['zfb'] : '',
                    "pay_qr" => !empty($post['wx']) ? $post['wx'] : ''
                );
                M('shop_info')->where(array("id" => $this->shopId))->save($up);
            }
            $this->ajaxReturn(1);
        }
        $re = array(
            "zfb" => '',
            "wx" => ''
        );
        $shopInfo = M('shop_info')
            ->where(array("id" => $this->shopId))
            ->find();
        if (!empty($shopInfo['pay_zfb'])){
            $re['zfb'] = $shopInfo['pay_zfb'];
        }
        if (!empty($shopInfo['pay_qr'])){
            $re['wx'] = $shopInfo['pay_qr'];
        }
        $this->assign('re', $re);
        $this->display();
    }
}