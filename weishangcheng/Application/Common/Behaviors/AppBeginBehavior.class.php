<?php
namespace Common\Behaviors;

class AppBeginBehavior{
    //行为执行入口
    public function run(){
        /*
        echo print_r($_GET).'<br>';
        echo '__MODULE__: '.__MODULE__.'<br>';
        echo '__CONTROLLER__: '.__CONTROLLER__.'<br>';
        echo '__ACTION__: '.__ACTION__.'<br>';
        echo 'MODULE_NAME: '.MODULE_NAME.'<br>';
        echo 'CONTROLLER_NAME: '.CONTROLLER_NAME.'<br>';
        echo 'ACTION_NAME: '.ACTION_NAME.'<br>';
        echo 'PATH_INFO: '.$_SERVER['PATH_INFO'].'<br>';
        die;
        */

        // Session初始化
        if(!IS_CLI){
            session(C('SESSION_OPTIONS'));
        }
        define('P_USER', 'user');
        if (APP_NAME == P_USER){
            define('PROJECT_ID', 0);
            return;
        }
        // H5页面的身份认证
        if(MODULE_NAME == 'H5'){
            if (APP_NAME == ''){
                session('redirect', 1);
                $url = $_GET['url'];
                redirect('/594b69b5c7de6?default=1&url='.$url);
            }else{
                if ($_GET['default'] == 1){
                    session('redirect', 1);
                }else{
                    session('redirect', 0);
                }
            }
            // 初始化配置文件
            $this->initConfig();

            // 绑定店铺关系
            $login = session('user');
            if(!empty($login) && $login['id']){
                $this->bindShareProject($login);
            }
        }
    }
    
    /**
     * 重新初始化项目配置文件
     */
    private function initConfig(){
        $project = get_project(APP_NAME);
        define('PROJECT_ID', $project['id']); // 后面可能废弃
        define('PROJECT', $project);

        /*
        $config = get_host_project(APP_NAME);
        C('TMPL_PARSE_STRING.__PROJECT__', '<?php echo "'.$config['url'].'" ?>');
        define('PROJECT_ID', $config['id']);
        
        C('HOST_PROJECT', $config);

        $project = get_project($config['id']);
        $project['url'] = $config['url'];
        C('PROJECT', $project);
        
        $weixin = get_wx_config($config['appid']);
        C('WEIXIN', $weixin);
        $default_weixin = get_wx_config(C('DEFAULT_WEIXIN'));
        C('DEFAULT_WEIXIN_CONFIG', $default_weixin);
        $open_weixin = get_wx_config(C('OPEN_WEIXIN'));
        C('OPEN_WEIXIN_CONFIG', $open_weixin);
        */
    }
    
    /**
     * 绑定会员和店铺关系
     */
    private function bindShareProject($login){
        $project = PROJECT;
        $appid   = $project['appid'];
        $mid     = isset($login[$appid]) ? $login[$appid]['mid'] : $login['id'];

        $key = 'mbr_pro_'.$mid;
        $inited = S($key);
        $projects = $inited ? explode(',', $inited) : array();
        if(in_array($project['id'], $projects)){
            S($key, $inited, 7200);
            return;
        }

        $Member = new \Common\Model\MemberModel();
        $Member->bindProject(array(
            'project_id' => $project['id'],
            'mid'        => $mid,
            'share_mid'  => is_numeric($_GET['share_mid']) ? $_GET['share_mid'] : 0,
            'source'     => is_numeric($_GET['share_mid']) ? 'share_'.$_GET['share_mid'] : strtolower($_SERVER['PATH_INFO']),
            'host'       => $project['alias']
        ));

        // 同步session信息
        $projects[] = $project['id'];
        S($key, implode(',', $projects), 7200);
    }
}
?>