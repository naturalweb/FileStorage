<?php
require __DIR__.'/../vendor/autoload.php';

use Aws\S3\S3Client;
use NaturalWeb\FileStorage\Storage\S3Storage;

define('AWS_KEY', '');
define('AWS_SECRET', '');

$clientS3 = S3Client::factory(array(
    'key'    => AWS_KEY,
    'secret' => AWS_SECRET
));

$bucket = "consultaleiloes";
$storage = new S3Storage($bucket, $clientS3);

$operationParams = array(
    'Bucket' => 'consultaleiloes',
    'Prefix' => 'userfiles/images/',
    'Delimiter' => "/",
);

$objectIterator = $clientS3->getIterator('ListObjects', $operationParams, array(
    'return_prefixes' => true,
    'sort_results' => true
));

foreach ($objectIterator as $key => $value)
{
    if (isset($value['Prefix']))
    {
        var_dump($value['Prefix']);
    } else {
        var_dump($value['Key']);
    }
}

//---- CREATE FOLDER -----------
// $folder = "foobar";
// if ($storage->createFolder($folder)) {
//     printf('PASTA "%s" CRIADA COM SUCESSO!%s', $folder, PHP_EOL);
// }

//---- RENAME -----------
// $old = "new-foo-bar/";
// $new = "foobar/";
// if ($storage->rename($old, $new)) {
//     printf('RENOMEADO "%s" => "%s" COM SUCESSO!%s', $old, $new, PHP_EOL);
// }

//--- LIST FILES ------
// $folder = "userfiles/";
// $files = $storage->files($folder);
// var_dump($files);

// $folder = "userfiles/";
// $list = $storage->listObjects($folder);
// var_dump($list);
