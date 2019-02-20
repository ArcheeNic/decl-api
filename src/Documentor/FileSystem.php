<?php namespace DeclApi\Documentor;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Plugin\ListFiles;

class FileSystem
{
    /**
     * Получить абсолютный путь
     *
     * @param string $path
     *
     * @return bool|string
     * @throws \Exception
     */
    protected final function absolutePath(string $path)
    {
        $folder = realpath($path);

        if (!is_dir($folder)) {
            throw new \Exception('Incorrect path {'.$path.'}');
        }

        return $folder;
    }

    /**
     * @param $path
     *
     * @return \League\Flysystem\Filesystem
     * @throws \Exception
     */
    protected final function newLocalFileSystem($path){
        $path       = $this->absolutePath($path);
        $adapter    = new Local($path);
        $fileSystem = new \League\Flysystem\Filesystem($adapter);
        $fileSystem->addPlugin(new ListFiles());
        return $fileSystem;
    }

    /**
     * Отобрать только php файлы
     *
     * @param $array
     *
     * @return array
     */
    protected final function arrayFilterPhp($array)
    {
        return array_filter($array, function ($value) {
            $isPath = ($value['path'] ?? null) ? true : false;
            $isType = ($value['type'] ?? null === 'file') ? true : false;
            $isPhp  = ($value['extension'] ?? null === 'php');
            return ($isPath && $isType && $isPhp) ? $value : null;
        });
    }
}