<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * Ueditor文件上传下载管理.
 *
 * FileController description.
 *
 * @version 1.0
 * @author lanxuebao
 */
class UeditorController extends Controller
{
    private $tableName = 'project_file';
    
    public function index()
    {
        $libPath = LIB_PATH."ORG/Ueditor/";
        header("Content-Type: text/html; charset=utf-8");
        $CONFIG = 1;
//        $CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($libPath."config.json")), true);
        $action = $_GET['action'];
        $data = array();

        switch ($action) {
            case 'config':
                $result =  $CONFIG;
                break;
                /* 上传图片 */
            case 'uploadimage':
                /* 上传涂鸦 */
            case 'uploadscrawl':
                /* 上传视频 */
            case 'uploadvideo':
                /* 上传文件 */
            case 'uploadfile':
                $result = include($libPath."action_upload.php");
                break;
                /* 列出图片 */
            case 'listimage':
                if(strtolower(MODULE_NAME) == "mall"){
                    $result = $this->getfiles($action,$CONFIG);
                }else{
                    $result = include($libPath."action_list.php");
                }
                break;
                /* 列出文件 */
            case 'listfile':
                if(strtolower(MODULE_NAME) == "mall"){
                    $result = $this->getfiles($action,$CONFIG);
                }else{
                    $result = include($libPath."action_list.php");
                }
                break;
                /* 抓取远程文件 */
            case 'catchimage':
                $result = include($libPath."action_crawler.php");
                break;
            default:
                $result = json_encode(array( 'state'=> '请求地址出错' ));
                    break;
        }
        
        if($action == 'uploadimage' || $action == 'uploadscrawl'){
            $data['type'] = 1;
        }else if($action == 'uploadvideo'){
            $data['type'] = 2;
        }else if($action == 'uploadfile'){
            $data['type'] = 3;
        }
        
        if(isset($data['type']) && is_array($result) && $result['state'] == 'SUCCESS'){
            $data['url']        = $result['url'];
            $data['ext']        = $result['type'];
            $data['title']      = rtrim($result['original'], $result['type']);
            $data['original']   = $result['original'];
            $data['size']       = $result['size'];
            
            // 获取图片宽高
            if($data['type'] == 1){
                $size = getimagesize($_SERVER['DOCUMENT_ROOT'].$result['url']);
                $data['width'] = $size[0];
                $data['height'] = $size[1];
            }

            $data['url'] = $result['url'] = C('CDN').$result['url'];
            $data['created'] = NOW_TIME;
            
            $login = session('user');
            if($login){
                if($login['project_id']){
                    $data['project_id'] = $login['project_id'];
                }if($login['username']){
                    $data['username'] = $login['username'];
                }
                $Module = M($this->tableName);
                $Module->add($data);
            }
        }
        
        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode(array(
                    'state'=> 'callback参数不合法'
                ));
            }
        } else {
            echo json_encode($result);
        }
    }
    
    /**
     *  获取已上传的文件列表
     * @param unknown $action
     * @param unknown $CONFIG
     * @return multitype:string unknown
     */
    private function getfiles($action,$CONFIG){
        $login = session('user');
        $where = "project_id='{$login['project_id']}'";
        /* 判断类型 */
        switch ($action) {
            /* 列出文件 */
            case 'listfile':
                $allowFiles = $CONFIG['fileManagerAllowFiles'];
                $listSize = $CONFIG['fileManagerListSize'];
                $path = $CONFIG['fileManagerListPath'];
                break;
                /* 列出图片 */
            case 'listimage':
                $where = " AND type=1";
            default:
                $allowFiles = $CONFIG['imageManagerAllowFiles'];
                $listSize = $CONFIG['imageManagerListSize'];
                $path = $CONFIG['imageManagerListPath'];
        }
        $allowFiles = "'".implode("','", $allowFiles)."'";
        
        $Module = M($this->tableName);
        
        $total = $Module->where($where)->count();
        $list = $Module->field("url, width, height")->where($where)->order("id DESC")->limit($_GET['start'], $_GET['size'])->select();
        
        /* 返回数据 */
        $result = array(
            "state" => "SUCCESS",
            "list" => $list,
            "start" => $_GET['start'],
            "total" => $total
        );
        
        return $result;
    }
}
?>