<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 代理管理
 * 
 * @author wangjing
 *        
 */
class VendorController extends CommonController
{
    public function index(){
        if(IS_AJAX){
            $Model = D('Vendor');
            $data = $Model->getAll();
            $this->ajaxReturn($data);
        }
        
        $this->display();
    }
    
    public function add(){
        if($_POST){
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
        $this->display('edit');
    }
    
    public function edit(){
        $id = $_GET['id'];
        if($_POST){
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
            $product = array();
            foreach($data['product'] as $k=>$v){
                if($v['title'] == '' || $v['price'] == '' || $v['url'] == ''){
                    unset($data['product'][$k]);
                    continue;
                }
                $product[] = $v;
            }
            $data['product'] = json_encode($product);
            $result = $Model->where('id='.$id)->save($data);
            if($result === false){
                $this->error('操作失败！');
            }
            
            $this->success('已保存！');
        }
        
        $Model = D('Vendor');
        $data = $Model->getOne($id);
        $this->assign('data',$data);
        $this->display();
    }
    
    public function delete(){
        $id = I("post.id");
        $res = M("vendor")->where("id IN ({$id})")->delete();
        $this->success('删除成功！');
    }
    
    public function detail(){
        $id = $_GET['id'];
        $Model = D('Vendor');
        $data = $Model->getOne($id);
        $this->assign('data',$data);
        $this->display();
    }
}

?>