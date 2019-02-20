<?php namespace Tests\Unit\DeclApi\TestedBlank;

use DeclApi\Core\ObjectClass;

/**
 * Тестовый родительский объект
 *
 * Образец описания
 * Который никогда не кончается
 *
 * @package Tests\Unit\DeclApi
 * @property TestedObjectChildClass $test
 */
class TestedObjectClass extends ObjectClass
{
    public function initRules()
    {
        $this->rulesInfo()->addObject(TestedObjectChildClass::class,'test', 'Тест', 'Тестовое поле')->setAttributes('required');
    }

    public function getTest()
    {
        return $this->getField('test');
    }
}