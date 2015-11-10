<?php

require __DIR__.'/../vendor/autoload.php';

use Dropbox\Client as DropboxClient;
use NaturalWeb\FileStorage\Storage\DropboxStorage;

$token = '';
$app = '';
$dropbox = new DropboxClient($token, $app);



// var_dump($dropbox->getAccountInfo());
$metadata = $dropbox->getMetadataWithChildren('/consultaleiloes/uploads');

var_dump($metadata);

if (isset($metadata['contents']) && is_array($metadata['contents'])) {
  $contents = $metadata['contents'];
  // var_dump($contents);
  $files = array_filter($contents, function($file){
    return $file['is_dir'] != 1;
  });

  $files = array_map(function($file){
    return $file['path'];
  }, $files);

  var_dump($files);

}


// $storage = new DropboxStorage('consultaleiloes', $dropbox);
// var_dump($storage->files('userfiles/'));

