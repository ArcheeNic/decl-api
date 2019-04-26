<?php namespace DeclApi\Core;

/**
 * Информация об ошибке
 * Class Point
 *
 * @package DeclApi\Core
 */
final class PointErrorItem
{
    /**
     * @var string|null $key ключ по нему определяется уникализация ошибки и получаются данные
     */
    protected $key;

    /**
     * @var string $docError описание для документации
     */
    protected $docDescription = '';

    /**
     * @var string $errorTitle заголовок ошибки в ответе
     */
    protected $errorTitle = '';

    /**
     * @var string $errorDescription описание для документации
     */
    protected $errorDescription = '';

    /**
     * @var int $httpCode http код ответа
     */
    protected $httpCode = 500;

    /**
     * @return string|null
     */
    public function getKey()/*TODO downgrade - : ?string*/
    {
        return $this->key;
    }

    /**
     * @param string|null $key
     *
     * @return PointErrorItem
     */
    public function setKey(/*TODO downgrade - ?string*/ $key): PointErrorItem
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string
     */
    public function getDocDescription()
    {
        return $this->docDescription;
    }

    /**
     * @param string $docDescription
     *
     * @return PointErrorItem
     */
    public function setDocDescription(string $docDescription): PointErrorItem
    {
        $this->docDescription = $docDescription;
        return $this;
    }

    /**
     * @return string
     */
    public function getErrorTitle()
    {
        return $this->errorTitle;
    }

    /**
     * @param string $errorTitle
     *
     * @return PointErrorItem
     */
    public function setErrorTitle(string $errorTitle): PointErrorItem
    {
        $this->errorTitle = $errorTitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getErrorDescription()
    {
        return $this->errorDescription;
    }

    /**
     * @param string $errorDescription
     *
     * @return PointErrorItem
     */
    public function setErrorDescription(string $errorDescription): PointErrorItem
    {
        $this->errorDescription = $errorDescription;
        return $this;
    }

    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * @param int $httpCode
     *
     * @return PointErrorItem
     */
    public function setHttpCode(int $httpCode): PointErrorItem
    {
        $this->httpCode = $httpCode;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'key'              => $this->getKey(),
            'docDescription'   => $this->getDocDescription(),
            'errorTitle'       => $this->getErrorTitle(),
            'errorDescription' => $this->getErrorDescription(),
            'httpCode'         => $this->getHttpCode(),
        ];
    }
}