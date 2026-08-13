<?php
/**
 * TotalLog — тотальное логирование запросов MODX.
 *
 * Событие: OnMODXInit.
 *
 * На входе снимаем запрос и время старта, саму строку пишем в
 * register_shutdown_function — к этому моменту известны пользователь,
 * длительность и результат, а thread_id остаётся тем же (одно соединение
 * на запрос). Для привязки к бинлогу пишем CONNECTION_ID() и окно
 * created_at..finished_at.
 *
 * Настройки:
 *   totallog_enabled           — рубильник
 *   totallog_log_get           — писать ли GET (по умолчанию нет)
 *   totallog_days              — срок хранения, дней (по умолчанию 90)
 *   totallog_analyzer_snippet  — сниппет-анализатор (component/table_name/description/excel_ids/raschet_ids/smens)
 *   totallog_skip_urls         — маски URL, которые не логируем (через запятую)
 *   totallog_log_service       — писать ли служебные действия (totallog_service_actions)
 *   totallog_log_modx          — писать ли действия менеджера MODX (totallog_modx_actions)
 */

if ($modx->event->name !== 'OnMODXInit') {
    return;
}

if (!$modx->getOption('totallog_enabled', null, true)) {
    return;
}

// Своя модель на месте? Во время установки и обновления самого пакета файлы
// перекладываются, и попытка подключить модель сыпет в лог «Path specified for
// package totallog is not valid» + «Could not load class: TLItem». Один запрос
// без записи в журнал не потеря, а шум в логе — потеря.
if (!is_dir(MODX_CORE_PATH . 'components/totallog/model/')) {
    return;
}

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'CLI';
if ($method === 'GET' && !$modx->getOption('totallog_log_get', null, false)) {
    return;
}

$url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

// CLI (крон, ручные скрипты): REQUEST_URI нет, и запись выглядела бы безымянной.
// Пишем то, что реально запустили — файл с аргументами.
if ($url === '' && PHP_SAPI === 'cli') {
    $argv = isset($_SERVER['argv']) && is_array($_SERVER['argv']) ? $_SERVER['argv'] : [];
    if (!$argv && isset($_SERVER['SCRIPT_FILENAME'])) {
        $argv = [$_SERVER['SCRIPT_FILENAME']];
    }
    $url = implode(' ', $argv);
}

// Не логируем сами себя и статику
$skip = trim((string)$modx->getOption('totallog_skip_urls', null, ''));
if ($skip !== '') {
    foreach (explode(',', $skip) as $mask) {
        $mask = trim($mask);
        if ($mask !== '' && strpos($url, $mask) !== false) {
            return;
        }
    }
}

/**
 * Маскируем чувствительные значения — в лог не должны попадать пароли и токены.
 */
$tlMask = function ($data) use (&$tlMask) {
    if (!is_array($data)) {
        return $data;
    }
    $secret = ['pass', 'password', 'token', 'secret', 'apikey', 'api_key', 'auth'];
    $out = [];
    foreach ($data as $k => $v) {
        $lk = strtolower((string)$k);
        $hit = false;
        foreach ($secret as $s) {
            if (strpos($lk, $s) !== false) {
                $hit = true;
                break;
            }
        }
        $out[$k] = $hit ? '***' : (is_array($v) ? $tlMask($v) : $v);
    }
    return $out;
};

/**
 * Приведение к валидному UTF-8.
 * В теле запроса могут прийти байты в чужой кодировке — MySQL отвергает такую строку,
 * и INSERT падает целиком: запрос молча не попадает в журнал. Битые байты выкидываем.
 */
$tlUtf8 = function ($str) {
    $str = (string)$str;
    if ($str === '' || preg_match('//u', $str)) {
        return $str;
    }

    return (string)@iconv('UTF-8', 'UTF-8//IGNORE', $str);
};

/**
 * Обрезка больших полей — импорт из Excel может прислать мегабайты.
 */
$tlCut = function ($str, $limit = 65535) {
    $str = (string)$str;
    if (strlen($str) <= $limit) {
        return $str;
    }
    return substr($str, 0, $limit) . "\n…[обрезано, было " . strlen($str) . " байт]";
};

