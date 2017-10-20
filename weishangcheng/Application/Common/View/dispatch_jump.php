<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>跳转提示</title>
<link rel="stylesheet" href="/bootstrap2.3.2/css/bootstrap.min.css">
<style type="text/css">
body{ background: #fff; font-family: '微软雅黑'; color: #3a87ad; font-size: 16px; }
</style>
</head>
<body>
    <table style="position: absolute;left: 0;right: 0;bottom: 20px;top: -10%;height: 100%;width: 100%;">
        <tr>
            <td style="text-align:center;">
                <div style="width:550px; margin:0 auto;">
                    <img src="__CDN__/img/logo.jpg" style="width: 200px;margin-bottom: 20px;" />
                    <div>
                      <h4>跳转提示!</h4>
                      <p>
                          <?php if(isset($message)) {?>
                          <span style="margin-right:10px;">:)</span><?php echo($message); ?>
                          <?php }else{?>
                          <span style="margin-right:10px;">:(</span><?php echo($error); ?>
                          <?php }?>
                      </p>
                     <p>页面自动<a id="href" href="<?php echo($jumpUrl); ?>">跳转</a> 等待时间： <b id="wait">3</b></p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
<script type="text/javascript">
(function(){
var wait = document.getElementById('wait'),href = document.getElementById('href').href;
var interval = setInterval(function(){
	var time = --wait.innerHTML;
	if(time <= 0) {
		location.href = href;
		clearInterval(interval);
	};
}, 1000);
})();
</script>
</body>
</html>
