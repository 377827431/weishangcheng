<?php
namespace Common\Common;

use Think\Controller;

/**
 * 基础父类.
 *
 * CollegeController description.
 *
 * @version 1.0
 * @author Administrator
 */
class CommonController extends Controller
{
    private $_common_shops;
    private $_common_card;
    protected $projectId;
    
    function __construct(){
        parent::__construct();
        
        $this->checkAuth();

        if(MODULE_NAME == 'Admin'){
            $this->projectId = $this->user('project_id');
        }else if(MODULE_NAME == 'H5'){
            $this->projectId = PROJECT_ID;
            $shop = M('shop')->where(array('id'=>$this->projectId.'001'))->find();
            if (!empty($shop)) {
                define('SHOP', $shop);
            }
            if (session('redirect') == 1){
                $id = $this->user('id');
                $member = M('member')->where(array("id" => $id))->find();
                if (!empty($member['last_host'])){
                    $url = I('get.url', '');
                    if (!empty($url)){
                        $url = '/'.$url;
                    }
                    redirect('/'.$member['last_host'].$url);
                }
            }
            $this->shop_trace();
        }
    }

    /**
     * 浏览店铺 增加记录
     */
    private function shop_trace(){
        if (APP_NAME == P_USER){
            return;
        }
        $user = session('user');
        $alias = APP_NAME;
        if (empty($user['trace']) || $user['trace'] != $alias){
            session('user.trace', $alias);
            $project = M('project')
                ->field('id')
                ->where(array("alias" => $alias))
                ->find();
            $shopId = $project['id'].'001';
            $re = M('shop_trace')
                ->field('')
                ->where(array("shop_id" => $shopId, "mid" => $this->user('id')))
                ->find();
            if (!empty($re)){
                $data = array(
                    "modify" => date("Y-m-d H:i:s", time()),
                );
                $where = array(
                    "mid" => $this->user('id'),
                    "shop_id" => $shopId
                );
                M('shop_trace')->where($where)->save($data);
            }else{
                $add = array(
                    "mid" => $this->user('id'),
                    "shop_id" => $shopId,
                    "modify" => date("Y-m-d H:i:s", time()),
                    "is_del" => 0
                );
                M('shop_trace')->add($add);
            }
        }
    }
    
    /**
     * 模板显示 调用内置的模板引擎显示方法，
     * @access protected
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     * @param string $content 输出内容
     * @param string $prefix 模板缓存前缀
     * @return void
     */
    protected function display($templateFile='',$charset='',$contentType='',$content='',$prefix='') {
        if(IS_AJAX || IS_POST){
            C('LAYOUT_ON', false);
        }
        $this->view->display($templateFile,$charset,$contentType,$content,$prefix);
        exit();
    }
    
