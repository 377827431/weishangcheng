<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 资金流水
 *
 * @author wangbaofu
 *
 */
class FeedbackController extends CommonController
{
    private $access;
    function __construct(){
        parent::__construct();
        $this->access = \Common\Common\Auth::get()->validated('admin','feedback','search_all');
    }
    public function index(){
        $start_date = I('get.start_date', date('Y-m-d 00:00:00', strtotime('-1 day')));//下单时间  开始
        $end_date = I('get.end_date', date('Y-m-d 23:59:59'));//下单时间  结束
        $title    = $_GET['title'];
        $user_id     = $_GET['user_id'];
        $where = array();
        
        if(IS_AJAX){
            if($this->access == false){
                $userId = $this->user('id');
                $where['feedback.user_id'] = $userId;
            }
            if($start_date != '' && $end_date == '')
                $where['feedback.created'] = array('egt', $start_date);
            if($end_date != '' && $start_date == '')
                $where['feedback.created'] = array('elt', $end_date);
            if($start_date != '' && $end_date != '')
                $where['feedback.created'] = array('between', array($start_date , $end_date));
            if($title != '')
                $where['g.title'] = array('like', '%'.$title.'%');
            if($user_id != '')
                $where['feedback.user_id'] = array('eq', $user_id);
            
            $data = D('FeedBack')->getAll($where);
            $this->ajaxReturn($data);
        }
        
        $Model = M();
        if($this->access == true){
            $users = $Model->query("SELECT id,nick,username FROM admin_user");
            $this->assign('users',$users);
        }
        
        $this->assign(array(
            'search' => array('start_date' => $start_date,'end_date' => $end_date),
            'access' => $this->access
        ));
        $this->display();
    }
    
    public function delete(){
        if(IS_POST){
            $model = M("mall_goods_feedback");
            $id = $_POST['id'];
            if(empty($id)){
                $this->error("ID 错误,请稍后重试");
            }
            $model->where("id IN ({$id})")->delete();
            $this->success();
        }
    }
}