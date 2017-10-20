<?php
namespace H5\Model;
use Common\Model\BaseModel;
use Common\Model\ActivityType;

class GrouponModel extends BaseModel{
    private $MainTag     = '团购返现';
    private $PriceTitle  = '团购价';
    private $ActivityTag = ActivityType::GROUPON;

    /**
     * 商品详情
     */
    public function coverDetail($params, $goods, $buyer)
    {
        $detailField = isset($goods['detail']) ? ',activity.detail, activity.back_config' : '';
        $activityId = substr($goods['activity_id'], 4);
        $sql = "SELECT activity.id AS activity_id, activity.title, activity.main_tag, activity.price, activity.price_title,
                    activity.price, activity.score, activity.start_time, activity.end_time, activity.total, activity.sold,
                    activity.level_quota, activity.buy_quota, activity.products, activity.min_order_quantity{$detailField}
                FROM mall_groupon AS activity
                WHERE activity.id='{$params['id']}'";
        $activity = $this->query($sql);
        if(empty($activity)){
            $this->error = '活动不存在';
            return $goods;
        }
        $activity = $activity[0];

        $activityId = $this->ActivityTag.$activity['activity_id'];

        // 活动标记 - 文字
        if(empty($activity['main_tag'])){
            $activity['main_tag'] = $this->MainTag;
        }

        // 活动结束 - 卸载活动标记
        if(NOW_TIME > $activity['end_time']){
            $goods = $this->unsetActivity($goods);
            if(!$params['preview']){
                return $goods;
            }
        }else if(NOW_TIME < $activity['start_time']){
            $goods['redirect_link'] = array(
                'url'  => $buyer['url'].'/goods?id='.$goods['goods_id'].'&activity_id='.$activityId,
                'text' => $activity['main_tag']
            );
            if(!$params['preview']){
                return $goods;
            }
        }

        // 活动倒计时
        if(NOW_TIME < $activity['start_time']){
            $goods['progress'] = 0;
            $goods['countdown'] = array('type' => 'start', 'start' => NOW_TIME, 'end' => $activity['start_time']);
        }else{
            $goods['progress'] = bcdiv($goods['sold'], $goods['total'], 4) * 100;
            $goods['countdown'] = array('type' => 'end', 'start' => NOW_TIME, 'end' => $activity['end_time']);
        }

        $activity['products'] = json_decode($activity['products'], true);
        $activity['level_quota'] = $activity['level_quota'] === '' ? array() : explode(',', $activity['level_quota']);

        // 覆盖公共数据
        $goods = $this->coverData($goods, $activity, $buyer);

        $goods['stock'] = 0;
        foreach ($goods['products'] as $i=>$product){
            $config = $activity['products'][$product['id']];
            $product['stock'] = $this->getCacheStock('p'.$product['id'], $config['total'] - $config['sold'], $activity['start_time'], $activity['end_time']);
            if(!$goods['is_score']){
                $product['original_price'] = $product['price'];
            }
            $product['price'] = $config['price'];
            $product['score'] = $config['score'];
            $goods['stock']  += $product['stock'];
            $product = $this->coverViewPrice($product, $buyer, $activity['price_title']);
            $goods['products'][$i] = $product;
        }

        // 佣金比例
        $goods['agent_rate'] = array('is_join' => 0, 'message' => '团购返现不参与推广无佣金');
        // 页面显示的按钮
        $goods['action'] = $this->getGoodsBtnAction($goods, $activity, $buyer);
        return $goods;
    }

