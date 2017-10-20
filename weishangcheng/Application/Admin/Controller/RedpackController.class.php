<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 红包
 * 
 * @author LanXueBao
 */
class RedpackController extends CommonController
{

    private $Model;

    function __construct()
    {
        parent::__construct();
        $this->Model = M("wx_redpack");
    }
    
    private function typeName($type){
        switch ($type){
            case 4:
                return '定制';
                break;
            case 1:
                return '现金';
                break;
            case 2:
                return '裂变';
                break;
            case 3:
                return '积分';
                break;
            default:
                return '';
        }
    }

    /**
     * 红包列表
     */
    public function index()
    {
        if (IS_AJAX) {
            $data           = array();
            $offset         = I('get.offset/d', 0);
            $limit          = I('get.limit/d', 20);
            
            $data['total']  = $this->Model->count();
            $data['rows']   = $this->Model->limit($offset, $limit)->select();
            
            foreach($data['rows'] as $index=>$item){
                if($item['type'] == 4){ // 定制红包
                    $data['rows'][$index]['state'] = array('disabled' => true);
                }
                
                $data['rows'][$index]['type_name'] = $this->typeName($item['type']);
            }
            $this->ajaxReturn($data);
        }
        
        $this->display();
    }
    
    /**
     * 添加红包
     */
    public function add(){
        if(IS_POST){
            $data = $_POST;
            $data['create_uid']     = $this->user('id');
            $data['create_time']    = time();
            $data['wishing']        = json_encode($_POST['wishing']);
            
            $result = $this->Model->add($data);
            if($result > 0){
                $this->success('已保存');
            }
            
            $this->error('保存失败');
        }
        
        $config = C('WEIXIN');
        // 默认值
        $data = array(
            'send_name'     => $config['mchName'],
            'logo_imgurl'   => $config['logo'],
            'share_imgurl'  => $config['logo'],
            'wishing'       => array('')
        );
        $this->assign('data', $data);
        $this->display('edit');
    }
    
    /**
     * 根据id查找红包
     * @param unknown $id
     */
    private function find($id){
        $data = $this->Model->find($id);
        if(empty($data)){
            $this->error('红包不存在');
        }
        $data['wishing'] = json_decode($data['wishing'], true);
        
        return $data;
    }
    
    /**
     * 编辑红包
     */
    public function edit($id = 0){
        if(IS_POST){
            $data = $_POST;
            $data['wishing']        = json_encode($_POST['wishing']);
            $result = $this->Model->where("id='{$id}'")->save($data);
            $this->success('已保存');
        }
        
        $data = $this->find($id);
        $this->assign('data', $data);
        $this->display();
    }
    
    /**
     * 红包详情
     */
    public function detail($id = 0){
        if(IS_AJAX){
            $data               = array('total' => 0, 'rows' => array());
            $offset             = I('get.offset/d', 0);
            $limit              = I('get.limit/d', 50);
            
            // 搜索条件
            $where              = array();
            $where['record.pid']= I('get.id/d', 0);
            $nickname           = I('get.nickname', '');
            $stime              = I('get.stime', '');
            $etime              = I('get.etime', '');
            if($nickname != '')
                $where['wx_user.nickname']   = array('like', '%'.$nickname.'%');
            
            if($stime != '' && $etime != ''){
                $where['record.create_time'] = array('between', strtotime($stime).','.strtotime($etime));
            }else{
                if($stime != '')
                    $where['record.create_time'] = array('EGT', strtotime($stime));
                if($etime != '')
                    $where['record.create_time'] = array('ELT', strtotime($etime));
            }

            // 查询
            $Model              = M('wx_redpack_record');
            $data['total']      = $Model->alias("record")->join("wx_user ON wx_user.openid=record.openid")->where($where)->count();
            if($data['total']   > 0){
                $data['rows']   = $Model
                                  ->alias("record")
                                  ->field("record.id, record.openid, record.amount, record.mch_billno 
                                      ,record.create_time, record.result_code, record.err_code, record.err_code_des
                                      ,wx_user.nickname,wx_user.province, wx_user.city, wx_user.sex, wx_user.subscribe")
                                  ->join("wx_user ON wx_user.openid=record.openid")
                                  ->where($where)
                                  ->order("record.id DESC")
                                  ->limit($offset, $limit)
                                  ->select();
                
                foreach($data['rows'] as $index=>$item){
                    $data['rows'][$index]['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                }
            }
            
            // 查找红包领取记录
            $this->ajaxReturn($data);
        }
        $data = $this->find($id);
        $this->assign('data', $data);
        $this->display();
    }
}
?>