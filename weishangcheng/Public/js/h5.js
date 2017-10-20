// 常用数字精度计算
Number.prototype.bcFixed=String.prototype.bcFixed=String.prototype.toFixed=function(digits){if(!/\d+/.test(digits)){digits=2}var r=this.toString().split('.');if(r.length==1){return(r[0]*1).toFixed(digits)}else{return((r[0]+'.'+r[1].substr(0,digits))*1).toFixed(digits)}}
Number.prototype.bcadd=String.prototype.bcadd=function(p2,n){var v1=this.toString(),v2=p2.toString(),v=l1=l2=p=0;try{l1=v1.split('.')[1].length}catch(e){}try{l2=v2.split('.')[1].length}catch(e){}p=Math.pow(10,Math.max(l1,l2));v=(v1.bcmul(p)+v2.bcmul(p))/p;return!!n?v.bcFixed(n):v}
Number.prototype.bcsub=String.prototype.bcsub=function(p2,n){var v1=this.toString(),v2=p2.toString(),v=l1=l2=p=0;try{l1=v1.split('.')[1].length}catch(e){}try{l2=v2.split('.')[1].length}catch(e){}p=Math.pow(10,Math.max(l1,l2));v=(v1.bcmul(p)-v2.bcmul(p))/p;return!!n?v.bcFixed(n):v}
Number.prototype.bcmul=String.prototype.bcmul=function(p2,n){var v1=this.toString(),v2=p2.toString(),v=len=0;try{len+=v1.split('.')[1].length}catch(e){}try{len+=v2.split('.')[1].length}catch(e){}v=Number(v1.replace('.',''))*Number(v2.replace('.',''))/Math.pow(10,len);return!!n?v.bcFixed(n):v}
Number.prototype.bcdiv=String.prototype.bcdiv=function(p2,n){var v1=this.toString(),v2=p2.toString(),v=l1=l2=0;try{l1=v1.split('.')[1].length}catch(e){}try{l2=v2.split('.')[1].length}catch(e){}v=(Number(v1.replace('.',''))/Number(v2.replace('.',''))).bcmul(Math.pow(10,l2-l1));return!!n?v.bcFixed(n):v}
Number.prototype.toScore=String.prototype.toScore=function(){return this*100}
Number.prototype.toMoney=String.prototype.toMoney=function(){return this*0.01}
Number.prototype.split=function(){return this.toFixed(2).split('.')}

// 自动生成随机id
function newId(length){if(length==undefined){length=10}var chars="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";var str="";for(var i=0;i<length;i++){str+=chars.substr(Math.ceil(Math.random()*chars.length),1)}return str}
// 表单验证
define('h5/validate', ["jquery",'//cdn.bootcss.com/jquery-validate/1.15.0/jquery.validate.min.js'],function(){jQuery.validator.addMethod("mobile",function(value,element){var tel=/^1[3|4|5|7|8]\d{9}$/;return this.optional(element)||(tel.test(value))},"请输入有效的手机号码");jQuery.validator.addMethod("cardid",function(value,element){var tel=/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/;return this.optional(element)||(tel.test(value))},"请输入有效的身份证号");jQuery.validator.addMethod("regular",function(value,element){var regular=eval(element.getAttribute('data-rule-regular'));return this.optional(element)||(regular.test(value))},"输入有误");$.extend($.validator.messages,{required:"这是必填字段",remote:"请修正此字段",email:"请输入有效的电子邮件地址",url:"请输入有效的网址",date:"请输入有效的日期",dateISO:"请输入有效的日期 (YYYY-MM-DD)",number:"请输入有效的数字",digits:"只能输入数字",creditcard:"请输入有效的信用卡号码",equalTo:"你的输入不相同",extension:"请输入有效的后缀",maxlength:$.validator.format("最多可以输入 {0} 个字符"),minlength:$.validator.format("最少要输入 {0} 个字符"),rangelength:$.validator.format("请输入长度在 {0} 到 {1} 之间的字符串"),range:$.validator.format("请输入范围在 {0} 到 {1} 之间的数值"),max:$.validator.format("请输入不大于 {0} 的数值"),min:$.validator.format("请输入不小于 {0} 的数值")});var t={init:function($form,callback){$form.validate({ignore:".ignore",focusInvalid:false,onfocusout:false,onkeyup:false,errorPlacement:function(error,element){},invalidHandler:function(event,validator){toast.show(validator.errorList[0].message);validator.errorList[0].element.focus()},submitHandler:function(){var data={};var array=$form.serializeArray();for(var i=0;i<array.length;i++){if(typeof data[array[i].name]=='undefined'){data[array[i].name]=array[i].value}else{data[array[i].name]+=','+array[i].value}}if(typeof callback=='function'){var result=callback.apply($form,[data]);if(result===false){return false}} $form.ajaxSubmit();return false}})},onSubmit:function(){}};return t;});
//倒计时
function countDown(endTime,startTime){if(!startTime){startTime=Date.now()}var leftTime=endTime-startTime;var leftsecond=parseInt(leftTime/1000);var day=Math.floor(leftsecond/(60*60*24));var hour=Math.floor((leftsecond-day*24*60*60)/3600);var minute=Math.floor((leftsecond-day*24*60*60-hour*3600)/60);var second=Math.floor(leftsecond-day*24*60*60-hour*3600-minute*60);return{day:day,hour:hour,minute:minute,second:second}}

