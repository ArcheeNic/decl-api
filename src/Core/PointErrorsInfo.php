<?php namespace DeclApi\Core;

/**
 * Список возможных ошибок поинта.
 * Другие варианты стабильно будут отдавать 501 ошибку.
 * Class Point
 *
 * @package DeclApi\Core
 */
final class PointErrorsInfo
{
    /**
     * @var PointErrorItem[] $data
     */
    protected $data = [];

    /**
     * Добавить ошибку.
     * Все поля важны
     *
     * @param     string $key              ключ по нему определяется уникализация ошибки и получаются данные
     * @param     string $docDescription   описание для документации
     * @param     string $errorTitle       заголовок ошибки в ответе
     * @param     string $errorDescription текст ошибки в ответе
     * @param int        $httpCode         http код ответа
     *
     * @return PointErrorsInfo
     * @throws
     */
    public function addError($key, $docDescription, $errorTitle, $errorDescription, $httpCode = 501): PointErrorItem
    {
        if (isset($this->data[$key])) {
            throw new \Exception('Добавить ошибку не удается. Такой ключ ошибки уже сущестует');
        }

        return $this->data[$key] = (new PointErrorItem)
            ->setKey($key)
            ->setDocDescription($docDescription)
            ->setErrorTitle($errorTitle)
            ->setErrorDescription($errorDescription)
            ->setHttpCode($httpCode);
    }

    public function getError($key)/*TODO downgrade - : ?PointErrorItem*/
    {
        return $this->data[$key] ?? null;
    }

    /**
     * @return PointErrorItem[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        $data = [];

        foreach ($this->getData() as $key => $value) {
            $data[$key] = $value->toArray();
        }

        return $data;
    }

    public function exception($key, $errorTitle = null, $errorDescription = null)
    {
        if (!$error = $this->getError($key)) {
            throw new DeclApiCoreException('Ошибки с кодом {'.$key.'} не объявлено');
        }

        throw new DeclApiException(
            ((string)$errorTitle)?:(string)$error->getErrorTitle(),
            ((string)$errorDescription)?:(string)$error->getErrorDescription(),
            $error->getHttpCode()
        );
    }

}