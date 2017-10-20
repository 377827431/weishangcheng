<?php 
namespace Common\Model;

use Think\Model;
use Org\PinYin;
use Org\IdWork;
/**
 * 店铺表modal
 * @author lanxuebao
 *
 */
class ShopModel extends Model{
    protected $tableName = 'shop';
    private $projectId;
    function __construct($projectId = 0){
        parent::__construct();
        
        $this->projectId = $projectId;
    }
    
    public function getAll(){
        $projectId = $this->projectId;
        $where = "";
        if($_GET['name'] != ''){
            $where = " AND `name` LIKE '%".addslashes($_GET['name'])."%'";
        }
        
        $sql = "SELECT shop.id, member.name AS `nickname`, shop.`name`, shop.state, shop.created, shop.logo, shop.pv, shop.uv, shop.cat_id,
                    shop.service_hotline, shop.expire_time, shop.watch_num, shop.logistics_score, shop.service_score, shop.rate_times,
                    shop.sum_logistics, shop.sum_service, shop.rate_good, shop.rate_middle, shop.rate_bad, shop_info.contacts,
                    shop_info.mobile, shop_info.qq, shop_info.business_hours, shop_info.`desc`, shop_info.province_id, shop_info.city_id,
                    shop_info.county_id, shop_info.address, shop_info.lon, shop_info.lat
                FROM shop
                LEFT JOIN shop_info ON shop_info.id = shop.id
                LEFT JOIN member ON member.id = shop.mid
                WHERE shop.id BETWEEN {$projectId}000 AND {$projectId}999".$where;
        
        $list = $this->query($sql);
        foreach ($list as $i=>$item){
            $item['created'] = substr($item['created'], 0, 10);
            $item['expire_date'] = $item['expire_time'] > 0 ? date('Y-m-d', $item['expire_time']) : '永久';
            $item['goods_rate'] = $item['rate_good'].'/'.$item['rate_middle'].'/'.$item['rate_bad'];
            $item['state_str'] = $item['state'] == 1 ? '启用' : ($item['state'] == 0 ? '禁用' : '过期');
            $list[$i] = $item;
        }
        return $list;
    }
    
    /**
     * 根据ID获取店铺信息
     */
    public function getById($id){
        $sql = "SELECT shop.id, shop.`name`, shop.state, shop.created, shop.logo, shop.pv, shop.uv, shop.cat_id,
                    shop.service_hotline, shop.expire_time, shop.watch_num, shop.logistics_score, shop.service_score, shop.rate_times,
                    shop.sum_logistics, shop.sum_service, shop.rate_good, shop.rate_middle, shop.rate_bad, shop_info.contacts,
                    shop_info.mobile, shop_info.qq, shop_info.business_hours, shop_info.`desc`, shop_info.province_id, shop_info.city_id,
                    shop_info.county_id, shop_info.address, shop_info.lon, shop_info.lat
                FROM shop
                LEFT JOIN shop_info ON shop_info.id = shop.id
                WHERE shop.id=".$id;
        $shop = $this->query($sql);
        $shop = $shop[0];
        if(!$shop){
            $this->error = '店铺不存在';
            return;
        }else if(IdWork::getProjectId($shop['id']) != $this->projectId){
            $this->error = '您无权编辑他人的店铺';
            return;
        }
        return $shop;
    }
    
    /**
     * 删除
     * @param unknown $id
     */
    public function deleteById($ids){
    	$ids = explode(',', $ids);
    	
    	foreach($ids as $id){
    		if(!is_numeric($id)){
    			$this->error = '被删除ID错误';
    			return;
    		}if($id == $this->projectId.'000'){
    			$this->error = '第一个店铺不能删除';
    			return;
    		}if(IdWork::getProjectId($id) != $this->projectId){
    			$this->error = '您无权删除他人店铺';
    			return;
    		}
    	}
        
        $this->delete($id);
    }
    
    /**
     * 店铺名称是否存在
     */
    public function existsName($name, $id = 0){
        $name = addslashes($name);
        $shops = $this->query("SELECT id, `name` FROM shop WHERE `name`='{$name}' ORDER BY id LIMIT 2");
        if($shops){
            if(isset($shops[1])){
                return $shops[1]['id'] != $id;
            }else{
                return $shops[0]['id'] != $id;
            }
        }
        return $shops ? true : false;
    }
    
