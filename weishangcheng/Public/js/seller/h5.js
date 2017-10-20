var isWeiXin = navigator.userAgent.toLowerCase().match(/MicroMessenger/i) == "micromessenger";
var isApp = !!navigator.userAgent.match(/Appcan/);

Number.prototype.bcFixed = String.prototype.bcFixed = String.prototype.toFixed = function(digits){
	if(!/\d+/.test(digits)){
		digits = 2;
	}
	var r = this.toString().split('.');
    if(r.length == 1){
    	return (r[0]*1).toFixed(digits);
    }else{
    	return ((r[0]+'.'+r[1].substr(0, digits))*1).toFixed(digits);
    }
}
Number.prototype.bcadd = String.prototype.bcadd = function(p2, n){
	var v1 = this.toString(),
		v2 = p2.toString()
		v = l1 = l2 = p = 0;

	try{l1 = v1.split('.')[1].length}catch(e){}
	try{l2 = v2.split('.')[1].length}catch(e){}

	p = Math.pow(10, Math.max(l1, l2));
	v = (v1.bcmul(p) + v2.bcmul(p)) / p;

	return !!n ? v.bcFixed(n) : v;
}
Number.prototype.bcsub = String.prototype.bcsub = function(p2, n){
	var v1 = this.toString(),
		v2 = p2.toString()
		v = l1 = l2 = p = 0;

	try{l1 = v1.split('.')[1].length}catch(e){}
	try{l2 = v2.split('.')[1].length}catch(e){}

	p = Math.pow(10, Math.max(l1, l2));
	v = (v1.bcmul(p) - v2.bcmul(p)) / p;
	return !!n ? v.bcFixed(n) : v;
}
Number.prototype.bcmul = String.prototype.bcmul = function(p2, n){
	var v1 = this.toString(),
	v2 = p2.toString()
	v = len = 0;

	try{len += v1.split('.')[1].length}catch(e){}
	try{len += v2.split('.')[1].length}catch(e){}
	v = Number(v1.replace('.', '')) * Number(v2.replace('.', '')) / Math.pow(10, len);
	return !!n ? v.bcFixed(n) : v;
}
Number.prototype.bcdiv = String.prototype.bcdiv = function(p2, n){
	var v1 = this.toString(),
		v2 = p2.toString()
		v = l1 = l2 = 0;

	try{l1 = v1.split('.')[1].length}catch(e){}
	try{l2 = v2.split('.')[1].length}catch(e){}

	 v = (Number(v1.replace('.', '')) / Number(v2.replace('.', ''))).bcmul(Math.pow(10, l2 - l1));
	 return !!n ? v.bcFixed(n) : v;
}

define('h5/validate', ['jquery','validatejs'],function(){jQuery.validator.addMethod("mobile",function(value,element){var tel=/^1[3|4|5|7|8]\d{9}$/;return this.optional(element)||(tel.test(value))},"请输入有效的手机号码");jQuery.validator.addMethod("cardid",function(value,element){var tel=/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/;return this.optional(element)||(tel.test(value))},"请输入有效的身份证号");jQuery.validator.addMethod("regular",function(value,element){var regular=eval(element.getAttribute('data-rule-regular'));return this.optional(element)||(regular.test(value))},"输入有误");$.extend($.validator.messages,{required:"这是必填字段",remote:"请修正此字段",email:"请输入有效的电子邮件地址",url:"请输入有效的网址",date:"请输入有效的日期",dateISO:"请输入有效的日期 (YYYY-MM-DD)",number:"请输入有效的数字",digits:"只能输入数字",creditcard:"请输入有效的信用卡号码",equalTo:"你的输入不相同",extension:"请输入有效的后缀",maxlength:$.validator.format("最多可以输入 {0} 个字符"),minlength:$.validator.format("最少要输入 {0} 个字符"),rangelength:$.validator.format("请输入长度在 {0} 到 {1} 之间的字符串"),range:$.validator.format("请输入范围在 {0} 到 {1} 之间的数值"),max:$.validator.format("请输入不大于 {0} 的数值"),min:$.validator.format("请输入不小于 {0} 的数值")});var t={init:function($form,callback){$form.validate({ignore:".ignore",focusInvalid:false,onfocusout:false,onkeyup:false,errorPlacement:function(error,element){},invalidHandler:function(event,validator){toast.show(validator.errorList[0].message);validator.errorList[0].element.focus()},submitHandler:function(){var data={};var array=$form.serializeArray();for(var i=0;i<array.length;i++){if(typeof data[array[i].name]=='undefined'){data[array[i].name]=array[i].value}else{data[array[i].name]+=','+array[i].value}}if(typeof callback=='function'){var result=callback.apply($form,[data]);if(result===false){return false}} $form.ajaxSubmit();return false}})},onSubmit:function(){}};return t;});

requirejs(['jquery'], function(){
	win.start();

	var $container = $('body>.container');
	var minHeight = document.documentElement.clientHeight - parseFloat($container.css('padding-bottom'))- parseFloat($container.css('padding-top')) - parseFloat($container.children('.js-footer').height());
	$container.children('.content').css('min-height', minHeight + 'px');

	// 联系客服
	setTimeout(function(){
		require(['customer_service'], function(t){
			$('body').on('click', '.js-lxkf', function(){
				t.show($(this).data());
				return false;
			})
		});
	}, 1000);

	// setTimeout(function(){
	// 	require(['order_timer']);
	// }, 3000);
});

