# Создание таблицы на основе PVTables и gtsAPI

## Описание системы

Данный проект использует Vue 3 с компонентом PVTables для создания интерактивных таблиц, которые работают с gtsAPI для управления данными. Система предоставляет полнофункциональный CRUD интерфейс с поддержкой фильтрации, сортировки, автокомплита и подтаблиц.

## Архитектура

### Основные компоненты:
- **PVTables** - Vue компонент для отображения таблиц (версия 0.1.7)
- **gtsAPI** - API для работы с данными
- **Конфигурационные файлы** - настройки таблиц и полей

### Структура проекта:
```
src/
├── App.vue              # Основной компонент
├── main.js              # Точка входа приложения
└── style.css            # Стили

_build/configs/
├── gtsapipackages.js    # Конфигурация таблиц и API
├── settings.js          # Настройки системы
├── gtsapirules.js       # Правила доступа
└── data.js              # Данные

```

## Пример использования

### 1. Основной компонент (App.vue)

```vue
<template>
  <PVTables table="gsRaschets" :actions="actions" ref="childComponentRef"/>
</template>

<script setup>
import {PVTables} from 'pvtables/dist/pvtables'
import { ref } from 'vue';

const childComponentRef = ref()

const actions = ref({
  // Пример внешнего действия создания
  // create:{
  //   row:true,
  //   icon:"pi pi-trash",
  //   class:"p-button-rounded p-button-danger",
  //   click: (data,columns) => {
  //     childComponentRef.value.refresh();
  //   }
  // }
})
</script>
```

**ВАЖНО:** Внешние действия (actions) автоматически генерируются компонентом PVTables в виде кнопок в интерфейсе таблицы. Не нужно создавать дополнительные кнопки в шаблоне - они будут отображаться автоматически на основе конфигурации actions.

### 3. Конфигурация таблицы (gtsapipackages.js)

Основная структура конфигурации на примере пакета gsraschets:

```javascript
export default {
    gsraschets: {  // packageName
        name: 'gsraschets',
        gtsAPITables: {
            gsRaschets: {  // tableName
                table: 'gsRaschets',
                class: 'gsRaschet',
                autocomplete_field: '',
                version: 22,
                authenticated: true,
                groups: 'Administrator,itr,manager',
                permitions: '',
                active: true,
                properties: {
                    actions: {
                        read: {},
                        create: {},
                        update: {},
                        subtabs: {
                            test: {
                                gsDocOrderLink: {
                                    title: "Документы",
                                    table: "gsDocOrderLink",
                                    where: {
                                        "type_order_id": 3,
                                        "order_id": "id"
                                    }
                                },
                                OrgsContact: {
                                    title: "Контакты",
                                    table: "OrgsContact",
                                    where: {
                                        "OrgsContactLink.org_id": "org_id"
                                    }
                                },
                                family: {
                                    title: "Семья расчетов",
                                    table: "gsRaschets",
                                    where: {
                                        "family_id": "family_id",
                                        "last": 0
                                    }
                                }
                            }
                        }
                    },
                    query: {
                        where: {
                            "gsRaschet.last": 1
                        }
                    },
                    filters: {
                        period_id: {
                            label: "Период",
                            type: "autocomplete",
                            table: "gsPeriod",
                            default_row: {
                                active: 1
                            }
                        }
                    },
                    fields: {
                        // Конфигурация полей
                    }
                }
            }
        }
    },
    organizations: {  // Другой packageName
        name: 'organizations',
        gtsAPITables: {
            OrgsContact: {
                // Конфигурация таблицы контактов
            }
        }
    }
}
```

## Типы полей

### Основные типы из примера gsRaschets:
- **view** - только для просмотра
- **text** - текстовое поле
- **textarea** - многострочный текст
- **number** - числовое поле
- **decimal** - десятичное число с настройкой FractionDigits
- **date** - поле даты
- **boolean** - логическое поле
- **select** - выпадающий список с select_data
- **autocomplete** - автокомплит с привязкой к таблице
- **html** - HTML контент с шаблоном (tpl)
- **Email** - поле email

