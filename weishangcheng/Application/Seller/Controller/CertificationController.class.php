<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/6/6
 * Time: 16:10
 */

namespace Seller\Controller;
use Alipay\AntifraudVerify;


class CertificationController extends ManagerController
{
    public function index(){
        $id = $this->user('id');
        $t_auth = M('transfers_auth')->field('id, status, created, card_name, card_no')->where("id = '{$id}'")->find();
        if (empty($t_auth) || $t_auth['status'] == 2){
            $this->display();
        }elseif ($t_auth['status'] == 0){
            $this->display("result");
        }else{
            $this->assign('data', $t_auth);
            $this->display("overview");
        }
    }

    public function update_auth(){
        if (IS_AJAX){
            $id = $this->user('id');
            $data = M('transfers_auth')->field('status')->where("id = '{$id}'")->find();
            if (empty($data)){
                $add = array(
                    'id' => $id,
                    'created' => time(),
                    'status' => 0,
                    'project_id' => substr($this->shopId, 0, -3),
                    'shop_id' => $this->shopId,
                    'card_pic' => I('post.card_img'),
                    'card_name' => I('post.true_name'),
                    'card_no' => I('post.card_num')
                );
                M('transfers_auth')->add($add);
                $msg = '1';
            }else{
                if ($data['status'] == 0 || $data['status'] == 1){
                    $msg = '1';
                }else{
                    $up = array(
                        'created' => time(),
                        'status' => 0,
                        'project_id' => substr($this->shopId, 0, -3),
                        'shop_id' => $this->shopId,
                        'card_pic' => I('post.card_img'),
                        'card_name' => I('post.true_name'),
                        'card_no' => I('post.card_num')
                    );
                    M('transfers_auth')->where("id = '{$id}' AND status = 2")->save($up);
                    $msg = '1';
                }
            }
            $this->ajaxReturn($msg);
        }
    }

    public function result(){
        $this->display();
    }

    public function overview(){
        $this->display();
    }

    public function alipay_verify(){
        $is_show = I('get.id', 0);
        $from = I('get.from', '');
        $data = M('transfers_auth')
            ->where(array("id" => $this->user('id'), "status" => 1))
            ->find();
        $re = array(
            "name" => '',
            "cert_no" => '',
            "zfb" => '',
            "wx" => ''
        );
        if (!empty($data)){
            $re['name'] = $data['card_name'];
            $re['cert_no'] = $data['card_no'];
            $con = 0;
        }else{
            $con = 1;
        }
        $shopInfo = M('shop_info')
            ->where(array("id" => $this->shopId))
            ->find();
        if (!empty($shopInfo['pay_zfb'])){
            $re['zfb'] = $shopInfo['pay_zfb'];
        }
        if (!empty($shopInfo['pay_qr'])){
            $re['wx'] = $shopInfo['pay_qr'];
        }
        $this->assign('con', $con);
        $this->assign('re', $re);
        $this->assign('from', $from);
        $this->assign('is_show', $is_show);
        $this->display();
    }

    public function verify(){
        $result = 0;
        $post = I('post.');
        if (!empty($post['zfb']) || !empty($post['wx'])){
            $up = array(
                "pay_zfb" => !empty($post['zfb']) ? $post['zfb'] : '',
                "pay_qr" => !empty($post['wx']) ? $post['wx'] : ''
            );
            M('shop_info')->where(array("id" => $this->shopId))->save($up);
        }
        $param = array(
            "name" => $post['name'],
            "cert_no" => $post['cert_no']
        );
        $re = $this->doVerify($param);
        $re = json_decode(json_encode($re), 1);
        $re = $re['zhima_credit_antifraud_verify_response'];
        if (!empty($re['code']) && $re['code'] == 10000 && !empty($re['verify_code'][0])){
            $code = $re['verify_code'][0];
            if (substr($code, -3, strlen($code)) == '_MA'){
                $data = M('transfers_auth')
                    ->where(array("id" => $this->user('id')))
                    ->find();
                if (!empty($data)){
                    $up = array(
                        "status" => 1,
                        "project_id" => substr($this->shopId, 0, -3),
                        "shop_id" => $this->shopId,
                        "card_name" => $param['name'],
                        "card_no" => $param['cert_no'],
                        "created" => time()
                    );
                    M('transfers_auth')->where(array("id" => $this->user('id')))->save($up);
                }else{
                    $add = array(
                        "id" => $this->user('id'),
                        "status" => 1,
                        "project_id" => substr($this->shopId, 0, -3),
                        "shop_id" => $this->shopId,
                        "card_name" => $param['name'],
                        "card_no" => $param['cert_no'],
                        "created" => time()
                    );
                    M('transfers_auth')->add($add);
                }
                $result = 1;
            }
        }

        if ($result == 0){
            $date = date("Y-m-d H:i:s", time());
            $error = session('alipay_verify_error');
            if (count($error) > 9){
                $info = json_encode($error);
                $add = array(
                    "userid" => $this->user('id'),
                    "created" => $date,
                    "info" => $info
                );
                M('member_certification_log')->add($add);
                session('alipay_verify_error', null);
            }else{
                $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
                $user_IP = ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];
                $error[] = array(
                    "name" => $param['name'],
                    "cert_id" => $param['cert_no'],
                    "time" => $date,
                    "ip" => $user_IP
                );
                session('alipay_verify_error', $error);
            }
        }
        $res = array(
            "code" => $result,
        );
        $this->ajaxReturn($res);
    }

    private function doVerify($param){
        $str = '';
        foreach ($param as $k => $v){
            $str .= $v.'_';
        }
        $date = date("Ymd", time());
        $str .= $date;
        $str = urlencode($str);
        $str = str_replace('%', '', $str);
        $param['transaction_id'] = $str;
        Vendor('Alipay.AntifraudVerify');
        $re = AntifraudVerify::verify($param);
        return $re;
    }
}