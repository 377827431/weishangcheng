<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 客服评价
 * @author Hua
 */
class EvaluateController extends CommonController
{
    
    /**
     * 列表
     */
    public function index(){
        $weixin = M('kefu_list')->field('weixin,id')->select();
        if(IS_AJAX){
            $offset = I('get.offset', 0);
            $limit = I('get.limit', 50);
            $where = array();
            $Model = M('kefu_evaluate');
            $start = $_GET['start_date'];
            $end = $_GET['end_date'];
            
            if(is_numeric($_GET['attitude'])){
                $where[] = "kefu_evaluate.attitude='".addslashes($_GET['attitude'])."'";
            }
            if(is_numeric($_GET['weixin'])){
                $where[] = "kefu_evaluate.kf_id='".addslashes($_GET['weixin'])."'";
            }
            if($start!='' && $end!=''){
                $where[] = "kefu_evaluate.created between '".$start."' AND '".$end."'";
                $date1 = date_create($start);
                $date2 = date_create($end);
                $diff = date_diff($date1,$date2);
                $date = str_replace('+','',$diff->format("%R%a"));
                if($date>31){
                    $this->error('只能查询一个月以内的');
                }
            }
            $where = implode(' AND ', $where);
            $data = array('total' => 0, 'rows' => array());
            $data['total'] = $Model->where($where)->count();
            if($data['total']>0){
                $data['rows'] = $Model->where($where)->limit($offset,$limit)->select();
            }
            $this->ajaxReturn(array(
                "total" => $data['total'],
                "rows" => $data['rows'],
            ));
        }
        $this->assign(array(
            'start_date' => date('Y-m-d 00:00:00', strtotime('-1 day')),
            'end_date'   => date('Y-m-d 23:59:59'),
            'weixin'    => $weixin,
        ));
        $this->display();
    }
    
}
?>