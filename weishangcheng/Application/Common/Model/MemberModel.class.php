<?php 
namespace Common\Model;

use Org\Wechat\WechatAuth;
use Org\IdWork;
class MemberModel extends BaseModel{
    protected $tableName = 'member';
    
    /**
     * 关注微信公众号
     */
    public function subscribe($config, $param){
        $appid     = $param['appid'];
        $openid    = $param['openid'];
        $projectId = $param['project_id'];
        $subTime   = $param['time'];
        $source    = $param['source'];
        $shareMid  = $param['share_mid'];
        
        // 读取微信用户信息
        $wechatAuth = new WechatAuth($config, $appid);
        $userInfo = $wechatAuth->userInfo($openid);
        if(!$userInfo || !$userInfo['openid']){
            E('获取微信用户信息失败');
        }
        
        $userInfo['appid']  = $appid;
        $userInfo['source'] = $source;
        $wxUser = $this->addWxUser($userInfo, true);
        
        $projectApp = $this->query("SELECT alias, mch_appid FROM project_appid WHERE id={$projectId} AND appid='{$appid}'");
        $projectApp = $projectApp[0];
        if(!$projectApp){
            E('数据丢失');
        }
        
        $isFirstBind = false;
        $appList = $this->bindInfo(array(
            'third_appid'    => $config['appid'],
            'default'        => array('appid' => $appid, 'openid' => $openid),
            'current'        => array('appid' => $appid, 'openid' => $openid),
            'mid'            => 0,
            'project_id'     => $projectId,
            'share_mid'      => is_numeric($shareMid) ? $shareMid: 0,
            'host'           => $projectApp['alias'],
            'source'         => $userInfo['source']
        ), $isFirstBind);
        
        return array(
            'mid'        => $appList[$appid]['mid'], // 会员id
            'first_sub'  => $wxUser['first_sub'],    // 首次关注
            'first_add'  => $wxUser['first_add'],    // 首次添加
            'first_bind' => $isFirstBind             // 首次与项目绑定关系
        );
    }
    
    /**
     * 添加微信用户信息
     */
    public function addWxUser($userInfo, $return = false){
        $appid    = $userInfo['appid'];
        $openid   = $userInfo['openid'];
        $firstSub = false;
        $firstAdd = false;

        $wxUser   = array(
            'openid'     => $openid,
            'appid'      => $appid,
            'nickname'   => $userInfo['nickname'],
            'sex'        => $userInfo['sex'],
            'last_login' => NOW_TIME,
            'headimgurl' => $userInfo['headimgurl'],
            'province'   => $userInfo['province'],
            'city'       => $userInfo['city'],
            'country'    => $userInfo['country'],
            'created'    => NOW_TIME,
            'source'     => $userInfo['source']
        );

        $other = array();
        if(isset($userInfo['subscribe'])){
            $other['subscribe']      = $userInfo['subscribe'];
            $other['subscribe_time'] = $userInfo['subscribe_time'];
            $other['groupid']        = $userInfo['groupid'];
            $other['remark']         = $userInfo['remark'];

            $exists = $this->query("SELECT subscribe_time FROM wx_user WHERE openid='{$openid}' AND appid='{$appid}'");
            $firstSub = count($exists) == 0 || $exists[0]['subscribe_time'] == 0 ? true : false;
        }if(isset($userInfo['unionid'])){
            $other['unionid']        = $userInfo['unionid'];
        }
        
        // 插入
        $sql = "INSERT INTO wx_user SET ";
        $wxUser = array_merge($wxUser, $other);
        foreach ($wxUser as $field=>$value){
            $sql .= "`{$field}`='".addslashes($value)."',";
        }
        $sql = rtrim($sql, ',');
        
        // 更新
        $sql .= " ON DUPLICATE KEY UPDATE 
                    nickname=VALUES(nickname),
                    sex=VALUES(sex),
                    last_login=VALUES(last_login),
                    headimgurl=VALUES(headimgurl),
                    province=VALUES(province),
                    city=VALUES(city),
                    country=VALUES(country)";
        foreach($other as $field=>$value){
            $sql .= ",`{$field}`=VALUES({$field})";
        }

        $result   = $this->execute($sql);
        $firstAdd = $result == 1;
        
        if($return){
            $wxUser = $this->query("SELECT * FROM wx_user WHERE openid='{$openid}' AND appid='{$appid}'");
            $wxUser = $wxUser[0];
            $wxUser['first_add'] = $firstAdd;
            $wxUser['first_sub'] = $firstSub;
            return $wxUser;
        }
        
        return array('first_add' => $firstAdd, 'first_sub' => $firstSub);
    }
    
