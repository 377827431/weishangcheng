<?php
namespace Service\Controller;
use Alipay\AntifraudVerify;
use Think\Controller;

class TestController extends Controller
{
    public function index(){
//        die();
        Vendor('Alipay.AntifraudVerify');
        $param = array(
            "name" => "纪阳",
            "transaction_id" => "150001",
            "cert_no" => "230107198501271810",
            "mobile" => "15004678169"
        );
        $param['transaction_id'] = base_convert($param['name'].$param['cert_no'], 11, 36);
        $str = str_replace('%', '', urlencode($param['name'])).$param['cert_no'];
        print_data($str);
        $re = AntifraudVerify::verify($param);
        print_data($re);
    }

    public function tt(){

    }
}
?>