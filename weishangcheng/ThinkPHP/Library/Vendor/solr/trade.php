<?php
/**
 * trade订单tid获取
 */
class trade{

    private static $options = array(
        'hostname' => '127.0.0.1',
        'port' => '8080',
    );

    public static function addDocument($fieldArr=array(), $table = ''){
        if ($table == ''){
            return false;
        }
        self::$options['path'] = 'solr/'.$table;
        $client = new SolrClient(self::$options);
        $doc = new SolrInputDocument();
        foreach($fieldArr as $k => $v){
            $doc->addField($k,$v);
        }
        $client->addDocument($doc);
        $client->commit();
        return true;
    }

    public static function selectTrade($qwhere=array(), $pageindex=1, $pagesize=20){
        self::$options['path'] = 'solr/trade';
        $client = new SolrClient(self::$options);
        $query = new SolrQuery();
        $sel = '';
        if (isset($qwhere['title']) && $qwhere['title'] != ''){
            if(preg_match("/^[a-zA-Z0-9]+$/",$qwhere['title'])){
                $sel = "title : *".$qwhere['title']."*";
            }else{
                $sel .= 'title~'.$qwhere['title'];
            }
        }else{
            $sel .= 'title~*';
        }

        foreach($qwhere as $k => $v){
            $sel .= ' +'.$k.':'.$v;
        }
        $query->setQuery($sel);

        //查询字段
        $query->addField('tid');

        $query->setGroup(true);
        $query->addGroupField('tid');
        $query->setGroupLimit(1);
        //分页
        $query->setStart(self::getPageIndex($pageindex,$pagesize));
        $query->setRows($pagesize);

        $query_response = $client->query($query);
        $response = $query_response->getResponse();
        $ret = array();
        if ($response['responseHeader']['status'] == 0 && is_array($response['grouped']['tid']['groups']) && count($response['grouped']['tid']['groups']) > 0){
            foreach ($response['grouped']['tid']['groups'] as $k => $v){
                $tid = $v['doclist']['docs'][0]['tid'];
                if (!in_array($tid, $ret)){
                    $ret[] = $tid;
                }
            }
        }
        return $ret;
    }

    public static function selectGoods($qwhere=array(), $pageindex=1, $pagesize=20){
        self::$options['path'] = 'solr/mall_goods';
        $client = new SolrClient(self::$options);
        $query = new SolrQuery();
        $sel = '';
        if (isset($qwhere['title']) && $qwhere['title'] != ''){
            if(preg_match("/^[a-zA-Z0-9]+$/",$qwhere['title'])){
                $sel = "title : *".$qwhere['title']."*";
            }else{
                $sel .= 'title~'.$qwhere['title'];
            }
        }else{
            $sel .= 'title~*';
        }
        unset($qwhere['title']);
        foreach($qwhere as $k => $v){
            $sel .= ' +'.$k.':'.$v;
        }
        $query->setQuery($sel);

        //查询字段
        $query->addField('id');
        $query->addField('title');

        //分页
        $query->setStart(self::getPageIndex($pageindex,$pagesize));
        $query->setRows($pagesize);

        $query_response = $client->query($query);
        $response = $query_response->getResponse();
        $ret = array();
        if ($response['responseHeader']['status'] == 0 && is_array($response['response']['docs']) && count($response['response']['docs']) > 0){
            foreach ($response['response']['docs'] as $k => $v){
                $id = $v['title'];
                if (!in_array($id, $ret)){
                    $ret[] = $id;
                }
            }
        }
        return $ret;
    }

    private static function getPageIndex($pageindex,$pagesize){
        if($pageindex<=1)
            $pageindex = 0;
        else
            $pageindex = ($pageindex-1)*$pagesize;
        return $pageindex;
    }

}