    protected function flush($message, $over = false){
        static $first = true;

        if($first){
            $first = false;
            ignore_user_abort(true);
            ob_end_clean();
            ob_implicit_flush(1);
            include THINK_PATH.'Tpl\flush.tpl';
            echo '<p class="msg-item" style="text-align:center">您可关闭此窗口，不必等待！</p>';
        }else if(!$over){
            echo '<p class="msg-item">'.date('i分s秒：').$message.'</p>';
        }else{
            echo '<p class="msg-over">'.$message.'</p>';
            $time = second_to_time(time() - NOW_TIME, true);
            echo '<p class="msg-item" style="text-align:center">共用'.$time.'</p>';
        }
        
        ob_end_flush();
        flush();
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
        if((MODULE_NAME == 'H5' || MODULE_NAME == 'Seller') && isset($user['black_list']) && $user['black_list'] > NOW_TIME){
            $this->error('您已被封号：'.date('Y-m-d H:i:s', $user['black_start']).' ~ '.date('Y-m-d H:i:s', $user['black_end']), 'javascript:;');
        }
        
        if($key === ''){
            return $user;
        }else if(isset($user[$key])) {
            return $user[$key];
        }else{
            E('方法已过时');
            if (isset($user[str_replace('wx.','',$key)])) {
                return $user[str_replace('wx.','',$key)];
            }
            
            if (isset($user[str_replace('m.','',$key)])) {
                return $user[str_replace('m.','',$key)];
            }

            $sql = "SELECT {$key}
                    FROM project_member AS pm
                    left join wx_user AS wx on wx.mid = pm.mid
                    left join member on member.id = wx.mid
                    left join project_card as pc on pc.id = pm.card_id
                    WHERE pm.mid={$user['id']} AND wx.openid='".$user[C('DEFAULT_WEIXIN_CONFIG.appid')]."'";
            $user = M()->query($sql);
            if(count($user) > 0){
                $user = $user[0];
            }
        }

        if(empty($user)){
            session('user', null);
            Auth::checkLogin();
        }else if(count($user) == 1){
            return current($user);
        }
        return $user;
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
    
    /**
     * 检查登陆
     */
    protected function checkAuth(){
        if(MODULE_NAME == 'Admin'){
            $this->user('id');
            $url = '';
            if(!is_null($this->authRelation)){
                $current = strtolower(ACTION_NAME);
                foreach($this->authRelation as $action=>$target){
                    if($action == $current){
                        $url = $target;
                        break;
                    }
                }
            }
            
            $result = Auth::get()->check($url);
            if(!$result){
                $this->error('您没有权限');
            }
            return;
        }else if(IS_WEIXIN && isset($_GET['code']) && is_numeric($_GET['state']) && NOW_TIME - $_GET['state'] < 120 && (MODULE_NAME == 'H5' || MODULE_NAME == 'Seller') ){
            $this->getLogin();
        }
    }
    
    /**
     * 获取登录人session.user信息
     */
    private function getLogin(){
        $user = session('user');
        $data = array('type' => '', 'status' => -1, 'url' => '', 'redirect' => '', 'mobile' => cookie('auth_mobile'), 'appid' => '');
        if(MODULE_NAME == 'H5' || MODULE_NAME == 'Seller'){
            if(IS_WEIXIN){
                $project = defined('PROJECT') ? PROJECT : get_project($_SERVER['HTTP_HOST']);
                $appid   = isset($user[$project['third_mpid']]) ? $project['appid'] : $project['third_mpid'];
                if(isset($user[$appid])){
                    if($user['openid'] != $user[$appid]['openid']){
                        $user['id']     = $user[$appid]['mid'];
                        $user['openid'] = $user[$appid]['openid'];
                        $user['appid']  = $appid;
                        session('user', $user);
                    }
                    return $user;
                }
                
                $ok = false;
                if($_GET['appid'] == $appid && !empty($_GET['code']) && NOW_TIME - $_GET['state'] < 120){
                    $config = get_wx_config($project['third_appid']);
                    $wechatAuth = new \Org\Wechat\WechatAuth($config, $_GET['appid']);
                    $response = $wechatAuth->getAccessToken('code', $_GET['code']);
                    if(!$response || !isset($response['openid'])){
                        $this->error('授权登陆失败');
                    }
                    
                    $userInfo = $wechatAuth->getUserInfo($response);
                    // var_dump($response, $userInfo);die;
                    if (empty($userInfo) || isset($userInfo['errcode'])) {
                        // $this->error('获取授权用户信息失败<hr>微信错误码：' . $userInfo['errcode'] . '<br>错误信息：' . $userInfo['errmsg']);
                        $userInfo = array(
                            'openid' => $response['openid'],
                            'nickname' => '',
                            'sex' => '',
                            'language' => 'zh_CN',
                            'city' => '',
                            'province' => '',
                            'country' => '',
                            'headimgurl' => '',
                            'privilege' => array(),
                        );
                    }
                    
                    // 优先保存到wx_user
                    $Member             = new \Common\Model\MemberModel();
                    $userInfo['appid']  = $appid;
                    $userInfo['source'] = is_numeric($_GET['share_mid']) ? 'share_'.$_GET['share_mid'] : strtolower($_SERVER['PATH_INFO']);
                    $Member->addWxUser($userInfo);
                    
                    // 拿到用户openid
                    $openid       = $response['openid'];
                    $user[$appid] = array('openid' => $openid, 'mid' => 0);
                    
                    // 当前公众号(非默认保存数据)
                    if($appid == $project['appid']){
                        $appList = $Member->bindInfo(array(
                            'third_appid'    => $project['third_appid'],
                            'default'        => array('appid' => $project['third_mpid'], 'openid' => $user[$project['third_mpid']]['openid']),
                            'current'        => array('appid' => $project['appid'],      'openid' => $user[$project['appid']]['openid']),
                            'mid'            => $user['id'],
                            'project_id'     => $project['id'],
                            'share_mid'      => is_numeric($_GET['share_mid']) ? $_GET['share_mid'] : 0,
                            'host'           => $project['alias'],
                            'source'         => $userInfo['source']
                        ));
                        
                        foreach($appList as $appid=>$item){
                            $user[$appid] = $item;
                        }
                        $user['id'] = $user[$appid]['mid'];
                        $user['openid'] = $user[$appid]['openid'];
                        $user['appid']  = $appid;
                        $user['login_type'] = 1;
                        $ok= true;
                    }
                    session('user', $user);
                }
                
                unset($_GET['code']);
                unset($_GET['state']);
                unset($_GET['appid']);
                if($ok){
                    return $user;
                }
                $redirect = $this->getLoginRedirect();
                $scope        = $appid == $project['appid'] ? 'snsapi_userinfo' : 'snsapi_base';
                $data['url']  = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.$redirect;
                $data['url'] .= '&response_type=code&scope='.$scope.'&state='.time().'&component_appid='.$project['third_appid'].'#wechat_redirect';
                $data['appid'] = $project['appid'];
            }else if(is_numeric($user['id'])){
                return $user;
            }
        }else if(is_numeric($user['id'])){
            return $user;
        }
        die('redirect');
        // 计算回跳地址
        if(!$data['url']){
            $data['redirect'] = $this->getLoginRedirect();
            $data['url'] = __MODULE__.'/login?redirect='.$data['redirect'];
        }
        
        if(IS_AJAX){
            $this->ajaxReturn($data);
        }else if(IS_WEIXIN){
            exit('<script>window.location.replace("'.$data['url'].'")</script>');
        }else{
            redirect($data['url'], 0);
        }
    }
    
    /*代理级别*/
    public function agentLevel($level = ''){
        $Model = D("project_card");
        $project_id = session('user.project_id');
        $data = $Model->field("id, `title`,expire_time,discount,price,auto_trade,auto_payment,auto_score,settlement_type,agent_rate,agent_same,agent_rate2")->where("id like '".$project_id."%'")->order("id")->select();
        $list = array();
        foreach($data as $k=>$v){
            $list[$v['id']] = $v;
        }
        if($level !== ''){
            return $list[$level];
        }
        return $list;
    }
    
    /**
     * 获取所有店铺
     */
    public function shops(){
        if(is_null($this->_common_shops)){
            $allShop = $this->authAllShop();
            $myProjectId = $this->user('project_id');
            $where = "";
            if($allShop){
                $where = "id BETWEEN {$myProjectId}000 AND {$myProjectId}999";
            }else{
                $where = "id=".$this->user('shop_id');
            }
            $this->_common_shops = M()->query("SELECT id, `name`, state FROM shop WHERE {$where} ORDER BY id, state");
        }
        return $this->_common_shops;
    }
    /**
     * 获取所有会员卡
     */
    public function card(){
        if(is_null($this->_common_card)){
            $myProjectId = $this->user('project_id');
            $this->_common_card = M()->query("SELECT id,title FROM project_card WHERE id like '{$myProjectId}%' ORDER BY id");
        }
        return $this->_common_card;
    }
    
    /**
     * 当前登录账号是否有全部店铺的权限
     */
    protected function authAllShop(){
        static $allshop = null;
        if(is_null($allshop)){
            $allshop = \Common\Common\Auth::get()->validated('admin','shop','all');
        }
        return $allshop;
    }

    public function getShopId($mid = null){
        $shopId = session('manager.shop');
        if (!isset($shopId) || $shopId == ''){
            if (empty($mid)){
                $mid = $this->user('id');
            }
            $shop = M('shop')->field('max(id) as id')->where("mid = {$mid}")->find();
            if (($shop['id']) > 0){
                $shopId = $shop['id'];
                session('manager.shop', $shopId);
            }else{
                redirect('/seller/create');
            }
        }
        return $shopId;
    }
    /*
     * 检测是否是小B流程
     */
    public function isLittleB($projectId){
        $appid = M()->query("SELECT appid,mch_appid FROM project_appid WHERE id={$projectId}");
        if($appid[0]['mch_appid'] == C('DEFAULT_WEIXIN') && $appid[0]['appid'] == C('DEFAULT_APPID') && $projectId != '100006'){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 随机分配数据库连接,注册时使用
     */
    public function DbConnect($control='buyerReg',$alias=''){
        $connect = array('DB1','DB2','DB3');
        //商户注册时,随机一个数据库链接
        $key = rand(0,count($connect)-1);
        return $connect[$key];
    }
    /**
     * 多数据库查询
     * $tatble:表名
     * $where:查询条件
     */
    public function getAllDb($table,$where,$alias='',$field='',$join=array(),$order='',$limit=''){
        $array1 = M($table,'','DB1')->alias($alias)->field($field)->where($where)->join($join)->order($order)->limit($limit)->select();
        foreach ($array1 as $k1 => $v1) {
            $array1[$k1]['DB'] = 'DB1';
        }
        $array2 = M($table,'','DB2')->alias($alias)->field($field)->where($where)->join($join)->order($order)->limit($limit)->select();
        foreach ($array2 as $k2 => $v2) {
            $array2[$k2]['DB'] = 'DB2';
        }
        $array3 = M($table,'','DB3')->alias($alias)->field($field)->where($where)->join($join)->order($order)->limit($limit)->select();
        foreach ($array3 as $k3 => $v3) {
            $array3[$k3]['DB'] = 'DB3';
        }
        $arr = array_merge($array1,$array2,$array3);
        return $arr;
    }
    /*
     * 多数据库，获取最大ID
     */
    public function getMaxId($table){
        $arr = $this->getAllDb($table,'');
        $max_id = 0;
        foreach ($arr as $key => $value) {
            if($max_id<$value['id']){
                $max_id = $value['id'];
            }
        }
        return $max_id;
    }
    /*
     * 多数据库，检测数据重复
     */
    public function checkRepeat($table,$where){
        $arr = $this->getAllDb($table,$where);
        //验证是否存在
        if(empty($arr)){
            //不存在
            return 'not_exist';
        }else{
            //存在
            return 'exist';
        }
    }
}
?>