<?php 
namespace Common\Model;

use Think\Model;
use Org\PinYin;

/**
 * 
 * @author Administrator
 * 
 * 101团购返现;102零元购;103限时折扣;104满减
 * 
 * 201区间价;202会员价;203积分兑换
 * 
 * 301包邮;302新品
 */
class TagModel extends Model{
    protected $tableName = 'mall_tag';
    protected $projectId = 0;
    
    public function __construct($projectId){
        parent::__construct();
        
        if(!is_numeric($projectId)){
            E(__CLASS__.'的projectId必须');
        }
        $this->projectId = $projectId;
    }
    
    public function getProjectId(){
        return $this->projectId;
    }
    
    public function find($id){
        $this->where("project_id={$this->projectId}");
        return parent::find($id);
    }
    
    public function adminList(){
        $sql = "SELECT id, `name`, pid, icon, sort, created, `level`, is_last, goods_quantity
                    FROM {$this->tableName}
                    WHERE project_id='{$this->projectId}'
                    ORDER BY sort DESC, id";
        $list = $this->query($sql);
        $rows = array();
        foreach ($list as $item){
            $rows[$item['pid']][] = $item;
        }
        
        // 排序后展示
        $list = array();
        $this->appendChildren($rows, 0, $list);
        return $list;
    }
    
    private function appendChildren($rows, $pid, &$list){
        if(!isset($rows[$pid])){
            return;
        }
        
        foreach ($rows[$pid] as $item){
            $list[] = $item;
            $this->appendChildren($rows, $item['id'], $list);
        }
    }
    
    public function add($data){
        ignore_user_abort(1);
        set_time_limit(180);
        
        $parent = null;
        if($data['pid'] > 0){
            $parent = $this->field("id, pid, level, parent_ids")->find($data['pid']);
            if(!$parent){
                $this->error = 'PID不存在';
                return -1;
            }
            
            $data['level'] = $parent['level'] + 1;
            $data['parent_ids'] = $parent['parent_ids'].($parent['parent_ids'] ? ',' : '').$parent['id'];
        }
        
        $pinyin = new PinYin();
        $data['pinyin'] = $pinyin->getAllPY($data['name']);
        $data['project_id'] = $this->projectId;
        $data['created'] = date('Y-m-d H:i:s');
        $data['is_last'] = 1;
        
        $id = parent::add($data);
        
        if($id < 1){
            $this->error = '添加失败';
            return -1;
        }
        
        if($parent){
            $parents = ($parent['parent_ids'] ? $parent['parent_ids'].',' : '').$parent['id'];
            $this->appendChild($parents, $id, 0);
        }
        return $id;
    }
    
    private function appendChild($parentIds, $append, $goodsQuantity){
        if(!$parentIds || !$append){
            return;
        }
        $this->execute("UPDATE {$this->tableName} SET child_ids=CONCAT(child_ids, IF(child_ids='', '', ','), '{$append}'), is_last=0, goods_quantity=goods_quantity+{$goodsQuantity} WHERE id IN ({$parentIds})");
    }
    
