# Система триггеров gtsAPI

## Общее описание

Система триггеров gtsAPI предоставляет механизм для расширения функциональности API таблиц через пользовательские обработчики. Триггеры позволяют выполнять дополнительную логику на различных этапах работы с данными.

## Регистрация триггеров

Триггеры регистрируются через метод `regTriggers()` в сервисном классе компонента:

```php
public function regTriggers() {
    return [
        'ИмяТаблицы' => [
            'gtsapifunc' => 'имяМетодаТриггера',
            'gtsapi_rule' => 'имяМетодаПравил',
            'gtsapi_watch_form' => 'имяМетодаОтслеживанияФормы',
            'gtsapi_addfields' => 'имяМетодаДобавленияПолей'
        ]
    ];
}
```

## Типы триггеров

### 1. gtsapi_rule - Модификация правил таблицы

**Назначение:** Динамическая модификация конфигурации таблицы перед выполнением операций

**Вызывается:** Перед каждым API запросом к таблице

**Параметры:**
- `$params['rule']` (array) - ссылка на правила таблицы для модификации
- `$params['class']` (string) - название класса таблицы
- `$params['request']` (array) - данные запроса
- `$params['trigger']` (string) - тип триггера ('gtsapi_rule')

**Возвращает:** `['success' => 1]` или `['success' => 0, 'message' => 'ошибка']`

**Пример использования:**
```php
public function modifyTableRule($params) {
    $rule = &$params['rule'];
    $class = $params['class'];
    
    // Добавление дополнительных полей
    if ($class === 'gcNaryadLink') {
        $rule['properties']['fields']['custom_field'] = [
            'type' => 'text',
            'label' => 'Дополнительное поле'
        ];
    }
    
    return ['success' => 1, 'message' => 'Правила модифицированы'];
}
```

### 2. gtsapifunc - Основной триггер CRUD операций

**Назначение:** Выполнение дополнительной логики до и после операций создания, чтения, обновления и удаления

**Вызывается:** До и после операций create, read, update, delete

**Параметры:**
- `$params['rule']` (array) - правила таблицы
- `$params['class']` (string) - название класса таблицы
- `$params['type']` (string) - тип вызова ('before' или 'after')
- `$params['method']` (string) - метод операции ('create', 'read', 'update', 'delete')
- `$params['fields']` (array) - поля запроса
- `$params['object_old']` (array) - старые данные объекта
- `$params['object_new']` (array) - новые данные объекта (по ссылке)
- `$params['object']` (object) - объект MODX (по ссылке)
- `$params['trigger']` (string) - тип триггера ('gtsapifunc')
- `$params['internal_action']` (string) - внутреннее действие, вызвавшее метод (например, 'create' или 'update' для метода 'read')

**Возвращает:** `['success' => 1, 'data' => []]` или `['success' => 0, 'message' => 'ошибка']`

**Пример использования:**
```php
public function handleCRUDTrigger($params) {
    $type = $params['type'];
    $method = $params['method'];
    $class = $params['class'];
    
    if ($type === 'before' && $method === 'create') {
        // Логика перед созданием записи
        $params['object_new']['created_at'] = date('Y-m-d H:i:s');
    }
    
    if ($type === 'after' && $method === 'update') {
        // Логика после обновления записи
        $this->logUpdate($params['object_old'], $params['object_new']);
    }
    
    return ['success' => 1, 'data' => []];
}
```

### 3. gtsapi_watch_form - Отслеживание изменений формы

**Назначение:** Обработка изменений в форме в реальном времени

**Вызывается:** При изменении полей формы (watch_form action)

**Параметры:**
- `$params['rule']` (array) - правила таблицы
- `$params['class']` (string) - название класса таблицы
- `$params['request']` (array) - данные запроса
- `$params['fields']` (array) - поля формы
- `$params['trigger']` (string) - тип триггера ('gtsapi_watch_form')

**Возвращает:** `['success' => 1, 'data' => []]` или `['success' => 0, 'message' => 'ошибка']`

**Пример использования:**
```php
public function watchFormChanges($params) {
    $request = $params['request'];
    
    // Реагируем на изменение определенного поля
    if (isset($request['product_id'])) {
        // Обновляем связанные поля
        return [
            'success' => 1,
            'data' => [
                'updated_fields' => [
                    'price' => $this->calculatePrice($request['product_id']),
                    'category' => $this->getProductCategory($request['product_id'])
                ]
            ]
        ];
    }
    
    return ['success' => 1, 'data' => []];
}
```

### 4. gtsapi_addfields - Добавление дополнительных полей

**Назначение:** Динамическое добавление полей к таблице

**Вызывается:** При формировании списка полей для различных операций

