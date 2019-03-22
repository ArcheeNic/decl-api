<?php namespace DeclApi\Core;

use Couchbase\Exception;

class ExampleCallPoint
{
    protected $_data = [];

    /**
     * Установить пример кода
     * @param string $lang язык
     * @param string $text текст примера
     *
     * @return $this
     * @throws DeclApiCoreException
     */
    public function set(string $lang, string $text)
    {
        if ($this->get($lang)) {
            throw new DeclApiCoreException('В классе уже задан параметр '.$lang);
        }
        $this->_data[$lang] = $text;
        return $this;
    }

    /**
     * Заменить пример кода
     * @param string $lang
     * @param string $text
     *
     * @return $this
     */
    public function replace(string $lang, string $text)
    {
        $this->_data[$lang] = $text;
        return $this;
    }

    /**
     * Получить пример кода
     * @param $lang
     *
     * @return mixed|null
     */
    public function get($lang)
    {
        if (!isset($this->_data[$lang])) {
            return null;
        }
        return $this->_data[$lang];
    }

    /**
     * Получить весь массив
     * @return mixed
     */
    public function getData(){
        return $this->_data;
    }

}