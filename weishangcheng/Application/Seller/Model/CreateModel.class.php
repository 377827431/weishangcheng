<?php
/**
 * Created by PhpStorm.
 * User: jy
 * Date: 2017/4/7
 * Time: 9:33
 */

namespace Seller\Model;
use Common\Model\BaseModel;

class CreateModel extends BaseModel{
    protected $tableName = 'shop';

    public function create_ps($mid, $name){
        $project = $this->table('project')->where("manager_id = {$mid}")->find();
        if (!empty($project)){
            return $this->c_shop($project['id'], $mid, $name);
        }else{
            $appid = C('DEFAULT_APPID');
            $project = M('project')->field('host')->join('project_appid on project_appid.id = project.id')->where(array('appid' => $appid))->find();
            $add = array(
                "host" => $project['host'],
                "manager_id" => $mid,
                "name" => $mid,
            );
            $id = M('project')->add($add);
            if ($id > 0){
                $up = array(
                    "alias" => $id,
                );
                M('project')->where("id = {$id}")->save($up);
                $add = array(
                    "alias" => $id,
                    "id" => $id,
                );
                M('project_appid')->add($add);
                return $this->c_shop($id, $mid, $name);
            }
        }
        return 0;
    }

    public function c_shop($project_id, $mid, $name){
        $shop000 = $project_id * 1000;
        $res = $this->field('1')->where("id = {$shop000}")->find();
        if (empty($res)){
            $add = array(
                "id" => $shop000,
                "mid" => $mid,
            );
            M('shop')->add($add);
        }
        $like = $project_id.'%';
        $shops = $this->field('max(id) as id')->where("id like '{$like}'")->find();
        if ($shops['id'] > 0){
            $shopId = $shops['id'] + 1;
        }else{
            $shopId = $project_id * 1000 + 1;
        }
        $add = array(
            "id" => $shopId,
            "name" => $name,
            "mid" => $mid,
            "logo" => I('post.logo'),
        );
        session('manager.shop', $shopId);
        return M('shop')->add($add);
    }

}
?>