<?php
define('__ADMIN__', C('APP_SUB_DOMAIN_DEPLOY') ? '' : '/admin');
return array(
	//'配置项'=>'配置值'
    'LAYOUT_ON'             =>  true, // 是否启用布局
    'LAYOUT_NAME'           =>  '_layout',
    //'URL_HTML_SUFFIX'       =>  '',  // URL伪静态后缀设置
    'URL_PARAMS_BIND_TYPE'  =>  0, // URL变量绑定的类型 0 按变量名绑定 1 按变量顺序绑定
    'HTML_CACHE_ON'         =>  false, // 开启静态缓存
    'HTML_CACHE_TIME'       =>  120,   // 全局静态缓存有效期（秒）
    'HTML_FILE_SUFFIX'      =>  '.shtml', // 设置静态缓存文件后缀
    'HTML_CACHE_RULES'      =>  array(  // 定义静态缓存规则
         'login:index'      =>  'login'
    ),
    'SESSION_PREFIX'        => '',
    'SESSION_OPTIONS'       => array()
);