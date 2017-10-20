<?php 
namespace Admin\Model;

use Common\Model\BaseModel;
use Common\Model\OrderModel;
use Common\Model\StaticModel;
use Org\PSCWS\PSCWS4;
class GoodsModel extends BaseModel{
    protected $tableName = 'mall_goods';
    
    /**
     * 添加商品
     * @param Goods $data
     * @return number
     */
    public function add($data){
        $result = 0;
        $created = date('Y-m-d H:i:s');
        
        /*********************** 保存mall_goods ************************/
        $this->startTrans();
        $goodsId = parent::add(array(
            'shop_id'    => $data['shop_id'],
            'tao_id'     => $data['tao_id'],
            'cat_id'     => $data['cat_id'],
            'tag_id'     => $data['tag_id'],
            'is_virtual' => $data['is_virtual'],
            'title'      => $data['title'],
            'price'      => $data['price'],
            'score'      => $data['score'],
            'cost'       => $data['cost'],
            'pic_url'    => $data['pic_url'],
            'stock'      => $data['stock'],
            'outer_id'   => $data['outer_id'],
            'buy_quota'  => $data['buy_quota'],
            'day_quota'  => $data['day_quota'],
            'every_quota'=> $data['every_quota'],
            'level_quota'=> $data['level_quota'],
            'tag_quota'  => $data['tag_quota'],
            'is_display' => $data['is_display'],
            'sub_stock'  => $data['sub_stock'],
            'invoice'    => $data['invoice'],
            'warranty'   => $data['warranty'],
            'returns'    => $data['returns'],
            'freight_id' => $data['freight_id'],
            'created'    => $created,
            'weight'     => $data['weight'],
            'original_price'      => $data['original_price'],
            'custom_price'       => $data['custom_price'],
            'min_order_quantity' => $data['min_order_quantity'],
            'member_discount'    => $data['member_discount']
        ));
        if(!$goodsId){
            $this->rollback();
            $this->error = '添加商品失败';
            return -1;
        }
        /*********************** 保存mall_goods(结束) ************************/
        
        
        /*********************** 保存mall_product ************************/
        $productModel = array();
        foreach ($data['products'] as $item){
            unset($item['id']);
            $item['goods_id'] = $goodsId;
            $item['created'] = $created;
            $productModel[] = $item;
        }
        $result = M('mall_product')->addAll($productModel);
        if(!$result){
            $this->rollback();
            $this->error = '保存SKU失败';
            return -1;
        }
        /*********************** 保存mall_product(结束) ************************/
        

        // 数据分组 - mall_goods_content表
        M('mall_goods_content')->add(array(
            'goods_id'    => $goodsId,
            'sku_json'    => $data['sku_json'],
            'images'      => $data['images'],
            'template_id' => $data['template_id'],
            'parameters'  => $data['parameters'],
            'digest'      => $data['digest'],
            'detail'      => $data['detail'],
            'send_place'  => $data['send_place'],
            'remote_area' => $data['remote_area'],
        ));
        
        // 数据分组 - mall_goods_sort表
        M('mall_goods_sort')->add(array(
            'id'     => $goodsId,
            'zonghe' => NOW_TIME
        ));
        // 计算类目下产品个数
        $this->updateCategoryQuantity($data['cat_id'], true);
        $this->updateTagQuantity($data['tag_id'], true);
        
        // 搜索关键词
        $pscws = new PSCWS4();
        $result = $pscws->getTextAndPinYin($data['title'].' '.$data['outer_id']);
        $sql = "INSERT INTO mall_key_word SET id={$goodsId}, kw='".addslashes($result['text'])."', py='".addslashes($result['pinyin'])."'";
        $this->execute($sql);
        
        $this->commit();
        return 1;
    }
    
