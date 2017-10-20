<?php
namespace H5\Controller;

/**
 * 活动
 * 
 * @author lanxuebao
 *        
 */
class ActiveController
{
    private $active = null;
    function __construct(){
        $activeId = is_numeric($_REQUEST['active_id']) ? $_REQUEST['active_id'] : $_REQUEST['id'];
        if(!is_numeric($activeId)){
            E('活动不存在');
        }
        
        // 判断活动是否存在
        $Model = M('active');
        $active = $Model->find($activeId);
        if(empty($active)){
            E('活动不存在');
        }
        
        $className = '\H5\Active\Active_'.$activeId;
        $this->active = new $className($active);
    }
    
    public function __call($action, $params){
        if(method_exists($this->active, $action)){
            call_user_func_array(array($this->active, $action), array());
        }else{
            E('方法:'.$action.'不存在');
        }
    }
}
?>