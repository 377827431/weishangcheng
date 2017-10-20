<?php
namespace Admin\Controller;

use Common\Common\CommonController;
/**
 * 用户管理
 * 
 * @author 兰学宝
 *        
 */
class UserController extends CommonController
{

    private $Module;
    private $accessTable;
    private $roleTable;

    function __construct()
    {
        parent::__construct();
        $this->Module = M('admin_user');
        $this->accessTable = 'admin_user';
        $this->roleTable = 'admin_role';
    }
    
    /**
     * 列表
     */
    public function index()
    {
        if (IS_AJAX) {
            $where = array();
            if (strlen($_GET['nick']) > 0) { $where['users.nick'] = array( 'like', '%' . $_GET['nick'] . '%' ); }
            if (strlen($_GET['username']) > 0) { $where['users.username'] = array( 'like', '%' . $_GET['username'] . '%'); }
            if(is_numeric($_GET['role'])){ $where['role.id'] = $_GET['role'];  }
            if(is_numeric(session('user.shop_id'))){ $where['users.shop_id'] = session('user.shop_id');  }
            $rows = $this->Module
                         ->alias('users')
                         ->field("users.id, users.nick, users.username, users.status,group_concat(role.name ORDER BY role.id ASC) AS role_name,shop.name AS shop_name")
                         ->join("admin_role AS role ON role.id in (users.role)")
                         ->join("shop ON users.shop_id=shop.id")
                         ->where($where)
                         ->group("users.id")
                         ->order("users.shop_id, users.id")
                         ->select();
            $this->ajaxReturn($rows);
        }
        $shop = $this->shops();
        $this->assign(array(
            'role_list'     => $this->roleList(),
            'shop'         => $shop
        ));
        $this->display();
    }
    
    /**
     * 获取角色列表
     */
    private function roleList(){
       return $this->Module->query("SELECT id, `name`, `status` FROM admin_role");
    }
    
    /**
     * 添加用户
     */
    public function add(){
        if(IS_POST){
            $data = $_POST;
            if(!is_numeric($data['username'])){
                $this->error('必须11位手机号');
            }
            $exists = $this->Module->where("username='{$data['username']}'")->count();
            if(!empty($exists)){
                $this->error('该账号已存在！');
            }
            
            $data['password'] = md5($data['password']);
            $result = $this->Module->add($data);
            if($result > 0){
                $this->success('添加成功！');
            }
            $this->error('添加失败！');
        }
        
        $shop = $this->shops();
        $my_shop = $this->user('shop_id');
        
        $this->assign(array(
            'shop'    =>  $shop,
            'my_shop' =>    $my_shop
        ));
        $this->display();
    }
    
    /**
     * 编辑用户
     */
    public function edit($id = 0){
        if(IS_POST){
            $data = $_POST['data'];
            $sid = $_POST['sid'];
            $aid = implode(',', $sid);
            $data['id'] = intval($data['id']);
            $data['association_id'] = $aid;
            
            if($data['id'] <= 0){
                $this->error('数据ID异常！');
            }
            $result = $this->Module->save($data);
            if($result >= 0){
                $this->success('已修改！');
            }
            $this->error('修改失败！');
        };
        $id = $_REQUEST['id'];
        $data = $this->Module->query("SELECT * from admin_user where id = {$id}");
        if(empty($data)){
            $this->error('数据不存在或已被删除！');
        }
        $data = $data[0];
        $mySid = M('admin_user')->field('association_id')->find($id);
        $sid = explode(',',$mySid['association_id']);
        $shop = $this->shops();
        $this->assign(array(
            'sid'     => $sid,
            'data'    => $data,
            'shop'    => $shop
            ));
        $this->display();
    }
    
    /**
     * 删除用户
     */
    public function delete($id = 0){
        if(empty($id)){
            $this->error('删除项不能为空！');
        }
        
        $id = addslashes($id);
        $result = $this->Module->execute("DELETE FROM admin_user WHERE id IN ({$id}) AND can_del=1");
        $this->success('删除成功！');
    }
    
    /**
     * 授权
     */
    public function role(){
        $user_id = I('get.id/d', 0);
        if(IS_GET){
            if(!is_numeric($user_id) || $user_id < 1){
                $this->error('请选择授权用户！');
            }
        }else{
            $user_id = $_POST['user_id'];
        }
        
        if(IS_POST){
            $roles = I('role_id');
            foreach ($roles as $role) {
                if (!is_numeric($role)) {
                    $this->error('请正确提交');
                }
            }
            $this->Module->where(array('id'=>$user_id))->save(array('role'=>implode(',', $roles)));
            $this->success('已保存！');
        }
        
        $my_role = $this->myRoleList($user_id);
        $role_list = $this->Module->query("SELECT id, `name`, `status` FROM ".$this->roleTable);
   
        $this->assign(array('user_id' =>$user_id, 'role_list' => $role_list, 'my_role' => $my_role));
        $this->display();
    }
    
    /**
     * 获取我的角色集合
     */
    private function myRoleList($user_id){
        $my_role = $this->Module->query("SELECT role FROM ".$this->accessTable." WHERE id=".$user_id);
        $list = explode(',', $my_role[0]['role']);
        return $list;
    }
    
    /**
     * 修改密码
     * @param number $user_id
     */
    public function password(){
        $user_id = I('get.id');
        if(IS_POST){
            $password = I('post.password');
            if($password == ''){
                $this->error('新密码不能为空！');
            }
            if($password != $_POST['password2']){
                $this->error('两次密码不一致！');
            }
            
            $data = array();
            $data['id'] = I('post.id/d', 0);
            $data['password'] = md5($password);
            $this->Module->save($data);
            
            $this->success('修改成功！');
        }
        
        $data = $this->Module->field("id, username, nick")->find($user_id);
        if(empty($data)){
            $this->error('用户不存在或已被删除！');
        }
        $this->assign('data', $data);
        $this->display();
    }
}

?>