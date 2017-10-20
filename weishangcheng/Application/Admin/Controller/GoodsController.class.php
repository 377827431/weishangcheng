<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Admin\Model\GoodsModel;
use Common\Model\CategoryModel;
use Org\IdWork;

/**
 * 商品
 *
 * @author 兰学宝
 */
class GoodsController extends CommonController
{
    private $shopId;
    private $all_shop;
    function __construct(){
        parent::__construct();
        $this->shopId = $this->user('shop_id');
        
        $this->all_shop = $this->authAllShop();
        
        if(!IS_AJAX && IS_GET){
            $sort_access = \Common\Common\Auth::get()->validated('admin','goods','saveSort');
            $this->assign('sort_access', $sort_access);
        }
    }
    
    /**
     * 出售中的商品
     *
     * @author lanxuebao
     */
    public function index(){
    	$where = array(
    		'shop_id' => $this->shopId,
    		'tag' => I('get.tag'),
    		'title' => $_GET['title'],
    		'action'  => 'index',
    		'sort'    => $_GET['sort'],
    		'order'    => $_GET['order']
    	);
        $this->showGoodsList($where);
    }
    
    private function showGoodsList($_where){
        if(IS_AJAX){
            $Model = D('Goods');
            $data = $Model->getProductList($_where);
            $this->ajaxReturn($data);
        }

        //查询商品分组
        $Module = M("mall_tag");
        $goods_tag = $Module->select();

        $shop = $this->shops();
        $this->assign(array(
            'goods_tag' => $goods_tag,
            'shop'     => $shop,
            'all_shop' => $this->all_shop
        ));
        if(!empty($_GET['tag'])){
            $this->assign("tag", $_GET['tag']);
        }
        
        
        $column_field = array();
        if(ACTION_NAME == 'no_display'){
            $column_field = array('field' => 'takedowns', 'label' => '下架时间');
        }else{
            $column_field = array('field' => 'created', 'label' => '创建时间');
        }
        $this->assign('search', $_where);
        $this->assign('column_field', $column_field);
        $this->display('index');
    }
    
    /**
     *  已售罄的商品
     *  @author lanxuebao
     */
    public function soldout(){
        $where = null;
        if(IS_AJAX){
            $where = array(
                'shop_id' => $this->shopId,
                'tag' => I('get.tag'),
                'title' => $_GET['title'],
                'action'  => 'soldout',
                'sort'    => $_GET['sort'],
                'order'    => $_GET['order']
            );
        }
        $this->showGoodsList($where);
    }
    
    /**
     * 仓库中的商品
     * @author lanxuebao
     */
    public function no_display(){
        $where = null;
        if(IS_AJAX){
            $where = array(
                'shop_id' => $this->shopId,
                'tag' => I('get.tag'),
                'title' => $_GET['title'],
                'action'  => 'no_display',
                'sort'    => $_GET['sort'],
                'order'    => $_GET['order']
            );
        }
        $this->showGoodsList($where);
    }
    
    /**
     * 删除
     */ 
    public function delete(){
        $id = I('post.id');
        $Model = new GoodsModel;
        $result = $Model->deleteById($id, $this->shopId);
        if($result < 0){
            $this->error($Model->getError());
        }
        $result = $Model->query("SELECT cat_id FROM mall_goods WHERE id=".$id);
        if(empty($result)){
            $this->error('分组不能为空');
        }
        $result = $result[0];
        // 计算类目下产品个数
        $this->updateCategoryQuantity($result['cat_id'],'');
        $this->updateTagQuantity($result['tag_id'],'');
        $this->success('删除成功！','index');
    }
    /**
     * 更新类目下产品数量
     */
    private function updateCategoryQuantity($catId, $add){
        $quantity = 'goods_quantity'.($add ? '+1' : '-1');
        $list = M()->query("SELECT id, parent_ids FROM mall_category WHERE id=$catId");
        foreach($list as $item){
            $parents = $item['parent_ids'] ? $item['parent_ids'].','.$item['id'] : $item['id'];
            M()->execute("UPDATE mall_category SET goods_quantity={$quantity} WHERE id IN ({$parents})");
        }
    }
    
