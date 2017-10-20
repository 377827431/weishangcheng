<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Org\Util\String2;

/**
 * 优惠券
 * @author Administrator
 *
 */
class CouponController extends CommonController{
    private $allShop = false;
    function __construct(){
        parent::__construct();
        $this->allShop = $this->authAllShop();
    }
    
    public function index(){
        if(IS_AJAX){
            $this->showList();
        }
        $this->display();
    }
    
    private function showList(){
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
    
        $where = array();
        if(!empty($_GET['name'])){
            $where[] = "name like '%".$_GET['name']."%'";
        }
        $where = count($where) > 0 ? 'WHERE '.implode(' AND ', $where) : '';
    
        $data = array('total' => 100, 'rows' => array());
        $sql = "SELECT * FROM mall_coupon {$where} LIMIT {$offset}, {$limit}";
        $Model=M();
        $list = $Model->query($sql);
        foreach ($list as $i=>$item){
            $list[$i]['start_time'] = date('m-d H:i:s',$item['start_time']);
            $list[$i]['end_time'] = date('m-d H:i:s',$item['end_time']);
            $list[$i]['status'] = $item['status'] == 0?'失效':'有效';
        }
        $data['rows'] = $list;
        $data['total'] = count($Model->query("SELECT * FROM mall_coupon"));
        $this->ajaxReturn($data);
    }
    
    public function add(){
        $myShop = array('id' => $this->user('shop_id'), 'name' => $this->user('shop_name'));
        // 显示页面
        if(IS_POST){
            $this->save($myShop);
            $this->success('优惠券创建成功！');
        }
        
        $shops = $this->shops();
        $card  = $this->card();
        $this->assign(array(
            'card'  => $card,
            'shops' => $shops,
            'all_shop' => $this->allShop,
            'data'    => array('range_type' => 0)
        ));
        $this->display('edit');
    }
    
    /**
     * 保存优惠券
     */
    private function save($myShop){
        if(!$this->allShop && $_POST['shop_id'] != $myShop['id']){
            $this->error('您无权为其他店铺添加优惠券');
        }else if(empty($_POST['shop_id'])){
            $_POST['shop_id'] = $myShop['id'];
        }
        // 店铺优惠券
        $shopCoupon = $shopIdList = $shopid=$goodsList = array();
        $Model = M('mall_coupon');
        $project_id = session('user.project_id');
        $shop_id = $Model->query("SELECT id FROM shop WHERE id between ".$project_id."001 and ".$project_id.'999');
        if(empty($shop_id)){
            $this->error('不存在店铺');
        }
        foreach ($shop_id as $i=>$item){
            $shop_ids .=','. $shop_id[$i]['id'];
            $shop_ids = ltrim($shop_ids,',');
        }
        $coupon = array(
            'type'       => 1,
            'coupon_code'=> uniqid(),
            'project_id' => $project_id,
            'status'     => $_POST['status'] ? 1 : 0,
            'name'       => $_POST['name'],
            'start_time' => strtotime($_POST['start_time']),
            'end_time'   => strtotime($_POST['end_time']),
            'meet'       => $_POST['meet'],
            'value'      => preg_replace('(，|-|~|;|；)', ',', $_POST['value']),
            'total'      => $_POST['total'],
            'quota'      => $_POST['quota'],
            'notice'     => $_POST['notice'],
            'range_type' => $_POST['range_type'],
            'range_value'  => $_POST['range_value']?$_POST['range_value']:'0',
            'range_exclude'  => $_POST['range_exclude'],
            'shop_id'    => $_POST['shop_id'],
            'shop_ids'   => empty($_POST['shop_ids'])?$shop_ids:$_POST['shop_ids'],
            'member_level'    => $_POST['member_level']
        );
        $this->coupon($coupon);
        $coupon['id'] = $Model->add($coupon);
        if($coupon['id'] < 1){
            $this->error('添加失败');
        }
        $Model->commit();
    }
    
