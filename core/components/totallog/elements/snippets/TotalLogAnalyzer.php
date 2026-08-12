<?php
/**
 * TotalLogAnalyzer — анализатор запроса для TotalLog.
 *
 * Вызывается плагином TotalLog. На входе $snapshot (url, method, action, request, body).
 * Возвращает JSON: component, description, excel_ids, smens.
 *
 * Задача — писать так, чтобы понял начальник производства: не «POST /api/gcNaryadLink/512»,
 * а «Наряд Резка, смена 12.08.2026: отмечено выполнено 3 детали (заказы 2147, 2142)».
 * Поэтому id из запроса расшифровываются по справочникам (смены, наряды, сотрудники,
 * детали заказов).
 *
 * Разобраны действия: NewSmena, ZagruzkaTable, Lusya, StaffWorkload.
 * Неизвестные действия описываются обобщённо — журнал не «слепнет» на новых компонентах.
 *
 * Работает на любом сайте: если таблиц производства нет (modx28), расшифровка молча
 * пропускается и остаётся общее описание.
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

$snapshot = isset($scriptProperties['snapshot']) ? $scriptProperties['snapshot'] : [];
$url     = isset($snapshot['url']) ? (string)$snapshot['url'] : '';
$method  = isset($snapshot['method']) ? (string)$snapshot['method'] : '';
$action  = isset($snapshot['action']) ? (string)$snapshot['action'] : '';
$request = [];
if (!empty($snapshot['request'])) {
    $decoded = json_decode($snapshot['request'], true);
    if (is_array($decoded)) {
        $request = $decoded;
    }
}

// ВАЖНО: фронт PVTables шлёт полезную нагрузку JSON-телом, а в $_REQUEST остаются
// только 'q' и 'api_action'. Всё интересное (id строки, smena_id, mark, excel_id)
// лежит именно в body — поэтому работаем по объединённому набору.
$body = [];
if (!empty($snapshot['body'])) {
    $decoded = json_decode($snapshot['body'], true);
    if (is_array($decoded)) {
        $body = $decoded;
    }
}
$data = array_merge($request, $body);

$out = [
    'component'   => '',
    'description' => '',
    'excel_ids'   => '',
    'smens'       => '',
];

// ---------------------------------------------------------------------------
// Доступ к справочникам производства. На сайтах без tSklad просто ничего не резолвим.
// ---------------------------------------------------------------------------
$tlHasProd = false;
try {
    $modx->addPackage('tsklad', MODX_CORE_PATH . 'components/tsklad/model/');
    $modx->addPackage('gtsbalance', MODX_CORE_PATH . 'components/gtsbalance/model/');
    $tlHasProd = (bool)$modx->getFields('tSkladSmena');
} catch (\Throwable $e) {
    $tlHasProd = false;
}

$tlCache = [];

/** Смена → «12.08.2026 (см. 2)» */
$tlSmena = function ($id) use ($modx, &$tlCache, $tlHasProd) {
    $id = (int)$id;
    if (!$id || !$tlHasProd) return '';
    $key = 'sm' . $id;
    if (isset($tlCache[$key])) return $tlCache[$key];
    $label = '';
    try {
        if ($o = $modx->getObject('tSkladSmena', $id)) {
            $date = $o->get('date');
            $label = $date ? date('d.m.Y', strtotime($date)) : ('смена #' . $id);
            if ((int)$o->get('number') > 1) {
                $label .= ' (см. ' . (int)$o->get('number') . ')';
            }
        }
    } catch (\Throwable $e) {
    }

    return $tlCache[$key] = $label;
};

/** Наряд → «Резка» */
$tlNaryad = function ($id) use ($modx, &$tlCache, $tlHasProd) {
    $id = (int)$id;
    if (!$id || !$tlHasProd) return '';
    $key = 'nr' . $id;
    if (isset($tlCache[$key])) return $tlCache[$key];
    $label = '';
    try {
        if ($o = $modx->getObject('tSkladNaryad', $id)) {
            $label = (string)$o->get('name');
        }
    } catch (\Throwable $e) {
    }

    return $tlCache[$key] = $label;
};

