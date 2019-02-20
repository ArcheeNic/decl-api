<?php namespace DeclApi\Documentor;

use DeclApi\Core\ObjectClass;
use DeclApi\Core\Request;
use DeclApi\Core\RulesInfo;

class ItemObject extends ItemInfo
{
    /**
     * @var RulesInfo $parameters правила
     */
    protected $rules;

    /**
     * @return RulesInfo
     */
    public function getRules(): RulesInfo
    {
        return $this->rules;
    }

    public function analyse()
    {
        $classname = $this->classname;
        /**
         * @var ObjectClass $class
         */
        $class       = new $classname();
        $this->rules = $class->rulesInfo();
    }

}