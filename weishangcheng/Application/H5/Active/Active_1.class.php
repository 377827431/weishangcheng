<?php
namespace H5\Active;

use Common\Model\BaseModel;
use Org\Wechat\WechatAuth;
class Active_1 extends Active
{
    private $Model;
    function __construct($active){
        parent::__construct($active);
        
        $this->Model = new WheelModel($active);
    }
    
    public function index(){
        $uid = $this->user('id');
        $mobile = $_POST['mobile'];
        $errorCode = 0;
        $errorMsg = '';
        if(!is_numeric($mobile) || strlen($mobile) != 11){
            $mobile = '';
        }else{ // 判断手机号是否有效
            $connection = 'mysql://other:seable123@123.56.134.59:30000/weitonglianmen#utf8';
            $MOrder = M('wtlm_order', '', $connection);
            $order = $MOrder->field("id, lc_name")->where("lc_phone='{$mobile}'")->find();
            if(empty($order)){
                $errorCode = 10998;
                $errorMsg = '手机号不存在';
            }
        }
        
        $wheel = $this->Model->getData($mobile);
        $wheel['post_url'] = '/h5/active';
        $myPrizeList = $this->Model->myPrizeList($mobile);
        
        if($errorCode > 0){
            $wheel['error_code'] = $errorCode;
            $wheel['error_msg'] = $errorMsg;
        }
        
        $this->assign(array(
            'wheel' => $wheel,
            'mobile' => $mobile,
            'myPrizeList' => $myPrizeList
        ));
        $this->display();
    }
    
    /**
     * 计算中奖
     */
    public function getLucky(){
        $user = $this->user('id, mobile, agent_level, openid');
        $mobile = $_POST['mobile'];
        $result = $this->Model->getLucky($mobile, $user);
        $this->ajaxReturn($result);
    }
    
    /**
     * 保存收货地址
     */
    public function addOrder(){
        $this->Model->addOrder($_POST);
        $this->success();
    }
    
    public function cancel(){
        $result = $this->Model->cancel($_POST['id'], $_POST['mobile']);
        if($result > 0){
            $this->success();
        }
        $this->error('已不可取消');
    }
}

class WheelModel extends BaseModel
{
    protected $tableName   = 'active_record';
    protected $active;
    protected $maxTimes = 100;
    protected $stepTimes = 10;
    protected $prizeList = array();
    
    function __construct($active){
        parent::__construct();
        $this->active = $active;
        $imgUrl = C('CDN');
        
        // key=奖品id
        $this->prizeList = array(
            '0'     => array('id' => 0, 'price' => '0', 'level' => 0, 'times' => 0, 'title' => '谢谢参与', 'type' => 0, 'num' => 1, 'image_url' => '', 'index' => '0,2,4,6,8,10', 'stock' => 1),
            '1'     => array('id' => 1, 'price' => '3000.00', 'level' => 1, 'times' => 0, 'title' => '哈雷电动车', 'type' => 3, 'num' => 0, 'image_url' => $imgUrl.'/static/active_1/haleidiandongche.gif', 'index' => '1', 'stock' => 0),
            '2'     => array('id' => 2, 'price' => '2499.00', 'level' => 2, 'times' => 0, 'title' => 'OPPO R9 玫瑰金 64GB', 'type' => 3, 'num' => 0, 'image_url' => $imgUrl.'/static/active_1/oppor9.jpg', 'index' => '3', 'stock' => 0),
            '3'     => array('id' => 3, 'price' => '798.00', 'level' => 3, 'times' => 0, 'title' => 'YM幽马山地自行车', 'type' => 3, 'num' => 0, 'image_url' => $imgUrl.'/static/active_1/shandizixingche.jpg', 'index' => '5', 'stock' => 0),
            '4'     => array('id' => 4, 'price' => '590.00', 'level' => 4, 'times' => 0, 'title' => '一路平衡车', 'type' => 3, 'num' => 0, 'image_url' => $imgUrl.'/static/active_1/pinghengche.jpg', 'index' => '7', 'stock' => 0),
            '5'     => array('id' => 5, 'price' => '83.00', 'level' => 5, 'times' => 0, 'title' => 'T20冷暖箱', 'type' => 3, 'num' => 0, 'image_url' => $imgUrl.'/static/active_1/chezaibingxiang.jpg', 'index' => '9', 'stock' => 0),
            '6'     => array('id' => 6, 'price' => '380.00', 'level' => 6, 'times' => 10, 'title' => '380元会员', 'type' => 1, 'num' => 1, 'image_url' => $imgUrl.'/static/active_1/380.jpg', 'index' => '11', 'stock' => 1, 'detail_url' => '/h5/pay/rule')
        );
    }
    
