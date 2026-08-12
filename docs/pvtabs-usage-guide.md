# Руководство по использованию компонента PVTabs

## Обзор

`PVTabs` - это универсальный компонент вкладок из библиотеки PVTables, который позволяет организовать различные типы контента в табличном интерфейсе. Компонент поддерживает множество типов вкладок: таблицы, формы, деревья, галереи файлов и пользовательские компоненты.

## Импорт

```javascript
import { PVTabs } from 'pvtables/dist/pvtables'
```

## Основное использование

```vue
<template>
  <PVTabs 
    :tabs="tabs"
    :actions="actions"
    :filters="filters"
    :current_id="currentId"
    :parent_row="parentRow"
    @refresh-table="handleRefresh"
    @get-response="handleResponse"
  />
</template>

<script setup>
import { PVTabs } from 'pvtables/dist/pvtables'
import { ref } from 'vue'

const tabs = ref({
  tab1: {
    title: 'Основная информация',
    type: 'form',
    table: 'users'
  },
  tab2: {
    title: 'Связанные данные',
    type: 'table',
    table: 'orders'
  }
})

const actions = ref({})
const filters = ref({})
const currentId = ref(1)
const parentRow = ref({})

const handleRefresh = () => {
  console.log('Таблица обновлена')
}

const handleResponse = (event) => {
  console.log('Получен ответ:', event)
}
</script>
```

## Свойства (Props)

### tabs
- **Тип:** `Object`
- **Обязательно:** Да
- **Описание:** Объект с конфигурацией вкладок. Каждый ключ - это уникальный идентификатор вкладки.

### actions
- **Тип:** `Object`
- **По умолчанию:** `{}`
- **Описание:** Объект с действиями для таблиц внутри вкладок.

### filters
- **Тип:** `Object`
- **По умолчанию:** `{}`
- **Описание:** Объект с фильтрами для каждой вкладки. Ключи должны соответствовать ключам вкладок.

### parent_row
- **Тип:** `Object`
- **По умолчанию:** `{}`
- **Описание:** Данные родительской строки для условного отображения вкладок.

### current_id
- **Тип:** `Number | String`
- **По умолчанию:** `0`
- **Описание:** ID текущей записи для форм и связанных данных.

### child
- **Тип:** `Boolean`
- **По умолчанию:** `false`
- **Описание:** Флаг, указывающий что компонент является дочерним.

### class_key
- **Тип:** `String`
- **По умолчанию:** `''`
- **Описание:** Ключ класса для галереи файлов.

## События (Events)

### @refresh-table
Вызывается при необходимости обновления таблицы.

```vue
<PVTabs @refresh-table="handleRefresh" />
```

### @get-response
Вызывается при получении ответа от дочерних компонентов.

```vue
<PVTabs @get-response="handleResponse" />
```

### @select-treenode
Вызывается при выборе узла в дереве.

```vue
<PVTabs @select-treenode="handleTreeNodeSelect" />
```

### @update-treenode-title
Вызывается при обновлении заголовка узла дерева.

```vue
<PVTabs @update-treenode-title="handleTreeNodeUpdate" />
```

### @select-file
Вызывается при выборе файла в файловом дереве.

```vue
<PVTabs @select-file="handleFileSelect" />
```

## Типы вкладок

### 1. Таблица (table)

Стандартная вкладка с таблицей PVTables.

```javascript
const tabs = ref({
  orders: {
    title: 'Заказы',
    type: 'table',
    table: 'orders',
    active: true  // Активна по умолчанию
  }
})
```

### 2. Форма (form)

Вкладка с формой редактирования.

```javascript
const tabs = ref({
  userForm: {
    title: 'Редактирование',
    type: 'form',
    table: 'users'
  }
})
```

### 3. Дерево (tree)

Вкладка с древовидной структурой.

```javascript
const tabs = ref({
  categories: {
    title: 'Категории',
    type: 'tree',
    table: 'categories',
    dragable: true  // Разрешить перетаскивание
  }
})
```

### 4. Файловое дерево (filetree)

Вкладка с деревом файлов.

```javascript
const tabs = ref({
  files: {
    title: 'Файлы',
    type: 'filetree',
    mediaSources: ['filesystem', 'images']
  }
})
```

### 5. Содержимое файла (filecontent)

Вкладка для отображения содержимого файла.

```javascript
const tabs = ref({
  fileView: {
    title: 'Просмотр файла',
    type: 'filecontent',
    file: '/path/to/file.txt',
    content: 'Содержимое файла',
    mime: 'text/plain',
    mediaSources: ['filesystem']
  }
})
```

### 6. Галерея файлов (file-gallery)

Вкладка с галереей файлов.

```javascript
const tabs = ref({
  gallery: {
    title: 'Галерея',
    type: 'file-gallery',
    list_name: 'product_images'
  }
})
```

**Параметры галереи:**
- `list_name` - имя списка файлов
- Автоматически использует `current_id` и `class_key` из props
- Поддерживает загрузку, редактирование и удаление файлов
- Встроенные фильтры
- Пагинация (20 элементов на страницу)

