<?php

// +----------------------------------------------------------------------
// | 常用公共函数类库
// +----------------------------------------------------------------------
// | 请不要修改或删除原有内容.
// +----------------------------------------------------------------------
/**
 * 调试输出
 * @param unknown $data
 */
function print_data($data, $var_dump = false)
{
    ob_start();
    ob_clean();
    header("Content-type: text/html; charset=utf-8");
    echo "<pre>";
    if ($var_dump) {
        var_dump($data);
    } else {
        print_r($data);
    }
    exit();
}

/**
 * 将数组变成键值对数组
 *
 * @param array $data            
 * @param mixed $key            
 * @return array
 */
function array_kv(array $data, $key = 'id', $all = false, $all_field = 'id')
{
    if (! isset($data[0])) {
        return $data;
    }
    
    $array = array();
    if (count($data[0]) > 2) {
        foreach ($data as $_k => $_v) {
            if ($all) {
                if (count($_v) > 3) {
                    $new_value = $_v;
                    unset($new_value[$all_field]);
                    unset($new_value[$key]);
                    $array[''.$_v[$key]][''.$_v[$all_field]] = $new_value;
                } else {
                    $new_value = $_v;
                    unset($new_value[$all_field]);
                    unset($new_value[$key]);
                    $array[''.$_v[$key]][''.$_v[$all_field]] = end($new_value);
                }
            } else {
                $array[''.$_v[$key]] = $_v;
                unset($array[$_v[$key]][$key]);
            }
        }
    } else {
        foreach ($data as $_k => $_v) {
            $array[''.$_v[$key]] = end($_v);
        }
    }
    
    return $array;
}

/**
 * 将列表按照字段分组
 *
 * @param array $data            
 * @param unknown $key            
 * @return Ambigous <multitype:, unknown>
 */
function array_group(array $data, $key)
{
    $list = array();
    foreach ($data as $index => $item) {
        $_temp = $item;
        unset($_temp[$key]);
        $list[$item[$key]][] = $_temp;
    }
    
    return $list;
}

/**
 * CURL网络请求
 * 
 * @param unknown $url            
 * @param string $data            
 * @param string $contentType            
 * @return mixed
 */
function http_request($url, $data = null, $contentType = null)
{
    E('请将此方法移除');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL 报错时使用
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // SSL 报错时使用
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if (! empty($contentType)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type:' . $contentType
        ));
    }
    if (! empty($data)) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

/**
 * 数组排序
 */
function sort_list(&$list, $pid = 0, $index = 0){
    if (empty($list)) {
        return $list;
    }
    $data = array();
    
    $level = array('一', '二', '三', '四', '五', '六', '七', '八', '九', '十');
    foreach ($list as $key => $value) {
        if ($value['pid'] == $pid) {
            unset($list[$key]);
            if ($pid > 0) {
                $split_str = '├─';
                for ($i = $index - 1; $i > 0; $i --) {
                    $split_str .= '──';
                }
                $value['split'] = $split_str;
                $value['level'] = $level[$index];
            }else{
                $value['split'] = '';
                $value['level'] = $level[0];
            }
            $data[] = $value;
            $children = sort_list($list, $value['id'], $index + 1);
            if(!empty($children)){
                $data = array_merge($data , $children);
            }
        }
    }
    
    // 把没有父节点的数据追加到返回结果中，避免数据丢失
    if($pid == 0 ){
        if(count($list) > 0){
            $data = array_merge($data, $list);
        }
        
        $list = $data;
        return $list;
    }
    return $data;
}

function shorturl($input) {
    $base32 = array (
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
        'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p',
        'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
        'y', 'z', '0', '1', '2', '3', '4', '5'
    );
     
    $hex = md5($input);
    $hexLen = strlen($hex);
    $subHexLen = $hexLen / 8;
    $output = array();
     
    for ($i = 0; $i < $subHexLen; $i++) {
        $subHex = substr ($hex, $i * 8, 8);
        $int = 0x3FFFFFFF & (1 * ('0x'.$subHex));
        $out = '';
         
        for ($j = 0; $j < 6; $j++) {
            $val = 0x0000001F & $int;
            $out .= $base32[$val];
            $int = $int >> 5;
        }
         
        $output[] = $out;
    }
     
    return $output;
}

