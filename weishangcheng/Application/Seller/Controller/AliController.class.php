<?php

namespace Seller\Controller;
use Org\Alibaba;

class AliController extends ManagerController{

    public function index(){
    	$q = I('q', '', 'addslashes');
        if (IS_AJAX){
    		$offset = I('offset/d', 0);
    		$size = I('size/d', 10);
    		$page = empty($offset) ? 0 : ceil($offset/10);
	    	$shop = M('shop')->find($this->shopId);
	    	$ali = new \Org\Alibaba\AlibabaAuth($shop['aliid']);
	    	$result = $ali->searchGoods($q, $offset, $size);

	    	$goods = array();
	    	if ($result['total'] > 0) {
	    		foreach ($result['toReturn'] as $key => $value) {
	    			# code...
	    		}
	    		$goods[] = array(
	    			'test' => $result
				);
	    	}
	        $this->ajaxReturn($result['toReturn']);
        }
        $this->assign('q', $q);
        $this->display();
    }
}