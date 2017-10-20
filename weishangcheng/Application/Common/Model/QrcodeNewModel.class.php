<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/9/1
 * Time: 13:13
 */

namespace Common\Model;

use Think\Model;

class QrcodeNewModel extends Model{
    protected $tableName = 'wx_qrcode';

    /**
     * 生成商品关注带参数的二维码 2017/9/12
     */
    public function create_goods_code_guanzhu($shopId = '', $mid = '', $id, $price,$share_mid){
        if ($shopId == '' && APP_NAME != 'seller'){
            $project = get_project(APP_NAME);
            $shopId = $project['id'].'001';
        }

        $model = M('mall_goods');
        $shop = $model
            ->alias('s')
            ->field('s.id, mc.images, si.wx_no, s.title as title, s.pic_url, shop.logo, shop.name as name')
            ->join('shop on s.shop_id = shop.id')
            ->join('shop_info as si on si.id = s.shop_id')
            ->join('mall_goods_content as mc on mc.goods_id = s.id')
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
//        $data = "{$project['host']}/{$project['alias']}";
//        if ($mid != ''){
//            $data .= $mid;
//        }
//        if (!empty($share_mid)){
//            $data .= '&share_mid='.$share_mid;
//        }
        $data = 'qrcode_'.$shopId.'1688wsc_'.$id.'1688wsc_'.$share_mid;
        $wechatAuth = new \Org\Wechat\WechatAuth($project['appid']);
        $result = $wechatAuth->qrcodeCreate($data);
        if (!empty($result['url'])){
            $data = $result['url'];
        }else{
            return;
        }

        // 纠错级别：L、M、Q、H
        $level = 'H';
        $size = 3;
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
        $shop['logo'] = $shopLogoFile;

        $shop['shopLogoFile'] = $_SERVER['DOCUMENT_ROOT'].'/img/mall/shop_logo_s.png';
        $shop['logo_108'] = $_SERVER['DOCUMENT_ROOT'].'/img/open_108.png';
//        $shop['kaidian'] = $_SERVER['DOCUMENT_ROOT'].'/img/mall/kaidian.png';

//        $last_str = $this->get_last($shop['shop_sign']);
//        $shopSignFile = $_SERVER['DOCUMENT_ROOT'].'/img/temp/shop_sign/'.$folder.'/'.$projectId.$last_str;
//        $signimgurl = $shop['shop_sign'];
//        \Org\Net\Http::curlDownload($signimgurl, $shopSignFile);
//        if(!@is_file($shopSignFile) || getimagesize($shopSignFile) == 0){
//            $shopSignFile = $_SERVER['DOCUMENT_ROOT'].'/img/mall/shop_header.jpg';
//        }
//        $shop['shopSignFile'] = $shopSignFile;
        $shop['price'] = $price;
        $shop['show'] = array();
        if (!empty($shop['images'])){
            $shop['images'] = explode(',', $shop['images']);
            $imageId = 0;
            foreach ($shop['images'] as $k => $v){
                $imageId++;
                $last_str = $this->get_last($v);
                $goodsImages = $_SERVER['DOCUMENT_ROOT'].'/img/temp/shop_sign/'.$folder.'/'.$projectId.$imageId.$last_str;
                $signimgurl = $v;
                $signimgurl = str_replace('https://', 'http://', $signimgurl);
                \Org\Net\Http::curlDownload($signimgurl, $goodsImages);
                if(@is_file($goodsImages) && getimagesize($goodsImages) != 0){
                    $shop['show'][] = $goodsImages;
                    break;
                }
            }
        }
        if (empty($shop['show'])){

        }
        // 生成推送图片-PHP画布
        $data = $this->createRecommendImg2($shop, $qrcodeImgString, $folder, 1);

        $result = array('link' => $data['url'].'?modify='.NOW_TIME);

        return $result;
    }

