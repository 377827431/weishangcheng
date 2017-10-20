<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 众筹拼团
 *
 * @author lanxuebao
 *
 */
class GrouponController extends CommonController
{
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
        $sql = "SELECT * FROM mall_groupon {$where} LIMIT {$offset}, {$limit}";
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
        if($goods['is_del']){
            $this->error('商品不存在');
        }
        $this->ajaxReturn($goods);
    }
    
    private function getGoods($id, $activeId = 0){
        if(!is_numeric($id)){
            $this->error('商品ID不能为空');
        }
        $activeId = ActivityType::GROUPON.$activeId;
        $Model = M();
        $goods = $Model->query("SELECT id, title, shop_id, stock, tag_id, active_id FROM mall_goods WHERE id={$id} AND is_del=0");
        $goods = $goods[0];
        if(empty($goods)){
            $this->error('商品不存在');
        }else if(!$this->allShop && $goods['shop_id'] != $this->shopId){
            $this->error('您无权编辑其他店铺的商品');
        }else if($goods['active_id'] > 0 && $activeId != $goods['active_id']){
            $this->error('此商品正在参加其他活动');
        }
        
        $goods['product'] = array();
        $products = $Model->query("SELECT id, sku_json, stock, agent3_price, agent2_price, price, cost FROM mall_product WHERE goods_id=".$goods['id']);
        foreach ($products as $product){
            $product['spec'] = get_spec_name($product['sku_json']);
            unset($product['sku_json']);
        
            $product['price1'] = bcmul($product['agent3_price'], 0.98, 2);
            $product['price2'] = bcmul($product['agent3_price'], 0.95, 2);
            $product['price3'] = $product['agent2_price'];
            $goods['product'][$product['id']] = $product;
            
            if($product['spec'] != ''){
                $goods['hasSku'] = true;
            }
        }
        $goods['tag_id'] = explode(',', $goods['tag_id']);
        return $goods;
    }
    
    /**
     * 添加
     */
    public function add(){
        if(IS_POST){
            $this->save();
        }
        
        $data = array(
            'goods_name' => '请在左侧输入商品ID',
            'single'     => 0,
            'start_time' => '',
            'end_time'   => ''
        );
        $this->assign('data', $data);
        $this->assign('canEdit', true);
        $this->display('edit');
    }
    
    public function edit(){
        $id = $_REQUEST['id'];
        if(!is_numeric($id)){
            $this->error('ID不能为空');
        }

        $Model = M('mall_groupon');
        $data = $Model->find($id);
        if(empty($data)){
            $this->error('活动不存在');
        }else if(!$this->allShop && $this->shopId != $data['shop_id']){
            $this->error('您无权修改他人店铺数据');
        }
        
        $canEdit = NOW_TIME < $data['start_time'];
        
        if(IS_POST){
            if(!$canEdit){
                $this->error('活动开始后无法修改数据');
            }else{
                $this->save($data);
            }
        }
        
        // 格式化数据
        $data['price_range'] = json_decode($data['price_range'], true);
        $data['start_time'] = date('Y-m-d H:i:s', $data['start_time']);
        $data['end_time'] = date('Y-m-d H:i:s', $data['end_time']);
        $data['quota'] = $data['quota'] == 0 ? '' : $data['quota'];
        $goods = $this->getGoods($data['goods_id'], $data['id']);
        $data['goods_name'] = $goods['title'];

        $this->assign('data', $data);
        $this->assign('goods', $goods);
        $this->assign('canEdit', $canEdit);
        $this->display();
    }
    
    private function save($exists){
        $data = array(
            'id'    => $exists['id'],
            'title' => $_POST['title'],
            'start_time' => strtotime($_POST['start_time']),
            'end_time' => strtotime($_POST['end_time']),
            'pic_url' => $_POST['pic_url'],
            'quota' => $_POST['quota'],
            'single' => $_POST['single'],
            'tag_name' => $_POST['tag_name'],
            'tag_short' => $_POST['tag_short'],
            'goods_id' => $_POST['goods_id'],
            'detail' => strlen($_POST['detail']) > 100 ? $_POST['detail'] : '',
            'created' => date('Y-m-d H:i:s'),
            'username' => $this->user('username'),
            'price'    => 0,
            'total'    => 0,
            'sold'    => 0,
            'price_range' => ''
        );
        
        if($data['start_time'] >= $data['end_time']){
            $this->error('活动开始时间不能大于结束时间');
        }else if($data['end_time'] <= NOW_TIME){
            $this->error('活动结束时间不能小于当前时间');
        }else if($data['end_time'] - $data['start_time'] < 1800){
            $this->error('活动时间不能低于30分钟');
        }
        
        $goods = $this->getGoods($data['goods_id'], $exists['id']);
        if(count($goods['product']) != count($_POST['product'])){
            $this->error('商品规格已变更，请重新编辑');
        }
        
        // 校验数据
        $priceRange = array();
        foreach($_POST['product'] as $productId=>$item){
            if(!isset($goods['product'][$productId])){
                $this->error('商品规格已变更，请重新编辑');
            }
            $item['sold'] = 0;
            $item['spec'] = $goods['product'][$productId]['spec'];
        
            foreach ($item['range'] as $num=>$price){
                if(!is_numeric($price) || $price < 0.01){
                    $this->error($goods['product'][$productId]['spec'].' 不能小于0.01');
                }
        
                // 最低价
                if($price < $data['price'] || $data['price'] == 0){
                    $data['price'] = $price;
                }
            }
        
            $data['total'] += $num;
            $priceRange[$productId] = $item;
        }
        
        $data['price_range'] = json_encode($priceRange, JSON_UNESCAPED_UNICODE);
        $data['shop_id'] = $goods['shop_id'];
        if(mb_strlen($data['price_range'], 'utf8') > 1000){
            $this->error('商品规格信息过大，无法添加');
        }
        
        $Model = M('mall_groupon');
        if($exists){
            $result = $Model->where("id=".$exists['id'])->save($data);
        }else{
            $result = $data['id'] = $Model->add($data);
        }
        
        if($result > 0){
            if(!in_array(1001, $goods['tag_id'])){
                $goods['tag_id'][] = 1001;
                sort($goods['tag_id']);
            }
            $goods['tag_id'] = implode(',', $goods['tag_id']);
            $Model->execute("UPDATE mall_goods SET tag_id='{$goods['tag_id']}', active_id='{$data['id']}' WHERE id=".$goods['id']);
            $this->success();
        }else{
            $this->error('添加失败');
        }
    }
    
    public function delete(){
        $id = $_POST['id'];
        if(empty($id)){
            $this->error('ID不能为空');
        }
        $id = addslashes($id);
        $sql = "SELECT mall_groupon.id, mall_groupon.goods_id, mall_groupon.shop_id, mall_goods.tag_id,
                    mall_groupon.start_time, mall_groupon.end_time
                FROM mall_groupon
                LEFT JOIN mall_goods ON mall_goods.id=mall_groupon.goods_id
                WHERE mall_groupon.id IN ({$id})";
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
            $index = array_search(1001, $tag);
            if($index > -1){
                unset($tag[$index]);
                $Model->execute("UPDATE mall_goods SET tag_id='".implode(',', $tag)."' WHERE id=".$item['goods_id']);
            }
            $Model->execute("DELETE FROM mall_groupon WHERE id=".$item['id']);
        }
        
        $this->success('已删除');
    }
}
?>