// 常用函数封装
var win = {
	start: function(){
		var $container = $('body>.container');
		var minHeight = document.documentElement.clientHeight - parseFloat($container.css('padding-bottom'))- parseFloat($container.css('padding-top')) - parseFloat($container.children('.js-footer').outerHeight());
		$container.children('.content').css('min-height', minHeight + 'px');

		this.initToast();
		win.globalAjax();
	},
	init: function(selector){
		selector = $(selector);
		this.validate(selector.find("form:not('.ignore')"));
	},
	redirect: function(url, time){
		if(url == undefined || url == ''){
			return;
		}

		if(time == undefined){
			window.location.href = url;
		}else{
			setTimeout(function () {
				window.location.href = url;
			}, time);
		}
	},
	back: function(steep){
		if(steep === true){ // 后退刷新
			location.href = document.referrer;
		}else{
			window.history.back();
		}
	},
	globalAjax: function(){
		$.ajaxSetup({
			global: true,
			waitting: '',
			beforeSend: function(XHR){
				var type = this.type.toUpperCase();
				if(this.waitting || (type == 'POST' && this.waitting !== false)){
					var msg = '加载中';
					if(typeof this.waitting == 'string' && this.waitting.length > 0){
						msg = this.waitting;
					}else if(type == 'POST'){
						if(this.post_msg == undefined){
							msg = '处理中';
						}
						if(this.post_msg == "upload_img"){
							msg = "正在上传图片,请耐心等待...";
						}
					}
					toast.loading(msg);
				}

				if(this.custom){return}
				this.custom = {};
				this.custom.success = typeof this.success == 'function' ? this.success : function(){};
				this.custom.error = typeof this.error == 'function' ? this.error : function(){};
				this.custom.complete = typeof this.complete == 'function' ? this.complete : function(){};

				var retry = this;
				this.success = function(data, textStatus, jqXHR){
					var response_type = jqXHR.getResponseHeader("Content-Type");
					if(this.dataType != 'json'){
						if(response_type != 'application/json; charset=utf-8'){
							return this.custom.success(data, textStatus, jqXHR);
						}else if(typeof data != 'object'){// 我请求的不是json数据，而返回的却是json数据(可能服务端出错)
							data = $.parseJSON(data);
						}
					}

					if(data.status == -1){ // 登录
						if(win.isWeiXin){
							return window.location.replace(data.url);
						}else if(data.type == 'app' || !win.isWeiXin){
							require(['buyer/login/modal'], function(modal){
								modal.init({appid: data.appid, redirect: data.redirect, mobile: data.mobile});
								modal.onLogin = function(){
									$.ajax(retry);
								}
							});
						}
						return win.redirect(data.url);
					}

					if(typeof data.info == 'string' && data.info != ''){
						if(data.status == 0){
							var reg=/^tel:([0-9]{11})$/;
							if(reg.test(data.info)){
								var tel = data.info.match(reg)[1];
								if($(".offshelf_dialog").length != 0){
									$(".offshelf_dialog a.phone_num").text(tel);
									$(".offshelf_dialog").show();
								}
								return false;
							}
							toast.warn(data.info);
						}else{
							toast.show(data.info);
						}
					}

					if(!isNaN(data.status)){
						if(data.url){
							//用于联系客服
							var reg=/^tel:([0-9]{11})$/;
							if(reg.test(data.url)){
								var tel = data.url.match(reg)[1];
								if($(".offshelf_dialog").length != 0){
									$(".offshelf_dialog a.phone_num").text(tel);
									$(".offshelf_dialog").show();
								}
								return false;
							}
							var reg2 = /^https:\/\/seller\.xingyebao\.com\/upload\/image\//;
							if(reg2.test(data.url)){
							// if(true){
								console.log("显示二维码弹窗");
								$('.share_logo').attr('src', data.url);
								$(".share_code_btn_text").text("扫码联系商家");
                    			$("#share_code_body").show();
								return false;
							}
							return win.redirect(data.url, 2);
						}

						this.custom.status = data.status;
						if(data.status == 1){
							this.custom.success(typeof data.info == 'object' ? data.info : {}, textStatus, jqXHR);
						}else if(data.status == 0){
							if(typeof data.info == 'object'){
								if(typeof data.info.msg == 'string'){
									toast.show(data.info.msg);
								}else{
									toast.show('操作失败！');
								}
								this.custom.error(data.info, textStatus, jqXHR);
							}else{
								this.custom.error({}, textStatus, jqXHR);
							}
						}
					}else{
						this.custom.success(data, textStatus, jqXHR);
					}
				};
				this.error = function(data, textStatus, jqXHR){
					toast.loading(false);
					toast.show('网络连接失败，请稍后再试！');
					if(typeof this.custom.error == 'function'){
						this.custom.error({}, textStatus, jqXHR);
					}
				};
				this.complete = function(XHR, TS){
					toast.loading(false);
					if(typeof this.custom.complete == 'function'){
						this.custom.complete(XHR, TS);
					}
				};
			}
		});
	},
	validate: function(object){// jquery.validate验证
		var $forms = $(object);
		if($forms.length == 0){
			return;
		}
		require(['h5/validate'], function(t){
			$forms.each(function (i) {
				t.init($forms.eq(i));
			});
		});
	},
	initToast: function(){
		var toast = {
			index: 0,
			show: function(msg, warning){
				if(win.isApp){return uexWindow.toast(0, 5, msg, 2500), false}

				toast.index++;
				var id = "toast-"+toast.index;
				$('body').append('<div id="'+id+'" class="toast-view'+(warning ? ' warning' : '')+'"><div class="toast-bg"></div><div class="ext-tips">'+msg+'</div></div></div>');
				setTimeout(function(){$('#'+id).remove()}, 3500);
			},
			warn: function(msg){
				toast.show(msg, true);
			},
			loading: function(msg){ // 显示加载等待
				$('#loading_modal').remove();
				if(msg === false){
					return;
				}
				$('body').append('<div id="loading_modal"class="loading-wrapper"><div class="mask"></div><div class="inner"></div><div class="text">'+msg+'</div><div class="loading-dot"><span></span><span></span><span></span></div></div>')
			}
		}

		window.toast = toast;
	},
	close: function(){
		if (win.isWeiXin) {
	        WeixinJSBridge.invoke('closeWindow', {}, function (res) {});
	    } else if (win.isApp) {
	    	uexWidgetOne.exit(0);
	    } else {
	        window.close();
	    }
	},
	initShopNavMenu: function(){
		var $submenus = $('#shop-nav .js-submenu');
		if($submenus.length < 1){
			return;
		}
		$submenus.on('click', '.mainmenu',function(){
			var $this = $(this).parent();
			if($this.hasClass('focued')){
				$this.removeClass('focued');
				return false;
			}

			$(document).one("click", function (){
				$submenus.removeClass('focued');
				return false;
			});

			$submenus.removeClass('focued');
			$this.addClass('focued');
			var  $submenu = $this.children('.submenu'), $arrow = $submenu.find('.arrow'), pwidth = $this.outerWidth(), cwidth = 0, arrowLeft = 0;
			cwidth = $submenu.outerWidth();
			arrowLeft = cwidth / 2 - 6;

			var left = $this.offset().left.bcadd(pwidth / 2) - (cwidth / 2);
			var cha = document.body.clientWidth - left - cwidth - 8;
			if(cha < 0){
				left += cha;
				arrowLeft -= cha;
			}
			$submenu.css('left', left+'px');
			$arrow.css('left', arrowLeft+'px');
			return false;
		});

		$('#shop-nav').click(function(event){
			event.stopPropagation();
		});
	},
	getFormValue: function($form){
		var data = {}, name = '', value = '', serializeArray = $form.serializeArray();
		for(var i=0; i<serializeArray.length; i++){
			name = serializeArray[i].name;
			if(name.substring(0, 2) == 'js'){
				continue;
			}
			value = serializeArray[i].value;

			value = $.trim(value);
			if(value != '' && !isNaN(value)){
				value = parseFloat(value);
			}

			if(name.indexOf('[') > -1){
				var list = name.split('['), name= '';

				var objlist = [], index = 0;
				for(var j=0; j<list.length; j++){
					name = list[j].replace(']', '');
					index = objlist.length - 1;
					var dtype = (j < list.length-1 && list[j+1] == ']') ? [] : {};

					if(j == 0){
						if(!data[name]){
							data[name] = dtype;
						}
						objlist.push(data[name]);
					}else if(name == ''){
						objlist[index].push(value);
					}else{
						if(j == list.length-1){
							objlist[index][name] = value;
						}else{
							if(!objlist[index][name]){
								objlist[index][name] = dtype;
							}
							objlist.push(objlist[index][name]);
						}
					}
				}
			}else{
				data[name] = value;
			}
		}

		return data;
	},
	trident: navigator.userAgent.indexOf('Trident') > -1, //IE内核
	presto: navigator.userAgent.indexOf('Presto') > -1, //opera内核
	webKit: navigator.userAgent.indexOf('AppleWebKit') > -1, //苹果、谷歌内核
	gecko: navigator.userAgent.indexOf('Gecko') > -1 && navigator.userAgent.indexOf('KHTML') == -1, //火狐内核
	mobile: !!navigator.userAgent.match(/AppleWebKit.*Mobile.*/) || !!navigator.userAgent.match(/AppleWebKit/), //是否为移动终端
	ios: !!navigator.userAgent.match(/\(i[^;]+;( U;)? CPnavigator.userAgent.+Mac OS X/), //ios终端
	android: navigator.userAgent.indexOf('Android') > -1 || navigator.userAgent.indexOf('Linux') > -1, //android终端或者uc浏览器
	iPhone: navigator.userAgent.indexOf('iPhone') > -1 || navigator.userAgent.indexOf('Mac') > -1, //是否为iPhone或者QQHD浏览器
	iPad: navigator.userAgent.indexOf('iPad') > -1, //是否iPad
	webApp: navigator.userAgent.indexOf('Safari') == -1, //是否web应该程序，没有头部与底部
	isIOS: function(){return win.ios || win.iPhone || win.iPad},
	isWeiXin: navigator.userAgent.toLowerCase().match(/MicroMessenger/i) == "micromessenger",
	isApp: !!navigator.userAgent.match(/Appcan/)
}

require(['jquery'], function(){
	// 联系客服
	setTimeout(function(){
		require(['h5/kefu'], function(t){
			$('body').on('click', '.js-lxkf', function(){
				t.show($(this).data());
				return false;
			})
		});

		$('body').on('click', '.switch', function(){
			var $this = $(this);
			if($this.hasClass('disabled') || $this.attr('disabled') != undefined){
				return false
			}
			$this.toggleClass('switch-on');
			this.checked = $this.hasClass('switch-on');
			$this.trigger('change');
			return false;
		});
	}, 1000);

	win.init('body');
	win.initShopNavMenu();
});
define("util/draw",["jquery"],function(e){var t,n;return t=function(e){function t(e){return Math.PI/180*e}function n(e,t){var n=[0,0],r=["M"+n.join(","),"L"+[n[0],n[1]+e].join(","),"L"+[n[0]+e/2,n[1]+e/2].join(","),"L"+n.join(",")],i=n[1]+e/2;t[0].setAttribute('refX',n[0]),t[0].setAttribute('refY',i),t[0].setAttribute('markerWidth',2*i),t[0].setAttribute('markerHeight',2*i),t.find("path").attr({d:r.join(" ")})}function r(r){r=e.extend({radius:0,margin:0,sAngle:0,eAngle:0,arrowSize:2.5,arrowObj:"#x-pushfresh-arrow",pathObj:".x-pullfresh-path"},r);var i=r.radius+r.margin+r.radius*Math.sin(t(r.eAngle)),s=r.radius+r.margin-r.radius*Math.cos(t(r.eAngle)),o=r.radius+r.margin+r.radius*Math.sin(t(r.sAngle)),u=r.radius+r.margin-r.radius*Math.cos(t(r.sAngle)),a=[["M"+o,u].join(",")];a.push([["A"+r.radius,r.radius].join(","),0,[r.eAngle-r.sAngle>180?1:0,1].join(","),[i,s].join(",")].join(" ")),e(r.pathObj).attr("d",a.join(" ")),n(r.arrowSize,e(r.arrowObj))}return{drawArc:r}}(e),n=function(e){function t(e,t,n,r,i,s,o,u,a){typeof t=="string"&&(t=parseInt(t,10)),typeof n=="string"&&(n=parseInt(n,10)),typeof r=="string"&&(r=parseInt(r,10)),typeof i=="string"&&(i=parseInt(i,10)),s=s!==undefined?s:console.log,o=o!==undefined?o:1,u=u!==undefined?u:Math.PI/8,a=a!==undefined?a:10;var f=typeof s!="function"?console.log:s,l,c,h,p,d,v,m,g;l=Math.atan2(i-n,r-t),c=Math.abs(a/Math.cos(u)),o&1&&(h=l+Math.PI+u,d=r+Math.cos(h)*c,v=i+Math.sin(h)*c,p=l+Math.PI-u,m=r+Math.cos(p)*c,g=i+Math.sin(p)*c,f(e,d,v,r,i,m,g,s)),o&2&&(h=l+u,d=t+Math.cos(h)*c,v=n+Math.sin(h)*c,p=l-u,m=t+Math.cos(p)*c,g=n+Math.sin(p)*c,f(e,d,v,t,n,m,g,s))}function n(e,n,r,i,s,o,u,a,f,l,c){a=a!==undefined?a:console.log,f=f!==undefined?f:1,l=l!==undefined?l:Math.PI/8,c=c!==undefined?c:10;var h,p,d,v,m,g=typeof a!="function"?console.log:a;g(e,n,r,i,s,o,u),f&1&&(h=Math.cos(s)*i+n,p=Math.sin(s)*i+r,d=Math.atan2(n-h,p-r),u?(v=h+10*Math.cos(d),m=p+10*Math.sin(d)):(v=h-10*Math.cos(d),m=p-10*Math.sin(d)),t(e,h,p,v,m,a,2,l,c)),f&2&&(h=Math.cos(o)*i+n,p=Math.sin(o)*i+r,d=Math.atan2(n-h,p-r),u?(v=h-10*Math.cos(d),m=p-10*Math.sin(d)):(v=h+10*Math.cos(d),m=p+10*Math.sin(d)),t(e,h,p,v,m,a,2,l,c))}return{drawArrow:t,drawArcedArrow:n}}(e),{SVGUtil:t,CanvasUtil:n}})
,define("h5/pullrefresh",["jquery", "util/draw", "md5"],function(e, t, md5) {
	var animations={SHORTCUTS:{a:'animate',an:'attributeName',at:'animateTransform',c:'circle',da:'stroke-dasharray',os:'stroke-dashoffset',f:'fill',lc:'stroke-linecap',rc:'repeatCount',sw:'stroke-width',t:'transform',v:'values'},setSvgAttribute:function(ele,k,v){ele.setAttribute(this.SHORTCUTS[k]||k,v)},easeInOutCubic:function(t,c){t/=c/2;if(t<1)return 1/2*t*t*t;t-=2;return 1/2*(t*t*t+2)},android:function(ele){var t=this;t.stoped=false;var rIndex=0;var rotateCircle=0;var startTime;var svgEle=ele.querySelector('g');var circleEle=ele.querySelector('circle');var bgcolor=['#FF4136','#0074D9','#FF851B','#B10DC9','#FFDC00','#2ECC40','#FF851B'];function run(){if(t.stoped)return;var v=t.easeInOutCubic(Date.now()-startTime,650);var scaleX=1;var translateX=0;var dasharray=(188-(58*v));var dashoffset=(182-(182*v));if(rIndex%2){scaleX=-1;translateX=-64;dasharray=(128-(-58*v));dashoffset=(182*v)}var rotateLine=[0,-101,-90,-11,-180,79,-270,-191][rIndex];t.setSvgAttribute(circleEle,'da',Math.max(Math.min(dasharray,188),128));t.setSvgAttribute(circleEle,'os',Math.max(Math.min(dashoffset,182),0));t.setSvgAttribute(circleEle,'t','scale('+scaleX+',1) translate('+translateX+',0) rotate('+rotateLine+',32,32)');rotateCircle+=4.1;if(rotateCircle>359)rotateCircle=0;t.setSvgAttribute(svgEle,'t','rotate('+rotateCircle+',32,32)');ele.style.stroke=bgcolor[rIndex];if(v>=1){rIndex++;if(rIndex>7)rIndex=0;startTime=Date.now()}window.requestAnimationFrame(run)}animations.run=function(){t.stoped=false;startTime=Date.now();run()}},run:function(){},stop:function(){this.stoped=true}};
	var pullrefresh = {
		options: {refresh:1,wrapper:".x-pullfresh-wrapper",canvas:".x-pullfresh-canvas",svg:".x-pullfresh-svg",container:'.x-pullfresh-container',circle:{originX:16,originY:16,radius:12},arrow:{angle:90,lineLength:3},moveOffset:50,moveRate:2.5},
		$container: null,
		init: function(opt) {var t=this;t.options=e.extend(!0,t.options,opt||{});t.addPullTip(),t.wrapper=e(t.options.wrapper),t.svg=e(t.options.svg),t.beginPos=0,t.currPos=0,t.endEvents="webkitTransitionEnd transitionend",t.addEventListener()},
		drawRotate: function(e,t){var n=this,r=0,i=e*360;n.svg.length?n.drawRotateSVG(r,i,t):n.canvas.length&&n.drawRotateCanvas(r,i,t)},
		drawRotateSVG: function(e,n,r){var i=this,s=i.options;t.SVGUtil.drawArc({margin:8,radius:s.circle.radius,sAngle:e+35,eAngle:n+35,arrowSize:s.arrow.lineLength*r*2.5,arrowObj:i.svg.find("#x-pullfresh-arrow"),pathObj:i.svg.find(".x-pullfresh-path")})},
		onTouchStart: function(e){var t=this;t.beginPos=e.touches[0].pageY,t.isFull=!1,t.currPos=0},
		onTouchMove: function(e){var t=this,n=e.touches[0].pageY-t.beginPos,r;t.isFull=!1;if(window.scrollY===0){if(n>0){e.preventDefault();e.stopPropagation()}if(n>30){t.currPos=n*.2+n*((t.options.moveOffset-t.currPos)/t.options.moveOffset)/10,r=Math.floor(t.currPos*1e3/t.options.moveOffset)/1e3;if(r>1||r<0)r=1,t.isFull=!0;r>=.9&&(t.isFull=!0),t.drawRotate(r>.9?.9:r,r),n=t.options.moveOffset*t.options.moveRate*r,t.wrapper.css({"-webkit-transition":"","-webkit-transform":"translate3d(0,"+n+"px,0)",transform:"translate3d(0,"+n+"px,0)",opacity:1})}}},
		onTouchEnd: function(e){this.isFull?this.onLoad(1,!0):this.hideLoading()},
		onScroll: function(){},
		showLoading: function(isRefresh){var t=this;t.isLoading=true;if(isRefresh){t.wrapper.attr('style','transform: translate3d(0px, 125px, 0px);opacity: 1').html('<div class="x-pullfresh-loading"><svg id="pullrefresh_loading"viewBox="0 0 64 64"style="stroke:#4b8bf4;fill:none;width:40px;height:40px;"><g><circle stroke-width="4"r="20"cx="32"cy="32"></circle></g></svg></div>');animations.android(t.wrapper.find('svg')[0]);animations.run();}else{t.upTip.addClass('pullfresh-loading')}},
		hideLoading: function(){var t=this,$loading=t.wrapper.find('.x-pullfresh-loading');t.isLoading=false;t.upTip.removeClass('pullfresh-loading');$loading.css('-webkit-transform','scale(0,0)');setTimeout(function(){if(!t.isLoading){animations.stop();t.wrapper.find('.x-pullfresh-loading').css('-webkit-transform','');t.wrapper.attr('style','').html('<div class="x-pullfresh-loading"><svg class="x-pullfresh-svg"><marker id="x-pullfresh-arrow" orient="auto" markerUnits="userSpaceOnUse"><path/></marker><path class="x-pullfresh-path" marker-end="url(#x-pullfresh-arrow)"fill="none"/></svg></div>');t.svg=t.wrapper.find('.x-pullfresh-svg')}},1000);},
		addPullTip: function(){e('body').append('<div class="x-pullfresh-wrapper"></div>');e('.x-pullfresh-more').html('<div class="pullfresh-up"><div class="loader"><span></span><span></span><span></span><span></span></div><div class="pullfresh-label">没有更多数据了</div></div>');this.upTip=e('.x-pullfresh-more')},
		handleEvent: function(e){switch(e.type){case'scroll':this.onScroll(e);break;case'touchstart':this.onTouchStart(e);break;case'touchmove':this.onTouchMove(e);break;case'touchend':this.onTouchEnd(e);break}},
		addEventListener: function(){if(this.options.refresh){var ele = document.body.querySelector('.container>.content');ele.addEventListener("touchstart",this,!1),ele.addEventListener("touchmove",this,!1),ele.addEventListener("touchend",this,!1)}window.addEventListener('scroll',this,!1)},
		onLoad: function(page){var t=this;t.isLoading=true;t.showLoading(page==1);setTimeout(function(){t.isLoading=false;t.hideLoading()},2000)},
		timer:0,
		triggerTop: 0,
		getTriggerTop: function(){return this.$container.offset().top + this.$container.outerHeight() - document.documentElement.clientHeight - 200},
		setMore: function(hasMore,page){var t=this;t.hideLoading();if(hasMore){t.upTip.removeClass('no-more');window.clearInterval(t.timer);t.triggerTop=t.getTriggerTop();t.timer=setInterval(function(){t.triggerTop=t.getTriggerTop()},3000)}else{t.upTip.addClass('no-more');window.clearInterval(t.timer)}},
		isLoading: false,
		lastCache: {},
		id: md5(window.location.host+window.location.pathname),
		info: null,
		localInfo: function(guid, data){
			var t = this;

			if(!t.info){
				t.info = t.getCache(t.id);
				if(!t.info){
					t.info = {key: '', items: {}};
				}
			}

			// 读取数据
			if(!guid){
				return t.info;
			}

			// 设置数据
			var cache = t.info.items[guid];
			if(data == undefined){
				return !cache ? {t: 0, p: 1} : cache;
			}else if(data == null){
				delete t.info.items[guid];
				t.setCache(t.id, t.info);
			}else{
				t.info.items[guid] = data;
				t.setCache(t.id, t.info);
			}
		},
		doRefresh: function(ajax){
			var t = this;
			if(t.$container == null){t.localInfo();t.init({refresh: typeof ajax.refresh == 'undefined' ? 1 : ajax.refresh})}
			if(!ajax || ajax === true) return this.onLoad(1, ajax === true);
			t.$container = $(ajax.container ? ajax.container : 'body');

			// 保存上次信息
			if(t.lastCache.guid){
				t.info.key = ajax.cacheKey;
				t.localInfo(t.lastCache.guid, {t: t.lastCache.scrollTop, p: t.lastCache.page});
			}
			t.lastCache = {scrollTop: document.body.scrollTop};

			var request = null, guid = null;
			if(ajax.cacheKey){
				guid = t.getGuid(ajax.url, ajax.data);
				t.info.key = ajax.cacheKey;
				t.localInfo(t.lastCache.guid, {t: t.lastCache.scrollTop, p: t.lastCache.page});
			}

			document.body.scrollTop = ajax.scrollTop ? ajax.scrollTop : 0;
			t.setMore(typeof ajax.hasMore == 'undefined' ? true : ajax.hasMore);

			t.onLoad = function(page, isRefresh){
				if(t.isLoading && request){
					request.abort();
				}

				t.showLoading(page == 1);

				if(ajax.data.size){
					ajax.data.offset = (page - 1) * ajax.data.size;
				}else{
					ajax.data.page = page;
				}

				if(ajax.cacheKey){
					if(page == 1){
						guid = t.getGuid(ajax.url, ajax.data);
					}

					if(isRefresh === true){
						t.clearCache(guid);
					}else{
						var a = t.getCache(guid+'_'+page);
						if(a){
							ajax.page = page;
							ajax.hasMore = ajax.success.apply(ajax.container, [a, page, ajax.data.size]);
							t.setMore(ajax.hasMore, page);

							t.lastCache.guid = guid;
							t.lastCache.key = ajax.cacheKey;
							t.lastCache.page = page;
							t.info.key = ajax.cacheKey;
							t.localInfo(guid, {t: document.body.scrollTop, p: page});
							return;
						}
					}
				}

				request = $.ajax({
					url: ajax.url,
					data: ajax.data,
					dataType: ajax.dataType,
				    ifModified: true,
				    timeout: 8000,
					success: function(a){
						ajax.page = page;
						ajax.hasMore = ajax.success.apply(ajax.container, [a, page, ajax.data.size]);
						t.setMore(ajax.hasMore, page);

						if(ajax.cacheKey){
							t.setCache(guid + '_' + page, a);
							t.lastCache.guid = guid;
							t.lastCache.key = ajax.cacheKey;
							t.lastCache.page = page;
							t.info.key = ajax.cacheKey;
							t.localInfo(guid, {t: document.body.scrollTop, p: page});
						}
						// //控制商品管理页初始化批量管理功能
						// if(ajax.url == "/seller/commodity"){
						// 	$(".batch_management_box").hide();
						// 	$(".batch_management").removeClass("batch_on");
						// 	$("#Submit_gl").show();
						// }
					},
					error: function(a, b, c){
						if(typeof ajax.error == 'function'){
							ajax.error(a, b, c);
						}
					},
					complete: function(a, b, c){
						t.hideLoading();
						if(typeof ajax.error == 'function'){
							ajax.error(a, b, c);
						}
					}
				});
			}

			var data = {p: ajax.page ? ajax.page : 1, t: ajax.scrollTop};
			if(ajax.cacheKey){
				data = t.localInfo(guid);
			}
			for(var i=0; i<data.p; i++){
				t.onLoad(i+1);
				document.body.scrollTop = data.t;
			}

			var setHistory = function(){
				t.info.key = ajax.cacheKey;
				t.localInfo(t.lastCache.guid, {t: document.body.scrollTop, p: ajax.page});
			}
			$(window).unbind('beforeunload', setHistory);

			if(ajax.cacheKey){
				$(window).bind('beforeunload', setHistory);
			}

			t.onScroll = function(e){
				t.lastCache.scrollTop = ajax.scrollTop = document.body.scrollTop;
				if(ajax.hasMore && !t.isLoading && document.body.scrollTop > t.triggerTop){
					t.onLoad(ajax.page+1)
				}
			}

			return ajax;
		},
		parseURL: function(url){var a=document.createElement('a');a.href=url;return{href:url,protocol:a.protocol.replace(':',''),host:a.hostname,port:a.port,query:a.search,params:(function(){var ret={},seg=a.search.replace(/^\?/,'').split('&'),len=seg.length,i=0,s;for(;i<len;i++){if(!seg[i]){continue}s=seg[i].split('=');ret[s[0]]=s[1]}return ret})(),file:(a.pathname.match(/\/([^\/?#]+)$/i)||[,''])[1],hash:a.hash.replace('#',''),path:a.pathname.replace(/^([^\/])/,'/$1'),relative:(a.href.match(/tps?:\/\/[^\/]+(.+)/)||[,''])[1]}},
		getGuid: function(url, data){
			var urlData = this.parseURL(url);
			if(data){
				for(var field in data){
					urlData.params[field] = data[field];
				}
			}

			var params = '';
			for(var field in urlData.params){
				if(field == 'page' || field == 'offset' || field == 'size'){
					continue;
				}
				params += (params == '' ? '?' : '&') + field + '=' + urlData.params[field];
			}

			url = urlData.protocol + '://'+urlData.host+urlData.path + params;
			return md5(url);
		},
		setCache: function(key, value){
			sessionStorage.setItem(key, JSON.stringify(value));
		},
		getCache: function(key){
			var json = sessionStorage.getItem(key);
			if(json){
				return eval("("+json+")");
			}
			return;
		},
		clearCache: function(guid){
			var t = this, data = t.localInfo(guid);
			if(data){
				t.localInfo(guid, null);
				for(var p=1; p<=data.p; p++){
					sessionStorage.removeItem(guid+'_'+p)
				}
			}
		}
	};

	pullrefresh.localInfo();
	return pullrefresh;
})
,define("h5/template/empty", function(){
	return {
		getHtml: function(option){
			var data = $.extend(0, {
				title: '居然没有数据T.T',
				notice: '要不刷新试试',
				link_url: __H5__+'/mall',
				link_text: '去逛逛'
			}, option);
			return '<div class="empty-list "style="padding-top:60px;"><div class="empty-list-header"><h4>'+data.title+'</h4><span>'+data.notice+'</span></div><div class="empty-list-content"><a href="'+data.link_url+'"class="js-go-home home-page tag tag-big tag-orange">'+data.link_text+'</a></div></div>'
		}
	}
})

,define("h5/jsweixin", ["https://res.wx.qq.com/open/js/jweixin-1.0.0.js"], function(wx){
	return {
		init: function(config, ready){
			wx.config({debug:false,appId:config.appId,timestamp:config.timestamp,nonceStr:config.nonceStr,signature:config.signature,jsApiList:["onMenuShareTimeline","onMenuShareAppMessage","onMenuShareQQ","onMenuShareWeibo","onMenuShareQZone","chooseImage","uploadImage"]});
			wx.ready(ready);
		},
		share: function(shareData, shareResult){
			// 分享到朋友圈
			wx.onMenuShareTimeline({title:shareData.title,link:shareData.link,imgUrl:shareData.imgUrl,success:function(){shareData.to='timeline';shareResult.call(shareData)}});
			// 分享给朋友
			wx.onMenuShareAppMessage({title:shareData.title,desc:shareData.desc,link:shareData.link,imgUrl:shareData.imgUrl,type:shareData.type,success:function(){shareData.to='appmessage';shareResult.call(shareData)}});
			// 分享到QQ
			wx.onMenuShareQQ({title:shareData.title,desc:shareData.desc,link:shareData.link,imgUrl:shareData.imgUrl,success:function(){shareData.to='qq';shareResult.call(shareData)}});
			// 分享到腾讯微博
			wx.onMenuShareWeibo({title:shareData.title,desc:shareData.desc,link:shareData.link,imgUrl:shareData.imgUrl,success:function(){shareData.to='weibo';shareResult.call(shareData)}});
			//分享到QQ空间
			wx.onMenuShareQZone({title:shareData.title,desc:shareData.desc,link:shareData.link,imgUrl:shareData.imgUrl,success:function(){shareData.to='qzone';shareResult.call(shareData)}});
		},
		chooseImage: function(uploadSuccess, count){
			count = /^\d+$/.test(count) ? count : 1;
			wx.chooseImage({count:count,sizeType:['compressed'],sourceType:['album','camera'],success:function(res){var localIds=res.localIds;for(var i=0;i<localIds.length;i++){var localid=localIds[i];wx.uploadImage({localId:localid,isShowProgressTips:1,success:function(res){uploadSuccess(localid,res)},fail:function(res){alert('上传图片失败，请重新选择图片')}})}}});
		}
	}
})
//店铺管理修改手机号
,define('h5/view/create/mobile', ["jquery", "h5/validate", 'address'], function($, validate){
    var v = {
        _getTpl: function(data){
            var id = newId();
            var html = '';
            html += '<div id="modal_'+id+'" class="modal-backdrop"></div>';
            html += '<div id="'+id+'" class="modal">';
            html += '	<form class="address-ui address-fm" method="post">';
            html += '    	<h4 class="address-fm-title">个人资料</h4>';
            html += '	    <div class="js-address-cancel publish-cancel js-cancel">';
            html += '	        <div class="cancel-img"></div>';
            html += '	    </div>';
            html += '    	<div class="block form" style="margin:0;">';
            html += '    		<input type="hidden" name="id" value="'+data.id+'">';
            html += '	        <div class="block-item">';
            html += '	            <label>联系电话</label>';
            html += '	            <input type="tel" name="mobile" value="'+data.mobile+'" placeholder="手机号码" required="required" data-rule-mobile="mobile" data-msg-required="请输入联系电话" data-msg-mobile="请输入正确的手机号" maxlength="11">';
            html += '	        </div>';
            html += '	        <div class="block-item js-code_view'+(data.mobile != '' ? ' hide' : '')+'">';
            html += '	            <label>验证码</label>';
            html += '	            <div class="area-layout">';
            html += '	            	<input type="number" name="checknum" required="required" class="'+(data.mobile.length == 11 ? 'ignore' : '')+'" placeholder="验证码" data-msg-required="请输入验证码" maxlength="6">';
            html += '	            	<button type="button" class="js-get_code tag tag-big tag-orange" style="border:none;position: absolute;right: 0;top: 0;bottom: 0;font-size: 12px;padding: 0 20px;background-color:#fff;color:#da8f3e;">获取验证码</button>';
            html += '	            </div>';
            html += '	        </div>';
            html += '    	</div>';
            html += '	    <div>';
            html += '	        <div class="action-container">';
            html += '	            <button type="submit" class="js-address-save btn btn-block btn-red">保存</button>';
            html += '	        </div>';
            html += '	    </div>';
            html += '	</form>';
            html += '</div>';

            return html;
        },
        $html: null,
        close: function(){
            this.$html.remove();
            $('html,body').css({'height': '', 'overflow': ''});
        },
        _render: function($html, data){
            var t = this,
                $modal = $html.eq(1);
            this.$html = $html;

            // 点击关闭
            $html.eq(0).on('click', function(){return t.close(),false});
            $modal.find('.js-cancel').on('click', function(){return t.close(),false});

            // 验证码处理
            var $mobile = $modal.find('input[name="mobile"]')
                ,$codeView = $modal.find('.js-code_view')
                ,$code = $codeView.find('input[name="checknum"]')
                ,tel = /^1[3|4|5|7|8]\d{9}$/;

            // 监听手机号变更
            $mobile.on('keyup', function(){
                var mobile = this.value;
                if(!tel.test(mobile)){
                    return false;
                }

                if(mobile == data.mobile){
                    $codeView.addClass('hide');
                    $code.addClass('ignore');
                }else{
                    $codeView.removeClass('hide');
                    $code.removeClass('ignore');
                }
            });

            $modal.find('.js-get_code').on('click', function(){
                var btn = this;
                var phone = $mobile.val();
                if(!tel.test(phone)){
                    toast.show('请输入正确的手机号码');
                    return false;
                }

                btn.disabled = true;
                $.ajax({
                    url: '/seller/shop/check',
                    data: {phone:phone},
                    type: 'post',
                    datatype: 'json',
                    success: function(){
                        t._daojishi(btn);
                    },
                    error: function(){
                        btn.disabled = false;
                    }
                });

                return false;
            });

            Address.bind('#province');

            // 监听表单提交
            validate.init($modal.find('form'), function(data){
                var result = t.onSave(data);
                t.close();
                return false;
            });
        },
        _daojishi: function(btn){
            var times = 60;
            var timer = setInterval(function(){
                btn.innerHTML = times + '秒后重新获取';
                times--;
                if(times == 0){
                    clearInterval(timer);
                    btn.innerHTML = '重新获取';
                    btn.disabled = false;
                }
            }, 1000);
        },
        show: function(data, onSave){
            var html = this._getTpl(data),
                $html = $(html);
            $('html,body').css({'height': document.documentElement.clientHeight + 'px', 'overflow': 'hidden'});
            $html.appendTo('body');
            this._render($html, data);
            if(typeof onSave == 'function'){
                this.onSave = onSave;
            }
        },
        onSave: function(data){}
    }
    return v;
})
,define('h5/kefu',["jquery"], function(){
	var t = {
		data: [],
		show: function(parameters){
			$.ajax({
				url: get_url('/kefu/qrcode'),
				dataType: 'json',
				data: parameters,
				waitting: true,
				success: function(data){
					t.render(data)
				}
			});
		},
		render: function(data){
			if(data.connected){
				win.close();
				return;
			}else if(data.rows.length == 0){
				toast.show('暂无相关客服');
				return;
			}

			var list = data.rows;
			require(['swiper'], function(){
				var modalId = newId(), html = '';
				html = '<div id="'+modalId+'" class="full-screen swiper-container swiper-container-horizontal"><div class="swiper-wrapper"style="line-height:'+document.documentElement.clientHeight + 'px">';
				for(var i=0; i<list.length; i++){
					html += '<div class="swiper-slide"><img src="'+list[i]['qrcode']+'"></div>';
				}
				html += '</div><div class="swiper-tip">长按二维码添加好友</div><div id="pagination-'+modalId+'" class="pagination"></div></div>';
				$('body').append(html);

				$('#'+modalId).on('click', function(){
					$(this).remove();
					return false;
				});

				var $tip = $('#'+modalId).find('.swiper-tip');
				var changed = function(index){
					var data = list[index];
					var html = '长按二维码添加好友';
					if(!!data.work_start){
						html += '<br>接待时间：'+data.work_start+' ~ '+data.work_end;
					}
					$tip.html(html);
				}
				changed(0);

				var mySwiper = new Swiper('#'+modalId,{
					loop : false,
					autoplayDisableOnInteraction : false,
					pagination : '#pagination-'+modalId,
					onSlideChangeEnd: function(swiper){
						changed(swiper.activeIndex);
					}
				});
			});
		}
	}
	return t;
})
,define('h5/pay', function(){
	var pay = {
		callpay: function(param, callback, type){
			if(!type){
				type = win.isApp ? 'open' : 'mp';
			}

			if(type == 'mp'){
				pay.wxMpPay(param, callback);
			}else if(type == 'open'){
				pay.wxOpenPay(param, callback);
			}else{
				alert('未知支付类型');
			}
		},
		wxMpPay: function(param, callback){	// 微信公众号支付
			function onBridgeReady() {
				WeixinJSBridge.invoke('getBrandWCPayRequest', {
					"appId": param.appId,
					"timeStamp": param.timeStamp,
					"nonceStr": param.nonceStr,
					"package": param.package,
					"signType": "MD5",
					"paySign": param.paySign
				},
				function(res) {
					if(res.err_msg == 'get_brand_wcpay_request:ok'){
						callback({errcode: 0, errmsg: res.err_msg});
					}else{
						callback({errcode: 1, errmsg: res.err_msg});
					}
				});
			}
			if (typeof WeixinJSBridge == "undefined") {
				if (document.addEventListener) {
					document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
				} else if (document.attachEvent) {
					document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
					document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
				}
			} else {
				onBridgeReady();
			}
		},
		wxOpenPay: function(param, callback){	// app微信支付
			uexWeiXin.cbStartPay = function(data){
				var result = JSON.parse(data);
				callback({errcode: result.errCode, errmsg: result.errStr});
			}

			var data = JSON.stringify(param);
			uexWeiXin.startPay(data);
		},
	};
	return pay;
})
,define('h5/lrzImage', ['jquery', 'lrz'], function($){
	var lrzImage = function($file, option){
		var t = this;
		t.$file = $file, t.option = option;
		$file.on('change', function(){
			for(var i=0; i<this.files.length; i++){
				t.change(this.files[i]);
			}
		});

		$file.val('');
	}

	lrzImage.prototype.change = function(file){
		var t = this;
		lrz(file).then(function(rst){
        	var vid = newId(), todo = t.option.preview.apply(t.$file, [rst, vid]);
        	if(todo !== false){t.doUp(rst.base64, vid)}
        }).catch(function (err) {
            $file.trigger('lrz.error', [err]);
        }).always(function () {
            $file.trigger('lrz.complete');
        });
	}

	lrzImage.prototype.doUp = function(base64, vid){
		var t = this, name = t.$file.attr('name'), o = this.option, param = {};
		param[name] = base64;

		$.ajax({
			url: o.url,
			type: 'post',
			data: param,
			dataType: 'json',
			xhrFields: {'Access-Control-Allow-Origin': '*'},
			success: function(data){
				o.success.apply(t.$file, [data, vid]);
			},
			error: function(){
				o.error.apply(t.$file, vid);
			}
		});
	}

	$.fn.lrzImage = function(option){
		var $element = this;
		$element.each(function(i){
			new lrzImage($element.eq(i), option);
		});
	}
	return lrzImage;
})
