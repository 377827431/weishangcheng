<?php 
namespace Common\Model;

/**
 * 资金流水modal
 * @author lanxuebao
 *
 */
class BalanceModel extends BaseModel{
    protected $tableName = 'member_balance';
    
    /**
     * 记录个人资金流水
     * @see \Think\Model::add()
     */
    public function add($record = array(), $notify = false, $p3 = null){
        if(!is_numeric($record['mid']) || !is_numeric($record['project_id']) || empty($record['reason']) || empty($record['type'])){
            E('member_balance 参数错误');
        }

        $record['add_balance'] = is_numeric($record['balance']) ? floatval($record['balance']) : 0;
        $record['add_wallet'] = is_numeric($record['wallet']) ? floatval($record['wallet']) : 0;
        $record['add_score'] = is_numeric($record['score']) ? floatval($record['score']) : 0;
        if($record['add_balance'] == 0 && $record['add_wallet'] == 0 && $record['add_score'] == 0){
            return 1;
        }
        $record['reason'] = addslashes($record['reason']);
        
        $where = "WHERE project_id={$record['project_id']} AND mid={$record['mid']}";
        $sumScore = $record['add_score'] > 0 && $record['type'] != 'trade_refund' ? ", sum_score=sum_score+{$record['add_score']}" : "";
        $this->execute("UPDATE project_member SET balance=balance+{$record['add_balance']}, wallet=wallet+{$record['add_wallet']}, score=score+{$record['add_score']} {$sumScore} {$where}");
        $member = $this->getProjectMember(array('id' => $record['mid']), $record['project_id']);
        $result = $this->execute("INSERT INTO member_balance SET
            add_balance='{$record['add_balance']}',
            add_wallet='{$record['add_wallet']}',
            add_score='{$record['add_score']}',
            project_id='{$record['project_id']}',
            mid='{$record['mid']}',
            balance='{$member['balance']}',
            wallet='{$member['wallet']}',
            score='{$member['score']}',
            created=".time().",
            `type`='{$record['type']}',
            reason='{$record['reason']}'");
        // // 判断是否佣金类型数据流入
        // $transfers = new \Common\Model\AuthTransfersModel();
        // $transfersResult = $transfers->memberTransfers($record);
        // 消息通知
        $keyword3 = array();
        if($record['add_balance'] != 0){
            $keyword3[] = '可提现'.($record['add_balance'] > 0 ? '+'.$record['add_balance'] : $record['add_balance']).'元';
        }
        if($record['add_wallet'] != 0){
            $keyword3[] = '不可提现'.($record['add_wallet'] > 0 ? '+'.$record['add_wallet'] : $record['add_wallet']).'元';
        }
        if($record['add_score'] != 0){
            $keyword3[] = ($record['add_score'] > 0 ? '+'.$record['add_score'] : $record['add_score']).'积分';
        }
        
        // 放入消息队列
        $this->lPublish('MessageNotify', array(
            'type'         => MessageType::BALANCE_CHANGE,
            'openid'       => $member['openid'],
            'appid'        => $member['appid'],
            'data'         => array(
                'url'      => $member['url'].'/balance#record',
                'title'    => '您有一条新的资金流水，详情如下',
                'username' => $member['name'], // 用户名
                'time'     => date('Y年m月d日 H:i'), // 变动时间
                'value'    => implode(';', $keyword3), // 金额变动
                'balance'  => '可提现'.$member['balance'].'元;不可提现'.$member['wallet'].'元;'.$member['score'].'积分', // 可用余额
                'reason'   => $record['reason'], // 变动原因
                'remark'   => '感谢您的使用，祝您生活愉快！'
            )
        ));
        return 1;
    }

    /**
     * 记录卖家资金流水
     * @see \Think\Model::add()
     */
    public function add_seller($record){
        if(!is_numeric($record['shop_id']) || empty($record['reason']) || empty($record['type'])){
            E('member_balance 参数错误');
        }
        $record['reason'] = addslashes($record['reason']);
        $record['add_balance'] = is_numeric($record['balance']) ? $record['balance'] : 0;
        $record['add_frozen'] = is_numeric($record['frozen']) ? $record['frozen'] : 0;
        $record['project_id'] = substr($record['shop_id'], 0, -3);
        $project_info = M('shop')->field('balance, frozen_balance')->where("id = {$record['shop_id']}")->find();
        $add = array(
            "add_balance" => $record['add_balance'],
            "shop_id" => $record['shop_id'],
            "balance" => $project_info['balance'],
            "created" => time(),
            "type" => $record['type'],
            "frozen" => $project_info['frozen_balance'],
//            "add_frozen" => $record['add_frozen'],
            "reason" => $record['reason'],
//            "rid" => $record['rid'],
        );
        return M('shop_balance')->add($add);
    }
    
    public function getAll($mid,$shopId = null){
        $where = array('mid' => $mid);
        
        $data = array('total' => 0, 'rows' => array());
        $data['total'] = $this->where($where)->count();
        if($data['total'] == 0){
            return $data;
        }
        
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $data['rows'] = $this->where($where)->order('id DESC')->limit($offset, $limit)->select();
        foreach($data['rows'] as $k=>$v){
           $data['rows'][$k]['money'] = ($v['money']>0)?'+'.$v['money']:$v['money'];
        }
        return $data;
    }
    
    /**
     * 获取会员金子流水数据
     */
    public function getMyRecord($where, $offset = 50, $limit = 0, $type = 0){
        $list = $this->field("id, add_balance, add_wallet, add_score, balance, wallet, score, reason, type, created")->where($where)->limit($offset, $limit)->order("id desc")->select();
        $types = BalanceType::getAll();
        
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $theDayBeforeYesterday = date('Y-m-d', strtotime('-2 day'));
        $weekarray = array('周日', '周一', '周二', '周三', '周四', '周五', '周六');
        foreach ($list as &$record){
            $timespan = $record['created'];
            $type     = $types[$record['type']];
            $record['short'] = $type['short'];
            $record['color'] = $type['color'];
            $ymd = date('Y-m-d', $timespan);
            
            if($today == $ymd){
                $record['date'] = '今天';
                $record['time'] = date('H:i', $timespan);
            }else if($yesterday == $ymd){
                $record['date'] = '昨天';
                $record['time'] = date('H:i', $timespan);
            }else if($theDayBeforeYesterday == $ymd){
                $record['date'] = '前天';
                $record['time'] = date('H:i', $timespan);
            }else{
                $week = date("w", $timespan);
                $record['date'] = $weekarray[$week];
                $record['time'] = date('m-d', $timespan);
            }
        }
        return $list;
    }
    
    /**
     * 获取所有人员的资金流水
     */
    public function getAllBalance($where = array()){
        $balacne_type = $this->balacne_type();
        $data = array('total' => 0, 'rows' => array());
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        
        $data['total'] = $this->alias('b')
                              ->field('b.*')
                              ->join('member AS m ON b.mid=m.id')
                              ->where($where)
                              ->order('b.id DESC')
                              ->limit($offset, $limit)
                              ->count();
        
        if($data['total'] == 0){
            return $data;
        }
        
        $data['rows'] = $this->alias('b')
                              ->field('b.*,m.nickname')
                              ->join('member AS m ON b.mid=m.id')
                              ->where($where)
                              ->order('b.id DESC')
                              ->limit($offset, $limit)
                              ->select();
        foreach($data['rows'] as $k=>$v){
            $data['rows'][$k]['type'] = $balacne_type[$v['type']];
        }
        
        return $data;
    }
    
    /**
     * 资金流水类型
     */
    function balacne_type($key = null){
        $list = array(
            'agent_up' => '代理升级',
            'agent_yjtj' => '推荐代理',
            'agent_ejtj' => '二级推荐代理',
            'agent_sjtj' => '三级推荐代理',
            'agent_tj' => '推荐代理(历史)',   // 已废弃
            'agent_jjtj' => '间接推荐代理(历史)',   // 已废弃
            'order' => '订单',
            'transfers' => '提现',
            'order_balance' => '订单结算收益',
            'order_refunded' => '订单退款',
            'lower_cancel'   => '下级代理取消订单',
            'tjhy'      => '推荐赠送',
            'gszs'      => '系统赠送',
            'sign'      => '每日签到',
            'handbag_express' => '手包派件签收'
        );
        
        if($key){
            return $list[$key];
        }
        
        return $list;
    }
}
?>