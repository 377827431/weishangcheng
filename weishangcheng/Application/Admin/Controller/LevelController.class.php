<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 调级记录
 * 
 * @author wangjing
 *        
 */
class LevelController extends CommonController
{
    protected $authRelation = array('search' => 'change');
    
    public function index(){
        $access = \Common\Common\Auth::get()->validated('admin','level','search_all');
        $start_date = I('get.start_date', date('Y-m-d 00:00:00', strtotime('-1 day')));//下单时间  开始
        $end_date = I('get.end_date', date('Y-m-d 23:59:59'));//下单时间  结束
        $usersId    = $_GET['uid'];
        
        if(IS_AJAX){
            $where = array();
            if($access == false){
                $where['cl.username'] = $this->user('id');
            }else if(is_numeric($usersId)){
                $where['users.id'] = $usersId;
            }
            
            if($start_date != '' && $end_date == '')
                $where['cl.created'] = array('egt', $start_date);
            if($end_date != '' && $start_date == '')
                $where['cl.created'] = array('elt', $end_date);
            if($start_date != '' && $end_date != '')
                $where['cl.created'] = array('between', array($start_date , $end_date));
            
            $data = D('LevelChange')->getAll($where);
            $this->ajaxReturn($data);
        }
        
        if($access == true){
            $users = M('admin_user')->field('id,nick,username')->select();
            $this->assign('users',$users);
        }
        
        $this->assign(array(
            'search' => array('start_date' => $start_date,'end_date' => $end_date),
            'access' => $access
        ));
        $this->display();
    }
    
    /**
     * 导出
     */
    public function printdetail(){
        $uid = $this->user('id');
        D('LevelChange')->export($uid);
    }
    
    /**
     * 调级
     */
    public function change(){
        if(!IS_POST){
            $this->display();
        }
        
        $uid = $this->user('id');
        $Model = D('LevelChange');
        $result = $Model->change($_POST['mid'], $_POST['level'], $uid);
        if($result < 0){
            $this->error($Model->getError());
        }
        
        $this->success();
    }
    
    /**
     * 根据手机号搜索会员
     */
    public function search(){
        $model = new \Admin\Model\LevelChangeModel();
        $list = $model->search($_GET['mobile']);
        $this->ajaxReturn($list);
    }
}

?>