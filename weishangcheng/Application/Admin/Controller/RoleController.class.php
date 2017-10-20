<?php
namespace Admin\Controller;

use Common\Common\CommonController;
/**
 * 角色管理
 * 
 * @author seable
 *        
 */
class RoleController extends CommonController
{
    private $Module;

    function __construct()
    {
        parent::__construct();
        $this->Module = M('admin_role');
    }
    
    /**
     * 列表
     */
    public function index()
    {
        if (IS_AJAX) {
            $rows = $this->Module->field("id, name, status, remark")->where("project_id=".$this->projectId)->select();
            $data = array( 'total' => count($rows), 'rows' => $rows );
            $this->ajaxReturn($data);
        }
        $this->display();
    }
    
    /**
     * 添加
     */
    public function add(){
        if(!IS_POST){
            $this->assign('data', array('status' => 1));
            $this->display('edit');
        }
        
        $data = I('post.');
        if(!empty($data)){
            $data['project_id'] = $this->projectId;
            $result = $this->Module->add($data);
            if($result > 0){
                $id = $this->Module->getLastInsID();
                $this->success(array('id' => $id));
            }
        }
        $this->error('添加失败！');
    }
    
    /**
     * 编辑
     */
    public function edit($id = 0){
        $data = $this->detail($id);
        
        if(IS_POST){
            $post = $_POST;
            $post['id'] = $data['id'];
            unset($post['project_id']);
            $result = $this->Module->save($data);
            if($result > -1){
                $this->success('已保存！');
            }
            $this->error('保存失败！');
        }
        
        $this->assign(array('data' => $data));
        $this->display('edit');
    }
    
    private function detail($id){
        $data = null;
        if(is_numeric($id) && $id > 0){
            $data = $this->Module->find($id);
        }
        
        if(!$data){
            $this->error('角色不存在');
        }else if($data['project_id'] != $this->projectId){
            $this->error('您无权修改他人数据');
        }
        return $data;
    }
        
    /**
     * 菜单授权
     * @param number $role_id
     */
    public function access_menu($id = 0){
        $role_id = $id;
        // 保存授权
        if(IS_POST){
            $data = array();
            $data['id'] = I('post.role_id/d', 0);
            $data['node_id'] = $_POST['node_id'];
            $data['menu_id'] = $_POST['menu_id'];
            $this->Module->save($data);
            $this->success('已保存！');
        }elseif(!is_numeric($role_id) || $role_id < 1){
            $this->error('角色ID不能为空！');
        }
        
        // 展示授权页面
        $menu_list = array();
        $role = $this->detail($role_id);
        $selectedMenu = explode(',', $role['menu_id']);
        $selectedNode = explode(',', $role['node_id']);
        
        $Module = M(C('AUTH_TABLE_MENU'), null);
        $menuList = F('menu', '', DATA_PATH . 'System/');
        $nodeList = F('node', '', DATA_PATH . 'System/');
        
        $disabled = array();    // 被禁用的菜单列表
        $pid = array();
        foreach($menuList as $menu){
            $menuDisabled = in_array($menu['pid'], $disabled) || $menu['status'] == 0;
            if($menuDisabled){
                $disabled[] = $menu['id'];
            }

            $parentIndex = count($menu_list);
            $menu_list[] = array(
                'id' => $menu['id'],
                'parent' => $menu['pid'] == 0 ? '#' : $menu['pid'],
                'text' => $menu['title'],
                'state' => array('disabled' => $menuDisabled, 'selected' => false),
                'checkbox' => array('three_state' => true)
            );

            // 找菜单下的节点
            $hasNode = false;
            foreach($nodeList as $nodeId=>$node){
                if($node['pid'] != $menu['id'] || $node['name'] == 'search'){
                    continue;
                }
                
                $nodeDisabled = $menuDisabled ? true : $node['access'] == -1;
                $menu_list[] = array(
                    'id' => $node['pid'].'_'.$node['id'], // 为了标记我是节点
                    'parent' => $node['pid'],
                    'text' => $node['title'],
                    'state' => array('disabled' => $nodeDisabled, 'selected' => in_array($node['id'], $selectedNode)),
                    'icon' => ' ',
                );
                
                $hasNode = true;
                unset($node_list[$nodeId]);
            }
            
            // 菜单是否选中
            if(!$hasNode && in_array($menu['id'], $selectedMenu)){
                $selected = true;
                foreach($menuList as $menu2){
                    if($menu2['pid'] == $menu['id']){
                        $selected = false;
                        break;
                    }
                }
                
                $menu_list[$parentIndex]['state']['selected'] = $selected;
            }
        }

        $menu_list = json_encode($menu_list, JSON_UNESCAPED_UNICODE);
        $this->assign(array('list' => $menu_list, 'role_id' => $role_id));
        $this->display();
    }
    
    /**
     * 获取所有部门
     */
    public function access_store($role_id = 0){
        if(!is_numeric($role_id) || $role_id < 1){
            $this->error('角色ID不能为空！');
        }
        
        // 保存授权
        if(IS_POST){
            $data = array('store_id' => trim($_POST['store_id'], ','));
            $this->Module->where("id=".$role_id)->save($data);
            $this->success('已保存！');
        }
        
        $role = $this->detail($role_id);
        $selectedStore = explode(',', $role['store_id']);
         
        $list = $this->getStore($selectedStore);
        $data = json_encode($list);
        $this->assign(array('data' => $data, 'role_id' => $role_id));
        $this->display();
    }
    
    private function getStore($selectedStore){
        $cityList = include_once APP_PATH.'Common/Common/CityList.php';
        
        //读取所有门店
        $Module = M('cxy_store');
        $list = $Module->where("status<>-1")->select();
        
        $store_list = array();
        foreach($list as $i=>$item){
            if(!isset($store_list['province_'.$item['province_id']])){
                $store_list['province_'.$item['province_id']] = array(
                    'id' => 'province_'.$item['province_id'],
                    'parent' => '#',
                    'text' => $cityList[$item['province_id']],
                    'icon'  =>  ''
                );
            }
        
            if(!isset($store_list['city_'.$item['city_id']])){
                $store_list['city_'.$item['city_id']] = array(
                    'id' => 'city_'.$item['city_id'],
                    'parent' => 'province_'.$item['province_id'],
                    'text' => $cityList[$item['city_id']],
                );
            }
        
            if(!isset($store_list['county_'.$item['county_id']])){
                $store_list['county_'.$item['county_id']] = array(
                    'id' => 'county_'.$item['county_id'],
                    'parent' => 'city_'.$item['city_id'],
                    'text' => $cityList[$item['county_id']],
                );
            }
        
            if(!isset($store_list['store_'.$item['id']])){
                $store_list['store_'.$item['id']] = array(
                    'id' => 'store_'.$item['id'],
                    'parent' => 'county_'.$item['county_id'],
                    'text' => $item['name'],
                    'icon'  => 'icon-home',
                    'real_id' => $item['id'],
                    'state' => array('disabled' => $item['status'] == 0, 'selected' => in_array($item['id'], $selectedStore))
                );
            }
        }
        
        sort($store_list);
        
        return $store_list;
    }
    
    /**
     * 删除菜单
     */
    public function delete($id = 0){
        if(empty($id)){
            $this->error('删除项不能为空！');
        }
        $result = $this->Module->delete($id);
        $this->success('删除成功！');
    }
}

?>