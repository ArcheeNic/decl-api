<?php namespace DeclApi\Documentor\OpenApi3\Specification;

class Property
{
    protected     $type;
    protected     $format;
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

    /**
     * Property constructor.
     *
     * @param $format
     *
     * @throws \Exception
     */
    public function __construct($format)
    {
        $this->format = $format;
        if(!isset(static::$formatAndTypeMap[$format])){
            throw new \Exception('incorrect format');
        }
    }

}