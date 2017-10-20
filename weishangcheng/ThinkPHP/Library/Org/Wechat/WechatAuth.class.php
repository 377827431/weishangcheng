<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi.cn@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Org\Wechat;

class WechatAuth {

    /* 消息类型常量 */
    const MSG_TYPE_TEXT     = 'text';
    const MSG_TYPE_IMAGE    = 'image';
    const MSG_TYPE_VOICE    = 'voice';
    const MSG_TYPE_VIDEO    = 'video';
    const MSG_TYPE_MUSIC    = 'music';
    const MSG_TYPE_NEWS     = 'news';
    const MSG_TYPE_LOCATION = 'location';
    const MSG_TYPE_LINK     = 'link';
    const MSG_TYPE_EVENT    = 'event';

    /* 二维码类型常量 */
    const QR_SCENE       = 'QR_SCENE';
    const QR_LIMIT_SCENE = 'QR_LIMIT_SCENE';
    const QR_SCENE_TIME  = 2592000;

    /**
     * 微信开发者申请的appID
     * @var string
     */
    private $appid = '';

    /**
     * 微信api根路径
     * @var string
     */
    private $apiURL    = 'https://api.weixin.qq.com/cgi-bin';

    /**
     * 微信媒体文件根路径
     * @var string
     */
    private $mediaURL  = 'http://file.api.weixin.qq.com/cgi-bin';

    /**
     * 微信二维码根路径
     * @var string
     */
    private $qrcodeURL = 'https://mp.weixin.qq.com/cgi-bin';

    private $requestCodeURL = 'https://open.weixin.qq.com/connect/oauth2/authorize';

    private $oauthApiURL = 'https://api.weixin.qq.com/sns';

    /* 微信配置文件 */
    public $config;
    private $customservice;

    /**
     * 构造方法，调用微信高级接口时实例化SDK
     * @param string $appid  微信appid
     * @param string $secret 微信appsecret
     */
    public function __construct($config = null, $appId = null){
        if(!empty($config)){
            if (is_string($config)) {
                $config = get_wx_config($config);
            }
        }

        if(empty($config['appid'])){
            E('微信配置文件不存在');
        }

        $this->token = $config['token'];
        $this->appid = empty($appId) ? $config['appid'] : $appId;
        $this->config = $config;
    }

    public function getRequestCodeURL($redirect_uri, $state = null,
        $scope = 'snsapi_userinfo'){

        $query = array(
            'appid'         => $this->config['appid'],
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => $scope,
        );

        if(!is_null($state) && preg_match('/[a-zA-Z0-9]+/', $state)){
            $query['state'] = $state;
        }

        $query = http_build_query($query);
        return "{$this->requestCodeURL}?{$query}#wechat_redirect";
    }

    /**
     * 获取access_token，用于后续接口访问
     * @return array access_token信息，包含 token 和有效期
     */
    public function getAccessToken($type = 'client', $code = null, $try = true){
        $param = array(
            'appid'  => $this->appid,
            'secret' => $this->config['secret']
        );
        
        // 第三方公众号接口
        if($this->config['appid'] != $this->appid){
            if($type == 'client'){
                return $this->apiAuthorizerToken(); 
            }else{
                $param['component_appid'] = $this->config['appid'];
                $param['component_access_token'] = $this->getComponentAccessToken();
            }
        }

        switch ($type) {
            case 'client':
                $token = S('token_'.$this->appid);
                if($token){return $token;}
                
                $param['grant_type'] = 'client_credential';
                $url = "{$this->apiURL}/token";
                break;
            case 'code':
                $param['code'] = $code;
                $param['grant_type'] = 'authorization_code';
                $url = "{$this->oauthApiURL}/oauth2/component/access_token";
                break;
            default:
                E('不支持的grant_type类型！');
                break;
        }

        $token = self::http($url, $param);
        $token = json_decode($token, true);

        if(is_array($token)){
            if($token['errcode'] == 40029){
                S('token_'.$this->appid, null);
                return $this->getAccessToken($type, $code, false);
            }
            
            if(isset($token['errcode'])){
                E($token['errmsg']);
            } else {
                if($type == 'client'){
                    S('token_'.$this->appid, $token['access_token'], 6900);
                    return $token['access_token'];
                }
                return $token;
            }
        }else if($try){
            return $this->getAccessToken($type, $code, false);
        } else {
            E('获取微信access_token失败！');
        }
    }
    