### 7. Пользовательский компонент (component)

Вкладка с пользовательским Vue компонентом.

```javascript
const tabs = ref({
  custom: {
    title: 'Пользовательский',
    type: 'component',
    name_component: 'MyCustomComponent'
  }
})
```

**Важно:** Компонент должен быть глобально зарегистрирован или импортирован.

#### Обязательные пропсы для пользовательского компонента

При использовании типа `component`, PVTabs автоматически передает следующие пропсы в ваш компонент:

- **parent_row** (Object) - данные родительской строки
- **parent-id** (Number | String) - ID текущей записи (значение из `current_id`)
- **filters** (Object) - фильтры для данной вкладки (из `filters[tab.key]`)

**Пример определения пропсов в пользовательском компоненте:**

```vue
<script setup>
const props = defineProps({
  parent_row: {
    type: Object,
    required: true,
    default: () => ({})
  },
  parentId: {
    type: [Number, String],
    required: true,
    default: 0
  },
  filters: {
    type: Object,
    required: false,
    default: () => ({})
  }
})

// Использование пропсов
console.log('Родительская строка:', props.parent_row)
console.log('ID родителя:', props.parentId)
console.log('Фильтры:', props.filters)
</script>
```

**Полный пример пользовательского компонента:**

```vue
<template>
  <div class="custom-component">
    <h3>Пользовательский компонент</h3>
    <p>ID родителя: {{ parentId }}</p>
    <p>Данные родителя: {{ parent_row }}</p>
    <div v-if="filters">
      <h4>Активные фильтры:</h4>
      <pre>{{ filters }}</pre>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  parent_row: {
    type: Object,
    required: true,
    default: () => ({})
  },
  parentId: {
    type: [Number, String],
    required: true,
    default: 0
  },
  filters: {
    type: Object,
    required: false,
    default: () => ({})
  }
})

// Вычисляемые свойства на основе пропсов
const hasFilters = computed(() => {
  return Object.keys(props.filters).length > 0
})
</script>

<style scoped>
.custom-component {
  padding: 1rem;
}
</style>
```

### 8. Множественные таблицы (tables)

Вкладка с несколькими таблицами.

```javascript
const tabs = ref({
  multiTables: {
    title: 'Связанные данные',
    type: 'tables',
    tables: {
      orders: {
        table: 'orders'
      },
      payments: {
        table: 'payments'
      }
    }
  }
})
```

## Условное отображение вкладок

Вкладки могут отображаться условно на основе данных родительской строки с помощью параметра `parent_if`.

```javascript
const tabs = ref({
  specialTab: {
    title: 'Специальная вкладка',
    type: 'table',
    table: 'special_data',
    parent_if: {
      status: 'active',
      type: 'premium'
    }
  }
})

const parentRow = ref({
  status: 'active',
  type: 'premium'
})
```

Вкладка `specialTab` будет отображаться только если:
- `parent_row.status === 'active'`
- `parent_row.type === 'premium'`

## Фильтры для вкладок

Каждая вкладка может иметь свои фильтры:

```javascript
const filters = ref({
  orders: {
    status: 'active',
    date_from: '2024-01-01'
  },
  payments: {
    paid: true
  }
})
```

Ключи в объекте `filters` должны соответствовать ключам вкладок.

## Методы компонента

### refresh(from_parent, table)

Обновляет содержимое вкладок.

```javascript
import { ref } from 'vue'

const tabsRef = ref()

// Обновить все вкладки
tabsRef.value.refresh(true)

// Обновить конкретную таблицу
tabsRef.value.refresh(true, 'orders')
```

**Параметры:**
- `from_parent` (Boolean) - обновление от родителя
- `table` (String) - ключ конкретной таблицы для обновления

## Полный пример использования

