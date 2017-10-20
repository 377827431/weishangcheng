var emotions = {
    url: 'https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/',
    list: [
        {"title": "微笑", "url": "0.gif", "x": "0", "y": "0"},
        {"title": "撇嘴", "url": "1.gif", "x": "-24", "y": "0"},
        {"title": "色", "url": "2.gif", "x": "-48", "y": "0"},
        {"title": "发呆", "url": "3.gif", "x": "-72", "y": "0"},
        {"title": "得意", "url": "4.gif", "x": "-96", "y": "0"},
        {"title": "流泪", "url": "5.gif", "x": "-120", "y": "0"},
        {"title": "害羞", "url": "6.gif", "x": "-144", "y": "0"},
        {"title": "闭嘴", "url": "7.gif", "x": "-168", "y": "0"},
        {"title": "睡", "url": "8.gif", "x": "-192", "y": "0"},
        {"title": "大哭", "url": "9.gif", "x": "-216", "y": "0"},
        {"title": "尴尬", "url": "10.gif", "x": "-240", "y": "0"},
        {"title": "发怒", "url": "11.gif", "x": "-264", "y": "0"},
        {"title": "调皮", "url": "12.gif", "x": "-288", "y": "0"},
        {"title": "呲牙", "url": "13.gif", "x": "-312", "y": "0"},
        {"title": "惊讶", "url": "14.gif", "x": "-336", "y": "0"},
        {"title": "难过", "url": "15.gif", "x": "-360", "y": "0"},
        {"title": "酷", "url": "16.gif", "x": "-384", "y": "0"},
        {"title": "冷汗", "url": "17.gif", "x": "-408", "y": "0"},
        {"title": "抓狂", "url": "18.gif", "x": "-432", "y": "0"},
        {"title": "吐", "url": "19.gif", "x": "-456", "y": "0"},
        {"title": "偷笑", "url": "20.gif", "x": "-480", "y": "0"},
        {"title": "可爱", "url": "21.gif", "x": "-504", "y": "0"},
        {"title": "白眼", "url": "22.gif", "x": "-528", "y": "0"},
        {"title": "傲慢", "url": "23.gif", "x": "-552", "y": "0"},
        {"title": "饥饿", "url": "24.gif", "x": "-576", "y": "0"},
        {"title": "困", "url": "25.gif", "x": "-600", "y": "0"},
        {"title": "惊恐", "url": "26.gif", "x": "-624", "y": "0"},
        {"title": "流汗", "url": "27.gif", "x": "-648", "y": "0"},
        {"title": "憨笑", "url": "28.gif", "x": "-672", "y": "0"},
        {"title": "大兵", "url": "29.gif", "x": "-696", "y": "0"},
        {"title": "奋斗", "url": "30.gif", "x": "-720", "y": "0"},
        {"title": "咒骂", "url": "31.gif", "x": "-744", "y": "0"},
        {"title": "疑问", "url": "32.gif", "x": "-768", "y": "0"},
        {"title": "嘘", "url": "33.gif", "x": "-792", "y": "0"},
        {"title": "晕", "url": "34.gif", "x": "-816", "y": "0"},
        {"title": "折磨", "url": "35.gif", "x": "-840", "y": "0"},
        {"title": "衰", "url": "36.gif", "x": "-864", "y": "0"},
        {"title": "骷髅", "url": "37.gif", "x": "-888", "y": "0"},
        {"title": "敲打", "url": "38.gif", "x": "-912", "y": "0"},
        {"title": "再见", "url": "39.gif", "x": "-936", "y": "0"},
        {"title": "擦汗", "url": "40.gif", "x": "-960", "y": "0"},
        {"title": "抠鼻", "url": "41.gif", "x": "-984", "y": "0"},
        {"title": "鼓掌", "url": "42.gif", "x": "-1008", "y": "0"},
        {"title": "糗大了", "url": "43.gif", "x": "-1032", "y": "0"},
        {"title": "坏笑", "url": "44.gif", "x": "-1056", "y": "0"},
        {"title": "左哼哼", "url": "45.gif", "x": "-1080", "y": "0"},
        {"title": "右哼哼", "url": "46.gif", "x": "-1104", "y": "0"},
        {"title": "哈欠", "url": "47.gif", "x": "-1128", "y": "0"},
        {"title": "鄙视", "url": "48.gif", "x": "-1152", "y": "0"},
        {"title": "委屈", "url": "49.gif", "x": "-1176", "y": "0"},
        {"title": "快哭了", "url": "50.gif", "x": "-1200", "y": "0"},
        {"title": "阴险", "url": "51.gif", "x": "-1224", "y": "0"},
        {"title": "亲亲", "url": "52.gif", "x": "-1248", "y": "0"},
        {"title": "吓", "url": "53.gif", "x": "-1272", "y": "0"},
        {"title": "可怜", "url": "54.gif", "x": "-1296", "y": "0"},
        {"title": "菜刀", "url": "55.gif", "x": "-1320", "y": "0"},
        {"title": "西瓜", "url": "56.gif", "x": "-1344", "y": "0"},
        {"title": "啤酒", "url": "57.gif", "x": "-1368", "y": "0"},
        {"title": "篮球", "url": "58.gif", "x": "-1392", "y": "0"},
        {"title":"乒乓","url":"59.gif","x":"-1416","y":"0"},
        {"title":"咖啡","url":"60.gif","x":"-1440","y":"0"},
        {"title":"饭","url":"61.gif","x":"-1464","y":"0"},
        {"title":"猪头","url":"62.gif","x":"-1488","y":"0"},
        {"title":"玫瑰","url":"63.gif","x":"-1512","y":"0"},
        {"title":"凋谢","url":"64.gif","x":"-1536","y":"0"},
        {"title":"示爱","url":"65.gif","x":"-1560","y":"0"},
        {"title":"爱心","url":"66.gif","x":"-1584","y":"0"},
        {"title":"心碎","url":"67.gif","x":"-1608","y":"0"},
        {"title":"蛋糕","url":"68.gif","x":"-1632","y":"0"},
        {"title":"闪电","url":"69.gif","x":"-1656","y":"0"},
        {"title":"炸弹","url":"70.gif","x":"-1680","y":"0"},
        {"title":"刀","url":"71.gif","x":"-1704","y":"0"},
        {"title":"足球","url":"72.gif","x":"-1728","y":"0"},
        {"title":"瓢虫","url":"73.gif","x":"-1752","y":"0"},
        {"title":"便便","url":"74.gif","x":"-1776","y":"0"},
        {"title":"月亮","url":"75.gif","x":"-1800","y":"0"},
        {"title":"太阳","url":"76.gif","x":"-1824","y":"0"},
        {"title":"礼物","url":"77.gif","x":"-1848","y":"0"},
        {"title":"拥抱","url":"78.gif","x":"-1872","y":"0"},
        {"title":"强","url":"79.gif","x":"-1896","y":"0"},
        {"title":"弱","url":"80.gif","x":"-1920","y":"0"},
        {"title":"握手","url":"81.gif","x":"-1944","y":"0"},
        {"title":"胜利","url":"82.gif","x":"-1968","y":"0"},
        {"title":"抱拳","url":"83.gif","x":"-1992","y":"0"},
        {"title":"勾引","url":"84.gif","x":"-2016","y":"0"},
        {"title":"拳头","url":"85.gif","x":"-2040","y":"0"},
        {"title":"差劲","url":"86.gif","x":"-2064","y":"0"},
        {"title":"爱你","url":"87.gif","x":"-2088","y":"0"},
        {"title":"NO","url":"88.gif","x":"-2112","y":"0"},
        {"title":"OK","url":"89.gif","x":"-2136","y":"0"},
        {"title":"爱情","url":"90.gif","x":"-2160","y":"0"},
        {"title":"飞吻","url":"91.gif","x":"-2184","y":"0"},
        {"title":"跳跳","url":"92.gif","x":"-2208","y":"0"},
        {"title":"发抖","url":"93.gif","x":"-2232","y":"0"},
        {"title":"怄火","url":"94.gif","x":"-2256","y":"0"},
        {"title":"转圈","url":"95.gif","x":"-2280","y":"0"},
        {"title":"磕头","url":"96.gif","x":"-2304","y":"0"},
        {"title":"回头","url":"97.gif","x":"-2328","y":"0"},
        {"title":"跳绳","url":"98.gif","x":"-2352","y":"0"},
        {"title":"挥手","url":"99.gif","x":"-2376","y":"0"},
        {"title":"激动","url":"100.gif","x":"-2400","y":"0"},
        {"title":"街舞","url":"101.gif","x":"-2424","y":"0"},
        {"title":"献吻","url":"102.gif","x":"-2448","y":"0"},
        {"title":"左太极","url":"103.gif","x":"-2472","y":"0"},
        {"title":"右太极","url":"104.gif","x":"-2496","y":"0"}
    ],
    getHtml: function(){
        var t = this, html = '';
        for(var i=0;i<t.list.length; i++){
            html += '<li class="emotions_item"><i class="js_emotion_i"data-gifurl="'+t.url+t.list[i].url+'"data-title="'+t.list[i].title+'"style="background-position:'+t.list[i].x+'px '+t.list[i].y+'px;"></i></li>';
        }
        
        return html;
    }
}