    /**
     * 根据pId获取member表信息
     */
    public function edit($data){
        if(! preg_match('/^1[3|4|5|7|8]\d{9}$/', $data['mobile'])){
            $this->error = '手机号格式错误.';
            return -1;
        }
        $this->where("id = ".addslashes($data['id']))->save($data);
        return 1;
    }
    
    /**
     * 获取个人资料
     */
    public function getPersonalInfo($mid, $project){
        $sql = "SELECT member.id, member.`name`, member.mobile,
                    member.province_id, member.city_id, member.county_id, member.address,
                    project.card_id, project.card_expire, project.balance, project.wallet, project.score,
                    (SELECT wx_user.headimgurl FROM wx_user WHERE mid={$mid} AND appid='{$project['appid']}') AS headimgurl
                FROM project_member AS project
                INNER JOIN member ON member.id=project.mid
                WHERE project.project_id={$project['id']} AND project.mid={$mid}";
        $member = $this->query($sql);
        $member = $member[0];
        
        // 会员卡
        $cards = get_member_card($project['id']);
        $card = $cards[$member['card_id']];
        if($card['discount'] > 0 && $card['discount'] < 1){
            $member['card_discount'] = ($card['discount']*10).'折';
        }else if($card['id'] > 0){
            $member['card_discount'] = 1;
        }else{
            $member['card_discount'] = 0;
        }
        $member['agent_title'] = $card['title'];
        
        // 优惠券
        $coupon = $this->query("SELECT COUNT(*) AS coupon_num FROM member_coupon WHERE mid={$mid}");
        $member['coupon_num'] = $coupon ? $coupon[0]['coupon_num'] : 0;
        
        // 格式化金额
        $member['balance'] = floatval($member['balance']);
        $member['wallet'] = floatval($member['wallet']);
        return $member;
    }

