<?php namespace Tests\Unit\DeclApi\TestedBlank;

use DeclApi\Core\ObjectClass;

class TestedSpecifySub2RulesObject extends ObjectClass
{
    protected function initRules()
    {
        $this->rulesInfo()->add('string','rw1','Обязательно если иное пустое 1') ->setAttributes(['required_without:{this}.rw2']);
        $this->rulesInfo()->add('string','rw2','Обязательно, если иное пустое 2')->setAttributes(['required_without:{this}.rw1']);
    }
}