    /**
     * 生成商品二维码H5 2017/9/1
     */
    public function create_goods_code($shopId = '', $mid = '', $id, $price,$share_mid){
        if ($shopId == '' && APP_NAME != 'seller'){
            $project = get_project(APP_NAME);
            $shopId = $project['id'].'001';
        }

        $model = M('mall_goods');
        $shop = $model
            ->alias('s')
            ->field('s.id, mc.images, si.wx_no, s.title as title, s.pic_url, shop.logo, shop.name as name')
            ->join('shop on s.shop_id = shop.id')
            ->join('shop_info as si on si.id = s.shop_id')
            ->join('mall_goods_content as mc on mc.goods_id = s.id')
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
        if (!empty($share_mid)){
            $data .= '&share_mid='.$share_mid;
        }
        // 纠错级别：L、M、Q、H
        $level = 'H';
        $size = 3;
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
        $shop['logo'] = $shopLogoFile;

        $shop['shopLogoFile'] = $_SERVER['DOCUMENT_ROOT'].'/img/mall/shop_logo_s.png';
        $shop['logo_108'] = $_SERVER['DOCUMENT_ROOT'].'/img/open_108.png';
//        $shop['kaidian'] = $_SERVER['DOCUMENT_ROOT'].'/img/mall/kaidian.png';

//        $last_str = $this->get_last($shop['shop_sign']);
//        $shopSignFile = $_SERVER['DOCUMENT_ROOT'].'/img/temp/shop_sign/'.$folder.'/'.$projectId.$last_str;
//        $signimgurl = $shop['shop_sign'];
//        \Org\Net\Http::curlDownload($signimgurl, $shopSignFile);
//        if(!@is_file($shopSignFile) || getimagesize($shopSignFile) == 0){
//            $shopSignFile = $_SERVER['DOCUMENT_ROOT'].'/img/mall/shop_header.jpg';
//        }
//        $shop['shopSignFile'] = $shopSignFile;
        $shop['price'] = $price;
        $shop['show'] = array();
        if (!empty($shop['images'])){
            $shop['images'] = explode(',', $shop['images']);
            $imageId = 0;
            foreach ($shop['images'] as $k => $v){
                $imageId++;
                $last_str = $this->get_last($v);
                $goodsImages = $_SERVER['DOCUMENT_ROOT'].'/img/temp/shop_sign/'.$folder.'/'.$projectId.$imageId.$last_str;
                $signimgurl = $v;
                $signimgurl = str_replace('https://', 'http://', $signimgurl);
                \Org\Net\Http::curlDownload($signimgurl, $goodsImages);
                if(@is_file($goodsImages) && getimagesize($goodsImages) != 0){
                    $shop['show'][] = $goodsImages;
                    break;
                }
            }
        }
        if (empty($shop['show'])){

        }
        // 生成推送图片-PHP画布
        $data = $this->createRecommendImg2($shop, $qrcodeImgString, $folder, 1);

        $result = array('link' => $data['url'].'?modify='.NOW_TIME);

        return $result;
    }

