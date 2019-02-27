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
    protected function initRules()
    {

    }
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

    /**
     * Создание объекта.
     * Object constructor.
     *
     * @param array $data
     *
     * @throws \Exception
     */
    public function __construct($data = [])
    {
        $this->rulesInfo();

        $this->initRules();

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
            if ($rule->isArray()) {
                $valueMutated = [];
                foreach ($value as $subkey => $subvalue) {
                    $valueMutated[$subkey] = $this->mutate($rule, $subvalue);
                }
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
     * @return int
     */
    protected function mutate(RuleItem $rule, $value)
    {
        if ($rule->isObject()) {
            $className = $rule->getType();
            return new $className($value);
        }

        if ($rule->getType() === 'integer') {
            return (int)$value;
        }

        return $value;
    }

    //endregion

    /**
     * Валидация данных
     *
     * @return \Illuminate\Contracts\Validation\Validator
     * @throws \Exception
     */
    public function validator(): \Illuminate\Contracts\Validation\Validator
    {
        return (new ValidatorFactory)->make($this->dataRaw, $this->rulesInfo()->rules());
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