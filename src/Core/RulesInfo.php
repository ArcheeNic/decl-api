<?php namespace DeclApi\Core;

/**
 * Список правил хранимых полей
 * Class RulesInfo
 *
 * @package DeclApi\Core
 */
class RulesInfo
{
    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array|null
     * @throws \Exception
     */
    public function rules(): array
    {
        $rules = [];
        foreach ($this->data as $key => $value) {
            /**
             * @var $value RuleItem
             */
            if ($value->isArray()) {

                if (in_array('required', $value->getAttributes())) {
                    $rules[$key][] = 'required';
                }

                $key = $key.'.*';
            }
            if ($value->isObject()) {
                $className = $value->getType();
                /**
                 * @var $class ObjectClass
                 */
                $class      = new $className([], false);
                $rulesChild = $class->rulesInfo()->rules();
                foreach ($rulesChild as $childKey => $childValue) {
                    $rules[$key.'.'.$childKey] = $childValue;
                }
            } else {
                $rules[$key] = $value->getAttributes();
            }
        }

        $rules = array_filter($rules);

        return $rules;
    }

    /**
     * Создать правило
     *
     * @param      $type
     * @param      $key
     * @param      $title
     * @param null $description
     *
     * @return RuleItem
     */
    public function add($type, $key, $title, $description = null): RuleItem
    {
        $value = new RuleItem($type, $key, $title, $description);

        return $this->data[$key] = $value;
    }

    /**
     * Создать правило
     *
     * @param      $type
     * @param      $key
     * @param      $title
     * @param null $description
     *
     * @return RuleItem
     */
    public function addObject($type, $key, $title, $description = null): RuleItem
    {
        $rule = $type;

        $value = (new RuleItem($rule, $key, $title, $description))->setIsObject(true);

        if (strpos($key, '.*')) {
            $value->setIsArray(true);
        }

        return $this->data[$key] = $value;
    }

    /**
     * Получить правило
     *
     * @param $key
     *
     * @return RuleItem|null
     */
    public function get($key)/*TODO downgrade - : ?RuleItem*/
    {
        return $this->data[$key] ?? null;
    }

    /**
     * @var RuleItem[] $data
     */
    protected $data = [];

}