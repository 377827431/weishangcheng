<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/5/22
 * Time: 13:00
 */

namespace Seller\Controller;
use Common\Common\CommonController;
use Common\Model\SettlementType;
use Think\Cache\Driver\Redis;
use Common\Model\ProjectConfig;

class MemberController extends ManagerController
{
    /*代理升级*/
    public function index(){
        $projectId = substr($this->shopId,0,strlen($this->shopId)-3);
        $list = M('project_card')->where("id BETWEEN {$projectId}1 AND {$projectId}9")->select();
        /*foreach ($list as $i=>$item){
            $item['condition'] = array();
            if($item['price'] > 0){
                $item['condition'][] = '需花'.floatval($item['price']).'元购买';
            }
            if($item['auto_trade'] > 0){
                $item['condition'][] = '成功交易'.$item['auto_trade'].'笔';
            }
            if($item['auto_payment'] > 0){
                $item['condition'][] = '成功支付'.floatval($item['auto_payment']).'元';
            }
            if($item['auto_score'] > 0){
                $item['condition'][] = '累计达'.floatval($item['auto_score']).'积分';
            }
            $item['condition'] = implode('或', $item['condition']);
            
            $item['commision'] = '<span title="此会员下单，如果上级不为游客：上一级得'.$item['agent_rate'].'%, 如果与下单人同级得'.$item['agent_same'].'%, 上二级得'.$item['agent_rate2'].'%">'.$item['agent_rate'].' : '.$item['agent_same'].' : '.$item['agent_rate2'].'</span>';
            
            $item['settlement_type'] = SettlementType::getById($item['settlement_type']);
            $discount = bcmul($item['discount'], 10, 1);
            $discount = floatval($discount);
            $item['discount'] = $discount > 0 && $discount < 10 ? $discount.'折' : '-';
            $item['expire_time'] = $item['expire_time'] > 0 ? $item['expire_time'].'天' : '永久';
            $list[$i] = $item;
        }*/
        $shop = M('shop')->find($this->shopId);
        foreach ($list as $k => $v) {
        	$list[$k]['level'] = substr($v['id'],strlen($v['id'])-1,1);
        	$discount = bcmul($v['discount'], 10, 1);
        	$list[$k]['discount'] = $discount > 0 && $discount < 10 ? $discount : '';
        	if($v['price'] == '0.00'){$list[$k]['price'] = '';}
        	if(!$v['auto_trade']){$list[$k]['auto_trade'] = '';}
        	if($v['auto_payment'] == '0.00'){$list[$k]['auto_payment'] = '';}
        	if(!$v['auto_score']){$list[$k]['auto_score'] = '';}
        }

        $this->assign("list",$list);
        $this->assign("project_id",$projectId);
        $this->assign("shop",$shop);
        $this->display();
    }

    public function detail(){
        $id = I('get.id','');
    	$id = addslashes($id);
        if(empty($id)){
            $this->error('id不能为空');
        }

        $projectId = substr($this->shopId,0,strlen($this->shopId)-3);
        $data = M('project_card')->where("id = '%s'",$id)->find();
        $data['condition'] = array();
        if($data['price'] > 0){
            $data['condition'][] = '需花'.floatval($data['price']).'元购买';
        }
        if($data['auto_trade'] > 0){
            $data['condition'][] = '成功交易'.$data['auto_trade'].'笔';
        }
        if($data['auto_payment'] > 0){
            $data['condition'][] = '成功支付'.floatval($data['auto_payment']).'元';
        }
        if($data['auto_score'] > 0){
            $data['condition'][] = '累计达'.floatval($data['auto_score']).'积分';
        }
        $data['condition'] = implode('或', $data['condition']);
        
        $data['commision'] = '<span title="此会员下单，如果上级不为游客：上一级得'.$data['agent_rate'].'%, 如果与下单人同级得'.$data['agent_same'].'%, 上二级得'.$data['agent_rate2'].'%">'.$data['agent_rate'].' : '.$data['agent_same'].' : '.$data['agent_rate2'].'</span>';
        
        $data['settlement_type'] = SettlementType::getById($data['settlement_type']);
        $discount = bcmul($data['discount'], 10, 1);
        $discount = floatval($discount);
        $data['discount'] = $discount > 0 && $discount < 10 ? $discount.'折' : '-';
        $data['expire_time'] = $data['expire_time'] > 0 ? $data['expire_time'].'天' : '永久';
        print_data($data);
        //$list[$i] = $item;
        $this->assign("data",$data);
    }
    
