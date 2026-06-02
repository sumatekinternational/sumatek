<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '../lib/api'

const { t } = useI18n()
const civilId = ref('')
const result = ref(null)
const loading = ref(false)
const error = ref('')

const theme = computed(() => ({
  clear: 'bg-emerald-50 border-emerald-300 text-emerald-800',
  caution: 'bg-amber-50 border-amber-300 text-amber-800',
  blocked: 'bg-red-50 border-red-300 text-red-800',
}[result.value?.status] || 'bg-slate-50 border-slate-200'))

async function check() {
  if (!civilId.value) return
  loading.value = true
  error.value = ''
  result.value = null
  try {
    result.value = (await api.post('/eligibility/check', { civil_id: civilId.value })).data
  } catch (e) {
    error.value = e.response?.data?.message || 'Error'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-2xl">
    <h1 class="text-2xl font-bold mb-5">{{ t('eligibility.title') }}</h1>

    <div class="card">
      <label class="label">{{ t('eligibility.civil_id') }}</label>
      <div class="flex gap-2">
        <input v-model="civilId" class="input" :placeholder="t('eligibility.placeholder')"
               @keyup.enter="check" inputmode="numeric" />
        <button class="btn-primary" :disabled="loading" @click="check">
          {{ loading ? t('common.loading') : t('eligibility.check') }}
        </button>
      </div>
      <p v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</p>
    </div>

    <div v-if="result" class="card mt-4 border-2" :class="theme">
      <div class="flex items-center justify-between">
        <div class="text-2xl font-extrabold tracking-wide">
          {{ t('eligibility.status.' + result.status) }}
        </div>
        <div class="font-mono text-lg">{{ result.civil_id_masked }}</div>
      </div>
      <p class="mt-2 font-medium">{{ result.prompt }}</p>
      <p v-if="result.status !== 'clear'" class="mt-1 text-sm">
        {{ t('eligibility.blocked_by', { n: result.blocked_by_agencies }) }}
      </p>

      <details class="mt-4 text-xs opacity-80">
        <summary class="cursor-pointer font-semibold">{{ t('eligibility.disclaimer') }}</summary>
        <p class="mt-2 leading-relaxed">{{ result.disclaimer }}</p>
      </details>
    </div>
  </div>
</template>
