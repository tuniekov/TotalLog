# Использование gtsAPIPackages - Общее руководство

## Описание

Данный файл содержит общее описание использования конфигурации gtsAPIPackages для создания API таблиц в системе gtsAPI.

## Структура конфигурации

```javascript
export default {
    packagename: {
        name: 'packagename', // имя пакета MODX
        gtsAPITables: {
            tablename: {
                table: 'tablename', // Название таблицы
                class: 'ClassName', // Класс MODX таблицы базы данных (если отличается от table)
                autocomplete_field: 'field_name', // Поле для автокомплита
                version: 1, // Версия конфигурации
                type: 1, // Тип таблицы: 1 - PVTables, 2 - JSON, 3 - UniTree
                authenticated: true, // Требуется аутентификация
                groups: 'Administrator,manager', // Группы пользователей с доступом
                permissions: '', // Разрешения MODX
                active: true, // Активность таблицы
                properties: {
                    // Конфигурация свойств таблицы
                }
            }
        }
    }
}
```

## Основные параметры таблицы

### Обязательные параметры
- `table` - название таблицы в базе данных
- `version` - версия конфигурации (увеличивается при изменениях)
- `type` - тип таблицы (1, 2 или 3)
- `active` - активность таблицы

### Опциональные параметры
- `class` - класс MODX (если отличается от названия таблицы)
- `autocomplete_field` - поле для автокомплита
- `authenticated` - требование аутентификации
- `groups` - группы пользователей с доступом
- `permissions` - разрешения MODX

## Типы таблиц

1. **type: 1** - Обычные таблицы PVTables
2. **type: 2** - JSON таблицы (работа с JSON полями в базе данных)
3. **type: 3** - Деревья UniTree и меню PVMenu

## JSON таблицы (type: 2)

**JSON таблицы** позволяют работать с данными, хранящимися в JSON полях базы данных. Это полезно когда:
- Данные имеют динамическую структуру
- Нужно хранить вложенные массивы объектов
- Требуется гибкость в изменении структуры данных без миграций БД
- Данные логически связаны с основной записью

### Основная конфигурация JSON таблицы

```javascript
nfReiki: {
    table: 'nfReiki',
    class: 'nfFlanecData',           // Класс MODX объекта
    autocomplete_field: '',
    version: 1,
    type: 2,                         // Обязательно для JSON таблиц
    authenticated: true,
    groups: 'Administrator',
    permissions: '',
    active: true,
    properties: {
        hide_id: 1,                  // Скрыть поле ID
        actions: {
            read: {},                // Доступные действия
        },
        json_path: {                 // Конфигурация пути к JSON данным
            where: {
                naryad_id: 'naryad_id'  // Условия для поиска записи
            },
            field: 'json_data',      // Поле с JSON данными
            key: 'reiki.{shina}'     // Путь внутри JSON с динамическими значениями
        },
        fields: {
            // Поля таблицы
        }
    }
}
```

### Параметр json_path

Параметр `json_path` определяет как система находит и работает с JSON данными:

**Обязательные параметры:**
- `where` - условия для поиска записи в базе данных
- `field` - название поля в таблице, содержащего JSON данные
- `key` - путь к данным внутри JSON (опционально)

#### where - Условия поиска записи

Определяет по каким полям искать запись в базе данных. Значения берутся из фильтров таблицы:

```javascript
json_path: {
    where: {
        naryad_id: 'naryad_id'  // Поле БД: имя фильтра
    }
}
```

**Как это работает:**
1. Пользователь выбирает значение в фильтре `naryad_id`
2. Система ищет запись где `naryad_id = значение_из_фильтра`
3. Из найденной записи берется поле с JSON данными

**Множественные условия:**
```javascript
json_path: {
    where: {
        naryad_id: 'naryad_id',
        smena_id: 'smena_id'
    }
}
```

#### field - Поле с JSON данными

Название поля в таблице базы данных, которое содержит JSON:

```javascript
json_path: {
    field: 'json_data'  // Поле типа TEXT/JSON в БД
}
```

**Структура данных в БД:**
```sql
CREATE TABLE nfFlanecData (
    id INT PRIMARY KEY,
    naryad_id INT,
    json_data TEXT  -- Это поле содержит JSON
);
```

#### key - Путь внутри JSON

**Новая возможность (v2024.1):** Поддержка динамических значений в пути через плейсхолдеры `{field_name}`.

Определяет путь к нужным данным внутри JSON структуры:

```javascript
json_path: {
    key: 'reiki.{shina}'  // Путь с динамическим значением
}
```

**Статический путь:**
```javascript
// JSON в БД:
{
    "reiki": {
        "shina1": [
            {"id": 1, "length": 100, "count": 5},
            {"id": 2, "length": 150, "count": 3}
        ]
    }
}

// Конфигурация:
json_path: {
    key: 'reiki.shina1'  // Фиксированный путь
}
```

**Динамический путь с плейсхолдерами:**
```javascript
// JSON в БД:
{
    "reiki": {
        "shina1": [
            {"id": 1, "length": 100, "count": 5}
        ],
        "shina2": [
            {"id": 1, "length": 200, "count": 8}
        ],
        "shina3": [
            {"id": 1, "length": 150, "count": 6}
        ]
    }
}

// Конфигурация:
json_path: {
    key: 'reiki.{shina}'  // {shina} заменяется на значение из фильтра
}

// Если пользователь выбрал в фильтре shina = "shina2",
// система получит данные из reiki.shina2
```

**Множественные плейсхолдеры:**
```javascript
json_path: {
    key: 'data.{category}.items.{type}'
}

// При category="electronics" и type="phones"
// Путь станет: data.electronics.items.phones
```

**Вложенность:**
Поддерживается до 4 уровней вложенности:
```javascript
// Уровень 1
key: 'items'

// Уровень 2
key: 'data.items'

// Уровень 3
key: 'data.category.items'

// Уровень 4
key: 'data.category.subcategory.items'
```

### Полный пример конфигурации

```javascript
export default {
    mypackage: {
        name: 'mypackage',
        gtsAPITables: {
            nfReiki: {
                table: 'nfReiki',
                class: 'nfFlanecData',
                autocomplete_field: '',
                version: 1,
                type: 2,
                authenticated: true,
                groups: 'Administrator',
                permissions: '',
                active: true,
                properties: {
                    hide_id: 1,
                    actions: {
                        read: {},
                        create: { groups: 'Administrator' },
                        update: { groups: 'Administrator' },
                        delete: { groups: 'Administrator' }
                    },
                    json_path: {
                        where: {
                            naryad_id: 'naryad_id'
                        },
                        field: 'json_data',
                        key: 'reiki.{shina}'
                    },
                    fields: {
                        "id": {
                            "type": "view"
                        },
                        "shina": {
                            "label": "Шина",
                            "type": "text",
                            "readonly": true
                        },
                        "length": {
                            "label": "Длина",
                            "type": "number"
                        },
                        "count": {
                            "label": "Кол-во",
                            "type": "number"
                        }
                    }
                }
            }
        }
    }
}
```

### Структура JSON данных

**Пример данных в поле json_data:**
```json
{
    "reiki": {
        "shina1": [
            {
                "id": 1,
                "shina": "shina1",
                "length": 100,
                "count": 5
            },
            {
                "id": 2,
                "shina": "shina1",
                "length": 150,
                "count": 3
            }
        ],
        "shina2": [
            {
                "id": 1,
                "shina": "shina2",
                "length": 200,
                "count": 8
            }
        ]
    }
}
```

### Работа с JSON таблицами

**Чтение данных:**
1. Пользователь выбирает фильтры (например, `naryad_id=123`, `shina=shina1`)
2. Система находит запись в БД по условиям `where`
3. Извлекает JSON из поля `field`
4. Переходит по пути `key` с подстановкой значений из фильтров
5. Отображает массив объектов как строки таблицы

**Создание записи:**
1. Пользователь создает новую запись
2. Система генерирует новый ID (максимальный + 1)
3. Добавляет объект в массив по указанному пути
4. Сохраняет обновленный JSON в БД

**Обновление записи:**
1. Пользователь редактирует запись
2. Система находит объект по ID в массиве
3. Обновляет значения полей
4. Сохраняет обновленный JSON в БД

**Удаление записи:**
1. Пользователь удаляет запись
2. Система находит объект по ID в массиве
3. Удаляет объект из массива
4. Сохраняет обновленный JSON в БД

### Особенности JSON таблиц

**Преимущества:**
- Гибкая структура данных без миграций БД
- Хранение связанных данных в одной записи
- Динамическая вложенность
- Быстрое прототипирование

**Ограничения:**
- Максимум 4 уровня вложенности в `key`
- ID генерируются автоматически (максимальный + 1)
- Нет прямых SQL запросов к данным внутри JSON
- Требуется наличие фильтров для определения записи

**Рекомендации:**
- Используйте `hide_id: 1` если ID не имеет смысла для пользователя
- Добавляйте `readonly: true` для полей, определяющих путь (например, `shina`)
- Всегда указывайте необходимые фильтры в интерфейсе
- Документируйте структуру JSON для разработчиков

### Примеры использования

**Хранение настроек по категориям:**
```javascript
json_path: {
    where: { user_id: 'user_id' },
    field: 'settings',
    key: 'categories.{category_id}.options'
}
```

**Данные по периодам:**
```javascript
json_path: {
    where: { report_id: 'report_id' },
    field: 'data',
    key: 'periods.{year}.{month}'
}
```

**Вложенные справочники:**
```javascript
json_path: {
    where: { document_id: 'document_id' },
    field: 'content',
    key: 'sections.{section}.items'
}
```

### select_from_json — Select-опции из соседнего JSON-ключа

В JSON таблицах (type: 2) часто бывает ситуация, когда данные для выпадающего списка находятся в соседнем ключе того же JSON объекта. Например, JSON содержит массив `dop_mats` (доп. материалы) и массив `naryads` (наряды), и при редактировании материала нужно выбрать наряд только из тех, что есть в этом JSON.

Параметр `select_from_json` в определении поля позволяет автоматически формировать опции select из соседнего ключа JSON.

#### Параметры поля

