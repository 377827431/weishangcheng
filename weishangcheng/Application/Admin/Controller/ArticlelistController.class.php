<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 文章列表
 * 
 * @author lanxuebao
 *        
 */
class ArticlelistController extends CommonController
{
    private $Model;

    function __construct()
    {
        parent::__construct();
        $this->Model = M('article_list');
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
            $channel_id = $type_id = array();
            $channel = M()->query("select id,name from article_channel ");
            foreach ($channel as $i=>$item){
                $channel_id[$item['id']] = $item['name'];
            }
            $type = M()->query("select id,name from article_type ");
            foreach ($type as $i=>$item){
                $type_id[$item['id']] = $item['name'];
            }
            foreach ($rows as $i=>$item){
                $rows[$i]['visible'] == 1 ? $rows[$i]['visible'] ='是' : $rows[$i]['visible'] ='否';
                $rows[$i]['channel_id'] = $channel_id[$item['channel_id']];
                $rows[$i]['type_id'] = $type_id[$item['type_id']];
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
            $data['created'] = date('Y-m-d H:i:s');
            $detail = strip_tags($data['detail']);
            if(empty($data['url']) || empty($data['detail'])){
                $this->error('跳转地址和文章内容不能都为空');
            }
            if(empty($data['abstract'])){
                if(!empty($detail)){
                    $data['abstract'] = substr($detail, 0 , 6);
                }
            }
            unset($data['detail']);
            $data['project_id'] = session('user.project_id');
            $result = $this->Model->add($data);
            if(!empty($detail)){
                $list = $this->Model->query("INSERT INTO article_content (id,content) values('".$result."' ,'".$detail."')");
            }
            if($result > 0){
                $this->success('添加成功!');
            }
            $this->error('添加失败！');
        }
        $type = M()->query("select name from article_type ");
        foreach ($type as $i=>$item){
            $type_id[$i] = $item['name'];
        }
        $channel = M()->query("select name from article_channel ");
        foreach ($channel as $i=>$item){
            $channel_id[$i] = $item['name'];
        }
        $list = $this->article_list(false);
        $this->assign(array('data' => $data,'type_id' => $type_id,'channel_id' => $channel_id,'list' => $list ));
        $this->display('edit');
    }
    
    /**
     * 编辑
     */
    public function edit($id = 0){
         $id = $_REQUEST['id'];
        if(!is_numeric($id)){
            $this->error('ID不能为空');
        }
        $Model = M('article_list');
        $data = $Model->find($id);
        $list = M('article_content')->find($id);
        $data['detail'] = $list['content'];
        if(IS_POST){
            $project_id = session('user.project_id');
            $edit_id = $_POST['id'];
            $coupon = array(
                'title'       => $_POST['title'],
                'project_id' => $project_id,
                'author'       => $_POST['author'],
                'abstract'      => $_POST['abstract'],
                'channel_id'      => $_POST['channel_id'],
                'type_id'     => $_POST['type_id'],
                'pv' => $_POST['pv'],
                'visible'  => $_POST['visible'],
                'url'  => $_POST['url'],
            );
            $Model->where('id='.$edit_id)->save($coupon);
            $detail = strip_tags($_POST['detail']);
            $result = $Model->execute("update article_content set content ='". $detail."' where id = ".$edit_id); 
            $Model->commit();
            $this->success('编辑文章列表成功');
        }
        $type = M()->query("select name from article_type ");
        foreach ($type as $i=>$item){
            $type_id[$i] = $item['name'];
        }
        $channel = M()->query("select name from article_channel ");
        foreach ($channel as $i=>$item){
            $channel_id[$i] = $item['name'];
        }
        $this->assign(array(
            'data'    => $data,
            'type_id' => $type_id,
            'channel_id' => $channel_id
        ));
        $this->display('edit');
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
            $list = $this->Model->query("delete from article_content where id = ".$id);
                if(empty($list)){
                    $this->success('删除成功！','articlelist');
                }
                $this->error('删除失败！','articlelist');
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