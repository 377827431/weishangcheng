<?php 
namespace Common\Model;

use Think\Model;
use Org\Wechat\WechatAuth;

/**
 * 获取二维码票据
 * @author lanxuebao
 *
 */
class QrcodeModel extends Model{
    protected $tableName = 'wx_qrcode';
    /* type类型常量 */
    const GOODS     = 1;
    const CUSTOMER_SERVICE = 2;
    const COUPON    = 3;
    
    /*
     * 获取商品临时二维码
     */
    public function getTicket($type, $outerId, $appid = null){
         $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=";
         if(empty($appid)){
             $appid = C('WEIXIN.appid');
         }
         
         $qrcode = $this->alias('qrcode')
             ->where("appid='".$appid."' AND outer_id='".$outerId."' AND type='".$type."'")
             ->find();
         
         if(!empty($qrcode) && NOW_TIME < $qrcode['expire_time']){
             $qrcode['url'] = $url.$qrcode['ticket'];
             return $qrcode;
         }
         
         $expire_time = WechatAuth::QR_SCENE_TIME - 300;
         $qrcode['outer_id']    = $outerId;
         $qrcode['expire_time'] = NOW_TIME + $expire_time;

         $this->startTrans();
         // 二维码场景值
         $sceneId = 0;
         if(empty($qrcode['id'])){
             // 到10万时清空数据库
             $qrcode['type']        = $type;
             $qrcode['appid']       = $appid;
             $sql = "INSERT INTO {$this->tableName} SET ";
             $sql .= "id=(SELECT next_id FROM (SELECT IF (ISNULL(MAX(id)),1,MAX(id) + 1) as next_id FROM {$this->tableName} WHERE appid = '{$appid}') as maxqr)";
             foreach ($qrcode as $field=>$value){
                 $sql .= ",`{$field}`='{$value}'";
             }
             $this->execute($sql);
             $sceneId = $this->getLastInsID();
         }else{
             $sceneId = $qrcode['id'];
         }
         
         $WechatAuth = new WechatAuth();
         $result = $WechatAuth->qrcodeCreate($sceneId, WechatAuth::QR_SCENE_TIME);
         if(empty($result) || $result['errcode'] != ''){
             $this->error = '二维码生成失败';
             $this->rollback();
             return null;
         }else{
             $qrcode['id']     = $sceneId;
             $qrcode['ticket'] = $result['ticket'];
         }
         
         $this->execute("UPDATE ".$this->tableName." SET ticket='".$qrcode['ticket']."' WHERE id=".$qrcode['id']);
         $this->commit();
         
         $qrcode['url'] = $url.$qrcode['ticket'];
         return $qrcode;
    }

    /**
     * 生成店铺关注二维码 2017/9/14
     */
    public function create_code_guanzhu($shopId = '', $mid = ''){
        $model = M('shop');
        $shop = $model
            ->alias('s')
            ->field('s.id, s.name, s.logo, si.shop_sign')
            ->join('shop_info as si on s.id = si.id')
            ->where("s.id = {$shopId}")
            ->find();
        if (empty($shop)){
            return;
        }
        $projectId = substr($shopId, 0, -3);
        // 1000人为一个文件夹
        $folderStep = 1000;
        $num = bcdiv($projectId-1, $folderStep)+1;
        $folder = ($num*$folderStep - $folderStep+1).'-'.$num*$folderStep;

        vendor("phpqrcode.phpqrcode");
        $project = M('project_appid')->field("alias, appid")->where(array("id" => $projectId))->find();
        if (!empty($mid)){
            $share_mid = $mid;
        }else{
            $share_mid = 0;
        }
        $data = 'qrcode_'.$shopId.'1688wsc_'.'0'.'1688wsc_'.$share_mid;
        $config = get_wx_config($project['appid']);
        $wechatAuth = new \Org\Wechat\WechatAuth($config['third_appid'], $config['appid']);
        $result = $wechatAuth->qrcodeCreate($data);
        if (!empty($result['url'])){
            $data = $result['url'];
        }else{
            return;
        }

        // 纠错级别：L、M、Q、H
        $level = 'Q';
        $size = 4;
        ob_start();
        \QRcode::png($data, false, $level, $size);
        $qrcodeImgString = base64_encode(ob_get_contents());
        ob_end_clean();
        // 下载店铺头像
        $last_str = $this->get_last($shop['logo']);
        $shopLogoFile = $_SERVER['DOCUMENT_ROOT'].'/img/temp/shop_logo/'.$folder.'/'.$projectId.$last_str;
        $logoimgurl = $shop['logo'];
        $logoimgurl = str_replace('https://', 'http://', $logoimgurl);
        \Org\Net\Http::curlDownload($logoimgurl, $shopLogoFile);
        if(!@is_file($shopLogoFile) || getimagesize($shopLogoFile) == 0){
            $shopLogoFile = $_SERVER['DOCUMENT_ROOT'].'/img/logo_108.png';
        }
        $shop['shopLogoFile'] = $shopLogoFile;

        $last_str = $this->get_last($shop['shop_sign']);
        $shopSignFile = $_SERVER['DOCUMENT_ROOT'].'/img/temp/shop_sign/'.$folder.'/'.$projectId.$last_str;
        $signimgurl = $shop['shop_sign'];
        $signimgurl = str_replace('https://', 'http://', $signimgurl);
        \Org\Net\Http::curlDownload($signimgurl, $shopSignFile);
        if(!@is_file($shopSignFile) || getimagesize($shopSignFile) == 0){
            $shopSignFile = $_SERVER['DOCUMENT_ROOT'].'/img/mall/shop_header.jpg';
        }
        $shopSignFile = $_SERVER['DOCUMENT_ROOT'].'/img/mall/dp_new2.png';
        $shop['shopSignFile'] = $shopSignFile;

        $shop['logo_108'] = $_SERVER['DOCUMENT_ROOT'].'/img/open_108.png';

        $shop['longTouchFile'] = $_SERVER['DOCUMENT_ROOT'].'/img/mall/long_touch_btn_line.jpg';

        // 生成推送图片-PHP画布
        if (empty($mid)){
            $data = $this->createRecommendImg($shop, $qrcodeImgString, $folder, 1);
        }else{
            $data = $this->createRecommendImg($shop, $qrcodeImgString, $folder);
        }

        $result = array('link' => $data['url'].'?modify='.NOW_TIME);

        return $result;
    }

