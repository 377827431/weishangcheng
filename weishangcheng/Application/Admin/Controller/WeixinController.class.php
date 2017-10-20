<?php
namespace Admin\Controller;
use Common\Common\CommonController;
use Org\Wechat\WechatAuth;
use Common\Model\WxAuthModel;

/**
 * 微信配置
 *
 * @author lanxuebao
 *
 */
class WeixinController extends CommonController
{  

    /**
    * 微信公众号列表
    */
    public function index(){
        if(!IS_AJAX){
            $this->display();
        }
        $project = get_project($this->projectId);
        $wxAuth = new WxAuthModel(C('THIRD_APPID'));
        $authUrl= $wxAuth->getAuthUrl();

        $sql = "SELECT project_appid.alias AS id, wx_appid.appid, wx_appid.name, wx_appid.alias AS wx_alias,
                    wx_appid.qrcode, wx_appid.authorized, IF(wx_appid.authorized=1, auth_bound, auth_unbound) AS auth_time,
                    service_type, verify_type, open_pay
                FROM project_appid
                LEFT JOIN wx_appid ON wx_appid.appid=project_appid.appid
                WHERE project_appid.id='{$this->projectId}'";
        $Model = M();
        $list = $Model->query($sql);
        $rows = array();
        foreach($list as $i=>$item){
            $item['authorized'] = ($item['authorized'] ? '已授权' : '已解绑').substr($item['auth_time'], 0, 10);
            unset($item['auth_time']);

            $item['service_type'] = $item['service_type'] == 2 ? '服务号' : '订阅号';
            switch($item['verify_type']){
                case -1:
                    $item['service_type'] .= '[未认证]';
                break;
                case 0:
                case 1:
                case 2:
                    $item['service_type'] .= '[已认证]';
                break;
                case 3:
                case 4:
                case 5:
                    $item['service_type'] .= '[认证中]';
                break;
            }
            $item['mall_link'] = $project['host'].'/'.$item['id'];
            if($item['id'] == $project['alias']){
                $item['action'] = '<a href="'.$authUrl.'">添加</a>&nbsp<a href="/weixin/menu?appid='.$item['appid'].'">菜单</a>';
                array_splice($rows, 0, 0, array($item));
            }else{
                $item['action'] = '<a href="javascript:alertMsg(\'请登录微信公众号后台自行取消授权\');">解绑</a>&nbsp<a href="/weixin/menu?appid='.$item['appid'].'">菜单</a>';
                $rows[] = $item;
            }
        }
        $this->ajaxReturn($rows);
    }

    /**
    * 保存授权
    */
    public function auth(){
        $auth_code = $_GET['auth_code'];

        $redirect = __MODULE__.'/weixin';
        $project = get_project($this->projectId);
        $wxAuth = new WxAuthModel(C('THIRD_APPID'));
        $saved = $wxAuth->authorized($auth_code, $project);
        if(!$saved){
            $this->error($wxAuth->getError(), $redirect);
        }
        redirect($redirect);
    }

    public function index2(){
        $Model_wx = M("wx_config");
        $Model = M("shop");
        $shop = $Model->where("id='".$this->shop['id']."'")->field("wx_appid")->find();

        $data = $Model_wx->where("appid='{$shop["wx_appid"]}'")->find();
        
        if(IS_POST){
            $up = array(
                "appid" => I("post.appid"),
                "name" => I("post.name"),
                "wxid" => I("post.wxid"),
                "server_url" => I("post.server_url"),
                "original_id" => I("post.original_id"),
                "secret" => I("post.secret"),
                "token" => I("post.token"),
                "encoding_aes_key" => I("post.encoding_aes_key"),
                "mchAccess" => I("post.mchaccess"),
                "mch_id" => I("post.mch_id"),
                "sub_mch_id" => I("post.sub_mch_id"),
                "mch_key" => I("post.mch_key"),
                "mch_name" => I("post.mch_name"),
                "login_email" => I("post.login_email"),
                "qrcode" => I("post.qrcode")
            );
            
            if(I("post.edit_login_pwd") && I("post.edit_login_pwd")!=""){
                $up['login_pwd'] = I("post.edit_login_pwd");
            }
            
            if(I("post.login_pwd")){
                $up['login_pwd'] = I("post.login_pwd");
            }
            
            if(I("post.edit_mchpwd") && I("post.edit_mchpwd")!=""){
                $up['mchPwd'] = I("post.edit_mchpwd");
            }
            
            if(I("post.mchpwd")){
                $up['mchPwd'] = I("post.mchpwd");
            }
                
            //修改商铺的wx_appid
            $up_shop["wx_appid"] = I("post.appid");
            $Model->where("id='{$this->shop['id']}'")->save($up_shop);
            //修改商铺的微信配置文件
            if(empty($data)){
                $Model_wx->add($up);
            }else{
                $Model_wx->where("appid='{$shop["wx_appid"]}'")->save($up);
            }
            $this->success("已保存！");
        }
        $this->assign("data",$data);
        $this->display();
    }
    
