<?php
namespace H5\Controller;
use Common\Common\CommonController;

/**
 * 用户管理
 * 
 * @author 兰学宝
 *        
 */
class AdminController extends CommonController
{
    public function index(){
        $user = $this->user("openid, nickname, headimgurl");
        $this->assign(array(
            'user'  => $user,
            'shop'  => $this->shop
        ));
        $this->display();
    }
    
    public function login(){
        $user = $this->user("openid, nickname, headimgurl");
        $this->assign(array(
            'user'  => $user,
            'shop'  => $this->shop
        ));
        $this->display();
    }
    
    public function bind(){
        $openid = $this->user("openid");
        $host = $_SERVER["HTTP_HOST"];
        $username = I("post.username");
        $password = I("post.password");
        $password = md5($password);
        
        $Model = M('users');
        $user = $Model->where("username='%s'", $username)->find();
        if(empty($user)){
            $this->error('账号不存在');
        }else if($user['password'] != $password){
            $this->error('密码错误');
        }
        
        // 判断此人是否为此店铺管理员
        $SUModel = M('shop_user');
        $shopUser = $SUModel->where("shop_id={$this->shop['id']} AND user_id={$user['id']}")->find();
        if(empty($shopUser)){
            $this->error("您不是此店铺的管理员,无法绑定");
        }
        
        $SUModel->where("id=".$shopUser['id'])->save(array("openid" => $openid));
        
        // session处理
        
        $this->success("绑定成功");
    }
}
?>