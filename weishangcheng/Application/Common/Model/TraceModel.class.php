<?php
namespace Common\Model;

use Think\Model;

class TraceModel extends Model{
    protected $tableName = 'shop_trace';

    public function traceShop($mid, $params){
        $offset = is_numeric($params['offset']) ? $params['offset'] : 0;
        $size = is_numeric($params['size']) ? $params['size'] : 6;

        $res = M('shop_trace')
            ->alias('ts')
            ->field('ts.shop_id, shop.name, shop.logo')
            ->join("shop on shop.id = ts.shop_id")
            ->where(array("ts.mid" => $mid, "ts.is_del" => 0))
            ->order('ts.modify desc')
            ->limit("{$offset}, {$size}")
            ->select();
        if (empty($res)){
            return array();
        }
        $shopIds = array();
        $projectIds = array();
        $condition = "";
        $condition2 = "";
        foreach ($res as $k => $v){
            $shopIds[] = $v['shop_id'];
            $projectIds[] =  substr($v['shop_id'], 0, -3);
            $condition .= "count(case when shop_id = '{$v['shop_id']}' then 1 else null end) as id_{$v['shop_id']},";
            $condition2 .= "count(case when shop_id = '{$v['shop_id']}' then 1 else null end) as id_{$v['shop_id']},";
        }
        if (!empty($condition)){
            $condition = substr($condition, 0, -1);
            $condition2 = substr($condition2, 0, -1);
        }
        $shopIds = implode(',', $shopIds);
        $projectIds = implode(',', $projectIds);
        $project = M('project')
            ->field("alias, id")
            ->where("id IN ({$projectIds})")
            ->select();
        $alias = array();
        foreach ($project as $k => $v){
            $alias[$v['id']] = $v['alias'];
        }

        $date = date('Y-m-d', strtotime('-7 days'));
        $new = M('mall_goods')
            ->field($condition)
            ->where("created > '{$date}' AND is_del = 0 AND is_display = 1")
            ->find();

        $goods = M('mall_goods')
            ->field($condition2)
            ->where("shop_id IN ($shopIds) AND is_del = 0 AND is_display = 1")
            ->find();

        //最新
        $sql = array();
        $shops =  explode(',', $shopIds);
        foreach ($shops as $k => $v){
            $sql[] = "(select shop_id, price, pic_url from mall_goods WHERE shop_id = '{$v}' AND is_del = 0 AND is_display = 1 ORDER BY id DESC limit 3)";
        }
        $sql = implode(' union ', $sql);
        $new3 = M()->query($sql);

        $newest = array();
        foreach ($new3 as $k => $v){
            $newest[$v['shop_id']][] = $v;
        }

        foreach ($res as $k => $v){
            $res[$k]["new"] = $new['id_'.$v['shop_id']];
            $res[$k]["goods"] = $goods['id_'.$v['shop_id']];
            $res[$k]["newest"] = !empty($newest[$v['shop_id']]) ? $newest[$v['shop_id']] : array();
            $res[$k]["alias"] = $alias[substr($v['shop_id'], 0, -3)];
        }
        return $res;
    }
}
?>