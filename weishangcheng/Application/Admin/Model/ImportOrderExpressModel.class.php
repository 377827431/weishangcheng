<?php
namespace Admin\Model;

use Common\Model\BaseModel;
use Think\Model;
use Org\Wechat\WechatAuth;
use Common\Model\OrderStatus;
/**
 * 导入订单 并计算结余
 * 
 * @author lanxuebao
 *        
 */
class ImportOrderExpressModel extends BaseModel
{
    protected $tableName = 'trade';
    private $sellerId;
    
    /**
     * 导入
     * @param unknown $filename
     */
    public function import($filename, $sellerId){
        $this->sellerId = $sellerId;
        header("Content-type: text/html; charset=utf-8");
        if (PHP_SAPI == 'cli'){
            exit('This example should only be run from a Web Browser');
        }
        
        ignore_user_abort(true);
        echo '<h1>系统正在导入，您可以干点别的，请勿关闭此窗口</h1>';
        ob_end_flush();
        flush();
        
        set_time_limit(0);
        Vendor('PHPExcel.PHPExcel.IOFactory');
    
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);

        $tradeList = $openidList = array();

        $this->startTrans();
        // 导入其他快递
        $this->importOther($objPHPExcel, $tradeList);
        $total = 0;
        $date = date("Y-m-d H:i:s");
        $rowsCount = count($tradeList);
        //更改订单状态 运单号
        foreach($tradeList as $tid=>$item){
            if(!$item['changed']){
                continue;
            }
            $total++;
            $express_no = "";
            foreach($item['express_no'] as $k=>$v){
                $express_no .= $v.":".$k.";";
                $tradeList[$tid]['express_name'] = $v;
                $tradeList[$tid]['express_no'] = $k;
            }
            
            // 运单：
            $express_no = rtrim($express_no,";");
            $toout = OrderStatus::OUT_STOCK;
            $send = OrderStatus::WAIT_CONFIRM_GOODS;
            $this->execute("UPDATE trade SET `status`=IF(`status`='{$toout}', '{$send}', `status`),express_no='{$express_no}',`consign_time`=IF(ISNULL(consign_time), '{$date}', `consign_time`) WHERE tid='{$tid}'");
//            $this->execute("UPDATE trade_seller SET `status`=IF(`status`='{$toout}', '{$send}', `status`) WHERE tid='{$tid}'");
//            $this->execute("UPDATE trade_buyer SET `status`=IF(`status`='{$toout}', '{$send}', `status`) WHERE tid='{$tid}'");
            $this->lPublish('TradeCopy',$tid);
        }
        $this->commit();
        echo '<h1>导入完毕，共计倒入'.$rowsCount.'条，更新'.$total.'条订单物流信息！</h1>';
        ignore_user_abort(true);
        header('X-Accel-Buffering: no');
        header('Content-Length: '. strlen(ob_get_contents()));
        header("Connection: close");
        header("HTTP/1.1 200 OK");
        ob_end_flush();
        flush();
        set_time_limit(0);  

