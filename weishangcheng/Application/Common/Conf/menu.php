<?php
function create_mall_menu(){
    $key = 'menu_'.PROJECT['id'];
    $list = S($key);
    if($list === false){
        $list = M()->query("SELECT button FROM mall_menu WHERE project_id='".PROJECT['id']."' LIMIT 1");
        $list = json_decode($list[0]['button'], true);
        S($key, $list, 7200);
    }
    if(!$list){
        $list = array (
            array (
                'type' => 'view',
                'name' => '全部商品',
                'url' => '/?url=mall'
            ),
            array (
                'type' => 'view',
                'name' => '我的订单',
                'url' => session('user.id')=='1000019'?"javascript:toast.show('您当前操作为买家行为 请在微信中查看');":__MODULE__.'/ordernew'
            ),
            array (
                'type' => 'view',
                'name' => '我的',
                'url' => __MODULE__.'/personalnew'
                // 'sub_button' => array (
                //     array (
                //         'type' => 'view',
                //         'name' => '个人资料',
                //         'url' => __MODULE__.'/personal'
                //     ),
                //     array (
                //         'type' => 'view',
                //         'name' => '我的订单',
                //         'url' => __MODULE__.'/order'
                //     )
                // ),
            ),
        );
    }

    $html = '';
    foreach ($list as $btn){
        if(isset($btn['sub_button'])){
            $html .= '<div class="nav-item js-submenu"><a href="javascript:;" class="mainmenu"><i class="arrow-weixin"></i><span class="mainmenu-txt">'.$btn['name'].'</span></a>';
            $html .= create_submenu($btn['sub_button']);
            $html .= '</div>';
        }else{
            $html .= '<div class="nav-item">';
            $txt = '<span class="mainmenu-txt">'.$btn['name'].'</span>';
            switch ($btn['type']){
                case 'click':
                    $html .= ' <a href="javascript:;" class="'.$btn['key'].'">'.$txt.'</a>';
                    break;
                default:
                    $html .= ' <a href="'.$btn['url'].'" class="mainmenu">'.$txt.'</span></a>';
                    break;
            }
            $html .= '</div>';
        }
    }
    return $html;
}

function create_submenu($list){
    $html = '<div class="submenu"><span class="arrow before-arrow"></span><span class="arrow after-arrow"></span><ul>';
    $last = count($list) - 1;
    foreach ($list as $i=>$btn){
        switch ($btn['type']){
            case 'click':
                $html .= ' <li><a href="javascript:;" class="'.$btn['key'].'">'.$btn['name'].'</a></li>';
                break;
            default:
                $html .= ' <li><a href="'.$btn['url'].'">'.$btn['name'].'</a></li>';
                break;
        }

        if($i < $last){
            $html .= '<li class="line-divide"></li>';
        }
    }
    $html .= '</ul></div>';
    return $html;
}
return create_mall_menu();
?>
