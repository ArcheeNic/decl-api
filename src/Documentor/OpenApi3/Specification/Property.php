<?php namespace DeclApi\Documentor\OpenApi3\Specification;

class Property
{
    public static $formatAndTypeMap
        = [
            'int32'     => 'integer',
            'int64'     => 'integer',
            'float'     => 'number',
            'double'    => 'number',
            'string'    => 'string',
            'byte'      => 'string',
            'binary'    => 'string',
            'boolean'   => 'boolean',
            'date'      => 'string',
            'date-time' => 'string',
            'password'  => 'string',
        ];

    public static $validatorToFormat
        = [
            'integer' => 'int64',
            'float'   => 'float',
            'string'  => 'string',
            'boolean' => 'boolean',
        ];

    /**
     * Получить на основе типа валидатора - формат поля по спецификации OpenApi 3
     */
    public static function getFormatFromValidator($type)
    {
        if (isset(static::$validatorToFormat[$type])) {
            return static::$validatorToFormat[$type];
        } else {
            return static::$validatorToFormat['string'];
        }
    }

}