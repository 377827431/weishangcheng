<?php 
namespace Common\Model;

class StaticModel{
    /**
     * 快递公司
     */
    public static function express($key = -1){
        static $list = null;

        if(is_null($list)){
            $list = include COMMON_PATH.'Conf/express.php';
        }
        
        if($key > -1){
            return $list[$key];
        }
        
        return  $list;
    }
    
    /**
     * 获取城市列表
     * @return unknown
     */
    public static function getCityList($code = 0){
        static $allCityList;
        if(is_null($allCityList)){
            $allCityList = include COMMON_PATH.'/Conf/city.php';
        }

        if($code > 0){
            return $allCityList[$code];
        }
        return $allCityList;
    }
    
    /**
     * 客服类型
     */
    public static function getCustomerServiceType(){
        return array(
            '1' => '平台咨询',
            '2' => '产品小视频'
        );
    }

    public static function getNeedRefund($refundedFee, $paidList){
        $refundedFee = floatval($refundedFee);

        $sort = array('wallet', 'weixin', 'balance');
        $prev = array('wallet' => 0, 'weixin' => 0, 'balance' => 0, 'surplus' => 0);
        if($refundedFee > 0){
            $field = $sort[0];
            $prev[$field] = $refundedFee >= $paidList[$field] ? $paidList[$field] : $refundedFee;
            $refundedFee = floatval(bcsub($refundedFee, $prev[$field], 2));

            if($refundedFee > 0){
                $field = $sort[1];
                $prev[$field] = $refundedFee >= $paidList[$field] ? $paidList[$field] : $refundedFee;
                $refundedFee = floatval(bcsub($refundedFee, $prev[$field], 2));

                if($refundedFee > 0){
                    $field = $sort[2];
                    $prev[$field] = $refundedFee >= $paidList[$field] ? $paidList[$field] : $refundedFee;
                    $refundedFee = floatval(bcsub($refundedFee, $prev[$field], 2));

                    if($refundedFee > 0){
                        $surplus = floatval(bcadd($prev['balance'], $refundedFee, 2));
                        $prev['balance'] = $surplus;
                        $prev['surplus'] = $surplus;
                    }
                }
            }
        }

        return $prev;
    }
}

/**
 * 订单状态
 * @author lanxuebao
 *
 */
class OrderStatus{
    /**待付款*/
    const WAIT_PAY = 1;
    /**待确认付款*/
    const WAIT_PAY_CONFIRM = 2;
    /**待发货*/
    const WAIT_SEND_GOODS = 3;
    /**出库中*/
    const OUT_STOCK = 4;
    /**部分发货*/
    const PART_SEND_GOODS = 5;
    /**待确认收货*/
    const WAIT_CONFIRM_GOODS = 6;
    /**买家关闭*/
    const BUYER_CANCEL = 7;
    /**交易成功*/
    const SUCCESS = 8;
    
    /**1688待下单*/
    const ALI_WAIT_ORDER = 21;
    /**1688待付款*/
    const ALI_WAIT_PAY = 25;
    /**1688下单成功*/
    const ALI_ORDER_SUCCESS = 22;
    /**1688结束(不再更新订单)*/
    const ALI_ORDER_END = 23;
    /**1688订单异常*/
    const ALI_FAIL = 24;
    
    static public function getAll(){
        return array(
            1 => array('title' => '待付款', 'key' => '', 'describe' => '等待买家付款'),
            //2 => array('title' => '待确认付款', 'key' => '', 'describe' => ''),
            3 => array('title' => '待发货', 'key' => '', 'describe' => '买家已付款'),
            4 => array('title' => '出库中', 'key' => '', 'describe' => ''),
            //5 => array('title' => '部分发货', 'key' => '', 'describe' => ''),
            6 => array('title' => '待确认收货', 'key' => '', 'describe' => '供应商已发货'),
            7 => array('title' => '交易关闭', 'key' => '', 'describe' => '交易关闭'),
            8 => array('title' => '交易成功', 'key' => '', 'describe' => '已完成'),
            25=> array('title' => '待发货' ,'key' => '', 'describe' => '买家已付款')
        );
    }
    
