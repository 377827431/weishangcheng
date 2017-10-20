<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 限时折扣
 * @author lanxuebao
 *
 */
class DiscountController extends CommonController{
    private $shopId;
    private $allShop;
    public $authRelation = array(
        'goods'       => 'index'
    );
    
    function __construct(){
        parent::__construct();
        $this->shopId = $this->user('shop_id');
        $this->allShop = \Common\Common\Auth::get()->validated('admin','shop','all');
    }
    
    public function index(){
        if(IS_AJAX){
            $this->showList();
        }
    
        $allShop = $this->shops();
        $this->assign(array(
            'allShop'     => $allShop
        ));
        $this->display();
    }
    
    private function showList(){
        $data = array('rows' => null, 'total' => 0);
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
    
        $where = "";
        if(!$this->allShop){
            $where = "WHERE shop_id={$this->shopId}";
        }else if(is_numeric($_GET['shop_id'])){
            $where = "WHERE shop_id={$_GET['shop_id']}";
        }
    
        $Model = M();
        $sql = "SELECT * FROM mall_zero {$where} LIMIT {$offset}, {$limit}";
        $list = $Model->query($sql);
        foreach ($list as $i=>$item){
            if(NOW_TIME < $item['start_time']){
                $item['status'] = '未开始';
            }else if(NOW_TIME > $item['end_time']){
                $item['status'] = '已结束';
            }else if($item['sold'] > 0 && $item['sold'] == $item['total']){
                $item['status'] = '已售罄';
            }else if($item['sold'] > $item['total']){
                $item['status'] = '已超售';
            }else{
                $item['status'] = '进行中';
            }
    
            $item['progress'] = (bcdiv($item['sold'], $item['total'], 4) * 100);
            $item['active_time'] = date('Y-m-d H:i:s', $item['start_time']).' 至 '.date('Y-m-d H:i:s', $item['end_time']);
    
            $list[$i] = $item;
        }
        $data['rows'] = $list;
        $data['total'] = count($list);
        $this->ajaxReturn($data);
    }
    
    /**
     * 查找商品
     */
    public function goods(){
        $goods = $this->getGoods($_GET['id'], $_GET['active']);
        $this->ajaxReturn($goods);
    }
    
    private function getGoods($id, $activeId = null){
        if(!is_numeric($id)){
            $this->error('商品ID不能为空');
        }
    
        $Model = new ActivityModel();
        $goods = $Model->canJoin($id, '102'.$activeId);
    
        if(!$goods){
            $this->error($Model->getError());
        }else if(!$this->allShop && $goods['shop_id'] != $this->shopId){
            $this->error('您无权编辑其他店铺的商品，请切换店铺后再试');
        }
    
        return $goods;
    }
    
    /**
     * 添加
     */
    public function add(){
        if(IS_POST){
            return $this->save();
        }
    
        $data = array(
            'goods_name'  => '请在左侧输入商品ID',
            'start_time'  => date('Y-m-d H', strtotime('+2 hour')).':00:00',
            'end_time'    => date('Y-m-d', strtotime('+30 day')).' 23:59:59',
            'min_order_quantity' => 1,
            'price_title' => '活动价',
            'main_tag'    => '零元购'
        );
        $this->assign('data', $data);
        $this->assign('canEdit', true);
        $this->assign('canChangeGoods', 1);
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
    
        $Model = new ActivityModel('mall_zero');
        $data = $Model->find($id);
        if(empty($data)){
            $this->error('活动不存在');
        }else if(!$this->allShop && $this->shopId != $data['shop_id']){
            $this->error('您无权修改他人店铺数据');
        }
    
        // 格式化数据
        $canEdit = NOW_TIME < $data['end_time'];
        $canChangeGoods = NOW_TIME < $data['start_time'];
        $data['products'] = json_decode($data['products'], true);
        $data['start_time'] = date('Y-m-d H:i:s', $data['start_time']);
        $data['end_time'] = date('Y-m-d H:i:s', $data['end_time']);
        $data['buy_quota'] = $data['buy_quota'] == 0 ? '' : $data['buy_quota'];
        $data['vsold'] = $data['vsold'] == 0 ? '' : $data['vsold'];
        if(IS_POST){
            if(!$canEdit){
                $this->error('活动已结束无法修改数据');
            }else{
                $this->save($data);
            }
        }
    
        $goods = $this->getGoods($data['goods_id'], $data['id']);
        $this->assign('data', $data);
        $this->assign('canEdit', $canEdit);
        $this->assign('goods', $goods);
        $this->assign('canChangeGoods', $canEdit && $canChangeGoods);
        $this->display();
    }
    
