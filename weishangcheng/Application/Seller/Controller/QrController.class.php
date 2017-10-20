<?php
namespace Seller\Controller;
use Org\Wechat\WechatAuth;

/**
 * 微信二维码
 * @author jy
 *
 */
class QrController extends ManagerController
{
    /**
     * 生成推荐二维码
     */
    public function recommond(){
        $model = new \Common\Model\QrcodeModel();
        $model->create_code(100001001);
        die();
        ignore_user_abort(true);
        header('X-Accel-Buffering: no');
        header('Content-Length: '. strlen(ob_get_contents()));
        header("Connection: close");
        header("HTTP/1.1 200 OK");
        ob_end_flush();
        flush();


        set_time_limit(180);
        $type = addslashes($_GET['type']);
//        $openid = addslashes($_GET['openid']);
//        if(empty($openid)){
//            $this->error('openid不能为空');
//        }
        set_time_limit(0);
        $Module = M("wx_user");
        //获取用户信息
//        $user = $Module
//            ->alias("wx")
//            ->field("member.id, wx.openid, member.agent_level, member.nickname AS mname, wx.nickname, wx.headimgurl, wx.appid")
//            ->join("member ON member.id=wx.mid")
//            ->where("wx.openid='{$openid}'")
//            ->find();
//
//        if(empty($user)){
//            return;
//        }

        $list = array(
            'jingdian'  => array(
                'qrcode'   => array('x' => 374, 'y' => 373, 'w' => 231, 'h' => 231, 'filename' => ''),
                'headimg'  => array('x' => 51, 'y' => 41, 'w' => 97, 'h' => 97, 'filename' => ''),
                'background' => array('x' => 0, 'y' => 0, 'w' => 640, 'h' => 824, 'filename' => 'jingdian.jpg'),
                'font' => array('R' => 255, 'G' => 255, 'B' => 255,'f' => 26, 'filename' => ''),
                'user' => array('x' => 170, 'y' => 100, 'w' => 0, 'h' => 0, 'filename' => ''),
                'valid' => array('f' => 12, 'R' => 0, 'G' => 0, 'B' => 0, 'left' => 50, 'top' => 795, 'filename' => ''),
            ),
            'gaobige1'  => array(
                'qrcode'   => array('x' => 463, 'y' => 761, 'w' => 160, 'h' => 160, 'filename' => ''),
                'headimg'  => array('x' => 24, 'y' => 765, 'w' => 100, 'h' => 100, 'filename' => ''),
                'background' => array('x' => 0, 'y' => 0, 'w' => 640, 'h' => 1000, 'filename' => 'gaobige1.jpg'),
                'font' => array('R' => 0, 'G' => 0, 'B' => 255,'f' => 13, 'filename' => ''),
                'user' => array('x' => 146, 'y' => 798, 'w' => 0, 'h' => 0, 'filename' => ''),
                'valid' => array('f' => 12, 'R' => 0, 'G' => 0, 'B' => 0, 'left' => 50, 'top' => 970, 'filename' => ''),
            ),
            'gaobige2'  => array(
                'qrcode'   => array('x' => 442, 'y' => 759, 'w' => 139, 'h' => 139, 'filename' => ''),
                'headimg'  => array('x' => 219, 'y' => 606, 'w' => 117, 'h' => 117, 'filename' => ''),
                'background' => array('x' => 0, 'y' => 0, 'w' => 640, 'h' => 1000, 'filename' => 'gaobige2.jpg'),
                'font' => array('R' => 255, 'G' => 255, 'B' => 255,'f' => 13, 'filename' => ''),
                'user' => array('x' => 215, 'y' => 750, 'w' => 0, 'h' => 0, 'filename' => ''),
                'valid' => array('f' => 12, 'R' => 0, 'G' => 0, 'B' => 0, 'left' => 50, 'top' => 970, 'filename' => ''),
            ),
            'gaobige3'  => array(
                'qrcode'   => array('x' => 76, 'y' => 433, 'w' => 132, 'h' => 132, 'filename' => ''),
                'headimg'  => array('x' => 34, 'y' => 237, 'w' => 100, 'h' => 100, 'filename' => ''),
                'background' => array('x' => 0, 'y' => 0, 'w' => 640, 'h' => 640, 'filename' => 'gaobige3.jpg'),
                'font' => array('R' => 255, 'G' => 255, 'B' => 255,'f' => 13, 'filename' => ''),
                'user' => array('x' => 144, 'y' => 255, 'w' => 0, 'h' => 0, 'filename' => ''),
                'valid' => array('f' => 12, 'R' => 255, 'G' => 255, 'B' => 255, 'left' => 50, 'top' => 610, 'filename' => ''),
            ),
            'yuangou'  => array(
                'qrcode'   => array('x' => 403, 'y' => 693, 'w' => 176, 'h' => 176, 'filename' => ''),
                'headimg'  => array('x' => 0, 'y' => 0, 'w' => 0, 'h' => 0, 'filename' => ''),
                'background' => array('x' => 0, 'y' => 0, 'w' => 640, 'h' => 1000, 'filename' => 'yuangou.jpg'),
                'font' => array('R' => 255, 'G' => 255, 'B' => 255,'f' => 13, 'filename' => ''),
                'user' => array('x' => 0, 'y' => 0, 'w' => 0, 'h' => 0, 'filename' => ''),
                'valid' => array('f' => 12, 'R' => 255, 'G' => 255, 'B' => 255, 'left' => 50, 'top' => 970, 'filename' => ''),
            ),
            'chunjie'  => array(
                'qrcode'   => array('x' => 227, 'y' => 649, 'w' => 186, 'h' => 186, 'filename' => ''),
                'headimg'  => array('x' => 506, 'y' => 430, 'w' => 107, 'h' => 107, 'filename' => ''),
                'background' => array('x' => 0, 'y' => 0, 'w' => 640, 'h' => 1000, 'filename' => 'chunjie.jpg'),
                'font' => array('R' => 255, 'G' => 0, 'B' => 0, 'f' => 13,'filename' => ''),
                'user' => array('x' => 376, 'y' => 477, 'w' => 0, 'h' => 0, 'filename' => ''),
                'valid' => array('f' => 12, 'R' => 0, 'G' => 0, 'B' => 0, 'left' => 50, 'top' => 970, 'filename' => ''),
            ),
            'shop'  => array(
                'qrcode'   => array('x' => 227, 'y' => 649, 'w' => 186, 'h' => 186, 'filename' => ''),
                'headimg'  => array('x' => 506, 'y' => 430, 'w' => 107, 'h' => 107, 'filename' => ''),
                'background' => array('x' => 0, 'y' => 0, 'w' => 640, 'h' => 1000, 'filename' => 'chunjie.jpg'),
                'font' => array('R' => 255, 'G' => 0, 'B' => 0, 'f' => 13,'filename' => ''),
                'user' => array('x' => 376, 'y' => 477, 'w' => 0, 'h' => 0, 'filename' => ''),
                'valid' => array('f' => 12, 'R' => 0, 'G' => 0, 'B' => 0, 'left' => 50, 'top' => 970, 'filename' => ''),
            ),

        );
        $wechatAuth = new WechatAuth('wx6cc9d933ad90b954');
        $user = session('manager');
        // 1000人为一个文件夹
        $folderStep = 1000;
        $num = bcdiv($user['project_id']-1, $folderStep)+1;
        $folder = ($num*$folderStep - $folderStep+1).'-'.$num*$folderStep;
        // 生成带参数二维码
        $ticket = $user["ticket"];
        if(empty($ticket)){
            $scene_id = "seller_".$user["project_id"];
            $qrcode   = $wechatAuth->qrcodeCreate($scene_id);
            $ticket   = $qrcode["ticket"];
        }
$ticket = 'gQFK8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3kweXE0T3JscWY3UTltc3ZPMklvAAIEG9jUUgMECAcAAA==';
        // 判断用户是否生成过本地二维码图片
        $qrcodeImg = $_SERVER['DOCUMENT_ROOT'].'/img/wximg/seller/'.$folder.'/'.$user["project_id"].'.jpg';
        if(!@is_file($qrcodeImg) || filesize($qrcodeImg) == 0){
            $qrcodeUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
            \Org\Net\Http::curlDownload($qrcodeUrl, $qrcodeImg);
            if(!@is_file($qrcodeImg) || filesize($qrcodeImg) == 0){
//                $wechatAuth->sendText($openid, '生成二维码失败，请重试！');
                return;
            }
        }

        // 下载用户头像
        $localHeadimg = $_SERVER['DOCUMENT_ROOT'].'/img/wximg/headimg/'.$folder.'/'.$user["project_id"].'.jpg';
        $headimgurl = str_replace('http://', 'https://', $user['headimgurl']);
        \Org\Net\Http::curlDownload($headimgurl, $localHeadimg);
        if(!@is_file($localHeadimg) || filesize($localHeadimg) == 0){
//            $wechatAuth->sendText($openid, '下载头像失败，请重试！');
            $localHeadimg = $_SERVER['DOCUMENT_ROOT'].'/img/logo.jpg';
        }

        // 生成推送图片-PHP画布
        $data = $this->createRecommendImg($user, $qrcodeImg, $localHeadimg, $folder, $list[$type]);

        $result = array('link' => $data['url'].'?modify='.NOW_TIME);
        $this->ajaxReturn($result);
    }

