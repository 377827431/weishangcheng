<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Org\Util\String2;

/**
 * 邀请码
 *
 */
class InvitationController extends CommonController
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
        $where = count($where) > 0 ? 'WHERE '.implode(' AND ', $where) : '';
        
        $data = array('total' => 100, 'rows' => array());
        $sql = "SELECT pc.title,mic.id,mic.type,mic.created,mic.expires_in,mic.creator,mic.quantity,mic.used FROM member_invitation_code as mic left join project_card as pc on mic.card_id = pc.id {$where} LIMIT {$offset}, {$limit}";
        $Model=M();
        $list = $Model->query($sql);
        $data['rows'] = $list;
        $data['total'] = count($Model->query("SELECT * FROM member_invitation_code"));
        if(!empty($data['rows'])){
            foreach ($data['rows'] as $i => $items){
                $data['rows'][$i]['type'] = $items['type']==0 ? '不通用':'通用';
                $data['rows'][$i]['timeout'] = date('Y-m-d',$items['created']).'  -  '.date('Y-m-d',$items['expires_in']);
            }
        }
        $this->ajaxReturn($data);
    }
    
    /**
     * 添加
     */
    public function add(){
        if(IS_POST){
            if($_POST['type'] == 0){
                $this->save();
            }
            $this->save_other();
        }
        $card = M()->query("select id,title from project_card ");
        foreach ($card as $i=>$item){
            $card_id[$item['id']] = $item['title'];
        }
        $creator = session('user.nick');
        $data = array();
        $this->assign('data', $data);
        $this->assign('creator', $creator);
        $this->assign('card_id', $card_id);
        $this->assign('canEdit', true);
        $this->display('add');
    }
    
    /**
     * 编辑
     */
    public function edit(){
        $id = $_REQUEST['id'];
        if(!is_numeric($id)){
            $this->error('ID不能为空');
        }
        $Model = M();
        $data = $Model->query("select * from member_invitation_code where id =".$id);
        if(empty($data)){
            $this->error('信息不存在');
        }
        $data = $data[0];
        if(IS_POST){
            $this->save($data);
        }
        foreach ($data as $i => $items){
            $data['created'] = date('Y-m-d H:i:s',mktime($items['created']));
            $data['expires_in'] = date('Y-m-d H:i:s',mktime($items['expires_in']));
        }
        $card = M()->query("select id,title from project_card ");
        foreach ($card as $i=>$item){
            $card_id[$item['id']] = $item['title'];
        }
        $this->assign('data', $data);
        $this->assign('card_id', $card_id);
        $this->assign('canEdit', true);
        $this->display('edit');
    }
    
    /**
     * 不通用保存
     * @param unknown $exists
     */
    private function save($exists){
        $date=date('Y-m-d H:i:s');
        $data = array(
            'card_id' => $_POST['card_id'],
            'create' => strtotime($date),
            'quantity' => $_POST['quantity'],
            'code'=>'123456',
            'used' => $_POST['used'],
            'creator' => $_POST['creator'],
            'expires_in' => strtotime($_POST['expires_in']),
            'type' => $_POST['type'],
        );
        $Model = M('member_invitation_code');
        if($exists){
            $result = $Model->where("id=".$exists['id'])->save($data);
        }else{
            $result = $Model->add($data);
        }
        $id = $result;
        $count = $_POST['quantity'];
        $model = new String2();
        $data_rand = String2::buildCountRand($count, '6 ', 1);
        foreach ($data_rand as $i=>$item){
            $result = $Model->execute("INSERT IGNORE INTO member_invitation_record set code = '{$item}', id = {$id},card_id = {$data['card_id']}");
        }
            $this->success('以保存');
    }
    
    /**
     * 通用保存
     * @param unknown $exists
     */
    private function save_other($exists){
        $date=date('Y-m-d H:i:s');
        $data = array(
            'card_id' => $_POST['card_id'],
            'create' => $_POST['created'],
            'quantity' => $_POST['quantity'],
            'code' => $_POST['code'],
            'used' => $_POST['used'],
            'creator' => $_POST['creator'],
            'expires_in' => $_POST['expires_in'],
        );
        $Model = M('member_invitation_code');
        if($exists){
            $result = $Model->where("id=".$exists['id'])->save($data);
        }else{
            $result = $Model->add($data);
        }
        $result = $Model->execute("INSERT IGNORE INTO member_invitation_record set code = '{$data['code']}', id = {$result},card_id = {$data['card_id']}");
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
        M()->query("DELETE from member_invitation_code where id in ($id)");
        $this->success('已删除');
    }
    
    /**
     * 导出产品
     */
    public function invitation_out(){
        $id = $_REQUEST['id'];
        $Model = D('Invitation');
        $Model->export($id);
    }
}
?>