| Параметр | Тип | Описание |
|----------|-----|----------|
| `select_from_json` | string | Имя соседнего ключа в JSON, из которого брать опции |
| `select_value_field` | string | Поле в элементах соседнего массива, содержащее значение (по умолчанию `id`) |
| `select_label_field` | string | Поле для отображаемого текста (по умолчанию `name`) |
| `select_label_class` | string | xPDO класс для получения label из БД вместо JSON (опционально) |

#### Принцип работы

1. При `read()` JSON таблицы бэкенд находит поля с `select_from_json`
2. Из соседнего ключа JSON извлекаются уникальные значения по `select_value_field`
3. Формируются опции `[{id, content}, ...]`:
   - Если указан `select_label_class` — label берётся из БД (по `select_label_field`)
   - Иначе — label берётся из JSON (по `select_label_field`)
4. Опции передаются фронтенду в `response.data.selects`
5. PVTables автоматически применяет их к соответствующему select-полю

#### Пример: доп. материалы с нарядами

**Структура JSON в БД:**
```json
{
    "naryads": [
        {"naryad_id": 5, "fullname": "Деталь 1"},
        {"naryad_id": 8, "fullname": "Деталь 2"}
    ],
    "dop_mats": [
        {"id": 1, "naryad_id": 5, "material_id": 100, "cnt": 2},
        {"id": 2, "naryad_id": 8, "material_id": 101, "cnt": 1}
    ]
}
```

**Конфигурация таблицы:**
```javascript
dopProductMaterial: {
    table: 'dopProductMaterial',
    class: 'gsRaschetProductDop',
    type: 2,
    properties: {
        json_path: {
            where: { raschet_product_id: 'raschet_product_id' },
            field: 'json',
            key: 'dop_mats'
        },
        fields: {
            naryad_id: {
                label: 'Наряд списания',
                type: 'select',
                select_from_json: 'naryads',       // Брать опции из ключа "naryads"
                select_value_field: 'naryad_id',    // Значение — поле naryad_id
                select_label_class: 'gsNaryad',     // Label из таблицы gsNaryad
                select_label_field: 'name'          // Поле name в gsNaryad
            },
            material_id: {
                label: 'Материал',
                type: 'autocomplete',
                table: 'gsMaterial'
            },
            cnt: {
                label: 'Количество',
                type: 'decimal'
            }
        }
    }
}
```

**Результат:** при открытии таблицы `dopProductMaterial` поле `naryad_id` отобразит выпадающий список с двумя нарядами (id 5 и 8), а их названия будут взяты из таблицы `gsNaryad`.

#### Пример: label из JSON (без обращения к БД)

Если `select_label_class` не указан, label берётся прямо из элементов JSON:

```javascript
fields: {
    category_id: {
        label: 'Категория',
        type: 'select',
        select_from_json: 'categories',    // Соседний ключ
        select_value_field: 'id',          // По умолчанию 'id'
        select_label_field: 'title'        // Поле для отображения
    }
}
```

При JSON `{"categories": [{"id": 1, "title": "Основные"}, {"id": 2, "title": "Доп."}]}` — select покажет «Основные» и «Доп.».

## Конфигурация properties

### Form (Конфигурация формы)

**Новая возможность (v2024.1):** Параметр `form` позволяет настраивать отображение формы редактирования записи с поддержкой табов и кастомных шаблонов.

```javascript
properties: {
    form: {
        // Опциональная настройка ширины полей
        fieldWidth: 'full', // 'full' = 100%, или любое CSS значение: '30rem', '400px', 'calc(100% - 200px)'
        
        // Вариант 1: Кастомный Vue шаблон
        template: `
            <div class="custom-form">
                <EditField :field="columns2[0]" v-model="model[columns2[0].field]" />
                <EditField :field="columns2[1]" v-model="model[columns2[1].field]" />
            </div>
        `,
        
        // Вариант 2: Организация полей в табы
        tabs: {
            main: {
                label: 'Основная информация',
                fields: 'id,name,email'  // Поля по именам
            },
            details: {
                label: 'Детали',
                fields: '0-3,phone'      // Поля по индексам и именам
            },
            settings: {
                label: 'Настройки',
                fields: 'active,created_at'
            }
        }
    }
}
```

#### Режим template

Позволяет создать полностью кастомный Vue-шаблон для формы редактирования.

**Доступные переменные в шаблоне:**
- `EditField` - компонент для отображения полей
- `model` - объект с данными записи
- `columns2` - массив конфигураций полей
- `inline` - флаг компактного отображения
- `autocompleteSettings` - настройки автокомплитов
- `selectSettings2` - настройки селектов
- `setValue` - метод для сохранения изменений

**Пример кастомного шаблона:**
```javascript
form: {
    template: `
        <div class="custom-form-layout">
            <div class="form-section">
                <h3>Основные данные</h3>
                <div class="form-row">
                    <EditField 
                        :field="columns2.find(f => f.field === 'name')" 
                        v-model="model.name" 
                        :data="model"
                        :use_data="true"
                        @set-value="setValue()"
                    />
                </div>
                <div class="form-row">
                    <EditField 
                        :field="columns2.find(f => f.field === 'email')" 
                        v-model="model.email"
                        :data="model"
                        :use_data="true"
                        @set-value="setValue()"
                    />
                </div>
            </div>
            
            <div class="form-section">
                <h3>Дополнительно</h3>
                <div class="form-row">
                    <EditField 
                        :field="columns2.find(f => f.field === 'phone')" 
                        v-model="model.phone"
                        :data="model"
                        :use_data="true"
                        @set-value="setValue()"
                    />
                </div>
            </div>
        </div>
    `
}
```

#### Режим tabs

Организует поля формы в табы для удобной навигации по большим формам.

**Формат конфигурации:**
```javascript
form: {
    tabs: {
        tab_key: {
            label: 'Название таба',
            fields: 'field1,field2,0-3,field5'
        }
    }
}
```

**Параметры таба:**
- `label` (обязательно) - название таба, отображаемое в интерфейсе
- `fields` (обязательно) - список полей для отображения в табе

**Форматы указания полей:**

1. **По имени поля:** `'id,name,email'`
   - Выводит поля с указанными именами
   - Порядок соответствует порядку в строке

2. **По индексу:** `'0,2,5'`
   - Выводит поля по их индексам в массиве `columns2`
   - Индексация начинается с 0

3. **Диапазон индексов:** `'0-3'`
   - Выводит поля с индексами от 0 до 3 включительно
   - Эквивалентно `'0,1,2,3'`

4. **Комбинированный формат:** `'id,0-2,name,5,email'`
   - Можно комбинировать все форматы
   - Порядок полей соответствует порядку в строке

**Автоматический таб "Дополнительно":**
- Создается автоматически, если есть поля не указанные ни в одном табе
- Содержит все поля из `columns2` где `table_only != true` и `type != 'hidden'`
- Не отображается, если все поля распределены по табам

**Примеры использования:**

```javascript
// Простая организация в 2 таба
form: {
    tabs: {
        main: {
            label: 'Основное',
            fields: 'id,name,email,phone'
        },
        settings: {
            label: 'Настройки',
            fields: 'active,role,created_at'
        }
    }
}

// Использование индексов
form: {
    tabs: {
        first_group: {
            label: 'Первая группа',
            fields: '0-3'  // Первые 4 поля
        },
        second_group: {
            label: 'Вторая группа',
            fields: '4-7'  // Следующие 4 поля
        }
    }
}

// Комбинированный подход
form: {
    tabs: {
        main: {
            label: 'Основная информация',
            fields: 'id,name,0-2'  // id, name и первые 3 поля по индексу
        },
        contacts: {
            label: 'Контакты',
            fields: 'email,phone,address'
        },
        system: {
            label: 'Системные',
            fields: '10,11,created_at,updated_at'
        }
    }
}

// Для большой формы с автоматическим табом "Дополнительно"
form: {
    tabs: {
        personal: {
            label: 'Личные данные',
            fields: 'name,surname,birthdate'
        },
        work: {
            label: 'Работа',
            fields: 'position,department,salary'
        }
        // Остальные поля автоматически попадут в таб "Дополнительно"
    }
}
```

**Поведение:**
- Первый таб активируется автоматически при открытии формы
- Переключение между табами сохраняет введенные данные
- Валидация работает для всех полей независимо от активного таба
- Поля в табах отображаются в том же стиле, что и в обычной форме

**Совместимость:**
- Работает в модальных окнах редактирования (PVTables)
- Работает в панелях форм (PVFormPanel)
- Полная поддержка всех типов полей
- Совместимо с `customFields` и `readonly`

**Приоритет режимов:**
1. Если указан `template`, используется кастомный шаблон
2. Если указан `tabs`, используется режим табов
3. Если ничего не указано, используется стандартная форма

**Технические особенности:**
- Использует компоненты Tabs из PrimeVue
- Автоматическое отслеживание использованных полей
- Эффективная работа с большим количеством полей
- Поддержка динамического изменения конфигурации

### Hide ID
```javascript
properties: {
    hide_id: 1, // Скрывает поле ID в интерфейсе таблицы
    // остальные настройки...
}
```

Параметр `hide_id` используется для скрытия поля ID в интерфейсе таблицы. Полезно когда:
- ID не имеет смысла для пользователя
- При отображении агрегированных данных
- При группировке записей по полям
- Когда ID записи не относится к отображаемой строке целиком

### Actions (Действия)
```javascript
actions: {
    read: {}, // Чтение
    create: { groups: 'Administrator' }, // Создание
    update: { groups: 'Administrator' }, // Обновление
    delete: { groups: 'Administrator' }, // Удаление
    excel_export: {}, // Экспорт в Excel (включено по умолчанию)
    
    // Кастомные действия
    raschet_row: {
        action: 'gtsshop/raschet_row', // Путь к методу в сервисном файле
        row: true, // Действие применяется к строке
        icon: "pi pi-calculator", // Иконка для кнопки
        groups: 'Administrator' // Группы доступа (опционально)
    }
}
```

#### Excel Export (Экспорт в Excel)

**Новая возможность (v2024.1):** Автоматический экспорт данных таблицы в формат Excel с поддержкой всех типов полей и форматирования.

**Автоматическое включение:**
Действие `excel_export` автоматически добавляется во все таблицы, если не отключено явно:

```javascript
actions: {
    excel_export: false // Отключить экспорт в Excel
}
```

