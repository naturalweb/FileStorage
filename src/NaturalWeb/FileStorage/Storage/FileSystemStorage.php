<?php
namespace NaturalWeb\FileStorage\Storage;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileSystemStorage implements StorageInterface
{
    protected $host;

    protected $pathRoot;

    /**
     * Construct
     * 
     * @param string $pathRoot
     * @param string $host
     */
    public function __construct($pathRoot, $host = '')
    {
        $this->pathRoot = rtrim($pathRoot, '/') . "/";
        $this->host = rtrim($host, '/') . "/";
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
     * Save file for storage
     * 
     * @param string $source   Source File
     * @param string $path     Remote path
     * @param bool   $override Override
     * 
     * @return bool
     * @throws
     */
    public function upload($source, $path, $override = false)
    {
        $path = ltrim($path, "/");
        return copy($source, $this->pathRoot.$path);
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
        $path = trim($path, "/");
        return unlink($this->pathRoot.$path);
    }

    /**
     * Create Folder
     * 
     * @param string $path Path folder
     * 
     * @return bool
     * @throws
     */
    public function createFolder($path)
    {
        $mode = 0777;
        $recursive = true;
        $path = ltrim($path, "/");
        return mkdir($this->pathRoot.$path, $mode, $recursive);
    }

    /**
     * Delete Folder
     * 
     * @param string $path Path folder
     * 
     * @return bool
     * @throws
     */
    public function deleteFolder($path)
    {
        $path = ltrim($path, "/");

        if ( ! $this->isDir($path)) {
            throw new ExceptionStorage("Directory {$path} Not found or Not Is Diretory", 1);
        }

        $items = new FilesystemIterator($this->pathRoot.$path);

        foreach ($items as $item)
        {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-director, otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir())
            {
                $pathname = str_replace($this->pathRoot, "", $item->getPathname());
                $this->deleteFolder($pathname);
            }

            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all of the files in this directory
            // and calling directories recursively, so we delete the real path.
            else
            {
                $pathname = str_replace($this->pathRoot, "", $item->getPathname());
                $this->delete($pathname);
            }
        }

        return rmdir($this->pathRoot.$path);
    }

    /**
     * Get remote url, return false in case of error
     * 
     * @param string $path Remote Path with folder
     * 
     * @return string | false
     */
    public function getUrl($path)
    {
        $path = ltrim($path, "/");
        $url = '';
        if ($this->isFile($path)) $url = $this->host.$path;

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
        $path = ltrim($path, "/");
        return file_exists($this->pathRoot.$path);
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
        $path = trim($path, "/");
        return is_file($this->pathRoot.$path);
    }

    /**
     * If path is diretory
     * 
     * @param string $path Remote Path
     * 
     * @return bool
     */
    public function isDir($path)
    {
        $path = ltrim($path, "/");
        return is_dir($this->pathRoot.$path);
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
        $directory = ltrim($directory, "/");

        $iterator = new FilesystemIterator($this->pathRoot.$directory);

        $files = array();

        foreach ($iterator as $file) {
            $files[] = strval($file);
        }
        
        return $files;
    }

    /**
     * Get an array of all files in a directory.
     *
     * @param string $directory Directory
     * 
     * @return array
     */
    public function files($directory)
    {
        $directory = ltrim($directory, "/");
        $files = array();

        $dirIterator = new RecursiveDirectoryIterator($this->pathRoot.$directory);
        $iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::CHILD_FIRST);
        
        foreach ($iterator as $file) {
            if (filetype($file) == 'file') {
                $files[] = strval($file);
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
        $oldname = $this->pathRoot.ltrim($oldname, "/");
        $newname = $this->pathRoot.ltrim($newname, "/");
        return rename($oldname, $newname);
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
        $pathFrom = $this->pathRoot.ltrim($from, "/");
        $pathTo   = $this->pathRoot.ltrim($to, "/");

        if (is_dir($pathFrom)) {
            return $this->copyDir($pathFrom, $pathTo);
        } else {
            return $this->copyFile($pathFrom, $pathTo);
        }
    }

    /**
     * Copy File
     * 
     * @param string $from Path From
     * @param string $to   Path To
     * 
     * @return bool
     * @access private
     */
    private function copyFile($from, $to)
    {
        return copy($from, $to);
    }

    /**
     * Copy Directory Recursive
     * 
     * @param string $from Path From
     * @param string $to   Path To
     * 
     * @return bool
     * @access private
     */
    private function copyDir($from, $to)
    {
        mkdir($to, 0777, true);
        
        $items = new FilesystemIterator($from);

        foreach ($items as $item)
        {
            $pattern = preg_quote($from, "/");
            $Key = preg_replace("/^{$pattern}/", "", $item);

            $subFrom = strval($item);
            $subTo   = $to.$Key;
            
            if (filetype($item) == 'dir')
            {
                $this->copyDir($subFrom, $subTo);
            } else {
                $this->copyFile($subFrom, $subTo);
            }
        }

        return true;
    }
}