<?php namespace Tests\Unit\DeclApi\TestedBlank;

use DeclApi\Core\ObjectClass;

/**
 * Тестовый подчиненный объект
 *
 * Class TestedObjectChildClass
 *
 * @property int   $test
 * @property int[] $testArray
 *
 * @package Tests\Unit\DeclApi
 */
class TestedObjectChildClass extends ObjectClass
{
    /**
     * @param array $data
     *
     * @throws \Exception
     */
    public function initRules(array $data = [])
    {
        $this->rulesInfo()->add('integer', 'test', 'Тест', 'Тестовое поле')->setAttributes('required');
        $this->rulesInfo()->add('integer', 'testArray', 'Тест массива', 'Поле в виде массива')->setAttributes('required')->setIsArray(true);
    }

    public function getTest()
    {
        return $this->getField('test');
    }
    public function getTestArray()
    {
        return $this->getField('testArray');
    }
}