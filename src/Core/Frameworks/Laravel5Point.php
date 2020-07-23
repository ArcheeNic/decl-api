<?php namespace DeclApi\Core\Frameworks;

use DeclApi\Core\DeclApiException;
use DeclApi\Core\DeclApiValiadateException;
use DeclApi\Core\DiffFieldsObject;
use DeclApi\Core\ObjectClass;
use DeclApi\Core\Point;
use DeclApi\Core\Request;
use DeclApi\Core\ValidatorFactory;
use DeclApi\Documentor\ItemPoint;
use Illuminate\Contracts\Validation\Factory;

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

    protected function requestCheckDiffErrors($errors = [])
    {
        return null;
    }

    protected function responseCheckDiffErrors($errors = [])
    {
        return null;
    }

    /**
     * @param  \Illuminate\Http\Request   $illuminateRequest
     * @param  \Illuminate\Http\Response  $illuminateResponse
     *
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function __invoke(\Illuminate\Http\Request $illuminateRequest, \Illuminate\Http\Response $illuminateResponse
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
            $requestEnd = new $requestType(static::requestToArray($illuminateRequest), true, new ValidatorFactory(app(Factory::class)));

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
        } catch (DeclApiValiadateException $exception) {
            return $this->abort(400, [
                'title'       => 'request validation error',
                'description' => json_decode($exception->getMessage()),
            ]);
        } catch (DeclApiException $exception) {
            return $this->abort($exception->getResponseCode(), [
                'title'       => $exception->getTitle(),
                'description' => $exception->getDescription(),
            ], $exception->getHeaders());
        } catch (\Exception $exception) {
            if (env('APP_DEBUG')) {
                return $this->abort(501, ['message' => $exception->getMessage(), 'trace' => explode("\n", $exception->getTraceAsString())]);
            }

            $errorsInfo = $this->errorsInfo()->getData();
            foreach ($errorsInfo as $key => $errorItem) {
                if($errorItem->isRegexKey() && preg_match('!'.$key.'!ui',$exception->getMessage())){
                    return $this->abort($exception->getResponseCode(), [
                        'title'       => $exception->getMessage(),
                        'description' => $errorItem->getErrorDescription()
                    ], $exception->getHeaders());
                }elseif($key === $exception->getMessage()){
                    return $this->abort($exception->getResponseCode(), [
                        'title'       => $key,
                        'description' => $errorItem->getErrorDescription()
                    ], $exception->getHeaders());
                }
            }

            return $this->abort(501, ['message' => 'Произошла системная ошибка. Обратитесь к разарботчикам']);
        }

        return $this->illuminateResponse->setContent($response->jsonSerialize());
    }

    public function abort($code = 501, $message, $headers = [])
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
            'parameter' => $illuminateRequest->route()->parameters(),
            'header'    => $illuminateRequest->header(),
            'json'      => $illuminateRequest->json()->all(),
        ];
    }
}