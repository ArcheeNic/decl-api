<?php namespace DeclApi\Core\Frameworks;

use DeclApi\Core\DeclApiException;
use DeclApi\Core\DiffFieldsObject;
use DeclApi\Core\ObjectClass;
use DeclApi\Core\Point;
use DeclApi\Core\Request;
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
            $pointInfo   = new ItemPoint(get_class($this));
            $requestType = $pointInfo->request;

            /**
             * @var Request $requestEnd
             */
            $requestEnd = new $requestType(static::requestToArray($illuminateRequest));

            // проверяем диффы
            $this->requestCheckDiffErrors(DiffFieldsObject::diffRequest($requestEnd));
            $requestEnd->cleanDiffData();

            $validator = $requestEnd->validator();
            if ($validator->fails()) {
                return $this->abort(400, $validator->errors());
            }
            /**
             * @var ObjectClass $response
             */
            $response = $this->handler($requestEnd);

            $this->responseCheckDiffErrors(DiffFieldsObject::diff($response));
            $validator = $response->validator();
            if ($response instanceof ObjectClass) {
                if ($validator->fails()) {
                    return $this->abort(400, $validator->errors());
                }
            }
        } catch (DeclApiException $exception) {
            return $this->abort($exception->getResponseCode(), $exception->getMessage(), $exception->getHeaders());
        } catch (\Exception $exception) {
            if (env('APP_DEBUG')) {
                return $this->abort(500,
                    ['message' => $exception->getMessage(), 'trace' => explode("\n", $exception->getTraceAsString())]);
            }
            return $this->abort(500, ['message' => 'Произошла системная ошибка. Обратитесь к разарботчикам']);
        }
        return $this->illuminateResponse->setContent($response->jsonSerialize());
    }

    protected function requestCheckDiffErrors($errors = [])
    {
        return null;
    }

    protected function responseCheckDiffErrors($errors = [])
    {
        return null;
    }

    public function abort($code = 500, $message, $headers = [])
    {
        $response = $this->illuminateResponse;
        $response->setStatusCode($code);
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