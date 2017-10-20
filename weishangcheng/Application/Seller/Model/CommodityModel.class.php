<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/4/7
 * Time: 9:33
 */

namespace Seller\Model;
use Common\Model\BaseModel;

class CommodityModel extends BaseModel{
    protected $tableName = 'mall_goods';

    public function getGoodsList($_where){
        $where = array();
        $offset = I('get.offset/d', 0);
        $limit = I('get.size/d', 50);
        $where[] = "mall_goods.is_del = 0";
        $where[] = "mall_goods.shop_id = ".$_where['shop_id'];
        if ($_where['action'] == 'sales'){
            $where[] = "mall_goods.is_display = 1 AND mall_goods.stock > 0";
        }
        if ($_where['action'] == 'sold'){
            $where[] = "mall_goods.stock <= 0";
        }
        if ($_where['action'] == 'shelf'){
            $where[] = "mall_goods.is_display = 0";
        }
        if(!empty($_where['title'])){
            $title = addslashes($_where['title']);
            $where[] = "(mall_goods.title LIKE '%{$title}%' OR mall_goods.outer_id like '%{$title}%')";
        }
//        print_data(implode(' AND ', $where));
        $data = $this
            ->where(implode(' AND ', $where))
            ->field('mall_goods.*, agent_goods.*, mall_goods_sort.sold,mall_goods.id as id')
            ->join('mall_goods_sort ON mall_goods_sort.id = mall_goods.id')
            ->join('agent_goods ON agent_goods.id = mall_goods.id')
            ->order('sort DESC, created DESC')
            ->limit($offset, $limit)
            ->select();
        $promoters = $this->promoters_reward($_where['shop_id']);
        $host = $this->get_alias($_where['shop_id']);
        foreach ($data as $k => $v){
            //获取区间价格,区间利润
            $products = $this->query("SELECT * FROM mall_product WHERE goods_id = '{$v['id']}'");
            $minprice = $v['price'];
            $maxprice = $v['price'];
            $minprofit = $v['price']-$v['cost'];
            $maxprofit = $v['price']-$v['cost'];
            foreach ($products as $kp => $vp) {
                if($minprice>$vp['price']){
                    $minprice = $vp['price'];
                }
                if($maxprice<$vp['price']){
                    $maxprice = $vp['price'];
                }
                if($minprofit>$vp['price']-$vp['cost']){
                    $minprofit = $vp['price']-$vp['cost'];
                }
                if($maxprofit<$vp['price']-$vp['cost']){
                    $maxprofit = $vp['price']-$vp['cost'];
                }
            }
            if($maxprice != $minprice){
                $data[$k]['price_range'] = $minprice.' - '.$maxprice;
            }
            if($maxprofit != $minprofit){
                $data[$k]['profit_range'] = $minprofit.' - '.$maxprofit;
            }
            
            //
            $reward_value = json_decode($v['reward_value'], true);
            $reward_value1 = $reward_value['o'][0]['1_o'];
            $reward_value2 = $reward_value['o'][0]['2_o'];
            if (!empty($reward_value1) && $reward_value1 > 0){
                $data[$k]['reward1'] = $reward_value1;
            }else{
                if($promoters['promoters_set'] == 1){
                    //开启推广佣金
                    $data[$k]['reward1'] = $promoters['agent_rate'];
                    $data[$k]['reward_type'] = $promoters['reward_type'];
                }else{
                    $data[$k]['reward1'] = 0;
                }
            }
            if (!empty($reward_value2) && $reward_value2 > 0){
                $data[$k]['reward2'] = $reward_value2;
            }else{
                if($promoters['promoters_set'] == 1){
                    //开启推广佣金
                    $data[$k]['reward2'] = $promoters['agent_rate'];
                    $data[$k]['reward_type'] = $promoters['reward_type'];
                }else{
                    $data[$k]['reward2'] = 0;
                }
            }
            if($data[$k]['reward_type'] == 1){
                $data[$k]['reward1'] .= '元';
                $data[$k]['reward2'] .= '元';
            }else{
                $data[$k]['reward1'] .= '%';
                $data[$k]['reward2'] .= '%';
            }
            if ($v['cost'] == 0 || $v['cost'] == ''){
                $data[$k]['profit'] = '0.00';
            }else{
                $data[$k]['profit'] = (string)(number_format($v['price'] - $v['cost'], 2));
            }
            $data[$k]['host'] = $host;
        }
        return $data;
    }
    //推广员佣金信息
    private function promoters_reward($shop_id){
        $projectId = substr($shop_id, 0, -3);
        $data = M('project_config')->where("project_id={$projectId} AND `key`='104'")->find();
        if (!empty($data)){
            $data = json_decode($data['val'],true);
            if($data['recruit_open']==1){
                $data = array(
                    'promoters_set' => 1,
                    'reward_type' => $data['settlement_type'],
                    'agent_rate' => $data['agent_rate']?$data['agent_rate']:0,
                    'agent_rate2' => $data['agent_rate2']?$data['agent_rate2']:0,
                    );
            }else{
                $data = array(
                    'promoters_set' => 0,
                    );
            }
        }else{
            $data = array(
                'promoters_set' => 0,
                );
        }
        return $data;
    }
    public function get_alias($shop_id){
        $projectId = substr($shop_id,0,-3);
        $project = M('project')->find($projectId);
        return $project['host'].'/'.$project['alias'].'/';
    }
}
?>