    /**
     * 获取结算方式
     * @param int $id
     * @return string 方式值
     */
    static public function getById($id = -1, $onlyTitle = true){
        $list = self::getAll();
        return $id > - 1 ? ($onlyTitle ? $list[$id]['title'] : $list[$id]) : $list;
    }
    
    /**
     * 获取订单的退款状态/文本
     */
    static public function getAliStatus($id = -1, $onlyTitle = true){
        static $list = null;
        if(is_null($list)){
            $list = array(
                21 => array('title' => '待下单', 'key' => 'ALI_WAIT_ORDER', 'describe' => ''),
                22 => array('title' => '下单成功', 'key' => '下单成功', 'describe' => ''),
                23 => array('title' => '已完成', 'key' => 'ALI_ORDER_END', 'describe' => ''),
                24 => array('title' => '订单异常', 'key' => 'ALI_FAIL', 'describe' => ''),
            );
        }
        
        return $id > - 1 ? ($onlyTitle ? $list[$id]['title'] : $list[$id]) : $list;
    }
}

/**
 * 退款状态
 * @author lanxuebao
 *
 */
class RefundStatus{
    /**无退款*/
    const NO_REFUND = 0;
    /**部分退款中*/
    const PARTIAL_REFUNDING = 1;
    /**已部分退款*/
    const PARTIAL_REFUNDED = 2;
    /**部分退款失败*/
    const PARTIAL_FAILED = 3;
    /**全额退款中*/
    const FULL_REFUNDING = 4;
    /**已全额退款*/
    const FULL_REFUNDED = 5;
    /**全额退款失败*/
    const FULL_FAILED = 6;
    /**超额退款*/
    const EXCESS_REFUND = 7;
    /**退款申请中*/
    const APPLYING = 11;
    /**待上传单号*/
    const WAIT_EXPRESS_NO = 12;
    /**等待退款*/
    const WAIT_REFUND = 13;
    /**已退款*/
    const REFUNDED = 14;
    /**拒绝退款*/
    const REFUSED_REFUND = 15;
    /**已取消退款*/
    const CANCEL_REFUND = 16;
    
    /**
     * 获取订单的退款状态/文本
     */
    static public function getTradeStatus($id = -1, $onlyTitle = true){
        static $list = null;
        if(is_null($list)){
            $list = array(
                0 => array('title' => '无退款', 'key' => 'NONE', 'describe' => ''),
                1 => array('title' => '部分退款中', 'key' => 'NONE', 'describe' => ''),
                2 => array('title' => '已部分退款', 'key' => 'NONE', 'describe' => ''),
                3 => array('title' => '部分退款失败', 'key' => 'NONE', 'describe' => ''),
                4 => array('title' => '全额退款中', 'key' => 'NONE', 'describe' => ''),
                5 => array('title' => '已全额退款', 'key' => 'NONE', 'describe' => ''),
                6 => array('title' => '全额退款失败', 'key' => 'NONE', 'describe' => ''),
                6 => array('title' => '超额退款', 'key' => 'NONE', 'describe' => '')
            );
        }
        
        return $id > - 1 ? ($onlyTitle ? $list[$id]['title'] : $list[$id]) : $list;
    }
    
    /**
     * 获取子订单的退款状态/文本
     */
    static public function getOrderStatus($id = -1, $onlyTitle = true){
        static $list = null;
        if(is_null($list)){
            $list = array(
                0  => array('title' => '无退款', 'key' => 'NONE', 'describe' => ''),
                11 => array('title' => '申请中', 'key' => 'NONE', 'describe' => ''),
                12 => array('title' => '待上传单号', 'key' => 'NONE', 'describe' => ''),
                13 => array('title' => '等待退款', 'key' => 'NONE', 'describe' => ''),
                14 => array('title' => '已退款', 'key' => 'NONE', 'describe' => ''),
                15 => array('title' => '拒绝退款', 'key' => 'NONE', 'describe' => ''),
                16 => array('title' => '取消退款', 'key' => 'NONE', 'describe' => '')
            );
        }
        
        return $id > - 1 ? ($onlyTitle ? $list[$id]['title'] : $list[$id]) : $list;
    }
}

