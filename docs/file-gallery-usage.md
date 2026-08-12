# Галерея файлов gtsAPIFile

Галерея файлов - это комплексное решение для управления файлами в PVTables, которое позволяет загружать, просматривать, редактировать и привязывать файлы к ресурсам и пользователям.

## Компоненты

### FileGallery
Основной компонент галереи файлов с полным функционалом.

```vue
<template>
  <FileGallery
    title="Галерея файлов"
    :parent-id="resourceId"
    parent-class="modResource"
    list-name="gallery"
    :allow-upload="true"
    :allow-edit="true"
    :allow-delete="true"
    :show-filters="true"
    :page-size="20"
    api-endpoint="/api/file-gallery"
    @file-selected="onFileSelected"
    @files-uploaded="onFilesUploaded"
    @file-updated="onFileUpdated"
    @file-deleted="onFileDeleted"
    @error="onError"
  />
</template>

<script>
export default {
  data() {
    return {
      resourceId: 123
    }
  },
  methods: {
    onFileSelected(file) {
      console.log('Выбран файл:', file)
    },
    onFilesUploaded(files) {
      console.log('Загружены файлы:', files)
    },
    onFileUpdated(file) {
      console.log('Обновлен файл:', file)
    },
    onFileDeleted(file) {
      console.log('Удален файл:', file)
    },
    onError(message) {
      console.error('Ошибка:', message)
    }
  }
}
</script>
```

#### Props

| Prop | Тип | По умолчанию | Описание |
|------|-----|--------------|----------|
| `title` | String | 'Галерея файлов' | Заголовок галереи |
| `parentId` | Number/String | 0 | ID родительского объекта |
| `parentClass` | String | 'modResource' | Класс родительского объекта |
| `listName` | String | 'default' | Название списка для группировки |
| `allowUpload` | Boolean | true | Разрешить загрузку файлов |
| `allowEdit` | Boolean | true | Разрешить редактирование файлов |
| `allowDelete` | Boolean | true | Разрешить удаление файлов |
| `allowActions` | Boolean | true | Показывать кнопки действий |
| `allowSelection` | Boolean | false | Разрешить множественный выбор |
| `showFilters` | Boolean | true | Показывать фильтры |
| `pageSize` | Number | 20 | Количество файлов на странице |
| `apiEndpoint` | String | '/api/file-gallery' | Endpoint API |

#### События

| Событие | Параметры | Описание |
|---------|-----------|----------|
| `file-selected` | file | Файл выбран |
| `files-uploaded` | files | Файлы загружены |
| `file-updated` | file | Файл обновлен |
| `file-deleted` | file | Файл удален |
| `file-opened` | file | Файл открыт для просмотра |
| `file-downloaded` | file | Файл скачан |
| `selection-changed` | selectedIds | Изменился выбор файлов |
| `error` | message | Произошла ошибка |

### FileUploadDialog
Диалог для загрузки файлов с drag & drop поддержкой.

```vue
<FileUploadDialog
  v-if="showUploadDialog"
  :parent-id="resourceId"
  parent-class="modResource"
  list-name="gallery"
  :allowed-extensions="['jpg', 'png', 'pdf']"
  :max-file-size="10485760"
  api-endpoint="/api/file-gallery"
  @close="showUploadDialog = false"
  @uploaded="onFilesUploaded"
/>
```

### FileEditDialog
Диалог для редактирования информации о файле.

```vue
<FileEditDialog
  v-if="showEditDialog"
  :file="selectedFile"
  api-endpoint="/api/file-gallery"
  @close="showEditDialog = false"
  @updated="onFileUpdated"
/>
```

### FileViewDialog
Диалог для просмотра файлов с поддержкой изображений, PDF и текстовых файлов.

```vue
<FileViewDialog
  v-if="showViewDialog"
  :file="selectedFile"
  api-endpoint="/api/file-gallery"
  @close="showViewDialog = false"
/>
```

## API

### FileGalleryAPI
Класс для работы с API галереи файлов.

```javascript
import { FileGalleryAPI } from 'pvtables'

const api = new FileGalleryAPI('/api/file-gallery')

// Получение списка файлов
const files = await api.getFiles({
  parent: 123,
  class: 'modResource',
  list: 'gallery',
  limit: 20,
  offset: 0
})

// Загрузка файлов
const uploadedFiles = await api.uploadFiles(fileList, {
  parent: 123,
  class: 'modResource',
  list: 'gallery'
})

// Обновление файла
const updatedFile = await api.updateFile(fileId, {
  name: 'Новое название',
  description: 'Новое описание'
})

// Удаление файла
await api.deleteFile(fileId)

// Привязка файла к объекту
await api.attachFile(fileId, parentId, 'modResource', 'gallery')

// Отвязка файла
await api.detachFile(fileId)
```

## Утилиты

### fileUtils
Набор утилит для работы с файлами.

```javascript
import { fileUtils } from 'pvtables'

// Форматирование размера файла
const size = fileUtils.formatFileSize(1024) // "1.00 KB"

// Получение иконки файла
const icon = fileUtils.getFileIcon('pdf') // "fas fa-file-pdf"

// Проверка типа файла
const isImage = fileUtils.isImage('jpg') // true
const isText = fileUtils.isTextFile('txt') // true

// Получение расширения
const ext = fileUtils.getFileExtension('document.pdf') // "pdf"

// Валидация файла
const validation = fileUtils.validateFile(file, config)
if (!validation.valid) {
  console.error(validation.error)
}
```

## Серверная часть

### Модель gtsAPIFile
PHP класс для работы с файлами в базе данных.