    /**
     * 生成店铺二维码 h5index
     */
    public function create_code($shopId = '', $mid = ''){
        $model = M('shop');
        $shop = $model
            ->alias('s')
            ->field('s.id, s.name, s.logo, si.shop_sign')
            ->join('shop_info as si on s.id = si.id')
            ->where("s.id = {$shopId}")
            ->find();
        if (empty($shop)){
            return;
        }
        $projectId = substr($shopId, 0, -3);
        // 1000人为一个文件夹
        $folderStep = 1000;
        $num = bcdiv($projectId-1, $folderStep)+1;
        $folder = ($num*$folderStep - $folderStep+1).'-'.$num*$folderStep;

        vendor("phpqrcode.phpqrcode");
        $project = M('project')->field("host, alias")->where(array("id" => $projectId))->find();
        $data = "{$project['host']}/{$project['alias']}";
        if (!empty($mid)){
            $data .= $mid;
        }
        // 纠错级别：L、M、Q、H
        $level = 'Q';
        $size = 4;
        ob_start();
        \QRcode::png($data, false, $level, $size);
        $qrcodeImgString = base64_encode(ob_get_contents());
        ob_end_clean();
        // 下载店铺头像
        $last_str = $this->get_last($shop['logo']);
        $shopLogoFile = $_SERVER['DOCUMENT_ROOT'].'/img/temp/shop_logo/'.$folder.'/'.$projectId.$last_str;
        $logoimgurl = $shop['logo'];
        $logoimgurl = str_replace('https://', 'http://', $logoimgurl);
        \Org\Net\Http::curlDownload($logoimgurl, $shopLogoFile);
        if(!@is_file($shopLogoFile) || getimagesize($shopLogoFile) == 0){
            $shopLogoFile = $_SERVER['DOCUMENT_ROOT'].'/img/logo_108.png';
        }
        $shop['shopLogoFile'] = $shopLogoFile;

        $last_str = $this->get_last($shop['shop_sign']);
        $shopSignFile = $_SERVER['DOCUMENT_ROOT'].'/img/temp/shop_sign/'.$folder.'/'.$projectId.$last_str;
        $signimgurl = $shop['shop_sign'];
        $signimgurl = str_replace('https://', 'http://', $signimgurl);
        \Org\Net\Http::curlDownload($signimgurl, $shopSignFile);
        if(!@is_file($shopSignFile) || getimagesize($shopSignFile) == 0){
            $shopSignFile = $_SERVER['DOCUMENT_ROOT'].'/img/mall/shop_header.jpg';
        }
        $shopSignFile = $_SERVER['DOCUMENT_ROOT'].'/img/mall/dp_new2.png';
        $shop['shopSignFile'] = $shopSignFile;

        $shop['logo_108'] = $_SERVER['DOCUMENT_ROOT'].'/img/open_108.png';

        $shop['longTouchFile'] = $_SERVER['DOCUMENT_ROOT'].'/img/mall/long_touch_btn_line.jpg';

        // 生成推送图片-PHP画布
        if (empty($mid)){
            $data = $this->createRecommendImg($shop, $qrcodeImgString, $folder, 1);
        }else{
            $data = $this->createRecommendImg($shop, $qrcodeImgString, $folder);
        }

        $result = array('link' => $data['url'].'?modify='.NOW_TIME);

        return $result;
    }

