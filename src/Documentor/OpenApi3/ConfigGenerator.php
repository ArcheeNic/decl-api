<?php namespace DeclApi\Documentor\OpenApi3;

use DeclApi\Core\DeclApiCoreException;
use DeclApi\Core\Point;
use DeclApi\Core\RuleItem;
use DeclApi\Documentor\ItemInfo;
use DeclApi\Documentor\ItemObject;
use DeclApi\Documentor\ItemPoint;
use DeclApi\Documentor\ItemRequest;
use DeclApi\Documentor\OpenApi3\Specification\Property;

class ConfigGenerator
{
    protected $configData;
    protected $classData; // нужны все данные сканирования, чтобы использвать связи
    protected $schemas = [];

    /**
     * ConfigGenerator constructor.
     *
     * @param $configData
     * @param $classData
     */
    public function __construct(array $configData, array $classData)
    {
        $this->configData = $configData;
        $this->classData  = $classData;
    }

    /**
     * @param string $configKey ключ конфигурации
     *
     * @return mixed
     * @throws \Exception
     */
    public function generateConfig(string $configKey)
    {
        $configData = $this->configData;

        foreach ($this->classData['route'][$configKey] as $routePoint) {
            $method = mb_strtolower($routePoint['method']);
            $action = '/'.$routePoint['action'];

            $replaceUri = [];
            if (isset($configData['documentor']['replaceUri'])) {
                $replaceUri = $configData['documentor']['replaceUri'];
                if (!is_array($replaceUri)) {
                    throw new DeclApiCoreException('Поле replaceUri в шаблоне документации должно быть массивом');
                }
            }

            foreach ($replaceUri as $value) {
                if (!is_array($value) || count($value) != 2) {
                    throw new DeclApiCoreException('Дочернее свойство поля replaceUri в шаблоне документации должно быть массивом и должно содержать 2 значения');
                }
                $action = preg_replace($value[0], $value[1], $action);
            }

            $className = $routePoint['class'];
            $class     = new $className();

            /**
             * @var ItemPoint $itemPoint
             * @var Point     $class
             */
            $itemPoint = $this->classData['point'][$configKey][$className];

            $point = [
                'description' => implode("\n", $itemPoint->getDescription())."\n"
                                 .$this->makeResponsesDescription($class),
                'summary'     => $itemPoint->getTitle(),
            ];

            if ($itemPoint->getTag()) {
                $point['tags'] = $itemPoint->getTag();
            }


            //region Примеры кода
            $samples = [];

            foreach ($class->exampleCallCode()->getData() as $key => $value) {
                if (empty($value)) {
                    continue;
                }
                $samples[] = [
                    'lang'   => $key,
                    'source' => $value
                ];
            }

            if (count($samples)) {
                $point['x-code-samples'] = $samples;
            }
            //endregion


            /**
             * @var ItemRequest $request
             */
            $request = $this->classData['request'][$itemPoint->request] ?? null;

            $requestRules = $request->getRules()->getData();

            $parameters = [];

            /**
             * @var RuleItem $headerRule
             */
            foreach ($requestRules['header'] as $rule) {
                $parameters[] = $this->makeParameterHeader($rule);
            }
            foreach ($requestRules['parameter'] as $rule) {
                $parameters[] = $this->makeParameterQuery($rule);
            }
            foreach ($requestRules['cookie'] as $rule) {
                $parameters[] = $this->makeParameterCookie($rule);
            }

            $point['parameters'] = $parameters;

            /**
             * @var ItemObject $response
             */
            $response = $this->classData['object'][$itemPoint->response] ?? null;

            $point['responses'] = ['200' => $this->makeResponseOk($response)];
            foreach ($this->makeResponseErrors($class) as $key => $value) {
                $point['responses'][$key] = $value;
            }

            if (!isset($configData['paths'])) {
                $configData['paths'] = [];
            }
            if (!isset($configData['paths'][$action])) {
                $configData['paths'][$action] = [];
            }
            $configData['paths'][$action][$method] = $point;
        }
        return $configData;
    }

