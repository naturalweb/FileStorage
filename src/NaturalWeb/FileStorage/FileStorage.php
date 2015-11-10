<?php
namespace NaturalWeb\FileStorage;

use \Exception;
use NaturalWeb\FileStorage\Storage\StorageInterface;

/**
 * Tratamento dos Arquivos nos Storages
 */
class FileStorage
{
    /**
     * Registra o ultimo Error
     * @var Exception
     */
    protected $error;

    /**
     * Class Responsavel pelo storage dos arquivos
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Construct, recebe o storage
     * 
     * @param StorageInterface $storage Storage dos Arquivos
     */
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Retorna o Storage dos arquivos
     * 
     * @return StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Salva o arquivo no storage
     * 
     * @param string $name     Name Original
     * @param string $source   Source File
     * @param string $folder   Remote Folder
     * @param bool   $override Override?
     * 
     * @return array [name,path,url]|false
     */
    public function save($name, $source, $folder, $override = false)
    {
        $return = false;

        try {
            if (!$this->storage->exists($folder)) {
                $this->storage->createFolder($folder);
            }

            $path = $this->setFilename($name, $folder, $override);
            
            $this->storage->upload($source, $path, $override);

            $return = array(
                'name' => $name,
                'path' => $path,
                'url'  => $this->storage->getUrl($path),
            );
            $this->error = null;

        } catch(Exception $e) {
            $this->error = $e;
            $return = false;
        }
        return $return;
    }

    /**
     * Return Message Error
     * 
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Crypt filename and concat with folder
     * 
     * @param string $name   Name Original, Return Referer
     * @param string $folder Remote Folder
     * 
     * @return string
     * @throws
     */
    protected function setFilename(&$name, $folder, $override = false)
    {
        $folder = trim($folder, '/');
        $extension = strrchr($name, '.');
        $len = mb_strlen($name) - mb_strlen($extension);
        $nameOrig = substr($name,0,$len);
        $num = 0;
        do {
            if (!$override && $num > 0) {
                $name = "{$nameOrig}-{$num}{$extension}";
            }
            $num++;

            $filename  = "/{$folder}/{$name}";
        } while ($this->storage->exists($filename));

        return $filename;
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param string $method     Method
     * @param array  $parameters Params
     * 
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        try {
            $this->error = null;
            $storage = $this->getStorage();
            return call_user_func_array(array($storage, $method), $parameters);

        } catch(Exception $e) {
            $this->error = $e;
            return false;
        }
    }
}