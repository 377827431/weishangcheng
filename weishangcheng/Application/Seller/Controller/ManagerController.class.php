<?php
namespace Seller\Controller;
use Common\Common\CommonController;
use Think\Controller;

/**
 * 卖家端基础父类.
 *
 * extends 买家端基础类
 *
 * @author jy
 */
class ManagerController extends CommonController
{
    protected $shopId;
    function __noGetShop(){
        parent::__construct();
    }
    function __construct(){
        parent::__construct();
        $this->shopId = $this->getShopId();
    }

    public function getShopId($uid = null){
        $userIdInPlatform = I('get.userIdInSourcePlatform','');
        if(!empty($userIdInPlatform)){
            session('manager', array());
        }
        $shopId = session('manager.shop_id');
        if (!isset($shopId) || $shopId == ''){
            if (empty($uid)){
                $uid = $this->user('id');
            }
            $shop = M('admin_user')->field('shop_id')->where("id = {$uid}")->find();
            if (($shop['shop_id']) > 0){
                $shopId = $shop['shop_id'];
                session('manager.shop_id', $shopId);
            }else{
//                redirect('/seller/create');
            }
        }
        return $shopId;
    }

    /**
     * 用户信息
     *
     * @param string $key
     *            字段名称（string表示多个用逗号间隔，array表示更新用户信息）
     * @return Ambigous <boolean, unknown>|\Think\mixed
     */

    protected function user($key = ''){
        $user = $this->getLogin();

        // 判断是否为封号状态
        if(MODULE_NAME == 'Seller' && isset($user['black_list']) && $user['black_list'] > NOW_TIME){
            $this->error('您已被封号：'.date('Y-m-d H:i:s', $user['black_start']).' ~ '.date('Y-m-d H:i:s', $user['black_end']), 'javascript:;');
        }

        if($key === ''){
            return $user;
        }else if(isset($user[$key])) {
            return $user[$key];
        }else{
            E('方法已过时');
        }

        if(empty($user)){
            session('manager', null);
            Auth::checkLogin();
        }else if(count($user) == 1){
            return current($user);
        }
        return $user;
    }

