<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '../lib/api'

const { t } = useI18n()
const data = ref(null)
const loading = ref(true)

function fils(v) {
  return ((v ?? 0) / 1000).toLocaleString(undefined, { minimumFractionDigits: 3 })
}

onMounted(async () => {
  try {
    data.value = (await api.get('/dashboard')).data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold mb-5">{{ t('dashboard.title') }}</h1>
    <div v-if="loading" class="text-slate-500">{{ t('common.loading') }}</div>
    <div v-else-if="data" class="grid grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="card"><div class="text-sm text-slate-500">{{ t('dashboard.workers') }}</div>
        <div class="text-3xl font-bold mt-1">{{ data.workers.total }}</div></div>
      <div class="card"><div class="text-sm text-slate-500">{{ t('dashboard.active_contracts') }}</div>
        <div class="text-3xl font-bold mt-1">{{ data.contracts.active }}</div></div>
      <div class="card"><div class="text-sm text-slate-500">{{ t('dashboard.open_visa') }}</div>
        <div class="text-3xl font-bold mt-1">{{ data.visa_pipeline.open }}</div></div>
      <div class="card"><div class="text-sm text-slate-500">{{ t('dashboard.sla_breached') }}</div>
        <div class="text-3xl font-bold mt-1 text-amber-600">{{ data.visa_pipeline.sla_breached }}</div></div>
      <div class="card"><div class="text-sm text-slate-500">{{ t('dashboard.outstanding') }}</div>
        <div class="text-3xl font-bold mt-1">{{ fils(data.billing.outstanding_fils) }}</div></div>
      <div class="card"><div class="text-sm text-slate-500">{{ t('dashboard.blocked_by_us') }}</div>
        <div class="text-3xl font-bold mt-1 text-red-600">{{ data.sponsors.blocked_by_us }}</div></div>
    </div>
  </div>
</template>
