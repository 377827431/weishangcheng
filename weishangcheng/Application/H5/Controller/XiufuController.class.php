<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 修复
 * 
 * @author lanxuebao
 *        
 */
class XiufuController extends CommonController
{
    public function index(){
        $user = $this->user('member.id, wx.headimgurl, member.mobile, member.agent_level');
        $old = M('member_bind_old')->where("openid='{$user['openid']}'")->count();
        
        $agent = $this->agentLevel($user["agent_level"]);
        
        $this->assign(array(
            'user'  => $user,
            'agent' => $agent,
            'cando' => $old == 0
        ));
        $this->display();
    }
    
    public function search(){
        $mobile = $_POST['mobile'];
        if(!is_numeric($mobile) || strlen($mobile) != 11){
            $this->error('请输入正确的手机号');
        }
        
        $user = $this->user('member.id, wx.headimgurl, member.mobile, member.agent_level, openid');
        
        $agent = $this->agentLevel();
        $Model = M();
        
        $sql = "SELECT member.id, member.pid, member.balance,member.agent_level,member.sex,member.nickname AS nick, 
                       wx.openid, wx.nickname,wx.headimgurl, member.reg_time, member.mobile,pmbr.mobile AS pmobile,pmbr.nickname AS pname,
                       wx.appid
                FROM member 
                INNER JOIN wx_user AS wx ON wx.mid=member.id
                LEFT JOIN member AS pmbr ON member.pid>0 AND pmbr.id=member.pid
                WHERE member.mobile='{$mobile}'
                GROUP BY member.id";
        $list = $Model->query($sql);
        
        $wxList = C('WXLIST');
        foreach($list as $i=>$item){
            if($item['id'] == $user['id'] || $item['openid'] == $user['openid']){
                unset($list[$i]);
                continue;
            }
            $list[$i]['app_name'] = $wxList[$item['appid']]['name'];
            $list[$i]['created'] = date('y年m月d日', $item['reg_time']);
            $list[$i]['agent_str'] = $agent[$item['agent_level']]['title'];
            $list[$i]['parent'] = '无上级';
            if($item['pid'] > 0){
                $list[$i]['parent'] = '上级'.substr($item['mobile'], 0, 3).'****'.substr($item['mobile'], -4);
            }
        }
        $this->ajaxReturn($list);
    }
    
    /**
     * 手机号短信验证
     */
    public function code(){
        $mobile = $_REQUEST['mobile'];
        #号码非数字报错
        if (!is_numeric($mobile) || strlen($mobile) != 11) {
            $this->error('请输入手机号');
        }
         
        // 判断上次验证码是否未过60秒
        $check = session("xiufu_mobile");
        $now = time();
        if (is_array($check) && $now < $check['time']) {
            $this->success('验证码已发送');
        }
    
        $uid = $this->user('id');
        #生成验证码，并存储
        $checknum = rand(100000, 999999);
        
        #发送短信验证码
        vendor('TopSDK.SMSSend');
        $res = FcSmsNumSend(array('id' => $uid), $mobile, $checknum);
        if (isset($res->result)) {
            session("xiufu_mobile", array('mobile'=>$mobile, "num" => $checknum, "time" => $now + 60));
            $this->success('验证码已发送，请注意查收');
        }else if ($res->code == 15 ){
             $this->error('发送验证码过于频繁');
        }else{
            $this->error('发送失败');
        }
    }
    
    /**
     * 绑定账号
     */
    public function bind(){
        $user = $this->user('id, openid, agent_level');
        
        if(!is_numeric($_POST['code']) 
            || strlen($_POST['code']) != 6
            || !is_numeric($_POST['mobile']) 
            || strlen($_POST['mobile']) != 11
            || !is_numeric($_POST['id']) ){
            $this->error('绑定失败，非法提交');
        }

       $Model = M('member');
       
       // 查找此人是否存在,是否被绑定过
       $member = $Model
                ->field("member.id, member.mobile")
                ->where("member.id=".$_POST['id'])
                ->find();
       
       if(empty($member) || $member['mobile'] != $_POST['mobile']){
           $this->error('手机账号不存在');
       }else if($member['mid'] == $user['id']){
           $this->error('您已绑定此ID，无需再次绑定');
       }
       
       $code = session('xiufu_mobile');
       if(empty($code['mobile']) || $_POST['code'] != $code['num'] || $code['mobile'] != $_POST['mobile']){
           $this->error('验证码错误');
       }
       
       $childrenStr = '';
       $children = $Model->query("SELECT id FROM member WHERE pid={$user['id']}");
       foreach($children as $item){
           $childrenStr .= $item['id'].',';
       }
       $childrenStr = rtrim($childrenStr, ',');
       
       $Model->startTrans();

       $now = date('Y-m-d H:i:s');
       $Model->execute("INSERT INTO member_bind_old(mid, uid, bind_date, openid, children) VALUES({$member['id']}, {$user['id']}, '{$now}', '{$user['openid']}', '{$childrenStr}')");
       
       // 绑定关系
       $Model->execute("UPDATE wx_user SET mid={$member['id']} WHERE openid='{$user['openid']}'");
       if($childrenStr != ''){
           $Model->execute("UPDATE member SET pid={$member['id']} WHERE pid='{$user['id']}'");
       }
       $Model->commit();
       session('xiufu_mobile', null);
       session('user.id', $member['id']);
       $this->success();
    }
}
?>