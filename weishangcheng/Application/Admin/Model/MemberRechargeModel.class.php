<?php 
namespace Common\Model;

class MemberRechargeModel extends BaseModel{
    protected $tableName = 'recharge_agent';
    
    public function getAll(){
        $data = array('total' => 0, 'rows' => array());
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        
        // 搜索条件
        $where = array();
        if($_GET['status']){
            $where['recharge.`status`'] = $_GET['status'];
        }
        
        if(is_numeric($_GET['mobile']))
            $where['member.mobile'] = $_GET['mobile'];
        if($_GET['start_date'] != '')
            $where['recharge.created'] = array('egt', $_GET['start_date']);
        if($_GET['end_date'] != '')
            $where['recharge.created'] = array('elt', $_GET['end_date']);
        if($_GET['start_date'] != '' && $_GET['end_date'] != '')
            $where['recharge.created'] = array(array('egt', $_GET['start_date']),array('elt', $_GET['end_date']),'and');
        
        $data['total'] = $this->alias("recharge")
                              ->join("member ON member.id=recharge.buyer_id")
                              ->where($where)
                              ->count();

        if($data['total'] == 0){
            return $data;
        }
        
        $list = $this->alias("recharge")
                             ->field("recharge.*, member.nickname")
                             ->join("member ON member.id=recharge.buyer_id")
                             ->where($where)
                             ->limit($offset, $limit)
                             ->order("recharge.tid DESC")
                             ->select();
        
        $data['rows'] = $this->parseData($list);
        return $data;
    }
    
    /**
     * 解析数据
     * @param unknown $list
     * @return multitype:string
     */
    private function parseData($list){
        $result = array();
        $agent = $this->agentLevel();
        foreach($list as $item){
            $detail = json_decode($item['detail'], true);
            unset($item['detail']);
            $item['self_amount'] = $detail[0]['money'];
        
            if(isset($detail[1])){
                $detail[1]['agent_title'] = $agent[$detail[1]['agent_level']]['title'];
                $item['parent1'] = $detail[1];
            }else{
                $item['parent1'] = null;
            }
        
            if(isset($detail[2])){
                $detail[2]['agent_title'] = $agent[$detail[2]['agent_level']]['title'];
                $item['parent2'] = $detail[2];
            }else{
                $item['parent2'] = null;
            }
        
            if(isset($detail[3])){
                $detail[3]['agent_title'] = $agent[$detail[3]['agent_level']]['title'];
                $item['parent3'] = $detail[3];
            }else{
                $item['parent3'] = null;
            }
            $item['status_str']   = $item['status'] == 'success' ? '成功' : '待付款';
            $result[] = $item;
        }
        
        return $result;
    }
    
    /**
     * 导出
     */
    public function export(){
        $where = array();
        if($_GET['status']){
            $where['recharge.`status`'] = $_GET['status'];
        }
        
        if(is_numeric($_GET['mobile']))
            $where['member.mobile'] = $_GET['mobile'];
        if($_GET['start_date'] != '')
            $where['recharge.created'] = array('egt', $_GET['start_date']);
        if($_GET['end_date'] != '')
            $where['recharge.created'] = array('elt', $_GET['end_date']);
        if($_GET['start_date'] != '' && $_GET['end_date'] != '')
            $where['recharge.created'] = array(array('egt', $_GET['start_date']),array('elt', $_GET['end_date']),'and');
        
        $list = $this->alias("recharge")
                ->field("recharge.*, member.nickname, member.mobile")
                ->join("member ON member.id=recharge.buyer_id")
                ->where($where)
                ->order("recharge.tid DESC")
                ->select();
        $list = $this->parseData($list);
        
        $date = date('Y-m-d H:i:s');
        // 加载PHPExcel
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        
        // 读取第一个工作表
        $worksheet = $objPHPExcel->setActiveSheetIndex(0);
        $worksheet->setTitle('代理充值');
        
        $i=1;
        $worksheet
        ->setCellValue('A'.$i, '代理姓名')
        ->setCellValue('B'.$i, '代理手机号')
        ->setCellValue('C'.$i, '充值金额')
        ->setCellValue('D'.$i, '晋升等级')
        ->setCellValue('E'.$i, '赠送货款')
        ->setCellValue('F'.$i, '一级代理收益')
        ->setCellValue('G'.$i, '二级代理收益')
        ->setCellValue('H'.$i, '三级代理收益')
        ->setCellValue('I'.$i, '状态')
        ->setCellValue('J'.$i, '操作时间');
        $agent = $this->agentLevel();
        foreach($list AS $item){
            $i++;
            $worksheet
            ->setCellValue('A'.$i, $item['nickname'])
            ->setCellValue('B'.$i, $item['mobile'])
            ->setCellValue('C'.$i, $item['once_amount'])
            ->setCellValue('D'.$i, $agent[$item['agent_level']]['title'])
            ->setCellValue('E'.$i, $item['self_amount'])
            ->setCellValue('F'.$i, $item['parent1']['money'].$item['parent1']['agent_title'])
            ->setCellValue('G'.$i, $item['parent2']['money'].$item['parent2']['agent_title'])
            ->setCellValue('H'.$i, $item['parent3']['money'].$item['parent3']['agent_title'])
            ->setCellValue('I'.$i, $item['status_str'])
            ->setCellValue('J'.$i, $item['created']);
        }
        // Redirect output to a client’s web browser (Excel2007)
        $text = iconv('UTF-8', 'GB2312', '代理充值记录导出');
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