    /**
    * 邦定公众号会员信息
    */
    public function bindInfo($param, &$isFirst = false){
        $defaultAppid = $param['default']['appid'];
        $currentAppid = $param['current']['appid'];
        $appList  = array(
            $defaultAppid => array('openid' => $param['default']['openid'], 'mid' => 0),
            $currentAppid => array('openid' => $param['current']['openid'], 'mid' => 0)
        );

        $sql      = "SELECT openid, appid, mid, created, headimgurl, province, city,nickname FROM wx_user WHERE ";
        $sql     .= $param['default']['openid'] == $param['current']['openid'] ?
                    "openid='{$param['current']['openid']}' AND appid='{$currentAppid}'" :
                    "openid IN ('{$param['default']['openid']}', '{$param['current']['openid']}') AND appid IN ('{$defaultAppid}', '{$currentAppid}')";
        $wxUsers  = $this->query($sql);

        foreach ($wxUsers as $i=>$item) {
            $appid = $item['appid'];
            if(isset($appList[$appid])){
                $appList[$appid]['mid'] = $item['mid'];
            }
        }

        // 计算应该用哪个mid
        $mid     = $param['mid'];
        if($appList[$currentAppid]['mid'] > 0){
            $mid = $appList[$currentAppid]['mid'];
        }else if($appList[$defaultAppid]['mid'] > 0){
            $mid = $appList[$defaultAppid]['mid'];
        }

        $timestamp = NOW_TIME;
        if(!$mid){
            $userInfo = $wxUsers[0];
            $member = array('name'=>$userInfo['nickname'],'sex' => empty($userInfo['sex']) ? 0:$userInfo['sex'] , 'head_img' => $userInfo['headimgurl'], 'created' => $timestamp, 'last_login' => $timestamp);
            if (!empty($userInfo['province'])) {
                $province = $this->query("SELECT id FROM city WHERE `short_name`='{$userInfo['province']}' AND `level`=1");
                if(!empty($province)){$member['province_id'] = $province[0]['id'];}

                $city = $this->query("SELECT id FROM city WHERE `short_name`='{$userInfo['city']}' AND `level`=2");
                if(!empty($city)){$member['city_id'] = $city[0]['id'];}
            }
            $mid = $this->add($member);
        }
        
        if($appList[$defaultAppid]['mid'] == 0){
            $this->execute("UPDATE wx_user SET mid='{$mid}' WHERE appid='{$defaultAppid}' AND openid='{$appList[$defaultAppid]['openid']}'");
            $appList[$defaultAppid]['mid'] = $mid;
        }if($appList[$currentAppid]['mid'] == 0){
            $this->execute("UPDATE wx_user SET mid='{$mid}' WHERE appid='{$currentAppid}' AND openid='{$appList[$currentAppid]['openid']}'");
            $appList[$currentAppid]['mid'] = $mid;
        }

        // 检测是否邦定项目
        $this->bindProject(array(
            'project_id' => $param['project_id'],
            'mid'        => $mid,
            'share_mid'  => $param['share_mid'],
            'source'     => $param['source'],
            'host'       => $param['host']
        ), $isFirst);
        return $appList;
    }

    
    /**
     * 绑定会员和店铺关系
     */
    public function bindProject($param, &$isFirst = false){
        $projectId = $param['project_id'];
        $shopId = $projectId.'001';
        $mid = $param['mid'];
        if($param['share_mid'] == $mid){
            $param['share_mid'] = 0;
        }
        
        $member = $this->getProjectMember($mid, $projectId);
        if($member['binded']){
            $where = " WHERE project_id='{$projectId}' AND mid='{$mid}'";
            $sql = "UPDATE project_member SET last_host='{$param['host']}'".$where;
            $this->execute($sql);
        }else{
            $isFirst = true;
            $host = get_host_project($param['host']);
            $card = IdWork::createMemberCard($projectId);
            $card['expire'] = $host['card_expire'] > 0 ? strtotime('+'.$host['card_expire'].' day') : 0;
            
            $timestamp = NOW_TIME;
            // 为店铺创建会员关系
            $sql = "INSERT INTO project_member SET
                    project_id={$host['id']},
                    mid={$mid},
                    card_id={$host['card_id']},
                    card_expire={$card['expire']},
                    card_no='{$card['no']}',
                    card_index={$card['index']},
                    wallet={$host['give_wallet']},
                    score={$host['give_score']},
                    created={$timestamp},
                    last_host='{$host['alias']}'";
            $Model = new \Common\Model\Agent();
            if ($Model->is_agent($param['share_mid'], $shopId)){
                $sql .= ", pid='{$param['share_mid']}'";
            }
            // 创建会员与店铺关系记录
            $this->execute($sql);
        
            // 绑定好友关系
            if($param['share_mid'] > 0){
                $this->bindFreight($mid, $param['share_mid'], $param['from_way'], true);
            }
        
            // 插入资金
            if($host['give_wallet'] > 0 || $host['give_score'] > 0){
                $Balance = new BalanceModel();
                $Balance->add(array(
                    'mid'        => $mid,
                    'project_id' => $host['project_id'],
                    'type'       => BalanceType::FIRST_REGISTER,
                    'reason'     => '首次注册',
                    'wallet'     => $host['give_wallet'],
                    'score'      => $host['give_score']
                ), true);
            }
        }
        
        return;
    }
    
    /**
     * 绑定好友关系
     */
    public function bindFreight($mid, $pid, $way, $sendMsg = false){
        if($mid == $pid || !$pid){
            return;
        }
        $sql = "INSERT INTO member_friend SET
                    mid='{$pid}',
                    friend_id='{$mid}',
                    bind_time='".NOW_TIME."',
                    from_type='{$way}',
                    two_awy='1'
                ON DUPLICATE KEY UPDATE two_awy=1, bind_time=VALUES(bind_time)";
        $result = $this->execute($sql);
        if(!$sendMsg){
            return;
        }
    }
}
?>