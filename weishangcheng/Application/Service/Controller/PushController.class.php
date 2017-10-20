<?php
namespace Service\Controller;
use Common\Common\CommonController;

use Org\Wechat\WechatAuth;

/**
 * 数据统计热点图API
 *
 * @author lanxuebao
 */
class PushController extends CommonController
{
    public function index()
    {#echo '1';die;
        $data = M("test")->limit(0,300)->select();
        $m = M();
        foreach( $data as $k=>$v )
        {
            $re = $m->execute("delete from test where openid = '{$v['openid']}'");
            if ( $re <= 0 )
            {
               unset($data[$k]);
            }
        }
        $i = 0;
        $wechat = new WechatAuth(C('WEIXIN'));
        foreach( $data as $key => $value )
        {
            $i++;
            $result1 = $wechat->sendNews(
                $value['openid'],
                array('同学们，今晚有课哦！', '微商学院开课啦！这里有专业的讲师教你如何刷爆朋友圈，大咖级微商高手分享经验，全部课程免费，让你不再迷茫。',
                  'https://mp.weixin.qq.com/s?__biz=MzI3NjI3OTc4NQ==&mid=100001145&idx=1&sn=39d049625cf33136755fb69392f8a0ad#rd',
                  'https://mmbiz.qlogo.cn/mmbiz/0CDg4F45g36qoHJR5Sicr8QJ9xrD4dpfSlMhwzuuJUMHWOfJMzEgbu6L4dfnI5ZtGc4U1jX2Bpfmam3NgZ9Ijiag/0?wx_fmt=jpeg')
            );
        }
        header("location: http://wslm.hljwtlm3.com/service/push?a=".$i);
    }
    public function SMSpush()
    {die;
        $temp = array(
            'id'  => '1111111111',
            'sign'  => '培训通知',
            'param'  => array(
                    'time'  => '7月2日晚19点30',
                    'content'  => '微商联盟:玩转微商',
                    'num'  => '81031871'
                ),
            'phone'  => 'phone',
            'code'  => 'SMS_11300438',
        );
        $data = M("test")->limit(0,30)->select();
        $m = M();
        $list = array();
        foreach( $data as $k=>$v )
        {
            $re = $m->execute("delete from test where openid = '{$v['openid']}'");
            if ( $re <= 0 )
            {
               unset($data[$k]);
            }else
            {
                $temp['phone'] = $v['openid'];
                $list[] = $temp;
            }
        }
        $i = 0;
        vendor('TopSDK.SMSSend');
        $reslist = PushSMS($list);
        $Model = M('sms_send');
        foreach( $reslist as $res )
        {
            $i++;
            if (isset($res->result)) {
                $smssend = array(
                    'uid'   =>  $temp['id'],
                    'status'    =>  1,
                    'remark'    =>  $res->request_id.''
                );
                $Model->add($smssend);
                $i++;
            } else {
                $smssend = array(
                    'uid'   =>  $temp['id'],
                    'status'    =>  0,
                    'remark'    =>  $res->request_id.''.$res->sub_msg
                );
                $Model->add($smssend);
            }
        }
        header("location: http://wslm.hljwtlm3.com/service/push/SMSpush?a=".$i);
    }
}