<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '../lib/api'

const { t } = useI18n()
const rows = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    rows.value = (await api.get('/workers')).data.data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold mb-5">{{ t('workers.title') }}</h1>
    <div class="card p-0 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500">
          <tr>
            <th class="text-start px-4 py-3">{{ t('workers.name') }}</th>
            <th class="text-start px-4 py-3">{{ t('workers.nationality') }}</th>
            <th class="text-start px-4 py-3">{{ t('workers.status') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="3" class="px-4 py-6 text-slate-400">{{ t('common.loading') }}</td></tr>
          <tr v-else-if="!rows.length"><td colspan="3" class="px-4 py-6 text-slate-400">{{ t('workers.empty') }}</td></tr>
          <tr v-for="w in rows" :key="w.id" class="border-t border-slate-100">
            <td class="px-4 py-3">{{ w.name_en || w.name_ar }}</td>
            <td class="px-4 py-3">{{ w.nationality }}</td>
            <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ w.status }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
