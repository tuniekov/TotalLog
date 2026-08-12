# Руководство по использованию компонента PVForm

## Обзор

PVForm - это универсальный компонент формы из библиотеки pvtables, который автоматически генерирует поля формы на основе метаданных и обеспечивает двустороннюю привязку данных.

## Импорт

```javascript
import { PVForm } from 'pvtables/dist/pvtables'
```

## Базовое использование

### В шаблоне
```vue
<template>
  <PVForm 
    v-model="formData" 
    :columns="columns" 
    :selectSettings="selectSettings" 
    :inline="true" 
    @set-value="onFormChange"
  />
</template>
```

### В скрипте
```javascript
import { ref } from 'vue'
import { PVForm } from 'pvtables/dist/pvtables'

const formData = ref({})
const columns = ref([])
const selectSettings = ref({})

const onFormChange = (data) => {
  // Обработка изменений формы
  console.log('Form changed:', data)
}
```

## Свойства (Props)

### v-model
Двусторонняя привязка данных формы.

```javascript
const Raschet = ref({
  id: 1,
  name: 'Название расчета',
  date: '2023-01-01'
})
```

```vue
<PVForm v-model="Raschet" :columns="columns" />
```

### :columns
Массив объектов, описывающих поля формы. Каждый объект содержит метаданные поля.

#### Структура объекта column:
```javascript
const columns = ref([
  {
    field: 'id',           // Имя поля
    label: 'ID',           // Подпись поля
    type: 'number',        // Тип поля
    dataType: 'numeric',   // Тип данных для валидации
    readonly: true,        // Только для чтения
    required: false        // Обязательное поле
  },
  {
    field: 'name',
    label: 'Название',
    type: 'text',
    dataType: 'text',
    readonly: false
  },
  {
    field: 'description',
    label: 'Описание',
    type: 'textarea',
    dataType: 'text'
  },
  {
    field: 'product_id',
    label: 'Продукт',
    type: 'autocomplete',
    table: 'gsProduct',    // Таблица для автокомплита
    dataType: 'numeric'
  },
  {
    field: 'active',
    label: 'Активен',
    type: 'boolean',
    dataType: 'boolean'
  },
  {
    field: 'created_date',
    label: 'Дата создания',
    type: 'date',
    dataType: 'date'
  }
])
```

#### Типы полей (type):
- `text` - текстовое поле
- `textarea` - многострочное текстовое поле
- `number` - числовое поле
- `decimal` - десятичное число
- `date` - поле даты
- `boolean` - чекбокс
- `autocomplete` - поле с автокомплитом
- `view` - только для просмотра

#### Типы данных (dataType):
- `text` - текстовые данные
- `numeric` - числовые данные
- `date` - данные даты
- `boolean` - булевы данные

### :selectSettings
Объект с настройками для полей типа select и autocomplete.

```javascript
const selectSettings = ref({
  product_id: {
    rows: [
      { id: 1, name: 'Продукт 1' },
      { id: 2, name: 'Продукт 2' }
    ]
  },
  category_id: {
    rows: [
      { id: 1, name: 'Категория 1' },
      { id: 2, name: 'Категория 2' }
    ]
  }
})
```

### :inline
Булево значение для отображения полей в одну строку.

```vue
<PVForm v-model="data" :columns="columns" :inline="true" />
```

## События

### @set-value
Срабатывает при изменении любого поля формы.

```vue
<PVForm 
  v-model="Raschet" 
  :columns="columns" 
  @set-value="SaveRaschet"
/>
```

```javascript
const SaveRaschet = async (changedData) => {
  try {
    const resp = await api_gsRaschet.update(Raschet.value)
    if (resp.data.object) {
      Raschet.value = resp.data.object
    }
    if (!resp.success) {
      notify('error', { detail: resp.message })
    }
  } catch (error) {
    notify('error', { detail: error.message })
  }
}
```

## Генерация columns из API

Обычно columns генерируются автоматически на основе метаданных, полученных от API:

```javascript
const getRaschetForm = async () => {
  try {
    const response = await api_gsRaschet.options()
    
    if (response.data.hasOwnProperty("fields")) {
      const fields = response.data.fields
      
      if (response.data.selects) {
        selectSettings.value = response.data.selects
      }
      
      let cols = []
      
      for (let field in fields) {
        fields[field].field = field
        
        // Установка label по умолчанию
        if (!fields[field].hasOwnProperty("label")) {
          fields[field].label = field
        }
        
        // Установка типа по умолчанию
        if (!fields[field].hasOwnProperty("type")) {
          fields[field].type = "text"
        }
        
        // Обработка readonly
        if (fields[field].hasOwnProperty("readonly")) {
          fields[field].readonly = fields[field].readonly === true || fields[field].readonly == 1
        }
        
        // Добавление данных для select
        if (fields[field].select_data) {
          if (!selectSettings.value[field]) {
            selectSettings.value[field] = {}
          }
          selectSettings.value[field].rows = fields[field].select_data
        }
        
        // Определение dataType на основе type
        switch (fields[field].type) {
          case "view":
          case "number":
          case "decimal":
          case "autocomplete":
            fields[field].dataType = "numeric"
            break
          case "date":
            fields[field].dataType = "date"
            break
          case "boolean":
            fields[field].dataType = "boolean"
            break
          default:
            fields[field].dataType = "text"
        }
        
        cols.push(fields[field])
      }
      
      columns.value = cols
      
      // Загрузка данных объекта
      Raschet.value = await api_gsRaschet.get(raschet_id.value)
    }
  } catch (error) {
    notify('error', { detail: error.message })
  }
}
```

## Примеры использования

### 1. Простая форма редактирования
```vue
<template>
  <Dialog v-model:visible="editDialog" header="Редактировать" modal>
    <PVForm v-model="editData" :columns="editColumns"/>
    <template #footer>
      <Button label="Отмена" @click="hideDialog" />
      <Button label="Сохранить" @click="saveData" />
    </template>
  </Dialog>
</template>

<script setup>
const editData = ref({})
const editColumns = ref([
  {
    field: 'product_id',
    label: 'Изделие',
    type: 'autocomplete',
    table: 'gsProduct',
    readonly: true
  },
  {
    field: 'quantity',
    label: 'Количество',
    type: 'number',
    dataType: 'numeric'
  },
  {
    field: 'price',
    label: 'Цена',
    type: 'decimal',
    dataType: 'numeric'
  }
])

const saveData = async () => {
  try {
    await api_client.update(editData.value)
    editDialog.value = false
    notify('success', { detail: 'Данные сохранены' })
  } catch (error) {
    notify('error', { detail: error.message })
  }
}
</script>
```

### 2. Форма с автосохранением
```vue
<template>
  <PVForm 
    v-model="autoSaveData" 
    :columns="columns" 
    @set-value="autoSave"
  />
</template>

<script setup>
const autoSave = debounce(async () => {
  try {
    await api_client.update(autoSaveData.value)
    notify('success', { detail: 'Автосохранение выполнено' })
  } catch (error) {
    notify('error', { detail: error.message })
  }
}, 1000)
</script>
```

## Заключение

PVForm обеспечивает:
- Автоматическую генерацию полей на основе метаданных
- Двустороннюю привязку данных
- Встроенную валидацию
- Поддержку различных типов полей
- Гибкую настройку через props
- Интеграцию с системой уведомлений
