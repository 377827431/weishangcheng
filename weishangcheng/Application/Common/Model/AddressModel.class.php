<?php 
namespace Common\Model;

class AddressModel extends BaseModel{
    protected $tableName = 'member_address';
    protected $pk = 'receiver_id';
    private $cityList = null;
    
    public function getCityList(){
        if(is_null($this->cityList)){
	       $this->cityList = include COMMON_PATH.'Conf/city.php';
        }
        return $this->cityList;
    }
    
    public function getCityName($id){
        $list = $this->getCityList();
        return $list[$id]['name'];
    }
    
    /**
     * 获取收货地址
     * @param unknown $mid
     * @return \Think\mixed
     */
    public function getDefault($mid){
        $address = $this->field("receiver_id, receiver_name, receiver_mobile, province_code, city_code, county_code, receiver_zip, receiver_detail")->where("mid=%d", $mid)->order("is_default DESC")->find();
    
        if(!empty($address)){
            $address['receiver_province'] = $this->getCityName($address['province_code']);
            $address['receiver_city'] = $this->getCityName($address['city_code']);
            $address['receiver_county'] = $this->getCityName($address['county_code']);
            $address['receiver_zip'] = $address['receiver_zip'] == 0 ? '' : $address['receiver_zip'];
        }
    
        return $address;
    }
    
    public function toarray(){
        $model = M('city');
        $list = $model->order("id")->select();
    
        foreach($list as $item){
            echo "'{$item['code']}'=>array('name'=>'{$item['name']}','sname'=>'{$item['short_name']}','pcode'=>{$item['pcode']},'level'=>{$item['level']},'pinyin'=>'{$item['pinyin']}'),<br>";
        }
    }
    
    public function tojs(){
        $list = include COMMON_PATH.'/Model/city.php';
         
        $result = array();
        foreach($list as $code=>$item){
            $result[$item['pcode']][$code] = $item;
        }
    
        print_data(json_encode($result, JSON_UNESCAPED_UNICODE));
    }
    
    public function getAll($mid){
        $list = $this
                ->field("receiver_id, receiver_name, receiver_mobile, province_code, city_code, county_code, receiver_zip, receiver_detail")
                ->where("mid='{$mid}'")
                ->order("is_default DESC, receiver_id DESC")
                ->select();
        
        foreach($list as $index=>$item){
            if($index > 19){
                $this->delete($item['receiver_id']);
                continue;
            }
            $list[$index]['receiver_province'] = $this->getCityName($item['province_code']);
            $list[$index]['receiver_city'] = $this->getCityName($item['city_code']);
            $list[$index]['receiver_county'] = $this->getCityName($item['county_code']);
            $list[$index]['receiver_zip'] = $item['receiver_zip'] == 0 ? '' : $item['receiver_zip'];
        }
        
        return $list;
    }
    
    /**
     * 保存
     * @param unknown $data
     */
    public function modify($data){
        if(is_numeric($data['receiver_id'])){
            $where = "receiver_id='{$data['receiver_id']}' AND mid='{$data['mid']}'";
            $this->where($where)->save($data);
        }else{
            unset($data['receiver_id']);
            $data['receiver_id'] = $this->add($data);
        }
        
        $data['receiver_province'] = $this->getCityName($data['province_code']);
        $data['receiver_city'] = $this->getCityName($data['city_code']);
        $data['receiver_county'] = $this->getCityName($data['county_code']);
        return $data;
    }
}
?>