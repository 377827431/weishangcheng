<?php
namespace H5\Controller;
use Common\Common\CommonController;
use Common\Model\BaseModel;
use Org\Wechat\WechatAuth;

/**
 * 销售额
 * @author lanxuebao
 *
 */
class TeamController extends CommonController
{
    public function index(){
        $Model = M();
        $id=$this->user('id');
        $agent=array(100012=>0,100013=>0,100014=>0,100015=>0,100016=>0,100017=>0,100018=>0);
        $number=$Model->query("SELECT count(*) as number,card_id FROM `project_member` WHERE pid=$id  GROUP BY card_id") ;
        $total=$Model->query("SELECT count(*) as total FROM `project_member` WHERE pid=$id");
        foreach ($number as $val){
            $agent[$val['card_id']]=$val['number'];
        }
        $agent[5]=$total[0]['total'];
        $shop = explode('/', $_SERVER['PHP_SELF']);
        $shop = $shop['2'];
        $this->assign('agent',$agent);
        $this->assign('shop',$shop);
        // 月份
        $this->assignMonth();
        $this->display();
    }
    
    /**
     * 月份
     */
    private function assignMonth(){
        $monthList = array();
        $year = $startYear = date('Y');
        $month = $startMonth = date('n');
        $endYear = 2016;
        $endMonth = 12;
        
        do{
            do{
                $monthList[$year.'-'.($month < 10 ? '0' : '').$month] = $year.'年'.$month.'月';
                if($year == $endYear && $month == $endMonth){
                    break;
                }
        
                $month--;
            }while ($month > 0);
        
            if($year == $endYear && $month == $endMonth){
                break;
            }
            $year--;
            $month = 12;
        }while ($year >= $endYear);
        
        $this->assign('monthList', $monthList);
    }
    
