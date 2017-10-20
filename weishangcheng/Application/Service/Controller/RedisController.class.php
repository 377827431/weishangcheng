<?php
namespace Service\Controller;

use Common\Common\CommonController;
use Think\Cache\Driver\Redis;

/**
 * 阿里接口
 */
class RedisController extends CommonController
{
    public function test(){
        $redis = new Redis();
        $redis->set('aaaa', 123);
        echo $redis->get('aaaa');
        die;
    }
}
?>