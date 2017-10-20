require(['jquery'],function($){
	$('.fetch_cash').on('click',function(){
        var from_where = $("span.tixian_ways").attr('tixian_flag');
        // if(from_where == "nohave"){
        //     return false;
        // }
        $.ajax({
            url: "/seller/income/withdrawals_info",
            type: "POST",
            success: function(data) {
                if (data['code'] == 1){
                    // $('.js-bank-name').html(data.bc_name);
                    // $('.show_bank_num').html(data.bc_no);
                    if(from_where == "bank"){
                        //默认选项，选银行
                        $('.js-bank-name').html(data.bc_name);
                        $('.show_bank_num').html(data.bc_no);
                        $("td.tixian_method").toggleClass("on",false);
                        $("td.tixian_method.bank_method").toggleClass("on",true);
                    }
                    if(from_where == "alipay" || from_where == "nohave"){
                        //选支付宝
                        $('.show_bank_num').html(data.ali_no);
                        $('span.js-bank-name').html(data.ali_name);
                        $("td.tixian_method").toggleClass("on",false);
                        $("td.tixian_method.alipay_method").toggleClass("on",true);
                    }
                    $("input.bank_card_id").val(data.bc_no);
                    $("input.bank_name").val(data.bc_name);
                    $("input.ali_name").val(data.ali_no);
                    $(".tixian_dailog").show();
                }
            }
        });
	});
})
