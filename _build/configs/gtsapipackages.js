const baseFields = {
    id: { type: 'view' },
    created_at: { label: 'Время', type: 'datetime', readonly: 1 },
    // Показываем ФИО, а не логин: username в базе остаётся (запись переживает удаление
    // пользователя), но читать журнал удобнее по человеку
    modx_user_id: { label: 'Пользователь', type: 'autocomplete', table: 'modUserTotalLog', readonly: 1 },
    component: { label: 'Компонент', type: 'text', readonly: 1 },
    success: { label: 'OK', type: 'boolean', readonly: 1 },
    description: { label: 'Что произошло', type: 'textarea', readonly: 1 },
    // Динамические поля gtsAPI (excel_ids, raschet_ids, smens) здесь НЕ перечисляем:
    // их список живёт в _build/configs/data.js и может пополняться прямо на сайте.
    // В конфиг они попадают триггером gtsapi_rule — TotalLog::ruleTLItem().
}

export default {
    totallog: {
        name: 'totallog',
        gtsAPITables: {
            // === Админский журнал: все поля ===
            TLItem: {
                table: 'TLItem',
                class: 'TLItem',
                autocomplete_field: '',
                version: 6,
                type: 1,
                authenticated: true,
                // Не группы, а разрешение MODX — его привозит пакет (permissions.js),
                // админ вешает политику на СВОИ существующие группы.
                groups: '',
                permissions: 'totallog_admin',
                active: true,
                properties: {
                    loadModels: 'totallog',
                    limit: 100,
                    actions: {
                        read: {},
                        excel_export: {},
                        delete: { groups: 'Administrator' },
                    },
                    query: {
                        sortby: { 'TLItem.created_at': 'DESC' },
                    },
                    fields: {
                        ...baseFields,
                        table_name: { label: 'Таблица', type: 'text', readonly: 1 },
                        action: { label: 'Действие', type: 'text', readonly: 1 },
                        method: { label: 'Метод', type: 'text', readonly: 1 },
                        url: { label: 'URL', type: 'text', readonly: 1 },
                        ip: { label: 'IP', type: 'text', readonly: 1 },
                        username: { label: 'Логин', type: 'text', readonly: 1, modal_only: 1 },
                        duration_ms: { label: 'Длит., мс', type: 'number', readonly: 1 },
                        sql_count: { label: 'SQL, шт.', type: 'number', readonly: 1 },
                        sql_time_ms: { label: 'SQL, мс', type: 'number', readonly: 1 },
                        thread_id: { label: 'MySQL conn', type: 'number', readonly: 1 },
                        service: { label: 'Служебное', type: 'boolean', readonly: 1 },
                        finished_at: { label: 'Завершён', type: 'datetime', readonly: 1, modal_only: 1 },
                        request: { label: 'REQUEST', type: 'textarea', readonly: 1, modal_only: 1 },
                        body: { label: 'BODY', type: 'textarea', readonly: 1, modal_only: 1 },
                    },
                },
            },

            // === Пользовательский журнал: только понятные поля, фильтр по component задаёт модуль ===
            TLItemUser: {
                table: 'TLItemUser',
                class: 'TLItem',
                autocomplete_field: '',
                version: 6,
                type: 1,
                authenticated: true,
                groups: '',
                permissions: 'totallog_view',
                active: true,
                properties: {
                    loadModels: 'totallog',
                    limit: 50,
                    actions: {
                        read: {},
                    },
                    query: {
                        // Служебные действия (просмотры, options, статистика) пишутся,
                        // но начцеха они не нужны — список в totallog_service_actions
                        where: { 'TLItem.service': 0 },
                        sortby: { 'TLItem.created_at': 'DESC' },
                    },
                    fields: baseFields,
                },
            },
        },
    },

    // Справочник пользователей для колонки «Пользователь». Отдельная таблица, а не общая
    // с другими компонентами: TotalLog ставится на чужие сайты, где своих таблиц может не быть.
    modx: {
        name: 'modx',
        gtsAPITables: {
            modUserTotalLog: {
                table: 'modUserTotalLog',
                class: 'modUser',
                autocomplete_field: 'modx_user_id',
                version: 2,
                type: 1,
                authenticated: true,
                groups: '',
                // Справочник людей — не публичный: открываем тем же правом, что и журнал
                // (в политике «TotalLog Admin» оно тоже есть)
                permissions: 'totallog_view',
                active: true,
                properties: {
                    actions: {
                        read: {},
                    },
                    autocomplete: {
                        where: {
                            'modUserProfile.fullname:LIKE': '%query%',
                            'OR:modUser.username:LIKE': '%query%',
                        },
                        query: {
                            class: 'modUser',
                            leftJoin: {
                                modUserProfile: {
                                    class: 'modUserProfile',
                                    on: 'modUserProfile.internalKey = modUser.id',
                                },
                            },
                            select: {
                                modUser: 'modUser.id,modUser.username',
                                modUserProfile: 'modUserProfile.fullname',
                            },
                            sortby: {
                                fullname: 'ASC',
                            },
                        },
                        // ФИО первым — по нему журнал и читают; логин в скобках, чтобы
                        // отличать однофамильцев и находить запись по учётке
                        tpl: '{$fullname}({$username})',
                        // Не 0: при limit:0 подписи не подгружаются на страницах после первой
                        limit: 30,
                    },
                },
            },
        },
    },
}
