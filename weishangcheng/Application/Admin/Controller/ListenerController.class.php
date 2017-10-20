<?php
namespace Admin\Controller;

use Think\Cache\Driver\Redis;
use Think\Controller;
use Think\Log;
use Org\Net\Http;

class ListenerController extends Controller{
    public static $channel;
    function __construct(){
        parent::__construct();

        // 本系统则忽略
        if(IS_CLI){
           return; 
        }

        $ip = get_client_ip();
        if($ip != '121.199.166.253'){
            //Http::sendHttpStatus(404);
           // die;
        }

        // 发送请求重新创建
        if($_GET['sign']){
            $url = C('PROTOCOL').$_SERVER['HTTP_HOST'].(__MODULE__ ? '/'.__MODULE__ : '').'/listener/subscribe';
            $sign = create_sign($_GET, $url);
            if($sign != $_GET['sign']){

                if(ACTION_NAME == 'subscribe'){
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/test.txt', '签名错误');die;
                }
                $this->error('签名错误');
            }
        }else{
            $username = session('user.username');
            if($username != 'admin' && $username != '18968001693'){
//                Http::sendHttpStatus(404); die;
            }
        }
    }
    
    private function getAllChannel($createfile = false){
        $Model = M('subscribe_channel');
        $sql = "SELECT t_channel.*, GROUP_CONCAT(t_thread.id) AS thread
                FROM subscribe_channel AS t_channel
                LEFT JOIN subscribe_thread AS t_thread ON t_thread.channel=t_channel.channel
                GROUP BY t_channel.id";
        $list = $Model->query($sql);
    
        $content = '';
        $result = array();
        foreach ($list as $item){
            $item['thread'] = $item['thread'] ? explode(',', $item['thread']) : array();
            $result[$item['channel']] = $item;
    
            if($createfile){
                $content .= "\t'".$item['channel']."' => array('module' => '".$item['module']."', 'action' => '".$item['action']."'),\r\n";
            }
        }
    
        if($createfile){
            $filename = realpath(APP_PATH.'Admin/conf/subscribe.php');
            file_put_contents($filename, "<?php\r\n// 监听订阅列表\r\nreturn array(\r\n{$content});\r\n?>");
        }
    
        return $result;
    }
    
    /**
     * 启动所有监听程序
     */
    public function index(){
        $list = $this->getAllChannel(true);
        $redis = new Redis();
        foreach ($list as $i=>$item){
             $item['size'] = $redis->lSize($item['channel']);
             $item['quantity'] = count($item['thread']);
             $list[$i] = $item;
        }
        
        $this->assign('list', $list);
        $this->display();
    }
    
    public function unsubscribe(){
        $list = $this->getAllChannel();
        
        $redis = new Redis();
        foreach ($list as $channel=>$item){
            $redis->publish($channel, 'unsubscribe');
        }
        $redis->close();
        
        M('subscribe_channel')->execute("TRUNCATE TABLE subscribe_thread");

        $this->redirect(__MODULE__.'/listener');
    }
    
    public function execute(){
        $channel = $_GET['channel'];
        if(!$channel){
            $this->error('channel 不能为空');
        }

        $redis = new Redis();
        $count = $redis->publish($channel);
        print_data($count);
    }
    
    /**
     * 增加监听
     */
    public function subscribe(){
        $channel = $_GET['channel'];
        $list = $this->getAllChannel();
        if(!isset($list[$channel])){
            $this->error('channel不存在');
        }
        $config = $list[$channel];
        
        $redis = new Redis();

        $today = date('Y-m-d H:i:s');
        $uniqid = uniqid();
        $quantity = count($config['thread']);
        $Model = M();
        if($_GET['type'] == 'plus'){
            $quantity++;
            $sql = "INSERT INTO subscribe_thread SET id='{$uniqid}', channel='{$channel}', created='{$today}'";
            $Model->execute($sql);
        }else{
            foreach ($config['thread'] as $uniqid){
                $result = $redis->publish($channel, $uniqid);
                $quantity--;
                $sql = "DELETE FROM subscribe_thread WHERE id='{$uniqid}'";
                $Model->execute($sql);
                if($result > 0){
                    break;
                }
            }
        }
        
        // 记录
        finish_request(array('status' => 1, 'info' => $quantity), $_GET['type'] != 'plus');
        
        // 通知执行
        $url = C('PROTOCOL').$_SERVER['HTTP_HOST'].(__MODULE__ ? '/'.__MODULE__ : '').'/listener/execute';
        $param = array(
            'timestamp' => time(),
            'noncestr'  => \Org\Util\String2::randString(16),
            'channel'   => $channel['name'],
        );
        $param['sign'] = create_sign($param, $url);
        sync_notify($url, $param);
        
        set_time_limit(0);

        //register_shutdown_function(array($this, 'fatalError'));
        set_error_handler(array($this, 'appError'), E_ERROR|E_WARNING);
        set_exception_handler(array($this, 'appException'));
        
        ListenerController::$channel = array('start_time' => time(), 'name' => $channel, 'data' => '启动程序', 'message' => get_client_ip(), 'uniqid' => $uniqid);
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        $redis->subscribe(array($channel), function($instance, $channel, $message){
            $uniqid = ListenerController::$channel['uniqid'];
            if($message == 'unsubscribe' || $message == $uniqid){
                ListenerController::log('unsubscribe');
            }
            
            // 全局错误标记
            ListenerController::$channel['data'] = '';
            ListenerController::$channel['message'] = $message;

            $redis = new Redis();
            $config = require realpath(APP_PATH.'Admin/conf/subscribe.php');
            $config = $config[$channel];
            $Model = null;
            $timeout = 600;

            // 10分钟自动结束任务
            if(time() - ListenerController::$channel['start_time'] > $timeout){
                ListenerController::log('超时结束', false);
            }
            
            try{
                while ($redis->lSize($channel)){
                    ListenerController::$channel['data'] = $postData = $redis->rPop($channel);
                
                    $param_arr = decode_json($postData);
                    if(is_null($param_arr)){
                        $param_arr = $postData;
                    }
                    $param_arr = is_array($param_arr) ? $param_arr : array($param_arr);
                
                    $Model = new $config['module']();
                    call_user_func_array(array($Model, $config['action']), $param_arr);
                    unset($Model);
                    
                    // 10分钟自动结束任务
                    if(time() - ListenerController::$channel['start_time'] > $timeout){
                        ListenerController::log('超时结束', false);
                    }
                }
            }catch (\Exception $e){
                $data = ListenerController::$channel;
                M('subscribe_log')->add(array(
                    'channel'    => $channel,
                    'start_time' => date('Y-m-d H:i:s', $data['start_time']),
                    'end_time'   => date('Y-m-d H:i:s'),
                    'data'       => $data['data'],
                    'message'    => $data['message'],
                    'error'      => 'Exception:'.$e->getMessage(),
                    'uniqid'     => $uniqid
                ));
                
                // 本次终止，重新通知计算
                $size = $redis->lSize($channel);
                if($size > 0){
                    $redis->publish($channel);
                }
            }finally {
                unset($Model);
            }
            $redis->close();
            unset($redis);
            
            // 10分钟自动结束任务
            if(time() - ListenerController::$channel['start_time'] > $timeout){
                ListenerController::log('超时结束', false);
            }
        });
    }
    
