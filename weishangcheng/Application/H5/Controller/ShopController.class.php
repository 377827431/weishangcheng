<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 首页
 * @author lanxuebao
 *
 */
class ShopController extends CommonController
{
	/**
     * 商城首页
     */
    public function index(){
        $cdn = C('CDN');
        $shop_id = is_numeric($_GET['shop_id']) ? $_GET['shop_id'] : PROJECT_ID."001";
        $shop_info = M("shop")
                    ->alias('s')
                    ->field('s.id, s.name, s.logo, si.desc,si.shop_sign')
                    ->join('shop_info as si on s.id = si.id')
                    ->where("s.id = {$shop_id}")
                    // ->cache(true, 600)
                    ->find();
        if(empty($shop_info['logo'])){
            $shop_info['logo'] = $cdn."/img/logo_108.png";
        }
        if(empty($shop_info['shop_sign'])){
            $shop_info['shop_sign'] = $cdn."/img/mall/shop_header.jpg";
        }
        if(empty($shop_info['desc'])){
            $shop_info['desc'] = '欢迎光临我的小店，喜欢就拍下哦！';
        }
        //实名认证
        $transfers_auth = M('transfers_auth')->where(array('project_id'=>$this->projectId,'status'=>'1'))->find();
        if(empty($transfers_auth)){
            $shop_info['transfers_auth'] = '0';
        }else{
            $shop_info['transfers_auth'] = '1';
        }
        $this->assign('shop_info', $shop_info);
        
        //购物车红点
        $buyer_id = $this->user("id");
        
        $this->assign('id',$buyer_id);
        //全部商品数量
        $res = M('mall_goods')->field('count(*) as count')->where("shop_id = {$shop_id} AND is_del = 0 AND is_display = 1")->find();
        $this->assign('all_goods_count', $res['count']);

        //新品数量 7天内
        $time = date('Y-m-d H:i:s', strtotime("-7 day"));
        $res = M('mall_goods')->field('count(*) as count')->where("shop_id = {$shop_id} AND is_del = 0 AND is_display = 1 AND created > '{$time}'")->find();
        $this->assign('new_goods_count', $res['count']);

        //商品分类tag
        $tag_list = M('mall_tag')
            ->field('id, name')
            ->where("`project_id` = '{$this->projectId}' AND `level` = 1 AND `pid` = 0")
            ->select();
        $this->assign('tag_list', $tag_list);

        // 微信分享
        if(IS_WEIXIN){
            $share = array(
                "title"  => $shop_info['name'],
                "desc"   => $shop_info['desc'],
                "link"   => (is_ssl() ? 'https' : 'http').$_SERVER['HTTP_HOST'].__MODULE__.'/shop?id='.$shop_info['id'].'&share_mid='.$buyer_id,
                "imgUrl" => $shop_info['logo']
            );
            
            // 获取签名
            $WechatAuth = new \Org\Wechat\WechatAuth(PROJECT['third_appid'], PROJECT['appid']);
            $sign = $WechatAuth->getSignPackage();
            $this->assign(array('wxsign' => json_encode($sign), 'share' => encode_json($share)));
        }
        if (PROJECT_ID == 1100006) {
            $this->display('index_zymall');
        }
        $this->display();
    }

    
}
?>