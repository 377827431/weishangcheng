<?php
namespace Admin\Model;

use Think\Model;
use Common\Model\BaseModel;

class SyncSubscribe extends BaseModel{
    protected $tableName = "alibaba_trade";
    /**
     * 同步1688订单状态
     */
    public function syncOrder($shopId,$startTid,$endTid=null){
        if($endTids != null && !is_numeric($endTid)){
            E('订单号格式错误');
        }
        if(!is_numeric($startTid)){
            E('订单号格式错误');
        }
        $ali = new \Common\Model\AlibabaModel();
        $shop = $this->query("SELECT aliid FROM shop WHERE id={$shopId}");
        $tokenId = $shop[0]['aliid'];
        $result = $ali->getAliTrade($startTid,$tokenId, $endTids);
        if($result != true){
            E($result);
        }
    }
}
?>