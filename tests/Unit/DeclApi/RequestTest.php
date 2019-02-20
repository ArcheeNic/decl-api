<?php

namespace Tests\Unit\DeclApi;


use DeclApi\Core\Frameworks\Laravel5Point;
use DeclApi\Core\RulesInfo;
use DeclApi\Core\RulesInfoRequest;
use Illuminate\Http\Request;
use Tests\TestCase;
use Tests\Unit\DeclApi\TestedBlank\TestedRequest;

class RequestTest extends TestCase
{
    /**
     * Проверка работы валидации объекта запрсса
     * @throws \Exception
     */
    function testRequestValidate()
    {
        $this->markTestSkipped('Пропущено. Laravel не установлен');

        // числа намеренно инициируются как строки,  для проверки работоспособности
        $dataRaw    = ['test' => ['test' => '1', 'testArray' => ['0', '1', '2']]];
        $json    = json_encode($dataRaw);
        $requestData = Request::create('', 'POST', $dataRaw, ['cookiesTest' => '1'], [], [], $json);
        $requestData->headers->set('headerTest', '1');

        // успешная валидация
        $request = new TestedRequest(Laravel5Point::requestToArray($requestData));

        $this->assertCount(0, $request->validator('parameter')->errors());
        $this->assertCount(0, $request->validator('json')->errors());
        $this->assertCount(0, $request->validator('cookie')->errors());
        $this->assertCount(0, $request->validator('header')->errors());

        // неуспешная валидация
        $dataRaw['test']['testArray'][0] = 'not integer';
        $json    = json_encode($dataRaw);
        $requestData = Request::create('', 'POST', $dataRaw, ['cookiesTest' => 'not integer'], [], [], $json);
        $requestData->headers->set('headerTest', null);

        $request = new TestedRequest(Laravel5Point::requestToArray($requestData));
        $this->assertCount(1, $request->validator('parameter')->errors());
        $this->assertCount(1, $request->validator('json')->errors());
        $this->assertCount(1, $request->validator('cookie')->errors());
        $this->assertCount(2, $request->validator('header')->errors());
    }
}
