<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Common\Common\Auth;
use Think\Cache\Driver\Redis;

/**
 * 后台首页
 *
 */
class IndexController extends CommonController
{
    
    public function index(){
        $groups = Auth::get()->getMenuGroups();
        $redirect = '';
        foreach($groups as $group){
            if($group['link']){
                $redirect = $group['link'];
                break;
            }
        }
        
        if(!$redirect){
            exit('无权限');
        }
        redirect($redirect, 0);
    }
    
    /**
     * 修改我的密码
     */
    public function modifyPwd(){
        $user_id = $this->user('id');
        if(IS_POST){
            $password = I('post.password');
            if($password == ''){
                $this->error('新密码不能为空！');
            }
            if($password != $_POST['password2']){
                $this->error('两次密码不一致！');
            }
    
            M()->execute("UPDATE users SET password='".md5($password)."' WHERE id=".$user_id);
    
            $this->success('修改成功！');
        }
    
        $this->display();
    }
    
    /**
     * 切换店铺
     */
   public function modifySwitch(){
       $user_id = $this->user('id');
       if(IS_POST){
           $list = addslashes($_POST['shop']);
           $shop = explode(',',$list);
           $shop_id = $shop[0];
           $shop_name = $shop[1];
           if(!is_numeric($shop_id)){
               $this->error('店铺ID不能为空');
           }
          // M()->execute("UPDATE users SET shop_id='{$shop_id}' WHERE id=".$user_id);
           session_start();
           $_SESSION['admin']['user']['shop_id'] = $shop_id;
           $_SESSION['admin']['user']['shop_name'] = $shop_name;
           $this->success('切换成功',$_SERVER['HTTP_REFERER']);
       }
       
       $session = session('user');
       if($session['is_admin']==1){
           $data = $this->shops();
       }else{
           $aid = M('users')->field('shop_id,association_id')->find($user_id);
           $map['id'] = array('in',$aid['association_id']);
           if($aid['shop_id']!=$_SESSION['admin']['user']['shop_id']){
               $map['id'] = array('in',$aid['association_id'].','.$aid['shop_id']);
           }
           
           $data = M('shop')->field('id,name')->where($map)->select();
       }
       
       $this->assign('data',$data);
       $this->display();
   }
    
    /**
     * 修改我的账户
     */
    public function modifyAccount(){
        $user = $this->user('id,nick');
        if(IS_POST){
            if(empty($_POST['nick'])){
                $this->error('请填写账户昵称！');
            }
            
            M()->execute("UPDATE users SET nick='%s' WHERE id=".$user['id'],$_POST['nick']);
            $this->success('修改成功！');
        }
    
        $this->assign('user',$user);
        $this->display();
    }
    
    public function qr(){
        $auth = new \Org\Wechat\WechatAuth();
        $result = $auth->qrcodeCreate('lxkf');
        redirect('https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$result['ticket']);
    }
    
    /*
    * 1688授权
    */
    public function aliOAuth(){
        vendor('AopSDK.aop.AOPOAuth');
		$auth = new \AOPOAuth();
        $auth->setParam(array(
            'redirect_uri'  => $_SERVER['HTTP_HOST'].'/index/setToken',
            'state' => 'wslm'
        ));
        $auth->doOAuth();
    }
    
    /*
    * 获取1688授权剩余天数
    */
    public function setToken(){
        vendor('AopSDK.AopSdk');
        $api = new \AOPAPI();
        $result = $api->getToken($_GET['code']);
        $data = array(
            'ali_id' => $result->aliId,
            'access_token' => $result->access_token,
            'expires_in' => NOW_TIME+$result->expires_in - 600,
            'refresh_token' => $result->refresh_token,
            'login_id' => $result->resource_owner,
            'member_id' => $result->memberId,
            'refresh_token_timeout' => substr($result->refresh_token_timeout,0,14)
        );
        
        M('alibaba_token')->add($data, null, true);
        redirect("", 0);
    }
}
?>