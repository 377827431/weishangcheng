<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 菜单管理
 * 
 * @author lanxuebao
 *        
 */
class MenuController extends CommonController
{
    private $Model;

    function __construct(){
        parent::__construct();
        
        $login = $this->user();
        if(!APP_DEBUG && $login['username'] != 'admin'){
            $this->error('仅admin管理员可操作');
        }
        $this->Model = M('admin_menu');
    }
    
    private function menu_list($all = true){
        if(!$all){
            $this->Model->field("id, pid, title, status, gid");
            $id = I('get.id/d', 0);
            if($id > 0){
                $this->Model->where("id!={$id}");
            }
        }
        
        $rows = $this->Model->order('pid, gid, sort DESC, id')->select();
        return sort_list($rows);
    }

    /**
     * 列表
     */
    public function index()
    {
        if (IS_AJAX) {
            $rows = $this->menu_list();
            foreach ($rows as $i => $item) {
                $rows[$i]['title'] = $item['split'].$item['title'];
                $rows[$i]['url'] = (empty($item['module']) ? '' : '/'.$item['module']) . '/' . $item['controller'];
                if(!empty($item['action'])){
                    $rows[$i]['url'] .= '/' . $item['action'];
                }
                if (! empty($item['params'])) {
                    $rows[$i]['url'] .= $item['params'];
                }
            }
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
            unset($data['create_btn']);
            if(empty($data['module']) || empty($data['controller']) || empty($data['action'])){
                unset($data['module']);
                unset($data['controller']);
                unset($data['action']);
            }
            
            $result = $this->Model->add($data);
            if($result > 0){
                // 自动创建菜单
                if(isset($_POST['create_btn']) && !empty($data['module']) && !empty($data['controller'])){
                    if(empty($data['action'])){
                        $data['action'] = 'index';
                    }
                    
                    $menu_id = $this->Model->getLastInsID();
                    $node = array();
                    $node[] = array('pid' => $menu_id, 'title' => '查看', 'name' => $data['action'], 'icon' => '', 'groups' => 1, 'visible' => 0, 'event_type' => 'view', 'target' => 'self', 'sort' => '100');
                    $node[] = array('pid' => $menu_id, 'title' => '添加', 'name' => 'add', 'icon' => 'icon-plus', 'groups' => 1, 'visible' => 1, 'event_type' => 'view', 'target' => 'modal', 'sort' => '99');
                    $node[] = array('pid' => $menu_id, 'title' => '编辑', 'name' => 'edit', 'icon' => 'icon-edit', 'groups' => 1, 'visible' => 1, 'event_type' => 'view', 'target' => 'modal', 'sort' => '99');
                    $node[] = array('pid' => $menu_id, 'title' => '删除', 'name' => 'delete', 'icon' => 'icon-trash', 'groups' => 1, 'visible' => 1, 'event_type' => 'default', 'target' => '', 'sort' => '99');
                    $node[] = array('pid' => $menu_id, 'title' => '搜索', 'name' => 'search', 'icon' => 'icon-search', 'groups' => 2, 'visible' => 1, 'event_type' => 'default', 'target' => '', 'sort' => '99');
                    M("admin_node")->addAll($node);
                }
                
                $this->cache();
                $this->success('添加成功!');
            }
            $this->error('添加失败！');
        }
        $list = $this->menu_list(false);
        $this->assign(array('list' => $list, 'action' => 'add', 'data' => array('sort' => 99, 'status' => 1, 'module' => strtolower(MODULE_NAME))));
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
            
            if(empty($data['module'])){
                $data['module'] =  $data['controller'] = $data['action'] = '';
            }
            $result = $this->Model->save($data);
            
            if($result > 0){
                $this->cache();
            }
            if($result >= 0){
                $this->success('已修改！');
            }
            $this->error('修改失败！');
        }
        
        $data = $this->Model->find($id);
        if(empty($data)){
            $this->error('菜单不存在或已被删除！');
        }
        $list = $this->menu_list(false);
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
            M("admin_node")->where("pid IN ({$id})")->delete();
            
            $this->cache();
        }
        $this->success('删除成功！');
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
    
    /**
     * 更新缓存
     */
    public function cache(){
        /***************** 缓存菜单 *****************/
        $menuList = array();
        // 缓存菜单
        $list = $this->Model->where("status<>0")->order('pid, gid, sort DESC, id')->select();

        $list = $this->sortMenu($list);
        // 数据处理
        foreach($list as $index=>$menu){
            // URL
            if(!empty($menu['module']) && !empty($menu['controller'])){
                if(C('APP_SUB_DOMAIN_DEPLOY') == true){
                    $menu['url'] = '/'.$menu['controller'];
                }
                else{
                    $menu['url'] = '/'.$menu['module'].'/'.$menu['controller'];
                }
                
                if($menu['action'] != 'index'){
                    $menu['url'] .= '/'.$menu['action'];
                }
                $menu['url'] .= $menu['params'];
            }
            else{
                $menu['url'] = '';
            }
            
            unset($menu['sort']);
            $menuList[$menu['id']] = $menu;
        }
        
        F('menu', $menuList, DATA_PATH . 'System/');
        
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
}
?>