    /**
     * 更新分组下产品数量
     */
    private function updateTagQuantity($tagId, $add){
        if(!$tagId){return;}
        $quantity = 'goods_quantity'.($add ? '+1' : '-1');
        $list = $this->query("SELECT id, parent_ids FROM mall_tag WHERE id IN ($tagId)");
        foreach($list as $item){
            $parents = $item['parent_ids'] ? $item['parent_ids'].','.$item['id'] : $item['id'];
            $this->execute("UPDATE mall_tag SET goods_quantity={$quantity} WHERE id IN ({$parents})");
        }
    }
    
    /**
     * 批量上架
     */ 
    public function takeUp(){
        $ids = I('post.ids');
        $Model = D('Goods');
        $result = $Model->takeUp($ids, $this->shopId);
        if($result < 0){
            $this->error($Model->getError());
        }
        $this->success('已上架');
    }
    
    /**
     * 批量下架
     * @author zhanghaipeng
     */ 
    public function takeDown(){
        $ids = I('post.ids');
        $Model = new \Admin\Model\GoodsModel();
        $result = $Model->takeDown($ids, $this->shopId);
        if($result < 0){
            $this->error($Model->getError());
        }
        $this->success('已下架');
    }
   
    /**
     * 修改分组
     * @author lanxuebao
     */
    public function saveTag(){
        $goodsId = $_REQUEST['id'];
        if(!is_numeric($goodsId)){
            $this->error('请选择单个商品');
        }
        
        $Model = M('mall_goods');
        $goods = $Model->find($goodsId);
        if(empty($goods)){
            $this->error('商品ID不存在');
        }
        $goods['tag_id'] = explode(',', $goods['tag_id']);
        if(IS_POST){
            if(count($_POST['tag_id']) > 3){
                $this->error('最多可选3个分组');
            }
            $tagList = array();
            foreach ($_POST['tag_id'] as $tagId){
                if($tagId < 10000){
                    $tagList[] = $tagId;
                }
            }
            foreach ($_POST['tag_id'] as $tagId){
                if(!in_array($tagId, $tagList) && is_numeric($tagId)){
                    $tagList[] = $tagId;
                }
            }
            $Model->execute("UPDATE mall_goods SET tag_id='".implode(',', $tagList)."' WHERE id=".$goods['id']);
            $this->success("修改分组成功");
        }
        
        //查询商品分组
        $project_id = session('user.project_id');
        $tagList = $Model->query("SELECT * FROM mall_tag WHERE project_id={$project_id}");
        $this->assign(array(
            'tagList' => $tagList,
            'goods'   => $goods
        ));
        $this->display();
    }
    
    /**
     * 会员折扣
     * @author lanxuebao
     */
    public function discount(){
        $id = I('post.id');
        $join = I('post.join');
        $Model = D('Goods');
        $result = $Model->discount($id, $join);
        if($result < 0){
            $this->error($Model->getError());
        }
        $this->success($join ? '参与会员折扣成功' : '已取消参与会员折扣');
    }
    
    /**
     * 入库出库
     */
    public function storage(){
        $Model = D('Goods');
        if(IS_POST){
            $products = $_POST['products'];
            $goodsId = $_POST['id'];
            $stock = $Model->updateProducts($goodsId, $products);
            $this->success(array('stock' => $stock));
        }
        $id = I('get.id');
        $goods = $Model->getById($id);
        unset($goods['detail']);
        $this->assign('goods', $goods);
        $this->display();
    }
    
    /**
     * 复制商品 (复制商品  分组及商品下产品的数据)
     * @author lanxuebao
     */
    public function copy(){
        $id = I('post.id');
        if(empty($id)){
            $this->error("商品ID错误");
        }
        $Model = new GoodsModel('Goods');
        $result = $Model->copy_goods($id);
        if($result < 0){
            $this->error($Model->getError());
        }
        
        $this->success("复制成功");
    }
    
