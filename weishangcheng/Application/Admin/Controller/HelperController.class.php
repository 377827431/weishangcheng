<?php
namespace Admin\Controller;
use Common\Common\CommonController;

class HelperController extends CommonController{
	//客服问题列表
	public function index(){
		if(IS_AJAX){
			$data = array('rows' => null, 'total' => 0);
	        $offset = I('get.offset', 0);
	        $limit = I('get.limit', 50);
	        $type = I('get.type','');
	        $question = I('get.question');
	        $where = array();
	        if(is_numeric($type)){
	            $where['type'] = $type;
	        }
	        if(!empty($question)){
	        	$question = addslashes($question);
	        	$where['question'] = array('like',"%{$question}%");
	        }
	        $list = M('helper')->where($where)->limit("{$offset}, {$limit}")->select();
	        $data['rows'] = $list;
	        $data['total'] = count($list);
	        $this->ajaxReturn($data);
		}
		$type_list = M('helper_type')->select();
		$this->assign('typeList',$type_list);
		$this->display();
	}
	//添加客服问题
	public function add(){
		if(IS_POST){
			$post = I('post.');
			$this->save($post);
		}
		$this->display('edit');
	}
	//获取问题类型
	public function typeList(){
		if(IS_AJAX){
			$type_list = M('helper_type')->select();
			$this->ajaxReturn($type_list);
		}
	}
	//编辑客服问题
	public function edit(){
		if(IS_POST){
			$post = I('post.');
			$this->save($post);
		}
		$id = I('get.id');
		$data = M('helper')->find($id);
		$data['answer'] = html_entity_decode($data['answer']);
		$this->assign('data',$data);
		$this->display('edit');
	}
	//保存客服问题
	private function save($data){
		if(!is_numeric($data['id'])){
			//添加
			unset($data['id']);
			$data['created'] = date('Y-m-d H:i:s');
			$type = M('helper_type')->find($data['type']);
			$data['type_name'] = $type['type_name'];
			$result = M('helper')->add($data);
			if($result>0){
				$this->success();
			}else{
				$this->error('添加失败');
			}
		}else{
			//编辑
			$type = M('helper_type')->find($data['type']);
			$data['type_name'] = $type['type_name'];
			$result = M('helper')->where("id=%d",$data['id'])->save($data);
			$this->success();
		}
	}
	//删除客服问题
	public function delete(){
		$id = I('post.id');
        if(empty($id)){
            $this->error('ID不能为空');
        }
        $id = addslashes($id);
        $sql = "SELECT * FROM helper WHERE id IN ({$id})";
        $Model = M();
        $list = $Model->query($sql);
        if(empty($list)){
            $this->error('ID不存在');
        }
        
        foreach ($list as $item){
            $Model->execute("DELETE FROM helper WHERE id=".$item['id']);
        }
        
        $this->success('已删除');
	}

}
?>