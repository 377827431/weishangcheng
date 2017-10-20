<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 会员黑名单--管理
 * @author lanxuebao
 *
 */
class BlackController extends CommonController
{
    public $authRelation = array(
        'add'       => 'admin.member.black',
    );
    
    /**
     * 会员黑名单列表
     */
    public function index(){
    	if(!IS_AJAX){
        	$this->display();
    	}
    	
    	$data = array('total' => 0, 'rows' => array());
    	$page = I('get.page', 1, '/^\d+$/');
    	$offset = I('get.offset', 0);
    	$limit = I('get.limit', 50);

    	$where = array();
    	if(is_numeric($_GET['kw'])){
    	    $_temp = "(black.mid={$_GET['kw']}";
    	    if(preg_match('/^1[3|4|5|7|8]\d{9}$/', $_GET['kw'])){
    	        $_temp .= " OR member.mobile='{$_GET['kw']}'";
    	    }
    	    $where[] = $_temp.')';
    	}
    	
    	if(is_numeric($_GET['status'])){
    	    $where[] = "black.expires_in ".($_GET['status'] == 1 ? '>' : '<')." ".NOW_TIME;
    	}
    	
    	$where = count($where) > 0 ? "WHERE ".implode(' AND ', $where) : "";

    	$model = M();
    	$sql = "SELECT COUNT(*) AS total
    	        FROM member_black AS black
    	        LEFT JOIN member ON member.id=black.mid
    	        {$where}";
    	$data['total'] = $model->query($sql)[0]['total'];
    	
    	if($data['total'] > 0){
    	    $sql = "SELECT black.*, member.nickname, member.mobile
            	    FROM member_black AS black
            	    LEFT JOIN member ON member.id=black.mid
            	    {$where}
            	    ORDER BY id DESC
            	    LIMIT {$offset}, {$limit}";
            $list = $model->query($sql);
            
            foreach ($list as $i=>$item){
                $item['days'] = round(($item['expires_in'] - $item['created'])/86400)+1;
                $item['status'] = $item['expires_in'] > NOW_TIME ? '冻结中' : '已解封';
                $item['created'] = date("Y-m-d H:i", $item['created']);
                $item['expires_in'] = date("Y-m-d H:i", $item['expires_in']);
                $data['rows'][] = $item;
            }
    	}
    	$this->ajaxReturn($data);
    }
    
    /**
     * 解封
     */
    public function unblock(){
        if(empty($_POST['id'])){
            $this->error("ID不能为空");
        }

        $username = $this->user('username');
        $username = addslashes($username);
        $id = addslashes($_POST['id']);
        M()->execute("UPDATE member_black SET expires_in=".NOW_TIME.", username='{$username}' WHERE id IN ({$id}) AND expires_in>".NOW_TIME);
        $this->success('已解封');
    }
    
    /**
     * 删除
     */
    public function delete(){
    	if(empty($_POST['id'])){
    	    $this->error("ID不能为空");
    	}
    	
    	$id = addslashes($_POST['id']);
    	M("member_black")->delete($id);
    	$this->success();
    }
    
    /**
     * 加入黑名单
     */
    public function add(){
        $mid = $_REQUEST['mid'];
        if(empty($mid)){
            $this->error('会员ID不能为空');
        }
        
        // 投放页面
        if(!IS_POST){
            $this->assign(array(
                'mid'   => $mid,
                'expires_in' => date('Y-m-d H:i:s', strtotime('+1 month'))
            ));
            $this->display();
        }

        // 保存数据
        $expires = strtotime($_POST['expires_in']);
        if($expires <= NOW_TIME){
            $this->error('解封时间异常');
        }

        $username = $this->user('username');
        $username = addslashes($username);
        $remark = addslashes($_POST['remark']);
        $idList = explode(',', $mid);
        $dataList = array();
        foreach ($idList as $mid){
            if(!is_numeric($mid)){
                $this->error('会员ID异常');
            }
            
            $dataList[] = array(
                'mid'   => $mid,
                'create_time' => NOW_TIME,
                'expires_time' => $expires,
                'username' => $username,
                'remark'    => $remark
            );
        }
        
        M('member_black')->addAll($dataList);
        $this->success('已加入黑名单');
    }
}