    /**
     * 生成商品二维码
     */
    public function create_goods_code($shopId = '', $mid = '', $id,$price){
        if ($shopId == '' && APP_NAME != 'seller'){
            $project = get_project(APP_NAME);
            $shopId = $project['id'].'001';
        }

        $model = M('mall_goods');
        $shop = $model
            ->alias('s')
            ->field('s.id, s.title as title, s.pic_url, shop.logo, shop.name as name')
            ->join('shop on s.shop_id = shop.id')
            ->where("s.shop_id = {$shopId} AND s.id = {$id}")
            ->find();
        if (empty($shop)){
            return;
        }
        $shop['shop_sign'] = $shop['pic_url'];

        $projectId = substr($shopId, 0, -3);
        // 1000人为一个文件夹
        $folderStep = 1000;
        $num = bcdiv($projectId-1, $folderStep)+1;
        $folder = ($num*$folderStep - $folderStep+1).'-'.$num*$folderStep;

        vendor("phpqrcode.phpqrcode");
        $project = get_project($projectId);
        $data = "{$project['host']}/{$project['alias']}";
        if ($mid != ''){
            $data .= $mid;
        }
        // 纠错级别：L、M、Q、H
        $level = 'M';
        $size = 2;
        ob_start();
        \QRcode::png($data, false, $level, $size);
        $qrcodeImgString = base64_encode(ob_get_contents());
        ob_end_clean();
        // 下载店铺头像
        $last_str = $this->get_last($shop['logo']);
        $shopLogoFile = $_SERVER['DOCUMENT_ROOT'].'/img/temp/shop_logo/'.$folder.'/'.$projectId.$last_str;
        $logoimgurl = $shop['logo'];
        \Org\Net\Http::curlDownload($logoimgurl, $shopLogoFile);
        if(!@is_file($shopLogoFile) || getimagesize($shopLogoFile) == 0){
            $shopLogoFile = $_SERVER['DOCUMENT_ROOT'].'/img/logo_108.png';
        }
        $shop['shopLogoFile'] = $shopLogoFile;

        $last_str = $this->get_last($shop['shop_sign']);
        $shopSignFile = $_SERVER['DOCUMENT_ROOT'].'/img/temp/shop_sign/'.$folder.'/'.$projectId.$last_str;
        $signimgurl = $shop['shop_sign'];
        \Org\Net\Http::curlDownload($signimgurl, $shopSignFile);
        if(!@is_file($shopSignFile) || getimagesize($shopSignFile) == 0){
            $shopSignFile = $_SERVER['DOCUMENT_ROOT'].'/img/mall/shop_header.jpg';
        }
        $shop['shopSignFile'] = $shopSignFile;
        $shop['price'] = $price;
        // $shop['longTouchFile'] = $_SERVER['DOCUMENT_ROOT'].'/img/mall/long_touch_btn_line.jpg';
        if(IS_WEIXIN){
            $shop['longTouchFile'] = $_SERVER['DOCUMENT_ROOT'].'/img/mall/long_touch_btn_new.png';
        }else{
            $shop['longTouchFile'] = $_SERVER['DOCUMENT_ROOT'].'/img/mall/long_touch_btn_new2.png';
        }

        // 生成推送图片-PHP画布
        $data = $this->createRecommendImg2($shop, $qrcodeImgString, $folder, 1);

        $result = array('link' => $data['url'].'?modify='.NOW_TIME);

        return $result;
    }

