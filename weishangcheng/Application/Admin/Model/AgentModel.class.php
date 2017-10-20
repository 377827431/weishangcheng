<?php
namespace Admin\Model;

use Think\Model;

/**
 * 代理管理
 * @author wangjing
 *
 */
class AgentModel extends Model
{
    protected $tableName = 'project_card';
    
    /*
     * 所有代理级别
     */
    public function getAllLevel(){
        $rows = $this
                ->order("id asc")
                ->select();
        return $data = array("rows" => $rows);
    }
    
    /*
     * 编辑代理级别
     */
    public function updateLevel($data){
        if(empty($data['id']) || empty($data['title'])){
            $this->error = '提交数据不能为空';
            return -1;
        }
        if($data['once_amount'] < 0 || $data['parent_cost'] < 0 || $data['indirect_cost'] < 0 || $data['third_cost'] < 0 || $data['platform_cost'] < 0 || $data['sum_amount'] < 0){
            $this->error = '金额不能小于0';
            return -1;
        }
        if(bcsub($data['once_amount'], ($data['parent_cost'] + $data['indirect_cost'] + $data['third_cost'] + $data['platform_cost']), 2) < 0){
            $this->error = '一次性充值金额不能小于  推荐人费用  + 平台获利的总和！';
            return -1;
        }
        
        $this->save($data);
        
//         $agent = $this->find($data['id']);
//         if($agent['level'] == 3){
//             $this->execute("UPDATE agent SET price_title='%s' WHERE level=0", $agent['price_title']);
//         }
        return 1;
    }
    
    /*验证数值*/
    protected function checkVal($val){
        if($val < 0){
            return false;
        }else{
            return true;
        }
    }
    
    /*获取单条代理级别数据*/
    public function getOneLevel($id){
        $list = array();
        $list = $this->find($id);
        return $list;
    }
}
?>