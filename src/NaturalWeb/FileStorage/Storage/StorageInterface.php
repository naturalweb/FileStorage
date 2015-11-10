<?php
namespace NaturalWeb\FileStorage\Storage;

interface StorageInterface
{
    const E_NOT_FOUND   = 10;
    const E_NOT_IS_FILE = 20;
    const E_NOT_IS_DIR  = 30;
    
    /**
     * Save file for storage
     * 
     * @param string $source   Source File
     * @param string $path     Remote Path
     * @param bool   $override Override
     * 
     * @return bool
     * @throws
     */
    public function upload($source, $path, $override = false);

    /**
     * Delete remote file
     * 
     * @param string $path Remote Path
     * 
     * @return bool
     * @throws
     */
    public function delete($path);

    /**
     * Create Folder
     * 
     * @param string $path Path folder
     * 
     * @return bool
     * @throws
     */
    public function createFolder($path);

    /**
     * Delete Folder
     * 
     * @param string $path Path folder
     * 
     * @return bool
     * @throws
     */
    public function deleteFolder($path);

    /**
     * Get remote url, return false in case of error
     * 
     * @param string $path Remote Path with folder
     * 
     * @return string
     * @throws
     */
    public function getUrl($path);

    /**
     * If path exists
     * 
     * @param string $path Remote Path
     * 
     * @return bool
     */
    public function exists($path);

    /**
     * If path is file
     * 
     * @param string $path Remote Path
     * 
     * @return bool
     */
    public function isFile($path);

    /**
     * If path is diretory
     * 
     * @param string $path Remote Path
     * 
     * @return bool
     */
    public function isDir($path);

    /**
     * Get an array of files in a directory.
     *
     * @param string $directory Directory
     * 
     * @return array
     */
    public function items($directory);

    /**
     * Get an array of all files in a directory.
     *
     * @param  string  $directory
     * @return array
     */
    public function files($directory);
    
    /**
     * Rename Object
     * 
     * @param string $oldname Path From
     * @param string $newname Path To
     * 
     * @return bool
     */
    public function rename($oldname, $newname);

    /**
     * Copy Object
     *
     * @param string $from Path From
     * @param string $to   Path To
     * 
     * @return bool
     */
    public function copy($from, $to);
}