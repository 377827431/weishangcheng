<?php 
namespace Common\Model;

class CartModel extends GoodsModel{
    protected $tableName = 'mall_cart';

    /*
     * 标记商品已失效
     */
    private function invalid($id){
        $this->execute("UPDATE mall_cart SET invalid=1 WHERE id=".$id);
    }
    
    /**
     * 获取购物车中的产品
     * @param unknown $buyerId
     * @return multitype:
     */
    public function getAll($login, $projectId = 0){
        $resultList = array();
        
        // 先获购物车中的取数据，然后再分解数据
        $activeProductList = $normalProductList = $goodsQuantity = array();
        $sql = "SELECT
                    cart.id AS cart_id, cart.quantity, IF(cart.activity_id>0, cart.activity_id, goods.activity_id) AS activity_id, cart.invalid,
                    product.id AS product_id, goods.id AS goods_id, goods.title, shop.id AS shop_id, shop.`name` AS shop_name,
                    product.price, goods.score, product.original_price, product.custom_price, goods.member_discount,
                    goods.cat_id, goods.tag_id, goods.min_order_quantity, product.stock, goods.is_display, goods.sold_time,
                    goods.is_del, goods.level_quota, goods.buy_quota, goods.every_quota, goods.day_quota,
                    product.sku_json, IF(product.pic_url != '', product.pic_url, goods.pic_url) AS pic_url, goods.goods_type
                FROM mall_cart AS cart
                LEFT JOIN mall_goods AS goods ON goods.id=cart.goods_id
                LEFT JOIN  mall_product AS product ON product.id=cart.product_id
                LEFT JOIN shop ON shop.id=goods.shop_id
                WHERE cart.buyer_id=".$login['id'].($projectId > 0 ? " AND cart.shop_id BETWEEN {$projectId}000 AND {$projectId}999" : "")."
                ORDER BY cart.id DESC";
        $cartList = $this->query($sql);
        if(count($cartList) == 0){
            return array(
                'groups'      => array(),
                'invalidList' => array()
            );
        }
        
        $limit = array('quota' => array(), 'min_order' => array());
        foreach ($cartList as $i=>$item){
            // 结算标记
            $item['settlement'] = array(
                'cart_id'   => $item['cart_id'],
                'quantity'  => $item['quantity'],
                'errmsg'    => '',
                'can_buy'   => 1,
                'invalid'   => 0
            );
            $cartList[$i] = $item;
        }
        
        // 批量解析数据
        $cartList = $this->goodsListHandler($cartList, $login);

        // 分配购物车数据
        $groups = $invalidList = array();
        foreach($cartList as $i=>$item){
            // 标记宝贝失效
            $invalid = $item['invalid'];
            unset($item['invalid']);
            unset($item['quantity']);
            if($item['settlement']['invalid']){
                if(!$invalid){
                    $this->invalid($item['settlement']['cart_id']);
                }
                $invalidList[] = $item;
                continue;
            }
            
            $quantity = $item['settlement']['quantity'];
            $item['total_fee'] = bcmul($item['price'], $quantity, 2);
            $item['total_score'] = $item['price_type'] == 3 ? bcmul($item['score'], $quantity, 2) : 0;
            
            if(!isset($groups[$item['shop_id']])){
                $groups[$item['shop_id']] = array(
                    'shop_id'   => $item['shop_id'],
                    'shop_name' => $item['shop_name'],
                    'products'    => array()
                );
            }
            
            $groups[$item['shop_id']]['products'][] = $item;
        }

        // 签名
        $payUrl = C('PAY_HOST').'/confirm?ticket='.session_id();
        return array(
            'groups'      => array_values($groups),
            'invalidList' => $invalidList,
        );
    }
    
    /**
     * 加入购物车
     */
    public function insert($cart){
        if(!is_numeric($cart['activity_id'])){
            $cart['activity_id'] = 0;
        }
        
        // 购物车数量限制
        $list = $this->where("buyer_id={$cart['buyer_id']} AND shop_id='{$cart['shop_id']}'")->select();
        $total = count($list);
        
        $invalidTotal = 0;
        foreach($list as $item){
            if($item['product_id'] == $cart['product_id']){
                if($item['activity_id'] == $cart['activity_id']){
                    $this->execute("UPDATE {$this->tableName} SET quantity=quantity+{$cart['quantity']} WHERE id=".$item['id']);
                    return $total;
                }
            }
        }
        
        if($total >= 20){
            $this->error = "购物车已上线，无法添加";
            return -1;
        }

        $cart['created'] = NOW_TIME;
        $this->add($cart);
        $this->query("UPDATE mall_goods_sort SET cart_times=cart_times+1 WHERE id='{$cart['goods_id']}'");
        return $total+1;
    }
    
    /**
     * 设置数量
     * @param unknown $id
     * @param unknown $buyerId
     * @param unknown $num
     */
    public function update($id, $quantity, $buyerId){
        return $this->execute("UPDATE mall_cart SET quantity='{$quantity}' WHERE id='{$id}' AND buyer_id='{$buyerId}'");
    }
    
    /**
     * 获取买家购物车中的数量
     * @param unknown $buyerId
     * @param string $sellerId
     */
    public function getBuyerNum($buyerId){
        return $this->where(array("buyer_id" => $buyerId))->count();
    }
}
?>