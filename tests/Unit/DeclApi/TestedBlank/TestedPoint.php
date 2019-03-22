<?php namespace Tests\Unit\DeclApi\TestedBlank;

use DeclApi\Core\ObjectClass;
use DeclApi\Core\Frameworks\Laravel5Point;
use DeclApi\Core\Request;

/**
 * Тестовый эндпоинт
 *
 * Пример описания поинта
 *
 * Class TestedPoint
 *
 * @package Tests\Unit\DeclApi\TestedBlank
 */
class TestedPoint extends Laravel5Point
{
    protected function initExampleCallCode()
    {
        // TODO: Implement initExampleCallCode() method.
    }

    protected function initErrors()
    {
        $this->errorsInfo()->addError('incorrect_request', 'Неверный запрос', '', '', 400);
        $this->errorsInfo()->addError('no_request', 'Нет запроса', '', '', 400);
        $this->errorsInfo()->addError('authorize_required', 'Неверный запрос', '', '', 302);
        $this->errorsInfo()->addError('not_found', 'Запись не найдена', '', '', 404);
        $this->errorsInfo()->addError('authorize_incorrect', 'Неверный запрос', '', '', 302);
    }

    /**
     * @param Request $request
     *
     * @return ObjectClass
     * @throws \Exception
     */
    public function handler(TestedRequest $request): TestedResponse
    {
        return (new TestedResponse());
    }

}