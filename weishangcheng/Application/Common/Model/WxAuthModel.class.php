<?php
namespace Common\Model;

use Org\Wechat\WechatAuth;
use Think\Cache\Driver\Redis;

/**
 * 微信授权类
 * @author Administrator
 *
 */
class WxAuthModel extends BaseModel{
    protected $tableName = 'wx_appid';
    protected $pk = 'appid';
    private $config;
    
    public function __construct($config){
        parent::__construct();
        
        if(!is_array($config)){
            $config = get_wx_config($config);
        }
        $this->config = $config;
    }
    
    public function wechatAuth(){
        static $wechatAuth = null;
        if(is_null($wechatAuth)){
            $wechatAuth = new WechatAuth($this->config);
        }
        return $wechatAuth;
    }
    
    /**
     * 获取授权跳转地址
     */
    public function getAuthUrl($redirect = ''){
        $preAuthCode = $this->wechatAuth()->getPreAuthCode();
        if(!$redirect){
            $redirect = C('PROTOCOL').$_SERVER['HTTP_HOST'].(__MODULE__ ? '/'.__MODULE__ : '').'/weixin/auth';
        }
        $url  = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid='.$this->config['appid'];
        $url .= '&pre_auth_code='.$preAuthCode.'&redirect_uri='.urlencode($redirect);
        return $url;
    }
    
    /**
     * 授权
     */
    public function authorized($auth_code, $project){
        $wechatAuth = $this->wechatAuth();
        
        $response = $wechatAuth->apiQueryAuth($auth_code);
        $appid    = $response['authorizer_appid'];
        
        // 判断公众号是否已被其他项目使用
        $exists = $this->find($appid);
        if($exists && $exists['authorized'] == 1 && $exists['project_id'] && $exists['project_id'] != $project['id']){
            $this->error = '此公众号已被授权给其他店铺使用，若您是此公众号管理员请登陆该微信公众号后台进行取消授权操作';
            return;
        }
        
        $info     = $wechatAuth->getAuthorizerInfo($appid);
        $base     = $info['authorizer_info'];
        $auth     = $info['authorization_info'];
        
        $access = '';
        foreach ($auth['func_info'] as $item){
            $access .= $item['funcscope_category']['id'].',';
        }
        $access = rtrim($access, ',');
        
        $data = array(
            'appid'            => $appid,
            'project_id'       => $project['id'],
            'authorized'       => 1,
            'auth_bound'       => date('Y-m-d H:i:s'),
            'third_appid'      => C('THIRD_APPID'),
            'refresh_token'    => $response['authorizer_refresh_token'],
            'auth_ids'         => $access,
            'name'             => $base['nick_name'],
            'head_img'         => $base['head_img'],
            'service_type'     => $base['service_type_info']['id'],
            'verify_type'      => $base['verify_type_info']['id'],
            'user_name'        => $base['user_name'],
            'alias'            => $base['alias'],
            'qrcode'           => $base['qrcode_url'],
            'signature'        => $base['signature'],
            'principal_name'   => $base['principal_name'],
            'open_pay'         => $base['business_info']['open_pay'],
            'open_shake'       => $base['business_info']['open_shake'],
            'open_scan'        => $base['business_info']['open_scan'],
            'open_card'        => $base['business_info']['open_card'],
            'open_store'       => $base['business_info']['open_store']
        );

        if(!$exists){
            $this->add($data);
        }else{
            $this->where("appid='{$appid}'")->save($data);
        }
        
        // 获取获取可以发起微信支付的公众号主体appid
        $sql = "SELECT appid, mch_appid FROM project_appid WHERE id={$project['id']} LIMIT 1
                UNION
                SELECT appid, mch_appid FROM project_appid WHERE id={$project['id']} AND appid='{$appid}'";
        $appList = $this->query($sql);
       
        foreach ($appList as $i=>$item) {
            $appList[$item['appid']] = $item['mch_appid'];
            unset($appList[$i]);
        }

        // 已经存在过则忽略
        if(isset($appList[$appid])){
            return true;
        }

        // 可发起微信支付的appid
        $mchAppid = count($appList) > 0 ? current($appList) : C('DEFAULT_WEIXIN');
        
        // 插入project_appid表
        $shopModel = new ShopModel();
        $aliasList = $shopModel->getAlias($data['name'], array($data['alias'], $data['appid'], $data['user_name']));
        $alias = '';
        if(array_search($data['alias'], $aliasList) > -1){   // 公众号的微信号
            $alias = $data['alias'];
        }else if(array_search($data['appid'], $aliasList) > -1){    // 公众号appid
            $alias = $data['appid'];
        }else if(array_search($data['user_name'], $aliasList) > -1){    // 公众号原始id
            $alias = $data['user_name'];
        }else{
            $alias = current($aliasList);
        }
        
        $sql = "INSERT INTO project_appid SET
                    id={$project['id']},
                    alias='{$alias}',
                    appid='{$appid}',
                    mch_appid='{$mchAppid}'
                ON DUPLICATE KEY UPDATE
                    id=VALUES(id),
                    alias=VALUES(alias),
                    appid=VALUES(appid),
                    mch_appid=VALUES(mch_appid)";
        $this->execute($sql);
        return true;
    }
    
