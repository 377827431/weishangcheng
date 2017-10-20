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

/**
 * 客服咨询
 * @author lanxuebao
 *
 */
class KefuController
{
    private $wechat;
    private $config;
    private $wechatAuth;

    public function index()
    {
        $webConfig = get_wx_config($_GET['appid']);
        $this->config = $webConfig['WEIXIN'];
        
        // 微信修改配置验证token
        if (IS_GET) {
            exit(isset($_GET['echostr']) ? $_GET['echostr'] : '');
        }
        
        /* 加载微信SDK */
        $this->wechat = new Wechat($this->config);

        /* 获取请求信息 */
        $data = $this->wechat->request();
        if (! $data || ! is_array($data)) {
            exit('');
        }

        if($data['MsgType'] == Wechat::MSG_TYPE_EVENT){ // 事件消息
            switch ($data['Event']) {
                case Wechat::MSG_EVENT_SUBSCRIBE: // 关注
                    $this->subscribe($data);
                    break;
                case Wechat::MSG_EVENT_SCAN: // 二维码扫描
                    $this->scan($data['FromUserName'], $data['EventKey']);
                    break;
                case Wechat::MSG_EVENT_UNSUBSCRIBE: // 取消关注
                    break;
                case Wechat::MSG_EVENT_TEMPLATESENDJOBFINISH: // 发送模板消息 - 事件推送
                    break;
                case Wechat::MSG_EVENT_CLICK: // 菜单点击
                    break;
                case Wechat::MSG_EVENT_LOCATION: // 报告位置
                    break;
                case Wechat::MSG_EVENT_MASSSENDJOBFINISH: // 群发消息成功
                    break;
                case 'scancode_waitmsg':    // 扫码推事件且弹出“消息接收中”提示框
                    break;
                case 'kf_create_session':   // 客服接入会话
                    $this->kfCreated($data);
                    break;
                case 'kf_close_session':    // 客服关闭会话
                    $this->kfClosed($data);
                    break;
                case 'kf_switch_session':   // 转接会话
                    $this->kfSwitched($data);
                    break;
                case 'VIEW':
                    break;
                default:
                    exit('');
            }
        }else if($data['MsgType'] == Wechat::MSG_TYPE_TEXT){
            $this->receiveText($data['Content'], $data['FromUserName']);
        }
        
        $this->contactCustomer($data['FromUserName']);
    }
    
    private function WechatAuth(){
        if(is_null($this->wechatAuth)){
            $this->wechatAuth = new WechatAuth($this->config);
        }
        return $this->wechatAuth;
    }

    /**
     * 关键字自动回复
     *
     * @param unknown $text
     * @param unknown $openid
     */
    private function receiveText($text, $openid){
        $this->contactCustomer($openid, $text);
    }
    
    /**
     * 关注事件处理
     *
     * @param mixed $data
     */
    private function subscribe($data){
        $openid = $data['FromUserName'];
    
        if(!empty($data['EventKey'])){
            $this->scan($openid, substr($data['EventKey'], 8));
        }
    }
    
    /**
     * 扫描带参数二维码
     * @param unknown $openid
     * @param unknown $scene_str
     */
    private function scan($openid, $scene_str){
        $array = explode('-', $scene_str);
        switch ($array[0]){
            case 'kefu':    // 指定客服
                $this->givenKF($array[1]);
                break;
        }
    }
    
    /**
     * 转发到微信多客服
     * @param string $KfAccount
     */
    private function toCustomer($openid, $KfAccount = null){
        $text = '';
        
        $wechatAuth = $this->WechatAuth();
        $session = $wechatAuth->getSession($openid);

        // 结束上次会话，并重新接入
        if(!isset($session['errcode']) && !empty($session['kf_account'])){
            if($session['kf_account'] != $KfAccount){
                $wechatAuth->closeCustomer(array(
                    'kf_account' => $session['kf_account'],
                    'openid' => $openid,
                    'text' => '用户请求让其他客服来接待'
                ));
                
                $text .= '已取消排队，为您转接其他客服，请勿重复识别不同的客服二维码，以免增加您的排队时间\r\n\r\n';
            }
        }

        $text .= '正在为您转接专属客服！';
        $waitting = $wechatAuth->getKFWaitting();
        if(!isset($waitting['errcode']) && $waitting['count'] > 0){
            $text .= '\r\n您前面还有'.$waitting['count'].'人，请排队等待...';
        }else{
            $text .= '\r\n请稍后...';
        }
        
        $this->WechatAuth()->sendText($openid, $text);
        $this->wechat->replyCustomer($KfAccount);
    }
     
