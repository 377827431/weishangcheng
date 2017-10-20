<?php
namespace Admin\Model;

use Common\Model\BaseModel;
use Common\Model\AlibabaModel;

/**
 * 发货并导出订单
 * 
 * @author lanxuebao
 *
 */
class ExportFreeModel extends BaseModel
{
    protected $tableName = 'trade_gift';
    /*
     *导出赠品订单
     */
    public function printFreeOrder($title){
        $list = $this->query("SELECT * FROM trade_gift where title like '%".$_GET['title']."%' order by tid asc");
        if(empty($list)){
            $this->error = '暂无赠送订单';
            return false;
        }
        foreach($list as $i=>$item){
            // 主订单
                $freeList[$i+1] = array(
                    'tid'       => $item['tid'],
                    'mid'       => $item['mid'],
                    'created'   => date('Y-m-d H:i:s',$item['created']),
                    'goods_id'  => $item['goods_id'],
                    'title'     => $item['title'],
                    'quantity'  => $item['quantity'],
                    'status'    => $item['status'],
                );
        }
        // 加载PHPExcel
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        // 读取工作表
        $worksheet = $objPHPExcel->setActiveSheetIndex(0);
        $worksheet->setTitle('赠品列表');
    
        $i=1;
        $worksheet
        ->setCellValue('A'.$i, '订单号')
        ->setCellValue('B'.$i, '会员id')
        ->setCellValue('C'.$i, '领取时间')
        ->setCellValue('D'.$i, '商品id')
        ->setCellValue('E'.$i, '赠品名称')
        ->setCellValue('F'.$i, '领取数量')
        ->setCellValue('G'.$i, '状态');
        $status = array(
            '0'=>'待领取',
            '1'=>'已领取',
            '2'=>'已失效',
        );
        foreach($freeList as $i=>$item){
            $i++;
            $worksheet
            ->setCellValue('A'.$i, $item['tid'])
            ->setCellValue('B'.$i, $item['mid'])
            ->setCellValue('C'.$i, $item['created'])
            ->setCellValue('D'.$i, $item['goods_id'])
            ->setCellValue('E'.$i, $item['title'])
            ->setCellValue('F'.$i, $item['quantity'])
            ->setCellValue('G'.$i, $status[$item['status']]);
        }
        $worksheet->getStyle('A1:I'.($i+1))
        ->getAlignment()
        ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)
        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
    
        // Redirect output to a client’s web browser (Excel2007)
        $text = iconv('UTF-8', 'GB2312', '赠品领取记录');
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
    
}
?>