    /**
     * 商品入库
     */
    public function editStocks(){
        $model_product = M("mall_product");
        $model_goods = M("mall_goods");
        if(IS_POST){
            $data_type = $_POST['data_type'];
            if($data_type == 1){
                $stocks = $_POST['stocks'];
                if(empty($stocks)){
                    $this->error('商品库存未填写');
                }else if(!is_numeric($stocks)){
                    $this->error('商品库存格式不对,请重新输入');
                }
                $product_id = $_POST['product_id'];
                $goods_id = $_POST['goods_id'];
                
                $sql_product = "update mall_product set stock=IF(stock + {$stocks} > 0, stock + {$stocks}, 0)where id={$product_id};";
                $model_product->execute($sql_product);
                $sql_goods = "update mall_goods set stock=IF(stock + {$stocks} > 0, stock + {$stocks}, 0)where id={$goods_id};";
                $model_goods->execute($sql_goods);
                
                $this->success();
            }else if($data_type == 2){
                $goods_id = $_POST['goods_id'];
                $product_ = $_POST['products'];
                //处理    每个产品的库存的加减
                foreach($product_ as $key => $value){
                    if(is_numeric($value['stock']) && $value['stock'] != 0){
                        $product_id = $value['id'];
                        $stocks = $value['stock'];
                        
                        $sql_product = "update mall_product set stock=IF(stock + {$stocks} > 0, stock + {$stocks}, 0)where id={$product_id};";
                        $model_product->execute($sql_product);
                    }
                }
                //处理    商品的库存的加减
                $goods_stock = $model_product->where("goods_id = %d",$goods_id)->sum("stock");//所有商品下的产品的库存和
                $data['stock'] = $goods_stock;
                $model_goods->where("id = %d",$goods_id)->save($data);
                
                $this->success();
            }
        }
        //查询商品下面的产品是否含有商品规格
        $id = I('get.goods_id');
        if(empty($id)){
            $this->error("商品ID错误");
        }
        $list = $model_product->where("goods_id = %d",$id)->find();
        if(empty($list['sku_json'])){
            //查询商品信息
            $goods['data_type'] = 1;//代表无规格
            $goods['stock'] = $list['stock'];//库存
            $goods['price'] = $list['price'];//价格
            $goods['outer_id'] = $list['outer_id'];//库存
            $goods['goods_id'] = $id;
            $goods['product_id'] = $list['id'];
            $this->ajaxReturn($goods);
        }else{
            $data = $this->Model->find($id);
            $products = $this->Model->query("SELECT * FROM mall_product WHERE goods_id={$id} ORDER BY id");
            $goods = array(
                'sku_json'  => array(), 
                'cat_id'    => $data['cat_id']
            );
            $goods['data_type'] = 2;//代表有规格
            $goods['sku_json'] = json_decode($data['sku_json'], true);
            $goods['product']['products'] = $products;
            $goods['goods_id'] = $id;
            $goods['pay_type'] = $data['pay_type'];
            $this->ajaxReturn($goods);
        }
    }
    
    private function assingEdit(&$goods){
        $projectId = IdWork::getProjectId($goods['shop_id']);
        //获取商品类目信息
        $Model = new CategoryModel($projectId);
        $categorys = $Model->getAll();
        $this->assign('categorys', $categorys);
        $this->assign('categoryClass', 'full-screen');
        
        // 不是淘系商品
        if(empty($goods['tao_id'])){
            $list = D('Sku')->getAll($projectId);
            $this->assign('sku_list', $list);
        }

        // 会员卡
        $memberCards = $Model->query("SELECT id, title FROM project_card WHERE id BETWEEN '{$projectId}0' AND '{$projectId}9'");
        array_unshift($memberCards, array('id' => 0, 'title' => '非本会员'));
        $this->assign('memberCards', $memberCards);
    }
    