    public function save($data, $old){
        ignore_user_abort(1);
        set_time_limit(180);
        
        $parent = null;
        if($data['pid'] > 0){
            $parent = $this->find($data['pid']);
            $data['level'] = $parent['level'] + 1;
            $data['parent_ids'] = ($parent['parent_ids'] ? $parent['parent_ids'].',' : '').$parent['id'];
        }else{
            $data['parent_ids'] = '';
            $data['level'] = 1;
        }
        
        $pinyin = new PinYin();
        $data['pinyin'] = $pinyin->getAllPY($data['name']);
        $data['is_last'] = $old['child_ids'] ? 0 : 1;
        
        $result = parent::save($data);
        if($result < 1){
            return;
        }
        
        if($parent){
            $childId = $data['id'].($data['child_ids'] ? ','.$data['child_ids'] : '');
            $parents = ($parent['parent_ids'] ? $parent['parent_ids'].',' : '').$parent['id'];
            $this->appendChild($parents, $childId, $old['goods_quantity']);
        }
        
        // 从旧数据的上级中去除我和我的下级
        if($old['parent_ids']){
            $replace = $old['id'].($old['child_ids'] ? ','.$old['child_ids'] : '');
            $res = $this->field("id, child_ids")->where("id IN({$old['parent_ids']})")->select();
            $replace = explode(',', $replace);
            $setId = array();
            foreach ($res as $v){
                $child_ids = $v['child_ids'] == '' ? null : explode(',', $v['child_ids']);
                foreach ($child_ids as $kk => $vv){
                    if (in_array($vv, $replace)){
                        unset($child_ids[$kk]);
                    }
                }
                $setId[$v['id']] = empty($child_ids) ? '' : implode(',', $child_ids);
            }
            if (!empty($setId)){
                $sql = "UPDATE {$this->tableName} SET child_ids = CASE id ";
                foreach ($setId as $k => $v){
                    $sql .= " WHEN '".$k."' THEN '".$v."'";
                }
                $sql .= " END, is_last=IF(child_ids='', 1, 0), goods_quantity=goods_quantity-{$old['goods_quantity']} WHERE id IN({$old['parent_ids']})";
                $this->execute($sql);
            }
        }

        if($parent){
            $childId = $data['id'].($data['child_ids'] ? ','.$data['child_ids'] : '');
            $parents = ($parent['parent_ids'] ? $parent['parent_ids'].',' : '').$parent['id'];
            $this->appendChild($parents, $childId, $old['goods_quantity']);
        }
        
        // 重建我的下级的上级关系
        if($old['child_ids']){
            $upLevel = $data['level'] - $old['level'];
            $res = $this->field("id, parent_ids")->where("id IN({$old['child_ids']})")->select();
            $replace = explode(',', $old['parent_ids']);
            $setId = array();
            foreach ($res as $v){
                $parent_ids = $v['parent_ids'] == '' ? null : explode(',', $v['parent_ids']);
                foreach ($parent_ids as $kk => $vv){
                    if (in_array($vv, $replace)){
                        unset($parent_ids[$kk]);
                    }
                }
                $setId[$v['id']] = empty($parent_ids) ? '' : implode(',', $parent_ids);
            }
            if (!empty($setId)){
                $sql = "UPDATE {$this->tableName} SET parent_ids = CASE id ";
                foreach ($setId as $k => $v){
                    $sql .= " WHEN '".$k."' THEN '".$v."'";
                }
                $add = '';
                if($data['parent_ids']){
                    $add .= ", parent_ids = CONCAT('{$data['parent_ids']}', ',', parent_ids) ";
                }
                $sql .= " END, `level`=`level`+{$upLevel}{$add} WHERE id IN({$old['child_ids']})";
                $this->execute($sql);
            }
        }
        return $result;
    }
    
    public function getAll(){
        $list = array();
        $_list = $this->query("SELECT id, `name`, level, pid, icon FROM {$this->tableName} WHERE project_id='{$this->projectId}' ORDER BY sort DESC, id");
        
        foreach ($_list as $i=>$item){
            $list[$item['pid']][] = $item;
        }
        
        return $list;
    }
    
    public function delete($id){
        $id = addslashes($id);
        $where = "id IN ({$id}) AND project_id=".$this->projectId;
        $list = $this->where($where)->select();
        foreach ($list as $item){
            if($item['child_ids']){
                $this->error = '请先删除['.$item['name'].']下的子分组，再删除本分组';
                return -1;
            }
        }
        foreach ($list as $item){
            if ($item['parent_ids']){
                $replace = $item['id'].($item['child_ids'] ? ','.$item['child_ids'] : '');
                $res = $this->field("id, child_ids")->where("id IN({$item['parent_ids']})")->select();
                $replace = explode(',', $replace);
                $setId = array();
                foreach ($res as $v){
                    $child_ids = $v['child_ids'] == '' ? null : explode(',', $v['child_ids']);
                    foreach ($child_ids as $kk => $vv){
                        if (in_array($vv, $replace)){
                            unset($child_ids[$kk]);
                        }
                    }
                    $setId[$v['id']] = empty($child_ids) ? '' : implode(',', $child_ids);
                }
                if (!empty($setId)){
                    $sql = "UPDATE {$this->tableName} SET child_ids = CASE id ";
                    foreach ($setId as $k => $v){
                        $sql .= " WHEN '".$k."' THEN '".$v."'";
                    }
                    $sql .= " END, is_last=IF(child_ids='', 1, 0), goods_quantity=goods_quantity-{$item['goods_quantity']} WHERE id IN({$item['parent_ids']})";
                    $this->execute($sql);
                }
            }
        }
        return $this->execute("DELETE FROM {$this->tableName} WHERE ".$where);
    }
}
?>