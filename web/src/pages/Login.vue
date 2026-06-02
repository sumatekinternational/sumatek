<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuth } from '../stores/auth'
import { setLocale } from '../i18n'

const { t, locale } = useI18n()
const router = useRouter()
const auth = useAuth()

const email = ref('a.admin@agency.test')
const password = ref('password123')
const error = ref('')
const loading = ref(false)

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.message || t('login.error')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-brand-700 to-brand-500 p-4">
    <div class="card w-full max-w-sm">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-bold">{{ t('login.title') }}</h1>
        <button class="btn-ghost text-sm" @click="setLocale(locale === 'ar' ? 'en' : 'ar')">
          🌐 {{ t('common.language') }}
        </button>
      </div>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="label">{{ t('login.email') }}</label>
          <input v-model="email" type="email" class="input" autocomplete="username" />
        </div>
        <div>
          <label class="label">{{ t('login.password') }}</label>
          <input v-model="password" type="password" class="input" autocomplete="current-password" />
        </div>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
        <button class="btn-primary w-full" :disabled="loading">
          {{ loading ? t('common.loading') : t('login.submit') }}
        </button>
      </form>
    </div>
  </div>
</template>
