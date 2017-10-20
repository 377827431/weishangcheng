<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>404</title>
<link rel="stylesheet" href="/bootstrap2.3.2/css/bootstrap.min.css">
</head>
<body>
    <table style="position: absolute;left: 0;right: 0;bottom: 0;top: 0;height: 100%;width: 100%;text-align: center;">
        <tr>
            <td>
            	<div style="width: 600px;text-align: center;margin: 20px auto;border-radius: 6px;border: 2px solid #ddd;padding: 15px;">
	            	<table style="width: 100%;">
	            		<tbody>
	            			<tr>
	            			<td style="font-size:10em;">404</td>
	            			<td style="color:#999;">●</td>
	            			<td>
	            				<img src="__CDN__/img/logo.jpg" style="margin-bottom:20px;width:108px">
								<p><a title="官方网站" href="http://www.xingyebao.com" target="_blank" style="color: initial;">兴业宝科技</a> 24小时服务热线：400-0450-611</p>
								<p style="text-align:center;"><input type="button" class="btn" value="返回" onclick="window.history.back()"></p>
	            			</td>
	            		</tr>
	            		</tbody>
	            	</table>
            	</div>
            	<div class="error" style="width: 634px;text-align: left; margin: 0 auto;font-size: 12px;">
				    <div class="alert">
	                  <strong>Warning!</strong> <?php echo strip_tags($e['message']);?>.
	                </div>
					<div class="content">
	                <?php if(isset($e['file'])) {?>
		               <div class="info">
							<div class="title">
								<h3>错误位置</h3>
							</div>
							<div class="text">
								<p>FILE: <?php echo $e['file'] ;?> &#12288;LINE: <?php echo $e['line'];?></p>
							</div>
						</div>
	                    <?php }?>
	                    <?php if(isset($e['trace'])) {?>
	                    <div class="info">
							<div class="title">
								<h3>TRACE</h3>
							</div>
							<div class="text">
								<p><?php echo nl2br($e['trace']);?></p>
							</div>
						</div>
	                <?php }?>
				</div>
            </td>
        </tr>
    </table>
</body>
</html>
