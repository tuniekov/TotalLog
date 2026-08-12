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
    // Маски URL, которые не логируем (через запятую, подстрокой)
    'skip_urls': {
        'xtype': 'textfield',
        'value': '/api/totallog,/api/package,/assets/,/favicon',
        'area': 'totallog_main',
    },
}
