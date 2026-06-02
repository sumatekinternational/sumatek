<script setup>
import { onMounted } from 'vue'
import { useAuth } from './stores/auth'

// Restore the session on hard reload if a token is present.
const auth = useAuth()
onMounted(() => {
  if (auth.isAuthenticated && !auth.user) auth.fetchMe().catch(() => {})
})
</script>

<template>
  <router-view />
</template>
