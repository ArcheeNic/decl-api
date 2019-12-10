<?php namespace DeclApi\Documentor\OpenApi3;

use DeclApi\Core\DeclApiCoreException;
use DeclApi\Core\Point;
use DeclApi\Core\RuleItem;
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
                'summary'     => $itemPoint->getTitle()
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

            if (!empty($requestRules['json'])) {
                $requestName = $className.'Request';
                $this->addSchemaRequestJson($requestName, $requestRules['json']);
                $point['requestBody'] = [
                    'required' => true,
                    'content'  => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => $this->refName($requestName)
                            ]
                        ]
                    ]
                ];
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

        if (empty($configData['components'])) {
            $configData['components'] = [];
        }

        if (empty($configData['components']['schemas'])) {
            $configData['components']['schemas'] = [];
        }

        $configData['components']['schemas'] = array_replace($configData['components']['schemas'], $this->schemas);
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

    /**
     * @param string $className
     *
     * @return null
     * @throws DeclApiCoreException
     * @throws \Exception
     */
    public function addSchema(string $className)
    {
        $schemaName = $this->schemaName($className);

        if (isset($this->schemas[$schemaName])) {
            return null;
        }

        if (!$class = $this->getClass($className)) {
            return null;
        }

        $this->schemas[$schemaName] = [
            'description' => $this->makeDocDescription($class->getTitle(), $class->getDescription()),
            'properties'  => $this->makeObjectProperties($class->getRules()->getData())
        ];
        $required                   = $this->getObjectRequiredFields($class->getRules()->getData());
        if (count($required) > 0) {
            $this->schemas[$schemaName]['required'] = $required;
        }
        return null;
    }


    /**
     * @param $schemaName
     * @param $rules
     *
     * @return null
     * @throws DeclApiCoreException
     */
    public function addSchemaRequestJson($className, $rules)
    {
        $schemaName = $this->schemaName($className);

        if (isset($this->schemas[$schemaName])) {
            return null;
        }

        $this->schemas[$schemaName] = [
            'properties' => $this->makeObjectProperties($rules)
        ];

        $required = $this->getObjectRequiredFields($rules);
        if (count($required) > 0) {
            $this->schemas[$schemaName]['required'] = $required;
        }
        return null;
    }

    /**
     * @param $className
     *
     * @return string
     * @throws DeclApiCoreException
     */
    protected function refName($className)
    {
        return '#/components/schemas/'.$this->schemaName($className);
    }

    /**
     * @param $className
     *
     * @return string|string[]|null
     * @throws DeclApiCoreException
     */
    protected function schemaName($className)
    {

        $schemaName = $className;

        $replaceSchemas = [];

        if (
            !empty($this->configData['documentor'])
            && !empty($this->configData['documentor']['replaceSchemas'])
        ) {
            if (!is_array($this->configData['documentor']['replaceSchemas'])) {
                throw new DeclApiCoreException('documentor.replaceSchemas is not array');
            }

            $replaceSchemas = $this->configData['documentor']['replaceSchemas'];
        }

        foreach ($replaceSchemas as $key => $replaceSchema) {
            if (!is_array($replaceSchema)) {
                throw new DeclApiCoreException('documentor.replaceSchemas.'.$key.' is not array');
            }
            if (!count($replaceSchema)) {
                throw new DeclApiCoreException('documentor.replaceSchemas.'.$key.' the number of elements is 2');
            }
            $replaceSchema = array_values($replaceSchema);
            $schemaName    = preg_replace($replaceSchema[0], $replaceSchema[1], $schemaName);
        }

        return $schemaName;
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
            'description' => $this->makeDescriptionRule($ruleItem),
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
     * @throws DeclApiCoreException
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
     * @param $title
     * @param $description
     *
     * @return string
     */
    protected function makeDocDescription($title = '', $description, $descriptionGlue = "\n")
    {
        if (is_array($description)) {
            $descriptionCompiled = implode($descriptionGlue, $description);
        } else {
            $descriptionCompiled = $description;
        }

        $descriptionCompiled = '<small><i>'.$descriptionCompiled.'</i></small>';

        $return = '';
        if ($title && $description) {
            $return = '<div>'.$title.'</div><div>'.$descriptionCompiled.'</div>';
        } elseif ($title) {
            $return = $title;
        } elseif ($description) {
            $return = $description;
        }
        return $return;
    }

    /**
     * Генерация описания
     *
     * @param RuleItem $responseRule
     *
     * @return string
     */
    public function makeDescriptionRule(RuleItem $responseRule)
    {
        return $this->makeDocDescription($responseRule->getTitle(), $responseRule->getDescription());
    }

    /**
     * Создать массив с данными об объекте
     *
     * @param RuleItem[] $responseRules
     *
     * @return array
     * @throws \Exception
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
                    'description' => $this->makeDocDescription($responseRule->getTitle(),
                        $responseRule->getDescription()),
                    // хак, для того чтобы оторажалось описание для $ref
                    'allOf'       => [
                        ['$ref' => $this->refName($responseRule->getType())]
                    ]
                ];
            } else {
                $json[$responseRule->getKey()] = $this->makeOneResponseProperty($responseRule);
            }
        }

        return $json;
    }

    /**
     * Получает массив обязательных полей
     *
     * @param RuleItem[] $responseRules
     *
     * @return array
     * @throws \Exception
     */
    protected function getObjectRequiredFields(array $responseRules)
    {
        $result = [];
        foreach ($responseRules as $responseRule) {
            if ($responseRule->isRequired()) {
                $result[] = $responseRule->getKey();
            }
        }
        return $result;
    }


    /**
     * Создать простой объект
     *
     * @return array
     * @throws \Exception
     */
    public function makeOneResponseProperty(RuleItem $responseRule)
    {
        $object = [
            'type'        => Property::getFormatFromValidator($responseRule->getType()),
            'description' => $this->makeDescriptionRule($responseRule),
            'required'    => $responseRule->isRequired()
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
     *
     * @return array
     * @throws DeclApiCoreException
     */
    public function makeArrayResponseProperty(RuleItem $responseRule)
    {
        $object = [
            'type'        => 'array',
            'description' => $this->makeDescriptionRule($responseRule)
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
            ];
        } else {
            $object['items'] = [
                'type' => Property::getFormatFromValidator($responseRule->getType()),
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