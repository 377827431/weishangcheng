<?php
namespace H5\Controller;

use Common\Common\CommonController;
use Common\Model\AddressModel;

/**
 * 收货地址api
 * @author lanxuebao
 *
 */
class AddressController extends CommonController
{
	public function json(){
		$Model = D('City');
		$pid = I('get.pid/d', 1);
		
		$list = $Model->select($pid);
		$this->ajaxReturn($list);
	}
	
	/**
	 * 我的收货地址列表
	 */
	public function my(){
		$mid = $this->user('id');
		$Model = new AddressModel();
		$data = $Model->getAll($mid);
		$this->ajaxReturn($data);
	}
	
	/**
	 * 删除收货地址
	 */
	public function delete(){
		$id = I('post.receiver_id/d', 0);
		if($id > 0){
			$mid = $this->user('id');
			M('member_address')->where(array("mid" => $mid, "receiver_id" => $id))->delete();
		}
		$this->success();
	}
	
	/**
	 * 编辑收货地址
	 */
	public function save(){
		$data = array(
		    'receiver_id'    => $_POST['receiver_id'],
            'mid'            => $this->user('id'),
            'receiver_name'  => trim($_POST['receiver_name']),
            'receiver_mobile'=> $_POST['receiver_mobile'],
            'province_code'  => $_POST['province_code'],
            'city_code'      => $_POST['city_code'],
            'county_code'    => $_POST['county_code'],
            'receiver_detail'=> trim($_POST['receiver_detail']),
            'receiver_zip'   => $_POST['receiver_zip'],
            'is_default'     => $_POST['is_default'] == 1 ? 1 : 0
		);
		    
		$Model = new AddressModel();
		$data = $Model->modify($data);
		$this->success($data);
	}
}
?>