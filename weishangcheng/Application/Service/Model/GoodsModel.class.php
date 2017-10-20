<?php
namespace Service\Model;

/**
 * 网店管家 商品模型
 */
class GoodsModel extends \Common\Model\BaseModel
{
    protected $tableName = 'mall_goods';
    function __construct(){
        parent::__construct();
    }
    
    /**
     * 获取单笔退款及交易信息
     * @param unknown $refundId
     */
    public function getGoods($param){
        $fields = array(
            'g.id' => 'gid',#ItemID
            'g.title' => 'gtitle',#ItemName
            'g.stock' => 'gstock',#Num
            'g.outer_id' => 'gouter_id',#OuterID
            'g.price' => 'gprice',#Price
            
            'p.id' => 'pid',#SkuID
            'p.sku_json' => 'psku_json',#Unit
            'p.score' => 'pscore',#Num
            'p.outer_id' => 'pouter_id',#SkuOuterID
            'p.price' => 'pprice'#SkuPrice
            # IsSku 是否存在多个SKU
        );
        $this->alias('g')->field($fields)
            ->join('LEFT JOIN mall_product AS p ON g.id = p.goods_id');
        $data = $this->where($param)->select();
        $result = array(
            'TotalCount' => 0,
            'Result' => 1,
            'Cause' => null,
        );
        foreach( $data as $k => $v )
        {
            if ( isset($result[$v['gid']]) )
            {
                $result[$v['gid']]['Ware']['IsSku'] = '1';
                $result[$v['gid']]['Ware'][] = array(
                    'items' => array(
                        'SkuID' => $v['pid'].'',
                        'Unit' => $this->getSpec($v['psku_json']).'',
                        'Num' => $v['pscore'].'',
                        'SkuOuterID' => $v['pouter_id'].'',
                        'SkuPrice' => $v['pprice'].'',
                    ),
                );
            }else{
                $result[$v['gid']]['Ware']['ItemID'] = $v['gid'].'';
                $result[$v['gid']]['Ware']['ItemName'] = $v['gtitle'].'';
                $result[$v['gid']]['Ware']['Num'] = $v['gstock'].'';
                $result[$v['gid']]['Ware']['OuterID'] = $v['gouter_id'].'';
                $result[$v['gid']]['Ware']['Price'] = $v['gprice'].'';
                $result[$v['gid']]['Ware']['IsSku'] = '0';
                $result[$v['gid']]['Ware'][] = array(
                    'items' => array(
                        'SkuID' => $v['pid'].'',
                        'Unit' => $this->getSpec($v['psku_json']).'',
                        'Num' => $v['pscore'].'',
                        'SkuOuterID' => $v['pouter_id'].'',
                        'SkuPrice' => $v['pprice'].'',
                    ),
                );
                $result['TotalCount']++;
            }
        }
        return $result;
    }
    
    /**
     * 转换spec名字
     * @param unknown $skuJson
     * @return string
     */
    public function getSpec($skuJson){
        if(empty($skuJson)){
            return '';
        }else if(!is_array($skuJson)){
            $skuJson = json_decode($skuJson, true);
        }
    
        $sku_name = '';
        foreach($skuJson as $sku){
            $sku_name .= $sku['v'].' ';
        }
        return rtrim($sku_name);
    }
}
?>