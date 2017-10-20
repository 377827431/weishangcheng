<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用入口文件

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG', true);
die "123";
// 通用设置PATH_INFO，如果保证PHP能正常获取到
if (PHP_SAPI == 'cli') {
    $_SERVER['DOCUMENT_ROOT'] = __DIR__;
}else{
	// 跨域域名白名单检测
	if(isset($_SERVER['HTTP_ORIGIN'])){
		include_once('white.domain.php');
	}
	
	// 部署后请打开此段代码
    //define('IS_APP', preg_match('/(Mobile ).*(Appcan)/', $_SERVER['HTTP_USER_AGENT']));
    //define('IS_WEIXIN', preg_match('/(MicroMessenger)/', $_SERVER['HTTP_USER_AGENT']));
    define('IS_APP', false);
    define('IS_WEIXIN', false);
}

// 定义应用目录
define('APP_PATH', '../Application/');

// 引入ThinkPHP入口文件
require '../ThinkPHP/ThinkPHP.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单