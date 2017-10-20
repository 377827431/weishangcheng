<?php
namespace Seller\Controller;
use Think\Controller;
/*
 * 推广员招募分享文件
 * 
 */
class ShareController extends Controller{

	public function index()
        {
                $id = I('get.id','','int');
                $share_mid = I('get.share_mid','','int');
                $projectId = substr($id,0,-3);
        	$recruit = M('shop_info')->field('id,recruit_title,recruit_content')->find($id);
                $redirect = parse_url(C('PROTOCOL').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
                $project = M('project')->field('alias')->find($projectId);
                $url = $redirect['scheme'].'://'.$redirect['host'].'/'.$project['alias'].'/login?apply='.$id.'&share_mid='.$share_mid;
                $this->assign('url',$url);
                $this->assign('recruit',$recruit);
                $this->display('join_text_show');
	}
}

?>