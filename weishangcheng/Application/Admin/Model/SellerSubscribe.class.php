<?php
namespace Admin\Model;

use Think\Model;
use Org\IdWork;
use Common\Model\BaseModel;
use Common\Model\PayType;
use Common\Model\MessageType;

class SellerSubscribe extends BaseModel{

    /**
     * 通知店铺订单付款了
     */
    public function orderPaid($tid){
        if(!is_numeric($tid)){
            E('订单号格式错误');
        }
        
        // 订单基础信息
        $sql = "SELECT tid, buyer_id, seller_id, seller_name, kind, total_quantity, created,
                    pay_time, (SELECT mid FROM shop WHERE shop.id=trade.seller_id) AS seller_mid,
                    paid_balance + paid_wallet + paid_fee AS total_paid, paid_score, pay_type,
                    receiver_name, receiver_mobile, receiver_province, receiver_city, receiver_county, receiver_detail,
					(SELECT shop.mid FROM shop WHERE shop.id=trade.seller_id) AS shop_mid
                FROM trade WHERE tid='{$tid}'";
        $trade = $this->query($sql);
        $trade = $trade[0];
        if(!$trade || !$trade['seller_mid']){
            return;
        }

        // 消息接收人
        $project = get_project($trade['seller_id'], true);
        $member = $this->getProjectMember(array('id' => $trade['shop_mid']), $project['id']);
        if($member['subscribe'] == 0){
            return;
        }
        
        // 下单金额
        $totalPay = '';
        if($trade['total_paid'] > 0 && $trade['paid_score'] > 0){
            $totalPay = $trade['total_paid'].'元 + '.$trade['paid_score'].$project['score_alias'];
        }else if($trade['total_paid'] > 0){
            $totalPay = $trade['total_paid'].'元';
        }else if($trade['paid_score'] > 0){
            $totalPay = floatval($trade['paid_score']).$project['score_alias'];
        }

        // 购买的产品
        $orders = $this->query("SELECT title, quantity FROM trade_order WHERE tid='{$trade['tid']}'");
        $title = $remark = '';
        $address = '收货地址：'.$trade['receiver_name'].' '.$trade['receiver_mobile'].' '.$trade['receiver_province'].$trade['receiver_city'].$trade['receiver_county'].$trade['receiver_detail'];
        if(count($orders) == 1){
            $title = $orders[0]['title'].'('.$orders[0]['quantity'].'件)';
            $remark = $address;
        }else{
            $title = mb_substr($orders[0]['title'], 0, 10).'...共'.$trade['total_quantity'].'件';
            foreach ($orders as $i=>$order){
                $remark .= ($i == 0 ? '' : '\r\n').$order['title'].'x'.$order['quantity'].'件';
            }
            $remark .= '\r\n'.$address;
        }
        
        $this->lPublish('MessageNotify', array(
            'type'    => MessageType::NEW_ORDER,
            'openid'  => $member['openid'],
            'appid'   => $member['appid'],
            'data'    => array(
                'url' => $member['url'].'/order/detail?tid='.$trade['tid'],
                'title'      => '您的店铺有新订单已付款，记得按时发货哦',
                'shop_name'  => $trade['seller_name'], // 店铺名称
                'goods_name' => $title, // 商品名称
                'order_time' => date('Y年m月d日 H:i', $trade['created']), // 下单时间
                'order_fee'  => $totalPay, // 下单金额
                'pay_status' => '已付款('.PayType::getById($trade['pay_type']).')', // 付款状态
                'remark'     => $remark
            )
        ));
    }
}
?>