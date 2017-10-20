<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 文章频道
 * 
 * @author lanxuebao
 *        
 */
class ArticleChannelController extends CommonController
{
    private $Model;

    function __construct()
    {
        parent::__construct();
        $this->Model = M('article_channel');
    }
    
    private function article_list($all = true){
        if(!$all){
            $this->Model->field("*");
            $id = I('get.id/d', 0);
            if($id > 0){
                $this->Model->where("id!={$id}");
            }
        }
        
        $rows = $this->Model->order('id DESC')->select();
        return sort_list($rows);
    }

    /**
     * 列表
     */
    public function index()
    {
        if (IS_AJAX) {
            $rows = $this->article_list();
            $this->ajaxReturn($rows);
        }
        $this->display();
    }
    
    
    /**
     * 添加菜单
     */
    public function add(){
        if(IS_POST){
            $data = I('post.');
            $data['project_id'] = session('user.project_id');
            $result = $this->Model->add($data);
            if($result > 0){
                $this->success('添加成功!');
            }
            $this->error('添加失败！');
        }
        $list = $this->article_list(false);
        $this->assign(array('data' => $data,'list' => $list ));
        $this->display('edit');
    }
    
    /**
     * 编辑
     */
    public function edit($id = 0){
        if(IS_POST){
            $data = I('post.');
            if($id <= 0){
                $this->error('数据ID异常！');
            }
            $result = $this->Model->save($data);
            if($result >= 0){
                $this->success('已修改！');
            }
            $this->error('修改失败！');
        }
        
        $data = $this->Model->find($id);
        if(empty($data)){
            $this->error('菜单不存在或已被删除！');
        }
        $list = $this->article_list(false);
        $this->assign(array('data' => $data,'list' => $list ));
        $this->display();
    }
    
    /**
     * 删除菜单
     */
    public function delete($id = 0){
        if(empty($id)){
            $this->error('删除项不能为空！');
        }
        $result = $this->Model->delete($id);
        if($result > 0){
            $this->success('删除成功！');
        }
    }
    
    private function toArray(&$list, $array, $childField = 'children'){
        foreach($array as $index=>$item){
            unset($array[$index]);
            
            $children = $item[$childField];
            unset($item['children']);
            $list[$item['id']] = $item;
            
            if(!empty($children)){
                $this->toArray($list, $children);
            }
        }
    }
    
    private function sortMenu(&$list, $pid = 0, $index = 0){
        if (empty($list)) {
            return $list;
        }
        $data = array();
        
        foreach ($list as $key => $value) {
            if ($value['pid'] == $pid) {
                unset($list[$key]);
                $data[] = $value;
                $children = sort_list($list, $value['id'], $index + 1);
                if(!empty($children)){
                    $data = array_merge($data , $children);
                }
            }
        }
        
        // 把没有父节点的数据追加到返回结果中，避免数据丢失
        if($pid == 0 ){
            if(count($list) > 0){
                $data = array_merge($data, $list);
            }
            
            $list = $data;
            return $list;
        }
        return $data;
    }
}

?>