// 常用函数封装
var win = {
	start: function(){
		this.globalAjax();
		this.initToast();
		this.init('body');
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
			waitting: '',
			$msg_box: null,
			beforeSend: function(XHR){
				// loading加载等待提示框
				var type = this.type.toUpperCase();
				if(this.waitting || (type == 'POST' && this.waitting !== false)){
					var msg = '加载中';
					if(typeof this.waitting == 'string' && this.waitting.length > 0){
						msg = this.waitting;
					}else if(type == 'POST'){
						msg = '处理中';
						if(this.post_msg == "upload_img"){
							msg = "正在上传图片,请耐心等待...";
						}
					}
					toast.loading(msg);
				}

				if(this.custom){
					return;
				}

				this.custom = {};
				this.custom.success = this.success;
				this.custom.error = this.error;
				this.custom.complete = this.complete;

				var retry = this;
				this.success = function(data, textStatus, jqXHR){
					var response_type = jqXHR.getResponseHeader("Content-Type");
					if(this.dataType != 'json' && response_type != 'application/json; charset=utf-8'){
						if(typeof this.custom.success == 'function'){
							this.custom.success(data, textStatus, jqXHR);
						}
						return;
					}

					// 我请求的不是json数据，而返回的却是json数据(可能服务端出错)
					if(this.dataType != 'json' && typeof data != 'object' && response_type == 'application/json; charset=utf-8'){
						data = $.parseJSON(data);
					}

					if(!isWeiXin && data.status == -1){ // 登录
						if(data.type == 'app'){
							require(['login_modal'], function(modal){
								modal.init({appid: data.appid, redirect: data.redirect, mobile: data.mobile});
								modal.onLogin = function(){
									$.ajax(retry);
								}
							});
						}else{
							return win.redirect(data.url);
						}
						return;
					}

					if(data.url){
						return win.redirect(data.url, 2);
					}

					if(typeof data.info == 'string' && data.info != ''){
						if(data.status == 0){
							alert(data.info);
						}else{
							toast.show(data.info);
						}
					}

					if(!isNaN(data.status)){
						if(data.status == 1){
							if(typeof this.custom.success == 'function'){
								return this.custom.success(typeof data.info == 'object' ? data.info : {}, textStatus, jqXHR);
							}else{
								return;
							}
						}else if(data.status == 0){
							if(typeof this.custom.error == 'function'){
								if(typeof data.info == 'object'){
									if(typeof data.info.msg == 'string'){
										toast.show(data.info.msg);
									}else{
										toast.show('操作失败！');
									}
									return this.custom.error(data.info, textStatus, jqXHR);
								}else{
									return this.custom.error({}, textStatus, jqXHR);
								}
							}else{
								return;
							}
						}
					}else if(typeof this.custom.success == 'function'){
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

					if(typeof this.dialog == 'object'){
						this.dialog.remove();
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
		require(['validate'], function(t){
			$forms.each(function (i) {
				t.init($forms.eq(i));
			});
		});
	},
	initToast: function(){
		var paddingTop = document.body.style.paddingTop == '' ? 0 : document.body.style.paddingTop + 'px';
		$('body').append('<div id="toast-view"><div class="ext-tips" style="top:'+paddingTop+'"><span></span></div></div></div>');

		var toast = {
			timer: null,
			$dialog: null,
			$tip: null,
			$content: null,
			init: function(){
				this.timer = null;
				this.$dialog = $('#toast-view'),
				this.$tip = this.$dialog.children(),
				this.$content = this.$tip.children()
			},
			show: function(msg){ // 弹出文字提示
				if(isApp){
					uexWindow.toast(0,5, msg, 2500);
					return;
				}
				if(!this.$dialog){
					toast.init();
				}
				this.$dialog.attr('class', '');
				this.$content.html(msg);
				this.$tip.addClass('show');
				window.clearTimeout(toast.timer);
				toast.timer = setTimeout(function(){
					toast.$tip.removeClass('show');
				}, 2500);
			},
			warning: function(msg){
				toast.show(msg);
				toast.$dialog.attr('class', 'warning');
			},
			loading: function(msg){ // 显示加载等待
				if(msg === false){
					$('#loading_modal').remove();
					return;
				}
				$('body').append('<div id="loading_modal"class="loading-wrapper"><div class="mask"></div><div class="inner"></div><div class="text">'+msg+'</div><div class="loading-dot"><span></span><span></span><span></span></div></div>')
			},
			close: function(){

			}
		}

		window.toast = toast;
	},
	close: function(){
		if (isWeiXin) {
	        WeixinJSBridge.invoke('closeWindow', {}, function (res) {});
	    } else if (isApp) {
	    	uexWidgetOne.exit(0);
	    } else {
	        window.close();
	    }
	}
};

function newId(length){
	if(length == undefined){
		length = 10;
	}
	var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	var str = "";
	for (var i = 0; i < length; i++) {
		str += chars.substr(Math.ceil(Math.random()*chars.length), 1);
	}
	return str;
}

define('skumodal', ['jquery'], function($){	// 弹出sku
	//收藏
	$('body').on('click', '.js-add-collect', function(){
		var goods_id = $(this).data('id');
		$.ajax({
			url: '/h5/collection/add',
			data: {goods_id: goods_id},
			dataType: 'json',
			success: function(){
				$('.js-add-collect').addClass('checked');
			},
			error: function(){
				$('.js-add-collect').removeClass('checked');
			}
		});
		return false;
	});

	var ShoppingCart = function(_options){
		this.options = $.extend({
				id: '',
				goods: {},
				onBuy: null,
				onCart: null,
				loading: function(){}
		}, _options);

		this.bg_id = '';
		this.modal_id = '';
		this.getSKU(_options.data);
	}

	ShoppingCart.prototype = {
		goods: {				// 商品信息
				id: '', 		// 商品id
				title: '', 		// 商品名称
				desc: '',		// 商品描述
				price: '0.00',	// 价格
				original_price: '',	// 原价
				score: 0,		// 支付积分
				points: 0,		// 购买赠送积分
				pay_type: 1,	// 支付方式(1price，2score，3price+score)
				stock: 0,	// 库存
				hide_stock: 0,	// 隐藏库存
				pic_url: '', 	// 商品宣传图
				buy_quota: 0,		// 每人限购
				quota: 0,	// 用户最多可购买数量
				sold_num: 0,	// 总销售量
				num: 1, 	// 商品购买数量
				sale_distance:0, // 距离开售时间剩余秒数
				sku_json: [],	// 产品sku
				products: [],	// 产品
		},
		product: {	// 选择的产品信息
			id: '', 	// 产品id
			goods_id: '',	// 商品id
			price: '0.00',	// 价格,
			orginal_price: '', // 原价
			num: 1,	// 购买数量
			stock: 0,	// 库存
			score: 0,	// 支付积分
			sku_json: [],
			sku: '', // 规格字符串
			pic_url: '',
		},
		getSKU: function(parameters){// 获取商品信息
			var obj = this;
			if(parameters){
				obj.request_parameters = parameters;
			}else{
				parameters = obj.request_parameters;
			}
			obj.options.loading(true);
			$.ajax({
				url: '/h5/goods/skudata',
				data: parameters,
				dataType: 'json',
				success: function(data){
					obj.setGoods(data);
					obj.show();
				},
				complete: function(){
					if(typeof obj.options.loaded == 'function'){
						obj.options.loaded();
					}
				}
			});
		},
		init: function(){ // 初始化
			this.$modal = null;
			this.$btn_minus = null;	// 减少按钮
			this.$btn_plus = null;	// 增加按钮
			this.$input_num = null;	// 购买数量文本框
			this.$btn_cart = null;	// 加入购物车按钮
			this.$btn_next = null;	// 下一步按钮
			this.$btn_buy = null;	// 立即购买按钮
			this.$js_stock = null;	// 库存
		},
		SKUResult: {},
		keys: [],
		data: {}
		,getObjKeys: function(obj){if(obj!==Object(obj))throw new TypeError('Invalid object');var keys=[];for(var key in obj)if(Object.prototype.hasOwnProperty.call(obj,key))keys[keys.length]=key;return keys;}
		,combInArray: function(aData){if(!aData||!aData.length){return[]}var len=aData.length;var aResult=[];for(var n=1;n<len;n++){var aaFlags=this.getCombFlags(len,n);while(aaFlags.length){var aFlag=aaFlags.shift();var aComb=[];for(var i=0;i<len;i++){aFlag[i]&&aComb.push(aData[i])}aResult.push(aComb)}}return aResult}
		,getCombFlags: function(m,n){if(!n||n<1){return[]}var aResult=[];var aFlag=[];var bNext=true;var i,j,iCnt1;for(i=0;i<m;i++){aFlag[i]=i<n?1:0}aResult.push(aFlag.concat());while(bNext){iCnt1=0;for(i=0;i<m-1;i++){if(aFlag[i]==1&&aFlag[i+1]==0){for(j=0;j<i;j++){aFlag[j]=j<iCnt1?1:0}aFlag[i]=0;aFlag[i+1]=1;var aTmp=aFlag.concat();aResult.push(aTmp);if(aTmp.slice(-n).join("").indexOf('0')==-1){bNext=false}break}aFlag[i]==1&&iCnt1++}}return aResult}
		,initSKU: function(sku_json,products){for(var i=0;i<sku_json.length;i++){var sku_items=sku_json[i].items;var items=[];for(var j=0;j<sku_items.length;j++){items.push(sku_items[j].id)}this.keys.push(items)}for(var i=0;i<products.length;i++){if(products[i].stock==0){continue}var product=products[i],sku_items=product.sku_json,sku_key='';for(var j=0;j<sku_items.length;j++){sku_key+=(j==0?'':';')+sku_items[j].vid}this.data[sku_key]=product}var i,j,skuKeys=this.getObjKeys(this.data);for(i=0;i<skuKeys.length;i++){var skuKey=skuKeys[i];var sku=this.data[skuKey];var skuKeyAttrs=skuKey.split(";");skuKeyAttrs.sort(function(value1,value2){return parseInt(value1)-parseInt(value2)});var combArr=this.combInArray(skuKeyAttrs);for(j=0;j<combArr.length;j++){var key=combArr[j].join(";");if(!this.SKUResult[key]){this.SKUResult[key]=true}}this.SKUResult[skuKeyAttrs.join(";")]=sku}}
		,setGoods: function(goods){
			this.goods = goods;
			if(goods.products.length == 1){
				this.product = $.extend({}, goods.products[0])
			}else{
				this.product = $.extend({}, goods),
				this.product.id = ''
			}

			this.initSKU(goods.sku_json, goods.products);
		},
		buyNum: 0,
		show: function(){ // 显示
			var goods = this.goods;
			this.bg_id = newId();
			this.modal_id = newId();

			var html = '<div id="'+this.bg_id+'" class="modal-backdrop"></div>';

			html += '<div id="'+this.modal_id+'" class="sku-layout modal" style="overflow:visible">';
			html += '	<div class="layout-title name-card sku-name-card">';
			html += '		<div class="thumb">';
			html += '			<img src="'+goods.pic_url+'">';
			html += '		</div>';
			html += '		<div class="detail goods-base-info clearfix">';
			html += '			<div class="goods-price"></div>';

			if(!goods.hide_stock && this.goods.sale_distance > 0 && goods.quota_str){
				goods.hide_stock = 1;
			}
			html += '			<div class="stock'+(goods.hide_stock ? " hide" : "")+'">库存0件</div>';
			if(goods.quota_str){
				html += '		<div class="ellipsis">'+goods.quota_str+'</div>';
			}

			html += '			<div id="sku_countdown" class="sale-distance"></div>';
			html += '		</div>';
			html += '		<div class="js-cancel sku-cancel">';
			html += '			<div class="cancel-img"></div>';
			html += '		</div>';
			html += '	</div>';
			html += '	<div class="js-agent-notice"></div>';
			html += '	<div class="layout-content" style="max-height: '+(document.documentElement.clientHeight * 0.7 - 50 ).toFixed(2)+'px;">';

			// sku属性
			if(goods.sku_json){
				// 如果只有一种产品则默认选中
				var active = goods.products.length == 1 ? ' active' : '';
				var disabled = '';
				for(var i=0; i<goods.sku_json.length; i++){
					html += '<dl class="clearfix block-item">';
					html += '	<dt class="model-title sku-sel-title">';
					html += '		<label>'+goods.sku_json[i].text+'：</label><span></span>';
					html += '	</dt>';
					html += '	<dd>';
					html += '		<ul class="model-list sku-sel-list">';

					var sku_items = goods.sku_json[i].items, img = '';
					for(var j=0; j<sku_items.length; j++){
						disabled = !this.SKUResult[sku_items[j].id] ? ' unavailable' : '';
						img = sku_items[j].img ? sku_items[j].img : '';
						html += '<li class="tag sku-tag pull-left ellipsis'+active + disabled +'" data-id="'+sku_items[j].id+'" data-img="'+img+'">'+sku_items[j].text+'</li>';
					}

					html += '		</ul>';
					html += '	</dd>';
					html += '</dl>';
				}
			}
			html += '		<dl class="clearfix block-item">';
			html += '			<dt class="model-title sku-weight pull-left'+(goods.hide_weight ? " hide" : "")+'">';
			html += '				<label>重：'+goods.weight+'kg</label>';
			html += '			</dt>';
			html += '			<dd>';
			html += '				<dl class="clearfix">';
			html += '					<div class="quantity">';
			html += '						<button class="minus disabled" type="button" disabled="true"></button>';
			html += '						<input type="text" class="txt js-input-num" value="1">';
			html += '						<button class="plus" type="button"></button>';
			html += '						<div class="response-area response-area-minus"></div>';
			html += '						<div class="response-area response-area-plus"></div>';
			html += '					</div>';
			html += '				</dl>';
			html += '			</dd>';
			html += '		</dl>';
			html += '	</div>';

			html += '	<div class="content-foot clearfix">';
			html += '		<a class="js-add-collect collect'+(goods.other.is_collection ? ' checked' : '')+'" data-id="'+goods.id+'"></a>';
			html += '		<a href="javascript:;" class="kefu js-lxkf" data-shop="'+goods.shop_id+'" data-goods="'+goods.id+'"></a>';
			html += '		<a class="cart" href="/h5/cart"><b class="js-cart-num">'+goods.other.cart_num+'</b><em id="J-addone">+1</em></a>';

			var buttons = this.options.buttons;
			var actions = goods.action;
			var actionsBtn = [];
			for(var i =0; i<actions.length; i++){
				if(goods.agent_level == 0 && actionsBtn.length > 0){
					break;
				}

				if(buttons[actions[i].id]){
					actionsBtn.push('<button id="sku_'+actions[i].id+'" type="button" class="btn '+actions[i]['class']+'"'+(actions[i].disabled ? ' disabled' : '')+'>'+actions[i]['txt']+'</button>');
				}
			}
			if(goods.agent_level == 0){ actionsBtn.push('<a class="btn btn-red" href="/h5/pay/rule">升级为会员</a>') }
			html += '		<div class="actions actions-'+actionsBtn.length+' clearfix">'+actionsBtn.join('')+'</div>';
			html += '	</div>';
			html += '</div>';

			$('body').append(html);

			var obj = this;
			// 点击黑色背景关闭弹窗
			$('#' + obj.bg_id).on('click', function(){
				obj.close();
			});

			var $modal = obj.$modal = $('#' + obj.modal_id);
			$modal.find('.js-cancel').on('click', function(){
				obj.close();
			});

			if(goods.countdown && goods.countdown.end > 0){
				this.countdown(goods.countdown);
			}

			// 代理价
			this.$agentNotice = $modal.find('.js-agent-notice');

			// 价格
			this.$current_price = $modal.find('.goods-price');
			this.$thumbImg = $modal.find('.thumb>img');

			// 购买数量文本框
			this.$input_num = $modal.find('.js-input-num').on('change', function(){
				obj.quantity(parseInt(this.value));
				return false;
			});
			// 减少数量
			this.$btn_minus = $modal.find('.response-area-minus').on('click', function(){
				 obj.quantity(parseInt(obj.$input_num.val()) - 1);
					return false;
			});
			// 增加数量
			this.$btn_plus = $modal.find('.response-area-plus').on('click', function(){
				obj.quantity(parseInt(obj.$input_num.val()) + 1);
				return false;
			});
			// 监听产品改变事件
			this.$sku_list = $modal.find('.sku-sel-list').on('click', '.sku-tag',function(){
				obj.checked(this);
				return false;
			});
			this.$sku_tags = this.$sku_list.find('.sku-tag');

			// 剩余数量
			this.$js_stock = $modal.find('.stock');
			$modal.find('#sku_addCart').on('click', function(){
				obj.events.onCart.apply(obj);
				return false;
			});
			$modal.find('#sku_buyNow').on('click', function(){
				obj.events.onBuy.apply(obj);
				return false;
			});

			// 重量
			this.$weightNum = $modal.find('.sku-weight');

			this.setProduct(this.product);
		},
		countdown: function(data){
			var t = this;
			var start_time = data.start*1000;
			var end_time = data.end*1000;
		    var timer = window.setInterval(function(){
		        start_time += 1000;

		    	var leftTime=end_time - start_time;
		    	var leftsecond = parseInt(leftTime/1000);
		    	var day=Math.floor(leftsecond/(60*60*24));
		    	var hour=Math.floor((leftsecond-day*24*60*60)/3600);
		    	var minute=Math.floor((leftsecond-day*24*60*60-hour*3600)/60);
		    	var second=Math.floor(leftsecond-day*24*60*60-hour*3600-minute*60);

		    	if(leftTime == 0){
		    		window.clearInterval(timer);
		    		t.close();
		    		t.getSKU();
		    	}
		    	var html = data.txt;
		    	if(day > 0){
		    		html += '<i>'+(day < 10 ? '0' + day : day)+'</i>天';
		    	}
		    	html += '<i>'+(hour < 10 ? '0' + hour : hour)+'</i>小时'+
		            '<i>'+(minute < 10 ? '0' + minute : minute)+'</i>分'+
		            '<i>'+(second < 10 ? '0' + second : second)+'</i>秒';
		    	var element = document.getElementById('sku_countdown');
		    	if(!element){
		    		window.clearInterval(timer);
		    	}else{
		    		element.innerHTML = html;
		    	}
		    }, 1000);
		},
		checked: function(ele){ // 产品改变
			var t = this;
			var $self = $(ele);
			if($self.hasClass('unavailable')){
				return false;
			}

			$self.parents(':eq(1)').prev().children('span').html($self.hasClass('active') ? '' : $self.text());
			var product = null, img_url, sku_modal = this;
			//选中自己，兄弟节点取消选中
			$self.toggleClass('active').siblings().removeClass('active');

			//已经选择的节点
			var selectedObjs = sku_modal.$sku_list.find('.active');
			if(selectedObjs.length) {
				//获得组合key价格
				var selectedIds = [], img;
				selectedObjs.each(function(i, item) {
					selectedIds.push(item.getAttribute('data-id'));

					img = item.getAttribute('data-img');
					if(img){
						img_url = img;
					}
				});

				selectedIds.sort(function(value1, value2) {
					return parseInt(value1) - parseInt(value2);
				});

				product = sku_modal.SKUResult[selectedIds.join(';')];

				var len = selectedIds.length;

				// 判断是否有此产品

				//用已选中的节点验证待测试节点 underTestObjs
				sku_modal.$sku_tags.not(selectedObjs).not($self).each(function() {
					var $this = $(this);
					var siblingsSelectedObj = $this.siblings('.active');
					var testAttrIds = [];//从选中节点中去掉选中的兄弟节点
					if(siblingsSelectedObj.length) {
						var siblingsSelectedObjId = siblingsSelectedObj.attr('data-id');
						for(var i = 0; i < len; i++) {
							(selectedIds[i] != siblingsSelectedObjId) && testAttrIds.push(selectedIds[i]);
						}
					} else {
						testAttrIds = selectedIds.concat();
					}
					testAttrIds = testAttrIds.concat($this.attr('data-id'));
					testAttrIds.sort(function(value1, value2) {
						return parseInt(value1) - parseInt(value2);
					});

					if(!sku_modal.SKUResult[testAttrIds.join(';')]) {
						$this.addClass('unavailable').removeClass('active');
					} else {
						$this.removeClass('unavailable');
					}
				});
			} else {
				//设置属性状态
				sku_modal.$sku_list.each(function() {
					var $this = $(this);
					sku_modal.SKUResult[$this.attr('data-id')] ? $this.removeClass('unavailable') : $this.addClass('unavailable').removeClass('active');
				})
			}

			this.setProduct(product, img_url);
		},
		setProduct: function(product, img_url){
			var current = null;
			if(product && typeof product == 'object'){
				current = product;
				this.product = $.extend({}, product);
			}else{
				current = this.goods;
			}

			var html = ''
			   ,original_price = ''
			   ,pic_url = img_url ? img_url : this.goods.pic_url;

			if(this.goods.pay_type == 2){ // 积分
				html += '<i class="js-goods-price price c-orange">'+current.score+'</i>';
				html += '<span class="score-name font-size-12 c-orange">积分</span>';
				original_price = parseFloat(current.price) > 0 ? current.price : current.original_price;
			}else if(this.goods.pay_type == 3){ // RMB + 积分
				html += '<span class="js-goods-price price c-orange"><i class="price-name font-size-12">¥</i>' + current.my_price;
				if(product.id != ''){
					html += '+' + current.score + '<span class="font-size-12 c-orange">积分</span>';
				}
				html += '</span>';
			}else if(this.goods.pay_type == 4 || this.goods.pay_type == 5){ // 积分兑换微信红包
				html += '<i class="js-goods-price price font-size-18 c-orange">'+current.score+'</i>';
				html += '<span class="score-name font-size-12 c-orange">积分</span>';
			}else{
				var agent = current.agents[this.goods.agent_level];
				if(agent.price_prefix){html += '<span class="price_prefix">'+agent.price_prefix+'</span>';}
				html += '<em>' + agent.price + '</em>';
				if(agent.price_suffix){html += '<span class="price_suffix">'+agent.price_suffix+'</span>';}
				original_price = parseFloat(current.original_price) > 0 ? current.original_price : '';
			}

			if(original_price != '' && current.price < original_price)
				html += '<span class="origin">¥' + original_price + '</span>';
			this.$current_price.html(html);

			this.$js_stock.html('库存' + current.stock + '件');
			this.quantity(this.buyNum);
			this.$thumbImg.attr('src', pic_url+'');

			// 代理价
			var agentHtml = '', agents = current.agents, commission = 0, html = '';
			for(var i=agents.length-1; i>-1; i--){
				if(agents[i].commission > 0){
					commission = agents[i].commission * 1;
					html = '<span>赚'+agents[i].title+commission+'元</span>';
					if(i == 0){
						agentHtml = html + agentHtml;
					}else{
						agentHtml += html;
					}
				}
			}
			if(agentHtml != '') {agentHtml = '<div class="notice">'+agentHtml+'</div>'}
			this.$agentNotice.html(agentHtml);

			// 重量
			this.$weightNum.html('重量：'+current.weight+'kg('+this.goods.freight_fee.msg+')');
		},
		close: function(){	// 隐藏
			$('#' + this.bg_id + ',#' + this.modal_id).remove();
		},
		quantity: function(num){ //加减数量
			var error = '';

			if(num < 1){
				num = 1;
			}

			if(!this.product.quota){
				this.product.quota = this.product.stock;
			}

			if(num > this.product.quota){
				num = this.product.quota;
			}

			// 限购
			if(num > this.goods.quota){
				num = this.goods.quota;
			}

			// 减少数量按钮样式处理
			if(num <= 1){
				this.$btn_minus.siblings('.minus').addClass('disabled').attr('disabled', 'disabled');
			}else{
				this.$btn_minus.siblings('.minus').removeClass('disabled').removeAttr('disabled');
			}

			// 增加数量按钮样式处理
			if(num == this.goods.quota || num == this.product.quota){
				this.$btn_plus.siblings('.plus').addClass('disabled').attr('disabled', 'disabled');
			}else{
				this.$btn_plus.siblings('.plus').removeClass('disabled').removeAttr('disabled');
			}

			this.$input_num.val(num);
			this.buyNum = num;
		},
		getProduct: function(){
			if(!this.product.id){// 判断哪个规则没有选择
				 var spec_error = [];
				 this.$modal.find('.sku-sel-list').each(function(i, item){
					 var $this = $(item);
					 if($this.children('.active').length == 0){
						 spec_error.push($this.parent().prev().text().replace('：', ''));
					}
				 });

				 var error = '请选择 ';
				 if(spec_error.length == 1){
					 error += spec_error[0];
				 }else{
					 for(var i=0; i<spec_error.length; i++){
						 error += spec_error[i];
						 if(i == spec_error.length - 2){
							 error += ' 和 ';
						 }else if(i < spec_error.length - 2){
							 error += ' 、 ';
						 }
					 }
				 }

				 toast.show(error);
				 return null;
			 }

			this.product.num = this.buyNum;
			return this.product;
		},
		events: {// 监听事件
			onBuy: function(){ // 点击立即购买
				var t = this;
				 var product = t.getProduct();
				 if(!product || !product.id || product.num == 0){
					 return false;
				 }

				 if(typeof t.options.onBuy == 'function'){
					 var result = t.options.onBuy(product);
					 if(result === false){
						 return;
					 }
				 }

				 // 微信红包只能立即兑换
				 if(this.goods.pay_type == 4 || this.goods.pay_type == 5){
					 $.ajax({
						 url: '/h5/pay/redpack',
						 type: 'post',
						 dataType: 'json',
						 data: {goods_id: t.goods.id, product_id: t.product.id, num: 1},
						 success: function(data){
							 t.close();
							 alert('红包已发送，请保持网络通畅！');
						 }
					 });
				 }else{
					 var html =
						 '<form action="/h5/pay/confirm" method="post">'+
						 '<input type="hidden" name="products[0][id]" value="'+product.id+'">'+
						 '<input type="hidden" name="products[0][num]" value="'+product.num+'">'+
						 '</form>';
					 $(html).submit();
				 }
			},
			onCart: function(){ // 点击加入购物车
				var product = this.getProduct();
				 if(!product || !product.id || product.num == 0){
					 return false;
				 }

				if(typeof this.options.onBuy == 'function'){
					var result = this.options.onCart(product);
					if(result === false){
						return;
					}
				}

				var objCar = this;
				 $.ajax({
					 url: '/h5/cart/add',
					 type: 'post',
					 dataType: 'json',
					 data: product,
					 success: function(data){
						 objCar.close();
						 $('.js-cart-num').html(data.total);

						 var $cart = $('#global-cart'),
							 $rightIcon = $('#right-icon');
						 if($cart.length == 0){
							if($rightIcon.length == 0){
								$('body').append('<div id="right-icon" class="js-right-icon no-text"><div class="js-right-icon-container right-icon-container clearfix" style="width: 50px;"></div></div>');
								$rightIcon = $('#right-icon');
							}
							$rightIcon.children('.js-right-icon-container').append('<a id="global-cart" href="/h5/cart" class="icon new s1"><p class="icon-img"></p></a>');
						 }
					 }
				 });
			}
		}
	}

	return ShoppingCart;
}),
define('order/address', ['jquery', 'validate', 'address'], function($, validate){
	var address = {
		init: function(_default){
			this.selected = _default;
		},
		list: [],
		_default: {
			id: '',
			user_name: '',
			mobile: '',
			province_name: '',
			city_name: '',
			county_name: '',
			detail: '',
			zip_code: ''
		},
		selected: {},
		edit: function(edit_data){
			this.close();

			var addr = null;
			var edit_index = -1;

			if(typeof edit_data == 'object'){
				addr = edit_data;
				if(!isNaN(addr.id)){
					for(var i=0; i<this.list.length; i++){
						if(this.list[i].id == addr.id){
							edit_index = i;
							addr = this.list[i];
							break;
						}
					}
				}
			}else if(!isNaN(edit_data)){
				edit_index = edit_data;
				addr = this.list[edit_index];
			}else{
				addr = this._default;
			}

			var id = newId();
			var html = '';
			html += '<div id="modal_'+id+'" class="modal-backdrop"></div>';
			html += '<div id="'+id+'" class="modal">';
			html += '	<form class="js-address-fm address-ui address-fm" method="post" action="/h5/address/edit">';
			html += '    	<h4 class="address-fm-title">收货地址</h4>';
			html += '	    <div class="js-address-cancel publish-cancel js-cancel">';
			html += '	        <div class="cancel-img"></div>';
			html += '	    </div>';
			html += '    	<div class="block form" style="margin:0;">';
			html += '    		<input type="hidden" name="id" value="'+addr.id+'">';
			html += '	        <div class="block-item no-top-border">';
			html += '	            <label>收 货 人</label>';
			html += '	            <input type="text" name="receiver_name" value="'+addr.user_name+'" placeholder="名字" required="required" data-msg-required="请输入收货人" rangelength="2,15" data-msg-rangelength="收货人在2~15个字符之间" maxlength="15">';
			html += '	        </div>';
			html += '	        <div class="block-item">';
			html += '	            <label>联系电话</label>';
			html += '	            <input type="tel" name="receiver_mobile" value="'+addr.mobile+'" placeholder="手机号码" required="required" data-rule-mobile="mobile" data-msg-required="请输入联系电话" data-msg-mobile="请输入正确的手机号" maxlength="11">';
			html += '	        </div>';
			html += '	        <div class="block-item">';
			html += '	            <label>选择地区</label>';
			html += '	            <div class="js-area-select area-layout">';
			html += '	            	<span>';
			html += '						<select id="province" name="receiver_province" data-city="#city" data-selected="'+addr.province_name+'" data-name="true" class="address-province" required="required" data-msg-required="请选择省份">';
			html += '							<option value="">选择省份</option>';
			html += '						</select>';
			html += '					</span>';
			html += '					<span>';
			html += '						<select id="city" name="receiver_city" data-county="#county" data-selected="'+addr.city_name+'" class="address-city" required="required" data-msg-required="请选择城市">';
			html += '							<option value="">选择城市</option>';
			html += '						</select>';
			html += '					</span>';
			html += '					<span>';
			html += '						<select id="county" name="receiver_county" data-selected="'+addr.county_name+'" class="address-county" required="required" data-msg-required="请选择区县">';
			html += '							<option value="">选择区县</option>';
			html += '						</select>';
			html += '					</span>';
			html += '				</div>';
			html += '        	</div>';
			html += '	        <div class="block-item">';
			html += '	            <label>详细地址</label>';
			html += '	            <input type="text" name="receiver_detail" value="'+addr.detail+'" placeholder="街道门牌信息，请勿重复省市区" required="required" rangelength="5,120" data-msg-required="请输入详细地址" data-msg-rangelength="详细地址在5~120个字符之间" maxlength="120">';
			html += '	        </div>';
			html += '	        <div class="block-item">';
			html += '	            <label>邮政编码</label>';
			html += '	            <input type="text" minlength="6" maxlength="6" name="receiver_zip" value="'+addr.zip_code+'" placeholder="邮政编码(选填)" data-rule-digits="digits" data-msg-digits="邮政编码应为6位数字">';
			html += '	        </div>';
			html += '    	</div>';
			html += '	    <div>';
			html += '	        <div class="action-container">';
			html += '	            <button type="submit" class="js-address-save btn btn-block btn-red">确定</button>';
			if(edit_index > -1){
				html += '	            <a class="js-address-delete btn btn-block">删除收货地址</a>';
			}
			html += '	        </div>';
			html += '	    </div>';
			html += '	</form>';
			html += '</div>';

			$('html,body').css({'height': document.documentElement.clientHeight + 'px', 'overflow': 'hidden'});
			$('body').append(html);

			this.$bg = $('#modal_' + id);
			var $modal = this.$modal = $('#' + id);

			Address.bind('#province');

			// 监听表单提交
			var $form = $modal.find('.js-address-fm'),
			changed = false;
			$form.on('change', function(){
				changed = true;
			});

			validate.init($form, function(data){
				if(!changed){
					address.close();
					return false;
				}

				data.province_code = $modal.find('#province :selected').data('value');
				data.city_code = $modal.find('#city :selected').data('value');
				data.county_code = $modal.find('#county :selected').data('value');
				address.onSelect(data);
				address.close();
				return false;
			});

			address.$bg.on('click', function(){
				address.close();
			});
			$modal.find('.js-address-cancel').on('click', function(){
				address.close();
			});

			$modal.find('.js-address-delete').on('click', function(){
				if(confirm('确定要删除这个收货地址么？')){
					$.ajax({
						url: '/h5/address/delete',
						type: 'post',
						data: {id: addr.id},
						dataType: 'json',
						success: function(){
							address.list.splice(edit_index, 1);
							address.show();
						},
						error: function(){
							toast.show('删除失败');
						}
					});
				}
			});
		},
		loading: false,
		getList: function(){
			address.loading = true;
			$.ajax({
				url: '/h5/address/my',
				dataType: 'json',
				loading: true,
				success: function(list){
					address.loading = false;
					address.list = list;
					address.show();
				},
				complete: function(){
					address.loading = false;
				}
			});
		},
		show: function(){
			if(this.loading){
				return;
			}

			if(this.list == null){
				this.getList();
				return;
			}

			if(this.list.length == 0){
				this.edit(this.selected);
				return;
			}

			this.close();
			$('html,body').css({'height': document.documentElement.clientHeight + 'px', 'overflow': 'hidden'});

			var list = this.list;
			var id = newId();
			var html = '';
			html += '<div id="modal_'+id+'" style="height: 100%; position: absolute; top: 0px; left: 0px; right: 0px; z-index: 1000; transition: none 0.2s ease; opacity: .7; background-color: rgba(0, 0, 0, 0.901961);"></div>';
			html += '<div id="'+id+'" style="overflow: hidden; left: 0px; right: 0px; bottom: 0px; visibility: visible; position: fixed; z-index: 1000; transform: translate3d(0px, 0px, 0px); transition: all 300ms ease;background: white;">';
			html += '<div class="js-scene-address-list">';
			html += '	<div class="address-ui address-list">';
			html += '	    <h4 class="address-title">选择收货地址</h4>';
			html += '	    <div class="cancel-img js-cancel"></div>';
			html += '	    <div class="js-address-container address-container">';
			if(list.length == 0){
				html += '	    	<p style="text-align:center;line-height:60px;">列表为空</p>';
			}else{
				for(var i=0; i<list.length; i++){
					html += '	    	<div data-index="'+i+'" class="js-address-item block-item'+(this.selected.id == list[i].id ? ' address-selected' : '')+'">';
					html += '	    		<div class="icon-check"></div>';
					html += '	    		<p>'+list[i].user_name+', '+list[i].mobile+'</p>';
					html += '	    		<span class="address-str address-str-sf">'+list[i].province_name + ' ' + list[i].city_name + ' ' + list[i].county_name + ' ' + list[i].detail +'</span>';
					html += '	    		<div class="address-opt  js-edit-address ">';
					html += '	    	    	<i class="icon_circle-info">i</i>';
					html += '	    		</div>';
					html += '	    	</div>';
				}
			}
			html += '	    </div>';
			html += '	    <div class="action-container js-add-address">';
			html += '	        <span class="icon_add"></span>';
			html += '	        <a class="add-address" href="javascript:;">新增地址</a>';
			html += '	        <span class="icon_arrow-right"></span>';
			html += '	    </div>';
			html += '	</div>';
			html += '</div>';
			html += '</div>';
			$('body').append(html);
			this.$bg = $('#modal_' + id);
			var $modal = this.$modal = $('#' + id);
			this.$bg.on('click', function(){
				address.close();
			});
			$modal.find('.js-cancel').on('click', function(){
				address.close();
			});

			// 新增收货地址
			$modal.find('.js-add-address').on('click', function(){
				address.close();
				address.edit();
			});

			var $address_container = $modal.find('.js-address-container');
			// 点击编辑收货地址
			$address_container.find('.js-edit-address').on('click', function(){
				var index = $(this).parent().attr('data-index');
				address.edit(index);
				return false;
			});

			// 选择收货地址
			$address_container.find('.js-address-item').on('click', function(){
				var $this = $(this);
				var index = $this.attr('data-index');
				address.selected = $.extend({}, address.list[index]);

				$this.addClass('address-selected').siblings().removeClass('address-selected');
				address.onSelect(address.selected);
				address.close();
				return false;
			});
		},
		close: function(){
			$('body,html').css({'height': '', 'overflow': ''});
			if(this.$bg){
				this.$bg.remove();
				this.$modal.remove();
			}
		},
		onSelect: function(address){
		}
	}
	return address;
});
define('mall/cart', ['jquery'],function(){
	var cart = {
		editing: false,
		data: [],
		init: function(list){
			this.$cart_contianer = $('#cart-container'),
			this.$cart_bottom = $('.js-cart-bottom'),
			this.$select_all = this.$cart_bottom.find('.js-select-all'),
			this.$total_price = this.$cart_bottom.find('.js-total-price');
			this.$btn_pay = this.$cart_bottom.find('.js-go-pay');

			cart.data = list;
			cart.render();
		},
		template: function(list){
			var html = '';
			for(var i = 0; i < list.length; i++) {
				html +=
				'<div class="block js-cart-group">' +
				'	<div class="block block-order block-cart">' +
				'		<div class="header">' +
				'			<a class="font-size-12" href="'+list[i].url+'">店铺：'+list[i].name+'</a>' +
				'			<a href="javascript:;" data-type="cart" class="js-edit-list pull-right c-blue font-size-12 edit-list">编辑</a>' +
				'		</div>' +
				'		<ul class="block block-list block-list-cart border-bottom-0">';

				var myLevel = list[i].agent_level;
				for(var j = 0; j < list[i].products.length; j++) {
					html +=
					'<li class="block-item block-item-cart relative clearfix" data-id="'+list[i].products[j].id+'">'+
					'	<div class="check-container">'+
					'		<span class="check '+(list[i].products[j].disabled ? "disabled info" : "checked")+'"></span>'+
					'	</div>'+
					'	<div class="name-card name-card-3col clearfix">'+
					'		<a class="thumb js-goods-link">'+
					'			<img src="'+list[i].products[j].pic_url+'">'+
					'		</a>'+
					'		<div class="detail">'+
					'			<a class="js-goods-link" href="'+list[i].products[j].url+'">'+
					'				<h3 class="js-ellipsis" style="max-height: 32px; overflow: hidden;">'+
					'					<i>'+list[i].products[j].title+'</i>'+
					'				</h3>'+
					'				<p class="ellipsis">'+list[i].products[j].spec+'</p>'+
					'					<div class="error-box">'+list[i].products[j].error+'</div>'+
					'			</a>'+
					'		</div>'+
					'		<div class="right-col price-num">';

					html += '<div class="price">';
					if(list[i].products[j].price_prefix){
						html += '<span class="price_prefix">'+list[i].products[j].price_prefix+'</span>';
					}
					html += '<em>'+list[i].products[j].price+'</em>';
					if(list[i].products[j].price_suffix){
						html += '<span class="price_suffix">'+list[i].products[j].price_suffix+'</span>';
					}
					html += '</div>';

					html += '<div class="num">'+
							'	<span class="num-txt">×'+list[i].products[j].num+'</span>';

					if(!list[i].products[j].disabled){
						html +=
						'<div class="quantity">'+
						'	<button type="button" class="minus"disabled="disabled"></button>'+
						'	<input type="text" class="txt" value="'+list[i].products[j].num+'">'+
						'	<button type="button" class="plus"disabled="disabled"></button>'+
						'	<div class="response-area response-area-minus"></div>'+
						'	<div class="response-area response-area-plus"></div>'+
						//'	<div class="txtCover"></div>'+
						'</div>';
					}
					html += '</div></div><div class="delete-btn"><span>删除</span></div></li>';
				}
				html += '</ul></div></div>';
			}
			return html;
		},
		render: function(){
			var list = this.data;
			var $bottom = $('.js-cart-bottom');
			if(list.length == 0){
				$bottom.hide();
				this.$cart_contianer.html('<div class="empty-list "style="padding-top:60px;"><div class="empty-list-header"><h4>购物车快饿瘪了T.T</h4><span>快给我挑点宝贝</span></div><div class="empty-list-content"><a href="/h5/mall"class="js-go-home home-page tag tag-big tag-orange">去逛逛</a></div></div>');
				return;
			}else{
				$bottom.show();
			}

			var html = this.template(list);
			this.$cart_contianer.html(html);

			var $list = this.$cart_contianer.find('li.block-item-cart');
			$list.each(function(ii, item){
				var $li = $list.eq(ii);
				var cart_id = $li.attr('data-id');

				for(var i=0; i<list.length; i++){
					for(var j=0; j<list[i].products.length; j++){
						if(list[i].products[j].id == cart_id){
							$li.data('product', list[i].products[j]);
							return true;
						}
					}
				}
			});

			this.reg_event();
			this.checked();
		},
		reg_event: function(){
			this.$cart_contianer.find('.check-container').on('click', function(){
				if(cart.editing){
					cart.remove(this.parentElement);
					return false;
				}

				var $check = $(this).find('.check');
				if($check.hasClass('disabled')){
					return false;
				}

				if($check.hasClass('checked')){
					$check.removeClass('checked');
				}else{
					$check.addClass('checked');
				}

				cart.checked();
				return false;
			});

			this.$select_all.on('click', function(){
				cart.checked(!this.classList.contains('checked'));
			});

			this.$cart_contianer.find('.js-edit-list').on('click', function(){
				var cart_block_class = this.parentElement.parentElement.classList;
				if(this.innerText == '编辑'){
					this.innerText = '完成';
					cart_block_class.add('editing');
					cart.editing = true;
				}else{
					this.innerText = '编辑';
					cart_block_class.remove('editing');
					cart.editing = false;
				}
			});

			this.$cart_contianer.find('.quantity').on('click', '.response-area-minus,.response-area-plus', function(){
				if(this.classList.contains('disabled')){
					return false;
				}
				if(this.classList.contains('response-area-minus')){
					cart.buyNum(this.parentElement, -1, true);
				}else{
					cart.buyNum(this.parentElement, 1, true);
				}
				cart.checked();
				return false;
			}).find('.txt').on('change', function(){
				if(this.value == '' || isNaN(this.value)){
					this.value = 1;
				}
				cart.buyNum(this.parentElement, 0, true);
			}).each(function(i, item){
				cart.buyNum(this.parentElement, 0, false);
			});

			this.$btn_pay.on('click', function(){
				var form = '<form action="/h5/pay/confirm" method="post">';
				form += '<input type="hidden" name="from" value="shopping_cart">';
				var $check_items = cart.$cart_contianer.find('.check');
				$check_items.each(function(i){
					var product = $check_items.eq(i).parents('li:first').data('product');
					if(product.disabled || !product.checked){
						return true;
					}

					form += '<input type="hidden" name="products['+i+'][id]" value="'+product.product_id+'">';
					form += '<input type="hidden" name="products['+i+'][num]" value="'+product.num+'">';
				});

				form += '</form>';
				$(form).submit();
			});
		},
		remove: function(li){
			li.style.display = 'none';
			var cart_id = li.getAttribute('data-id');
			$.ajax({
				url: '/h5/cart/delete',
				type: 'post',
				dataType: 'json',
				data: {id: cart_id},
				success: function(){
					if(li.parentElement.childElementCount == 1){
						$(li).parents('.js-cart-group:first').remove();
					}else{
						li.remove();
					}

					cart.checked();
				},
				error: function(){
					li.style.display = '';
				}
			});
		},
		checked: function(checked){
			var checked_num = 0;
			var disabled_num = 0;
			var total_num = this.$cart_contianer.find('li').length;
			var total_price = 0;
			var total_score = 0;

			var $check_items = this.$cart_contianer.find('.check');
			$check_items.each(function(i){
				if($check_items.eq(i).hasClass('disabled')){
					disabled_num++;
				}else if(true === checked){
					$check_items.eq(i).addClass('checked');
				}else if(false === checked){
					$check_items.eq(i).removeClass('checked');
				}

				var product = $check_items.eq(i).parents('li:first').data('product');
				if($check_items.eq(i).hasClass('checked')){
					product.checked = 1;
					checked_num++;

					switch(product.pay_type){
						case 2:
							total_score += product.score * product.num;
							break;
						case 3:
							total_price += parseFloat((product.price * product.num).toFixed(2));
							total_score += product.score * product.num;
							break;
						case 1:
						default:
							total_price += parseFloat((product.price * product.num).toFixed(2));
							break;
					}
				}else{
					product.checked = 0;
				}
			});

			if(checked_num > 0 && checked_num + disabled_num == total_num){
				this.$select_all.addClass('checked');
				this.$select_all.find('.check').addClass('checked');
			}else{
				this.$select_all.removeClass('checked');
				this.$select_all.find('.check').removeClass('checked');
			}

			if(checked_num > 0){
				if(total_price > 0 && total_score > 0){
					this.$total_price.html('合计：¥' + total_price + ' + ' + total_score + '积分');
				}else if(total_score > 0){
					this.$total_price.html('合计：' + total_score + '积分');
				}else{
					this.$total_price.html('合计：' + total_price.toFixed(2) + '元');
				}

				this.$btn_pay.removeAttr('disabled');
			}else{
				this.$total_price.html('合计：0元');
				this.$btn_pay.attr('disabled', 'disabled');
			}
			this.$btn_pay.html('结算('+checked_num+')');
		},
		buyNum: function(quantity, add_num, save){
			var $quantity = $(quantity);
			var $input = $quantity.find('.txt');
			var old_val = parseInt($input.val());
			var target = old_val + add_num;

			if(target < 1){
				target = 1;
			}

			var $li = $quantity.parents('li:first');
			var product = $li.data('product');

			if(target > product.quota){
				target = product.quota;
			}

			if(target > product.stock){
				target = product.stock;
			}

			product.num = target;
			$input.val(target);

			var $minus = $quantity.find('.minus'),
				$plus = $quantity.find('.plus');
			if(target == 1){
				$minus.attr('disabled', 'disabled');
				$minus.siblings('.response-area-minus').addClass('disabled');
			}else{
				$minus.removeAttr('disabled');
				$minus.siblings('.response-area-minus').removeClass('disabled');
			}

			if(target == product.quota){
				$plus.attr('disabled', 'disabled');
				$plus.siblings('.response-area-plus').addClass('disabled');
			}else{
				$plus.removeAttr('disabled');
				$plus.siblings('.response-area-plus').removeClass('disabled');
			}

			$quantity.prev().html('x' + target);

			if(save){
				$.ajax({
					url: '/h5/cart/update',
					type: 'post',
					dataType: 'json',
					data: {id: product.id, num: target}
				});
			}
			return;
		}
	};

	return cart;
})
,define('pay', function(){
	var pay = {
		callpay: function(parameters, callback, type){
			if(!type){
				type = isApp ? 'open' : 'mp';
			}

			if(type == 'mp'){
				pay.wxMpPay(parameters, callback);
			}else if(type == 'open'){
				pay.wxOpenPay(parameters, callback);
			}else{
				alert('未知支付类型');
			}
		},
		wxMpPay: function(parameters, callback){	// 微信公众号支付
			var jsApiCall = function(){
				WeixinJSBridge.invoke('getBrandWCPayRequest', parameters, function(res){
					if(res.err_msg == 'get_brand_wcpay_request:ok'){
						callback({errcode: 0, errmsg: res.err_msg});
					}else{
						callback({errcode: 1, errmsg: res.err_msg});
					}
				});
			}

			if (typeof WeixinJSBridge == "undefined"){
				if( document.addEventListener ){
					document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
				}else if (document.attachEvent){
					document.attachEvent('WeixinJSBridgeReady', jsApiCall);
					document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
				}
			}else{
				jsApiCall();
			}
		},
		wxOpenPay: function(parameters, callback){	// app微信支付
			uexWeiXin.cbStartPay = function(data){
				var result = JSON.parse(data);
				callback({errcode: result.errCode, errmsg: result.errStr});
			}

			var data = JSON.stringify(parameters);
			uexWeiXin.startPay(data);
		},
	};
	return pay;
})
,define("canvasUtils",function(t,a,e){var o=function(){var t=function(t,a,e,o,s,n,r,i){"use strict";"string"==typeof a&&(a=parseFloat(a)),"string"==typeof e&&(e=parseFloat(e)),"string"==typeof o&&(o=parseFloat(o)),"string"==typeof s&&(s=parseFloat(s)),"string"==typeof n&&(n=parseFloat(n)),"string"==typeof r&&(r=parseFloat(r));2*Math.PI;switch(t.save(),t.beginPath(),t.moveTo(a,e),t.lineTo(o,s),t.lineTo(n,r),i){case 0:var h=Math.sqrt((n-a)*(n-a)+(r-e)*(r-e));t.arcTo(o,s,a,e,.55*h),t.fill();break;case 1:t.beginPath(),t.moveTo(a,e),t.lineTo(o,s),t.lineTo(n,r),t.lineTo(a,e),t.fill();break;case 2:t.stroke();break;case 3:var f=(a+o+n)/3,M=(e+s+r)/3;t.quadraticCurveTo(f,M,a,e),t.fill();break;case 4:var c,p,l,d,h,u=5;if(n==a)h=r-e,c=(o+a)/2,l=(o+a)/2,p=s+h/u,d=s-h/u;else{h=Math.sqrt((n-a)*(n-a)+(r-e)*(r-e));var y=(a+n)/2,v=(e+r)/2,g=(y+o)/2,T=(v+s)/2,b=(r-e)/(n-a),F=h/(2*Math.sqrt(b*b+1))/u,P=b*F;c=g-F,p=T-P,l=g+F,d=T+P}t.bezierCurveTo(c,p,l,d,a,e),t.fill()}t.restore()},a=function(t,a,o,s,n,r,i,h,f,M,c,p,l){"use strict";h="undefined"!=typeof h?h:3,f="undefined"!=typeof f?f:1,M="undefined"!=typeof M?M:Math.PI/8,c="undefined"!=typeof c?c:10,p="undefined"!=typeof p?p:1,t.save(),t.lineWidth=p,t.beginPath(),t.arc(a,o,s,n,r,i),t.stroke();var d,u,y,v,g;if(1&f&&(d=Math.cos(n)*s+a,u=Math.sin(n)*s+o,y=Math.atan2(a-d,u-o),i?(v=d+10*Math.cos(y),g=u+10*Math.sin(y)):(v=d-10*Math.cos(y),g=u-10*Math.sin(y)),e(t,d,u,v,g,h,2,M,c)),2&f){d=Math.cos(r)*s+a,u=Math.sin(r)*s+o,y=Math.atan2(a-d,u-o),i?(v=d-10*Math.cos(y),g=u-10*Math.sin(y)):(v=d+10*Math.cos(y),g=u+10*Math.sin(y)),e(t,d-l*Math.sin(r),u+l*Math.cos(r),v-l*Math.sin(r),g+l*Math.cos(r),h,2,M,c)}t.restore()},e=function(a,e,o,s,n,r,i,h,f){"use strict";"string"==typeof e&&(e=parseFloat(e)),"string"==typeof o&&(o=parseFloat(o)),"string"==typeof s&&(s=parseFloat(s)),"string"==typeof n&&(n=parseFloat(n)),r="undefined"!=typeof r?r:3,i="undefined"!=typeof i?i:1,h="undefined"!=typeof h?h:Math.PI/8,f="undefined"!=typeof f?f:10;var M,c,p,l,d="function"!=typeof r?t:r,u=Math.sqrt((s-e)*(s-e)+(n-o)*(n-o)),y=(u-f/3)/u;1&i?(M=Math.round(e+(s-e)*y),c=Math.round(o+(n-o)*y)):(M=s,c=n),2&i?(p=e+(s-e)*(1-y),l=o+(n-o)*(1-y)):(p=e,l=o),a.beginPath(),a.moveTo(p,l),a.lineTo(M,c),a.stroke();var v=Math.atan2(n-o,s-e),g=Math.abs(f/Math.cos(h));if(1&i){var T=v+Math.PI+h,b=s+Math.cos(T)*g,F=n+Math.sin(T)*g,P=v+Math.PI-h,k=s+Math.cos(P)*g,m=n+Math.sin(P)*g;d(a,b,F,s,n,k,m,r)}if(2&i){var T=v+h,b=e+Math.cos(T)*g,F=o+Math.sin(T)*g,P=v-h,k=e+Math.cos(P)*g,m=o+Math.sin(P)*g;d(a,b,F,e,o,k,m,r)}};return{drawArrow:e,drawArcedArrow:a}}();e.exports=o})
,define("touchPull",function(t,i,s){!function(t,i){var s=i.document,n={NONE:0,NOOP:1,UP:2,RIGHT:3,DOWN:4,LEFT:5,LEFT_RIGHT:6},o={con:"",minDistance:4,onPullStart:function(){},onMove:function(){},onPullEnd:function(){}},h=function(i){"string"==typeof i.con&&(i.con=s.querySelector(i.con)),this.options=t.extend({},o,i),this.hasTouch=!1,this.direction=n.NONE,this.distanceX=this.startY=this.startX=0,this.isPull=!1,this.initEvent()};h.prototype={initEvent:function(){var t=this;this._touchStart=function(i){t.__start(i)},this._touchMove=function(i){t.__move(i)},this._touchEnd=function(i){t.__end(i)},this.options.con.addEventListener("touchstart",this._touchStart,!1),this.options.con.addEventListener("touchmove",this._touchMove,!1),this.options.con.addEventListener("touchend",this._touchEnd,!1)},detachEvent:function(){this.options.con.removeEventListener("touchstart",this._touchStart,!1),this.options.con.removeEventListener("touchmove",this._touchMove,!1),this.options.con.removeEventListener("touchend",this._touchEnd,!1)},__start:function(t){t=t.targetTouches,1===t.length&&(this.startX=t[0].pageX,this.startY=t[0].pageY,this.direction=n.NONE,this.distanceX=0,this.hasTouch=!0,this.startScrollY=i.scrollY)},__move:function(t){if(this.hasTouch){if(this.direction===n.UP)return;var i=t.targetTouches[0];if(this.direction===n.NONE){this.distanceX=i.pageX-this.startX,this.distanceY=i.pageY-this.startY;var s=Math.abs(this.distanceY),o=Math.abs(this.distanceX);o+s>this.options.minDistance&&(this.direction=o>1.73*s?n.LEFT_RIGHT:s>1.73*o?this.distanceY<0?n.UP:n.DOWN:n.NOOP,this.startScrollY<10&&this.distanceY>0&&(this.direction=n.DOWN)),this.startScrollY<10&&this.direction===n.DOWN&&this.distanceY>this.options.minDistance&&(this.isPull=!0,this.options.onPullStart(t,this.distanceY))}this.isPull&&this.direction===n.DOWN&&(this.distanceY=i.pageY-this.startY,this.refreshY=parseInt(this.distanceY*this.options.pullRatio),this.options.onMove(t,this.distanceY))}},__end:function(t){!this.hasTouch||n.LEFT_RIGHT!==this.direction&&n.DOWN!==this.direction||(this.direction===n.LEFT_RIGHT&&(t.preventDefault(),this.options.onPullEnd(t,this.distanceX,n.LEFT_RIGHT)),this.direction===n.DOWN&&this.isPull&&(t.preventDefault(),this.options.onPullEnd(t,this.distanceY,n.DOWN))),this.hasTouch=!1,this.isPull=!1}},i.TouchPull={init:function(t){return new h(t)},DIRECTION:n}}(window.jQuery||window.Zepto,window),s.exports=TouchPull})
,define("pullrefresh",function(e,t,n){TouchPull=require("touchPull"),CanvasUtils=require("canvasUtils");var i=function(e){return{drawArc:i}}(window.jQuery||window.Zepto,window);!function(e,t){var n=t.document,r=window.requestAnimationFrame||window.webkitRequestAnimationFrame||window.mozRequestAnimationFrame||window.oRequestAnimationFrame||window.msRequestAnimationFrame||function(e){window.setTimeout(e,1e3/60)},s={con:"",minDistance:4},o=["onPullStart","onMove","onRelease","needRefresh","doRefresh","noop"],a=30,l=10,u=300,c=100,h=10,p=function(e){var t=5*e/12;return t},f=function(){var e=document.createElement("canvas"),t=!(!e.getContext||!e.getContext("2d")),n=navigator.userAgent.toLowerCase(),i=(n.match(/chrome\/([\d.]+)/),n.match(/version\/([\d.]+).*safari/)),r=(n.match(/firefox\/([\d.]+)/),n.match(/mx[\d.]+/)),s=!1;return r&&i&&(s=!0),!t&&s}(),d=function(){return!0}(),v=function(i){"string"==typeof i.con&&(i.con=n.querySelector(i.con));var r={},a=this;e.each(o,function(e,t){r[t]=a["_"+t].bind(a)}),i.doRefresh=function(){return a._load(1)},this.options=e.extend({},s,r,i),this.shouldRefresh=!1,this.isRefreshing=!1,this.$pullTip=null,r.onPullEnd=this._onPullEnd.bind(this),i=e.extend({},r,i),this.touchPull=t.TouchPull.init(i),this.refreshTimes=0,this.isLoading=!1,this.$pullUpTip=$('<div class="pull-up"><div class="loader"><span></span><span></span><span></span><span></span></div><div class="pullUpLabel"></div></div>');$(this.options.con).append(this.$pullUpTip);var s=this;window.addEventListener('scroll',function(){var distance=document.body.scrollHeight-document.body.scrollTop;s._onScroll(distance)},false)};v.prototype={_onScroll:function(distance){if(this.isRefreshing||this.isLoading)return false;if(distance<=window.innerHeight+30){this.isLoading=true;this.$pullUpTip.addClass('pull-loading');this._load(1)}},_doRefresh:function(){alert();return $.Deferred()},_load: function(page){var ov=$.Deferred(),ot=this,ajax=typeof ot.options.ajax == 'function' ? ot.options.ajax() : ot.options.ajax;ajax=$.extend({url: '', dataType: 'json', data: {}}, ajax);ajax.success=function(rows){var tpl=typeof ot.options.template == 'function' ? ot.options.template() : ot.options.template;alert(tpl);ot._hasMore(false);},ajax.error=function(){},ajax.complete=function(){ov.resolve()};$.ajax(ajax);return ov;},_hasMore: function(more){alert('has_more:'+more);},_onPullStart: function(e) {this.isRefreshing || (e.preventDefault(), this.addPullTip(this.options.con))},_onMove: function(e, t) {if (!this.isRefreshing) {e.preventDefault();var n=p(t);n=this.isRefreshing ? n + this.minRefreshDistance: n,this.movePullTip(n),this.changePullTip(n, this.options.con)}},_onPullEnd: function(e, n) {if (!this.isRefreshing) {var i=this;this.options.needRefresh(n),this.options.onRelease().then(function() {"function" == typeof gaevent && t.gaevent("refresh", "drag_refresh_new"),i.options.needRefresh() ? ("function" == typeof gaevent && t.gaevent("refresh", "drag_refresh_OK_new"), t._vis_opt_queue=t._vis_opt_queue || [], t._vis_opt_queue.push(function() {_vis_opt_goal_conversion(13359)}), i.isRefreshing=!0, i.refreshTimes += 1, i.options.doRefresh().always(function() {i.reset()})) : (i.reset(), i.options.noop())})}},transitionDefer: null,onTransitionEnd: function() {var e=this;e.shouldRefresh ? e.canvasObj.startAuto() : e.reset(),setTimeout(function() {e.transitionDefer.resolve()},!1)},_onRelease: function() {if (this.transitionDefer=e.Deferred(), this.pullTipExist()) {var t=this.$pullTip[0];t.addEventListener("webkitTransitionEnd", this.onTransitionEnd.bind(this), !1);var n=this.shouldRefresh ? this.minRefreshDistance: 0,i=!0;this.movePullTip(n, "all " + u + "ms linear", i)} else this.transitionDefer.resolve();return this.transitionDefer},_doRefresh: function() {var t=e.Deferred();return t.resolve(),t},_noop: function() {},_needRefresh: function(e) {return e=p(e),!this.shouldRefresh && e >= this.minRefreshDistance && (this.shouldRefresh=!0),this.shouldRefresh},pullTipExist: function() {return this.$pullTip && this.$pullTip[0]},reset: function() {var e=this.isRefreshing;this.isRefreshing=!1,this.shouldRefresh=!1,this.removePullTip(e)},canvasObj: function() {function n() {var e=(W + 1) % q.length;return W=e,e}function s(e) {return 360 + e - I}function o() {k || b.clearRect(0, 0, 2 * y, 2 * _)}function l(e) {if (!f) {var t=e.start,n=e.end,i=e.lineWidth,r=e.color,s=e.counterClockwise,a=e.co,l=e.clearRect;l && o(),b.save(),b.globalCompositeOperation=a,b.beginPath(),b.arc(y, _, j, R(t), R(n), s),b.lineWidth=i,b.strokeStyle=r,b.stroke(),b.restore()}}function c() {if (!f) {var e=B.speed,t=B.startAngle,i=z,r=B.color,o=B.lineWidth,a=B.counterClockwise,u=B.globalCompositeOperation,c=X || +new Date;i=+new Date,e=360 / S * (i - c),X=i,z += e,i=Math.min(Q, z);var h="draw" === H;if (!k && (l({start: t,end: i,color: r,lineWidth: o,counterClockwise: a,co: u,clearRect: h}), z >= Q)) if (b.closePath(), B="erase" !== H ? U: F, H="erase" !== H ? "erase": "draw", "draw" === H) {L=B.color;var p=n(L);B.color=q[p],B.startAngle=(B.startAngle - I) % 360,z=B.startAngle,Q=s(z)} else z=B.startAngle=F.startAngle}}function p(e) {if (!f) {var t=F.speed,n=F.startAngle,i=F.startAngle,r=q[0];if (!isNaN(e)) {e=Math.min(D.minRefreshDistance - a, e);var s=e / (D.minRefreshDistance - a),o=(Q - h) * s - F.startAngle;t=o}i += t,N=i,v({start: n,end: i,color: r,distance: e})}}function d() {var t=D.minRefreshDistance - a,n=t / S * 1.3,i=q[0],s=t,o=+new Date,l=e.Deferred(),u=function() {if (s >= 0) {var e=+new Date;s -= n * (e - o),o=e;var t=s / (D.minRefreshDistance - a),c=(Q - h) * t - F.startAngle,p=N - c;p=Math.min(p, N),v({start: p,end: N,color: i,distance: s}),r(u)} else l.resolve()};return r(u),l}function v(n) {var r=n.distance,s=k ? 10 : 25,l=O,u=r / (D.minRefreshDistance - a);isNaN(r) || (s *= u, l=O * u),o(),k ? i.drawArc({x: y,y: _,radius: j,margin: P,startDegree: n.start,endDegree: n.end,arrowSize: s,arrowObj: e(A).find("#markerArrow"),pathObj: e(A).find("#svgPath"),color: n.color}) : (b.strokeStyle=n.color, b.fillStyle=n.color, t.CanvasUtils.drawArcedArrow(b, y, _, j, R(n.start), R(n.end), !1, 1, 2, R(45), s, O, l))}function w(e) {var t=0;if (e) {e=e.replace("matrix(", "").replace(")", ""),e=e.replace(/\s+/gi, "");var n=e.split(",");t=n[5] || 0}return t}function m() {var e=w(D.$pullTip.css("transform"));if (! (a > e)) {var t=u,n=e / t,i=e,s=+new Date,o=function() {if (i > a && D.$pullTip) {var e=+new Date,t=n * (e - s);i -= t,T(i - a),p(i - a),g(i - a),s=e,r(o)}};r(o)}}function g(t) {var n=1 * t / (D.minRefreshDistance - a);e(A).css("opacity", n)}function T(e, t) {var n=e;t || (n=Math.max(0, (e - a) / D.minRefreshDistance * 360)),A.style.webkitTransition="none",A.style.webkitTransform="rotate(" + n + "deg)"}function R(e) {return e * (Math.PI / 180)}function x(e) {clearTimeout(Y),e=e || 8e3,Y=setTimeout(function() {D.reset()},e)}var D=null,A=null,b=null,k=!1,y=100,_=100,j=50,P=0,O=15,C=!1,E=5,$=0,M=1500,S=1e3,q=["green", "red", "blue", "#f3b000"],L=q[0],W=1,F={startAngle: $,speed: E,color: q[0],counterClockwise: !1,globalCompositeOperation: "source-out",lineWidth: O},U={startAngle: $,speed: E,color: "white",counterClockwise: !1,globalCompositeOperation: "destination-out",lineWidth: O + 40},z=$,N=$,B=F,H="draw",I=50,Q=0,X=0,Y=-1;return {init: function(e, t) {this.reset(),X=0,C=!1,A=e.find("#load-tip-svg")[0] || e.find("#load-tip-canvas")[0],b=A.getContext ? A.getContext("2d") : A,k=A.getContext ? !1 : !0,N=z=$,F.startAngle=U.startAngle=$,Q=s(z),W=1,F.color=q[W],H="draw",B=F,D=t,k ? (P=9, y=_=j=(40 - 2 * P) / 2) : (y=_=100, P=0, j=50)},reset: function() {A=null,b=null},drawArrowedArcByDis: function(e) {p(e)},drawArc: function(e) {f ? console.log("not support") : c(e)},clearCurrent: function() {f ? console.log("not support") : m()},rotate: T,changeOpacity: g,autoRotate: function() {var e=A.style.webkitTransform;e=e.replace("rotate(", "").replace("deg", "").replace(")", "");var t=parseFloat(e),n=360 / M,i=this,s=+new Date,o=function() {if (C) {var e=+new Date,a=t + n * (e - s);s=e,i.rotate(a, !0),t=a,r(o)}};r(o)},autoDraw: function() {if (!f) {var t=function() {C && (k ? (C=!1, e(b).attr("class", "spinner")) : (c(), r(t)))},n=d();n.done(function() {r(t)})}},startAuto: function() {C=!0,D.touchPull.detachEvent(),this.autoDraw(),this.autoRotate(),x()},stopAuto: function() {C=!1,D.touchPull.initEvent(),clearTimeout(Y)}}} (),initCanvas: function() {this.canvasObj.init(this.$pullTip, this)},addPullTip: function(t) {this.removePullTip(),t=this.options.con;var n=this.$pullTip;if(!n){var i=[];if (i.push("<div class='list_top'>"), i.push("<div class='list_top_con v2'>"), d) i.push("<canvas  id='load-tip-canvas'  width='200'  height='200'  class='" + (f ? "not-support": "") + "'></canvas>");else{var r=5,s=20,o=11,a=10,u=70,c=3.5,h=["M0,0", "L0," + a, "L" + r + "," + r, "L0,0"].join(" ");i.push('<svg id="load-tip-svg" class=""><marker id="markerArrow" markerWidth="' + a + '" markerHeight="' + a + '" refX="0" refY="' + r + '"  orient="auto" markerUnits="userSpaceOnUse"><path d="' + h + '" style="fill: #660000;" />  </marker>  <path stroke-width="' + c + '" stroke-linecap="round" id="svgPath" marker-end="url(#markerArrow)" d="M125,25 A100,100 0 0,1 125,25"  style="stroke:#660000; fill:none;"/><circle style="stroke-dasharray:' + u + ';" id="svgCircle" class="path" fill="none" stroke-width="' + c + '" stroke-linecap="round" cx="' + s + '" cy="' + s + '" r="' + o + '"></circle>  </svg>')}i.push("</div></div>"),this.$pullTip=e(i.join("")).insertAfter("body"),n=this.$pullTip,this.minRefreshDistance=n.outerHeight();var p=n[0];p.style.webkitTransition="none",p.style.webkitTransform="translate3d(0," + l + "px,0)",p.style.top=t.getBoundingClientRect().top - this.minRefreshDistance + "px",this.initCanvas();}},movePullTip: function(e, t, n) {if (this.pullTipExist()) {var i=Math.min(c, e);this.$pullTip[0].style.webkitTransition=t || "none",this.$pullTip[0].style.webkitTransform="translate3d(0," + i + "px,0)",0 === e ? this.canvasObj.clearCurrent() : e > a && (this.shouldRefresh ? this.isRefreshing || n === !0 || this.canvasObj.rotate(e) : (c - 5 >= e && this.canvasObj.rotate(e), this.canvasObj.drawArrowedArcByDis(e - a), this.canvasObj.changeOpacity(e - a)))}},changePullTip: function() {this.pullTipExist()},removePullTip: function(t){if(this.pullTipExist()) if (t) {var n=this;n.canvasObj.stopAuto(),n.$pullTip[0].style.webkitTransition="all 100ms linear",n.$pullTip.css("opacity", .1),n.$pullTip[0].style.webkitTransform += " scale(0.1)"}else this.$pullTip[0].removeEventListener("webkitTransitionEnd", this.onTransitionEnd, !1),this.$pullTip.remove(),this.$pullTip=null,e(window).trigger("pullrefresh_pulltip_removed")}},t.PullRefresh={init: function(e) {return new v(e)}};}(window.jQuery || window.Zepto, window),n.exports=window.PullRefresh;})
,define("util/draw",["jquery"],function(e){var t,n;return t=function(e){function t(e){return Math.PI/180*e}function n(e,t){var n=[0,0],r=["M"+n.join(","),"L"+[n[0],n[1]+e].join(","),"L"+[n[0]+e/2,n[1]+e/2].join(","),"L"+n.join(",")],i=n[1]+e/2;t[0].setAttribute('refX',n[0]),t[0].setAttribute('refY',i),t[0].setAttribute('markerWidth',2*i),t[0].setAttribute('markerHeight',2*i),t.find("path").attr({d:r.join(" ")})}function r(r){r=e.extend({radius:0,margin:0,sAngle:0,eAngle:0,arrowSize:2.5,arrowObj:"#x-pushfresh-arrow",pathObj:".x-pullfresh-path"},r);var i=r.radius+r.margin+r.radius*Math.sin(t(r.eAngle)),s=r.radius+r.margin-r.radius*Math.cos(t(r.eAngle)),o=r.radius+r.margin+r.radius*Math.sin(t(r.sAngle)),u=r.radius+r.margin-r.radius*Math.cos(t(r.sAngle)),a=[["M"+o,u].join(",")];a.push([["A"+r.radius,r.radius].join(","),0,[r.eAngle-r.sAngle>180?1:0,1].join(","),[i,s].join(",")].join(" ")),e(r.pathObj).attr("d",a.join(" ")),n(r.arrowSize,e(r.arrowObj))}return{drawArc:r}}(e),n=function(e){function t(e,t,n,r,i,s,o,u,a){typeof t=="string"&&(t=parseInt(t,10)),typeof n=="string"&&(n=parseInt(n,10)),typeof r=="string"&&(r=parseInt(r,10)),typeof i=="string"&&(i=parseInt(i,10)),s=s!==undefined?s:console.log,o=o!==undefined?o:1,u=u!==undefined?u:Math.PI/8,a=a!==undefined?a:10;var f=typeof s!="function"?console.log:s,l,c,h,p,d,v,m,g;l=Math.atan2(i-n,r-t),c=Math.abs(a/Math.cos(u)),o&1&&(h=l+Math.PI+u,d=r+Math.cos(h)*c,v=i+Math.sin(h)*c,p=l+Math.PI-u,m=r+Math.cos(p)*c,g=i+Math.sin(p)*c,f(e,d,v,r,i,m,g,s)),o&2&&(h=l+u,d=t+Math.cos(h)*c,v=n+Math.sin(h)*c,p=l-u,m=t+Math.cos(p)*c,g=n+Math.sin(p)*c,f(e,d,v,t,n,m,g,s))}function n(e,n,r,i,s,o,u,a,f,l,c){a=a!==undefined?a:console.log,f=f!==undefined?f:1,l=l!==undefined?l:Math.PI/8,c=c!==undefined?c:10;var h,p,d,v,m,g=typeof a!="function"?console.log:a;g(e,n,r,i,s,o,u),f&1&&(h=Math.cos(s)*i+n,p=Math.sin(s)*i+r,d=Math.atan2(n-h,p-r),u?(v=h+10*Math.cos(d),m=p+10*Math.sin(d)):(v=h-10*Math.cos(d),m=p-10*Math.sin(d)),t(e,h,p,v,m,a,2,l,c)),f&2&&(h=Math.cos(o)*i+n,p=Math.sin(o)*i+r,d=Math.atan2(n-h,p-r),u?(v=h-10*Math.cos(d),m=p-10*Math.sin(d)):(v=h+10*Math.cos(d),m=p+10*Math.sin(d)),t(e,h,p,v,m,a,2,l,c))}return{drawArrow:t,drawArcedArrow:n}}(e),{SVGUtil:t,CanvasUtil:n}})
,define("util/pullfresh",["jquery","util/draw"],function(e,t){return{init:function(t){var n=this;return n.options=e.extend(!0,{wrapper:".x-pullfresh-wrapper",canvas:".x-pullfresh-canvas",svg:".x-pullfresh-svg",loadingClass:"x-on",circle:{originX:16,originY:16,radius:12,canvasStyle:{lineCap:"round",strokeStyle:"#34be33",lineWidth:1},svgStyle:{"stroke-linecap":"round",stroke:"#34be33",fill:"none","stroke-width":1}},arrow:{angle:90,lineLength:3,canvasStyle:{strokeStyle:"#34be33",lineWidth:1},svgStyle:{fill:"#34be33"}},moveOffset:50,moveRate:2.5},t||{}),n.wrapper=e(n.options.wrapper),n.canvas=e(n.options.canvas),n.svg=e(n.options.svg),!n.svg.length||!document.createElementNS||!document.createElementNS("http://www.w3.org/2000/svg","svg").createSVGRect?(n.svg=[],n.loading=n.canvas):(n.svg.find(".x-pullfresh-path").css(n.options.circle.svgStyle),n.svg.find("#x-pullfresh-arrow").find("path").css(n.options.arrow.svgStyle),n.loading=n.svg),n.beginPos=0,n.currPos=0,n.endEvents="webkitTransitionEnd transitionend",n},drawRotate:function(e,t){var n=this,r=0,i=e*360;n.svg.length?n.drawRotateSVG(r,i,t):n.canvas.length&&n.drawRotateCanvas(r,i,t)},drawRotateCanvas:function(e,n,r){var i=this,s=i.options,o,u;i.canvas.show().css({opacity:r}),o=i.canvas.get(0),u=o.getContext("2d"),u.clearRect(0,0,o.width,o.height),e=Math.PI/180*(e-50),n=Math.PI/180*(n-50),t.CanvasUtil.drawArcedArrow(u,s.circle.originX,s.circle.originY,s.circle.radius,e,n,!1,function(e,t,n,r,i,o,u,a){var f;if(!a){e.beginPath(),e.arc(t,n,r,i,o,u);for(f in s.circle.canvasStyle)s.circle.canvasStyle.hasOwnProperty(f)&&(e[f]=s.circle.canvasStyle[f]);e.stroke(),e.closePath();return}e.beginPath();for(f in s.arrow.canvasStyle)s.arrow.canvasStyle.hasOwnProperty(f)&&(e[f]=s.arrow.canvasStyle[f]);e.moveTo(t,n),e.lineTo(r,i),e.lineTo(o,u),e.stroke(),e.closePath()},2,Math.PI/(360/s.arrow.angle),s.arrow.lineLength*r)},drawRotateSVG:function(e,n,r){var i=this,s=i.options;i.svg.show().css({opacity:r}),t.SVGUtil.drawArc({margin:8,radius:s.circle.radius,sAngle:e+35,eAngle:n+35,arrowSize:s.arrow.lineLength*r*2.5,arrowObj:i.svg.find("#x-pullfresh-arrow"),pathObj:i.svg.find(".x-pullfresh-path")})},onTouchStart:function(e){var t=this;t.loading.removeClass(t.options.loadingClass),t.beginPos=e.touches[0].pageY,t.isFull=!1,t.currPos=0},onTouchMove:function(e){var t=this,n=e.touches[0].pageY-t.beginPos,r;t.isFull=!1;if(window.scrollY===0&&n>5){t.currPos=n*.2+n*((t.options.moveOffset-t.currPos)/t.options.moveOffset)/10,r=Math.floor(t.currPos*1e3/t.options.moveOffset)/1e3;if(r>1||r<0)r=1,t.isFull=!0;r>=.9&&(t.isFull=!0),t.drawRotate(r>.9?.9:r,r),n=t.options.moveOffset*t.options.moveRate*r,t.wrapper.css({"-webkit-transform":"translate3d(0,"+n+"px,0)",transform:"translate3d(0,"+n+"px,0)",display:'block',opacity:1})}else{t.wrapper.css('display','none')}},onTouchEnd:function(e){return this.isFull?!0:(this.loadEnd(),!1)},cbkOnce:function(e){var t=this;t.oneEvtType||(t.oneEvtType=e);if(t.oneEvtType===e)return!0},showLoading:function(){var e=this,t=1,n;e.drawRotate(t>.9?.9:t,t),n=e.options.moveOffset*e.options.moveRate/4*3,e.wrapper.css({opacity:1,display:'',"-webkit-transform":"translate3d(0,"+n+"px,0)",transform:"translate3d(0,"+n+"px,0)"}),e.loading.addClass(e.options.loadingClass)},loadStart:function(e){this.showLoading();e&&e()},loadEnd:function(e){var t=this;t.wrapper.css({"-webkit-transition":"all 0.6s ease-in-out",transition:"translate3d(0px, 0px, 0px)","-webkit-transform":"translate3d(0px, 0px, 0px)",transform:"translate3d(0px, 0px, 0px)"}),t.currPos=0,t.beginPos=0,t.loading.removeClass(t.options.loadingClass),setTimeout(function(){t.wrapper.css({"-webkit-transition":"",transition:"","-webkit-transform":"",transform:""});t.drawRotate(0,0)},600)}}})
,define("templates/pullfresh-view.html", [], function() {return '<div id="pullfresh-wrapper" class="x-pullfresh-wrapper"><div class="x-pullfresh-loading"><canvas class="x-pullfresh-canvas" width="32" height="32" style="display:none"></canvas><svg class="x-pullfresh-svg" style="display:none"><marker id="x-pullfresh-arrow" orient="auto" markerUnits="userSpaceOnUse"><path/></marker><path class="x-pullfresh-path" marker-end="url(#x-pullfresh-arrow)"/></svg></div></div>'})
,define("templates/pullfresh-loading-view.html", [], function() {return '<div class="pullfresh-up"><div class="loader"><span></span><span></span><span></span><span></span></div><div class="pullfresh-label">没有更多数据了</div></div>'})
,define("pullfresh", ["jquery", "util/pullfresh", "templates/pullfresh-view.html", "templates/pullfresh-loading-view.html"],function(e, a, d, u){
	$('body').append(d);
	var pullFresh = {
		pullfresh: null,
		page: 0,
		init: function(options){
			var t = this;
			t.pullfresh = a.init();
			t.disable();
			if(options.height && !isNaN(options.height)){
				options.size = Math.ceil(window.innerHeight / options.height) * 2;
				if(options.size < 10){
					options.size = 10;
				}
			}
			t.options = $.extend({size: 20, height: 0, refresh: false, autoLoad: true, container: false}, options);
			t.$container = $(options.container);
			t.$pu = t.$container.nextAll('.pullfresh-up');
			if(t.$pu.length == 0){
				t.$container.after(u);
				t.$pu = t.$container.next();
			}
			if(typeof t.options.onLoad == 'function'){
				t.onLoad = t.options.onLoad;
			}
			if(typeof t.options.onBeforeLoad == 'function'){
				t.onBeforeLoad = t.options.onBeforeLoad;
			}
			t.enable();
		},
		handleEvent: function (e) {
			switch ( e.type ) {
			case 'scroll':
				this._scroll(e);
				break;
			case 'touchstart':
				this._touchstart(e);
				break;
			case 'touchmove':
				this._touchmove(e);
				break;
			case 'touchend':
				this._touchend(e);
					break;
			}
		},
		disable: function(){
			var container = document.querySelector('.container');
			this.enabled = false,
			window.removeEventListener('scroll', this, false),
			container.removeEventListener('touchstart', this, false),
			container.removeEventListener('touchmove', this, false),
			container.removeEventListener('touchend', this, false)
		},
		enable: function(){
			var t = this;
			this.isLoading = false,
			this.enabled = true,
			this.enablePullUp(true);

			if(this.options.refresh){
				this.enablePullDown();
			}

			if(this.options.autoLoad && this.page == 0){
				t.doRefresh(false);
			}
		},
		enablePullUp: function(enable){
			window.removeEventListener('scroll', this);
			if(enable){
				window.addEventListener('scroll', this, false);
			}
		},
		enablePullDown: function(){
			var container = document.querySelector('.container');
			container.addEventListener('touchstart', this, false),
			container.addEventListener('touchmove', this, false),
			container.addEventListener('touchend', this, false)
		},
		_touchstart: function(e) {
			if (this.isLoading) return;
			this.pullfresh.onTouchStart(e)
		},
		_touchmove: function(e) {
			if (this.isLoading) return;
			this.pullfresh.onTouchMove(e)
		},
		_touchend: function(e) {
			if (this.isLoading) return;
			if(this.pullfresh.onTouchEnd(e)){
				this.doRefresh(true);
			}
		},
		doRefresh: function(refresh){
			var t = this;
			t.isLoading = true;
			t.deferred = $.Deferred();

			var parameters = {page: 1, size: t.options.size, offset: 0};
			var doLoad = t.onBeforeLoad(parameters, refresh);
			if(false === doLoad){
				return;
			}

			t.pullfresh.loadStart(function() {
				$.when(t.deferred)
				.done(function(){
					t.page = 1;
				}),
				t.onLoad(parameters, refresh);
			});
		},
		deferred: null,
		resolve: function(){
			this.isLoading = false,
			this.pullfresh.loadEnd(),
			this.deferred.resolve();
		},
		fail: function(){
			this.isLoading = false,
			this.pullfresh.loadEnd(),
			this.deferred.reject();
		},
		onRefresh: function(page, offset, size){
			var t = this;
			setTimeout(function(){
				t.resolve();
			}, 2000);
		},
		onPullUp: function(d){
			var t = this;
			setTimeout(function(){
				t.resolve();
			}, 2000);
		},
		onBeforeLoad: function(parameters, isRefresh){
		},
		onLoad: function(parameters){
			var t = this;
			setTimeout(function(){
				t.resolve();
			}, 2000);
		},
		hasMore: false,
		failTimes: 0,
		setNoMore: function(e){
			var t = this;
			t.hasMore = true === e || typeof e == 'string' ? false : true;
			var msg = typeof e == 'string' ? e : '没有更多数据了';
			if(t.$pu){
				t.$pu.removeClass('pullfresh-loading'),
				t.$pu.find('.pullfresh-label').html(msg);
				if(t.hasMore){
					t.$pu.removeClass('no-more')
				}else{
					t.$pu.addClass('no-more')
				}
			}

			t.enablePullUp(false);
			if(t.hasMore){
				t.enablePullUp(true);
			}

			t.resolve();
		},
		scrollTop: 0,
		_scroll: function(e){
			var t = this;
			if(!t.enabled){
				return t.enablePullUp(false)
			}

			var pscroll = t.scrollTop;
			t.scrollTop = document.body.scrollTop;
			if(t.failTimes > 0 || document.body.scrollTop < t.scrollTop
					|| !t.hasMore || t.isLoading
					|| document.body.scrollHeight - document.body.scrollTop > window.innerHeight + 50){
				return;
			}
			t.isLoading = true;

			if(t.$pu){
				t.$pu.removeClass('no-more').addClass('pullfresh-loading');
			}
			t.deferred = $.Deferred();
			$.when(t.deferred)
			.done(function(){
				t.page++;
			})
			.fail(function(){
				t.failTimes = 4;
				var timer = setInterval(function(){
					t.failTimes--;
					if(t.failTimes <= 0){
						window.clearInterval(timer);
					}
				}, 1000);
				if(t.$pu){
					t.$pu.removeClass('pullfresh-loading').addClass('no-more'),
					t.$pu.find('.pullfresh-label').html('加载失败，请稍后重试');
				}
			});

			var parameters = {page: t.page + 1, size: t.options.size, offset: t.page * t.options.size};
			var doLoad = t.onBeforeLoad(parameters, false);
			if(false !== doLoad){
				t.onLoad(parameters, false);
			}
		}
	};
	return pullFresh;
})
// 订单列表
,define('view/order/list', ["pullfresh", "jquery"], function(pullfresh){
	var order_list = {},
	active= 'all';
	var inner_list = function(list){
		if(list.length == 0){
			return '<li><div class="empty-list list-finished"style="padding-top:60px;"><div><h4>居然还没有订单</h4><p class="font-size-12">好东西，手慢无</p></div><div><a href="/h5/mall"class="tag tag-big tag-orange"style="padding:8px 30px;">去逛逛</a></div></div></li>';
		}
		var html = '', trade = null, order = null, url= '';
		for(var i=0; i< list.length; i++){
			trade = list[i];
			order = trade.orders[0];

			if(trade.status == 'topay'){
				url = '/h5/pay/'+trade.tid;
			}else{
				url = '/h5/order/detail?tid='+trade.tid;
			}

			html += '<li class="js-block-order block block-order animated" data-tid="'+trade.tid+'">';
			html += '<div class="header">';
			html += '   <span class="font-size-12">订单号：'+trade.tid+'</span>';
			html += '   <span class="pull-right c-red">'+trade.status_str+'</span>';
			html += '</div>';
			html += '<hr class="margin-0 left-10">';
			html += '<div class="block block-list border-top-0 border-bottom-0">';
			html += '	<a href="'+url+'" class="block-item name-card name-card-3col clearfix">';
			html += '		<div class="thumb">';
			html += '    		<img src="'+order.pic_url+'">';
			html += '		</div>';
			html += '		<div class="detail">';
			html += '			<h3 class="l2-ellipsis">'+order.title+'</h3>';
			html += '			<p class="c-gray ellipsis">'+order.spec+'</p>';
			html += '		</div>';
			html += '		<div class="right-col">';

			if(order.pay_type == 2){
				html += '<div class="price orgin">¥<span>'+order.price+'</span></div>';
				html += '<div class="score">+'+order.score+'</div>';
			}else if(order.pay_type == 3){
				html += '<div class="price">¥<span>'+order.price+'</span></div>';
				html += '<div class="score">+'+order.score+'</div>';
			}else if(order.pay_type == 6){
				html += '<div class="price" style="color:#f60">赠品</div>';
			}else{
				html += '<div class="price">¥<span>'+order.price+'</span></div>';
			}

			html += '           <div class="num">';
			html += '               ×<span class="num-txt">'+order.num+'</span>';
			html += '           </div>';
			html += '        </div>';
			html += '    </a>';
			html += '    <a href="'+url+'" class="center font-size-12 block-item" style="padding: 4px 10px 4px 0;">';
			html += '        共'+trade.kind+'件商品　合计：¥'+trade.payment+'(含运费¥'+trade.post_fee+')';
			html += '    </a>';
			html += '</div>';
			html += '<hr class="margin-0 left-10">';
			html += '<div class="bottom font-size-12">下单时间：'+trade.created;
			html += '   <div class="opt-btn">';
			if(trade.status == 'topay'){
				if(trade.pay_type == 'giveaway'){
					html += '<a class="btn btn-in-order-list js-cancel-order" href="javascript:;">放弃</a>';
					html += '<a class="btn btn-red btn-in-order-list" href="'+url+'">领取</a>';
				}else{
					 html += '<a class="btn btn-in-order-list js-cancel-order" href="javascript:;">取消</a>';
					 html += '<a class="btn btn-red btn-in-order-list" href="'+url+'">付款</a>';
				}
			 }else if(trade.status == 'send'){
				 html += '<a class="btn btn-red btn-in-order-list js-receipt-order">确认收货</a>';
			 }
			html += '    </div>';
			html += '</div>';
			html += '</li>';
		}

		return html;
	}

	$('#nav_order_status>a').on('click', function(){
		var $this = $(this),
		target = $(this).attr('href'),
		$target = $(target),
		active = $this.data('status');

		pullfresh.page = 0;
		pullfresh.init({
			refresh: true,
			height: 153,
			container: $('#order_' + active).children("ul"),
			onLoad: function(parameters){
				$.ajax({
					url: '/h5/order?status='+active+'&offset=' + parameters.offset + '&size='+parameters.size,
					success: function(list){
						var $content = $('#order_'+active+'>.js-list');
						if(parameters.page == 1){
							var html = inner_list(list);
							$content.html(html);
						}else{
							var html = inner_list(list);
							$content.append(html);
						}

						var noMore = list.length < parameters.size;
						if(parameters.page == 1 && list.length == 0){
							noMore = '';
						}
						pullfresh.setNoMore(noMore);
					},
					error: function(){
						pullfresh.fail();
					}
				});
			}
		});

		$this.addClass('active').siblings().removeClass('active'),
		$target.removeClass('hide').siblings().addClass('hide');
		return false;
	});

	$('#nav_order_status>.active').trigger('click');

	$('#order-list-container').on('click', '.js-cancel-order',function(){
		if(!confirm('确定'+this.innerText+'吗？')){
			return false;
		}

		var $li = $(this).parents('.js-block-order:first');
		var tid = $li.data('tid');
		$.ajax({
			url: '/h5/order/cancel',
			type: 'post',
			dataType: 'json',
			data: {tid: tid},
			success: function(){
				$li.find('>.header>.pull-right').html('已取消');
				$li.find('>.bottom>.opt-btn').remove();
			}
		});
		return false;
	}).on('click', '.js-receipt-order',function(){
		if(!confirm('确认收货吗？没有收到物品前请勿此操作')){
			return false;
		}

		var $btn = $(this),
			  $li = $btn.parents('.js-block-order:first');
		var tid = $li.data('tid');
		$.ajax({
			url: '/h5/order/sign',
			dataType: 'json',
			data: {tid: tid},
			type: 'post',
			success: function(){
				$btn.remove();
				$li.find('.header>.pull-right').html('已完成');
			}
		});
		return false;
	});
})
,define('module/cart/num', ['jquery'], function(){
	setTimeout(function(){
		$.ajax({
			url: '/h5/cart/num',
			dataType: 'json',
			success: function(data){
				var $right = $('#right-icon');
				if(data.num > 0){
					if($right.length == 0){
						var html = '<div id="right-icon"class="js-right-icon no-text"><div class="js-right-icon-container right-icon-container clearfix"style="width: 50px;"><a id="global-cart"href="/h5/cart"class="icon new s1"><p class="icon-img"></p><p class="icon-txt">购物车</p></a><a class="js-show-more-btn icon show-more-btn hide new"></a></div></div>';
						$('body').append(html);
					}
				}else if($right.length > 0){
					$right.remove();
				}
			}
		});
	},3000);
}),
define('jsweixin', ['weixinjs'], function(wx){
	var jweixin = {
		init: function(config, ready){
			wx.config({
				debug: false,
				appId: config.appId,
				timestamp: config.timestamp,
				nonceStr: config.nonceStr,
				signature: config.signature,
				jsApiList: ['onMenuShareTimeline','onMenuShareAppMessage','onMenuShareQQ','onMenuShareWeibo','onMenuShareQZone','chooseImage', 'uploadImage']
			});

			wx.ready(ready);
		},
		share: function(shareData, shareResult){
			// 分享到朋友圈
			wx.onMenuShareTimeline({
				title: shareData.title,
				link: shareData.link,
				imgUrl: shareData.imgUrl,
				success: function () {
					shareData.to = 'timeline';
					shareResult.call(shareData);
				}
			});

			// 分享给朋友
			wx.onMenuShareAppMessage({
				title: shareData.title, // 分享标题
				desc: shareData.desc, // 分享描述
				link:  shareData.link, // 分享链接
				imgUrl: shareData.imgUrl, // 分享图标
				type: shareData.type, // 分享类型,music、video或link，不填默认为link
				dataUrl: shareData.dataUrl, // 如果type是music或video，则要提供数据链接，默认为空
				success: function () {
					shareData.to = 'appmessage';
					shareResult.call(shareData);
				}
			});

			// 分享到QQ
			wx.onMenuShareQQ({
				title: shareData.title,
				desc: shareData.desc,
				link: shareData.link,
				imgUrl: shareData.imgUrl,
				success: function () {
					shareData.to = 'qq';
					shareResult.call(shareData);
				}
			});

			// 分享到腾讯微博
			wx.onMenuShareWeibo({
				title: shareData.title,
				desc: shareData.desc,
				link: shareData.link,
				imgUrl: shareData.imgUrl,
				success: function () {
					shareData.to = 'weibo';
					shareResult.call(shareData);
				}
			});

			//分享到QQ空间
			wx.onMenuShareQZone({
				title: shareData.title,
				desc: shareData.desc,
				link: shareData.link,
				imgUrl: shareData.imgUrl,
				success: function () {
					shareData.to = 'qzone';
					shareResult.call(shareData);
				}
			});
		},
		chooseImage: function(uploadSuccess, count){
			count = /^\d+$/.test(count) ? count : 1;
			wx.chooseImage({
	            count: count,
	            sizeType: ['compressed'],
	            sourceType: ['album', 'camera'],
	            success: function (res) {
	                var localIds = res.localIds;
	                for(var i=0; i<localIds.length; i++){
						var localid = localIds[i];
	                	wx.uploadImage({
	        	            localId:  localid,
	        	            isShowProgressTips: 1,
	        	            success: function (res) {
	        	            	uploadSuccess(localid, res);
	        	            },
	        	            fail: function (res) {
	        	                alert('上传图片失败，请重新选择图片');
	        	            }
	        	        });
	                }
	            }
	        });
		}
	}
	return jweixin;
}),
//资金流水记录
define('view/balance', ["pullfresh", "jquery"], function(pullfresh){
	var $btnTransfers = $('.js-btn-transfers'),
		$balance = $('.js-balance'),
		$noBalance = $('.js-no-balance'),
		$totalBalance = $('.js-total-balance'),
		$canBalance = $('.js-can_balance'),
		$inputMoney = $('.js-input-money'),
		$content = $('#balance_list .js-balance-list')
		canTransfers = 0;

	$('.tabber a').on('click', function(){
		var $ele = $(this);
		$ele.addClass('active');
		$ele.siblings('.active').data('scrollTop', document.body.scrollTop);
		$ele.siblings().removeClass('active');
		var target = $ele.attr('href'),
			$target = $(target);
		$target.siblings('.tabber-item').addClass('hide');
		$target.removeClass('hide');

		document.body.scrollTop = $ele.data('scrollTop') || 0;
		return false;
	});

	var appendHTML = function(html, month){
		if(month == ''){
			return;
		}

		var $month = $content.children('[data-month="'+month+'"]');
		if($month.length > 0){
			$month.children('ul').append(html);
			return;
		}

		var html2 = '';
		html2 += '<div data-month="'+month+'">';
		html2 += '<div class="balance-month"><p>'+month.substr(5, 2)+'月<span class="pull-right">'+month.substr(0, 4)+'年</span></p></div>';
		html2 += '<ul class="block block-list">'+html+'</ul></div>';
		$content.append(html2);
	}

	var showData = function(data, isRefresh){
		$balance.html(data.user.balance);
		$noBalance.html(data.user.no_balance);
		$canBalance.html(data.user.can_balance);
		$totalBalance.html(data.user.total_balance);
		$inputMoney.val(data.user.can_balance);
		canTransfers = parseFloat(data.user.can_balance);
		if(data.user.can_balance >= 1){
			$btnTransfers.removeAttr('disabled');
		}else{
			$btnTransfers.attr('disabled', 'disabled');
		}

		if(isRefresh){
			$content.html('');
		}

		var html = '',
			list = data.rows,
			prevMonth = currentMonth = '';

		if(pullfresh.page <= 1 && list.length == 0){
			pullfresh.setNoMore('暂无积分记录');
		}else{
			for(var i=0; i< list.length; i++){
				currentMonth = list[i].create_time.substr(0, 7);
				if(prevMonth != currentMonth){
					if(prevMonth == ''){
						prevMonth = currentMonth;
					}
					appendHTML(html, prevMonth);
					prevMonth = currentMonth;
					html = '';
				}

				html += '<li class="block-item">';
				html += '	<div class="block-left"><div>'+list[i].date+'</div><div>'+list[i].time+'</div></div>';
				html += '	<div class="block-dot" style="background:'+list[i].color+'">'+list[i].short+'</div>';
				html += '	<div class="block-info">';
				html += '		<div class="block-title">'+(list[i].money > 0 ? '+' : '')+list[i].money+'</div>';
				html += '		<div class="block-content">'+list[i].reason+'</div>';
				html += '	</div>';
				html += '</li>';
			}
			appendHTML(html, prevMonth);
			pullfresh.setNoMore(list.length < 20);
		}
	}

	pullfresh.init({
		refresh: true,
		container: $content,
		onLoad: function(parameters, isRefresh){
			$.ajax({
				url: '/h5/balance?offset=' + parameters.offset + '&size='+parameters.size,
				dataType: 'json',
				success: function(data){
					showData(data, isRefresh);
				},
				error: function(){
					pullfresh.fail();
				}
			});
		}
	});

	// 兑换金额改变
	var changeTimer = 0;
	$inputMoney.on('change', function(){
		window.clearTimeout(changeTimer);
		var value = isNaN(this.value) ? 1 : parseFloat(this.value);
		if(value > canTransfers){
			value = canTransfers;
		}
		this.value = value.toFixed(2);
		return false;
	}).on('keyup', function(){
		window.clearTimeout(changeTimer);
		changeTimer = setTimeout(function(){
			$inputMoney.trigger('change');
		}, 1000);
		return false;
	});

	// 立即兑换按钮
	$btnTransfers.on('click', function(){
		var amount = $inputMoney.val();
		$.ajax({
			url: '/h5/balance/transfers',
			type: 'post',
			dataType: 'json',
			data: {amount: amount},
			success: function(){
				pullfresh.doRefresh();
			}
		});
		return false;
	});
})
//编辑个人资料
,define('view/personal/edit', ["jquery", "validate", 'address'], function($, validate){
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
			html += '	        <div class="block-item no-top-border">';
			if(data.agent_level == 0){
				html += '	   		<a href="/h5/xiufu" style="color:#f60;text-align:center;margin-left: -10px;">老会员或变成游客请点击这里</a>';
			}
			html += '	        </div>';
			html += '	        <div class="block-item'+(data.agent_level == 0 ? '' : '  no-top-border')+'">';
			html += '	            <label>姓　　名</label>';
			html += '	            <input type="text" name="nickname" value="'+(data.mobile == '' ? '' : data.nickname)+'" placeholder="真实姓名" required="required" data-msg-required="请输入姓名">';
			html += '	        </div>';
			html += '	        <div class="block-item">';
			html += '	        	<label>性　　别</label>';
			html += '	        	<div class="area-layout">';
			html += '	        		<select name="sex">';
			html += '	        			<option value="0">保密</option>';
			html += '	        			<option value="1"'+(data.sex==1?'selected="selected"':'')+'>男</option>';
			html += '	        			<option value="2"'+(data.sex==2?'selected="selected"':'')+'>女</option>';
			html += '	        		</select>';
			html += '	        	</div>';
			html += '	        </div>';
			html += '	        <div class="block-item">';
			html += '	            <label>联系电话</label>';
			html += '	            <input type="tel" name="mobile" value="'+data.mobile+'" placeholder="手机号码" required="required" data-rule-mobile="mobile" data-msg-required="请输入联系电话" data-msg-mobile="请输入正确的手机号" maxlength="11">';
			html += '	        </div>';
			html += '	        <div class="block-item js-code_view'+(data.mobile != '' ? ' hide' : '')+'">';
			html += '	            <label>验证码</label>';
			html += '	            <div class="area-layout">';
			html += '	            	<input type="number" name="checknum" required="required" class="'+(data.mobile.length == 11 ? 'ignore' : '')+'" placeholder="验证码" data-msg-required="请输入验证码" maxlength="6">';
			html += '	            	<button type="button" class="js-get_code tag tag-big tag-orange" style="border:none;position: absolute;right: 0;top: 0;bottom: 0;font-size: 12px;padding: 0 20px;">获取验证码</button>';
			html += '	            </div>';
			html += '	        </div>';
			html += '	        <div class="block-item">';
			html += '	            <label>居住地址</label>';
			html += '	            <div class="js-area-select area-layout">';
			html += '	            	<span>';
			html += '						<select id="province" name="province_id" data-city="#city" data-selected="'+data.province_id+'" class="address-province" required="required" data-msg-required="请选择省份">';
			html += '							<option value="">选择省份</option>';
			html += '						</select>';
			html += '					</span>';
			html += '					<span>';
			html += '						<select id="city" name="city_id" data-county="#county" data-selected="'+data.city_id+'" class="address-city" required="required" data-msg-required="请选择城市">';
			html += '							<option value="">选择城市</option>';
			html += '						</select>';
			html += '					</span>';
			html += '					<span>';
			html += '						<select id="county" name="county_id" data-selected="'+data.county_id+'" class="address-county"  required="required" data-msg-required="请选择区县">';
			html += '							<option value="">选择区县</option>';
			html += '						</select>';
			html += '					</span>';
			html += '				</div>';
			html += '        	</div>';
			html += '	        <div class="block-item">';
			html += '	            <label>详细地址</label>';
			html += '	            <input type="text" name="detail" value="'+data.detail+'" placeholder="街道门牌信息，请勿重复省市区" required="required" data-msg-required="请输入详细地址">';
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
					url: '/h5/personal/check',
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
,define('goods_list_tpl', function(){
	return {
		getHTML: function(list, page){
			if(list.length == 0 && page == 1){
				return '<li><div class="empty-list"><div><h4>居然都没啦</h4><p class="font-size-12">好东西，手慢无</p></div><div><a href="" class="js-refresh tag tag-big tag-orange">刷新</a></div></div></li>';
			}

			var html = tagHtml = '', class_name, index = 0;
			for(var i=0; i< list.length; i++){
				var goods = list[i], images = goods.images, imagehtml = '';
				for(var j=0; j<images.length; j++){
					imagehtml += '<div class="swiper-slide"><img src="'+images[j]+'"></div>';
				}

				index++;
				class_name = index == 1 ? 'big-pic' : 'small-pic';

				if(i + 1 == list.length){
					if(index % 2 == 0){
						class_name = 'big-pic';
					}
				}else if(index == 19){
					index = 0;
				}

				tagHtml = '';
				if(goods.tags){
					var tags = goods.tags;
					for(var j=0; j<tags.length; j++){
						tagHtml += '<span class="tag tag-blue">'+tags[j]+'</span>';
					}
				}

				html += '<li class="js-goods-card goods-card card '+ class_name+'" data-id="'+goods.id+'">';
				html += '	<a href="'+goods.link+'" class="link">';
				html += '		<div class="photo-block"><div class="goods-main-image">';
				html+= '           <img class="goods-photo js-goods-lazy" data-original="'+goods.pic_url+'"><script type="text/html">'+imagehtml+'</script>';
				if(goods.stock == 0){
					html += '<div class="item-badge"><div class="badge-content"><div class="badge-title">已售罄</div><div class="badge-info">SOLDOUT AGAIN</div></div></div>';
				}
				html+= '        </div></div>';
				html += '		<div class="info clearfix info-price">';
				html += '			<div class="goods-title">';
				if(goods.tags == ''){
					html += goods.title;
				}else{
					html += '<div class="short">'+goods.title+'</div>';
					html += '<div class="title-icons">'+tagHtml+'</div>';
				}
				html += '			</div>';
				html += '			<p class="goods-sub-title c-black hide"></p>';
				html += '           <div class="goods-price">';

				for(var j=0; j<goods.view_price.length; j++){
					var info = goods.view_price[j];
					html += '<p>';
					if(info.title){html += info.title+'：'}
					if(info.prefix){html += '<span class="price_prefix">'+info.prefix+'</span>'}
					html += '<em>'+info.price+'</em>';
					if(info.suffix){html += '<span class="price_suffix">'+info.suffix+'</span>'}
					html += '</p>';
				}

				html += '           </div>';
				html += '		</div>';
				html += '		<div class="goods-buy btn1 info-title"></div>';
				html += '		<div class="js-goods-buy buy-response" data-id="'+goods.id+'"></div>';
				html += '	</a>';
				html += '</li>';
			}

			return html;
		},
		progess: function(list, page){
			if(list.length == 0 && page == 1){
				return '<li><div class="empty-list"><div><h4>居然都没啦</h4><p class="font-size-12">好东西，手慢无</p></div><div><a href="" class="js-refresh tag tag-big tag-orange">刷新</a></div></div></li>';
			}

			var html = tagHtml = '', class_name, index = 0;
			for(var i=0; i< list.length; i++){
				var goods = list[i], images = goods.images, imagehtml = '';
				for(var j=0; j<images.length; j++){
					imagehtml += '<div class="swiper-slide"><img src="'+images[j]+'"></div>';
				}

				index++;
				class_name = index == 1 ? 'big-pic' : 'small-pic';

				if(i + 1 == list.length){
					if(index % 2 == 0){
						class_name = 'big-pic';
					}
				}else if(index == 19){
					index = 0;
				}

				html += '<li class="js-goods-card goods-card card '+ class_name+'" data-id="'+goods.id+'">';
				html += '	<a href="'+goods.link+'" class="link">';
				html += '		<div class="photo-block"><div class="goods-main-image">';
				html+= '           <img class="goods-photo js-goods-lazy" data-original="'+goods.pic_url+'"><script type="text/html">'+imagehtml+'</script>';
				if(goods.stock == 0){
					html += '<div class="item-badge"><div class="badge-content"><div class="badge-title">已售罄</div><div class="badge-info">SOLDOUT AGAIN</div></div></div>';
				}
				html+= '        </div></div>';
				html += '		<div class="info clearfix info-price">';
				html += '			<div class="goods-title">'+goods.title+'</div>';
				html += '			<p class="goods-sub-title c-black hide"></p>';

				html += '<div class="groupon-price" style="display:-webkit-box;margin:5px 0 8px">';
				html += '	<div class="item-price" style="-webkit-box-flex: 1;color: #999;">';
				html += '		<span class="item-newprice" style="color: #fc353a;font-size:18px;font-weight: 700;">';
				var agent = goods.view_price[0]

				html += (agent.price_prefix ? '<span class="price_prefix">'+agent.price_prefix+'</span>' : '')+'<em>'+agent.price+'</em></span>';
				var agent = goods.view_price[1]
				html += '		<del class="item-oldprice">';
				html += (agent.price_prefix ? '<span class="price_prefix">'+agent.price_prefix+'</span>' : '')+'<em>'+agent.price+'</em></span>';
				html += '		</del>';
				html += '	</div>';

				html += '	<div class="action">马上抢<i></i></div>';
				html += '</div>';
				html += '<div class="item-status">';
				html += '	<div class="status-bar">';
				html += '		<div class="status-progress" style="width:'+goods.progress+'%;"></div>';
				html += '		<div class="status-soldrate">'+goods.progress+'%</div>';
				html += '	</div>';
				html += '	<div class="status-num">'+goods.active_sold+'件已抢</div>';
				html += '</div>';
				html += '		</div>';
				html += '	</a>';
				html += '</li>';
			}

			return html;
		}
	}
})
,define('goods_list', ["jquery", "goods_list_tpl", "lazyload"], function($, template){
	var t = {
		url: '',
		skumodal: null,
		container: '.js-goods-list',
		pullfresh: null,
		cacheKey: 'all',
		tplName: 'getHTML',
		start: function(options){
			t.url = options.url;
			t.container = options.container;
			if(options.tplName){
				t.tplName = options.tplName;
			}

			if(!!options.pullfresh){
				t.initPullfresh(options.pullfresh);
			}else{
				t.loadData();
			}
			t.bindEvent();
		},
		parseList: function(params, first){
			var $container = $(t.container);
			var html = template[t.tplName](params.list, params.page);
			if(first){
				$container.html(html);
			}else{
				$container.append(html);
			}

			if(!!t.pullfresh){
				t.pullfresh.page = params.page;
				t.pullfresh.setNoMore(params.noMore);
			}

			$container.find(".js-goods-lazy").lazyload({
				placeholder : "__CDN__/img/logo_rgb.jpg",
			    threshold : 270
			});
		},
		initPullfresh: function(option){
			if(!option || option === true){
				option = {refresh: true, page: 1, size: 38};
			}

			require(['pullfresh'], function(pullfresh){
				t.pullfresh = pullfresh;
				pullfresh.init({
					refresh: option.refresh,
					size: option.size,
					container: t.container,
					onLoad: function(parameters, isRefresh){
						t.loadData(parameters, pullfresh, isRefresh);
					},
				});
			});
		},
		loadData: function(params, pullfresh, isRefresh){
			if(!params){
				params = {page: 1, size: 19, offset: 0};
			}
			params = t.queryParams(params);
			if(!isRefresh && params.page == 1 && !!history.state && !!history.state[t.cacheKey]){
				var historyData = history.state;
				var data = historyData[t.cacheKey];
				t.parseList(data, true);
				document.body.scrollTop = data.scrollTop;
				return false;
			}

			$.ajax({
				url: t.url,
				data: params,
				dataType: 'json',
				cache: true,
				success: function(list){
					params.noMore = params.page == 1 && list.length == 0 ? '' : list.length < params.size;
					params.list = list;
					t.parseList(params, params.page == 1);

					var historyData = !history.state ? {} : history.state;
					var oldList = null;
					if(isRefresh || !historyData[t.cacheKey]){
						oldList = [];
					}else{
						oldList = historyData[t.cacheKey].list;
					}

					params.list = oldList.concat(list);
					params.scrollTop = document.body.scrollTop;
					historyData[t.cacheKey] = params;
					history.replaceState(historyData, '', '');
				},
				error: function(){
					t.parseList([], params);
					if(pullfresh){
						pullfresh.fail();
					}
				},
			});
		},
		queryParams: function(params){
			return params;
		},
		onLongClick: function(){
			var ele = this;
			require(['swiper'], function(){
				var $body = $('body'),
				images = $(ele).find('script').html(),
				id = newId();
				$body.append('<div id="'+id+'" class="swiper-container swiper-container-horizontal full-screen"><div class="swiper-wrapper" style="line-height:'+document.documentElement.clientHeight + 'px">'+images+'</div><div class="swiper-tip">温馨提示：长按图片保存到手机</div><div id="swiper-pagination-'+id+'" class="pagination"></div></div>');

				var $modal = $('#' + id);
				$modal.on('click', function(){
					$modal.remove();
					return false;
				});

				var mySwiper = new Swiper('#' + id, {loop: false, pagination : '#swiper-pagination-'+id});
			});
		},
		bindEvent: function(){
			// 下单按钮
			var $container = $(t.container);
			$container.on('click', '.js-goods-buy', function(){
				var animate = this.previousElementSibling;
				if(animate.classList.contains('ajax-loading')){
					return false;
				}
				animate.classList.add('ajax-loading');

				var $btn = $(this);
				if(!t.skumodal){
					require(['skumodal'], function(ShoppingCart){
						t.skumodal = ShoppingCart;
						t.showSku($btn);
					});
					return false;
				}
				t.showSku($btn);
				return false;
			});

			// 长按显示高清图
			var timeOutEvent=0;
			$container.on('touchstart', '.js-goods-card',function(){
				var ele = this;
				timeOutEvent = setTimeout(function(){
					t.onLongClick.apply(ele);
				}, 600);
			}).on('touchend', function(){
				clearTimeout(timeOutEvent);
			}).on('touchmove', function(){
				clearTimeout(timeOutEvent);
			}).on('click', '.js-refresh', function(){  // 重试(刷新)
				if(!!t.pullfresh){
					t.pullfresh.doRefresh(true);
				}else{
					t.loadData();
				}
				return false;
			});
		},
		showSku: function($btn){	// 弹出商品SKU模态框
			var parameters = $btn.data();
			new t.skumodal({
				data: parameters,
				buttons:{'buyNow': '立即下单', 'addCart': '加入购物车'},
				loaded: function(){
					$btn.prev().removeClass('ajax-loading');
				},
				onCart: function(product){
					 // 自定义处理，请返回false。否则将自动加入购物车
				},
				onBuy: function(product){
					// 自定义处理，请返回false。否则将自动进入后续处理
				}
			});
		}
	}

	// 离开页面记录滚动位置
	window.onbeforeunload = function(event) {
		if(history.state){
			var data = history.state;
			data[t.cacheKey].scrollTop = document.body.scrollTop;
			history.replaceState(data, '', '');
		}
	}
	return t;
})
,define('search', ['jquery'], function(){
	var $searchBar = $('.search-bar'),
		$form = $searchBar.find('.search-form'),
		$title = $form.find('.search-input')
	   ,t = {
			data: {shop_id: '', tag_id: '', title: ''},
			onSearch: function(data){
				var parameters = '';
				parameters = '?title=' + data.title;
				window.location.href = '/h5/list'+parameters;
			},
			init: function(){
				var array = $form.serializeArray();
				for(var i=0; i<array.length; i++){
					t.data[array[i].name] = array[i].value;
				}
			}
		};

	t.init();

	// 搜索
	var $historyList = $searchBar.find('.js-history-list');
	var searchGoods = function(){
		$searchBar.removeClass('focused');
		$('body').css({'height': '', 'overflow': ''});

		var array = $form.serializeArray();
		for(var i=0; i<array.length; i++){
			t.data[array[i].name] = array[i].value;
		}
		t.onSearch(t.data);
	}

	$searchBar.find('form').on('submit', function(){
		searchGoods();
		return false;
	});

	// 搜索历史
	$title.on('click', function(){
		$searchBar.addClass('focused');
		$(this).select();
		$('body').css({'height': '100%', 'overflow': 'hidden'});

		require(['//cdn.bootcss.com/jquery-cookie/1.4.1/jquery.cookie.min.js'], function(){
			var searchStr = $.cookie('search_goods');
			if(!searchStr){
				return;
			}

			var searchList = searchStr.split(';');
			var html = '';
			for(var i=0; i<searchList.length; i++){
				html += '<li>'+searchList[i]+'</li>';
			}
			$historyList.html(html);
		});
	});

	// 点击搜索历史
	$historyList.on('click', 'li',function(){
		var title = $(this).text();
		$title.val(title);
		searchGoods();
		return false;
	});

	// 清除搜索历史
	$searchBar.find('.js-tag-clear').on('click', function(){
		$.removeCookie('search_goods', { path: '/' });
		$historyList.html('');
		return false;
	});

	// 取消搜索
	$searchBar.find('.js-search-cancel').on('click', function(){
		$title.val('');
		$searchBar.removeClass('focused');
		$('body').css({'height': '', 'overflow': ''});
		return false;
	});

	return t;
})
,define('customer_service',['jquery'], function(){
	var t = {
		data: [],
		show: function(parameters){
			$.ajax({
				url: '/h5/kefu/qrcode',
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
,define('model_pay_coupon', ['jquery'], function(){
	var model = {
		scrollTop: 0,
		$modal: null,
		$list: null,
		$tip: null,
		types: {'1':'优惠券', '2': '优惠码', '3': '代金券', '4': '满减', '5':'限时折扣'},
		reset: function(list){
			if(list.length == 0){
				this.$list.html('<li class="block-item none">无优惠可用</li>');
				return;
			}

			var htmlChecked = htmlDiscount = htmlPromotion = htmlCoupon = html = '',
				className = '', single = false;
			for(var i=0; i<list.length; i++){
				if(list[i].checked && list[i].single){
					single = true;
					break;
				}
			}

			for(var i=0; i<list.length; i++){
				className = (list[i].single || single ? ' only-one' : '')+' js-type-'+list[i].type;
				if(list[i].checked){className += ' active'}

				html = '				<li data-index="'+i+'" class="block-item coupon-item '+className+'">';
				html += '					<div class="label-check-img"></div>';
				html += '					<div class="coupon-info">';
				html += '						<p class="font-size-12">'+list[i].title+'<em class="pull-right">-'+list[i].discount_fee.toFixed(2)+'</em></p>';
				html += '						<p class="font-size-12 c-gray-darker">'+list[i].description+'<em class="pull-right">'+this.types[list[i].type]+'</em></p>';
				html += '					</div>';
				html += '				</li>';

				if(list[i].checked){
					htmlChecked += html;
				}else if(list[i].type == 5){
					htmlDiscount += html;
				}else if(list[i].type == 4){
					htmlPromotion += html;
				}else{
					htmlCoupon += html;
				}
			}
			this.$list.html(htmlChecked+htmlDiscount+htmlPromotion+htmlCoupon);

			// 每点击一次重新计算一次
			var $coupons = this.$list.children('.coupon-item');
			$coupons.on('click', function(){
				var index = $(this).data('index');

				list[index].checked = !list[index].checked;

				if(list[index].checked){
					for(var i=0; i<list.length; i++){
						if(index == i){
							continue;
						}else if(list[i].type == list[index].type && (list[index].type == 4 || list[index].type == 5)){
							continue;
						}

						if(list[index].single || list[i].single || list[i].type == list[index].type){
							list[i].checked = false
						}
					}
				}
				model.onSelected(list, false);
				return false;
			});

			// 统计总优惠金额
			var totalDiscountFee = 0;
			for(var i=0; i<list.length; i++){
				if(list[i].checked){
					totalDiscountFee = totalDiscountFee.bcadd(list[i].discount_fee);
				}
			}
			totalDiscountFee = totalDiscountFee.toFixed(2);
			if(totalDiscountFee == '0.00'){
				model.$tip.html('选择优惠');
			}else{
				model.$tip.html('已优惠' + totalDiscountFee + '元');
			}
		},
		show: function(list){
			var html = '<div>';
			html += '<div class="modal-backdrop js-close"></div>';
			html += '<div class="modal popup coupon-popup">';
			html += '	<div class="js-scene-coupon-list">';
			html += '		<div class="header"><span class="js-tip">选择优惠</span><span class="js-close cancel-img"></span></div>';
			html += '		<div class="block block-list border-0">';
			html += '			<div class="js-code-inputer coupon-input-container block-item">';
			html += '				<input class="js-code-txt txt txt-coupon font-size-14" type="text" placeholder="请输入优惠码" autocapitalize="off" maxlength="20">';
			html += '				<button class="js-valid-code coupon-valid btn btn-white font-size-14" type="button">兑换</button>';
			html += '			</div>';
			html += '			<ul class="js-coupon-list coupon-list"></ul>';
			html += '		</div>';
			html += '	</div>';
			html += '	<div class="action-container coupon-action-container">';
			html += '		<button class="js-close btn btn-block btn-green" style="margin: 0px;">确定</button>';
			html += '	</div>';
			html += '</div></div>';

			this.scrollTop = document.body.scrollTop;
			$('html,body').css({'height': document.documentElement.clientHeight + 'px', 'overflow': 'hidden'});

			var $modal = $(html);
			$modal.appendTo('body');
			this.$modal = $modal;

			$modal.find('.js-valid-code').on('click', function(){
				toast.show('优惠码不存在');
				return false;
			});

			$modal.find('.js-close').on('click', function(){
				model.close();
				return false;
			});

			this.$list = $modal.find('.js-coupon-list');
			this.$tip = $modal.find('.js-tip');
			this.reset(list);
		},
		close: function(){
			$('body,html').css({'height': '', 'overflow': ''});
			this.$modal.remove();
			document.body.scrollTop = this.scrollTop;
		},
		onSelected: function(list){}
	};

	return model;
})
,define('login_modal', ['jquery'], function(){
	var tpl = '';
	tpl  = '<div id="login-modal">';
	tpl += '<div class="modal-backdrop"></div>';
	tpl += '<div class="popout-login">';
	tpl += '    <div class="header c-green center">';
	tpl += '        <h2><span class="loading"></span><span class="js-error">登录惠君优品</span></h2>';
	tpl += '    </div>';
	tpl += '    <fieldset class="wrapper-form font-size-14">';
	tpl += '        <div class="form-item">';
	tpl += '            <label>手机号：</label>';
	tpl += '            <input name="mobile" class="js-mobile" type="tel" maxlength="11" autocomplete="off" placeholder="11位手机号">';
	tpl += '        </div>';
	tpl += '        <div class="js-help-info error c-orange"></div>';
	tpl += '        <div class="form-item hide">';
	tpl += '            <label>验证码：</label>';
	tpl += '            <input type="number" name="code" class="js-auth-code" maxlength="6" autocomplete="off" placeholder="短信验证码">';
	tpl += '            <button type="button" class="js-get-code tag btn-auth-code tag-green">获取验证码</button>';
	tpl += '        </div>';
	tpl += '        <div class="form-item">';
	tpl += '            <label>密　码：</label>';
	tpl += '            <input name="password" type="text" class="js-password" autocomplete="off" maxlength="20" placeholder="6-20位任意字符">';
	tpl += '        </div>';
	tpl += '    </fieldset>';
	tpl += '    <div class="action-container">';
	tpl += '        <input type="button" class="js-confirm btn btn-green btn-block" value="立即登录">';
	tpl += '    </div>';
	tpl += '</div>';
	tpl += '</div>';

	var modal = {
		redirect: '/h5/mall',
		appid: '',
		$modal: null,
		mobile: '',
		init: function(data){
			$('#login-modal').remove();
			$('body').append(tpl);

			if(data){
				this.redirect = data.redirect;
				this.appid = data.appid;
				this.mobile = data.mobile;
			}
			modal.bindEvent();
		},
		onLogin: function(){
			window.location.href = modal.redirect;
		},
		bindEvent: function(){
			var $modal = modal.$modal = $('#login-modal'),
			$mobile = $modal.find('.js-mobile'),
			$authCode = $modal.find('.js-auth-code'),
			$password = $modal.find('.js-password'),
			$submit = $modal.find('.js-confirm'),
			$error = $modal.find('.js-error');

			$mobile.val(this.mobile);
			$mobile.on('keyup', function(){
				var mobile = this.value;
				if(!/^1[3|4|5|7|8]\d{9}$/.test(mobile)){
					$authCode.parent().addClass('hide');
					return false;
				}

				$modal.addClass('doing');
				$.ajax({
					url: '/h5/login/exists',
					type: 'post',
					data: {mobile: mobile},
					dataType: 'json',
					waitting: false,
					success: function(result){
						$password.parent().removeClass('hide');
						if(result.errcode == 0){ // 输入密码
							$authCode.parent().addClass('hide');
							$submit.val('立即登录');
						}else if(result.errcode == 1){	// 注册
							$authCode.parent().removeClass('hide');
							$submit.val('立即注册');
						}
					},
					complete: function(){
						$modal.removeClass('doing');
					}
				});
				return false;
			});

			// 获取验证码
			$modal.find('.js-get-code').on('click', function(){
				var btn = this,
					mobile = $mobile.val();
				btn.disabled = true;
				$modal.addClass('doing');
				$.ajax({
					url: '/h5/login/code',
					data: {mobile: mobile},
					type: 'post',
					waitting: false,
					datatype: 'json',
					success: function(){
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
					error: function(){
						btn.disabled = false;
					},
					complete: function(){
						$modal.removeClass('doing');
					}
				});
				return false;
			});

			$submit.on('click', function(){
				if($modal.hasClass('doing')){
					return false;
				}

				var data = {
					mobile: $mobile.val(),
					code: $authCode.val(),
					password: $password.val(),
					redirect: modal.redirect
				};

				if(!/^1[3|4|5|7|8]\d{9}$/.test(data.mobile)){
					$error.addClass('c-orange').html('请输入11位手机号');
					return false;
				}

				if(!/^\d{6}$/.test(data.code) && !$authCode.parent().hasClass('hide')){
					$error.addClass('c-orange').html('请输入验证码');
					return false;
				}

				var password = data.password.split('');
				if(password.length < 6 || password.length > 20){
					$error.addClass('c-orange').html('请输入6-20位密码');
					return false;
				}

				var pwdArray = [];
				for(var i=0; i<password.length; i++){
					if(pwdArray.indexOf(password[i]) == -1){
						pwdArray.push(password[i]);
					}
				}
				if(pwdArray.length < 5){
					$error.addClass('c-orange').html('密码过于简单');
					return false;
				}

				$error.removeClass('c-orange').html('登录惠君优品');
				$modal.addClass('doing');
				var btn = this;
				btn.disabled = true;
				$modal.addClass('doing');

				$.ajax({
					url: '/h5/login/auth',
					type: 'post',
					data: data,
					dataType: 'json',
					success: function(result){
						modal.mobile = data.mobile;
						$password.parent().removeClass('hide');
						if(result.errcode == 1){	// 注册
							$authCode.parent().removeClass('hide');
							$submit.val('立即注册');
							$error.addClass('c-orange').html(result.errmsg);
						}else if(result.errcode == 2){	// 密码错误
							$error.addClass('c-orange').html(result.errmsg);
						}else if(result.errcode == 3){	// 绑定微信
							modal.wxLogin();
							$error.removeClass('c-orange').html('账户'+modal.mobile);
						}else if(result.list.length == 1){
							modal.loginSuccess();
						}

						if(result.list && result.list.length > 0){
							modal.setMemberList(result.list);
						}
					},
					complete: function(){
						btn.disabled = false;
						$modal.removeClass('doing');
					}
				});
				return false;
			});

			// 选择登录的账号
			$modal.on('click', '.member-item',function(){
				var $this = $(this);
				if($this.data('openid') == ''){
					modal.wxLogin();
					$error.addClass('c-orange').html('请先绑定微信号');
					return false;
				}

				var mid = $this.data('mid');
				$.ajax({
					url: '/h5/login/confirm',
					type: 'post',
					dataType: 'json',
					data: {mid: mid},
					success: function(){
						modal.loginSuccess();
					}
				});
				return false;
			}).on('click', '.js-bind', function(){
				modal.wxLogin();
				return false;
			});
		},
		setMemberList: function(list){
			var color = ['#00a0f8', '#bb14c5', '#f90', '#999', '#7caf1a'],
			colorIndex = 0;
			var html = '<div class="member-list">';
			var showBind = false;
			for(var i=0; i<list.length; i++){
				var member = list[i];
				html += '<div class="member-item" data-mid="'+member.id+'" data-openid="'+member.wxid+'">';

				if(member.head_img != ''){
					html += '	<div class="member-headimg"><img src="'+member.head_img+'"></div>';
				}else{
					html += '	<div class="member-headimg" style="background-color:'+color[colorIndex]+'">'+member.head_txt+'</div>';
					colorIndex = colorIndex+1 == color.length ? 0 : colorIndex+1;
				}
				html += '	<div class="member-info">';
				html += '	    <div><span class="member-nick">'+member.nickname+'</span><span class="pull-right member-level">'+member.agent_title+'</span></div>';
				html += '	 	<div class="member-detail">账户剩余'+((member.balance*1+member.no_balance*1).toFixed(2) * 100 / 100)+'积分';
				html += '	  		<span class="pull-right hide">08月07日 23:59</span>';
				html += '		</div>';
				html += '	</div>';
				html += '</div>';

				if(!member.wxid){
					showBind = true;
				}
			}
			html += '</div>';

			html += '<div class="action-container'+(showBind ? '' : ' hide')+'">';
			html += '	<input type="button" class="js-bind btn btn-green btn-block" value="绑定微信号">';
			html += '</div>';

			var $header = modal.$modal.find('.header');
			$header.nextAll().remove();
			$header.after(html);
		},
		wxLogin: function(){
			uexWeiXin.cbRegisterApp=function(opCode,dataType,data){
			    if(data != 0){
			    	alert('微信异常请联系本平台客服：register');
			    	return;
			    }

			    // 检测是否安装微信
			    uexWeiXin.isWXAppInstalled();
			}

			uexWeiXin.cbIsWXAppInstalled=function(opCode,dataType,data){
				if(data != 0){
					alert('请先安装微信再使用本app');
			    	return;
				}

				var params = {
			        scope:"snsapi_userinfo,snsapi_base",
			        state:"0902"
			    };
			    var data = JSON.stringify(params);
			    uexWeiXin.login(data);
			};

			// 授权登录回调函数
			uexWeiXin.cbLogin = function(data){
				var result = JSON.parse(data);
				if(!result.code){
					alert('已取消授权登录');
					return;
				}

				$.ajax({
					url: '/h5/login/bind',
					data: result,
					type: 'post',
					dataType: 'json',
					success: function(list){
						modal.setMemberList(list);

						if(list.length == 1){
							modal.loginSuccess();
						}
					}
				});
			}

			uexWeiXin.registerApp(modal.appid);
		},
		loginSuccess: function(){
			modal.$modal.remove();
			modal.onLogin();
		}
	};
	return modal;
})
,define('order_timer', function(){
	var enabled = true;
	var fn = function(){
		$.ajax({
			url: '/h5/order/nearest',
			dataType: 'json',
			success: function(data){
				if(data.errcode == 1){
					enabled = false;
					return;
				}

				var html = '<section id="order_timer_content" class="tip_visitors">' +
					'<img src="'+data.headimgurl+'">' +
				    '<p>最新订单来自<span class="textFlow">'+data.nickname+'</span>，'+data.time+'之前</p>' +
				    '</section>';
				$('body').append(html);
				$('#order_timer_content').animate({top: '20%',opacity: 1});
			},
			complete: function(){
				if(!enabled){
					return;
				}
				setTimeout(function(){
					var $content = $('#order_timer_content');
					$content.animate({top: '10%',opacity: 0}, function(){
						$content.remove();

						var myDate = new Date();
						var hours = myDate.getHours();
						var time = (hours > 2 && hours < 6 ? 15000 : 6000) + Math.round(Math.random() * 6000);
						setTimeout(function(){
							fn();
						}, time);
					});
				}, 3000);
			}
		});
	}

	fn();
})
//分销商品管理
,define('my_commodity_list', ["pullfresh", "jquery"], function(pullfresh){
	var bill_list = {},
	active= 'all';
	var inner_list = function(list){
		if(list.data == ''){
			return '<li style="text-align:center;">还没有记录~~~</li>';
		}
		var html = '', trade = null, order = null, url= '';
		trade = list.data;
		for(var i=0; i< trade.length; i++){
			html += '<li class="clearfix" data-id="'+trade[i].data_id+'">';
			html +='	<a href="javascript:;" class="check_wrap">';
			html +='		<span class="checked_icon"></span>';
			html +='	</a>';
			html +='	<div class="img_wrap">';
			html +='		<img src="'+trade[i].img+'"/>';
			html +='	</div>';
			html +='	<div class="words_wrap">';
			html +='		<div class="main_name clearfix">';
			html +='			<span class="tag_agent">代理</span>';
			html +='			<p>'+trade[i].name+'</p>';
			html +='		</div>';
			html +='		<ul class="commodity_ul clearfix">';
			html +='			<li>货号：<span>'+trade[i].number+'</span></li>';
			html +='			<li>库存：<span>'+trade[i].stock+'</span></li>';
			html +='			<li>销量：<span>'+trade[i].sales+'</span></li>';
			html +='		</ul>';
			html +='		<ul class="price_ul clearfix">';
			html +='			<li class="true_price">¥'+trade[i].new_price+'</li>';
			html +='			<li>拿货价：<span>¥'+trade[i].old_price+'</span></li>';
			html +='		</ul>';
			html +='	</div>';
			html +='</li>';
		}

		return html;
	}

	$('#switch-tab-container>ul>li').on('click', function(){
		var $this = $(this),
		target = $(this).attr('href'),
		$target = $(target),
		active = $this.data('status');
		if($this.index()==3){
			$this.parents('.container').removeClass('edit_state');
			$('.goods_opeation .batch_shelf').addClass('disabled');
			$('.goods_opeation .batch_shelf').text('批量下架');
		}else{
			$('.goods_opeation .batch_shelf').removeClass('disabled');
		}
		pullfresh.page = 0;
		pullfresh.init({
			refresh: true,
			container: $('#'+active+'_log').children("ul"),
			onLoad: function(parameters){
				// $.ajax({
				// 	url: '/h5/center/recordList?status='+active+'&offset=' + parameters.offset + '&size='+parameters.size,
				// 	success: function(list){
				// 		console.log(list);
				// 		var $content = $('#'+active+'_log'+'>.js-list');
				// 		if(parameters.page == 1){
				// 			var html = inner_list(list);
				// 			$content.html(html);
				// 		}else{
				// 			var html = inner_list(list);
				// 			$content.append(html);
				// 		}

				//		var noMore = list.data.length < parameters.size;
				//		if(parameters.page == 1 && list.data.length == 0){
				//			noMore = '';
				//		}
				// 		pullfresh.setNoMore(noMore);
				// 	},
				// 	error: function(){
				// 		pullfresh.fail();
				// 	}
				// });
				//list={"type": 1,"data": []};
				//list = {"type": 1,"data": [{"data_id":0,"name": "大兴安岭特产山珍蘑菇礼盒木耳礼盒123","img": "img/goods_img.jpg","number":"Ib-001","stock":1998,"sales":0,"old_price":168,"new_price":225},{"data_id":0,"name": "大兴安岭特产山珍蘑菇礼盒木耳礼盒123","img": "img/goods_img.jpg","number":"Ib-001","stock":1998,"sales":0,"old_price":168,"new_price":225},{"data_id":0,"name": "大兴安岭特产山珍蘑菇礼盒木耳礼盒123","img": "img/goods_img.jpg","number":"Ib-001","stock":1998,"sales":0,"old_price":168,"new_price":225},{"data_id":0,"name": "大兴安岭特产山珍蘑菇礼盒木耳礼盒123","img": "img/goods_img.jpg","number":"Ib-001","stock":1998,"sales":0,"old_price":168,"new_price":225},{"data_id":0,"name": "大兴安岭特产山珍蘑菇礼盒木耳礼盒123","img": "img/goods_img.jpg","number":"Ib-001","stock":1998,"sales":0,"old_price":168,"new_price":225},{"data_id":0,"name": "大兴安岭特产山珍蘑菇礼盒木耳礼盒123","img": "img/goods_img.jpg","number":"Ib-001","stock":1998,"sales":0,"old_price":168,"new_price":225},{"data_id":0,"name": "大兴安岭特产山珍蘑菇礼盒木耳礼盒123","img": "img/goods_img.jpg","number":"Ib-001","stock":1998,"sales":0,"old_price":168,"new_price":225},{"data_id":0,"name": "大兴安岭特产山珍蘑菇礼盒木耳礼盒123","img": "img/goods_img.jpg","number":"Ib-001","stock":1998,"sales":0,"old_price":168,"new_price":225},{"data_id":0,"name": "大兴安岭特产山珍蘑菇礼盒木耳礼盒123","img": "img/goods_img.jpg","number":"Ib-001","stock":1998,"sales":0,"old_price":168,"new_price":225}]};
				list = {"type": 1,"data": [{"data_id":0,"name": "大兴安岭特产山珍蘑菇礼盒木耳礼盒123","img": "img/goods_img.jpg","number":"Ib-001","stock":1998,"sales":0,"old_price":168,"new_price":225}]};
				$content = $('#'+active+'_log'+'>.js-list');
				if(parameters.page == 1){
					var html = inner_list(list);
					$content.html(html);
				}else{
					var html = inner_list(list);
					$content.append(html);
				}
				//document.body.scrollTop = 0;

				var noMore = list.data.length < parameters.size;
				if(parameters.page == 1 && list.data.length == 0){
					noMore = '';
				}
				pullfresh.setNoMore(noMore);

			}
		});
		$this.addClass('active').siblings().removeClass('active'),
		$target.removeClass('hide').siblings().addClass('hide');



		return false;
	});

	$('#switch-tab-container>ul>li.active').trigger('click');




})

//订单管理
,define('my_order_list', ["pullfresh", "jquery"], function(pullfresh){
	var bill_list = {},
	active= 'all';
	var inner_list = function(list){
		if(list.length == ''){
			return '<li style="text-align:center;padding: 10px;background-color: #fff;">还没有记录~~~</li>';
		}
		var html = '', trade = null, order = null, url= '';
		for(var i=0; i< list.length; i++){
			trade = list[i];
			order = trade.orders[0];
			html += '<li>';
			html += '	<div class="buyer_state clearfix">';
			html +='		<span class="left">买家:'+trade.buyer_id+'</span>';
			html +='		<span class="right state_font">'+trade.order_state+'</span>';
			html +='	</div>';
			html +='	<p class="order_number">订单号：'+trade.order_num+'</p>';
			html +='	<div class="ware_all_box">';
			html +='		<div class="ware_box">';
			html +='			<a class="ware_thumb">';
			html +='				<img src="'+order.img+'">';
			html +='			</a>';
			html +='        </div>';
			html +='        <div class="detail_box clearfix">';
			html +='            <a class="ware_link" href="">';
			html +='                <h3 class="js-ellipsis">';
			html +='                    <i>'+order.name+'</i>';
			html +='                </h3>';
			html +='                <p>商品：'+order.name+'货号：'+order.number+'</p>';
			html +='            </a>';
			html +='        </div>';
			html +='        <div class="price_box">';
			html +='            <p class="new_price">¥'+order.new_price+'</p>';
			html +='            <p class="old_price">¥'+order.old_price+'</p>';
			html +='            <p class="number">×'+order.sales+'</p>';
			html +='        </div>';
			html +='    </div>';
			html +='    <div class="date_total clearfix">';
			html +='        <span class="left">'+trade.time+'</span>';
			html +='        <span class="total_row right"><i>¥'+trade.total_old_price+'</i>共'+trade.total_num+'件商品 <span>合计¥'+trade.total_new_price+'</span></span>';
			html +='    </div>';
			if(trade.no_delivery==0){
				html +='    <div class="deliver_goods">';
				html +='        <a href="" class="deliver_goods_btn">发货</a>';
				html +='    </div>';
			}
			html +='</li>';
		}
		return html;
	}

	$('#order-tab-container>ul>li').on('click', function(){
		var $this = $(this),
		target = $(this).attr('href'),
		$target = $(target),
		active = $this.data('status');

		pullfresh.page = 0;
		pullfresh.init({
			refresh: true,
			container: $('#'+active+'_log').children("ul"),
			onLoad: function(parameters){
				// $.ajax({
				// 	url: '/h5/center/recordList?status='+active+'&offset=' + parameters.offset + '&size='+parameters.size,
				// 	success: function(list){
				// 		console.log(list);
				// 		var $content = $('#'+active+'_log'+'>.js-list');
				// 		if(parameters.page == 1){
				// 			var html = inner_list(list);
				// 			$content.html(html);
				// 		}else{
				// 			var html = inner_list(list);
				// 			$content.append(html);
				// 		}

				// 		var noMore = list.length < parameters.size;
				// 		if(parameters.page == 1 && list.length == 0){
				// 			noMore = '';
				// 		}
				// 		pullfresh.setNoMore(noMore);
				// 	},
				// 	error: function(){
				// 		pullfresh.fail();
				// 	}
				// });

				console.log(parameters);
				list = [{
						"buyer_id": "甲木",
						"order_state": "待发货",
						"order_num": "2017022417219",
						"total_num": 1,
						"total_old_price":26,
						"total_new_price":19.8,
						"time":"03-25 09:42",
						"no_delivery":0,
						"orders": [{"name": "大兴安岭特产山珍蘑菇礼盒木耳礼盒123", "img": "img/goods_img.jpg", "number": "Ib-001", "old_price": 1988.00, "new_price": 198.00, "sales": 1}]
						},{
						"buyer_id": "甲木",
						"order_state": "待发货",
						"order_num": "2017022417219",
						"total_num": 1,
						"total_old_price":26,
						"total_new_price":19.8,
						"time":"03-25 09:42",
						"no_delivery":1,
						"orders": [{"name": "大兴安岭特产山珍蘑菇礼盒木耳礼盒123", "img": "img/goods_img.jpg", "number": "Ib-001", "old_price": 1988.00, "new_price": 198.00, "sales": 1}]
						},{
						"buyer_id": "甲木",
						"order_state": "待发货",
						"order_num": "2017022417219",
						"total_num": 1,
						"total_old_price":26,
						"total_new_price":19.8,
						"time":"03-25 09:42",
						"no_delivery":1,
						"orders": [{"name": "大兴安岭特产山珍蘑菇礼盒木耳礼盒123", "img": "img/goods_img.jpg", "number": "Ib-001", "old_price": 1988.00, "new_price": 198.00, "sales": 1}]
						}];
				//list = [];
				$content = $('#'+active+'_log'+'>.js-list');
				if(parameters.page == 1){
					var html = inner_list(list);
					$content.html(html);
				}else{
					var html = inner_list(list);
					$content.append(html);
				}
				//document.body.scrollTop = 0;

				var noMore = list.length < parameters.size;
				if(parameters.page == 1 && list.length == 0){
					noMore = '';
				}
				pullfresh.setNoMore(noMore);

			}
		});
		$this.addClass('active').siblings().removeClass('active'),
		$target.removeClass('hide').siblings().addClass('hide');



		return false;
	});

	$('#order-tab-container>ul>li.active').trigger('click');

})

//我的账单
,define('my_bill', ["pullfresh", "jquery"], function(pullfresh){
	var bill_list = {},
	active= 'income';
	var inner_list = function(list){
		if(list.length == ''){
			return '<li style="text-align:center;padding: 10px;background-color: #fff;">还没有记录~~~</li>';
		}
		var html = '', trade = null, order = null, url= '';
		for(var i=0; i< list.length; i++){
			trade = list[i];
			html += '<li>';
			html += '	<a href="" class="each_message_box">';
			html +='		<div class="each_message_cell1">';
			html +='			<img src="'+trade.img+'"/>';
			html +='		</div>';
			html +='        <div class="each_message_cell2">';
			html +='            <h4 class="each_message_title">'+trade.bill_name+'</h4>';
			html +='            <p class="each_message_amount">订单金额：'+trade.bill_money+'</p>';
			html +='            <p class="each_message_time">'+trade.bill_time+'</p>';
			html +='        </div>';
			html +='        <div class="each_message_cell3">';
			if(trade.type==0){
				html +='            <span class="each_message_number plus">+'+trade.number+'</span>';
			}else{
				html +='            <span class="each_message_number reduce">-'+trade.number+'</span>';
			}
			html +='        </div>';
			html +='    </a>';
			html +='</li>';
		}
		return html;
	}

	$('#bill-tab-container>ul>li').on('click', function(){
		var $this = $(this),
		target = $(this).attr('href'),
		$target = $(target),
		active = $this.data('status');

		pullfresh.page = 0;
		pullfresh.init({
			refresh: true,
			container: $('#'+active+'_log').children("ul"),
			onLoad: function(parameters){
				// $.ajax({
				// 	url: '/h5/center/recordList?status='+active+'&offset=' + parameters.offset + '&size='+parameters.size,
				// 	success: function(list){
				// 		console.log(list);
				// 		var $content = $('#'+active+'_log'+'>.js-list');
				// 		if(parameters.page == 1){
				// 			var html = inner_list(list);
				// 			$content.html(html);
				// 		}else{
				// 			var html = inner_list(list);
				// 			$content.append(html);
				// 		}

				// 		var noMore = list.length < parameters.size;
				// 		if(parameters.page == 1 && list.length == 0){
				// 			noMore = '';
				// 		}
				// 		pullfresh.setNoMore(noMore);
				// 	},
				// 	error: function(){
				// 		pullfresh.fail();
				// 	}
				// });

				console.log(parameters);
				list = [{
						"type":0,
						"bill_name": "商品交易",
						"bill_money": "19.8",
						"bill_time": "2017-03-25 09:46:14",
						"img": "img/goods_img.jpg",
						"number":1
						}];
				//list = [];
				$content = $('#'+active+'_log'+'>.js-list');
				if(parameters.page == 1){
					var html = inner_list(list);
					$content.html(html);
				}else{
					var html = inner_list(list);
					$content.append(html);
				}
				//document.body.scrollTop = 0;

				var noMore = list.length < parameters.size;
				if(parameters.page == 1 && list.length == 0){
					noMore = '';
				}
				pullfresh.setNoMore(noMore);

			}
		});
		$this.addClass('active').siblings().removeClass('active'),
		$target.removeClass('hide').siblings().addClass('hide');



		return false;
	});

	$('#bill-tab-container>ul>li.active').trigger('click');

})


,define('save_order', ["jquery","validate"], function($, validate){
	var $form = $('#entering_form');
	validate.init($form, function(data){
		$.ajax({
			url: '/h5/hpactive/createOrder',
			data: {data: data},
			dataType: 'json',
			success: function(){
				toast.show('保存成功~');
			},
			error: function(){
				toast.show('保存失败~');
			}
		});
		//win.redirect('http://www.baidu.com',2000);
		return false;
	});
})



//倒计时00天00小时00分00秒
function countDown(endTime, startTime){
	var leftTime=endTime - startTime;
	var leftsecond = parseInt(leftTime/1000);
	var day=Math.floor(leftsecond/(60*60*24));
	var hour=Math.floor((leftsecond-day*24*60*60)/3600);
	var minute=Math.floor((leftsecond-day*24*60*60-hour*3600)/60);
	var second=Math.floor(leftsecond-day*24*60*60-hour*3600-minute*60);
	return [day < 10 ? '0'+day : day, hour < 10 ? '0'+hour : hour, minute < 10 ? '0'+minute : minute, second < 10 ? '0'+second : second];
}
