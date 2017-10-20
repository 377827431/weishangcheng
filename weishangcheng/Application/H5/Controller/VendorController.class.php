<?php
namespace H5\Controller;
use Common\Common\CommonController;

/**
 * 供应商入住
 * @author 王宝福
 *
 */
class VendorController extends CommonController
{
    
    public function index(){
        print_data(1);
        $this->display();
    }
    
    public function add(){
        $data = $_POST;
        
        if($data['nick'] == '')
            $this->error('请填写联系人！');
        if($data['mobile'] == '')
            $this->error('请填写联系电话！');
        if(! preg_match('/^1[3|4|5|7|8]\d{9}$/', $data['mobile']))
            $this->error('手机号格式错误.');
        if($data['email'] == '')
            $this->error('请填写email！');
        if(! preg_match('/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/', $data['email']))
            $this->error('email格式错误.');
        if($data['address'] == '')
            $this->error('请填写公司地址！');
        
        $Model = M('vendor');
        $data['created'] = date('Y-m-d H:i:s');
        $data['product'] = json_encode($data['product']);
        $result = $Model->add($data);
        if($result > 0){
            $this->success('已保存！');
        }
        
        $this->error('操作失败！');
    }
}