/**
 * 付款方式
 * @author lanxuebao
 *
 */
class PayType{
    /**微信自有支付*/
    const WEIXIN = 1;
    /**零钱支付*/
    const BALANCE = 2;
    /**货款支付*/
    const WALLET = 3;
    /**积分兑换*/
    const SCORE = 4;
    /**货到付款*/
    const CODPAY = 5;
    /**代付*/
    const PEERPAY = 6;
    /**支付宝支付*/
    const ALIPAY = 7;
    /**银行卡支付*/
    const BANKCARDPAY = 8;
    /**领取赠品*/
    const GIFT = 9;
    /**优惠券/码全额抵扣*/
    const COUPON = 10;
    /**合并付货款*/
    const MERGEDPAY = 11;
    
    static public function getAll(){
        return array(
            0  => array('title' => '', 'key' => 'NONE', 'describe' => ''),
            1  => array('title' => '微信支付', 'key' => 'WEIXIN', 'describe' => ''),
            2  => array('title' => '零钱支付', 'key' => 'BALANCE', 'describe' => ''),
            3  => array('title' => '货款支付', 'key' => 'WALLET', 'describe' => ''),
            4  => array('title' => '积分兑换', 'key' => '', 'describe' => ''),
            5  => array('title' => '货到付款', 'key' => '', 'describe' => ''),
            6  => array('title' => '代付', 'key' => '', 'describe' => ''),
            7  => array('title' => '支付宝支付', 'key' => '', 'describe' => ''),
            8  => array('title' => '银行卡支付', 'key' => '', 'describe' => ''),
            9  => array('title' => '领取赠品', 'key' => '', 'describe' => ''),
            10 => array('title' => '优惠券码', 'key' => '', 'describe' => ''),
            11 => array('title' => '合并付货款', 'key' => '', 'describe' => '')
        );
    }
    
    /**
     * 获取结算方式
     * @param int $id
     * @return string 方式值
     */
    static public function getById($id = -1, $onlyTitle = true){
        $list = self::getAll();
        return $id > - 1 ? ($onlyTitle ? $list[$id]['title'] : $list[$id]) : $list;
    }
}

/**
 * 结算方式
 * @author lanxuebao
 *
 */
class SettlementType{
    /**不结算*/
    const NONE = 0;
    /**手动结算*/
    const MANUAL = 1;
    /**付款后*/
    const AFTER_PAY = 2;
    /**发货后*/
    const AFTER_SEND_GOODS = 3;
    /**确认收货后*/
    const AFTER_CONFIRM_GOODS = 4;
    /**评价后*/
    const AFTER_EVALUATE = 5;
    
    static public function getAll(){
        return array(
            0  => array('title' => '不参与结算', 'key' => 'NONE', 'describe' => ''),
            1  => array('title' => '手动结算', 'key' => 'MANUAL', 'describe' => ''),
            2  => array('title' => '付款后', 'key' => 'AFTER_PAY', 'describe' => ''),
            3  => array('title' => '发货后', 'key' => 'AFTER_SEND_GOODS', 'describe' => ''),
            4  => array('title' => '确认收货后', 'key' => 'AFTER_CONFIRM_GOODS', 'describe' => ''),
            5  => array('title' => '评价后', 'key' => 'AFTER_EVALUATE', 'describe' => '')
        );
    }
    
    /**
     * 获取结算方式
     * @param int $id
     * @return string 方式值
     */
    static public function getById($id = -1, $onlyTitle = true){
        $list = self::getAll();
        return $id > - 1 ? ($onlyTitle ? $list[$id]['title'] : $list[$id]) : $list;
    }
}

/**
 * 交易结束类型
 * @author lanxuebao
 *
 */
