<?php 
namespace Common\Model;

use Think\Model;
class SkuModel extends Model{
    protected $tableName = 'mall_sku';
    
    public function getAll($projectId){
        // 获取用户自定义的sku
        $data = $this->where("project_id IN (0, {$projectId}) AND pid=0")->select();
        foreach($data as $item){
            $list[$item['id']] = $item['text'];
        }
        return $list;
    }
}
?>