<?php
namespace Pay\Controller;
use Org\WxPay\WxPayNotify;
use Think\Cache\Driver\Redis;
use Common\Model\PayType;

class WxpaynotifyController extends WxPayNotify
{
    public function index($data, &$error){
        $transactionId = $data['transaction_id'];
        $outTradeNo = $data['out_trade_no'];

        $Model = M();
        $wxOrder = $Model->query("SELECT tid, out_trade_no, openid, total_fee, attach, transaction_id FROM trade_pay_create WHERE tid='{$outTradeNo}'");
        $wxOrder = $wxOrder[0];
        if(empty($wxOrder)){
            return 'out_trade_no不存在';
        }else if($wxOrder['transaction_id']){
            return true;
        }

        $settlement = bcdiv($data['total_fee'], 100, 2);
        if($wxOrder['total_fee'] != $settlement){
            $error= 'total_fee与本系统不匹配';
        }
        
        // 用户最终支付金额
        if(isset($data['settlement_total_fee'])){
            $settlement = bcdiv($data['settlement_total_fee'], 100, 2);
        }
        
        // 保存
        $result = $Model->execute("UPDATE trade_pay_create SET settlement_fee='{$settlement}', transaction_id='{$transactionId}' WHERE tid='{$outTradeNo}' AND transaction_id=''");
        if(!$result){
            return true;
        }
        
        // 追加到支付
        $data['message'] = $error;
        $data['created'] = date('Y-m-d H:i:s');
        M('wx_pay_notify')->add($data, null, true);
        
        // 通知订单已支付，异步处理数据
        $redis = new Redis();
        $tidArray = explode(',', $wxOrder['out_trade_no']);
        foreach ($tidArray as $tid){
            $redis->lPush('TradePaid', array('tid' => $tid, 'pay_time' => strtotime($data['time_end']), 'pay_type' => PayType::WEIXIN, 'paid_fee' => $wxOrder['total_fee'], 'paid_score' => 0, 'transaction_id' => $transactionId, 'out_trade_no' => $outTradeNo));
        }
        $redis->publish('TradePaid', NOW_TIME);
        return true;
    }
}
?>