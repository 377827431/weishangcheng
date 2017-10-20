<?php
namespace Service\Controller;

use Think\Controller;
use Org\IdWork;

/**
 * 商品API
 * @author 兰学宝
 *
 */
class GoodsController extends Controller{
    
    /**
     * 保存pv/uv
     */
    public function savepv(){
        // 安全校验
        $sign = create_sign($_GET);
        if($sign != $_GET['sign']){
            $this->error('签名错误');
        }
        
        if(!is_numeric($_GET['buyer_id']) || !is_numeric($_GET['goods_id']) || !is_numeric($_GET['cat_id'])){
            $this->error('参数错误：'.json_encode($_GET), true);
        }
        
        $buyerId = $_GET['buyer_id'];
        $goodsId = $_GET['goods_id'];
        $catId = $_GET['cat_id'];
        $modify = date('His');
        
        $Model = M();
        $id = IdWork::getLikeId();
        $where = "WHERE id='{$id}' AND user_id={$buyerId} AND goods_id={$goodsId}";
        $existsUv = $Model->query("SELECT id FROM mall_goods_uv {$where} LIMIT 1");
        if(count($existsUv) > 0){
            $Model->execute("UPDATE mall_goods_uv SET times=times+1, is_del=0, modify='{$modify}' ".$where);
            $Model->execute("UPDATE mall_goods_sort SET pv=pv+1 WHERE id=".$goodsId);
        }else{
            $Model->execute("INSERT INTO mall_goods_uv SET id='{$id}', user_id={$buyerId}, goods_id={$goodsId}, cat_id='{$catId}', modify='{$modify}', is_del=0");
            $Model->execute("UPDATE mall_goods_sort SET pv=pv+1, uv=uv+1 WHERE id=".$goodsId);
        }
    }
    
    // 清空购物车
    public function clearCart(){
        $sign = create_sign($_GET);
        if($sign != $_GET['sign']){
            $this->error('签名错误');
        }
        
        $Model = M();
        if(!is_numeric($_POST['id'])){
            $this->error('id不能为空');
        }
        $Model->execute("DELETE FROM trade_book WHERE id='{$_POST['id']}'");
        
        $carts = json_decode($_POST['carts'], true);
        foreach ($carts as $id=>$quantity){
            if(!is_numeric($id) || !is_numeric($quantity)){
                break;
            }
            
            $result = $Model->execute("DELETE FROM mall_cart WHERE id='{$id}' AND quantity<={$quantity}");
            if($result < 1){
                $Model->execute("UPDATE mall_cart SET quantity=quantity-{$quantity} WHERE id='{$id}'");
            }
        }
    }
}
?>