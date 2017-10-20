<?php
namespace Common\Common;

class Auth{
    private $allMenuList; // 所有菜单
    private $allNodeList; // 我的权限
    private $is_admin;  // 是否为超级管理员
    private $access;
    private $user_id;   // 用户id
    private $current;
    private $menuGroups;
    private $validatedList;
    private $config = array(
        'AUTH_ON'           => true,            // 认证开关
        'AUTH_TYPE'         => 1,               // 认证方式，1为实时认证；2为登录认证。
        'AUTH_MENU_GROP'    => true,            // 是否使用菜单分组
        
        'AUTH_MENU'         => 'admin_menu',       // 菜单表名称
        'AUTH_NODE'         => 'admin_node',       // 菜单权限节点表名称
        'AUTH_ROLE'         => 'admin_role',       // 角色表名称
        'AUTH_ROLE_USER'    => 'admin_user',  // 用户权限表
        
        'AUTH_USER'         => 'user.id',   // 用户session中键
        'AUTH_ADMIN'        => 'user.is_admin' // 是否为超级管理员的session标记
    );
    
    // 无需认证的模块
    private static $public = array(
            'admin'=> array(
            'index'=> array(),
            'api'=> array(),
            'goods'=> array('feedback'),
            'ueditor' => array()
        )
    );
    
    private static $auth;
    public static function get(){
        if(is_null(self::$auth)){
            self::$auth = new Auth();
        }
        
        return self::$auth;
    }
    
    function __construct($userId = null, $isAdmin = null){
        $this->allMenuList    = F('menu', '', DATA_PATH . 'System/');
        $this->allNodeList  = F('node', '', DATA_PATH . 'System/');
        // config.php中的配置覆盖$this->config
        # some code
        if(is_numeric($userId)){
            print_data($userId);
            $this->is_admin = $isAdmin;
            $this->user_id  = $userId;
        }else{
            $this->is_admin = session($this->config['AUTH_ADMIN']);
            $this->user_id  = session($this->config['AUTH_USER']);
        }
    }
    
    /**
     * 获取授权的权限节点
     * @return multitype:multitype:
     */
    public function getAccess(){
        if(!is_null($this->access)){
            return $this->access;
        }
        
        $menu_list = $this->allMenuList;
        $node_list = $this->allNodeList;
        
//         print_data("hello");
        $access = M($this->config['AUTH_ROLE_USER'], null)
                ->alias('access')
                ->field("GROUP_CONCAT(node_id) AS nodes, GROUP_CONCAT(menu_id) AS menus")
                ->join($this->config['AUTH_ROLE']." AS role ON role.id in (access.role)", "INNER")
                ->where("role.status=1 AND access.id = '".$this->user_id."'")
                ->find();

        if(empty($access)){
            $this->access = array(
                'menu_id' => array(),
                'node_id' => array()
            );
            return $this->access;
        }
        
        // 允许访问的权限
        $nodeIdList = $access['nodes'];
        $nodeIdList = explode(',', $nodeIdList);  // 分割成数组
        $nodeIdList = array_filter($nodeIdList);  // 去除空值
        $nodeIdList = array_unique($nodeIdList);  // 去除重复值
        
        // 允许访问的菜单
        $menuIdList = $access['menus'];
        $menuIdList = explode(',', $menuIdList);  // 分割成数组
        $menuIdList = array_filter($menuIdList);  // 去除空值
        $menuIdList = array_unique($menuIdList);  // 去除重复值
        
        foreach($menuIdList as $id){
            $menuId = $menu_list[$id]['pid'];
            while (isset($menu_list[$menuId]) && !in_array($menuId, $menuIdList)){
                $menuIdList[] = $menuId;
                $menuId = $menu_list[$menuId]['pid'];
            }
        }
        
        foreach($nodeIdList as $id){
            $menuId = $node_list[$id]['pid'];
            while (isset($menu_list[$menuId]) && !in_array($menuId, $menuIdList)){
                $menuIdList[] = $menuId;
                $menuId = $menu_list[$menuId]['pid'];
            }
        }

        $this->access = array(
            'menu_id' => $menuIdList,
            'node_id' => $nodeIdList
        );
        return $this->access;
    }

