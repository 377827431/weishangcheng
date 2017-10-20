<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/9/4
 * Time: 19:01
 */

namespace H5\Controller;
use Common\Common\CommonController;
use H5\Model\GoodsModel;


class TraceController extends CommonController
{
    public function index(){
        if (IS_AJAX){
            $Model = new \Common\Model\TraceModel();
            $mid = $this->user('id');
            $params = I('get.');
            $data = $Model->traceShop($mid, $params);
            $this->ajaxReturn($data);
        }
        $this->display();
    }

    public function viewed(){
        if (IS_AJAX){
            $userId = $this->user('id');
            $params = I('get.');
            $offset = is_numeric($params['offset']) ? $params['offset'] : 0;
            $size = is_numeric($params['size']) ? $params['size'] : 6;
            $data = M('mall_goods_uv')
                ->alias('uv')
                ->field("uv.id as ids, g.*")
                ->join('mall_goods as g on g.id = uv.goods_id', 'inner')
                ->where(array("uv.user_id" => $userId, "uv.is_del" => 0))
                ->group('id')
                ->order('`modify` desc')
                ->limit("{$offset}, {$size}")
                ->select();
            if (!empty($data)){
                $pr = array();
                foreach ($data as $k => $v){
                    $pr[] = substr($v['shop_id'], 0, -3);
                }
                $pr = implode(',', $pr);
                $re = M('project')->where("id IN ({$pr})")->select();
                $alias = array();
                foreach ($re as $k => $v){
                    $alias[$v['id']] = $v['alias'];
                }
                foreach ($data as $k => $v){
                    $projectId = substr($v['shop_id'], 0, -3);
                    $data[$k]['alias'] = $alias[$projectId];
                }
            }
            $this->ajaxReturn($data);
        }
        $this->display();
    }

    public function delete(){
        if (IS_AJAX){
            $userId = $this->user('id');
            $id = I('post.id', 0);
            if (is_numeric($id) && $id > 0){
                $data = array(
                    "is_del" => 1
                );
                M('mall_goods_uv')->where(array("goods_id" => $id, "user_id" => $userId))->save($data);
            }
            $this->ajaxReturn(1);
        }
    }

    /**
     * 删除浏览过的店铺
     */
    public function delete_shop(){
        if (IS_AJAX){
            $userId = $this->user('id');
            $id = I('post.id', 0);
            if (is_numeric($id) && $id > 0){
                $data = array(
                    "is_del" => 1
                );
                M('shop_trace')->where(array("mid" => $userId, "shop_id" => $id))->save($data);
            }
            $this->ajaxReturn(1);
        }
    }
}