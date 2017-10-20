<?php
namespace Common\Service;

interface GoodsViewService{
    /**
     * 商品实体
     * @param Goods $goods
     * @param Member $buyer
     */
    public function parseDetail($goods, $buyer, $qiangzhi = false);

    /**
     * 设置按钮
     * @param Goods $goods
     * @param Member $buyer
     */
    public function getAction($goods, $buyer, $active);
    
    public function parseGoodsList($buyer, $list);
    
    public function unsetActivity($goodsList);
    
    public function search($params, $buyer);
}
?>