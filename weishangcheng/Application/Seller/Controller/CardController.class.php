<?php
/**
 * 会员卡
 * lanxuabo
 */
namespace Seller\Controller;


use Org\IdWork;

class CardController extends ManagerController {
    public function index(){
        
    }
    
    /**
     * 图文
     */
    public function news(){
        $Model = M();
        $projectId= IdWork::getProjectId($this->shopId);
        $cards = $Model->query("select id, title, price from project_card where id BETWEEN {$projectId}1 AND {$projectId}9 AND price>0");
        $project = get_project($projectId);
        
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
                'pic_url'    => '',
            	'is_virtual' => 1,
            	//'pic_url'    => $project['logo'],
            	'pic_url'    => C('CDN').'/img/mall/member_card.jpg',
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
            
            project_config($projectId, \Common\Model\ProjectConfig::CARD_GOODS_ID, $goodsId);
        }
        
        $this->assign('cards', encode_json($cards));
        $this->assign('detail', $exists['detail']);
        $this->display();
    }
}
?>