    /**
     * 联系在线客服
     */
    private function contactCustomer($openid, $keyword = ''){
        $text = '';
        $config = C('KEFU');
        $workStart = str_replace(':', '', $config['work_start']);
        $workEnd = str_replace(':', '', $config['work_end']);
        $hours = date('Hi');
        
        // 查找在线的客服
        $onLineList = $this->wechatAuth()->getOnlineKFList();
        
        // 客服已下班，转到匹配的QQ客服
        /*
        if($hours > $workEnd && $keyword !== '' && mb_strlen($keyword, 'utf8') < 7){
            $keyword = addslashes($keyword);
            $sql = "SELECT id, nickname, kf_account, qq FROM kf_list WHERE MATCH (keyword) AGAINST ('{$keyword}' IN BOOLEAN MODE) AND '".date('H:i')."' BETWEEN work_start AND work_end";
            $list = M()->query($sql);
            
            if(count($list) > 0){
                shuffle($list);
                $qqlist = '';
                foreach ($list as $item){
                    foreach ($onLineList as $online){
                        if($online['kf_account'] == $item['kf_account']){
                            $this->toCustomer($openid, $item['kf_account'], $item['accepted_case']);
                        }
                    }
                    
                    // QQ客服
                    if(!is_numeric($item['qq'])){
                        $qqlist .= '\r\n<a href="http://wpa.qq.com/msgrd?v=3&uin='.$item['qq'].'&site=qq&menu=yes">'.$item['nickname'].'</a>';
                    }
                }
                if($qqlist != ''){
                    $text = '微信客服已离线，请联系QQ客服：'.$qqlist.'\r\n请保证您已登录手机QQ，否则可能无法连接QQ客服';
                    $this->wechat->replyText($text);
                }
            }
        }
        */
        
        if(count($onLineList) == 0){
            if($hours < $workStart){
                $text = '亲，客服还没有上班哦！';
            }else if($hours > $workEnd){
                $text = '铁打的客服也要休息，求放过！';
            }else{
                $text = '客服妹妹开小差啦？暂无在线客服！';
            }
            $text .= "\r\n请".$config['work_start'].'~'.$config['work_end'].'再联系客服！';
            $this->wechat->replyText($text);
        }
        /*
        else if(is_numeric($keyword) && $keyword > 0 && $keyword < 3){
            $this->toCustomer($openid, null, $item['accepted_case']);
        }
        */
        
        $this->wechat->replyNewsOnce(
            '【通知】好消息！！专属客服功能开通了~更快捷，更方便，更贴心！',
            "点击查看详细信息，或请通过商品详情、订单详情来咨询客服！\r\n接待时间：".$config['work_start']."~".$config['work_end']."\r\n售后热线：".$config['FOUR_ZERO_ZERO'],
            'https://mp.weixin.qq.com/s?__biz=MzI0NTQ3NzM2Ng==&mid=100004787&idx=1&sn=cf78c5f8fc9bd47bbafa73295f68d5bb&chksm=694ca92a5e3b203c67ac6ff97bb4c607e71f1994f4053ebbe6890d80e3b348d7c44cf312bcc9#rd',
            'https://mmbiz.qpic.cn/mmbiz_jpg/5Z5kMc99DGI1h25smjMzZycwEJuQTTqPttMAGiaaKGUkXSULHWSExAcZCAzuZEgW7oMB095nHty7gOdELhibIVNQ/0?wx_fmt=jpeg'
        );
        
        $text = "亲，欢迎使用在线客服！\r\n请通过商品详情或订单详情来接入您的专署客服！\r\n【投诉热线】".C('FOUR_ZERO_ZERO');
        $this->wechat->replyText($text);
    }
    
