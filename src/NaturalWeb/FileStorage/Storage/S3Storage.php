<?php
namespace NaturalWeb\FileStorage\Storage;

use Aws\S3\S3Client;
use \CallbackFilterIterator;
use \Exception;

class S3Storage implements StorageInterface
{

    const ACL_PUBLIC_READ = "public-read";
    const ACL_AUTHENTICATED_READ = "authenticated-read";

    /**
     * @var S3Client
     */
    protected $clientS3;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * Construct
     *
     * @param string $bucket
     * @param S3Client $clientS3
     */
    public function __construct($bucket, S3Client $clientS3)
    {
        $this->bucket = trim($bucket, '/');
        $this->clientS3 = $clientS3;
        $this->expires_seconds = 30 * 365 * 24 * 60 * 60; // 15 anos
    }

    /**
     * Get Bucket
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Get Dropbox
     *
     * @return S3Client
     */
    public function getClient()
    {
        return $this->clientS3;
    }

    /**
     * Save file for storage
     *
     * @param string $source   Source File
     * @param string $path   Remote Path
     * @param bool   $override Override
     *
     * @return bool
     * @throws
     */
    public function upload($source, $path, $override = false)
    {
        $path = trim($path, '/');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type  = finfo_file($finfo, $source);
        finfo_close($finfo);

        $response = $this->clientS3->putObject(array(
            'Bucket'      => $this->bucket,
            'Key'         => $path,
            'SourceFile'  => $source,
            'ACL'         => static::ACL_PUBLIC_READ,
            'ContentType' => $type,
            'CacheControl' => "max-age={$this->expires_seconds}",
            'Expires' => gmdate("D, d M Y H:i:s T", time()+$this->expires_seconds),
        ));

        return true;
    }

    /**
     * Delete remote file
     *
     * @param string $path Remote Path
     *
     * @return bool
     * @throws
     */
    public function delete($path)
    {
        $path = trim($path, '/');

        if (!$this->isFile($path)) {
            $path = $this->bucket . "/" . $path;
            throw new ExceptionStorage("File {$path} Not found or Not Is File", static::E_NOT_IS_FILE);
        }

        $response = $this->clientS3->deleteObject(array(
            'Bucket' => $this->bucket,
            'Key'    => $path,
        ));

        return (bool) $response;
    }

    /**
     * Create Folder
     *
     * @param string $folder Folder
     *
     * @return bool
     * @throws
     */
    public function createFolder($folder)
    {
        $folder = trim($folder, "/") . "/";

        if ($folder === "/") {
            return true;
        }

        // Fail if this pseudo directory key already exists
        if ($this->exists($folder)) {
            return true;
        }

        $response = $this->clientS3->putObject(array(
            'Bucket' => $this->bucket,
            'Key'    => $folder,
            'ACL'    => static::ACL_PUBLIC_READ,
            'Body'   => "",
        ));

        return (bool) $response;
    }

    /**
     * Delete Folder
     *
     * @param string $folder Path folder
     *
     * @return bool
     * @throws
     */
    public function deleteFolder($folder)
    {
        $folder = trim($folder, '/') . '/';

        // Fail if this pseudo directory key already exists
        if (!$this->isDir($folder)) {
            $path = $this->bucket . "/" . $folder;
            throw new ExceptionStorage("Directory {$path} Not found or Not Is Diretory", static::E_NOT_IS_DIR);
        }

        return (bool) $this->clientS3->deleteMatchingObjects($this->bucket, $folder);
    }

    /**
     * Get remote url, return false in case of error
     *
     * @param string $path Remote Path with folder
     *
     * @return string
     */
    public function getUrl($path)
    {
        return $this->clientS3->getObjectUrl($this->bucket, $path);
    }

    /**
     * If path exists
     *
     * @param string $path Remote Path
     *
     * @return bool
     */
    public function exists($path)
    {
        return $this->clientS3->doesObjectExist($this->bucket, $path);
    }

    /**
     * If path is file
     *
     * @param string $path Remote Path
     *
     * @return bool
     */
    public function isFile($path)
    {
        $path = trim($path, '/');
        return $this->clientS3->doesObjectExist($this->bucket, $path);
    }

