/**
 * @author:yangdonglei
 * @date:2017-06-06
 * @description: 使用固定比例对传入的图片进行剪切。返回剪切后的base64图片
 * @function:
    show({
         show_canvas:"#show_canvas",  //显示图片的canvas框,图片最终会通过drawImage方法加载到canvas的正中间。
         mask_canvas:"#mask_canvas",  //显示图片剪裁框的canvas。所有的拖拽事件都加载到这个层，这个层要在最上面。
         result_canvas:"#result_canvas", //装载剪切后图片的canvas。这个canvas放在show_canvas层之下，透明度为0.
         canvas_container:".canvas_container", //三个canvas的父容器。这个容器需要给出固定宽高供子canvas继承。
         source_imgSrc:rst.base64,  //需要被剪裁的源图像src。
         mask_border:40,//定义剪切框边缘区域，剪切框在这个区域的正中线，剪切框内部mask_border/2区域以及外部mask_border/2区域均为可调大小区域，剩下的区域为剪切框的拖拽区
         radio_wh:1,    //定义剪切框的宽高比例。
         min_length:80  //限制剪切框最小边的长度，防止剪切框被拖拽得太小。
     });
     initMaskPosition() //初始化剪切框，根据源图片在图片展示canvas中的长宽，使剪切框在屏幕正中央，剪切框的最大边，是展示图片最小边的80%;
     getImageSrc() //该函数被调用的时候，会返回当前剪切框内被剪切过以后的base64数据；
     drawImage() //通过source_imgSrc的值，在显示源图像的canvas内展示出尚未被剪切的源图像,然后调用initMaskPosition()初始化剪切框；
     drawMask(x,y,width,height)  //根据左上角和长宽在剪切容器canvas内绘制剪切框。
     touchStart()  //当剪切框容器canvas被按下以后，通过点击位置，判断用户按下的区域是否为可拖拽的区域，如果是，则判断点击位置负责哪个方向的拖拽（上下左右左上右上左下右下中间）。并记录按下时的剪切框状态。
     touchMove() //如果用户按下时点击的是可拖拽的合法区域，则根据用户拖拽的方向，不停的调用drawMask重绘剪切框。

* @return:
    {
        show(param),//传入图片和剪切配置参数,展示原图，初始化剪切框
        getImageSrc() //传出剪切后的图片src;base64格式
    }

 */