**Стандартные настройки:**
```javascript
actions: {
    excel_export: {
        head: true, // Кнопка в заголовке таблицы
        icon: 'pi pi-file-excel', // Иконка Excel
        class: 'p-button-rounded p-button-success', // Зеленая кнопка
        label: 'Excel' // Подпись кнопки
    }
}
```

**Функциональность экспорта:**
- **Все строки**: Экспортируются все данные с `limit = 0`
- **Текущие фильтры**: Применяются активные фильтры таблицы
- **Форматирование**: Автоматическое форматирование ячеек по типам полей
- **Автокомплиты**: Выгружаются в читаемом виде с использованием предзагруженных данных

**Обработка типов полей:**

1. **Autocomplete поля** - выгружаются в 2 столбца:
   - `{label} ID` - числовое значение ID
   - `{label}` - отображаемое значение (name, title, content)

2. **Multiautocomplete поля** - создается несколько столбцов:
   - Для каждого поля поиска создается отдельный столбец
   - Формат: `{label} - {search_field_label}`
   - Значения автоматически преобразуются в читаемый вид

3. **Date поля** - форматируются как даты Excel:
   - Автоматическое преобразование в Excel формат даты
   - Применяется форматирование ячейки `d.m.Y`

4. **Остальные типы** - выгружаются как есть с сохранением форматирования

**Форматирование Excel файла:**
- **Заголовки**: Выделены жирным шрифтом
- **Автофильтр**: Применяется к строке заголовков
- **Границы**: Добавляются ко всем ячейкам с данными
- **Автоширина**: Автоматический подбор ширины столбцов (если не задано `col_widths`)
- **Имя файла**: `export_{table_name}_{date}_{time}.xlsx`

**Обработка полей при экспорте:**
- **`type: 'date'`** — экспортируется как Excel-дата с форматом `dd.mm.yyyy`. Дата строится из `Y-m-d` напрямую (без `strtotime`), поэтому нет сдвига из-за часового пояса сервера.
- **`type: 'html'`** — HTML-теги удаляются (`strip_tags`), HTML-сущности декодируются, остаётся чистый текст. `<br>` и `</p>` превращаются в переводы строк. Таким образом колонки вида «ссылка с текстом №18732» попадают в экспорт как «№18732», без сырого `<a href=…>`.
- **`type: 'autocomplete'`** — разворачивается в две колонки:
  - `{label} ID` — числовой id (тип заголовка `autocomplete_id`);
  - `{label}` — читаемое отображение через `tpl`/`content` из autocomplete-конфигурации (тип `autocomplete_display`).
- **`type: 'multiautocomplete'`** — по одной колонке на каждое поле в `search`, формат `{label} - {search_label}`, значения через предзагруженный autocomplete.

#### Ширины колонок: `col_widths` и `max_width`

По умолчанию ширина колонок подбирается автоматически (`setAutoSize`). Для точного контроля используйте `col_widths` в конфиге `excel_export`:

```javascript
actions: {
    excel_export: {
        col_widths: {
            id: 4.5,               // ширина в "символах Excel"
            date: 12,
            document: 32,
            composition: 45,
            summa: 14,
            comment: 42,
            // autocomplete-поля — две колонки; можно задать общую или разные:
            account_id: 9,                 // обе колонки одной ширины
            purpose_id_id: 8,              // только ID-колонка
            purpose_id_display: 30,        // только display-колонка
            confirmed_by_id: 6,
            confirmed_by_display: 10,
        },
        max_width: 60,             // потолок для autoSize у полей вне col_widths
    },
}
```

**Правила поиска ширины для колонки** (в порядке приоритета):

1. Для autocomplete-поля:
   - ID-колонка (`header.type = 'autocomplete_id'`) берёт `{field}_id`, если задан.
   - Display-колонка (`'autocomplete_display'`) берёт `{field}_display`, если задан.
2. Любая колонка с `field = X` берёт `col_widths[X]`, если задан.
3. Если ничего не задано → `setAutoSize(true)`.

**`max_width`**: после вычисления autoSize пересчитывается реальная ширина; колонки, у которых она превысила `max_width` и при этом им не назначена явная ширина, обрезаются до `max_width`. Удобно для таблиц с длинным `textarea`/`html`, чтобы Excel не рисовал их на пол-экрана.

**Единицы измерения**: то же, что и в UI Excel — «символы» стандартного шрифта. Ориентировочно 1 символ ≈ 7 пикселей при Calibri 11.

**Расширенная конфигурация с формой:**
```javascript
actions: {
    excel_export: {
        head: true,
        icon: 'pi pi-file-excel',
        class: 'p-button-rounded p-button-success',
        label: 'Экспорт',
        form: {
            fields: {
                period_start: {
                    label: 'Дата начала',
                    type: 'date'
                },
                period_end: {
                    label: 'Дата окончания', 
                    type: 'date'
                },
                department_id: {
                    label: 'Отдел',
                    type: 'autocomplete',
                    table: 'departments'
                }
            }
        }
    }
}
```

**Поведение с form.fields:**
1. Если указаны `form.fields`, данные из текущих фильтров (`$request['filters']`) выводятся сверху таблицы
2. Автокомплиты в форме автоматически подгружаются и отображаются как читаемый текст
3. Форма отделяется от таблицы пустой строкой
4. Поля формы выводятся в формате: `{label}: {value}`

**Примеры использования:**

```javascript
// Простое отключение экспорта
actions: {
    excel_export: false
}

// Кастомная настройка кнопки
actions: {
    excel_export: {
        label: 'Скачать отчет',
        icon: 'pi pi-download',
        class: 'p-button-outlined'
    }
}

// С дополнительной информацией в заголовке
actions: {
    excel_export: {
        form: {
            fields: {
                report_date: {
                    label: 'Дата отчета',
                    type: 'date'
                },
                manager_id: {
                    label: 'Менеджер',
                    type: 'autocomplete',
                    table: 'users'
                }
            }
        }
    }
}
```

**Технические особенности:**
- Использует библиотеку PHPOffice\PHPExcel из компонента gettables
- Эффективная работа с автокомплитами через предзагруженные данные
- Поддержка всех стандартных фильтров и сортировок
- Автоматическая обработка больших объемов данных
- Корректная работа с кодировкой UTF-8

**Безопасность:**
- Применяются все настройки доступа из конфигурации таблицы
- Экспортируются только те данные, которые пользователь может видеть
- Соблюдаются все фильтры и ограничения доступа

#### Copy (Копирование записей)

**Новая возможность (v2024.1):** Действие `copy` позволяет копировать записи таблицы вместе со связанными данными из других таблиц.

**Автоматическое включение:**
Действие `copy` может быть добавлено в конфигурацию таблицы для копирования записей с их связями.

**Базовая конфигурация:**
```javascript
actions: {
    copy: {
        label: 'Копировать',
        parent_class: 'gsProduct',
        default_template: 'ProductTemplate'
    }
}
```

**Расширенная конфигурация с формой и связями (для PVTables):**
**Проверить потом не знаю как работает в pvtables form. Скорее не работает.**
```javascript
actions: {
    copy: {
        label: 'Копировать продукт',
        default_template: 'ProductTemplate',
        form: 'PVTables',
        add_fields: {
            product_type_id: {
                label: 'Тип продукта',
                type: 'select',
                select: 'product_type',
                default: 1
            },
            category_id: {
                label: 'Категория',
                type: 'autocomplete',
                table: 'categories'
            }
        },
        child: {
            many: {
                'gsProductParam': 'Params',
                'gsProductVariable': 'Variable',
                'gsProductMaterial': 'Material',
                'gsProductJob': 'Job'
            },
            one: {
                'gsProductSettings': 'Settings'
            }
        }
    }
}
```

**Конфигурация для UniTree (с множественными таблицами):**
```javascript
actions: {
    copy: {
        tables: {
            gsProduct: {
                label: 'Копировать продукт',
                parent_classes: ['gsProduct'],  // Массив классов-родителей
                form: 'UniTree',
                add_fields: {
                    product_type_id: {
                        label: 'Тип продукта',
                        type: 'select',
                        select: 'product_type',
                        default: 1
                    }
                },
                child: {
                    many: {
                        'gsProductParam': 'Params',
                        'gsProductVariable': 'Variable'
                    }
                }
            },
            gsCategory: {
                label: 'Копировать категорию',
                parent_classes: ['gsCategory'],
                form: 'UniTree',
                add_fields: {
                    name: {
                        label: 'Название',
                        type: 'text'
                    }
                }
            }
        }
    }
}
```

**Параметры действия copy:**
- `label` (обязательно) - название действия в интерфейсе
- `parent_class` (опционально) - класс MODX для основной таблицы
- `default_template` (опционально) - шаблон по умолчанию для новой записи
- `form` (опционально) - тип формы: 'UniTree' или 'PVTables'
- `add_fields` (опционально) - дополнительные поля для ввода при копировании
- `child` (опционально) - конфигурация связанных таблиц для копирования

**Параметр add_fields:**
Объект с полями формы, где ключ - имя поля, значение - конфигурация поля:
- `label` - подпись поля
- `type` - тип поля (text, select, autocomplete, date и т.д.)
- `table` - таблица для autocomplete (если type = 'autocomplete')
- `select` - название селекта (если type = 'select')
- `default` - значение по умолчанию

**Параметр child:**
- `many` - объект для копирования связей типа "один ко многим"
  - Ключ: класс MODX связанной таблицы
  - Значение: алиас связи для метода `getMany()`
- `one` - объект для копирования связей типа "один к одному"
  - Ключ: класс MODX связанной таблицы
  - Значение: алиас связи для метода `getOne()`

**Поведение при копировании:**

1. **Без формы:**
   - Создается копия записи с новым ID
   - Копируются все поля кроме ID
   - Если указаны связи `child`, копируются связанные записи

2. **С формой:**
   - Открывается модальное окно с полями из `form.row`
   - Пользователь может изменить значения полей
   - Применяются значения по умолчанию из `default`
   - После подтверждения создается копия с новыми значениями

3. **Копирование связей many:**
   - Загружаются все связанные записи через `getMany(alias)`
   - Удаляются существующие связи у новой записи (если есть)
   - Создаются новые записи связей с привязкой к новой записи

4. **Копирование связей one:**
   - Загружается связанная запись через `getOne(alias)`
   - Создается новая запись связи с привязкой к новой записи

