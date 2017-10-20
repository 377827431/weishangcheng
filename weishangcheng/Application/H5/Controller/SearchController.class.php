<?php
namespace H5\Controller;

use Common\Common\CommonController;
use H5\Model\GoodsModel;

/**
 * 搜索
 * @author lanxuebao
 *
 */
class SearchController extends CommonController
{
    public function index(){
        if($_GET['tag_id'] == 106){
            $this->error('攻城狮正在建设中，敬请期待');
        }

        $login = $this->user();
        $project = get_project(PROJECT_ID);
        // 进入不同业务场景
        $activeList = include COMMON_PATH.'/Conf/activity.php';
        $params = $_GET;
        $params['project_id'] = $project['id'];

        $ModelName = (is_numeric($params['tag_id']) && isset($activeList[$params['tag_id']]))
            ? $activeList[$params['tag_id']]['model']
            : '\H5\Model\GoodsModel';

        $Model = new $ModelName();
        $data = $Model->search($params, $login);

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
//        print_data($data);
        $this->ajaxReturn($data);
    }

    /**
     * 猜你喜欢
     */
    public function like(){
        $login = $this->user();
        $goodsModel = new GoodsModel();
        $goods_list = $goodsModel->getLikeGoods($login, PROJECT_ID);
        $this->ajaxReturn($goods_list);
    }

    public function tuijian(){
        $login = $this->user();
        $goodsModel = new GoodsModel();
        $goods_list = $goodsModel->getTuijianGoods($login, PROJECT_ID);
        $this->ajaxReturn($goods_list);
    }
}
?>