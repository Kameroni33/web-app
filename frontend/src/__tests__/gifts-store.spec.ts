import { beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/apps/gifts/api', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@/apps/gifts/api')>()),
  listMyItems: vi.fn<() => Promise<unknown>>(),
  listWishlists: vi.fn<() => Promise<unknown>>(),
  reorderItems: vi.fn<(ids: number[]) => Promise<void>>(),
}))

import * as api from '@/apps/gifts/api'
import { useMyGiftsStore } from '@/apps/gifts/store'
import { useAuthStore } from '@/stores/auth'

const item = (id: number, extra: Partial<api.MyItem> = {}): api.MyItem => ({
  id,
  title: `Item ${id}`,
  links: [],
  category: null,
  notes: null,
  received: false,
  ...extra,
})

const profile = (id: number) => ({ id, name: `P${id}`, role: 'member' as const, avatar_url: null })

describe('my gifts store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.resetAllMocks()
    useAuthStore().user = {
      id: 1,
      email: 'a@example.com',
      is_admin: false,
      can: { manage_groups: false },
      profile: profile(1),
    }
    vi.mocked(api.listMyItems).mockResolvedValue([
      item(1, { category: 'Books' }),
      item(2, { received: true, category: 'Games' }),
      item(3, { category: 'Books' }),
    ])
    vi.mocked(api.listWishlists).mockResolvedValue([])
  })

  it('splits wanted from received and lists categories once', async () => {
    const gifts = useMyGiftsStore()
    await gifts.load()

    expect(gifts.wanted.map((i) => i.id)).toEqual([1, 3])
    expect(gifts.received.map((i) => i.id)).toEqual([2])
    expect(gifts.categories).toEqual(['Books', 'Games'])
  })

  it('saves a new order of wanted items only', async () => {
    const gifts = useMyGiftsStore()
    await gifts.load()

    await gifts.reorder([gifts.wanted[1]!, gifts.wanted[0]!])

    expect(api.reorderItems).toHaveBeenCalledWith([3, 1])
    expect(gifts.items.map((i) => i.id)).toEqual([3, 1, 2])
  })

  it('forgets the list when another profile takes over', async () => {
    const gifts = useMyGiftsStore()
    await gifts.load()

    useAuthStore().setProfile(profile(2))
    await nextTick()

    expect(gifts.items).toEqual([])
    expect(gifts.loaded).toBe(false)
  })

  it('shortens links to their site', () => {
    expect(api.linkHost('https://www.amazon.com/dp/123')).toBe('amazon.com')
    expect(api.linkHost('not a url')).toBe('not a url')
  })
})
