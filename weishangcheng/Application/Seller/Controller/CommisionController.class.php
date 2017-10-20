<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/5/22
 * Time: 9:34
 */

namespace Seller\Controller;
use Common\Model\ProjectConfig;
use Org\Wechat\WechatAuth;

class CommisionController extends ManagerController
{
    //WHOLE_SHOP_REWARD
    public function index(){
        $projectId = substr($this->shopId, 0, -3);
        if (IS_AJAX){
            $data = array(
                'settlement_type' => intval($_POST['settlement_type']),
                'agent_rate'      => floatval($_POST['agent_rate']),
            );
            M('project_card')->where("id BETWEEN {$projectId}1 AND {$projectId}9")->save($data);
            project_config($projectId, ProjectConfig::WHOLE_SHOP_REWARD, json_encode($data));
            $this->ajaxReturn('1');
        }
        $key = ProjectConfig::WHOLE_SHOP_REWARD;
        $data = M('project_config')->where("project_id='{$projectId}' AND `key`='{$key}'")->find();
        if (!empty($data)){
            $card = json_decode($data['val'], true);
        }else{
            $card = M('project_card')
                ->field('agent_rate, settlement_type')
                ->where("id BETWEEN {$projectId}1 AND {$projectId}9")
                ->order('id')
                ->find();
            if (empty($card)){
                $card = array(
                    'agent_rate' => 0,
                    'settlement_type' => 0
                );
            }
        }
        $this->assign('card', $card);
        $this->display();
    }

