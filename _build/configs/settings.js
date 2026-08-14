// ВНИМАНИЕ: инсталлятор пакета сам добавляет к ключу префикс имени пакета.
// Ключ 'days' → системная настройка 'totallog_days'. Префикс здесь не писать.
export default {
    // Конфигурация страницы админки. В параметрах пункта меню JSON писать НЕЛЬЗЯ:
    // меню менеджера MODX рендерится через Smarty, и фигурные скобки он считает
    // своим синтаксисом — страница падает с "Syntax error in template".
    // Поэтому JSON лежит здесь, а меню ссылается на имя настройки.
    'admin': {
        'xtype': 'textarea',
        'value': '{"title":"Журнал запросов","mixVue":{"app":"totallog","config":{"module":"TLAll"}}}',
        'area': 'totallog_main',
    },
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
    // === Что вообще писать ===
    // Журнал закрывает три задачи: кто что делал, где ошибки компонентов, где тормозит.
    // Поэтому шум выключается по частям, а не одним рубильником: служебные действия
    // и возня менеджера MODX — это разный шум, и нужны они в разных случаях.
    //
    // Служебные действия (просмотры, автокомплиты, статистика).
    // Пишутся — они нужны для «где тупит», — но в пользовательском модуле не видны:
    // начцеха просмотры не интересуют. Выключить запись: log_service = 0.
    'log_service': {
        'xtype': 'combo-boolean',
        'value': 1,
        'area': 'totallog_main',
    },
    // Через запятую. Сравнивается полное имя, часть после «/» и префикс (если запись
    // заканчивается на «/» или «*»).
    'service_actions': {
        'xtype': 'textfield',
        'value': 'options,read,get,autocomplete,naryads_table,get_current_smena,get_smena_by_date,get_naryad_orders_info,get_workload_table,getStats_all,getStatOrders,get_dets,recalcTimeTable',
        'area': 'totallog_main',
    },
    // Действия менеджера MODX (/connectors/index.php): дерево ресурсов, реестр, списки.
    // Нужны для «кто удалил ресурс», но их много и они забивают журнал —
    // выключается отдельно: log_modx = 0.
    'log_modx': {
        'xtype': 'combo-boolean',
        'value': 1,
        'area': 'totallog_main',
    },
    // Ветки процессоров MODX. Запись с «/» на конце = префикс: «system/» ловит
    // system/registry/register/read и всё остальное из этой ветки.
    'modx_actions': {
        'xtype': 'textfield',
        'value': 'system/,resource/,element/,security/,workspace/,context/,browser/,source/,mgr/',
        'area': 'totallog_main',
    },
    // Маски URL, которые не логируем (через запятую, подстрокой)
    'skip_urls': {
        'xtype': 'textfield',
        // ⚠️ НЕ ставить сюда '/assets/': legacy-компоненты (ZagruzkaTable, Lusya и др.)
        // шлют действия на /assets/components/<пакет>/action.php — они бы не логировались.
        // Статика (css/js/картинки) MODX не бутстрапит, её глушить не надо.
        'value': '/api/totallog,/api/package,/favicon',
        'area': 'totallog_main',
    },
}
