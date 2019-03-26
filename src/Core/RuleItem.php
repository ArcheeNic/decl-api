<?php namespace DeclApi\Core;

/**
 * Правило хранимого поля
 * Class RuleItem
 *
 * @package DeclApi\Core
 */
class RuleItem
{
    /**
     * @var bool является ли поле объектом
     */
    protected $isObject = false;
    /**
     * @var string тип хранимых данных
     */
    protected $type = '';
    /**
     * @var string поле
     */
    protected $key = '';
    /**
     * @var string Название поля
     */
    protected $title = '';
    /**
     * @var string описание поля
     */
    protected $description = '';
    /**
     * @var array возможные варианты поля
     */
    protected $enum = [];
    /**
     * @var array свойства валидации (см. Illuminate/Validator)
     */
    protected $attributes = [];
    /**
     * @var null значение по умолчанию
     */
    protected $default = null;
    /**
     * @var null установлено ли значение по умолчанию (на случай null)
     */
    protected $isDefault = false;
    /**
     * @var null Поле для теста или примера
     */
    protected $exampleValue = null;
    /**
     * @var bool является поле массивом
     */
    protected $isArray = false;

    /**
     * RuleItem constructor.
     *
     * @param $type
     * @param $key
     * @param $title
     * @param $description
     */
    public function __construct($type, $key, $title, $description)
    {
        $this->type        = $type;
        $this->key         = $key;
        $this->title       = $title;
        $this->description = $description;
    }

    /**
     * получить имя поля
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Установить имя поля
     */
    /**
     * @param string $key
     *
     * @return RuleItem
     */
    public function setKey(string $key): RuleItem
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Получить название поля
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Установить название поля
     *
     * @param string $title
     *
     * @return RuleItem
     */
    public function setTitle(string $title): RuleItem
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Получить описание поля
     *
     * @return string
     */
    public function getDescription(): string
    {
        return (string)$this->description;
    }

    /**
     * Установить описание поля
     *
     * @param string $description
     *
     * @return RuleItem
     */
    public function setDescription(string $description): RuleItem
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Получить варианты поля
     *
     * @return array
     */
    public function getEnum(): array
    {
        return $this->enum;
    }

    /**
     * Установить варианты поля
     *
     * @param array $enum
     *
     * @return RuleItem
     */
    public function setEnum(array $enum): RuleItem
    {
        $this->enum = $enum;
        return $this;
    }

    /**
     * Получить параметры валидации
     *
     * @return array
     * @throws \Exception
     */
    public function getAttributes(): array
    {
        if (is_array($this->attributes)) {
            if ($this->isObject()) {
                $attributes = $this->attributes;
            } else {
                $attributes = array_merge([$this->type], $this->attributes);
            }
        } else {
            throw new DeclApiException('Некорректное определение свойства', 'Должен быть массив или строка');
        }
        // это только для специальных типов, таких как массив или объект
        $attributes = array_diff($attributes,['array','object']);

        return $attributes;
    }

    /**
     * Уставновить параметры валидации
     *
     * @param mixed $attributes
     *
     * @return RuleItem
     */
    public function setAttributes($attributes)
    {
        if (is_string($attributes)) {
            $attributes = explode('|', $attributes);
        }

        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Получить значение по умолчанию
     *
     * @return null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return null
     */
    public function isDefault()
    {
        return $this->isDefault;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isRequired(): bool
    {
        return in_array('required', $this->getAttributes(), true);
    }

    /**
     * Установить значение по умолчанию
     *
     * @param null $default
     *
     * @return RuleItem
     */
    public function setDefault($default)
    {
        $this->isDefault = true;
        $this->default = $default;
        return $this;
    }

    /**
     * Получить значение для теста или примера
     *
     * @return null
     */
    public function getExampleValue()
    {
        return $this->exampleValue;
    }

    /**
     * Установить значение для теста или примера
     *
     * @param null $exampleValue
     *
     * @return RuleItem
     */
    public function setExampleValue($exampleValue)
    {
        $this->exampleValue = $exampleValue;
        return $this;
    }

    /**
     * Получить тип поля
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Установить тип поля
     *
     * @param string $type
     *
     * @return RuleItem
     */
    public function setType(string $type): RuleItem
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Является ли поле объектом?
     *
     * @return bool
     */
    public function isObject(): bool
    {
        return $this->isObject;
    }

    /**
     * Установить - является ли поле объектом
     *
     * @param bool $isObject
     *
     * @return RuleItem
     */
    public function setIsObject(bool $isObject): RuleItem
    {
        $this->isObject = $isObject;
        return $this;
    }

    /**
     * Является ли поле массивом
     *
     * @return bool
     */
    public function isArray(): bool
    {
        return $this->isArray;
    }

    /**
     * Установить - является ли поле массивом
     *
     * @param bool $isArray
     *
     * @return RuleItem
     */
    public function setIsArray(bool $isArray): RuleItem
    {
        $this->isArray = $isArray;
        return $this;
    }
}