    public function appException($e){
        $errstr = $e->getMessage();
        $errfile = $e->getFile();
        $errline = $e->getLine();
        $errorStr = "$errstr ".$errfile." 第 $errline 行.";
        ListenerController::log($errorStr, true);
    }
    
   public static function log($error = '程序结束', $isError = false){
        $channel = ListenerController::$channel;
        $uniqid = $channel['uniqid'];
        
        // 删除线程
        $Model = M('subscribe_log');
        $Model->execute("DELETE FROM subscribe_thread WHERE id='{$uniqid}'");
        
        // 发送请求重新创建
        if($error != 'unsubscribe'){
            $url = C('PROTOCOL').$_SERVER['HTTP_HOST'].(__MODULE__ ? '/'.__MODULE__ : '').'/listener/subscribe';
            $param = array(
                'timestamp' => time(),
                'uniqid'    => $uniqid,
                'noncestr'  => \Org\Util\String2::randString(16),
                'channel'   => $channel['name'],
                'type'      => 'plus'
            );
            $param['sign'] = create_sign($param, $url);
            sync_notify($url, $param);
        }

        if($isError){
            $Model->add(array(
                'channel'    => $channel['name'],
                'start_time' => date('Y-m-d H:i:s', $channel['start_time']),
                'end_time'   => date('Y-m-d H:i:s'),
            	'data'       => encode_json($channel['data']),
                'message'    => '异常',
                'error'      => $error,
                'uniqid'     => $uniqid
            ));
        }

        exit('程序结束');
    }
    
    public function appError($errno, $errstr, $errfile, $errline) {
        switch ($errno) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $errorStr = "$errstr ".$errfile." 第 $errline 行.";
                break;
            default:
                $errorStr = "[$errno] $errstr ".$errfile." 第 $errline 行.";
                break;
        }
        
        ListenerController::log($errorStr, true);
    }
    
    public function fatalError() {
        ListenerController::log();
    }
    
    /**
     * 自动检测
     */
    public function autoCheck(){
    	set_time_limit(600);
    	$host  = C('ADMIN_URL');
        M('subscribe_channel')->execute("TRUNCATE TABLE subscribe_thread");
        
        // 先取消订阅
        $channels = $this->getAllChannel();
        $redis = new Redis();
        foreach ($channels as $item){
        	$redis->publish($item['channel'], 'unsubscribe');
        }
        
        // 重新创建订阅
        foreach ($channels as $item){
            $channel  = $item['channel'];
            $quantity = 0;
            $size     = $redis->lSize($channel);
            $needAdd  = 0;
            
            // 增加至最低线程数量
            if($quantity < $item['min']){
                $needAdd = $item['min'] - $quantity;
            }
            
            // 待执行任务过多，追加线程
            if($size > 50){
                $needAdd++;
            }else if($quantity > $item['min']){
                $needAdd = $item['min'] - $quantity;
            }
            
            if($needAdd == 0){
                continue;
            }if($needAdd > 0){
                for($i=0; $i<$needAdd; $i++){
                    $url = $host.'/listener/subscribe';
                    $param = array(
                        'timestamp' => time(),
                        'noncestr'  => \Org\Util\String2::randString(16),
                        'channel'   => $channel,
                        'type'      => 'plus'
                    );
                    $param['sign'] = create_sign($param, $url);
                    sync_notify($url, $param);
                }
            }else if($needAdd < 0){
                foreach($item['thread'] as $uniqid){
                    $result = $redis->publish($channel, $uniqid);
                    if($result > 0){
                        $needAdd++;
                    }
                    
                    if($needAdd > -1){
                        break;
                    }
                }
            }
        }
        
        // 停留3秒重后通知执行数据处理
        sleep(3);
        foreach ($channels as $item){
        	$redis->publish($item['channel']);
        }
        $redis->close();
    }
}
?>