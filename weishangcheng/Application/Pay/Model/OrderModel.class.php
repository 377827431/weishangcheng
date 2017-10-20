<?php 
namespace Pay\Model;

use Common\Model\BaseModel;
use Common\Model\OrderStatus;

class OrderModel extends BaseModel{
    protected $tableName = 'trade';
    protected $pk = 'tid';
    
    public function createWxPayOrder($data, $tids){
        $config = get_wx_config($data['appid']);
        if(!$config['mch_id']){
            E('mch_id不存在');
        }
        
        $JsApiApy = new \Org\WxPay\JsApiPay();
        
        // 查找上次生成的支付
        $sql = "SELECT tid, created, total_fee, appid, mch_id, prepay_id
                FROM trade_pay_create
                WHERE out_trade_no='{$tids}' AND type=1 AND openid='{$data['openid']}' AND trade_type='JSAPI'
                ORDER BY tid DESC
                LIMIT 1";
        $wxorder = $this->query($sql);
        $wxorder = $wxorder[0];

        if(!empty($wxorder) && $wxorder['created'] + 7000 > NOW_TIME && floatval($data['total_fee']) == floatval($wxorder['total_fee'])){
            $param = $JsApiApy->GetParameters(array(
                'appid' => $wxorder['appid'],
                'mch_id' => $wxorder['mch_id'],
                'prepay_id' => $wxorder['prepay_id'],
                'trade_type' => $wxorder['trade_type'],
            ));
            $param['payment'] = $data['total_fee'];
            return $param;
        }

        // 请求数据实体类
        $input = new \Org\WxPay\WxPayUnifiedOrder();
        $input->SetBody($data['body']);
        if($data['detail']){$input->SetDetail($data['detail']);}
        if($data['attach']){$input->SetAttach($data['attach']);}
        $totalFee = bcmul($data['total_fee'], 100);
        $input->SetTotal_fee($totalFee);
        $input->SetAttach($data['attach'] ? $data['attach'] : uniqid());  // 随机校验码
        $input->SetTime_expire($data['time_expire'] ? $data['time_expire'] : date('YmdHis', strtotime('+8 minute')));
        $input->SetNotify_url($data['notify_url'] ? $data['notify_url'] : 'http://pay.xingyebao.com/wxpaynotify');
        $input->SetTrade_type(IS_APP ? 'APP' : 'JSAPI');
        $input->SetOpenid($data['openid']);
        $input->SetAppid($config['appid']);//公众账号ID
        $input->SetMch_id($config['mch_id']);//商户号
        
        // 执行创建订单
        $result = $JsApiApy->createOrder($input);
        
        // 异常处理
        if(isset($result['errcode'])){
            $this->error = $result['errmsg'];
            return;
        }
        
        // 保存本次记录
        $values = $input->GetValues();
        M('trade_pay_create')->add(array(
            'tid'         => $values['out_trade_no'],
            'type'        => 1,
            'out_trade_no'=> $tids,
            'total_fee'   => $data['total_fee'],
            'time_expire' => $values['time_expire'],
            'trade_type'  => $values['trade_type'],
            'openid'      => $values['openid'],
            'notify_url'  => $values['notify_url'],
            'mch_id'      => $values['mch_id'],
            'appid'       => $values['appid'],
            'body'        => $values['body'],
            'prepay_id'   => substr($result['package'], 10),
            'created'     => $result['timeStamp'],
            'client_ip'   => $values['spbill_create_ip'],
            'attach'      => $values['attach']
        ));

        $result['payment'] = $data['total_fee'];
        return $result;
    }
    
    /**
     * 结算页面后立即创建支付
     * 不检测商品信息变更，允许超售(付款减库存)
     */
    public function createPay($tidList, $appid, $openid){
        if(!$tidList || !$appid || !$openid || !is_array($tidList)){
            $this->error = '创建支付参数异常';
            return;
        }
        
        sort($tidList);
        foreach ($tidList as $tid){
            if(!$tid || !is_numeric($tid)){
                $this->error = 'tid异常';
                return;
            }
        }
        $tids = implode(',', $tidList);
        
        // 判断订单状态
        $sql = "SELECT trade.tid, trade.payment, trade.`status`, trade.kind, trade.total_quantity, trade_order.title
                FROM trade
                INNER JOIN trade_order ON trade_order.oid=trade.tid
                WHERE trade.tid IN ({$tids})";
        $tradeList = $this->query($sql);
        $count = count($tradeList);
        if($count != count($tidList)){
            $this->error = '订单ID异常:不存在';
            return;
        }
        
        // 本次需要支付的总额
        $payment = 0;
        foreach ($tradeList as $trade){
            if($trade['status'] != OrderStatus::WAIT_PAY){
                $this->error = '订单['.$trade['tid'].']不可支付，状态已变更为：'.OrderStatus::getById($trade['status']);
                return;
            }
            
            $payment = bcadd($payment, $trade['payment'], 2);
        }

        $data = array(
            'body'      => '订单结算中心-'.$tradeList[0]['title'].'('.($count > 0 ? '等'.($count-1).'种商品' : $tradeList[0]['total_quantity'].'件').')',
            'total_fee' => floatval($payment),
            'appid'     => $appid,
            'openid'    => $openid,
        );
        
        return $this->createWxPayOrder($data, $tids);
    }
}
?>