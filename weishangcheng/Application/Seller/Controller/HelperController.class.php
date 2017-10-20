<?php
/**
 * 客服问题列表页
 * create by LeeJS
 */
namespace Seller\Controller;
use Think\Controller;

class HelperController extends Controller{
	/**
	 * 问题列表页
	 */
	public function index(){
		//问题类别
		$type_list = M('helper_type')->select();
		//全部问题答案
		$list = M('helper')->select();
		$this->assign('type',$type_list);
		$this->assign('list',$list);
		$this->display('helppage');
		
	}
	/**
	 * 问题答案页
	 */
	public function answer(){
		$id = I("get.id");
		$detail = M('helper')->field('question,answer')->find($id);
		$detail['answer'] = html_entity_decode($detail['answer']); 
		$detail['answer'] = strip_tags($detail['answer'],'<img>');
		$this->assign('detail',$detail);
		$this->display('help_details');
	}
}
?>