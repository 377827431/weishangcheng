<?php 
namespace Common\Model;
use Org\IdWork;

class GoodsModel extends BaseModel{
    protected $tableName = 'mall_goods';
    
    /**
     * 会员价
     */
    public function getMemberPrice(&$goods, $buyer){
        $useDiscount = $goods['member_discount'];
        $result_price = $goods['price'];
        $result_score = $goods['score'];
        $price_title = $buyer['price_title'];
    
        if($goods['price_type'] == 0){// 普通价
    
        }else if($goods['price_type'] == 1){// 区间价
            $quantity = isset($goods['quantity']) ? $goods['quantity'] : 1;
            foreach ($goods['custom_price'] as $min=>$price){
                if($quantity >= $min){
                    $result_price = $price;
                }
            }
        }else if($goods['price_type'] == 2){// 会员价
            $useDiscount = 0;
            if(isset($goods['custom_price'][$buyer['card_id']])){
                $result_price = $goods['custom_price'][$buyer['card_id']];
                if($result_price <= 0){
                    $result_price = bcsub($goods['price'], $result_price);
                }
            }
        }else if($goods['price_type'] == 3){// 积分价
            $useDiscount = 0;
            if(isset($goods['custom_price'][$buyer['card_id']])){
                $result_price = $goods['custom_price'][$buyer['card_id']][0];
                $result_score = $goods['custom_price'][$buyer['card_id']][1];
            }
        }else if($goods['price_type'] == 4){// 单品代理
            $useDiscount = 0;
            $quantity = isset($goods['quantity']) ? $goods['quantity'] : 1;
            $goods['agent']['target'] = 0;
            $key = key($goods['custom_price']);
    
            foreach ($goods['custom_price'] as $gid=>$item){
                // 已经成为代理了
                if(in_array($gid, $buyer['agents'])){
                    $result_price = $item['price'];
                    $goods['min_order_quantity'] = $item['second']; // 最低补货数量
                    $goods['level_quota'] = array();    // 不限购
                    $goods['buy_quota'] = 0;    // 不限购
                    if(isset($goods['settlement'])){
                        $level = substr($gid, -1);
                        $goods['settlement']['errmsg'] = $goods['agent']['title'].'“'.$goods['agent']['items'][$level]['title'].'”补货';
                        $goods['agent']['target'] = $level;
                    }
                }
                // 零售或补货
                else if($quantity >= $item['first']){
                    $result_price = $item['price'];
                    $goods['min_order_quantity'] = $item['second']; // 最低补货数量
                    $goods['level_quota'] = array();    // 不限购
                    $goods['buy_quota'] = 0;    // 不限购
                    if(isset($goods['settlement'])){
                        $level = substr($gid, -1);
                        $goods['settlement']['errmsg'] = '升级为'.$goods['agent']['title'].'“'.$goods['agent']['items'][$level]['title'].'”';
                        $goods['agent']['target'] = $level;
                    }
                }
            }
        }
    
        // 会员折扣
        if($useDiscount && $buyer['discount'] > 0 && $buyer['discount'] < 1){
            $result_price = bcmul($result_price, $buyer['discount'], 2);
        }
    
        return array('price' => floatval($result_price), 'score' => floatval($result_score), 'price_title' => $price_title);
    }
    
    public function getPriceType($tagArray){
        if(in_array(201, $tagArray)){
            return 1;
        }else if(in_array(202, $tagArray)){
            return 2;
        }else if(in_array(203, $tagArray)){
            return 3;
        }else if(in_array(204, $tagArray)){
            return 4;
        }
        return 0;
    }
    
