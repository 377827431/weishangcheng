<?php
namespace Admin\Controller;

use Think\Controller;
use Common\Model\ShopModel;

class RegisterController extends Controller
{
    private $smsKey = 'register_platform';
    public function index(){
        layout(false);
        $this->display();
    }
   
    /**
     * 获取短信验证码
     */
    public function captcha(){
        $mobile = $_POST['mobile'];
        if (!is_numeric($mobile) || strlen($mobile) != 11) {
            $this->error('请输入正确的11位手机号');
        }
         
        $check = session($this->smsKey);
        if($check && $check['mobile'] == $mobile && $check['time'] + 60 > NOW_TIME){
            $this->success('验证码已发送'.(APP_DEBUG ? $check['code'] : ''));
        }
        
        $result = send_sms(array(
            'mobile' => $mobile,
            'sign'   => '注册开店',
            'name'   => '用户',
        ));
        if($result['errcode'] == 0){
            session($this->smsKey, array('time' => NOW_TIME, 'code' => $result['code'], 'mobile' => $mobile));
            $this->success('验证码已发送'.(APP_DEBUG ? $result['code'] : ''));
        }
        
        $this->error($result['errmsg']);
    }
    
    /**
     * 立即创建
     */
    public function create(){
        if(!is_numeric($_POST['mobile']) || strlen($_POST['mobile']) != 11){
            $this->error('请输入手机号');
        }else if(!is_numeric($_POST['captcha']) || strlen($_POST['captcha']) != 6){
            $this->error('请输入6位短信验证码');
        }
        
        $len = mb_strlen($_POST['name'], 'UTF-8');
        if($len < 2 || $len > 15){
            $this->error('名称应在2到15个字符之间');
        }
        
        $len = mb_strlen($_POST['password'], 'UTF-8');
        if($len < 6 || $len > 20){
            $this->error('密码应在6到20个字符之间');
        }

        $captcha = session($this->smsKey);
        if(!$captcha || $captcha['mobile'] != $_POST['mobile'] || $captcha['code'] != $_POST['captcha']){
            $this->error('验证码无效，请重新获取');
        }
        
        // 创建店铺
        $Model = new ShopModel();
        $result = $Model->firstAdd(
                    array(
                        'mobile'   => $_POST['mobile'],
                        'password' => $_POST['password'],
                    ),
                    array(
                        'name' => $_POST['name']
                    )
                  );
        if(!$result){
            $this->error($Model->getError());
        }
        
        session('[destroy]');
        session('user', array(
            'id'         => $result['login_id'],
            'nick'       => '超级管理员',
            'is_admin'   => 1,
            'username'   => $_POST['mobile'],
            'shop_id'    => $result['shop_id'],
            'shop_name'  => $_POST['name'],
            'project_id' => $result['project_id']
        ));
        $this->success('创建成功',__MODULE__.'/shop');
    }
}
?>