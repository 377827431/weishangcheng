<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 微信体现记录
 * @author hua
 *
 */
class TransfersController extends CommonController
{
    public function index(){
        $start_date = I('get.start_date', date('Y-m-d 00:00:00', strtotime('-1 day')));//前一天
        $end_date = I('get.end_date', date('Y-m-d 23:59:59'));// 结束
        if(IS_AJAX){
            $data = $this->transferslist($paging=0);
            $this->ajaxReturn(array(
                "total" => $data['total'],
                "rows" => $data['rows']
            ));
        }
         $this->assign(array(
            start_date => $start_date,
            end_date   => $end_date,
            wxlist     => C('WXLIST'),
        )); 
         $this->display();
    }
    
    /**
     * 导出
     */
    public function export(){
        $wxList = C('WXLIST');
        $searchStr = "查询时间段：".$_GET["start_date"]."至".$_GET["end_date"];
        if(!empty($_GET['result_code'])){
            $searchStr.= $_GET["result_code"]==success ? "\r\n成功" : "\r\n失败";
        }
        if(!empty($_GET["mobile"])){
             $searchStr.= "\r\n 手机号：".$_GET["mobile"];
        }
        if($_GET['start_amount']!='' && $_GET['end_amount']!=''){
             $searchStr.= "\r\n 提现金额：".$_GET["start_amount"]."至".$_GET["end_amount"];
        }
         
        $data = $this->transferslist($paging=1);
        // 加载PHPExcel
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $wxUseedCount = 0;//已经用了几个sheet了
        foreach($data['rows'] as $k=>$v){
            if(!$wxList[$v['mch_appid']]['inited']){
                $wxList[$v['mch_appid']]['row'] = 4;
                $wxList[$v['mch_appid']]['money'] = 0;
                $wxList[$v['mch_appid']]['inited'] = true;
                $wxUseedCount++;
                $sheetCount = $objPHPExcel->getSheetCount();
                if($sheetCount < $wxUseedCount){
                   $objPHPExcel->createSheet();
                }
                
                $worksheet = $wxList[$v['mch_appid']]['sheet'] = $objPHPExcel->getSheet($wxUseedCount-1);
                $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');
                $worksheet->setCellValueExplicit('A1', $searchStr);
                $worksheet->getRowDimension(1)->setRowHeight(40);
                $name = $wxList[$v['mch_appid']]['name'];
                $worksheet->setTitle($name);
                $worksheet
                ->setCellValue('A3', '序号')
                ->setCellValue('B3', '会员名称')
                ->setCellValue('C3', '手机号')
                ->setCellValue('D3', 'openid')
                ->setCellValue('E3', '提现金额')
                ->setCellValue('F3', '提现前金额（包含不可提现）')
                ->setCellValue('G3', '不可提现金额（提现前）')
                ->setCellValue('H3', '是否提现成功')
                ->setCellValue('I3', '提现时间');
            }else{
                $worksheet = $wxList[$v['mch_appid']]['sheet'];
            }
            
            $i = $wxList[$v['mch_appid']]['row'];
            $wxList[$v['mch_appid']]['row']=$i+1;
            $worksheet
            ->setCellValueExplicit('A'.$i, $i-1)
            ->setCellValueExplicit('B'.$i, $v['nickname'])
            ->setCellValueExplicit('C'.$i, $v['mobile'])
            ->setCellValueExplicit('D'.$i, $v['openid'])
            ->setCellValueExplicit('E'.$i, $v['amount'])
            ->setCellValueExplicit('F'.$i, $v['balance'])
            ->setCellValueExplicit('G'.$i, $v['no_balance'])
            ->setCellValueExplicit('H'.$i, $v['result_code'])
            ->setCellValueExplicit('I'.$i, $v['payment_time']);
            
            $money = $wxList[$v['mch_appid']]['money']+$v['amount'];
            $wxList[$v['mch_appid']]['money'] = $money;
            
        }
        foreach ($wxList as $v){
            if($v['sheet']){
                $v['sheet']->setCellValueExplicit('A2', '总提现金额：'.$v['money'].'元');
            }
            
        }
       
    
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $text = iconv('UTF-8', 'GB2312', '微信提现记录');
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
     * 提现与导出调用
     */
    public function transferslist($paging){
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $where = array();
        $Model = M('wx_transfers');
        $mobile = $_GET['mobile'];
        $openid = $_GET['openid'];
        $amount = $_GET['amount'];
        $start = $_GET['start_date'];
        $end = $_GET['end_date'];
        
        if(!empty($_GET['result_code'])){//是否成功
            $where[] = "t.result_code='".addslashes($_GET['result_code'])."'";
        }
        if(is_numeric($mobile)){//会员手机
            $where[] = "m.mobile='".addslashes($mobile)."'";
        }
        if(!empty($_GET['appid'])){
            $where[] = "t.mch_appid='".addslashes($_GET['appid'])."'";
        }
        if(is_numeric($_GET['start_amount'])){
            $where[] = "t.amount>='".$_GET['start_amount']."'";
        }
        if(is_numeric($_GET['end_amount'])){
            $where[] = "t.amount<='".$_GET['end_amount']."'";
        }
        if($start!='' && $end!=''){
            $where[] = " t.payment_time between '".$start."' AND '".$end."'";
            $date1 = date_create($start);
            $date2 = date_create($end);
            $diff = date_diff($date1,$date2);
            $date = str_replace('+','',$diff->format("%R%a"));
            if($date>31){
                $this->error('只能查询一个月以内的');
            }
        }   
        
        $where = implode(' AND ', $where);
        $data = array('total' => 0, 'rows' => array());
        if($paging==0){
            $sql=" SELECT count(*) AS total
            FROM wx_transfers AS t
            INNER JOIN member AS m ON m.id = t.mid
            where {$where}
            ORDER BY t.id desc ";
            $data['total'] = $Model->query($sql);
            $total = $data['total'][0]['total'];
            $data['total'] = $total;
            if($data['total'] > 0){
                $sql=" SELECT t.mid,t.mch_appid,t.result_code,t.amount,t.balance,t.payment_time,t.no_balance,m.mobile,m.nickname
                FROM wx_transfers AS t
                INNER JOIN member AS m ON m.id = t.mid
                where {$where}
                ORDER BY t.id desc ";
                $sql.= " LIMIT {$offset},{$limit}";
                $data['rows'] = $Model->query($sql);
            }
        }else{
            $sql=" SELECT t.mid,t.openid,t.mch_appid,t.result_code,t.amount,t.balance,t.payment_time,t.no_balance,m.mobile,m.nickname
            FROM wx_transfers AS t
            INNER JOIN member AS m ON m.id = t.mid
            where {$where}
            ";
            $data['rows'] = $Model->query($sql);
        }
        foreach ($data['rows'] as $key => $value){
            $result_code = $value['result_code'] == SUCCESS ? '成功' : '失败';
            $data['rows'][$key]['result_code'] = $result_code;
        }
        return $data;
    }
    
}
?>