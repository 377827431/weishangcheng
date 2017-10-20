<?php
namespace Pay\Controller;

use Common\Common\CommonController;

class PayController extends CommonController{
    
    protected function checkLogin(){
        $login = session('user');
        if(!$login['id']){
            if(strlen($_GET['ticket']) < 20){
                $this->error('登录超时，请返回重新操作');
            }
        
            $option = C('SESSION_OPTIONS');
            $option['id'] = $_GET['ticket'];
            session($option);
        
            $login = session('user');
            if(!$login['id']){
                $this->error('登录超时，请返回重新操作');
            }
        }
        
        return $login;
    }
}
?>