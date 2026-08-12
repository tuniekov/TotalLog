# Компонент на основе PVExtra
Компонент на основе PVExtra создает в MODX:
1. Ресурсы (страницы). Настройки в файле _build\configs\resources.js.
2. Конфиг таблиц базы в _build\configs\gtsapipackages.js. Описание в docs/use_gtsapipackages.md.
3. Начальные данные в базу в файле _build\configs\data.js.
4. Схему базы компонента в файле core\components\[package]\model\schema\[package].mysql.schema.xml.
5. Сервисный класс компонента в файле core\components\[package]\model\[package].class.php.
6. Если нужна сложная форма или внешние действия читай docs/pvtables-gtsapi-table-creation.md.
Если это не нужно старайся задавать таблицу в _build\configs\gtsapipackages.js. На странице компонента выводи {'!PVTable' | snippet : ['table'=>'имя таблицы']}
7. Для дополнительного функционала на стороне сервера используем триггеры. docs/gtsAPI_triggers.md.
8. В коде vue используй апи docs\api-usage-guide.md.
9. В коде vue импортируй компоненты primevue из 'pvtables/dist/pvtables'. Например import { PVTables, Button, Dialog } from 'pvtables/dist/pvtables'.
10. Используй PVForm. docs\pvform-usage-guide.md.