    /**
     * 更新授权
     */
    public function updateauthorized($auth_code){
        $wechatAuth = $this->wechatAuth();
        
        $response = $wechatAuth->apiQueryAuth($auth_code);
        $appid    = $response['authorizer_appid'];
        
        $info     = $wechatAuth->getAuthorizerInfo($appid);
        $base     = $info['authorizer_info'];
        $auth     = $info['authorization_info'];
        
        $access = '';
        foreach ($auth['func_info'] as $item){
            $access .= $item['funcscope_category']['id'].',';
        }
        $access = rtrim($access, ',');
        
        $data = array(
            'appid'            => $appid,
            'authorized'       => 1,
            'auth_bound'       => date('Y-m-d H:i:s'),
            'third_appid'      => $this->config['appid'],
            'refresh_token'    => $response['authorizer_refresh_token'],
            'auth_ids'         => $access,
            'name'             => $base['nick_name'],
            'head_img'         => $base['head_img'],
            'service_type'     => $base['service_type_info']['id'],
            'verify_type'      => $base['verify_type_info']['id'],
            'user_name'        => $base['user_name'],
            'alias'            => $base['alias'],
            'qrcode'           => $base['qrcode_url'],
            'signature'        => $base['signature'],
            'principal_name'   => $base['principal_name'],
            'open_pay'         => $base['business_info']['open_pay'],
            'open_shake'       => $base['business_info']['open_shake'],
            'open_scan'        => $base['business_info']['open_scan'],
            'open_card'        => $base['business_info']['open_card'],
            'open_store'       => $base['business_info']['open_store']
        );
        $this->where("appid='{$appid}'")->save($data);
    }
    
    /**
     * 取消授权
     */
    public function unauthorized($appid){
        $Model = M();
        $sql = "SELECT wx_appid.appid, project_appid.id AS project_id, project_appid.alias
                FROM wx_appid
                LEFT JOIN project_appid ON project_appid.id=wx_appid.project_id AND project_appid.appid=wx_appid.appid
                WHERE wx_appid.appid='{$appid}'";
        $app = $Model->query($sql);
        if(!$app){return;}
        $app = $app[0];

        // 标记已被取消授权
        $Model->execute("UPDATE wx_appid SET authorized=0, auth_unbound='".date('Y-m-d H:i:s')."' WHERE appid='{$appid}'");

        return;
        if(!$app['project_id']){
            return;
        }
        
        // 项目和公众号使用系统默认，防止用户访问数据出错
        $defaultAppid = C('DEFAULT_APPID');
        $defaultMchAppid = C('DEFAULT_WEIXIN');
        $Model->execute("UPDATE project_appid SET appid='{$defaultAppid}', mch_appid='{$defaultMchAppid}' WHERE project_id='{$app['project_id']}' AND appid='{$appid}'");
        
        $redis = new Redis();
        $redis->del('pro_'.$app['project_id']);
        $redis->del('host_'.$app['alias']);
    }
}
?>