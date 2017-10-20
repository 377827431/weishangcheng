<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 代理管理
 * 
 * @author wangjing
 *        
 */
class AgentController extends CommonController
{
    /*代理升级*/
    public function index(){
        if(IS_AJAX){
            $Model = M('agent_group');
            $data['rows'] = $Model->select();
            $cards = $Model->query("SELECT id, title FROM project_card WHERE id BETWEEN {$this->projectId}0 AND {$this->projectId}9");
            $settlement_type = array(
                4 => '确认收货后(推荐)',
                0 => '不参与推广',
                2 => '买家付款后',
                3 => '上传快递单号后',
                1 => '手动结算(暂不支持)');
            foreach ($data['rows'] as $i=>$item){
                $data['rows'][$i]['card_id'] = $cards[$i]['title'];
                $data['rows'][$i]['settlement_type'] = $settlement_type[$item['settlement_type']];
                $data['rows'][$i]['reward_type'] = $item['reward_type'] == 1 ? '每成交1件提n元' : '按成交额百分比计算';
            }
            $this->ajaxReturn($data);
        }
        $this->display();
    }
    
    /**
     * 编辑
     */
    public function edit(){
        $id = $_GET['id'];

        $Model = M('agent_group');
        $data = $Model->find($id);
        if(!$data || $data['project_id'] != $this->projectId){
            $this->error('代理权不存在');
        }

        if(IS_POST){
            $data = I("post.");
            $result = $Model->updateLevel($data);
            if($result < 0){
                $this->error($Model->getError());
            }
            $this->success();
        }

        $data['items'] = json_decode($data['items'], true);
        $data['relation'] = explode_string(',', $data['relation']);

        $this->assign('data', $data);
        $this->assignMust($data['id']);
        $this->display();
    }
    
    /*
     * 添加
     */
    public function add(){
        if(IS_POST){
            $this->save();
        }

        $this->assignMust();
        $this->display('edit');
    }

    /**
     * 添加和编辑必用参数
     * @param mixed $id 
     */
    private function assignMust($id = 0){
        $Model = M('agent_group');

        // 关联代理权
        $relations = $Model->query("SELECT id, title, items FROM agent_group WHERE project_id={$this->projectId} AND id != {$id}");
        foreach($relations as $i=>$item){
            $relations[$i]['items'] = json_decode($item['items'], true);
        }
        $this->assign('relations', $relations);

        // 关联会员卡
        $cards = $Model->query("SELECT id, title FROM project_card WHERE id BETWEEN {$this->projectId}0 AND {$this->projectId}9");
        $this->assign('cards', $cards);

        $this->assign('settlement_type', array(
           4 => array('title' => '确认收货后(推荐)'),
           0 => array('title' => '不参与推广'),
           2 => array('title' => '买家付款后'),
           3 => array('title' => '上传快递单号后'),
           1 => array('title' => '手动结算(暂不支持)')
       ));
    }
    
    /*
     * 保存
     */
    public function save($id = 0){
        $data = array(
            'project_id'      => $this->projectId,
            'title'           => $_POST['title'],
            'relation'        => $_POST['relation'] ? $_POST['relation'] : '',
            'card_id'         => is_numeric($_POST['card_id']) && substr($_POST['card_id'], 0, -1) == $this->projectId ? $_POST['card_id'] : 0,
            'reward_type'     => $_POST['reward_type'],
            'settlement_type' => $_POST['settlement_type'],
            'items'           => ''
        );

        $Model = M('agent_group');
        if($id > 0){
            $level = 1;
            $items =  array();
            foreach($_POST['items'] as $item){
                if(!$item['title']){
                    continue;
                }

                $items[$id.$level] = $item;
                $level++;
            }
            $data['items'] = $items;
            $Model->where("id=".$id)->save($data);
        }else{
            $data['created'] = date('Y-m-d H:i:s');
            $data['reward_first'] = '{}';
            $data['reward_second'] = '{}';
            $id = $Model->add($data);

            $level = 1;
            $items =  array();
            foreach($_POST['items'] as $item){
                if(!$item['title']){
                    continue;
                }

                $items[$id.$level] = $item;
                $level++;
            }
            $Model->where("id=".$id)->save(array('items' => $items));
        }

        $this->success('已保存');
    }
    