class TradeEndType{
    const OTHER = 10;
    /**买家误拍或重拍了*/
    const BUYER_WUPAI = 1;
    /**买家主动取消*/
    const BUYER_CANCEL = 2;
    /**无法联系上买家*/
    const BUYER_LOST = 3;
    /**买家无诚意完成交易*/
    const BUYER_NO_SINCERITY = 4;
    /**已经缺货无法交易*/
    const NO_STOCK = 5;
    /**卖家主动取消*/
    const SELLER_CANCEL = 6;
    /**超时未付款*/
    const PAY_TIMEOUT = 7;
    /**超出限购*/
    const BUYER_QUOTA = 8;
    /**确认收货*/
    const SUCESS = 9;
    /**退款/售后*/
    const REFUND = 10;
    
    
    static public function getAll(){
        return array(
            0  => array('id' => 0, 'title' => '', 'key' => '', 'describe' => ''),
            1  => array('id' => 1, 'title' => '买家误拍或重拍了', 'key' => 'BUYER_WUPAI', 'describe' => ''),
            2  => array('id' => 2, 'title' => '买家主动取消', 'key' => '', 'describe' => ''),
            3  => array('id' => 3, 'title' => '无法联系上买家', 'key' => '', 'describe' => ''),
            4  => array('id' => 4, 'title' => '买家无诚意完成交易', 'key' => '', 'describe' => ''),
            5  => array('id' => 5, 'title' => '已经缺货无法交易', 'key' => '', 'describe' => ''),
            6  => array('id' => 6, 'title' => '卖家主动取消', 'key' => '', 'describe' => ''),
            7  => array('id' => 7, 'title' => '超时未付款', 'key' => '', 'describe' => ''),
            8  => array('id' => 8, 'title' => '超出限购', 'key' => '', 'describe' => ''),
            9  => array('id' => 9, 'title' => '确认收货', 'key' => '', 'describe' => ''),
            10 => array('id' => 10, 'title' => '退款/售后', 'key' => '', 'describe' => '')
        );
    }
    
    /**
     * 获取结算方式
     * @param int $id
     * @return string 方式值
     */
    static public function getById($id = -1, $onlyTitle = true){
        $list = self::getAll();
        return $id > - 1 ? ($onlyTitle ? $list[$id]['title'] : $list[$id]) : $list;
    }
}

/**
 * 退款原因
 */
class RefundReason{
    static public function getAll($is_received){
        $list = array();
        
        // 系统判定没有收到物品时
        if(!$is_received){
            $list[10] = array('title' => '7天无理由退换货', 'key' => '', 'describe' => '');
            $list[12] = array('title' => '误拍/重拍', 'key' => '', 'describe' => '');
            $list[13] = array('title' => '退运费', 'key' => '', 'describe' => '');
            $list[22] = array('title' => '未按约定时间发货', 'key' => '', 'describe' => '');
        }else{// 系统判定已收到物品时
            $list[10] = array('title' => '7天无理由退换货', 'key' => '', 'describe' => '');
            $list[11] = array('title' => '与卖家协商一致', 'key' => '', 'describe' => '');
            $list[12] = array('title' => '误拍/重拍', 'key' => '', 'describe' => '');
            $list[13] = array('title' => '退运费', 'key' => '', 'describe' => '');
            $list[14] = array('title' => '做工问题', 'key' => '', 'describe' => '');
            $list[15] = array('title' => '缩水/褪色', 'key' => '', 'describe' => '');
            $list[16] = array('title' => '大小/尺寸描述不符', 'key' => '', 'describe' => '');
            $list[17] = array('title' => '颜色/图案/款式描述不符', 'key' => '', 'describe' => '');
            $list[18] = array('title' => '材质面料描述不符', 'key' => '', 'describe' => '');
            $list[19] = array('title' => '少件/漏发', 'key' => '', 'describe' => '');
            $list[20] = array('title' => '卖家发错货', 'key' => '', 'describe' => '');
            $list[21] = array('title' => '包装/商品破损/污渍', 'key' => '', 'describe' => '');
            $list[22] = array('title' => '假冒品牌', 'key' => '', 'describe' => '');
            $list[23] = array('title' => '包裹丢失/无物流信息', 'key' => '', 'describe' => '');
        }
        return $list;
    }

