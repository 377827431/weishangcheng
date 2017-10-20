<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Common\Model\BaseModel;
use Common\Model\ExpressModel;

/**
 * 评论
 *
 * 
 *
 */
class PraiseController extends CommonController
{
    private $shopId;
    private $allShop;
    public $authRelation = array(
        'goods'       => 'index'
    );
    
    function __construct(){
        parent::__construct();
        $this->shopId = $this->user('shop_id');
        $this->allShop = \Common\Common\Auth::get()->validated('admin','shop','all');
    }
    
    public function index(){
        $goods_id = $_REQUEST['goods_id'];
        if(IS_AJAX){
            if(empty($goods_id)){
                $this->showList();
            }
            else{
                $this->showList1($goods_id);
            }
        }
        
        $this->display();
    }
    /*
     * 查询
     * */
    private function showList(){
        $data = array('rows' => null, 'total' => 0);
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $Model = M();
        $sql = "SELECT * FROM mall_trade_rate LIMIT {$offset}, {$limit}";
        $list = $Model->query($sql);
        $data['rows'] = $list;
        $data['total'] = count($list);
        $this->ajaxReturn($data);
    }
    private function showList1($goods_id){
        $data = array('rows' => null, 'total' => 0);
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $Model = M();
        $sql = "SELECT * FROM mall_trade_rate where goods_id like '%{$goods_id}%' AND visible=1  LIMIT {$offset}, {$limit}";
        $list = $Model->query($sql);
        $data['rows'] = $list;
        $data['total'] = count($list);
        $this->ajaxReturn($data);
    }
  
   
}
?>