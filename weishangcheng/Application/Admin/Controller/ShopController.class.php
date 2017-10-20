<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 店铺设置
 * 
 * @author lanxuebao
 *        
 */
class ShopController extends CommonController
{
    private $Model;
    
    function __construct(){
        parent::__construct();
        $this->Model = new \Common\Model\ShopModel($this->projectId);
    }
    
    /**
     * 店铺设置--店铺信息
     */
    public function index(){
        $this->edit();
    }
    
    /**
     * project下所有店铺
     */
    public function all(){
        if(IS_AJAX){
            $list = $this->Model->getAll($this->projectId);
            $this->ajaxReturn($list);
        }
        
        $this->display();
    }
    
    /**
     * 添加
     */
    public function add(){
        if(IS_POST){
            $success = $this->Model->addNew($_POST['shop'], $_POST['info']);
            if(!$success){
                $this->error($this->Model->getError());
            }
            $this->success('创建新店铺成功！');
        }
        
        $shop = array('logo' => C('CDN').'/img/logo.jpg');
        $this->assign('data', $shop);
        $this->display('edit');
    }
    
    /**
     * 保存店铺信息
     */
    public function edit(){
        $id = is_numeric($_REQUEST['id']) ? $_REQUEST['id'] : $this->user('shop_id');
        
        if(IS_POST){
            if($_POST['disabled']){
                $this->Model->disabled($id);
            }else{
                $_POST['shop']['id'] = $id;
                $success = $this->Model->update($_POST['shop'], $_POST['info']);
                if(!$success){
                    $this->error($this->Model->getError());
                }
            }
            $this->success('已保存！');
        }
        
        $shop = $this->Model->getById($id);
        if(empty($shop)){
            $this->error('店铺不存在');
        }else if(empty($shop['logo'])){
            $shop['logo'] = C('CDN').'/img/logo.jpg';
        }
        $this->assign('data', $shop);
        $this->display('edit');
    }
    
    /*
     * 编辑退款地址
     */
    public function refund(){
        $Model = M('shop_refund');
        if(IS_POST){
            $id = I('post.id/d');
            if(!is_numeric($id)){
                $this->error('编辑ID不能为空');
            }
            if(empty($_POST['receiver_name'])){
                $this->error('退货联系人不能为空');
            }
            if(empty($_POST['receiver_province'])){
                $this->error('退货省份不能为空');
            }
            if(empty($_POST['receiver_city'])){
                $this->error('退货城市不能为空');
            }
            if(empty($_POST['receiver_county'])){
                $this->error('退货区/县不能为空');
            }
            if(empty($_POST['receiver_detail'])){
                $this->error('退货详细地址不能为空');
            }
            if(!is_numeric($_POST['receiver_mobile'])){
                $this->error('退货电话不能为空');
            }
            $data = array(
                'id'                 => addslashes($_POST['id']),
                'receiver_name'      => addslashes($_POST['receiver_name']),
                'receiver_mobile'    => addslashes($_POST['receiver_mobile']),
                'receiver_zip'       => addslashes($_POST['receiver_zip']),
                'receiver_province'  => addslashes($_POST['receiver_province']),
                'receiver_city'      => addslashes($_POST['receiver_city']),
                'receiver_county'    => addslashes($_POST['receiver_county']),
                'receiver_detail'    => addslashes($_POST['receiver_detail'])
            );
            $Model->save($data);
            $this->success('已保存');
        }
        $data = $Model->query("select * from shop left join shop_refund as sr on sr.id = shop.id where shop.id = '".$_GET['id']."'");
        if(empty($data)){
            $this->error('不能为空');
        }
        $data = $data[0];
        $this->assign('data',$data);
        $this->display();
    }    
    
    /**
     * 删除店铺
     */
    public function delete(){
    	$Model = $this->Model;
    	$res = $Model->deleteById($_POST['id']);
        if(!$res){
        	$this->error($Model->getError());
        }
        $this->success('删除成功！');
    }
}
?>