require(['vue','jquery'],function(Vue,$){
	//注册 全局 组件
	var img_con = new Vue({
		el:"#answer_container",
		data:{
			isSuccess:false,
			failed_reason:"该商品库存不足",
			qr_code:"img/qr_code.jpg",
			fetched:false
		},
		methods:{
			another_shop:function(){
				console.log("button_click");
			}
		},
		created:function(){
			var _self = this;
			$.ajax({
				url: 'mock/test.php',
				type: 'POST',
				dataType: 'json',
				data: {param1: ''},
				success:function(data){
					_self.isSuccess = data.isSuccess;
					_self.failed_reason = data.failed_reason;
					_self.qr_code = data.qr_code;
				},
				complete:function(){
					_self.fetched=true;
				}
			})
		}
	})
})
