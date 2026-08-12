# API для поддержки multiautocomplete в gtsAPI

## Обзор

Для поддержки нового типа поля `multiautocomplete` в gtsAPI были внесены изменения в метод `get_autocomplete` класса `tableAPIController`. Этот тип поля позволяет создавать автокомплиты с множественными полями поиска, размещенными в одном InputGroup.

## Изменения в API

### 1. Метод get_autocomplete

В метод `get_autocomplete` добавлена поддержка параметра `search` для обработки множественных полей поиска:

```php
// Обработка множественных полей поиска для multiautocomplete
if(!empty($request['search'])){
    foreach($request['search'] as $searchField => $searchConfig){
        if(isset($searchConfig['value']) && !empty($searchConfig['value'])){
            $where[$searchField] = $searchConfig['value'];
        }
    }
}
```

### 2. Структура запроса

Для `multiautocomplete` поля клиент может отправлять запросы со следующей структурой:

```javascript
{
    "query": "поисковый запрос",
    "search": {
        "field1": {
            "value": "значение1"
        },
        "field2": {
            "value": "значение2"
        }
    }
}
```

## Конфигурация поля multiautocomplete

### В properties таблицы gtsAPI

```json
{
    "fields": {
        "product_id": {
            "type": "multiautocomplete",
            "label": "Товар",
            "table": "products",
            "search": {
                "category_id": {
                    "label": "Категория",
                    "table": "categories",
                    "default_row": true
                },
                "brand_id": {
                    "label": "Бренд", 
                    "table": "brands",
                    "search": {
                        "category_id": {
                            "value": null
                        }
                    }
                }
            }
        }
    }
}
```

### Параметры конфигурации search

- `label` - подпись поля поиска
- `table` - таблица для поиска данных
- `default_row` - загружать ли данные по умолчанию
- `search` - вложенные зависимости между полями поиска
- `distinct` - использовать ли DISTINCT в запросе

## Логика работы

### 1. Инициализация полей поиска

При монтировании компонента:
- Загружаются данные по умолчанию для каждого поля поиска
- Устанавливаются значения по умолчанию если они указаны

### 2. Поиск в конкретном поле

При вводе в поле поиска:
- Отправляется запрос к API с параметрами поля
- Учитываются зависимости от других полей поиска
- Обновляются предложения для данного поля

### 3. Обновление зависимых полей

При выборе значения в поле поиска:
- Очищаются зависимые поля
- Загружаются новые данные для зависимых полей
- Обновляется основной список

### 4. Обновление основного списка

При изменении любого поля поиска:
- Формируется запрос с учетом всех выбранных фильтров
- Обновляется основной список автокомплита

## Примеры использования

### 1. Простой multiautocomplete

```json
{
    "product_id": {
        "type": "multiautocomplete",
        "table": "products",
        "search": {
            "category_id": {
                "label": "Категория",
                "table": "categories"
            }
        }
    }
}
```

### 2. С зависимыми полями

```json
{
    "product_id": {
        "type": "multiautocomplete", 
        "table": "products",
        "search": {
            "category_id": {
                "label": "Категория",
                "table": "categories",
                "default_row": true
            },
            "brand_id": {
                "label": "Бренд",
                "table": "brands", 
                "search": {
                    "category_id": {
                        "value": null
                    }
                }
            }
        }
    }
}
```

### 3. С множественными зависимостями

```json
{
    "product_id": {
        "type": "multiautocomplete",
        "table": "products",
        "search": {
            "category_id": {
                "label": "Категория", 
                "table": "categories"
            },
            "brand_id": {
                "label": "Бренд",
                "table": "brands",
                "search": {
                    "category_id": {
                        "value": null
                    }
                }
            },
            "supplier_id": {
                "label": "Поставщик",
                "table": "suppliers",
                "search": {
                    "category_id": {
                        "value": null
                    },
                    "brand_id": {
                        "value": null
                    }
                }
            }
        }
    }
}
```

## Совместимость

Изменения в API полностью обратно совместимы:
- Существующие `autocomplete` поля продолжают работать без изменений
- Новый параметр `search` игнорируется для обычных автокомплитов
- Не требуется изменений в существующих конфигурациях

## Требования к серверу

- gtsAPI версии с поддержкой обновленного метода `get_autocomplete`
- Таблицы для полей поиска должны быть настроены в gtsAPI
- Соответствующие права доступа к таблицам