    /**
     * 从缓存中读取token信息
     * access_token, access_expires, jsapi_token, jsapi_expires
     */
    private function cache($type, $val = null){
        $key = 'wx_token_'.$this->config['appid'];
        $token = S($key);
        
        if(is_null($val)){
            if(!$token){
                return false;
            }
            return $token;
        }
        S($key, $val, 7000);
    }

    /**
     * 获取授权用户信息
     * @param  string $token acess_token
     * @param  string $lang  指定的语言
     * @return array         用户信息数据，具体参见微信文档
     */
    public function getUserInfo($token, $lang = 'zh_CN'){
        $query = array(
            'access_token' => $token['access_token'],
            'openid'       => $token['openid'],
            'lang'         => $lang,
        );
        
        $info = self::http("{$this->oauthApiURL}/userinfo", $query);
        return json_decode($info, true);
    }

    /**
     * 上传媒体资源
     * @param  string $filename 媒体资源本地路径
     * @param  string $type     媒体资源类型，具体请参考微信开发手册
     */
    public function mediaUpload($filename, $type){
        $token = $this->getAccessToken();
        $param = array(
            'access_token' => $token,
            'type'         => $type
        );

        $filename = realpath($filename);
        if(!$filename) throw new \Exception('资源路径错误！');

        $file = array('media' => "@{$filename}");
        $url  = "{$this->mediaURL}/media/upload";
        $data = self::http($url, $param, $file, 'POST');

        return json_decode($data, true);
    }

    /**
     * 获取媒体资源下载地址
     * 注意：视频资源不允许下载
     * @param  string $media_id 媒体资源id
     * @return string           媒体资源下载地址
     */
    public function mediaGet($media_id, $filename = null){
        $token = $this->getAccessToken();
        $param = array(
            'access_token' => $token,
            'media_id'     => $media_id
        );

        $url = "{$this->mediaURL}/media/get?";
        return $url . http_build_query($param);
    }
    
    public function meidaDownLoad($media_id, $filename){
        $token = $this->getAccessToken();
        $param = array(
            'access_token' => $token,
            'media_id'     => $media_id
        );
    
        $url = "{$this->mediaURL}/media/get?" . http_build_query($param);
    
        $opts = array(
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_URL => $url
        );

        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
    
        //发生错误，抛出异常
        if($error) throw new \Exception('下载图片失败：' . $error);
    
        //$filename .= '/'.date('YmdHis').rand(1000, 9999);
        switch($info['content_type']){
            case 'image/jpeg':
                $filename.='.jpg';
                break;
            case 'image/x-png':
                $filename.='.png';
                break;
            case 'image/bmp':
                $filename.='.bmp';
                break;
            case 'image/gif':
                $filename.='.gif';
                break;
            default:
                $filename.='.jpg';
                break;
        }
    
        $local_file = fopen($_SERVER['DOCUMENT_ROOT'].$filename, 'w');
        fwrite($local_file, $data);
        fclose($local_file);
        return $filename;
    }

    /**
     * 给指定用户推送信息
     * 注意：微信规则只允许给在48小时内给公众平台发送过消息的用户推送信息
     * @param  string $openid  用户的openid
     * @param  array  $content 发送的数据，不同类型的数据结构可能不同
     * @param  string $type    推送消息类型
     */
    public function messageCustomSend($openid, $content, $type = self::MSG_TYPE_TEXT){

        //基础数据
        $data = array(
            'touser'=>$openid,
            'msgtype'=>$type,
        );

        // 以某个客服帐号来发消息（在微信6.0.2及以上版本中显示自定义头像）
        if(!empty($this->customservice)){
            $data['customservice'] = array('kf_account' => $this->customservice);
        }

        //根据类型附加额外数据
        $data[$type] = call_user_func(array($this, $type), $content);

        return $this->api('message/custom/send', $data);
    }
    
