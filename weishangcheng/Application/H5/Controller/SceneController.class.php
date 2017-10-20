<?php
namespace H5\Controller;
use Common\Common\CommonController;
use Org\Wechat\WechatAuth;

/**
 * 场景
 * @author lanxuebao
 *
 */
class SceneController extends CommonController
{
    public function index(){
        $sceneId = $_GET['id'];
        $member = $this->getDLS();
        $this->assign('member', $member);
        
        //获取签名
        $WechatAuth = new WechatAuth();
        $sign = $WechatAuth->getSignPackage();
        $this->assign('sign', $sign);
        
        $this->display($sceneId);
    }
    
    private function getDLS(){
        $mid = 0;
        if(!is_numeric($_GET['mid'])){
            $mid = $this->user('id');
        }else{
            $mid = $_GET['mid'];
        }
        
        $Model = M();
        
        $sql = "SELECT dlsqr.ticket, member.id, member.nickname, member.agent_level 
                FROM member
                LEFT JOIN member_dlsqr AS dlsqr ON dlsqr.mid=member.id
                WHERE member.id=".$mid;
        $member = $Model->query($sql);
        if(empty($member)){
            return null;
        }else{
            $member = $member[0];
        }
        
        $codeUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=';
        if(!empty($member['ticket'])){
            $member['qrcode'] = $codeUrl.$member['ticket'];
            return $member;
        }
        
        $wechatAuth = new WechatAuth();
        
        $scene_id = "dls_".$member["id"];
        $qrcode   = $wechatAuth->qrcodeCreate($scene_id);
        $ticket = $qrcode['ticket'];
        $member['qrcode'] = $codeUrl.$ticket;
        $Model->execute("REPLACE INTO member_dlsqr(mid, mediaid, expires, ticket) VALUES('{$member['id']}', '', '0', '{$ticket}')");
        
        return $member;
    }
}
?>