    /**
     * 保存活动信息
     */
    private function save($exists = null){
        $data = array(
            'title'      => $_POST['title'],
            'start_time' => strtotime($_POST['start_time']),
            'end_time'   => strtotime($_POST['end_time']),
            'pic_url'    => $_POST['pic_url'],
            'main_tag'   => $_POST['main_tag'],
            'price_title'=> $_POST['price_title'],
            'goods_id'   => $_POST['goods_id'],
            'detail'     => strlen($_POST['detail']) > 100 ? $_POST['detail'] : '',
            'created'    => date('Y-m-d H:i:s'),
            'creater'    => $this->user('username'),
            'price'      => 0,
            'score'      => 0,  // 如果设置了积分则变成积分商品
            'total'      => 0,
            'vsold'      => !is_numeric($_POST['vsold']) || $_POST['vsold'] < 1 ? 0 : $_POST['vsold'],
            'products'   => '',
            'buy_quota'  => $_POST['buy_quota'],
            'min_order_quantity' => $_POST['min_order_quantity'] < 1 ? 1 : $_POST['min_order_quantity'],
            'freight_id' => $_POST['freight_id']
        );
    
        if($data['start_time'] + 300 > $data['end_time']){
            $this->error('活动时间至少5分钟以上');
        }else if($data['end_time'] <= NOW_TIME){
            $this->error('活动结束时间不能低于现在时间');
        }
    
        $goods = $this->getGoods($data['goods_id'], $exists['id']);
        if(count($goods['products']) != count($_POST['product'])){
            $this->error('商品规格已变更，请重新编辑');
        }
    
        // 校验数据
        $products = array();
        foreach($_POST['product'] as $id=>$item){
            $products[$id] = array(
                'total' => $item['total'],
                'sold'  => !empty($exists['products'][$id]['sold'])? $exists['products'][$id]['sold'] : 0,
                'spec'  => $item['spec']
            );
    
            $data['total'] += $item['total'];
        }
    
        $data['products'] = json_encode($products, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
        $data['shop_id'] = $goods['shop_id'];
        if(mb_strlen($data['products'], 'UTF-8') > 3000){
            $this->error('商品规格信息过大，无法添加');
        }
    
        $activityId = 102;
        $Model = M('mall_zero');
        if($exists){
            $activityId .= $exists['id'];
            $result = $Model->where("id=".$exists['id'])->save($data);
        }else{
            $result = $Model->add($data);
            $activityId .= $result;
        }
    
        if($result > 0){
            if(!in_array(102, $goods['tag_id']) || $goods['activity_id'] != $activityId){
                $goods['tag_id'][] = 102;
                array_unique($goods['tag_id']);
                sort($goods['tag_id']);
                $Model->execute("UPDATE mall_goods SET tag_id='".implode(',', $goods['tag_id'])."', activity_id='{$activityId}' WHERE id=".$goods['id']);
            }
            $this->success();
        }
        $this->error('保存失败');
    }
    
    public function delete(){
        $id = $_POST['id'];
        if(empty($id)){
            $this->error('ID不能为空');
        }
        $id = addslashes($id);
        $sql = "SELECT mall_zero.id, mall_zero.goods_id, mall_zero.shop_id, mall_goods.tag_id,
        mall_zero.start_time, mall_zero.end_time
        FROM mall_zero
        LEFT JOIN mall_goods ON mall_goods.id=mall_zero.goods_id
        WHERE mall_zero.id IN ({$id})";
        $Model = M();
        $list = $Model->query($sql);
        if(empty($list)){
            $this->error('ID不存在');
        }
    
        foreach ($list as $item){
            if(!$this->allShop && $item['shop_id'] != $this->shopId){
                $this->error('您无权修改他人店铺数据');
            }else if(NOW_TIME - 300 > $item['start_time']){
                $this->error('距离活动开始5分钟后不再提供删除功能，请将商品下架至活动结束');
            }
    
            $tag = explode(',', $item['tag_id']);
            $index = array_search(1002, $tag);
            if($index > -1){
                unset($tag[$index]);
                $Model->execute("UPDATE mall_goods SET tag_id='".implode(',', $tag)."' WHERE id=".$item['goods_id']);
            }
            $Model->execute("DELETE FROM mall_zero WHERE id=".$item['id']);
        }
    
        $this->success('已删除');
    }
}
?>