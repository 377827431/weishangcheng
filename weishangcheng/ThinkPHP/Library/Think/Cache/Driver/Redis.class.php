<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Cache\Driver;
use Think\Cache;
defined('THINK_PATH') or exit();

/**
 * Redis缓存驱动 
 * 要求安装phpredis扩展：https://github.com/nicolasff/phpredis
 */
class Redis extends Cache  {
     /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options=array()) {
        if ( !extension_loaded('redis') ) {
            E(L('_NOT_SUPPORT_').':redis');
        }
        $options = array_merge(array (
            'host'          => C('REDIS_HOST') ? : '127.0.0.1',
            'port'          => C('REDIS_PORT') ? : 6379,
            'timeout'       => C('DATA_CACHE_TIMEOUT') ? : false,
            'persistent'    => C('REDIS_PERSISTENT') ? : false,
            'auth'          => C('REDIS_AUTH') ? : false,
        ),$options);

        $this->options =  $options;
        $this->options['expire'] =  isset($options['expire'])?  $options['expire']  :   C('DATA_CACHE_TIME');
        $this->options['prefix'] =  isset($options['prefix'])?  $options['prefix']  :   C('DATA_CACHE_PREFIX');        
        $this->options['length'] =  isset($options['length'])?  $options['length']  :   0;
        $func = $options['persistent'] ? : 'connect';
        
        $this->handler  = new \Redis;
        $options['timeout'] === false ?
            $this->handler->$func($options['host'], $options['port']) :
            $this->handler->$func($options['host'], $options['port'], $options['timeout']);
        
        if($options['auth']){
            $this->handler->auth($options['auth']);
        }
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name) {
        $value = $this->handler->get($this->options['prefix'].$name);
        $jsonData  = json_decode($value, true, 512, JSON_BIGINT_AS_STRING);
        return ($jsonData === NULL) ? $value : $jsonData;   //检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null) {
        if(is_null($expire)) {
            $expire  =  $this->options['expire'];
        }
        
        $name   =   $this->options['prefix'].$name;
        //对数组/对象数据进行缓存处理，保证数据完整性
        if(is_object($value) || is_array($value)){
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        if(is_int($expire) && $expire) {
            $result = $this->handler->setex($name, $expire, $value);
        }else{
            $result = $this->handler->set($name, $value);
        }
        if($result && $this->options['length']>0) {
            // 记录缓存队列
            $this->queue($name);
        }
        return $result;
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name) {
        return $this->handler->delete($this->options['prefix'].$name);
    }

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clear() {
        return $this->handler->flushDB();
    }
    
    public function lPush(){
        $args = func_get_args();
        $key = $args[0];
        
        array_splice($args, 0, 1);
        if(count($args) > 1 || is_array($args[0])){
            $value = encode_json($args);
        }else{
            $value = $args[0];
        }
    
        return $this->handler->lPush($key, $value);
    }
    
    public function publish($channel, $data = ''){
        if(!$data){
            $data = get_client_ip();
        }else{
            $data = encode_json($data);
        }

        return $this->handler->publish($channel, $data);
    }
    
    public function lPublish(){
        $args = func_get_args();
        $channel = $args[0];
        call_user_func_array(array($this, 'lPush'), $args);
    
        return $this->publish($channel);
    }
    
    public function attribute($key, $value = null){
        if(!$value){
            return $this->handler->$key;
        }
        $this->handler->$key = $value;
    }
}
