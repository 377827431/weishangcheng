<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Common\Model\SettlementType;
use Think\Cache\Driver\Redis;
use Common\Model\ProjectConfig;

/**
 * 会员卡认证
 * 
 */
class CertificationController extends CommonController
{
    public function index(){
        $projectId = $this->user('project_id');
        if(IS_AJAX){
            $list = M('transfers_auth')->query("select ta.id,ta.status,ta.card_name,ta.card_no,ta.shop_id,au.nick,ta.modify 
                from transfers_auth as ta inner join admin_user as au on au.id = ta.id where ta.project_id= {$projectId}");
            foreach ($list as $i=>$item){
                $list[$i]['status'] = $item['status'] == 0 ? '待审核' : ($item['status'] == 1 ? '通过' : '未通过');
                $list[$i]['modify'] = date('Y-m-d H:i:s',$item['modify']);
            }
            $this->ajaxReturn($list);
        }

        $this->display();
    }
    
    public function  edit(){
        $Id = addslashes($_GET['id']);
        $project_id = $this->projectId;
        $Model = M('transfers_auth');
        if(IS_POST){
            $Id = $_REQUEST['id'];
            $status = addslashes($_POST['status']);
            $time = strtotime(date('Y-m-d H:i:s'));
            $data = $Model->query("UPDATE transfers_auth set status ={$status},modify={$time} where id = {$Id}");
            $this->success('已保存','index');
        }
        $data = $Model->query("select * from transfers_auth as ta inner join admin_user as au on au.id = ta.id where ta.project_id= {$project_id} and ta.id ={$Id}");
        if(empty($data)){
            $this->error('id不存在');
        }
        $data = $data[0];
        $this->assign('data', $data);
        $this->display();
    }
}
?>