    /**
     * 店铺图片合成专用方法
     */
    private function createRecommendImg($shop, $qrcodeFile, $folder, $type = 0){
        $size = getimagesizefromstring(base64_decode($qrcodeFile));
//        $resize = $size[0]/202;
//        $resize = $size[0]/236;
        $resize = $size[0]/245;
        // 最终文件路基
        $path = '/img/temp/seller_recommend/'.$folder;
        $folder = $_SERVER['DOCUMENT_ROOT'].$path;
        if (!file_exists($folder) && !mkdir($folder, 0777, true)) {
            E('无读写权限');
        }

        $projectId = substr($shop['id'], 0, -3);
        $url = C('CDN').$path.'/'.$projectId.'.png';
        $filename = $folder.'/'.$projectId.'.png';

        if ($type == 1){
            $down = 40;
        }else{
            $down = 0;
        }

        $str = $shop['name'];
        $length = mb_strlen($str, 'utf-8');

        $title_str = $shop['title'];
        $title_length = mb_strlen($title_str, 'utf-8');

//        $i = 0;
//        $dt = 0;
//        while($i < $title_length){
//            $check_name = mb_substr($title_str, 0, $i, 'utf-8');
//            $check_length = $this->get_hanzi($check_name) * 2 + $this->get_zimu($check_name);
//            if ($check_length > 30){
//                $dt = $i - 1;
//                break;
//            }
//            $i++;
//        }
//        if ($dt == 0 && $type == 1){
//            $down = $down - 28;
//        }

        // 绘制背景图
        $image = imagecreatetruecolor(551*$resize, (700 + $down)*$resize);
        $white = imagecolorallocate($image, 255, 255, 255);
        $white2 = imagecolorallocatealpha($image, 255, 255, 255, 0);
        imagefill($image, 0, 0, $white2);

        // 读取头像图片
        if($shop['shopLogoFile']){
            $shopLogo = imagecreatefrompng($shop['shopLogoFile']);
            if (!$shopLogo){
                $shopLogo = imagecreatefromjpeg($shop['shopLogoFile']);
            }
            $psizearray = getimagesize($shop['shopLogoFile']);
            imagecopyresized($image, $shopLogo, 210*$resize, 156*$resize, 0, 0, 132*$resize, 132*$resize, $psizearray[0], $psizearray[1]);
            imagedestroy($shopLogo);
        }
        //212 158
//        中间方形图片
        if($shop['shopSignFile']){
            $shopSign = imagecreatefrompng($shop['shopSignFile']);
            if (!$shopSign){
                $shopSign = imagecreatefromjpeg($shop['shopSignFile']);
            }
            $psizearray = getimagesize($shop['shopSignFile']);
            imagecopyresized($image, $shopSign, 0*$resize, 0*$resize, 0, 0, 551*$resize, 700*$resize, $psizearray[0], $psizearray[1]);
            imagedestroy($shopSign);
        }

        // 填充二维码
        $qsizearray= array();
        $img_qrcode     = imagecreatefromstring(base64_decode($qrcodeFile));
        $psizearray = getimagesizefromstring(base64_decode($qrcodeFile));
        imagecopyresized($image, $img_qrcode, ((551 * $resize - $psizearray[0])/2),  358*$resize, 0, 0, $psizearray[0], $psizearray[1], $psizearray[0], $psizearray[1]);
        imagedestroy($img_qrcode);
        $n1 = $psizearray[0];
        $n2 = $psizearray[1];

        //二维码中间图标
        if($shop['logo_108']){
            $shopLogo = imagecreatefrompng($shop['logo_108']);
            if (!$shopLogo){
                $shopLogo = imagecreatefromjpeg($shop['logo_108']);
            }
            $psizearray = getimagesize($shop['logo_108']);
            $wide = 60;
            imagecopyresized($image, $shopLogo, ((551 * $resize - $n1)/2) + $n1/2 - $wide*$resize/2, 358*$resize + $n2/2 - $wide*$resize/2, 0, 0, $wide*$resize, $wide*$resize, $psizearray[0], $psizearray[1]);
            imagedestroy($shopLogo);
        }

        // 文字
        $font = $_SERVER['DOCUMENT_ROOT'].'/font/msyh.ttf';
        $blackcolor = imagecolorallocate($image, 40, 40, 40);
        $gray = imagecolorallocate($image, 60, 60, 60);

 //       店铺名称
        $name = $str;
        $nameSize = imageftbbox(20*$resize, 0, $font, $name);
        imagefttext($image,20*$resize,0, (551*$resize - $nameSize[2])/2, 336*$resize, $blackcolor, $font, $name);

        if ($type == 1){
            $nameSize = imageftbbox(20*$resize, 0, $font, '此为亲的小店二维码');
            imagefttext($image,20*$resize,0, (551*$resize - $nameSize[2])/2, 636*$resize, $blackcolor, $font, '此为亲的小店二维码');
            $nameSize = imageftbbox(20*$resize, 0, $font, '使用其他APP扫码预览小店吧');
            imagefttext($image,20*$resize,0, (551*$resize - $nameSize[2])/2, (636 + 40)*$resize, $blackcolor, $font, '使用其他APP扫码预览小店吧');
        }else{
            //下面文字
            $nameSize = imageftbbox(20*$resize, 0, $font, '微信扫描或长按识别二维码进店');
            imagefttext($image,20*$resize,0, (551*$resize - $nameSize[2])/2, 636*$resize, $blackcolor, $font, '微信扫描或长按识别二维码进店');
        }

        imagesavealpha($image , true);
        // 保存图像
        imagepng($image, $filename);
        // 释放内存
        imagedestroy($image);
        return array(
            'filename' => $filename,
            'url'      => $url
        );
    }
    private function createRecommendImg2($shop, $qrcodeFile, $folder, $type = 0){
        $size = getimagesizefromstring(base64_decode($qrcodeFile));
        $resize = $size[0]/83;
        $viewWidth = 505*$resize;
        // 最终文件路基
        $path = '/img/temp/seller_recommend/'.$folder;
        $folder = $_SERVER['DOCUMENT_ROOT'].$path;
        if (!file_exists($folder) && !mkdir($folder, 0777, true)) {
            E('无读写权限');
        }

        $projectId = substr($shop['id'], 0, -3);
        $url = C('CDN').$path.'/'.$projectId.'.jpg';
        $filename = $folder.'/'.$projectId.'.jpg';

        if ($type == 1){
            $down = 70;
        }else{
            $down = 0;
        }

        $str = $shop['name'];
        $length = mb_strlen($str, 'utf-8');

        $title_str = $shop['title'];
        $title_length = mb_strlen($title_str, 'utf-8');

        $i = 0;
        $dt = 0;
        while($i < $title_length){
            $check_name = mb_substr($title_str, 0, $i, 'utf-8');
            $check_length = $this->get_hanzi($check_name) * 2 + $this->get_zimu($check_name);
            if ($check_length > 30){
                $dt = $i - 1;
                break;
            }
            $i++;
        }
        if ($dt == 0 && $type == 1){
            $down = $down - 28;
        }

        // 绘制背景图
        $image = imagecreatetruecolor($viewWidth, (723+$down)*$resize);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        // 读取头像图片
        if($shop['shopLogoFile']){
            $shopLogo = imagecreatefrompng($shop['shopLogoFile']);
            if (!$shopLogo){
                $shopLogo = imagecreatefromjpeg($shop['shopLogoFile']);
            }
            $psizearray = getimagesize($shop['shopLogoFile']);
            imagecopyresized($image, $shopLogo, 74*$resize, 20*$resize, 0, 0, 70*$resize, 70*$resize, $psizearray[0], $psizearray[1]);
            imagedestroy($shopLogo);
        }
        //截取圆形头像
        $shopLogoSize = 70*$resize;
        $shopLogoTop = 20*$resize;
        $cx = 218*$resize/2;
        $cy = $shopLogoTop + $shopLogoSize/2;       
        
        for($x = (180*$resize - $shopLogoSize)/2; $x < (218*$resize + $shopLogoSize)/2; $x++){        
            for($y = $shopLogoTop; $y < $shopLogoTop + $shopLogoSize; $y++){        
                if ((($x - $cx) * ($x - $cx) + ($y - $cy) * ($y - $cy)) > (($shopLogoSize-2) * ($shopLogoSize-2))/4){       
                    imagesetpixel($image,$x,$y,$white);
                }       
            }       
        }       


        if($shop['shopSignFile']){
            $shopSign = imagecreatefrompng($shop['shopSignFile']);
            if (!$shopSign){
                $shopSign = imagecreatefromjpeg($shop['shopSignFile']);
            }
            $psizearray = getimagesize($shop['shopSignFile']);
            imagecopyresized($image, $shopSign, 74*$resize, 100*$resize, 0, 0, 355*$resize, 355*$resize, $psizearray[0], $psizearray[1]);
            imagedestroy($shopSign);
        }
        //底部长按图片保存分享
        if($shop['longTouchFile']){
            $longTouchFile = imagecreatefrompng($shop['longTouchFile']);
            $psizearray = getimagesize($shop['longTouchFile']);
            imagecopyresized($image, $longTouchFile, 0, (648+$down)*$resize, 0, 0, $viewWidth, 80*$resize, $psizearray[0], $psizearray[1]);
            imagedestroy($longTouchFile);
        }

        // 填充二维码
        $qsizearray= array();
        $img_qrcode     = imagecreatefromstring(base64_decode($qrcodeFile));
        $psizearray = getimagesizefromstring(base64_decode($qrcodeFile));
        imagecopyresized($image, $img_qrcode, 74*$resize,  (505+$down)*$resize, 0, 0, $psizearray[0]*1.7, $psizearray[1]*1.7, $psizearray[0], $psizearray[1]);
        imagedestroy($qsizearray);

        // 文字
        $font = $_SERVER['DOCUMENT_ROOT'].'/font/msyh.ttf';
        $blacks = imagecolorallocate($image, 40, 40, 40);
        $blackcolor = imagecolorallocate($image, 218, 143, 62);
        $gray = imagecolorallocate($image, 228, 188, 145);
        //店铺名称
        if ($length > 8){
            $name = mb_substr($str, 0, 8, 'utf-8');
            imagefttext($image,21*$resize,0, (268-92-18)*$resize, (70-15)*$resize, $blackcolor, $font, $name);
            $name2 = mb_substr($str, 8, $length - 8, 'utf-8');
            imagefttext($image,21*$resize,0, (268-92-18)*$resize, (70+15)*$resize, $blackcolor, $font, $name2);
        }else{
            $name = $str;
            imagefttext($image,21*$resize,0, (268-92-18)*$resize, 70*$resize, $blackcolor, $font, $name);
        }
        //商品价格
        if($shop['price']){
            imagefttext($image,19*$resize,0, 74*$resize, (500+$down)*$resize, $blackcolor, $font, $shop['price']);
        }
        //长按或扫描二维码
        imagefttext($image,15*$resize,0, 222*$resize, (560+$down)*$resize, $blackcolor, $font, '长按或扫描二维码');
        if ($type == 1){
            $title = mb_substr($title_str, 0, $dt == 0 ? $title_length : $dt, 'utf-8');
            if ($dt != 0){
                $title2 = mb_substr($title_str, $dt, $title_length - $dt, 'utf-8');
            }
            //商品名称
            imagefttext($image,17*$resize,0, 74*$resize, 500*$resize, $blacks, $font, $title);
            if ($dt != 0){
                imagefttext($image,17*$resize,0, 74*$resize, (500+28)*$resize, $blacks, $font, $title2);
            }
            //查看详情
            imagefttext($image,15*$resize,0, 222*$resize, (600+$down)*$resize, $blackcolor, $font, '查看商品详情');
        }else{
            imagefttext($image,15*$resize,0, 222*$resize, 600*$resize, $blackcolor, $font, '查看店铺');
        }
        // 保存图像
        imagejpeg($image, $filename);

        // 释放内存
        imagedestroy($image);
        return array(
            'filename' => $filename,
            'url'      => $url
        );
    }

