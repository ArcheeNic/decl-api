<?php namespace Tests\Unit\DeclApi\TestedBlank;

use DeclApi\Core\ObjectClass;

class TestedSpecifySubRulesObject extends ObjectClass
{
    protected function initRules()
    {
        $this->rulesInfo()->addObject(TestedSpecifySub2RulesObject::class,'childRules1','образец второго дочернего класса')->setIsObject(true);
    }
}