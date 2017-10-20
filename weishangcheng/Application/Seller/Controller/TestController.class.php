<?php

namespace Seller\Controller;
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/4/6
 * Time: 17:53
 */
class TestController extends \Think\Controller{

    public function index(){
//        $id = I('post.id');
//        $price = I('post.price');
//        $id = 1000029;
//        if (is_numeric($id) && $id > 0){
//            $mid = '/goods?id='.$id;
//            $model = new \Common\Model\QrcodeModel();
//            $result = $model->create_goods_code(100006001, $mid, $id,'¥'.$price);
//            $url = $result['link'];
//            print_data($url);
//            $this->ajaxReturn($url);
//        }
        session('user',array(
            'id'=>6,
            'wxf725c1736ace01c3'=>array(
                'openid'=>'ovM4cwrOuzq_ndsS4DxeJVuTo-vg',
                'mid'=>6
            ),
            'wxc2aa27081932df9b'=>array(
                'openid'=>'oIKyBwbcEN6urLIB172RBGnJZivc',
                'mid'=>6
            ),
            'openid'=>'oIKyBwbcEN6urLIB172RBGnJZivc',
            'appid'=> 'wxc2aa27081932df9b',
            'login_type'=>1,
        ));
        var_dump(session());die;
    }
}