```php
// Создание нового файла
$file = $modx->newObject('gtsAPIFile');
$file->fromArray([
    'parent' => 123,
    'class' => 'modResource',
    'list' => 'gallery',
    'name' => 'Документ',
    'path' => 'uploads/gallery/',
    'file' => 'document.pdf',
    'mime' => 'application/pdf',
    'type' => 'pdf',
    'size' => 1024,
    'active' => 1
]);
$file->save();

// Получение файлов ресурса
$files = $modx->getCollection('gtsAPIFile', [
    'parent' => 123,
    'class' => 'modResource',
    'active' => 1
]);

// Генерация миниатюр для изображения
if ($file->isImage()) {
    $file->generateThumbnails();
}
```

### API Контроллер
PHP контроллер для обработки запросов API.

```php
// Регистрация маршрута в gtsAPI
$rule = $modx->newObject('gtsAPIRule');
$rule->fromArray([
    'point' => 'file-gallery',
    'controller_class' => 'gtsAPIFileGalleryController',
    'controller_path' => 'path/to/controller/',
    'active' => 1,
    'authenticated' => 1
]);
$rule->save();

// Добавление действий
$actions = [
    ['gtsaction' => 'list', 'processor' => 'getFilesList'],
    ['gtsaction' => 'upload', 'processor' => 'uploadFile'],
    ['gtsaction' => 'update', 'processor' => 'updateFile'],
    ['gtsaction' => 'delete', 'processor' => 'deleteFile']
];

foreach ($actions as $actionData) {
    $action = $modx->newObject('gtsAPIAction');
    $action->fromArray(array_merge($actionData, [
        'rule_id' => $rule->get('id'),
        'active' => 1
    ]));
    $action->save();
}
```

## Схема базы данных

Таблица `gtsapi_files` содержит следующие поля:

- `id` - Уникальный идентификатор
- `parent` - ID родительского объекта
- `class` - Класс родительского объекта (modResource, modUser)
- `list` - Название списка для группировки
- `name` - Название файла
- `description` - Описание файла
- `path` - Путь к файлу
- `file` - Имя файла на диске
- `mime` - MIME тип
- `type` - Расширение файла
- `trumb` - URL миниатюры
- `url` - URL файла
- `hash` - SHA1 хеш содержимого
- `session` - ID сессии
- `size` - Размер файла в байтах
- `createdby` - ID пользователя, создавшего файл
- `source` - ID источника медиа
- `context` - Контекст
- `active` - Активность файла
- `rank` - Порядок сортировки
- `createdon` - Дата создания
- `properties` - Дополнительные свойства (JSON)

## Примеры использования

### Простая галерея для ресурса

```vue
<template>
  <div>
    <h2>Файлы ресурса</h2>
    <FileGallery
      :parent-id="$route.params.id"
      parent-class="modResource"
      list-name="attachments"
    />
  </div>
</template>
```

### Галерея с ограниченными правами

```vue
<template>
  <FileGallery
    title="Документы"
    :parent-id="userId"
    parent-class="modUser"
    list-name="documents"
    :allow-upload="canUpload"
    :allow-edit="canEdit"
    :allow-delete="canDelete"
    :allowed-extensions="['pdf', 'doc', 'docx']"
  />
</template>

<script>
export default {
  computed: {
    canUpload() {
      return this.$store.getters.hasPermission('file_upload')
    },
    canEdit() {
      return this.$store.getters.hasPermission('file_edit')
    },
    canDelete() {
      return this.$store.getters.hasPermission('file_delete')
    }
  }
}
</script>
```

### Программная работа с файлами

```javascript
import { FileGalleryAPI } from 'pvtables'

export default {
  async created() {
    this.api = new FileGalleryAPI()
    await this.loadFiles()
  },
  
  methods: {
    async loadFiles() {
      try {
        const response = await this.api.getFiles({
          parent: this.resourceId,
          class: 'modResource',
          type: 'jpg'
        })
        
        if (response.success) {
          this.files = response.data.files
        }
      } catch (error) {
        console.error('Ошибка загрузки файлов:', error)
      }
    },
    
    async uploadFile(file) {
      try {
        const response = await this.api.uploadFiles([file], {
          parent: this.resourceId,
          class: 'modResource',
          list: 'gallery'
        })
        
        if (response.success) {
          await this.loadFiles()
        }
      } catch (error) {
        console.error('Ошибка загрузки файла:', error)
      }
    }
  }
}
```

## Настройка

### Конфигурация по умолчанию

```javascript
import { defaultConfig } from 'pvtables'

// Изменение настроек по умолчанию
defaultConfig.maxFileSize = 20971520 // 20MB
defaultConfig.allowedExtensions.push('svg')
defaultConfig.pageSize = 50
```

### Кастомизация стилей

```css
/* Переопределение стилей галереи */
.file-gallery {
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.file-item:hover {
  transform: translateY(-2px);
  transition: transform 0.2s ease;
}

.upload-zone {
  border-style: solid;
  border-width: 3px;
}
```

## Безопасность

1. **Валидация файлов** - Проверка расширений и размеров на клиенте и сервере
2. **Права доступа** - Контроль через систему разрешений MODX
3. **Санитизация** - Очистка имен файлов и путей
4. **Хеширование** - SHA1 хеши для проверки целостности
5. **Сессии** - Привязка к сессиям для временных файлов

## Производительность

1. **Ленивая загрузка** - Пагинация и виртуальный скроллинг
2. **Кеширование** - Кеширование миниатюр и метаданных
3. **Оптимизация изображений** - Автоматическое создание миниатюр
4. **CDN поддержка** - Возможность использования внешних источников медиа