    /**
     * 添加商品
     */
    public function add(){
        $myShopId = $this->user('shop_id');
        // 默认数据
        $data = array(
            'shop_id'    => $myShopId,
            'tao_id'     => is_numeric($_GET['tao_id']) ? $_GET['tao_id'] : 0,
            'cat_id'     => '',
            'tag_id'     => '',
            'is_virtual' => 0,
            'title'      => '',
            'price_type' => 0,
            'price'      => '',
            'original_price' => '',
            'cost'       => '',
            'images'     => '',
            'pic_url'    => '',
            'sku_json'   => '',
            'products'   => array(),
            'stock'      => 0,
            'weight'     => '',
            'outer_id'   => '',
            'buy_quota'  => 0,
            'day_quota'  => 0,
            'every_quota'=> 0,
            'level_quota'=> '',
            'tag_quota'  => '',
            'min_order_quantity' => 1,
            'is_display' => 0,
            'sold_time'  => 0,
            'sub_stock'  => 0,
            'member_discount' => 0,
            'freight_id' => '',
            'invoice'    => 0,
            'warranty'   => 0,
            'returns'    => 1,
            'score'      => 0,
            'digest'     => '',
            'send_place' => '',
            'remote_area'=>'',
            'parameters' => '',
            'detail'     => '',
            'custom_price' => ''
        );
        
        if(IS_POST){
            $goods = $this->postGoods($data);
            $Model = new GoodsModel();
            $result = $Model->add($goods);
            if($result > 0){
                $this->success('添加成功');
            }
            $this->error('添加失败：'.$Model->getError());
        }
        
        $data = $this->parseEditGoods($data);
        if($data['tao_id'] > 0){
            $data = $this->mergeTaoGoods($data);
        }
        
        $this->assingEdit($data);
        $this->assign('data', $data);
        $this->display('edit');
    }
    
    /**
     * 合并1688商品
     */
    private function mergeTaoGoods($goods){
        $Model = new \Common\Model\AlibabaModel();
        $tokenId = $Model->getTokenId($goods['shop_id']);
        if(!$tokenId){
            $this->error('店铺未绑定1688账号');
        }
        
        $aliGoods = $Model->syncGoods($goods['tao_id'], '3027680123', true,$tokenId);
        if($aliGoods['status'] != 'published'){
            $this->error('1688商品未上架，无法编辑');
        }
        
        $category = $Model->query("SELECT `name`, parent_ids FROM alibaba_category WHERE id='{$aliGoods['cat_id']}'");
        if($category[0]){
            $list = array();
            if($category[0]['parent_ids']){
                $parents = $Model->query("SELECT `name` FROM alibaba_category WHERE id IN ({$category[0]['parent_ids']}) ORDER BY level LIMIT 2");
                $list[] = $parents[0]['name'];
                $list[] = $parents[1]['name'];
            }
            
            $list[] = $category[0]['name'];
            $goods['cat_tip'] = '(建议：'.implode('→', $list).')';
        }
        
        $products = array();
        foreach ($goods['products'] as $i=>$item){
            $localId = IdWork::convertSKU($item['sku_json']);
            $products[$localId] = $item; 
        }
        
        // 通用数据覆盖
        $goods['title'] = $aliGoods['subject'];
        $goods['price'] = $aliGoods['retailprice'] > 0 ? $aliGoods['retailprice'] : $aliGoods['price'];
        $goods['cost'] = $aliGoods['cost'];
        $goods['weight'] = $aliGoods['weight'];
        $goods['stock'] = $aliGoods['stock'];
        $goods['append_freight_id'] = array('id' => 'T'.$aliGoods['freight_id'], 'name' => '__1688默认模板__', 'describe' => '1688模板：与您在1688网站或1688手机端看到的运费模板一致；建议您选择1688默认模板，以免供应商更换运费模板导致您损失！');
        if(!$goods['id']){
            $goods['freight_id'] = 'T'.$aliGoods['freight_id'];
        }
        $goods['sku_json'] = $aliGoods['sku_json'] ? json_decode($aliGoods['sku_json'], true) : array();
        $goods['images'] = json_decode($aliGoods['images'], true);
        $goods['parameters'] = array();
        $attributes = json_decode($aliGoods['attributes'], true);
        foreach ($attributes as $item){
            $goods['parameters'][] = array($item['name'], implode('、', $item['value']));
        }
        $goods['detail'] = $aliGoods['detail'];
        
        // 区间价
        $rangePrice = $aliGoods['price_range'] ? json_decode($aliGoods['price_range'], true) : array();
        if(count($rangePrice) > 1){
            $goods['price_type'] = 1;
            $goods['custom_price'] = $rangePrice;
        }else{
            $goods['price_type'] = 0;
            $goods['custom_price'] = $rangePrice;
        }
        
        $aliGoods['products'] = $aliGoods['products'] ? json_decode($aliGoods['products'], true) : array();
        if($aliGoods['products']){
            $goods['products'] = array();
            foreach ($aliGoods['products'] as $i=>$item){
                $product = null;
                $localId = IdWork::convertSKU($item['sku_json']);
                if(isset($products[$localId])){
                    $product = $products[$localId];
                }else{
                    $product = array(
                        'price'    => $item['price'],
                        'score'    => 0,
                        'cost'     => $item['price'],
                        'sku_json' => $item['sku_json'],
                        'outer_id' => '',
                        'weight'   => $goods['weight'],
                        'original_price' => ''
                    );
                }

                $product['custom_price'] = $rangePrice;
                $product['weight'] = $goods['weight'];
                $product['cost'] = $item['price'];
                $product['stock'] = $item['stock'];
                $goods['products'][] = $product;
            }
        }
        
        if($aliGoods['relation'] == 1){ // 大市场
            $goods['min_order_quantity'] = $aliGoods['min_order_quantity'];
        }else if($aliGoods['relation'] == 2){ // 一件代发
            $goods['price'] = $aliGoods['daixiao_price'];
            $goods['cost'] = $aliGoods['daixiao_price'];
        }else if($aliGoods['relation'] == 3){ // 微供
            
        }else{
            $this->error('未知1688商品类型，无法添加');
        }
        
        return $goods;
    }
    
