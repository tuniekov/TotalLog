# TotalLog

Тотальное логирование запросов MODX: одна строка на каждый обработанный запрос —
кто, что дёрнул, с какими параметрами, сколько выполнялось и какие заказы/смены затронул.

Отвечает на вопрос «кто это сделал», когда `gtsAPILog` не помогает: тот пишет только
CRUD через gtsAPI, а TotalLog видит вообще всё, включая кастомные действия и legacy-код.

## Установка

```bash
npm install
npm run build
```

Сборка создаёт таблицу, плагин, сниппет-анализатор, настройки, конфиги таблиц и страницы.

## Страницы

Пакет создаёт две страницы. **Родитель не зашит числом** — на каждом сайте структура своя:

1. системная настройка `totallog_parent_resource` (id родителя) — приоритет;
2. иначе ищется страница с алиасом `admin`;
3. иначе — корень сайта.

Если алиас `totallog` на сайте уже занят чужой страницей, резолвер её **не трогает**,
а создаёт свою с префиксом (`totallog-totallog`).

Id созданных страниц пишутся в настройки `totallog_p_all` и `totallog_p_user` —
ссылаться в коде надо на них, а не на номера.

### Не создавать страницы

Для публичной сборки очистите `_build/configs/resources.js` до `export default {}`.
Тогда страницу создаёт пользователь сам, а вызов вставляет руками:

```modx
{'!mixVue' | snippet : [
    'app'=>'totallog',
    'config'=>['module'=>'TLAll']
]}
```

## Модули

| Модуль | Кому | Что показывает |
|---|---|---|
| `TLAll` | админ | все запросы, все поля, кнопка «Изменения в БД» |
| `TLUser` | пользователь | только заданные компоненты и понятные поля |

Пользовательский модуль настраивается прямо в вызове:

```modx
{'!mixVue' | snippet : [
    'app'=>'totallog',
    'config'=>[
        'module'=>'TLUser',
        'component'=>'newsmena,gcNaryadLink',
        'title'=>'Кто менял наряды'
    ]
]}
```

## Настройки

| Настройка | По умолчанию | Смысл |
|---|---|---|
| `totallog_enabled` | 1 | рубильник |
| `totallog_log_get` | 0 | писать ли GET-запросы |
| `totallog_days` | 90 | срок хранения; чистка раз в сутки |
| `totallog_analyzer_snippet` | `TotalLogAnalyzer` | сниппет, объясняющий смысл запроса |
| `totallog_skip_urls` | `/api/totallog,/api/package,/assets/,/favicon` | что не логировать |
| `totallog_parent_resource` | 0 | родитель страниц пакета |

Настройки при обновлении пакета **не перезаписываются** (`update.settings: false`) —
выставленные значения переживают деплой.

## Бинлог MySQL — второй слой, по умолчанию ВЫКЛЮЧЕН

Журнал отвечает «кто нажал». Что при этом **реально изменилось в базе** — знает бинлог:
он пишет каждую изменённую строку, включая правки мимо API (прямой SQL, триггеры, крон).

**Постоянно держать его включённым не нужно.** Legacy-код (Люся, ZagruzkaTable, tSklad)
всё равно поднимает MODX и попадает в TotalLog, а что именно поменялось через API —
пишет `gtsAPILog` с `data_before`/`data_after` и готовым откатом (`restore_version`).
Бинлог остаётся инструментом **точечного расследования**: включил на неделю, поймал,
выключил. Формат ROW пишет строку целиком — один пересчёт расчёта это сотни строк,
а при `expire_logs_days = 0` логи не чистятся и молча забивают диск.

Связка уже готова: в каждой записи журнала лежат `thread_id` (соединение MySQL) и окно
`created_at … finished_at`. Кнопка **«Изменения в БД»** собирает из них команду
`mysqlbinlog`. Пока бинлог выключен, команда вернёт пустоту — это ожидаемо.

### Включение на OpenServer / OSPanel (Windows, MySQL 5.7)

Правки в `my.ini` активного модуля MySQL (`V:\OSPanel\modules\MySQL-5.7\my.ini`,
в OpenServer — `modules/database/MySQL-5.7/my.ini`), секция `[mysqld]`:

```ini
[mysqld]
log_bin           = mysql-bin        # относительный путь = внутрь datadir
server_id         = 1                # обязателен, иначе MySQL не стартует
binlog_format     = ROW              # построчно, а не по запросам
binlog_row_image  = FULL             # полные строки: видно «было → стало»
expire_logs_days  = 7                # ⚠️ без этого логи не удаляются никогда
max_binlog_size   = 100M
```

Перезапустить модуль MySQL из трея OSPanel (не весь сервер). Проверка:

```sql
SHOW VARIABLES WHERE Variable_name IN ('log_bin','binlog_format','binlog_row_image','expire_logs_days','datadir');
SHOW BINARY LOGS;
```

Файлы лягут в `datadir` (`V:\OSPanel\data\MySQL-5.7\default\`). Чтение — из папки MySQL:

```cmd
V:\OSPanel\modules\MySQL-5.7\bin\mysqlbinlog.exe -v --base64-output=DECODE-ROWS ^
  --start-datetime="2026-08-14 02:00:00" --stop-datetime="2026-08-14 02:05:00" ^
  V:\OSPanel\data\MySQL-5.7\default\mysql-bin.000001 > dump.txt
```

Дальше искать в `dump.txt` строку `thread_id=<из журнала>` — это и есть правки того запроса.

### Включение на Ubuntu (MySQL 5.7)

Отдельным файлом, чтобы не трогать пакетные конфиги:

```bash
sudo tee /etc/mysql/mysql.conf.d/binlog.cnf >/dev/null <<'EOF'
[mysqld]
log_bin           = /var/log/mysql/mysql-bin.log
server_id         = 1
binlog_format     = ROW
binlog_row_image  = FULL
expire_logs_days  = 7
max_binlog_size   = 100M
EOF

sudo systemctl restart mysql
```

⚠️ Каталог должен принадлежать mysql: `sudo mkdir -p /var/log/mysql && sudo chown mysql:mysql /var/log/mysql`.
Если включён AppArmor, путь вне `/var/log/mysql` придётся разрешать в профиле — проще не выносить.

Проверка и чтение:

```bash
mysql -e "SHOW VARIABLES LIKE 'log_bin'; SHOW MASTER STATUS;"
sudo mysqlbinlog -v --base64-output=DECODE-ROWS \
  --start-datetime="2026-08-14 02:00:00" --stop-datetime="2026-08-14 02:05:00" \
  /var/log/mysql/mysql-bin.* | awk '/thread_id=12345/,0' | head -100
```

Место под контролем: `du -sh /var/log/mysql`. Ручная чистка — `PURGE BINARY LOGS BEFORE NOW() - INTERVAL 3 DAY;`
(файлы удалять `rm`-ом нельзя — MySQL ведёт их индекс).

### Выключение

Убрать `log_bin` из конфига и перезапустить MySQL; оставшиеся файлы удалить через
`PURGE BINARY LOGS`. Настройки `binlog_format`/`binlog_row_image` можно оставить —
без `log_bin` они ни на что не влияют.

Подробности архитектуры — в `CLAUDE.md`.
