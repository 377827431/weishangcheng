<?php
namespace Admin\Model;

use Think\Model;
use Common\Model\BaseModel;

/**
 * 赠品管理
 * @author lanxuebao
 *
 */
class MallGiveawayModel extends BaseModel
{
    protected $tableName = 'mall_giveaway';
    
    /**
     * 赠品列表
     */
    public function getAll($where,$offset,$limit){
        $rows = array();
        $total = $this->alias("away")
                      ->where($where)
                      ->limit($offset,$limit)
                      ->count();
        
        if($total > 0){
            $rows = $this->alias("away")
                         ->field("away.id,away.title,away.status,away.give_num,away.num,
                                  CONCAT(away.start_time,'<br>',away.end_time) AS active_time,
                                  g.title AS product_title")
                         ->join("mall_product AS pro ON away.product_id=pro.id")
                         ->join("mall_goods AS g ON pro.goods_id=g.id")
                         ->where($where)
                         ->limit($offset,$limit)
                         ->order("away.start_time DESC")
                         ->select();
        }
        
        return $data = array("total"=>$total, "rows"=>$rows);
    }
    
    /**
     * 根据id获取赠品
     */
    public function getOne($id){
        $row = $this->alias("away")
                     ->field("away.*,g.title AS product_title,pro.sku_json,pro.stock AS product_stock")
                     ->join("mall_product AS pro ON away.product_id=pro.id")
                     ->join("mall_goods AS g ON pro.goods_id=g.id")
                     ->where("away.id='{$id}'")
                     ->find();
        
         $row["sku_name"] = $this->toSpecName($row["sku_json"]);
         
         return $row;
    }
    
    /**
     * 添加赠品
     */
    public function insert($data = array()){
        if(empty($data)){
            $this->error = "提交数据不能为空！";
            return -1;
        }else if($data['title'] == ''){
            $this->error = '活动名称不能为空！';
            return -1;
        }else if($data['product_id'] == '' || empty($data['product_id'])){
            $this->error = '请选择产品！';
            return -1;
        }else if($data['start_time'] == ''){
            $this->error = '活动开始时间不能为空！';
            return -1;
        }else if($data['end_time'] == ''){
            $this->error = '活动结束时间不能为空！';
            return -1;
        }else if(strtotime($data['end_time']) - strtotime($data['start_time']) <= 0){
            $this->error = '结束时间必须大于开始时间！';
            return -1;
        }else if($data["expiration_day"] == "" || $data['expiration_day'] < 0 || !is_numeric($data['expiration_day']) || strpos($data['expiration_day'] , ".")!==false){
            $this->error = '领取有效期必须为大于等于0的整数！';
            return -1;
        }else if($data["buy_quota"] == "" || $data['buy_quota'] < 0 || !is_numeric($data['buy_quota']) || strpos($data['buy_quota'] , ".")!==false){
            $this->error = '领取限制必须为大于等于0的整数！';
            return -1;
        }else if($data["stock"] == "" || $data['stock'] < 0 || !is_numeric($data['stock']) || strpos($data['stock'] , ".")!==false){
            $this->error = '赠品数量必须为大于等于0的整数！';
            return -1;
        }
        $data["status"] = 1;
        $result = $this->add($data);
        
        if($result <= 0){
            $this->error = '操作失败！';
            return -1;
        }
        
        return 1;
    }
    
    /**
     * 编辑赠品
     */
    public function update($data) {
        if(empty($data)){
            $this->error = "提交数据不能为空！";
            return -1;
        }else if($data['title'] == ''){
            $this->error = '活动名称不能为空！';
            return -1;
        }else if($data['start_time'] == ''){
            $this->error = '活动开始时间不能为空！';
            return -1;
        }else if($data['end_time'] == ''){
            $this->error = '活动结束时间不能为空！';
            return -1;
        }else if(strtotime($data['end_time']) - strtotime($data['start_time']) <= 0){
            $this->error = '结束时间必须大于开始时间！';
            return -1;
        }else if($data["expiration_day"] == "" || $data['expiration_day'] < 0 || !is_numeric($data['expiration_day']) || strpos($data['expiration_day'] , ".")!==false){
            $this->error = '领取有效期必须为大于等于0的整数！';
            return -1;
        }else if($data["buy_quota"] == "" || $data['buy_quota'] < 0 || !is_numeric($data['buy_quota']) || strpos($data['buy_quota'] , ".")!==false){
            $this->error = '领取限制必须为大于等于0的整数！';
            return -1;
        }else if($data["stock"] == "" || $data['stock'] < 0 || !is_numeric($data['stock']) || strpos($data['stock'] , ".")!==false){
            $this->error = '赠品数量必须为大于等于0的整数！';
            return -1;
        }
        
        $result = $this->where("id='{$data["id"]}'")->save($data);
        
        if($result === false){
            $this->error = '操作失败！';
            return -1;
        }
        
        return 1;
    }
}