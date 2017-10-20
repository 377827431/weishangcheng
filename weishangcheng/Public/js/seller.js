//分销商品管理
 var dataList;
 var listnum = 0;
define('seller/ali_goods', ["jquery", "h5/pullrefresh"], function($, pullfresh){
    var bill_list = {},
        active= 'all';
    var inner_list = function(list){
        var isshow = $('.js-list').children().hasClass('clearfix');
        if(list.length == '' && !isshow){
            return '<li style="text-align:center;">还没有记录~~~</li>';
        }
        var html = '', trade = null, order = null, url= '';
        trade = list;
        for(var i=0; i< trade.length; i++){
            html += '<li class="clearfix" data-id="'+trade[i].offerId+'">';
            html +='    <a href="javascript:;" class="check_wrap">';
            html +='        <span class="checked_icon"></span>';
            html +='    </a>';
            html +='    <a href="/seller/commodity/goods1688?id='+trade[i].offerId+'">';
            // html +='    <a href="javascript:void(0)" data-href="/seller/commodity/goods1688?id='+trade[i].offerId+'">';
            html +='<div class="a_link_id" style="display:none;">'+ trade[i].offerId +'</div>';
            html +='    <div class="img_wrap">';
            html +='        <img src="'+trade[i].imageList[0].size64x64URL+'"/>';
            html +='    </div>';
            html +='    <div class="words_wrap">';
            html +='        <div class="main_name clearfix">';
            html +='            <span class="tag_agent">代理</span>';
            html +='            <p>'+trade[i].subject+'</p>';
            html +='        </div>';
            html +='        <ul class="commodity_ul clearfix line-height24">';
            // html +='         <li>货号：<span>'+trade[i].number+'</span></li>';
            html +='            <li>库存：<span>'+trade[i].amount+'</span></li>';
            html +='            <li>销量：<span>'+trade[i].saledCount+'</span></li>';
            html +='        </ul>';
            html +='        <ul class="price_ul clearfix">';
            html +='            <li class="true_price">¥'+trade[i].price+'</li>';
            // html +='         <li>拿货价：<span>¥'+trade[i].cost+'</span></li>';
            html +='        </ul>';
            html +='    </div>';
            html +='    </a>';
            html +='</li>';
        }

        return html;
    }

    var search_title = $('.search_title').val();

    pullfresh.doRefresh({
        url: '/seller/ali',
        data: {size: 10, q:search_title},
        dataType: "json",
        cache: false,
        success: function(data, page, size){
            console.log(page, size);
            var $content = $('#all_log'+'>.js-list');
            if(page == 1){
                var html = inner_list(data);
                $content.html(html);
            }else{
                var html = inner_list(data);
                $content.append(html);
            }
            if (data.length == 0){
                return false;
            }
            return true;
        }
    });
})

