<?php namespace DeclApi\Core;

use Illuminate\Contracts\Validation\Validator;
use Throwable;

/**
 * Class DeclApiValiadateException
 *
 * @package DeclApi\Core
 */
class DeclApiValiadateException extends \Exception
{
    protected $validator;
    protected $objectClass;

    /**
     * DeclApiValiadateException constructor.
     *
     * @param ObjectClass    $objectClass
     * @param Validator      $validator
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(
        ObjectClass $objectClass,
        Validator $validator,
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->validator   = $validator;
        $this->objectClass = $objectClass;
        parent::__construct(json_encode($this->validator->errors()), $code, $previous);
    }

    /**
     * @return Validator
     */
    public function getValidator(): Validator
    {
        return $this->validator;
    }

    /**
     * @return ObjectClass
     */
    public function getObjectClass(): ObjectClass
    {
        return $this->objectClass;
    }

}