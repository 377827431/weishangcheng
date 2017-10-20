<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 扫码登陆
 * 
 * @author lanxuebao
 *        
 */
class LoginController extends CommonController
{
    private $cookieKey = 'auth_mobile';
    private $codeKey = 'login_valid';
    private $project;

    public function __seller(){
        parent::__construct();
    }

    public function __construct(){
        parent::__construct();
        if (APP_NAME != P_USER){
            $this->project = get_project(APP_NAME);
        }
    }
    
    public function index(){
        $mobile = cookie($this->cookieKey);
        $redirect = $_GET['redirect'] ? $_GET['redirect']: __MODULE__.'/mall';
        $apply = $_GET['apply'] ? $_GET['apply']:'';
        $share_mid = $_GET['share_mid'] ? $_GET['share_mid']:'';
        $this->assign(array(
            'mobile'      => $mobile,
            'redirect'    => $redirect,
            'appid'       => $project['appid'],
            'logo'        => $project['logo'],
            'apply'       => $apply,
            'share_mid'    => $share_mid
        ));
        $this->display();
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
            'project_id' => $this->project['id']
        ));
        
        if($result['errcode'] != 0){
            $this->error('验证码发送失败：'.$result['errmsg']);
        }
        
        $auth = array('time' => NOW_TIME, 'code' => $result['code'], 'mobile' => $mobile);
        session($this->codeKey, $auth);
        $this->success('验证码已发送，请注意查收'.(APP_DEBUG ? $result['code'] : ''));
    }
    
    protected function getMobile(){
        $mobile = $_POST['mobile'];
        if(!is_numeric($mobile) || strlen($mobile) != 11){
            $this->error('请输入11位手机号');
        }
        return $mobile;
    }
    
    /**
     * 校验手机号
     */
    public function exists(){
        $mobile = $this->getMobile();
        
        $result = array('btn' => '登　录', 'code' => 0);
        
        $Model = M('member');
        $member = $Model->field("id, mobile, password")->where("mobile='{$mobile}'")->find();
        if(empty($member)){
            $result['btn']  = '立即注册';
            $result['code'] = 1;
        }else if(empty($member['password'])){
            $result['btn']  = '绑定密码';
            $result['code'] = 1;
        }
        $this->ajaxReturn($result);
    }
    
    /**
     * 执行登录APP
     */
    public function auth(){
        $mobile = $this->getMobile();
        if(strlen($_POST['password']) < 6){
            $this->error('密码不能少于6位');
        }

        $Model = M('member');
        $member = $Model->field("id, mobile, password")->where("mobile='{$mobile}'")->find();
        $password = md5($_POST['password']);
        
        // 校验验证码
        if(empty($member['password'])){
            $auth = session($this->codeKey);
            if(empty($auth) || floatval($auth['code']) != floatval($_POST['code']) || floatval($auth['mobile']) != floatval($mobile)){
                $this->loginError($member, '验证码无效');
            }else if(empty($member)){
                $member = array('mobile' => $mobile, 'password' => $password, 'from_way' => strtolower($_SERVER['PATH_INFO']), 'created' => NOW_TIME);
                $member['id'] = $Model->add($member);
            }else{
                $Model->execute("UPDATE member SET password='{$password}' WHERE id=".$member['id']);
            }
        }else if($member['password'] != $password){
            $this->error('密码错误');
        }
        if(!empty($_POST['apply']) && !empty($member['id'])){
            $projectId = substr($_POST['apply'],0,-3);
            $data = M('project_config')->where("project_id='{$projectId}' AND `key`='104'")->find();
            $card = json_decode($data['val'], true);
            if(empty($data)){
                $this->error('该店铺未开启推广员佣金');
            }else if($card['recruit_open'] == '0'){
                //未开启招募
                $this->error('该店铺未开启推广员招募');
            }else if(!empty($_POST['share_mid'])){
                //次级推广员
                //判断邀请人是否为推广员
                $Model = new \Common\Model\Agent();
                $isAgent = $Model->is_agent($_POST['share_mid'],$_POST['apply']);
                if($isAgent == true){
                    //建立上下级关系
                    if($member['id'] == $_POST['share_mid']){
                        $this->error('您不能成为自己的二级推广员');
                    }
                    $pm = M('project_member')->where(array('mid'=>$member['id'],'project_id'=>$projectId,'pid'=>$_POST['share_mid']))->find();
                    $project = M('project')->field('alias')->find($projectId);
                    if(empty($pm)){
                        $time = time();
                        $host = $project['alias'].'/login';
                        $data = array(
                            'mid'        => $member['id'],
                            'project_id' => $projectId,
                            'created'    => $time,
                            'pid'        => $_POST['share_mid'],
                            'last_host'  => $host,
                            'source'     => $host,
                            );

                        M('project_member')->add($data,null,true);
                    }else{
                        $data = array(
                            'last_host'  => $project['alias'].'/login',
                            );
                        M('project_member')->where(array('mid'=>$member['id'],'project_id'=>$projectId,'pid'=>$_POST['share_mid']))->save($data);
                    }
                    if($card['check_open'] == 0){
                        //未开启审核
                        $agent = M('agent')->where(array('mid'=>$member['id'],'shop_id'=>$_POST['apply'],'status'=>'1'))->find();
                        if(empty($agent)){
                            $data = array(
                                'mid'          => $member['id'],
                                'shop_id'      => $_POST['apply'],
                                'status'       => 1,
                                'total_amount' => 0,
                                'num'          => 0,
                                'reward1'      => 0,
                                'reward2'      => 0,
                                'pnum'         => 0,
                                'created'      => time(),
                                );
                            M('agent')->add($data);
                            //上级推广员推荐人数加一
                            $agent_up = M('agent')->field('pnum')->where(array('mid'=>$_POST['share_mid'],'shop_id'=>$_POST['apply'],'status'=>'1'))->find();
                            $pnum = $agent_up['pnum']+1;
                            M('agent')->where(array('mid'=>$_POST['share_mid'],'shop_id'=>$_POST['apply'],'status'=>'1'))->save(array('pnum'=>$pnum));
                        }else{
                            $this->error('您已经成为该店铺推广员，请不要重复申请！');
                        }
                    }else{
                        $agent_adopt = M('agent')->field('status')->where(array('mid'=>$member['id'],'shop_id'=>$_POST['apply'],'status'=>'1'))->find();
                        if(empty($agent_adopt)){
                            $agent = M('agent')->field('status')->where(array('mid'=>$member['id'],'shop_id'=>$_POST['apply'],'status'=>'0'))->find();
                            if(empty($agent)){
                                $data = array(
                                    'mid'          => $member['id'],
                                    'shop_id'      => $_POST['apply'],
                                    'status'       => 0,
                                    'total_amount' => 0,
                                    'num'          => 0,
                                    'reward1'      => 0,
                                    'reward2'      => 0,
                                    'pnum'         => 0,
                                    'created'      => time(),
                                    );
                                M('agent')->add($data);
                            }else{
                                $this->error('申请正在审核...');
                            }
                        }else{
                            $this->error('您已经成为该店铺推广员，请不要重复申请！');
                        }    
                    }
                }else{
                    $this->error('邀请人不是推广员，无法邀请好友');
                }
            }else{
                //一级推广员
                if($card['check_open'] == 0){
                    //未开启审核
                    $agent = M('agent')->where(array('mid'=>$member['id'],'shop_id'=>$_POST['apply'],'status'=>'1'))->find();
                    if(empty($agent)){
                        $data = array(
                            'mid'          => $member['id'],
                            'shop_id'      => $_POST['apply'],
                            'status'       => 1,
                            'total_amount' => 0,
                            'num'          => 0,
                            'reward1'      => 0,
                            'reward2'      => 0,
                            'pnum'         => 0,
                            'created'      => time(),
                            );
                        M('agent')->add($data);
                    }else{
                        $this->error('您已经成为该店铺推广员，请不要重复申请！');
                    }
                }else{
                    $agent_adopt = M('agent')->field('status')->where(array('mid'=>$member['id'],'shop_id'=>$_POST['apply'],'status'=>'1'))->find();
                    if(empty($agent_adopt)){
                        $agent = M('agent')->field('status')->where(array('mid'=>$member['id'],'shop_id'=>$_POST['apply'],'status'=>'0'))->find();
                        if(empty($agent)){
                            $data = array(
                                'mid'          => $member['id'],
                                'shop_id'      => $_POST['apply'],
                                'status'       => 0,
                                'total_amount' => 0,
                                'num'          => 0,
                                'reward1'      => 0,
                                'reward2'      => 0,
                                'pnum'         => 0,
                                'created'      => time(),
                                );
                            M('agent')->add($data);
                        }else{
                            $this->error('申请正在审核...');
                        }
                    }else{
                        $this->error('您已经成为该店铺推广员，请不要重复申请！');
                    }    
                } 
            }
            
        }
        $this->setSession($member);
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

    protected function setSession($member){
        $user = array(
            'id'         => $member['id'],
            'openid'     => '',
        	'appid'      => '',
            'login_type' => 2
        );
        
        $wxuser = M();
        $wx = $wxuser->query("SELECT openid, appid FROM wx_user WHERE mid={$member['id']} AND appid='{$this->project['appid']}'");
        $wx = $wx[0];
        if($wx){
        	$user['openid'] = $wx['openid'];
        	$user['appid']  = $wx['appid'];
            $user[$wx['appid']] = array('openid' => $wx['openid'], 'mid' => $member['id']);
        }
        session('user', $user);
        session($this->codeKey, null);
        cookie($this->cookieKey, $member['mobile'], array('expire' => 3600 * 24 * 3));
    }
}
?>