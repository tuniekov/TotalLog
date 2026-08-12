# TotalLog — тотальное логирование запросов MODX

## Роль

Отвечает на вопрос «кто это сделал». Пишет **одну строку на каждый обработанный MODX-запрос**
в таблицу `TLItem` (`modx_totallog_items`) и показывает журнал в двух UI-модулях.

Дополняет `gtsAPILog` (лог gtsAPI): тот пишет только CRUD **через gtsAPI**, а TotalLog —
все запросы вообще, включая кастомные действия и legacy-код, который ходит мимо API.

## Как устроено

```
Запрос → плагин TotalLog (OnMODXInit)
           ├─ снимок: url, method, action, ip, REQUEST, BODY, время старта
           └─ register_shutdown_function
                ├─ сниппет-анализатор → component / description / excel_ids / smens
                ├─ CONNECTION_ID() → thread_id  (привязка к бинлогу MySQL)
                └─ INSERT в TLItem + раз в сутки чистка старых записей
```

Строка пишется **в конце запроса**, а не в начале: к этому моменту известны пользователь,
длительность и результат. Соединение с MySQL то же самое, поэтому `thread_id` валиден.

## Таблица TLItem

| Поле | Смысл |
|---|---|
| `modx_user_id`, `username` | кто (в UI показывается ФИО — автокомплит `modUserTotalLog` по `modx_user_id`) |
| `url`, `method`, `action` | что дёрнули (`action` — значение поля REQUEST, в имени которого есть «action»). Для CLI в `url` пишется файл с аргументами |
| `component` | пакет: из префикса действия (`gtsshop/…`), из `/assets/components/<пакет>/` или из справочника gtsAPI по таблице |
| `table_name` | таблица: `/api/TLItem` у gtsAPI, параметр `table_name` у legacy getTables |
| `description` | человекочитаемое описание (заполняет анализатор) |
| `ip` | откуда |
| `request`, `body` | payload (обрезаются, чувствительные ключи маскируются; форма раскладывается в JSON) |
| `thread_id` | ID соединения MySQL — связка с бинлогом |
| `created_at`, `finished_at`, `duration_ms` | окно запроса |
| `sql_count`, `sql_time_ms` | цена запроса в базе: сколько SQL и сколько на них ушло |
| `excel_ids`, `raschet_ids`, `smens` | доп. поля gtsAPI, задаются в `_build/configs/data.js` |

⚠️ `excel_ids` — **номера заказов**, `raschet_ids` — **ид расчётов** (`gsRaschet.id`). Это разные вещи,
`raschet_id` из запроса в «Заказы» не пишем.

⚠️ Поле `table_name`, а не `table` — по той же причине, что `description` вместо `desc`.

⚠️ Поле называется `description`, а НЕ `desc` — `DESC` зарезервированное слово MySQL,
в сыром SQL gtsAPI (`select`, `sortby`) оно ломает запрос.

## Сниппет-анализатор

`TotalLogAnalyzer` (имя настраивается в `totallog_analyzer_snippet`). Получает `$scriptProperties['snapshot']`,
возвращает JSON с `component`, `description`, `excel_ids`, `smens`.

Именно здесь описывается «человеческий» смысл действия: не «POST /api/gcNaryadLink/512», а
«Перемещено со смены 12.08.2026 на 15.08.2026». Расширять — правилами внутри сниппета.

## Динамические поля в конфиге таблиц

`excel_ids`, `raschet_ids`, `smens` — динамические поля gtsAPI: они заданы в
`_build/configs/data.js` и могут добавляться прямо на сайте. В `gtsapipackages.js` их
**не перечисляем** — иначе список живёт в двух местах и новые поля молча теряются.

Работают два триггера (`regTriggers()`; в свойствах таблицы — `loadModels: 'totallog'`).
Ключ триггера — **класс** (`TLItem`), поэтому одна запись покрывает обе таблицы.

