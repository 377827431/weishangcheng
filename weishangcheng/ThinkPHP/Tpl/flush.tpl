<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>实时输出</title>
<meta name="viewport" content="initial-scale=1, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
<meta content="yes" name="apple-mobile-web-app-capable">
<meta content="red" name="apple-mobile-web-app-status-bar-style">
<meta name="format-detection" content="telphone=no">
<style type="text/css">
body{color:#666;background-color:#eee;-webkit-font-smoothing:antialiased;-webkit-tap-highlight-color:rgba(255,255,255,0);margin:0;padding:0}
.msg-img{margin-top:100px;height:151px;background:url(/img/mall/error.gif) 50% 0 no-repeat}
.msg-content{margin-top:30px;line-height:22px;color:#333;font-size:16px;text-align:center}
.msg-ip{font-size:12px;color:#999}
.msg-img.img-ok{background-position:50% -156px}
.btn-back{padding: 5px 20px;color: #999;text-decoration: none;}
.back{display:none;position:absolute;bottom:50px;left:0;right:0;text-align:center}
.msg-item{font-size:14px;margin:0 auto;max-width:414px}
.msg-over{text-align:center;color:red}
</style>
</head>
<body>
	<div class="msg-img"></div>
	<div class="msg-content">
	    <div class="msg-error"><?php echo $message;?></div>
	</div>
</body>
</html>
