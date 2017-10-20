<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 差价收益
 * @author hua
 *
 */
class DifferenceController extends CommonController
{
    public function index(){
        $start_date = I('get.start_date', date('Y-m-d', strtotime('-1 day')));//前一天
        $end_date = I('get.end_date', date('Y-m-d'));// 结束
        if(IS_AJAX){
           $data = $this->differencelist($paging=0);
           
           $this->ajaxReturn(array(
               "total" => $data['total'],
               "rows" => $data['rows']
           ));
        }
        $this->assign(array(
            start_date => $start_date,
            end_date   => $end_date
        ));
        $this->display();
    }
    
    /**
     * 导出
     */
    public function export(){
        $data = $this->differencelist($paging=1);
        // 加载PHPExcel
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
    
        // 读取工作表
        $worksheet = $objPHPExcel->setActiveSheetIndex(0);
        $worksheet->setTitle('差价收益');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');
        $searchStr = "查询时间段：".$_GET["start_date"]."至".$_GET["end_date"];
        if(!empty($_GET["mobile"])){
            $searchStr.= $_GET["mobile"];
        }
        if(!empty($_GET["tid"])){
            $searchStr.= "\r\n订单编号：".$_GET["tid"];
        }
        if(is_numeric($_GET["checkout"])){
            $searchStr.= $_GET["tid"] ==1 ? "\r\n已结算" : "\r\n未结算";
        }
        $worksheet->setCellValueExplicit('A1', $searchStr);
        $worksheet->getRowDimension(1)->setRowHeight(40);
   
        $i=3;   // 单元格写入开始行
        // 设置标题
        $worksheet
        ->setCellValue('A'.$i, '订单编号')
        ->setCellValue('B'.$i, '商品名称')
        ->setCellValue('C'.$i, '下单人')
        ->setCellValue('D'.$i, '收益人')
        ->setCellValue('E'.$i, '售价')
        ->setCellValue('F'.$i, '上一级售价')
        ->setCellValue('G'.$i, '差价')
        ->setCellValue('H'.$i, '数量')
        ->setCellValue('I'.$i, '收益');
        
        $sum = 0;
        foreach($data['rows'] as $k=>$v){
            $i++;
            $worksheet
            ->setCellValueExplicit('A'.$i, $v['tid'])
            ->setCellValueExplicit('B'.$i, $v['title'])
            ->setCellValueExplicit('C'.$i, $v['buyer_id'])
            ->setCellValueExplicit('D'.$i, $v['mid'])
            ->setCellValueExplicit('E'.$i, $v['sell_price'])
            ->setCellValueExplicit('F'.$i, $v['parent_price'])
            ->setCellValueExplicit('G'.$i, $v['diff_price'])
            ->setCellValueExplicit('H'.$i, $v['num'])
            ->setCellValueExplicit('I'.$i, $v['total_fee']);
            
            $sum+=$v['total_fee'];
        }
        $worksheet->setCellValueExplicit('A2', '总收益：'.$sum.'元');
    
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $text = iconv('UTF-8', 'GB2312', '差价交易');
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
    }
    
    /**
     * 方法调用
     */
    public function differencelist($paging){
        $Model = M("mall_trade_difference");
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $where = array();
        $start = $_GET['start_date'];
        $end = $_GET['end_date'];
        if(!empty($_GET['tid'])){//订单编号
            $where[] = "d.tid='".addslashes($_GET['tid'])."'";
        }
        if(is_numeric($_GET['mobile'])){//联系电话
            $where[] = "m.mobile='".addslashes($_GET['mobile'])."'";
        }
        if(is_numeric($_GET['checkout'])){//是否结算
            $where[] = "d.checkout='".addslashes($_GET['checkout'])."'";
        }
        if($start!='' && $end!=''){
            $st = (str_replace('-','',$start)).'00000';
            $en = (str_replace('-','',$end)).'99999';
            $where[] = "d.tid <='".$en."'";
            $where[] = "d.tid >='".$st."'";
            $date1 = date_create($start);
            $date2 = date_create($end);
            $diff = date_diff($date1,$date2);
            $date = str_replace('+','',$diff->format("%R%a"));
            if($date>31){
                $this->error('只能查询一个月以内的');
            }
        }
        
        $where = implode(' AND ', $where);
        
        if($paging == 0){
            $data = array('total' => 0, 'rows' => array());
            $sql=" SELECT count(*) AS total
                   FROM mall_trade_difference AS d
                   INNER JOIN mall_order AS o ON d.tid=o.tid AND d.oid=o.oid
                   INNER JOIN member AS m ON m.id = d.mid
                   INNER JOIN member AS b ON b.id = d.buyer_id
                   WHERE {$where}";
            $data['total'] = $Model->query($sql);
            $total = $data['total'][0]['total'];
            $data['total'] = $total;
            if($data['total'] > 0){
                $sql=" SELECT o.sku_json,o.title,o.goods_id,d.*,m.mobile,m.nickname, b.nickname AS buyer_nick
                        FROM mall_trade_difference AS d
                        INNER JOIN mall_order AS o ON d.tid=o.tid AND d.oid=o.oid
                        INNER JOIN member AS m ON m.id = d.mid
                        INNER JOIN member AS b ON b.id = d.buyer_id
                        where {$where}
                        ORDER BY d.tid desc
                        LIMIT {$offset},{$limit}";
                $data['rows'] = $Model->query($sql);
           }
        }else{
            $sql=" SELECT o.sku_json,o.title,o.goods_id,d.*,m.mobile,m.nickname, b.nickname AS buyer_nick
                    FROM mall_trade_difference AS d
                    INNER JOIN mall_order AS o ON d.tid=o.tid AND d.oid=o.oid
                    INNER JOIN member AS m ON m.id = d.mid
                    INNER JOIN member AS b ON b.id = d.buyer_id
                    where {$where}
                    ORDER BY d.tid desc";
            $data['rows'] = $Model->query($sql);
        }
        
        foreach ($data['rows'] as $key => $value){
            $data['rows'][$key]['sku_json'] = get_spec_name($value['sku_json']);
            $checkout = $value['checkout'] == 1 ? '已结算' : '未结算';
            $data['rows'][$key]['checkout'] = $checkout;
        }
        return $data;
    }
}
?>