    private function createRecommendImg2($shop, $qrcodeFile, $folder, $type = 0){
        //581, 832
        $size = getimagesizefromstring(base64_decode($qrcodeFile));
        $resize = $size[0]/168;
        $viewWidth = 581;
        // 最终文件路基
        $path = '/img/temp/seller_recommend/'.$folder;
        $folder = $_SERVER['DOCUMENT_ROOT'].$path;
        if (!file_exists($folder) && !mkdir($folder, 0777, true)) {
            E('无读写权限');
        }

        $projectId = $shop['id'];
        $url = C('CDN').$path.'/'.$projectId.'.png';
        $filename = $folder.'/'.$projectId.'.png';

        $font = $_SERVER['DOCUMENT_ROOT'].'/font/msyh.ttf';

        $descSize = 17;
        $desc = $shop['title'];
        $desc_length = mb_strlen($desc, 'utf-8');
        $i = 0;
        $dt = $desc_length;
        while($i < $desc_length){
            $check_name = mb_substr($desc, 0, $i, 'utf-8');
            $firstbox = imageftbbox($descSize, 0, $font, $check_name);
            if ($firstbox[2] > (503 - 10) * $resize){
                $dt = $i;
                break;
            }
            $i++;
        }
        if ($dt < $desc_length){
            $down = 40;
        }else{
            $down = 0;
        }

        // 绘制背景图
        $image = imagecreatetruecolor($viewWidth * $resize, (832+$down)*$resize);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        //503, 503
        $imageSize = array();
        foreach ($shop['show'] as $k => $v){
            $tsize = getimagesize($shop['show']);
            if ($tsize[0] == $tsize[1]){
                $imageSize[] = $v;
            }
        }
        if (!empty($imageSize)){
//            $key = array_rand($imageSize);
            $shop['show'] = $imageSize[0];
        }else{
//            $key = array_rand($shop['show']);
            $shop['show'] = $shop['show'][0];
        }

        if($shop['show']){
            $shopSign = imagecreatefrompng($shop['show']);
            if (!$shopSign){
                $shopSign = imagecreatefromjpeg($shop['show']);
                if(!$shopSign){
                    $shopSign = imagecreatefromgif($shop['show']);
                }
            }
            $psizearray = getimagesize($shop['show']);
            if ($psizearray[0] >= $psizearray[1]){
                $high = 503 * $psizearray[1] / $psizearray[0];
                $top = (503 - $high) / 2 + 99;
                imagecopyresized($image, $shopSign, (($viewWidth - 503)/2)*$resize, $top*$resize, 0, 0, 503*$resize, $high*$resize, $psizearray[0], $psizearray[1]);
            }else{
                $width = 503 * $psizearray[0] / $psizearray[1];
                $left = (503 - $width) / 2 + ($viewWidth - 503)/2;
                imagecopyresized($image, $shopSign, $left*$resize, 99*$resize, 0, 0, $width*$resize, 503*$resize, $psizearray[0], $psizearray[1]);
            }
            imagedestroy($shopSign);
        }



        // 读取头像图片
        if($shop['logo']){
            $shopLogo = imagecreatefrompng($shop['logo']);
            if (!$shopLogo){
                $shopLogo = imagecreatefromjpeg($shop['logo']);
            }
            $psizearray = getimagesize($shop['logo']);
            imagecopyresized($image, $shopLogo, (($viewWidth - 503)/2)*$resize, 29*$resize, 0, 0, 108*$resize, 104*$resize, $psizearray[0], $psizearray[1]);
            imagedestroy($shopLogo);
        }


        // 读取头像图片s
        if($shop['shopLogoFile']){
            $shopLogo = imagecreatefrompng($shop['shopLogoFile']);
            if (!$shopLogo){
                $shopLogo = imagecreatefromjpeg($shop['shopLogoFile']);
            }
            $psizearray = getimagesize($shop['shopLogoFile']);
            imagecopyresized($image, $shopLogo, (($viewWidth - 503)/2)*$resize, 29*$resize, 0, 0, 108*$resize, 104*$resize, $psizearray[0], $psizearray[1]);
            imagedestroy($shopLogo);
        }

        // 填充二维码
        $img_qrcode     = imagecreatefromstring(base64_decode($qrcodeFile));
        $psizearray = getimagesizefromstring(base64_decode($qrcodeFile));
        imagecopyresized($image, $img_qrcode, (($viewWidth - 503)/2 - 16)*$resize,  (660 - 16 + 4 + $down)*$resize, 0, 0, $psizearray[0], $psizearray[1], $psizearray[0], $psizearray[1]);
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
            imagecopyresized($image, $shopLogo, (($viewWidth - 503)/2 - 16)*$resize + $n1/2 - 20*$resize, (660 - 16 + 4 + $down)*$resize + $n2/2 - 20*$resize, 0, 0, 40*$resize, 40*$resize, $psizearray[0], $psizearray[1]);
            imagedestroy($shopLogo);
        }

//        if($shop['kaidian']){
//            $shopLogo = imagecreatefrompng($shop['kaidian']);
//            if (!$shopLogo){
//                $shopLogo = imagecreatefromjpeg($shop['kaidian']);
//            }
//            $psizearray = getimagesize($shop['kaidian']);
//            $wide = 80;
//            imagecopyresized($image, $shopLogo, (($viewWidth - 503)/2)*$resize + 503*$resize - $wide*$resize, (794 + $down)*$resize - $wide*$resize + 11*$resize, 0, 0, $wide*$resize, $wide*$resize, $psizearray[0], $psizearray[1]);
//            imagedestroy($shopLogo);
//        }

        // 文字
        $blacks = imagecolorallocate($image, 80, 80, 80);
        $blackcolor = imagecolorallocate($image, 80, 80, 80);
        $redcolor = imagecolorallocate($image, 231, 24, 41);

