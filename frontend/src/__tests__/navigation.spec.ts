import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { createRouter, createWebHistory, type Router } from 'vue-router'
import { goBack, landedOnAnotherProfile, resetHistory, tagEntry } from '@/router/navigation'

let profileId: number | null = 1
const Blank = { render: () => null }

function makeRouter(): Router {
  const router = createRouter({
    history: createWebHistory(),
    routes: ['/', '/a', '/b', '/c', '/home'].map((path) => ({ path, component: Blank })),
  })
  router.beforeEach((_to, from) =>
    landedOnAnotherProfile(from, profileId) ? { path: '/home', replace: true } : undefined,
  )
  router.afterEach((_to, _from, failure) => tagEntry(profileId, !failure))
  return router
}

const popped = () =>
  new Promise((resolve) => window.addEventListener('popstate', resolve, { once: true }))
const settle = () => new Promise((resolve) => setTimeout(resolve, 20))

describe('app navigation history', () => {
  let router: Router

  // Each test's router listens for popstate; stop the old ones.
  afterEach(() => router.options.history.destroy())

  beforeEach(async () => {
    profileId = 1
    window.history.replaceState(null, '', '/')
    router = makeRouter()
    await router.push('/')
    await router.isReady()
  })

  it('tracks depth as pages are opened', async () => {
    await router.push('/a')
    await router.push('/b')
    expect(window.history.state.kamDepth).toBe(2)
    expect(window.history.state.kamProfile).toBe(1)
  })

  it('collapses history when starting over', async () => {
    await router.push('/a')
    await router.push('/b')
    await resetHistory(router, '/c', 1)
    expect(router.currentRoute.value.path).toBe('/c')
    expect(window.history.state.kamDepth).toBe(0)
  })

  it('sends you home when going back lands on another profile’s page', async () => {
    await router.push('/a')
    await router.push('/b')
    profileId = 2 // switched without collapsing (the safety net's case)
    await router.push('/c')
    window.history.go(-2)
    await popped()
    await settle()
    expect(router.currentRoute.value.path).toBe('/home')
  })

  it('back links pop history when the parent is the previous page', async () => {
    await router.push('/a')
    await router.push('/b')
    const before = window.history.length
    void goBack(router, '/a')
    await popped()
    await settle()
    expect(router.currentRoute.value.path).toBe('/a')
    expect(window.history.state.kamDepth).toBe(1)
    expect(window.history.length).toBe(before)
  })
})
