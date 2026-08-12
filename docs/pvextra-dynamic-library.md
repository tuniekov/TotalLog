# Создание динамически подгружаемой библиотеки для PVExtra компонента

## Краткая инструкция

Для создания динамически подгружаемой библиотеки на основе PVExtra компонента необходимо добавить 3 элемента:

### 1. Создать файл `src/index.js`

Точка входа библиотеки с экспортом компонента:

```javascript
import YourComponent from './App.vue'
import { apiCtor } from 'pvtables/dist/pvtables'

/**
 * Дополнительные функции (опционально)
 */
export async function CustomHandler(requestData) {
  const api = apiCtor('yourTableName')
  const response = await api.action('your/action', requestData)
  return response
}

/**
 * Плагин Vue
 */
export default {
  install(app) {
    app.component('YourComponent', YourComponent)
  }
}

/**
 * Именованный экспорт
 */
export { YourComponent }
```

### 2. Создать файл `vite.config.component.js`

Конфигурация для сборки библиотеки:

```javascript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  build: {
    lib: {
      entry: resolve(__dirname, 'src/index.js'),
      name: 'YourComponentName',           // Глобальное имя
      fileName: 'yourcomponent',           // Имя файла
      formats: ['umd']
    },
    rollupOptions: {
      external: ['vue', /^pvtables.*/],
      output: {
        globals: { 
          vue: 'Vue',
          'pvtables/dist/pvtables': 'PVTables'
        },
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/yourcomponent.css'
          }
          return 'yourcomponent.[ext]'
        },
        entryFileNames: 'js/yourcomponent.js',
        chunkFileNames: 'js/[name].js'
      }
    },
    outDir: 'assets/components/yourpackage/web/',
    emptyOutDir: false
  },
  plugins: [vue()]
})
```

### 3. Добавить скрипт в `package.json`

```json
{
  "scripts": {
    "build:component": "vite build --config vite.config.component.js && node ./_build/upconfig.js"
  }
}
```

## Сборка

```bash
npm run build:component
```

## Результат

Файлы будут созданы в:
```
assets/components/yourpackage/web/
├── css/
│   └── yourcomponent.css
└── js/
    └── yourcomponent.js
```

## Ключевые параметры

- **name** - глобальное имя переменной (доступно как `window.YourComponentName`)
- **fileName** - базовое имя выходных файлов
- **formats: ['umd']** - формат для браузера
- **external** - зависимости не включаются в бандл (Vue, PVTables)
- **globals** - соответствие импортов глобальным переменным
- **outDir** - куда сохранять собранные файлы

Подробная документация: `docs/dynamic-library-guide.md`

---

## Использование динамической библиотеки в другом компоненте

### 1. Настройка главного приложения (main.js)

Для использования динамически загружаемых компонентов необходимо настроить `ComponentLoader` в главном файле приложения:

```javascript
import { createApp } from 'vue'
import myPVTables, { ComponentLoader } from 'pvtables/dist/pvtables'
import * as Vue from 'vue'
import * as PVTables from 'pvtables/dist/pvtables'
import App from './App.vue'

// Делаем Vue и PVTables доступными глобально для UMD компонентов
window.Vue = Vue
window.PVTables = PVTables

// Инициализируем PVTablesAPI для динамически загружаемых компонентов
if (!window.PVTablesAPI) {
    window.PVTablesAPI = {
        useNotifications: PVTables.useNotifications,
        apiCtor: PVTables.apiCtor,
        apiFetch: PVTables.apiFetch,
        Vue: Vue
    }
}

const app = createApp(App)

// Создаем ComponentLoader и делаем его доступным
const componentLoader = new ComponentLoader(app)
app.provide('componentLoader', componentLoader)
window.componentLoader = componentLoader

app.use(myPVTables)
app.mount('#app')
```

**Ключевые моменты:**
- `window.Vue` и `window.PVTables` - глобальные переменные для UMD компонентов
- `window.PVTablesAPI` - API для взаимодействия динамических компонентов с основным приложением
- `ComponentLoader` - класс для загрузки компонентов, доступен через `provide/inject` и глобально

### 2. Использование в компоненте Vue

```vue
<template>
  <div>
    <!-- Компонент будет доступен после загрузки -->
    <YourComponent 
      v-if="isComponentLoaded"
      :some-prop="someValue"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'

// Получаем ComponentLoader
const componentLoader = inject('componentLoader')

// Флаг загрузки компонента
const isComponentLoaded = ref(false)

onMounted(async () => {
  try {
    // Загружаем компонент по имени (без расширения .js)
    await componentLoader.loadComponent('YourComponent')
    
    // Проверяем успешную загрузку
    if (componentLoader.loadedComponents.has('YourComponent')) {
      isComponentLoaded.value = true
    }
  } catch (error) {
    console.error('Ошибка загрузки компонента:', error)
  }
})
</script>
```

### 3. Использование дополнительных функций из библиотеки

Если библиотека экспортирует дополнительные функции (например, `CustomHandler`), их можно использовать так:

