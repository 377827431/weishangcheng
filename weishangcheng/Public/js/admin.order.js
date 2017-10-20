$(function(){
	var $searchForm = $('#order_search');
	 $searchForm.on('submit', function(){
		 $('#page').val(1);
		 var data = $(this).serializeArray();
		 data.push({name: 'page', value: 1});
		 getOrderList(data);
		 return false;
	 }).on('change', 'input',function(){
		this.value = $.trim(this.value);
	 }).trigger('submit');
	 
	 // 初始化运单修改模态框
	 $('#sellerSendModal').modal({show: false}).find('.btn-primary').on('click', function(){
		 var $this = $(this);
		 var tid = $this.prevAll('.js-tid').val();
		 var send = $this.prevAll('.js-send').val();
		 
		 $.ajax({
			 url: MODULE+'/order/sendOne',
			 type: 'post',
			 dataType: 'json',
			 data: {tid: tid, send: send},
			 success: function(){
				$searchForm.trigger('submit');
				$('#sellerSendModal').modal('hide');
			 }
		 });
	 });
	
	// 打单并发货
	var $status = $searchForm.find('.js-status');
	$('#out_stock').hover(function(){
		$(this).data('status', $status.val());
		$status.val(3);
		return false;
	}, function(){
		var status = $(this).data('status');
		$status.val(status);
		return false;
	}).on('click', function(){
		var $all_shop = $('#all_shop')
		   ,tip = '';
		if($all_shop.length == 1){
			var shop_id = $all_shop.val();
			if(shop_id == '' || shop_id == 'all'){
				return alert('请选择要出库的店铺'), false;
			}
			tip = '【' + $all_shop.find(':selected').text() + '】';
		}
		
		if(!confirm(tip + '确定要出库吗？')){
			return false;
		}
		
		var data = $searchForm.serializeArray();
		var url = MODULE+'/order/out_stock?';
		for(var i=0; i<data.length; i++){
			url += (i > 0 ? '&' : '')+data[i].name+'='+data[i].value;
		}
		window.location.href = url;
		return false;
	});
	
	// 导出订单
	$('#printOrder').on('click', function(){
		var data = $searchForm.serializeArray();
		var url = MODULE+'/order/printOrder?';
		for(var i=0; i<data.length; i++){
			url += (i > 0 ? '&' : '')+data[i].name+'='+data[i].value;
		}
		window.open(url);
		return false;
	});
	initEvent();
});

