<?php
namespace Admin\Controller;

use Common\Common\CommonController;
/**
* @author 于仕洋
* 模块 : 签到
*/
class SignController extends CommonController
{
	private $Module;

    /**
     * 签到规则
     */
	public function index(){
		$Model = D('Sign');
		$data = $Model->getData();
		$this->assign('data',$data);
		
		// 一年多少天
		$year = date('Y');
		$days = (($year % 4 == 0) && ($year % 100 != 0) || ($year % 400 == 0)) ? 366 : 365;
		$this->assign('days',$days);
		
		// 是否有修改权限
		$canSave = \Common\Common\Auth::get()->validated('admin', 'sign', 'save');
		$this->assign('canSave', $canSave);
		$this->display();
	}
	
	/**
	 * 保存修改
	 */
	public function save(){
	    $data = array(
	        'id'       => $_POST['id'],
	        'title'    => $_POST['title'],
	        'notice'   => $_POST['notice'],
	        'money'    => $_POST['money'],
	        'enabled'  => $_POST['enabled'] == 0 ? 0 : 1
	    );
	    
	    $rules = $_POST['rules'];
	    ksort($rules);
	    $data['rules'] = json_encode($rules);
	    
	    $Model = D('Sign');
	    if(is_numeric($data['id'])){
	        $Model->save($data);
	    }else{
    	    $Model->add($data);
	    }
	    
	    $this->success("已保存");
	}
}
?>