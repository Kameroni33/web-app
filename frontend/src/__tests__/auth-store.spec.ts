import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/api/auth', () => ({
  fetchCurrentUser: vi.fn<(...args: unknown[]) => Promise<unknown>>(),
  verifyLoginCode: vi.fn<(...args: unknown[]) => Promise<unknown>>(),
  verifyLoginLink: vi.fn<(...args: unknown[]) => Promise<unknown>>(),
  logout: vi.fn<(...args: unknown[]) => Promise<unknown>>(),
}))

import * as api from '@/api/auth'
import { useAuthStore } from '@/stores/auth'

const user = { id: 1, email: 'mom@example.com' }

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.resetAllMocks()
  })

  it('loads the session only once, even when asked concurrently', async () => {
    vi.mocked(api.fetchCurrentUser).mockResolvedValue(user)
    const auth = useAuthStore()

    await Promise.all([auth.ensureLoaded(), auth.ensureLoaded()])
    await auth.ensureLoaded()

    expect(api.fetchCurrentUser).toHaveBeenCalledTimes(1)
    expect(auth.isSignedIn).toBe(true)
  })

  it('treats no session as signed out', async () => {
    vi.mocked(api.fetchCurrentUser).mockResolvedValue(null)
    const auth = useAuthStore()

    await auth.ensureLoaded()

    expect(auth.loaded).toBe(true)
    expect(auth.isSignedIn).toBe(false)
  })

  it('retries loading after a network failure', async () => {
    vi.mocked(api.fetchCurrentUser).mockRejectedValueOnce(new Error('offline'))
    vi.mocked(api.fetchCurrentUser).mockResolvedValueOnce(user)
    const auth = useAuthStore()

    await expect(auth.ensureLoaded()).rejects.toThrow('offline')
    await auth.ensureLoaded()

    expect(auth.user).toEqual(user)
  })

  it('signs in with a code and clears the user on sign-out even if the request fails', async () => {
    vi.mocked(api.verifyLoginCode).mockResolvedValue(user)
    vi.mocked(api.logout).mockRejectedValue(new Error('offline'))
    const auth = useAuthStore()

    await auth.signInWithCode(user.email, '123456')
    expect(auth.user).toEqual(user)

    await expect(auth.signOut()).rejects.toThrow('offline')
    expect(auth.isSignedIn).toBe(false)
  })
})
