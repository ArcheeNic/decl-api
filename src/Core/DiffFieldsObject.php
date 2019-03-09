<?php


namespace DeclApi\Core;


class DiffFieldsObject
{
    /**
     * Строгая валидация должна вызывать критическую ошибку при проверке.
     * Строгая валидация вариативна
     * Есть 3 варианта: убрать лишнее, записать в лог, выдать исключение
     * Также есть 2 кейса: стандартный объект и request объект
     * Необходимо как-то регулироавть эти механики.
     * Для того чтобы было гибко - нужно принимать всё входящими переменными. Ни в коем случае не использовать
     * переменные класса.
     */

    /**
     * @param ObjectClass $object
     *
     * @return array
     */
    public static function diff(ObjectClass $object): array
    {
        $data   = $object->dataMutated();
        $rules  = $object->rulesInfo();
        $return = [];

        foreach ($data as $key => $item) {
            $rule = $rules->get($key);
            if (!$rule) {
                $return[$key] = 'Поле '.$key.' не указано в правилах';
            } elseif ($item instanceof ObjectClass && $rule->isObject()) {
                $errors = static::subDiffObject($key, $item);
                foreach ($errors as $k => $v) {
                    $return[$k] = $v;
                }
            } elseif (is_array($item)) {
                $errors = static::subDiffArray($key, $item);
                foreach ($errors as $k => $v) {
                    $return[$k] = $v;
                }
            }
        }

        return $return;
    }

    /**
     * @param Request $object
     *
     * @return array
     * @throws \Exception
     */
    public static function diffRequest(Request $object): array
    {
        $data   = $object->dataMutated();
        $rules  = $object->rulesInfo();
        $return = [];

        $targets = array_keys($object->rulesInfo()->getData());

        foreach ($targets as $target){
            if($target === 'header'){
                continue;
            }
            if(!isset($data[$target])){
                continue;
            }
            foreach ($data[$target] as $key => $item) {
                $rule = $rules->get($target,$key);
                if (!$rule) {
                    $return[strtoupper($target).'.'.$key] = 'Поле '.$key.' не указано в правилах';
                } elseif ($item instanceof ObjectClass && $rule->isObject()) {
                    $errors = static::subDiffObject($key, $item);
                    foreach ($errors as $k => $v) {
                        $return[$k] = $v;
                    }
                } elseif (is_array($item)) {
                    $errors = static::subDiffArray($key, $item);
                    foreach ($errors as $k => $v) {
                        $return[$k] = $v;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Работаем с подобъектом
     *
     * @param           $key
     * @param \stdClass $item
     *
     * @return array|null
     */
    protected static function subDiffObject($key, ObjectClass $item): array
    {
        $return = [];
        $errors = static::diff($item);
        foreach ($errors as $k => $v) {
            $return[$key.'.'.$k] = $v;
        }
        return $return;
    }

    /**
     * Работаем с массивом подобъектов
     *
     * @param       $key
     * @param array $item
     *
     * @return array
     */
    protected static function subDiffArray($key, array $item): array
    {
        $return = [];
        foreach ($item as $itemKey => $itemValue) {
            if(!($itemValue instanceof ObjectClass)){
                continue;
            }

            $errors = static::subDiffObject($itemKey, $itemValue);
            foreach ($errors as $errorKey => $error) {
                $return[$key.'.'.$errorKey] = $error;
            }
        }
        return $return;
    }
}