,define('seller/login_modal', ["jquery"], function(){

    var tpl = '<div id="login-modal" class="login-modal">'+
        '<script src="/js/flexible.js"></script>'+
        '<div class="logo_seller tb-logo"></div>'+
        '<form id="loginForm" class="mlogin" method="post">'+
        '<div class="am-list">'+
        '<div class="am-list-item">'+
        '<i class="kphone_icon"></i>'+
        '<div class="am-list-control">'+
        '<input type="text" class="am-input-required js-username" placeholder="请输入店主手机号">'+
        '</div>'+
        '<div class="am-list-action">'+
        '<i class="am-icon-clear" style="display: none;"></i>'+
        '</div>'+
        '</div>'+
        '<div class="am-list-item ydl_valid" style="display:none">'+
        '<i class="kcheck_icon"></i>'+
        '<div class="am-list-control">'+
        '<input type="text" class="am-input-required am-input-required-checkCode js-auth-code" placeholder="验证码">'+
        '</div>'+
        '<div class="am-list-action am-list-action-msg"><i class="am-icon-clear" style="display: none;"></i></div>'+
        '<div class="am-list-button">'+
        '<span class="getCheckcode">获取验证码</span>'+
        '</div>'+
        '</div>'+
            //
        // '<div class="am-list-item" style="display:none">'+
        // '<div class="am-list-control">'+
        // '<input type="text" class="am-input-required js-shop-name" placeholder="默认店铺名称（之后可以修改）">'+
        // '</div>'+
        // '<div class="am-list-action">'+
        // '<i class="am-icon-clear" style="display: none;"></i>'+
        // '</div>'+
        // '</div>'+
            //
        '<div class="am-list-item require_password">'+
        '<i class="kpsword_icon"></i>'+
        '<div class="am-list-control">'+
        '<input type="password" class="am-input-required am-input-required-password js-password" placeholder="请输入密码">'+
        '</div>'+
        '<div class="am-list-action am-list-action-password">'+
        '<i class="am-icon-clear"></i>'+
        '</div>'+
        '</div>'+
        '<div class="am-list-item require_again_password">'+
        '<i class="kpsword_icon"></i>'+
        '<div class="am-list-control">'+
        '<input type="password" class="am-input-required am-input-required-password js_again_password" placeholder="请再次输入密码">'+
        '</div>'+
        '</div>'+
        '</div>'+
        '<div class="other-link ydl_other_link">'+
        '<div class="am-field am-footer">'+
        '<a href="javascript:toast.show(\'暂不支持网页端注册\');" class="f-left"></a>'+
        '<a href="javascript:;" id="forget" class="f-right">忘记密码</a>'+
        '</div>'+
        '</div>'+
        '<p class="ydl_ptitle"><a class="imitate_check imitate_checked" id="js_imitate_check" href="javascript:;"></a><input id="agree_procity" type="checkbox" checked/>创建小店代表您同意<a href="#" class="pop_procity">《开店服务协议》</a></p>' +
        '<div class="am-field am-fieldBottom ydl_am_field">'+
        '<button type="submit" class="am-button am-button-submit js-submit">登 录</button>'+
        '</div>'+
        '<div class="ydl_add_shop">'+
        '<div>' +
        '<div>1.</div>' +
        '<div class="ydl_plabel">请认真填写店主的手机号，以便在微信公众号端登录管理小店</div>' +
        '</div>' +
        '<div>' +
        // '<div>2.</div>' +
        // '<div class="ydl_plabel">此手机号将作为微信端小店登录账号，密码默认888，可在微信公众号端，进入“店铺管理”中修改</div>' +
        '</div>' +
        '<div>' +
        '<div>2.</div>' +
        '<div class="ydl_plabel">微信端管理小店：在微信中搜索公众号“开店”即可或保存下方二维码图片并在微信中长按识别打开。</div>' +
        '</div>' +
        '<img class="add_shop_qr" src="/img/seller/reg_qr.png">' +
        '</div>' +
        '</form>'+
        '</div>';
    var ydl_stat = 1;
    var modal = {
        redirect: '',
        appid: '',
        $modal: null,
        mobile: '',
        init: function(data){
            $('#login-modal').remove();
            $('body').append(tpl);

            if(data){
                this.redirect = data.redirect;
                this.appid = data.appid;
                this.mobile = data.mobile;
                this.aliid = data.aliid;
                this.userid = data.userid;
            }

            modal.bindEvent();

            if(win.isApp){
                window.uexOnload = function(type){
                    uexWindow.setReportKey(0,1);
                    uexWindow.onKeyPressed = function(){
                        uexWidgetOne.exit();
                    };
                }
            }
        },
        onLoginSuccess: function(){
            window.location.href = modal.redirect;
        },
        needCheckCode: false,
        bindEvent: function(){
            var $modal = modal.$modal = $('#login-modal'),
                $mobile = $modal.find('.js-username'),
                $authCode = $modal.find('.js-auth-code'),
                $shopName = $modal.find('.js-shop-name'),
                $password = $modal.find('.js-password'),
                $againPassword = $modal.find('.js_again_password'),
                $submit = $modal.find('.js-submit'),
                $authView = $authCode.parent().parent();
                $shopView = $shopName.parent().parent();
                $addShop = $modal.find('.ydl_add_shop');
                $addshopagree = $modal.find('.ydl_ptitle');
                $requirePWD = $modal.find('.require_password');
                $requireAgainPWD = $modal.find('.require_again_password');
                $forgetPWD = $modal.find('.ydl_other_link');

            $mobile.val(this.mobile);
            $mobile.on('keyup', function(){
                var mobile = this.value;
                if(!/^1[3|4|5|7|8]\d{9}$/.test(mobile)){//如果不是手机号
                    //$authView.hide();
                    //$shopView.hide();
                    // if(ydl_stat){
                    //     $requirePWD.show();
                    //     $forgetPWD.show();
                    //     $("div.ydl_am_field button").text("登　录");
                    //     $("#agree_procity").attr("checked",true);
                    //     $addShop.hide();
                    // }
                    $authView.show();
                    $shopView.show();
                    $requireAgainPWD.show();
                    $forgetPWD.hide();
                    $addShop.show();
                    $addshopagree.show();
                    $("div.ydl_am_field button").text("创建小店");
                    return false;
                }
                $.ajax({
                    url: get_url('/login/exists'),
                    type: 'post',
                    data: {mobile: mobile},
                    dataType: 'json',
                    waitting: false,
                    success: function(result){
                        modal.needCheckCode = result.code;
                        if(result.code){//注册
                            $authView.show();
                            $shopView.show();
                            $requireAgainPWD.show();
                            $forgetPWD.hide();
                            $addShop.show();
                            $addshopagree.show();
                        }else{//登录
                            $authView.hide();
                            $shopView.hide();
                            $requireAgainPWD.hide();
                            $forgetPWD.show();
                            $addShop.hide();
                            $addshopagree.hide();
                        }
                        ydl_stat = result.code;
                        $submit.html(result.btn);
                    }
                });


                return false;
            }).trigger('keyup');

            // 获取验证码
            $modal.find('.getCheckcode').on('click', function(){
                var btn = this;
                if(btn.disabled){
                    return false;
                }

                var mobile = $mobile.val();
                btn.disabled = true;
                $.ajax({
                    url: get_url('/login/code'),
                    data: {mobile: mobile},
                    type: 'post',
                    waitting: false,
                    datatype: 'json',
                    success: function(){
                        modal.daojishi(btn);
                    },
                    error: function(){
                        btn.disabled = false;
                    }
                });
                return false;
            });

            $modal.find('form').on('submit', function(){
                var data = {
                    mobile: $mobile.val(),
                    code: $authCode.val(),
                    shopName: $mobile.val(),
                    password: $password.val(),
                    againPassword: $againPassword.val(),
                    redirect: modal.redirect,
                    aliid: modal.aliid,
                    userid: modal.userid,
                };

                if(!/^1[3|4|5|7|8]\d{9}$/.test(data.mobile)){
                    return toast.show('请输入11位手机号'), false
                }

                if(modal.needCheckCode && !/^\d{6}$/.test(data.code)){
                    return toast.show('请输入验证码'), false
                }

                var password = data.password;
                if(password.length < 6 || password.length > 20){
                    return toast.show('请输入6-20位密码'), false
                }
                //再次输入密码
                var againPassword = data.againPassword;
                if(modal.needCheckCode &&password!=againPassword){
                    return toast.show('密码和确认密码不一致'), false
                }

                var pwdArray = [];
                for(var i=0; i<password.length; i++){
                    if(pwdArray.indexOf(password[i]) == -1){
                        pwdArray.push(password[i]);
                    }
                }
                if(pwdArray.length < 5){
                    return toast.show('密码过于简单'), false
                }

                modal.dologin(data, this);
                return false;
            });
        },
        dologin: function(data, btn){
            btn.disabled = true;
            if(!$("#agree_procity").is(":checked") && ydl_stat == 1){
                toast.show("请先同意用户协议");
                return false;
            }
            $.ajax({
                url: get_url('/login/auth'),
                type: 'post',
                data: data,
                dataType: 'json',
                success: function(result){
                    if(result.isurl == '1'){
                        window.location.href=result.url;
                    }else{
                        modal.loginSuccess();
                    }
                },
                complete: function(){
                    btn.disabled = false;
                }
            });
        },
        daojishi: function(btn){
            var times = 60;
            btn.innerHTML = times + '秒后重新获取';

            var timer = setInterval(function(){
                times--;
                btn.innerHTML = times + '秒后重新获取';
                if(times == 0){
                    clearInterval(timer);
                    btn.innerHTML = '重新获取校验码';
                    btn.disabled = false;
                }
            }, 1000);
        },
        wxLogin: function(){
            uexWeiXin.cbRegisterApp=function(opCode,dataType,data){
                if(data != 0){
                    alert('微信异常请联系本平台客服：register');
                    return;
                }

                // 检测是否安装微信
                uexWeiXin.isWXAppInstalled();
            }

            uexWeiXin.cbIsWXAppInstalled=function(opCode,dataType,data){
                if(data != 0){
                    alert('请先安装微信再使用本app');
                    return;
                }

                var params = {
                    scope:"snsapi_userinfo,snsapi_base",
                    state:"0902"
                };
                var data = JSON.stringify(params);
                uexWeiXin.login(data);
            };

            // 授权登录回调函数
            uexWeiXin.cbLogin = function(data){
                var result = JSON.parse(data);
                if(!result.code){
                    alert('已取消授权登录');
                    return;
                }

                $.ajax({
                    url: get_url('/login/bind'),
                    data: result,
                    type: 'post',
                    dataType: 'json',
                    success: function(list){
                        modal.setMemberList(list);

                        if(list.length == 1){
                            modal.loginSuccess();
                        }
                    }
                });
            }

            uexWeiXin.registerApp(modal.appid);
        },
        loginSuccess: function(){
            modal.$modal.remove();
            modal.onLoginSuccess();
        }
    };
    $('body').on('click', '#forget', function(){
        $('.offshelf_dialog').show();
        return false;
    })
    $('.offshelf_dialog .close_icon_btn').click(function(){
        $('.offshelf_dialog').hide();
    })
    return modal;
})

