<?php namespace DeclApi\Core;

/**
 * Объект данных.
 * Class Object
 *
 * @package DeclApi\Core
 */
abstract class ObjectClass implements \IteratorAggregate, \JsonSerializable
{
    //region Правила хранимых полей
    /**
     * Информация о правилах хранимых полей
     *
     * @var RulesInfo
     */
    protected $rulesInfo;

    /**
     * Получить информацию, если же ее нет, инициировать
     *
     * @return RulesInfo
     */
    public function rulesInfo()
    {
        if ($this->rulesInfo === null) {
            $this->rulesInfo = new RulesInfo();
        }
        return $this->rulesInfo;
    }

    /**
     * Добавить информацию о правилах хранимых полей
     */
    abstract protected function initRules();
    //endregion

    /**
     * Входящие данные
     * Хранятся здесь для отладки или решения споров
     *
     * @var array
     */
    protected $dataRaw = [];

    /**
     * @var array мутированные данные
     */
    protected $dataMutated = [];

    protected $preValidate = true;

    /**
     * Создание объекта.
     * Object constructor.
     *
     * @param array $data        данные
     * @param bool  $preValidate предварительная валидация (происходит между записью массива и мутацией)
     *
     * @throws \Exception
     */
    public function __construct(array $data = [], bool $preValidate = true)
    {
        $this->preValidate = $preValidate;
        $this->rulesInfo();

        $this->initRules();

        if ($preValidate === true) {
            $validator = $this->validatorCustom($data);
            if ($validator->fails()) {
                throw new DeclApiValiadateException($this, $validator);
            }
        }

        $this->setData($data);
        $this->setDefaults();

        $this->mutateAll();
    }

    //region Работа с исходными данными

    /**
     * Установить исходные данные (без мутирования)
     * Рекомендуется после этого вызвать мутатор, если не будет дополнительных манипуляций с исходными данными (см.
     * конструктор)
     *
     * @param array $data
     */
    protected function setData($data = [])
    {
        foreach ($data as $key => $value) {
            $this->dataRaw[$key] = $value;
        }
    }


    public function cleanDiffData()
    {
        foreach ($this->dataMutated() as $key => $value) {
            if (!$this->rulesInfo()->get($key)) {
                unset($this->dataMutated()[$value]);
            }
        }
    }

    /**
     * Подготовить и установить умолчания в исходные данные для полей
     * Рекомендуется делать в самом конце  (см. конструктор)
     */
    protected function setDefaults()
    {
        foreach ($this->rulesInfo()->getData() as $item => $value) {
            /**
             * @var RuleItem $value
             */
            if (!isset($this->dataRaw[$item]) && $value->isDefault()) {
                $this->dataRaw[$item] = $value->getDefault();
            }
        }
    }

    /**
     * Мутация всех данных
     */
    protected function mutateAll()
    {
        foreach ($this->dataRaw as $key => $value) {
            $rule = $this->rulesInfo()->get($key);
            if (!$rule) {
                $valueMutated = $value;
                //     continue;
            } else {
                $valueMutated = $this->mutate($rule, $value);
            }

            $this->dataMutated[$key] = $valueMutated;
        }
    }

    /**
     * Мутация конкретного поля
     *
     * @param RuleItem $rule
     * @param          $value
     *
     * @return mixed
     */
    protected function mutate(RuleItem $rule, $value)
    {
        if ($rule->isArray()) {
            if (!is_array($value)) {
                return [];
            }
            $valueMutated = [];
            $subrule      = clone $rule;
            $subrule->setIsArray(false);
            foreach ($value as $subkey => $subvalue) {
                $valueMutated[$subkey] = $this->mutate($subrule, $subvalue);
            }
            return $valueMutated;
        }

        if ($rule->isObject()) {
            $className = $rule->getType();
            if (!is_array($value)) {
                throw new DeclApiCoreException('Некорретный тип данных. Текущий тип: '.gettype($value).', ожидаемый: '.$rule->getType().'. Целевое поле: '.$rule->getKey().'. Значение:'.dump($value));
            }
            return new $className($value, $this->preValidate);
        }

        if ($rule->getType() === 'integer') {
            return (int)$value;
        }

        return $value;
    }

    //endregion

    /**
     * Валидация данных
     * Перед валидатором проводится строгая системная валидация
     *
     * @return \Illuminate\Contracts\Validation\Validator
     * @throws \Exception
     */
    public function validator(): \Illuminate\Contracts\Validation\Validator
    {
        return (new ValidatorFactory)->make($this->dataRaw, $this->rulesInfo()->rules());
    }

    /**
     * Валидация данных
     * Перед валидатором проводится строгая системная валидация
     *
     * @return \Illuminate\Contracts\Validation\Validator
     * @throws \Exception
     */
    public function validatorCustom($data): \Illuminate\Contracts\Validation\Validator
    {
        return (new ValidatorFactory)->make($data, $this->rulesInfo()->rules());
    }

    //region Работа с мутированными данными (конечные данные для работы)

    /**
     * Геттер данных
     *
     * @param $name
     *
     * @return mixed|null
     */
    protected function getField($name)
    {
        return $this->dataMutated[$name] ?? null;
    }

    /**
     * Сеттер данных
     *
     * @param $name
     * @param $value
     */
    protected function setField($name, $value)
    {
        if ($rule = $this->rulesInfo()->get($name)) {
            $value = $this->mutate($rule, $value);
        }
        $this->dataMutated[$name] = $value;
    }

    public function dataMutated()
    {
        return $this->dataMutated;
    }
    //endregion

    //region Системные функции
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        $array = $this->dataMutated;
        foreach ($array as $key => $value) {
            if ($value instanceof ObjectClass) {
                $array[$key] = $value->toArray();
            }
            if (is_array($value)) {
                foreach ($value as $subkey => $subvalue) {
                    if (is_array($subvalue)) {
                        foreach ($subvalue as $subsubkey => $subsubvalue) {
                            if (empty($array[$key][$subkey])) {
                                $array[$key][$subkey] = [];
                            }
                            if ($subsubvalue instanceof ObjectClass) {
                                $array[$key][$subkey][$subsubkey] = $subsubvalue->toArray();
                            }
                        }
                    } else {
                        if ($subvalue instanceof ObjectClass) {
                            $array[$key][$subkey] = $subvalue->toArray();
                        }
                    }
                }
            }
        }
        return $array;
    }

    /**
     * @return \ArrayIterator|\Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->dataRaw);
    }
    //endregion
}