<?php
namespace H5\Controller;

use Common\Common\CommonController;
use Common\Model\CategoryModel;

/**
 * 商品类目
 * @author lanxuebao
 *
 */
class TagController extends CommonController{
    
    public function index(){
        $list = M('mall_tag')->order("pid, sort DESC")->select();
        $from=$_REQUEST['from'];
        $rows = array();
        foreach ($list as $item){
            $rows[$item['pid']][] = $item;
        }
        $this->assign('list', $rows);
        $this->assign('from', $from);
        $this->display(IS_AJAX ? 'ajax' : '');
    }
}
?>