    /**
     * 批量处理商品
     */
    public function goodsListHandler($list, &$login){
        if(!$list){
            return $list;
        }
        // 是否为结算
        $isSettlement = isset(current($list)['settlement']);
        // 活动商品
        $activityList = array();
        // 所查询的项目id
        $projectIds = array();
        // 普通商品
        $normalList = array();
        // 区间价商品
        $rangeList = array();
    
        foreach($list as $i=>$goods){
            // 商品标记
            $goods['tag_id'] = $goods['tag_id'] == '' ? array() : explode(',', $goods['tag_id']);
            $goods['price_type'] = $this->getPriceType($goods['tag_id']);
            // 价格组合
            if($goods['custom_price'] != ''){
                $goods['custom_price'] = json_decode($goods['custom_price'], true);
            }
    
            // 允许使用其他优惠
            $goods['other_discount'] = 1;
            // 商品主标签
            $goods['main_tag'] = '';
            // 项目id
            $goods['project_id'] = IdWork::getProjectId($goods['shop_id']);
            $projectIds[] = $goods['project_id'];
    
            // 参加的活动
            if($goods['activity_id'] > 0){
                $activity = ActivityType::getById($goods['activity_id']);
                if(!$activity){
                    $goods['activity_id'] = 0;
                }else{
                    if(!isset($activityList[$activity['type']])){
                        $activityList[$activity['type']] = array('model' => $activity['model'], 'items' => array());
                    }
                    $activityList[$activity['type']]['items'][$activity['id']][$i] = $goods;
                    continue;
                }
            }else if($isSettlement){
                if($goods['price_type'] == 1){// 合并区间价
                    $key = $goods['goods_id'];
                    foreach ($goods['custom_price'] as $quantity=>$price){
                        $key .= '_'.$quantity.'_'.$price;
                    }
    
                    $quantity = $goods['settlement']['quantity'];
                    if(!isset($rangeList[$key])){
                        $rangeList[$key] = array('index' => array($i), 'quantity' => $quantity);
                    }else{
                        $rangeList[$key]['index'][] = $i;
                        $rangeList[$key]['quantity'] += $quantity;
                    }
                }else if($goods['price_type'] == 4){    // 单品代理合并价格
                    $key = 'agent';
                    foreach ($goods['custom_price'] as $gid=>$price){
                        $key .= '_'.$gid.'_'.$price['price'].'_'.$price['first'].'_'.$price['second'];
                    }
    
                    $quantity = $goods['settlement']['quantity'];
                    if(!isset($rangeList[$key])){
                        $rangeList[$key] = array('index' => array($i), 'quantity' => $quantity);
                    }else{
                        $rangeList[$key]['index'][] = $i;
                        $rangeList[$key]['quantity'] += $quantity;
                    }
                }
            }
    
            $normalList[$i] = $goods;
        }
    
        // 计算区间价商品价格
        foreach ($rangeList as $item){
            foreach ($item['index'] as $i){
                $normalList[$i]['quantity'] = $item['quantity'];
            }
        }
    
        // 获取对应的买家信息
        $login = $this->getProjectMember($login, $projectIds);
        // 解析普通商品数据
        foreach ($normalList as $i=>$goods){
            $list[$i] = $this->goodsHandler($goods, $login[$goods['project_id']]);
        }
    
        // 活动处理
        foreach ($activityList as $type=>$activity){
            $ActiveModel = new $activity['model']();
            $goodsList = $ActiveModel->coverGoodsList($activity['items'], $login, $isSettlement);
            foreach ($goodsList as $i=>$goods){
                $list[$i] = $this->goodsHandler($goods, $login[$goods['project_id']]);
            }
        }
    
        // 结算处理
        if($isSettlement){
            $list = $this->settlementLimit($list, $login);
        }
    
        // 恢复查询结果排序
        ksort($list);
        return $list;
    }
    
