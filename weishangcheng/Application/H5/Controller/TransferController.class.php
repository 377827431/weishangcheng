<?php
namespace H5\Controller;
use Think\Controller;

class TransferController extends Controller{
	public function index(){
		$url = I('get.url');
		if(IS_WEIXIN){
			redirect($url);
		 }else{
		 	$user = session('user');
		 	if(!is_numeric($user['id'])){
				session('user',array('id'=>'1000019'));
				redirect($url);
			}else{
				redirect($url);
			}
		 }
	 }
}
?>