<?php
namespace H5\Controller;

use Common\Common\CommonController;
use Common\Model\CartModel;
use Common\Model\AddressModel;
use Org\IdWork;

/**
 * 购物车
 * @author 兰学宝
 *
 */
class CartController extends CommonController
{
	/**
     * 购物车列表
     */
	public function index(){
	    $buyer = $this->user();
	    $Model = new \Common\Model\CartModel();
	    $data = $Model->getAll($buyer, $this->projectId);
	    $this->assign('jsonList', json_encode($data));
		$this->display();
	}
	
    /**
     * 加入购物车
     */
    public function add(){
        if(intval($_POST['quantity']) < 1 || !is_numeric($_POST['goods_id']) || !is_numeric($_POST['product_id'])){
            $this->error('非法提交：参数错误');
        }
        //屏蔽测试帐号
        $userid = $this->user('id');
        if($userid == '1000019'){
            $this->error("您当前操作为买家行为 请在微信中查看");
        }
        $cart = array(
            'buyer_id'      => $this->user('id'),
            'goods_id'      => $_POST['goods_id'],
            'product_id'    => $_POST['product_id'],
            'quantity'      => $_POST['quantity'],
            'activity_id'   => $_POST['activity_id'],
            'shop_id'       => $_POST['shop_id'],
        );
        
        $Model = new \Common\Model\CartModel();
        $total = $Model->insert($cart);
        if($total < 1){
            $this->error($Model->getError());
        }
        
        $this->success(array('total' => $total));
    }
    
    /**
     * 删除购物车
     */
    public function delete(){
        $id = $_POST['id'];
        if(!is_numeric($id)){
            $this->error('非法提交：参数错误');
        }
        
        $buyer = $this->user();
        $Model = new CartModel();
        $Model->execute("DELETE FROM mall_cart WHERE id={$id} AND buyer_id=".$buyer['id']);
        $data = $Model->getAll($buyer);
        $this->ajaxReturn($data);
    }

    /**
     * 更新购物车
     */
    public function update(){
        if(!is_numeric($_POST['id']) || !is_numeric($_POST['quantity'])){
            $this->error('非法提交：参数错误');
        }
        
        $buyer = $this->user();
        $Model = new CartModel();
        $Model->update($_POST['id'], $_POST['quantity'], $buyer['id']);
        $data = $Model->getAll($buyer);
        $this->ajaxReturn($data);
    }
    
    /**
     * 获取购物车中的数量
     */
    public function num(){
        $buyerId = $this->user('id', false);
        $num = is_numeric($buyerId) ? D('Cart')->getBuyerNum($buyerId) : 0;
        $this->ajaxReturn(array('num' => $num));
    }
    
    /**
     * 清空失效的宝贝
     */
    public function invalid(){
        $buyerId = $this->user('id');
        M()->execute("DELETE FROM mall_cart WHERE buyer_id='{$buyerId}' AND invalid=1");
        $this->success('已清除');
    }
    
    /**
     * 提交订单
     */
    public function submit(){
        $products = json_decode($_POST['products'], true);
        if(!$products || !is_array($products)){
            $this->error('products is not null');
        }else if(count($products) > 20){
            $this->error('单次结算不能超过20种商品');
        }
        
        //合并产品
        $productList = $cartList = array();
        foreach ($products as $item){
            if(!is_numeric($item['product_id']) || !is_numeric($item['goods_id']) || $item['quantity'] < 1){
                $this->error('非法提交：字段异常');
            }
        
            if(!is_numeric($item['activity_id'])){
                $item['activity_id'] = 0;
            }
        
            $key = $item['product_id'].$item['activity_id'];
            if(!isset($productList[$key])){
                $productList[$key] = array(
                    'goods_id'     => $item['goods_id'],
                    'product_id'   => $item['product_id'],
                    'activity_id'  => $item['activity_id'],
                    'quantity'     => $item['quantity'],
                );
            }else{
                $productList[$key]['quantity'] += $item['quantity'];
            }
            
            if(is_numeric($item['cart_id'])){
                $cartList[$item['cart_id']] = $item['quantity'];
            }
        }
        $productList = array_values($productList);

        $project  = PROJECT;

        if (APP_NAME == P_USER){
            $goodIds = array();
                foreach ($products as $k => $v){
                if (!in_array($v['goods_id'], $goodIds)){
                    $goodIds[] = $v['goods_id'];
                }
            }
            if (!empty($goodIds)){
                $goodIds = implode(',', $goodIds);
                $data = M('mall_goods')
                    ->field('shop_id')
                    ->where("id IN ($goodIds)")
                    ->group('shop_id')
                    ->select();
                if (!empty($data)){
                    if (count($data) > 1){
                        $this->error('多个店铺不能同时结算');
                    }else{
                        $shopId = $data[0]['shop_id'];
                        $projectId = substr($shopId, 0, -3);
                        $project = get_project($projectId);
                    }
                }
            }
        }
        
        $login = $this->user();
        $Model = M('trade_book');
        
        $address = $Model->query("SELECT address FROM trade_book WHERE buyer_id='{$login['id']}' AND address != '' ORDER BY id DESC LIMIT 1");
        $address = $address[0]['address'];
        if(!$address){
            $MAddress = new AddressModel();
            $address = $MAddress->getDefault($login['id']);
            if($address){
                $address = json_encode($address, JSON_UNESCAPED_UNICODE);
            }else{
                $address = '';
            }
        }

        // 数据异常，重新授权登录
        // if(!isset($login[$project['third_mpid']])){
        //     //session('user', null);
        //     $this->error("请在微信端下单");
        //     // $this->getLogin();
        // }

        //屏蔽测试帐号
        $userid = $this->user('id');
        if($userid == '1000019'){
            $this->error("您当前操作为买家行为 请在微信中查看");
        }
        
        // 是否关注
        $subscribe = $Model->query("SELECT subscribe FROM wx_user WHERE openid='{$login['openid']}' AND appid='{$login['appid']}'");
        $subscribe = $subscribe[0]['subscribe'];

        $data = array(
            'id'              => IdWork::nextBookKey(),
            'buyer_id'        => $login['id'],
        	'buyer_appid'     => $login['appid'],
        	'buyer_openid'    => $login[$project['third_mpid']]['openid'],
        	'buyer_subscribe' => $subscribe,
        	'mch_appid'       => $project['third_mpid'],
            'products'        => json_encode($productList, JSON_UNESCAPED_UNICODE),
            'carts'           => encode_json($cartList),
            'created'         => NOW_TIME,
            'redirect'        => $_SERVER['HTTP_REFERER'],
            'client_ip'       => get_client_ip(),
            'address'         => $address,
            'login_type'      => $login['login_type']==NULL?'':$login['login_type']
        );

        $result = $Model->add($data);
        if($result > 0){
            $redirect = C('PAY_URL').'/order/confirm?book_key='.$data['id'].'&ticket='.session_id();
            $this->success('', $redirect);
        }
        $this->error('未知错误');
    }
}
?>