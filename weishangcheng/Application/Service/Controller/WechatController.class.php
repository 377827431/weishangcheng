<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace Service\Controller;

use Org\Wechat\Wechat;
use Org\Wechat\WechatAuth;
use Common\Model\MemberModel;
use Common\Model\WxAuthModel;

class WechatController extends Wechat
{
    /**
     * 微信消息接口入口
     * 所有发送到微信的消息都会推送到该操作
     * 所以，微信公众平台后台填写的api地址则为该操作的访问地址
     */
    public function handler($data){
        if(isset($data['MsgType'])){
            if($data['MsgType'] == Wechat::MSG_TYPE_EVENT){ // 事件消息
                switch ($data['Event']) {
                    case Wechat::MSG_EVENT_SUBSCRIBE: // 关注
                        $this->subscribe($data);
                        break;
                    case Wechat::MSG_EVENT_SCAN: // 二维码扫描
                        $this->scan($data['FromUserName'], $data['EventKey']);
                        break;
                    case Wechat::MSG_EVENT_UNSUBSCRIBE: // 取消关注
                        $this->unSubscribe($data['FromUserName']);
                        break;
                    case Wechat::MSG_EVENT_TEMPLATESENDJOBFINISH: // 发送模板消息 - 事件推送
                        $this->templateSendJobFinish($data);
                        break;
                    case Wechat::MSG_EVENT_CLICK: // 菜单点击
                        $this->msgEventClick($data);
                        break;
                    case Wechat::MSG_EVENT_LOCATION: // 报告位置
                        $this->replyText($data['Event'].'from_callback');
                        break;
                    case 'scancode_waitmsg':    // 扫码推事件且弹出“消息接收中”提示框
                        $this->scancode($data['FromUserName'], $data['ScanCodeInfo']['ScanResult'], $data['ScanCodeInfo']['ScanType']);
                        break;
                    case Wechat::MSG_EVENT_MASSSENDJOBFINISH: // 群发消息成功
                    case 'VIEW':
                    default:
                        exit('success');
                }
            }else if($data['MsgType'] == Wechat::MSG_TYPE_TEXT){
                // 用于微信开放平台第三方公众号上线全网发布检测使用，部署后请注释掉，切勿删除
                if($data['Content'] == 'TESTCOMPONENT_MSG_TYPE_TEXT'){
                    $this->replyText('TESTCOMPONENT_MSG_TYPE_TEXT_callback');
                }else if(substr($data['Content'], 0, 16) == 'QUERY_AUTH_CODE:'){
                    $code = explode(':', $data['Content']);
                    $redis = new \Think\Cache\Driver\Redis();
                    $result = $redis->lPublish('Test', $this->config['appid'], $data['FromUserName'], $code[1]);
                    exit('');
                }
                
                // 自动回复
                $this->receiveText($data['Content'], $data['FromUserName']);
            }
        }else if(isset($data['InfoType'])){
            switch ($data['InfoType']) {
                case 'unauthorized':    // 解绑授权
                    $wxAuth = new WxAuthModel($this->config);
                    $wxAuth->unauthorized($data['AppId']);
                    break;
                case 'authorized':  // 授权
                    break;
                case 'updateauthorized': // 更新权限
                    $wxAuth = new WxAuthModel($this->config);
                    $wxAuth->updateauthorized($data['AuthorizationCode']);
                    break;
                case 'component_verify_ticket': // 定时推送给我们的通信ticket
                    $this->WechatAuth()->componentVerifyTicket($data['ComponentVerifyTicket']);
                    break;
                default:
                    break;
            }
        }
        exit('success');
    }

    /**
     * 关注事件处理
     *
     * @param mixed $data
     */
    private function subscribe($data){
        $openid = $data['FromUserName'];

        $eventKey = '';
        if(isset($data['EventKey'])){
            $eventKey = substr($data['EventKey'], 8);
        }

        $config = get_wx_config($this->appid);
        $Model = new MemberModel();
        $data = $Model->subscribe($this->config, array(
            'appid'      => $this->appid,
            'openid'     => $openid,
            'project_id' => $config['project_id'],
            'time'    => $data['CreateTime'],
            'source'  => $eventKey ? $eventKey : 'subscribe'
        ));

        $first = $data['first_sub'];
        if($eventKey != ''){
            $this->scan($openid, $eventKey, $first);
        }

        $sql = "SELECT reply.content, is_rand
                FROM wx_reply AS reply
                WHERE reply.appid='{$this->appid}' AND reply.is_subscribe=1
                UNION ALL
                SELECT reply.content, is_rand
                FROM wx_reply AS reply
                WHERE reply.appid='{$this->appid}' AND reply.is_default=1";
        $reply = $Model->query($sql);
        $this->doAutoReply($openid, $reply[0]['content'], $reply[0]['is_rand']);
    }

