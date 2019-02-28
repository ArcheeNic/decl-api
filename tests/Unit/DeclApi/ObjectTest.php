<?php

namespace Tests\Unit\DeclApi;

use Tests\TestCase;
use Tests\Unit\DeclApi\TestedBlank\TestedObjectChildClass;
use Tests\Unit\DeclApi\TestedBlank\TestedObjectClass;
use Tests\Unit\DeclApi\TestedBlank\TestedObjectDefaults;

class ObjectTest extends TestCase
{
    /**
     * Проверка валидации объектов
     *
     * @throws \Exception
     */
    public function testValidate()
    {
        // проверяем - возвращает ли ошибки валидации?
        $object = new TestedObjectClass();
        $this->assertCount(2, $object->validator()->errors());

        // проверяем - возвращает ли дочерние ошибки валидации?
        $object = new TestedObjectClass(['test' => ['test' => 'example']]);
        $this->assertCount(2, $object->validator()->errors());

        // проверяем - правильно ли реагирует валидатор на введенные данные без массива
        $object = new TestedObjectClass(['test' => ['test' => '1']]);
        $this->assertCount(1, $object->validator()->errors());

        // проверяем - правильно ли реагирует валидатор на введенные данные с неверным массивом (по ошибке на каждый элемент)
        $object = new TestedObjectClass(['test' => ['test' => '1', 'testArray' => ['a', 'b', 'c']]]);
        $this->assertCount(3, $object->validator()->errors());

        // проверяем - правильно ли реагирует валидатор на введенные данные с массивом
        $object = new TestedObjectClass(['test' => ['test' => '1', 'testArray' => [0, 1, 2]]]);
        $this->assertCount(0, $object->validator()->errors());
    }

    /**
     * Проверка работы геттеров и сеттеров
     *
     * @throws \Exception
     */
    public function testGettersAndSetters()
    {
        $raw    = ['test' => ['test' => '1', 'testArray' => ['0', '1', '2']]];
        $object = new TestedObjectClass($raw);
        $this->assertCount(0, $object->validator()->errors());

        // преобразование в класс
        $this->assertInstanceOf(TestedObjectChildClass::class, $object->getTest());

        // получение массива
        $this->assertEquals($raw, $object->toArray());

        // цифры после мутации из строк превращаются в числа. Поэтому нуцжно привести исходный массив к ожидаемому виду
        $raw['test']['test']      = 1;
        $raw['test']['testArray'] = [0, 1, 2];

        // получение json
        $this->assertEquals($raw, $object->toArray());

        // получение json
        $this->assertEquals(json_encode($raw), json_encode($object));
    }

    /**
     * @throws \Exception
     */
    public function testDefaults()
    {
        $object = new TestedObjectDefaults();
        $this->assertEquals(['stringArray'=>[]],$object->toArray());
    }
}