    /**
     * 结算数据处理
     */
    private function settlementLimit($list, $buyer){
        $limitList = array();
        foreach ($list as $i=>$item){
            // 数量限制
            $item['quota'] = $item['stock'];
    
            // 宝贝无效
            if(!$item['product_id'] || $item['is_del']){
                $item['settlement']['errmsg'] = '宝贝不存在';
                $item['settlement']['invalid'] = 1;
                $item['settlement']['can_buy'] = 0;
                if($item['is_del']){
                    $item['link'] = $buyer['url'].'/mall?cat_id='.$item['cat_id'];
                }
            }else if(!$item['is_display']){
                $item['settlement']['errmsg'] = '宝贝已下架';
                $item['settlement']['invalid'] = 1;
                $item['settlement']['can_buy'] = 0;
            }else if($item['stock'] < 1){
                $item['settlement']['errmsg'] = '宝贝已售罄';
                $item['settlement']['invalid'] = 1;
                $item['settlement']['can_buy'] = 0;
            }else if($item['level_quota'] && !in_array($buyer[$item['project_id']]['card_id'], $item['level_quota'])){
                $item['settlement']['errmsg'] = $buyer[$item['project_id']]['agent_title'].'不可购买';
                $item['settlement']['invalid'] = 1;
                $item['settlement']['can_buy'] = 0;
            }else if($item['is_score'] && $buyer[$item['project_id']]['score'] < $item['score']){
                $item['settlement']['errmsg'] = '积分不足';
                $item['settlement']['can_buy'] = 0;
            }
    
            if(!$item['settlement']['can_buy']){
                $list[$i] = $item;
                continue;
            }
    
            // 宝贝不可购买
            if($item['sold_time'] > NOW_TIME){
                $item['settlement']['errmsg'] = date('m月d日 H:i', $item['sold_time']).'开售';
                $item['settlement']['can_buy'] = 0;
            }else if($item['stock'] < $item['settlement']['quantity']){
                $item['settlement']['errmsg'] = '仅剩'.$item['stock'].'件';
                $item['settlement']['can_buy'] = 0;
            }
    
            // 限购 / 最小起订量
            $quota = $item['min_order_quantity'] > 1;
            if($item['buy_quota'] > 0){
                if($item['buy_quota'] < $item['quota']){
                    $item['quota'] = $item['buy_quota'];
                }
                $quota = true;
            }
            if($item['every_quota'] > 0){
                if($item['every_quota'] < $item['quota']){
                    $item['quota'] = $item['every_quota'];
                }
                $quota = true;
            }
            if($item['day_quota'] > 0){
                if($item['day_quota'] < $item['quota']){
                    $item['quota'] = $item['day_quota'];
                }
                $quota = true;
            }
    
            $list[$i] = $item;
            if(!$item['settlement']['can_buy']){
                continue;
            }
    
            if($quota){
                $quantity = $item['settlement']['quantity'];
                if(!isset($limitList[$item['goods_id']])){
                    $limitList[$item['goods_id']]['quantity'] = $quantity;
                    $limitList[$item['goods_id']]['index'] = $i;
                }else{
                    $limitList[$item['goods_id']]['quantity'] += $quantity;
                }
            }
        }
    
        if(count($limitList) == 0){
            return $list;
        }
    
        // 查找购买数量
        $today = date('Ymd');
        foreach ($limitList as $goodsId=>$item){
            $goods = $list[$item['index']];
            $buyerId = $buyer[$item['project_id']]['id'];
    
            // 每人每日限购
            if($goods['every_quota'] > 0){
                $sql = "SELECT SUM(quantity) AS total
                        FROM trade_every_quota
                        WHERE goods_id='{$goodsId}' AND buyer_id='{$buyerId}' AND today='{$today}'";
                $result = $this->query($sql);
    
                $item['every_quota'] = $goods['every_quota'] - $result[0]['total'] ? : 0;
                if($item['quantity'] > $item['every_quota']){
                    $limitList[$goodsId] = $item;
                    continue;
                }
            }
    
            // 日限售
            if($goods['day_quota'] > 0){
                $sql = "SELECT SUM(quantity) AS total
                        FROM trade_day_quota
                        WHERE goods_id='{$goodsId}' AND today='{$today}'";
                $result = $this->query($sql);
    
                $item['day_quota'] = $goods['day_quota'] - $result[0]['total'] ? : 0;
                if($item['quantity'] > $item['day_quota']){
                    $limitList[$goodsId] = $item;
                    continue;
                }
            }
    
            // 每人限购
            if($goods['buy_quota'] > 0){
                $rangeTid = IdWork::getTidRange($goods['activity'] ? $goods['activity']['start_time'] : strtotime('-3 month'));
                $sql = "SELECT SUM(quantity) AS total
                        FROM trade_buyer
                        INNER JOIN trade_order ON trade_order.tid=trade_buyer.tid
                        WHERE trade_buyer.buyer_id='{$buyerId}'
                        AND trade_buyer.tid BETWEEN {$rangeTid[0]} AND {$rangeTid[1]}
                        AND goods_id='{$goodsId}' AND sub_stock=1";
                $result = $this->query($sql);
    
                $item['buy_quota'] = $goods['buy_quota'] - $result[0]['total'] ? : 0;
                if($item['quantity'] > $item['buy_quota']){
                    $limitList[$goodsId] = $item;
                    continue;
                }
            }
    
            $limitList[$goodsId] = $item;
        }
    
        foreach ($list as $i=>$goods){
            if(!isset($limitList[$goods['goods_id']])){
                continue;
            }
    
            $limit = $limitList[$goods['goods_id']];
    
            $errmsg = array();
    
            // 最小起订量
            if($goods['min_order_quantity'] > 1){
                if($limit['quantity'] < $goods['min_order_quantity']){
                    $goods['settlement']['can_buy'] = 0;
                }
                $errmsg[] = '最小起订量'.$goods['min_order_quantity'].'件';
            }
    
            if($goods['buy_quota'] > 0){
                if($limit['quantity'] > $limit['buy_quota']){
                    $goods['settlement']['can_buy'] = 0;
                }
                $errmsg[] = '每人限购'.$goods['buy_quota'].'件';
            }
    
            if($goods['every_quota'] > 0){
                if($limit['quantity'] > $limit['every_quota']){
                    $goods['settlement']['can_buy'] = 0;
                }
                $errmsg[] = '每日限购'.$goods['every_quota'].'件';
            }
    
            if($goods['day_quota'] > 0){
                if($limit['quantity'] > $limit['day_quota']){
                    $goods['settlement']['can_buy'] = 0;
                }
                $errmsg[] = '每日限售'.$goods['day_quota'].'件';
            }
    
            if($goods['settlement']['errmsg'] == '' || $goods['settlement']['can_buy'] == 0){
                $goods['settlement']['errmsg'] = $errmsg[0];
            }
            $list[$i] = $goods;
        }
    
        return $list;
    }
    
