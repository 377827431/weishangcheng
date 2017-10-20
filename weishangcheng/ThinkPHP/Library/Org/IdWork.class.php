<?php 
namespace Org;

class IdWork{
    /**
     * 订单id
     * @return string
     */
    public static function nextTId(){
        $key = 'tid_auto_increment';
        $redis = new \Think\Cache\Driver\Redis();
        $increment = $redis->incr($key);
        if($increment > 9990 || $increment < 1000){
            $increment = rand(1000, 1100);
            $redis->set($key, $increment);
        }
        
        $seconds = date('H', NOW_TIME) * 60 * 60 + date('i', NOW_TIME) * 60 + date('s', NOW_TIME);
        $seconds = sprintf('%05s', $seconds);
        $now = date('Ymd', NOW_TIME);
        $redis->close();
        return substr($now, 0, 1).substr($now, 2).$seconds.$increment;
    }
    
    public static function isSystemTid($tid){
        if(strlen($tid) != 16){
            return false;
        }
        
        $date = substr($tid, 0, 1).'0'.substr($tid, 1, 2).'-'.substr($tid, 3, 2).'-'.substr($tid, 5, 2);
        $second = intval(substr($tid, 7, 5));
        
        if($second >= 3600){
            $date .= ' '.floor($second/3600);
            $second = ($second%3600);
        }else{
            $date .= ' 00';
        }
        if($second >= 60){
            $date .= ':'.floor($second/60);
            $second = ($second%60);
        }else{
            $date .= ':00';
        }
        $date .= ':'.floor($second);
        
        $timestamp = strtotime($date);
        if(!$timestamp){
            return false;
        }
        
        // 重新组合校对
        $seconds = date('H', $timestamp) * 60 * 60 + date('i', $timestamp) * 60 + date('s', $timestamp);
        $seconds = sprintf('%05s', $seconds);
        $now = date('Ymd', $timestamp);
        $prefix = substr($now, 0, 1).substr($now, 2).$seconds;
        
        return $prefix == substr($tid, 0, 12);
    }
    
    /**
     * 获取tid范围
     * @param timespan $start
     * @param timespan $end
     */
    public static function getTidRange($start = null, $end = null){
        // 最小订单号
        if(is_null($start)){
            $start = strtotime(date('Y-m-d').'00:00:00');
        }else if(!is_numeric($start)){
            $start = strtotime($start);
            $start -= 60;  // 去掉1分钟时间差
        }
        
        $seconds = date('H', $start) * 60 * 60 + date('i', $start) * 60 + date('s', $start);
        $seconds = sprintf('%05s', $seconds);
        $now = date('Ymd', $start);
        $startTid = substr($now, 0, 1).substr($now, 2).$seconds.'0000';
        
        // 最大订单号
        if(is_null($end)){
            $end = NOW_TIME;
        }else if(!is_numeric($end)){
            $end = strtotime($end);
            $end += 60;  // 增加1分钟时间差
        }
        $seconds = date('H', $end) * 60 * 60 + date('i', $end) * 60 + date('s', $end);
        $seconds = sprintf('%05s', $seconds);
        $now = date('Ymd', $end);
        $endTid = substr($now, 0, 1).substr($now, 2).$seconds.'9999';
        
        return array($startTid, $endTid);
    }

    /**
     * 提现no
     * @return string
     */
    public function nextTNo(){
        $key = 'tno_auto_increment';
        $redis = new \Think\Cache\Driver\Redis();
        $increment = $redis->incr($key);
        if($increment > 9950 || $increment < 1000){
            $increment = rand(1000, 1111);
            $redis->set($key, $increment);
        }

        $seconds = date('H', NOW_TIME) * 60 * 60 + date('i', NOW_TIME) * 60 + date('s', NOW_TIME) + 1;
        $seconds = sprintf('%05s', $seconds);
        $now = date('Ymd', NOW_TIME);
        return '3'.substr($now, 2, 2).substr($now, 4).$seconds.$increment;
    }
    
    /**
     * trade_book 表自增id
     * @return string
     */
    public static function nextBookKey(){
        $seconds = date('H', NOW_TIME) * 60 * 60 + date('i', NOW_TIME) * 60 + date('s', NOW_TIME) + 1;
        $seconds = sprintf('%05s', $seconds);
        $now = date('Ymd', NOW_TIME);
        return substr($now, 0, 1).substr($now, 2).$seconds.rand(100, 999);
    }
    
    /**
     * 自增外部订单号(16位)
     */
    public static function nextOutTid(){
        $key = 'outtid_increment';
        $redis = new \Think\Cache\Driver\Redis();
        $increment = $redis->incr($key);
        if($increment > 999900 || $increment < 100000){
            $increment = rand(100000, 111111);
            $redis->set($key, $increment);
        }
        
        $now = date('Ymd', NOW_TIME);
        return substr($now, 0, 1).substr($now, 2).$increment.rand(111, 999);
    }
    
    /**
     * 根据日期获取最小外部订单号
     */
    public static function getMinOutTid($timetamp){
        if(!is_numeric($timetamp)){
            $timetamp = strtotime($timetamp);
        }
        // 向前推15分钟，防止延迟
        $timetamp -= 900;
        
        $now = date('Ymd', $timetamp);
        return substr($now, 0, 1).substr($now, 2).'000000'.'000';
    }
    
    /**
     * 根据卖家id获取项目id
     * @param int $shopId
     */
    public static function getProjectId($shopId){
        return substr($shopId, 0, -3);
    }
    
    /**
     * 猜你喜欢ID
     */
    public static function getLikeId($timestamp = null){
        if(is_null($timestamp)){
            $timestamp = time();
        }else if(is_string($timestamp)){
            $timestamp = strtotime($timestamp);
        }

        $now = date('Ymd', $timestamp);
        return substr($now, 0, 1).substr($now, 2, 2).sprintf('%03s', date('z', $timestamp));
    }
    
    /**
     * 获取活动的真实ID
     */
    public static function getActivityRealId($activityId){
        return substr($activityId, 3);
    }
    
    public static function getActivityType($activityId){
        return substr($activityId, 0, 3);
    }
    
    /**
     * 将SKU值排序后转成MD5,方便对比数据
     */
    public static function convertSKU($skuArray, &$specName = ''){
        if(!$skuArray){
            return '';
        }else if(!is_array($skuArray)){
            $skuArray = json_decode($skuArray, true);
        }
    
        $specName = get_spec_name($skuArray, true);
        $result = array();
        foreach ($skuArray as $item){
            $result[] = $item['kid'].$item['v'];
        }
        sort($result);
        $result = implode('', $result);
        return md5($result);
    }
    
    /**
     * 隐藏手机号中间几位
     * @param unknown $mobile
     * @return string
     */
    static public function hideMobile($mobile){
        return substr($mobile, 0, 3).'****'.substr($mobile, -3);
    }
    
    public static function anonymous($nick){
    	$len = mb_strlen($nick, 'UTF-8');
    	return mb_substr($nick, 0, 1, 'UTF-8').'***'.mb_substr($nick, $len-1, 1, 'UTF-8');
    }
    
    static public function createMemberCard($projectId){
        $key = 'mbr_count_'.$projectId;
        $redis = new \Think\Cache\Driver\Redis();
        $increment = $redis->incr($key);
        $date = date('Ymd');
        $card = substr($date, 0, 1).substr($date, 2, 6).sprintf('%08s', $increment).rand(100, 999);
        return array('index' => $increment, 'no' => $card);
    }
}
?>