var ov = {
	init: function(){
		
	},
	bindEvent: function(){
		
	},
	// 弹出关闭订单选项
	cancelTrade: function($element, tid, callback){
		var removeModal = function(){
			$('#cancel_trade_modal').remove();
			$('body').unbind('click', removeModal);
		}
		removeModal();
		
		var offset = $element.offset(), left = offset.left - 141 + $element.width() / 2, top = offset.top - 120;
		var html = '<div id="cancel_trade_modal" class="popover fade top in" style="top:'+top+'px; left:'+left+'px; display: block;">'+
	    '<div class="arrow"></div>'+
	    '<h3 class="popover-title">取消订单'+tid+'</h3><div class="popover-content"><div class="text-center">'+
	    '<select>'+
	    '<option value="">请选择一个取消订单理由</option>'+
	    '<option value="1">买家误拍或重拍了</option>'+
	    '<option value="6">买家主动取消</option>'+
	    '<option value="3">无法联系上买家</option>'+
	    '<option value="4">买家无诚意完成交易</option>'+
	    '<option value="5">已经缺货无法交易</option>'+
	    '<option value="0">其他</option>'+
	    '</select>'+
	    '<button class="btn btn-primary btn-mini">提交</button></div></div></div>';
		
		var $body = $('body').append(html),
		$modal = $('#cancel_trade_modal');
		$modal.on('click', function(){
			return false;
		});
		$body.on('click', removeModal);
		
		var $btn = $modal.find('.btn');
		$btn.on('click', function(){
			var $select = $modal.find('select'), reason = $select.val();
			if(reason == ''){
				alertMsg('请选择关闭原因');
			}else{
				$.ajax({
					url: MODULE+'/order/cancel',
					data: {tid: tid, reason: reason},
					type: 'post',
					dataType: 'json',
					success: function(){
						callback.call(tid);
					}
				});
				removeModal();
			}
			return false;
		});
	},
	// 设置卖家备注
	setRemark: function(tid, remark, callback){
		var html = '<div id="sellerRemarkModal"class="modal hide fade modal-middle"tabindex="-1"role="dialog"aria-labelledby="myModalLabel"aria-hidden="true"><div class="modal-header"><button type="button"class="close"data-dismiss="modal"aria-hidden="true">×</button><h3 id="myModalLabel">卖家备注</h3></div><div class="modal-body text-center"><textarea class="js-remark"placeholder="最多可输入256个字符"maxlength="256"style="width: 507px; margin: 0px 0px 10px; height: 123px">'+remark+'</textarea><button class="btn btn-primary"style="width: 150px">保存</button></div></div>';
		$('body').append(html);
		var $modal = $('#sellerRemarkModal');
		$modal.modal('show').on('hidden', function(){return $modal.remove(),false});
		
		$modal.find('.btn').on('click', function(){
			var remark = $.trim($modal.find('textarea').val());
			$.ajax({
				 url: MODULE+'/order/remark',
				 type: 'post',
				 dataType: 'json',
				 data: {tid: tid, remark: remark},
				 success: function(){
					 callback(remark)
				 }
			 });
			 $modal.modal('hide');
		});
	},
	// 上传快递单号
	uploadExpressNo: function(tid, text, callback){
		var html = '<div id="sellerSendGoodsModal"class="modal hide fade modal-middle"tabindex="-1"role="dialog"aria-labelledby="myModalLabel"aria-hidden="true"><div class="modal-header"><button type="button"class="close"data-dismiss="modal"aria-hidden="true">×</button><h3 id="sendModalLabel">运单信息</h3><span>运单格式为<span style="color:red">快递公司:运单号</span>，多笔运单使用分号或换行分割。</span></div><div class="modal-body text-center"><textarea placeholder="圆通速递:88888888;&#13;&#10;韵达:88888888;&#13;&#10;顺丰速运:88888888;"maxlength="256"style="width: 507px; margin: 0px 0px 10px; height: 123px">'+text+'</textarea><button class="btn btn-primary"style="width: 150px">发货</button></div></div>';
		$('body').append(html);
		
		var $modal = $('#sellerSendGoodsModal');
		$modal.modal({keyboard: false}).on('hidden', function(){return $modal.remove(), false});
		
		var $text = $modal.find('textarea'), $btn = $modal.find('.btn'), has_error = false;
		
		var submit = [];
		$text.on('change', function(){
			has_error = false;
			text = this.value.replace(/：/g, ':').replace(/(；|;|,|，|。|\.)/g, '\n'),
			list = text.split('\n'), count = 0, result = {};
			
			for(var i=0; i<list.length; i++){
				if(!list[i]){
					continue
				}
				
				var express = list[i].replace(/\s+/g, '').split(':');
				if(express.length == 1 || !express[0] || !express[1]){
					var str = (express[0] ? express[0] : express[1]).toString(), matchs = str.match(/[0-9a-zA-Z]{8,}/);
					if(/^[0-9a-zA-Z]+$/.test(str) || /^[0-9a-zA-Z]{0, 8}$/.test(str) || !matchs){
						$btn.attr('disabled', 'disabled');
						return false;
					}
					
					express[1] = matchs[0];
					if(matchs.index > 0){
						express[0] = str.substring(0, matchs.index)
					}else{
						express[0] = str.replace(matchs[0], '');
					}
				}
				
				if(!/^\W{2,}$/.test(express[0]) || !/^[0-9a-zA-Z]{8,}$/.test(express[1])){
					has_error = true
				}
				
				if(!result[express[1]]){
					result[express[1]] = {index: count, name: express[0], no: express[1]};
				}else{
					result[express[1]]['name'] = express[0];
				}
				count++;
			}
			
			submit = [];
			for(var no in result){
				var data = result[no];
				submit[data.index] = {name: data.name, no: data.no};
			}

			text = '';
			for(var i=0; i<submit.length; i++){
				text += (i > 0 ? '\n' : '')+submit[i].name+':'+submit[i].no;
			}
			this.value = text;
			
			if(has_error){
				$btn.attr('disabled', 'disabled')
			}else{
				$btn.removeAttr('disabled')
			}
		});
		
		$btn.on('click', function(){
			if(submit.length == 0){
				return $modal.modal('hide'), false;
			}

			$btn.attr('disabled', 'disabled');
			$.ajax({
				url: MODULE + '/order/sendGoods',
				data: {tid: tid, express: submit},
				type: 'post',
				dataType: 'json',
				success: function(){
					return $modal.modal('hide'), callback(submit), false;
				},
				error: function(){
					$btn.removeAttr('disabled')
				}
			});
			return false;
		});
	},
	// 退款
	refund: function(tid, callback, has_changed){
		if(has_changed || $('#refund_container').length == 0){
			$.get(MODULE+'/refund/detail?tid='+tid, function(html){
				$('#refund_container').remove();
				$('body').append('<div id="refund_container">'+html+'</div>');

				var $container = $('#refund_container'), $modal = $container.find('.modal');
				$modal.modal({keyboard: false}).on('hide', function(){
					$container.remove();
					if(has_changed){
						callback();
					}
				});
				ov.refund(tid, callback);
			});
			return;
		}
		
		var $container = $('#refund_container'), $modal = $container.find('.modal');
		
		var $refundContainer = $container.find('.refund-list');
		$container.find('.refund-table-header').css('margin-right', $refundContainer[0].offsetWidth - $refundContainer[0].clientWidth);
		
		var $script = $('#trade_refund_orders'),
		orders = $script.html(),
		order = {}, actions = [];
		$script.remove();
		orders = eval(orders);
		
		// 必须控件
		var $reason  = $modal.find('.js-reason'),
		$refund_fee  = $modal.find('.js-refund_fee'),
		$refund_post = $modal.find('.js-refund_post'),
		$remark      = $modal.find('.js-remark'),
		$sremark     = $modal.find('.js-refund_sremark'),
		$images      = $modal.find('.js-images'),
		$actions     = $modal.find('.js-action'),
		$name        = $modal.find('.js-receiver_name'),
		$mobile      = $modal.find('.js-receiver_mobile'),
		$address     = $modal.find('.js-receiver_address'),
		$type        = $modal.find('.js-refund_type'),
		$expressNo   = $modal.find('.js-express_no'),
		$refund_quantity = null;
		
		$refundContainer.on('click', 'tr', function(){
			var $tr = $(this);
			if($tr.hasClass('info')){
				return false
			}
			
			// 恢复上次选中的内容
			var $prev = $tr.siblings('.info');
			if($prev.length > 0){
				$prev.removeClass('info');
				$prev.find('.js-refund-num').html(orders[$prev.data('index')].refund.refund_quantity);
			}
			
			// 重新覆盖数据
			$tr.addClass('info');
			order = $.extend({}, orders[$tr.data('index')]);
			var refund = order.refund;
			$tr.find('.js-refund-num').html('<input type="text" class="js-num" name="refund_quantity" value="'+(refund.refund_quantity > 0 ? refund.refund_quantity : '')+'" placeholder="'+refund.refund_quantity+'" style="width:30px;text-align:center" min="1" max="'+order.quantity+'">');
			
			$reason.val(refund.refund_reason).removeAttr('disabled');
			$refund_fee.val(refund.refund_fee).removeAttr('disabled');
			$refund_post.val(refund.refund_post).removeAttr('disabled');
			$sremark.val(refund.refund_sremark).removeAttr('disabled');
			$remark.html('<span class="label label-warning">'+(refund.is_received ? '已' : '未')+'收到物品</span>'+refund.refund_remark);
			$name.val(refund.receiver_name);
			$mobile.val(refund.receiver_mobile);
			$address.val(refund.receiver_address);
			$type.prop('checked', refund.refund_type == 1);
			if(refund.refund_express){
				$type.parent().hide();
				$expressNo.show().html(refund.refund_express).attr('href', 'https://m.kuaidi100.com/result.jsp?nu='+refund.refund_express)
			}else{
				$type.parent().show();
				$expressNo.hide()
			}
			
			var images = refund.refund_images, html = '';
			if(refund.refund_images){
				for(var i=0; i<images.length; i++){
					html += '<a href="'+images[i]+'" target="_blank" style="margin-right:10px;"><img src="'+images[i]+'" style="width:32px;height:32px;"></a>';
				}
				html = '<th>图片凭证</th><td colspan="5">'+html+'</td>';
			}
			$images.html(html);
			
			// 执行数量改变 - 计算最高退款金额
			$refund_quantity = $tr.find('.js-num');
			if(refund.refund_quantity > 0){
				$refund_quantity.trigger('change');
			}
			
			// 可操作的按钮
			actions = [], html = '';
			switch(refund.refund_status){
				case -1: // 无退款
					alertMsg('此单用户无法申请退款，但您可继续操作');
				case 0: // 无退款
					actions.push({text: '添加退款', className: 'btn-danger', action: 'add'});
					break;
				case 11: // 退款申请中
					actions.push({text: '拒绝退款', className: '', action: 'refuse'});
					actions.push({text: '同意退款', className: 'btn-danger', action: 'agree'});
					break;
				case 12: // 待上传单号
					actions.push({text: '取消退款', className: '', action: 'cancel'});
					actions.push({text: '提前退款', className: 'btn-danger', action: 'advance'});
					break;
				case 13: // 等待退款
					actions.push({text: '取消退款', className: '', action: 'cancel'});
					actions.push({text: '立即退款', className: 'btn-danger', action: 'refundNow'});
					break;
			}
			for(var i=0; i<actions.length; i++){
				html += '<button type="button" class="btn '+actions[i].className+'" data-action="'+actions[i].action+'">'+actions[i].text+'</button>';
			}
			$actions.html(html);
			return false;
		})
		// 退款数量改变
		.on('change', '.js-num', function(){
			var value = this.value, max = parseFloat(this.max);
			value = value == '' || isNaN(value) ? '' : parseInt(value);
			
			if(value && value > 0){
				if(value > max){
					value = max;
				}
				max = order.payment;
				$refund_fee.attr('max', max).attr('placeholder', '最高'+max+'元');
			}else{
				value = '';
			}
			
			this.value = value;
			return false
		});
		
		// 退款金额改变
		$refund_fee.on('change', ov.priceChangedEvent);
		// 退款运费改变
		$refund_post.on('change', ov.priceChangedEvent);
		
		// 点击按钮
		$actions.on('click', '.btn', function(){
			$btns = $actions.find('.btn');
			var post = {
				tid: tid,
				refund_id: order.oid,
				refund_reason: $reason.val(),
				refund_quantity: $refund_quantity.val(),
				refund_fee: $refund_fee.val(), refund_post: $refund_post.val(),
				refund_sremark: $sremark.val(), receiver_name: $name.val(),
				receiver_mobile: $mobile.val(), receiver_address: $address.val(),
				refund_type: $type[0].checked ? 1 : 0,
				action: $(this).data('action')
			};

			if(post.refund_reason == '') return $reason.focus(), false
			if(post.refund_quantity == '') return $refund_quantity.focus(), false
			if(post.refund_fee == '') return $refund_fee.focus(), false
			if(post.refund_post == '') return $refund_post.focus(), false
			if(post.refund_type == 0){
				if(post.receiver_name == '') return $name.focus(), false
				if(post.receiver_mobile == '') return $mobile.focus(), false
				if(post.receiver_address == '') return $address.focus(), false
			}

			if(post.action == 'refuse' && post.refund_sremark.length < 5){
				$sremark.focus();
				return alertMsg('请输入拒绝原因'), false;
			}
			
			$btns.attr('disabled', 'disabled');
			$.ajax({
				url: MODULE+'/refund/handle',
				type: 'post',
				data: post,
				dataType: 'json',
				success: function(){
					ov.refund(tid, callback, true);
				}
			});
			return false;
		})
	},
	priceChangedEvent: function(){
		var value = this.value, min=0, max = parseFloat(this.max);
		value = value == '' || isNaN(value) ? 0 : parseFloat(value);
		if(value > max){
			value = max
		}else if(value < min){
			value = ''
		}
		
		this.value = value === '' ? '' : parseFloat(value.toFixed(2))
	}
}

