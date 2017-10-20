<?php
namespace H5\Controller;

use Common\Common\CommonController;
/**
* @author 
* 模块 : 公告
*/
class NoticeController extends CommonController
{
    public function index(){
        if(IS_AJAX){
            $this->showList();
        }
        $this->display();
    }
    
    private function showList(){
        $Model=M();
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $lists= $Model->query("SELECT * FROM mall_notice where visible=1 order by notice desc,boutique desc  LIMIT {$offset}, {$limit}");
        $list = array();
        foreach ($lists as $i=>$item){
            if($item['boutique']){
                $item['remark'] = '精品,'.$item['remark'];
            }
            if($item['notice']){
                $item['remark'] = '置顶,'.$item['remark'];
            }
            $tag = array_filter(explode(',', $item['remark']));
            $list[] = array(
                'id'    => $item['id'],
                'title' => $item['title'],
                'url'  => $item['title_url'],
                'created' => substr($item['created'], 0, 10),
                'pv'    => $item['pv'],
                'tag'   => $tag
            );
        }
        $this->ajaxReturn($list);
    }
    
    public function detail(){
        $id = $_REQUEST['id'];
        if(!is_numeric($id)){
            $this->error('ID不是数字');
        }
        M()->query("update mall_notice set pv=pv+1 where id={$id}");
        redirect($_GET['redirect']);
    }
    
    public function pv(){
        $id = $_GET['id'];
        // 增加数据库的访问量
        # some code
        exit();
    }
}
?>