//分销商品管理
,define('seller/my_commodity_list', ["jquery", "h5/pullrefresh"], function($, pullfresh){
    var t = {
        option: {},
        getActive: function(_default){
            var key = pullfresh.info.key;
            return key ? key : _default;
        },
        doRefresh: function(data){
            t.option = $.extend({}, data);
            data.success = t.onLoadSuccess;
            pullfresh.doRefresh(data);
            dataList = data.data.status;
           
        },
        onLoadSuccess: function(list, page, size){
            var $this = this;
            setTimeout(function(){
                var html = t.getTradeHTML(list, page), $html = $(html);

                if(page == 1){
                    $this.html($html);
                }else{
                    $this.append($html);
                }

                t.bindEvent($html);
            }, 0);
            return list.length == size;
        },
        getTradeHTML: function(list, page){
            if(page == 1 && dataList == "sales" && (!list || list.length == 0)){
                $(".batch_management").hide();
                return '<div class="empty-list list-finished"style="padding-top:60px;"><div><h4>小店暂无任何商品</h4><p style="font-size:14px">赶快去采源宝选货转发到你的小店吧！</p></div></div>';
            }else if(page == 1 && (!list || list.length == 0)){
                $(".batch_management").hide();
                return '<div class="empty-list list-finished"style="padding-top:60px;"><div><h4>暂无商品信息</h4></div></div>';
            }
            if(dataList == "sales" && list.length != 0 && listnum == 0){
                $(".batch_management").show();
            }else if(dataList == "sales" && list.length == 0 && listnum == 1){
                $(".batch_management").show();
            }else if(dataList == "shelf" && list.length != 0 && listnum == 0){
                $(".batch_management").show();
            }else if(dataList == "shelf" && list.length == 0 && listnum == 1){
                $(".batch_management").show();
            }
                
             
            var html = '';
            for(var i=0; i<list.length; i++){
                var data = list[i];
                // console.log("data",data);
                if (data.stock > 999){
                    data.stock = '999+';
                }
                // if(list.length == 0){
                //     alert(1);
                // }
                if(listnum == "0"){
                    html += '<div class="goods-item clearfix" style="overflow: hidden;" data-id="'+data.id+'" data-shop="'+data.shop_id+'" data-price="'+(data.price_range ? data.price_range : data.price)+'">'
                    +     '<a href="'+data.host+'transfer?url='+data.host+'goods?id='+data.id+'" class="goods-info clearfix">'
                    +     '<span class="thumb_check" list_k="0" style="display:none"></span>'
                    +       '<div class="thumb" style="position: relative;"><img class="js-lazy" src="'+data.pic_url+'"></div>'
                    +       '<div class="detail">'
                    +          '<h3 class="goods-title">'+data.title+'</h3>'
                    +          '<p class="price"><span class="price-prefix">¥</span>'+(data.price_range ? data.price_range : data.price)+'</p>'
                    +          '<p class="detail-row clearfix">'
                    +              '<span class="left-col">销量&nbsp;&nbsp;'+data.sold+'</span>'
                    +              '<span class="right-col">利润&nbsp;&nbsp;'+(data.profit_range ? data.profit_range : data.profit)+'</span>'
                    +          '</p>'
                    +          '<p class="detail-row clearfix">'
                    +              '<span class="left-col">库存&nbsp;&nbsp;'+data.stock+'</span>'
                    // +              '<span class="right-col">佣金&nbsp;&nbsp;'+data.reward1+'+'+data.reward2+'</span>'
                    +          '</p>'
                    +       '</div>'
                    +     '</a>'
                    +     '<div class="goods-action clearfix">'
                    +       '<a  href="/seller/commodity/goods?id='+data.id+'"><i class="icon-edit"></i>编辑</a>'
                    // +       '<a href="javascript:;" class="js-salary"><i class="icon-salary"></i>佣金</a>'
                    +       '<a href="javascript:;" class="js-display" data-display="'+data.is_display+'"><i class="icon-'+(data.is_display ? 'takedown' : 'display')+'"></i>'+(data.is_display ? '下架' : '上架')+'</a>'
                    +       '<a href="javascript:;" class="js-'+(data.is_display ? 'share' : 'delete')+'"><i class="icon-'+(data.is_display ? 'share' : 'delete')+'"></i>'+(data.is_display ? '分享' : '删除')+'</a>'
                    +       '<a href="javascript:;" class="js-top"><i class="icon-top"></i>置顶</a>'
                    +     '</div>'
                    +  '</div>'
                }else{
                    if($(".thumb_check").is(":visible")){
                        if($(".batch_management_left span").is(".on_ck")){
                            html += '<div class="goods-item clearfix" style="overflow: hidden;" data-id="'+data.id+'" data-shop="'+data.shop_id+'" data-price="'+(data.price_range ? data.price_range : data.price)+'">'
                            +     '<a href="'+data.host+'transfer?url='+data.host+'goods?id='+data.id+'" class="goods-info clearfix" style="left:20px;">'
                            +     '<span class="thumb_check on_ck" list_k="1" style="display:block !important;"></span>'
                            +       '<div class="thumb" style="position: relative;"><img class="js-lazy" src="'+data.pic_url+'"></div>'
                            +       '<div class="detail">'
                            +          '<h3 class="goods-title">'+data.title+'</h3>'
                            +          '<p class="price"><span class="price-prefix">¥</span>'+(data.price_range ? data.price_range : data.price)+'</p>'
                            +          '<p class="detail-row clearfix">'
                            +              '<span class="left-col">销量&nbsp;&nbsp;'+data.sold+'</span>'
                            +              '<span class="right-col">利润&nbsp;&nbsp;'+(data.profit_range ? data.profit_range : data.profit)+'</span>'
                            +          '</p>'
                            +          '<p class="detail-row clearfix">'
                            +              '<span class="left-col">库存&nbsp;&nbsp;'+data.stock+'</span>'
                            // +              '<span class="right-col">佣金&nbsp;&nbsp;'+data.reward1+'+'+data.reward2+'</span>'
                            +          '</p>'
                            +       '</div>'
                            +     '</a>'
                            +     '<div class="goods-action clearfix">'
                            +       '<a  href="/seller/commodity/goods?id='+data.id+'"><i class="icon-edit"></i>编辑</a>'
                            // +       '<a href="javascript:;" class="js-salary"><i class="icon-salary"></i>佣金</a>'
                            +       '<a href="javascript:;" class="js-display" data-display="'+data.is_display+'"><i class="icon-'+(data.is_display ? 'takedown' : 'display')+'"></i>'+(data.is_display ? '下架' : '上架')+'</a>'
                            +       '<a href="javascript:;" class="js-'+(data.is_display ? 'share' : 'delete')+'"><i class="icon-'+(data.is_display ? 'share' : 'delete')+'"></i>'+(data.is_display ? '分享' : '删除')+'</a>'
                            +       '<a href="javascript:;" class="js-top"><i class="icon-top"></i>置顶</a>'
                            +     '</div>'
                            +  '</div>'
                        }else{
                            html += '<div class="goods-item clearfix" style="overflow: hidden;" data-id="'+data.id+'" data-shop="'+data.shop_id+'" data-price="'+(data.price_range ? data.price_range : data.price)+'">'
                            +     '<a href="'+data.host+'transfer?url='+data.host+'goods?id='+data.id+'" class="goods-info clearfix" style="left:20px;">'
                            +     '<span class="thumb_check" list_k="0" style="display:block !important;"></span>'
                            +       '<div class="thumb" style="position: relative;"><img class="js-lazy" src="'+data.pic_url+'"></div>'
                            +       '<div class="detail">'
                            +          '<h3 class="goods-title">'+data.title+'</h3>'
                            +          '<p class="price"><span class="price-prefix">¥</span>'+(data.price_range ? data.price_range : data.price)+'</p>'
                            +          '<p class="detail-row clearfix">'
                            +              '<span class="left-col">销量&nbsp;&nbsp;'+data.sold+'</span>'
                            +              '<span class="right-col">利润&nbsp;&nbsp;'+(data.profit_range ? data.profit_range : data.profit)+'</span>'
                            +          '</p>'
                            +          '<p class="detail-row clearfix">'
                            +              '<span class="left-col">库存&nbsp;&nbsp;'+data.stock+'</span>'
                            // +              '<span class="right-col">佣金&nbsp;&nbsp;'+data.reward1+'+'+data.reward2+'</span>'
                            +          '</p>'
                            +       '</div>'
                            +     '</a>'
                            +     '<div class="goods-action clearfix">'
                            +       '<a  href="/seller/commodity/goods?id='+data.id+'"><i class="icon-edit"></i>编辑</a>'
                            // +       '<a href="javascript:;" class="js-salary"><i class="icon-salary"></i>佣金</a>'
                            +       '<a href="javascript:;" class="js-display" data-display="'+data.is_display+'"><i class="icon-'+(data.is_display ? 'takedown' : 'display')+'"></i>'+(data.is_display ? '下架' : '上架')+'</a>'
                            +       '<a href="javascript:;" class="js-'+(data.is_display ? 'share' : 'delete')+'"><i class="icon-'+(data.is_display ? 'share' : 'delete')+'"></i>'+(data.is_display ? '分享' : '删除')+'</a>'
                            +       '<a href="javascript:;" class="js-top"><i class="icon-top"></i>置顶</a>'
                            +     '</div>'
                            +  '</div>'
                        }
                       
                    }else{
                        html += '<div class="goods-item clearfix" style="overflow: hidden;" data-id="'+data.id+'" data-shop="'+data.shop_id+'" data-price="'+(data.price_range ? data.price_range : data.price)+'">'
                        +     '<a href="'+data.host+'transfer?url='+data.host+'goods?id='+data.id+'" class="goods-info clearfix">'
                        +     '<span class="thumb_check" list_k="0" style="display:none !important;"></span>'
                        +       '<div class="thumb" style="position: relative;"><img class="js-lazy" src="'+data.pic_url+'"></div>'
                        +       '<div class="detail">'
                        +          '<h3 class="goods-title">'+data.title+'</h3>'
                        +          '<p class="price"><span class="price-prefix">¥</span>'+(data.price_range ? data.price_range : data.price)+'</p>'
                        +          '<p class="detail-row clearfix">'
                        +              '<span class="left-col">销量&nbsp;&nbsp;'+data.sold+'</span>'
                        +              '<span class="right-col">利润&nbsp;&nbsp;'+(data.profit_range ? data.profit_range : data.profit)+'</span>'
                        +          '</p>'
                        +          '<p class="detail-row clearfix">'
                        +              '<span class="left-col">库存&nbsp;&nbsp;'+data.stock+'</span>'
                        // +              '<span class="right-col">佣金&nbsp;&nbsp;'+data.reward1+'+'+data.reward2+'</span>'
                        +          '</p>'
                        +       '</div>'
                        +     '</a>'
                        +     '<div class="goods-action clearfix">'
                        +       '<a  href="/seller/commodity/goods?id='+data.id+'"><i class="icon-edit"></i>编辑</a>'
                        // +       '<a href="javascript:;" class="js-salary"><i class="icon-salary"></i>佣金</a>'
                        +       '<a href="javascript:;" class="js-display" data-display="'+data.is_display+'"><i class="icon-'+(data.is_display ? 'takedown' : 'display')+'"></i>'+(data.is_display ? '下架' : '上架')+'</a>'
                        +       '<a href="javascript:;" class="js-'+(data.is_display ? 'share' : 'delete')+'"><i class="icon-'+(data.is_display ? 'share' : 'delete')+'"></i>'+(data.is_display ? '分享' : '删除')+'</a>'
                        +       '<a href="javascript:;" class="js-top"><i class="icon-top"></i>置顶</a>'
                        +     '</div>'
                        +  '</div>'
                    }
                }   
            }
            listnum = 1;
            return html;
        },
        bindEvent: function($html){
            // 图片懒加载
            // requirejs(['lazyload'], function(){
            //     $html.find(".js-lazy").lazyload({
            //         placeholder : "__CDN__/img/logo_rgb.jpg",
            //         threshold : 270
            //     });
            // });

            // 上下架
            $html.on('click', '.js-display', t.display);
            // 分享
            $html.on('click', '.js-share', t.share);
            //删除
            $html.on('click', '.js-delete', t.del);
            // 置顶
            $html.on('click', '.js-top', t.setTop);
            //佣金
            $html.on('click','.js-salary', t.salary);

        },
        display: function(){
            var $this = $(this), is_display = $this.data('display');
             // if(!confirm('确定'+(is_display ? '下架' : '上架')+'吗？')){
             //     return false
             // }
             var id = $this.parents('.goods-item:first').data('id');
            $('.offshelf_dialog').data('display',is_display);
            $('.offshelf_dialog').data('id',id);
            $('.offshelf_dialog').find('.dialog_text_main').text('确认将该商品'+(is_display ? '下架' : '上架')+'吗？');
            $('.offshelf_dialog').show();


            return false;
        },
        share: function(){
            var id = $(this).parents('.goods-item:first').data('id');
            var price = $(this).parents('.goods-item:first').data('price');
            var shop_id = $(this).parents('.goods-item:first').data('shop');
            var cybType = $("#cyb").val();
            // toast.show('在此处写分享代码:商品'+id);
//          https://seller.xingyebao.com/login/goods_qr'
            $.ajax({
                url: 'https://seller.xingyebao.com/login/goods_qr',
                type: 'post',
                dataType: 'json',
                data:{id:id,price:price,shop_id:shop_id,isCYB:cybType},
                success: function(data){
                	console.log(data);
                	html = '';
                	if(data.isCYB == "true"){
                		bt_title = data.detail;
                		$(".textarea_border textarea").val(data.detail);
                		for(var i = 0; i < data.images.length; i++){
                			if(i == 0){
                				html += "<li>";
	                			html += "<img src="+data.images[i]+"><i class='delete_icon dh_on'><img class='dh_icon' src='/img/seller/dh.png'/></i>";
	                			html += "</li>";
                			}else{
                				html += "<li>";
	                			html += "<img src="+data.images[i]+"><i class='delete_icon'><img class='dh_icon' src='/img/seller/dh.png'/></i>";
	                			html += "</li>";
                			}
                			
                		}
                		$(".item_photo_container ul").append(html);
                		$(".cyb_fx_box").show();
                		return false;
                	}else{
                        $(window).on("resize",function(){
                            if(window.orientation == 0 || window.orientation == 180) {
                                var width = $(window).width()/1.5;
                                var height = $(window).height()*0.7;
                                var x = height/6;
                                $(".share_code_body").css({
                                    height:height+"px !important",
                                    top:x+'px'
                                })
                                $(".share_logo").css("height",height+"px");
                            }else {
                                var width = $(window).width()/4;
                                var height = $(window).height()*0.6;
                                var x = height/4;
                                $(".share_code_body").css({
                                    height:height+"px !important",
                                    top:x+'px'
                                })
                                $(".share_logo").css("height",height+"px");
                            }
                        }).trigger("resize");
                        $("#share_code_body").attr("style","display:flex"); 
                		$('.share_logo').attr('src', data);
	                    var a = data.split('?');
	                    $('#share_img').attr('href', a[0]);
	                    $('#share_code_body').fadeIn(300);
	                    return false;
                	}
                	
                }
            });
        },
        del:function(){
            var id = $(this).parents('.goods-item:first').data('id');
            $('.offshelf_dialog').data('display',-1);
            $('.offshelf_dialog').data('id',id);
            $('.offshelf_dialog').find('.dialog_text_main').text('确认将该商品删除吗？');
            $('.offshelf_dialog').show();
            
        },
        setTop: function(){
            var id = $(this).parents('.goods-item:first').data('id');
            $.ajax({
                url: '/seller/commodity/toggleTop',
                type: 'post',
                data: {id: id},
                dataType: 'json',
                success: function(){
                    toast.show('已置顶');
                    pullfresh.doRefresh();
                }
            });
        },
        salary:function(){
            var id = $(this).parents('.goods-item:first').data('id');
            var promoters = $('#promoters_set').val();
            if(promoters == 0){
                //未设置推广员功能 ---弹窗
                $("input.good_id_input").val(id);
                $("div.comission_unset_dailog").show();
            }
            if(promoters == 1){
                //已打开推广员功能 --- 跳页
                window.location.href = "/seller/commodity/goods_commision?id="+id;
            }


        }

    };
    $('.offshelf_dialog_footer .yes_btn').click(function(){
        var id = $('.offshelf_dialog').data('id');
        var is_display = $('.offshelf_dialog').data('display');
        if(is_display == '-1'){
            $.ajax({
                url: '/seller/commodity/delete',
                type: 'post',
                data: {id: id},
                dataType: 'json',
                success: function(data){
                    //toast.show('已删除');
                    $('.offshelf_dialog').hide();
                    $('.js-count-sales').html("出售中(" + data.sales + ")");
                    $('.js-count-sold').html("已售罄(" + data.sold + ")");
                    $('.js-count-shelf').html("已下架(" + data.shelf + ")");
                    pullfresh.doRefresh();
                }
            });
        }else{
            $.ajax({
                url: '/seller/commodity/toggleDisplay',
                type: 'post',
                data: {id: id, display: is_display ? 0 : 1},
                dataType: 'json',
                success: function(data){
                    $('.offshelf_dialog').hide();
                    $('.js-count-sales').html("出售中(" + data.sales + ")");
                    $('.js-count-sold').html("已售罄(" + data.sold + ")");
                    $('.js-count-shelf').html("已下架(" + data.shelf + ")");
                    pullfresh.doRefresh();
                }
            });
        }
    })
    $('.offshelf_dialog_footer .no_btn').click(function(){
        $('.offshelf_dialog').hide();
    })
    return t;
})

