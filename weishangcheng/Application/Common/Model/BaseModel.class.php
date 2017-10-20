<?php 
namespace Common\Model;

use Think\Model;
use Think\Cache\Driver\Redis;

class BaseModel extends Model{
    protected $autoCheckFields = true;
    
    public function __construct($name='',$tablePrefix='',$connection=''){
        if(empty($name) || get_class($this) == 'Common\Model\BaseModel'){
            $this->autoCheckFields = false;
        }
        parent::__construct($name, $tablePrefix, $connection);
    }
    
    /**
     * 获取项目下的会员信息
     * 也可以放入redis缓存中增加性能
     */
    public function getProjectMember($login, $projectId){
        $onlyOne = false;
        if(is_numeric($projectId)){
            $onlyOne = true;
            $projectId = array($projectId);
        }else{
            $projectId = array_unique($projectId);
        }
        
        if(is_array($login)){
            $mid = $login['id'];
            $openid = $login['openid'];
        }else{
            $mid = $login;
            $openid = '';
        }
        
        $buyer = array();
        foreach ($projectId as $id){
            $project = get_project($id);
            $buyer[$id] = array(
                'id'         => $mid,
                'host'       => $project['host'],
                'url'        => $project['url'],
                'card_id'    => $project['card_id'],
                'card_expire'=> $project['card_expire'],
                'balance'    => 0,
                'wallet'     => 0,
                'score'      => 0,
                'pid'        => 0,
                'is_agent'   => 0,
                'discount'   => 1,
                'agent_title'=> '游客',
                'price_title'=> '',
                'min_score'  => $project['min_score'],
                'min_money'  => $project['min_money'],
                'openid'     => $openid,
                'subscribe'  => 0,
                'appid'      => '',
                'sum_paid'   => 0,
                'sum_trade'  => 0,
                'sum_score'  => 0,
                'agents'     => array(),  // 单品代理权
                'binded'     => 0,
                'project'    => $project
            );
        }
        $projectId = implode(',', $projectId);
        $sql = "SELECT project_member.project_id, project_member.card_id, project_member.card_expire, project_member.balance,
                    project_member.wallet, project_member.score, project_member.pid, project_appid.alias AS last_host, member.`name`,
                    project_member.agents, wx_user.openid, wx_user.appid, wx_user.subscribe,
                    project_member.sum_paid, project_member.sum_trade,project_member.sum_score
                FROM project_member
                INNER JOIN member ON member.id=project_member.mid
                LEFT JOIN project_appid ON project_appid.alias=project_member.last_host
                LEFT JOIN wx_user ON wx_user.mid=project_member.mid AND wx_user.appid=project_appid.appid
                WHERE project_member.project_id IN ({$projectId}) AND project_member.mid='{$mid}'";
        $list = $this->query($sql);
        foreach ($list as $item){
            $cards = get_member_card($item['project_id']);
            $card = $cards[$item['card_id']];
            $data = $buyer[$item['project_id']];
            $data['discount'] = $card['discount'];
            $data['is_agent'] = $card['is_agent'];
            $data['agent_title'] = $card['title'];
            $data['price_title'] = $card['price_title'];
            $data['openid'] = $item['openid'];
            $data['appid'] = $item['appid'];
            $data['subscribe'] = $item['subscribe'];
            $data['name'] = $item['name'];
            $data['card_id'] = $item['card_id'];
            $data['card_expire'] = $item['card_expire'];
            $data['balance'] = $item['balance'];
            $data['wallet'] = $item['wallet'];
            $data['score'] = $item['score'];
            $data['pid'] = $item['pid'];
            if($item['last_host']){
                $data['url'] = $data['host'].'/'.$item['last_host'];
            }
            $data['agents'] = explode_string(',', $item['agents']);
            $data['binded'] = 1;
            $buyer[$item['project_id']] = $data;
        }
        
        if($onlyOne){
            return current($buyer);
        }
        return $buyer;
    }
    
