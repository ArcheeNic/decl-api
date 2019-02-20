<?php

namespace Tests\Unit\DeclApi;

use DeclApi\Core\Frameworks\Laravel5Point;
use Illuminate\Http\Request;
use Tests\TestCase;
use Tests\Unit\DeclApi\TestedBlank\TestedPoint;
use Tests\Unit\DeclApi\TestedBlank\TestedRequest;

/**
 * Тестирование логики
 * Class LogicTest
 *
 * @package Tests\Unit\DeclApi
 */
class LogicTest extends TestCase
{
    /**
     * Прооверяем логику работы поинта
     *
     * @throws \Exception
     */
    function testLogicLaravel5Main()
    {
        $this->markTestSkipped('Пропущено. Laravel не установлен');

        // пример логики работы миддлвара
        $dataRaw     = ['test' => ['test' => '1', 'testArray' => ['0', '1', '2']]];
        $json        = json_encode($dataRaw);
        $requestData = Request::create('', 'POST', $dataRaw, ['cookiesTest' => '1'], [], [], $json);
        $requestData->headers->set('headerTest', '1');
        $request = new TestedRequest(Laravel5Point::requestToArray($requestData));

        $next     = new TestedPoint();
        $response = $next->handler($request);
        $errors   = $response->validator()->errors();

        $this->assertCount(0, $errors);
    }

    /**
     * Прооверяем логику работы поинта
     *
     * @throws \Exception
     */
    function testLogicMain()
    {
        // пример логики работы миддлвара
        $dataRaw = ['test' => ['test' => '1', 'testArray' => ['0', '1', '2']]];
        $request = new TestedRequest(
            [
                'header'    => [
                    'headerTest' => '1'
                ],
                'cookie'    => [
                    'cookiesTest' => '1'
                ],
                'json'      => $dataRaw,
                'parameter' => []
            ]
        );
        $json    = json_encode($dataRaw);

        $next     = new TestedPoint();
        $response = $next->handler($request);
        $errors   = $response->validator()->errors();

        $this->assertCount(0, $errors);
    }
}
