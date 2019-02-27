<?php namespace DeclApi\Core;

/**
 * Класс данных запроса.
 * В отличии от ObjectClass содержит данные разделенные на json.attribute,header данные
 * Class Request
 *
 * @package DeclApi\Core
 */
abstract class Request extends ObjectClass
{

    //region Работа с исходными данными

    protected function setData($data = [])
    {
        foreach ($data as $target => $values) {
            foreach ($values as $key => $value) {
                $this->dataRaw[$target][$key] = $value;
            }
        }
    }

    protected function setDefaults()
    {
        foreach ($this->rulesInfo()->getData() as $target => $values) {
            foreach ($values as $key => $value) {
                /**
                 * @var RuleItem $value
                 */
                if (!isset($this->dataRaw[$target][$key]) && $default = $value->getDefault()) {
                    $this->dataRaw[$target][$key] = $default;
                }
            }
        }
    }

    /**
     * @see ObjectClass::mutateAll()
     * @throws \Exception
     */
    protected function mutateAll()
    {
        foreach ($this->dataRaw as $target => $values) {
            foreach ($values as $key => $value) {
                $rule = $this->rulesInfo()->get($target, $key);
                if ($rule === null) {
                    $valueMutated = $value;
                } elseif ($rule->isArray()) {
                    $valueMutated = [];
                    foreach ($value as $subkey => $subvalue) {
                        $valueMutated[$subkey] = $this->mutate($rule, $subvalue);
                    }
                } else {
                    $valueMutated = $this->mutate($rule, $value);
                }

                $this->dataMutated[$target][$key] = $valueMutated;
            }
        }
    }

    /**
     * @see ObjectClass::mutate()
     */
    protected function mutate(RuleItem $rule, $value)
    {
        if ($rule->isObject()) {
            $className = $rule->getType();
            return new $className($value);
        } elseif ($rule->getType() === 'integer') {
            return (int)$value;
        } else {
            return $value;
        }
    }
    // endregion

    //region Правила хранимых полей

    /**
     * @var RulesInfoRequest
     */
    protected $rulesInfo;

    public function rulesInfo()
    {
        if ($this->rulesInfo === null) {
            $this->rulesInfo = new RulesInfoRequest();
        }
        return $this->rulesInfo;
    }
    // endregion


    /**
     * @param $target
     *
     * @return \Illuminate\Contracts\Validation\Validator
     * @throws \Exception
     */
    public function validator(/*TODO downgrade - ?string*/ $target = null): \Illuminate\Contracts\Validation\Validator
    {
        if ($target) {
            return (new ValidatorFactory())->make($this->dataRaw[$target]??[], $this->rulesInfo()->rulesGroup($target));
        }

        return (new ValidatorFactory())->make($this->dataRaw??[], $this->rulesInfo()->rules());
    }


}