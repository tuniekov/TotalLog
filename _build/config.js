export default {
    name:"TotalLog",
    name_lower:"totallog",
    version:"1.0.0",
    release:"beta",
    schema:true,
    assets:true,
    core:true,
    update:{
        snippets: true,
        plugins: true,
        chunks: true,
        templates: true,
        // ВАЖНО: settings НЕ обновляем при каждом деплое — иначе сбрасываются
        // значения, выставленные админом (срок хранения, имя сниппета и т.д.).
        // На первой установке настройки всё равно создаются.
        settings: false,
        menus: true,
        gtsapipackages: true,
    }
}
