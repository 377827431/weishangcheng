<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use GatewayClient\Gateway;
use Org\IdWork;
/**
 * 商品分组
 *
 * @author 兰学宝
 *
 */
class TestController extends CommonController
{
    /**
     * 列表
     */
    public function index()
    {
        print_data('33');
        print_data(json_decode("[{\"name\":\"全部商品\",\"sub_button\":[{\"type\":\"view\",\"name\":\"全部商品\",\"url\":\"http://www.weishang.com/wtlm/mall\"},{\"type\":\"view\",\"name\":\"零元购\",\"url\":\"http://www.weishang.com/wtlm/zero\"},{\"type\":\"view\",\"name\":\"积分商城\",\"url\":\"http://www.weishang.com/wtlm/score\"},{\"type\":\"view\",\"name\":\"微商爆款\",\"url\":\"http://www.weishang.com/wtlm/mall?shop_id=10001001\"},{\"type\":\"view\",\"name\":\"商品分类\",\"url\":\"http://www.weishang.com/wtlm/category\"}]},{\"type\":\"view\",\"name\":\"购物车\",\"url\":\"http://www.weishang.com/wtlm/cart\"},{\"name\":\"会员中心\",\"sub_button\":[{\"type\":\"view\",\"name\":\"个人资料\",\"url\":\"http://www.weishang.com/wtlm/personal\"},{\"type\":\"view\",\"name\":\"我的订单\",\"url\":\"http://www.weishang.com/wtlm/order\"},{\"type\":\"view\",\"name\":\"推广二维码\",\"url\":\"javascript:;\"},{\"type\":\"click\",\"name\":\"在线客服\",\"key\":\"js-lxkf\"},{\"type\":\"view\",\"name\":\"我的收藏\",\"url\":\"http://www.weishang.com/wtlm/collection\"}]}]", true));
//        Vendor('workerman.Gateway');
//        Gateway::$registerAddress = 'websocket://127.0.0.1:123622';
////        print_r(Gateway::isOnline('7f0000010b5400000002'));
//        $rr = Gateway::sendToClient('7f0000010b5400000004', '666uuu');
//        print_r($rr);
//        die('11');

        Vendor('solr.trade');
//        $fieldArr = array(
//            "cat_id" => "11",
//            "id" => 1111111111,
//        );
//        $res = \trade::addDocument($fieldArr, 'mall_goods');die($res);
        $qwhere = array(
//            "id" => "[0 TO 500000000000]",
            "title" => "23",
        );
        echo '<pre>';
        $res = \trade::selectGoods($qwhere, 1, 200);
        print_r($res);die();
        $this->display();
    }

    public function set(){
        set_time_limit(0);
        $model = M('admin_user');
        $from = '13900000000';
        $to = '13900003999';
        $data = M('admin_user')->field("max(id) as id, max(username) as username")->where("`username` between '13900000000' AND '13900003999'")->find();
        if ($data['id'] > 0 && $data['username'] != ''){
            $from = $data['username'];
        }
        $model->startTrans();
        $mobile = $from;
        while ($mobile < $to){
            $mobile++;
            $Model = new \Common\Model\ShopModel();
            $Model->firstAdd(
                array(
                    'mobile'   => $mobile,
                    'password' => '123456',
                ),
                array(
                    'name' => $mobile
                )
            );
        }
        $model->commit();
    }

    public function shop_balance(){
        set_time_limit(0);
        $data = M('shop_balance')->find();
        foreach ($data as $k => $v){
            $data[$k] = "'".$v."'";
        }

        $rid = M('shop_balance')->field("max(id) as id")->find();
        $rid = $rid['id'];

        while (true){
            $rid += 1500;
            $sid = $rid;
            $sql = "insert into `shop_balance` VALUES ";
            while ($sid < $rid + 1499){
                $sid++;
                $random = rand(100020, 104019);
                $data['id'] = "'".$sid."'";
                $data['shop_id'] = "'".$random."001"."'";
                $data['username'] = "'"."system"."'";
                $m = '('.implode(',',$data).')';
                if ($sid < $rid + 1499){
                    $m .= ',';
                }
                $sql .= $m;
            }
            M('shop_balance')->execute($sql);
        }
    }

