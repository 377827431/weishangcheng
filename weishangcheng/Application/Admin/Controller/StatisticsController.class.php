<?php
namespace Admin\Controller;
use Common\Common\CommonController;

/**
 * 订单统计
 * @author lanxuebao
 *
 */
class StatisticsController extends CommonController
{
    /**
     * 显示一周内产品销售情况
     */
    public function index(){
        if(IS_AJAX){
            $data = $this->getSaleList();
            $this->ajaxReturn($data);
        }
        
        $start_date = I('get.start_date', date('Y-m-d H:i:s', strtotime('-1 week')));//开始时间 
        $end_date = I('get.end_date', date('Y-m-d H:i:s'));//结束时间
        
        $this->assign(array(
            "search"      => array(
                'start_date' => $start_date,
                'end_date' => $end_date,
            )
        ));
        $this->display();
    }
    
    /**
     * 获取一周内产品销量
     */
    private function getSaleList(){
        $parameters = $_GET;
        $data = array();
        $order = array();
        $where = array();
        if($parameters['start_date'] != '' && $parameters['end_date'] == '')
            $where['trade.created'] = array('egt', $parameters['start_date']);
        if($parameters['end_date'] != '' && $parameters['start_date'] == '')
            $where['trade.created'] = array('elt', $parameters['end_date']);
        if($parameters['start_date'] != '' && $parameters['end_date'] != '')
            $where['trade.created'] = array('between', array($parameters['start_date'] , $parameters['end_date']));
        $Model = M('mall_order');
        
        $list = $Model->alias("o")
                      ->field("o.tid,o.num,o.total_fee,o.product_id,
                               pro.price,pro.sku_json,pro.agent2_price,pro.agent3_price,
                               trade.status,goods.title")
                      ->join("mall_trade AS trade ON o.tid=trade.tid","INNER")
                      ->join("mall_product AS pro ON o.product_id=pro.id","INNER")
                      ->join("mall_goods AS goods ON o.goods_id=goods.id","INNER")
                      ->where($where)
                      ->select();
        
        $GoodsModel = D('Goods');
        foreach($list as $k=>$v){
            if(isset($data[$v["product_id"]])){
                $data[$v['product_id']]["sold_num"] += ($v['status'] == "send" ? $v["num"] : 0);
                $data[$v['product_id']]["trade_num"] += ($order[$v['product_id']][$v['tid']] == $v['tid'] ? 0 : 1);
                $data[$v['product_id']]["order_num"] += $v['num'];
                $data[$v['product_id']]["order_cancel_num"] += ($v['status'] == "cancel" ? 1 : 0);
                $data[$v['product_id']]["sold_cash"] += ($v['status'] == "send" ? $v['total_fee'] : 0);
            }else{
                $order[$v['product_id']][$v['tid']] = $v['tid'];
        
                $data[$v['product_id']] = array(
                    "sold_num" => $v['status'] == "send" ? $v["num"] : 0,
                    "title" => $v["title"],
                    "trade_num" => 1,
                    "order_num" => $v['num'],
                    "order_cancel_num" => $v['status'] == "cancel" ? 1 : 0,
                    "sold_cash" => $v['status'] == "send" ? $v['total_fee'] : 0,
                    "spec" => get_spec_name($v['sku_json']),
                    "agent2_price" => $v['agent2_price'],
                    "agent3_price" => $v['agent3_price'],
                    "price" => $v['price'],
                );
            }
        }
        
        //以sold_num进行排序
        $result = array();
        foreach($data as $v){
            $result[] = $v['sold_num'];
        }
        array_multisort($result, SORT_DESC, $data);
        
        return $data;
    }
    
    /**
     * 产品销量导出
     */
    public function exportSale(){
        $products = $this->getSaleList();
        
        // 加载PHPExcel
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
    
        // 设置文档基本属性
        $objPHPExcel->getProperties()
        ->setCreator("微通联盟")
        ->setLastModifiedBy("微通联盟")
        ->setTitle(date('Y-m-d H:i:s'));
        //->setDescription(json_encode($_POST));
    
        // 读取工作表
        $worksheet = $objPHPExcel->setActiveSheetIndex(0);
        $worksheet->setTitle('产品销量汇总');
    
        $i=1;   // 单元格写入开始行
        // 设置标题
        $worksheet
        ->setCellValue('A'.$i, '序号')
        ->setCellValue('B'.$i, '实际销售量')
        ->setCellValue('C'.$i, '产品名称')
        ->setCellValue('D'.$i, '产品规格')
        ->setCellValue('E'.$i, '包含产品的订单数量')
        ->setCellValue('F'.$i, '产品总下单次数')
        ->setCellValue('G'.$i, '产品的退货次数')
        ->setCellValue('H'.$i, '成交的产品总金额')
        ->setCellValue('I'.$i, '员工价')
        ->setCellValue('J'.$i, '会员价')
        ->setCellValue('K'.$i, '售价');
    
        foreach($products as $k=>$v){
            $i++;
            $worksheet
            ->setCellValueExplicit('A'.$i, $i-1)
            ->setCellValueExplicit('B'.$i, $v['sold_num'])
            ->setCellValueExplicit('C'.$i, $v['title'])
            ->setCellValueExplicit('D'.$i, $v['spec'])
            ->setCellValueExplicit('E'.$i, $v['trade_num'])
            ->setCellValueExplicit('F'.$i, $v['order_num'])
            ->setCellValueExplicit('G'.$i, $v['order_cancel_num'])
            ->setCellValueExplicit('H'.$i, $v['sold_cash'])
            ->setCellValueExplicit('I'.$i, $v['agent2_price'])
            ->setCellValueExplicit('J'.$i, $v['agent3_price'])
            ->setCellValueExplicit('K'.$i, $v['price']);
        }
    
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="产品销量汇总'.date('YmdHis').'.xlsx"');
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
}