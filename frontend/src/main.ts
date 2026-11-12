import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { AxiosError } from 'axios'

import App from './App.vue'
import router from './router'
import { http } from './api/http'
import { listenForInstallPrompt } from './composables/useInstallPrompt'
import { applyTheme } from './composables/useTheme'
import { useAuthStore } from './stores/auth'

applyTheme()
listenForInstallPrompt()
// iOS Safari only applies :active (our pressed styles) once a touch listener exists.
document.addEventListener('touchstart', () => {}, { passive: true })

const app = createApp(App)

app.use(createPinia())
app.use(router)

// Session ended server-side (expired, or signed out from another device):
// forget the user and send them to sign in.
const auth = useAuthStore()
http.interceptors.response.use(undefined, async (error) => {
  const url = error instanceof AxiosError ? (error.config?.url ?? '') : ''
  if (error instanceof AxiosError && error.response?.status === 401 && url !== '/api/me') {
    auth.clearSession()
    const current = router.currentRoute.value
    if (current.meta.requiresAuth) {
      await router.replace({ name: 'login', query: { redirect: current.fullPath } })
    }
  }
  // This device's profile was deleted (or never chosen): ask who's using the app.
  if (
    error instanceof AxiosError &&
    error.response?.status === 409 &&
    (error.response.data as { code?: string } | undefined)?.code === 'profile_required'
  ) {
    auth.setProfile(null)
    const current = router.currentRoute.value
    if (current.name !== 'choose-profile') {
      await router.replace({ name: 'choose-profile', query: { redirect: current.fullPath } })
    }
  }
  throw error
})

app.mount('#app')
