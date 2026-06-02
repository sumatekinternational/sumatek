<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuth } from '../stores/auth'
import { setLocale } from '../i18n'

const { t, locale } = useI18n()
const router = useRouter()
const auth = useAuth()

const nav = computed(() => [
  { name: 'dashboard', to: '/dashboard', perm: null },
  { name: 'eligibility', to: '/eligibility', perm: 'eligibility.check' },
  { name: 'sponsors', to: '/sponsors', perm: 'sponsor.view' },
  { name: 'workers', to: '/workers', perm: 'worker.view' },
].filter((i) => !i.perm || auth.can(i.perm)))

function toggleLang() {
  setLocale(locale.value === 'ar' ? 'en' : 'ar')
}

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen flex">
    <aside class="w-60 bg-brand-700 text-white flex flex-col">
      <div class="px-5 py-4 border-b border-white/10">
        <div class="font-bold text-lg">{{ t('app.title') }}</div>
        <div class="text-xs text-white/70">{{ t('app.subtitle') }}</div>
      </div>
      <nav class="flex-1 p-3 space-y-1">
        <router-link
          v-for="item in nav" :key="item.name" :to="item.to"
          class="block rounded-lg px-3 py-2 text-sm hover:bg-white/10"
          active-class="bg-white/15 font-medium"
        >{{ t('nav.' + item.name) }}</router-link>
      </nav>
      <div class="p-3 border-t border-white/10">
        <div class="text-xs text-white/70 mb-2 px-1">{{ auth.user?.name }}</div>
        <button class="btn-ghost w-full text-white hover:bg-white/10" @click="logout">
          {{ t('nav.logout') }}
        </button>
      </div>
    </aside>

    <main class="flex-1 flex flex-col">
      <header class="h-14 bg-white border-b border-slate-200 flex items-center justify-between px-6">
        <div class="font-semibold">{{ t('app.title') }}</div>
        <button class="btn-ghost" @click="toggleLang">🌐 {{ t('common.language') }}</button>
      </header>
      <div class="p-6 flex-1">
        <router-view />
      </div>
    </main>
  </div>
</template>
