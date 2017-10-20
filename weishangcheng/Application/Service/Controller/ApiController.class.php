<?php
namespace Service\Controller;

use Org\Wechat\WechatAuth;
use Common\Common\CommonController;
use Common\Model\BaseModel;

/**
 * 公共类
 *
 * @author lanxuebao
 */
class ApiController extends CommonController
{
    /**
     * 获取微信JSSDK参数
     */
    public function jssdkconfig(){
        $w = new WechatAuth();
        $data = $w->getSignPackage();
        $this->ajaxReturn($data);
    }
    
    public function accssToken(){
        $w = new WechatAuth();
        $data = $w->getAccessToken();
        exit($data);
    }
    
    public function getJsApiTicket(){
        $w = new WechatAuth();
        $data = $w->getJsApiTicket();
        exit($data);
    }
    
    /**
     * 下载多媒体文件到本地
     * @param string $media_id
     */
    public function syncMaterial(){
        $appid = $_REQUEST['appid'];
        $type  = $_REQUEST['type'];
        $page  = $_REQUEST['page'];

        $size = 20;
        $list = array();
        $result = array();
        $auth = new WechatAuth($appid);
        $data = $auth->batchgetMaterial($type, ($page - 1) * $size, $size);

        if(isset($data['error'])){
            $this->error('更新失败：'.$data['errmsg']);
        }
        
        $total = $data['total_count'];
        
        if($type == 'news'){
            foreach ($data['item'] as $item){
                $content = array();
                $update_time = date('Y-m-d H:i:s', $item['update_time']);
                $news = array(
                    'appid'         => $appid,
                    'type'          => $type,
                    'media_id'      => $item['media_id'],
                    'title'         => $item['content']['news_item'][0]['title'],
                    'update_time'   => $update_time,
                    'url'           => $item['content']['news_item'][0]['url']
                );
            
                foreach($item['content']['news_item'] as $_news){
                    unset($_news['content']);
                    $content[] = $_news;
                }
                
                $news['content'] = json_encode($content, JSON_UNESCAPED_UNICODE);
                
                $list[] = $news;
                
                $result[] = array(
                    'media_id'      => $item['media_id'],
                    'update_time'   => $update_time,
                    'content'       => $content
                );
            }
        }else{
            foreach ($data['item'] as $item){
                $list[] = array(
                    'appid'         => $appid,
                    'type'          => $type,
                    'media_id'      => $item['media_id'],
                    'title'         => $item['name'],
                    'update_time'   => date('Y-m-d H:i:s', $item['update_time']),
                    'url'           => $item['url'],
                    'content'       => null
                );
                $result[] = array(
                    'media_id'      => $item['media_id'],
                    'name'          => $item['name'],
                    'update_time'   => date('Y-m-d H:i:s', $item['update_time']),
                    'url'           => $item['url']
                );
            }
        }

        $this->ajaxReturn(array('total'=> $total, 'rows' => $result));
    }
    
    /**
     * 下载微信多媒体文件
     */
    public function meida_down(){
        $appid = $_REQUEST['appid'];
        $list  = $_REQUEST['mediaid'];

        $config     = get_wx_config($appid);
        $wechatAuth = new WechatAuth($config['WEIXIN']);
        $url = array();
        foreach ($list as $mediaId){
            $folder = '/upload/refund/'.date('Y-m-d');
            if(!@is_dir($_SERVER['DOCUMENT_ROOT'].$folder)){
                mkdir ($_SERVER['DOCUMENT_ROOT'].$folder, 0777, true);
            }
            
            $url[] = $config['CDN'].$wechatAuth->meidaDownLoad($mediaId,  $folder.'/'.date('YmdHis').rand(100, 999));
        }
        
        $this->ajaxReturn($url, 'JSONP');
    }
    
    public function getMember(){
        $kw = $_GET['kw'];
        if(!is_numeric($kw)){
            $this->error('请输入关键词');
        }
        
        $Model = new BaseModel();
        $where = "WHERE (member.id='{$kw}'".(preg_match('/^1[3|4|5|7|8]\d{9}$/', $_GET['kw']) ? " OR member.mobile='{$_GET['kw']}'" : "").")";
        
        $list = array();
        $sql = "SELECT member.id, member.nickname AS nick, member.sex, member.agent_level, member.reg_time, member.mobile,
                wx.nickname, wx.headimgurl, wx.subscribe, wx.created AS wx_created, wx.subscribe_time, wx.appid,
                member.province_id, member.city_id, member.county_id, member.address,
                wx.province, wx.city, wx.country, wx.last_login
                FROM member
                INNER JOIN wx_user AS wx ON wx.mid=member.id
                {$where}
                ORDER BY wx.last_login DESC";
        $list = $Model->query($sql);
        if(count($list) == 0){
            $this->error('无匹配会员');
        }

        $agents = $Model->agentLevel();
        $wx = C('WXLIST');
        $result = array();
        foreach($list as $member){
            if(!isset($result[$member['id']])){
                $data = array(
                    'id'            => $member['id'],
                    'nick'          => $member['nick'],
                    'mobile'        => $member['mobile'],
                    'sex'           => $member['sex'],
                    'agent_level'   => $member['agent_level'],
                    'agent_title'   => $agents[$member['agent_level']]['title'],
                    'reg_time'      => date('Y年m月d日 H:i', $member['reg_time']),
                    'province_id'   => $member['province_id'],
                    'city_id'       => $member['city_id'],
                    'county_id'     => $member['county_id'],
                    'address'       => $member['address'],
                    'agents'        => array(),
                    'wxs'           => array()
                );
                foreach($agents as $agent){
                    if($agent['level'] == 1||$agent['level'] == 2){
                        continue;
                    }
        
                    $data['agents'][] = array(
                        'level'    => $agent['level'],
                        'title'    => $agent['title'],
                        'disabled' => $agent['level'] == $member['agent_level']
                    );
                }
                $result[$member['id']] = $data;
            }else if(count($result[$member['id']]['wxs']) == 3){
                continue;
            }
        
            $result[$member['id']]['wxs'][] = array(
                'app_name'       => $wx[$member['appid']]['name'],
                'headimgurl'     => $member['headimgurl'],
                'subscribe_time' => date('Y年m月d日 H:i', $member['subscribe_time']),
                'wx_created'     => date('Y年m月d日 H:i', $member['wx_created']),
                'last_login'     => date('Y年m月d日 H:i', $member['last_login']),
                'subscribe'      => $member['subscribe'],
                'province'       => $member['province'],
                'city'           => $member['city'],
                'country'        => $member['country'],
                'nickname'       => $member['nickname']
            );
        }
        
        $result = array_values($result);
        $this->ajaxReturn($result);
    }
}
?>