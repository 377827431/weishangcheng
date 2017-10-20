<?php
namespace Admin\Model;

use Think\Model;

/**
 * 高级图文
 * @author wangjing
 *
 */
class AdvancedNewsModel extends Model
{
    protected $tableName = 'wx_news';
    
    /*
     * 高级图文列表
     */
    public function getAll($where, $offset, $limit){
    	$where['pid'] = 0;
        $rows = array();
        $total = $this->where($where)->count();
        
        if($total > 0){
            $rows1 = $this->where($where)->order("id desc")->limit($offset, $limit)->select();
            
            $pids = '';
            foreach ($rows1 as $i=>$item){
            	$item['items'] = array();
            	$rows[$item['id']] = $item;
            	$pids .= $item['id'].',';
            }
            $pids = rtrim($pids, ',');
            $where['pid'] = array('in', $pids);
            
            $rows2 = $this->where($where)->order("id")->select();
            foreach($rows2 as $item){
            	$rows[$item['pid']]['items'][] = $item;
            }
        }
        return $data = array("total" => $total, "rows" => $rows);
    }
    
    /*
     * 添加图文
     */
    public function insert($list, $projectId = 0){
        $created = date("Y-m-d H:i:s");
        
        $pid = 0;
        foreach ($list as $i=>$item){
        	$item['project_id'] = $projectId;
        	$item['pid']        = $pid;
        	$item['created']    = $created;
        	$id = $this->add($list);
        	if($i == 0){
        		$pid = $id;
        	}
        }
        return '添加成功！';
    }
    
    /*
     * 编辑图文
     */
    public function update($oldList, $newList, $projectId){
    	$pid = 0;
    	$created = date("Y-m-d H:i:s");
    	$projectId = $oldList[0]['project_id'];
    	$id = $oldList[0]['id'];
    	$existsIds = array();
    	
    	$this->startTrans();
    	foreach ($newList as $i=>$item){
    		$item['pid'] = $pid;
    		if(is_numeric($item['id'])){
    			if(($item['pid'] == 0 && $item['id'] != $id) || $item['pid'] != $id){
    				return -1;
    			}
    			$this->save($item);
    		}else{
    			$item['created'] = $created;
    			$item['project_id'] = $projectId;
    			$item['id'] = $this->add($item);
    		}
    		
    		if($i == 0){
    			$pid = $item['id'];
    		}
    		
    		$existsIds[] = $item['id'];
    	}
    	
    	$deletes = '';
    	foreach($oldList as $item){
    		if(!in_array($item['id'], $existsIds)){
    			$deletes .= $item['id'].',';
    		}
    	}
    	if($deletes != ''){
    		$deletes = rtrim($deletes, ',');
    		$this->execute("DELETE FROM {$this->tableName} WHERE id IN({$deletes})");
    	}
    	$this->commit();
        return 1;
    }
    
    /*
     * 获取单条图文
     */
    public function getById($id, $projectId){
    	$rows = array();
    	$sql  = " SELECT * FROM wx_news WHERE id={$id}";
    	$sql .= " UNION ALL ";
    	$sql .= " SELECT * FROM wx_news WHERE project_id='{$projectId}' AND pid={$id}";
    	$sql .= " ORDER BY id";
    	$list = $this->query($sql);
        return $list;
    }
}
?>