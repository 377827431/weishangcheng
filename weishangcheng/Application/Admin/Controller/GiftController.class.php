<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 
 *
 */
class GiftController extends CommonController
{
    public function index(){
        if(IS_AJAX){
            $this->showList();
        }
        $this->display();
    }
    
    private function showList(){
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $shopId = session('user.shop_id');
        $where = array();
        if(!empty($_GET['title'])){
            $where[] = "title like '%".$_GET['title']."%'";
        }
        $where = count($where) > 0 ? 'and '.implode(' AND ', $where) : '';
        $data = array('total' => 100, 'rows' => array());
        $sql = "SELECT * FROM mall_gift where shop_id = {$shopId} {$where} LIMIT {$offset}, {$limit}";
        $Model=M();
        $list = $Model->query($sql);
        foreach ($list as $i=>$item){
        };
        $data['rows'] = $list;
        $data['total'] = count($Model->query("SELECT * FROM mall_gift"));
        if(!empty($data['rows'])){
            foreach ($data['rows'] as $i => $items){
                $data['rows'][$i]['start_time'] = date('Y-m-d',intval($items['start_time']));
                $data['rows'][$i]['end_time'] = date('Y-m-d',intval($items['end_time']));
            }
        }
        $this->ajaxReturn($data);
    }
    
    /**
     * 添加
     */
    public function add(){
        if(IS_POST){
            $this->save();
        }
        $data = array();
        $this->assign('data', $data);
        $this->assign('canEdit', true);
        $this->display('edit');
    }
    
    /**
     * 编辑
     */
    public function edit(){
        $id = $_REQUEST['id'];
        if(!is_numeric($id)){
            $this->error('ID不能为空');
        }
        $Model = M();
        $data = $Model->query("select * from mall_gift where id =".$id);
        if(empty($data)){
            $this->error('信息不存在');
        }
        foreach ($data as $i => $items){
            $data[$i]['start_time'] = date('Y-m-d',intval($items['start_time']));
            $data[$i]['end_time'] = date('Y-m-d',intval($items['end_time']));
        }
        $data = $data[0];
        if(IS_POST){
            $this->save($data);
        }
        $this->assign('data', $data);
        $this->assign('canEdit', true);
        $this->display('edit');
    }
    
    /**
     * 保存
     * @param unknown $exists
     */
    private function save($exists){
        $data = array(
            'shop_id' => session('user.shop_id'),
            'title' => $_POST['title'],
            'goods_id' => $_POST['goods_id'],
            'product_id' => '',
            'start_time' => strtotime($_POST['start_time']),
            'end_time' => strtotime($_POST['end_time']),
            'buy_quota' => $_POST['buy_quota'],
        );
        $Model = M('mall_gift');
        $shop_id = $Model->query("select shop_id from mall_goods where id=".$_POST['goods_id']);
        if(empty($shop_id)){
            $this->error('商品不存在');
        }
        if($shop_id[0]['shop_id'] != session('user.shop_id')){
            $this->error('不属于当前登录店铺');
        }
        if($exists){
            $result = $Model->where("id=".$exists['id'])->save($data);
        }else{
            $result = $Model->add($data);
        }
            $this->success('以保存');
    }
    
    /**
     * 删除
     */
    public function delete(){
        $id = $_POST['id'];
        if(empty($id)){
            $this->error('ID不能为空');
        }
        $id = addslashes($id);
        M()->query("DELETE from mall_gift where id in ($id)");
        $this->success('已删除');
    }
    /**
     * 导出订单
     */
    public function printOrder(){
        $Model = D('ExportFree');
        $title = $_GET['title'];
        $data = $Model->printFreeOrder($title);
        if($data === false){
            $this->error($Model->getError());
        }
        die;
    }
}
?>