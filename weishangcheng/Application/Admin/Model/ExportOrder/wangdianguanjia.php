<?php
/**
 * 网店管家模板
 */
return function(\PHPExcel $objPHPExcel, array $tradeList){
    $worksheet1 = $objPHPExcel->getSheet(0);
    $worksheet2 = $objPHPExcel->getSheet(1);
    
    $sheet1Index = 1;
    $sheet2Index = 1;

    foreach($tradeList as $tid=>$trade){
        $sheet1Index++;
        $worksheet1->setCellValue('A'.$sheet1Index, $tid);  // 订单编号
        $worksheet1->setCellValue('C'.$sheet1Index, $trade['receiver_name']);  // 姓名
        $worksheet1->setCellValue('D'.$sheet1Index, $trade['receiver_mobile']);  // 电话
        $worksheet1->setCellValue('F'.$sheet1Index, $trade['receiver_province']);  //省
        $worksheet1->setCellValue('G'.$sheet1Index, $trade['receiver_city']);  // 市
        $worksheet1->setCellValue('H'.$sheet1Index, $trade['receiver_county']);  // 区县
        $worksheet1->setCellValue('I'.$sheet1Index, $trade['receiver_detail']);    // 详细地址
        $worksheet1->setCellValue('J'.$sheet1Index, $trade['receiver_zip']);  // 邮编
        $worksheet1->setCellValue('M'.$sheet1Index, $trade['seller_remark']);  // 客服备注
        $worksheet1->setCellValue('N'.$sheet1Index, $trade['buyer_remark']);  // 客户备注
        $worksheet1->setCellValue('O'.$sheet1Index, $trade['post_fee']);  // 邮费
        $worksheet1->setCellValue('S'.$sheet1Index, $trade['discount_fee']);  // 优惠金额
        $worksheet1->setCellValue('T'.$sheet1Index, $trade['pay_time']);  // 成交时间

        if($trade['express_id'] == 15){
            $worksheet1->getStyle('A'.$sheet1Index.':V'.$sheet1Index)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB(PHPExcel_Style_Color::COLOR_RED);
        }
        foreach($trade['orders'] as $order){
            $sheet2Index++;
            if($trade['express_id'] == 15){ //顺丰标红
            }
            $worksheet2->setCellValue('A'.$sheet2Index, $tid);  // 订单编号
            $worksheet2->setCellValue('B'.$sheet2Index, $order['outer_id']);  // 商品编码
            $worksheet2->setCellValue('C'.$sheet2Index, $order['title']);  // 商品名称
            $worksheet2->setCellValue('D'.$sheet2Index, $order['spec']);  // 商品规格
            $worksheet2->setCellValue('G'.$sheet2Index, $order['num']);  // 数量
            $worksheet2->setCellValue('H'.$sheet2Index, $order['discount_price']);  // 折扣后单价
            
            if($order['refund_num'] > 0 ){
                $worksheet2->setCellValue('J'.$sheet2Index, '退款'.$order['refund_num'].'件');  // 备注
            }
            
            if($trade['express_id'] == 15){
                $worksheet2->getStyle('A'.$sheet2Index.':J'.$sheet2Index)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB(PHPExcel_Style_Color::COLOR_RED);
            }
        }
    }
}

?>