    /*
     * 删除
     */
    public function delete(){
        $id = addslashes($_POST['id']);
        if(empty($id)){
            $this->error('id不能为空');
        }
        M()->query("delete from agent_group where id in ({$id})");
        $this->success('删除成功');
    }

    /**
     * 佣金设置
     */
    public function commision(){
        $projectId = $this->projectId;
        $id = $_REQUEST['id'];
        if(!is_numeric($id)){
            $this->error('代理权ID不能为空');
        }

        $Model = M('agent_group');
        // 查找商品最新信息
        $group = $Model->query("SELECT * FROM agent_group WHERE id={$id} AND project_id={$projectId}");
        $group = $group[0];
        if(!$group){
            $this->error('代理权不存在');
        }

        if(IS_POST){
            $data = array();
            $data[$_REQUEST['type'] == 'first' ? 'reward_first' : 'reward_second'] = json_encode($_POST['reward_value'], JSON_NUMERIC_CHECK);
            $Model->where("id=".$id)->save($data);
            $this->success('已保存');
        }

        $group['reward_value'] = $_REQUEST['type'] == 'first' ? $group['reward_first'] : $group['reward_second'];
        $group['type'] = $_REQUEST['type'];
        $this->assign('data', $group);

        $agent_list = array();
        $list = json_decode($group['items'], true);
        foreach($list as $key=>$item){
            $agent_list[] = array('id' => $key, 'title' => $item['title']);
        }
        $agent_list[] = array('id' => 'o', 'title' => '其他', 'settlement_type' => 4);
        //$agent_list[] = array('id' => 0, 'title' => '游客', 'settlement_type' => 4);
        $this->assign('agent_list', json_encode($agent_list));

        $this->assign('title', '首次升级佣金');
        $this->display();
    }
    
    public function content(){
        layout(false);
        $Model = M();
        $projectId = $this->projectId;
        $cards = $Model->query("select id, title, price from project_card where id BETWEEN {$projectId}1 AND {$projectId}9 AND price>0");
        //$project = get_project($projectId);
        
        $Model = M('mall_goods');
        $sql = "SELECT id, detail
                FROM mall_goods
                LEFT JOIN mall_goods_content AS content ON content.goods_id=mall_goods.id
                WHERE shop_id={$projectId}000 AND goods_type='1'";
        $exists = $Model->query($sql);
        $exists = $exists[0];
        
        if(IS_POST){
            // 把需要花钱买的会员卡弄出来
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
                'is_virtual' => 1,
                'pic_url'    => $project['logo'],
                'created'    => $today
            );
            
            // 插入
            $productId = array();
            if($exists){
                $goodsId = $exists['id'];
                $products = $Model->query("SELECT id, sku_json FROM mall_product WHERE goods_id=".$goodsId);
                foreach ($products as $item){
                    $sku = decode_json($item['sku_json']);
                    $sku = $sku[0];
                    $productId[$sku['vid']] = $item['id'];
                }
            }
            $goodsId = $exists ? $exists['id'] : $Model->add($goods);
            
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
            $html = addslashes($_POST['content']);
            $skuJson = encode_json($skuJson);
            $skuJson = addslashes($skuJson);
            if($exists){
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
            
            //project_config($projectId, \Common\Model\ProjectConfig::CARD_GOODS_ID, $goodsId);
            
            $this->success();
        }
        
        $this->assign('cards', encode_json($cards));
        $this->assign('detail', $exists['detail']);
        $this->display();
    }
}
?>