<?php

namespace Seller\Controller;
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/4/6
 * Time: 18:18
 */
class CreateController extends ManagerController{
    function __construct(){
        parent::__noGetShop();
    }

    public function index(){
        $id = $this->user('id');
        $user = M('member')->field('mobile, id')->where("id = {$id}")->find();
        if (!isset($user['mobile'])){
            $user['mobile'] = '';
        }
        $mid = $user['id'];
        if (IS_AJAX){
            $post = I('post.');
            $model = new \Seller\Model\CreateModel();
            $result = $model->create_ps($mid, $post['name']);
            $up = array(
                "wxid" => $post['wxid'],
            );
            M('member')->where("id = {$mid}")->save($up);
//            $this->success('11', '/seller/index');
        }
        $this->assign('user', $user);
        $this->display();
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
        $project = M('project')->where("manager_id = {$data['id']}")->find();
        if (!empty($project)){
            $data['project_id'] = $project['id'];
        }else{
            $data['project_id'] = 0;
        }
        $data['sign'] = '登录验证';
        $data['mobile'] = $phone;
        $res = send_sms($data);
        if ($res['code'] > 0) {
            session("check",array('phone'=>$phone, "num" =>$res['code'],"time" =>$now + 60));
            $this->success('已发送');
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
        $post = $_POST['data'];
        $data = array(
            "id"            => $post['id'],
            "mobile"        => $post['mobile'],
            "checknum"      => $post['checknum'],
        );
        #判断如果手机号没改，就不走验证码流程
        $id = $this->user('id');
        $user = M('member')->field('mobile')->where("id = {$id}")->find();
        $Model = D("Member");
        if ($user['mobile'] != $data['mobile']) {
            $check = session('check');
            if(!is_array($check) || $check['num'] != $data['checknum'] || $check['phone'] != $data['mobile']){
                $this->error('验证码错误');
            }
        }
        $res = $Model->edit($data);
        if($res > -1){
            session('check', null);
            $this->success("保存成功");
        }
        $this->error('存储失败');
    }
}