    private function getGoods($id){
        $sql = "SELECT goods.id, goods.shop_id, goods.cat_id, goods.tag_id, goods.is_virtual, goods.title, goods.price, goods.score,
                    goods.stock, goods.outer_id, goods.cost, goods.custom_price, goods.member_discount, goods.min_order_quantity,
                    goods.hide_stock, goods.sub_stock, goods.buy_quota, goods.day_quota, goods.every_quota, goods.level_quota,
                    goods.tag_quota, goods.is_display, goods.sold_time, goods.freight_id, goods.tao_id, goods.invoice, goods.warranty,
                    goods.returns, goods.extend_param, content.images, content.sku_json, content.send_place, content.remote_area,
                    content.template_id, content.parameters, content.digest, content.detail, goods.weight, goods.pic_url, goods.original_price
                FROM mall_goods AS goods
                INNER JOIN mall_goods_content AS content ON content.goods_id=goods.id
                INNER JOIN mall_goods_sort AS sort ON sort.id=goods.id
                WHERE goods.id=".$id;
        $Model = M();
        $goods = $Model->query($sql);
        if(!$goods){
            $this->error('商品ID不存在');
        }
        $goods = $goods[0];
        
        // 查找产品
        $goods['products'] = $Model->query("SELECT id, stock, price, score, cost, custom_price, weight, outer_id, sku_json, original_price FROM mall_product WHERE goods_id=".$goods['id']);
        // 解析商品
        $goods = $this->parseEditGoods($goods);
        return $goods;
    }
    
    /**
     * 添加/编辑商品处理页面显示的内容
     */
    private function parseEditGoods($goods){
        $goods['price_type'] = 0;
        $goods['images'] = $goods['images'] ? explode(',', $goods['images']) : array();
        $goods['custom_price'] = $goods['custom_price'] ? json_decode($goods['custom_price'], true) : '';
        $goods['level_quota'] = $goods['level_quota'] !== '' ? explode(',', $goods['level_quota']) : array();
        $goods['parameters'] = $goods['parameters'] ? json_decode($goods['parameters'], true) : array();
        
        if($goods['sold_time'] > 0){
            $goods['sold_time'] = date('Y-m-d H:i:s', $goods['sold_time']);
        }
        
        if($goods['tag_id']){
            $tags = array();
            $goods['tag_id'] = explode(',', $goods['tag_id']);
            foreach ($goods['tag_id'] as $i=>$tagId){
                switch ($tagId){
                    case 201:   // 区间价
                        $goods['price_type'] = 1;
                        break;
                    case 202:  // 会员价
                        $goods['custom_price'][0] = floatval($goods['price']);
                        ksort($goods['custom_price']);
                        $goods['price_type'] = 2;
                        break;
                    case 203: // 积分价
                        $goods['custom_price'][0] = array(floatval($goods['price']), $goods['score']);
                        ksort($goods['custom_price']);
                        $goods['price_type'] = 3;
                        break;
                    case 204: // 单品代理
                        $goods['price_type'] = 4;
                        break;
                }
                
                if($tagId > 1000){
                    $tags[] = $tagId;
                }
            }
            $goods['tag_id'] = $tags;
        }else{
            $goods['tag_id'] = array();
        }

        if($goods['sku_json']){
            $goods['sku_json'] = json_decode($goods['sku_json'], true);
            foreach ($goods['products'] as $i=>$item){
                $item['sku_json'] = json_decode($item['sku_json'], true);
                if($goods['price_type'] > 0){
                    $item['custom_price'] = json_decode($item['custom_price'], true);
                }
                
                if($goods['price_type'] == 2){// 会员价
                    $item['custom_price'][0] = floatval($item['price']);
                    ksort($goods['custom_price']);
                }else if($goods['price_type'] == 3){// 积分价
                    $item['custom_price'][0] = array(floatval($item['price']), $item['score']);
                    ksort($goods['custom_price']);
                }
                
                $goods['products'][$i] = $item;
            }
        }else{
            $goods['sku_json'] = array();
        }
        
        if($goods['cost'] == 0){
            $goods['cost'] = '';
        }
        return $goods;
    }
    