```vue
<script setup>
import { inject } from 'vue'

const componentLoader = inject('componentLoader')

// Функция для вызова CustomHandler из загруженного компонента
const callCustomHandler = async (requestData) => {
  try {
    // Загружаем компонент, если еще не загружен
    await componentLoader.loadComponent('YourComponent')
    
    // Получаем модуль компонента
    const componentModule = componentLoader.loadedComponents.get('YourComponent')
    
    // Проверяем наличие функции
    if (componentModule && typeof componentModule.CustomHandler === 'function') {
      const result = await componentModule.CustomHandler(requestData)
      return result
    }
  } catch (error) {
    console.error('Ошибка вызова CustomHandler:', error)
    throw error
  }
}
</script>
```

### 4. Пример: Динамическая загрузка компонента печати

Реальный пример из модуля NewSmena, где компонент `PVPrint` загружается динамически:

```vue
<template>
  <div>
    <!-- Кнопка печати появится только после загрузки компонента -->
    <PVPrint 
      v-if="isPVPrintLoaded"
      :custom-print-handler="customPrintHandler"
      page-key="my-page"
      @print-success="handlePrintSuccess"
      @print-error="handlePrintError"
      ref="printBtn"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'

const componentLoader = inject('componentLoader')
const isPVPrintLoaded = ref(false)
const printBtn = ref(null)

// Кастомный обработчик печати
const customPrintHandler = async (printer, options) => {
  try {
    // Формируем данные для печати
    const requestData = {
      printer_id: printer.id,
      is_virtual: printer.is_virtual,
      print_options: options
    }
    
    // Проверяем, загружен ли компонент с PrintHandler
    if (componentLoader.loadedComponents.has('SomeComponent')) {
      const componentModule = componentLoader.loadedComponents.get('SomeComponent')
      
      // Используем PrintHandler из компонента, если он есть
      if (componentModule && typeof componentModule.PrintHandler === 'function') {
        const result = await componentModule.PrintHandler(requestData)
        
        if (result && result.data.html) {
          // Генерируем PDF из HTML
          return await printBtn.value.generatePDF(result.data.html, {
            pageKey: 'my-page',
            printOptions: options
          })
        }
      }
    }
    
    return { success: true, message: 'Печать выполнена' }
  } catch (error) {
    console.error('Ошибка печати:', error)
    throw error
  }
}

const handlePrintSuccess = (result) => {
  console.log('Печать успешна:', result)
}

const handlePrintError = (error) => {
  console.error('Ошибка печати:', error)
}

onMounted(async () => {
  try {
    // Загружаем компонент PVPrint
    await componentLoader.loadComponent('PVPrint')
    isPVPrintLoaded.value = true
  } catch (error) {
    console.error('Ошибка загрузки PVPrint:', error)
  }
})
</script>
```

### 5. Динамическая загрузка компонентов во вкладках

Пример использования динамических компонентов в системе вкладок:

```vue
<script setup>
import { ref, inject, nextTick } from 'vue'
import { PVTabs } from 'pvtables/dist/pvtables'

const componentLoader = inject('componentLoader')
const tabs = ref({
  default: {
    title: 'Основная вкладка',
    type: 'table',
    table: 'someTable',
    active: true
  }
})

// Функция для загрузки и добавления компонента во вкладку
const loadComponentTab = async (componentName, tabTitle) => {
  try {
    // Загружаем компонент
    await componentLoader.loadComponent(componentName)
    
    // Проверяем успешную загрузку
    if (!componentLoader.loadedComponents.has(componentName)) {
      console.error('Компонент не загружен')
      return
    }
    
    // Ждем регистрации компонента в Vue
    await nextTick()
    
    // Дополнительная задержка для полной регистрации
    await new Promise(resolve => setTimeout(resolve, 150))
    
    // Деактивируем текущую вкладку
    tabs.value.default.active = false
    
    // Добавляем новую вкладку с компонентом
    tabs.value.dynamicTab = {
      title: tabTitle,
      type: 'component',
      name_component: componentName,
      active: true
    }
    
    await nextTick()
  } catch (error) {
    console.error('Ошибка загрузки компонента:', error)
    // Возвращаемся к основной вкладке
    tabs.value.default.active = true
  }
}
</script>

<template>
  <PVTabs :tabs="tabs" />
</template>
```

### 6. Проверка загрузки компонента

```javascript
// Проверить, загружен ли компонент
if (componentLoader.loadedComponents.has('YourComponent')) {
  console.log('Компонент загружен')
}

// Получить модуль компонента
const componentModule = componentLoader.loadedComponents.get('YourComponent')

// Проверить наличие конкретной функции
if (componentModule && typeof componentModule.CustomHandler === 'function') {
  // Функция доступна
}
```

### 7. Важные замечания

1. **Глобальные зависимости**: Убедитесь, что `window.Vue` и `window.PVTables` установлены до загрузки компонентов
2. **Асинхронность**: Всегда используйте `await` при загрузке компонентов
3. **Проверка загрузки**: Проверяйте `loadedComponents.has()` перед использованием
4. **nextTick**: Используйте `nextTick()` после добавления компонента в DOM
5. **Обработка ошибок**: Всегда оборачивайте загрузку в `try/catch`
6. **Задержки**: Для сложных компонентов может потребоваться дополнительная задержка после `nextTick()`

### 8. Путь к компонентам

ComponentLoader автоматически формирует путь к компонентам:
```
/assets/components/{componentName}/web/js/{componentName}.js
```


