/**
 * 系统集成js
 */
function zh_table() {
	$.extend($.fn.bootstrapTable.defaults, {
        formatLoadingMessage: function () { return '正在加载中...'; },
        formatRecordsPerPage: function (pageNumber) { return '每页显示 ' + pageNumber + ' 条记录'; },
        formatShowingRows: function (pageFrom, pageTo, totalRows) { return '显示第 ' + pageFrom + ' 到第 ' + pageTo + ' 条记录，总共 ' + totalRows + ' 条记录'; },
        formatSearch: function () { return '搜索'; },
        formatNoMatches: function () { return '没有找到匹配的记录'; },
        formatRefresh: function () { return '刷新'; },
        formatToggle: function () { return '切换'; },
        formatColumns: function () { return '选择列'; }
    });
}
(function(){ 
	var GridView = function(el, option){
		if(typeof $.fn.bootstrapTable == 'undefined'){
			if($('#bootstrap_table_css').length == 0){
				$('head:first').append('<link id="bootstrap_table_css" rel="stylesheet" href="//cdn.bootcss.com/bootstrap-table/1.10.1/bootstrap-table.min.css" />');
			}
			
			$.ajax({
				url : '//cdn.bootcss.com/bootstrap-table/1.10.1/bootstrap-table.min.js',
				dataType : "script",
				cache: true,
				success:function(data, str){
					new GridView(el, option);
				}
			});
			return;
		}
		
		this.$table = $(el);
		if(this.$table.length == 0){
			return;
		}
		
		this.$toolbar = $(this.$table.data('toolbar'));
		this.$form = this.$toolbar.find('form:first');
		this.bootstrapTable = null;
		this.queryParams = {};
		this.currentRow = null;
		this.uniqueId = this.$table.data('uniqueId') || 'id';
		this.module = this.$toolbar.data('module');
		
		this.pagination = this.$table.data('pagination') == false ? false : true;
		this.sidePagination = this.$table.data('sidePagination') || "server";
		//this.clientSort = this.$table.data('clientSort') == false ? false : true;
		this.pageSize = this.$table.data('pageSize') || 50;
		this.clickToSelect = this.$table.data('clickToSelect') == false ? false : true;
		this.showRefresh = this.$table.data('showRefresh') || false;
		this.$table.data('gridview', this);
		this.init();
		this.expand = {};
	};
	
	GridView.prototype.init = function(){
		this.initForm();
		this.initTable();
		this.initToolbar();

		this.$table.trigger('table.ready', [this]);
		this.$table.data('table.ready', true);
	};
	
	GridView.prototype.initTable = function(){
		zh_table();
		var $this = this,
			$table = $this.$table;
		
		var isTreeView = $table.data('treeView');
		if(isTreeView){
			var $th = $table.children('thead').find('th[data-tree="true"]');
			$th.attr('data-formatter', 'gridview_formatter_tree');
			gridview_formatter_tree = function(val, row, index){
				return '<span class="collapsed" style="padding-left: '+(row.level-1) * 19+'px;">'+
						(row.is_last ? '' : '<a href="javascript:;">&nbsp;</a>')+
					   '</span>' + val;
			}
			
			$table.on('click', 'td>.collapsed, td>.expanded',function(){
				var $this = $(this),
					$tr = $this.parents('tr:first'),
					firstIndex = $tr.data('index'),
					$nextAllTr = $tr.nextAll('[data-pid="'+$tr.data('uniqueid')+'"]'),
					lastIndex = $nextAllTr.last().data('index');
				
				if(!$this.hasClass('expanded')){
					$this.addClass('expanded');
					$nextAllTr.show();
				}else{
					$this.removeClass('expanded');
					var $range = $tr.nextAll(':lt('+(lastIndex - firstIndex)+')').hide().find('>td>.expanded');
					$range.trigger('click');
				}
				return false;
			});
		}
		
		// 图片懒加载
		var lazyload = $table.data('lazyload') || false;
		$table.bootstrapTable({
			striped: false, // 隔行换色
			uniqueId: $this.uniqueId,
			showRefresh: $this.showRefresh,
			//clientSort: $this.clientSort,
			pagination: $this.pagination,
			classes: 'table table-hover table-no-bordered' + (isTreeView ? ' table-tree' : ''),
			sidePagination: $this.sidePagination,
			pageSize: $this.pageSize,
			//clickToSelect: $this.clickToSelect,
			queryParams: function(params){
				delete params.search;
				params = $.extend($this.queryParams, params);
				$this.queryParams = params;
				return params;
			},
	        rowAttributes: function (row, index) {
	        	if(isTreeView){
	        		var attr = {'data-pid': row.pid};
	        		if(row.pid != 0){
	        			attr['style'] = 'display:none'
	        		}
					return attr;
				}
	            return {};
	        },
	        onAll: function (name, args) {
	        	//$table.trigger('all', [name, args]);
	            return false;
	        },
	        onClickCell: function (field, value, row, $element) {
	        	//$table.trigger('clickCell', [field, value, row, $element]);
	            return false;
	        },
	        onDblClickCell: function (field, value, row, $element) {
	        	//$table.trigger('dblClickCell', [field, value, row, $element]);
	            return false;
	        },
	        onClickRow: function (row, $element) {
	        	$this.currentRow = row;
	        	$element.addClass('info').siblings().removeClass('info');
	        	//$this.scrollPosition = $table.bootstrapTable('getScrollPosition');
	        	$table.trigger('clickRow', [row, $element]);
	        	return false;
	        },
	        onDblClickRow: function (item, $element) {
	        	//$table.trigger('dblClickRow', [item, $element]);
	            return false;
	        },
	        onSort: function (name, order) {
	        	//$table.triggerHandler('sort', [name, order]);
	        },
	        onCheck: function (row) {
	        	//$table.trigger('check', [row, $this]);
	            return false;
	        },
	        onUncheck: function (row) {
	        	//$table.trigger('uncheck', [row, $this]);
	            return false;
	        },
	        onCheckAll: function (rows) {
	        	//$table.trigger('checkAll', [rows]);
	            return false;
	        },
	        onUncheckAll: function (rows) {
	        	//$table.trigger('uncheckAll', [rows]);
	            return false;
	        },
	        onCheckSome: function(rows){
	        	//$table.trigger('checkSome', [rows]);
	            return false;
	        },
	        onUncheckSome: function(rows){
	        	//$table.trigger('uncheckSome', [rows]);
	            return false;
	        },
	        onLoadSuccess: function (data) {
	            return false;
	        },
	        onLoadError: function (status) {
	        	//$table.trigger('loadError', [status]);
	            return false;
	        },
	        onColumnSwitch: function (field, checked) {
	        	//$table.trigger('columnSwitch', [field, checked]);
	            return false;
	        },
	        onPageChange: function (number, size) {
	        	//$table.trigger('pageChange', [number, size]);
	            return false;
	        },
	        onSearch: function (text) {
	        	//$table.trigger('search', [text]);
	            return false;
	        },
	        onToggle: function (cardView) {
	        	//$table.trigger('toggle', [cardView]);
	            return false;
	        },
	        onPreBody: function (data) {
	        	//$table.trigger('preBody', [data]);
	            return false;
	        },
	        onPostBody: function () {
	        	var data = this.data;
	        	var current = null;
	        	if($this.expand){
	        		for(var i=0; i<data.length; i++){
		        		if(typeof $this.expand[data[i].id] != 'undefined'){
		        			$this.bootstrapTable.expandRow($this.expand[data[i].id]);
		        		}
		        		
		        		if($this.currentRow && $this.currentRow.id == data.id){
		        			current = data[i];
		        		}
		        	}
	        	}
	        	
	        	$this.currentRow = current;
	        	
	        	// 图片懒加载
	        	if(lazyload){
	        		if(typeof $.lazyload == 'function'){
	        			$table.find("img").lazyload({
	    	        		placeholder : "__CDN__/img/logo_rgb.jpg",
	    	        	    threshold : 270
	    	        	})
	        		}else{
		        		$.ajax({
		        			type: 'GET',
		        			url: '//cdn.bootcss.com/jquery.lazyload/1.9.1/jquery.lazyload.min.js',
		        			dataType: 'script',
		        			cache: true,
		        			success: function(){
		        				$table.find("img").lazyload({
			    	        		placeholder : "__CDN__/img/logo_rgb.jpg",
			    	        	    threshold : 270
			    	        	})
		        			}
		        		});
	        		}
	        	}
	            return false;
	        },
	        onPostHeader: function () {
	        	//$table.trigger('postHeader');
	            return false;
	        },
	        onExpandRow: function (index, row, $detail) {
	        	$this.expand[row.id] = index;
	            return false;
	        },
	        onCollapseRow: function (index, row) {
	        	delete $this.expand[row.id];
	            return false;
	        },
	        onRefreshOptions: function(){
	        	alert();
	        }
		});
		
		$this.bootstrapTable = $table.data('bootstrap.table');
		$this.bootstrapTable.$pagination.addClass('clearfix');
		if($this.bootstrapTable.$toolbar.length > 0){
			var $columns = $this.bootstrapTable.$toolbar.children('.columns');
			if($columns.length > 0){
				$this.bootstrapTable.$toolbar.prepend($columns);
			}
		}
	};
	
	GridView.prototype.initForm = function(){
		var $this = this;
		if($this.$form.length == 0){
			return;
		}
		
		var row = $this.getFormValue();
		$this.queryParams = row;
		
		$this.$form.on('submit', function(){
			var row = $this.getFormValue();
			$this.queryParams = row;
			$this.bootstrapTable.options.pageNumber = 1;
			$this.$table.bootstrapTable('refresh');
			return false;
		}).on('change', 'input',function(){
			this.value = $.trim(this.value);
		});
		
		/*
		if(typeof $.fn.validate == 'undefined'){
			win.getScript('//cdn.bootcss.com/jquery-validate/1.15.0/jquery.validate.min.js', function(){
				$this.initForm();
			});
			return;
		}

		zh_validator();
		$this.$form.validate({
	        errorClass:  "help-block",
	        errorElement: "span",
	        ignore: ".ignore",
	        onfocusout: false,
	        onkeyup: false,
	        onclick: false,
	        focusInvalid: false,
	        focusCleanup: true,
	        highlight: function (element, errorClass, validClass) {
	        	$(element).parents('.control-group:eq(0)').addClass('error');
	        },
	        unhighlight: function (element, errorClass, validClass) {
	        	$(element).parents('.control-group:eq(0)').removeClass('error');
	        },
	        errorPlacement: function(error, element){
	        	error.appendTo(element.parents('.controls:eq(0)'));  
	        },
	        submitHandler: function () {
	        	var row = $this.getFormValue();
				$this.queryParams = row;
				$this.bootstrapTable.options.pageNumber = 1;
				$this.$table.bootstrapTable('refresh');
				return false;
	        }
	    });
	    */
	};
	
	// 获取form表单 + table查询条件
	GridView.prototype.getQueryParams = function(){
		var row = this.getFormValue();
		return $.extend(this.queryParams, row);
	}
	
	GridView.prototype.initToolbar = function(){
		var $this = this;
		
		this.$toolbar.on('click', '.btn[data-name]',function(){
			// 要执行的事件名称
			var $btn = $(this);
			var eventName = $btn.data('name');
			var params = {
					url: $btn.data('eventValue') == '' ? ($this.module + '/' + eventName) : $btn.data('eventValue'), 
					event_type: $btn.data('eventType'),
					event_value: $btn.data('eventValue'),
					target: $btn.data('target'),
					text: this.innerText
				};
			
			if(eventName.substr(0, 6) == 'search'){// 搜索
				$this.$form.submit();
				return;
			}
			
			if(params.event_type == 'custom'){ // 自定义事件
				 return $this.$table.trigger(eventName, [$this, $btn]);
			}else if(params.event_type == 'view'){ // 打开网址
				params.data = {};
				
				if(eventName.substr(0, 4) == 'edit' || eventName.substr(0, 6) == 'detail' || eventName.substr(0, 6) == 'update'){
					if($this.currentRow == null){
						 return alertMsg('请先选择要编辑的数据！', 'warning');
					}
					
					//params.data[$this.uniqueId] = $this.currentRow[$this.uniqueId];
					
					if(params.event_value == ''){
						params.url += '?' + $this.uniqueId + '=' + $this.currentRow[$this.uniqueId];
					}
				}else if(eventName.substr(0, 6) == 'export'){
					var queryData = $this.getQueryParams(),
						queryParams = [];
					for(var key in queryData){
						queryParams.push(key+'='+queryData[key]);
					}
					params.url += '?' + queryParams.join('&');
				}
				
				var result = $this.$table.triggerHandler(eventName, [$this, params]);
				if(result === false){
					return false;
				}
				
				if(params.target == 'modal'){
					$this.loadModal(params.url, params.data);
					return false;
				}
				
				if(params.target == 'self' || params.target == ''){
					window.location.href = params.url;
				}else if(params.target == '_blank'){
					window.open(params.url);
				}else{
					var $container = $(params.target);
					$container.load(params.url, function(){
						win.init($container);
						$container.find('table[data-toggle="gridview"]').gridView();;
					});
				}
				return;
			}else if(params.event_type == 'javascript'){ // 打开网址
				return $('html').append('<script type="text/javascript">' + params.event_value + '</script>');
			}
			
			if(eventName.substr(0, 6) == 'delete'){
				var rows = $this.$table.bootstrapTable('getSelections'); // 当前页被选中项(getAllSelections 所有分页被选中项)
				if(rows.length == 0){ 
					alertMsg('请勾选要删除的数据', 'warning');
					return; 
				}
				
				var params = {
						rows: rows,
						length: rows.length,
						url:  $this.module + '/' + eventName,
						backdrop: true,
						title: '提示',
						message: '确定要删除选中的'+rows.length+'项吗？',
						okValue: '确定',
						cancelValue: '取消',
						ajaxMsg: '正在删除中...',
						data: null,
						ok: function(){
							var post_data = {};
							
							// 要删除的数据id数组
							var uniqueId = [];
							for(var i in params.rows){
								uniqueId.push(params.rows[i][$this.uniqueId]);
							}

							if(params.data == null){
								post_data[$this.uniqueId] = uniqueId.join(',');
							}else{
								post_data = params.data;
							}
							
							// 请求服务器删除数据
							$.ajax({
								url: params.url,
								dataType: 'json',
								data: post_data,
								waitting: params.ajaxMsg,
								type: 'post',
								success: function(ajaxData){
									var expand = $this.expand;
									for(var i=0; i<params.rows.length; i++){
										if(typeof expand[params.rows[i].id] != 'undefined'){
											delete expand[params.rows[i].id];
										}
									}
									$this.$table.bootstrapTable('remove', {field: 'id', values: uniqueId});
									
									// 通知删除成功
									var result = $this.$table.triggerHandler('deleted', [ajaxData, 'success']);
									if(result === false){ return false; }
									
									ajaxData.deletedRows = params.rows;
									
									if($this.bootstrapTable.data.length == 0 && $this.bootstrapTable.options.sidePagination == 'server'){
										$this.$table.bootstrapTable('refresh');
									}else{
										//$this.resetView();
									}
								},
								error: function(ajaxData){
									ajaxData.deletedRows = params.rows;
									$this.$table.triggerHandler('deleted', [ajaxData, 'error']);
								}
							});
						},
						cancel: function(){}
				};
				
				// 通知我要删除
				var result = $this.$table.triggerHandler('delete', [$this, params]);
				if(result === false){ return; }
				
				// 弹出删除提示
				alertConfirm({
					title: params.title,
					content: params.message,
					okValue: params.okValue,
					cancelValue: params.cancelValue,
					ok: params.ok,
					cancel: params.cancel,
					backdrop: params.backdrop
				});
			}else{
				$this.$table.triggerHandler(eventName, [$this, params]);
			}
		});
		
		var fieldKey = this.uniqueId;
		this.$table.on('click', '.js-edit, .js-delete',function(){
			var $ele = $(this),
				id = $ele.parents('tr:first').data('uniqueid'),
				url = $this.$toolbar.data('module'),
				href = $ele.attr('href'),
				postData = {};
			
			if($ele.hasClass('js-edit')){
				if(href != 'javascript:;'){
					url = href;
				}else{
					url += '/edit?'+fieldKey+'='+id
				}
				$this.loadModal(url);
			}else if($ele.hasClass('js-delete')){
				var message = $ele.data('message') || '确定删除吗？';
				if(!confirm(message)){
					return false;
				}
				
				postData[fieldKey] = id;
				$.ajax({
					url: url + '/delete',
					type: 'post',
					data: postData,
					dataType: 'json',
					success: function(){
						$this.remoad();
					}
				});
			}
			return false;
		});
	};
	
	GridView.prototype.refresh = function(){
		this.$table.bootstrapTable('refresh');
	};
	
	GridView.prototype.reload = function(){
		this.$form.submit();
	};
	
	GridView.prototype.loadModal = function(url, data){
		var $this = this;
		$.ajax({
			url: url,
			waitting: true,
			dataType: 'html',
			data: data,
			waitting: '正在加载，请稍后...',
			success: function(html){
				var $html = $('<div>'+html+'</div>');
				
				var $modal = $html.find('.modal:eq(0)');
				if($modal.length == 0){
					alertMsg(html, 'warning');
					return;
				}
				
				$html.appendTo('body');
				win.init($html);
				$modal.modal({ keyboard: false }).show();
				
				var action = '';
				var $form = $html.find('form');
				$modal.on('hide', function(){
					if($form.length > 0 && $form.data('submited') == true){
						if(action == ''){
							$this.$table.bootstrapTable('refresh');
						}else if(action == 'add'){
							//$this.resetView();
						}
					}
					
					$html.remove();
				});
				
				if($form.length > 0 && $form.attr('data-submit') == 'ajax'){
					$form.on('ajaxSubmit', function(e, data){
						var row = $this.getFormValue($form);
						if(!win.empty(data)){
	        				row = $.extend(row, data);
	        			}
	        			
	        			if(!win.empty(row[$this.uniqueId])){
							if($form.data('success') == 'refresh'){
								$form.data('success', null);
							}else{
		        				// 获取当前数据所在行
								var data_index = $this.bootstrapTable.$body.find('tr[data-uniqueid="'+row[$this.uniqueId]+'"]').attr('data-index');
								if(data_index == undefined){ // 添加数据
									action = 'add';
									$this.$table.bootstrapTable('insertRow', {index: 0, row: row});
								}else{// 更新行数据
									action = 'edit';
									$this.$table.bootstrapTable('updateRow', {index: data_index, row: row});
								}
							}
	        			}
					});
				}
			}
		});
	};
	
	GridView.prototype.showModal = function(modal){
		$this = this;
		var $modal = $(modal);
		
		$modal.modal({ keyboard:false}).show();
		$modal.on('hide', function(){
			var $form = $modal[0].nodeName == 'FORM' ? $modal : $modal.find('form');
			
			if($form.length >0 && $form.data('submited') == true){
				$form.data('submited', false);
				$this.$table.bootstrapTable('refresh');
			}
		});
	};
	
	GridView.prototype.getFormValue = function(selector){
		var $form = selector == undefined ? this.$form : $(selector);
		if($form.length == 0){
			return;
		}

		var row = {};
		var serializeArray = $form.serializeArray(), name;
		
		// 仅支持到一位数组
		$.each(serializeArray, function(i, item){
			name = item.name.substr(5,item.name.indexOf(']')-5);
			if(name == ''){
				row[item.name] = item.value;
			}else{
				if (row[name] !== undefined) {
					
					// 保存数组形式
		           if (!row[name].push) { 
		        	   row[name] = [row[name]]; 
		           }
		           row[name].push(item.value || '');
		        } else {
		        	row[name] = item.value || '';
		        }
			}
		});
		
		for(var i in row){
			if(row[i] instanceof Array){
				row[i] = row[i].join(',');
			}
		}
		return row;
	};
	
	GridView.prototype.current = function(){
		return this.currentRow;
	}
	
	var $tables = $('table[data-toggle="gridview"]');
	
	if($tables.length > 0){
		for(var i=0; i<$tables.length; i++){
			new GridView($tables.eq(i));
		}
	}
	
	$.fn.gridView = function(option, params){
		var $gridview = this.data('gridview');
		if(typeof option == 'string'){
			if($gridview == undefined){
				return;
			}
			
			if(typeof $gridview[option] == 'function'){
				return $gridview[option]();
			}else{
				return $gridview.$table.bootstrapTable(option);
			}
		}
		
		if($gridview != undefined){
			return;
		}
		
		if(this.length > 0){
			new GridView(this, option);
		}
		return this;
	};
})();