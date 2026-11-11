import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import * as api from '@/api/auth'
import type { User } from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const loaded = ref(false)
  let loading: Promise<void> | null = null

  const isSignedIn = computed(() => user.value !== null)

  /** Load the session once; the router guard awaits this before every navigation. */
  function ensureLoaded(): Promise<void> {
    if (loaded.value) return Promise.resolve()
    loading ??= api
      .fetchCurrentUser()
      .then((current) => {
        user.value = current
        loaded.value = true
      })
      .finally(() => {
        loading = null
      })
    return loading
  }

  async function signInWithCode(email: string, code: string): Promise<void> {
    user.value = await api.verifyLoginCode(email, code)
    loaded.value = true
  }

  async function signInWithLink(token: string): Promise<void> {
    user.value = await api.verifyLoginLink(token)
    loaded.value = true
  }

  async function signOut(): Promise<void> {
    try {
      await api.logout()
    } finally {
      clearSession()
    }
  }

  /** Forget the user locally, e.g. after the server answers 401. */
  function clearSession(): void {
    user.value = null
    loaded.value = true
  }

  return {
    user,
    loaded,
    isSignedIn,
    ensureLoaded,
    signInWithCode,
    signInWithLink,
    signOut,
    clearSession,
  }
})