    private function get_hanzi($str){
        $title_length = mb_strlen($str, 'utf-8');
        $title_length2 = strlen($str);
        $hanzi = round(($title_length2 - $title_length)/2);
        return $hanzi;
    }

    private function get_zimu($str){
        $title_length = mb_strlen($str, 'utf-8');
        $title_length2 = strlen($str);
        $hanzi = round(($title_length2 - $title_length)/2);
        return $title_length - $hanzi;
    }

    private function get_last($str){
        $last_str = '';
        if (substr($str, -4, 4) == '.png'){
            $last_str = '.png';
        }else if (substr($str, -5, 5) == '.jpeg') {
            $last_str = '.jpeg';
        }else if (substr($str, -4, 4) == '.jpg') {
            $last_str = '.jpg';
        }
        return $last_str;
    }


    /**
     * 生成新商城二维码
     */
    public function create_new_code($shopId = '', $mid = ''){
        if ($shopId == '' && APP_NAME != 'seller'){
            $project = get_project(APP_NAME);
            $shopId = $project['id'].'001';
        }

        $model = M('shop');
        $shop = $model
            ->alias('s')
            ->field('s.id, s.name, s.logo, si.shop_sign, si.desc')
            ->join('shop_info as si on s.id = si.id')
            ->where("s.id = {$shopId}")
            ->find();
        if (empty($shop)){
            return;
        }
        $projectId = substr($shopId, 0, -3);
        // 1000人为一个文件夹
        $folderStep = 1000;
        $num = bcdiv($projectId-1, $folderStep)+1;
        $folder = ($num*$folderStep - $folderStep+1).'-'.$num*$folderStep;

        vendor("phpqrcode.phpqrcode");
        $project = get_project($projectId);
        $data = "{$project['host']}/{$project['alias']}";
        if ($mid != ''){
            $data .= $mid;
        }
        // 纠错级别：L、M、Q、H
        $level = 'H';
        $size = 10;
        ob_start();
        \QRcode::png($data, false, $level, $size);
        $qrcodeImgString = base64_encode(ob_get_contents());
        ob_end_clean();
        // 下载店铺头像
        $last_str = $this->get_last($shop['logo']);
        $shopLogoFile = $_SERVER['DOCUMENT_ROOT'].'/img/temp/shop_logo/'.$folder.'/'.$projectId.$last_str;
        $logoimgurl = $shop['logo'];
        \Org\Net\Http::curlDownload($logoimgurl, $shopLogoFile);
        if(!@is_file($shopLogoFile) || getimagesize($shopLogoFile) == 0){
            $shopLogoFile = $_SERVER['DOCUMENT_ROOT'].'/img/logo_108.png';
        }
        $shop['shopLogoFile'] = $shopLogoFile;

        $shop['longTouchFile'] = $_SERVER['DOCUMENT_ROOT'].'/img/mall/long_touch_btn_new.png';

        // 生成推送图片-PHP画布
        $data = $this->createNewImg($shop, $qrcodeImgString, $folder);

        $result = array('link' => $data['url'].'?modify='.NOW_TIME);

        return $result;
    }