    public function thirdCustomSend($authCode, $openid){
        $info = $this->apiQueryAuth($authCode);
        
        $type = self::MSG_TYPE_TEXT;
        //基础数据
        $data = array(
            'touser'=>$openid,
            'msgtype'=> $type,
        );
        
        //根据类型附加额外数据
        $data[$type] = call_user_func(array($this, $type), $authCode.'_from_api');
        
        $params = array('access_token' => $info['authorizer_access_token']);
        
        $url  = "{$this->apiURL}/message/custom/send";
        if(!empty($data)){
            array_walk_recursive($data, function(&$value){
                $value = urlencode($value);
            });
            $data = urldecode(json_encode($data));
        }
        
        $data = self::http($url, $params, $data, 'POST');
        return json_decode($data, true);
    }

    /**
     * 设置以某个客服帐号来发消息（在微信6.0.2及以上版本中显示自定义头像）
     * @param string $customservice 客服账号
     */
    public function useCustomService($customservice = ''){
        if($customservice === ''){
            $this->customservice = $this->config['custom_service'] . '@' . $this->config['wxid'];
        }else{
            $this->customservice = $customservice;
        }
    }

    /**
     * 发送文本消息
     * @param  string $openid 用户的openid
     * @param  string $text   发送的文字
     */
    public function sendText($openid, $text){
        return $this->messageCustomSend($openid, $text, self::MSG_TYPE_TEXT);
    }

    /**
     * 发送图片消息
     * @param  string $openid 用户的openid
     * @param  string $media  图片ID
     */
    public function sendImage($openid, $media){
        return $this->messageCustomSend($openid, $media, self::MSG_TYPE_IMAGE);
    }

    /**
     * 发送语音消息
     * @param  string $openid 用户的openid
     * @param  string $media  音频ID
     */
    public function sendVoice($openid, $media){
        return $this->messageCustomSend($openid, $media, self::MSG_TYPE_VOICE);
    }

    /**
     * 发送视频消息
     * @param  string $openid      用户的openid
     * @param  string $media_id    视频ID
     * @param  string $title       视频标题
     * @param  string $discription 视频描述
     */
    public function sendVideo(){
        $video  = func_get_args();
        $openid = array_shift($video);
        return $this->messageCustomSend($openid, $video, self::MSG_TYPE_VIDEO);
    }

    /**
     * 发送音乐消息
     * @param  string $openid         用户的openid
     * @param  string $title          音乐标题
     * @param  string $discription    音乐描述
     * @param  string $musicurl       音乐链接
     * @param  string $hqmusicurl     高品质音乐链接
     * @param  string $thumb_media_id 缩略图ID
     */
    public function sendMusic(){
        $music  = func_get_args();
        $openid = array_shift($music);
        return $this->messageCustomSend($openid, $music, self::MSG_TYPE_MUSIC);
    }

    /**
     * 发送图文消息
     * @param  string $openid 用户的openid
     * @param  array  $news   图文内容 [标题，描述，URL，缩略图]
     * @param  array  $news1  图文内容 [标题，描述，URL，缩略图]
     * @param  array  $news2  图文内容 [标题，描述，URL，缩略图]
     *                ...     ...
     * @param  array  $news9  图文内容 [标题，描述，URL，缩略图]
     */
    public function sendNews(){
        $news   = func_get_args();
        $openid = array_shift($news);
        return $this->messageCustomSend($openid, $news, self::MSG_TYPE_NEWS);
    }

    /**
     * 发送一条图文消息
     * @param  string $openid      用户的openid
     * @param  string $title       文章标题
     * @param  string $discription 文章简介
     * @param  string $url         文章连接
     * @param  string $picurl      文章缩略图
     */
    public function sendNewsOnce(){
        $news   = func_get_args();
        $openid = array_shift($news);
        $news   = array($news);
        return $this->messageCustomSend($openid, $news, self::MSG_TYPE_NEWS);
    }