/** Сотрудник → ФИО */
$tlStaff = function ($id) use ($modx, &$tlCache, $tlHasProd) {
    $id = (int)$id;
    if (!$id || !$tlHasProd) return '';
    $key = 'st' . $id;
    if (isset($tlCache[$key])) return $tlCache[$key];
    $label = '';
    try {
        if ($o = $modx->getObject('gtsBStaff', $id)) {
            $label = (string)$o->get('name');
        }
    } catch (\Throwable $e) {
    }

    return $tlCache[$key] = $label;
};

/**
 * Строки наряд-смены (tSkladDetNSLink) → марки деталей и номера заказов.
 * Возвращает ['marks' => '2147/1, 2142/4', 'excel_ids' => '2147,2142', 'count' => N]
 */
$tlDets = function (array $ids) use ($modx, $tlHasProd) {
    $res = ['marks' => '', 'excel_ids' => '', 'count' => count($ids)];
    if (!$ids || !$tlHasProd) return $res;
    try {
        $linkTbl = $modx->getTableName('tSkladDetNSLink');
        $listTbl = $modx->getTableName('tSkladOrderList');
        $ids = array_slice(array_map('intval', $ids), 0, 200);
        $in = implode(',', $ids);
        $sql = "SELECT ol.mark, ol.excel_id
                  FROM {$linkTbl} l
                  JOIN {$listTbl} ol ON ol.id = l.det_id
                 WHERE l.id IN ({$in})";
        $stmt = $modx->query($sql);
        $marks = [];
        $excel = [];
        if ($stmt) {
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($r['mark'])) $marks[$r['mark']] = true;
                if (!empty($r['excel_id'])) $excel[$r['excel_id']] = true;
            }
        }
        $marksList = array_keys($marks);
        $res['marks'] = implode(', ', array_slice($marksList, 0, 8))
            . (count($marksList) > 8 ? ' и ещё ' . (count($marksList) - 8) : '');
        $res['excel_ids'] = implode(',', array_keys($excel));
    } catch (\Throwable $e) {
    }

    return $res;
};

/** Достаём ids так же, как это делает NewSmena::extractIdsFromData (+ id строки из тела) */
$tlIds = function () use ($data) {
    $ids = [];
    if (!empty($data['data_fields_values']) && is_array($data['data_fields_values'])) {
        foreach ($data['data_fields_values'] as $field) {
            if (!isset($field['ids'])) continue;
            $v = $field['ids'];
            $ids = array_merge($ids, is_array($v) ? $v : array_map('trim', explode(',', (string)$v)));
        }
    }
    foreach (['ids', 'id', 'link_id'] as $k) {
        if (!empty($data[$k])) {
            $v = $data[$k];
            $ids = array_merge($ids, is_array($v) ? $v : array_map('trim', explode(',', (string)$v)));
        }
    }

    return array_values(array_unique(array_filter($ids, 'strlen')));
};

/**
 * Марки, заказы и смены, которые УЖЕ есть в payload'е — фронт присылает строку целиком.
 * Это точнее и дешевле похода в базу; в базу идём только если в payload'е пусто.
 */
$tlPayloadRows = function () use ($data) {
    $res = ['marks' => [], 'excel' => [], 'smens' => [], 'rows' => 0];
    $walk = function ($arr) use (&$walk, &$res) {
        if (!is_array($arr)) return;
        $isRow = isset($arr['mark']) || isset($arr['excel_id']) || isset($arr['det_id']);
        if ($isRow) {
            $res['rows']++;
            if (!empty($arr['mark'])) $res['marks'][(string)$arr['mark']] = true;
            if (!empty($arr['excel_id'])) $res['excel'][(string)$arr['excel_id']] = true;
            if (!empty($arr['smena_id'])) $res['smens'][(int)$arr['smena_id']] = true;
        }
        foreach ($arr as $v) {
            if (is_array($v)) $walk($v);
        }
    };
    $walk($data);

    return $res;
};

/** Первое непустое значение по списку ключей (рекурсивно) */
$pick = function ($keys) use ($data) {
    $walk = function ($arr, $keys) use (&$walk) {
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $found = $walk($v, $keys);
                if ($found !== null) return $found;
            } elseif (in_array((string)$k, $keys, true) && $v !== '' && $v !== null) {
                return $v;
            }
        }
        return null;
    };

    return $walk($data, $keys);
};