**Примеры использования:**

```javascript
// Простое копирование без связей
actions: {
    copy: {
        label: 'Дублировать',
        parent_class: 'Article'
    }
}

// Копирование с формой
actions: {
    copy: {
        label: 'Копировать статью',
        parent_class: 'Article',
        form: 'PVTables',
        add_fields: {
            title: {
                label: 'Новое название',
                type: 'text'
            },
            published: {
                label: 'Опубликовано',
                type: 'boolean',
                default: 0
            }
        }
    }
}

// Копирование со связями many
actions: {
    copy: {
        label: 'Копировать с параметрами',
        parent_class: 'Product',
        child: {
            many: {
                'ProductParam': 'Params',
                'ProductImage': 'Images'
            }
        }
    }
}

// Полная конфигурация
actions: {
    copy: {
        label: 'Копировать заказ',
        parent_class: 'Order',
        default_template: 'OrderTemplate',
        form: 'UniTree',
        add_fields: {
            order_date: {
                label: 'Дата заказа',
                type: 'date'
            },
            status_id: {
                label: 'Статус',
                type: 'select',
                select: 'order_status',
                default: 1
            }
        },
        child: {
            many: {
                'OrderItem': 'Items',
                'OrderPayment': 'Payments'
            },
            one: {
                'OrderSettings': 'Settings'
            }
        }
    }
}
```

**Триггеры:**
Действие `copy` поддерживает триггеры:
- `before_copy` - выполняется перед копированием
- `after_copy` - выполняется после успешного копирования

**Ответ сервера:**
```javascript
{
    success: true,
    message: 'Запись успешно скопирована',
    data: {
        id: 123, // ID новой записи
        saved: [
            // Массив результатов сохранения связей
        ]
    }
}
```

**Интеграция с интерфейсом:**
- Кнопка копирования отображается в строке таблицы (если `row: true`)
- Кнопка копирования отображается в заголовке (если `head: true`)
- Иконка по умолчанию: `pi pi-copy`
- Класс кнопки по умолчанию: `p-button-info`
- Поддержка множественного выбора для копирования нескольких записей

**Безопасность:**
- Применяются все настройки доступа из конфигурации таблицы
- Копируются только те данные, которые пользователь может видеть
- Соблюдаются все фильтры и ограничения доступа


#### Print (Печать)

**Новая возможность (v2024.1):** Автоматическая печать данных таблицы с поддержкой всех типов полей, форматирования и интеграции с PVPrint.

**Автоматическое включение:**
Действие `print` автоматически добавляется во все таблицы, если не отключено явно:

```javascript
actions: {
    print: false // Отключить печать
}
```

**Стандартные настройки:**
```javascript
actions: {
    print: {
        head: true, // Кнопка в заголовке таблицы
        icon: 'pi pi-print', // Иконка принтера
        class: 'p-button-rounded p-button-info', // Синяя кнопка
        label: 'Печать' // Подпись кнопки
    }
}
```
Эти настройки иногрируются так как в PVTables компонент PVPrint подгружается динамически и настройки не подходят.

**Функциональность печати:**
- **Все строки**: Печатаются все данные с `limit = 0`
- **Текущие фильтры**: Применяются активные фильтры таблицы
- **Форматирование**: Автоматическое форматирование HTML для печати
- **Автокомплиты**: Выводятся в читаемом виде с использованием предзагруженных данных

**Обработка типов полей:**

1. **Autocomplete поля** - выводятся в читаемом виде:
   - Автоматическое преобразование ID в отображаемое значение (name, title, content)
   - Использование предзагруженных данных из autocompletes

2. **Multiautocomplete поля** - создается несколько столбцов:
   - Для каждого поля поиска создается отдельный столбец
   - Формат: `{label} - {search_field_label}`
   - Значения автоматически преобразуются в читаемый вид

3. **Остальные типы** - выводятся как есть с сохранением форматирования

**Форматирование HTML документа:**
- **Заголовок**: Название таблицы в центре документа
- **Таблица**: Полная таблица с границами и заголовками
- **Стили**: Встроенные CSS стили для корректной печати
- **Кодировка**: UTF-8 для корректного отображения кириллицы

**Расширенная конфигурация с формой:**
```javascript
actions: {
    print: {
        head: true,
        icon: 'pi pi-print',
        class: 'p-button-rounded p-button-info',
        label: 'Печать',
        form: {
            fields: {
                period_start: {
                    label: 'Дата начала',
                    type: 'date'
                },
                period_end: {
                    label: 'Дата окончания', 
                    type: 'date'
                },
                department_id: {
                    label: 'Отдел',
                    type: 'autocomplete',
                    table: 'departments'
                }
            }
        }
    }
}
```

**Поведение с form.fields:**
1. Если указаны `form.fields`, данные из текущих фильтров (`$request['filters']`) выводятся сверху таблицы
2. Автокомплиты в форме автоматически подгружаются и отображаются как читаемый текст
3. Форма отделяется от таблицы отступом
4. Поля формы выводятся в формате: `{label}: {value}`

**Интеграция с PVPrint:**

Действие `print` поддерживает два режима работы:

1. **Виртуальная печать (is_virtual = 1):**
   - Генерируется HTML и возвращается в браузер
   - Браузер создает PDF через встроенный механизм печати
   - Не требует установки PVPrint

2. **Физическая печать через PVPrint (is_virtual = 0):**
   - Генерируется HTML и отправляется на физический принтер
   - Требует установленный компонент PVPrint
   - Поддержка настроек принтера через `printOptions`

**Пример запроса с параметрами печати:**
```javascript
// Виртуальная печать (PDF в браузере)
{
    api_action: 'print',
    is_virtual: 1,
    filters: { /* текущие фильтры */ }
}

// Физическая печать через PVPrint
{
    api_action: 'print',
    is_virtual: 0,
    printer_id: 5, // ID принтера из PVPrint
    printOptions: {
        copies: 2,
        orientation: 'portrait'
    },
    filters: { /* текущие фильтры */ }
}
```

**Примеры использования:**

```javascript
// Простое отключение печати
actions: {
    print: false
}

// Кастомная настройка кнопки
actions: {
    print: {
        label: 'Распечатать отчет',
        icon: 'pi pi-file-pdf',
        class: 'p-button-outlined'
    }
}

// С дополнительной информацией в заголовке
actions: {
    print: {
        form: {
            fields: {
                report_date: {
                    label: 'Дата отчета',
                    type: 'date'
                },
                manager_id: {
                    label: 'Менеджер',
                    type: 'autocomplete',
                    table: 'users'
                }
            }
        }
    }
}
```

**Технические особенности:**
- Генерация HTML с встроенными стилями для печати
- Эффективная работа с автокомплитами через предзагруженные данные
- Поддержка всех стандартных фильтров и сортировок
- Автоматическая обработка больших объемов данных
- Корректная работа с кодировкой UTF-8
- Поддержка скрытия полей через `no_print` и `hide_id`

**Скрытие полей при печати:**
```javascript
fields: {
    "internal_notes": {
        "label": "Внутренние заметки",
        "type": "textarea",
        "no_print": true // Поле не будет выводиться при печати
    }
}
```

**Безопасность:**
- Применяются все настройки доступа из конфигурации таблицы
- Печатаются только те данные, которые пользователь может видеть
- Соблюдаются все фильтры и ограничения доступа
- HTML-экранирование для предотвращения XSS

**Совместимость:**
- Работает со всеми типами полей
- Полная совместимость с фильтрами и сортировками
- Поддержка виртуальной и физической печати
- Интеграция с PVPrint

#### Кастомные действия

Кастомные действия позволяют добавлять специальные операции, которые обрабатываются в сервисном файле пакета (например, `gtsshop.class.php`).

**Структура кастомного действия:**
- `action` - путь к методу в формате `package/method_name`
- `row` - если `true`, действие применяется к выбранной строке
- `head` - если `true`, действие доступно в заголовке таблицы
- `icon` - CSS класс иконки для кнопки (например, `pi pi-calculator`)
- `label` - текст кнопки (для head действий)
- `groups` - группы пользователей с доступом к действию
- `modal_form` - конфигурация модальной формы (опционально)
- `template_row` - кастомный Vue шаблон для отображения кнопки действия в строке (новое в v2024.1)

**Простой пример кастомного действия:**
```javascript
raschet_row: {
    action: 'gtsshop/raschet_row',
    row: true,
    icon: "pi pi-calculator",
    groups: 'Administrator'
}
```

**Кастомное действие с модальной формой:**
```javascript
move_naryads: {
    action: 'gtscraft/move_naryads',
    row: true,
    head: true,
    icon: 'pi pi-arrows-alt',
    modal_form: {
        fields: {
            smena_id: {
                label: 'Смена',
                type: 'autocomplete',
                table: 'gcSmena',
                readonly: 1
            },
            comment: {
                label: 'Комментарий',
                type: 'textarea'
            }
        },
        buttons: {
            submit: {
                label: 'Переместить',
                icon: 'pi pi-check'
            }
        }
    }
}
```

**Кастомное действие с Vue шаблоном (новое в v2024.1):**
```javascript
custom_status: {
    action: 'mypackage/update_status',
    row: true,
    template_row: `
        <div class="custom-action-group">
            <button 
                v-if="data.status === 'pending'" 
                @click="executeAction()" 
                class="p-button p-button-success p-button-sm"
            >
                <i class="pi pi-check"></i> Одобрить
            </button>
            <button 
                v-else-if="data.status === 'approved'" 
                @click="executeAction()" 
                class="p-button p-button-warning p-button-sm"
            >
                <i class="pi pi-pause"></i> Приостановить
            </button>
            <span v-else class="status-completed">
                <i class="pi pi-check-circle text-green-500"></i> Завершено
            </span>
        </div>
    `
}
```

#### Кастомные Vue шаблоны для действий (template_row)

**Новая возможность (v2024.1):** Параметр `template_row` позволяет создавать полностью кастомные Vue шаблоны для отображения кнопок действий в строках таблицы.

**Основные возможности:**
- Полная кастомизация внешнего вида кнопок действий
- Условная логика отображения на основе данных строки
- Поддержка всех Vue директив и возможностей
- Безопасная компиляция с валидацией шаблонов

