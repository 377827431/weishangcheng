<?php 
namespace Common\Model;
use Org\IdWork;

/**
 * 店铺资金流水
 * @author lanxuebao
 *
 */
class ShopBalanceModel extends BaseModel{
    protected $tableName = 'shop_balance';
    
    /**
     * 记录个人资金流水
     * @see \Think\Model::add()
     */
    public function add($record = array(), $notify = false, $p3 = null){
        if(!is_numeric($record['shop_id']) || empty($record['reason']) || !is_numeric($record['type'])){
            E('balance参数错误');
        }

        $record['balance'] = floatval($record['balance']);
        $record['reason'] = addslashes($record['reason']);
        $record['username'] = !$record['username'] ? 'system' : addslashes($record['username']);
        
        $this->execute("UPDATE shop SET balance=balance+{$record['balance']} WHERE id={$record['shop_id']}");
        $shop = $this->query("SELECT balance, mid, name FROM shop WHERE id={$record['shop_id']}");
        $shop = $shop[0];
        $this->execute("INSERT INTO shop_balance SET
            created=".time().",
            shop_id='{$record['shop_id']}',
            add_balance='{$record['balance']}',
            balance='{$shop['balance']}',
            type='{$record['type']}',
            reason='{$record['reason']}',
            username='{$record['username']}'");

        if(!$notify || !$shop['mid'] || $record['balance'] < 0){
        	return $shop;
        }
        // //判断是否佣金或订单类型数据流入
        // $transfers = new \Common\Model\AuthTransfersModel();
        // $transfersResult = $transfers->shopTransfers($record);
        // 消息通知
        static $notifyMember = array();
        
        $member = null;
        $key = $shop['id'].'_'.$shop['mid'];
        if(isset($notifyMember[$key])){
        	$member = $notifyMember[$key];
        }else{
        	$projectId = IdWork::getProjectId($record['shop_id']);
        	$member = $this->getProjectMember($shop['mid'], $projectId);
        	$notifyMember[$key] = $member;
        }
        
        if(!$member['subscribe']){
        	return $shop;
        }

        // 放入消息队列
        $project = $member['project'];
        $this->lPublish('MessageNotify', array(
            'type'         => MessageType::BALANCE_CHANGE,
            'openid'       => $member['openid'],
            'appid'        => $member['appid'],
            'data'         => array(
                'url'      => '',
                'title'    => '尊贵的'.$project['name'].'，您旗下的店铺收益已到账！',
                'username' => $shop['name'], // 用户名
                'time'     => date('Y年m月d日 H:i'), // 变动时间
                'value'    => $record['balance'], // 金额变动
                'balance'  => $shop['balance'], // 可用余额
                'reason'   => $record['reason'], // 变动原因
                'remark'   => '感谢您对使用，祝您生活愉快！'
            )
        ));
        
        return $shop;
    }

    /**
     * (插入)订单待结算
     * @param mixed $shopId 
     * @param mixed $balance 
     */
    public function addWaitSettlement($tid, $shopId, $balance){
    	$timestamp = time();
    	$sql = "INSERT INTO shop_settlement SET
				tid='{$tid}',
				shop_id='{$shopId}',
				created='{$timestamp}',
				wait_settlement='{$balance}',
				modified='{$timestamp}',
				`status`=0";
    	$this->execute($sql);
        $balance = floatval($balance);
        if($balance == 0){
            return;
        }
        $sql = "UPDATE shop SET wait_settlement=wait_settlement+{$balance} WHERE id=".$shopId;
        $this->execute($sql);
    }
}
?>