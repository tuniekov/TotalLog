<template>
  <!-- Селектор модуля — показывается только если модуль не задан снаружи -->
  <div v-if="ModuleNotSeted" class="card flex justify-center p-4">
    <Select
      v-model="Module"
      :options="Modules"
      optionLabel="module"
      placeholder="Выберите модуль"
      class="w-full md:w-56"
    />
  </div>

  <component
    v-if="Module && Module.module && modules[Module.module]"
    :is="modules[Module.module]"
    :config="Module"
  />
</template>

<script setup>
import { Select } from 'pvtables/dist/pvtables'
import { ref } from 'vue'
import { modules, modulesList } from './modules/index.js'

const Module = ref({ module: 'TLAll' })
const Modules = ref(modulesList)
const ModuleNotSeted = ref(true)

// Конфигурация приходит со страницы:
// {'!mixVue' | snippet : ['app'=>'totallog','config'=>['module'=>'TLUser','component'=>'gcNaryadLink,newsmena']]}
if (typeof totallogConfigs !== 'undefined' && totallogConfigs && totallogConfigs.module) {
  if (modules[totallogConfigs.module]) {
    Module.value = totallogConfigs
    ModuleNotSeted.value = false
  }
}
</script>

<style>
#totallog {
  width: 100%;
}
</style>
