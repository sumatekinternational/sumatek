<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '../lib/api'

const { t } = useI18n()
const rows = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    rows.value = (await api.get('/sponsors')).data.data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold mb-5">{{ t('sponsors.title') }}</h1>
    <div class="card p-0 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-start">
          <tr>
            <th class="text-start px-4 py-3">{{ t('sponsors.name') }}</th>
            <th class="text-start px-4 py-3">{{ t('sponsors.nationality') }}</th>
            <th class="text-start px-4 py-3">{{ t('sponsors.phone') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="3" class="px-4 py-6 text-slate-400">{{ t('common.loading') }}</td></tr>
          <tr v-else-if="!rows.length"><td colspan="3" class="px-4 py-6 text-slate-400">{{ t('sponsors.empty') }}</td></tr>
          <tr v-for="s in rows" :key="s.id" class="border-t border-slate-100">
            <td class="px-4 py-3">{{ s.name_ar || s.name_en }}</td>
            <td class="px-4 py-3">{{ s.nationality }}</td>
            <td class="px-4 py-3">{{ s.phone }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
