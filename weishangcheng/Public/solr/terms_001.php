<?php
echo "<pre>";
include "bootstrap.php";

$options = array(
    'hostname' => SOLR_SERVER_HOSTNAME,
    'login'    => SOLR_SERVER_USERNAME,
    'password' => SOLR_SERVER_PASSWORD,
    'port'     => SOLR_SERVER_PORT,
    'path'     => SOLR_SERVER_PATH,
);

$client = new SolrClient($options);
$query = new SolrQuery();
$query->setTerms(true);
$query->setTermsField('id');
$updateResponse = $client->query($query);
print_r($updateResponse->getResponse());
