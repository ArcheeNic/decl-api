# Декларативный API

## Оглавление
* [О библиотке](#о-библиотке)
    * [Установка](#установка)
    * [Описание](#описание)
    * [Термины и определения](#термины-и-определения)
    * [Приципы и архитектура](#приципы-и-архитектура)
* [Создаём поинт](#создаем-поинт)
    * [Создание класса Request](#создание-класса-request)
    * [Создание класса Response](#создание-класса-response)
    * [Создание поинта](#создание-поинта)
    * [Интеграция с laravel 5.4+](#интеграция-с-laravel-54)
* Примеры использования
    * [Использование поинтов](./tests/Unit/DeclApi/LogicTest.php)
    * [Использование поинтов в laravel 5](./tests/Unit/DeclApi/LogicTest.php)
    * [Создание документации](./tests/Unit/DeclApiDoc/MakeDocTest.php)
    * [Создание документации на Laravel 5](./tests/Unit/DeclApiDoc/CompleteDocL5Test.php)

## О библиотке
### Установка
[к оглавлению](#оглавление)

В composer проекта добавить строки
```
    "repositories": [
        {
            "type": "vcs",
            "url":  "gogs@gogs.inetpartners.org:eautopay/decl-api.git"
        }
    ]
```

Далее в консоли можно написать
`composer require eautopay/decl-api`

### Описание
[к оглавлению](#оглавление)

DeclApi - библиотека декларативного API

Это значит, что разработчик сначала описывает правила, по которым
получается информация и отдается, а потом уже пишется сама логика

Библиотека реализована с максимальной изоляцией от любых фреймворков.

За счет того, что реализуется декларативная логика, есть возможность
сгенерировать документацию и провести автоматический тест на
работоспособность API.

За счет изоляции от фреймворков - переезд будет менее болезненен,
а родительская архитектура будет той же

За счет того чтоб библиотека

### Термины и определения
[к оглавлению](#оглавление)


EndPoint - URI с собственной логикой.

Класс EndPoint - класс с логикой эндпоинта

Объект API - класс набора данных в рамках одного уровня

Класс Request - объект API с информацией о входящих данных

### Приципы и архитектура
[к оглавлению](#оглавление)

`...`

## Создаём поинт

### Создание класса Request
[к оглавлению](#оглавление)


> Отличие Request от обычного Объекта, то, что у него в качестве входящих
  данных идут массивы групп полей: json, parameter (get,post), header

В папке App/Api создадим файл для работы с входящими данными ExampleRequest

```php
    <?php namespace App\Api;

    use DeclApi\Core\Request;

    class ExampleRequest extends Request
    {
    }
    ?>
```

Добавим правило для get/post передавемого параметра

```php
    /**
     * @throws \Exception
     */
    protected function initRules()
    {
        parent::initRules();
        $this->rulesInfo()->add('parameter','integer','example','Пример поля','Пример описания поля')->setDefault(10)->setAttributes('required');
    }
```

### Создание класса Response
[к оглавлению](#оглавление)


> Класс response - обычныый объект - ObjectClass, для порядка все-таки
рекомендуется называть его с окончанием Response, чтобы было понятно назначение объекта

В папке App/Api создадим файл для работы с входящими данными ExampleRequest

```php
<?php namespace App\Api;

use DeclApi\Core\ObjectClass;

class ExampleResponse extends ObjectClass
{
}
```

Добавим правило для хранимого параметра

```php
    /**
     * @throws \Exception
     */
    protected function initRules()
    {
        parent::initRules();
        $this->rulesInfo()->add('integer','example','Пример параметра','Описание параметра');
    }
 ```


### Создание поинта
[к оглавлению](#оглавление)


> Будем рассматривать на примере laravel 5

В папке app/Http создадим подпапку Api
В папке app/Http создадим подпапку Api
> Название и расположение папки и файлов не имеет значения и ограничена
только архитектурой и настройками фреймворка.
>
> В Laravel 5 api.php использует в качестве основной папки для поиска
целей роутов `app/Http`

Внутри папки создадим класс Endpoint ExamplePoint.php со следующим содержимым:
```php
<?php namespace App\Http\Api;

use DeclApi\Core\PointL5Bridge;

class ExamplePoint extends Laravel5Point
{

}
```

> Обратите внимание на родительский класс PointL5Bridge - это мост для
интеграции библиотеки в Laravel 5. Он является дочерним классом Point.
>
> Если реализация будет требоваться в другом фреймворке - можно написать
аналогичный мост и спользовать его. Так же можно использовать чистый  Point

Теперь в созданный класс добавим метод
```php
    /**
     * @param ExampleRequest $request
     *
     * @return ExampleResponse
     * @throws \Exception
     */
    public function handler(ExampleRequest $request): ExampleResponse
    {
        $data = new ExampleResponse();
        return $data;
    }
```

Над классом добавим используемые неймспейсы
```
use App\Api\ExampleRequest;
use App\Api\ExampleResponse;
```

### Интеграция с laravel 5.4+
[к оглавлению](#оглавление)


> Так как в качестве родителя мы использовали мост для laravel 5, нам не
нужно писать какие либо особые правила или нстраивать связь для работы библиотеки

Добавим в api.php строку
```
Route::get('/example',Api/'ExamplePoint');
```

Теперь наш Point будет вызываться при обращении по URI `/api/example`