    /**
     * 指定客服
     */
    private function givenKF($id){
        $service = M('kf_list')->find($id);
        if(empty($service) || !$service['enabled']){
            return;
        }
        
        // 是否在上班期间
        $hour = date('Hi');
        if($hour < str_replace(':', '', $service['work_start']) || $hour > str_replace(':', '', $service['work_end'])){
            return;
        }
        
        // 微信在线客服
        if(!empty($service['kf_account'])){
            $onLineList = $this->wechatAuth()->getOnlineKFList();
            foreach ($onLineList as $kf){
                if($kf['kf_account'] == $service['kf_account']){
                    $this->wechat->replyCustomer($service['kf_account']);
                }
            }
        }
        
        // QQ客服
        if(is_numeric($service['qq'])){
            $text = "微信客服已离线\r\n<a href=\"http://wpa.qq.com/msgrd?v=3&uin=".$service['qq']."&site=qq&menu=yes\">点击这里为您连接QQ客服</a>\r\n请保证您已登录手机QQ，否则可能无法连接QQ客服";
            $this->wechat->replyText($text);
        }
    }
    
    /**
     * 根据客服账号获取客服信息
     * @param unknown $account
     * @return mixed
     */
    private function getKFByAccount($account){
        $data = M()->query("SELECT id, nickname, weixin FROM kf_list WHERE kf_account='$account'");
        return $data[0];
    }
    
    /**
     * 客服接入会话
     * @param unknown $data
     */
    private function kfCreated($data){
        $text = '已接通专属客服';
        $kefu = $this->getKFByAccount($data['KfAccount']);
        if(!empty($kefu)){
            $text .= '：'.$kefu['nickname'].'\r\n咨询过后记得好评哦！';
        }
        
        // 更新接待人数
        M()->execute("UPDATE kf_list SET times=times+1 WHERE id=".$kefu['id']);
        $this->WechatAuth()->sendText($data['FromUserName'], $text);
        exit('');
    }
    
    /**
     * 客服关闭会话
     * @param unknown $data
     */
    private function kfClosed($data){
        $this->sendEvaluate($data['FromUserName'], $data['KfAccount']);
        exit('');
    }
    
    /**
     * 客服转接会话
     * @param unknown $data
     */
    private function kfSwitched($data){
        $this->sendEvaluate($data['FromUserName'], $data['FromKfAccount']);
        exit('');
    }
    
    /**
     * 评价客服
     */
    private function sendEvaluate($openid, $kfAccount){
        $kefu = $this->getKFByAccount($kfAccount);
        if(empty($kefu)){
            return;
        }
        
        $wechatAuth = $this->WechatAuth();
        $userinfo = $wechatAuth->userInfo($openid);

        $sql = "INSERT INTO kf_evaluate
                SET created='".date('Y-m-d H:i:s')."',
                    openid='{$openid}',
                    nickname='".addslashes($userinfo['nickname'])."',
                    attitude=0,
                    kf_id='".addslashes($kefu['id'])."',
                    kf_weixin='".addslashes($kefu['weixin'])."',
                    kf_nick='".addslashes($kefu['kf_nick'])."'";
        $Model = M();
        $Model->execute($sql);
        $id = $Model->getLastInsID();
        
        $url = C('PROTOCOL').$_SERVER['HTTP_HOST'].'/h5/kefu/evaluate?id='.$id.'&attitude=';
        $text  = '会话结束，感谢您的来访\r\n请对我的服务做出评价：';
        $text .= '\r\n<a href=\"'.$url.'5\">非常满意★★★★★</a>';
        $text .= '\r\n<a href=\"'.$url.'4\">还算满意★★★★　</a>';
        $text .= '\r\n<a href=\"'.$url.'3\">一般般啦★★★　　</a>';
        $text .= '\r\n<a href=\"'.$url.'2\">有点失望★★　　　</a>';
        $text .= '\r\n<a href=\"'.$url.'1\">我要投诉★　　　　</a>';
        $this->WechatAuth()->sendText($openid, $text);
    }
}
?>