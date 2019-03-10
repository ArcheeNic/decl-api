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
     * @throws \Exception
     */
    function analyse()
    {
        $classname     = $this->classname;
        $reflection    = new \ReflectionClass($classname);

        $handlerMethod = $reflection->getMethod('handler');

        $this->response = (string)$handlerMethod->getReturnType();

        if (!$this->response) {
            throw new \Exception('Отсутсвует информация о response типе объекта класса .'.$classname);
        }

        $requestParameters = $handlerMethod->getParameters();
        if (!$requestParameters) {
            throw new \Exception('Отсутствует информация о request объекте класса .'.$classname);
        }

        $requestType = $requestParameters[0];
        if (!$requestType) {
            throw new \Exception('Отсутствует информация о request типе объекта класса .'.$classname);
        }

        $this->request = (string) $requestType->getType();
        if (!$this->request) {
            throw new \Exception('Отсутствует информация о request типе объекта класса .'.$classname);
        }

        $this->docFile = $classname::$docFile;
    }
}