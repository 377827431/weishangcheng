<?php
return array(
    'ORDER_TIMEOUT'        => 28800, // 超过8小时则取消订单
    'SEND_TIMEOUT'         => 3600 * 24 * 3,
    'CDN'                  => (true?'https':'http').'://seller.xingyebao.com',
    'TMPL_PARSE_STRING'    => array(
        '__CDN__'          => (true?'https':'http').'://seller.xingyebao.com',
        '__PAY__'          => ''
    ),
    'PROTOCOL'             => (true?'https://':'http://'),
    'DEFAULT_WEIXIN'       => 'wxecdbd3aa2d27e833',
    'THIRD_APPID'          => 'wx6cc9d933ad90b954',
    'ADMIN_URL'            => (true?'https':'http').'://seller.xingyebao.com',
    'PAY_URL'              => (true?'https':'http').'://pay.xingyebao.com',
    'H5_HOST'              => (true?'https':'http').'://weishang.xingyebao.com',
    'SERVICE_URL'          => (true?'https':'http').'://service.xingyebao.com',
    'SESSION_OPTIONS'      => array(
        'prefix'           => 'session',
        'type'             => 'Redis',
        'name'             => 'PHPSESSID'
    ),
    'SESSION_REDIS_HOST'   => '127.0.0.1',
    'SESSION_REDIS_AUTH'   => ''
);
?>