    /**
     * 首次创建店铺
     */
    public function firstAdd($login, $shop, $info = null){
        // 判断登录手机号是否存在
        $login['mobile'] = addslashes($login['mobile']);
        $exists = $this->query("SELECT id FROM admin_user WHERE username='{$login['mobile']}'");
        if($exists){
            $this->error = '登录账号已存在';
            return;
        }
        
        // 判断店铺名称是否存在
        $exists = $this->existsName($shop['name']);
        if($exists){
            $this->error = '店铺名称已存在';
            return;
        }

        $shopName     = addslashes($shop['name']);
        $aliasList    = $this->getAlias($shopName);
        $alias        = current($aliasList);
        $today        = date('Y-m-d H:i:s', NOW_TIME);
        $host         = C('H5_HOST');
        $defaultMchAppid = C('DEFAULT_WEIXIN');
        $defaultAppid = C('DEFAULT_APPID');
        $thirdAppid   = C('THIRD_APPID');
        if(!empty($login['aliid'])){
            $aliid = $login['aliid'];
        }else{
            $aliid = '';
        }
        if(!empty($login['userid'])){
            $userid = $login['userid'];
        }else{
            $userid = '';
        }
        $mid = $this->getMidByMobile($login['mobile']);
        if($mid == 0){
            $mid = '';
        }
        // 保存数据
        $this->startTrans();
        // 插入项目
        $this->execute("INSERT INTO project SET `name`='{$shopName}', alias='{$alias}', host='{$host}', third_appid='{$thirdAppid}', created=".NOW_TIME);
        $projectId = $this->query("SELECT LAST_INSERT_ID()");
        $projectId = current($projectId[0]);
        
        // 插入项目公众号
        $this->execute("INSERT INTO project_appid SET id={$projectId}, alias='{$alias}', appid='{$defaultAppid}', mch_appid='{$defaultMchAppid}', created=".NOW_TIME);
        
        // 创建登录账号
        $this->execute("INSERT INTO admin_user SET username='{$login['mobile']}', password='".md5($login['password'])."', shop_id={$projectId}001, `status`=1, nick='店铺管理员', administrator=0, role='27', created='{$today}', last_login='{$today}', can_del=0");
        $userId = $this->query("SELECT LAST_INSERT_ID()");
        $userId = current($userId[0]);

        // 创建店铺
        $shopName = 'XD'.$userId;
        $shopId = $projectId.'001';
        $this->execute("INSERT INTO shop SET id={$projectId}000, `name`='{$shopName}', created='{$today}', service_hotline='{$login['mobile']}', aliid='{$aliid}', userid='{$userid}', mid='{$mid}'");
        $this->execute("INSERT INTO shop SET id={$projectId}001, `name`='{$shopName}', created='{$today}', service_hotline='{$login['mobile']}', aliid='{$aliid}', userid='{$userid}', mid='{$mid}'");

        $this->execute("INSERT INTO shop_info SET id={$projectId}001");

        if(!empty($login['aliid'])){
            //同步店铺信息到1688
            $data = array(
                'shop_name'  => $shopName,
                'desc'       => '',
                'starttime'  => $today,
                'endtime'    => date('Y-m-d H:i:s',strtotime($today)+24*365*3600),
                'status'     => 'true',
                );
            $result = $this->synShopInfoTo1688($data,$login['aliid']);

            
        }
        $this->commit();
        
        // 创建会员卡
        $this->execute("INSERT INTO project_card(id, title, price_title) VALUES({$projectId}1, '铜牌会员', '铜牌代理'),({$projectId}2, '银牌会员', '银牌代理'),({$projectId}3, '金牌会员', '金牌代理'),({$projectId}4, '钻石会员', '钻石代理')");
        return array('login_id' => $userId, 'project_id' => $projectId, 'shop_id' => $shopId);
    }
    /*
     * 同步店铺信息到1688
     */
    public function synShopInfoTo1688($data,$tokenId){
        $ali = new \Org\Alibaba\AlibabaAuth($tokenId);
        $userPlatformDetails = array(
            'shopName'    => $data['shop_name'],//小店名称
            'shopDesc'    => $data['desc'],//小店描述
            'authStart'   => $data['starttime'],//授权开始时间
            'authEnd'     => $data['endtime'],//授权结束时间
            'authState'   => $data['status'],//授权状态 
            );
        $result = $ali->syncUserPlatform($userPlatformDetails);
        //失败重试，两次限制
        for($i=0;$i<=2;$i++){
            if($i == 2){
                //达到重试次数限制写入日志
                $response = json_encode($userPlatformDetails,JSON_UNESCAPED_UNICODE);
                $return = json_encode($result,JSON_UNESCAPED_UNICODE);
                $time = date("Y-m-d H:i:s");
                $sql = "INSERT INTO alibaba_shop_syn(shop_name,aliid,response,result,time) VALUES('{$data['shop_name']}','{$tokenId}','{$response}','{$return}','{$time}')";
                $this->execute($sql);
            }else{
                //失败后，重试同步店铺信息
                if($result['success'] != true){
                    $result = $ali->syncUserPlatform($userPlatformDetails);
                }else{
                    break;
                }
            }
        }
        
        return $result;
    }
    /**
     * 计算别名
     */
    public function getAlias($shopName, $default = array()){
        $PinYin = new PinYin();
        $pyList = $default;
        $name = $shopName;
        $pyList[] = $PinYin->getFirstPY($name);
        $pinyin = $PinYin->getAllPY($name);
        if(strlen($pinyin) < 21){
            $pyList[] = $pinyin;
        }
        
        $replaceList = array('\W', '(公司)$', '(集团)$', '(有限公司)$', '(有限责任公司)$', '(股份有限公司)$', '(旗舰)$', '(旗舰店)$');
        foreach($replaceList as $pattern){
            $pattern = '/'.$pattern.'/';
            if(preg_match($pattern, $shopName)){
                $name = preg_replace($pattern, '', $shopName);
                if(!$name){
                    continue;
                }
                $pyList[] = $PinYin->getFirstPY($name);
        
                $pinyin = $PinYin->getAllPY($name);
                if(strlen($pinyin) < 21){
                    $pyList[] = $pinyin;
                }
            }
        }
        
        $pyList = array_filter($pyList);
        $pyList = array_unique($pyList);
        usort($pyList, function($v1, $v2){
            return strlen($v1) > strlen($v2);
        });
        
        // 查看哪个别名没有被使用
        $aliasList = $this->query("SELECT alias FROM project_appid WHERE alias IN ('".implode("','", $pyList)."')");
        foreach ($aliasList as $item){
            $i = array_search($item['alias'], $pyList);
            unset($pyList[$i]);
        }
        
        if(count($pyList) == 0){
            $temp = uniqid();
            for ($i=0; $i < strlen($temp); $i++) {
                if (ord($temp[$i])<97) {
                    $temp[$i] = chr(ord($temp[$i])+55);
                }
            }
            $pyList[] = $temp;
        }
        return $pyList;
    }
    
