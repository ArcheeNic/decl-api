<?php namespace DeclApi\Core;

/**
 * Список хранимых полей для запроса
 * Class RulesInfoRequest
 *
 * @package DeclApi\Core
 */
class RulesInfoRequest
{
    /**
     * @var RuleItem[] $data
     */
    protected $data
        = [
            'parameter' => [],
            'parameterRoute' => [],
            'header'    => [],
            'cookie'    => [],
            'json'      => []
        ];

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Данные, валидируемые в заголовках
     *
     * @param string $type        тип (если класс - его имя)
     * @param string $key         ключ по которому будет искаться
     * @param string $title       название поле для документации
     * @param string $description детальное описание
     *
     * @return RuleItem
     * @throws \Exception
     */
    public function addHeader($type, $key, $title, $description): RuleItem
    {
        return $this->add('header', $type, $key.'.*', $title, $description);
    }

    /**
     * Данные, в теле документа
     *
     * @param string $type        тип (если класс - его имя)
     * @param string $key         ключ по которому будет искаться
     * @param string $title       название поле для документации
     * @param string $description детальное описание
     *
     * @return RuleItem
     * @throws \Exception
     */
    public function addParameter($type, $key, $title, $description): RuleItem
    {
        return $this->add('parameter', $type, $key, $title, $description);
    }
    /**
     * Данные, в теле документа
     *
     * @param string $type        тип (если класс - его имя)
     * @param string $key         ключ по которому будет искаться
     * @param string $title       название поле для документации
     * @param string $description детальное описание
     *
     * @return RuleItem
     * @throws \Exception
     */
    public function addParameterRoute($type, $key, $title, $description): RuleItem
    {
        return $this->add('parameterRoute', $type, $key, $title, $description);
    }

    /**
     * Данные, в cookies
     *
     * @param string $type        тип (если класс - его имя)
     * @param string $key         ключ по которому будет искаться
     * @param string $title       название поле для документации
     * @param string $description детальное описание
     *
     * @return RuleItem
     * @throws \Exception
     */
    public function addCookie($type, $key, $title, $description): RuleItem
    {
        return $this->add('cookie', $type, $key, $title, $description);
    }

    /**
     * Данные, в cookies
     *
     * @param string $type        тип (если класс - его имя)
     * @param string $key         ключ по которому будет искаться
     * @param string $title       название поле для документации
     * @param string $description детальное описание
     *
     * @return RuleItem
     * @throws \Exception
     */
    public function addJson($type, $key, $title, $description): RuleItem
    {
        return $this->add('json', $type, $key, $title, $description);
    }

    /**
     * @param string $target      цель - header, cookies, parameterRoute (всё, что внутри строки), parameter (post или get), json (тело в виде json)
     * @param string $type        тип (если класс - его имя)
     * @param string $key         ключ по которому будет искаться
     * @param string $title       название поле для документации
     * @param string $description детальное описание
     *
     * @return RuleItem
     * @throws \Exception
     */
    public function add($target, $type, $key, $title, $description): RuleItem
    {
        $this->checkTarget($target);
        return $this->data[$target][$key] = new RuleItem($type, $key, $title, $description);
    }

    /**
     * Смотреть правила в классе объекта
     *
     * @param string $target    цель - header, cookies, parameterRoute (всё, что внутри строки), parameter (post или get), json (тело в виде json)
     * @param string $className имя класса у которого нужно зарать правила
     *
     * @return RuleItem[]
     * @throws \Exception
     */
    public function see($target, $className)
    {
        $this->checkTarget($target);
        /**
         * @var ObjectClass $class
         */
        $class = new $className([], false);
        $rules = $class->rulesInfo()->getData();

        return $this->data[$target] = $rules;
    }

    /**
     * Получить правило
     *
     * @param $target
     *
     * @return RuleItem|null
     * @throws \Exception
     */
    public function get($target, $value)/*TODO downgrade - : ?RuleItem*/
    {
        $this->checkTarget($target);
        return $this->data[$target][$value] ?? null;
    }

    /**
     * Проверить что цель указана верно и заявлена в классе
     *
     * @param $target
     *
     * @throws \Exception
     */
    protected function checkTarget($target)
    {
        if (!isset($this->data[$target])) {
            throw new DeclApiException('Incorrect rules target', 'Target {'.$target.'} rule');
        }
    }

    /**
     * Получить правила только по одной группе
     *
     * @param $target
     *
     * @return array
     * @throws \Exception
     */
    public function rulesGroup($target)
    {
        $this->checkTarget($target);
        $rulesTarget = $this->data[$target];
        $rules       = [];
        foreach ($rulesTarget as $key => $value) {
            /**
             * @var $value RuleItem
             */
            if ($value->isArray()) {
                $rules[$key][] = 'array';

                if (in_array('required', $value->getAttributes())) {
                    $rules[$key][] = 'required';
                }

                $key = $key.'.*';
            }
            if ($value->isObject()) {
                $className = $value->getType();
                /**
                 * @var $class Request
                 */
                $class      = new $className([], false);
                $rulesChild = $class->rulesInfo()->rules($target);
                foreach ($rulesChild as $childKey => $childValue) {
                    $rules[$key.'.'.$childKey] = $childValue;

                    foreach ($rules[$key.'.'.$childKey] as $keyAttr => $valueAttr) {
                        $rules[$key.'.'.$childKey][$keyAttr] = str_replace('{this}.', '{this}.'.$key.'.', $valueAttr);
                    }
                }
            } else {
                $rules[$key] = $value->getAttributes();
            }
        }
        return $rules;
    }

    public function rulesGroupCompiled($target)
    {
        $rules = $this->rulesGroup($target);
        $rules = $this->rulesClearRelative($rules);
        return $rules;
    }

    /**
     * Получить все правила
     *
     * @throws \Exception
     */
    public function rules()
    {
        $rules = [];
        $keys  = array_keys($this->data);

        foreach ($keys as $key) {
            /**
             * @var RuleItem $value
             */
            foreach ($this->rulesGroup($key) as $item => $value) {
                $rules[$key.'.'.$item] = $value;
                foreach ($rules[$key.'.'.$item] as $keyAttr => $valueAttr) {
                    $rules[$key.'.'.$item][$keyAttr] = str_replace('{this}.', '{this}.'.$key.'.', $valueAttr);
                }
            }
        }

        return $rules;
    }


    /**
     * @param array $rules
     *
     * @return array
     */
    protected function rulesClearRelative(array $rules): array
    {
        foreach ($rules as $keyAttr => $valueAttr) {
            $rules[$keyAttr] = str_replace('{this}.', '', $valueAttr);
        }
        return $rules;
    }

    /**
     * @throws \Exception
     */
    public function rulesCompiled()
    {
        $rules = $this->rules();
        $rules = $this->rulesClearRelative($rules);
        return $rules;
    }
}