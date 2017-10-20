<?php
namespace Common\Model;

/**
 * 微信自动回复
 * 
 * @author lanxebao
 *        
 */
class ReplyModel extends BaseModel
{
    protected $tableName = 'wx_reply';
    
    public function getAll($appid){
    	$appid = addslashes($appid);
    	$rows = array();
    	$data = array('total' => 0, 'rows' => $rows);
        
        $data['total'] = $this->where("appid='{$appid}'")->count();
        if($data['total'] == 0){
            return $data;
        }
        
        $offset = I('get.offset/d', 0);
        $limit = I('get.limit/d', 50);
        
        $sql = "SELECT reply.*, kw.keyword, kw.full_match 
                FROM wx_reply AS reply
                LEFT JOIN wx_keyword AS kw ON kw.reply_id=reply.id
                WHERE reply.appid='{$appid}'
				ORDER BY reply.id DESC
				LIMIT {$offset},{$limit}";
        
        $list = $this->query($sql);

        // 组合
        $advanced_ids = "";
        foreach($list as $item){
        	if(!isset($rows[$item['id']])){
            	$contents = json_decode($item['content'], true);
            	$rows[$item['id']] = array(
                    'id'           => $item['id'],
                    'rule'         => $item['rule'],
                    'is_default'   => $item['is_default'],
                    'is_subscribe' => $item['is_subscribe'],
            		'content'      => $contents,
                    'keyword'      => array()
                );
                
            	foreach($contents as $val){
            		if($val["type"] == "senior"){
            			$advanced_ids .= $val["id"].",";
                    }
                }
            }
            
            $rows[$item['id']]['keyword'][] = array('keyword' => $item['keyword'], 'full_match' => $item['full_match']);
        }
        
        // 读取高级图文的标题
        $advanced = array();
        if($advanced_ids != ""){
        	$advanced_ids = rtrim($advanced_ids,',');
            $advanced = $this->query("SELECT id, title FROM wx_news WHERE id IN ({$advanced_ids})");
            $advanced = array_kv($advanced);
        }
        
        foreach($rows as $item){
        	foreach($item["content"] as $i=>$val){
                if($val["type"] == "senior"){
                	$item["content"][$i]["title"] = $advanced[$val["id"]];
                }
        	}
        	
        	$data['rows'][] = $item;
        }
        return $data;
    }
    
    /**
     * 添加自动回复
     */
    public function addReply($data){
        if(empty($data)){
            $this->error = "数据不能为空！";
            return -1;
        }else if(empty($data["keyword"])){
            $this->error = "关键字不能为空！";
            return -1;
        }else if(empty($data["content"])){
            $this->error = "回复内容不能为空！";
            return -1;
        }else if(empty($data['appid']) || $data['appid']== ""){
            $this->error = "微信appid不能为空！";
            return -1;
        }
        
        //关键字唯一验证
        $exists = $this->existsKeyword($data["keyword"], $data['appid']);
        if($exists){
            return -1;
        }
        
        // 无用字段过滤
        $data['content'] = $this->filterContents($data['content']);
        $replyId = $this->add(array(
            "appid"        => $data['appid'],
            "rule"         => $data["rule"],
            "content"      => encode_json($data['content']),
            "is_subscribe" => $data["is_subscribe"],
            "is_default"   => $data["is_default"],
            "is_rand"      => $data['is_rand'],
            "modified"     => date('Y-m-d H:i:s')
        ));
        
        //保存关键字
        $sql = "INSERT INTO wx_keyword(keyword, full_match, reply_id) VALUES";
        foreach($data["keyword"] as $item){
            $sql .= "('".addslashes($item['keyword'])."', '{$item['full_match']}', {$replyId}),";
        }
        $sql = rtrim($sql, ',');
        $this->execute($sql);
        
        //保存wx_reply表信息
        if($data["is_subscribe"] == 1 && $data["is_default"] == 1){
            $this->execute("UPDATE wx_reply SET is_subscribe=0,is_default=0 WHERE id<{$replyId} AND appid='{$data['appid']}'");
        }else if($data["is_subscribe"] == 1){
            $this->execute("UPDATE wx_reply SET is_subscribe=0 WHERE id<{$replyId} AND appid='{$data['appid']}' AND is_subscribe=1");
        }else if($data["is_default"] == 1){
            $this->execute("UPDATE wx_reply SET is_default=0 WHERE id<{$replyId} AND appid='{$data['appid']}' AND is_default=1");
        }
        return 1;
    }

    private function filterContents($list){
        foreach($list as $i=>$item){
            if($item['type'] == 'news'){
                $item = array(
                    'type'     => $item['type'],
                    'media_id' => $item['media_id'],
                    'content'  => $item['content'],
                    'update_time' => $item['update_time']
                );
                foreach ($item['content'] as $c=>$content){
                    $item['content'][$c] = array(
                        'title'  => $content['title'],
                        'digest' => $content['digest'],
                        'url' => $content['url'],
                        'thumb_url' => $content['thumb_url'],
                    );
                }
            }else if($item['type'] == 'senior'){
                
            }else if($item['type'] == 'text'){
                $item = array(
                    'type'    => $item['type'],
                    'id'      => $item['id']
                );
            }else{
                $item = array(
                    'type'     => $item['type'],
                    'media_id' => $item['media_id'],
                    'name'     => $item['name'],
                    'url'      => $item['url']
                );
            }
            $list[$i] = $item;
        }

        return $list;
    }
    
