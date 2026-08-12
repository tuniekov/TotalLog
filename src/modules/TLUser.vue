<template>
  <div class="tl-user">
    <h2 class="text-lg font-semibold mb-2">{{ title }}</h2>
    <PVTables table="TLItemUser" :filters="filters" ref="tableRef" />
  </div>
</template>

<script setup>
import { PVTables } from 'pvtables/dist/pvtables'
import { ref, computed } from 'vue'

/**
 * Пользовательский журнал: показывает только выбранные компоненты.
 *
 * Подключение на странице:
 * {'!mixVue' | snippet : [
 *   'app'=>'totallog',
 *   'config'=>[
 *     'module'=>'TLUser',
 *     'component'=>'gcNaryadLink,newsmena',
 *     'title'=>'Кто менял наряды'
 *   ]
 * ]}
 */
const props = defineProps({
  config: { type: Object, default: () => ({}) },
})

const tableRef = ref()

const title = computed(() => props.config.title || 'Что происходило')

const components = computed(() => {
  const raw = props.config.component || ''
  if (Array.isArray(raw)) return raw.filter(Boolean)
  return String(raw).split(',').map(s => s.trim()).filter(Boolean)
})

const filters = computed(() => {
  const list = components.value
  if (!list.length) return {}
  return list.length === 1
    ? { component: { value: list[0], matchMode: 'equals' } }
    : { component: { value: list, matchMode: 'in' } }
})
</script>

<style scoped>
.tl-user { padding: 1rem; }
</style>
