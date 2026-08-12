<template>
  <div class="tl-all">
    <h2 class="text-lg font-semibold mb-2">Журнал запросов</h2>

    <PVTables table="TLItem" :actions="actions" ref="tableRef" />

    <!-- Бинлог запроса -->
    <div v-if="binlog.show" class="tl-overlay" @click.self="binlog.show = false">
      <div class="tl-modal">
        <div class="tl-modal-head">
          <b>Изменения в БД за этот запрос</b>
          <button class="tl-close" @click="binlog.show = false">✕</button>
        </div>

        <div class="tl-modal-body">
          <p class="mb-2">
            Запрос <b>#{{ binlog.row.id }}</b> — {{ binlog.row.username || 'аноним' }},
            {{ binlog.row.created_at }} … {{ binlog.row.finished_at || '—' }}
            (соединение MySQL <b>{{ binlog.row.thread_id }}</b>)
          </p>

          <p class="tl-note">
            Чтение бинлога из интерфейса ещё не подключено. Пока — готовая команда:
            выполнить на сервере БД, она покажет все строки, изменённые именно этим запросом.
          </p>

          <pre class="tl-cmd">{{ binlogCommand }}</pre>

          <button class="tl-btn" @click="copyCmd">Скопировать команду</button>
          <span v-if="binlog.copied" class="tl-copied">скопировано</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { PVTables, useNotifications } from 'pvtables/dist/pvtables'
import { ref, computed } from 'vue'

// PVTables экспортирует композабл useNotifications, а не функцию notify
const { notify } = useNotifications()

const tableRef = ref()
const binlog = ref({ show: false, row: {}, copied: false })

const binlogCommand = computed(() => {
  const r = binlog.value.row || {}
  const from = r.created_at || ''
  const to = r.finished_at || r.created_at || ''
  return [
    'mysqlbinlog -v --base64-output=DECODE-ROWS \\',
    `  --start-datetime="${from}" --stop-datetime="${to}" \\`,
    '  /var/lib/mysql/mysql-bin.* \\',
    `  | awk '/thread_id=${r.thread_id}/,0'`,
  ].join('\n')
})

const copyCmd = async () => {
  try {
    await navigator.clipboard.writeText(binlogCommand.value)
    binlog.value.copied = true
    setTimeout(() => { binlog.value.copied = false }, 2000)
  } catch (e) {
    notify('error', { detail: 'Не удалось скопировать' })
  }
}

const actions = ref({
  TLItem: {
    binlog: {
      row: true,
      label: 'Изменения в БД',
      menu: 1,
      icon: 'pi pi-database',
      class: 'p-button-rounded p-button-info',
      click: (data) => {
        binlog.value = { show: true, row: data, copied: false }
      },
    },
  },
})
</script>

<style scoped>
.tl-all { padding: 1rem; }

.tl-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.45);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.tl-modal {
  background: #fff; border-radius: .5rem; width: min(760px, 92vw);
  max-height: 85vh; overflow: auto; box-shadow: 0 10px 40px rgba(0,0,0,.25);
}
.tl-modal-head {
  display: flex; justify-content: space-between; align-items: center;
  padding: .75rem 1rem; border-bottom: 1px solid #e5e7eb;
}
.tl-modal-body { padding: 1rem; }
.tl-close { border: none; background: none; cursor: pointer; font-size: 1rem; }
.tl-note { color: #6b7280; font-size: .875rem; margin-bottom: .5rem; }
.tl-cmd {
  background: #0f172a; color: #e2e8f0; padding: .75rem; border-radius: .375rem;
  font-size: .8rem; overflow-x: auto; white-space: pre;
}
.tl-btn {
  margin-top: .75rem; padding: .4rem .8rem; border-radius: .375rem;
  border: 1px solid #d1d5db; background: #f9fafb; cursor: pointer;
}
.tl-copied { margin-left: .5rem; color: #16a34a; font-size: .875rem; }
</style>