    /**
     * 新商城二维码
     */
    private function createNewImg($shop, $qrcodeFile, $folder, $type = 0){
        $size = getimagesizefromstring(base64_decode($qrcodeFile));
        // qr 330
        $resize = $size[0]/83;
        $viewWidth = $size[0] * 1.488;
        $viewHeight = $size[0] * 2.297;
        $shopLogoSize = $size[0] * 0.424;
        $shopLogoTop = $size[0] * 0.0788;
        $nameSize = (int)($size[0] * 0.073 * 0.8);
        $descSize = (int)($size[0] * 0.073 * 0.8);
        $nameTop = $shopLogoTop + $shopLogoSize + $size[0] * 0.0364 + $size[0] * 0.0879;
        $qrTop = $nameTop;
        $longTouchSize = $size[0] * 0.2727;
        $longTouchTop = $size[0] * 2.0242;

        // 最终文件路基
        $path = '/img/temp/seller_recommend/'.$folder;
        $folder = $_SERVER['DOCUMENT_ROOT'].$path;
        if (!file_exists($folder) && !mkdir($folder, 0777, true)) {
            E('无读写权限');
        }

        $projectId = substr($shop['id'], 0, -3);
        $url = C('CDN').$path.'/'.$projectId.'.jpg';
        $filename = $folder.'/'.$projectId.'.jpg';

        if ($type == 1){
            $down = 70;
        }else{
            $down = 0;
        }

        $str = $shop['name'];
        $length = mb_strlen($str, 'utf-8');

        $title_str = $shop['title'];
        $title_length = mb_strlen($title_str, 'utf-8');

        $i = 0;
        $dt = 0;
        while($i < $title_length){
            $check_name = mb_substr($title_str, 0, $i, 'utf-8');
            $check_length = $this->get_hanzi($check_name) * 2 + $this->get_zimu($check_name);
            if ($check_length > 30){
                $dt = $i - 1;
                break;
            }
            $i++;
        }
        if ($dt == 0 && $type == 1){
            $down = $down - 28;
        }

        // 绘制背景图
        $image = imagecreatetruecolor($viewWidth, $viewHeight);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        // 读取头像图片
        if($shop['shopLogoFile']){
            $shopLogo = imagecreatefrompng($shop['shopLogoFile']);
            if (!$shopLogo){
                $shopLogo = imagecreatefromjpeg($shop['shopLogoFile']);
            }
            $psizearray = getimagesize($shop['shopLogoFile']);
            imagecopyresized($image, $shopLogo, ($viewWidth - $shopLogoSize)/2, $shopLogoTop, 0, 0, $shopLogoSize, $shopLogoSize, $psizearray[0], $psizearray[1]);
            imagedestroy($shopLogo);
        }

        $cx = $viewWidth/2;
        $cy = $shopLogoTop + $shopLogoSize/2;

        for($x = ($viewWidth - $shopLogoSize)/2; $x < ($viewWidth + $shopLogoSize)/2; $x++){
            for($y = $shopLogoTop; $y < $shopLogoTop + $shopLogoSize; $y++){
                if ((($x - $cx) * ($x - $cx) + ($y - $cy) * ($y - $cy)) > (($shopLogoSize-2) * ($shopLogoSize-2))/4){
                    imagesetpixel($image,$x,$y,$white);
                }
            }
        }
//        for($x = ($viewWidth - $shopLogoSize)/2; $x < ($viewWidth + $shopLogoSize)/2; $x++){
//            for($y = $shopLogoTop; $y < $shopLogoTop + $shopLogoSize; $y++){
//                if ((($x - $cx) * ($x - $cx) + ($y - $cy) * ($y - $cy)) > (($shopLogoSize-2) * ($shopLogoSize-2))/4){
//                    if ((($x - $cx) * ($x - $cx) + ($y - $cy) * ($y - $cy)) < (($shopLogoSize) * ($shopLogoSize))/4){
//                        $c = imagecolorat($image,$x,$y);
//                        print_data($c);
//                        imagesetpixel($image,$x,$y,$white);
//                    }
//                }
//            }
//        }


    // 填充二维码
        $qsizearray = array();
        $img_qrcode = imagecreatefromstring(base64_decode($qrcodeFile));
        $psizearray = getimagesizefromstring(base64_decode($qrcodeFile));
        imagecopyresized($image, $img_qrcode, ($viewWidth - $psizearray[0])/2,  $qrTop, 0, 0, $psizearray[0], $psizearray[1], $psizearray[0 ], $psizearray[1]);
        imagedestroy($img_qrcode);

        $font = $_SERVER['DOCUMENT_ROOT'].'/font/msyh.ttf';
        $blackcolor = imagecolorallocate($image, 0, 0, 0);
        $name = $str;
        $rr = imageftbbox($nameSize, 0, $font, $name);
        imagefttext($image, $nameSize,0, ($viewWidth-$rr[2])/2, $nameTop, $blackcolor, $font, $name);

        if($shop['longTouchFile']){
            $longTouchFile = imagecreatefrompng($shop['longTouchFile']);
            if (!$longTouchFile){
                $longTouchFile = imagecreatefromjpeg($shop['longTouchFile']);
            }
            $psizearray = getimagesize($shop['longTouchFile']);
            imagecopyresized($image, $longTouchFile, 0, $longTouchTop, 0, 0, $viewWidth, $longTouchSize, $psizearray[0], $psizearray[1]);
            imagedestroy($longTouchFile);
        }

        $desc = $shop['desc'];
        $desc_length = mb_strlen($desc, 'utf-8');
        $i = 0;
        $dt = $desc_length;
        while($i < $desc_length){
            $check_name = mb_substr($desc, 0, $i, 'utf-8');
            $firstbox = imageftbbox($descSize, 0, $font, $check_name);
            if ($firstbox[2] > $size[0]){
                $dt = $i;
                break;
            }
            $i++;
        }
        $descTop = $qrTop + $size[0] + $size[0] * 0.12;
        if ($dt < $desc_length){
            $desc1 = mb_substr($desc, 0, $dt, 'utf-8');
            imagefttext($image, $descSize,0, ($viewWidth-$firstbox[2])/2, $descTop, $blackcolor, $font, $desc1);
            $last = mb_substr($desc, $dt, $desc_length - $dt, 'utf-8');
            $descTop2 = $descTop + $size[0] * 0.139;
            $secondbox = imageftbbox($descSize, 0, $font, $last);
            imagefttext($image, $descSize,0, ($viewWidth-$secondbox[2])/2, $descTop2, $blackcolor, $font, $last);
        }else{
            $firstbox = imageftbbox($descSize, 0, $font, $desc);
            imagefttext($image, $descSize,0, ($viewWidth-$firstbox[2])/2, $descTop, $blackcolor, $font, $desc);
        }

        // 保存图像
        imagejpeg($image, $filename);

        // 释放内存
        imagedestroy($image);
        return array(
            'filename' => $filename,
            'url'      => $url
        );
    }
    /**
     * 生成二维码
     */
    public function create_new_code2($shopId = '', $mid = ''){
        if ($shopId == '' && APP_NAME != 'seller'){
            $project = get_project(APP_NAME);
            $shopId = $project['id'].'001';
        }

        $model = M('shop');
        $shop = $model
            ->alias('s')
            ->field('s.id, s.name, s.logo, si.shop_sign, si.desc')
            ->join('shop_info as si on s.id = si.id')
            ->where("s.id = {$shopId}")
            ->find();
        if (empty($shop)){
            return;
        }
        $projectId = substr($shopId, 0, -3);
        // 1000人为一个文件夹
        $folderStep = 1000;
        $num = bcdiv($projectId-1, $folderStep)+1;
        $folder = ($num*$folderStep - $folderStep+1).'-'.$num*$folderStep;

        vendor("phpqrcode.phpqrcode");
        $project = get_project($projectId);
        $data = "{$project['host']}";
        if ($mid != ''){
            $data .= $mid;
        }
        // 纠错级别：L、M、Q、H
        $level = 'H';
        $size = 10;
        ob_start();
        \QRcode::png($data, false, $level, $size);
        $qrcodeImgString = base64_encode(ob_get_contents());
        ob_end_clean();
        // 下载店铺头像
        $last_str = $this->get_last($shop['logo']);
        $shopLogoFile = $_SERVER['DOCUMENT_ROOT'].'/img/temp/shop_logo/'.$folder.'/'.$projectId.$last_str;
        $logoimgurl = $shop['logo'];
        \Org\Net\Http::curlDownload($logoimgurl, $shopLogoFile);
        if(!@is_file($shopLogoFile) || getimagesize($shopLogoFile) == 0){
            $shopLogoFile = $_SERVER['DOCUMENT_ROOT'].'/img/logo_108.png';
        }
        $shop['shopLogoFile'] = $shopLogoFile;

        $shop['longTouchFile'] = $_SERVER['DOCUMENT_ROOT'].'/img/mall/long_touch_btn_new.png';

        // 生成推送图片-PHP画布
        $data = $this->createNewImg2($shop, $qrcodeImgString, $folder);

        $result = array('link' => $data['url'].'?modify='.NOW_TIME);

        return $result;
    }
    private function createNewImg2($shop, $qrcodeFile, $folder, $type = 0){
        $size = getimagesizefromstring(base64_decode($qrcodeFile));
        // qr 330
        $resize = $size[0]/83;
        $viewWidth = $size[0] * 1.006;
        $viewHeight = $size[0] * 1.006;
        $nameTop = $size[0] * 0.006;
        $qrTop = $nameTop;

        // 最终文件路基
        $path = '/img/temp/seller_recommend/'.$folder;
        $folder = $_SERVER['DOCUMENT_ROOT'].$path;
        if (!file_exists($folder) && !mkdir($folder, 0777, true)) {
            E('无读写权限');
        }

        $projectId = substr($shop['id'], 0, -3);
        $url = C('CDN').$path.'/'.$projectId.'.jpg';
        $filename = $folder.'/'.$projectId.'.jpg';

        // 绘制背景图
        $image = imagecreatetruecolor($viewWidth, $viewHeight);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

    // 填充二维码
        $qsizearray = array();
        $img_qrcode = imagecreatefromstring(base64_decode($qrcodeFile));
        $psizearray = getimagesizefromstring(base64_decode($qrcodeFile));
        imagecopyresized($image, $img_qrcode, ($viewWidth - $psizearray[0])/2,  $qrTop, 0, 0, $psizearray[0], $psizearray[1], $psizearray[0 ], $psizearray[1]);
        imagedestroy($img_qrcode);
        // 保存图像
        imagejpeg($image, $filename);
        // 释放内存
        imagedestroy($image);
        return array(
            'filename' => $filename,
            'url'      => $url
        );
    }
}
?>