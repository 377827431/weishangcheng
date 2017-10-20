<?php
// 监听订阅列表
return array(
	'AlibabaTradeAdd' => array('module' => '\Admin\Model\AlibabaTradeSubscribe', 'action' => 'add'),
	'CommisionAdd' => array('module' => '\Admin\Model\CommisionSubscribe', 'action' => 'add'),
	'CommisionSettlement' => array('module' => '\Admin\Model\CommisionSubscribe', 'action' => 'settlement'),
	'CouponUsed' => array('module' => '\Admin\Model\CouponSubscribe', 'action' => 'used'),
	'CouponGive' => array('module' => '\Admin\Model\CouponSubscribe', 'action' => 'give'),
	'MemberBalance' => array('module' => '\Admin\Model\MemberSubscribe', 'action' => 'balance'),
	'MemberCheckLevelup' => array('module' => '\Admin\Model\MemberSubscribe', 'action' => 'checkLevelup'),
	'SellerOrderPaid' => array('module' => '\Admin\Model\SellerSubscribe', 'action' => 'orderPaid'),
	'TradeCreated' => array('module' => '\Admin\Model\TradeSubscribe', 'action' => 'created'),
	'TradeCopy' => array('module' => '\Admin\Model\TradeSubscribe', 'action' => 'copy'),
	'TradePaid' => array('module' => '\Admin\Model\TradeSubscribe', 'action' => 'paid'),
	'TradeMinusStock' => array('module' => '\Admin\Model\TradeSubscribe', 'action' => 'minusStock'),
	'BalanceNotify' => array('module' => '\Admin\Model\MemberSubscribe', 'action' => 'balanceNotify'),
	'MemberCardBuy' => array('module' => '\Admin\Model\MemberSubscribe', 'action' => 'buyCard'),
	'TradeBackCash' => array('module' => '\Admin\Model\TradeSubscribe', 'action' => 'backCash'),
	'TradeCancelled' => array('module' => '\Admin\Model\TradeSubscribe', 'action' => 'cancelled'),
	'MessageNotify' => array('module' => '\Admin\Model\MessageSubscribe', 'action' => 'template'),
	'Test' => array('module' => '\Admin\Model\TestSubscribe', 'action' => 'test'),
	'MessageViewed' => array('module' => '\Admin\Model\MessageSubscribe', 'action' => 'viewed'),
);
?>