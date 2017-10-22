<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/9/18
 * Time: 16:13
 */

namespace Alipay;
require_once 'aop/request/ZhimaCreditAntifraudVerifyRequest.php';
require_once 'aop/AopClient.php';

class AntifraudVerify
{
    public static function verify($param){
        ini_set("display_errors", "On");
        error_reporting(E_ALL | E_STRICT);
        $c = new \AopClient();
        $c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
//$c->gatewayUrl = "https://openapi.alipaydev.com/gateway.do"; //沙箱
        $c->appId = "123";
//$c->appId = "2016081900287121";  //沙箱
        $c->rsaPrivateKey = '123' ;
        $c->format = "json";
        $c->charset= "GBK";
        $c->signType= "RSA2";
        $c->alipayrsaPublicKey = '123';
        $request = new \ZhimaCreditAntifraudVerifyRequest();
        $re = "{
            'mobile': '".$param['mobile']."',
            'name': '".$param['name']."',
            'product_code': 'w1010100000000002859',
            'transaction_id': '".$param['transaction_id']."',
            'cert_type': 'IDENTITY_CARD',
            'cert_no': '".$param['cert_no']."',
        }";
        $request->setBizContent($re);
        $response= $c->execute($request);
        return $response;
    }
}