    /**
     * 获取页面显示的按钮
     */
    public function getGoodsBtnAction($goods, $activity, $buyer)
    {
        $recharge = '/agent/recharge';
        $collection = array('class' => 'js-add-collect collect'.($goods['collection_id'] ? '  checked' : ''), 'data-id' => $goods['goods_id'], 'link' => 'javascript:;');
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
        }else if(NOW_TIME < $activity['start_time']){
            $left[] = $collection;
            $left[] = $kefu;
            $left[] = $cart;
            $action[] = array('id' => 'addCart', 'text' => '活动未开始，加入购物车抢购', 'disabled' => 0, 'class' => 'btn btn-orange');
        }else if(NOW_TIME > $activity['end_time']){
            $left[] = $kefu;
            $left[] = $cart;
            $action[] = array('id' => 'btnUpLevel', 'text' => '活动结束，去参加推广计划赚佣金吧', 'disabled' => 0, 'class' => 'btn btn-orange', 'href' => $recharge);
        }else{
            $left[] = $collection;
            $left[] = $kefu;
            $left[] = $cart;
            $action[] = array('id' => 'addCart', 'text' => '加入购物车', 'disabled' => 0, 'class' => 'btn btn-orange', 'link' => 'javascript:;');
            $action[] = array('id' => 'buyNow', 'text' => '立即购买', 'disabled' => 0, 'class' => 'btn btn-orange-dark', 'link' => 'javascript:;');
        }
        return array('left' => $left, 'right' => $action);
    }

    /**
    * 覆盖全部商品列表中的数据
    */
    public function coverGoodsList($activityGoodsList, $buyer, $settlement = false){
        $activityId = array_keys($activityGoodsList);
        $activityId = implode(',', $activityId);
        $sql = "SELECT activity.id AS activity_id, activity.title, goods_id, activity.main_tag, activity.price_title,
                    activity.price, activity.score, activity.start_time, activity.end_time, activity.total, activity.sold,
                    ".($settlement ? "activity.products, activity.level_quota, activity.buy_quota, activity.min_order_quantity" : "pic_url")."
                FROM mall_groupon AS activity
                WHERE activity.id IN ({$activityId})";
        $activityList = $this->query($sql);

        // 逐行解析
        $list = array();
        foreach ($activityList as $activity){
            // 默认标记
            if(empty($activity['main_tag'])){
                $activity['main_tag'] = $this->MainTag;
            }
            //默认价格标题
            if(empty($activity['price_title'])){
                $activity['price_title'] = $this->PriceTitle;
            }
            // 解析产品
            if($settlement){
                $activity['products'] = json_decode($activity['products'], true);
            }

            if(isset($activity['level_quota'])){
                $activity['level_quota'] = $activity['level_quota'] === '' ? array() : explode(',', $activity['level_quota']);
            }

            // 覆盖产品数据
            $goodsList = $activityGoodsList[$activity['activity_id']];
            foreach ($goodsList as $i=>$goods){
                // 如果已结束则卸载活动标记
                if(NOW_TIME >= $activity['end_time']){
                    $goods = $this->unsetActivity($goods);
                }

                if($settlement){ // 结算
                    $goods = $this->coverSettlement($goods, $activity, $buyer[$goods['project_id']]);
                }else if(NOW_TIME < $activity['start_time']){   // 活动未开始只打参加某活动标记
                    $goods['main_tag'] = $activity['main_tag'];
                    $goods['activity_id'] = 0;
                }else if(NOW_TIME < $activity['end_time']){ // 活动进行中
                    $goods = $this->coverData($goods, $activity, $buyer[$goods['project_id']]);
                }

                $list[$i] = $goods;
                unset($goodsList[$i]);
            }
            unset($activityGoodsList[$activity['activity_id']]);
        }

        // 防止漏下数据
        foreach($activityGoodsList as $goodsList){
            foreach ($goodsList as $i=>$goods){
                $list[$i] = $this->unsetActivity($goods);
            }
        }

        return $list;
    }

    /**
     * 覆盖购物车/结算数据
     * 购物车显示仍然显示活动信息
     * 结算时如果不符合活动条件则显示原有信息
     */
    private function coverSettlement($goods, $activity, $buyer){
        $goods['title']    = $activity['title'];
        $goods['main_tag'] = $activity['main_tag'];

        if(NOW_TIME >= $activity['end_time']){
            $goods['settlement']['errmsg'] = '活动已结束';
            $goods['settlement']['can_buy'] = 0;
            $goods['settlement']['invalid'] = 1;
            return $goods;
        }

        $config = $activity['products'][$goods['product_id']];
        if(!$config){
            $goods['settlement']['errmsg'] = '规格已变更，请重新选择';
            $goods['settlement']['can_buy'] = 0;
            $goods['settlement']['invalid'] = 1;
            $goods['link'] = $buyer['url'].'/mall?cat_id='.$goods['cat_id'];
            return $goods;
        }

        if(NOW_TIME < $activity['start_time']){
            $goods['settlement']['errmsg'] = '活动未开始';
            $goods['settlement']['can_buy'] = 0;
            $goods['settlement']['invalid'] = 0;
        }

        $activity['total'] = $config['total'];
        $activity['sold']  = $config['sold'];
        $activity['price'] = $config['price'];
        $activity['score'] = $config['score'];
        $goods = $this->coverData($goods, $activity, $buyer);
        return $goods;
    }

    /**
     * 覆盖数据
     */
    private function coverData($goods, $activity, $buyer){
        $goods['buy_quota']   = $activity['buy_quota'];
        $goods['level_quota'] = $activity['level_quota'];
        $goods['min_order_quantity'] = $activity['min_order_quantity'];
        $goods['member_discount'] = 0;
        $goods['other_discount'] = 0;

        // 活动标记
        $goods['main_tag'] = $activity['main_tag'];
        // 详情链接
        $goods['link'] = $buyer['url'].'/goods?id='.$goods['goods_id'].'&activity_id='.$this->ActivityTag.$activity['activity_id'];
        // 商品标题
        $goods['title'] = $activity['title'];
        // 活动库存
        $key = isset($goods['product_id']) ? 'p'.$goods['product_id'] : 'g'.$goods['goods_id'];
        $goods['stock'] = $this->getCacheStock($key , $activity['total'] - $activity['sold'], $activity['start_time'], $activity['end_time']);
        // 活动销量
        $activity['sold'] = $activity['total'] - $goods['stock'];
        // 原购买价格
        if(!$goods['is_score']){
            $goods['original_price'] = $goods['price'];
        }
        // 省
        $sheng = $goods['is_score'] ? bcsub($goods['score'], $activity['score'], 2) : bcsub($goods['original_price'], $activity['price'], 2);
        // 现价
        $goods['price'] = $activity['price'];
        $goods['score'] = $activity['score'];
        // 页面显示的价格
        $goods = $this->coverViewPrice($goods, $buyer, $activity['price_title']);
        // 商品活动信息
        $goods['activity'] = array(
            'id'             => $activity['activity_id'],
            'name'           => $activity['title'],
            'type'           => $this->ActivityTag,
            'main_tag'       => $activity['main_tag'],
            'start_time'     => $activity['start_time'],
            'end_time'       => $activity['end_time'],
            'discount_fee'   => $sheng,
            'description'    => '反现金',
        );

        if(isset($activity['back_config'])){
            $back = end($activity['back_config']);
            $goods['activity']['description'] = '最高返'.($back['balance'] + $back['wallet']).'元';
            $goods['activity']['back'] = $activity['back_config'];
        }

        if(isset($goods['detail']) && !empty($activity['detail'])){
            $goods['detail'] = $activity['detail'];
        }
        return $goods;
    }

    /**
     * 页面显示的价格
     */
    private function coverViewPrice($goods, $buyer, $priceTile = ''){
        if(!$priceTile){
            $priceTile = $this->PriceTitle;
        }

        // 必须使用积分
        if($goods['is_score']){
            $goods["view_price"] = array(
                array('title' => $priceTile, 'price' => $goods['score'], 'prefix' => '','suffix' => '积分'),
                array('title' => '原　价', 'price' => sprintf('%.2f', $goods['original_price']), 'prefix' => '¥','suffix' => '')
            );
        }else{
            $price = split_money($goods['price']);
            $goods["view_price"] = array(
                array('title' => $priceTile, 'price' => $price[0], 'prefix' => '¥','suffix' => $price[1]),
                array('title' => '原　价', 'price' => sprintf('%.2f', $goods['original_price']), 'prefix' => '¥','suffix' => '')
            );
        }

        return $goods;
    }

    /**
     * 卸载活动标记
     */
    public function unsetActivity($goods){
        $tagIndex = array_search($this->ActivityTag, $goods['tag_id']);
        if($tagIndex > -1){
            unset($goods['tag_id'][$tagIndex]);
            $tagStr = implode(',', $goods['tag_id']);
            $this->execute("UPDATE mall_goods SET tag_id='{$tagStr}', activity_id=0 WHERE id='{$goods['goods_id']}' AND activity_id='{$goods['activity_id']}'");
        }
        $goods['activity_id'] = 0;
        return $goods;
    }

    /**
     * 专项列表
     */
    public function search($params, $login){
        $offset = is_numeric($params['offset']) ? $params['offset'] : 0;
        $size = is_numeric($params['size']) ? $params['size'] : 38;

        $where = array();
        if(is_numeric($params['shop_id'])){
            $where[] = "activity.shop_id=".$params['shop_id'];
        }else if(is_numeric($params['project_id'])){
            $where[] = "activity.shop_id BETWEEN {$params['project_id']}001 AND {$params['project_id']}999";
        }

        if($params['status'] == 'waiting'){
            $where[] = " activity.end_time>".NOW_TIME." AND activity.start_time>".NOW_TIME;
        }else{
            $where[] = NOW_TIME." BETWEEN activity.start_time AND activity.end_time";
        }
        $where[] = "goods.is_display='1'";
        $where   = "WHERE ".implode(' AND ', $where);

        $sql = "SELECT activity.id AS activity_id, goods.id AS goods_id, activity.title, goods.cat_id, goods.tag_id,
                    activity.price, activity.score, IF(goods.original_price>goods.price, goods.original_price, goods.price) AS original_price,
                    activity.start_time, activity.end_time, activity.shop_id,
                    IF(activity.pic_url='', goods.pic_url, activity.pic_url) AS pic_url,
                    activity.total, activity.sold, activity.main_tag, activity.price_title,
                    activity.level_quota, activity.buy_quota, activity.min_order_quantity, activity.hide_sold, activity.hide_progress
                FROM mall_groupon AS activity
                INNER JOIN mall_goods AS goods ON goods.id = activity.goods_id
                {$where}
                LIMIT {$offset}, {$size}";
        $list = $this->query($sql);

        // 获取project_id
        $projectId = array();
        foreach ($list as $i=>$goods){
            $goods['project_id'] = get_project_id($goods['shop_id']);
            $list[$i] = $goods;
            $projectId[] = $goods['project_id'];
        }
        $buyerList = $this->getProjectMember($login, $projectId);

        foreach ($list as $i=>$goods){
            $goods['tag_id'] = $goods['tag_id'] ? explode(',', $goods['tag_id']) : array();
            $goods['is_score'] = in_array('score', $goods['tag_id']);
            $buyer = $buyerList[$goods['project_id']];
            $goods['other_discount'] = 0;
            $goods['activity_id'] = $this->ActivityTag.$goods['activity_id'];
            $goods['link'] = $buyer['url'].'/goods?id='.$goods['goods_id'].'&activity_id='.$goods['activity_id'];
            // 页面显示的价格
            $goods = $this->coverViewPrice($goods, $buyer, $goods['price_title']);

            // 活动倒计时
            if(NOW_TIME < $goods['start_time']){
                $goods['progress'] = 0;
                $goods['countdown'] = array('type' => 'start', 'start' => NOW_TIME, 'end' => $goods['start_time']);
            }else{
                $goods['stock'] = $this->getCacheStock('g'.$goods['goods_id'], $goods['total'] - $goods['sold'], $goods['start_time'], $goods['end_time']);
                $goods['sold'] = $goods['total'] - $goods['stock'];

                $goods['progress'] = bcdiv($goods['sold'], $goods['total'], 4) * 100;
                $goods['countdown'] = array('type' => 'end', 'start' => NOW_TIME, 'end' => $goods['end_time']);
            }

            // 标记
            $goods['notice'] = '';
            if($goods['min_order_quantity'] > 1){
                $goods['notice'] = $goods['min_order_quantity'].'件起批';
            }else if($goods['buy_quota'] > 0){
                $goods['notice'] = '每人限购'.$goods['buy_quota'].'件';
            }else if($goods['level_quota'] !== ''){
                $cards = explode(',', $goods['level_quota']);
                if(!in_array($buyer['card_id'], $cards)){
                    $goods['notice'] = $buyer['agent_title'].'不可购买';
                }
            }

            unset($goods['price_title']);
            unset($goods['main_tag']);
            unset($goods['start_time']);
            unset($goods['end_time']);
            unset($goods['total']);
            unset($goods['project_id']);
            $list[$i] = $goods;
        }
        return $list;
    }

    /**
     * 同步库存信息
     * 请注意：已开启事务
     */
    public function syncStock($param){
        $id = $param['id'];
        $this->startTrans(); // 开启事务，他人修改数据请等待
        $activity = $this->field("products")->find($id);
        if(!empty($activity)){
            $products = json_decode($activity['products'], true);
            $products[$param['product_id']]['sold'] -= $param['quantity'];
            $products = json_encode($products, JSON_UNESCAPED_UNICODE);
            $this->execute("UPDATE mall_groupon SET sold=sold-'{$param['quantity']}', products='{$products}' WHERE id={$id}");
        }
        $this->commit();
    }
}
?>