$val = function ($key, $default = '') use ($data) {
    return isset($data[$key]) && $data[$key] !== '' ? $data[$key] : $default;
};

/** Заглавная первая буква. ucfirst() кириллицу не берёт — она многобайтовая. */
$tlUp = function ($s) {
    $s = (string)$s;

    return $s === '' ? '' : mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($s, 1, null, 'UTF-8');
};

// ---------------------------------------------------------------------------
// Компонент
// ---------------------------------------------------------------------------
$method_ = '';
if ($action !== '' && strpos($action, '/') !== false) {
    list($out['component'], $method_) = array_pad(explode('/', $action, 2), 2, '');
} elseif (preg_match('~/api/([A-Za-z0-9_]+)~', $url, $m)) {
    $out['component'] = $m[1];
} elseif (preg_match('~^/([A-Za-z0-9_-]+)~', $url, $m)) {
    $out['component'] = $m[1];
}

// ---------------------------------------------------------------------------
// Разбор действий производственных пакетов
// ---------------------------------------------------------------------------
$smensOut = [];
$addSmena = function ($id) use (&$smensOut, $tlSmena) {
    $label = $tlSmena($id);
    if ($label !== '') $smensOut[$label] = true;

    return $label;
};

/**
 * Сводка по затронутым строкам: сколько позиций, какие марки, какие заказы.
 * Сначала берём то, что уже прислал фронт, и только если там пусто — идём в базу.
 */
$tlCollect = function () use ($tlIds, $tlDets, $tlPayloadRows, $addSmena) {
    $p = $tlPayloadRows();
    foreach (array_keys($p['smens']) as $sid) {
        $addSmena($sid);
    }

    $ids = $tlIds();
    $count = $p['rows'] > 0 ? $p['rows'] : count($ids);
    $marks = array_keys($p['marks']);
    $excel = array_keys($p['excel']);

    if (!$marks && $ids) {
        $d = $tlDets($ids);
        if ($d['marks'] !== '') $marks = [$d['marks']];
        if ($d['excel_ids'] !== '') $excel = explode(',', $d['excel_ids']);
        if ($d['count']) $count = $d['count'];
    }

    return [
        'count'     => $count,
        'marks'     => implode(', ', array_slice($marks, 0, 8))
            . (count($marks) > 8 ? ' и ещё ' . (count($marks) - 8) : ''),
        'excel_ids' => implode(',', $excel),
    ];
};

$key = $out['component'] . '/' . $method_;

