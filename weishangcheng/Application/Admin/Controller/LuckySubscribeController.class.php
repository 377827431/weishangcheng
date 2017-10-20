<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 幸运会员
 * 从上个月关注的会员中抽取几名幸运者
 * 
 * @author lanxuebao
 *        
 */
class LuckySubscribeController extends CommonController
{
    public function index(){
        if(IS_AJAX){
            $offset = I('get.offset', 0);
            $limit = I('get.limit', 50);
            
            if($_GET["lucky_date"]){
                $where["als.lucky_time"] = array("egt",$_GET["lucky_date"]);
            }
            if($_GET["nickname"]){
                $where["user.nickname"] = array("like","%".$_GET["nickname"]."%");
            }
            
            $Model = D('LuckySubscribe');
            $data = $Model->getAll($where,$offset,$limit);
            
            $this->ajaxReturn($data);
        }
        
        $prev = $this->getlastMonthDays();
        $this->assign('parameter', array(
            'start_time' => $prev[0],
            'end_time' => $prev[1],
        ));
        $this->display();
    }
    
    private function getlastMonthDays(){
        $timestamp=time();
        $firstday=date('Y-m-01',strtotime(date('Y',$timestamp).'-'.(date('m',$timestamp)-1).'-01')).' 00:00:00';
        $lastday=date('Y-m-d',strtotime("$firstday +1 month -1 day")).' 23:59:59';
        return array($firstday,$lastday);
    }
    
    public function add(){
        $parameter = $_POST;
        $Model = D('LuckySubscribe');
        $user = $Model->getLucky($parameter);
        if(empty($user)){
            $this->error($Model->getError());
        }
        
        $this->success($user);
    }
    
    public function delete(){
        $id = I("post.id");
        M("active_lucky_subscribe")->where("id IN ({$id})")->delete();
        $this->success('删除成功！');
    }
}
?>