| Триггер | Метод | Зачем |
|---|---|---|
| `gtsapi_rule` | `ruleTLItem` | добавляет поля в конфиг: gtsAPI подставляет их только в таблицу, зарегистрированную в `gtsAPIFieldTable` (у нас `TLItem`), — `TLItemUser` осталась бы без них |
| `gtsapi_addfields` | `addFieldsTLItem` | закрывает их на запись: gtsAPI отдаёт динамические поля редактируемыми, а журнал только читают |

⚠️ **`readonly` ставить именно в `gtsapi_addfields`.** `options()` вызывает `addFields()`
**второй раз** — уже после `gtsapi_rule` — и пересобирает динамические поля с нуля, теряя
всё, что дописал триггер правил. В `TLItemUser` этого не видно (там `addFields()` выходит
сразу), поэтому баг выглядит как необъяснимая разница между двумя журналами.

## Настройки

| Настройка | По умолчанию | Смысл |
|---|---|---|
| `totallog_enabled` | 1 | рубильник |
| `totallog_log_get` | 0 | писать ли GET (иначе каждый хит пишет в базу) |
| `totallog_days` | 90 | срок хранения; самоочистка раз в сутки при любом запросе |
| `totallog_analyzer_snippet` | `TotalLogAnalyzer` | имя сниппета-анализатора |
| `totallog_skip_urls` | `/api/totallog,/api/package,/favicon` | маски URL, которые не логируем |
| `totallog_log_service` | 1 | писать ли служебные действия |
| `totallog_service_actions` | `options,read,get,autocomplete,…` | что считать служебным |
| `totallog_log_modx` | 1 | писать ли возню менеджера MODX |
| `totallog_modx_actions` | `system/,resource/,element/,…` | ветки процессоров MODX |

Шум выключается по частям, потому что журнал закрывает три разные задачи: **кто что делал**,
**где ошибки компонентов**, **где тормозит** (четвёртая — откат — это бинлог, см. ниже).
Служебные действия нужны для «где тормозит», действия менеджера — для «кто удалил ресурс»;
кому что не нужно, тот гасит своим рубильником.

Списки действий сравниваются тремя способами: полное имя (`lusya/calc`), часть после `/`
(`calc`) и префикс, если запись кончается на `/` или `*` (`system/` ловит всю ветку).

⚠️ В `_build/config.js` стоит `update.settings: false` — иначе каждый деплой сбрасывал бы
значения, выставленные админом (грабля из Lusya).

## UI-модули

Модульная система PVExtra (`src/modules/`, см. `PVExtra/docs/modular-system-guide.md`).

| Модуль | Кому | Что показывает |
|---|---|---|
| `TLAll` | админ | все поля + кнопка «Изменения в БД» (бинлог запроса) |
| `TLUser` | пользователь | только заданные `component`, понятные поля |

Подключение на странице:

```modx
{'!mixVue' | snippet : [
    'app'=>'totallog',
    'config'=>[
        'module'=>'TLUser',
        'component'=>'gcNaryadLink,newsmena',
        'title'=>'Кто менял наряды'
    ]
]}
```

## Бинлог MySQL (второй слой, пока не подключён)

TotalLog отвечает «кто и что нажал». Что при этом **реально изменилось в базе** — знает только
бинлог MySQL в формате ROW. Он ловит любые изменения, включая прямые `save()` и сырой SQL.

Состояние: `log_bin=OFF`, но `binlog_format=ROW` и `binlog_row_image=FULL` — включается одной
настройкой. ⚠️ `expire_logs_days=0` — без ретеншна логи не чистятся и забьют диск.

Кнопка «Изменения в БД» сейчас отдаёт готовую команду `mysqlbinlog` с подставленными
`thread_id` и окном времени. Следующий шаг — фоновый разбор бинлога в таблицу, чтобы UI читал из БД.

## Сборка

```bash
npm install
npm run modules:update   # после добавления модуля в src/modules/
npm run build            # vite build + upconfig (схема, плагин, сниппет, настройки, конфиги таблиц)
```