switch ($key) {

    // ======================= NewSmena — работа со сменой =======================
    case 'newsmena/set_done':
    case 'newsmena/set_undone':
    case 'newsmena/toggle_done':
        $dets = $tlCollect();
        $what = $method_ === 'set_undone' ? 'снята отметка «выполнено»'
            : ($method_ === 'set_done' ? 'отмечено «выполнено»' : 'переключена отметка «выполнено»');
        $out['description'] = $tlUp($what) . ': ' . $dets['count'] . ' поз.'
            . ($dets['marks'] !== '' ? ' — ' . $dets['marks'] : '');
        $out['excel_ids'] = $dets['excel_ids'];
        break;

    case 'newsmena/move_to_smena':
        $dets = $tlCollect();
        $to = $addSmena($val('smena_id'));
        $out['description'] = 'Перемещено на смену ' . ($to ?: '#' . $val('smena_id'))
            . ': ' . $dets['count'] . ' поз.'
            . ($dets['marks'] !== '' ? ' — ' . $dets['marks'] : '')
            . ($val('move_next') ? ' (со сдвигом следующих)' : '');
        $out['excel_ids'] = $dets['excel_ids'];
        break;

    case 'newsmena/set_rezka_block':
    case 'newsmena/set_rezka_unblock':
    case 'newsmena/toggle_rezka_block':
        $dets = $tlCollect();
        $what = $method_ === 'set_rezka_unblock' ? 'снята блокировка резки'
            : ($method_ === 'set_rezka_block' ? 'поставлена блокировка резки' : 'переключена блокировка резки');
        $out['description'] = $tlUp($what) . ': ' . $dets['count'] . ' поз.'
            . ($dets['marks'] !== '' ? ' — ' . $dets['marks'] : '');
        $out['excel_ids'] = $dets['excel_ids'];
        break;

    case 'newsmena/generate_print_html':
        $out['description'] = 'Печать наряда';
        break;

    case 'newsmena/naryads_table':
    case 'newsmena/get_naryad_orders_info':
    case 'newsmena/get_current_smena':
    case 'newsmena/get_smena_by_date':
        $out['description'] = 'Просмотр данных смены';
        $addSmena($val('smena_id'));
        break;

    // ======================= ZagruzkaTable — таблица загрузки =======================
    case 'zagruzkatable/moveNaryadToSmena':
        $from = $addSmena($val('smena_id'));
        $to = $addSmena($val('target_smena_id'));
        $nar = $tlNaryad($val('naryad_id'));
        $out['description'] = 'Наряд ' . ($nar ?: '#' . $val('naryad_id'))
            . ' перемещён со смены ' . ($from ?: '?') . ' на ' . ($to ?: '?');
        break;

    case 'zagruzkatable/moveNaryadDetailToSmena':
        $from = $addSmena($val('smena_id'));
        $to = $addSmena($val('target_smena_id'));
        $nar = $tlNaryad($val('naryad_id'));
        $out['description'] = 'Детали наряда ' . ($nar ?: '#' . $val('naryad_id'))
            . ' перемещены со смены ' . ($from ?: '?') . ' на ' . ($to ?: '?');
        $out['excel_ids'] = (string)$val('sk_order_id');
        break;

    case 'zagruzkatable/copyTabel':
        $from = $addSmena($val('smena_id'));
        $to = $addSmena($val('target_smena_id'));
        $nar = $tlNaryad($val('naryad_id'));
        $out['description'] = 'Табель наряда ' . ($nar ?: '#' . $val('naryad_id'))
            . ' скопирован со смены ' . ($from ?: '?') . ' на ' . ($to ?: '?');
        break;

    case 'zagruzkatable/moveStaff':
        $from = $addSmena($val('smena_id'));
        $to = $addSmena($val('target_smena_id'));
        $out['description'] = 'Сотрудник ' . ($tlStaff($val('staff_id')) ?: '#' . $val('staff_id'))
            . ' переведён с наряда ' . ($tlNaryad($val('naryad_id')) ?: '?')
            . ' (' . ($from ?: '?') . ') на наряд ' . ($tlNaryad($val('target_naryad_id')) ?: '?')
            . ' (' . ($to ?: '?') . ')';
        break;

    case 'zagruzkatable/koef_time':
        $sm = $addSmena($val('smena_id'));
        $out['description'] = 'Изменён коэффициент времени наряда '
            . ($tlNaryad($val('naryad_id')) ?: '#' . $val('naryad_id'))
            . ($sm ? ', смена ' . $sm : '')
            . ' → ' . $val('koef_time');
        break;

    case 'zagruzkatable/recalcTimeTable':
        $out['description'] = 'Пересчёт таблицы загрузки'
            . ($val('year') ? ' за ' . $val('year') . ' г.' : '');
        break;

    case 'zagruzkatable/getStatOrders':
    case 'zagruzkatable/getStats_all':
        $out['description'] = 'Просмотр статистики загрузки';
        break;

    // ======================= Lusya — распределение по сменам =======================
    case 'lusya/calc':
    case 'lusya/calc_not_stop':
        $out['description'] = 'Люся: распределение деталей по сменам'
            . ($val('start_date') ? ' с ' . $val('start_date') : '')
            . ($val('end_date') ? ' по ' . $val('end_date') : '')
            . ($val('mark') ? ', марка ' . $val('mark') : '')
            . ($method_ === 'calc_not_stop' ? ' (без остановки)' : '');
        $out['excel_ids'] = (string)$val('excel_id', $val('sk_order_id'));
        break;

    case 'lusya/add_naryads':
        $out['description'] = 'Люся: добавление нарядов'
            . ($val('start_date') ? ' с ' . $val('start_date') : '')
            . ($val('end_date') ? ' по ' . $val('end_date') : '')
            . ($val('mark') ? ', марка ' . $val('mark') : '');
        break;

    case 'lusya/restore':
        $out['description'] = 'Люся: откат распределения'
            . ($val('full_restore') ? ' (полный)' : '');
        break;

    case 'lusya/full_restore':
        $out['description'] = 'Люся: полный откат распределения';
        break;

    case 'lusya/pereraschet_rezerv_time':
        $out['description'] = 'Люся: пересчёт резервного времени';
        break;

    // ======================= StaffWorkload — расстановка людей =======================
    case 'staffworkload/move_staff_to_naryad':
        $to = $addSmena($val('smena_id'));
        $from = $addSmena($val('from_smena_id'));
        $out['description'] = 'Сотрудник ' . ($tlStaff($val('staff_id')) ?: '#' . $val('staff_id'))
            . ' поставлен на наряд ' . ($tlNaryad($val('naryad_id')) ?: '#' . $val('naryad_id'))
            . ($to ? ', смена ' . $to : '')
            . ($val('from_naryad_id') ? ' (был на ' . ($tlNaryad($val('from_naryad_id')) ?: '?')
                . ($from ? ', ' . $from : '') . ')' : '')
            . ($val('reserve_time') ? ', резерв ' . $val('reserve_time') : '');
        break;

    case 'staffworkload/update_staff_in_naryad':
        $sm = $addSmena($val('smena_id'));
        $out['description'] = 'Изменена расстановка: ' . ($tlStaff($val('staff_id')) ?: '#' . $val('staff_id'))
            . ' на наряде ' . ($tlNaryad($val('naryad_id')) ?: '#' . $val('naryad_id'))
            . ($sm ? ', смена ' . $sm : '')
            . ($val('teor_ktu') !== '' ? ', КТУ ' . $val('teor_ktu') : '')
            . ($val('reserve_time') !== '' ? ', резерв ' . $val('reserve_time') : '');
        break;

    case 'staffworkload/remove_staff_from_naryad':
        $sm = $addSmena($val('smena_id'));
        $out['description'] = 'Сотрудник ' . ($tlStaff($val('staff_id')) ?: '#' . $val('staff_id'))
            . ' снят с наряда ' . ($tlNaryad($val('naryad_id')) ?: '#' . $val('naryad_id'))
            . ($sm ? ', смена ' . $sm : '');
        break;

    case 'staffworkload/toggle_day_off':
        $sm = $addSmena($val('smena_id'));
        $out['description'] = ($val('day_off') ? 'Смена отмечена выходным' : 'С смены снят признак выходного')
            . ($sm ? ': ' . $sm : '');
        break;

    case 'staffworkload/get_workload_table':
        $out['description'] = 'Просмотр расстановки людей';
        break;

    // ======================= Всё остальное =======================
    default:
        if ($action !== '') {
            $out['description'] = 'Действие: ' . $action;
        } else {
            $wordByMethod = ['PUT' => 'Создано', 'POST' => 'Изменено', 'DELETE' => 'Удалено'];
            if (isset($wordByMethod[$method]) && $out['component'] !== '') {
                $out['description'] = $wordByMethod[$method] . ' в «' . $out['component'] . '»';
            }
        }
        // Обобщённо вытаскиваем заказ и смену, если они есть в запросе
        $excel = $pick(['excel_id', 'excel_ids', 'order_id', 'sk_order_id', 'raschet_id']);
        if ($excel !== null) {
            $out['excel_ids'] = is_array($excel) ? implode(',', $excel) : (string)$excel;
        }
        $smena = $pick(['smena_id', 'smena_ids']);
        if ($smena !== null && !is_array($smena)) {
            $addSmena($smena);
        }
        break;
}

if ($smensOut) {
    $out['smens'] = implode(', ', array_keys($smensOut));
}

// Хвост описания: заказы и смены — чтобы строка читалась целиком
if ($out['excel_ids'] !== '' && stripos($out['description'], 'заказ') === false) {
    $out['description'] .= ' · заказ ' . $out['excel_ids'];
}
if ($out['smens'] !== '' && stripos($out['description'], 'смен') === false) {
    $out['description'] .= ' · смена ' . $out['smens'];
}

return json_encode($out, JSON_UNESCAPED_UNICODE);
