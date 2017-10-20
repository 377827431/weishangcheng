<?php
namespace H5\Controller;
use Common\Common\CommonController;

/**
 * 我的赠品
 * @author 王宝福
 *
 */
class PresentController extends CommonController
{
    public function index(){
        if(IS_AJAX){
            $limit = I('get.size/d', 0);
            $offset = I('get.offset/d', 20);
             
            $mid = $this->user('id');
            $where = array();
            $where['trade.buyer_id'] = $mid;
            $where['trade.buyer_del'] = 0;
            $where['trade.pay_type'] = 'giveaway';
             
            $Model = D("Order");
            $data = $Model->getShortList($where, $limit, $offset);
             
            $this->ajaxReturn($data);
        }
        $this->display();
    }
}