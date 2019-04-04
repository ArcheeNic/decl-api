<?php namespace DeclApi\Documentor\OpenApi3;

use DeclApi\Core\Point;
use DeclApi\Core\RuleItem;
use DeclApi\Documentor\FileSystem;
use DeclApi\Documentor\ItemObject;
use DeclApi\Documentor\OpenApi3\Specification\Property;

class MakeDoc extends FileSystem
{
    protected $configRoot;
    protected $configFileSystem;
    protected $configList;

    /**
     * MakeDoc constructor.
     *
     * @param string $configsRoot
     * @param array  $data
     *
     * @throws \Exception
     */
    public function __construct(string $configsRoot)
    {
        $this->configRoot       = $this->absolutePath($configsRoot);
        $this->configFileSystem = $this->newLocalFileSystem($this->configRoot);
    }

    /**
     * Документы генерируются на основе файлов конфигурации.
     * То есть без них ничего не будет сгенерировано, даже если будет весь объем информации
     *
     * @param array      $data
     * @param array      $configs список конфигураций, которые должны быть использованы
     *                            если пустой массив - значит все
     * @param       bool $clear   очистить служебную информваци и оставить только информцаию согласно спецификации
     *                            OpenApi v3
     *
     * @return array
     * @throws \Exception
     */
    public function generate(array $data, array $configs = [], $clear = false): array
    {
        $dataConfigs = [];
        foreach ($configs as $config) {
            $configItem = $this->generateConfig($config, $data);
            if ($clear) {
                $configItem = $this->clearGenereatedData($configItem);
            }
            $dataConfigs[$config] = $configItem;
        }
        return $dataConfigs;
    }

    public function getConfigs($configs): array
    {
        if (empty($configs)) {
            // если не переданы ключи конфигов - то закидываем все
            return $this->getAvailableConfigs();
        }
        // если переданы ключи конфигов, то берем только доступные
        return array_intersect($configs, $this->getAvailableConfigs());
    }

    /**
     * @param array $data
     * @param array $configs
     *
     * @throws \Exception
     */
    public function generateAndSave(array $data, array $configs = [])/*TODO downgrade - : void*/
    {
        $dataGenerated = $this->generate($data, $configs);
        foreach ($dataGenerated as $key => $value) {
            $this->save($value);
        }
    }

    public function save($data)
    {
        $filePath = $data['documentor']['savePath'];
        $saveData = $this->clearGenereatedData($data);
        $dir      = dirname($data['documentor']['savePath']);
        if (!file_exists($dir) && !mkdir($dir) && !is_dir($dir)) {
            return null;
        }
        file_put_contents($filePath, json_encode($saveData));
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function clearGenereatedData(array $data): array
    {
        unset($data['documentor']);
        if (empty($data['tags'])) {
            unset($data['tags']);
        }
        return $data;
    }

    /**
     * @param string $configKey ключ конфигурации
     * @param array  $data      нужны все данные сканирования, чтобы использвать связи
     *
     * @return mixed
     * @throws \Exception
     */
    public function generateConfig(string $configKey, array $data)
    {
        if (!isset($data['route'][$configKey])) {
            throw new \Exception('Config {'.$configKey.'} routes not found');
        }

        $configData = $this->getConfigData($configKey);

        $configGenerator = new ConfigGenerator($configData,$data);
        $configData = $configGenerator->generateConfig($configKey);

        return $configData;
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
     * @param Point $point
     *
     * @return array
     */
    protected function makeResponseErrors(Point $point): array
    {
        $response = $point->errorsInfo()->getData();
        $data     = [];
        foreach ($response as $key => $value) {
            $httpCode        = $value->getHttpCode();
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

    /**
     * Создать массив с данными об объекте
     *
     * @param RuleItem[] $responseRules
     * @param array $responseRules
     * @param array $data
     *
     * @return array
     */
    protected function makeObjectProperties(array $responseRules, array $data)
    {

        $json = [];

        foreach ($responseRules as $responseRule) {
            if ($responseRule->isArray()) {
                $json[$responseRule->getKey()] = $this->makeArrayResponseProperty($responseRule, $data);
            } elseif ($responseRule->isObject()) {
                /**
                 * @var \DeclApi\Documentor\ItemObject $itemObject
                 */
                $itemObject                    = $data['object'][$responseRule->getType()];
                $rules                         = $itemObject->getRules()->getData();
                $json[$responseRule->getKey()] = [
                    'type'        => 'object',
                    'description' => $this->makeDescription($responseRule),
                    'properties'  => $this->makeObjectProperties($rules, $data)
                ];
            } else {
                $json[$responseRule->getKey()] = $this->makeOneResponseProperty($responseRule, $data);
            }
        }

        return $json;
    }

    /**
     * Создать простой объект
     *
     * @param RuleItem $responseRule
     */
    public function makeOneResponseProperty(RuleItem $responseRule, array $data)
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
     * @param  RuleItem $responseRule
     */
    public function makeArrayResponseProperty(RuleItem $responseRule, array $data)
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
            $itemObject      = $data['object'][$responseRule->getType()];
            $rules           = $itemObject->getRules()->getData();
            $object['items'] = [
                'type'       => 'object',
                'properties' => $this->makeObjectProperties($rules, $data)
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
     * Генерация описания
     *
     * @param RuleItem $responseRule
     *
     * @return string
     */
    public function makeDescription(RuleItem $responseRule){
        if ($responseRule->getDescription()) {
            $description = '<div>'.$responseRule->getTitle().'</div>';
            $description .= '<div><small><i>'.$responseRule->getDescription().'</i></small></div>';
        } else {
            $description = $responseRule->getTitle();
        }
        return $description;
    }

    /**
     * @param ItemObject $response
     *
     * @return array
     */
    protected function makeResponseOk(ItemObject $response, array $data): array
    {

        $responseRules = $response->getRules()->getData();

        $json = $this->makeObjectProperties($responseRules, $data);

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
     * Получить данные о файле конфигруации
     *
     * @param string $configKey
     *
     * @return array
     * @throws \Exception
     */
    protected function getConfigListItem(string $configKey): array
    {
        $configList = $this->getConfigList();

        if (!$configFile = $configList[$configKey] ?? null) {
            throw new \Exception('Config {'.$configKey.'} not found');
        }

        return $configList[$configKey];
    }

    /**
     * @param string $configKey
     *
     * @return mixed
     * @throws \Exception
     */
    protected function getConfigData(string $configKey)
    {
        $configFile = $this->getConfigListItem($configKey);

        $filepath = realpath($this->configRoot.'/'.$configFile['basename']);
        $data     = include($filepath);

        if (!is_array($data)) {
            throw new \Exception('Config {'.$configKey.'} file is not return array');
        }

        return $data;

    }

    public function getAvailableConfigs()
    {
        return array_keys($this->getConfigList());
    }

    /**
     * Получаем список конфигурационных файлов
     *
     * @return array
     */
    public function getConfigList(): array
    {
        if ($this->configList !== null) {
            return $this->configList;
        }


        $this->configList = $this->scanConfigList();

        return $this->configList;
    }

    protected function scanConfigList()
    {
        $fsItems       = $this->configFileSystem->listContents('');
        $configListRaw = $this->arrayFilterPhp($fsItems);
        $configList    = [];
        foreach ($configListRaw as $value) {
            $configList[$value['filename']] = $value;
        }
        return $configList;
    }
}