<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/6/21
 * Time: 9:07
 */

namespace Admin\Controller;

use Common\Common\CommonController;


class ReminderController extends CommonController
{
    private $shopId;

    function __construct(){
        parent::__construct();

        $login = $this->user();
        $this->shopId = $login['shop_id'];
    }

    public function index(){
        if(IS_AJAX) {
            $OrderReminderType = \Common\Model\OrderReminderType::getAll();
            $offset = I('get.offset', 0);
            $limit = I('get.limit', 20);
            $model = M('trade_reminder');
            $count = $model
                ->field("count(*) as count")
                ->where(array('shop_id' => $this->shopId))
                ->limit($offset,$limit)
                ->find();
            $count = $count['count'];
            $data = $model
                ->field("id, tid, shop_id, name, type, created, status")
                ->where(array('shop_id' => $this->shopId))
                ->limit($offset,$limit)
                ->order('status desc, created desc')
                ->select();
            foreach ($data as $k => $v){
                $data[$k]['title'] = $OrderReminderType[$v['type']]['title'];
                if ($v['created'] > 0){
                    $data[$k]['created'] = date("Y-m-d H:i:s", $v['created']);
                }else{
                    $data[$k]['created'] = '';
                }
                if ($v['status'] == 2){
                    $data[$k]['status'] = '未处理';
                }else{
                    $data[$k]['status'] = '已处理';
                }
            }
            $this->ajaxReturn(array('rows' => $data, 'total' => $count));
        }
        $this->display();
    }

    public function delete(){
        if(IS_AJAX) {
            $id = I('post.id');
            if (is_numeric($id)){
                M('trade_reminder')
                    ->where(array('id' => $id, 'shop_id' => $this->shopId))
                    ->save(array('status' => 1));
            }
            $this->ajaxReturn('1');
        }
    }

}