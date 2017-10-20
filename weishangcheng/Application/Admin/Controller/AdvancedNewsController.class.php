<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Admin\Model\AdvancedNewsModel;

/**
 * 高级图文
 * 
 * @author lanxuebao
 *        
 */
class AdvancedNewsController extends CommonController
{
    public function index(){
        if(!IS_AJAX){
	        $this->display();
        }
        
        $page = I('get.page/d', 1);
        $offset = 20;
        $limit = ($page - 1) * $offset;
        $total = 0;
        
        $where = array('project_id' => $this->projectId);
        $Model = new AdvancedNewsModel();
        $data = $Model->getAll($where, $offset, $limit);
        $total = $data['total'];
        foreach ($data['rows'] as $k=>$v){
        	$line = floor($k/3);//当前行数
        	$column = $k-$line*3;//所在列
        	if($column == 0){
        		$list1[] = $v;
        	}else if($column == 1){
        		$list2[] = $v;
        	}else if($column == 2){
        		$list3[] = $v;
        	}
        }
        $this->assign(array(
        		'total' => $total,
        		'page'=>$page,
        		'offset'=>$offset,
        		'list1' => $list1,
        		'list2' => $list2,
        		'list3' => $list3
        ));
        $this->display('list');
    }
    
    /**
     * 添加
     */
    public function add(){
        if(IS_GET){
        	$this->display("edit");
        }
        
        $Model = new AdvancedNewsModel();
        $result = $Model->insert($_POST['data'], $this->projectId);
        if(empty($result)){
        	$this->error($Model->getError());
        }
        $this->success($result,'/advanced_news');
    }
    
    /**
     * 编辑
     */
    public function edit($id = 0){
    	$Model = new AdvancedNewsModel();
    	$list  = $Model->getById($id, $this->projectId);
        if(IS_POST){
        	$result = $Model->update($list, $_POST['data']);
            if(empty($result)){
                $this->error($Model->getError());
            }
            $this->success($result,'/advanced_news');
        }
        
        if(empty($data)){
            $this->error('数据不存在或已被删除！');
        }
        $this->assign('data', encode_json($list));
        $this->display();
    }
    
    public function delete($id = 0){
        $result = M("wx_news")->where("id IN ({$id}) AND project_id='{$this->projectId}'")->delete();
        if($result){
            M("wx_news")->where("project_id='{$this->projectId}' AND pid IN ({$id})")->delete();
        }
        $this->success('删除成功！');
    }
}
?>