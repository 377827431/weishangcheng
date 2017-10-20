require(['jquery'],function(){
	//关闭备注弹窗
	$('.order_ps_dailog .dailog_mask').on('click',function(){
		$('.order_ps_dailog').hide();
	})
	//备注弹窗显示
	$('.add_order_ps_btn').on('click',function(){
		//初始化备注弹窗，这个弹窗的初始值可能需要从数据库中获取
		//此处用123代替。
		initOrderPsDailog($('.ps_comment').val());
		$('.order_ps_dailog').show();
		var init_font_num = $('textarea.ps_comment').val().length;
		$('.ps_comment_container p').text(init_font_num);
	});
	//备注字数统计
	$('.ps_comment').on('input',function(){
		var _temp = $(this).val();
		var num = _temp.length;
		if(num > 250){
			$(this).val($(this).val().substring(0,250));
			num = 250;
		}
		$(this).siblings('p').text(num);
	})
	//订单备注保存
	$('.order_ps_save_btn').on('click',function(){
		var param = $.trim($('.ps_comment_container .ps_comment').val());
		var order_num = $('.order_num').html();
		// if(param == "") param = "无";
		// console.log("订单备份保存",param);
		//如果ajax保存成功
        $.ajax({
            url: '/seller/order/remark',
            dataType: 'json',
            type:'post',
            data:{data:param,order_num:order_num},
            success: function(data){
                $('.order_ps_dailog').hide();
                $('.order_note_box span.order_note_pp').html(param);
            }
        });
		// $('.ps_comment_container .ps_comment').val("");
	})
	//订单弹窗初始化
	function initOrderPsDailog(text){
		if(text){
			$('.ps_comment_container .ps_comment').text(text);
		}else{
			return false;
		}
	}
	//退款按钮点击
	$('.refund_btn').on('click',function(){
		//var order_num = $('.order_info .order_num').text();
		// 退款
        var refund_id = $(this).attr('data-id');
        //alert(123);return false;
        $.ajax({
            url: '/seller/refund?refund_id='+refund_id,
            waitting: true,
            success: function(html){
                $('body').append(html);
            }
        });
        return false;
	})
	//立即发货
	$('.send_now_btn').on('click',function(){
		var order_num = $('.order_info .order_num').text();
		var receiver_name = $('.order_detail_list .name').val();
		var detail = $('.order_detail_list .detail').val();
        var mobile = $('.order_detail_list .mobile').val();
        var remark = $('.order_detail_list .remark').val();
		window.location.href = '/seller/order/send?id=' + order_num + '&name=' + receiver_name + '&detail=' + detail + '&mobile=' + mobile + '&remark=' + remark;
		// console.log("立即发货，单号",order_num);
	})
	//申请退款
	$('.refund_now_btn').on('click',function(){
		var order_num = $('.order_info .order_num').text();
		window.location.href = '/seller/refund/detail?tid=' + order_num;
		// console.log("立即发货，单号",order_num);
	})
	//收获地址textarea随字数展开
	$('.order_detail_list textarea').each(function () {
	  this.setAttribute('style', 'height:' + (this.scrollHeight) + 'px;overflow-y:hidden;');
	}).on('input', function () {
	  this.style.height = 'auto';
	  this.style.height = (this.scrollHeight) + 'px';
	  console.log("行高",this.style.height);
	  if(this.value.length > 100){
		  this.value = this.value.substring(0,100);
	  };
	});
})