    protected function getLoginRedirect(){
        $redirect = '';
        if(IS_AJAX || IS_POST){
            $redirect = $_SERVER['HTTP_REFERER'];
        }else if(MODULE_NAME == 'Seller'){
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
            $redirect = C('PROTOCOL').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        
        return urlencode($redirect);
    }

    /**
     * 获取登录人session.manager信息
     */
    private function getLogin(){
        $user = session('manager');
        $data = array('type' => '', 'status' => -1, 'url' => '', 'redirect' => '', 'mobile' => cookie('auth_mobile'), 'appid' => '');
        if(MODULE_NAME == 'Seller'){
            if(is_numeric($user['id'])){
                return $user;
            }else{ 
                $is1688 = I('get.is1688','');
                if(!empty($is1688)){
                    $ali = new \Org\Alibaba\AlibabaAuth();
                    $userIdInPlatform = I('get.userIdInSourcePlatform','');
                    if(is_numeric($userIdInPlatform)){
                        //签名验证
                        $sign = I('get.sign','');
                        $request_sign = $ali->getSign(urldecode($data['redirect']));
                        // if(empty($sign) || $sign!=$request_sign){
                        //     $this->error('签名验证不一致');
                        // }
                        file_put_contents('auth.txt','传入签名信息：'.$sign."\r\n",FILE_APPEND);
                        file_put_contents('auth.txt','校验签名信息：'.$request_sign."\r\n",FILE_APPEND);
                        //通过数据库中是否存储用户标识，判断用户是否开店；如存储用户标识，查询到对应店铺直接登陆；如未存储用户标识，跳到登录页面，填写注册信息，存储用户标识；
                        $data['redirect'] = $this->getLoginRedirect();
                        $alibaba = M('alibaba_token')->where(array('userId'=>$userIdInPlatform))->find();
                        if(!empty($alibaba)){
                            //已授权
                            $shop = M('shop')->where(array('aliid'=>$userIdInPlatform))->order('id DESC')->find();
                            if(!empty($shop)){
                                //已开店,直接登录
                                $user_info = M('admin_user')->where(array('shop_id'=>$shop['id']))->find();
                                session('manager', array(
                                    'id'         => $user_info['id'],
                                    'username'   => $user_info['username'],
                                    'shop_id'    => $user_info['shop_id'],
                                    'project_id' => substr($user_info['shop_id'],0,-3)
                                ));
                                session('auth_mobile', null);
                                cookie('login_valid', $user_info['username'], array('expire' => 3600 * 24 * 3));
                                $user = session('manager');
                                $data['url'] = urldecode($this->redirectHandle($data['redirect']));
                                redirect($data['url'], 0);
                            }
                        }
                    }
                    $code = I('get.code','');
                    if(!empty($code)){
                        $state = I('get.state');
                        $dataAli = $ali->setAuth($code,$state);
                        $data['redirect'] = $this->redirectHandle($this->getLoginRedirect());
                        $shop = M('shop')->where(array('aliid'=>$dataAli['userId']))->order('id DESC')->find();
                        if(empty($shop)){
                            //未绑定1688和店铺信息
                            $data['url'] = __MODULE__.'/login?redirect='.$data['redirect'].'&aliid='.$dataAli['id'].'&userid='.$dataAli['userId'];
                        }else{
                            //已绑定
                            $user_info = M('admin_user')->where(array('shop_id'=>$shop['id']))->find();
                            session('manager', array(
                                'id'         => $user_info['id'],
                                'username'   => $user_info['username'],
                                'shop_id'    => $user_info['shop_id'],
                                'project_id' => substr($user_info['shop_id'],0,-3)
                            ));
                            session('auth_mobile', null);
                            cookie('login_valid', $user_info['username'], array('expire' => 3600 * 24 * 3));
                            $user = session('manager');
                            $data['url'] = urldecode($data['redirect']);
                        }
                    }else{
                        $data['redirect'] = $this->getLoginRedirect();
                        $data['url'] = $ali->redirectAuth(urldecode($data['redirect']));
                    }
                }else{
                    if( __SELF__ == __MODULE__."/commodity/goods1688?".$_SERVER['QUERY_STRING']){
                        $redirect = (is_ssl() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];
                        $pathInfo = trim($_SERVER['PATH_INFO'],'/');
                        if($pathInfo){$redirect .= '/'.$pathInfo;}
                        
                        // 拼接GET参数
                        if(count($_GET) >= 0){
                            $_GET['is1688'] = 1;
                            $params = http_build_query($_GET);
                            if($params){
                                $redirect .= '?'.$params;
                            }
                        }
                        redirect($redirect);
                    }
                }
                
            }
        }
        // 计算回跳地址
        if(!$data['url']){
            $data['redirect'] = $this->getLoginRedirect();
            $data['url'] = __MODULE__.'/login?redirect='.$data['redirect'];
        }

        if(IS_AJAX){
            $this->ajaxReturn($data);
        }else{
            redirect($data['url'], 0);
        }
    }
    /*
     * 去掉1688授权后code,state参数
     */
    private function redirectHandle($redirect){
        $redirect = parse_url(urldecode($redirect));
        $param = explode('&', $redirect['query']);
        foreach ($param as $key => $value) {
            if(stripos($value,'code=') !== false){
                unset($param[$key]);
            }
            if(stripos($value,'state=') !== false){
                unset($param[$key]);
            }
            if(stripos($value, 'is1688=') !== false){
                unset($param[$key]);
            }
            if(stripos($value, 'userIdInSourcePlatform=') !== false){
                unset($param[$key]);
            }
        }
        $redirect['query'] = implode('&',$param);
        $url = $redirect['scheme'].'://'.$redirect['host'].$redirect['path'];
        if($redirect['query'] != ''){
            $url .= '?'.$redirect['query'];
        }
        return urlencode($url);
    }

}
?>