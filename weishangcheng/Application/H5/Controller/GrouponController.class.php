<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 团购返现
 * @author lanxuebao
 *
 */
class GrouponController extends CommonController
{
    public function index(){
        $user = $this->user('id');
        
        // 轮播图(缓存5分钟)
        $banners = M("mall_banner")
            ->where("project_id='{$this->project['id']}' AND FIND_IN_SET(1001, area)>0 AND is_show = 1")
            ->order("sort desc, id")
            ->limit("0, 7")
            ->cache(true, 300)
            ->select();
        $this->assign('banners', $banners);
        $this->display();
    }
}
?>