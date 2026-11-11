<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppUpdatePrompt from '@/components/AppUpdatePrompt.vue'
import { useAppsStore } from '@/stores/apps'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const appsStore = useAppsStore()
const route = useRoute()
const router = useRouter()

/** The app the current page belongs to, shown next to the brand. */
const currentApp = computed(() => (route.meta.app ? appsStore.find(route.meta.app) : undefined))

const version = import.meta.env.DEV ? `${__APP_VERSION__}-dev` : __APP_VERSION__
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
    <div class="brand">
      <RouterLink to="/" class="home-link">
        <img src="/logo.svg" alt="" width="28" height="28" />
        <span :class="{ 'hide-narrow': currentApp }">Web Apps</span>
      </RouterLink>
      <template v-if="currentApp">
        <span class="separator" aria-hidden="true">/</span>
        <span class="app-name">{{ currentApp.name }}</span>
      </template>
    </div>
    <nav v-if="auth.isSignedIn">
      <RouterLink to="/devices">Devices</RouterLink>
      <button type="button" class="link" @click="signOut">Sign out</button>
    </nav>
  </header>

  <RouterView />

  <footer class="app-footer muted">
    <span :title="`Built ${buildTime}`">Web Apps v{{ version }}</span> ·
    <RouterLink to="/install">Install</RouterLink>
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
  min-width: 0;
  font-weight: 600;
}

.home-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text);
  text-decoration: none;
}

.home-link img {
  border-radius: 7px;
}

.separator {
  color: var(--muted);
  font-weight: 400;
}

.app-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* On phones, the logo alone links home once an app name is shown.
   Visually hidden rather than display:none, so the link keeps its name. */
@media (max-width: 30rem) {
  .hide-narrow {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip-path: inset(50%);
    white-space: nowrap;
  }
}

nav {
  display: flex;
  align-items: center;
  gap: 1rem;
}

nav a {
  text-decoration: none;
}

.app-footer {
  padding: 2rem 1rem calc(5rem + env(safe-area-inset-bottom));
  text-align: center;
  font-size: 0.8rem;
}
</style>