    /**
     * 创建用户组
     * @param  string $name 组名称
     */
    public function groupsCreate($name){
        $data = array('group' => array('name' => $name));
        return $this->api('groups/create', $data);
    }
    
    /**
     * 删除用户分组
     * @param int $id
     */
    public function groupsDelete($id){
        $data = array('group' => array('id' => $id));
        return $this->api('groups/delete', $data);
    }

    /**
     * 查询所有分组
     * @return array 分组列表
     */
    public function groupsGet(){
        return $this->api('groups/get', '', 'GET');
    }

    /**
     * 查询用户所在的分组
     * @param  string $openid 用户的OpenID
     * @return number         分组ID
     */
    public function groupsGetid($openid){
        $data = array('openid' => $openid);
        return $this->api('groups/getid', $data);
    }

    /**
     * 修改分组
     * @param  number $id   分组ID
     * @param  string $name 分组名称
     * @return array        修改成功或失败信息
     */
    public function groupsUpdate($id, $name){
        $data = array('group' => array('id' => $id, 'name' => $name));
        return $this->api('groups/update', $data);
    }

    /**
     * 移动用户分组
     * @param  string $openid     用户的OpenID
     * @param  number $to_groupid 要移动到的分组ID
     * @return array              移动成功或失败信息
     */
    public function groupsMemberUpdate($openid, $to_groupid){
        $data = array('openid' => $openid, 'to_groupid' => $to_groupid);
        return $this->api('groups/member/update', $data);
    }

    /**
     * 用户设备注名
     * @param  string $openid 用户的OpenID
     * @param  string $remark 设备注名
     * @return array          执行成功失败信息
     */
    public function userInfoUpdateremark($openid, $remark){
        $data = array('openid' => $openid, 'remark' => $remark);

        $data['component_appid'] = $this->config['appid'];
        $data['component_access_token'] = $this->getComponentAccessToken();
        return $this->api('user/info/updateremark', $data);
    }

    /**
     * 获取指定用户的详细信息
     * @param  string $openid 用户的openid
     * @param  string $lang   需要获取数据的语言
     */
    public function userInfo($openid, $lang = 'zh_CN'){
        $param = array('openid' => $openid, 'lang' => $lang);
        return $this->api('user/info', '', 'GET', $param);
    }

    /**
     * 获取关注者列表
     * @param  string $next_openid 下一个openid，在用户数大于10000时有效
     * @return array               用户列表
     */
    public function userGet($next_openid = ''){
        $param = array('next_openid' => $next_openid);
        return $this->api('user/get', '', 'GET', $param);
    }

    /**
     * 创建自定义菜单
     * @param  array $button 符合规则的菜单数组，规则参见微信手册
     */
    public function menuCreate($button, $matchrule = null){
        $data = array('button' => $button);
        if(is_array($matchrule) && !empty($matchrule)){
            $data['matchrule'] = $matchrule;
        }
        
        return $this->api('menu/create', $data);
    }
    
    /**
     * 创建个性化菜单
     * @param  array $button 符合规则的菜单数组，规则参见微信手册
     */
    public function menuConditional($button, $matchrule = null){
        $data = array('button' => $button);
        if(is_array($matchrule) && !empty($matchrule)){
            $data['matchrule'] = $matchrule;
        }
    
        return $this->api('menu/addconditional', $data);
    }

    /**
     * 获取所有的自定义菜单
     * @return array  自定义菜单数组
     */
    public function menuGet(){
        return $this->api('menu/get', '', 'GET');
    }

    /**
     * 删除自定义菜单
     */
    public function menuDelete(){
        return $this->api('menu/delete', '', 'GET');
    }

