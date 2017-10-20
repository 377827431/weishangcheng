<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Common\Model\ExpressModel;
use Common\Model\CityModel;

/**
 * 运费模板
 * @author yujinghua
 *
 */
class FreightTemplateController extends CommonController{
    private $myShopId;
    function __construct(){
        parent::__construct();
        $this->myShopId = $this->user('shop_id');
    }
    
    public function index(){
        if(!IS_AJAX){
            $this->display();
        }
        $Model = new ExpressModel();
        $list = $Model->getShopFreightTemplates($this->myShopId);
        $this->ajaxReturn($list);
    }
    
    private function assginDefault(){
        $data = array(
                    'express' => array(10),
                    'type'    => 0,
                    'default' => array(
                        'order' => 1,
                        'payment' => 0,
                        'start' => '',
                        'postage' => '',
                        'plus' => '',
                        'postage_plus' => ''
                    ),
                    'specials' => array()
                );
        $this->assign('default', json_encode($data));
        return $data;
    }
    
    /**
     * 添加运费模板
     */
    public function add(){
        $Model = new ExpressModel();
        if(IS_POST){
            $data = $_POST;
            $templates = json_decode($templates, true);
            $data['created'] = date('Y-m-d H:i:s');
            $data['config'] = json_encode($data['templates'], JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE);
            $data['shop_id'] = $this->myShopId;
            $data['checked'] = $data['templates'][0]['express'][0];
            $Model->add($data);
            $this->success();
        }
        
        $data = array(
            'name'  => '',
            'templates' => array($this->assginDefault())
        );
        
        $expressList = $Model->getAllExpress();
        $this->assign(array(
            'expressList' => $expressList,
            'data'        => $data,
            'list'        => json_encode($data['templates'], JSON_UNESCAPED_UNICODE)
        ));
        $this->display('edit');
    }
    
    public function edit(){
        $id = $_REQUEST['id'];
        if(!is_numeric($id)){
            $this->error('ID不能为空');
        }

        $Model = new ExpressModel();
        $data = $Model->find($id);
        if($data['shop_id'] != $this->myShopId){
            $this->error('您无权修改其他店铺的模板');
        }
        
        if(IS_POST){
            $data = $_POST;
            $data['config'] = json_encode($_POST['templates'], JSON_NUMERIC_CHECK|JSON_UNESCAPED_UNICODE);
            $result = $Model->where("id=".$id)->save($data);
            if($result > 0){
                $this->baoyou($data['id']);
            }
            $this->success();
        }
        
        if($data['send_place'] > 0){
            $City = new CityModel();
            $county = $City->find($data['send_place'], true);
            $data['count_id'] = $county['id'];
            $city = $City->find($county['pcode'], true);
            $data['city_id'] = $city['id'];
            $province = $City->find($city['pcode'], true);
            $data['province_id'] = $province['id'];
        }
        $data['templates'] = json_decode($data['config'], true);
        
        $expressList = $Model->getAllExpress();
        $this->assign(array(
            'expressList' => $expressList,
            'data'        => $data,
            'list'        => json_encode($data['templates'], JSON_UNESCAPED_UNICODE)
        ));
        $this->assginDefault();
        $this->display();
    }
    
    /**
     * 包邮标记
     */
    public function baoyou($id){
        // 包邮标记
        $express = new \Common\Model\ExpressModel();
        // 查找商品信息，id weight tag_id
        $goods_list = $express->query("SELECT id,weight,tag_id from mall_goods where freight_id='{$id}' and is_del=0");
        $template = $express->query("SELECT * from template_freight where id='{$id}'");
        foreach ($goods_list as $i=>$val){
            $val['tag_id'] = explode(',',$val['tag_id']);
            $expressFee = $express->getRangeFee($template[0], $val['weight']);
            if($expressFee['baoyou']){
                if(in_array(1000, $val['tag_id'])){
                    continue;
                }
                array_unshift($val['tag_id'], 1000);
            }else{
                $i = array_search(1000, $val['tag_id']);
                if($i > -1){
                    unset($val['tag_id'][$i]);
                }else {
                    continue;
                }
            }
            $val['tag_id'] = array_unique($val['tag_id']);
            $val['tag_id'] = implode(',', $val['tag_id']);
            $express->query("update mall_goods SET tag_id='{$val['tag_id']}' where id='{$val['id']}'");
        }
    }
    
    /**
     * 删除
     */
    public function delete(){
        $arry = explode(",", $_REQUEST['id']);
        $Model = new ExpressModel();
        foreach ($arry as $freight_tid){
            if(!is_numeric($freight_tid)){
                $this->error('ID不能为空');
            }
            
            $data = $Model->find($freight_tid);
            if($data['shop_id'] != $this->myShopId){
                $this->error('您无权修改其他店铺的模板');
            };
            $template = M('mall_goods')->where("freight_id=".$freight_tid)->find();
            if($template){
                $this->error('模板正在运用，删除失败！');
            }
            
            $result = $Model->query("DELETE FROM template_freight WHERE id =".$freight_tid);
        }
        $this->success('删除成功！');
    }
}
?>