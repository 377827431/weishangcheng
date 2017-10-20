<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 首页
 * @author lanxuebao
 *
 */
class IndexController extends CommonController
{
	/**
     * 商城首页
     */
    public function index(){
        redirect(__MODULE__.'/shop');
    }
    
    private function banners($key){
        $Model = M('mall_banner');
        $banners = $Model
            ->where("project_id='".PROJECT_ID."' AND FIND_IN_SET('$key',area) and is_show = 1")
            ->order("sort desc, id")
            // ->cache(true, 600)
            ->select();
        
        $topbanners = $bottombanners = array();
        if($banners){
            foreach($banners as $item){
                if($item['position'] == 1){
                    $topbanners[] = $item;
                }else {
                    $bottombanners[] = $item;
                }
            }
        }
        
        $this->assign(array(
            'topbanners'   => $topbanners,
            'bottombanners'=> $bottombanners,
        ));
    }
    
    public function login_out(){
        session('[destroy]');
        exit('<script>alert("已注销登录");</script>');
    }
}
?>