    /**
     * If path is diretory
     *
     * @param string $path Remote folder
     *
     * @return bool
     */
    public function isDir($path)
    {
        $path = trim($path, '/') . '/';
        return $this->clientS3->doesObjectExist($this->bucket, $path);
    }

    /**
     * Get an array of files in a directory.
     *
     * @param string $directory Directory
     *
     * @return array
     */
    public function items($directory)
    {
        $directory = trim($directory, '/') . '/';

        $options = array(
            'return_prefixes' => true,
            'sort_results' => true
        );

        $objectIterator = $this->clientS3->getIterator('ListObjects', array(
            'Bucket'    => $this->bucket,
            'Prefix'    => $directory,
            'Delimiter' => '/',
        ), $options);

        $iterator = new CallbackFilterIterator($objectIterator, function($object){
            return (isset($object['Key']) || isset($object['Prefix']));
        });

        $files = array();

        foreach ($iterator as $object)
        {
            $files[] = isset($object['Key']) ? $object['Key'] : $object['Prefix'];
        }

        return $files;
    }

    /**
     * Get an array of all files in a directory recursive.
     *
     * @param string $directory Directory
     *
     * @return array
     */
    public function files($directory)
    {
        $directory = trim($directory, '/') . '/';

        $options = array(
            'return_prefixes' => true,
            'sort_results' => true
        );

        $objectIterator = $this->clientS3->getIterator('ListObjects', array(
            'Bucket'    => $this->bucket,
            'Prefix'    => $directory,
        ), $options);

        $iterator = new CallbackFilterIterator($objectIterator, function($object){
            return (isset($object['Key']) && substr($object['Key'], -1, 1) !== '/');
        });

        $files = array();

        foreach ($iterator as $file)
        {
            $files[] = $file['Key'];
        }

        return $files;
    }

    /**
     * Rename Object
     *
     * @param string $oldname Path From
     * @param string $newname Path To
     *
     * @return bool
     */
    public function rename($oldname, $newname)
    {
        $this->copy($oldname, $newname);

        // Delete the original object
        if ($this->isDir($oldname))
        {
            $this->deleteFolder($oldname);
        } else {
            $this->delete($oldname);
        }

        return true;
    }

    /**
     * Copy Object
     *
     * @param string $from Path From
     * @param string $to   Path To
     *
     * @return bool
     */
    public function copy($from, $to)
    {
        $from = ltrim($from, "/");
        $to   = ltrim($to, "/");

        if (!$this->exists($from)) {
            $path = $this->bucket . "/" . $from;
            throw new ExceptionStorage("Object {$path} Not found", static::E_NOT_IS_DIR);
        }

        if ($this->exists($to)) {
            $path = $this->bucket . "/" . $to;
            throw new ExceptionStorage("Object {$path} exists");
        }

        // Copia Object
        $this->clientS3->copyObject(array(
            'Bucket'     => $this->bucket,
            'Key'        => $to,
            'CopySource' => $this->bucket . '/' . rawurlencode($from),
            'ACL'        => static::ACL_PUBLIC_READ,
            'MetadataDirective' => 'COPY',
        ));

        $pathFrom = trim($from, '/') . '/';

        $iterator = $this->clientS3->getIterator('ListObjects', array(
            'Bucket' => $this->bucket,
            'Prefix' => $pathFrom,
        ));

        foreach ($iterator as $object)
        {
            $pattern = preg_quote($pathFrom, "/");
            $Key = preg_replace("/^{$pattern}/", "", $object['Key']);

            if (!empty($Key))
            {
                $pattern = preg_quote($pathFrom, "/");
                $Key = preg_replace("/^{$pattern}/", "", $object['Key']);

                $origem  = $this->bucket . '/' . $object['Key'];
                $destino = trim($to, "/") . "/" . $Key;

                // Copia Object
                $this->clientS3->copyObject(array(
                    'Bucket'     => $this->bucket,
                    'Key'        => $destino,
                    'CopySource' => $origem,
                    'ACL'        => static::ACL_PUBLIC_READ,
                    'MetadataDirective' => 'COPY'
                ));
            }
        }

        return true;
    }
}