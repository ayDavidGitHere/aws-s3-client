<?php

// Include the AWS SDK using the Composer autoloader
require ABSPATH . '/vendor/autoload.php';
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
define('AWS_KEY', '');
define('AWS_SECRET_KEY', '');
define('REGION', '');












use JMathai\S3BucketStreamZip\S3BucketStreamZip;
use JMathai\S3BucketStreamZip\Exception\InvalidParameterException;
if(!function_exists("streamAsZip")){
function streamAsZip() {
  set_time_limit(0);
  
  // $params
  $bucket = '';
  $foldername = "";
  $stream = new S3BucketStreamZip(
    
              // $auth
              array(
                'key'    => '',
                'secret' => '',
                'version' => 'latest',
                'region'  => 'us-east-1',
              //),
              //array(
                'bucket'  => $bucket,  // required
                'prefix'  => "$foldername/"// optional (path to folder to stream)
              )
            );
  
  $stream->send("$foldername.zip");
  error_log("downloading zip  ...");
}}


if(!function_exists("listBuckets")){
function listBuckets(){
  $buckets = $client->listBuckets();
  error_log("__B___" . json_encode($buckets) . "__B__");
  try {
    foreach ($buckets['Buckets'] as $bucket){
        error_log("{$bucket['Name']}\t{$bucket['CreationDate']}\n");
    }
  } catch (S3Exception $e) {
      error_log($e->getMessage() . "\n");
  }
}}
if(!function_exists("logParams")){
function logParams($objects){
    foreach ($objects as $key => $what) {
      error_log("£: " . json_encode($key) . " - " . gettype($what));
    }
}}
if(!function_exists("getContent")){
function getContent($objects, $listObjectsParams){
    $contents = [];
    $filterByText = $listObjectsParams["FilterByText"] ;
    foreach ($objects['Contents']??[] as $object){
        $object_key = $object['Key'];
        //error_log($object_key);
        if (substr(strrchr($object['Key'], '/'), 1, 1) === '.') 
        continue; // Filter By Starts with "."
        $tmp = explode('/', $object['Key']); //error_log($filterByText);
        if ( !(stripos(end($tmp), $filterByText) !== false) ? true : false)
        continue; //Filter By Text
        $contents[] = $object_key;
    }
    return $contents;
}}
if(!function_exists("getSize")){
function getSize($objects){
    $totalSize = 0;
    //logParams($objects);
    foreach ($objects as $object){
        $totalSize += $object['Size'];
    }
    return $totalSize;
}}
if(!function_exists("getCommonPrefixes")){
function getCommonPrefixes($objects){ 
    $foldernames = [];//error_log(logParams($objects));
    foreach ($objects['CommonPrefixes']??[] as $cp){
        $foldernames[] = $cp['Prefix'];
    }
    return $foldernames;
}}
if(!function_exists("getCommonPrefixesFromContent")){
function getCommonPrefixesFromContent($content){ 
    $subfoldernames = [];
    foreach ($content as $key) {
        $folders = explode('/', $key);
        if (count($folders) > 1) {
            $subfolder = $folders[1] . '/';
            if (!in_array($subfolder, $subfoldernames)) {
                array_push($subfoldernames, $subfolder);
            }
        }
    }
    error_log(json_encode($subfoldernames));
    return $subfoldernames;
}}