/**
 * 数据XML编码
 * @param  object $xml  XML对象
 * @param  mixed  $data 数据
 * @param  string $item 数字索引时的节点名称
 * @return string
 */
function data_xml($xml, $data, $item = 'item'){
    // E('请将此方法移除');
    foreach ($data as $key => $value) {
        //指定默认的数字key
        is_numeric($key) && $key = $item;
    
        //添加子元素
        if(is_array($value) || is_object($value)){
            $child = $xml->addChild($key);
        } else {
            if(is_numeric($value)){
                $child = $xml->addChild($key, $value);
            } else {
                $child = $xml->addChild($key);
                $node  = dom_import_simplexml($child);
                $cdata = $node->ownerDocument->createCDATASection($value);
                $node->appendChild($cdata);
            }
        }
    }
}

/**
 * 冒泡排序
 */
function maopao($array, $sortField = 'sort', $childField = ''){
    $count = count($array);
    if ($count <= 0) {
        return $array;
    }
    for ($i = 0; $i < $count; $i ++) {
        if ($childField != '' && !empty($array[$i][$childField])) {
            $array[$i][$childField] = maopao($array[$i][$childField]);
        }
    
        for ($k = $count - 1; $k > $i; $k --) {
            if ($array[$k][$sortField] > $array[$k - 1][$sortField]) {
                $tmp = $array[$k];
                $array[$k] = $array[$k - 1];
                $array[$k - 1] = $tmp;
            }
        }
    }
    return $array;
}

function get_child($all_list, $pid = 0){
    $list = array();
    foreach($all_list as $index=>$item){
        if($item['pid'] == $pid){
            unset($all_list[$index]);

            $children = get_child($all_list, $item['id']);
            $item['has_child'] = count($children) > 0 ? 1 : 0;
            $item['children'] = $children;
            $list[] = $item;
        }
    }

    return $list;
}

/**
 * 根据appid获取微信配置文件
 */