    /**
     * 创建二维码，可创建指定有效期的二维码和永久二维码
     * @param  integer $scene_id       二维码参数
     * @param  integer $expire_seconds 二维码有效期，0-永久有效
     */
    public function qrcodeCreate($scene_id, $expire_seconds = 0){
        $data = array();

        if(is_numeric($expire_seconds) && $expire_seconds > 0){
            $data['expire_seconds'] = $expire_seconds;
            $data['action_name']    = self::QR_SCENE;
            $data['action_info']['scene']['scene_id'] = $scene_id;
        } else {
            if(is_numeric($scene_id)){
                $data['action_name']    = self::QR_LIMIT_SCENE;
                $data['action_info']['scene']['scene_id'] = $scene_id;
            }else{
                $data['action_name']    = 'QR_LIMIT_STR_SCENE';
                $data['action_info']['scene']['scene_str'] = $scene_id;
            }
        }

        return $this->api('qrcode/create', $data);
    }

    /**
     * 根据ticket获取二维码URL
     * @param  string $ticket 通过 qrcodeCreate接口获取到的ticket
     * @return string         二维码URL
     */
    public function showqrcode($ticket){
        return "{$this->qrcodeURL}/showqrcode?ticket={$ticket}";
    }

    /**
     * 长链接转短链接
     * @param  string $long_url 长链接
     * @return string           短链接
     */
    public function shorturl($long_url){
        $data = array(
            'action'   => 'long2short',
            'long_url' => $long_url
        );

        return $this->api('shorturl', $data);
    }

    /**
     * 调用微信api获取响应数据
     * @param  string $name   API名称
     * @param  string $data   POST请求数据
     * @param  string $method 请求方式
     * @param  string $param  GET请求参数
     * @return array          api返回结果
     */
    protected function api($name, $data = '', $method = 'POST', $param = ''){
        if($this->config['appid'] != $this->appid){
            $token = $this->getComponentAccessToken();
        }else{
            $token = $this->getAccessToken();
        }
        $token = $this->getAccessToken();
        $params = array('access_token' => $token);

        if(!empty($param) && is_array($param)){
            $params = array_merge($params, $param);
        }

        $url  = "{$this->apiURL}/{$name}";
        if(!empty($data)){
            //保护中文，微信api不支持中文转义的json结构
            array_walk_recursive($data, function(&$value){
                $value = urlencode($value);
            });
            $data = urldecode(json_encode($data));
        }

        $data = self::http($url, $params, $data, $method);

        return json_decode($data, true);
    }

    /**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     * @param  string $url    请求URL
     * @param  array  $param  GET参数数组
     * @param  array  $data   POST的数据，GET请求时该参数无效
     * @param  string $method 请求方法GET/POST
     * @return array          响应数据
     */
    protected static function http($url, $param, $data = '', $method = 'GET'){
        $opts = array(
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_URL            => $url
        );

        /* 根据请求类型设置特定参数 */
        if(!empty($param)){
            $opts[CURLOPT_URL] .= '?' . http_build_query($param);
        }
        
        if(strtoupper($method) == 'POST'){
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $data;

            if(is_string($data)){ //发送JSON数据
                $opts[CURLOPT_HTTPHEADER] = array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($data),
                );
            }
        }

        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        //发生错误，抛出异常
        if($error) throw new \Exception('请求发生错误：' . $error);

