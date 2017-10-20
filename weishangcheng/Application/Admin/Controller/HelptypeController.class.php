<?php
namespace Admin\Controller;
use Common\Common\CommonController;

class HelptypeController extends CommonController{
	private $Module;

    function __construct()
    {
        parent::__construct();
        $this->Module = M('helper_type');
    }
	//问题分类列表
	public function index(){
		if(IS_AJAX){
			$list = $this->Module->select();
			$this->ajaxReturn($list);
		}
		$this->display();
	}
	//添加问题分类
	public function add(){
		if(IS_POST){
			$post = I('post.');
			$result = $this->Module->add($post);
			if($result>0){
				$this->success('保存成功');
			}
		}
		$this->display();
	}
	//编辑问题分类
	public function edit(){
		$id = I('get.id');
		if(!is_numeric($id)){
			$this->error('参数错误');
		}
		if(IS_POST){
			$post = I('post.');
			$result = $this->Module->where("id=%d",$post['id'])->save($post);
			if($result>0){
				$this->success('保存成功');
			}
		}
		$data = $this->Module->find($id);
		$this->assign('data',$data);
		$this->display('add');
	}
	//删除分类
	public function delete(){
		$id = I('post.id');
		if(empty($id)){
			$this->error('参数错误');
		}
		$id=addslashes($id);
		$sql = "SELECT * FROM helper_type WHERE id IN ({$id})";
        $Model = M();
        $list = $Model->query($sql);
        if(empty($list)){
            $this->error('ID不存在');
        }
        
        foreach ($list as $item){
            $Model->execute("DELETE FROM helper_type WHERE id=".$item['id']);
        }
        
        $this->success('删除成功');
	}
}
?>