    /**
     * 检测1688商品是否该自动下架
     * 每隔10分钟更新一次，如果无tao_id和未上架则不做处理
     */
    private function checkAliGoods($goods){
        if($goods['tao_id'] == 0 || !$goods['is_display']){
            return $goods;
        }
        
        $sql = "SELECT goods.id,goods.freight_id,goods.products,goods.`status`,goods.price_range,ar.relation,ar.last_sync, min_order_quantity
                FROM alibaba_relation AS ar
                INNER JOIN alibaba_goods AS goods ON ar.id = goods.id
                WHERE ar.id='{$goods['tao_id']}' AND token_id='{$goods['aliid']}'";
        $old = $this->query($sql);
        if(!$old){E('数据异常');}
        $old = $old[0];
        
        // 距离上次更新未超过10分钟不做处理
        $timestamp = time();
        if($timestamp - $old['last_sync'] < 600){
            return $goods;
        }
        
        $AlibabaModel = new AlibabaModel();
        $new          = $AlibabaModel->syncGoods($goods['tao_id'], '3027680123', 'id,freight_id,products,`status`,price_range,min_order_quantity', $goods['aliid']);
        $oldSku       = $newSku = null;
        $stock = 0;
        $products = json_decode($new['products'],true);
        foreach ($products as $key => $value) {
            $stock += $value['stock'];
        }
        
        // 是否自动下架
        $takedown = false;
        $message  = '';
        if($stock<=0){
            $takedown = true;
            $message  = '商品已售罄';
        }
        if($new['status'] != 'published'){
            $takedown = true;
            $message  = '商品状态为下架';
        }else if($new['min_order_quantity'] != $old['min_order_quantity']){
            $takedown = true;
            $message  = '最小起批量';
        }
        else if($new['relation'] != $old['relation']){
            $takedown = true;
            $message  = '与您1688账号的代销关系';
        }
        // 检测运费模板是否变更
        else if(!is_numeric($goods['freight_id']) && $goods['freight_id'] != 'T'.$new['freight_id'] || $old['freight_id'] != $new['freight_id']){
            $takedown = true;
            $message  = '运费模板';
        }
        // 检测商品出售状态是否变更
        else if($new['price_range'] != $old['price_range']){
            $takedown = true;
            $message  = '采购价';
        }else{
            // 检测SKU是否变化
            $oldSku = json_decode($old['products'], true);
            $newSku = json_decode($new['products'], true);
            if(count($oldSku) != count($newSku)){
                $takedown = true;
                $message  = '产品SKU属性';
            }
        }
        if(!$takedown){
            return $goods;
        }
        
        // 转成键值对，方便数据对比
        $temp = array();
        foreach ($oldSku as $item){
            $temp[$item['sku_id']] = $item['price'];
        }
        $oldSku = $temp;
        
        // 与新更新的SKU对比SKUID和PRICE
        foreach($newSku as $item){
            if(!isset($oldSku[$item['sku_id']]) || bccomp($item['price'], $oldSku[$item['sku_id']]) != 0){
                $takedown = true;
                $message  = '采购价格';
                break;
            }
        }
        
        if(!$takedown){
            return $goods;
        }
        
        $this->execute("UPDATE mall_goods SET is_display=0, takedowns='{$timestamp}' WHERE id='{$goods['goods_id']}'");
        // 发送消息通知
        $member = $this->getProjectMember($goods['shop_mid'], $goods['project_id']);
        if($member['subscribe']){
            $this->lPublish('MessageNotify', array(
                'type'         => MessageType::WAIT_DO_WORK,
                'openid'       => $member['openid'],
                'appid'        => $member['appid'],
                'data'         => array(
                    'url'      => '',
                    'title'    => '1688商品已被系统自动下架',
                    'time'     => date('Y年m月d日 H:i'),
                    'name'     => '商品下架待重新上架',
                    'remark'   => '下架原因：供应商修改了'.$message.'。 尊贵的'.$goods['shop_name'].'管理员，您的商品['.$goods['goods_id'].']已被系统自动下架，请重新编辑商品信息确认无误后再上架，以免给您造成亏损！商品名称-'.$goods['title']
                )
            ));
        }
        
        $goods['is_display'] = 0;
        return $goods;
    }
    
