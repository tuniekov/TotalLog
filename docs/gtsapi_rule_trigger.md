# Триггер gtsapi_rule

## Описание

Триггер `gtsapi_rule` позволяет модифицировать правила таблицы (`$rule`) перед их использованием в API. Этот триггер вызывается в методе `route_post` после загрузки пакетов и перед обработкой действий.

## Назначение

Триггер предназначен для динамического изменения конфигурации таблиц в зависимости от контекста запроса. Это позволяет:

- Изменять свойства таблицы на лету
- Модифицировать поля, действия, запросы
- Добавлять условную логику в зависимости от пользователя или данных запроса
- Переопределять настройки безопасности

## Момент вызова

Триггер вызывается в методе `route_post` класса `tableAPIController` после:
1. Проверки базовых разрешений
2. Декодирования свойств таблицы
3. Загрузки пакетов и моделей

И перед:
1. Проверкой разрешений на действия
2. Обработкой конкретных API действий

## Параметры триггера

Триггер получает следующие параметры:

```php
$params = [
    'rule' => &$rule,        // Ссылка на массив правил таблицы (можно изменять)
    'class' => $class,       // Имя класса таблицы
    'request' => $request,   // Данные запроса
    'trigger' => 'gtsapi_rule' // Тип триггера
];
```

### Описание параметров

- **`rule`** - массив с конфигурацией таблицы, передается по ссылке, что позволяет его изменять
- **`class`** - строка с именем класса MODX для таблицы
- **`request`** - массив с данными HTTP запроса
- **`trigger`** - строка с типом триггера (всегда 'gtsapi_rule')

## Структура rule

Массив `$rule` содержит:

```php
$rule = [
    'id' => 123,                    // ID таблицы в gtsAPITable
    'table' => 'table_name',        // Название таблицы
    'class' => 'ClassName',         // Класс MODX
    'package_id' => 1,              // ID пакета
    'properties' => [               // Свойства таблицы
        'fields' => [...],          // Конфигурация полей
        'actions' => [...],         // Доступные действия
        'query' => [...],           // Настройки запросов
        'filters' => [...],         // Фильтры
        // другие свойства...
    ]
];
```

## Регистрация триггера

Для использования триггера в сервисном файле пакета:

### 1. Добавить в метод regTriggers()

```php
public function regTriggers()
{
    return [
        'ClassName' => [
            'gtsapi_rule' => 'modifyTableRule'
        ]
    ];
}
```

### 2. Реализовать метод обработки

```php
public function modifyTableRule($params)
{
    $rule = &$params['rule'];
    $class = $params['class'];
    $request = $params['request'];
    
    // Логика модификации правил
    // Например, изменение полей в зависимости от пользователя
    if ($this->modx->user->id > 0) {
        $rule['properties']['fields']['user_field'] = [
            'label' => 'Пользовательское поле',
            'type' => 'text',
            'default' => $this->modx->user->id
        ];
    }
    
    return $this->success('Правила модифицированы');
}
```

## Примеры использования

### Пример 1: Условные поля

```php
public function modifyTableRule($params)
{
    $rule = &$params['rule'];
    $request = $params['request'];
    
    // Добавляем поле только для администраторов
    if ($this->modx->user->isMember('Administrator')) {
        $rule['properties']['fields']['admin_notes'] = [
            'label' => 'Заметки администратора',
            'type' => 'textarea',
            'modal_only' => true
        ];
    }
    
    return $this->success();
}
```

### Пример 2: Динамические запросы

```php
public function modifyTableRule($params)
{
    $rule = &$params['rule'];
    $request = $params['request'];
    
    // Изменяем запрос в зависимости от параметров
    if (isset($request['department_id'])) {
        $rule['properties']['query']['where']['department_id'] = $request['department_id'];
    }
    
    return $this->success();
}
```

### Пример 3: Условные действия

```php
public function modifyTableRule($params)
{
    $rule = &$params['rule'];
    $class = $params['class'];
    
    // Убираем действие удаления для определенных ролей
    if (!$this->modx->user->isMember('Administrator')) {
        unset($rule['properties']['actions']['delete']);
    }
    
    // Добавляем кастомное действие
    $rule['properties']['actions']['export'] = [
        'action' => 'mypackage/export_data',
        'icon' => 'pi pi-download',
        'groups' => 'Manager'
    ];
    
    return $this->success();
}
```

### Пример 4: Модификация автокомплита

```php
public function modifyTableRule($params)
{
    $rule = &$params['rule'];
    $request = $params['request'];
    
    // Изменяем условия автокомплита в зависимости от контекста
    if (isset($request['context']) && $request['context'] === 'limited') {
        $rule['properties']['autocomplete']['where']['active'] = 1;
        $rule['properties']['autocomplete']['limit'] = 10;
    }
    
    return $this->success();
}
```

## Возвращаемые значения

Метод триггера должен возвращать результат в стандартном формате:

```php
// Успешное выполнение
return $this->success('Сообщение об успехе');

// Ошибка (прерывает выполнение API запроса)
return $this->error('Сообщение об ошибке');
```

## Обработка ошибок

Если триггер возвращает ошибку (`success = false`), выполнение API запроса прерывается и ошибка возвращается клиенту.

Если в триггере возникает исключение, оно логируется и возвращается ошибка:

```php
try {
    // вызов триггера
} catch (Error $e) {
    $this->modx->log(1, 'gtsAPI Ошибка триггера gtsapi_rule ' . $e->getMessage());
    return $this->error('Ошибка триггера gtsapi_rule ' . $e->getMessage());
}
```

## Рекомендации

1. **Производительность**: Избегайте тяжелых операций в триггере, так как он вызывается при каждом API запросе
2. **Безопасность**: Тщательно проверяйте входные данные перед модификацией правил
3. **Логирование**: Используйте логирование для отладки сложной логики
4. **Тестирование**: Тестируйте триггер с различными типами запросов и пользователей

## Совместимость

Триггер `gtsapi_rule` доступен начиная с версии gtsAPI, где была добавлена поддержка модификации правил через триггеры.
