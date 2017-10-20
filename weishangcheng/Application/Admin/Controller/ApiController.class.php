<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Org\IdWork;
/**
 * 公共类
 *
 * @author lanxuebao
 */
class ApiController extends CommonController
{
    /**
     * 获取我的商品分组
     */
    public function tag(){
        $Module = M('mall_tag');
        $list = $Module->field("id, `name`")->where("visible=1")->select();
        $this->ajaxReturn($list);
    }
    
    /**
     * 获取店铺sku属性值
     */
    public function skutree(){
        $id = $_GET['id'];
        $shopId = $_GET['shop_id'];
        if(!is_numeric($id) || !is_numeric($shopId)){
            $this->error('参数错误');
        }
        
        $projectId = IdWork::getProjectId($shopId);
        $Module = M('mall_sku');
        $list = $Module->field("id, text")->where("project_id IN (0, {$projectId}) AND pid={$id}")->select();
        $list = array_kv($list);
        $this->ajaxReturn($list);
    }
    
    /**
     * 添加SKU属性值
     */
    public function addsku(){
        $data = array();
        $data['text'] = $_POST['text'];
        $data['pid'] = is_numeric($_POST['pid']) ? $_POST['pid'] : 0;
        $data['project_id'] = $this->user('project_id');
        
        $Module = M('mall_sku');
        $sku = $Module->where($data)->find();
        if(empty($sku)){
            $data['id'] = $Module->add($data);
        }else{
            $data = $sku;
        }
        
        $this->ajaxReturn($data);
    }
    
    public function getMemberBalance(){
        $mid = $_GET['mid'];
        $data = D('Common/Balance')->getAll($mid, $this->shop['id']);
        foreach ($data['rows'] as $i=>$item){
            $data['rows'][$i]['created'] = date('Y-m-d H:i:s',$item['created']);
        }
        $this->ajaxReturn($data);
    }
    
    public function qr(){
        $scene_id = $_GET['scene_id'];
        if(strlen($scene_id) < 2){
            exit('scene_id不能为空');
        }
        $auth = new \Org\Wechat\WechatAuth();
        $result = $auth->qrcodeCreate($scene_id);
        $imgUrl = $auth->showqrcode($result['ticket']);
        redirect($imgUrl, 0);
    }
    
    /**
     * 获取产品列表
     */
    public function products(){
        $data = D('Common/Goods')->getProduct($this->shop['id']);
        $this->ajaxReturn($data);
    }
    
    /**
     * 根据手机号搜用户
     */
    public function search_member(){
        $mobile = $_REQUEST['mobile'];
        if(!is_numeric($mobile)){
            $this->error('请输入手机号');
        }
        
        $where = "WHERE member.mobile=".$mobile;
        if(is_numeric($_REQUEST['noid'])){
            //$where .= " AND member.id!=".$_REQUEST['noid'];
        }
    
        $sql = "SELECT member.id, wx_user.nickname, member.mobile, member.nickname AS nick, agent.title AS agent_title, member.agent_level, wx_user.headimgurl
                FROM member
                INNER JOIN wx_user on wx_user.mid=member.id
                LEFT JOIN agent ON agent.`level`=member.agent_level
                {$where}
                GROUP BY member.id";
        $Model = M();
        $list = $Model->query($sql);
        $this->ajaxReturn($list);
    }
    
    /**
     * 获取单品代理分组
     */
    public function agent(){
        $projectId = IdWork::getProjectId($_GET['shop_id']);
        $Model = M();
        $list = $Model->query("SELECT id, title, items FROM agent_group WHERE project_id='{$projectId}'");
        
        foreach ($list as $i=>$agent){
            $agent['items'] = json_decode($agent['items'], true);
            $list[$i] = $agent;
        }
        $this->ajaxReturn($list);
    }
}
?>