<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 资金流水
 * 
 * @author wangbaofu
 *        
 */
class BalanceController extends CommonController
{
    public function index(){
        $Model = D('Common/Balance');
        
        if(IS_AJAX){
            $where = array();
            if(is_numeric($_GET['mid'])){
                $where['m.id'] = $_GET['mid'];
            }else if(is_numeric($_GET['mobile'])) {
                $where['m.mobile'] = $_GET['mobile'];
            }
            
            if($_GET['status'] == 1){
                $where['b.money'] = array('gt',0);
            }else if($_GET['status'] == 2){
                $where['b.money'] = array('lt',0);
            }
            
            if($_GET['start_date'] != '' && $_GET['end_date'] == '')
                $where['b.create_time'] = array('egt', $_GET['start_date']);
            if($_GET['end_date'] != '' && $_GET['start_date'] == '')
                $where['b.create_time'] = array('elt', $_GET['end_date']);
            if($_GET['start_date'] != '' && $_GET['end_date'] != '')
                $where['b.create_time'] = array('between', array($_GET['start_date'] , $_GET['end_date']));
            
            if(strlen($_GET['type']) > 0) { $where['b.type'] = $_GET['type']; }
            
            $data = $Model->getAllBalance($where);
            
            $this->ajaxReturn($data);
        }
        
        $balacne_type = $Model->balacne_type();
        $this->assign('balacne_type',$balacne_type);
        
        $this->display();
    }
    
    public function stat ()
    {
        $date = strtotime(I('date',''));
        if ( $date > 0 )
        {
            $date = date('Y-m-d', $date);
        }else{
            $date = date('Y-m-d', NOW_TIME-86400);
        }
        if ( IS_AJAX )
        {
            $type_list = D('Common/Balance')->balacne_type();
            $data = M('member_balance')->field('IF(money>0,1,-1) as bp,type,sum(money) as money,count(*) as count')
                ->where(array('create_time' => array(array('gt', $date.' 00:00:00'),array('lt',$date.' 23:59:59')),array('mid'=>array('neq', '10000'))))->group('IF(money>0,1,-1),type')->select();
            foreach( $data as $key => $value )
            {
                $data[$key]['type_name'] = $type_list[$value['type']];
            }
            $this->ajaxReturn($data);
        }
        $this->assign('date', $date);
        $this->display();
    }
}