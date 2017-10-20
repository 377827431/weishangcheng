<?php
return array(
    'ORDER_TIMEOUT'        => 28800, // 超过8小时则取消订单
    'SEND_TIMEOUT'         => 3600 * 24 * 3,
    'CDN'                  => (true?'https':'http').'://seller.xingyebao.cn',
    'TMPL_PARSE_STRING'    => array(
        '__CDN__'          => (true?'https':'http').'://seller.xingyebao.cn',
        '__PAY__'          => ''
    ),
    'PROTOCOL'             => (true?'https://':'http://'),
    'DEFAULT_WEIXIN'       => 'wxecdbd3aa2d27e833',
    'THIRD_APPID'          => 'wx6cc9d933ad90b954',
    'ADMIN_URL'            => (true?'https':'http').'://seller.xingyebao.cn',
    'PAY_URL'              => (true?'https':'http').'://pay.xingyebao.cn',
    'H5_HOST'              => (true?'https':'http').'://weishang.xingyebao.cn',
    'SERVICE_URL'          => (true?'https':'http').'://service.xingyebao.cn',
    'SESSION_OPTIONS'      => array(
        'prefix'           => 'session',
        'type'             => 'Redis',
        'name'             => 'PHPSESSID'
    ),
    'SESSION_REDIS_HOST'   => '127.0.0.1',
    'SESSION_REDIS_AUTH'   => ''
);
?>