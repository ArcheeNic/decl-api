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
class TestedObjectDefaults extends ObjectClass
{
    public function initRules()
    {
        $this->rulesInfo()->add('string','stringArray','stringArray')->setIsArray(true)->setDefault([]);
    }
}