### Пример конфигурации полей из gsRaschets:

```javascript
fields: {
    "id": {
        "type": "view",
        "class": "gsRaschet"
    },
    "period_id": {
        "type": "autocomplete",
        "class": "gsRaschet",
        "table": "gsPeriod",
        "default_row": {
            "active": 1
        }
    },
    "name": {
        "label": "Имя",
        "type": "text",
        "class": "gsRaschet"
    },
    "index": {
        "label": "Индекс",
        "type": "text",
        "class": "gsRaschet"
    },
    "date": {
        "label": "Дата расчета",
        "type": "date",
        "readonly": 1,
        "class": "gsRaschet"
    },
    "org_id": {
        "label": "Контрагент",
        "type": "autocomplete",
        "readonly": 1,
        "table": "Orgs",
        "class": "gsRaschet"
    },
    "created_by": {
        "label": "Создан",
        "type": "autocomplete",
        "readonly": 1,
        "table": "modUser",
        "class": "gsRaschet"
    },
    "discount": {
        "label": "Скидка",
        "type": "decimal",
        "readonly": 1,
        "FractionDigits": 2,
        "class": "gsRaschet"
    },
    "cost": {
        "label": "Стоимость",
        "type": "decimal",
        "readonly": 1,
        "FractionDigits": 2,
        "class": "gsRaschet"
    },
    "status_id": {
        "label": "Статус",
        "type": "select",
        "select_data": [
            {"id": 1, "content": "Согласование"},
            {"id": 3, "content": "Перерасчет"},
            {"id": 4, "content": "Заказ"},
            {"id": 5, "content": "Отказ"}
        ],
        "class": "gsRaschet"
    },
    "comment": {
        "label": "Примечание",
        "type": "textarea",
        "class": "gsRaschet"
    },
    "excel_id": {
        "label": "Заказ",
        "type": "number",
        "table_only": 1,
        "readonly": 1,
        "class": "gsRaschet"
    },
    "link": {
        "label": "Ссылка",
        "type": "html",
        "tpl": "<a target=\"_blank\" href=\"{'gtsshop_p_admin_manager_raschet' | option | url : [] : [\"raschet_id\"=>$id]}\">Расчет</a>",
        "table_only": 1,
        "class": "gsRaschet"
    }
}
```

## Настройки полей

### Основные параметры:
- **label** - подпись поля
- **type** - тип поля
- **class** - CSS класс или класс модели (например, "gsRaschet")
- **readonly** - только для чтения
- **table_only** - отображать только в таблице
- **table** - связанная таблица для autocomplete
- **default_row** - условия по умолчанию
- **FractionDigits** - количество знаков после запятой
- **select_data** - данные для select
- **tpl** - шаблон для html полей

## Действия (Actions)

### Доступные действия:
- **read** - чтение данных
- **create** - создание записей
- **update** - обновление записей
- **delete** - удаление записей

### Пример настройки действий из gsRaschets:

```javascript
actions: {
    read: {},
    create: {},
    update: {},
    subtabs: {
        test: {
            gsDocOrderLink: {
                title: "Документы",
                table: "gsDocOrderLink",
                where: {
                    "type_order_id": 3,
                    "order_id": "id"
                }
            },
            OrgsContact: {
                title: "Контакты",
                table: "OrgsContact",
                where: {
                    "OrgsContactLink.org_id": "org_id"
                }
            },
            family: {
                title: "Семья расчетов",
                table: "gsRaschets",
                where: {
                    "family_id": "family_id",
                    "last": 0
                }
            }
        }
    }
}
```

## Фильтры

Пример из gsRaschets:

```javascript
filters: {
    period_id: {
        label: "Период",
        type: "autocomplete",
        table: "gsPeriod",
        default_row: {
            active: 1
        }
    }
}
```

## Автокомплит

Пример из OrgsContract:

