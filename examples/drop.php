<?php

require __DIR__.'/vendor/autoload.php';

use Dropbox\Client as DropboxClient;

$token = 'KBX87Dt80DcAAAAAAAAAGAsOTb0KrkX1mXvPyoi6k8L1q0cbSnM2oBS-SrUAI19-';
$app = 'Getup.io/1.0';
$dropbox = new DropboxClient($token, $app);

var_dump($dropbox->getAccountInfo());
$metadata = $dropbox->getMetadataWithChildren('/consultaleiloes/uploads');

if (isset($metadata['contents']) && is_array($metadata['contents'])) {
  $contents = $metadata['contents'];
  $files = array_filter($contents, function($file){
    return $file['is_dir'] != 1;
  });

  $files = array_map(function($file){
    return $file['path'];
  }, $files);

  var_dump($files);

}