    /**
     * 处理商品详情
     */
    protected function goodsDetailHandler($goods, $buyer, $activityPreview = false){
        $goods['project_id'] = IdWork::getProjectId($goods['shop_id']);
        $goods = $this->checkAliGoods($goods);
        
        // 商品标记
        $goods['tag_id'] = $goods['tag_id'] == '' ? array() : explode(',', $goods['tag_id']);
        $goods['price_type'] = $this->getPriceType($goods['tag_id']);
        // 商品主标签
        $goods['main_tag'] = '';
        // 价格组合
        if($goods['custom_price'] != ''){
            $goods['custom_price'] = json_decode($goods['custom_price'], true);
        }
        // 允许使用其他优惠
        $goods['other_discount'] = 0;
        // 收藏id
        $collection = $this->query("SELECT id FROM member_collection WHERE mid='{$buyer['id']}' AND goods_id='{$goods['goods_id']}' LIMIT 1");
        $goods['collection_id'] = count($collection) > 0 ? $collection[0]['id'] : 0;
        // 购物车数量
        $cart = $this->query("SELECT id FROM mall_cart WHERE buyer_id='{$buyer['id']}' AND shop_id BETWEEN {$goods['project_id']}000 AND {$goods['project_id']}999 LIMIT 1");
        $goods['cart_quantity'] = $cart[0]['id'] ? 1 : 0;
        // 获取SKU信息
        $goods['products'] = $this->query("SELECT id, stock, price, original_price, score, retail_price, custom_price, weight, sku_json, pic_url FROM mall_product WHERE goods_id='{$goods['goods_id']}'");
    
        // 参加的活动
        $activity = null;
        if($goods['activity_id'] > 0){
            $activity = $this->getActivity($goods['activity_id']);
            if(is_null($activity)){
                $goods['activity_id'] = 0;
            }
        }
        if($activity){
            $AM = new $activity['model']();
            $activity['preview'] = $activityPreview;
            $goods = $AM->coverDetail($activity, $goods, $buyer);
        }
    
        $minOrderQuantity = $goods['min_order_quantity'];
        $goods = $this->goodsHandler($goods, $buyer);
    
        // 图片集
        if(isset($goods['images'])){
            $goods['images'] = explode(',', $goods['images']);
        }
        // 参数
        if(isset($goods['parameters'])){
            $goods['parameters'] = json_decode($goods['parameters'], true);
        }
    
        // 发货地
        if(isset($goods['send_place'])){
            if($goods['send_place'] > 0){
                $city = StaticModel::getCityList($goods['send_place']);
                $province = StaticModel::getCityList($city['pcode']);
                $goods['send_place'] = $province['sname'].$city['sname'];
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
    
    
        // 价格区间
        $goods['min_price'] = 999999999;
        $goods['max_price'] = 0;
        // 重量区间
        $minWeight = 999999999;
        $maxWeight = 0;
        foreach ($goods['products'] as $i=>$product){
            $product['sku_json'] = decode_json($product['sku_json']);
            $product['spec'] = get_spec_name($product['sku_json']);
    
            // 页面显示的价格
            if(!isset($product['view_price'])){
                $product['member_discount'] = $goods['member_discount'];
                $product['custom_price'] = decode_json($product['custom_price'], true);
                $product['price_type'] = $goods['price_type'];
                $product['quantity'] = $product['min_order_quantity'] = $minOrderQuantity;
                $product = $this->coverViewPrice($product, $buyer);
                unset($product['member_discount']);
                unset($product['price_type']);
                $goods['products'][$i] = $product;
            }
    
            if($product['price'] < $goods['min_price']){
                $goods['min_price'] = $product['price'];
            }
    
            if($product['price'] > $goods['max_price']){
                $goods['max_price'] = $product['price'];
            }
    
            if($product['weight'] < $minWeight){
                $minWeight = $product['weight'];
            }
    
            if($product['weight'] > $maxWeight){
                $maxWeight = $product['weight'];
            }
        }
        $minPrice*=1;
        $maxPrice*=1;
        if($minPrice < $maxPrice){
            $goods['price'] = $minPrice.' - '.$maxPrice;
        }
    
        // 最多可购数量
        $goods['quota_notice'] = '';
        $goods['quota'] = $goods['stock'];
        if($goods['day_quota'] > 0){
            $goods['quota_notice'] = '每日限售'.$goods['day_quota'].'件';
            if($goods['day_quota'] < $goods['quota']){
                $goods['quota'] = $goods['day_quota'];
            }
        }
        if($goods['buy_quota'] > 0){
            $goods['quota_notice'] = '每人限购'.$goods['buy_quota'].'件';
            if($goods['buy_quota'] < $goods['quota']){
                $goods['quota'] = $goods['buy_quota'];
            }
        }
        if($goods['every_quota'] > 0){
            $goods['quota_notice'] = '每日限购'.$goods['every_quota'].'件';
            if($goods['every_quota'] < $goods['quota']){
                $goods['quota'] = $goods['every_quota'];
            }
        }
        if($goods['min_order_quantity'] > 1){
            $goods['quota_notice'] = $goods['min_order_quantity'].'件起订';
        }
    
        if(!is_array($goods['level_quota'])){
            $goods['level_quota'] = $goods['level_quota'] ? explode(',', $goods['level_quota']) : array();
        }
    
        if($goods['level_quota'] && !in_array($buyer['card_id'], $goods['level_quota'])){
            $goods['quota_notice'] = $buyer['agent_title'].'不可购买';
        }
    
        // 页面底部按钮
        if(!isset($goods['action'])){
            $goods['action'] = $this->goodsActionHandler($goods, $buyer);
        }
    
        // 计算运费
        $express = new ExpressModel();
        $data = $express->getRangeFee($goods['freight_id'], $minWeight, $maxWeight, $goods['min_order_quantity']);
        $goods['freight_fee'] = $data['msg'];
    
        // 差价佣金比例
        if(!isset($goods['agent_rate'])){
            $goods['agent_rate'] = $this->getAgentRate($goods, $buyer['card_id']);
        }
        return $goods;
    }
    
    /**
     * 普通商品列表
     * @param unknown $goods
     * @param unknown $buyer
     */
    private function goodsHandler($goods, $buyer, $isSettlement = false){
        // 单品代理权
        if($goods['price_type'] == 4){
            $groupId = key($goods['custom_price']);
            $agent = get_agent_group($groupId);
            $agent['level'] = 0;
            foreach ($buyer['agents'] as $agentId){
                $groupId = substr($agentId, 0, -1);
                if($groupId == $agent['id']){   // 有此产品的代理权
                    $agent['level'] = substr($agentId, -1);
                }
            }
            $goods['agent'] = $agent;
        }
        
        // 会员卡类商品
        if($goods['goods_type'] == 1){
            $goods['other_discount'] = 0;
            $goods['main_tag'] = '会员卡';
        }
    
        // 详情链接地址
        if(!isset($goods['link'])){
            $goods['link'] = $buyer['url'].'/goods?id='.$goods['goods_id'];
        }
        // SKU请求链接
        $goods['sku_url'] = $buyer['url'].'/goods/sku?id='.$goods['goods_id'].($goods['activity_id'] > 0 ? '&activity_id='.$goods['activity_id'] : '');
        // 页面显示的价格
        if(!isset($goods['view_price'])){
            $goods = $this->coverViewPrice($goods, $buyer);
        }
    
        // 负数库存
        if($goods['stock'] < 1){$goods['stock'] = 0;}
    
        if(isset($goods['level_quota']) && !is_array($goods['level_quota'])){
            $goods['level_quota'] = $goods['level_quota'] === '' ? array() : explode(',', $goods['level_quota']);
        }
    
        // SKU
        if(isset($goods['sku_json'])){
            $goods['sku_json'] = decode_json($goods['sku_json']);
        }
    
        if(isset($goods['product_id'])){
            $goods['spec'] = get_spec_name($goods['sku_json']);
        }
    
        if(in_array(999, $goods['tag_id'])){
            if(!$goods['main_tag']){
                $goods['main_tag'] = '一件代发';
            }else{
                $goods['tags'][] = '包邮';
            }
        }
    
        unset($goods['quantity']);
        return $goods;
    }
    
    /**
     * 页面显示的价格
     */
    private function coverViewPrice($goods, $buyer){
        $goods["view_price"] = array();
        // 数据丢失
        if(!is_numeric($goods['price'])){
            $goods["view_price"][] = array('title' => '', 'price' => '', 'prefix' => '', 'suffix' => '');
            return $goods;
        }
    
        $data = $this->getMemberPrice($goods, $buyer);
        $goods['price'] = $data['price'];
        $goods['score'] = $data['score'];

        if($data['price_title']){
            $data['price_title'] .= ': ';
        }
    
        if($goods['price_type'] == 3){  // 积分价
            $goods['score'] = $data['score'];
            $goods["view_price"][] = array('title' => $data['price_title'], 'price' => $goods['score'], 'prefix' => '', 'suffix' => '积分'.($goods['price'] > 0 ? '+'.$goods['price'].'元' : ''));
            $goods["view_price"][] = array('title' => '原　价', 'price' => sprintf('%.2f', $goods['original_price']), 'prefix' => '¥', 'suffix' => '');
        }else{
            $price = split_money($goods['price']);
            $goods["view_price"][] = array('title' => '', 'price' => $price[0], 'prefix' => '¥', 'suffix' => $price[1]);
    
            if($buyer['card_id'] == 0){
                $goods["view_price"][] = array('title' => '代理价', 'price' => '代理价','prefix' => '','suffix' => '');
            }else{
                $goods["view_price"][] = array('title' => '', 'price' => '','prefix' => '', 'suffix' => '');
            }
    
            $goods['original_price'] = $goods['original_price'] > $goods['price'] ? $goods['original_price'] : '';
        }
    
        return $goods;
    }
    
    /**
     * 获取差价提成
     */
    public function getAgentRate($goods, $cardId){
        $result = array('settlement_type' => 0, 'message' => '', 'type' => 0, 'min' => 0, 'max' => 0);
        return $result;
        $cardList = get_member_card($goods['project_id']);
        if(!isset($cardList[$cardId]) || !$cardList[$cardId]['is_agent']){
            $result['message'] = '<a href="">点此升级享受推广佣金</a>';
            return $result;
        }
    
        $agent = $cardList[$cardId];
        $min = 999999999;
        $max = 0;
    
        // 查找自定义佣金
        $data = $this->query("SELECT * FROM agent_goods WHERE id='{$goods['goods_id']}'");
        if(!empty($data)){
            $data = $data[0];
            if(!$data['is_join']){
                $result['message'] = '此商品未参加推广计划';
                return $result;
            }
    
            $result['type'] = $data['type'];
            if($data['type'] == 1){// 所有人固定百分比(反一级)
                $result['min_rate'] = $data['value'];
                $result['max_rate'] = $data['value'];
                $result['min_fee'] = bcmul($goods['min_price'], $data['value']*0.01, 2);
                $result['max_fee'] = bcmul($goods['max_price'], $data['value']*0.01, 2);
                $result['message'] = '佣金比例: '.$data['value'].'%';
            }else if($data['type'] == 2){// 所有人固定金额(反一级)
                $result['min_fee'] = $data['value'];
                $result['max_fee'] = $data['value'];
                $result['message'] = '固定佣金: '.$data['value'].'元';
            }else{ // 指定组合
                $config = $result['items'] = json_decode($data['value'], true);
                foreach ($config as $orderId=>$items){
                    foreach ($items as $p=>$item){
                        if(!isset($item[$cardId])){
                            continue;
                        }
    
                        $value = $item[$cardId];
                        if($value > $max){
                            $max = $value;
                        }
                        if($value < $min){
                            $min = $value;
                        }
                    }
                }
    
                if($data['type'] == 3){ // 按照百分比返
                    $result['min_rate'] = $min;
                    $result['max_rate'] = $max;
                    $result['min_fee'] = bcmul($goods['min_price'], $min*0.01, 2);
                    $result['max_fee'] = bcmul($goods['max_price'], $max*0.01, 2);
                    $result['message'] = '佣金比例: '.($max > $min) ? $min.'% - '.$max.'%' : $min.'%';
                }else if($data['type'] == 4){ // 按照金额返
                    $result['min_fee'] = $min;
                    $result['max_fee'] = $max;
                    $result['message'] = '固定佣金: '.(($max > $min) ? $min.' - '.$max : $min).'元';
                }
            }
        }else{// 使用默认代理级别的佣金比例
            // 上一级
            if($agent['agent_rate'] < $min){$min = $agent['agent_rate'];}
            if($agent['agent_rate'] > $max){$max = $agent['agent_rate'];}
    
            // 上二级
            if($agent['agent_rate2'] > 0){
                if($agent['agent_rate2'] < $min){$min = $agent['agent_rate2'];}
                if($agent['agent_rate2'] > $max){$max = $agent['agent_rate2'];}
            }
    
            // 同级
            if($agent['agent_same'] > 0){
                if($agent['agent_same'] < $min){$min = $agent['agent_same'];}
                if($agent['agent_same'] > $max){$max = $agent['agent_same'];}
            }
    
            $result['min_rate'] = $min;
            $result['max_rate'] = $max;
            $result['min_fee'] = bcmul($goods['min_price'], $min*0.01, 2);
            $result['max_fee'] = bcmul($goods['max_price'], $max*0.01, 2);
            $result['message'] = ('佣金比例: '.(($max > $min) ? $min.'% - '.$max.'%' : $min.'%')).($agent['agent_same'] == 0 ? '(同级无佣金)' : '');
            $result['agent_rate'] = $agent['agent_rate'];
            $result['agent_same'] = $agent['agent_same'];
            $result['agent_rate2'] = $agent['agent_rate2'];
        }
    
        $result['is_join'] = 1;
        $result['min_fee'] = floor($result['min_fee']);
        $result['max_fee'] = floor($result['max_fee']);
        return $result;
    }
    
    /**
     * 获取缓存的库存(普通商品切勿缓存库存)
     * @param string $suffix
     * @param int $default
     * @param timespan $startTime
     * @param timespan $endTime
     * @return int
     */
    public function getCacheStock($suffix, $default, $startTime, $endTime){
        $stock = 0;
        if(NOW_TIME > $startTime - 600 && NOW_TIME < $endTime + 7200){
            $key = 'stock_'.$suffix;
            $stock = S($key);
            if($stock === false){
                $stock = $default;
                S($key, $stock, $endTime + 7200 - NOW_TIME);
            }
        }else{
            $stock = $default;
        }
        return $stock < 1 ? 0 : $stock;
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
    
        $sql = "UPDATE ".$this->tableName." SET is_del=1 WHERE `id` IN ({$goodsIds})";
        if(is_numeric($shopId)){
            $sql .= "  AND shop_id=".$shopId;
        }
    
        $result = $this->execute($sql);
        if($result > 0){
            $this->execute("DELETE FROM mall_goods_sort WHERE goods_id IN ({$goodsIds})");
        }
        return $result;
    }
}
?>