        $wechatAuthList = array();
        foreach ($tradeList as $tid => $value){
            if(!$value['changed'] || $value['subscribe']==0){
                continue;
            }
            
            $WechatAuth = null;
            if(!isset($wechatAuthList[$value['appid']])){
                $config = get_wx_config($value['appid']);
                $WechatAuth = new WechatAuth($config['WEIXIN']);
                $wechatAuthList[$value['appid']] = $WechatAuth; 
            }else{
                $WechatAuth = $wechatAuthList[$value['appid']];
            }

            $message = array(
                'template_id' => $config['WX_TEMPLATE']['OPENTM200565259'],
                'url' => $config['HOST'].'/h5/order/detail?tid='.$tid,
                'data' => array(
                    "first"    => array("value" => '亲,宝贝已经启程了,好想快点来到您身边'),
                    "keyword1" => array("value" => $tid),
                    "keyword2" => array("value" => $value['express_name']),
                    "keyword3" => array("value" => $value['express_no']),
                    "remark"   => array("value" => '收货信息：'."\t".$value["receiver_name"] ."\t".$value["receiver_mobile"]."\t".$value["receiver_province"]. $value["receiver_city"]. $value["receiver_county"]. $value["receiver_detail"])
                )
            );
            $WechatAuth->sendTemplate($value['buyer_openid'], $message);
        }
    }
    
    /**
     * 导入其他
     */
    private function importOther(\PHPExcel $objPHPExcel, &$tradeList){
        $worksheet = $objPHPExcel->getSheet(0);
        $rows = $worksheet->getHighestRow();

        if($rows <= 1){
            return 0;
        }

        echo '<h3>获取到'.($rows-1).'行数据，开始导入...</h3>';
        ob_end_flush();
        flush();

        $count = 0;
        $tids = '';
        for($i=2; $i<=$rows; $i++){
            $tid = ''.$worksheet->getCell('A'.$i)->getValue();
            $tid = str_replace("'", '', $tid);
            $express_no = ''.$worksheet->getCell('B'.$i)->getValue();
            $express_name = $worksheet->getCell('C'.$i)->getValue();
            if(empty($tid) || empty($express_no)){
                continue;
            }
            
            if(empty($express_name)){
                $express_name = '其他快递';
            }

            $tid = substr($tid, 0, 16);
            if(isset($tradeList[$tid])){
                if($tradeList[$tid]['express_no'][$express_no] != $express_name){
                    $tradeList[$tid]['changed'] = 1;
                }
                $tradeList[$tid]['express_no'][$express_no] = $express_name;
            }else{
                $tradeList[$tid] = array('status' => '', 'express_no' => array($express_no => $express_name), 'changed' => 0);
                $tids .= "'".$tid."',";
                $count++;
            }

            if($count == 100){
                $count = 0;
                $this->getTrade($tids, $tradeList);
                $tids = '';
            }
        }

        if($tids != ''){
            $this->getTrade($tids, $tradeList);
        }
    }
    
    private function getTrade($tids, &$tradeList){
        if($tids == ""){
            return;
        }

        $static = D('Static');
        $allExpress = $static->express(true, 'id');
        
        $tids = rtrim($tids, ',');
        $Model = M();
        $where = '';
        $access = \Common\Common\Auth::get()->validated('admin','shop','all');
        if (!$access){
            $where = " AND seller_id=".$this->sellerId;
        }
        
        $sql = "SELECT
                	trade.tid,
                	trade.receiver_name,
                	trade.receiver_mobile,
                	trade.receiver_province,
                	trade.receiver_city,
                	trade.receiver_county,
                	trade.receiver_detail,
                	trade.buyer_openid,
                	trade.`status`,
                	trade.express_id,
                	trade.express_no,
                	wx_user.appid,
                	wx_user.subscribe
                FROM
                	trade
                LEFT JOIN wx_user ON wx_user.openid = trade.buyer_openid
                WHERE
                	trade.tid IN ({$tids})".$where;
        $list = $Model->query($sql);

        foreach($list as $item){
            if(empty($item['express_no'])){
                $tradeList[$item['tid']]['changed'] = 1;
            }else{
                $old = explode(';', $item['express_no']);
                $new = $tradeList[$item['tid']]['express_no'];
                
                $oldExpress = array();
                foreach ($old as $val){
                    $express = explode(':', $val);
                    if(isset($express[1])){
                        $oldExpress[''.$express[1]] = $express[0];
                    }else{
                        $oldExpress[''.$express[0]] = $allExpress[$item['express_id']]['name'];
                    }
                }
                
                foreach($new as $expressNo=>$expressName){
                    if(!array_key_exists($expressNo, $oldExpress) || $expressName != $oldExpress[$expressNo]){
                        $tradeList[$item['tid']]['changed'] = 1;
                        $oldExpress[''.$expressNo] = $expressName;
                    }
                }
                $tradeList[$item['tid']]['express_no'] = $oldExpress;
            }

            $tradeList[$item['tid']]['receiver_name'] = $item['receiver_name'];
            $tradeList[$item['tid']]['receiver_mobile'] = $item['receiver_mobile'];
            $tradeList[$item['tid']]['receiver_province'] = $item['receiver_province'];
            $tradeList[$item['tid']]['receiver_city'] = $item['receiver_city'];
            $tradeList[$item['tid']]['receiver_county'] = $item['receiver_county'];
            $tradeList[$item['tid']]['receiver_detail'] = $item['receiver_detail'];
            $tradeList[$item['tid']]['buyer_openid'] = $item['buyer_openid'];
            $tradeList[$item['tid']]['subscribe'] = $item['subscribe'];
            $tradeList[$item['tid']]['status'] = $item['status'];
            $tradeList[$item['tid']]['appid'] = $item['appid'];
        }
    }
}
?>