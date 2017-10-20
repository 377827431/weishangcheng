<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 添加修改微信公众账号
 *
 * @author yushiyang
 *
 */
class TransfersauthController extends CommonController
{
    private $Model;

    function __construct(){
        parent::__construct();
        $this->Model = M("transfers_auth");
    }

    public function index(){
    	$offset = I('get.offset', 0);
        $limit = I('get.limit', 20);
        $data = I("get.");
        $where = array();
        if(!empty($data['shop_name'])){
        	$where['s.name'] = array("like","%".$data["shop_name"]."%");
        }
        if(!empty($data['au_username'])){
        	$where['au.username'] = array("like","%".$data["au_username"]."%");
        }
        if(!empty($data['created_str'])){
        	$where['FROM_UNIXTIME(ta.created,"%Y-%m-%d")'] = $data['created_str'];
        }
        if(is_numeric($data['status'])){
        	$where['ta.status'] = $data['status'];
        }
        if(is_numeric($data['card_no'])){
            $where['ta.card_no'] = $data['card_no'];
        }
        if(!empty($data['card_name'])){
            $where['ta.card_name'] = $data['card_name'];
        }
    	if(IS_AJAX){
            $rows = array();
            $total = $this->Model->alias("ta")
	                    ->join("shop AS s ON s.id = ta.shop_id")
	                    ->join("admin_user AS au ON au.id = ta.id")
	                    ->join("project AS p ON p.id = ta.project_id")->where($where)->count();
	                //print_data(M()->getLastSql());
            if($total > 0){
	            $rows =  $this->Model
	                    ->alias("ta")
	                    ->field("ta.*,s.name AS shop_name,p.name AS pro_name,au.username AS au_username")
	                    ->join("shop AS s ON s.id = ta.shop_id")
	                    ->join("admin_user AS au ON au.id = ta.id")
	                    ->join("project AS p ON p.id = ta.project_id")
                        ->where($where)
	                    ->order("ta.status, ta.created desc")
                        ->limit($offset,$limit)
                        ->select();
	            foreach ($rows as $k => $v) {
	            	$rows[$k]['created_str'] = date("Y-m-d H:i:s",$v['created']);
	            	if($v['modify'] == 0){
	            		$rows[$k]['modify_str'] = "";
	            	}else{
	            		$rows[$k]['modify_str'] = date("Y-m-d H:i:s",$v['modify']);
	            	}
	            }
            }
            $this->ajaxReturn(array('rows' => $rows, 'total' => $total));
        }
        $this->assign("data",$data);
        $this->display();
    }

    public function checkinfo(){
    	$data = I("post.");
    	//print_data($data);
    	$upd = array("status" => $data['status'],"modify" => time());
    	$result = $this->Model->where("id = %d",$data['id'])->save($upd);
    	if($result <= 0){
    		$this->error("审批失败！");
    	}
    	$this->success("审批成功！");
    }
}
?>