**Доступные переменные в шаблоне:**
- `data` - объект данных строки таблицы
- `columns` - массив колонок таблицы
- `table` - название таблицы
- `filters` - текущие фильтры таблицы
- `action` - объект конфигурации действия

**Доступные методы:**
- `executeAction()` - выполнить действие (вызывает серверный метод)
- `emitEvent(eventName, data)` - эмитировать кастомное событие

**Примеры использования:**

```javascript
// Простая кнопка с условным отображением
simple_approve: {
    action: 'mypackage/approve_item',
    row: true,
    template_row: `
        <button 
            v-if="data.status === 'pending'" 
            @click="executeAction()" 
            class="p-button p-button-success p-button-sm"
        >
            <i class="pi pi-check"></i> Одобрить
        </button>
    `
}

// Группа кнопок с разными состояниями
status_control: {
    action: 'mypackage/change_status',
    row: true,
    template_row: `
        <div class="action-group">
            <button 
                v-if="data.status === 'draft'" 
                @click="executeAction()" 
                class="p-button p-button-info p-button-sm"
            >
                <i class="pi pi-send"></i> Отправить
            </button>
            <button 
                v-else-if="data.status === 'pending'" 
                @click="executeAction()" 
                class="p-button p-button-success p-button-sm"
            >
                <i class="pi pi-check"></i> Одобрить
            </button>
            <button 
                v-else-if="data.status === 'approved'" 
                @click="executeAction()" 
                class="p-button p-button-warning p-button-sm"
            >
                <i class="pi pi-pause"></i> Приостановить
            </button>
            <span v-else class="status-completed">
                <i class="pi pi-check-circle text-green-500"></i> Завершено
            </span>
        </div>
    `
}

// Кнопка с подтверждением
delete_with_confirm: {
    action: 'mypackage/delete_item',
    row: true,
    template_row: `
        <button 
            @click="confirmDelete()" 
            class="p-button p-button-danger p-button-sm"
            :disabled="data.has_children"
        >
            <i class="pi pi-trash"></i>
            {{ data.has_children ? 'Заблокировано' : 'Удалить' }}
        </button>
    `
}

// Кнопка с динамическим текстом и иконкой
dynamic_action: {
    action: 'mypackage/toggle_status',
    row: true,
    template_row: `
        <button 
            @click="executeAction()" 
            :class="'p-button p-button-sm ' + (data.active ? 'p-button-warning' : 'p-button-success')"
        >
            <i :class="'pi ' + (data.active ? 'pi-pause' : 'pi-play')"></i>
            {{ data.active ? 'Деактивировать' : 'Активировать' }}
        </button>
    `
}

// Ссылка вместо кнопки
view_details: {
    action: 'mypackage/view_details',
    row: true,
    template_row: `
        <a 
            :href="'/admin/details/' + data.id" 
            target="_blank"
            class="text-blue-500 hover:text-blue-700"
        >
            <i class="pi pi-external-link"></i> Подробнее
        </a>
    `
}

// Комплексный пример с несколькими элементами
complex_actions: {
    action: 'mypackage/complex_action',
    row: true,
    template_row: `
        <div class="flex gap-2 items-center">
            <!-- Основная кнопка действия -->
            <button 
                @click="executeAction()" 
                class="p-button p-button-primary p-button-sm"
                :disabled="!data.can_edit"
            >
                <i class="pi pi-pencil"></i>
            </button>
            
            <!-- Индикатор статуса -->
            <span 
                :class="'status-indicator status-' + data.status"
                :title="'Статус: ' + data.status"
            >
                <i :class="'pi ' + getStatusIcon(data.status)"></i>
            </span>
            
            <!-- Счетчик связанных записей -->
            <span 
                v-if="data.children_count > 0" 
                class="badge badge-info"
                :title="data.children_count + ' связанных записей'"
            >
                {{ data.children_count }}
            </span>
        </div>
    `
}
```

**Обработка событий в шаблоне:**

```javascript
// Кастомная обработка с подтверждением
confirm_action: {
    action: 'mypackage/dangerous_action',
    row: true,
    template_row: `
        <button 
            @click="handleConfirm()" 
            class="p-button p-button-danger p-button-sm"
        >
            <i class="pi pi-exclamation-triangle"></i> Опасное действие
        </button>
    `,
    // В серверном коде можно обработать дополнительные параметры
    setup: `
        const handleConfirm = () => {
            if (confirm('Вы уверены? Это действие нельзя отменить!')) {
                executeAction();
            }
        };
        return { handleConfirm };
    `
}
```

**Стилизация кнопок:**

```javascript
styled_button: {
    action: 'mypackage/styled_action',
    row: true,
    template_row: `
        <button 
            @click="executeAction()" 
            class="custom-action-btn"
            :style="{
                backgroundColor: data.priority === 'high' ? '#ff4444' : '#44ff44',
                color: 'white',
                border: 'none',
                padding: '8px 12px',
                borderRadius: '4px',
                cursor: 'pointer'
            }"
        >
            <i class="pi pi-bolt"></i>
            {{ data.priority === 'high' ? 'Срочно' : 'Обычно' }}
        </button>
    `
}
```

**Безопасность template_row:**
- Все шаблоны проходят валидацию на предмет безопасности
- Блокируются потенциально опасные конструкции
- При ошибке компиляции показывается предупреждение
- Используется стандартная кнопка как fallback

**Поведение:**
- Если указан `template_row`, он используется вместо стандартной кнопки
- Если компиляция не удалась, отображается стандартная кнопка с `icon` и `class`
- Метод `executeAction()` автоматически вызывает серверное действие с данными строки
- Поддерживается полная интеграция с системой уведомлений и обновления таблицы

**Совместимость:**
- Работает только с действиями типа `row` (строковые действия)
- Полная совместимость с `modal_form`
- Не влияет на производительность при отсутствии шаблона
- Поддерживается во всех браузерах

**Параметры modal_form:**
- `fields` - поля формы (аналогично полям таблицы)
- `buttons.submit.label` - текст кнопки отправки (по умолчанию "Выполнить")
- `buttons.submit.icon` - иконка кнопки отправки (по умолчанию "pi pi-check")

**Поведение с modal_form:**
1. При нажатии на кнопку действия открывается модальная форма
2. Пользователь заполняет поля формы
3. При нажатии кнопки отправки данные формы объединяются с данными строки/фильтров
4. Отправляется запрос к серверному методу с полными данными

**Пример реализации в сервисном файле:**
```php
public function raschet_row($data = array())
{
    // Получение ID строки из $data['id']
    if(!$gsRaschetProduct = $this->modx->getObject("gsRaschetProduct", (int)$data['id'])) {
        return $this->error('Не найдена строка расчета!');
    }
    
    // Выполнение расчетов
    // ... логика обработки ...
    
    return $this->success('Расчет выполнен!', ['refresh_row' => 1]);
}

public function move_naryads($data = array())
{
    // Получение данных из формы
    $smena_id = $data['smena_id'] ?? null;
    $comment = $data['comment'] ?? '';
    
    // Получение данных строки (если row action)
    $naryad_id = $data['naryad_id'] ?? null;
    
    // Логика перемещения нарядов
    // ... обработка ...
    
    return $this->success('Наряды перемещены!', ['refresh_table' => 1]);
}

public function handleRequest($action, $data = array())
{
    switch($action) {
        case 'raschet_row':
            return $this->raschet_row($data);
        case 'move_naryads':
            return $this->move_naryads($data);
        break;
        // другие действия...
    }
}
```

**Методы ответа:**
- `$this->success($message, $data)` - успешный ответ
- `$this->error($message, $data)` - ответ с ошибкой

**Специальные параметры ответа:**
- `refresh_row` - обновить строку в таблице
- `refresh_table` - обновить всю таблицу
- `reload_with_id` - перезагрузить с новым ID

### Query (Запросы)
```javascript
query: {
    leftJoin: {
        TableName: {
            class: 'ClassName',
            on: 'TableName.id = MainTable.foreign_id'
        }
    },
    where: {
        'field': 'value'
    },
    select: {
        MainTable: '*',
        TableName: 'TableName.field1, TableName.field2'
    },
    sortby: {
        'field': 'ASC'
    }
}
```

### Autocomplete
```javascript
autocomplete: {
    select: [
        "id",
        "name", 
        "date"
    ], // Поля для выборки
    tpl: '{$name} - {$date | date : "d.m.Y"}', // Шаблон отображения (Fenom)
    template: `
        <div class="custom-item">
            <strong>{{ option.name }}</strong>
            <small>{{ option.date }}</small>
        </div>
    `, // Vue-шаблон для кастомного отображения (новое в v2024.1)
    where: {
        "name:LIKE": "%query%"
    },
    limit: 10, // Лимит записей (0 - без лимита)
    query: {
        // Дополнительные параметры запроса (leftJoin, where и т.д.)
        leftJoin: {
            RelatedTable: {
                class: "RelatedTable",
                on: "MainTable.related_id = RelatedTable.id"
            }
        }
    }
}
```

**Новый параметр `template` (v2024.1):**
- Позволяет задавать Vue-шаблоны для кастомного отображения элементов автокомплита
- Имеет приоритет над стандартным `tpl` (Fenom-шаблоном)
- Поддерживает все возможности Vue: директивы, привязки, условную логику
- Доступные переменные: `option` (объект записи), `index` (индекс элемента)

**Примеры использования template:**

```javascript
// Простой шаблон
autocomplete: {
    template: `<div><strong>{{ option.name }}</strong> - {{ option.email }}</div>`
}

// Шаблон с условной логикой
autocomplete: {
    template: `
        <div class="user-item" :class="{ 'inactive': !option.active }">
            <div class="user-name">{{ option.name }}</div>
            <div v-if="option.avatar" class="user-avatar">
                <img :src="option.avatar" />
            </div>
            <div class="user-status">
                <span v-if="option.active" class="status-active">Активен</span>
                <span v-else class="status-inactive">Неактивен</span>
            </div>
        </div>
    `
}

// Шаблон с форматированием данных
autocomplete: {
    template: `
        <div class="product-item">
            <div class="product-name">{{ option.name }}</div>
            <div class="product-price">{{ option.price }}₽</div>
            <div v-if="option.discount > 0" class="product-discount">
                Скидка: {{ option.discount }}%
            </div>
        </div>
    `
}
```

