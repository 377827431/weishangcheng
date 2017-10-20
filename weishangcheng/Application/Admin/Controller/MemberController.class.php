<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Org\Wechat\WechatAuth;
use Common\Model\BaseModel;
/**
 * 会员管理
 * @author lanxuebao
 *
 */
class MemberController extends CommonController
{
    public function index(){
        $Model = M();
        if(!IS_AJAX){
            $project_id = session('user.project_id');
            if($_GET['mid']){
                $this->assign('mid',$_GET['mid']);
            }
            $appid="";
            $wxlist = $Model->query("select pr.name,pra.appid from project_appid as pra
                    INNER join project as pr on pr.alias = pra.alias where pra.id = ".$this->projectId);
            foreach ($wxlist as $i=>$item){
                $appid[$item['appid']] = $item;
            }
            $this->assign("appid",  $appid);
            $levels = $this->agentLevel();
            $this->assign("levels",$levels);
            $this->display();
        }
        $this->user();
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $wx_config = C('WEIXIN');
        $where = "";
        $project_id = session('user.project_id');
        $where .= " AND pm.project_id = '".$project_id."'";
        if($_GET['mid']){
            $where .= " AND m.id IN (".addslashes($_GET['mid']).")";
        }
        if(!empty($_GET['appid'])){
            $where .= " AND w_u.appid='".addslashes($_GET['appid'])."'";
        }
        if($_GET['name']){
            $where .= " AND m.name like '%".addslashes($_GET['name'])."%'";
        }
        if($_GET['nickname']){//昵称
            $where .= " AND w_u.nickname like '%".addslashes($_GET['nickname'])."%'";
        }
        if(is_numeric($_GET['mobile'])){//联系电话
            $where .= " AND m.mobile='".addslashes($_GET['mobile'])."'";
        }
        if(is_numeric($_GET['levels'])){//代理级别
            $where .= " AND card_id ='".$_GET['levels']."'";
        }
        if(is_numeric($_GET['city_id'])){//城市
            $where .= " AND m.city_id ='".$_GET['city_id']."'";
        }else if(is_numeric($_GET['province_id'])){
            $where .= " AND m.province_id ='".$_GET['province_id']."'";
        }
        if(is_numeric($_GET['subscribe'])){//关注
            $where .= " AND w_u.subscribe =".$_GET['subscribe'];
        }
        if($where != ''){
            $where = "WHERE ".ltrim($where, ' AND');
        }
        $sql = "SELECT
                    card.id as card_id,m.id,w_u.nickname,w_u.province,w_u.city,w_u.country,
                    FROM_UNIXTIME(pm.created, '%Y-%m-%d %H:%i') as created,pm.remark,
                    m.sex,m.mobile, m.wxno,
                    card.title,pm.pid,(pm.balance+pm.wallet) as balance
                FROM project_member AS pm 
                INNER JOIN member AS m ON pm.mid=m.id
                INNER JOIN wx_user AS w_u ON m.id=w_u.mid
                LEFT JOIN project_card AS card ON card.id=pm.card_id
                {$where} 
                ORDER BY m.id DESC
                LIMIT {$offset},{$limit}";
        $rows = $Model->query($sql);
        $MCity = D('City');
        $agentLevel = $this->agentLevel();
        foreach($rows as $k=>$v){
            $level = $agentLevel[$v['card_id']];
            $rows[$k]['agent_level'] = ($v['agent_level'] !== '')?$level['title']:'';
            $rows[$k]['name'] = $v['name'];
            $rows[$k]['city_name'] = $MCity->find($v['city_id']);
        }

        $total = count($rows);
        $total += $offset + ($total < $limit ? 0 : 50);
        $this->ajaxReturn(array(
            "total" => $total,
            "rows" => $rows
        ));
    }

    /**
     * 添加备注
     * @author jy
     */

    public function member_remark(){
//        $goods_id = $_REQUEST['goods_id'];
//        $tid      = $_REQUEST['tid'];
        $project_id = session('user.project_id');
        if(IS_GET){
            $id = I('get.id');
            if (!is_numeric($id)){
                return;
            }
            $res = M('project_member')->field('mid, remark')->where("project_id = {$project_id} AND mid = {$id}")->find();
            $this->assign('data', $res);
            $this->display();
        }
        $up = array(
            "remark" => I('post.remark'),
        );
        $mid = I('post.id');
        if (!is_numeric($mid)){
            return;
        }
        M('project_member')->where("mid = {$mid} AND project_id = {$project_id}")->save($up);
        $this->success('已保存！');
    }

    /**
     * 显示下级代理
     * @author zw
     */
    public function show_cm(){
        if($_GET['data'] != 'json'){
            $this->display();
        }
        $mid = I("get.mid" ,'', int);
        if (!$mid) //上级代理id
            $this->error('错误的用户编号！');
        $rowstotal = M()->query("select name,'上级代理' as pid,pc.title,mobile from member 
                                        inner join project_member as pm on pm.mid=member.id
                                        inner join project_card as pc on pc.id=pm.card_id where pm.mid =
                                        (select pid from project_member as pm where pm.mid  = ".$mid.")
                                UNION
                                select name,'上二级代理' as pid,pc.title,mobile from member 
                                        inner join project_member as pm on pm.mid=member.id
                                        inner join project_card as pc on pc.id=pm.card_id  where pm.mid =
                                        (select pid from project_member as pm where pm.mid  = 
                                        (select pid from project_member as pm where pm.mid  = ".$mid."))
                                UNION
                                select name,'上三级代理' as pid,pc.title,mobile from member  
                                        inner join project_member as pm on pm.mid=member.id
                                        inner join project_card as pc on pc.id=pm.card_id  where pm.mid =
                                        (select pid from project_member as pm where pm.mid  = 
                                        (select pid from project_member as pm where pm.mid  = 
                                        (select pid from project_member as pm where pm.mid  = ".$mid.")))
                                UNION
                                select name,'一级代理' as pid,pc.title,mobile from member   
                                        inner join project_member as pm on pm.mid=member.id
                                        inner join project_card as pc on pc.id=pm.card_id  where pm.pid  = ".$mid."
                                UNION
                                select name,'二级代理' as pid,pc.title,mobile from member   
                                        inner join project_member as pm on pm.mid=member.id
                                        inner join project_card as pc on pc.id=pm.card_id  where pm.pid in
                                        (select pm.mid from project_member as pm  where pm.pid  = ".$mid.")
                                UNION
                                select name,'三级代理' as pid,pc.title,mobile from member   
                                        inner join project_member as pm on pm.mid=member.id
                                        inner join project_card as pc on pc.id=pm.card_id  where pm.pid in
                                    (select pm.mid from project_member as pm  where pm.pid in
                                            (select pm.mid from project_member as pm where pm.pid  = ".$mid."))");
        $total = count($rowstotal);
        if($total == 0){
            $this->ajaxReturn(array(
                "total" => $total,
                "rows" => array()
            ));
        }
        $this->ajaxReturn(array(
            "total" => $total,
            "rows" => $rowstotal
        ));
    }
    
    
    /**
     * 资金流水
     * @author wangjing
     */
    public function balance_list(){
        $this->display();
    }
    
    /**
     * 修改等级
     * @author wangjing
     */
    public function change_level($id = 0){
        if(empty($id)){
            $this->error('修改项不能为空！');
        }
        $Model = M('member');
        if(IS_POST){
            $data = I("post.");
            if($data['agent_level'] == ''){
                $this->error('请选择代理级别!');
            }
            //查询代理信息
            $member = $Model->query("SELECT project_id,mid,card_id from project_member WHERE mid = ".$id);
            if(empty($member)){
                $this->error('请选择代理！');
            }
            $member = $member[0];
            //修改等级
            $card_expire = $Model->query("SELECT expire_time from project_card WHERE id = ".$data['agent_level']);
            if(empty($card_expire)){
                $this->error('代理等级有效时间不能为零！');
            }
            $card_expire = $card_expire[0]['expire_time'];
            $card_expire = 86400*$card_expire;
            $time = NOW_TIME;
            if($card_expire == 0){
                $card_expire = $time;
            }
            $result = $Model->query("update project_member set card_id =". $data['agent_level']." ,card_expire ='".$card_expire."' where mid =".$_GET['id'] );
            if($result === false){
                $this->error("修改失败！");
            }

            //保存修改日志
            $uid = $_GET['id'];
            $levelModel = D('LevelChange');
            $result = $levelModel->add($member, $uid, $data['agent_level'], 2);
            if($result < 0){
                $this->error($levelModel->getError());
            }
            $this->success('操作成功');
        }
        $card_id = $Model->query("SELECT card_id from project_member WHERE mid = ".$id);
        if(empty($card_id)){
            $this->error('会员卡不存在');
        }
        $card_id = $card_id[0]['card_id'];
        $levels = $this->agentLevel();
        $this->assign(array(
            'ids' => $id,
            'card_id'=> $card_id,
            'levels' => $levels
        ));
        $this->display();
    }
    
    /**
     * 设置为公司员工
     * @author wangjing
     */
    public function employee($id = 0){
        if(empty($id)){
            $this->error('修改项不能为空！');
        }
        $Model = M('member');
        if(IS_POST){
            $data = I("post.");
            $result = $Model->where("id IN(".$id.")")->save($data);
            if($result >= 0){
                $this->success("设置成功！");
            }else{
                $this->error("设置失败！");
            }
        }
        $this->assign(array(
            'ids' => $id
        ));
        $this->display();
    }
    
    /**
     * 员工关系调整页面
     */
    public function member_out(){
        $change_mid = $_REQUEST['mid'];
        if(!is_numeric($change_mid)){
            $this->error('mid不能为空');
        }
        
        if(IS_GET){
            $this->assign('change_mid', $change_mid);
            $this->display();
        }
        
        $Model = M('member');
        // 设置为无上级
        if($_POST['action'] == 'none'){
            $sql = "UPDATE member SET pid=0 WHERE id='{$change_mid}'";
            $Model->execute($sql);
            $this->success("修改成功！");
        }

        // 调整上下级关系
        if(!is_numeric($_POST['pid'])){
            $this->error('pid不能为空');
        }else if($change_mid == $_POST['pid']){
            $this->error('调整人和被调整人不能为同一个人');
        }

        $parent = $Model->find($_POST['pid']);
        if(empty($parent)){
            $this->error('pid不存在');
        }
        $member = $Model->find($change_mid);
        if(empty($member)){
            $this->error('mid不存在');
        }

        $sql = "";
        if($_POST['action'] == 'parent'){  // 将我的上级更改为pid
            if($member['pid'] == $parent['id']){
                $this->error('无需调整');
            }else if($parent['pid'] == $member['id']){
                $this->error('无法循环绑定');
            }
            
            $sql = "UPDATE member SET pid='{$_POST['pid']}' WHERE id='{$change_mid}'";
        }else if($_POST['action'] == 'child'){  // 将所有下级的上级改为pid
            $sql = "UPDATE member SET pid='{$_POST['pid']}' WHERE pid='{$change_mid}'";
        }
        
        $Model->execute($sql);
        $this->success("修改成功！");
    }
    
    /**
     * 修改下级的pid
     */
    private function change_pid(){
        $Model = M('member');
        if(!is_numeric($_POST['id']) || !is_numeric($_POST['change_mid'])){
            $this->error('非法数据！');
        }else if($_POST['id'] == $_POST['change_mid']){
            $this->error('不能把自己设为上级！');
        }
        
        //修改人
        $sourceMember = $Model->find($_POST['change_mid']);
        if(empty($sourceMember)){
            $this->error('数据不存在！');
        }
        
        if($sourceMember['pid'] == $_POST['id']){
            $this->success('修改成功');
        }
        
        //选中的父级
        $targetMember = $Model->find($_POST['id']);
        if(empty($targetMember)){
            $this->error('数据不存在！');
        }
        
        //验证 选中的父级 是否在 修改人的下线中 
        $is_my_child = $Model->query("SELECT isMyChild({$targetMember['id']},{$sourceMember['id']}) AS result");
        if($is_my_child[0]['result'] != 0){
            $this->error('调整的代理为选中的代理的下级，无法修改！');
        }
        
        //把修改人的pid 改成 选中的父级
        $Model->execute("UPDATE member SET pid=".$targetMember['id']." WHERE id=".$sourceMember['id']);
        
        $this->success("修改成功！");
    }
    
    /*
     * 补发积分
     */
    public function reissue_score(){
        if(!is_numeric($_REQUEST['id'])){
            $this->error('会员id不能为空');
        }
        
        $loginId = $this->user('id');
        $user = D('admin_user')->field('administrator')->find($loginId);
        $isAdministrator = $user['administrator'];
        if(IS_POST){
           $this->reissue($isAdministrator);
        }
        $this->assign('id', $_REQUEST['id']);
        $this->assign('isAdministrator', $isAdministrator);
        $this->display();
    }
    
    /**
     * 添加积分调用
     */
    public function reissue($isAdministrator){
        $uid = $this->user('id');
        $post = array(
            'mid'        =>$_POST['id'],
            'type'       =>$_POST['type'],
            'reason'     =>$_POST['reason'],
            'balance'    =>$isAdministrator ? $_POST['balance'] : 0,
            'no_balance' =>$_POST['no_balance'],
            'project_id' =>session('user.project_id'),
        );
        if(!is_numeric($post['mid'])){
            $this->error('会员ID不能为空');
        }
        if(empty($post['reason'])){
            $this->error('原因不能为空');
        }
        if(!is_numeric($post['balance']) || !is_numeric($post['no_balance'])){
            $this->error('请输入有效的金额');
        }
        if($post['balance']==0 && $post['no_balance']==0){
            $this->error('金额不能都为0');
        }
        if($post['balance'] > 200 || $post['no_balance'] > 200){
            $this->error('金额不能大于200');
        }
        
        $start_time = strtotime(date("Y-m-d 00:00:00"));
        $end_time = strtotime(date("Y-m-d 23:59:59"));
        $Model = new \Common\Model\BalanceModel();
        $exists = $Model->where("member_balance.created BETWEEN '".$start_time."' AND '".$end_time."' AND member_balance.mid=".$post['mid'])
                        ->find();
        if(!empty($exists)){
            $this->error('一天只能补发一次积分,请明天在添加');
        }
        $id = $Model->add($post);
//         $wxUser = $Model->getWXUserConfig($post['mid']);
//         if(!empty($wxUser['config'])){
//             $money = bcadd($post['balance'], $post['no_balance'], 2);
//             $WechatAuth = new WechatAuth($wxUser['config']['WEIXIN']);
//             $message = array(
//                 'template_id' => $wxUser['config']['WX_TEMPLATE']['TM00335'],
//                 'url' => $wxUser['config']['HOST'].'/h5/balance#record',
//                 'data' => array(
//                     'first'    => array('value' => '您有新积分'.($money>0 ? '到账' : '扣款').'，详情如下。', 'color' => '#173177'),
//                     'account'  => array('value' => '当前账号'),
//                     'time'     => array('value' => date('Y年m月d日 H:i')),
//                     'type'     => array('value' => $Model->balacne_type($post['type'])),
//                     'creditChange' => array('value' => $money>0 ? '增加' : '减少'),
//                     'number'       => array('value' => $money),
//                     'creditName'   => array('value' => '积分'),
//                     'amount'       => array('value' => '***'),
//                     'remark'       => array('value' => '人工补发：'.$post['reason'])
//                 )
//             );
            
//             $WechatAuth->sendTemplate($wxUser['openid'], $message);
//         }
        $this->success("保存成功");
    }
     
    /**
     * 推送消息
     */
    public function message($id = 0){
        if(IS_GET){
            $this->assign('id',$id);
            $this->display();
        }
        
        // 发送消息
        $this->send();
    }
    
    /**
     * 消息调用
     */
    private function send(){
        $id = addslashes($_POST['id']);
        if(empty($id)){
            $this->error('消息接收人ID不能为空');
        }
        
        $systemUrl = false;
//         if(!empty($_POST['url'])){
//             if(strpos('/', $_POST['url']) !== false){
//                 $systemUrl = true;
//             }else if(strpos('http', $_POST['url']) === false){
//                 $this->error('URL格式错误');
//             }
//         }

        $post = array(
            'first'        =>$_POST['first'],
            'keyword1'     =>$_POST['keyword1'],
            'keyword2'     =>$_POST['keyword2'],
            'url'          =>$_POST['url'],
            'remark'       =>$_POST['remark'],
        );
        $Model= new BaseModel();
        $sql = "SELECT wx_user.mid, wx_user.openid, wx_user.appid, wx_user.subscribe, wx_user.nickname, member.name AS `name`,  member.id
                FROM member
                INNER JOIN wx_user ON member.id=wx_user.mid
                WHERE wx_user.mid in ({$id}) AND wx_user.subscribe=1";
        $list = $Model->query($sql);
        $count = count($list);
        if($count == 0){
            $this->error('消息接收人不存在或未关注公众号');
        }
        
        
        header('Content-Type:application/json; charset=utf-8');
        echo json_encode(array('status' => 1, 'info' => '已开始发送，预计发送'.$count.'人'));
        ignore_user_abort(true);
        header('X-Accel-Buffering: no');
        header('Content-Length: '. strlen(ob_get_contents()));
        header("Connection: close");
        header("HTTP/1.1 200 OK");
        ob_end_flush();
        flush();
        
        set_time_limit(600);
        $wechatAuthlist = array();
        foreach ($list as $i=>$user){
            if(!isset($wechatAuthlist[$user['appid']])){
                $config = get_wx_config($user['appid']);
                $wechatAuthlist[$user['appid']] = array(
                    'config' => $config,
                    'wechatAuth' => new WechatAuth($config)
                );
            }

            $config = $wechatAuthlist[$user['appid']]['config'];
            $wechatAuth = $wechatAuthlist[$user['appid']]['wechatAuth'];
            $message = array(
                'template_id' => $config['template']['OPENTM401202033'],
                'url' => '',
                'data' => array(
                    'first'    => array('value' => $post['first'], 'color' => '#173177'),
                    'keyword1' => array('value' => $post['keyword1']),
                    'keyword2' => array('value' => $post['keyword2']),
                    'remark'   => array('value' => $post['remark'])
                )
            );
           $result = $wechatAuth->sendTemplate($user['openid'], $message);
        }
    }
}
?>