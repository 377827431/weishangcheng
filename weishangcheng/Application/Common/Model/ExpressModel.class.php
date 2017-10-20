<?php 
namespace Common\Model;

class ExpressModel extends BaseModel{
    protected $tableName = 'template_freight';
    
    public function getAllExpress(){
        return include COMMON_PATH.'Conf/express.php';
    }
    
    /*
    * 是否包邮（判断首重里有0的就算）
    */
    public function getRangeFee($id, $minWeight, $maxWeight, $quantity = 1){
        $result = array('min' => 0, 'max' => 0, 'msg' => '包邮', 'baoyou' => false);
        if($id == 'T1' || $id == 'BY'){
            $result['baoyou'] = true;
            return $result;
        }else if(strpos($id, 'T') === 0){
            $result['msg'] = '订单页查看';
            return $result;
        }
        
        $result['min'] = 999999999;
        $t = $this->find($id);
        
        $templates = json_decode($t['config'], true);
        foreach($templates as $template){
            $weight = $template['type'] == 1 ? $quantity : bcmul($minWeight, $quantity, 2);
            $data = $this->getFee($template, $weight);
            if($data['min'] < $result['min']){
                $result['min'] = $data['min'];
            }
            if($data['max'] > $result['max']){
                $result['max'] = $data['max'];
            }
            
            if($maxWeight > $minWeight){
                $weight = $template['type'] == 1 ? $quantity : bcmul($maxWeight, $quantity, 2);
                $data = $this->getFee($template, $weight);
                if($data['min'] < $result['min']){
                    $result['min'] = $data['min'];
                }
                if($data['max'] > $result['max']){
                    $result['max'] = $data['max'];
                }
            }
        }
        
        $result['baoyou'] = $result['min'] == 0;
        if($result['max'] == 0){
            $result['msg'] = '包邮';
        }else if($result['min'] < $result['max']){
            $result['msg'] = $result['min'].' - '.$result['max'].'元';
        }else{
            $result['msg'] = $result['max'].'元';
        }
        
        return $result;
    }
    
    private function getFee($template, $weight){
        $result = array('min' => 999999999, 'max' => 0, 'msg' => '包邮', 'baoyou' => false);
        
        $data = $template['default'];
        $money = $data['postage'];
        if($weight > $data['start']){
            $temp = bcsub($weight, $data['start'], 2);
            $temp = bcdiv($temp, $data['plus'], 2);
            $plus = bcmul($data['postage_plus'], ceil($temp), 2);
            $money = bcadd($money, $plus, 2);
        }
        
        if($money < $result['min']){$result['min'] = $money;}
        if($money > $result['max']){$result['max'] = $money;}
        
        foreach($template['specials'] as $data){
            $money2 = $data['postage'];
            if($weight > $data['start']){
                $temp = bcsub($weight, $data['start'], 2);
                $temp = bcdiv($temp, $data['plus'], 2);
                $plus = bcmul($data['postage_plus'], ceil($temp), 2);
                $money2 = bcadd($money2, $plus, 2);
            }
            
            if($money2 < $result['min']){$result['min'] = $money2;}
            if($money2 > $result['max']){$result['max'] = $money2;}
        }

        $result['min'] = floatval($result['min']);
        $result['max'] = floatval($result['max']);
        return $result;
    }
    
    /**
     * 获取某店铺的运费模板
     * @param unknown $shopId
     */
    public function getShopFreightTemplates($shopId){
        $list = $this->where("shop_id='{$shopId}'")->select();
        
        // 快递公司
        $expressList = include COMMON_PATH.'Conf/express.php';
        $cityModel = new \Common\Model\CityModel();
        $types = array(array('重', '公斤'), array('件', '件'));
        foreach ($list as $i=>$item){
            $templates = json_decode($item['config'], true);
            unset($list[$i]['config']);
            $describe = '';
            foreach ($templates as $ti=>$template){
                $describe .= '<div'.($ti>0 ? ' style="border-top: 1px dashed #e5e5e5;"' : '').'>';
        
                $expressName = '';
                foreach ($template['express'] AS $expressId){
                    $expressName .= '、'.$expressList[$expressId]['name'];
                }
                
                $type = $types[$item['type']];
                $describe .= '<span class="">'.ltrim($expressName, '、').'</span>：';
                $data = $template['default'];
                $describe .= "默认满{$data['payment']}元首{$type[0]}{$data['start']}{$type[1]}以内{$data['postage']}元";
                $describe .= "，每续{$type[0]}{$data['plus']}{$type[1]}增加{$data['postage_plus']}元；";
        
                // 指定地区
                foreach($template['specials'] as $data){
                    $describe .= '<br>'.implode('、', $data['areas']).'：';
                    $describe .= "满{$data['payment']}元首{$type[0]}{$data['start']}{$type[1]}首费{$data['postage']}元";
                    $describe .= "，每续{$type[0]}{$data['plus']}{$type[1]}增加{$data['postage_plus']}元；";
                }
                
                $describe .= '</div>';
            }
        
            $list[$i]['describe'] = $describe;
        }
        return $list;
    }
}
?>