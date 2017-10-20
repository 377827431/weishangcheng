<?php
namespace H5\Model;
use Org\IdWork;
use Think\Cache\Driver\Redis;

class GoodsModel extends \Common\Model\GoodsModel{
    protected $tableName = 'mall_goods';

    /**
     * 获取首页展示的商品
     * @param unknown $shopId
     */
    public function search($params, $login){
        $offset = is_numeric($params['offset']) ? $params['offset'] : 0;
        $size = is_numeric($params['size']) ? $params['size'] : 38;

        $join = ''; // 连表
        $order = '';    // 默认排序
        switch ($params['sort']){
            case 'newest':
                $order = 'goods.id DESC';
                break;
            case 'sales':
                $order = 'sort.sevenday DESC';
                break;
            case 'hot':
                $order = 'sort.uv DESC';
                break;
            case 'price':
                $order = 'goods.price';
                break;
            default:
                $order = 'sort.sort DESC,created DESC';
                break;
        }

        $where = array();
        if(is_numeric($params['shop_id'])){
            $where[] = "goods.shop_id=".$params['shop_id'];
        }else if(is_numeric($params['project_id'])){
            $where[] = "goods.shop_id BETWEEN {$params['project_id']}001 AND {$params['project_id']}999";
        }
        $where[] = 'goods.is_display=1';

        if(is_numeric($params['cat_id'])){
            $where[] = "goods.cat_id ='".$params['cat_id']."'";
        }

        if(is_numeric($params['tag_id'])){
            $where[] = "MATCH (goods.tag_id) AGAINST ({$params['tag_id']} IN BOOLEAN MODE)";
        }else if($params['tag_id'] == 'score'){
            $where[] = "MATCH (goods.tag_id) AGAINST (203 IN BOOLEAN MODE)";
        }

        if($params['kw'] != ''){
            $kw = addslashes($params['kw']);
            $where[] = "(MATCH(kw.kw, kw.py) AGAINST ('{$kw}' IN BOOLEAN MODE) OR goods.title LIKE '%{$kw}%')";
            $join   .= " LEFT JOIN mall_key_word AS kw ON kw.id=goods.id";
        }

        // 合并WHERE条件
        $where   = "WHERE ".implode(' AND ', $where);

        $sql = "SELECT goods.id AS goods_id, goods.cat_id, goods.tag_id, goods.activity_id, goods.title, goods.pic_url,
                    goods.original_price, goods.price, goods.score, custom_price, member_discount, goods.stock, goods.shop_id
                FROM mall_goods AS goods
                {$join}
                LEFT JOIN mall_goods_sort AS sort ON sort.id=goods.id
                {$where}
                ORDER BY {$order}
                LIMIT {$offset}, {$size}";
        $list = $this->query($sql);

        // 页面数据显示处理
        if(count($list) == 0){
            return $list;
        }

        return $this->goodsListHandler($list, $login);
    }

