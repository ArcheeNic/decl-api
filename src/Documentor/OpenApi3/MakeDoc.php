<?php namespace DeclApi\Documentor\OpenApi3;

use DeclApi\Core\ObjectClass;
use DeclApi\Core\Point;
use DeclApi\Core\Request;
use DeclApi\Core\RuleItem;
use DeclApi\Core\RulesInfo;
use DeclApi\Documentor\FileSystem;
use DeclApi\Documentor\ItemObject;
use DeclApi\Documentor\ItemPoint;
use DeclApi\Documentor\ItemRequest;
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
        foreach ($dataGenerated as $key=>$value) {
            $this->save($value);
        }
    }

    public function save($data){
        $filePath = $data['documentor']['savePath'];
        $saveData = $this->clearGenereatedData($data);
        $dir = dirname($data['documentor']['savePath']);
        if(!file_exists($dir) && !mkdir($dir) && !is_dir($dir)){
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
        unset($data['tags']);
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

        foreach ($data['route'][$configKey] as $routePoint) {
            $method    = mb_strtolower($routePoint['method']);
            $action    = '/'.$routePoint['action'];
            $className = $routePoint['class'];
            $class     = new $className();

            /**
             * @var ItemPoint $itemPoint
             */
            $itemPoint = $data['point'][$configKey][$routePoint['class']];

            $point = [
                'description' => implode("\n", $itemPoint->getDescription())."\n"
                                 .$this->makeResponsesDescription($class),
                'summary'     => $itemPoint->getTitle(),
            ];

            /**
             * @var ItemRequest $request
             */
            $request      = $data['request'][$itemPoint->request] ?? null;
            $requestRules = $request->getRules()->getData();

            $parameters = [];

            /**
             * @var RuleItem $headerRule
             */
            foreach ($requestRules['header'] as $headerRule) {
                $parameters[] = $this->makeParameterHeader($headerRule);
            }
            foreach ($requestRules['parameter'] as $headerRule) {
                $parameters[] = $this->makeParameterQuery($headerRule);
            }
            foreach ($requestRules['cookie'] as $headerRule) {
                $parameters[] = $this->makeParameterCookie($headerRule);
            }
            $point['parameters'] = $parameters;

            /**
             * @var ItemObject $response
             */
            $response = $data['object'][$itemPoint->response] ?? null;

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
        $data     = [];
        $data[]   = '<hr/>';
        $data[]   = '<h4>Список ошибок</h4>';
        $data[]   = '<ul>';
        $errors   = [];
        $response = $point->errorsInfo()->getData();
        foreach ($response as $key => $value) {
            $code                               = $value->getHttpCode();
            $errors[$code.'_'.$value->getKey()] = '<li><strong>'.$code.'</strong> - '.$value->getDocDescription()
                                                  .'</li>';
        }
        ksort($errors);
        foreach ($errors as $error) {
            $data[] = $error;
        }
        $data[] = '</ul>';
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
            $data[$value->getHttpCode()] = [
                'description' => '',
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/error'
                        ]
                    ]
                ]
            ];
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

        $json = [];

        /**
         * @var RuleItem $responseRule
         */
        foreach ($responseRules as $responseRule) {
            $json[$responseRule->getKey()] = [
                'type'        => 'string',
                'description' => $responseRule->getDescription()
            ];
        }

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
            'description' => $ruleItem->getTitle().'. '.$ruleItem->getDescription(),
            'schema'      => [
                'type' => 'string'
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