;(function() {
	var pageContents, andioObj;
	window.lockEffect = function(obj, pageInfo, pages, width, height) {
		audioObj = obj;
		pageContents = pageInfo;
		for(var i = 0; i< pages.length; i++) {
			var number = pages[i].num;
			if($('.finger_background', '#page' + number).length) {
				$('.finger_background', '#page' + number).remove();
			}
			$('<div class = "finger_background page_effect lock"></div>').prependTo($('#page' + number)).attr('id', 'finger_background' + number).attr('style', 'background-image: url(' + pages[i].finger.bgImage.path +');width:100%; height: 100%;');
			$('<div class = "finger_div"></div>').prependTo($('#finger_background' + number)).attr('id', 'finger_div' + number);
			//$('<img class = "finger_kuang" src = "http://makaplat.qiniudn.com/%E6%A1%86.png"></img>').appendTo($('#finger_background' + pages[i].num)).attr('id', 'finger_kuang' + pages[i].num);
			$('<img class = "finger_zw"/>').appendTo($('#finger_background' + number)).attr('id', 'finger_zw' + number).attr('src', pages[i].finger.zwImage.path);
			$('<img class = "finger_line" src = "'+ CLIENT_CDN +'yiqixiu/finger_line.png">').appendTo($('#finger_background' +pages[i].num)).attr('id', 'finger_line' + pages[i].num);
			$('<p class = "finger_info"></p>').appendTo($('#finger_background' + number)).attr('id', 'finger_info' + number);
			renderPage(eqShow, number, pageInfo);
			unlock(number);
		}
	};
	var hastouch = "ontouchstart" in window?true:false,
			tapstart = hastouch?"touchstart":"mousedown",
			tapend = hastouch?"touchend":"mouseup";

	function unlock(num) {
		$('#finger_div' + num).on(tapstart,function(){
			$('#page' + num).find('.u-arrow-bottom').attr('style', 'display: none;');
			$('#page' + num).find('.edit_wrapper').attr('style', 'display: none;');
			$('#finger_line' + num).show();
			$('#finger_info' + num).html('扫描中...');
			$('#finger_line' + num).stop(true,false).animate({top:'50%', marginTop: '-87px'},1000,function(){

				$('#finger_div' + num).unbind();
				$('#finger_info' + num).hide().html('扫描成功').fadeIn(300,function(){
					$('#finger_line' + num).fadeOut();
					$('#finger_zw' + num).fadeOut();
					$('#finger_div' + num).fadeOut();
					$('#finger_info' + num).fadeOut(200,function(){
						setTimeout(function(){
							$('#finger_background' + num).removeClass('lock').addClass('unlock');
						}, 500);
						$('#finger_background' + num).addClass('noFinger').fadeOut(500, function(){
							$('#page' + num).empty();
							renderPage(eqShow, num, pageContents);
							for(var i = 0; i < pageContents[num-1].elements.length; i++) {
								var $elem = eqShow.selectElement(pageContents[num-1].elements[i].id);
						        eqxCommon.bindTrigger($elem, pageContents[num-1].elements[i]);
						    }
						});
						$('#audio_btn').css('opacity', 1);
						if(num == 1) {
							eqShow.playVideo(audioObj);
						}
					});

				});


			});


		});
	}
})();
