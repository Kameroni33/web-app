<script setup lang="ts">
import { computed, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import AppUpdatePrompt from '@/components/AppUpdatePrompt.vue'
import ProfileAvatar from '@/components/ProfileAvatar.vue'
import { useInstallPrompt } from '@/composables/useInstallPrompt'
import { useAppsStore } from '@/stores/apps'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const appsStore = useAppsStore()
const route = useRoute()
const { installed } = useInstallPrompt()

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

const logoUrl = __LOGO_URL__
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
        <img :src="logoUrl" alt="" width="28" height="28" />
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
      <ProfileAvatar
        :name="auth.profile.name"
        :id="auth.profile.id"
        :src="auth.profile.avatar_url"
        :size="32"
      />
    </RouterLink>
  </header>

  <RouterView />

  <footer class="app-footer muted">
    <span :title="`Built ${buildTime}`">Kam Apps v{{ version }}</span>
    <template v-if="!installed">
      · <RouterLink to="/install">Install</RouterLink>
    </template>
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

/* Home and account links: a pill that highlights on hover and sinks when pressed. */
.home-link,
.me-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
  margin: -0.3rem -0.5rem;
  padding: 0.3rem 0.5rem;
  color: var(--text);
  text-decoration: none;
  border-radius: 999px;
  transition:
    background-color 0.15s,
    transform 0.1s;
}

.home-link:hover,
.me-link:hover {
  background: var(--surface);
}

.home-link:active,
.me-link:active {
  background: var(--border);
  transform: scale(0.94);
}

@media (prefers-reduced-motion: reduce) {
  .home-link:active,
  .me-link:active {
    transform: none;
  }
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