**Совместимость:**
- Если указан `template`, он используется вместо `tpl`
- Если `template` не указан, используется стандартный `tpl` (Fenom)
- При ошибке компиляции `template` автоматически используется `tpl` как fallback

#### Виртуальный скроллинг и ленивая загрузка

**Новая возможность (v2024.1):** Автокомплиты теперь поддерживают виртуальный скроллинг с ленивой загрузкой для эффективной работы с большими объемами данных.

**Автоматическая активация:**
- Виртуальный скроллинг активируется автоматически при наличии более 10 записей
- Данные загружаются порциями по 10 записей при прокрутке до конца списка
- Отображаются только видимые элементы списка для экономии памяти

**Преимущества:**
- Высокая производительность при работе с тысячами записей
- Минимальное потребление памяти браузера
- Плавная прокрутка без задержек
- Полная обратная совместимость с существующими конфигурациями

**Настройка размера страницы:**
```javascript
autocomplete: {
    limit: 20, // Увеличить размер страницы до 20 записей
    // остальные параметры...
}
```

#### Динамические шаблоны отображения

**Новая возможность (v2024.1):** Поддержка динамических Vue-шаблонов для кастомного отображения элементов в списке автокомплита.

**Базовое использование:**
```javascript
fields: {
    "product_id": {
        "type": "autocomplete",
        "table": "products",
        "template": `
            <div class="product-item">
                <strong>{{ option.name }}</strong>
                <div class="product-details">
                    <span class="price">{{ option.price }}₽</span>
                    <span class="category">{{ option.category_name }}</span>
                </div>
            </div>
        `
    }
}
```

**Расширенный пример с условной логикой:**
```javascript
"user_id": {
    "type": "autocomplete", 
    "table": "users",
    "template": `
        <div class="user-item" :class="{ 'user-offline': !option.is_online }">
            <div class="user-avatar">
                <img v-if="option.avatar" :src="option.avatar" />
                <div v-else class="avatar-placeholder">{{ option.name.charAt(0) }}</div>
            </div>
            <div class="user-info">
                <div class="user-name">{{ option.name }}</div>
                <div class="user-status">
                    <span v-if="option.is_online" class="status-online">В сети</span>
                    <span v-else class="status-offline">Не в сети</span>
                    <span class="last-seen">{{ option.last_seen }}</span>
                </div>
            </div>
        </div>
    `
}
```

**Доступные переменные в шаблоне:**
- `option` - объект записи со всеми полями из базы данных
- `index` - индекс элемента в списке

**Поддерживаемые Vue-возможности:**
- Интерполяция данных `{{ }}`
- Директивы `v-if`, `v-else`, `v-show`
- Привязка классов `:class`
- Привязка атрибутов `:src`, `:href` и т.д.
- Условная отрисовка элементов

**Обработка ошибок:**
- При ошибке компиляции шаблона показывается уведомление пользователю
- В консоли выводится подробная информация об ошибке
- При ошибке используется стандартное отображение

**Совместимость:**
- Работает с обычными `autocomplete` полями
- Работает с `multiautocomplete` полями
- Полная совместимость с виртуальным скроллингом
- Не влияет на производительность при отсутствии шаблона

**Параметры autocomplete:**

- `select` - массив полей для выборки из базы данных
- `tpl` - Fenom-шаблон для отображения элементов в списке автокомплита
- `where` - условия фильтрации (поддерживает плейсхолдер `%query%` для поискового запроса)
- `limit` - максимальное количество записей в результате (0 = без ограничений)
- `query` - дополнительные параметры запроса (leftJoin, where, sortby и т.д.)

**Поддержка условий where с Fenom-шаблонами:**

Для полей автокомплита можно задавать дополнительные условия фильтрации с использованием Fenom-шаблонов:

```javascript
fields: {
    smena_id: {
        label: 'Смена',
        type: 'autocomplete',
        table: 'gcSmena',
        where: {
            'date:>=': `{'' | date: "Y-m-d"}` // Только смены начиная с текущей даты
        }
    }
}
```

**Поддерживаемые Fenom-модификаторы в where:**
- `{'' | date: "Y-m-d"}` - текущая дата
- `{'+1 day' | date: "Y-m-d"}` - завтрашняя дата  
- `{'-1 day' | date: "Y-m-d"}` - вчерашняя дата
- `{'+1 week' | date: "Y-m-d"}` - дата через неделю
- `{'+1 month' | date: "Y-m-01"}` - первый день следующего месяца
- `{'' | date: "Y-m-t"}` - последний день текущего месяца

**Примеры использования where с датами:**

```javascript
// Только будущие даты
where: {
    'date:>=': `{'' | date: "Y-m-d"}`
}

// Только текущий месяц
where: {
    'date:>=': `{'' | date: "Y-m-01"}`,
    'date:<=': `{'' | date: "Y-m-t"}`
}

// Только завтрашние записи
where: {
    'date': `{'+1 day' | date: "Y-m-d"}`
}
```

**Безопасность:**
Обработка Fenom-шаблонов в условиях where ограничена только модификатором `date` для предотвращения выполнения произвольного кода. Это обеспечивает безопасность системы при работе с пользовательскими данными.

**Применение:**
- Условия where из конфигурации полей применяются автоматически при всех запросах автокомплита
- Поддерживается как в обычном autocomplete, так и в multiautocomplete
- Работает во всех компонентах: поиск, получение по ID, получение по show_id

### Fields (Поля)
```javascript
fields: {
    "field_name": {
        "label": "Подпись поля",
        "type": "text", // Тип поля
        "desc": "Описание поля", // Подсказка под полем (новое в v2024.1)
        "required": true, // Обязательное поле с валидацией (новое в v2024.1)
        "needed": true, // Альтернатива required (новое в v2024.1)
        "readonly": true, // Только для чтения
        "modal_only": true, // Только в модальном окне
        "table_only": true // Только в таблице
    }
}
```

#### Описание и валидация полей (новое в v2024.1)

**Параметр `desc`** - добавляет текстовое описание под полем ввода:
```javascript
fields: {
    "email": {
        "label": "Email",
        "type": "text",
        "desc": "Используется для отправки уведомлений и восстановления пароля"
    },
    "price": {
        "label": "Цена",
        "type": "decimal",
        "desc": "Цена указывается в рублях без НДС"
    }
}
```

**Параметры `required` и `needed`** - делают поле обязательным для заполнения:
```javascript
fields: {
    "name": {
        "label": "Название",
        "type": "text",
        "required": true, // или "required": 1
        "desc": "Обязательное поле - введите уникальное название"
    },
    "category_id": {
        "label": "Категория",
        "type": "autocomplete",
        "table": "categories",
        "needed": 1, // Альтернативный параметр
        "desc": "Выберите категорию из списка"
    }
}
```

**Визуальное отображение:**
- Обязательные поля отмечаются красной звездочкой `*` рядом с меткой
- Описание отображается серым текстом под полем ввода
- При пустом значении обязательного поля:
  - Поле получает класс `p-invalid` (красная рамка)
  - Под полем показывается сообщение "Поле обязательно для заполнения"

**Правила валидации:**
- Поле считается невалидным если:
  - Значение `null`, `undefined` или пустая строка `''`
  - Для массивов - длина равна 0
  - Для чисел - значение `NaN`
- Валидация срабатывает в реальном времени при изменении значения
- Работает во всех режимах формы (табы, стандартная форма)

**Примеры использования:**

```javascript
// Простое обязательное поле с описанием
fields: {
    "title": {
        "label": "Заголовок",
        "type": "text",
        "required": true,
        "desc": "Краткий заголовок статьи (не более 100 символов)"
    }
}

// Автокомплит с валидацией
fields: {
    "user_id": {
        "label": "Пользователь",
        "type": "autocomplete",
        "table": "users",
        "needed": 1,
        "desc": "Выберите пользователя из списка или начните вводить имя"
    }
}

// Поле с описанием без валидации
fields: {
    "notes": {
        "label": "Примечания",
        "type": "textarea",
        "desc": "Дополнительная информация (необязательно)"
    }
}

// Комбинация параметров
fields: {
    "phone": {
        "label": "Телефон",
        "type": "text",
        "required": true,
        "desc": "Формат: +7 (XXX) XXX-XX-XX",
        "readonly": false
    }
}
```

**Совместимость:**
- Работает во всех типах полей
- Поддерживается в табах и стандартной форме
- Совместимо с `customFields` и динамическими полями
- Валидация работает с `readonly` полями (не показывается для readonly)

## Типы полей

- `text` - текстовое поле
- `textarea` - многострочное текстовое поле
- `number` - числовое поле
- `decimal` - десятичное число
- `date` - дата
- `boolean` - логическое поле
- `autocomplete` - автокомплит
- `multiautocomplete` - автокомплит с множественными полями поиска
- `multiple` - множественный выбор с таблицей связей (новое в v2024.1)
- `view` - только просмотр
- `hidden` - скрытое поле
- `html` - HTML контент

### Кастомные Vue шаблоны для полей

**Новая возможность (v2024.1):** Поддержка кастомных Vue шаблонов для отображения полей в режиме просмотра с автоматическим переключением в режим редактирования при клике.

**Базовое использование:**
```javascript
fields: {
    "time": {
        "label": "Время работы",
        "type": "decimal",
        "template": `
            <span class="time-badge" @click="$emit('click')">
                {{ Math.floor(value/60) }}ч {{ value%60 }}м
            </span>
        `
    }
}
```

**Расширенный пример с условной логикой:**
```javascript
"status": {
    "label": "Статус",
    "type": "text", 
    "template": `
        <div class="status-display" @click="$emit('click')">
            <span :class="'status-' + value.toLowerCase()">
                {{ value }}
            </span>
            <i v-if="value === 'ERROR'" class="pi pi-exclamation-triangle text-red-500"></i>
            <i v-else-if="value === 'SUCCESS'" class="pi pi-check text-green-500"></i>
        </div>
    `
}
```

**Пример с форматированием данных:**
```javascript
"price": {
    "label": "Цена",
    "type": "decimal",
    "template": `
        <div class="price-display" @click="$emit('click')">
            <span class="currency">{{ new Intl.NumberFormat('ru-RU', {
                style: 'currency', 
                currency: 'RUB'
            }).format(value) }}</span>
            <small v-if="row.discount > 0" class="discount">
                Скидка: {{ row.discount }}%
            </small>
        </div>
    `
}
```