    public function edit(){
        $id = I('get.id','');
        $cardId = addslashes($id);
        $project_id = substr($this->shopId,0,strlen($this->shopId)-3);
        
        $Model = M('project_card');
        $levels = array();
        for($i=1; $i<10; $i++){
            $id = $project_id.$i;
            $levels[] = $id;
        }

        $exists = $Model->query("select id from project_card where id BETWEEN {$project_id}1 AND {$project_id}9");
        foreach ($exists as $item){
            if($item['id'] == $cardId){
                continue;
            }

            $index = array_search($item['id'], $levels);
            if($index > -1){
                unset($levels[$index]);
            }
        }

        if(count($levels) == 0){
            $this->error('最多可创建9个会员卡');
        }

        // 如果已被使用则禁止修改等级
        $used = $Model->query("SELECT 1 FROM project_member WHERE project_id={$project_id} AND card_id={$cardId} LIMIT 1");

        $old = $Model->find($cardId);
        if(empty($old) || $project_id != substr($old['id'], 0, -1)){
            $this->error('会员卡不存在');
        }

        /*if(IS_POST){
            $data = $this->getData();
            if(!in_array($data['id'], $levels)){
                $this->error('会员卡等级已存在');
            }else if($used && $data['id'] != $cardId){
                $this->error('会员卡已被使用，禁止修改等级');
            }else if($project_id != substr($data['id'], 0, -1)){
                $this->error('会员卡不存在');
            }
            
            $Model->where("id=".$cardId)->save($data);
            $this->success('已保存');
        }*/

        $data        = $old;
        $discount    = bcmul($data['discount'], 10, 2);
        $discount    = explode('.', $discount);
        $data['zk1'] = $discount[0];
        $data['zk2'] = intval($discount[1]);
        $data['discut'] = bcmul($data['discount'], 10, 2);
        $data['discut'] = $data['discut'] > 0 && $data['discut'] < 10 ? $data['discut'] : '';
        if(!$data['agent_rate']){$data['agent_rate'] = '';}
        if(!$data['agent_same']){$data['agent_same'] = '';}
        if(!$data['agent_rate2']){$data['agent_rate2'] = '';}
        if(!$data['expire_time']){$data['expire_time'] = '';}
        if($data['price'] == '0.00'){$data['price'] = '';}
        if(!$data['auto_trade']){$data['auto_trade'] = '';}
        if($data['auto_payment'] == '0.00'){$data['auto_payment'] = '';}
        if(!$data['auto_score']){$data['auto_score'] = '';}

        $data['quantity'] = $used ? 1 : 0;

        $level_list = array();
        foreach ($levels as $k => $v) {
        	$level_list[]['key'] = substr($v,strlen($v)-1,1);
        	if($v == $data['id']){
        		$level_list[count($level_list)-1]['default'] = true;
        	}else{
        		$level_list[count($level_list)-1]['default'] = false;
        	}
        }

        $shop = M('shop')->find($this->shopId);
        //$this->ajaxReturn(array("data" => $data , "levels" => $level_list));
        $this->assign('levels', $level_list);
        $this->assign('data', $data);
        $this->assign('shop',$shop);
        $this->display('add_vip_card');
    }

    private function getData(){
    	$project_id = substr($this->shopId,0,strlen($this->shopId)-3);
        $data = array(
            'id'              => $project_id.$_POST['level'],
            'title'           => $_POST['level_nick'],
            'discount'        => bcdiv($_POST['discut'] ? $_POST['discut'] : 10, 10, 2),
            'expire_time'     => intval($_POST['empire_date']),
            'price'           => floatval($_POST['member_value']),
            'auto_trade'      => intval($_POST['auto_order_num']),
            'auto_payment'    => floatval($_POST['auto_price']),
            'auto_score'      => intval($_POST['auto_score']),
            'price_title'     => $_POST['level_nick']
        );
        $key = ProjectConfig::WHOLE_SHOP_REWARD;
        $res = M('project_config')->where("project_id='{$project_id}' AND `key`='{$key}'")->find();
        if (!empty($res)){
            $res = json_decode($res['val'], true);
            $data['agent_rate'] = $res['agent_rate'];
            $data['settlement_type'] = $res['settlement_type'];
        }

        $data['discount'] = floatval($data['discount']);

        // 数据校验
        if($data['price'] < 0){
            $this->error('价格不能小于1元');
        }else if($data['auto_trade'] < 0 || $data['auto_payment'] < 0 || $data['auto_score'] < 0){
            $this->error('自动升级条件值不能小于1');
        }else if($data['discount'] < 0 || $data['discount'] > 9.9){
            $this->error('会员折扣应在0-9.9之间');
        }

        return $data;
    }
    
    /*
     * 添加
     */
    public function add(){
        $project_id = substr($this->shopId,0,strlen($this->shopId)-3);
        
        $Model = M('project_card');
        $levels = array();
        for($i=1; $i<10; $i++){
            $id = $project_id.$i;
            $levels[] = $id;
        }

        $exists = $Model->query("select id from project_card where id BETWEEN {$project_id}1 AND {$project_id}9");
        foreach ($exists as $item){
            $index = array_search($item['id'], $levels);
            if($index > -1){
                unset($levels[$index]);
            }
        }

        if(count($levels) == 0){
            $this->error('最多可创建9个会员卡');
        }

        $shop = M('shop')->find($this->shopId);

        $coupon = M('mall_coupon')->where("shop_id = {$this->shopId} and status = 1")->select();
        if(empty($coupon)){
            $coupon[0]['id'] = 0;
            $coupon[0]['name'] = '无可用优惠券';
        }
        /*if(IS_POST){
            $data = $this->getData();
            if(!in_array($data['id'], $levels)){
                $this->error('会员卡等级已存在');
            }
            
            $Model->add($data);
            $this->success('添加成功');
        }*/
        $level_list = array();
        $i = 0;
        foreach ($levels as $k => $v) {
        	$level_list[]['key'] = substr($v,strlen($v)-1,1);
        	if($i == 0){
        		$level_list[count($level_list)-1]['default'] = true;
        	}else{
        		$level_list[count($level_list)-1]['default'] = false;
        	}
        	$i++;
        }
        $this->assign('levels',$level_list);
        $this->assign('shop',$shop);
        $this->assign('coupon',$coupon);
        $this->display("add_vip_card");
    }
    
