/**
 * Справочные данные пакета. Разложены ПО САЙТАМ — так же, как страницы в resources.js.
 *
 * Эти доп. поля (заказы/расчёты/смены) осмысленны только на наших инсталляциях: их
 * заполняет сниппет-анализатор, разбирающий действия наших же компонентов. На чужом
 * сайте, куда пакет попадёт из репозитория, колонки не создаются — журнал работает
 * и без них.
 *
 * Ключ — метка сайта (системная настройка `gtsapi_site_key`), можно перечислить через
 * запятую. Поддерживаются также хост и `sha1:<хеш хоста>`; `'*'` — блок для всех
 * остальных сайтов (здесь его намеренно нет).
 */
export default {
    'modx28,vk24': {
        gtsapi: {
            gtsAPIField: {
                key: "name",
                rows: [
                    {
                        "title": "Заказы",
                        "name": "excel_ids",
                        "dbtype": "varchar",
                        "dbprecision": "191",
                        "dbnull": 1,
                        "dbdefault": "",
                        "dbindex": "INDEX",
                        "after_field": "component",
                        "rank": 1,
                        "default": "",
                        "field_type": "text",
                        "gtsapi_config": "",
                        "description": "Доп. поля TotalLog",
                        "modal_only": 0,
                        "table_only": 0,
                    },
                    {
                        // Ид расчётов (gsRaschet), а НЕ номера заказов — номер заказа это excel_id
                        "title": "Расчёты",
                        "name": "raschet_ids",
                        "dbtype": "varchar",
                        "dbprecision": "191",
                        "dbnull": 1,
                        "dbdefault": "",
                        "dbindex": "INDEX",
                        "after_field": "excel_ids",
                        "rank": 2,
                        "default": "",
                        "field_type": "text",
                        "gtsapi_config": "",
                        "description": "Доп. поля TotalLog",
                        "modal_only": 0,
                        "table_only": 0,
                    },
                    {
                        "title": "Смены",
                        "name": "smens",
                        "dbtype": "varchar",
                        "dbprecision": "191",
                        "dbnull": 1,
                        "dbdefault": "",
                        "dbindex": "no",
                        "after_field": "raschet_ids",
                        "rank": 3,
                        "default": "",
                        "field_type": "text",
                        "gtsapi_config": "",
                        "description": "Доп. поля TotalLog",
                        "modal_only": 0,
                        "table_only": 0,
                    },
                ]
            },
            gtsAPIFieldGroup: {
                key: "name",
                rows: [
                    {
                        name: "Доп. поля TotalLog",
                        from_table: "gtsAPIField",
                        link_group_table: "gtsAPIFieldGroupLink",
                        all: 0,
                    },
                ]
            },
            gtsAPIFieldGroupLink: {
                type: "link",
                rows: [
                    {
                        group_field_id: { key: "name", table: "gtsAPIFieldGroup", name: "Доп. поля TotalLog" },
                        field_id: { key: "name", table: "gtsAPIField", name: "excel_ids" },
                    },
                    {
                        group_field_id: { key: "name", table: "gtsAPIFieldGroup", name: "Доп. поля TotalLog" },
                        field_id: { key: "name", table: "gtsAPIField", name: "raschet_ids" },
                    },
                    {
                        group_field_id: { key: "name", table: "gtsAPIFieldGroup", name: "Доп. поля TotalLog" },
                        field_id: { key: "name", table: "gtsAPIField", name: "smens" },
                    },
                ]
            },
            gtsAPIFieldTable: {
                key: "name_table",
                rows: [
                    {
                        name_table: "TLItem",
                        add_base: 1,
                        add_table: 1,
                        only_text: 0,
                        after_field: "component",
                        desc: "Доп. поля TotalLog",
                    },
                ]
            },
            gtsAPIFieldGroupTableLink: {
                type: "link",
                rows: [
                    {
                        group_field_id: { key: "name", table: "gtsAPIFieldGroup", name: "Доп. поля TotalLog" },
                        table_field_id: { key: "name_table", table: "gtsAPIFieldTable", name_table: "TLItem" },
                    },
                ]
            },
        },
    },
}
