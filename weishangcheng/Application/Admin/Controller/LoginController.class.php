<?php
namespace Admin\Controller;

use Think\Controller;
use Org\IdWork;

class LoginController extends Controller
{
    /*
     * 是否启用验证码
     */
    private $enabledCaptcha = false;
    
    /**
     * 登录页面
     */
    public function index(){
        layout(false);
        $user = null;
        $redirect = !empty($_GET['redirect']) ? $_GET['redirect'] : "/shop";
        if(IS_GET){
            $session_user = session("user");
            if(empty($session_user)){
                $this->autoLogin($redirect);//自动登录验证
            }
            
            $this->assign('enabledCaptcha', $this->enabledCaptcha);
            die('444333');
            $this->display();
        }
        
        if($this->enabledCaptcha){
            if(strlen($_POST['captcha']) != 4){
                $this->ajaxReturn(array('errcode' => 3, 'errmsg' => '请输入4位验证码'));
            }else if(!$this->captcha($_POST['captcha'])){
                $this->ajaxReturn(array('errcode' => 3, 'errmsg' => '验证码错误'));
            }
        }
        
        $password = I('post.password');
        $username = I('post.username');
        if(strlen($password) > 0){
            $password = md5($password);
        }
        $this->doLogin(array(
            'username' => $username,
            'password' => $password,
            'redirect' => $redirect
        ));
        
    }

    /**
     * 自动登录验证
     * @return boolean|string|Ambigous <string, \Think\mixed>
     */
    private function autoLogin($redirect){
        $username = cookie('auto_login');
        if(!$username){
            return false;
        }
    
        $where = "username='".addslashes($username)."'";
        $Model = M("admin_auto_login");
        $data = $Model->where($where)->find();
        if(empty($data)){ //没有设置自动登录 就跳到登录页面让其输入信息
            return false;
        }
        
        if($data["last_time"] < NOW_TIME){
            $Model->where($where)->delete();
            return false;
        }
    
        $ip = get_client_ip();//获取登录人的ip地址
        if($ip != $data['ip']){
            $Model->where($where)->delete();
            return false;
        }
    
        $this->doLogin(array(
            'username' => $data['username'],
            'password' => $data['password'],
            'redirect' => $redirect
        ));
    }
    
    /**
     * 执行登陆
     */
    public function doLogin($data)
    {
        $result = array('errcode' => 0, 'errmsg' => '');
        
        //验证输入的信息
        if(strlen($data['username']) == 0){
            $this->ajaxReturn(array('errcode' => 1, 'errmsg' => '请输入账号'));
        }elseif(strlen($data['password']) == 0){
            $this->ajaxReturn(array('errcode' => 2, 'errmsg' => '请输入密码'));
        }
        
        //账号信息验证
        $Module = M('admin_user');
        $user = $Module->alias('users')
                       ->field('users.*,shop.name AS shop_name')
                       ->join('shop ON users.shop_id=shop.id','INNER')
                       ->where("users.username='%s'", addslashes($data['username']))
                       ->find();
        if (empty($user)) { // 账号不存在
            $this->ajaxReturn(array('errcode' => 1, 'errmsg' => '账号不存在'));
        } elseif ($user['password'] != $data['password']) { // 密码错误
            $this->ajaxReturn(array('errcode' => 2, 'errmsg' => '密码错误'));
        } elseif ($user['status'] == 0) { // 账号已被禁用
            $this->ajaxReturn(array('errcode' => 1, 'errmsg' => '账号未启用'));
        }

        // 登录成功
        $this->write_session($user);
        if(IS_POST){
            if($_POST['auto_login']){
                $this->do_Auto_login($user['username'], $user['password']);
            }else{
                cookie('auto_login', null);
            }
        }
        if(IS_GET){
            redirect($data['redirect']);
        }else{
            $this->ajaxReturn(array('errcode' => 0, 'errmsg' => '登陆成功', 'url' => $data['redirect']));
        }
    }
    
    /**
     * 登录后存储session数据并跳转页面
     */
    private function write_session($user){
        // 将用户固定的信息存入session中
        session('user', array(
            'id'         => $user['id'],
            'nick'       => $user['nick'],
            'is_admin'   => $user['administrator'],
            'username'   => $user['username'],
            'shop_id'    => $user['shop_id'],
            'shop_name'  => $user['shop_name'],
            'project_id' => IdWork::getProjectId($user['shop_id'])
        ));
    }
    
    /**
     * 保存自动登录信息
     * $password 已加密
     * @param unknown $username
     */
    private function do_Auto_login($username,$password){
        $ip = get_client_ip();//获取登录人的ip地址
        $where = "username='%s'";
        $auto_data = M("admin_auto_login")->where($where,$username)->find();
        if(empty($auto_data)){ //添加
            $add["username"] = $username;
            $add["ip"] = $ip;
            $add["last_time"] = NOW_TIME+259200;
            $add["password"] = $password;
            M("admin_auto_login")->add($add);
        }else{ //修改
            $up["ip"] = $ip;
            $up["last_time"] = NOW_TIME+259200;
            $up["password"] = $password;
            M("admin_auto_login")->where("username='".$auto_data["username"]."'")->save($up);
        }
        //设置cookie
        cookie('auto_login', $username, array('expire'=>259200,'prefix'=>''));
    }
    