//订单管理
,define('seller/my_order_list', ["h5/pullrefresh", "jquery"], function(pullfresh){
    var bill_list = {},
        active= 'all';
    var inner_list = function(list){
        if(list.length == ''){
            return '<li class="no_more_li" style="text-align:center;padding: 10px;background-color: #fff;">没有更多记录了~~~</li>';
        }
        var html = '', trade = null, order = null, url= '';
        for(var i=0; i< list.length; i++){
            trade = list[i];
            orders = trade.orders;
            html += '<li>';
            html += '   <div class="buyer_state clearfix">';
            html +='        <span class="left">收货人:'+trade.receiver_name+'</span>';
            if(trade.isLB=='1'){
                html +='        <span class="right state_font">'+trade.status_desc+'</span>';
            }else{
                html +='        <span class="right state_font">'+trade.status_str+'</span>';
            }
            html +='    </div>';
            // html +=' <p class="order_number">订单号：'+trade.tid+'</p>';
            for(var j=0; j< orders.length; j++){
                order = orders[j];
                html +='    <div class="ware_all_box">';
                html +='        <div class="ware_box">';
                html +='            <a class="ware_thumb">';
                html +='                <img src="'+order.pic_url+'">';
                html +='            </a>';
                html +='        </div>';
                html +='        <div class="detail_box clearfix">';
                html +='            <a class="ware_link" href="/seller/order/detail?id=' + trade.tid + '">';
                html +='                <h3 class="js-ellipsis">';
                html +='                    <i>'+order.title+'</i>';
                html +='                </h3>';
                html +='                <p>'+order.sku_desc+'</p>';
                html +='            </a>';
                html +='        </div>';
                html +='        <div class="price_box">';
                html +='            <p class="new_price">¥'+order.price+'</p>';
                if (Number(order.original_price) > Number(order.price)){
                    html +='            <p class="old_price">¥'+order.original_price+'</p>';
                }
                html +='            <p class="number">×'+order.quantity+'</p>';
                html +='        </div>';
                html +='    </div>';
            }
            html +='    <div class="date_total clearfix">';
            // html +='        <span class="left">'+trade.created+'</span>';
            // html +='        <span class="total_row right"><i>¥'+trade.total_fee+'</i>共'+trade.total_quantity+'件商品 <span>合计¥'+trade.paid_fee+'</span></span>';
            if(trade.status == '1' || trade.status == '7'){
                html +='        <span class="total_row right">共'+trade.total_quantity+'件商品 <span style="color:#da8f3e;"><span style="color:#a4a4a4;">合计</span>:¥'+trade.payment+'</span>(含运费¥'+trade.total_postage+')</span>';
            }else{
                html +='        <span class="total_row right">共'+trade.total_quantity+'件商品 <span style="color:#da8f3e;"><span style="color:#a4a4a4;">合计</span>:¥'+trade.paid_fee+'</span>(含运费¥'+trade.total_postage+')</span>';
            }
            html +='    </div>';

            if(trade.status=='1'){
                html +='    <div class="deliver_goods">';
                html +='        <a href="/seller/order/cancel?id=' + trade.tid + '" class="deliver_goods_btn">关闭订单</a>';
                if(trade.isLB=='1'){
                    if(trade.is_suyuan == '1'){
                        html +='        <a href="javascript:void(0);" is_suyuan="'+trade.is_suyuan+'" trade_tid="' + trade.tid + '" class="deliver_goods_btn js-remind" style="background-color:#da8f3e;color:white;border:1px solid #da8f3e;">确认买家已付款，同步订单到采源宝</a>';
                    }else{
                        html +='        <a href="javascript:void(0);" is_suyuan="'+trade.is_suyuan+'" trade_tid="' + trade.tid + '" class="deliver_goods_btn js-remind" style="background-color:#da8f3e;color:white;border:1px solid #da8f3e;">我确认买家已付款</a>';
                    }
                }
                html +='    </div>';
            }
            if(trade.status=='25'){
                html +='    <div class="deliver_goods">';
                if(trade.isLB=='1'){
                    if(trade.is_suyuan == '1'){
                        html +='        <a href="javascript:void(0);" trade_tid="' + trade.tid + '" class="deliver_goods_btn goto_caiyuanbao" style="background-color:#da8f3e;color:white;border:1px solid #da8f3e;">去采源宝付款</a>';
                    }else{
                        html +='        <a href="/seller/order/detail?id=' + trade.tid + '" class="deliver_goods_btn">发货</a>';
                    }
                }else{
                    html +='        <a href="/seller/order/detail?id=' + trade.tid + '" class="deliver_goods_btn">发货</a>';
                }
                html +='    </div>';
            }
            if(trade.status=='3'){
                html +='    <div class="deliver_goods">';
                if(trade.isLB=='1'){
                    if(trade.is_suyuan == '1'){
                        html +='        <a style="color:#da8f3e;">已付款给供应商，等待供应商发货</a>';
                    }else{
                        html +='        <a href="/seller/order/detail?id=' + trade.tid + '" class="deliver_goods_btn">发货</a>';
                    }
                }else{
                    html +='        <a href="/seller/order/detail?id=' + trade.tid + '" class="deliver_goods_btn">发货</a>';
                }
                html +='    </div>';
            }
            if(trade.status == '6'){
                html +='    <div class="deliver_goods">';
                html +='        <a class="btn dc_fx_function" data-id="'+trade.seller_id+'" style="margin-right: 5px;">分享物流给买家</a>';
                html +='        <a class="btn btn-in-order-list js-search-express" href="/seller/order/checkLogistics?tid='+trade.tid+'" data-confirm="">查看物流</a>';
                html +='    </div>';
            }
            if(trade.status == '7'){
               if(trade.errmsg!=''){
                    html +='    <div class="deliver_goods">';
                    html +='            <a class="dc_style" style="color:#a4a4a4;">'+trade.errmsg+'</a>';
                    html +='            <a href="javascript:;" data-tid="'+trade.tid+'" class="deliver_goods_btn js-delete-trade">删除</a>';
                    html +='    </div>';
                }else{
                    html +='    <div class="deliver_goods">';
                    html +='        <a href="javascript:;" data-tid="'+trade.tid+'" class="deliver_goods_btn js-delete-trade">删除</a>';
                    html +='    </div>';
                }
            }
            html +='</li>';
        }
        html +='';
        return html;
    }

    $("body").on('click', ".js-delete-trade", function(){
        if(!confirm('删除之后无法恢复，确定继续操作吗？')){
            return false
        }
        var $this = $(this);
        var tid = $this.attr('data-tid');
        $.ajax({
            url: "/seller/order/delete?id="+tid,
            dataType: 'json',
            success: function(data){
                if (data == 1){
                    toast.show('订单已删除');
                    pullfresh.doRefresh(true);
                }else{
                    toast.show('订单错误');
                }
            }
        });
    })

    $('#order-tab-container>ul>li').on('click', function(){
        var $this = $(this),
            target = $(this).attr('href'),
            $target = $(target),
            active = $this.data('status');

        var search_title = $('.search_title').val();
        var param = $('.param').val();
        pullfresh.doRefresh({
            url: '/seller/order',
            data: {status:active,size: 20, title:search_title,param:param},
            dataType: "json",
            cache: false,
            success: function(data, page){
                var $content = $('#'+active+'_log'+'>.js-list');
                if(page == 1){
                    $('.param').val('');
                    var html = inner_list(data);
                    $content.html(html);
                }else{
                    $('.param').val('');
                    var html = inner_list(data);
                    $content.append(html);
                }
                if (data.length == 0){
                    return false;
                }
                return true;
            }

        });

        $this.addClass('active').siblings().removeClass('active'),
        $target.removeClass('hide').siblings().addClass('hide');
        return false;
    });

    $('#order-tab-container>ul>li.active').trigger('click');
})

    //我的账单
