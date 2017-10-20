<?php
namespace Seller\Controller;

use Think\Controller;
use Org\IdWork;

class LoginController extends Controller
{
    private $cookieKey = 'auth_mobile';
    private $codeKey = 'login_valid';

    /**
     * 登录页面
     */
    public function index(){
        $mobile = cookie($this->cookieKey);
        $redirect = $_GET['redirect'] ? $_GET['redirect']: __MODULE__;
        $aliid = I('get.aliid','','int');
        $userid = I('get.userid','','int');
        $this->assign(array(
            'mobile'   => $mobile,
            'redirect' => $redirect,
            'aliid'    => $aliid,
            'userid'   => $userid,
        ));
        $this->display();
    }

    public function out(){
        session('manager', null);
        if($_GET['set']==1){
            redirect(C('host').'/seller');
        }else{
            $this->success('退出成功',C('host').'/seller');
        }
    }

    /**
     * 执行登陆
     */
    public function doLogin($data)
    {
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
        session('manager', array(
            'id'         => $user['id'],
            'username'   => $user['username'],
            'shop_id'    => $user['shop_id'],
            'project_id' => IdWork::getProjectId($user['shop_id'])
        ));
        session($this->codeKey, null);
        cookie($this->cookieKey, $user['username'], array('expire' => 3600 * 24 * 3));
    }

    /**
     * 校验手机号
     */
    public function exists(){
        $mobile = $this->getMobile();
        $result = array('btn' => '登　录', 'code' => 0);
        $Model = M('admin_user');
        $member = $Model->field("id, username, password")->where("username='{$mobile}'")->find();
        if(empty($member)){
            $result['btn']  = '创建小店';
            $result['code'] = 1;
        }else if(empty($member['password'])){
            $result['btn']  = '绑定密码';
            $result['code'] = 1;
        }
        $this->ajaxReturn($result);
    }

    protected function getMobile(){
        $mobile = $_POST['mobile'];
        if(!is_numeric($mobile) || strlen($mobile) != 11){
            $this->error('请输入11位手机号');
        }
        return $mobile;
    }

    /**
     * 获取验证码
     */
    public function code(){
        $mobile = $this->getMobile();
        $auth = session($this->codeKey);
        if(!empty($auth) && NOW_TIME - $auth['time'] < 60){
            $this->error('发送比较频繁，请1分钟后再试！');
        }

        $result = send_sms(array(
            'sign'   => '登录验证',
            'name'   => '用户',
            'mobile' => $mobile,
            'project_id' => 0
        ));

        if($result['errcode'] != 0){
            $this->error('验证码发送失败：'.$result['errmsg']);
        }

        $auth = array('time' => NOW_TIME, 'code' => $result['code'], 'mobile' => $mobile);
        session($this->codeKey, $auth);
        $this->success('验证码已发送，请注意查收'.(APP_DEBUG ? $result['code'] : ''));
    }

    /**
     * 执行登录APP
     */
    public function auth(){
        $mobile = $this->getMobile();
        if(strlen($_POST['password']) < 6){
            $this->error('密码不能少于6位');
        }
        
        //账号信息验证
        $Model = M('admin_user');
        $user = $Model->alias('users')
            ->field('users.*,shop.name AS shop_name,shop.aliid,shop.userid')
            ->join('shop ON users.shop_id=shop.id','INNER')
            ->where("users.username='%s'", addslashes(I('post.mobile')))
            ->find();
        $password = md5(I('post.password'));
        $shopName = I('post.shopName');
        $aliid = I('post.aliid','','int');
        $userid = I('post.userid','','int');
        // 校验验证码
        if(empty($user['password'])){
            $auth = session($this->codeKey);
            if(empty($auth) || floatval($auth['code']) != floatval($_POST['code']) || floatval($auth['mobile']) != floatval($mobile)){
                $this->loginError($user, '验证码无效');
            }else if(empty($user)){
                // 创建店铺
                $Model = new \Common\Model\ShopModel();
                $result = $Model->firstAdd(
                    array(
                        'mobile'   => $mobile,
                        'password' => I('post.password'),
                        'aliid'    => $aliid,
                        'userid'   => $userid,
                    ),
                    array(
                        'name' => $shopName
                    )
                );
                if(!$result){
                    $this->error($Model->getError());
                }else{
                    $user = array('id' => $result['login_id'], 'username' => $mobile, 'password' => $password, 'shop_id' => $result['shop_id']);
                    $this->write_session($user);
                    $url = '/seller';
                    $data = array('isurl'=>'1','url'=>$url);
                    $this->ajaxReturn($data);
                }
            }else{
//                $Model->execute("UPDATE admin_user SET password='{$password}' WHERE id=".$user['id']);
            }
        }else if($user['password'] != $password){
            $this->error('密码错误');
        }    
        if(empty($user['aliid']) || is_null($user['aliid']) || $user['aliid'] == '0'){
            M('shop')->where(array('id'=>$user['shop_id']))->save(array('aliid'=>$aliid));
            $shopInfo = M('shop_info')->where("id = '{$users['shop_id']}'")->find();
            $today        = date('Y-m-d H:i:s', NOW_TIME);
            $Model = new \Common\Model\ShopModel();
            $data = array(
                'shop_name'  => $user['shop_name'],
                'desc'       => $shopInfo['desc'],
                'starttime'  => $today,
                'endtime'    => date('Y-m-d H:i:s',strtotime($today)+24*30*3600),
                'status'     => '1',
            );
            $Model->synShopInfoTo1688($data,$aliid);
        }else{
            if($aliid!=$user['aliid'] && $aliid!=0 && !empty($aliid) && !is_null($aliid)){
                M('shop')->where(array('id'=>$user['shop_id']))->save(array('aliid'=>$aliid));
                $shopInfo = M('shop_info')->where("id = '{$users['shop_id']}'")->find();
                $today        = date('Y-m-d H:i:s', NOW_TIME);
                $Model = new \Common\Model\ShopModel();
                $data = array(
                    'shop_name'  => $user['shop_name'],
                    'desc'       => $shopInfo['desc'],
                    'starttime'  => $today,
                    'endtime'    => date('Y-m-d H:i:s',strtotime($today)+24*30*3600),
                    'status'     => '1',
                );
                $Model->synShopInfoTo1688($data,$aliid);
            }
        }
        if(empty($user['userid']) || is_null($user['userid'])){
            M('shop')->where(array('id'=>$user['shop_id']))->save(array('userid'=>$userid));
        }else{
            if($userid!=$user['userid'] && $userid!=0 && !empty($userid) && !is_null($userid)){
                M('shop')->where(array('id'=>$user['shop_id']))->save(array('userid'=>$userid));
            }
        }
        $this->write_session($user);
        $this->success();
    }

    protected function loginError($member, $errmsg){
        $result = array('btn' => '登　录', 'code' => 0, 'errmsg' => $errmsg);
        if(empty($member)){
            $result['btn']  = '立即注册';
            $result['code'] = 1;
        }else if(empty($member['password'])){
            $result['btn']  = '绑定密码';
            $result['code'] = 1;
        }
        $this->ajaxReturn($result);
    }
}