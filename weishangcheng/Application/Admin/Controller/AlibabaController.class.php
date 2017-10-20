<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Common\Model\AlibabaModel;
use Org\Alibaba\AlibabaAuth;

/**
 * 阿里巴巴
 */
class AlibabaController extends CommonController
{
    public $authRelation = array(
        'synctrade'   => 'sync',
        'detail'      => 'index'
    );
    
    /**
     * 店铺列表
     */
    public function index(){
        if(IS_AJAX){
            $this->getGoodsList();
        }
        
        // 授权跳转地址
        $authUrl  = (is_ssl() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].__CONTROLLER__.'/auth';
        $Model    = new AlibabaAuth();
        $redirect = $Model->redirectAuth($authUrl);
        $this->assign('auth_url', $redirect);
        
        // 今日可更新次数
        $data = $this->getSyncTimes();
        $this->assign('can_sync', $data['times'] > 0 && NOW_TIME - $data['prev_time'] > 10800);
        $this->display();
    }
    
    /**
     * 获取今日可更新次数
     */
    private function getSyncTimes(){
        $shopId = $this->user('shop_id');
        $Model = M('alibaba_sync');
        $data = $Model->find($shopId);
        $today = date('Y-m-d');
        if(!$data || $data['today'] != $today){
            $sql = "INSERT INTO alibaba_sync
                    SET id={$shopId}, today='{$today}', times=1, prev_time=0
                    ON DUPLICATE KEY UPDATE today=VALUES(today), times='{$data['max_times']}', prev_time=0";
            $Model->execute($sql);
            $data = $Model->find($shopId);
        }
        
        return $data;
    }
    
    /**
     * 同步1688店铺/商品
     * lanxuebao
     */
    public function sync(){
        $shopId = $this->user('shop_id');
        
        $AliModel = new AlibabaModel();
        $start = $AliModel->syncShop($shopId, true);
        if(!$start){
            $this->error($AliModel->getError());
        }
        $this->success('已开始同步');
    }
    
    /**
     * 编辑
     */
    public function edit($id = 0){
        $Model = M('alibaba_shop_returnd');
        if(IS_POST){
            $id = I('post.id/d');
            if(!is_numeric($id)){
                $this->error('编辑ID不能为空');
            }
            if(empty($_POST['receiver_name'])){
                $this->error('退货联系人不能为空');
            }
            if(empty($_POST['receiver_province'])){
                $this->error('退货省份不能为空');
            }
            if(empty($_POST['receiver_city'])){
                $this->error('退货城市不能为空');
            }
            if(empty($_POST['receiver_county'])){
                $this->error('退货区/县不能为空');
            }
            if(empty($_POST['receiver_detail'])){
                $this->error('退货详细地址不能为空');
            }
            if(!is_numeric($_POST['receiver_mobile'])){
                $this->error('退货电话不能为空');
            }
            $data = array(
                'id'                 => addslashes($_POST['id']),
                'receiver_name'      => addslashes($_POST['receiver_name']),
                'receiver_mobile'    => addslashes($_POST['receiver_mobile']),
                'receiver_zip'       => addslashes($_POST['receiver_zip']),
                'receiver_province'  => addslashes($_POST['receiver_province']),
                'receiver_city'      => addslashes($_POST['receiver_city']),
                'receiver_county'    => addslashes($_POST['receiver_county']),
                'receiver_detail'    => addslashes($_POST['receiver_detail'])
            );
            $Model->save($data);
            $this->success('已保存');
        }
        $data = $Model->query("select * from alibaba_shop left join alibaba_shop_returnd as asr on asr.name = alibaba_shop.login_id where alibaba_shop.id = '".$_GET['id']."'");
        if(empty($data)){
            $this->error('不能为空');
        }
        $data = $data[0];
        $this->assign('data',$data);
        $this->display();
    }
    
    /**
     * 1688授权处理
     */
    public function auth(){
        $Model = new AlibabaAuth();
        $data = $Model->setAuth($_GET['code'],$_GET['state']);
        
        // 更新店铺和1688账号关系
        M()->execute("UPDATE shop SET aliid='{$data['id']}' WHERE id=".$this->user('shop_id'));
        redirect(__CONTROLLER__);
    }
    
