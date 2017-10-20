$(function(){
	var v = {
		$selected: null,
		$content: null,
		$toolbar: null,
		appendTool: function(){
			var html = ''+
				'<div id="js-toolbar" class="tool-bar">'+
					'<div class="tool-item" data-action="insertCard"><div class="tool-label">插名片</div></div>'+
					'<div class="tool-item" data-action="insertImage"><div class="tool-label">插图片</div></div>'+
					'<div class="tool-item" data-action="cancel"><div class="tool-label">取　消</div></div>'+
					'<div class="tool-item" data-action="insertLabel"><div class="tool-label">插文字</div></div>'+
					'<div class="tool-item" data-action="updateLabel"><div class="tool-label">改文字</div></div>'+
					'<div class="tool-item" data-action="delElement"><div class="tool-label">刪　除</div></div>'+
				'</div>';
			$('body').append(html);
			
			html = '<div id="js-tool-bottom" class="tool-bar tool-bottom">'+
						'<div class="tool-item" id="reset"><div class="tool-label">重　做</div></div>'+
						'<div class="tool-item" id="redo"><div class="tool-label">恢　复</div></div>'+
						'<div class="tool-item" id="undo"><div class="tool-label">撤　销</div></div>'+
				   '<div>';
			$('body').append(html);
		},
		bindEvent: function($content, $toolbar){
			// 重新定位工具的位置和大小
			$(window).resize(function(e) {
				if(v.$selected){
					v.resetPosition(v.$selected);
				}
		    });
			
			// 点击元素弹出工具
			$content.on('click', function(e){
				//console.log(e.target.nodeName);
				if(e.target === $content[0]){
					return false;
				}
				
				var $ele = $(e.target);
				
				if(!!v.$selected && v.$selected[0] === e.target){
					var $parent = $ele.parent();
					if($parent[0] !== v.$content[0]){
						v.resetPosition($parent);
					}
				}else{
					v.resetPosition($ele);
				}
				
				v.$toolbar.show();
				return false;
			});
			
			v.$toolbar.on('click', '.tool-item', function(){
				var $this = $(this), action = $this.data('action');
				
				v[action].apply($this, [v.$selected]);
				return false;
			});
			
			var observer = new MutationObserver(function(mutations) {
			  mutations.forEach(function(mutation) {
				  v.record_list.push(mutation);
				  console.log(mutation);
			  });    
			});

			// 撤销重做
			var undo = new Undo($content[0], {
		        childList: true,
		        subtree: true,
		        attributes : false,
		        attributeOldValue : false,
		        characterData : true,
		        characterDataOldValue : true
		    });
			$('#undo').on('click', function(){
				if(!undo.undo()){
					alert('没有内容可撤销了！');
				}
				v.cancel();
				return false;
			});
			$('#redo').on('click', function(){
				if(!undo.redo()){
					alert('没有内容可恢复了！');
				}
				v.cancel();
				return false;
			});
			
			$('#reset').on('click', function(){
				undo.reset();
				v.cancel();
				document.body.scrollTop = 0;
				return false;
			});
		},
		editor: null,
		cancel: function($ele){
			if(v.$selected){
				v.$selected.removeClass('focus');
				v.$selected = null;
			}
			v.$toolbar.hide();
		},
		delElement: function($ele){
			$ele.remove();
			v.$toolbar.hide();
		},
		insertCard: function($ele){
			
		},
		insertImage: function($ele){
			var tagName = $ele[0].tagName, html = '<img src="https://img.yzcdn.cn/upload_files/2017/04/18/FjIq6L5NZyGEXH9RaN5gP69EaM8I.jpg" style="display:block">';
			if(tagName == 'IMG' || tagName == 'TEXT'){
				$ele.after(html);
			}else{
				$ele.append(html);
			}
			v.cancel();
		},
		// 重新定位工具的位置和大小
		resetPosition: function($ele){
			var $content = v.$content, $toolbar = v.$toolbar,
			left = $content.offset().left, right = window.document.documentElement.clientWidth - left - $content.outerWidth();
	    	$toolbar.css({left: left, right: right});
	    	
	    	var top = $ele.offset().top + $ele.outerHeight() + 3;
			$toolbar.css('top', top);
			
			if(!v.$selected || v.$selected[0] !== $ele[0]){
				$ele.addClass('focus');
				if(v.$selected){
					v.$selected.removeClass('focus');
				}
				v.$selected = $ele;
			}
		},
		init: function(){
			v.appendTool();
			v.$toolbar = $('#js-toolbar');
			v.$content = $('#js_content>section:eq(0)');
			
			v.bindEvent(v.$content, v.$toolbar);
			
			$('body').addClass('editing');
			// 把一些无用的html元素删掉
			// some code
		}
	};
	
	v.init();
});