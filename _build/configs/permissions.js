/**
 * Разрешения MODX, которые привозит пакет.
 *
 * Своих групп пользователей пакет НЕ создаёт — админ чужого сайта им не обрадуется,
 * да и группы у всех свои. Вместо этого привозим шаблон разрешений: админ прикрепляет
 * готовую политику к той группе, которая у него уже есть.
 *
 * Проверяются штатно — в gtsapipackages.js у таблицы указывается `permissions`.
 *
 * Ничего не удаляется при переустановке: политики уже могут висеть на группах.
 */
export default {
    'TotalLogTemplate': {
        description: 'Права компонента TotalLog',
        template_group: 'Admin',
        permissions: {
            'totallog_view': 'Просмотр журнала: только разрешённые компоненты (страница «Что происходило»)',
            'totallog_admin': 'Полный доступ к журналу запросов (все поля, экспорт, удаление)',
        },
        policies: {
            'TotalLog': {
                description: 'Просмотр журнала TotalLog',
                permissions: ['totallog_view'],
            },
            'TotalLog Admin': {
                description: 'Полный доступ к журналу TotalLog',
                permissions: ['totallog_view', 'totallog_admin'],
            },
        },
    },
}