    /**
     * 获取结算方式
     * @param int $id
     * @return string 方式值
     */
    static public function getById($id = -1, $onlyTitle = true){
        $list = self::getAll(true);
        return $id > - 1 ? ($onlyTitle ? $list[$id]['title'] : $list[$id]) : $list;
    }
}

/**
 * 资金流水类型
 * @author lanxuebao
 *
 */
class BalanceType{
    /**订单*/
    const PAY_ORDER = 1;
    /**订单退款*/
    const ORDER_REFUND = 2;
    /**首次注册账号*/
    const FIRST_REGISTER = 3;
    /**满减赠送*/
    const MAN_JIAN = 4;
    /**会员卡*/
    const MEMBER_CARD = 5;
    /**升级*/
    const LEVEUP = 6;
    /**活动返现*/
    const BACK_CASH = 7;
    /**订单关闭*/
    const TRADE_CANCEL = 8;
    /**订单关闭/退款佣金扣回*/
    const COMMISION_DEDUCTED = 9;
    /**订单*/
    const TRADE = 10;
    /**手续费*/
    const POUNDAGE = 11;
    /**佣金*/
    const COMMISION = 12;
    /**提现*/
    const TRANSFERS = 13;

    static public function getAll(){
        return array(
            1   => array('title' => '订单支付', 'short' => '订', 'color' => '#00bffe', 'key' => 'BUYER_WUPAI', 'describe' => ''),
            2   => array('title' => '订单退款', 'short' => '退', 'color' => '#00bffe', 'key' => '', 'describe' => ''),
            3   => array('title' => '首次注册账号', 'short' => '注', 'color' => '#9E9E9E', 'key' => '', 'describe' => ''),
            4   => array('title' => '满减赠送', 'short' => '满', 'color' => '#9E9E9E', 'key' => '', 'describe' => ''),
            5   => array('title' => '会员卡赠送', 'short' => '会', 'color' => '#9E9E9E', 'key' => '', 'describe' => ''),
            6   => array('title' => '升级', 'short' => '升', 'color' => '#9E9E9E', 'key' => '', 'describe' => ''),
            7   => array('title' => '活动返现', 'short' => '返', 'color' => '#9E9E9E', 'key' => '', 'describe' => ''),
            8   => array('title' => '订单关闭', 'short' => '订', 'color' => '#00bffe', 'key' => '', 'describe' => ''),
            9   => array('title' => '佣金扣回', 'short' => '佣', 'color' => '#FF5722', 'key' => '', 'describe' => ''),
            10  => array('title' => '订单', 'short' => '订', 'color' => '#00bffe', 'key' => '', 'describe' => ''),
            11  => array('title' => '手续费', 'short' => '费', 'color' => '#00bffe', 'key' => '', 'describe' => ''),
            12  => array('title' => '佣金', 'short' => '佣', 'color' => '#FF5722', 'key' => '', 'describe' => ''),
            13  => array('title' => '提现', 'short' => '提', 'color' => '#FF5722', 'key' => '', 'describe' => ''),
        );
    }
    
    static public function getById($id = -1, $onlyTitle = true){
        $list = self::getAll();
        return $id > - 1 ? ($onlyTitle ? $list[$id]['title'] : $list[$id]) : $list;
    }
}

/**
 * 订单类型
 * @author lanxuebao
 *
 */
class OrderType{
    /**普通*/
    const NORMAL = 0;
    /**预定*/
    const YUDING = 1;
    /**代购*/
    const DAIGOU = 2;
    /**货到付款*/
    const COD = 3;
    /**会员折扣*/
    const MEMBER_DISCOUNT = 4;
    /**会员卡*/
    const MEMBER_CARD = 5;
    /**积分兑换*/
    const SCORE = 6;
    /**赠品*/
    const GIFT = 7;
    /**团购返现*/
    const GROUPON = 101;
    /**零元购*/
    const ZERO = 102;
    /**限时折扣*/
    const DISCOUNT = 103;
}