    /**
     * 微信通用接口类
     */
    private function WechatAuth(){
        static $wechatAuth = null;
        if(is_null($wechatAuth)){
            $wechatAuth = new WechatAuth($this->config, $this->appid);
        }
        return $wechatAuth;
    }

    /**
     * 扫描带参数二维码
     * @param unknown $openid
     * @param unknown $scene_str
     */
    private function scan($openid, $scene_str, $newMember = false){
        if($scene_str == 'dls_13'){ // 魔力果冻印刷的二维码
            $scene_str = 'moliguodong';
        }

        if($scene_str == 'lxkf'){
            $this->contactCustomer($openid);
        }else if($scene_str == 'moliguodong'){
            // https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQGs8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3NUaV8xVFhsdnMtSDRTUGRtaEF0AAIEmOURWAMEAAAAAA==
            $this->moliguodong($openid, $newMember);
        }
        else if($scene_str == 'permanent'){
            $this->WechatAuth()->sendNewsOnce($openid,'免费送会员', '输入邀请码，免费获取会员名额', "http://wslm.hljwtlm3.com/h5/personal?data='register'", '');
        }
        else if(is_numeric($scene_str)){
            $QrcodeModel = new \Common\Model\QrcodeModel();
            $qrcode = $QrcodeModel->where("id='{$scene_str}'")->find();
            if(!empty($qrcode)){
                switch ($qrcode['type']){
                    case $QrcodeModel::COUPON:  // 优惠券
                        $this->replyCoupon($qrcode['outer_id'], $openid, $newMember);
                        break;
                    case $QrcodeModel::GOODS:  // 商品
                        $this->replyGoods($qrcode['outer_id']);
                        break;
                    case "share_qr":  // 分享绑定上下级关系
                        $this->updatePid($openid, $qrcode['outer_id'], $newMember);
                        if($qrcode['params'] == 'meipai'){
                            $list = $QrcodeModel->query("SELECT url,title,discription,picurl FROM `live` ORDER BY `id` DESC LIMIT 1");
                            if(!empty($list)){
                                $list = $list[0];
                                $this->WechatAuth()->sendNewsOnce($openid,$list['title'], $list['discription'], $list['url'], $list['picurl']);
                            }
                        }

                        break;
                }
            }
        }
        else if(substr($scene_str, 0, 4) == 'dls_'){ //发展代理
            $pid = substr($scene_str, 4);
            $this->updatePid($openid, $pid, $newMember);
        }else if (substr($scene_str, 0, 7) == 'qrcode_'){
            $scene_str = substr($scene_str, 7, -7);
            $scene_str = explode('1688wsc_', $scene_str);
            $shopId = $scene_str[0];
            $projectId = substr($shopId, 0, -3);
            $goods_id = $scene_str[1];
            $project = M('project_appid')->where(array("id" => $projectId))->find();
            $member = M('member')
                ->join("wx_user AS wx ON wx.mid=member.id")
                ->where("wx.openid='{$openid}'")
                ->find();
            $up = array(
                "last_host" => $project['alias']
            );
            M('member')->where(array("id" => $member['id']))->save($up);
            $wechatAuth = new \Org\Wechat\WechatAuth($project['appid']);
            if ($goods_id > 0 && false){
                if (!empty($scene_str[2])){
                    $share = '&share_mid='.$scene_str[2];
                }else{
                    $share = '';
                }
                $url = C('H5_HOST')."/".$project['alias'].'/goods?id='.$goods_id.$share;
                $goods_info = M('mall_goods')
                    ->field("mall_goods.title, mgc.images, mgc.digest")
                    ->join("mall_goods_content AS mgc ON mgc.goods_id = mall_goods.id")
                    ->where("mall_goods.id = {$goods_id}")
                    ->find();
                if (!empty($goods_info['images'])){
                    $pic = explode(',', $goods_info['images']);
                    $pic = $pic[0];
                }else{
                    $pic = '';
                }
                $wechatAuth->sendNews($openid, $goods_info['title'], $goods_info['digest'], $url, $pic);
            }elseif (true){
//                if (!empty($scene_str[2])){
//                    $share = '?share_mid='.$scene_str[2];
//                }else{
//                    $share = '';
//                }
//                $url = C('H5_HOST')."/".$project['alias'].$share;
//                $shop = M('shop')
//                    ->field("shop.id, shop.name, si.desc, si.shop_sign")
//                    ->join('shop_info as si ON si.id = shop.id')
//                    ->where(array("id" => $shopId))
//                    ->find();
//                $wechatAuth->sendNews($openid, $shop['name'], $shop['desc'], $url, $shop['shop_sign']);
                $wechatAuth->sendText($openid, "欢迎亲！在这里您可以更方便的查询物流、管理订单等。");
            }
        }

        $array = explode('-', $scene_str);
        switch ($array[0]){
            case 'coupon':
                $Model = M('kf_list');
                $videoList = $Model->field("weixin, qrcode")->where("type=2 AND enabled=1")->select();
                if(count($videoList) > 0){
                    shuffle($videoList);
                    $wechatAuth = $this->WechatAuth();
                    $html = '想了解更多请添加我们的小视频微信号：';
                    foreach ($videoList as $i=>$item){
                        $html .= '\r\n<a href=\"'.$videoList[0]['qrcode'].'\">'.$videoList[0]['weixin'].'</a>';
                    }
                    $wechatAuth->sendText($openid, $html);
                }

                $this->replyCoupon($array[1], $openid, $newMember);
                break;
        }
    }