**Параметры:**
- `$params['rule']` (array) - правила таблицы
- `$params['class']` (string) - название класса таблицы
- `$params['method']` (string) - метод операции
- `$params['fields']` (array) - поля таблицы (по ссылке)
- `$params['trigger']` (string) - тип триггера ('gtsapi_addfields')

**Возвращает:** Не требует возврата, изменяет поля по ссылке

**Пример использования:**
```php
public function addCustomFields($params) {
    $fields = &$params['fields'];
    $class = $params['class'];
    $method = $params['method'];
    
    if ($class === 'gcNaryadLink' && $method === 'options') {
        // Добавляем вычисляемое поле
        $fields['calculated_field'] = [
            'type' => 'text',
            'label' => 'Вычисляемое поле',
            'readonly' => true
        ];
    }
}
```

## Обработка ошибок

Все триггеры должны обрабатывать ошибки и возвращать соответствующие результаты:

```php
try {
    // Логика триггера
    return ['success' => 1, 'data' => []];
} catch (Exception $e) {
    $this->modx->log(1, 'Ошибка в триггере: ' . $e->getMessage());
    return ['success' => 0, 'message' => 'Ошибка выполнения триггера'];
}
```

## Событие gtsAPIRunTriggers

Дополнительно к пользовательским триггерам система поддерживает событие MODX `gtsAPIRunTriggers`:

**Параметры события:**
- `class` - класс таблицы
- `rule` - правила таблицы
- `type` - тип операции ('before'/'after')
- `method` - метод операции
- `fields` - поля запроса
- `object_old` - старые данные
- `object_new` - новые данные
- `object` - объект MODX
- `internal_action` - внутреннее действие, вызвавшее метод

**Использование в плагине:**
```php
switch ($modx->event->name) {
    case 'gtsAPIRunTriggers':
        $class = $scriptProperties['class'];
        if ($class === 'MyTable') {
            // Логика обработки
            if ($error) {
                $modx->event->output('Ошибка обработки');
            }
        }
        break;
}
```

## Практические примеры

### Пример 1: Автоматическое заполнение полей

```php
public function autoFillFields($params) {
    if ($params['type'] === 'before' && $params['method'] === 'create') {
        $params['object_new']['user_id'] = $this->modx->user->id;
        $params['object_new']['created_at'] = date('Y-m-d H:i:s');
    }
    
    return ['success' => 1];
}
```

### Пример 2: Валидация данных

```php
public function validateData($params) {
    if ($params['type'] === 'before' && in_array($params['method'], ['create', 'update'])) {
        $data = $params['object_new'];
        
        if (empty($data['required_field'])) {
            return ['success' => 0, 'message' => 'Обязательное поле не заполнено'];
        }
        
        if (!$this->isValidEmail($data['email'])) {
            return ['success' => 0, 'message' => 'Некорректный email'];
        }
    }
    
    return ['success' => 1];
}
```

### Пример 3: Логирование изменений

```php
public function logChanges($params) {
    if ($params['type'] === 'after' && $params['method'] === 'update') {
        $changes = array_diff_assoc($params['object_new'], $params['object_old']);
        
        if (!empty($changes)) {
            $this->modx->log(1, 'Изменения в ' . $params['class'] . ': ' . json_encode($changes));
        }
    }
    
    return ['success' => 1];
}
```

### Пример 4: Обработка операции чтения (read)

```php
public function handleReadOperation($params) {
    if ($params['type'] === 'after' && $params['method'] === 'read') {
        $out = $params['object_old']; // Данные результата чтения
        
        if (count($out['rows']) > 0) {
            $customFields = [];
            $row_settings = [];
            
            // Обработка каждой строки результата
            foreach ($out['rows'] as &$row) {
                // Подготовка дополнительных полей и настроек строки
                list($customField, $row_setting) = $this->prepareRowData($row);
                $customFields[$row['id']] = $customField;
                $row_settings[$row['id']] = $row_setting;
            }
            
            // Добавление дополнительных данных к результату
            $out['customFields'] = $customFields;
            $out['row_setting'] = $row_settings;
        }
        
        // Возврат модифицированного результата
        return $this->success('', ['out' => $out]);
    }
    
    return $this->success();
}

private function prepareRowData($row) {
    $customField = [];
    $row_setting = [];
    
    // Логика подготовки дополнительных полей
    if ($row['status'] === 'active') {
        $row_setting['class'] = 'success';
    } elseif ($row['status'] === 'pending') {
        $row_setting['class'] = 'warning';
    }
    
    // Добавление динамических полей
    $customField['calculated_field'] = [
        'type' => 'text',
        'readonly' => true,
        'value' => $this->calculateValue($row)
    ];
    
    return [$customField, $row_setting];
}
```