    public function trade_book(){
//        $data = M('trade_book')->where("id > 217060139305448")->delete();die();
        set_time_limit(0);
        $data = M('trade_book')->where("id = 217060139305448")->find();
        foreach ($data as $k => $v){
            $data[$k] = "'".$v."'";
        }

        $rid = M('trade_book')->field("max(id) as id")->find();
        $rid = $rid['id'];

        while (true){
            $rid = $rid + 1500;
            $sid = $rid;
            $sql = "insert into `trade_book` VALUES ";
            while ($sid < $rid + 1499){
                $sid++;
                $random = rand(2000000, 2220000);
                $data['id'] = "'".$sid."'";
                $data['buyer_id'] = "'".$random."'";
                $m = '('.implode(',',$data).')';
                if ($sid < $rid + 1499){
                    $m .= ',';
                }
                $sql .= $m;
            }
            M('trade_book')->execute($sql);
        }
    }

    public function trade_refund(){
        set_time_limit(0);
        $data = M('trade_refund')->where("refund_id = 2170531517091108")->find();
        foreach ($data as $k => $v){
            $data[$k] = "'".$v."'";
        }

        $rid = M('trade_refund')->field("max(refund_id) as id")->find();
        $rid = $rid['id'];

        while (true){
            $rid = $rid + 1500;
            $sid = $rid;
            $sql = "insert into `trade_refund` VALUES ";
            while ($sid < $rid + 1499){
                $sid++;
                $random = rand(100020, 104019);
                $random2 = rand(1, 15);
                $data['refund_id'] = "'".$sid."'";
                $data['refund_sid'] = "'".$random."001"."'";
                $data['refund_tid'] = "'".$sid."'";
                $data['refund_status'] = "'".$random2."'";
                $m = '('.implode(',',$data).')';
                if ($sid < $rid + 1499){
                    $m .= ',';
                }
                $sql .= $m;
            }
            M('trade_refund')->execute($sql);
        }
    }

    public function transfers_auth(){
        set_time_limit(0);
        $data = M('transfers_auth')->where("id = 20")->find();
        foreach ($data as $k => $v){
            $data[$k] = "'".$v."'";
        }

        $rid = M('transfers_auth')->field("max(id) as id")->find();
        $rid = $rid['id'];

        $mobile = $rid;
        $sql = "insert into `transfers_auth` VALUES ";
        while ($mobile < 1000){
                $mobile ++;
                $random = rand(100020, 104019);
                $data['id'] = "'".$mobile."'";
                $data['project_id'] = "'".$random."'";
                $data['shop_id'] = "'".$random."001"."'";
                $data['card_name'] = "'"."测试"."'";
                $m = '('.implode(',',$data).')';
                if ($mobile < 1000){
                    $m .= ',';
                }
                $sql .= $m;
        }
        M('transfers_auth')->execute($sql);
    }

    public function member_change(){
        set_time_limit(0);
        $data = M('member_change')->where("mid = 1000004")->find();
        foreach ($data as $k => $v){
            $data[$k] = "'".$v."'";
        }

        $rid = M('member_change')->field("max(id) as id")->find();
        $rid = $rid['id'];

        while ($rid < 1200000){
            $rid = $rid + 1500;
            $sid = $rid;
            $sql = "insert into `member_change` VALUES ";
            while ($sid < $rid + 1499){
                $sid++;
                $random = rand(2000000, 2220000);
                $random2 = rand(100020, 104019);
                $k = 0;
                $random += 200;
                if ($random == $sid){
                    $random += 200;
                }
                if ($random > 2220000){
                    $random = $random - 1000000;
                }
                $data['id'] = "'".$sid."'";
                $data['mid'] = "'".$random."'";
                $data['old_level'] = "'"."0"."'";
                $data['old_title'] = "'"."游客"."'";
                $data['new_level'] = "'".$random2."3"."'";
                $data['new_title'] = "'"."金牌会员"."'";
                $m = '('.implode(',',$data).')';
                if ($sid < $rid + 1499){
                    $m .= ',';
                }
                $sql .= $m;
            }
            M('member_change')->execute($sql);
        }
    }

