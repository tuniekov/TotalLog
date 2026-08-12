// ВНИМАНИЕ: инсталлятор пакета сам добавляет к ключу префикс имени пакета.
// Ключ 'days' → системная настройка 'totallog_days'. Префикс здесь не писать.
export default {
    'enabled': {
        'xtype': 'combo-boolean',
        'value': 1,
        'area': 'totallog_main',
    },
    // Писать ли GET-запросы. По умолчанию нет — иначе каждый хит пишет строку в базу.
    'log_get': {
        'xtype': 'combo-boolean',
        'value': 0,
        'area': 'totallog_main',
    },
    // Сколько дней хранить лог. Самоочистка раз в сутки при любом запросе.
    'days': {
        'xtype': 'textfield',
        'value': 90,
        'area': 'totallog_main',
    },
    // Сниппет-анализатор: получает запрос, возвращает component / description / excel_ids / smens
    'analyzer_snippet': {
        'xtype': 'textfield',
        'value': 'TotalLogAnalyzer',
        'area': 'totallog_main',
    },
    // Родители и шаблоны страниц НЕ в настройках — они заданы по сайтам
    // в _build/configs/resources.js (ключ = список хостов).
    // Служебные действия: пишутся в журнал, но НЕ показываются в пользовательском
    // модуле (начцеха не нужны просмотры и статистика). В админском видны все.
    // Через запятую; сравнивается и полное имя, и часть после «/».
    'service_actions': {
        'xtype': 'textfield',
        'value': 'options,read,get,naryads_table,get_current_smena,get_smena_by_date,get_naryad_orders_info,get_workload_table,getStats_all,getStatOrders,get_dets,recalcTimeTable',
        'area': 'totallog_main',
    },
    // Маски URL, которые не логируем (через запятую, подстрокой)
    'skip_urls': {
        'xtype': 'textfield',
        'value': '/api/totallog,/api/package,/assets/,/favicon',
        'area': 'totallog_main',
    },
}
