<?php
namespace Admin\Controller;

use Common\Common\CommonController;

/**
 * 微信 - 高级图文
 * @author wangbaishuang
 *
 */

class SeniorController extends CommonController
{
    function __construct()
    {
        parent::__construct();
    }
    
    public function index(){
        $this->display();    
    }
}