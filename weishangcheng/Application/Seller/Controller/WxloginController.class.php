<?php
namespace Seller\Controller;
use Think\Controller;


class WxloginController extends Controller{
    public function index(){
        //微信授权获取openid
        if(IS_WEIXIN){
            if(is_numeric($_GET['shop_id']) && !empty($_GET['shop_id'])){
                $admin = M('admin_user')->where("shop_id=%d",$_GET['shop_id'])->find();
                if(!empty($admin)){
                    if(empty($admin['openid'])){
                        $defaultAppid = C('DEFAULT_WEIXIN');
                        $thirdAppid   = C('THIRD_APPID');
                        if($_GET['appid'] == $defaultAppid && !empty($_GET['code']) && NOW_TIME - $_GET['state'] < 120){
                            $config = get_wx_config($thirdAppid);
                            $wechatAuth = new \Org\Wechat\WechatAuth($config, $_GET['appid']);
                            $response = $wechatAuth->getAccessToken('code', $_GET['code']);
                            if(!$response || !isset($response['openid'])){
                                $this->error('授权登陆失败');
                            }
                            
                            // 拿到用户openid
                            $openid = $response['openid'];
                            //存储openid
                            M("admin_user")->where("shop_id = %d",$_GET['shop_id'])->save(array('openid'=>$openid));
                            //缓存SESSION
                            session('manager', array(
                                'id'         => $admin['id'],
                                'username'   => $admin['username'],
                                'shop_id'    => $admin['shop_id'],
                                'project_id' => substr($admin['shop_id'],0,-3),
                                'openid'     => $openid
                            ));
                            //绑定成功后检测账户内余额
                            $shop = M('shop')->find($_GET['shop_id']);
                            if(!empty($shop) && $shop['balance']!=0){
                                //如果可提现余额不为空，且是小B，自动提现
                                $projectId = substr($shop['id'], 0,-3);
                                $Model = D('AuthTransfers');
                                //计算转账金额(扣除手续费)
                                $re_tixian = $shop['balance']-ceil(($shop['balance']*6/1000)*100)/100;
                                //判断是否符合微信转账的条件
                                $re = $Model->transfers_rule($re_tixian,$record['shop_id'],$openid);
                                if($re == false){
                                    //不符合条件,跳出提现流程；
                                    $this->error('微信绑定成功，提现失败');
                                }
                                $record = array(
                                    'balance'  => $shop['balance'],
                                    'shop_id'  => $shop['id'],
                                    'reason'   => '绑定微信成功,自动提现',
                                    'username' => 'system'
                                );
                                //提现
                                $user = array(
                                    'appid'      => $_GET['appid'],
                                    'project_id' => $projectId,
                                    'openid'     => $openid,
                                    'desc'       => $record['reason'].';自动转账',
                                    'balance'    => $shop['balance'],
                                    'no_balance' => 0,
                                );
                                $result = $Model->transfers($user,$re_tixian,$record,'shop');
                                if($result == true){
                                    $this->success("微信绑定成功，并已提现","/seller/index");
                                }else{
                                   $this->success("微信绑定成功，提现失败","/seller/index");
                                }
                            }else{
                                $this->success('微信绑定成功',"/seller/index");
                            }
                        }else{
                            $redirect = $this->getLoginRedirect();
                            $url  = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$defaultAppid.'&redirect_uri='.$redirect;
                            $url .= '&response_type=code&scope=snsapi_base&state='.time().'&component_appid='.$thirdAppid.'#wechat_redirect';
                            redirect($url, 0);
                        }
                    }else{
                        //缓存SESSION
                        session('manager', array(
                            'id'         => $admin['id'],
                            'username'   => $admin['username'],
                            'shop_id'    => $admin['shop_id'],
                            'project_id' => substr($admin['shop_id'],0,-3),
                            'openid'     => $openid
                        ));
                        $this->error("微信已绑定开店","/seller/index");
                    }
                }else{
                    $this->error("未找到开店店铺");
                }
            }else{
                $this->error("参数错误");
            }
        }else{
            $this->error("请前往微信端访问此页面");
        }
    }
    protected function getLoginRedirect(){
        $redirect = '';
        if(IS_AJAX || IS_POST){
            $redirect = (is_ssl() ? 'https' : 'http').'://'.$_SERVER['HTTP_REFERER'];
        }else if(MODULE_NAME == 'H5' || MODULE_NAME == 'Seller'){
            $redirect = (is_ssl() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];
            $pathInfo = trim($_SERVER['PATH_INFO'],'/');
            if($pathInfo){$redirect .= '/'.$pathInfo;}
            
            // 拼接GET参数
            if(count($_GET) > 0){
                $params = http_build_query($_GET);
                if($params){
                    $redirect .= '?'.$params;
                }
            }
        }else{
            $redirect = (is_ssl() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        
        return urlencode($redirect);
    }
}