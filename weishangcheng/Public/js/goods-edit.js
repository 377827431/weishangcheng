/**
 * 编辑商品
 */
var EditGoods = {
	goods: {},
	shop_id: null,
	init: function(goods){
		var t = this;
		t.goods = goods;
		t.shop_id = goods.shop_id;

		// 初始化SKU列表
		if(goods.sku_json && !goods.tao_id){
			for(var i=0; i<goods.sku_json.length; i++){
				t.addGoodsSku(goods.sku_json[i]);
			}
		}

		t.initPriceType(goods);
		t.initCategory(goods);
		t.initImg();
		t.initSoldTime(goods);
		t.initFreightTemplate(goods);
		t.initAttr();
		t.initForm();
		
		$.getScript('/js/address.js', function(){
			t.initSendPlace(goods);
			t.initRemoteArea(goods);
		});

		// 下一步
		$('.js-switch-step').on('click', function(){
			var step = this.getAttribute('data-next-step') - 1;
			$('#myTab a:eq('+step+')').tab('show');
		});
		
		// 点击添加商品规格按钮
		$('#add_goods_sku').on('click', function(){
			return t.addGoodsSku(), false;
		});
		
		// 计算总库存、价格、积分
		$('#stock-region').on('change', 'input',function(){
			var $this = $(this), is_price = $this.hasClass('js-price');
			if($this.hasClass('js-stock')){ // 库存
				if(this.value != '' && !/^\d+$/.test(this.value)){
					this.value = parseInt(this.value);
				}
			}else if(is_price || $this.hasClass('js-weight') || $this.hasClass('js-cost') || $this.hasClass('js-first') || $this.hasClass('js-second')){
				var name = $this.attr('name');
				
				var name = $this.attr('name').substr(-7);
				var fushu = t.usedPriceType == 2 && !$this.hasClass('js-weight') && name != '[price]';
				this.value = t.toFixed(this, fushu);
				
				if(t.usedPriceType == 2 && is_price && name == '[price]'){
					t.resetMemberPriceRange($this);
				}
			}
			
			if(!$this.hasClass('js-shiyong')){
				t.resetPriceAndStock();
			}
			return false;
		})
		// 双击左键向下填充数据
		.on('dblclick', '.sku-table-right input',function(){
			var $this = $(this),
				$td = $this.parent(),
				$tr = $td.parent();
			var value = $this.val(), index = $td.index();
			
			var $nextAll = $tr.nextAll().find('input:eq('+index+')');
			if(t.usedPriceType == 2 && $this.attr('name').indexOf('[price]') > 0){
				$nextAll.each(function(i){
					this.value = value;
					t.resetMemberPriceRange($nextAll.eq(i));
				});
				return;
			}else{
				$nextAll.val(value);
			}
			
			t.resetPriceAndStock();
			return false;
		})
		// 双击右键向右填充价格
		.on('contextmenu', '.js-price, .js-score', function(){
			var $this = $(this), now = Date.now(), old = $this.data('click_time');
			if(old && now - old < 1000){
				var $td = $this.parent(), index = $td.index(), type = $this.hasClass('js-price') ? '.js-price' : '.js-score';
				$this.parent().parent().find('input[type="text"]:gt('+index+')').filter(type).val(this.value);
				return false;
			}
			
			$this.data('click_time', now);
		});
		
		// 切换选项卡
		$('#myTab').on('shown', function (e) {
			var active = $(this).children().eq(1).hasClass('active');
			if(!active){
				t.beforeInsertImage = null;
			}
		});

		t.initGoodsTag(goods);
		
		window.onbeforeunload = function(event) { 
			return '数据未保存，确定要离开吗？';
		}
	}
	,resetMemberPriceRange: function($this){
		var $tr = $this.parent().parent(),
			$prices = $tr.find('.js-price:gt(0)'),
		max = parseFloat($this.val()),
		min = -(max / 2).toFixed(2);
		$prices.attr('data-rule-range', min+','+max);
	}
	,initCategory: function(goods){
		var t = this;
		
		// 选择类目
		$('#class-info-region .js-cat-item').on('click', function(){
			var $this = $(this)
			   ,$item = null
			   ,$siblings = null
			   ,name = ''
			   ,catId = $this.attr('data-id');
			
			// 一级类目
			if($this.hasClass('widget-goods-klass-item')){
				if($this.hasClass('has-children')){
					return false;
				}
				name = $this.children('.widget-goods-klass-name').text();
				$item = $this;
			}else{
				$this.children().prop('checked', true);
				name = $this.text();
				$item = $this.parents('.widget-goods-klass-item:first');
				$item.children(':first').html(name + '<i class="cover-down"></i>');
				name = $item.data('name') + ' - ' + name;
			}
			$siblings = $item.siblings('.widget-goods-klass-item');
			$siblings.each(function(i, item){
				if(i == $siblings.length - 1){
					return false;
				}
				var $cat = $siblings.eq(i);
				$cat.removeClass('current');
				$cat.children(':first').html($cat.data('name') + '<i class="cover-down"></i>');
			});
			
			$item.addClass('current');
			$('#js-tag-step').html(name);
			goods.cat_id = catId;
			
			$('#myTab a:eq(1)').tab('show');
			return false;
		}).filter('[data-id="'+goods.cat_id+'"]').trigger('click');
		
		$('#cat_list .widget-goods-klass-children li').on('mouseover', function(){
			var $li = $(this),
				pid = $li.data('pid'),
				$siblings = $li.siblings();
			$li.addClass('hover');
			$siblings.each(function(i){
				if($siblings.eq(i).data('pid') == pid){
					$siblings.eq(i).addClass('hover')
				}else{
					$siblings.eq(i).removeClass('hover')
				}
			});
			return false;
		});
	}
	// 初始化商品分组
	,initGoodsTag: function(goods){
		var $select = $('#goods_tag');
		$select.siblings('.js-refresh').on('click', function(){
			// 获取分组
			$.ajax({
				url: __ADMIN__+'/tag',
				dataType: 'json',
				success: function(list){
					var html = '', pname = '', group = false;
					for(var i=0; i<list.length; i++){
						if(list[i].level > 3){
							continue;
						}
						
						if(list[i].level == 1){
							pname = list[i].name;
							if(!list[i].is_last){
								if(group){html += '</optgroup>'}
								html += '<optgroup label="'+pname+'">';
							}else{
								group = false;
								html += '<option value="'+list[i].id+'">'+pname+'</option>';
							}
						}else if(list[i].level == 2){
							html += '<option value="'+list[i].id+'"'+(list[i].is_last ? '' : 'disabled="disabled"')+'>'+pname+' - '+list[i].name+'</option>';
						}else{
							html += '<option value="'+list[i].id+'">'+list[i].name+'</option>';
						}
					}
					if(group){html += '</optgroup>'}
					$select.html(html);
					
					$select.select2({
						tags: false,
						maximumSelectionLength: 3,
						placeholder: "非必填项",
						allowClear: true,
						multiple: true
					});
					
					$select.val(goods.tag_id).trigger("change");

					$('#page_loading').fadeOut(function(){
						$(this).remove()
					});
				}
			});
			return false;
		}).trigger('click');
	}
	// 初始化form
	,initForm: function(){
		var t = this;
		zh_validator();
		var $form = $('#goods_edit_form');
		
		$form.find('.js-submit').on('click', function(){
			var goods = t.goods,
			post_data = {
				shop_id: t.shop_id,
				cat_id: goods.cat_id,
				send_place: goods.send_place,
				remote_area: goods.remote_area,
				images: '',
				sold_time: goods.sold_time,
				price_type: t.usedPriceType,
				tao_id: goods.tao_id > 0 ? goods.tao_id : 0,
				member_discount: 0,
				invoice: 0,
				warranty: 0,
				returns: 0
			};
			// 基础数据校验
			if(post_data.cat_id == ''){
				alertMsg('请选择商品所属类目');
				$('#myTab a:eq(0)').tab('show');
				return false;
			}
			// 商品宣传图
			var $images = $('.js-picture-list img');
			if($images.length == 0){
				alertMsg('请至少上传一张宣传图');
				$('#myTab a:eq(1)').tab('show');
				return false;
			}
			
			var images = [];
			$images.each(function(){
				images.push(this.src);
			});
			post_data.pic_url = images[0];
			post_data.images = images.join(',');
			
			// 其他字段验证
			if(!$form.valid()){
				return false;
			}
			
			// 合并数据
			var form_data = win.getFormValue($form);
			
			// 标题
			{
				var title = form_data.title, $title = $('#js-goods_title'), $gtl = $title.parent().parent();
				if(title.length > 100){
					return $title.focus(), $gtl.addClass('error'), false;
				}
				
				var list = title.split(''), length = 0;
				for(var i=0; i<list.length; i++){
					if(list[i].match(/[^\x00-\xff]/ig)){
						length += 2;
					}else{
						length += 1;
					}
				}
				length = Math.ceil(length/2);
				if(length > 30){
					$('#myTab a:eq(1)').tab('show');
					return alertMsg('商品名称过长'), false;
				}else{
					$gtl.removeClass('error')
				}
			}
			
			for(var k in form_data){
				post_data[k] = form_data[k]
			}
			
			var is_display = $(this).data('display');
			if(is_display != undefined){
				post_data.is_display = is_display ? 1 : 0;
			}
			
			var sku_json = t.getSkuList();
			if(!sku_json || sku_json.length == 0){
				post_data.sku_json = '';
			}else{
				post_data.sku_json = t.toJSON(sku_json);
			}
			post_data.parameters = JSON.stringify(post_data.parameters);
			post_data.level_quota = post_data.level_quota ? post_data.level_quota.join(',') : '';
			
			if(t.usedPriceType != 0){
				for(var key in post_data.custom_price){
					if(t.usedPriceType == 1){
						post_data.price = post_data.custom_price[key];
					}else if(t.usedPriceType == 2){ // 过滤会员价空字段
						if(post_data.custom_price[key] === ''){
							delete post_data.custom_price[key];
						}
					}else if(t.usedPriceType == 3){ // 过滤积分价空字段
						if(post_data.custom_price[key].price === '' || post_data.custom_price[key].score === ''){
							delete post_data.custom_price[key];
						}
					}
				}
				post_data.custom_price = JSON.stringify(post_data.custom_price);
				
				// 过滤掉会员价的空值
				if(post_data.price_type > 1 && post_data.sku_json && post_data.sku_json.length > 0){
					for(var i=0; i<post_data.products.length; i++){
						var custom_price = post_data.products[i].custom_price;
						
						var price = {};
						if(t.usedPriceType == 2){ // 过滤会员价空字段
							for(var card_id in custom_price){
								if(custom_price[card_id] !== ''){
									price[card_id] = custom_price[card_id];
								}
							}
						}else{
							for(var card_id in custom_price){
								if(custom_price[card_id].price === '' || custom_price[card_id].score === ''){
									price[card_id] = custom_price[card_id];
								}
							}
						}
						
						custom_price = price;
					}
				}
			}
			post_data.products = t.toJSON(post_data.products);
			
			var tags = $('#goods_tag').val();
			post_data.tag_id = tags ? tags.join(',') : '';
			
			var $buttons = $(this).parent().children();
			$buttons.attr('disabled', 'disabled');
			$.ajax({
				url: $form.attr('url'),
				type: 'post',
				dataType: 'json',
				data: post_data,
				success: function(){
					window.onbeforeunload = null;
					win.back();
				},
				error: function(){
					$buttons.removeAttr('disabled');
				}
			});
			
			return false;
		});
		
		$form.validate({
	        errorClass: 'help-inline',
	        errorElement: "span",
	        ignore: ".ignore",
	        highlight: function (element, errorClass, validClass) {
	        	var $element = $(element);
	        	$element.parents('.control-group:first').addClass('error');
	        },
	        unhighlight: function (element, errorClass, validClass) {
	        	var $element = $(element);
	            if ($element.attr('aria-invalid') != undefined) {
	            	$element.parents('.control-group:first').removeClass('error');
	            }
	        },
	        errorPlacement: function($error, $element){},
	        invalidHandler: function(event, validator) {
				var $element = $(validator.errorList[0].element),
					data = $element.data(),
					errmsg = validator.errorList[0].message;
				if(data.label){
					errmsg = data.label + '：' + errmsg;
				}
				alertMsg(errmsg, 'error');

				var index = $element.parents('.tab-pane:first').data('index');
				$('#myTab a:eq('+index+')').tab('show');
				$element.focus();
			},
	        submitHandler: function () {
	    		return false;
	        }
	    });
	}
	,initSoldTime: function(goods){
		var t = this;
		if(typeof $.fn.datetimepicker == 'undefined'){
			win.getStyle('/css/bootstrap-datetimepicker.min.css');
			win.getScript('/js/bootstrap-datetimepicker.min.js', function(){
				t.initSoldTime(goods);
			});
			return;
		}
		
		$.fn.datetimepicker.dates['zh-CN'] = {
				days: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期日"],
				daysShort: ["周日", "周一", "周二", "周三", "周四", "周五", "周六", "周日"],
				daysMin:  ["日", "一", "二", "三", "四", "五", "六", "日"],
				months: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
				monthsShort: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
				today: "今天",
				suffix: [],
				meridiem: ["上午", "下午"]
		};
		
		var $container = $('#sold_time'),
			$calendarCtl = $container.find('.input-append'),
			$type = $container.find('.js-sold_time_type'),
			$date = $calendarCtl.children('input');
		$calendarCtl.datetimepicker({
			format : "yyyy-MM-dd hh:mm:ss",
			pickDate: true,
			pickTime: true,
			startDate: new Date(),
		    language: 'zh-CN',
		    pickPosition: 'top-right'
		}).on('changeDate', function(a, b){
			goods.sold_time = $date.val();
			$type.eq(0).prop('checked', false);
			$type.eq(1).prop('checked', true);
		});
		
		// 点击“立即开售”或“定时开售”
		$type.on('click', function(){
			var $this = $(this);
			$this.parent().siblings('.radio').find('input').prop('checked', false);
			if($this.data('type') == 'now'){
				$calendarCtl.addClass('hide');
				goods.sold_time = 0;
			}else{
				$calendarCtl.removeClass('hide');
				goods.sold_time = $date.val();
			}
		});
		
		if(goods.sold_time != 0){
			$date.val(goods.sold_time);
			$type.eq(1).trigger('click');
		}
	}
	,memberCards: {}
	,getSkuList: function(){
		if(this.goods.tao_id){
			return this.goods.sku_json || [];
		}
		
		var sku_list  = [];
		$('#goods_sku_content>.sku-sub-group').each(function(){
			var $this = $(this),
			    $select = $this.find('.sku-group-title>select'),
			    $atoms = $this.find('.sku-atom-list .sku-atom>span'),
	            sku_item = {};
			
			if($atoms.length > 0){
				sku_item.id = $select.val();
				sku_item.text = $select.find(':selected').text();
				sku_item.items = [];
				
				$atoms.each(function(i){
					var sku = {id: this.getAttribute('data-atom-id'), text: this.innerText},
						$img = $atoms.eq(i).siblings('.upload-img-wrap').find('.js-img-preview');
					if($img.length > 0){
						sku.img = $img.attr('src');
					}
					sku_item.items.push(sku);
				});

				sku_list.push(sku_item);
			}
		});
		return sku_list;
	}
	,resetSKUStore: function(sku_list){
		var t = this, sku_list = t.getSkuList(), th = '';
		
		var leftTable = '', rightTable = '';
		if(sku_list.length > 0){
			var leftWidth = sku_list.length * 96, rightWidth = 744 - leftWidth,
				product_sku_list = [],
				rightTr = [],
				rightTh = '', rightCount = 4;
			
			// 右侧table
			switch(t.usedPriceType){
				case 0: // 普通
					rightTh += '<th class="js-price must text-center">售价(元)</th>';
					rightCount++;
					break;
				case 1: // 区间价
					var rangePrice = t.rangePrice;
					for(var quantity in rangePrice){
						rightTh += '<th class="js-price must text-center" data-quantity="'+quantity+'">≥'+quantity+'件</th>';
						rightCount++;
					}
					break;
				case 2: // 会员价
					var memberCards = t.memberCards;
					for(var i=0; i<memberCards.length; i++){
						rightTh += '<th class="js-price '+(i==0?'must':'')+' text-center" data-card_id="'+memberCards[i].id+'">'+(i==0?'默认价格':memberCards[i].title)+'</th>';
						rightCount++;
					}
					break;
				case 3: // 积分价
					rightTh += '<th class="must text-center">原价(元)</th>';
					rightCount++;
					var memberCards = t.memberCards;
					for(var i=0; i<memberCards.length; i++){
						rightTh += '<th colspan="2" class="js-price '+(i==0?'must':'')+' text-center" data-card_id="'+memberCards[i].id+'">'+(i==0?'默认价格':memberCards[i].title)+'</th>';
						rightCount++;
					}
					break;
				case 4: // 单品代理
					rightTh += '<th class="must text-center">全国统一</th>';
					rightCount++;
					var agentPrice = t.agentPrice;
					for(var id in agentPrice){
						rightTh += '<th class="must text-center" colspan="3" style="width:180px">'+agentPrice[id].title+'</th>';
						rightCount+=3;
					}
					break;
			}
			
			var hideScroll = rightWidth > rightCount * 76;
			
			// 左侧table
			leftTable = '<div style="float:left;width:'+leftWidth+'px" class="sku-table-left"><table class="table-sku-stock'+(hideScroll?' hide-bottom':'')+'"><thead><tr>';
			for(var i=0; i<sku_list.length; i++){
				leftTable += '<th style="width:96px">'+sku_list[i].text+'</th>';
				var count = 1;
				for(var j = i+1; j < sku_list.length; j++){
					count = count * sku_list[j].items.length;
				}
				sku_list[i].count = count;
			}
			leftTable += '</tr></thead><tbody>' + t.getLeftTr(sku_list, 0, [], rightTr, hideScroll) + '</tbody></table></div>';
			
			rightTable = '<div class="sku-table-right" style="float:left;width:'+rightWidth+'px;overflow-x:'+(hideScroll?'hidden':'auto')+';margin-left:2px"><table class="table-sku-stock'+(hideScroll?' hide-bottom':'')+'"><thead><tr>';
			rightTable += rightTh+'<th>成本(元)</th>'+ 
					'<th>库存</th>'+ 
			        '<th style="width:45px">重量(kg)</th>'+
				    '<th>商家编码</th>' +
				    '</tr></thead>'+
				    '<tbody>' + rightTr.join('') + '</tbody></table></div>';
		}
		$('#goods_sku_content').data(sku_list);
		
		$('#stock-region').html(leftTable+rightTable);
		
		var $weightCtl = $('#weight').parent().parent(), $gprice = $('.goods-price-control');
		if(sku_list.length > 0){
			// 禁止手动修改
			$('#total_stock, #weight').attr('readonly', 'readonly').addClass('ignore');
			$gprice.find('.js-price,.js-score,.js-first,.js-second,#cost,#price').attr('readonly', 'readonly').addClass('ignore');
			$('#product_list').show();
			$weightCtl.addClass('hide');
			t.resetPriceAndStock();
		}else{
			// 允许手动修改
			$('#total_stock, #weight').removeAttr('readonly').removeClass('ignore');
			$gprice.find('.js-price,.js-score,.js-first,.js-second,#cost,#price').removeAttr('readonly').removeClass('ignore');
			$('#product_list').hide();
			$weightCtl.removeClass('hide');
		}
	}
	,getSKUJSON: function(){
		var sku_list  = [];
		$('#goods_sku_content>.sku-sub-group').each(function(){
			var $this = $(this),
			    $select = $this.find('.sku-group-title>select'),
			    $atoms = $this.find('.sku-atom-list .sku-atom>span'),
	            sku_item = {};
			
			if($atoms.length > 0){
				sku_item.id = $select.val();
				sku_item.text = $select.find(':selected').text();
				sku_item.items = [];
				
				th += '<th>'+sku_item.text+'</th>';
				
				$atoms.each(function(){
					sku_item.items.push({id: this.getAttribute('data-atom-id'), text: this.innerText});
				});

				sku_list.push(sku_item);
			}
		});
		
		return sku_list;
	}
	,getLeftTr: function(sku_list, index, pro_sku, right_tr, hideScroll){
		var t=this,result = '';
		if(index == 0){
			result += '<tr>';
		}
		
		var items = sku_list[index].items;
		
		for(var j=0; j<items.length; j++){
			var product = $.extend([], pro_sku);
			product.push({kid: sku_list[index].id, vid: items[j].id, k: sku_list[index].text, v: items[j].text});
			
			if(j > 0){result += '<tr>'}
			result += '<td rowspan="'+sku_list[index].count+'"'+(j==items.length-1&&hideScroll?'style="border-bottom:none"':'')+' title="'+items[j].text+'"><div class="ellipsis">'+items[j].text+'</div></td>';
			
			if(index == sku_list.length - 1){
				right_tr.push(t.getRightTr(product, right_tr.length));
			}
			
			if(index + 1 < sku_list.length){
				result += t.getLeftTr(sku_list, index+1, product, right_tr, hideScroll);
			}
			if(j > 0){result += '</tr>'}
		}

		return result;
	}
	,getRightTr: function(sku, index){
		var t = this, key = t.priceType[t.usedPriceType].data, pipei_max = 0, shiyong = false,
		product = {
			id: '',
			stock: '',
			price: t.goods.price,
			original_price: t.goods.original_price,
			score: '',
			outer_id: '',
			weight: t.goods.weight,
			cost: t.goods.cost,
			custom_price: t[key],
			sku_quota: ''
		};
		
		$.each(t.goods.products, function(i, product2){
			var pipei_num = 0;
			var sku2 = product2.sku_json;
			for(var j=0; j<sku.length; j++){
				for(var h=0; h<sku2.length; h++){
					if(sku[j].vid == sku2[h].vid){
						pipei_num++;
					}
				}

				if(t.usedPriceType == 4 && shiyong == false){
					var str = sku[j].v.toString();
					shiyong = str.indexOf('试用') > -1 || str.indexOf('试吃') > -1
				}
			}
			
			if(pipei_num == sku.length){
				product = $.extend(product, product2);
				if(t.goods.price_type != t.usedPriceType){
					product.custom_price = t[key];
				}
				return false;
			}
		});
		
		var html ='<tr title="双击左键自动向下填充">', title = 'title="双击左键自动向下填充&#10;双击右键向右填充价格"';
		switch(t.usedPriceType){
			case 0: // 普通
				html += '<td class="input-td">';
				html += '  	<input type="text" name="products['+index+'][price]" value="'+(product.price ? parseFloat(product.price) : '')+'" class="input-mini js-price" maxlength="10" data-label="SKU售价" required="required">';
				html += '</td>';
				break;
			case 1: // 区间价
				var rangePrice = t.rangePrice, price = '';
				for(var quantity in rangePrice){
					price = product.custom_price[quantity] ? product.custom_price[quantity] : rangePrice[quantity];
					price = price ? parseFloat(price) : '';
					html += '<td class="input-td"'+title+'>';
					html += '  	<input type="text" name="products['+index+'][custom_price]['+quantity+']" value="'+price+'" class="input-mini js-price" maxlength="10" data-label="SKU区间价" required="required">';
					html += '</td>';
				}
				break;
			case 2: // 会员价
				var memberCards = t.memberCards, memberPrice = t.memberPrice, price = '', cardId = '', name='', range = '0.01,99999999';
				if(t.memberPrice[0]){
					var max = parseFloat(t.memberPrice[0]), min = (max/2).toFixed(2);
					range = -min+','+max;
				}
				for(var i=0; i<memberCards.length; i++){
					cardId = memberCards[i].id;
					name = i == 0 ? 'products['+index+'][price]' : 'products['+index+'][custom_price]['+cardId+']';
					price = product.custom_price[cardId] ? product.custom_price[cardId] : memberPrice[cardId];
					price = price ? parseFloat(price) : '';
					html += '<td class="input-td"'+title+'>';
					html += '  	<input type="text" name="'+name+'" value="'+price+'" class="input-mini js-price" maxlength="10" data-label="SKU会员价"'+(i == 0 ? 'required="required"' : '')+' data-rule-range="'+range+'">';
					html += '</td>';
				}
			break;
			case 3: // 积分价
				html += '<td class="input-td">';
				html += '  	<input type="text" name="products['+index+'][original_price]" value="'+(product.original_price ? parseFloat(product.original_price) : '')+'" class="input-mini js-original_price" maxlength="10" data-label="SKU原价" required="required">';
				html += '</td>';
				
				var memberCards = t.memberCards, scorePrice = t.scorePrice, price = '', cardId = '', name1='', name2='';
				for(var i=0; i<memberCards.length; i++){
					cardId = memberCards[i].id;
					var data = product.custom_price[cardId] ? product.custom_price[cardId] : scorePrice[cardId];
					name1 = i==0 ? 'products['+index+'][price]' : 'products['+index+'][custom_price]['+cardId+'][]';
					name2 = i==0 ? 'products['+index+'][score]' : 'products['+index+'][custom_price]['+cardId+'][]';
					html += '<td class="input-td"'+title+' style="width:50px">';
					html += '  	<input type="text" name="'+name1+'" value="'+(data ? data[0] : '')+'" placeholder="¥" class="input-mini js-price" maxlength="10" data-label="SKU会员价" '+(i == 0 ? 'required="required"' : '')+' data-rule-range="0,999999999">';
					html += '</td>';
					html += '<td class="input-td"'+title+' style="width:70px">';
					html += '  	<input type="text" name="'+name2+'" value="'+(data ? data[1] : '')+'" placeholder="积分" class="input-mini js-score" maxlength="10" data-label="SKU兑换积分" '+(i == 0 ? 'required="required"' : '')+' data-rule-range="10,999999999">';
					html += '</td>';
				}
				break;
			case 4: // 单品代理
				var agentPrice = t.agentPrice;
				if(shiyong){
					var colspan = Object.keys(agentPrice).length * 3 + 1;
					html += '<td class="input-td" colspan="'+colspan+'" title="全国统一零售价">';
					html += '  	<input type="text" name="products['+index+'][price]" value="'+(product.price ? parseFloat(product.price) : '')+'" class="input-mini js-price js-shiyong" maxlength="10" data-label="SKU统一零售价" required="required" placeholder="全国统一零售价">';
					html += '</td>';
				}else{
					html += '<td class="input-td">';
					html += '  	<input type="text" name="products['+index+'][price]" value="'+(product.price ? parseFloat(product.price) : '')+'" class="input-mini js-price" maxlength="10" data-label="SKU统一零售价" required="required" placeholder="售价">';
					html += '</td>';
					
					for(var id in agentPrice){
						var data = product.custom_price[id];
						html += '<td class="input-td">';
						html += '  	<input type="text" name="products['+index+'][custom_price]['+id+'][first]" value="'+(data.first ? parseInt(data.first) : '')+'" class="input-mini js-first" maxlength="10" data-label="SKU'+data.title+'第一次拿货" placeholder="首" required="required">';
						html += '</td>';
						html += '<td class="input-td">';
						html += '  	<input type="text" name="products['+index+'][custom_price]['+id+'][second]" value="'+(data.second ? parseInt(data.second) : '')+'" class="input-mini js-second" maxlength="10" data-label="SKU'+data.title+'补货" placeholder="补" required="required">';
						html += '</td>';
						html += '<td class="input-td">';
						html += '  	<input type="text" name="products['+index+'][custom_price]['+id+'][price]" value="'+(data.price ? parseInt(data.price) : '')+'" class="input-mini js-price" maxlength="10" data-label="SKU'+data.title+'进货价" placeholder="进" required="required">';
						html += '</td>';
					}
				}
				break;
		}
		
		html += '<td class="input-td">';
		html += '	<input type="text" name="products['+index+'][cost]" value="'+(product.cost ? parseFloat(product.cost) : '')+'" class="js-cost input-mini" data-label="SKU成本" data-rule-range="0,999999999">';
		html += '</td>';
		html += '<td class="input-td">';
		html += '	<input type="text" name="products['+index+'][stock]" value="'+product.stock+'" class="js-stock input-mini" maxlength="10" data-label="SKU库存" required="required">';
		html += '</td>';
		html += '<td class="input-td" style="width:45px">';
		html += '	<input type="text" name="products['+index+'][weight]" value="'+product.weight+'" class="js-weight input-small" maxlength="10" data-label="SKU重量">';
		html += '</td>';
		html += '<td class="input-td">';
		html += '	<input type="text" name="products['+index+'][outer_id]" value="'+product.outer_id+'" class="js-outer_id input-small" maxlength="20">';
		if(product.id){
			html += '  	<input type="hidden" name="products['+index+'][id]" value="'+product.id+'">';
		}
		html += '  	<textarea class="hide" name="products['+index+'][sku_json]">'+t.toJSON(sku)+'</textarea>';
		html += '</td></tr>';
		return html;
	}
	,resetPriceAndStock: function(){
		var t = this,
			goods = t.goods,
			min = {price: 999999999, index: 0, column: 0},
			totalStock = 0,
			price = 0,
			$tr = $('#stock-region>.sku-table-right tbody>tr');
		
		$tr.each(function(i){
			var $price = $tr.eq(i).find('.js-price');
			$price.each(function(p){
				if($price.eq(p).hasClass('js-shiyong')){
					return true;
				}
				price = $price.eq(p).val();
				if(price == ''){
					return true
				}
				
				price = parseFloat(price);
				if(price < min.price){
					min.price = price;
					min.index = i;
					min.column = p;
				}
			});
			
			// 累加总库存
			var stock = $tr.eq(i).find('.js-stock').val();
			if(stock != ''){
				totalStock += stock*1;
			}
		});
		
		var $ontrol = $('.goods-price-control>.js-price_type');
		var $goodsPrice = $ontrol.find('.js-price');
		
		$tr.eq(min.index).find('.js-price').each(function(i){
			if(t.usedPriceType == 4){
				if(i == 0){
					$('#price').val(this.value);
				}else{
					$goodsPrice.eq(i-1).val(this.value);
				}
			}else{
				$goodsPrice.eq(i).val(this.value);
			}
		});

		if(t.usedPriceType == 3){
			var $goodsScore = $ontrol.find('.js-score');
			$tr.eq(min.index).find('.js-score').each(function(i){
				$goodsScore.eq(i).val(this.value);
			});
		}else if(t.usedPriceType == 4){
			var $first = $ontrol.find('.js-first');
			$tr.eq(min.index).find('.js-first').each(function(i){
				$first.eq(i).val(this.value)
			});
			
			var $second = $ontrol.find('.js-second');
			$tr.eq(min.index).find('.js-second').each(function(i){
				$second.eq(i).val(this.value)
			});
		}
		
		goods.cost = $tr.eq(min.index).find('.js-cost').val();
		$('#cost').val(goods.cost);

		goods.weight = $tr.eq(min.index).find('.js-weight').val();
		$('#weight').val(goods.weight);
		
		goods.stock = totalStock;
		$('#total_stock').val(goods.stock);
	}
	,editor: null
	,beforeInsertImage: function(e, list){return false}
	,initImg: function(){
		var t = this, editor = UE.getEditor('image_text_container');
		t.editor = editor;

		// 弹出图片上传框
		$('.js-picture-list .js-add-picture').on('click', function(){
			var $control = $(this).parent();
			
			editor.getDialog("insertimage").open();
			t.beforeInsertImage = function(e, list){
				var html = '';
		    	for(var i=0; i<list.length; i++){
		    		html += '<li><a href="'+list[i]['src']+'" target="_blank"><img src="'+list[i]['src']+'"></a><a class="js-delete-picture close-modal small hide">×</a></li>';
		    	}
		    	$control.before(html);
		    	return true;
			}
			return false;
		});

		$('.js-picture-list').on('click', '.js-delete-picture', function(){
			return $(this).parent().remove(), false
		});
		
		editor.addListener('beforeInsertImage', function(e, list){
			if(typeof t.beforeInsertImage == 'function'){
				return t.beforeInsertImage(e, list);
			}
			return false;
		});
	}
	// 添加规格
	,addGoodsSku: function(defaultData){
		var html = '';
		html += '<div class="sku-sub-group">';
		html += '	<h3 class="sku-group-title">';
		html += '		<select style="width:150px;" class="js-sku-list">'+$('#goods_sku_list').html()+'</select>';
		html += '		<a class="js-remove-sku-group remove-sku-group">&times</a>';
		html += '	</h3>';
		html += '	<div class="js-sku-atom-container sku-group-cont">';
		html += '		<div class="js-sku-atom-list sku-atom-list"></div>';
		html += '		<a href="javascript:;" class="js-add-sku-atom add-sku">+添加</a>';
		html += '	</div>';
		html += '</div>';
		
		var t = this, $html = $(html);
		$html.insertBefore('#add_goods_sku');
		var $selectSKU = $html.find('.sku-group-title>select');
		if(defaultData){
			$selectSKU.val(defaultData.id);
		}
		var $atomlist = $html.find('.js-sku-atom-container>.js-sku-atom-list');
		$selectSKU.select2({tags: true, placeholder: "请选择"});
		
		$selectSKU.on('select2:close', function(){
			if(this.value == ''){
				$html.remove();
			}
		}).on('select2:selecting', function(ev){
			var data = ev.params.args.data;
			
			var id = ev.params.args.data.id;
			if($selectSKU.val() == id){
				return true;
			}
			
			// 判断是否重复选择
			var exists = false;
			$('#goods_sku_content .js-sku-list').each(function(){
				if(this.value == id){
					exists = true;
					return false;
				}
			});
			
			if(exists){
				alertMsg('请勿重复选择规格！');
				return false;
			}
			
			// 添加sku
			if(!ev.params.args.data.element){
				$.ajax({
					url: '/api/addsku',
					type: 'post',
					dataType: 'json',
					async: false,
					data: {text: data.text},
					waitting: '正在添加规格',
					success: function(newData){
						$selectSKU.find('[value="'+data.id+'"]').attr('value', newData.id).removeAttr('data-select2-tag');
						$selectSKU.val([newData.id]).trigger("change");
						$selectSKU.select2('close');
					},
					error: function(){
						$selectSKU.find('[value="'+data.id+'"]').remove();
					}
				});
			}
		});
		
		// 移除
		$html.find('.sku-group-title>.js-remove-sku-group').on('click', function(){
			$html.remove();
			t.resetSKUStore();
		});
		
		var $js_sku_atom_container = $html.find('.js-sku-atom-container').on('click', '.sku-atom>.js-remove-sku-atom', function(){
			var $this = $(this);
			$this.parent().remove();
			t.resetSKUStore();
		});
		
		// 获取规格值
		$selectSKU.on('change', function(){
			var value = this.value;
			t.getSkuChildren(value);
			
			// 移除已选择的项
			$atomlist.empty();
			$html.find('.js-add-sku-atom').popover('hide');
			t.resetSKUStore();
		});
		
		$html.find('.js-add-sku-atom').popover({
			html: true,
			placement: 'bottom',
			trigger: 'manual',
			content: '正在加载中...'
		}).on('click', function(){
			var $this = $(this),pid = $selectSKU.val();
			$this.popover('show');
			var $content = $this.data('popover').$tip.find('.popover-content');
			$content.html('正在加载中...');
			
			var $btn_ok, $select_sku_val;
			t.getSkuChildren(pid, function(options){
				$content.html('<select style="width:276px" multiple="multiple">'+options+'</select><input type="button" class="btn btn-primary btn-ok" value="确定" style="margin-left:15px"> <input type="button" class="btn btn-cancel" value="取消">');
				$btn_ok = $content.find('.btn-ok');
				$select_sku_val = $content.find('select');
				$select_sku_val.on('select2:selecting', function(ev){
					var temp_data = ev.params.args.data;
					if(temp_data.element){
						return true;
					}
					
					// ajax添加数据
					$btn_ok.attr('disabled', 'disabled');
					var $this = $(this);
					$.ajax({
						url: __ADMIN__+'/api/addsku',
						type: 'post',
						dataType: 'json',
						data: {pid: pid, text: temp_data.text, shop_id: t.shop_id},
						async: false,
						success: function(newData){
							t.skuChildren[pid] += '<option value="'+newData.id+'">'+newData.text+'</option>';
							var $option = $select_sku_val.find('[value="'+newData.text+'"]');
							$option.attr('value', newData.id).removeAttr('data-select2-tag');
							var data = $option.data('data');
							data.id = newData.id;
							$option.data('data', data);
						},
						error: function(){
							return false;
						},
						complete: function(){
							$btn_ok.removeAttr('disabled');
						}
					});
				}).select2({
					tags: true,
					placeholder: "请选择"
				});
			});
			
			// 关闭弹窗
			$content.find('.btn').on('click', function(){
				if(this.classList.contains('btn-primary')){ // 确定
					var data = $select_sku_val.select2('data');
					var html = '';
					var sku_id = $selectSKU.val();
					var addImg = $('#js-addImg-function').prop('checked') ? true : false;
					for(var i in data){
						if(isNaN(data[i]['id'])){
							continue;
						}
						if($atomlist.find('span[data-atom-id="'+data[i]['id']+'"]').length == 0){
							html += t.getAtom(data[i]);
						}
					}
					if(html != ''){
						$atomlist.append(html);
						t.resetSKUStore();
					}
				}
				$this.popover('hide');
			});
		});
		
		// 初始化数据
		if(defaultData){
			if(defaultData.items){
				var html = '';
				for(var i=0;i<defaultData.items.length;i++){
					html += t.getAtom(defaultData.items[i]);
				}
				$atomlist.append(html);
			}
		}
		
		var _sku_id = $selectSKU.val();
		if(!_sku_id){
			$selectSKU.select2("open");
		}else{
			t.getSkuChildren(_sku_id, function(options){
				sku_options = options;
			});
		}
		
		// 弹出图片上传框
		$html.find('.js-sku-atom-list').on('click', '.js-btn-add', function(){
			t.editor.getDialog("insertimage").open();
			var btnAddImg = this;
			t.beforeInsertImage = function(e, list){
				btnAddImg.innerHTML = '<img src="'+list[0]['src']+'" class="js-img-preview">';
				return true;
			}
			return false;
		});
	}
	,getAtom: function(data){
		var html = '';
		html += '<div class="sku-atom">';
		html += '	<span data-atom-id="'+data.id+'">'+data.text+'</span>';
		html += '	<div class="close-modal small js-remove-sku-atom">×</div>';
		html += '	<div class="upload-img-wrap">';
		html += '		<div class="arrow"></div>';
		html += '		<div class="js-upload-container" style="position:relative;">';
		html += '			<div class="add-image js-btn-add">' + (data.img ? '<img src="'+data.img+'" class="js-img-preview">' : '+') + '</div>';
		html += '		</div>';
		html += '	</div>';
		html += '</div>';
		return html;
	}
	// 获取sku
	,skuChildren:{}
	,getSkuChildren: function(id, callback){
		var t = this;
		if(t.skuChildren[id] == undefined){
			$.ajax({
				url: __ADMIN__+'/api/skutree?id='+id+'&shop_id='+t.shop_id,
				dataType: 'json',
				waitting: '正在获取数据',
				success: function(data){
					var options = '';
					for(var i in data){
						options += '<option value="'+i+'">'+data[i]+'</option>';
					}
					t.skuChildren[id] = options;
					if(typeof callback == 'function'){
						callback(options);
					}
				}
			});
		}else if(typeof callback == 'function'){
			callback(t.skuChildren[id]);
		}
	},
	initAttr: function(){
		var $table = $('.attr-table tbody');
		var $addAttr = $('.js-add-attr');
		$addAttr.on('click', function(){
			var i = $addAttr.data('total') + 1;
			$addAttr.data('total',i);
			$table.append('<tr><th><input type="text" name="parameters['+i+'][key]" maxlength="8" placeholder="属性名称"></th><td><a class="delete-attr label label-warning">删除</a><input type="text" name="parameters['+i+'][value]" maxlength="128" placeholder="请输入属性值"></td></tr>');
			return false;
		});
		
		$table.on('click', '.delete-attr', function(){
			$(this).parent().parent().remove();
			return false;
		})
	},
	// 初始化发货地区
	initSendPlace: function(goods){
		var $sendPlace = $('#send_place');
		var list = Address.list[1];

		var html = '<div class="send-place"><ul>';
		var index = 1;
		for(var code in list){
			html += '<li data-code="'+code+'"><a>'+list[code].sname+'<i></i></a></li>';
			if(index % 8 == 0){
				html += '<li class="send-city"></li>';
			}
			index++;
		}
		html += '</ul></div>';
		$sendPlace.append(html);
		
		var $container = $sendPlace.children('.send-place');
		var $name = $sendPlace.children('.js-send-place-name');
		
		$sendPlace.on('click', 'li[data-code]', function(){
			var $this = $(this);
			$this.addClass('active');
			$this.siblings().removeClass('active');
			
			if($this.parent().parent().hasClass('send-city')){
				var $actives = $container.find('.active');
				var text = $actives.eq(0).text() + ' ' + $actives.eq(1).text();
				$name.html(text);
				goods.send_place = $actives.eq(1).data('code');
			}else{
				var list = Address.list[$this.data('code')];
				var html = '<ul>';
				for(var code in list){
					html += '<li data-code="'+code+'"><a href="javascript:;">'+list[code].sname+'</a></li>';
				}
				html += '</ul>';
				$this.siblings('.send-city').hide();
				$this.nextAll('.send-city:first').html(html).show();
			}
			
			return false;
		});
		
		// 默认值
		if(goods.send_place != '' && goods.send_place > 0){
			var city = Address.find(goods.send_place);
			var $li = $sendPlace.find('li[data-code="'+city.pcode+'"]');
			$li.trigger('click');
			$li.nextAll('.send-city:first').find('li[data-code="'+goods.send_place+'"]').trigger('click');
		}
	},
	// 禁止选中地区下单(偏远地区)
	initRemoteArea: function(goods){
		var $remote_area = $('#remote_area'),
			$txt = $remote_area.find('.js-remote_area'),
			list = Address.list[1];

		// 特殊地区标红，方便快速选中
		var redList = ['150000', '640000', '630000', '620000', '540000', '530000', '650000', '460000'];
		var html = '<div class="send-place"><ul>', colorClass = '';
		for(var code in list){
			colorClass = redList.indexOf(code) > -1 ? 'color-red' : '';
			html += '<li data-code="'+code+'"><a href="javascript:;" class="'+colorClass+'">'+list[code].sname+'</a></li>';
		}
		html += '<li data-code="clear"><a href="javascript:;" style="color:#08c">清除</a></li></ul></div>';
		$remote_area.append(html);
		
		var $container = $remote_area.children('.send-place');
		$remote_area.on('click', 'li', function(){
			var $this = $(this),
			province_code = $this.data('code'),
			selectedTxt = [],
			selectedCode = [];
			
			if(province_code == 'clear'){
				$this.siblings('.active').removeClass('active');
			}else{
				$this.toggleClass('active');
				var html = $txt.html(),
					code = goods.remote_area,
					province_name = $this.text();
				if(code != ''){
					selectedTxt = html.split(';');
					selectedCode = code.split(',');
				}
							
				if($this.hasClass('active')){
					selectedTxt.push(province_name);
					selectedCode.push(province_code);
				}else{
					selectedTxt.splice(selectedTxt.indexOf(province_name), 1);
					selectedCode.splice(selectedCode.indexOf(province_code), 1);
				}
			}

			$txt.html(selectedCode.length == 0 ? '不限制' : selectedTxt.join(';'));
			goods.remote_area = selectedCode.length == 0 ? '' : selectedCode.join(',');
			return false;
		});
		
		// 默认值
		if(goods.remote_area != ''){
			var html = [];
			checked = goods.remote_area.split(',');
			for(var i=0; i<checked.length; i++){
				var $li = $remote_area.find('li[data-code="'+checked[i]+'"]');
				$li.addClass('active');
				html.push($li.text());
			}
			$txt.html(html.join(';'));
		}
	},
	initFreightTemplate: function(goods){
		var $template = $('#freight_id'), templateList = [];
		
		$template.siblings('.js-refresh').on('click', function(){
			$.ajax({
				url: __ADMIN__+'/freight_template',
				dataType: 'json',
				success: function(list){
					templateList = list;
					if(goods.append_freight_id){
						templateList.push(goods.append_freight_id);
					}
					templateList.push({id: 0, name: '卖家承担运费', describe: '系统级模板：全国统一包邮，费用由本店铺承担，建议自行创建运费模板！'});
					var html = '';
					for(var i=0; i<list.length; i++){
						html += '<option value="'+list[i].id+'"'+(goods.freight_id == list[i].id ? 'selected="selected"' : '')+'>'+list[i].name+'</option>';
					}
					$template.html(html);
				}
			});
			return false;
		}).trigger('click');
		
		$template.popover({
			title: '运费详情',
			html: true,
			placement: 'top',
			content: '',
			trigger: 'hover'
		});
		
		$template.hover(function(){
			var template = null,
			    currentId = $template.val();;
			for(var i=0; i<templateList.length; i++){
				if(templateList[i].id == currentId){
					template = templateList[i];
					break;
				}
			}
			
			if(!template){
				return false;
			}
			
			var $tip = $template.data('popover').$tip;
			$tip.find('.popover-title').html(template.name);
			$tip.find('.popover-content').html(template.describe);
		});
	}
	// 按数量定售价
	,usedPriceType: 0
	,useNormalPrice: function($control, goods){
		var t = this;
		var html = ''+
		'<div class="price-item">'+
	        '<input type="text" value="售　价" disabled="disabled">'+
	        '<input type="text" name="price" value="'+(goods.price ? parseFloat(goods.price) : '')+'" id="price" class="js-price" style="width:219px" placeholder="售出的价格" data-label="零售价" required="required" data-rule-range="0.01,999999999">'+
	        '<input type="text" value="元/件" disabled="disabled">'+
	    '</div>';
		$control.html(html);
		
		$control.find('#price').on('change', function(){
			goods.price = t.toFixed(this);
			return;
		});
	}
	,rangePrice: {}
	,useRangePrice: function($control, goods){
		var t = this, html = '', price = '', count = 0, required = '';
		
		for(var quantity in t.rangePrice){
			required = count < 2 ? 'required="required"' : '';
			price = t.rangePrice[quantity] ? parseFloat(t.rangePrice[quantity]) : '';
			html += '<div class="price-item">'+
				        '<input type="text" value="当购买" disabled="disabled">'+
				        '<input type="text" value="'+quantity+'" class="js-quantity" data-label="区间价 - 购买量"'+required+'>'+
				        '<input type="text" value="件及以上时" disabled="disabled">'+
				        '<input type="text" value="'+price+'" name="custom_price['+quantity+']" class="js-price" data-label="区间价 - 金额"'+required+'>'+
				        '<input type="text" value="元/件" disabled="disabled">'+
				    '</div>';
			count++;
		}
		
		for(var i=count; i<3; i++){
			required = i < 2 ? 'required="required" name="custom_price[_'+i+']"' : '';
			html += ''+
			'<div class="price-item">'+
		        '<input type="text" value="当购买" disabled="disabled">'+
		        '<input type="text" class="js-quantity" data-label="区间价 - 购买量"'+required+'>'+
		        '<input type="text" value="件及以上时" disabled="disabled">'+
		        '<input type="text" class="js-price" data-label="区间价 - 金额"'+required+'>'+
		        '<input type="text" value="元/件" disabled="disabled">'+
		    '</div>';
		}
		$control.html(html);
		
		// 区间价改变
		$control.find('.js-quantity, .js-price').on('change', function(){
			t.rangePrice = {};
			var $quantity = $control.find('.js-quantity'),
				$price = $control.find('.js-price'), min = 0;
			$quantity.each(function(i){
				var quantity = $quantity.eq(i).val(),
				price = $price.eq(i).val();
				
				quantity = /^[1-9]+[0-9]*$/.test(quantity) ? parseFloat(quantity) : '';
				$quantity.eq(i).val(quantity);
				price = price != '' && !isNaN(price) && price > 0 ? parseFloat(price) : '';
				$price.eq(i).val(price == '' ? '' : price);
				
				if(quantity != ''){
					t.rangePrice[quantity] = price;
					
					if(min == 0 || quantity < min){
						min = quantity;
					}
				}
			});
			
			t.useRangePrice($control, goods);
			if(min > 0){
				$('#min_order_quantity').val(min);
			}
			t.resetSKUStore();
			return false;
		});
	}
	,memberPrice: {}
	,useMemberPrice: function($control, goods){
		var t = this, list = t.memberCards, html = '', price = '', required='', range='0.01,999999999';
		for(var i=0; i<list.length; i++){
			if(i == 0){
				price = goods.price ? parseFloat(goods.price) : '';
				if(price != ''){
					range = -(price/2).toFixed(2)+','+price;
				}
			}else{
				price = t.memberPrice[list[i].id] ? t.memberPrice[list[i].id] : '';
			}
			required = i == 0 ? 'required="required"' : '';
			html += ''+
			'<div class="price-item member-price">'+
	            '<input type="text" value="'+(i==0 ? '默认价格' : list[i].title)+'" disabled="disabled">'+
	            '<input type="text" value="'+price+'" name="'+(i>0?'custom_price['+list[i].id+']' : 'price')+'" class="js-price" data-card_id="'+list[i].id+'" data-label="'+list[i].title+'" data-rule-range="'+range+'"'+required+'>'+
	            '<input type="text" value="元/件" disabled="disabled">'+
	        '</div>';
		}
		$control.html(html);
		$('#member_discount').removeAttr('checked');
		
		// 会员价改变
		var $member_price = $control.find('.js-price');
		$member_price.on('change', function(){
			var $this = $(this),
				card_id = $this.data('card_id'), 
				price = (this.value == '' || isNaN(this.value)) ? 0 : parseFloat(this.value);
			this.value = card_id == 0 && price < 0.01 ? '' : price;
			t.memberPrice[card_id] = price;
			
			if(card_id == 0){
				var min = 0.01, max = 99999999, range = '0.01,99999999';
				if(price > 0){
					var max = parseFloat(price),
						min = -(max/2).toFixed(2);
					range = min+','+max;
				}
				
				$member_price.each(function(i){
					if(i > 0){
						$member_price.eq(i).attr('data-rule-range', range);
					}
				});
			}else if(this.value == 0){
				this.value = '';
			}
			
			if(t.usedPriceType == 2){
				t.resetSKUStore();
			}
			return false;
		});
		alertMsg('价格为空的会员卡将使用“默认价格”，会员价可输入负数哦！');
	}
	,scorePrice: {}
	,useScorePrice: function($control){
		var t = this, list = t.memberCards, html = '', price = score = '', required = '';
		for(var i=0; i<list.length; i++){
			price = t.scorePrice[list[i].id] ? t.scorePrice[list[i].id][0] : '';
			score = t.scorePrice[list[i].id] ? t.scorePrice[list[i].id][1] : '';
			required = i == 0 ? 'required="required"' : '';
			html += ''+
			'<div class="price-item score-price">'+
	            '<input type="text" value="'+(i==0 ? '默认价格' : list[i].title)+'" disabled="disabled">'+
	            '<input type="text" value="'+price+'" class="js-price" name="'+(i>0?'custom_price['+list[i].id+'][]':'price')+'" data-card_id="'+list[i].id+'" data-label="'+list[i].title+'"'+required+'>'+
	            '<input type="text" value="+" disabled="disabled">'+
	            '<input type="text" value="'+score+'" class="js-score" name="'+(i>0?'custom_price['+list[i].id+'][]':'score')+'" data-card_id="'+list[i].id+'" data-label="'+list[i].title+'"'+required+'>'+
	            '<input type="text" value="积分" disabled="disabled">'+
	        '</div>';
			
			t.scorePrice[list[i].id] = [price, score];
		}
		$control.html(html);

		$control.on('change', '.js-price',function(){
			var $this = $(this), price = t.toFixed(this), card_id = $this.data('card_id');
			t.scorePrice[card_id][0] = price;
			return false;
		});
		
		$control.on('change', '.js-score',function(){
			var $this = $(this), $price = $this.siblings('.js-price'), price = $price.val(), card_id = $this.data('card_id');
			var score = t.toScore(this);
			if(!price){
				price = 0;
				$price.val(0);
			}
			t.scorePrice[card_id][0] = price;
			t.scorePrice[card_id][1] = score;
			return false;
		});
		
		alertMsg('价格 或 积分为空的会员卡将使用“默认价格”');
	}
	,show_original_price: function(goods, show){
		var t = this, $container = $('.js-cost-control');
		if(!show){
			$container.prev('.js-original_price').remove();
			return null;
		}
		
		$container.before('<div class="js-original_price price-item"><input type="text" value="原　价" disabled="disabled"><input type="text" name="original_price" value="'+goods.original_price+'" id="original_price" style="width:219px" placeholder="必填" data-label="原价" required="required" data-rule-min="0.01"><input type="text" value="元/件" disabled="disabled"></item>');
		$container.prev().on('change', 'input', function(){
			goods.original_price = t.toFixed(this);
			return false;
		});
	}
	,agentPrice: {}
	,agentList: {}
	,useAgentPrice: function($control, goods){
		var t = this, html = '';
		$control.html('<div class="js-agent-name" style="margin-bottom:10px"></div><div class="js-agent-list"></div><div class="price-item"><input type="text" value="零售价" disabled="disabled"><input type="text" name="price" value="'+goods.price+'" id="price" style="width:219px" placeholder="全国统一零售价" data-label="零售价" required="required" data-rule-min="0.01"><input type="text" value="元/件" disabled="disabled"></div>');
		$.ajax({
			url: __ADMIN__+'/api/agent?shop_id='+goods.shop_id,
			dataType: 'json',
			success: function(list){
				t.agentList = {};
				var html = '<select><option value="">请选择</option>';
				for(var i=0; i<list.length; i++){
					html += '<option value="'+list[i].id+'">'+list[i].title+'</option>';
					
					t.agentList[list[i].id] = list[i];
				}
				html += '<select>';
				$control.children('.js-agent-name').html(html);
				
				var $select = $control.find('.js-agent-name>select');
				$select.select2({
					tags: false,
					placeholder: "请选择代理分组",
				});
				$control.find('.js-agent-name').append('<p class="help-desc">代理价和统一售价相同时被视为试用装，用户不会自动升级为代理，且限购和会员卡只针对试用装，起批数量将根据首次和补货数量自动应用</p>');
				
				if(goods.price_type == 4){
					var gid = 0;
					for(gid in goods.custom_price){
						break;
					}
					$select.val(gid.substr(0, gid.length-1)).trigger('change');
				}
			}
		});
		
		$control.children('.js-agent-name').on('change', 'select',function(){
			var html = '', groupId = this.value, list = t.agentList[groupId].items;
			t.agentPrice = {};
			for(var level in list){
				var data = list[level], id = groupId+''+level;
				var def = goods.price_type == 4 ? goods.custom_price[id] : {price: '', first: '', second: ''};
				t.agentPrice[id] = {title:data.title, price: def.price, first: def.first, second: def.second};
				html += '<div class="price-item agent-price" data-id="'+id+'">'+
							'<input type="text" value="首次拿货" disabled="disabled">'+
							'<input type="text" name="custom_price['+id+'][first]" class="js-first" value="'+def.first+'" placeholder="首">'+
				            '<input type="text" value="件成为“'+data.title+'”，每次补货至少" disabled="disabled">'+
				            '<input type="text" name="custom_price['+id+'][second]" class="js-second" value="'+def.second+'" placeholder="补">'+
				            '<input type="text" value="件，进货价" disabled="disabled">'+
				            '<input type="text" value="'+def.price+'" class="js-price" name="custom_price['+id+'][price]" data-label="'+data.title+'" placeholder="进">'+
				            '<input type="text" value="元/件" disabled="disabled">'+
				        '</div>';
			}
			
			$control.children('.js-agent-list').html(html);
			t.resetSKUStore();
			return false;
		});
		
		$control.children('.js-agent-list').on('change', 'input', function(){
			var $this = $(this), id = $this.parent().data('id');
			if($this.hasClass('js-price')){
				t.toFixed(this);
				t.agentPrice[id].price = this.value;
			}else if($this.hasClass('js-first')){
				t.toScore(this);
				t.agentPrice[id].first = this.value;
			}else{
				t.toScore(this);
				t.agentPrice[id].second = this.value;
			}
			return false;
		});
		
		$('#price').on('change', function(){
			t.toFixed(this);
			goods.price = this.value;
			return false;
		});
	}
	,priceType: [{title: '价格', fn: 'useNormalPrice'}, {title: '区间价', fn: 'useRangePrice', data: 'rangePrice'}, {title: '会员价', fn: 'useMemberPrice', data: 'memberPrice'}, {title: '积分价', fn: 'useScorePrice', data: 'scorePrice'}, {title: '单品代理', fn: 'useAgentPrice', data: 'agentPrice'}]
	,initPriceType: function(goods){
		var t = this,
			priceType = t.priceType,
			$control = $('.goods-price-control>.js-price_type'),
			dataKey = priceType[goods.price_type].data;

		// 更改价格方式
		t.usedPriceType = goods.price_type - 1;
		if(dataKey){
			t[dataKey] = goods.custom_price;
		}
		$('#toggle_price').on('click', function(){
			var $this = $(this);
			
			var index = t.usedPriceType + 1;
			if(index == priceType.length){index = 0}
			t.usedPriceType = index;
			
			$this.html(priceType[index].title);
			t[priceType[index].fn]($control, goods);
			
			$('#member_discount').prop('disabled', (index == 2 || index == 3) ? true : false);
			$('#min_order_quantity').prop('readonly', index == 1 ? true : false);
			
			if(index == 3){
				t.show_original_price(goods, true);
				$('#join_discount').hide();
				$('#score').attr('disabled', 'disabled').parents('.control-group:first').hide();
			}else{
				t.show_original_price(goods, false);
				$('#join_discount').show();
				$('#score').removeAttr('disabled').parents('.control-group:first').show();
			}
			
			// 重置SKU区域
			t.resetSKUStore();
			return false;
		}).trigger('click');
		
		// 成本价改变
		$('#cost').on('change', function(){
			goods.cost = t.toFixed(this);
			return false;
		});
	}
	,toFixed: function(element, fushu){
		var value = element.value;
		if(value != '' && isNaN(value) || (value < 0 && !fushu)){
			value = '';
		}else if(value != ''){
			value = parseFloat(value).toFixed(2);
			value = parseFloat(value);
		}
		element.value = value;
		return value;
	}
	,toScore: function(element){
		var value = element.value;
		if(value == '' || isNaN(value) || value < 1){
			value = '';
		}else{
			value = parseInt(value);
		}
		element.value = value;
		return value;
	}
	,toJSON: function(data){
		if(!data){
			return ''
		}
		
		return JSON.stringify(data, function(key, value){
			if(value != '' && !isNaN(value)){
				return parseFloat(value);
			}
			return value;
		});
	}
}