    /**
     * 图片合成方法
     */
    private function createRecommendImg($user, $qrcodeFile, $headimgFile, $folder, $data){
        // 最终文件路基
        $path = '/img/wximg/seller_recommend/'.$folder;
        $folder = $_SERVER['DOCUMENT_ROOT'].$path;
        if (!file_exists($folder) && !mkdir($folder, 0777, true)) {
            E('无读写权限');
        }

        $url = C('CDN').$path.'/'.$user['project_id'].'.jpg';
        $filename = $folder.'/'.$user['project_id'].'.jpg';

        // 绘制背景图
        $image = imagecreatetruecolor(783, 893);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);


//
//        // 将背景图覆盖到初始化的图形中
//        imagecopyresized($image, $background, $data['background']['x'], $data['background']['y'], 0, 0, $data['background']['w'],$data['background']['h'], $data['background']['w'], $data['background']['h']);
//        imagedestroy($background);

        // 读取头像图片
        if($data['headimg']){
            $img_headimg    = imagecreatefromjpeg($headimgFile);
            $psizearray     = getimagesize($headimgFile);
            imagecopyresized($image, $img_headimg,$data['headimg']['x'],$data['headimg']['y'], 0, 0,$data['headimg']['w'],$data['headimg']['h'], $psizearray[0], $psizearray[1]);
            imagedestroy($img_headimg);
            if($data['background']['filename'] == 'jingdian.jpg'){
                $img_headimg    = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'].'/img/wximg/header-img.png');
                $psizearray     = getimagesize($_SERVER['DOCUMENT_ROOT'].'/img/wximg/header-img.png');
                imagecopyresized($image, $img_headimg, 0, 0, 0, 0, 168, 145, $psizearray[0], $psizearray[1]);
                imagedestroy($img_headimg);
            }
        }

