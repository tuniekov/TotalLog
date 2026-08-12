# Группа полей AddFieldsForOrderProduct

## Описание

Группа полей `AddFieldsForOrderProduct` предназначена для автоматического добавления дополнительных полей в таблицу `OrderNaryads` через систему триггеров gtsAPI. Поля из этой группы автоматически интегрируются в SQL запросы и конфигурацию отображения таблицы OrderNaryads.

## Назначение

Система позволяет:
- Динамически добавлять новые поля в таблицу OrderNaryads без изменения кода
- Автоматически генерировать агрегирующие SQL формулы для расчета сумм
- Настраивать отображение полей в интерфейсе
- Контролировать позиционирование полей в таблице

## Принцип работы

1. **Поля добавляются** в группу `AddFieldsForOrderProduct` через систему gtsAPI
2. **Триггер `gtsapi_rule`** автоматически читает поля из группы при обращении к таблице OrderNaryads
3. **Модифицируется SQL запрос** - добавляются формулы агрегации
4. **Обновляется конфигурация** - добавляются поля для отображения в интерфейсе

## Структура конфигурации поля

Для работы с группой AddFieldsForOrderProduct поле должно содержать в свойствах (`properties`) конфигурацию `gtsapi_rule_config`:

```json
{
    "properties": {
        "gtsapi_rule_config": {
            "OrderNaryads": {
                "select_formula": "sum(gcNaryadLink.smena_count*gsOrderProduct.{field_name}) as sum_{field_name}",
                "field_config": {
                    "field_name_template": "sum_{field_name}",
                    "label": "Сум. {title}",
                    "type": "decimal",
                    "FractionDigits": 3,
                    "readonly": 1
                },
                "insert_after": "sum_count"
            }
        }
    }
}
```

### Параметры конфигурации

#### `select_formula`
- **Назначение**: SQL формула для агрегации данных
- **Плейсхолдеры**: `{field_name}` - заменяется на имя поля
- **Пример**: `"sum(gcNaryadLink.smena_count*gsOrderProduct.{field_name}) as sum_{field_name}"`
- **Результат**: `sum(gcNaryadLink.smena_count*gsOrderProduct.S) as sum_S`

#### `field_config`
Конфигурация поля для отображения в интерфейсе:

##### `field_name_template`
- **Назначение**: Шаблон имени поля в результирующей таблице
- **Плейсхолдеры**: `{field_name}` - заменяется на имя исходного поля
- **Пример**: `"sum_{field_name}"` → `sum_S`
- **По умолчанию**: `sum_` + имя поля

##### `label`
- **Назначение**: Отображаемое название поля в интерфейсе
- **Плейсхолдеры**: `{title}` - заменяется на title исходного поля
- **Пример**: `"Сум. {title}"` → `"Сум. S"`

##### Другие параметры
- `type`: тип поля (text, decimal, number, etc.)
- `FractionDigits`: количество знаков после запятой для decimal
- `readonly`: поле только для чтения (0/1)
- Любые другие параметры конфигурации поля gtsAPI

#### `insert_after`
- **Назначение**: Имя поля, после которого вставить новое поле
- **Пример**: `"sum_count"` - вставить после поля sum_count
- **Опционально**: если не указано, поле добавляется в конец

## Примеры использования

### Пример 1: Поле площади (S)

```json
{
    "title": "S",
    "name": "S",
    "dbtype": "decimal",
    "dbprecision": "12,3",
    "properties": {
        "gtsapi_rule_config": {
            "OrderNaryads": {
                "select_formula": "sum(gcNaryadLink.smena_count*gsOrderProduct.{field_name}) as sum_{field_name}",
                "field_config": {
                    "field_name_template": "sum_{field_name}",
                    "label": "Сум. {title}",
                    "type": "decimal",
                    "FractionDigits": 3,
                    "readonly": 1
                },
                "insert_after": "sum_count"
            }
        }
    }
}
```

**Результат**:
- SQL: `sum(gcNaryadLink.smena_count*gsOrderProduct.S) as sum_S`
- Поле: `sum_S` с лейблом "Сум. S"
- Позиция: после поля sum_count

### Пример 2: Поле веса с другим шаблоном

```json
{
    "title": "Вес",
    "name": "weight",
    "properties": {
        "gtsapi_rule_config": {
            "OrderNaryads": {
                "select_formula": "sum(gcNaryadLink.smena_count*gsOrderProduct.{field_name}) as total_{field_name}",
                "field_config": {
                    "field_name_template": "total_{field_name}",
                    "label": "Общий {title}",
                    "type": "decimal",
                    "FractionDigits": 2,
                    "readonly": 1
                },
                "insert_after": "sum_price"
            }
        }
    }
}
```