// Тело запроса: для multipart/form-data php://input недоступен — там только $_POST/$_FILES
$body = '';
$ctype = isset($_SERVER['CONTENT_TYPE']) ? strtolower($_SERVER['CONTENT_TYPE']) : '';
if ($method !== 'GET' && strpos($ctype, 'multipart/form-data') === false) {
    $raw = @file_get_contents('php://input');
    if ($raw !== false) {
        $body = $raw;
    }
}
if ($body === '' && !empty($_POST)) {
    $body = json_encode($tlMask($_POST), JSON_UNESCAPED_UNICODE);
}
// Форма (application/x-www-form-urlencoded) приходит одной строкой вида
// «action=system%2Fregistry%2Fread&topic=%2Fys%2F…». Раскладываем в JSON: значения
// раскодируются (%2F → /), поле читается в таблице и в модалке, а анализатор
// получает параметры структурой — как из JSON-тела.
if ($body !== '' && strpos($ctype, 'x-www-form-urlencoded') !== false && $body[0] !== '{' && $body[0] !== '[') {
    $parsed = [];
    parse_str($body, $parsed);
    if (!empty($parsed)) {
        $body = json_encode($tlMask($parsed), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

// action — значение поля, в имени которого содержится "action"
$action = '';
foreach ($_REQUEST as $k => $v) {
    if (stripos((string)$k, 'action') !== false && !is_array($v)) {
        $action = (string)$v;
        break;
    }
}

/**
 * Действие против списка масок. Сравнивается полное имя («lusya/calc»), часть после
 * «/» («calc») и префикс, если запись списка заканчивается на «/» или «*»:
 * «system/» ловит всю ветку процессоров MODX.
 */
$tlMatch = function ($action, $list) {
    $action = strtolower(trim((string)$action));
    $list = strtolower(trim((string)$list));
    if ($action === '' || $list === '') {
        return false;
    }
    $short = strpos($action, '/') !== false ? substr($action, strrpos($action, '/') + 1) : $action;
    foreach (explode(',', $list) as $mask) {
        $mask = rtrim(trim($mask), '*');
        if ($mask === '') {
            continue;
        }
        if (substr($mask, -1) === '/') {
            if (strpos($action, $mask) === 0) {
                return true;
            }
            continue;
        }
        if ($action === $mask || $short === $mask) {
            return true;
        }
    }

    return false;
};

// Служебное действие? Пишем (нужно для «где тупит»), но пользовательский модуль такое
// не показывает. Если админ выключил — не пишем вовсе, тогда и разбираться нечего.
$service = $tlMatch($action, (string)$modx->getOption('totallog_service_actions', null, '')) ? 1 : 0;
if ($service && !$modx->getOption('totallog_log_service', null, true)) {
    return;
}

// Возня менеджера MODX — отдельный рубильник: она нужна для «кто удалил ресурс»,
// но её много.
if ($tlMatch($action, (string)$modx->getOption('totallog_modx_actions', null, ''))
    && !$modx->getOption('totallog_log_modx', null, true)) {
    return;
}

$snapshot = [
    'url'     => $tlUtf8($tlCut($url, 500)),
    'method'  => $method,
    'action'  => $tlUtf8($tlCut($action, 191)),
    'ip'      => isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] !== ''
        ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0])
        : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''),
    // Страница, с которой пришёл запрос: для XHR это адрес интерфейса, где нажали кнопку
    'referer' => isset($_SERVER['HTTP_REFERER']) ? $tlUtf8($tlCut($_SERVER['HTTP_REFERER'], 500)) : '',
    'request' => $tlUtf8($tlCut(json_encode($tlMask($_REQUEST), JSON_UNESCAPED_UNICODE))),
    'body'    => $tlUtf8($tlCut($body, 262144)),
];

// Перехват ответа API: без него в журнале «перемещено» стояло бы и там, где сервер
// отказал. Буферизуем только /api/ (ответ — небольшой JSON), храним первые 8 КБ.
if (strpos($url, '/api/') === 0) {
    ob_start(function ($buf) {
        if (!isset($GLOBALS['totallog_response'])) {
            $GLOBALS['totallog_response'] = substr($buf, 0, 8192);
        }

        return $buf;
    });
}

$startedFloat = microtime(true);
$startedAt = date('Y-m-d H:i:s', (int)$startedFloat);

// Резерв памяти: при OOM освобождаем его в самом начале shutdown, чтобы логгер
// смог доработать и не оборвал цепочку shutdown-функций (после нас идёт
// фатал-логгер gtsAPI, зарегистрированный на OnHandleRequest).
$GLOBALS['totallog_reserve'] = str_repeat('x', 262144);