if(!function_exists("queryAWS")){
function queryAWS($product_id, $action="getfoldersnames", $params = []){
    // Set up the S3 client with your AWS credentials
    $client = new S3Client([
        'version' => 'latest',
        'region'  => 'us-east-1',
        'credentials' => [
            'key'    => '',
            'secret' => '',
        ],
    ]);
    
    
    
    
    
    // Set the bucket name and prefix (if any)
    $bucket = '';
    if($product_id == 149) $bucket = '';
    // Use the listObjectsV2 method to get the objects in the bucket with the specified prefix
    try {
        $listObjectsParams = [
            'Bucket' => $bucket,
        ];
        if($action==="getfoldersnames"){
            $listObjectsParams['Prefix'] = "";
            $listObjectsParams['Delimiter'] = "/";
        }
        if($action==="getfoldersubfolders"){ 
          error_log("getfoldersubfolders" .  $params["folder"]);
            $listObjectsParams['Prefix'] = $params["folder"]. "/";
            $listObjectsParams['Delimiter'] = "/";
        }
        if($action==="getfolderdata"){
            $listObjectsParams['Prefix'] = "" . $params["folder"] . "/";
            if(isset($params["maxlength"]))
            $listObjectsParams['MaxKeys'] = $params["maxlength"];
            if(isset($params["StartAfterKey"]))
            $listObjectsParams['StartAfter'] = $params["StartAfterKey"];
            if(isset($params["FilterByText"]))
            $listObjectsParams['FilterByText'] = $params["FilterByText"];
        }
        if($action==="getfolderkeycount"){
            $listObjectsParams['Prefix'] = "" . $params["folder"] . "/";
            //$listObjectsParams['Delimiter'] = "/";
            $listObjectsParams['MaxKeys'] = 0;
        }
        if($action==="getfolderdownload"){ 
          $listObjectsParams = [];
          $listObjectsParams['Bucket'] = "" . $bucket;
          $listObjectsParams['Prefix'] = "" . $params["folder"] . "/";
          if(isset($params["maxlength"]))
          $listObjectsParams['MaxKeys'] = $params["maxlength"];
          $listObjectsParams['StartAfter'] = $params["StartAfterKey"] ?? "";
          $listObjectsParams['FilterByText'] = $params["FilterByText"] ?? "";
        }
        //ESCAPE
        if(isset($listObjectsParams['Prefix'])) {
            $listObjectsParams['Prefix'] = str_ireplace("\'", "'", $listObjectsParams['Prefix']); //remove escape
            //error_log("Prefix: " . $listObjectsParams['Prefix']);
        }
        if(isset($listObjectsParams['StartAfter'])) {
            $listObjectsParams['StartAfter'] = str_ireplace("\'", "'", $listObjectsParams['StartAfter']); //remove escape
            //error_log("StartAfter: " . $listObjectsParams['StartAfter']);
        }
        if(isset($listObjectsParams['FilterByText'])) {
            $listObjectsParams['FilterByText'] = str_ireplace("\'", "'", $listObjectsParams['FilterByText']); //remove escape
            //error_log("FilterByText: " . $listObjectsParams['FilterByText']);
        }
        
        
        
        
        if($action==="getfolderdownload"){ 
          return [
            "data" => [$params["folder"] => getFolderDownloadLink($client, $listObjectsParams)],
          ];
        }
        //error_log(json_encode($listObjectsParams));
        $objects = $client->listObjectsV2($listObjectsParams);
        
        
        
        
        if($action==="getfoldersnames") {
            return [
              "data" => getCommonPrefixes($objects),
            ];
        }
        if($action==="getfoldersubfolders") {
            return [
              "data" => getCommonPrefixes($objects),
            ];
        }
        if($action==="getfolderdata") {
            $foldercontents = getContent($objects, $listObjectsParams);
            return [
              "data" => [$params["folder"] => $foldercontents, "keycountwithoutfilter" => count($objects['Contents']??[])],
            ];
        }
        if($action==="getfolderkeycount") {
            return [
              "data" => [$params["folder"] => $objects["KeyCount"]],
            ];
        }
        
        
        
        
        
        
        
        
        
    } catch (AwsException $e) {
        // Handle the exception
        error_log( $e->getMessage() );
        return [];
    }
}}

if(!function_exists('getFolderDownloadLink')){
function getFolderDownloadLink($client, $listObjectsParams) {
    try {
        
        $bucketname = $listObjectsParams["Bucket"];
        $foldername = $listObjectsParams["Prefix"];
        $startafter = $listObjectsParams["StartAfter"];
        $filterByText = $listObjectsParams["FilterByText"] ?? "";
        $filterByText = htmlspecialchars($filterByText, ENT_QUOTES, 'UTF-8');
        /*error_log("getFolderDownloadLink " .$filterByText);
        error_log("getFolderDownloadLink " .$foldername);
        error_log("getFolderDownloadLink " .$startafter);*/
        //error_log("... getFolderDownloadLink ..." . gmdate("Y M d H i s"));
        //error_log(json_encode($listObjectsParams));
        // Get all object keys for the folder
        $result = $client->listObjectsV2($listObjectsParams);
        //error_log("... listObjects ..." . gmdate("Y M d H i s"));
        
    
    
        $objectindex = 0;
        $urlsAsync = [];
        foreach ($result['Contents'] as $object) {
            $object_key = $object['Key'];
            //error_log($object_key);
            // Skip directories
            if (substr($object_key, -1) === '/') {
                continue;
            }
            if (substr(strrchr($object_key, '/'), 1, 1) === '.') {
                continue;
            }
            $tmp = explode('/', $object_key); //error_log($filterByText);
            $object_name = end($tmp);/*
            if(substr($object_name,-5) === substr($filterByText,-5)) {
              error_log($object_name);
              error_log($filterByText);
            }*/
            if ( !(stripos($object_name, $filterByText) !== false) ? true : false) {
                continue;
            }
            
            
            $objectindex++;
            if($objectindex <0 || $objectindex > $listObjectsParams['MaxKeys']) {
                continue;
            }
            
            
            //continue;
            
            /*
            $object_data = $client->getObject([
                'Bucket' => $bucketname,
                'Key' => $object_key
            ]);*/
            $cmd = $client->getCommand('GetObject', [
                'Bucket' => $bucketname,
                'Key' => $object_key,
                //'ResponseContentDisposition' => mb_convert_encoding("attachment; filename=$object_name", 'HTML-ENTITIES', 'UTF-8'),
                'ResponseContentDisposition' => urlencode("attachment; filename=$object_name"),
            ]);
            $request = $client->createPresignedRequest($cmd, '+7 days');
            $urlValue = (string) $request->getUri();
            //error_log($urlValue);
            $url = [$urlValue, $object_key];
            $urlsAsync[] = $url;
        }
        $urls = $urlsAsync;
        error_log(json_encode($urls));
        //error_log(gmdate("Y M d H i s"));
        return $urls;
    } catch (AwsException $e) {
        return null;
    }
}}







