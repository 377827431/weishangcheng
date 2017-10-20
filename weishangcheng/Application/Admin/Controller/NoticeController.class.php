<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 商城公告
 *
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
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        
        $where = array();
        if(!empty($_GET['title'])){
            $where[] = "title like '%".$_GET['title']."%'";
        }
        $where = count($where) > 0 ? 'WHERE '.implode(' AND ', $where) : '';
        
        $data = array('total' => 100, 'rows' => array());
        $sql = "SELECT * FROM mall_notice {$where} LIMIT {$offset}, {$limit}";
        $Model=M();
        $list = $Model->query($sql);
        foreach ($list as $i=>$item){
            $list[$i]['visible'] =$list[$i]['visible']== 1?"显示" :"不显示";
            $list[$i]['notice'] =$list[$i]['notice']== 1?"置顶" :"不置顶";
            $list[$i]['boutique'] =$list[$i]['boutique']== 1?"精品" :"非精品";
        };
        $data['rows'] = $list;
        $data['total'] = count($Model->query("SELECT * FROM mall_notice"));
        $this->ajaxReturn($data);
    }
    
    /**
     * 添加
     */
    public function add(){
        if(IS_POST){
            $this->save();
        }
        
        $data = array('visible'=>1);
        $this->assign('data', $data);
        $this->assign('canEdit', true);
        $this->display('edit');
    }
    
    /**
     * 编辑
     */
    public function edit(){
        $id = $_REQUEST['id'];
        if(!is_numeric($id)){
            $this->error('ID不能为空');
        }
        $Model = M('mall_notice');
        $data = $Model->find($id);
        if(empty($data)){
            $this->error('信息不存在');
        }
        if(IS_POST){
            $this->save($data);
        }
        $this->assign('data', $data);
        $this->assign('canEdit', true);
        $this->display();
    }
    
    /**
     * 保存
     * @param unknown $exists
     */
    private function save($exists){
        $date=date('Y-m-d H:i:s');
        $remark=str_replace("，", ",",$_POST['remark']);
        $data = array(
            'title' => $_POST['title'],
            'created' => "$date",
            'visible' => $_POST['visible'],
            'remark' => $remark,
            'pv' => $_POST['pv'],
            'title_url' => $_POST['title_url'],
            'notice' => $_POST['notice'],
            'boutique' => $_POST['boutique'],
        );
        $Model = M('mall_notice');
        if($exists){
            $result = $Model->where("id=".$exists['id'])->save($data);
        }else{
            $result = $Model->add($data);
        }
            $this->success('以保存');
    }
    
    /**
     * 删除
     */
    public function delete(){
        $id = $_POST['id'];
        if(empty($id)){
            $this->error('ID不能为空');
        }
        $id = addslashes($id);
        M()->query("DELETE from mall_notice where id in ($id)");
        $this->success('已删除');
    }
}
?>