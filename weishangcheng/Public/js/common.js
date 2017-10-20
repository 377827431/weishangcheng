// validator
function zh_validator() {
	// 验证手机号
	jQuery.validator.addMethod("mobile", function (value, element) {
        var tel = /^1[3|4|5|7|8]\d{9}$/;
        return this.optional(element) || (tel.test(value));
    }, "请输入有效的手机号码");

	// 验证身份证号
    jQuery.validator.addMethod("cardid", function (value, element) {
        var tel = /^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/;
        return this.optional(element) || (tel.test(value));
    }, "请输入有效的身份证号");
    
    // 自定义正则验证
    jQuery.validator.addMethod("regular", function (value, element) {
        var regular = eval(element.getAttribute('data-rule-regular'));
        return this.optional(element) || (regular.test(value));
    }, "输入有误");
    
    $.extend($.validator.messages, {
    	required: "这是必填字段",
    	remote: "请修正此字段",
    	email: "请输入有效的电子邮件地址",
    	url: "请输入有效的网址",
    	date: "请输入有效的日期",
    	dateISO: "请输入有效的日期 (YYYY-MM-DD)",
    	number: "请输入有效的数字",
    	digits: "只能输入数字",
    	creditcard: "请输入有效的信用卡号码",
    	equalTo: "你的输入不相同",
    	extension: "请输入有效的后缀",
    	maxlength: $.validator.format("最多可以输入 {0} 个字符"),
    	minlength: $.validator.format("最少要输入 {0} 个字符"),
    	rangelength: $.validator.format("请输入长度在 {0} 到 {1} 之间的字符串"),
    	range: $.validator.format("请输入范围在 {0} 到 {1} 之间的数值"),
    	max: $.validator.format("请输入不大于 {0} 的数值"),
    	min: $.validator.format("请输入不小于 {0} 的数值")
    });
}

// table
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

$(function () {
    win.start();
    
    $('.js-back-to-top').on('click', function(){
    	document.body.scrollTop = 0;
    	return false;
    });
});