,define('seller/my_bill', ["h5/pullrefresh", "jquery"], function(pullfresh){
    var bill_list = {},
        active= 'income';
    var inner_list = function(list){
        var isshow = $('.js-list').children().children().hasClass('each_message_box');
        if(list.length == '' && !isshow){
            return '<li style="text-align:center;padding: 10px;background-color: #fff;">还没有记录~~~</li>';
        }
        var html = '', trade = null, order = null, url= '';
        for(var i=0; i< list.length; i++){
            trade = list[i];
            html += '<li>';
            html += '   <a class="each_message_box">';
            html +='        <div class="each_message_cell1">';
            // html +='         <img src="'+trade.img+'"/>';
            html += '   <div class="block-dot" style="background:' + list[i].color + '">' + list[i].short + '</div>';
            html +='        </div>';
            html +='        <div class="each_message_cell2">';
            html +='            <h4 class="each_message_title">'+trade.reason+'</h4>';
            // html +='            <p class="each_message_amount">订单金额：'+trade.money+'</p>';
            html +='            <p class="each_message_time">'+trade.created+'</p>';
            html +='        </div>';
            html +='        <div class="each_message_cell3">';
            if(trade.type==0){
                html +='            <span class="each_message_number plus">+'+trade.money+'</span>';
            }else{
                html +='            <span class="each_message_number reduce">-'+trade.money+'</span>';
            }
            html +='        </div>';
            html +='    </a>';
            html +='</li>';
            // html += '    <a href="" class="each_message_box">';
            // html += '<li class="block-item">';
            // html += '    <div class="block-left"><div>' + list[i].date + '</div><div>' + list[i].time + '</div></div>';
            // html += '    <div class="block-dot" style="background:' + list[i].color + '">' + list[i].short + '</div>';
            // html += '    <div class="block-info">';
            // html += '        <div class="block-title">' + (list[i].money > 0 ? '+' : '') + list[i].money + '</div>';
            // html += '        <div class="block-content">' + list[i].reason + '</div>';
            // html += '    </div>';
            // html += '</li>';
            // html +='    </a>';
        }
        return html;
    }

    $('#bill-tab-container>ul>li').on('click', function(){
        var $this = $(this),
            target = $(this).attr('href'),
            $target = $(target),
            active = $this.data('status');

        // pullfresh.page = 0;
        pullfresh.doRefresh({
            url: '/seller/income/bill',
            data: {status:active,size: 20},
            dataType: "json",
            cache: false,
            success: function(data, page){
                var $content = $('#'+active+'_log'+'>.js-list');
                if(page == 1){
                    var html = inner_list(data);
                    $content.html(html);
                }else{
                    var html = inner_list(data);
                    $content.append(html);
                }
                if (data.length == 0){
                    return false;
                }
                return true;
            }
        });
        // pullfresh.init({
        //     refresh: true,
        //     container: $('#'+active+'_log').children("ul"),
        //     onLoad: function(parameters){
        //         // $.ajax({
        //         //   url: '/h5/center/recordList?status='+active+'&offset=' + parameters.offset + '&size='+parameters.size,
        //         //   success: function(list){
        //         //       console.log(list);
        //         //       var $content = $('#'+active+'_log'+'>.js-list');
        //         //       if(parameters.page == 1){
        //         //           var html = inner_list(list);
        //         //           $content.html(html);
        //         //       }else{
        //         //           var html = inner_list(list);
        //         //           $content.append(html);
        //         //       }
        //
        //         //       var noMore = list.length < parameters.size;
        //         //       if(parameters.page == 1 && list.length == 0){
        //         //           noMore = '';
        //         //       }
        //         //       pullfresh.setNoMore(noMore);
        //         //   },
        //         //   error: function(){
        //         //       pullfresh.fail();
        //         //   }
        //         // });
        //
        //         console.log(parameters);
        //         list = [{
        //             "type":0,
        //             "bill_name": "商品交易",
        //             "bill_money": "19.8",
        //             "bill_time": "2017-03-25 09:46:14",
        //             "img": "img/goods_img.jpg",
        //             "number":1
        //         }];
        //         //list = [];
        //         $content = $('#'+active+'_log'+'>.js-list');
        //         if(parameters.page == 1){
        //             var html = inner_list(list);
        //             $content.html(html);
        //         }else{
        //             var html = inner_list(list);
        //             $content.append(html);
        //         }
        //         //document.body.scrollTop = 0;
        //
        //         var noMore = list.length < parameters.size;
        //         if(parameters.page == 1 && list.length == 0){
        //             noMore = '';
        //         }
        //         pullfresh.setNoMore(noMore);
        //     }
        // });
        $this.addClass('active').siblings().removeClass('active'),
            $target.removeClass('hide').siblings().addClass('hide');
        return false;
    });
    $('#bill-tab-container>ul>li.active').trigger('click');
})


