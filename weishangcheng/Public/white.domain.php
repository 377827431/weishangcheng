<?php
if(!defined('APP_DEBUG')){
	header("HTTP/1.0 404 Not Found");
	exit();
}else if(APP_DEBUG){
	header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
	return;
}

$list   = array('seller.xingyebao.com', 'weishang.xingyebao.com', 'service.xingyebao.com', 'pay.xingyebao.com');
$domain = preg_replace('/^(http)(s?):\/\//', '', $_SERVER['HTTP_ORIGIN']);
if(!in_array($domain, $list)){
	header("HTTP/1.0 404 Not Found");
	exit();
}

unset($list);
unset($domain);
header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
?>