    /**
     * 编辑
     */
    public function edit($id){
        if(!is_numeric($id)){
            $this->error('商品ID不能为空');
        }
        $goods = $this->getGoods($id);
        if(IS_GET){
            if($goods['tao_id'] > 0){
                $goods = $this->mergeTaoGoods($goods);
            }
            $this->assingEdit($goods);
            $this->assign('data', $goods);
            $this->display('edit');
        }
        
        $goods = $this->postGoods($goods);
        $Model = new GoodsModel();
        $result = $Model->update($goods);
        
        if($result >= 0){
            $this->success('已保存');
        }
        $this->error('保存失败：'.$Model->getError());
    }
    
    /**
     * 添加/编辑商品POST数据
     */
    private function postGoods($old){
        if($old['shop_id'] != $_POST['shop_id']){
            $this->error('店铺ID不相同：您已切换店铺，请刷新页面后再编辑商品');
        }
        
        // 旧的产品id
        $delProductIds = array();
        foreach ($old['products'] as $product){
            if($product['id'] && is_numeric($product['id'])){
                $delProductIds[] = $product['id'];
            }
        }

        $goods = $old;
        // 覆盖新数据
        foreach ($goods as $field=>$value){
            if(isset($_POST[$field])){
                $goods[$field] = $_POST[$field];
            }
        }
        
        //if(!$goods['freight_id']){ $this->error('请选择运费模板');}

        // 删除旧的类目id
        if($old['cat_id'] && intval($old['cat_id']) != intval($goods['cat_id'])){
            $goods['del_cat_id'] = $old['cat_id'];
        }

        // 解析产品
        if($goods['sku_json'] && $goods['products']){
            $skuJosn = json_decode($goods['sku_json'], true);
            $skuImg = array();
            foreach ($skuJosn[0]['items'] as $item){
                if($item['img']){
                    $skuImg[$item['id']] = $item['img'];
                }
            }
            
            $products = json_decode($goods['products'], true);
            if(!$products){
                $this->error('解析SKU失败');
            }
            
            $goods['products'] = array();
            foreach ($products as $item){
                $skuJosn = json_decode($item['sku_json'], true);
                if(isset($skuImg[$skuJosn[0]['vid']])){
                    $item['pic_url'] = $skuImg[$skuJosn[0]['vid']];
                }else{
                    $item['pic_url'] = '';
                }
                
                $product = array(
                    'stock'    => $item['stock'],
                    'price'    => $item['price'],
                    'score'    => $goods['price_type'] != 3 ? $goods['score'] : $item['score'],
                    'cost'     => $item['cost'],
                    'weight'   => $item['weight'],
                    'outer_id' => $item['outer_id'],
                    'sku_json' => $item['sku_json'],
                    'pic_url'  => $item['pic_url'],
                    'custom_price'   => $item['custom_price'] ? $item['custom_price'] : '',
                    'original_price' => $goods['price_type'] == 3 ? $item['original_price'] : 0,
                );
                if(preg_match('/^\d+$/', $item['id']) && $item['id'] > 0){
                    $product['id'] = $item['id'];
                    $index = array_search($item['id'], $delProductIds);
                    if($index > -1){
                        unset($delProductIds[$index]);
                    }
                }
                $goods['products'][] = $product;
            }
        }else{
            $goods['sku_json'] = '';
            $product = array(
                'id'       => $delProductIds[0],
                'stock'    => $goods['stock'],
                'price'    => $goods['price'],
                'score'    => $goods['score'],
                'cost'     => $goods['cost'],
                'weight'   => $goods['weight'],
                'outer_id' => $goods['outer_id'],
                'custom_price'   => $goods['custom_price'] ? $goods['custom_price'] : '',
                'original_price' => $goods['price_type'] == 3 ? $goods['original_price'] : 0,
            );
            
            unset($delProductIds[0]);
            $goods['products'] = array($product);
        }
        // 标记要删除的SKU_ID
        if(count($delProductIds) > 0){
            $goods['del_sku'] = implode(',', $delProductIds);
        }
        
        // 解析图片
        if(!$goods['images'] || !$goods['pic_url']){
            $this->error('请至少上传一张宣传图');
        }
        
        // tag标记
        $newTag = array();
        if($goods['tag_id']){
            $newTag = explode(',', $goods['tag_id']);
            foreach ($newTag as $tagId){
                if(!is_numeric($tagId) || $tagId < 1000){
                    $this->error('商品分组标签异常');
                }
            }
        }
        // 删除旧的分组id
        $addTagId = array_diff($newTag, $goods['tag_id']);
        $delTagId = array_diff($goods['tag_id'], $newTag);
        if($addTagId){$goods['add_tag_id'] = implode(',', $addTagId);}
        if($delTagId){$goods['del_tag_id'] = implode(',', $delTagId);}
        
        // 开售时间
        if($goods['sold_time']){
            $goods['sold_time'] = strtotime($goods['sold_time']);
        }
        
        // 自定义参数
        if($goods['parameters']){
            $goods['parameters'] = json_decode($goods['parameters'], true);
            $parameters = array();
            foreach ($goods['parameters'] as $item){
                $key = trim($item['key']);
                $val = trim($item['value']);
                if($key === '' || $val === ''){
                    continue;
                }
                $parameters[] = array($key,$val);
            }
            if($parameters){
                $goods['parameters'] = json_encode($parameters, JSON_UNESCAPED_UNICODE);
            }
        }
        if(!$goods['parameters']){$goods['parameters'] = '';}
        
        // 价格标记
        if(($goods['price_type'] == 1 || $goods['price_type'] == 2) && floatval($goods['price']) < 0.01){
            $this->error('商品价格字段异常');
        }
        $appendTagId = '';
        switch($goods['price_type']){
            case 0: // 普通价
                break;
            case 1: // 区间价
                $appendTagId = 201;
                break;
            case 2: // 会员价
                $appendTagId = 202;
                break;
            case 3: // 积分价
                $appendTagId = 203;
                break;
            case 4: // 单品代理
                $appendTagId = 204;
                break;
            default:
                $this->error('未知价格类型');
                break;
        }
        if($appendTagId){
            $goods['tag_id'] = $appendTagId.($goods['tag_id'] ? ','.$goods['tag_id'] : '');
        }
        
        // 包邮标记
        $express = new \Common\Model\ExpressModel();
        $result = $express->getRangeFee($goods['freight_id'], $goods['weight'], $goods['weight'], $goods['min_order_quantity']);
        if($result['baoyou']){
            $goods['tag_id'] = '301'.($goods['tag_id'] ? ','.$goods['tag_id'] : '');
        }
        
        $goods['original_price'] = is_numeric($goods['original_price']) ? $goods['original_price'] : 0;
        $goods['template_id'] = 0;
        return $goods;
    }
    
