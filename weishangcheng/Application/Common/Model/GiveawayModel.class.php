<?php 
namespace Common\Model;

class GiveawayModel extends BaseModel{
    protected $tableName = 'mall_giveaway';
    
    public function getInValidity($shopId){
        $where = array("giveaway.end_time" => array("gt", date('Y-m-d H:i:s')));
        $list = $this
                ->field("giveaway.id, giveaway.title, giveaway.stock, product.sku_json, product.pic_url")
                ->alias("giveaway")
                ->join("mall_product AS product ON product.id=giveaway.product_id")
                ->where($where)
                ->select();
        
        foreach($list as $i=>$item){
            $list[$i]['spec'] = $this->toSpecName($item['sku_json']);
            unset($list[$i]['sku_json']);
        }
        return $list;
    }
    
    public function addOrder($giveaway,$buyer, $seller){
        // 产品信息
        $product = $this->query("SELECT product.*, goods.is_virtual, goods.pic_url AS goods_pic_url,goods.pay_type
            FROM mall_product AS product
            LEFT JOIN mall_goods AS goods ON goods.id=product.goods_id
            WHERE product.id=".$giveaway['product_id']);
        $product = $product[0];
        
        $today = date('Y-m-d H:i:s');
        // 买家ip定位
        $ipLocation = new \Org\Net\IpLocation();
        $location = $ipLocation->getlocation();
        // 生成订单号
        $idwork = new \Org\IdWork();
        
        $trade = array(
            'tid'              => $idwork->nextId(),
            'type'             => 'active',     // 活动订单
            'created'          => $today,
            'status'           => 'topay',
            'buyer_id'         => $buyer['id'],
            'buyer_nick'       => $buyer['nickname'],
            'buyer_type'       => isset($buyer['type']) ? $buyer['type'] : 1,
            'buyer_openid'     => $buyer['openid'],
            'buyer_subscribe'  => $buyer['subscribe'],
            'buyer_location'   => $location['country'],
            'buyer_ip'         => $location['ip'],
            'kind'             => 1,   // 购买了几种商品
            'total_num'        => 1,   // 商品数量的和
            'total_fee'        => 0,   // 商品数量*商品售价的和
            'total_score'      => $giveaway['score'],   // 商品数量*商品积分价的和
            'total_points'     => 0,   // 交易成功赠送积分
            'post_fee'         => $giveaway['post_fee'],   // 商品数量*商品邮费的和
            'payment'          => $giveaway['post_fee'],   // 需付金额
            'pay_type'         => 'giveaway',   // 领取赠品
            'paid_fee'         => 0,   // 已付金额
            'paid_score'       => 0,   // 已付积分
            'shipping_type'    => $product['is_virtual'] ? 'virtual' : 'express', // 物流方式
        );
        
        $order = array(
            'oid'          => $trade['tid'],
            'tid'          => $trade['tid'],
            'buyer_id'     => $trade['buyer_id'],
            'goods_id'     => $product['goods_id'],
            'product_id'   => $product['id'],
            'outer_id'     => $product['outer_id'],
            'cat_id'       => $product['cat_id'],
            'title'        => $giveaway['title'],
            'num'          => 1,
            'price'        => $product['price'],
            'score'        => $giveaway['score'],
            'original_price'=>$product['original_price'],
            'pic_url'      => $product['pic_url'] ? $product['pic_url'] : $product['goods_pic_url'],
            'pay_type'     => 1,
            'points'       => 0,
            'total_fee'    => $trade['total_fee'],
            'total_score'  => $giveaway['score'],
            'payment'      => 0,
            'sku_json'     => $product['sku_json'],
            'post_fee'     => $giveaway['post_fee'],
            'is_virtual'   => $product['is_virtual'],
            'shipping_type'=> $trade['shipping_type'],
        );
        
        $record = array(
            'pid' => $giveaway['id'],
            'mid' => $buyer['id'],
            'created' => $today,
            'tid'   => $trade['tid']
        );
        
        M('trade')->add($trade);
        M('mall_order')->add($order);
        M('mall_giveaway_record')->add($record);
        
        $sql = "UPDATE mall_giveaway SET give_num=give_num+1 WHERE id='{$giveaway["id"]}'";
        $this->execute($sql);
        
        $trade['orders'] = array($order);
        return $trade;
    }
}
?>