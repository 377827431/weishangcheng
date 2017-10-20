<?php
namespace H5\Controller;

use Common\Common\CommonController;

/**
 * 商品列表
 * @author Administrator
 *
 */
class MallController extends CommonController
{
    public function index(){
        $user = $this->user('id');

        // 轮播图(缓存5分钟)
        if(is_numeric($_GET['tag_id'])){
            $banners = M("mall_banner")
            ->where("project_id='{$this->project['id']}' AND FIND_IN_SET('{$_GET['tag_id']}', area)>0 AND is_show = 1")
            ->order("sort desc, id")
            ->limit("0, 7")
            // ->cache(true, 300)
            ->select();
            $this->assign('banners', $banners);
        }
        //商品分类tag
        $tag_list = M('mall_tag')
            ->field('id, name')
            ->where("`project_id` = '{$this->projectId}' AND `level` = 1 AND `pid` = 0")
            ->select();
        $this->assign('tag_list', $tag_list);
        if (is_numeric($_GET['tag_id']) && $_GET['tag_id'] > 0){
            $tag_id = $_GET['tag_id'];
        }else{
            $tag_id = 0;
        }
        $this->assign('tag_id', $tag_id);
        
        $this->assign('sort', $_GET['sort'] ? $_GET['sort'] : 'zonghe');
        $this->display();
    }

    /**
     * 商城首页产品列表
     */
    public function search(){
        if($_GET['tag_id'] == 1006){
            
        }

        // 检测是否登录
        $buyer = $this->user();
        
        // 进入不同业务场景
        $activeList = include COMMON_PATH.'/Conf/activity.php';
        $params = $_GET;
        $params['project_id'] = C('project.id');
      
        $ModelName = (is_numeric($params['tag_id']) && isset($activeList[$params['tag_id']]))
            ? $activeList[$params['tag_id']]['model']
            : '\Common\Model\GoodsViewModel';
            
        $Model = new $ModelName();
        $data = $Model->search($params, $buyer);

        // 保存搜索历史记录
        $kw = $_GET['kw'];
        if($kw != ''){
            $search = cookie('search_goods');
            $searchList = !empty($search) ? explode(';', $search) : array();
            $key = array_search($kw, $searchList);
            if($key !== false){
                array_splice($searchList, $key, 1);
            }
            array_unshift($searchList, $kw);
            if(count($searchList) > 20){
                array_splice($searchList, 20);
            }
            $search = implode(';', $searchList);
            cookie('search_goods', $search, 2592000);
        }
        $this->ajaxReturn($data);
    }
}
?>