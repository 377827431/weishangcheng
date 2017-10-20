<?php 
namespace Common\Model;

class CityModel{
    private $list;
    
    public function sync(){
        $values = "";
    
        set_time_limit(0);
        $Model = M('city');
        $_list = $Model->query("select p.name AS pname, city.name, city.id,city.code, city.pcode from city
                left join city as p on p.id=city.pcode
                where city.`level`=2 and city.`code` not in (
                select pcode from city where `level`=3
                )");
    
        //print_data($_list);
        foreach($_list as $item){
            //$str = "callback({success:true,result:[['469006100','万城镇','469006','wan cheng zhen'],['469006101','龙滚镇','469006','long gun zhen'],['469006102','和乐镇','469006','he le zhen'],['469006103','后安镇','469006','hou an zhen'],['469006104','大茂镇','469006','da mao zhen'],['469006105','东澳镇','469006','dong ao zhen'],['469006106','礼纪镇','469006','li ji zhen'],['469006107','长丰镇','469006','zhang feng zhen'],['469006108','山根镇','469006','shan gen zhen'],['469006109','北大镇','469006','bei da zhen'],['469006110','南桥镇','469006','nan qiao zhen'],['469006111','三更罗镇','469006','san geng luo zhen'],['469006400','国营东兴农场','469006','guo ying dong xing nong chang'],['469006401','国营东和农场','469006','guo ying dong he nong chang'],['469006404','国营新中农场','469006','guo ying xin zhong nong chang'],['469006500','兴隆华侨农场','469006','xing long hua qiao nong chang'],['469006501','地方国营六连林场','469006','di fang guo ying liu lian lin chang']]});";
            $url = 'https://lsp.wuliu.taobao.com/locationservice/addr/output_address_town_array.do?l1='.$item['pcode'].'&l2='.$item['code'].'&l3=';
            $result = http_request($url);
            $str = substr($result, 32);
            $str = rtrim($str, '});');
            $str = str_replace('\'', '"', $str);
            $list = json_decode($str, true);
    
            if(empty($list)){
                continue;
            }
    
            foreach($list as $county){
                if($county[2] != $item['code'] && !in_array($item['code'], array(441900, 442000, 460200, 620200, 820100))){
                    echo "<pre>";
                    print_r($item);
                    echo '<hr>';
                    print_r($county);
                    echo '<hr>';
                    print_data($list);
                }
    
                $values .= "({$county[0]}, '{$county[1]}', '{$county[1]}', 3, {$county[0]}, {$item['code']}),<br>";
            }
            //print_data($list);
        }
        echo "INSERT INTO city(id, name, short_name, level, `code`, pcode) VALUES<br>".$values;
    }
    
    public function tosj(){
        $Model = M('city');
    
        $list = $Model->order("`level`, id")->select();
    
        echo '{';
        foreach($list as $i=>$item){
            if($item['level'] == 1){
                echo ($i > 0 ? ',' : '').'"'.$item['id'].'":{';
                echo '"name":"'.$item['name'].'"';
    
                foreach($list as $item2){
                    if($item2['pcode'] == $item['code']){
    
                        echo ',"'.$item2['id'].'":{';
                        echo '"name":"'.$item2['name'].'"';
    
                        $city3 = '';
                        foreach($list as $item3){
                            if($item3['pcode'] == $item2['code']){
                                $city3 .= ',"'.$item3['id'].'":"'.$item3['name'].'"';
                            }
                        }
                        echo rtrim($city3, ',').'';
                        echo '}';
                    }
                }
    
                echo '}';
            }
        }
        echo '}';
    }
    
    public function __construct(){
	   $this->list = include COMMON_PATH.'Conf/city.php';
    }
    
    public function select($pid = 0){
        if($pid == 0){
            return $this->list;
        }
        
        $list = array();
        foreach ($this->list as $id=>$item){
            if($item['pcode'] == $pid){
                $item['id'] = $id;
                $list[] = $item;
            }
        }
        
        return $list;
    }
    
    public function find($code, $field = 'name'){
        $data = $this->list[$code];
        $data['id'] = $code;
        
        if($field == '*' || $field === true){
            return $data;
        }
        return $data[$field];
    }
    
    /**
     * 获取登录人的地址
     * @param string $tag
     * @param string $title
     * @return mixed
     */
    public function getMyCity($mid){
        $Model = M('member_address');
        $data = $Model->where("mid='{$mid}'")->select();
        if(count($data) > 0){
            foreach($data as $index=>$item){
                $data[$index]['province_name'] = $this->find($item['province_id']);
                $data[$index]['city_name'] = $this->find($item['city_id']);
                $data[$index]['county_name'] = $item['county_id'] ? $this->find($item['county_id']) : '';
            }
        }
        
        return $data;
    }
    
    /**
     * 
     */
    public function editCity($data){
        $Model = M('member_address');
        if(is_numeric($data['id'])){
            $Model->where("id='{$data['id']}'")->save($data);
        }else{
            $data['create_time'] = NOW_TIME;
            $Model->add($data);
            $data['id'] = $Model->getLastInsID();
        }
        
        $data['province_name'] = $this->find($data['province_id']);
        $data['city_name'] = $this->find($data['city_id']);
        $data['county_name'] = $data['county_id'] ? $this->find($data['county_id']) : '';
        
        return $data;
    }
}
?>