    //商品佣金设置 h5卖家端
    public function goods_save(){
        $projectId = substr($this->shopId, 0, -3);
        $key = ProjectConfig::WHOLE_SHOP_REWARD;
        $data = M('project_config')->where("project_id='{$projectId}' AND `key`='{$key}'")->find();
        if (!empty($data)){
            $card = json_decode($data['val'], true);
        }else{
            $card = M('project_card')
                ->field('agent_rate, settlement_type')
                ->where("id BETWEEN {$projectId}1 AND {$projectId}9")
                ->order('id')
                ->find();
            if (empty($card)){
                $card = array(
                    'agent_rate' => 0,
                    'settlement_type' => 0
                );
            }
        }
        $goodsId = I('post.id');
        if (empty($goodsId) || !is_numeric($goodsId)){
            $this->error('商品id非法');
        }
        $comission = I('post.comission');
        if (empty($comission) || !is_numeric($comission)){
            $this->error('佣金非法');
        }
        if (IS_AJAX){
            if($_POST['reward_type'] == -1){
                M('agent_goods')->delete($goodsId);
            }else{
                $reward_value = array(
                    'o' => array(
                        0 => array(
                            '1_o' => $comission,
                            '2_o' => 0
                        )
                    )
                );
                $data = array(
                    'id'              => $goodsId,
                    'reward_type'     => 0,
                    'settlement_type' => $card['settlement_type'],
                    'reward_value'    => json_encode($reward_value, JSON_NUMERIC_CHECK)
                );
                M('agent_goods')->add($data, null, true);
            }
            $this->ajaxReturn('1');
        }
    }
    //点击推广员
    public function promoters(){
        $this->display('good_commision');
    }
    //推广员佣金设置
    public function promoters_set(){
        $projectId = substr($this->shopId, 0, -3);
        if (IS_AJAX){
            //关闭二级奖励
            if($_POST['reward2'] == 'false'){
                $_POST['agent_rate2'] = 0;
            }
            $data = array(
                'settlement_type' => intval($_POST['settlement_type']),
                'agent_rate'      => floatval($_POST['agent_rate']),
                'agent_rate2'      => floatval($_POST['agent_rate2']),
            );
            M('project_card')->where("id BETWEEN {$projectId}1 AND {$projectId}9")->save($data);
            //推广员招募
            $data['recruit_open'] = $_POST['recruit']=='true'?1:0;
            //推广员审核
            $data['check_open'] = $_POST['check']=='true'?1:0;
            project_config($projectId, ProjectConfig::WHOLE_SHOP_REWARD, json_encode($data));
            $this->ajaxReturn('1');
        }
        $key = ProjectConfig::WHOLE_SHOP_REWARD;
        $data = M('project_config')->where("project_id='{$projectId}' AND `key`='{$key}'")->find();
        if (!empty($data)){
            $card = json_decode($data['val'], true);
        }else{
            $card = array(
                'agent_rate' => 0,
                'agent_rate2' => 0,
                'settlement_type' => 0,
                'recruit_open' => 0,
                'check_open' => 0,
            );
        }
        $this->assign('card', $card);
        $this->display('poster_cfg');
    }
    //推广员招募
    public function promoters_recruit(){
        $projectId = substr($this->shopId,0,-3);
        $key = ProjectConfig::WHOLE_SHOP_REWARD;
        $data = M('project_config')->where("project_id='{$projectId}' AND `key`='{$key}'")->find();
        $card = json_decode($data['val'], true);
        if($card['recruit_open'] == '0'){
            //未开启推广员招募
            $this->error('请开启推广员招募');
        }else{
            $this->display('join');
        }
    }
    //推广员招募——招募文案
    public function promoters_recruit_copywriter(){
        if(IS_AJAX){
            //保存
            $data = array(
                'recruit_title' => $_POST['recruit_title'],
                'recruit_content' => $_POST['recruit_content']
                );
            M('shop_info')->where('id='.$this->shopId)->save($data);
            if($_POST['zhuanfa'] == 1){
                $projectId = substr($this->shopId,0,-3);
                $redirect = parse_url(C('PROTOCOL').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
                 $data = array(
                    'link' => $redirect['scheme'].'://'.$redirect['host'].'/seller/share?id='.$this->shopId,//分享地址
                    );
                $this->ajaxReturn($data);
            }else{
                $this->ajaxReturn('1');
            }
            
        }
        $recruit = M('shop_info')->field('recruit_title,recruit_content')->where('id='.$this->shopId)->find();
        $this->assign('recruit',$recruit);
        // $projectId = substr($this->shopId,0,-3);
        // // $appid = M('project_appid')->where("id = {$projectId}")->find();
        // // $appid = $appid['appid'];
        // // $config = get_wx_config($appid);
        // // $wx = new WechatAuth($config);
        // // $jssdk_config = $wx->getSignPackage();
        // // $this->assign('pack',json_encode($jssdk_config));
        // $redirect = parse_url('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        // $share_data = array(
        //     // 'title' =>'推广员招募',
        //     'link' => $redirect['scheme'].'://'.$redirect['host'].'/seller/share?id='.$this->shopId,//分享地址
        //     // 'imgUrl' =>'',
        //     // 'desc' =>'推广员招募',
        //     );
        // $this->assign('share_data',$share_data);
        $this->display('join_text');
    }
    //推广员招募——审核信息——申请人列表
    public function promoters_recruit_check(){
        $title = I('get.title','');
        if(IS_AJAX){ 
            $offset = I('get.offset', 0);
            $size = I('get.size', 20);
            
            $where = array();
            $where[] = "agent.shop_id={$this->shopId}";
            if(!empty($title)){
                $title = addslashes($title);
                $where[] = "(agent.mid = '{$title}' OR member.mobile = '{$title}' OR member.name like '{$title}%')";
            }
            
           switch ($_GET['status']){
                case 'created':
                    $order = ' ORDER BY agent.created DESC ';
                    break;
                case 'all':
                    $order = ' ORDER BY agent.created DESC ';
                    break;
                case 'passed':
                    $where[] = "agent.status=1";
                    $order = ' ORDER BY agent.created DESC ';
                    break;
                case 'unpassed':
                    $where[] = "agent.status<>1";
                    $order = ' ORDER BY agent.created DESC ';
                    break;
            }
            $list = $this->agent_list($where, $order, $offset, $size);
            foreach ($list as $key => $value) {
                if(empty($value['pname'])){
                    $list[$key]['pname'] = '无';
                }
            }
            $this->ajaxReturn($list);
        }
        $this->assign('search_title', $title);
        $this->display('review');
    }
    public function agent_list($where, $order, $offset = 0, $limit = 20){
        $where = implode(' AND ', $where);

        $sql = "SELECT agent.mid,agent.status, agent.shop_id, agent.created, member.name, project_member.pid, member.head_img, member.mobile  
                FROM agent
                INNER JOIN member ON agent.mid = member.id 
                INNER JOIN project_member ON (project_member.mid = agent.mid AND project_member.project_id = SUBSTR(agent.shop_id, 1, LENGTH(agent.shop_id) - 3))  
                WHERE {$where} 
                {$order}
                LIMIT {$offset}, {$limit}";
                // print_data($sql);
        $agentList = M()->query($sql);
        return $this->agentListHandle($agentList);
    }
    private function agentListHandle($agentList){
        $pids = array();
        foreach ($agentList as $key => $value){
            if ($value['pid'] > 0){
                $pids[] = $value['pid'];
            }
        }
        if (!empty($pids)){
            $pids = implode(', ', $pids);
            $sql = "SELECT id, name  
                FROM member 
                WHERE `id` IN ({$pids})";
            $memberList = M()->query($sql);
        }
        $id2name = array();
        foreach ($memberList as $key => $value){
            $id2name[$value['id']] = $value['name'];
        }
        foreach ($agentList as $key => $value){
            if ($value['pid'] > 0){
                $agentList[$key]['pname'] = $id2name[$value['pid']];
            }else{
                $agentList[$key]['pname'] = "无";
            }
            $agentList[$key]['created'] = date("Y-m-d H:i:s", $value['created']);
        }
        return $agentList;
    }
    //推广员招募——审核信息——通过
    public function promoters_recruit_check_adopt(){
        if(IS_AJAX){
            $post = I('post.');
            if(empty($post)){
                $this->error('未传递参数');
            }else{
                $shop_id = $this->shopId;
                $mid = $post['mid'];
                if(!is_numeric($mid)){
                    $this->error('参数错误');
                }
                $agent = M('agent')->where("mid = {$mid} AND shop_id = {$shop_id} AND status = 0")->find();
                if(empty($agent)){
                    $this->error('该申请已处理');
                }else{
                    M('agent')->where("mid = {$mid} AND shop_id = {$shop_id} AND status = 0")->save(array('status'=>'1'));
                    //如果是推广员邀请，在推广员的推荐人数加一
                    $project_id = substr($this->shopId,0,-3);
                    $project = M('project_member')->where(array('mid'=>$mid,'project_id'=>$project_id))->find();
                    if(!empty($project)){
                        if($project['pid']!=0){
                            //推广员邀请
                            $agent_up = M('agent')->where(array('mid'=>$project['pid'],'status'=>1,'shop_id'=>$this->shopId))->find();
                            $pnum = $agent_up['pnum']+1;
                            M('agent')->where(array('mid'=>$project['pid'],'status'=>1,'shop_id'=>$this->shopId))->save(array('pnum'=>$pnum));
                        }
                    }
                    $this->ajaxReturn('1');
                }
            }
        }
    }
    //推广员招募——审核信息——拒绝
    public function promoters_recruit_check_refuse(){
        if(IS_AJAX){
            $post = I('post.');
            if(empty($post)){
                $this->error('未传递参数');
            }else{
                $shop_id = $this->shopId;
                $mid = $post['mid'];
                if(!is_numeric($mid)){
                    $this->error('参数错误');
                }
                $agent = M('agent')->where("mid = {$mid} AND shop_id = {$shop_id} AND status = 0")->find();
                if(empty($agent)){
                    $this->error('该申请已处理');
                }else{
                    M('agent')->where("mid = {$mid} AND shop_id = {$shop_id} AND status = 0")->save(array('status'=>'2'));
                    $this->ajaxReturn('1');
                }
            }
        }
    }
}