    /**
     * 退出登录
     */
    public function out(){
        $username = session('user.username');
        session('[destroy]');
        cookie("auto_login",null);
        if(strlen($username) > 0){
            M("admin_auto_login")->where("username='".addslashes($username)."'")->delete();
        }
        redirect(__MODULE__.'/login');
    }
    
    /**
     * 验证码
     */
    public function captcha($code = ''){
        $config =    array(
            'fontSize'    =>    36,    // 验证码字体大小
            'length'      =>    4,     // 验证码位数
            'useNoise'    =>    false, // 关闭验证码杂点
            'useCurve'    => true,
            'codeSet'     => '0123456789',
            'seKey'       => 'admin_login'
        );
        $Verify = new \Think\Verify($config);
        if(is_numeric($code)){
            return $Verify->check($code);
        }
        $Verify->entry();
        exit();
    }

    public function goods_qr(){
        $id = I('post.id');
        $price = I('post.price');
        $shop = I('post.shop_id');
        $isCYB = I('post.isCYB');
        $share_mid = I('post.share_mid', 0);
        $admin = I('get.admin', 0);
        if (is_numeric($id) && $id > 0){
            //记录分享信息
            M('share_data')->add(array('shop_id'=>$shop,'goods_id'=>$id,'sharetime'=>date('Y-m-d H:i:s')));
            //生成二维码
            $mid = '/goods?id='.$id;
            $model = new \Common\Model\QrcodeNewModel();
            if ($admin == 1){
                $result = $model->create_goods_code_guanzhu($shop, $mid, $id,'¥ '.$price, $share_mid);
            }else{
                $result = $model->create_goods_code($shop, $mid, $id,'¥ '.$price, $share_mid);
            }
            $url = $result['link'];
            if($isCYB == 'true'){
                //采源宝端
                $goods_content = M('mall_goods_content')->field('detail,images')->find($id);
                $goods = M('mall_goods')->field('title')->find($id);
                $goods_content['detail'] = strip_tags($goods_content['detail'],'<img>');
                $goods_content['detail']= preg_replace("/<(\/?img.*?)>/si","",$goods_content['detail']); //过滤img标签
                $goods['detail'] = str_replace('{','',$goods_content['detail']);
                $goods['images'] = explode(',', $goods_content['images']);
                $goodsImg = array(0=>$url);
                foreach ($goods['images'] as $key => $value) {
                    $goodsImg[] = $value;
                }
                $goods['images'] = $goodsImg;
                $goods['isCYB'] = $isCYB;
                $this->ajaxReturn($goods);
            }else{
                $this->ajaxReturn($url);
            }
        }
    }

    public function shop_qr(){
        $model = new \Common\Model\QrcodeModel();
        $mid = I('post.id');
        $shop = I('post.shop_id');
        $mids = '?share_mid='.$mid;
        $admin = I('get.admin', 0);
        //记录分享信息
        M('share_data')->add(array('shop_id'=>$shop,'goods_id'=>0,'sharetime'=>date('Y-m-d H:i:s')));
        if ($admin == 1){
            $result = $model->create_code_guanzhu($shop, $mid);
        }else{
            $result = $model->create_code($shop, $mids);
        }
        $url = $result['link'];
        $this->ajaxReturn($url);
    }

    /**
     * seller 2017/9/1
     */
    public function shop_qr2(){
        $shop = I('post.shop_id');
        $admin = I('get.admin', 0);
        $model = new \Common\Model\QrcodeModel();
        //记录分享信息
        M('share_data')->add(array('shop_id'=>$shop,'goods_id'=>0,'sharetime'=>date('Y-m-d H:i:s')));
        if ($admin == 1){
            $result = $model->create_code_guanzhu($shop, 0);
        }else{
            $result = $model->create_code($shop, 0);
        }
        $url = $result['link'];
        $this->ajaxReturn($url);
    }

    /**
     *付款完事 点关闭按钮  弹窗二维码 店铺
     */
    public function shop_guanzhu(){
        $model = new \Common\Model\QrcodeNewModel();
        $shop = I('post.shop_id');
        $result = $model->create_code_guanzhu($shop, 0);
        $url = $result['link'];
        $this->ajaxReturn($url);
    }

    /**
     *支付下面二维码
     */
    public function shop_guanzhu_code(){
        $model = new \Common\Model\QrcodeNewModel();
        $shop = I('post.shop_id');
        $result = $model->create_code_guanzhu_code($shop, 0);
        $url = $result['link'];
        $this->ajaxReturn($url);
    }

}