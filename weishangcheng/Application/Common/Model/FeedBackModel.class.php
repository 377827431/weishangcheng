<?php 
namespace Common\Model;

use Think\Model;
/**
 * 资金流水modal
 * @author lanxuebao
 *
 */
class FeedBackModel extends Model{
    protected $tableName = 'mall_goods_feedback';
    
    public function getAll($where = array()){
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $data = array('total' => 0, 'rows' => array());
        
        $data['total'] = $this->alias('feedback')
                              ->join('mall_goods AS g ON feedback.goods_id=g.id','INNER')
                              ->where($where)
                              ->count();
        if($data['total'] == 0){
            return $data;
        }
        
        $data['rows'] = $this->alias('feedback')
                             ->field('feedback.*,g.title,u.nick,u.username')
                             ->join('mall_goods AS g ON feedback.goods_id=g.id','INNER')
                             ->join('users AS u ON feedback.user_id=u.id')
                             ->where($where)
                             ->limit($offset,$limit)
                             ->order('feedback.created DESC')
                             ->select();
        
        return $data;
    }
}