        // 填充二维码
        $img_qrcode     = imagecreatefromjpeg($qrcodeFile);
        $qsizearray     = getimagesize($qrcodeFile);

        imagecopyresized($image, $img_qrcode, 80,  802, 0, 0, 66, 66, $qsizearray[0], $qsizearray[1]);
        imagedestroy($qsizearray);

        // 白色文字
        $font           = $_SERVER['DOCUMENT_ROOT'].'/font/msyh.ttf';
        if($data['font'] && $data['user']){
            $whitecolor     = imagecolorallocate($image, 0, 0, 0);
            // 代理姓名
            $str = '你好，我是'.$user['nickname'];
            $length = mb_strlen($str, 'utf-8');
            $name           = $length > 10 ? mb_substr($str, 0, 10, 'utf-8').'...' : $str;
            imagefttext($image,16,0, 393, 97, $whitecolor, $font, $name);
        }
        //有限时间
        $whitecolor1     = imagecolorallocate($image, $data['valid']['R'], $data['valid']['G'], $data['valid']['B']);
        $str1 = date('m月d日',strtotime("+30 day"));
        $length = mb_strlen($str1, 'utf-8');
        $name1           = $length > 30 ? mb_substr($str1, 0, 10, 'utf-8').'...' : '该二维码30天（'."{$str1}".'前）有效，过期请重新生成';
        $data['valid']['top'] = 0;
        imagefttext($image, $data['valid']['f'], 0, 120, $data['valid']['top'], $whitecolor1, $font, $name1);
        // 保存图像
        imagejpeg($image, $filename);

        // 释放内存
        imagedestroy($image);
//        $image = imagecreatetruecolor(2000, 2000);
//        $white = imagecolorallocate($image, 255, 255, 255);
//        imagefill($image, 0, 0, $white);
//        imagejpeg($image, $filename);
        return array(
            'filename' => $filename,
            'url'      => $url
        );
    }

    /**
     * 代理URL二维码
     */
    public function create_url(){
        $projectId = substr($this->shopId, 0, -3);
        vendor("phpqrcode.phpqrcode");
        $project = get_project($projectId);
        // 纠错级别：L、M、Q、H
        $level = 'Q';
        // 点的大小：1到10,用于手机端4就可以了
        $size = 10;
        \QRcode::png($project['url'], false, $level, $size);
        die();
    }

    public function create(){
        $scene_id = $_GET['scene_id'];
        $wechatAuth = new \Org\Wechat\WechatAuth();
        $result = $wechatAuth->qrcodeCreate($scene_id);

        $result['outer_url'] = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$result['ticket'];
    }

    public function shop_qr(){
        $model = new \Common\Model\QrcodeModel();
        $result = $model->create_new_code($this->shopId);
        $url = $result['link'];
        $this->ajaxReturn($url);
    }

    public function goods_qr(){
        $id = I('post.id');
        $price = I('post.price');
        if (is_numeric($id) && $id > 0){
            $mid = '/goods?id='.$id;
            $model = new \Common\Model\QrcodeModel();
            $result = $model->create_goods_code($this->shopId, $mid, $id,'¥'.$price);
            $url = $result['link'];
            $this->ajaxReturn($url);
        }
    }
}