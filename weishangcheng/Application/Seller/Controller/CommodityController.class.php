<?php

namespace Seller\Controller;
use Common\Model\ProjectConfig;

/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/4/6
 * Time: 18:18
 */
class CommodityController extends ManagerController{
    /*
     * 商品列表
     */
    public function index(){
        $title = I('get.title');
        if (IS_AJAX){
            $where = array(
                'shop_id' => $this->shopId,
                'action'  => I('get.status'),
                'title' => I('get.title'),
            );
            $model = new \Seller\Model\CommodityModel();
            $data = $model->getGoodsList($where);
            $this->ajaxReturn($data);
        }
        $res = M('mall_goods')
            ->field("count(case when is_display = 1 then 1 else null end) as `sales`, 
                        count(case when is_display = 0 then 1 else null end) as `shelf`, 
                        count(case when stock = 0 then 1 else null end) as `sold`")
            ->where(array("shop_id" => $this->shopId, "is_del" => 0))
            ->find();
        $this->assign('top_count', $res);
        $projectId = substr($this->shopId, 0, -3);
        $key = ProjectConfig::WHOLE_SHOP_REWARD;
        $data = M('project_config')->where("project_id={$projectId} AND `key`='{$key}'")->find();
        if (!empty($data)){
            $val = json_decode($data['val'],true);
            if($val['recruit_open']=='1'){
                $card['promoters'] = 1;
            }else{
                $card['promoters'] = 0;
            }
        }else{
            $card = array(
                'promoters' => 0,
            );
        }
        if(IS_WEIXIN){
            $this->assign('iswx','1');
        }else{
            $this->assign('iswx','0');
        }
        $this->assign('card', $card);
        $this->assign('search_title', $title);
        $this->display();
    }

    /*
     * 商品上下架
     */
    public function toggleDisplay(){
        $id = I('post.id');
        $up = array(
            "is_display" => I('post.display')
        );
        if (!is_numeric($id)){
            $idto = str_replace(',', '', $id);
            if (!is_numeric($idto)){
                $this->error('商品id非法！');
            }
            M('mall_goods')->where("id IN ({$id}) AND shop_id = {$this->shopId}")->save($up);
        }else{
            M('mall_goods')->where(array('id' => $id, "shop_id" => $this->shopId))->save($up);
        }
        $res = M('mall_goods')
            ->field("count(case when is_display = 1 then 1 else null end) as `sales`, 
                        count(case when is_display = 0 then 1 else null end) as `shelf`, 
                        count(case when stock = 0 then 1 else null end) as `sold`")
            ->where(array("shop_id" => $this->shopId, "is_del" => 0))
            ->find();
        $this->ajaxReturn($res);
    }

    /*
     * 商品置顶
     */
    public function toggleTop(){
        $id = I('post.id');
        if (!is_numeric($id)){
            $this->error('商品id非法！');
        }
        $up = array(
            "sort" => time(),
        );
        M('mall_goods_sort')->where(array('id' => $id, "shop_id" => $this->shopId))->save($up);
        $this->ajaxReturn('success');
    }

    /*
     * 商品删除
     */
    public function delete(){
        if (IS_AJAX){
            $id = I('post.id');
            $up = array(
                'is_del' => 1
            );
            if (is_numeric($id) && $id > 0){
                $goods = M('mall_goods')->where('id = %d',$id)->find();
                if(!empty($goods)){
                    M('mall_goods')->where("id = {$id} AND shop_id = {$this->shopId}")->save($up);
                    if (!empty($goods['tag_id'])){
                        $tags = explode(',', $goods['tag_id']);
                        $param = array();
                        foreach ($tags as $k => $v){
                            $param[$v] = 1;
                        }
                        $this->goods_quantity_del_new($param);
                    }
                }
            }else{
                $idto = str_replace(',', '', $id);
                if (!is_numeric($idto)){
                    $this->error('商品id非法！');
                }
                $goods = M('mall_goods')->where("id IN ({$id})")->select();
                if (!empty($goods)){
                    M('mall_goods')->where("id IN ({$id}) AND shop_id = {$this->shopId}")->save($up);
                    $param = array();
                    foreach ($goods as $k => $v){
                        if (!empty($v['tag_id'])){
                            $tags = explode(',', $v['tag_id']);
                            foreach ($tags as $kk => $vv){
                                $param[$vv]++;
                            }
                        }
                    }
                    if (!empty($param)){
                        $this->goods_quantity_del_new($param);
                    }
                }
            }
            $res = M('mall_goods')
                ->field("count(case when is_display = 1 then 1 else null end) as `sales`, 
                                    count(case when is_display = 0 then 1 else null end) as `shelf`, 
                                    count(case when stock = 0 then 1 else null end) as `sold`")
                ->where(array("shop_id" => $this->shopId, "is_del" => 0))
                ->find();
            $this->ajaxReturn($res);
        }
    }
    /*
     * 商品下架
     */
    public function shelf(){
        if (IS_AJAX){
            $ids = I('post.off_line');
            $ids = addslashes($ids);
            $where = "shop_id = {$this->shopId}";
            if (!empty($ids)){
                $where .= ' AND id IN ('.implode(',', $ids).')';
            }
            $model = M('mall_goods');
            $up = array(
                "is_display" => 0,
            );
            $result = $model->where($where)->save($up);
            $this->ajaxReturn($result);
        }
    }
    /*
     * 商品佣金设置
     */
    public function goods_commision(){
        if (IS_AJAX){
            $id = I('post.id/d');
            if($_POST['commission_switch'] == 'false'){
                $agent = M('agent_goods')->find($id);
                if(empty($agent)){
                    $this->ajaxReturn('1');
                }else{
                    M('agent_goods')->delete($id);
                    $this->ajaxReturn('1');
                }
            }else{
                if($_POST['reward_type'] == -1){
                    M('agent_goods')->delete($id);
                }else{
                    if($_POST['reward_type'] == 1){
                        $_POST['agent_rate'] = $_POST['agent_rate_yuan'];
                        $_POST['agent_rate2'] = $_POST['agent_rate2_yuan'];
                    }
                    if($_POST['lv_two'] == false){
                        $_POST['agent_rate2'] = 0;
                    }
                    $reward_value = array(
                        'o' => array(
                            0 => array(
                                '1_o' => $_POST['agent_rate'],
                                '2_o' => $_POST['agent_rate2']
                            )
                        )
                    );
                    $data = array(
                        'id'              => $id,
                        'reward_type'     => $_POST['reward_type'],
                        'settlement_type' => $_POST['settlement_type'],
                        'reward_value'    => json_encode($reward_value, JSON_NUMERIC_CHECK)
                    );
                    M('agent_goods')->add($data, null, true);
                }
                $this->ajaxReturn('1');
            }
        }
        $projectId = substr($this->shopId,0,-3);
        $goodsId = I('get.id','');
        if(!is_numeric($goodsId)){
            $this->error('商品ID不能为空');
        }
        $goods = M('mall_goods')
            ->field('id, shop_id, price, tag_id')
            ->where("id = {$goodsId} AND shop_id = {$this->shopId}")
            ->find();
        if(empty($goods)){
            $this->error('商品不存在');
        }
        $agent_goods = M('agent_goods')->find($goods['id']);
        if(empty($agent_goods)){
            //初次开启奖励佣金，默认显示全店铺佣金
            $key = ProjectConfig::WHOLE_SHOP_REWARD;
            $project = M('project_config')->where("project_id='{$projectId}' AND `key`='{$key}'")->find();
            $shop_commision = json_decode($project['val'],true);
            $agent_goods = array(
                'settlement_type' => 4,
                'reward_type'     => $shop_commision['settlement_type'],
                'agent_rate'      => $shop_commision['agent_rate'],
                'agent_rate2'     => $shop_commision['agent_rate2'],
                'reward_open'     => 0,
                );
        }else{
            //显示该商品佣金
            $agent_goods['reward_value'] = json_decode($agent_goods['reward_value'],true);
            $agent_goods['agent_rate'] = $agent_goods['reward_value']['o'][0]['1_o'];
            $agent_goods['agent_rate2'] = $agent_goods['reward_value']['o'][0]['2_o'];
            $agent_goods['reward_open'] = 1;
        }
        $agent_goods['min_price']   = bcdiv($goods['price'], 2, 2);
        $agent_goods['id'] = $goods['id'];
        $this->assign('agent_goods', $agent_goods);
        $this->display('single_commission');
        
    }
    /*
     * 验证tag_id
     */
    private function format_tag($tag_id){
        if (!empty($tag_id)){
            $tag_id = explode(',', $tag_id);
            foreach ($tag_id as $k => $v){
                if (!is_numeric($v)){
                    unset($tag_id[$k]);
                }
            }
            if (!empty($tag_id)){
                $tag_id = implode(',', $tag_id);
            }else{
                $tag_id = '';
            }
        }
        return $tag_id;
    }

    /*
     * 添加/修改商品信息、添加1688商品
     */
    public function goods(){
        $id = I('get.id/d','');
        $is_tao = I('get.istao','');
        if($id == '0'){
            $this->error('参数错误');
        }
        if(IS_AJAX){
            //保存商品信息
            $id = I('post.id/d','');
            $istao = I('post.istao','0');
            $data['is_suyuan'] = I('post.suyuan',0);
            $data['shop_id'] = $this->shopId;
            $data['title'] = I('post.good_title','');
            $data['pic_url'] = I('post.pic','');
            $data['price'] = I('post.price','');
            //$data['stock'] = I('post.stock','');
            $data['tag_id'] = I('post.tag_id','');
            $data['returns'] = '1';
            $data['is_display'] = I('post.updown','0');
            $data['created'] = date("Y-m-d H:i:s");
            $data['products'] = S('products'.$id.$this->shopId);
            $data['detail'] = S('detail'.$id.$this->shopId);

            $data['tag_id'] = $this->format_tag($data['tag_id']);
            if(empty($id)){
                //添加商品
                $result = $this->goods_local_add($data);
                if($result == true){
                    //清除缓存
                    S('detail'.$id.$this->shopId,null);
                    S('products'.$id.$this->shopId,null);
                    $data = array('result' => "success");
                    $this->ajaxReturn($data);
                }else{
                    $data = array(
                        'result' => 'fail',
                        'reason' => '10004'
                    );
                    $this->ajaxReturn($data);
                }
            }else{
                if($istao == '1'){
                    //1688商品编辑保存
                    $aliGoods = M('alibaba_goods')->where("id = %d",$id)->find();
                    if($aliGoods['status'] != 'published'){
                        $data = array(
                            'result' => 'fail',
                            'reason' => '10001'
                            );
                        $this->ajaxReturn($data);
                    }
                    if($aliGoods['stock'] == '0'){
                        $data = array(
                            'result' => 'fail',
                            'reason' => '10002'
                            );
                        $this->ajaxReturn($data);
                    }
                    if($aliGoods['expire_time'] < date("Y-m-d H:i:s")){
                        $data = array(
                            'result' => 'fail',
                            'reason' => '10003'
                            );
                        $this->ajaxReturn($data);
                    }
                    $goodsInfo = M('mall_goods')->where("tao_id = %d AND shop_id = %d AND is_del = %d",$id,$this->shopId,0)->find();
                    if(empty($goodsInfo)){
                        //插入操作
                        $extend_param = array('is_suyuan' => $data['is_suyuan']);
                        $data['extend_param'] = json_encode($extend_param);
                        $result = $this->goods_1688_add($data,$aliGoods);
                        if($result == true){
                            //清除缓存
                            S('detail'.$id.$this->shopId,null);
                            S('products'.$id.$this->shopId,null);
                            $data = array('result' => "successtao");
                            $this->ajaxReturn($data);
                        }else{
                            $data = array(
                                'result' => 'fail',
                                'reason' => '10004'
                            );
                            $this->ajaxReturn($data);
                        } 
                    }else{
                        //修改操作
                        $extend_param = json_decode($goodsInfo['extend_param'],true);
                        $extend_param['is_suyuan'] = $data['is_suyuan'];
                        $data['extend_param'] = json_encode($extend_param);
                        $result = $this->goods_save($data,$goodsInfo['id']);
                        if($result == true){
                            S('detail'.$id.$this->shopId,null);
                            S('products'.$id.$this->shopId,null);
                            $data = array('result' => "successtao");
                            $this->ajaxReturn($data);
                        }else{
                            $data = array(
                                'result' => 'fail',
                                'reason' => '10004'
                            );
                            $this->ajaxReturn($data);
                        } 
                    }
                }else{
                    //非1688商品编辑
                    $extend_param = json_decode($goodsInfo['extend_param'],true);
                    $extend_param['is_suyuan'] = $data['is_suyuan'];
                    $data['extend_param'] = json_encode($extend_param);
                    $data['price'] = $data['products']['price'];
                    $result = $this->goods_save($data,$id);
                    if($result == true){
                        S('detail'.$id.$this->shopId,null);
                        S('products'.$id.$this->shopId,null);
                        $data = array('result' => "success");
                        $this->ajaxReturn($data);
                    }else{
                        $data = array(
                            'result' => 'fail',
                            'reason' => '10004'
                        );
                        $this->ajaxReturn($data);
                    }
                }
            }
        }
        //初始化缓存,商品详情，价格设置
        if(!empty(S('detail'.$id.$this->shopId)) && I('get.from') != 'modify_gift_detail'){
            S('detail'.$id.$this->shopId,null);
        }
        if(!empty(S('products'.$id.$this->shopId)) && I('get.from') != 'price_cfg'){
            S('products'.$id.$this->shopId,null);
        }
        $tagId = '';
        $catname = '';
        if(empty($id)){
            //添加商品
            $this->goods_add_local();
        }else{
            if($is_tao == 1){
                 //1688商品
                $data = $this->goods_edit_1688($id);
                if($data == false){
                    $this->error("未获得1688商品信息");
                }
            }else{
                //非1688商品
                $data = $this->goods_edit_local($id);
                if($data == false){
                    $this->error("未获得商品信息");
                }
                if(!empty($data['tag_id'])){
                    $tagId = $data['tag_id'];
                }
            }
        }
        //输出商品信息
        $n1 = mb_strlen($data['title']);
        $n2 = strlen($data['title']);
        $b1 = ($n2-$n1)/2;
        $b2 = $n1 - $b1;
        $data['title_num'] = $b1 * 2 + $b2;
        $this->assign('data',$data);
        //显示分组信息
        $project_id = $this->user('project_id');
        $tag_list = M('mall_tag')
            ->field('id, name')
            ->where("`project_id` = {$project_id} AND `level` = 1 AND `pid` = 0")
            ->select();
        $tags = array();
        $tags_name = array();
        if (!empty($tagId)){
            $tagId = explode(',', $tagId);
            foreach ($tag_list as $k => $v){
                if (in_array($v['id'], $tagId)){
                    $tags[] = $v['id'];
                    $tags_name[] = $v['name'];
                    $tag_list[$k]['is_select'] = 1;
                }
            }
        }
        $tags = !empty($tags) ? implode(',', $tags) : '';
        $tags_name = !empty($tags_name) ? implode(',', $tags_name) : '';
        $info = S('products'.$id.$this->shopId);
        if (!empty($info['product'][0]['sku_json'])){
            $this->assign('is_sku', 1);
        }
        $this->assign('tags',$tags);
        $this->assign('tags_name',$tags_name);
        $this->assign('tag_list',$tag_list);
//        $this->assign('catid',$tagId);
//        $this->assign('catname',$catname);
//        $this->assign('cat_list',$category);
        $this->display('goods');
    }
    /*
     * 1688铺货、跳过编辑保存
     */
    public function goods1688(){
        $id = I('get.pushProductIds');
        if(!is_numeric($id) || empty($id)){
            $reason = urlencode(urlencode('铺货失败'));
            header("location:/seller?code=-1&reason=".$reason);exit;
        }
        // $id = explode(',',$ids);
        //获取1688商品数据
        $goods = $this->goodsAli($id);
        if($goods == 'aliidEmpty'){
            $reason = urlencode(urlencode('未绑定1688帐号'));
            header("location:/seller?code=-1&reason=".$reason);exit;
        }
        if($goods == 'offerIdEmpty'){
            $reason = urlencode(urlencode('1688商品不存在'));
            header("location:/seller?code=-1&reason=".$reason);exit;
        }
        if($goods == 'goodsEmpty'){
            $reason = urlencode(urlencode('获取1688商品信息失败'));
            header("location:/seller?code=-1&reason=".$reason);exit;
        }
        $products = array();
        $productMsg = $goods['products'];
        $skuInfo = $goods['sku_json'];
        $goods['cost'] = $goods['price'];
        $goods['price'] = ceil($goods['price']*1.1*10)/10;
        if(empty($productMsg) || empty($skuInfo)){
            $product[0] = array(
                'stock'    => $goods['stock'],
                'price'    => $goods['price'],
                'cost'    => $goods['cost'],
                'sku_json' => '[{"kid":0,"vid":1,"k":"其他","v":"其他"}]',
                'pic_url'  => '',
            );
            $products = array(
                'price'     => $goods['price'],//商品零售价
                'product'   => $product,       //产品规格，价格，库存
                'stock'     => $goods['stock'],//商品总库存
            );
        }else{
            foreach ($skuInfo as $sik => $siv) {
                foreach ($siv['items'] as $item){
                    if($item['img']){
                        $skuImg[$siv['id']][$item['id']] = $item['img'];
                    }
                }
            }
            foreach ($productMsg as $value) {
                foreach ($value['sku_json'] as $sjk => $sjv) {
                    foreach ($skuImg as $smk => $smv) {
                        if($sjv['kid'] == $smk){
                            $vid = $value['sku_json'][$sjk]['vid'];
                        }
                    }
                    
                }
                // $vid = $value['sku_json'][0]['vid'];
                foreach ($skuImg as $smk => $smv) {
                    if(isset($skuImg[$smk][$vid])){
                        $value['pic_url'] = $skuImg[$smk][$vid];
                    }else{
                        $value['pic_url'] = '';
                    }
                }
                $value['cost'] = $value['price'];
                $value['price'] = ceil($value['price']*1.1*10)/10;
                $product[] = array(
                    'stock' => $value['stock'],
                    'price' => $value['price'],
                    'cost' => $value['cost'],
                    'sku_json' => json_encode($value['sku_json']),
                    'pic_url' => $value['pic_url'],
                    );
            }
            $products = array(
                'price'     => $goods['price'],//商品零售价
                'product'   => $product,       //产品规格，价格，库存
                'stock'     => $goods['stock'],//商品总库存
                'cost'      => $goods['cost'], //商品成本价
            );
        }
        $project_id = substr($this->shopId,0,-3);
        $cate = M('mall_category')->where('name = "%s" AND project_id="%d"','其他',$project_id)->find();
        if(empty($cate)){
            $cat = array(
                'project_id' => $project_id,
                'name' => '其他',
                'pinyin' => 'qita',
                'level' => 1,
                'pid' => 0,
                'sort' => 0,
                'created' => date('Y-m-d H:i:s'),
                'goods_quantity' => 0,
                'is_last' => 1,
                );
            $cate['id'] = M('mall_category')->add($cat);
        }
        $data = array(
            'shop_id'      => $this->shopId,      //店铺ID
            'cat_id'       => $cate['id'],        //分类ID
            'title'        => $goods['title'],    //商品标题
            'pic_url'      => $goods['images'],   //商品主图
            'price'        => $goods['price'],    //商品零售价
            'cost'         => $goods['cost'],    //成本价
            'stock'        => $goods['stock'],    //商品总库存
            'tao_id'       => $id,                //铺货商品的offerId
            'is_display'   => '1',                //默认上架
            'detail'       => $goods['detail'],   //商品详情
            'returns'      => '1',                //是否退换
            'created'      => date('Y-m-d H:i:s'),//创建时间
            'products'     => $products,          //产品信息
            );
        $goodsInfo = M('mall_goods')->where('tao_id = %d AND shop_id = %d AND is_del = %d',$id,$data['shop_id'],0)->find();
        if(empty($goodsInfo)){
            //添加到数据库
            $aliGoods = M('alibaba_goods')->where('id = %d',$id)->find();
            if($aliGoods['status'] != 'published'){
                $reason = urlencode(urlencode('1688商品未上架'));
                header("location:/seller?code=-1&reason=".$reason);exit;
            }
            if($aliGoods['stock'] == '0'){
                $reason = urlencode(urlencode('1688商品库存不足'));
                header("location:/seller?code=-1&reason=".$reason);exit;
            }
            if($aliGoods['expire_time'] < date("Y-m-d H:i:s")){
                $reason = urlencode(urlencode('1688商品已过期'));
                header("location:/seller?code=-1&reason=".$reason);exit;
            }
            $extend_param = array('is_suyuan' => '1');
            $data['extend_param'] = json_encode($extend_param);

            $result = $this->goods_1688_add($data,$aliGoods);
            if($result == 'false'){
                $reason = urlencode(urlencode('1688商品添加失败'));
                header("location:/seller?code=-1&reason=".$reason);exit;
            }else{
                $gid = $result['gid'];
            }

        }else{
            //更新数据库
            $gid = $goodsInfo['id'];
            $aliGoods = M('alibaba_goods')->where('id = %d',$goodsInfo['tao_id'])->find();
            if($aliGoods['status'] != 'published'){
                $reason = urlencode(urlencode('1688商品未上架'));
                header("location:/seller?code=-1&reason=".$reason);exit;
            }
            if($aliGoods['stock'] == '0'){
                $reason = urlencode(urlencode('1688商品库存不足'));
                header("location:/seller?code=-1&reason=".$reason);exit;
            }
            if($aliGoods['expire_time'] < date("Y-m-d H:i:s")){
                $reason = urlencode(urlencode('1688商品已过期'));
                header("location:/seller?code=-1&reason=".$reason);exit;
            }
            $extend_param = json_decode($goodsInfo['extend_param'],true);
            $extend_param['is_suyuan'] = '1';
            $data['extend_param'] = json_encode($extend_param);
            $result = $this->goods_save($data,$goodsInfo['id']);
        }
        if($result == true){
            //1688铺货成功
            //铺货成功。同步铺货商品
            $synResult = $this->syncGoodsTo1688($id,$gid,$data['pic_url'][0]);
            if($synResult['success'] == 'true'){
                header("location:/seller?code=0");exit;
            }else{
                $reason = urlencode(urlencode('同步铺货结果到1688失败'));
                header("location:/seller?code=-1&reason=".$reason);exit;
            }
        }else{
            //1688铺货失败
            $reason = urlencode(urlencode('商品铺货失败'));
            header("location:/seller?code=-1&reason=".$reason);exit;
        }
    }

    /*
     * 同步铺货结果
     * @param $productId 1688商品ID
     * @param $goodsId 商城商品ID
     * @param $imgUrl 铺货首图链接
     */
    private function syncGoodsTo1688($productId,$goodsId,$imgUrl=''){
        $shop = M('shop')->find($this->shopId);
        $ali = new \Org\Alibaba\AlibabaAuth($shop['aliid']);
        $projectId = substr($this->shopId,0,-3);
        $project = M('project')->find($projectId);
        $pushProductResults = array(
            array(
                'productId'        => $productId,
                'shopName'         => $project['name'],
                'imgUrl'           => $imgUrl,
                'productUrlInShop' => $project['host'].'/'.$project['alias'].'/goods?id='.$goodsId
            )
        );
        $result = $ali->syncPushProductResult($pushProductResults);
        return $result;
    }

    /*
     * 本地商品编辑页数据
     */
    private function goods_edit_local($id){
        $model = M('mall_goods');
        $goods = $model->alias("goods")
            ->join("mall_goods_content as mgc on goods.id = mgc.goods_id")
            ->where("goods.id = {$id} AND goods.shop_id = {$this->shopId}")
            ->order("goods.id desc")
            ->find();
        if(empty($goods)){
            return false;
        }
        $goods['product'] = M('mall_product')
            ->alias('product')
            ->field("product.price,product.sku_json,product.stock,product.pic_url,product.cost, product.retail_price, product.pic_url")
            ->where("goods_id = {$id}")
            ->select();

        if ($goods['images'] != ''){
            $goods['images'] = explode(',', $goods['images']);
        }
        foreach ($goods['product'] as $k => $v){
            $goods['product'][$k]['retail_price'] = $goods['product'][$k]['cost'] * 1.2;
        }
        $goods['extend_param'] = json_decode($goods['extend_param'],true);
        $goods['is_suyuan'] = $goods['extend_param']['is_suyuan'];
        $data = array(
            'id'        => $id,
            'istao'     => '',
            'title'     => $goods['title'],//商品标题
            'images'    => $goods['images'],//商品主图
            'price'     => $goods['price'],//商品零售价
            'stock'     => $goods['stock'],//商品总库存
            'tag_id'    => $goods['tag_id'],//商品分组
            'is_display'=> $goods['is_display'],//是否上架
            'is_suyuan' => $goods['is_suyuan'],//是否回流
        );
        $products = array(
            'price'     => $goods['price'],//商品零售价
            'product'   => $goods['product'],//产品规格，价格，库存
            'stock'     => $goods['stock'],//商品总库存
            'cost' => $goods['cost'],
            'retail_price' => $goods['cost'] * 1.2,
            'pic_url'   => $goods['pic_url'],
        );
        //商品详情，缓存
        if(empty(S('detail'.$id.$this->shopId))){
            S('detail'.$id.$this->shopId,$goods['detail'],1800);
        }
        //价格设置，缓存：价格，商品规格
        if(empty(S('products'.$id.$this->shopId))){
            S('products'.$id.$this->shopId,$products,1800);
        }
        return $data;
    }
    /*
     * 1688商品编辑页数据
     */
    private function goods_edit_1688($id){
        //1688商品
        $goods = $this->goodsAli($id);
        if($goods == false){
            return false;
        }
        $productMsg = $goods['products'];
        $skuInfo = $goods['sku_json'];
        if(empty($productMsg) || empty($skuInfo)){
            $product[0] = array(
                'stock'    => $goods['stock'],
                'price'    => $goods['price'],
                'sku_json' => '[{"kid":0,"vid":1,"k":"其他","v":"其他"}]',
                'pic_url'  => '',
            );
            $products = array(
                'price'     => $goods['price'],//商品零售价
                'product'   => $product,//产品规格，价格，库存
                'stock'     => $goods['stock'],//商品总库存
            );
        }else{
            foreach ($skuInfo[0]['items'] as $item){
                if($item['img']){
                    $skuImg[$item['id']] = $item['img'];
                }
            }
            foreach ($productMsg as $value) {
                $vid = $value['sku_json'][0]['vid'];
                if(isset($skuImg[$vid])){
                    $value['pic_url'] = $skuImg[$vid];
                }else{
                    $value['pic_url'] = '';
                }
                $product[] = array(
                    'stock' => $value['stock'],
                    'price' => $value['price'],
                    'sku_json' => json_encode($value['sku_json']),
                    'pic_url' => $value['pic_url'],
                    );
            }
            $products = array(
                'price'     => $goods['price'],//商品零售价
                'product'   => $product,//产品规格，价格，库存
                'stock'     => $goods['stock'],//商品总库存
            );
        
        }
        $data = array(
            'id'        => $id,
            'istao'     => '1',
            'title'     => $goods['title'],//商品标题
            'images'    => $goods['images'],//商品主图
            'price'     => $goods['price'],//商品零售价
            'stock'     => $goods['stock'],//商品总库存
            'is_display'=> '1',//默认上架
            'is_suyuan'=> '1',//默认回流
            );
        //商品详情，缓存
        if(empty(S('detail'.$id.$this->shopId,$goods['detail']))){
            S("detail".$id.$this->shopId,$goods['detail'],1800);
        }
        //价格设置，缓存：价格，商品规格,库存
        if(empty(S('products'.$id.$this->shopId))){
            S('products'.$id.$this->shopId,$products,1800);
        }
        return $data;
    }
    /*
     * 本地商品添加页数据
     */
    private function goods_add_local(){
        $detail = '';
        $product[0] = array(
            'stock'    => 99999,
            'price'    => 0,
            'sku_json' => '[{"kid":0,"vid":1,"k":"其他","v":"其他"}]',
            'pic_url'  => '',
            );
        $products = array(
            'price'     => 0,//商品零售价
            'product'   => $product,//产品规格，价格，库存
            'stock'     => 99999,//商品总库存
        );

        //商品详情，缓存
        if(empty(S('detail'.$id.$this->shopId,$detail))){
            S("detail".$id.$this->shopId,$detail,1800);
        }
        //价格设置，缓存：价格，商品规格,库存
        if(empty(S('products'.$id.$this->shopId))){
            S('products'.$id.$this->shopId,$products,1800);
        }
    }
    /*
     * 1688商品添加保存
     */
    private function goods_1688_add($data,$aliGoods){
        $price_range = json_decode($aliGoods['price_range'],true);
        foreach ($price_range as $key => $value) {
            if($key == 1){
                $range[$key] = $value;
            }
        }
        if(empty($range)){
            $aliGoods['price_range'] = '';
        }else{
           $aliGoods['price_range'] = json_encode($range); 
        }
        $goods = array(
            'pic_url'  => $data['pic_url'][0],
            'custom_price' => $aliGoods['price_range'],
            'weight' => $aliGoods['weight'],
            'freight_id' => 'T'.$aliGoods['freight_id'],
            'stock' => $aliGoods['stock'],
            'extend_param' => $data['extend_param'],
            'tao_id'  => $aliGoods['id'],
//            "tag_id" => $data['tag_id']
        );
        $goods = array_merge($data,$goods);
        $addgoods = M('mall_goods')->add($goods);
        if($addgoods > 0){
            $time_n =  time();
            $sort = M('mall_goods_sort')->add(array('id'=>$addgoods, 'zonghe' => $time_n, 'sort' => $time_n));
            if($sort <= 0){
                return 'false';
            }
            if(!empty($data['tag_id'])){
                $this->goods_quantity_save($data['tag_id']);
            }
            $this->key_word('add',$data['title'],$addgoods);
            $goods_content = array(
                'goods_id' => $addgoods,
                'sku_json' => $aliGoods['sku_json'],
                'images' => implode(',',$data['pic_url']),
                'detail' => $data['detail'],
            );
            $content = M('mall_goods_content')->add($goods_content);
            if($content <= 0){
                return 'false';
            }
            $products = $data['products'];
            foreach ($products['product'] as $value) {
                $product[] = array(
                    'goods_id' => $addgoods,
                    'stock' => $value['stock'],
                    'cost' => $value['cost']==0?$products['cost']:$value['cost'],
                    'price' => $value['price']==0?$products['price']:$value['price'],
                    'weight' => $aliGoods['weight'],
                    'sku_json' => $value['sku_json'],
                    'pic_url' => is_null($value['pic_url'])?$goods['pic_url']:$value['pic_url'],
                    'created' => date("Y-m-d H:i:s"),
                    );
            }
            $product = M('mall_product')->addAll($product);
            if($product > 0){
                return array('result'=>'true','gid'=>$addgoods);
            }else{
                return 'false';
            }
        }else{
            return 'false';
        }
    }
    /*
     * 1688与本地商品编辑保存
     */
    private function goods_save($data,$id){
        $goods = array(
            'title'   => $data['title'],
            'price'   => $data['price'],
            'is_display' => $data['is_display'],
            'pic_url' => $data['pic_url'][0],
            'tag_id'  => $data['tag_id'],
            'extend_param'  => $data['extend_param'],
        );
        if (!empty($data['products']['product'])){
            $price = 0;
            foreach ($data['products']['product'] as $k => $v){
                if ($v['price'] < $price || $price == 0){
                    $price = $v['price'];
                }
            }
            if ($price > 0){
                $goods['price'] = $price;
            }
        }
        if(!empty($data['tag_id'])){
            $cat = $this->cat_in_goods($id,$data['tag_id']);
        }
        M('mall_goods')->where("id = %d",$id)->save($goods);
        $this->key_word('update',$data['title'],$id);
        $goods_content = array(
            'images' => implode(',',$data['pic_url']),
            'detail' => $data['detail'],
            );
        M('mall_goods_content')->where("goods_id = %d",$id)->save($goods_content);
        $productList = M('mall_product')->where('goods_id = %d',$id)->order('id ASC')->select();
        $product = array();
        foreach ($data['products']['product'] as $key => $value) {
            foreach ($productList as $k => $v) {
                if($key == $k){
                    $product[$k] = array(
                        'id'       => $v['id'],
                        'goods_id' => $id,
                        'stock'    => $value['stock'],
                        'price'    => $value['price'],
                        'cost'     => $value['cost'],
                        'weight'   => $v['weight'],
                        'sku_json' => $value['sku_json'],
                        'pic_url'  => $value['pic_url'],
                        'created'  => date("Y-m-d H:i:s"),
                        );
                }
            }   
        }
        $product = M('mall_product')->addAll($product,array(),true);
        if($product > 0){
            return true;
        }else{
            return false;
        }
    }
    /*
     * 本地商品添加保存
     */
    private function goods_local_add($data){
        $goods = array(
            'pic_url' =>$data['pic_url'][0],
            'stock' => 99999,
            'created' => date("Y-m-d H:i:s"),
        );
        $goods = array_merge($data,$goods);
        $addgoods = M('mall_goods')->add($goods);
        if($addgoods > 0){
            M('mall_goods_sort')->add(array('id'=>$addgoods));
            $this->goods_quantity_save($data['cat_id']);
            $this->key_word('add',$data['title'],$addgoods);
            $goods_content = array(
                'goods_id' => $addgoods,
                'images' => implode(',',$data['pic_url']),
                'detail' => $goods['detail'],
            );
            $addGoodsContent = M('mall_goods_content')->add($goods_content);
            $products = $data['products'];
            foreach ($products['product'] as $value) {
                $product[] = array(
                    'goods_id' => $addgoods,
                    'stock' => $value['stock'],
                    'price' => $value['price'],
                    'cost' => $value['price'],
                    'sku_json' => $value['sku_json'],
                    'pic_url' => $value['pic_url'],
                    'created' => date("Y-m-d H:i:s"),
                    );
            }
            $product = M('mall_product')->addAll($product);
            if($product > 0){
                return true;
            }else{
                return false;
            }
        }
    }
    /*
     * 商品规格价格调整
     */
    public function price_cfg(){
        $id = I('get.id','');
        if(IS_AJAX){
            $param = I('post.param');
            $id = I('post.id','');
            $products = S('products'.$id.$this->shopId);
            $min_price = 99999999999;
            foreach ($products['product'] as $key => $value) {
                $products['product'][$key]['price'] = $param[$key]['value'];
                $products['product'][$key]['stock'] = $param[$key]['store_num'];
                //取最小值
                if($min_price>$param[$key]['value']){
                    $min_price = $param[$key]['value'];
                }
            }
            $products['price'] = $min_price;
            S('products'.$id.$this->shopId,$products,1800);
            $goods = array(
                'result' => 'success',
                );
            $this->ajaxReturn($goods);
        }
        $products = S('products'.$id.$this->shopId);
        $group = array();
        foreach ($products['product'] as $value) {
            $sku_json = json_decode($value['sku_json'],true);
            $group_name = '';
            foreach ($sku_json as $val) {
                $group_name .= $val['v'];
            }
            $group[] =array(
                'name' => $group_name,
                'stock' => $value['stock'],
                'price' => sprintf("%.2f",$value['price']),
                'cost' => sprintf("%.2f",$value['cost']),
                'retail_price' => sprintf("%.2f",$value['retail_price']),
                'profit' => ($value['price'] - $value['cost'])==0?0:sprintf("%.2f", $value['price'] - $value['cost']),
             );
        }
        $goods = array(
            "sku"   => $group,
            "id" =>$id,
            "pic_url" => $products['pic_url'],
            );
        $is_sku = 1;
        if (count($goods['sku']) == 1 && $goods['sku'][0]['name'] == ''){
            $goods['sku'][0]['name'] = '无规格';
            $is_sku = 0;
        }
        $this->assign('is_sku', $is_sku);
        $this->assign('goods',$goods);
        $this->display('price_cfg');
    }
    /*
     * 商品价格随动
     */
    public function price_chg(){
        if(IS_AJAX){
            $id = I('post.id','');
            $price = I('post.price','0');
            $stock = I('post.stock','0');
            if($id == ''){
                if(empty(S('products'.$id.$this->shopId))){
                    //
                    $products = array(
                        'price'    => '0.00',
                        'sku_json' => '[]',
                        'stock'    => '0',
                        'pic_url'  => '',
                        );
                    S('products'.$id.$this->shopId,$products,1800);
                }
                $products = S('products'.$id.$this->shopId);
                $this->ajaxReturn($products);
            }else{
                $products = S('products'.$id.$this->shopId); 
                $this->ajaxReturn($products);
            }
        }
    }
    /*
     * 商品详情展示
     */
    public function modify_gift_detail(){
        $goods_id = I('get.id','');
        if (IS_AJAX){
            $id = I('post.id','');
            $detail = strip_tags(S('detail'.$id.$this->shopId),"<img>");
            $data = $detail;
            $this->ajaxReturn($data);
        }
        $this->assign('goods_id',$goods_id);
        $this->display('modify_gift_detail');
    }
    /*
     * 商品详情修改
     */
    public function modify_save(){
        $detail = I('post.content');
        $id = I('post.id','');
        S('detail'.$id.$this->shopId,$detail,1800);
        $this->ajaxReturn(1);
    }
    /*
     * 获得1688商品信息
     */
    private function goodsAli($offerId){
        if(empty($offerId)){
            return 'offerIdEmpty';
        }
        $shop = M('shop')->find($this->shopId);
        if(empty($shop) || $shop['aliid'] == ''){
            return 'aliidEmpty';
        }

        $Model = D("Common/Alibaba");
        $goods = $Model->syncGoods($offerId,'',false,$shop['aliid']);
        if(empty($goods)){
            return 'goodsEmpty';
        }
        //返回数据
        $data = array(
            'title'     => $goods['subject'],//商品标题
            'images'    => $goods['images'],//商品主图
            'detail'    => $goods['detail'],//商品详情，含图
            'price'     => $goods['price'],//商品零售价
            'sku_json'  => $goods['sku_json'],//商品规格
            'products'  => $goods['products'],//产品规格，价格，库存
            'stock'     => $goods['stock'],//商品库存
        );
        return $data;
    }
    /*
     * 搜索关键词
     */
    private function key_word($action,$title,$goodsId){
        // 搜索关键词
        $pscws = new \Org\PSCWS\PSCWS4();
        $result = $pscws->getTextAndPinYin($title);
        if($action == 'add'){
            $sql = "INSERT INTO mall_key_word SET id={$goodsId}, kw='".addslashes($result['text'])."', py='".addslashes($result['pinyin'])."'";
            M()->execute($sql);
        }
        if($action == 'update'){
            $sql = "UPDATE mall_key_word SET kw='".addslashes($result['text'])."', py='".addslashes($result['pinyin'])."' WHERE id={$goodsId}";
            M()->execute($sql);
        }
    }
    /*
     * 商品类目关联商品，在添加修改商品时执行
     */
    private function cat_in_goods($id,$tag_id){
        $goods = M('mall_goods')->where('id = %d',$id)->find();
        if($tag_id != $goods['cat_id']){
            $this->goods_quantity_save($tag_id,$goods['cat_id']);
        }
    }
    /*
     * 修改商品更换类目时，类目商品数量修改
     */
    private function goods_quantity_save($new_id,$old_id = ''){
        if(!empty($old_id)){
             //减少旧分类商品数量
            $old_cat = M('mall_tag')->where('id = %d',$old_id)->find();
            $goods_quantity_old = $old_cat['goods_quantity'] - 1;
            $old = array(
                'goods_quantity' => $goods_quantity_old,
                );
            M('mall_tag')->where('id = %d',$old_id)->save($old);
        }
        //增加新分类商品数量
        $new_cat = M('mall_tag')->where('id = %d',$new_id)->find();
        $goods_quantity_new = $new_cat['goods_quantity'] + 1;
        $new = array(
            'goods_quantity' => $goods_quantity_new,
            );
        M('mall_tag')->where('id = %d',$new_id)->save($new);
    }

    /*
     * 删除商品时，类目商品数量修改
     */
    private function goods_quantity_del($id){
        $project_id = $this->user("project_id");
        $cat = M('mall_category')->where('id = %d AND project_id = %d',$id,$project_id)->find();
        $goods_quantity = $cat['goods_quantity'] - 1;
        M('mall_category')->where('id = %d AND project_id = %d',$id,$project_id)->save(array('goods_quantity' => $goods_quantity));
    }

    /*
     * 删除商品时，类目商品数量修改  新
     */
    private function goods_quantity_del_new($param){
        if (empty($param)){
            return;
        }
        $project_id = $this->user("project_id");
        $ids = array();
        foreach ($param as $k => $v){
            $ids[] = $k;
        }
        $ids = implode(',', $ids);
//        $tags = M('mall_tag')->where("id IN ({$ids}) AND project_id = {$project_id}")->select();
//        foreach ($tags as $k => $v){
//            $param[$k] = $v['goods_quantity'] - $param[$v['id']];
//            $param[$k] = $param[$k] < 0 ? 0 : $param[$k];
//        }
        $sql = "update mall_tag set goods_quantity = CASE ";
        foreach ($param as $k => $v){
            $sql .= " WHEN id = {$k} THEN if(goods_quantity - {$v} > 0, goods_quantity - {$v}, 0) ";
        }
        $sql .= "ELSE goods_quantity END WHERE id IN ({$ids}) AND project_id = {$project_id}";
        M('mall_tag')->query($sql);
    }

    /*
     * 类目展示
     */
    public function tag(){
        //查找数据
        $project_id = $this->user('project_id');
        $tag_list = M('mall_tag')
            ->field('id, name')
            ->where('project_id = %d AND level = %d AND pid = %d',$project_id,1,0)
            ->select();
        $tag_id = I('post.tag_id', '');
        $tags = $this->format_tag($tag_id);
        if (!empty($tags)){
            $tags = explode(',', $tags);
            foreach ($tag_list as $k => $v){
                if (in_array($v['id'], $tags)){
                    $tag_list[$k]['is_select'] = 1;
                }else{
                    $tag_list[$k]['is_select'] = 0;
                }
            }
        }
        $this->ajaxReturn($tag_list);
    }
    /*
     * 删除类目
     */
    public function tag_del(){
        if(IS_AJAX){
            $id = I('post.id','');
            $goods_cat = M('mall_goods')
                ->field('id, tag_id')
                ->where("shop_id = {$this->shopId} AND MATCH (tag_id) AGAINST ({$id} IN BOOLEAN MODE)")
                ->select();
            $goodsId = array();
            $up = array();
            if(!empty($goods_cat)){
                foreach ($goods_cat as $k => $v){
                    if (!empty($v['tag_id'])){
                        $v['tag_id'] = explode(',', $v['tag_id']);
                        if (in_array($id, $v['tag_id'])){
                            foreach ($v['tag_id'] as $kk => $vv){
                                if ($vv == $id){
                                    unset($v['tag_id'][$kk]);
                                }
                            }
                            if (!empty($v['tag_id'])){
                                $v['tag_id'] = implode(',', $v['tag_id']);
                            }else{
                                $v['tag_id'] = '';
                            }
                            $up[$v['id']] = $v['tag_id'];
                            $goodsId[] = $v['id'];
                        }
                    }
                }
                $goodsId = implode(',', $goodsId);
                $sql = "update mall_goods set `tag_id` = case `id` ";
                foreach ($up as $k => $v){
                    $sql .= " when {$k} then '{$v}' ";
                }
                $sql .= " end where `id` IN ($goodsId) AND shop_id = {$this->shopId} ";
                M()->execute($sql);
            }

            $project_id = substr($this->shopId, 0, -3);
            M('mall_tag')->where(array("id" => $id, "project_id" => $project_id))->delete();
            //删除成功后，隐藏表单，显示列表；如果没有列表数据，显示添加类目表单
            $result = array();
            $this->ajaxReturn($result);
        }
    }
    /*
     * 类目添加/编辑，表单提交
     */
    public function tag_save(){
        //接收参数，添加类目数据
        if(IS_POST){
            $id = I('post.id/d',0);
            $name = I('post.name','');
            $project_id = $this->user('project_id');
            $name = addslashes($name);
            $check = $this->tag_name_check($name,$project_id);
            if($check == false){
                $this->ajaxReturn('fail');
            }
            $py = new \Org\PinYin();
            $pinyin = $py->getAllPY($name);
            $project_id = $this->user('project_id');
            if(empty($id)){
                $category = array(
                    'project_id' => $project_id,
                    'name' => $name,
                    'pinyin' => $pinyin,
                    'level' => 1,
                    'pid' => 0,
                    'sort' => 0,
                    'created' => date('Y-m-d H:i:s'),
                    'goods_quantity' => 0,
                    'is_last' => 1,
                    );
                $catid = M('mall_tag')->add($category);
            }else{
                M('mall_tag')
                    ->where(array(
                        "id" => $id,
                        "project_id" => $project_id
                    ))
                    ->save(array(
                        'name'=>$name,
                        'pinyin' => $pinyin
                    ));
//                $catid = $id;
            }
//            //添加修改成功后，隐藏表单，选择类目
//            $result = array(
//                'id' => $catid,
//                'name' => $name,
//                'sort' => $sort,
//                );
            $this->ajaxReturn(1);
        }
    }
    /*
     * 类目名称重复
     */
    private function tag_name_check($name,$project_id){
        $cat = M('mall_tag')->where('name = "%s" AND project_id = %d',$name,$project_id)->select();
        if(empty($cat)){
            return true;
        }else{
            return false;
        }
    }

}