# Использование gtsAPIPackages - Конфигурация data_fields для группированных данных

## Описание

Данный файл содержит описание использования параметра `data_fields` в конфигурации gtsAPIPackages для работы с группированными данными.

## Параметр data_fields

### Назначение
Параметр `data_fields` определяет список полей, которые будут использоваться для идентификации записей при операциях удаления, когда стандартное поле ID не подходит.

**ВАЖНО**: `data_fields` требуется когда в SQL запросе есть группировка по полям (GROUP BY) и, следовательно, выдаваемое ID записи не относится ко всей выдаваемой строке, а представляет собой ID одной из записей в группе.

### Использование
```javascript
properties: {
    data_fields: ["field1", "field2", "field3"],  // Поля для идентификации записей
    // остальные настройки...
}
```

### Структура передаваемых данных

При удалении записей с использованием `data_fields`, фронтенд передает параметр `data_fields_values`:

#### Для одной строки:
```javascript
{
    "data_fields_values": [
        {
            "field1": "value1",
            "field2": "value2", 
            "field3": "value3"
        }
    ]
}
```

#### Для множественных строк:
```javascript
{
    "data_fields_values": [
        {
            "field1": "value1",
            "field2": "value2",
            "field3": "value3"
        },
        {
            "field1": "value4",
            "field2": "value5", 
            "field3": "value6"
        }
    ]
}
```

## Пример конфигурации с группировкой

```javascript
export default {
    mypackage: {
        name: 'mypackage',
        gtsAPITables: {
            salesReport: {
                table: 'sales',
                class: 'Sale',
                version: 1,
                authenticated: true,
                groups: 'Administrator,manager',
                active: true,
                properties: {
                    // Указываем поля для идентификации записей при удалении
                    data_fields: ["product_id", "manager_id", "date"],
                    
                    actions: {
                        read: {},
                        delete: {}
                    },
                    
                    // Запрос с группировкой
                    query: {
                        select: {
                            sales: 'product_id, manager_id, DATE(created_at) as date, SUM(amount) as total_amount, COUNT(*) as count'
                        },
                        groupby: 'product_id, manager_id, DATE(created_at)'
                    },
                    
                    fields: {
                        "product_id": {
                            "label": "Продукт",
                            "type": "autocomplete",
                            "table": "products"
                        },
                        "manager_id": {
                            "label": "Менеджер", 
                            "type": "autocomplete",
                            "table": "modUser"
                        },
                        "date": {
                            "label": "Дата",
                            "type": "date",
                            "readonly": true
                        },
                        "total_amount": {
                            "label": "Общая сумма",
                            "type": "decimal",
                            "FractionDigits": 2,
                            "readonly": true
                        },
                        "count": {
                            "label": "Количество продаж",
                            "type": "number", 
                            "readonly": true
                        }
                    }
                }
            }
        }
    }
}
```

## Логика работы в API

При наличии `data_fields` и `data_fields_values` в запросе:

1. API игнорирует параметр `ids`
2. Формирует WHERE условие на основе полей из `data_fields` и их значений из `data_fields_values`
3. Для каждой строки в `data_fields_values` создается AND условие между всеми полями
4. Если строк несколько, между ними используется OR условие

### Использование в операциях удаления

При удалении записей с `data_fields`:

```sql
DELETE FROM sales 
WHERE (product_id = 'value1' AND manager_id = 'value2' AND date = 'value3')
   OR (product_id = 'value4' AND manager_id = 'value5' AND date = 'value6')
```

### Использование в кастомных действиях

`data_fields` также может использоваться в кастомных действиях для идентификации записей:

```javascript
// Конфигурация с кастомным действием
actions: {
    read: {},
    delete: {},
    custom_action: {
        action: 'mypackage/process_grouped_data',
        row: true,
        icon: "pi pi-cog"
    }
}
```

**Пример обработки в сервисном файле:**
```php
public function process_grouped_data($data = array())
{
    // Если используются data_fields, получаем data_fields_values
    if (isset($data['data_fields_values'])) {
        $dataFieldsValues = $data['data_fields_values'];
        
        // Обрабатываем каждую группу данных
        foreach ($dataFieldsValues as $fieldValues) {
            // $fieldValues содержит значения полей для одной группированной записи
            // Например: ['product_id' => 'value1', 'manager_id' => 'value2', 'date' => 'value3']
            
            // Выполняем операции с группированными данными
            $this->processGroupedRecord($fieldValues);
        }
        
        return $this->success('Обработка завершена!', ['refresh_table' => 1]);
    }
    
    // Обычная обработка с ID
    if (isset($data['id'])) {
        // Стандартная обработка одной записи
        return $this->processRecord($data['id']);
    }
    
    return $this->error('Не переданы данные для обработки!');
}

private function processGroupedRecord($fieldValues)
{
    // Логика обработки группированной записи
    // Используем значения полей для поиска и обработки данных
    $productId = $fieldValues['product_id'];
    $managerId = $fieldValues['manager_id'];
    $date = $fieldValues['date'];
    
    // Выполняем необходимые операции...
}
```

**Структура данных в кастомном действии:**
```javascript
// Для одной группированной записи
{
    "action": "mypackage/process_grouped_data",
    "data_fields_values": [
        {
            "product_id": "value1",
            "manager_id": "value2", 
            "date": "value3"
        }
    ]
}

// Для нескольких группированных записей
{
    "action": "mypackage/process_grouped_data",
    "data_fields_values": [
        {
            "product_id": "value1",
            "manager_id": "value2",
            "date": "value3"
        },
        {
            "product_id": "value4",
            "manager_id": "value5",
            "date": "value6"
        }
    ]
}
```

## Рекомендации

1. **Включайте в `data_fields` все поля, необходимые для уникальной идентификации записи**
2. **Используйте `data_fields` только при группировке данных** - в обычных случаях достаточно стандартного ID
3. **Убедитесь, что комбинация полей в `data_fields` уникально идентифицирует записи**

## Совместимость

Функциональность `data_fields` полностью обратно совместима. Если параметры не указаны, API работает по стандартной схеме с использованием ID записей.
