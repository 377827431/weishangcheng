<?php
namespace Admin\Model;

use Think\Model;
use Common\Model\BaseModel;
use Org\Wechat\WechatAuth;

class TestSubscribe extends BaseModel{

    public function test($thirdAppid, $openid, $auth_code){
        sleep(1);
        $config = get_wx_config($thirdAppid);
        $wechatAuth = new WechatAuth($config, 'wx570bc396a51b8ff8');
        $result = $wechatAuth->thirdCustomSend($auth_code, $openid);
    }

      
}
?>