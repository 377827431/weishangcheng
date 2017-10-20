<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
    
    $ip = get_client_ip();
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>{:('[title]' == '['.'title'.']' ? C('PROJECT.name') : '[title]')}</title>
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
.back{position:absolute;bottom:50px;left:0;right:0;text-align:center}
</style>
</head>
<body>
	<div class="msg-img <?php echo $status ? ' img-ok' : '' ?>"></div>
	<div class="msg-content">
	    <p class="msg-error"><?php echo $status ? $message : $error;?></p>
<!-- 	    <p class="msg-ip"><?php echo $ip; ?></p> -->
	    <p class="msg-ip"><a href="<?php echo($jumpUrl); ?>" class="btn-back">返 回</a></p>
	</div>
	<div class="back"><a id="wait" class="btn-back" href="<?php echo($jumpUrl); ?>"><?php echo($waitSecond); ?></a></div>
	<script type="text/javascript">
	(function(){
	var wait = document.getElementById('wait');
	var interval = setInterval(function(){
		var time = --wait.innerHTML;
		if(time <= 0) {
			location.href = wait.href;
			clearInterval(interval);
		};
	}, 1000);
	})();
	</script>
</body>
</html>
