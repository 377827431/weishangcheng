;(function(window){
	window.ME = {};
	ME.commands = {
		// 文字加粗
		bold: {
			toolbar: function(){
				return ''+
				'<div id="edui_bold" class="edui-button edui-bold" title="加粗">'+
					'<i class="edui-icon"></i>'+
				'</div>'
			},
			init: function(){
				var t = this;
				$('#edui_bold').on('click', function(){
					ME.document.body.focus();
					var weight = null, prev = null;
					ME.setStyle(function(){
						if(weight == null){
							weight = this.style.fontWeight;
							prev = this.previousSibling;
							if(weight == 'bold' || !prev){
								weight = ''
							}else if(prev.nodeName == '#text'){
								weight = 'bold'
							}else{
								weight = prev.style.fontWeight == '' ? 'bold' : '';
							}
						}
						this.style.fontWeight = weight;
					});
					return false;
				});
			}
		},
		// 文字字体大小
		fontsize: {
			toolbar: function(){
				return ''+
				'<div id="edui_fontSize" class="edui-button edui-font-size" title="字体大小">'+
					'<i class="edui-icon"></i>'+
					'<div class="edui-content">'+
						'<div class="edui-list">'+
			                '<div class="edui-item" style="font-size:10px">发</div>'+
			                '<div class="edui-item" style="font-size:11px">发</div>'+
			                '<div class="edui-item" style="">发</div>'+
			                '<div class="edui-item" style="font-size:13px">发</div>'+
			                '<div class="edui-item" style="font-size:14px">发</div>'+
			                '<div class="edui-item" style="font-size:15px">发</div>'+
			                '<div class="edui-item" style="font-size:16px">发</div>'+
			                '<div class="edui-item" style="font-size:18px">发</div>'+
			                '<div class="edui-item" style="font-size:20px">发</div>'+
			                '<div class="edui-item" style="font-size:22px">发</div>'+
			                '<div class="edui-item" style="font-size:25px">发</div>'+
			                '<div class="edui-item" style="font-size:28px">发</div>'+
			                '<div class="edui-item" style="font-size:30px">发</div>'+
			                '<div class="edui-item" style="font-size:32px">发</div>'+
			            '</div>'+
			        '</div>'+
				'</div>';
			},
			init: function(){
				var t = this;
				$('#edui_fontSize .edui-item').on('click', function(){
					ME.document.body.focus();
					var fontSize = this.style.fontSize;
					ME.setStyle(function(){
						this.style.fontSize = fontSize;
					});
					return false;
				});
			}
		},
		// 文字颜色
		color: {
			toolbar: function(){
				return ''+
				'<div id="edui_color" class="edui-button edui-color" title="字体颜色">'+
					'<i class="edui-icon"></i>'+
					'<div class="edui-content">'+
						'<div class="edui-list">'+
			                '<div class="edui-item" style="background-color:#FF4136"></div>'+
			                '<div class="edui-item" style="background-color:#FFDC00"></div>'+
			                '<div class="edui-item" style="background-color:#FF851B"></div>'+
			                '<div class="edui-item" style="background-color:#111111"></div>'+
			                '<div class="edui-item" style="background-color:#001f3f"></div>'+
			                '<div class="edui-item" style="background-color:#AAAAAA"></div>'+
			                '<div class="edui-item" style="background-color:#DDDDDD"></div>'+
			                '<div class="edui-item" style="background-color:#0074D9"></div>'+
			                '<div class="edui-item" style="background-color:#7FDBFF"></div>'+
			                '<div class="edui-item" style="background-color:#39CCCC"></div>'+
			                '<div class="edui-item" style="background-color:#3D9970"></div>'+
			                '<div class="edui-item" style="background-color:#2ECC40"></div>'+
			                '<div class="edui-item" style="background-color:#01FF70"></div>'+
			                '<div class="edui-item" style="background-color:#85144b"></div>'+
			                '<div class="edui-item" style="background-color:#B10DC9"></div>'+
			                '<div class="edui-item" style="background-color:#F012BE"></div>'+
			            '</div>'+
					'</div>'+
				'</div>'
			},
			init: function(){
				var t = this;
				$('#edui_color .edui-item').on('click', function(){
					ME.document.body.focus();
					var color = this.style.backgroundColor;
					ME.setStyle(function(){
						this.style.color = color;
					});
					return false;
				});
			}
		},
		// 插入图片
		image: {
			toolbar: function(){
				return ''+
				'<div id="edui_image" class="edui-button edui-image" title="上传图片">'+
					'<i class="edui-icon"></i>'+
					'<input type="file" id="edui_img_file" name="upfile" accept="image/jpg,image/jpeg,image/png">'+
				'</div>'
			},
			init: function(){
				require(['h5/lrzImage'], function(){
					$('#edui_img_file').lrzImage({
						url: get_url('/ueditor/index?action=uploadscrawl'),
						preview: function(rst, vid){
							var $html = $('<p class="edui-image"><img id="lrz_img_'+vid+'" src="'+rst.base64+'" style="width:100%"><span class="edui-draggable">移动</span><button type="button" class="btn btn-red btn-xxsmall js-buy-card" style="position: absolute;right: 15px;bottom: 15px;" data-id="0">立即充值</button></p>');
							var selection = ME.selection, range = null, element = null;
							if(selection.rangeCount > 0){
								range = selection.getRangeAt(0);
								if(range.endContainer.nodeName == 'BODY'){
									element = range.endContainer;
								}else{
									element = ME.getPElement(range.endContainer);
								}
							}else{
								element = ME.document.body;
							}

							if(element.nodeName == 'P'){
								$(element).after($html);
								if(ME.emptyText(element.innerHTML)){
									element.remove();
								}
							}else{
								$(element).append($html);
							}
							ME.commands.buy.setSelect($html);
						},
						success: function(data, vid){
							ME.document.getElementById('lrz_img_'+vid).src = data.url;
						},
						error: function(vid){
							ME.document.getElementById('lrz_img_'+vid).parentElement.remove();
						}
					});
				});
			}
		},
		buy: {
			toolbar: function(){
				return ''+
				'<div id="edui_buy" class="edui-button edui-buy" title="立即购买按钮图片">'+
					'<i class="edui-icon"></i>'+
					'<div class="edui-content">'+
						'<div class="edui-list">'+
		                	'<div class="edui-item">按钮颜色'+
				                '<select id="btn-buy-color">'+
									'<option value="btn-red">红色</option>'+
									'<option value="btn-white">白色</option>'+
									'<option value="btn-yellow">黄色</option>'+
									'<option value="btn-grayeee">灰色</option>'+
									'<option value="btn-orange-dark">橘红色</option>'+
								'</select>'+
			                '</div>'+
			                '<div class="edui-item js-img-delete">删除图片</div>'+
			                '<div class="edui-item js-text-before">插入文字</div>'+
			                '<div class="edui-item js-text-after">添加文字</div>'+
			                '<div class="edui-item">会员级别'+
				                '<select id="btn-buy-level">'+
									'<option value="0">删除按钮</option>'+
									'<option value="1">铜牌会员</option>'+
									'<option value="2">金牌会员</option>'+
									'<option value="3">银牌会员</option>'+
									'<option value="4">砖石会员</option>'+
								'</select>'+
			                '</div>'+
						'</div>'+
					'</div>'+
				'</div>'
			},
			init: function(){
				var t = this, $edui = $('#edui_buy');
				$('#btn-buy-color').on('change', function(){
					var $btn = t.$btn;
					if(!$btn){
						return toast.show('请先上传大图<br>或点击立即购买按钮'), false;
					}
					
					$btn.attr('class', 'btn '+this.value+' btn-xxsmall js-buy-card');
					return false;
				});
				
				$('#btn-buy-level').on('change', function(){
					var $btn = t.$btn;
					if(!$btn){
						return toast.show('请先上传大图<br>或点击立即购买按钮'), false;
					}
					
					$btn.css('opacity', this.value == 0 ? '.3' : 1).attr('data-id', this.value);
					return false;
				});
				
				$edui.find('.js-img-delete').on('click', function(){
					if(t.$btn){
						t.$btn.parent().remove();
					}
					t.$btn = null;
					return false
				});
				
				$edui.find('.js-text-before,.js-text-after').on('click', function(){
					var $this = $(this), $btn = t.$btn;
					if(!$btn){
						return false
					}
					
					var ctrl = document.createElement('p');
					ctrl.innerHTML = '&#8203;';
					
					if($this.hasClass('js-text-before')){
						$btn.parent().before(ctrl);
					}else{
						$btn.parent().after(ctrl);
					}

					ME.document.body.focus();
					var range = ME.document.createRange();
					range.setStart(ctrl, 0);
					range.setEnd(ctrl, 0);
					ME.selection.removeAllRanges();
					ME.selection.addRange(range);
					return false
				});
			},
			setSelect: function($element){
				var $btn = this.$btn = $element.find('.js-buy-card');
				var id = $btn.attr('data-id');
				$('#btn-buy-level').val(id);
				
				$('#edui_buy').trigger('click');
			}
		},
		save: {
			toolbar: function(){
				return '<div id="edui_save" class="edui-button edui-save" title="保存"><i class="edui-icon"></i></div>';
			},
			init: function(){
				$('#edui_save').on('click', function(){
					ME.option.save(ME.document.body.innerHTML);
					return false;
				})
			}
		}
	};
	
	ME.selection = {}
	
	// 创建元素
	ME.createElement = function(html){
		var div = document.createElement('div');
		div.innerHTML = html;
		return div.children[0];
	}
	
	// 在element后追加元素
	ME.append = function(element, node, position){
		if(typeof node == 'string'){
			if(element.nodeName == '#text'){
				ME.appendText(element, node);
			}else{
				element.innerHTML += node;
			}
			return;
		}
	}
	
	ME.appendText = function(textNode, value){
		textNode.nodeValue += value;
	}
	
	// iframe初始化完毕
	ME._setup = function(doc){
		ME.document = doc;
		doc.body.innerHTML = ME.html();
		
		if(doc.body.childElementCount == 0){
			doc.body.innerHTML = '<p>&#8203;</p>';
		}
		doc.body.addEventListener('keydown', function(e){
			switch(e.keyCode){
				case 8: // 删除
					break;
				case 16: // 方向选择
				case 37: // 向左选择
				case 38: // 向上选择
				case 39: // 向右选择
				case 40: // 向下选择
					break;
				case 13: // 回车
					var p = document.createElement('p');
					p.innerHTML = '&#8203;';
					var prev = ME.getPElement(ME.selection.focusNode);
					ME.insertAfter(p, prev);

					var range = ME.document.createRange();  
					range.selectNodeContents(p);  
					range.collapse(false);  
					var sel = ME.selection;  
					sel.removeAllRanges();  
					sel.addRange(range); 

					e.stopPropagation();
					e.preventDefault();
					break;
				default:
					if(this.childElementCount == 0){
						this.innerHTML = '<p>&#8203;</p>';
					}
					break;
			}
		});

		ME.selection = doc.getSelection();
		doc.body.focus();
		
		$(doc.body).on('click', '.edui-image', function(){
			return ME.commands.buy.setSelect($(this)), false;
		});
	}

	ME.initToolbar = function(){
		var toolbars = this.option.toolbars, commands = this.commands;
		
		//  创建工具栏容器
		var container = document.getElementById('edui-toolbar');
		if(!container){
			container = document.createElement('div');
			container.setAttribute('class', 'edui-toolbar');
			container.setAttribute('id', 'edui-toolbar');
			document.body.appendChild(container);
		}

		// 填充工具栏项目
		var html = '', cmd = '';
		for(var i=0; i<toolbars.length; i++){
			cmd = toolbars[i];
			if(cmd == '|'){
				
			}else{
				html += commands[cmd].toolbar();
			}
		}
		container.innerHTML = html;
		
		// 初始化工具栏项目(事件)
		for(var i=0; i<toolbars.length; i++){
			cmd = toolbars[i];
			if(cmd == '|'){
				continue;
			}
			commands[cmd].init();
		}
		
		var $buttons = $(container).children('.edui-button');
		$buttons.on('click', function(){
			var $content = $(this).children('.edui-content');
			if($content.length > 0){
				$buttons.children('.edui-content').removeClass('active');
				return $content.addClass('active'),false
			}else{
				ME.document.body.focus();
			}
		});
	}
	
	// 初始参数
	ME.option = {
		 // 显示哪些按钮
		 toolbars: ['fontsize', 'bold', 'color', '|', 'buy', 'image', 'save']
		 // iframe内需要加载的样式文件
		,css: []
	}
	
	// 获取/设置html内容
	ME.html = function(html){
		if(html == undefined){
			return this._html;
		}else{
			this._html = html;
		}
	}

	// 在被选元素的结尾插入内容
	ME.append = function(newEl, targetEl){
		targetEl.parentNode.appendChild(newEl);
	}
	ME.insertAfter = function(newEl, targetEl){
	    var parentEl = targetEl.parentNode;
	    if(!parentEl.lastChild || parentEl.lastChild == targetEl){
	     	parentEl.appendChild(newEl);
	    }else{
	     	parentEl.insertBefore(newEl, targetEl.nextSibling);
	    }            
	}
	ME.insertBefore = function(newEl, targetEl){
		targetEl.parentNode.insertBefore(newEl, targetEl);
	}
	
	// 获取选中的节点
	ME.getSelectNodes = function(list, node, range){
		var startNode = range.startContainer,
			endNode = range.endContainer;
		if(node.nodeName == '#text'){
			list.push({
				node: node,
				start: node == startNode ? range.startOffset : 0,
				end: node == endNode ? range.endOffset : node.length
			});
			
			if(node == endNode){return}
		}else{ // 元素
			if(node == endNode){return}
			list.push({node: node});
		}
		
		if(node.nextSibling){
			node = node.nextSibling;
		}else if(node.parentNode){
			var parent = node.parentNode;
			node = parent.nextSibling ? parent.nextSibling : parent.parentNode.nextSibling;
		}else{
			return;
		}
		
		if(!node){
			return;
		}
		
		if(node.nodeName != '#text'){
			if(node.nodeName == 'P' && node.classList.contains('edui-image')){
				return;
			}
			
			node = node.childNodes[0];
			if(node.nodeName != '#text'){
				node = node.childNodes[0]
			}
		}

		ME.getSelectNodes(list, node, range);
	}
	
	ME.getSelectPosition = function(P, endNode){
		var offset = 0, nodes = P.childNodes;
		for(var i=0; i<nodes.length; i++){
			if(nodes[i].nodeName == '#text'){
				if(nodes[i] == endNode){
					return offset;
				}
				offset += nodes[i].nodeValue.length;
			}else{
				var nodes2 = nodes[i].childNodes;
				for(var j=0; j<nodes2.length; j++){
					if(nodes2[j] == endNode){
						return offset;
					}
					offset += nodes2[j].nodeValue.length;
				}
			}
		}
		
		return -1;
	}
	
	ME.getStartNode = function(P, length){
		var result = {}, offset = 0, nodes = P.childNodes, len = 0;
		for(var i=0; i<nodes.length; i++){
			if(nodes[i].nodeName == '#text'){
				len = nodes[i].nodeValue.length;
				if(offset+len > length){
					result.node = nodes[i];
					result.length = length - offset;
					return result;
				}
				offset += len;
			}else{
				var nodes2 = nodes[i].childNodes;
				for(var j=0; j<nodes2.length; j++){
					len = nodes2[j].nodeValue.length;
					if(offset+len > length){
						result.node = nodes2[j];
						result.length = length - offset;
						return result;
					}
					offset += len;
				}
			}
		}
		return result;
	}
	
	ME.getEndNode = function(P, length){
		var result = {}, offset = 0, nodes = P.childNodes, len = 0;
		for(var i=0; i<nodes.length; i++){
			if(nodes[i].nodeName == '#text'){
				len = nodes[i].nodeValue.length;
				if(offset+len >= length){
					result.node = nodes[i];
					result.length = length - offset;
					return result;
				}
				offset += len;
			}else{
				var nodes2 = nodes[i].childNodes;
				for(var j=0; j<nodes2.length; j++){
					len = nodes2[j].nodeValue.length;
					if(offset+len >= length){
						result.node = nodes2[j];
						result.length = length - offset;
						return result;
					}
					offset += len;
				}
			}
		}
		return result;
	}
	
	ME.getPElement = function(lastP){
		while(lastP.nodeName != 'P'){
			lastP = lastP.parentElement;
		}
		return lastP;
	}
	
	ME.emptyText = function(text){
		return text == '' || encodeURI(text) == "%E2%80%8B"
	}
	
	ME.setStyle = function(callback){
		if(ME.selection.rangeCount == 0){
			return toast.show('请按回车或选择可编辑的元素'), false;
		}
		var selection = ME.selection,
			range     = selection.getRangeAt(0),
			startNode = range.startContainer,
			endNode   = range.endContainer,
			list      = [];
		
		ME.getSelectNodes(list, startNode, range);
		
		// 最顶端的P元素
		var firstP = ME.getPElement(range.startContainer), startOffset = 0;
		// 文字在P标签中的位置
		startOffset = range.startOffset + ME.getSelectPosition(firstP, startNode);

		// 最顶端的P元素
		var lastP = ME.getPElement(range.endContainer), lastOffset = 0;
		// 文字在P标签中的位置
		endOffset = range.endOffset + ME.getSelectPosition(lastP, endNode);
		
		for(var i=0; i<list.length; i++){
			var element = null, row = list[i], node = row.node, start = row.start, end = row.end, text = '';
			if(node.nodeName == '#text'){
				text = node.nodeValue;
				
				var parent = node.parentElement;
				if(!parent){ // 元素被合并了
					continue;
				}
				
				if(parent.nodeName != 'P'){
					parent.innerHTML = text.substring(0, start);
					
					element = parent.cloneNode();
					element.innerHTML = start == end ? '&#8203;' : text.substring(start, end);
					ME.insertAfter(element, parent);
					
					if(end != text.length){
						var endElement = parent.cloneNode();
						endElement.innerHTML = text.substring(end);
						ME.insertAfter(endElement, element);
					}
					
					if(ME.emptyText(parent.innerHTML)){
						parent.remove();
					}
				}else{
					// 创建中间
					element = document.createElement('span');
					element.innerHTML = start == end ? '&#8203;' : text.substring(start, end);
					ME.insertAfter(element, node);

					// 保留结尾
					if(end != text.length){
						var endNode = document.createTextNode(text.substring(end));
						ME.insertAfter(endNode, element);
					}
					
					// 保留开头
					if(start > 0){
						node.nodeValue = text.substring(0, start);
					}else{
						node.remove();
					}
				}
				
				if(ME.emptyText(node.nodeValue)){
					node.remove();
				}
			}else{
				element = node;
				text = element.innerHTML;
				var endNode = element.cloneNode();
				endNode.innerHTML = text.substring(end);
				ME.insertAfter(endNode, element);
				
				element.innerHTML = text.substring(0, end);
			}
			callback.apply(element);

			var prev = element.previousSibling,
				next = element.nextSibling,
				style = element.getAttribute('style'),
				style1Str = style ? style.replace(/(\s|,|:)/g, '', '').split(';').sort().join('') : '';
			if(prev && prev.nodeName != '#text'){
				var style2 = prev.getAttribute('style'),
					style2Str = style2 ? style2.replace(/(\s|,|:)/g, '', '').split(';').sort().join('') : '';
				if(style1Str == style2Str){
					element.innerHTML = prev.innerHTML + element.innerHTML;
					prev.remove();
				}
			}
			
			if(!style1Str){
				if(!prev && !next){
					element.parentElement.innerHTML = element.innerHTML;
				}else{
					if(next && next.nodeName == '#text'){
						element.innerHTML += next.nodeValue;
						next.remove();
					}
					
					if(prev && prev.nodeName == '#text'){
						prev.nodeValue += element.innerHTML;
					}else{
						var endNode = document.createTextNode(element.innerHTML);
						ME.insertAfter(endNode, prev ? prev : element);
					}
					element.remove();
				}
			}
		}
		
		// 计算从哪个元素开始选中
		var range = ME.document.createRange();
		var start = ME.getStartNode(firstP, startOffset), end = null;
		if(start.length == 0 && ME.emptyText(start.node.nodeValue)){
			end = start;
			end.length = 1;
		}else{
			end = ME.getEndNode(lastP, endOffset);
		}

		range.setStart(start.node, start.length);
		range.setEnd(end.node, end.length);	
		selection.removeAllRanges(); 
		selection.addRange(range);
	}
	
	$.fn.editor = function(option){
		var $this = this, html = $this.html();
		ME.html(html);
		
		// 合并初始化参数
		if(!option){option = {}}
		for(var k in option){
			ME.option[k] = option[k]
		}
		
		var height = document.documentElement.clientHeight - 80;
		
		// iframe容器
		html = '<!DOCTYPE html>' +
        '<html xmlns=\'http://www.w3.org/1999/xhtml\' class=\'view\' ><head>' +
        '<style type=\'text/css\'>' +
        '.view{padding:0;word-wrap:break-word;cursor:text;height:100%;}\n' +
        'body{margin:0px;padding:0;font-family:sans-serif;font-size:12px}body.view{padding-bottom:80px}.btn[data-id="0"]{display:none}' +
        'p{margin: 0 0 1px 0;}.edui-image{margin:0;padding:0;line-height:0;position:relative;user-modify:read-only;-webkit-user-modify: read-only;}.edui-draggable{position: absolute;right: 0;top: 50%;padding: 20px;margin-top: -20px;opacity: 0.3;    background-color: #fff;}</style>';
		if(option.css){
			var css = option.css;
			if(typeof css == 'string'){
				html += '<link rel=\'stylesheet\' type=\'text/css\' href=\''+css+'\'/>';
			}else{
				for(var i=0; i<css.length; i++){
					html += '<link rel=\'stylesheet\' type=\'text/css\' href=\''+encodeURI(css[i])+'\'/>';
				}
			}
		}
		
        html += '<script src=\'https://cdn.bootcss.com/Sortable/1.5.1/Sortable.min.js\'></script>' +
        '<script>setTimeout(function(){window.parent.ME._setup(document);Sortable.create(document.body, {animation: 150,handle: \'.edui-draggable\',draggable: \'.edui-image\'});},0);</script>' +
        '</head><body class=\'view\' contenteditable=\'true\' spellcheck=\'false\'></body></html>';
		$this.html('<iframe style="width:100%;height:'+height+'px" frameborder="0" src="javascript:void(function(){document.open();document.write(&quot;'+html+'&quot;);document.close();}())"></iframe>');
		
		// 创建工具栏
		ME.initToolbar();
		
		if(option.cards){
			var html = '<option value="0">删除按钮</option>', cards = option.cards;
			for(var i=0; i<cards.length; i++){
				html += '<option value="'+cards[i].id+'">'+cards[i].title+'</option>';
			}
			$('#btn-buy-level').html(html);
		}
	}
})(window);