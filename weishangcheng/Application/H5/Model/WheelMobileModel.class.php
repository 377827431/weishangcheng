<?php
namespace H5\Model;

use Think\Model;
class WheelMobileModel extends Model
{
    protected $tableName = 'active_wheel';
    protected $maxTimes = 1;
    protected $stepTimes = 500000;
    
    protected $prizeList = array(
        '1'     => array('prize_id' => 1, 'price' => '1149.00', 'level' => 1, 'times' => 500000, 'title' => '平板电脑', 'type' => 3, 'num' => 999999999, 'image_url' => '/wheel_1/pad.jpg', 'index' => '1,7', 'stock' => 999999999),
        '2'     => array('prize_id' => 2, 'price' => '799.00', 'level' => 2, 'times' => 250000, 'title' => '智能手表', 'type' => 3, 'num' => 999999999, 'image_url' => '/wheel_1/shoubiao.jpg', 'index' => '2,5', 'stock' => 999999999),
        '6'     => array('prize_id' => 6, 'price' => '698.00', 'level' => 3, 'times' => 100000, 'title' => '吊篮', 'type' => 3, 'num' => 999999999, 'image_url' => '/wheel_1/diaochuang.jpg', 'index' => '8,11', 'stock' => 999999999),
        '3'     => array('prize_id' => 3, 'price' => '588.00', 'level' => 4, 'times' => 10000, 'title' => '新款运动鞋', 'type' => 3, 'num' => 999999999, 'image_url' => '/wheel_1/xie.jpg', 'index' => '4,10', 'stock' => 999999999),
        '4'     => array('prize_id' => 4, 'price' => '238.00', 'level' => 5, 'times' => 0, 'title' => '招财貔貅', 'type' => 3, 'num' => 999999999, 'image_url' => '/wheel_1/shouchuan_1.jpg', 'index' => '0,6', 'stock' => 999999999),
        '5'     => array('prize_id' => 5, 'price' => '148.00', 'level' => 6, 'times' => 0, 'title' => '文玩饰品', 'type' => 3, 'num' => 999999999, 'image_url' => '/wheel_1/shouchuan_2.jpg', 'index' => '3,9', 'stock' => 999999999),
    );
    
    public function getPrizeList(){
        return $this->prizeList;
    }
    
    public function getById($id, $mobile){
        if(!is_numeric($id)){
            $this->error = 'ID不存在';
            return;
        }
        
        $data = $this->find($id);
        if(empty($data)){
            $this->error = '活动不存在';
            return; 
        }
        
        // 保存点击率
        $this->execute("UPDATE active_wheel SET pv=pv+1 WHERE id=".$id);
        $data['prize'] = $this->prizeList;
        
        if(strtotime($data['start_time']) > NOW_TIME){
            $data['error_code'] = 10000;
            $data['error_msg'] = '活动于'.$data['start_time'].'开始';
        }else if(strtotime($data['end_time']) < NOW_TIME){
            $data['error_code'] = 10000;
            $data['error_msg'] = '活动于已圆满结束开始';
        }
        
        if(empty($mobile)){
            $data['error_code'] = 10998;
            $data['error_msg'] = '请输入手机号';
        }else{
            $today = strtotime(date('Y-m-d').' 00:00:00');
            $playTimes = $this->query("SELECT COUNT(*) FROM active_wheel_1_record WHERE mobile='%s' AND created BETWEEN {$today} AND ".NOW_TIME, $mobile);
            if(!empty($playTimes) && current($playTimes[0]) >= $this->maxTimes){
                $data['error_code'] = 10001;
                $data['error_msg'] = '每个手机号每天只能参与'.$this->maxTimes.'次';
            }
        }
        
        $data['mobile'] = $mobile;
        $data['has_point'] = 0;
        if(!isset($data['error_code'])){
            $data['error_code'] = 0;
        }
        
        return $data;
    }
    