    /**
     * 绑定上级
     * @param String $openid
     * @param int $pid
     * @param Boolean $newMember
     */
    private function updatePid($openid, $pid, $newMember = false){
        $Model = M('member');
        $member = $Model->join("wx_user AS wx ON wx.mid=member.id")
                ->where("wx.openid='{$openid}'")
                ->find();
        $error_array = "";
        if(empty($member)){
            $error_array = '二维码无效，无法绑定为好友！';
        }else if($member['agent_level'] > 0){
            $error_array ='您已成为代理，无需绑定上级好友';
        }else if($member['id'] == $pid){
            $error_array ='您不能绑定您自己';
        }else if($member['pid'] == $pid){
            $error_array ='您早已与推荐人成为好友关系了!';
        }else if($member['pid'] > 0){
            $error_array ='不可重新绑定推荐人!';
        }
        if(!empty($error_array)){
            $this->WechatAuth()->sendText($openid,$error_array);
            return ;
        }
        $member['is_new'] = $newMember;
        $member['subscribe'] = 1;

        $GuanzhuModel = new \Common\Model\GuanzhuModel();
        $shangxian = $GuanzhuModel->shareGuanzhu($member, $pid);
        if($shangxian == -1){
            $this->WechatAuth()->sendText($openid,$GuanzhuModel->getError());
        }

        $this->WechatAuth()->sendText($openid,'您已与推荐人【'.$shangxian['nickname'].'】绑定为好友！');
    }

    /**
     * 回复商品信息
     * @param int $goods_id
     */
    private function replyGoods($goods_id){
        $goods = M('mall_goods')->find($goods_id);
        if(empty($goods)){
            //  || $goods['is_del'] == 1 || $goods['is_display'] != 1
            return;
        }

        $title = $goods['title'];
        $discription = $goods['digest'];
        $url = C('PROTOCOL').$_SERVER['HTTP_HOST'].'/h5/goods?id='.$goods['id'];
        $picurl = $goods['pic_url'];

        $this->replyNewsOnce($title, $discription, $url, $picurl);
    }

    /**
     * 取消关注事件处理
     *
     * @param mixed $data
     */
    private function unSubscribe($openid){
        M()->execute("UPDATE wx_user SET subscribe=0, unsubscribe_time=" . time() . " WHERE appid='{$this->appid}' AND openid='{$openid}'");
    }

    /**
     * 发送模板消息 - 事件推送
     *
     * @param mixed $data
     */
    private function templateSendJobFinish($data) {

    }

    /**
     * 菜单点击事件推送
     *
     * @param mixed $data
     */
    private function msgEventClick($data){
        switch ($data['EventKey']) {
            case 'fzdl':
                $this->fzdl($data['FromUserName']);
                break;
            case 'lxkf':
                $this->contactCustomer($data['FromUserName']);
            case 'sign':
                $this->sign($data['FromUserName']);
            default:
                $this->menuMsg($data['EventKey']);
                break;
        }
    }

    /**
     * 响应菜单消息（回复 文字 图文 消息）
     *
     * @param mixed $key
     */
    private function menuMsg($key){
        $event = explode(".", $key);

        //回复文字消息
        if($event[0] == "text"){
            $msg = M("wx_event")->find($event[1]);
            if(!empty($msg)){
                $this->replyText($msg["content"]);
            }
        }

        //回复图文消息
        if($event[0] == "advanced_news"){
            $this->replyAdvanced($event[1]);
        }
    }

