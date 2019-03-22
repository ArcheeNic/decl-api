<?php


namespace DeclApi\Core;


trait ValidateTrait
{
    /**
     * @var string строгость валидации. Как поступать с полями, которые не указаны в декларации
     *             strict - строго - вызывают фатальную ошибку валидации)
     *             transitional - переходной режим - поля сохранаяют ошибку, но валидация успешная
     *             soft - не проверят на избыточность полей
     */
    protected $validateStrict = 'strict';

    /**
     * @return string
     */
    protected function getValidateStrict(): string
    {
        return $this->validateStrict;
    }

    /**
     * @param string $validateStrict
     *
     * @return ObjectClass
     */
    protected function setValidateStrict(string $validateStrict): ObjectClass
    {
        $this->validateStrict = $validateStrict;
        return $this;
    }

    /**
     * Проверка на наличие лишних полей
     *
     * @return array
     * @throws DeclApiCoreException
     */
    protected function validateStrictDiffFields()
    {
        $this->recursiveValidateStrictFieldItem($this->dataRaw);
        $currentKeys  = array_keys($this->dataRaw);
        $rules        = $this->rulesInfo()->getData();
        $declaredKeys = array_keys($rules);
        return array_diff($currentKeys, $declaredKeys);
    }

    /**
     * @param $data
     *
     * @throws DeclApiCoreException
     */
    protected function recursiveValidateStrictFieldItem($data)
    {
        foreach ($data as $key => $item) {
            if (is_object($item) && $item instanceof ObjectClass) {
                $item->validateStrictRun();
            }
            if (is_array($item)) {
                $this->recursiveValidateStrictFieldItem($item);
            }
        }
    }

    /**
     * @throws DeclApiCoreException
     */
    public function validateStrictRun()
    {
        $diff = $this->validateStrictDiffFields();
        if ($diff) {
            $this->validateStrictException(get_class($this).' содержит лишние поля: '.implode(', ', $diff));
        }
    }

    /**
     * Решаеть как быть с ошибкой
     *
     * @param $error
     *
     * @throws DeclApiCoreException
     */
    protected function validateStrictException($error)
    {
        if ($this->validateStrict === 'strict') {
            throw new DeclApiCoreException($error);
        } elseif ($this->validateStrict === 'transitional') {
            // TODO: далее должно быть логирование ошибки
        }
    }
}