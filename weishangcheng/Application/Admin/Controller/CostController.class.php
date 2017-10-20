<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 订单成本核算
 * @author lanxuebao
 */
class CostController extends CommonController
{
    public function index(){
        
    }
    
    public function price(){
        $result = $list = array();
        $filename = '../hesuan.xlsx';
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        
        $worksheet = $objPHPExcel->getSheet(0);
        $rows = $worksheet->getHighestRow();
        for($i=2; $i<=$rows; $i++){
            $goodsId = trim($worksheet->getCell('H'.$i)->getValue());
            if(!is_numeric($goodsId)){
                continue;
            }
        
            $price = trim($worksheet->getCell('C'.$i)->getValue());
            if(!isset($list[$goodsId])){
                $list[$goodsId] = array($price);
            }else if(!in_array($price, $list[$goodsId])){
                $list[$goodsId][] = $price;
                if(!in_array($goodsId, $result)){
                    $result[] = $goodsId;
                }
            }
        }
        
        print_data($result);
    }
    
    public function spec(){
        set_time_limit(0);
        $result = array();
        Vendor('PHPExcel.PHPExcel.IOFactory');
        
        $filename = '../hesuan.xlsx';
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        
        $worksheet = $objPHPExcel->getSheet(2);
        $rows = $worksheet->getHighestRow();
        for($i=2; $i<=$rows; $i++){
            $cell = $worksheet->getCell('O'.$i);
            $json = trim($cell->getValue());
            $spec = get_spec_name($json);
            $cell->setValue($spec);
        }
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $text = iconv('UTF-8', 'GB2312', '核算 - 结果');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objWriter->save('php://output');
    }
    
    public function num(){
        $result = $result1 = array();
        
        $filename = '../hesuan.xlsx';
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        $worksheet = $objPHPExcel->getSheet(1);
        $rows = $worksheet->getHighestRow();
        for($i=2; $i<=$rows; $i++){
            $sendTime = trim($worksheet->getCell('N'.$i)->getValue());
            if(empty($sendTime)){
                continue;
            }
            
            $tid = trim($worksheet->getCell('A'.$i)->getValue());
            if(!is_numeric($tid)){
                continue;
            }
            $num = trim($worksheet->getCell('B'.$i)->getValue());
            
            if(!isset($result1[$tid])){
                $result1[$tid] = $num;
            }else{
                $result1[$tid] += $num;
            }
        }
        
        $sended = $this->sendexcel();
        
        foreach ($result1 as $tid=>$num){
            if(!isset($sended[$tid])){
                $result[$tid] = '未发货';
            }else if($num != $sended[$tid]){
                $result[$tid] = '发货'.$sended[$tid].'件；数据库'.$num.'件';
            }
        }
        
        print_data($result);
    }
    
    public function postfee(){
        $result = $result1 = array();
        
        $filename = '../hesuan.xlsx';
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        $worksheet = $objPHPExcel->getSheet(1);
        $rows = $worksheet->getHighestRow();
        for($i=2; $i<=$rows; $i++){
            $tid = trim($worksheet->getCell('A'.$i)->getValue());
            if(!is_numeric($tid)){
                continue;
            }
            
            $postfee = trim($worksheet->getCell('D'.$i)->getValue());
            if(!is_numeric($postfee)){
                continue;
            }
            
            if(!isset($result1[$tid])){
                $result1[$tid] = $postfee;
            }else if($result1[$tid] != $postfee){
                $result[] = $tid;
            }
        }
        
        print_data($result);
    }
    
    public function sendexcel(){
        $result = array();
        Vendor('PHPExcel.PHPExcel.IOFactory');
        
        $dir = realpath("../yiwu");  //要获取的目录
        $dh = opendir($dir);
        while ($file = readdir($dh)){
            if($file!="." && $file!=".."){
                $filename = $dir.'\\'.$file;
                $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        
                $worksheet = $objPHPExcel->getSheet(1);
                $rows = $worksheet->getHighestRow();
                for($i=2; $i<=$rows; $i++){
                    $tid = trim($worksheet->getCell('A'.$i)->getValue());
                    $num = trim($worksheet->getCell('D'.$i)->getValue());
        
                    if(!isset($result[$tid])){
                        $result[$tid] = $num;
                    }else{
                        $result[$tid] += $num;
                    }
                }
            }
        }
        closedir($dh);
        return $result;
    }
    
