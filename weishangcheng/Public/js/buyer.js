define("buyer/skumodal", ["jquery"], function($){
	var t = {
		 // 初始化
		init: function(url, callback){
			t.requestSKU(url, function(data){
				t.show(data);
				if(typeof callback){
					callback();
				}
			})
		},
		show: function(goods){
			this.goods = goods;
			this.initSKU(goods.sku_json, goods.products);
			t._show(goods);
			// t._initYDLShow(goods);
			if ($("li.sku-tag[sku-row-num=1]").length == 0 && $("li.sku-tag.active").length != 0) {
                var toTest = $("li.sku-tag.active");
                var testId = toTest.attr("data-id");
                if (this.SKUResult.length == 0 || this.SKUResult[testId] == undefined) {
                    toTest.toggleClass("unavaliable", true).toggleClass("active", false);
                }

            }
		},
		_initYDLShow:function(goods){
			var len = goods.products.length;
			var product = goods.products;
			for(var i = 0;i<len;i++){
				if(product[i].stock <= 0){
					var pos = product[i].sku_json;
					if(sku_json.length == 2){
						$("li.sku-tag[sku-row-num=0]").eq(sku_json[0].vid - 1).toggleClass('unavailable',true);

					}
				}
			}
		},
		// ajax获取商品信息
		requestSKU: function(url, callback){
			$.ajax({
				url: url,
				dataType: 'json',
				success: function(data){callback(data)},
				complete: function(){

				}
			});
		}
		,SKUResult: {}
		,keys: []
		,data: {}
		,getObjKeys: function(obj){if(obj!==Object(obj))throw new TypeError('Invalid object');var keys=[];for(var key in obj)if(Object.prototype.hasOwnProperty.call(obj,key))keys[keys.length]=key;return keys;}
		,combInArray: function(aData){if(!aData||!aData.length){return[]}var len=aData.length;var aResult=[];for(var n=1;n<len;n++){var aaFlags=this.getCombFlags(len,n);while(aaFlags.length){var aFlag=aaFlags.shift();var aComb=[];for(var i=0;i<len;i++){aFlag[i]&&aComb.push(aData[i])}aResult.push(aComb)}}return aResult}
		,getCombFlags: function(m,n){if(!n||n<1){return[]}var aResult=[];var aFlag=[];var bNext=true;var i,j,iCnt1;for(i=0;i<m;i++){aFlag[i]=i<n?1:0}aResult.push(aFlag.concat());while(bNext){iCnt1=0;for(i=0;i<m-1;i++){if(aFlag[i]==1&&aFlag[i+1]==0){for(j=0;j<i;j++){aFlag[j]=j<iCnt1?1:0}aFlag[i]=0;aFlag[i+1]=1;var aTmp=aFlag.concat();aResult.push(aTmp);if(aTmp.slice(-n).join("").indexOf('0')==-1){bNext=false}break}aFlag[i]==1&&iCnt1++}}return aResult}
		,initSKU: function(sku_json,products){this.SKUResult = [];this.data = [];for(var i=0;i<sku_json.length;i++){var sku_items=sku_json[i].items;var items=[];for(var j=0;j<sku_items.length;j++){items.push(sku_items[j].id)}this.keys.push(items)}for(var i=0;i<products.length;i++){if(products[i].stock<1){continue}var product=products[i],sku_items=product.sku_json,sku_key='';for(var j=0;j<sku_items.length;j++){sku_key+=(j==0?'':';')+sku_items[j].vid}this.data[sku_key]=product}var i,j,skuKeys=this.getObjKeys(this.data);for(i=0;i<skuKeys.length;i++){var skuKey=skuKeys[i];var sku=this.data[skuKey];var skuKeyAttrs=skuKey.split(";");/*skuKeyAttrs.sort(function(value1,value2){return parseInt(value1)-parseInt(value2)});*/var combArr=this.combInArray(skuKeyAttrs);for(j=0;j<combArr.length;j++){var key=combArr[j].join(";");if(!this.SKUResult[key]){this.SKUResult[key]=true}}this.SKUResult[skuKeyAttrs.join(";")]=sku}}
		,getActionHTML: function(goods){
			var left = goods.action.left, right = goods.action.right, html = '';
			// for(var i=0; i<left.length; i++){
			// 	html += '<a class="'+left[i]['class']+'" data-id="'+left[i]['data-id']+'" href="'+(left[i].link ? left[i].link : 'javascript:;')+'"></a>'
			// }

			html += '<div class="actions actions-'+right.length+' clearfix" style="margin-left:'+(left.length * 50)+'px">'
			for(var i=0; i<right.length; i++){
				html += '<a id="sku_'+right[i]['id']+'" class="'+right[i]['class']+'" href="'+right[i]['link']+'">'+right[i]['text']+'</a>'
			}
			return html+'</div>'
		}
		,getSkuHTML: function(goods){
			if(!goods.sku_json || goods.sku_json.length == 0){return ''}
			var list = goods.sku_json, html = '', disabled = '', active = goods.products.length == 1 ? ' active' : '';
			for(var i=0; i<list.length; i++){
				var skuitems = '';
				html += '<dl class="clearfix block-item">'+
						'<dt class="model-title sku-sel-title"><label>'+list[i].text+'：</label><span></span></dt>'+
						'<dd><ul class="model-list sku-sel-list" row_num="'+ list.length +'">';
				var sku_items = list[i].items, img = '';
				for(var j=0; j<sku_items.length; j++){
					disabled = !this.SKUResult[sku_items[j].id] ? ' unavailable' : '';
					img = sku_items[j].img ? sku_items[j].img : '';
					html += '<li class="tag sku-tag pull-left ellipsis'+active + disabled +'" sku-row-num="'+i+'" data-id="'+sku_items[j].id+'" data-img="'+img+'">'+sku_items[j].text+'</li>'
				}
				html += '</ul></dd></dl>';
			}
			return html;
		}
		,getHTML: function(goods){
			return ''+
			'<div id="skumodal">'+
				'<div class="js-cancel modal-backdrop"></div>'+
				'<div class="sku-layout modal" style="overflow:visible">'+
					'<div class="layout-title name-card sku-name-card">'+
			 			'<div class="thumb dc_pc_click" style="cursor: pointer;"><img src="'+goods.pic_url+'"></div>'+
		 				'<div class="detail goods-base-info clearfix">'+
							'<div class="goods-price"></div>'+
							'<div class="stock'+(goods.hide_stock ? " hide" : "")+'">库存0件</div>'+
							'<div id="skumodal-countdown" class="sale-distance"></div>'+
						'</div>'+
						'<div class="js-cancel sku-cancel"><div class="cancel-img"></div></div>'+
					'</div>'+
					'<div class="js-agent-notice">'+(t.goods.agent_rate.message ? '<div class="notice">'+t.goods.agent_rate.message+'</div>' : '')+'</div>'+
					'<div class="layout-content" style="max-height: '+(document.documentElement.clientHeight * 0.7 - 50 ).toFixed(2)+'px;">'+
						t.getSkuHTML(goods)+
						'<dl class="clearfix block-item">'+
							'<dt class="model-title sku-weight pull-left"><label class="js-weight">重：'+goods.weight+'kg</label><label>('+goods.freight_fee+')</label></dt>'+
							'<dd>'+
								'<div class="quantity">'+
									'<button class="minus" type="button"></button>'+
									'<input type="text" class="txt js-input-num" value="1" data-min="1" data-max="'+goods.stock+'" data-quota="'+(goods.quota ? goods.quota : goods.stock)+'">'+
									'<button class="plus" type="button"></button>'+
									'<div class="response-area response-area-minus"></div>'+
									'<div class="response-area response-area-plus"></div>'+
								'</div>'+
								'<div class="quota-notice">'+goods.quota_notice+'</div>'+
							'</dd>'+
						'</dl>'+
					'</div>'+
					'<div class="content-foot clearfix">'+t.getActionHTML(goods)+'</div>'+
				'</div>'+
			'</div>'
		}
		,close: function(){$("#skumodal").remove()}
		,_show: function(goods){ // 显示
			var html = t.getHTML(goods);
			$('body').append(html);
			// 倒计时
			t.countdown(goods);
			var $modal = $('#skumodal');
			// 点击关闭弹窗
			$modal.find(".js-cancel").on("click", function(){t.close()});
			// 价格
			this.$current_price = $modal.find('.goods-price');
			// 缩略图
			this.$thumbImg = $modal.find('.thumb>img');
			// 购买数量文本框
			this.$input_num = $modal.find('.js-input-num').on('change', function(){
				return t.quantity(parseInt(this.value)), false
			});
			this.$notice = $modal.find('.js-agent-notice');

			// 增加数量
			this.$btn_plus = $modal.find('.response-area-plus').on('click', function(){
				return t.quantity(parseInt(t.$input_num.val()) + 1), false;
			});
			// 减少数量
			this.$btn_minus = $modal.find('.response-area-minus').on('click', function(){
				if (parseInt(t.$input_num.val()) < 1) {
                    return false
                }
                return t.quantity(parseInt(t.$input_num.val()) - 1),false;
			});
			// 监听产品改变事件
			this.$sku_list = $modal.find('.sku-sel-list').on('click', '.sku-tag',function(){
				return t.checked(goods, this), false;
			});
			this.$sku_tags = this.$sku_list.find('.sku-tag');
			this.$min_order_quantity = $modal.find('.quota-notice');

			// 剩余数量
			t.$stock = $modal.find('.stock');
			// 重量
			this.$weightNum = $modal.find('.js-weight');
			// 监听加入购物车
			$modal.find('#sku_addCart').on('click', function(){
				var data = t.getProduct(goods);
				if(data){t.onCart.apply(this, [data])}
				return false;
			});
			// 监听立即购买
			$modal.find('#sku_buyNow').on('click', function(){
				var data = t.getProduct(goods);
				if(data){t.onBuy.apply(this,[data])}
				return false;
			});

			// 默认选中的产品
			var product = goods.products.length == 1 ? goods.products[0] : goods;
			t.productId = goods.products.length == 1 ? goods.products[0].id : null;
			this.setProduct(product , product.pic_url ? product.pic_url : goods.pic_url);

			// 单品代理
			if(goods.price_type == 4){
				var html = '<dl class="clearfix block-item"><dt class="model-title sku-sel-title"><label>'+goods.agent.title+'：</label><span></span></dt><dd><ul id="sku_agent" class="model-list">';

				if(goods.agent.level == 0){
					html += '<li class="tag sku-tag pull-left ellipsis active" data-id="0">统一零售</li>';
				}
				var agent_id = goods.agent.id;
				for(var level in goods.agent.items){
					var item = goods.agent.items[level];
					html += '<li class="tag sku-tag pull-left ellipsis'+(goods.agent.level > level ? ' unavailable' : '')+(goods.agent.level == level ? ' active' : '')+'" data-id="'+agent_id+''+level+'">'+item.title+'</li>';
				}
				html += '</ul></dd></dl>';
				$modal.find('.layout-content').prepend(html);

				$('#sku_agent').on('click', '.sku-tag',function(){
					var $this = $(this);
					if($this.hasClass('unavailable')){
						return false
					}

					var agent_id = $this.data('id'), quantity = 1;
					if(agent_id > 0){
						var level_id = parseInt(t.goods.agent.id+''+t.goods.agent.level), quantity = 1;
						if(level_id == agent_id){
							quantity = t.rangePrice[agent_id].second
						}else{
							quantity = t.rangePrice[agent_id].first
						}
					}

					t.quantity(quantity);
					return false;
				}).children('.active').trigger('click');
			}
		}
		,countdown: function(goods){if(!goods.countdown||goods.countdown.end<0){return}var start_time=Date.parse(new Date());var end_time=goods.countdown.end*1000;var timer=window.setInterval(function(){start_time+=1000;var leftTime=end_time-start_time;if(leftTime==0){window.clearInterval(timer);t.close();t.init(goods.sku_url);return}var leftsecond=parseInt(leftTime/1000);var day=Math.floor(leftsecond/(60*60*24));var hour=Math.floor((leftsecond-day*24*60*60)/3600);var minute=Math.floor((leftsecond-day*24*60*60-hour*3600)/60);var second=Math.floor(leftsecond-day*24*60*60-hour*3600-minute*60);var html=goods.countdown.type=='start'?'距开始':'距结束';if(day>0){html+='<i>'+(day<10?'0'+day:day)+'</i>天'}html+='<i>'+(hour<10?'0'+hour:hour)+'</i>小时<i>'+(minute<10?'0'+minute:minute)+'</i>分<i>'+(second<10?'0'+second:second)+'</i>秒';var element=document.getElementById('skumodal-countdown');if(!element){window.clearInterval(timer)}else{element.innerHTML=html}},1000)}
		,checked: function(goods, ele){ // 产品改变
			var t = this;
			var $self = $(ele);

			if($self.hasClass('unavailable')){return false}

			$self.parents(':eq(1)').prev().children('span').html($self.hasClass('active') ? '' : $self.text());
			var product = null, img_url, sku_modal = this;
			$self.toggleClass('active').siblings().removeClass('active');
			var selectedObjs = sku_modal.$sku_list.find('.active');
			// 修复商品主图在 二分类时只切换颜色tab时不改变。
			var row_num = sku_modal.$sku_list.attr('row_num');
			if(row_num == 2 && selectedObjs.length == 1){
				var _row = selectedObjs[0].getAttribute('sku-row-num');
				if(_row == "0"){
					var _pos = parseInt((selectedObjs[0].getAttribute('data-id') - 1)*$("#skumodal .sku-tag[sku-row-num=1]").length);
					_pic_path = goods.products[_pos].pic_url;
					t.$thumbImg.attr('src',_pic_path);
					
				}
			}

			if(selectedObjs.length) {
				var selectedIds = [];
				selectedObjs.each(function(i, item) {
					selectedIds.push(item.getAttribute('data-id'));
				});
				product = sku_modal.SKUResult[selectedIds.join(';')];


				var len = selectedIds.length;

				//用已选中的节点验证待测试节点 underTestObjs
				var row_one_select_id = $("#skumodal .sku-tag[sku-row-num=0].active").attr('data-id');
				var row_two_select_id = $("#skumodal .sku-tag[sku-row-num=1].active").attr('data-id');
				sku_modal.$sku_tags.toggleClass("unavailable",false);
				sku_modal.$sku_tags.not(selectedObjs).not($self).each(function() {
					if(row_num == "1" && row_one_select_id != undefined){
						if(sku_modal.SKUResult[$(this).attr("data-id")] == undefined){
								$(this).toggleClass("unavailable",true);
							}
					}
					if(row_one_select_id!=undefined && row_two_select_id != undefined){
						if($(this).attr('sku-row-num') == '0'){
							//待测节点是第一行的节点
							if(sku_modal.SKUResult[$(this).attr('data-id')+';'+row_two_select_id] == undefined){
								$(this).toggleClass("unavailable",true);
							}
						}else{
							//待测节点是第二行的节点
							if(sku_modal.SKUResult[row_one_select_id +';'+$(this).attr('data-id')] == undefined){
								$(this).toggleClass("unavailable",true);
							}
						}
					}
					if(row_one_select_id != undefined && row_two_select_id == undefined){
						//第一行选中 第二行没选中
						if($(this).attr('sku-row-num') == '1'){
							if(sku_modal.SKUResult[row_one_select_id +';'+$(this).attr('data-id')] == undefined){
								$(this).toggleClass("unavailable",true);
							}
						}
						if(row_num == 2){
							if($(this).attr('sku-row-num') == '0'){
								var _temp_id = $(this).attr('data-id');
								var _temp_stay = false;
								$("#skumodal .sku-tag[sku-row-num=1]").each(function(){
									if(sku_modal.SKUResult[_temp_id+';'+$(this).attr('data-id')] != undefined){
										_temp_stay = true;
									}
								})
								if(_temp_stay == false){
									$(this).toggleClass('unavailable',true);
								}
							}
						}

					}
					if(row_one_select_id == undefined && row_two_select_id != undefined){
						//第一行没选中 第二行选中
						if($(this).attr('sku-row-num') == '0'){
							if(sku_modal.SKUResult[$(this).attr('data-id')+';'+row_two_select_id] == undefined){
								$(this).toggleClass("unavailable",true);
							}
						}
						if(row_num == 2){
							if($(this).attr('sku-row-num') == '1'){
								var _temp_id = $(this).attr('data-id');
								var _temp_stay = false;
								$("#skumodal .sku-tag[sku-row-num=0]").each(function(){
									if(sku_modal.SKUResult[$(this).attr('data-id')+';'+_temp_id] != undefined){
										_temp_stay = true;
									}
								})
								if(_temp_stay == false){
									$(this).toggleClass('unavailable',true);
								}
							}
						}

					}

					// var $this = $(this);
					// var siblingsSelectedObj = $this.siblings('.active');
					// var testAttrIds = [];//从选中节点中去掉选中的兄弟节点
					// if(siblingsSelectedObj.length){
					// 	var siblingsSelectedObjId = siblingsSelectedObj.attr('data-id');
					// 	for(var i = 0; i < len; i++) {
					// 		(selectedIds[i] != siblingsSelectedObjId) && testAttrIds.push(selectedIds[i]);
					// 	}
					// }else{
					// 	testAttrIds = selectedIds.concat();
					// }
					// testAttrIds = testAttrIds.concat($this.attr('data-id'));
					// // testAttrIds.sort(function(value1, value2) {
					// // 	return parseInt(value1) - parseInt(value2)
					// // });

					// if(!sku_modal.SKUResult[testAttrIds.join(';')]){
					// 	$this.addClass('unavailable').removeClass('active')
					// }else{
					// 	$this.removeClass('unavailable')
					// }
				});
			}else{
				sku_modal.$sku_list.each(function() {
					var $this = $(this);
					sku_modal.SKUResult[$this.attr('data-id')] ? $this.removeClass('unavailable') : $this.addClass('unavailable').removeClass('active');
				})
			}

			if(typeof product == 'object'){
				t.productId = product.id;
				this.setProduct(product);
			}else{
				t.productId = null
			}
		},
		rangePrice: null,
		setProduct: function(product){
			var viewPrice = product.view_price;
			var html = '<span class="js-price">';
			if(viewPrice[0].prefix){html += '<span class="price-prefix">'+viewPrice[0].prefix+'</span>';}
			html += '<em>'+ viewPrice[0].price + '</em>';
			if(viewPrice[0].suffix){html += '<span class="price-suffix">'+viewPrice[0].suffix+'</span>';}
			html += '</span>';
			if(viewPrice[1]){
				html += '<span class="origin">';
				if(viewPrice[1].prefix){html += '<span class="price-prefix">'+viewPrice[1].prefix+'</span>';}
				html += '<em>'/*+ viewPrice[1].price*/ + '</em>';
				if(viewPrice[1].suffix){html += '<span class="price-suffix">'+viewPrice[1].suffix+'</span>';}
				html += '</span>';
			}
			t.$current_price.html(html);
			t.$thumbImg.attr('src', product.pic_url ? product.pic_url : t.goods.pic_url);
			t.$stock.html('库存'+ product.stock + '件');
			t.$weightNum.html('重量：'+product.weight+'kg');
			t.$input_num.attr('max', product.stock);
			if(t.goods.price_type == 1 || t.goods.price_type == 4){
				t.rangePrice = $.extend(null, product.custom_price);
			}
			t.$min_order_quantity.html(product.min_order_quantity > 1 ? product.min_order_quantity+'件起订' : '');

			t.$input_num.data('max', product.stock);
			t.quantity(t.buyNum);
		},
		buyNum: 1,
		productId: null,
		quantity: function(num){ //加减数量
			var error = '', $num = t.$input_num, min = $num.data('min'), max = $num.data('max'), quota = $num.data('quota');

			var price = 0;
			if(t.goods.price_type == 4){
				var quantity = 0, max_id = 0, is_first = true, mylevel = t.goods.agent.level, myid = parseInt(t.goods.agent.id+''+mylevel);
				for(var agent_id in t.rangePrice){
					if(myid == agent_id){ // 补货
						max_id = agent_id;
						t.$min_order_quantity.html('补货至少'+t.rangePrice[max_id].second+'件');
					}else{ // 首次
						quantity = t.rangePrice[agent_id].first
						if(num >= quantity){
							max_id = agent_id;
							t.$min_order_quantity.html('首次至少'+t.rangePrice[max_id].first+'件');
						}
					}
				}

				var $li = $('#sku_agent>li');
				$li.removeClass('active');
				if(max_id > 0){
					price = t.rangePrice[max_id].price;
				}else{
					t.$min_order_quantity.html(t.goods.quota_notice);
				}
				$li.filter('[data-id="'+max_id+'"]').addClass('active');
			}else{
				if(quota < max){max = quota}
				if(t.goods.price_type == 1){
					for(var quantity in t.rangePrice){
						if(num >= quantity){
							price = t.rangePrice[quantity]
						}
					}
				}
			}

			if(price > 0){
				price = price.toFixed(2);
				price = price.split('.');
				if(price != ''){
					t.$current_price.children('.js-price').html('<span><span class="price-prefix">¥</span><em>'+price[0]+'</em><span class="price-suffix">.'+price[1]+'</span></span>');
				}
			}

			if(num > max){num = max}
			else if(num < min){num = min}

			// 减少数量按钮样式处理
			this.$btn_minus.siblings('.minus').attr('disabled', num <= min)
			// 增加数量按钮样式处理
			this.$btn_plus.siblings('.plus').attr('disabled', num >= max);

			$num.val(num);
			t.buyNum = num;
		},
		getProduct: function(goods){
			if(t.productId){
				if(t.buyNum < 1){
					return toast.show('购买数量不能小于1'), null;
				}
				var data = {goods_id: goods.goods_id, product_id: t.productId, quantity: t.buyNum, shop_id: goods.shop_id};
				if(goods.activity_id > 0){
					data.activity_id = goods.activity_id;
				}
				return data;
			}

			var spec = [], $list = this.$sku_list;
			$list.each(function(i, item){
				 var $this = $(item);
				 if($this.children('.active').length == 0){
					 spec.push($this.parent().prev().text().replace('：', ''));
				 }
			});

			var error = '请选择 ';
			if(spec.length == 1){
				 error += spec[0];
			 }else{
				 for(var i=0; i<spec.length; i++){
					 error += spec[i];
					 if(i == spec.length - 2){
						 error += ' 和 ';
					 }else if(i < spec.length - 2){
						 error += ' 、 ';
					 }
				 }
			}
			toast.show(error);
			return;
		},
		onBuy: function(data){ // 点击立即购买
            // if ($('.js-cart-num').hasClass('has-cart')){
            //     window.location.href = get_url('/cart');
            // }else{
                var list = [{goods_id: data.goods_id, product_id: data.product_id, quantity: data.quantity, activity_id: data.activity_id}];
                var products = JSON.stringify(list);
                $.ajax({
                    url: get_url('/cart/submit'),
                    type: 'post',
                    data: {products: products},
                    dataType: 'json'
                });
            // }
		},
		onCart: function(postData){ // 点击加入购物车
			 $.ajax({
				 url: get_url('/cart/add'),
				 type: 'post',
				 dataType: 'json',
				 data: postData,
				 success: function(data){
					 t.goods.cart_quantity = data.total;
					 t.close();
					 $('.js-cart-num').addClass('has-cart');
					 $('#global-cart').removeClass('hide');
				 }
			 });
		}
	}

	//加入收藏
	$('body').on('click', '.js-add-collect', function(){
		var $this = $(this), id = $this.data('id'), action = $this.hasClass('checked') ? 'delete' : 'add';

		var $btn = $('.js-add-collect');
		if(action == 'add'){
			$btn.addClass('checked');
		}else{
			$btn.removeClass('checked');
		}
		$.ajax({
			 url: get_url('/collection/'+action),
			 type: 'post',
			 waitting: false,
			 dataType: 'json',
			 data: {goods_id: id, action: action},
			 error: function(){
				if(action == 'add'){
					$btn.removeClass('checked');
				}else{
					$btn.addClass('checked');
				}
			 }
		 });

		return false;
	});
	return t;
})
// 交易 - 收货地址
,define('buyer/address', ["jquery", 'h5/validate', 'address'], function($, validate){
	var address = {
		list: null,
		url: null,
		_default: {
			receiver_id: '',
			receiver_name: '',
			receiver_mobile: '',
			province_code: '',
			city_code: '',
			county_code: '',
			receiver_detail: '',
			receiver_zip: ''
		},
		_checked: 0,
		save: function(data, callback){
			var save = data.save;
			delete data.save;
			if(save == 0){
				data.receiver_province = $('#province :selected').text();
				data.receiver_city = $('#city :selected').text();
				data.receiver_county = $('#county :selected').text();
				if(!data.receiver_id){
					data.receiver_id = '_'+ (new Date().getTime());
				}
				callback(data);
				return;
			}
			if(address.url != null){
				var aurl=address.url;
			}else{
				var aurl='';
			}
			$.ajax({
				url: aurl+'/address/save',
				type: 'post',
				dataType: 'json',
				data: data,
				success: function(result){
					callback(result);
				}
			});
		},
		edit: function(edit_data){
			address.close();
			var addr = null;
			var edit_index = -1;

			if(typeof edit_data == 'object'){
				addr = edit_data;
				for(var i=0; i<this.list.length; i++){
					if(this.list[i].receiver_id == addr.receiver_id){
						edit_index = i;
						addr = this.list[i];
						break;
					}
				}
			}else if(!isNaN(edit_data)){
				edit_index = edit_data;
				addr = this.list[edit_index];
			}else{
				addr = this._default;
			}

			var html = '<div id="address_modal">'+
			    '<div class="modal-backdrop js-cancel"></div>'+
			    '<div class="modal">'+
			    	'<form class="js-address-fm address-ui address-fm" method="post" action="/address/edit">'+
			    		'<h4 class="address-fm-title">收货地址</h4>'+
			    	    '<div class="js-cancel cancel-img"></div>'+
			        	'<div class="block form" style="margin:0;">'+
	    					'<input type="hidden" name="receiver_id" value="'+addr.receiver_id+'">'+
	    				    '<div class="block-item no-top-border">'+
		    			        '<label>收 货 人</label>'+
		    			        '<input type="text" name="receiver_name" value="'+addr.receiver_name+'" placeholder="名字" required="required" data-msg-required="请输入收货人" rangelength="2,15" data-msg-rangelength="收货人在2~15个字符之间" maxlength="15">'+
		    			        '</div>'+
		    			        '<div class="block-item">'+
		    			        '<label>联系电话</label>'+
		    			        '<input type="tel" name="receiver_mobile" value="'+addr.receiver_mobile+'" placeholder="手机号码" required="required" data-rule-mobile="mobile" data-msg-required="请输入联系电话" data-msg-mobile="请输入正确的手机号" maxlength="11">'+
	    			        '</div>'+
	    			        '<div class="block-item">'+
	    			        	'<label>选择地区</label>'+
	    			            '<div class="js-area-select area-layout">'+
	    			        		'<span>'+
	    								'<select id="province" name="province_code" data-city="#city" data-selected="'+addr.province_code+'" class="address-province" required="required" data-msg-required="请选择省份">'+
	    									'<option value="">选择省份</option>'+
    									'</select>'+
									'</span>'+
			    					'<span>'+
			    						'<select id="city" name="city_code" data-county="#county" data-selected="'+addr.city_code+'" class="address-city" required="required" data-msg-required="请选择城市">'+
			    							'<option value="">选择城市</option>'+
		    							'</select>'+
			    					'</span>'+
			    					'<span>'+
			    						'<select id="county" name="county_code" data-selected="'+addr.county_code+'" class="address-county" required="required" data-msg-required="请选择区县">'+
			    						'<option value="">选择区县</option>'+
			    						'</select>'+
			    					'</span>'+
		    					'</div>'+
	    		        	'</div>'+
	    		            '<div class="block-item">'+
	    		            	'<label>详细地址</label>'+
	    		            	'<input type="text" name="receiver_detail" value="'+addr.receiver_detail+'" placeholder="街道门牌信息，请勿重复省市区" required="required" rangelength="5,120" data-msg-required="请输入详细地址" data-msg-rangelength="详细地址在5~120个字符之间" maxlength="120">'+
    		                '</div>'+
    		                '<div class="block-item">'+
    		                	'<label>邮政编码</label>'+
    		                	'<input type="text" minlength="6" maxlength="6" name="receiver_zip" value="'+addr.receiver_zip+'" placeholder="邮政编码(选填)" data-rule-digits="digits" data-msg-digits="邮政编码应为6位数字">'+
    		                '</div>'+
		                '</div>'+
		    	    '<div class="action-container">';
					if(addr.receiver_id != '' && !isNaN(addr.receiver_id)){
						html += '<button type="button" class="js-address-delete btn btn-block">删除</button>'
					}else{
						html += '<button type="submit" class="btn btn-block" name="save" value="0">临时</button>'
					}
					html +='<button type="submit" class="btn btn-block btn-red" name="save" value="1">保存</button>'+
			    	    '</div>'+
			    	'</form>'+
			    '</div></div>';
			$('body').append(html);

			var $modal = $('#address_modal');
			Address.bind('#province');

			// 监听表单提交
			var $form = $modal.find('form'),
			changed = false;
			$form.on('change', function(){
				changed = true;
			});

			validate.init($form, function(data){
				if(!changed){
					if(data.save == 0 || !isNaN(data.receiver_id)){
						address.close();
						return false;
					}
				}

				address.save(data, function(data){
					if(edit_index > -1){
						address.list[edit_index] = data;
					}else{
						address.list.push(data);
					}

					address.onSelect(data);
					address.close();
				});
				return false;
			});

			$modal.find('.js-address-delete').on('click', function(){
				if(confirm('确定要删除这个收货地址么？')){
					if(address.url != null){
						var aurl=address.url;
					}else{
						var aurl='';
					}
					$.ajax({
						url: aurl+'/address/delete',
						type: 'post',
						data: {receiver_id: addr.receiver_id},
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

			$('html,body').css({'height': document.documentElement.clientHeight + 'px', 'overflow': 'hidden'});
		},
		getList: function(){
			if(this.list != null){
				address.setList(list);
				return this.list;
			}
			if(address.url != null){
				var aurl=address.url;
			}else{
				var aurl='';
			}
			$.ajax({
				url: aurl+"/address/my",
				dataType: 'json',
				loading: true,
				success: function(list){
					address.list = list;
					address.updateListView(list);
				}
			});
		},
		updateListView: function(list){
			if(list == null){
				return;
			}else if(list.length == 0){
				address.show(address._default, true);
				return
			}

			var html = '';
			for(var i=0; i<list.length; i++){
				html +=
				'<div data-index="'+i+'" class="js-address-item block-item">'+
					'<div class="icon-check'+(list[i].receiver_id == this._checked ? ' icon-checked' : '')+'"></div>'+
					'<p>'+
						'<span class="address-name" style="margin-right: 5px;">'+list[i].receiver_name+'</span>,'+
			            '<span class="address-tel">'+list[i].receiver_mobile+'</span>'+
			         '</p>'+
			         '<span class="address-str address-str-sf">'+list[i].receiver_province+' '+list[i].receiver_city+' '+list[i].receiver_county+' '+list[i].receiver_detail+'</span>'+
			         '<div class="address-opt js-edit-address"><i class="icon-circle-info"></i></div>'+
	         	'</div>'
			}
			$('#address_modal .js-address-container').html(html);
		},
		showList: function(checked){
			var html =
				'<div id="address_modal">'+
					'<div class="modal-backdrop js-cancel"></div>'+
					'<div class="modal">'+
						'<div class="address-ui address-list">'+
							'<h4 class="address-title">收货地址</h4>'+
							'<div class="cancel-img js-cancel"></div>'+
					        '<div class="js-address-container address-container block block-list border-top-0">'+
					        	'<div class="js-address-item block-item" style="text-align:center;margin-left: -15px">正在加载中</div>'+
				         	'</div>'+
					        '<div class="add-address js-add-address">'+
					            '<span class="icon-add"></span>'+
					            '<a class="" href="javascript:;">新增地址</a>'+
					            '<span class="icon-arrow-right"></span>'+
					        '</div>'+
			         	'</div>'+
		         	'</div>'+
				'</div>';
			$('body').append(html);
			var $modal = $('#address_modal');
			address.updateListView(address.list);
			// 新增收货地址
			$modal.on('click', '.js-add-address', function(){
				address.show(address._default, true);
			});

			// 点击编辑收货地址
			$modal.on('click', '.js-edit-address', function(){
				var index = $(this).parent().data('index');
				address.show(address.list[index], true);
				return false;
			});

			// 选择收货地址
			$modal.on('click', '.js-address-item', function(){
				var $this = $(this);
				var index = $this.attr('data-index');
				address.selected = $.extend({}, address.list[index]);

				$this.addClass('address-selected').siblings().removeClass('address-selected');
				address.onSelect(address.selected);
				address.close();
				return false;
			});
		},
		show: function(checked, edit){
			if(this.list == null){
				this.getList();
			}
			this.close();
			if(typeof checked == 'object'){
				this._checked = checked.receiver_id;
			}

			if(edit){
				this.edit(checked);
			}else if(this.list != null && this.list.length == 0){
				this.edit();
			}else{
				this.showList();
			}

			$('#address_modal .js-cancel').on('click', function(){
				address.close();
			});
		},
		close: function(){
			$('body,html').css({'height': '', 'overflow': ''});
			$('#address_modal').remove();
		},
		onSelect: function(address){}
	}
	return address;
})
// 购物车
,define('buyer/cart', ["jquery", "h5/template/empty"],function($, et){
	var t = {
		cartList: null,
		editingList: null,
		action: '/pay/confirm',
		init: function(data){
			// 保留上次编辑的信息(选中、编辑中)
			var first = t.cartList == null;
			if(first){t.cartList = {}; t.editingList = {};}
			var newEditing = {}, newList = {};
			for(var i=0; i<data.groups.length; i++){
				var seller = data.groups[i];
				seller.editing = t.editingList[seller.shop_id] ? t.editingList[seller.shop_id] : '';
				for(var j=0; j<seller.products.length; j++){
					var item = seller.products[j];
					item.checked = !t.cartList[item.cart_id] ? 'checked' : t.cartList[item.cart_id].checked;
					newList[item.cart_id] = item;
				}
				newEditing[seller.shop_id] = seller.editing;
			}
			t.editingList = newEditing;
			t.cartList = newList;

			// 填充html
			t.renderList(data.groups, data.buyer);
			t.renderInvalid(data.invalidList, data.buyer);

			// 显示空数据
			if(data.groups.length == 0 && data.invalidList.length == 0){t._showEmpty()}
			// 重置汇总
			t.resetCount()

			if(data.pay_url){
				t.action = data.pay_url
			}
			t.buyer_id = data.buyer_id;
		},
		// 绑定有效购物车列表
		renderList: function(list, buyer){
			if(!list || list.length == 0){return $('#cart-container').html('')}

			var html = '';
			for(var i=0; i<list.length; i++){
				var items = '', seller = list[i];
				for(var j=0; j<seller.products.length; j++){
					var data = seller.products[j];
					items += ''+
					'<a href="'+data.link+'" class="js-goods-item name-card name-card-goods clearfix block-item" data-id="'+data.cart_id+'">'+
						'<div class="check-container"><span data-id="'+data.cart_id+'" class="check '+data.checked+(data.settlement.can_buy ? '': ' disabled')+'"></span></div>'+
				    	'<div class="thumb"><img class="js-view-image" src="'+data.pic_url+'"></div>'+
				    	'<div class="detail">'+
				        	'<div class="clearfix detail-row">'+
				            	'<div class="right-col text-right">'+
				                	'<div class="price">'+
										'<span class="price-prefix">'+data.view_price[0].prefix+'</span>'+
										'<em>'+data.view_price[0].price+'</em>'+
										'<span class="price-suffix">'+data.view_price[0].suffix+'</span>'+
									'</div>'+
									'<div class="num c-gray-darker">×<span class="num-txt">'+data.settlement.quantity+'</span></div>'+
			                	'</div>'+
			                	'<div class="left-col">'+
					                '<div class="goods-title"><h3 class="l2-ellipsis">'+data.title+'</h3></div>'+
					                '<div class="quantity"><button type="button" class="minus"'+(data.settlement.quantity <= 1 ? "disabled":"")+'></button><input type="text" value="'+data.settlement.quantity+'" data-value="'+data.settlement.quantity+'" min="1" max="'+data.quota+'" class="txt"><button type="button" class="plus"'+(data.settlement.quantity >= data.quota ? "disabled":"")+'></button><div class="response-area response-area-minus"></div><div class="response-area response-area-plus"></div></div>'+
					            '</div>'+
					        '</div>'+
					        '<div class="clearfix detail-row">'+
					            '<div class="right-col"></div>'+
					            '<div class="left-col">'+
					                '<p class="c-gray-darker sku">'+data.spec+'</p>'+
					            '</div>'+
					        '</div>'+
					        '<div class="clearfix detail-row">'+
					        	'<div class="right-col main-tag">'+data.main_tag+'</div>'+
					            '<div class="left-col error-box">'+data.settlement.errmsg+'</div>'+
					        '</div>'+
					    '</div>'+
					    '<div class="delete-btn"><span>删除</span></div>'+
					'</a>'
				}

				html += ''+
				'<div class="block block-order '+seller.editing+'" data-id="'+seller.shop_id+'">'+
					'<div class="header">'+
						'<a class="font-size-12" href="javascript:;">'+seller.shop_name+'</a>'+
						'<a href="javascript:;" class="js-edit pull-right c-orange font-size-12">'+(seller.editing ? '完成' : '编辑')+'</a>'+
					'</div>'+
					'<div>'+items+'</div>'+
				'</div>'
			}

			$('#cart-container').html(html);
		},
		// 绑定失效宝贝数据
		renderInvalid: function(list, buyer){
			if(!list || list.length == 0){return $('#invalid-container').html('')}

			var items = '';
			for(var i=0; i<list.length; i++){
				var data = list[i];
				items += ''+
				'<div class="js-goods-item order-goods-item clearfix block-list">'+
					'<a href="'+data.link+'" class="name-card name-card-goods clearfix block-item">'+
						'<div class="check-container"><span class="check checked disabled"></span></div>'+
				    	'<div class="thumb"><img class="js-view-image" src="'+data.pic_url+'"></div>'+
				    	'<div class="detail">'+
				        	'<div class="clearfix detail-row">'+
				            	'<div class="right-col text-right">'+
				                	'<div class="price">'+
										'<span class="price-prefix">'+data.view_price[0].prefix+'</span>'+
										'<em>'+data.view_price[0].price+'</em>'+
										'<span class="price-suffix">'+data.view_price[0].suffix+'</span>'+
									'</div>'+
			                	'</div>'+
			                	'<div class="left-col">'+
					                '<div><h3 class="l2-ellipsis">'+data.title+'</h3></div>'+
					            '</div>'+
					        '</div>'+
					        '<div class="clearfix detail-row">'+
					            '<div class="right-col">'+
					                '<div class="num c-gray-darker">×<span class="num-txt">'+data.settlement.quantity+'</span></div>'+
					            '</div>'+
					            '<div class="left-col">'+
					                '<p class="c-gray-darker sku">'+data.spec+'</p>'+
					            '</div>'+
					        '</div>'+
					        '<div class="clearfix detail-row">'+
				        		'<div class="right-col main-tag">'+data.main_tag+'</div>'+
					            '<div class="left-col error-box">'+data.settlement.errmsg+'</div>'+
					        '</div>'+
					    '</div>'+
					'</a>'+
				'</div>'
			}

			var html = ''+
				'<div class="block block-order">'+
					'<div class="header">'+
						'<a class="font-size-12" href="javascript:;">失效宝贝</a>'+
						'<a href="javascript:;" class="js-clear-invalid pull-right c-orange font-size-12 edit-list">清空失效宝贝</a>'+
					'</div>'+
					'<div>'+items+'<div>'+
				'</div>';

			$('#invalid-container').html(html);
		},
		_showEmpty: function(){
			$("#empty-container").html(et.getHtml({title: '还没有添加商品 T.T', notice: '快给我挑点宝贝'}))
		},
		_bindEvent: function(){
			var $container = $('#cart-container');
			// 编辑
			$container.on('click', '.js-edit',function(){
				var $this = $(this),
				$seller = $this.parent().parent(),
				sellerId = $seller.data('id');

				$seller.toggleClass('editing');
				var editing = $seller.hasClass('editing');
				$this.html(editing ? '完成' : '编辑');
				t.editingList[sellerId] = editing ? 'editing' : '';
				return false;
			})
			// 删除
			.on('click', '.delete-btn',function(){
				var $this = $(this),
			    $parent = $this.parents('.js-goods-item:first'),
			    cartId = $parent.data('id'),
			    siblings = $parent.siblings().length;

				t.deleteOne(cartId, function(data){
					t.init(data);
				});
				return false;
			})
			// 改变数量
			.on('click', '.response-area', function(){
				var $this = $(this),
				$item = $this.parents('.js-goods-item:first');
				t.onQuantityChange($item, $this.hasClass('response-area-plus') ? '+1' : '-1');
				return false;
			}).on('change', '.txt', function(){
				var $this = $(this),
				$item = $this.parents('.js-goods-item:first');
				t.onQuantityChange($item, $this.val());
				return false;
			})
			// 选中
			.on('click', '.check-container', function(){
				var $check = $(this).children(),
				$cart = $check.parents('.js-goods-item:first'),
				cartId = $cart.data('id');
				if(!$check.hasClass('disabled')){
					$check.toggleClass('checked');
					t.cartList[cartId].checked = $check.hasClass('checked') ? 'checked' : '';
					t.resetCount();
				}
				return false;
			});

			// 清空失效宝贝
			$('#invalid-container').on('click', '.js-clear-invalid', function(){
				t.clearInvalid();
				return false;
			});

			// 全选
			$(".js-select-all").on("click", function(){
				var $check = $(this).children();
				if(!$check.hasClass("disabled")){
					// var $checkList = 
					checked = !$check.hasClass('checked');
					// index = 0;
					for(var cartId in t.cartList){

						if(t.cartList[cartId].settlement.can_buy){
							$checkList = $("#cart-container .check[data-id='"+t.cartList[cartId].cart_id+"']");
							t.cartList[cartId].checked = checked ? 'checked' : '';
							if(checked){
								if($checkList.hasClass(".disabled")){}else{$checkList.addClass('checked')}
							}else{
								if($checkList.hasClass(".disabled")){}else{$checkList.removeClass('checked')}
							}
						}
						// index++;
					}
					t.resetCount();
				}
				return false;
			});

			// 结算按钮
			t.$btnPay = $(".js-go-pay");
			t.$btnPay.on("click", function(){
				return t.submit(), false
			});
		},
		// 结算
		submit: function(){
			var list = [];
			for(var cartId in t.cartList){
				var data = t.cartList[cartId];
				if(!data.settlement.can_buy || !data.checked){continue}

				var p = {goods_id: data.goods_id, product_id: data.product_id, quantity: data.settlement.quantity, cart_id: data.settlement.cart_id, activity_id: data.activity_id};
				list.splice(0, 0, p);
			}

			if(list.length == 0){
				return false
			}

			var products = JSON.stringify(list);
			$.ajax({
				url: get_url('/cart/submit'),
				type: 'post',
				data: {products: products},
				dataType: 'json'
			});
		},
		// 清空失效宝贝
		clearInvalid: function(){
			$.ajax({
				url: get_url('/cart/invalid'),
				type: 'post',
				success: function(){
					$('#invalid-container').html('');
				}
			});
		},
		// 删除某个购物车记录
		deleteOne: function(cartId, callback){
			$.ajax({
				url: get_url('/cart/delete'),
				data: {id: cartId},
				type: 'post',
				success: function(data){
					delete t.cartList[cartId];
					callback(data);
				}
			});
		},
		// 数量改变
		onQuantityChange: function($item, value){
			var $num = $item.find('.txt'),
			old = $num.data('value'),
			quantity = 0,
		    cartId = $item.data('id'),
		    min = $num.attr('min')*1,
		    max = $num.attr('max')*1;

			old = !/\d+/.test(old) ? 1 : old*1;
			value = !/\d+/.test(value) ? 1 : value*1;

			if(value == '-1'){quantity = old-1}
			else if(value == '+1'){quantity = old+1}
			else if(/\d+/.test(value)){quantity = value*1}

			if(quantity < min){quantity = min}
			if(quantity > max){quantity = max}

			$num.val(quantity);
			if(quantity == old){return false}
			$num.data('value', quantity);
			$num.siblings('.minus').attr('disabled', quantity <= min);
			$num.siblings('.plus').attr('disabled', quantity >= max);

			// 通知服务器数据变更
			$.ajax({
				url: get_url('/cart/update'),
				data: {id: cartId, quantity: quantity},
				type: 'post',
				success: function(data){t.init(data)}
			});
		},
		// 重置汇总数据
		resetCount: function(){
			var totalFee = 0, totalScore = 0, total = 0, disabled = 0,checked = 0, jiesuan = 0;
			for(var cartId in t.cartList){
				total++;
				var data = t.cartList[cartId];
				if(!data.settlement.can_buy){disabled++; continue;}
				else if(!data.checked){continue}

				checked++;
				jiesuan++;
				totalScore = totalScore.bcadd(data.total_score, 2);
				totalFee = totalFee.bcadd(data.total_fee, 2);
			}

			totalFee*=1;
			totalScore*=1;
			t.$btnPay.html("结算("+jiesuan+")").attr('disabled', jiesuan == 0);
			var $total = t.$btnPay.prev();
			if(totalFee > 0 && totalScore > 0){
				$total.html("合计："+totalFee+"元 + "+totalScore+"积分");
			}else if(totalFee > 0){
				$total.html("合计："+totalFee+"元");
			}else if(totalScore > 0){
				$total.html("合计："+totalScore+"积分");
			}else{
				$total.html("合计：0");
			}

			var $totalCheck = $(".js-select-all"), $checked = $totalCheck.find(".check");
			if(disabled + checked == total){$checked.addClass('checked')}
			else{$checked.removeClass('checked')}

			if(disabled == total){$checked.addClass('disabled')}
			else{$checked.removeClass('disabled')}

			if(jiesuan == 0){$totalCheck.removeClass('checked')}
			else{$totalCheck.addClass('checked')}
		}
	};

	// 绑定默认监听事件
	return t._bindEvent(), t
})
// 订单列表
,define('buyer/order/list', ["h5/pullrefresh"], function(pullfresh){
	var t = {
		option: {},
		getActive: function(_default){
			var key = pullfresh.info.key;
			return key ? key : _default;
		},
		doRefresh: function(data){
			t.option = $.extend({}, data);
			data.success = t.onLoadSuccess;
			pullfresh.doRefresh(data);
		},
		onLoadSuccess: function(list, page, size){
            var html = t.getTradeHTML(list, page), $html = $(html);

            if(page == 1){
            	this.html($html);
            }else{
            	this.append($html);
            }

            t.bindEvent($html);
            return list.length == size;
        },
        getTradeHTML: function(list, page){
        	if(page == 1 && (!list || list.length == 0)){
        		return '<div class="empty-list list-finished"style="padding-top:60px;"><div><h4>居然还没有订单</h4><p class="font-size-12">好东西，手慢无</p></div><div><a href="'+get_url('/mall')+'"class="tag tag-big tag-orange"style="padding:8px 30px;">去逛逛</a></div>';
        	}

        	var html = '';
        	for(var i=0; i<list.length; i++){
        		var trade = list[i], orders = trade.orders, project = trade.project;

        		html += '<div class="block block-list block-order">'+
                			'<div class="header"><span>订单号：'+trade.tid+'</span><span class="pull-right">'+trade.status_str+'</span></div>';

        		// 子订单
		        for(var j=0; j<orders.length; j++){
		        	var order = orders[j];
		        	html += ''+
		        		'<a href="'+trade.detail_url+'" class="name-card name-card-goods clearfix block-item">'+
		                    '<div class="thumb"><img class="js-lazy" data-original="'+order.pic_url+'"></div>'+
		                    '<div class="detail">'+
		                        '<div class="clearfix detail-row">'+
		                            '<div class="right-col text-right">';
		        	for(var p=0; p<order.view_price.length; p++){
			        	var vp = order.view_price[p];
	                    html += '<div class="price"><span class="price-prefix">'+vp.prefix+'</span><em>'+vp.price+'</em><span class="price-suffix">'+vp.suffix+'</span></div>';
		        	}
                            html += '</div>'+
		                            '<div class="left-col">'+
		                                '<div class="goods-title"><h3 class="l2-ellipsis">'+order.title+'</h3></div>'+
		                            '</div>'+
		                        '</div>'+
		                        '<div class="clearfix detail-row">'+
		                            '<div class="right-col">'+
		                                '<div class="num c-gray-darker">×<span class="num-txt">'+order.quantity+'</span></div>'+
		                            '</div>'+
		                            '<div class="left-col"><p class="c-gray-darker sku">'+order.spec+'</p></div>'+
		                        '</div>'+
		                        '<div class="clearfix detail-row">'+
		                            '<div class="right-col main-tag">'+order.main_tag+'</div>'+
		                            '<div class="left-col error-box"></div>'+
		                        '</div>'+
		                    '</div>'+
		                '</a>'
		        }

		        html += '<div class="block-item bottom" style="overflow:visible;line-height:27px;">';
				html += '<span>';
				html +='合计：';
		        if(trade.paid_score > 0){
		        	html += trade.paid_score+trade.project.score_alias;
		        }else{
		        	html += '¥'+trade.need_payment+'(含运费'+trade.total_postage+')';
		        }
				html += '</span>';
		        html += '<div class="opt-btn" data-tid="'+trade.tid+'">';
		        for(var b=0; b<trade.buttons.length; b++){
		        	var btn = trade.buttons[b];
		        	html += '<a class="btn btn-in-order-list '+btn['class']+'" href="'+btn.url+'" data-confirm="'+(btn.confirm ? btn.confirm : '')+'">'+btn.text+'</a>';
		        }
		        html += '</div></div></div>';
        	}
        	return html;
        },
        bindEvent: function($html){
        	// 图片懒加载
            requirejs(['lazyload'], function(){
				$html.find(".js-lazy").lazyload({
					placeholder : "/img/logo_rgb.jpg",
				    threshold : 270
				});
			});

            // 取消订单
            $html.on('click', '.js-cancel-trade', t.cancelTrade);
            // 删除订单
            $html.on('click', '.js-delete-trade', t.deleteTrade);
            // 确认收货
            $html.on('click', '.js-confirm-goods', t.confirmGoods);
            // 崔单
            $html.on('click', '.js-reminder-trade', t.reminderTrade);
        },
        tradeDoAction: function(){
    		var $this = $(this), tid = $this.parent().data('tid');

        	$.ajax({
        		url: $this.attr('href'),
        		data: {tid: tid},
        		type: 'post',
        		dataType: 'json',
        		success: function(){
        			pullfresh.doRefresh(true);
        		}
        	});
        },
        cancelTrade: function(){
        	if(!confirm('取消订单后已支付部分将自动退回，确定取消订单吗？')){
        		return false
        	}

        	return t.tradeDoAction.apply(this),false;
        },
        deleteTrade: function(){
        	if(!confirm('删除之后无法恢复，确定继续操作吗？')){
        		return false
        	}
        	return t.tradeDoAction.apply(this),false;
        },
        confirmGoods: function(){
        	if(!confirm('确定确认收货吗？')){
        		return false
        	}
        	return t.tradeDoAction.apply(this),false;
        },
        reminderTrade: function(){
        	return t.tradeDoAction.apply(this),false;
        }
	};
	return t;
})
// 购物车是否有东西
,define("buyer/cart/num", ["jquery"], function(){
	$.ajax({
		url:get_url('/cart/num'),
		dataType:'json',
		success:function(data){
			if(data.num > 0){
				$('.my_chat_link').addClass('on');
			}else{
                $('.my_chat_link').removeClass('on');
			}
			var $gc = $('#global-cart');
			if(data.total > 0){
				$gc.removeClass('hide')
			}else{
				$gc.addClass('hide')
			}
		}
	})
})
,define('buyer/goods/template', function(){
	return {
		"default": function(list, page, bigSplit){
			if(list.length == 0 && page == 1){
				return '<li><div class="empty-list"><div><h4>小店暂无任何商品</h4><p class="font-size-12"></p></div></div></li>';
			}
			// 显示大图
			if(bigSplit == undefined){bigSplit=true}
			var html = tagHtml = '', class_name = 'small-pic', index = 0;
			for(var i=0; i< list.length; i++){
				var goods = list[i], imagehtml = '';
				if(goods.images){
					for(var j=0; j<goods.images.length; j++){
						imagehtml += '<div class="swiper-slide"><img src="'+goods.images[j]+'"></div>';
					}
				}else{
					imagehtml += '<div class="swiper-slide"><img src="'+goods.pic_url+'"></div>';
				}

				if(bigSplit){
					index++;
					class_name = index == 1 ? 'big-pic' : 'small-pic';

					if(i + 1 == list.length){
						if(index % 2 == 0){
							class_name = 'big-pic';
						}
					}else if(index == 19){
						index = 0;
					}
				}else if(i + 1 == list.length && (i+1) % 2 != 0){
					class_name = 'big-pic';
				}

				tagHtml = '';
				if(goods.tags){
					var tags = goods.tags;
					for(var j=0; j<tags.length; j++){
						tagHtml += '<span class="tag tag-red">'+tags[j]+'</span>';
					}
				}

				html += '<li class="js-goods-card goods-card card '+ class_name+'" data-id="'+goods.goods_id+'">';
				html += '	<a href="'+goods.link+'" class="link">';
				html += '		<div class="photo-block">';
				if(goods.main_tag){
					html += '<div class="sm-offer-imgtags"><div class="left-tag yjdf">'+goods.main_tag+'</div></div>';
				}
				html += '<div class="goods-main-image">';
				html+= '           <img class="goods-photo js-goods-lazy" data-original="'+goods.pic_url+'"><script type="text/html">'+imagehtml+'</script>';
				if(goods.stock == 0){
					html += '<div class="item-badge"><div class="badge-content"><div class="badge-title">已售罄</div><div class="badge-info">SOLDOUT AGAIN</div></div></div>';
				}
				html+= '        </div></div>';
				html += '		<div class="info clearfix info-price">';
				html += '			<div class="goods-title">'+tagHtml+goods.title+'</div>';
				html += '			<p class="goods-sub-title c-black hide">'+goods.digest+'</p>';

				// 售价
				html += '           <div class="goods-price">';
				var vp = goods.view_price[0];
				if(vp.title){html += '<span class="price-title">'+vp.title+'：</span>'}
				if(vp.prefix){html += '<span class="price-prefix">'+vp.prefix+'</span>'}
				html += '<em>'+vp.price+'</em>';
				if(vp.suffix){html += '<span class="price-suffix">'+vp.suffix+'</span>'}
				html += '           </div>';

				// 原价
				if(goods.view_price.length > 1){
					html += '           <div class="goods-original-price hide">';
					vp = goods.view_price[1];
					if(vp.title){html += '<span class="price-title">'+vp.title+'：</span>'}
					if(vp.prefix){html += '<span class="price-prefix">'+vp.prefix+'</span>'}
					html += '<em>'+vp.price+'</em>';
					if(vp.suffix){html += '<span class="price-suffix">'+vp.suffix+'</span>'}
					html += '           </div>';
				}

				html += '		</div>';
				html += '		<div class="goods-buy btn1 info-title"></div>';
				html += '		<div class="js-goods-buy buy-response" data-url="'+goods.sku_url+'"></div>';
				html += '	</a>';
				html += '</li>';
			}

			return html;
		},
		"progress": function(list, page){
			if(list.length == 0 && page == 1){
				return '<li><div class="empty-list"><div><h4>居然都没啦</h4><p class="font-size-12">好东西，手慢无</p></div><div><a href="" class="js-refresh tag tag-big tag-orange">刷新</a></div></div></li>';
			}

			var html = '', status_html = '', price = '';
			for(var i=0; i<list.length; i++){
				var data = list[i];

				if(data.hide_progress){
					status_html =
						'<div class="countdown">'+
	                    '<span class="text">距'+(data.countdown.type == 'start' ? '开始' : '结束')+': </span>'+
	                    '<span class="time" data-start="'+data.countdown.start+'" data-end="'+data.countdown.end+'"><em>0</em>天<em>0</em>小时<em>0</em>分<em>0</em>秒</span>'+
	                	'</div>';
				}else{
					status_html = '	<div class="status-bar">';
					status_html += '		<div class="status-progress" style="width:'+data.progress+'%;"></div>';
					status_html += '		<div class="status-soldrate">'+data.progress+'%</div>';
					status_html += '	</div>';
				}

				price = '<span class="goods-price">'+
							'<span class="price-prefix">'+data.view_price[0].prefix+'</span>'+
							'<em>'+data.view_price[0].price+'</em>'+
							'<span class="price-suffix">'+data.view_price[0].suffix+'</span>'+
						'</span>'+
						'<del class="orginal-price">'+
							'<span class="price-prefix">'+data.view_price[1].prefix+'</span>'+
							'<em>'+data.view_price[1].price+'</em>'+
							'<span class="price-suffix">'+data.view_price[1].suffix+'</span>'+
						'</del>'

				html += ''+
				'<a href="'+data.link+'" class="name-card name-card-goods" data-id="'+data.goods_id+'">'+
			    	'<div class="thumb"><img class="js-view-image" src="'+data.pic_url+'"></div>'+
			    	'<div class="detail">'+
			    		'<div class="goods-title">'+data.title+'</div>'+
			    		'<div class="detail-row"><div class="error-box">'+data.notice+'</div></div>'+
				        '<div class="clearfix detail-row" style="line-height:24px">'+
				            '<div class="right-col"><div class="action">马上抢<i></i></div></div>'+
				            '<div class="left-col">'+price+'</div>'+
				        '</div>'+
				        '<div class="clearfix detail-row">'+
				        	'<div class="right-col sold-num" style="margin-right:3px">'+(data.hide_sold ? '手慢无' : data.sold+'件已抢')+'</div>'+
				            '<div class="left-col">'+status_html+'</div>'+
				        '</div>'+
				    '</div>'+
				'</a>'
			}

			return html;
		}
	}
})
,define("buyer/goods/list", ["jquery", "h5/pullrefresh", "buyer/goods/template"], function($, pullrefresh, template){
	var t = {
		getActive: function(_def){
			var key = pullrefresh.info.key;
			return key ? key : _def;
		},
		doRefresh: function(params){
			params.dataType = 'json';
			params.success = function(list, page){
				var $container = params.container,
				html = template[params.template ? params.template : "default"](list, page),
				$html = $(html);

				if(page == 1){
					$container.html($html);
				}else{
					var $children = $container.children(), index = $children.length - 1;
					$children.each(function(i){
						var _page = $children.eq(i).data('page');
						if(_page > page){
							index = i - 1;
							return false;
						}
					});
					$children.eq(index).after($html);
				}

				t.bindEvent($html);
				return list.length == params.data.size;
			}
			pullrefresh.doRefresh(params);
		},
		bindEvent: function($html){
			requirejs(['lazyload'], function(){
				$html.find(".js-goods-lazy").lazyload({
					placeholder : "/img/logo_rgb.jpg",
				    threshold : 270
				});
			});

			t.countdown($html);

			// 绑定加入购物车事件
			require(['buyer/skumodal'], function(skumodal){
				$html.on('click', '.js-goods-buy', function(){
					var $this = $(this), $prev = $this.prev();
					if($prev.hasClass('ajax-loading')){
						return false;
					}

					$prev.addClass('ajax-loading');
					skumodal.init($this.data('url'), function(){
						$prev.removeClass('ajax-loading');
					});
					return false;
				});
			});
		},
		countdown: function($html){
			var $elementList = $html.find('.countdown>.time');
			if($elementList.length == 0){
				return;
			}

			var timerList = {}, nowTime = Date.parse(new Date()) / 1000;
			$elementList.each(function(i, element){
				var data = $elementList.eq(i).data(),
					timer = data.end;
				if(nowTime >= data.end){
					return true;
				}else if(!timerList[timer]){
					timerList[timer] = {end: data.end*1000, element: [element]};
				}else{
					timerList[timer].element.push(element);
				}
			});

			for(var key in timerList){
				var start = Date.parse(new Date()),
					data = timerList[key], html = '';
				var timer = window.setInterval(function(){
					start += 1000;

			        var leftTime = data.end - start,
			        leftsecond = parseInt(leftTime/1000),
			        day=Math.floor(leftsecond/(60*60*24)),
			        hour=Math.floor((leftsecond-day*24*60*60)/3600),
			        minute=Math.floor((leftsecond-day*24*60*60-hour*3600)/60),
			        second=Math.floor(leftsecond-day*24*60*60-hour*3600-minute*60);

			        if(leftTime == 0){ window.clearInterval(timer)}

			        html = (day > 0 ? '<em>'+day+'</em>天' : '') + '<em>'+hour+'</em>小时<em>'+minute+'</em>分<em>'+second+'</em>秒';
			        for(var i=0; i<data.element.length; i++){
			        	data.element[i].innerHTML = html
			        }
			    }, 1000);
			}
		}
	}
	return t;
})
,define('buyer/search', ["jquery"], function(){
	var $searchBar = $('.search-bar'),
		$form = $searchBar.find('.search-form'),
		$title = $form.find('.search-input')
	   ,t = {
			data: {},
			onSearch: function(kw){
				window.location.href = get_url('/mall'+(kw ? '?kw='+kw : ''));
			},
			init: function(){
				var array = $form.serializeArray();
				for(var i=0; i<array.length; i++){
					if(array[i].value){
						t.data[array[i].name] = array[i].value;
					}
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
		t.onSearch(t.data.kw);
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

		require(['cookie'], function(){
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
,define('buyer/login_modal', ["jquery"], function(){
	var tpl = '<div id="login-modal" class="login-modal">'+
			'<script src="/js/flexible.js"></script>'+
		    '<div class="logo tb-logo"></div>'+
		    '<form id="loginForm" class="mlogin" method="post">'+
		        '<div class="am-list">'+
		            '<div class="am-list-item">'+
		                '<div class="am-list-control">'+
		                    '<input type="text" class="am-input-required js-username" placeholder="请输入手机号">'+
		                '</div>'+
		                '<div class="am-list-action">'+
		                    '<i class="am-icon-clear" style="display: none;"></i>'+
		                '</div>'+
		            '</div>'+
		            '<div class="am-list-item" style="display:none">'+
		                '<div class="am-list-control">'+
		                    '<input type="text" class="am-input-required am-input-required-checkCode js-auth-code" placeholder="校验码">'+
		                '</div>'+
		                '<div class="am-list-action am-list-action-msg"><i class="am-icon-clear" style="display: none;"></i></div>'+
		                '<div class="am-list-button">'+
		                    '<span class="getCheckcode">获取短信校验码</span>'+
		                '</div>'+
		            '</div>'+
		            '<div class="am-list-item">'+
		                '<div class="am-list-control">'+
		                    '<input type="password" class="am-input-required am-input-required-password js-password" placeholder="请输入密码">'+
		                '</div>'+
		                '<div class="am-list-action am-list-action-password">'+
		                    '<i class="am-icon-clear"></i>'+
		                '</div>'+
		                '<div class="pwd-show iconfont" id="show-pwd"></div>'+
		            '</div>'+
		        '</div>'+
		        '<div class="other-link">'+
		            '<div class="am-field am-footer">'+
		                '<a href="javascript:toast.show(\'暂不支持网页端注册\');" class="f-left"></a>'+
		                '<a href="javascript:toast.show(\'请联系平台客服处理，客服电话：18329046555\');" id="forget" class="f-right">忘记密码</a>'+
		            '</div>'+
		        '</div>'+
		        '<div class="am-field am-fieldBottom">'+
		            '<button type="submit" class="am-button am-button-submit js-submit">登 录</button>'+
		        '</div>'+
		    '</form>'+
	    '</div>';

	var modal = {
		redirect: '',
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
				this.apply = data.apply;
				this.share_mid = data.share_mid;
			}

			modal.bindEvent();

			if(win.isApp){
				window.uexOnload = function(type){
					uexWindow.setReportKey(0,1);
					uexWindow.onKeyPressed = function(){
						uexWidgetOne.exit();
					};
				}
			}
		},
		onLoginSuccess: function(){
			window.location.href = modal.redirect;
		},
		needCheckCode: false,
		bindEvent: function(){
			var $modal = modal.$modal = $('#login-modal'),
			$mobile = $modal.find('.js-username'),
			$authCode = $modal.find('.js-auth-code'),
			$password = $modal.find('.js-password')
			$submit = $modal.find('.js-submit'),
			$authView = $authCode.parent().parent();

			$mobile.val(this.mobile);
			$mobile.on('keyup', function(){
				var mobile = this.value;
				if(!/^1[3|4|5|7|8]\d{9}$/.test(mobile)){
					$authView.hide();
					return false;
				}

				$.ajax({
					url: get_url('/login/exists'),
					type: 'post',
					data: {mobile: mobile},
					dataType: 'json',
					waitting: false,
					success: function(result){
						modal.needCheckCode = result.code;
						if(result.code){
							$authView.show();
						}else{
							$authView.hide();
						}
						$submit.html(result.btn);
					}
				});
				return false;
			}).trigger('keyup');

			// 获取验证码
			$modal.find('.getCheckcode').on('click', function(){
				var btn = this;
				if(btn.disabled){
					return false;
				}

				var mobile = $mobile.val();
				btn.disabled = true;
				$.ajax({
					url: get_url('/login/code'),
					data: {mobile: mobile},
					type: 'post',
					waitting: false,
					datatype: 'json',
					success: function(){
						modal.daojishi(btn);
					},
					error: function(){
						btn.disabled = false;
					}
				});
				return false;
			});

			$modal.find('form').on('submit', function(){
				var data = {
					mobile: $mobile.val(),
					code: $authCode.val(),
					password: $password.val(),
					redirect: modal.redirect,
					apply: modal.apply,
					share_mid: modal.share_mid,
				};

				if(!/^1[3|4|5|7|8]\d{9}$/.test(data.mobile)){
					return toast.show('请输入11位手机号'), false
				}

				if(modal.needCheckCode && !/^\d{6}$/.test(data.code)){
					return toast.show('请输入验证码'), false
				}

				var password = data.password.split('');
				if(password.length < 6 || password.length > 20){
					return toast.show('请输入6-20位密码'), false
				}

				var pwdArray = [];
				for(var i=0; i<password.length; i++){
					if(pwdArray.indexOf(password[i]) == -1){
						pwdArray.push(password[i]);
					}
				}
				if(pwdArray.length < 5){
					return toast.show('密码过于简单'), false
				}

				modal.dologin(data, this);
				return false;
			});
		},
		dologin: function(data, btn){
			btn.disabled = true;

			$.ajax({
				url: get_url('/login/auth'),
				type: 'post',
				data: data,
				dataType: 'json',
				success: function(result){
					modal.loginSuccess();
				},
				complete: function(){
					btn.disabled = false;
				}
			});
		},
		daojishi: function(btn){
			var times = 60;
			btn.innerHTML = times + '秒后重新获取';

			var timer = setInterval(function(){
				times--;
				btn.innerHTML = times + '秒后重新获取';
				if(times == 0){
					clearInterval(timer);
					btn.innerHTML = '重新获取校验码';
					btn.disabled = false;
				}
			}, 1000);
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
					url: get_url('/login/bind'),
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
			modal.onLoginSuccess();
		}
	};
	return modal;
})
,define('buyer/goods/rate', ["jquery", 'h5/pullrefresh'], function($, pullfresh){
	var t = {
		init: function(goods_id, container){
			var $container = $(container), $tabber = $('.js-rate-tabber');
			$tabber.find('button').on('click', function(){
				var $this = $(this), type = $this.data('type');
				pullfresh.doRefresh({
					refresh: false,
					url: get_url('/rate'),
					data: {id: goods_id, size: 20, type: type},
					container: $container,
					dataType:"json",
					cache: false,
					success: function(list, page){
						t.show(list, page, $container);
						return list.length >= 20;
					}
				});

				document.body.scrollTop = $tabber.offset().top - 42;
				$this.addClass('active').siblings().removeClass('active');
			}).eq(0).trigger('click');
		},
		show: function(list, page, $container){
			var html = '';
			if(page == 1 && list.length == 0){
				html = '<li class="item"><div class="empty-list list-finished"><h4>暂无评价内容</h4><p class="font-size-12">确认收货后即可参与评价哦</p></div></li>';
				$container.html(html);
				return;
			}

			for(var i=0; i<list.length; i++){
				var data = list[i];
				html +=
				'<li class="item">'+
	            '<div class="info">'+
	            	'<div class="author">'+
                    	'<span class="nike">'+data.nickname+'</span>'+
                    	'<time>'+data.created+'</time>'+
                    '</div>'+
	            '</div>'+
	            '<blockquote>'+data.feedback+'</blockquote>'+
	            '<ul class="pics">';
				for(var j=0; j<data.images.length; j++){
		            html += '<li><img data-original="'+data.images[j]+'" class="js-lazy"></li>';
				}
	            html += '</ul>'+
	            '<div class="sku">'+data.spec+'</div>'+
	            '</li>';
			}

			if(page == 1){
				$container.html(html);
			}else{
				$container.append(html);
			}

			require(['lazyload'], function(){
				$container.find(".js-lazy").lazyload({
					placeholder : "/img/logo_rgb.jpg",
				    threshold : 270
				});
			});
		}
	}
	return t;
})
,define('buyer/college', ["jquery", 'h5/pullfresh'], function($, pullfresh){
	var t = {
		$container: null,
		cacheKey: 'goods_college',
		init: function(){
			t.$container = $('.js-notice-container');
			t.cacheKey = 'notice_list';
			pullfresh.init({
				refresh: true,
				size: 30,
				container: t.$container,
				onLoad: function(parameters, isRefresh){
					t.loadData(parameters, pullfresh, isRefresh);
				}
			});

			t.pullfresh = pullfresh;
		},
		loadData: function(params, pullfresh, isRefresh){
			if(!isRefresh && params.page == 1 && !!history.state && !!history.state[t.cacheKey]){
				var historyData = history.state;
				var data = historyData[t.cacheKey];
				t.parseList(data, true);
				document.body.scrollTop = data.scrollTop;
				return false;
			}

			$.ajax({
				url: get_url('/college'),
				data: params,
				dataType: 'json',
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
		parseList: function(params, first){
			var html = '';
			if(first && (!params.list || params.list.length == 0)){
				html = '<div class="empty-list list-finished" style="padding-top:60px;"><div><h4>暂无公告信息</h4><p class="font-size-12">好东西，手慢无</p></div><div><a href="/mall" class="tag tag-big tag-orange" style="padding:8px 30px;">去商城逛逛</a></div></div>';
			}else{
				for(var i=0; i<params.list.length; i++){
					var data1 = params.list[i];
					html +='<div class="notice-group"><h3 class="notice-group-title">'+data1['title']+'</h3><div class="notice-group-container">';
					for(var k=0; k<data1.rows.length; k++){
						var data = params.list[i]['rows'][k];
						html+='<a class="notice-item" href="/college/detail?id='+data.id+'&redirect='+encodeURIComponent(data.url)+'" target="_blank">'+
		                    '<div class="notice-title">'+data.title+'</div>'+
		                    '<div class="notice-info">'+
		                    	'<span class="notice-tag">';
		                    	for(var j=0; j<data.tag.length; j++){
		                    		html += '<span class="tag tag-orange">'+data.tag[j]+'</span>'
		    					}
		                    	html +=
		                    	'</span>'+
		                    	'<span class="notice-created">发布：'+data.created+'</span>'+
		                    	'<span class="notice-pv">'+data.pv+'查看</span>'+
		                    '</div>'+
		                '</a>';
					}
					html += '</div></div>';
				}
			}

			if(first){
				t.$container.html(html);
			}else{
				t.$container.append(html);
			}

			pullfresh.page = params.page;
			pullfresh.setNoMore(params.noMore);
		}
	}

	t.init();
	return t;
})
,define('buyer/notice', ["jquery", 'h5/pullfresh'], function($, pullfresh){
	var t = {
		$container: null,
		cacheKey: 'goods_notice',
		init: function(){
			t.$container = $('.js-notice-container');
			t.cacheKey = 'notice_list';
			pullfresh.init({
				refresh: true,
				size: 30,
				container: t.$container,
				onLoad: function(parameters, isRefresh){
					t.loadData(parameters, pullfresh, isRefresh);
				}
			});

			t.pullfresh = pullfresh;
		},
		loadData: function(params, pullfresh, isRefresh){
			if(!isRefresh && params.page == 1 && !!history.state && !!history.state[t.cacheKey]){
				var historyData = history.state;
				var data = historyData[t.cacheKey];
				t.parseList(data, true);
				document.body.scrollTop = data.scrollTop;
				return false;
			}

			$.ajax({
				url: get_url('/notice'),
				data: params,
				dataType: 'json',
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
		parseList: function(params, first){
			var html = '';
			if(first && (!params.list || params.list.length == 0)){
				html = '<div class="empty-list list-finished" style="padding-top:60px;"><div><h4>暂无公告信息</h4><p class="font-size-12">好东西，手慢无</p></div><div><a href="/mall" class="tag tag-big tag-orange" style="padding:8px 30px;">去商城逛逛</a></div></div>';
			}else{
				for(var i=0; i<params.list.length; i++){
					var data = params.list[i];
					html +=
						'<a class="notice-item" href="/notice/detail?id='+data.id+'&redirect='+encodeURIComponent(data.url)+'" target="_blank">'+
		                    '<div class="notice-title">'+data.title+'</div>'+
		                    '<div class="notice-info">'+
		                    	'<span class="notice-tag">';
		                    	for(var j=0; j<data.tag.length; j++){
		                    		html += '<span class="tag tag-orange">'+data.tag[j]+'</span>'
		    					}
		                    	html +=
		                    	'</span>'+
		                    	'<span class="notice-created">发布：'+data.created+'</span>'+
		                    	'<span class="notice-pv">'+data.pv+'查看</span>'+
		                    '</div>'+
		                '</a>';
				}
			}

			if(first){
				t.$container.html(html);
			}else{
				t.$container.append(html);
			}

			pullfresh.page = params.page;
			pullfresh.setNoMore(params.noMore);
		}
	}

	t.init();
	return t;
})
,define('buyer/pay_coupon', ["jquery"], function(){
	var model = {
		scrollTop: 0,
		$modal: null,
		$list: null,
		$tip: null,
		checked: [],
		reset: function(list){
			this.checked = [];
			if(list.length == 0){
				this.$list.html('<li class="block-item none">无优惠可用</li>');
				return;
			}

			var html = '';
			for(var i=0; i<list.length; i++){
				var data = list[i];

				if(data.checked){
					this.checked.push(i);
				}

				html += '<li data-index="'+i+'" class="block-item coupon-item'+(data.checked ? ' active' : '')+'">'+
							'<div class="label-check-img"></div>'+
							'<div class="coupon-info">'+
								'<p class="font-size-12">'+list[i].name+'<em class="pull-right">'+(data.discount_fee > 0 ? '-'+data.discount_fee : '促销活动')+'</em></p>'+
								'<p class="font-size-12 c-gray-darker">'+list[i].description+'<em class="pull-right"></em></p>'+
							'</div>'+
						'</li>';
			}
			this.$list.html(html);
		},
		show: function(list, callback_onchanged){
			var html = '<div>';
			html += '<div class="modal-backdrop js-close"></div>';
			html += '<div class="modal popup coupon-popup">';
			html += '	<div class="js-scene-coupon-list">';
			html += '		<div class="header"><span class="js-tip">店铺优惠</span><span class="js-close cancel-img"></span></div>';
			html += '		<div class="block block-list border-0">';
			html += '			<div class="js-code-inputer coupon-input-container block-item">';
			html += '				<input class="js-code-txt txt txt-coupon font-size-14" type="text" placeholder="请输入优惠码" autocapitalize="off" maxlength="20">';
			html += '				<button class="js-valid-code coupon-valid btn btn-white font-size-14" type="button">兑换</button>';
			html += '			</div>';
			html += '			<ul class="js-coupon-list coupon-list"></ul>';
			html += '		</div>';
			html += '	</div>';
			html += '	<div class="action-container coupon-action-container">';
			html += '		<button class="js-btn-ok btn btn-block btn-green" style="margin: 0px;">确定</button>';
			html += '	</div>';
			html += '</div></div>';

			this.scrollTop = document.body.scrollTop;
			$('body>.container').css("margin-top", "-"+document.body.scrollTop+"px");
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

			this.$list.children('.coupon-item').on('click', function(){
				var $this = $(this),
					index = $this.data('index');
				if(list[index].disabled){
					toast.show("必须选择");
					return false;
				}
				$this.toggleClass("active");
				return false;
			});

			// 确定
			$modal.find(".js-btn-ok").on("click", function(){
				var prev = model.checked, changed = false, checkedList = [];

				var $checked = model.$list.children('.active');
				for(var i=0; i<$checked.length; i++){
					var index = $checked.eq(i).data('index'),
						data = list[index];
					checkedList.push(index);
					if(prev.indexOf(index) == -1){
						changed = true;
					}
				}

				model.close();
				if(changed || prev.length != checkedList.length){
					callback_onchanged(checkedList);
				}
				return false;
			})
		},
		close: function(){
			$('body>.container').css("margin-top", "");
			$('body,html').css({'height': '', 'overflow': ''});
			this.$modal.remove();
			document.body.scrollTop = this.scrollTop;
		},
		onChanged: function(list){}
	};

	return model;
})
,define("buyer/payconfirm", ["jquery"], function($){
	// express-detail icon-address
	// sku
	//合计
	var t = {
		book_key: '',
		address:{
			receiver_id: '',
			receiver_name: '',
			receiver_mobile: '',
			receiver_province: '',
			receiver_city: '',
			receiver_county: '',
			receiver_detail: '',
			receiver_zip: '',
			province_code: '',
			city_code: '',
			county_code: ''
		},
		discount_fee:Math.round(Math.random()*9+1),
		groups: [],
		init: function(book_key){
			t.book_key = book_key;
			t.$address = $('.js-order-address');
			t.$listContainer = $('.js-goods-list-container');

			// 构建订单基础信息
			t.build();

			// 绑定事件
			t.bindEvent();
		},
		bindEvent: function(){
			// 收货地址改变
			require(["buyer/address"], function(modal){
				var $address = t.$address;
				$('.content').on('click', '.js-order-address', function(){
					var address = t.address;
					address.id = t.address.receiver_id;
					modal.show(address);
					return false;
				});

				// 选择收货地址
				modal.onSelect = function(data){
					t.setAddress(data, true);
				}
			});

			// 店铺优惠
			require(["buyer/pay_coupon"], function(model){
				$('.js-goods-list-container').on('click', '.js-discount',function(){
					var index = $(this).parents(".trade-item:first").attr("data-index").split("."),
						group = t.groups[index[0]],
						trade = group.trades[index[1]],
						discountList = trade.discount_details;
					model.show(discountList, function(changeList){
						for(var i=0; i<discountList.length; i++){
							discountList[i].checked = changeList.indexOf(i) > -1;
						}
						if(changeList.length > 0){
							$('#allow_discount_fee').data('type', 'allow_discount_fee').addClass('switch-on').prop('checked', true);
						}
						t.build();
					});
				});
			});

			// 配送方式改变
			t.$listContainer.on('change', '.js-express', function(){
				t.build();
				return false;
			});

			// 提交订单
			$('.js-order-total-pay').on('click', '.js-confirm', function(){
				t.createOrder();
				return false;
			});

			// 清空失效宝贝
			var $invalidList = $('.js-invalid-list-container');
			$invalidList.on('click', '.js-clear', function(){
				var $btn = $('.js-order-total-pay .js-confirm');
				var $this = $(this), products = $this.data('id');
				$.ajax({
					url: __PAY__ + '/order/clear?book_key='+t.book_key+'&products='+products,
					dataType: 'json',
					success: function(){
						$btn.removeAttr('disabled');
						$invalidList.html('')
					},
					error: function(){
						$invalidList.show()
					}
				});
				return false;
			});

			// 优惠汇总用户可选择是否使用
			$('.js-order-total').on('change', '.switch', function(){
				var $this = $(this);
				if($this.hasClass('js-anonymous')){
					t.anonymous = this.checked;
				}else{
					if($this.data('type') == 'allow_discount_fee'){
						$this.data('reset', $this.hasClass('switch-on') ? 1 : 0);
					}
					t.build();
				}
				return false;
			});
		},
		unbindEvent: function(show){
			t.$listContainer.unbind('click');
			$('.content').unbind('click');
			t.$listContainer.find('.js-express').remove();
			t.$listContainer.find('.js-msg-container').attr('disabled', 'disabled').css('background-color', '#fff');
			$('.js-order-total-pay').unbind('click').find('.js-confirm').html(show);
			$('.js-order-total').unbind('click');
		},
		getParam: function(){
			discount_fee_n = t.discount_fee.toFixed(4);
			var params = {book_key: t.book_key,discount_fee:discount_fee_n};
			if(t.groups.length > 0){
				params.adjust = {};
				params.address = t.address;

				var shop_id = '', freight_id = '', groups = t.groups, $switch = $('.js-order-total .switch'), field = '', isReset = false;
				$switch.each(function(i){
					field = $switch.eq(i).data('type');
					if(field){
						params[field] = $switch.eq(i).hasClass('switch-on') ? 1 : 0;
						if(field == 'allow_discount_fee'){
							isReset = $switch.eq(i).data('reset');
						}
					}
				});

				for(var gi=0; gi<groups.length; gi++){
					shop_id = groups[gi].shop_id;
					for(var ti=0; ti<groups[gi].trades.length; ti++){
						freight_id = groups[gi].trades[ti].freight_id;

						var key = shop_id+'_'+freight_id;
						params[key+'_express'] = $.trim($('#express_'+key).val());
						params[key+'_remark'] = $.trim($('#remark_'+key).val());

						// 允许使用优惠
						var list = groups[gi].trades[ti].discount_details;
						for(var i=0; i<list.length; i++){
							if(params['allow_discount_fee'] && list[i].checked){
								params[key+'_'+list[i].type] = list[i].id;
							}else if(!isReset && params[key+'_'+list[i].type] == undefined){
								params[key+'_'+list[i].type] = 0;
							}
						}
					}
				}
			}

			return params;
		},
		build: function(){
			var params = t.getParam();

			$.ajax({
				url: __PAY__ + '/order/buildorder',
				type: 'post',
				data: params,
				dataType: 'json',
				success: function(data){
					t.show(data);
					$('#page_loading').fadeOut(function(){
						$(this).remove()
					});
				},
//				error: function(){
//					setTimeout(function(){
//						history.go(-1);
//					},1500);
//				}
			});
		},
		createOrder: function(){
			var params = t.getParam();
			params.need_pay = t.need_pay;
			params.need_score = t.need_score;

			$.ajax({
				url: __PAY__ + '/order/buildorder',
				type: 'post',
				data: params,
				dataType: 'json',
				success: function(data){
					if(!data.submit){
						return t.show(data), null;
					}
					else{
						t.orderSuccess(data);
					}

				}
			});
		},
		anonymous: 0,
		show: function(data){
			var list = data.groups, address = data.address;
			t.groups = list;
			t.setAddress(address);
			t.resetView(list,data.isLB);
			$('#trade_message').html(data.message);

			var html = '', describe = data.describe, v1 = v2 = '';
			html += '<div class="block block-list">'+describe+'<div style="display: none" class="block-item"><span class="title-info">匿名购买</span><div class="js-anonymous switch '+(t.anonymous ? 'switch-on' : '')+' mini" data-type="anonymous"></div></div></div>';
			html += '<div class="sum-need-pay'+(data.hide_sum_need_pay ? ' hide' : '')+'"><div class="pay-arrow"></div><div class="block-item">';
			if(data.need_pay > 0 || data.need_score == 0){
				html += '<p><span>应付总额</span><span class="pull-right c-orange">¥'+data.need_pay+'</span></p>';
				var need_pay = data.need_pay.split('.');
				v1 += '<span class="c-orange"><span class="js-price font-size-16">¥'+need_pay[0]+'</span><span class="js-price-sub font-size-12">.'+need_pay[1]+'</span>';
			}
			if(data.need_score > 0){
				html += '<p><span>应付积分</span><span class="pull-right c-orange">'+data.need_score+'积分</span></p>';
				v2 += '<span class="c-orange"><span class="js-price font-size-16">'+data.need_score+'</span><span class="js-price-sub font-size-12">积分</span>';
			}
			html += '</div></div>';

			$('.js-order-total').html(html);


			html = '<div class="pull-right pull-margin-up">';
			html += '<span class="c-gray-darker font-size-16">共<span class="c-orange"> '+data.total_quantity+' </span>件，</span>';
			html += '<span class="c-gray-darker font-size-16">合计：</span>';
			if(t.express_err!=''){
				html += '<span class="c-orange"><span class="js-price font-size-16">¥0</span><span class="js-price-sub font-size-12"></span>';
			}else{
				html += v1;
			}

			if(v1 != '' && v2 != ''){
				html += '<span class="c-gray-darker font-size-16"> + </span>';
			}
			html += v2;
			html += '</span>';
			if(t.express_err!=''){
				html += '<button disabled="disabled" class="js-confirm1 btn btn-red-f44 commit-bill-btn">提交订单</button>';
			}else{
				html += '<button class="js-confirm btn btn-red-f44 commit-bill-btn">提交订单</button>';
			}
			html += '</div>';
			$('.js-order-total-pay').html(html);

			t.need_pay = data.need_pay;
			t.need_score = data.need_score;

			var $btn = $('.js-order-total-pay .js-confirm');
			if(data.has_error){
				$btn.attr('disabled', 'disabled');
			}else{
				$btn.removeAttr('disabled');
			}

			if(data.error != ''){
				toast.show(data.error);
			}

			t.showInvalidList(data.invalidList, list.length > 0);
		},
		showInvalidList: function(list, canContinue){
			var $container = $('.js-invalid-list-container');
			if(list.length == 0){
				$container.html('');
				return;
			}

			var html = '', products = [];
			for(var i=0; i<list.length; i++){
				var order = list[i];
				products.push(order.product_id);
				html += ''+
				'<a class="name-card name-card-goods clearfix block-item" href="'+order.link+'">'+
					'<div class="thumb"><img src="'+order.pic_url+'"></div>'+
					'<div class="detail">'+
						'<div class="clearfix detail-row">'+
							'<div class="right-col text-right">'+
								'<div class="price"><span class="price-prefix">'+order.view_price.prefix+'</span><em>'+order.view_price.price+'</em><span class="price-suffix">'+order.view_price.suffix+'</span></div>'+
								'<div class="num c-gray-darker">×<span class="num-txt">'+order.settlement.quantity+'</span></div>'+
							'</div>'+
							'<div class="left-col">'+
								'<div href="'+order.link+'" class="goods-title"><h3 class="l2-ellipsis">'+order.title+'</h3></div>'+
							'</div>'+
						'</div>'+
						'<div class="clearfix detail-row">'+
							'<div class="right-col"></div>'+
							'<div class="left-col" style="padding-right:0"><p class="c-gray-darker sku 12-ellipsis" style="width:100%;text-overflow:ellipsis;">'+order.spec+'</p></div>'+
						'</div>'+
						'<div class="clearfix detail-row">'+
							'<div class="right-col main-tag">'+order.main_tag+'</div>'+
							'<div class="left-col error-box">'+order.settlement.errmsg+'</div>'+
						'</div>'+
					'</div>'+
				'</a>';
			}

			var qingkong = canContinue ? '<a href="javascript:;" data-id="'+products.join(',')+'" class="js-clear pull-right c-orange font-size-12">清空</a>' : '';
			var view = '<div class="hr-text line" style="margin-bottom:10px"><span class="text">失效的宝贝</span></div>'+
					   '<div class="block block-list block-order" style="margin-bottom:10px">'+
							'<div class="header" style="padding-left:0px;"><span>失效的宝贝</span>'+qingkong+'</div>'+
							'<div class="trade-item">'+html+
							'</div>'+
						'</div>';
			if(canContinue){
				view += '<div class="hr-text line" style="margin-bottom:10px"><span class="text">有效的宝贝</span></div>';
			}
			$container.html(view);
		},
		resetView: function(groupList,isLB){
			var html = '';
			for(var i=0; i<groupList.length; i++){
				var group = groupList[i], tradeList = group.trades;
				html += '<div class="block block-list block-order" data-shop_id="'+group.shop_id+'">'+
							'<div class="header">'+
								'<span>店铺：'+group.shop_name+'</span>'+
							'</div>';
				for(var j=0; j<tradeList.length; j++){
					html += '<div class="trade-item" data-index="'+i+'.'+j+'">';
					var trade = tradeList[j], orders = trade.orders;
					for(var h=0; h<orders.length; h++){
						var order = orders[h];
						html += ''+
						'<div class="name-card name-card-goods clearfix block-item">'+
							'<div href="'+order.link+'" class="thumb"><img src="'+order.pic_url+'"></div>'+
							'<div class="detail">'+
								'<div class="clearfix detail-row">'+
									'<div class="right-col text-right">'+
										'<div class="price"><span class="price-prefix">'+order.view_price.prefix+'</span><em>'+order.view_price.price+'</em><span class="price-suffix">'+order.view_price.suffix+'</span></div>'+
										'<div class="num c-gray-darker">×<span class="num-txt">'+order.quantity+'</span></div>'+
									'</div>'+
									'<div class="left-col">'+
										'<div href="'+order.link+'" class="goods-title"><h3 class="l2-ellipsis">'+order.title+'</h3></div>'+
									'</div>'+
								'</div>'+
								'<div class="clearfix detail-row">'+
									'<div class="right-col"></div>'+
									'<div class="left-col" style="padding-right:0"><p class="c-gray-darker sku 12-ellipsis" style="width:100%;text-overflow:ellipsis;">'+order.sku_str+'</p></div>'+
								'</div>'+
								'<div class="clearfix detail-row">'+
									'<div class="right-col main-tag">'+order.main_tag+'</div>'+
									'<div class="left-col error-box"'+(order.gift_id ? ' style="padding-right:0"' : '')+'>'+order.errmsg+'</div>'+
								'</div>'+
							'</div>'+
						'</div>';
					}

					var expHTML = '', express_text = '';
					if(trade.express.length == 0){
						express_text = '请选择收货地址';
					}else{
						expHTML = '<select class="js-express" id="express_'+group.shop_id+'_'+trade.freight_id+'">';
						for(var k=0; k<trade.express.length; k++){
							var express = trade.express[k], text = express.name + ' ' + (express.money > 0 ? express.money+'元' : '包邮');
							if(express.checked){
								if(express.errcode){
									express_text = express.errmsg;
									t.express_err = express.errmsg;
								}else{
									express_text = text;
								}
							}
							if(express.errcode){
								text = express.name+'('+express.errmsg+')';
							}
							expHTML += '<option value="'+express.id+'"'+(express.checked ? ' selected' : '')+' '+(express.errcode ? 'disabled="disabled"' : '')+'>'+text+'</option>';
						}
						expHTML += '</select>';
					}

					if(trade.discount_details.length > 0){
						html += '<div class="block-item font-size-12 js-discount">'+
									'<span>店铺优惠</span>'+
									'<div class="pull-right arrow">¥'+trade.discount_fee+'</div>'+
								'</div>';
					}

					html += '<div class="block-item select-express'+(expHTML == '' ? ' js-order-address' : '')+'">'+
			                    '<span>配送方式</span>'+
			                    '<div class="pull-right arrow" data-freight_id=""><span class="express_name"></span>'+express_text+'</div>'+
			                    expHTML+
			                '</div>'+
							'<div class="block-item order-message clearfix js-order-message" data-type="msg">'+
								'<textarea id="remark_'+group.shop_id+'_'+trade.freight_id+'" class="js-msg-container font-size-12" placeholder="给卖家留言...">'+trade.buyer_remark+'</textarea>'+
							'</div>'+
						'</div>';
				}

				var heji = '';
				if(group.payment > 0 && group.payscore > 0){
					heji = '<span>¥'+group.payment+'</span> + <span>'+group.payscore+'积分</span>'
				}else if(group.payscore > 0){
					heji = '<span>'+group.payscore+'积分</span>'
				}else{
					if(t.express_err!=''){
						heji = '<span>¥0</span>'
					}else{
						heji = '<span>¥'+group.payment+'</span>'
					}

				}
				if(isLB == 1){
				html += '<div class="block-item">'+
							'<div class="pull-left">优惠券<span class="c-grey">(每笔订单立减)</span></div><div class="pull-right"><span class="c-orange">'+'-¥'+group.discount_fee+'</span></div>'+
						'</div>';
				}
				html += '<div class="block-item">'+
							'合计<div class="pull-right"><span class="c-orange">'+heji+'</span></div>'+
						'</div>'+
					'</div>';
			}

			t.$listContainer.html(html);
		},
		setAddress: function(data, checkChange){
			if(!data || !data.city_code){
				return;
			}
			var $address = t.$address;

			if($address.hasClass('empty-address')){
				$address.removeClass('empty-address');
			}

			var changed = false, html = '', old = t.address;
			html += '<li class="info" class="clearfix" style="flex:5;">';
			html += '	<div>';
			html += '		<span class="name">收货人： '+data.receiver_name+'</span>';
			html += '		<span class="tel">'+data.receiver_mobile+'</span>';
			html += '   </div>'
			html += '   <div>';
			html += '收货地址：'+data.receiver_province+data.receiver_city+data.receiver_county+data.receiver_detail;
			html += '   </div>';
			html += '</li>';
			$address.find('.express-detail').html(html);

			if(checkChange){
				for(var field in old){
					if(old[field]+'' != data[field]+''){
						changed = true;
						t.address[field] = data[field];
					}
				}
			}

			t.address = data;
			t.express_err = '';
			if(changed){
				t.build();
			}
		},
		orderSuccess: function(data){
			// 无需支付了
			if(!data.need_pay || data.need_pay.length == 0){
				// 订单创建成功
				t.unbindEvent('下单成功');
				// 跳转地址
				t.redirect = data.redirect;
				t.order_url = data.order_url;
				toast.show('下单成功');
				return t.success(), null;
			}

			// 跳转地址
			t.redirect = data.redirect;
			t.order_url = data.order_url;

			if(data.isLB == '1'){
				// 订单创建成功
				toast.show("下单成功");
				t.unbindEvent('微信支付');
				var param = data.seller;
				var get_param1 = [];
				for(var d=0;d<param.length;d++){
					var dv = param[d];
					get_param1[d] = JSON.parse('{"tid":'+dv.tid+'}');
				}
				setTimeout(
					function(){
						window.location.href="/order/buyerPay?data="+JSON.stringify(get_param1)+'&shop_id='+data.shop_id;
					},1500
				)
				// $(".Payment_popups_box span.data_value").text(data.payment);
				// $(".Payment_popups_box_zf").attr("src",data.pay_qr);
			}else{
				toast.show('下单成功，您还需支付'+data.payment+'元');
				// 订单创建成功
				t.unbindEvent('去支付');
				window.onbeforeunload = function(event) {
					return '订单未支付(如您已支付请忽略)，确定离开吗？';
				}

				// 显示支付按钮并立即生成支付
				require(['h5/pay'], function(pay){
					$('.js-order-total-pay .js-confirm').on('click', function(){
						if(win.isWeiXin || 1 > 0){
							t.wxPay(pay, data.need_pay, data.appid, data.payment);
						}else{
							alert('暂不支持其他支付方式');
						}
						return false;
					});
				});
			}
		},
		wxPay: function(pay, tidArray, appid, payment){
			$.ajax({
				url: __PAY__+'/order/wxpay',
				data: {tid: tidArray, appid: appid},
				type: 'post',
				dataType: 'json',
				success: function(param){
					if(param.payment > payment || param.payment < payment){
						toast.show('卖家已为您改价，仅需支付'+param.payment+'元即可完成支付！');
					}

					delete param.payment;
					pay.callpay(param, function(res){
						if(res.errcode == 0){
							t.success();
						}
					});
				}
			});
		},
		success: function(){
			window.onbeforeunload = null;

			toast.loading('下单成功');
			var time = 4, $text = $('#loading_modal .text');
			var timer = setInterval(function(){
				time--;

				if(time < 1){
					window.clearInterval(timer);
					window.location.replace(t.order_url);
				}else{
					$text.html('下单成功('+time+')');
				}
			}, 1000);
		}
	};

	return t;
})
//编辑个人资料
,define('buyer/edit', ["jquery", "h5/validate", 'address'], function($, validate){
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
			html += '	        <div class="block-item no-top-border hide">';
			html += '<a href="/xiufu" style="color:#f60;text-align:center;margin-left: -10px;">老会员或变成游客请点击这里</a>';
			html += '	        </div>';
			html += '	        <div class="block-item no-top-border">';
			html += '	            <label>姓　　名</label>';
			html += '	            <input type="text" name="name" value="'+(data.mobile == '' ? '' : data.name)+'" placeholder="真实姓名" required="required" data-msg-required="请输入姓名">';
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
			html += '	            	<button type="button" class="js-get_code tag tag-big tag-orange" style="border:none;position: absolute;right: 0;top: 0;bottom: 0;font-size: 12px;padding: 0 20px;color: #da8f3e;background-color:#fff;">获取验证码</button>';
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
			html += '	            <input type="text" name="address" value="'+data.address+'" placeholder="街道门牌信息，请勿重复省市区" required="required" data-msg-required="请输入详细地址">';
			html += '	        </div>';
			html += '	        <div class="block-item">';
			html += '	            <label>邀请码</label>';
			html += '	            <input type="text" name="yqm" value="" placeholder="邀请码可直接绑定会员级别">';
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
				var mobile = $mobile.val();
				if(!tel.test(mobile)){
					return toast.show('请输入正确的手机号码'), false;
				}
				btn.disabled = true;
				$.ajax({
					url: get_url('/personal/check'),
					data: {mobile:mobile},
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
,define('buyer/nearest_order', function(){var t={stop:3000,init:function(stop,interval){if(stop)this.interval=interval;if(interval)this.interval=interval;setTimeout(function(){t.request()},t.stop);t.width=document.body.querySelector('.container').clientWidth-20},request:function(){$.ajax({url:get_url('/order/nearest'),dataType:'jsonp',success:function(data){if(data.errcode==1){return}t.show(data)}})},width:300,show:function(data){var html='<section id="order_timer_content" class="tip_visitors"><img src="'+data.img+'"><p><span class="textFlow" style="max-width:'+t.width+'px">'+data.message+'</span></p></section>';$('body').append(html);$('#order_timer_content').animate({top:'20%',opacity:1});setTimeout(function(){t.remove(data.interval)},t.stop)},remove:function(interval){var $content=$('#order_timer_content');$content.animate({top:'10%',opacity:0},function(){$content.remove();setTimeout(function(){t.request()},interval*1000)})}};t.init();})
//require(['buyer/nearest_order']);
//我的足迹
,define('buyer/goods/template2', function(){
    return {
        "default": function(list, page, bigSplit){
            if(list.length == 0 && page == 1){
            	 $(".x-pullfresh-more").hide();
                return '<li style="background:none !important;"><div class="empty-list"><div><h4>您还没有逛过的商品</h4><p class="font-size-12"></p></div></div></li>';
            }
            // 显示大图
            if(bigSplit == undefined){bigSplit=true}
            var html = tagHtml = '', class_name = 'small-pic', index = 0;
            for(var i=0; i< list.length; i++){
                var goods = list[i], imagehtml = '';

                html += '<li>';
                html += '<a href="/'+goods.alias+'/goods?id='+goods.id+'">';
                html += '   <div class="my_tracks_left">';
                html += '   <img src="'+goods.pic_url+'" alt="">';
                html += '   </div>';
                html += '   <div class="my_tracks_right">';
                html += '    <p>'+goods.title+'</p>';
                html += '<div>';
                html += '<em class="Price_box">';
                html += '    <span class="price_icon">￥</span>';
                html += '    <span class="price_num">'+goods.price+'</span>';
                html += '</em>';
                html += '<span class="share_box">';
                html += '   <div class="share_box_btn" data-price="'+goods.price+'" data-id="'+goods.id+'" shop-id="'+goods.shop_id+'">分享</div>';
                html += '   <div class="remove_list" data-id="'+goods.id+'" style="margin-right:.5rem;">删除</div>';
                html += '   </span>';
                html += '   <div style="clear:both"></div>';
                html += '   </div>';
                html += '   </div>';
                html += '   </a>';
                html += '    </li>';
            }
            return html;
        },
    }
})
,define("buyer/goods/list2", ["jquery", "h5/pullrefresh", "buyer/goods/template2"], function($, pullrefresh, template){
    var t = {
        getActive: function(_def){
            var key = pullrefresh.info.key;
            return key ? key : _def;
        },
        doRefresh: function(params){
            params.dataType = 'json';
            params.success = function(list, page){
                var $container = params.container,
                    html = template[params.template ? params.template : "default"](list, page),
                    $html = $(html);

                if(page == 1){
                    $container.html($html);
                }else{
                    var $children = $container.children(), index = $children.length - 1;
                    $children.each(function(i){
                        var _page = $children.eq(i).data('page');
                        if(_page > page){
                            index = i - 1;
                            return false;
                        }
                    });
                    $children.eq(index).after($html);
                }

                t.bindEvent($html);
                return list.length == params.data.size;
            }
            pullrefresh.doRefresh(params);
        },
        bindEvent: function($html){
            requirejs(['lazyload'], function(){
                $html.find(".js-goods-lazy").lazyload({
                    placeholder : "/img/logo_rgb.jpg",
                    threshold : 270
                });
            });

            t.countdown($html);

            // 绑定加入购物车事件
            require(['buyer/skumodal'], function(skumodal){
                $html.on('click', '.js-goods-buy', function(){
                    var $this = $(this), $prev = $this.prev();
                    if($prev.hasClass('ajax-loading')){
                        return false;
                    }

                    $prev.addClass('ajax-loading');
                    skumodal.init($this.data('url'), function(){
                        $prev.removeClass('ajax-loading');
                    });
                    return false;
                });
            });
        },
        countdown: function($html){
            var $elementList = $html.find('.countdown>.time');
            if($elementList.length == 0){
                return;
            }

            var timerList = {}, nowTime = Date.parse(new Date()) / 1000;
            $elementList.each(function(i, element){
                var data = $elementList.eq(i).data(),
                    timer = data.end;
                if(nowTime >= data.end){
                    return true;
                }else if(!timerList[timer]){
                    timerList[timer] = {end: data.end*1000, element: [element]};
                }else{
                    timerList[timer].element.push(element);
                }
            });

            for(var key in timerList){
                var start = Date.parse(new Date()),
                    data = timerList[key], html = '';
                var timer = window.setInterval(function(){
                    start += 1000;

                    var leftTime = data.end - start,
                        leftsecond = parseInt(leftTime/1000),
                        day=Math.floor(leftsecond/(60*60*24)),
                        hour=Math.floor((leftsecond-day*24*60*60)/3600),
                        minute=Math.floor((leftsecond-day*24*60*60-hour*3600)/60),
                        second=Math.floor(leftsecond-day*24*60*60-hour*3600-minute*60);

                    if(leftTime == 0){ window.clearInterval(timer)}

                    html = (day > 0 ? '<em>'+day+'</em>天' : '') + '<em>'+hour+'</em>小时<em>'+minute+'</em>分<em>'+second+'</em>秒';
                    for(var i=0; i<data.element.length; i++){
                        data.element[i].innerHTML = html
                    }
                }, 1000);
            }
        }
    }
    return t;
})
//我逛过的店
,define('buyer/goods/template3', function(){
    return {
        "default": function(list, page, bigSplit){
				// console.log("haha",list.length);
            if(list.length == 0 && page == 1){
            	$(".x-pullfresh-more").hide();
                return '<li style="background:none !important;"><div class="empty-list"><div><h4>您还没有逛过的店</h4><p class="font-size-12"></p></div></div></li>';
            }
            // 显示大图
            if(bigSplit == undefined){bigSplit=true}
            var html = tagHtml = '', class_name = 'small-pic', index = 0;
            for(var i=0; i< list.length; i++){
                var shop = list[i], imagehtml = '';
				
				html +='<li>';
				html +='	<div class="stroll_box_padding">';
				html +='		<div class="stroll_title_logo">';
				html +='			<img src="'+shop.logo+'" alt="">';
				html +='		</div>';
				html +='		<div class="stroll_title_title">';
				html +='			<h4>'+shop.name+'</h4>';
				html +='			<p>最新上新数量<span class="auto_margins">'+shop.new+'</span>共<span>'+shop.goods+'</span>件宝贝</p>';
				html +='		</div>';
				html +='	</div>';
				html +='	<div class="stroll_box_padding stroll_list">';
				html +='		<ul>';
				var pics = shop.newest;
                for(var j=0; j< pics.length; j++){
                	var pic = pics[j];
                    html +='			<li>';
                    html +='				<div class="img_s">';
                    html +='				<img src="'+pic.pic_url+'" alt="">';
                    html +='				<p>￥<span>'+pic.price+'</span></p>';
                    html +='				</div>';
                    html +='			</li>';
                }
				html +='		</ul>';
				html +='	</div>';
				html +='	<div class="stroll_list_btn">';
				html +='		<p>';
				html +='			<a class="btn_style store_btn" href="/'+shop.alias+'/shop" >进店</a>';
				html +='			<a class="btn_style share_btn" shop-id="'+shop.shop_id+'" href="javacsript:void(0)" >分享</a>';
				html +='			<a class="btn_style delete_btn" shop-id="'+shop.shop_id+'" href="javascript:void(0)" >删除</a>';
				html +='			<div style="clear:both"></div>';
				html +='		</p>';
				html +='	</div>';
				html +='</li>';
            }
            return html;
        },
    }
})
,define("buyer/goods/list3", ["jquery", "h5/pullrefresh", "buyer/goods/template3"], function($, pullrefresh, template){
    var t = {
        getActive: function(_def){
            var key = pullrefresh.info.key;
            return key ? key : _def;
        },
        doRefresh: function(params){
            params.dataType = 'json';
            params.success = function(list, page){
                var $container = params.container,
                    html = template[params.template ? params.template : "default"](list, page),
                    $html = $(html);

                if(page == 1){
                    $container.html($html);
                }else{
                    var $children = $container.children(), index = $children.length - 1;
                    $children.each(function(i){
                        var _page = $children.eq(i).data('page');
                        if(_page > page){
                            index = i - 1;
                            return false;
                        }
                    });
                    $children.eq(index).after($html);
                }

                t.bindEvent($html);
                return list.length == params.data.size;
            }
            pullrefresh.doRefresh(params);
        },
        bindEvent: function($html){
            requirejs(['lazyload'], function(){
                $html.find(".js-goods-lazy").lazyload({
                    placeholder : "/img/logo_rgb.jpg",
                    threshold : 270
                });
            });

            t.countdown($html);

            // 绑定加入购物车事件
            require(['buyer/skumodal'], function(skumodal){
                $html.on('click', '.js-goods-buy', function(){
                    var $this = $(this), $prev = $this.prev();
                    if($prev.hasClass('ajax-loading')){
                        return false;
                    }

                    $prev.addClass('ajax-loading');
                    skumodal.init($this.data('url'), function(){
                        $prev.removeClass('ajax-loading');
                    });
                    return false;
                });
            });
        },
        countdown: function($html){
            var $elementList = $html.find('.countdown>.time');
            if($elementList.length == 0){
                return;
            }

            var timerList = {}, nowTime = Date.parse(new Date()) / 1000;
            $elementList.each(function(i, element){
                var data = $elementList.eq(i).data(),
                    timer = data.end;
                if(nowTime >= data.end){
                    return true;
                }else if(!timerList[timer]){
                    timerList[timer] = {end: data.end*1000, element: [element]};
                }else{
                    timerList[timer].element.push(element);
                }
            });

            for(var key in timerList){
                var start = Date.parse(new Date()),
                    data = timerList[key], html = '';
                var timer = window.setInterval(function(){
                    start += 1000;

                    var leftTime = data.end - start,
                        leftsecond = parseInt(leftTime/1000),
                        day=Math.floor(leftsecond/(60*60*24)),
                        hour=Math.floor((leftsecond-day*24*60*60)/3600),
                        minute=Math.floor((leftsecond-day*24*60*60-hour*3600)/60),
                        second=Math.floor(leftsecond-day*24*60*60-hour*3600-minute*60);

                    if(leftTime == 0){ window.clearInterval(timer)}

                    html = (day > 0 ? '<em>'+day+'</em>天' : '') + '<em>'+hour+'</em>小时<em>'+minute+'</em>分<em>'+second+'</em>秒';
                    for(var i=0; i<data.element.length; i++){
                        data.element[i].innerHTML = html
                    }
                }, 1000);
            }
        }
    }
    return t;
})
//资金流水记录
,define('buyer/balance', ["h5/pullrefresh", "jquery"], function(pullfresh){
	var $btnTransfers = $('.js-btn-transfers'),
		$balance = $('.js-balance'),
		$noBalance = $('.js-no-balance'),
		$totalBalance = $('.js-total-balance'),
		$canBalance = $('.js-can-balance'),
		$scoreBalance = $('.js-score-balance'),
		$inputMoney = $('.js-input-money'),
		$content = $('#balance_list .js-balance-list');

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

	var $container = $('#balance_list .js-balance-list');
	var timer = 0;
	var showData = function(data, page){
		$totalBalance.html(data.user.total_balance);
		$balance.html(data.user.balance);
		$noBalance.html(data.user.wallet);
		$inputMoney.val(data.user.this_times);
		$canBalance.html(data.user.can_transfers);
		$scoreBalance.html(data.user.score);
		window.clearInterval(timer);
		if(data.btn.disabled){
			$btnTransfers.attr('disabled', 'disabled');

			if(data.btn.seconds > 0){
	    		var leftsecond = data.btn.seconds;
		    	timer = setInterval(function(){
		    		leftsecond--;
					var day = Math.floor(leftsecond / (60 * 60 * 24));
					var hour = Math.floor((leftsecond - day * 24 * 60 * 60) / 3600);
					var minute = Math.floor((leftsecond - day * 24 * 60 * 60 - hour * 3600) / 60);
					var second = Math.floor(leftsecond - day * 24 * 60 * 60 - hour * 3600 - minute * 60);

					var html = [];
					if(hour > 0){html.push(hour+'小时')}
					if(minute > 0){html.push(minute+'分')}
					if(second > 0){html.push(second+'秒')}
					$btnTransfers.html(html.join(''));

					if(leftsecond == 0){
						window.clearInterval(timer);
						$btnTransfers.html('立即提现').removeAttr('disabled');
					}
		    	}, 1000);
			}
		}else{
			$btnTransfers.html('立即提现').removeAttr('disabled');
		}

		list = data.rows;
		var html = '',
			prevMonth = currentMonth = '', money = 0;

		for(var i=0; i< list.length; i++) {
			var timestamp3 = list[i].created;
			var newDate = new Date();
			newDate.setTime(timestamp3 * 1000);
			list[i].created = newDate.toISOString();
			currentMonth = list[i].created.substr(0, 7);
			if (prevMonth != currentMonth) {
				if (prevMonth == '') {
					prevMonth = currentMonth;
				}
				appendHTML(html, prevMonth);
				prevMonth = currentMonth;
				html = '';
			}

			money = parseFloat(list[i].add_balance);
			if(money != 0){
				html += '<li class="block-item">';
				html += '	<div class="block-left"><div>' + list[i].date + '</div><div>' + list[i].time + '</div></div>';
				html += '	<div class="block-dot" style="background:' + list[i].color + '">' + list[i].short + '</div>';
				html += '	<div class="block-info">';
				html += '		<div class="block-title">' + (money > 0 ? '+' : '') + money + '<span style="float:right;color:#9E9E9E;font-size:10px">'+data.balance_alias+'</span></div>';
				html += '		<div class="block-content">' + list[i].reason + '</div>';
				html += '	</div>';
				html += '</li>';
			}

			money = parseFloat(list[i].add_wallet);
			if(money != 0){
				html += '<li class="block-item">';
				html += '	<div class="block-left"><div>' + list[i].date + '</div><div>' + list[i].time + '</div></div>';
				html += '	<div class="block-dot" style="background:' + list[i].color + '">' + list[i].short + '</div>';
				html += '	<div class="block-info">';
				html += '		<div class="block-title">' + (money > 0 ? '+' : '') + money + '<span style="float:right;color:#9E9E9E;font-size:10px">'+data.wallet_alias+'</span></div>';
				html += '		<div class="block-content">' + list[i].reason + '</div>';
				html += '	</div>';
				html += '</li>';
			}

			money = parseFloat(list[i].add_score);
			if(money != 0){
				html += '<li class="block-item">';
				html += '	<div class="block-left"><div>' + list[i].date + '</div><div>' + list[i].time + '</div></div>';
				html += '	<div class="block-dot" style="background:' + list[i].color + '">' + list[i].short + '</div>';
				html += '	<div class="block-info">';
				html += '		<div class="block-title">' + (money > 0 ? '+' : '') + money + '<span style="float:right;color:#9E9E9E;font-size:10px">'+data.score_alias+'</span></div>';
				html += '		<div class="block-content">' + list[i].reason + '</div>';
				html += '	</div>';
				html += '</li>';
			}
		}
		appendHTML(html, prevMonth);
	}

	pullfresh.doRefresh({
		url: get_url('/balance'),
		data: {iu:'iu',size: 20},
		dataType: "json",
		cache: false,
		container: $content,
		success: function(data, page){
			if(page == 1) {
				$container.html("");
			}
			var html = showData(data, page);
			if(page == 1){
				$container.html(html);
			}else{
				var $children = $container.children(), index = $children.length - 1;
				$children.each(function(i){
					var _page = $children.eq(i).data('page');
					if(_page > page){
						index = i - 1;
						return false;
					}
				});
				$children.eq(index).after(html);
			}
			return data.rows.length >= 20;
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
		$btnTransfers.attr('disabled', 'disabled');
		var amount = $inputMoney.val();
		$.ajax({
			url: get_url('/balance/transfers'),
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
