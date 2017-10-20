<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 评论
 */
class RateController extends CommonController
{
    public function index(){
        $goods_id = $_REQUEST['goods_id'];
        if(IS_AJAX){
            $this->showList();
        }
        $this->display();
    }
    /*
     * 查询
     * */
    private function showList(){
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $where = array();
        $buyer_id = $_GET['buyer_id'];
        if(is_numeric($_GET['goods_id'])){
            $where[] = "rate.goods_id=".$_GET['goods_id'];
        }
        if(is_numeric($_GET['result'])){
            $where[] = "rate.result=".$_GET['result'];
        }
        if(is_numeric($buyer_id)){
            $Model = new \Common\Model\BaseModel();
            $where[] = "trade.buyer_id=".$buyer_id;
            $sql2 = "SELECT count(*) as comment
            FROM mall_trade_rate AS rate
            INNER JOIN mall_order AS `order` ON `order`.oid=rate.oid
            INNER JOIN mall_trade AS trade ON trade.tid=`order`.tid
            where trade.buyer_id={$buyer_id}
            group by rate.result
            ORDER BY rate.result asc
            LIMIT {$offset}, {$limit}";
            $list1 = $Model->query($sql2);
        }
        $where = count($where) > 0 ? 'WHERE '.implode(' AND ', $where) : '';
        $data = array('total' => 100, 'rows' => array());
        $Model = new \Common\Model\BaseModel();
        $sql = "SELECT rate.id,rate.oid, rate.goods_id, rate.feedback, rate.result, rate.created,
                    rate.visible, rate.description,rate.logistics,rate.service_quality,
                    `order`.title, `order`.sku_json,
                    trade.tid, trade.buyer_id, trade.buyer_nick, trade.buyer_agent_level
                FROM mall_trade_rate AS rate
                INNER JOIN mall_order AS `order` ON `order`.oid=rate.oid
                INNER JOIN mall_trade AS trade ON trade.tid=`order`.tid
                {$where}
                ORDER BY rate.id DESC
                LIMIT {$offset}, {$limit}";
        $list = $Model->query($sql);
        $resultList = array(1 => '好评', 2 => '中评', 3 => '差评');
        $serviceList = array(0 => '未评价',1=>'很不满意',2=>'不满意',3=>'一般',4=>'满意',5=>'很满意');
        $agentList = $Model->agentLevel();
        foreach ($list as $item){
            $data['rows'][] = array(
                'id' => $item['id'],
                'oid' => $item['oid'],
                'tid' => $item['tid'], 
                'goods_id' => $item['goods_id'],
                'title' => $item['title'],
                'spec' => get_spec_name($item['sku_json']),
                'buyer_nick' => $item['buyer_nick'],
                'buyer_id'   => $item['buyer_id'],
                'buyer_agent_level' => $agentList[$item['buyer_agent_level']]['title'],
                'result' => $resultList[$item['result']],
                'service' => $serviceList[$item['description']].'/'.$serviceList[$item['logistics']].'/'.$serviceList[$item['service_quality']], 
                'feedback' => $item['feedback'],
                'created' => substr($item['created'], 0, 10),
                'visible' => $item['visible']
            );
        }
        $data['total'] = $offset + count($data['rows']) + 50;
        $data['comment1'] = $list1['0'];
        $data['comment2'] = $list1['1'];
        $data['comment3'] = $list1['2'];
        $this->ajaxReturn($data);
    }
    /*
     * 显示/隐藏
     * /
     */
    public function delete(){
        $Model=M();
        $id = $_REQUEST['id'];
        if(!is_numeric($id)){
            $this->error('失败');
        }
        $list = $Model->query("SELECT * FROM mall_trade_rate where id=".$id);
        if($list){
            $visible=$list['0']['visible']==1?0:1;
            $Model->execute("UPDATE mall_trade_rate SET visible=".$visible);
            $this->success('修改成功');
        }
        
    }
}
?>