        return  $data;
    }

    /**
     * 构造文本信息
     * @param  string $content 要回复的文本
     */
    private static function text($content){
        $data['content'] = $content;
        return $data;
    }

    /**
     * 构造图片信息
     * @param  integer $media 图片ID
     */
    private static function image($media){
        $data['media_id'] = $media;
        return $data;
    }

    /**
     * 构造音频信息
     * @param  integer $media 语音ID
     */
    private static function voice($media){
        $data['media_id'] = $media;
        return $data;
    }

    /**
     * 构造视频信息
     * @param  array $video 要回复的视频 [视频ID，标题，说明]
     */
    private static function video($video){
        $data = array();
        list(
            $data['media_id'],
            $data['title'],
            $data['description'],
        ) = $video;

        return $data;
    }

    /**
     * 构造音乐信息
     * @param  array $music 要回复的音乐[标题，说明，链接，高品质链接，缩略图ID]
     */
    private static function music($music){
        $data = array();
        list(
            $data['title'],
            $data['description'],
            $data['musicurl'],
            $data['hqmusicurl'],
            $data['thumb_media_id'],
        ) = $music;

        return $data;
    }

    /**
     * 构造图文信息
     * @param  array $news 要回复的图文内容
     * [
     *      0 => 第一条图文信息[标题，说明，图片链接，全文连接]，
     *      1 => 第二条图文信息[标题，说明，图片链接，全文连接]，
     *      2 => 第三条图文信息[标题，说明，图片链接，全文连接]，
     * ]
     */
    private static function news($news){
        $articles = array();
        foreach ($news as $key => $value) {
            list(
                $articles[$key]['title'],
                $articles[$key]['description'],
                $articles[$key]['url'],
                $articles[$key]['picurl']
            ) = $value;

            if($key >= 9) break; //最多只允许10条图文信息
        }

        $data['articles']     = $articles;
        return $data;
    }

    private static function httpRequest($url, $data = null, $contentType = null){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    //SSL 报错时使用
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);    //SSL 报错时使用
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(!empty($contentType)){
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:'.$contentType));
        }
        if (!empty($data)){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * 添加模板id
     * @param unknown $template_id_short
     */
    public function addTemplate($template_id_short){
        $data = array('template_id_short' => $template_id_short);
        return $this->api('template/api_add_template', $data);
    }

    /**
     * 发送模板消息
     * @param mixed $touser
     * @param array $data
     * @throws \Exception
     */
    public function sendTemplate($touser, array $data){
        $token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$token;
        $data['touser'] = $touser;
        $output = self::httpRequest($url, json_encode($data));
        return empty($output) ? null : json_decode($output, true);
    }

    /**
    *创建客服账号
    */
    public function setKeFu($data){
        $token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/customservice/kfaccount/add?access_token='.$token;
        $output = self::http($url, array(), $data , $method = 'POST');
        return empty($output) ? null : json_decode($output);
    }

    /**
     * 修改用户备注
     * @param unknown $openid
     * @param unknown $remark
     * @return Ambigous <NULL, mixed>
     */
    public function remark($openid, $remark){
        $token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$token;
        $data = array('openid' => $openid, 'remark' => $remark);
        $output = self::httpRequest($url, json_encode($data));
        return empty($output) ? null : json_decode($output, true);
    }
    
    /**
     * Summary of getJsApiTicket
     * @return mixed
     */
    public function getJsApiTicket() {
        $key = 'jsapi_'.$this->appid;
        $ticket = S($key);
        if($ticket){
            return $ticket;
        }else{
            $token = $this->getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=".$token;
            $response = self::httpRequest($url);
            $res = json_decode($response);
            $ticket = $res->ticket;
            S($key, $ticket, 6900);
            return $ticket;
        }
    }
    
    /**
     * 获取jssdk签名
     */
    public function getSignPackage($protocol=null){
        $appid = $this->appid;
        $jsapiTicket = $this->getJsApiTicket();
        // 注意 URL 一定要动态获取，不能 hardcode.
        if(IS_AJAX){
            $url = $_SERVER['HTTP_REFERER'];
        }else{
            $url = (is_ssl() ? "https://" : "http://").$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }

        $timestamp = NOW_TIME;
        $nonceStr = \Org\Util\String2::randString(16);
    
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=".$jsapiTicket."&noncestr=".$nonceStr."&timestamp=".$timestamp."&url=".$url;
        $signature = sha1($string);
    
        $signPackage = array(
            "appId"     => $appid,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "signature" => $signature
        );
        return $signPackage;
    }
    
    /**
     * 获取永久素材
     * @param unknown $type
     * @param unknown $offset
     * @param unknown $count
     * @return multitype:
     */
    public function batchgetMaterial($type, $offset, $count){
        $data = array('type' => $type, 'offset' => $offset, 'count' => $count);
        return $this->api('material/batchget_material', $data, $method = 'POST');
    }
    
    /**
     * 永久素材的总数
     * @return multitype:
     */
    public function getMaterialCount(){
        return $this->api('material/get_materialcount', '', $method = 'GET');
    }

    /**
     * 结束客服会话
     * @param unknown $data
     * @return NULL|mixed
     */
    public function closeCustomer($data){
        $url = 'https://api.weixin.qq.com/customservice/kfsession/close?access_token='.$this->getAccessToken();
        $output = self::httpRequest($url, json_encode($data));
        return empty($output) ? null : json_decode($output, true);
    }
    
    /**
     * 获取客服接待列表
     * @param unknown $kfAccount
     * @return mixed
     */
    public function getSessionList($kfAccount){
        $url = 'https://api.weixin.qq.com/customservice/kfsession/getsessionlist';
        $params = array(
            'access_token' => $this->getAccessToken(),
            'kf_account'   => $kfAccount
        );
        $data = self::http($url, $params, $data);
        return json_decode($data, true);
    }
    
    /**
     * 获取客服接待列表
     * @param unknown $kfAccount
     * @return mixed
     */
    public function getSession($openid){
        $url = 'https://api.weixin.qq.com/customservice/kfsession/getsession';
        $params = array(
            'access_token' => $this->getAccessToken(),
            'openid'       => $openid
        );
        $data = self::http($url, $params, $data);
        return json_decode($data, true);
    }
    
    /**
     * 获取所有客服
     * @return array
     */
    public function getKFList(){
        $url = $this->apiURL.'/customservice/getkflist';
        $params = array(
            'access_token' => $this->getAccessToken()
        );
        $data = self::http($url, $params);
        $data = json_decode($data, true);
        if(isset($data['kf_list'])){
            return $data['kf_list'];
        }
        return array();
    }
    
    /**
     * 获取在线客服接待信息
     */
    public function getOnlineKFList(){
        $url = $this->apiURL.'/customservice/getonlinekflist';
        $params = array(
            'access_token' => $this->getAccessToken()
        );
        
        $data = self::http($url, $params);
        $data = json_decode($data, true);
        if(isset($data['kf_online_list'])){
            return $data['kf_online_list'];
        }
        return array();
    }
    
    /**
     * 添加客服
     * @param String $kfAccount
     * @param String $nickname
     * @param String $password MD5加密后的明文，默认123456
     */
    public function addKF($kfAccount, $nickname, $password = ''){
        $url = 'https://api.weixin.qq.com/customservice/kfaccount/add';
        $param = array(
            'access_token' => $this->getAccessToken()
        );
        
        $data = array(
            'kf_account' => $kfAccount,
            'nickname'   => $nickname,
            'password'   => $password == '' ? md5('123456') : $password,
            'kf_wx'      => 'www_lxb_com'
        );
        
        if(!empty($data)){
            array_walk_recursive($data, function(&$value){
                $value = urlencode($value);
            });
            $data = urldecode(json_encode($data));
        }
        
        $result = self::http($url, $param, $data, 'POST');
        return json_decode($result, true);
    }
    
    /**
     * 删除客服
     * @param string $account 账号
     */
    public function delKF($account){
        $url = 'https://api.weixin.qq.com/customservice/kfaccount/del';
        $params = array(
            'access_token' => $this->getAccessToken(),
            'kf_account'   => $account
        );
        
        $data = self::http($url, $params);
        return json_decode($data, true);
    }
    
    /**
     * 获取未接入会话列表
     */
    public function getKFWaitting(){
        $url = 'https://api.weixin.qq.com/customservice/kfsession/getwaitcase';
        $params = array(
            'access_token' => $this->getAccessToken()
        );
        $data = self::http($url, $params);
        return json_decode($data, true);
    }
    
    /**
     * 客服创建会话
     * @param unknown $data
     * @return mixed
     */
    public function kfSessionCreate($data){
        $url = 'https://api.weixin.qq.com/customservice/kfsession/create?access_token='.$this->getAccessToken();
        //$data = array('kf_account' => '','openid' => '', 'text' => '');
        $data = self::http($url, null, $data, 'POST');
        return json_decode($data, true);
    }

    /**
     * 1、推送component_verify_ticket协议
     */
    public function componentVerifyTicket($ticket = null){
        $key = 'ticket_'.$this->config['appid'];
        if (!empty($ticket)) {
            S($key, $ticket, 86400);
        } else {
            return S($key);
        } 
    }

    /**
     * 2、获取第三方平台component_access_token
     */
    public function getComponentAccessToken(){
        $key = 'componen_token_'.$this->config['appid'];
        $access_token = S($key);
        if ($access_token) {
            return $access_token;
        }
        
        $data = array(
            'component_appid'           => $this->config['appid'],
            'component_appsecret'       => $this->config['secret'],
            'component_verify_ticket'   => $this->componentVerifyTicket(),
        );
        
        $url = $this->apiURL.'/component/api_component_token';
        $response = self::http($url, null, json_encode($data), 'POST');
        $response = json_decode($response, true);
        
        $access_token = $response['component_access_token'];
        S($key, $access_token, $response['expires_in'] - 600);
        return $access_token;
    }

    /**
     * 3、获取预授权码pre_auth_code
     */
    public function getPreAuthCode(){
        $key = 'pre_auth_'.$this->config['appid'];
        $pre_auth_code = S($key);
        if ($pre_auth_code) {
            return $pre_auth_code;
        }

        $url = $this->apiURL.'/component/api_create_preauthcode';
        $param = array('component_access_token' => $this->getComponentAccessToken());
        $data = array('component_appid' => $this->config['appid']);
        $response = self::http($url, $param, json_encode($data), 'POST');
        $response = json_decode($response, true);
        
        $pre_auth_code = $response['pre_auth_code'];
        S($key, $pre_auth_code, $response['expires_in'] - 180);
        return $pre_auth_code;
    }
    
    /**
     * 4、使用授权码换取公众号或小程序的接口调用凭据和授权信息
     */
    public function apiQueryAuth($code){
        $url = $this->apiURL.'/component/api_query_auth';
        $param = array('component_access_token' => $this->getComponentAccessToken());
        $data = array(
            'component_appid'     => $this->config['appid'],
            'authorization_code'  => $code
        );
        
        $response = self::http($url, $param, json_encode($data), 'POST');
        $response = json_decode($response, true);
        
        $info = $response['authorization_info'];

        // 调用接口凭证，和component_access_token不是一个(authorizer_appid=$this->appid)
        S('token_'.$info['authorizer_appid'], $info['authorizer_access_token'], $info['expires_in'] - 300);
        // 用于刷新authorizer_access_token
        S('refresh_'.$info['authorizer_appid'], $info['authorizer_refresh_token']);
        return $info;
    }
    
    private function getThirdRefreshToken($appid){
        $key = 'refresh_'.$appid;
        $data = S($key);
        if(!$data){
            E('授权TOKEN丢失，请重新授权');
        }
        return $data;
    }

    /**
     * 5、获取（刷新）授权公众号或小程序的接口调用凭据（令牌）
     */
    public function apiAuthorizerToken(){
        $key = 'token_'.$this->appid;
        $token = S($key);
        if($token){return $token;}

        $url = $this->apiURL.'/component/api_authorizer_token';
        $param = array('component_access_token' => $this->getComponentAccessToken());
        $data = array(
            'component_appid'          => $this->config['appid'],
            'authorizer_appid'         => $this->appid,
            'authorizer_refresh_token' => $this->getThirdRefreshToken($this->appid)
        );

        $response = self::http($url, $param, json_encode($data), 'POST');
        $response = json_decode($response, true);

        $token = $response['authorizer_access_token'];
        S($key, $token, $response['expires_in'] - 300);
        //S('refresh_'.$this->appid, $response['authorizer_refresh_token']);
        return $token;
    }

    /**
     * 6、获取授权方的帐号基本信息
     */
    public function getAuthorizerInfo($appid){
        $url = $this->apiURL.'/component/api_get_authorizer_info';
        $param = array('component_access_token' => $this->getComponentAccessToken());
        $data = array(
            'component_appid'  => $this->config['appid'],
            'authorizer_appid' => $appid
        );

        $response = self::http($url, $param, json_encode($data), 'POST');
        return json_decode($response, true);
    }
}
?>