        //店铺名称
        $name = $shop['name'];
        $name_length = mb_strlen($name, 'utf-8');
        $i = 0;
        $dt2 = $name_length;
        while($i < $name_length){
            $check_name = mb_substr($name, 0, $i, 'utf-8');
            $checkbox = imageftbbox($descSize, 0, $font, $check_name);
            if ($checkbox[2] > 340 * $resize){
                $dt2 = $i;
                break;
            }
            $i++;
        }
        if ($dt2 < $name_length){
            $name = mb_substr($name, 0, $dt, 'utf-8').'...';
            imagefttext($image, $descSize,0, (($viewWidth - 503)/2 + 108 + 10)*$resize, 78*$resize, $blacks, $font, $name);
        }else{
            imagefttext($image, $descSize,0, (($viewWidth - 503)/2 + 108 + 10)*$resize, 78*$resize, $blacks, $font, $name);
        }



        $descTop = 640 * $resize;
        if ($dt < $desc_length){
            $desc1 = mb_substr($desc, 0, $dt, 'utf-8');
            imagefttext($image, $descSize,0, (($viewWidth - 503)/2)*$resize, $descTop, $blacks, $font, $desc1);
            $last = mb_substr($desc, $dt, $desc_length - $dt, 'utf-8');
            $descTop2 = $descTop + 37 * $resize;
            $secondbox = imageftbbox($descSize, 0, $font, $last);

            if ($secondbox[2] > 503 * $resize){
                $last_length = mb_strlen($last, 'utf-8');
                $i = 0;
                $dt = $last_length;
                while($i < $last_length){
                    $check_name = mb_substr($last, 0, $i, 'utf-8');
                    $checkbox = imageftbbox($descSize, 0, $font, $check_name);
                    if ($checkbox[2] > (503 - 30) * $resize){
                        $dt = $i;
                        break;
                    }
                    $i++;
                }
                $last = mb_substr($last, 0, $dt, 'utf-8').'...';
                imagefttext($image, $descSize,0, (($viewWidth - 503)/2)*$resize, $descTop2, $blacks, $font, $last);
            }else{
                imagefttext($image, $descSize,0, (($viewWidth - 503)/2)*$resize, $descTop2, $blacks, $font, $last);
            }
        }else{
            $firstbox = imageftbbox($descSize, 0, $font, $desc);
            imagefttext($image, $descSize,0, (($viewWidth - 503)/2)*$resize, $descTop, $blacks, $font, $desc);
        }

        //商品价格
        if($shop['price']){
            imagefttext($image,19,0, 192*$resize, (746 + $down)*$resize, $redcolor, $font, $shop['price']);
        }

        //扫码立即下单
        imagefttext($image,21,0, 192*$resize, (794 + $down)*$resize, $redcolor, $font, '扫码立即下单');

        //店主微信号
        if (!empty($shop['wx_no'])){
            $wx_no = '店主微信号: '.$shop['wx_no'];
            imagefttext($image,16,0, 192*$resize, (694 + $down)*$resize, $blackcolor, $font, $wx_no);
        }

        // 保存图像
        imagepng($image, $filename);