// 常用函数封装
var win = window.win = {
	menuSelector: '',
	headerSelector: '',
	start: function(){
		this.globalAjax();
		this.init('body');
	},
    init: function(selector){
    	var $selector = $(selector);
    	this.validate($selector.find('form[data-validate="true"]'));
    	
    	this.initDate($selector.find('.input-append.date,.calendar'));
    	$selector.find('.btn-back').on('click', function(){ win.back(); return false;});
    	
    	this.select2($selector.find('select.js-select2'));
    	this.initProcess($selector.find('.process'));
    	this.initRangeDate($selector.find('.data-range input'));
    },
    initProcess: function($process){
    	var $process = $(this);
    	if($process.length == 0){
    		return;
    	}
    	
		var $processCnt = $process.find('.process-cnt');
		var $processCntItems = $processCnt.find('.process-cnt-item');
		var $titles = $process.find('.process-bar>ul>li');
		$titles.on('click', function(){
			var $li = $(this),
				$prevAll = $li.prevAll(),
				$nextAll = $li.nextAll();
			
			$prevAll.addClass('pprev').removeClass('prev selected next nnext');
			$prevAll.eq(0).removeClass('pprev').addClass('prev');
			
			$nextAll.addClass('nnext').removeClass('prev pprev selected next');
			$nextAll.eq(0).removeClass('nnext').addClass('next');
			
			$li.addClass('selected').removeClass('prev pprev next nnext');
			
			$processCntItems.eq($li.index()).addClass('show').siblings().removeClass('show');
			return false;
		});
		
		$process.find('.js-next').on('click', function(){
			var index = $(this).data('index');
			$titles.eq(index).trigger('click');
			return false;
		});
    },
    empty: function(obj){
    	if(obj === undefined || obj === null || obj === ''){
    		return true;
    	}
    	
    	var type = typeof(obj);
    	if(type === 'object'){
    		for(var i in obj){
    			return false;
    		}
    		
    		return true;
    	}
    	
    	if(type === 'string' && $.trim(obj) === ''){
    		return true;
    	}
    	
    	return false;
    },
    redirect: function(url, time){
    	if(url == undefined || url == ''){
    		return;
    	}
    	
    	if(time == undefined){
    		window.location.href = url;
    	}else{
            setTimeout(function () {
                window.location.href = url;
            }, time);
    	}
    },
    modal: function(url){
    	$.ajax({
			url: url,
			dataType: 'html',
			success: function(html){
				var $html = $('<div>'+html+'</div>');
				
				var $modal = $html.find('.modal:first');
				if($modal.length == 0){
					alertMsg(html);
					return;
				}
				
				$html.appendTo('body');
				win.init($html);
				$modal.modal().show();
				$modal.on('hide', function(){
					$html.remove();
				});
			}
		});
    },
    back: function(steep){
    	location.href = document.referrer;
    },
	globalAjax: function(){
		$.ajaxSetup({
			waitting: false,
			$msg_box: null,
			beforeSend: function(XHR){
				if((this.type == 'POST' && this.waitting != false) || this.waitting != false){
					if(true == this.waitting || this.waitting == ''){
						this.waitting = '请稍后...';
					}
					if(this.waitting != undefined){
						this.$msg_box = alertMsg(this.waitting, -1);
					}
				}
				
				this.custom = {};
				this.custom.success = this.success;
				this.custom.error = this.error;
				this.custom.complete = this.complete;

				this.success = function(data, textStatus, jqXHR){
					var response_type = jqXHR.getResponseHeader("Content-Type");
					if(this.dataType != 'json' && response_type != 'application/json; charset=utf-8'){
						if(typeof this.custom.success == 'function'){
							this.custom.success(data, textStatus, jqXHR);
						}
						return;
					}
					
					// 我请求的不是json数据，而返回的却是json数据(可能服务端出错)
					if(this.dataType != 'json' && response_type == 'application/json; charset=utf-8' && typeof data != 'object'){
						data = $.parseJSON(data);
					}
					
					if(typeof data.info == 'string' && data.info != ''){
						alertMsg(data.info);
					}
					
					if(!win.empty(data.url)){
						return win.redirect(data.url, 2);
					}
					
					if(!win.empty(data.status)){
						if(data.status == 1){
							if(typeof this.custom.success == 'function'){
								return this.custom.success(typeof data.info != 'undefined' ? data.info : {}, textStatus, jqXHR);
							}else{
								return;
							}
						}else if(data.status == 0){
							if(typeof this.custom.error == 'function'){
								if(typeof data.info == 'object'){
									alertMsg('操作失败！', 'warning');
									return this.custom.error(data.info, textStatus, jqXHR);
								}else{
									return this.custom.error({}, textStatus, jqXHR);
								}
							}else{
								return;
							}
						}
					}else if(typeof this.custom.success == 'function'){
						this.custom.success(data, textStatus, jqXHR);
					}
				};
				this.error = function(data, textStatus, jqXHR){
					if(typeof this.custom.error == 'function'){
						this.custom.error({}, textStatus, jqXHR);
					}else{
						alertMsg('网络连接失败，请稍后再试！', 'error');
					}
				};
				this.complete = function(XHR, TS){
					if(this.$msg_box != null){
						this.$msg_box.remove();
					}
					if(typeof this.custom.complete == 'function'){
						this.custom.complete(XHR, TS);
					}
					
					if(typeof this.dialog == 'object'){
						this.dialog.remove();
					}
				};
			}
		});
	},
	getScript: function(url, fn){ // 下载js
		$.ajax({
			url : url,
			dataType : "script",
			cache: true,
			success:function(data, str){
				if(typeof fn == 'function'){
					fn();
				}
			}
		});
	},
	getStyle: function(url){ // 下载样式
		var style = $('link[href="'+url+'"]');
		if(style.length > 0){
			return;
		}
		
		$('head').append('<link rel="stylesheet" href="'+url+'">');
	},
	initDate: function(selector){
		var $selector = $(selector);
		if($selector.length == 0){
			return;
		}
		if(typeof $.fn.datetimepicker == 'undefined'){
			win.getStyle('/css/bootstrap-datetimepicker.min.css');
			win.getScript('/js/bootstrap-datetimepicker.min.js', function(){
				// 日历控件
				$.fn.datetimepicker.dates['zh-CN'] = {
						days: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期日"],
						daysShort: ["周日", "周一", "周二", "周三", "周四", "周五", "周六", "周日"],
						daysMin:  ["日", "一", "二", "三", "四", "五", "六", "日"],
						months: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
						monthsShort: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
						today: "今天",
						suffix: [],
						meridiem: ["上午", "下午"]
				};
				
				win.initDate($selector);
			});
			return;
		}
		
		
		$selector.each(function(i, item){
			// bootstrap 扩展的日期控件用法
			$selector.eq(i).datetimepicker({
				format : $selector.eq(i).data('format') || "yyyy-MM-dd",
				pickDate: $selector.eq(i).data('pickDate') || true,
				pickTime: $selector.eq(i).data('pickDate') || true,
				startDate: $selector.eq(i).data('startDate') ? new Date($selector.eq(i).data('startDate')) : null,
				endDate: $selector.eq(i).data('endDate') ? new Date($selector.eq(i).data('endDate')) : null, 
			    language: 'zh-CN',
			    pickDate: $selector.eq(i).data('pickDate')
			}).on('dblclick', function(){
				$(this).find('input').val('');
				return false;
			});
		});
	},
	getDate: function(add) { 
		var dd = new Date(); 
		dd.setDate(dd.getDate()+add);
		var y = dd.getFullYear(); 
		var m = dd.getMonth()+1;
		var d = dd.getDate(); 
		return y+"-"+m+"-"+d; 
	},
	getPreMonth: function(date) {  
        var arr = date.split('-');  
        var year = arr[0]; //获取当前日期的年份  
        var month = arr[1]; //获取当前日期的月份  
        var day = arr[2]; //获取当前日期的日  
        var days = new Date(year, month, 0);  
        days = days.getDate(); //获取当前日期中月的天数  
        var year2 = year;  
        var month2 = parseInt(month) - 1;  
        if (month2 == 0) {  
            year2 = parseInt(year2) - 1;  
            month2 = 12;  
        }  
        var day2 = day;  
        var days2 = new Date(year2, month2, 0);  
        days2 = days2.getDate();  
        if (day2 > days2) {  
            day2 = days2;  
        }  
        if (month2 < 10) {  
            month2 = '0' + month2;  
        }  
        var t2 = year2 + '-' + month2 + '-' + day2;  
        return t2;  
    },
    getNextMonth: function(date) {  
        var arr = date.split('-');  
        var year = arr[0]; //获取当前日期的年份  
        var month = arr[1]; //获取当前日期的月份  
        var day = arr[2]; //获取当前日期的日  
        var days = new Date(year, month, 0);  
        days = days.getDate(); //获取当前日期中的月的天数  
        var year2 = year;  
        var month2 = parseInt(month) + 1;  
        if (month2 == 13) {  
            year2 = parseInt(year2) + 1;  
            month2 = 1;  
        }  
        var day2 = day;  
        var days2 = new Date(year2, month2, 0);  
        days2 = days2.getDate();  
        if (day2 > days2) {  
            day2 = days2;  
        }  
        if (month2 < 10) {  
            month2 = '0' + month2;  
        }  
      
        var t2 = year2 + '-' + month2 + '-' + day2;  
        return t2;  
    }, 
	initRangeDate: function(selector){
		var $selector = $(selector);
		if($selector.length == 0){
			return;
		}else if(typeof moment != 'function'){
			win.getScript('//cdn.bootcss.com/moment.js/2.18.1/moment.min.js', function(){
				win.initRangeDate($selector);
			});
			win.getStyle('//cdn.bootcss.com/bootstrap-daterangepicker/2.1.25/daterangepicker.min.css');
			return;
		}else if(typeof $.fn.daterangepicker == 'undefined'){
			win.getScript('//cdn.bootcss.com/bootstrap-daterangepicker/2.1.25/daterangepicker.min.js', function(){
				win.initRangeDate($selector);
			});
			return;
		}
			
		
		var r          = moment(),
		today          = r.format('YYYY-MM-DD'),
		thisMonthEnd   = r.endOf("month").format('YYYY-MM-DD')+' 23:59:59',
		thisMonthStart = r.startOf("month").format('YYYY-MM-DD')+' 00:00:00',
		ym             = moment(),
		yesterday      = ym.add(-1,'days').format('YYYY-MM-DD'),
		prevMoment     = r.add(-1,'days'),
		prevMonthStart = prevMoment.startOf("month").format('YYYY-MM-DD')+' 00:00:00',
		prevMonthEnd   = prevMoment.endOf("month").format('YYYY-MM-DD')+' 23:59:59';
		
		$selector.each(function(i){
			var $input = $selector.eq(i);
			$input.daterangepicker({
				"autoApply": true,
			    "showDropdowns": false,
			    "timePicker": true,
			    "timePicker24Hour": true,
			    //"timePickerSeconds": true,
			    "alwaysShowCalendars": true,
			    "dateLimit": {"days": 31},
			    "ranges": {
			        "今天": [today+' 00:00:00', today+' 23:59:59'],
			        "昨天": [yesterday+' 00:00:00', yesterday+' 23:59:59'],
			        "最近七天": [moment().add(-6,'days').format('YYYY-MM-DD')+' 00:00:00', today+' 23:59:59'],
			        "最近30天": [moment().add(-29,'days').format('YYYY-MM-DD')+' 00:00:00', today+' 23:59:59'],
			        "本月": [thisMonthStart, thisMonthEnd],
			        "上个月": [prevMonthStart, prevMonthEnd],
			    },
			    "locale": {
			        "direction": "ltr",
			        "format": "YYYY-MM-DD HH:mm",
			        "separator": " - ",
			        "applyLabel": "确定",
			        "cancelLabel": "取消",
			        "fromLabel": "From",
			        "toLabel": "To",
			        "customRangeLabel": "自定义",
			        "daysOfWeek": ["日","一","二","三","四","五","六"],
			        "monthNames": ["1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月"],
			        "firstDay": 1
			    },
			    "startDate": $input.data('startDate'),
			    "endDate": $input.data('endDate'),
			    "maxDate": $input.data('maxDate'),
			    "opens": "left"
			}, function(start, end, label) {
				console.log("New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')");
			});
		});
	},
	getFormValue: function($form){
		var data = {}, name = '', value = '', serializeArray = $form.serializeArray();
		for(var i=0; i<serializeArray.length; i++){
			name = serializeArray[i].name;
			if(name.substring(0, 2) == 'js'){
				continue;
			}
			value = serializeArray[i].value;
			
			value = $.trim(value);
			if(value != '' && !isNaN(value)){
				value = parseFloat(value);
			}
			
			if(name.indexOf('[') > -1){
				var list = name.split('['), name= '';
				
				var objlist = [], index = 0;
				for(var j=0; j<list.length; j++){
					name = list[j].replace(']', '');
					index = objlist.length - 1;
					var dtype = (j < list.length-1 && list[j+1] == ']') ? [] : {};
					
					if(j == 0){
						if(!data[name]){
							data[name] = dtype;
						}
						objlist.push(data[name]);
					}else if(name == ''){
						objlist[index].push(value);
					}else{
						if(j == list.length-1){
							objlist[index][name] = value;
						}else{
							if(!objlist[index][name]){
								objlist[index][name] = dtype;
							}
							objlist.push(objlist[index][name]);
						}
					}
				}
			}else{
				data[name] = value;
			}
		}
		
		return data;
	},
	validate: function(object){// jquery.validate验证
		var $forms = $(object);
		if($forms.length == 0){
			return;
		}
		
		if(typeof $.fn.validate == 'undefined'){
			this.getScript('//cdn.bootcss.com/jquery-validate/1.15.0/jquery.validate.min.js', function(){
				win.validate($forms);
			});
			return;
		}
		
		zh_validator();
		
		$forms.each(function (i, form) {
        	var $form = $forms.eq(i);
        	$form.validate({
		        errorClass: $form.data('errorClass') == undefined ? "help-inline" : $form.data('errorClass'),
		        errorElement: "span",
		        ignore: ".ignore",
		        highlight: function (element, errorClass, validClass) {
		        	var $element = $(element),
		        		parent = '.control-group';
		        	if($element.hasClass('td-input')){
		        		parent = 'td'
		        	}
		        	
		        	$element.parents(parent+':first').addClass('error');
		        },
		        unhighlight: function (element, errorClass, validClass) {
		        	var $element = $(element),
		        		parent = '.control-group';
		        	if($element.hasClass('td-input')){
		        		parent = 'td'
		        	}
		        	
		        	$element.parents(parent+':first').removeClass('error');
		        },
		        errorPlacement: function($error, $element){
		        	$error.addClass('error-message');
		        	if($element.hasClass('td-input')){
		        		//$error.appendTo($element.parents('td:first'));
			        	return false;
		        	}
		        	
		        	if($element[0].tagName == 'SELECT' && $error.text() == '必须填写'){
		        		$error.html('必须选择');
		        	}

		        	$error.appendTo($element.parents('.controls:first'));
		        	return false;
		        },
		        invalidHandler: function(event, validator) {
					var $element = $(validator.errorList[0].element),
						data = $element.data(),
						errmsg = validator.errorList[0].message;
					if(data.label){
						errmsg = data.label + '：' + errmsg;
					}
					alertMsg(errmsg, 'error');
					$element.focus();
				},
		        submitHandler: function () {
		        	var submitData = win.getFormValue($form);
		        	var result = $form.triggerHandler('valid', [submitData]);
		        	if(typeof result == 'boolean'){
		        		return result;
		        	}
		        	
		        	var $submit = $form.find(':submit'),
		        	$actionBtn = $form.find('.form-actions button, .form-actions input[type="button"]');

	        		$submit.attr('disabled', true);
	        		$actionBtn.attr('disabled', true);
	        		
	        		$.ajax({
	        	        url: $form.attr('action'),
	        	        type: $form.attr('method'),
	        	        data: submitData,
	        	        dataType: 'json',
	        	        success: function (data, str) {
	        	        	$form.data('submited', true);
	        	        	var result = $form.triggerHandler('submited', [data, str]);
	        	            if (result == false) {
	        	            	return false;
	        	            }
	        	            
	        				var form_success = $form.data('success');

	        	            // 表单提交后操作
	        	            if (form_success == 'back') { // 返回上一页
	        	            	win.back(-1);
	        	            }else if (form_success == 'refresh') { // 刷新本页
	        	            	window.location.reload();
	        	            }else{
	        	            	var $modal = $form.hasClass('modal') ? $form : $form.children('.modal');
	        	            	if($modal.length == 0){
	        	            		$modal = $form.parents('.modal:first');
	        	            	}
	        	            	
	        	            	if($modal.length > 0){
	        	            		$modal.modal('hide');
	        	            	}
	        	            }
	        	        },
	        	        error: function (data) {
	        	        	$submit.removeAttr('disabled');
	    	        		$actionBtn.removeAttr('disabled');
	        	        }
	        	    });
		            return false;
		        }
		    });
		});
	},
	bootstrapTable: function(object){
		var $table = $(object);
		if($table.length == 0){
			return;
		}
		
		if(typeof $.fn.bootstrapTable != 'function'){
			var $win = this;
			this.getStyle('/css/bootstrap-table.min.css');
			this.getScript('//cdn.bootcss.com/bootstrap-table/1.10.1/bootstrap-table.min.js', function(){
				$win.bootstrapTable($table);
			});
			return;
		}
		
		zh_table();
		$table.bootstrapTable();
	},
	defaultEditor: null,
	ajaxFileUpload: function($append){
		var $win = this;
		if(typeof UE != 'object'){
			this.getScript('/ueditor/ueditor.config.js');
			this.getScript('/ueditor/ueditor.all.min.js', function(){
				$win.ajaxFileUpload($append);
			});
			return;
		}
	
		var editor_id = 'editor_upfile';
		var $input_url = $append.find('input[type="text"]:first');
		if($('#' + editor_id).length == 0){
			$('body').append('<script type="text/html" id="'+editor_id+'"></script>');
			
			$win.defaultEditor = UE.getEditor(editor_id,{
			    isShow: false
			});	
		}
		
		// 点击按钮上传文件
		$append.find('.btn-up').on('click', function(){
			// 监听图片上传
			$win.defaultEditor.removeListener('beforeInsertImage');
			$win.defaultEditor.addListener('beforeInsertImage', function (t, list) {
				$input_url.val(list[0]['src']);
	        	$append.find('.btn-up').data('popover').options.content = '<img src="'+list[0]['src']+'" style="width:128px; height: 128px;" />';
	        });
			
			$win.defaultEditor.getDialog("insertimage").open();
		})
		// 图片预览
		.popover({
			html: true,
			placement: 'right',
			title: false,
			content: '<img src="'+$input_url.val()+'" style="max-width:128px;" />',
			trigger: 'hover'
		});
		
		return false;
	},
	select2: function($selector){
		if($selector.length == 0){
			return;
		}
		
		if(typeof $.fn.select2 == 'undefined'){
			win.getStyle('//cdn.bootcss.com/select2/4.0.3/css/select2.min.css');
			win.getScript('//cdn.bootcss.com/select2/4.0.3/js/select2.min.js', function(){
				win.select2($selector);
			});
			
			return;
		}
		
		$selector.select2();
	}
};