    /**
     * 更新类目下产品数量
     */
    private function updateCategoryQuantity($catId, $add){
        $quantity = 'goods_quantity'.($add ? '+1' : '-1');
        $list = $this->query("SELECT id, parent_ids FROM mall_category WHERE id=$catId");
        foreach($list as $item){
            $parents = $item['parent_ids'] ? $item['parent_ids'].','.$item['id'] : $item['id'];
            $this->execute("UPDATE mall_category SET goods_quantity={$quantity} WHERE id IN ({$parents})");
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
     * 更新商品信息
     * @param unknown $goods
     */
    public function update($data){
        $result = 0;
        $created = date('Y-m-d H:i:s');
        
        /*********************** 保存mall_goods ************************/
        $this->startTrans();
        $goodsId = $data['id'];
        $this->where("id=".$goodsId)->save(array(
            'shop_id'    => $data['shop_id'],
            'tao_id'     => $data['tao_id'],
            'cat_id'     => $data['cat_id'],
            'tag_id'     => $data['tag_id'],
            'is_virtual' => $data['is_virtual'],
            'title'      => $data['title'],
            'price'      => $data['price'],
            'score'      => $data['score'],
            'cost'       => $data['cost'],
            'pic_url'    => $data['pic_url'],
            'stock'      => $data['stock'],
            'outer_id'   => $data['outer_id'],
            'buy_quota'  => $data['buy_quota'],
            'day_quota'  => $data['day_quota'],
            'every_quota'=> $data['every_quota'],
            'level_quota'=> $data['level_quota'],
            'tag_quota'  => $data['tag_quota'],
            'is_display' => $data['is_display'],
            'sold_time'  => $data['sold_time'],
            'sub_stock'  => $data['sub_stock'],
            'invoice'    => $data['invoice'],
            'warranty'   => $data['warranty'],
            'returns'    => $data['returns'],
            'freight_id' => $data['freight_id'],
            'created'    => $created,
            'weight'     => $data['weight'],
            'original_price'      => $data['original_price'],
            'custom_price'       => $data['custom_price'],
            'min_order_quantity' => $data['min_order_quantity'],
            'member_discount'    => $data['member_discount']
        ));
        /*********************** 保存mall_goods(结束) ************************/
        
        
        /*********************** 保存mall_product ************************/
        $productModel = array();
        $PM = M('mall_product');
        foreach ($data['products'] as $item){
            $item['goods_id'] = $goodsId;
            if(is_numeric($item['id'])){
                $PM->save($item);
            }else{
                $item['created'] = $created;
                $productModel[] = $item;
            }
        }
        if(count($productModel) > 0){
            $result = $PM->addAll($productModel);
            if(!$result){
                $this->rollback();
                $this->error = '保存SKU失败';
                return -1;
            }
        }
        // 删除无用的sku
        if($data['del_sku']){
            $this->execute("DELETE FROM mall_product WHERE id IN ({$data['del_sku']})");
        }
        /*********************** 保存mall_product(结束) ************************/
        
        
        // 数据分组 - mall_goods_content表
        M('mall_goods_content')->where("goods_id=".$goodsId)->save(array(
            'sku_json'    => $data['sku_json'],
            'images'      => $data['images'],
            'template_id' => $data['template_id'],
            'parameters'  => $data['parameters'],
            'digest'      => $data['digest'],
            'detail'      => $data['detail'],
            'send_place'  => $data['send_place'],
            'remote_area' => $data['remote_area']
        ));
        
        // 计算类目下产品个数
        if($data['del_cat_id']){
            $this->updateCategoryQuantity($data['cat_id'], true);
            $this->updateCategoryQuantity($data['del_cat_id'], false);
        }
        if($data['del_tag_id']){
            $this->updateTagQuantity($data['del_tag_id'], false);
        }
        if($data['add_tag_id']){
            $this->updateTagQuantity($data['add_tag_id'], true);
        }
        
        // 搜索关键词
        $pscws = new PSCWS4();
        $result = $pscws->getTextAndPinYin($data['title']);
        $sql = "UPDATE mall_key_word SET kw='".addslashes($result['text'])."', py='".addslashes($result['pinyin'])."' WHERE id={$goodsId}";
        $this->execute($sql);
        
        $this->commit();
        return 1;
    }
    
    /**
     * 复制商品
     * @author Liucong
     */
    public function copy($id){
        $data = $this->getById($id);
        $data['pv'] = 0;
        $data['uv'] = 0;
        $data['sold_num'] = 0;
        $data['order_num'] = 0;
        $data['sku_json'] = is_array($data['sku_json']) ? json_encode($data['sku_json'], JSON_UNESCAPED_UNICODE) : '';
        if(empty($data['weight'])){
                unset($data['weight']);
        }
        unset($data['id']);
        foreach($data['products'] as $key => $value){
                unset($data['products'][$key]['id']);
                if(empty($value['modified'])){
                        unset($data['products'][$key]['modified']);
                }
                $data['products'][$key]['sold_num'] = 0;
                $data['products'][$key]['order_num'] = 0;
                $data['products'][$key]['pv'] = 0;
                $data['products'][$key]['uv'] = 0;
        }
        $result = $this->insert($data);
        return $result;
    }
    
    //复制
    public function copy_goods($id){
        $data = $this->where('tao_id = 0')->find($id);
        $data['created'] = date("Y-m-d H:i:s");
        $data['title']=$data['title']."复制";
        unset($data['id']);
        $data['activity_id']=0;
        $data['tag_id'] = explode(',', $data['tag_id']);
        foreach ($data['tag_id'] as $i=>$item){
            if($item>100 && $item<200){
                unset($data['tag_id'][$i]);
            }
        }
        $data['tag_id'] = implode(',', $data['tag_id']);
        $data['id'] = M('mall_goods')->add($data);
        $products = $this->query("SELECT * FROM mall_product WHERE goods_id=".$id);
        foreach ($products as $key=>$val){
            unset($products[$key]['id']);
            $products[$key]['goods_id'] = $data['id'];
        }
        $result= M('mall_product')->addAll($products);
        M('mall_goods_sort')->add(array(
            'id'     => $data['id'],
            'zonghe' => NOW_TIME
        ));
        M('mall_goods_content')->add(array(
            'goods_id'    => $data['id'],
            'sku_json'    => $products[0]['sku_json'],
            'images'      => $data['pic_url'],
            'template_id' => $data['freight_id'],
            'parameters'  => $data['parameters'] == false ? '0' : $data['parameters'],
            'digest'      => $data['digest']== false ? '0' : $data['digest'],
            'detail'      => $data['detail']== false ? '0' : $data['detail'],
            'send_place'  => $data['send_place']== false ? '0' : $data['send_place'],
            'remote_area' => $data['remote_area']== false ? '0' : $data['remote_area'],
        ));
        return $result;
    }
    
    /**
     * 根据id获取商品信息
     * @param unknown $id
     * @return NULL|Ambigous <mixed, boolean, NULL, string, unknown, multitype:, object>
     */
    public function getById($id){
        $goods = $this
                 ->alias("goods")
                 ->field("goods.id, goods.sold_time, goods.buy_quota, goods.cat_id, goods.tag_id,
                     goods.title,  goods.price, 
                     goods.original_price, goods.score_percent, goods.stock, goods.hide_stock, goods.outer_id, 
                     goods.pic_url, goods.images, goods.digest, goods.day_quota, goods.every_quota, 
                     goods.agent_quota, goods.is_virtual, goods.freight_tid, goods.min_order_quantity,
                     goods.member_discount, goods.points, goods.is_display, goods.sold_num, goods.order_num,
                     goods.invoice, goods.warranty, goods.`returns`, goods.created, goods.sku_json, 
                     goods.template_id, goods.is_del, goods.weight, goods.shop_id, goods.tao_id, goods.parameters,
                     shop.name AS shop_name")
                 ->join("shop ON shop.id=goods.shop_id")
                 ->where("goods.id=".$id)
                 ->find();
        
        if(empty($goods)){
            $this->error = '商品不存在';
            return null;
        }
        
        return $this->formatter($goods);
    }
    
    private function formatter($goods){
        // 格式化数据
        $goods['price'] = sprintf("%.2f", $goods['price']);
        $goods['retail_price'] = $goods['price'];
        if($goods['original_price'] > $goods['price']){
            $goods['original_price'] = sprintf("%.2f", $goods['original_price']);
        }else{
            $goods['original_price'] = '';
        }
        $goods['images'] = json_decode($goods['images'], true);
        
        // sku组合
        $goods['sku_json'] = $goods['sku_json'] ? json_decode($goods['sku_json'], true) : array();
    
        // 参数
        if(isset($goods['parameters'])){
            $goods['parameters'] = json_decode($goods['parameters'], true);
        }
    
        // 产品
        $products = $this->query("SELECT * FROM mall_product WHERE goods_id='{$goods['id']}'");
        foreach($products as $i=>$item){
            $products[$i]['sku_json'] = ($item['sku_json'] && $item['sku_json'] != '[]') ? json_decode($item['sku_json'], true) : array();
        }
        $goods['products'] = $products;
        
        /************************************/
        // 限购处理
        $limit = array();
        $goods['quota'] = $goods['stock'];
        $todayStart     = date('Y-m-d').' 00:00:00';
        $todayEnd       = date('Y-m-d').' 23:59:59';
        
        // 日限售处理(同时处理库存)
        if($goods['day_quota'] > 0){
            $limit[] = '每日限售'.$goods['day_quota'].'件';
            
            $OrderModel = new OrderModel();
            $soldNum = $OrderModel->getSoldNumByTime($goods['id'], $todayStart, $todayEnd);
            if($soldNum >= $goods['day_quota']){
                $goods['stock'] = 0;
            }else{
                $stock = $goods['day_quota'] - $soldNum;
                $goods['stock'] = $goods['stock'] < $stock ? $goods['stock'] : $stock;
            }
        
            $goods['quota'] = $goods['stock'];
        }
        
        // 每人每日限购处理
        if($goods['every_quota'] > 0){
            $limit[] = '每日限购'.$goods['every_quota'].'件';
            if($goods['quota'] > 0){
                $quota = $goods['stock'] < $goods['every_quota'] ? $goods['stock'] : $goods['every_quota'];
                if($quota < $goods['quota']){
                    $goods['quota'] = $quota;
                }  
            }
        }
        
        // 每人最多能购买
        if($goods['buy_quota'] > 0){
            $limit[] = '每人限购'.$goods['buy_quota'].'件';
            if($goods['quota'] > 0){
                $quota = $goods['stock'] < $goods['buy_quota'] ? $goods['stock'] : $goods['buy_quota'];
                if($quota < $goods['quota']){
                    $goods['quota'] = $quota;
                }
            }
        }
        
        // 最小起订量
        if($goods['min_order_quantity'] > 1 && count($limit) == 0){
            $limit[] = '最小起订量'.$goods['min_order_quantity'].'件';
        }
        
        if($goods['quota'] > 0 && ($goods['pay_type'] == 4 || $goods['pay_type'] == 5)){
            $goods['quota'] = 1;
        }
        $goods['quota_str'] = count($limit) > 0 ? implode(',', $limit) : '';
        /*************************************/
        
        // 发货地
        if(isset($goods['send_place'])){
            if($goods['send_place'] > 0){
                $city = StaticModel::getCityList($goods['send_place']);
                $province = StaticModel::getCityList($city['pcode']);
                $goods['send_place'] = $province['sname'].' '.$city['sname'];
            }else{
                $goods['send_place'] = '';
            }
        }
        
        // 偏远地区
        if(!empty($goods['remote_area'])){
            $remoteAreas = explode(',', $goods['remote_area']);
            $remoteArea = '';
            foreach ($remoteAreas as $code){
                $city = StaticModel::getCityList($code);
                $remoteArea .= $remoteArea == '' ? $city['sname'] : '、'.$city['sname'];
            }
            $goods['remote_area'] = $remoteArea;
        }
        
        $goods['status'] = 'onsale';
        if($goods['sold_time'] > NOW_TIME){
            $goods['countdown'] = array('txt' => '距开售剩余', 'start' => NOW_TIME, 'end' => $goods['sold_time']);
            $goods['action'] = array(
                array('id' => 'addCart', 'txt' => '加入购物车', 'disabled' => 0, 'class' => 'btn-orange'),
                array('id' => 'buyNow', 'txt' => '立即购买', 'disabled' => 1, 'class' => 'disabled')
            );
        }else if($goods['is_display'] == 0){
            $goods['action'] = array(
                array('id' => 'buyNow', 'txt' => '已下架', 'disabled' => 1, 'class' => 'disabled')
            );
        }else if($goods['stock'] <= 0){
            $goods['action'] = array(
                array('id' => 'buyNow', 'txt' => '已售罄', 'disabled' => 1, 'class' => 'disabled')
            );
        }else if($goods['quota'] == 0){
            $goods['action'] = array(
                array('id' => 'buyNow', 'txt' => '已超限购', 'disabled' => 1, 'class' => 'disabled')
            );
        }else{
            $goods['action'] = array(
                array('id' => 'addCart', 'txt' => '加入购物车', 'disabled' => 0, 'class' => 'btn-orange'),
                array('id' => 'buyNow', 'txt' => '立即购买', 'disabled' => 0, 'class' => 'btn-orange-dark'),
            );
        }
        
        return $goods;
    }
    
    /**
     * 更新单个产品(不更新goods表库存)
     * @param unknown $product
     * @return number|boolean
     */
    public function updateProduct($product){
        if(!is_numeric($product['id'])){
            $this->error = '产品ID不能为空';
            return -1;
        }
        
        $Model = M('mall_product');
        if(is_array($product['sku_json'])){
            $product['sku_json'] = json_encode($product['sku_json'], JSON_UNESCAPED_UNICODE);
        }
        $Model->where("id=%d", $product['id'])->save($product);
    }
    
    /**
     * 批量更新产品(自动更新goods表库存)
     * @param unknown $products
     */
    public function updateProducts($goodsId, $products){
        $Model = M('mall_product');
        $minPrice = -1;
        foreach($products as $id=>$product){
            $Model->where("id=".$id)->save($product);
            
            if($minPrice < 0 || $product['price'] < $minPrice){
                $minPrice = $product['price'];
            }
        }
        
        $this->execute("UPDATE mall_goods SET price='{$minPrice}', stock=(SELECT SUM(stock) FROM mall_product WHERE goods_id={$goodsId}) WHERE id={$goodsId}");
    }
    
    /**
     * 根据id删除商品
     * @param unknown $goodsIds
     * @param string $shopId
     * @return number
     */
    public function deleteById($goodsIds, $shopId = null){
        if(empty($goodsIds)){ 
            $this->error = '商品ID不能为空';
            return  -1;
        }
        
        $sql = "Update ".$this->tableName." SET is_del=1 WHERE `id` IN ({$goodsIds})";
        if(is_numeric($shopId)){
            $sql .= "  AND shop_id=".$shopId;
        }
        
        $result = $this->execute($sql);
        if($result > 0){
            $this->execute("DELETE FROM mall_goods_sort WHERE id IN ({$goodsIds})");
            $this->execute("DELETE FROM mall_key_word WHERE id IN ({$goodsIds})");
        }
        return $result;
    }
    
    /**
     * 批量下架
     * @param unknown $goodsIds
     * @param string $shopId
     * @return number|\Think\false
     */
    public function takeDown($goodsIds, $shopId = null){
        if(empty($goodsIds)){ 
            $this->error = '商品ID不能为空';
            return  -1;
        }
        
        $goodsIds = addslashes($goodsIds);
        $time = date('Y-m-d H:i:s');
        $sql = "UPDATE ".$this->tableName." SET is_display=0,takedowns='".$time."' WHERE `id` IN ({$goodsIds})";
        if(is_numeric($shopId)){
            $sql .= " AND shop_id=".$shopId;
        }
        
        $result = $this->execute($sql);
        if($result > 0){
            $this->goodsTakedownsMsg($goodsIds);
        }
        return $result;
    }
    
    /**
     * 批量上架
     * @param unknown $goodsIds
     * @param string $shopId
     * @return number|\Think\false
     */
    public function takeUp($goodsIds, $shopId = null){
        if(empty($goodsIds)){ 
            $this->error = '商品ID不能为空';
            return  -1;
        }
        
        $sql = "UPDATE ".$this->tableName." SET is_display=1 WHERE `id` IN ({$goodsIds})";
        if(is_numeric($shopId)){
            $sql .= " AND shop_id=".$shopId;
        }
        return $this->execute($sql);
    }
    
    /**
     * 
     * @param unknown $goodsIds
     * @param unknown $join
     * @param string $shopId
     * @return number|\Think\false
     */
    public function discount($goodsIds, $join, $shopId = null){
        if(empty($goodsIds)){
            $this->error = '商品ID不能为空';
            return  -1;
        }
        
        $sql = "UPDATE ".$this->tableName." SET member_discount=".($join ? 1 : 0)." WHERE `id` IN ({$goodsIds})";
        if(is_numeric($shopId)){
            $sql .= " AND shop_id=".$shopId;
        }
        return $this->execute($sql);
    }
    
    /**
     * 获取运费
     * @param unknown $goods
     */
    private function getFreightFee($goods){
        if(isset($goods['freight_fee'])){
            return $goods['freight_fee'];
        }
        
        $EM = new \Common\Model\ExpressModel();
        return $EM->getRangeFee($goods['freight_tid'], $goods['weight']);
    }
    
    /**
     * 获取产品
     */
    public function getProduct($shopId){
        $data = array('total' => 0, 'rows' => array());
        
        $where = "goods.is_del=0";
        if(!empty($_GET['title']))
            $where .= "AND goods.title LIKE '%".addslashes($_GET['title'])."%'";
        
        $data['total'] = $this->alias("goods")
                ->join("mall_product AS product ON product.goods_id=goods.id", "INNER")
                ->where($where)
                ->count();
        if($data['total'] == 0)
            return $data;
        
        $offset = I('get.offset/d', 0);
        $limit = I('get.limit/d', 50);
        
        $list = $this->alias("goods")
                ->field("product.id, product.sku_json, goods.post_fee, product.stock, goods.title")
                ->join("mall_product AS product ON product.goods_id=goods.id", "INNER")
                ->where($where)
                ->order("goods.id DESC")
                ->limit($offset, $limit)
                ->select();
        
        foreach($list as $i=>$item){
            $list[$i]['spec'] = $this->getSpec($item['sku_json']);
            unset($list[$i]['sku_json']);
        }
        
        $data['rows'] = $list;
        return $data;
    }
    
    /**
     * 获取产品信息
     */
    public function getProductList($_where){
        $data = array('total' => 0, 'rows' => array());
        $where = array();
        $offset = I('get.offset/d', 0);
        $limit = I('get.limit/d', 50);
        $where[] = "goods.is_del=0";
        if($_where['action'] == 'index'){
            $where[] = "goods.is_display=1 AND goods.stock>0";
        }else if($_where['action'] == 'soldout'){
            $where[] = "product.stock<=0";
        }else if($_where['action'] == 'no_display'){
            $where[] = "goods.is_display=0";
        }
        
        if(is_numeric($_where['shop_id'])){
            $where[] = "goods.shop_id=".$_where['shop_id'];
        }
        
        if(!empty($_where['title'])){
            $title = addslashes($_where['title']);
            $where[] = "(goods.title LIKE '%{$title}%' OR goods.outer_id like '%{$title}%')";
        }
        
        $innerJoin = " INNER JOIN mall_goods_sort AS goods_sort ON goods_sort.id=goods.id";
        if(is_numeric($_where['tag'])){
            $where[] = "MATCH (goods.tag_id) AGAINST ({$_where['tag']} IN BOOLEAN MODE)";
        }
        
        if($_where['action'] == 'soldout'){
            $innerJoin .= " INNER JOIN mall_product AS product ON product.goods_id=goods.id";
        }

        $project_id = session('user.project_id');
        $where[] = "goods.shop_id LIKE '%{$project_id}%'";
        $where = count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "";
        // 计算总数
        $_total = $this->query("SELECT COUNT(distinct goods.id) FROM mall_goods AS goods".$innerJoin.$where);
        $data['total'] = current($_total[0]);
        if($data['total'] == 0){
            return $data;
        }
        $groupBy = $_where['action'] == 'soldout' ? " GROUP BY goods.id" : "";
        $orderBy = "";
        switch ($_where['sort']){
            case '':
                $orderBy = "goods.id ";
                break;
            case 'title':
                $orderBy = "goods.id";
                break;
            case 'stock':
                $orderBy = "goods.stock";
                break;
            case 'created':
                $orderBy = "goods.id";
                break;
            case 'sold':
                $orderBy = "goods_sort.sold";
                break;
            case 'sort':
                $orderBy = "goods_sort.sort";
                break;
        }

        if(empty($orderBy)){
            if($_where['action'] == 'no_display'){
                $orderBy = " ORDER BY goods.takedowns desc";
            }
            else{
                $orderBy = " ORDER BY goods_sort.sort DESC, goods_sort.zonghe DESC, goods_sort.uv DESC";
            }
        }else{
            $orderBy = " ORDER BY ".$orderBy." ".$_where['order'];
        }
        $sql = "SELECT goods.id, goods.title, goods.price, goods.pic_url, goods.stock, sort,goods.takedowns, goods.tao_id,
                       goods.created,goods.shop_id,goods.cost,goods.price,goods.original_price,
                       goods_sort.sevenday,goods_sort.sold,goods_sort.sevenday,goods_sort.threeday,goods_sort.pv,goods_sort.uv
                FROM mall_goods AS goods
                {$innerJoin}
                {$where}
                {$groupBy}
                {$orderBy}
                LIMIT {$offset}, {$limit}";
        $data['rows'] = $this->query($sql);
        if($_where['action'] == 'soldout'){
            foreach ($data['rows'] as $i=>$item){
                $data['rows'][$i]['spec'] = get_spec_name($item['sku_json']);
                unset($data['rows'][$i]['sku_json']);
            }
        }
        $project = get_project($project_id);
        foreach ($data['rows'] as $key => $value) {
            $data['rows'][$key]['url'] = $project['host'].'/'.$project['alias'].'/goods?id='.$value['id'];   
        }
        return $data;
    }
    
    
    /**
     * 导出产品信息
     */
    public function export($_where){
        set_time_limit(0);
        
        $where = array("goods.is_del=0");
        if($_where['action'] == 'index'){
            $where[] = "goods.is_display=1 AND goods.stock>0";
        }else if($_where['action'] == 'soldout'){
            $where[] = "product.stock<=0";
        }else if($_where['action'] == 'no_display'){
            $where[] = "goods.is_display=0";
        }
        
        if(is_numeric($_where['shop_id'])){
            $where[] = "goods.shop_id=".$_where['shop_id'];
        }
        
        if(!empty($_where['title'])){
            $title = addslashes($_where['title']);
            $where[] = "(goods.title LIKE '%{$title}%' OR goods.outer_id like '%{$title}%')";
        }
        
        $innerJoin = " LEFT JOIN mall_goods_sort AS goods_sort ON goods_sort.id=goods.id";
        $innerJoin .= " INNER JOIN mall_product AS product ON product.goods_id=goods.id";
        if(is_numeric($_where['tag'])){
            $where[] = "MATCH (goods.tag_id) AGAINST ({$_where['tag']} IN BOOLEAN MODE)";
        }
        
        $where = count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "";
        
        $orderBy = "";
        switch ($_where['sort']){
            case 'title':
                $orderBy = "goods.title";
                break;
            case 'stock':
                $orderBy = "goods.stock";
                break;
            case 'created':
                $orderBy = "goods.id";
                break;
            case 'sort':
                $orderBy = "goods_sort.sort";
                break;
        }
        
        if(empty($orderBy)){
            $orderBy = " ORDER BY goods_sort.sort DESC, goods_sort.zonghe DESC, goods_sort.uv DESC";
        }else{
            $orderBy = " ORDER BY ".$orderBy." ".$_where['order'];
        }
        
        $sql = "SELECT goods.id, goods.title,goods.stock AS goods_stock, goods.created AS goods_created, goods_sort.sort,goods_sort.uv,goods_sort.pv,goods_sort.threeday,
                       goods_sort.sevenday,goods_sort.thiryday,product.id AS product_id,product.sku_json, product.price,product.outer_id, product.stock, product.sold,
                       product.created,product.cost, goods.outer_id AS goods_outer_id
                FROM mall_goods AS goods
                {$innerJoin}
                {$where}
                {$orderBy}";
                
        $list = $this->query($sql);
        $goodsList = array();
        foreach($list as $item){
            if(!isset($goodsList[$item['id']])){
                $goodsList[$item['id']] = array(
                    'outer_id' => $item['goods_outer_id'],
                    'title' => $item['title'],
                    'pv' => $item['pv'],
                    'uv' => $item['uv'],
                    'stock' => $item['goods_stock'],
                    'sold_num' => $item['goods_sold_num'],
                    'created' => $item['goods_created'],
                    'sort' => $item['sort'],
                    'yesterday' => $item['yesterday'],
                    'threeday' => $item['threeday'],
                    'sevenday' => $item['sevenday'],
                    'thiryday' => $item['thiryday'],
                    'pv' => $item['pv'],
                    'uv' => $item['uv'],
                    'products' => array()
                );
            }
            
            $goodsList[$item['id']]['products'][] = array(
                'id' => $item['product_id'],
                'outer_id' => $item['outer_id'],
                'spec' => get_spec_name($item['sku_json']),
                'price' => $item['price'],
                'agent2_price' => $item['agent2_price'],
                'agent3_price' => $item['agent3_price'],
                'stock' => $item['stock'],
                'sold' => $item['sold'],
                'created' => $item['created'],
                'cost' => $item['cost'],
                'pv' => $item['pv'],
                'uv' => $item['uv']
                
            );
        }
        
        $date = date('Y-m-d H:i:s');
        // 加载PHPExcel
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        
        // 读取第一个工作表
        $worksheet = $objPHPExcel->setActiveSheetIndex(0);
        $worksheet->setTitle('产品信息');
        
        $i=1;
        $worksheet
        ->setCellValue('A'.$i, 'ID')
        ->setCellValue('B'.$i, '商品名称')
        ->setCellValue('C'.$i, '商家编码')
        ->setCellValue('D'.$i, 'SKU')
        ->setCellValue('E'.$i, '员工价')
        ->setCellValue('F'.$i, '会员价')
        ->setCellValue('G'.$i, '零售价')
        ->setCellValue('H'.$i, '成本')
        ->setCellValue('I'.$i, '产品销量')
        ->setCellValue('J'.$i, '产品库存')
        ->setCellValue('K'.$i, '产品货号')
        ->setCellValue('L'.$i, '创建时间')
        ->setCellValue('M'.$i, '商品销量')
        ->setCellValue('N'.$i, '商品库存')
        ->setCellValue('O'.$i, '商品PV')
        ->setCellValue('P'.$i, '商品UV')
        ->setCellValue('Q'.$i, '昨日销量')
        ->setCellValue('R'.$i, '三日销量')
        ->setCellValue('S'.$i, '七日销量')
        ->setCellValue('T'.$i, '月销量');
        
        foreach($goodsList AS $goodsId=>$goods){
            $i++;
            $worksheet
            ->setCellValue('A'.$i, $goodsId)
            ->setCellValue('B'.$i, $goods['title'])
            ->setCellValue('C'.$i, $goods['outer_id'])
            ->setCellValue('N'.$i, $goods['stock'])
            ->setCellValue('Q'.$i, $goods['yesterday'])
            ->setCellValue('R'.$i, $goods['threeday'])
            ->setCellValue('S'.$i, $goods['sevenday'])
            ->setCellValue('T'.$i, $goods['thiryday']);
            
            // 合并单元格
            $productCount = count($goods['products']);
            if($productCount > 1){
                $mergeLine = $productCount + $i - 1;
                
                $worksheet
                ->mergeCells("A{$i}:A{$mergeLine}")
                ->mergeCells("B{$i}:B{$mergeLine}")
                ->mergeCells("C{$i}:C{$mergeLine}")
                ->mergeCells("M{$i}:M{$mergeLine}")
                ->mergeCells("N{$i}:N{$mergeLine}")
                ->mergeCells("O{$i}:O{$mergeLine}")
                ->mergeCells("P{$i}:P{$mergeLine}")
                ->mergeCells("Q{$i}:Q{$mergeLine}")
                ->mergeCells("R{$i}:R{$mergeLine}")
                ->mergeCells("S{$i}:S{$mergeLine}")
                ->mergeCells("T{$i}:T{$mergeLine}");
            }
            
            foreach ($goods['products'] as $index=>$product){
                if($index > 0){
                    $i++;
                }
                $worksheet->setCellValue('D'.$i, $product['spec'])
                ->setCellValueExplicit('E'.$i, $product['agent2_price'], \PHPExcel_Cell_DataType::TYPE_NUMERIC)
                ->setCellValueExplicit('F'.$i, $product['agent3_price'], \PHPExcel_Cell_DataType::TYPE_NUMERIC)
                ->setCellValueExplicit('G'.$i, $product['price'], \PHPExcel_Cell_DataType::TYPE_NUMERIC)
                ->setCellValueExplicit('H'.$i, $product['cost'], \PHPExcel_Cell_DataType::TYPE_NUMERIC)
                ->setCellValue('I'.$i, $product['sold_num'])
                ->setCellValue('J'.$i, $product['stock'])
                ->setCellValue('K'.$i, $product['outer_id'])
                ->setCellValue('L'.$i, $product['created'])
                ->setCellValue('M'.$i, $product['sold'])
                ->setCellValue('O'.$i, $product['pv'])
                ->setCellValue('P'.$i, $product['uv']);
            }
        }
        
        $worksheet->getStyle('A1:T'.(count($list)+1))
        ->getAlignment()
        ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)
        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        
        // Redirect output to a client’s web browser (Excel2007)
        $text = iconv('UTF-8', 'GB2312', '产品信息');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        //header('Cache-Control: max-age=1');
        
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
    
    
}
?>