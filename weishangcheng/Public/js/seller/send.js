require(['jquery'],function(){
	//textarea自适应高度
	$('textarea').each(function () {
	  this.setAttribute('style', 'height:' + (this.scrollHeight) + 'px;overflow-y:hidden;');
	}).on('input', function () {
	  this.style.height = 'auto';
	  this.style.height = (this.scrollHeight) + 'px';
	  console.log("行高",this.style.height);
	  if(this.value.length > 100){
		  this.value = this.value.substring(0,100);
	  };
	});
	//录入单号input输入不超过25位
	$('input.kuaidi_num').on('input',function(){
		var input_val = $(this).val();
		if(input_val.length >25){
			input_val = input_val.substring(0,25);
			$(this).val(input_val);
		}
	});
	//二维码扫描快递单
	$('i.scan_qr').click(function(){
		console.log("二维码扫描");
	})
	//数据验证
	$(".send_btn").click(function(){
		var sender_nick = $('.receive_info li:nth-child(1) input').val();
		if(sender_nick == ""){
			alert("收件人不能为空");
			return false;
		}
		if(sender_nick.length > 20){
			alert("收件人名称不能大于20位");
			$('.receive_info li:nth-child(1) input').val("");
			return false;
		}
		var tel = $('.receive_info li:nth-child(2) input').val();
		var tel_reg = /(^(86|\+86)?(\s|\-)?(13|14|17|15|18)[0-9]{9}$)|(^[0-9]{4}(\-|\s)?[0-9]{8}$)/;
		if(!tel_reg.test(tel)){
			alert("联系电话输入不合法")
			$('.receive_info li:nth-child(2) input').val("");
			return false;
		}
		var send_addr = $.trim($('.receive_info li:nth-child(3) textarea').val());
		if(send_addr == ""){
			alert("请输入详细的收货地址");
			return false;
		}
		var custom_ps = $.trim($('.receive_info li:nth-child(4) textarea').val());
		var kuaidi = $.trim($('.kuaidi_info li:nth-child(1) input.kuaidi').val());
		var kuaidi_num = $.trim($('.kuaidi_info li:nth-child(2) input.kuaidi_num').val());
		var kuaidi_reg = /^[0-9]{1,14}$/;
		var send_tid = $('.send_tid').val();
		if(kuaidi_num == ""){
			alert("请录入快递单号");
			return false;
		}
		if(!kuaidi_reg.test(kuaidi_num)){
			alert("快递单号非法");
			return false;
		}
		//参数汇总
		var param = {
			id:send_tid,
			sender_nick:sender_nick,
			tel:tel,
			send_addr:send_addr,
			custom_ps:custom_ps,
			kuaidi:kuaidi,
			kuaidi_num:kuaidi_num
		}
		console.log(param);
        $.ajax({
            url: '/seller/order/send',
            dataType: 'json',
            type:'post',
            data:{data:param},
            success: function(data){
				window.location.href = "/seller/order/detail?id=" + send_tid;
            }
        });
	})
})