    public function getPrizeList(){
        return $this->prizeList;
    }
    
    public function getData($mobile = ''){
        $data = $this->active;
        $data['mobile'] = $mobile;
        $data['error_code'] = 0;
        
        // 保存点击率
        if(IS_GET && !IS_AJAX){
            $this->execute("UPDATE active SET pv=pv+1 WHERE id=".$this->active['id']);
        }
        
        $data['prize'] = $this->prizeList;
        
        $now = NOW_TIME;
        if(strtotime($this->active['start_time']) > $now){
            $data['error_code'] = 10000;
            $data['error_msg'] = '活动于'.$data['start_time'].'开始';
        }else if(strtotime($this->active['end_time']) < $now){
            $data['error_code'] = 10000;
            $data['error_msg'] = '活动于已圆满结束开始';
        }else if(!is_numeric($mobile) || strlen($mobile) != 11){
            $data['error_code'] = 10998;
            $data['error_msg'] = '请输入手包收货手机号';
        }else{
            $today = strtotime(date('Y-m-d').' 00:00:00');
            $playTimes = $this->query("SELECT COUNT(*) FROM {$this->tableName} WHERE active_id={$this->active['id']} AND uid='{$mobile}' AND created BETWEEN {$today} AND {$now}");
            if(!empty($playTimes) && current($playTimes[0]) >= $this->maxTimes){
                $data['error_code'] = 10001;
                $data['error_msg'] = '每个手机号每天只能参与'.$this->maxTimes.'次';
            }
        }
        
        $data['has_point'] = 0;
        return $data;
    }
    
