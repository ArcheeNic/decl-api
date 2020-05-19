<?php namespace DeclApi\Documentor;

use DeclApi\Core\ObjectClass;
use DeclApi\Core\Point;
use DeclApi\Core\Request;
use League\Flysystem\AdapterInterface;

/**
 * Отсканировать папку и собрать только те файлы, коорые нам нужны для документации
 * Class ScanFolder
 *
 * @package DeclApi\Documentor
 */
class ScanFiles extends \DeclApi\Documentor\FileSystem
{
    /**
     * @var string $root
     */
    protected $root = '';

    /**
     * @var AdapterInterface
     */
    protected $fileSystem;

    /**
     * ScanFiles constructor.
     *
     * @param $root
     *
     * @throws \Exception
     */
    public function __construct($root)
    {
        $path             = $this->absolutePath($root);
        $this->root       = $path;
        $this->fileSystem = $this->newLocalFileSystem($path);
    }

    /**
     * @throws \ReflectionException
     */
    public function getInfo()
    {
        $dataFileSystem = $this->fileSystem->listContents('', true);
        $phpFiles       = $this->arrayFilterPhp($dataFileSystem);
        $types          = $this->groupFilesByType($phpFiles);
        $info           = $this->completeInfo($types);
        return $info;
    }

    /**
     * @param array $array
     *
     * @return array
     * @throws \ReflectionException
     */
    private function completeInfo($array = [])
    {
        $result = [
            'available' => [
                'point'   => [],
                'request' => [],
                'object'  => [],
            ],
            'point'     => [],
            'request'   => [],
            'object'    => []
        ];

        $result['available'] = $array;

        foreach ($result['available']['point'] as $key => $value) {
            $item                                                   = new ItemPoint($value);
            $result['point'][$item->docFile][$item->getClassname()] = $item;
        }

        foreach ($result['available']['request'] as $key => $value) {
            $item                                     = new ItemRequest($value);
            $result['request'][$item->getClassname()] = $item;
        }

        foreach ($result['available']['object'] as $key => $value) {
            $item                                    = new ItemObject($value);
            $result['object'][$item->getClassname()] = $item;
        }

        return $result;
    }

    /**
     * Сгруппировать файлы по типу
     *
     * @param $array
     *
     * @return array
     * @throws \ReflectionException
     */
    private final function groupFilesByType($array)
    {
        $request = [];
        $object  = [];
        $point   = [];


        foreach ($array as $value) {
            $content = file_get_contents($this->root.'/'.$value['path']);
            if (preg_match('!namespace (.*?);[\n\r]!mui', $content, $namespace)) {
                $namespace = $namespace[1];
            }
            if (preg_match('!class (.*?) .*?[\n\r]!mui', $content, $classname)) {
                $classname = $classname[1];
            }

            if (!$classname || !$namespace) {
                continue;
            }

            $fullClassname = $namespace.'\\'.$classname;
            $reflectionClass = new \ReflectionClass($fullClassname);
            if($reflectionClass->isAbstract()){
                continue;
            }

            if (is_subclass_of($fullClassname, Request::class)) {
                $request[] = $fullClassname;
            } elseif (is_subclass_of($fullClassname, ObjectClass::class)) {
                $object[] = $fullClassname;
            } elseif (is_subclass_of($fullClassname, Point::class)) {
                if(!method_exists($fullClassname, 'handler')){
                    continue;
                }
                $point[] = $fullClassname;
            }
        }

        return compact('request', 'object', 'point');
    }
}