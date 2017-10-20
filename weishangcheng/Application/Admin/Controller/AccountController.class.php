<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 添加修改微信公众账号
 *
 * @author lanxuebao
 *
 */
class AccountController extends CommonController
{
    private $Model;

    function __construct(){
        parent::__construct();
        $this->Model = M("project_appid");
    }

    public function index(){
        if(IS_AJAX) {
            $offset = I('get.offset', 0);
            $limit = I('get.limit', 20);
            $user = $this->user();
            $total = $this->Model->where('project_id = %d', $user['project_id'])->count();
            $data = $this->Model
                ->alias('pa')
                ->field('pa.id, pa.appid, pa.card_id, pa.card_expire, pa.host, pa.give_score, pa.created, pa.give_wallet, pa.give_coupon, project_card.title')
                ->join('project_card ON project_card.id = pa.card_id')
                ->where('pa.project_id = %d', $user['project_id'])
                ->limit($offset,$limit)
                ->select();
            foreach ($data as $k => $v){
                $data[$k]['created'] = date("Y-m-d H:i:s", $v['created']);
                if ($v['card_expire'] == 0){
                    $data[$k]['card_expire'] = '永久';
                }else{
                    $data[$k]['card_expire'] .= '天';
                }
                $data[$k]['url'] = C('PROTOCOL').$v['host'].'/'.$v['id'];
                if ($v['card_id'] == 0){
                    $data[$k]['title'] = '游客';
                }
            }
            $this->ajaxReturn(array('rows' => $data, 'total' => $total));
        }
        $this->display();
    }

    public function edit(){
        $id = I('get.id');
        if (IS_POST){
            $post = I('post.');
            $up = array(
                "url"         => $post['url'],
                "appid"       => $post['appid'],
                "card_id"     => $post['card_id'],
                "give_score"  => $post['give_score'],
                "give_wallet" => $post['give_wallet'],
                "give_coupon" => $post['give_coupon'],
            );
            $res = $this->Model->where("id = '%s'", $post['account_id'])->save($up);
            if ($res > 0){
                $this->success("保存成功");
            }else{
                $this->error("保存失败");
            }
        }
        $user = $this->user();
        $data = $this->Model->where("id = '%s' and project_id = %d", $id, $user['project_id'])->find();
        $cards = $this->Model->table('project_card')->field('id, title')->where("project_id = {$user['project_id']}")->select();
        $give_coupons = $this->Model->table('shop_coupon')->where("shop_id = {$user['shop_id']}")->select();
        $data['url'] = C('PROTOCOL').$data['host'].'/'.$data['id'];
        $this->assign('give_coupons', $give_coupons);
        $this->assign('cards', $cards);
        $this->assign('data', $data);
        $this->display();
    }

    public function delete(){
        $ids = I('post.id');
        $user = $this->user();
        $ids = explode(',', $ids);
        foreach ($ids as $k => $v){
            $ids[$k] = "'".$v."'";
        }
        $ids = implode(',', $ids);
        M()->query("delete from project_appid where id in ($ids) AND project_id = {$user['project_id']}");
        $this->success('删除成功');
    }
}

?>