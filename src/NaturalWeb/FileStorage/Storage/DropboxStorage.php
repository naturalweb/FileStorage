<?php
namespace NaturalWeb\FileStorage\Storage;

use Dropbox\Client;
use Dropbox\WriteMode;

class DropboxStorage implements StorageInterface
{
    /**
     * @var Client
     */
    protected $dropbox;

    /**
     * @var string
     */
    protected $pathRoot;

    /**
     * Construct
     * 
     * @param Client $dropbox
     */
    public function __construct($pathRoot, Client $dropbox)
    {
        $this->pathRoot = rtrim($pathRoot, '/');
        $this->dropbox = $dropbox;
    }

    /**
     * Get Path Root
     * 
     * @return string
     */
    public function getPathRoot()
    {
        return $this->pathRoot;
    }

    /**
     * Get Dropbox
     * 
     * @return Client
     */
    public function getDropbox()
    {
        return $this->dropbox;
    }

    /**
     * Save file for storage
     * 
     * @param string $source   Source File
     * @param string $path   Remote path
     * @param bool   $override Override
     * 
     * @return bool
     * @throws
     */
    public function upload($source, $path, $override = false)
    {
        $fd = fopen($source, "rb");
        $writeMode = ($override===true) ? WriteMode::force() : WriteMode::add();
        $metadata = $this->dropbox->uploadFile($this->pathRoot.$path, $writeMode, $fd);
        fclose($fd);

        return !is_null($metadata);
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
        if (!$this->isFile($path)) {
            $path = $this->pathRoot.$path;
            throw new ExceptionStorage("File {$path} Not found or Not Is File", static::E_NOT_IS_FILE);
        }

        $metadata = $this->dropbox->delete($this->pathRoot.$path);
        return !is_null($metadata);
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
        if ($this->exists($folder)) {
            return true;
        }

        $metadata = $this->dropbox->createFolder($this->pathRoot.$folder);
        return !is_null($metadata);
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
        if (!$this->isDir($folder)) {
            $path = $this->pathRoot.$folder;
            throw new ExceptionStorage("Directory {$path} Not found or Not Is Diretory", static::E_NOT_IS_DIR);
        }

        $metadata = $this->dropbox->delete($this->pathRoot.$folder);
        return !is_null($metadata);
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
        $url = '';
        $share = $this->dropbox->createShareableLink($this->pathRoot.$path);
        if ($share)
            $url = str_replace('www.dropbox.com' ,  'dl.dropboxusercontent.com', $share);
        
        return $url;
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
        $metadata = $this->dropbox->getMetadata($this->pathRoot.$path);
        return is_array($metadata) && isset($metadata['path']);
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
        $metadata = $this->dropbox->getMetadata($this->pathRoot.$path);
        return is_array($metadata) && isset($metadata['is_dir']) && $metadata['is_dir']==false;
    }

    /**
     * If path is diretory
     * 
     * @param string $folder Remote folder
     * 
     * @return bool
     */
    public function isDir($folder)
    {
        $metadata = $this->dropbox->getMetadata($this->pathRoot.$folder);
        return is_array($metadata) && isset($metadata['is_dir']) && $metadata['is_dir']==true;
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
        $items = array();

        $metadata = $this->dropbox->getMetadata($this->pathRoot.$directory);

        if (isset($metadata['contents']) && is_array($metadata['contents']))
        {
            foreach ($metadata['contents'] as $file)
            {
                if ($file['is_dir'] == false) {
                    $items[] = $file['path'];
                }
            }
        }

        return $items;
    }

    /**
     * Get an array of all files in a directory.
     *
     * @param  string  $directory
     * @return array
     */
    public function files($directory)
    {
        $files = array();

        $metadata = $this->dropbox->getMetadataWithChildren($this->pathRoot.$directory);

        if (isset($metadata['contents']) && is_array($metadata['contents']))
        {
            foreach ($metadata['contents'] as $file)
            {
                if ($file['is_dir'] == false) {
                    $files[] = $file['path'];
                }
            }
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
        return false;
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
        return false;
    }
}