<?php
// +----------------------------------------------------------------------
// 动态缩放图片
// +----------------------------------------------------------------------
$file = $_SERVER['DOCUMENT_ROOT'].'/'.$_GET['file'];
$width = $_GET['width'];
$height = $_GET['height'];
$new_file = $file.'!'.$width.'x'.$height.'.jpg';

if(!@is_file($file) || !is_numeric($width) || !is_numeric($height)){
    $file = $_SERVER['DOCUMENT_ROOT'].'/img/seable_logo.png';
    $new_file = $file.'!'.$width.'x'.$height.'.jpg';
}

include('../ThinkPHP/Library/Think/Image.class.php');
include('../ThinkPHP/Library/Think/Image/Driver/Gd.class.php');
if(strstr($file, '.') == '.gif'){
    include('../ThinkPHP/Library/Think/Image/Driver/GIF.class.php');
}

$image = new \Think\Image();
$image->open($file);
$type = $image->type();
$mime = $image->mime();

if($type == 'png' || $type == 'jpeg' || $type == 'gif'){
    if(!@is_file($new_file)){
        $image->thumb($width, $height);
        $image->save($new_file, null, 100);
    }
}else{
    $new_file = $file;
}

ob_clean();
header('Content-Type: '.$mime);
header('Cache-Control: max-age=315360000');
header("Expires:" . gmdate("d, d m y h:i:s", strtotime('+1 year')) . "gmt");
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header ('Pragma: cache');

if($type == 'jpeg'){
    $im = imagecreatefromjpeg($new_file);
    imagejpeg($im);
}else if($type == 'png'){
    $im = imagecreatefrompng($new_file);
    imagepng($im);
}else if($type == 'gif'){
    $im = imagecreatefromgif($new_file);
    imagegif($im);
}else if($type == 'bmp'){
    $im = imagecreatefromwbmp($new_file);
    imagepng($im);
}else{
    $im = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'].'/img/seable_logo.png');
    imagepng($im);
}

// 释放内存
imagedestroy($im);
?>