    public function trade(){
//        $mall_goods = M('mall_goods')->where("id > 2000000")->delete();
//        $mall_goods_content = M('mall_goods_content')->where("goods_id > 2000000")->delete();
//        $mall_goods_sort = M('mall_goods_sort')->where("id > 2000000")->delete();
//        $mall_goods_uv = M('mall_goods_uv')->where("goods_id > 2000000")->delete();
//        $mall_product = M('mall_product')->where("goods_id > 2000000")->delete();
//        die();
        set_time_limit(0);
        $trade = M('trade')->where("tid = 2170524487801065")->find();
        $trade_order = M('trade_order')->where("oid = 2170524487801065")->find();
        $trade_buyer = M('trade_buyer')->where("tid = 2170524487801065")->find();
        $trade_seller = M('trade_seller')->where("tid = 2170524487801065")->find();
        foreach ($trade as $k => $v){
            $trade[$k] = "'".$v."'";
        }
        foreach ($trade_order as $k => $v){
            $trade_order[$k] = "'".$v."'";
        }
        foreach ($trade_buyer as $k => $v){
            $trade_buyer[$k] = "'".$v."'";
        }
        foreach ($trade_seller as $k => $v){
            $trade_seller[$k] = "'".$v."'";
        }

        $from = 2000000;
        $to = 3000000;
        $id = $from;

        $ids = M('trade')->field("count(*) as id")->find();
        $ids = $ids['id'] + $from;
        if ($ids > $from){
            $id = $ids;
        }
        while ($id < $to){
            $id += 1000;
            $sid = $id;
            $sql = "insert into `trade` VALUES ";
            $sql2 = "insert into `trade_order` VALUES ";
            $sql3 = "insert into `trade_buyer` VALUES ";
            $sql4 = "insert into `trade_seller` VALUES ";
//            sleep(1);
            while ($sid < $id + 999){
                $sid++;
                $random = rand(100020, 104019);
                $random2 = rand(2000000, 2220000);
                $random3 = rand(2000000, 2220000);
                $tid = IdWork::nextTId();
                $trade['tid'] = "'".$tid."'";
                $trade['status'] = "'"."3"."'";
                $trade['receiver_name'] = "'"."测试"."'";
                $trade['seller_id'] = "'".$random."001"."'";
                $trade['buyer_id'] = "'".$random2."'";
                $trade['buyer_nick'] = "'"."测试"."'";

                $trade_order['oid'] = "'".$tid."'";
                $trade_order['tid'] = "'".$tid."'";
                $trade_order['status'] = "'"."3"."'";
                $trade_order['goods_id'] = "'".$random3."'";
                $trade_order['title'] = "'"."测试商品title"."'";

                $trade_buyer['tid'] = "'".$tid."'";
                $trade_buyer['status'] = "'"."3"."'";
                $trade_buyer['seller_id'] = "'".$random."001"."'";
                $trade_buyer['buyer_id'] = "'".$random2."'";

                $trade_seller['tid'] = "'".$tid."'";
                $trade_seller['status'] = "'"."3"."'";
                $trade_seller['seller_id'] = "'".$random."001"."'";
                $trade_seller['buyer_id'] = "'".$random2."'";

                $t = '('.implode(',',$trade).')';
                $o = '('.implode(',',$trade_order).')';
                $b = '('.implode(',',$trade_buyer).')';
                $s = '('.implode(',',$trade_seller).')';
                if ($sid < $id + 999){
                    $t .= ',';
                    $o .= ',';
                    $b .= ',';
                    $s .= ',';
                }
                $sql .= $t;
                $sql2 .= $o;
                $sql3 .= $b;
                $sql4 .= $s;
            }
            $model = M('trade');
            $model->startTrans();

            M('trade')->execute($sql);
            M('trade_order')->execute($sql2);
            M('trade_buyer')->execute($sql3);
            M('trade_seller')->execute($sql4);

            $model->commit();
        }
    }

    public function member_friend(){
        set_time_limit(0);
        $data = M('member_friend')->where("mid = 1000004")->find();
        foreach ($data as $k => $v){
            $data[$k] = "'".$v."'";
        }
        $from = 2000000;
        $to = 2220000;
        $id = $from;

        $ids = M('member_friend')->field("max(mid) as id")->find();
        $ids = $ids['id'];
        if ($ids > $from){
            $id = $ids;
        }


        while ($id < $to){
            $id += 50;
            $sid = $id;

            $sql = "insert into `member_friend` VALUES ";
            while ($sid < $id + 49){
                $sid++;
                $random = rand(2000000, 2220000);
                $k = 0;
                while ($k < 20){
                    $k++;
                    $random += 200;
                    if ($random == $sid){
                        $random += 200;
                    }
                    if ($random > 2220000){
                        $random = $random - 1000000;
                    }
                    $data['mid'] = "'".$sid."'";
                    $data['friend_id'] = "'".$random."'";
                    $m = '('.implode(',',$data).')';
                    if ($sid < $id + 49 || $k < 20){
                        $m .= ',';
                    }
                    $sql .= $m;
                }
            }
            M('member_friend')->execute($sql);
        }
    }

