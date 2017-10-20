<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Admin\Model\AdvancedNewsModel;
use Common\Model\ReplyModel;

/**
 * 微信 - 自动回复
 * @author lanxuebao
 *
 */
class ReplyController extends CommonController
{
    private $Model;
    function __construct()
    {
        parent::__construct();
        $this->Model = new ReplyModel();
    }
    
    public function index(){
    	if(!IS_AJAX){
    		$sql = "SELECT wx_appid.appid, wx_appid.name
		    		FROM project_appid
		    		LEFT JOIN wx_appid ON wx_appid.appid=project_appid.appid
		    		WHERE project_appid.id={$this->projectId}";
		    $appList = $this->Model->query($sql);
    		
		    $this->assign('applist', $appList);
            $this->display();
        }
        
        
        $data = $this->Model->getAll($_GET['appid']);
        $this->ajaxReturn($data);
    }

    private function getAppid($appid){
        $defAppId = C('DEFAULT_APPID');
        
        if(!empty($appid)){
            $appid = addslashes($appid);
            if($appid == $defAppId){
                $this->error('默认公众号不能修改');
            }
            
            $wx_appid = M()->query("select appid from project_appid where id=".$this->projectId." and appid = '".$appid."'");
            if(empty($wx_appid)){
                $this->error('公众号不存在');
            }
        }else{
            $project = get_project($this->projectId);
            $appid = $project['appid'];
            if($appid == $defAppId){
                $this->error('默认公众号不能修改');
            }
        }

        return $appid;
    }
    
    public function add(){
        $appid = $this->getAppid($_REQUEST['appid']);

        if(IS_GET){
            $this->assign('data', json_encode(array(
                'appid' => $appid,
                'rule' => '',
                'is_subscribe' => 0,
                'is_default' => 0,
                'keyword' => array(),
                'content' => array()
            )));
            $this->display('edit');
        }
        
        $result = $this->Model->addReply($_POST);
        if($result < 1){
            $this->error($this->Model->getError());
        }
        
        $this->success("已保存！");
    }
    
    public function edit(){
        $id = $_REQUEST["id"];
        $data = $this->Model->getById($id, $this->projectId);
        $this->getAppid($data['appid']);

        if(IS_POST){
            $result = $this->Model->saveReply($_POST, $id);
            if($result < 1){
                $this->error($this->Model->getError());
            }
            
            $this->success("已保存！");
        }

        $this->assign("data", json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->display();
    }
    
    public function delete(){
        $id = $_POST["id"];
        M("wx_keyword")->where("reply_id IN ({$id})")->delete();
        M("wx_reply")->delete($id);
        
        $this->success("操作成功！");
    }
    
    /**
     * 获取高级图文
     */
    public function getAdvanced(){
    	$where = array('project_id' => $this->projectId);
        $page = I('get.page/d', 1);
        $offset = 20;
        $limit = ($page - 1) * $offset;
        $total = 0;
        
        $Model = new AdvancedNewsModel();
        $data = $Model->getAll($where, $offset, $limit);
        $this->ajaxReturn($data);
    }
}