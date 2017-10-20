<?php
namespace Common\Model;

class ActivityModel extends GoodsModel{
    protected $MainTag     = '';
    protected $PriceTitle  = '活动价';
    protected $ActivityTag = 0;

    public function __construct($ActivityType){
        parent::__construct();

        $acitivity = ActivityType::getById($ActivityType);
        $this->MainTag = $acitivity['main_tag'];
        $this->ActivityTag = $acitivity['id'];
    }

    public function canJoin($goodsId, $activeId){
        $goods = $this->query("SELECT id, cat_id, tag_id, price, score, title, shop_id, stock, freight_id, activity_id, is_display FROM mall_goods WHERE id='{$goodsId}' AND is_del=0");
        if(empty($goods)){
            $this->error = '商品不存在';
            return false;
        }
        $goods = $goods[0];
        $goods['tag_id'] = $goods['tag_id'] ? explode(',', $goods['tag_id']) : array();

        // 检测商品是否已参加其他活动
        if($goods['activity_id'] > 0){
            $active = $this->getActivity($goods['activity_id']);
            if($active){
                $Model = new $active['model']();
                $goods = $Model->isExpired($goods);
                if($goods['activity_id'] > 0 && $goods['activity_id'] != $activeId){
                    $this->error = '该商品正在参加其他活动：'.$Model->MainTag;
                    return false;
                }
            }
        }

        $products = $this->query("SELECT id, stock, price, score, sku_json, cost FROM mall_product WHERE goods_id=".$goodsId);
        foreach ($products as $i=>$item){
            $item['spec'] = get_spec_name($item['sku_json']);
            if(strlen($item['spec']) == 0){
                $item['spec'] = '无';
            }
            unset($item['sku_json']);
            $products[$i] = $item;
        }
        $goods['products'] = $products;
        return $goods;
    }

    /**
     * 覆盖商品详情
     */
    public function coverDetail($params, $goods, $buyer){

    }


    /**
     * 获取页面显示的按钮
     */
    public function getBtnAction($goods, $activity, $buyer){
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
            $cards = get_member_card($goods['project_id']);
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

    }

    /**
     * 覆盖购物车/结算数据
     * 购物车显示仍然显示活动信息
     * 结算时如果不符合活动条件则显示原有信息
     */
    private function coverSettlement($goods, $activity, $buyer){

    }

    /**
     * 覆盖公共数据
     */
    private function coverData($goods, $activity, $buyer){

    }

    /**
     * 页面显示的价格
     */
    private function coverViewPrice($goods, $buyer, $priceTile = ''){

    }

    /**
     * 卸载活动标记
     */
    public function unsetActivity($goods){
        $tags = is_array($goods['tag_id']) ? $goods['tag_id'] : ($goods['tag_id'] ? explode(',', $goods['tag_id']) : array());
        $tagIndex = array_search($this->ActivityType, $tags);
        if($tagIndex > -1){
            unset($tags[$tagIndex]);
            $tagStr = implode(',', $tags);
            $this->execute("UPDATE mall_goods SET tag_id='{$tagStr}', activity_id=0 WHERE id='{$goods['goods_id']}' AND activity_id='{$goods['activity_id']}'");
        }
        $goods['activity_id'] = 0;
        return $goods;
    }

    /**
     * 搜索(专项列表)
     */
    public function search($params, $login){

    }

    /**
     * 检测活动是否过期
     * @param unknown $activityId
     */
    public function isExpired($activityId){
        return 1;
    }
}
?>