function newId(length){
	if(length == undefined){
		length = 10;
	}
	var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    var str = "";
    for (var i = 0; i < length; i++) {
        str += chars.substr(Math.ceil(Math.random()*chars.length), 1);
    }
    return str;
}

function alertMsg(content, time) {
	var option = {title: false, content: '', time: 3, status: 'info'};
	if(typeof content == 'object'){
		option = $.extend(option, content);
	}else if(typeof content == "string"){
		option.content = content;
	}
	
	if(time != undefined && time != '' && !isNaN(time)){
		option.time = time;
	}else if(typeof time == 'string' && (time == 'info' || time == 'success' || time == 'error' || time == 'warning')){
		option.status = time;
	}

	var html 	= '<div id="msg_box_div" style="position:fixed;left:20%;right:20%; top: -25%;z-index:9999;text-align: center;-webkit-transition: opacity .3s linear,top .3s ease-out; -moz-transition: opacity .3s linear,top .3s ease-out;-o-transition: opacity .3s linear,top .3s ease-out;transition: opacity .3s linear,top .3s ease-out;">';
	html 		+= '	<div class="alert alert-'+option.status+'" style="display:inline-block; padding:4px 20px 4px 20px;margin: 0;">';
	if(option.title != undefined && option.title !== false && option.title != ''){
		html	+= '		<h4>'+option.title+'</h4>';
	}
	html 		+= '		'+option.content;
	html 		+= '	</div>';
	html 		+= '</div>';
	
	if(option.time > 0){
		$('#msg_box_div').remove();
	}
	
	var $msg_box = $(html);
	$msg_box.appendTo('body');
	
	setTimeout( function(){
			$msg_box.css('top', '60px');
	}, 10);
	
	if(option.time > 0){
		var timer = setTimeout(function () {
			$msg_box.remove();
	    }, option.time * 1000 + 60);
		
		$('#msg_box_div').hover(function () {
	        window.clearTimeout(timer);
	    }, function () {
	    	timer = setTimeout(function () {
	    		$msg_box.remove();
	        }, option.time * 1000 + 60);
	    });
	}
	
	return $msg_box;
}