    public function memeber_balance(){
        set_time_limit(0);
        $address = M('member_balance')->where("id = 26 AND mid = 1000004")->find();
        foreach ($address as $k => $v){
            $address[$k] = "'".$v."'";
        }
        $from = 2000000;
        $to = 2220000;
        $id = $from;

        $ids = M('member_balance')->field("max(mid) as id")->find();
        $ids = $ids['id'];
        if ($ids > $from){
            $id = $ids;
        }

        $rid = M('member_balance')->field("max(id) as id")->find();
        $rid = $rid['id'];

        while ($id < $to){
            $id += 50;
            $sid = $id;

            $sql = "insert into `member_balance` VALUES ";
            while ($sid < $id + 49){
                $sid++;
                $random = rand(100020, 104019);
                $k = 0;
                while ($k < 20){
                    $k++;
                    $rid++;
                    $address['mid'] = "'".$sid."'";
                    $address['project_id'] = "'".$random."'";
                    $address['id'] = "'".$rid."'";
                    $address['reason'] = "'测试'";
                    $m = '('.implode(',',$address).')';
                    if ($sid < $id + 49 || $k < 20){
                        $m .= ',';
                    }
                    $sql .= $m;
                }
            }
            M('member_balance')->execute($sql);
        }
    }

    public function address(){
        set_time_limit(0);
        $address = M('member_address')->where("receiver_id = 2 AND mid = 1000004")->find();
        foreach ($address as $k => $v){
            $address[$k] = "'".$v."'";
        }
        $from = 2000000;
        $to = 2220000;
        $id = $from;

        $ids = M('member_address')->field("max(mid) as id")->find();
        $ids = $ids['id'];
        if ($ids > $from){
            $id = $ids;
        }

        $rid = M('member_address')->field("max(receiver_id) as id")->find();
        $rid = $rid['id'];

        while ($id < $to){
            $id += 50;
            $sid = $id;

            $sql = "insert into `member_address` VALUES ";
            while ($sid < $id + 49){
                $rid++;
                $sid++;
                $address['mid'] = "'".$sid."'";
                $address['receiver_id'] = "'".$rid."'";
                $address['receiver_name'] = "'测试'";
                $m = '('.implode(',',$address).')';
                if ($sid < $id + 49){
                    $m .= ',';
                }
                $sql .= $m;
            }

            M('member_address')->execute($sql);
        }

    }

    public function project_member(){
        set_time_limit(0);
        $project_member = M('project_member')->where("mid = 1000021 AND project_id = 100907")->find();
        foreach ($project_member as $k => $v){
            $project_member[$k] = "'".$v."'";
        }
        $from = 2000000;
        $to = 2220000;
        $id = $from;

        $ids = M('project_member')->field("max(mid) as id")->find();
        $ids = $ids['id'];
        if ($ids > $from){
            $id = $ids;
        }
//        print_data($id);
        while ($id < $to){
            $id += 50;
            $sid = $id;

            $sql = "insert into `project_member` VALUES ";
            while ($sid < $id + 49){
                $sid++;
                $project_member['mid'] = "'".$sid."'";
                $random = rand(100020, 104019);
                $card = IdWork::createMemberCard($random);
                $project_member['project_id'] = "'".$random."'";
                $project_member['card_no'] = "'".$card['no']."'";
                $project_member['card_index'] = "'".$card['index']."'";
                $m = '('.implode(',',$project_member).')';

                $m .= ',';

                $sql .= $m;

                $random2 = rand(100020, 104019);
                if ($random2 == $random){
                    $random2 = $random + 300;
                }
                if ($random2 > 104019){
                    $random2 = $random2 - 500;
                }
                $card = IdWork::createMemberCard($random2);
                $project_member['project_id'] = "'".$random2."'";
                $project_member['card_no'] = "'".$card['no']."'";
                $project_member['card_index'] = "'".$card['index']."'";
                $m = '('.implode(',',$project_member).')';
                if ($sid < $id + 49){
                    $m .= ',';
                }

                $sql .= $m;
            }

            M('project_member')->execute($sql);
        }
    }

