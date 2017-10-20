<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 充值记录
 * 
 * @author wangbaofu
 *        
 */
class RechargeController extends CommonController
{
    public $authRelation = array(
        'export'   => 'admin.recharge.index'
    );
    
    public function index(){
        if(IS_AJAX){
            $Model = D('MemberRecharge');
            $data = $Model->getAll();
            $this->ajaxReturn($data);
        }
        
        $this->assign(array(
            'start_date' => date('Y-m-d 00:00:00', strtotime('-1 day')),
            'end_date'   => date('Y-m-d 23:59:59')
        ));
        $this->display();
    }
    
    /**
     * 导出
     */
    public function export(){
        D('MemberRecharge')->export();
    }
}

?>