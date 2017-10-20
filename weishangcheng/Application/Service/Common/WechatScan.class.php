<?php
namespace Service\Common;

use Org\Wechat\Wechat;
/**
 * 微信 - 扫描带参数二维码处理类
 *
 * @author lanxuebao
 *        
 */
class WechatScan
{
    private $wechat;
    private $data;
    private $isNew; // 是否为新会员

    public function __construct(Wechat $wechat)
    {
        $this->$wechat = $wechat;
        $this->data = $wechat->request();
    }

    public function parse($scene_id, $isNew)
    {
        // 保存扫码记录
        $Model = M('wx_qrcode_record');
        $Model->add(array(
            'openid'    => $this->data['FromUserName'],
            'isnew'     => $isNew,
            'eventkey'  => $scene_id,
            'ticket'    => $this->data['Ticket'],
            'createtime'=> $this->data['CreateTime']
        ));
        
        $this->scene_id = $scene_id;
        $this->isNew = $isNew;
        
        $Model = M('wx_qrcode');
        $qr = $Model->where("scene_id='{$this->scene_id}'")->find();
        
        // 二维码解析方法
        if (empty($qr) || empty($qr['parse_fn']) || ! method_exists($this, $qr['parse_fn'])) {
            return false;
        }
        
        call_user_func(array( $this, $qr['parse_fn'] ), $qr);
    }
}

?>