<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 店铺出库记录
 * @author hua
 *
 */
class OrderExcelController extends CommonController
{
    private $allShop;
    function __construct(){
        parent::__construct();

        $this->allShop = \Common\Common\Auth::get()->validated('admin','shop','all');
    }
    
    public function index(){
        if(IS_AJAX){
            $this->exportlist();
        }else if(is_numeric($_GET['id'])){
            $this->down($_GET['id']);
        }
        
        $shopList = $this->allShop ? $this->shops() : null;
        $this->assign(array(
            'shopList'  => $shopList,
            'start_date' => date('Y-m-d 00:00:00', strtotime('-1 day')),
            'end_date'   => date('Y-m-d 23:59:59'),
        ));
        $this->display();
    }
    
    /**
     * 下载数据
     */
    private function down($id){
        header("Content-type:text/html;charset=utf-8");
        $data = M('trade_outstock')->field('filename')->where('id='.$id)->find();
        $fi = implode('', $data);
        $a=realpath('../Upload').'\exportSend'.'/'."$fi";
        $filename = str_replace('/','\\',$a);
         if(!file_exists($filename)){
            $this->error('文件不存在');
         } 

        Vendor('PHPExcel.PHPExcel.IOFactory');
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $text = iconv('UTF-8', 'GB2312', $fi);
        header('Content-Disposition: attachment;filename="'.$text.'"');
        header('Cache-Control: max-age=0');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }
    
    /**
     * 调用数据列表
     */
    private function exportlist(){
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $where = array();
        $Model = M('trade_outstock');
        $start = $_GET['start_date'];
        $end = $_GET['end_date'];
    
        $myShopId = $this->user('shop_id');
        if(!$this->allShop){
            $where[] = "e.shop_id='{$myShopId}'";
        }else if(is_numeric($_GET['shop_id'])){
            $where[] = "e.shop_id='".addslashes($_GET['shop_id'])."'";
        }
        if(is_numeric($_GET['uid'])){
            $where[] = "e.uid='".addslashes($_GET['uid'])."'";
        }
        if($start!='' && $end!=''){
            $where[] = " e.created between '".$start."' AND '".$end."'";
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
            $sql=" SELECT count(*) AS total
                    FROM trade_outstock AS e
                    INNER JOIN admin_user AS u ON u.id = e.uid
                    INNER JOIN shop AS s ON s.id = e.shop_id
                    where {$where}
                    ORDER BY e.id desc ";
            $data['total'] = $Model->query($sql);
            $total = $data['total'][0]['total'];
            $data['total'] = $total;
            if($data['total'] > 0){
                $sql=" SELECT e.id,e.shop_id,e.uid,e.created,e.trade_count,u.username,s.name
                        FROM trade_outstock AS e
                        INNER JOIN admin_user AS u ON u.id = e.uid
                        INNER JOIN shop AS s ON s.id = e.shop_id
                        where {$where}
                        ORDER BY e.id desc ";
                $sql.= " LIMIT {$offset},{$limit}";
                $data['rows'] = $Model->query($sql);
            }

        $this->ajaxReturn($data);
    }
    
}
?>