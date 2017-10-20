require(['jquery','lrz','cookie','sortable'],function(){
	
	//删除图片
	// $('body').on('click','.item_photo_container ul li .delete_icon',function(event){
	// 	event.stopPropagation();
	// 	$(this).parent().remove();
	// 	return false;
	// });
	//添加图片
	$('.item_photo_container .add_item_photo').on('click',function(){
        $('#upfile').click();
	});
	//输入价格验证
	$('.item_value_cfg input').on('input',function(){
		var reg =/(^(([1-9]\d{0,9})|0)(\.\d{1,2})?$)|(^[0-9]\.[0-9]{0,2}$)|(^[0-9]?[1-9]?$)/;
		var value = $(this).val();
		if(reg.test(value)){
		}else{
			$(this).val('');
		}
	})
	$('.item_value_cfg input').on('blur',function(){
		$(this).val(parseFloat($(this).val()||0));
	});
	//商品标题不超过30个字
	$('.item_title_container input').on('input',function(){
		var _temp = $(this).val();
		if(getByteLen(_temp) > 60){
			while(Number(getByteLen(_temp)) > 60){
                _temp = _temp.substring(0,_temp.length-1);
			}
			$(this).val(_temp);
		}
	})

    function getByteLen(val) {
        var len = 0;
        for (var i = 0; i < val.length; i++) {
            var a = val.charAt(i);
            if (a.match(/[^\x00-\xff]/ig) != null) {
                len += 2;
            }
            else {
                len += 1;
            }
        }
        return len;
    }
    //删除页面缓存
    function delStorage(good_id){
        $.cookie('ydl_good_id'+good_id,null);
    }
	//保存
	$('.save_btn').click(function(){
		var img_array = new Array();
		$('.item_photo_container ul li:not(:last)').each(function(){
			img_array.push($(this).find('img').attr('src'));
		})
		var good_title = $('.item_title_container textarea').val();
		//价格
		var three_price = new Array();
		$('.item_value_cfg ul li input').each(function(){
			three_price.push($(this).val());
		})
		if(img_array.length == 0){
			toast.show("请添加商品图片");
			return false;
		}
		if(good_title.length == 0){
            toast.show("请添加商品标题");
			return false;
		}
		// if(!parseFloat(three_price[0])){
		// 	console.log("拿货价不能为空");
		// 	return false;
		// }
		// if(!parseFloat(three_price[0])){
        //     toast.show("商品零售价不能为空");
		// 	return false;
		// }
		var postfree = $('.post_free input[type="checkbox"]').is(':checked')?1:0;
		var updown = $('.up_down_gift input[type="checkbox"]').is(':checked')?1:0;
		var suyuan = $('.suyuan input[type="checkbox"]').is(':checked')?1:0;
		var id = $('.goods_id_save').val();
        var istao = $('.tao_is_save').val();
        var tag_id = $('.cat_is_save').val();
        $.ajax({
            url: "/seller/commodity/goods",
            type: "POST",
            data:{pic:img_array,id:id,istao:istao,tag_id:tag_id,good_title:good_title,price:three_price[0],updown:updown,suyuan:suyuan,postfree:postfree},
            success: function(d) {
                if(d.result == 'success'){
                    toast.show("保存成功");
                    delStorage($("input.goods_id_save").val());
                    window.location.href="/seller/commodity/";
                }
                if(d.result == 'successtao'){
                    toast.show("保存成功");
                    delStorage($("input.goods_id_save").val());
                    window.location.href="/seller/commodity/";
                }
                if(d.result == 'fail'){
                    var r = '';
                    if(d.reason == '10001'){
                        r = '1688商品未上架';
                        toast.show('1688商品未上架');
                    }
                    if(d.reason == '10002'){
                        r = '1688商品库存不足';
                        toast.show('1688商品库存不足');
                    }
                    if(d.reason == '10003'){
                        r = '1688商品已过期';
                        toast.show('1688商品已过期');
                    }
                    if(d.reason == '10004'){
                        r = '商品编辑失败';
                        toast.show('商品编辑失败');
                    }
                    window.location.href="/seller/commodity/";
                }
                if(d.result == 'error'){
                    toast.show("保存失败");
                    window.location.href="/seller/commodity/";
                }
            }
        });
	})

    $('#upJQuery').on('click', function() {
        var fd = new FormData();
        fd.append("upload", 1);
        oFile = $("#upfile").get(0).files[0];
        imgSize = oFile.size;
        $("#upfile").val('');
        if(imgSize < 250000){
            fd.append("upfile", oFile);
        } else {
            lrz(oFile,{width:500,height:500})
			.then(function(rst){
				//测试压缩后的图片有多大
				// console.log('图片经过压缩后，大小为：'+(rst.base64.length / 1024)+'KB');
                fd.append("upfile", rst.base64);
                $.ajax({
                    url: "https://seller.xingyebao.com/ueditor?action=uploadscrawl",
                    type: "POST",
                    processData: false,
                    contentType: false,
                    data: fd,
                    success: function(d) {
                        var dd = JSON.parse(d);
                        if (dd.state == 'SUCCESS'){
                            $('.add_item_photo').before('<li><img src="'+dd.url+'"><i class="delete_icon"></i></li>');
                        }
                    }
                });
			})
            return;
        }
        $.ajax({
            url: "https://seller.xingyebao.com/ueditor?action=uploadimage",
            type: "POST",
            processData: false,
            contentType: false,
            data: fd,
            success: function(d) {
                var dd = JSON.parse(d);
                if (dd.state == 'SUCCESS'){
                    $('.add_item_photo').before('<li><img src="'+dd.url+'"><i class="delete_icon"></i></li>');
				}
            }
        });
    });

	//商品分类

	//分类弹窗呼起
	$(".category").click(function(){
        $("#category .block_item").find("icon-check").toggleClass("icon-checked",false);
        var ids = $("input.cat_is_save").val().split(',');
        if(ids != ""){
               for(var i = 0;i<ids.length;i++){
                $("#category .block-item[data-index="+ids[i]+"]").find("icon-check").toggleClass("icon-checked",true);
            } 
        } 
		$("#category").show();
	})
	//分类弹窗关闭
	$("#category .js-cancel").click(function(){
		var $choosen = $('#category').find("div.icon-checked").parents("div.block-item");
		var id = "";
        $choosen.each(function(){
            id += $(this).attr("data-index")+",";
        })
        id = id.substr(0,id.length-1);
		var nick = "";
        $choosen.each(function(){
            nick += $(this).find("span.address-name").text()+",";
        })
        nick = nick.substr(0,nick.length-1);
		$("div.category span.nick").text(nick);
        $('.cat_is_save').val(id);
		$("div.category span.nick").attr("category_id",id);
		$("#category").hide();
	})
	//check-icon切换
	$("#category").on("click",".block-item",function(){
		$(this).find("div.icon-check").toggleClass("icon-checked");
        // 至少选择一个类目
        // if($("div.icon-checked").length == 0){
        //     $(this).find("div.icon-check").toggleClass("icon-checked",true);
        //     toast.show("请至少设置一个分类");
        // }
	})
	//check-icon编辑
	$("#category").on("click",".js-edit-category",function(){
		$("#edit_categroy").attr("from_where","edit");
		var sort_id = $(this).parents(".block-item").attr("data-index");
		var nick = $(this).parents(".block-item").find(".address-name").text();
		$("#edit_categroy").attr("from_id",sort_id);
		$("#edit_categroy").attr("from_nick",nick);
		$("#edit_categroy input.sort_id").val(sort_id);
		$("#edit_categroy input.nick").val(nick);
		$("#edit_categroy").show();
        return false;
	})
	//编辑页删除
	$("#edit_categroy").on("click",".edit_categroy_delete",function(){
		//要被删除的内容是
		var from_id = $("#edit_categroy").attr("from_id");
		var from_nick = $("#edit_categroy").attr("from_nick");
		var id = $("#edit_categroy input.sort_id").val();
		var nick = $("#edit_categroy input.nick").val();
        //删除
        $.ajax({
            url: '/seller/commodity/tag_del',
            type: 'post',
            dataType: 'json',
            data: {id:id},
            success: function(result){
                if(result == 'fail'){
                    toast.show('商品分类下存在商品，无法删除');
                    return false;
                }
                reBuildCategroy();
                $("#edit_categroy").hide();
            }
        });
		//删除成功后

	})
	//编辑页保存
	$("#edit_categroy").on("click",".edit_categroy_save",function(){
		var from_id = $("#edit_categroy").attr("from_id");
		var from_nick = $("#edit_categroy").attr("from_nick");
		var id = $("#edit_categroy input.sort_id").val();
		var nick = $("#edit_categroy input.nick").val();
        if(nick == ''){
            toast.show('名称不能为空');
            return false;
        }
		var from_where = $("#edit_categroy").attr("from_where");
		if(from_where == "edit"){
			//编辑
			$.ajax({
                url: '/seller/commodity/tag_save',
                type: 'post',
                dataType: 'json',
                data: {id:id, name:nick},
                success: function(result){
                    if(result == 'fail'){
                        toast.show('该名称已存在');
                        return false;
                    }
                    reBuildCategroy();
                    $("#edit_categroy").hide();
                }
            });
		}
		else{
			//新增
			$.ajax({
                url: '/seller/commodity/tag_save',
                type: 'post',
                dataType: 'json',
                data: {name:nick},
                success: function(result){
                    if(result == 'fail'){
                        toast.show('该名称已存在');
                        return false;
                    }
                    reBuildCategroy();
                    $("#edit_categroy").hide();
                }
            });
		}

	})
	//编辑页取消
	$("#edit_categroy").on("click",".edit_categroy_cancel",function(){
		$("#edit_categroy").hide();
		return false;
	})
	//新增分类
	$("#category .category_add").on("click",function(){
		$("#edit_categroy").attr("from_where","add").show();
		$("#edit_categroy input.sort_id").val("");
		$("#edit_categroy input.nick").val("");
	})
	//重构分类列表
	function reBuildCategroy(){
		var list = '';
        var tag_id = $('.cat_is_save').val();
        $.ajax({
            url: '/seller/commodity/tag',
            type: 'post',
            dataType: 'json',
            data: {tag_id:tag_id},
            success: function(data){
                list = data;
                console.log(list);
                 var html = "";
                 for(var i = 0;i< list.length;i++){
                     html += '<div data-index="'+ list[i].id +'" class="js-address-item block-item">';
                     html += '<div class="icon-check';
                     if(list[i]['is_select'] == 1){
                         html += ' icon-checked';
                     }
                     html += '"></div>';
                     html += '<p><span class="address-name" style="margin-right: 5px;">'+ list[i].name +'</span></p>';
                     html += '<div class="address-opt js-edit-address js-edit-category"><i class="icon-circle-info"></i></div>';
                     html += '</div>';
                 }
                 $("#category .js-address-container").html(html);
            },
            complete:function(){
                $("#category .block-list").scrollTop(100011);
            }
        });

	}
	//点击图片放大预览
	// $("body").on("click",".item_photo_container ul li",function(){
	// 	var img = $(this).find("img").attr('src');
    // 	if($(this).is(".add_item_photo")){
    		
    // 	}else{
    // 		$(".preview_box img").attr("src",img);
    // 		$(".preview_box img").addClass("zoomIn");
    // 		$(".preview_box").fadeIn(300);
    // 	}
	// })
    //点击图片隐藏
    $("body").on("click",".preview_box",function(){
    	$(this).fadeOut(300);
    })
    
})
