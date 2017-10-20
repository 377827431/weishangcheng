<?php 
namespace Admin\Model;

use Common\Model\BaseModel;
/**
 * @author lanxuebao
 *
 */
class LevelChangeModel extends BaseModel{
    protected $tableName = 'member_level_change';
    
    /**
     * 批量调级日志
     * @see \Think\Model::addAll()
     */
    public function add($member, $uid, $agent_level, $reason = 1){
        $date = date('Y-m-d H:i:s');
        foreach($member as $k=>$v){
            if($k == 'card_id'&& $v == $agent_level){
                continue;
            }
            $username = $_SESSION;
            foreach ($username as $i=>$item){
                foreach ($item as $j=>$items){
                }
            }
        }
        $level_change = array(
                'project_id'  =>$member['project_id'],
                'username'         => $items['username'],
                'mid'         => $member['mid'],
                'old_level'   => $member['card_id'],
                'new_level'   => $agent_level,
                '$result'     => $date,
                'reason'      => $reason
         );
        $sql = "INSERT INTO member_level_change (project_id,username,mid,old_level,new_level,created,reason)
            values(".$member['project_id'].",'".$items['username']."',".$member['mid'].','.$member['card_id'].','.$agent_level.",'".$date."',".$reason.')';
        $result = $this->query($sql);
        if($result <= 0){
            $this->error = '操作失败！';
            return -1;
        }
        return $result;
    }
    
    public function getAll($where = array()){
        $offset = I('get.offset', 0);
        $limit = I('get.limit', 50);
        $data = array('total' => 0,'rows' => array());

        $data['total'] = $this->alias('cl')
                              ->join('admin_user AS users ON cl.username=users.id')
                              ->where($where)
                              ->count();
        if($data['total'] == 0){
            return $data;
        }
        
        $data['rows'] = $this->alias('cl')
                             ->field('cl.*, m.nickname, users.nick, users.username')
                             ->join('admin_user AS users ON cl.username=users.id')
                             ->join('member AS m ON cl.mid=m.id')
                             ->where($where)
                             ->limit($offset,$limit)
                             ->order('cl.id DESC')
                             ->select();
        
        $agent_level = $this->agentLevel();
        
        foreach($data['rows'] as $k=>$v){
            $data['rows'][$k]['old_level_name'] = $agent_level[$v['old_level']]['title'];
            $data['rows'][$k]['level_name'] = $agent_level[$v['new_level']]['title'];
        }
        return $data;
    }
    
    /**
     * 搜索
     * @param unknown $mobile
     */
    public function search($mobile){
        $list = array();
        $agents = $this->agentLevel();
        $sql = "SELECT member.id, member.nickname AS nick, member.sex, member.agent_level, member.reg_time, member.mobile,
                    wx.nickname, wx.headimgurl, wx.subscribe, wx.created AS wx_created, wx.subscribe_time, wx.appid,
                    member.province_id, member.city_id, member.county_id, member.address,
                    wx.province, wx.city, wx.country, wx.last_login
                FROM member
                INNER JOIN wx_user AS wx ON wx.mid=member.id
                WHERE member.mobile='{$mobile}'
                ORDER BY wx.last_login DESC";
        $list = $this->query($sql);
        if(count($list) == 0){
            return $list;
        }
//         print_data($list);
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
        
        return array_values($result);
    }
    
    /**
     * 调级
     */
    public function change($mid, $targetLevel, $uid){
        $member = $this->query("SELECT id,agent_level,nickname FROM member WHERE id=".$mid);
        if(empty($member)){
            $this->error('会员不存在');
            return -1;
        }
        $member = $member[0];
        if($member['agent_level'] == $targetLevel){
            return 0;
        }
        if($member['agent_level']==1||$member['agent_level']==2){
            $this->error = '调级操作不合法';
            return -1;
        }
        else{
            if($targetLevel == 1 || $targetLevel == 2 ){
                $this->error = '调级不能超过会员';
                return -1;
            }
            else{
                $this->execute("UPDATE member SET agent_level='{$targetLevel}' WHERE id=".$member['id']);
                
                parent::add(array(
                    'uid'         => $uid,
                    'mid'         => $member['id'],
                    'old_level'   => $member['agent_level'],
                    'new_level'   => $targetLevel,
                    'created'     => date('Y-m-d H:i:s'),
                    'reason'      => 1
                ));
                
                $wxUser = $this->query("SELECT openid, appid FROM wx_user WHERE mid={$member['id']} AND subscribe=1 ORDER BY last_login DESC LIMIT 1");
                
                // 发送消息通知
                if(!empty($wxUser)){
                    $this->sendMsg($wxUser[0], $member['agent_level'], $targetLevel);
                }
                return 1;
            }
        }
    }
    
    /**
     * 发送消息通知
     * @param unknown $wxList
     * @param unknown $sourceLevel
     * @param unknown $targetLevel
     */
    private function sendMsg($wxUser, $sourceLevel, $targetLevel){
        $config = get_wx_config($wxUser['appid']);
        $wechatAuth = new \Org\Wechat\WechatAuth($config['WEIXIN']);
        
        $level = $this->agentLevel();
        $message = array(
            'template_id' => $config['WX_TEMPLATE']['TM00891'],
            'url'  => $config['HOST'].'/h5/personal',
            'data' => array(
                'first'   => array('value' => '亲爱的'.$wxUser['nickname'].'您已成为'.$level[$targetLevel]['title'], 'color' => '#173177'),
                'grade1'  => array('value' => $level[$sourceLevel]['title']),
                'grade2'  => array('value' => $level[$targetLevel]['title']),
                'time'    => array('value' => date('Y-m-d H:i')),
                'remark'  => array('value' => '请遵守平台和微信运营规范，切勿玩火自焚！记得修改个人资料哦！')
            )
        );
        
        $wechatAuth->sendTemplate($wxUser['openid'], $message);
    }
    
    /**
     * 导出excel
     */
    public function export($uid){
        $access = \Common\Common\Auth::get()->validated('admin','level','search_all');
        if (PHP_SAPI == 'cli')
            die('This example should only be run from a Web Browser');
        set_time_limit(0);
        
        $start_date = I('get.start_date', date('Y-m-d 00:00:00', strtotime('-1 day')));//下单时间  开始
        $end_date = I('get.end_date', date('Y-m-d 23:59:59'));//下单时间  结束
        $usersId    = $_GET['uid'];
        
        $where = array();
        if($access == false){
            $where['cl.uid'] = $uid;
        }elseif($usersId != ''){
            $where['users.id'] = $usersId;
        }
        
        if($start_date != '' && $end_date == '')
            $where['cl.created'] = array('egt', $start_date);
        if($end_date != '' && $start_date == '')
            $where['cl.created'] = array('elt', $end_date);
        if($start_date != '' && $end_date != '')
            $where['cl.created'] = array('between', array($start_date , $end_date));
        
        //查询记录
        $data = $this->alias('cl')
                ->field('cl.*, m.nickname, users.nick, users.username')
                ->join('users AS users ON cl.uid=users.id')
                ->join('member AS m ON cl.mid=m.id')
                ->where($where)
                ->order('cl.id DESC')
                ->select();
        
        // 代理登记
        $agents = $this->agentLevel();
        
        // 加载PHPExcel
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        
        // 设置文档基本属性
        $objPHPExcel->getProperties()
        ->setCreator("微通联盟")
        ->setLastModifiedBy("微通联盟")
        ->setTitle(date('Y-m-d H:i:s'))
        ->setSubject($start_date.'~'.$end_date)
        ->setDescription(json_encode($_POST));
        
        // 读取第一个工作表
        $worksheet = $objPHPExcel->setActiveSheetIndex(0);
        $worksheet->setTitle('调级数量记录');
        
        $i=1;
        $worksheet
        ->setCellValue('A'.$i, '操作人')
        ->setCellValue('B'.$i, '操作账号')
        ->setCellValue('C'.$i, '调为经理人数')
        ->setCellValue('D'.$i, '调为员工人数')
        ->setCellValue('E'.$i, '调为会员人数')
        ->setCellValue('F'.$i, '调为游客人数');
        
        // 读取第二个工作表
        $worksheet2 = $objPHPExcel->createSheet(1);
        $worksheet2->setTitle('调级记录');
        
        $ii=1;
        $worksheet2
        ->setCellValue('A'.$ii, '操作人')
        ->setCellValue('B'.$ii, '操作账号')
        ->setCellValue('C'.$ii, '代理昵称')
        ->setCellValue('D'.$ii, '原代理等级')
        ->setCellValue('E'.$ii, '调级后等级')
        ->setCellValue('F'.$ii, '操作时间');
        
        $list = array();
        foreach($data as $item){
            $ii++;
            $worksheet2
            ->setCellValue('A'.$ii, $item['nick'])
            ->setCellValue('B'.$ii, $item['username'])
            ->setCellValue('C'.$ii, $item['nickname'])
            ->setCellValue('D'.$ii, $agents[$item['old_level']]['title'])
            ->setCellValue('E'.$ii, $agents[$item['new_level']]['title'])
            ->setCellValue('F'.$ii, $item['created']);
        
            if(!isset($list[$item['mid']])){
                $list[$item['mid']] = array(
                    'usersId'     => $item['mid'],
                    'nick'        => $item['nick'],
                    'username'    => $item['username'],
                    'levelOne'    => $item['level'] == 1 ? 1 : 0,
                    'levelTwo'    => $item['level'] == 2 ? 1 : 0,
                    'levelThree'  => $item['level'] == 3 ? 1 : 0,
                    'levelFour'   => $item['level'] == 0 ? 1 : 0,
                );
            }else{
                $list[$item['mid']]['levelOne']   += $item['level'] == 1 ? 1 : 0;
                $list[$item['mid']]['levelTwo']   += $item['level'] == 2 ? 1 : 0;
                $list[$item['mid']]['levelThree'] += $item['level'] == 3 ? 1 : 0;
                $list[$item['mid']]['levelFour']  += $item['level'] == 0 ? 1 : 0;
            }
        }
        
        if(!empty($list)){
            foreach($list as $v){
                $i++;
                $worksheet
                ->setCellValue('A'.$i, $v['nick'])
                ->setCellValue('B'.$i, $v['username'])
                ->setCellValue('C'.$i, $v['levelOne'])
                ->setCellValue('D'.$i, $v['levelTwo'])
                ->setCellValue('E'.$i, $v['levelThree'])
                ->setCellValue('F'.$i, $v['levelFour']);
            }
        }
        
        // Redirect output to a client’s web browser (Excel2007)
        $text = iconv('UTF-8', 'GB2312', '调级记录');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$text.date('YmdHis').'.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        //header('Cache-Control: max-age=1');
        
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}
?>