    /**
     * 添加新店铺
     */
    public function addNew($shop, $info){
        $exists = $this->existsName($shop['name']);
        if($exists){
            $this->error = '店铺名称已存在';
            return;
        }
        
        $max = $this->query("SELECT max(id) AS max_id FROM shop WHERE id BETWEEN {$this->projectId}001 AND {$this->projectId}999");
        $maxId = $max[0]['max_id'] ? $max[0]['max_id'] + 1 : 1;
        // 校验是否超过999个店铺
        if(IdWork::getProjectId($maxId) != $this->projectId){
            $this->error = '您的999个店铺已全部使用，已无法再创建新店铺了';
            return;
        }
        
        $shop['mid'] = $this->getMidByMobile($info['mobile']);
        
        $shop['id'] = $maxId;
        $this->add($shop);
        
        $info['id'] = $maxId;
        M('shop_info')->add($info, null, true);
        return $maxId;
    }
    
    /**
     * 更改店铺信息
     */
    public function update($shop, $info){
    	if(is_numeric($info['mobile'])){
    		$mid = $this->getMidByMobile($info['mobile']);
    		if($mid == 0){
    			$this->error = '负责人手机号不存在';
    			return false;
    		}
    		$shop['mid'] = $mid;
    	}

        $exists = $this->existsName($shop['name'], $shop['id']);
        if($exists){
            $this->error = '店铺名称已存在，请更改!';
            return false;
        }

        $this->where('id='.$shop['id'])->save($shop);
        
        $info['id'] = $shop['id'];
        M('shop_info')->add($info, null, true);
        return true;
    }
    
    /**
     * 根据手机号获取会员id
     * @param unknown $mobile
     */
    private function getMidByMobile($mobile){
    	if(!is_numeric($mobile)){
    		$this->error = '请输入负责人手机号';
    		return false;
    	}
    	
    	$member = $this->query("SELECT id FROM member WHERE mobile='{$mobile}' LIMIT 1");
    	if(!$member){
    		$this->error = '手机号不存在';
    		return 0;
    	}
    	return $member[0]['id'];
    }

    /**
     * 禁用启用
     */
    public function disabled($id){
        if($id == $this->projectId.'000'){
            $this->error = '此店铺不能删除';
            return;
        }else if(IdWork::getProjectId($id) != $this->projectId){
            $this->error = '您无权操作他人店铺';
            return;
        }
        
        $this->execute("UPDATE shop SET state=IF(state=1, 0, 1) WHERE id=".$id);
    }
}
?>