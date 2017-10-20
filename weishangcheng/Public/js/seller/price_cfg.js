require(['jquery'],function(){
	function isPriceValid(flag){
		var inputPrice = parseFloat($.trim($('input.sell_price').val()));
		var factory_price = parseFloat($("li.factory_price_li.on span.factory_price").text());
		var isValid = true;
		if(inputPrice == 0 || inputPrice==""){
			isValid = false;
		}else if(parseFloat(inputPrice)<parseFloat(factory_price)){
			isValid = false;
		}else{
			var reg =/(^(([1-9]\d{0,9})|0)(\.\d{1,2})?$)|(^[0-9]\.[0-9]{0,2}$)|(^[0-9]?[1-9]?$)/;
			isValid = reg.test(inputPrice);
		}
		if(!isValid){
			resetInput($('li.tag_container>div span.on').attr("group-id"));
		}
		if(flag == undefined){
			return isValid;
		}else{
			for(var i = 0;i< param.length;i++){
				if(parseFloat(param[i].pre_value) > parseFloat(inputPrice)){
					isValid = false;
					break;
				}
			}
		}
		return isValid;
	}
	function isStorageValid(){
		var storage = $.trim($('input.store_price_input').val());
		var isValid = false;
		if(storage == 0 || storage==""){
			isValid == false;
		}else{
			var reg = /^[1-9][0-9]*$/;
			isValid = reg.test(storage);
		}
		if(!isValid){
			resetInput($('li.tag_container>div span.on').attr("group-id"));
		}
		return isValid;
	}
	var group_id;
	//结果集
	var param=[];
	+function(){
		$('li.factory_price_li').each(function(){
			param.push({
				group_id:$(this).attr("group-id"),
				value:parseFloat($(this).children("span").text())+parseFloat($(this).next().next().text()),
				pre_value:$(this).children("span").text(),
				group_name:$(".tag_container>div span[group-id='"+ $(this).attr("group-id") +"']").text(),
				store_num:$(this).next().next().next().next().text(),
				profit:$(this).next().next().text()
			})
		})
	}();
	// console.log(param);
	function updateLocalParam(group_id){
		var i = 0;
		for(i = 0;i < param.length;i++){
			if(param[i].group_id == group_id){
				break;
			}
		}
		if($.trim($("input.sell_price").val()) == "" || $("input.sell_price") == 0){
		}else{
			param[i].value = $("input.sell_price").val();
		}
		param[i].profit = parseFloat(param[i].value - param[i].pre_value).toFixed(2);
	}

	function updateLocalStorage(group_id){
		var i = 0;
		for(i = 0;i < param.length;i++){
			if(param[i].group_id == group_id){
				break;
			}
		}
		if($.trim($("input.store_price_input").val()) == "" || $("input.store_price_input") == 0 ){
		}else{
			param[i].store_num = $("input.store_price_input").val();
		}
	}
	//重置input
	function resetInput(group_id){
		var i = 0;
		for(i = 0;i< param.length;i++){
			if(param[i].group_id == group_id){
				break;
			}
		}
		$("input.sell_price").val(parseFloat(param[i].value).toFixed(2));
		$("li.increse_price_li.on .increse_price").text(param[i].profit);
		// $('li.bat_set_flag').html('<label style="font-size:14px;color:#656565;text-indent:1em;">批量设置价格</label><input class="mui-switch mui-switch-animbg" type="checkbox">');
		$("input.store_price_input").val(param[i].store_num);
	}
	//修改售价改变事件
	$("input.sell_price").on('input',function(){
        // var reg =/(^(([1-9]\d{0,9})|0)(\.\d{1,2})?$)|(^[0-9]\.[0-9]{0,2}$)|(^[0-9]?[1-9]?$)/;
        var value = $(this).val();
        var factory_price = $("li.factory_price_li.on span.factory_price").html();
        if (factory_price == 0){
            setProfit(0);
		}else{
            setProfit(value - factory_price);
		}
	});
	$('.offshelf_dialog .close_icon_btn').click(function(){
        $('.offshelf_dialog').hide();
    })
	$(".sold_price input.sell_price,.sold_price input.store_price_input").focus(function(){
		var value = $.trim($(this).val());
		$(this).attr('placeholder',value);
		$(this).val('');
	})
	$(".sold_price input.sell_price,.sold_price input.store_price_input").blur(function(){
		var defalut_value = parseFloat($(this).attr('placeholder'));
		if($(this).val()==""){
			$(this).val(defalut_value);
		}
	})

	//利润赋值
	function setProfit(val){
		$("li.increse_price_li.on span.increse_price").text(val.toFixed(2));
	}
	$('body').on('click','.tag',function(){
		var is_bat_set = $('li.bat_set_flag input[type="checkbox"]').is(':checked');
		var is_bat_set_storage = $('li.store_set_flag input[type="checkbox"]').is(':checked');
		if(!isPriceValid()){
			//单个价格非法

			$('.offshelf_dialog').show();
			$('.offshelf_dialog .dialog_text_main').html('<p>您输入的价格已低于成本价,</p>请核对后重新输入;');
			return false;
		}else{
			//单个价格合法
			if(is_bat_set && !isPriceValid("bat_set")){
				//批量价格 非法
				$('.offshelf_dialog').show();
				$('.offshelf_dialog .dialog_text_main').html('<p>批量设置的某个分类下的价格已低于成本价,</p>请分开设置');
				return false;
			}
		}

		if(is_bat_set){
			//批量
			$('li.tag_container>div span').each(function(){
				updateLocalParam($(this).attr("group-id"));
			})
		}else{
			//单个
			updateLocalParam($('li.tag_container>div span.on').attr("group-id"));
		}
		if(is_bat_set_storage){
			//批量
			$('li.tag_container>div span').each(function(){
				updateLocalStorage($(this).attr("group-id"));
			})
		}else{
			//单个
			updateLocalStorage($('li.tag_container>div span.on').attr("group-id"));
		}
		group_id = $(this).attr("group-id");
		if($(this).hasClass("on")){
			return false;
		}else{
			$('.tag_container div span').toggleClass("on",false);
			$(this).toggleClass("on",true);
			$("li.factory_price_li,li.sell_price_li,li.increse_price_li").toggleClass("on",false);
			$('li.factory_price_li[group-id="'+ group_id +'"]').toggleClass("on",true);
			$('li.sell_price_li[group-id="'+ group_id +'"]').toggleClass("on",true);
			$('li.increse_price_li[group-id="'+ group_id +'"]').toggleClass("on",true);
		}
		resetInput(group_id);

	})
	//保存按钮
	$('.save_btn').click(function(){
		var is_bat_set = $('li.bat_set_flag input[type="checkbox"]').is(':checked');
		var is_bat_set_storage = $('li.store_set_flag input[type="checkbox"]').is(':checked');
		if(!isPriceValid()){
			if(is_bat_set){
				$('.offshelf_dialog').show();
				$('.offshelf_dialog .dialog_text_main').html('<p>批量设置的某个分类下的价格已低于成本价,</p>请分开设置');
				return false;
			}else{
				$('.offshelf_dialog').show();
				$('.offshelf_dialog .dialog_text_main').html('<p>您输入的价格已低于成本价,</p>请核对后重新输入;');
				return false;
			}
		}else{
			if(is_bat_set && !isPriceValid("group")){
				$('.offshelf_dialog').show();
				$('.offshelf_dialog .dialog_text_main').html('<p>批量设置的某个分类下的价格已低于成本价,</p>请分开设置');
				return false;
			}
		}
		if(is_bat_set){
			//批量
			$('li.tag_container>div span').each(function(){
				updateLocalParam($(this).attr("group-id"));
			})
		}else{
			//单个
			updateLocalParam($('li.tag_container>div span.on').attr("group-id"));
		}
		if(is_bat_set_storage){
			//批量
			$('li.tag_container>div span').each(function(){
				updateLocalStorage($(this).attr("group-id"));
			})
		}else{
			//单个
			updateLocalStorage($('li.tag_container>div span.on').attr("group-id"));
		}
		var id = $('.goods_id_save').val();
		$.ajax({
            url: "/seller/commodity/price_cfg",
            type: 'post',
            data:{id:id,param:param},
            success: function(data){
             	if(data.result == 'success'){
             		toast.show('保存成功');
                    window.location.href = "/seller/commodity/goods?id="+id+"&from=price_cfg";
             		// history.go(-1);
             	}
            },
            error: function(){
            	console.log('data');
                toast.show('保存失败');
            }
        });
	})

	//当前批量确认弹窗操作对象
	var whoes_id = "";
	//批量开关切换
	$("body").on("click","input.mui-switch",function(){
		var from_where = $(this).parents("li").eq(0).hasClass("store_set_flag")?"kucun":"price";
		var curr_stat = $(this).is(":checked");
		whoes_id = from_where;
		if(curr_stat == true){
			var text = "";
			if(from_where == "kucun"){
				text = "批量修改库存将会把所有分类的库存统一修改为当前设置库存，您确定要修改吗？"
			}else{
				text = "批量修改价格将会把所有分类的价格统一修改为当前设置价格，您确定要修改吗？"
			}
			$("div.switch_dailog_sure .switch_info").text(text);
			$("div.switch_dailog_sure").show();
		}
	})
	//确认
	$("body").on("click","div.switch_dailog_sure .btn_sure",function(){
		$("div.switch_dailog_sure").hide();
	})
	//取消
	$("body").on("click","div.switch_dailog_sure .btn_cancel",function(){
		var html = ""
		switch(whoes_id){
			case 'kucun':{
				html = '<label style="font-size:14px;color:#656565;text-indent:1em;">批量设置库存</label><input class="mui-switch mui-switch-animbg" type="checkbox">';
				$("li.store_set_flag").html(html);
				break;
			};
			case 'price':{
				html = '<label style="font-size:14px;color:#656565;text-indent:1em;">批量设置价格</label><input class="mui-switch mui-switch-animbg" type="checkbox">';
				$("li.bat_set_flag").html(html);
				break;
			}
		}
		$("div.switch_dailog_sure").hide();
	})
})