```javascript
autocomplete: {
    "select": ["id", "name"],
    "where": {
        "name:LIKE": "%query%",
        "org_id": "org_id"
    },
    "tpl": "{$name}",
    "limit": 20
}
```

## Подтаблицы (Subtabs)

Пример из gsRaschets:

```javascript
subtabs: {
    test: {
        gsDocOrderLink: {
            title: "Документы",
            table: "gsDocOrderLink",
            where: {
                "type_order_id": 3,
                "order_id": "id"
            }
        },
        OrgsContact: {
            title: "Контакты",
            table: "OrgsContact",
            where: {
                "OrgsContactLink.org_id": "org_id"
            }
        },
        family: {
            title: "Семья расчетов",
            table: "gsRaschets",
            where: {
                "family_id": "family_id",
                "last": 0
            }
        }
    }
}
```

## Запросы (Query)

Пример из OrgsContact:

```javascript
query: {
    class: 'OrgsContact',
    leftJoin: {
        OrgsContactLink: {
            class: "OrgsContactLink",
            on: "OrgsContact.id = OrgsContactLink.contact_id"
        }
    },
    select: {
        OrgsContact: "*",
        OrgsContactLink: "OrgsContactLink.org_id,OrgsContactLink.default"
    },
    sortby: {
        "id": "ASC"
    }
}
```

## Пошаговое создание новой таблицы

### Шаг 1: Добавить конфигурацию в gtsapipackages.js

```javascript
export default {
    gsraschets: {  // Существующий пакет
        name: 'gsraschets',
        gtsAPITables: {
            gsRaschets: {
                // Существующая конфигурация
            },
            newTable: {  // Новая таблица в существующем пакете
                table: 'newTable',
                class: 'NewClass',
                version: 1,
                authenticated: true,
                groups: 'Administrator',
                active: true,
                properties: {
                    actions: {
                        read: {},
                        create: {},
                        update: {}
                    },
                    fields: {
                        "id": {
                            "type": "view",
                            "class": "NewClass"
                        },
                        "name": {
                            "label": "Название",
                            "type": "text",
                            "class": "NewClass"
                        }
                    }
                }
            }
        }
    },
    newPackage: {  // Новый пакет
        name: 'newPackage',
        gtsAPITables: {
            anotherTable: {
                table: 'anotherTable',
                class: 'AnotherClass',
                version: 1,
                authenticated: true,
                groups: 'Administrator',
                active: true,
                properties: {
                    actions: {
                        read: {}
                    },
                    fields: {
                        "id": {
                            "type": "view",
                            "class": "AnotherClass"
                        }
                    }
                }
            }
        }
    }
}
```

### Шаг 2: Создать Vue компонент

```vue
<template>
  <PVTables table="newTable" :actions="actions" ref="tableRef"/>
</template>

<script setup>
import {PVTables} from 'pvtables/dist/pvtables'
import { ref } from 'vue';

const tableRef = ref()
const actions = ref({})
</script>
```

## Особенности системы

1. **Пакетная структура**: Таблицы группируются в пакеты (например, gsraschets, organizations)
2. **Аутентификация**: Поддержка групп пользователей и разрешений
3. **Версионность**: Контроль версий конфигурации таблиц
4. **Автокомплит**: Интеллектуальный поиск по связанным таблицам
5. **Подтаблицы**: Отображение связанных данных в отдельных вкладках
6. **Фильтрация**: Гибкая система фильтров
7. **Шаблоны**: HTML шаблоны для кастомного отображения

## Рекомендации

1. Всегда указывайте версию конфигурации
2. Используйте осмысленные имена для полей и таблиц
3. Настраивайте права доступа через groups и permissions
4. Применяйте readonly для вычисляемых полей
5. Используйте table_only для полей, которые не нужны в формах
6. Настраивайте автокомплит для улучшения UX
7. Группируйте связанные данные в подтаблицы
8. Следуйте структуре пакетов для организации таблиц
9. Используйте правильные классы моделей в конфигурации полей

Эта система предоставляет мощный и гибкий инструмент для создания административных интерфейсов с минимальным количеством кода.