    public function sales(){
        $id=$this->user('id');
        if($_GET['month'] == ""){
            $BeginDate = date('Ym');
        }
        else{
            $BeginDate = $_GET['month'];
        }
        $startDate=date('Y-m-01 00:00:00', strtotime(date("$BeginDate")));
        $endDate = date('Y-m-d 23:59:59', strtotime("$startDate+1 month -1 day"));
        $timespan = strtotime($startDate);
        $endDate1 = strtotime($endDate);
        $timespan = strtotime('-1 day', $timespan);
        $startTid = date('Ymd', $timespan).'00000';
        $endTid = date('Ymd',$endDate1).'99999';
        $Model = M();
        
        //自己级下级交易金额
        $total=$Model->query("SELECT sum(payment+paid_balance) as total
            FROM trade
            INNER JOIN (
            SELECT * FROM project_member where pid={$id} or mid={$id}
            ) AS child ON child.mid=trade.buyer_id 
            where tid BETWEEN '{$startTid}' AND '{$endTid}' AND pay_time BETWEEN '{$startDate}' AND '{$endDate}'");
        //自己级下级好友充值金额
        $balance=$Model->query("SELECT sum(add_balance) as balance
            FROM member_balance 
            where mid in (SELECT id from project_member where pid=$id OR id=$id) 
            AND type='agent_tj' AND created BETWEEN '{$startDate}' AND '{$endDate}'");
       
        //下级人数
        $team_total=$Model->query("select count(pid) as team_total from project_member where pid=$id");
        
        //自己级下级退款金额
        $refund=$Model->query("SELECT sum(refund_fee+refund_post) as total
            FROM trade_refund 
            INNER JOIN mall_order AS child ON child.oid=trade_refund.refund_id
            INNER JOIN trade ON trade.tid=child.tid
            where trade.buyer_id in (SELECT id from project_member where pid=$id OR id=$id)
            AND trade_refund.refund_state='3' AND refund_modify BETWEEN '{$startDate}' AND '{$endDate}'");
        
        $data = array(
            'month' => str_replace('-', '年', $_GET['month']).'月',
            'trade' => $total[0]['total'],
            'agent' => $balance[0]['balance'],
            'total' => '',
            'next_txt' => '距离xx级还差(元)',
            'next_distance' => rand(1000, 5000),
            'team_total' => $team_total[0]['team_total'],
            'level' =>'',
            'refund' =>$refund[0]['total'],
        );
        if(empty($data['trade'])){
            $data['trade'] = "0";
        }
        if(empty($data['agent'])){
            $data['agent'] = "0";
        }
        if(empty($data['refund'])){
            $data['refund'] = "0";
        }
        $data['total'] = $data['trade'] + $data['agent'] - $data['refund'];
        if($data['total']<28000){
            $data['level'] = '1级-白银';
            $result=28000-$data['total'];
            $data['next_txt'] = '距离'.$data['level'].'级还差(元)';
            $data['next_distance'] = $result;
        }
        else if($data['total']>28000 && $data['total']<58000){
            $data['level'] = '2级-黄金';
            $result=58000-$data['total'];
            $data['next_txt'] = '距离'.$data['level'].'级还差(元)';
            $data['next_distance'] = $result;
        }
        else if($data['total']<158000 && $data['total']>58000){
            $data['level'] = '3级-铂金';
            $result=158000-$data['total'];
            $data['next_txt'] = '距离'.$data['level'].'级还差(元)';
            $data['next_distance'] = $result;
        }
        else if($data['total']>158000 && $data['total']<258000){
            $data['level'] = '4级-钻石';
            $result=258000-$data['total'];
            $data['next_txt'] = '距离'.$data['level'].'级还差(元)';
            $data['next_distance'] = $result;
        }
        else{
            $data['level'] = '4级-钻石';
            $result=$data['total']-258000;
            $data['next_txt'] = '超出'.$data['level'].'级(元)';
            $data['next_distance'] = $result;
        }
        $this->ajaxReturn($data);
    }
    
    /**
     * 我的好友
     */
    public function friends(){
        $agents = $this->agentLevel();
        $card_id = $_REQUEST['card_id'];
        $Model = M();
        $loginId = $this->user('id');
        $loginOpenId = $this->user('openid');
        if(!IS_AJAX){
            $sql = "SELECT member.id, pm.pid, member.name AS `name`, member.mobile, pm.card_id, wx_user.nickname, wx_user.headimgurl,wx_user.mid
                    FROM member, project_member as pm,wx_user
                    WHERE member.id={$loginId} AND wx_user.openid='{$loginOpenId}' AND pm.mid={$loginId}";
            $member = $Model->query($sql);
            $member = $member[0];
            $member['key'] = '我';
            if($member['pid'] > 0){
                $sql = "SELECT member.id, member.name AS `name`, member.mobile,  pm.card_id, wx_user.nickname, wx_user.headimgurl, wx_user.mid
                FROM member
                INNER JOIN project_member as pm on pm.mid = member.id
                INNER JOIN wx_user ON wx_user.mid=member.id
                WHERE member.id={$member['pid']}
                ORDER BY wx_user.subscribe DESC, wx_user.last_login DESC
                LIMIT 1";
                $parent = $Model->query($sql);
                if(!empty($parent)){
                    $member = $parent[0];
                    $member['key'] = '上级';
                }
            }
            $member['card_id'] = $agents[$member['card_id']]['title'];
            if(empty($member['card_id'])){
                $member['card_id'] = "游客";
            }
            $this->assign('my', $member);
            $this->assign('card_id',$card_id);
            $this->assign('agentList', json_encode($agents));
            $this->display();
        }
        $card = addslashes($_REQUEST['card']);
        $offset = is_numeric($_GET['offset']) ? $_GET['offset'] : 0;
        $size = is_numeric($_GET['size']) ? $_GET['size'] : 20;
        $pid = $this->user('id');
        $where = "pm.pid = $pid";
        if (is_numeric($card)){
            $where .= " and  pm.card_id=$card";
        }
        if(strlen($_GET['kw']) > 0){
            $where .= " AND member.name like '%".$_GET['kw']."%' OR wx.nickname like '%".$_GET['kw']."%'";
            if(preg_match('/^1[34578]\d{9}$/', $_GET['kw'])){
                $where .= " OR member.mobile='".$_GET['kw']."'";
            }
        }
        $sql = "SELECT * FROM
                (SELECT mbr.*, wx_user.nickname, wx_user.headimgurl
                    FROM
                    (
                        SELECT id, name, mobile, pm.card_id, IF(pm.card_id = 0, 99, pm.card_id) AS sort
                        FROM member
                        inner join project_member as pm on pm.mid=member.id
                        WHERE {$where}
                        ORDER BY sort
                        LIMIT {$offset}, {$size}
                    ) AS mbr
                    INNER JOIN wx_user ON wx_user.mid=mbr.id
                    ORDER BY wx_user.last_login desc
                ) AS wx_mbr
                GROUP BY id
                ORDER BY sort";
        $data = $Model->query($sql);
        foreach($data as $i=>$item){
            $data[$i]['agent_title'] = $agents[$item['card_id']]['title'];
        }
        $this->ajaxReturn($data);
    }
    
    /**
     * 邀请好友升级
     */
    public function invite(){
        $login = $this->user('member.name, member.mobile');
        $mid = $_GET['id'];
        $agentLevel = $_GET['agent_level'];
        if(!is_numeric($mid)){
            $this->error('好友id不能为空');
        }else if(!is_numeric($agentLevel) || $agentLevel == 0 || $agentLevel == 1){
            $this->error('被邀请级别错误');
        }

        $agents = $this->agentLevel();
        if(!isset($agents[$agentLevel])){
            $this->error('被邀请等级不存在:'.$agentLevel);
        }
        
        // 获取好友信息
        $Model = new BaseModel();
        $wxUser = $Model->getWXUserConfig($mid);
        if(empty($wxUser) || $wxUser['subscribe'] != 1 || empty($wxUser['config'])){
            $this->error('无法邀请：被邀请人未关注公众号');
        }
        
        $message = array(
            'template_id' => $wxUser['config']['WX_TEMPLATE']['OPENTM401202033'],
            'url' => $wxUser['config']['HOST'].'/h5/pay/rule',
            'data' => array(
                'first'    => array('value' => $login['nickname'].'邀请您升级', 'color' => '#173177'),
                'keyword1'  => array('value' => '待升级'),
                'keyword2'     => array('value' => date('Y年m月d日 H:i')),
                'remark'       => array('value' => '升级等级：'.$agents[$agentLevel]['title'])
            )
        );
        
        $wechatAuth = new WechatAuth($wxUser['config']['WEIXIN']);
        $result = $wechatAuth->sendTemplate($wxUser['openid'], $message);
        if($result['errcode'] != 0){
            $this->error('邀请失败:'.$result['errmsg']);
        }
        $this->error('已发送邀请！');
    }
}
?>