```vue
<template>
  <div class="tabs-container">
    <PVTabs 
      ref="tabsRef"
      :tabs="tabs"
      :actions="actions"
      :filters="filters"
      :current_id="currentId"
      :parent_row="parentRow"
      :class_key="classKey"
      @refresh-table="handleRefresh"
      @get-response="handleResponse"
      @select-treenode="handleTreeNodeSelect"
      @select-file="handleFileSelect"
    />
  </div>
</template>

<script setup>
import { PVTabs } from 'pvtables/dist/pvtables'
import { ref, onMounted } from 'vue'

const tabsRef = ref()
const currentId = ref(1)
const classKey = ref('Product')

const tabs = ref({
  info: {
    title: 'Основная информация',
    type: 'form',
    table: 'products',
    active: true
  },
  images: {
    title: 'Изображения',
    type: 'file-gallery',
    list_name: 'product_images'
  },
  orders: {
    title: 'Заказы',
    type: 'table',
    table: 'orders'
  },
  categories: {
    title: 'Категории',
    type: 'tree',
    table: 'categories',
    dragable: true
  },
  analytics: {
    title: 'Аналитика',
    type: 'component',
    name_component: 'ProductAnalytics',
    parent_if: {
      status: 'published'
    }
  }
})

const actions = ref({
  create: {
    label: 'Создать',
    icon: 'pi pi-plus'
  },
  edit: {
    label: 'Редактировать',
    icon: 'pi pi-pencil'
  },
  delete: {
    label: 'Удалить',
    icon: 'pi pi-trash'
  }
})

const filters = ref({
  orders: {
    product_id: currentId.value,
    status: 'active'
  }
})

const parentRow = ref({
  status: 'published',
  type: 'physical'
})

const handleRefresh = () => {
  console.log('Обновление таблицы')
  // Дополнительная логика обновления
}

const handleResponse = (event) => {
  console.log('Получен ответ:', event)
  // Обработка ответа
}

const handleTreeNodeSelect = (event) => {
  console.log('Выбран узел дерева:', event)
  // Обработка выбора узла
}

const handleFileSelect = (event) => {
  console.log('Выбран файл:', event)
  // Обработка выбора файла
}

onMounted(() => {
  // Инициализация при монтировании
  console.log('PVTabs смонтирован')
})

// Пример программного обновления
const refreshAllTabs = () => {
  tabsRef.value.refresh(true)
}

const refreshSpecificTab = (tabKey) => {
  tabsRef.value.refresh(true, tabKey)
}
</script>

<style scoped>
.tabs-container {
  padding: 1rem;
}
</style>
```

## Интеграция с другими компонентами

### С PVTables

```javascript
const tabs = ref({
  mainTable: {
    title: 'Основная таблица',
    type: 'table',
    table: 'products'
  }
})
```

### С PVForm

```javascript
const tabs = ref({
  editForm: {
    title: 'Редактирование',
    type: 'form',
    table: 'products'
  }
})
```

### С FileGallery

```javascript
const tabs = ref({
  gallery: {
    title: 'Галерея',
    type: 'file-gallery',
    list_name: 'images'
  }
})
```

## Обработка ошибок

Компонент автоматически обрабатывает ошибки при загрузке динамических компонентов:

```javascript
// Если компонент не найден, в консоль выводится ошибка:
// "PVTabs: Component "ComponentName" not found. Make sure it's globally registered or imported."
```

## Рекомендации по использованию

### 1. Организация вкладок
- Группируйте связанные данные в одной вкладке
- Используйте понятные названия вкладок
- Активируйте наиболее важную вкладку по умолчанию с помощью `active: true`

### 2. Производительность
- Используйте условное отображение (`parent_if`) для скрытия ненужных вкладок
- Не загружайте все данные сразу, используйте ленивую загрузку
- Применяйте фильтры для ограничения объема данных

### 3. Фильтры
- Определяйте фильтры для каждой вкладки отдельно
- Используйте реактивные фильтры для динамического обновления
- Связывайте фильтры с `current_id` для отображения связанных данных

### 4. События
- Обрабатывайте события для синхронизации данных между вкладками
- Используйте `@refresh-table` для обновления связанных данных
- Реагируйте на `@get-response` для обработки результатов операций

### 5. Пользовательские компоненты
- Регистрируйте компоненты глобально или импортируйте их
- Передавайте необходимые данные через `parent_row` и `current_id`
- Обрабатывайте ошибки загрузки компонентов

## Примеры использования в реальных сценариях

### Карточка товара

```javascript
const tabs = ref({
  general: {
    title: 'Общие данные',
    type: 'form',
    table: 'products',
    active: true
  },
  images: {
    title: 'Изображения',
    type: 'file-gallery',
    list_name: 'product_images'
  },
  prices: {
    title: 'Цены',
    type: 'table',
    table: 'product_prices'
  },
  stock: {
    title: 'Остатки',
    type: 'table',
    table: 'product_stock'
  }
})
```

### Управление пользователями

```javascript
const tabs = ref({
  profile: {
    title: 'Профиль',
    type: 'form',
    table: 'users',
    active: true
  },
  orders: {
    title: 'Заказы',
    type: 'table',
    table: 'orders'
  },
  permissions: {
    title: 'Права доступа',
    type: 'tree',
    table: 'permissions',
    dragable: false
  },
  activity: {
    title: 'Активность',
    type: 'component',
    name_component: 'UserActivity'
  }
})
```

### Файловый менеджер

```javascript
const tabs = ref({
  tree: {
    title: 'Структура',
    type: 'filetree',
    mediaSources: ['filesystem', 'images', 'documents'],
    active: true
  },
  content: {
    title: 'Содержимое',
    type: 'filecontent',
    file: selectedFile.value,
    content: fileContent.value,
    mime: fileMime.value,
    mediaSources: ['filesystem']
  }
})
```

## Заключение

Компонент `PVTabs` предоставляет мощный и гибкий способ организации различных типов контента в табличном интерфейсе. Благодаря поддержке множества типов вкладок, условного отображения и событий, он может быть адаптирован под любые требования вашего приложения.
