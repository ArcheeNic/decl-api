<?php namespace DeclApi\Documentor;

/**
 * Информация о поинте
 * Class ItemPoint
 *
 * @package DeclApi\Documentor
 */
class ItemPoint extends ItemInfo
{
    /**
     * @var string $request входящие данные
     */
    public $request;

    /**
     * @var string $response результат
     */
    public $response;

    /**
     * @var string $docFile файл, куда будет сохраняться документация
     */
    public $docFile;

    /**
     * @throws \ReflectionException
     */
    function analyse()
    {
        $classname      = $this->classname;
        $reflection     = new \ReflectionClass($classname);
        $handlerMethod  = $reflection->getMethod('handler');
        $this->response = $handlerMethod->getReturnType()->getName();
        $this->request  = $handlerMethod->getParameters()[0]->getType()->getName();
        $this->docFile = $classname::$docFile;
    }
}