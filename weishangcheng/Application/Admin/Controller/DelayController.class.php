<?php
namespace Admin\Controller;

use Common\Common\CommonController;
use Common\Model\BalanceType;

/**
 * 后台延迟付款 admin
 *
 * @author lanxuebao
 *
 */
class DelayController extends CommonController
{
    public function index(){
        if(IS_AJAX) {
            $offset = I('get.offset', 0);
            $limit = I('get.limit', 20);
            $model = M('transfers_request');
            $count = $model
                ->where('status = 0')
                ->field("count(*) as count")
                ->limit($offset,$limit)
                ->order('id desc')
                ->find();
            $count = $count['count'];
            $data = $model
                ->field('transfers_request.*, shop.name')
                ->join('shop ON shop.id = transfers_request.shop_id')
                ->where('status = 0')
                ->limit($offset,$limit)
                ->select();
            foreach ($data as &$v){
                $v['created'] = date("Y-m-d H:i:s", $v['created']);
                if($v['type'] == 'bank'){
                    $v['type'] = '银行卡';
                }else if($v['type'] == 'alipay'){
                    $v['type'] = '支付宝';
                    $v['bc_name'] = '无';
                }
            }
            $this->ajaxReturn(array('rows' => $data, 'total' => $count));
        }
        $this->display();
    }

    public function confirm(){
        $id = I('post.id');
        if (!is_numeric($id)){
            return;
        }
        $model = M('transfers_request');
        $tr = $model->where("id = {$id}")->find();
        if (empty($tr['shop_id']) || $tr['shop_id'] == 0){
            $this->error( '请求店铺信息非法！');
        }
        if ($tr['status'] == 0){
            $model->startTrans();
            M()->execute("update `transfers_request` set `status` = 1 WHERE id = {$id}");
            $tr['project_id'] = substr($tr['shop_id'], 0, -3);
            M()->execute("update `shop` set `frozen_balance` = `frozen_balance` - {$tr['amount']} WHERE id = {$tr['shop_id']}");
            $shop_get = M('shop')->field('frozen_balance')->where("id = {$tr['shop_id']}")->find();
            $frozen_balance = $shop_get['frozen_balance'];
            if ($frozen_balance < 0){
                $model->rollback();
                $this->error( '账户冻结资金不足！');
            }
            $BalanceModel = new \Common\Model\BalanceModel();
            $BalanceModel->add_seller(array(
                'balance' => -$tr['amount'],
                'reason'  => '卖家提现扣款',
                'type'    => BalanceType::TRANSFERS,
                'shop_id' => $tr['shop_id'],
            ));
            if ($tr['type'] == 'bank'){
                $add = array(
                    "shop_id" => $tr['shop_id'],
                    "created" => time(),
                    "rid" => $tr['id'],
                    "amount" => $tr['amount'],
                    "bc_name" => $tr['bc_name'],
                    "bc_no" => $tr['bc_no'],
                    "card_name" => $tr['card_name'],
                    "card_no" => $tr['card_no'],
                    "address" => $tr['address'],
                );
                M('bank_transfers')->add($add);
            }
            if ($tr['type'] == 'alipay'){
                $add = array(
                    "shop_id" => $tr['shop_id'],
                    "created" => time(),
                    "rid" => $tr['id'],
                    "amount" => $tr['amount'],
                    "alipay_accounts" => $tr['bc_no'],
                    "alipay_name" => $tr['card_name'],
                    "card_no" => $tr['card_no'],
                );
                M('alipay_transfers')->add($add);
            }
//            if ($tr['type'] == 'weixin'){
//                $add = array(
//                    "project_id" => $tr['project_id'],
//                    "mid" => $tr['mid'],
//                    "created" => time(),
//                    "amount" => $tr['amount'],
//                );
//                M('wx_transfers')->add($add);
//            }
            $model->commit();
        }
        $this->ajaxReturn('1');
    }

    public function cancel(){
        $id = I('post.id');
        if (!is_numeric($id)){
            return;
        }
        $tr = M('transfers_request')->where("id = {$id}")->find();
        if (empty($tr['shop_id']) || $tr['shop_id'] == 0){
            $this->error( '请求店铺信息非法！');
        }
        if ($tr['status'] == 0){
            $model = M('shop');
            $model->startTrans();
            M()->execute("update `transfers_request` set `status` = 2 WHERE id = {$id}");
            $tr['project_id'] = substr($tr['shop_id'], 0, -3);
            M()->execute("update `shop` set `balance` = `balance` + {$tr['amount']}, `frozen_balance` = `frozen_balance` - {$tr['amount']} WHERE id = {$tr['shop_id']}");
            $shop_get = $model->field('frozen_balance')->where("id = {$tr['shop_id']}")->find();
            $frozen_balance = $shop_get['frozen_balance'];
            if ($frozen_balance < 0){
                $model->rollback();
                $this->error( '账户冻结资金不足！');
            }
            $model->commit();
        }
        $this->ajaxReturn('1');
    }

}

?>