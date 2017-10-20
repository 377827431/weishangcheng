<?php
/**
 * solr数据操作类
 */
class phpSolr{

    //solr服务器地址及端口设置
    private static $options = array('hostname' => '127.0.0.1','port' => '8080');

    /**
     * 设置solr库选择
     * @param $core string 库名称
     */
    public static function setCore($core){
        if($core) self::$options['path']='solr/'.$core;
    }

    public static function addDocument($fieldArr=array()){
        $client = new SolrClient(self::$options);
        $doc = new SolrInputDocument();
        foreach($fieldArr as $k => $v){
            $doc->addField($k,$v);
        }
        $client->addDocument($doc);
        $client->commit();
        return true;
    }

    public static function delDocument($ids){
        $client = new SolrClient(self::$options);
        if(is_array($ids))
            $client->deleteByIds($ids);
        else
            $client->deleteById($ids);
        $client->commit();
        return true;
    }

    public static function selectQuery($qwhere=array(),$fqwhere=array(),$getField=array(),$sort=array(),$pageindex=1,$pagesize=2){
        $client = new SolrClient(self::$options);
        $query = new SolrQuery();
        $sel = '';
        foreach($qwhere as $k => $v){
            $sel .= ' +'.$k.':'.$v;        //对中文分词的field用这行
//        $sel = "{$k} : \"*{$v}*\"";    //对英文貌似$v两侧加*号就能模糊搜索了
        }
        $query->setQuery($sel);
        //关键字检索

        //附加条件，根据范围检索，适用于数值型
        if($fqwhere){
            $query->setFacet(true);
            foreach($fqwhere as $k => $v)
                $query->addFacetQuery($v);
            //$query->addFacetQuery('price:[* TO 500]');
        }

        //查询字段
        if($getField){
            foreach($getField as $key => $val)
                $query->addField($val);
        }
        //排序
        if($sort){
            foreach($sort as $k => $v){
                if($v == 'asc')
                    $query->addSortField($k,SolrQuery::ORDER_ASC);
                elseif($v == 'desc')
                    $query->addSortField($k,SolrQuery::ORDER_DESC);
            }
        }
        $query->setGroup(true);
        $query->addGroupField('tid');
        $query->setGroupLimit(1);
        //分页
        $query->setStart(self::getPageIndex($pageindex,$pagesize));
        $query->setRows($pagesize);

        $query_response = $client->query($query);
        $response = $query_response->getResponse();
        return $response;
    }

    private static function getPageIndex($pageindex,$pagesize){
        if($pageindex<=1)
            $pageindex = 0;
        else
            $pageindex = ($pageindex-1)*$pagesize;
        return $pageindex;
    }

}