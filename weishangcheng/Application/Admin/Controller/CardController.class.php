<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Common\Model\SettlementType;
use Common\Model\ProjectConfig;

/**
 * 会员卡
 * 
 * @author lanxuebao
 *        
 */
class CardController extends CommonController
{
    /*代理升级*/
    public function index(){
        $projectId = $this->user('project_id');
        if(IS_AJAX){
            $list = M('project_card')->where("id BETWEEN {$projectId}1 AND {$projectId}9")->select();
            foreach ($list as $i=>$item){
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
            }
            $this->ajaxReturn($list);
        }

        $this->display();
    }
    
    public function content(){
        $Model = M();
        $project_id = $this->projectId;
        $cards = $Model->query("select id, title, price from project_card where id BETWEEN {$project_id}1 AND {$project_id}9 AND price>0");
        $html = $Model->query("SELECT goods_id, detail FROM mall_goods_content WHERE goods_id=(SELECT id FROM mall_goods WHERE shop_id={$this->projectId}000 AND goods_type=1)");
        $html = $html[0];
        
        if(IS_POST){
        	$this->addGoods($cards, $_POST['html'], $html['goods_id'] ? $html['goods_id'] : 0);
            $this->success('已保存');
        }
        
        $this->assign('html', $html['detail']);
        $this->assign('cards', $cards);
        $this->display();
    }
    
    /**
     * 生成商品
     */
    private function addGoods($cards, $html, $existsId = 0){
    	$projectId = $this->projectId;
    	$Model = M('mall_goods');
    	$Model->startTrans();
    	
    	$today = date('Y-m-d H:i:s');
    	$goods = array(
    			'shop_id'    => $projectId.'000',
    			'title'      => '会员卡',
    			'price'      => array(),
    			'goods_type' => 1,
    			'member_discount' => 0,
    			'stock'      => 0,
    			'is_display' => 1,
    			'pic_url'    => '',
    			'is_virtual' => 1,
    			//'pic_url'    => $project['logo'],
    			'pic_url'    => C('CDN').'/img/mall/member_card.jpg',
    			'created'    => $today
    	);
    	
    	// 插入
    	$productId = array();
    	if($existsId > 0){
    		$goodsId = $existsId;
    		$products = $Model->query("SELECT id, sku_json FROM mall_product WHERE goods_id=".$goodsId);
    		foreach ($products as $item){
    			$sku = decode_json($item['sku_json']);
    			$sku = $sku[0];
    			$productId[$sku['vid']] = $item['id'];
    		}
    	}else{
    		$goodsId = $Model->add($goods);
    	}
    	
    	// 更新产品
    	$products = array();
    	$skuJson = array(array('id' => 1, 'text' => '会员级别', 'items' => array()));
    	
    	// 组合skujson
    	$dataPID = array();
    	$priceList = array();
    	foreach($cards as $i=>$card){
    		$stock = 9999;
    		if($card['price'] != '0.00'){
    			$goods['price'][] = $card['price'];
    		}else{
    			$stock = 0;
    		}
    		$skuJson[0]['items'][] = array('id' => $card['id'], 'text' => $card['title']);
    		
    		if(array_key_exists($card['id'], $productId)){
    			$pid = $productId[$card['id']];
    			$sql = "UPDATE mall_product SET price='{$card['price']}', stock={$stock} WHERE id=".$pid;
    			$Model->execute($sql);
    			unset($productId[$card['id']]);
    		}else{
    			$sku = encode_json(array(array('kid' => 1, 'vid' => $card['id'], 'k' => '会员级别', 'v' => $card['title'])));
    			$sql = "INSERT INTO mall_product SET goods_id={$goodsId}, price='{$card['price']}', stock={$stock}, sku_json='{$sku}', created='{$today}'";
    			$Model->execute($sql);
    			$pid = $Model->execute("SELECT LAST_INSERT_ID()");
    		}
    		$dataPID[$card['id']] = $pid;
    	}
    	
    	// 更新goods_content
    	$html = addslashes($html);
    	$skuJson = encode_json($skuJson);
    	$skuJson = addslashes($skuJson);
    	if($existsId > 0){
    		$sql = "UPDATE mall_goods_content SET detail='{$html}', sku_json='{$skuJson}' WHERE goods_id=".$goodsId;
    	}else{
    		$sql = "INSERT INTO mall_goods_content SET detail='{$html}', sku_json='{$skuJson}', goods_id={$goodsId}, template_id=1";
    		$Model->execute("INSERT INTO mall_goods_sort SET id=".$goodsId);
    	}
    	$Model->execute($sql);
    	
    	// 删除无用的sku_id
    	if(count($productId) > 0){
    		$productId = array_values($productId);
    		$productId = implode(',', $productId);
    		$Model->execute("DELETE FROM mall_product WHERE id IN({$productId})");
    	}
    	
    	sort($goods['price']);
    	$goods['price'] = current($goods['price']);
    	$Model->execute("UPDATE mall_goods SET price='{$goods['price']}' WHERE id=".$goodsId);
    	$Model->commit();
    	
    	project_config($projectId, \Common\Model\ProjectConfig::CARD_GOODS_ID, $goodsId);
    }
    
