<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/4/7
 * Time: 9:33
 */

namespace Seller\Model;
use Common\Model\BaseModel;
use Think\Page;

class OrderModel extends BaseModel{
    protected $tableName = 'trade';

    public function getShortList($where, $limit = 0, $offset = 20){
        $field = "trade.tid, trade.seller_id, (trade.paid_wallet + trade.paid_balance + trade.payment) as paid_fee, FROM_UNIXTIME(trade.created, '%Y-%m-%d %H:%i:%S') as created,
                  trade.kind, trade.status, trade.pay_type, trade.buyer_nick, trade.total_quantity as total_num,
                  trade.type, trade.buyer_rate, trade.total_freight as post_fee, trade.payment, trade.paid_wallet as paid_no_balance,
                  `order`.oid, `order`.goods_id, trade.buyer_id, trade.sign_time,
	              `order`.title, `order`.pic_url, `order`.price, `order`.original_price,
                  `trade`.pay_type AS ppt, `order`.quantity as num, `order`.sku_json";
        $list = $this->table('trade_seller')
            ->alias("seller")
            ->field($field)
            ->join("trade ON seller.tid = trade.tid")
            ->join("trade_order AS `order` ON `order`.oid=trade.tid")
            ->where($where)
            ->order("trade.created DESC")
            ->limit($offset, $limit)
            ->select();
        $project_ids = array();
        foreach ($list as $k => $v){
            $p_id = substr($v['seller_id'], 0 , -3);
            if (!in_array($p_id, $project_ids)){
                $project_ids[] = $p_id;
            }
        }
        if (!empty($project_ids)){
            $project_ids = implode(',', $project_ids);
            $project = $this->table('project')->where("id IN ({$project_ids})")->select();
            $p_alias = array();
            foreach ($project as $k => $v){
                $p_alias[$v['id']] = $v['alias'];
            }
        }

        $data = array();

        // 字段处理
        foreach($list as $index=>$item){
            if(!isset($data[$item['tid']])){
                $Model = new \Common\Model\OrderModel();
                $Model->handle($item);

                $showRate = 0;
                if(strtotime($item['sign_time']) && $item['buyer_rate'] == 0){
                    $showRate = 1;
                }

                $data[$item['tid']] = array(
                    'tid'       => $item['tid'],
                    'type'      => $item['type'],
                    'created'   => $item['created'],
                    'status'    => $item['status'],
                    'status_str'=> $item['status_str'],
                    'post_fee'  => $item['post_fee'],
                    //'receiver_name'   => $item['receiver_name'],
                    //'receiver_mobile'   => $item['receiver_mobile'],
                    'kind'      => $item['kind'],
                    'payment'   => $item['payment'],
                    'pay_type'  => $item['pay_type'],
                    'show_rate'  => $showRate,
                    'alias'     => $p_alias[substr($item['seller_id'], 0 , -3)],
                    'buyer_id' => $item['buyer_nick'],
                    'total_num' => $item['total_num'],
                    'paid_fee' => $item['paid_fee'],
                    'orders'    => array()
                );
            }

            $data[$item['tid']]['orders'][] = array(
                'oid' => $item['oid'],
                'goods_id'  => $item['goods_id'],
                'product_id'=> $item['product_id'],
                'title' => $item['title'],
                'num' => $item['num'],
                'original_price' => $item['original_price'],
                'price' => $item['price'],
                'pic_url' => $item['pic_url'],
                'pay_type' => $item['ppt'],
                'spec' => get_spec_name($item['sku_json'])
            );
        }

        return array_values($data);
    }
    public function getTradeByTid($tid){
        $field = "tid, (paid_wallet + paid_balance + payment) as paid_fee, FROM_UNIXTIME(created, '%Y-%m-%d %H:%i:%S') as created, kind, status, pay_type,
                  type, buyer_rate, total_freight as post_fee, payment, paid_wallet as paid_no_balance,
                  buyer_id, sign_time, pay_type AS ppt, total_fee, discount_fee, receiver_name, receiver_detail, buyer_remark, receiver_mobile,
                   FROM_UNIXTIME(pay_time, '%Y-%m-%d %H:%i:%S') as pay_time, seller_remark, express_id";
        $trade = $this->field($field)->where("tid='%s'", $tid)->find();
        if(empty($trade)){
            $this->error = '订单不存在';
            return null;
        }

        // 订单详情
        $trade['orders'] =
            $this->query("SELECT `order`.*, refund.*
                    /*
                    , difference.checkout, SUM(difference.total_fee) AS total_diff, GROUP_CONCAT(difference.mid) AS diff_mid
                    */
                     FROM trade_order AS `order`
                     LEFT JOIN trade_refund AS refund ON refund.refund_id=`order`.oid
                     /*
                     LEFT JOIN trade_difference AS difference ON difference.oid=`order`.oid
                     */
                     WHERE `order`.tid='{$tid}'
                     GROUP BY `order`.oid");

        // 查找物流信息
        $trade['express'] = array();
        $staticM = D('Common/Static');
        if($trade['express_no'] == ''){
            $express = $staticM->express(false, 'id');
            $trade['express_name'] = $express[$trade['express_id']]['name'];
        }else if(strpos($trade['express_no'], ':')){  // 快递公司名称:运单号
            $express = $staticM->express(false, 'name');
            $expressList = explode(';', $trade['express_no']);
            foreach($expressList as $item){
                $detail = explode(':', $item);
                $trade['express'][] = array('name' => $detail[0], 'code' => $express[$detail[0]]["code"], 'no' => $detail[1]);
            }
        }else{   // 直接运单号
            $express = $staticM->express();
            foreach($express as $k=>$v){
                if($trade['express_id'] == $v["id"]){
                    $trade['express'][] = array('name' => $v["name"], 'code' => $v["code"], 'no' => $trade['express_no']);
                    break;
                }
            }
        }
        $d_time = strtotime($trade['pay_time']) + C('SEND_TIMEOUT') - NOW_TIME;
        $days = floor($d_time/(3600 * 24));
        $d_time = $d_time - $days * 3600 * 24;
        $hours = floor($d_time/3600);
        $d_time = $d_time - $hours * 3600;
        $minutes = floor($d_time/60);
        $trade['d-h-m'] = '';
        if ($days > 0){
            $trade['d-h-m'] .= $days.'天';
        }
        if ($hours > 0){
            $trade['d-h-m'] .= $hours.'小时';
        }
        if ($minutes > 0){
            $trade['d-h-m'] .= $minutes.'分钟';
        }
        if ($d_time <= 0){
            $trade['d-h-m'] = '0分钟';
        }
        $Model = new \Common\Model\OrderModel();
        $Model->handle($trade);
        return $trade;
    }


}
?>