    /**
     * @param string $className
     *
     * @return ItemObject|null
     */
    public function getClass(string $className)
    {
        if (!isset($this->classData['object'][$className])) {
            return null;
        }
        return $this->classData['object'][$className];
    }

    public function addSchema(string $className)
    {
        if (isset($this->schemas[$className])) {
            return null;
        };

        if (!$class = $this->getClass($className)) {
            return null;
        };

        $this->schemas[$className] = $this->makeObjectProperties($class->getRules()->getData());
    }


    /**
     * @param Point $point
     *
     * @return string
     */
    protected function makeResponsesDescription(Point $point): string
    {
        $data = [];

        //region Готовим ошибки
        $errors   = [];
        $response = $point->errorsInfo()->getData();
        foreach ($response as $key => $value) {
            $code                               = $value->getHttpCode();
            $errors[$code.'_'.$value->getKey()] = '<li><strong>'.$code.'</strong> - '.$value->getDocDescription()
                                                  .'</li>';
        }
        ksort($errors);

        if (count($errors)) {
            $data[] = '<hr/>';
            $data[] = '<h4>Список ошибок</h4>';
            $data[] = '<ul>';

            foreach ($errors as $error) {
                $data[] = $error;
            }
            $data[] = '</ul>';
        }
        //endregion

        return implode("\n", $data);
    }


    /**
     * Параметры заголовков
     *
     * @param RuleItem $ruleItem
     *
     * @return array
     * @throws \Exception
     */
    protected function makeParameterHeader(RuleItem $ruleItem): array
    {
        $data         = $this->makeParameter($ruleItem);
        $data['in']   = 'header';
        $data['name'] = str_replace('.*', '', $ruleItem->getKey());
        return $data;
    }


    /**
     * Параметры кукисов
     *
     * @param RuleItem $ruleItem
     *
     * @return array
     * @throws \Exception
     */
    protected function makeParameterCookie(RuleItem $ruleItem): array
    {
        $data         = $this->makeParameter($ruleItem);
        $data['in']   = 'cookie';
        $data['name'] = $ruleItem->getKey();
        return $data;
    }

    /**
     * Параметры запроса
     *
     * @param RuleItem $ruleItem
     *
     * @return array
     * @throws \Exception
     */
    protected function makeParameterQuery(RuleItem $ruleItem): array
    {
        $data         = $this->makeParameter($ruleItem);
        $data['in']   = 'query';
        $data['name'] = $ruleItem->getKey();
        return $data;
    }


    /**
     * Подготовить параметр
     *
     * @param RuleItem $ruleItem
     *
     * @return array
     * @throws \Exception
     */
    protected function makeParameter(RuleItem $ruleItem): array
    {
        $data = [
            'name'        => $ruleItem->getKey(),
            'description' => $this->makeDescription($ruleItem),
            'schema'      => [
                'type' => Property::getFormatFromValidator($ruleItem->getType())
            ],
        ];
        if ($ruleItem->getEnum()) {
            $data['schema']['enum'] = $ruleItem->getEnum();
        }
        if ($ruleItem->getDefault() !== null) {
            $data['schema']['default'] = $ruleItem->getDefault();
        }
        if ($ruleItem->isRequired()) {
            $data['required'] = true;
        }
        return $data;
    }


    /**
     * @param ItemObject $response
     *
     * @return array
     */
    protected function makeResponseOk(ItemObject $response): array
    {

        $responseRules = $response->getRules()->getData();

        $this->addSchema($response->getClassname());

        $json = $this->makeObjectProperties($responseRules);

        return [
            'description' => '',
            'content'     => [
                'application/json' => [
                    'schema' =>
                        [
                            'type'       => 'object',
                            'properties' => $json
                        ]
                ]
            ]
        ];
    }

