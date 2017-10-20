<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 赠品
 *
 * @author wangbaofu
 */
class GiveawayController extends CommonController
{
    private $Model;
    function __construct(){
        parent::__construct();
        $this->Model = D('MallGiveaway');
    }
    /**
     * 赠品列表
     */
    public function index(){
        if(IS_AJAX){
            $offset = I('get.offset', 0);
            $limit = I('get.limit', 50);
            
            if($_GET["title"]){
                $where["away.title"] = array("like","%".$_GET["title"]."%");
            }
            
            $data = $this->Model->getAll($where,$offset,$limit);
            $this->ajaxReturn($data);
        }
        $this->display();
    }
    
    /**
     * 添加赠品
     */
    public function add(){
        if(IS_POST){
            $data = $_POST;
            $result = $this->Model->insert($data);    
            if($result > 0){
                $this->success("添加成功！");
            }
            
            $this->error($this->Model->getError());
        }
        $this->display();
    }
    
    /**
     * 编辑赠品
     */
    public function edit(){
        $id = $_GET["id"];
        if(IS_POST){
            $data = $_POST;
            $result = $this->Model->update($data);    
            if($result > 0){
                $this->success("已保存");
            }
            
            $this->error($this->Model->getError());
        }
        
        $data = $this->Model->getOne($id);
        $this->assign("data",$data);
        $this->display();
    }
    
    /**
     * 结束活动
     */
    public function finish($ids){
        $data["status"] = 0;
        $result = $this->Model->where("id IN ({$ids})")->save($data);
        if($result > 0){
            $this->success("已保存");
        }
        $this->error("活动已结束！");
    }
    
}
?>