**Доступные переменные в шаблоне:**
- `value` - значение поля
- `field` - объект конфигурации поля
- `row` - объект всей строки данных
- `data` - алиас для `row`

**Поведение:**
- В режиме просмотра отображается кастомный Vue шаблон
- При клике на шаблон активируется режим редактирования со стандартным интерфейсом поля
- После изменения значения автоматически возвращается в режим просмотра
- Если шаблон не указан, используется стандартное отображение

**Поддерживаемые Vue-возможности:**
- Интерполяция данных `{{ }}`
- Директивы `v-if`, `v-else`, `v-show`
- Привязка классов `:class`
- Привязка атрибутов `:src`, `:href` и т.д.
- Обработка событий `@click`, `@mouseover` и т.д.
- Условная отрисовка элементов

**Безопасность:**
- Все шаблоны проходят валидацию на предмет безопасности
- Блокируются потенциально опасные конструкции (доступ к `window`, `document`, `eval` и т.д.)
- При обнаружении опасного кода шаблон не компилируется и показывается предупреждение
- Ошибки компиляции обрабатываются с уведомлениями пользователю

**Запрещенные конструкции в шаблонах:**
- `$parent`, `$root` - доступ к родительским компонентам
- `document.`, `window.` - доступ к глобальным объектам браузера
- `eval(`, `<script` - выполнение произвольного кода
- `localStorage`, `sessionStorage` - доступ к хранилищу
- `fetch(`, `XMLHttpRequest` - сетевые запросы
- `setTimeout`, `setInterval` - таймеры

**Примеры практического применения:**

```javascript
// Отображение времени в удобном формате
"duration": {
    "type": "number", // минуты в БД
    "template": `
        <span class="duration-badge" @click="$emit('click')">
            {{ value >= 1440 ? Math.floor(value/1440) + 'д ' : '' }}
            {{ Math.floor((value%1440)/60).toString().padStart(2,'0') }}:{{ (value%60).toString().padStart(2,'0') }}
        </span>
    `
}

// Прогресс-бар для процентов
"progress": {
    "type": "decimal",
    "template": `
        <div class="progress-container" @click="$emit('click')">
            <div class="progress-bar" :style="'width: ' + value + '%'"></div>
            <span class="progress-text">{{ value }}%</span>
        </div>
    `
}

// Статус с иконками
"order_status": {
    "type": "text",
    "template": `
        <div class="status-badge" :class="'status-' + value" @click="$emit('click')">
            <i class="pi" :class="{
                'pi-clock': value === 'pending',
                'pi-cog': value === 'processing', 
                'pi-check': value === 'completed',
                'pi-times': value === 'cancelled'
            }"></i>
            {{ value }}
        </div>
    `
}
```

**Совместимость:**
- Работает со всеми типами полей
- Полная обратная совместимость - если `template` не указан, используется стандартное отображение
- Не влияет на производительность при отсутствии шаблона
- Поддерживается в таблицах, формах и модальных окнах

### Multiple (Множественный выбор с таблицей связей)

**Новый тип поля (v2024.1):** `multiple` позволяет создавать поля для множественного выбора записей с автоматическим управлением связями через промежуточную таблицу.

**Основные возможности:**
- Множественный выбор записей из справочной таблицы
- Автоматическое управление связями через таблицу связей (table_link)
- Отображение выбранных записей в виде чипов
- Поиск и фильтрация доступных записей
- Мгновенное сохранение изменений в базу данных
- Поддержка режима readonly

**Структура конфигурации:**
```javascript
fields: {
    "tags": {
        "label": "Теги",
        "type": "multiple",
        "table_link": "product_tags",      // Таблица связей в gtsAPI
        "table": "tags",                   // Таблица с данными для выбора
        "title_field": "name",             // Поле для отображения в списке
        "main_id": "product_id",           // Поле ID основной записи в table_link
        "slave_id": "tag_id",              // Поле ID связанной записи в table_link
        "readonly": false                  // Опционально, блокировка редактирования
    }
}
```

**Параметры поля:**
- `table_link` (обязательно) - название таблицы связей в gtsAPI, где хранятся связи между основной таблицей и справочной
- `table` (обязательно) - название справочной таблицы с данными для выбора
- `title_field` (обязательно) - имя поля из `table`, которое отображается в списке выбора
- `main_id` (обязательно) - имя поля в `table_link`, которое хранит ID записи основной таблицы
- `slave_id` (обязательно) - имя поля в `table_link`, которое хранит ID записи из справочной таблицы `table`
- `readonly` (опционально) - если `true`, поле доступно только для просмотра

**Пример использования:**

```javascript
// Конфигурация для связи товаров с тегами
export default {
    myshop: {
        name: 'myshop',
        gtsAPITables: {
            products: {
                table: 'products',
                version: 1,
                type: 1,
                active: true,
                properties: {
                    fields: {
                        "id": {
                            "type": "view"
                        },
                        "name": {
                            "label": "Название товара",
                            "type": "text"
                        },
                        "tags": {
                            "label": "Теги",
                            "type": "multiple",
                            "table_link": "product_tags",
                            "table": "tags",
                            "title_field": "name",
                            "main_id": "product_id",
                            "slave_id": "tag_id"
                        }
                    }
                }
            },
            // Справочная таблица тегов
            tags: {
                table: 'tags',
                version: 1,
                type: 1,
                active: true,
                properties: {
                    fields: {
                        "id": { "type": "view" },
                        "name": {
                            "label": "Название тега",
                            "type": "text"
                        }
                    }
                }
            },
            // Таблица связей (может не иметь конфигурации в gtsAPITables)
            product_tags: {
                table: 'product_tags',
                version: 1,
                type: 1,
                active: true,
                properties: {
                    fields: {
                        "id": { "type": "view" },
                        "product_id": {
                            "label": "Товар",
                            "type": "autocomplete",
                            "table": "products"
                        },
                        "tag_id": {
                            "label": "Тег",
                            "type": "autocomplete",
                            "table": "tags"
                        }
                    }
                }
            }
        }
    }
}
```

**Структура таблицы связей:**
```sql
CREATE TABLE `product_tags` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `product_id` (`product_id`),
    KEY `tag_id` (`tag_id`)
);
```

**Поведение компонента:**

1. **Загрузка данных:**
   - При открытии поля загружаются все доступные записи из `table`
   - Загружаются текущие связи из `table_link` для данной записи
   - Автоматически отмечаются уже выбранные элементы

2. **Изменение выбора:**
   - При добавлении элемента создается новая запись в `table_link`
   - При удалении элемента удаляется соответствующая запись из `table_link`
   - Все изменения сохраняются мгновенно

3. **Валидация:**
   - Если у основной записи нет ID (новая запись), показывается ошибка
   - Нельзя сохранить связи для несохраненной записи
   - Пользователь должен сначала сохранить основную запись

**Отображение:**
- В режиме просмотра: список выбранных элементов в виде чипов
- В режиме редактирования: MultiSelect с поиском и фильтрацией
- Поддержка большого количества записей (до 1000)

**Ограничения:**
- Требуется наличие ID у основной записи для сохранения связей
- Таблица связей должна быть доступна через gtsAPI
- Максимум 1000 записей в справочной таблице (можно изменить в коде)

**Совместимость:**
- Работает в таблицах (PVTables)
- Работает в формах (PVForm)
- Работает в модальных окнах редактирования
- Поддерживает параметр `readonly` из `customFields`

**Примеры использования:**

```javascript
// Связь сотрудников с проектами
"projects": {
    "label": "Проекты",
    "type": "multiple",
    "table_link": "employee_projects",
    "table": "projects",
    "title_field": "name",
    "main_id": "employee_id",
    "slave_id": "project_id"
}

// Связь статей с категориями
"categories": {
    "label": "Категории",
    "type": "multiple",
    "table_link": "article_categories",
    "table": "categories",
    "title_field": "title",
    "main_id": "article_id",
    "slave_id": "category_id",
    "readonly": false
}

// Связь пользователей с ролями
"roles": {
    "label": "Роли",
    "type": "multiple",
    "table_link": "user_roles",
    "table": "roles",
    "title_field": "name",
    "main_id": "user_id",
    "slave_id": "role_id"
}
```

### Multiautocomplete

Новый тип поля `multiautocomplete` позволяет создавать автокомплиты с множественными полями поиска, размещенными в одном InputGroup. Подробная документация доступна в [multiautocomplete-api.md](multiautocomplete-api.md).

**Пример конфигурации:**
```javascript
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
```

**Особенности:**
- Поддержка зависимых полей поиска
- Компактное размещение в InputGroup
- Полная совместимость с обычными автокомплитами
- Поддержка всех стандартных параметров (show_id, default_row и т.д.)

## Пример простой конфигурации

```javascript
export default {
    mypackage: {
        name: 'mypackage',
        gtsAPITables: {
            users: {
                table: 'users',
                version: 1,
                type: 1,
                authenticated: true,
                groups: 'Administrator',
                active: true,
                properties: {
                    autocomplete: {
                        tpl: '{$name}',
                        where: {
                            "name:LIKE": "%query%"
                        },
                        limit: 0
                    },
                    actions: {
                        read: {},
                        create: { groups: 'Administrator' },
                        update: { groups: 'Administrator' },
                        delete: { groups: 'Administrator' }
                    },
                    fields: {
                        "id": {
                            "type": "view"
                        },
                        "name": {
                            "label": "Имя",
                            "type": "text"
                        },
                        "email": {
                            "label": "Email",
                            "type": "text"
                        },
                        "active": {
                            "label": "Активен",
                            "type": "boolean"
                        }
                    }
                }
            }
        }
    }
}
```

## Специальные возможности

### Для группированных данных
Если вам нужны таблицы с группировкой данных (GROUP BY), обратитесь к файлу `docs/use_group_gtsapipackages.md` для получения подробной информации о параметре `data_fields`.

### Для деревьев UniTree и меню PVMenu
При использовании `type: 3` доступны дополнительные настройки для работы с иерархическими структурами.

#### Компонент PVMenu

**PVMenu** - это компонент для отображения горизонтального меню сайта на основе данных из API. Компонент использует Menubar из PrimeVue для отображения иерархической структуры меню с поддержкой URL навигации.