    /**
     * 商品列表
     */
    private function getGoodsList(){
        $offset = I('get.offset', 0);
        $limit  = I('get.limit', 50);
        $shopId = $this->user('shop_id');
        
        $Model  = M();
        $shop   = $Model->query("SELECT aliid FROM shop WHERE id=".$shopId);
        $shop   = $shop[0];
        if(!$shop['aliid']){
            $this->error('本店铺未绑定1688账号，请先授权');
        }
        $tokenId= $shop['aliid'];
        $data = array('total' => 0, 'rows' => array());
        
        $where = array();
        if(is_numeric($_GET['kw']) && strlen($_GET['kw']) == 12){
            $where[] = "relation.id='{$_GET['kw']}'";
        }else if(!empty($_GET['kw'])){
            $where[] = "MATCH (goods.key_word) AGAINST ('".addslashes($_GET['kw'])."'IN BOOLEAN MODE)";
        }if(!empty($_GET['login_id'])){
            $loginId = addslashes($_GET['login_id']);
            $where[] = "goods.login_id='{$loginId}'";
        }
        $where = count($where) > 0 ? 'WHERE '.implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) AS total
                FROM alibaba_relation AS relation
                INNER JOIN alibaba_goods AS goods ON goods.id=relation.id
                {$where}";
        $total = $Model->query($sql);
        $total = $total[0]['total'];
        if($total == 0){
            $this->ajaxReturn($data);
        }
        $data['total'] = $total;
        
        $sql = "SELECT relation.id, relation.relation, relation.last_sync, goods.price_range,
                    goods.subject, goods.login_id, goods.subject, goods.price, goods.stock,
                    goods.retailprice, goods.daixiao_price, goods.min_order_quantity, goods.unit,
                    mall_goods.id AS goods_id, goods.images, mall_goods.is_display, goods.status,
                    mall_goods.price AS sold_price
                FROM alibaba_relation AS relation
                INNER JOIN alibaba_goods AS goods ON goods.id=relation.id
                LEFT JOIN mall_goods ON mall_goods.tao_id=relation.id
                {$where}
                LIMIT {$offset},{$limit}";
        $data['rows'] = $Model->query($sql);
        
        $relation = array(1 => '大市场', 2 => '代销', 3 => '微供');
        foreach ($data['rows'] as $i=>$item){
            $images = json_decode($item['images'], true);
            $item['pic_url'] = $images[0];
            unset($item['images']);
            
            if($item['relation'] == 1){
                $range = json_decode($item['price_range'], true);
                $max = current($range);
                if(count($range > 1)){
                    $min = end($range);
                    $item['price'] = '<div title="进价">'.$min.'~'.$max.'</div>';
                }else{
                    $item['price'] = '<div title="进价">'.$max.'</div>';
                }
            }else if($item['relation'] == 2){
                $item['price'] = '<div title="进价">'.$item['daixiao_price'].'</div>';
            }else{
                $item['price'] = '<div title="我的进价">'.$item['price'].'</div>';
            }
            
            $item['price'] .= '<div title="商家建议" class="color-gray">'.($item['retailprice'] > 0 ? $item['retailprice'] : '无').'</div>';
            $item['price'] .= '<div title="我的售价" class="color-gray">'.($item['sold_price'] ? $item['sold_price'] : '无').'</div>';
            
            unset($item['price_range']);
            unset($item['daixiao_price']);
            $item['last_sync'] = date('Y-m-d H:i', $item['last_sync']);
            $item['relation']  = $relation[$item['relation']];
            $data['rows'][$i] = $item;
        }
        
        $this->ajaxReturn($data);
    }
    
    /**
     * 同步淘系订单
     * @return multitype:unknown Ambigous <>
     */
    public function syncTrade(){
        $tid = $_GET['tid'];
        if (!is_numeric($tid)) {
            $this->error('错误的订单ID。');
        }
        $result = D('Alibaba')->getAliTrade($tid);

        if(!empty($result[$tid])){
            $this->success('已同步');
        }
        
        $this->success('无更新内容');
    }
    
    //商品详情
    public function detail(){
        $taoId = $_GET['tao_id'];
        $shopId = $this->user('shop_id');
        $shop = M('shop')->find($shopId);
        $tokenId = $shop['aliid'];
        if(!is_numeric($taoId)){
            $this->error('offerId错误');
        }
        
        $Model = new AlibabaModel();
        $result = $Model->syncGoods($taoId,'3027680123',false,$tokenId);
        
        if(!empty($result['error'])){
            $this->error($result['error']);
        }

        $data = $Model->find($taoId);
        $this->assign('data', $data);
        $this->display();
    }
    
    /**
     * 商品类目(三级)
     */
    public function category(){
        $this->display();
    }
    
    /**
     * 同步1688类目
     */
    public function sync_category(){
        $url = C('SERVICE_URL').'/ali/category';
        M('alibaba_category')->execute("TRUNCATE TABLE alibaba_category");
        
        $AlibabaAuth = new \Org\Alibaba\AlibabaAuth();
        $list = $AlibabaAuth->category(0);
        foreach ($list[0]['childIDs'] as $id){
            $param = array(
                'timestamp' => time(),
                'noncestr'  => \Org\Util\String2::randString(16),
                'id'        => $id
            );
            $param['sign'] = create_sign($param);
            sync_notify($url, $param);
        }
        print_data('已开始同步');
    }
}
?>