    /*
     * 删除
     */
    public function delete(){
        $id = I('post.id','');
        $id = addslashes($id);
        if(empty($id)){
            $this->error('id不能为空');
        }

        $project_id = substr($this->shopId,0,strlen($this->shopId)-3);

        $list = explode(',', $id);
        $min  = floatval($project_id.'1');
        $max  = floatval($project_id.'9');
        foreach($list as $id){
            $id = floatval($id);
            if($id < $min || $id > $max){
                $this->error('会员卡不存在');
            }
        }

        $Model = M();
        // 判断被删除到会员卡是否已被使用
        $id = implode(',', $list);
        $used = $Model->query("SELECT 1 FROM project_member WHERE project_id={$project_id} AND card_id IN ({$id}) LIMIT 1");
        if($used){
            $this->error('会员卡已被使用，无法删除');
        }

        M()->query("delete from project_card where id in ({$id})");
        $this->success('删除成功');
    }

    public function cardinsert(){
        $_POST = array(
            'level'              => $_POST['level'],
            'level_nick'           => $_POST['level_nick'],
            'discut'        => $_POST['discut'],
            'empire_date'     => $_POST['empire_date'],
            'member_value'           => $_POST['member_value'],
            'auto_order_num'      => $_POST['auto_order_num'],
            'auto_price'    => $_POST['auto_price'],
            'auto_score'      => 0,
            );
    	//$_POST = $_POST['data'];
    	$project_id = substr($this->shopId,0,strlen($this->shopId)-3);
        
        $Model = M('project_card');
        $levels = array();
        for($i=1; $i<10; $i++){
            $id = $project_id.$i;
            $levels[] = $id;
        }

        $exists = $Model->query("select id from project_card where id BETWEEN {$project_id}1 AND {$project_id}9");
        foreach ($exists as $item){
            $index = array_search($item['id'], $levels);
            if($index > -1){
                unset($levels[$index]);
            }
        }

        if(count($levels) == 0){
            $this->error('最多可创建9个会员卡');
        }
        $data = $this->getData();
        if(!in_array($data['id'], $levels)){
            $this->error('会员卡等级已存在');
        }
        
        $Model->add($data);
        $this->success('添加成功');
    }

    public function cardedit(){
        $_POST = array(
            'level'              => $_POST['level'],
            'level_nick'           => $_POST['level_nick'],
            'discut'        => $_POST['discut'],
            'empire_date'     => $_POST['empire_date'],
            'member_value'           => $_POST['member_value'],
            'auto_order_num'      => $_POST['auto_order_num'],
            'auto_price'    => $_POST['auto_price'],
            'auto_score'      => 0,
            'id' =>$_POST['id'],
            );
    	//$_POST = $_POST['data'];
    	$cardId = addslashes($_POST['id']);
        $project_id = substr($this->shopId,0,strlen($this->shopId)-3);
        
        $Model = M('project_card');
        $levels = array();
        for($i=1; $i<10; $i++){
            $id = $project_id.$i;
            $levels[] = $id;
        }

        $exists = $Model->query("select id from project_card where id BETWEEN {$project_id}1 AND {$project_id}9");
        foreach ($exists as $item){
            if($item['id'] == $cardId){
                continue;
            }

            $index = array_search($item['id'], $levels);
            if($index > -1){
                unset($levels[$index]);
            }
        }

        if(count($levels) == 0){
            $this->error('最多可创建9个会员卡');
        }

        // 如果已被使用则禁止修改等级
        $used = $Model->query("SELECT 1 FROM project_member WHERE project_id={$project_id} AND card_id={$cardId} LIMIT 1");

        $old = $Model->find($cardId);
        if(empty($old) || $project_id != substr($old['id'], 0, -1)){
            $this->error('会员卡不存在');
        }
        $data = $this->getData();
        if(!in_array($data['id'], $levels)){
            $this->error('会员卡等级已存在');
        }else if($used && $data['id'] != $cardId){
            $this->error('会员卡已被使用，禁止修改等级');
        }else if($project_id != substr($data['id'], 0, -1)){
            $this->error('会员卡不存在');
        }
        
        $Model->where("id=".$cardId)->save($data);
        $this->success('修改成功');
    }
}