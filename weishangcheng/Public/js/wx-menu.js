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

var Menu = {
	focus: {},
	$focus: null,
	id: '',
	appid: '',
	init: function(menu_button, menu_id){
		var t = this;
		t.id = menu_id;
		
		$('#add_menu').on('click', function(){
			t.add(this);
			return false;
		});
		
		// 添加子菜单
		$('#menuList').on('click', '.js_addMenuBox', function(){
			t.addSub(this);
			return false;
		}).on('click', '.jslevel2', function(){
			t.setFocus($(this));
			return false;
		}).on('click', '.jsMenu', function(){
			t.setFocus($(this));
			return false;
		});
		
		// 删除菜单
		$('#jsDelBt').on('click', function(){
			t.del();
		});
		
		// 菜单名称改变
		t.$name = $('.js_menu_name').on('change', function(){
			t.setName(this.value);
			return false;
		});
		
		// type=url值改变
		t.$url = $('.js_menu_url').on('change', function(){
			t.setUrl(this.value);
			return false;
		});
		// type=event值改变
		t.$event = $('.js_menu_event').on('change', function(){
			t.setEvent(this.value);
			return false;
		});
		
		// 事件类型
		t.$type = $('.js-event-type input').on('change', function(){
			t.focus.content = '';
			t.setType(this.value);
			return false;
		});
		
		// 切换选项卡
		$('.js_tab_navs .tab_nav').on('click', function(){
			var $tab_nav = $(this),
				$tab = $($tab_nav.attr('data-tab')),
				$siblings = $tab_nav.siblings();
			
			$tab_nav.addClass('selected');
			$siblings.removeClass('selected').trigger('revert');
			$tab.parent('.tab_content').css('display', 'block').siblings('.tab_content').css('display', 'none');
			
			if($tab_nav.attr('data-tab') == '.js_textArea'){
				$('#reply_content').focus();
			}
		}).on('revert', function(){
			var $tab_nav = $(this),
			$tab = $($tab_nav.attr('data-tab'));
		
			if($tab.hasClass('js_textArea')){
				$tab.find('textarea').val('');
			}else{
				var $children = $tab.children();
				$children.eq(0).show();
				$children.eq(1).children('.js-item').remove();
			}
			return false;
		});
		
		// 表情处理
		t.emotion();
		
		// 保存数据
		var $btnSave = $('#jsSaveBt');
		$btnSave.on('click', function(){
			var $this = $(this), appid = $this.data('appid');
			var data = t.getData();
			if(!data){
				return false;
			}
			$.ajax({
				url: '/weixin/menu?appid='+appid,
				type: 'post',
				dataType: 'json',
				data: {id: t.id, button: data, is_matchrule: 0},
				success: function(result){
					t.id = result.id;
				}
			});
			return false;
		});
		t.appid = $btnSave.data('appid');
		
		// 弹出素材库
		$('.js-open-media').on('click', function(){
			var $ele = $(this),
				type = $ele.attr('data-type');
			
			var $modal = $('#'+type+'Modal');
			
			if($modal.data('modal')){
				$modal.modal('show');
				return false;
			}

			$modal.modal();
			
			syncMaterial.sync(type, 1);
			
			// 双击选中图文
			$body = $modal.children('.modal-body');
			$body.on('dblclick', '.js-item',function(){
				var $item = $(this),
				media_id = $item.attr('data-media_id');
				
				$item.addClass('selected').siblings().removeClass('selected');
				$item.parent().siblings().children().not($item).removeClass('selected');
				$modal.modal('hide');
				
				Menu.setContent(type, syncMaterial.data[media_id]);
				return false;
			});
			return false;
		});
		
		// 拍照/相册
		$('.menu_content_container>.js-pic').on('change', 'input',function(){
			t.focus.type = this.value;
			return false;
		});
		
		if(menu_button){
			t.create(menu_button);
		}
	},
	create: function(button){
		var t = this;
		var ele = $('#add_menu');
		for(var i=0; i<button.length; i++){
			var $focus = t.add(ele, button[i], false);
			
			if(button[i].sub_button){
				var sub_btn = button[i].sub_button;
				var $addSub = $focus.find('.js_addMenuBox');
				for(var j=0; j<sub_btn.length; j++){
					t.addSub($addSub, sub_btn[j], false);
				}
			}
		}
		t.resetMenuWidth();
	},
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
    		t.focus.type = 'text';
    		t.focus.content = t.getStr();
            return false;
        });
	},
	//获取输入的内容
	getStr: function(){
		var element = document.getElementById('reply_content'), str = element.value;
        var num = 600 - str.length;
        document.getElementById('js_editorTip').innerHTML = num > 0 ? num  : 0;
        return str;
	},
	setName: function(name){
		this.$name.val(name);
		this.focus.name = name;
		this.$nameLabel.html(name);
	},
	setType: function(_type){
		var t = this, viewName = '', focus = this.focus;
		switch(_type){
			case 'view':
				viewName = 'view';
				break;
			case 'news':
			case 'text':
			case 'image':
			case 'voice':
			case 'video':
				viewName = 'news';
				break;
			case 'pic':
				_type = 'pic_photo_or_album';
			case 'pic_photo_or_album':
			case 'pic_sysphoto':
			case 'pic_weixin':
				viewName = 'pic';
				break;
			case 'scan':
				viewName = 'scan';
				break;
			case 'event':
				viewName = 'event';
				break;
		}
		
		this.$type.each(function(i, item){
			if(item.value == viewName){
				item.checked = true;
			}
		});
		
		var $view = $('.menu_content_container>.js-'+viewName);
		$view.show().siblings().hide();

		this.focus.type = _type;
		switch(_type){
			case 'view':
				t.setUrl(focus.content);
				break;
			case 'text':
			case 'news':
			case 'image':
			case 'voice':
			case 'video':
				t.setContent(_type, t.focus.content);
				$view.find('.js_tab_navs>.js-'+_type).trigger('click');
				break;
			case 'pic_photo_or_album':
			case 'pic_sysphoto':
			case 'pic_weixin':
				$view.find('input[value="'+_type+'"]').prop('checked', true);
				break;
			case 'event':
				t.setEvent(focus.content);
				break;
		}
	},
	setContent: function(type, content){
		var t = this;
		if(type == 'text'){
			return t.setText(t.focus.content||'');
		}
		
		var $selected = $('#'+type+'_selected'),
			$prev = $selected.prev();

		this.focus.type = type;
		if(!content){
			$prev.show();
			$selected.hide();
			return;
		}
		
		var html = '';
		switch(type){
			case 'news':
				html += syncMaterial.getNewsHtml(content);
				break;
			case 'voice':
				html += syncMaterial.parseVideo([content]);
				break;
			case 'video':
				html += syncMaterial.parseVoice([content]);
				break;
		}

		var $children = $selected.children();
		if($children.length == 2){
			$selected.prepend(html);
		}else{
			$children.eq(0).prop('outerHTML', html);
		}
		
		if(type != 'news'){
			$selected.children(':first').css({display: 'block', width: '100%'});
		}
		$selected.show();
		$prev.hide();
		
		this.focus.content = content;
	},
	setUrl: function(url){
		this.$url.val(url); 
		this.focus.content = url;
	},
	setEvent: function(event_val){
		this.$event.val(event_val); 
		this.focus.content = event_val;
	},
	setText: function(text){
		this.focus.content = text;
		$('#reply_content').val(text);
	},
	add: function(ele, data, autoFocus){
		var t = this;
		if(!data){
			data = {type: 'view', name: '菜单名称', content: '' };
		}
		
		var html = '';
		html += '<li class="jsMenu pre_menu_item grid_item jslevel1 ui-sortable ui-sortable-disabled size1of3">';
		html += '    <a href="javascript:void(0);" class="pre_menu_link" draggable="false">';
		html += '        <i class="icon_menu_dot js_icon_menu_dot dn" style="display: none;"></i>';
		html += '        <i class="icon20_common sort_gray"></i>';
		html += '        <span class="js_title">'+data.name+'</span>';
		html += '    </a>';
		html += '    <div class="sub_pre_menu_box js_titleBox"style="display: none;">';
		html += '        <ul class="sub_pre_menu_list">';
		html += '            <li class="js_addMenuBox"><a href="javascript:void(0);" class="jsSubView js_addL2Btn" title="最多添加5个子菜单" draggable="false"><span class="sub_pre_menu_inner js_sub_pre_menu_inner"><i class="icon14_menu_add"></i></span></a></li>';
		html += '        </ul>';
		html += '        <i class="arrow arrow_out"></i>';
		html += '        <i class="arrow arrow_in"></i>';
		html += '    </div>';
		html += '</li>';

		var $ele = $(ele);
		$ele.before(html);
		var $focus = $ele.prev();
		$focus.data('menu', data);
		
		if(autoFocus !== false){
			//$focus.siblings().removeClass('current').children('.sub_pre_menu_box').hide();
			//$('#menuList .sub_pre_menu_box li').removeClass('current');
			t.setFocus($focus);
		}
		
		return $focus;
	},
	addSub: function(ele, data, autoFocus){	// 添加子菜单
		var $ele = $(ele);
		var len = $ele.siblings().length;
		if(len == 5){
			return false;
		}
		
		var t = this;
		if(!data){
			data = {type: 'view', name: '子菜单' + (len+1), content: '' };
		}
		
		var html = '';
		html += '<li class="jslevel2 current">';
		html += '	<a href="javascript:void(0);" class="jsSubView">';
		html += '		<span class="sub_pre_menu_inner js_sub_pre_menu_inner">';
		html += '			<i class="icon20_common sort_gray"></i>';
		html += '			<span class="js_title">'+data.name+'</span>';
		html += '		</span>';
		html += '	</a>';
		html += '</li>';
		
		$ele.before(html);
		var $focus = $ele.prev();
		$focus.data('menu', data);
		
		if(autoFocus !== false){
			t.setFocus($focus);
			//$ele.siblings('.jslevel2').removeClass('current');
			//$ele.parents('.jsMenu:first').removeClass('current');
		}
		
		return $focus;
	},
	del: function(){
		var t = this;
		if(!t.focus){
			return false;
		}
		alertConfirm({
			title: '删除确认',
			content: '删除后“'+t.focus.name+'”菜单下设置的内容将被删除',
			ok: function(){
				var $siblings = null;
				if(t.$focus.hasClass('jsMenu')){ // 一级菜单
					$siblings = t.$focus.siblings('.jsMenu');
				}else{
					$siblings = t.$focus.siblings('.jslevel2');
				}

				t.$focus.remove();
				
				t.setFocus($siblings && $siblings.length > 0 ? $siblings.eq(0) : null);
			}
		});
	},
	resetMenuWidth: function (){
		var $menuList = $('#menuList');
		var $children = $('#menuList>li');
		var width = '100%';
		if($children.length == 1){
			width = '100%';
		}else if($children.length == 2){
			width = '50%';
		}else{
			width = '33.33%';
		}
		$children.css('width', width);
		$('#menuList #add_menu').show();
		if($children.length >= 4){
			$children.eq(3).hide();
		}
	},
	setFocus: function($focus){
		var t = this;
		t.resetMenuWidth();
		$('#js_rightBox').css('display', $focus ? 'block' : 'none');
		if(!$focus){
			return;
		}
		
		t.$focus = $focus;
		t.$nameLabel = $focus.children(':first').find('.js_title');
		
		t.focus = $focus.data('menu');
		
		// 选中的是一级菜单
		var subLen = 0;
		var $viewContent = $('#view').children(':gt(0)');
		if($focus.hasClass('jsMenu')){
			$focus.addClass('current').siblings().removeClass('current').children('.sub_pre_menu_box').hide()
			$focus.children('.sub_pre_menu_box').show().find('.jslevel2').removeClass('current');
			subLen = $focus.find('.jslevel2').length;
		}else{
			$focus.addClass('current').siblings().removeClass('current');
			$focus.parents('.sub_pre_menu_box:first').show().parents('.jsMenu:first').removeClass('current').siblings().removeClass('current').children('.sub_pre_menu_box').hide();
		}
		
		if(subLen > 0){
			$viewContent.hide();
		}else{
			$viewContent.show();
		}
		
		t.setName(t.focus.name);
		t.$name.focus();
		t.setType(t.focus.type);
	},
	validate: function($menu){
		var t = this;
		var menu = $menu.data('menu');
		var error = '';
		if(!menu.name){
			error = '菜单名称不能为空';
		}else if(menu.type == 'pic_photo_or_album' || menu.type == 'pic_sysphoto' || menu.type == 'pic_weixin' || menu.type == 'scan'){
			delete menu.content;
		}else if(!menu.content){
			error = '菜单“'+menu.name+'”的';
			switch(menu.type){
				case 'view':
					error += '链接地址';
					break;
				case 'text':
					error += '文字消息';
					break;
				case 'news':
					error += '图文消息';
					break;
				case 'voice':
					error += '语音消息';
					break;
				case 'video':
					error += '视频消息';
					break;
				case 'event':
					error += '自定义事件';
					break;
			}
			error += '不能为空';
		}
		
		if(error != ''){
			t.setFocus($menu);
			alert(error);
			return false;
		}
		
		return menu;
	},
	getData: function(){
		var t = this;
		var data = [];
		var $firstList = $('#menuList').children('.jsMenu');
		
		for(var i=0; i<$firstList.length; i++){
			var menu = {};
			var $secondList = $firstList.eq(i).find('.jslevel2');
			
			if($secondList.length > 0){
				menu = $firstList.eq(i).data('menu');
				delete menu.type;
				delete menu.content;
				if(!menu.name){
					t.setFocus($firstList.eq(i));
					alert('菜单名称不能为空');
					return false;
				}
				
				var second = [];
				for(var j=0; j<$secondList.length; j++){
					var menu2 = t.validate($secondList.eq(j));
					if(!menu2){
						return false;
					}
					second.push(menu2);
				}
				
				menu.sub_button = second;
			}else{
				menu = t.validate($firstList.eq(i));
				if(!menu){
					return false;
				}
				delete menu.sub_button;
			}
			
			data.push(menu);
		}
		
		return data;
	}
}