    /**
     * 猜你喜欢
     */
    public function getTuijianGoods($login, $projectId = 0){
        //置顶
        $sql = "SELECT goods.id AS goods_id, goods.cat_id, goods.tag_id, goods.activity_id, goods.title, goods.pic_url,
                    goods.price, goods.original_price, goods.score, custom_price, member_discount, goods.stock, goods.shop_id
                FROM mall_goods AS goods
                INNER JOIN mall_goods_sort AS sort ON sort.id=goods.id 
                WHERE shop_id = {$projectId}001 AND is_display=1 
                ORDER BY sort.sort DESC,goods.created DESC 
                LIMIT 0,1";
        $newList = $this->query($sql);
                
        //最新
        $newList_sql = "SELECT goods.id AS goods_id, goods.cat_id, goods.tag_id, goods.activity_id,
                            goods.title, goods.pic_url,goods.price, goods.original_price, goods.score,
                            custom_price, member_discount, goods.stock, goods.shop_id
                        FROM mall_goods AS goods
                        WHERE shop_id = {$projectId}001 AND is_display=1
                        ORDER BY created DESC
                        LIMIT 0,2";
        $newsList = $this->query($newList_sql);
        foreach ($newList as $k => $v) {
            if(!empty($newsList)){
                foreach ($newsList as $k1 => $v1) {
                    if($v['goods_id'] == $v1['goods_id']){
                        unset($newsList[$k1]);
                    }
                }
            }
        }
        if(!empty($newsList)){
            foreach ($newsList as $k1 => $v1) {
                $newList[] = $v1;
            }
        }
        $new_count = count($newList);

        //热销
        $hotList = M("mall_goods")
                        ->alias("goods")
                        ->field("goods.id AS goods_id, goods.cat_id, goods.tag_id, goods.activity_id,
                            goods.title, goods.pic_url,goods.price, goods.original_price, goods.score,
                            custom_price, member_discount, goods.stock, goods.shop_id")
                        ->join("mall_goods_sort AS mgs ON goods.id = mgs.id")
                        ->where("goods.is_display=1 AND goods.shop_id = {$projectId}001 AND mgs.id is not null")
                        ->order("mgs.sold DESC")
                        ->limit(0,2)
                        ->select();

        // 查找3个月内浏览历史记录
        $startId = IdWork::getLikeId('-3 month');
        $sql = "SELECT GROUP_CONCAT(DISTINCT cat_id) AS cat_id
                FROM mall_goods_uv
                WHERE id>'{$startId}' AND user_id = '{$login['id']}'
                ORDER BY id DESC LIMIT 50";
        $history = $this->query($sql);
        $catId = $history[0]['cat_id'];
        $where = array();
        if($projectId > 0){
            $where[] = "shop_id BETWEEN {$projectId}001 AND {$projectId}999";
        }
//        if($catId != ''){
//            $where[] = "cat_id IN ({$catId})";
//        }
        $where[] = "is_display=1";
        $tag_id = I('get.tag_id', 0);
        if ($tag_id > 0){
            $where[] = "MATCH (goods.tag_id) AGAINST ({$tag_id} IN BOOLEAN MODE)";
        }
        $where = "WHERE ".implode(' AND ', $where);
        $sql = "SELECT goods.id AS goods_id, goods.cat_id, goods.tag_id, goods.activity_id, goods.title, goods.pic_url,
                    goods.price, goods.original_price, goods.score, custom_price, member_discount, goods.stock, goods.shop_id
                FROM mall_goods AS goods
                INNER JOIN mall_goods_sort AS sort ON sort.id=goods.id
                {$where}
                ORDER BY sort.sort DESC,goods.created DESC
                LIMIT 1,57";
        $list = $this->query($sql);
        foreach ($newList as $k => $v) {
            if(!empty($hotList)){
                foreach ($hotList as $k1 => $v1) {
                    if($v['goods_id'] == $v1['goods_id']){
                        unset($hotList[$k1]);
                    }
                }
            }
        }
        if(!empty($hotList)){
            foreach ($hotList as $k1 => $v1) {
                $newList[] = $v1;
            }
        }
        foreach ($newList as $k => $v) {
            if(!empty($list)){
                foreach ($list as $k1 => $v1) {
                    if($v['goods_id'] == $v1['goods_id']){
                        unset($list[$k1]);
                    }
                }
            }
        }
        if(!empty($list)){
            foreach ($list as $k1 => $v1) {
                $newList[] = $v1;
            }
        }
        return $this->goodsListHandler($newList, $login);
    }

    /**
     * 根据商品ID获取商品信息
     * @param unknown $id
     */
    public function getDetail($params, &$login){
        if(!is_numeric($params['id'])){
            $this->error = '参数错误';
            return;
        }
        $sql = "SELECT goods.id AS goods_id, goods.cat_id, goods.tag_id, goods.title,
                    goods.price, goods.original_price, goods.score, custom_price, member_discount,
                    shop.id AS shop_id, shop.`name` AS shop_name, goods.activity_id, goods.pic_url,
                	goods.stock, goods.weight, goods.is_display, goods.sold_time, shop.mid AS shop_mid,
                	goods.level_quota, goods.buy_quota, goods.day_quota, goods.every_quota, goods.min_order_quantity,
                	goods.retail_price, goods.invoice, goods.warranty, goods.`returns`, goods.tao_id, shop.aliid,
                    content.images, goods_sort.rate_times, content.sku_json, content.parameters, content.remote_area,
                	content.send_place, content.digest, content.template_id, content.detail, goods.freight_id,
                	(goods_sort.pv + goods_sort.collection + goods_sort.order_times + goods_sort.cart_times + goods_sort.rate_times)+1 AS month_sold,
                	goods_sort.rate_good, goods_sort.rate_middle, goods_sort.rate_bad, shop.logo as shop_logo
                FROM mall_goods AS goods
                INNER JOIN mall_goods_content AS content ON content.goods_id = goods.id
                INNER JOIN mall_goods_sort AS goods_sort ON goods_sort.id=goods.id
                INNER JOIN shop ON shop.id = goods.shop_id
                WHERE goods.id='{$params['id']}' AND goods.is_del=0";
        $goods = $this->query($sql);
        if(empty($goods)){
            $this->error = '商品不存在';
            return;
        }
        $goods = $goods[0];

        $goods['project_id'] = IdWork::getProjectId($goods['shop_id']);
        $login = $this->getProjectMember($login, $goods['project_id']);

        // 强制活动预览
        $activityPreview = $params['activity_id'] > 0 && $params['activity_id'] == $goods['activity_id'];
        $goods['quantity'] = $goods['min_order_quantity'];
        $goods = $this->goodsDetailHandler($goods, $login, $activityPreview);

        // 保存浏览量
        $this->syncPV($goods, $login['id']);
        return $goods;
    }

    /**
     * 保存浏览量
     */
    private function syncPV($goods, $buyerId){
        $param = array(
            'timestamp' => time(),
            'noncestr'  => \Org\Util\String2::randString(16),
            'goods_id' => $goods['goods_id'],
            'cat_id'   => $goods['cat_id'],
            'buyer_id' => $buyerId,
            'shop_id'  => $goods['shop_id']
        );
        $redis = new Redis();
        $redis->lPublish('MessageViewed', $param);
    }

    public function goodsActionHandler($goods, $buyer){
        $recharge = '/agent/recharge';
        $collection = array('class' => 'js-add-collect collect'.($goods['collection_id'] ? '  checked' : ''), 'data-id' => $goods['goods_id'], 'link' => 'javascript:;');
        $shop = array('class' => 'shop-main', 'link' => $buyer['url'].'/shop?id='.$goods['shop_id']);
        $kefu = array('class' => 'kefu js-lxkf', 'data-id' => $goods['shop_id'], 'data-goods' => $goods['goods_id'], 'link' => 'javascript:;');
        $cart = array('class' => 'cart js-cart-num'.($goods['cart_quantity'] ? ' has-cart' : ''), 'link' => $buyer['url'].'/cart');
        $left = $action = array();
        if($goods['is_display'] == 0){
            $left[] = $kefu;
            $left[] = $cart;
            $action[] = array('id' => '', 'text' => '已下架，该商品已售罄', 'disabled' => 0, 'class' => 'btn btn-orange', 'href' => $recharge);
        }else if($goods['stock'] < 1){
            $left[] = $kefu;
            $left[] = $cart;
            $action[] = array('id' => 'btnUpLevel', 'text' => '已售罄，去参加推广计划赚佣金吧', 'disabled' => 0, 'class' => 'btn btn-orange', 'href' => $recharge);
        }else if($goods['level_quota'] && !in_array($buyer['card_id'], $goods['level_quota'])){
            $left[] = $kefu;
            $left[] = $cart;
            $cards = C('MEMBER_CARD');
            $join = '';
            foreach ($cards as $cardId=>$info){
                if(in_array($cardId, $goods['level_quota'])){
                    $join .= $join == '' ? $info['title'] : '、'.$info['title'];
                }
            }
            $action[] = array('id' => 'btnUpLevel', 'text' => '仅限'.$join.'参与', 'disabled' => 0, 'class' => 'btn btn-orange', 'href' => $recharge);
        }else if(NOW_TIME < $goods['sold_time']){
            $left[] = $shop;
            $left[] = $kefu;
            $left[] = $cart;
            $action[] = array('id' => 'addCart', 'text' => '加入购物车', 'disabled' => 0, 'class' => 'btn btn-orange');
            $action[] = array('id' => 'buyNow', 'text' => '即将开售', 'disabled' => 1, 'class' => 'btn btn-orange-dark');
        }else{
            $left[] = $shop;
            $left[] = $kefu;
            $left[] = $cart;
            $action[] = array('id' => 'buyNow', 'text' => '立即下单', 'disabled' => 0, 'class' => 'btn btn-orange-dark', 'link' => 'javascript:;');
            $action[] = array('id' => 'addCart', 'text' => '加入购物车', 'disabled' => 0, 'class' => 'btn btn-orange', 'link' => 'javascript:;');
        }
        return array('left' => $left, 'right' => $action);
    }

    /**
     * 获取商品的sku
     * @param unknown $goodsId
     */
    public function getSKU($params, $login){
        if(!is_numeric($params['id'])){
            $this->error = "参数错误";
            return;
        }
        $sql = "SELECT goods.id AS goods_id, goods.title, goods.cat_id, goods.tag_id, goods.shop_id,goods.freight_id,
                	goods.price, goods.original_price, goods.custom_price, goods.member_discount, goods.score,
                	goods.weight, goods.stock, goods.is_display, goods.sold_time, content.sku_json, goods.tao_id, shop.aliid,
                	goods.level_quota, goods.buy_quota, goods.day_quota, goods.every_quota, goods.min_order_quantity,
                	goods.activity_id, goods.retail_price, content.sku_json, goods.pic_url, shop.mid AS shop_mid
                FROM mall_goods AS goods
                INNER JOIN mall_goods_content AS content ON content.goods_id = goods.id
                INNER JOIN shop ON shop.id=goods.shop_id
                WHERE goods.id='{$params['id']}' AND goods.is_del=0";
        $goods = $this->query($sql);
        if(empty($goods)){
            $this->error = '商品不存在';
            return;
        }
        $goods = $goods[0];

        $goods['project_id'] = IdWork::getProjectId($goods['shop_id']);
        $login = $this->getProjectMember($login, $goods['project_id']);

        // 强制活动预览
        $activityPreview = $params['activity_id'] > 0 && $params['activity_id'] == $goods['activity_id'];
        $goods['quantity'] = $goods['min_order_quantity'];
        $goods = $this->goodsDetailHandler($goods, $login, $activityPreview);

        // 保存浏览量
        $this->syncPV($goods, $login['id']);
        return $goods;
    }
}
?>