    /**
     * 微信菜单
     */
    public function menu(){
        $defAppId = C('DEFAULT_APPID');
        $appid = '';
        
        if(!empty($_GET['appid'])){
            $appid = addslashes($_GET['appid']);
            if($appid != $defAppId){
                $wx_appid = M()->query("select appid from project_appid where id=".$this->projectId." and appid = '".$appid."'");
                if(empty($wx_appid)){
                    $this->error('公众号不存在');
                }
            }
        }else{
            $project = get_project($this->projectId);
            $appid = $project['appid'];
        }
        
        if($appid == $defAppId){
            //$this->error('默认公众号不能修改');
        }
        
        
        if(IS_POST){
            $this->saveMenu($appid);
        }
        
        
        $Module = M('wx_menu');
        $sql = "SELECT wx_appid.appid, wx_appid.name
                FROM project_appid
                LEFT JOIN wx_appid ON wx_appid.appid=project_appid.appid
                WHERE project_appid.id={$this->projectId}";
        $list = $Module->query($sql);
        $appList = array();
        foreach ($list as $item){
            $appList[$item['appid']] = $item['name'];
        }
        if(!isset($appList[$appid])){
            $this->error('公众号不存在');
        }
        $this->assign('applist', $appList);
        $this->assign('appid', $appid);
        
        $menu = $Module->where("appid='{$appid}'")->find();
        if(empty($menu)){
            $menu = array('button' => 'null');
        }
        $this->assign('menu', $menu);
        $this->display();
    }
    
    private function saveMenu($appid){
        $id          = $_POST['id'];
        $button      = $_POST['button'];
        $is_matchrule= $_POST['is_matchrule'];
        $matchrule   = $_POST['matchrule'];
        $modify_time = date('Y-m-d H:i:s');
        
        $data = array(
            'appid'        => $appid,
            'button'       => json_encode($button, JSON_UNESCAPED_UNICODE),
            'is_matchrule' => $is_matchrule,
            'matchrule'    => is_array($matchrule) ? json_encode($matchrule, JSON_UNESCAPED_UNICODE) : '',
            'modify_time'  => $modify_time
        );
        
        $Module = M('wx_menu');
        if(is_numeric($id) && $id > 0){
            $Module->where("id={$id} AND appid='{$appid}'")->save($data);
        }else{
            $Module->add($data);
            $id = $Module->getLastInsID();
        }
        
        $Model = M('wx_menu_event');
        $Model->where("menu_id=".$id)->delete();
        $button = $this->getButtons($id, $button);
        
        $wechat = new WechatAuth($appid);
        if($is_matchrule == 1){ //个性化菜单
            $matchrule = array(
                "group_id" => "",
                "sex" => 0,
                "country" => "中国",
                "province" => "",
                "city" => "",
                "client_platform_type" => "",
                "language" => ""
            );
            $result = $wechat->menuConditional($button, $matchrule);
            
            if($result["menuid"] > 0){
                $this->success('个性化菜单已更新，重新关注可立即看到效果', __ACTION__.'?appid='.$appid);
            }
        }else{//自定义菜单
            $result = $wechat->menuCreate($button, $matchrule);
            
            if($result['errmsg'] == 'ok'){
                $this->success('微信菜单已更新，重新关注可立即看到效果', __ACTION__.'?appid='.$appid);
            }
        }
        
        $this->error($result['errmsg']);
    }
    
    private function getButtons($menuId, $list){
        $Model  = M('wx_menu_event');
        
        $result = array();
        foreach ($list as $data){
            $eventKey = '';
            if($data['type'] == "advanced_news" || $data['type'] == "text"){
                $eventId = $Model->add(array('menu_id' => $menuId, 'type' => $data['type'], 'content' => $data['content']));
                $eventKey = $data['type'].".".$eventId;
            }else{
                $eventKey = $data['content'];
            }
            $button = $this->getButton($data, $eventKey);
            if(!empty($data['sub_button'])){
                $button['sub_button'] = $this->getButtons($menuId, $data['sub_button']);
            }
            $result[] = $button;
        }
        
        return $result;
    }
    
    /**
     * 菜单单项解析
     * @param unknown $item
     * @param unknown $key
     * @return multitype:multitype: string NULL unknown
     */
    private function getButton($item, $key){
        $menu = array('name' => $item['name']);
        // 跳转网页
        if($item['type'] == 'view'){
            $menu['type'] = 'view';
            $menu['url']  = $item['content'];
        }
        // 扫码
        else if($item['type'] == 'scan'){
            $menu['type'] = 'scancode_waitmsg';
            $menu['key']  = $key;
            $menu['sub_button']  = array();
        }
        // 相册或拍照
        else if($item['type'] == 'pic_photo_or_album' || $item['type'] == 'pic_sysphoto' || $item['type'] == 'pic_weixin' ){
            $menu['type'] = $item['type'];
            $menu['key']  = $key;
            $menu['sub_button']  = array();
        }
        // 图文消息
        else if($item['type'] == 'news' || $item['type'] == 'voice' || $item['type'] == 'video'){
            $menu['type']     = 'media_id';
            $menu['media_id'] = $item['content']['media_id'];
        }else if($item['type'] == 'text' || $item['type'] == 'advanced_news' || $item['type'] == 'event'){
            $menu['type'] = 'click';
            $menu['key']  = $key;
        }
        
        return $menu;
    }
    
    /**
     * 消息回复
     */
    public function reply(){
        $type = $_GET['type'] == 'keyword' ? 'keyword' : 'watch';
        
        $this->assign(array('type' => $type));
        $this->display();
    }
}
?>