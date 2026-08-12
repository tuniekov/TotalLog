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
| `modx_user_id`, `username` | кто |
| `url`, `method`, `action` | что дёрнули (`action` — значение поля REQUEST, в имени которого есть «action») |
| `component` | к какому компоненту относится (заполняет анализатор) |
| `description` | человекочитаемое описание (заполняет анализатор) |
| `ip` | откуда |
| `request`, `body` | payload (обрезаются, чувствительные ключи маскируются) |
| `thread_id` | ID соединения MySQL — связка с бинлогом |
| `created_at`, `finished_at`, `duration_ms` | окно запроса |
| `excel_ids`, `smens` | доп. поля gtsAPI, задаются в `_build/configs/data.js` |

⚠️ Поле называется `description`, а НЕ `desc` — `DESC` зарезервированное слово MySQL,
в сыром SQL gtsAPI (`select`, `sortby`) оно ломает запрос.

## Сниппет-анализатор

`TotalLogAnalyzer` (имя настраивается в `totallog_analyzer_snippet`). Получает `$scriptProperties['snapshot']`,
возвращает JSON с `component`, `description`, `excel_ids`, `smens`.

Именно здесь описывается «человеческий» смысл действия: не «POST /api/gcNaryadLink/512», а
«Перемещено со смены 12.08.2026 на 15.08.2026». Расширять — правилами внутри сниппета.

## Настройки

| Настройка | По умолчанию | Смысл |
|---|---|---|
| `totallog_enabled` | 1 | рубильник |
| `totallog_log_get` | 0 | писать ли GET (иначе каждый хит пишет в базу) |
| `totallog_days` | 90 | срок хранения; самоочистка раз в сутки при любом запросе |
| `totallog_analyzer_snippet` | `TotalLogAnalyzer` | имя сниппета-анализатора |
| `totallog_skip_urls` | `/api/totallog,/assets/,/favicon` | маски URL, которые не логируем |

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
