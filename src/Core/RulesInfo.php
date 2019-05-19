<?php namespace DeclApi\Core;

use Illuminate\Validation\Rule;

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
                    foreach ($rules[$key.'.'.$childKey] as $keyAttr => $valueAttr) {
                        $rules[$key.'.'.$childKey][$keyAttr] = str_replace('{this}.', $key.'.{this}.', $valueAttr);
                    }
                }
            } else {
                $rules[$key] = $value->getAttributes();
            }

            if ($value->getType() === 'in_string') {
                $rules[$key][] = 'in_string:'.implode(',', $value->getEnum());
                if (array_search('in_string', $rules[$key]) !== false) {
                    unset($rules[$key][array_search('in_string', $rules[$key])]);
                }
            } elseif ($value->getEnum()) {
                $rules[$key][] = 'in:'.implode(',', $value->getEnum());
            }
        }

        $rules = array_filter($rules);

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
    public function rulesCompiled(){
        $rules = $this->rules();
        $rules = $this->rulesClearRelative($rules);
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