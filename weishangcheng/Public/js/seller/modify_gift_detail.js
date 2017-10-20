require(['jquery','lrz'],function($){
	//用来判断输入框内容是否发生变化
	var prve_value = "";
	//最大输入位数 中文2位，英文1位
	//删除图片
	$('body').on('click','.item_photo_container ul li .delete_icon',function(event){
		$(this).parent().remove();
		event.stopPropagation();
	});
	//添加图片
	$('.item_photo_container .add_item_photo').on('click',function(){
        $('#upfile').click();
	});
	//点击图片放大预览
 	$("body").on("click",".item_photo_container ul li",function(){
 		var img = $(this).find("img").attr('src');
    	if($(this).is(".add_item_photo")){
    		
    	}else{
    		$(".preview_box img").attr("src",img);
    		$(".preview_box img").addClass("zoomIn");
    		$(".preview_box").fadeIn(300);
    	}
 	})
    //点击图片隐藏
    $("body").on("click",".preview_box",function(){
 		$(this).fadeOut(300);
 	})
	//保存按钮
	$('.save_btn').click(function(){
		var img_array = new Array();
		var str = '';
		$('.item_photo_container ul li:not(:last)').each(function(){
			img_array.push($(this).find('img').attr('src'));
            str += '<img src= "' + $(this).find('img').attr('src') + '">';
		})
		var good_ditail = $('.gift_detail_intro textarea').val();
		if(img_array.length == 0){
            toast.show('请添加商品图片');
			return false;
		}
		if(good_ditail.length == 0){
            toast.show('请添加商品详情介绍');
			return false;
		}
        var id = $('.goods_id_save').val();
        $.ajax({
            url: "/seller/commodity/modify_save",
            type: "POST",
            data: {id:id,content:good_ditail+str},
            success: function(d) {
                console.log(d);
                if (d == 1){
                    toast.show('保存成功');
                    window.location.href = "/seller/commodity/goods?id="+id+"&from=modify_gift_detail";
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
                    fd.append("upfile", rst.base64);
                    $.ajax({
                        url: "https://seller.xingyebao.cn/ueditor?action=uploadscrawl",
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
            url: "https://seller.xingyebao.cn/ueditor?action=uploadimage",
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
})

function upload_deal()
{
	$("#upJQuery").click();
}
