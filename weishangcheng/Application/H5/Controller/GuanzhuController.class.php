<?php
namespace H5\Controller;
use Common\Common\CommonController;
use Common\Model\QrcodeModel;

/**
 * 扫码关注页面
 * @author 王宝福
 *
 */
class GuanzhuController extends CommonController
{
    /**
     * 商品码关注
     * 文件common/behaviors/appbeginbehavior中调用
     */
    public function index(){
        $qrimage = C('WEIXIN.qrcode');
        if(is_numeric($_GET['goods_id'])){
            // 获取商品主图
            $goods = M('mall_goods')->find($_GET['goods_id']);
            if(!empty($goods)){
                $this->assign('goods', $goods);
                
                $QrModel = new QrcodeModel();
                $qrcode = $QrModel->getTicket(QrcodeModel::GOODS, $_GET['goods_id']);
                
                if(!empty($qrcode)){
                    $qrimage = $qrcode['url'];
                }
            }
        }
        
        $this->assign('qrimage', $qrimage);
        $this->display();
    }
}
?>