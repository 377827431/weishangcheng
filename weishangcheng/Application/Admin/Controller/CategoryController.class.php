<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Common\Model\CategoryModel;
/**
 * 商品类目
 * 
 * @author lanxuebao
 *        
 */
class CategoryController extends CommonController
{
    /**
     * 列表
     */
    public function index(){
        if(!IS_AJAX){
            $this->display();
        }
        
        $Model = $this->getModel();
        $list = $Model->adminList();
        $this->ajaxReturn($list);
    }
    
    private function getModel(){
        static $Model = null;
        if(is_null($Model)){
            $Model = new CategoryModel($this->projectId);
        }
        
        return $Model;
    }
    
    /**
     * 添加
     */
    public function add(){
        $data = array(
            'pid'  => 0,
            'icon' => '',
            'name' => '',
            'sort' => '',
        );

        $Model = $this->getModel();
        if(IS_POST){
            $result = $Model->add($_POST);
            $this->success('已添加');
        }
        
        $this->assignPCategory();
        $this->display();
    }
    
    private function assignPCategory($pid = -1, $id = -1){
        $Model = $this->getModel();
        $projectId = $Model->getProjectId();
        
        $list = $Model->field('id, name, level, pid')->where("project_id='{$projectId}' AND level<3")->order("sort DESC, id")->select();
        $list1 = $list2 = array();
        $pid1 = 0;
        foreach ($list as $item){
            if($item['id'] == $id){
                continue;
            }
            
            if($item['level'] == 1){
                $list1[] = $item;
            }else{
                $list2[$item['pid']][] = $item;
            }
            
            if($pid == $item['id']){
                $pid1 = $item['level'] == 1 ? $item['id'] : $item['pid'];
            }
        }
        
        $this->assign('pid1', $pid1);
        $this->assign('list1', $list1);
        $this->assign('list2', json_encode($list2));
    }
    
    /**
     * 编辑
     */
    public function edit(){
        $id = $_REQUEST['id'];
        if(!is_numeric($id)){
            $this->error('ID不能为空');
        }
        
        $Model = $this->getModel();
        $data = $Model->find($id);
        if(!$data){
            $this->error('ID不存在');
        }
        
        if(IS_POST){
            $old = $data;
            $data = array_merge($data, $_POST);
            $Model->save($data, $old);
            $this->success('保存成功');
        }
        
        $data['sort'] = $data['sort'] == 0 ? '' : $data['sort'];
        $this->assign('data', $data);
        $this->assignPCategory($data['pid'], $data['id']);
        $this->display('add');
    }
    
    /**
     * 删除菜单
     */
    public function delete(){
        $id = $_POST['id'];
        if(empty($id)){
            $this->error('删除项不能为空！');
        }
        $Model = $this->getModel();
        $result = $Model->delete($id);
        if($result < 1){
            $this->error($Model->getError());
        }
        $this->success('删除成功！');
    }
}
?>