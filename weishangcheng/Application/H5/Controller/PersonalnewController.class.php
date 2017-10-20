<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/9/26
 * Time: 10:58
 */

namespace H5\Controller;


use Common\Common\CommonController;
use Common\Model\OrderStatus;
use Common\Model\MemberModel;

class PersonalnewController extends CommonController
{
    public function index(){
        $user = $this->user();
        if(empty($user['headimgurl'])){
            $user['headimgurl'] = "https://seller.xingyebao.com/img/logo_108.png";
        }
        if($user['id'] == '1000019'){
            $this->assign('testMember','1');
        }else{
            $this->assign('testMember','-1');
        }
        $orderNum = $this->getOrderCountNew($user['id']);
        $Model = new MemberModel();
        $user = $Model->getPersonalInfo($user['id'], $project);

        $this->assign('user', $user);
        $this->assign('orderNum', $orderNum);
        $this->display();
    }

    /**
     * 保存个人资料
     */
    public function save(){
        $data = array(
            'sex'           => $_POST['sex'],
            "name"          => $_POST['name'],
            "mobile"        => $_POST['mobile'],
            "province_id"   => $_POST['province_id'],
            "city_id"       => $_POST['city_id'],
            "county_id"     => $_POST['county_id'],
            "address"       => $_POST['address'],
        );

        if(! preg_match('/^1[3|4|5|7|8]\d{9}$/', $data['mobile'])){
            $this->error('手机号格式错误');
        }

        $login = $this->user();
        $project = get_project(PROJECT_ID);
        $Model = new MemberModel();
        $user = $Model->getPersonalInfo($login['id'], $project);

        if ($user['mobile']+'' != $data['mobile']+'') {
            $check = session('update_mobile');
            if(!is_array($check) || $check['code'] != $_POST['checknum'] || $check['mobile'] != $data['mobile']){
                $this->error('验证码无效');
            }
        }
        // 保存
        $Model->where("id=".$login['id'])->save($data);
        if(empty($_POST['yqm'])){
            $this->success($data);
        }

        $yqm = addslashes($_POST['yqm']);
        $timestamp = NOW_TIME;
        $sql = "SELECT invitation.id, invitation.card_id, invitation.quantity, invitation.used, invitation.expires_in,
                    invitation.type, record.mid, record.used_time
                FROM member_invitation_code AS invitation
                INNER JOIN member_invitation_record AS record ON invitation.id=record.id AND record.`code`='{$yqm}'
                WHERE invitation.card_id BETWEEN {$project['id']}001 AND {$project['id']}999 AND invitation.expires_in>{$timestamp} LIMIT 1";
        $invitation = $Model->query($sql);
        $invitation = $invitation[0];
        if(empty($invitation)){
            $this->success('邀请码无效');
        }if($invitation['type'] == 1 && $invitation['quantity'] - $invitation['used'] < 1){ // 通用码
            $this->success('您来晚了，被邀请码名额已达上限');
        }if($invitation['mid'] > 0){
            $this->success('邀请码在 '.date('Y年m月d日H点i分', $invitation['used_time']).' 被使用');
        }

        $cards = get_member_card($project['id']);
        if(!isset($cards[$invitation['card_id']])){
            $this->success('邀请码无效：会员卡已被删除');
        }
        $card = $cards[$invitation['card_id']];

        if($user['card_id'] > $card['id']){
            $this->success('您当前等级高于邀请码等级，无需更改！');
        }

        $expireDay = 0;
        if($card['expire_time'] > 0){
            $expireDay = date('+'.$card['expire_time'].' day');
        }

        if($invitation['type'] == 1){   // 通用码插入
            $yqm2 = $invitation['used'] + 1;
            $sql = "INSERT INTO member_invitation_record
                    SET id={$invitation['id']}, `code`='{$yqm2}', mid={$user['id']}, used_time={$timestamp}";
        }else{ // 单码更新
            $sql = "UPDATE member_invitation_record SET mid={$user['id']}, used_time={$timestamp}
                    WHERE id={$invitation['id']} AND `code`='{$yqm}' AND mid=0";
        }
        $result = $Model->execute($sql);
        if($result < 0){
            $this->success('邀请码无效');
        }
        $Model->execute("UPDATE member_invitation_code SET used=used+1 WHERE id={$invitation['id']}");

        $Model->execute("UPDATE project_member SET card_id={$card['id']}, card_expire={$expireDay} WHERE project_id={$project['id']} AND mid={$user['id']}");
        $Model->execute("INERT INTO member_change SET
                           mid={$user['id']},
                           old_level={$user['card_id']},
                           old_title='".addslashes($cards[$user['card_id']]['title'])."',
                           new_level={$card['id']},
                           new_title='".addslashes($card['title'])."',
                           created='".date('y-m-d H:i:s')."',
                           reason='邀请码{$yqm}'升级");
        $this->success('', __MODULE__.'/personal?modify='.NOW_TIME);
    }

    /**
     * @param 订单数
     * @return mixed
     */
    private function getOrderCountNew($buyerId){
        $topay = OrderStatus::WAIT_PAY;
        $tosend = OrderStatus::WAIT_SEND_GOODS;
        $tosend_ali = OrderStatus::ALI_WAIT_PAY;
        $send = OrderStatus::WAIT_CONFIRM_GOODS;
        $torate = OrderStatus::SUCCESS;
        $where = " buyer_id={$buyerId} ";
        if (!empty($this->projectId)){
            $where .= " AND seller_id BETWEEN {$this->projectId}000 AND {$this->projectId}999";
        }
        $list = M('trade_buyer')
            ->field("count(case when status = '{$topay}' then 1 else null end) as `topay`,
                        count(case when (status = '{$tosend}' OR status = '{$tosend_ali}') then 1 else null end) as `tosend`,
                        count(case when status = '{$send}' then 1 else null end) as `send`,
                        count(case when status = '{$torate}' AND buyer_rate = 0 then 1 else null end) as `torate`,
                        count(case when refund_status > 0 then 1 else null end) as `torefund` ")
            ->where($where)
            ->find();
        return $list;
    }
}