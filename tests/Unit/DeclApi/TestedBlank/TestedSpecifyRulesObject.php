<?php namespace Tests\Unit\DeclApi\TestedBlank;

use DeclApi\Core\ObjectClass;

class TestedSpecifyRulesObject extends ObjectClass
{
    protected function initRules()
    {
        $this->rulesInfo()->addObject(TestedSpecifySubRulesObject::class,'childRules','образец дочернего класса')->setIsObject(true);
    }
}