<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 文章内容
 * 
 * @author lanxuebao
 *        
 */
class ArticleContentController extends CommonController
{
    private $Model;
    function __construct()
    {
        parent::__construct();
        $this->Model = M('article_content');
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
        $Module = $this->Model;
        $id = $_REQUEST['id'];
        $data = $Module->query("select *from article_content where id=".$id);
        $data = $data[0];
        if(IS_POST){
            $data = I('post.');
            $result =  M()->execute("update article_content set content='".$data['content']."' where id=".$data['id']);
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
}

?>