/** 弹出确认提示框 */
function alertConfirm(_option, ok) {
	if(typeof _option == 'string'){
		_option = {content: _option};
		if(typeof ok == 'function'){
			_option.ok = ok;
		}
	}
	
	option = jQuery.extend({
		title: '提示',
		content: '',
		okValue: '确定',
		ok: function(){},
		cancelValue: '取消',
		backdrop: $('body').find('.modal-backdrop').length == 0,
		cancel: function(){}
	} , _option);
	
	var html = '';
	html += '<div class="modal modal-mini hide fade" tabindex="-1" role="dialog">';
	html += '	<div class="modal-header">';
	html += '		<button type="button" class="close" data-dismiss="modal">×</button>';
	html += '		<h3 id="myModalLabel">'+option.title+'</h3>';
	html += ' 	</div>';
	html += '  <div class="modal-body" style="text-align:center;">'+option.content+'</div>';
	html += '  <div class="modal-footer">';
	html += '    <button class="btn" data-dismiss="modal">'+option.cancelValue+'</button>';
	html += '    <button class="btn btn-primary">'+option.okValue+'</button>';
	html += '  </div>';
	html += '</div>';
	
	var visibled_modal = $('.modal:visible');
	visibled_modal.hide();
	
	var mydialog = $(html);
	mydialog.appendTo('body');
	mydialog.modal({
		backdrop: option.backdrop
	}).show();
	
	mydialog.find('button[data-dismiss="modal"]').on('click', function(){
		var go = option.cancel();
		if(go != false){
			visibled_modal.show();
		}
		setTimeout(function(){
			mydialog.remove();
		}, 600);
	});
	mydialog.find('.btn-primary').on('click', function(){
		option.ok();
		mydialog.modal('hide');
		visibled_modal.show();
	});
}