,define('save_order', ["jquery","validate"], function($, validate){
    var $form = $('#entering_form');
    validate.init($form, function(data){
        $.ajax({
            url: '/h5/hpactive/createOrder',
            data: {data: data},
            dataType: 'json',
            success: function(){
                toast.show('保存成功~');
            },
            error: function(){
                toast.show('保存失败~');
            }
        });
        //win.redirect('http://www.baidu.com',2000);
        return false;
    });
})

//推广员管理列表
,define('seller/agent_list', ["h5/pullrefresh", "jquery"], function(pullfresh){
    var active= 'all';
    var agent_list = function(list){
        if(list.length == ''){
            return '<li style="text-align:center;padding: 10px;background-color: #fff;">没有记录~~~</li>';
        }
        var html = '';
        for(var i=0; i< list.length; i++){
            var agent = list[i];
            html += '<li>';
            html +='    <div class="ware_all_box" style="width:100%">';
            html +='        <div class="ware_box">';
            html +='            <a class="ware_thumb">';
            html +='                <img src="'+agent.head_img+'">';
            html +='            </a>';
            html +='        </div>';
            html +='        <div class="detail_box clearfix" style="width:calc(100% - 80px);">';
            html +='            <a class="ware_link" href="/seller/agent/detail?id=' + agent.mid + '">';
            html +='                <h3 class="js-ellipsis">';
            html +='                    <i>'+agent.name+'</i>';
            html +='                </h3>';
            html +=                 '<div style="display: flex;justify-content: space-between;flex-wrap: wrap;">'
            html +='                    <h3 class="js-ellipsis" style="width:40%;">';
            html +='                        <i class="id">'+agent.mid+'</i>';
            html +='                    </h3>';
            html +='                    <h3 class="js-ellipsis" style="width:60%;">';
            html +='                        <i class="tel">'+agent.mobile+'</i>';
            html +='                    </h3>';
            html +='                    <h3 class="js-ellipsis" style="width:40%;">';
            html +='                        <i class="inventor">'+agent.pname+'</i>';
            html +='                    </h3>';
            html +='                    <h3 class="js-ellipsis" style="width:60%;">';
            html +='                        <i class="intime">'+agent.created+'</i>';
            html +='                    </h3>';
            html +='    </div>';
            html +='            </a>';
            html +='        </div>';
            html +='    </div>';
            html +='</li>';
        }

        return html;
    }

    $('#order-tab-container>ul>li').on('click', function(){
        var $this = $(this),
            target = $(this).attr('href'),
            $target = $(target),
            active = $this.data('status');

        var search_title = $('.search_title').val();
        pullfresh.doRefresh({
            url: '/seller/agent/manager',
            data: {status:active,size: 20, title:search_title},
            dataType: "json",
            cache: false,
            success: function(data, page){
                var $content = $('#'+active+'_log'+'>.js-list');
                if(page == 1){
                    var html = agent_list(data);
                    $content.html(html);
                }else{
                    var html = agent_list(data);
                    $content.append(html);
                }
                if (data.length == 0){
                    return false;
                }
                return true;
            }
        });

        $this.addClass('active').siblings().removeClass('active'),
            $target.removeClass('hide').siblings().addClass('hide');

        return false;
    });

    $('#order-tab-container>ul>li.active').trigger('click');
})

