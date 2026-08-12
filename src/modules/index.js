// Автоматический экспорт всех модулей
import TLAll from './TLAll.vue'
import TLUser from './TLUser.vue'

export const modules = {
    TLAll,
    TLUser
}

// Список доступных модулей для селекта
export const modulesList = Object.keys(modules).map(name => ({ module: name }))