function get_wx_config($appid){
    $key = 'wx_'.$appid;
    $config = S($key);
    if(!$config){
        $config = M()->query("SELECT appid, `name`, qrcode, token, secret, encoding_aes_key,
                mch_id, sub_mch_id, mch_key, mch_name, template, third_appid, project_id
                FROM wx_appid
                WHERE appid='{$appid}'");
        $config = $config[0];
        $config['template'] = json_decode($config['template'], true);
        S($key, $config, 7200);
    }
    return $config;
}

/**
 * 根据别名获取项目信息(请直接使用get_project)
 */
function get_host_project($host){
    $key = 'host_'.$host;
    $config = S($key);
//    if(!$config){
        $sql = "SELECT id, alias, appid, mch_appid, card_id, card_expire, give_score, give_wallet, give_coupon
                FROM project_appid
                WHERE alias='{$host}'";
        $config = M()->query($sql);
        if(empty($config)){
            E('未知作用域名：'.$host);
        }
        $config = $config[0];
        // 保存12小时
        S($key, $config, 43200);
//    }
    return $config;
}

/**
 * 获取项目配置文件
 */
function get_project($key, $isShop = false){
    $project = $host = $projectId = null;
    if(is_numeric($key)){
        $projectId = $isShop ? \Org\IdWork::getProjectId($key) : $key;
    }else if(strpos($key, '.') !== false){
        $projectId = S('pro_www_'.preg_replace('/^(http|https):\/\//', '', $key));
    }else if(!is_numeric($key)){
        $host = get_host_project($key);
        $projectId = $host['id'];
    }else{
        E('未知作用域：'.$key);
    }

    if(is_numeric($projectId)){
        $project = S('pro_'.$projectId);
    }
    
    // 重新加载
//    if(!$project){
        $where = is_numeric($projectId) ? "id={$projectId}" : "host='{$key}'";
        $Model = M();
        $sql = "SELECT id, `name`, alias, host, `status`, service_hotline, balance_alias, wallet_alias, score_alias,
                    min_score, min_money, member_increment, logo, third_appid
                FROM project
                WHERE {$where}
                LIMIT 1";
        $project = $Model->query($sql);
        if(empty($project)){
            E('项目ID不存在：'.$projectId);
        }
        $project = $project[0];

        $countkey = 'mbr_count_'.$projectId;
        $increment = S($countkey);
        if($increment > $project['member_increment']){
            $Model->execute("UPDATE project SET member_increment='{$increment}' WHERE id='{$projectId}'");
        }else{
            S($countkey, $project['member_increment'], 7200*2);
        }
        unset($project['member_increment']);
        
        // 保存2小时
        S('pro_'.$project['id'], $project, 7200);
        
        $temp = preg_replace('/^(http|https):\/\//', '', $project['host']);
        S('pro_www_'.$temp, $project['id'], 7800);
//    }

    // 默认信息
    if(is_null($host)){
        if(defined('PROJECT') && PROJECT['id'] == $project['id']){
            $host = get_host_project(APP_NAME); 
        }else{
            $host = get_host_project($project['alias']);
        }
    }
    $project['host']        = is_ssl() ? str_replace('http://', 'https://', $project['host']) : str_replace('https://', 'http://', $project['host']);
    $project['url']         = $project['host'].'/'.$host['alias'];
    $project['alias']       = $host['alias'];
    $project['appid']       = $host['appid'];
    $project['card_id']     = $host['card_id'];
    $project['third_mpid']  = $host['mch_appid'];
    $project['card_expire'] = $host['card_expire'];
    $project['give_score']  = $host['give_score'];
    $project['give_wallet'] = $host['give_wallet'];
    $project['give_coupon'] = $host['give_coupon'];
    return $project;
}

function project_config($projectId, $key, $val = null){
    $cacheKey = 'pconf_'.$projectId.'_'.$key;
    
    // 保存
    if(!is_null($val)){
        $isJson = 0;
        if(is_array($val) || is_object($val)){
            $isJson = 1;
            $default = json_encode($default, JSON_UNESCAPED_UNICODE);
        }else{
            $val = addslashes($val);
        }
        M()->execute("REPLACE INTO project_config SET project_id={$projectId}, `key`='{$key}', `val`='{$val}', is_json={$isJson}");
        S($cacheKey, $val, 7200);
        return;
    }
    
    $val = S($cacheKey);
    if($val === false){
        $config = M()->query("SELECT val, is_json FROM project_config WHERE project_id='{$projectId}' AND `key`='{$key}'");
        $val = null;
        if($config){
            $config = $config[0];
            if($config['is_json']){
                $val = json_decode($config['val'], true);
            }else{
                $val = $config['val'];
            }
        }
        
        S($cacheKey, $val, 7200);
    }
    return $val;
}

/**
 * 获取会员卡
 */
function get_member_card($projectId, $isShopId = false){
    if($isShopId){
        $projectId = \Org\IdWork::getProjectId($projectId);
    }
    
    $key = 'member_card_'.$projectId;
    $cards = S($key);
    if(!$cards){
        // 店铺会员卡
        $sql = "SELECT id, title, price_title, discount, settlement_type, agent_rate, agent_same, agent_rate2,
                    icon, background, auto_trade, auto_payment, auto_score, expire_time,
                    give_wallet, give_score, give_coupon
                FROM project_card
                WHERE id BETWEEN '{$projectId}0' and '{$projectId}9'";
        $agentList = M()->query($sql);
        $cards = array();
        foreach ($agentList as $item){
            $cards[$item['id']] = $item;
        }
        
        // 保存2小时
        S($key, $cards, 7200);
    }
   
    $cards['0'] = array(
        'id' => 0, 'level' => 0, 'title' => '游客', 'price_title' => '',
        'discount' => 0, 'is_agent' => 0, 'agent_rate' => 0,  'agent_same' => 0, 'agent_rate2' => 0,
        'icon' => '', 'background' => '', 'settlement_type' => 0
    );
    
    ksort($cards);
    return $cards;
}

/**
 * 获取规格名称
 * @param unknown $skuJson
 */
function get_spec_name($skuJson, $fullname = false){
    if(empty($skuJson) || $skuJson == '[]'){
        return '';
    }else if(!is_array($skuJson)){
        $skuJson = json_decode($skuJson, true);
    }
    
    $sku_name = '';
    foreach($skuJson as $i=>$sku){
        if($fullname){
            $sku_name .= ($i == 0 ? '' : ';').$sku['k'].':'.$sku['v'];
        }else{
            $sku_name .= $sku['v'].' ';
        }
    }
    return rtrim($sku_name);
}

function finish_request($data = null, $finish = false){
    if(!is_null($data)){
        ob_start();
        ob_clean();
        if(is_array($data)){
            header('Content-Type:application/json; charset=utf-8');
            echo json_encode($data);
        }else{
            echo '<pre>'.$data;
        }
    }
    
    if($finish){
        exit();
    }
    
    ignore_user_abort(true);
    header("Content-Type: text/event-stream\n");
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    header('Content-Length: '. strlen(ob_get_contents()));
    header("Connection: close");
    header("HTTP/1.1 200 OK");
    ob_end_flush();
    flush();
}

/**
 * 强制浏览器缓存
 * @param number $expiresMinute
 * @param unknown $key
 */
function setBrowserCache($expiresMinute = 5, $key = ''){
    if(empty($key)){
        $key = $_SERVER['REQUEST_URI'];
    }
    $Etag = md5($key);
    header('Cache-Control: public');
    header('ETag: "'.$Etag.'"');
    header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
    header('Expires: '.gmdate('D, d M Y H:i:s', strtotime('+'.$expiresMinute.' minute')) . ' GMT');
    header('Pragma: public');
}

function scoreToMoney($score){
    return bcmul($score, 0.01, 2);
}

//求最大公约数
function max_divisor($a, $b){
    $n = min($a, $b);
    for($i=$n; $i>1; $i--){
        $_a = floatval(bcdiv($a, $i, 2));
        $_b = floatval(bcdiv($b, $i, 2));
        if (intval($_a) == $_a && intval($_b) == $_b){
            return $i;
        }
    }
    return 1;
}

// 1积分=0.1元
function money_to_score($maxMoney, $totalScore = 0, $min_score = 1, $min_yuan = 0.1){
    // 金额保留小数位数
    $min_yuan = floatval($min_yuan);
    $scale = 0;
    $temp = explode('.', $min_yuan);
    if (sizeof($temp) > 1) {
        $decimal = end($temp);
        $scale = strlen($decimal);
    }
    
    // 最大积分
    $maxScore = bcdiv($maxMoney, $min_yuan) * $min_score;
    if($totalScore > $maxScore){
        $totalScore = $maxScore;
    }
    
    $score = bcdiv($totalScore, $min_score);
    $score = bcmul($score, $min_score);
    $money = bcdiv($score, $min_score);
    $money = bcmul($money, $min_yuan, $scale);

    return array(
        'min_score' => $min_score,
        'min_money' => $min_yuan,
        'max_score' => $maxScore,
        'max_money' => $maxMoney,
        'score'     => $score,
        'money'     => floatval($money)
    );
}

function split_money($money){
    $money = sprintf('%.2f', $money);
    $result = explode('.', $money);
    return array($result[0], '.'.$result[1]);
}

/**
 * 每件产品减少多少钱
 */
function avg_dsicount($jian, $tradeGoods){
    asort($tradeGoods);
    $list = $result = array();
    $total = $totalDiscount = $discount = 0;
    
    $last = 0;
    foreach($tradeGoods as $i=>$payment){
        $last = $i;
        $total = bcadd($total, $payment, 2);
    }
    
    $prec = bcdiv($jian, $total, 6);
    
    foreach($tradeGoods as $i=>$payment){
        if($last == $i){
            $discount = bcsub($jian, $totalDiscount, 2);
        }else{
            $discount = bcmul($payment, $prec, 2);
        }
        $result[$i] = $discount;
        $totalDiscount = bcadd($totalDiscount, $discount, 2);
    }
    
    return $result;
}

/**
 * 发送短信
 */
function send_sms($data, $params = null){
    if(APP_DEBUG){
        return array('errcode' => 0, 'errmsg' => '', 'code' => rand(100000, 999999));;
    }

    $sendResult = array('errcode' => -1, 'errmsg' => '发送失败', 'code' => '');
    $Model = M('message_sms');
    $prev = $Model->field("created, errcode")->where("mobile='{$data['mobile']}'")->order("id DESC")->find();
    /*
    if(!empty($prev) && NOW_TIME - $prev['created'] < 3600){
        $sendResult['errmsg'] = '过于频繁，请1分钟后再试';
        return $sendResult;
    }
    */

    $data['code'] = rand(100000, 999999);
    $id = $Model->add(array(
        'project_id' => is_numeric($data['project_id']) ? $data['project_id'] : 0,
        'mobile'   => $data['mobile'],
        'sign'     => $data['sign'],
        'template' => 'SMS_9675002',
        'param'     => $data['code'],
        'created'  => NOW_TIME,
        'uid'     => $data['name'],
        'errcode'  => '-1',
        'errmsg'   => '',
    ));
    
    vendor('TopSDK.TopSdk');
    $c = new \TopClient();
    $c->appkey = '23370420';
    $c->secretKey = '055951e28f60452d6845b27115b28d51';
    $req = new \AlibabaAliqinFcSmsNumSendRequest();
    $req->setExtend($data['extend']);
    $req->setSmsType('normal');
    // $req->setSmsFreeSignName($data['sign']);
    $req->setSmsFreeSignName("手机验证");
    $req->setSmsParam('{"name":"开店","user":"小店用户","code":"'.$data['code'].'"}');
    $req->setRecNum($data['mobile']."");
    $req->setSmsTemplateCode('SMS_72535007');
    $paramsg = $req->getApiParas();

    $result = $c->execute($req);
    if(!empty($result)){
        $result = json_encode($result);
        $result = json_decode($result, true);

        if(isset($result['code'])){
            $sendResult['errcode'] = $result['code'];
            $sendResult['errmsg'] = $result['msg'];
        }else{
            $sendResult['errcode'] = $result['result']['err_code'];
            $sendResult['errmsg'] = $result['err_msg'];
        }
        $errmsg = addslashes($sendResult['errmsg']);
        $Model->execute("UPDATE message_sms SET errcode='{$sendResult['errcode']}', errmsg='{$errmsg}' WHERE id=".$id);
    }

    if($sendResult['errcode'] == 0){
        $sendResult['code'] = $data['code'];
    }
    return $sendResult;
}

function sync_notify($url, $param = array(), $post = array()){
    $urlinfo = parse_url($url);
    $host = $urlinfo['host'];
    $path = $urlinfo['path'];
    $query = $param ? http_build_query($param) : '';

    $port = 80;
    $errno = 0;
    $errstr = '';
    $timeout = 10;

    $fp   = fsockopen($host, $port, $errno, $errstr, $timeout);

    if(empty($post)){
        $out  = "GET ".$path.($query ? '?'.$query : '')." HTTP/1.1\r\n";
        $out .= "host:".$host."\r\n";
        $out .= "connection:close\r\n\r\n";
    }else{
        $post = http_build_query($post);
        $out  = "POST ".$path.($query ? '?'.$query : '')." HTTP/1.1\r\n";
        $out .= "host:".$host."\r\n";
        $out .= "content-length:".strlen($post)."\r\n";
        $out .= "content-type:application/x-www-form-urlencoded\r\n";
        $out .= "connection:close\r\n\r\n";
        $out .= $post;
    }

    fputs($fp, $out);
    fclose($fp);
}

/**
 * 创建签名
 */
function create_sign($param, $url = ''){
    $secret   = 'a10e31e31f92baff5a147f104ed88323';
    $token    = 'lxb';
    
    if(time() - $param['timestamp'] > 120){
        return '';
    }
    
    //签名步骤一：按字典序排序参数
    ksort($param);
    //签名步骤二：在string后加入KEY
    $string = "";
    foreach($param as $k => $v){
        if($k != "sign" && $v != "" && !is_array($v)){
            $string .= $k . "=" . $v . "&";
        }
    }
    $string = ($url == '' ? '' : $url.'?').trim($string, "&")."&token=".$token."&key=".$secret;
    //签名步骤三：MD5加密
    $string = md5($string);
    //签名步骤四：所有字符转为大写
    return strtoupper($string);
}

function second_to_time($second, $format = false){
    $zh = array("years" => '年', "days" => '天', "hours" => '小时',"minutes" => '分', "seconds" => '秒');
    $result = array("years" => 0, "days" => 0, "hours" => 0,"minutes" => 0, "seconds" => 0);
    
    if($second >= 31556926){
        $result["years"] = floor($second/31556926);
        $second = ($second%31556926);
    }
    if($second >= 86400){
        $result["days"] = floor($second/86400);
        $second = ($second%86400);
    }
    if($second >= 3600){
        $result["hours"] = floor($second/3600);
        $second = ($second%3600);
    }
    if($second >= 60){
        $result["minutes"] = floor($second/60);
        $second = ($second%60);
    }
    $result["seconds"] = floor($second);
    
    if($format){
        $start = false;
        $str = '';
        foreach($result as $key=>$value){
            if($value == 0 && !$start){
                continue;
            }
            $start = true;
            $str .= $value.$zh[$key];
        }
        return $str;
    }
    
    return $result;
}

function get_city_by_name($name, $pcode = 1){
    static $list = null;
    if(is_null($list)){
        $list = include(COMMON_PATH.'Conf/city.php');
    }
    
    foreach ($list as $code=>$item){
        if($item['name'] == $name && $item['pcode'] == $pcode){
            $item['code'] = $item['id'] = $code;
            return $item;
        }
    }
}

function decode_json($json, $toArray = true){
    if(is_numeric($json)){
        return $json;
    }else if(is_string($json)){
        if(!$json){
            return $toArray ? array() : new stdClass();
        }
        return json_decode($json, $toArray, 512, JSON_BIGINT_AS_STRING);
    }else if(is_array($json)){
        return $json;
    }else if(is_object($json) && $toArray){
        $json = json_encode($json, JSON_BIGINT_AS_STRING);
        return json_decode($json, $toArray, 512, JSON_BIGINT_AS_STRING);
    }
    return $toArray ? array() : new stdClass();
}

function encode_json($array){
    if(!is_array($array)){
        return $array;
    }else if(!$array){
        return '';
    }
    return json_encode($array, JSON_UNESCAPED_UNICODE|JSON_BIGINT_AS_STRING);
}

function explode_string($delimiter, $string){
    if(is_array($string)){
        return $string;
    }else if(is_null($string) || $string === ''){
        return array();
    }
    
    return explode($delimiter, $string);
}

function get_agent_group($groupId){
    $groupId = substr($groupId, 0, -1);
    
    $key = 'agent_'.$groupId;
    $data = S($key);
    if($data === false){
        $data = M()->query("SELECT project_id, title, items FROM agent_group WHERE id='{$groupId}'");
        if($data){
            $data = $data[0];
            $data['items'] = json_decode($data['items'], true);
        }
        S($key, $data, 7200);
    }
    
    $data['id'] = $groupId;
    $data['level'] = substr($groupId, -1);
    return $data;
}
?>