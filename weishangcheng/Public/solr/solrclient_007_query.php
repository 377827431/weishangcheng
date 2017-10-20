<?php

echo "<pre>";
include "bootstrap.php";

$options = array
(
    'hostname' => SOLR_SERVER_HOSTNAME,
    'login'    => SOLR_SERVER_USERNAME,
    'password' => SOLR_SERVER_PASSWORD,
    'port'     => SOLR_SERVER_PORT,
    'path'     => SOLR_SERVER_PATH,
);

$client = new SolrClient($options);

$query = new SolrQuery();

$query->setQuery('腰');
$query->setStart(0);
$query->setRows(50);

$query->addField('id')->addField('title')->addField('outer_id')->addField('tao_id');

$query_response = $client->query($query);

$response = $query_response->getResponse();

print_r($response);
