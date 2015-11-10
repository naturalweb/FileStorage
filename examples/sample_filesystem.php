<?php
require __DIR__.'/../vendor/autoload.php';

use NaturalWeb\FileStorage\Storage\FileSystemStorage;

$root = "/home/renato/Downloads/testes";
$storage = new FileSystemStorage($root);

//---- DELETE FOLDER -----------
// $folder = "foobar";
// if ($storage->deleteFolder($folder)) {
//     printf('PASTA "%s" CRIADA COM SUCESSO!%s', $folder, PHP_EOL);
// }

//---- CREATE FOLDER -----------
// $folder = "foobar";
// if ($storage->createFolder($folder)) {
//     printf('PASTA "%s" CRIADA COM SUCESSO!%s', $folder, PHP_EOL);
// }

//---- RENAME -----------
// $old = "new-bar/";
// $new = "foobar/";
// if ($storage->rename($old, $new)) {
//     printf('RENOMEADO "%s" => "%s" COM SUCESSO!%s', $old, $new, PHP_EOL);
// }

//--- LIST FILES ------
// $folder = "foobar/";
// $files = $storage->files($folder);
// var_dump($files);

// $folder = "userfiles/";
// $list = $storage->listObjects($folder);
// var_dump($list);