//推广员管理列表
,define('seller/agent_review', ["h5/pullrefresh", "jquery"], function(pullfresh){
    var active= 'all';
    var agent_list = function(list){
        if(list.length == ''){
            return '<li style="text-align:center;padding: 10px;background-color: #fff;">没有记录~~~</li>';
        }
        var html = '';
        for(var i=0; i< list.length; i++){
            var agent = list[i];
            console.log(agent);
            if(agent.status == 1){
                var status = 'passed';
                var result = '通过';
            }else if(agent.status == 0){
                var status = 'passing';
                var result = '审核中';
            }else{
                var status = 'unpassed';
                var result = '未通过';
            }
            html +='<li stat="'+status+'" style="display:flex;justify-content:space-between;flex-wrap:wrap;align-items:center;">';
            html +='            <img src="'+agent.head_img+'" alt="" style="width:60px;height:60px;">';
            html +='            <div style="width:calc(100% - 65px);">';
            html +='                <p class="title">'+agent.name+'</p>';
            html +='                <div class="div-row" style="width:100%;font-size:0px;">';
            html +='                    <span class="span-cell '+status+'">'+result+'</span>'
            html +='                    <span class="span-cell tel_phone">'+agent.mobile+'</span> ';
            html +='                </div>';
            html +='                <div class="div-row" style="width:100%;font-size:0px;">';
            html +='                    <span class="span-cell inventer">'+agent.pname+'</span>';
            html +='                    <span class="span-cell create-time">'+agent.created+'</span>';
            html +='                </div>';
            html +='            </div>';
            html +='            <div class="varify_group">'
            html +='                <div class="btn_pass" data-id="'+agent.mid+'">通过</div>'
            html +='                <div class="btn_reject" data-id="'+agent.mid+'">拒绝</div>';
            html +='            </div>';
            html +='        </li>';
        }

        return html;
    }

    $('#order-tab-container>ul>li').on('click', function(){
        var $this = $(this),
            target = $(this).attr('href'),
            $target = $(target),
            active = $this.data('status');
            $("div.order-log-container").toggleClass("hide",true);
            $("#"+active+"_log").toggleClass("hide",false);
        var search_title = $('.search_title').val();
        pullfresh.doRefresh({
            url: '/seller/commision/promoters_recruit_check',
            data: {status:active,size: 20, title:search_title},
            dataType: "json",
            cache: false,
            success: function(data, page){
                var $content = $('#'+active+'_log'+'>.js-list');
                if(page == 1){
                    var html = agent_list(data);
                    $content.html(html);
                }else{
                    var html = agent_list(data);
                    $content.append(html);
                }
                if (data.length == 0){
                    return false;
                }
                return true;
            }
        });

        $this.addClass('active').siblings().removeClass('active'),
            $target.removeClass('hide').siblings().addClass('hide');

        return false;
    });

    $('#order-tab-container>ul>li.active').trigger('click');
})