    public function member(){
        set_time_limit(0);
        $member = M('member')->where("mobile = 15004678169")->find();
        foreach ($member as $k => $v){
            $member[$k] = "'".$v."'";
        }
        $from = 2000000;
        $to = 2220000;
        $id = $from;

        $ids = M('member')->field("max(id) as id")->find();
        $ids = $ids['id'];
        if ($ids > $from){
            $id = $ids;
        }
        $mobile = 13900000000;
        while ($id < $to){
            $id += 50;
            $sid = $id;
            $sql = "insert into `member` VALUES ";
            while ($sid < $id + 49){
                $mobile++;
                $sid++;
                $member['id'] = "'".$sid."'";
                $member['mobile'] = "'".$mobile."'";
                $m = '('.implode(',',$member).')';
                if ($sid < $id + 49){
                    $m .= ',';
                }
                $sql .= $m;
            }
            M('member')->execute($sql);
        }

    }

    public function goods(){
//        $mall_goods = M('mall_goods')->where("id > 2000000")->delete();
//        $mall_goods_content = M('mall_goods_content')->where("goods_id > 2000000")->delete();
//        $mall_goods_sort = M('mall_goods_sort')->where("id > 2000000")->delete();
//        $mall_goods_uv = M('mall_goods_uv')->where("goods_id > 2000000")->delete();
//        $mall_product = M('mall_product')->where("goods_id > 2000000")->delete();
//        die();
        set_time_limit(0);
        $mall_goods = M('mall_goods')->where("id = 1004200")->find();
        $mall_goods_content = M('mall_goods_content')->where("goods_id = 1004200")->find();
        $mall_goods_sort = M('mall_goods_sort')->where("id = 1004200")->find();
        $mall_goods_uv = M('mall_goods_uv')->where("goods_id = 1004200")->find();
        $mall_product = M('mall_product')->where("goods_id = 1004200")->find();
        foreach ($mall_goods as $k => $v){
            $mall_goods[$k] = "'".$v."'";
        }
        foreach ($mall_goods_content as $k => $v){
            $mall_goods_content[$k] = "'".$v."'";
        }
        foreach ($mall_goods_sort as $k => $v){
            $mall_goods_sort[$k] = "'".$v."'";
        }
        foreach ($mall_goods_uv as $k => $v){
            $mall_goods_uv[$k] = "'".$v."'";
        }
        foreach ($mall_product as $k => $v){
            $mall_product[$k] = "'".$v."'";
        }

        $from = 2000000;
        $to = 2220000;
        $id = $from;

        $ids = M('mall_goods')->field("max(id) as id")->find();
        $ids = $ids['id'];
        if ($ids > $from){
            $id = $ids;
        }

        while ($id < $to){
            $id += 50;
            $sid = $id;
            $sql = "insert into `mall_goods` VALUES ";
            $sql2 = "insert into `mall_goods_content` VALUES ";
            $sql3 = "insert into `mall_goods_sort` VALUES ";
            $sql4 = "insert into `mall_goods_uv` VALUES ";
            $sql5 = "insert into `mall_product` VALUES ";
            while ($sid < $id + 49){
                $sid++;
                $random = rand(100020, 104019);
                $mall_goods['shop_id'] = "'".$random."001"."'";
                $mall_goods['id'] = "'".$sid."'";
                $mall_goods_content['goods_id'] = $mall_goods['id'];
                $uvid = 1000000 + $sid;
                $mall_goods_uv['id'] = "'".$uvid."'";
                $pdid = 2000000 + $sid;
                $mall_product['id'] = "'".$pdid."'";
                $mall_goods_sort['id'] = $mall_goods['id'];
                $mall_goods_uv['goods_id'] = $mall_goods['id'];
                $mall_product['goods_id'] = $mall_goods['id'];
                $goods = '('.implode(',',$mall_goods).')';
                $content = '('.implode(',',$mall_goods_content).')';
                $sort = '('.implode(',',$mall_goods_sort).')';
                $uv = '('.implode(',',$mall_goods_uv).')';
                $product = '('.implode(',',$mall_product).')';
                if ($sid < $id + 49){
                    $goods .= ',';
                    $content .= ',';
                    $sort .= ',';
                    $uv .= ',';
                    $product .= ',';
                }
                $sql .= $goods;
                $sql2 .= $content;
                $sql3 .= $sort;
                $sql4 .= $uv;
                $sql5 .= $product;
            }

            $model = M('mall_goods');
            $model->startTrans();

            M('mall_goods')->execute($sql);
            M('mall_goods_content')->execute($sql2);
            M('mall_goods_sort')->execute($sql3);
            M('mall_goods_uv')->execute($sql4);
            M('mall_product')->execute($sql5);

            $model->commit();
        }
    }


}

?>