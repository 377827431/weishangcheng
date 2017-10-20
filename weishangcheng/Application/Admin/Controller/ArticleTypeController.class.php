<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 文章类型
 * 
 * @author lanxuebao
 *        
 */
class ArticleTypeController extends CommonController
{
    private $Model;

    function __construct()
    {
        parent::__construct();
        $this->Model = M('article_type');
    }
    
    /**
     * 工具栏按钮列表
     * @param number $menu_id
     */
    public function index($menu = 0){
        if(IS_POST){
            $rows = $this->Model->where("id=".$menu)->select();
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
            $Module = $this->Model;
            $result = $Module->add($data);
            if($result > 0){
                $this->success('添加成功！');
            }
            $this->error('添加失败！');
        }
        $this->assign('data', $data);
        $this->display('edit');
    }
    
    /**
     * 工具栏 - 修改按钮
     * @param number $menu_id
     */
    public function edit($id = 0){
        $data = M()->query("select *from article_type where id=".$_REQUEST['id']);
        $data = $data[0];
        if(IS_POST){
            $data = I('post.');
            $result =  M()->execute("update article_type set content=".$data['content']." where id=".$_REQUEST['id']);
            if($result >= 0){
                $this->success('修改成功！');
            }
            $this->error('修改失败！');
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
            if($result >= 0){
                $this->success('已删除！');
            }
        }
        
        $this->error('删除失败！');
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