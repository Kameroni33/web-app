<script setup lang="ts">
import { computed, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import AppUpdatePrompt from '@/components/AppUpdatePrompt.vue'
import ProfileAvatar from '@/components/ProfileAvatar.vue'
import { useAppsStore } from '@/stores/apps'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const appsStore = useAppsStore()
const route = useRoute()

/** The app the current page belongs to, shown next to the brand. */
const currentApp = computed(() => (route.meta.app ? appsStore.find(route.meta.app) : undefined))

// Inside an app, its colours replace the brand accent (both themes, via light-dark()).
watchEffect(() => {
  const style = document.documentElement.style
  const theme = currentApp.value?.theme
  if (theme) {
    style.setProperty('--accent', `light-dark(${theme.accent[0]}, ${theme.accent[1]})`)
    style.setProperty('--accent-soft', `light-dark(${theme.accentSoft[0]}, ${theme.accentSoft[1]})`)
  } else {
    style.removeProperty('--accent')
    style.removeProperty('--accent-soft')
  }
  document
    .querySelector('meta[name="theme-color"]')
    ?.setAttribute('content', theme?.accent[0] ?? '#0f766e')
})

const version = import.meta.env.DEV ? `${__APP_VERSION__}-dev` : __APP_VERSION__
const buildTime = new Date(__APP_BUILD_TIME__).toLocaleString(undefined, {
  dateStyle: 'medium',
  timeStyle: 'short',
})
</script>

<template>
  <header class="app-header">
    <div class="brand">
      <RouterLink to="/" class="home-link">
        <img src="/logo.svg" alt="" width="28" height="28" />
        <span :class="{ 'hide-narrow': currentApp }">Kam Apps</span>
      </RouterLink>
      <template v-if="currentApp">
        <span class="separator" aria-hidden="true">/</span>
        <span class="app-name">{{ currentApp.name }}</span>
      </template>
    </div>
    <RouterLink
      v-if="auth.profile"
      :to="{ name: 'settings' }"
      class="me-link"
      :aria-label="`${auth.profile.name}, settings`"
    >
      <span class="me-name">{{ auth.profile.name }}</span>
      <ProfileAvatar :name="auth.profile.name" :id="auth.profile.id" :size="32" />
    </RouterLink>
  </header>

  <RouterView />

  <footer class="app-footer muted">
    <span :title="`Built ${buildTime}`">Kam Apps v{{ version }}</span> ·
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

.me-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
  color: var(--text);
  text-decoration: none;
}

.me-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 10rem;
}

@media (max-width: 30rem) {
  .me-name {
    display: none;
  }
}

.app-footer {
  padding: 2rem 1rem calc(5rem + env(safe-area-inset-bottom));
  text-align: center;
  font-size: 0.8rem;
}
</style>
