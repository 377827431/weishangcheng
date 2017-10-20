<?php 
namespace Common\Model;

use Think\Model;
class AopModel extends Model{
    protected $tableName = 'alibaba_token';
    protected $aop_oauth;
    protected $aop_api;
    private $appId = 4157308;
    private $appKey = 'kVhHHhfWu1';
    public $loginId = '亲客微商';
    private $memberId = 'b2b-302768012358b41';

    public function __construct($key = ''){
        parent::__construct();
        
        /*
        switch ($key){
            case 'dqhp1981':
                $this->loginId = 'dqhp1981';
                $this->memberId = 'b2b-1109393181';
                break;
            case 'wangxiaozheng9199':
                $this->loginId = 'wangxiaozheng9199';
                $this->memberId = 'b2b-882751043';
                break;
            case '亲客微商':
                $this->loginId = '亲客微商';
                $this->memberId = 'b2b-302768012358b41';
                break;
            case '龙江味道88':
                $this->loginId = '龙江味道88';
                $this->memberId = 'b2b-1711749575';
                break;
            default:
                $this->loginId = '亲客微商';
                $this->memberId = 'b2b-302768012358b41';
                break;
        }
        */
        
        vendor('AopSDK.AopSdk');
        $this->aop_api = new \AOPAPI();
    }
    
    // 获取买家订单
    public function getBuyerTrade($tid)
    {
        $this->aop_api->version = 2;
        $token = $this->getAccessToken();
        $getTradeOrderList = new \TradeOrderListGet($token);
        $getTradeOrderList->buyerMemberId($this->memberId);
        $getTradeOrderList->orderIdSet($tid);
        $result = $this->aop_api->api($getTradeOrderList);

        return $result;
    }
    
    /**
     * 根据1688订单号获取阿里订单信息
     * @param unknown $tid
     * @return mixed
     */
    public function getTradeListByTid($tid){
        if(is_array($tid)){
            $tid = implode(',', $tid);
        }

        $this->aop_api->version = 2;
        $token = $this->getAccessToken();
        $getTradeOrderList = new \TradeOrderListGet($token);
        $getTradeOrderList->buyerMemberId($this->memberId);
        $getTradeOrderList->orderIdSet('['.$tid.']');
        $result = $this->aop_api->api($getTradeOrderList);
        
        return $result['orderListResult']['modelList'];
    }

    /*
    * 获取1688授权剩余天数
    */
    public function getAccessTime ($loingId = null){
        if(empty($loingId)){
            $loingId = $this->loginId;
        }
        
        $data = $this->find($loingId);
        if(empty($data)){
            return null;
        }
        
        $data['refresh_day'] = floor((strtotime($data['refresh_token_timeout']) - NOW_TIME)/86400);
        return $data;
    }
    
    /**
     * 数据库中存取token信息
     */
    public function aliOAuth(){
        $aop_oauth = $this->aop_api->aop_oauth;
        $param = array(
            'redirect_uri'  => $_SERVER['HTTP_HOST'].'/admin/index/setToken',
            'state' => 'wslm'
        );
        $aop_oauth->setParam($param);
        $aop_oauth->doOAuth();
        die;
    }
    
    
    /**
     * 数据库中存取token信息
     */
    public function setToken($code){
        $result = $this->aop_api->getToken($code);
        if($result->resource_owner != $this->loginId){
            $this->error = '授权店铺与预期授权店铺不一致';
            return -1;
        }

        $data = array(
            'ali_id' => $result->aliId,
            'access_token' => $result->access_token,
            'expires_in' => NOW_TIME+$result->expires_in - 600,
            'refresh_token' => $result->refresh_token,
            'login_id' => $result->resource_owner,
            'member_id' => $result->memberId,
            'refresh_token_timeout' => substr($result->refresh_token_timeout,0,14)
        );
        
        return $this->add($data, null, true);
    }
    
    /**
     * 数据库中存取token信息
     */
    public function getAccessToken(){
        $data = $this->find($this->loginId);
        if(empty($data)){
            E('店铺TOKEN不存在：'.$this->loginId);
        }else if(NOW_TIME < $data['expires_in']){
            return $data['access_token'];
        }
        
        $result = $this->aop_api->refreshToken($data['refresh_token']);
        
        $sql = "UPDATE {$this->tableName} SET 
                    access_token='{$result->access_token}',
                    expires_in=".(NOW_TIME + $result->expires_in - 600)."
                WHERE login_id='{$this->loginId}'";
        
        $this->execute($sql);
        return $result->access_token;
    }