    /**
     * 检查权限
     * @return boolean
     */
    public function check($url = ''){
        if(empty($url)){
            return $this->validated(MODULE_NAME, CONTROLLER_LOWER_NAME, ACTION_NAME);
        }else{
            $module = explode('.', $url);
            if(count($module) == 1){
                return $this->validated(MODULE_NAME, CONTROLLER_LOWER_NAME, $module[0]);
            }
            return $this->validated($module[0], $module[1], isset($module[2]) ? $module[2] : 'index');
        }
    }
    
    public function validated($module = '', $controller = '', $action = ''){
        if($this->is_admin || !$this->config['AUTH_ON']){
            return true;
        }
        $module     = strtolower($module);
        $controller = strtolower($controller);
        $action     = strtolower($action);
        
        if(isset($this->validatedList[$module.$controller.$action])){
            return $this->validatedList[$module.$controller.$action];
        }
        
        $public = self::isPublic($module, $controller, $action);
        if($public){
            $this->validatedList[$module.$controller.$action] = true;
            return true;
        }
        $access     = $this->getAccess();
        
        // 从菜单中获取
        foreach($access['menu_id'] as $menuId){
            $menu = $this->allMenuList[$menuId];
            if($menu['module'] == $module && $menu['controller'] == $controller && $menu['action'] == $action){
                $this->validatedList[$module.$controller.$action] = true;
                return true;
            }
        }
        
        // 从菜单节点中读取
        foreach($access['node_id'] as $nodeId){
            $node = $this->allNodeList[$nodeId];
            if($node['module'] == $module && $node['controller'] == $controller && $node['name'] == $action){
                $this->validatedList[$module.$controller.$action] = true;
                return true;
            }
        }
        
        $this->validatedList[$module.$controller.$action] = false;
        return false;
    }
    
    /**
     * 获取当前访问的信息
     */
    public function getCurrent(){
        if(!is_null($this->current)){
            return $this->current;
        }
        
        $this->current = $this->getMCA(MODULE_NAME, CONTROLLER_LOWER_NAME, ACTION_NAME);
        return $this->current;
    }
    
    public function getMCA($module, $controller, $action){
        $module     = strtolower($module);
        $controller = strtolower($controller);
        $action     = strtolower($action);
        
        // 从菜单中获取
        foreach($this->allMenuList as $menu){
            if($menu['module'] == $module && $menu['controller'] == $controller && $menu['action'] == $action){
                return $menu;
            }
        }
        
        // 从菜单节点中读取
        foreach($this->allNodeList as $node){
            if($node['module'] == $module && $node['controller'] == $controller && $node['name'] == $action){
                return $this->allMenuList[$node['pid']];
            }
        }
        
        return null;
    }
    
    /**
     * 输出左侧菜单
     */
    public function showMenuHtml(){
        $current = $this->getCurrent();
        $groups = $this->getMenuGroups();
        
        $html = '<div class="sidebar"><dl>';
        foreach($groups[$current['gid']]['items'] as $menu){
            $class = $current['id'] == $menu['id'] ? ' active' : '';
            if($menu['pid'] == 0){
                $html .= '    <dt><a href="'.$menu['url'].'" class="sidebar-item'.$class.'"><i class="'.$menu['icon'].'"></i> '.$menu['title'].'</a></dt>';
            }else{
                $html .= '    <dd><a href="'.$menu['url'].'" class="sidebar-item'.$class.'"><i class="icon-pinterest"></i> '.$menu['title'].'</a></dd>';
            }
        }
        
        $html .= '</dl></div>';
        
        echo $html;
    }
    
    /**
     * 获取分组及分组下的菜单
     * 数据已处理好
     */
    public function getMenuGroups(){
        if($this->menuGroups != null){
            return $this->menuGroups;
        }
        
        $link = '';
        $access = $this->getAccess();
        $menuIdList = $access['menu_id'];
        $menu_list = $this->allMenuList;
        $groups = array(
            '1' => array('id' => 1, 'title' => '基础信息', 'link' => $link, 'items' => array()),
            '2' => array('id' => 2, 'title' => '商品管理', 'link' => $link, 'items' => array()),
            '3' => array('id' => 3, 'title' => '订单中心', 'link' => $link, 'items' => array()),
            '4' => array('id' => 4, 'title' => '会员中心', 'link' => $link, 'items' => array()),
            '5' => array('id' => 5, 'title' => '营销中心', 'link' => $link, 'items' => array()),
            '6' => array('id' => 6, 'title' => '数据中心', 'link' => $link, 'items' => array())
        );
        
        foreach($menu_list as $key=>$menu){
            unset($menu_list[$key]);
            
            if($menu['status'] == 2){
                foreach($menu_list as $_key=>$_menu){
                    if($_menu['pid'] == $menu['id']){
                        unset($menu_list[$_key]);
                    }
                }
                continue;
            }
            
            if(!$this->is_admin && !in_array($menu['id'], $menuIdList)){
                continue;
            }
            
            if($groups[$menu['gid']]['link'] == $link && $menu['url'] != ''){
                $groups[$menu['gid']]['link'] = $menu['url'];
            }
            $groups[$menu['gid']]['items'][] = $menu;
        }
        
        $this->menuGroups = $groups;
        return $this->menuGroups;
    }
    