**Результат**:
- SQL: `sum(gcNaryadLink.smena_count*gsOrderProduct.weight) as total_weight`
- Поле: `total_weight` с лейблом "Общий Вес"
- Позиция: после поля sum_price

## Добавление нового поля

### Шаг 1: Создание поля
Добавить поле в таблицу gtsAPIField через конфигурацию или интерфейс:

```json
{
    "title": "Название поля",
    "name": "field_name",
    "dbtype": "decimal",
    "dbprecision": "12,3",
    "properties": {
        "gtsapi_rule_config": {
            "OrderNaryads": {
                // конфигурация...
            }
        }
    }
}
```

### Шаг 2: Добавление в группу
Создать связь в таблице gtsAPIFieldGroupLink:

```json
{
    "group_field_id": {
        "key": "name",
        "table": "gtsAPIFieldGroup",
        "name": "AddFieldsForOrderProduct"
    },
    "field_id": {
        "key": "name",
        "table": "gtsAPIField",
        "name": "field_name"
    }
}
```

### Шаг 3: Добавление в таблицу
Если поле должно быть добавлено в базу данных, создать связь в gtsAPIFieldTable:

```json
{
    "name_table": "gsOrderProduct",
    "add_base": 1,
    "add_table": 1,
    "desc": "AddFieldsForOrderProduct"
}
```

## Технические детали

### Триггер gtsapi_rule
Триггер `modifyOrderNaryadsRule` в классе `gtsCraft`:
1. Читает поля из группы AddFieldsForOrderProduct
2. Проверяет наличие конфигурации gtsapi_rule_config
3. Модифицирует SQL запрос, добавляя формулы
4. Обновляет конфигурацию полей с учетом позиционирования

### SQL модификация
Исходный запрос:
```sql
SELECT gcNaryadLink.id, gcNaryadLink.naryad_id, gcNaryadLink.smena_id,
       sum(gcNaryadLink.smena_count) as sum_count,
       sum(gcNaryadLink.smena_count*gcNaryadLink.price) as sum_price
```

После добавления поля S:
```sql
SELECT gcNaryadLink.id, gcNaryadLink.naryad_id, gcNaryadLink.smena_id,
       sum(gcNaryadLink.smena_count) as sum_count,
       sum(gcNaryadLink.smena_count*gcNaryadLink.price) as sum_price,
       sum(gcNaryadLink.smena_count*gsOrderProduct.S) as sum_S
```

### Позиционирование полей
Поля вставляются в указанную позицию в массиве fields:
```php
// Исходный порядок
['id', 'naryad_id', 'sum_count', 'sum_price', 'smena_id']

// После добавления поля с insert_after: "sum_count"
['id', 'naryad_id', 'sum_count', 'sum_S', 'sum_price', 'smena_id']
```

## Ограничения и рекомендации

### Ограничения
1. Поля должны существовать в таблице gsOrderProduct
2. Формулы должны быть совместимы с GROUP BY запросом
3. Имена полей должны быть уникальными в результирующей таблице

### Рекомендации
1. Используйте осмысленные имена полей и шаблоны
2. Указывайте правильные типы данных и точность
3. Тестируйте SQL формулы перед добавлением
4. Документируйте назначение каждого поля

## Совместимость

Система работает с:
- gtsAPI версии с поддержкой триггеров gtsapi_rule
- MODX с установленными компонентами gtsShop и gsOrder
- Таблицами gcNaryadLink, gsOrderProduct, gsOrder

## Отладка

Для отладки работы триггера:
1. Проверьте логи MODX на наличие ошибок триггера
2. Убедитесь, что поле добавлено в группу AddFieldsForOrderProduct
3. Проверьте корректность JSON конфигурации gtsapi_rule_config
4. Тестируйте SQL формулы отдельно

## Примеры ошибок

### Неправильная формула
```json
// Ошибка: поле не существует
"select_formula": "sum(gcNaryadLink.smena_count*gsOrderProduct.nonexistent_field)"

// Правильно: поле существует в gsOrderProduct
"select_formula": "sum(gcNaryadLink.smena_count*gsOrderProduct.S)"
```

### Неправильный шаблон
```json
// Ошибка: конфликт имен
"field_name_template": "sum_count" // уже существует

// Правильно: уникальное имя
"field_name_template": "sum_{field_name}"