    /**
     * 批量获取会员信息
     * @param list $login
     * @param int $projectId
     */
    public function getProjectMembers($projectId, $idList){
        $idList = explode_string(',', $idList);
        $project = get_project($projectId);
    
        $sql = "SELECT project_id, card_id, card_expire, balance, wallet, score, pid, last_host, member.`name`,
                    project_member.agents, wx_user.openid, wx_user.appid, wx_user.subscribe, project_member.mid, wx_user.`last_login`
                FROM project_member
                INNER JOIN member ON member.id=project_member.mid
                INNER JOIN wx_user ON wx_user.mid=project_member.mid
                WHERE project_id={$projectId} AND project_member.mid IN (".implode(',', $idList).")";
        $list = $this->query($sql);
    
        $cards = get_member_card($projectId);
        $buyer = array();
        foreach ($list as $item){
            $card = $cards[$item['card_id']];
            $data = array();
            $data['discount'] = $card['discount'];
            $data['is_agent'] = $card['is_agent'];
            $data['agent_title'] = $card['title'];
            $data['price_title'] = $card['price_title'];
            $data['openid'] = $item['openid'];
            $data['appid'] = $item['appid'];
            $data['subscribe'] = $item['subscribe'];
            $data['name'] = $item['name'];
            $data['card_id'] = $item['card_id'];
            $data['card_expire'] = $item['card_expire'];
            $data['balance'] = $item['balance'];
            $data['wallet'] = $item['wallet'];
            $data['score'] = $item['score'];
            $data['pid'] = $item['pid'];
            $data['last_login'] = $item['last_login'];
            if($item['last_host']){
                $data['url'] = C('PROTOCOL').$data['host'].'/'.$item['last_host'];
            }
            $data['agents'] = explode_string(',', $item['agents']);
            $buyer[$item['mid']] = $data;
        }
    
        return $buyer;
    }
    
    public function Redis($temp = null){
        static $redis = null;
        if(is_null($redis)){
            $redis = is_null($temp) ? new Redis() : $temp;
        }
        return $redis;
    }
    
    /**
     * 放入消息队列，程序结束后自动推送
     */
    final public function lPush(){
        $args = func_get_args();
        call_user_func_array(array($this->Redis(), 'lPush'), $args);
    }
    
    final public function publish($channel){
        return $this->Redis()->publish($channel, time());
    }
    
    /**
     * 放入消息队列，并立即处理
     */
    final public function lPublish(){
        $args = func_get_args();
        call_user_func_array(array($this->Redis(), 'lPublish'), $args);
    }

    /**
     * 商品下架通知
     */
    public function goodsTakedownsMsg($id, $type = ''){
        $where = '';
        if($type == '1688'){
            $where = "mall_goods.tao_id='{$id}'";
        }else if(is_numeric($id)){
            $where = "mall_goods.id='{$id}'";
        }else{
            $where = "mall_goods.id IN('{$id}')";
        }
        
        $sql = "SELECT * FROM 
                (SELECT mall_goods.id, mall_goods.title, mall_goods.shop_id, shop.mid, wx_user.openid, wx_user.appid, mall_goods.tao_id
                    FROM mall_goods
                    INNER JOIN shop ON shop.id=mall_goods.shop_id
                    INNER JOIN wx_user ON wx_user.mid=shop.mid
                    WHERE {$where} AND mall_goods.is_del=0 AND wx_user.subscribe=1
                    ORDER BY wx_user.last_login DESC
                ) AS info
                GROUP BY id";
        $list = $this->query($sql);
        $time = date('Y年m月d日 H:i');
        foreach ($list as $data){
            $this->lPublish('MessageNotify', array(
                'type'         => MessageType::WAIT_DO_WORK,
                'openid'       => $data['openid'],
                'appid'        => $data['appid'],
                'data'         => array(
                    'url'      => $data['url'].'/goods',
                    'title'    => '下架待查看',
                    'name'     =>   '事项名称',
                    'time'     => date('Y年m月d日 H:i'), // 变动时间
                    'remark'   => $remark = '描述：'.(empty($data['tao_id']) ? '' : '1688').'商品【'.$data['title'].'】已下架，若是您本人操作请忽略此消息'
                )
            ));
        }
    }
}
?>