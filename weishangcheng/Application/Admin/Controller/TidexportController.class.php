<?php
namespace Admin\Controller;
use Common\Common\CommonController;

class TidexportController extends CommonController
{
    public function export(){
        $Model=M();
        $data=array('1'=>7,'2'=>15,'3'=>30,'4'=>60);
        $where='';
        foreach ($data as $i=>$val){
            $list=$this->search_time($val);
            if($val==7){
                $tid_count_7=$list['tid_count'];
                $tid_fee_7=$list['tid_fee'];
                $where.='tid_count_7='.$tid_count_7.',';
                $where.='tid_fee_7='.$tid_fee_7.',';
            }
            else if($val==15){
                $tid_count_15=$list['tid_count'];
                $tid_fee_15=$list['tid_fee'];
                $where.='tid_count_15='.$tid_count_15.',';
                $where.='tid_fee_15='.$tid_fee_15.',';
            }
            else if($val==30){
                $tid_count_30=$list['tid_count'];
                $tid_fee_30=$list['tid_fee'];
                $where.='tid_count_30='.$tid_count_30.',';
                $where.='tid_fee_30='.$tid_fee_30.',';
            }
            else {
                $tid_count_60=$list['tid_count'];
                $tid_fee_60=$list['tid_fee'];
                $where.='tid_count_60='.$tid_count_60.',';
                $where.='tid_fee_60='.$tid_fee_60.',';
            }
        }
        $where.="buyer_nike='".$list['buyer_nick']."'".','."created='".$list['created']."'";
        $list1=$Model->query("INSERT INTO tid_export SET $where");
    }
    
    public function search_time($data){
        $Model=M();
        $endDate=date('Y-m-d H:i:s');
        $startDate = date('Y-m-d H:i:s', strtotime("-$data day"));
        $sql = "SELECT buyer_nick,count(tid) as tid_count,SUM(total_fee+post_fee) as tid_fee,pay_time AS created from mall_trade
        where buyer_id in (SELECT id FROM member where pid=0 and agent_level in(2,3,4))
        AND pay_time BETWEEN '{$startDate}' AND  '{$endDate}'";
        $list = $Model->query($sql);
        return $list=$list['0'];
    }
}