// 同步素材
var syncMaterial = {
	data: {},
	sync: function(type, page){
		var t = this,
			$content = $('#'+type+'Modal>.modal-body'),
			id = type+'-page-'+page
			$page = $('#' + id);
		
		$content.children().hide();
		if($page.length == 0){
			$content.append('<div id="'+id+'"></div>');
			$page = $('#' + id);
			t.doSync(type, page, $content, $page);
		}else{
			$page.show();
		}
	},
	doSync: function(type, page, $content, $page){
		var t = this;
		$page.html('<div style="text-align:center; margin-top:190px;">正在加载中...</div>');
		
		$.ajax({
			url: SERVICE_URL+'/api/syncMaterial?appid='+Menu.appid+'&type='+type+'&page='+page,
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
				}
				
				$page.html(html);
				t.resetPage(type, page, data.total, data.size);
			},
			error: function(){
				$page.html('<div style="text-align:center; margin-top:190px;"><button type="button" class="btn" onclick="syncMaterial.retory(\''+type+'\','+page+')">重试</button></div>');
			}
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
		var t=this, html = html1 = html2 = html3 = '<div class="media_preview_area">';
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
	parseImage: function(data){
		
	},
	parseVideo: function(rows){
		var html = '';
		for(var i=0; i<rows.length; i++){
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
		syncMaterial.data[data.media_id] = data;
		var img = '';
		var selected = Menu.focus.content && data.media_id == Menu.focus.content ? ' selected' : '';
		var html = '<div class="js-item appmsg'+(!data.content.length > 1 ? ' multi' : '') + selected +'" data-media_id="'+data.media_id+'"><div class="appmsg_content">';
		if(data.content.length > 1){
			for(var i=0; i<data.content.length; i++){
				var news = data.content[i];
				img = news.thumb_url.replace('http://mmbiz.qpic.cn', 'https://mmbiz.qlogo.cn');
				html += '<div class="appmsg_item js_appmsg_item has_thumb">';
				html += '<img class="js_appmsg_thumb appmsg_thumb" src="'+img+'" data-src="'+news.thumb_url+'">';
				html += '<h4 class="appmsg_title">';
				html += '<a href="'+news.url+'" target="_blank">'+news.title+'</a>';
				html += '</h4>';
				html += '</div>';
			}
		}else{
			var news = data.content[0];
			img = news.thumb_url.replace('http://mmbiz.qpic.cn', 'https://mmbiz.qlogo.cn');
			html += '<h4 class="js-item appmsg_title js_title"><a href="'+news.url+'" target="_blank">'+news.title+'</a></h4>';
			html += '<div class="appmsg_info">';
			html += '	<em class="appmsg_date">'+data.update_time+'</em>';
			html += '</div>';
			html += '<div class="appmsg_thumb_wrp">';
			html += '	<img  src="'+img+'" data-src="'+news.thumb_url+'" alt="封面" class="appmsg_thumb">';
			html += '</div>';
			html += '<p class="appmsg_desc">'+news.digest+'</p>';
		}
		html += '</div></div>';
		return html;
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
	}
}