    /**
     * 编辑自动回复
     */
    public function saveReply($data, $replyId){
        if(empty($data)){
            $this->error = "数据不能为空！";
            return -1;
        }else if(empty($data["keyword"])){
            $this->error = "关键字不能为空！";
            return -1;
        }else if(empty($data["content"])){
            $this->error = "回复内容不能为空！";
            return -1;
        }
        
        //关键字唯一验证
        $existsIds = array();
        $exists = $this->existsKeyword($data["keyword"], $data['appid'], $replyId, $existsIds);
        if($exists){
            return -1;
        }
        
        // 无用字段过滤
        $data['content'] = $this->filterContents($data['content']);
        $this->where("id={$replyId}")->save(array(
            "rule"         => $data["rule"],
            "content"      => encode_json($data['content']),
            "is_subscribe" => $data["is_subscribe"],
            "is_default"   => $data["is_default"],
            "is_rand"      => $data['is_rand']
        ));

        //关键字
        $delete = "";
        $insert = "";
        foreach($data["keyword"] as $item){
            $kw = addslashes($item['keyword']);
            $delete .= "'{$kw}',";
            if(array_key_exists($item['keyword'], $existsIds)){
                $exists = $existsIds[$item['keyword']];
                if($item['full_match'] != $exists['full_match']){
                    $this->execute("UPDATE wx_keyword SET full_match={$item['full_match']} WHERE id=".$exists['id']);
                }
            }else{
                $insert .= "('".$kw."', '{$item['full_match']}', {$replyId}),";
            }
        }
        // 删除关键字
        if($delete != ''){
            $delete = rtrim($delete, ',');
            $this->execute("DELETE FROM wx_keyword WHERE reply_id={$replyId} AND keyword NOT IN ({$delete})");
        }
        
        if($insert != ""){
            $insert = "INSERT INTO wx_keyword(keyword, full_match, reply_id) VALUES".rtrim($insert, ',');
            $this->execute($insert);
        }
        return 1;
    }
    
    /**
     * 关键字唯一验证(同公众账号下不能有相同的关键字)
     */
    private function existsKeyword($data, $appid, $replyId = 0, &$result = array()){
        //整理关键字信息
        $keywords = array();
        foreach($data as $v){
            if(!is_numeric($v['full_match'])){
                $this->error = '非法匹配方法';
                return true;
            }else if(!in_array($v['keyword'], $keywords)){
                $keywords[] = addslashes($v['keyword']);
            }else{
                $this->error = '关键字['.$v['keyword'].']重复！';
                return true;
            }
        }
        $keywords = "'".implode("','", $keywords)."'";
        
        $sql = "SELECT wx_keyword.id, wx_reply.id AS reply_id, keyword, full_match
                FROM wx_reply, wx_keyword
                WHERE wx_reply.appid='{$appid}'
                    AND wx_reply.id=wx_keyword.reply_id
                    AND wx_keyword.keyword IN (".$keywords.")";
        $list = $this->query($sql);
        $exists = '';
        foreach ($list as $item){
            if($replyId == 0 || ($replyId > 0 && $item['reply_id'] != $replyId)){
                $exists .= $item['keyword'].'、';
            }else{
                $result[$item['keyword']] = $item;
            }
        }
        
        if($exists == ''){
            return false;
        }
        
        $this->error = "关键字已存在：".rtrim($exists, '、');
        return true;
    }
    
    /**
     * 根据id获取高级图文
     * @param unknown $id
     * @return Ambigous <multitype:, unknown, mixed, boolean, NULL, string, object>
     */
    public function getAdvancedById($id, $projectId){
        $rows = array();
        $sql  = " SELECT * FROM wx_news WHERE id={$id}";
        $sql .= " UNION ALL ";
        $sql .= " SELECT * FROM wx_news WHERE project_id='{$projectId}' AND pid={$id}";
        $sql .= " ORDER BY id";
        $list = $this->query($sql);
        
        $data          = $list[0];
        $data['type']  = 'senior';
        $data['items'] = array();
        $count = count($list);
        for($i=1; $i<$count; $i++){
            $data['items'][] = $list[$i];
        }
        return $data;
    }
    
    /**
     * 根据id获取数据
     */
    public function getById($id, $projectId){
        //获取回复表数据
        $data = $this->find($id);
        if(empty($data)){
            $this->error = "暂无数据！";
            return;
        }
        
        //获取关键字数据
        $keyword = $this->query("SELECT id, keyword, full_match FROM wx_keyword WHERE reply_id=".$id);
        foreach($keyword as $k=>$v){
            $data["keyword"][] = $v;
        }
        
        //获取回复内容数据
        $data["content"] = json_decode($data["content"], true);
        foreach($data["content"] as $i=>$item){
            if($item["type"] == 'senior'){
                $data["content"][$i] = $this->getAdvancedById($item["id"], $projectId);
            }
        }
        return $data;
    }
}
?>