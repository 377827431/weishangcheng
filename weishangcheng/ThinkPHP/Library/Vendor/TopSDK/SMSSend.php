<?php

function FcSmsNumSend($data, $phone, $num){
    include "TopSdk.php";
    $c = new TopClient;
    $c->appkey = '23370420';
    $c->secretKey = '055951e28f60452d6845b27115b28d51';
    $req = new AlibabaAliqinFcSmsNumSendRequest;
    $req->setExtend($data['id']);
    $req->setSmsType("normal");
    $req->setSmsFreeSignName("手机验证");
    $req->setSmsParam("{\"name\":\"用户\",\"code\":\"".$num."\"}");
    $req->setRecNum("{$phone}");
    $req->setSmsTemplateCode("SMS_9675002");
    return $c->execute($req);
}

function PushSMS ($datalist)
{
    include "TopSdk.php";
    $c = new TopClient;
    $c->appkey = '23370420';
    $c->secretKey = '055951e28f60452d6845b27115b28d51';
    $res = array();
    foreach( $datalist as $data )
    {
        $req = new AlibabaAliqinFcSmsNumSendRequest;
        $req->setExtend($data['id']);
        $req->setSmsType("normal");
        $req->setSmsFreeSignName($data['sign']);
        $req->setSmsParam(json_encode($data['param']));
        $req->setRecNum($data['phone']);
        $req->setSmsTemplateCode($data['code']);
        $res[]=$c->execute($req);
    }
    return $res;
}