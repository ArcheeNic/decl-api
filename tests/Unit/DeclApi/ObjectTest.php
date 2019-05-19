<?php

namespace Tests\Unit\DeclApi;

use DeclApi\Core\DeclApiValiadateException;
use DeclApi\Core\DiffFieldsObject;
use Tests\TestCase;
use Tests\Unit\DeclApi\TestedBlank\TestedObjectChildClass;
use Tests\Unit\DeclApi\TestedBlank\TestedObjectClass;
use Tests\Unit\DeclApi\TestedBlank\TestedObjectDefaults;
use Tests\Unit\DeclApi\TestedBlank\TestedRequest;
use Tests\Unit\DeclApi\TestedBlank\TestedSpecifyRulesObject;

class ObjectTest extends TestCase
{
    /**
     * проверяем - возвращает ли ошибки валидации при пустом массиве
     *
     * @throws \Exception
     */
    public function testEmpty()
    {
        $object = new TestedObjectClass([], false);
        $this->assertCount(2, $object->validator()->errors());
    }

    /**
     * проверяем - возвращает ли дочерние ошибки валидации?
     *
     * @throws \Exception
     */
    public function testIncorrectChildValidations()
    {
        $this->expectExceptionMessage('{"test.test":["validation.integer"],"test.testArray":["validation.required"]');
        new TestedObjectClass(['test' => ['test' => ['test' => 'example']]]);
    }

    /**
     * Проверяется предвалидация и ее отключение
     *
     * @throws \Exception
     */
    public function testPreValidate()
    {
        // пустой массив автоматом отключает превалиацию
        new TestedObjectClass(['test' => ['test' => ['test' => 'example']]], false);

        // превалидация отключена
        new TestedObjectClass(['test' => ['test' => ['test' => 'example']]], false);

        // превалидация включена
        $this->expectException(DeclApiValiadateException::class);
        new TestedObjectClass(['test' => ['test' => ['test' => 'example']]]);
    }

    /**
     * Проверка валидации объектов
     *
     * @throws \Exception
     */
    public function testValidate()
    {
        // проверяем - правильно ли реагирует валидатор на введенные данные без массива
        $object = new TestedObjectClass(['test' => ['test' => '1']], false);
        $this->assertCount(1, $object->validator()->errors());

        // проверяем - правильно ли реагирует валидатор на введенные данные с неверным массивом (по ошибке на каждый элемент)
        $object = new TestedObjectClass(['test' => ['test' => '1', 'testArray' => ['a', 'b', 'c']]], false);
        $this->assertCount(3, $object->validator()->errors());

        // проверяем - правильно ли реагирует валидатор на введенные данные с массивом
        $object = new TestedObjectClass(['test' => ['test' => '1', 'testArray' => [0, 1, 2]]]);
        $this->assertCount(0, $object->validator()->errors());

        // проверяем строгую системную валидацию
        $object = new TestedObjectClass([
            'test'       => [
                'test'          => '1',
                'left_subfield' => '2',
                'testArray'     => [0, 1, 2]
            ],
            'left_field' => '1'
        ]);
        $errors = DiffFieldsObject::diff($object);
        $this->assertEquals([
            'test.left_subfield' => 'Поле left_subfield не указано в правилах',
            'left_field'         => 'Поле left_field не указано в правилах',
        ], $errors);

        // проверяем строгую системную валидацию Request
        $object = new TestedRequest([
                'header'    => ['headerTest' => '1'],
                'cookie'    => ['cookiesTest' => '1'],
                'json'      => ['left_field' => '1111'],
                'parameter' => []
            ]
            , false);
        $errors = DiffFieldsObject::diffRequest($object);

        $this->assertEquals([
            'JSON.left_field' => 'Поле left_field не указано в правилах',
        ], $errors);
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

        // цифры после мутации из строк превращаются в числа. Поэтому нуцжно привести исходный массив к ожидаемому виду
        $raw['test']['test']      = 1;
        $raw['test']['testArray'] = [0, 1, 2];

        // получение массива
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
        $this->assertEquals(['stringArray' => []], $object->toArray());
    }

    /**
     * @throws \Exception
     */
    public function testSpecifyRulesObject()
    {
        $object = new TestedSpecifyRulesObject([
            'childRules' => [
                'childRules' => [
                    'rw1' => 'example'
                ]
            ]
        ]);
        $this->assertInstanceOf(TestedSpecifyRulesObject::class, $object);
    }
}