    public function edit(){
        $cardId = addslashes($_GET['id']);
        $project_id = $this->projectId;
        
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
        $used = $Model->query("SELECT 1 FROM project_member WHERE project_id={$this->projectId} AND card_id={$cardId} LIMIT 1");

        $old = $Model->find($cardId);
        if(empty($old) || $this->projectId != substr($old['id'], 0, -1)){
            $this->error('会员卡不存在');
        }

        if(IS_POST){
            $data = $this->getData();
            if(!in_array($data['id'], $levels)){
                $this->error('会员卡等级已存在');
            }else if($used && $data['id'] != $cardId){
                $this->error('会员卡已被使用，禁止修改等级');
            }else if($this->projectId != substr($data['id'], 0, -1)){
                $this->error('会员卡不存在');
            }
            
            $Model->where("id=".$cardId)->save($data);
            $this->success('已保存');
        }

        $data        = $old;
        $discount    = bcmul($data['discount'], 10, 2);
        $discount    = explode('.', $discount);
        $data['zk1'] = $discount[0];
        $data['zk2'] = intval($discount[1]);
        if(!$data['agent_rate']){$data['agent_rate'] = '';}
        if(!$data['agent_same']){$data['agent_same'] = '';}
        if(!$data['agent_rate2']){$data['agent_rate2'] = '';}
        if(!$data['expire_time']){$data['expire_time'] = '';}
        if($data['price'] == '0.00'){$data['price'] = '';}
        if(!$data['auto_trade']){$data['auto_trade'] = '';}
        if($data['auto_payment'] == '0.00'){$data['auto_payment'] = '';}
        if(!$data['auto_score']){$data['auto_score'] = '';}

        $data['quantity'] = $used ? 1 : 0;
        $this->assign('levels', $levels);
        $this->assign('data', $data);
        $this->display('edit');
    }

    private function getData(){
        $data = array(
            'id'              => $_POST['id'],
            'title'           => $_POST['title'],
            'discount'        => bcdiv($_POST['zk1'].'.'.$_POST['zk2'], 10, 2),
            'settlement_type' => intval($_POST['settlement_type']),
            'agent_rate'      => floatval($_POST['agent_rate']),
            'agent_rate2'     => floatval($_POST['agent_rate2']),
            'agent_same'      => floatval($_POST['agent_same']),
            'expire_time'     => intval($_POST['expire_time']),
            'price'           => floatval($_POST['price']),
            'auto_trade'      => intval($_POST['auto_trade']),
            'auto_payment'    => floatval($_POST['auto_payment']),
            'auto_score'      => intval($_POST['auto_score']),
            'price_title'     => $_POST['title']
        );

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
        $project_id = $this->projectId;
        
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


        if(IS_POST){
            $data = $this->getData();
            if(!in_array($data['id'], $levels)){
                $this->error('会员卡等级已存在');
            }
            
            $Model->add($data);
            $this->success('添加成功');
        }

        $this->assign('levels', $levels);
        $this->display('edit');
    }
    
    /*
     * 删除
     */
    public function delete(){
        $id = addslashes($_POST['id']);
        if(empty($id)){
            $this->error('id不能为空');
        }

        $list = explode(',', $id);
        $min  = floatval($this->projectId.'1');
        $max  = floatval($this->projectId.'9');
        foreach($list as $id){
            $id = floatval($id);
            if($id < $min || $id > $max){
                $this->error('会员卡不存在');
            }
        }

        $Model = M();
        // 判断被删除到会员卡是否已被使用
        $id = implode(',', $list);
        $used = $Model->query("SELECT 1 FROM project_member WHERE project_id={$this->projectId} AND card_id IN ({$id}) LIMIT 1");
        if($used){
            $this->error('会员卡已被使用，无法删除');
        }

        M()->query("delete from project_card where id in ({$id})");
        $this->success('删除成功');
    }
}
?>