    /**
     * 计算并保存中奖信息
     * @param unknown $id
     * @param unknown $buyer
     * @param unknown $shop
     * @return multitype:number string multitype:number string  |unknown
     */
    public function getLucky($id, $mobile){
        $result = array('code' =>10010, 'msg' => '', 'data' => array('type' => 0, 'give_point' => 0, 'detail_url' => '', 'index' => 0, ));

        $wheel = $this->find($id);
        if(empty($wheel)){
            $result['msg'] = '活动不存在';
            return $result;
        }
        
        // 计算不中奖的区域
        $noPrize = rand(0, 1) == 1 ? $this->prizeList['4'] : $this->prizeList['5'];
        $tempIndex = explode(',', $noPrize['index']);
        $rand = rand(0, count($tempIndex) - 1);
        $noPrize['index'] = $tempIndex[$rand];
        $result['data'] = $noPrize;
        
        if(strtotime($wheel['start_time']) > NOW_TIME){
            $result['msg'] = '活动于'.$wheel['start_time'].'开始';
            return $result;
        }else if(strtotime($wheel['end_time']) < NOW_TIME){
            $result['msg'] = '活动于已圆满结束开始';
            return $result;
        }
        
        if(empty($mobile)){
            $data['msg'] = '请输入手机号';
            return $result;
        }else{
            $today = strtotime(date('Y-m-d').' 00:00:00');
            $playTimes = $this->query("SELECT COUNT(*) FROM active_wheel_1_record WHERE mobile='%s' AND created BETWEEN {$today} AND ".NOW_TIME, $mobile);
            if(!empty($playTimes) && current($playTimes[0]) >= $this->maxTimes){
                $data['msg'] = '每个手机号每天只能参与'.$this->maxTimes.'次';
                return $result;
            }
        }
        
        // 保存人次
        $recordTable = 'active_wheel_'.$wheel['id'].'_record';
        $myPlayTimes = $this->query("SELECT COUNT(*) FROM {$recordTable} WHERE mobile='%s'", $mobile);
        $uv = current($myPlayTimes[0]) == 0 ? 1 : 0;
        $this->execute("UPDATE active_wheel SET times=times+1, uv=uv+{$uv} WHERE id=".$id);

        $times = $wheel['times'] + 1;
        if($times > $this->stepTimes){
            $times -= (ceil($times / $this->stepTimes) - 1) * $this->stepTimes;
        }
        
        // 中奖奖品
        $prize = $noPrize;
        foreach($this->prizeList as $item){
            if($item['times'] > 0 && $times == $item['times']){
                $tempIndex = explode(',', $item['index']);
                $rand = rand(0, count($tempIndex) - 1);
                $item['index'] = $tempIndex[$rand];
                $prize = $item;
                break;
            }
        }
        
        // 插入抽奖记录
        M($recordTable)->add(array(
            'mobile'   => $mobile,
            'created' => NOW_TIME,
            'level'   => $prize['level'],
            'prize_id'   => $prize['prize_id']
        ));
        
        // 中奖奖品
        $result['data']['title'] = $prize['title'];
        $result['data']['index'] = $prize['index'];
        $result['data']['type'] = $prize['type'];
        $result['data']['prize_id'] = $prize['prize_id'];
        $result['data']['price'] = $prize['price'];
        $result['data']['image_url'] = $prize['image_url'];
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
        $data['created'] = date('Y-m-d H:i:s');
        $data['status'] = 'tosend';
        M('active_wheel_1_lucky')->add($data);
    }
    
    public function myPrizeList($mobile){
        if(empty($mobile)){
            return array();
        }
        
        $list = $this->query("SELECT * FROM active_wheel_1_lucky WHERE mobile='%s' ORDER BY id DESC", array($mobile));
        foreach($list as $i=>$item){
            $list[$i]['title'] = $this->prizeList[$item['prize_id']]['title'];
            $list[$i]['image_url'] = $this->prizeList[$item['prize_id']]['image_url'];
        }
        return $list;
    }
    
    public function cancel($id, $mobile){
        $now = date('Y-m-d H:i:s');
        $this->query("UPDATE active_wheel_1_lucky SET `status`='cancel',end_time='{$now}' WHERE id='%d' AND mobile='%s'", array($id, $mobile));
    }
}
?>