    /**
     * 获取微供供应商列表
     *
     */
    public function getSupplier($format = true){
        $token = $this->getAccessToken();
        $getSupplierNames = new \AlibabaChinaMicrosupplyOpenGetSupplierNames($token);
        $result = $this->aop_api->api($getSupplierNames,$format);
        
        if($result['success'] != 1){
            $this->error = $result['msgInfo'];
            return null;
        }else if(empty($result['model'])){
            $this->error = '无关联店铺';
            return $result['model'];
        }else{
            return $result['model'];
        }
        
        return $result;
    }

    /**
     * 根据指定供应商获取产品信息
     * @param  String $name  供应商账户名
     * @param  int $offset  起始页
     * @param  int $limit  每页数量
     *
     */
    public function getGoodsList($name,$offset=0,$limit = 10,$format = true){
        $token = $this->getAccessToken(); 
        $getOfferList = new \AlibabaChinaMicrosupplyOpenGetOfferList($token);
        $getOfferList->setMarketSupplierLoginId($name);
        $getOfferList->setOffset($offset);
        $getOfferList->setLimit($limit);
        $result = $this->aop_api->api($getOfferList,$format);
        
        if($result['success'] != 1){
            $this->error = $result['msgInfo'];
            return null;
        }else if(empty($result['model'])){
            $this->error = '此店铺无产品';
            return $result['model'];
        }else{
            return $result['model'];
        }
        return $result;
    }

    /**
     * 获取中文站地址库接口
     *
     */
    public function getAddress($format = true){
        $token = $this->getAccessToken(); 
        $getChinaAddress = new \AlibabaChinaMicrosupplyOpenGetChinaAddress($token);
        $result = $this->aop_api->api($getChinaAddress,$format);
        return $result;
    }

    /**
     * (old)获取商品信息
     * @param  Long $productid  商品ID
     *
     */
    public function getOff($productid,$format = true){
        $token = $this->getAccessToken();
        $OffGet = new \OffGet($token);
        $OffGet->setOfferId($productid);
        $OffGet->setReturnFields();
        $result = $this->aop_api->api($OffGet,$format);
        if(empty($result['result'])){
            $this->error = '未知错误';
            return;
        }else if(!empty($result['result']['errCode'])){
            $this->error = $result['result']['errMsg'];
            return;
        }
        return $result;
    }

    /**
     * 获取商品信息
     * @param  Long $productid  商品ID
     *
     */
    public function getProduct($productid){
        $token = $this->getAccessToken();
        $ProductGet = new \AlibabaProductGet($token);
        $ProductGet->setProductID($productid);
        $ProductGet->setWebSite('1688');
        $result = $this->aop_api->api($ProductGet, true);
        if(empty($result['result'])){
            $this->error = '未知错误';
            return;
        }else if(!empty($result['result']['errCode'])){
            $this->error = $result['result']['errMsg'];
            return;
        }
        return $result['result'];
    }

    /**
     * 获取商品9图
     * @param  Long $productid  商品ID
     *
     */
    public function getProductImg($productid,$format = true){
        $token = $this->getAccessToken();
        $images = new \AlibabaChinaMicrosupplyOpenGetOfferImages($token);
        $images->setOfferId($productid);
        $result = $this->aop_api->api($images,$format);
        return $result;
    }
    
    /**
     * 获取商品信息
     *
     */
    public function getProductInfo($productId, $format = true){
        $token = $this->getAccessToken();
        $offGet = new \OffGet($token);
        $offGet->setOfferId($productId);
        
        $fields  = 'offerId,isPrivate,privateProperties,detailsUrl,type,tradeType,offerStatus,subject,details,qualityLevel,imageList,productFeatureList';
        $fields .= ',isOfferSupportOnlineTrade,isSupportMix,unit,priceUnit,amount,amountOnSale,saledCount,retailPrice,unitPrice';
        $fields .= ',priceRanges,termOfferProcess,freightTemplateId,productUnitWeight,freightType,isSkuOffer,isSkuTradeSupported,skuArray,gmtCreate,gmtModified,gmtExpire';
        $offGet->setReturnFields($fields);
        $result = $this->aop_api->api($offGet, $format);
        
        if($result['result']['success'] != 1){
            $this->error = '获取商品信息失败';
            return;
        }else{
            return $result['result']['toReturn'][0];
        }
        return $result['result'];
    }

