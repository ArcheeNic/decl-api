<?php namespace DeclApi\Core;

/**
 * Точка входа. Отсюда начинается работа с логикой
 * Класс остается пока абстрактным, хотя было желание переделать в интерфейс.
 * Возможно что нужно будет перенести сюда некие общие методы, поэтому класс абстрактен
 * Так же может быть использован как instance идентификатор
 * Class Point
 *
 * @package DeclApi\Core
 */
abstract class Point
{
    static public $docFile = 'main';
    /**
     * @var array $docVersion Version. Default equivalent - 0.0.1
     */
    static public $docVersion = [0, 0, 1];

    /**
     * @var PointErrorsInfo $errorsInfo
     */
    protected $errorsInfo;

    /**
     * @throws \Exception
     */
    protected function initErrors()
    {

    }

    /**
     * @return PointErrorsInfo
     */
    public function errorsInfo(): PointErrorsInfo
    {
        if ($this->errorsInfo === null) {
            $this->errorsInfo = new PointErrorsInfo();
        }

        return $this->errorsInfo;
    }

    /**
     * Point constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->initErrors();
    }
}