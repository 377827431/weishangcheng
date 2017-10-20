<?php

function curlDownload($remote,$local) {
    $cp = curl_init($remote);

    $SSL = substr($remote, 0, 8) == "https://" ? true : false;
    if ($SSL) {
        curl_setopt($cp, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($cp, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
    }

    $folder = substr($local, 0, strrpos($local, '/'));
    if (!file_exists($folder) && !mkdir($folder, 0777, true)) {
        E('无读写权限');
    }

    $fp = fopen($local,"w");
    curl_setopt($cp, CURLOPT_FILE, $fp);
    curl_setopt($cp, CURLOPT_HEADER, 0);
    curl_setopt($cp, CURLOPT_TIMEOUT, 10);
    curl_exec($cp);
    curl_close($cp);
    fclose($fp);
}

// 图文地址
$original_url = 'https://mp.weixin.qq.com/s/KfO5y8XyPSisyTm3DsRrzQ';

// 保存文件路径
$filepath = './upload/tool/'.date('Ym').'/'.md5($original_url);
if(!is_dir($filepath)){
    mkdir($filepath, 0777, true);
}

// 检测本地是否已下载过此文章
$filename = $filepath.'/original.html';
if(!is_file($filename)){
    curlDownload($original_url, $filename);
}

// 读取源文件
$html = file_get_contents($filename);
// 去除script引用
$html = preg_replace('/<script[\s\S]*?<\/script>/i', '', $html);
// 去除被注释的html
$html = preg_replace('/<\!--.*-->/i', '', $html);
// 去除外部样式引用
$html = preg_replace('/<link.*>/i', '', $html);
// 去除内部样式
$html = preg_replace('/<style.*>/i', '', $html);

// 匹配所有图片(下载到本地，腾讯有防盗链)
preg_match_all('/<img data-src="(.*?)"/i', $html, $imgList);
preg_match_all('/url\(&quot;(.*?)&quot;/i', $html, $backgroundList);

// 下载图片
$imgs = array();
$imgs = array_merge($imgs, $imgList[1]);
$imgs = array_merge($imgs, $backgroundList[1]);
$imgs = array_unique($imgs);
foreach ($imgs as $src){
    $ext = str_replace('=', '.', strrchr($src, '='));
    $url = $filepath.'/'.md5($src).$ext;
    
    if(!is_file($url)){
        curlDownload($src, $url);
    }
    $src = preg_replace('/\//', '\\/', $src);
    $src = preg_replace('/\./', '\.', $src);
    $src = preg_replace('/\?/', '\?', $src);
    $html = preg_replace('/('.$src.')/i', ltrim($url, '.'), $html);
}

// 替换本地图片路径
$html = preg_replace('/(<img data-src=)/i', '<img src=', $html);

// 插入样式
echo '<link rel="stylesheet" href="/css/news-tool.css"></link>';

// 输出内容
echo $html;
echo '<script src="//cdn.bootcss.com/jquery/3.2.1/jquery.min.js"></script>';
echo '<script src="/js/undo.js"></script>';
echo '<script src="/js/news-tool.js"></script>';
?>