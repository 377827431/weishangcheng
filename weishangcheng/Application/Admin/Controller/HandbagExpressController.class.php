<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 手包赠送金
 */
class HandbagExpressController extends CommonController
{
    private $tableName = 'active_handbag_express';
    protected $authRelation = array(
        'export'    => 'admin.handbag_express.index'
    );
    
    public function index(){
        if(IS_AJAX){
            $data = $this->showData();
            $this->ajaxReturn($data);
        }
        $this->display();
    }
    
    /**
     * 获取where条件
     */
    private function getWhere(){
        $where = array();
        if(!empty($_GET['id'])){
            $where[] = "handbag.id='".addslashes($_GET['id'])."'";
        }else{
            if(is_numeric($_GET['mobile']))
                $where[] = "member.mobile>=".$_GET['mobile'];
            if(!empty($_GET['sign_start']) && !empty($_GET['sign_end']))
                $where[] = "handbag.sign_time BETWEEN ".strtotime($_GET['sign_start'])." AND ".strtotime($_GET['sign_end']);
            if(!empty($_GET['created_start']) && !empty($_GET['created_end']))
                $where[] = "handbag.created BETWEEN ".strtotime($_GET['created_start'])." AND ".strtotime($_GET['created_end']);
        }
        
        return $where;
    }
    
    private function showData(){
        $data = array('total' => 0, 'rows' => array());
        $Model = M($this->tableName);
        
        $where = $this->getWhere();
        if(count($where) == 0){
            $this->error('请输入查询条件');
        }
        
        $data['total'] = $Model->alias("handbag")
                        ->join("member ON member.id=handbag.mid")
                        ->where($where)
                        ->count();
        if($data['total'] == 0){
            return $data;
        }
        
        $list = $Model->alias("handbag")
                ->field("handbag.id, handbag.mid, handbag.sign_time, handbag.amount, handbag.created, handbag.times, member.nickname, member.mobile")
                ->join("member ON handbag.mid>0 AND member.id=handbag.mid")
                ->where($where)
                ->order("created DESC")
                ->select();

        foreach($list as $i=>$handbag){
            if($handbag['sign_time'] > 0){
                $handbag['sign_time'] = date('Y-m-d H:i', $handbag['sign_time']);
            }else{
                $handbag['sign_time'] = null;
                $handbag['amount'] = null;
                $handbag['nickname'] = null;
                $handbag['mobile'] = null;
            }
            
            $handbag['created']   = $handbag['created']   > 0 ? date('Y-m-d H:i', $handbag['created'])   : '';
            $data['rows'][$i] = $handbag;
        }
        
        return $data;
    }
    
    /**
     * 导入
     */
    public function import(){
        if(empty($_FILES) || !preg_match('/\.xl(s|sx)$/', $_FILES['file']['name'], $match)){
            $this->error("请上传excel文件！");
        }
        if($_FILES["file"]['error'] > 0){
            $this->error("文件格式错误！");
        }
        
        header("Content-type: text/html; charset=utf-8");
        if (PHP_SAPI == 'cli'){
            exit('This example should only be run from a Web Browser');
        }
        
        // 超长等待，忽略浏览器关闭
        set_time_limit(0);
        ignore_user_abort(true);
        $this->flushStr('<h1>系统正在导入...您可以先干点别的，关闭了也没关系！</h1>');
        $filename = $_FILES['file']['tmp_name'];
        Vendor('PHPExcel.PHPExcel.IOFactory');
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        $worksheet = $objPHPExcel->getSheet(0);
        $rowCount = $worksheet->getHighestRow();
        if($rowCount <= 1){
            return $this->flushStr('<h1 styel="color:red">未获取到数据</h1>');
        }
        $this->flushStr('<h3>获取到'.($rowCount - 1).'条数据，开始导入...</h3>');
        
        // 开始导入数据
        $values = '';
        $count = 0;
        $db = M();
        for($i=2; $i<=$rowCount; $i++){
            $expressNo = $worksheet->getCell('A'.$i)->getValue();
            if($expressNo == '' || $expressNo == 'null'){
                continue;
            }
            $employee = $worksheet->getCell('B'.$i)->getValue();
            preg_match('/^([a-z,A-Z]+)(\d+)/', $employee, $match);
            
            $values .= "('".$expressNo."', ".NOW_TIME.", '{$match[1]}', '{$match[2]}'),";
            if ($i % 100 == 0 || $i == $rowCount){
                $values = rtrim($values, ',');
                $count += $db->execute("INSERT IGNORE INTO {$this->tableName}(id, created, company, employee_no) VALUES".$values);
                $values = '';
                $this->flushStr('<h3>已导入'.($i-1).'条</h3>');
            }
        }
        $this->flushStr('<h3 style="color:red">导入完成！<br>共获取'.($rowCount-1).'条，导入并更新'.$count.'条数据</h3>');
    }
    
    private function flushStr($str){
        echo $str;
        ob_end_flush();
        flush();
    }
    
    /**
     * 导出
     */
    public function export(){
        $where = $this->getWhere();
        if(count($where) == 0)
            $this->error('请输入查询条件');
        
        
        $list = M($this->tableName)->alias("handbag")
                ->field("handbag.id, handbag.mid, handbag.sign_time, handbag.amount, handbag.created, handbag.times, member.nickname, member.mobile, company, employee_no")
                ->join("member ON handbag.mid>0 AND member.id=handbag.mid")
                ->where($where)
                ->order("created DESC")
                ->select();
        
        if(empty($list)){
            $this->error('无匹配数据');
        }
        
        // 加载PHPExcel
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        // 读取工作表
        $worksheet = $objPHPExcel->setActiveSheetIndex(0);
        
        $i=1;
        $worksheet
        ->setCellValue('A'.$i, '快递单号')
        ->setCellValue('B'.$i, '发货时间')
        ->setCellValue('C'.$i, '签收时间')
        ->setCellValue('D'.$i, '扫码次数')
        ->setCellValue('E'.$i, '员工编号')
        ->setCellValue('F'.$i, '派件代理')
        ->setCellValue('G'.$i, '派件奖励')
        ->setCellValue('H'.$i, '奖励时间');
        
        foreach($list as $item){
            $worksheet
            ->setCellValueExplicit('A'.$i, $item['id'], \PHPExcel_Cell_DataType::TYPE_STRING2)
            ->setCellValue('B'.$i, date('Y-m-d H:i', $item['created']))
            ->setCellValue('D'.$i, $item['times'])
            ->setCellValue('E'.$i, $item['company'].$item['employee_no']);
            if($item['mid'] > 0){
                $worksheet
                ->setCellValue('C'.$i, date('Y-m-d H:i', $item['sign_time']))
                ->setCellValueExplicit('F'.$i, $item['mobile'] ? $item['mobile'] : $item['nickname'], \PHPExcel_Cell_DataType::TYPE_STRING2)
                ->setCellValueExplicit('G'.$i, $item['amount'], \PHPExcel_Cell_DataType::TYPE_NUMERIC)
                ->setCellValue('H'.$i, $item['end_time']);
            }
            $i++;
        }
        
        $filename = iconv('UTF-8', 'GB2312', '扫码派单导出').'-'.date('YmdHis').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header ('Cache-Control: cache, must-revalidate');
        header ('Pragma: public');
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}