var AutoReply = {
	data:{
        appid: '',
    	rule: '',
    	is_subscribe: 0,
    	is_default: 0,
        is_rand: 1,
    	keyword: [],
    	content: []
	},
	$keywordList: null,
    $rule: null,
    $default: null,
    $subscribe: null,
    $replyContent: null,
    $saveBtn: null,
    init: function(data){
        var t = this;
        if(data){
            t.data = data;	
        }
        
        // 初始化变量
        t.$rule = $('.js-rule'),
        t.$default = $('.js-default'),
        t.$subscribe = $('.js-subscribe'),
        t.$replyContent = $('#reply_content_list'),
        t.$keywordList = $('.keyword-list'),
        t.$materialModal = $('#materialModal');
        t.$isRand = $('.js-rand');
        
        // 初始化数据
        t.initData(t.data);
        
        t.bindEvent();
    },
    bindEvent: function(){
    	var t = this;
        
        //添加关键词
        $('body').on('click', '.js-add-keyword',function(){
        	t.addKeyword();
        	return false;
        });

        //删除关键词项
        t.$keywordList.on('click','.keyword .close--circle',function(){
            $(this).parents('.keyword').remove();
            return false;
        })
        //全匹和模糊切换
        .on('click','.add-on',function(){
            var $self = $(this);
            var full_match = $self.data('type');
            $self.data('type', full_match==1 ? 0 : 1);
            $self.html(full_match==1 ? '模糊' : '全匹');
        });
        
        // 编辑回复内容
        t.$replyContent.on('click', '.js-edit', function(){
        	var $li = $(this).parents('li:first');
        	t.showMaterial($li.data('reply'), $li.index());
        	t.$materialModal.modal('show');
        	return false;
        })
        // 删除回复内容
        .on('click', '.js-delete', function(){
        	$(this).parents('li:first').remove();
        	return false;
        });

    	// 弹出素材库
        t.initMaterial();
        
        /*保存关键字回复设置*/
        t.$saveBtn = $('.save_key_word_set').on('click',function(){
        	var data = t.getData();
        	
        	// 数据校验
        	if(data.rule == ''){
        		if(data.is_subscribe == 1){
        			data.rule = '关注回复';
        		}else if(data.is_default == 1){
        			data.rule = '默认回复';
        		}else{
        			data.rule = '关键词回复';
        		}
        	}

        	if(data.keyword.length == 0)
        		return alertMsg('关键词不能为空'), false;
        	if(data.content.length == 0)
        		return alertMsg('回复内容不能为空'), false;
        	
        	t.saveData(data);
        	return false;
        });
        
        // 表情处理
        t.emotion();
    },
    initMaterial: function(){
    	var t = this, $modal = t.$materialModal,
    		$tabA = $modal.find('.nav-tabs a'),
    		editIndex = -1;
    	
    	$modal.modal({show: false});
    	// 双击选中图文
		$modal.find('.material-list').on('dblclick', '.js-item',function(){
			var $item = $(this),
			media_id = $item.attr('data-media_id');
			
			//$item.addClass('selected').siblings().removeClass('selected');
			//$item.parent().siblings().children().not($item).removeClass('selected');
			$modal.modal('hide');
			t.setContent(syncMaterial.data[media_id], editIndex);

			return false;
		});
		
		$modal.find('.js-text-ok').on('click', function(){
			var text = $('#reply_content').val();
			if(text && text.length > 0){
				t.setContent({type: 'text', content: text}, editIndex);
			}
			$modal.modal('hide');
			return false;
		});
		
    	$('.js-open-material').on('click', function(){
    		t.showMaterial( {type: 'text'}, -1);
    		$modal.modal('show');
			return false;
        });
    	
    	$tabA.on('click', function(){
        	var type = $(this).data('type');
        	if(type == 'text'){
        		return;
        	}
        	syncMaterial.sync(type, 1);
        });
    	
    	t.showMaterial = function(data, index){
    		editIndex = index;
    		
    		$tabA.each(function(i){
    			if($tabA.eq(i).data('type') == data.type){
    				$tabA.eq(i).trigger('click');
    				return false;
    			}
    		});
    		
    		if(data.type == 'text'){
    			var html = t.setText(data.content);
    			$('#reply_content').html(html);
    			t.getStr();
    		}
        }
    },
    showMaterial: null,
    hideEmotion: function(e){	// 关闭表情
        $('#emotion_wrp').hide();
        $(document).unbind('click', this.hideEmotion);
    },
    emotion: function(){
        var t = this, selection = document.getSelection(), range = null;

        t.$emotion = $('#emotion_wrp');
        t.$emotion.find('.emotions').append(emotions.getHtml());

        // 弹出表情
        $('.editor_toolbar .js_switch').on('click', function(){
            t.$emotion.show();
            $(document).unbind('click', t.hideEmotion).on('click', t.hideEmotion);
            return false;
        });
        
        // 预览表情
        t.$emotion.find('.js_emotion_i').hover(function(){
            var $emotion = $(this);
            var gifurl = $emotion.data('gifurl');
            var title = $emotion.data('title');
            t.$emotion.find('.emotions_preview').html('<img src="'+gifurl+'" alt="'+title+'">');
        }).on('click', function(){
            var ele = document.getElementById('reply_content'), end = ele.selectionEnd;
            ele.value = ele.value.substring(0, end) + "/" + this.getAttribute('data-title') + '' + ele.value.substring(end);
            t.getStr();
        });
        
        // 计算输入的文字
        $('#reply_content').on('change keyup', function(){
            return t.getStr(), false;
        });
    },
    //获取输入的内容
    getStr: function(){
        var element = document.getElementById('reply_content'), str = element.value;
        var num = 600 - str.length;
        document.getElementById('js_editorTip').innerHTML = num > 0 ? num  : 0;
        return str;
    },
    setContent: function(data, index){
        this.addReply([data], index);
    },
    setText: function(text){
        if(!text){
            text = '';
        }
        if(text.length > 0){
            var list = emotions.list;
            var reg;
            for(var i=0; i<list.length; i++){
                reg = new RegExp('/'+list[i].title,"gi");
                text = text.replace(reg, '<img src="'+emotions.url+list[i].url+'" alt="/'+list[i].title+'">')
            }

            text = text.replace(/\n/g, '<br>');
        }

        //$('#reply_content').html(text);
        return text;
    },
    addKeyword: function(data){
    	var t = this, count = t.$keywordList.children().length;
        if(count >= 8){
        	return alertMsg('每组规则最多支持8个关键词'), false;
        }
        
        var list = data instanceof Array ? data : [{keyword: '', full_match: 1}];
    	var html = '';
    	for(var i=0; i<list.length; i++){
    		html += '<div class="keyword input-append">';
	        html +='<a href="javascript:;" class="close--circle">×</a>';
	        html +='<input type="text" class="value" value="'+list[i].keyword+'" maxlength="8">';
	        html +='<span class="add-on" data-type="'+list[i].full_match+'">'+(list[i].full_match == 1 ? '全匹' : '模糊')+'</span>';
	        html +='</div>';
    	}
	    
    	t.$keywordList.append(html);
    },
    addReply: function(list, index){
    	if(!list || list.length == 0){
    		return;
    	}
    	
    	var t = this, html = '', contentHtml = '', typeStr = '';
    
    	for(var i=0; i<list.length; i++){
    		switch(list[i].type){
		        case 'text':
		        	typeStr = '文　　本';
		        	contentHtml = t.setText(list[i].content);
		            break;
		        case 'news':
		        	typeStr = '图　　文';
		        	contentHtml = syncMaterial.getNewsHtml(list[i]);
		            break;
		        case 'voice':
		        	typeStr = '语　　音';
		        	contentHtml = syncMaterial.parseVoice([list[i]]);
		            break;
		        case 'video':
		        	typeStr = '视　　频';
		        	contentHtml = syncMaterial.parseVideo([list[i]]);
		            break;
		        case 'senior':
                    typeStr = '高级图文';
                    contentHtml = syncMaterial.getSeniorHtml(list[i]);
                    break;
		    }
    		
    		html = '<li><div><a class="reply-type">'+typeStr+'：</a><span class="options"><a class="icon-pencil js-edit"></a><a class="icon-trash js-delete" style="margin-left:20px"></a></span></div>';
    	    html += '<div class="reply-content">'+contentHtml+'</div></li>';
    	    
    	    if(index > -1){
    	    	t.$replyContent.children(':eq('+index+')').prop('outerHTML', html);
    	    	t.$replyContent.children(':eq('+index+')').data('reply', list[i]);
    		}else{
    			$(html).data('reply', list[i]).appendTo(t.$replyContent);
    		}
    	}
    },
    initData: function(data){
    	var t = this;
    	t.$rule.val(data.rule);
    	t.$default.prop('checked', data.is_default);
    	t.$subscribe.prop('checked', data.is_subscribe);
        t.$isRand.prop('checked', data.is_rand);
    	t.addKeyword(data.keyword);
    	t.addReply(data.content);
    },
    getData: function(){
    	var t = this, data = t.data;
    	data.rule = t.$rule.val();
    	data.is_default = t.$default.prop('checked') ? 1 : 0;
        data.is_subscribe = t.$subscribe.prop('checked') ? 1 : 0;
        data.is_rand = t.$isRand.prop('checked') ? 1 : 0;
    	data.keyword = [];
    	data.content = [];
    	
    	// 遍历关键词
    	var $keywords = t.$keywordList.find('input'), text;
    	for(var i=0; i<$keywords.length; i++){
    		text = $keywords.eq(i).val();
    		if(text == ''){
    			continue;
    		}
    		
    		data.keyword.push({full_match: $keywords.eq(i).next().data('type'), keyword: text});
    	}
    	
    	// 遍历回复内容
    	var $replyList = t.$replyContent.children();
    	$replyList.each(function(i){
    		data.content.push($replyList.eq(i).data('reply'));
    	});

    	return data;
    },
    saveData: function(data){
    	var t = this;
		t.$saveBtn.attr('disabled', 'disabled');
    	$.ajax({
    		type: 'post',
    		dataType: 'json',
    		data: data,
    		success: function(){
    			window.location = document.referrer;
    		},
    		error: function(){
    			t.$saveBtn.removeAttr('disabled');
    		}
    	});
    }
}

