<?php 
namespace Admin\Model;

use Common\Model\BaseModel;
use Common\Model\OrderModel;
class InvitationModel extends BaseModel{
    protected $tableName = 'member_invitation_code';
    
    /**
     * 导出
     */
    public function export($id){
        set_time_limit(0);
        $sql = "SELECT mir.id,mir.code,mir.card_id,mir.mid,mir.used_time,pc.title,member.nickname,member.mobile
                FROM member_invitation_record as mir left join project_card as pc on mir.card_id=pc.id 
                left join member on member.id =mir.mid  where mir.id = {$id}";
        $list = $this->query($sql);
        $date = date('Y-m-d H:i:s');
        // 加载PHPExcel
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        
        // 读取第一个工作表
        $worksheet = $objPHPExcel->setActiveSheetIndex(0);
        $worksheet->setTitle('邀请码');
        
        $i=1;
        $worksheet
        ->setCellValue('A'.$i, 'ID')
        ->setCellValue('B'.$i, '邀请码')
        ->setCellValue('C'.$i, '会员卡')
        ->setCellValue('D'.$i, '被邀请人ID')
        ->setCellValue('E'.$i, '被邀请时间')
        ->setCellValue('F'.$i, '被邀请昵称')
        ->setCellValue('G'.$i, '被邀请电话');
        
        foreach($list AS $invitationId=>$invitation){
            $i++;
            $worksheet
            ->setCellValue('A'.$i, $invitation['id'])
            ->setCellValue('B'.$i, $invitation['code'])
            ->setCellValue('C'.$i, $invitation['title'])
            ->setCellValue('D'.$i, $invitation['mid'])
            ->setCellValue('E'.$i, $invitation['used_time'] = $invitation['used_time']>0 ? date('Y-m-d H:i:s',$invitation['used_time']) :'0')
            ->setCellValue('F'.$i, $invitation['nickname'])
            ->setCellValue('G'.$i, $invitation['mobile']);
            
            // 合并单元格
            $productCount = count($invitation['products']);
            if($productCount > 1){
                $mergeLine = $productCount + $i - 1;
                
                $worksheet
                ->mergeCells("A{$i}:A{$mergeLine}")
                ->mergeCells("B{$i}:B{$mergeLine}")
                ->mergeCells("C{$i}:C{$mergeLine}")
                ->mergeCells("M{$i}:M{$mergeLine}")
                ->mergeCells("N{$i}:N{$mergeLine}")
                ->mergeCells("O{$i}:O{$mergeLine}")
                ->mergeCells("P{$i}:P{$mergeLine}")
                ->mergeCells("Q{$i}:Q{$mergeLine}")
                ->mergeCells("R{$i}:R{$mergeLine}")
                ->mergeCells("S{$i}:S{$mergeLine}")
                ->mergeCells("T{$i}:T{$mergeLine}");
            }
        }
        
        $worksheet->getStyle('A1:T'.(count($list)+1))
        ->getAlignment()
        ->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)
        ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        
        $text = iconv('UTF-8', 'GB2312', '邀请码');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
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