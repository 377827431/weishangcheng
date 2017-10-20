<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/9/25
 * Time: 16:30
 */

namespace H5\Controller;


class CallController
{
    public function index(){
        $user = array(
            'id' => 1000021,
            "openid" => "obIC3w-8C4LNHUGgRRUNnEmVtdMQ",
            "appid" => "wxecdbd3aa2d27e833",
            "login_type" => 2,
            "wxecdbd3aa2d27e833" => array(
                "openid" => "obIC3w-8C4LNHUGgRRUNnEmVtdMQ",
                "mid" => 1000021
            )
        );
        session('user', $user);
    }
}