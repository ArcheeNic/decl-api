<?php namespace Tests\Unit\DeclApi\TestedBlank;

use DeclApi\Core\ObjectClass;

/**
 * Пример объекта ответа
 * Class TestedResponse
 *
 * @package Tests\Unit\DeclApi\TestedBlank
 */
class TestedResponse extends ObjectClass
{
    public function initRules()
    {
        parent::initRules();
        $this->rulesInfo()->add('string', 'status', 'Статус ответа', 'Статус ответа')->setAttributes('required')
            ->setEnum(['success', 'error'])->setDefault('success');
        $this->rulesInfo()->add('integer', 'statusCode', 'Код ответа', 'Аналог HTTP статусов ответа')
            ->setAttributes('required')->setDefault(200);
        $this->rulesInfo()->addObject(TestedObjectClass::class,'test', 'Тест', 'Тестовое поле')->setAttributes('required');
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status)
    {
        $this->setField('status',$status);
    }

    /**
     * @param int $code
     */
    public function setStatusCode(int $code)
    {
        $this->setField('statusCode',$code);
    }
}