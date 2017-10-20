<?php
namespace H5\Controller;

use Common\Common\CommonController;
use H5\Model\GoodsModel;
use Common\Model\ProjectConfig;

/**
 * 商城
 * @author lanxuebao
 *
 */
 
class GoodsController extends CommonController
{
    public function _empty($action){
        if(is_numeric($action)){
            $_GET['id'] = $action;
            return $this->index();
        }
        
        E('URL地址不存在');
    }
    
    /**
     * 商品列表
     */
    public function index(){
        $buyer = $this->user();
        $Model = new GoodsModel();

        // 会员卡商品id
        $cardGoodsId = project_config(PROJECT['id'], \Common\Model\ProjectConfig::CARD_GOODS_ID);
        $get = I('get.');
        $goods = $Model->getDetail($get, $buyer);
        if(empty($goods) || $goods['is_del'] == 1){
            $this->error('商品不存在或已被删除');
        }
        if (empty($goods['shop_logo'])){
            $goods['shop_logo'] = C('CDN').'/img/logo_108.png';
        }
        
        if(IS_WEIXIN){
            // 分享文案
            $shareData = array(
                "title"  => $goods['title'],
                "desc"   => empty($goods['digest']) ? $goods['title'] : $goods['digest'],
                "link"   =>  $buyer['url'].'/goods?id='.$goods['goods_id'].'&share_mid='.$buyer['id'],
                "imgUrl" => $goods['pic_url']
            );
            
            // 获取签名
            $WechatAuth = new \Org\Wechat\WechatAuth(PROJECT['third_appid'], PROJECT['appid']);
            $sign = $WechatAuth->getSignPackage();
            $this->assign(array('sign' => json_encode($sign), 'share_data' => json_encode($shareData),'iswx'=>'1'));
        }
        
        $rangePrice = array();
        if($goods['price_type'] == 1){
            $rangePrice = $goods['custom_price'];
        }

        if (in_array(1000, $goods['tag_id'])){
            $goods['baoyou'] = 1;
        }
        if (!empty($goods['digest']) && mb_strlen($goods['digest']) > 60){
            $goods['digest'] = mb_substr($goods['digest'], 0, 60).'...';
        }
        $max = '';
        $max_show = array();
        $min = '';
        $min_show = array();
        foreach ($goods['products'] as $key => $value) {
            if($key == 0){
                $max = $value['price'];
                $max_show = $value['view_price'][0];
                $min = $value['price'];
                $min_show = $value['view_price'][0];
            }else{
                if($max<$value['price']){
                    $max = $value['price'];
                    $max_show = $value['view_price'][0];
                }
                if($min>$value['price']){
                    $min = $value['price'];
                    $min_show = $value['view_price'][0];
                }
            }
            
        }
        $goods['max'] = $max;
        $goods['max_show'] = $max_show;
        $goods['min'] = $min; 
        $goods['min_show'] = $min_show;
        
        //验证小B
        $isLB = $this->isLittleB($this->projectId);
        if($isLB == true){
            //判断第一次访问
            $buyer_id = $this->user("id");
            if(IS_WEIXIN){
                $wx = M('wx_user')->where(array('mid'=>$buyer_id))->find();
                if($wx['coupon']==0 && empty($wx['remark'])){
                    $this->coupon_add();
                    $this->assign('isfirst',1);
                }
            }
            $this->assign('isLB',1);
        }else{
            $this->assign('siLB',0);
        }
        //实名认证
        $transfers_auth = M('transfers_auth')->where(array('project_id'=>$this->projectId,'status'=>'1'))->find();
        if(empty($transfers_auth)){
            $this->assign('transfers_auth','0');
        }else{
            $this->assign('transfers_auth','1');
        }
        //检测是否是阿里商品
        if($goods['tao_id'] == 0 || empty($goods['tao_id']) || is_null($goods['tao_id'])){
            $this->assign('isALGoods','0');
        }else{
            $this->assign('isALGoods','1');
        }
        //获取城市编码
        $province = M('city')->field('id,name')->where(array('level'=>1,'pcode'=>1))->select();
        $express = 0;
        //获取最后一次下单的地址省份
        $lastTrade = M('trade')->field('receiver_province')->where(array('buyer_id'=>$buyer['id']))->order('tid desc')->find();
        if(empty($lastTrade)){
            //如果不存在最后下单地址，默认为0，请选择
            foreach ($province as $key => $value) {
                $province[$key]['default'] = 0;
            }
            $province[] = array('id'=>0,'name'=>'请选择','default'=>1);
        }else{
            //获取该省份城市编码
            $lastProvince = M('city')->field('id,name')->where(array('name'=>$lastTrade['receiver_province'],'level'=>1,'pcode'=>1))->find();
            $express = $this->express($lastProvince['id'],$goods['aliid'],$goods['tao_id']);
            //存在，显示最后下单地址
            foreach ($province as $key => $value) {
                if($lastProvince['id'] == $value['id']){
                    $province[$key]['default'] = 1;
                }else{
                    $province[$key]['default'] = 0;
                }
            }
        }
        $this->assign(array(
            'buyer' => $buyer,
            'data'  => $goods,
            'range_price' => $rangePrice,
            'cardGoodsId' => $cardGoodsId,
            'province' => $province,
            'express' => $express
        ));

        if($goods['template_id'] == 1){
            $this->memberCard($goods);
        }else{
        	$showRateNum = project_config($this->projectId, ProjectConfig::SHOW_RATE_NUM);
        	$this->assign('show_rate_num', $showRateNum ? '' : 'hide');
            $this->display('index');
        }
    }
    /**
     * 计算运费
     */
    public function countExpress(){
        if(IS_AJAX){
            $provinceCode = I('post.provinceCode');
            $aliId = I('post.aliId/d');
            $taoId = I('post.taoId/d');
            $res = $this->express($provinceCode,$aliId,$taoId);
            $this->ajaxReturn($res);
        }
    }
    private function express($provinceCode,$aliId,$taoId){
        $goods = M('alibaba_goods')->find($taoId);
        $products = json_decode($goods['products'],true);
        $data = array();
        $data['skuId'] = $products[0]['sku_id'];
        $data['specId'] = $products[0]['spec_id'];
        $data['quantity'] = 1;
        $data['unitPrice'] = $products[0]['price'];
        $data['freightId'] = $goods['freight_id'];
        $data['offerId'] = $taoId;
        $cityCode = substr($provinceCode,0,-3).'100';
        $countryCode = substr($cityCode,0,-2);
        $where['id'] = array('like',"$countryCode%");
        $country = M('city')->where($where)->select();
        $countryCode = $country[0]['id'];
        $ali = new \Org\Alibaba\AlibabaAuth($aliId);
        $res = $ali->getWGExpressFee($data,$cityCode,$countryCode);
        if(!empty($res['errcode'])){
            return $res['errmsg'];
        }else{
            return $res['freight_fee'];
        }
    }
    /**
     * 获取商品sku信息
     * @param unknown $id
     */
    public function sku(){
        if(!is_numeric($_GET['id'])){
            $this->error('商品ID不能为空');
        }
        
        $buyer = $this->user();
        
        $Model = new GoodsModel();
        $get = I('get.');
        $goods = $Model->getSKU($get, $buyer);
        if(empty($goods) || $goods['is_del'] == 1){
            $this->error('商品不存在或已被删除');
        }
        $this->ajaxReturn($goods);
    }
    
    /**
     * 商品详商 和 最近下单记录 
     */
    public function detail(){
        if(!is_numeric($_GET['id'])){
            exit('商品ID异常');
        }

        $Model = M("mall_goods");
        $detail = $Model->getFieldById($_GET['id'], 'detail');
        exit($detail);
    }

    private function memberCard($goods){
        $products = array();
        foreach($goods['products'] as $product){
            $cardId = $product['sku_json'][0]['vid'];
            $products[$cardId] = $product['id'];
        }

        $this->assign('products', json_encode($products, JSON_UNESCAPED_UNICODE));
        $this->display('member_card');
    }

    /**
     * 我要开小店
     */
    public function cshop(){
        $this->display();
    }

    public function coupon_add(){
        // if(IA_AJAX){
            $id = $this->user('id');
            M('wx_user')->where(array('mid'=>$id))->save(array('coupon'=>'100','remark'=>'1'));
            // $this->success();
        // }
    }
}
?>