    /**
     * 计算并保存中奖信息
     * @param unknown $id
     * @param unknown $buyer
     * @param unknown $shop
     * @return multitype:number string multitype:number string  |unknown
     */
    public function getLucky($mobile, $user){
        $result = array('code' =>10010, 'msg' => '', 'data' => array('type' => 0, 'give_point' => 0, 'detail_url' => '', 'index' => 0, ));

        $wheel = $this->getData($mobile);
        if($wheel['error_code'] != 0){
            $result['msg'] = $wheel['error_msg'];
            return $result;
        }
        
        // 计算不中奖的区域
        $noPrize = $this->prizeList['0'];
        $tempIndex = explode(',', $noPrize['index']);
        $rand = rand(0, count($tempIndex) - 1);
        $noPrize['index'] = $tempIndex[$rand];
        $result['data'] = $noPrize;
        
        // 保存人次
        $myPlayTimes = $this->query("SELECT COUNT(*) FROM {$this->tableName} WHERE active_id={$this->active['id']} AND uid='{$mobile}' LIMIT 1");
        $uv = current($myPlayTimes[0]) == 0 ? 1 : 0;
        $this->execute("UPDATE active SET pay_times=pay_times+1, uv=uv+{$uv} WHERE id=".$this->active['id']);

        $times = $wheel['pay_times'] + 1;
        if($times > $this->stepTimes){
            $times -= (ceil($times / $this->stepTimes) - 1) * $this->stepTimes;
        }
        
        // 中奖奖品
        $prize = $noPrize;
        foreach($this->prizeList as $item){
            if($item['stock'] > 0 && $times == $item['times']){
                if($item['id'] == 6 && $user['agent_level'] > 0){   // 已成为代理则不在中出380会员
                    break;
                }
                
                $tempIndex = explode(',', $item['index']);
                $rand = rand(0, count($tempIndex) - 1);
                $item['index'] = $tempIndex[$rand];
                $prize = $item;
                break;
            }
        }
        
        // 插入抽奖记录
        $this->execute("INSERT INTO {$this->tableName}
                        SET active_id={$this->active['id']},
                            uid='{$mobile}',
                            created=".NOW_TIME.",
                            prize_level='{$prize['level']}',
                            prize_id='{$prize['id']}',
                            status='0'");
        
        // 380会员
        if($prize['id'] == 6){
            $this->execute("UPDATE member SET agent_level=3".(empty($user['mobile']) ? ', mobile='.$mobile : '')." WHERE id=".$user['id']);
            if($user['subscribe'] == 1){
                $wechatAuth = new WechatAuth();
                $wechatAuth->sendTemplate($user['openid'], array(
                    'template_id' => 'TM00891',
                    'url'  => C('HOST').'/h5/mall',
                    'data' => array(
                        'first'   => array('value' => '恭喜您成为本平台会员！', 'color' => '#173177'),
                        'grade1'  => array('value' => '游客'),
                        'grade2'  => array('value' => '会员'),
                        'time'    => array('value' => date('Y年m月d日 H:i')),
                        'remark'  => array('value' => '赶紧去商城享受会员优惠价吧！')
                    )
                ));
            }
        }
        
        // 中奖奖品
        $result['data']['mobile'] = $mobile;
        $result['data']['active_id'] = $this->active['id'];
        $result['data']['record_id'] = $this->getLastInsID();
        $result['data']['title'] = $prize['title'];
        $result['data']['index'] = $prize['index'];
        $result['data']['type'] = $prize['type'];
        $result['data']['prize_id'] = $prize['id'];
        $result['data']['price'] = $prize['price'];
        $result['data']['image_url'] = $prize['image_url'];
        $result['data']['detail_url'] = $prize['detail_url'];
        $result['msg'] = '';
        $result['code'] = 0;
        return $result;
    }
    
    /**
     * 获取所有幸运大抽奖
     * @param string $shopId
     * @return multitype:number multitype:
     */
    public function getAll($where = array()){
        $rows = array();
        $limit = I('get.limit/d', 0);
        $offset = I('get.offset/d', 50);
        
        $total = $this->alias("wheel")
                      ->where($where)
                      ->count();
        
        if($total > 0){
            $rows = $this->alias("wheel")
                          ->field("wheel.id, wheel.title, wheel.subscribe,
                                   CONCAT(wheel.uv,'/',wheel.times) AS uv_times,
                                   CONCAT(wheel.start_time,'<br>',wheel.end_time) AS active_time")
                          ->where($where)
                          ->order("wheel.id DESC")
                          ->limit($limit, $offset)
                          ->select();
            
            foreach($rows as $i=>$item){
                $rows[$i]['url'] = C('PROTOCOL').$_SERVER['HTTP_HOST'].'/h5/wheel?id='.$item['id'];
            }
        }
        
        return $data = array('total' => $total, 'rows' => $rows);
    }
    
    /**
     * 下单
     */
    public function addOrder($data){
        $this
        ->where("active_id={$this->active['id']} AND id={$data['record_id']} AND uid={$data['mobile']}")
        ->save(array(
            'status' => 1,
            'confirm_time'      => NOW_TIME,
            'receiver_name'     => $data['receiver_name'],
            'receiver_mobile'   => $data['receiver_mobile'],
            'receiver_province' => $data['receiver_province'],
            'receiver_city'     => $data['receiver_city'],
            'receiver_county'   => $data['receiver_county'],
            'receiver_detail'   => $data['receiver_detail'],
            'receiver_zip'      => is_numeric($data['receiver_zip']) ? $data['receiver_zip'] : null
        ));
    }
    
    public function myPrizeList($mobile){
        if(empty($mobile)){
            return array();
        }
        
        $list = $this->query("SELECT * FROM {$this->tableName} WHERE active_id={$this->active['id']} AND uid='{$mobile}' AND prize_level>0 ORDER BY id DESC LIMIT 100");
        foreach($list as $i=>$item){
            $list[$i]['title'] = $this->prizeList[$item['prize_id']]['title'];
            $list[$i]['image_url'] = $this->prizeList[$item['prize_id']]['image_url'];
            $list[$i]['created'] = date('Y-m-d H:i', $item['created']);
        }
        return $list;
    }
    
    public function cancel($id, $mobile){
        if(is_numeric($id) && is_numeric($mobile)){
            return $this->execute("UPDATE {$this->tableName} SET `status`=0 WHERE active_id={$this->active['id']} AND id={$id} AND uid={$mobile} AND `status`=1");
        }
        return 0;
    }
}
?>