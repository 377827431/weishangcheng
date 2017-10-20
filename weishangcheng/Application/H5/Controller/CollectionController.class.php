<?php
namespace H5\Controller;

use Common\Common\CommonController;
use Think\Model;
use Common\Model\BaseModel;

/**
 * 收藏表
 * @author yjh
 *
 */
class CollectionController extends CommonController
{   
    /**
     * 我的收藏
     */
    public function index(){
        // 投放视图
        if(!IS_AJAX){
           $this->display();
        }
        
        $Model = new BaseModel();
        $buyer = $Model->getTradingBuyer();
        
        // 投放数据
        $offset = is_numeric($_GET['offset']) ? $_GET['offset'] : 0;
        $size = is_numeric($_GET['size']) ? $_GET['size'] : 19;
        
        $uid = $this->user('id');
        $myLevel = $this->user('agent_level');
        
        $sql = "SELECT goods.id, goods.title, goods.price, goods.original_price, goods.pic_url, goods.cat_id, goods.tag_id,
                  goods.images, goods.agent2_price, goods.agent3_price, goods.weight
                FROM mall_goods goods,member_collection m
                WHERE goods.id=m.goods_id and m.mid=$uid
                ORDER BY m.id
                LIMIT {$offset}, {$size}";
        
        $data = $Model->query($sql);
        $data = $Model->goodsListHandler($data, $buyer);
        $this->ajaxReturn($data);
    }
    
    /**
     * 添加收藏
     */
    public function add(){
        if(!is_numeric($_REQUEST['goods_id'])){
            $this->error('商品ID不能为空');
        }

        $Model = M('member_collection');
        $data = array(
            'mid'       => $this->user('id'),
            'goods_id'  => $_REQUEST['goods_id'],
            'created'   => date("Y-m-d H:i:s")
        );
        
        $Model->add($data, null, true);
        $this->success();
    }
    
    /**
     * 移除收藏
     */
    public function delete(){
        $mid = $this->user('id');
        $goods_id = $_POST['goods_id'];
        if(!is_numeric($goods_id)){
            $this->error('商品ID不能为空');
        }
        
        M('member_collection')->where(array("mid" => $mid, 'goods_id' => $goods_id))->delete();
        $this->success();
    }
}
?>