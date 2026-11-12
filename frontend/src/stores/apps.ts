import { computed, ref, watch } from 'vue'
import { defineStore } from 'pinia'
import { fetchAvailableApps } from '@/api/apps'
import type { AvailableApp } from '@/api/apps'
import { appRegistry } from '@/apps/registry'
import type { AppEntry } from '@/apps/registry'
import { useAuthStore } from './auth'

export type LauncherApp = AvailableApp & AppEntry

export const useAppsStore = defineStore('apps', () => {
  const auth = useAuthStore()
  const available = ref<AvailableApp[]>([])
  const loaded = ref(false)
  let loading: Promise<void> | null = null

  /** Apps the server allows and this build knows how to show. */
  const apps = computed<LauncherApp[]>(() =>
    available.value.flatMap((app) => {
      const entry = appRegistry[app.key]
      return entry ? [{ ...app, ...entry }] : []
    }),
  )

  function ensureLoaded(): Promise<void> {
    if (loaded.value) return Promise.resolve()
    const forUser = auth.user?.id
    loading ??= fetchAvailableApps()
      .then((list) => {
        if (auth.user?.id !== forUser) return // signed out or switched mid-request
        available.value = list
        loaded.value = true
      })
      .finally(() => {
        loading = null
      })
    return loading
  }

  function find(key: string): LauncherApp | undefined {
    return apps.value.find((app) => app.key === key)
  }

  // A different person may sign in on this device: never show their list to the next one.
  watch(
    () => auth.user?.id,
    () => {
      available.value = []
      loaded.value = false
    },
  )

  return { apps, loaded, ensureLoaded, find }
})
