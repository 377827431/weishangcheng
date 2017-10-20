<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 二维码管理
 * 
 * @author lanxuebao
 *        
 */
class  QrController extends CommonController
{
    public function index(){
        
    }
    
    public function create(){
        $wechatAuth = new \Org\Wechat\WechatAuth();
        $filename = $_SERVER['DOCUMENT_ROOT'].'/img/qrcode/wx11c0525ac0e785da_huiyuan_kefu.jpg';
        $result = $wechatAuth->mediaUpload($filename, 'image');
        print_data($result);
    }
}