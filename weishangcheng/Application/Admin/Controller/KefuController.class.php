<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 客服管理
 * 
 * @author lanxuebao
 *        
 */
class KefuController extends CommonController
{
    private $Model;
    private function Model(){
        if(is_null($this->Model)){
            $this->Model = new \Admin\Model\KeFuModel();
        }
        return $this->Model;
    }
    
    /**
     * 客服类型
     * @return string[]
     */
    private function getTypes(){
        return \Common\Model\StaticModel::getCustomerServiceType();
    }
    
    private function showList(){
        $where = array();
        if(is_numeric($_GET['type'])){
            $where['type'] = $_GET['type'];
        }
        if($_GET['weixin']){
            $where['weixin'] = array('like', '%'.addslashes($_GET['weixin']).'%');
        }
        
        $Model = $this->Model();
        $list = $Model->getAll($where);
        $this->ajaxReturn($list);
    }
    
    /**
     * 列表
     */
    public function index(){
        if(IS_AJAX){
            $this->showList();
        }
        
        $this->assign('types', $this->getTypes());
        $this->display();
    }
    
    /**
     * 添加
     */
    public function add(){
        if(IS_POST){
            $this->Model()->save($_POST);
            $this->success("添加成功");
        }
        
        $this->assign(array(
            'data' => array(
                'work_start'  => '08:00',
                'work_end'    => '17:00',
                'enabled'     => 1,
                'added'       => 0
            ),
            'types' => $this->getTypes()
        ));
        $this->assignData();
        $this->display("edit");
    }

    
    
    /**
     * 编辑
     * @param number $id
     */
    public function edit($id = 0){
        if(IS_POST){
            $this->Model()->save($_POST);
            $this->success("编辑成功");
        }
        
        $Model = $this->Model();
        $data = $Model->getById($id);
        if(empty($data)){
            $this->error($Model->getError());
        }

        $this->assign(array(
            'data' => $data,
            'types' => $this->getTypes()
        ));
        $this->assignData();
        $this->display();
    }
    
    /**
     * 添加和编辑时的必须数据
     */
    private function assignData(){
        // 投放所有客服
        /*
        $appid = C('KEFU.appid');
        $wechatAuth = new \Org\Wechat\WechatAuth($appid);
        $kfList = $wechatAuth->getKFList();
        if(isset($kfList['errcode'])){
            $this->error('获取微信客服异常：'.$kfList['errmsg']);
        }
        $this->assign('wxKFList', $kfList);
        */
        
        $shopList = $this->shops();
        $this->assign('shopList', $shopList);
    }
    
    /**
     * 删除
     * @param number $id
     */
    public function delete($id = 0){
        $result = $this->Model()->delete($id);
        $this->success('删除成功！');
    }



}