### Пример 5: Использование internal_action для различения контекста вызова

```php
public function handleReadWithContext($params) {
    if ($params['type'] === 'before' && $params['method'] === 'read') {
        $internal_action = $params['internal_action'];
        
        // Различная логика в зависимости от контекста вызова
        if ($internal_action === 'create') {
            // Логика для read, вызванного после создания записи
            // Например, можно добавить дополнительные поля для отображения
            $this->modx->log(1, 'Read вызван после создания записи');
        } elseif ($internal_action === 'update') {
            // Логика для read, вызванного после обновления записи
            // Например, можно обновить кэш или пересчитать связанные данные
            $this->modx->log(1, 'Read вызван после обновления записи');
        } else {
            // Обычный read (прямой вызов)
            $this->modx->log(1, 'Прямой вызов read');
        }
    }
    
    return $this->success();
}
```

## Особенности триггера на операцию read

Триггер на операцию `read` имеет особенности в обработке данных:

### Структура данных в read триггере

При операции `read` параметр `$params['object_old']` содержит результат выборки данных:

```php
$out = $params['object_old'];
// Структура $out:
// [
//     'rows' => [...],        // Массив строк результата
//     'total' => 100,         // Общее количество записей
//     'autocomplete' => [...], // Данные автокомплита
//     'row_setting' => [...], // Настройки строк
//     'log' => '...'          // Лог выполнения
// ]
```

### Модификация результата чтения

Триггер может модифицировать результат чтения, добавляя:

1. **customFields** - дополнительные поля для каждой строки
2. **row_setting** - настройки отображения строк (CSS классы, стили)
3. **Дополнительные данные** - любые другие данные для фронтенда

### Пример из gtsShop

В компоненте gtsShop триггер `triggergsRaschetProductgtsAPI` обрабатывает чтение таблицы `gsRaschetProduct`:

```php
if ($method == 'read') {
    $out = $params['object_old'];
    if (count($out['rows']) > 0) {
        $customFields = [];
        $row_settings = [];
        
        foreach ($out['rows'] as &$row) {
            // Подготовка дополнительных полей на основе типа продукта
            list($customField, $row_setting) = $this->prepare_raschet_row_gtsAPI($row);
            $customFields[$row['id']] = $customField;
            $row_settings[$row['id']] = $row_setting;
        }
        
        $out['customFields'] = $customFields;
        $out['row_setting'] = $row_settings;
    }
    
    return $this->success('', ['out' => $out]);
}
```

### Возврат данных из read триггера

Для модификации результата чтения триггер должен вернуть:

```php
return $this->success('', ['out' => $modifiedOut]);
```

Где `$modifiedOut` - модифицированная структура данных результата.

### Параметр internal_action в read триггере

Параметр `internal_action` позволяет определить контекст вызова метода `read`:

- **Пустая строка** - прямой вызов read (обычная загрузка данных)
- **'create'** - read вызван после создания записи (для получения свежих данных созданной записи)
- **'update'** - read вызван после обновления записи (для получения обновленных данных)

Это позволяет применять разную логику обработки в зависимости от контекста:

```php
public function handleReadOperation($params) {
    if ($params['type'] === 'after' && $params['method'] === 'read') {
        $internal_action = $params['internal_action'];
        $out = $params['object_old'];
        
        // Применяем разную логику в зависимости от контекста
        if ($internal_action === 'create' || $internal_action === 'update') {
            // Для операций создания/обновления добавляем дополнительные данные
            $out['notification'] = [
                'type' => 'success',
                'message' => 'Запись успешно ' . ($internal_action === 'create' ? 'создана' : 'обновлена')
            ];
        }
        
        return $this->success('', ['out' => $out]);
    }
    
    return $this->success();
}
```

## Рекомендации по использованию

1. **Производительность** - избегайте тяжелых операций в триггерах, особенно в 'before' триггерах
2. **Обработка ошибок** - всегда обрабатывайте исключения и возвращайте корректные результаты
3. **Логирование** - используйте логирование для отладки и мониторинга
4. **Тестирование** - тщательно тестируйте триггеры на различных сценариях
5. **Документирование** - документируйте логику триггеров для других разработчиков
6. **Read триггеры** - используйте read триггеры для добавления динамических полей и настроек отображения

## Отладка триггеров

Для отладки триггеров используйте:

```php
$this->modx->log(1, 'Триггер ' . $params['trigger'] . ' для ' . $params['class']);
$this->modx->log(1, 'Параметры: ' . print_r($params, true));
```

Логи можно просматривать в менеджере MODX в разделе "Система" → "Журналы ошибок".