class ProjectConfig{
    /**会员卡商品id*/
    const CARD_GOODS_ID = 101;
    /**允许强制下单*/
    const ALLOW_FORCE_ORDER = 102;
    /**好评奖励积分*/
    const GOOD_RATE_SCORE = 103;
    /**全局佣金设置*/
    const WHOLE_SHOP_REWARD = 104;
    /**显示评价数量*/
    const SHOW_RATE_NUM = 105;
}

/**
 * 订单类型
 * @author lanxuebao
 *
 */
class TradeType{
    /**普通商品*/
    const NORMAL = 1;
    /**积分兑换*/
    const SCORE = 2;
    /**货到付款*/
    const COD = 3;
    /**赠品*/
    const GIFT = 4;
    /**预定*/
    const YUDING = 5;
    /**代购*/
    const DAIGOU = 6;
    /**会员卡*/
    const MEMBER_CARD = 7;
}

/**
 * 活动类型
 * @author lanxuebao
 *
 */
class ActivityType{
    /**团购返现*/
    const GROUPON = 101;
    /**零元购*/
    const ZERO = 102;
    /**限时折扣*/
    const DISCOUNT = 103;
    /**满减*/
    const MAN_JIAN = 104;
    
    static public function getAll(){
        return array(
            101  => array('type' => 101, 'title' => '团购返现', 'model' => '\H5\Model\GrouponModel', 'main_tag' => '团购返现','key' => '', 'describe' => ''),
            102  => array('type' => 102, 'title' => '零元购',  'model' => '\H5\Model\ZeroModel', 'key' => '', 'main_tag' => '零元购','describe' => ''),
            103  => array('type' => 103, 'title' => '限时折扣', 'model' => '\H5\Model\DiscountModel', 'key' => '', 'main_tag' => '限时折扣','describe' => ''),
            104  => array('type' => 104, 'title' => '满减',   'model' => '', 'key' => '', 'describe' => '', 'main_tag' => '满减'),
        );
    }
    
    static public function getByType($type, $onlyModel = false){
        $list = self::getAll();
        return $onlyModel ? $list[$type]['model'] : $list[$type];
    }
    
    static public function getById($id){
        $type = substr($id, 0, 3);
        $data = self::getByType($type, false);
        if(!$data){
            return;
        }
        $data['id'] = substr($id, 0, -3);
        return $data;
    }
}

/**
 * 模板类型
 * @author Administrator
 *
 */
class MessageType{
    /**会员级别变更提醒*/
    const MEMBER_GRADE_CHANGE = 'TM00891';
    /**退款申请审核结果*/
    const REFUND_RESULT = 'OPENTM202735558';
    /**退款进度*/
    const REFUND = 'OPENTM401479948';
    /**订单发货提醒*/
    const ORDER_SEND_GOODS = 'OPENTM200565259';
    /**新订单通知*/
    const NEW_ORDER = 'OPENTM200750297';
    /**待办事项通知*/
    const WAIT_DO_WORK = 'OPENTM401202033';
    /**资金变动通知*/
    const BALANCE_CHANGE = 'OPENTM207453441';
}

/**
 * 后台提醒
 * Class OrderReminderType
 * @package Common\Model
 */
class OrderReminderType{
    /**下单成功提醒*/
    const NEW_ORDER = 1;
    /**催单提醒*/
    const REMIND = 2;
    /**退款提醒*/
    const REFUND = 3;

    static public function getAll(){
        return array(
            1  => array('type' => 1, 'title' => '下单成功提醒', 'key' => '', 'describe' => ''),
            2  => array('type' => 2, 'title' => '催单提醒', 'key' => '', 'describe' => ''),
            3  => array('type' => 3, 'title' => '退款提醒', 'key' => '', 'describe' => '')
        );
    }
}
?>