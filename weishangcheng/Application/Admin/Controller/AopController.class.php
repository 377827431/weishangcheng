<?php
namespace Admin\Controller;

use Common\Common\CommonController;
/**
 * 临时测试类
 *
 * @author lanxuebao
 */
class AopController extends CommonController
{
    public function index(){
        vendor('AopSDK.AopSdk');
        $aop_oauth = new \AOPOAuth();
        
        if ( isset($_GET['code']) )
        {
            $aop_api = new \AOPAPI($aop_oauth);
            #解析
            $result = $aop_api->getToken($_GET['code']);
            var_dump(json_encode($result));die;
        }
        
        $param = array(
            'redirect_uri'	=> 'http://wslm.xingyebao.me/aop',
            'state'	=> 'test',
        );
        $aop_oauth->setParam($param);
        $aop_oauth->doOAuth();
    }
    
    public function refresh()
    {
        vendor('AopSDK.AopSdk');
        $aop_oauth = new \AOPOAuth();
        $param = $aop_oauth->param;
        
        $result = '{"refresh_token_timeout":"20170126094556000+0800","aliId":"1711749575","resource_owner":"\u9f99\u6c5f\u5473\u905388","memberId":"b2b-1711749575","expires_in":"36000","refresh_token":"527bc519-b359-4dd2-9792-225008f6c58a","access_token":"81dfefd1-7bf0-46a3-8a7b-a9b53a21b292"}';
        $token = json_decode($result);
        
        $aop_api = new \AOPAPI($aop_oauth);
        $result = $aop_api->refreshToken($token);
        var_dump($result);die;
    }
    
    public function getSupplier()
    {
        $token = '47b7aaf9-fcec-4fdb-ab90-fef7bd4a3bd3';
        vendor('AopSDK.AopSdk');
        $aop_oauth = new \AOPOAuth();
        $aop_api = new \AOPAPI($aop_oauth);
        
        $getSupplierNames = new \AlibabaChinaMicrosupplyOpenGetSupplierNames($token);
        $result = $aop_api->api($getSupplierNames);
        var_dump($result);die;
    }
    
    public function getGoodsList()
    {
        $token = '47b7aaf9-fcec-4fdb-ab90-fef7bd4a3bd3';
        vendor('AopSDK.AopSdk');
        $aop_oauth = new \AOPOAuth();
        $aop_api = new \AOPAPI($aop_oauth);
        
        $getOfferList = new \AlibabaChinaMicrosupplyOpenGetOfferList($token);
        $getOfferList->setMarketSupplierLoginId('陕西天之源酒业');
        $getOfferList->setOffset(0);
        $getOfferList->setLimit(10);
        $result = $aop_api->api($getOfferList);
        var_dump($result);die;
    }
}
?>