
var Senior = {
    //data:[{"title":"标题，必填，64字符","digest":"摘要，120个字符","link":"链接地址","cover_url":"封面图"}],
    data:[{
        "title": "",
        "digest": "",
        "link": "",
        "cover_url": ""
    }],
    init: function(data){
        var t = this;
        if(data){
            t.data = data;
        }
        t.$appmsg_content = $('.senior_graphics_area .appmsg_content');
        
        // 初始化数据
        t.initData(data);
        t.bindEvent();
    },
    initData: function(data){
        var t = this;
        if(!data){
            data = t.data;
        }
        if(data.length==1){
            t.oneNews(data);
        }else{
            for(var i=0;i<data.length;i++){
                var html ="";
                html+='<div class="appmsg_item js_appmsg_item has_thumb">';
                html+='<img class="js_appmsg_thumb appmsg_thumb" src="'+data[i].cover_url+'" onerror=this.src="__CDN__/img/weixin/no_image.png" alt=""/>';
                html+='<h4 class="appmsg_title">';
                html+='<a href="'+data[i].link+'" target="_blank">'+data[i].title+'</a></h4>';
                html+='<div class="actions-wrap"><span class="action edit">编辑</span><span class="action delete">删除</span></div></div>';
                t.$appmsg_content.append(html);
                $('.appmsg_content .appmsg_item').eq(i).data('content',data[i]);
            }
        }
    },
    bindEvent: function(){
        var t = this;
        //添加图文
        $('body').on('click', '.add_graphics_btn',function(){
            t.AddGraphics();
            return false;
        });
        //选中图文
        $('.senior_graphics_area').on('click','.appmsg_content .appmsg_item',function(){
            $this = $(this);
            $this.addClass('active').siblings('.appmsg_item').removeClass('active');
            t.setText($this);
        });
        $('.senior_graphics_area').on('click','.appmsg_content .senior_appmsg',function(){
            $('.appmsg_content .appmsg_item').removeClass('active');
            $this = $(this);
            t.setText($this);
        });
        //默认第一个图文内容显示
        t.$appmsg_content.find('div:first').trigger('click');
        
        //修改标题
                $('.graphics_title').on('change', function(){
            t.setName(this.value);
        });

        //修改链接
                $('.graphics_link').on('change', function(){
            t.setUrl(this.value);
        });

        //修改摘要
                $('.graphics_textarea').on('change', function(){
            t.setDigest(this.value);
        });
        $('body').on('click','.appmsg_content .appmsg_item .actions-wrap .delete',function(){
            var self= $(this);
            var index = self.parents('.appmsg_item').index();
            $('.appmsg_content .appmsg_item').eq(index-1).trigger('click');
            self.parents('.appmsg_item').remove();
            if($('.appmsg_content .appmsg_item').length==1){
                t.oneNews([$('.appmsg_content .appmsg_item').eq(0).data('content')]);
                $('.appmsg_content .appmsg_item').eq(0).remove();
            }
            
        })
        
        $('#senior_graphics_btn').on('click',function(){

            var data = t.validate(t.getData());
            if(!data){
                return false;
            }
            
            $.ajax({
                type: 'post',
                dataType: 'json',
                data: {data:data},
                success: function(result){
                    
                }
            });
            return false;

        })
    },
    AddGraphics: function(data){
        var t = this;
        if(!data){
            data = {"title": "", "digest":"","link": "", "cover_url": "" };
        }
        if($('.senior_graphics_preview .appmsg_content .appmsg_item').length>=8){
            alertMsg('图文消息不能超过8个');
            return false;
        }
        if($('.appmsg_content .senior_appmsg').length>0){
            t.addSecond();
        }
        var html = '<div class="appmsg_item js_appmsg_item has_thumb">';
            html+='<img class="js_appmsg_thumb appmsg_thumb" src="'+data.cover_url+'" onerror=this.src="__CDN__/img/weixin/no_image.png" alt=""/>';
            html+='<h4 class="appmsg_title">';
            html+='<a href="'+data.link+'" target="_blank">'+data.title+'</a></h4>';
            html+='<div class="actions-wrap"><span class="action edit">编辑</span><span class="action delete">删除</span></div></div>';
        t.$appmsg_content.append(html);
        var $focus = $('.appmsg_content .appmsg_item:last');
        $focus.data('content',data);
        $focus.trigger('click');
    },
    setText: function($this){
        $('.graphics_title').val($this.data('content').title);
        $('.graphics_textarea').val($this.data('content').digest);
        $('.graphics_link').val($this.data('content').link);
        $('.graphics_img').val($this.data('content').cover_url);
        $('.graphics_img').next('img').attr('src',$this.data('content').cover_url);
    },
    setName: function(name){
        if($('.appmsg_content .senior_appmsg').length>0){
            $('.appmsg_content .senior_appmsg').find('.appmsg_title a').text(name);
            $('.appmsg_content .senior_appmsg').data('content').title = name;
            return false;
        }
        $('.appmsg_content .appmsg_item.active').find('.appmsg_title a').text(name);
        $('.appmsg_content .appmsg_item.active').data('content').title = name;
    },
    setUrl: function(url){
        if($('.appmsg_content .senior_appmsg').length>0){
            $('.appmsg_content .senior_appmsg').find('.appmsg_title a').attr('href',url);
            $('.appmsg_content .senior_appmsg').data('content').link = url;
            return false;
        }
        $('.appmsg_content .appmsg_item.active').find('.appmsg_title a').attr('href',url);
        $('.appmsg_content .appmsg_item.active').data('content').link = url;
    },
    setDigest: function(content){
        if($('.appmsg_content .senior_appmsg').length>0){
            $('.appmsg_content .senior_appmsg').data('content').digest = content;
            return false;
        }
        $('.appmsg_content .appmsg_item.active').data('content').digest = content;
    },
    setImg: function(value){
        if($('.appmsg_content .senior_appmsg').length>0){
            $('.appmsg_content .senior_appmsg').find('img.appmsg_thumb').attr('src',value);
            $('.appmsg_content .senior_appmsg').data('content').cover_url = value;
            return false;
        }
        $('.appmsg_content .appmsg_item.active').find('img.appmsg_thumb').attr('src',value);
        $('.appmsg_content .appmsg_item.active').data('content').cover_url = value;
    },
    CheckUrl: function(str) {
        var RegUrl = new RegExp();
        RegUrl.compile("^[A-Za-z]+://[A-Za-z0-9-_]+\\.[A-Za-z0-9-_%&\?\/.=]+$");
        if (!RegUrl.test(str)) {
            return false;
        }
        return true;
    },
        validate: function($data){
        var t = this;
        var data = $data;
        var error = '';
        for(var i=0;i<data.length;i++){
            if(data[i].title==""){
                error += '第'+(i+1)+'个图文的标题不能为空';
                t.setFocus(i);
                alert(error);
                return false
            }
            if(data[i].link==""){
                error += '第'+(i+1)+'个图文的链接地址不能为空';
                t.setFocus(i);
                alert(error);
                return false
            }
            var url=$.trim(data[i].link);
            url=url.substr(0,7).toLowerCase()=="http://"?url:"http://"+url;
            if(!t.CheckUrl(url)){
                t.setFocus(i);
                alert('第'+(i+1)+'个图文请输入有效的链接地址');
                return false;
            }
            if(data[i].cover_url==""){
                error += '第'+(i+1)+'个图文的封面图不能为空';
                t.setFocus(i);
                alert(error);
                return false
            }
        }
        return data;
    },
    setFocus: function($index){
        if($('.appmsg_content .senior_appmsg').length==1){
            $('.appmsg_content .senior_appmsg').eq(0).trigger('click');
        }else{
            $('.appmsg_content .appmsg_item').eq($index).trigger('click');           
        }
    },
    getData: function(){
        var data =[];
        if($('.appmsg_content .senior_appmsg').length==1){
            data.push($('.appmsg_content .senior_appmsg').eq(0).data('content'));
        }else{
            for(var i=0;i<$('.appmsg_content .appmsg_item').length;i++){
                data.push($('.appmsg_content .appmsg_item').eq(i).data('content'));
            }            
        }
        return data;
    },
    oneNews: function(data){
        var t = this;
        var only_data = data[0];
        var html = '<div class="js-item appmsg senior_appmsg"><div class="appmsg_content">';
            html += '<h4 class="js-item appmsg_title js_title">';
            html += '<a href="'+only_data.link+'" target="_blank">'+only_data.title+'</a>';
            html += '</h4>';
            //html +='<div class="appmsg_info">	<em class="appmsg_date"></em></div>';
            html += '<div class="appmsg_thumb_wrp">';
            html += '<img class="appmsg_thumb" src="'+only_data.cover_url+'" data-src="'+only_data.cover_url+'">';
            html += '</div>';
            html +='<p class="appmsg_desc">'+only_data.digest+'</p>';
            html += '</div>';
            html += '</div>';
        t.$appmsg_content.append(html);
        var $focus = $('.appmsg_content .senior_appmsg');
        $focus.data('content',only_data);
    },
    addSecond: function(){
        var t = this;
        var data = $('.appmsg_content .senior_appmsg').data('content');
        var html = '<div class="appmsg_item js_appmsg_item has_thumb">';
            html+='<img class="js_appmsg_thumb appmsg_thumb" src="'+data.cover_url+'" onerror=this.src="__CDN__/img/weixin/no_image.png" alt=""/>';
            html+='<h4 class="appmsg_title">';
            html+='<a href="'+data.link+'" target="_blank">'+data.title+'</a></h4>';
            html+='<div class="actions-wrap"><span class="action edit">编辑</span><span class="action delete">删除</span></div></div>';
        t.$appmsg_content.append(html);
        $('.appmsg_content .senior_appmsg').remove();
        var $focus = $('.appmsg_content .appmsg_item:first');
        $focus.data('content',data);
    }
}


