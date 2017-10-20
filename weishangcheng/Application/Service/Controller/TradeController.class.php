<?php
namespace Service\Controller;

use Common\Common\CommonController;

/**
 * 数据统计热点图API
 *
 * @author lanxuebao
 */
class TradeController extends CommonController
{
    public function index()
    {
	$_where = array("t.created" => array('gt', date('Y-m-d H:i:s',NOW_TIME-10)));
	$data = M("mall_trade")
		->alias("t")
		->field("c.lng as lng,c.lat as lat")
		->join("city AS c ON t.receiver_city = c.`name`")
		->where($_where)
		->order("tid DESC")
		->limit(0,100)
		->select();
	$result	= array();
	foreach( $data as $key => $value )
	{
		$result[] = array(
			"from"	=> array(
					'lng'	=> 120.074913,
					'lat'	=> 29.306864
			),
			'to'	=> array(
					'lng'	=> (float)$value['lng'],
					'lat'	=> (float)$value['lat']
			),
		);
	}
	$this->ajaxReturn($result);
    }
}