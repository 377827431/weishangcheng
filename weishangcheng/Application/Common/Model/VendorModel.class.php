<?php 
namespace Common\Model;

use Think\Model;
/**
 * 资金流水modal
 * @author lanxuebao
 *
 */
class VendorModel extends Model{
    protected $tableName = 'vendor';
    
    public function getAll(){
        $where = array();
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $data = array('total' => 0,'rows' => array());
        if($_GET['nick'] != '')
            $where['nick'] = array('like','%'.$_GET['nick'].'%');
        if($_GET['mobile'] != '')
            $where['mobile'] = array('like','%'.$_GET['mobile'].'%');
        if($_GET['tag'] != '')
            $where['tag'] = array('like','%'.$_GET['tag'].'%');
        if($_GET['email'] != '')
            $where['email'] = array('like','%'.$_GET['email'].'%');
        
        $data['total'] = $this->where($where)->count();
        if($data['total'] == 0){
            return $data;
        }
        
        $data['rows'] = $this->where($where)->order('created DESC')->limit($offset, $limit)->select();
        
        foreach($data['rows'] as $k=>$v){
            $data['rows'][$k]['product_desc'] = $this->getPorductDesc($v['product']);
        }
        
        return $data;
    }
    
    public function getOne($id){
        $data = $this->find($id);
        
        if(empty($data)){
            $this->error = '暂无数据！';
            return -1;
        }
        
        $data['brand_desc'] = $data['brand'] == 1 ? '自由品牌' : '代理品牌';
        $data['forward_desc'] = $data['forward'] == 1 ? '可一键转发' : '不可一键转发';
        $data['company_desc'] = $data['company'] == 1 ? '10人以内' : ($data['company'] == 2 ? '10-50人' : ($data['company'] == 3 ? '50-200人' : '200人以上'));
        $data['turnover_desc'] = $data['turnover'] == 1 ? '120万以下' : ($data['company'] == 2 ? '120-500万' : ($data['company'] == 3 ? '500-1000万' : '1000万以上'));
        $data['product_desc'] = json_decode($data['product'],true);
        
        return $data;
    }
    
    public function getPorductDesc($skuJson){
        if(empty($skuJson) || $skuJson == '[]'){
            return '';
        }else if(!is_array($skuJson)){
            $skuJson = json_decode($skuJson, true);
        }
        $product_desc = '';
        foreach($skuJson as $sku){
            if (!preg_match("/^(http|ftp):/", $sku['url'])) {
                $sku['url'] = C('PROTOCOL').$sku['url'];
            }
            
            $product_desc .= '<a href="'.$sku['url'].'" target="_blank">'.$sku['title'].'</a>&nbsp;&nbsp;&nbsp;零售价：'.sprintf('%.2f',$sku['price'])."元<br/>";
        }
        return rtrim($product_desc,"<br/>");
    }
    
}
?>