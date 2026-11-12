import { beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/api/apps', () => ({
  fetchAvailableApps: vi.fn<(...args: unknown[]) => Promise<unknown>>(),
}))

import * as api from '@/api/apps'
import { useAppsStore } from '@/stores/apps'
import { useAuthStore } from '@/stores/auth'

const account = (id: number) => ({
  id,
  email: `user${id}@example.com`,
  is_admin: false,
  can: { manage_groups: false },
})

describe('apps store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.resetAllMocks()
    useAuthStore().user = account(1)
  })

  it('shows only apps this build knows how to open', async () => {
    vi.mocked(api.fetchAvailableApps).mockResolvedValue([
      { key: 'gifts', name: 'Gift Lists', description: 'Wish lists.' },
      { key: 'from-a-newer-server', name: 'Future', description: 'Not in this build.' },
    ])
    const apps = useAppsStore()

    await apps.ensureLoaded()

    expect(apps.apps.map((app) => app.key)).toEqual(['gifts'])
    expect(apps.find('gifts')?.home).toEqual({ name: 'gifts' })
  })

  it('forgets the list when a different user signs in', async () => {
    vi.mocked(api.fetchAvailableApps).mockResolvedValue([
      { key: 'gifts', name: 'Gift Lists', description: 'Wish lists.' },
    ])
    const apps = useAppsStore()
    await apps.ensureLoaded()

    useAuthStore().user = account(2)
    await nextTick()

    expect(apps.loaded).toBe(false)
    expect(apps.apps).toEqual([])
    await apps.ensureLoaded()
    expect(api.fetchAvailableApps).toHaveBeenCalledTimes(2)
  })
})
