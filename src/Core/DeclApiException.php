<?php namespace DeclApi\Core;

use Throwable;

class DeclApiException extends \Exception
{
    /**
     * @var string Название ошибки
     */
    protected $title;
    /**
     * @var string Описание ошибки
     */
    protected $description;
    /**
     * @var int HTTP код ошибки
     */
    protected $responseCode;
    /**
     * @var array HTTP заголовки ответа
     */
    protected $headers;

    /**
     * Такая сложная конструкция необходима для того чтобы можно было вернуть адекватный ответ клиенту
     * DeclApiException constructor.
     *
     * @param string         $title
     * @param string         $description
     * @param int            $responseCode
     * @param array          $headers
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $title,
        string $description = "",
        int $responseCode = 501,
        array $headers = [],
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->title        = $title;
        $this->description  = $description;
        $this->responseCode = $responseCode;
        $this->headers      = $headers;
        parent::__construct("Title:".$title.";\nDesc:".$description, $code, $previous);
    }

    /**
     * Навзвание ошибки
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Описание ошибки
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * HTTP код ошибки
     *
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    /**
     * HTTP заголовки ответа
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }


}