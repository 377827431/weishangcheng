<?php
namespace Admin\Model;

use Think\Model;
use Org\Wechat\WechatAuth;
use Common\Model\BaseModel;
use Common\Model\MessageType;
use Org\IdWork;

/**
 * 发送模板消息
 * @author Administrator
 *
 *
 */
class MessageSubscribe extends BaseModel{
    
    /**
     * 模板消息
     */
    public function template($param){
        $config = get_wx_config($param['appid']);
        $templateId = $config['template'][$param['type']];
        if(!$templateId){
            return;
        }
        
        switch ($param['type']){
            case MessageType::BALANCE_CHANGE: // 资金变动通知
                $template = $this->getBalanceChangeTemplate($templateId, $param['data']);
                break;
            case MessageType::REFUND_RESULT: // 退款申请审核结果
                $template = $this->getRefundTemplate($templateId, $param['data']);
                break;
            case MessageType::NEW_ORDER: // 新订单通知
                $template = $this->getNewOrderTemplate($templateId, $param['data']);
                break;
            case MessageType::MEMBER_GRADE_CHANGE: // 会员级别变更提醒
                $template = $this->getMemberGradeChangeTemplate($templateId, $param['data']);
                break;
            case MessageType::REFUND: // 退款进度
                $template = $this->getRefundTemplate($templateId, $param['data'], true);
                break;
            case MessageType::WAIT_DO_WORK: // 待办消息通知
                $template = $this->getWaitDoWorkTemplate($templateId, $param['data']);
                break;
        }
        
        return $this->sendTemplate($param['openid'], $template, $config);
    }
    
    /**
     * 发送微信模板消息通知
     * @param string $templateId 模板ID
     * @param string $openid     接收消息用户openid
     * @param string $template   模板内容
     * @param string $appid      用哪个公众号发送消息
     */
    public function sendTemplate($openid, $template, $config){
        $appid = $config['appid'];
        if($config['third_appid'] && $config['appid'] != $config['third_appid']){
            $config = get_wx_config($config['third_appid']);
        }
        $WechatAuth = new WechatAuth($config, $appid);
        return $WechatAuth->sendTemplate($openid, $template);
    }
    
    /**
     * 发送微信客服消息(文本)
     * @param string $openid
     * @param string $appid
     * @param string $message
     */
    public function sendText($openid, $appid, $message){
        $config = get_wx_config($appid);
        $WechatAuth = new WechatAuth($config);
        return $WechatAuth->sendText($openid, $message);
    }
    
    /**
     * 资金流水变动
     */
    public function getBalanceChangeTemplate($templateId, $data){
        return array(
            'template_id'  => $templateId,
            'url'          => $data['url'],
            'data'         => array(
                'first'    => array('value' => $data['title'], 'color' => '#173177'),
                'keyword1' => array('value' => $data['username']), // 用户名
                'keyword2' => array('value' => $data['time']), // 变动时间
                'keyword3' => array('value' => $data['value']), // 金额变动
                'keyword4' => array('value' => $data['balance']), // 可用余额
                'keyword5' => array('value' => $data['reason']), // 变动原因
                'remark'   => array('value' => $data['remark'])
            )
        );
    }
    
    /**
     * 退款审核结果
     */
    public function getRefundTemplate($templateId, $data, $new = false){
        if(!$new){
            return array(
                "template_id"  => $templateId,
                "url"          => $data['url'],
                "data" => array(
                    "first"    => array("value" => $data['title'], 'color' => '#173177'),
                    "keyword1" => array("value" => $data['status']), // 状态
                    "keyword2" => array("value" => $data['money']), // 退款金额
                    "keyword3" => array("value" => $data['explain']), // 审核说明
                    "remark"   => array("value" => $data['remark'])
                )
            );
        }
        
        return array(
            "template_id"  => $templateId,
            "url"          => $data['url'],
            "data" => array(
                "first"      => array("value" => $data['title'], 'color' => '#173177'),
                "keyword1"   => array("value" => $data['tid']), // 订单编号
                "keyword2"   => array("value" => $data['progress']), // 当前进度
                "keyword3"   => array("value" => $data['goods']), // 商品名称
                "keyword4"   => array("value" => $data['refund_fee']), // 退款金额
                "remark"     => array("value" => $data['remark'])
            )
        );
    }
    
    /**
     * 获取新订单模板消息
     */
    public function getNewOrderTemplate($templateId, $data){
        return array(
            'template_id' => $templateId,
            'url' => $data['url'],
            'data' => array(
                'first'     => array('value' => $data['title'], 'color' => '#173177'),
                'keyword1'  => array('value' => $data['shop_name']), // 店铺名称
                'keyword2'  => array('value' => $data['goods_name']), // 商品名称
                'keyword3'  => array('value' => $data['order_time']), // 下单时间
                'keyword4'  => array('value' => $data['order_fee']), // 下单金额
                'keyword5'  => array('value' => $data['pay_status']), // 付款状态
                'remark'    => array('value' => $data['remark'])
            )
        );
    }
    
    /**
     * 会员级别变更提醒
     */
    public function getMemberGradeChangeTemplate($templateId, $data){
        return array(
            'template_id' => $templateId,
            'url'  => $data['url'],
            'data' => array(
                'first'   => array('value' => $data['title'], 'color' => '#173177'),
                'grade1'  => array('value' => $data['old_grade']),
                'grade2'  => array('value' => $data['new_grade']),
                'time'    => array('value' => $data['time']),
                'remark'  => array('value' => empty($data['remark']) ? '感谢您的支持，祝您生活愉快！' : $data['remark'])
            )
        );
    }
    
    /**
     * 待办消息提醒
     */
    public function getWaitDoWorkTemplate($templateId, $data){
        return array(
            'template_id'  => $templateId,
            'url'          => $data['url'],
            'data'         => array(
                'first'    => array('value' => $data['title'], 'color' => '#173177'),
                'keyword1'  => array('value' => $data['name']),
                'keyword2'  => array('value' => $data['time']),
                'remark'    => array('value' => $data['remark'])
            )
        );
    }

    /**
     * 保存mall_goods_uv
     */
    public function viewed($param){
        $buyerId = $param['buyer_id'];
        $goodsId = $param['goods_id'];
        $catId = $param['cat_id'];
        $modify = date('His');

        $Model = M();
        $id = IdWork::getLikeId();
        $where = "WHERE id='{$id}' AND user_id={$buyerId} AND goods_id={$goodsId}";
        $existsUv = $Model->query("SELECT id FROM mall_goods_uv {$where} LIMIT 1");
        if(count($existsUv) > 0){
            $Model->execute("UPDATE mall_goods_uv SET times=times+1, is_del=0, modify='{$modify}' ".$where);
            $Model->execute("UPDATE mall_goods_sort SET pv=pv+1 WHERE id=".$goodsId);
        }else{
            $Model->execute("INSERT INTO mall_goods_uv SET id='{$id}', user_id={$buyerId}, goods_id={$goodsId}, cat_id='{$catId}', modify='{$modify}', is_del=0");
            $Model->execute("UPDATE mall_goods_sort SET pv=pv+1, uv=uv+1 WHERE id=".$goodsId);
        }
    }
    
}
?>