    public function hesuan_price(){
        $Model = M();
        $_oldList = $Model->query("SELECT * FROM yw_price");
        $goodsList = array();
        foreach ($_oldList as $item){
            $goodsList[$item['goods_id'].$item['spec']] = $item['cost'];
        }
        
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $filename = '../hesuan.xlsx';
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        $worksheet = $objPHPExcel->getSheet(0);
        $rows = $worksheet->getHighestRow();
        $result = array();
        for($i=2; $i<=$rows; $i++){
            $tid = trim($worksheet->getCell('A'.$i)->getValue());
            $goodsId = trim($worksheet->getCell('K'.$i)->getValue());
            $spec = trim($worksheet->getCell('L'.$i)->getValue());
            if(isset($goodsList[$goodsId.$spec])){
                $worksheet->setCellValue('F'.$i, $goodsList[$goodsId.$spec]);
            }
        }
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $text = iconv('UTF-8', 'GB2312', '核算 - 结果');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objWriter->save('php://output');
    }
    
    public function setShop(){
        $Model = M();
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $filename = '../hesuan.xlsx';
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        $worksheet = $objPHPExcel->getSheet(0);
        $rows = $worksheet->getHighestRow();
        $result = array();
        for($i=2; $i<=$rows; $i++){
            $outTid = trim($worksheet->getCell('K'.$i)->getValue());
            $tid = trim($worksheet->getCell('D'.$i)->getValue());
            if(is_numeric($tid)){
                $sql = "SELECT seller_nick, tid, pay_time
                        FROM trade
                        WHERE tid=".$tid;
            }else if(is_numeric($outTid)){
                $sql = "SELECT seller_nick, tid, pay_time
                        FROM trade
                        WHERE tid=(SELECT tid FROM alibaba_trade WHERE alibaba_trade.out_tid='{$outTid}' AND is_del=0 AND do_cost=1)";
            }else{
                continue;
            }
            
            $data = $Model->query($sql);
            if(empty($data)){
                continue;
            }
            $data = $data[0];
            
            $worksheet
            ->setCellValue('C'.$i, $data['seller_nick'])
            ->setCellValue('D'.$i, $data['tid'])
            ->setCellValue('E'.$i, $data['pay_time']);
        }
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $text = iconv('UTF-8', 'GB2312', '核算 - 结果');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objWriter->save('php://output');
    }
    
    public function refund(){
        $Model = M();
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $filename = '../hesuan.xlsx';
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        $worksheet = $objPHPExcel->getSheet(0);
        $rows = $worksheet->getHighestRow();
        $result = array();
        for($i=2; $i<=$rows; $i++){
            $tid = trim($worksheet->getCell('D'.$i)->getValue());
            if(!is_numeric($tid)){
                continue;
            }
            
            $sql = "SELECT SUM(trade.paid_balance+trade.payment) AS paid_total,
                           trade.total_cost, trade_refund.refund_modify,
                           SUM(trade_refund.refund_fee+trade_refund.refund_post) AS refund_total,
                           trade.refunded_fee
                    FROM trade
                    INNER JOIN mall_order ON mall_order.tid=trade.tid
                    LEFT JOIN trade_refund ON trade_refund.refund_id=mall_order.oid AND trade_refund.refund_state='3'
                    WHERE trade.tid={$tid}";
            $data = $Model->query($sql);
            if(empty($data)){
                continue;
            }
            $data = $data[0];
            
            $worksheet
            ->setCellValue('F'.$i, $data['paid_total'])
            ->setCellValue('G'.$i, $data['total_cost'])
            ->setCellValue('H'.$i, $data['refund_total'])
            ->setCellValue('I'.$i, $data['refunded_fee']);
        }
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $text = iconv('UTF-8', 'GB2312', '核算 - 结果');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objWriter->save('php://output');
    }
}
?>