    public function showMenuGroup(){
        $current = $this->getCurrent();
        $list = $this->getMenuGroups();
        $html  = '';
        foreach ($list as $index=>$item){
            $html .= '<li'.($current['gid'] == $item['id'] ? ' class="active"' : '').'>';
            $html .= '<a href="'.$item['link'].'">'.$item['title'].'</a>';
            
            if(count($item['items']) > 0){
                $nav = '';
                $noend = false;
                $width = 0;
                foreach($item['items'] as $menu){
                    $url = $menu['url'] ? $menu['url'] : 'javascript:;';
                    if($menu['pid'] == 0){
                        $width += 111;
                        if($noend){
                            $nav .= '</ul></div>';
                        }
                        $noend = true;
                        $nav .= '<div class="nav-item"><a href="'.$url.'" class="nav-title">'.$menu['title'].'</a>';
                        $nav .= '<ul class="sub-nav">';
                    }else{
                        $nav .= '<li><a href="'.$url.'">'.$menu['title'].'</a></li>';
                    }
                }
                $html .= '<div class="group-nav" style="width:'.$width.'px">'.$nav.'</ul></div></div>';
            }
            
            $html .= '</li>';
        }
        
        return $html;
    }
    
    public function showTollbar($module, $controller, $action = 'index'){
        $current = $this->getCurrent();
        
        if($current['module'] != $module || $current['controller'] != $controller || $current['action'] != $action){
            $current = null;
            foreach($this->allMenuList as $menu){
                if($menu['module'] == $module && $menu['controller'] == $controller && $menu['action'] == $action){
                    $current = $menu;
                    break;
                }
            }
        }
        
        if(is_null($current)){
            return;
        }
        
        $access = $this->getAccess();
        
        $nodeList = $this->allNodeList;
        $html = '';
        $btn_group = 0;
        foreach($nodeList as $node){
            // 不显示的按钮
            if($node['visible'] != 1 || $node['access'] == -1){
                continue;
            }
            
            if($node['pid'] == $current['id'] && ($this->is_admin || $node['name'] == 'search' || in_array($node['id'], $access['node_id']))){
                if($btn_group != $node['groups']){
                    if($btn_group > 0){
                        $html .= '</div>';
                        $html .= '<div class="btn-group">';
                    }
                    $btn_group = $node['groups'];
                }
                
                $html .= '<button type="'.($node['name'] == 'search' ? 'submit' : 'button').'" data-name="'.$node['name'].'" class="btn btn-default"';
                $html .= ' data-event-type="'.$node['event_type'].'" data-event-value="'.$node['event_value'].'" data-target="'.$node['target'].'">';
                        $html .= '<i class="'.$node['icon'].'"></i> '.$node['title'].'</button>';
            }
        }
        
        if($html != ''){
            $html = '<div class="btn-list"><div class="btn-group">'.$html.'</div></div>';
        }
        echo $html;
    }
    
    public static function isPublic($module_name = '', $controller_name = '', $action_name = '') {
        if($module_name == ''){
            $module_name = strtolower(MODULE_NAME);
            $controller_name = strtolower(CONTROLLER_LOWER_NAME);
            $action_name = strtolower(ACTION_NAME);
        }
        
        $public = self::$public;
        if(isset($public[$module_name])
            && isset($public[$module_name][$controller_name])){
            if(is_array($public[$module_name][$controller_name])){
                if(count($public[$module_name][$controller_name]) == 0 
                    || in_array($action_name, $public[$module_name][$controller_name])){
                    return true;
                }
            }
        }
        
        return false;
    }
}
?>