    /**
     * 回复高级图文
     *
     * @param mixed $key
     */
    private function replyAdvanced($id, $openid = null){
        $rows = array();
        $sql  = " SELECT title, digest, link, cover_url FROM wx_news WHERE id={$id}";
        $sql .= " UNION ALL ";
        $sql .= " SELECT title, digest, link, cover_url FROM wx_news WHERE project_id=(SELECT project_id FROM wx_news WHERE id={$id}) AND pid={$id}";
        $sql .= " ORDER BY id";
        $list = $this->query($sql);
        if(empty($list)){
            return;
        }
        
        $news = array();
        foreach($list as $item){
            $news[] = array_values($item);
        }
        
        if(is_null($openid)){
            $this->replyNews($news);
        }
        
        $wechatAuth = $this->WechatAuth();
        $wechatAuth->sendNews($news);
    }

    /**
     * 关键字自动回复
     *
     * @param unknown $text
     * @param unknown $openid
     */
    private function receiveText($text, $openid){
        //$this->replyText($text);
        $this->autoReply($text, $openid);
    }

    /**
     * 自动回复
     * array $reply_con
     * @param unknown $reply_con
     */
    private function autoReply($text, $openid){
        $Model = M();
        $sql = array();
        $text = addslashes($text);
        if(mb_strlen($text, 'UTF-8') <= 20){
            $sql[] = "SELECT reply.content, is_rand
                    FROM wx_reply AS reply
                    INNER JOIN wx_keyword AS kw ON reply.id=kw.reply_id
                    WHERE reply.appid='{$this->appid}' AND kw.full_match=1 AND kw.keyword='{$text}'";
            $sql[] = "SELECT reply.content, is_rand
                    FROM wx_reply AS reply
                    INNER JOIN wx_keyword AS kw ON reply.id=kw.reply_id
                    WHERE reply.appid='{$this->appid}' AND kw.full_match=0 AND '{$text}' LIKE CONCAT('%', kw.keyword, '%')";
        }
        $sql[] = "SELECT reply.content, is_rand
                  FROM wx_reply AS reply
                  WHERE reply.appid='{$this->appid}' AND reply.is_default=1";
        $reply = $Model->query(implode(' UNION ALL ', $sql));
        $this->doAutoReply($openid, $reply[0]['content'], $reply[0]['is_rand']);
    }

    /**
     * 发送自动回复消息
     */
    private function doAutoReply($openid, $contents, $isRand){
        if(empty($contents)){
            return;
        }

        //格式化回复内容
        $contents = json_decode($contents, true);
        //获取随机数
        $rand = rand(0, count($contents)-1);
        $wechatAuth = $this->WechatAuth();
        foreach ($contents as $i => $item){
            if($isRand && $i != $rand){
                continue;
            }
            
            $type = $item["type"];
            if($type == "text"){ //回复文字
                $wechatAuth->sendText($openid, $item["content"]);
            }else if($type == "senior"){ //回复高级图文
                $this->replyAdvanced($item["id"], $openid);
            }else if($type == "news"){
                $news = array();
                foreach($item['content'] as $val){
                    $news[] = array($val['title'], $val['digest'], $val['url'], $val['thumb_url']);
                }
                $wechatAuth->sendNews($openid, $news);
            }else if($type == "voice"){ //语音
                $wechatAuth->sendVoice($openid, $item["media_id"]);
            }else if($type == "video"){ // 视频
                $wechatAuth->sendVideo($openid, $item["media_id"], $item["name"], $item["name"]);
            }
        }
        exit('success');
    }

    /**
     * 转发到微信多客服
     * @param string $KfAccount
     */
    private function toCustomer($KfAccount = null){
        $this->replyCustomer($KfAccount);
    }

    /**
     * 联系在线客服
     */
    private function contactCustomer($openid){
        $this->replyText('正在开发中...');
    }

    /**
     * 签到
     */
    private function sign($openid){
        $Model = D('Sign');
        $sign = $Model->sign($openid);
        if($sign < 0){
            $this->replyText($Model->getError());
        }

        $text = "签到成功，已连续签到".$sign['continued']."次，\r\n本次获得".sprintf('%.2f', $sign['money']).'积分';
        $this->replyText($text);
    }

    /**
     * 扫码推事件且弹出“消息接收中”提示框
     * @param unknown $data
     */
    private function scancode($openid, $result, $type){
        $this->replyText($type.':'.$result);
    }

    private function moliguodong($openid, $isNew = false){
        $this->replyGoods(1914);
    }
}
?>