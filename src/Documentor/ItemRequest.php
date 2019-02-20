<?php namespace DeclApi\Documentor;

use DeclApi\Core\Request;
use DeclApi\Core\RulesInfoRequest;

class ItemRequest extends ItemInfo
{
    /**
     * @var RulesInfoRequest $parameters правила
     */
    protected $rules;

    /**
     * @return RulesInfoRequest
     */
    public function getRules(): RulesInfoRequest
    {
        return $this->rules;
    }

    public function analyse()
    {
        $classname = $this->classname;
        /**
         * @var Request $class
         */
        $class       = new $classname();
        $this->rules = $class->rulesInfo();
    }
}