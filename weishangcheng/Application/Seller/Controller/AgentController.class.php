<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/6/30
 * Time: 9:15
 */

namespace Seller\Controller;


class AgentController extends ManagerController
{
    public function index(){
        $this->display();
    }

    public function manager(){
        $title = I('get.title');
        if(IS_AJAX){
            $offset = I('get.offset', 0);
            $size = I('get.size', 20);
            $where = array();
            $where[] = "agent.shop_id={$this->shopId}";
            $where[] = "agent.status='1'";
            if(!empty($title)){
                $title = addslashes($title);
                $where[] = "(agent.mid = '{$title}' OR member.mobile = '{$title}' OR member.name like '{$title}%')";
            }
            switch ($_GET['status']){
                case 'created':
                    $order = ' ORDER BY agent.created DESC ';
                    break;
            }
            $Model = new \Common\Model\Agent();
            $list = $Model->agent_list($where, $order, $offset, $size);
            $this->ajaxReturn($list);
        }
        $this->assign('search_title', $title);
        $this->display();
    }

    public function delete(){
        if(IS_AJAX){
            $mid = I('post.id');
            $Model = new \Common\Model\Agent();
            $Model->delete($mid, $this->shopId);
            $this->ajaxReturn('1');
        }
    }

    public function detail(){
        $mid = I('get.id');
        if (!is_numeric($mid)){

        }
        $Model = new \Common\Model\Agent();
        $data = $Model->getDetail($mid, $this->shopId);
        $this->assign('data', $data);
        $this->display();
    }
}