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
     * @throws \Exception
     * @see ObjectClass::mutateAll()
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
        if($value === null && in_array('nullable',$rule->getAttributes())){
            return $value;
        }
        if ($rule->isObject()) {
            $className = $rule->getType();

            return new $className($value, false, $this->getValidationFactory());
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
     * @throws \Exception
     */
    public function cleanDiffData()
    {
        foreach ($this->dataMutated() as $target => $targetData) {
            foreach ($targetData as $targetDataKey => $targetDataValue) {
                if (!$this->rulesInfo()->get($target, $targetDataKey)) {
                    unset($this->dataMutated[$target][$targetDataKey]);
                    continue;
                }
                if ($targetDataValue instanceof ObjectClass) {
                    $this->dataMutated[$target][$targetDataKey]->cleanDiffData();
                    continue;
                }

                if (is_array($targetDataValue)) {
                    foreach ($targetDataValue as $key => $i) {
                        if ($i instanceof ObjectClass) {
                            $this->dataMutated[$target][$targetDataKey][$key]->cleanDiffData();
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $target
     *
     * @return \Illuminate\Contracts\Validation\Validator
     * @throws \Exception
     */
    public function validator(/*TODO downgrade - ?string*/ $target = null): \Illuminate\Contracts\Validation\Validator
    {
        if ($target) {
            return $this->getValidationFactory()->make($this->dataRaw[$target] ?? [], $this->rulesInfo()->rulesGroupCompiled($target));
        }

        return $this->getValidationFactory()->make($this->dataRaw ?? [], $this->rulesInfo()->rulesCompiled());
    }

    protected function hasTargetField($target, $fieldName): bool
    {
        if (!isset($this->dataMutated()[$target])) {
            return false;
        }
        if (!isset($this->dataMutated()[$target][$fieldName])) {
            return false;
        }

        return true;
    }

    protected function getTargetField($target, $fieldName)
    {
        if ($this->hasTargetField($target, $fieldName)) {
            return $this->dataMutated()[$target][$fieldName];
        }

        return null;
    }

    public final function hasParameterField($fieldName): bool
    {
        return $this->hasTargetField('parameter', $fieldName);
    }

    public final function hasCookieField($fieldName): bool
    {
        return $this->hasTargetField('cookie', $fieldName);
    }

    public final function hasHeaderField($fieldName): bool
    {
        return $this->hasTargetField('header', $fieldName);
    }

    public final function hasJsonField($fieldName): bool
    {
        return $this->hasTargetField('json', $fieldName);
    }

    public final function getParameterField($fieldName)
    {
        return $this->getTargetField('parameter', $fieldName);
    }

    public final function getCookieField($fieldName)
    {
        return $this->getTargetField('cookie', $fieldName);
    }

    public final function getHeaderField($fieldName)
    {
        return $this->getTargetField('header', $fieldName);
    }

    public final function getJsonField($fieldName)
    {
        return $this->getTargetField('json', $fieldName);
    }

}