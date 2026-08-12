# Руководство по модульной системе gtsCraft

## Обзор системы

Модульная система gtsCraft представляет собой автоматическую систему загрузки Vue.js компонентов, которая позволяет легко добавлять новые модули без изменения основного кода приложения. Система работает как в режиме разработки, так и в production сборке.

## Архитектура системы

### Основные компоненты

1. **src/App.vue** - главный компонент приложения
2. **src/modules/index.js** - автоматически генерируемый файл экспорта модулей
3. **src/modules/generate-index.js** - скрипт автоматической генерации индекса
4. **src/modules/*.vue** - файлы модулей
5. **src/modules/README.md** - документация модульной системы

### Принцип работы

Система использует динамический импорт Vue.js компонентов и автоматическую генерацию индексного файла для обеспечения модульности приложения.

## Структура главного компонента (App.vue)

```vue
<template>
    <!-- Селектор модуля (показывается если модуль не задан) -->
    <div v-if="ModuleNotSeted" class="card flex justify-center">
        <Select 
            v-model="Module" 
            :options="Modules" 
            optionLabel="module" 
            placeholder="Выберите модуль" 
            class="w-full md:w-56" 
        />
    </div>
    
    <!-- Динамический компонент выбранного модуля -->
    <component 
        v-if="Module && Module.module && modules[Module.module]" 
        :is="modules[Module.module]"
    />
</template>

<script setup>
    import { Select } from 'pvtables/dist/pvtables'
    import { ref } from 'vue'
    import { modules, modulesList } from './modules/index.js'
    
    // Реактивные переменные
    const Module = ref({module:'Naryad'})  // Модуль по умолчанию
    const Modules = ref(modulesList)       // Список доступных модулей
    const ModuleNotSeted = ref(true)       // Флаг отображения селектора
    
    // Автоматическая конфигурация модуля
    if(typeof gtscraftConfigs !== 'undefined' && gtscraftConfigs && gtscraftConfigs.module){
        if(modules[gtscraftConfigs.module]) {
            Module.value = gtscraftConfigs
            ModuleNotSeted.value = false
        }
    }
</script>
```

### Ключевые особенности App.vue

1. **Условное отображение селектора** - селектор модуля показывается только если модуль не задан автоматически
2. **Динамический компонент** - использует `<component :is="">` для рендеринга выбранного модуля
3. **Внешняя конфигурация** - поддерживает автоматический выбор модуля через глобальную переменную `gtscraftConfigs`
4. **Безопасная проверка** - проверяет существование модуля перед его загрузкой

## Структура модульной системы

### Файл src/modules/index.js (автогенерируемый)

```javascript
// Автоматический экспорт всех модулей
import Naryad from './Naryad.vue'
import Smens from './Smens.vue'
import OrderNaryads from './OrderNaryads.vue'

export const modules = {
    Naryad,
    Smens,
    OrderNaryads,
}

// Список доступных модулей для селекта
export const modulesList = Object.keys(modules).map(name => ({ module: name }))
```

### Скрипт генерации src/modules/generate-index.js

```javascript
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

// Получаем текущую директорию для ES модулей
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Получаем все .vue файлы в папке modules
const modulesDir = __dirname;
const files = fs.readdirSync(modulesDir)
  .filter(file => file.endsWith('.vue'))
  .map(file => file.replace('.vue', ''));

// Генерируем содержимое index.js
const imports = files.map(name => `import ${name} from './${name}.vue'`).join('\n');
const exports = files.map(name => `    ${name}`).join(',\n');

const content = `// Автоматический экспорт всех модулей
${imports}

export const modules = {
${exports}
}

// Список доступных модулей для селекта
export const modulesList = Object.keys(modules).map(name => ({ module: name }))
`;

// Записываем файл
fs.writeFileSync(path.join(modulesDir, 'index.js'), content);
console.log('index.js обновлен с модулями:', files.join(', '));
```

## Создание нового модуля

### Шаг 1: Создание файла модуля

Создайте новый `.vue` файл в папке `src/modules/`. Например, `MyNewModule.vue`:

```vue
<template>
  <div class="my-new-module">
    <h1>Мой новый модуль</h1>
    
    <!-- Ваш контент модуля -->
    <PVTables 
      table="myTable" 
      :actions="actions"
      :filters="filters"
    />
  </div>
</template>

<script setup>
import { PVTables } from 'pvtables/dist/pvtables'
import { ref } from 'vue'

// Реактивные данные модуля
const actions = ref({})
const filters = ref({})

// Логика модуля
</script>

<style scoped>
.my-new-module {
  padding: 1rem;
}
</style>
```

### Шаг 2: Обновление индекса модулей

Выполните команду для автоматического обновления индекса:

```bash
npm run modules:update
```

Эта команда:
1. Сканирует папку `src/modules/` на наличие `.vue` файлов
2. Автоматически генерирует импорты в `src/modules/index.js`
3. Обновляет список доступных модулей

### Шаг 3: Проверка работы

После выполнения команды ваш модуль автоматически станет доступен в селекторе модулей приложения.

## Структура типового модуля

### Базовая структура

```vue
<template>
  <div class="module-name">
    <!-- Секция фильтров (опционально) -->
    <div class="filters-section mb-4 p-4 border rounded" v-if="hasFilters">
      <h3 class="text-lg font-semibold mb-4">Фильтры</h3>
      <PVForm 
        v-model="filters"
        :columns="filterColumns"
        :autocompleteSettings="autocompleteSettings"
        :inline="true"
        @set-value="applyFilters"
      />
      <div class="flex gap-2 mt-4">
        <button @click="applyFilters" class="btn-primary">
          Применить фильтры
        </button>
        <button @click="clearFilters" class="btn-secondary">
          Очистить
        </button>
      </div>
    </div>

    <!-- Основная таблица -->
    <PVTables 
      :table="tableName" 
      :actions="actions"
      :filters="appliedFilters"
      ref="tableRef"
    />
  </div>
</template>

<script setup>
import { PVTables, PVForm } from 'pvtables/dist/pvtables'
import { ref, computed, onMounted } from 'vue'

// Конфигурация модуля
const tableName = 'yourTableName'
const hasFilters = true

// Реактивные данные
const tableRef = ref()
const filters = ref({})
const appliedFilters = ref({})

// Конфигурация фильтров
const filterColumns = ref([
  {
    field: 'field_name',
    label: 'Поле',
    type: 'autocomplete',
    table: 'referenceTable'
  }
])

// Настройки автокомплита
const autocompleteSettings = ref({})

// Действия таблицы
const actions = ref({
  // Кастомные действия
})

// Методы
const applyFilters = () => {
  // Логика применения фильтров
}

const clearFilters = () => {
  // Логика очистки фильтров
}

// Хуки жизненного цикла
onMounted(() => {
  // Инициализация модуля
})
</script>

<style scoped>
.module-name {
  padding: 1rem;
}

.filters-section {
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
}

.btn-primary {
  @apply px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600;
}

.btn-secondary {
  @apply px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600;
}
</style>
```

## Внешняя конфигурация модулей

### Автоматический выбор модуля

Для автоматического выбора модуля при загрузке страницы определите глобальную переменную:

```javascript
window.gtscraftConfigs = {
    module: 'Naryad' // имя модуля
}
```

### Интеграция с MODX

**ВАЖНО:** Модули вызываются на странице через сниппет mixVue с указанием конфигурации:

```modx
{'!mixVue' | snippet : [
    'app'=>'packageName',
    'config'=>[
        'module'=>'moduleName'
    ]
]}
```

Пример для модуля matNaryads:
```modx
{'!mixVue' | snippet : [
    'app'=>'Smena',
    'config'=>[
        'module'=>'matNaryads'
    ]
]}
```

**ВАЖНО:** В App.vue используется переменная `packageNameConfigs` (например, `smenaConfigs`), а не `gtscraftConfigs`. Это обеспечивает универсальность системы для разных пакетов.

```javascript
// Автоматическая конфигурация модуля
if(typeof smenaConfigs !== 'undefined' && smenaConfigs && smenaConfigs.module){
  if(modules[smenaConfigs.module]) {
    Module.value = smenaConfigs
    ModuleNotSeted.value = false
  }
}
```

## Интеграция с PVTables

### Основные компоненты

Модули используют компоненты из библиотеки PVTables:

1. **PVTables** - основной компонент таблицы
2. **PVForm** - компонент формы для фильтров
3. **Select** - компонент селектора

### Конфигурация таблиц

```javascript
// Базовая конфигурация таблицы
const tableConfig = {
  table: 'tableName',           // Имя таблицы
  actions: actions.value,       // Действия таблицы
  filters: appliedFilters.value // Фильтры
}

// Конфигурация фильтров
const filterColumns = [
  {
    field: 'field_name',         // Имя поля
    label: 'Метка поля',         // Отображаемое название
    type: 'autocomplete',        // Тип поля
    table: 'referenceTable',     // Справочная таблица
    class: 'ClassName',          // Класс для JOIN
    as: 'alias'                  // Алиас для поля
  }
]
```

## Система команд NPM

### Доступные команды

```json
{
  "scripts": {
    "dev": "vite",                                    // Запуск dev сервера
    "build": "vite build && node ./_build/upconfig.js", // Сборка проекта
    "copy": "node ./_build/copy.js",                  // Копирование файлов
    "get_token": "node ./_build/get_token.js",        // Получение токена
    "upconfig": "node ./_build/upconfig.js",          // Обновление конфигурации
    "preview": "vite preview",                        // Предпросмотр сборки
    "modules:update": "node ./src/modules/generate-index.js" // Обновление модулей
  }
}
```

### Команда обновления модулей

```bash
npm run modules:update
```

Эта команда автоматически:
- Сканирует папку `src/modules/`
- Находит все `.vue` файлы
- Генерирует импорты и экспорты
- Обновляет `src/modules/index.js`
- Выводит список обновленных модулей

## Преимущества модульной системы

### ✅ Автоматизация
- Автоматическое обнаружение новых модулей
- Автоматическая генерация индексного файла
- Минимальный boilerplate код

### ✅ Совместимость
- Работает в development и production режимах
- Совместимость с Vite сборщиком
- Поддержка ES модулей

### ✅ Безопасность
- Безопасная обработка отсутствующих переменных
- Проверка существования модулей
- Graceful fallback при ошибках

### ✅ Гибкость
- Простое добавление новых модулей
- Внешняя конфигурация через глобальные переменные
- Поддержка различных типов модулей

## Рекомендации по разработке

### Именование модулей
- Используйте PascalCase для имен файлов модулей
- Имя файла должно соответствовать назначению модуля
- Избегайте специальных символов в именах

### Структура кода
- Следуйте единой структуре для всех модулей
- Используйте композиционный API Vue 3
- Выносите общую логику в отдельные composables

### Стилизация
- Используйте scoped стили для изоляции CSS
- Применяйте Tailwind CSS классы для единообразия
- Создавайте переиспользуемые CSS классы

### Производительность
- Используйте lazy loading для больших модулей
- Оптимизируйте запросы к API
- Применяйте мемоизацию для тяжелых вычислений

## Отладка и тестирование

### Проверка загрузки модулей
```javascript
// В консоли браузера
console.log(window.modules) // Список загруженных модулей
console.log(window.modulesList) // Список для селектора
```

### Отладка автоконфигурации
```javascript
// Проверка конфигурации
console.log(window.gtscraftConfigs)
```

### Логирование обновлений
При выполнении `npm run modules:update` в консоли отображается:
```
index.js обновлен с модулями: Naryad, Smens, OrderNaryads, MyNewModule
```

## Заключение

Модульная система gtsCraft обеспечивает гибкую и масштабируемую архитектуру для разработки Vue.js приложений. Система автоматизирует процесс добавления новых модулей и обеспечивает их безопасную загрузку как в режиме разработки, так и в production среде.

Следуя данному руководству, вы сможете легко создавать новые модули и интегрировать их в существующее приложение без изменения основного кода.
