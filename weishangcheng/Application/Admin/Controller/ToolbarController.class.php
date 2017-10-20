<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 菜单按钮管理
 * 
 * @author lanxuebao
 *        
 */
class ToolbarController extends CommonController
{
    private $Model;
    public $authRelation = array(
        'index'   => 'admin.menu.index',
        'add'   => 'admin.menu.add',
        'edit'   => 'admin.menu.edit',
        'delete'   => 'admin.menu.delete',
    );

    function __construct()
    {
        parent::__construct();
        $this->Model = M('admin_node');
    }
    
    /**
     * 工具栏按钮列表
     * @param number $menu_id
     */
    public function index($menu = 0){
        if(IS_POST){
            $rows = $this->Model->where("pid=".$menu)->order("`groups`, sort desc, id")->select();
            $this->ajaxReturn($rows);
        }
        $this->assign('menu_id', $menu);
        $this->display();
    }
    
    /**
     * 工具栏 - 添加按钮
     * @param number $menu_id
     */
    public function add($menu_id = 0){
        if(IS_POST){
            $data = I('post.');
            if($data['target'] == 'container'){
                $data['target'] = $_POST['target'];
            }
            
            if($data['event_type'] == 'default'){
                $data['event_value'] = '';
            }
            $data['name'] = strtolower($data['name']);
            
            $Module = $this->Model;
            $result = $Module->add($data);
            if($result > 0){
                $this->cache();
                $this->success('添加成功！');
            }
            $this->error('添加失败！');
        }
        
        $data = array('pid' => $menu_id, 'visible' => 1);
        $this->assign('data', $data);
        $this->display('edit');
    }
    
    /**
     * 工具栏 - 修改按钮
     * @param number $menu_id
     */
    public function edit($id = 0){
        if(IS_POST){
            $data = I('post.');
            if($data['target'] == 'container'){
                $data['target'] = $_POST['target'];
            }
            
            if($data['event_type'] == 'default'){
                $data['target'] = '';
            }
            $data['name'] = strtolower($data['name']);
            
            $result = $this->Model->save($data);
            if($result > 0){
                $this->cache();
            }
            if($result >= 0){
                $this->success('修改成功！');
            }
            $this->error('修改失败！');
        }

        $data = $this->Model->find($id);
        if(empty($data)){
            $this->error('按钮不存在或已被删除！');
        }
        $this->assign('data', $data);
        $this->display('edit');
    }
    
    /**
     * 工具栏 - 删除按钮
     * @param number $id
     */
    public function delete($id = 0){
        if(IS_POST){
            $result = $this->Model->delete($id);
            if($result > 0){
                $this->cache();
            }
            if($result >= 0){
                $this->success('已删除！');
            }
        }
        
        $this->error('删除失败！');
    }
    
    /**
     * 工具栏 - 自动生成按钮
     * @param number $id
     */
    public function autoCreate($menu_id = 0){
        if(IS_POST){
            $menu = $this->Model->find($menu_id);
            $exists = $this->Model->field("id, `name`")->where("pid='{$menu_id}'")->select();
            $data = array();
            
            $exists = array_kv($exists);

            $default_action = empty($menu['action']) ? 'index' : $menu['action'];
            
            if(!in_array($default_action, $exists)){
                $data[] = array('pid' => $menu_id, 'title' => '查看', 'name' => $default_action, 'icon' => '', 'groups' => 1, 'visible' => 0, 'event_type' => 'view', 'target' => 'self', 'sort' => '100');
            }
            
            if(!in_array('add', $exists)){
                $data[] = array('pid' => $menu_id, 'title' => '添加', 'name' => 'add', 'icon' => 'icon-plus', 'groups' => 1, 'visible' => 1, 'event_type' => 'view', 'target' => 'modal', 'sort' => '99');
            }
            if(!in_array('edit', $exists)){
                $data[] = array('pid' => $menu_id, 'title' => '编辑', 'name' => 'edit', 'icon' => 'icon-edit', 'groups' => 1, 'visible' => 1, 'event_type' => 'view', 'target' => 'modal', 'sort' => '99');
            }
            if(!in_array('delete', $exists)){
                $data[] = array('pid' => $menu_id, 'title' => '删除', 'name' => 'delete', 'icon' => 'icon-trash', 'groups' => 1, 'visible' => 1, 'event_type' => 'default', 'target' => '', 'sort' => '99');
            }
            if(!in_array('search', $exists)){
                $data[] = array('pid' => $menu_id, 'title' => '搜索', 'name' => 'search', 'icon' => 'icon-search', 'groups' => 2, 'visible' => 1, 'event_type' => 'default', 'target' => '', 'sort' => '99');
            }
            
            if(count($data) > 0){
                $result = $this->Model->addAll($data);
                if($result > 0){
                    $this->cache();
                    $this->success('自动生成成功！');
                }
            }else{
                $this->error('不能重复生成！');
            }
        }
        
        $this->error('自动生成失败！');
    }
    
    /**
     * 更新缓存
     */
    public function cache(){
        /***************** 缓存菜单 *****************/
        // 缓存节点
        $sql = "SELECT menu.module, menu.controller, node.*
                FROM admin_menu AS menu
                INNER JOIN admin_node AS node ON node.pid=menu.id
                WHERE menu.status<>0 AND node.access<>-1
                ORDER BY node.`groups`, node.sort DESC, node.id";
        
        $list = $this->Model->query($sql);
        
        // 处理成键值对数组(模块，控制器，操作)
        $nodeList = array();
        foreach($list as $index=>$item){
            $nodeList[$item['id']] = $item;
        }

        F('node', $nodeList, DATA_PATH . 'System/');

        if(!IS_AJAX){
            $this->success('缓存已更新！', 'javascript:window.history.back();');
        }
    }
    
    /**
     * 保存排序
     */
    public function saveSort(){
        if(is_array($_POST['list']) && count($_POST['list']) > 0){
            $sql = "";
            foreach($_POST['list'] as $id=>$sort){
                $sql .= "UPDATE admin_node SET sort='".$sort."' WHERE id=".$id.";";
            }
            $result = $this->menuModule->execute($sql);
            
            if($result > 0){
                $this->cache();
            }
        }
        $this->success('已保存排序！');
    }
}

?>