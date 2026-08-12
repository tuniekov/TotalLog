const baseFields = {
    id: { type: 'view' },
    created_at: { label: 'Время', type: 'datetime', readonly: 1 },
    username: { label: 'Пользователь', type: 'text', readonly: 1 },
    component: { label: 'Компонент', type: 'text', readonly: 1 },
    description: { label: 'Что произошло', type: 'textarea', readonly: 1 },
    excel_ids: { label: 'Заказы', type: 'text', readonly: 1 },
    smens: { label: 'Смены', type: 'text', readonly: 1 },
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
                version: 2,
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
                        action: { label: 'Действие', type: 'text', readonly: 1 },
                        method: { label: 'Метод', type: 'text', readonly: 1 },
                        url: { label: 'URL', type: 'text', readonly: 1 },
                        ip: { label: 'IP', type: 'text', readonly: 1 },
                        modx_user_id: { label: 'ID юзера', type: 'number', readonly: 1, table_only: 1 },
                        duration_ms: { label: 'Длит., мс', type: 'number', readonly: 1 },
                        thread_id: { label: 'MySQL conn', type: 'number', readonly: 1 },
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
                version: 2,
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
                        sortby: { 'TLItem.created_at': 'DESC' },
                    },
                    fields: baseFields,
                },
            },
        },
    },
}
