# Руководство по использованию API в App.vue

## Обзор

В файле `src/App.vue` используются три основных API компонента:
- **apiCtor** - конструктор для создания API клиентов
- **useNotifications** - хук для работы с уведомлениями
- **Toast** - компонент для отображения уведомлений

## 1. apiCtor - Конструктор API клиентов

### Импорт
```javascript
import { apiCtor } from 'pvtables/dist/pvtables'
```

### Создание API клиентов

#### Базовое использование
```javascript
const api_gsRaschet = apiCtor('gsRaschet')
const api_gsRaschetProduct = apiCtor('gsRaschetProduct')
const api_gsFavoriteProduct = apiCtor('gsFavoriteProduct')
const api_gsFavoriteRaschetProduct = apiCtor('gsFavoriteRaschetProduct')
const api_gsProductTree = apiCtor('gsProductTree')
```

#### С таймаутом
```javascript
const api_gsRaschet = apiCtor('gsRaschet', 300000) // 5 минут таймаут
```

### Методы API клиентов

#### 1. options() - Получение метаданных
```javascript
const response = await api_gsRaschet.options()
// Возвращает поля, настройки селектов и другие метаданные
```

#### 2. get(id) - Получение объекта по ID
```javascript
const raschet = await api_gsRaschet.get(raschet_id.value)
```

#### 3. read() - Чтение данных
```javascript
const response = await api_gsProductTree.read()
```

#### 4. update(object) - Обновление объекта
```javascript
const resp = await api_gsRaschet.update(Raschet.value)
```

#### 5. delete(params) - Удаление объекта
```javascript
await api_gsFavoriteRaschetProduct.delete({ ids: fproduct.id })
```

#### 6. action(actionName, params) - Выполнение кастомных действий
```javascript
// Перерасчет
const resp = await api_gsRaschet.action('gtsshop/pereraschet', {
  raschet_id: raschet_id.value
})

// Копирование расчета
const resp = await api_gsRaschet.action('gtsshop/copy_raschet', {
  raschet_id: raschet_id.value
})

// Отправка заказа
const resp = await api_gsRaschet.action('gtsshop/send_zakaz', {
  raschet_id: raschet_id.value
})

// Вставка продукта
await api_gsRaschetProduct.action('insert', {
  filters: filters,
  product_id: product.id
})

// Получение избранного
const response = await api_gsFavoriteProduct.action('gtsshop/get_favorites')

// Сохранение в избранное
await api_gsFavoriteProduct.action('gtsshop/save_favorite', {
  ...gsFavoriteRaschetProduct.value
})
```

### Обработка ответов API

Все API методы возвращают объект с полями:
- `success` - булево значение успешности операции
- `data` - данные ответа
- `message` - сообщение об ошибке или успехе

```javascript
const resp = await api_gsRaschet.action('gtsshop/pereraschet', {
  raschet_id: raschet_id.value
})

if (!resp.success) {
  notify('error', { detail: resp.message })
} else {
  notify('success', { detail: resp.message })
}
```

## 2. useNotifications - Хук для уведомлений

### Импорт и инициализация
```javascript
import { useNotifications } from 'pvtables/dist/pvtables'

const { notify } = useNotifications()
```

### Использование

#### Уведомление об ошибке
```javascript
notify('error', { detail: error.message })
notify('error', { detail: 'Текст ошибки' })
```

#### Уведомление об успехе
```javascript
notify('success', { detail: 'Операция выполнена успешно' })
notify('success', { detail: resp.message })
```

### Примеры использования в try-catch блоках

```javascript
try {
  const resp = await api_gsRaschet.update(Raschet.value)
  if (!resp.success) {
    notify('error', { detail: resp.message })
  }
} catch (error) {
  notify('error', { detail: error.message })
}
```

## 3. Toast - Компонент для отображения уведомлений

### Импорт
```javascript
import { Toast } from 'pvtables/dist/pvtables'
```

### Использование в шаблоне
```vue
<template>
  <!-- Другие компоненты -->
  <Toast/>
</template>
```

Компонент `Toast` автоматически отображает уведомления, отправленные через `useNotifications`.

## Паттерны использования

### 1. Стандартная обработка API вызовов
```javascript
const someApiCall = async () => {
  try {
    const resp = await api_client.action('some_action', params)
    if (!resp.success) {
      notify('error', { detail: resp.message })
    } else {
      notify('success', { detail: resp.message })
      // Дополнительная логика при успехе
    }
  } catch (error) {
    notify('error', { detail: error.message })
  }
}
```

### 2. Обновление данных после API вызова
```javascript
const updateData = async () => {
  try {
    const resp = await api_gsRaschet.update(data)
    if (resp.data.object) {
      Raschet.value = resp.data.object // Обновляем локальные данные
    }
    if (resp.data.refresh_table == 1) {
      await getRaschetForm() // Перезагружаем форму
      gsRaschetProduct_table.value.refresh() // Обновляем таблицу
    }
  } catch (error) {
    notify('error', { detail: error.message })
  }
}
```

### 3. Работа с формами и диалогами
```javascript
const saveProduct = async () => {
  try {
    await api_gsRaschetProduct.action('insert', {
      filters: filters,
      ...gsRaschetProduct.value
    })
    gsRaschetProduct_table.value.refresh()
    gsRascheProductDialog.value = false // Закрываем диалог
  } catch (error) {
    notify('error', { detail: error.message })
  }
}
```

## Заключение

Данная архитектура обеспечивает:
- Единообразную работу с API через `apiCtor`
- Централизованную систему уведомлений через `useNotifications`
- Автоматическое отображение уведомлений через компонент `Toast`
- Удобную обработку ошибок и успешных операций
