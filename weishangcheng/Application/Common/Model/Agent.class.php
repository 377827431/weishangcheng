<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/6/29
 * Time: 16:10
 */

namespace Common\Model;


use Think\Model;

class Agent extends Model
{
    /**
     * @param $qr_mid
     * @param $mid
     * @param $shopId
     * 绑定店铺推广人关系
     */
    public function bind_agent($qr_mid, $mid, $shopId){
        if ($this->is_agent($mid, $shopId)){
            $up = array(
                "pid" => $mid,
            );
            $Module = M('agent');
            $where = array(
                "mid" => $qr_mid,
                "project_id" => substr($shopId, 0, -3),
            );
            $Module->where($where)->save($up);
        }
    }

    /**
     * @param $mid
     * @param $shopId
     * @return bool
     * 判断是否为店铺推广人
     */
    public function is_agent($mid, $shopId){
        $Module = M('agent');
        $where = array(
            "mid" => $mid,
            "shop_id" => $shopId,
            "status" => 1
        );
        $res = $Module->field("1")->where($where)->find();
        if (!empty($res)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @param array|mixed $mid
     * @param $shopId
     * @return int
     * 清退推广员
     */
    public function delete($mid, $shopId){
        $Module = M('agent');
        $where = array(
            "mid" => $mid,
            "seller_id" => $shopId
        );
        $Module->where($where)->delete();
        $where = array(
            "pid" => $mid,
            "project_id" => substr($shopId, 0, -3)
        );
        $Module = M('project_member');
        $Module->where($where)->save(array("pid" => 0));
        return 1;
    }

    /**
     * @param $where
     * @param int $offset
     * @param int $limit
     * @return mixed
     * 推广员列表
     */
    public function agent_list($where, $order, $offset = 0, $limit = 20){
        $where = implode(' AND ', $where);

        $sql = "SELECT agent.mid, agent.shop_id, agent.created, member.name, project_member.pid, member.head_img, member.mobile  
                FROM agent
                INNER JOIN member ON agent.mid = member.id 
                INNER JOIN project_member ON (project_member.mid = agent.mid AND project_member.project_id = SUBSTR(agent.shop_id, 1, LENGTH(agent.shop_id) - 3))  
                WHERE {$where} 
                {$order}
                LIMIT {$offset}, {$limit}";
        $agentList = $this->query($sql);
        return $this->agentListHandle($agentList);
    }

    private function agentListHandle($agentList){
        $pids = array();
        foreach ($agentList as $key => $value){
            if ($value['pid'] > 0){
                $pids[] = $value['pid'];
            }
        }
        if (!empty($pids)){
            $pids = implode(', ', $pids);
            $sql = "SELECT id, name  
                FROM member 
                WHERE `id` IN ({$pids})";
            $memberList = $this->query($sql);
        }
        $id2name = array();
        foreach ($memberList as $key => $value){
            $id2name[$value['id']] = $value['name'];
        }
        foreach ($agentList as $key => $value){
            if ($value['pid'] > 0){
                $agentList[$key]['pname'] = $id2name[$value['pid']];
            }else{
                $agentList[$key]['pname'] = "无";
            }
            $agentList[$key]['created'] = date("Y-m-d H:i:s", $value['created']);
        }
        return $agentList;
    }

    public function getDetail($mid, $shopId){
        $member = M('member')->field("name, head_img")->where(array("id" => $mid))->find();
        if (!empty($member)){

        }
        $project_id = substr($shopId, 0, -3);
        $Module = M('project_member');
        $where = array(
            "project_id" => $project_id,
            "pid" => $mid,
        );
        $res = $Module
            ->field("mid")
            ->where($where)
            ->select();
        $midOne = array();
        foreach ($res as $key => $value){
            $midOne[] = $value['mid'];
        }
        $case = array();
        $case[] = "sum(case when tc.mid = {$mid} then tc.settlement_balance else 0 end) as `reward1`";
        $case[] = "sum(case when tc.mid = {$mid} and settlement_time > 0 then tc.settlement_balance else 0 end) as `reward0`";
        if (!empty($midOne)){
            $strOne = implode(',', $midOne);
            $where = 'AND `pid` IN ('.$strOne.')';
            $case[] = "sum(case when tc.mid in ({$strOne}) then tc.settlement_balance else 0 end) as `reward2`";
            $res = $Module
                ->field("mid")
                ->where("`project_id` = {$project_id} {$where}")
                ->select();
        }
        $midTwo = array();
        foreach ($res as $key => $value){
            if (!in_array($value['mid'], $midOne)){
                $midTwo[] = $value['mid'];
            }
        }
        $case[] = "sum(case when tc.mid = '{$mid}' then trade_order.payment else 0 end) as `sum`";
//        $case[] = "count('tc.tid') as num";
        $case = implode(',', $case);
        $Module = M('trade_commision');
        $data = $Module
            ->alias('tc')
            ->field($case)
            ->join('trade on tc.tid = trade.tid')
            ->join('trade_order on trade_order.oid = tc.oid')
            ->where("tc.seller_id = '{$shopId}'")
            ->find();
        $num = $Module
            ->field("count(tid) as num")
            ->where(array("mid" => $mid, "seller_id" => $shopId))
            ->find();
        if (!empty($num)){
            $num = $num['num'];
        }else{
            $num = 0;
        }
        $data['num'] = $num;
        if (!isset($data['reward1']) || empty($data['reward1'])){
            $data['reward1'] = 0;
        }
        if (!isset($data['reward2']) || empty($data['reward2'])){
            $data['reward2'] = 0;
        }
        if (!isset($data['reward0']) || empty($data['reward0'])){
            $data['reward0'] = 0;
        }
        if (!isset($data['sum']) || empty($data['sum'])){
            $data['sum'] = 0;
        }
        $data['p_one'] = count($midOne);
        $data['p_two'] = count($midTwo);
        $data['pnum'] = $data['p_one'] + $data['p_two'];
        $agent = M('agent')->field("created")->where(array("mid" => $mid, "shop_id" => $shopId, "status" => 1))->find();
        if (!empty($agent)){
            $data['created'] = date("Y-m-d", $agent['created']);
        }else{
            $data['created'] = "";
        }
        $data['id'] = $mid;
        $data['name'] = $member['name'];
        $data['head_img'] = $member['head_img'];
        return $data;
    }

}