**Требования для использования PVMenu:**
- `type: 3` - обязательно для работы с деревьями
- `properties.makeUrl: 1` - обязательно для генерации URL в поле `node.data.url`

**Пример конфигурации для меню:**
```javascript
export default {
    mypackage: {
        name: 'mypackage',
        gtsAPITables: {
            site_menu: {
                table: 'site_menu',
                version: 1,
                type: 3, // Обязательно для UniTree/PVMenu
                authenticated: false, // Меню может быть публичным
                active: true,
                properties: {
                    makeUrl: 1, // Обязательно для генерации URL
                    autocomplete: {
                        tpl: '{$title}',
                        where: {
                            "title:LIKE": "%query%",
                            "active": 1
                        },
                        limit: 0
                    },
                    fields: {
                        "id": {
                            "type": "view"
                        },
                        "title": {
                            "label": "Заголовок",
                            "type": "text"
                        },
                        "url": {
                            "label": "URL",
                            "type": "text"
                        },
                        "parent_id": {
                            "label": "Родитель",
                            "type": "autocomplete",
                            "table": "site_menu"
                        },
                        "menuindex": {
                            "label": "Порядок",
                            "type": "number"
                        },
                        "active": {
                            "label": "Активен",
                            "type": "boolean"
                        }
                    }
                }
            }
        }
    }
}
```

**Использование компонента PVMenu:**
```vue
<template>
    <div class="menu-container">
        <PVMenu 
            table="site_menu" 
            @menu-click="handleMenuClick"
        />
    </div>
</template>

<script setup>
import { PVMenu } from 'pvtables'

const handleMenuClick = (event) => {
    console.log('Клик по меню:', event.node, event.url)
}
</script>
```

**Основные возможности PVMenu:**
- Загрузка данных меню через API (тот же что и для UniTree)
- Отображение горизонтального иерархического меню с помощью Menubar
- Поддержка SVG иконок из gtsAPIUniTreeClass
- Навигация по URL (node.data.url) - внутренние и внешние ссылки
- Автоматическая фильтрация неактивных элементов (active !== 0)
- События клика по элементам меню
- Индикатор загрузки

**Структура данных узла меню:**
```javascript
{
    data: {
        id: 1,
        title: "Главная",
        url: "/",
        parent_id: 0,
        menuindex: 1,
        active: 1,
        class: "menu_item" // для иконок
    },
    children: [
        // дочерние элементы
    ]
}
```

**Поддержка иконок:**
Если в ответе API присутствует `gtsAPIUniTreeClass` с SVG иконками, они автоматически отображаются в меню:

```javascript
// В ответе API
gtsAPIUniTreeClass: {
    "menu_item": {
        "svg": "<svg>...</svg>"
    },
    "menu_category": {
        "svg": "<svg>...</svg>"
    }
}
```

**Обработка URL:**
- **Внутренние ссылки** (начинающиеся не с http): `window.location.href = url`
- **Внешние ссылки** (http/https): `window.open(url, '_blank')`

**События:**
- `@menu-click` - клик по элементу меню, передает объект с `node` и `url`

**Методы компонента:**
- `refresh()` - обновить данные меню

**Стилизация:**
Компонент использует стандартные классы PrimeVue Menubar и может быть стилизован через CSS:

```css
.pv-menu {
    width: 100%;
}

.menu-icon svg {
    width: 16px;
    height: 16px;
}
```

**Отличия от UniTree:**
- Упрощенный интерфейс без поиска, drag&drop, действий
- Фокус на навигации через URL
- Автоматическая фильтрация неактивных элементов
- Использование Menubar вместо sl-vue-tree-next
- Горизонтальное отображение для меню сайта

## Ограничения имен полей

При создании полей в gtsAPIPackages следует избегать использования зарезервированных слов и конфликтующих имен:

### Запрещенные имена полей

**JavaScript/Vue зарезервированные слова:**
- `class` - зарезервированное слово JavaScript, используйте `css_class`, `class_name`
- `function`, `var`, `let`, `const`, `sort` - JavaScript ключевые слова
- `data`, `methods`, `computed` - Vue.js зарезервированные имена

### Рекомендуемые альтернативы

```javascript
// Плохо
fields: {
    sort: { type: 'number' },        // Конфликт с JavaScript
    order: { type: 'number' },       // Зарезервированное слово
    class: { type: 'text' }          // JavaScript зарезервированное
}

// Хорошо
fields: {
    sortfield: { type: 'number' },   // Безопасная альтернатива
    order_num: { type: 'number' },   // Описательное имя
    css_class: { type: 'text' }      // Четкое назначение
}
```

### Правила именования

1. **Используйте snake_case** для имен полей: `user_id`, `created_date`
2. **Добавляйте суффиксы** для уточнения: `_id`, `_name`, `_field`, `_num`
3. **Избегайте сокращений** которые могут быть неоднозначными
4. **Проверяйте совместимость** с SQL и JavaScript

## Сортировка строк перетаскиванием (row_drag)

Включает drag-and-drop сортировку строк таблицы. Добавляет колонку-хэндлер (⠿, 28px) слева, с которой пользователь перетаскивает строки.

### Требования к таблице

- В БД должно быть числовое поле-сортировщик (обычно `sortfield`)
- Данные должны читаться с `sortby: 'sortfield ASC'`

### Конфигурация

```javascript
properties: {
    row_drag: {
        sortfield:  'sortfield',   // поле для хранения порядка (шаг 10)
        parentsort: 'raschet_id',  // группировка — строки сортируются независимо внутри группы
    },
    query: {
        sortby: 'sortfield ASC',
    },
}
```

**`sortfield`** — числовое поле. При создании строки автоматически получает `MAX(sortfield) + 10` в рамках группы `parentsort`.

**`parentsort`** — опционально. Если задан, сортировка и нумерация работают независимо для каждого значения этого поля (например, для каждого `raschet_id`).

### Что происходит

| Событие | Действие сервера |
|---------|-----------------|
| Создание строки | Устанавливает `sortfield = MAX + 10` внутри группы `parentsort` |
| Drag & drop | Вызов `sortable_reorder`: перезаписывает `sortfield` (10, 20, 30…) в новом порядке |
| «Вставить выше» | Вызов `sortable_insert_above`: сдвигает строки ≥ target на +10, создаёт новую строку |

### `sortable_insert_above` — вставка строки выше

Row-action, который вставляет новую пустую строку непосредственно перед выбранной.

```javascript
actions: {
    insert_above: {
        action: 'sortable_insert_above',
        row:    true,
        menu:   1,              // показывать в выпадающем меню строки
        icon:   'pi pi-arrow-up',
        label:  'Вставить выше',
    },
}
```

**Алгоритм на сервере:**
1. Берёт `sortfield` целевой строки (target)
2. Сдвигает все строки с `sortfield >= target.sortfield` в той же группе на +10
3. Создаёт новую строку с `sortfield = target.sortfield` и `parentsort = target.raschet_id`
4. Возвращает `{ id, sortfield }` новой строки

Фронтенд после ответа добавляет строку в таблицу и открывает её для редактирования.

> **Важно:** `row_drag` должен быть объектом (`{ sortfield, parentsort }`), а не `true`. Простое `row_drag: true` не работает с `sortable_reorder` и `sortable_insert_above`.

---

## Мобильное отображение (mobile_card)

На мобильных устройствах PVTables автоматически переключается на карточный список вместо таблицы. Поведение карточки настраивается через `properties.mobile_card`.

### Авто-карточка (без конфигурации)

Если `mobile_card` не задан, поля распределяются по ролям автоматически по именам и типам:

| Роль | Что показывается | Эвристика (field/label содержит) |
|------|-----------------|----------------------------------|
| **title** | Заголовок карточки (жирный) | `name`, `title`, `caption`, `product`, `description`, `наименование` |
| **badge** | Цветной чип-статус | тип `select` + `status`, `state`, `статус`, `состояние` |
| **subtitle** | Подзаголовок (серый) | `client`, `customer`, `date`, `article`, `sku`, `code`, `клиент`, `дата` |
| **metrics** | Числа в сетке | тип `decimal`/`number` + `sum`, `amount`, `price`, `qty`, `total`, `сумма` |
| **body** | Аккордеон «Подробнее» | все остальные поля |

### Параметры mobile_card

```javascript
properties: {
    mobile_card: {
        tpl:     `...Vue-шаблон карточки...`,   // полная замена авто-карточки
        article: `...Vue-шаблон артикула...`,   // строка артикула под подзаголовком
    }
}
```

### `mobile_card.article` — строка артикула

Отображается серым шрифтом под подзаголовком. Компилируется как Vue-шаблон.

**Доступные переменные:**
- `row` — объект строки (все поля)
- `cols` — массив колонок `[{ field, label, type, ... }]`
- `selectSettings` — словари select/autocomplete

```javascript
mobile_card: {
    article: `{{ [row.mark, [row.a_s||row.A, row.b_s||row.B].filter(Boolean).join('x'), row.L ? 'L='+row.L : '', row.metall, row.comment].filter(Boolean).join(', ') }}`
}
// Результат: "573 / 9, Ф160xФ250, L=0.1, 0,5Ц id:2, Ral 300"
```

### `mobile_card.tpl` — полный шаблон карточки

Полностью заменяет авто-карточку. Компилируется как Vue-компонент.

```javascript
mobile_card: {
    tpl: `
        <div>
            <div class="font-bold text-sm">{{ row.name }}</div>
            <div class="text-xs text-gray-500">{{ row.client }} · {{ row.order_date }}</div>
        </div>
    `
}
```

> **Важно:** При изменении `mobile_card` необходимо увеличить `version` таблицы, чтобы конфигурация обновилась на сервере.

---

## Рекомендации

1. **Всегда увеличивайте версию** при изменении конфигурации
2. **Используйте осмысленные названия** для таблиц и полей
3. **Избегайте зарезервированных слов** при именовании полей
4. **Настраивайте права доступа** через groups и permissions
5. **Тестируйте конфигурацию** после каждого изменения
6. **Документируйте изменения** в комментариях

## Совместимость

Конфигурация gtsAPIPackages полностью совместима с системой MODX и поддерживает все стандартные типы полей и операции.