    /**
     * 获取运费模板列表
     *
     */
    public function getFreightTemplate($format = true)
    {
        $token = $this->getAccessToken();  
        $TemplateGet = new \AlibabaLogisticsFreightTemplateGetList($token);
        $TemplateGet->setWebSite('1688');
        $result = $this->aop_api->api($TemplateGet,$format);
        return $result;
    }

    /**
     * 微供下单
     * @param  Array $orderinput  订单信息
     *         String supplierLoginId 供应商名称
     *         String senderInfo 代理商信息
     *         Double totalFreightFee 总运费
     *         Double totalProductPrice 商品总价，不含运费
     *
     * @param  Array $BuyItem 商品信息
     *         String type 类型，判定是SKU还是无SKU  示例（SKU）
     *         String specId sku的1688标识
     *         Long skuId 与淘宝打通后的sku id
     *         Double price 商品单价
     *         Long amount 下单数量
     *         String unit 单位  示例（个）
     *
     * @param  Array $offerviewitem  offerId和运费模板
     *         Long offerId 
     *         Long freightId 运费模板
     *         
     * @param  Array $AddressInfo 收件人信息
     *         String personalName 收件人姓名
     *         String mobileNO 收件人手机号码
     *         String province 省份          示例（浙江省）
     *         String city 城市              示例（杭州市）
     *         String district 区域          示例（滨江区）
     *         Long cityCode 城市编码
     *         Long districtCode 区域编码
     *         Long areaCode 地区码
     *         String addressDetail 详细地址
     */
    public function openOrder($orderinput,$format = true)
    {
        $token = $this->getAccessToken();  
        $MicrosupplyOpenOrder = new \AlibabaChinaMicrosupplyOpenOrder($token);
        $MicrosupplyOpenOrder->setMicroSupplyMakeOrderInputModels($orderinput);
        $result = $this->aop_api->api($MicrosupplyOpenOrder,$format);
        return $result;
    }

    /**
     * 查询订单
     * @param  Long $orderid  订单ID号
     *
     */
    public function getOrder($orderid,$format = true)
    {
        $token = $this->getAccessToken();  
        $OrderDetail = new \TradeOrderOrderDetailGet($token);
        $OrderDetail->setOrderId($orderid);
        $result = $this->aop_api->api($OrderDetail,$format);
        return $result;
    }
    
    /**
     *
     */
    public function freightFee($param,$format = true)
    {
        $token = $this->getAccessToken();  
        $FreightFee = new \AlibabaChinaMicrosupplyOpenCalculateFreightFee($token);
        $FreightFee->setCityCode($param['cityCode']);
        $FreightFee->setDistrictCode($param['districtCode']);
        $FreightFee->setOrderInputModel(json_encode($param['orderInputModel']));
        $result = $this->aop_api->api($FreightFee,$format);
        return $result;
    }
    
    public function getAgentProduct($productId){
        $token = $this->getAccessToken();
        $ProductGet = new \AlibabaAgentProductGet($token);
        $ProductGet->setProductID($productId);
        $ProductGet->setWebSite('1688');
        $result = $this->aop_api->api($ProductGet, true);
        return $result;
    }
    
    /**
     * 大市场普通订单创建前预览
     * 多订单的预先浏览展示，在创建订单前可以调用该API计算价格，物流费用等
     */
    public function tradeGeneralPreorder($goods, $address){
        $token = $this->getAccessToken();
        $Model = new \AlibabaTradeGeneralPreorder($token);
        $Model->setGoods($goods);
        $Model->setReceiveAddress($address);
        
        $result = $this->aop_api->api($Model, true);
        if(!empty($result['errorCode'])){
            $this->error = $result['errorMessage'];
            return;
        }
        return $result['result'];
    }
    
    /**
     * 大市场普通订单下单
     * @return void|mixed
     */
    public function tradeGeneralCreateOrder(){
        $token = $this->getAccessToken();
        $Model = new \AlibabaTradeGeneralCreateOrder($token);
        
        $Model->setCargoGroups($goods);
        $Model->setOtherInfoGroup($info);
        $Model->setReceiveAddress($address);
        
        $result = $this->aop_api->api($Model, true);
        if(!empty($result['errorCode'])){
            $this->error = $result['errorMessage'];
            return;
        }
        return $result['result'];
    }
}
?>