    /**
     * 编辑优惠券
     */
    public  function edit(){
        $id = $_REQUEST['id'];
        if(!is_numeric($id)){
            $this->error('ID不能为空');
        }
        $shops = $this->shops();
        $card  = $this->card();
        $Model = M('mall_coupon');
        $data = $Model->find($id);
        $data['shop_ids'] = explode(',',$data['shop_ids']);
        $data['member_level'] = explode(',',$data['member_level']);
        $data[start_time] = date('m-d H:i:s',$data[start_time]);
        $data[end_time] = date('m-d H:i:s',$data[end_time]);
        $data['id'] = $id;
        if(IS_POST){
            $shop_id = session('user.shop_id');
            $project_id = session('user.project_id');
            $edit_id = $_POST['id']; 
            $coupon = array(
                'name'       => $_POST['name'],
                'project_id' => $project_id,
                'start_time' => mktime($_POST['start_time']),
                'end_time'   => mktime($_POST['end_time']),
                'meet'       => $_POST['meet'],
                'value'      => preg_replace('(，|-|~|;|；)', ',', $_POST['value']),
                'total'      => $_POST['total'],
                'quota'      => $_POST['quota'],
                'notice'     => $_POST['notice'],
                'range_type' => $_POST['range_type'],
                'range_value'  => $_POST['range_value']?$_POST['range_value']:'0',
                'range_exclude'  => $_POST['range_exclude'],
                'shop_id'    => $shop_id,
                'shop_ids'   => $_POST['shop_ids'],
                'member_level'    => $_POST['member_level']
            );
            $this->coupon($coupon);
            $Model->where('id='.$edit_id)->save($coupon);
            $Model->commit();
            $this->success('编辑优惠券成功');
        }
        $this->assign(array(
            'card'  => $card,
            'shops' => $shops,
            'all_shop' => $this->allShop,
            'data'    => $data
        ));
        $this->display('edit');
    }
    
    /**
     * 删除
     */
    public function delete(){
        $id = $_POST['id'];
        if(empty($id)){
            $this->error('ID不能为空');
        }
        $id = addslashes($id);
        M()->query("DELETE from mall_coupon where id in ($id)");
        $this->success('已删除');
    }
    /*
     * 添加优惠券
     * 
     */
    public function coupon($coupon){
        // 添加优惠券
        $Model = M('mall_coupon');
        $Model->startTrans();
        $rangeType = $_POST['range_type'];
        $shopIdList = explode(',', $coupon['shop_ids']);
        $count = "";
        if($rangeType == 1){
            $shop_id = $Model->query("SELECT shop_id FROM mall_goods WHERE id in ({$coupon['range_value']})");
            foreach ($shop_id as $i=>$item){
                if(!in_array($item['shop_id'],$shopIdList)){
                    $this->error('ID【'.$coupon['range_value'].'】不适合选中适用店铺');
                }
            }
        }
        if($rangeType == 2){
            $group = explode(',', $coupon['range_value']);
            foreach ($group as $i=>$item){
                $tag = $Model->query("SELECT * FROM mall_tag WHERE id ={$item}");
                if(empty($tag)){
                    $this->error('ID【'.$item.'】分组不存在');
                }
                $tag = $tag[0];
                if($tag['project_id'] != $coupon['project_id']){
                    $this->error('ID【'.$item.'】与登陆项目不属于同一项目');
                }
            }
        }
        if($rangeType == 3){
            $classify = explode(',', $coupon['range_value']);
            foreach ($classify as $i=>$item){
                $category = $Model->query("SELECT * FROM mall_category WHERE id ={$item}");
                if(empty($category)){
                    $this->error('ID【'.$item.'】分类不存在');
                }
                $category = $category[0];
                if($category['project_id'] != $coupon['project_id']){
                    $this->error('ID【'.$item.'】与登陆项目不属于同一项目');
                }
            }
        }
    }
}
?>