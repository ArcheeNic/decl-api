<?php namespace DeclApi\Core\Frameworks;

use DeclApi\Core\DeclApiException;
use DeclApi\Core\ObjectClass;
use DeclApi\Core\Point;
use DeclApi\Documentor\ItemPoint;

/**
 * Мост Pointа для Laravel 5.4+
 * Class PointL5Bridge
 *
 * @package DeclApi\Core
 */
abstract class Laravel5Point extends Point implements BridgeContract
{
    /**
     * @var \Illuminate\Http\Request
     */
    protected $illuminateRequest;
    /**
     * @var \Illuminate\Http\Response
     */
    protected $illuminateResponse;

    /**
     * @param \Illuminate\Http\Request  $illuminateRequest
     * @param \Illuminate\Http\Response $illuminateResponse
     *
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function __invoke(
        \Illuminate\Http\Request $illuminateRequest,
        \Illuminate\Http\Response $illuminateResponse
    ): \Illuminate\Http\Response {
        $this->illuminateResponse = $illuminateResponse;
        $this->illuminateRequest  = $illuminateRequest;

        // пример логики работы мидлвара
        // определение типа реквеста
        try {
            $pointInfo = new ItemPoint(get_class($this));
            $requestType = $pointInfo->request;

            $requestEnd = new $requestType(static::requestToArray($illuminateRequest));
            $response   = $this->handler($requestEnd);

            $validator = $response->validator();
            if ($response instanceof ObjectClass) {
                $response->validator()->fails();
            }
        } catch (DeclApiException $exception) {
            $this->abort($exception->getResponseCode(), $exception->getMessage(), $exception->getHeaders());
        }
        return $this->illuminateResponse->setContent($response->jsonSerialize());
    }

    public function abort($code = 500, $message = '', $headers = [])
    {
        $response = $this->illuminateResponse->setStatusCode($code);
        if (!empty($message)) {
            $response->setContent($message);
        }
        if (!empty($headers)) {
            $response->withHeaders($headers);
        }
        return $response;
    }

    static public function requestToArray(\Illuminate\Http\Request $illuminateRequest): array
    {
        return [
            'parameter' => $illuminateRequest->all(),
            'header'    => $illuminateRequest->header(),
            'json'      => $illuminateRequest->json()->all(),
        ];
    }
}