        // 释放内存
        imagedestroy($image);
        return array(
            'filename' => $filename,
            'url'      => $url
        );
    }

    /**
     * 生成店铺关注二维码 2017/9/20
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
        $wechatAuth = new \Org\Wechat\WechatAuth(C('THIRD_APPID'), $project['appid']);
        $result = $wechatAuth->qrcodeCreate($data);
        if (!empty($result['url'])){
            $data = $result['url'];
        }else{
            return;
        }

        // 纠错级别：L、M、Q、H
        $level = 'Q';
        $size = 10;
        ob_start();
        \QRcode::png($data, false, $level, $size);
        $qrcodeImgString = base64_encode(ob_get_contents());
        ob_end_clean();


        $shop['logo_108'] = $_SERVER['DOCUMENT_ROOT'].'/img/open_108.png';

        $shop['qrtrace'] = $_SERVER['DOCUMENT_ROOT'].'/img/mall/qrtrace.png';

        // 生成推送图片-PHP画布
        $data = $this->createRecommendImg($shop, $qrcodeImgString, $folder, 1);

        $result = array('link' => $data['url'].'?modify='.NOW_TIME);

        return $result;
    }

    /**
     * 店铺关注二维码图片合成专用方法
     */
    private function createRecommendImg($shop, $qrcodeFile, $folder, $type = 0){
        $size = getimagesizefromstring(base64_decode($qrcodeFile));
        $resize = $size[0]/320;
        // 最终文件路基
        $path = '/img/temp/seller_recommend/'.$folder;
        $folder = $_SERVER['DOCUMENT_ROOT'].$path;
        if (!file_exists($folder) && !mkdir($folder, 0777, true)) {
            E('无读写权限');
        }

        $projectId = substr($shop['id'], 0, -3);
        $url = C('CDN').$path.'/'.$projectId.'.png';
        $filename = $folder.'/'.$projectId.'.png';

        $image = imagecreatetruecolor(1033 * $resize, 1033*$resize);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        // 填充图片
        $qsizearray= array();
        $qrtrace     = imagecreatefrompng($shop['qrtrace']);
        $psizearray = getimagesize($shop['qrtrace']);
        imagecopyresized($image, $qrtrace,0,  0, 0, 0, 1033 * $resize, 1033*$resize, $psizearray[0], $psizearray[1]);
        imagedestroy($qrtrace);

        // 填充二维码
        $qsizearray= array();
        $img_qrcode     = imagecreatefromstring(base64_decode($qrcodeFile));
        $psizearray = getimagesizefromstring(base64_decode($qrcodeFile));
        imagecopyresized($image, $img_qrcode, ((1033 * $resize - $psizearray[0]*2)/2),  347*$resize, 0, 0, $psizearray[0]*2, $psizearray[1]*2, $psizearray[0], $psizearray[1]);
        imagedestroy($img_qrcode);
        $n1 = $psizearray[0] * 2;
        $n2 = $psizearray[1] * 2;

        //二维码中间图标
        if($shop['logo_108']){
            $shopLogo = imagecreatefrompng($shop['logo_108']);
            if (!$shopLogo){
                $shopLogo = imagecreatefromjpeg($shop['logo_108']);
            }
            $psizearray = getimagesize($shop['logo_108']);
            $wide = 120;
            imagecopyresized($image, $shopLogo, ((1033 * $resize - $n1)/2) + $n1/2 - $wide*$resize/2, 347*$resize + $n2/2 - $wide*$resize/2, 0, 0, $wide*$resize, $wide*$resize, $psizearray[0], $psizearray[1]);
            imagedestroy($shopLogo);
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

    /**
     * 生成店铺关注二维码 2017/9/20 只有二维码
     */
    public function create_code_guanzhu_code($shopId = '', $mid = ''){
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
        $wechatAuth = new \Org\Wechat\WechatAuth(C('THIRD_APPID'),$project['appid']);
        $result = $wechatAuth->qrcodeCreate($data);
        if (!empty($result['url'])){
            $data = $result['url'];
        }else{
            return;
        }

        // 纠错级别：L、M、Q、H
        $level = 'Q';
        $size = 10;
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
        $data = $this->createRecommendImg_code($shop, $qrcodeImgString, $folder, 1);

        $result = array('link' => $data['url'].'?modify='.NOW_TIME);

        return $result;
    }

    /**
     * 店铺关注二维码图片合成专用方法 只有二维码
     */
    private function createRecommendImg_code($shop, $qrcodeFile, $folder, $type = 0){
        $size = getimagesizefromstring(base64_decode($qrcodeFile));
//        $resize = $size[0]/245;
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


        $image     = imagecreatefromstring(base64_decode($qrcodeFile));
        $psizearray = getimagesizefromstring(base64_decode($qrcodeFile));
        $n1 = $psizearray[0];
        $n2 = $psizearray[1];
        //二维码中间图标
        if($shop['logo_108']){
            $shopLogo = imagecreatefrompng($shop['logo_108']);
            if (!$shopLogo){
                $shopLogo = imagecreatefromjpeg($shop['logo_108']);
            }
            $psizearray = getimagesize($shop['logo_108']);
            $wide = 50;
            imagecopyresized($image, $shopLogo, $n1/2 - $wide*$resize/2, $n2/2 - $wide*$resize/2, 0, 0, $wide*$resize, $wide*$resize, $psizearray[0], $psizearray[1]);
            imagedestroy($shopLogo);
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

}
?>