    /**
     * 保存排序
     */
    public function saveSort(){
        if(!is_numeric($_POST['id']) || !is_numeric($_POST['sort'])){
            $this->error('参数错误');
        }

        $Model = M();
        $data = $Model->query("SELECT shop_id FROM mall_goods WHERE id=".$_POST['id']);
        $data = $data[0];
        if(!$data || IdWork::getProjectId($data['shop_id']) != $this->projectId || ($data['shop_id'] != $this->shopId && !$this->all_shop)){
            $this->error('商品不存在');
        }

        $Model->execute("UPDATE mall_goods_sort SET sort='{$_POST['sort']}' WHERE id=".$_POST['id']);
        $this->success('已保存排序！');
    }
    
    /**
     * 导出产品
     */
    public function export(){
        $_where = array(
            'shop_id' => $this->shopId,
            'tag' => I('get.tag'),
            'title' => $_GET['title'],
            'action'  => $_GET['action'],
            'sort'    => $_GET['sort'],
            'order'    => $_GET['order']
        );
        
        $Model = D('Goods');
        $Model->export($_where);
    }
    
    /**
     * 商品返款信息
     */
    public function feedback(){
        $goods_id = $_REQUEST['goods_id'];
        $tid      = $_REQUEST['tid'];
        if(IS_GET){
            $this->assign(array(
                'goods_id' => $goods_id,
                'tid'      => $tid
            ));
            $this->display();
        }
        
        $add = array(
            'goods_id'  => $goods_id,
            'user_id'   => $this->user('id'),
            'question'  => $_POST['question'],
            'created'  => date('Y-m-d H:i:s'),
        );
        
        if(!empty($tid)){
            $add['tid'] = $tid;
        }
        
        M('mall_goods_feedback')->add($add);
        $this->success('已保存！');
    }
    