function initEvent(){
	var $searchForm = $('#order_search');
	// 取消订单
	$('#order_list_table').on('click', '.js-cancel-order', function(){
		var $this = $(this), tid = $(this).parents('tbody:first').data('tid');
		ov.cancelTrade($this, tid, function(){
			getOrderList();
		});
		return false;
	})
	// 卖家订单备注
	.on('click', '.js-set-seller-remark', function(){
		var $btn = $(this),
		$tbody = $btn.parents('tbody:first'),
		tid = $tbody.data('tid'),
		$remark = $tbody.find('.js-trade-remark'),
		remark = $remark.html();
		
		return ov.setRemark(tid, remark, function(remark){
			$remark.html(remark);
			if(remark == ''){
				$btn.addClass('color-gray');
			}else{
				$btn.removeClass('color-gray');
			}
		}), false
	})
	// 独立运单信息维护
	.on('click', '.js-upload-express-no', function(){
		var $btn = $(this), $tbody = $btn.parents('tbody:first'), tid = $tbody.data('tid'), $express = $tbody.find('.js-express'), $no = $express.children(), text = '';
		$no.each(function(i){
			if(i > 0){
				var name = $no.eq(i).attr('title'),index = name.indexOf('(');
				text += (index > 0 ? name.substring(0, index) : name)+':'+$no.eq(i).html()+'\n';
			}
		});
		return ov.uploadExpressNo(tid, text, function(list){
			getOrderList();
		}), false;
	})
	// 退款
	.on('click', '.js-refund', function(){
		var tid = $(this).parents('tbody:first').data('tid');
		return ov.refund(tid, function(){
			getOrderList()
		}), false;
	})
	// 反馈
	.on('click', '.js-goods_feedback',function(){
		var $this = $(this)
		   ,id = $this.data('gid')
		   ,tid = $this.parents('tbody:first').data('tid');
		$.get(MODULE+'/goods/feedback?goods_id='+id+'&tid='+tid, function(html){
			var $html = $(html);
			$html.appendTo('body');
		});
        return false;
	})
	// 填写外部单号
	.on('click', '.js-order-no', function(){
		var $this = $(this),
			$tbody = $this.parents('tbody:first'),
		    tid = $tbody.data('tid');
		
		$.get('/order/setOutTradeNo?tid=' + tid, function(html){
			$('body').append(html)
			.unbind('out_trade_no_change')
			.on('out_trade_no_change', function(e, data){
				$searchForm.trigger('submit');
			});
		});
		return false;
	})
	// 调价
	.on('click', '.js-adjust_fee', function(){
		var $this = $(this),
    		$tbody = $this.parents('tbody:first'),
    	    tid = $tbody.data('tid');
		$.get(MODULE+'/order/adjust?tid=' + tid, function(html){
			$('body').append(html)
		});
		return false;
	});
}

// 获取订单列表
var prev_get_order_parameters = null;
function getOrderList(data){
	if(!data){
		data = prev_get_order_parameters;
	}else{
		prev_get_order_parameters = data;
	}
	
	$.ajax({
		url: MODULE+'/order',
		data: data,
		success: function(html){
			$('#order_list_table').html(html);
			
			var $pagination = $('#pagination'), total = $pagination.data('total');
			if(total > 0){
				var page = $pagination.data('page');
				$pagination.pagination({
					itemsCount: total,
					pageSize: $pagination.data('size'),
					displayPage: 10,
					currentPage: page,
					showCtrl: true,
					onSelect: function (page) {
						data[data.length - 1].value = page;
						getOrderList(data);
						document.body.scrollTop = 370;
					}        
				});
				
				var $lazy = $('#order_list_table').find(".js-lazy");
				$lazy.lazyload({
					placeholder : "__CDN__/img/logo_rgb.jpg",
				    threshold : 270
				})
			}
		}
	});
}