register_shutdown_function(function () use ($modx, $snapshot, $startedFloat, $startedAt, $tlUtf8, $service) {
    unset($GLOBALS['totallog_reserve']);

    // Цена запроса в базе. Снимаем ДО анализатора и своего INSERT — иначе к запросу
    // приплюсуются справочники, которые читает сам журнал.
    $sqlCount = (int)$modx->executedQueries;
    $sqlTimeMs = (int)round($modx->queryTime * 1000);

    // Был ли фатал? Тогда работаем по минимуму: не зовём сниппет-анализатор,
    // чтобы не упасть второй раз и не съесть диагностику gtsAPI.
    $lastError = error_get_last();
    $isFatal = $lastError && in_array(
        $lastError['type'],
        [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR],
        true
    );

    try {
        // Повторная проверка: между началом запроса и shutdown пакет мог начать
        // переустанавливаться
        if (!is_dir(MODX_CORE_PATH . 'components/totallog/model/')) {
            return;
        }
        $modx->addPackage('totallog', MODX_CORE_PATH . 'components/totallog/model/');

        /** @var TLItem $item */
        $item = $modx->newObject('TLItem');
        if (!$item) {
            return;
        }

        // Анализатор: component / table_name / description / excel_ids / raschet_ids / smens
        $add = [];
        $snippetName = trim((string)$modx->getOption('totallog_analyzer_snippet', null, ''));
        if ($isFatal) {
            $add['description'] = 'FATAL: ' . $lastError['message']
                . ' в ' . $lastError['file'] . ':' . $lastError['line'];
            $snippetName = '';
        }
        if ($snippetName !== '') {
            $res = $modx->runSnippet($snippetName, ['snapshot' => $snapshot]);
            if (is_array($res)) {
                $add = $res;
            } elseif (is_string($res) && $res !== '') {
                $decoded = json_decode($res, true);
                if (is_array($decoded)) {
                    $add = $decoded;
                }
            }
        }

        // Исход запроса. Ответ API мог осесть в нашем буфере (см. ob_start выше)
        // либо ещё лежать в незакрытом буфере — читаем оба варианта.
        $success = 1;
        $resp = isset($GLOBALS['totallog_response']) ? $GLOBALS['totallog_response'] : '';
        if ($resp === '' && ob_get_level() > 0) {
            $resp = (string)ob_get_contents();
        }
        if ($resp !== '' && ($j = json_decode($resp, true)) && is_array($j) && array_key_exists('success', $j)) {
            if (!$j['success']) {
                $success = 0;
                $msg = isset($j['message']) ? trim((string)$j['message']) : '';
                $add['description'] = 'НЕ ВЫПОЛНЕНО: '
                    . (isset($add['description']) && $add['description'] !== '' ? $add['description'] : 'запрос отклонён')
                    . ($msg !== '' ? ' — ' . $msg : '');
            }
        }

        $threadId = 0;
        $stmt = $modx->query('SELECT CONNECTION_ID()');
        if ($stmt) {
            $threadId = (int)$stmt->fetchColumn();
        }

        $finishedFloat = microtime(true);

        foreach (['description', 'component', 'table_name', 'excel_ids', 'smens', 'raschet_ids'] as $k) {
            if (isset($add[$k])) $add[$k] = $tlUtf8($add[$k]);
        }

        $item->fromArray(array_merge([
            'modx_user_id' => (int)$modx->user->id,
            'username'     => $tlUtf8((string)$modx->user->get('username')),
            'url'          => $snapshot['url'],
            'method'       => $snapshot['method'],
            'action'       => $snapshot['action'],
            'component'    => '',
            'table_name'   => '',
            'description'  => '',
            'ip'           => $snapshot['ip'],
            'referer'      => $snapshot['referer'],
            'service'      => $service,
            'success'      => $success,
            'request'      => $snapshot['request'],
            'body'         => $snapshot['body'],
            'thread_id'    => $threadId,
            'created_at'   => $startedAt,
            'finished_at'  => date('Y-m-d H:i:s', (int)$finishedFloat),
            'duration_ms'  => (int)round(($finishedFloat - $startedFloat) * 1000),
            'sql_count'    => $sqlCount,
            'sql_time_ms'  => $sqlTimeMs,
        ], $add), '', true, true);

        $item->save();

        // Самоочистка раз в сутки — при любом запросе
        $today = date('Y-m-d');
        $cacheKey = 'totallog_cleanup_date';
        $lastClean = $modx->cacheManager->get($cacheKey, ['cache_prefix' => 'totallog/']);
        if ($lastClean !== $today) {
            $days = (int)$modx->getOption('totallog_days', null, 90);
            if ($days > 0) {
                $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
                $table = $modx->getTableName('TLItem');
                $modx->exec("DELETE FROM {$table} WHERE created_at < '{$cutoff}'");
            }
            $modx->cacheManager->set($cacheKey, $today, 0, ['cache_prefix' => 'totallog/']);
        }
    } catch (\Throwable $e) {
        // Лог не должен ронять сайт и не должен обрывать цепочку shutdown-функций:
        // следом идёт фатал-логгер gtsAPI (OnHandleRequest), он нужнее нашей записи.
        try {
            $modx->log(modX::LOG_LEVEL_ERROR, '[TotalLog] ' . $e->getMessage());
        } catch (\Throwable $e2) {
            // молча — писать уже некуда
        }
    }
});
