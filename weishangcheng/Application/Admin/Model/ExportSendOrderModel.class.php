<?php
namespace Admin\Model;

use Common\Model\BaseModel;
use Common\Model\AlibabaModel;
use Common\Model\OrderStatus;
use Org\IdWork;

/**
 * 发货并导出订单
 * 
 * @author lanxuebao
 *
 */
class ExportSendOrderModel extends BaseModel
{
    protected $tableName = 'trade';
    
    private function queryList($shopId, $status){
        $receiver_name = I('get.receiver_name');//收货人姓名
        $receiver_mobile = I('get.receiver_mobile');//收货人手机号
        $buyer_mobile = I('get.buyer_mobile');
        $title = I('get.title');
        
        $pregDateTime = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/';
        $search = array(
            'tid'        => is_numeric($_GET['tid']) ? $_GET['tid'] : null,
            'start_date'   => preg_match($pregDateTime, $_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d 00:00:00', strtotime('-3 day')),
            'end_date'   => preg_match($pregDateTime, $_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d 23:59:59'),
            'buyer_id'   => is_numeric($_GET['buyer_id']) ? $_GET['buyer_id'] : null,
            'buyer_mobile'   => is_numeric($_GET['buyer_mobile']) ? $_GET['buyer_mobile'] : null,
            'pay_start_date'   => preg_match($pregDateTime, $_GET['pay_start_date']) ? $_GET['pay_start_date'] : null,
            'pay_end_date'   => preg_match($pregDateTime, $_GET['pay_end_date']) ? $_GET['pay_end_date'] : null,
            'receiver_mobile'   => preg_match('/^1[3|4|5|7|8]\d{9}$/', $_GET['receiver_mobile']) ? $_GET['receiver_mobile'] : null,
            'receiver_name'   => addslashes($_GET['receiver_name']),
            'shop_id'       => $shopId,
            'title'        => addslashes($_GET['title']),
            'refund_state' => $_GET['refund_state'],
        );

        $where = array("trade.seller_id=".$shopId);
        if($status != '' && $status != 'all'){
            $where[] = "trade.`status`='{$status}'";
        }
        $tidRange = IdWork::getTidRange($search['start_date'], $search['end_date']);
        $where[] = "trade.tid BETWEEN '".$tidRange[0]."' AND '".$tidRange[1]."'";
        $search['start_date'] = strtotime($search['start_date']);
        $search['end_date'] = strtotime($search['end_date']);
        $where[] = "trade.created BETWEEN '{$search['start_date']}' AND '{$search['end_date']}'";
        
        if(is_numeric($search['tid'])){
            if(strlen($search['tid']) == 13){ // 本系统
                $where[] = "trade.tid='{$search['tid']}'";
            }else if(strlen($search['tid']) == 16){ // 1688订单
                $tids = $this->query("SELECT GROUP_CONCAT(tid) AS tid FROM alibaba_trade WHERE out_tid='{$search['tid']}'");
                $tids = $tids[0]['tid'];
                if(empty($tids)){
                    $this->error('外部订单号不存在');
                }
                $where[] = "trade.tid IN ({$tids})";
            }else{
                $this->error = '订单号格式未识别';
                return false;
            }
        }
        
        if(is_numeric($search['buyer_id'])){
            $where[] = "trade.buyer_id={$search['buyer_id']}";
        }else if(is_numeric($search['buyer_mobile'])){
            $mids = $this->query("SELECT GROUP_CONCAT(id) AS ids FROM member WHERE mobile='{$search['buyer_mobile']}'");
            $mids = $mids[0]['ids'];
            if(empty($mids)){
                $this->error = '下单人手机号不存在';
                return false;
            }
        
            if(strpos($mids, ',')){
                $where[] = "trade.buyer_id IN({$mids})";
            }else{
                $where[] = "trade.buyer_id={$mids}";
            }
        }
        
        if($search['pay_start_date']){
            $where[] = "trade.pay_time>='{$search['pay_start_date']}'";
        }
        if($search['pay_end_date']){
            $where[] = "trade.pay_time<='{$search['pay_end_date']}'";
        }
        if($search['receiver_name']){
            $where[] = "trade.receiver_name='{$search['receiver_name']}'";
        }
        if($search['receiver_mobile']){
            $where[] = "trade.receiver_mobile='{$search['receiver_mobile']}'";
        }
        
        if($search['refund_state'] == '' || $search['refund_state'] == 'all'){
        
        }else if($search['refund_state'] == 'refunding'){
            $where[] = "trade.refund_state IN ('partial_refunding','full_refunding')";
        }else if($search['refund_state'] == 'refunded'){
            $where[] = "trade.refund_state IN ('partial_refunded', 'partial_failed', 'full_refunded', 'full_failed')";
        }else{
            $where[] = "trade.refund_state='{$search}'";
        }
        
        if($title != ''){
            $where[] = "`order`.title like '%{$title}%'";
        }
        $where = "WHERE ".implode(' AND ', $where);

        // 读取订单
        $selectSQL = "SELECT
                        trade.tid,trade.type, trade.kind, trade.total_quantity as total_num,trade.`status`, trade.paid_fee, trade.buyer_id, trade.express_id,
                        trade.refund_status AS trade_refund_state, trade.created, trade.pay_time, trade.total_cost,
                        trade.receiver_name, trade.receiver_mobile, trade.receiver_mobile, trade.receiver_province, trade.buyer_remark,
                        trade.seller_id, trade.total_fee AS trade_total_fee, trade.paid_balance, trade.paid_wallet as paid_no_balance,
                        trade.receiver_city, trade.receiver_county, trade.receiver_detail, trade.receiver_zip, trade.total_weight,
                        trade.total_postage as post_fee, trade.discount_fee AS trade_discount_fee, trade.payment AS trade_payment, trade.express_no,
                        `order`.title, `order`.quantity as num, `order`.price, `order`.outer_id, `order`.sku_json,`order`.product_id, `order`.weight,
                        `order`.cost,`order`.payment, `order`.discount_fee, `order`.total_fee, `order`.goods_id, trade.seller_name as seller_nick,
                        IF(ISNULL(refund.refund_status), 0, refund.refund_status) AS refund_state, trade.consign_time,
                        refund.refund_fee+refund.refund_post AS refund_fee, IF(ISNULL(refund.refund_quantity), 0, refund.refund_quantity) AS refund_quantity
                    FROM
                    (
                        SELECT DISTINCT trade.tid
                        FROM trade AS trade
                        INNER JOIN trade_order AS `order` ON `order`.tid=trade.tid
                        LEFT JOIN trade_refund AS refund ON refund.refund_id=`order`.oid
                        {$where}
                        ORDER BY trade.pay_time, trade.tid
                        LIMIT 1000
                    ) AS _trade
                    INNER JOIN trade AS trade ON trade.tid=_trade.tid
                    INNER JOIN trade_order AS `order` ON `order`.tid=trade.tid
                    LEFT JOIN trade_refund AS refund ON refund.refund_id=`order`.oid
                    ORDER BY trade.pay_time";
        $list = $this->query($selectSQL);
        return $list;
    }
    
    public function sendAndExport($shopId, $uid){
        if (PHP_SAPI == 'cli')
            die('This example should only be run from a Web Browser');
        ignore_user_abort(true);
        set_time_limit(0);

        $list = $this->queryList($shopId, OrderStatus::WAIT_SEND_GOODS);
        if(empty($list)){
            $this->error = '无待发货订单';
            return false;
        }
        
        //查找导出模板
        $_template = $this->query("SELECT * FROM template_outstock WHERE id='{$shopId}' AND type=1 LIMIT 1");
        if(empty($_template)){
            $this->error = '未上传待发货excel模板';
            return false;
        }
        $_template = $_template[0];
        $StaticModel = D('Static');
        $expressList = $StaticModel->express(false, 'id');
        
        // 开始数据处理
        $tradeList = $productSpec = $goodsList = array();
        $minTid = 9999999999999; $maxTid = 0;
        foreach($list as $i=>$item){
            if((string)$item['trade_refund_state'] == 'partial_refunding'  || (string)$item['trade_refund_state'] == 'full_refunding'){
                continue;
            }

            $item['discount_price'] = sprintf('%.2f', $item['payment'] / $item['num']);
            if($item['refund_state'] > 0 && $item['refund_state'] == 3){
                if($item['refund_quantity'] == $item['num']){ // 全都退了
                    continue;
                }else{
                    $item['num'] -= $item['refund_quantity'];
                }
            }else{
                $item['refund_quantity'] = 0;
            }

            // 主订单
            if(!isset($tradeList[$item['tid']])){
                $tradeList[$item['tid']] = array(
                    'tid'                 => $item['tid'],
                    'created'             => $item['created'],
                    'receiver_name'       => $item['receiver_name'],
                    'receiver_mobile'     => $item['receiver_mobile'],
                    'receiver_province'   => $item['receiver_province'],
                    'receiver_city'       => $item['receiver_city'],
                    'receiver_county'     => $item['receiver_county'],
                    'receiver_detail'     => $item['receiver_detail'],
                    'receiver_zip'        => $item['receiver_zip'] > 0 ? $item['receiver_zip'] : '',
                    'seller_nick'         => $item['seller_nick'],
                    'seller_remark'       => $item['seller_remark'],
                    'buyer_nick'          => $item['buyer_nick'],
                    'post_fee'            => $item['post_fee'],
                    'discount_fee'        => $item['trade_discount_fee'],
                    'payment'             => $item['trade_payment'],
                    'pay_time'            => $item['pay_time'],
                    'refund_state'        => $item['refund_state'],
                    'refunded_fee'        => $item['refunded_fee'],
                    'express_id'          => $item['express_id'],
                    'express_name'        => $expressList[$item['express_id']]['name'],
                    'kind'                => 0,
                    'total_num'           => 0,
                    'total_weight'        => 0,
                    'body'                => '',
                    'paid_fee'            => $item['paid_fee'],
                    'refunded_fee'        => $item['refunded_fee'],
                    'trade_refund_state'  => $item['trade_refund_state'],
                    'buyer_remark'        => $item['buyer_remark'],
                    'orders'              => array()
                );

                if($item['tid'] < $minTid){
                    $minTid = $item['tid'];
                }
                
                if($item['tid'] > $maxTid){
                    $maxTid = $item['tid'];
                }
            }

            $tradeList[$item['tid']]['kind']++;
            $tradeList[$item['tid']]['total_num'] += $item['num'];
            $tradeList[$item['tid']]['total_weight'] += $item['num'] * $item['weight'];
            
            if(!isset($productSpec[$item['product_id']])){
                $spec = get_spec_name($item['sku_json']);
                $productSpec[$item['product_id']] = $spec;
            }else{
                $spec =  $productSpec[$item['product_id']];
            }
            
            $tradeList[$item['tid']]['body'] .= (count($tradeList[$item['tid']]['orders']) == 0 ? "" : "\r\n").$item['title'].$spec."(".$item['num']."件)";
            
            // 子订单
            $tradeList[$item['tid']]['orders'][] = array(
                'oid'           => $item['oid'],
                'outer_id'      => $item['outer_id'],
                'title'         => $item['title'],
                'spec'          => $spec,
                'price'         => $item['price'],
                'num'           => $item['num'],
                'refund_quantity'    => $item['refund_quantity'],
                'payment'       => $item['payment'],
                'discount_price'=> $item['discount_price']
            );
            
            // 产品信息合并
            if(!isset($goodsList[$item['product_id']])){
                $goodsList[$item['product_id']] = array(
                    'goods_id'       => $item['goods_id'],
                    'outer_id'       => $item['outer_id'],
                    'title'          => $item['title'],
                    'spec'           => $item['spec'],
                    'num'            => $item['num'],
                    'refund_quantity'     => $item['refund_quantity'],
                    'total_fee'      => $item['total_fee'],
                    'payment'        => $item['payment']
                );
            }else{
                $goodsList[$item['product_id']]['num']          += $item['num'];
                $goodsList[$item['product_id']]['refund_quantity']   += $item['refund_quantity'];
                $goodsList[$item['product_id']]['total_fee']    += $item['total_fee'];
                $goodsList[$item['product_id']]['payment']      += $item['payment'];
            }
        }
        unset($productSpec);
        // 删除最后一个
        $tradeCount = count($tradeList);

        foreach ($tradeList as &$v){
            $v['tid'] = "'".$v['tid'];
        }
        // 加载PHPExcel
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $objPHPExcel = null;
        $filename = $_SERVER['DOCUMENT_ROOT'].'/../Upload/Template/ToOut/'.$_template['filename'];
        if(substr($_template['config'], -4) == '.php'){   // 使用php模板
            $objPHPExcel = \PHPExcel_IOFactory::load($filename);
            $setCellValue = require_once MODULE_PATH.'Model/ExportOrder/'.$_template['config'];
            $setCellValue($objPHPExcel, $tradeList);
        }else{ // excel列对应数据库字段
            $templates = json_decode($_template['config'], true);
            $objPHPExcel = \PHPExcel_IOFactory::load($filename);
            $this->setCellValue($objPHPExcel, $tradeList, $templates);
        }

        // 导出商品信息
        $this->exportGoodsInfo($objPHPExcel, $goodsList);
        unset($goodsList);
        
        // 设置文档基本属性
        $objPHPExcel->getProperties()
                    ->setTitle(date('Y-m-d H:i:s'));
        
        //把文件下载到服务器
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $month = date('Y-m');
        $folder = '../Upload/exportSend/'.$month;
        if(!@is_dir($folder)){
            mkdir ($folder, 0777, true);
        }
        $savename = date("Y-m-d His").'_'.$shopId.'.xlsx';
        $objWriter->save($folder.'/'.$savename);
        
        // 保存数据修改
        for($i=0; $i<$tradeCount; $i+=100){
            $keys = array_slice($tradeList, $i, 100, true );
            $keys = array_keys($keys);
            $toout = OrderStatus::OUT_STOCK;
            $this->execute("UPDATE trade SET `status`={$toout} WHERE tid IN (".implode(',', $keys).")");
            $this->execute("UPDATE trade_seller SET `status`={$toout} WHERE tid IN (".implode(',', $keys).")");
            $this->execute("UPDATE trade_buyer SET `status`={$toout} WHERE tid IN (".implode(',', $keys).")");
//            $this->lPublish('TradeCopy',$keys);
        }

        $sql = "INSERT INTO trade_outstock
                SET
                    shop_id='{$shopId}',
                    uid='{$uid}',
                    created='".date('Y-m-d H:i:s')."',
                    filename='{$month}/{$savename}',
                    trade_count={$tradeCount}
                    ";
//                    parameters='".json_encode($_GET, JSON_UNESCAPED_UNICODE)."'";
        
        $this->execute($sql);
        
        // Redirect output to a client’s web browser (Excel2007)
        $text = iconv('UTF-8', 'GB2312', '订单');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        //header('Cache-Control: max-age=1');
        
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter->save('php://output');
        
        header('X-Accel-Buffering: no');
        header('Content-Length: '. strlen(ob_get_contents()));
        header("Connection: close");
        header("HTTP/1.1 200 OK");
        ob_end_flush();
        flush();
        
//        // 1688订单批量下单
//        $aliModel = new AlibabaModel();
//        //$aliModel->commitOrder($minTid, $maxTid);
//        for($i=0; $i<$tradeCount; $i+=200){
//            $keys = array_slice($tradeList, $i, 200);
//            $keys = array_keys($keys);
//            $aliModel->commitOrder($keys);
//        }
        exit;
    }
    
    /**
    * 单元格赋值
    */
    private function setCellValue(\PHPExcel $objPHPExcel, $tradeList, $templates){
        foreach($tradeList as $tid=>$trade){
            $trade['total_weight'] = sprintf('%.2f', $trade['total_weight']);
            $_express_id = isset($templates[$trade['express_id']]) ? $trade['express_id'] : 0;
            
            $template = $templates[$_express_id];
            $worksheet = $objPHPExcel->getSheet($template['sheet']);
            $i = $template['start'];
            $templates[$_express_id]['start']++;
            
            // 单元格赋值
            foreach($template['field'] as $column=>$field){
                $worksheet->setCellValue($column.$i, $trade[$field]);
            }
        }
    }
    
    /**
     * 导出商品信息
     */
    private function exportGoodsInfo(\PHPExcel $objPHPExcel, array $goodsList){
        $sheetCount = $objPHPExcel->getSheetCount();
        $workSheet = $objPHPExcel->createSheet($sheetCount);
        $workSheet->setTitle('商品信息');
        $workSheet->setCellValue('A1', '产品编码');
        $workSheet->setCellValue('B1', '产品名称');
        $workSheet->setCellValue('C1', '产品规格');
        $workSheet->setCellValue('D1', '总数量');
        $workSheet->setCellValue('E1', '总金额');
        $workSheet->setCellValue('F1', '总实付');
        $workSheet->setCellValue('G1', '总退款数');
        $workSheet->setCellValue('H1', '商品ID');
        
        $index = 1;
        foreach ($goodsList as $item){
            $index++;
            $workSheet->setCellValue('A'.$index, $item['outer_id']);
            $workSheet->setCellValue('B'.$index, $item['title']);
            $workSheet->setCellValue('C'.$index, $item['spec']);
            $workSheet->setCellValue('D'.$index, $item['num'] + $item['refund_quantity']);
            $workSheet->setCellValue('E'.$index, sprintf('%.2f', $item['total_fee']));
            $workSheet->setCellValue('F'.$index, sprintf('%.2f', $item['payment']));
            $workSheet->setCellValue('G'.$index, $item['refund_quantity']);
            $workSheet->setCellValue('H'.$index, $item['goods_id']);
        }
    }
    
    /**
     * 导出产品销量
     * @param unknown $worksheet1
     * @param unknown $i
     * @param unknown $outerProduct
     * @param unknown $remarkProduct
     */
    public function productSale($worksheet, $i, $outerProduct, $remarkProduct){
        if(!empty($outerProduct)){
            foreach($outerProduct as $k=>$v){
                $i++;
                $worksheet
                ->setCellValueExplicit('A'.$i, $k, \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValue('B'.$i, $v['titel'])
                ->setCellValue('C'.$i, $v['spec'])
                ->setCellValue('D'.$i, $v['num'])
                ->setCellValue('E'.$i, $v['total_price'])
                ->setCellValueExplicit('F'.$i, $v['product_id'], \PHPExcel_Cell_DataType::TYPE_STRING);
            }
        }
        
        if(!empty($remarkProduct)){
            foreach($remarkProduct as $k=>$v){
                $i++;
                $worksheet
                ->setCellValueExplicit('A'.$i, $v['outer_id'], \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValue('B'.$i, $v['titel'])
                ->setCellValue('C'.$i, $v['spec'])
                ->setCellValue('D'.$i, $v['num'])
                ->setCellValue('E'.$i, $v['total_price'])
                ->setCellValueExplicit('F'.$i, $v['product_id'], \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValue('G'.$i, $v['buyer_remark']);
            }
        }
    }
    
    /**
     * 导出相同产品不同价格的销量
     * @param unknown $worksheet4
     * @param unknown $iiii
     * @param unknown $samePrice
     */
    private function samePrice($worksheet, $i, $samePrice){
        if(!empty($samePrice)){
            foreach($samePrice as $k=>$v){
                $i++;
                $worksheet
                ->setCellValueExplicit('A'.$i, $v['outer_id'], \PHPExcel_Cell_DataType::TYPE_STRING)
                ->setCellValue('B'.$i, $v['title'])
                ->setCellValue('C'.$i, $v['spec'])
                ->setCellValue('D'.$i, $v['num'])
                ->setCellValue('E'.$i, $v['price']);
            }
        }
    }
    
    /**
     * 导出订单
     */
    public function printOrder($shopId){
        $list = $this->queryList($shopId, $_GET['status']);
        if(empty($list)){
            $this->error = '暂无订单';
            return false;
        }
        
        $tradeList = $productSpec = array();
        foreach($list as $i=>$item){
            $item['discount_price'] = sprintf('%.2f', $item['payment'] / $item['num']);
            if($item['refund_state'] == 4 || $item['refund_state'] == 5){
                $item['refund_quantity'] = 0;
            }
        
            // 主订单
            if(!isset($tradeList[$item['tid']])){
                $tradeList[$item['tid']] = array(
                    'status'              => $item['status'],
                    'created'             => $item['created'],
                    'receiver_name'       => $item['receiver_name'],
                    'receiver_mobile'     => $item['receiver_mobile'],
                    'receiver_province'   => $item['receiver_province'],
                    'receiver_city'       => $item['receiver_city'],
                    'receiver_county'     => $item['receiver_county'],
                    'receiver_detail'     => $item['receiver_detail'],
                    'receiver_zip'        => $item['receiver_zip'] > 0 ? $item['receiver_zip'] : '',
                    'seller_nick'         => $item['seller_nick'],
                    'seller_remark'       => $item['seller_remark'],
                    'buyer_nick'          => $item['buyer_nick'],
                    'post_fee'            => $item['post_fee'],
                    'total_fee'           => $item['trade_total_fee'],
                    'discount_fee'        => $item['trade_discount_fee'],
                    'payment'             => $item['trade_payment'],
                    'pay_time'            => $item['pay_time'],
                    'refund_state'        => $item['trade_refund_state'],
                    'refunded_fee'        => $item['refunded_fee'],
                    'express_id'          => $item['express_id'],
                    'consign_time'        => $item['consign_time'],
                    'express_no'          => $item['express_no'],
                    'paid_balance'        => $item['paid_balance'],
                    'paid_no_balance'     => $item['paid_no_balance'],
                    'total_cost'          => $item['total_cost'],
                    'total_num'           => 0,
                    'total_weight'        => 0,
                    'paid_fee'            => $item['paid_fee'],
                    'refunded_fee'        => $item['refunded_fee'],
                    'buyer_remark'        => $item['buyer_remark'],
                    'orders'              => array()
                );
            }
        
            if(!isset($productSpec[$item['product_id']])){
                $spec = get_spec_name($item['sku_json']);
                $productSpec[$item['product_id']] = $spec;
            }else{
                $spec =  $productSpec[$item['product_id']];
            }
        
            // 子订单
            $tradeList[$item['tid']]['orders'][] = array(
                'oid'           => $item['oid'],
                'goods_id'      => $item['goods_id'],
                'outer_id'      => $item['outer_id'],
                'title'         => $item['title'],
                'spec'          => $spec,
                'price'         => $item['price'],
                'discount_price'=> $item['discount_price'],
                'num'           => $item['num'],
                'refund_quantity'    => $item['refund_quantity'],
                'refund_fee'    => $item['refund_fee'],
                'refund_state'  => $item['refund_state'],
                'payment'       => $item['payment'],
                'cost'          => $item['cost']
            );
        }
        //print_data($tradeList);
        unset($productSpec);
        unset($list);
        
        // 加载PHPExcel
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        
        // 读取工作表
        $worksheet = $objPHPExcel->setActiveSheetIndex(0);
        $worksheet->setTitle('订单列表');
        
        $i=1;
        $worksheet
        ->setCellValue('A'.$i, '订单号')
        ->setCellValue('B'.$i, '下单时间')
        ->setCellValue('C'.$i, '付款时间')
        ->setCellValue('D'.$i, '货品总价')
        ->setCellValue('E'.$i, '邮费')
        ->setCellValue('F'.$i, '涨价或折扣')
        ->setCellValue('G'.$i, '积分抵用')
        ->setCellValue('H'.$i, '积分支付')
        ->setCellValue('I'.$i, '微信支付')
        ->setCellValue('J'.$i, '实付款')
        ->setCellValue('K'.$i, '订单成本')
        ->setCellValue('L'.$i, '订单状态')
        ->setCellValue('M'.$i, '产品编码')
        ->setCellValue('N'.$i, '产品名称')
        ->setCellValue('O'.$i, '商品ID')
        ->setCellValue('P'.$i, '产品规格')
        ->setCellValue('Q'.$i, '售价')
        ->setCellValue('R'.$i, '折后价')
        ->setCellValue('S'.$i, '数量')
        ->setCellValue('T'.$i, '成本')
        ->setCellValue('U'.$i, '退货/退款')
        ->setCellValue('V'.$i, '退款总额')
        ->setCellValue('W'.$i, '退款状态')
        ->setCellValue('X'.$i, '买家姓名')
        ->setCellValue('Y'.$i, '收货人')
        ->setCellValue('Z'.$i, '收货手机号')
        ->setCellValue('AA'.$i, '收货省份')
        ->setCellValue('AB'.$i, '收货城市')
        ->setCellValue('AC'.$i, '收货区/县')
        ->setCellValue('AD'.$i, '买家留言')
        ->setCellValue('AE'.$i, '发货时间')
        ->setCellValue('AF'.$i, '物流公司运单号');
        
        //订单状态
        $StaticModel = new \Common\Model\StaticModel();
        $orderStatus = $StaticModel->orderStatus();
        $refundedState = $StaticModel->refundedState();
        foreach($tradeList as $tid=>$trade){
            $i++;
            
            $worksheet
            ->setCellValue('A'.$i, $tid)
            ->setCellValue('B'.$i, $trade['created'])
            ->setCellValue('C'.$i, $trade['pay_time'])
            ->setCellValue('D'.$i, $trade['total_fee'])
            ->setCellValue('E'.$i, $trade['post_fee'])
            ->setCellValue('F'.$i, -1*$trade['discount_fee'])
            ->setCellValue('G'.$i, -1*$trade['paid_no_balance'])
            ->setCellValue('H'.$i, $trade['paid_balance'])
            ->setCellValue('I'.$i, $trade['payment'])
            ->setCellValue('J'.$i, bcadd($trade['payment'], $trade['paid_balance'], 2))
            ->setCellValue('K'.$i, $trade['total_cost'])
            ->setCellValue('L'.$i, $orderStatus[$trade['status']])
            ->setCellValue('X'.$i, $trade['receiver_name'])
            ->setCellValue('Y'.$i, $trade['receiver_mobile'])
            ->setCellValue('Z'.$i, $trade['receiver_province'])
            ->setCellValue('AA'.$i, $trade['receiver_city'])
            ->setCellValue('AB'.$i, $trade['receiver_county'])
            ->setCellValue('AC'.$i, $trade['receiver_detail'])
            ->setCellValue('AD'.$i, $trade['buyer_remark'])
            ->setCellValue('AE'.$i, $trade['consign_time'])
            ->setCellValue('AF'.$i, $trade['express_no']);
            
            $productCount = count($trade['orders']);
            if($productCount > 1){
                $mergeLine = $productCount + $i - 1;
                $worksheet
                ->mergeCells("A{$i}:A{$mergeLine}")
                ->mergeCells("B{$i}:B{$mergeLine}")
                ->mergeCells("C{$i}:C{$mergeLine}")
                ->mergeCells("D{$i}:D{$mergeLine}")
                ->mergeCells("E{$i}:E{$mergeLine}")
                ->mergeCells("F{$i}:F{$mergeLine}")
                ->mergeCells("G{$i}:G{$mergeLine}")
                ->mergeCells("H{$i}:H{$mergeLine}")
                ->mergeCells("I{$i}:I{$mergeLine}")
                ->mergeCells("J{$i}:J{$mergeLine}")
                ->mergeCells("K{$i}:K{$mergeLine}")
                ->mergeCells("L{$i}:L{$mergeLine}")
                ->mergeCells("X{$i}:X{$mergeLine}")
                ->mergeCells("Y{$i}:Y{$mergeLine}")
                ->mergeCells("Z{$i}:Z{$mergeLine}")
                ->mergeCells("AA{$i}:AA{$mergeLine}")
                ->mergeCells("AB{$i}:AB{$mergeLine}")
                ->mergeCells("AC{$i}:AC{$mergeLine}")
                ->mergeCells("AD{$i}:AD{$mergeLine}")
                ->mergeCells("AE{$i}:AE{$mergeLine}")
                ->mergeCells("AF{$i}:AF{$mergeLine}");
            }
            
            foreach($trade['orders'] as $index=>$order){
                if($index > 0){
                    $i++;
                }
                
                $worksheet
                ->setCellValue('M'.$i, $order['outer_id'])
                ->setCellValue('N'.$i, $order['title'])
                ->setCellValue('O'.$i, $order['goods_id'])
                ->setCellValue('P'.$i, $order['spec'])
                ->setCellValue('Q'.$i, $order['price'])
                ->setCellValue('R'.$i, $order['discount_price'])
                ->setCellValue('S'.$i, $order['num'])
                ->setCellValue('T'.$i, $order['cost'])
                ->setCellValue('U'.$i, $order['refund_quantity'])
                ->setCellValue('V'.$i, $order['refund_fee'])
                ->setCellValue('W'.$i, $refundedState[$order['refund_state']]);
            }
        }
        $worksheet->getStyle('A1:AF'.($i+1))
        ->getAlignment()
        ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)
        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        
        // Redirect output to a client’s web browser (Excel2007)
        $text = iconv('UTF-8', 'GB2312', '订单记录');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        //header('Cache-Control: max-age=1');
        
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}
?>