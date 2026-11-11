<script setup lang="ts">
import { useRouter } from 'vue-router'
import AppUpdatePrompt from '@/components/AppUpdatePrompt.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const buildTime = new Date(__APP_BUILD_TIME__).toLocaleString(undefined, {
  dateStyle: 'medium',
  timeStyle: 'short',
})

async function signOut(): Promise<void> {
  await auth.signOut()
  await router.replace('/login')
}
</script>

<template>
  <header class="app-header">
    <RouterLink to="/" class="brand">
      <img src="/logo.svg" alt="" width="28" height="28" />
      Personal Tools
    </RouterLink>
    <nav v-if="auth.isSignedIn">
      <RouterLink to="/devices">Devices</RouterLink>
      <button type="button" class="link" @click="signOut">Sign out</button>
    </nav>
  </header>

  <RouterView />

  <footer class="app-footer muted">
    Version {{ buildTime }} · <RouterLink to="/install">Install</RouterLink>
  </footer>

  <AppUpdatePrompt />
</template>

<style scoped>
.app-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: calc(0.75rem + env(safe-area-inset-top)) 1rem 0.75rem;
  border-bottom: 1px solid var(--border);
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: var(--text);
  text-decoration: none;
}

nav {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.app-footer {
  padding: 2rem 1rem calc(5rem + env(safe-area-inset-bottom));
  text-align: center;
  font-size: 0.8rem;
}
</style>
