<?php
return array(
    'ORDER_TIMEOUT'        => 28800, // 超过8小时则取消订单
    'SEND_TIMEOUT'         => 3600 * 24 * 3,
    'CDN'                  => (false?'https':'http').'://seller.xingyebao.com',
    'TMPL_PARSE_STRING'    => array(
        '__CDN__'          => (false?'https':'http').'://seller.xingyebao.com',
        '__PAY__'          => ''
    ),
    'PROTOCOL'             => (true?'https://':'http://'),
    'DEFAULT_WEIXIN'       => 'wxecdbd3aa2d27e833',
    'THIRD_APPID'          => 'wx6cc9d933ad90b954',
    'ADMIN_URL'            => (false?'https':'http').'://seller.xingyebao.com',
    'PAY_URL'              => (false?'https':'http').'://pay.xingyebao.com',
    'H5_HOST'              => (false?'https':'http').'://weishang.xingyebao.com',
    'SERVICE_URL'          => (false?'https':'http').'://service.xingyebao.com',
    'SESSION_OPTIONS'      => array(
        'prefix'           => 'session',
        'type'             => 'Redis',
        'name'             => 'PHPSESSID'
    ),
    'SESSION_REDIS_HOST'   => 'r-2ze95be0d4d1d5d4.redis.rds.aliyuncs.com',
    'SESSION_REDIS_AUTH'   => 'Jy5040309884'
);
?>