define('image_cut',["jquery"],function($){
    //显示canvas
    var $cvs = null;
    var ctx = null;
    //剪裁canvas
    var $mcvs = null;
    var mctx = null;
    //剪裁结果canvas
    var $rcvs = null;
    var rctx = null;
    //容器尺寸
    var con_width = null;
    var con_height = null;
    //容器尺寸比
    var ratio_con_wh = null;
    //原图src
    var source_imgSrc = null;
    //剪裁框初始位置
    var mask_x = null;
    var mask_y = null;
    var mask_width = null;
    var mask_height = null;
    //长宽变化可拖拽区域宽度
    var mask_border = null;
    //手指点击位置
    var touch_x = null;
    var touch_y = null;
    var touch_mask_x = null;
    var touch_mask_y = null;
    var touch_mask_width = null;
    var touch_mask_height = null;
    // 是否能拖拽标识
    var can_drag = null;
    // 拖拽事件方向
    var drag_direction = null;
    // 是否限制比例剪裁
    var radio_wh = null;
    var min_length = null;

    //初始化
    var init = function(param){
        //初始化canvas
        if($(param.show_canvas).length != 0){
            $cvs = $(param.show_canvas);
        }else{
            $cvs = $("#show_canvas");
        }
        if($(param.mask_canvas).length != 0){
            $mcvs = $(param.mask_canvas);
        }else{
            $mcvs = $("#mask_canvas");
        }
        if($(param.result_canvas).length != 0){
            $rcvs = $(param.result_canvas);
        }else{
            $rcvs = $("#result_canvas");
        }
        ctx = $cvs[0].getContext("2d");
        mctx = $mcvs[0].getContext("2d");
        rctx = $rcvs[0].getContext("2d");
        //初始化canvas宽高
        if($(param.canvas_container).length != 0){
            con_width = $(param.canvas_container).width();
            con_height = $(param.canvas_container).height();
        }else{
            con_width = $(".canvas_container").width();
            con_height = $(".canvas_container").height();
        }
        $cvs[0].height = con_height;
        $cvs[0].width = con_width;
        $mcvs[0].height = con_height;
        $mcvs[0].width = con_width;
        ratio_con_wh = con_width / con_height;
        //初始化原图片路径
        if(param.source_imgSrc){
            source_imgSrc = param.source_imgSrc;
        }else{
            param.source_imgSrc = "__CDN__/img/cut_test.jpg";
        }
        //初始化剪切框原始参数
        mask_border = param.mask_border || 40;
        touch_mask_x = mask_x;
        touch_mask_y = mask_y;
        touch_mask_width = mask_width;
        touch_mask_height = mask_height;
        can_drag = false;
        radio_wh = param.radio_wh || mask_width / mask_height;
        min_length = param.min_length || 80;
        //初始化绑定事件
        $mcvs.on("touchstart",touchStart);
        $mcvs.on("touchmove",touchMove);
    }
    //载入源图片
    var drawImage = function(){
        var _img = new Image();
        _img.src = source_imgSrc;
        _img.onload = function(){
            var img_src_width = _img.width;
            var img_src_height = _img.height;
            var wh_img_radio = img_src_width/img_src_height;
            var img_draw_height = 0;
            var img_draw_width = 0;
            var img_start_x = 0;
            var img_start_y = 0;
            if(parseFloat(wh_img_radio)<parseFloat(ratio_con_wh)){
                img_draw_height = con_height;
                img_draw_width = parseInt(wh_img_radio * con_height,10);
                img_start_x = parseInt((con_width - img_draw_width)/2,10);
                img_start_y = 0;
            }else{
                img_draw_width = con_width;
                img_draw_height = parseInt(con_width / wh_img_radio,10);
                img_start_x = 0;
                img_start_y = parseInt((con_height - img_draw_height)/2,10);
            }
            ctx.drawImage(_img,0,0,img_src_width,img_src_height,img_start_x,img_start_y,img_draw_width,img_draw_height);
            //初始化剪切框，使剪切框展现出合适的大小。
            initMaskPosition({
                "width":img_draw_width,
                "height":img_draw_height
            });
        }
    }
    //初始化剪切框位置
    var initMaskPosition = function(img_size){
        var _mask_max_length = img_size.width > img_size.height?img_size.height:img_size.width;
        if(radio_wh > 1){
            mask_width = _mask_max_length * 0.8;
            mask_height = mask_width / radio_wh;
        }else{
            mask_height = _mask_max_length * 0.8;
            mask_width = mask_height * radio_wh;
        }
        mask_x = con_width / 2 - mask_width / 2;
        mask_y = con_height / 2 - mask_height / 2;
        console.log("初始剪切框位置：")
        console.log("("+mask_x+","+mask_y+")");
        console.log("("+mask_width+","+mask_height+")");
        drawMask(mask_x,mask_y,mask_width,mask_height);
    }
    //画剪切框
    var drawMask = function(start_pt_x,start_pt_y,win_width,win_height){
        mctx.clearRect(0,0,con_width,con_height);
        mctx.beginPath();
        mctx.fillStyle = "rgba(0,0,0,0.6)";
        mctx.fillRect(0,0,con_width,con_height);
        mctx.clearRect(start_pt_x,start_pt_y,win_width,win_height);
        mctx.strokeStyle = "green";
        mctx.strokeRect(start_pt_x,start_pt_y,win_width,win_height);
    }
    //剪切框事件
    var touchStart = function(e){
        var _touch = e.originalEvent.targetTouches[0];
        var _x = _touch.pageX;
        var _y = _touch.pageY;
        if(_x >= mask_x - mask_border/2 && _x <= mask_x + mask_width + mask_border/2 && _y>= mask_y - mask_border/2 && _y <= mask_y + mask_height + mask_border/2){
            //左
            if(_x <= mask_x + mask_border/2){
                if(_y <= mask_y + mask_border/2){
                    drag_direction = "topLeft";
                }else if( _y < mask_y + mask_height - mask_border/2){
                    drag_direction = "left";
                }else{
                    drag_direction = "bottomLeft";
                }
            }
            //中
            else if(_x < mask_x+mask_width - mask_border/2){
                if(_y <= mask_y + mask_border/2){
                    drag_direction = "top";
                }else if(_y < mask_y + mask_height - mask_border/2){
                    drag_direction = "move";
                }else{
                    drag_direction = "bottom";
                }
            }
            //右
            else{
                if(_y <= mask_y + mask_border/2){
                    drag_direction = "topRight";
                }else if(_y < mask_y + mask_height - mask_border/2){
                    drag_direction = "right";
                }else{
                    drag_direction = "bottomRight";
                }
            }
            can_drag = true;
            touch_x = _x;
            touch_y = _y;
            touch_mask_x = mask_x;
            touch_mask_y = mask_y;
            touch_mask_width = mask_width;
            touch_mask_height = mask_height;
        }else{
            can_drag = false;
        }
        return false;
    }
    var touchMove = function(e){
        if(can_drag){
            var _touch = e.originalEvent.targetTouches[0];
            var _x = _touch.pageX;
            var _y = _touch.pageY;
            switch(drag_direction){
                //左上移动的时候保证右下角点固定不动
                case "topLeft":{
                    //先计算出移动后应该在的位置
                    var move_x = _x - touch_x;
                    var move_y = _y - touch_y;
                    mask_x = touch_mask_x + move_x;
                    mask_y = touch_mask_y + move_y;
                    mask_width = touch_mask_x + touch_mask_width - mask_x;
                    mask_height = touch_mask_y + touch_mask_height - mask_y;
                    //防止左上截图移出屏幕
                    if(mask_width > touch_mask_width+touch_mask_x){
                        mask_width = touch_mask_width+touch_mask_x;
                        mask_x = 0;
                    }
                    if(mask_height > touch_mask_y + touch_mask_height){
                        mask_height = touch_mask_y +touch_mask_height;
                        mask_y = 0;
                    }
                    //确保调整大小的时候保持固定长宽比
                    if(mask_width/mask_height > radio_wh){
                        mask_x += mask_width - mask_height * radio_wh;
                        mask_width = mask_height * radio_wh;
                    }else{
                        mask_y += mask_height - mask_width/radio_wh;
                        mask_height = mask_width/radio_wh;
                    }
                    //确保最小图片尺寸
                    if(radio_wh > 1 && mask_height < min_length){
                        mask_height = min_length;
                        mask_width = mask_height * radio_wh;
                        mask_x = touch_mask_x + touch_mask_width - mask_width;
                        mask_y = touch_mask_y + touch_mask_height - mask_height;
                    }
                    if(radio_wh <= 1 && mask_width < min_length){
                        mask_width = min_length;
                        mask_height = mask_width / radio_wh;
                        mask_x = touch_mask_x + touch_mask_width - mask_width;
                        mask_y = touch_mask_y + touch_mask_height - mask_height;
                    }
                    break;
                };
                //向左调整大小
                case "left":{
                    //中轴线
                    var center_line = touch_mask_y + touch_mask_height / 2;
                    var move_x = _x - touch_x;
                    mask_x = touch_mask_x + move_x;
                    mask_width = touch_mask_x + touch_mask_width - mask_x;
                    //防止向左侧溢出
                    if(mask_x < 0){
                        mask_width = touch_mask_x + touch_mask_width;
                        mask_x = 0;
                    }
                    mask_height = mask_width / radio_wh;
                    //保持比例不变,检查高度是否溢出
                    if(center_line + mask_height / 2 > con_height && center_line - mask_height / 2 < 0){
                        //上下同时溢出
                        var top_overflow = -(center_line - mask_height / 2);
                        var bottom_overflow = center_line + mask_height /2 - con_height;
                        var _big_overflow = top_overflow > bottom_overflow?top_overflow:bottom_overflow;
                        mask_height = (mask_height/2 - _big_overflow) * 2;
                    }else if(center_line + mask_height /2 > con_height){
                        //向下溢出
                        var _overflow = center_line+mask_height/2 - con_height;
                        mask_height = (mask_height/2 - _overflow) * 2;
                    }else if(center_line - mask_height /2 < 0){
                        var _overflow = -(center_line - mask_height/2);
                        mask_height = (mask_height/2 - _overflow) * 2;
                        //想上溢出
                    }else{
                        //上下均不溢出
                    }
                    //检查是否缩略过小
                    if(radio_wh > 1 && mask_height < min_length){
                        mask_height = min_length;
                        mask_width = mask_height * radio_wh;
                        mask_x = touch_mask_x + touch_mask_width - mask_width;
                        mask_y = center_line - mask_height / 2;
                    }
                    if(radio_wh >= 1 && mask_width < min_length){
                        mask_width = min_length;
                        mask_height = mask_width / radio_wh;
                        mask_x = touch_mask_x + touch_mask_width - mask_width;
                        mask_y = center_line - mask_height / 2;
                    }
                    mask_y = center_line - mask_height / 2;
                    break;
                };
                case "bottomLeft":{
                    var move_x = _x - touch_x;
                    var move_y = _y - touch_y;
                    mask_x = touch_mask_x + move_x;
                    console.log(move_x);
                    mask_width = touch_mask_x + touch_mask_width - mask_x;
                    mask_height = touch_mask_height + move_y;
                    //出界判断
                    if(mask_x < 0){
                        mask_width = touch_mask_x + touch_mask_width;
                        mask_x = 0;
                    }
                    if(mask_y + mask_height > con_height){
                        mask_height = con_height - touch_mask_y;
                    }
                    //比例限制
                    if(mask_width / mask_height < radio_wh){
                        mask_height = mask_width / radio_wh;
                    }else{
                        mask_width = mask_height * radio_wh;
                    }
                    //最小长度限制
                    if(radio_wh > 1 && mask_height < min_length){
                        mask_height = min_length;
                        mask_width = mask_height * radio_wh;
                        mask_x = touch_mask_x + touch_mask_width - mask_width;
                    }
                    if(radio_wh <= 1 && mask_width < min_length){
                        mask_width = min_length;
                        mask_height = min_length / radio_wh;
                        mask_x = touch_mask_x + touch_mask_width - mask_width;
                    }
                    break;
                };
                case "top":{
                    var center_line = touch_mask_x + touch_mask_width / 2;
                    var move_y = _y - touch_y;
                    //防止向上溢出
                    mask_y = touch_mask_y + move_y;
                    mask_height = touch_mask_y + touch_mask_height - mask_y;
                    if(mask_y < 0){
                        mask_y = 0;
                        mask_height = touch_mask_y + touch_mask_height;
                    }
                    //保持固定比例
                    mask_width = mask_height * radio_wh;
                    mask_x = center_line - mask_width / 2
                    //检查宽度是否溢出
                    if(center_line - mask_width / 2 < 0){
                        //向左溢出
                        mask_width = center_line * 2;
                        mask_height = mask_width / radio_wh;
                        mask_x = 0;
                        mask_y = touch_mask_y + touch_mask_width / 2  - mask_height;
                    }
                    if(center_line + mask_width / 2 > con_width){
                        //向右溢出
                        mask_width = (con_width - center_line) * 2;
                        mask_height = mask_width / radio_wh;
                        mask_x = center_line - mask_width / 2;
                        mask_y = touch_mask_y + touch_mask_height - mask_height;
                    }
                    //限制最小边长
                    if(radio_wh > 1 && mask_height < min_length){
                        mask_height = min_length;
                        mask_width = mask_height * radio_wh;
                        mask_x = center_line - mask_width / 2;
                        mask_y = touch_mask_y + touch_mask_height - mask_height;
                    }
                    if(radio_wh <= 1 && mask_width < min_length){
                        mask_width = min_length;
                        mask_height = mask_width / radio_wh;
                        mask_x = center_line - mask_width / 2;
                        mask_y = touch_mask_y + touch_mask_height - mask_height;
                    }
                    break;
                };
                case "move":{
                    var move_x = _x - touch_x;
                    var move_y = _y - touch_y;
                    mask_x = touch_mask_x + move_x;
                    mask_y = touch_mask_y + move_y;
                    if(mask_x < 0){
                        mask_x = 0;
                    }
                    if(mask_y < 0){
                        mask_y = 0;
                    }
                    if(mask_x+mask_width>con_width){
                        mask_x -= mask_x+mask_width - con_width;
                    }
                    if(mask_y+mask_height>con_height){
                        mask_y -= mask_y+mask_height - con_height;
                    }
                    break;
                };
                case "bottom":{
                    var center_line = touch_mask_x + touch_mask_width / 2;
                    var move_y = _y - touch_y;
                    mask_height = touch_mask_height + move_y;
                    mask_width = mask_height * radio_wh;
                    mask_x = center_line - mask_width / 2;
                    //方式向下溢出
                    if(touch_mask_y + mask_height > con_height){
                        mask_height = con_height - touch_mask_y;
                        mask_width = mask_height * radio_wh;
                        mask_x = center_line - mask_width / 2;
                    }
                    //向左溢出
                    if(mask_x < 0){
                        mask_width = center_line * 2;
                        mask_height = mask_width / radio_wh;
                        mask_x = 0;
                    }
                    //向右溢出
                    if(mask_x + mask_width > con_width){
                        mask_width = (con_width - center_line)*2;
                        mask_height = mask_width / radio_wh;
                        mask_x = center_line - mask_width / 2;
                    }
                    //控制最小大小
                    if(radio_wh > 1 && mask_height < min_length){
                        mask_height = min_length;
                        mask_width = mask_height * radio_wh;
                        mask_x = center_line - mask_width / 2;
                    }
                    if(radio_wh <= 1 && mask_width < min_length){
                        mask_width = min_length;
                        mask_height = mask_width / radio_wh;
                        mask_x = center_line - mask_width / 2;
                    }
                    break;
                };
                case "topRight":{
                    var move_x = _x - touch_x;
                    var move_y = _y - touch_y;
                    mask_y = touch_mask_y + move_y;
                    mask_height = touch_mask_y + touch_mask_height - mask_y;
                    mask_width = touch_mask_width + move_x;
                    //防止溢出
                    if(mask_y < 0){
                        mask_height += mask_y;
                        mask_y = 0;
                    }
                    if(mask_x + mask_width > con_width){
                        mask_width = con_width - touch_mask_x;
                    }
                    //控制长宽比例
                    if(mask_width / mask_height < radio_wh){
                        mask_height = mask_width / radio_wh;
                        mask_y = touch_mask_y + touch_mask_height - mask_height;
                    }else{
                        mask_width = mask_height * radio_wh;
                    }
                    //控制最小边长
                    if(radio_wh > 1 && mask_height < min_length){
                        mask_height = min_length;
                        mask_width = mask_height * radio_wh;
                        mask_y = touch_mask_y + touch_mask_height - mask_height;
                    }
                    if(radio_wh <= 1 && mask_width < min_length){
                        mask_width = min_length;
                        mask_height = mask_width / radio_wh;
                        mask_y = touch_mask_y + touch_mask_height - mask_height;
                    }
                    break;
                };
                case "right":{
                    var center_line = touch_mask_y + touch_mask_height / 2;
                    var move_x = _x - touch_x;
                    mask_width = touch_mask_width + move_x;
                    mask_height = mask_width / radio_wh;
                    mask_y = center_line - mask_height / 2;
                    //防止右侧溢出
                    if(mask_x + mask_width > con_width){
                        mask_width = con_width - mask_x;
                        mask_height = mask_width / radio_wh;
                        mask_y = center_line - mask_height / 2;
                    }
                    //向上溢出
                    if(center_line - mask_height / 2 < 0){
                        mask_height = center_line * 2;
                        mask_width = mask_height * radio_wh;
                        mask_y = 0;
                    }
                    //向下溢出
                    if(center_line + mask_height / 2 > con_height){
                        mask_height = (con_height - center_line)*2;
                        mask_width = mask_height * radio_wh;
                        mask_y = center_line - mask_height / 2;
                    }
                    //限制最小边长
                    if(radio_wh > 1 && mask_height < min_length){
                        mask_height = min_length;
                        mask_width = mask_height * radio_wh;
                        mask_y = center_line - mask_height / 2;
                    }
                    if(radio_wh <= 1 && mask_width < min_length){
                        mask_width = min_length;
                        mask_height = mask_width / radio_wh;
                        mask_y = center_line - mask_height / 2;
                    }
                    break;
                };
                case "bottomRight":{
                    var move_x = _x - touch_x;
                    var move_y = _y - touch_y;
                    mask_height = touch_mask_height + move_y;
                    mask_width = touch_mask_width + move_x;
                    //剪裁超出边界
                    if(mask_x + mask_width > con_width){
                        mask_width = con_width - touch_mask_x;
                    }
                    if(mask_y + mask_height > con_height){
                        mask_height = con_height - touch_mask_y;
                    }
                    //保持剪裁长宽比例
                    if(mask_width / mask_height > radio_wh){
                        mask_width = mask_height * radio_wh;
                    }else{
                        mask_height = mask_width / radio_wh;
                    }
                    //限制剪裁最小边长
                    if(radio_wh > 1 && mask_height < min_length){
                        mask_height = min_length;
                        mask_width = mask_height * radio_wh;
                    }
                    if(radio_wh <= 1 && mask_width < min_length){
                        mask_width = min_length;
                        mask_height = mask_width / radio_wh;
                    }
                    break;
                };
                default:{
                };
            }
            drawMask(mask_x, mask_y, mask_width, mask_height);
        }
    }
    //保存事件
    var getImageSrc = function(){
        var image_data = ctx.getImageData(mask_x,mask_y,mask_width,mask_height);
        $rcvs[0].width = mask_width;
        $rcvs[0].height = mask_height;
        rctx.clearRect(0,0,mask_width,mask_height);
        rctx.putImageData(image_data,0,0);
        return $rcvs[0].toDataURL("image/png");
    }
    //组装show
    var show = function(param){
        init(param);
        drawImage();
    }
    return {
        show: show,
        getImageSrc:getImageSrc
    };
})