    /**
     * 专属客服
     */
    public function kefu(){
        $goods = $_REQUEST['goods'];
        if(empty($goods)){
            $this->error('请先选中要查看的商品');
        }
        
        $Model = new \Admin\Model\KeFuModel();
        if(IS_POST){
            $Model->saveGoods($goods, $_POST['list']);
            $this->success();
        }
        
        $groups = $Model->getAll(true);
        
        // 单个商品，则默认选中客服
        if(is_numeric($goods)){
            $selected = $Model->getGoodsKF($goods);
            foreach($selected as $sid){
                foreach ($groups as $type=>$item){
                    if(isset($item[$sid['kf_id']])){
                        $groups[$type][$sid['kf_id']]['checked'] = true;
                    }
                }
            }
        }
        
        $this->assign(array('groups' => $groups, 'goods' => $goods));
        $this->display();
    }

    /**
     * 设置商品佣金
     */
    public function commision(){
        $projectId = $this->projectId;
        $goodsId = $_REQUEST['id'];
        if(!is_numeric($goodsId)){
            $this->error('商品ID不能为空');
        }

        $Model = M('agent_goods');
        // 查找商品最新信息
        $goods = $Model->query("SELECT id, shop_id, price, tag_id FROM mall_goods WHERE id=".$goodsId);
        $goods = $goods[0];
        if(!$goods){
            $this->error('商品不存在');
        }else if(IdWork::getProjectId($goods['shop_id']) != $projectId || ($this->shopId != $goods['shop_id'] && !$this->authAllShop())){
            $this->error('您无权设置他人店铺商品');
        }

        if(IS_POST){
            if($_POST['reward_type'] == -1){
                $Model->delete($goods['id']);
            }else{
                $data = array(
                    'id'              => $goods['id'],
                    'reward_type'     => $_POST['reward_type'],
                    'settlement_type' => $_POST['settlement_type'],
                    'reward_value'    => json_encode($_POST['reward_value'], JSON_NUMERIC_CHECK)
                );
                $Model->add($data, null, true);
            }

            $this->success('已保存');
        }

        $data = $Model->find($goods['id']);
        if(!$data){
            $data['id']           = $goods['id'];
            $data['reward_type']  = -1;
            $data['reward_value'] = '{}';
        }else{
            $data['reward_value'] = $data['reward_value'];
        }
        $data['min_price']   = bcdiv($goods['price'], 2, 2);
        $this->assign('data', $data);

        $this->assign('settlement_type', array(
            4 => array('title' => '确认收货后(推荐)'),
            0 => array('title' => '不参与推广'),
            2 => array('title' => '买家付款后'),
            3 => array('title' => '上传快递单号后'),
            1 => array('title' => '手动结算(暂不支持)')
        ));

        $agent_list = $Model->query("SELECT id, title, settlement_type FROM project_card WHERE id BETWEEN {$projectId}0 AND {$projectId}9");
        $agent_list[] = array('id' => 'o', 'title' => '其他', 'settlement_type' => 4);
        //$agent_list[] = array('id' => 0, 'title' => '游客', 'settlement_type' => 4);
        $this->assign('agent_list', json_encode($agent_list));
        $this->display();
    }
}
?>