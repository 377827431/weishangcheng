<?php
namespace H5\Controller;

use Common\Common\CommonController;
use Org\IdWork;

class RateController extends CommonController{
    
    public function index(){
    	$Model = M();
        if(!is_numeric($_GET['id']) || !is_numeric($_GET['offset']) || !is_numeric($_GET['size'])){
            $this->error('参数错误');
        }
        
        $where = "";
        switch ($_GET['type']){
        	case 'good':
        		$where .= "AND rate.score=5";
        		break;
        	case 'middle':
        		$where .= "AND rate.score BETWEEN 3 AND 4";
        		break;
        	case 'bad':
        		$where .= "AND rate.score<3";
        		break;
        }
        
        $list = array();
        $sql = "SELECT
					member.name,
					member.head_img,
				    rate.goods_spec,
					rate.feedback,
					rate.score,
					rate.created,
					rate.anonymous,
				    rate.images
				FROM trade_rate AS rate
				LEFT JOIN member ON member.id = rate.mid
				WHERE rate.goods_id={$_GET['id']} {$where} AND rate.visible = 1
                ORDER BY rate.oid DESC
                LIMIT {$_GET['offset']}, {$_GET['size']}";
        $rows = $Model->query($sql);
        
        foreach ($rows as $val){
        	$list[] = array(
        		'created'  => date('Y-m-d', $val['created']),
                'headimg'  => $val['head_img'],
                'score'    => $val['score'],
        		'nickname' => IdWork::anonymous($val['name']),
        		'spec'     => $val['goods_spec'],
                'feedback' => $val['feedback'],
                'images'   => explode_string('|', $val['images'])
            ); 
        }
        $this->ajaxReturn($list);
    }
}
?> 