Number.prototype.bcFixed = String.prototype.bcFixed = String.prototype.toFixed = function(digits){
	if(!/\d+/.test(digits)){
		digits = 2;
	}
	var r = this.toString().split('.');
    if(r.length == 1){
    	return (r[0]*1).toFixed(digits);
    }else{
    	return ((r[0]+'.'+r[1].substr(0, digits))*1).toFixed(digits);
    }
}
Number.prototype.bcadd = String.prototype.bcadd = function(p2, n){
	var v1 = this.toString(),
		v2 = p2.toString()
		v = l1 = l2 = p = 0;
	
	try{l1 = v1.split('.')[1].length}catch(e){}
	try{l2 = v2.split('.')[1].length}catch(e){}
	
	p = Math.pow(10, Math.max(l1, l2));
	v = (v1.bcmul(p) + v2.bcmul(p)) / p;
	
	return !!n ? v.bcFixed(n) : v;
}
Number.prototype.bcsub = String.prototype.bcsub = function(p2, n){
	var v1 = this.toString(),
		v2 = p2.toString()
		v = l1 = l2 = p = 0;
	
	try{l1 = v1.split('.')[1].length}catch(e){}
	try{l2 = v2.split('.')[1].length}catch(e){}
	
	p = Math.pow(10, Math.max(l1, l2));
	v = (v1.bcmul(p) - v2.bcmul(p)) / p;
	return !!n ? v.bcFixed(n) : v;
}
Number.prototype.bcmul = String.prototype.bcmul = function(p2, n){
	var v1 = this.toString(),
	v2 = p2.toString()
	v = len = 0;

	try{len += v1.split('.')[1].length}catch(e){}
	try{len += v2.split('.')[1].length}catch(e){}
	v = Number(v1.replace('.', '')) * Number(v2.replace('.', '')) / Math.pow(10, len);
	return !!n ? v.bcFixed(n) : v;
}
Number.prototype.bcdiv = String.prototype.bcdiv = function(p2, n){
	var v1 = this.toString(),
		v2 = p2.toString()
		v = l1 = l2 = 0;

	try{l1 = v1.split('.')[1].length}catch(e){}
	try{l2 = v2.split('.')[1].length}catch(e){}
	
	 v = (Number(v1.replace('.', '')) / Number(v2.replace('.', ''))).bcmul(Math.pow(10, l2 - l1));
	 return !!n ? v.bcFixed(n) : v;
}