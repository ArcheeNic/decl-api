<?php namespace Tests\Unit\DeclApi\TestedBlank;


use DeclApi\Core\Request;

/**
 * Пример объекта запроса
 * Class TestedRequest
 *
 * @package Tests\Unit\DeclApi\TestedBlank
 */
class TestedRequest extends Request
{


    /**
     * TestedRequest constructor.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws \Exception
     */
    protected function initRules() {
        $this->rulesInfo()->see('parameter',TestedObjectClass::class);
        $this->rulesInfo()->addJson(TestedObjectChildClass::class,'test', 'Тест', 'Тестовое поле')->setIsObject(true)->setAttributes('required');
        $this->rulesInfo()->addCookie('integer','cookiesTest', 'Тест', 'Тестовый кукис')->setAttributes(['required']);
        $this->rulesInfo()->addHeader('integer','headerTest', 'Тест', 'Тестовый заголовок')->setAttributes('required');
    }
}