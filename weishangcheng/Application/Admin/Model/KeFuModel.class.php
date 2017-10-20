<?php 
namespace Admin\Model;

use Common\Model\BaseModel;
use Org\Wechat\WechatAuth;
/**
 * @author lanxuebao
 *
 */
class KeFuModel extends BaseModel{
    protected $tableName = 'kefu_list';
    
    /**
     * 获取所有
     * @param array $where
     * @param string $kv
     * @return string[]|unknown
     */
    public function getAll($where = array(), $groups = false){
        $types = \Common\Model\StaticModel::getCustomerServiceType();
        
        if($where === true){
            $groups = true;
        }else if(!empty($where)){
            $this->where($where);
        }
        
        $groupList = array();
        $list = $this->order('type')->select();
        foreach($list as &$item){
            $item['type_str'] = $types[$item['type']];
            $item['work'] = $item['work_start'].' ~ '.$item['work_end'];
            $item['avg_score']=bcdiv($item['score'], $item['amount'],1);
            
            if($groups){
                $groupList[$item['type_str']][$item['id']] = $item;
            }
        }
        
        return $groups ? $groupList : $list;
    }
    
    /**
     * 根据id获取
     * @param unknown $id
     */
    public function getById($id){
        if(!is_numeric($id)){
            $this->error = 'id不能为空';
            return;
        }
        
        $data = $this->find($id);
        if(empty($data)){
            $this->error = 'id不存在';
            return;
        }
        $data['shop_id'] = explode(',', $data['shop_id']);
        return $data;
    }
    
    /**
     * 根据id删除
     * @param unknown $id
     * @return number
     */
    public function deleteById($id){
        if(empty($id)){
            $this->error = 'id不能为空';
            return -1;
        }
        
        $result = $this->delete($id);
        if($result > 0){
            $this->execute("DELETE FROM kefu_goods WHERE kf_id IN ({$id})");
        }
    }
    
    /**
     * 保存
     * {@inheritDoc}
     * @see \Think\Model::save()
     */
    public function save($data){
        if(count($data['shop_id']) > 15){
            $this->error = '最多可选15个店铺';
            return -1;
        }
        $data['shop_id'] = count($data['shop_id']) > 0 ? implode(',', $data['shop_id']) : '';
        
        if(is_numeric($data['id'])){
            parent::save($data);
        }else{
            $data['created'] = date("Y-m-d H:i:s");
            $data['id'] = $this->add($data);
        }
        
        // 生成二维码
        $appid = C('KEFU.appid');
        $wechatAuth = new WechatAuth($appid);
        $result = $wechatAuth->qrcodeCreate('kefu-'.$data['id']);
        if(isset($result['errcode'])){
            $this->error = '生成二维码失败：'.$result['errmsg'];
            return 0;
        }
        $this->execute("UPDATE {$this->tableName} SET ticket='{$result['ticket']}' WHERE id=".$data['id']);
        
        return 1;
    }
    
    /**
     * 保存咨询客服
     */
    public function saveGoods($goods, $list){
        $sql = "INSERT IGNORE INTO kefu_goods(goods_id, kf_id) VALUES";
        if(is_numeric($goods)){
            // 单品应用此配置
            $this->execute("DELETE from kefu_goods WHERE goods_id=".$goods);
            if(!empty($list)){
                $list = explode(',', $list);
                foreach($list as $sid){
                    if(!is_numeric($sid)){
                        E('客服id异常');
                    }
                    
                    $sql .= "({$goods}, {$sid}),";
                }
                $this->execute(rtrim($sql, ','));
            }
            
            return;
        }else if(empty($list)){
            return;
        }
        
        $goods = explode(',', $goods);
        $list = explode(',', $list);
        foreach ($goods as $gid){
            if(!is_numeric($gid)){
                E('商品id异常');
            }
            
            foreach($list as $sid){
                if(!is_numeric($sid)){
                    E('客服id异常');
                }
                
                $sql .= "({$gid}, {$sid}),";
            }
        }
        
        $this->execute(rtrim($sql, ','));
        return;
    }
    
    /**
     * 获取商品指定的客服
     * @param int $goodsId
     */
    public function getGoodsKF($goodsId){
        return $this->query("SELECT kefu_id FROM kefu_goods WHERE goods_id=".$goodsId);
    }
}
?>