<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 销售统计
 */
class SaleController extends CommonController
{
    function __construct(){
        parent::__construct();
    }
    public function index(){
        $user = $this->user();
        if(IS_AJAX){
            $user = $this->user();
            $data = M()->query("SELECT 
                    m.d as date,m.c as count,m1.c as gz,m.paid_fee,m2.c as cz_c,m2.total_recharge as cz_fee 
                FROM (
                    SELECT FROM_UNIXTIME(created, '%Y-%m-%d') AS d, count(*) AS c, SUM(paid_fee) AS paid_fee FROM trade
                    WHERE `status` IN ( '3', '4', '5', '6', '7' ) AND seller_id = {$user['shop_id']}
                    AND created > DATE_FORMAT( FROM_UNIXTIME( UNIX_TIMESTAMP(now()) - 86400 * 9 ), '%Y-%m-%d' )
                    GROUP BY FROM_UNIXTIME(created, '%Y-%m-%d')
                    ORDER BY d DESC
                ) AS m LEFT JOIN (
                    SELECT DATE_FORMAT(FROM_UNIXTIME(subscribe_time),'%Y-%m-%d') as d,count(*) as c from wx_user 
                    LEFT JOIN project_member AS pm ON wx_user.mid = pm.mid
                    WHERE subscribe=1 and subscribe_time BETWEEN (UNIX_TIMESTAMP(CONCAT(DATE_FORMAT(now(),'%Y-%m-%d'),' 00:00:00')) - 9*86400) 
                        AND (UNIX_TIMESTAMP(CONCAT(DATE_FORMAT(now(),'%Y-%m-%d'),' 23:59:59')))  AND pm.project_id = '{$user['project_id']}' 
                    group by DATE_FORMAT(FROM_UNIXTIME(subscribe_time),'%Y-%m-%d') order BY d DESC
                ) as m1 on m.d=m1.d LEFT JOIN (
                    select DATE_FORMAT(created,'%Y-%m-%d') as d,count(*) as c, SUM(once_amount) AS total_recharge from recharge_agent where `status` = 'success' AND project_id = '{$user['project_id']}' 
                    AND created > DATE_FORMAT( FROM_UNIXTIME( UNIX_TIMESTAMP(now()) - 86400 * 9 ), '%Y-%m-%d' ) group by DATE_FORMAT(created,'%Y-%m-%d')
                ) as m2 on m.d=m2.d");
            $this->ajaxReturn($data);
        }
        $this->display();
    }
    
    public function goods(){
        $date = strtotime(I('date'));
        # 非预定类型输入与默认均查看昨天
        if ( $date <= 0 )
        {
            $date = NOW_TIME-86400;
        }
        $this->assign('date', date('Y-m-d', $date));
        if(IS_AJAX){
            $data = $this->forecast($date);
            $this->ajaxReturn($data);
        }
        $this->display();
    }
    
    private function forecast ( $date )
    {
        $user = $this->user();
        $start = strtotime(date('Y-m-d', $date-3*86400).' 00:00:00');
        $end = strtotime(date('Y-m-d', $date).' 23:59:59');
        $data = M()->query("SELECT
                created,title,sku,outer_id,avg(sum) as avg,
                IF (sum - avg(sum) > 0, '1', '2') AS trend,
                sum,
                IF (
                    sum - avg(sum) > 0,
                    sum * 1.1,
                    sum * 0.9
                ) AS forecast
            FROM
            (
                SELECT
                    g.created AS created,
                    product_id AS id,
                    p.sku_json AS sku,
                    DATE_FORMAT(t.created, '%Y-%m-%d') AS d,
                    o.title AS title,
                    p.outer_id AS outer_id,
                    sum(o.quantity) AS sum
                FROM
                    trade_order AS o
                LEFT JOIN trade AS t ON o.tid = t.tid
                LEFT JOIN mall_product AS p ON o.product_id = p.id
                LEFT JOIN mall_goods AS g ON o.goods_id = g.id
                WHERE
                    t.created > '{$start}'
                AND t.created < '{$end}'
                AND t.seller_id = '{$user['shop_id']}'
                GROUP BY
                    product_id,
                    FROM_UNIXTIME(t.created, '%Y-%m-%d')
                ORDER BY
                    id ASC,
                    d DESC
            ) AS t
            GROUP BY id ORDER BY sum DESC");
        print_data(M()->getlastsql());
        $GoodsModel = D('Goods');
        foreach( $data as $key => $value )
        {
            $data[$key]['sku'] = get_spec_name($value['sku']);
            # 计算销售趋势，推荐销量
            if ( $value['sum'] - $value['avg'] == 0 ) {
                $data[$key]['trend'] = 0;
                $data[$key]['forecast'] = $this->get_forecast($value['sum'],$data[$key]['trend']);
            }elseif ( $value['sum'] - $value['avg'] > 0 ) {
                $data[$key]['trend'] = 1;
                $data[$key]['forecast'] = $this->get_forecast($value['sum'],$data[$key]['trend']);
            }else{
                $data[$key]['trend'] = -1;
                $data[$key]['forecast'] = $this->get_forecast($value['sum'],$data[$key]['trend']);
            }
        }
        return $data;
    }
    
    // 预计销量函数 param: 当日实际数，销售趋势
    private function get_forecast ( $num, $trend )
    {
        $result = 0;
        switch ( $trend )
        {
            case -1:
                $result = $num*0.9;
            break;
            case 1:
                $result = $num*1.1;
            break;
            default:
                $result = $num;
            break;
        }
        return $result;
    }
    
    /**
     * 产品销量导出
     */
    public function exportSale(){
        $date = strtotime(I('date'));
        # 非预定类型输入与默认均查看昨天
        if ( $date <= 0 )
        {
            $date = NOW_TIME-86400;
        }
        $products = $this->forecast($date);
        
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
        $worksheet->setTitle(date('Y-m-d', $date).'简易预计次日出货量');
    
        $i=1;   // 单元格写入开始行
        // 设置标题
        $worksheet
        ->setCellValue('A'.$i, '品名')
        ->setCellValue('B'.$i, '规格')
        ->setCellValue('C'.$i, '创建时间')
        ->setCellValue('D'.$i, '商家编号')
        ->setCellValue('E'.$i, '前三天平均成交量')
        ->setCellValue('F'.$i, '成交趋势')
        ->setCellValue('G'.$i, '当天成交量')
        ->setCellValue('H'.$i, '预计次日出货量');
    
        foreach($products as $k=>$v){
            $i++;
            switch ( $v['trend'] )
            {
                case -1:
                    $v['trend'] = '降';
                break;
                case 1:
                    $v['trend'] = '升';
                break;
                default:
                    $v['trend'] = '平';
                break;
            }
            $worksheet
            ->setCellValueExplicit('A'.$i, $v['title'])
            ->setCellValueExplicit('B'.$i, $v['sku'])
            ->setCellValueExplicit('C'.$i, $v['created'])
            ->setCellValueExplicit('D'.$i, $v['outer_id'])
            ->setCellValueExplicit('E'.$i, $v['avg'])
            ->setCellValueExplicit('F'.$i, $v['trend'])
            ->setCellValueExplicit('G'.$i, $v['sum'])
            ->setCellValueExplicit('H'.$i, $v['forecast']);
        }
    
        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.date('Y-m-d', $date).'简易预计次日出货量.xlsx"');
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
    
    public function shopStat (){
        $user = $this->user();
        $startDate = I('get.start_date', date('Y-m-d 00:00:00'));//付款时间 - 开始
        $endDate = I('get.end_date', date('Y-m-d 23:59:59'));//付款时间 -  结束
        if(!IS_AJAX){
            $this->assign(array(
                'start_date'  => $startDate,
                'end_date'    => $endDate
            ));
            $this->display();
        }
        
        $minTid = 2016101500000;
        $minPayTime = '2016-10-15 23:59:59';
        $timespan = strtotime($startDate);
        if($timespan < strtotime($minPayTime)){
            $this->error('仅支持'.$minPayTime.'以后的数据');
        }
        $timespan = strtotime('-1 day', $timespan);
        $startTid = date('Ymd', $timespan).'00000';
        
        $Model = M();
        $sql = "SELECT
                    t.seller_id AS id, t.seller_name AS seller_nick,
                    COUNT(t.tid) AS `count`,SUM(t.total_fee) AS `total_fee`, SUM(t.total_postage) AS `post_fee`,
                    SUM(t.paid_balance) AS `paid_balance`,-SUM(t.paid_wallet) AS `paid_no_balance`, -SUM(t.discount_fee) AS `discount_fee`,
                    SUM(t.payment) AS `payment`,-SUM(ROUND(payment*0.006,2)) as `wechat_fee`, -SUM((SELECT SUM(total_fee) FROM trade WHERE tid=t.tid)) AS `trade_difference`,
                    -SUM(t.total_cost) AS total_cost,COUNT(DISTINCT t.buyer_id) AS `buyer_id`,SUM(t.total_quantity) AS `total_num`
                FROM trade AS t
                WHERE
                    t.tid > '{$startTid}' AND t.pay_time BETWEEN UNIX_TIMESTAMP('{$startDate}') AND UNIX_TIMESTAMP('{$endDate}') AND t.seller_id = '{$user['shop_id']}'
                GROUP BY t.seller_id
                ORDER BY `count` DESC, `total_fee` DESC, `total_postage` DESC";
        $tradeList = $Model->query($sql);
        
        // 在时间段内的退款，历史数据要去掉上次退款金额
        $sql = "SELECT seller_id AS id, seller_nick, SUM(sum_refund) AS refund_fee, SUM(back_fee) AS back_fee
                FROM (
            		SELECT tid, seller_id, seller_nick, sum_refund, IF(sum_lirun-history_refund-sum_refund>0, 0, sum_lirun-history_refund-sum_refund) AS back_fee
            		FROM (
        				SELECT refund.*, SUM(IF(ISNULL(history.refund_id), 0, `history`.refund_fee + `history`.refund_post)) AS history_refund 
        				FROM (
    						SELECT
								trade.tid, trade.seller_id, trade.seller_name AS seller_nick,
								trade.paid_balance+trade.payment-trade.total_cost AS sum_lirun,
								SUM(refund.refund_fee + refund.refund_post) AS sum_refund
    						FROM trade_refund AS refund
    						INNER JOIN trade_order AS `order` ON `order`.oid=refund.refund_id
    						INNER JOIN trade AS trade ON trade.tid=`order`.tid
                            WHERE
                                refund.refund_id>{$minTid} AND trade.pay_time>UNIX_TIMESTAMP('{$minPayTime}')
                                AND refund.refund_modify BETWEEN '{$startDate}' AND '{$endDate}'
                                AND refund.refund_status = '3'
                                AND trade.seller_id = '{$user['shop_id']}'
    						GROUP BY trade.tid
        				) AS refund
        				INNER JOIN trade_order AS `order` ON `order`.tid=refund.tid
        				LEFT JOIN trade_refund AS `history` ON `history`.refund_id=`order`.oid 
        					 AND `history`.refund_status = '3' AND `history`.refund_modify<'{$startDate}'
        				GROUP BY refund.tid
            		) AS refund
                ) AS record
                GROUP BY seller_id";
        //print_data($sql);
        $_refundList = $Model->query($sql);
        $refundList = array();
        foreach ($_refundList as $item){
            $item['refund_fee'] *= 1;
            $item['back_fee'] *= 1;
            $refundList[$item['id']] = $item;
        }
        
        // 合并数据
        foreach ($tradeList as $i=>$trade){
            if(isset($refundList[$trade['id']])){
                $trade['refund_fee'] = $refundList[$trade['id']]['refund_fee'];
                $trade['back_fee'] = $refundList[$trade['id']]['back_fee'];
            }else{
                $trade['refund_fee'] = 0;
                $trade['back_fee'] = 0;
            }

            $profit = bcadd($trade['paid_balance'], $trade['payment'], 2);
            $profit = bcadd($profit, $trade['wechat_fee'], 2);
            $profit = bcadd($profit, $trade['trade_difference'], 2);
            $profit = bcadd($profit, $trade['total_cost'], 2);
            
            $trade['profit'] = $profit*1;
            $trade['post_fee'] *= 1;
            $trade['paid_balance'] *= 1;
            $trade['paid_no_balance'] *= 1;
            $trade['discount_fee'] *= 1;
            $trade['payment'] *= 1;
            $trade['wechat_fee'] *= 1;
            $trade['trade_difference'] *= 1;
            $trade['total_cost'] *= 1;
            $trade['refund_fee'] *= 1;
            $trade['back_fee'] *= 1;
            $tradeList[$i] = $trade;
            unset($refundList[$trade['id']]);
        }
        
        foreach ($refundList as $refund){
            $tradeList[] = array('id' => $refund['id'], 'seller_nick' => $refund['seller_nick'], 'refund_fee' => $refund['refund_fee'], 'back_fee' => $refund['back_fee']);
        }
        
        $this->ajaxReturn($tradeList);
    }
}
?>