    /**
     * Генерация описания
     *
     * @param RuleItem $responseRule
     *
     * @return string
     */
    public function makeDescription(RuleItem $responseRule)
    {
        if ($responseRule->getDescription()) {
            $description = '<div>'.$responseRule->getTitle().'</div>';
            $description .= '<div><small><i>'.$responseRule->getDescription().'</i></small></div>';
        } else {
            $description = $responseRule->getTitle();
        }
        return $description;
    }

    /**
     * Создать массив с данными об объекте
     *
     * @param RuleItem[] $responseRules
     * @param array      $data
     *
     * @return array
     */
    protected function makeObjectProperties(array $responseRules)
    {
        $json = [];

        foreach ($responseRules as $responseRule) {
            if ($responseRule->isArray()) {
                $json[$responseRule->getKey()] = $this->makeArrayResponseProperty($responseRule);
            } elseif ($responseRule->isObject()) {
                $this->addSchema($responseRule->getType());

                $json[$responseRule->getKey()] = [
                    '$ref' => $responseRule->getType()
                ];
            } else {
                $json[$responseRule->getKey()] = $this->makeOneResponseProperty($responseRule);
            }
        }

        return $json;
    }


    /**
     * Создать простой объект
     *
     * @param RuleItem $responseRule
     */
    public function makeOneResponseProperty(RuleItem $responseRule)
    {
        $object = [
            'type'        => Property::getFormatFromValidator($responseRule->getType()),
            'description' => $this->makeDescription($responseRule)
        ];

        if (($example = $this->getExample($responseRule)) !== null) {
            $object['example'] = $example;
        }

        return $object;
    }

    public function getExample(RuleItem $responseRule)
    {
        if ($responseRule->getExampleValue() !== null) {
            return $responseRule->getExampleValue();
        } elseif ($responseRule->getDefault() !== null) {
            return $responseRule->getDefault();
        }
        return null;
    }

    /**
     * Создать параметр - массив
     *
     * @param RuleItem $responseRule
     * @param array    $data
     *
     * @return array
     */
    public function makeArrayResponseProperty(RuleItem $responseRule)
    {
        $object = [
            'type'        => 'array',
            'description' => $this->makeDescription($responseRule)
        ];

        if ($responseRule->getType() === 'object') {
            $object['items'] = "{}";
            if (($example = $this->getExample($responseRule)) !== null) {
                $object['example'] = $example;
            }

        } elseif ($responseRule->isObject()) {
            /**
             * @var \DeclApi\Documentor\ItemObject $itemObject
             */
            $itemObject      = $this->classData['object'][$responseRule->getType()];
            $rules           = $itemObject->getRules()->getData();
            $object['items'] = [
                'type'       => 'object',
                'properties' => $this->makeObjectProperties($rules)
                // TODO: Нужно сделать как-то дочерние объекты
                //                'properties' => $this->makeOneResponseProperty($responseRule->getType())
            ];
        } else {
            $object['items'] = [
                'type' => Property::getFormatFromValidator($responseRule->getType()),
                // TODO: Нужно сделать как-то дочерние объекты
                //                'properties' => $this->makeOneResponseProperty($responseRule->getType())
            ];
        }

        return $object;
    }

    /**
     * @param Point $point
     *
     * @return array
     */
    protected function makeResponseErrors(Point $point): array
    {
        $response = $point->errorsInfo()->getData();
        $data     = [];
        foreach ($response as $key => $value) {
            $httpCode = $value->getHttpCode();

            $data[$httpCode] = [
                'description' => '',
                'content'     => [
                    'application/json' => [
                        'schema'  => [
                            '$ref' => '#/components/schemas/error',
                        ],
                        'example' => [
                            'code'        => $httpCode,
                            'title'       => $value->getErrorTitle(),
                            'description' => $value->getErrorDescription(),
                        ],
                    ]
                ]
            ];
        }
        return $data;
    }
}