// 同步素材
var syncMaterial = {
    data: {},
    sync: function(type, page){
    	var t = this,
			$content = $('#tab_'+type+'_content'),
			id = type+'-page-'+page
			$page = $('#' + id);
		
		$content.children().css('display', 'none');
		if($page.length == 0){
			$content.append('<div id="'+id+'"></div>');
			$page = $('#' + id);
			t.doSync(type, page, $content, $page);
		}else{
			$page.css('display', 'block');
		}
    },
    doSync: function(type, page, $content, $page){
        var t = this, url = SERVICE_URL+'/api/syncMaterial';
        
        if(type == 'senior'){ //高级图文
        	url = __ADMIN__+'/reply/getAdvanced?page='+page;
        }
        
        $page.html('<div style="text-align:center; margin-top:190px;">正在加载中...</div>');
        
        $.ajax({
            url: url,
            type:'post',
            data: {appid: AutoReply.data.appid, type: type, page: page, project_id: t.data.project_id},
            dataType: 'json',
            success: function(data){
                var html = '';
                switch(type){
                    case 'news':
                        html = t.parseNews(data);
                    break;
                    case 'image':
                        html = t.parseImage(data);
                    break;
                    case 'video':
                        html = t.parseVideo(data.rows);
                    break;
                    case 'voice':
                        html = t.parseVoice(data.rows);
                    break;
                    case 'senior':
                        html = t.parseSenior(data);
                    break;
                }
                
                $page.html(html);
                t.resetPage(type, page, data.total, data.size);
            },
            error: function(){
                $page.html('<div style="text-align:center; margin-top:190px;"><button type="button" class="btn" onclick="syncMaterial.retory(\''+type+'\','+page+')">重试</button></div>');
            }
        });
    },


    resetPage: function(type, page, total, size){
    	var t = this;
		$pagination = $('#'+type+'-pagination');
		$pagination.pagination({
			itemsCount: total,
		    pageSize: size,
		    displayPage: 10,
		    currentPage: page,
		    showCtrl: true,
		    onSelect: function (page) {
		    	t.sync(type, page);
		    }        
		});
		
		$refresh = $pagination.prev();
		$refresh.removeAttr('disabled');
		$refresh.unbind('click').on('click', function(){
			$refresh.attr('disabled', 'disabled');
			t.retory(type, page);
		});
    },
    retory: function(type, page){
        var t = this,
            $content = $('#'+type+'ModalContent'),
            id = type+'-page-'+page
            $page = $('#' + id),
            t.doSync(type, page, $content, $page);
    },
    parseNews: function(data){
        var t=this, html = html1 = '<div class="media_preview_area" style="margin-right: 20px;">',
        	html2 = '<div class="media_preview_area" style="margin-right: 20px;">',
        	html3 = '<div class="media_preview_area">';
        for(var i=0; i<data.rows.length; i++){
            t.data[data.rows[i].media_id] = data.rows[i];
            html = t.getNewsHtml(data.rows[i]);
            index = (i+length) % 3;
            if(index == 0){
                html1 += html;
            }else if(index == 1){
                html2 += html;
            }else{
                html3 += html;
            }
        }
        
        html1 += '</div>',
        html2 += '</div>',
        html3 += '</div>';
        
        return html1 + html2 + html3;
    },
    /*高级图文的方法*/
    parseSenior: function(data){
        var t=this, html = html1 = '<div class="media_preview_area" style="margin-right: 20px;">',
            html2 = '<div class="media_preview_area" style="margin-right: 20px;">',
            html3 = '<div class="media_preview_area">';
        for(var i=0; i<data.rows.length; i++){
            t.data[data.rows[i].id] = data.rows[i];
            html = t.getSeniorHtml(data.rows[i]);
            index = (i+length) % 3;
            if(index == 0){
                html1 += html;
            }else if(index == 1){
                html2 += html;
            }else{
                html3 += html;
            }
        }
        
        html1 += '</div>',
        html2 += '</div>',
        html3 += '</div>';
        
        return html1 + html2 + html3;
    },
    parseImage: function(data){
        
    },
    parseVideo: function(rows){
        var html = '';
        for(var i=0; i<rows.length; i++){
        	rows[i].type = 'video';
            syncMaterial.data[rows[i].media_id] = rows[i];
            html += 
                '<div class="audio_msg_card js-item" data-media_id="'+rows[i].media_id+'">'+
                '<div class="msg_card_inner">'+
                '	<div class="msg_card_bd">'+
                '		<div class="audio_msg_wrp card file_wrp cover">'+
                '			<div class="audio_msg">'+
                '				<div class="icon_audio_wrp">'+
                '					<span class="icon_audio_msg"></span>'+
                '				</div>'+
                '				<div class="audio_content">'+
                '				<div class="audio_title">'+rows[i].name+'</div>'+
                '					<div class="audio_length">00:00</div>'+
                '					<div class="audio_date">创建于：'+rows[i].update_time+'</div>'+
                '				</div>'+
                '			</div>'+
                '		</div>'+
                '	</div>'+
                '</div></div>';
        }
        return html;
    },
    parseVoice: function(rows){
        var html = '';
        for(var i=0; i<rows.length; i++){
        	rows[i].type = 'voice';
            syncMaterial.data[rows[i].media_id] = rows[i];
            html += 
                '<div class="audio_msg_card js-item" data-media_id="'+rows[i].media_id+'">'+
                '<div class="msg_card_inner">'+
                '	<div class="msg_card_bd">'+
                '		<div class="audio_msg_wrp card file_wrp cover">'+
                '			<div class="audio_msg">'+
                '				<div class="icon_audio_wrp">'+
                '					<span class="icon_audio_msg"></span>'+
                '				</div>'+
                '				<div class="audio_content">'+
                '				<div class="audio_title">'+rows[i].name+'</div>'+
                '					<div class="audio_length">00:00</div>'+
                '					<div class="audio_date">创建于：'+rows[i].update_time+'</div>'+
                '				</div>'+
                '			</div>'+
                '		</div>'+
                '	</div>'+
                '</div></div>';
        }
        return html;
    },
    getNewsHtml: function(data){
    	data.type = 'news';
        syncMaterial.data[data.media_id] = data;
        var img = '';
        var selected = '';
        var html = '<div class="js-item appmsg'+(!data.content.length > 1 ? ' multi' : '') + selected +'" data-media_id="'+data.media_id+'"><div class="appmsg_content">';
        if(data.content.length > 1){
            for(var i=0; i<data.content.length; i++){
                var news = data.content[i];
                html += '<div class="appmsg_item js_appmsg_item has_thumb">';
                html += '<img class="js_appmsg_thumb appmsg_thumb" src="'+news.thumb_url+'">';
                html += '<h4 class="appmsg_title">';
                html += '<a href="'+news.url+'" target="_blank">'+news.title+'</a>';
                html += '</h4>';
                html += '</div>';
            }
        }else{
            var news = data.content[0];
            html += '<h4 class="js-item appmsg_title js_title"><a href="'+news.url+'" target="_blank">'+news.title+'</a></h4>';
            html += '<div class="appmsg_info">';
            html += '	<em class="appmsg_date">'+data.update_time+'</em>';
            html += '</div>';
            html += '<div class="appmsg_thumb_wrp">';
            html += '	<img  src="'+news.thumb_url+'" alt="封面" class="appmsg_thumb">';
            html += '</div>';
            html += '<p class="appmsg_desc">'+news.digest+'</p>';
        }
        html += '</div></div>';
        return html;
    },
    getSeniorHtml: function(data){
        data.type = 'senior';
        syncMaterial.data[data.id] = data;
        var img = '';
        var selected = '';
        var html = '<div class="js-item appmsg'+(data.items && data.items.length > 0 ? ' multi' : '') + selected +'" data-media_id="'+data.id+'"><div class="appmsg_content">';
        if(data.items && data.items.length > 0){
        	var newsOne = data;
            img = newsOne.cover_url.replace('http://mmbiz.qpic.cn', 'https://mmbiz.qlogo.cn');
            html += '<div class="appmsg_item js_appmsg_item has_thumb">';
            html += '<img class="js_appmsg_thumb appmsg_thumb" src="'+img+'" data-src="'+newsOne.cover_url+'">';
            html += '<h4 class="appmsg_title">';
            html += '<a href="'+newsOne.link+'" target="_blank">'+newsOne.title+'</a>';
            html += '</h4>';
            html += '</div>';
        	
            for(var i=0; i<data.items.length; i++){
                var news = data.items[i];
                img = news.cover_url.replace('http://mmbiz.qpic.cn', 'https://mmbiz.qlogo.cn');
                html += '<div class="appmsg_item js_appmsg_item has_thumb">';
                html += '<img class="js_appmsg_thumb appmsg_thumb" src="'+img+'" data-src="'+news.cover_url+'">';
                html += '<h4 class="appmsg_title">';
                html += '<a href="'+news.link+'" target="_blank">'+news.title+'</a>';
                html += '</h4>';
                html += '</div>';
            }
        }else{
            var news = data;
            img = news.cover_url.replace('http://mmbiz.qpic.cn', 'https://mmbiz.qlogo.cn');
            html += '<h4 class="js-item appmsg_title js_title"><a href="'+news.link+'" target="_blank">'+news.title+'</a></h4>';
            html += '<div class="appmsg_info">';
            html += '	<em class="appmsg_date">'+data.created+'</em>';
            html += '</div>';
            html += '<div class="appmsg_thumb_wrp">';
            html += '	<img  src="'+img+'" data-src="'+news.cover_url+'" alt="封面" class="appmsg_thumb">';
            html += '</div>';
            html += '<p class="appmsg_desc">'+news.digest+'</p>';
        }
        html += '</div></div>';
        return html;
    }
}
