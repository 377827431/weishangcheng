<?php
namespace H5\Model;
use Common\Model\ActivityModel;
use Org\IdWork;
use Common\Model\ActivityType;

class ZeroModel extends ActivityModel{
    function __construct(){
        parent::__construct(ActivityType::ZERO);
    }
    
    /**
     * 商品详情
     */
    public function coverDetail($params, $goods, $buyer)
    {
        $field = isset($goods['detail']) ? ',activity.detail' : '';
        $sql = "SELECT activity.id AS activity_id, activity.title, activity.price, activity.score, activity.main_tag, activity.price_title,
                    activity.level_quota, activity.buy_quota, activity.start_time, activity.end_time, activity.total,
                    activity.sold, activity.vsold,
                    activity.freight_id, activity.products{$field}
                FROM mall_zero AS activity
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
        
        $goods['price_type'] = 0;
        
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
            $product['price'] = $activity['price'];
            $product['score'] = $activity['score'];
            $goods['stock']  += $product['stock'];
            $product = $this->coverViewPrice($product, $buyer, $activity['price_title']);
            $goods['products'][$i] = $product;
        }
        $goods['stock'] -= $goods['vsold'];
        
        // 页面显示的按钮
        $goods['action'] = $this->getBtnAction($goods, $activity, $buyer);
        return $goods;
    }
    
    /**
     * 覆盖全部商品列表中的数据
     */
    public function coverGoodsList($activityGoodsList, $buyer, $settlement = false){
        $activityId = array_keys($activityGoodsList);
        $activityId = implode(',', $activityId);
        $sql = "SELECT activity.id AS activity_id, activity.title, activity.main_tag, activity.price_title,
                    activity.start_time, activity.end_time, goods_id,
                    activity.total, activity.sold, activity.price, activity.score
                    ".($settlement ? ", activity.products, activity.freight_id, level_quota, buy_quota" : "")."
                FROM mall_zero AS activity
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
                // 如果原价比活动价还便宜，则不显示优惠信息
                $originalPrice = $this->getMemberPrice($goods, $buyer);
                if($originalPrice < $activity['price']){
                    $goods['activity_id'] = 0;
                }else{
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
        $goods = $this->coverData($goods, $activity, $buyer);
        return $goods;
    }
    
    /**
     * 覆盖通用数据
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
        $activity['sold'] = $activity['total'] - $goods['stock'] + $activity['vsold'];
        // 原购买价格
        if(!$goods['is_score']){
            $goods['original_price'] = $this->getMemberPrice($goods, $buyer);
        }
        // 省
        $sheng = $goods['is_score'] ? bcsub($goods['score'], $activity['score'], 2) : bcsub($goods['original_price'], $activity['price'], 2);
        // 活动价
        $goods['score'] = $activity['score'];
        $goods['price'] = $activity['price'];
        // 页面显示的价格
        $goods = $this->coverViewPrice($goods, $buyer, $activity['price_title']);
        // 商品活动信息
        $goods['activity'] = array(
            'id'             => $activity['activity_id'],
            'name'           => $activity['title'],
            'type'           => $this->ActivityType,
            'main_tag'       => $activity['main_tag'],
            'start_time'     => $activity['start_time'],
            'end_time'       => $activity['end_time'],
            'discount_fee'   => floatval($sheng),
            'description'    => '已抢'.$activity['sold'].'件'
        );
        
        if(!isset($goods['product_id'])){
            // 主图
            if($activity['pic_url']){$goods['pic_url'] = $activity['pic_url'];}
            // 商品详情
            if(!empty($activity['detail'])){$goods['detail'] = $activity['detail'];}
        }
        
        // 卸载包邮标记
        $baoyou = array_search(999, $goods['tag_id']);
        if($baoyou > -1){
            unset($goods['tag_id'][$baoyou]);
        }
        // 运费模板
        $goods['freight_id'] = $activity['freight_id'];
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
    public function unsetactivity($goods){
        $tagIndex = array_search($this->ActivityType, $goods['tag_id']);
        if($tagIndex > -1){
            unset($goods['tag_id'][$tagIndex]);
            $tagStr = implode(',', $goods['tag_id']);
            $this->execute("UPDATE mall_goods SET tag_id='{$tagStr}', activity_id=0 WHERE id='{$goods['goods_id']}' AND activity_id='{$goods['activity_id']}'");
        }
        $goods['activity_id'] = 0;
        return $goods;
    }

    /**
     * 零元购列表
     */
    public function search($params, $login){
        $offset = is_numeric($params['offset']) ? $params['offset'] : 0;
        $size = is_numeric($params['size']) ? $params['size'] : 38;
        
        $where = array();
        if(is_numeric($params['shop_id'])){
            $where[] = "activity.shop_id=".$params['shop_id'];
        }else if(is_numeric($params['project_id'])){
            $where[] = "(activity.shop_id BETWEEN {$params['project_id']}001 AND {$params['project_id']}999)";
        }
        if($params['status'] == 'waiting'){
            $where[] = " activity.end_time>".NOW_TIME." AND activity.start_time>".NOW_TIME;
        }else{
            $where[] = NOW_TIME." BETWEEN activity.start_time AND activity.end_time";
        }
        $where[] = "goods.is_display='1'";
        $where   = "WHERE ".implode(' AND ', $where);
        
        $sql = "SELECT activity.id AS activity_id, goods.id AS goods_id, activity.title, goods.cat_id, goods.tag_id,
                    activity.price, activity.score, IF(goods.original_price > goods.price, goods.original_price, goods.price) AS original_price,
                    activity.start_time, activity.end_time, activity.shop_id,
                    IF(activity.pic_url='', goods.pic_url, activity.pic_url) AS pic_url,
                    activity.total, activity.sold, activity.vsold, activity.main_tag, activity.price_title,
                    activity.buy_quota, activity.min_order_quantity
                FROM mall_zero AS activity
                INNER JOIN mall_goods AS goods ON goods.id = activity.goods_id
                {$where}
                LIMIT {$offset}, {$size}";
        $list = $this->query($sql);
        if(count($list) == 0){
            return $list;
        }
        
        // 获取project_id
        $projectId = array();
        foreach ($list as $i=>$goods){
            $goods['project_id'] = IdWork::getProjectId($goods['shop_id']);
            $list[$i] = $goods;
            $projectId[] = $goods['project_id'];
        }
        $buyerList = $this->getProjectMember($login, $projectId);
        $config = get_project_config(PROJECT_ID, 'zero_config', array('hide_sold' => 1, 'tyle' => 'count_down'));
        
        foreach ($list as $i=>$goods){
            $buyer = $buyerList[$goods['project_id']];
            $goods['tag_id'] = $goods['tag_id'] ? explode(',', $goods['tag_id']) : array();
            $goods['price_type'] = 0;
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
                $goods['sold'] = $goods['total'] - $goods['stock'] + $goods['vsold'];
                
                $goods['progress'] = bcdiv($goods['sold'], $goods['total'], 4);
                $goods['progress'] = bcmul($goods['progress'], 100, 2);
                $goods['progress'] = $goods['progress'] > 100 ? 100 : floatval($goods['progress']);
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

            $goods['hide_progress'] = $config['tyle'] != 'count_down';
            $goods['hide_sold'] = $config['hide_sold'];
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
            $this->execute("UPDATE mall_zero SET sold=sold-'{$param['quantity']}', products='{$products}' WHERE id={$id}");
        }
        $this->commit();
    }
    
    /**
     * 检测活动是否过期
     * @param unknown $activityId
     */
    public function isExpired($goods){
        $activityId = IdWork::getActivityRealId($goods['activity_id']);
        $activity = $this->query("SELECT goods_id, end_time FROM {$this->tableName} WHERE id='{$activityId}'");
        if(!$activity){
            return $goods;
        }
        
        $activity = $activity[0];
        if(time() > $activity['end_time']){
            $this->unsetActivity($goods);
        }
        return $goods;
    }
}
?>